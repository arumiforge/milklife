<?php
/**
 * Ikon peramban dan layar utama (F9-19), dipakai semua tata letak di `<head>`.
 * Sejak K152 ikonnya dibuat dari logo resmi MilkLife Athletics Challenge
 * (`sumber/logo/`). Ikon PNG dan `apple-touch-icon` hanya ditulis bila
 * berkasnya di `public/aset/gambar/ikon-aplikasi/` ada; `ikon.svg` hanya
 * cadangan bila favicon PNG belum ada,
 * dan `manifest.webmanifest` hanya bila semua ikon yang disebutnya ada,
 * supaya peramban tidak meminta berkas yang belum ada (404).
 *
 *     <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
 */

use App\Libraries\GambarOpsional;

$folderIkon = 'ikon-aplikasi/';
?>
<?php if (! GambarOpsional::ada($folderIkon . 'favicon-32.png')) : ?>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
<?php endif ?>
<?php foreach (['favicon-32.png' => '32x32', 'favicon-16.png' => '16x16'] as $berkas => $ukuran) : ?>
    <?php if (($alamat = GambarOpsional::alamat($folderIkon . $berkas)) !== null) : ?>
        <link rel="icon" href="<?= esc($alamat) ?>" type="image/png" sizes="<?= $ukuran ?>">
    <?php endif ?>
<?php endforeach ?>
<?php if (($alamat = GambarOpsional::alamat($folderIkon . 'apple-touch-180.png')) !== null) : ?>
    <link rel="apple-touch-icon" href="<?= esc($alamat) ?>" sizes="180x180">
<?php endif ?>
<?php if (GambarOpsional::ada($folderIkon . 'ikon-192.png') && GambarOpsional::ada($folderIkon . 'ikon-512.png') && GambarOpsional::ada($folderIkon . 'ikon-maskable-512.png') && is_file(FCPATH . 'manifest.webmanifest')) : ?>
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
<?php endif ?>
