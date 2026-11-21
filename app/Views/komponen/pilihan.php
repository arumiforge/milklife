<?php
/**
 * Daftar pilihan berlabel (select Flowbite Pro).
 *
 *     komponen('pilihan', ['label' => 'Kabupaten', 'nama' => 'kabupaten', 'opsi' => ['pati' => 'Kab. Pati'], 'nilai' => 'pati', 'kosong' => 'Pilih kabupaten']);
 *
 * @var string                $label
 * @var string                $nama
 * @var string|null           $id
 * @var array<string, string> $opsi    nilai => label
 * @var string|null           $nilai
 * @var string|null           $kosong  label pilihan kosong di atas (tanpa nilai)
 * @var string|null           $bantuan
 * @var string|null           $galat
 * @var bool|null             $wajib
 */

$id ??= $nama;
$adaGalat = ! empty($galat);
$terkait  = trim((empty($bantuan) ? '' : $id . '-bantuan') . ' ' . ($adaGalat ? $id . '-galat' : ''));
?>
<div>
    <label for="<?= esc($id) ?>" class="mb-1.5 block text-sm font-semibold text-gray-900"><?= esc($label) ?><?php if ($wajib ?? false) : ?> <span class="text-red-700" aria-hidden="true">*</span><?php endif ?></label>
    <select id="<?= esc($id) ?>" name="<?= esc($nama) ?>" class="<?= $adaGalat ? 'border-red-500 bg-red-50 focus:border-red-500 focus:ring-red-100' : 'border-gray-300 bg-white focus:border-primary-600 focus:ring-primary-100' ?> block min-h-11 w-full rounded-lg border p-2.5 text-base text-gray-900 focus:ring-4 sm:text-sm"<?= atribut_html(['required' => $wajib ?? false, 'aria-invalid' => $adaGalat ? 'true' : null, 'aria-describedby' => $terkait === '' ? null : $terkait]) ?>>
        <?php if (isset($kosong)) : ?>
            <option value=""><?= esc($kosong) ?></option>
        <?php endif ?>
        <?php foreach ($opsi as $nilaiOpsi => $labelOpsi) : ?>
            <option value="<?= esc((string) $nilaiOpsi) ?>"<?= (string) $nilaiOpsi === (string) ($nilai ?? '') ? ' selected' : '' ?>><?= esc($labelOpsi) ?></option>
        <?php endforeach ?>
    </select>
    <?php if (! empty($bantuan)) : ?>
        <p id="<?= esc($id) ?>-bantuan" class="mt-1.5 text-sm text-gray-500"><?= esc($bantuan) ?></p>
    <?php endif ?>
    <?php if ($adaGalat) : ?>
        <p id="<?= esc($id) ?>-galat" class="mt-1.5 flex items-start gap-1 text-sm font-medium text-red-700"><?= ikon('circle-alert', 'size-4 mt-0.5') ?><?= esc($galat) ?></p>
    <?php endif ?>
</div>
