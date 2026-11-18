<?php
/**
 * Nomor atlet (BIB) massal untuk mPDF (`App\Libraries\HariLomba\CetakBib`,
 * F8-03). A4 tegak tanpa margin; tiap BIB setengah halaman mendatar
 * (210 × 148,5 mm), dua per halaman, dengan garis potong putus-putus di
 * tengah. Kotak BIB memakai posisi tetap supaya ukurannya pasti.
 *
 * @var list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}> $atlet
 * @var string $namaAcara nama acara berhuruf besar
 * @var string $seriTahun 'Seri 2 Tahun 2026'
 * @var bool   $logo      logo persegi ada (`var:logo`)
 */

$halaman = array_chunk($atlet, 2);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; color: #1b2433; }
    .bib { position: absolute; left: 10mm; width: 190mm; height: 128mm; }
    .bib-atas { top: 10mm; }
    .bib-bawah { top: 158.5mm; }
    .potong { position: absolute; left: 0; top: 148.5mm; width: 210mm; border-top: 0.3mm dashed #8a94a6; }
    table.kepala { width: 100%; border-bottom: 0.6mm solid #021F7B; }
    table.kepala td { vertical-align: middle; padding-bottom: 2mm; }
    .acara { font-size: 13pt; font-weight: bold; color: #021F7B; }
    .seri { font-size: 10pt; color: #5B6475; }
    .ku { font-size: 20pt; font-weight: bold; color: #ffffff; background-color: #021F7B; text-align: center; padding: 2mm 4mm; }
    .nomor { font-size: 180pt; font-weight: bold; text-align: center; line-height: 1; color: #000000; padding-top: 10mm; padding-bottom: 4mm; }
    .nama { font-size: 24pt; font-weight: bold; text-align: center; }
    .sekolah { font-size: 15pt; text-align: center; color: #33415a; padding-top: 1mm; }
    .kosong { font-size: 14pt; text-align: center; padding-top: 40mm; color: #5B6475; }
</style>
</head>
<body>
<?php if ($atlet === []) : ?>
    <div class="kosong">Belum ada atlet dengan nomor atlet (BIB) dari pendaftaran Lunas.</div>
<?php endif ?>
<?php foreach ($halaman as $nomorHalaman => $isi) : ?>
    <?php if ($nomorHalaman > 0) : ?><pagebreak /><?php endif ?>
    <?php foreach ($isi as $urutan => $a) : ?>
        <div class="bib <?= $urutan === 0 ? 'bib-atas' : 'bib-bawah' ?>">
            <table class="kepala">
                <tr>
                    <?php if ($logo) : ?><td width="18mm"><img src="var:logo" height="15mm"></td><?php endif ?>
                    <td>
                        <div class="acara"><?= esc($namaAcara) ?></div>
                        <?php if ($seriTahun !== '') : ?><div class="seri"><?= esc($seriTahun) ?></div><?php endif ?>
                    </td>
                    <td width="40mm" class="ku"><?= esc($a['kelompok_umur']) ?></td>
                </tr>
            </table>
            <div class="nomor"><?= esc($a['nomor_dada']) ?></div>
            <div class="nama"><?= esc(mb_strtoupper($a['nama'])) ?></div>
            <div class="sekolah"><?= esc($a['sekolah']) ?></div>
        </div>
    <?php endforeach ?>
    <div class="potong"></div>
<?php endforeach ?>
</body>
</html>
