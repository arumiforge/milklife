<?php
/**
 * Halaman publik hasil lomba (`/hasil`, F8-04): nomor yang hasilnya sudah
 * diterbitkan, per kelompok umur, dengan juara I–III babak Final. Pengendali:
 * `App\Controllers\HasilController`.
 *
 * @var CodeIgniter\View\View                                                  $this
 * @var string                                                                 $namaAcara
 * @var list<array{id: int, nama: string, nomor: list<array<string, mixed>>}> $kelompok `HasilLomba::daftarNomor(true)`
 * @var array<int, list<array<string, mixed>>>                                 $juara    `HasilLomba::juara(true)`
 */

$medali = [1 => 'emas', 2 => 'perak', 3 => 'perunggu'];
$romawi = [1 => 'I', 2 => 'II', 3 => 'III'];
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Hasil Lomba – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan halaman-hasil">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <h1><i class="bi bi-trophy" aria-hidden="true"></i> Hasil Lomba</h1>
            <p class="mb-0">Hasil yang sudah diumumkan panitia <?= esc($namaAcara) ?>. Nomor lain menyusul setelah lombanya selesai.</p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <?php if ($kelompok === []) : ?>
            <div class="unduhan-kosong">
                <span class="ikon-bulat mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <h2 class="h4">Belum ada hasil</h2>
                <p>Panitia belum mengumumkan hasil lomba. Hasil tiap nomor muncul di sini setelah lombanya selesai. Silakan kembali lagi nanti.</p>
                <a class="btn btn-outline-primary" href="<?= site_url('/') ?>">
                    <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda
                </a>
            </div>
        <?php endif ?>

        <?php foreach ($kelompok as $ku) : ?>
            <section class="mb-4" id="hasil-ku-<?= (int) $ku['id'] ?>" aria-labelledby="judul-ku-<?= (int) $ku['id'] ?>">
                <h2 class="h4 fw-bold" id="judul-ku-<?= (int) $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
                <div class="row g-3">
                    <?php foreach ($ku['nomor'] as $n) : ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card h-100" id="hasil-nomor-<?= (int) $n['id'] ?>">
                                <div class="card-body">
                                    <h3 class="h6 fw-bold"><a href="<?= site_url('hasil/' . (int) $n['id']) ?>"><?= esc($n['nama_lengkap']) ?></a></h3>
                                    <?php if (($juara[(int) $n['id']] ?? []) === []) : ?>
                                        <p class="text-body-secondary small mb-2">Juara belum ditetapkan.</p>
                                    <?php else : ?>
                                        <ol class="list-unstyled mb-2">
                                            <?php foreach ($juara[(int) $n['id']] as $j) : ?>
                                                <li class="d-flex gap-2 mb-1">
                                                    <i class="bi bi-award-fill hasil-medali-<?= $medali[$j['peringkat']] ?>" aria-hidden="true"></i>
                                                    <span>
                                                        <span class="visually-hidden">Juara </span><strong><?= $romawi[$j['peringkat']] ?></strong>
                                                        <?= esc($j['nama']) ?>
                                                        <span class="d-block small text-body-secondary"><?= esc($j['regu'] === null ? $j['sekolah'] . ' · ' : '') ?><?= esc($j['prestasi_teks']) ?></span>
                                                    </span>
                                                </li>
                                            <?php endforeach ?>
                                        </ol>
                                    <?php endif ?>
                                    <a class="small" href="<?= site_url('hasil/' . (int) $n['id']) ?>">Lihat semua babak <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </section>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
