<?php
/**
 * Tata letak panel admin: lebar penuh, tanpa teks footer.
 *
 * - Layar ≥ lg: menu samping tetap di kiri, kepala halaman menempel di atas.
 * - Ponsel dan tablet: menu samping menjadi sidebar (Bootstrap
 *   `offcanvas-lg`) yang dibuka tombol menu di kepala halaman, berisi semua
 *   butir (K131, K133). Menu yang menempel di bawah hanya 5 butir urusan
 *   pendaftaran (K132, K133). Tombol Keluar tetap di kepala halaman.
 * Kedua menu memakai daftar yang sama: `tata_letak/_menu_admin.php`.
 * Pengecekan dan Halaman depan (K102) menempel di bagian bawah menu samping;
 * di ponsel dan tablet keduanya juga ikon di kepala halaman.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman, cukup nama halamannya (misalnya `Dasbor`). Tab
 *   peramban menampilkan `<judul> – Admin <nama acara>`; nama acara dari
 *   `AcaraController::namaTampil()` ditambahkan di sini, jadi halaman tidak
 *   menulisnya sendiri. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `judul_halaman` (pilihan): judul pendek di kepala halaman, tampil sebagai
 *   `<h1>`. Bila kosong, isi `judul` yang dipakai. Isi halaman dimulai dari `<h2>`.
 * - `jejak` (pilihan): jejak navigasi halaman turunan dari `admin/_jejak`
 *   (K117), tampil di atas pesan kilat dan isi.
 * - `isi`: isi halaman, di dalam `<main id="isi">`; bungkus dengan `container-fluid`.
 * - `kepala` (pilihan): tag tambahan di `<head>`, misalnya gaya khusus halaman.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap dan `aset/js/kemajuan.js`.
 *
 * Nama acara di menu samping dari pengaturan `Acara.nama` (`tata_letak/_merek`).
 *
 * Nama admin dibaca dari sesi `admin_nama` dan menjadi tautan ke halaman
 * Akun (`/admin/akun`). Tombol Keluar mengirim `POST` ke `/admin/keluar`
 * dengan token CSRF (rutenya dibuat F1-04).
 *
 * Pesan kilat `pesan_sukses` dan `pesan_galat` ditampilkan di atas isi lewat
 * `admin/_pesan.php`; halaman tidak menampilkannya sendiri.
 *
 * Bilah kemajuan di atas halaman dinyalakan dari JavaScript halaman:
 * `Kemajuan.mulai()` lalu `Kemajuan.selesai()`.
 *
 * - `tabel` (pilihan, Tahap 6): halaman dengan tabel data mengisi bagian ini
 *   dengan teks apa pun (`<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>`).
 *   Gaya DataTables lalu dimuat di kepala, dan jQuery, DataTables, serta
 *   `aset/js/tabel-admin.js` dimuat sebelum `skrip`. Halaman lain tidak
 *   memuatnya.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;

// Dibaca sekali lalu dipakai di <title> dan di kepala halaman.
$judul        = trim($this->renderSection('judul'));
$judulHalaman = trim($this->renderSection('judul_halaman'));
if ($judulHalaman === '') {
    $judulHalaman = $judul;
}

$judulTab = ($judul === '' ? '' : $judul . ' – ') . 'Admin ' . esc(AcaraController::namaTampil());

$pakaiTabel = trim($this->renderSection('tabel')) !== '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $judulTab ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <?php if ($pakaiTabel) : ?>
    <link rel="stylesheet" href="<?= aset('vendor/datatables/css/dataTables.bootstrap5.min.css') ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/admin.css') ?>">
    <?= $this->renderSection('kepala') ?>
</head>
<body class="tata-admin" data-bs-theme="light">
    <div id="bilah-kemajuan" class="bilah-kemajuan" hidden aria-hidden="true">
        <div class="bilah-kemajuan-isi"></div>
    </div>

    <a class="lompat-ke-isi visually-hidden-focusable" href="#isi">Lompat ke isi</a>

    <div class="admin-bingkai">
        <?php /* Satu elemen untuk dua bentuk: menu samping di layar lebar, sidebar di ponsel (K131). */ ?>
        <aside id="menu-laci" class="menu-samping offcanvas-lg offcanvas-start d-lg-flex" tabindex="-1" aria-label="Menu panel admin">
            <div class="offcanvas-header menu-laci-kepala">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#menu-laci" aria-label="Tutup menu"></button>
            </div>
            <a class="menu-samping-merek merek" href="<?= site_url('admin/dasbor') ?>">
                <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
                <span class="menu-samping-peran">Panel admin</span>
            </a>
            <nav class="menu-samping-nav" aria-label="Menu admin">
                <?= view('tata_letak/_menu_admin', ['tempatMenu' => 'samping'], ['saveData' => false]) ?>
            </nav>
        </aside>

        <div class="admin-utama">
            <header class="kepala-admin">
                <button type="button" class="btn btn-outline-primary tombol-menu d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#menu-laci" aria-controls="menu-laci" title="Buka menu">
                    <i class="bi bi-list" aria-hidden="true"></i>
                    <span class="visually-hidden">Buka menu</span>
                </button>
                <h1 class="kepala-admin-judul"><?= $judulHalaman ?></h1>
                <div class="kepala-admin-pengguna">
                    <?php /* Di layar lebar Pengecekan dan Halaman depan ada di bagian bawah menu samping (K102). */ ?>
                    <a class="btn btn-outline-primary tombol-pengecekan d-lg-none" href="<?= site_url('admin/pengecekan') ?>" title="Pengecekan: keadaan pendaftaran dan kelengkapan pengaturan"<?= url_is('admin/pengecekan') ? ' aria-current="page"' : '' ?>>
                        <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                        <span class="d-none d-md-inline tombol-kepala-teks">Pengecekan</span>
                    </a>
                    <a class="btn btn-outline-primary tombol-halaman-depan d-lg-none" href="<?= site_url('/') ?>" target="_blank" rel="noopener" title="Buka halaman depan di tab baru">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span class="d-none d-md-inline tombol-kepala-teks">Halaman depan</span>
                        <span class="visually-hidden">(tab baru)</span>
                    </a>
                    <a class="kepala-admin-nama" href="<?= site_url('admin/akun') ?>" title="Buka Akun: ganti nama dan kata sandi">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span class="visually-hidden">Masuk sebagai</span>
                        <span class="kepala-admin-nama-teks"><?= esc((string) session('admin_nama')) ?></span>
                        <span class="visually-hidden">(buka Akun)</span>
                    </a>
                    <form action="<?= site_url('admin/keluar') ?>" method="post" class="m-0">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn btn-outline-primary tombol-keluar">
                            <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                            <span class="tombol-kepala-teks">Keluar</span>
                        </button>
                    </form>
                </div>
            </header>

            <main id="isi" class="admin-isi">
                <?php /* Jejak navigasi halaman turunan (K117), sebelum pesan kilat. */ ?>
                <?php $jejak = trim($this->renderSection('jejak')); ?>
                <?php if ($jejak !== '') : ?><div class="container-fluid px-3 px-lg-4"><?= $jejak ?></div><?php endif ?>
                <?= $this->include('admin/_pesan') ?>
                <?= $this->renderSection('isi') ?>
            </main>
        </div>
    </div>

    <nav class="menu-bawah d-lg-none" aria-label="Menu pendaftaran">
        <?= $this->include('tata_letak/_menu_admin') ?>
    </nav>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
    <?php if ($pakaiTabel) : ?>
    <script src="<?= aset('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= aset('vendor/datatables/js/dataTables.min.js') ?>"></script>
    <script src="<?= aset('vendor/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= aset('js/tabel-admin.js') ?>"></script>
    <?php endif; ?>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
