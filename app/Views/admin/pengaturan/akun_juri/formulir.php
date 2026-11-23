<?php
/**
 * Formulir tambah dan ubah akun juri (`GET …/akun-juri/tambah`,
 * `GET …/akun-juri/<id>/ubah`, F11-16), tata letak `admin_tw`. Ditampilkan
 * ulang dengan isian yang diketik bila ada yang salah; kotak kata sandi
 * tidak pernah diisi ulang.
 *
 * @var CodeIgniter\View\View     $this
 * @var array<string, mixed>|null $juri  akun yang diubah, `null` untuk akun baru
 * @var array<string, string>     $isian nama, nama_pengguna
 * @var array<string, string>     $galat pesan per kotak
 */

use App\Models\AdminModel;

$baru   = $juri === null;
$tujuan = $baru ? site_url('admin/pengaturan/akun-juri') : site_url('admin/pengaturan/akun-juri/' . (int) $juri['id']);
$judul  = $baru ? 'Tambah juri' : 'Ubah akun juri';

$kelasIsian = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . (isset($galat[$kotak])
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"'
    . (isset($galat[$kotak]) ? ' aria-invalid="true"' : '');
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
// ikon-sprite: clipboard-pen check x
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= $judul ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Akun juri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-formulir-juri">
        <h2 id="judul-formulir-juri" class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <?= ikon('clipboard-pen', 'text-primary-700') ?> <?= $judul ?>
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            <?= $baru
                ? 'Akun ini hanya bisa membuka Lembar juri dan halaman Akun miliknya.'
                : 'Bila kata sandi diganti, juri yang sedang masuk dengan akun ini diminta masuk lagi dengan kata sandi baru.' ?>
        </p>

        <div class="mt-4">
            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
        </div>

        <form action="<?= $tujuan ?>" method="post" novalidate class="mt-4 grid gap-5 lg:grid-cols-2 lg:items-start lg:gap-x-6">
            <?= csrf_field() ?>

            <div>
                <label for="nama" class="<?= $label ?>">Nama juri</label>
                <input type="text" class="<?= $kelasIsian('nama') ?>" id="nama" name="nama" maxlength="100" autocomplete="off"
                    value="<?= esc($isian['nama'], 'attr') ?>" required<?= $uraian('nama') ?>>
                <p id="nama-bantuan" class="<?= $bantuan ?>">Tampil di kepala panel juri, misalnya Pak Budi (juri lompat jauh).</p>
                <?= $pesan('nama') ?>
            </div>

            <div>
                <label for="nama_pengguna" class="<?= $label ?>">Nama pengguna</label>
                <input type="text" class="<?= $kelasIsian('nama_pengguna') ?>" id="nama_pengguna" name="nama_pengguna" maxlength="30"
                    autocomplete="off" autocapitalize="none" spellcheck="false"
                    value="<?= esc($isian['nama_pengguna'], 'attr') ?>" required<?= $uraian('nama_pengguna') ?>>
                <p id="nama_pengguna-bantuan" class="<?= $bantuan ?>">Dipakai juri untuk masuk. 3–30 karakter: huruf kecil, angka, titik, atau garis bawah, tanpa spasi, misalnya juri.budi.</p>
                <?= $pesan('nama_pengguna') ?>
            </div>

            <div>
                <label for="sandi" class="<?= $label ?>"><?= $baru ? 'Kata sandi' : 'Kata sandi baru' ?></label>
                <input type="password" class="<?= $kelasIsian('sandi') ?>" id="sandi" name="sandi" autocomplete="new-password"
                    minlength="<?= AdminModel::SANDI_MINIMAL ?>" maxlength="<?= AdminModel::SANDI_MAKSIMAL ?>"<?= $baru ? ' required' : '' ?><?= $uraian('sandi') ?>>
                <p id="sandi-bantuan" class="<?= $bantuan ?>"><?= $baru
                    ? 'Paling sedikit ' . AdminModel::SANDI_MINIMAL . ' karakter. Berikan langsung kepada juri, jangan lewat grup.'
                    : 'Kosongkan bila kata sandi tidak diganti. Paling sedikit ' . AdminModel::SANDI_MINIMAL . ' karakter.' ?></p>
                <?= $pesan('sandi') ?>
            </div>

            <div>
                <label for="ulangi_sandi" class="<?= $label ?>">Ulangi kata sandi</label>
                <input type="password" class="<?= $kelasIsian('ulangi_sandi') ?>" id="ulangi_sandi" name="ulangi_sandi" autocomplete="new-password"
                    maxlength="<?= AdminModel::SANDI_MAKSIMAL ?>"<?= $baru ? ' required' : '' ?><?= $uraian('ulangi_sandi') ?>>
                <p id="ulangi_sandi-bantuan" class="<?= $bantuan ?>">Ketik sekali lagi kata sandi yang sama.</p>
                <?= $pesan('ulangi_sandi') ?>
            </div>

            <div class="flex flex-wrap gap-2 pt-2 lg:col-span-2">
                <?= komponen('tombol', ['label' => $baru ? 'Simpan akun juri' : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/akun-juri'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
