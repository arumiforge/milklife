<?php
/**
 * Bagian "Meet the Athletes" halaman depan (`#atlet`, F10-10): tokoh anime G10-01
 * di `public/aset/gambar/tokoh/`, judul editorial Inggris dengan sorot kuas G10-11
 * dan keterangan Indonesia (K153).
 *
 * - Layar md ke atas: lembar tokoh `tokoh/lembar-tokoh.webp` (lima tokoh berjajar).
 * - Ponsel: dua tokoh tunggal `tokoh/tokoh-*.webp` berdampingan (urutan pilihan A, C,
 *   lalu B, D, E bila ada yang belum ada). Gambar yang `hidden` di satu ukuran
 *   tidak diunduh peramban karena semuanya `loading="lazy"` (di bawah lipatan).
 * - Hanya lembar yang ada: lembar tampil di semua ukuran; hanya tokoh tunggal yang
 *   ada: tokoh tunggal tampil di semua ukuran. Tanpa satu pun berkas, bagian ini
 *   tidak ditulis (K41).
 *
 * Penanda uji: `data-tokoh-lembar`, `data-tokoh-tunggal`.
 */

// Tokoh G10-01 (brief `desain/tugas/G10-01-lembar-tokoh.md`) dan teks alternatifnya.
$tokohTunggal = [
    'a' => 'Siswi SD berkaus biru sedang berlari',
    'c' => 'Siswa SMA berkaus oranye menolak peluru',
    'b' => 'Siswi SMP berjilbab olahraga melayang di lompat jauh',
    'd' => 'Siswa SD berkaus biru muda bersiap start jongkok',
    'e' => 'Panitia bertopi memegang stopwatch dan papan klip',
];

$tunggal = [];
foreach ($tokohTunggal as $kode => $alt) {
    if (count($tunggal) === 2) {
        break;
    }
    if (ada_gambar('tokoh/tokoh-' . $kode . '.webp')) {
        $tunggal[$kode] = $alt;
    }
}

$adaLembar = ada_gambar('tokoh/lembar-tokoh.webp');

if (! $adaLembar && $tunggal === []) {
    return;
}

$kelasLembar  = $tunggal === [] ? 'block' : 'hidden md:block';
$kelasTunggal = $adaLembar ? 'md:hidden' : '';
// Lembar 2:1 dibatasi lebarnya supaya tidak setinggi layar; dua tokoh tunggal di layar lebar juga.
$kelasBingkai = $adaLembar ? 'md:mx-auto md:max-w-4xl' : 'mx-auto max-w-2xl';
?>
<section id="atlet" class="tekstur-kertas scroll-mt-20" aria-labelledby="judul-atlet" data-bagian-tokoh>
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <p class="judul-tampilan" lang="en" aria-hidden="true" data-judul-inggris>Meet the <span class="sorot-kuas">Athletes</span></p>
        <h2 id="judul-atlet" class="judul-keterangan mt-2">Kenalan dengan para atlet</h2>
        <p class="mt-3 max-w-3xl text-lg text-gray-700">Dari SD sampai SMA, setiap anak punya nomor lomba yang pas: lari, lompat, dan lempar. Panitia siap mendampingi di lintasan.</p>

        <div class="bingkai-foto mt-8 <?= $kelasBingkai ?>">
            <div class="bg-linear-to-b from-langit-100 to-white px-3 pt-4 sm:px-6 sm:pt-6">
                <?php if ($adaLembar) : ?>
                    <?= gambar_opsional('tokoh/lembar-tokoh.webp', [
                        'class'             => $kelasLembar . ' h-auto w-full select-none',
                        'alt'               => 'Lima tokoh MilkLife Athletics Challenge berdiri berjajar: siswi SD berkuncir kuda, siswi SMP berjilbab olahraga, siswa SMA berkaus oranye, siswa SD berambut ikal, dan panitia bertopi dengan papan klip',
                        'data-tokoh-lembar' => true,
                    ]) ?>
                <?php endif ?>
                <?php if ($tunggal !== []) : ?>
                    <div class="<?= $kelasTunggal ?> grid grid-cols-2 items-end gap-2 sm:gap-4" data-tokoh-tunggal>
                        <?php foreach ($tunggal as $kode => $alt) : ?>
                            <?= gambar_opsional('tokoh/tokoh-' . $kode . '.webp', ['class' => 'h-auto w-full select-none', 'alt' => $alt]) ?>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        </div>
    </div>
</section>
