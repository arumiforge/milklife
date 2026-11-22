<?php
/**
 * Contoh halaman di tata letak `polos_tw` berisi formulir, seperti halaman
 * masuk admin (hanya dipakai uji tata letak F1-05, F9-17).
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/polos_tw') ?>

<?= $this->section('judul') ?>Masuk admin<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<h1 class="text-2xl font-bold text-gray-900" id="judul-contoh-formulir">Masuk admin</h1>
<p class="mt-1 mb-6 text-sm text-gray-600">Masukkan nama pengguna dan kata sandi panitia.</p>
<?= komponen('peringatan', ['jenis' => 'bahaya', 'isi' => 'Nama pengguna atau kata sandi salah. Periksa lagi, lalu coba sekali lagi.', 'kelas' => 'mb-4']) ?>
<form method="post" action="#" id="formulir-contoh" class="space-y-4">
    <div>
        <label for="nama_pengguna" class="mb-1.5 block text-sm font-semibold text-gray-900">Nama pengguna</label>
        <input type="text" class="block w-full rounded-lg border border-gray-300 p-3 text-base" id="nama_pengguna" name="nama_pengguna" autocomplete="username" required>
    </div>
    <div>
        <label for="sandi" class="mb-1.5 block text-sm font-semibold text-gray-900">Kata sandi</label>
        <input type="password" class="block w-full rounded-lg border border-gray-300 p-3 text-base" id="sandi" name="sandi" autocomplete="current-password" required>
    </div>
    <?= komponen('tombol', ['label' => 'Masuk', 'tipe' => 'submit', 'blok' => true]) ?>
</form>
<?= $this->endSection() ?>
