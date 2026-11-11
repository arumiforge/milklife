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
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `judul_halaman` (pilihan): judul pendek di kepala halaman, tampil sebagai
 *   `<h1>`. Bila kosong, isi `judul` yang dipakai. Isi halaman dimulai dari `<h2>`.
 * - `isi`: isi halaman, di dalam `<main id="isi">`; bungkus dengan `container-fluid`.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap dan `aset/js/kemajuan.js`.
 *
 * Nama admin dibaca dari sesi `admin_nama`. Tombol Keluar mengirim `POST`
 * ke `/admin/keluar` dengan token CSRF (rutenya dibuat F1-04).
 *
 * Bilah kemajuan di atas halaman dinyalakan dari JavaScript halaman:
 * `Kemajuan.mulai()` lalu `Kemajuan.selesai()`.
 *
 * @var CodeIgniter\View\View $this
 */

// Dibaca sekali lalu dipakai di <title> dan di kepala halaman.
$judul        = $this->renderSection('judul');
$judulHalaman = trim($this->renderSection('judul_halaman'));
if ($judulHalaman === '') {
    $judulHalaman = $judul;
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $judul ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
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
                <span class="merek-utama">MilkLife</span>
                <span class="merek-sambungan">Athletics Challenge</span>
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
                    <span class="kepala-admin-nama">
                        <i class="bi bi-person-circle" aria-hidden="true"></i>
                        <span class="visually-hidden">Masuk sebagai</span>
                        <span class="kepala-admin-nama-teks"><?= esc((string) session('admin_nama')) ?></span>
                    </span>
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
                <?= $this->renderSection('isi') ?>
            </main>
        </div>
    </div>

    <nav class="menu-bawah d-lg-none" aria-label="Menu admin">
        <?= $this->include('tata_letak/_menu_admin') ?>
    </nav>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
