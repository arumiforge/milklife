<?php
/**
 * Tata letak halaman publik, dirancang dari ponsel lebih dulu.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `isi`: isi halaman, berada di dalam `<main id="isi">`. Halaman menulis
 *   `<h1>`-nya sendiri; kepala dan kaki tidak memakai heading.
 * - `kepala`: tag tambahan di `<head>` (misalnya `<link>` CSS atau `<meta>` halaman itu).
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap.
 *
 * Menu publik ditulis di `$menuPublik` di bawah. Butir tanpa `alamat` tampil
 * non-aktif (`aria-disabled`, tanpa tautan) dengan `keterangan`-nya, atau
 * "Segera" bila tanpa keterangan. Alamat Pendaftaran dan Cek Pendaftaran
 * diambil dari `IsiHalamanDepan` (satu tempat dengan tombol di halaman
 * depan). Pendaftaran hanya menaut saat pendaftaran terbuka (rancangan 6.1);
 * selain itu keterangannya `IsiHalamanDepan::keteranganPendaftaran()`.
 *
 * Pesan kilat `pesan_sukses` dan `pesan_galat` (misalnya dari filter batas
 * unggah) tampil di atas isi halaman (`tata_letak/_pesan_publik`).
 *
 * Nama acara di kepala diambil dari pengaturan `Acara.nama`
 * (`tata_letak/_merek`). Teks kaki dari `IsiHalamanDepan::kaki()`:
 * `HalamanDepan.footer` (baris baru menjadi `<br>`), atau "nama acara tahun"
 * bila kosong.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\IsiHalamanDepan;

$bukaTutup = (new IsiHalamanDepan())->bukaTutup();

$menuPublik = [
    ['label' => 'Beranda', 'alamat' => '/'],
    ['label' => 'Pendaftaran', 'alamat' => $bukaTutup->terbuka() ? IsiHalamanDepan::ALAMAT_PENDAFTARAN : null, 'keterangan' => IsiHalamanDepan::keteranganPendaftaran($bukaTutup)],
    ['label' => 'Cek Pendaftaran', 'alamat' => IsiHalamanDepan::ALAMAT_CEK_PENDAFTARAN],
    ['label' => 'Unduhan', 'alamat' => 'unduhan'],
];

$kaki = IsiHalamanDepan::kaki();
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
    <?= $this->renderSection('kepala') ?>
</head>
<body class="tata-publik" data-bs-theme="light">
    <a class="lompat-ke-isi visually-hidden-focusable" href="#isi">Lompat ke isi</a>

    <header class="kepala-publik">
        <nav class="navbar navbar-expand-md navbar-dark" aria-label="Menu utama">
            <div class="container">
                <a class="navbar-brand merek py-2" href="<?= site_url('/') ?>">
                    <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
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
                                        <span class="menu-segera"><?= esc($butir['keterangan'] ?? 'Segera') ?></span>
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
        <?= view('tata_letak/_pesan_publik', [], ['saveData' => false]) ?>
        <?= $this->renderSection('isi') ?>
    </main>

    <footer class="kaki-publik">
        <div class="container py-4">
            <p class="mb-0"><?= nl2br(esc($kaki), false) ?></p>
        </div>
    </footer>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
