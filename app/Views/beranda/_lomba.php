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
 * - Gaya hanya di wilayah "Bagian lomba (F3-02)" di `tema.css`.
 *
 * Susunan: satu kartu per kelompok umur (`#lomba-ku-<id>`). Tahun lahir,
 * jenjang, dan biaya individu (bila ditampilkan) selalu terlihat; daftar nomornya dilipat dalam
 * `<details>` (asumsi rencana 5) supaya halaman tidak terlalu panjang di
 * ponsel, tanpa tabel lebar dan tanpa skrip.
 *
 * Nomor dengan nama dan aturan yang sama digabung menjadi satu baris
 * (misalnya "Lari 60 m — Putra, Putri"); kategorinya berurutan seperti
 * nomornya di panel admin.
 */

if ($kelompokUmur === []) {
    return;
}

$tampilkanBiaya = (bool) ($tampilkanBiaya ?? false);

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

    $anggota = match (true) {
        $inti !== null && $batas !== null && $batas > $inti => "{$inti} atlet inti, paling banyak {$batas} anggota",
        $inti !== null                                       => "{$inti} atlet per tim",
        $batas !== null                                      => "Paling banyak {$batas} anggota per tim",
        default                                              => null,
    };

    $komposisi = match (true) {
        $nomor['jumlah_putra'] !== null && $nomor['jumlah_putri'] !== null => "Minimal {$nomor['jumlah_putra']} putra dan {$nomor['jumlah_putri']} putri",
        $nomor['jumlah_putra'] !== null                                     => "Minimal {$nomor['jumlah_putra']} putra",
        $nomor['jumlah_putri'] !== null                                     => "Minimal {$nomor['jumlah_putri']} putri",
        default                                                             => null,
    };

    return array_values(array_filter([
        ['ikon' => 'people', 'teks' => $anggota],
        ['ikon' => 'gender-ambiguous', 'teks' => $komposisi],
    ], static fn (array $butir): bool => $butir['teks'] !== null));
};
?>
<section id="lomba" class="bagian-lomba" aria-labelledby="judul-lomba">
    <div class="container py-5">
        <h2 id="judul-lomba" class="lomba-judul">Kelompok umur dan nomor lomba</h2>
        <p class="lomba-pengantar">
            Kelompok umur ditentukan dari tahun lahir atlet.
            <?php if ($tampilkanBiaya) : ?>
                Biaya individu dihitung per atlet untuk tiap nomor yang diikuti; biaya beregu dihitung per tim.
            <?php else : ?>
                Rincian biaya ditampilkan di bagian pembayaran saat mendaftar.
            <?php endif ?>
        </p>

        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 align-items-start">
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
                <div class="col">
                    <article id="lomba-ku-<?= $ku['id'] ?>" class="lomba-ku" aria-labelledby="<?= $idJudul ?>">
                        <h3 id="<?= $idJudul ?>" class="lomba-ku-nama"><?= esc($ku['nama']) ?></h3>

                        <dl class="lomba-ku-info">
                            <div>
                                <dt><i class="bi bi-calendar3" aria-hidden="true"></i> Tahun lahir</dt>
                                <dd class="lomba-tahun-lahir"><?= esc($ku['tahun_lahir'] !== '' ? $ku['tahun_lahir'] : 'Tahun lahir segera diumumkan') ?></dd>
                            </div>
                            <div>
                                <dt><i class="bi bi-mortarboard" aria-hidden="true"></i> Jenjang sekolah</dt>
                                <dd class="lomba-jenjang"><?= esc($ku['jenjang'] !== '' ? $ku['jenjang'] : 'Jenjang sekolah segera diumumkan') ?></dd>
                            </div>
                            <?php if ($tampilkanBiaya) : ?>
                                <div>
                                    <dt><i class="bi bi-cash-coin" aria-hidden="true"></i> Biaya individu</dt>
                                    <dd class="lomba-biaya-individu"><?= esc($teksBiaya($ku['biaya_individu'], 'per atlet per nomor', 'Biaya segera diumumkan')) ?></dd>
                                </div>
                            <?php endif ?>
                        </dl>

                        <?php if ($jumlahIndividu + $jumlahBeregu === 0) : ?>
                            <p class="lomba-kosong"><i class="bi bi-hourglass-split" aria-hidden="true"></i> Nomor lomba segera diumumkan.</p>
                        <?php else : ?>
                            <details class="lomba-nomor">
                                <summary>
                                    <span class="lomba-nomor-ringkas">
                                        <span class="lomba-nomor-label"><span class="lomba-nomor-buka">Lihat nomor lomba</span><span class="lomba-nomor-tutup">Sembunyikan nomor lomba</span></span>
                                        <span class="lomba-nomor-jumlah">
                                            <?= esc(implode(' · ', array_filter([
                                                $jumlahIndividu > 0 ? "{$jumlahIndividu} individu" : '',
                                                $jumlahBeregu > 0 ? "{$jumlahBeregu} beregu" : '',
                                            ]))) ?>
                                        </span>
                                    </span>
                                    <i class="bi bi-chevron-down lomba-nomor-panah" aria-hidden="true"></i>
                                </summary>

                                <?php if ($individu !== []) : ?>
                                    <h4 class="lomba-nomor-judul">Nomor individu</h4>
                                    <ul class="lomba-daftar lomba-daftar-individu">
                                        <?php foreach ($individu as $butir) : ?>
                                            <li>
                                                <span class="lomba-nama-nomor"><?= esc($butir['nomor']['nama']) ?></span>
                                                <span class="lomba-kategori"><span class="visually-hidden">— </span><?= esc(implode(', ', $butir['kategori'])) ?></span>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                <?php endif ?>

                                <?php if ($beregu !== []) : ?>
                                    <h4 class="lomba-nomor-judul">Nomor beregu</h4>
                                    <ul class="lomba-daftar lomba-daftar-beregu">
                                        <?php foreach ($beregu as $butir) : ?>
                                            <?php
                                            $nomor = $butir['nomor'];
                                            $info  = $keteranganTim($nomor);

                                            if ($tampilkanBiaya) {
                                                $info[] = ['ikon' => 'cash-coin', 'teks' => $teksBiaya($nomor['biaya_beregu'], 'per tim', 'Biaya tim segera diumumkan'), 'kelas' => 'lomba-biaya-beregu'];
                                            }
                                            ?>
                                            <li>
                                                <div class="lomba-beregu-kepala">
                                                    <span class="lomba-nama-nomor"><?= esc($nomor['nama']) ?></span>
                                                    <span class="lomba-kategori"><span class="visually-hidden">— </span><?= esc(implode(', ', $butir['kategori'])) ?></span>
                                                </div>
                                                <?php if ($info !== []) : ?>
                                                    <ul class="lomba-beregu-info">
                                                        <?php foreach ($info as $butirInfo) : ?>
                                                            <li<?= isset($butirInfo['kelas']) ? ' class="' . $butirInfo['kelas'] . '"' : '' ?>><i class="bi bi-<?= $butirInfo['ikon'] ?>" aria-hidden="true"></i> <?= esc($butirInfo['teks']) ?></li>
                                                        <?php endforeach ?>
                                                    </ul>
                                                <?php endif ?>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                <?php endif ?>
                            </details>
                        <?php endif ?>
                    </article>
                </div>
            <?php endforeach ?>
        </div>
    </div>
</section>
