<?php
/**
 * Klasemen sekolah publik (F8-05, K124): hanya nomor yang hasilnya sudah
 * diterbitkan. Pengendali: `App\Controllers\HasilController::klasemen()`.
 *
 * @var CodeIgniter\View\View               $this
 * @var string                              $namaAcara
 * @var array<string, array<string, mixed>> $klasemen   `HasilLomba::klasemen(true)`
 * @var string                              $metode     `Klasemen::MEDALI` atau `Klasemen::POIN`
 * @var string                              $keterangan `Klasemen::keterangan()`
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Klasemen Sekolah – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan halaman-hasil">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <h1><i class="bi bi-award" aria-hidden="true"></i> Klasemen Sekolah</h1>
            <p class="mb-0">Klasemen per jenjang sekolah. <?= esc($keterangan) ?> Dihitung dari nomor yang hasilnya sudah diterbitkan panitia.</p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a class="btn btn-outline-primary" href="<?= site_url('hasil') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Hasil per nomor</a>
            <?php if ($klasemen !== []) : ?>
                <a class="btn btn-outline-primary" href="<?= site_url('hasil/klasemen/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
                <a class="btn btn-outline-success" href="<?= site_url('hasil/klasemen/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
            <?php endif ?>
        </div>

        <?php if ($klasemen === []) : ?>
            <div class="unduhan-kosong" id="klasemen-kosong">
                <span class="ikon-bulat mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <h2 class="h4">Klasemen belum tersedia</h2>
                <p>Klasemen muncul setelah panitia menerbitkan hasil Final. Silakan kembali lagi nanti.</p>
            </div>
        <?php endif ?>

        <?= view('hasil/_klasemen', ['klasemen' => $klasemen, 'admin' => false, 'metode' => $metode], ['saveData' => false]) ?>
    </div>
</div>
<?= $this->endSection() ?>
