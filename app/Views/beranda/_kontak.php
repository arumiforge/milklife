<?php
/**
 * Bagian kontak halaman depan (`#kontak`): narahubung, media sosial, tombol
 * tanya admin dan bagikan lewat WA.
 *
 * Bagian ini selalu tampil karena tombol "Bagikan lewat WA" selalu ada;
 * daftar narahubung, media sosial, dan tombol tanya admin hanya tampil bila
 * isinya ada. Semua tautan keluar dibuka di tab baru. Teks admin di-escape.
 *
 * Data yang tersedia (dari `IsiHalamanDepan`):
 *
 * @var list<array{nama: string, nomor: string, alamat: string}> $narahubung
 *      `narahubung()`: `nomor` tampil '0812-3456-7890', `alamat` 'https://wa.me/62…'; [] bila belum ada.
 * @var list<array{label: string, alamat: string, alamat_ringkas: string, ikon: string}> $mediaSosial
 *      `mediaSosial()`: `alamat` http/https, `alamat_ringkas` 'instagram.com/contoh',
 *      `ikon` nama Bootstrap Icons tanpa "bi-" (dipetakan ke Lucide di sini); [] bila belum ada.
 * @var string|null $waTanya   `waTanya()`: 'https://wa.me/62…?text=…', atau null bila nomornya belum diisi.
 * @var string      $waBagikan `waBagikan()`: 'https://wa.me/?text=…' (selalu ada).
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - `<section id="kontak" aria-labelledby="judul-kontak">` dengan `<h2 id="judul-kontak">`.
 * - Tombol tanya admin hanya bila `$waTanya !== null`; tombol bagikan selalu tampil.
 * - Tautan keluar dibuka di tab baru dengan `rel="noopener noreferrer"`.
 * - Tata letak baru Tailwind (F9-03); penanda uji berupa atribut `data-*`.
 */

$adaKontak = $narahubung !== [] || $waTanya !== null;
$tabBaru   = '<span class="sr-only"> (di tab baru)</span>';

// Nama ikon media sosial dari `TautanModel` (Bootstrap Icons) ke Lucide.
$ikonMedia = static fn (string $nama): string => match ($nama) {
    'instagram', 'facebook', 'youtube' => $nama,
    'twitter-x'                        => 'twitter',
    'tiktok'                           => 'music',
    'whatsapp'                         => 'message-circle',
    'telegram'                         => 'send',
    default                            => 'globe',
};
// ikon-sprite: instagram facebook youtube twitter music message-circle send globe user share-2
?>
<section id="kontak" class="scroll-mt-20 bg-white" aria-labelledby="judul-kontak">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <div class="relative overflow-hidden rounded-3xl border border-gray-200 latar-merek p-6 shadow-lembut sm:p-10">
            <p class="judul-tampilan" lang="en" aria-hidden="true" data-judul-inggris>Get in Touch</p>
            <h2 id="judul-kontak" class="judul-keterangan mt-2">Kontak</h2>
            <p class="mt-2 max-w-2xl text-lg text-gray-600">
                <?php if ($adaKontak) : ?>
                    Ada pertanyaan tentang lomba atau pendaftaran? Hubungi panitia lewat WhatsApp.
                <?php else : ?>
                    Bagikan halaman ini supaya sekolah lain tahu tentang lomba ini.
                <?php endif ?>
            </p>

            <?php if ($narahubung !== [] || $mediaSosial !== []) : ?>
                <div class="mt-8 grid gap-6 lg:grid-cols-2">
                    <?php if ($narahubung !== []) : ?>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Narahubung</h3>
                            <ul class="mt-3 space-y-3" data-daftar-narahubung>
                                <?php foreach ($narahubung as $orang) : ?>
                                    <li class="flex flex-wrap items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4" data-narahubung-butir>
                                        <?= ikon('user', 'size-7 shrink-0 text-primary-700') ?>
                                        <div class="min-w-0 flex-1">
                                            <p class="font-bold text-gray-900 break-words" data-narahubung-nama><?= esc($orang['nama']) ?></p>
                                            <p class="text-gray-600" data-narahubung-nomor><?= esc($orang['nomor']) ?></p>
                                        </div>
                                        <a class="inline-flex size-12 shrink-0 items-center justify-center rounded-lg text-green-600 hover:bg-green-50 focus:outline-none focus:ring-4 focus:ring-green-100" href="<?= esc($orang['alamat'], 'attr') ?>" target="_blank" rel="noopener noreferrer" title="Kirim pesan WA" data-narahubung-wa>
                                            <?= view('tata_letak/_ikon_wa', ['kelas' => 'size-7'], ['saveData' => false]) ?><span class="sr-only">Kirim pesan WA <?= esc($orang['nama']) ?> (di tab baru)</span>
                                        </a>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    <?php endif ?>

                    <?php if ($mediaSosial !== []) : ?>
                        <div>
                            <h3 class="text-sm font-bold uppercase tracking-wide text-gray-500">Media sosial</h3>
                            <ul class="mt-3 space-y-3" data-daftar-media-sosial>
                                <?php foreach ($mediaSosial as $media) : ?>
                                    <li>
                                        <a class="flex min-h-11 items-center gap-3 rounded-2xl border border-gray-200 bg-white p-4 hover:border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-200" href="<?= esc($media['alamat'], 'attr') ?>" target="_blank" rel="noopener noreferrer" data-media-sosial-tautan>
                                            <?= ikon($ikonMedia($media['ikon']), 'size-7 shrink-0 text-oranye-600') ?>
                                            <span class="flex min-w-0 flex-col">
                                                <span class="font-bold text-gray-900" data-media-sosial-label><?= esc($media['label']) ?></span>
                                                <span class="text-sm text-gray-600 break-all" data-media-sosial-alamat><?= esc($media['alamat_ringkas']) ?></span>
                                            </span>
                                            <?= $tabBaru ?>
                                        </a>
                                    </li>
                                <?php endforeach ?>
                            </ul>
                        </div>
                    <?php endif ?>
                </div>
            <?php endif ?>

            <div class="mt-8 flex flex-col gap-3 sm:flex-row" data-kontak-tombol>
                <?php if ($waTanya !== null) : ?>
                    <a class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl tombol-utama-berart px-6 py-3 text-base font-bold focus:outline-none focus:ring-4 focus:ring-primary-300" href="<?= esc($waTanya, 'attr') ?>" target="_blank" rel="noopener noreferrer" data-kontak-tanya>
                        <?= ikon('message-circle') ?> Tanya panitia lewat WA<?= $tabBaru ?>
                    </a>
                <?php endif ?>
                <a class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-base font-bold text-primary-800 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-primary-200" href="<?= esc($waBagikan, 'attr') ?>" target="_blank" rel="noopener noreferrer" data-kontak-bagikan>
                    <?= ikon('share-2') ?> Bagikan lewat WA<?= $tabBaru ?>
                </a>
            </div>
        </div>
    </div>
</section>
