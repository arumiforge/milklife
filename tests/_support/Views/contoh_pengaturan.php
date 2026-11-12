<?php
/**
 * Tampilan contoh pola halaman pengaturan (hanya dipakai uji). Halaman
 * sungguhan memakai susunan yang sama: navigasi pengaturan, ringkasan isian
 * salah, formulir dengan `is-invalid` + `invalid-feedback`, dan formulir hapus
 * ber-`data-konfirmasi`.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $isian
 * @var array<string, string>  $galat
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Contoh Pengaturan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Contoh Pengaturan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="card">
        <div class="card-body p-4">
            <?= view('admin/_galat_isian', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/pengaturan/contoh') ?>" method="post" novalidate>
                <?= csrf_field() ?>

                <?php foreach (['nama' => 'Nama acara', 'tempat' => 'Tempat', 'biaya' => 'Biaya'] as $kotak => $label) : ?>
                    <div class="mb-3">
                        <label for="<?= $kotak ?>" class="form-label"><?= $label ?></label>
                        <input type="text" class="form-control<?= isset($galat[$kotak]) ? ' is-invalid' : '' ?>"
                            id="<?= $kotak ?>" name="<?= $kotak ?>" value="<?= esc($isian[$kotak] ?? '', 'attr') ?>"
                            <?= isset($galat[$kotak]) ? 'aria-describedby="' . $kotak . '-galat"' : '' ?>>
                        <?php if (isset($galat[$kotak])) : ?>
                            <div id="<?= $kotak ?>-galat" class="invalid-feedback"><?= esc($galat[$kotak]) ?></div>
                        <?php endif ?>
                    </div>
                <?php endforeach; ?>

                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>

            <form action="<?= site_url('admin/pengaturan/contoh/7/hapus') ?>" method="post" class="mt-3"
                data-konfirmasi="Data contoh nomor 7 akan dihapus dan tidak bisa dikembalikan."
                data-konfirmasi-tombol="Hapus data">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger">Hapus</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
