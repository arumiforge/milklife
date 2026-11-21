<?php
/**
 * Klasemen sekolah untuk mPDF (F8-05), A4 tegak, satu tabel per jenjang
 * (`App\Libraries\Hasil\UnduhanKlasemen`).
 *
 * @var array<string, array<string, mixed>> $klasemen   `Klasemen::hitung()`
 * @var bool                                $publik     hanya nomor terbit
 * @var string                              $keterangan `Klasemen::keterangan()` sesuai metode
 * @var string                              $namaAcara nama acara berhuruf besar
 * @var string                              $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                                $logo      logo persegi ada (`var:logo`)
 */
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; font-size: 9pt; color: #1b2433; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; color: #021F7B; }
    .nama-acara { font-size: 14pt; font-weight: bold; }
    .seri-tahun { font-size: 10pt; font-weight: bold; color: #1b2433; }
    h1 { font-size: 13pt; color: #021F7B; margin: 4mm 0 1mm; }
    h2 { font-size: 11pt; color: #021F7B; margin: 5mm 0 1.5mm; }
    .keterangan { color: #5B6475; margin-bottom: 2mm; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.5mm; }
    table.data td { border: 0.2mm solid #B9C3D3; padding: 1.8mm 1.5mm; }
    tr.juara td { background: #FFF4E5; font-weight: bold; }
    .tengah { text-align: center; }
    .kosong { color: #5B6475; text-align: center; padding: 6mm 0; }
</style>
</head>
<body>
<table class="kepala" width="100%">
    <tr>
        <td width="20mm"><?php if ($logo) : ?><img src="var:logo" height="16mm"><?php endif ?></td>
        <td class="judul">
            <div class="nama-acara"><?= esc($namaAcara) ?></div>
            <?php if ($seriTahun !== '') : ?><div class="seri-tahun"><?= esc($seriTahun) ?></div><?php endif ?>
        </td>
        <td width="20mm"></td>
    </tr>
</table>
<h1>Klasemen Sekolah</h1>
<div class="keterangan">
    <?= esc($keterangan) ?> Dihitung dari babak Final<?= $publik ? ' nomor yang hasilnya sudah diterbitkan' : ' semua nomor, termasuk yang belum diterbitkan' ?>.
</div>
<?php if ($klasemen === []) : ?>
    <div class="kosong">Belum ada juara.</div>
<?php endif ?>
<?php foreach ($klasemen as $jenjang) : ?>
    <h2><?= esc($jenjang['label']) ?></h2>
    <table class="data">
        <thead>
            <tr>
                <th width="12mm">Per.</th>
                <th>Sekolah</th>
                <th width="16mm" class="tengah">Juara I</th>
                <th width="16mm" class="tengah">Juara II</th>
                <th width="16mm" class="tengah">Juara III</th>
                <th width="16mm" class="tengah">Jumlah</th>
                <th width="16mm" class="tengah">Poin</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($jenjang['baris'] as $b) : ?>
                <tr<?= $b['juara_umum'] !== null ? ' class="juara"' : '' ?>>
                    <td class="tengah"><?= $b['peringkat'] ?></td>
                    <td><?= esc($b['sekolah']) ?><?= $b['juara_umum'] !== null ? ' (Juara umum ' . str_repeat('I', $b['juara_umum']) . ')' : '' ?></td>
                    <td class="tengah"><?= $b['emas'] ?></td>
                    <td class="tengah"><?= $b['perak'] ?></td>
                    <td class="tengah"><?= $b['perunggu'] ?></td>
                    <td class="tengah"><?= $b['jumlah'] ?></td>
                    <td class="tengah"><?= $b['poin'] ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
<?php endforeach ?>
</body>
</html>
