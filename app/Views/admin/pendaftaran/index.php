<?php
/**
 * Daftar pendaftaran (F6-02): tabel dari `admin/pendaftaran/data`
 * (`aset/js/tabel-admin.js`), saringan status, jenjang, dan kabupaten, dan
 * tombol Excel yang mengikuti saringan aktif. Data dari
 * `PendaftaranController::index()`. Tata letak `admin_tw` (F9-15).
 *
 * Bentuk kolom tambahan (`pilih`, `idPendaftaran`, `rincian`) didaftarkan
 * `aset/js/admin-pendaftaran.js`. Kotak centang tiap baris milik formulir
 * `#form-massal` (atribut `form`): Tandai Lunas, Kembalikan ke Menunggu, atau
 * Hapus untuk yang dicentang di halaman tabel ini (K76).
 *
 * F8-02: kolom Status diberi tanda "Bukti baru" (bentuk `statusBukti`) dan
 * saringan "Bukti baru belum diperiksa" (`bukti=baru`, juga bisa dibuka
 * langsung dari kartu dasbor lewat `admin/pendaftaran?bukti=baru`).
 *
 * @var CodeIgniter\View\View  $this
 * @var array<string, string>  $jenjang   kode => label
 * @var list<string>           $kabupaten
 */

$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$pilihan = 'block min-h-11 w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Pendaftaran<?= $this->endSection() ?>

<?= $this->section('tabel') ?>ya<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <p class="max-w-3xl text-gray-700">
        Semua pendaftaran yang masuk lewat halaman Pendaftaran. Buka rinciannya untuk melihat atlet,
        menandai Lunas setelah transfer diperiksa, atau menghapus pendaftaran yang batal.
        Riwayat pendaftaran yang sudah dihapus bisa dicari di <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url('admin/riwayat') ?>">Riwayat pendaftaran</a>.
    </p>

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-tabel-pendaftaran">
        <h2 id="judul-tabel-pendaftaran" class="sr-only">Daftar pendaftaran</h2>

        <form id="saringan-pendaftaran" class="flex flex-wrap items-end gap-3" role="search" aria-label="Saring pendaftaran" data-saringan-tabel>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-status">Status</label>
                <select class="<?= $pilihan ?>" id="saring-status" name="status">
                    <option value="">Semua status</option>
                    <option value="lunas">Lunas</option>
                    <option value="menunggu">Menunggu</option>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-bukti">Bukti bayar</label>
                <select class="<?= $pilihan ?>" id="saring-bukti" name="bukti">
                    <option value="">Semua</option>
                    <option value="baru">Bukti baru belum diperiksa</option>
                    <option value="ada">Sudah ada bukti</option>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-jenjang">Jenjang</label>
                <select class="<?= $pilihan ?>" id="saring-jenjang" name="jenjang">
                    <option value="">Semua jenjang</option>
                    <?php foreach ($jenjang as $kode => $labelJenjang) : ?>
                        <option value="<?= esc($kode, 'attr') ?>"><?= esc($labelJenjang) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="min-w-0 flex-[1_1_12rem]">
                <label class="<?= $label ?>" for="saring-kabupaten">Kabupaten/Kota</label>
                <select class="<?= $pilihan ?>" id="saring-kabupaten" name="kabupaten">
                    <option value="">Semua kabupaten</option>
                    <?php foreach ($kabupaten as $nama) : ?>
                        <option value="<?= esc($nama, 'attr') ?>"><?= esc($nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex max-w-full flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Kosongkan saringan', 'tipe' => 'reset', 'varian' => 'kedua', 'ikon' => 'circle-x']) ?>
                <?= komponen('tombol', ['label' => 'Unduh Excel', 'href' => site_url('admin/pendaftaran/excel'), 'varian' => 'kedua', 'ikon' => 'file-spreadsheet', 'atribut' => ['id' => 'excel-pendaftaran']]) ?>
                <?= komponen('tombol', ['label' => 'Backup data', 'href' => site_url('admin/pendaftaran/ekspor'), 'varian' => 'kedua', 'ikon' => 'cloud-download', 'atribut' => ['id' => 'ekspor-pendaftaran', 'title' => 'Semua pendaftaran, foto, dan bukti bayar dalam satu zip, misalnya untuk pindah server']]) ?>
            </div>
        </form>

        <form id="form-massal" class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-xl border border-transparent bg-gray-50 px-3 py-2 text-sm text-gray-700 [&.bilah-massal-aktif]:border-primary-300 [&.bilah-massal-aktif]:bg-primary-50 [&.bilah-massal-aktif]:font-semibold [&.bilah-massal-aktif]:text-primary-900"
            action="<?= site_url('admin/pendaftaran/massal') ?>" method="post"
            data-konfirmasi="" aria-label="Tindakan untuk pendaftaran yang dicentang" data-bilah-massal>
            <?= csrf_field() ?>
            <p class="flex items-center gap-2" aria-live="polite">
                <?= ikon('square-check', 'size-4') ?>
                <span><span id="jumlah-dipilih">0</span> pendaftaran dicentang</span>
            </p>
            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Tandai Lunas', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'circle-check', 'kelas' => 'min-h-11 disabled:pointer-events-none disabled:opacity-50', 'atribut' => ['name' => 'tindakan', 'value' => 'lunas', 'disabled' => true]]) ?>
                <?= komponen('tombol', ['label' => 'Kembalikan ke Menunggu', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'hourglass', 'kelas' => 'min-h-11 disabled:pointer-events-none disabled:opacity-50', 'atribut' => ['name' => 'tindakan', 'value' => 'menunggu', 'disabled' => true]]) ?>
                <?= komponen('tombol', ['label' => 'Hapus', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'trash-2', 'kelas' => 'min-h-11 text-red-700! disabled:pointer-events-none disabled:opacity-50', 'atribut' => ['name' => 'tindakan', 'value' => 'hapus', 'disabled' => true]]) ?>
            </div>
        </form>

        <table id="tabel-pendaftaran" class="w-full" data-tabel-admin
            data-alamat="<?= site_url('admin/pendaftaran/data') ?>"
            data-saringan="#saringan-pendaftaran" data-excel="#excel-pendaftaran"
            data-kosong="Belum ada pendaftaran. Pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini."
            data-kosong-saring="Tidak ada pendaftaran yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.">
            <thead>
                <tr>
                    <th data-kolom="id" data-bentuk="pilih" data-urut="tidak" class="w-10">
                        <input class="size-5 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500" type="checkbox" id="pilih-semua" aria-label="Centang semua pendaftaran di halaman ini">
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
                    <th data-kolom="status" data-bentuk="statusBukti">Status</th>
                    <th data-kolom="alamat" data-bentuk="rincian" data-urut="tidak" class="text-end"><span class="sr-only">Tindakan</span></th>
                </tr>
            </thead>
        </table>
    </section>

    <p class="text-sm text-gray-600">
        Pencarian mencocokkan ID pendaftaran, nama sekolah, dan NPSN. Excel memuat semua baris yang cocok
        dengan pencarian dan saringan, tanpa batas halaman.
    </p>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-pendaftaran.js') ?>"></script>
<?= $this->endSection() ?>
