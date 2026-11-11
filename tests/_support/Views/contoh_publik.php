<?php
/**
 * Halaman contoh untuk menguji tata letak `publik` (hanya dipakai uji).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Judul Contoh publik<?= $this->endSection() ?>

<?= $this->section('isi') ?><p id="isi-contoh">Isi contoh publik</p><?= $this->endSection() ?>

<?= $this->section('skrip') ?><script id="skrip-contoh"></script><?= $this->endSection() ?>
