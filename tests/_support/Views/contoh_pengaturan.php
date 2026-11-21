<?php
/**
 * Tampilan contoh pola halaman pengaturan (hanya dipakai uji). Halaman
 * sungguhan memakai susunan yang sama: navigasi pengaturan, ringkasan isian
 * salah, formulir dengan `aria-invalid` + pesan per kotak, dan formulir hapus
 * ber-`data-konfirmasi`.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian
 * @var array<string, string>  $galat
 */
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Contoh Pengaturan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Contoh Pengaturan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6">
        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= site_url('admin/pengaturan/contoh') ?>" method="post" class="space-y-4" novalidate>
            <?= csrf_field() ?>

            <?php foreach (['nama' => 'Nama acara', 'tempat' => 'Tempat', 'biaya' => 'Biaya'] as $kotak => $label) : ?>
                <?= komponen('isian', ['nama' => $kotak, 'label' => $label, 'nilai' => $isian[$kotak] ?? '', 'galat' => $galat[$kotak] ?? null]) ?>
            <?php endforeach; ?>

            <?= komponen('tombol', ['label' => 'Simpan', 'tipe' => 'submit', 'ikon' => 'save']) ?>
        </form>

        <form action="<?= site_url('admin/pengaturan/contoh/7/hapus') ?>" method="post" class="mt-3"
            data-konfirmasi="Data contoh nomor 7 akan dihapus dan tidak bisa dikembalikan."
            data-konfirmasi-tombol="Hapus data">
            <?= csrf_field() ?>
            <?= komponen('tombol', ['label' => 'Hapus', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'trash-2']) ?>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
