<?php
/**
 * Isi bukti pendaftaran PDF (A4 mendatar), diubah mPDF oleh
 * `App\Libraries\BuktiPendaftaran`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema.
 *
 * @var array<string, mixed> $p         satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var string               $namaAcara nama acara berhuruf besar
 * @var string               $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                 $logo      logo persegi ada (disisipkan `BuktiPendaftaran::pdf()` sebagai PNG transparan, `var:logo`)
 */

// Ikon WhatsApp (Bootstrap Icons, MIT), disisipkan sebagai SVG.
$ikonWa = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="#25D366" d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>');

$lunas = $p['status'] === 'lunas';
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
    .penanggung-jawab { margin-top: 4mm; font-size: 10pt; }
    .status { margin-top: 5mm; font-size: 10.5pt; }
    .status td { vertical-align: middle; }
    .lencana { padding: 1.5mm 4mm; font-weight: bold; border: 0.5mm solid; border-radius: 2mm; }
    .lencana-lunas { color: #0F5132; background: #D1E7DD; border-color: #198754; }
    .lencana-menunggu { color: #664D03; background: #FFF3CD; border-color: #E0A800; }
</style>
</head>
<body>

<table class="kepala" width="100%">
    <tr>
        <td width="28mm"><?php if ($logo) : ?><img src="var:logo" height="24mm"><?php endif ?></td>
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

<p class="penanggung-jawab">Penanggung Jawab (Official) :
    <?php foreach ($p['penanggung_jawab'] as $i => $pj) : ?><?= $i > 0 ? ', ' : '' ?><b><?= esc($pj['nama']) ?></b> (<img src="<?= $ikonWa ?>" width="3.2mm" height="3.2mm" style="vertical-align: middle;"> <?= esc($pj['nomor_wa']) ?>)<?php endforeach ?>
</p>

<h2>Nomor Lomba Individu</h2>
<?php if ($p['individu'] === []) : ?>
    <p class="kosong">Tidak ada nomor individu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <tr>
            <th width="8mm">No</th><th>Nama</th><th>NISN</th><th>Kelompok Umur</th><th>Nomor Lomba</th><th class="uang">Biaya</th>
        </tr>
        <?php foreach ($p['individu'] as $i => $baris) : ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($baris['nama']) ?></td>
                <td><?= esc($baris['nisn']) ?></td>
                <td><?= esc($baris['kelompok_umur']) ?></td>
                <td><?= esc($baris['nomor_lomba']) ?></td>
                <td class="uang"><?= esc(rupiah($baris['biaya'])) ?></td>
            </tr>
        <?php endforeach ?>
        <tr class="jumlah"><td colspan="5">Subtotal individu</td><td class="uang"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
    </table>
<?php endif ?>

<h2>Nomor Lomba Beregu</h2>
<?php if ($p['beregu'] === []) : ?>
    <p class="kosong">Tidak ada nomor beregu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <tr><th width="8mm">No</th><th>Nomor Lomba</th><th>Anggota</th><th class="uang">Biaya</th></tr>
        <?php foreach ($p['beregu'] as $i => $tim) : ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($tim['nomor_lomba']) ?></td>
                <td><?= esc(implode(', ', array_column($tim['anggota'], 'nama'))) ?></td>
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

<table class="status">
    <tr>
        <td>Status Pembayaran :&nbsp;</td>
        <td class="lencana <?= $lunas ? 'lencana-lunas' : 'lencana-menunggu' ?>"><?= esc($p['status_label']) ?></td>
    </tr>
</table>

</body>
</html>
