<?php
/**
 * Daftar peserta per nomor lomba (F8-03). Data dari `DaftarPesertaController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, peserta: int}>}> $kelompokUmur
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Daftar Peserta<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= view('admin/hari_lomba/_tab', ['aktif' => 'admin/daftar-peserta'], ['saveData' => false]) ?>

    <p class="teks-info mb-4">
        Daftar peserta untuk pemanggilan atlet dan juri: No, nomor atlet (BIB), nama, sekolah, tanda hadir dari daftar ulang, dan kolom kosong untuk catatan juri.
        Nomor beregu ditulis per regu, anggota inti dulu lalu cadangan. Hanya atlet dari pendaftaran Lunas.
        Unduh lagi menjelang pemanggilan supaya tanda hadirnya terbaru.
    </p>

    <?php if ($kelompokUmur === []) : ?>
        <p class="text-body-secondary" id="peserta-kosong">Belum ada nomor lomba. Tambahkan di Pengaturan, Nomor Lomba.</p>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section class="card mb-4" aria-labelledby="judul-ku-<?= $ku['id'] ?>">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                    <h2 class="h5 mb-0 me-auto" id="judul-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
                    <span class="small text-body-secondary">Semua nomor:</span>
                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= esc('Unduh semua nomor ' . $ku['nama'], 'attr') ?>">
                        <a class="btn btn-outline-primary" href="<?= site_url('admin/daftar-peserta/kelompok-umur/' . $ku['id'] . '/pdf') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
                        <a class="btn btn-outline-success" href="<?= site_url('admin/daftar-peserta/kelompok-umur/' . $ku['id'] . '/excel') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
                    </div>
                </div>
                <ul class="list-group">
                    <?php foreach ($ku['nomor'] as $n) : ?>
                        <li class="list-group-item d-flex gap-2 align-items-center py-2">
                            <div class="flex-grow-1 min-w-0">
                                <div class="fw-semibold"><?= esc($n['nama']) ?></div>
                                <div class="small text-body-secondary"><?= $n['jenis'] === 'beregu' ? 'Beregu · ' : '' ?><?= $n['peserta'] ?> atlet</div>
                            </div>
                            <div class="btn-group btn-group-sm flex-shrink-0" role="group" aria-label="<?= esc('Unduh ' . $n['nama'], 'attr') ?>">
                            <a class="btn btn-outline-primary" href="<?= site_url('admin/daftar-peserta/' . $n['id'] . '/pdf') ?>" aria-label="<?= esc('PDF ' . $n['nama'], 'attr') ?>"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF</a>
                            <a class="btn btn-outline-success" href="<?= site_url('admin/daftar-peserta/' . $n['id'] . '/excel') ?>" aria-label="<?= esc('Excel ' . $n['nama'], 'attr') ?>"><i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Excel</a>
                            </div>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
