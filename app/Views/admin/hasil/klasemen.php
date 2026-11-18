<?php
/**
 * Klasemen sekolah di panel admin (F8-05, K124). Data dari `KlasemenController::index()`.
 *
 * @var CodeIgniter\View\View               $this
 * @var array<string, array<string, mixed>> $klasemen    `Klasemen::hitung()`, semua nomor
 * @var int                                 $belumTerbit nomor ber-juara yang belum terbit
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Klasemen Sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap gap-2 align-items-start mb-3">
        <p class="teks-info mb-0 flex-grow-1">
            Juara umum per jenjang: juara I terbanyak, lalu juara II, lalu juara III; bila masih sama, peringkat bersama.
            Dihitung dari babak Final semua nomor; satu regu juara = satu piala untuk sekolahnya, sekolah dikenali dari NPSN.
            Halaman publik hanya menghitung nomor yang sudah diterbitkan.
        </p>
        <div class="btn-group btn-group-sm" role="group" aria-label="Unduh klasemen">
            <a class="btn btn-outline-primary" href="<?= site_url('admin/klasemen/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
            <a class="btn btn-outline-success" href="<?= site_url('admin/klasemen/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
        </div>
    </div>

    <?php if ($belumTerbit > 0) : ?>
        <div class="alert alert-warning" id="belum-terbit">
            <?= $belumTerbit ?> nomor yang juaranya ikut dihitung di sini belum diterbitkan, jadi klasemen publik masih berbeda.
            <a href="<?= site_url('admin/hasil') ?>">Buka Hasil lomba</a> untuk menerbitkannya.
        </div>
    <?php endif ?>

    <?php if ($klasemen === []) : ?>
        <p class="text-body-secondary" id="klasemen-kosong">Belum ada juara. Juara diambil dari babak Final di menu Hasil lomba.</p>
    <?php endif ?>

    <?= view('hasil/_klasemen', ['klasemen' => $klasemen, 'admin' => true], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
