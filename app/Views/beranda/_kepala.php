<?php
/**
 * Kepala halaman depan (`#pembuka`): gambar kepala sebagai latar di belakang
 * judul (K44), logo, judul,
 * teks, data acara, keadaan pendaftaran, dan tombol Pendaftaran serta Cek
 * Pendaftaran.
 *
 * - Gambar dan logo yang belum diunggah (atau berkasnya hilang) tidak
 *   ditampilkan sama sekali (K41).
 * - Judul dari pengaturan Halaman Depan; bila kosong, nama acara menjadi
 *   judul. Satu-satunya `<h1>` halaman ada di sini (`#judul-acara`).
 * - Teks admin di-escape; baris baru di dalam paragraf menjadi `<br>`.
 * - Tombol tanpa alamat tampil nonaktif dengan tulisan "Segera" (K39).
 *
 * @var array{nama: string, seri_tahun: string, tempat: string, tanggal: string} $acara
 * @var array<string, mixed>                                                       $tampilan  lihat IsiHalamanDepan::tampilan()
 * @var App\Libraries\BukaTutupPendaftaran                                         $bukaTutup
 * @var list<array{kunci: string, label: string, alamat: string|null, utama: bool}> $tombol
 */

use App\Libraries\BukaTutupPendaftaran;

// Ikon keadaan pendaftaran (sama dengan dasbor admin).
$ikonKeadaan = [
    BukaTutupPendaftaran::DISIAPKAN    => 'hourglass-split',
    BukaTutupPendaftaran::BELUM_DIBUKA => 'calendar-event',
    BukaTutupPendaftaran::TERBUKA      => 'door-open-fill',
    BukaTutupPendaftaran::DITUTUP      => 'door-closed-fill',
];
$keadaan = $bukaTutup->keadaan();

// Logo yang sudah diunggah, berurutan seperti di pengaturan.
$logo = [];

foreach ([
    'logo_persegi'         => 'Logo ' . $acara['nama'],
    'logo_persegi_panjang' => 'Logo ' . $acara['nama'],
    'logo_djarum'          => 'Logo Bakti Olahraga Djarum Foundation',
] as $kunci => $alt) {
    if ($tampilan[$kunci] !== null) {
        $logo[] = ['kunci' => $kunci, 'alamat' => $tampilan[$kunci], 'alt' => $alt, 'ukuran' => $tampilan['ukuran'][$kunci] ?? null];
    }
}

// Atribut width/height dari ukuran asli supaya halaman tidak melompat saat gambar dimuat.
$ukuranAtribut = static fn (?array $ukuran): string => $ukuran === null ? '' : ' width="' . $ukuran['lebar'] . '" height="' . $ukuran['tinggi'] . '"';
?>
<section id="pembuka" class="pembuka<?= $tampilan['gambar_kepala'] !== null ? ' pembuka-dengan-gambar' : '' ?>" aria-labelledby="judul-acara">
    <?php if ($tampilan['gambar_kepala'] !== null) : ?>
        <div class="pembuka-gambar">
            <img src="<?= esc($tampilan['gambar_kepala'], 'attr') ?>" alt="<?= esc('Gambar kepala ' . $acara['nama'], 'attr') ?>"<?= $ukuranAtribut($tampilan['ukuran']['gambar_kepala'] ?? null) ?> fetchpriority="high">
        </div>
    <?php endif ?>

    <div class="container py-4 py-md-5">
        <?php if ($logo !== []) : ?>
            <ul class="pembuka-logo list-unstyled" aria-label="Logo">
                <?php foreach ($logo as $butir) : ?>
                    <li class="pembuka-logo-butir pembuka-<?= str_replace('_', '-', $butir['kunci']) ?>">
                        <img src="<?= esc($butir['alamat'], 'attr') ?>" alt="<?= esc($butir['alt'], 'attr') ?>"<?= $ukuranAtribut($butir['ukuran']) ?>>
                    </li>
                <?php endforeach ?>
            </ul>
        <?php endif ?>

        <?php if ($acara['seri_tahun'] !== '') : ?>
            <p class="pita-miring fs-5 mb-3"><span><?= esc($acara['seri_tahun']) ?></span></p>
        <?php endif ?>

        <?php if ($tampilan['judul'] !== '') : ?>
            <p class="pembuka-nama mb-2"><?= esc($acara['nama']) ?></p>
            <h1 id="judul-acara" class="pembuka-judul pembuka-judul-teks mb-4"><?= esc($tampilan['judul']) ?></h1>
        <?php else : ?>
            <h1 id="judul-acara" class="pembuka-judul mb-4">
                <?= view('tata_letak/_merek', [], ['saveData' => false]) ?>
            </h1>
        <?php endif ?>

        <?php if ($tampilan['teks'] !== []) : ?>
            <div class="pembuka-teks mb-4">
                <?php foreach ($tampilan['teks'] as $paragraf) : ?>
                    <p><?= nl2br(esc($paragraf), false) ?></p>
                <?php endforeach ?>
            </div>
        <?php endif ?>

        <p class="pembuka-waktu mb-4">
            <?php if ($acara['tanggal'] === '' && $acara['tempat'] === '') : ?>
                <span><i class="bi bi-calendar-event" aria-hidden="true"></i>Tanggal dan tempat acara segera diumumkan</span>
            <?php else : ?>
                <?php if ($acara['tanggal'] !== '') : ?>
                    <span><i class="bi bi-calendar-event" aria-hidden="true"></i><?= esc($acara['tanggal']) ?></span>
                <?php endif ?>
                <?php if ($acara['tempat'] !== '') : ?>
                    <span><i class="bi bi-geo-alt" aria-hidden="true"></i><?= esc($acara['tempat']) ?></span>
                <?php endif ?>
            <?php endif ?>
        </p>

        <div class="pembuka-pendaftaran">
            <div class="keadaan-pendaftaran keadaan-<?= esc(str_replace('_', '-', $keadaan), 'attr') ?>" data-keadaan="<?= esc($keadaan, 'attr') ?>">
                <span class="keadaan-ikon"><i class="bi bi-<?= $ikonKeadaan[$keadaan] ?>" aria-hidden="true"></i></span>
                <div>
                    <p class="keadaan-label mb-1"><?= esc($bukaTutup->label()) ?></p>
                    <p class="keadaan-kalimat mb-0"><?= esc($bukaTutup->kalimat()) ?></p>
                </div>
            </div>

            <div class="pembuka-tombol">
                <?php foreach ($tombol as $butir) : ?>
                    <?php $kelas = 'btn btn-lg ' . ($butir['utama'] ? 'btn-primary' : 'btn-outline-light') . ' tombol-' . $butir['kunci']; ?>
                    <?php if ($butir['alamat'] === null) : ?>
                        <a class="<?= $kelas ?> disabled tombol-nonaktif" role="link" aria-disabled="true">
                            <?= esc($butir['label']) ?>
                            <span class="tombol-segera">Segera</span>
                        </a>
                    <?php else : ?>
                        <a class="<?= $kelas ?>" href="<?= site_url($butir['alamat']) ?>"><?= esc($butir['label']) ?></a>
                    <?php endif ?>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</section>
