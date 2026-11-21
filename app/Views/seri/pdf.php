<?php
/**
 * Daftar seri dan lintasan (*start list*) untuk mPDF (F9-08), A4 tegak
 * (`App\Libraries\Hasil\UnduhanSeri`). Tiap babak satu bagian, tiap seri satu
 * tabel: lintasan (atau urutan), nomor atlet, nama, sekolah, dan kolom
 * prestasi kosong untuk juri.
 *
 * @var string                     $judul     nomor atau hari
 * @var list<array<string, mixed>> $daftar    `SeriLintasan::untukUnduhan()`
 * @var string                     $namaAcara nama acara berhuruf besar
 * @var string                     $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                       $logo      logo persegi ada (`var:logo`)
 */

use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\UnduhanSeri;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; font-size: 10pt; color: #1b2433; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; color: #021F7B; }
    .nama-acara { font-size: 14pt; font-weight: bold; }
    .seri-tahun { font-size: 10pt; font-weight: bold; color: #1b2433; }
    h1 { font-size: 15pt; color: #021F7B; margin: 5mm 0 1mm; text-align: center; }
    .subjudul { text-align: center; font-size: 11pt; font-weight: bold; color: #C4461B; margin-bottom: 3mm; }
    h2 { font-size: 12pt; color: #021F7B; margin: 6mm 0 1mm; }
    .info { color: #5B6475; font-size: 9pt; margin-bottom: 2mm; }
    .belum { color: #9A3412; font-weight: bold; }
    h3 { font-size: 10.5pt; color: #C4461B; margin: 3mm 0 1mm; }
    table.data { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.6mm 2mm; }
    table.data td { border-bottom: 0.2mm solid #B9C3D3; padding: 2mm; vertical-align: top; }
    tr.genap td { background: #F3F6FB; }
    .lintasan { font-weight: bold; font-size: 12pt; text-align: center; color: #021F7B; }
    .tebal { font-weight: bold; }
    .anggota { color: #5B6475; font-size: 8pt; }
    .isian { border-bottom: 0.2mm solid #5B6475; }
    .kosong { color: #5B6475; text-align: center; padding: 6mm 0; }
    .catatan { color: #5B6475; font-size: 8pt; margin-top: 4mm; }
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
<h1>DAFTAR SERI DAN LINTASAN</h1>
<div class="subjudul"><?= esc($judul) ?></div>

<?php if ($daftar === []) : ?>
    <p class="kosong">Belum ada babak yang dibagi ke seri.</p>
<?php endif ?>

<?php foreach ($daftar as $d) : ?>
    <?php
    $b        = $d['babak'];
    $lintasan = $b['aturan']['lintasan'];
    $jadwal   = UnduhanSeri::jadwal($b);
    $lolos    = SeriLintasan::teksLolos($d['lolos'], (string) $b['nomor']['jenis_hasil']);
    $beregu   = $b['nomor']['jenis'] === 'beregu';
    ?>
    <h2><?= esc($b['nomor']['nama']) ?> – <?= esc($b['nomor']['kelompok_umur']) ?> · <?= esc($b['nama']) ?></h2>
    <div class="info">
        <?= $jadwal === '' ? 'Jadwal menyusul' : esc($jadwal) ?>
        <?php if ($lolos !== '') : ?> · Lolos ke <?= esc($b['berikut']['nama'] ?? 'babak berikutnya') ?>: <?= esc($lolos) ?><?php endif ?>
        <?php if (! $b['dikunci']) : ?> · <span class="belum">Belum dikunci, bisa berubah</span><?php endif ?>
    </div>
    <?php foreach ($d['seri'] as $seri => $peserta) : ?>
        <h3><?= $seri === 0 ? 'Belum dibagi' : ($lintasan ? 'Seri ' . $seri : 'Kelompok ' . $seri) ?> (<?= count($peserta) ?> <?= $beregu ? 'regu' : 'atlet' ?>)</h3>
        <table class="data">
            <thead>
                <tr>
                    <th width="16mm"><?= $lintasan ? 'Lintasan' : 'Urutan' ?></th>
                    <?php if (! $beregu) : ?><th width="20mm">No. Atlet</th><?php endif ?>
                    <th><?= $beregu ? 'Regu' : 'Nama' ?></th>
                    <th>Sekolah</th>
                    <th width="28mm">Prestasi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($peserta as $i => $p) : ?>
                    <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                        <td class="lintasan"><?= $p['lintasan'] === null ? '–' : $p['lintasan'] ?></td>
                        <?php if (! $beregu) : ?><td class="tebal"><?= esc((string) ($p['nomor_dada'] ?? '–')) ?></td><?php endif ?>
                        <td>
                            <span class="tebal"><?= esc($p['nama']) ?></span>
                            <?php if ($p['anggota'] !== []) : ?><br><span class="anggota"><?= esc(implode(', ', $p['anggota'])) ?></span><?php endif ?>
                        </td>
                        <td><?= esc($p['sekolah']) ?></td>
                        <td class="isian"><?= esc($p['prestasi_teks']) ?></td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    <?php endforeach ?>
<?php endforeach ?>

<div class="catatan">Q = lolos sebagai n terbaik tiap seri, q = lolos dari prestasi terbaik berikutnya. Daftar terbaru ada di panel admin dan halaman Jadwal situs pendaftaran.</div>
</body>
</html>
