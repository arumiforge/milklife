<?php
/**
 * Hasil satu nomor lomba yang sudah terbit, semua babak (F8-04, K124).
 * Pengendali: `App\Controllers\HasilController::nomor()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $namaAcara
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::semuaBabak()`
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Hasil <?= esc($nomor['nama']) ?> <?= esc($nomor['kelompok_umur']) ?> – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan halaman-hasil">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <h1><i class="bi bi-trophy" aria-hidden="true"></i> <?= esc($nomor['nama']) ?></h1>
            <p class="mb-0"><?= esc($nomor['kelompok_umur']) ?> · Hasil semua babak. DNS = tidak start, DNF = tidak finis, DQ = diskualifikasi.</p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <div class="d-flex flex-wrap gap-2 mb-4">
            <a class="btn btn-outline-primary" href="<?= site_url('hasil') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Semua hasil</a>
            <a class="btn btn-outline-primary" href="<?= site_url('hasil/' . $nomor['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
            <a class="btn btn-outline-success" href="<?= site_url('hasil/' . $nomor['id'] . '/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
        </div>

        <?php if ($babak === []) : ?>
            <p class="text-body-secondary">Belum ada babak.</p>
        <?php endif ?>

        <?php foreach ($babak as $b) : ?>
            <section class="card mb-4" aria-labelledby="babak-<?= $b['id'] ?>">
                <div class="card-body">
                    <h2 class="h5 fw-bold" id="babak-<?= $b['id'] ?>"><?= esc($b['nama']) ?></h2>
                    <?= view('hasil/_tabel', ['nomor' => $nomor, 'babak' => $b, 'admin' => false], ['saveData' => false]) ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
