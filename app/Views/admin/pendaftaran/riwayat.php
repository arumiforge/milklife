<?php
/**
 * Cari riwayat tindakan admin menurut ID pendaftaran (F8-01, K124), terutama
 * untuk pendaftaran yang sudah dihapus atau digabung, yang rinciannya tidak
 * ada lagi. Data dari `PendaftaranController::riwayat()`.
 *
 * @var CodeIgniter\View\View      $this
 * @var string                     $kata    ID yang dicari (boleh sebagian)
 * @var list<array<string, mixed>> $riwayat `RiwayatAdmin::cari()`
 * @var int                        $batas   paling banyak baris hasil
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Riwayat Pendaftaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Riwayat Pendaftaran<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak', ['jejak' => [
    ['label' => 'Pendaftaran', 'alamat' => 'admin/pendaftaran'],
    ['label' => 'Riwayat'],
]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="teks-info mb-4">
        Riwayat tindakan admin tetap disimpan walau pendaftarannya sudah dihapus atau digabung.
        Ketik ID pendaftarannya (contoh MAC2A0000126, boleh sebagian), lalu tekan Cari.
    </p>

    <section class="card mb-4" aria-labelledby="judul-cari-riwayat">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-cari-riwayat" class="visually-hidden">Cari riwayat</h2>
            <form action="<?= site_url('admin/riwayat') ?>" method="get" role="search" class="row g-2 align-items-end" id="form-cari-riwayat">
                <div class="col-12 col-sm-8 col-lg-5">
                    <label class="form-label" for="cari-id">ID pendaftaran</label>
                    <input type="search" class="form-control" id="cari-id" name="id" value="<?= esc($kata, 'attr') ?>" maxlength="20" autocomplete="off" placeholder="MAC2A0000126">
                </div>
                <div class="col-12 col-sm-4 col-lg-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search" aria-hidden="true"></i> Cari</button>
                </div>
            </form>
        </div>
    </section>

    <?php if ($kata !== '') : ?>
        <section class="card mb-4" aria-labelledby="judul-hasil-riwayat">
            <div class="card-body p-3 p-lg-4">
                <h2 id="judul-hasil-riwayat" class="h5 mb-1">Hasil untuk "<?= esc($kata) ?>"</h2>
                <p class="small text-body-secondary mb-2">
                    <?= count($riwayat) ?> catatan, terbaru di atas.
                    <?php if (count($riwayat) >= $batas) : ?>
                        Hanya <?= $batas ?> catatan terbaru yang ditampilkan; ketik ID lebih lengkap untuk mempersempit.
                    <?php endif ?>
                </p>
                <?= view('admin/pendaftaran/_riwayat', ['riwayat' => $riwayat, 'denganId' => true], ['saveData' => false]) ?>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>
