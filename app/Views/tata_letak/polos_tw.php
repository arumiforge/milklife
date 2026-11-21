<?php
/**
 * Tata letak polos bertampilan baru (Tahap 9) untuk halaman masuk admin:
 * latar biru muda, nama acara di atas (pengaturan `Acara.nama`), dan satu
 * kartu putih di tengah (lebar paling besar 28rem). Padanan `polos.php`.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman; tab peramban menampilkan `<judul> – <nama acara>`.
 * - `isi`: isi kartu (judul `<h1>`, pesan, formulir), tanpa kartu sendiri.
 * - `skrip`: `<script src>` tambahan.
 *
 * Penanda uji: `data-polos-merek` (nama acara), `data-polos-kartu` (kartu).
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
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
</head>
<body class="min-h-screen bg-primary-50">
    <main id="isi" class="flex min-h-screen items-center justify-center px-4 py-10">
        <div class="w-full max-w-md">
            <p class="mb-6 text-center" data-polos-merek>
                <a href="<?= site_url('/') ?>" class="inline-flex items-center gap-2 text-xl font-extrabold text-primary-900 hover:text-primary-700">
                    <span class="inline-flex size-10 items-center justify-center rounded-xl bg-primary-700 text-white"><?= ikon('zap') ?></span>
                    <span><?= $namaAcara ?></span>
                </a>
            </p>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut sm:p-8" data-polos-kartu>
                <?= $this->renderSection('isi') ?>
            </div>
        </div>
    </main>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
