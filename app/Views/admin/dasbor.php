<?php
/**
 * Dasbor admin sementara. Ringkasan angka dan daftar kelengkapan
 * pengaturan dikerjakan di Tahap 2; sampai saat itu dasbor hanya
 * menampilkan keadaan kosong, tanpa angka.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Dasbor – Admin MilkLife<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Dasbor<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <section class="card kartu-kosong mb-4" aria-labelledby="judul-kosong">
        <div class="card-body text-center px-3 py-5">
            <span class="ikon-bulat mb-3"><i class="bi bi-inbox" aria-hidden="true"></i></span>
            <h2 id="judul-kosong" class="h4">Belum ada pendaftaran</h2>
            <p class="text-body-secondary mb-0 mx-auto">
                Pendaftaran dari sekolah akan muncul di sini setelah formulir pendaftaran dibuka.
                Untuk sekarang belum ada yang perlu diperiksa.
            </p>
        </div>
    </section>

    <div class="row g-4">
        <section class="col-12 col-xl-7" aria-labelledby="judul-langkah">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 id="judul-langkah" class="h5 mb-3">Langkah berikutnya</h2>
                    <p class="text-body-secondary">Sebelum pendaftaran dibuka, panitia perlu mengisi:</p>
                    <ol class="daftar-langkah mb-3">
                        <li>Data acara: nama, seri, tempat, dan tanggal.</li>
                        <li>Batas usia, biaya, dan awalan nomor dada tiap kelompok umur.</li>
                        <li>Nomor lomba yang dibuka untuk tiap kelompok umur.</li>
                        <li>Rekening dan nomor WA untuk konfirmasi pembayaran.</li>
                    </ol>
                    <p class="text-body-secondary small mb-0">
                        Menu untuk mengisinya akan muncul di menu admin begitu halamannya tersedia.
                    </p>
                </div>
            </div>
        </section>

        <section class="col-12 col-xl-5" aria-labelledby="judul-gambar">
            <div class="card h-100">
                <div class="card-body p-4">
                    <h2 id="judul-gambar" class="h5 mb-3">Gambar kepala halaman depan</h2>
                    <?= view('tata_letak/_tanpa_gambar', ['rasio' => '21x9', 'label' => 'Gambar kepala halaman depan'], ['saveData' => false]) ?>
                    <p class="text-body-secondary small mt-3 mb-0">
                        Gambar kepala dan logo bisa diunggah setelah pengaturan halaman depan tersedia.
                    </p>
                </div>
            </div>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
