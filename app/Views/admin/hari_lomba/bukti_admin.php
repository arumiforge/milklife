<?php
/**
 * Halaman pilihan untuk admin yang sedang masuk dan membuka alamat kode QR
 * bukti pendaftaran (F8-03, asumsi 3 K125), misalnya dengan aplikasi kamera
 * ponsel saat daftar ulang. Pengunjung lain langsung mendapat PDF-nya.
 * Tata letak `polos_tw` (Tahap 9, F9-17).
 *
 * @var CodeIgniter\View\View $this
 * @var string $idPendaftaran
 * @var string $namaSekolah
 * @var string $alamatDaftarUlang
 * @var string $alamatPdf alamat bukti dengan `?pdf=1`
 */
?>
<?= $this->extend('tata_letak/polos_tw') ?>

<?= $this->section('judul') ?>Bukti <?= esc($idPendaftaran) ?><?= $this->endSection() ?>

<?= $this->section('kepala') ?>
    <meta name="robots" content="noindex, nofollow">
<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<h1 class="text-2xl font-bold break-words text-gray-900"><?= esc($namaSekolah) ?></h1>
<p class="mt-1 mb-6 text-sm text-gray-600"><?= esc($idPendaftaran) ?></p>
<p class="mb-4 text-gray-700">Anda sedang masuk sebagai admin. Pilih yang ingin dibuka:</p>
<div class="space-y-3">
    <?= komponen('tombol', ['label' => 'Buka daftar ulang', 'href' => $alamatDaftarUlang, 'ikon' => 'scan-qr-code', 'ukuran' => 'besar', 'blok' => true, 'atribut' => ['id' => 'tautan-daftar-ulang']]) ?>
    <?= komponen('tombol', ['label' => 'Buka bukti pendaftaran (PDF)', 'href' => $alamatPdf, 'ikon' => 'file-text', 'varian' => 'kedua', 'blok' => true, 'atribut' => ['id' => 'tautan-bukti-pdf']]) ?>
</div>
<?= $this->endSection() ?>
