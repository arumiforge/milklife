<?php
/**
 * Isi rekap PDF (A4 mendatar), diubah mPDF oleh
 * `App\Libraries\Panel\RekapPdf`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema; warnanya sama dengan bukti pendaftaran.
 *
 * @var array{kolom: array<string, array{judul: string, jenis: string}>, baris: list<array<string, mixed>>, jumlah: array<string, mixed>|null} $rekap
 * @var string $judul     'Rekap Per Sekolah'
 *
 * Rekap keuangan ditambah tabel tim tanpa pembayar biaya tim (K68).
 * @var string $status    'Lunas', 'Menunggu', 'Semua status', atau 'Lunas dan Menunggu'
 * @var string $namaAcara nama acara berhuruf besar
 * @var string $seriTahun 'Seri 2 Tahun 2026'
 * @var bool   $logo      logo persegi ada (`var:logo`)
 */

$sel = static function (array $kolom, mixed $nilai): string {
    if ($nilai === null || $nilai === '') {
        return '<td></td>';
    }

    return match ($kolom['jenis']) {
        'rupiah' => '<td class="angka">' . esc(rupiah((int) $nilai)) . '</td>',
        'angka'  => '<td class="angka">' . (int) $nilai . '</td>',
        default  => '<td>' . esc((string) $nilai) . '</td>',
    };
};
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; font-size: 8.5pt; color: #1b2433; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; color: #021F7B; }
    .nama-acara { font-size: 16pt; font-weight: bold; }
    .seri-tahun { font-size: 11pt; font-weight: bold; color: #1b2433; }
    h1 { font-size: 12pt; color: #021F7B; margin: 4mm 0 1mm; }
    .keterangan { color: #5B6475; margin-bottom: 3mm; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.3mm; }
    table.data td { border-bottom: 0.2mm solid #D6DEEA; padding: 1.1mm 1.3mm; vertical-align: top; }
    table.data .angka { text-align: right; white-space: nowrap; }
    table.data tr.jumlah td { font-weight: bold; border-top: 0.4mm solid #021F7B; border-bottom: none; }
    .kosong { color: #5B6475; text-align: center; padding: 8mm 0; }
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
<div class="keterangan">Status: <?= esc($status) ?> · Dicetak <?= esc(tanggal_id(date('Y-m-d H:i:s'), true)) ?></div>

<?php if ($rekap['baris'] === []) : ?>
    <div class="kosong">Belum ada data untuk rekap ini.</div>
<?php else : ?>
    <table class="data">
        <thead>
            <tr>
                <?php foreach ($rekap['kolom'] as $kolom) : ?>
                    <th<?= $kolom['jenis'] === 'teks' ? '' : ' class="angka"' ?>><?= esc($kolom['judul']) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rekap['baris'] as $baris) : ?>
                <tr>
                    <?php foreach ($rekap['kolom'] as $kunci => $kolom) : ?>
                        <?= $sel($kolom, $baris[$kunci] ?? null) ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if ($rekap['jumlah'] !== null) : ?>
                <tr class="jumlah">
                    <?php foreach ($rekap['kolom'] as $kunci => $kolom) : ?>
                        <?= $sel($kolom, $rekap['jumlah'][$kunci] ?? null) ?>
                    <?php endforeach; ?>
                </tr>
            <?php endif ?>
        </tbody>
    </table>
<?php endif ?>

<?php if (isset($rekap['tim_tanpa_pembayar'])) : ?>
    <h1>Tim Tanpa Pembayar Biaya Tim</h1>
    <?php if ($rekap['tim_tanpa_pembayar']['baris'] === []) : ?>
        <div class="keterangan">Semua tim beregu sudah punya pembayar biaya tim.</div>
    <?php else : ?>
        <div class="keterangan">Biaya tim belum ditagih ke pendaftaran mana pun; tagihkan lewat Ubah di salah satu pendaftaran anggotanya.</div>
        <table class="data">
            <thead>
                <tr>
                    <?php foreach ($rekap['tim_tanpa_pembayar']['kolom'] as $kolom) : ?>
                        <th<?= $kolom['jenis'] === 'teks' ? '' : ' class="angka"' ?>><?= esc($kolom['judul']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rekap['tim_tanpa_pembayar']['baris'] as $baris) : ?>
                    <tr>
                        <?php foreach ($rekap['tim_tanpa_pembayar']['kolom'] as $kunci => $kolom) : ?>
                            <?= $sel($kolom, $baris[$kunci] ?? null) ?>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif ?>
<?php endif ?>

</body>
</html>
