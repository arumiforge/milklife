<?php
/**
 * Hasil publik satu nomor lomba (`/hasil/<id>`, F8-04): semua babak, Final
 * paling akhir. Hanya untuk nomor yang sudah terbit.
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $namaAcara
 * @var array<string, mixed>       $nomor `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak `HasilLomba::susunan()`
 */
?>
<?= $this->extend('tata_letak/publik') ?>

<?= $this->section('judul') ?>Hasil <?= esc($nomor['nama_lengkap'] . ' ' . $nomor['nama_kelompok_umur']) ?> – <?= esc($namaAcara) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="halaman-unduhan halaman-hasil">
    <div class="halaman-unduhan-kepala">
        <div class="container">
            <p class="mb-1"><a class="link-light" href="<?= site_url('hasil') ?>"><i class="bi bi-arrow-left" aria-hidden="true"></i> Semua hasil</a></p>
            <h1><i class="bi bi-trophy" aria-hidden="true"></i> <?= esc($nomor['nama_lengkap']) ?></h1>
            <p class="mb-0"><?= esc($nomor['nama_kelompok_umur']) ?></p>
        </div>
    </div>

    <div class="container halaman-unduhan-isi">
        <?php if ($babak === []) : ?>
            <p class="text-body-secondary">Belum ada hasil untuk nomor ini.</p>
        <?php endif ?>
        <?php foreach (array_reverse($babak) as $b) : ?>
            <section class="card mb-3" id="babak-<?= (int) $b['id'] ?>" aria-labelledby="judul-babak-<?= (int) $b['id'] ?>">
                <div class="card-header bg-transparent">
                    <h2 class="h6 fw-bold mb-0" id="judul-babak-<?= (int) $b['id'] ?>"><?= esc($b['nama']) ?></h2>
                </div>
                <?php if ($b['baris'] === []) : ?>
                    <div class="card-body text-body-secondary">Belum ada hasil.</div>
                <?php else : ?>
                    <?= view('hasil/_tabel_babak', ['nomor' => $nomor, 'babak' => $b], ['saveData' => false]) ?>
                <?php endif ?>
            </section>
        <?php endforeach ?>
    </div>
</div>
<?= $this->endSection() ?>
