<?php
/**
 * Tata letak halaman publik, dirancang dari ponsel lebih dulu.
 *
 * Bagian yang diisi tampilan:
 * - `judul`: judul halaman untuk tab peramban. Isi yang berasal dari data wajib di-escape (`esc()`).
 * - `isi`: isi halaman, berada di dalam `<main id="isi">`. Halaman menulis
 *   `<h1>`-nya sendiri; kepala dan kaki tidak memakai heading.
 * - `kepala`: tag tambahan di `<head>` (misalnya `<link>` CSS atau `<meta>` halaman itu).
 * - `skrip`: tag `<script>` tambahan, dimuat setelah Bootstrap, `kemajuan.js`
 *   (bilah muat), dan `konfirmasi.js` (jendela konfirmasi, `window.Konfirmasi`).
 *
 * Menu publik ditulis di `$menuPublik` di bawah (K81): Tentang, Kategori,
 * dan Jadwal melompat ke bagian halaman depan, Unduhan ke halaman unduhan,
 * lalu tombol Panitia ke halaman masuk admin. Pendaftaran dan Cek
 * Pendaftaran dibuka dari dua tombol besar di bagian pembuka halaman depan.
 *
 * Di halaman depan kepala memakai gaya terang (`kepala-beranda`) dengan logo
 * acara (`IsiHalamanDepan::logoKepala()`) dan label seri; halaman lain tetap
 * memakai kepala biru dengan nama acara.
 *
 * Pesan kilat `pesan_sukses` dan `pesan_galat` (misalnya dari filter batas
 * unggah) tampil di atas isi halaman (`tata_letak/_pesan_publik`).
 *
 * Nama acara di kepala diambil dari pengaturan `Acara.nama`
 * (`tata_letak/_merek`). Teks kaki dari `IsiHalamanDepan::kaki()`:
 * `HalamanDepan.footer` (baris baru menjadi `<br>`), atau "nama acara tahun"
 * bila kosong. Logo Bakti Olahraga Djarum Foundation (bila diunggah) di
 * kanan kaki (K84).
 *
 * @var CodeIgniter\View\View $this
 */

use App\Libraries\IsiHalamanDepan;

$beranda = url_is('/');

$menuPublik = [
    ['label' => 'Tentang', 'alamat' => site_url('/') . '#pembuka'],
    ['label' => 'Kategori', 'alamat' => site_url('/') . '#lomba'],
    ['label' => 'Jadwal', 'alamat' => site_url('/') . '#jadwal'],
    ['label' => 'Unduhan', 'alamat' => site_url('unduhan'), 'aktif' => url_is('unduhan')],
];

// Logo dan label seri hanya di kepala halaman depan.
$logoKepala = null;
$seriTahun  = '';

if ($beranda) {
    $isiDepan   = new IsiHalamanDepan();
    $tampilan   = $isiDepan->tampilan();
    $kunciLogo  = IsiHalamanDepan::logoKepala($tampilan);
    $acara      = $isiDepan->acara();
    $seriTahun  = $acara['seri_tahun'];
    $logoKepala = $kunciLogo === null ? null : ['alamat' => $tampilan[$kunciLogo], 'ukuran' => $tampilan['ukuran'][$kunciLogo] ?? null];
}

$kaki = IsiHalamanDepan::kaki();

// Logo Bakti Olahraga Djarum Foundation di kanan kaki halaman, semua halaman publik (K84).
$isiKaki    = $isiDepan ?? new IsiHalamanDepan();
$tampilKaki = $tampilan ?? $isiKaki->tampilan();
$logoKaki   = array_values(array_filter(
    IsiHalamanDepan::logoPembuka($tampilKaki, $isiKaki->acara()['nama']),
    static fn (array $butir): bool => $butir['kunci'] === 'logo_djarum',
))[0] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#021F7B">
    <title><?= $this->renderSection('judul') ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('vendor/bootstrap-icons/bootstrap-icons.min.css') ?>">
    <link rel="stylesheet" href="<?= aset('css/tema.css') ?>">
    <?= $this->renderSection('kepala') ?>
</head>
<body class="tata-publik" data-bs-theme="light">
    <div id="bilah-kemajuan" class="bilah-kemajuan" hidden aria-hidden="true">
        <div class="bilah-kemajuan-isi"></div>
    </div>

    <a class="lompat-ke-isi visually-hidden-focusable" href="#isi">Lompat ke isi</a>

    <header class="kepala-publik<?= $beranda ? ' kepala-beranda' : '' ?>">
        <nav class="navbar navbar-expand-lg <?= $beranda ? 'navbar-light' : 'navbar-dark' ?>" aria-label="Menu utama">
            <div class="container">
                <a class="navbar-brand merek py-2" href="<?= site_url('/') ?>">
                    <?php if ($logoKepala !== null) : ?>
                        <img class="kepala-logo" src="<?= esc($logoKepala['alamat'], 'attr') ?>" alt="<?= esc('Logo ' . $acara['nama'], 'attr') ?>"<?= $logoKepala['ukuran'] === null ? '' : ' width="' . $logoKepala['ukuran']['lebar'] . '" height="' . $logoKepala['ukuran']['tinggi'] . '"' ?>>
                    <?php else : ?>
                        <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
                    <?php endif ?>
                </a>
                <?php if ($seriTahun !== '') : ?>
                    <p class="kepala-seri"><?= esc($seriTahun) ?></p>
                <?php endif ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu-publik" aria-controls="menu-publik" aria-expanded="false" aria-label="Buka atau tutup menu">
                    <span class="navbar-toggler-icon" aria-hidden="true"></span>
                </button>
                <div class="collapse navbar-collapse" id="menu-publik">
                    <ul class="navbar-nav ms-lg-auto gap-lg-2">
                        <?php foreach ($menuPublik as $butir) : ?>
                            <li class="nav-item">
                                <?php if ($butir['aktif'] ?? false) : ?>
                                    <a class="nav-link active" aria-current="page" href="<?= $butir['alamat'] ?>"><?= esc($butir['label']) ?></a>
                                <?php else : ?>
                                    <a class="nav-link" href="<?= $butir['alamat'] ?>"><?= esc($butir['label']) ?></a>
                                <?php endif ?>
                            </li>
                        <?php endforeach ?>
                    </ul>
                    <a class="btn tombol-panitia ms-lg-4" href="<?= site_url('admin/masuk') ?>">Panitia</a>
                </div>
            </div>
        </nav>
    </header>

    <main id="isi">
        <?= view('tata_letak/_pesan_publik', [], ['saveData' => false]) ?>
        <?= $this->renderSection('isi') ?>
    </main>

    <footer class="kaki-publik">
        <div class="container py-4 kaki-isi">
            <p class="mb-0"><?= nl2br(esc($kaki), false) ?></p>
            <?php if ($logoKaki !== null) : ?>
                <img class="kaki-logo" src="<?= esc($logoKaki['alamat'], 'attr') ?>" alt="<?= esc($logoKaki['alt'], 'attr') ?>"<?= $logoKaki['ukuran'] === null ? '' : ' width="' . $logoKaki['ukuran']['lebar'] . '" height="' . $logoKaki['ukuran']['tinggi'] . '"' ?>>
            <?php endif ?>
        </div>
    </footer>

    <script src="<?= aset('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
    <?php // Bilah muat dan jendela konfirmasi untuk semua halaman publik (K89). ?>
    <script src="<?= aset('js/kemajuan.js') ?>"></script>
    <script src="<?= aset('js/konfirmasi.js') ?>"></script>
    <script src="<?= aset('js/pilih-tanggal.js') ?>"></script>
    <?= $this->renderSection('skrip') ?>
</body>
</html>
