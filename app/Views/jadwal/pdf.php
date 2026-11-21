<?php
/**
 * Jadwal pertandingan satu hari untuk mPDF (F9-07), A4 tegak
 * (`App\Libraries\Hasil\UnduhanJadwal`). Kolom seperti `sumber/foto-acara/contohjadwal1.jpg`:
 * waktu, nomor, putra/putri, kelompok umur, babak; ditambah lokasi.
 *
 * @var string                     $hari      "Rabu, 15 Juli 2026"
 * @var list<array<string, mixed>> $babak     `JadwalPertandingan::perHari()` satu hari
 * @var string                     $namaAcara nama acara berhuruf besar
 * @var string                     $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                       $logo      logo persegi ada (`var:logo`)
 */

$adaLokasi = in_array(true, array_map(static fn (array $b): bool => $b['lokasi'] !== '', $babak), true);
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
    .hari { text-align: center; font-size: 11pt; font-weight: bold; color: #C4461B; margin-bottom: 4mm; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 2mm; }
    table.data td { border-bottom: 0.2mm solid #B9C3D3; padding: 2.2mm 2mm; vertical-align: top; }
    tr.genap td { background: #F3F6FB; }
    .waktu { font-weight: bold; color: #C4461B; white-space: nowrap; }
    .tebal { font-weight: bold; }
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
<h1>JADWAL PERTANDINGAN</h1>
<div class="hari"><?= esc($hari) ?></div>
<table class="data">
    <thead>
        <tr>
            <th width="22mm">Waktu</th>
            <th>Nomor</th>
            <th width="22mm">Putra/Putri</th>
            <th width="22mm">KU</th>
            <th>Babak</th>
            <?php if ($adaLokasi) : ?><th>Lokasi</th><?php endif ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($babak as $i => $b) : ?>
            <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                <td class="waktu"><?= $b['jam'] === null ? '–' : esc($b['jam']) . ' WIB' ?></td>
                <td class="tebal"><?= esc($b['nomor']) ?></td>
                <td><?= esc($b['jenis_kelamin']) ?></td>
                <td><?= esc($b['kelompok_umur']) ?></td>
                <td><?= esc($b['babak']) ?></td>
                <?php if ($adaLokasi) : ?><td><?= esc($b['lokasi']) ?></td><?php endif ?>
            </tr>
        <?php endforeach ?>
        <?php if ($babak === []) : ?>
            <tr><td colspan="<?= $adaLokasi ? 6 : 5 ?>" class="kosong">Belum ada babak di hari ini.</td></tr>
        <?php endif ?>
    </tbody>
</table>
<div class="catatan">Waktu dalam WIB dan bisa berubah menyesuaikan jalannya lomba. Jadwal terbaru ada di halaman Jadwal situs pendaftaran.</div>
</body>
</html>
