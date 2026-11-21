<?php
/**
 * Kotak pengganti bertampilan baru (Tailwind) untuk gambar yang belum ada,
 * bukan gambar tiruan (K41). Satu potongan untuk semua halaman panel admin
 * Tahap 9 (F9-17); padanan `_tanpa_gambar.php` untuk halaman lama.
 *
 *     <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => '21x9', 'label' => 'Gambar kepala halaman depan'], ['saveData' => false]) ?>
 *
 * `saveData => false` supaya isiannya tidak terbawa ke pemakaian berikutnya.
 * Penanda uji: kelas `tanpa-gambar` dan atribut `data-tanpa-gambar`.
 *
 * @var string|null $rasio `1x1`, `4x3`, `16x9` (bawaan), atau `21x9`
 * @var string|null $label keterangan gambar apa yang belum ada (pilihan, teks biasa)
 * @var string|null $kelas kelas tambahan pada kotak (pilihan), misalnya `w-full`
 */

$kelasRasio = [
    '1x1'  => 'aspect-square',
    '4x3'  => 'aspect-[4/3]',
    '16x9' => 'aspect-video',
    '21x9' => 'aspect-[21/9]',
][$rasio ?? ''] ?? 'aspect-video';
$label = isset($label) ? trim((string) $label) : '';
$teks  = 'Belum ada gambar';
?>
<div class="tanpa-gambar <?= $kelasRasio ?> flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-2 text-center text-gray-600<?= empty($kelas) ? '' : ' ' . esc($kelas, 'attr') ?>" role="img" aria-label="<?= esc($label === '' ? $teks : $teks . ': ' . $label, 'attr') ?>" data-tanpa-gambar>
    <span class="contents" aria-hidden="true">
        <?= ikon('image', 'size-6 text-gray-400') ?>
        <span class="text-sm font-semibold"><?= $teks ?></span>
        <?php if ($label !== '') : ?>
            <span class="text-xs"><?= esc($label) ?></span>
        <?php endif ?>
    </span>
</div>
