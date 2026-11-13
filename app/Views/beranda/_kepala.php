<?php
/**
 * Bagian pembuka halaman depan (`#pembuka`, K81), mengikuti konsep visual
 * pemilik: latar krem, judul besar miring, foto di kanan yang memudar ke
 * latar, baris info (tanggal, tempat, keadaan pendaftaran), dua tombol
 * ajakan, dan deret kartu kelompok umur. Di layar lebar bagian ini mengisi
 * tepat satu layar di bawah kepala; bagian berikutnya mulai setelah digulir.
 *
 * - Gambar kepala (foto atlet) diunggah admin di Pengaturan > Halaman Depan;
 *   gambar dan logo yang belum diunggah atau berkasnya hilang tidak
 *   ditampilkan (K41). Logo acara tampil di kepala halaman dan logo
 *   Djarum Foundation di kaki halaman (tata letak publik, K84).
 * - Judul dari pengaturan, huruf besar; tiap kalimat (atau tiap kata bila
 *   hanya satu kalimat) satu baris dan baris terakhir oranye. Bila kosong, nama acara menjadi judul. Satu-satunya `<h1>`
 *   halaman ada di sini (`#judul-acara`).
 * - Teks admin di-escape; baris baru di dalam paragraf menjadi `<br>`.
 * - Tombol tanpa alamat tampil nonaktif dengan keterangannya (K39).
 *
 * @var array{nama: string, seri_tahun: string, tempat: string, tanggal: string}                              $acara
 * @var array<string, mixed>                                                                                   $tampilan     lihat IsiHalamanDepan::tampilan()
 * @var App\Libraries\BukaTutupPendaftaran                                                                     $bukaTutup
 * @var list<array{kunci: string, label: string, alamat: string|null, utama: bool, keterangan?: string|null}> $tombol
 * @var list<array{id: int, nama: string}>                                                                     $kelompokUmur
 */

use App\Libraries\BukaTutupPendaftaran;

// Ikon keadaan pendaftaran; terbuka memakai pengeras suara seperti konsep (K82).
$ikonKeadaan = [
    BukaTutupPendaftaran::DISIAPKAN    => 'hourglass-split',
    BukaTutupPendaftaran::BELUM_DIBUKA => 'calendar-event',
    BukaTutupPendaftaran::TERBUKA      => 'megaphone',
    BukaTutupPendaftaran::DITUTUP      => 'door-closed-fill',
];
$keadaan = $bukaTutup->keadaan();

// Kartu info ringkas seperti konsep (K82): "9–12" besar di atas "Desember 2026",
// tempat dipisah di koma terakhir. Bentuk lain tampil satu baris.
$tanggalInfo = preg_match('/^([\d\s–-]+?)\s+(\p{L}+\s+\d{4})$/u', $acara['tanggal'], $c) === 1 ? [$c[1], $c[2]] : ['', $acara['tanggal']];
$koma        = mb_strrpos($acara['tempat'], ',');
$tempatInfo  = $koma === false ? [$acara['tempat'], ''] : [trim(mb_substr($acara['tempat'], 0, $koma)), trim(mb_substr($acara['tempat'], $koma + 1))];
[$ringkasAtas, $ringkasBawah] = $bukaTutup->ringkas();

// Atribut width/height dari ukuran asli supaya halaman tidak melompat saat gambar dimuat.
$ukuranAtribut = static fn (?array $ukuran): string => $ukuran === null ? '' : ' width="' . $ukuran['lebar'] . '" height="' . $ukuran['tinggi'] . '"';

// Judul per kalimat ("Berlari. Bermain. Berprestasi."); judul pendek satu
// kalimat (paling banyak 4 kata) per kata ("MilkLife Athletics Challenge", K83).
// Judul satu kalimat yang lebih panjang mengalir biasa (K86). Huruf besar lewat CSS.
$kalimatJudul = preg_split('/(?<=[.!?])\s+/u', $tampilan['judul'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
$kataJudul    = count($kalimatJudul) === 1 ? (preg_split('/\s+/u', $kalimatJudul[0], -1, PREG_SPLIT_NO_EMPTY) ?: []) : [];
if (count($kataJudul) > 1 && count($kataJudul) <= 4) {
    $kalimatJudul = $kataJudul;
}

$ikonTombol = ['pendaftaran' => 'arrow-right', 'cek' => 'arrow-right'];

/** Nama kelompok umur dipisah: "KU 8 (Seneng Playon)" → ["KU 8", "Seneng Playon"]. */
$pisahNama = static fn (string $nama): array => preg_match('/^(.*?)\s*\((.+)\)\s*$/u', $nama, $c) === 1 ? [$c[1], $c[2]] : [$nama, ''];
?>
<section id="pembuka" class="pembuka<?= $tampilan['gambar_kepala'] !== null ? ' pembuka-dengan-gambar' : '' ?>" aria-labelledby="judul-acara">
    <div class="pembuka-utama">
        <?php if ($tampilan['gambar_kepala'] !== null) : ?>
            <div class="pembuka-gambar">
                <img src="<?= esc($tampilan['gambar_kepala'], 'attr') ?>" alt="<?= esc('Gambar kepala ' . $acara['nama'], 'attr') ?>"<?= $ukuranAtribut($tampilan['ukuran']['gambar_kepala'] ?? null) ?> fetchpriority="high">
            </div>
        <?php endif ?>

        <?php // Lengkung oranye dan hijau muda serta gelombang bawah: hiasan. ?>
        <svg class="pembuka-lengkung" viewBox="0 0 800 600" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M120 600 C 160 330, 420 150, 800 90" fill="none" stroke="#E85F31" stroke-width="46" stroke-linecap="round"/>
            <path d="M210 600 C 250 380, 470 220, 800 175" fill="none" stroke="#C9E265" stroke-width="22" stroke-linecap="round"/>
        </svg>

        <div class="container pembuka-isi">
            <div class="pembuka-kiri">
                <span class="pembuka-garis" aria-hidden="true"></span>

                <?php if ($kalimatJudul !== []) : ?>
                    <h1 id="judul-acara" class="pembuka-judul pembuka-judul-teks">
                        <?php foreach ($kalimatJudul as $i => $kalimat) : ?>
                            <span class="pembuka-judul-baris<?= $i === count($kalimatJudul) - 1 && $i > 0 ? ' pembuka-judul-sorot' : '' ?>"><?= esc($kalimat) ?></span>
                        <?php endforeach ?>
                    </h1>
                <?php else : ?>
                    <h1 id="judul-acara" class="pembuka-judul">
                        <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
                    </h1>
                <?php endif ?>

                <?php if ($tampilan['teks'] !== []) : ?>
                    <div class="pembuka-teks">
                        <?php foreach ($tampilan['teks'] as $paragraf) : ?>
                            <p><?= nl2br(esc($paragraf), false) ?></p>
                        <?php endforeach ?>
                    </div>
                <?php endif ?>

                <div class="pembuka-info">
                    <p class="pembuka-waktu">
                        <?php if ($acara['tanggal'] === '' && $acara['tempat'] === '') : ?>
                            <span class="info-sel"><i class="bi bi-calendar-event" aria-hidden="true"></i><span class="info-teks">Tanggal dan tempat acara segera diumumkan</span></span>
                        <?php else : ?>
                            <?php if ($acara['tanggal'] !== '') : ?>
                                <span class="info-sel"><i class="bi bi-calendar-event" aria-hidden="true"></i><span class="info-teks"><?php if ($tanggalInfo[0] !== '') : ?><strong class="info-besar"><?= esc($tanggalInfo[0]) ?></strong> <?php endif ?><span><?= esc($tanggalInfo[1]) ?></span></span></span>
                            <?php endif ?>
                            <?php if ($acara['tempat'] !== '') : ?>
                                <span class="info-sel"><i class="bi bi-geo-alt" aria-hidden="true"></i><span class="info-teks"><span><?= esc($tempatInfo[0]) ?></span><?php if ($tempatInfo[1] !== '') : ?><span class="visually-hidden">,</span> <span><?= esc($tempatInfo[1]) ?></span><?php endif ?></span></span>
                            <?php endif ?>
                        <?php endif ?>
                    </p>

                    <div class="keadaan-pendaftaran keadaan-<?= esc(str_replace('_', '-', $keadaan), 'attr') ?>" data-keadaan="<?= esc($keadaan, 'attr') ?>">
                        <i class="bi bi-<?= $ikonKeadaan[$keadaan] ?> keadaan-ikon" aria-hidden="true"></i>
                        <?php // Tampilan ringkas; kalimat lengkap tetap dibaca pembaca layar. ?>
                        <span class="keadaan-ringkas" aria-hidden="true"><span><?= esc($ringkasAtas) ?></span> <strong><?= esc($ringkasBawah) ?></strong></span>
                        <div class="visually-hidden">
                            <p class="keadaan-label"><?= esc($bukaTutup->label()) ?></p>
                            <p class="keadaan-kalimat"><?= esc($bukaTutup->kalimat()) ?></p>
                        </div>
                    </div>
                </div>

                <nav class="tombol-utama" aria-label="Pendaftaran">
                    <?php foreach ($tombol as $butir) : ?>
                        <?php $kelas = 'btn btn-lg ' . ($butir['utama'] ? 'btn-primary' : 'btn-garis-biru') . ' tombol-' . $butir['kunci']; ?>
                        <?php if ($butir['alamat'] === null) : ?>
                            <a class="<?= $kelas ?> disabled tombol-nonaktif" role="link" aria-disabled="true">
                                <?= esc($butir['label']) ?>
                                <span class="tombol-segera"><?= esc($butir['keterangan'] ?? 'Segera') ?></span>
                            </a>
                        <?php else : ?>
                            <a class="<?= $kelas ?>" href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?> <i class="bi bi-<?= $ikonTombol[$butir['kunci']] ?? 'arrow-right' ?>" aria-hidden="true"></i></a>
                        <?php endif ?>
                    <?php endforeach ?>
                </nav>
            </div>
        </div>

        <svg class="pembuka-gelombang" viewBox="0 0 1440 80" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M0 52 C 360 6, 900 6, 1440 30 L1440 80 L0 80 Z" fill="#E85F31"/>
            <path d="M0 66 C 380 22, 920 20, 1440 44 L1440 80 L0 80 Z" fill="#021F7B"/>
            <path d="M0 76 C 400 38, 940 36, 1440 58 L1440 80 L0 80 Z" fill="#FBF3E8"/>
        </svg>
    </div>

    <?php if ($kelompokUmur !== []) : ?>
        <div class="pembuka-ku">
            <div class="container">
                <p class="pembuka-ku-label"><span>Kelompok usia</span></p>
                <ul class="pembuka-ku-daftar">
                    <?php foreach ($kelompokUmur as $i => $ku) : ?>
                        <?php [$namaKu, $julukan] = $pisahNama($ku['nama']); ?>
                        <li>
                            <a class="pembuka-ku-kartu pembuka-ku-kartu-<?= $i % 2 === 0 ? 'hijau' : 'oranye' ?>" href="#lomba-ku-<?= (int) $ku['id'] ?>">
                                <span class="pembuka-ku-nama">
                                    <?= esc($namaKu) ?>
                                    <?php if ($julukan !== '') : ?>
                                        <span class="pembuka-ku-julukan"><?= esc($julukan) ?></span>
                                    <?php endif ?>
                                </span>
                            </a>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    <?php endif ?>
</section>
