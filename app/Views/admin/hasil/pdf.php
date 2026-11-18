<?php
/**
 * Kerangka PDF hasil lomba dan klasemen (A4 tegak), diubah mPDF oleh
 * `App\Libraries\Hasil\DokumenPdf`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema; warnanya sama dengan rekap PDF.
 *
 * @var string $judul
 * @var string $isi       HTML isi (sudah di-escape oleh view isinya)
 * @var string $namaAcara nama acara berhuruf besar
 * @var string $seriTahun 'Seri 2 Tahun 2026'
 * @var bool   $logo      logo persegi ada (`var:logo`)
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
    .nama-acara { font-size: 15pt; font-weight: bold; }
    .seri-tahun { font-size: 11pt; font-weight: bold; color: #1b2433; }
    h1 { font-size: 13pt; color: #021F7B; margin: 4mm 0 1mm; }
    h2 { font-size: 11pt; color: #021F7B; margin: 5mm 0 1.5mm; }
    .keterangan { color: #5B6475; margin-bottom: 2mm; }
    table.data { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.3mm; }
    table.data td { border-bottom: 0.2mm solid #D6DEEA; padding: 1.1mm 1.3mm; vertical-align: top; }
    table.data .angka { text-align: right; white-space: nowrap; }
    table.data .tengah { text-align: center; }
    table.data tr.juara td { font-weight: bold; }
    .kecil { font-size: 7.5pt; color: #5B6475; font-weight: normal; }
    .kosong { color: #5B6475; padding: 4mm 0; }
</style>
</head>
<body>
<table class="kepala" width="100%">
    <tr>
        <td width="24mm"><?php if ($logo) : ?><img src="var:logo" height="20mm"><?php endif ?></td>
        <td class="judul">
            <div class="nama-acara"><?= esc($namaAcara) ?></div>
            <?php if ($seriTahun !== '') : ?><div class="seri-tahun"><?= esc($seriTahun) ?></div><?php endif ?>
        </td>
        <td width="24mm"></td>
    </tr>
</table>
<h1><?= esc($judul) ?></h1>
<div class="keterangan">Dicetak <?= esc(tanggal_id(date('Y-m-d H:i:s'), true)) ?></div>
<?= $isi ?>
</body>
</html>
