<?php
/**
 * Halaman masuk admin, tata letak `polos_tw` (Tahap 9). Kartu dan jaraknya
 * disediakan tata letak, jadi isinya cukup judul, pesan, dan formulir.
 * Judul "Admin Area" dan tautan "Kembali ke beranda" di bawah kartu (bagian
 * `bawah` tata letak) atas permintaan pemilik 22 November 2026.
 *
 * @var CodeIgniter\View\View $this
 * @var string|null           $galat        pesan galat di atas formulir
 * @var string|null           $info         pesan pemberitahuan (misalnya setelah keluar)
 * @var string                $namaPengguna nama pengguna yang tadi diketik
 * @var array{kunci_situs: string, aksi: string, skrip: string}|null $turnstile pemeriksaan keamanan (K122); null bila mati
 */
$turnstile ??= null;
$kelasIsian = 'block w-full rounded-lg border border-gray-300 bg-white p-3 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
?>
<?= $this->extend('tata_letak/polos_tw') ?>

<?= $this->section('judul') ?>Admin Area<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<h1 class="mb-6 text-2xl font-bold text-gray-900">Admin Area</h1>

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

<?= $this->section('bawah') ?>
<p class="mt-6 text-center">
    <a href="<?= site_url('/') ?>" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-3 font-semibold text-primary-800 underline-offset-4 hover:underline focus-visible:outline-3 focus-visible:outline-primary-700" data-tautan-beranda>
        <?= ikon('arrow-left', 'size-5') ?>Kembali ke beranda
    </a>
</p>
<?= $this->endSection() ?>

<?php if ($turnstile !== null) : ?>
    <?= $this->section('skrip') ?>
    <script src="<?= esc($turnstile['skrip']) ?>" async defer></script>
    <?= $this->endSection() ?>
<?php endif ?>
