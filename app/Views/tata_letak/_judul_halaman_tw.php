<?php
/**
 * Pita judul halaman publik bertampilan baru (F9-03): latar gradien merek,
 * ikon, `<h1>`, keterangan, dan tombol tindakan opsional. Dipakai Unduhan,
 * Hasil, Klasemen, Galeri, Cek Pendaftaran, dan Pendaftaran.
 *
 * F10-09: judul editorial Inggris (`inggris`) tampil besar di atas `<h1>`
 * Indonesia (`tata_letak/_judul_editorial`, K153), tanpa alis (K164).
 *
 *     view('tata_letak/_judul_halaman_tw', ['inggris' => 'Downloads', 'judul' => 'Unduhan', 'ikon' => 'download', 'teks' => '…'], ['saveData' => false]);
 *
 * @var string      $judul      judul Indonesia (`<h1>`)
 * @var string|null $inggris    judul editorial Inggris (F10-09)
 * @var string      $ikon       nama ikon Lucide
 * @var string|null $teks       keterangan di bawah judul (di-escape)
 * @var string|null $kembali    alamat tautan kembali (opsional)
 * @var string|null $labelKembali
 * @var string|null $aksi       HTML tombol (sudah di-escape pemanggil)
 * @var string|null $gambar     ilustrasi G9-09 relatif `public/aset/gambar/` (misalnya `judul/hasil.webp`),
 *                              di kanan pita mulai `md`; tidak dirender bila berkasnya belum ada (F9-19)
 */

$ilustrasi = empty($gambar) ? '' : gambar_opsional($gambar, ['class' => 'h-36 w-auto lg:h-44']);
?>
<div class="latar-merek border-b border-gray-200/70">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12<?= $ilustrasi === '' ? '' : ' md:flex md:items-center md:gap-8' ?>">
        <?php if ($ilustrasi !== '') : ?><div class="min-w-0 md:flex-1"><?php endif ?>
        <?php if (! empty($kembali)) : ?>
            <a href="<?= esc($kembali, 'attr') ?>" class="mb-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline" data-tautan-kembali><?= ikon('arrow-left', 'size-4') ?><?= esc($labelKembali ?? 'Kembali') ?></a>
        <?php endif ?>
        <?= view('tata_letak/_judul_editorial', ['judul' => $judul, 'inggris' => $inggris ?? null, 'ikon' => $ikon], ['saveData' => false]) ?>
        <?php if (! empty($teks)) : ?>
            <p class="mt-3 max-w-3xl text-lg text-gray-600"><?= esc($teks) ?></p>
        <?php endif ?>
        <?php if (! empty($aksi)) : ?>
            <div class="mt-5 flex flex-wrap gap-2"><?= $aksi ?></div>
        <?php endif ?>
        <?php if ($ilustrasi !== '') : ?>
            </div>
            <div class="hidden shrink-0 md:block" data-judul-ilustrasi><?= $ilustrasi ?></div>
        <?php endif ?>
    </div>
</div>
