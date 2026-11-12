<?php

namespace App\Libraries;

use App\Filters\BatasUnggahFilter;
use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Menyimpan gambar dan berkas unggahan admin di folder publik.
 *
 * Berkas disimpan di `public/unggahan/<folder>/<nama acak>.<ekstensi>`.
 * Yang disimpan di pengaturan atau tabel adalah jalur relatifnya,
 * `unggahan/<folder>/<nama acak>.<ekstensi>`, dan alamatnya `base_url($jalur)`.
 *
 * - Folder yang boleh dipakai: `FOLDER` (`halaman-depan`, `pembayaran`, `unduhan`).
 * - Gambar: JPG, PNG, atau WEBP, paling besar 2 MB. Gambar yang sisi
 *   terpanjangnya lebih dari `SISI_GAMBAR` piksel dikecilkan saat disimpan,
 *   dengan format yang sama (PNG dan WEBP tetap transparan).
 * - Berkas unduhan: PDF, JPG, PNG, DOC/DOCX, XLS/XLSX, paling besar 10 MB.
 * - Jenis berkas diperiksa dari isinya, bukan dari namanya. Ekstensi yang
 *   disimpan selalu dari daftar yang diizinkan, tidak pernah disalin dari nama
 *   berkas pengunggah, jadi tidak ada berkas yang bisa dijalankan di folder publik.
 *
 * Pemakaian di pengendali (contoh logo bank, F2-05):
 *
 *     $unggahan = new Unggahan();
 *     $berkas   = $this->request->getFile('logo_bank');
 *
 *     if (Unggahan::dipilih($berkas)) {
 *         $pesan = $unggahan->periksaGambar($berkas);     // null = sah
 *         if ($pesan !== null) {
 *             $galat['logo_bank'] = $pesan;                 // tampilkan ulang formulir
 *         }
 *     }
 *
 *     // … setelah semua isian sah:
 *     $lama  = setting('Pembayaran.logo_bank');
 *     $jalur = $unggahan->simpanGambar($berkas, 'pembayaran');  // 'unggahan/pembayaran/….png'
 *     setting('Pembayaran.logo_bank', $jalur);
 *     $unggahan->hapus($lama);                              // berkas lama dibuang setelah yang baru tersimpan
 *
 * Untuk berkas unduhan (F2-07) sama, dengan `periksaBerkas()` dan
 * `simpanBerkas($berkas, 'unduhan')`. Ukuran dan jenis berkas yang tersimpan:
 * `ukuran($jalur)`, `jenis($jalur)`, `Unggahan::ukuranTeks($bita)`.
 *
 * Di uji, folder dasar diganti supaya uji tidak menulis ke `public/unggahan/`:
 * pakai sifat `Tests\Support\UnggahanSementara` (memanggil `gantiFolderDasar()`).
 */
class Unggahan
{
    /**
     * Folder di bawah `unggahan/` yang boleh dipakai.
     */
    public const FOLDER = ['halaman-depan', 'pembayaran', 'unduhan'];

    /**
     * Gambar: ekstensi tersimpan => jenis isi (MIME) yang diterima.
     *
     * @var array<string, list<string>>
     */
    public const JENIS_GAMBAR = [
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /**
     * Berkas unduhan: ekstensi tersimpan => jenis isi (MIME) yang diterima.
     *
     * Dokumen Office lama (DOC, XLS) dan baru (DOCX, XLSX) kadang dikenali
     * hanya sebagai "berkas OLE" atau "ZIP"; untuk jenis isi yang sama milik
     * lebih dari satu ekstensi, ekstensi nama berkas dipakai untuk memilih di
     * antara ekstensi yang diizinkan itu saja.
     *
     * @var array<string, list<string>>
     */
    public const JENIS_BERKAS = [
        'pdf'  => ['application/pdf', 'application/x-pdf'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png'],
        'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/cdfv2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/x-zip-compressed'],
        'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2', 'application/cdfv2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-zip-compressed'],
    ];

    /**
     * Ukuran paling besar dalam bita.
     */
    public const BATAS_GAMBAR = 2 * 1024 * 1024;

    public const BATAS_BERKAS = 10 * 1024 * 1024;

    /**
     * Sisi terpanjang gambar tersimpan dalam piksel. Gambar yang lebih besar
     * dikecilkan; yang lebih kecil disimpan apa adanya.
     */
    public const SISI_GAMBAR = 1920;

    /**
     * Mutu simpan JPG dan WEBP setelah dikecilkan (0–100).
     */
    private const MUTU_GAMBAR = 85;

    /**
     * Folder dasar pengganti untuk uji; `null` = `public/unggahan/`.
     */
    private static ?string $folderDasarUji = null;

    /**
     * Ganti folder dasar (hanya untuk uji). Selama diganti, berkas sumber yang
     * bukan hasil unggahan HTTP sungguhan juga diterima, karena uji tidak bisa
     * membuat unggahan sungguhan. `null` mengembalikan ke `public/unggahan/`.
     */
    public static function gantiFolderDasar(?string $folder): void
    {
        self::$folderDasarUji = $folder === null ? null : rtrim($folder, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Folder dasar penyimpanan, diakhiri pemisah folder. Jalur `unggahan/…`
     * berada di bawahnya: `<folder dasar><folder>/<nama>`.
     */
    public static function folderDasar(): string
    {
        return self::$folderDasarUji ?? FCPATH . 'unggahan' . DIRECTORY_SEPARATOR;
    }

    /**
     * Apakah admin memilih berkas di kotak unggah ini. Kotak yang dibiarkan
     * kosong (misalnya saat mengubah data tanpa mengganti gambar) bernilai `false`.
     */
    public static function dipilih(?UploadedFile $berkas): bool
    {
        return $berkas !== null && $berkas->getError() !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Periksa gambar. `null` bila sah; selain itu pesan ramah yang menyebut
     * cara memperbaikinya.
     */
    public function periksaGambar(?UploadedFile $berkas): ?string
    {
        return $this->periksa($berkas, self::JENIS_GAMBAR, self::BATAS_GAMBAR, 'gambar')['pesan'];
    }

    /**
     * Periksa berkas unduhan. `null` bila sah; selain itu pesan ramah.
     */
    public function periksaBerkas(?UploadedFile $berkas): ?string
    {
        return $this->periksa($berkas, self::JENIS_BERKAS, self::BATAS_BERKAS, 'berkas')['pesan'];
    }

    /**
     * Simpan gambar ke `unggahan/<folder>/` dan kembalikan jalur relatifnya.
     * Gambar yang sisi terpanjangnya lebih dari `SISI_GAMBAR` piksel dikecilkan.
     *
     * @throws RuntimeException Bila gambar tidak sah atau gagal disimpan; pesannya ramah.
     */
    public function simpanGambar(?UploadedFile $berkas, string $folder): string
    {
        $jalur = $this->simpan($berkas, $folder, self::JENIS_GAMBAR, self::BATAS_GAMBAR, 'gambar');

        self::kecilkanGambar((string) $this->jalurPenuh($jalur));

        return $jalur;
    }

    /**
     * Simpan berkas unduhan ke `unggahan/<folder>/` dan kembalikan jalur relatifnya.
     *
     * @throws RuntimeException Bila berkas tidak sah atau gagal disimpan; pesannya ramah.
     */
    public function simpanBerkas(?UploadedFile $berkas, string $folder): string
    {
        return $this->simpan($berkas, $folder, self::JENIS_BERKAS, self::BATAS_BERKAS, 'berkas');
    }

    /**
     * Hapus berkas tersimpan. Jalur kosong, jalur di luar `unggahan/<folder>/`,
     * atau berkas yang sudah tidak ada diabaikan. `true` bila berkas terhapus.
     */
    public function hapus(?string $jalur): bool
    {
        $penuh = $this->jalurPenuh($jalur);

        return $penuh !== null && is_file($penuh) && @unlink($penuh);
    }

    /**
     * Apakah berkas untuk jalur ini ada.
     */
    public function ada(?string $jalur): bool
    {
        $penuh = $this->jalurPenuh($jalur);

        return $penuh !== null && is_file($penuh);
    }

    /**
     * Ukuran berkas tersimpan dalam bita, atau `null` bila tidak ada.
     */
    public function ukuran(?string $jalur): ?int
    {
        $penuh = $this->jalurPenuh($jalur);

        if ($penuh === null || ! is_file($penuh)) {
            return null;
        }

        $ukuran = filesize($penuh);

        return $ukuran === false ? null : $ukuran;
    }

    /**
     * Jenis berkas untuk ditampilkan, dari ekstensi tersimpan: `PDF`, `JPG`, `DOCX`, …
     * Jalur yang tidak sah menghasilkan `''`.
     */
    public function jenis(?string $jalur): string
    {
        return $this->jalurPenuh($jalur) === null ? '' : strtoupper(pathinfo((string) $jalur, PATHINFO_EXTENSION));
    }

    /**
     * Jalur lengkap di disk untuk jalur relatif `unggahan/<folder>/<nama>.<ekstensi>`,
     * atau `null` bila bentuknya tidak sah (mencegah jalur keluar dari folder unggahan).
     */
    public function jalurPenuh(?string $jalur): ?string
    {
        $jalur = (string) $jalur;
        $pola  = '#^unggahan/(' . implode('|', array_map(static fn (string $f): string => preg_quote($f, '#'), self::FOLDER)) . ')/([a-f0-9]{32}\.[a-z0-9]{2,5})$#';

        if (preg_match($pola, $jalur, $cocok) !== 1) {
            return null;
        }

        return self::folderDasar() . $cocok[1] . DIRECTORY_SEPARATOR . $cocok[2];
    }

    /**
     * Ukuran yang mudah dibaca: `850 KB`, `1,5 MB`, `10 MB`.
     */
    public static function ukuranTeks(int $bita): string
    {
        if ($bita < 1024) {
            return $bita . ' bita';
        }

        if ($bita < 1024 * 1024) {
            return max(1, (int) round($bita / 1024)) . ' KB';
        }

        $mb = round($bita / (1024 * 1024), 1);

        return rtrim(rtrim(number_format($mb, 1, ',', '.'), '0'), ',') . ' MB';
    }

    /**
     * @param array<string, list<string>> $jenisIzin
     *
     * @return array{pesan: string|null, ekstensi: string|null}
     */
    private function periksa(?UploadedFile $berkas, array $jenisIzin, int $batas, string $sebutan): array
    {
        $daftarJenis = self::teksJenis($jenisIzin);
        $batasTeks   = self::ukuranTeks($batas);
        $gagal       = static fn (string $pesan): array => ['pesan' => $pesan, 'ekstensi' => null];

        if (! self::dipilih($berkas)) {
            return $gagal("Belum ada {$sebutan} yang dipilih. Pilih {$sebutan} {$daftarJenis} dari komputer atau ponsel Anda.");
        }

        $kode = $berkas->getError();

        if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) {
            return $gagal("Ukuran {$sebutan} terlalu besar. Paling besar {$batasTeks}; perkecil dulu, lalu unggah lagi.");
        }

        if ($kode === UPLOAD_ERR_PARTIAL) {
            return $gagal("Pengiriman {$sebutan} terputus. Periksa sambungan internet, lalu unggah sekali lagi.");
        }

        $sumber = $berkas->getTempName();

        if ($kode !== UPLOAD_ERR_OK || $sumber === '' || ! is_file($sumber) || ! $this->sumberDiterima($sumber)) {
            return $gagal(ucfirst($sebutan) . ' gagal diterima. Coba unggah sekali lagi; bila masih gagal, hubungi pengelola komputer server.');
        }

        $ukuran = filesize($sumber);

        if ($ukuran === false || $ukuran === 0) {
            return $gagal(ucfirst($sebutan) . " yang dipilih kosong. Pilih {$sebutan} lain yang berisi.");
        }

        if ($ukuran > $batas) {
            return $gagal('Ukuran ' . $sebutan . ' ' . self::ukuranTeks($ukuran) . ", lebih dari batas {$batasTeks}. Perkecil dulu, lalu unggah lagi.");
        }

        $ekstensi = $this->tentukanEkstensi($sumber, (string) $berkas->getClientExtension(), $jenisIzin);

        if ($ekstensi === null) {
            return $gagal("Jenis {$sebutan} tidak bisa dipakai. Unggah {$sebutan} {$daftarJenis}.");
        }

        return ['pesan' => null, 'ekstensi' => $ekstensi];
    }

    /**
     * @param array<string, list<string>> $jenisIzin
     */
    private function simpan(?UploadedFile $berkas, string $folder, array $jenisIzin, int $batas, string $sebutan): string
    {
        if (! in_array($folder, self::FOLDER, true)) {
            throw new RuntimeException('Folder unggahan tidak dikenal: ' . $folder . '. Pakai salah satu: ' . implode(', ', self::FOLDER) . '.');
        }

        $hasil = $this->periksa($berkas, $jenisIzin, $batas, $sebutan);

        if ($hasil['pesan'] !== null) {
            throw new RuntimeException($hasil['pesan']);
        }

        $tujuanFolder = self::folderDasar() . $folder;

        if (! is_dir($tujuanFolder) && ! @mkdir($tujuanFolder, 0755, true) && ! is_dir($tujuanFolder)) {
            throw new RuntimeException(ucfirst($sebutan) . ' gagal disimpan karena folder unggahan tidak bisa dibuat. Pastikan folder public/unggahan boleh ditulisi, lalu coba lagi.');
        }

        $nama   = bin2hex(random_bytes(16)) . '.' . $hasil['ekstensi'];
        $tujuan = $tujuanFolder . DIRECTORY_SEPARATOR . $nama;
        $sumber = $berkas->getTempName();

        $berhasil = is_uploaded_file($sumber)
            ? @move_uploaded_file($sumber, $tujuan)
            : (self::$folderDasarUji !== null && @copy($sumber, $tujuan));

        if (! $berhasil) {
            throw new RuntimeException(ucfirst($sebutan) . ' gagal disimpan. Pastikan folder public/unggahan boleh ditulisi dan ruang penyimpanan masih cukup, lalu coba lagi.');
        }

        @chmod($tujuan, 0644);

        return 'unggahan/' . $folder . '/' . $nama;
    }

    /**
     * Kecilkan gambar tersimpan di `$penuh` bila sisi terpanjangnya lebih dari
     * `SISI_GAMBAR` piksel. Format tetap sama; PNG dan WEBP tetap transparan,
     * dan arah foto JPG dari kamera (EXIF) diterapkan supaya tidak terbalik.
     *
     * Bila gambar tidak bisa diolah (misalnya memori tidak cukup), berkas
     * aslinya dibiarkan, karena sudah lolos batas ukuran unggah.
     *
     * `true` bila gambar dikecilkan.
     */
    public static function kecilkanGambar(string $penuh): bool
    {
        $info = @getimagesize($penuh);

        if ($info === false || max($info[0], $info[1]) <= self::SISI_GAMBAR) {
            return false;
        }

        [$lebar, $tinggi, $jenis] = $info;
        $buka = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];

        if (! isset($buka[$jenis]) || ! function_exists($buka[$jenis])) {
            return false;
        }

        $skala      = self::SISI_GAMBAR / max($lebar, $tinggi);
        $lebarBaru  = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        self::cukupkanMemori(($lebar * $tinggi + $lebarBaru * $tinggiBaru) * 5);

        $asli = @$buka[$jenis]($penuh);

        if ($asli === false) {
            return false;
        }

        $baru = imagecreatetruecolor($lebarBaru, $tinggiBaru);

        if ($jenis !== IMAGETYPE_JPEG) {
            imagealphablending($baru, false);
            imagesavealpha($baru, true);
            imagefill($baru, 0, 0, imagecolorallocatealpha($baru, 0, 0, 0, 127));
        }

        imagecopyresampled($baru, $asli, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
        imagedestroy($asli);

        if ($jenis === IMAGETYPE_JPEG) {
            $baru = self::putarSesuaiExif($baru, $penuh);
        }

        $sementara = $penuh . '.sementara';
        $tersimpan = match ($jenis) {
            IMAGETYPE_JPEG => imagejpeg($baru, $sementara, self::MUTU_GAMBAR),
            IMAGETYPE_PNG  => imagepng($baru, $sementara, 9),
            default        => imagewebp($baru, $sementara, self::MUTU_GAMBAR),
        };
        imagedestroy($baru);

        if (! $tersimpan || ! @rename($sementara, $penuh)) {
            @unlink($sementara);

            return false;
        }

        @chmod($penuh, 0644);
        clearstatcache(true, $penuh);

        return true;
    }

    /**
     * Terapkan arah foto dari data EXIF (foto ponsel yang diambil miring),
     * karena data EXIF hilang saat gambar disimpan ulang.
     */
    private static function putarSesuaiExif(\GdImage $gambar, string $penuh): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $gambar;
        }

        $exif  = @exif_read_data($penuh);
        $sudut = match ((int) ($exif['Orientation'] ?? 1)) {
            3       => 180,
            6       => -90,
            8       => 90,
            default => 0,
        };

        if ($sudut === 0) {
            return $gambar;
        }

        $diputar = imagerotate($gambar, $sudut, 0);

        if ($diputar === false) {
            return $gambar;
        }

        imagedestroy($gambar);

        return $diputar;
    }

    /**
     * Naikkan batas memori PHP bila kurang dari `$bita` (gambar besar butuh
     * sekitar 5 bita per piksel saat diolah GD).
     */
    private static function cukupkanMemori(int $bita): void
    {
        $batas = BatasUnggahFilter::bitaDariIni((string) ini_get('memory_limit'));

        if ($batas <= 0) {
            return;
        }

        $perlu = memory_get_usage() + $bita;

        if ($perlu > $batas) {
            @ini_set('memory_limit', (string) $perlu);
        }
    }

    /**
     * Berkas sumber harus hasil unggahan HTTP sungguhan, kecuali di uji
     * (folder dasar diganti).
     */
    private function sumberDiterima(string $sumber): bool
    {
        return is_uploaded_file($sumber) || self::$folderDasarUji !== null;
    }

    /**
     * Ekstensi tersimpan dari isi berkas, atau `null` bila jenisnya tidak diizinkan.
     *
     * @param array<string, list<string>> $jenisIzin
     */
    private function tentukanEkstensi(string $sumber, string $ekstensiKlien, array $jenisIzin): ?string
    {
        $mime = $this->jenisIsi($sumber);

        if ($mime === null) {
            return null;
        }

        $calon = [];

        foreach ($jenisIzin as $ekstensi => $daftarMime) {
            if (in_array($mime, $daftarMime, true)) {
                $calon[] = $ekstensi;
            }
        }

        if ($calon === []) {
            return null;
        }

        $ekstensiKlien = strtolower($ekstensiKlien) === 'jpeg' ? 'jpg' : strtolower($ekstensiKlien);

        // Satu calon: dipakai, apa pun nama berkasnya. Beberapa calon (berkas
        // OLE/ZIP): pilih yang sama dengan ekstensi nama berkas, bila tidak ada
        // yang sama berkas ditolak.
        if (count($calon) === 1) {
            $ekstensi = $calon[0];
        } elseif (in_array($ekstensiKlien, $calon, true)) {
            $ekstensi = $ekstensiKlien;
        } else {
            return null;
        }

        // Gambar harus benar-benar bisa dibaca sebagai gambar.
        if (in_array($ekstensi, ['jpg', 'png', 'webp'], true) && @getimagesize($sumber) === false) {
            return null;
        }

        return $ekstensi;
    }

    /**
     * Jenis isi (MIME) dari isi berkas.
     */
    private function jenisIsi(string $sumber): ?string
    {
        if (! class_exists('finfo')) {
            return null;
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($sumber);

        return is_string($mime) && $mime !== '' ? $mime : null;
    }

    /**
     * `JPG, PNG, atau WEBP` dari daftar jenis.
     *
     * @param array<string, list<string>> $jenisIzin
     */
    private static function teksJenis(array $jenisIzin): string
    {
        $nama = array_map('strtoupper', array_keys($jenisIzin));
        $akhir = array_pop($nama);

        return $nama === [] ? (string) $akhir : implode(', ', $nama) . ', atau ' . $akhir;
    }
}
