<?php
/**
 * Formulir tambah dan ubah kelompok umur, tata letak Tailwind `admin_tw`.
 * Data dari `KelompokUmurController`.
 *
 * Kotak: nama, tahun lahir paling awal dan paling akhir (K36), centang
 * jenjang (K24), biaya individu, awalan nomor dada (urut sesuai ketikan),
 * urutan tampil, dan aktif.
 *
 * @var CodeIgniter\View\View       $this
 * @var array<string, mixed>|null   $ku         kelompok umur tersimpan (ubah) atau null (tambah)
 * @var array<string, mixed>        $isian      nilai kotak (teks; `jenjang` larik kode)
 * @var array<string, string>       $galat      nama kotak => pesan
 * @var array<string, string>       $awalanLain awalan => nama kelompok umur lain pemiliknya
 */

use App\Libraries\Jenjang;
use App\Models\KelompokUmurModel;

$baru   = $ku === null;
$aksi   = $baru ? site_url('admin/pengaturan/kelompok-umur') : site_url('admin/pengaturan/kelompok-umur/' . (int) $ku['id']);
$judul  = $baru ? 'Tambah Kelompok Umur' : 'Ubah ' . $ku['nama'];

// Kelas dan atribut kotak; kotak yang salah ditandai merah dan `aria-invalid`.
$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$kotak = static fn (string $nama, bool $salah = false): string => 'class="' . $kelasKotak($salah || isset($galat[$nama])) . '"'
    . ($salah || isset($galat[$nama]) ? ' aria-invalid="true"' : '');
$jelaskan  = static function (string $kotak, string $bantuan = '') use ($galat): string {
    $id = trim($bantuan . (isset($galat[$kotak]) ? ' ' . $kotak . '-galat' : ''));

    return $id === '' ? '' : ' aria-describedby="' . $id . '"';
};
$pesan     = static fn (string $kotak): string => isset($galat[$kotak]) ? '<p id="' . $kotak . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$kotak]) . '</p>' : '';
$label     = 'mb-1.5 block text-sm font-semibold text-gray-900';
$bantuan   = 'mt-1.5 text-sm text-gray-600';

// Tabrakan tahun lahir disebut di kotak pertama; kotak kedua ikut ditandai.
$tahunSalah = isset($galat['tahun_lahir_awal']) || isset($galat['tahun_lahir_akhir']);

$terpakai = array_values(array_filter($ku['awalan'] ?? [], static fn (array $a): bool => (int) $a['nomor_terakhir'] > 0));
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?><?= esc($judul) ?><?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Kelompok Umur<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-6 p-4 sm:p-6">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <div class="space-y-6">
        <h2 class="text-xl font-bold break-words text-gray-900"><?= esc($judul) ?></h2>

        <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

        <form action="<?= $aksi ?>" method="post" id="formulir-kelompok-umur" class="space-y-6" novalidate>
            <?= csrf_field() ?>

            <section class="space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6">
                <div>
                    <label for="nama" class="<?= $label ?>">Nama kelompok umur</label>
                    <input type="text" <?= $kotak('nama') ?> id="nama" name="nama"
                        maxlength="<?= KelompokUmurModel::NAMA_MAKSIMAL ?>" value="<?= esc((string) $isian['nama'], 'attr') ?>"
                        placeholder="Contoh: KU 10"<?= $jelaskan('nama') ?>>
                    <?= $pesan('nama') ?>
                </div>

                <fieldset>
                    <legend class="mb-1 text-sm font-semibold text-gray-900">Tahun lahir atlet</legend>
                    <p id="tahun-lahir-bantuan" class="mb-3 text-sm text-gray-600">
                        Atlet masuk kelompok umur ini bila tahun lahirnya ada di antara kedua tahun, termasuk kedua tahun itu.
                        Kosongkan salah satu bila tidak dibatasi, misalnya KU 8 "lahir 2019 ke atas": isi 2019 lalu kosongkan kotak kedua.
                        <?php if (KelompokUmurModel::bolehPilih()) : ?>
                            Rentang boleh beririsan dengan kelompok umur lain di jenjang yang sama; atlet yang lahir di tahun irisan
                            dipilihkan kelompok umurnya oleh pendaftar.
                        <?php else : ?>
                            Dalam satu jenjang, rentang tidak boleh beririsan dengan kelompok umur aktif lain.
                        <?php endif ?>
                    </p>
                    <div class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-end">
                        <div>
                            <label for="tahun_lahir_awal" class="mb-1 block text-sm text-gray-700">Paling awal (atlet tertua)</label>
                            <input type="text" inputmode="numeric" maxlength="4" autocomplete="off"
                                <?= $kotak('tahun_lahir_awal', $tahunSalah) ?> id="tahun_lahir_awal" name="tahun_lahir_awal"
                                value="<?= esc((string) $isian['tahun_lahir_awal'], 'attr') ?>" placeholder="Contoh: 2016"<?= $jelaskan('tahun_lahir_awal', 'tahun-lahir-bantuan') ?>>
                        </div>
                        <div class="hidden pb-3 text-sm text-gray-500 sm:block">sampai</div>
                        <div>
                            <label for="tahun_lahir_akhir" class="mb-1 block text-sm text-gray-700">Paling akhir (atlet termuda)</label>
                            <input type="text" inputmode="numeric" maxlength="4" autocomplete="off"
                                <?= $kotak('tahun_lahir_akhir', $tahunSalah) ?> id="tahun_lahir_akhir" name="tahun_lahir_akhir"
                                value="<?= esc((string) $isian['tahun_lahir_akhir'], 'attr') ?>" placeholder="Contoh: 2018"<?= $jelaskan('tahun_lahir_akhir', 'tahun-lahir-bantuan') ?>>
                        </div>
                    </div>
                    <?php foreach (['tahun_lahir_awal', 'tahun_lahir_akhir'] as $nama) : ?>
                        <?= $pesan($nama) ?>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset id="jenjang" tabindex="-1" aria-describedby="jenjang-bantuan<?= isset($galat['jenjang']) ? ' jenjang-galat' : '' ?>">
                    <legend class="mb-1 text-sm font-semibold text-gray-900">Jenjang sekolah yang boleh ikut</legend>
                    <p id="jenjang-bantuan" class="mb-2 text-sm text-gray-600">
                        Atlet dari jenjang yang tidak dicentang tidak bisa didaftarkan di kelompok umur ini.
                    </p>
                    <div class="flex flex-wrap gap-x-6 gap-y-1">
                        <?php foreach (Jenjang::semua() as $kode => $namaJenjang) : ?>
                            <label class="inline-flex min-h-11 items-center gap-2 text-sm text-gray-900" for="jenjang-<?= esc($kode, 'attr') ?>">
                                <input class="size-4 rounded <?= isset($galat['jenjang']) ? 'border-red-500' : 'border-gray-300' ?> text-primary-700 focus:ring-primary-300" type="checkbox" name="jenjang[]"
                                    id="jenjang-<?= esc($kode, 'attr') ?>" value="<?= esc($kode, 'attr') ?>"
                                    <?= isset($galat['jenjang']) ? 'aria-invalid="true"' : '' ?>
                                    <?= in_array($kode, (array) $isian['jenjang'], true) ? 'checked' : '' ?>>
                                <?= esc($namaJenjang) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?= $pesan('jenjang') ?>
                </fieldset>

                <div>
                    <label for="biaya_individu" class="<?= $label ?>">Biaya per nomor individu</label>
                    <div class="flex">
                        <span class="inline-flex items-center rounded-s-lg border border-e-0 border-gray-300 bg-gray-100 px-3 text-sm text-gray-700">Rp</span>
                        <input type="text" inputmode="numeric" autocomplete="off"
                            <?= str_replace('rounded-lg', 'rounded-e-lg', $kotak('biaya_individu')) ?> id="biaya_individu" name="biaya_individu"
                            value="<?= esc((string) $isian['biaya_individu'], 'attr') ?>" placeholder="Contoh: 50000"<?= $jelaskan('biaya_individu', 'biaya-bantuan') ?>>
                    </div>
                    <?= $pesan('biaya_individu') ?>
                    <p id="biaya-bantuan" class="<?= $bantuan ?>">
                        Dibayar per atlet untuk setiap nomor individu. Tulis angka tanpa titik. Biaya nomor beregu diatur di halaman Nomor Lomba.
                    </p>
                </div>

                <div>
                    <label for="awalan" class="<?= $label ?>">Awalan nomor atlet (BIB)</label>
                    <input type="text" inputmode="numeric" autocomplete="off"
                        <?= $kotak('awalan') ?> id="awalan" name="awalan"
                        value="<?= esc((string) $isian['awalan'], 'attr') ?>" placeholder="Contoh: 2"<?= $jelaskan('awalan', 'awalan-bantuan') ?>>
                    <?= $pesan('awalan') ?>
                    <div id="awalan-bantuan" class="<?= $bantuan ?>">
                        Angka pertama nomor atlet (BIB), satu angka 1 sampai 9. Satu awalan cukup untuk 999 atlet; bila perlu lebih,
                        tulis beberapa awalan dipisah koma, misalnya <strong>2, 6</strong>. Awalan dipakai berurutan seperti ditulis.
                        <?php if ($awalanLain !== []) : ?>
                            <span class="mt-1 block">
                                Sudah dipakai kelompok umur lain:
                                <?= esc(implode('; ', array_map(static fn (string $kode, string $nama): string => $kode . ' untuk ' . $nama, array_map('strval', array_keys($awalanLain)), $awalanLain))) ?>.
                            </span>
                        <?php endif ?>
                        <?php foreach ($terpakai as $a) : ?>
                            <span class="mt-1 flex items-start gap-1.5 text-gray-900">
                                <?= ikon('lock', 'mt-0.5 size-4 shrink-0') ?>
                                <span>
                                    Awalan <?= esc((string) $a['awalan']) ?> sudah dipakai sampai nomor atlet (BIB)
                                    <?= esc($a['awalan'] . str_pad((string) $a['nomor_terakhir'], 3, '0', STR_PAD_LEFT)) ?>, jadi tidak bisa dihapus.
                                </span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-[12rem_1fr] sm:items-start">
                    <div>
                        <label for="urutan" class="<?= $label ?>">Urutan tampil</label>
                        <input type="text" inputmode="numeric" maxlength="3" autocomplete="off"
                            <?= $kotak('urutan') ?> id="urutan" name="urutan"
                            value="<?= esc((string) $isian['urutan'], 'attr') ?>"<?= $jelaskan('urutan', 'urutan-bantuan') ?>>
                        <?= $pesan('urutan') ?>
                        <p id="urutan-bantuan" class="<?= $bantuan ?>">Angka kecil tampil lebih dulu.</p>
                    </div>
                    <div class="sm:pt-6">
                        <label for="aktif" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                            <input type="checkbox" id="aktif" name="aktif" value="1" class="peer sr-only"
                                aria-describedby="aktif-bantuan"<?= (string) $isian['aktif'] === '1' ? ' checked' : '' ?>>
                            <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                            <span class="text-sm font-semibold text-gray-900">Aktif</span>
                        </label>
                        <p id="aktif-bantuan" class="ms-14 text-sm text-gray-600">Kelompok umur yang mati tidak muncul di pendaftaran.</p>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap gap-2">
                <?= komponen('tombol', ['label' => 'Simpan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
                <?= komponen('tombol', ['label' => 'Batal', 'href' => site_url('admin/pengaturan/kelompok-umur'), 'varian' => 'kedua', 'ikon' => 'x']) ?>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>
