<?php
/**
 * Kotak pengganti untuk gambar yang belum ada (bukan gambar tiruan) di
 * halaman bertampilan baru (Tahap 9), padanan `tata_letak/_tanpa_gambar.php`
 * dengan data yang sama. Penanda uji: `data-tanpa-gambar`.
 *
 *     <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '1x1', 'label' => 'Foto ofisial'], ['saveData' => false]) ?>
 *
 * @var string|null $rasio `1x1`, `4x3`, `16x9` (bawaan), `21x9`.
 * @var string|null $label Keterangan gambar apa yang belum ada (pilihan). Teks biasa, di-escape di sini.
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
<div class="flex w-full <?= $kelasRasio ?> items-center justify-center rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 text-center text-gray-500" role="img" aria-label="<?= esc($label === '' ? $teks : $teks . ': ' . $label, 'attr') ?>" data-tanpa-gambar="<?= $rasio ?>">
    <span class="flex flex-col items-center gap-1" aria-hidden="true">
        <?= ikon('image', 'size-6') ?>
        <span class="text-xs font-semibold"><?= $teks ?></span>
        <?php if ($label !== '') : ?>
            <span class="text-xs"><?= esc($label) ?></span>
        <?php endif; ?>
    </span>
</div>
