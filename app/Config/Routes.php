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

// ---------- Hasil lomba publik (F8-04, K124) ----------
// Hanya nomor yang hasilnya sudah diterbitkan admin.
$routes->get('hasil', 'HasilController::index');
$routes->get('hasil/(:num)', 'HasilController::nomor/$1');
$routes->get('hasil/(:num)/(pdf|excel)', 'HasilController::unduh/$1/$2');
// Klasemen sekolah per jenjang dari nomor yang terbit (F8-05).
$routes->get('hasil/klasemen', 'HasilController::klasemen');
$routes->get('hasil/klasemen/(pdf|excel)', 'HasilController::unduhKlasemen/$1');
// ---------- Akhir hasil lomba publik (F8-04, F8-05) ----------

// ---------- Bantuan dan FAQ (F9-04) ----------
$routes->get('bantuan', 'BantuanController::index');
// ---------- Akhir Bantuan dan FAQ ----------

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
    // Pengecekan (K102): keadaan pendaftaran, kelengkapan pengaturan, gambar kepala.
    $routes->get('pengecekan', 'PengecekanController::index');
    // Katalog komponen tampilan baru (Tahap 9, F9-01): untuk menilai gaya di staging.
    $routes->get('gaya', 'GayaController::index');

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
    // Tolak atlet dari pendaftaran Menunggu; NISN-nya bisa didaftarkan lagi (K116).
    $routes->get('pendaftaran/(:num)/tolak-atlet', 'TolakAtletController::index/$1');
    $routes->post('pendaftaran/(:num)/tolak-atlet', 'TolakAtletController::tolak/$1');
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
    // Atlet yang ditolak panitia, semua pendaftaran (K117).
    $routes->get('atlet-ditolak', 'AtletDitolakController::index');
    $routes->get('atlet-ditolak/data', 'AtletDitolakController::data');
    $routes->get('atlet-ditolak/excel', 'AtletDitolakController::excel');
    // Urutkan ulang nomor dada per sekolah (K111).
    $routes->get('atlet/nomor-dada', 'NomorDadaController::index');
    $routes->post('atlet/nomor-dada', 'NomorDadaController::urutkan');

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

    // ---------- F8-01/F8-02: riwayat tindakan admin dan bukti bayar baru ----------
    // Cari riwayat menurut ID pendaftaran, termasuk yang sudah dihapus (F8-01).
    $routes->get('riwayat', 'PendaftaranController::riwayat');
    // Tombol "Sudah diperiksa" untuk bukti bayar baru dari ofisial (F8-02).
    $routes->post('pendaftaran/(:num)/bukti-bayar/diperiksa', 'BuktiBayarController::diperiksa/$1');
    // ---------- Akhir F8-01/F8-02 ----------

    // Data sekolah (K97): lihat dan sunting nama supaya sekolah bernama umum bisa dibedakan.
    $routes->get('sekolah', 'SekolahController::index');
    $routes->get('sekolah/data', 'SekolahController::data');
    $routes->get('sekolah/excel', 'SekolahController::excel');
    // Beri nama desa sekaligus ke sekolah bernama kembar (K114).
    $routes->get('sekolah/nama-desa', 'SekolahController::namaDesa');
    $routes->post('sekolah/nama-desa', 'SekolahController::terapkanNamaDesa');
    $routes->get('sekolah/(:num)/ubah', 'SekolahController::ubah/$1');
    $routes->post('sekolah/(:num)', 'SekolahController::perbarui/$1');
    // Usulan nama sekolah dari pendaftar (K99).
    $routes->get('sekolah/usulan', 'SekolahController::usulan');
    $routes->post('sekolah/usulan/(:num)/setujui', 'SekolahController::setujuiUsulan/$1');
    $routes->post('sekolah/usulan/(:num)/tolak', 'SekolahController::tolakUsulan/$1');

    // ---------- Hari lomba (F8-03, K124) ----------
    // Daftar ulang lewat kode QR bukti dan tanda hadir per atlet.
    $routes->get('daftar-ulang', 'DaftarUlangController::index');
    $routes->post('daftar-ulang/(:num)/hadir', 'DaftarUlangController::hadir/$1');
    $routes->post('daftar-ulang/(:num)/batal-hadir', 'DaftarUlangController::batalHadir/$1');
    // Cetak nomor atlet (BIB) massal: `?ku=<id kelompok umur>` atau `?ku=semua`.
    $routes->get('nomor-atlet/cetak', 'CetakBibController::index');
    $routes->get('nomor-atlet/cetak/pdf', 'CetakBibController::pdf');
    // Tampilan BIB (latar, kepala, warna), gambar contoh, unduhan per sekolah dan ZIP semua (K137).
    $routes->post('nomor-atlet/cetak/tampilan', 'CetakBibController::simpanTampilan');
    $routes->get('nomor-atlet/cetak/contoh', 'CetakBibController::contoh');
    $routes->get('nomor-atlet/cetak/sekolah/(:segment)/(pdf|zip)', 'CetakBibController::sekolah/$1/$2');
    $routes->get('nomor-atlet/cetak/zip', 'CetakBibController::zip');
    // Daftar peserta per nomor lomba (PDF, Excel), juga semua nomor satu kelompok umur.
    $routes->get('daftar-peserta', 'DaftarPesertaController::index');
    $routes->get('daftar-peserta/(:num)/(pdf|excel)', 'DaftarPesertaController::unduh/$1/$2');
    $routes->get('daftar-peserta/kelompok-umur/(:num)/(pdf|excel)', 'DaftarPesertaController::unduhKelompokUmur/$1/$2');
    // ---------- Akhir hari lomba (F8-03) ----------

    // ---------- Hasil lomba (F8-04, K124) ----------
    // Nomor lomba per kelompok umur, babak per nomor, isian prestasi per babak.
    $routes->get('hasil', 'HasilController::index');
    $routes->get('hasil/nomor/(:num)', 'HasilController::nomor/$1');
    $routes->get('hasil/nomor/(:num)/(pdf|excel)', 'HasilController::unduh/$1/$2');
    $routes->post('hasil/nomor/(:num)/babak', 'HasilController::tambahBabak/$1');
    $routes->post('hasil/nomor/(:num)/terbitkan', 'HasilController::terbitkan/$1');
    $routes->post('hasil/nomor/(:num)/tarik', 'HasilController::tarik/$1');
    $routes->get('hasil/babak/(:num)', 'HasilController::babak/$1');
    $routes->post('hasil/babak/(:num)', 'HasilController::simpanHasil/$1');
    $routes->post('hasil/babak/(:num)/peserta', 'HasilController::peserta/$1');
    $routes->post('hasil/babak/(:num)/ubah', 'HasilController::ubahBabak/$1');
    $routes->post('hasil/babak/(:num)/hapus', 'HasilController::hapusBabak/$1');
    // ---------- Akhir hasil lomba (F8-04) ----------

    // Klasemen sekolah per jenjang, semua nomor termasuk yang belum terbit (F8-05).
    $routes->get('klasemen', 'KlasemenController::index');
    $routes->get('klasemen/(pdf|excel)', 'KlasemenController::unduh/$1');

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

        // Sakelar "Pendaftar memilih kelompok umur" di halaman Kelompok Umur (K134).
        $routes->post('kelompok-umur/pilihan', 'KelompokUmurController::pilihan');

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

        // ---------- Bantuan dan FAQ (F9-04) ----------
        $routes->get('bantuan', 'FaqController::index');
        $routes->get('bantuan/tambah', 'FaqController::tambah');
        $routes->post('bantuan', 'FaqController::buat');
        $routes->post('bantuan/bawaan', 'FaqController::bawaan');
        $routes->get('bantuan/(:num)/ubah', 'FaqController::ubah/$1');
        $routes->post('bantuan/(:num)', 'FaqController::perbarui/$1');
        $routes->post('bantuan/(:num)/hapus', 'FaqController::hapus/$1');
        $routes->post('bantuan/(:num)/aktif', 'FaqController::aktif/$1');
        $routes->post('bantuan/(:num)/(naik|turun)', 'FaqController::pindah/$1/$2');
        // ---------- Akhir Bantuan dan FAQ ----------

        // ---------- Tabel skor multi-lomba (F9-10) ----------
        // Daftar tabel, pasang tabel bawaan, tambah/hapus tabel; baris per komponen dan kategori.
        $routes->get('tabel-skor', 'TabelSkorController::index');
        $routes->post('tabel-skor', 'TabelSkorController::buat');
        $routes->post('tabel-skor/pasang', 'TabelSkorController::pasang');
        $routes->post('tabel-skor/(:num)/hapus', 'TabelSkorController::hapus/$1');
        $routes->get('tabel-skor/(:num)/([a-z0-9-]+)/(putra|putri)', 'TabelSkorController::baris/$1/$2/$3');
        $routes->post('tabel-skor/(:num)/([a-z0-9-]+)/(putra|putri)', 'TabelSkorController::simpanBaris/$1/$2/$3');
        $routes->post('tabel-skor/(:num)/([a-z0-9-]+)/(putra|putri)/hapus', 'TabelSkorController::hapusBaris/$1/$2/$3');
        $routes->post('tabel-skor/(:num)/([a-z0-9-]+)/(putra|putri)/impor', 'TabelSkorController::impor/$1/$2/$3');
        $routes->get('tabel-skor/(:num)/([a-z0-9-]+)/(putra|putri)/excel', 'TabelSkorController::excel/$1/$2/$3');
        // ---------- Akhir tabel skor (F9-10) ----------
    });
});
