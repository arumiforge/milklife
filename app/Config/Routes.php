<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Rute aplikasi. Perutean otomatis dimatikan (`app/Config/Routing.php`),
 * jadi hanya rute yang ditulis di sini yang bisa dibuka.
 *
 * @var RouteCollection $routes
 */

// Publik
$routes->get('/', 'BerandaController::index');

// Semua berkas unduhan (K40). Pengendalinya dibuat F3-03; selama belum ada,
// alamatnya menampilkan halaman tidak ditemukan.
$routes->get('unduhan', 'UnduhanController::index');

// Cek pendaftaran dan bukti (Tahap 5). Blok milik sesi Tahap 5 (K48).
$routes->get('cek-pendaftaran', 'CekPendaftaranController::index');
// Bukti PDF: kode acak lalu ID pendaftaran (K77); alamat tanpa kode tidak ada lagi.
$routes->get('bukti/([0-9a-f]{24})/(:segment)', 'BuktiController::index/$1/$2');
// Official mengunggah bukti bayar sendiri (K83), dari wizard atau Cek Pendaftaran.
$routes->post('bukti/([0-9a-f]{24})/(:segment)/bukti-bayar', 'BuktiController::unggahBayar/$1/$2');
// Akhir blok Tahap 5

// ---------- Pendaftaran (Tahap 4) ----------
// Wizard pendaftaran dan titik akhir JSON-nya (rencana Tahap 4, "Rute dan
// pengendali"). Pengendali publik, bukan pengaturan admin yang namanya sama.
$routes->get('pendaftaran', 'PendaftaranController::index');
$routes->get('pendaftaran/sekolah', 'PendaftaranController::sekolah');
$routes->post('pendaftaran/periksa', 'PendaftaranController::periksa');
$routes->post('pendaftaran/simpan', 'PendaftaranController::simpan');
// ---------- Akhir pendaftaran ----------

// Admin. Semua rute dijaga filter `admin` (`App\Filters\AdminFilter`):
// yang belum masuk hanya bisa membuka halaman masuk.
$routes->get('admin', static fn () => redirect()->to('admin/dasbor'), ['filter' => 'admin']);
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'admin'], static function (RouteCollection $routes): void {
    $routes->get('masuk', 'MasukController::index');
    $routes->post('masuk', 'MasukController::masuk');
    $routes->post('keluar', 'MasukController::keluar');
    $routes->get('dasbor', 'DasborController::index');

    // Akun admin yang sedang masuk (F2-08): dua formulir di satu halaman.
    $routes->get('akun', 'AkunController::index');
    $routes->post('akun', 'AkunController::simpan');
    $routes->post('akun/sandi', 'AkunController::simpanSandi');

    // ---------- Panel data (Tahap 6) ----------
    // Semua rute Tahap 6 ditulis F6-01 (rencana Tahap 6, "Aturan untuk semua
    // sub-sesi" butir 3); pengendalinya dibuat tugas masing-masing. Selama
    // pengendalinya belum ada, alamatnya menampilkan halaman tidak ditemukan.
    // Tabel memakai pasangan `…/data` (JSON DataTables) dan `…/excel`
    // (saringan yang sama, `App\Libraries\Panel\TabelSisiServer`).

    // Pendaftaran (F6-02; ubah dan perbarui F6-04).
    $routes->get('pendaftaran', 'PendaftaranController::index');
    $routes->get('pendaftaran/data', 'PendaftaranController::data');
    $routes->get('pendaftaran/excel', 'PendaftaranController::excel');
    // Paket semua data pendaftaran untuk pindah server (K76); dimasukkan lewat `php spark pendaftaran:impor`.
    $routes->get('pendaftaran/ekspor', 'PendaftaranController::ekspor');
    // Tindakan untuk banyak pendaftaran sekaligus: lunas, menunggu, atau hapus (K76).
    $routes->post('pendaftaran/massal', 'PendaftaranController::massal');
    $routes->get('pendaftaran/(:num)', 'PendaftaranController::rincian/$1');
    // Foto penanggung jawab: id pendaftaran, id penanggung jawab.
    $routes->get('pendaftaran/(:num)/foto/(:num)', 'PendaftaranController::foto/$1/$2');
    $routes->post('pendaftaran/(:num)/lunas', 'PendaftaranController::lunas/$1');
    $routes->post('pendaftaran/(:num)/menunggu', 'PendaftaranController::menunggu/$1');
    $routes->post('pendaftaran/(:num)/hapus', 'PendaftaranController::hapus/$1');
    $routes->get('pendaftaran/(:num)/ubah', 'PendaftaranController::ubah/$1');
    $routes->post('pendaftaran/(:num)', 'PendaftaranController::perbarui/$1');

    // Bukti bayar (F6-03): id pendaftaran, id bukti bayar.
    $routes->post('pendaftaran/(:num)/bukti-bayar', 'BuktiBayarController::unggah/$1');
    $routes->get('pendaftaran/(:num)/bukti-bayar/(:num)', 'BuktiBayarController::lihat/$1/$2');
    $routes->get('pendaftaran/(:num)/bukti-bayar/(:num)/unduh', 'BuktiBayarController::unduh/$1/$2');
    $routes->post('pendaftaran/(:num)/bukti-bayar/(:num)/hapus', 'BuktiBayarController::hapus/$1/$2');

    // Atlet (F6-03).
    $routes->get('atlet', 'AtletController::index');
    $routes->get('atlet/data', 'AtletController::data');
    $routes->get('atlet/excel', 'AtletController::excel');

    // Rekap (F6-06). Jenis rekap: nomor-lomba, tim-beregu, sekolah,
    // kelompok-umur, keuangan; jenis lain tidak ditemukan. `rekap` tanpa
    // jenis membuka rekap pertama.
    $routes->get('rekap', 'RekapController::index');
    // Rekap gabungan (K85): semua data dalam satu Excel, satu sheet per bagian.
    $routes->get('rekap/gabungan/excel', 'RekapController::semua');
    $routes->get('rekap/(nomor-lomba|tim-beregu|sekolah|kelompok-umur|keuangan)', 'RekapController::index/$1');
    $routes->get('rekap/(nomor-lomba|tim-beregu|sekolah|kelompok-umur|keuangan)/data', 'RekapController::data/$1');
    $routes->get('rekap/(nomor-lomba|tim-beregu|sekolah|kelompok-umur|keuangan)/excel', 'RekapController::excel/$1');
    $routes->get('rekap/(nomor-lomba|tim-beregu|sekolah|kelompok-umur|keuangan)/pdf', 'RekapController::pdf/$1');
    // ---------- Akhir panel data (Tahap 6) ----------

    // Data sekolah (K97): lihat dan sunting nama supaya sekolah bernama umum bisa dibedakan.
    $routes->get('sekolah', 'SekolahController::index');
    $routes->get('sekolah/data', 'SekolahController::data');
    $routes->get('sekolah/excel', 'SekolahController::excel');
    $routes->get('sekolah/(:num)/ubah', 'SekolahController::ubah/$1');
    $routes->post('sekolah/(:num)', 'SekolahController::perbarui/$1');
    // Usulan nama sekolah dari pendaftar (K99).
    $routes->get('sekolah/usulan', 'SekolahController::usulan');
    $routes->post('sekolah/usulan/(:num)/setujui', 'SekolahController::setujuiUsulan/$1');
    $routes->post('sekolah/usulan/(:num)/tolak', 'SekolahController::tolakUsulan/$1');

    // Pengaturan (Tahap 2). Semua rute ditulis F2-01; pengendalinya dibuat
    // tugas masing-masing (rencana Tahap 2, "Rute"). Selama pengendalinya
    // belum ada, alamatnya menampilkan halaman tidak ditemukan.
    $routes->group('pengaturan', ['namespace' => 'App\Controllers\Admin\Pengaturan'], static function (RouteCollection $routes): void {
        $routes->get('/', 'BerandaPengaturanController::index');

        // Halaman satu formulir: tampilkan (`index`) dan simpan (`simpan`).
        foreach ([
            'acara'         => 'AcaraController',         // F2-02
            'pendaftaran'   => 'PendaftaranController',   // F2-02
            'pembayaran'    => 'PembayaranController',    // F2-05
            'halaman-depan' => 'HalamanDepanController',  // F2-05
        ] as $dasar => $pengendali) {
            $routes->get($dasar, $pengendali . '::index');
            $routes->post($dasar, $pengendali . '::simpan');
        }

        // Halaman daftar data: pola yang sama untuk semuanya.
        foreach ([
            'kelompok-umur' => 'KelompokUmurController',  // F2-03
            'nomor-lomba'   => 'NomorLombaController',    // F2-04
            'jadwal'        => 'JadwalController',        // F2-06
            'kontak'        => 'KontakController',        // F2-06
            'unduhan'       => 'UnduhanController',       // F2-07
        ] as $dasar => $pengendali) {
            $routes->get($dasar, $pengendali . '::index');
            $routes->get($dasar . '/tambah', $pengendali . '::tambah');
            $routes->post($dasar, $pengendali . '::buat');
            $routes->get($dasar . '/(:num)/ubah', $pengendali . '::ubah/$1');
            $routes->post($dasar . '/(:num)', $pengendali . '::perbarui/$1');
            $routes->post($dasar . '/(:num)/hapus', $pengendali . '::hapus/$1');
        }
    });
});
