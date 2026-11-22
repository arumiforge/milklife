<?php
/**
 * Formulir tambah dan ubah album Galeri (`GET …/galeri/tambah`,
 * `GET …/galeri/<id>/ubah`, F9-06), tata letak `admin_tw`. Ditampilkan
 * ulang dengan isian yang diketik bila ada yang salah.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>|null  $album      yang diubah, `null` untuk yang baru
 * @var array<string, string>      $isian      judul, keterangan, tautan, urutan, aktif
 * @var array<string, string>      $galat      pesan per kotak
 * @var bool                       $pilihUlang sampul yang dipilih sah, tetapi belum disimpan karena isian lain salah
 * @var App\Libraries\Unggahan     $unggahan
 */

use App\Libraries\Unggahan;
use App\Models\GaleriModel;

$baru   = $album === null;
$tujuan = $baru ? site_url('admin/pengaturan/galeri') : site_url('admin/pengaturan/galeri/' . (int) $album['id']);
$judul  = $baru ? 'Tambah album' : 'Ubah album';
$sampul = ! $baru && is_string($album['sampul']) && $unggahan->ada($album['sampul']) ? $album['sampul'] : null;

$kelasIsian = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . (isset($galat[$kotak])
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$uraian = static fn (string $kotak): string => ' aria-describedby="' . $kotak . '-bantuan' . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : '') . '"'
    . (isset($galat[$kotak]) ? ' aria-invalid="true"' : '');
$pesan  = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Galeri<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-formulir-album">
        <h2 id="judul-formulir-album" class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <?= ikon('images', 'text-primary-700') ?> <?= esc($judul) ?>
        </h2>
        <p class="mt-1 text-sm text-gray-600">Album tampil di halaman Galeri sebagai kartu bersampul. Pengunjung yang menekannya dibawa ke tautan album di tab baru.</p>

        <div class="mt-4">
            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
        </div>

        <form action="<?= $tujuan ?>" method="post" enctype="multipart/form-data" novalidate data-formulir-album class="mt-4 grid gap-5 lg:grid-cols-2 lg:items-start lg:gap-x-6">
            <?= csrf_field() ?>

            <div>
                <label for="judul" class="<?= $label ?>">Judul album</label>
                <input type="text" class="<?= $kelasIsian('judul') ?>" id="judul" name="judul"
                    value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['judul'] ?>" required<?= $uraian('judul') ?>>
                <p id="judul-bantuan" class="<?= $bantuan ?>">Nama singkat, misalnya "Seri 1 – 2025" atau "Hari kedua".</p>
                <?= $pesan('judul') ?>
            </div>

            <div>
                <label for="keterangan" class="<?= $label ?>">Keterangan <span class="font-normal text-gray-500">(boleh kosong)</span></label>
                <input type="text" class="<?= $kelasIsian('keterangan') ?>" id="keterangan" name="keterangan"
                    value="<?= esc($isian['keterangan'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['keterangan'] ?>"<?= $uraian('keterangan') ?>>
                <p id="keterangan-bantuan" class="<?= $bantuan ?>">Satu kalimat di bawah judul kartu.</p>
                <?= $pesan('keterangan') ?>
            </div>

            <div>
                <label for="tautan" class="<?= $label ?>">Tautan album <span class="font-normal text-gray-500">(boleh diisi nanti)</span></label>
                <input type="url" class="<?= $kelasIsian('tautan') ?>" id="tautan" name="tautan" inputmode="url"
                    value="<?= esc($isian['tautan'], 'attr') ?>" maxlength="<?= GaleriModel::PANJANG['tautan'] ?>" placeholder="https://drive.google.com/…"<?= $uraian('tautan') ?>>
                <p id="tautan-bantuan" class="<?= $bantuan ?>">
                    Buka album di Instagram atau Google Drive, lalu salin alamat lengkapnya dari kotak alamat peramban (diawali https://).
                    Untuk Google Drive, pastikan foldernya dibagikan ke "Siapa saja yang memiliki link".
                    Selama tautan kosong, album tidak tampil untuk umum.
                </p>
                <?= $pesan('tautan') ?>
            </div>

            <div>
                <?php if ($sampul !== null) : ?>
                    <p class="<?= $label ?>">Sampul sekarang</p>
                    <img src="<?= base_url($sampul) ?>" alt="" width="240" height="160" class="mb-2 block h-40 w-60 max-w-full rounded-lg border border-gray-200 object-cover">
                    <label class="mb-3 inline-flex min-h-11 items-center gap-2 text-sm text-gray-900" for="hapus_sampul">
                        <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" id="hapus_sampul" name="hapus_sampul" value="1"
                            data-konfirmasi-centang="Sampul album dihapus dari server saat album disimpan, dan tidak bisa dikembalikan." data-konfirmasi-tombol="Hapus sampul">
                        Hapus sampul (kartu memakai latar warna)
                    </label>
                <?php endif ?>
                <label for="sampul" class="<?= $label ?>">
                    <?= $sampul === null ? 'Sampul' : 'Ganti sampul' ?> <span class="font-normal text-gray-500">(<?= $sampul === null ? 'boleh kosong' : 'kosongkan bila tidak diganti' ?>)</span>
                </label>
                <input type="file" class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-primary-100 <?= isset($galat['sampul']) ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' ?>" id="sampul" name="sampul" accept=".jpg,.jpeg,.png,.webp"
                    data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"<?= $uraian('sampul') ?>>
                <p id="sampul-bantuan" class="<?= $bantuan ?>">
                    Foto JPG, PNG, atau WEBP, paling besar <?= esc(Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR)) ?>. Foto mendatar paling bagus.
                    Foto dikecilkan otomatis.<?php if ($sampul !== null) : ?> Sampul lama dihapus setelah sampul baru tersimpan.<?php endif ?>
                </p>
                <?= $pesan('sampul') ?>
                <?php if ($pilihUlang) : ?>
                    <div class="pilih-ulang mt-2 flex items-start gap-2 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900" role="status" data-pilih-ulang>
                        <?= ikon('info', 'mt-0.5 size-4 shrink-0') ?>
                        <span>Sampul yang tadi dipilih belum tersimpan karena ada isian lain yang perlu diperbaiki. Pilih sampulnya lagi sebelum menyimpan.</span>
                    </div>
                <?php endif ?>
            </div>

            <div class="grid items-end gap-4 sm:grid-cols-12 lg:col-span-2">
                <div class="sm:col-span-5">
                    <label for="urutan" class="<?= $label ?>">Urutan</label>
                    <input type="number" class="<?= $kelasIsian('urutan') ?>" id="urutan" name="urutan"
                        value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= GaleriModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                    <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu. Kosongkan untuk paling akhir.</p>
                    <?= $pesan('urutan') ?>
                </div>
                <div class="sm:col-span-7 sm:pb-7">
                    <?= komponen('sakelar', ['label' => 'Tampilkan di halaman Galeri', 'nama' => 'aktif', 'centang' => $isian['aktif'] === '1']) ?>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 pt-2 lg:col-span-2">
                <?= komponen('tombol', ['label' => $baru ? 'Simpan album' : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/galeri'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
