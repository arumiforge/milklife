<?php
/**
 * Formulir tambah dan ubah jadwal (`GET …/jadwal/tambah`, `GET …/jadwal/<id>/ubah`),
 * tata letak `admin_tw`. Ditampilkan ulang dengan isian yang diketik bila ada
 * yang salah. Waktu memakai `datetime-local` (nilai `YYYY-MM-DDTHH:MM`)
 * yang ditampilkan `pilih-tanggal.js` sebagai pilihan Tanggal, Bulan, Tahun,
 * Jam, dan Menit (K89).
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>|null   $jadwal jadwal yang diubah, `null` untuk jadwal baru
 * @var array<string, string>       $isian  judul, keterangan, waktu (`Y-m-d\TH:i`), tempat, tautan, urutan, tampil ('1'/'0')
 * @var array<string, string>       $galat  pesan per kotak
 */

use App\Models\JadwalModel;

$baru   = $jadwal === null;
$tujuan = $baru ? site_url('admin/pengaturan/jadwal') : site_url('admin/pengaturan/jadwal/' . (int) $jadwal['id']);

// Atribut bersama tiap kotak: kelas, tanda salah, dan uraian (bantuan dan pesan galat).
$kelasIsian = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . (isset($galat[$kotak])
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$uraian = static function (string $kotak, bool $adaBantuan = true) use ($galat): string {
    $id = array_filter([$adaBantuan ? $kotak . '-bantuan' : '', isset($galat[$kotak]) ? $kotak . '-galat' : '']);

    return ($id === [] ? '' : ' aria-describedby="' . implode(' ', $id) . '"')
        . (isset($galat[$kotak]) ? ' aria-invalid="true"' : '');
};
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
$pilihan = '<span class="font-normal text-gray-500">(boleh kosong)</span>';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= $baru ? 'Tambah Agenda' : 'Ubah Agenda' ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Agenda<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-formulir-jadwal">
        <h2 id="judul-formulir-jadwal" class="text-lg font-bold text-gray-900"><?= $baru ? 'Tambah agenda' : 'Ubah agenda' ?></h2>
        <p class="mt-1 text-sm text-gray-600">
            Hanya judul yang wajib diisi. Isi tempat untuk kegiatan tatap muka, tautan untuk kegiatan daring.
        </p>

        <div class="mt-4">
            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
        </div>

        <form action="<?= $tujuan ?>" method="post" novalidate class="mt-4">
            <?= csrf_field() ?>

            <div class="grid gap-5 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label for="judul" class="<?= $label ?>">Judul</label>
                    <input type="text" class="<?= $kelasIsian('judul') ?>" id="judul" name="judul"
                        value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['judul'] ?>" required<?= $uraian('judul') ?>>
                    <p id="judul-bantuan" class="<?= $bantuan ?>">Contoh: Pertemuan Teknik (Technical Meeting), Sosialisasi Daring.</p>
                    <?= $pesan('judul') ?>
                </div>

                <div>
                    <label for="waktu" class="<?= $label ?>">Waktu <?= $pilihan ?></label>
                    <input type="datetime-local" class="<?= $kelasIsian('waktu') ?> min-h-11" id="waktu" name="waktu"
                        value="<?= esc($isian['waktu'], 'attr') ?>"<?= $uraian('waktu') ?>>
                    <p id="waktu-bantuan" class="<?= $bantuan ?>">Tanggal dan jam dalam WIB. Kosongkan bila belum pasti.</p>
                    <?= $pesan('waktu') ?>
                </div>

                <div>
                    <label for="tempat" class="<?= $label ?>">Tempat <?= $pilihan ?></label>
                    <input type="text" class="<?= $kelasIsian('tempat') ?>" id="tempat" name="tempat"
                        value="<?= esc($isian['tempat'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['tempat'] ?>"<?= $uraian('tempat') ?>>
                    <p id="tempat-bantuan" class="<?= $bantuan ?>">Untuk kegiatan tatap muka, misalnya Supersoccer Arena, Kudus.</p>
                    <?= $pesan('tempat') ?>
                </div>

                <div class="md:col-span-2">
                    <label for="tautan" class="<?= $label ?>">Tautan <?= $pilihan ?></label>
                    <input type="url" inputmode="url" class="<?= $kelasIsian('tautan') ?>" id="tautan" name="tautan"
                        value="<?= esc($isian['tautan'], 'attr') ?>" maxlength="<?= JadwalModel::PANJANG['tautan'] ?>"
                        placeholder="https://" autocapitalize="off" spellcheck="false"<?= $uraian('tautan') ?>>
                    <p id="tautan-bantuan" class="<?= $bantuan ?>">Untuk kegiatan daring, misalnya alamat Zoom, Google Meet, atau siaran YouTube.</p>
                    <?= $pesan('tautan') ?>
                </div>

                <div class="md:col-span-2">
                    <label for="keterangan" class="<?= $label ?>">Keterangan <?= $pilihan ?></label>
                    <textarea class="<?= $kelasIsian('keterangan') ?>" id="keterangan" name="keterangan" rows="3"
                        maxlength="<?= JadwalModel::PANJANG['keterangan'] ?>"<?= $uraian('keterangan') ?>><?= esc($isian['keterangan']) ?></textarea>
                    <p id="keterangan-bantuan" class="<?= $bantuan ?>">Siapa yang perlu hadir atau apa yang perlu dibawa. Paling banyak <?= JadwalModel::PANJANG['keterangan'] ?> huruf.</p>
                    <?= $pesan('keterangan') ?>
                </div>

                <div>
                    <label for="urutan" class="<?= $label ?>">Urutan</label>
                    <input type="number" class="<?= $kelasIsian('urutan') ?>" id="urutan" name="urutan"
                        value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= JadwalModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                    <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu.</p>
                    <?= $pesan('urutan') ?>
                </div>

                <div class="md:pt-7">
                    <label for="tampil" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                        <input type="checkbox" id="tampil" name="tampil" value="1" class="peer sr-only"
                            <?= $isian['tampil'] === '1' ? 'checked' : '' ?> aria-describedby="tampil-bantuan">
                        <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                        <span class="text-sm font-semibold text-gray-900">Tampilkan di halaman depan</span>
                    </label>
                    <p id="tampil-bantuan" class="ms-14 text-sm text-gray-600">Matikan untuk menyembunyikan tanpa menghapus.</p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => $baru ? 'Simpan agenda' : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/jadwal'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<?= $this->endSection() ?>
