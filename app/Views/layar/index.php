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
    <?= view('tata_letak/_ikon_aplikasi', [], ['saveData' => false]) ?>
    <link rel="preload" href="<?= base_url('aset/vendor/inter/inter-latin-wght-normal.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= aset('css/tw.css') ?>">
</head>
<body class="h-full overflow-hidden bg-primary-950 font-sans text-white antialiased">
    <div id="layar" class="fixed inset-0 overflow-hidden" data-layar
        data-sumber="<?= site_url('layar/data') ?>"
        data-segar="<?= (int) $segar ?>"
        data-awal="<?= esc((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'attr') ?>"<?= $atributLatar ?>>
        <h1 class="sr-only"><?= esc('Papan hasil ' . $data['acara']['nama']) ?></h1>
        <?php // Kerangka (F9-17) selama modul `layar.js` dan latarnya dimuat; dibuang skrip begitu papan tersusun. ?>
        <div class="absolute inset-0 flex flex-col gap-[3vmin] bg-linear-to-br from-primary-950 via-primary-900 to-primary-700 p-[4vmin]" role="status" data-kerangka-layar>
            <span class="sr-only">Memuat papan hasil…</span>
            <div class="flex items-center gap-[3vmin]" aria-hidden="true">
                <div class="flex-1 space-y-[1.5vmin]">
                    <div class="h-[5vmin] w-1/2 animate-pulse rounded-lg bg-white/15 motion-reduce:animate-none"></div>
                    <div class="h-[3vmin] w-1/4 animate-pulse rounded-lg bg-white/10 motion-reduce:animate-none"></div>
                </div>
                <div class="h-[6vmin] w-[16vmin] animate-pulse rounded-lg bg-white/15 motion-reduce:animate-none"></div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-[2vmin] rounded-2xl bg-white/5 p-[3vmin]" aria-hidden="true">
                <div class="h-[5vmin] w-2/5 animate-pulse rounded-lg bg-white/15 motion-reduce:animate-none"></div>
                <?php foreach (['w-11/12', 'w-10/12', 'w-9/12', 'w-11/12', 'w-8/12', 'w-10/12'] as $lebar) : ?>
                    <div class="h-[4.5vmin] <?= $lebar ?> animate-pulse rounded-lg bg-white/10 motion-reduce:animate-none"></div>
                <?php endforeach ?>
            </div>
        </div>
        <noscript>
            <p class="relative z-10 p-8 text-2xl font-semibold">Papan hasil memerlukan JavaScript. Hasil lengkap ada di <a class="underline" href="<?= site_url('hasil') ?>"><?= esc(site_url('hasil')) ?></a>.</p>
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
