<?php
/**
 * Kotak pengganti untuk gambar yang belum ada (bukan gambar tiruan, K41) di
 * semua halaman bertampilan baru (Tahap 9; disatukan di F9-15 dan F9-17).
 *
 *     <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Foto ofisial'], ['saveData' => false]) ?>
 *
 * `saveData => false` supaya isiannya tidak terbawa ke pemakaian berikutnya.
 * Penanda uji: kelas `tanpa-gambar` dan atribut `data-tanpa-gambar` (berisi rasio).
 *
 * @var string|null $rasio `1x1`, `4x3`, `16x9` (bawaan), atau `21x9`
 * @var string|null $label keterangan gambar apa yang belum ada (pilihan, teks biasa, di-escape di sini)
 * @var string|null $kelas kelas tambahan pada kotak (pilihan)
 */

$rasio = isset($rasio) && in_array($rasio, ['1x1', '4x3', '16x9', '21x9'], true) ? $rasio : '16x9';
$label = isset($label) ? trim((string) $label) : '';
$teks  = 'Belum ada gambar';
$kelasRasio = [
    '1x1'  => 'aspect-square',
    '4x3'  => 'aspect-[4/3]',
    '16x9' => 'aspect-video',
    '21x9' => 'aspect-[21/9]',
][$rasio];
?>
<div class="tanpa-gambar flex w-full <?= $kelasRasio ?> items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 text-center text-gray-600<?= empty($kelas) ? '' : ' ' . esc($kelas, 'attr') ?>" role="img" aria-label="<?= esc($label === '' ? $teks : $teks . ': ' . $label, 'attr') ?>" data-tanpa-gambar="<?= $rasio ?>">
    <span class="flex flex-col items-center gap-1" aria-hidden="true">
        <?= ikon('image', 'size-6 text-gray-400') ?>
        <span class="text-xs font-semibold"><?= $teks ?></span>
        <?php if ($label !== '') : ?>
            <span class="text-xs"><?= esc($label) ?></span>
        <?php endif ?>
    </span>
</div>
