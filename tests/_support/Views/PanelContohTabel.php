<?php
/**
 * Halaman contoh dengan tabel data panel (Tahap 6), hanya dipakai uji
 * `tests/fitur/PanelTataLetakTabelTest.php`. Pola yang sama dipakai halaman
 * Pendaftaran, Atlet, dan Rekap.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Tabel contoh<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid">
    <form id="saringan-contoh" class="saringan-tabel" role="search" aria-label="Saring data contoh">
        <div>
            <label class="form-label" for="saring-status">Status</label>
            <select class="form-select" id="saring-status" name="status">
                <option value="">Semua</option>
                <option value="lunas">Lunas</option>
                <option value="menunggu">Menunggu</option>
            </select>
        </div>
    </form>
    <a id="excel-contoh" class="btn btn-outline-primary" href="<?= site_url('admin/pendaftaran/excel') ?>">Unduh Excel</a>
    <table id="tabel-contoh" class="table" data-tabel-admin
        data-alamat="<?= site_url('admin/pendaftaran/data') ?>"
        data-saringan="#saringan-contoh" data-excel="#excel-contoh"
        data-kosong="Belum ada pendaftaran. Pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini.">
        <thead>
            <tr>
                <th data-kolom="id_pendaftaran">ID</th>
                <th data-kolom="nama_sekolah">Sekolah</th>
                <th data-kolom="total" data-bentuk="rupiah" class="text-end">Total</th>
                <th data-kolom="status" data-bentuk="status">Status</th>
            </tr>
        </thead>
    </table>
</div>
<?= $this->endSection() ?>
