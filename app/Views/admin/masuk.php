<?php
/**
 * Halaman masuk admin, tata letak `polos_tw` (Tahap 9). Kartu dan jaraknya
 * disediakan tata letak, jadi isinya cukup judul, pesan, dan formulir.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $galat        pesan galat di atas formulir
 * @var string|null           $info         pesan pemberitahuan (misalnya setelah keluar)
 * @var string                $namaPengguna nama pengguna yang tadi diketik
 * @var array{kunci_situs: string, aksi: string, skrip: string}|null $turnstile pemeriksaan keamanan (K122); null bila mati
 */
$turnstile ??= null;

use App\Controllers\Admin\Pengaturan\AcaraController;

$kelasIsian = 'block w-full rounded-lg border border-gray-300 bg-white p-3 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
?>
<?= $this->extend('tata_letak/polos_tw') ?>

<?= $this->section('judul') ?>Masuk admin<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<h1 class="text-2xl font-bold text-gray-900">Masuk admin</h1>
<p class="mb-6 mt-1 text-sm text-gray-600">Panel panitia <?= esc(AcaraController::namaTampil()) ?></p>

<?php if (! empty($galat)) : ?>
    <?= komponen('peringatan', ['jenis' => 'bahaya', 'isi' => esc($galat), 'kelas' => 'mb-4']) ?>
<?php elseif (! empty($info)) : ?>
    <?= komponen('peringatan', ['jenis' => 'info', 'isi' => esc($info), 'kelas' => 'mb-4']) ?>
<?php endif ?>

<form action="<?= site_url('admin/masuk') ?>" method="post" class="space-y-4">
    <?= csrf_field() ?>

    <div>
        <label for="nama_pengguna" class="mb-1.5 block text-sm font-semibold text-gray-900">Nama pengguna</label>
        <input type="text" class="<?= $kelasIsian ?>" id="nama_pengguna" name="nama_pengguna"
            value="<?= esc($namaPengguna, 'attr') ?>" maxlength="100"
            autocomplete="username" autocapitalize="none" spellcheck="false" required
            <?= $namaPengguna === '' ? 'autofocus' : '' ?>>
    </div>

    <div>
        <label for="sandi" class="mb-1.5 block text-sm font-semibold text-gray-900">Kata sandi</label>
        <input type="password" class="<?= $kelasIsian ?>" id="sandi" name="sandi"
            maxlength="255" autocomplete="current-password" required
            <?= $namaPengguna !== '' ? 'autofocus' : '' ?>>
    </div>

    <?php if ($turnstile !== null) : ?>
        <div class="cf-turnstile" data-sitekey="<?= esc($turnstile['kunci_situs'], 'attr') ?>" data-action="<?= esc($turnstile['aksi'], 'attr') ?>" data-language="id"></div>
    <?php endif ?>

    <?= komponen('tombol', ['label' => 'Masuk', 'tipe' => 'submit', 'ikon' => 'log-in', 'blok' => true]) ?>
</form>
<?= $this->endSection() ?>

<?php if ($turnstile !== null) : ?>
    <?= $this->section('skrip') ?>
    <script src="<?= esc($turnstile['skrip']) ?>" async defer></script>
    <?= $this->endSection() ?>
<?php endif ?>
