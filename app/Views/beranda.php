<?php
/**
 * Halaman depan sementara. Isi jadinya (gambar kepala, logo, kelompok umur,
 * jadwal, unduhan, kontak) dikerjakan di Tahap 3.
 *
 * Nama acara, seri, tanggal, dan tempat ditulis langsung dulu;
 * Tahap 2–3 menggantinya dengan pengaturan admin.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>MilkLife Athletics Challenge Seri 2 – 2026<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<section class="pembuka" aria-labelledby="judul-acara">
    <div class="container py-5">
        <p class="pita-miring fs-5 mb-3"><span>Seri 2 – 2026</span></p>
        <h1 id="judul-acara" class="pembuka-judul mb-4">
            MilkLife
            <span class="merek-sambungan">Athletics Challenge</span>
        </h1>
        <p class="pembuka-waktu mb-2">
            <span><i class="bi bi-calendar-event" aria-hidden="true"></i>9–12 Desember 2026</span>
            <span><i class="bi bi-geo-alt" aria-hidden="true"></i>Supersoccer Arena, Kudus</span>
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
