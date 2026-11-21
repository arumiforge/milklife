<?php
/**
 * Ikon peramban dan layar utama (F9-19, G9-11), dipakai semua tata letak di
 * `<head>`. `ikon.svg` selalu ada; ikon PNG dan `apple-touch-icon` hanya
 * ditulis bila berkasnya di `public/aset/gambar/ikon-aplikasi/` sudah ada,
 * dan `manifest.webmanifest` hanya bila semua ikon yang disebutnya ada,
 * supaya peramban tidak meminta berkas yang belum ada (404).
 *
 *     <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
 */

use App\Libraries\GambarOpsional;

$folderIkon = 'ikon-aplikasi/';
?>
<link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
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
