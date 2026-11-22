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
 * - `tanpa_menu_bawah`: isi apa saja (misalnya `1`) untuk menyembunyikan menu
 *   bawah ponsel, dipakai wizard pendaftaran supaya tombol langkah tidak tertutup.
 *
 * Navigasi F10-07 (K154): bilah submenu atas di layar `lg` (`data-bilah-atas`),
 * menu utama (`nav[aria-label="Menu utama"]`), laci kanan ponsel
 * (`tata_letak/_laci_publik`, `aset/js/menu-publik.js`), dan menu bawah ponsel
 * (`tata_letak/_menu_bawah_publik`). Halaman yang menampilkan menu bawah
 * diberi ruang bawah di `<body>` supaya isinya tidak tertutup.
 *
 * Menu dari `App\Libraries\MenuPublik`; teks dan logo kaki sama dengan tata
 * letak lama (`IsiHalamanDepan::kaki()`, logo Bakti Olahraga Djarum Foundation K84).
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\GambarOpsional;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\MenuPublik;
use App\Libraries\VarianFoto;

$menu      = MenuPublik::butir();
$tombol    = MenuPublik::tombol();
$bilahAtas = MenuPublik::bilahAtas();
$sosial    = MenuPublik::mediaSosial();
$kaki      = IsiHalamanDepan::kaki();

try {
    $isiDepan = new IsiHalamanDepan();
    $acara    = $isiDepan->acara();
    $tampilan = $isiDepan->tampilan();
    $logo     = IsiHalamanDepan::logoMerek($tampilan);
    // Logo unggahan admin bisa ratusan KB padahal tampil kecil: pakai salinan kecil (F10-07).
    $kunciLogo = IsiHalamanDepan::logoKepala($tampilan);
    if ($logo !== null && $kunciLogo !== null) {
        $logoKecil = VarianFoto::kecil((string) setting('HalamanDepan.' . $kunciLogo), VarianFoto::LEBAR_LOGO[$kunciLogo === 'logo_persegi' ? 0 : 1]);
        if ($logoKecil !== null) {
            $logo['alamat'] = base_url($logoKecil);
        }
    }
    // Logo Djarum unggahan admin, atau bawaan public/aset/gambar/logo-djarum.png (F10-01).
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
$menuBawah     = trim($this->renderSection('tanpa_menu_bawah')) === '';
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
<body class="flex min-h-screen flex-col bg-primary-50<?= ada_gambar('pola/titik-halus.webp') ? ' pola-titik' : '' ?><?= $menuBawah ? ' ada-menu-bawah' : '' ?>">
    <a href="#isi" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-primary-800 shadow-angkat focus:not-sr-only focus:fixed focus:start-4 focus:top-4">Lompat ke isi</a>

    <?php // ---------- Bilah submenu atas (F10-07, K154): layar lg ke atas ---------- ?>
    <?php if ($bilahAtas !== [] || $sosial !== []) : ?>
        <div class="hidden bg-primary-900 text-white lg:block" data-bilah-atas>
            <div class="mx-auto flex max-w-screen-xl items-center justify-end gap-4 px-4">
                <?php if ($bilahAtas !== []) : ?>
                    <nav aria-label="Menu tambahan">
                        <ul class="flex items-center gap-1 text-xs font-semibold">
                            <?php foreach ($bilahAtas as $butir) : ?>
                                <li>
                                    <a href="<?= esc($butir['alamat']) ?>" class="<?= $butir['aktif'] ? 'text-white underline decoration-oranye-500 decoration-2 underline-offset-4' : 'text-white/80 hover:text-white' ?> inline-flex min-h-10 items-center gap-1.5 px-2"<?= $butir['aktif'] ? ' aria-current="page"' : '' ?><?= $butir['luar'] ? ' target="_blank" rel="noopener"' : '' ?> data-bilah-atas-butir="<?= esc($butir['kunci']) ?>">
                                        <?= ikon($butir['ikon'], 'size-4 text-primary-300') ?><?= esc($butir['label']) ?><?= $butir['luar'] ? '<span class="sr-only"> (di tab baru)</span>' : '' ?>
                                    </a>
                                </li>
                            <?php endforeach ?>
                        </ul>
                    </nav>
                <?php endif ?>
                <?php if ($sosial !== []) : ?>
                    <ul class="flex items-center gap-0.5 border-s border-white/20 ps-3" aria-label="Media sosial" data-bilah-atas-sosial>
                        <?php foreach ($sosial as $media) : ?>
                            <li>
                                <a href="<?= esc($media['alamat']) ?>" target="_blank" rel="noopener" class="inline-flex size-10 items-center justify-center text-white/80 hover:text-white">
                                    <?= ikon($media['ikon'], 'size-4', $media['label'] . ' (di tab baru)') ?>
                                </a>
                            </li>
                        <?php endforeach ?>
                    </ul>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>
    <?php // ---------- Akhir bilah submenu atas ---------- ?>

    <header class="sticky top-0 z-40 border-b border-gray-200/80 bg-white/90 backdrop-blur" data-kepala-publik>
        <nav class="mx-auto flex max-w-screen-xl items-center gap-2 px-4 py-2.5 lg:gap-6" aria-label="Menu utama">
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
            <ul class="hidden flex-1 items-center justify-end gap-1 font-semibold lg:flex" data-menu-utama>
                <?php foreach ($menu as $butir) : ?>
                    <li>
                        <a href="<?= esc($butir['alamat']) ?>" class="<?= $butir['aktif'] ? 'bg-primary-100 text-primary-900' : 'text-gray-700 hover:bg-gray-100 hover:text-primary-700' ?> flex min-h-12 items-center rounded-lg px-3"<?= $butir['aktif'] ? ' aria-current="page"' : '' ?>><?= esc($butir['label']) ?></a>
                    </li>
                <?php endforeach ?>
            </ul>
            <div class="hidden items-center gap-2 lg:flex">
                <?php foreach ($tombol as $t) : ?>
                    <?= komponen('tombol', ['label' => $t['label'], 'href' => $t['alamat'], 'varian' => $t['varian'], 'ikon' => $t['ikon'], 'ikonKanan' => $t['varian'] === 'oranye']) ?>
                <?php endforeach ?>
            </div>
            <?php // Tombol laci di kanan, laci muncul dari kanan (F10-08, permintaan pemilik). ?>
            <button type="button" class="-me-2 inline-flex size-12 shrink-0 items-center justify-center rounded-lg text-primary-900 hover:bg-gray-100 focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-300 lg:hidden" aria-controls="laci-publik" aria-expanded="false" data-buka-laci>
                <?= ikon('menu', 'size-6', 'Buka menu') ?>
            </button>
        </nav>
    </header>

    <?= view('tata_letak/_laci_publik', ['menu' => $menu, 'tombol' => $tombol, 'bilahAtas' => $bilahAtas, 'sosial' => $sosial, 'namaAcara' => $acara['nama']], ['saveData' => false]) ?>

    <main id="isi" class="flex-1">
        <?= view('tata_letak/_pesan_tw', [], ['saveData' => false]) ?>
        <?= $this->renderSection('isi') ?>
    </main>

    <footer class="mt-16 border-t border-gray-200 bg-white">
        <div class="mx-auto flex max-w-screen-xl flex-col items-center gap-6 px-4 py-8 text-center sm:flex-row sm:justify-between sm:text-start">
            <div class="text-sm text-gray-600">
                <p data-kaki-teks><?= nl2br(esc($kaki), false) ?></p>
            </div>
            <?php if ($logoKaki !== null) : ?>
                <img src="<?= esc($logoKaki['alamat']) ?>" alt="<?= esc($logoKaki['alt']) ?>" class="h-12 w-auto max-w-full" data-kaki-logo<?= $logoKaki['ukuran'] === null ? '' : ' width="' . (int) $logoKaki['ukuran']['lebar'] . '" height="' . (int) $logoKaki['ukuran']['tinggi'] . '"' ?>>
            <?php endif ?>
        </div>
        <div class="pita-merek h-1.5" aria-hidden="true"></div>
    </footer>

    <?php if ($menuBawah) : ?>
        <?= view('tata_letak/_menu_bawah_publik', ['butir' => MenuPublik::bawah()], ['saveData' => false]) ?>
    <?php endif ?>

    <script src="<?= base_url('aset/vendor/flowbite/flowbite.min.js') ?>"></script>
    <script src="<?= aset('js/menu-publik.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
