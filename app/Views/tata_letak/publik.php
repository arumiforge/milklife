<?php
/**
 * Tata letak halaman publik, dirancang dari ponsel lebih dulu.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `isi`: isi halaman, berada di dalam `<main id="isi">`. Halaman menulis
 *   `<h1>`-nya sendiri; kepala dan kaki tidak memakai heading.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap.
 *
 * Menu publik ditulis di `$menuPublik` di bawah. Butir tanpa `alamat` belum
 * punya halaman: tampil non-aktif (`aria-disabled`, tanpa tautan) dengan
 * keterangan "Segera". Saat halamannya dibuat, isi `alamat`-nya.
 *
 * @var CodeIgniter\View\View $this
 */

$menuPublik = [
    ['label' => 'Beranda', 'alamat' => '/'],
    ['label' => 'Pendaftaran', 'alamat' => null],
    ['label' => 'Cek Pendaftaran', 'alamat' => null],
    ['label' => 'Unduhan', 'alamat' => null],
];

// Nama acara dan tahun ditulis langsung dulu; Tahap 3 menggantinya dengan pengaturan admin.
$namaAcaraUtama     = 'MilkLife';
$namaAcaraSambungan = 'Athletics Challenge';
$tahunAcara         = '2026';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $this->renderSection('judul') ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
</head>
<body class="tata-publik" data-bs-theme="light">
    <a class="lompat-ke-isi visually-hidden-focusable" href="#isi">Lompat ke isi</a>

    <header class="kepala-publik">
        <nav class="navbar navbar-expand-md navbar-dark" aria-label="Menu utama">
            <div class="container">
                <a class="navbar-brand merek py-2" href="<?= site_url('/') ?>">
                    <span class="merek-utama"><?= esc($namaAcaraUtama) ?></span>
                    <span class="merek-sambungan"><?= esc($namaAcaraSambungan) ?></span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-publik" aria-controls="menu-publik" aria-expanded="false" aria-label="Buka atau tutup menu">
                    <span class="navbar-toggler-icon" aria-hidden="true"></span>
                </button>
                <div class="collapse navbar-collapse" id="menu-publik">
                    <ul class="navbar-nav ms-auto gap-md-1">
                        <?php foreach ($menuPublik as $butir) : ?>
                            <li class="nav-item">
                                <?php if ($butir['alamat'] === null) : ?>
                                    <a class="nav-link disabled" aria-disabled="true">
                                        <?= esc($butir['label']) ?>
                                        <span class="menu-segera">Segera</span>
                                    </a>
                                <?php elseif (url_is($butir['alamat'])) : ?>
                                    <a class="nav-link active" aria-current="page" href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?></a>
                                <?php else : ?>
                                    <a class="nav-link" href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>

    <main id="isi">
        <?= $this->renderSection('isi') ?>
    </main>

    <footer class="kaki-publik">
        <div class="container py-4">
            <p class="mb-0"><?= esc($namaAcaraUtama . ' ' . $namaAcaraSambungan) ?> <?= esc($tahunAcara) ?></p>
        </div>
    </footer>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
