<?php
/**
 * Halaman depan sementara. Isi jadinya (gambar kepala, logo, kelompok umur,
 * jadwal, unduhan, kontak) dikerjakan di Tahap 3.
 *
 * Nama, seri, tahun, tempat, dan tanggal acara diambil dari pengaturan admin
 * (Data Acara). Bagian yang belum diisi tidak ditampilkan; bila tanggal dan
 * tempat sama-sama belum diisi, tampil "segera diumumkan". Nama yang belum
 * diisi diganti `AcaraController::NAMA_PENGGANTI`.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;

$seri         = trim((string) setting('Acara.seri'));
$tahun        = trim((string) setting('Acara.tahun'));
$tempat       = trim((string) setting('Acara.tempat'));
$tanggalAcara = rentang_tanggal_id(setting('Acara.tanggal_mulai'), setting('Acara.tanggal_selesai'));

// "Seri 2 – 2026", "Seri 2", "2026", atau kosong.
$seriTahun = implode(' – ', array_filter([$seri !== '' ? 'Seri ' . $seri : '', $tahun]));
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?><?= esc(trim(AcaraController::namaTampil() . ' ' . $seriTahun)) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="pembuka" aria-labelledby="judul-acara">
    <div class="container py-5">
        <?php if ($seriTahun !== '') : ?>
            <p class="pita-miring fs-5 mb-3"><span><?= esc($seriTahun) ?></span></p>
        <?php endif ?>
        <h1 id="judul-acara" class="pembuka-judul mb-4">
            <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
        </h1>
        <p class="pembuka-waktu mb-2">
            <?php if ($tanggalAcara === '' && $tempat === '') : ?>
                <span><i class="bi bi-calendar-event" aria-hidden="true"></i>Tanggal dan tempat acara segera diumumkan</span>
            <?php else : ?>
                <?php if ($tanggalAcara !== '') : ?>
                    <span><i class="bi bi-calendar-event" aria-hidden="true"></i><?= esc($tanggalAcara) ?></span>
                <?php endif ?>
                <?php if ($tempat !== '') : ?>
                    <span><i class="bi bi-geo-alt" aria-hidden="true"></i><?= esc($tempat) ?></span>
                <?php endif ?>
            <?php endif ?>
        </p>
    </div>
</section>

<div class="container py-4 py-md-5">
    <section class="bidang-info p-4 p-md-5" aria-labelledby="judul-status">
        <div class="d-flex flex-column flex-sm-row gap-3 align-items-sm-start">
            <span class="ikon-bulat"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
            <div>
                <h2 id="judul-status" class="h4 mb-2 text-reset">Pendaftaran sedang disiapkan</h2>
                <p class="mb-0">
                    Panitia sedang menyiapkan formulir pendaftaran untuk sekolah.
                    Silakan kembali lagi dalam beberapa hari. Kelompok umur, nomor lomba,
                    biaya, dan jadwal akan tampil di halaman ini.
                </p>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
