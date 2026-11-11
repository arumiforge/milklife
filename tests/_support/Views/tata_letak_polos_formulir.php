<?php
/**
 * Contoh halaman di tata letak `polos` berisi formulir Bootstrap biasa,
 * seperti halaman masuk admin (hanya dipakai uji tata letak F1-05).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/polos') ?>

<?= $this->section('judul') ?>Masuk Admin – MilkLife<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<h1 class="h4 mb-1" id="judul-contoh-formulir">Masuk admin</h1>
<p class="text-body-secondary mb-4">Masukkan nama pengguna dan kata sandi panitia.</p>
<div class="alert alert-danger" role="alert">Nama pengguna atau kata sandi salah. Periksa lagi lalu coba masuk.</div>
<form method="post" action="#" id="formulir-contoh">
    <div class="mb-3">
        <label for="nama_pengguna" class="form-label">Nama pengguna</label>
        <input type="text" class="form-control" id="nama_pengguna" name="nama_pengguna" autocomplete="username" required>
    </div>
    <div class="mb-4">
        <label for="sandi" class="form-label">Kata sandi</label>
        <input type="password" class="form-control" id="sandi" name="sandi" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn btn-primary w-100">Masuk</button>
</form>
<?= $this->endSection() ?>
