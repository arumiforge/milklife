<?php
/**
 * Kotak pengganti untuk gambar yang belum ada (bukan gambar tiruan).
 *
 * Pemakaian di view lain:
 *
 *     <?= view('tata_letak/_tanpa_gambar', ['rasio' => '21x9', 'label' => 'Gambar kepala halaman depan'], ['saveData' => false]) ?>
 *
 * `saveData => false` supaya `rasio` dan `label` tidak terbawa ke pemakaian berikutnya.
 *
 * @var string|null $rasio Rasio kotak dari kelas `ratio-*` Bootstrap: `1x1`, `4x3`, `16x9` (bawaan), `21x9`.
 * @var string|null $label Keterangan gambar apa yang belum ada (pilihan). Teks biasa, di-escape di sini.
 */

$rasio = isset($rasio) && in_array($rasio, ['1x1', '4x3', '16x9', '21x9'], true) ? $rasio : '16x9';
$label = isset($label) ? trim((string) $label) : '';
$teks  = 'Belum ada gambar';
?>
<div class="tanpa-gambar ratio ratio-<?= $rasio ?>" role="img" aria-label="<?= esc($label === '' ? $teks : $teks . ': ' . $label, 'attr') ?>">
    <div class="tanpa-gambar-isi" aria-hidden="true">
        <i class="bi bi-image"></i>
        <span class="tanpa-gambar-teks"><?= $teks ?></span>
        <?php if ($label !== '') : ?>
            <span class="tanpa-gambar-label"><?= esc($label) ?></span>
        <?php endif; ?>
    </div>
</div>
