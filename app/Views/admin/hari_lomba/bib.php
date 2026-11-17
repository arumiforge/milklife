<?php
/**
 * Cetak nomor atlet (BIB) massal (F8-03). Data dari `CetakBibController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, atlet: int}> $kelompokUmur
 * @var int $jumlahSemua
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Cetak Nomor Atlet (BIB)<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/nomor-atlet/cetak'], ['saveData' => false]) ?>

    <p class="teks-info mb-4">
        Satu nomor atlet (BIB) seukuran setengah A4 mendatar, dua per halaman A4; potong di garis putus-putus.
        Isinya nomor atlet, nama atlet, sekolah, dan kelompok umur, berurutan menurut nomor atlet.
        Hanya atlet dari pendaftaran Lunas. Bila panitia memakai BIB cetakan sponsor, PDF ini bisa dipakai sebagai label tempel.
        Urutkan nomor atlet lebih dulu bila perlu (<a href="<?= site_url('admin/atlet/nomor-dada') ?>">Urutkan nomor atlet</a>).
    </p>

    <section class="card" aria-labelledby="judul-bib">
        <div class="card-body">
            <h2 class="h5" id="judul-bib">Pilih kelompok umur</h2>
            <?php if ($jumlahSemua === 0) : ?>
                <p class="text-body-secondary mb-0" id="bib-kosong">Belum ada atlet dengan nomor atlet (BIB). Nomor atlet dibuat saat pendaftaran ditandai Lunas.</p>
            <?php else : ?>
                <div class="list-group" id="daftar-bib">
                    <?php foreach ($kelompokUmur as $ku) : ?>
                        <?php if ($ku['atlet'] > 0) : ?>
                            <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=<?= $ku['id'] ?>">
                                <span><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> <?= esc($ku['nama']) ?></span>
                                <span class="text-body-secondary small"><?= $ku['atlet'] ?> atlet · <?= (int) ceil($ku['atlet'] / 2) ?> halaman</span>
                            </a>
                        <?php else : ?>
                            <div class="list-group-item d-flex justify-content-between text-body-secondary">
                                <span><?= esc($ku['nama']) ?></span><span class="small">Belum ada atlet</span>
                            </div>
                        <?php endif ?>
                    <?php endforeach ?>
                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center fw-semibold" href="<?= site_url('admin/nomor-atlet/cetak/pdf') ?>?ku=semua">
                        <span><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> Semua kelompok umur</span>
                        <span class="text-body-secondary small"><?= $jumlahSemua ?> atlet · <?= (int) ceil($jumlahSemua / 2) ?> halaman</span>
                    </a>
                </div>
                <p class="small text-body-secondary mt-3 mb-0">Berkas besar butuh beberapa detik sampai terunduh.</p>
            <?php endif ?>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
