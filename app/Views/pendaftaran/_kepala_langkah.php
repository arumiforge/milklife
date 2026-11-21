<?php
/**
 * Kepala satu langkah wizard (F9-05): ilustrasi panduan G9-03 bila berkasnya
 * sudah ada (`IsiBantuan::gambarLangkah()`, tanpa gambar bila belum), judul
 * `<h2 id="judul-langkah-<n>">` yang difokuskan JS saat pindah langkah, dan
 * tombol Bantuan.
 *
 * @var int    $langkah 1–5
 * @var string $judul
 */

use App\Libraries\Bantuan\IsiBantuan;

$gambar = IsiBantuan::gambarLangkah((int) $langkah);
?>
<div class="langkah-judul mb-2 flex items-start justify-between gap-3">
    <div class="flex min-w-0 items-center gap-3">
        <?php if ($gambar !== null) : ?>
            <img src="<?= esc($gambar, 'attr') ?>" alt="" class="hidden h-18 w-24 shrink-0 rounded-2xl bg-primary-50 object-cover shadow-lembut sm:block" width="1200" height="900" data-gambar-langkah>
        <?php endif ?>
        <h2 id="judul-langkah-<?= (int) $langkah ?>" class="min-w-0 text-2xl font-extrabold tracking-tight text-primary-900 focus:outline-none sm:text-3xl" tabindex="-1"><?= (int) $langkah ?>. <?= esc($judul) ?></h2>
    </div>
    <?= view('pendaftaran/_tombol_bantuan', ['langkah' => (int) $langkah], ['saveData' => false]) ?>
</div>
