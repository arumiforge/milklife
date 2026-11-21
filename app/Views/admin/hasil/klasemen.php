<?php
/**
 * Klasemen sekolah di panel admin (F8-05, K124). Data dari `KlasemenController::index()`.
 *
 * @var CodeIgniter\View\View               $this
 * @var array<string, array<string, mixed>> $klasemen    `Klasemen::hitung()`, semua nomor
 * @var int                                 $belumTerbit nomor ber-juara yang belum terbit
 * @var string                              $metode      `Klasemen::MEDALI` atau `Klasemen::POIN` (Pengaturan > Hasil)
 * @var string                              $keterangan  `Klasemen::keterangan()`
 */

use App\Libraries\Hasil\Klasemen;
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Klasemen Sekolah<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <div class="d-flex flex-wrap gap-2 align-items-start mb-3">
        <p class="teks-info mb-0 flex-grow-1">
            <?= esc($keterangan) ?>
            Klasemen dihitung per jenjang dari babak Final semua nomor<?= $metode === Klasemen::POIN ? ' (poin dari peringkat 1–' . Klasemen::PERINGKAT_POIN . ')' : '' ?>; satu regu juara = satu piala untuk sekolahnya, sekolah dikenali dari NPSN.
            Metode diatur di <a href="<?= site_url('admin/pengaturan/hasil') ?>">Pengaturan &gt; Hasil</a>.
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

    <?= view('hasil/_klasemen', ['klasemen' => $klasemen, 'admin' => true, 'metode' => $metode], ['saveData' => false]) ?>
</div>
<?= $this->endSection() ?>
