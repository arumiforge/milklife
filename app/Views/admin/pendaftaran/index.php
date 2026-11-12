<?php
/**
 * Daftar pendaftaran (F6-02): tabel dari `admin/pendaftaran/data`
 * (`aset/js/tabel-admin.js`), saringan status, jenjang, dan kabupaten, dan
 * tombol Excel yang mengikuti saringan aktif. Data dari
 * `PendaftaranController::index()`.
 *
 * Bentuk kolom tambahan (`pilih`, `idPendaftaran`, `rincian`) didaftarkan
 * `aset/js/admin-pendaftaran.js`. Kotak centang tiap baris milik formulir
 * `#form-massal` (atribut `form`): Tandai Lunas, Kembalikan ke Menunggu, atau
 * Hapus untuk yang dicentang di halaman tabel ini (K76).
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
    <p class="teks-info mb-4">
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
                    <a id="ekspor-pendaftaran" class="btn btn-outline-primary" href="<?= site_url('admin/pendaftaran/ekspor') ?>"
                        title="Semua pendaftaran, foto, dan bukti bayar dalam satu zip, misalnya untuk pindah server">
                        <i class="bi bi-cloud-arrow-down" aria-hidden="true"></i>
                        Backup data
                    </a>
                </div>
            </form>

            <form id="form-massal" class="bilah-massal" action="<?= site_url('admin/pendaftaran/massal') ?>" method="post"
                data-konfirmasi="" aria-label="Tindakan untuk pendaftaran yang dicentang">
                <?= csrf_field() ?>
                <p class="bilah-massal-jumlah mb-0" aria-live="polite">
                    <i class="bi bi-check2-square" aria-hidden="true"></i>
                    <span id="jumlah-dipilih">0</span> pendaftaran dicentang
                </p>
                <div class="bilah-massal-tombol">
                    <button type="submit" class="btn btn-sm btn-outline-success" name="tindakan" value="lunas" disabled>
                        <i class="bi bi-check-circle" aria-hidden="true"></i> Tandai Lunas
                    </button>
                    <button type="submit" class="btn btn-sm btn-outline-secondary" name="tindakan" value="menunggu" disabled>
                        <i class="bi bi-hourglass-split" aria-hidden="true"></i> Kembalikan ke Menunggu
                    </button>
                    <button type="submit" class="btn btn-sm btn-outline-danger" name="tindakan" value="hapus" disabled>
                        <i class="bi bi-trash" aria-hidden="true"></i> Hapus
                    </button>
                </div>
            </form>

            <table id="tabel-pendaftaran" class="table table-hover align-middle w-100" data-tabel-admin
                data-alamat="<?= site_url('admin/pendaftaran/data') ?>"
                data-saringan="#saringan-pendaftaran" data-excel="#excel-pendaftaran"
                data-kosong="Belum ada pendaftaran. Pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini."
                data-kosong-saring="Tidak ada pendaftaran yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
                <thead>
                    <tr>
                        <th data-kolom="id" data-bentuk="pilih" data-urut="tidak" class="kolom-pilih">
                            <input class="form-check-input" type="checkbox" id="pilih-semua" aria-label="Centang semua pendaftaran di halaman ini">
                        </th>
                        <th data-bentuk="nomor" class="text-end">No</th>
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

    <p class="teks-catatan mt-3 mb-0">
        Pencarian mencocokkan ID pendaftaran, nama sekolah, dan NPSN. Excel memuat semua baris yang cocok
        dengan pencarian dan saringan, tanpa batas halaman. Centang hanya berlaku untuk halaman tabel yang sedang tampil.
        Backup data selalu memuat semua pendaftaran (tanpa saringan) beserta foto dan bukti bayar;
        untuk memasukkannya di server baru, jalankan <code>php spark pendaftaran:impor &lt;berkas.zip&gt;</code>.
    </p>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
