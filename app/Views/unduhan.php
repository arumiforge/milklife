<?php
/**
 * Halaman publik semua berkas unduhan (`/unduhan`, K40). Pengendali:
 * `App\Controllers\UnduhanController`.
 *
 * Daftar memakai potongan bersama `beranda/_daftar_unduhan` (sama dengan
 * bagian unduhan halaman depan). Bila belum ada berkas, tampil pesan ramah.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara `IsiHalamanDepan::acara()['nama']`
 * @var list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}> $unduhan
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Unduhan – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <h1><i class="bi bi-download" aria-hidden="true"></i> Unduhan</h1>
            <p class="mb-0">Flyer, petunjuk teknis, dan berkas lain dari panitia <?= esc($namaAcara) ?>.</p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <?php if ($unduhan === []) : ?>
            <div class="unduhan-kosong">
                <span class="ikon-bulat mb-3"><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                <h2 class="h4">Belum ada berkas unduhan</h2>
                <p>Panitia belum menaruh berkas di sini. Flyer dan petunjuk teknis akan muncul di halaman ini setelah tersedia. Silakan kembali lagi nanti.</p>
                <a class="btn btn-outline-primary" href="<?= site_url('/') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda
                </a>
            </div>
        <?php else : ?>
            <?= view('beranda/_daftar_unduhan', ['unduhan' => $unduhan, 'tingkatJudul' => 'h2'], ['saveData' => false]) ?>
        <?php endif ?>
    </div>
</div>
<?= $this->endSection() ?>
