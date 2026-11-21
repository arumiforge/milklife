<?php
/**
 * Beri nama desa ke sekolah bernama kembar (K114): `admin/sekolah/nama-desa`.
 * Data dari `SekolahController::namaDesa()` (`NamaDesaSekolah::pratinjau()`).
 * Tata letak `admin_tw` (F9-15).
 *
 * Tabel pertama berisi nama baru yang diusulkan, masing-masing dengan kotak
 * centang (bawaannya tercentang) untuk formulir `#form-nama-desa`. Tabel kedua
 * berisi sekolah yang tidak bisa dibedakan otomatis, dengan tautan Ubah.
 * Jendela konfirmasi dari `aset/js/konfirmasi.js`, centang semua dari
 * `aset/js/centang-semua.js`.
 *
 * @var CodeIgniter\View\View $this
 * @var array{usulan: list<array<string, mixed>>, lewati: list<array<string, mixed>>} $rencana
 * @var array<string, string> $pembeda kolom => label sumber nama pembeda
 */
$usulan = $rencana['usulan'];
$lewati = $rencana['lewati'];

$kelasCentang = 'size-5 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500';
$kelasTh      = 'border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600';
$kelasTd      = 'px-4 py-3 align-top';
$kelasBaris   = 'border-b border-gray-100 last:border-b-0 hover:bg-gray-50';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Tambahkan nama desa<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Sekolah', 'alamat' => 'admin/sekolah'], ['label' => 'Tambahkan nama desa']]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <div class="max-w-3xl space-y-3 text-gray-700">
        <p>
            Sekolah yang namanya sama dengan sekolah lain diberi nama baru dengan menambahkan nama desanya, misalnya
            MIS MUHAMMADIYAH di desa Jati menjadi MIS MUHAMMADIYAH JATI. Bila desanya sudah tertulis di nama atau sama dengan
            sekolah lain yang bernama sama, nama kecamatan atau kabupaten yang dipakai.
        </p>
        <p>
            Pendaftaran yang sudah masuk dengan nama lama ikut memakai nama baru. Pendaftaran yang memakai nama usulan pendaftar
            tidak diubah. Setelah diterapkan, nama setiap sekolah masih bisa diubah lagi lewat tombol Ubah di daftar sekolah.
        </p>
    </div>

    <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-nama-baru">
        <h2 id="judul-nama-baru" class="text-lg font-bold text-gray-900">Nama baru (<?= count($usulan) ?> sekolah)</h2>

        <?php if ($usulan === []) : ?>
            <p class="text-gray-600" id="nama-baru-kosong">
                Tidak ada sekolah yang bisa diberi nama desa. Semua sekolah sudah punya nama yang berbeda dari sekolah lain,
                atau perlu diubah sendiri (lihat daftar di bawah).
            </p>
        <?php else : ?>
            <form action="<?= site_url('admin/sekolah/nama-desa') ?>" method="post" id="form-nama-desa" class="space-y-4"
                data-konfirmasi="Sekolah yang dicentang akan memakai nama baru. Pendaftaran yang memakai nama lama ikut diganti."
                data-konfirmasi-judul="Terapkan nama baru?"
                data-konfirmasi-tombol="Terapkan"
                data-konfirmasi-jenis="utama">
                <?= csrf_field() ?>

                <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                    <table class="w-full text-left text-sm text-gray-700" id="tabel-nama-baru">
                        <caption class="sr-only">Nama baru yang akan dipakai</caption>
                        <thead>
                            <tr>
                                <th scope="col" class="<?= $kelasTh ?> w-10">
                                    <input class="<?= $kelasCentang ?>" type="checkbox" id="centang-semua-nama-desa" checked
                                        data-centang-semua="#tabel-nama-baru" aria-label="Centang semua sekolah">
                                </th>
                                <th scope="col" class="<?= $kelasTh ?>">NPSN</th>
                                <th scope="col" class="<?= $kelasTh ?>">Nama sekarang</th>
                                <th scope="col" class="<?= $kelasTh ?>">Nama baru</th>
                                <th scope="col" class="<?= $kelasTh ?>">Diambil dari</th>
                                <th scope="col" class="<?= $kelasTh ?>">Letak</th>
                                <th scope="col" class="<?= $kelasTh ?> text-end">Pendaftaran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usulan as $s) : ?>
                                <tr class="<?= $kelasBaris ?>" data-npsn="<?= esc($s['npsn'], 'attr') ?>">
                                    <td class="<?= $kelasTd ?>">
                                        <input class="<?= $kelasCentang ?>" type="checkbox" name="id[]" value="<?= (int) $s['id'] ?>" checked
                                            id="pilih-<?= (int) $s['id'] ?>" aria-label="<?= esc('Pakai nama ' . $s['nama_baru'], 'attr') ?>">
                                    </td>
                                    <td class="<?= $kelasTd ?> whitespace-nowrap"><?= esc($s['npsn']) ?></td>
                                    <td class="<?= $kelasTd ?>"><?= esc($s['nama']) ?></td>
                                    <td class="<?= $kelasTd ?> font-semibold text-gray-900"><?= esc($s['nama_baru']) ?></td>
                                    <td class="<?= $kelasTd ?>"><?= esc($pembeda[$s['pembeda']] ?? $s['pembeda']) ?></td>
                                    <td class="<?= $kelasTd ?> text-gray-600"><?= esc($s['lokasi']) ?></td>
                                    <td class="<?= $kelasTd ?> text-end"><?= (int) $s['pendaftaran'] ?></td>
                                </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>

                <?= komponen('tombol', ['label' => 'Terapkan nama baru', 'tipe' => 'submit', 'ikon' => 'check-check']) ?>
            </form>
        <?php endif ?>
    </section>

    <?php if ($lewati !== []) : ?>
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-lewati">
            <div>
                <h2 id="judul-lewati" class="text-lg font-bold text-gray-900">Perlu diubah sendiri (<?= count($lewati) ?> sekolah)</h2>
                <p class="mt-1 text-sm text-gray-600">
                    Letak sekolah ini tidak cukup untuk membedakannya dari sekolah lain bernama sama. Tekan Ubah dan tulis nama pembedanya,
                    misalnya dengan nama dusun atau nomor. Bila semua sekolah lain yang bernama sama sudah diberi nama baru, nama sekolah ini
                    sudah berbeda dan boleh dibiarkan.
                </p>
            </div>

            <div class="relative overflow-x-auto rounded-xl border border-gray-200">
                <table class="w-full text-left text-sm text-gray-700" id="tabel-lewati">
                    <caption class="sr-only">Sekolah yang perlu diubah sendiri</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="<?= $kelasTh ?>">NPSN</th>
                            <th scope="col" class="<?= $kelasTh ?>">Nama</th>
                            <th scope="col" class="<?= $kelasTh ?>">Letak</th>
                            <th scope="col" class="<?= $kelasTh ?>">Sebab</th>
                            <th scope="col" class="<?= $kelasTh ?>"><span class="sr-only">Tindakan</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lewati as $s) : ?>
                            <tr class="<?= $kelasBaris ?>" data-npsn="<?= esc($s['npsn'], 'attr') ?>">
                                <td class="<?= $kelasTd ?> whitespace-nowrap"><?= esc($s['npsn']) ?></td>
                                <td class="<?= $kelasTd ?>"><?= esc($s['nama']) ?></td>
                                <td class="<?= $kelasTd ?> text-gray-600"><?= esc($s['lokasi']) ?></td>
                                <td class="<?= $kelasTd ?> text-gray-600"><?= esc($s['alasan']) ?></td>
                                <td class="<?= $kelasTd ?> text-end">
                                    <?= komponen('tombol', ['label' => 'Ubah', 'href' => site_url('admin/sekolah/' . (int) $s['id'] . '/ubah'), 'varian' => 'kedua', 'ukuran' => 'kecil', 'kelas' => 'min-h-11']) ?>
                                </td>
                            </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/centang-semua.js') ?>"></script>
<?= $this->endSection() ?>
