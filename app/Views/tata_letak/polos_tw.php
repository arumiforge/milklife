<?php
/**
 * Tata letak polos bertampilan baru (Tahap 9) untuk halaman masuk admin:
 * latar biru muda, nama acara di atas (pengaturan `Acara.nama`), dan satu
 * kartu putih di tengah (lebar paling besar 28rem). Padanan `polos.php`.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman; tab peramban menampilkan `<judul> – <nama acara>`.
 * - `isi`: isi kartu (judul `<h1>`, pesan, formulir), tanpa kartu sendiri.
 * - `kepala` (pilihan): tambahan di `<head>`, misalnya `<meta name="robots">`.
 * - `skrip`: `<script src>` tambahan.
 *
 * Gambar Codex (F9-19, hanya bila berkasnya ada): ilustrasi G9-08
 * `admin/masuk.webp` di sisi kanan layar lebar (`lg` ke atas, `data-polos-ilustrasi`)
 * dan hiasan pita G9-12 `pola/pita-sudut.webp` di sudut kanan atas (`data-hiasan`);
 * mulai layar lg, bokeh G9-12 `pola/bintik-stadion.webp` di belakang kartu.
 *
 * Penanda uji: `data-polos-merek` (nama acara), `data-polos-kartu` (kartu).
 *
 * @var CodeIgniter\View\View $this
 */

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\IsiHalamanDepan;

try {
    $logoMerek = IsiHalamanDepan::logoMerek((new IsiHalamanDepan())->tampilan());
} catch (Throwable) {
    $logoMerek = IsiHalamanDepan::logoMerek([]);
}

$judul     = trim($this->renderSection('judul'));
$namaAcara = esc(AcaraController::namaTampil());
$judulTab  = $judul === '' ? $namaAcara : $judul . ' – ' . $namaAcara;
$ilustrasi = gambar_opsional('admin/masuk.webp', ['class' => 'size-full object-cover']);
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $judulTab ?></title>
    <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
<?= $this->renderSection('kepala') ?>
</head>
<body class="min-h-screen bg-primary-50">
<div class="<?= $ilustrasi === '' ? '' : 'lg:grid lg:grid-cols-2' ?>">
    <main id="isi" class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <?= gambar_opsional('pola/bintik-stadion.webp', ['class' => 'pointer-events-none absolute inset-0 hidden size-full select-none object-cover lg:block', 'data-hiasan' => 'bintik-stadion']) ?>
        <?= gambar_opsional('pola/pita-sudut.webp', ['class' => 'pointer-events-none absolute right-0 top-0 w-40 select-none sm:w-64', 'data-hiasan' => 'pita-sudut']) ?>
        <div class="relative w-full max-w-md">
            <p class="mb-6 text-center" data-polos-merek>
                <a href="<?= site_url('/') ?>" class="inline-flex items-center gap-2 text-xl font-extrabold text-primary-900 hover:text-primary-700">
                    <?php if ($logoMerek !== null) : ?>
                        <img src="<?= esc($logoMerek['alamat']) ?>" class="h-14 w-auto" alt=""<?= $logoMerek['ukuran'] === null ? '' : ' width="' . (int) $logoMerek['ukuran']['lebar'] . '" height="' . (int) $logoMerek['ukuran']['tinggi'] . '"' ?> data-logo-merek>
                    <?php else : ?>
                        <span class="inline-flex size-10 items-center justify-center rounded-xl bg-primary-700 text-white"><?= ikon('zap') ?></span>
                    <?php endif ?>
                    <span><?= $namaAcara ?></span>
                </a>
            </p>
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-lembut sm:p-8" data-polos-kartu>
                <?= $this->renderSection('isi') ?>
            </div>
        </div>
    </main>
    <?php if ($ilustrasi !== '') : ?>
        <div class="hidden overflow-hidden bg-white lg:block" data-polos-ilustrasi><?= $ilustrasi ?></div>
    <?php endif ?>
</div>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
