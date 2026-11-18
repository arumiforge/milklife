<?php
/**
 * Halaman pilihan untuk admin yang sedang masuk dan membuka alamat kode QR
 * bukti pendaftaran (F8-03, asumsi 3 K125), misalnya dengan aplikasi kamera
 * ponsel saat daftar ulang. Pengunjung lain langsung mendapat PDF-nya.
 *
 * @var string $idPendaftaran
 * @var string $namaSekolah
 * @var string $alamatDaftarUlang
 * @var string $alamatPdf alamat bukti dengan `?pdf=1`
 */
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Bukti <?= esc($idPendaftaran) ?></title>
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
</head>
<body class="bg-body-tertiary">
<main class="container py-5 maks-w-32r">
    <h1 class="h4 mb-1"><?= esc($namaSekolah) ?></h1>
    <p class="text-body-secondary mb-4"><?= esc($idPendaftaran) ?></p>
    <p>Anda sedang masuk sebagai admin. Pilih yang ingin dibuka:</p>
    <a class="btn btn-primary btn-lg w-100 mb-3" id="tautan-daftar-ulang" href="<?= esc($alamatDaftarUlang, 'attr') ?>">
        <i class="bi bi-qr-code-scan" aria-hidden="true"></i> Buka daftar ulang
    </a>
    <a class="btn btn-outline-primary w-100" id="tautan-bukti-pdf" href="<?= esc($alamatPdf, 'attr') ?>">
        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Buka bukti pendaftaran (PDF)
    </a>
</main>
</body>
</html>
