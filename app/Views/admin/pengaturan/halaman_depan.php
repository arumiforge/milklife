<?php
/**
 * Formulir isi halaman depan (`HalamanDepanController`), tata letak `admin_tw`.
 *
 * @var CodeIgniter\View\View                                                      $this
 * @var array<string, string>                                                      $isian
 * @var array<string, string>                                                      $galat
 * @var array<string, array{label: string, rasio: string, saran: string, jalur: string}> $gambar kunci => keterangan dan jalur tersimpan ('' bila belum ada)
 * @var list<string>                                                               $gambarPerluDipilihLagi
 * @var bool                                                                       $tampilkanBiaya sakelar `HalamanDepan.tampilkan_biaya`
 * @var bool                                                                       $hasilDiAtas sakelar `HalamanDepan.hasil_di_atas` (K143)
 * @var array<string, bool>                                                        $bilahAtas butir bilah menu atas yang dicentang (F10-07)
 * @var list<array{berkas: string, keterangan: string, ada: bool}>                  $carousel foto carousel pembuka berurutan (F9-03)
 */

use App\Libraries\CarouselHalamanDepan;
use App\Libraries\Unggahan;

// ikon-sprite: arrow-up arrow-down

$kotak = static function (string $nama, string $label, string $bantuan, array $isian, array $galat, int $baris = 0, array $atribut = []): string {
    $salah    = isset($galat[$nama]);
    $jelaskan = trim(($bantuan !== '' ? $nama . '-bantuan ' : '') . ($salah ? $nama . '-galat' : ''));
    $tambahan = ($salah ? ' aria-invalid="true"' : '') . ($jelaskan !== '' ? ' aria-describedby="' . $jelaskan . '"' : '');
    foreach ($atribut as $kunci => $nilai) {
        $tambahan .= ' ' . $kunci . '="' . esc($nilai, 'attr') . '"';
    }
    $kelas = 'block w-full rounded-lg border p-2.5 text-base focus:ring-4 sm:text-sm '
        . ($salah
            ? 'border-red-500 bg-red-50 text-red-900 focus:border-red-500 focus:ring-red-100'
            : 'border-gray-300 bg-white text-gray-900 focus:border-primary-600 focus:ring-primary-100');

    $masukan = $baris > 0
        ? '<textarea class="' . $kelas . '" id="' . $nama . '" name="' . $nama . '" rows="' . $baris . '"' . $tambahan . '>' . esc($isian[$nama] ?? '') . '</textarea>'
        : '<input type="text" class="' . $kelas . '" id="' . $nama . '" name="' . $nama . '" value="' . esc($isian[$nama] ?? '', 'attr') . '"' . $tambahan . '>';

    return '<div>'
        . '<label for="' . $nama . '" class="mb-1.5 block text-sm font-semibold text-gray-900">' . esc($label) . '</label>'
        . $masukan
        . ($salah ? '<p id="' . $nama . '-galat" class="mt-1.5 text-sm font-medium text-red-700">' . esc($galat[$nama]) . '</p>' : '')
        . ($bantuan !== '' ? '<p id="' . $nama . '-bantuan" class="mt-1.5 text-sm text-gray-600">' . esc($bantuan) . '</p>' : '')
        . '</div>';
};

// Rasio kotak gambar (`1x1`, `4x3`, `16x9`, `21x9`) => kelas Tailwind.
$kelasRasio = static fn (string $rasio): string => [
    '1x1'  => 'aspect-square',
    '4x3'  => 'aspect-[4/3]',
    '16x9' => 'aspect-video',
    '21x9' => 'aspect-[21/9]',
][$rasio] ?? 'aspect-video';

$kartu = 'rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-5';
?>
<?= $this->extend('tata_letak/admin_tw') ?>

<?= $this->section('judul') ?>Halaman Depan<?= $this->endSection() ?>

<?= $this->section('judul_halaman') ?>Halaman Depan<?= $this->endSection() ?>

<?= $this->section('isi') ?>
<div class="space-y-4 p-4 sm:px-6 sm:py-4">
    <?= $this->include('admin/pengaturan/_navigasi') ?>

    <p class="text-gray-700">
        Isi yang tampil di halaman depan web pendaftaran. Semua boleh diisi bertahap.
        Nama, seri, tahun, tempat, dan tanggal acara diatur di <a href="<?= site_url('admin/pengaturan/acara') ?>" class="font-semibold text-primary-700 underline">Data Acara</a>.
    </p>

    <?= view('admin/_galat_isian_tw', ['galat' => $galat], ['saveData' => false]) ?>

    <?php if ($gambarPerluDipilihLagi !== []) : ?>
        <?= komponen('peringatan', ['jenis' => 'peringatan', 'isi' => esc(implode(', ', $gambarPerluDipilihLagi)) . ' yang tadi dipilih belum tersimpan karena ada isian yang perlu diperbaiki. Pilih gambarnya lagi sebelum menyimpan.']) ?>
    <?php endif ?>

    <form action="<?= site_url('admin/pengaturan/halaman-depan') ?>" method="post" enctype="multipart/form-data" novalidate class="space-y-4">
        <?= csrf_field() ?>

        <section class="<?= $kartu ?>" aria-labelledby="judul-gambar">
            <h2 id="judul-gambar" class="text-lg font-bold text-gray-900">Gambar dan logo</h2>
            <p class="mb-4 mt-1 text-sm text-gray-600">
                JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR) ?> per gambar.
                Biarkan kotak unggah kosong bila gambarnya tidak ingin diganti.
            </p>

            <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                <?php foreach ($gambar as $nama => $butir) : ?>
                    <?php $salah = isset($galat[$nama]); ?>
                    <div class="min-w-0 <?= $nama === 'gambar_kepala' ? 'md:col-span-2 2xl:col-span-3' : '' ?>">
                        <fieldset class="h-full rounded-xl border border-gray-200 p-4" data-kotak-gambar="<?= esc($nama, 'attr') ?>">
                            <legend class="px-1 text-sm font-semibold text-gray-900"><?= esc($butir['label']) ?></legend>

                            <div class="mb-3 <?= $nama === 'gambar_kepala' ? 'max-w-2xl' : ($butir['rasio'] === '1x1' ? 'max-w-48' : 'max-w-xs') ?>" data-pratinjau="<?= esc($nama, 'attr') ?>" data-rasio="<?= esc($butir['rasio'], 'attr') ?>">
                                <?php if ($butir['jalur'] !== '') : ?>
                                    <div class="<?= $kelasRasio($butir['rasio']) ?> overflow-hidden rounded-lg border border-gray-200 bg-white">
                                        <img src="<?= esc(base_url($butir['jalur']), 'attr') ?>" alt="<?= esc($butir['label'] . ' saat ini', 'attr') ?>"
                                            class="<?= $nama === 'gambar_kepala' ? 'size-full object-cover' : 'size-full object-contain p-2' ?>">
                                    </div>
                                <?php else : ?>
                                    <?= view('tata_letak/_tanpa_gambar_tw', ['rasio' => $butir['rasio'], 'label' => $butir['label']], ['saveData' => false]) ?>
                                <?php endif ?>
                            </div>

                            <label for="<?= esc($nama, 'attr') ?>" class="mb-1 block text-sm text-gray-700"><?= $butir['jalur'] === '' ? 'Unggah gambar' : 'Ganti gambar' ?></label>
                            <input type="file" class="block w-full cursor-pointer rounded-lg border text-sm file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200 <?= $salah ? 'border-red-500 bg-red-50 text-red-900' : 'border-gray-300 bg-white text-gray-900' ?>" id="<?= esc($nama, 'attr') ?>" name="<?= esc($nama, 'attr') ?>"
                                accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>"<?= $salah ? ' aria-invalid="true"' : '' ?>
                                aria-describedby="<?= esc($nama, 'attr') ?>-bantuan<?= $salah ? ' ' . esc($nama, 'attr') . '-galat' : '' ?>">
                            <?php if ($salah) : ?>
                                <p id="<?= esc($nama, 'attr') ?>-galat" class="mt-1.5 text-sm font-medium text-red-700"><?= esc($galat[$nama]) ?></p>
                            <?php endif ?>
                            <p id="<?= esc($nama, 'attr') ?>-bantuan" class="mt-1.5 text-sm text-gray-600"><?= esc($butir['saran']) ?></p>

                            <?php if ($butir['jalur'] !== '') : ?>
                                <label class="mt-2 inline-flex min-h-11 items-center gap-2" for="hapus_<?= esc($nama, 'attr') ?>">
                                    <input class="size-4 rounded border-gray-300 text-primary-700 focus:ring-primary-300" type="checkbox" value="1" id="hapus_<?= esc($nama, 'attr') ?>" name="hapus_<?= esc($nama, 'attr') ?>"
                                        data-konfirmasi-centang="Gambar ini dihapus dari server saat pengaturan disimpan, dan tidak bisa dikembalikan." data-konfirmasi-tombol="Hapus gambar">
                                    <span class="text-sm text-gray-900">Hapus gambar ini</span>
                                </label>
                            <?php endif ?>
                        </fieldset>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <div class="grid gap-4 xl:grid-cols-2">
            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-teks">
                <h2 id="judul-teks" class="mb-4 text-lg font-bold text-gray-900">Teks</h2>
                <div class="space-y-4">
                    <?= $kotak('judul', 'Judul', 'Judul besar di bagian atas, tampil dengan huruf besar. Judul pendek (paling banyak 4 kata) tampil satu kata per baris dengan kata terakhir oranye, misalnya "MilkLife Athletics Challenge". Bila judul berisi beberapa kalimat bertitik, tiap kalimat menjadi satu baris. Judul satu kalimat yang lebih panjang mengalir biasa.', $isian, $galat, 0, ['maxlength' => '150']) ?>
                    <?= $kotak('teks', 'Teks pengantar', 'Beberapa kalimat di bawah judul. Tekan Enter dua kali untuk membuat paragraf baru.', $isian, $galat, 6, ['maxlength' => '2000']) ?>
                    <?= $kotak('footer', 'Teks kaki halaman', 'Tampil di bagian paling bawah, misalnya nama penyelenggara dan tahun.', $isian, $galat, 3, ['maxlength' => '500']) ?>
                </div>
            </section>

            <section class="<?= $kartu ?> min-w-0" aria-labelledby="judul-wa">
                <h2 id="judul-wa" class="mb-4 text-lg font-bold text-gray-900">Tombol WA</h2>
                <div class="space-y-4">
                    <?= $kotak('nomor_wa_tanya', 'Nomor WA tanya admin', 'Nomor yang dihubungi lewat tombol "Tanya admin", misalnya 0812-3456-7890.', $isian, $galat, 0, ['inputmode' => 'tel', 'autocomplete' => 'off']) ?>
                    <?= $kotak('pesan_tanya', 'Pesan tanya admin', 'Pesan yang sudah terketik saat pengunjung menekan "Tanya admin". Pengunjung masih bisa mengubahnya sebelum dikirim.', $isian, $galat, 4, ['maxlength' => '1000']) ?>
                    <?= $kotak('pesan_bagikan', 'Pesan bagikan', 'Pesan yang dibagikan lewat tombol "Bagikan ke WA". Tulis juga alamat web pendaftaran supaya penerima bisa langsung membukanya.', $isian, $galat, 4, ['maxlength' => '1000']) ?>
                </div>
            </section>
        </div>

        <?php // Peta lokasi (F10-02): bagian Lokasi di halaman depan, dekat Kontak. ?>
        <section class="<?= $kartu ?>" aria-labelledby="judul-peta" data-pengaturan-peta>
            <h2 id="judul-peta" class="text-lg font-bold text-gray-900">Peta lokasi</h2>
            <p class="mb-4 mt-1 text-sm text-gray-600">
                Peta tampil di bagian Lokasi halaman depan, di atas Kontak, dengan alamat dari Tempat di Data Acara.
                Kosongkan alamat peta bila bagian Lokasi tidak ingin ditampilkan.
            </p>
            <div class="grid gap-4 xl:grid-cols-2">
                <?= $kotak('peta_embed', 'Alamat peta (embed Google Maps)', 'Di Google Maps buka tempatnya, pilih Bagikan > Sematkan peta, lalu salin kodenya ke sini. Kode <iframe> utuh juga boleh; yang disimpan hanya alamat yang diawali https://www.google.com/maps/embed?.', $isian, $galat, 4, ['maxlength' => '2000', 'spellcheck' => 'false', 'autocomplete' => 'off']) ?>
                <?= $kotak('peta_tautan', 'Tautan buka peta', 'Tautan tombol "Buka di Google Maps", misalnya https://maps.google.com/?q=Supersoccer+Arena+Kudus. Kosongkan bila tombolnya tidak perlu.', $isian, $galat, 0, ['maxlength' => '2000', 'inputmode' => 'url', 'spellcheck' => 'false', 'autocomplete' => 'off']) ?>
            </div>
        </section>

        <section class="<?= $kartu ?> space-y-4" aria-labelledby="judul-lomba">
            <h2 id="judul-lomba" class="text-lg font-bold text-gray-900">Bagian lomba</h2>
            <div>
                <label for="tampilkan_biaya" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                    <input type="checkbox" role="switch" id="tampilkan_biaya" name="tampilkan_biaya" value="1" class="peer sr-only"
                        aria-describedby="tampilkan_biaya-bantuan"<?= $tampilkanBiaya ? ' checked' : '' ?>>
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                    <span class="text-sm font-semibold text-gray-900">Tampilkan biaya di halaman depan</span>
                </label>
                <p id="tampilkan_biaya-bantuan" class="text-sm text-gray-600">
                    Biaya individu tiap kelompok umur dan biaya tiap tim beregu. Bila dimatikan, biaya tidak tampil di halaman depan; rinciannya tetap muncul di bagian pembayaran saat sekolah mendaftar.
                </p>
            </div>
            <div>
                <label for="hasil_di_atas" class="inline-flex min-h-11 cursor-pointer items-center gap-3">
                    <input type="checkbox" role="switch" id="hasil_di_atas" name="hasil_di_atas" value="1" class="peer sr-only"
                        aria-describedby="hasil_di_atas-bantuan"<?= $hasilDiAtas ? ' checked' : '' ?>>
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-gray-300 transition-colors after:absolute after:start-[2px] after:top-[2px] after:size-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-primary-700 peer-checked:after:translate-x-full peer-focus-visible:ring-4 peer-focus-visible:ring-primary-300 rtl:peer-checked:after:-translate-x-full" aria-hidden="true"></span>
                    <span class="text-sm font-semibold text-gray-900">Tampilkan hasil di atas (hari lomba)</span>
                </label>
                <p id="hasil_di_atas-bantuan" class="text-sm text-gray-600">
                    Bila dinyalakan, bagian hasil dan juara umum pindah tepat di bawah bagian pembuka halaman depan, supaya mudah dilihat selama lomba. Hanya berlaku setelah ada nomor yang hasilnya diterbitkan.
                </p>
            </div>
        </section>

        <?php // ---------- Bilah menu atas (F10-07, K154) ---------- ?>
        <section class="<?= $kartu ?>" aria-labelledby="judul-bilah-atas" data-pengaturan-bilah-atas>
            <h2 id="judul-bilah-atas" class="text-lg font-bold text-gray-900">Bilah menu atas</h2>
            <p class="mb-4 mt-1 text-sm text-gray-600">
                Tautan kecil di atas menu utama pada layar komputer, juga di menu samping ponsel.
                Ikon media sosial ditambahkan otomatis dari Pengaturan &gt; Kontak.
            </p>
            <fieldset>
                <legend class="sr-only">Butir bilah menu atas</legend>
                <div class="grid gap-1 sm:grid-cols-2">
                    <?php foreach (\App\Libraries\MenuPublik::BILAH_ATAS as $kunciBilah => $labelBilah) : ?>
                        <label for="bilah_atas_<?= $kunciBilah ?>" class="flex min-h-11 cursor-pointer items-center gap-3 text-sm text-gray-900">
                            <input type="checkbox" id="bilah_atas_<?= $kunciBilah ?>" name="bilah_atas_<?= $kunciBilah ?>" value="1"
                                class="size-5 shrink-0 rounded border-gray-300 text-primary-700 focus:ring-4 focus:ring-primary-300"<?= ($bilahAtas[$kunciBilah] ?? true) ? ' checked' : '' ?>>
                            <?= esc($labelBilah) ?>
                        </label>
                    <?php endforeach ?>
                </div>
            </fieldset>
        </section>
        <?php // ---------- Akhir bilah menu atas ---------- ?>

        <div class="flex flex-wrap gap-2">
            <?= komponen('tombol', ['label' => 'Simpan isi halaman depan', 'tipe' => 'submit', 'ikon' => 'check']) ?>
        </div>
    </form>

    <?= $this->include('admin/pengaturan/_halaman_peserta') ?>

    <?php // Foto carousel pembuka (F9-03): tiap tindakan formulir sendiri, langsung tersimpan. ?>
    <section id="carousel" class="<?= $kartu ?>" aria-labelledby="judul-carousel">
        <h2 id="judul-carousel" class="text-lg font-bold text-gray-900">Foto carousel</h2>
        <p class="mb-4 mt-1 text-sm text-gray-600">
            Foto yang bergeser otomatis di bagian pembuka halaman depan, berurutan dari atas.
            Pilih foto mendatar tanpa tulisan; JPG, PNG, atau WEBP, paling besar <?= Unggahan::ukuranTeks(Unggahan::BATAS_GAMBAR) ?>.
            Paling banyak <?= CarouselHalamanDepan::BATAS ?> foto. Bila carousel kosong, gambar kepala yang tampil.
        </p>

        <?php if ($carousel === []) : ?>
            <p class="mb-4 text-gray-600" data-carousel-kosong>Belum ada foto di carousel.</p>
        <?php else : ?>
            <ol class="mb-6 grid gap-2" data-daftar-carousel>
                <?php foreach ($carousel as $i => $foto) : ?>
                    <?php $urutan = $i + 1; $alamatFoto = site_url('admin/pengaturan/halaman-depan/carousel/' . $urutan); ?>
                    <li class="flex flex-wrap items-center gap-3 rounded-xl border border-gray-200 p-2" data-foto-carousel="<?= $urutan ?>">
                        <span class="w-8 text-center font-semibold text-gray-600"><?= $urutan ?></span>
                        <?php if ($foto['ada']) : ?>
                            <img src="<?= esc(base_url($foto['berkas']), 'attr') ?>" alt="" class="h-[85px] w-32 rounded-lg object-cover" width="128" height="85" loading="lazy">
                        <?php else : ?>
                            <?= komponen('lencana', ['label' => 'Berkas hilang', 'warna' => 'kuning', 'ikon' => 'triangle-alert']) ?>
                        <?php endif ?>
                        <span class="min-w-0 flex-1 break-words text-sm text-gray-900"><?= $foto['keterangan'] !== '' ? esc($foto['keterangan']) : '<span class="text-gray-600">Tanpa keterangan</span>' ?></span>
                        <span class="flex flex-wrap gap-1">
                            <?php foreach (['naik' => ['arrow-up', 'Naikkan'], 'turun' => ['arrow-down', 'Turunkan']] as $arah => [$ikonArah, $labelArah]) : ?>
                                <?php if (($arah === 'naik' && $i > 0) || ($arah === 'turun' && $i < count($carousel) - 1)) : ?>
                                    <form action="<?= $alamatFoto . '/' . $arah ?>" method="post">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="berkas" value="<?= esc($foto['berkas'], 'attr') ?>">
                                        <button type="submit" class="inline-flex size-11 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:outline-none focus:ring-4 focus:ring-gray-100"><?= ikon($ikonArah) ?><span class="sr-only"><?= $labelArah ?> foto <?= $urutan ?></span></button>
                                    </form>
                                <?php endif ?>
                            <?php endforeach ?>
                            <form action="<?= $alamatFoto ?>/hapus" method="post"
                                data-konfirmasi="Foto ini dihapus dari carousel dan dari server, dan tidak bisa dikembalikan."
                                data-konfirmasi-judul="Hapus foto?" data-konfirmasi-tombol="Hapus foto">
                                <?= csrf_field() ?>
                                <input type="hidden" name="berkas" value="<?= esc($foto['berkas'], 'attr') ?>">
                                <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-red-300 bg-white px-3 text-sm font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus:ring-4 focus:ring-red-100"><?= ikon('trash-2') ?> Hapus<span class="sr-only"> foto <?= $urutan ?></span></button>
                            </form>
                        </span>
                    </li>
                <?php endforeach ?>
            </ol>
        <?php endif ?>

        <?php if (count($carousel) < CarouselHalamanDepan::BATAS) : ?>
            <form action="<?= site_url('admin/pengaturan/halaman-depan/carousel') ?>" method="post" enctype="multipart/form-data" class="grid items-start gap-4 md:grid-cols-12" data-tambah-carousel>
                <?= csrf_field() ?>
                <div class="min-w-0 md:col-span-5">
                    <label for="foto-carousel" class="mb-1.5 block text-sm font-semibold text-gray-900">Foto baru</label>
                    <input type="file" class="block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-sm text-gray-900 file:me-3 file:min-h-11 file:border-0 file:bg-gray-100 file:px-4 file:font-semibold file:text-gray-900 hover:file:bg-gray-200" id="foto-carousel" name="foto" accept="image/jpeg,image/png,image/webp" data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>" required>
                </div>
                <div class="min-w-0 md:col-span-5">
                    <label for="keterangan-carousel" class="mb-1.5 block text-sm font-semibold text-gray-900">Keterangan foto</label>
                    <input type="text" class="block w-full rounded-lg border border-gray-300 bg-white p-2.5 text-base text-gray-900 focus:border-primary-600 focus:ring-4 focus:ring-primary-100 sm:text-sm" id="keterangan-carousel" name="keterangan" maxlength="<?= CarouselHalamanDepan::BATAS_KETERANGAN ?>" aria-describedby="keterangan-carousel-bantuan">
                    <p id="keterangan-carousel-bantuan" class="mt-1.5 text-sm text-gray-600">Isi foto dengan kalimat singkat untuk pembaca layar, misalnya "Atlet melempar lembing".</p>
                </div>
                <div class="md:col-span-2 md:pt-7">
                    <?= komponen('tombol', ['label' => 'Tambah foto', 'tipe' => 'submit', 'varian' => 'kedua', 'ikon' => 'plus', 'blok' => true]) ?>
                </div>
            </form>
        <?php endif ?>
    </section>

    <?= $this->include('admin/pengaturan/_halaman_jadwal') ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('skrip') ?>
<script src="<?= aset('js/konfirmasi.js') ?>"></script>
<script src="<?= aset('js/admin-pengaturan-halaman-depan.js') ?>"></script>
<?= $this->endSection() ?>
