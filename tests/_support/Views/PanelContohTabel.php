<?php
/**
 * Halaman contoh dengan tabel data panel (Tahap 6), hanya dipakai uji
 * `tests/fitur/PanelTataLetakTabelTest.php`. Pola yang sama dipakai halaman
 * Pendaftaran, Atlet, dan Rekap.
 *
 * @var CodeIgniter\View\View $this
 */
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Tabel contoh<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:p-6">
    <form id="saringan-contoh" class="flex flex-wrap gap-3" role="search" aria-label="Saring data contoh">
        <div>
            <label class="mb-1.5 block text-sm font-semibold text-gray-900" for="saring-status">Status</label>
            <select class="block min-h-11 rounded-lg border border-gray-300 bg-white p-2.5 text-sm" id="saring-status" name="status">
                <option value="">Semua</option>
                <option value="lunas">Lunas</option>
                <option value="menunggu">Menunggu</option>
            </select>
        </div>
    </form>
    <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/pendaftaran/excel'), 'varian' => 'kedua', 'ikon' => 'download', 'atribut' => ['id' => 'excel-contoh']]) ?>
    <table id="tabel-contoh" data-tabel-admin
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
