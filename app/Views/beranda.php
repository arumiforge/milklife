<?php
/**
 * Halaman depan sementara. Isi jadinya dikerjakan di Tahap 3.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>MilkLife Athletics Challenge<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container py-5">
    <h1>MilkLife Athletics Challenge</h1>
    <p>Pendaftaran sedang disiapkan. Silakan kembali lagi dalam beberapa hari.</p>
</div>
<?= $this->endSection() ?>
