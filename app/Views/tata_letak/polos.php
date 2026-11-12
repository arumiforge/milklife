<?php
/**
 * Tata letak polos untuk halaman masuk admin: latar biru, nama acara di atas
 * (dari pengaturan `Acara.nama`, lewat `tata_letak/_merek`), dan satu kartu
 * putih di tengah (lebar paling besar 420 px).
 *
 * Halaman galat (`errors/html/*`) tidak memakai berkas ini karena bisa
 * dirender saat sistem bermasalah, tetapi tampil selaras dengannya.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman, cukup nama halamannya (misalnya `Masuk Admin`). Tab
 *   peramban menampilkan `<judul> – <nama acara>`; nama acara dari
 *   `AcaraController::namaTampil()` ditambahkan di sini. Isi yang berasal dari
 *   data wajib di-escape (`esc()`).
 * - `isi`: isi kartu. Kartu dan jaraknya sudah disediakan (`.card.polos-kartu > .card-body`),
 *   jadi isi cukup heading (`<h1 class="h4">`), pesan, dan formulir Bootstrap biasa,
 *   tanpa `.card` atau `.container` lagi.
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;

$judul     = trim($this->renderSection('judul'));
$namaAcara = esc(AcaraController::namaTampil());
$judulTab  = $judul === '' ? $namaAcara : $judul . ' – ' . $namaAcara;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $judulTab ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
</head>
<body class="tata-polos" data-bs-theme="light">
    <main id="isi" class="polos-bingkai">
        <div class="polos-kolom">
            <p class="polos-merek merek">
                <a href="<?= site_url('/') ?>">
                    <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
                </a>
            </p>
            <div class="card polos-kartu">
                <div class="card-body">
                    <?= $this->renderSection('isi') ?>
                </div>
            </div>
        </div>
    </main>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
