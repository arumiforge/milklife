<?php
/**
 * Halaman contoh untuk menguji tata letak `polos` (hanya dipakai uji).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/polos') ?>

<?= $this->section('judul') ?>Judul Contoh polos<?= $this->endSection() ?>

<?= $this->section('isi') ?><p id="isi-contoh">Isi contoh polos</p><?= $this->endSection() ?>

<?= $this->section('skrip') ?><script id="skrip-contoh"></script><?= $this->endSection() ?>
