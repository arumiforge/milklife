<?php
/**
 * Formulir tambah dan ubah kontak (`GET …/kontak/tambah?jenis=…`,
 * `GET …/kontak/<id>/ubah`), tata letak `admin_tw`. Label dan bantuan kotak
 * mengikuti jenisnya.
 * Ditampilkan ulang dengan isian yang diketik bila ada yang salah.
 *
 * @var CodeIgniter\View\View        $this
 * @var string                       $jenis       `narahubung` atau `media_sosial`
 * @var string                       $jenisAlamat `narahubung` atau `media-sosial` (kotak tersembunyi `jenis`)
 * @var array<string, string>        $teks        teks per jenis (`KontakController::TEKS`)
 * @var array<string, mixed>|null    $kontak      kontak yang diubah, `null` untuk kontak baru
 * @var array<string, string>        $isian       label, nilai, urutan
 * @var array<string, string>        $galat       pesan per kotak
 */

use App\Models\TautanModel;

$baru       = $kontak === null;
$narahubung = $jenis === TautanModel::NARAHUBUNG;
$tujuan     = $baru ? site_url('admin/pengaturan/kontak') : site_url('admin/pengaturan/kontak/' . (int) $kontak['id']);
$judul      = ($baru ? 'Tambah ' : 'Ubah ') . $teks['nama'];

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
// ikon-sprite: contact share-2
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc(ucfirst($judul)) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kontak<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6" aria-labelledby="judul-formulir-kontak">
        <h2 id="judul-formulir-kontak" class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <?= ikon($narahubung ? 'contact' : 'share-2', 'text-primary-700') ?>
            <?= esc(ucfirst($judul)) ?>
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            <?= $narahubung
                ? 'Sekolah bisa langsung mengirim WA ke nomor ini dari halaman depan.'
                : 'Alamat ini dibuka saat sekolah menekan nama media sosial di halaman depan.' ?>
        </p>

        <div class="mt-4">
            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
        </div>

        <form action="<?= $tujuan ?>" method="post" novalidate class="mt-4 space-y-5">
            <?= csrf_field() ?>
            <?php if ($baru) : ?>
                <input type="hidden" name="jenis" value="<?= esc($jenisAlamat, 'attr') ?>">
            <?php endif ?>

            <div>
                <label for="label" class="<?= $label ?>"><?= esc($teks['label']) ?></label>
                <input type="text" class="<?= $kelasIsian('label') ?>" id="label" name="label"
                    value="<?= esc($isian['label'], 'attr') ?>" maxlength="<?= TautanModel::PANJANG['label'] ?>" required<?= $uraian('label') ?>>
                <p id="label-bantuan" class="<?= $bantuan ?>"><?= esc($teks['label_bantuan']) ?></p>
                <?= $pesan('label') ?>
            </div>

            <div>
                <label for="nilai" class="<?= $label ?>"><?= esc($teks['nilai']) ?></label>
                <?php if ($narahubung) : ?>
                    <input type="tel" inputmode="tel" autocomplete="off" class="<?= $kelasIsian('nilai') ?>" id="nilai" name="nilai"
                        value="<?= esc($isian['nilai'], 'attr') ?>" maxlength="30" required<?= $uraian('nilai') ?>>
                <?php else : ?>
                    <input type="url" inputmode="url" class="<?= $kelasIsian('nilai') ?>" id="nilai" name="nilai"
                        value="<?= esc($isian['nilai'], 'attr') ?>" maxlength="<?= TautanModel::PANJANG['nilai'] ?>"
                        placeholder="https://" autocapitalize="off" spellcheck="false" required<?= $uraian('nilai') ?>>
                <?php endif ?>
                <p id="nilai-bantuan" class="<?= $bantuan ?>"><?= esc($teks['nilai_bantuan']) ?></p>
                <?= $pesan('nilai') ?>
            </div>

            <div class="sm:max-w-56">
                <label for="urutan" class="<?= $label ?>">Urutan</label>
                <input type="number" class="<?= $kelasIsian('urutan') ?>" id="urutan" name="urutan"
                    value="<?= esc($isian['urutan'], 'attr') ?>" min="0" max="<?= TautanModel::URUTAN_MAKSIMAL ?>" step="1" inputmode="numeric"<?= $uraian('urutan') ?>>
                <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu.</p>
                <?= $pesan('urutan') ?>
            </div>

            <div class="flex flex-wrap gap-2 pt-2">
                <?= komponen('tombol', ['label' => $baru ? 'Simpan ' . $teks['nama'] : 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/kontak'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>
