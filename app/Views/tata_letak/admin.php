<?php
/**
 * Tata letak panel admin: lebar penuh, tanpa teks footer.
 *
 * - Layar ≥ lg: menu samping tetap di kiri, kepala halaman menempel di atas.
 * - Ponsel dan tablet: menu samping hilang, diganti menu menempel di bawah
 *   (ikon + label). Tombol Keluar tetap di kepala halaman.
 * Kedua menu memakai daftar yang sama: `tata_letak/_menu_admin.php`.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman, cukup nama halamannya (misalnya `Dasbor`). Tab
 *   peramban menampilkan `<judul> – Admin <nama acara>`; nama acara dari
 *   `AcaraController::namaTampil()` ditambahkan di sini, jadi halaman tidak
 *   menulisnya sendiri. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `judul_halaman` (pilihan): judul pendek di kepala halaman, tampil sebagai
 *   `<h1>`. Bila kosong, isi `judul` yang dipakai. Isi halaman dimulai dari `<h2>`.
 * - `isi`: isi halaman, di dalam `<main id="isi">`; bungkus dengan `container-fluid`.
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
</head>
<body class="tata-admin" data-bs-theme="light">
    <div id="bilah-kemajuan" class="bilah-kemajuan" hidden aria-hidden="true">
        <div class="bilah-kemajuan-isi"></div>
    </div>

    <a class="lompat-ke-isi visually-hidden-focusable" href="#isi">Lompat ke isi</a>

    <div class="admin-bingkai">
        <aside class="menu-samping d-none d-lg-flex">
            <a class="menu-samping-merek merek" href="<?= site_url('admin/dasbor') ?>">
                <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
                <span class="menu-samping-peran">Panel admin</span>
            </a>
            <nav aria-label="Menu admin">
                <?= $this->include('tata_letak/_menu_admin') ?>
            </nav>
        </aside>

        <div class="admin-utama">
            <header class="kepala-admin">
                <h1 class="kepala-admin-judul"><?= $judulHalaman ?></h1>
                <div class="kepala-admin-pengguna">
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
                            Keluar
                        </button>
                    </form>
                </div>
            </header>

            <main id="isi" class="admin-isi">
                <?= $this->include('admin/_pesan') ?>
                <?= $this->renderSection('isi') ?>
            </main>
        </div>
    </div>

    <nav class="menu-bawah d-lg-none" aria-label="Menu admin">
        <?= $this->include('tata_letak/_menu_admin') ?>
    </nav>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <?php if ($pakaiTabel) : ?>
    <script src="<?= aset('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= aset('vendor/datatables/js/dataTables.min.js') ?>"></script>
    <script src="<?= aset('vendor/datatables/js/dataTables.bootstrap5.min.js') ?>"></script>
    <script src="<?= aset('js/tabel-admin.js') ?>"></script>
    <?php endif; ?>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
