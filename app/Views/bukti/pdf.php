<?php
/**
 * Isi bukti pendaftaran PDF (A4 mendatar), diubah mPDF oleh
 * `App\Libraries\BuktiPendaftaran`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema.
 *
 * @var array<string, mixed> $p         satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var string               $namaAcara nama acara berhuruf besar
 * @var string               $seriTahun 'Seri 2 Tahun 2026'
 * @var string|null          $logo      jalur berkas logo persegi, atau null
 */
$dada = static fn (?string $nomor): string => $nomor ?? '–';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; font-size: 9pt; color: #1b2433; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; color: #021F7B; }
    .nama-acara { font-size: 20pt; font-weight: bold; color: #021F7B; }
    .seri-tahun { font-size: 13pt; font-weight: bold; }
    .info { width: 100%; margin-top: 4mm; font-size: 10pt; }
    .info td { padding: 1mm 0; }
    .kanan { text-align: right; }
    h2 { font-size: 10.5pt; color: #021F7B; margin: 5mm 0 1.5mm; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.5mm; font-size: 8.5pt; }
    table.data td { border-bottom: 0.2mm solid #D6DEEA; padding: 1.3mm 1.5mm; vertical-align: top; }
    table.data td.uang, table.data th.uang { text-align: right; white-space: nowrap; }
    table.data tr.jumlah td { font-weight: bold; border-bottom: none; }
    .kosong { color: #5B6475; }
    table.total { margin-top: 5mm; width: 45%; border-collapse: collapse; }
    table.total td { padding: 1.3mm 1.5mm; }
    table.total tr.akhir td { font-size: 11pt; font-weight: bold; color: #021F7B; border-top: 0.4mm solid #021F7B; }
    .status { margin-top: 4mm; font-size: 10.5pt; }
</style>
</head>
<body>

<table class="kepala" width="100%">
    <tr>
        <td width="28mm"><?php if ($logo !== null) : ?><img src="<?= esc($logo) ?>" width="24mm" height="24mm"><?php endif ?></td>
        <td class="judul">
            <div class="nama-acara"><?= esc($namaAcara) ?></div>
            <?php if ($seriTahun !== '') : ?><div class="seri-tahun"><?= esc($seriTahun) ?></div><?php endif ?>
        </td>
        <td width="28mm"></td>
    </tr>
</table>

<table class="info">
    <tr>
        <td>Nama Sekolah : <b><?= esc($p['nama_sekolah']) ?></b></td>
        <td class="kanan">Tanggal Pendaftaran : <b><?= esc(tanggal_id($p['dibuat_pada'])) ?></b></td>
    </tr>
    <tr>
        <td>Kabupaten / Kota : <b><?= esc($p['kabupaten']) ?></b></td>
        <td class="kanan">ID Pendaftaran : <b><?= esc($p['id_pendaftaran']) ?></b></td>
    </tr>
</table>

<h2>Penanggung Jawab</h2>
<table class="data">
    <tr><th width="8mm">No</th><th>Nama</th><th>Nomor WA</th></tr>
    <?php foreach ($p['penanggung_jawab'] as $i => $pj) : ?>
        <tr><td><?= $i + 1 ?></td><td><?= esc($pj['nama']) ?></td><td><?= esc($pj['nomor_wa']) ?></td></tr>
    <?php endforeach ?>
</table>

<h2>Nomor Lomba Individu</h2>
<?php if ($p['individu'] === []) : ?>
    <p class="kosong">Tidak ada nomor individu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <tr>
            <th width="8mm">No</th><th>Nama</th><th>NISN</th><th>Tanggal Lahir</th><th>Kelompok Umur</th><th>Nomor Lomba</th><th>Nomor Dada</th><th class="uang">Biaya</th>
        </tr>
        <?php foreach ($p['individu'] as $i => $baris) : ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($baris['nama']) ?></td>
                <td><?= esc($baris['nisn']) ?></td>
                <td><?= esc(tanggal_id($baris['tanggal_lahir'])) ?></td>
                <td><?= esc($baris['kelompok_umur']) ?></td>
                <td><?= esc($baris['nomor_lomba']) ?></td>
                <td><?= esc($dada($baris['nomor_dada'])) ?></td>
                <td class="uang"><?= esc(rupiah($baris['biaya'])) ?></td>
            </tr>
        <?php endforeach ?>
        <tr class="jumlah"><td colspan="7">Subtotal individu</td><td class="uang"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
    </table>
<?php endif ?>

<h2>Nomor Lomba Beregu</h2>
<?php if ($p['beregu'] === []) : ?>
    <p class="kosong">Tidak ada nomor beregu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <tr><th width="8mm">No</th><th>Nomor Lomba</th><th>Anggota (Nomor Dada)</th><th class="uang">Biaya</th></tr>
        <?php foreach ($p['beregu'] as $i => $tim) : ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($tim['nomor_lomba']) ?></td>
                <td>
                    <?php foreach ($tim['anggota'] as $a) : ?>
                        <?= esc($a['nama']) ?> (<?= esc($dada($a['nomor_dada'])) ?>)<br>
                    <?php endforeach ?>
                </td>
                <td class="uang"><?= $tim['biaya_tim'] === null ? 'Tanpa biaya (tim sudah terdaftar)' : esc(rupiah($tim['biaya_tim'])) ?></td>
            </tr>
        <?php endforeach ?>
        <tr class="jumlah"><td colspan="3">Subtotal beregu</td><td class="uang"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
    </table>
<?php endif ?>

<table class="total" align="right">
    <tr><td>Subtotal individu</td><td class="kanan"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
    <tr><td>Subtotal beregu</td><td class="kanan"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
    <tr class="akhir"><td>Total Pembayaran</td><td class="kanan"><?= esc(rupiah($p['total'])) ?></td></tr>
</table>

<p class="status">Status Pembayaran : <b><?= esc($p['status_label']) ?></b></p>

</body>
</html>
