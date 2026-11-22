<?php
/**
 * Pengaturan > Hasil (F9-11): metode klasemen juara umum dan poin per
 * peringkat, tata letak `admin_tw`. Data dari `MetodeKlasemenController`.
 *
 * @var CodeIgniter\View\View $this
 * @var string                $metode `Klasemen::MEDALI` atau `Klasemen::POIN` (tersimpan atau yang baru dipilih)
 * @var list<string>          $poin   poin peringkat 1–8 (tersimpan atau yang baru diketik)
 * @var array<string, string> $galat  `metode`, `poin`, atau `poin_<n>` => pesan
 */

use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;

$keteranganMetode = [
    Klasemen::MEDALI => 'Sesuai petunjuk teknis bagian 11: sekolah dengan juara I terbanyak menjadi juara umum; bila sama, dibandingkan juara II, lalu juara III.',
    Klasemen::POIN   => 'Setiap atlet atau regu di peringkat 1–' . Klasemen::PERINGKAT_POIN . ' babak Final menyumbang poin untuk sekolahnya. Sekolah dengan poin terbanyak menjadi juara umum; bila sama, dibandingkan juara I, lalu II, lalu III.',
];
$poinGalat = array_filter($galat, static fn (string $kunci): bool => str_starts_with($kunci, 'poin'), ARRAY_FILTER_USE_KEY);
$kelasKotak = static fn (bool $salah): string => 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
    . ($salah
        ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$kartu = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Hasil<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Hasil<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Pilih cara menentukan juara umum di klasemen sekolah. Klasemen tetap dihitung terpisah per jenjang sekolah,
        dan kolom poin tampil di klasemen apa pun metodenya. Perubahan langsung berlaku di klasemen panel, halaman Hasil, PDF, Excel, dan videotron.
    </p>

    <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

    <form action="<?= site_url('admin/pengaturan/hasil') ?>" method="post" novalidate class="space-y-4">
        <?= csrf_field() ?>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-metode">
                <h2 id="judul-metode" class="mb-4 text-lg font-bold text-gray-900">Metode klasemen juara umum</h2>
                <fieldset<?= isset($galat['metode']) ? ' aria-describedby="metode-galat"' : '' ?>>
                    <legend class="sr-only">Metode klasemen juara umum</legend>
                    <div class="space-y-3">
                        <?php foreach (Klasemen::METODE as $kode => $label) : ?>
                            <div class="flex items-start gap-3">
                                <input class="mt-0.5 size-4 shrink-0 border-gray-300 text-primary-700 focus:ring-primary-300" type="radio" name="metode" id="metode-<?= $kode ?>" value="<?= $kode ?>"<?= isset($galat['metode']) ? ' aria-invalid="true"' : '' ?>
                                    aria-describedby="metode-<?= $kode ?>-bantuan"<?= $metode === $kode ? ' checked' : '' ?>>
                                <div class="min-w-0">
                                    <label class="text-sm font-semibold text-gray-900" for="metode-<?= $kode ?>">
                                        <?= esc($label) ?><?= $kode === Klasemen::MEDALI ? ' (bawaan)' : '' ?>
                                    </label>
                                    <p id="metode-<?= $kode ?>-bantuan" class="text-sm text-gray-600"><?= esc($keteranganMetode[$kode]) ?></p>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <?php if (isset($galat['metode'])) : ?>
                        <p id="metode-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat['metode']) ?></p>
                    <?php endif ?>
                </fieldset>
                <p class="mt-4 text-sm text-gray-600">
                    Bila masih sama, sekolah-sekolah itu berbagi peringkat yang sama. Peringkat 1–3 di setiap jenjang adalah juara umum I–III.
                </p>
            </section>

            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-poin">
                <h2 id="judul-poin" class="text-lg font-bold text-gray-900">Poin per peringkat</h2>
                <p class="mb-4 mt-1 text-sm text-gray-600" id="poin-bantuan">
                    Poin yang didapat sekolah untuk setiap atlet atau regu di peringkat babak Final. Bawaan: <?= esc(implode('-', array_slice(Klasemen::POIN_BAWAAN, 0, 6))) ?>, peringkat 7 dan 8 tanpa poin.
                    Angka bulat 0–<?= MetodeKlasemen::POIN_MAKSIMAL ?>, tidak boleh naik untuk peringkat berikutnya. Peringkat sama di Final mendapat poin yang sama.
                </p>
                <?php if (isset($galat['poin'])) : ?>
                    <p class="mb-3 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-medium text-red-900" id="poin-galat" role="alert"><?= esc($galat['poin']) ?></p>
                <?php endif ?>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <?php foreach ($poin as $i => $nilai) : ?>
                        <?php $id = 'poin_' . ($i + 1); ?>
                        <div class="min-w-0">
                            <label for="<?= $id ?>" class="mb-1 block text-sm font-semibold text-gray-900">Peringkat <?= $i + 1 ?></label>
                            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="3" autocomplete="off"
                                class="<?= $kelasKotak(isset($galat[$id])) ?>" id="<?= $id ?>" name="poin[]" value="<?= esc($nilai, 'attr') ?>"<?= isset($galat[$id]) ? ' aria-invalid="true"' : '' ?>
                                aria-describedby="poin-bantuan<?= isset($galat[$id]) ? ' ' . $id . '-galat' : '' ?>">
                            <?php if (isset($galat[$id])) : ?>
                                <p id="<?= $id ?>-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat[$id]) ?></p>
                            <?php endif ?>
                        </div>
                    <?php endforeach ?>
                </div>
                <?php if ($poinGalat === [] && $metode === Klasemen::POIN) : ?>
                    <p class="mt-4 text-sm text-gray-900" data-ringkasan-poin>Berlaku sekarang: <?= esc(Klasemen::teksPoin(array_map('intval', $poin))) ?>.</p>
                <?php endif ?>
            </section>
        </div>

        <div class="flex flex-wrap gap-2">
            <?= komponen('tombol', ['label' => 'Simpan metode klasemen', 'tipe' => 'submit', 'ikon' => 'check']) ?>
            <?= komponen('tombol', ['label' => 'Lihat klasemen', 'href' => site_url('admin/klasemen'), 'varian' => 'kedua', 'ikon' => 'trophy']) ?>
        </div>
    </form>
</div>
<?= $this->endSection() ?>
