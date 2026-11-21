<?php
/**
 * Formulir tambah (`$baris` null) dan ubah berkas unduhan (F2-07).
 * Data dari `UnduhanController::tampilkanFormulir()`. Tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View      $this
 * @var array<string, mixed>|null  $baris      baris tersimpan + `info`, atau null saat menambah
 * @var array<string, mixed>       $isian      judul, keterangan, urutan (teks), tampil (bool)
 * @var array<string, string>      $galat      nama kotak => pesan
 * @var bool                       $pilihUlang berkas yang dipilih sah, tetapi belum disimpan karena isian lain salah
 */

use App\Libraries\Unggahan;
use App\Models\UnduhanModel;

$menambah  = $baris === null;
$judulForm = $menambah ? 'Tambah Unduhan' : 'Ubah Unduhan';
$aksi      = $menambah ? site_url('admin/pengaturan/unduhan') : site_url('admin/pengaturan/unduhan/' . (int) $baris['id']);

$jenisBerkas = array_map('strtoupper', array_keys(Unggahan::JENIS_BERKAS));
$akhirJenis  = array_pop($jenisBerkas);
$teksJenis   = implode(', ', $jenisBerkas) . ', atau ' . $akhirJenis;
$terimaJenis = '.pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx';

$kelasKotak = static fn (string $kotak): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . (isset($galat[$kotak])
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$uraian     = static function (string $kotak, string $bantuan = '') use ($galat): string {
    $id = [];
    if ($bantuan !== '') {
        $id[] = $bantuan;
    }
    if (isset($galat[$kotak])) {
        $id[] = $kotak . '-galat';
    }

    return ($id === [] ? '' : ' aria-describedby="' . implode(' ', $id) . '"')
        . (isset($galat[$kotak]) ? ' aria-invalid="true"' : '');
};
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';
$label   = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan = 'mt-1.5 text-sm text-gray-600';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($judulForm) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?><?= esc($judulForm) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="max-w-3xl space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-label="<?= esc($judulForm, 'attr') ?>">
        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= $aksi ?>" method="post" enctype="multipart/form-data" novalidate class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="judul" class="<?= $label ?>">Judul</label>
                <input type="text" class="<?= $kelasKotak('judul') ?>" id="judul" name="judul"
                    value="<?= esc($isian['judul'], 'attr') ?>" maxlength="<?= UnduhanModel::PANJANG_JUDUL ?>" required
                    <?= $uraian('judul', 'judul-bantuan') ?>>
                <p id="judul-bantuan" class="<?= $bantuan ?>">Nama berkas yang dilihat sekolah, misalnya Juknis Seri 2 atau Flyer Pendaftaran.</p>
                <?= $pesan('judul') ?>
            </div>

            <div>
                <label for="keterangan" class="<?= $label ?>">Keterangan <span class="font-normal text-gray-500">(boleh dikosongkan)</span></label>
                <textarea class="<?= $kelasKotak('keterangan') ?>" id="keterangan" name="keterangan" rows="3"
                    maxlength="<?= UnduhanModel::PANJANG_KETERANGAN ?>"<?= $uraian('keterangan', 'keterangan-bantuan') ?>><?= esc($isian['keterangan']) ?></textarea>
                <p id="keterangan-bantuan" class="<?= $bantuan ?>">Kalimat pendek tentang isi berkas, paling banyak <?= UnduhanModel::PANJANG_KETERANGAN ?> huruf.</p>
                <?= $pesan('keterangan') ?>
            </div>

            <div>
                <?php if (! $menambah) : ?>
                    <?php $info = $baris['info']; ?>
                    <p class="<?= $label ?>">Berkas sekarang</p>
                    <div class="berkas-sekarang mb-4 flex flex-wrap items-center gap-2 rounded-lg border border-gray-200 p-3" data-berkas-sekarang>
                        <?php if ($info['ada']) : ?>
                            <span class="inline-flex items-center rounded-md border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-800"><?= esc($info['jenis']) ?></span>
                            <span class="text-sm text-gray-600"><?= esc($info['ukuran_teks']) ?></span>
                            <a class="inline-flex min-h-11 items-center gap-1 text-sm font-semibold text-primary-700 hover:underline" href="<?= esc($info['alamat'], 'attr') ?>" target="_blank" rel="noopener">
                                <?= ikon('external-link', 'size-4') ?> Buka berkas<span class="sr-only"> (di tab baru)</span>
                            </a>
                        <?php else : ?>
                            <span class="flex items-start gap-1 text-sm text-red-700">
                                <?= ikon('triangle-alert', 'mt-0.5 size-4 shrink-0') ?>
                                <span>Berkas tidak ditemukan di server. Pilih berkas pengganti di bawah.</span>
                            </span>
                        <?php endif ?>
                    </div>
                <?php endif ?>

                <label for="berkas" class="<?= $label ?>">
                    <?= $menambah ? 'Berkas' : 'Ganti berkas <span class="font-normal text-gray-500">(kosongkan bila tidak diganti)</span>' ?>
                </label>
                <input type="file" class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:cursor-pointer file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 focus:outline-none focus:ring-4 focus:ring-primary-100 <?= isset($galat['berkas']) ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' ?>" id="berkas" name="berkas"
                    accept="<?= $terimaJenis ?>" data-ukuran-maksimal="<?= Unggahan::BATAS_BERKAS ?>"
                    <?= $menambah ? 'required' : '' ?><?= $uraian('berkas', 'berkas-bantuan') ?>>
                <p id="berkas-bantuan" class="<?= $bantuan ?>">
                    <?= esc($teksJenis) ?>, paling besar <?= esc(Unggahan::ukuranTeks(Unggahan::BATAS_BERKAS)) ?>.
                    <?php if (! $menambah) : ?>Berkas lama dihapus setelah berkas baru tersimpan.<?php endif ?>
                </p>
                <?php if (isset($galat['berkas'])) : ?>
                    <?= $pesan('berkas') ?>
                <?php elseif ($pilihUlang) : ?>
                    <div class="pilih-ulang mt-2 flex items-start gap-2 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900" role="status" data-pilih-ulang>
                        <?= ikon('info', 'mt-0.5 size-4 shrink-0') ?>
                        <span>Berkas yang tadi dipilih belum tersimpan karena ada isian lain yang perlu diperbaiki. Pilih berkasnya lagi sebelum menyimpan.</span>
                    </div>
                <?php endif ?>
            </div>

            <div class="sm:max-w-48">
                <label for="urutan" class="<?= $label ?>">Urutan</label>
                <input type="number" class="<?= $kelasKotak('urutan') ?>" id="urutan" name="urutan"
                    value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= UnduhanModel::URUTAN_TERBESAR ?>" step="1" inputmode="numeric" required
                    <?= $uraian('urutan', 'urutan-bantuan') ?>>
                <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu.</p>
                <?= $pesan('urutan') ?>
            </div>

            <div>
                <label class="inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-gray-900" for="tampil">
                    <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" id="tampil" name="tampil" value="1"<?= $isian['tampil'] ? ' checked' : '' ?>
                        aria-describedby="tampil-bantuan">
                    Tampilkan di halaman depan
                </label>
                <p id="tampil-bantuan" class="text-sm text-gray-600">Hilangkan centang untuk menyembunyikan berkas tanpa menghapusnya.</p>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <?= komponen('tombol', ['label' => $menambah ? 'Simpan berkas' : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/unduhan'), 'varian' => 'kedua']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<?= $this->endSection() ?>
