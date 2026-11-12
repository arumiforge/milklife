<?php
/**
 * Daftar semua atlet (F6-03): tabel data dari `admin/atlet/data` dengan
 * saringan kelompok umur, jenis kelamin, nomor lomba, dan status
 * pembayaran, serta tombol unduh Excel yang mengikuti saringan aktif.
 * Hanya untuk melihat dan mengunduh; ID pendaftaran menaut ke rincian
 * pendaftaran, tempat atlet diubah. Data dari `AtletController::index()`.
 *
 * @var CodeIgniter\View\View                                 $this
 * @var list<array{id: int|string, nama: string}>              $kelompokUmur
 * @var array<string, list<array{id: int, nama: string}>>      $nomorLomba   nama kelompok umur => nomor lomba
 * @var array<string, string>                                 $jenisKelamin kode => label
 */

use App\Controllers\Admin\AtletController;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Atlet<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Atlet<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="text-body-secondary">
        Semua atlet dari setiap pendaftaran. Untuk mengubah data atlet, buka rincian pendaftarannya lewat ID pendaftaran.
    </p>

    <div class="card">
        <div class="card-body">
            <form id="saringan-atlet" class="saringan-tabel" role="search" aria-label="Saring daftar atlet">
                <div>
                    <label class="form-label" for="saring-kelompok-umur">Kelompok umur</label>
                    <select class="form-select" id="saring-kelompok-umur" name="kelompok_umur">
                        <option value="">Semua</option>
                        <?php foreach ($kelompokUmur as $ku) : ?>
                            <option value="<?= (int) $ku['id'] ?>"><?= esc($ku['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-jenis-kelamin">Jenis kelamin</label>
                    <select class="form-select" id="saring-jenis-kelamin" name="jenis_kelamin">
                        <option value="">Semua</option>
                        <?php foreach ($jenisKelamin as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-nomor-lomba">Nomor lomba</label>
                    <select class="form-select" id="saring-nomor-lomba" name="nomor_lomba">
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
                <div>
                    <label class="form-label" for="saring-status">Status pembayaran</label>
                    <select class="form-select" id="saring-status" name="status">
                        <option value="">Semua</option>
                        <?php foreach (AtletController::STATUS as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="saringan-tabel-tombol">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Kosongkan
                    </button>
                    <a id="excel-atlet" class="btn btn-outline-primary" href="<?= site_url('admin/atlet/excel') ?>">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Unduh Excel
                    </a>
                </div>
            </form>

            <table id="tabel-atlet" class="table table-hover align-middle w-100" data-tabel-admin
                data-alamat="<?= site_url('admin/atlet/data') ?>"
                data-saringan="#saringan-atlet" data-excel="#excel-atlet"
                data-kosong="<?= esc(AtletController::PESAN_KOSONG, 'attr') ?>"
                data-kosong-saring="Tidak ada atlet yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
                <caption class="visually-hidden">Daftar atlet</caption>
                <thead>
                    <tr>
                        <th data-kolom="nomor_dada">Nomor dada</th>
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
        </div>
    </div>
</div>
<?= $this->endSection() ?>
