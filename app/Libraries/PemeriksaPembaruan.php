<?php

namespace App\Libraries;

use App\Commands\MinifyAset;
use App\Commands\SesuaikanData;
use App\Libraries\Hasil\DataTabelSkor;
use App\Models\SekolahModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Database\MigrationRunner;
use JsonException;
use Throwable;

/**
 * Pemeriksaan setelah `git pull` (K115): perintah lanjutan apa yang perlu
 * dijalankan supaya server sama dengan versi baru. Dipakai
 * `php spark pembaruan:periksa`.
 *
 *     $hasil = (new PemeriksaPembaruan())->periksa();
 *     // [['kunci' => 'composer', 'judul' => 'Pustaka PHP (Composer)', 'perlu' => false, 'perintah' => null, 'keterangan' => 'Sudah sesuai composer.lock.'], ...]
 *
 * Urutan hasil sama dengan urutan menjalankan perintahnya:
 *
 * 1. `composer`: paket di `composer.lock` yang belum terpasang atau versinya
 *    beda di `vendor/` → `composer install --no-dev --optimize-autoloader`.
 * 2. `migrasi`: berkas migrasi (semua namespace, seperti `migrate --all`)
 *    yang belum tercatat di tabel `migrations` → `php spark migrate --all`.
 * 3. `sekolah`: sekolah di `data/sekolah/sekolah.json` yang belum ada atau
 *    datanya beda di tabel `sekolah`, tidak termasuk sekolah yang disunting
 *    admin (tidak akan ditimpa) → `php spark sekolah:impor`.
 * 4. `gabung`: NPSN dengan lebih dari satu pendaftaran Lunas (K109) →
 *    `php spark pendaftaran:gabung`.
 * 5. `tabel_skor`: tabel skor bawaan di `data/tabel-skor/` yang belum
 *    terpasang, atau nomor Trilomba yang masih berjenis hasil poin dan belum
 *    punya prestasi (F9-10, K143) → `php spark tabel-skor:impor --trilomba`.
 * 6. `faq`: tabel tanya-jawab Bantuan masih kosong (F9-04) →
 *    `php spark faq:awal`.
 * 7. `carousel`: foto carousel halaman depan belum pernah diatur (F9-03) atau
 *    masih berisi foto bawaan lama (K191) →
 *    `php spark halaman-depan:carousel`.
 * 8. `galeri`: album galeri bawaan belum pernah dipasang dan galeri kosong
 *    (F9-06, K140) → `php spark galeri:awal`.
 *    Ketiganya terjadi pada server yang basis datanya dipulihkan dari versi
 *    sebelum Tahap 9, misalnya saat pindah dari Laragon (F11-19).
 * 9. `data_bawaan`: data bawaan `data:awal` lama yang belum disamakan (nama
 *    `KU 8 (Seneng Playon)`, nomor WA contoh, media sosial lama; F10-01) →
 *    `php spark data:sesuaikan`.
 * 10. `aset`: berkas CSS/JS/SVG yang versi `.min`-nya belum ada atau lebih tua
 *    dari aslinya → `php spark aset:minify`. Dilewati bila `aplikasi.minify`
 *    mati dan belum ada berkas `.min` sama sekali.
 *
 * Pemeriksaan hanya membaca; tidak ada data yang diubah. Pemeriksaan yang
 * gagal (misalnya MySQL mati) tidak dianggap beres: `perlu` bernilai true,
 * `perintah` null, dan `keterangan` menyebut cara memperbaikinya.
 */
final class PemeriksaPembaruan
{
    /**
     * Kolom yang ditulis `sekolah:impor` dan dibandingkan.
     */
    public const KOLOM_SEKOLAH = ['nama', 'bentuk', 'jenjang', 'status', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten'];

    public const GALAT_BASIS_DATA = 'Belum bisa diperiksa karena basis data tidak bisa dihubungi. Pastikan container basis data menyala (docker compose ps) dan pengaturan basis data di .env benar, lalu jalankan pemeriksaan ini lagi.';

    private BaseConnection $db;

    public function __construct(
        ?BaseConnection $db = null,
        private readonly string $akar = ROOTPATH,
        private readonly string $folderAset = FCPATH . 'aset',
        private readonly ?bool $minify = null,
    ) {
        $this->db = $db ?? db_connect();
    }

    /**
     * `perintah` null dengan `perlu` true berarti pemeriksaannya gagal.
     *
     * @return list<array{kunci: string, judul: string, perlu: bool, perintah: string|null, keterangan: string}>
     */
    public function periksa(): array
    {
        return [
            $this->jalankan('composer', 'Pustaka PHP (Composer)', 'composer install --no-dev --optimize-autoloader', fn (): array => $this->periksaComposer()),
            $this->jalankan('migrasi', 'Struktur basis data (migrasi)', 'php spark migrate --all', fn (): array => $this->periksaMigrasi()),
            $this->jalankan('sekolah', 'Data sekolah', 'php spark sekolah:impor', fn (): array => $this->periksaSekolah()),
            $this->jalankan('gabung', 'Pendaftaran Lunas ber-NPSN sama', 'php spark pendaftaran:gabung', fn (): array => $this->periksaGabung()),
            $this->jalankan('tabel_skor', 'Tabel skor multi-lomba (Trilomba)', 'php spark tabel-skor:impor --trilomba', fn (): array => $this->periksaTabelSkor()),
            $this->jalankan('faq', 'Tanya-jawab Bantuan', 'php spark faq:awal', fn (): array => $this->periksaFaq()),
            $this->jalankan('carousel', 'Foto carousel halaman depan', 'php spark halaman-depan:carousel', fn (): array => $this->periksaCarousel()),
            $this->jalankan('galeri', 'Album galeri bawaan', 'php spark galeri:awal', fn (): array => $this->periksaGaleri()),
            $this->jalankan('data_bawaan', 'Data bawaan terbaru (KU 8, WA, media sosial)', 'php spark data:sesuaikan', fn (): array => $this->periksaDataBawaan()),
            $this->jalankan('aset', 'CSS dan JavaScript kecil', 'php spark aset:minify', fn (): array => $this->periksaAset()),
        ];
    }

    /**
     * Paket `composer.lock` (bagian `packages`, tanpa paket pengembang) yang
     * belum terpasang atau versinya berbeda.
     *
     * @param array<string, mixed> $kunci    Isi `composer.lock`
     * @param array<string, mixed> $terpasang Isi `vendor/composer/installed.json`
     *
     * @return list<string> "nama (terpasang → seharusnya)", berurutan
     */
    public static function paketTertinggal(array $kunci, array $terpasang): array
    {
        $versi = [];

        foreach ($terpasang['packages'] ?? $terpasang as $paket) {
            if (is_array($paket) && isset($paket['name'])) {
                $versi[(string) $paket['name']] = (string) ($paket['version'] ?? '');
            }
        }

        $tertinggal = [];

        foreach ($kunci['packages'] ?? [] as $paket) {
            $nama  = (string) ($paket['name'] ?? '');
            $harus = (string) ($paket['version'] ?? '');

            if ($nama === '' || ($versi[$nama] ?? null) === $harus) {
                continue;
            }

            $tertinggal[] = $nama . ' (' . ($versi[$nama] ?? 'belum ada') . ' → ' . $harus . ')';
        }

        sort($tertinggal);

        return $tertinggal;
    }

    /**
     * Berkas migrasi yang belum tercatat di tabel `migrations`. Kuncinya sama
     * dengan `MigrationRunner::getObjectUid()`: angka versi + nama kelas.
     *
     * @param list<array<string, mixed>> $berkas  Hasil `findMigrations()` (version, class, name)
     * @param list<array<string, mixed>> $riwayat Baris tabel `migrations` (version, class)
     *
     * @return list<string> Nama migrasi yang belum dijalankan, berurutan
     */
    public static function migrasiTertunda(array $berkas, array $riwayat): array
    {
        $uid   = static fn (array $m): string => preg_replace('/[^0-9]/', '', (string) $m['version']) . ltrim((string) $m['class'], '\\');
        $sudah = array_flip(array_map($uid, $riwayat));
        $belum = [];

        usort($berkas, static fn (array $a, array $b): int => strcmp($uid($a), $uid($b)));

        foreach ($berkas as $m) {
            if (! isset($sudah[$uid($m)])) {
                $belum[] = (string) $m['name'];
            }
        }

        return $belum;
    }

    /**
     * Bandingkan isi berkas data sekolah dengan yang tersimpan.
     *
     * @param iterable<mixed>                     $daftar    Isi `sekolah.json`
     * @param array<string, array<string, mixed>> $tersimpan npsn => baris tabel `sekolah` (dengan `disunting_pada`)
     *
     * @return array{baru: int, berubah: int}
     */
    public static function bedaSekolah(iterable $daftar, array $tersimpan): array
    {
        $hasil    = ['baru' => 0, 'berubah' => 0];
        $terlihat = [];

        foreach ($daftar as $baris) {
            [$data] = SekolahModel::periksaBaris($baris);

            if ($data === null || isset($terlihat[$data['npsn']])) {
                continue;
            }

            $terlihat[$data['npsn']] = true;
            $lama                    = $tersimpan[$data['npsn']] ?? null;

            if ($lama === null) {
                $hasil['baru']++;

                continue;
            }

            if (($lama['disunting_pada'] ?? null) !== null) {
                continue;
            }

            foreach (self::KOLOM_SEKOLAH as $kolom) {
                if ((string) ($data[$kolom] ?? '') !== (string) ($lama[$kolom] ?? '')) {
                    $hasil['berubah']++;

                    break;
                }
            }
        }

        return $hasil;
    }

    /**
     * Berkas aset asli yang `.min`-nya belum ada atau lebih tua.
     *
     * @return array{usang: list<string>, ada_min: bool}
     */
    public static function asetUsang(string $folder): array
    {
        $usang  = [];
        $adaMin = false;

        foreach (MinifyAset::daftarBerkas($folder) as $relatif) {
            $asli     = $folder . DIRECTORY_SEPARATOR . $relatif;
            $ekstensi = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
            $min      = substr($asli, 0, -strlen($ekstensi)) . 'min.' . $ekstensi;

            if (is_file($min)) {
                $adaMin = true;

                if (filemtime($min) >= filemtime($asli)) {
                    continue;
                }
            }

            $usang[] = str_replace(DIRECTORY_SEPARATOR, '/', $relatif);
        }

        return ['usang' => $usang, 'ada_min' => $adaMin];
    }

    /**
     * @param callable(): array{0: bool, 1: string} $periksa
     *
     * @return array{kunci: string, judul: string, perlu: bool, perintah: string|null, keterangan: string}
     */
    private function jalankan(string $kunci, string $judul, string $perintah, callable $periksa): array
    {
        try {
            [$perlu, $keterangan] = $periksa();
        } catch (DatabaseException) {
            return ['kunci' => $kunci, 'judul' => $judul, 'perlu' => true, 'perintah' => null, 'keterangan' => self::GALAT_BASIS_DATA];
        } catch (Throwable $e) {
            return [
                'kunci'      => $kunci,
                'judul'      => $judul,
                'perlu'      => true,
                'perintah'   => null,
                'keterangan' => 'Belum bisa diperiksa: ' . trim((string) preg_replace('/\s+/', ' ', $e->getMessage())),
            ];
        }

        return ['kunci' => $kunci, 'judul' => $judul, 'perlu' => $perlu, 'perintah' => $perlu ? $perintah : null, 'keterangan' => $keterangan];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaComposer(): array
    {
        $kunci      = $this->bacaJson($this->akar . 'composer.lock');
        $terpasang  = $this->bacaJson($this->akar . 'vendor/composer/installed.json');
        $tertinggal = self::paketTertinggal($kunci, $terpasang);

        if ($tertinggal === []) {
            return [false, 'Sudah sesuai composer.lock.'];
        }

        return [true, count($tertinggal) . ' paket belum sesuai: ' . self::ringkas($tertinggal) . '.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaMigrasi(): array
    {
        /** @var MigrationRunner $runner */
        $runner = service('migrations', null, $this->db, false);
        $runner->setNamespace(null);

        $berkas  = $runner->findMigrations();
        $riwayat = $this->db->tableExists('migrations')
            ? $this->db->table('migrations')->select('version, class')->get()->getResultArray()
            : [];
        $belum = self::migrasiTertunda(array_map(static fn (object $m): array => (array) $m, array_values($berkas)), $riwayat);

        if ($belum === []) {
            return [false, 'Semua ' . count($berkas) . ' migrasi sudah dijalankan.'];
        }

        return [true, count($belum) . ' migrasi baru: ' . self::ringkas($belum) . '.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaSekolah(): array
    {
        $berkas = $this->akar . 'data/sekolah/sekolah.json';

        if (! is_file($berkas)) {
            return [false, 'Berkas data/sekolah/sekolah.json tidak ada, jadi tidak ada yang perlu diimpor.'];
        }

        if (! $this->db->tableExists('sekolah')) {
            return [true, 'Tabel sekolah belum ada. Jalankan setelah migrasi.'];
        }

        $daftar    = $this->bacaJson($berkas);
        $tersimpan = [];

        foreach ($this->db->table('sekolah')->select('npsn, disunting_pada, ' . implode(', ', self::KOLOM_SEKOLAH))->get()->getResultArray() as $baris) {
            $tersimpan[$baris['npsn']] = $baris;
        }

        $beda = self::bedaSekolah($daftar, $tersimpan);

        if ($beda['baru'] === 0 && $beda['berubah'] === 0) {
            return [false, 'Sudah sesuai data/sekolah/sekolah.json (' . number_format(count($tersimpan), 0, ',', '.') . ' sekolah).'];
        }

        $bagian = [];

        if ($beda['baru'] > 0) {
            $bagian[] = number_format($beda['baru'], 0, ',', '.') . ' sekolah baru';
        }

        if ($beda['berubah'] > 0) {
            $bagian[] = number_format($beda['berubah'], 0, ',', '.') . ' sekolah dengan data berubah';
        }

        return [true, implode(' dan ', $bagian) . '. Sekolah yang disunting admin tidak ditimpa.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaGabung(): array
    {
        if (! $this->db->tableExists('pendaftaran')) {
            return [false, 'Tabel pendaftaran belum ada, jadi belum ada yang perlu digabung.'];
        }

        $jumlah = (int) ($this->db->query(
            "SELECT COUNT(*) AS jumlah FROM (SELECT npsn FROM {$this->db->prefixTable('pendaftaran')} WHERE status = 'lunas' AND npsn IS NOT NULL AND npsn <> '' GROUP BY npsn HAVING COUNT(*) > 1) ganda",
        )->getRowArray()['jumlah'] ?? 0);

        if ($jumlah === 0) {
            return [false, 'Setiap sekolah paling banyak punya satu pendaftaran Lunas.'];
        }

        return [true, $jumlah . ' sekolah punya lebih dari satu pendaftaran Lunas. Perintahnya meminta konfirmasi GABUNG.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaTabelSkor(): array
    {
        $bawaan = DataTabelSkor::berkasBawaan($this->akar . 'data/tabel-skor');

        if ($bawaan === []) {
            return [false, 'Tidak ada tabel skor bawaan di data/tabel-skor, jadi tidak ada yang perlu dipasang.'];
        }

        if (! $this->db->tableExists('tabel_skor')) {
            return [true, 'Tabel skor belum ada. Jalankan setelah migrasi.'];
        }

        $ada   = array_column($this->db->table('tabel_skor')->select('kode')->get()->getResultArray(), 'kode');
        $belum = array_values(array_filter(array_map(static fn (array $t): ?string => in_array($t['kode'], $ada, true) ? null : (string) $t['nama'], $bawaan)));
        $poin  = count((new DataTabelSkor($this->db))->trilombaMasihPoin());

        if ($belum === [] && $poin === 0) {
            return [false, 'Semua tabel skor bawaan terpasang dan nomor Trilomba memakai poin multi-lomba.'];
        }

        $bagian = [];

        if ($belum !== []) {
            $bagian[] = 'tabel bawaan belum terpasang: ' . implode(', ', $belum);
        }

        if ($poin > 0) {
            $bagian[] = $poin . ' nomor Trilomba masih berjenis hasil poin dan belum punya prestasi';
        }

        return [true, ucfirst(implode('; ', $bagian)) . '. Nomor yang sudah punya prestasi tidak diubah.'];
    }

    /**
     * Tanya-jawab Bantuan (F9-04): `faq:awal` hanya mengisi tabel yang kosong.
     *
     * @return array{0: bool, 1: string}
     */
    private function periksaFaq(): array
    {
        $jumlah = $this->db->table('faq')->countAllResults();

        if ($jumlah > 0) {
            return [false, number_format($jumlah, 0, ',', '.') . ' tanya-jawab sudah ada.'];
        }

        return [true, 'Halaman Bantuan belum punya tanya-jawab. Perintahnya memasang tanya-jawab bawaan yang bisa disunting di Pengaturan > Bantuan.'];
    }

    /**
     * Carousel halaman depan (F9-03): `halaman-depan:carousel` memasang
     * bila pengaturannya belum pernah diisi, atau mengganti salinan foto
     * bawaan lama yang masih ada (K191). Daftar kosong karena admin
     * menghapus semua foto dianggap sudah diatur.
     *
     * @return array{0: bool, 1: string}
     */
    private function periksaCarousel(): array
    {
        if (setting(CarouselHalamanDepan::KUNCI) !== null) {
            // Foto bawaan lama diganti foto bawaan baru (K191); foto unggahan admin tetap.
            $lama = CarouselHalamanDepan::jumlahBawaanLama();

            if ($lama > 0) {
                return [true, $lama . ' foto bawaan lama masih tampil. Perintahnya menggantinya dengan foto bawaan baru tanpa mengubah foto unggahan admin.'];
            }

            return [false, 'Sudah diatur.'];
        }

        return [true, 'Foto carousel belum pernah dipasang. Perintahnya memasang foto bawaan yang bisa diganti di Pengaturan > Halaman depan.'];
    }

    /**
     * Album galeri bawaan (F9-06, K140): `galeri:awal` hanya memasang bila
     * belum pernah dipasang dan galeri masih kosong.
     *
     * @return array{0: bool, 1: string}
     */
    private function periksaGaleri(): array
    {
        if ((bool) setting('Galeri.bawaan_dipasang') || $this->db->table('galeri')->countAllResults() > 0) {
            return [false, 'Sudah dipasang atau sudah diisi admin.'];
        }

        return [true, 'Galeri belum punya album. Perintahnya memasang album "Seri sebelumnya"; halaman Galeri tetap tersembunyi sampai dinyalakan di Pengaturan > Galeri.'];
    }

    /**
     * Data bawaan `data:awal` lama yang belum disamakan (F10-01).
     *
     * @return array{0: bool, 1: string}
     */
    private function periksaDataBawaan(): array
    {
        $diubah = SesuaikanData::sesuaikan($this->db, false)['diubah'];

        if ($diubah === []) {
            return [false, 'Tidak ada data bawaan lama yang perlu disamakan.'];
        }

        return [true, 'Akan diubah: ' . implode(' ', $diubah) . ' Isian admin tidak ditimpa.'];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function periksaAset(): array
    {
        $minify = $this->minify ?? config('Aplikasi')->minify;
        $hasil  = self::asetUsang($this->folderAset);

        if (! $minify && ! $hasil['ada_min']) {
            return [false, 'Tidak dipakai di komputer ini (aplikasi.minify = false di .env).'];
        }

        if ($hasil['usang'] === []) {
            return [false, 'Semua berkas kecil sudah terbaru.'];
        }

        return [true, count($hasil['usang']) . ' berkas belum diperkecil ulang: ' . self::ringkas($hasil['usang']) . '.'];
    }

    /**
     * @return array<mixed>
     */
    private function bacaJson(string $berkas): array
    {
        $isi = @file_get_contents($berkas);

        if ($isi === false) {
            throw new JsonException('Berkas ' . str_replace($this->akar, '', $berkas) . ' tidak bisa dibaca.');
        }

        $data = json_decode($isi, true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }

    /**
     * Lima butir pertama, sisanya disebut jumlahnya.
     *
     * @param list<string> $daftar
     */
    private static function ringkas(array $daftar): string
    {
        $tampil = implode(', ', array_slice($daftar, 0, 5));

        return count($daftar) > 5 ? $tampil . ', dan ' . (count($daftar) - 5) . ' lainnya' : $tampil;
    }
}
