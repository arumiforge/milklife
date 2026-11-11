<?php
/**
 * Halaman masuk admin. Tampilannya hanya memakai kelas Bootstrap; gaya tata
 * letak `polos` dikerjakan F1-05.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $galat        pesan galat di atas formulir
 * @var string|null           $info         pesan pemberitahuan (misalnya setelah keluar)
 * @var string                $namaPengguna nama pengguna yang tadi diketik
 */
?>
<?= $this->extend('tata_letak/polos') ?>

<?= $this->section('judul') ?>Masuk Admin – MilkLife<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Masuk Admin</h1>
                    <p class="text-body-secondary mb-4">Panel panitia MilkLife Athletics Challenge</p>

                    <?php if (! empty($galat)) : ?>
                        <div class="alert alert-danger d-flex gap-2" role="alert">
                            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                            <div><?= esc($galat) ?></div>
                        </div>
                    <?php elseif (! empty($info)) : ?>
                        <div class="alert alert-info d-flex gap-2" role="status">
                            <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                            <div><?= esc($info) ?></div>
                        </div>
                    <?php endif ?>

                    <form action="<?= site_url('admin/masuk') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="nama_pengguna" class="form-label">Nama pengguna</label>
                            <input type="text" class="form-control" id="nama_pengguna" name="nama_pengguna"
                                value="<?= esc($namaPengguna, 'attr') ?>" maxlength="100"
                                autocomplete="username" autocapitalize="none" spellcheck="false" required
                                <?= $namaPengguna === '' ? 'autofocus' : '' ?>>
                        </div>

                        <div class="mb-4">
                            <label for="sandi" class="form-label">Kata sandi</label>
                            <input type="password" class="form-control" id="sandi" name="sandi"
                                maxlength="255" autocomplete="current-password" required
                                <?= $namaPengguna !== '' ? 'autofocus' : '' ?>>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                            Masuk
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
