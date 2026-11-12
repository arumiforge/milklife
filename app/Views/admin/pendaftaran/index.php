<?php
/**
 * Daftar pendaftaran (F6-02): tabel dari `admin/pendaftaran/data`
 * (`aset/js/tabel-admin.js`), saringan status, jenjang, dan kabupaten, dan
 * tombol Excel yang mengikuti saringan aktif. Data dari
 * `PendaftaranController::index()`.
 *
 * Bentuk kolom tambahan (`idPendaftaran`, `rincian`) didaftarkan
 * `aset/js/admin-pendaftaran.js`.
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $jenjang   kode => label
 * @var list<string>           $kabupaten
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="text-body-secondary mb-4 pengaturan-pengantar">
        Semua pendaftaran yang masuk lewat halaman Pendaftaran. Buka rinciannya untuk melihat atlet,
        menandai Lunas setelah transfer diperiksa, atau menghapus pendaftaran yang batal.
    </p>

    <section class="card" aria-labelledby="judul-tabel-pendaftaran">
        <div class="card-body p-3 p-lg-4">
            <h2 id="judul-tabel-pendaftaran" class="visually-hidden">Daftar pendaftaran</h2>

            <form id="saringan-pendaftaran" class="saringan-tabel" role="search" aria-label="Saring pendaftaran">
                <div>
                    <label class="form-label" for="saring-status">Status</label>
                    <select class="form-select" id="saring-status" name="status">
                        <option value="">Semua status</option>
                        <option value="lunas">Lunas</option>
                        <option value="menunggu">Menunggu</option>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-jenjang">Jenjang</label>
                    <select class="form-select" id="saring-jenjang" name="jenjang">
                        <option value="">Semua jenjang</option>
                        <?php foreach ($jenjang as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="form-label" for="saring-kabupaten">Kabupaten/Kota</label>
                    <select class="form-select" id="saring-kabupaten" name="kabupaten">
                        <option value="">Semua kabupaten</option>
                        <?php foreach ($kabupaten as $nama) : ?>
                            <option value="<?= esc($nama, 'attr') ?>"><?= esc($nama) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="saringan-tabel-tombol">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle" aria-hidden="true"></i>
                        Kosongkan saringan
                    </button>
                    <a id="excel-pendaftaran" class="btn btn-outline-primary" href="<?= site_url('admin/pendaftaran/excel') ?>">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i>
                        Unduh Excel
                    </a>
                </div>
            </form>

            <table id="tabel-pendaftaran" class="table table-hover align-middle w-100" data-tabel-admin
                data-alamat="<?= site_url('admin/pendaftaran/data') ?>"
                data-saringan="#saringan-pendaftaran" data-excel="#excel-pendaftaran"
                data-kosong="Belum ada pendaftaran. Pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini."
                data-kosong-saring="Tidak ada pendaftaran yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
                <thead>
                    <tr>
                        <th data-kolom="id_pendaftaran" data-bentuk="idPendaftaran">ID</th>
                        <th data-kolom="tanggal" data-bentuk="tanggal">Tanggal</th>
                        <th data-kolom="nama_sekolah">Sekolah</th>
                        <th data-kolom="npsn">NPSN</th>
                        <th data-kolom="jenjang">Jenjang</th>
                        <th data-kolom="kabupaten">Kabupaten/Kota</th>
                        <th data-kolom="jumlah_atlet" data-bentuk="angka" class="text-end">Atlet</th>
                        <th data-kolom="total" data-bentuk="rupiah" class="text-end">Total</th>
                        <th data-kolom="status" data-bentuk="status">Status</th>
                        <th data-kolom="alamat" data-bentuk="rincian" data-urut="tidak" class="text-end"><span class="visually-hidden">Tindakan</span></th>
                    </tr>
                </thead>
            </table>
        </div>
    </section>

    <p class="text-body-secondary small mt-3 mb-0">
        Pencarian mencocokkan ID pendaftaran, nama sekolah, dan NPSN. Excel memuat semua baris yang cocok
        dengan pencarian dan saringan, tanpa batas halaman.
    </p>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/admin-pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
