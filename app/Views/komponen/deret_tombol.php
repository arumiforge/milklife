<?php
/**
 * Deret tombol tautan sejenis (K175 butir 8), misalnya "Lihat semua hasil" dan
 * "Lihat klasemen sekolah". Di layar lebar semua tombol berteks. Di ponsel
 * deret selalu satu baris: satu tombol berteks lengkap (yang pertama), yang
 * lain ikon saja. Tekan pertama pada tombol ikon melebarkannya dan
 * menampilkan teksnya (tombol lain menjadi ikon), tekan kedua membuka
 * halamannya. Perilaku ini dari `public/aset/js/deret-tombol.js`; halaman yang
 * memakai komponen ini memuat skrip itu.
 *
 * Papan ketik dan pembaca layar: teks tombol selalu ada di pohon aksesibilitas
 * (disembunyikan secara visual dengan `sr-only`, bukan `hidden`), dan Enter
 * langsung membuka halaman. Tombol yang difokus papan ketik ikut melebar.
 * Tanpa JavaScript, tombol ikon langsung membuka halaman.
 *
 *     komponen('deret_tombol', ['tombol' => [
 *         ['label' => 'Lihat semua hasil', 'href' => site_url('hasil'), 'ikon' => 'trophy'],
 *         ['label' => 'Lihat klasemen sekolah', 'href' => site_url('hasil/klasemen'), 'ikon' => 'award'],
 *     ]]);
 *
 * @var list<array{label: string, href: string, ikon: string}> $tombol
 * @var string|null                                            $kelas  kelas tambahan pembungkus
 */
?>
<div class="flex flex-nowrap gap-2 sm:flex-wrap <?= esc($kelas ?? '') ?>" data-deret-tombol>
    <?php foreach ($tombol as $i => $t) : ?>
        <a href="<?= esc($t['href'], 'attr') ?>" class="group inline-flex min-h-11 min-w-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-center text-sm font-semibold text-gray-900 transition-colors hover:bg-gray-50 hover:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100 max-sm:data-[lebar]:min-w-0 max-sm:data-[lebar]:flex-1 sm:px-5"<?= $i === 0 ? ' data-lebar' : '' ?> data-deret-butir>
            <?= ikon($t['ikon'], 'size-5 shrink-0') ?>
            <span class="max-sm:sr-only max-sm:group-data-[lebar]:not-sr-only max-sm:group-data-[lebar]:truncate"><?= esc($t['label']) ?></span>
        </a>
    <?php endforeach ?>
</div>
