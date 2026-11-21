<?php
/**
 * Kerangka (skeleton) selama data dimuat lewat JavaScript. Hanya untuk bagian
 * yang memang dimuat belakangan (rencana Tahap 9, F9-17); halaman buatan server
 * tidak memakai kerangka palsu. JS mengganti isi wadah ber-`data-kerangka`.
 *
 *     komponen('kerangka', ['jenis' => 'tabel', 'baris' => 5]);
 *
 * @var string|null $jenis teks (bawaan) | kartu | tabel | gambar
 * @var int|null    $baris jumlah baris teks/tabel
 * @var string|null $label teks pembaca layar, bawaan "Memuat data…"
 */

$baris = max(1, (int) ($baris ?? 3));
$jenis ??= 'teks';
$lebar = ['w-11/12', 'w-9/12', 'w-10/12', 'w-7/12', 'w-8/12'];
?>
<div role="status" aria-live="polite" data-kerangka class="w-full">
    <span class="sr-only"><?= esc($label ?? 'Memuat data…') ?></span>
    <?php if ($jenis === 'gambar') : ?>
        <div class="kerangka flex aspect-video w-full items-center justify-center text-gray-300" aria-hidden="true"><?= ikon('image', 'size-10') ?></div>
    <?php elseif ($jenis === 'kartu') : ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-5" aria-hidden="true">
            <div class="kerangka mb-4 h-32 w-full"></div>
            <div class="kerangka mb-3 h-4 w-1/2"></div>
            <?php for ($i = 0; $i < $baris; $i++) : ?>
                <div class="kerangka mb-2 h-3 <?= $lebar[$i % 5] ?>"></div>
            <?php endfor ?>
        </div>
    <?php elseif ($jenis === 'tabel') : ?>
        <div class="space-y-3 rounded-2xl border border-gray-200 bg-white p-4" aria-hidden="true">
            <div class="kerangka h-5 w-full"></div>
            <?php for ($i = 0; $i < $baris; $i++) : ?>
                <div class="flex gap-4"><div class="kerangka h-4 w-1/3"></div><div class="kerangka h-4 w-1/4"></div><div class="kerangka h-4 flex-1"></div></div>
            <?php endfor ?>
        </div>
    <?php else : ?>
        <div class="space-y-2.5" aria-hidden="true">
            <?php for ($i = 0; $i < $baris; $i++) : ?>
                <div class="kerangka h-3.5 <?= $lebar[$i % 5] ?>"></div>
            <?php endfor ?>
        </div>
    <?php endif ?>
</div>
