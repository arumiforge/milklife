<?php
/**
 * Halaman publik hasil lomba (F8-04, K124): nomor yang sudah diterbitkan
 * admin per kelompok umur, dengan juara I–III dari babak Final. Pengendali:
 * `App\Controllers\HasilController::index()`.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $namaAcara
 * @var list<array{id: int, nama: string, nomor: list<array<string, mixed>>}> $kelompokUmur `HasilLomba::daftarNomor(true)`
 * @var array<int, list<array<string, mixed>>> $juara id nomor => juara (`HasilLomba::juara(true)`)
 */
$medali = [1 => 'I', 2 => 'II', 3 => 'III'];
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Hasil Lomba – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan halaman-hasil">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <h1><i class="bi bi-trophy" aria-hidden="true"></i> Hasil Lomba</h1>
            <p class="mb-0">Hasil resmi <?= esc($namaAcara) ?> yang sudah diterbitkan panitia, per kelompok umur dan nomor lomba.</p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <?php if ($kelompokUmur === []) : ?>
            <div class="unduhan-kosong" id="hasil-kosong">
                <span class="ikon-bulat mb-3"><i class="bi bi-hourglass-split" aria-hidden="true"></i></span>
                <h2 class="h4">Hasil belum diterbitkan</h2>
                <p>Panitia belum menerbitkan hasil lomba. Hasil tiap nomor muncul di sini setelah diumumkan. Silakan kembali lagi nanti.</p>
                <a class="btn btn-outline-primary" href="<?= site_url('/') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali ke beranda</a>
            </div>
        <?php endif ?>

        <?php foreach ($kelompokUmur as $ku) : ?>
            <section class="mb-4" aria-labelledby="hasil-ku-<?= $ku['id'] ?>">
                <h2 class="h4 fw-bold" id="hasil-ku-<?= $ku['id'] ?>"><?= esc($ku['nama']) ?></h2>
                <div class="row row-cols-1 row-cols-md-2 g-3">
                    <?php foreach ($ku['nomor'] as $n) : ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h3 class="h6 fw-bold"><a href="<?= site_url('hasil/' . $n['id']) ?>"><?= esc($n['nama']) ?></a></h3>
                                    <?php if (($juara[$n['id']] ?? []) === []) : ?>
                                        <p class="small text-body-secondary mb-0">Juara belum ditetapkan. Lihat hasil semua babak.</p>
                                    <?php else : ?>
                                        <ol class="list-unstyled mb-0">
                                            <?php foreach ($juara[$n['id']] as $j) : ?>
                                                <li class="d-flex gap-2"><span class="badge text-bg-warning align-self-start">Juara <?= $medali[$j['peringkat']] ?? $j['peringkat'] ?></span><span><?= esc($j['nama']) ?><?= $n['jenis'] === 'beregu' ? '' : ' <span class="small text-body-secondary">' . esc($j['sekolah']) . '</span>' ?></span></li>
                                            <?php endforeach ?>
                                        </ol>
                                    <?php endif ?>
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
