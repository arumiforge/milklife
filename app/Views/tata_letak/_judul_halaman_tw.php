<?php
/**
 * Pita judul halaman publik bertampilan baru (F9-03): latar gradien merek,
 * ikon, `<h1>`, keterangan, dan tombol tindakan opsional. Dipakai Unduhan,
 * Hasil, dan Klasemen; halaman publik lain boleh memakainya.
 *
 *     view('tata_letak/_judul_halaman_tw', ['judul' => 'Unduhan', 'ikon' => 'download', 'teks' => '…'], ['saveData' => false]);
 *
 * @var string      $judul
 * @var string      $ikon       nama ikon Lucide
 * @var string|null $teks       keterangan di bawah judul (di-escape)
 * @var string|null $kembali    alamat tautan kembali (opsional)
 * @var string|null $labelKembali
 * @var string|null $aksi       HTML tombol (sudah di-escape pemanggil)
 */
?>
<div class="latar-merek border-b border-gray-200/70">
    <div class="mx-auto max-w-screen-xl px-4 py-8 sm:py-12">
        <?php if (! empty($kembali)) : ?>
            <a href="<?= esc($kembali, 'attr') ?>" class="mb-3 inline-flex min-h-11 items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline" data-tautan-kembali><?= ikon('arrow-left', 'size-4') ?><?= esc($labelKembali ?? 'Kembali') ?></a>
        <?php endif ?>
        <div class="flex flex-wrap items-center gap-4">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-white text-oranye-600 shadow-lembut sm:size-14"><?= ikon($ikon, 'size-6 sm:size-7') ?></span>
            <h1 class="min-w-0 text-3xl font-extrabold tracking-tight text-primary-900 break-words sm:text-4xl"><?= esc($judul) ?></h1>
        </div>
        <?php if (! empty($teks)) : ?>
            <p class="mt-3 max-w-3xl text-lg text-gray-600"><?= esc($teks) ?></p>
        <?php endif ?>
        <?php if (! empty($aksi)) : ?>
            <div class="mt-5 flex flex-wrap gap-2"><?= $aksi ?></div>
        <?php endif ?>
    </div>
</div>
