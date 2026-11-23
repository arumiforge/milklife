<?php
/**
 * Formulir tambah dan ubah rekor kejuaraan yang diisi admin
 * (`GET /admin/rekor/tambah`, `GET /admin/rekor/<id>/ubah`, F11-06), tata
 * letak `admin_tw`. Ditampilkan ulang dengan isian yang diketik bila ada
 * yang salah. Spesifikasi alat dan cara waktu terisi dari nomor yang
 * dipilih (`data-nomor-rekor`), dan bisa diubah.
 *
 * @var CodeIgniter\View\View                    $this
 * @var array<string, mixed>|null                $rekor yang diubah, `null` untuk yang baru
 * @var array<string, string>                    $isian `Rekor::rapikanIsian()`
 * @var array<string, string>                    $galat pesan per kotak
 * @var array<int, array<string, mixed>>         $nomor `Rekor::pilihanNomor()`
 */

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Rekor;
use App\Models\NomorLombaModel;

$baru   = $rekor === null;
$tujuan = $baru ? site_url('admin/rekor') : site_url('admin/rekor/' . (int) $rekor['id']);
$judul  = $baru ? 'Tambah rekor' : 'Ubah rekor';
$dipilih = $nomor[(int) $isian['nomor_lomba_id']] ?? null;
$waktu   = $dipilih === null || $dipilih['jenis_hasil'] === JenisHasil::WAKTU;

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

// Data nomor untuk skrip: jenis hasil, cara waktu, dan spesifikasi alat bawaan.
$dataNomor = [];

foreach ($nomor as $id => $n) {
    $dataNomor[$id] = ['waktu' => $n['jenis_hasil'] === JenisHasil::WAKTU, 'cara' => $n['cara_waktu'], 'alat' => $n['berat_alat'], 'contoh' => Rekor::contohPrestasi($n['jenis_hasil'])];
}

$kelompok = [];

foreach ($nomor as $n) {
    $kelompok[$n['kelompok_umur']][] = $n;
}
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Hasil lomba', 'alamat' => 'admin/hasil'], ['label' => 'Rekor kejuaraan', 'alamat' => 'admin/rekor'], ['label' => $judul]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5" aria-labelledby="judul-formulir-rekor">
        <h2 id="judul-formulir-rekor" class="flex items-center gap-2 text-lg font-bold text-gray-900">
            <?= ikon('trophy', 'text-primary-700') ?> <?= esc($judul) ?>
        </h2>
        <p class="mt-1 text-sm text-gray-600">Isi rekor yang belum tercatat dari hasil terbit, misalnya rekor awal yang ditetapkan panitia. Rekor ini dibandingkan dengan hasil Final berikutnya.</p>

        <div class="mt-4">
            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>
        </div>

        <form action="<?= $tujuan ?>" method="post" novalidate data-formulir-rekor class="mt-4 grid gap-5 lg:grid-cols-2 lg:items-start lg:gap-x-6">
            <?= csrf_field() ?>

            <div>
                <label for="nomor_lomba_id" class="<?= $label ?>">Nomor lomba</label>
                <select class="<?= $kelasIsian('nomor_lomba_id') ?> min-h-11" id="nomor_lomba_id" name="nomor_lomba_id" data-nomor-rekor="<?= esc((string) json_encode($dataNomor, JSON_UNESCAPED_UNICODE), 'attr') ?>"<?= $uraian('nomor_lomba_id') ?>>
                    <option value="">Pilih nomor lomba</option>
                    <?php foreach ($kelompok as $ku => $daftar) : ?>
                        <optgroup label="<?= esc((string) $ku, 'attr') ?>">
                            <?php foreach ($daftar as $n) : ?>
                                <option value="<?= $n['id'] ?>"<?= (string) $n['id'] === $isian['nomor_lomba_id'] ? ' selected' : '' ?>><?= esc($n['nama'] . ' – ' . $ku) ?></option>
                            <?php endforeach ?>
                        </optgroup>
                    <?php endforeach ?>
                </select>
                <p id="nomor_lomba_id-bantuan" class="<?= $bantuan ?>">Kelompok umur dan jenis kelamin ikut nomornya.</p>
                <?= $pesan('nomor_lomba_id') ?>
            </div>

            <div>
                <label for="berat_alat" class="<?= $label ?>">Spesifikasi alat <span class="font-normal text-gray-500">(boleh kosong)</span></label>
                <input type="text" class="<?= $kelasIsian('berat_alat') ?>" id="berat_alat" name="berat_alat" maxlength="<?= Rekor::PANJANG['berat_alat'] ?>" placeholder="Contoh: Peluru 3 kg"
                    value="<?= esc($isian['berat_alat'], 'attr') ?>"<?= $uraian('berat_alat') ?>>
                <p id="berat_alat-bantuan" class="<?= $bantuan ?>">Samakan dengan spesifikasi alat di Pengaturan &gt; Nomor lomba supaya rekor ini dibandingkan dengan hasil nomor itu.</p>
                <?= $pesan('berat_alat') ?>
            </div>

            <fieldset id="bagian-cara-waktu-rekor"<?= $waktu ? '' : ' hidden' ?> aria-describedby="cara_waktu-bantuan">
                <legend class="<?= $label ?>">Cara pencatatan waktu</legend>
                <div class="flex flex-wrap gap-x-6">
                    <?php foreach (NomorLombaModel::CARA_WAKTU as $kode => $teks) : ?>
                        <label class="flex min-h-11 items-center gap-2 text-sm text-gray-900" for="cara_waktu-<?= $kode ?>">
                            <input class="size-4 border-gray-300 text-primary-700 focus:ring-primary-500" type="radio" name="cara_waktu" id="cara_waktu-<?= $kode ?>" value="<?= $kode ?>"<?= $isian['cara_waktu'] === $kode ? ' checked' : '' ?>>
                            <?= $teks ?>
                        </label>
                    <?php endforeach ?>
                </div>
                <p id="cara_waktu-bantuan" class="<?= $bantuan ?>">Rekor waktu tangan dan waktu elektronik dicatat terpisah. Waktu tangan dibulatkan ke atas ke 0,1 detik.</p>
            </fieldset>

            <div>
                <label for="prestasi" class="<?= $label ?>">Prestasi</label>
                <input type="text" class="<?= $kelasIsian('prestasi') ?>" id="prestasi" name="prestasi" inputmode="decimal" autocomplete="off"
                    value="<?= esc($isian['prestasi'], 'attr') ?>"<?= $uraian('prestasi') ?>>
                <p id="prestasi-bantuan" class="<?= $bantuan ?>" data-contoh-prestasi><?= esc(Rekor::contohPrestasi($dipilih['jenis_hasil'] ?? JenisHasil::WAKTU)) ?></p>
                <?= $pesan('prestasi') ?>
            </div>

            <div>
                <label for="atlet" class="<?= $label ?>">Nama atlet</label>
                <input type="text" class="<?= $kelasIsian('atlet') ?>" id="atlet" name="atlet" maxlength="<?= Rekor::PANJANG['atlet'] ?>" autocomplete="off"
                    value="<?= esc($isian['atlet'], 'attr') ?>"<?= $uraian('atlet') ?>>
                <p id="atlet-bantuan" class="<?= $bantuan ?>">Untuk nomor beregu, tulis nama anggota regu dipisah koma.</p>
                <?= $pesan('atlet') ?>
            </div>

            <div>
                <label for="sekolah" class="<?= $label ?>">Sekolah</label>
                <input type="text" class="<?= $kelasIsian('sekolah') ?>" id="sekolah" name="sekolah" maxlength="<?= Rekor::PANJANG['sekolah'] ?>" autocomplete="off"
                    value="<?= esc($isian['sekolah'], 'attr') ?>"<?= $uraian('sekolah') ?>>
                <p id="sekolah-bantuan" class="<?= $bantuan ?>">Nama sekolah saat rekor dibuat.</p>
                <?= $pesan('sekolah') ?>
            </div>

            <div>
                <label for="seri" class="<?= $label ?>">Seri</label>
                <input type="text" class="<?= $kelasIsian('seri') ?>" id="seri" name="seri" maxlength="<?= Rekor::PANJANG['seri'] ?>"
                    value="<?= esc($isian['seri'], 'attr') ?>"<?= $uraian('seri') ?>>
                <p id="seri-bantuan" class="<?= $bantuan ?>">Contoh: <?= esc(Rekor::seriBawaan()) ?>.</p>
                <?= $pesan('seri') ?>
            </div>

            <div>
                <label for="tanggal" class="<?= $label ?>">Tanggal <span class="font-normal text-gray-500">(boleh kosong)</span></label>
                <input type="date" class="<?= $kelasIsian('tanggal') ?>" id="tanggal" name="tanggal" min="2020-01-01" max="<?= date('Y') + 1 ?>-12-31"
                    value="<?= esc($isian['tanggal'], 'attr') ?>"<?= $uraian('tanggal') ?>>
                <p id="tanggal-bantuan" class="<?= $bantuan ?>">Tanggal lomba saat rekor dibuat.</p>
                <?= $pesan('tanggal') ?>
            </div>

            <div class="flex flex-wrap gap-3 lg:col-span-2">
                <?= komponen('tombol', ['label' => 'Simpan rekor', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/rekor'), 'varian' => 'kedua']) ?>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
<script src="<?= aset('js/admin-rekor.js') ?>"></script>
<?= $this->endSection() ?>
