<?php

namespace App\Libraries\Cadangan;

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Unggahan;
use CodeIgniter\Database\BaseConnection;
use Config\Services;
use FilesystemIterator;
use JsonException;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Cadangan lengkap web di Pengaturan > Cadangan (F11-11, K174 butir 10–12):
 * semua data, semua pengaturan, dan semua berkas unggahan dalam satu zip,
 * lalu impor zip itu yang **mengganti semua** data dan pengaturan.
 *
 * Isi zip (`VERSI` 2):
 * - semua isi Backup data lama (`PaketPendaftaran`, K76): `data.json`,
 *   `berkas/…`, `galeri/…`. Jadi `php spark pendaftaran:impor` tetap bisa
 *   memakai berkas ini;
 * - `tabel/<nama>.jsonl`: setiap tabel basis data, satu baris JSON per baris
 *   tabel, apa adanya (ID sama). Tabel baru dari tugas berikutnya (misalnya
 *   rekor kejuaraan) otomatis ikut. Tidak ikut: `TABEL_DILEWATI`;
 * - `berkas/<folder>/<nama>` dan `unggahan/<folder>/<nama>`: semua berkas di
 *   folder unggahan (`petaFolder()`), termasuk yang belum dirujuk data;
 * - `cadangan.json` (`MANIFES`): jenis, versi, migrasi terakhir, dan untuk
 *   tiap tabel daftar kolom, jumlah baris, serta nomor urut berikutnya.
 *
 * Ekspor menulis tabel ke berkas sementara per halaman (`HALAMAN` baris),
 * jadi tabel besar tidak dimuat utuh ke memori; pengendali mengirim zip
 * langsung dari berkas (K118 B4).
 *
 * Impor, berurutan; bila satu langkah gagal, tidak ada yang berubah:
 * 1. periksa manifes, versi, migrasi, tabel, kolom, dan nama setiap berkas
 *    (hanya folder unggahan yang dikenal dan nama tanpa folder lain, jadi tidak
 *    ada berkas yang ditulis di luar folder unggahan);
 * 2. buat cadangan data sekarang di `folderOtomatis()` (`Sebelum_Impor_…zip`);
 * 3. salin berkas dari zip ke folder sementara di samping folder unggahan;
 * 4. dalam satu transaksi: kosongkan semua tabel, isi dari zip, cocokkan
 *    jumlah baris dengan manifes;
 * 5. setelah transaksi berhasil: folder unggahan diganti folder sementara.
 *
 *     $hasil = (new CadanganLengkap())->ekspor('/tmp/cadangan.zip');
 *     // ['pendaftaran' => 12, 'atlet' => 85, 'tabel' => 27, 'baris' => 4200, 'berkas' => 40]
 *     $hasil = (new CadanganLengkap())->impor('/tmp/cadangan.zip');
 *     // [... , 'cadangan_otomatis' => '…/writable/cadangan/Sebelum_Impor_20261124_101500_a1b2c3.zip', 'peringatan' => []]
 */
final class CadanganLengkap
{
    public const JENIS = 'cadangan-milklife';

    public const VERSI = 2;

    public const MANIFES = 'cadangan.json';

    /**
     * Tabel yang tidak ikut cadangan dan tidak diubah impor: akun admin (kata
     * sandi tetap milik server ini, jadi admin yang mengimpor tetap masuk),
     * catatan migrasi (bentuk tabel milik versi web, bukan data), dan
     * pengaturan salinan Cloudflare R2 (token milik server ini, F11-21).
     */
    public const TABEL_DILEWATI = ['admin', 'migrations', 'salinan_r2'];

    /**
     * Banyaknya cadangan otomatis `Sebelum_Impor_*.zip` yang disimpan; yang lebih lama dihapus.
     */
    public const SIMPAN_OTOMATIS = 5;

    /**
     * Pola nama cadangan otomatis.
     */
    public const POLA_OTOMATIS = '/^Sebelum_Impor_\d{8}_\d{6}_[0-9a-f]{6}\.zip$/D';

    /**
     * Baris per halaman saat menulis dan membaca tabel.
     */
    private const HALAMAN = 500;

    private BaseConnection $db;

    private static ?string $folderOtomatis = null;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Folder cadangan otomatis sebelum impor: `writable/cadangan/` (di luar
     * folder publik dan tidak masuk git).
     */
    public static function folderOtomatis(): string
    {
        return self::$folderOtomatis ?? WRITEPATH . 'cadangan';
    }

    /**
     * Untuk uji: ganti folder cadangan otomatis; `null` kembali ke bawaan.
     */
    public static function gantiFolderOtomatis(?string $folder): void
    {
        self::$folderOtomatis = $folder === null ? null : rtrim($folder, '/\\');
    }

    /**
     * true selama folder cadangan otomatis diganti uji.
     */
    public static function folderDiganti(): bool
    {
        return self::$folderOtomatis !== null;
    }

    /**
     * Awalan jalur di zip => folder unggahan di server. Hanya folder ini yang
     * ikut cadangan dan boleh ditulis impor.
     *
     * @return array<string, string>
     */
    public static function petaFolder(): array
    {
        $peta = [
            'berkas/' . FotoPenanggungJawab::FOLDER => FotoPenanggungJawab::folderDasar() . FotoPenanggungJawab::FOLDER,
            'berkas/' . BerkasBuktiBayar::FOLDER    => BerkasBuktiBayar::folderDasar() . BerkasBuktiBayar::FOLDER,
        ];

        foreach (Unggahan::FOLDER as $folder) {
            $peta['unggahan/' . $folder] = Unggahan::folderDasar() . $folder;
        }

        return $peta;
    }

    /**
     * Nama berkas unggahan yang sah: tanpa folder, tanpa titik di depan, dan
     * berekstensi gambar atau dokumen yang diizinkan `Unggahan`.
     */
    public static function namaBerkasSah(string $nama): bool
    {
        $ekstensi = array_unique(array_merge(['jpeg'], array_keys(Unggahan::JENIS_GAMBAR), array_keys(Unggahan::JENIS_BERKAS)));

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9_-]{0,127}\.(' . implode('|', $ekstensi) . ')$/D', $nama) === 1;
    }

    /**
     * Tulis cadangan lengkap ke `$tujuan` (ditimpa bila ada).
     *
     * @return array{pendaftaran: int, atlet: int, tabel: int, baris: int, berkas: int}
     *
     * @throws RuntimeException Bila zip gagal ditulis; berkas setengah jadi dihapus.
     */
    public function ekspor(string $tujuan): array
    {
        $paket        = (new PaketPendaftaran($this->db))->ekspor($tujuan);
        $sementara    = [];
        $tabel        = [];
        $baris        = 0;
        $jumlahBerkas = 0;
        $zip          = new ZipArchive();

        if ($zip->open($tujuan) !== true) {
            @unlink($tujuan);

            throw new RuntimeException('Berkas cadangan gagal dibuka lagi di ' . $tujuan . '. Periksa sisa ruang diska, lalu coba lagi.');
        }

        try {
            foreach ($this->daftarTabel() as $nama) {
                $berkas      = $this->berkasSementara();
                $sementara[] = $berkas;
                $kolom       = $this->kolom($nama);
                $jumlah      = $this->tulisTabel($nama, $kolom, $berkas);
                $baris += $jumlah;

                $zip->addFile($berkas, 'tabel/' . $nama . '.jsonl');
                $tabel[$nama] = ['kolom' => $kolom, 'baris' => $jumlah, 'urut_berikutnya' => $this->urutBerikutnya($nama)];
            }

            foreach (self::petaFolder() as $dalamZip => $folder) {
                foreach ($this->berkasDi($folder) as $nama => $penuh) {
                    if ($zip->locateName($dalamZip . '/' . $nama) === false) {
                        $zip->addFile($penuh, $dalamZip . '/' . $nama);
                    }

                    $jumlahBerkas++;
                }
            }

            $zip->addFromString(self::MANIFES, (string) json_encode([
                'jenis'   => self::JENIS,
                'versi'   => self::VERSI,
                'dibuat'  => date('Y-m-d H:i:s'),
                'acara'   => trim(setting('Acara.nama') . ' Seri ' . setting('Acara.seri') . ' ' . setting('Acara.tahun')),
                'migrasi' => $this->migrasiTerakhir(),
                'tabel'   => $tabel,
                'berkas'  => $jumlahBerkas,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            // Isi berkas baru dibaca dan ditulis saat close(); diska penuh terlihat di sini.
            if (! $zip->close()) {
                throw new RuntimeException('Berkas cadangan gagal ditulis. Periksa sisa ruang diska, lalu coba lagi.');
            }
        } catch (Throwable $e) {
            @$zip->close();
            @unlink($tujuan);

            throw $e instanceof RuntimeException ? $e : new RuntimeException('Cadangan gagal dibuat: ' . $e->getMessage(), 0, $e);
        } finally {
            foreach ($sementara as $berkas) {
                @unlink($berkas);
            }
        }

        return ['pendaftaran' => $paket['pendaftaran'], 'atlet' => $paket['atlet'], 'tabel' => count($tabel), 'baris' => $baris, 'berkas' => $jumlahBerkas];
    }

    /**
     * Ganti semua data, pengaturan, dan berkas unggahan server ini dengan isi
     * cadangan. Sebelumnya (bila `$cadanganOtomatis`) data sekarang
     * dicadangkan ke `folderOtomatis()`.
     *
     * @return array{tabel: int, baris: int, berkas: int, cadangan_otomatis: string|null, peringatan: list<string>}
     *
     * @throws RuntimeException Dengan pesan ramah bila cadangan ditolak atau gagal; tidak ada yang berubah.
     */
    public function impor(string $sumber, bool $cadanganOtomatis = true): array
    {
        $zip = new ZipArchive();

        if (! is_file($sumber) || $zip->open($sumber, ZipArchive::RDONLY) !== true) {
            throw new RuntimeException('Berkas ini bukan zip, atau zipnya rusak. Unduh lagi cadangan dari Pengaturan > Cadangan, lalu impor berkas itu. Tidak ada yang berubah.');
        }

        $folderBaru   = [];
        $manifes      = ['tabel' => []];
        $otomatis     = null;
        $jumlahBerkas = 0;
        $baris        = 0;

        try {
            $manifes = $this->periksaManifes($zip);
            [$berkasMasuk, $ukuran] = $this->periksaBerkas($zip);
            $this->periksaRuang($ukuran);

            $otomatis = $cadanganOtomatis ? $this->buatCadanganOtomatis() : null;

            foreach (self::petaFolder() as $dalamZip => $folder) {
                $folderBaru[$folder] = $this->salinFolder($zip, $folder, $berkasMasuk[$dalamZip] ?? []);
                $jumlahBerkas += count($berkasMasuk[$dalamZip] ?? []);
            }

            $baris = $this->gantiData($zip, $manifes['tabel']);
        } catch (Throwable $e) {
            foreach ($folderBaru as $baru) {
                self::hapusFolder($baru);
            }

            throw $e instanceof RuntimeException ? $e : new RuntimeException('Cadangan gagal diimpor: ' . $e->getMessage() . ' Tidak ada yang berubah.', 0, $e);
        } finally {
            $zip->close();
        }

        $this->aturUrut($manifes['tabel']);
        $peringatan = $this->tukarFolder($folderBaru);

        // Tembolok halaman (halaman depan, videotron, …) dan pengaturan yang sudah terbaca.
        cache()->clean();
        Services::resetSingle('settings');

        return ['tabel' => count($manifes['tabel']), 'baris' => $baris, 'berkas' => $jumlahBerkas, 'cadangan_otomatis' => $otomatis, 'peringatan' => $peringatan];
    }

    /**
     * Cadangan otomatis yang tersimpan, terbaru di atas.
     *
     * @return list<array{nama: string, ukuran: int, waktu: int}>
     */
    public static function daftarOtomatis(): array
    {
        $daftar = [];

        foreach (glob(self::folderOtomatis() . DIRECTORY_SEPARATOR . 'Sebelum_Impor_*.zip') ?: [] as $berkas) {
            $nama = basename($berkas);

            if (preg_match(self::POLA_OTOMATIS, $nama) === 1 && is_file($berkas)) {
                $daftar[] = ['nama' => $nama, 'ukuran' => (int) filesize($berkas), 'waktu' => (int) filemtime($berkas)];
            }
        }

        usort($daftar, static fn (array $a, array $b): int => strcmp($b['nama'], $a['nama']));

        return $daftar;
    }

    /**
     * Jalur lengkap cadangan otomatis bernama `$nama`, atau `null` bila nama tidak sah atau berkasnya tidak ada.
     */
    public static function jalurOtomatis(string $nama): ?string
    {
        if (preg_match(self::POLA_OTOMATIS, $nama) !== 1) {
            return null;
        }

        $jalur = self::folderOtomatis() . DIRECTORY_SEPARATOR . $nama;

        return is_file($jalur) ? $jalur : null;
    }

    /**
     * Buat cadangan data sekarang di `folderOtomatis()`, lalu hapus yang
     * melebihi `SIMPAN_OTOMATIS`.
     *
     * @throws RuntimeException Bila gagal; impor dibatalkan.
     */
    public function buatCadanganOtomatis(): string
    {
        $folder = self::folderOtomatis();

        if (! is_dir($folder) && ! @mkdir($folder, 0700, true) && ! is_dir($folder)) {
            throw new RuntimeException('Folder ' . $folder . ' untuk cadangan otomatis tidak bisa dibuat. Minta pengelola server memeriksa izin tulis folder writable. Tidak ada yang berubah.');
        }

        $berkas = $folder . DIRECTORY_SEPARATOR . 'Sebelum_Impor_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.zip';

        try {
            $this->ekspor($berkas);
        } catch (RuntimeException $e) {
            throw new RuntimeException('Cadangan data sekarang gagal dibuat, jadi impor dibatalkan: ' . $e->getMessage() . ' Tidak ada yang berubah.', 0, $e);
        }

        foreach (array_slice(self::daftarOtomatis(), self::SIMPAN_OTOMATIS) as $lama) {
            @unlink($folder . DIRECTORY_SEPARATOR . $lama['nama']);
        }

        return $berkas;
    }

    /**
     * @return array{tabel: array<string, array{kolom: list<string>, baris: int, urut_berikutnya: int|null}>}
     *
     * @throws RuntimeException
     */
    private function periksaManifes(ZipArchive $zip): array
    {
        $tolak = ' Tidak ada yang berubah.';
        $isi   = $zip->getFromName(self::MANIFES);

        if ($isi === false) {
            if ($zip->locateName('data.json') !== false) {
                throw new RuntimeException('Berkas ini Backup data versi lama: hanya berisi pendaftaran, tanpa pengaturan. Berkas seperti ini hanya bisa dimasukkan lewat perintah server "php spark pendaftaran:impor", ke server yang belum punya pendaftaran.' . $tolak);
            }

            throw new RuntimeException('Berkas ini bukan cadangan web pendaftaran. Unduh cadangan dari Pengaturan > Cadangan, lalu impor berkas itu.' . $tolak);
        }

        $manifes = json_decode($isi, true);

        if (! is_array($manifes) || ($manifes['jenis'] ?? null) !== self::JENIS) {
            throw new RuntimeException('Berkas ini bukan cadangan web pendaftaran, atau keterangannya rusak. Unduh lagi cadangan dari Pengaturan > Cadangan.' . $tolak);
        }

        if (($manifes['versi'] ?? null) !== self::VERSI) {
            throw new RuntimeException('Versi cadangan ini (' . (is_scalar($manifes['versi'] ?? null) ? (string) $manifes['versi'] : 'tidak tertulis') . ') tidak dikenal web ini, yang memakai versi ' . self::VERSI . '. Perbarui web ini dulu, lalu impor lagi.' . $tolak);
        }

        $migrasi = is_string($manifes['migrasi'] ?? null) ? $manifes['migrasi'] : '';

        if (strcmp($migrasi, $this->migrasiTerakhir()) > 0) {
            throw new RuntimeException('Cadangan ini dibuat oleh web versi lebih baru dari web ini. Perbarui web ini dulu (lihat panduan pemasangan, bagian pembaruan), lalu impor lagi.' . $tolak);
        }

        if (! is_array($manifes['tabel'] ?? null) || $manifes['tabel'] === []) {
            throw new RuntimeException('Cadangan ini tidak memuat tabel data. Berkasnya mungkin rusak; unduh lagi cadangan dari Pengaturan > Cadangan.' . $tolak);
        }

        $server = array_flip($this->daftarTabel());
        $tabel  = [];

        foreach ($manifes['tabel'] as $nama => $info) {
            $nama = (string) $nama;

            if (preg_match('/^[a-z0-9_]{1,64}$/D', $nama) !== 1 || ! isset($server[$nama])) {
                throw new RuntimeException('Cadangan memuat tabel "' . $nama . '" yang tidak ada di web ini. Perbarui web ini dulu, lalu impor lagi.' . $tolak);
            }

            $kolom = is_array($info) && is_array($info['kolom'] ?? null) ? array_values(array_filter($info['kolom'], 'is_string')) : [];

            if ($kolom === [] || count($kolom) !== count($info['kolom']) || ! is_int($info['baris'] ?? null) || $info['baris'] < 0) {
                throw new RuntimeException('Keterangan tabel "' . $nama . '" di cadangan rusak. Unduh lagi cadangan dari Pengaturan > Cadangan.' . $tolak);
            }

            $asing = array_diff($kolom, $this->kolom($nama));

            if ($asing !== []) {
                throw new RuntimeException('Tabel "' . $nama . '" di cadangan punya kolom yang tidak ada di web ini (' . implode(', ', $asing) . '). Perbarui web ini dulu, lalu impor lagi.' . $tolak);
            }

            if ($zip->locateName('tabel/' . $nama . '.jsonl') === false) {
                throw new RuntimeException('Isi tabel "' . $nama . '" tidak ada di cadangan. Berkasnya mungkin terpotong; unduh lagi cadangan dari Pengaturan > Cadangan.' . $tolak);
            }

            $urut          = $info['urut_berikutnya'] ?? null;
            $tabel[$nama] = ['kolom' => $kolom, 'baris' => $info['baris'], 'urut_berikutnya' => is_int($urut) && $urut > 0 ? $urut : null];
        }

        return ['tabel' => $tabel];
    }

    /**
     * Daftar berkas unggahan di zip per awalan folder, dan jumlah ukurannya.
     * Setiap berkas di bawah `berkas/` dan `unggahan/` harus berada langsung
     * di folder yang dikenal (`petaFolder()`) dengan nama yang sah.
     *
     * @return array{0: array<string, array<string, string>>, 1: int} awalan => [nama di zip => nama berkas], jumlah bita
     *
     * @throws RuntimeException
     */
    private function periksaBerkas(ZipArchive $zip): array
    {
        $peta   = self::petaFolder();
        $daftar = [];
        $ukuran = 0;

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $nama = is_array($stat) ? (string) $stat['name'] : '';

            if ((! str_starts_with($nama, 'berkas/') && ! str_starts_with($nama, 'unggahan/')) || str_ends_with($nama, '/')) {
                continue;
            }

            $folder = substr($nama, 0, (int) strrpos($nama, '/'));
            $berkas = substr($nama, strlen($folder) + 1);

            if (! isset($peta[$folder]) || ! self::namaBerkasSah($berkas)) {
                throw new RuntimeException('Cadangan memuat berkas yang tidak dikenal: ' . $nama . '. Berkas cadangan mungkin sudah diubah di luar web ini. Tidak ada yang berubah.');
            }

            $daftar[$folder][$nama] = $berkas;
            $ukuran += (int) $stat['size'];
        }

        return [$daftar, $ukuran];
    }

    /**
     * Tolak bila sisa diska jelas tidak cukup untuk berkas unggahan dan cadangan otomatis.
     */
    private function periksaRuang(int $ukuran): void
    {
        $sisa = @disk_free_space(WRITEPATH);

        if (is_float($sisa) && $sisa < $ukuran * 2 + 50 * 1024 * 1024) {
            throw new RuntimeException('Sisa ruang diska server (' . Unggahan::ukuranTeks((int) $sisa) . ') tidak cukup untuk berkas di cadangan ini (' . Unggahan::ukuranTeks($ukuran) . ') beserta cadangan data sekarang. Kosongkan ruang diska dulu. Tidak ada yang berubah.');
        }
    }

    /**
     * Salin berkas dari zip ke folder baru di samping `$folder`.
     *
     * @param array<string, string> $berkas nama di zip => nama berkas
     *
     * @return string Jalur folder baru.
     *
     * @throws RuntimeException
     */
    private function salinFolder(ZipArchive $zip, string $folder, array $berkas): string
    {
        $induk = dirname($folder);
        $baru  = $folder . '.pulih-' . bin2hex(random_bytes(6));

        if ((! is_dir($induk) && ! @mkdir($induk, 0755, true) && ! is_dir($induk)) || ! @mkdir($baru, 0755)) {
            throw new RuntimeException('Folder ' . $induk . ' tidak boleh ditulisi, jadi berkas unggahan tidak bisa dipulihkan. Minta pengelola server memeriksa izin folder itu. Tidak ada yang berubah.');
        }

        foreach ($berkas as $dalamZip => $nama) {
            $masuk  = $zip->getStream($dalamZip);
            $tujuan = $baru . DIRECTORY_SEPARATOR . $nama;
            $keluar = @fopen($tujuan, 'xb');
            $stat   = $zip->statName($dalamZip);

            if ($masuk === false || $keluar === false) {
                if (is_resource($masuk)) {
                    fclose($masuk);
                }

                if (is_resource($keluar)) {
                    fclose($keluar);
                }

                self::hapusFolder($baru);

                throw new RuntimeException('Berkas ' . $dalamZip . ' gagal disalin dari cadangan. Tidak ada yang berubah.');
            }

            // Paling banyak satu bita lebih dari ukuran yang tertulis di zip: isi yang lebih panjang ditolak tanpa memenuhi diska.
            $tersalin = is_array($stat) ? @stream_copy_to_stream($masuk, $keluar, (int) $stat['size'] + 1) : false;
            fclose($masuk);
            fclose($keluar);

            if ($tersalin === false || ! is_array($stat) || $tersalin !== (int) $stat['size']) {
                self::hapusFolder($baru);

                throw new RuntimeException('Berkas ' . $dalamZip . ' di cadangan rusak atau terpotong. Unduh lagi cadangan dari Pengaturan > Cadangan. Tidak ada yang berubah.');
            }

            @chmod($tujuan, 0644);
        }

        return $baru;
    }

    /**
     * Kosongkan semua tabel (kecuali `TABEL_DILEWATI`) dan isi dari zip, dalam satu transaksi.
     *
     * @param array<string, array{kolom: list<string>, baris: int, urut_berikutnya: int|null}> $tabel
     *
     * @return int Jumlah baris yang dimasukkan.
     *
     * @throws RuntimeException Transaksi dibatalkan; tidak ada yang berubah.
     */
    private function gantiData(ZipArchive $zip, array $tabel): int
    {
        $server = $this->daftarTabel();
        $jumlah = 0;

        // Urutan isi bebas: kunci asing diperiksa lagi setelah semua tabel terisi lewat jumlah baris.
        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->transBegin();

        try {
            foreach ($server as $nama) {
                $this->db->query('DELETE FROM `' . $nama . '`');
            }

            foreach ($tabel as $nama => $info) {
                $masuk = $this->isiTabel($zip, $nama, $info['kolom']);

                if ($masuk !== $info['baris']) {
                    throw new RuntimeException('Isi tabel "' . $nama . '" di cadangan tidak lengkap (' . $masuk . ' dari ' . $info['baris'] . ' baris). Berkasnya mungkin rusak; unduh lagi cadangan dari Pengaturan > Cadangan. Tidak ada yang berubah.');
                }

                $jumlah += $masuk;
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            if ($e instanceof RuntimeException && str_ends_with($e->getMessage(), 'Tidak ada yang berubah.')) {
                throw $e;
            }

            throw new RuntimeException('Data cadangan gagal dimasukkan: ' . $e->getMessage() . ' Tidak ada yang berubah.', 0, $e);
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        return $jumlah;
    }

    /**
     * @param list<string> $kolom
     *
     * @return int Jumlah baris yang dimasukkan.
     *
     * @throws RuntimeException
     */
    private function isiTabel(ZipArchive $zip, string $nama, array $kolom): int
    {
        $aliran = $zip->getStream('tabel/' . $nama . '.jsonl');

        if ($aliran === false) {
            throw new RuntimeException('Isi tabel "' . $nama . '" tidak bisa dibaca dari cadangan. Tidak ada yang berubah.');
        }

        $kunci  = array_flip($kolom);
        $batch  = [];
        $jumlah = 0;

        try {
            while (($baris = fgets($aliran)) !== false) {
                $baris = rtrim($baris, "\r\n");

                if ($baris === '') {
                    continue;
                }

                try {
                    $data = json_decode($baris, true, 4, JSON_THROW_ON_ERROR);
                } catch (JsonException) {
                    $data = null;
                }

                if (! is_array($data) || count($data) !== count($kunci) || array_diff_key($data, $kunci) !== []
                    || array_filter($data, static fn (mixed $nilai): bool => $nilai !== null && ! is_scalar($nilai)) !== []) {
                    throw new RuntimeException('Baris ke-' . ($jumlah + 1) . ' tabel "' . $nama . '" di cadangan rusak. Tidak ada yang berubah.');
                }

                $batch[] = array_merge($kunci, $data);
                $jumlah++;

                if (count($batch) === self::HALAMAN) {
                    $this->masukkan($nama, $batch);
                    $batch = [];
                }
            }
        } finally {
            fclose($aliran);
        }

        if ($batch !== []) {
            $this->masukkan($nama, $batch);
        }

        return $jumlah;
    }

    /**
     * @param list<array<string, mixed>> $baris
     */
    private function masukkan(string $nama, array $baris): void
    {
        if ($this->db->table($nama)->insertBatch($baris) === false) {
            $galat = $this->db->error();

            throw new RuntimeException('Tabel "' . $nama . '" gagal diisi: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.');
        }
    }

    /**
     * Nomor urut berikutnya sama dengan server asal, supaya ID yang pernah
     * dipakai (pendaftaran yang dihapus) tidak dipakai ulang. Di luar
     * transaksi: ALTER TABLE menutup transaksi yang sedang berjalan.
     *
     * @param array<string, array{kolom: list<string>, baris: int, urut_berikutnya: int|null}> $tabel
     */
    private function aturUrut(array $tabel): void
    {
        foreach ($tabel as $nama => $info) {
            if ($info['urut_berikutnya'] !== null) {
                $this->db->query('ALTER TABLE `' . $nama . '` AUTO_INCREMENT = ' . $info['urut_berikutnya']);
            }
        }
    }

    /**
     * Ganti setiap folder unggahan dengan folder barunya.
     *
     * @param array<string, string> $folderBaru folder => folder baru
     *
     * @return list<string> Peringatan untuk folder yang gagal diganti.
     */
    private function tukarFolder(array $folderBaru): array
    {
        $peringatan = [];

        foreach ($folderBaru as $folder => $baru) {
            $lama = null;

            if (is_dir($folder)) {
                $lama = $folder . '.lama-' . bin2hex(random_bytes(6));

                if (! @rename($folder, $lama)) {
                    self::hapusFolder($baru);
                    $peringatan[] = 'Berkas di folder ' . $folder . ' belum diganti karena folder itu sedang dipakai. Data dan pengaturan sudah diganti; impor sekali lagi untuk memulihkan berkasnya.';

                    continue;
                }
            }

            if (! @rename($baru, $folder)) {
                if ($lama !== null) {
                    @rename($lama, $folder);
                }

                self::hapusFolder($baru);
                $peringatan[] = 'Berkas di folder ' . $folder . ' belum diganti karena folder itu tidak boleh ditulisi. Data dan pengaturan sudah diganti; perbaiki izin folder, lalu impor sekali lagi.';

                continue;
            }

            if ($lama !== null) {
                self::hapusFolder($lama);
            }
        }

        return $peringatan;
    }

    /**
     * Tabel basis data yang ikut cadangan, urut nama.
     *
     * @return list<string>
     */
    private function daftarTabel(): array
    {
        $nama = [];

        foreach ($this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->getResultArray() as $baris) {
            $tabel = (string) reset($baris);

            if (preg_match('/^[a-z0-9_]{1,64}$/D', $tabel) === 1 && ! in_array($tabel, self::TABEL_DILEWATI, true)) {
                $nama[] = $tabel;
            }
        }

        sort($nama);

        return $nama;
    }

    /**
     * @return list<string>
     */
    private function kolom(string $tabel): array
    {
        return array_map(static fn (array $b): string => (string) $b['Field'], $this->db->query('SHOW COLUMNS FROM `' . $tabel . '`')->getResultArray());
    }

    /**
     * Tulis semua baris tabel ke `$berkas` (satu baris JSON per baris), per halaman.
     *
     * @param list<string> $kolom
     *
     * @return int Jumlah baris.
     */
    private function tulisTabel(string $tabel, array $kolom, string $berkas): int
    {
        $keluar = fopen($berkas, 'wb');

        if ($keluar === false) {
            throw new RuntimeException('Berkas sementara untuk cadangan gagal ditulis. Periksa sisa ruang diska.');
        }

        $utama  = array_map(static fn (array $b): string => (string) $b['Column_name'], $this->db->query("SHOW KEYS FROM `{$tabel}` WHERE Key_name = 'PRIMARY'")->getResultArray());
        $jumlah = 0;
        $akhir  = null;
        $lewat  = 0;

        try {
            do {
                $kueri = $this->db->table($tabel)->select(implode(', ', array_map(static fn (string $k): string => '`' . $k . '`', $kolom)), false)->limit(self::HALAMAN);

                if (count($utama) === 1) {
                    // Halaman menurut kunci utama: cepat dan tetap benar untuk tabel besar.
                    $kueri->orderBy($utama[0]);

                    if ($akhir !== null) {
                        $kueri->where($utama[0] . ' >', $akhir);
                    }
                } else {
                    foreach ($utama === [] ? $kolom : $utama as $k) {
                        $kueri->orderBy($k);
                    }

                    $kueri->offset($lewat);
                }

                $baris = $kueri->get()->getResultArray();

                foreach ($baris as $b) {
                    fwrite($keluar, json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
                }

                $jumlah += count($baris);
                $lewat += count($baris);
                $akhir = $baris === [] || count($utama) !== 1 ? $akhir : $baris[count($baris) - 1][$utama[0]];
            } while (count($baris) === self::HALAMAN);
        } finally {
            fclose($keluar);
        }

        return $jumlah;
    }

    private function urutBerikutnya(string $tabel): ?int
    {
        try {
            // MySQL 8 menyimpan angka AUTO_INCREMENT di tembolok statistik; minta yang terbaru.
            $this->db->query('SET SESSION information_schema_stats_expiry = 0');
        } catch (Throwable) {
            // MariaDB tidak punya pengaturan ini dan angkanya selalu terbaru.
        }

        $status = $this->db->query('SHOW TABLE STATUS LIKE ' . $this->db->escape($tabel))->getRowArray();
        $urut   = $status['Auto_increment'] ?? null;

        return is_numeric($urut) && (int) $urut > 0 ? (int) $urut : null;
    }

    private function migrasiTerakhir(): string
    {
        $baris = $this->db->query('SELECT MAX(version) AS versi FROM migrations')->getRowArray();

        return (string) ($baris['versi'] ?? '');
    }

    /**
     * Berkas langsung di `$folder` yang bernama sah.
     *
     * @return array<string, string> nama => jalur lengkap
     */
    private function berkasDi(string $folder): array
    {
        $daftar = [];

        if (! is_dir($folder)) {
            return $daftar;
        }

        foreach (new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS) as $isi) {
            if ($isi instanceof \SplFileInfo && $isi->isFile() && ! $isi->isLink() && self::namaBerkasSah($isi->getFilename())) {
                $daftar[$isi->getFilename()] = $isi->getPathname();
            }
        }

        ksort($daftar);

        return $daftar;
    }

    private function berkasSementara(): string
    {
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-tabel-');

        if ($berkas === false) {
            throw new RuntimeException('Berkas sementara gagal dibuat di ' . sys_get_temp_dir() . '. Periksa sisa ruang diska.');
        }

        return $berkas;
    }

    private static function hapusFolder(string $folder): void
    {
        if (! is_dir($folder) || is_link($folder)) {
            return;
        }

        foreach (new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS) as $isi) {
            if ($isi instanceof \SplFileInfo) {
                if ($isi->isDir() && ! $isi->isLink()) {
                    self::hapusFolder($isi->getPathname());
                } else {
                    @unlink($isi->getPathname());
                }
            }
        }

        @rmdir($folder);
    }
}
