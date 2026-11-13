<?php

namespace App\Libraries\Panel;

use App\Models\BuktiBayarModel;
use CodeIgniter\HTTP\Files\UploadedFile;
use GdImage;
use RuntimeException;
use Throwable;

/**
 * Berkas gambar bukti bayar yang diunggah admin atau official (K34, K83; rencana Tahap 6,
 * "Bukti bayar").
 *
 * - Diterima: gambar JPG, PNG, atau WEBP yang benar-benar terbaca, paling
 *   besar `BATAS_BITA`. Jenisnya diperiksa dari isi, bukan dari nama berkas.
 * - Gambar dibaca ulang lewat GD dan disimpan ulang sebagai JPEG, sisi
 *   terpanjang paling besar `SISI_MAKSIMAL` piksel. Data tambahan berkas
 *   (lokasi kamera dan sebagainya) ikut terbuang.
 * - Disimpan di luar folder publik dengan nama acak:
 *   `WRITEPATH . 'unggahan/bukti-bayar/<32 heksa>.jpg'`. Yang disimpan di
 *   `bukti_bayar.berkas` jalur relatifnya, contoh `bukti-bayar/3f9c….jpg`,
 *   dan berkasnya hanya disajikan lewat rute admin.
 * - Batas 5 bukti per pendaftaran dijaga aplikasi: `BuktiBayarModel::BATAS`.
 *
 * Pemakaian di pengendali (F6-03):
 *
 *     $berkas = new BerkasBuktiBayar();
 *     $unggah = $this->request->getFile('bukti');
 *     $pesan  = $berkas->periksa($unggah);          // null = sah, selain itu pesan ramah
 *
 *     $hasil = $berkas->simpan($unggah);            // ['berkas' => 'bukti-bayar/….jpg', 'ukuran' => 183204]
 *     model(BuktiBayarModel::class)->insert(['pendaftaran_id' => $id] + $hasil);
 *
 *     $penuh = $berkas->jalurPenuh($baris['berkas']);   // untuk dikirim ke peramban
 *     $berkas->hapus($baris['berkas']);
 *
 * Saat pendaftaran dihapus (F6-02), baris `bukti_bayar` ikut terhapus lewat
 * kunci asing; ambil daftar berkasnya lebih dulu, lalu hapus berkasnya
 * setelah transaksi berhasil:
 *
 *     $daftar = $berkas->berkasMilik($id);
 *     // … hapus pendaftaran dalam transaksi …
 *     $berkas->hapusBerkas($daftar);
 *
 * Bila barisnya sendiri juga harus dihapus: `hapusMilik($pendaftaranId)`.
 *
 * Di uji, folder dasar diganti dengan `gantiFolderDasar()`; selama diganti,
 * berkas yang bukan hasil unggahan HTTP sungguhan juga diterima.
 */
final class BerkasBuktiBayar
{
    public const SISI_MAKSIMAL = 1600;

    /**
     * Ukuran unggahan paling besar. Foto ponsel biasanya 2–6 MB.
     */
    public const BATAS_BITA = 10 * 1024 * 1024;

    public const FOLDER = 'bukti-bayar';

    public const MUTU_JPEG = 85;

    /**
     * Hasil `unggahKe()` bila batas bukti per pendaftaran sudah tercapai;
     * pesannya dipilih pemanggil (admin dan official berbeda saran).
     */
    public const PENUH = 'penuh';

    /**
     * Jenis gambar GD yang diterima.
     */
    private const JENIS = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    private static ?string $folderDasar = null;

    public static function gantiFolderDasar(?string $folder): void
    {
        self::$folderDasar = $folder === null ? null : rtrim($folder, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * Folder induk unggahan di luar `public/`, diakhiri pemisah folder.
     */
    public static function folderDasar(): string
    {
        return self::$folderDasar ?? WRITEPATH . 'unggahan' . DIRECTORY_SEPARATOR;
    }

    /**
     * Periksa unggahan. `null` bila sah; selain itu pesan ramah yang menyebut
     * cara memperbaikinya.
     */
    public function periksa(?UploadedFile $unggahan): ?string
    {
        if ($unggahan === null || $unggahan->getError() === UPLOAD_ERR_NO_FILE) {
            return 'Belum ada gambar yang dipilih. Pilih foto atau tangkapan layar bukti bayar (JPG, PNG, atau WEBP).';
        }

        $kode = $unggahan->getError();

        if ($kode === UPLOAD_ERR_INI_SIZE || $kode === UPLOAD_ERR_FORM_SIZE) {
            return 'Gambar terlalu besar. Paling besar 10 MB; perkecil dulu, lalu unggah lagi.';
        }

        $sumber = $unggahan->getTempName();

        if ($kode !== UPLOAD_ERR_OK || $sumber === '' || ! is_file($sumber) || ! (is_uploaded_file($sumber) || self::$folderDasar !== null)) {
            return 'Gambar gagal diterima. Coba unggah sekali lagi.';
        }

        $ukuran = filesize($sumber);

        if ($ukuran === false || $ukuran === 0) {
            return 'Berkas yang dipilih kosong. Pilih gambar lain.';
        }

        if ($ukuran > self::BATAS_BITA) {
            return 'Gambar terlalu besar. Paling besar 10 MB; perkecil dulu, lalu unggah lagi.';
        }

        return self::periksaIsi((string) file_get_contents($sumber));
    }

    /**
     * Simpan unggahan sebagai JPEG.
     *
     * @return array{berkas: string, ukuran: int} Jalur relatif dan ukuran berkas tersimpan.
     *
     * @throws RuntimeException Bila gambar tidak sah atau gagal disimpan; pesannya ramah.
     */
    public function simpan(?UploadedFile $unggahan): array
    {
        $pesan = $this->periksa($unggahan);

        if ($pesan !== null) {
            throw new RuntimeException($pesan);
        }

        return $this->simpanIsi((string) file_get_contents($unggahan->getTempName()));
    }

    /**
     * Simpan isi gambar (bita JPG, PNG, atau WEBP) sebagai JPEG.
     *
     * @return array{berkas: string, ukuran: int}
     *
     * @throws RuntimeException Bila gambar tidak sah atau gagal disimpan; pesannya ramah.
     */
    public function simpanIsi(string $isi): array
    {
        $pesan = self::periksaIsi($isi);

        if ($pesan !== null) {
            throw new RuntimeException($pesan);
        }

        $gambar = @imagecreatefromstring($isi);

        if (! $gambar instanceof GdImage) {
            throw new RuntimeException('Gambar tidak bisa dibaca. Pilih foto atau tangkapan layar lain.');
        }

        $folder = self::folderDasar() . self::FOLDER;

        try {
            $gambar = self::perkecil($gambar);

            if (! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) {
                throw new RuntimeException('Gambar gagal disimpan karena folder unggahan tidak bisa dibuat. Pastikan folder writable/unggahan boleh ditulisi.');
            }

            $jalur = self::FOLDER . '/' . bin2hex(random_bytes(16)) . '.jpg';
            $penuh = self::folderDasar() . $jalur;

            if (! imagejpeg($gambar, $penuh, self::MUTU_JPEG)) {
                throw new RuntimeException('Gambar gagal disimpan. Pastikan ruang penyimpanan masih cukup, lalu coba lagi.');
            }
        } finally {
            imagedestroy($gambar);
        }

        clearstatcache(true, $penuh);

        return ['berkas' => $jalur, 'ukuran' => (int) filesize($penuh)];
    }

    /**
     * Periksa, simpan, lalu catat satu bukti bayar untuk pendaftaran
     * `$pendaftaranId`. Dipakai panel admin dan official (K83).
     *
     * Hasil `null` bila tersimpan, `self::PENUH` bila batas sudah tercapai,
     * selain itu pesan galat ramah. Berkas disimpan di luar transaksi
     * (mengecilkan gambar butuh waktu); di dalam transaksi baris pendaftaran
     * dikunci supaya dua unggahan bersamaan tidak sama-sama lolos batas.
     * Bila baris gagal dicatat, berkas baru dihapus lagi.
     */
    public function unggahKe(int $pendaftaranId, ?UploadedFile $unggahan): ?string
    {
        $model = model(BuktiBayarModel::class);

        if (! $model->masihBolehTambah($pendaftaranId)) {
            return self::PENUH;
        }

        $pesan = $this->periksa($unggahan);

        if ($pesan !== null) {
            return 'Bukti bayar belum tersimpan. ' . $pesan;
        }

        try {
            $hasil = $this->simpan($unggahan);
        } catch (RuntimeException $e) {
            return 'Bukti bayar belum tersimpan. ' . $e->getMessage();
        }

        $db = $model->db;
        $db->transBegin();

        try {
            $db->query('SELECT id FROM ' . $db->prefixTable('pendaftaran') . ' WHERE id = ? FOR UPDATE', [$pendaftaranId]);

            if ($model->jumlahMilik($pendaftaranId) >= BuktiBayarModel::BATAS) {
                $db->transRollback();
                $this->hapus($hasil['berkas']);

                return self::PENUH;
            }

            $baru = $model->insert(['pendaftaran_id' => $pendaftaranId] + $hasil);
        } catch (Throwable $e) {
            $db->transRollback();
            $this->hapus($hasil['berkas']);

            throw $e;
        }

        if ($baru === false) {
            $db->transRollback();
            $this->hapus($hasil['berkas']);

            return 'Bukti bayar belum tersimpan karena ada gangguan di server. Coba unggah sekali lagi; bila masih gagal, hubungi panitia.';
        }

        $db->transCommit();

        return null;
    }

    /**
     * Jalur lengkap di disk, atau `null` bila bentuk jalurnya tidak sah
     * (mencegah jalur keluar dari folder bukti bayar).
     */
    public function jalurPenuh(?string $jalur): ?string
    {
        if ($jalur === null || preg_match('#^' . self::FOLDER . '/[0-9a-f]{32}\.jpg$#D', $jalur) !== 1) {
            return null;
        }

        return self::folderDasar() . $jalur;
    }

    /**
     * Hapus satu berkas. Jalur tidak sah atau berkas yang sudah tidak ada diabaikan.
     */
    public function hapus(?string $jalur): bool
    {
        $penuh = $this->jalurPenuh($jalur);

        return $penuh !== null && is_file($penuh) && @unlink($penuh);
    }

    /**
     * Jalur berkas bukti bayar milik satu pendaftaran.
     *
     * @return list<string>
     */
    public function berkasMilik(int $pendaftaranId): array
    {
        return array_column(model(BuktiBayarModel::class)->milikPendaftaran($pendaftaranId), 'berkas');
    }

    /**
     * Hapus beberapa berkas; hasilnya jumlah yang terhapus.
     *
     * @param list<string> $daftar
     */
    public function hapusBerkas(array $daftar): int
    {
        $jumlah = 0;

        foreach ($daftar as $jalur) {
            $jumlah += $this->hapus($jalur) ? 1 : 0;
        }

        return $jumlah;
    }

    /**
     * Hapus semua bukti bayar satu pendaftaran: barisnya, lalu berkasnya.
     * Hasilnya jumlah baris yang terhapus.
     */
    public function hapusMilik(int $pendaftaranId): int
    {
        $model  = model(BuktiBayarModel::class);
        $daftar = $model->milikPendaftaran($pendaftaranId);

        if ($daftar === []) {
            return 0;
        }

        $model->where('pendaftaran_id', $pendaftaranId)->delete();
        $this->hapusBerkas(array_column($daftar, 'berkas'));

        return count($daftar);
    }

    /**
     * `null` bila isi berkas gambar JPG, PNG, atau WEBP yang terbaca.
     */
    private static function periksaIsi(string $isi): ?string
    {
        if ($isi === '') {
            return 'Berkas yang dipilih kosong. Pilih gambar lain.';
        }

        if (strlen($isi) > self::BATAS_BITA) {
            return 'Gambar terlalu besar. Paling besar 10 MB; perkecil dulu, lalu unggah lagi.';
        }

        $info = @getimagesizefromstring($isi);

        if ($info === false || ! in_array($info[2], self::JENIS, true) || $info[0] < 1 || $info[1] < 1) {
            return 'Berkas ini bukan gambar yang bisa dipakai. Unggah foto atau tangkapan layar bukti bayar (JPG, PNG, atau WEBP).';
        }

        // Gambar raksasa menghabiskan memori GD sebelum sempat dikecilkan.
        if ($info[0] * $info[1] > 40_000_000) {
            return 'Ukuran gambar terlalu besar. Perkecil dulu, misalnya dengan tangkapan layar, lalu unggah lagi.';
        }

        return null;
    }

    /**
     * Gambar dengan sisi terpanjang paling besar `SISI_MAKSIMAL`, berlatar
     * putih (bagian transparan PNG/WEBP tidak menjadi hitam di JPEG).
     */
    private static function perkecil(GdImage $gambar): GdImage
    {
        $lebar   = imagesx($gambar);
        $tinggi  = imagesy($gambar);
        $skala   = min(1, self::SISI_MAKSIMAL / max($lebar, $tinggi));
        $lebarBaru  = max(1, (int) round($lebar * $skala));
        $tinggiBaru = max(1, (int) round($tinggi * $skala));

        $hasil = imagecreatetruecolor($lebarBaru, $tinggiBaru);
        imagefill($hasil, 0, 0, (int) imagecolorallocate($hasil, 255, 255, 255));
        imagecopyresampled($hasil, $gambar, 0, 0, 0, 0, $lebarBaru, $tinggiBaru, $lebar, $tinggi);
        imagedestroy($gambar);

        return $hasil;
    }
}
