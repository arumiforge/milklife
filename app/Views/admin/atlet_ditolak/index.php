<?php
/**
 * Daftar semua atlet yang ditolak panitia (K116, K117): tabel data dari
 * `admin/atlet-ditolak/data` dengan saringan keadaan (belum atau sudah
 * didaftarkan ulang) dan unduh Excel. Kotak jumlah di atas sekaligus menjadi
 * tombol saringan cepat (`aset/js/admin-atlet-ditolak.js`, `aria-pressed`).
 * Data dari `AtletDitolakController::index()`. Tata letak `admin_tw` (F9-15);
 * isi kolom Keadaan dari `admin/atlet_ditolak/_keadaan`.
 *
 * @var CodeIgniter\View\View                       $this
 * @var array{semua: int, belum: int, sudah: int}   $jumlah
 */

use App\Controllers\Admin\AtletDitolakController;

$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$pilihan = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
$kotak   = 'flex min-h-11 flex-col items-start gap-0.5 rounded-xl border border-gray-200 bg-white px-4 py-3 text-start text-sm font-semibold text-gray-700 shadow-lembut hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-100 aria-pressed:border-primary-300 aria-pressed:bg-primary-50 aria-pressed:ring-2 aria-pressed:ring-primary-300';
$angka   = 'text-2xl leading-tight font-extrabold text-primary-900';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Atlet ditolak<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <p class="text-gray-700">
        Atlet yang dikeluarkan panitia dari pendaftarannya. Sekolah melihat alasannya di Cek Pendaftaran sampai atletnya didaftarkan lagi.
        Untuk menolak atlet, buka rincian pendaftaran yang masih Menunggu lalu pilih <strong>Tolak atlet</strong>.
    </p>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(10rem,1fr))] gap-3" role="group" aria-label="Saring cepat menurut keadaan" data-ringkasan-ditolak>
        <button type="button" class="<?= $kotak ?>" data-saring-keadaan="" aria-pressed="true">
            <span class="<?= $angka ?>"><?= $jumlah['semua'] ?></span>
            <span>Semua</span>
        </button>
        <button type="button" class="<?= $kotak ?>" data-saring-keadaan="belum" aria-pressed="false">
            <span class="<?= $angka ?>"><?= $jumlah['belum'] ?></span>
            <span>Belum didaftarkan ulang</span>
        </button>
        <button type="button" class="<?= $kotak ?>" data-saring-keadaan="sudah" aria-pressed="false">
            <span class="<?= $angka ?>"><?= $jumlah['sudah'] ?></span>
            <span>Sudah didaftarkan ulang</span>
        </button>
    </div>

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-label="Daftar atlet ditolak">
        <form id="saringan-atlet-ditolak" class="grid grid-cols-2 items-end gap-3 lg:grid-cols-4" role="search" aria-label="Saring daftar atlet ditolak" data-saringan-tabel>
            <div class="min-w-0">
                <label class="<?= $label ?>" for="saring-keadaan">Keadaan</label>
                <select class="<?= $pilihan ?>" id="saring-keadaan" name="keadaan">
                    <option value="">Semua</option>
                    <?php foreach (AtletDitolakController::KEADAAN as $kode => $labelKeadaan) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelKeadaan) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-span-full flex flex-wrap gap-2" data-tombol-aksi>
                <?= komponen('tombol', ['label' => 'Kosongkan', 'tipe' => 'reset', 'varian' => 'kedua', 'ikon' => 'circle-x']) ?>
                <?= komponen('tombol_unduh', ['jenis' => 'excel', 'href' => site_url('admin/atlet-ditolak/excel'), 'atribut' => ['id' => 'excel-atlet-ditolak']]) ?>
            </div>
        </form>

        <?php // Kolom Alasan cukup lebar untuk dibaca, kolom pendek tidak terpotong (K117). ?>
        <table id="tabel-atlet-ditolak" class="w-full [&_td:nth-child(10)]:min-w-64 [&_td:nth-child(11)]:whitespace-nowrap [&_td:nth-child(3)]:whitespace-nowrap [&_td:nth-child(4)]:whitespace-nowrap [&_td:nth-child(6)]:whitespace-nowrap [&_td:nth-child(7)]:whitespace-nowrap" data-tabel-admin
            data-alamat="<?= site_url('admin/atlet-ditolak/data') ?>"
            data-saringan="#saringan-atlet-ditolak" data-excel="#excel-atlet-ditolak"
            data-kosong="<?= esc(AtletDitolakController::PESAN_KOSONG, 'attr') ?>"
            data-kosong-saring="Tidak ada atlet ditolak yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
            <caption class="sr-only">Daftar atlet ditolak</caption>
            <thead>
                <tr>
                    <th data-bentuk="nomor" class="text-end">No</th>
                    <th data-kolom="nama">Nama</th>
                    <th data-kolom="nisn">NISN</th>
                    <th data-kolom="tanggal_lahir" data-bentuk="tanggal">Tanggal lahir</th>
                    <th data-kolom="nama_sekolah">Sekolah</th>
                    <th data-kolom="id_pendaftaran" data-bentuk="tautan">ID pendaftaran</th>
                    <th data-kolom="kelompok_umur">Didaftarkan di</th>
                    <th data-kolom="nomor_lomba" data-urut="tidak">Nomor lomba</th>
                    <th data-kolom="kelompok_umur_sesuai">Seharusnya</th>
                    <th data-kolom="alasan" data-bentuk="html" data-urut="tidak">Alasan</th>
                    <th data-kolom="dibuat_pada" data-bentuk="tanggal">Ditolak</th>
                    <th data-kolom="ditolak_oleh">Oleh</th>
                    <th data-kolom="keadaan" data-bentuk="html" data-urut="tidak">Keadaan</th>
                </tr>
            </thead>
        </table>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/admin-atlet-ditolak.js') ?>"></script>
<?= $this->endSection() ?>
