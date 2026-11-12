<?php
/**
 * Pesan bila bukti diminta terlalu sering (pembatasan laju, status 429).
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var int                   $detik lama tunggu
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Bukti Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container halaman-cek-isi">
    <div class="cek-belum-siap" role="alert">
        <span class="ikon-bulat mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
        <h1 class="h4">Terlalu banyak permintaan bukti</h1>
        <p>Tunggu <?= esc((string) $detik) ?> detik lagi, lalu muat ulang halaman ini untuk mengunduh bukti pendaftaran.</p>
    </div>
</div>
<?= $this->endSection() ?>
