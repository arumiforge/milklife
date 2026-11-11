<?php
/**
 * Kerangka bersama halaman galat HTML (404, 400, dan galat umum mode produksi).
 *
 * Berdiri sendiri: tidak memakai `extend`, `aset()`, maupun `esc()`, karena
 * halaman galat bisa dirender saat sistem bermasalah. CSS disisipkan dari
 * `galat.css` di folder ini (tanpa permintaan berkas), dan alamat beranda
 * memakai `site_url()` bila bisa, dengan cadangan jalur akar `/`.
 *
 * Dipanggil dengan `require __DIR__ . '/_galat.php'` setelah `$galat` diisi:
 *
 * @var array{kode?: string, judul: string, pesan: list<string>, teknis?: string} $galat
 */

$galatTeks = static fn (string $teks): string => htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$galatBeranda = '/';

try {
    if (function_exists('site_url')) {
        $galatBeranda = site_url('/');
    }
} catch (Throwable) {
    $galatBeranda = '/';
}

$galatGaya = is_file(__DIR__ . '/galat.css') ? (string) file_get_contents(__DIR__ . '/galat.css') : '';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="light">
    <title><?= $galatTeks($galat['judul']) ?> – MilkLife Athletics Challenge</title>
    <style>
<?= $galatGaya ?>
    </style>
</head>
<body>
    <main class="galat-kolom">
        <p class="galat-merek">
            <a href="<?= $galatTeks($galatBeranda) ?>">MilkLife <span>Athletics Challenge</span></a>
        </p>
        <div class="galat-kartu">
            <?php if (($galat['kode'] ?? '') !== '') : ?>
                <p class="galat-kode"><span><?= $galatTeks($galat['kode']) ?></span></p>
            <?php endif; ?>
            <h1><?= $galatTeks($galat['judul']) ?></h1>
            <?php foreach ($galat['pesan'] as $galatParagraf) : ?>
                <p><?= $galatTeks($galatParagraf) ?></p>
            <?php endforeach; ?>
            <a class="galat-tombol" href="<?= $galatTeks($galatBeranda) ?>">Kembali ke beranda</a>
            <?php if (($galat['teknis'] ?? '') !== '') : ?>
                <p class="galat-teknis">Keterangan untuk pengembang (tidak tampil di server produksi): <?= nl2br($galatTeks($galat['teknis'])) ?></p>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>
