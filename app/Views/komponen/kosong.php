<?php
/**
 * Keadaan kosong: ilustrasi (bila berkasnya ada, K41), judul, penjelasan, dan tindakan.
 *
 *     komponen('kosong', ['gambar' => 'gambar/keadaan/kosong-hasil.webp', 'judul' => 'Hasil belum terbit', 'teks' => 'Hasil tampil setelah panitia menerbitkannya.']);
 *
 * @var string|null $gambar jalur relatif `public/aset/` (ilustrasi Codex: G9-04 publik, G9-08
 *                          `gambar/admin/kosong-admin.webp` di panel admin); disembunyikan bila belum ada
 * @var string|null $ikon   ikon Lucide pengganti bila gambar belum ada
 * @var string      $judul
 * @var string|null $teks
 * @var string|null $aksi   HTML tombol
 */

// Jalur `gambar/…` relatif `public/aset/`; pembantu gambar opsional memakai jalur di dalam `gambar/` (F9-19).
$ilustrasi = empty($gambar) ? '' : gambar_opsional((string) preg_replace('#^/?gambar/#', '', $gambar), ['class' => 'mb-4 h-40 w-auto']);
?>
<div class="flex flex-col items-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-10 text-center">
    <?php if ($ilustrasi !== '') : ?>
        <?= $ilustrasi ?>
    <?php else : ?>
        <span class="mb-4 inline-flex size-14 items-center justify-center rounded-2xl bg-primary-100 text-primary-700"><?= ikon($ikon ?? 'info', 'size-7') ?></span>
    <?php endif ?>
    <h2 class="text-lg font-bold text-gray-900"><?= esc($judul) ?></h2>
    <?php if (! empty($teks)) : ?>
        <p class="mt-1 max-w-md text-gray-600"><?= esc($teks) ?></p>
    <?php endif ?>
    <?php if (! empty($aksi)) : ?>
        <div class="mt-5 flex flex-wrap justify-center gap-3"><?= $aksi ?></div>
    <?php endif ?>
</div>
