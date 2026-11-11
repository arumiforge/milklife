<?php
/**
 * Halaman contoh untuk menguji tata letak `admin` (hanya dipakai uji).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Judul Contoh admin<?= $this->endSection() ?>

<?= $this->section('isi') ?><p id="isi-contoh">Isi contoh admin</p><?= $this->endSection() ?>

<?= $this->section('skrip') ?><script id="skrip-contoh"></script><?= $this->endSection() ?>
