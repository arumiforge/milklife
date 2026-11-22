<?php
/**
 * Kartu berbingkai lembut (Flowbite Pro).
 *
 *     komponen('kartu', ['judul' => 'Kelompok umur', 'ikon' => 'users', 'isi' => '<p>…</p>']);
 *
 * `isi` dan `kaki` berupa HTML yang sudah di-escape pemanggil.
 *
 * @var string|null $judul
 * @var string|null $keterangan teks kecil di bawah judul
 * @var string|null $ikon       ikon Lucide di samping judul
 * @var string|null $isi        HTML
 * @var string|null $kaki       HTML
 * @var string|null $kelas
 */
?>
<section class="<?= esc(trim('rounded-2xl border border-gray-200 bg-white p-5 shadow-lembut sm:p-6 ' . ($kelas ?? ''))) ?>">
    <?php if (! empty($judul)) : ?>
        <header class="mb-4 flex items-start gap-3">
            <?php if (! empty($ikon)) : ?>
                <?= ikon($ikon, 'mt-0.5 size-6 text-primary-700') ?>
            <?php endif ?>
            <div>
                <h2 class="text-lg font-bold text-gray-900"><?= esc($judul) ?></h2>
                <?php if (! empty($keterangan)) : ?>
                    <p class="mt-0.5 text-sm text-gray-500"><?= esc($keterangan) ?></p>
                <?php endif ?>
            </div>
        </header>
    <?php endif ?>
    <?= $isi ?? '' ?>
    <?php if (! empty($kaki)) : ?>
        <footer class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-4"><?= $kaki ?></footer>
    <?php endif ?>
</section>
