<?php
/**
 * Daftar nomor lomba untuk hasil lomba (F8-04, K124). Data dari `HasilController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, jenis_hasil: string, terbit_pada: string|null, babak: int, final: bool, peserta: int}>}> $kelompokUmur
 */

use App\Libraries\Hasil\JenisHasil;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Hasil Lomba<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="teks-info mb-4">
        Pilih nomor lomba untuk membuat babak (misalnya Penyisihan seri 1, lalu Final) dan mengisi prestasinya; peringkat dihitung otomatis.
        Juara I–III diambil dari babak Final. Hasil satu nomor tampil di halaman publik setelah ditekan <strong>Terbitkan</strong>.
    </p>

    <?php if ($kelompokUmur === []) : ?>
        <p class="text-body-secondary" id="hasil-kosong">Belum ada nomor lomba. Tambahkan di Pengaturan, Nomor Lomba.</p>
    <?php endif ?>

    <?php foreach ($kelompokUmur as $ku) : ?>
        <section class="card mb-4" aria-labelledby="judul-ku-<?= $ku['id'] ?>">
            <div class="card-body">
                <h2 class="h5 mb-3" id="judul-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
                <ul class="list-group">
                    <?php foreach ($ku['nomor'] as $n) : ?>
                        <li class="list-group-item d-flex gap-2 align-items-center py-2">
                            <div class="flex-grow-1 min-w-0">
                                <a class="fw-semibold" href="<?= site_url('admin/hasil/nomor/' . $n['id']) ?>"><?= esc($n['nama']) ?></a>
                                <div class="small text-body-secondary">
                                    <?= esc(JenisHasil::LABEL[$n['jenis_hasil']] ?? $n['jenis_hasil']) ?><?= $n['jenis'] === 'beregu' ? ' · beregu' : '' ?>
                                    · <?= $n['babak'] ?> babak<?= $n['final'] ? ' · Final ' . $n['peserta'] . ' peserta' : '' ?>
                                </div>
                            </div>
                            <?php if ($n['terbit_pada'] !== null) : ?>
                                <span class="badge rounded-pill text-bg-success flex-shrink-0">Terbit</span>
                            <?php elseif ($n['final']) : ?>
                                <span class="badge rounded-pill text-bg-warning flex-shrink-0">Belum terbit</span>
                            <?php endif ?>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </section>
    <?php endforeach ?>
</div>
<?= $this->endSection() ?>
