<?php
/**
 * Daftar sekolah (K97): tabel data dari `admin/sekolah/data` dengan
 * saringan kabupaten, jenjang, nama kembar, dan sudah disunting, serta
 * tombol unduh Excel yang mengikuti saringan aktif. Tiap baris punya
 * tautan Ubah ke formulir sekolah. Bila ada nama kembar, tombol "Tambahkan nama
 * desa ke nama yang sama" membuka `admin/sekolah/nama-desa` (K114). Data dari
 * `SekolahController::index()`. Tata letak `admin_tw` (F9-15).
 *
 * Penanda uji: peringatan nama kembar `[data-peringatan-kembar]`.
 *
 * @var CodeIgniter\View\View  $this
 * @var list<string>           $kabupaten
 * @var array<string, string>  $jenjang      kode data sekolah => label
 * @var int                    $jumlahKembar sekolah yang namanya sama dengan sekolah lain
 * @var int                    $jumlahUsulan usulan nama dari pendaftar yang menunggu (K99)
 */

use App\Controllers\Admin\SekolahController;

$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$pilihan = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="max-w-3xl text-gray-700">
        Data sekolah yang dicari lewat NPSN saat pendaftaran. Nama yang disimpan di sini dipakai untuk pendaftaran baru,
        rekap, dan bukti pendaftaran.
    </p>

    <div class="flex flex-wrap gap-2">
        <a class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg px-5 py-2.5 text-center text-sm font-semibold transition-colors focus:outline-none focus:ring-4 <?= $jumlahUsulan > 0 ? 'bg-primary-700 text-white hover:bg-primary-800 focus:ring-primary-300' : 'border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:ring-gray-100' ?>" href="<?= site_url('admin/sekolah/usulan') ?>" id="tautan-usulan">
            <?= ikon('message-square-text') ?> Usulan nama dari pendaftar
            <?php if ($jumlahUsulan > 0) : ?>
                <span class="ms-1 inline-flex items-center rounded-full bg-white px-2 py-0.5 text-xs font-semibold text-primary-800"><?= $jumlahUsulan ?> menunggu</span>
            <?php endif ?>
        </a>
        <?php if ($jumlahKembar > 0) : ?>
            <?= komponen('tombol', ['label' => 'Tambahkan nama desa ke nama yang sama', 'href' => site_url('admin/sekolah/nama-desa'), 'varian' => 'kedua', 'ikon' => 'map-pin', 'atribut' => ['id' => 'tautan-nama-desa']]) ?>
        <?php endif ?>
    </div>

    <?php if ($jumlahKembar > 0) : ?>
        <div class="flex items-start gap-3 rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-900" role="status" data-peringatan-kembar>
            <?= ikon('triangle-alert', 'mt-0.5 shrink-0') ?>
            <div class="min-w-0">
                <strong><?= number_format($jumlahKembar, 0, ',', '.') ?> sekolah</strong> memakai nama yang sama dengan sekolah lain,
                misalnya beberapa MI dengan nama MUHAMMADIYAH di desa berbeda. Tekan
                <a class="font-semibold underline hover:no-underline" href="<?= site_url('admin/sekolah/nama-desa') ?>">Tambahkan nama desa ke nama yang sama</a> untuk memberi semuanya
                nama desa sekaligus, contohnya MIS MUHAMMADIYAH JATI. Bisa juga satu per satu: pilih <em>Sama dengan sekolah lain</em>
                di saringan Nama, lalu tekan Ubah.
            </div>
        </div>
    <?php endif ?>

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-label="Daftar sekolah">
        <form id="saringan-sekolah" class="flex flex-wrap items-end gap-3" role="search" aria-label="Saring daftar sekolah" data-saringan-tabel>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-kabupaten">Kabupaten</label>
                <select class="<?= $pilihan ?>" id="saring-kabupaten" name="kabupaten">
                    <option value="">Semua</option>
                    <?php foreach ($kabupaten as $nama) : ?>
                        <option value="<?= esc($nama, 'attr') ?>"><?= esc($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-jenjang">Jenjang</label>
                <select class="<?= $pilihan ?>" id="saring-jenjang" name="jenjang">
                    <option value="">Semua</option>
                    <?php foreach ($jenjang as $kode => $labelJenjang) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelJenjang) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-nama">Nama</label>
                <select class="<?= $pilihan ?>" id="saring-nama" name="nama">
                    <option value="">Semua</option>
                    <?php foreach (SekolahController::SARING_NAMA as $kode => $labelNama) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelNama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-disunting">Disunting</label>
                <select class="<?= $pilihan ?>" id="saring-disunting" name="disunting">
                    <option value="">Semua</option>
                    <?php foreach (SekolahController::SARING_DISUNTING as $kode => $labelDisunting) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelDisunting) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex max-w-full flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Kosongkan', 'tipe' => 'reset', 'varian' => 'kedua', 'ikon' => 'circle-x']) ?>
                <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/sekolah/excel'), 'varian' => 'kedua', 'ikon' => 'file-spreadsheet', 'atribut' => ['id' => 'excel-sekolah']]) ?>
            </div>
        </form>

        <table id="tabel-sekolah" class="w-full" data-tabel-admin
            data-alamat="<?= site_url('admin/sekolah/data') ?>"
            data-saringan="#saringan-sekolah" data-excel="#excel-sekolah"
            data-kosong="<?= esc(SekolahController::PESAN_KOSONG, 'attr') ?>"
            data-kosong-saring="Tidak ada sekolah yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
            <caption class="sr-only">Daftar sekolah</caption>
            <thead>
                <tr>
                    <th data-bentuk="nomor" class="text-end">No</th>
                    <th data-kolom="npsn">NPSN</th>
                    <th data-kolom="nama">Nama</th>
                    <th data-kolom="bentuk">Bentuk</th>
                    <th data-kolom="jenjang">Jenjang</th>
                    <th data-kolom="status">Status</th>
                    <th data-kolom="desa_kelurahan">Desa/kelurahan</th>
                    <th data-kolom="kecamatan">Kecamatan</th>
                    <th data-kolom="kabupaten">Kabupaten</th>
                    <th data-kolom="nama_sama" data-bentuk="angka" class="text-end">Nama sama</th>
                    <th data-kolom="disunting">Disunting</th>
                    <th data-kolom="ubah" data-bentuk="tautan" data-urut="tidak"><span class="sr-only">Tindakan</span></th>
                </tr>
            </thead>
        </table>
    </section>
</div>
<?= $this->endSection() ?>
