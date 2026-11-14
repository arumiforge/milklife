<?php
/**
 * Daftar sekolah (K97): tabel data dari `admin/sekolah/data` dengan
 * saringan kabupaten, jenjang, nama kembar, dan sudah disunting, serta
 * tombol unduh Excel yang mengikuti saringan aktif. Tiap baris punya
 * tautan Ubah ke formulir sekolah. Data dari `SekolahController::index()`.
 *
 * @var CodeIgniter\View\View  $this
 * @var list<string>           $kabupaten
 * @var array<string, string>  $jenjang      kode data sekolah => label
 * @var int                    $jumlahKembar sekolah yang namanya sama dengan sekolah lain
 */

use App\Controllers\Admin\SekolahController;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="teks-info mb-3">
        Data sekolah yang dicari lewat NPSN saat pendaftaran. Nama yang disimpan di sini dipakai untuk pendaftaran baru,
        rekap, dan bukti pendaftaran.
    </p>

    <?php if ($jumlahKembar > 0) : ?>
        <div class="alert alert-warning d-flex align-items-start gap-2" role="status">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0" aria-hidden="true"></i>
            <div>
                <strong><?= number_format($jumlahKembar, 0, ',', '.') ?> sekolah</strong> memakai nama yang sama dengan sekolah lain,
                misalnya beberapa MI dengan nama MUHAMMADIYAH di desa berbeda. Pilih <em>Sama dengan sekolah lain</em> di saringan Nama,
                lalu tekan Ubah dan tambahkan pembeda seperti nama desa, contohnya MIS MUHAMMADIYAH JATI.
            </div>
        </div>
    <?php endif ?>

    <div class="card">
        <div class="card-body">
            <form id="saringan-sekolah" class="saringan-tabel" role="search" aria-label="Saring daftar sekolah">
                <div>
                    <label class="form-label" for="saring-kabupaten">Kabupaten</label>
                    <select class="form-select" id="saring-kabupaten" name="kabupaten">
                        <option value="">Semua</option>
                        <?php foreach ($kabupaten as $nama) : ?>
                            <option value="<?= esc($nama, 'attr') ?>"><?= esc($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-jenjang">Jenjang</label>
                    <select class="form-select" id="saring-jenjang" name="jenjang">
                        <option value="">Semua</option>
                        <?php foreach ($jenjang as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-nama">Nama</label>
                    <select class="form-select" id="saring-nama" name="nama">
                        <option value="">Semua</option>
                        <?php foreach (SekolahController::SARING_NAMA as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-disunting">Disunting</label>
                    <select class="form-select" id="saring-disunting" name="disunting">
                        <option value="">Semua</option>
                        <?php foreach (SekolahController::SARING_DISUNTING as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="saringan-tabel-tombol">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Kosongkan
                    </button>
                    <a id="excel-sekolah" class="btn btn-outline-primary" href="<?= site_url('admin/sekolah/excel') ?>">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Unduh Excel
                    </a>
                </div>
            </form>

            <table id="tabel-sekolah" class="table table-hover align-middle w-100" data-tabel-admin
                data-alamat="<?= site_url('admin/sekolah/data') ?>"
                data-saringan="#saringan-sekolah" data-excel="#excel-sekolah"
                data-kosong="<?= esc(SekolahController::PESAN_KOSONG, 'attr') ?>"
                data-kosong-saring="Tidak ada sekolah yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
                <caption class="visually-hidden">Daftar sekolah</caption>
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
                        <th data-kolom="ubah" data-bentuk="tautan" data-urut="tidak"><span class="visually-hidden">Tindakan</span></th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
