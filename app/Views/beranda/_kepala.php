<?php
/**
 * Bagian pembuka halaman depan (`#pembuka`, K81, F10-06): hero editorial
 * seperti `desain/referensi/hero-editorial.webp`, pita tiga fakta, lalu deret
 * kartu kelompok umur.
 *
 * F11-12 (K175): latar art hero kiri G11-02 (`beranda/hero-kiri.webp`, versi
 * tegak `hero-kiri-ponsel.webp`) bila berkasnya ada; gradasi foto lebih
 * panjang dan hiasan yang menyentuh foto memudar (`.tepi-pudar`), jadi tidak
 * ada garis potong lurus. Titik penanda slide hanya di layar lebar. Pita fakta
 * tanpa ikon; batas pendaftaran rata tengah di ponsel. Kartu KU: angka besar
 * berisian gradien sporty (`.isian-ku-<1–5>`) atau tekstur G11-01
 * (`ku/ku-<n>-isian.webp`, kelas `.isian-ku-gambar-<n>`), art G11-01
 * (`ku/ku-<n>-art.webp`) atau garis cadangan `.kartu-ku-garis`, dan jumlah
 * nomor berupa ikon dan angka dengan nama aksesibel lengkap.
 *
 * - Hero berlatar biru tua tanpa alis (K164): judul besar
 *   Barlow Condensed huruf besar, keterangan Indonesia, dan dua tombol yang
 *   mengikuti buka/tutup pendaftaran (K81). Tombol tanpa alamat tampil
 *   nonaktif dengan keterangannya (K39).
 * - Foto asli carousel halaman depan (`CarouselHalamanDepan`) memenuhi sisi
 *   kanan dengan gradasi biru tua ke kiri; di ponsel foto di atas (4:3)
 *   dengan gradasi ke bawah. Keterangan foto kecil di pojok kanan bawah.
 *   Bila carousel kosong, gambar kepala dari pengaturan yang tampil; bila itu
 *   pun belum ada, ilustrasi tokoh G9-02 di layar lebar bila berkasnya ada,
 *   atau tidak ada gambar sama sekali (K41).
 * - Slideshow (lebih dari satu foto) diatur `public/aset/js/carousel-beranda.js`:
 *   ganti tiap 5 detik, hanya titik penanda (mulai lg). Tanpa JS hanya foto pertama yang
 *   tampil. Foto pertama `fetchpriority="high"`; foto lain tersembunyi
 *   (`hidden`) dengan `loading="lazy"`, jadi baru diunduh saat skrip
 *   menyiapkannya menjelang tampil. Semua foto ber-`srcset` 640/960/asli
 *   (`VarianFoto`). Selama foto dimuat, latarnya warna rata-rata foto itu
 *   (`warna`, F9-17), digambar sebagai `<svg>` karena CSP menolak `style`.
 * - Judul dari pengaturan, huruf besar; tiap kalimat (atau tiap kata bila
 *   hanya satu kalimat pendek) satu baris dan baris terakhir oranye (K83,
 *   K86). Bila kosong, nama acara menjadi judul. Tahun tidak ditulis di judul
 *   (permintaan 20). Satu-satunya `<h1>` halaman ada di sini (`#judul-acara`).
 * - Pita fakta berlatar krem tanpa ikon: Hari lomba, Lokasi (dipisah di koma terakhir,
 *   menaut ke bagian peta `#lokasi` F10-02 bila tampil),
 *   dan keadaan pendaftaran ("Batas pendaftaran 30 November 2026", K82
 *   diperbarui). Kalimat lengkap keadaan tetap untuk pembaca layar.
 * - Hiasan sapuan kecepatan G10-02 (`beranda/hero-sapuan.webp`) mulai layar md
 *   bila berkasnya ada, dan garis kecepatan G10-11 samar di tepi bawah hero mulai lg
 *   (`data-garis-kecepatan`, F10-10); adegan Three.js, pita G9-02, dan pita sudut G9-12
 *   hanya untuk pembuka tanpa foto.
 * - Teks admin di-escape; baris baru di dalam paragraf menjadi `<br>`.
 *
 * Penanda untuk uji dan skrip memakai atribut `data-*`, bukan kelas.
 *
 * @var array{nama: string, seri_tahun: string, tempat: string, tanggal: string}                              $acara
 * @var array<string, mixed>                                                                                   $tampilan     lihat IsiHalamanDepan::tampilan()
 * @var App\Libraries\BukaTutupPendaftaran                                                                     $bukaTutup
 * @var list<array{kunci: string, label: string, alamat: string|null, utama: bool, keterangan?: string|null}> $tombol
 * @var list<array{id: int, nama: string, nomor_individu: list<mixed>, nomor_beregu: list<mixed>}>             $kelompokUmur
 * @var list<array{alamat: string, keterangan: string, ukuran: array{lebar: int, tinggi: int}|null, warna?: string|null, srcset?: string|null}> $carousel
 */

use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\IlustrasiBeranda;
use App\Libraries\PetaLokasi;

$keadaan = $bukaTutup->keadaan();
$batas   = $bukaTutup->batas();

// Fakta ketiga di pita: label, nilai, dan warna menurut keadaan (K82, permintaan 20: tanggal bertahun).
// Tanpa ikon sejak F11-12 (K175).
[$labelKeadaan, $nilaiKeadaan] = match ($keadaan) {
    BukaTutupPendaftaran::TERBUKA      => ['Batas pendaftaran', tanggal_id($batas)],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['Pendaftaran dibuka', tanggal_id($batas)],
    BukaTutupPendaftaran::DITUTUP      => ['Pendaftaran', 'Ditutup'],
    default                            => ['Pendaftaran', 'Segera dibuka'],
};
$sorotKeadaan = $keadaan === BukaTutupPendaftaran::TERBUKA;

// Hari lomba: "9–12 Desember" besar di atas "2026"; tempat dipisah di koma terakhir.
$tanggalInfo = preg_match('/^(.+?)\s+(\d{4})$/u', $acara['tanggal'], $c) === 1 ? [$c[1], $c[2]] : [$acara['tanggal'], ''];
$koma        = mb_strrpos($acara['tempat'], ',');
$adaPeta     = PetaLokasi::embed() !== '';
$tempatInfo  = $koma === false ? [$acara['tempat'], ''] : [trim(mb_substr($acara['tempat'], 0, $koma)), trim(mb_substr($acara['tempat'], $koma + 1))];

// Judul per kalimat; judul satu kalimat paling banyak 4 kata per kata (K83, K86).
$kalimatJudul = preg_split('/(?<=[.!?])\s+/u', $tampilan['judul'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
$kataJudul    = count($kalimatJudul) === 1 ? (preg_split('/\s+/u', $kalimatJudul[0], -1, PREG_SPLIT_NO_EMPTY) ?: []) : [];
if (count($kataJudul) > 1 && count($kataJudul) <= 4) {
    $kalimatJudul = $kataJudul;
}
if ($kalimatJudul === []) {
    $kalimatJudul = [$acara['nama']];
}

// Foto pembuka: carousel, atau gambar kepala bila carousel kosong.
$foto = $carousel;
if ($foto === [] && $tampilan['gambar_kepala'] !== null) {
    $foto = [['alamat' => $tampilan['gambar_kepala'], 'keterangan' => 'Gambar kepala ' . $acara['nama'], 'ukuran' => $tampilan['ukuran']['gambar_kepala'] ?? null]];
}
$putar = count($foto) > 1;

// Ilustrasi G9-02 bila belum ada foto sama sekali (K41: hanya bila berkasnya ada).
// Tiga tokoh bergantian per hari (lari, lompat, lempar); yang berkasnya belum ada dilewati.
$tokoh = null;
if ($foto === []) {
    $calonTokoh = ['tokoh-lari', 'tokoh-lompat', 'tokoh-lempar'];
    $mulaiTokoh = (int) date('z') % count($calonTokoh);
    for ($i = 0; $i < count($calonTokoh) && $tokoh === null; $i++) {
        $tokoh = IlustrasiBeranda::alamat('gambar/beranda/' . $calonTokoh[($mulaiTokoh + $i) % count($calonTokoh)] . '.webp');
    }
}

// Hiasan art: sapuan hero G10-02 mulai md; latar pita G9-02 (mulai md) dan pita sudut G9-12 (ponsel) hanya tanpa foto.
$sapuan        = gambar_opsional('beranda/hero-sapuan.webp', ['class' => 'tepi-pudar pointer-events-none absolute bottom-0 left-0 z-10 hidden w-2/3 max-w-3xl select-none opacity-50 md:block', 'data-hiasan' => 'hero-sapuan']);
$latarPita     = $foto === [] ? gambar_opsional('beranda/latar-pita.webp', ['class' => 'pointer-events-none absolute inset-0 hidden size-full select-none object-cover object-right opacity-30 md:block', 'data-hiasan' => 'latar-pita']) : '';
$pitaSudut     = $foto === [] ? gambar_opsional('pola/pita-sudut.webp', ['class' => 'pointer-events-none absolute -right-8 -top-8 w-36 select-none opacity-60 sm:w-72' . ($latarPita === '' ? '' : ' md:hidden'), 'loading' => false, 'data-hiasan' => 'pita-sudut']) : '';
// Latar art hero kiri G11-02 (K175): tepi kanannya memudar ke foto; versi tegak di ponsel di bawah foto.
$heroKiri       = gambar_opsional('beranda/hero-kiri.webp', ['class' => 'pointer-events-none absolute inset-y-0 left-0 z-10 hidden h-full w-[62%] select-none object-cover object-left lg:block', 'loading' => false, 'data-hiasan' => 'hero-kiri']);
$heroKiriPonsel = gambar_opsional('beranda/hero-kiri-ponsel.webp', ['class' => 'pointer-events-none absolute inset-x-0 bottom-0 -z-10 h-[75%] w-full select-none object-cover object-top lg:hidden', 'data-hiasan' => 'hero-kiri-ponsel']);
$latarLintasan = gambar_opsional('beranda/latar-lintasan.webp', ['class' => 'pointer-events-none absolute inset-0 -z-10 hidden size-full select-none object-cover md:block', 'data-hiasan' => 'latar-lintasan']);

$ukuranAtribut = static fn (?array $ukuran): string => $ukuran === null ? '' : ' width="' . (int) $ukuran['lebar'] . '" height="' . (int) $ukuran['tinggi'] . '"';

/** Nama kelompok umur dipisah bila admin menambah julukan dalam kurung: "KU 8 (Julukan)" → ["KU 8", "Julukan"]; "KU 8" → ["KU 8", ""]. */
$pisahNama = static fn (string $nama): array => preg_match('/^(.*?)\s*\((.+)\)\s*$/u', $nama, $c) === 1 ? [$c[1], $c[2]] : [$nama, ''];

$labelFakta = 'flex items-center gap-1.5 text-xs font-bold uppercase tracking-[0.14em] text-gray-600';
$nilaiFakta = 'mt-1 font-tampilan text-[1.75rem] font-extrabold leading-none sm:text-4xl';
?>
<section id="pembuka" class="relative" aria-labelledby="judul-acara"<?= $foto !== [] ? ' data-pembuka-foto="' . count($foto) . '"' : '' ?>>
    <div class="relative isolate overflow-hidden bg-primary-900 text-white" data-pembuka-hero>
        <?php if ($foto === []) : ?>
            <?php // Adegan Three.js hanya untuk pembuka tanpa foto, di lapisan sendiri yang disamarkan. ?>
            <div class="pointer-events-none absolute inset-0 -z-10 opacity-40" aria-hidden="true" data-adegan-merek>
                <div data-adegan-cadangan class="absolute inset-0 transition-opacity duration-700"></div>
            </div>
            <?= $latarPita ?>
            <?= $pitaSudut ?>
        <?php endif ?>

        <?php if ($foto !== []) : ?>
            <div id="carousel-beranda" class="relative lg:absolute lg:inset-y-0 lg:right-0 lg:w-[66%]"<?= $putar ? ' data-carousel-beranda role="region" aria-roledescription="carousel" aria-label="' . esc('Foto ' . $acara['nama'] . ' seri sebelumnya', 'attr') . '"' : '' ?>>
                <div class="relative aspect-[4/3] overflow-hidden bg-primary-950 sm:aspect-[16/9] lg:aspect-auto lg:h-full">
                    <?php foreach ($foto as $i => $f) : ?>
                        <?php $alt = $f['keterangan'] !== '' ? $f['keterangan'] : 'Foto acara ' . ($i + 1); ?>
                        <figure class="absolute inset-0 m-0<?= $putar ? ' transition-opacity duration-700 ease-in-out motion-reduce:transition-none' . ($i === 0 ? ' opacity-100' : ' hidden opacity-0') : '' ?>"<?= $putar ? ' data-carousel-item' . ($i === 0 ? '="active"' : '') . ' role="group" aria-roledescription="slide" aria-label="Foto ' . ($i + 1) . ' dari ' . count($foto) . '"' : '' ?> data-foto-pembuka>
                            <?php if (preg_match('/^#[0-9a-f]{6}$/D', (string) ($f['warna'] ?? '')) === 1) : ?>
                                <svg class="absolute inset-0 size-full" viewBox="0 0 1 1" preserveAspectRatio="none" aria-hidden="true" focusable="false" data-latar-foto><rect width="1" height="1" fill="<?= $f['warna'] ?>"/></svg>
                            <?php endif ?>
                            <img src="<?= esc($f['alamat'], 'attr') ?>"<?= ($f['srcset'] ?? null) !== null ? ' srcset="' . esc($f['srcset'], 'attr') . '" sizes="(min-width: 1024px) 66vw, 100vw"' : '' ?> alt="<?= esc($alt, 'attr') ?>" class="absolute inset-0 size-full object-cover"<?= $ukuranAtribut($f['ukuran']) ?> decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
                            <?php if ($f['keterangan'] !== '') : ?>
                                <?php // Keterangan kecil di pojok kanan bawah; isinya sama dengan alt, jadi disembunyikan dari pembaca layar. ?>
                                <figcaption class="absolute bottom-3 right-4 z-20 max-w-[70%] text-right text-xs leading-snug text-white/90 lg:bottom-6 lg:right-8 lg:max-w-sm" aria-hidden="true" data-keterangan-foto><?= esc($f['keterangan']) ?></figcaption>
                            <?php endif ?>
                        </figure>
                    <?php endforeach ?>
                    <?php // Gradasi biru tua: ke bawah di ponsel, ke kiri di layar lebar. ?>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 h-1/2 bg-linear-to-b from-transparent to-primary-900 lg:hidden" aria-hidden="true"></div>
                    <?php // Gradasi kiri panjang dan bertahap supaya batas dengan sisi kiri tidak terlihat sebagai garis (K175). ?>
                    <div class="pointer-events-none absolute inset-y-0 left-0 z-10 hidden w-[85%] bg-linear-to-r from-primary-900 via-primary-900/70 via-30% to-transparent lg:block" aria-hidden="true" data-gradasi-foto></div>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 z-10 hidden h-1/3 bg-linear-to-t from-primary-900/60 to-transparent lg:block" aria-hidden="true"></div>
                </div>
                <?php if ($putar) : ?>
                    <?php // Titik penanda slide hanya di layar lebar (K175); di ponsel foto tetap berganti otomatis. ?>
                    <div class="absolute bottom-12 right-6 z-20 hidden justify-end lg:flex" data-indikator-carousel>
                        <?php foreach ($foto as $i => $f) : ?>
                            <button type="button" class="group inline-flex h-11 w-7 items-center justify-center focus:outline-none" aria-current="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Foto <?= $i + 1 ?> dari <?= count($foto) ?>" data-carousel-slide-to="<?= $i ?>">
                                <span class="block h-2 w-2 rounded-full bg-white/50 transition-all group-hover:bg-white/80 group-focus-visible:ring-2 group-focus-visible:ring-white group-focus-visible:ring-offset-2 group-focus-visible:ring-offset-primary-900 group-aria-[current=true]:w-5 group-aria-[current=true]:bg-oranye-400 motion-reduce:transition-none" aria-hidden="true"></span>
                            </button>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>
            </div>
        <?php elseif ($tokoh !== null) : ?>
            <img src="<?= esc($tokoh, 'attr') ?>" alt="" class="pointer-events-none absolute bottom-0 right-0 hidden h-auto w-[42%] max-w-xl lg:block" width="1200" height="1200" fetchpriority="high">
        <?php endif ?>

        <?= $heroKiri ?>
        <?= $heroKiriPonsel ?>
        <?= $sapuan ?>

        <?php if (ada_gambar('elemen/garis-kecepatan.webp')) : ?>
            <?php // Garis kecepatan G10-11 samar di tepi bawah hero, di bawah tombol (mulai lg; di ponsel akan memotong tombol garis tepi), latar CSS lewat penanda `ada-garis-kecepatan` (F10-10). ?>
            <div class="garis-kecepatan tepi-pudar pointer-events-none absolute bottom-0 left-0 z-10 hidden bg-[length:100%_100%] bg-no-repeat h-24 w-[55%] opacity-40 lg:block" aria-hidden="true" data-garis-kecepatan></div>
        <?php endif ?>

        <div class="relative z-10 mx-auto max-w-screen-xl px-4 pb-10 lg:flex lg:min-h-[34rem] lg:items-center lg:py-16 xl:min-h-[38rem] <?= $foto !== [] ? 'pt-2 sm:pt-4' : 'pt-10 sm:pt-14' ?>">
            <div class="flex max-w-2xl flex-col gap-5 sm:gap-6 lg:w-1/2 xl:w-[46%]">
                <h1 id="judul-acara" class="font-tampilan text-[clamp(2.75rem,1.6rem+5vw,5.5rem)] font-extrabold uppercase italic leading-[0.9] tracking-tight text-white">
                    <?php foreach ($kalimatJudul as $i => $kalimat) : ?>
                        <?php $sorot = $i === count($kalimatJudul) - 1 && $i > 0; ?>
                        <span class="block<?= $sorot ? ' text-oranye-400' : '' ?>"<?= $sorot ? ' data-judul-sorot' : '' ?>><?= esc($kalimat) ?></span>
                    <?php endforeach ?>
                </h1>

                <?php if ($tampilan['teks'] !== []) : ?>
                    <div class="space-y-2 text-base text-primary-100 sm:text-lg" data-pembuka-teks>
                        <?php foreach ($tampilan['teks'] as $paragraf) : ?>
                            <p><?= nl2br(esc($paragraf), false) ?></p>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <nav class="flex flex-col gap-3 sm:flex-row" aria-label="Pendaftaran" data-tombol-utama>
                    <?php foreach ($tombol as $butir) : ?>
                        <?php
                        $kelas = 'inline-flex min-h-14 items-center justify-center gap-2 rounded-lg px-6 py-3 text-lg font-bold transition-colors focus:outline-none focus:ring-4 '
                            . ($butir['utama'] ? 'tombol-utama-berart tombol-berart-oranye focus:ring-oranye-300' : 'border border-white/70 text-white hover:bg-white/10 focus:ring-white/40');
                        ?>
                        <?php if ($butir['alamat'] === null) : ?>
                            <a class="<?= $kelas ?> cursor-not-allowed opacity-70" role="link" aria-disabled="true" data-tombol="<?= esc($butir['kunci'], 'attr') ?>"<?= $butir['utama'] ? ' data-utama' : '' ?>>
                                <?= esc($butir['label']) ?>
                                <span class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold uppercase"><?= esc($butir['keterangan'] ?? 'Segera') ?></span>
                            </a>
                        <?php else : ?>
                            <a class="<?= $kelas ?>" href="<?= site_url($butir['alamat']) ?>" data-tombol="<?= esc($butir['kunci'], 'attr') ?>"<?= $butir['utama'] ? ' data-utama' : '' ?>><?= esc($butir['label']) ?><?= ikon($butir['kunci'] === 'cek' ? 'search' : 'arrow-right') ?></a>
                        <?php endif ?>
                    <?php endforeach ?>
                </nav>
            </div>
        </div>
    </div>

    <?php // Pita tiga fakta (rujukan hero editorial): hari lomba, lokasi, keadaan pendaftaran. ?>
    <div class="tekstur-kertas border-b border-primary-900/10 text-primary-900" data-pembuka-info>
        <div class="mx-auto grid max-w-screen-xl grid-cols-2 sm:grid-cols-3">
            <div class="contents" data-pembuka-waktu>
                <?php if ($acara['tanggal'] === '' && $acara['tempat'] === '') : ?>
                    <p class="col-span-2 px-4 py-5 font-semibold text-gray-700">Tanggal dan tempat acara segera diumumkan</p>
                <?php else : ?>
                    <?php if ($acara['tanggal'] !== '') : ?>
                        <div class="min-w-0 border-r border-primary-900/10 px-4 py-4 sm:py-5<?= $acara['tempat'] === '' ? ' col-span-2' : '' ?>">
                            <p class="<?= $labelFakta ?>">Hari lomba</p>
                            <p class="<?= $nilaiFakta ?>"><span data-info-besar><?= esc($tanggalInfo[0]) ?></span><?php if ($tanggalInfo[1] !== '') : ?> <span class="mt-1.5 block font-sans text-sm font-semibold text-gray-700"><?= esc($tanggalInfo[1]) ?></span><?php endif ?></p>
                        </div>
                    <?php endif ?>
                    <?php if ($acara['tempat'] !== '') : ?>
                        <div class="min-w-0 px-4 py-4 sm:border-r sm:border-primary-900/10 sm:py-5<?= $acara['tanggal'] === '' ? ' col-span-2' : '' ?>">
                            <p class="<?= $labelFakta ?>">Lokasi</p>
                            <?php // Tautan ke bagian peta `#lokasi` (F10-02) bila bagian itu tampil. ?>
                            <p class="<?= $nilaiFakta ?> break-words"><?php if ($adaPeta) : ?><a class="decoration-oranye-500 decoration-2 underline-offset-4 hover:underline focus:outline-none focus-visible:underline" href="#lokasi" data-fakta-lokasi><?php endif ?><?= esc($tempatInfo[0]) ?><?php if ($adaPeta) : ?></a><?php endif ?><?php if ($tempatInfo[1] !== '') : ?><span class="sr-only">,</span> <span class="mt-1.5 block font-sans text-sm font-semibold text-gray-700"><?= esc($tempatInfo[1]) ?></span><?php endif ?></p>
                        </div>
                    <?php endif ?>
                <?php endif ?>
            </div>

            <div class="<?= $sorotKeadaan ? 'bg-oranye-100/60' : 'bg-primary-900/5' ?> col-span-2 border-t border-primary-900/10 px-4 py-4 text-center sm:col-span-1 sm:border-t-0 sm:py-5 sm:text-left" data-keadaan="<?= esc($keadaan, 'attr') ?>">
                <?php // Tampilan ringkas; kalimat lengkap tetap dibaca pembaca layar. ?>
                <div aria-hidden="true" data-keadaan-ringkas>
                    <p class="<?= $labelFakta ?> justify-center sm:justify-start"><?= esc($labelKeadaan) ?></p>
                    <p class="<?= $nilaiFakta ?><?= $sorotKeadaan ? ' text-oranye-700' : '' ?>"><?= esc($nilaiKeadaan) ?></p>
                </div>
                <div class="sr-only">
                    <p data-keadaan-label><?= esc($bukaTutup->label()) ?></p>
                    <p data-keadaan-kalimat><?= esc($bukaTutup->kalimat()) ?></p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($kelompokUmur !== []) : ?>
        <div class="relative isolate mx-auto max-w-screen-xl px-4 py-10" data-pembuka-ku>
            <?= $latarLintasan ?>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 <?= [3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][min(5, max(3, count($kelompokUmur)))] ?>">
                <?php foreach ($kelompokUmur as $i => $ku) : ?>
                    <?php
                    [$namaKu, $julukan] = $pisahNama($ku['nama']);
                    // Angka KU besar berisian tekstur G11-01 (`ku/ku-<n>-isian.webp`) bila ada, selain itu gradien (K175).
                    $angkaKu  = preg_match('/^(.*?)(\d+)\s*$/u', $namaKu, $c) === 1 ? [trim($c[1]), $c[2]] : null;
                    $isian    = $angkaKu !== null && ada_gambar('ku/ku-' . $angkaKu[1] . '-isian.webp');
                    $artKu    = $angkaKu === null ? '' : gambar_opsional('ku/ku-' . $angkaKu[1] . '-art.webp', ['class' => 'pointer-events-none absolute -bottom-2 -right-6 -z-10 h-[85%] w-auto max-w-none select-none', 'data-hiasan' => 'ku-art']);
                    $individu = count($ku['nomor_individu'] ?? []);
                    $beregu   = count($ku['nomor_beregu'] ?? []);
                    ?>
                    <li>
                        <a class="group relative isolate flex h-full min-h-32 flex-col gap-1 overflow-hidden rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-angkat focus:outline-none focus:ring-4 focus:ring-primary-200<?= $artKu === '' ? ' kartu-ku-garis kartu-ku-garis-' . ($i % 5 + 1) : '' ?>" href="#lomba-ku-<?= (int) $ku['id'] ?>" data-ku-kartu>
                            <?= $artKu ?>
                            <?php // Nama KU paling menonjol; julukan, lalu jumlah nomor individu dan beregu berupa ikon dan angka (K88, K175). ?>
                            <span class="flex items-start justify-between gap-2">
                                <?php if ($angkaKu !== null) : ?>
                                    <span class="flex items-baseline gap-1 font-tampilan font-extrabold uppercase italic leading-none text-primary-900" data-ku-nama>
                                        <?php if ($angkaKu[0] !== '') : ?><span class="text-2xl"><?= esc($angkaKu[0]) ?></span> <?php endif ?>
                                        <span class="isian-ku isian-ku-<?= $i % 5 + 1 ?> pr-1 text-7xl tracking-tight sm:text-8xl<?= $isian ? ' isian-ku-gambar-' . esc($angkaKu[1], 'attr') : '' ?>"<?= $isian ? ' data-isian-gambar' : '' ?>><?= esc($angkaKu[1]) ?></span>
                                    </span>
                                <?php else : ?>
                                    <span class="font-tampilan text-2xl font-extrabold uppercase text-primary-900" data-ku-nama><?= esc($namaKu) ?></span>
                                <?php endif ?>
                                <?= ikon('arrow-right', 'mt-1 size-4 shrink-0 text-oranye-600 transition group-hover:translate-x-0.5') ?>
                            </span>
                            <?php if ($julukan !== '') : ?>
                                <span class="text-sm font-semibold text-oranye-700"><?= esc($julukan) ?></span>
                            <?php endif ?>
                            <?php if ($individu + $beregu > 0) : ?>
                                <span class="mt-auto flex items-center gap-1.5 pt-1 text-sm font-semibold text-gray-600" data-ku-jumlah>
                                    <?php if ($individu > 0) : ?>
                                        <span class="inline-flex items-center gap-1"><?= ikon('user', 'size-4 text-primary-700') ?><span aria-hidden="true"><?= $individu ?></span><span class="sr-only"><?= $individu ?> nomor individu</span></span>
                                    <?php endif ?>
                                    <?php if ($individu > 0 && $beregu > 0) : ?><span class="text-xs text-gray-400" aria-hidden="true">/</span><span class="sr-only">,</span><?php endif ?>
                                    <?php if ($beregu > 0) : ?>
                                        <span class="inline-flex items-center gap-1"><?= ikon('users', 'size-4 text-oranye-600') ?><span aria-hidden="true"><?= $beregu ?></span><span class="sr-only"><?= $beregu ?> nomor beregu</span></span>
                                    <?php endif ?>
                                </span>
                            <?php endif ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</section>
