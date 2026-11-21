<?php
/**
 * Papan videotron `/layar` (F9-13, K141): kerangka kosong yang diisi
 * `public/aset/js/layar.js` dari data awal (`data-awal`) lalu dari
 * `/layar/data` setiap `data-segar` detik. Tanpa tata letak publik: layar
 * penuh tanpa menu.
 *
 * Parameter alamat (dibaca JS, untuk memaksa bila perlu): `?skala=1.2`
 * (perbesar/perkecil huruf, 0,5–3), `?susunan=lebar|datar|tegak|kecil`,
 * `?baris=12` (baris tabel per halaman), `?lama=15` (detik per tampilan).
 *
 * Latar: adegan Three.js (`aset/vendor/three/`), dengan cadangan potongan
 * gambar G9-06 (`data-latar-*`, hanya yang berkasnya ada) bila adegan tidak
 * jalan, lalu gradien biru.
 *
 * `alat/uji-layar.mjs` menyusun kerangka yang sama untuk uji tampilan
 * Playwright; bila atribut di sini berubah, ubah juga di sana.
 *
 * @var array<string, mixed>  $data  hasil `PapanVideotron::data()`
 * @var array<string, string> $latar kunci (`dasar`, `pita`, `kiri-atas`, `kanan-bawah`) => alamat gambar
 * @var int                   $segar detik antarpengambilan data
 */
$atributLatar = '';

foreach ($latar as $kunci => $alamat) {
    $atributLatar .= ' data-latar-' . esc($kunci, 'attr') . '="' . esc($alamat, 'attr') . '"';
}
?>
<!doctype html>
<html lang="id" class="h-full bg-primary-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="color-scheme" content="dark">
    <meta name="theme-color" content="#011450">
    <title><?= esc('Papan hasil – ' . $data['acara']['nama']) ?></title>
    <link rel="icon" href="<?= aset('gambar/ikon.svg') ?>" type="image/svg+xml">
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
</head>
<body class="h-full overflow-hidden bg-primary-950 font-sans text-white antialiased">
    <div id="layar" class="fixed inset-0 overflow-hidden" data-layar
        data-sumber="<?= site_url('layar/data') ?>"
        data-segar="<?= (int) $segar ?>"
        data-awal="<?= esc((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>"<?= $atributLatar ?>>
        <h1 class="sr-only"><?= esc('Papan hasil ' . $data['acara']['nama']) ?></h1>
        <noscript>
            <p class="p-8 text-2xl font-semibold">Papan hasil memerlukan JavaScript. Hasil lengkap ada di <a class="underline" href="<?= site_url('hasil') ?>"><?= esc(site_url('hasil')) ?></a>.</p>
        </noscript>
    </div>

    <button type="button" data-layar-penuh
        class="fixed end-3 top-3 z-30 hidden size-11 items-center justify-center rounded-lg bg-primary-900/80 text-white ring-1 ring-white/30 transition-opacity hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-white/60"
        aria-label="Layar penuh" title="Layar penuh (F)">
        <?= ikon('maximize', 'size-6', null) ?>
        <?= ikon('minimize', 'hidden size-6', null) ?>
    </button>

    <script type="module" src="<?= aset('js/layar.js') ?>"></script>
</body>
</html>
