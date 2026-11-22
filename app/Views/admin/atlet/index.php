<?php
/**
 * Daftar semua atlet (F6-03): tabel data dari `admin/atlet/data` dengan
 * saringan kelompok umur, jenis kelamin, nomor lomba, dan status
 * pembayaran, serta tombol unduh Excel yang mengikuti saringan aktif.
 * Hanya untuk melihat dan mengunduh; ID pendaftaran menaut ke rincian
 * pendaftaran, tempat atlet diubah. Data dari `AtletController::index()`.
 * Tata letak `admin_tw` (F9-15).
 *
 * @var CodeIgniter\View\View                                 $this
 * @var list<array{id: int|string, nama: string}>              $kelompokUmur
 * @var array<string, list<array{id: int, nama: string}>>      $nomorLomba   nama kelompok umur => nomor lomba
 * @var array<string, string>                                 $jenisKelamin kode => label
 * @var string|null                                           $diurutkanPada waktu nomor dada terakhir diurutkan ulang (K111)
 */

use App\Controllers\Admin\AtletController;

$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$pilihan = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Atlet<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Atlet<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <p class="min-w-0 flex-1 basis-72 text-gray-700">
            Semua atlet dari setiap pendaftaran. Untuk mengubah data atlet, buka rincian pendaftarannya lewat ID pendaftaran.
            <?php if ($diurutkanPada !== null) : ?>
                <br><span id="keterangan-nomor-dada" class="text-sm text-gray-600">Nomor atlet (BIB) diurutkan ulang per sekolah pada <?= esc(tanggal_id($diurutkanPada)) ?>.</span>
            <?php endif ?>
        </p>
        <?php // Urutkan ulang nomor dada per sekolah (K111). ?>
        <?= komponen('tombol', ['label' => 'Urutkan nomor atlet (BIB)', 'href' => site_url('admin/atlet/nomor-dada'), 'varian' => 'kedua', 'ikon' => 'arrow-down-0-1', 'atribut' => ['id' => 'tombol-urutkan-nomor-dada']]) ?>
    </div>

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-label="Daftar atlet">
        <form id="saringan-atlet" class="flex flex-wrap items-end gap-3" role="search" aria-label="Saring daftar atlet" data-saringan-tabel>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-kelompok-umur">Kelompok umur</label>
                <select class="<?= $pilihan ?>" id="saring-kelompok-umur" name="kelompok_umur">
                    <option value="">Semua</option>
                    <?php foreach ($kelompokUmur as $ku) : ?>
                        <option value="<?= (int) $ku['id'] ?>"><?= esc($ku['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-jenis-kelamin">Jenis kelamin</label>
                <select class="<?= $pilihan ?>" id="saring-jenis-kelamin" name="jenis_kelamin">
                    <option value="">Semua</option>
                    <?php foreach ($jenisKelamin as $kode => $labelJk) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelJk) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-nomor-lomba">Nomor lomba</label>
                <select class="<?= $pilihan ?>" id="saring-nomor-lomba" name="nomor_lomba">
                    <option value="">Semua</option>
                    <?php foreach ($nomorLomba as $namaKu => $daftar) : ?>
                        <optgroup label="<?= esc($namaKu, 'attr') ?>">
                            <?php foreach ($daftar as $nomor) : ?>
                                <option value="<?= $nomor['id'] ?>"><?= esc($nomor['nama']) ?></option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-status">Status pembayaran</label>
                <select class="<?= $pilihan ?>" id="saring-status" name="status">
                    <option value="">Semua</option>
                    <?php foreach (AtletController::STATUS as $kode => $labelStatus) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelStatus) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex max-w-full flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Kosongkan', 'tipe' => 'reset', 'varian' => 'kedua', 'ikon' => 'circle-x']) ?>
                <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/atlet/excel'), 'varian' => 'kedua', 'ikon' => 'file-spreadsheet', 'atribut' => ['id' => 'excel-atlet']]) ?>
            </div>
        </form>

        <table id="tabel-atlet" class="w-full" data-tabel-admin
            data-alamat="<?= site_url('admin/atlet/data') ?>"
            data-saringan="#saringan-atlet" data-excel="#excel-atlet"
            data-kosong="<?= esc(AtletController::PESAN_KOSONG, 'attr') ?>"
            data-kosong-saring="Tidak ada atlet yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
            <caption class="sr-only">Daftar atlet</caption>
            <thead>
                <tr>
                    <th data-bentuk="nomor" class="text-end">No</th>
                    <th data-kolom="nomor_dada">Nomor atlet (BIB)</th>
                    <th data-kolom="nama">Nama</th>
                    <th data-kolom="nisn">NISN</th>
                    <th data-kolom="jenis_kelamin">Jenis kelamin</th>
                    <th data-kolom="tanggal_lahir" data-bentuk="tanggal">Tanggal lahir</th>
                    <th data-kolom="kelompok_umur">Kelompok umur</th>
                    <th data-kolom="nomor_lomba" data-urut="tidak">Nomor lomba</th>
                    <th data-kolom="nama_sekolah">Sekolah</th>
                    <th data-kolom="id_pendaftaran" data-bentuk="tautan">ID pendaftaran</th>
                    <th data-kolom="status" data-bentuk="status">Status</th>
                </tr>
            </thead>
        </table>
    </section>
</div>
<?= $this->endSection() ?>
