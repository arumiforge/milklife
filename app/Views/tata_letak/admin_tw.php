<?php
/**
 * Tata letak panel admin bertampilan baru (Tahap 9, K140, K141), dari potongan
 * navbar dan sidebar templat Flowbite Pro Admin Dashboard. Halaman berpindah ke
 * sini dengan `$this->extend('tata_letak/admin_tw')` (F9-14 sampai F9-16);
 * tata letak lama Bootstrap dilepas di F9-17. Menu dari
 * `App\Libraries\Panel\MenuAdmin`.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: nama halaman; tab peramban `<judul> – Admin <nama acara>`.
 * - `judul_halaman` (pilihan): `<h1>` di kepala; bila kosong memakai `judul`.
 * - `jejak` (pilihan): jejak navigasi dari `admin/_jejak_tw` (K117). Bila
 *   kosong, jejak disusun dari menu dan judul (`MenuAdmin::jejak()`, F10-03),
 *   jadi setiap halaman admin punya jejak.
 * - `isi`: isi halaman, dimulai dari `<h2>`; bungkus dengan
 *   `<div class="p-4 sm:p-6">`.
 * - `kepala` (pilihan): tag tambahan di `<head>`.
 * - `skrip`: `<script src>` tambahan, dimuat paling akhir.
 * - `tabel` (pilihan): halaman dengan tabel data (`<table data-tabel-admin>`)
 *   mengisi bagian ini dengan teks apa pun. jQuery, DataTables (tanpa gaya
 *   Bootstrap), dan `aset/js/tabel-admin.js` lalu dimuat sebelum `skrip`;
 *   gaya tabelnya kelas Tailwind yang dipasang `tabel-admin.js` karena
 *   `<body data-tata="tw">`.
 *
 * Bilah kemajuan di atas halaman (`#bilah-kemajuan`, `aset/js/kemajuan.js`)
 * sama dengan tata letak lama: menyala saat formulir dikirim, tautan dibuka,
 * atau tabel memuat data.
 *
 * Warna (K154, F10-03): sidebar biru tua `primary-900` (#021F7B) setinggi
 * layar dengan pojok kiri atas biru `primary-700` (#234987) berisi logo resmi
 * (`IsiHalamanDepan::logoMerek()`) tanpa latar tambahan (F10-08); kepala dan isi terang.
 * Sidebar `w-72`, isi `lg:ps-72` tanpa batas lebar. Kelompok dan submenu
 * dilipat `aset/js/menu-admin.js`.
 *
 * Ponsel: sidebar menjadi laci (Flowbite `data-drawer-*`) yang dibuka tombol
 * menu di kepala (K131, K133) atau butir Menu di menu bawah (F10-03); menu
 * bawah 5 butir, butir aktif ikon dan label oranye (K133). Nama admin menuju
 * Akun; Keluar `POST` dengan CSRF. Penanda uji:
 * `data-menu-admin="samping|bawah"` pada `<nav>`, `data-kepala-admin` pada
 * `<header>`, `data-pojok-admin` pada blok logo.
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Panel\MenuAdmin;

try {
    $logoMerek = IsiHalamanDepan::logoMerek((new IsiHalamanDepan())->tampilan());
} catch (Throwable) {
    $logoMerek = IsiHalamanDepan::logoMerek([]);
}

$judul        = trim($this->renderSection('judul'));
$judulHalaman = trim($this->renderSection('judul_halaman'));
$judulHalaman = $judulHalaman === '' ? $judul : $judulHalaman;
$judulTab     = ($judul === '' ? '' : $judul . ' – ') . 'Admin ' . esc(AcaraController::namaTampil());
$jejak        = trim($this->renderSection('jejak'));
// Halaman tanpa jejak sendiri memakai jejak dari menu (F10-03).
$jejak = $jejak !== '' ? $jejak : view('admin/_jejak_tw', [
    'jejak' => MenuAdmin::jejak(trim(html_entity_decode(strip_tags($judul === '' ? $judulHalaman : $judul), ENT_QUOTES | ENT_HTML5, 'UTF-8'))),
], ['saveData' => false]);
$pakaiTabel   = trim($this->renderSection('tabel')) !== '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light">
    <title><?= $judulTab ?></title>
    <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
    <?= $this->renderSection('kepala') ?>
</head>
<body class="bg-gray-50" data-tata="tw">
    <div id="bilah-kemajuan" class="fixed inset-x-0 top-0 z-50 h-[3px]" hidden aria-hidden="true">
        <div class="bilah-kemajuan-isi h-full w-0 bg-oranye-500 transition-[width,opacity] duration-300 ease-out [.selesai_&]:opacity-0"></div>
    </div>

    <a href="#isi" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-primary-800 shadow-angkat focus:not-sr-only focus:fixed focus:start-4 focus:top-4">Lompat ke isi</a>

    <header data-kepala-admin class="fixed inset-x-0 top-0 z-30 flex h-14 items-center gap-2 border-b border-gray-200 bg-white px-2 sm:px-4 lg:start-72">
        <button type="button" data-drawer-target="menu-laci" data-drawer-toggle="menu-laci" aria-controls="menu-laci" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 lg:hidden">
            <?= ikon('menu', 'size-6', 'Buka menu') ?>
        </button>
        <h1 class="min-w-0 flex-1 truncate text-lg font-bold text-gray-900"><?= $judulHalaman ?></h1>
        <a href="<?= site_url('admin/akun') ?>" data-nama-admin class="hidden min-h-11 items-center gap-2 rounded-lg px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 sm:inline-flex" title="Buka Akun: ganti nama dan kata sandi">
            <?= ikon('circle-user-round', 'size-6 text-gray-500') ?><span class="sr-only">Masuk sebagai</span><span class="max-w-40 truncate"><?= esc((string) session('admin_nama')) ?></span>
        </a>
        <form action="<?= site_url('admin/keluar') ?>" method="post">
            <?= csrf_field() ?>
            <?= komponen('tombol', ['label' => 'Keluar', 'tipe' => 'submit', 'varian' => 'kedua', 'ukuran' => 'kecil', 'ikon' => 'log-out', 'kelas' => 'min-h-11']) ?>
        </form>
    </header>

    <aside id="menu-laci" class="fixed start-0 top-0 z-40 flex h-screen w-72 -translate-x-full flex-col bg-primary-900 transition-transform lg:z-30 lg:translate-x-0" tabindex="-1" aria-label="Menu panel admin">
        <div class="flex h-14 shrink-0 items-center gap-2 bg-primary-700 ps-3 pe-1" data-pojok-admin>
            <a href="<?= site_url('admin/dasbor') ?>" class="flex min-w-0 flex-1 items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white" data-merek-admin>
                <span class="inline-flex h-10 shrink-0 items-center justify-center">
                    <?php if ($logoMerek !== null) : ?>
                        <img src="<?= esc($logoMerek['alamat']) ?>" class="h-8 w-auto max-w-16" alt=""<?= $logoMerek['ukuran'] === null ? '' : ' width="' . (int) $logoMerek['ukuran']['lebar'] . '" height="' . (int) $logoMerek['ukuran']['tinggi'] . '"' ?> data-logo-merek>
                    <?php else : ?>
                        <?= ikon('zap', 'size-6 text-white') ?>
                    <?php endif ?>
                </span>
                <span class="min-w-0 leading-tight"><span class="block truncate text-sm font-extrabold text-white"><?= esc(AcaraController::namaTampil()) ?></span> <span class="block text-xs font-semibold text-langit-300">Panel admin</span></span>
            </a>
            <button type="button" data-drawer-hide="menu-laci" aria-controls="menu-laci" class="inline-flex size-11 shrink-0 items-center justify-center rounded-lg text-white hover:bg-white/10 lg:hidden">
                <?= ikon('x', '', 'Tutup menu') ?>
            </button>
        </div>
        <nav aria-label="Menu admin" class="flex-1 overflow-y-auto px-2 py-3" data-menu-admin="samping">
            <?= view('tata_letak/_menu_admin_tw', ['tempatMenu' => 'samping'], ['saveData' => false]) ?>
        </nav>
    </aside>

    <div class="pb-20 pt-14 lg:ps-72 lg:pb-0">
        <main id="isi">
            <div class="-mb-2 px-4 pt-3 sm:-mb-3 sm:px-6"><?= $jejak ?></div>
            <?= view('tata_letak/_pesan_tw', [], ['saveData' => false]) ?>
            <?= $this->renderSection('isi') ?>
        </main>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden" aria-label="Menu pendaftaran" data-menu-admin="bawah">
        <?= view('tata_letak/_menu_admin_tw', ['tempatMenu' => 'bawah'], ['saveData' => false]) ?>
    </nav>

    <script src="<?= base_url('aset/vendor/flowbite/flowbite.min.js') ?>"></script>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <script src="<?= aset('js/menu-admin.js') ?>"></script>
    <?php if ($pakaiTabel) : ?>
    <script src="<?= aset('vendor/jquery/jquery.min.js') ?>"></script>
    <script src="<?= aset('vendor/datatables/js/dataTables.min.js') ?>"></script>
    <script src="<?= aset('js/tabel-admin.js') ?>"></script>
    <?php endif ?>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
