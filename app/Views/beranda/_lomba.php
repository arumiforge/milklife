<?php
/**
 * Bagian lomba halaman depan (`#lomba`): kelompok umur, nomor lomba, dan biaya.
 *
 * Data yang tersedia (dari `IsiHalamanDepan::kelompokUmur()`, lihat komentar
 * kelasnya untuk bentuk persisnya):
 *
 * @var list<array{
 *     id: int,
 *     nama: string,
 *     tahun_lahir: string,
 *     jenjang: string,
 *     pilihan: list<string>,
 *     biaya_individu: int|null,
 *     nomor_individu: list<array{id: int, nama: string, kategori: string, kategori_label: string}>,
 *     nomor_beregu: list<array{id: int, nama: string, kategori: string, kategori_label: string, jumlah_inti: int|null, batas_anggota: int|null, jumlah_putra: int|null, jumlah_putri: int|null, biaya_beregu: int|null}>
 * }> $kelompokUmur Kelompok umur aktif dengan nomor aktifnya; [] bila belum ada.
 * @var bool $tampilkanBiaya Sakelar admin `HalamanDepan.tampilkan_biaya`
 *     (`IsiHalamanDepan::tampilkanBiaya()`); bila `false`, biaya individu dan
 *     biaya tim tidak ditulis sama sekali. Nilai awal `false` (K74).
 *
 * Kesepakatan (rencana Tahap 3, "Halaman depan"):
 * - Bagian tidak ditampilkan bila `$kelompokUmur === []`.
 * - `<section id="lomba" aria-labelledby="judul-lomba">` dengan `<h2 id="judul-lomba">`.
 * - Uang dengan `rupiah()`; `null` → "Biaya segera diumumkan", `0` → "Gratis".
 * - Tata letak baru Tailwind (F9-03); penanda uji berupa atribut `data-*`.
 *   Ilustrasi nomor lomba G9-05 tampil di samping nama nomor bila berkasnya ada (K41).
 *
 * Susunan: satu kartu per kelompok umur (`#lomba-ku-<id>`), paling banyak
 * dua kolom di layar lebar supaya teks yang lebih besar tetap lega (K76). Tahun lahir,
 * jenjang, dan biaya individu (bila ditampilkan) selalu terlihat; daftar nomornya dilipat dalam
 * `<details>` (asumsi rencana 5) supaya halaman tidak terlalu panjang di
 * ponsel, tanpa tabel lebar dan tanpa skrip.
 *
 * Nomor dengan nama dan aturan yang sama digabung menjadi satu baris
 * (misalnya "Lari 60 m — Putra, Putri"); kategorinya berurutan seperti
 * nomornya di panel admin.
 */

use App\Libraries\IlustrasiBeranda;

if ($kelompokUmur === []) {
    return;
}

$tampilkanBiaya = (bool) ($tampilkanBiaya ?? false);

/** Label kategori sebagai lencana berwarna (K76); pemisah koma tetap terbaca pembaca layar. */
$lencanaKategori = static fn (array $kategori): string => implode('<span class="sr-only">, </span>', array_map(
    static fn (string $label): string => '<span class="' . (match (strtolower($label)) {
        'putra' => 'bg-primary-100 text-primary-800',
        'putri' => 'bg-magenta-500/10 text-magenta-600',
        default => 'bg-oranye-100 text-oranye-800',
    }) . ' inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold" data-kategori="' . esc(strtolower($label), 'attr') . '">' . esc($label) . '</span>',
    $kategori,
));

/** Teks biaya: `null` belum diisi, `0` gratis, selain itu rupiah dengan satuannya. */
$teksBiaya = static fn (?int $biaya, string $satuan, string $belum): string => match (true) {
    $biaya === null => $belum,
    $biaya === 0    => 'Gratis',
    default         => rupiah($biaya) . ' ' . $satuan,
};

/**
 * Gabungkan nomor yang kuncinya sama, urutan kemunculan pertama dipakai.
 *
 * @param list<array<string, mixed>>               $daftar
 * @param callable(array<string, mixed>): string $kunci
 *
 * @return list<array{nomor: array<string, mixed>, kategori: list<string>}>
 */
$gabung = static function (array $daftar, callable $kunci): array {
    $hasil = [];

    foreach ($daftar as $nomor) {
        $k = $kunci($nomor);

        $hasil[$k] ??= ['nomor' => $nomor, 'kategori' => []];

        if (! in_array($nomor['kategori_label'], $hasil[$k]['kategori'], true)) {
            $hasil[$k]['kategori'][] = $nomor['kategori_label'];
        }
    }

    return array_values($hasil);
};

/** Keterangan tim nomor beregu; nilai yang belum diisi tidak ditulis. */
$keteranganTim = static function (array $nomor): array {
    $inti  = $nomor['jumlah_inti'];
    $batas = $nomor['batas_anggota'];

    // Batas anggota tidak ditulis di halaman depan (K80); tetap berlaku saat mendaftar.
    $anggota = match (true) {
        $inti !== null && $batas !== null && $batas > $inti => "{$inti} atlet inti per regu",
        $inti !== null                                       => "{$inti} atlet per regu",
        default                                              => null,
    };

    $komposisi = match (true) {
        $nomor['jumlah_putra'] !== null && $nomor['jumlah_putri'] !== null => "Minimal {$nomor['jumlah_putra']} putra dan {$nomor['jumlah_putri']} putri",
        $nomor['jumlah_putra'] !== null                                     => "Minimal {$nomor['jumlah_putra']} putra",
        $nomor['jumlah_putri'] !== null                                     => "Minimal {$nomor['jumlah_putri']} putri",
        default                                                             => null,
    };

    return array_values(array_filter([
        ['ikon' => 'users', 'teks' => $anggota],
        ['ikon' => 'user-round-check', 'teks' => $komposisi],
    ], static fn (array $butir): bool => $butir['teks'] !== null));
};

/** Ilustrasi kecil nomor lomba (G9-05), atau kosong bila belum ada. */
$gambarNomor = static function (string $nama): string {
    $alamat = IlustrasiBeranda::nomor($nama);

    return $alamat === null ? '' : '<img src="' . esc($alamat, 'attr') . '" alt="" class="size-8 shrink-0" width="512" height="512" loading="lazy">';
};

// ikon-sprite: users user-round-check banknote calendar-days graduation-cap signpost hourglass chevron-down

// Perlengkapan lomba G9-02 kecil di kartu kelompok umur, mulai layar sm, hanya bila berkasnya ada (F9-19).
$kartuKu = gambar_opsional('beranda/kartu-ku.webp', ['class' => 'pointer-events-none hidden h-16 w-auto shrink-0 select-none sm:block', 'data-hiasan' => 'kartu-ku']);
?>
<section id="lomba" class="scroll-mt-20 bg-white" aria-labelledby="judul-lomba">
    <div class="mx-auto max-w-screen-xl px-4 py-12 sm:py-16">
        <p class="judul-tampilan ornamen-judul" lang="en" aria-hidden="true" data-judul-inggris>Choose Your Race</p>
        <h2 id="judul-lomba" class="judul-keterangan mt-2">Kelompok umur dan nomor lomba</h2>
        <p class="mt-3 max-w-3xl text-lg text-gray-600" data-lomba-pengantar>
            Kelompok umur ditentukan dari tahun lahir atlet.
            <?php if ($tampilkanBiaya) : ?>
                Biaya individu dihitung per atlet untuk tiap nomor yang diikuti; biaya beregu dihitung per regu; satu sekolah boleh mendaftarkan beberapa regu di satu nomor beregu.
            <?php else : ?>
                Rincian biaya ditampilkan di bagian pembayaran saat mendaftar.
            <?php endif ?>
        </p>

        <div class="mt-8 grid items-start gap-5 lg:grid-cols-2">
            <?php foreach ($kelompokUmur as $ku) : ?>
                <?php
                $individu = $gabung($ku['nomor_individu'], static fn (array $n): string => $n['nama']);
                // Biaya ikut menentukan penggabungan hanya bila biaya ditampilkan.
                $beregu = $gabung($ku['nomor_beregu'], static fn (array $n): string => implode('|', [
                    $n['nama'], $n['kategori'] === 'mix' ? 'mix' : 'tunggal',
                    $n['jumlah_inti'] ?? '-', $n['batas_anggota'] ?? '-', $n['jumlah_putra'] ?? '-', $n['jumlah_putri'] ?? '-',
                    $tampilkanBiaya ? ($n['biaya_beregu'] ?? '-') : '',
                ]));
                $jumlahIndividu = count($ku['nomor_individu']);
                $jumlahBeregu   = count($ku['nomor_beregu']);
                $idJudul        = 'judul-lomba-ku-' . $ku['id'];
                ?>
                <article id="lomba-ku-<?= $ku['id'] ?>" class="scroll-mt-24 rounded-2xl border border-gray-200 bg-primary-50/60 p-5 shadow-lembut sm:p-6" aria-labelledby="<?= $idJudul ?>" data-lomba-ku>
                    <?php if ($kartuKu !== '') : ?>
                        <div class="flex items-start justify-between gap-3">
                            <h3 id="<?= $idJudul ?>" class="font-tampilan text-3xl font-extrabold uppercase text-primary-900"><?= esc($ku['nama']) ?></h3>
                            <?= $kartuKu ?>
                        </div>
                    <?php else : ?>
                        <h3 id="<?= $idJudul ?>" class="font-tampilan text-3xl font-extrabold uppercase text-primary-900"><?= esc($ku['nama']) ?></h3>
                    <?php endif ?>

                    <dl class="mt-4 grid gap-3 sm:grid-cols-2" data-lomba-ku-info>
                        <div class="rounded-xl bg-white p-3">
                            <dt class="flex items-center gap-1.5 text-sm font-semibold text-gray-500"><?= ikon('calendar-days', 'size-4') ?> Tahun lahir</dt>
                            <dd class="mt-0.5 font-bold text-gray-900" data-lomba-tahun-lahir><?= esc($ku['tahun_lahir'] !== '' ? $ku['tahun_lahir'] : 'Tahun lahir segera diumumkan') ?></dd>
                        </div>
                        <div class="rounded-xl bg-white p-3">
                            <dt class="flex items-center gap-1.5 text-sm font-semibold text-gray-500"><?= ikon('graduation-cap', 'size-4') ?> Jenjang sekolah</dt>
                            <dd class="mt-0.5 font-bold text-gray-900" data-lomba-jenjang><?= esc($ku['jenjang'] !== '' ? $ku['jenjang'] : 'Jenjang sekolah segera diumumkan') ?></dd>
                        </div>
                        <?php if ($tampilkanBiaya) : ?>
                            <div class="rounded-xl bg-white p-3 sm:col-span-2">
                                <dt class="flex items-center gap-1.5 text-sm font-semibold text-gray-500"><?= ikon('banknote', 'size-4') ?> Biaya individu</dt>
                                <dd class="mt-0.5 font-bold text-oranye-700" data-lomba-biaya-individu><?= esc($teksBiaya($ku['biaya_individu'], 'per atlet per nomor', 'Biaya segera diumumkan')) ?></dd>
                            </div>
                        <?php endif ?>
                    </dl>

                    <?php foreach ($ku['pilihan'] as $teksPilihan) : ?>
                        <p class="mt-3 flex items-start gap-2 rounded-xl bg-langit-100 p-3 text-sm text-primary-900" data-lomba-pilih-ku>
                            <?= ikon('signpost', 'mt-0.5 size-4') ?>
                            <span><?= esc($teksPilihan) ?>, dipilih saat mendaftar.</span>
                        </p>
                    <?php endforeach; ?>

                    <?php if ($jumlahIndividu + $jumlahBeregu === 0) : ?>
                        <p class="mt-4 flex items-center gap-2 text-gray-600" data-lomba-kosong><?= ikon('hourglass', 'size-4') ?> Nomor lomba segera diumumkan.</p>
                    <?php else : ?>
                        <details class="group mt-4 rounded-xl border border-gray-200 bg-white" data-lomba-nomor>
                            <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-2 font-semibold text-primary-800 [&::-webkit-details-marker]:hidden">
                                <span class="flex flex-wrap items-baseline gap-x-2">
                                    <span><span class="group-open:hidden">Lihat nomor lomba</span><span class="hidden group-open:inline">Sembunyikan nomor lomba</span></span>
                                    <span class="text-sm font-normal text-gray-500" data-lomba-nomor-jumlah>
                                        <?= esc(implode(' · ', array_filter([
                                            $jumlahIndividu > 0 ? "{$jumlahIndividu} individu" : '',
                                            $jumlahBeregu > 0 ? "{$jumlahBeregu} beregu" : '',
                                        ]))) ?>
                                    </span>
                                </span>
                                <?= ikon('chevron-down', 'transition-transform group-open:rotate-180') ?>
                            </summary>

                            <div class="space-y-4 border-t border-gray-100 px-4 pb-4 pt-3">
                                <?php if ($individu !== []) : ?>
                                    <div>
                                        <h4 class="text-sm font-bold uppercase tracking-wide text-gray-500">Nomor individu</h4>
                                        <ul class="mt-2 divide-y divide-gray-100" data-lomba-daftar-individu>
                                            <?php foreach ($individu as $butir) : ?>
                                                <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2">
                                                    <?= $gambarNomor($butir['nomor']['nama']) ?>
                                                    <span class="font-semibold text-gray-900"><?= esc($butir['nomor']['nama']) ?></span>
                                                    <span class="flex flex-wrap gap-1"><span class="sr-only">— </span><?= $lencanaKategori($butir['kategori']) ?></span>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                <?php endif ?>

                                <?php if ($beregu !== []) : ?>
                                    <div>
                                        <h4 class="text-sm font-bold uppercase tracking-wide text-gray-500">Nomor beregu</h4>
                                        <ul class="mt-2 divide-y divide-gray-100" data-lomba-daftar-beregu>
                                            <?php foreach ($beregu as $butir) : ?>
                                                <?php
                                                $nomor = $butir['nomor'];
                                                $info  = $keteranganTim($nomor);

                                                if ($tampilkanBiaya) {
                                                    $info[] = ['ikon' => 'banknote', 'teks' => $teksBiaya($nomor['biaya_beregu'], 'per regu', 'Biaya regu segera diumumkan'), 'biaya' => true];
                                                }
                                                ?>
                                                <li class="py-2">
                                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1" data-lomba-beregu-kepala>
                                                        <?= $gambarNomor($nomor['nama']) ?>
                                                        <span class="font-semibold text-gray-900"><?= esc($nomor['nama']) ?></span>
                                                        <span class="flex flex-wrap gap-1"><span class="sr-only">— </span><?= $lencanaKategori($butir['kategori']) ?></span>
                                                    </div>
                                                    <?php if ($info !== []) : ?>
                                                        <ul class="mt-1 space-y-0.5 text-sm text-gray-600" data-lomba-beregu-info>
                                                            <?php foreach ($info as $butirInfo) : ?>
                                                                <li class="flex items-center gap-1.5"<?= isset($butirInfo['biaya']) ? ' data-lomba-biaya-beregu' : '' ?>><?= ikon($butirInfo['ikon'], 'size-4 text-gray-400') ?> <?= esc($butirInfo['teks']) ?></li>
                                                            <?php endforeach ?>
                                                        </ul>
                                                    <?php endif ?>
                                                </li>
                                            <?php endforeach ?>
                                        </ul>
                                    </div>
                                <?php endif ?>
                            </div>
                        </details>
                    <?php endif ?>
                </article>
            <?php endforeach ?>
        </div>
    </div>
</section>
