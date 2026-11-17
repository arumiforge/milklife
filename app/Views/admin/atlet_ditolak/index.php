<?php
/**
 * Daftar semua atlet yang ditolak panitia (K116, K117): tabel data dari
 * `admin/atlet-ditolak/data` dengan saringan keadaan (belum atau sudah
 * didaftarkan ulang) dan unduh Excel. Kotak jumlah di atas sekaligus menjadi
 * tombol saringan cepat. Data dari `AtletDitolakController::index()`.
 *
 * @var CodeIgniter\View\View                       $this
 * @var array{semua: int, belum: int, sudah: int}   $jumlah
 */

use App\Controllers\Admin\AtletDitolakController;

?>
<?= $this->extend('tata_letak/admin') ?>

<?= $this->section('judul') ?>Atlet ditolak<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="container-fluid px-3 px-lg-4">
    <p class="teks-info mb-3">
        Atlet yang dikeluarkan panitia dari pendaftarannya. Sekolah melihat alasannya di Cek Pendaftaran sampai atletnya didaftarkan lagi.
        Untuk menolak atlet, buka rincian pendaftaran yang masih Menunggu lalu pilih <strong>Tolak atlet</strong>.
    </p>

    <div class="ringkasan-ditolak mb-4" role="group" aria-label="Saring cepat menurut keadaan">
        <button type="button" class="ringkasan-ditolak-kotak" data-saring-keadaan="" aria-pressed="true">
            <span class="ringkasan-ditolak-angka"><?= $jumlah['semua'] ?></span>
            <span>Semua</span>
        </button>
        <button type="button" class="ringkasan-ditolak-kotak ringkasan-ditolak-belum" data-saring-keadaan="belum" aria-pressed="false">
            <span class="ringkasan-ditolak-angka"><?= $jumlah['belum'] ?></span>
            <span>Belum didaftarkan ulang</span>
        </button>
        <button type="button" class="ringkasan-ditolak-kotak ringkasan-ditolak-sudah" data-saring-keadaan="sudah" aria-pressed="false">
            <span class="ringkasan-ditolak-angka"><?= $jumlah['sudah'] ?></span>
            <span>Sudah didaftarkan ulang</span>
        </button>
    </div>

    <div class="card">
        <div class="card-body">
            <form id="saringan-atlet-ditolak" class="saringan-tabel" role="search" aria-label="Saring daftar atlet ditolak">
                <div>
                    <label class="form-label" for="saring-keadaan">Keadaan</label>
                    <select class="form-select" id="saring-keadaan" name="keadaan">
                        <option value="">Semua</option>
                        <?php foreach (AtletDitolakController::KEADAAN as $kode => $label) : ?>
                            <option value="<?= esc($kode, 'attr') ?>"><?= esc($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="saringan-tabel-tombol">
                    <button type="reset" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle" aria-hidden="true"></i> Kosongkan
                    </button>
                    <a id="excel-atlet-ditolak" class="btn btn-outline-primary" href="<?= site_url('admin/atlet-ditolak/excel') ?>">
                        <i class="bi bi-file-earmark-spreadsheet" aria-hidden="true"></i> Unduh Excel
                    </a>
                </div>
            </form>

            <table id="tabel-atlet-ditolak" class="table table-hover align-middle w-100" data-tabel-admin
                data-alamat="<?= site_url('admin/atlet-ditolak/data') ?>"
                data-saringan="#saringan-atlet-ditolak" data-excel="#excel-atlet-ditolak"
                data-kosong="<?= esc(AtletDitolakController::PESAN_KOSONG, 'attr') ?>"
                data-kosong-saring="Tidak ada atlet ditolak yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
                <caption class="visually-hidden">Daftar atlet ditolak</caption>
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
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script>
    // Kotak jumlah sebagai saringan cepat: mengisi pilihan Keadaan lalu memicu saringan tabel.
    document.addEventListener('DOMContentLoaded', function () {
        var pilih = document.getElementById('saring-keadaan');
        var kotak = document.querySelectorAll('[data-saring-keadaan]');
        function tandai() {
            kotak.forEach(function (k) {
                k.setAttribute('aria-pressed', k.getAttribute('data-saring-keadaan') === pilih.value ? 'true' : 'false');
            });
        }
        kotak.forEach(function (k) {
            k.addEventListener('click', function () {
                pilih.value = k.getAttribute('data-saring-keadaan');
                pilih.dispatchEvent(new Event('change', { bubbles: true }));
                tandai();
            });
        });
        pilih.addEventListener('change', tandai);
        pilih.form.addEventListener('reset', function () { setTimeout(tandai, 0); });
    });
</script>
<?= $this->endSection() ?>
