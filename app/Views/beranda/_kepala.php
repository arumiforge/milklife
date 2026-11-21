<?php
/**
 * Bagian pembuka halaman depan (`#pembuka`, K81, F9-03): latar gradien merek
 * dengan adegan Three.js halus (dimatikan bila pengunjung memilih kurangi
 * gerak), label seri, judul besar, teks pengantar, kapsul info (tanggal,
 * tempat, keadaan pendaftaran, K82), dua tombol besar, carousel foto acara
 * sebelumnya, lalu deret kartu kelompok umur.
 *
 * Judul, keadaan pendaftaran, dan dua tombol selalu di layar pertama (K81):
 * di ponsel carousel turun di bawah tombol, di layar lebar di kanan.
 *
 * - Carousel (Flowbite, `data-carousel="static"`): putar otomatis, jeda,
 *   dan geser diatur `public/aset/js/carousel-beranda.js`. Foto pertama
 *   dimuat lebih dulu, sisanya `loading="lazy"`. Satu foto tampil diam.
 *   Selama foto dimuat, latarnya warna rata-rata foto itu (`warna`, F9-17),
 *   digambar sebagai `<svg>` berisi satu kotak karena CSP menolak `style`.
 *   Bila carousel kosong, gambar kepala dari pengaturan yang tampil; bila
 *   itu pun belum ada, tidak ada gambar sama sekali (K41).
 * - Judul dari pengaturan, huruf besar; tiap kalimat (atau tiap kata bila
 *   hanya satu kalimat pendek) satu baris dan baris terakhir oranye (K83,
 *   K86). Bila kosong, nama acara menjadi judul. Satu-satunya `<h1>`
 *   halaman ada di sini (`#judul-acara`).
 * - Teks admin di-escape; baris baru di dalam paragraf menjadi `<br>`.
 * - Tombol tanpa alamat tampil nonaktif dengan keterangannya (K39).
 *
 * Penanda untuk uji dan skrip memakai atribut `data-*`, bukan kelas.
 *
 * @var array{nama: string, seri_tahun: string, tempat: string, tanggal: string}                              $acara
 * @var array<string, mixed>                                                                                   $tampilan     lihat IsiHalamanDepan::tampilan()
 * @var App\Libraries\BukaTutupPendaftaran                                                                     $bukaTutup
 * @var list<array{kunci: string, label: string, alamat: string|null, utama: bool, keterangan?: string|null}> $tombol
 * @var list<array{id: int, nama: string, nomor_individu: list<mixed>, nomor_beregu: list<mixed>}>             $kelompokUmur
 * @var list<array{alamat: string, keterangan: string, ukuran: array{lebar: int, tinggi: int}|null, warna?: string|null}> $carousel
 */

use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\IlustrasiBeranda;

$keadaan = $bukaTutup->keadaan();

// Ikon dan warna ujung keadaan di kapsul info (K82, K92: terbuka biru).
$gayaKeadaan = [
    BukaTutupPendaftaran::DISIAPKAN    => ['hourglass', 'bg-oranye-50 text-oranye-800'],
    BukaTutupPendaftaran::BELUM_DIBUKA => ['calendar-clock', 'bg-langit-100 text-primary-900'],
    BukaTutupPendaftaran::TERBUKA      => ['megaphone', 'bg-primary-700 text-white'],
    BukaTutupPendaftaran::DITUTUP      => ['door-closed', 'bg-gray-100 text-gray-800'],
][$keadaan] ?? ['info', 'bg-gray-100 text-gray-800'];

// Kapsul info seperti konsep (K82): "9–12" besar di atas "Desember 2026",
// tempat dipisah di koma terakhir. Bentuk lain tampil satu baris.
$tanggalInfo = preg_match('/^([\d\s–-]+?)\s+(\p{L}+\s+\d{4})$/u', $acara['tanggal'], $c) === 1 ? [$c[1], $c[2]] : ['', $acara['tanggal']];
$koma        = mb_strrpos($acara['tempat'], ',');
$tempatInfo  = $koma === false ? [$acara['tempat'], ''] : [trim(mb_substr($acara['tempat'], 0, $koma)), trim(mb_substr($acara['tempat'], $koma + 1))];
[$ringkasAtas, $ringkasBawah] = $bukaTutup->ringkas();

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
$tokoh = $foto === [] ? IlustrasiBeranda::alamat('gambar/beranda/tokoh-lari.webp') : null;
$adaKanan = $foto !== [] || $tokoh !== null;

$ukuranAtribut = static fn (?array $ukuran): string => $ukuran === null ? '' : ' width="' . (int) $ukuran['lebar'] . '" height="' . (int) $ukuran['tinggi'] . '"';

/** Nama kelompok umur dipisah: "KU 8 (Seneng Playon)" → ["KU 8", "Seneng Playon"]. */
$pisahNama = static fn (string $nama): array => preg_match('/^(.*?)\s*\((.+)\)\s*$/u', $nama, $c) === 1 ? [$c[1], $c[2]] : [$nama, ''];
?>
<section id="pembuka" class="latar-merek relative overflow-hidden border-b border-gray-200/70" aria-labelledby="judul-acara"<?= $foto !== [] ? ' data-pembuka-foto="' . count($foto) . '"' : '' ?>>
    <?php // Adegan Three.js di lapisan sendiri yang disamarkan supaya tidak mengalahkan tulisan dan foto. ?>
    <div class="pointer-events-none absolute inset-0 opacity-40" aria-hidden="true" data-adegan-merek>
        <div data-adegan-cadangan class="absolute inset-0 transition-opacity duration-700"></div>
    </div>

    <div class="relative mx-auto grid max-w-screen-xl items-center gap-8 px-4 pb-10 pt-6 sm:pt-10 lg:grid-cols-12 lg:gap-12 lg:pb-14 lg:pt-14">
        <div class="<?= $adaKanan ? 'lg:col-span-6' : 'lg:col-span-10 xl:col-span-8' ?> flex flex-col gap-5 sm:gap-6">
            <?php if ($acara['seri_tahun'] !== '') : ?>
                <p class="inline-flex w-fit items-center gap-2 rounded-full border border-oranye-200 bg-white/80 px-3 py-1 text-sm font-bold text-oranye-700 shadow-lembut" data-pembuka-seri>
                    <span class="size-2 rounded-full bg-oranye-500" aria-hidden="true"></span><?= esc($acara['seri_tahun']) ?>
                </p>
            <?php endif ?>

            <h1 id="judul-acara" class="text-[2.5rem] font-extrabold uppercase italic leading-[0.95] tracking-tight text-primary-900 sm:text-6xl lg:text-7xl">
                <?php foreach ($kalimatJudul as $i => $kalimat) : ?>
                    <?php $sorot = $i === count($kalimatJudul) - 1 && $i > 0; ?>
                    <span class="block<?= $sorot ? ' text-oranye-600' : '' ?>"<?= $sorot ? ' data-judul-sorot' : '' ?>><?= esc($kalimat) ?></span>
                <?php endforeach ?>
            </h1>

            <?php if ($tampilan['teks'] !== []) : ?>
                <div class="space-y-2 text-base text-gray-700 sm:text-lg" data-pembuka-teks>
                    <?php foreach ($tampilan['teks'] as $paragraf) : ?>
                        <p><?= nl2br(esc($paragraf), false) ?></p>
                    <?php endforeach ?>
                </div>
            <?php endif ?>

            <div class="flex w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-lembut sm:w-fit sm:flex-row" data-pembuka-info>
                <p class="<?= $acara['tanggal'] !== '' && $acara['tempat'] !== '' ? 'grid-cols-2' : 'grid-cols-1' ?> grid items-stretch divide-x divide-gray-200" data-pembuka-waktu>
                    <?php if ($acara['tanggal'] === '' && $acara['tempat'] === '') : ?>
                        <span class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-gray-700"><?= ikon('calendar-days', 'text-primary-700') ?>Tanggal dan tempat acara segera diumumkan</span>
                    <?php else : ?>
                        <?php if ($acara['tanggal'] !== '') : ?>
                            <span class="flex min-w-0 items-center gap-2 px-4 py-3">
                                <?= ikon('calendar-days', 'size-6 text-primary-700') ?>
                                <span class="flex flex-col leading-tight">
                                    <?php if ($tanggalInfo[0] !== '') : ?><strong class="text-2xl font-extrabold text-primary-900" data-info-besar><?= esc($tanggalInfo[0]) ?></strong> <?php endif ?>
                                    <span class="text-sm font-semibold text-gray-700"><?= esc($tanggalInfo[1]) ?></span>
                                </span>
                            </span>
                        <?php endif ?>
                        <?php if ($acara['tempat'] !== '') : ?>
                            <span class="flex min-w-0 items-center gap-2 px-4 py-3">
                                <?= ikon('map-pin', 'size-6 text-oranye-600') ?>
                                <span class="flex flex-col leading-tight">
                                    <span class="font-bold text-primary-900"><?= esc($tempatInfo[0]) ?></span><?php if ($tempatInfo[1] !== '') : ?><span class="sr-only">,</span> <span class="text-sm font-semibold text-gray-700"><?= esc($tempatInfo[1]) ?></span><?php endif ?>
                                </span>
                            </span>
                        <?php endif ?>
                    <?php endif ?>
                </p>

                <div class="<?= $gayaKeadaan[1] ?> flex grow items-center gap-2 px-4 py-3" data-keadaan="<?= esc($keadaan, 'attr') ?>">
                    <?= ikon($gayaKeadaan[0], 'size-6') ?>
                    <?php // Tampilan ringkas; kalimat lengkap tetap dibaca pembaca layar. ?>
                    <span class="flex flex-col text-sm leading-tight" aria-hidden="true" data-keadaan-ringkas><span><?= esc($ringkasAtas) ?></span> <strong class="text-base font-extrabold"><?= esc($ringkasBawah) ?></strong></span>
                    <div class="sr-only">
                        <p data-keadaan-label><?= esc($bukaTutup->label()) ?></p>
                        <p data-keadaan-kalimat><?= esc($bukaTutup->kalimat()) ?></p>
                    </div>
                </div>
            </div>

            <nav class="flex flex-col gap-3 sm:flex-row" aria-label="Pendaftaran" data-tombol-utama>
                <?php foreach ($tombol as $butir) : ?>
                    <?php
                    $kelas = 'inline-flex min-h-14 items-center justify-center gap-2 rounded-xl px-6 py-3 text-lg font-bold transition-colors focus:outline-none focus:ring-4 '
                        . ($butir['utama'] ? 'bg-oranye-600 text-white shadow-angkat hover:bg-oranye-700 focus:ring-oranye-300' : 'border-2 border-primary-700 bg-white text-primary-800 hover:bg-primary-50 focus:ring-primary-200');
                    ?>
                    <?php if ($butir['alamat'] === null) : ?>
                        <a class="<?= $kelas ?> cursor-not-allowed opacity-60" role="link" aria-disabled="true" data-tombol="<?= esc($butir['kunci'], 'attr') ?>"<?= $butir['utama'] ? ' data-utama' : '' ?>>
                            <?= esc($butir['label']) ?>
                            <span class="rounded-md bg-black/10 px-2 py-0.5 text-xs font-semibold uppercase"><?= esc($butir['keterangan'] ?? 'Segera') ?></span>
                        </a>
                    <?php else : ?>
                        <a class="<?= $kelas ?>" href="<?= site_url($butir['alamat']) ?>" data-tombol="<?= esc($butir['kunci'], 'attr') ?>"<?= $butir['utama'] ? ' data-utama' : '' ?>><?= esc($butir['label']) ?><?= ikon($butir['kunci'] === 'cek' ? 'search' : 'arrow-right') ?></a>
                    <?php endif ?>
                <?php endforeach ?>
            </nav>
        </div>

        <?php if ($foto !== []) : ?>
            <div class="lg:col-span-6">
                <div id="carousel-beranda" class="relative"<?= $putar ? ' data-carousel="static" data-carousel-beranda role="region" aria-roledescription="carousel" aria-label="' . esc('Foto ' . $acara['nama'] . ' seri sebelumnya', 'attr') . '"' : '' ?>>
                    <div class="relative aspect-[4/3] overflow-hidden rounded-3xl bg-primary-100 shadow-angkat ring-1 ring-black/5 sm:aspect-[16/10]">
                        <?php foreach ($foto as $i => $f) : ?>
                            <div class="<?= $putar ? ($i === 0 ? '' : 'hidden ') . 'duration-700 ease-in-out' : 'absolute inset-0' ?>"<?= $putar ? ' data-carousel-item' . ($i === 0 ? '="active"' : '') . ' role="group" aria-roledescription="slide" aria-label="' . ($i + 1) . ' dari ' . count($foto) . '"' : '' ?> data-foto-pembuka>
                                <?php if (preg_match('/^#[0-9a-f]{6}$/D', (string) ($f['warna'] ?? '')) === 1) : ?>
                                    <svg class="absolute inset-0 size-full" viewBox="0 0 1 1" preserveAspectRatio="none" aria-hidden="true" focusable="false" data-latar-foto><rect width="1" height="1" fill="<?= $f['warna'] ?>"/></svg>
                                <?php endif ?>
                                <img src="<?= esc($f['alamat'], 'attr') ?>" alt="<?= esc($f['keterangan'] !== '' ? $f['keterangan'] : 'Foto acara ' . ($i + 1), 'attr') ?>" class="absolute inset-0 size-full object-cover"<?= $ukuranAtribut($f['ukuran']) ?> decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
                            </div>
                        <?php endforeach ?>
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 z-30 h-24 bg-linear-to-t from-primary-950/60 to-transparent" aria-hidden="true"></div>
                        <?php if ($putar) : ?>
                        <div class="absolute inset-x-4 bottom-4 z-40 flex flex-wrap items-center justify-center gap-2.5" data-indikator-carousel>
                            <?php foreach ($foto as $i => $f) : ?>
                                <button type="button" class="<?= $i === 0 ? 'bg-white' : 'bg-white/50' ?> relative size-2.5 rounded-full after:absolute after:-inset-2 after:content-['']" aria-current="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Foto <?= $i + 1 ?>" data-carousel-slide-to="<?= $i ?>"></button>
                            <?php endforeach ?>
                        </div>
                        <?php endif ?>
                    </div>

                    <?php if ($putar) : ?>
                        <div class="mt-3 flex justify-end gap-2">
                            <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white text-primary-900 shadow-lembut hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-200" data-carousel-prev>
                                <?= ikon('chevron-left', '', 'Foto sebelumnya') ?>
                            </button>
                            <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white text-primary-900 shadow-lembut hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-200" data-jeda-carousel aria-pressed="false" aria-label="Jeda foto bergeser">
                                <span data-ikon-jeda><?= ikon('pause') ?></span>
                                <span class="hidden" data-ikon-putar><?= ikon('play') ?></span>
                            </button>
                            <button type="button" class="inline-flex size-11 items-center justify-center rounded-full border border-gray-200 bg-white text-primary-900 shadow-lembut hover:bg-primary-50 focus:outline-none focus:ring-4 focus:ring-primary-200" data-carousel-next>
                                <?= ikon('chevron-right', '', 'Foto berikutnya') ?>
                            </button>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        <?php elseif ($tokoh !== null) : ?>
            <div class="hidden lg:col-span-6 lg:block">
                <img src="<?= esc($tokoh, 'attr') ?>" alt="" class="mx-auto h-auto w-full max-w-lg" width="1200" height="1200" fetchpriority="high">
            </div>
        <?php endif ?>
    </div>

    <?php if ($kelompokUmur !== []) : ?>
        <div class="relative mx-auto max-w-screen-xl px-4 pb-10" data-pembuka-ku>
            <p class="mb-4 text-sm font-bold uppercase tracking-wider text-primary-700" data-pembuka-ku-label>Kelompok umur</p>
            <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 <?= [3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][min(5, max(3, count($kelompokUmur)))] ?>">
                <?php foreach ($kelompokUmur as $i => $ku) : ?>
                    <?php [$namaKu, $julukan] = $pisahNama($ku['nama']); ?>
                    <li>
                        <a class="group flex h-full min-h-24 flex-col gap-1 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-angkat focus:outline-none focus:ring-4 focus:ring-primary-200" href="#lomba-ku-<?= (int) $ku['id'] ?>" data-ku-kartu>
                            <?php // Nama, julukan, lalu jumlah nomor individu dan beregu (K88); jumlah 0 tidak ditulis. ?>
                            <span class="flex items-center justify-between gap-2">
                                <span class="text-xl font-extrabold text-primary-900"><?= esc($namaKu) ?></span>
                                <?= ikon('arrow-right', 'size-4 text-oranye-600 transition group-hover:translate-x-0.5') ?>
                            </span>
                            <?php if ($julukan !== '') : ?>
                                <span class="text-sm font-semibold text-oranye-700"><?= esc($julukan) ?></span>
                            <?php endif ?>
                            <?php foreach (['individu' => count($ku['nomor_individu'] ?? []), 'beregu' => count($ku['nomor_beregu'] ?? [])] as $jenis => $jumlah) : ?>
                                <?php if ($jumlah > 0) : ?>
                                    <span class="text-sm text-gray-600"><?= $jumlah ?> nomor <?= $jenis ?></span>
                                <?php endif ?>
                            <?php endforeach ?>
                        </a>
                    </li>
                <?php endforeach ?>
            </ul>
        </div>
    <?php endif ?>
</section>
