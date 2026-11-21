<?php
/**
 * Hasil satu nomor lomba untuk mPDF (F8-04), A4 tegak, semua babak berurutan
 * dengan Final paling akhir (`App\Libraries\Hasil\UnduhanHasil`).
 *
 * @var array<string, mixed>       $nomor     `HasilLomba::nomor()`
 * @var list<array<string, mixed>> $babak     `HasilLomba::semuaBabak()`
 * @var string                     $namaAcara nama acara berhuruf besar
 * @var string                     $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                       $logo      logo persegi ada (`var:logo`)
 */

use App\Libraries\Hasil\JenisHasil;

$beregu = $nomor['jenis'] === 'beregu';
$kolom  = $beregu ? 4 : 6;
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
    table.data td { border: 0.2mm solid #B9C3D3; padding: 1.8mm 1.5mm; vertical-align: top; }
    tr.juara td { background: #FFF4E5; }
    .tengah { text-align: center; }
    .tebal { font-weight: bold; }
    .anggota { color: #5B6475; font-size: 8pt; }
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
<h1>Hasil <?= esc($nomor['nama']) ?> – <?= esc($nomor['kelompok_umur']) ?></h1>
<div class="keterangan">Jenis hasil: <?= esc(JenisHasil::LABEL[$nomor['jenis_hasil']]) ?>. DNS = tidak start, DNF = tidak finis, DQ = diskualifikasi.</div>
<?php if ($babak === []) : ?>
    <div class="kosong">Belum ada babak.</div>
<?php endif ?>
<?php foreach ($babak as $b) : ?>
    <h2><?= esc($b['nama']) ?><?= $b['final'] ? ' (Final)' : '' ?></h2>
    <table class="data">
        <thead>
            <tr>
                <th width="12mm">Per.</th>
                <?php if (! $beregu) : ?><th width="17mm">No. Atlet</th><?php endif ?>
                <th><?= $beregu ? 'Regu' : 'Nama' ?></th>
                <?php if (! $beregu) : ?><th>Sekolah</th><?php endif ?>
                <th width="<?= $nomor['jenis_hasil'] === JenisHasil::MULTI ? '38mm' : '24mm' ?>">Prestasi</th>
                <th width="18mm">Ket.</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($b['hasil'] as $h) : ?>
                <tr<?= $b['final'] && $h['peringkat'] !== null && $h['peringkat'] <= 3 ? ' class="juara"' : '' ?>>
                    <td class="tengah tebal"><?= $h['peringkat'] ?? '' ?></td>
                    <?php if (! $beregu) : ?><td class="tebal"><?= esc((string) $h['nomor_dada']) ?></td><?php endif ?>
                    <td>
                        <?= esc($h['nama']) ?>
                        <?php if ($beregu && $h['anggota'] !== []) : ?><div class="anggota"><?= esc(implode(', ', $h['anggota'])) ?></div><?php endif ?>
                    </td>
                    <?php if (! $beregu) : ?><td><?= esc($h['sekolah']) ?></td><?php endif ?>
                    <td>
                        <?= esc($h['prestasi_teks']) ?>
                        <?php foreach ($h['rincian'] ?? [] as $r) : ?><div class="anggota"><?= esc($r['nama']) ?> <?= $r['prestasi'] === '' ? '– (0)' : esc($r['prestasi']) . ' (' . $r['poin'] . ')' ?></div><?php endforeach ?>
                    </td>
                    <td><?= esc((string) $h['keterangan']) ?></td>
                </tr>
            <?php endforeach ?>
            <?php if ($b['hasil'] === []) : ?>
                <tr><td colspan="<?= $kolom ?>" class="kosong">Belum ada peserta.</td></tr>
            <?php endif ?>
        </tbody>
    </table>
<?php endforeach ?>
</body>
</html>
