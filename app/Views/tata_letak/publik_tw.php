<?php
/**
 * Tata letak publik bertampilan baru (Tahap 9, K140, K141), dari potongan
 * navbar dan footer templat Flowbite Pro Landing Pages. Halaman berpindah ke
 * sini dengan `$this->extend('tata_letak/publik_tw')`. Tata letak lama
 * Bootstrap dilepas di F9-17.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul tab peramban (di-escape pemanggil); juga `og:title`.
 * - `keterangan`: teks pendek halaman untuk `og:description` dan
 *   `meta description` (di-escape pemanggil); bawaan kalimat umum acara.
 * - `isi`: isi halaman di dalam `<main id="isi">`. Halaman menulis `<h1>`-nya sendiri.
 * - `kepala`: tag tambahan di `<head>`.
 * - `preload`: `<link rel="preload">` tambahan sebelum CSS, misalnya huruf judul
 *   Barlow Condensed yang tampil di layar pertama (F10-05).
 *
 * Kelas penanda `ada-<nama>` di `<html>` hanya ditulis untuk elemen G10-11 yang
 * berkasnya ada (`GambarOpsional::penandaElemen()`, F10-05).
 * - `skrip`: `<script src>` tambahan, dimuat setelah JS Flowbite. Modul ES
 *   (Three.js) memakai `<script type="module" src>`.
 *
 * Menu dari `App\Libraries\MenuPublik`; teks dan logo kaki sama dengan tata
 * letak lama (`IsiHalamanDepan::kaki()`, logo Bakti Olahraga Djarum Foundation K84).
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\GambarOpsional;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\MenuPublik;

$menu   = MenuPublik::butir();
$tombol = MenuPublik::tombol();
$kaki   = IsiHalamanDepan::kaki();

try {
    $isiDepan = new IsiHalamanDepan();
    $acara    = $isiDepan->acara();
    $tampilan = $isiDepan->tampilan();
    $logo     = IsiHalamanDepan::logoMerek($tampilan);
    $logoKaki = IsiHalamanDepan::logoDjarum($tampilan);
} catch (Throwable) {
    $acara    = ['nama' => 'MilkLife Athletics Challenge', 'seri_tahun' => ''];
    $logo     = IsiHalamanDepan::logoMerek([]);
    $logoKaki = IsiHalamanDepan::logoDjarum([]);
}

// Pratinjau tautan yang dibagikan (Open Graph, F9-19, G9-10). Gambar hanya bila berkasnya ada.
$judulHalaman = trim((string) preg_replace('/\s+/u', ' ', $this->renderSection('judul')));
$keterangan   = trim((string) preg_replace('/\s+/u', ' ', $this->renderSection('keterangan')));
if ($keterangan === '') {
    $keterangan = esc('Pendaftaran, jadwal, dan hasil ' . trim($acara['nama'] . ' ' . $acara['seri_tahun']) . '.');
}
$gambarBagikan = GambarOpsional::bagikan(uri_string());
?>
<!doctype html>
<html lang="id"<?= ($penandaElemen = GambarOpsional::penandaElemen()) === '' ? '' : ' class="' . esc($penandaElemen) . '"' ?>>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $judulHalaman ?></title>
    <meta name="description" content="<?= $keterangan ?>">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="<?= esc($acara['nama']) ?>">
    <meta property="og:title" content="<?= $judulHalaman ?>">
    <meta property="og:description" content="<?= $keterangan ?>">
    <meta property="og:url" content="<?= esc(current_url()) ?>">
    <?php if ($gambarBagikan !== null) : ?>
        <meta property="og:image" content="<?= esc($gambarBagikan) ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta name="twitter:card" content="summary_large_image">
    <?php else : ?>
        <meta name="twitter:card" content="summary">
    <?php endif ?>
    <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <?= $this->renderSection('preload') ?>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
    <?= $this->renderSection('kepala') ?>
</head>
<body class="flex min-h-screen flex-col bg-primary-50<?= ada_gambar('pola/titik-halus.webp') ? ' pola-titik' : '' ?>">
    <a href="#isi" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-primary-800 shadow-angkat focus:not-sr-only focus:fixed focus:start-4 focus:top-4">Lompat ke isi</a>

    <header class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/90 backdrop-blur" data-kepala-publik>
        <nav class="mx-auto flex max-w-screen-xl flex-wrap items-center justify-between gap-3 px-4 py-2.5" aria-label="Menu utama">
            <a href="<?= site_url('/') ?>" class="flex min-h-11 min-w-0 flex-1 items-center gap-3 lg:flex-none" data-merek>
                <?php if ($logo !== null) : ?>
                    <img src="<?= esc($logo['alamat']) ?>" class="h-10 w-auto mix-blend-multiply sm:h-12" alt="<?= esc('Logo ' . $acara['nama']) ?>"<?= $logo['ukuran'] === null ? '' : ' width="' . (int) $logo['ukuran']['lebar'] . '" height="' . (int) $logo['ukuran']['tinggi'] . '"' ?>>
                <?php endif ?>
                <span class="flex min-w-0 flex-col leading-tight">
                    <span class="text-sm font-extrabold text-primary-900 sm:text-lg" data-merek-nama><?= esc($acara['nama']) ?></span>
                    <?php if (($acara['seri_tahun'] ?? '') !== '') : ?>
                        <span class="text-xs font-semibold text-oranye-700 sm:text-sm" data-kepala-seri><?= esc($acara['seri_tahun']) ?></span>
                    <?php endif ?>
                </span>
            </a>
            <button type="button" data-collapse-toggle="menu-publik" aria-controls="menu-publik" aria-expanded="false" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-4 focus:ring-gray-200 lg:hidden">
                <?= ikon('menu', 'size-6', 'Buka atau tutup menu') ?>
            </button>
            <div id="menu-publik" class="hidden w-full lg:flex lg:w-auto lg:items-center lg:gap-6">
                <ul class="mt-2 flex flex-col gap-1 border-t border-gray-100 pt-2 font-semibold lg:mt-0 lg:flex-row lg:gap-1 lg:border-0 lg:pt-0">
                    <?php foreach ($menu as $butir) : ?>
                        <li>
                            <a href="<?= esc($butir['alamat']) ?>" class="<?= $butir['aktif'] ? 'bg-primary-100 text-primary-800' : 'text-gray-700 hover:bg-gray-100 hover:text-primary-700' ?> flex min-h-11 items-center rounded-lg px-3"<?= $butir['aktif'] ? ' aria-current="page"' : '' ?>><?= esc($butir['label']) ?></a>
                        </li>
                    <?php endforeach ?>
                </ul>
                <div class="mt-3 flex flex-col gap-2 pb-2 sm:flex-row lg:mt-0 lg:pb-0">
                    <?php foreach ($tombol as $t) : ?>
                        <?= komponen('tombol', ['label' => $t['label'], 'href' => $t['alamat'], 'varian' => $t['varian'], 'ikon' => $t['ikon'], 'ikonKanan' => $t['varian'] === 'oranye']) ?>
                    <?php endforeach ?>
                </div>
            </div>
        </nav>
    </header>

    <main id="isi" class="flex-1">
        <?= view('tata_letak/_pesan_tw', [], ['saveData' => false]) ?>
        <?= $this->renderSection('isi') ?>
    </main>

    <footer class="mt-16 border-t border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-xl flex-col gap-6 px-4 py-8 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-600">
                <p data-kaki-teks><?= nl2br(esc($kaki), false) ?></p>
                <p class="mt-2"><a href="<?= site_url('admin/masuk') ?>" class="inline-flex min-h-11 items-center gap-1 font-semibold text-primary-700 hover:underline"><?= ikon('lock') ?>Masuk panitia</a></p>
            </div>
            <?php if ($logoKaki !== null) : ?>
                <img src="<?= esc($logoKaki['alamat']) ?>" alt="<?= esc($logoKaki['alt']) ?>" class="h-12 w-auto max-w-full self-start sm:self-center" data-kaki-logo<?= $logoKaki['ukuran'] === null ? '' : ' width="' . (int) $logoKaki['ukuran']['lebar'] . '" height="' . (int) $logoKaki['ukuran']['tinggi'] . '"' ?>>
            <?php endif ?>
        </div>
        <div class="pita-merek h-1.5" aria-hidden="true"></div>
    </footer>

    <script src="<?= base_url('aset/vendor/flowbite/flowbite.min.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
