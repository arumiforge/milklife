<?php
/**
 * Kotak isian berlabel (input Flowbite Pro) dengan teks bantuan dan galat.
 *
 *     komponen('isian', ['label' => 'Nama sekolah', 'nama' => 'nama', 'nilai' => old('nama'), 'galat' => $galat['nama'] ?? null, 'wajib' => true]);
 *
 * @var string                     $label
 * @var string                     $nama
 * @var string|null                $id       bawaan sama dengan `nama`
 * @var string|null                $tipe     text (bawaan) | email | tel | number | date | password | textarea
 * @var string|null                $nilai
 * @var string|null                $bantuan  teks kecil di bawah isian
 * @var string|null                $galat    pesan galat; isian ditandai merah
 * @var bool|null                  $wajib
 * @var array<string, scalar|null> $atribut  atribut tambahan (`placeholder`, `inputmode`, `autocomplete`, …)
 */

$id ??= $nama;
$tipe ??= 'text';
$adaGalat = ! empty($galat);
$kelas = 'block w-full rounded-lg border p-3 text-base sm:text-sm focus:ring-4 '
    . ($adaGalat
        ? 'border-red-500 bg-red-50 text-red-900 placeholder-red-400 focus:border-red-500 focus:ring-red-100'
        : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');
$terkait = trim((empty($bantuan) ? '' : $id . '-bantuan') . ' ' . ($adaGalat ? $id . '-galat' : ''));
$atribut = ($atribut ?? []) + [
    'required'         => $wajib ?? false,
    'aria-invalid'     => $adaGalat ? 'true' : null,
    'aria-describedby' => $terkait === '' ? null : $terkait,
];
?>
<div>
    <label for="<?= esc($id) ?>" class="mb-1.5 block text-sm font-semibold text-gray-900"><?= esc($label) ?><?php if ($wajib ?? false) : ?> <span class="text-red-700" aria-hidden="true">*</span><?php endif ?></label>
    <?php if ($tipe === 'textarea') : ?>
        <textarea id="<?= esc($id) ?>" name="<?= esc($nama) ?>" rows="4" class="<?= $kelas ?>"<?= atribut_html($atribut) ?>><?= esc($nilai ?? '') ?></textarea>
    <?php else : ?>
        <input type="<?= esc($tipe) ?>" id="<?= esc($id) ?>" name="<?= esc($nama) ?>" value="<?= esc($nilai ?? '') ?>" class="<?= $kelas ?>"<?= atribut_html($atribut) ?>>
    <?php endif ?>
    <?php if (! empty($bantuan)) : ?>
        <p id="<?= esc($id) ?>-bantuan" class="mt-1.5 text-sm text-gray-500"><?= esc($bantuan) ?></p>
    <?php endif ?>
    <?php if ($adaGalat) : ?>
        <p id="<?= esc($id) ?>-galat" class="mt-1.5 flex items-start gap-1 text-sm font-medium text-red-700"><?= ikon('circle-alert', 'size-4 mt-0.5') ?><?= esc($galat) ?></p>
    <?php endif ?>
</div>
