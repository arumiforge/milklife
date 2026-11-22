<?php
/**
 * Bagian Lokasi halaman depan (`#lokasi`, F10-02): alamat singkat dan peta
 * Google Maps, tepat di atas Kontak.
 *
 * - Tidak tampil sama sekali bila alamat embed kosong (`PetaLokasi::embed()`).
 * - Iframe tidak ditulis di HTML: `peta-lokasi.js` (dimuat di bagian `skrip`
 *   `beranda.php`, di luar `<main>`) menyisipkannya dari
 *   `data-peta-src` saat bagian ini mendekati layar (IntersectionObserver),
 *   jadi tidak ada permintaan ke google.com sebelum itu. Tanpa JS atau
 *   sebelum dimuat, kotak peta berisi tautan "Buka di Google Maps".
 * - Rasio 4:3 di ponsel dan 16:9 di desktop lewat kelas Tailwind; tanpa
 *   gaya sebaris (CSP K127).
 *
 * @var string $tempat `IsiHalamanDepan::acara()['tempat']`, misalnya 'Supersoccer Arena, Kudus'; boleh ''.
 */

use App\Libraries\PetaLokasi;

$embed = PetaLokasi::embed();

if ($embed === '') {
    return;
}

$tautan    = PetaLokasi::tautan();
$judulPeta = 'Peta ' . ($tempat !== '' ? $tempat : 'lokasi lomba') . ' (Google Maps)';
$tabBaru   = '<span class="sr-only"> (di tab baru)</span>';
// ikon-sprite: map-pin map external-link
?>
<section id="lokasi" class="scroll-mt-20 bg-white" aria-labelledby="judul-lokasi">
    <div class="mx-auto grid max-w-screen-xl gap-6 px-4 pt-12 sm:pt-16 lg:grid-cols-12 lg:items-start lg:gap-10">
        <div class="lg:col-span-5">
            <p class="judul-tampilan ornamen-judul" lang="en" aria-hidden="true" data-judul-inggris>The Venue</p>
            <h2 id="judul-lokasi" class="judul-keterangan mt-2">Lokasi</h2>
            <?php if ($tempat !== '') : ?>
                <p class="mt-2 flex items-start gap-2 text-lg text-gray-700">
                    <?= ikon('map-pin', 'mt-1 size-5 shrink-0 text-oranye-600') ?>
                    <span class="min-w-0 break-words" data-lokasi-alamat><?= esc($tempat) ?></span>
                </p>
            <?php endif ?>
            <?php if ($tautan !== '') : ?>
                <a class="mt-4 hidden min-h-12 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-base font-bold text-primary-800 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-200 lg:inline-flex" href="<?= esc($tautan, 'attr') ?>" target="_blank" rel="noopener noreferrer" data-lokasi-tautan>
                    <?= ikon('external-link', 'size-5') ?> Buka di Google Maps<?= $tabBaru ?>
                </a>
            <?php endif ?>
        </div>

        <div class="lg:col-span-7">
            <div class="relative aspect-[4/3] overflow-hidden rounded-lg border border-gray-200 bg-gray-100 lg:aspect-video"
                data-peta-lokasi data-peta-src="<?= esc($embed, 'attr') ?>" data-peta-judul="<?= esc($judulPeta, 'attr') ?>">
                <div class="flex size-full flex-col items-center justify-center gap-3 p-6 text-center text-gray-600" data-peta-pengganti>
                    <?= ikon('map', 'size-10 text-primary-700') ?>
                    <?php if ($tautan !== '') : ?>
                        <a class="inline-flex min-h-11 items-center gap-2 font-semibold text-primary-800 underline hover:text-primary-900 focus:outline-none focus:ring-4 focus:ring-primary-200" href="<?= esc($tautan, 'attr') ?>" target="_blank" rel="noopener noreferrer">
                            Buka di Google Maps<?= $tabBaru ?>
                        </a>
                    <?php else : ?>
                        <p>Peta tampil saat bagian ini terlihat.</p>
                    <?php endif ?>
                </div>
            </div>
            <?php if ($tautan !== '') : ?>
                <a class="mt-4 inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-base font-bold text-primary-800 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-200 lg:hidden" href="<?= esc($tautan, 'attr') ?>" target="_blank" rel="noopener noreferrer" data-lokasi-tautan>
                    <?= ikon('external-link', 'size-5') ?> Buka di Google Maps<?= $tabBaru ?>
                </a>
            <?php endif ?>
        </div>
    </div>
</section>
