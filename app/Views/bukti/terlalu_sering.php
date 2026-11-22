<?php
/**
 * Pesan bila bukti diminta terlalu sering (pembatasan laju, status 429).
 * Tampilan Tailwind (F9-05).
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var int                   $detik lama tunggu
 */
?>
<?= $this->extend('tata_letak/publik_tw') ?>

<?= $this->section('judul') ?>Bukti Pendaftaran – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="mx-auto max-w-xl px-4 py-12">
    <div class="cek-belum-siap flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center" role="alert">
        <?= ikon('hourglass', 'mb-4 size-12 text-primary-700') ?>
        <h1 class="text-xl font-bold text-gray-900">Terlalu banyak permintaan bukti</h1>
        <p class="mt-1 max-w-md text-gray-600">Tunggu <?= esc((string) $detik) ?> detik lagi, lalu muat ulang halaman ini untuk mengunduh bukti pendaftaran.</p>
    </div>
</div>
<?= $this->endSection() ?>
