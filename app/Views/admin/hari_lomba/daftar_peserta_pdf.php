<?php
/**
 * Daftar peserta per nomor lomba untuk mPDF (F8-03), A4 tegak, satu nomor
 * per halaman (`App\Libraries\HariLomba\DaftarPesertaPdf`). Kolom: No, No.
 * Atlet, nama, sekolah, (peran untuk beregu), hadir, dan kolom kosong untuk
 * catatan juri. Nomor beregu ditulis per regu, inti dulu lalu cadangan.
 *
 * @var list<array<string, mixed>> $semua     hasil `DaftarPeserta::susun()`
 * @var string                     $namaAcara nama acara berhuruf besar
 * @var string                     $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                       $logo      logo persegi ada (`var:logo`)
 */

$barisPeserta = static function (int $no, array $p, bool $beregu): string {
    return '<tr>'
        . '<td class="tengah">' . $no . '</td>'
        . '<td class="bib">' . esc((string) ($p['nomor_dada'] ?? '')) . '</td>'
        . '<td>' . esc($p['nama']) . '</td>'
        . '<td>' . esc($p['sekolah']) . '</td>'
        . ($beregu ? '<td>' . esc((string) ($p['peran'] ?? '')) . '</td>' : '')
        . '<td class="tengah">' . ($p['hadir'] ? '&#10003;' : '&#9744;') . '</td>'
        . '<td></td>'
        . '</tr>';
};
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
    .keterangan { color: #5B6475; margin-bottom: 3mm; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.5mm; }
    table.data td { border: 0.2mm solid #B9C3D3; padding: 2mm 1.5mm; vertical-align: top; }
    table.data td.regu { background: #E8EEF8; font-weight: bold; color: #021F7B; }
    .tengah { text-align: center; }
    .bib { font-weight: bold; white-space: nowrap; }
    .kosong { color: #5B6475; text-align: center; padding: 8mm 0; }
</style>
</head>
<body>
<?php if ($semua === []) : ?>
    <div class="kosong">Belum ada nomor lomba.</div>
<?php endif ?>
<?php foreach ($semua as $ke => $daftar) : ?>
    <?php
    $beregu = $daftar['jenis'] === 'beregu';
    $jumlah = $beregu ? count($daftar['regu']) . ' regu' : count($daftar['peserta']) . ' atlet';
    ?>
    <?php if ($ke > 0) : ?><pagebreak /><?php endif ?>
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
    <h1>Daftar Peserta <?= esc($daftar['nama']) ?> – <?= esc($daftar['kelompok_umur']) ?></h1>
    <div class="keterangan"><?= esc($jumlah) ?> dari pendaftaran Lunas. Tanda &#10003; berarti sudah hadir saat daftar ulang.</div>
    <table class="data">
        <thead>
            <tr>
                <th width="9mm">No</th>
                <th width="17mm">No. Atlet</th>
                <th>Nama</th>
                <th>Sekolah</th>
                <?php if ($beregu) : ?><th width="20mm">Peran</th><?php endif ?>
                <th width="13mm">Hadir</th>
                <th width="<?= $beregu ? '32mm' : '40mm' ?>">Catatan juri</th>
            </tr>
        </thead>
        <tbody>
            <?php if (! $beregu) : ?>
                <?php foreach ($daftar['peserta'] as $i => $p) : ?>
                    <?= $barisPeserta($i + 1, $p, false) ?>
                <?php endforeach ?>
            <?php else : ?>
                <?php foreach ($daftar['regu'] as $regu) : ?>
                    <tr><td class="regu" colspan="7"><?= esc($regu['nama']) ?></td></tr>
                    <?php foreach ($regu['anggota'] as $i => $p) : ?>
                        <?= $barisPeserta($i + 1, $p, true) ?>
                    <?php endforeach ?>
                <?php endforeach ?>
            <?php endif ?>
            <?php if ($daftar['peserta'] === [] && $daftar['regu'] === []) : ?>
                <tr><td colspan="<?= $beregu ? 7 : 6 ?>" class="kosong">Belum ada peserta dari pendaftaran Lunas.</td></tr>
            <?php endif ?>
        </tbody>
    </table>
<?php endforeach ?>
</body>
</html>
