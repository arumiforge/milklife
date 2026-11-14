<?php

declare(strict_types=1);

/**
 * Halaman monitoring (K102): keadaan PHP, server web (Nginx), basis data,
 * penyimpanan unggahan, dan catatan pengunjung per jam dan per hari.
 *
 * Satu berkas mandiri di folder publik, dibuka di `/arumi.php`. Berkas ini
 * memuat CodeIgniter hanya sebagai pustaka (pengaturan `.env`, basis data,
 * `App\Libraries\Pengunjung`) tanpa menjalankan rute, jadi tidak tercatat
 * sebagai pengunjung dan tidak memakai sesi admin.
 *
 * Keamanan:
 * - Harus masuk dengan kata sandi. Isi halaman (angka apa pun) baru dibuat
 *   setelah masuk; sebelum itu yang tampil hanya kerangka kosong yang
 *   dikaburkan di belakang kotak masuk.
 * - Kata sandi disimpan sebagai hash bcrypt. Hash bawaan ada di bawah; bisa
 *   diganti tanpa mengubah berkas ini lewat `.env`:
 *   `arumi.sandiHash = '<hasil password_hash()>'`.
 * - 5 kali salah dari alamat yang sama → dikunci 15 menit. Setiap salah
 *   ditahan 1 detik.
 * - Sesi sendiri (`arumi_sesi`, HttpOnly, SameSite=Strict, Secure di HTTPS),
 *   berakhir setelah 60 menit tanpa kegiatan. Formulir memakai token CSRF.
 * - Kepala `X-Robots-Tag: noindex`, `Cache-Control: no-store`, dan
 *   `frame-ancestors 'none'`.
 *
 * Tampilan memakai CSS Flowbite (Tailwind) dari `template-flowbites/`
 * (`aset/vendor/flowbite/flowbite-dasbor.css`) dan ApexCharts.
 */

// ---------- Muat CodeIgniter sebagai pustaka ----------

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);

require FCPATH . '../app/Config/Paths.php';
$paths = new Config\Paths();
require $paths->systemDirectory . '/Boot.php';

/**
 * Urutan `Boot::bootWeb()` sampai sebelum aplikasi dijalankan.
 */
final class SiapkanArumi extends CodeIgniter\Boot
{
    public static function jalankan(Config\Paths $paths): void
    {
        static::definePathConstants($paths);
        if (! defined('APP_NAMESPACE')) {
            static::loadConstants();
        }
        static::checkMissingExtensions();
        static::loadDotEnv($paths);
        static::defineEnvironment();
        static::loadEnvironmentBootstrap($paths);
        static::loadCommonFunctions();
        static::loadAutoloader();
        static::autoloadHelpers();
    }
}

SiapkanArumi::jalankan($paths);
date_default_timezone_set((string) (config('App')->appTimezone ?: 'Asia/Jakarta'));
helper('aplikasi');

// Hash bcrypt kata sandi bawaan. Ganti lewat `.env`: arumi.sandiHash.
const ARUMI_HASH_BAWAAN = '$2y$12$cmrn5TjjNRmXDQ8s9V1FTOppj85j7cfAxBngA1rnnoageb2HqDMUO';
const ARUMI_BATAS_GAGAL = 5;
const ARUMI_LAMA_KUNCI  = 900;   // detik
const ARUMI_LAMA_DIAM   = 3600;  // detik tanpa kegiatan sebelum keluar sendiri

// ---------- Kepala keamanan ----------

header('X-Robots-Tag: noindex, nofollow, noarchive');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");

// ---------- Alat bantu ----------

function e(mixed $teks): string
{
    return htmlspecialchars((string) $teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ukuran(int|float|null $bita): string
{
    if ($bita === null || $bita < 0) {
        return '–';
    }

    $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i      = 0;

    while ($bita >= 1024 && $i < count($satuan) - 1) {
        $bita /= 1024;
        $i++;
    }

    return number_format($bita, $i === 0 ? 0 : ($bita >= 100 ? 0 : 1), ',', '.') . ' ' . $satuan[$i];
}

function mb(int|float $bita): float
{
    return round($bita / 1048576, 2);
}

function angka(int|float $n, int $desimal = 0): string
{
    return number_format($n, $desimal, ',', '.');
}

function iniBita(string $nilai): int
{
    $nilai = trim($nilai);
    if ($nilai === '' || $nilai === '-1') {
        return -1;
    }
    $n = (int) $nilai;

    return match (strtolower(substr($nilai, -1))) {
        'g'     => $n * 1073741824,
        'm'     => $n * 1048576,
        'k'     => $n * 1024,
        default => $n,
    };
}

function lamaWaktu(int $detik): string
{
    $hari  = intdiv($detik, 86400);
    $jam   = intdiv($detik % 86400, 3600);
    $menit = intdiv($detik % 3600, 60);

    return ($hari > 0 ? $hari . ' hari ' : '') . ($jam > 0 || $hari > 0 ? $jam . ' jam ' : '') . $menit . ' menit';
}

/**
 * Ukuran dan jumlah berkas satu folder (rekursif). Berhenti setelah 200.000
 * berkas supaya halaman tetap cepat.
 *
 * @return array{ada: bool, bita: int, berkas: int}
 */
function ukuranFolder(string $folder): array
{
    if ($folder === '' || ! is_dir($folder)) {
        return ['ada' => false, 'bita' => 0, 'berkas' => 0];
    }

    $bita   = 0;
    $berkas = 0;

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        foreach ($iterator as $f) {
            if ($f->isFile()) {
                $bita += (int) $f->getSize();
                $berkas++;
            }
            if ($berkas >= 200000) {
                break;
            }
        }
    } catch (Throwable) {
        // Folder tidak bisa dibaca seluruhnya; pakai yang sudah terhitung.
    }

    return ['ada' => true, 'bita' => $bita, 'berkas' => $berkas];
}

function alamatPengunjung(): string
{
    $jauh = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

    // Di balik Cloudflare Tunnel permintaan datang dari komputer ini sendiri.
    if (in_array($jauh, ['127.0.0.1', '::1'], true) && ! empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        return (string) $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    return $jauh;
}

function lewatHttps(): bool
{
    if (! empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    return in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
        && strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

// ---------- Kunci setelah salah berulang ----------

/**
 * Baca dan ubah catatan gagal masuk di `writable/cache/arumi-masuk.json`.
 *
 * @param callable(array<string, array{gagal: int, sampai: int, terakhir: int}>): array<string, array{gagal: int, sampai: int, terakhir: int}> $ubah
 *
 * @return array<string, array{gagal: int, sampai: int, terakhir: int}>
 */
function catatanGagal(?callable $ubah = null): array
{
    $berkas = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'arumi-masuk.json';
    $h      = @fopen($berkas, 'c+');

    if ($h === false) {
        return [];
    }

    flock($h, LOCK_EX);
    $isi   = stream_get_contents($h);
    $data  = json_decode($isi === false || $isi === '' ? '[]' : $isi, true);
    $data  = is_array($data) ? $data : [];
    $kini  = time();

    // Buang catatan lama.
    $data = array_filter($data, static fn (array $c): bool => ($c['sampai'] ?? 0) > $kini || ($c['terakhir'] ?? 0) > $kini - ARUMI_LAMA_KUNCI);

    if ($ubah !== null) {
        $data = $ubah($data);
        ftruncate($h, 0);
        rewind($h);
        fwrite($h, (string) json_encode($data));
    }

    flock($h, LOCK_UN);
    fclose($h);

    return $data;
}

function kunciAlamat(): string
{
    return hash('sha256', alamatPengunjung() . '|arumi');
}

function sisaKunci(): int
{
    $c = catatanGagal()[kunciAlamat()] ?? null;

    return $c === null ? 0 : max(0, (int) $c['sampai'] - time());
}

// ---------- Sesi ----------

$folderSesi = WRITEPATH . 'session' . DIRECTORY_SEPARATOR . 'arumi';
if (! is_dir($folderSesi)) {
    @mkdir($folderSesi, 0700, true);
}
if (is_dir($folderSesi) && is_writable($folderSesi)) {
    session_save_path($folderSesi);
}

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.gc_maxlifetime', (string) ARUMI_LAMA_DIAM);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '50');
// Alamat halaman ini dari permintaan, tanpa kueri (misalnya `/arumi.php`).
$alamatIni = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/arumi.php'), PHP_URL_PATH);
$alamatIni = preg_match('~^[A-Za-z0-9/._-]+$~', $alamatIni) === 1 ? $alamatIni : '/arumi.php';

session_name('arumi_sesi');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => $alamatIni,
    'secure'   => lewatHttps(),
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

$_SESSION['token'] ??= bin2hex(random_bytes(32));

$sudahMasuk = ($_SESSION['masuk'] ?? false) === true;

if ($sudahMasuk && time() - (int) ($_SESSION['aktif'] ?? 0) > ARUMI_LAMA_DIAM) {
    $_SESSION   = ['token' => bin2hex(random_bytes(32)), 'pesan' => 'Sesi berakhir karena lama tidak dipakai. Masuk lagi.'];
    $sudahMasuk = false;
    session_regenerate_id(true);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tokenSah = is_string($_POST['token'] ?? null) && hash_equals($_SESSION['token'], $_POST['token']);
    $aksi     = (string) ($_POST['aksi'] ?? '');

    if (! $tokenSah) {
        $_SESSION['pesan'] = 'Halaman sudah terlalu lama dibuka. Coba lagi.';
    } elseif ($aksi === 'keluar') {
        $_SESSION = ['token' => bin2hex(random_bytes(32)), 'pesan' => 'Anda sudah keluar.', 'jenis_pesan' => 'sukses'];
        session_regenerate_id(true);
    } elseif ($aksi === 'masuk') {
        $sisa = sisaKunci();

        if ($sisa > 0) {
            $_SESSION['pesan'] = 'Terlalu banyak kata sandi salah. Coba lagi dalam ' . (int) ceil($sisa / 60) . ' menit.';
        } else {
            $hash  = trim((string) env('arumi.sandiHash', ''));
            $hash  = $hash !== '' ? $hash : ARUMI_HASH_BAWAAN;
            $sandi = (string) ($_POST['sandi'] ?? '');

            if ($sandi !== '' && strlen($sandi) <= 200 && password_verify($sandi, $hash)) {
                catatanGagal(static function (array $d): array {
                    unset($d[kunciAlamat()]);

                    return $d;
                });
                session_regenerate_id(true);
                $_SESSION['masuk'] = true;
                $_SESSION['aktif'] = time();
                $_SESSION['token'] = bin2hex(random_bytes(32));
            } else {
                sleep(1);
                $data = catatanGagal(static function (array $d): array {
                    $k      = kunciAlamat();
                    $c      = $d[$k] ?? ['gagal' => 0, 'sampai' => 0, 'terakhir' => 0];
                    $c['gagal']++;
                    $c['terakhir'] = time();
                    if ($c['gagal'] >= ARUMI_BATAS_GAGAL) {
                        $c['sampai'] = time() + ARUMI_LAMA_KUNCI;
                        $c['gagal']  = 0;
                    }
                    $d[$k] = $c;

                    return $d;
                });
                $c = $data[kunciAlamat()] ?? ['gagal' => 0, 'sampai' => 0];
                $_SESSION['pesan'] = $c['sampai'] > time()
                    ? 'Terlalu banyak kata sandi salah. Coba lagi dalam ' . (int) ceil(ARUMI_LAMA_KUNCI / 60) . ' menit.'
                    : 'Kata sandi salah. Sisa percobaan: ' . (ARUMI_BATAS_GAGAL - (int) $c['gagal']) . '.';
            }
        }
    }

    $kueri = isset($_GET['tanggal']) && is_string($_GET['tanggal']) ? '?tanggal=' . rawurlencode($_GET['tanggal']) : '';
    header('Location: ' . $alamatIni . $kueri, true, 303);

    exit;
}

$pesan      = $_SESSION['pesan'] ?? null;
$jenisPesan = $_SESSION['jenis_pesan'] ?? 'galat';
unset($_SESSION['pesan'], $_SESSION['jenis_pesan']);

if ($sudahMasuk) {
    $_SESSION['aktif'] = time();
}

$token = $_SESSION['token'];

// ---------- Kumpulkan data (hanya setelah masuk) ----------

$hariIni = date('Y-m-d');
$tanggal = App\Libraries\Pengunjung::tanggalSah(is_string($_GET['tanggal'] ?? null) ? $_GET['tanggal'] : null, $hariIni);
if ($tanggal > $hariIni) {
    $tanggal = $hariIni;
}

$d = [];

if ($sudahMasuk) {
    $mulai = microtime(true);

    // PHP
    $opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
    $ekstensiWajib = ['intl', 'mbstring', 'mysqli', 'gd', 'zip', 'xml', 'curl', 'fileinfo', 'json', 'openssl', 'dom', 'iconv'];

    $d['php'] = [
        'versi'       => PHP_VERSION,
        'sapi'        => PHP_SAPI,
        'zona'        => date_default_timezone_get(),
        'lingkungan'  => defined('ENVIRONMENT') ? ENVIRONMENT : '–',
        'batas'       => [
            'memory_limit'        => ini_get('memory_limit') === '-1' ? 'tanpa batas' : ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size'       => ini_get('post_max_size'),
            'max_execution_time'  => ini_get('max_execution_time') . ' detik',
            'max_input_vars'      => ini_get('max_input_vars'),
            'max_file_uploads'    => ini_get('max_file_uploads'),
        ],
        'tampil_galat' => in_array(strtolower((string) ini_get('display_errors')), ['1', 'on', 'true', 'stdout'], true),
        'memori'       => memory_get_usage(true),
        'memori_puncak' => memory_get_peak_usage(true),
        'memori_batas' => iniBita((string) ini_get('memory_limit')),
        'opcache'      => is_array($opcache) ? [
            'aktif'   => (bool) ($opcache['opcache_enabled'] ?? false),
            'dipakai' => (int) ($opcache['memory_usage']['used_memory'] ?? 0),
            'bebas'   => (int) ($opcache['memory_usage']['free_memory'] ?? 0),
            'skrip'   => (int) ($opcache['opcache_statistics']['num_cached_scripts'] ?? 0),
            'kena'    => (float) ($opcache['opcache_statistics']['opcache_hit_rate'] ?? 0),
        ] : null,
        'ekstensi'     => array_combine($ekstensiWajib, array_map('extension_loaded', $ekstensiWajib)),
        'log_galat'    => (string) ini_get('error_log'),
    ];

    // Server dan Nginx
    $perangkat   = (string) ($_SERVER['SERVER_SOFTWARE'] ?? '');
    $laragon     = null;
    $logNginx    = [];
    $jalurAkar   = str_replace('\\', '/', ROOTPATH);

    if (preg_match('~^(.*?/laragon)/~i', $jalurAkar, $m) === 1) {
        $laragon = $m[1];
        foreach (glob($laragon . '/bin/nginx/*/logs/*.log') ?: [] as $log) {
            $logNginx[] = ['nama' => basename(dirname($log, 2)) . '/' . basename($log), 'bita' => (int) @filesize($log)];
        }
    }
    foreach (['/var/log/nginx/access.log', '/var/log/nginx/error.log'] as $log) {
        if (@is_readable($log)) {
            $logNginx[] = ['nama' => $log, 'bita' => (int) @filesize($log)];
        }
    }

    $muatan = function_exists('sys_getloadavg') ? @sys_getloadavg() : false;
    $nyala  = @is_readable('/proc/uptime') ? (int) (float) explode(' ', (string) @file_get_contents('/proc/uptime'))[0] : null;
    $memSistem = null;
    if (@is_readable('/proc/meminfo')) {
        $isi = (string) @file_get_contents('/proc/meminfo');
        if (preg_match('/MemTotal:\s+(\d+)/', $isi, $a) && preg_match('/MemAvailable:\s+(\d+)/', $isi, $b)) {
            $memSistem = ['total' => (int) $a[1] * 1024, 'tersedia' => (int) $b[1] * 1024];
        }
    }

    $d['server'] = [
        'perangkat'   => $perangkat !== '' ? $perangkat : 'Tidak diketahui',
        'nginx'       => preg_match('~nginx/?([\d.]*)~i', $perangkat, $m) === 1 ? ($m[1] !== '' ? $m[1] : 'ya') : null,
        'os'          => PHP_OS_FAMILY . ' · ' . php_uname('r'),
        'nama_mesin'  => php_uname('n'),
        'protokol'    => (string) ($_SERVER['SERVER_PROTOCOL'] ?? '–'),
        'https'       => lewatHttps(),
        'cloudflare'  => ! empty($_SERVER['HTTP_CF_RAY']),
        'negara'      => (string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''),
        'host'        => (string) ($_SERVER['HTTP_HOST'] ?? ''),
        'akar'        => (string) ($_SERVER['DOCUMENT_ROOT'] ?? ''),
        'laragon'     => $laragon,
        'log_nginx'   => $logNginx,
        'muatan'      => is_array($muatan) ? array_map(static fn ($n): string => angka((float) $n, 2), $muatan) : null,
        'nyala'       => $nyala,
        'mem_sistem'  => $memSistem,
        'cpu'         => @is_readable('/proc/cpuinfo') ? substr_count((string) @file_get_contents('/proc/cpuinfo'), 'processor') : (getenv('NUMBER_OF_PROCESSORS') ?: null),
    ];

    // Basis data
    try {
        $db = db_connect();
        $namaDb = $db->getDatabase();
        $status = [];
        foreach ($db->query("SHOW GLOBAL STATUS WHERE Variable_name IN ('Uptime', 'Threads_connected', 'Threads_running', 'Questions', 'Slow_queries', 'Max_used_connections', 'Aborted_connects')")->getResultArray() as $b) {
            $status[$b['Variable_name']] = (int) $b['Value'];
        }
        $variabel = [];
        foreach ($db->query("SHOW GLOBAL VARIABLES WHERE Variable_name IN ('max_connections', 'innodb_buffer_pool_size', 'version_comment')")->getResultArray() as $b) {
            $variabel[$b['Variable_name']] = $b['Value'];
        }
        $tabel = $db->query(
            'SELECT TABLE_NAME AS nama, TABLE_ROWS AS baris, DATA_LENGTH AS data, INDEX_LENGTH AS indeks, DATA_FREE AS bebas'
            . ' FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? ORDER BY (DATA_LENGTH + INDEX_LENGTH) DESC',
            [$namaDb],
        )->getResultArray();

        $hitung = static function (string $t) use ($db): ?int {
            try {
                return (int) $db->table($t)->countAllResults();
            } catch (Throwable) {
                return null;
            }
        };

        $d['db'] = [
            'ada'      => true,
            'versi'    => (string) $db->getVersion(),
            'jenis'    => stripos((string) ($variabel['version_comment'] ?? ''), 'mariadb') !== false ? 'MariaDB' : 'MySQL',
            'nama'     => $namaDb,
            'host'     => $db->hostname,
            'ukuran'   => array_sum(array_map(static fn (array $t): int => (int) $t['data'] + (int) $t['indeks'], $tabel)),
            'data'     => array_sum(array_map(static fn (array $t): int => (int) $t['data'], $tabel)),
            'indeks'   => array_sum(array_map(static fn (array $t): int => (int) $t['indeks'], $tabel)),
            'tabel'    => $tabel,
            'status'   => $status,
            'maks_sambungan' => (int) ($variabel['max_connections'] ?? 0),
            'buffer'   => (int) ($variabel['innodb_buffer_pool_size'] ?? 0),
            'jumlah'   => [
                'Pendaftaran'      => $hitung('pendaftaran'),
                'Atlet'            => $hitung('atlet'),
                'Bukti bayar'      => $hitung('bukti_bayar'),
                'Sekolah (data)'   => $hitung('sekolah'),
                'Catatan kunjungan' => $hitung('kunjungan'),
            ],
        ];
    } catch (Throwable $e) {
        $d['db'] = ['ada' => false, 'galat' => $e->getMessage()];
    }

    // Penyimpanan
    $folderCadangan = trim((string) env('cadangan.folder', ''));
    $kategori = [
        ['Bukti bayar', 'writable/unggahan/bukti-bayar', WRITEPATH . 'unggahan/bukti-bayar', 'gambar'],
        ['Foto penanggung jawab', 'writable/unggahan/penanggung-jawab', WRITEPATH . 'unggahan/penanggung-jawab', 'gambar'],
        ['Gambar halaman depan', 'public/unggahan/halaman-depan', FCPATH . 'unggahan/halaman-depan', 'gambar'],
        ['Logo pembayaran', 'public/unggahan/pembayaran', FCPATH . 'unggahan/pembayaran', 'gambar'],
        ['Berkas unduhan', 'public/unggahan/unduhan', FCPATH . 'unggahan/unduhan', 'gambar'],
        ['Log aplikasi', 'writable/logs', WRITEPATH . 'logs', 'sistem'],
        ['Sesi', 'writable/session', WRITEPATH . 'session', 'sistem'],
        ['Cache', 'writable/cache', WRITEPATH . 'cache', 'sistem'],
        ['Kode aplikasi', 'app', APPPATH, 'kode'],
        ['Aset tampilan', 'public/aset', FCPATH . 'aset', 'kode'],
        ['Pustaka (vendor)', 'vendor', ROOTPATH . 'vendor', 'kode'],
    ];

    $penyimpanan = [];
    foreach ($kategori as [$label, $jalur, $folder, $kelompok]) {
        $penyimpanan[] = ['label' => $label, 'jalur' => $jalur, 'kelompok' => $kelompok] + ukuranFolder($folder);
    }

    $cadangan = $folderCadangan !== '' ? ['label' => 'Cadangan (di luar web)', 'jalur' => $folderCadangan] + ukuranFolder($folderCadangan) : null;

    $jumlahKelompok = static fn (string $k): int => array_sum(array_map(static fn (array $p): int => $p['kelompok'] === $k ? $p['bita'] : 0, $penyimpanan));

    $d['simpan'] = [
        'daftar'    => $penyimpanan,
        'cadangan'  => $cadangan,
        'gambar'    => $jumlahKelompok('gambar'),
        'sistem'    => $jumlahKelompok('sistem'),
        'kode'      => $jumlahKelompok('kode'),
        'total'     => array_sum(array_column($penyimpanan, 'bita')),
        'disk_bebas' => @disk_free_space(ROOTPATH) ?: null,
        'disk_total' => @disk_total_space(ROOTPATH) ?: null,
    ];

    // Pengunjung
    try {
        $p = new App\Libraries\Pengunjung();
        $d['pengunjung'] = [
            'ada'       => true,
            'ringkasan' => $p->ringkasan($hariIni),
            'jam'       => $p->perJam($tanggal),
            'hari'      => $p->perHari(App\Libraries\Pengunjung::geser($tanggal, -29), $tanggal),
            'halaman'   => $p->halamanTeratas($tanggal, 10),
            'pertama'   => $p->tanggalPertama(),
        ];
    } catch (Throwable $e) {
        $d['pengunjung'] = ['ada' => false, 'galat' => $e->getMessage()];
    }

    $d['lama'] = (int) round((microtime(true) - $mulai) * 1000);
}

// ---------- Tampilan ----------

$hariId  = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulanId = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tanggalPanjang = static function (string $t) use ($hariId, $bulanId): string {
    $w = strtotime($t);

    return $hariId[(int) date('w', $w)] . ', ' . (int) date('j', $w) . ' ' . $bulanId[(int) date('n', $w)] . ' ' . date('Y', $w);
};

$css = 'aset/vendor/flowbite/flowbite-dasbor.css';
$ikon = 'aset/vendor/bootstrap-icons/bootstrap-icons.min.css';

$kartuStatus = static function (string $label, string $nilai, string $keterangan, string $ikonBi, string $warna): string {
    return '<div class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6">'
        . '<div class="flex items-start justify-between gap-3"><div class="min-w-0">'
        . '<p class="text-sm text-gray-500 dark:text-gray-400">' . e($label) . '</p>'
        . '<p class="mt-1 text-2xl font-bold leading-none text-gray-900 dark:text-white sm:text-3xl">' . e($nilai) . '</p></div>'
        . '<span class="ikon-status ' . $warna . '"><i class="bi bi-' . $ikonBi . '" aria-hidden="true"></i></span></div>'
        . '<p class="mt-3 text-sm text-gray-500 dark:text-gray-400">' . $keterangan . '</p></div>';
};

$baris = static function (string $label, string $nilai): string {
    return '<div class="flex items-start justify-between gap-4 py-2"><dt class="text-sm text-gray-500 dark:text-gray-400">' . e($label) . '</dt><dd class="text-end text-sm font-medium text-gray-900 dark:text-white break-all">' . $nilai . '</dd></div>';
};

$lencana = static function (bool $baik, string $ya, string $tidak): string {
    return $baik
        ? '<span class="rounded-md bg-green-100 dark:bg-green-900 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:text-green-300">' . e($ya) . '</span>'
        : '<span class="rounded-md bg-red-100 dark:bg-red-900 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:text-red-300">' . e($tidak) . '</span>';
};
?>
<!doctype html>
<html lang="id" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="color-scheme" content="dark">
    <title>Monitoring</title>
    <link rel="icon" href="aset/gambar/ikon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= e($css) ?>">
    <link rel="stylesheet" href="<?= e($ikon) ?>">
    <style>
        body { font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        .ikon-status { display: inline-flex; align-items: center; justify-content: center; flex: none; width: 2.75rem; height: 2.75rem; border-radius: .625rem; font-size: 1.25rem; }
        .ikon-biru { background: rgba(28, 100, 242, .18); color: #76a9fa; }
        .ikon-hijau { background: rgba(49, 196, 141, .16); color: #31c48d; }
        .ikon-oranye { background: rgba(255, 138, 76, .16); color: #ff8a4c; }
        .ikon-ungu { background: rgba(144, 97, 249, .18); color: #ac94fa; }
        .kabur { filter: blur(7px); pointer-events: none; user-select: none; }
        .tirai { position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; padding: 1rem; background: rgba(3, 7, 18, .55); -webkit-backdrop-filter: blur(8px); backdrop-filter: blur(8px); }
        .kotak-masuk { box-shadow: 0 20px 50px rgba(0, 0, 0, .5); border: 1px solid #374151; }
        .tombol-masuk { flex: none; min-width: 3rem; font-size: 1.25rem; }
        .tombol-masuk:disabled, #sandi:disabled { opacity: .5; cursor: not-allowed; }
        .isian-salah { border-color: #f05252 !important; box-shadow: 0 0 0 2px rgba(240, 82, 82, .35); animation: goyang .3s; }
        @keyframes goyang { 25% { transform: translateX(-6px); } 75% { transform: translateX(6px); } }
        .bilah { height: .5rem; border-radius: 9999px; background: #374151; overflow: hidden; }
        .bilah > span { display: block; height: 100%; border-radius: 9999px; }
        .kerangka { background: #374151; border-radius: .5rem; }
        .grafik { min-height: 280px; }
        .menempel { position: sticky; }
        .tabel-gulir { overflow-x: auto; }
        .apexcharts-tooltip { border: 0 !important; box-shadow: 0 6px 20px rgba(0, 0, 0, .4) !important; }
        details summary::marker { color: #9ca3af; }
        input[type="date"]::-webkit-calendar-picker-indicator { cursor: pointer; }
        :focus-visible { outline: 3px solid #1a56db; outline-offset: 2px; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-700 text-gray-900 antialiased dark:bg-gray-900 dark:text-white">

<?php if (! $sudahMasuk) : ?>
    <?php /* Kerangka kosong di belakang kotak masuk: tidak ada data sungguhan sebelum masuk. */ ?>
    <div class="kabur" aria-hidden="true">
        <nav class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
            <div class="kerangka h-6 w-48"></div>
        </nav>
        <main class="mx-auto max-w-screen-2xl space-y-4 p-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <?php for ($i = 0; $i < 4; $i++) : ?>
                    <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm"><div class="kerangka mb-3 h-4 w-24"></div><div class="kerangka h-8 w-32"></div></div>
                <?php endfor ?>
            </div>
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm xl:col-span-2"><div class="kerangka h-64 w-full"></div></div>
                <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm"><div class="kerangka h-64 w-full"></div></div>
            </div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm"><div class="kerangka h-48 w-full"></div></div>
                <div class="rounded-lg bg-white dark:bg-gray-800 p-6 shadow-sm"><div class="kerangka h-48 w-full"></div></div>
            </div>
        </main>
    </div>

    <div class="tirai" role="dialog" aria-modal="true" aria-label="Masuk">
        <form method="post" action="<?= e($alamatIni . ($tanggal !== $hariIni ? '?tanggal=' . $tanggal : '')) ?>" class="kotak-masuk w-full max-w-xs rounded-lg bg-white p-4 shadow-sm dark:bg-gray-800" autocomplete="off">
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <input type="hidden" name="aksi" value="masuk">
            <?php if ($pesan !== null) : ?>
                <p class="sr-only" role="alert"><?= e($pesan) ?></p>
            <?php endif ?>
            <label for="sandi" class="sr-only">Kata sandi</label>
            <div class="flex gap-2">
                <input type="password" name="sandi" id="sandi" required autofocus maxlength="200" autocomplete="current-password"
                    class="block w-full rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white<?= $pesan !== null && $jenisPesan !== 'sukses' ? ' isian-salah' : '' ?>"
                    <?= sisaKunci() > 0 ? 'disabled' : '' ?>>
                <button type="submit" class="tombol-masuk rounded-lg bg-primary-700 px-4 text-white hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-primary-300 dark:bg-primary-600 dark:hover:bg-primary-700 dark:focus:ring-primary-800"<?= sisaKunci() > 0 ? ' disabled' : '' ?>>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i><span class="sr-only">Masuk</span>
                </button>
            </div>
        </form>
    </div>
<?php else : ?>
    <?php
    $php   = $d['php'];
    $srv   = $d['server'];
    $db    = $d['db'];
    $simp  = $d['simpan'];
    $pgj   = $d['pengunjung'];
    $kemarin = App\Libraries\Pengunjung::geser($tanggal, -1);
    $besok   = App\Libraries\Pengunjung::geser($tanggal, 1);
    $jsonGrafik = [
        'jam'    => $pgj['ada'] ? $pgj['jam'] : [],
        'hari'   => $pgj['ada'] ? $pgj['hari'] : [],
        'simpan' => array_values(array_map(static fn (array $p): array => ['label' => $p['label'], 'mb' => mb($p['bita'])], array_filter($simp['daftar'], static fn (array $p): bool => $p['kelompok'] === 'gambar' && $p['bita'] > 0))),
        'tabel'  => $db['ada'] ? array_map(static fn (array $t): array => ['nama' => $t['nama'], 'mb' => mb((int) $t['data'] + (int) $t['indeks'])], array_slice($db['tabel'], 0, 10)) : [],
        'tanggal' => $tanggal,
    ];
    ?>
    <nav class="menempel top-0 z-40 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 py-3">
        <div class="mx-auto flex max-w-screen-2xl flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span class="ikon-status ikon-biru"><i class="bi bi-activity" aria-hidden="true"></i></span>
                <div>
                    <h1 class="text-lg font-bold leading-tight text-gray-900 dark:text-white">Monitoring</h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400"><?= e($tanggalPanjang($hariIni)) ?>, <?= date('H.i') ?> · dimuat dalam <?= (int) $d['lama'] ?> ms</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="<?= e($alamatIni . ($tanggal !== $hariIni ? '?tanggal=' . $tanggal : '')) ?>" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">
                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i><span class="hidden sm:inline">Muat ulang</span>
                </a>
                <form method="post" action="<?= e($alamatIni) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <input type="hidden" name="aksi" value="keluar">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800">
                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="hidden sm:inline">Keluar</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <main class="mx-auto max-w-screen-2xl space-y-4 p-4">
        <?php if ($pesan !== null) : ?>
            <div class="rounded-lg bg-red-100 dark:bg-red-900 p-4 text-sm text-red-800 dark:text-red-300" role="alert"><?= e($pesan) ?></div>
        <?php endif ?>

        <!-- Ringkasan -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan">
            <?= $kartuStatus('PHP', $php['versi'], e($php['sapi']) . ' · ' . ($php['opcache'] && $php['opcache']['aktif'] ? 'OPcache menyala' : 'OPcache mati'), 'filetype-php', 'ikon-biru') ?>
            <?= $kartuStatus('Ukuran basis data', $db['ada'] ? ukuran($db['ukuran']) : 'Gagal', $db['ada'] ? angka(count($db['tabel'])) . ' tabel · ' . e($db['jenis']) . ' ' . e($db['versi']) : 'Basis data tidak tersambung', 'database', 'ikon-ungu') ?>
            <?= $kartuStatus('Gambar dan bukti', angka(mb($simp['gambar']), 2) . ' MB', 'Total penyimpanan web ' . e(ukuran($simp['total'])), 'images', 'ikon-oranye') ?>
            <?= $kartuStatus('Pengunjung hari ini', $pgj['ada'] ? angka($pgj['ringkasan']['hari_ini']['unik']) : '–', $pgj['ada'] ? angka($pgj['ringkasan']['hari_ini']['kunjungan']) . ' halaman dibuka · kemarin ' . angka($pgj['ringkasan']['kemarin']['unik']) : 'Catatan pengunjung belum tersedia', 'people', 'ikon-hijau') ?>
        </section>

        <!-- Pengunjung -->
        <section class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6" aria-labelledby="judul-pengunjung" id="pengunjung">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 id="judul-pengunjung" class="text-xl font-bold text-gray-900 dark:text-white">Pengunjung</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400"><?= e($tanggalPanjang($tanggal)) ?><?= $tanggal === $hariIni ? ' (hari ini)' : '' ?></p>
                </div>
                <form method="get" action="<?= e($alamatIni) ?>#pengunjung" class="flex flex-wrap items-center gap-2">
                    <a href="<?= e($alamatIni . '?tanggal=' . $kemarin) ?>#pengunjung" class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" title="Hari sebelumnya">
                        <i class="bi bi-chevron-left" aria-hidden="true"></i><span class="sr-only">Hari sebelumnya</span>
                    </a>
                    <label for="tanggal" class="sr-only">Pilih tanggal</label>
                    <input type="date" id="tanggal" name="tanggal" value="<?= e($tanggal) ?>" max="<?= e($hariIni) ?>" min="<?= e($pgj['ada'] && $pgj['pertama'] ? $pgj['pertama'] : '2026-01-01') ?>"
                        class="rounded-lg border border-gray-300 bg-gray-50 dark:bg-gray-700 p-2 text-sm text-gray-900 dark:text-white focus:border-primary-500 focus:ring-primary-500" onchange="this.form.submit()">
                    <?php if ($tanggal < $hariIni) : ?>
                        <a href="<?= e($alamatIni . '?tanggal=' . $besok) ?>#pengunjung" class="inline-flex items-center rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700" title="Hari berikutnya">
                            <i class="bi bi-chevron-right" aria-hidden="true"></i><span class="sr-only">Hari berikutnya</span>
                        </a>
                        <a href="<?= e($alamatIni) ?>#pengunjung" class="rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white hover:bg-primary-800">Hari ini</a>
                    <?php endif ?>
                    <noscript><button type="submit" class="rounded-lg bg-primary-700 px-3 py-2 text-sm font-medium text-white">Lihat</button></noscript>
                </form>
            </div>

            <?php if (! $pgj['ada']) : ?>
                <p class="rounded-lg bg-yellow-100 dark:bg-yellow-900 p-4 text-sm text-yellow-800 dark:text-yellow-300">Catatan pengunjung belum bisa dibaca. Jalankan <code>php spark migrate</code> supaya tabel <code>kunjungan</code> dibuat.</p>
            <?php else : ?>
                <?php
                $totalJam = array_sum(array_column($pgj['jam'], 'kunjungan'));
                // Pengunjung unik satu hari dari hitungan harian (jumlah per jam menghitung ganda).
                $unikJam  = (int) (end($pgj['hari'])['unik'] ?? 0);
                $puncak   = $totalJam > 0 ? array_reduce($pgj['jam'], static fn (?array $a, array $b): array => $a === null || $b['kunjungan'] > $a['kunjungan'] ? $b : $a) : null;
                $r        = $pgj['ringkasan'];
                ?>
                <dl class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
                    <?php foreach ([
                        ['Pengunjung tanggal ini', angka($unikJam)],
                        ['Halaman dibuka', angka($totalJam)],
                        ['Jam tersibuk', $puncak ? sprintf('%02d.00–%02d.59', $puncak['jam'], $puncak['jam']) : '–'],
                        ['7 hari terakhir', angka($r['tujuh_hari']['unik'])],
                        ['30 hari terakhir', angka($r['tiga_puluh_hari']['unik'])],
                        ['Dari ponsel', $r['ponsel_persen'] . '%'],
                    ] as [$l, $v]) : ?>
                        <div class="rounded-lg bg-gray-50 dark:bg-gray-700 p-3">
                            <dt class="text-xs text-gray-500 dark:text-gray-400"><?= e($l) ?></dt>
                            <dd class="text-lg font-bold text-gray-900 dark:text-white"><?= e($v) ?></dd>
                        </div>
                    <?php endforeach ?>
                </dl>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <div class="xl:col-span-2">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Per jam</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Pengunjung dan halaman dibuka setiap jam (WIB)</p>
                        <div id="grafik-jam" class="grafik"></div>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Halaman terpopuler</h3>
                        <p class="mb-2 text-xs text-gray-500 dark:text-gray-400">Pada tanggal yang dipilih</p>
                        <?php if ($pgj['halaman'] === []) : ?>
                            <p class="rounded-lg bg-gray-50 dark:bg-gray-700 p-4 text-sm text-gray-500 dark:text-gray-400">Belum ada kunjungan pada tanggal ini.</p>
                        <?php else : ?>
                            <?php $maks = max(array_column($pgj['halaman'], 'kunjungan')); ?>
                            <ul class="space-y-3">
                                <?php foreach ($pgj['halaman'] as $h) : ?>
                                    <li>
                                        <div class="mb-1 flex justify-between gap-2 text-sm"><span class="truncate font-medium text-gray-900 dark:text-white"><?= e($h['halaman']) ?></span><span class="text-gray-500 dark:text-gray-400"><?= angka($h['kunjungan']) ?></span></div>
                                        <div class="bilah"><span style="width: <?= (int) round($h['kunjungan'] / $maks * 100) ?>%; background: #3f83f8"></span></div>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        <?php endif ?>
                    </div>
                </div>

                <div class="mt-6">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Per hari</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">30 hari sampai tanggal yang dipilih. Klik batang untuk melihat hari itu.</p>
                    <div id="grafik-hari" class="grafik"></div>
                </div>
            <?php endif ?>
        </section>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- PHP -->
            <section class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6" aria-labelledby="judul-php">
                <h2 id="judul-php" class="mb-1 flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white"><i class="bi bi-filetype-php text-primary-700 dark:text-primary-500" aria-hidden="true"></i> Kondisi PHP</h2>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Versi, batas, dan ekstensi yang dibutuhkan aplikasi</p>
                <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?= $baris('Versi', e($php['versi']) . ' ' . $lencana(version_compare($php['versi'], '8.2', '>='), 'sesuai', 'perlu 8.2+')) ?>
                    <?= $baris('SAPI', e($php['sapi'])) ?>
                    <?= $baris('Lingkungan aplikasi', e($php['lingkungan']) . ' ' . $lencana($php['lingkungan'] === 'production', 'produksi', 'bukan produksi')) ?>
                    <?= $baris('Zona waktu', e($php['zona'])) ?>
                    <?= $baris('Galat tampil di layar', $lencana(! $php['tampil_galat'], 'tidak', 'ya')) ?>
                    <?php foreach ($php['batas'] as $k => $v) : ?>
                        <?= $baris($k, e((string) $v)) ?>
                    <?php endforeach ?>
                    <?= $baris('Memori halaman ini', e(ukuran($php['memori'])) . ' (puncak ' . e(ukuran($php['memori_puncak'])) . ')') ?>
                    <?php if ($php['opcache']) : ?>
                        <?= $baris('OPcache', $lencana($php['opcache']['aktif'], 'menyala', 'mati') . ($php['opcache']['aktif'] ? ' ' . e(ukuran($php['opcache']['dipakai'])) . ' dipakai, ' . angka($php['opcache']['skrip']) . ' skrip, kena ' . angka($php['opcache']['kena'], 1) . '%' : '')) ?>
                    <?php else : ?>
                        <?= $baris('OPcache', $lencana(false, '', 'tidak terpasang')) ?>
                    <?php endif ?>
                    <?php if ($php['log_galat'] !== '' && @is_file($php['log_galat'])) : ?>
                        <?= $baris('Log galat PHP', e(ukuran((int) @filesize($php['log_galat'])))) ?>
                    <?php endif ?>
                </dl>
                <h3 class="mb-2 mt-4 text-sm font-semibold text-gray-900 dark:text-white">Ekstensi</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($php['ekstensi'] as $nama => $ada) : ?>
                        <span class="inline-flex items-center gap-1 rounded-md <?= $ada ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-300' ?> px-2.5 py-0.5 text-xs font-medium">
                            <i class="bi bi-<?= $ada ? 'check-lg' : 'x-lg' ?>" aria-hidden="true"></i><?= e($nama) ?><span class="sr-only"><?= $ada ? ' terpasang' : ' tidak terpasang' ?></span>
                        </span>
                    <?php endforeach ?>
                </div>
            </section>

            <!-- Server dan Nginx -->
            <section class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6" aria-labelledby="judul-server">
                <h2 id="judul-server" class="mb-1 flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white"><i class="bi bi-hdd-network text-primary-700 dark:text-primary-500" aria-hidden="true"></i> Server web dan Nginx</h2>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">Yang terbaca PHP dari server web dan sistem</p>
                <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                    <?= $baris('Server web', e($srv['perangkat']) . ' ' . ($srv['nginx'] !== null ? $lencana(true, 'Nginx', '') : '')) ?>
                    <?= $baris('Alamat', e($srv['host'])) ?>
                    <?= $baris('Protokol', e($srv['protokol'])) ?>
                    <?= $baris('HTTPS', $lencana($srv['https'], 'ya', 'tidak')) ?>
                    <?= $baris('Lewat Cloudflare', $lencana($srv['cloudflare'], 'ya' . ($srv['negara'] !== '' ? ' (' . $srv['negara'] . ')' : ''), 'tidak')) ?>
                    <?= $baris('Sistem operasi', e($srv['os'])) ?>
                    <?= $baris('Nama komputer', e($srv['nama_mesin'])) ?>
                    <?php if ($srv['cpu']) : ?><?= $baris('Inti prosesor', e((string) $srv['cpu'])) ?><?php endif ?>
                    <?php if ($srv['muatan']) : ?><?= $baris('Beban (1/5/15 menit)', e(implode(' · ', $srv['muatan']))) ?><?php endif ?>
                    <?php if ($srv['nyala'] !== null) : ?><?= $baris('Menyala selama', e(lamaWaktu($srv['nyala']))) ?><?php endif ?>
                    <?php if ($srv['mem_sistem']) : ?>
                        <?php $pakai = $srv['mem_sistem']['total'] - $srv['mem_sistem']['tersedia']; ?>
                        <?= $baris('Memori sistem', e(ukuran($pakai)) . ' dari ' . e(ukuran($srv['mem_sistem']['total']))) ?>
                    <?php endif ?>
                    <?= $baris('Folder publik', e($srv['akar'])) ?>
                    <?php if ($srv['laragon']) : ?><?= $baris('Laragon', e($srv['laragon'])) ?><?php endif ?>
                    <?php foreach ($srv['log_nginx'] as $log) : ?>
                        <?= $baris('Log ' . $log['nama'], e(ukuran($log['bita'])) . ' ' . ($log['bita'] > 104857600 ? $lencana(false, '', 'besar') : '')) ?>
                    <?php endforeach ?>
                </dl>
                <?php if ($simp['disk_total']) : ?>
                    <?php $persenDisk = (int) round(($simp['disk_total'] - $simp['disk_bebas']) / $simp['disk_total'] * 100); ?>
                    <div class="mt-4">
                        <div class="mb-1 flex justify-between text-sm"><span class="font-medium text-gray-900 dark:text-white">Diska server</span><span class="text-gray-500 dark:text-gray-400"><?= e(ukuran($simp['disk_bebas'])) ?> bebas dari <?= e(ukuran($simp['disk_total'])) ?></span></div>
                        <div class="bilah"><span style="width: <?= $persenDisk ?>%; background: <?= $persenDisk >= 90 ? '#e02424' : ($persenDisk >= 75 ? '#ff8a4c' : '#0e9f6e') ?>"></span></div>
                    </div>
                <?php endif ?>
            </section>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <!-- Penyimpanan -->
            <section class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6" aria-labelledby="judul-simpan">
                <h2 id="judul-simpan" class="mb-1 flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white"><i class="bi bi-folder2-open text-primary-700 dark:text-primary-500" aria-hidden="true"></i> Penyimpanan web</h2>
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                    Gambar dan bukti <strong class="text-gray-900 dark:text-white"><?= angka(mb($simp['gambar']), 2) ?> MB</strong> ·
                    sistem <?= angka(mb($simp['sistem']), 2) ?> MB · kode <?= angka(mb($simp['kode']), 2) ?> MB ·
                    total <strong class="text-gray-900 dark:text-white"><?= angka(mb($simp['total']), 2) ?> MB</strong>
                </p>
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Gambar dan bukti per jenis</h3>
                <div id="grafik-simpan" class="grafik"></div>
                <div class="tabel-gulir">
                    <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                        <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-400">
                            <tr><th scope="col" class="px-3 py-2">Isi</th><th scope="col" class="px-3 py-2 text-end">Berkas</th><th scope="col" class="px-3 py-2 text-end">Ukuran</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ([...$simp['daftar'], ...($simp['cadangan'] ? [$simp['cadangan']] : [])] as $p) : ?>
                                <tr class="border-b border-gray-100 dark:border-gray-700">
                                    <td class="px-3 py-2"><span class="font-medium text-gray-900 dark:text-white"><?= e($p['label']) ?></span><br><span class="text-xs"><?= e($p['jalur']) ?></span></td>
                                    <td class="px-3 py-2 text-end"><?= $p['ada'] ? angka($p['berkas']) : '–' ?></td>
                                    <td class="whitespace-nowrap px-3 py-2 text-end font-medium text-gray-900 dark:text-white"><?= $p['ada'] ? angka(mb($p['bita']), 2) . ' MB' : 'belum ada' ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Basis data -->
            <section class="rounded-lg bg-white dark:bg-gray-800 p-4 shadow-sm md:p-6" aria-labelledby="judul-db">
                <h2 id="judul-db" class="mb-1 flex items-center gap-2 text-xl font-bold text-gray-900 dark:text-white"><i class="bi bi-database text-primary-700 dark:text-primary-500" aria-hidden="true"></i> Basis data</h2>
                <?php if (! $db['ada']) : ?>
                    <p class="mt-3 rounded-lg bg-red-100 dark:bg-red-900 p-4 text-sm text-red-800 dark:text-red-300">Basis data tidak tersambung. Periksa MySQL di Laragon dan isian <code>database.default.*</code> di <code>.env</code>.</p>
                <?php else : ?>
                    <p class="mb-3 text-sm text-gray-500 dark:text-gray-400"><?= e($db['jenis']) ?> <?= e($db['versi']) ?> · <?= e($db['nama']) ?> di <?= e($db['host']) ?></p>
                    <dl class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                        <?php foreach ([
                            ['Ukuran', angka(mb($db['ukuran']), 2) . ' MB'],
                            ['Data / indeks', angka(mb($db['data']), 1) . ' / ' . angka(mb($db['indeks']), 1) . ' MB'],
                            ['Sambungan', angka($db['status']['Threads_connected'] ?? 0) . ' / ' . angka($db['maks_sambungan'])],
                            ['Menyala', isset($db['status']['Uptime']) ? lamaWaktu($db['status']['Uptime']) : '–'],
                            ['Kueri / detik', isset($db['status']['Uptime']) && $db['status']['Uptime'] > 0 ? angka(($db['status']['Questions'] ?? 0) / $db['status']['Uptime'], 2) : '–'],
                            ['Kueri lambat', angka($db['status']['Slow_queries'] ?? 0)],
                            ['Puncak sambungan', angka($db['status']['Max_used_connections'] ?? 0)],
                            ['Buffer InnoDB', ukuran($db['buffer'])],
                        ] as [$l, $v]) : ?>
                            <div class="rounded-lg bg-gray-50 dark:bg-gray-700 p-3">
                                <dt class="text-xs text-gray-500 dark:text-gray-400"><?= e($l) ?></dt>
                                <dd class="text-sm font-bold text-gray-900 dark:text-white"><?= e($v) ?></dd>
                            </div>
                        <?php endforeach ?>
                    </dl>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Isi data</h3>
                    <div class="mb-4 mt-2 flex flex-wrap gap-2">
                        <?php foreach ($db['jumlah'] as $l => $n) : ?>
                            <span class="rounded-md bg-primary-100 dark:bg-primary-900 px-2.5 py-0.5 text-xs font-medium text-primary-800 dark:text-primary-300"><?= e($l) ?>: <?= $n === null ? '–' : angka($n) ?></span>
                        <?php endforeach ?>
                    </div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tabel terbesar</h3>
                    <div id="grafik-tabel" class="grafik"></div>
                    <details class="mt-2">
                        <summary class="cursor-pointer text-sm font-medium text-primary-700 dark:text-primary-500">Semua tabel (<?= count($db['tabel']) ?>)</summary>
                        <div class="tabel-gulir mt-2">
                            <table class="w-full text-left text-sm text-gray-500 dark:text-gray-400">
                                <thead class="bg-gray-50 dark:bg-gray-700 text-xs uppercase text-gray-700 dark:text-gray-400">
                                    <tr><th scope="col" class="px-3 py-2">Tabel</th><th scope="col" class="px-3 py-2 text-end">Baris (kira-kira)</th><th scope="col" class="px-3 py-2 text-end">Ukuran</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($db['tabel'] as $t) : ?>
                                        <tr class="border-b border-gray-100 dark:border-gray-700">
                                            <td class="px-3 py-2 font-medium text-gray-900 dark:text-white"><?= e($t['nama']) ?></td>
                                            <td class="px-3 py-2 text-end"><?= angka((int) $t['baris']) ?></td>
                                            <td class="whitespace-nowrap px-3 py-2 text-end"><?= e(ukuran((int) $t['data'] + (int) $t['indeks'])) ?></td>
                                        </tr>
                                    <?php endforeach ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endif ?>
            </section>
        </div>

        <p class="py-2 text-center text-xs text-gray-500 dark:text-gray-400">Halaman ini tidak tercatat sebagai pengunjung. Keluar otomatis setelah <?= ARUMI_LAMA_DIAM / 60 ?> menit tanpa kegiatan.</p>
    </main>

    <script type="application/json" id="data-arumi"><?= json_encode($jsonGrafik, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
    <script src="aset/vendor/apexcharts/apexcharts.min.js"></script>
    <script>
    (function () {
        'use strict';
        if (typeof window.ApexCharts !== 'function') { return; }
        var data = JSON.parse(document.getElementById('data-arumi').textContent);
        var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        var angka = function (n) { return Number(n || 0).toLocaleString('id-ID'); };
        var mb = function (n) { return Number(n || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 }) + ' MB'; };
        var pendek = function (t) { var b = t.split('-'); return parseInt(b[2], 10) + ' ' + BULAN[parseInt(b[1], 10) - 1]; };
        var dasar = {
            chart: { fontFamily: 'Inter, sans-serif', foreColor: '#9CA3AF', background: 'transparent', toolbar: { show: false }, zoom: { enabled: false } },
            theme: { mode: 'dark' },
            tooltip: { theme: 'dark' },
            dataLabels: { enabled: false },
            grid: { borderColor: '#374151', strokeDashArray: 4 },
            legend: { position: 'top', horizontalAlign: 'left', fontSize: '13px', markers: { shape: 'circle', size: 6 } },
            noData: { text: 'Belum ada data' }
        };
        var gambar = function (id, opsi) {
            var el = document.getElementById(id);
            if (!el) { return; }
            var g = new window.ApexCharts(el, Object.assign({}, dasar, opsi, {
                chart: Object.assign({}, dasar.chart, opsi.chart),
                tooltip: Object.assign({ theme: 'dark' }, opsi.tooltip || {})
            }));
            g.render();
        };

        if (data.jam.length) {
            gambar('grafik-jam', {
                chart: { type: 'bar', height: 280 },
                series: [
                    { name: 'Pengunjung', data: data.jam.map(function (j) { return j.unik; }), color: '#1A56DB' },
                    { name: 'Halaman dibuka', data: data.jam.map(function (j) { return j.kunjungan; }), color: '#FF8A4C' }
                ],
                plotOptions: { bar: { columnWidth: '70%', borderRadius: 3, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.jam.map(function (j) { return (j.jam < 10 ? '0' : '') + j.jam; }), axisBorder: { show: false }, axisTicks: { show: false }, labels: { rotate: 0, hideOverlappingLabels: true } },
                yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return angka(Math.round(v)); } } },
                tooltip: { shared: true, intersect: false, x: { formatter: function (v) { return 'Pukul ' + v + '.00–' + v + '.59'; } }, y: { formatter: angka } },
                grid: dasar.grid
            });
        }

        if (data.hari.length) {
            gambar('grafik-hari', {
                chart: {
                    type: 'area', height: 280,
                    events: {
                        dataPointSelection: function (e, c, o) { pilih(o.dataPointIndex); },
                        markerClick: function (e, c, o) { pilih(o.dataPointIndex); }
                    }
                },
                series: [
                    { name: 'Pengunjung', data: data.hari.map(function (h) { return h.unik; }), color: '#1A56DB' },
                    { name: 'Halaman dibuka', data: data.hari.map(function (h) { return h.kunjungan; }), color: '#7E3AF2' }
                ],
                fill: { type: 'gradient', gradient: { opacityFrom: 0.35, opacityTo: 0 } },
                stroke: { curve: 'smooth', width: 3 },
                markers: { size: 4, strokeColors: '#1F2937', hover: { sizeOffset: 3 } },
                xaxis: { categories: data.hari.map(function (h) { return pendek(h.tanggal); }), axisBorder: { show: false }, axisTicks: { show: false }, tickAmount: 10, labels: { rotate: 0, hideOverlappingLabels: true } },
                yaxis: { min: 0, forceNiceScale: true, labels: { formatter: function (v) { return angka(Math.round(v)); } } },
                tooltip: { shared: true, intersect: false, y: { formatter: angka } },
                annotations: { xaxis: [{ x: pendek(data.tanggal), borderColor: '#E85F31', label: { text: 'Dipilih', style: { color: '#fff', background: '#E85F31' } } }] }
            });
        }

        function pilih(i) {
            var h = data.hari[i];
            if (h && h.tanggal !== data.tanggal) {
                window.location.href = window.location.pathname + '?tanggal=' + encodeURIComponent(h.tanggal) + '#pengunjung';
            }
        }

        if (data.simpan.length) {
            gambar('grafik-simpan', {
                chart: { type: 'donut', height: 300 },
                series: data.simpan.map(function (s) { return s.mb; }),
                labels: data.simpan.map(function (s) { return s.label; }),
                colors: ['#1A56DB', '#FF8A4C', '#16BDCA', '#7E3AF2', '#E74694', '#0E9F6E', '#9CA3AF', '#F05252', '#FACA15', '#31C48D', '#6B7280'],
                stroke: { colors: ['#1F2937'], width: 2 },
                legend: { position: 'bottom', fontSize: '12px' },
                plotOptions: { pie: { donut: { size: '68%', labels: { show: true, value: { formatter: function (v) { return mb(v); } }, total: { show: true, label: 'Gambar dan bukti', formatter: function (w) { return mb(w.globals.seriesTotals.reduce(function (a, b) { return a + b; }, 0)); } } } } } },
                tooltip: { y: { formatter: mb } }
            });
        }

        if (data.tabel.length) {
            gambar('grafik-tabel', {
                chart: { type: 'bar', height: Math.max(200, data.tabel.length * 30 + 40) },
                series: [{ name: 'Ukuran', data: data.tabel.map(function (t) { return t.mb; }) }],
                colors: ['#7E3AF2'],
                plotOptions: { bar: { horizontal: true, barHeight: '65%', borderRadius: 4, borderRadiusApplication: 'end' } },
                xaxis: { categories: data.tabel.map(function (t) { return t.nama; }), labels: { formatter: function (v) { return Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 }); } } },
                tooltip: { y: { formatter: mb } },
                grid: dasar.grid
            });
        }
    }());
    </script>
<?php endif ?>
</body>
</html>
