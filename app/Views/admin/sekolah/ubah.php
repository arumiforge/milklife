<?php
/**
 * Formulir ubah sekolah (K97): `GET admin/sekolah/<id>/ubah`. Ditampilkan
 * ulang dengan isian yang diketik bila ada yang salah. NPSN hanya
 * ditampilkan; tidak bisa diubah. Di samping formulir tampil sekolah lain
 * yang namanya sama, supaya admin tahu pembeda apa yang perlu ditambahkan.
 * Tata letak `admin_tw` (F9-15); kotak salah ditandai `aria-invalid="true"`.
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>        $sekolah           data tersimpan
 * @var array<string, string>       $isian             nilai kotak formulir
 * @var array<string, string>       $galat             pesan per kotak
 * @var list<string>                $kabupaten
 * @var array<string, string>       $jenjang           kode data sekolah => label
 * @var list<array<string, mixed>>  $namaSama          sekolah lain bernama sama (paling banyak 20)
 * @var int                         $jumlahPendaftaran pendaftaran dengan NPSN ini
 */

use App\Models\SekolahModel;

$kelasLabel = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuanKls = 'mt-1.5 text-sm text-gray-600';

/** Atribut `class`, `aria-invalid`, dan `aria-describedby` untuk satu kotak. */
$atributKotak = static function (string $kotak, bool $bantuan = false) use ($galat): string {
    $salah  = isset($galat[$kotak]);
    $kelas  = 'block min-h-11 w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
        . ($salah
            ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
            : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
    $uraian = trim(($bantuan ? $kotak . '-bantuan ' : '') . ($salah ? $kotak . '-galat' : ''));

    return 'class="' . $kelas . '"' . ($salah ? ' aria-invalid="true"' : '') . ($uraian !== '' ? ' aria-describedby="' . $uraian . '"' : '');
};
$pesan = static fn (string $kotak): string => isset($galat[$kotak])
    ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>'
    : '';

$teks = static function (string $kotak, string $label, int $panjang, string $bantuan = '') use ($isian, $atributKotak, $pesan, $kelasLabel, $bantuanKls): string {
    return '<div>'
        . '<label for="' . $kotak . '" class="' . $kelasLabel . '">' . esc($label) . '</label>'
        . '<input type="text" id="' . $kotak . '" name="' . $kotak . '"'
        . ' value="' . esc($isian[$kotak], 'attr') . '" maxlength="' . $panjang . '" ' . $atributKotak($kotak, $bantuan !== '') . '>'
        . ($bantuan !== '' ? '<p id="' . $kotak . '-bantuan" class="' . $bantuanKls . '">' . esc($bantuan) . '</p>' : '')
        . $pesan($kotak)
        . '</div>';
};
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Ubah sekolah<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Sekolah<?= $this->endSection() ?>

<?= $this->section('jejak') ?><?= view('admin/_jejak_tw', ['jejak' => [['label' => 'Sekolah', 'alamat' => 'admin/sekolah'], ['label' => 'Ubah ' . (string) ($sekolah['npsn'] ?? 'sekolah')]]], ['saveData' => false]) ?><?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="p-4 sm:px-6 sm:py-4">
    <div class="grid gap-4 lg:grid-cols-12 lg:items-start">
        <section class="space-y-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 lg:col-span-8 xl:col-span-7" aria-labelledby="judul-formulir-sekolah">
            <div>
                <h2 id="judul-formulir-sekolah" class="flex items-center gap-2 text-lg font-bold text-gray-900">
                    <?= ikon('school', 'text-primary-700') ?>
                    Ubah sekolah
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    NPSN <strong class="text-gray-900"><?= esc($sekolah['npsn']) ?></strong> tidak bisa diubah.
                    <?php if ($sekolah['disunting_pada'] !== null) : ?>
                        Terakhir disunting <?= esc(tanggal_id((string) $sekolah['disunting_pada'], true)) ?>.
                    <?php endif ?>
                    Setelah disimpan, sekolah ini tidak ditimpa saat data sekolah diimpor ulang.
                </p>
            </div>

            <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

            <form action="<?= site_url('admin/sekolah/' . (int) $sekolah['id']) ?>" method="post" class="space-y-5" novalidate>
                <?= csrf_field() ?>

                <?= $teks('nama', 'Nama sekolah', SekolahModel::PANJANG['nama'], 'Bila namanya sama dengan sekolah lain, tambahkan pembeda seperti nama desa, misalnya MIS MUHAMMADIYAH JATI.') ?>

                <div class="flex items-start gap-3">
                    <input class="mt-0.5 size-5 shrink-0 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500" type="checkbox" id="terapkan" name="terapkan" value="1"<?= $isian['terapkan'] === '1' ? ' checked' : '' ?> aria-describedby="terapkan-bantuan">
                    <div>
                        <label class="text-sm font-semibold text-gray-900" for="terapkan">Ganti juga nama sekolah di pendaftaran yang sudah masuk</label>
                        <p id="terapkan-bantuan" class="<?= $bantuanKls ?>">
                            <?= $jumlahPendaftaran > 0
                                ? 'Ada ' . $jumlahPendaftaran . ' pendaftaran dengan NPSN ini. Nama di rekap dan bukti pendaftarannya ikut berganti bila nama sekolah diubah.'
                                : 'Belum ada pendaftaran dengan NPSN ini.' ?>
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="jenjang" class="<?= $kelasLabel ?>">Jenjang</label>
                        <select id="jenjang" name="jenjang" <?= $atributKotak('jenjang', true) ?>>
                            <?php foreach ($jenjang as $kode => $label) : ?>
                                <option value="<?= esc($kode, 'attr') ?>"<?= strtoupper($isian['jenjang']) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p id="jenjang-bantuan" class="<?= $bantuanKls ?>">Menentukan kelompok umur yang boleh diikuti atlet.</p>
                        <?= $pesan('jenjang') ?>
                    </div>
                    <?= $teks('bentuk', 'Bentuk', SekolahModel::PANJANG['bentuk'], 'Contoh: SD, MI, SMP, MTs, SMA, SMK, MA.') ?>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="status" class="<?= $kelasLabel ?>">Status</label>
                        <select id="status" name="status" <?= $atributKotak('status') ?>>
                            <option value="">Tidak diketahui</option>
                            <?php foreach (SekolahModel::STATUS as $kode => $label) : ?>
                                <option value="<?= esc($kode, 'attr') ?>"<?= strtoupper($isian['status']) === $kode ? ' selected' : '' ?>><?= esc($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $pesan('status') ?>
                    </div>
                    <div>
                        <label for="kabupaten" class="<?= $kelasLabel ?>">Kabupaten</label>
                        <select id="kabupaten" name="kabupaten" <?= $atributKotak('kabupaten') ?>>
                            <?php foreach ($kabupaten as $nama) : ?>
                                <option value="<?= esc($nama, 'attr') ?>"<?= strcasecmp($isian['kabupaten'], $nama) === 0 ? ' selected' : '' ?>><?= esc($nama) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $pesan('kabupaten') ?>
                    </div>
                </div>

                <?= $teks('alamat', 'Alamat', SekolahModel::PANJANG['alamat']) ?>

                <div class="grid gap-5 sm:grid-cols-2">
                    <?= $teks('desa_kelurahan', 'Desa/kelurahan', SekolahModel::PANJANG['desa_kelurahan']) ?>
                    <?= $teks('kecamatan', 'Kecamatan', SekolahModel::PANJANG['kecamatan']) ?>
                </div>

                <div class="flex flex-wrap gap-2 pt-1">
                    <?= komponen('tombol', ['label' => 'Simpan perubahan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                    <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/sekolah'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
                </div>
            </form>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5 lg:col-span-4 xl:col-span-5" aria-labelledby="judul-nama-sama">
            <h2 id="judul-nama-sama" class="mb-2 text-base font-bold text-gray-900">Sekolah lain bernama <?= esc($sekolah['nama']) ?></h2>
            <?php if ($namaSama === []) : ?>
                <p class="text-sm text-gray-600">Tidak ada. Nama sekolah ini sudah berbeda dari sekolah lain.</p>
            <?php else : ?>
                <ul class="space-y-2 text-sm" id="daftar-nama-sama">
                    <?php foreach ($namaSama as $lain) : ?>
                        <li>
                            <a class="font-semibold text-primary-700 underline hover:no-underline" href="<?= site_url('admin/sekolah/' . (int) $lain['id'] . '/ubah') ?>">NPSN <?= esc($lain['npsn']) ?></a>
                            <span class="block text-gray-600"><?= esc(SekolahModel::lokasi($lain)) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif ?>
        </section>
    </div>
</div>
<?= $this->endSection() ?>
