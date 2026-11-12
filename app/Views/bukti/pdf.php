<?php
/**
 * Isi bukti pendaftaran PDF (F4 tegak, K77), diubah mPDF oleh
 * `App\Libraries\BuktiPendaftaran`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema.
 *
 * Susunan hemat tempat untuk data banyak: kepala (logo kiri, judul tengah,
 * kode QR kanan), kotak info ringkas, tabel nomor individu satu baris per
 * atlet (nomor lombanya digabung), tabel nomor beregu, lalu status dan total
 * berdampingan. Kepala tabel diulang di setiap halaman; nomor halaman di kaki
 * (`BuktiPendaftaran::pdf()`).
 *
 * @var array<string, mixed> $p         satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var string               $namaAcara nama acara berhuruf besar
 * @var string               $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                 $logo      logo persegi ada (`var:logo`, PNG transparan)
 *
 * Kode QR berisi alamat bukti ini, disisipkan `BuktiPendaftaran::pdf()` sebagai `var:qr`.
 */

// Ikon WhatsApp (Bootstrap Icons, MIT), disisipkan sebagai SVG.
$ikonWa = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="#25D366" d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>');

$lunas = $p['status'] === 'lunas';

// Stempel status (K78): bingkai ganda miring, hijau "LUNAS" atau oranye "BELUM LUNAS", sebagai SVG.
$warnaStempel = $lunas ? '#198754' : '#C4461B';
$stempel      = 'data:image/svg+xml;base64,' . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="120" viewBox="0 0 240 120">'
    . '<g transform="rotate(-7 120 60)">'
    . '<rect x="14" y="20" width="212" height="80" rx="12" fill="none" stroke="' . $warnaStempel . '" stroke-width="4.5"/>'
    . '<rect x="22" y="28" width="196" height="64" rx="7" fill="none" stroke="' . $warnaStempel . '" stroke-width="1.6"/>'
    . '<text x="120" y="66" font-family="dejavusans" font-weight="bold" font-size="' . ($lunas ? 34 : 22) . '" fill="' . $warnaStempel . '" text-anchor="middle">' . ($lunas ? 'LUNAS' : 'BELUM LUNAS') . '</text>'
    . '<text x="120" y="84" font-family="dejavusans" font-weight="bold" font-size="10.5" fill="' . $warnaStempel . '" text-anchor="middle">' . ($lunas ? 'PEMBAYARAN DITERIMA' : 'MENUNGGU KONFIRMASI') . '</text>'
    . '</g></svg>',
);

// Nomor individu digabung per atlet: satu baris per atlet, nomor dipisah koma, biaya dijumlah.
$perAtlet = [];

foreach ($p['individu'] as $baris) {
    $perAtlet[$baris['nisn']] ??= ['nama' => $baris['nama'], 'nisn' => $baris['nisn'], 'kelompok_umur' => $baris['kelompok_umur'], 'nomor' => [], 'biaya' => null];
    $perAtlet[$baris['nisn']]['nomor'][] = $baris['nomor_lomba'];

    if ($baris['biaya'] !== null) {
        $perAtlet[$baris['nisn']]['biaya'] = ($perAtlet[$baris['nisn']]['biaya'] ?? 0) + $baris['biaya'];
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    body { font-family: dejavusans; font-size: 8.5pt; color: #1b2433; }
    .kepala { width: 100%; border-bottom: 0.8mm solid #E85F31; padding-bottom: 3mm; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; }
    .label-bukti { font-size: 8pt; font-weight: bold; color: #C4461B; letter-spacing: 1.5pt; }
    .nama-acara { font-size: 17pt; font-weight: bold; color: #021F7B; }
    .seri-tahun { font-size: 10.5pt; font-weight: bold; color: #234987; }

    table.info { width: 100%; margin-top: 4mm; border-collapse: separate; border-spacing: 1.2mm; }
    table.info td { background: #F3F6FB; border-left: 0.8mm solid #9BCCEC; padding: 1.6mm 2.2mm; vertical-align: top; }
    .info-label { font-size: 6.5pt; color: #5B6475; text-transform: uppercase; letter-spacing: 0.4pt; }
    .info-isi { font-size: 9.5pt; font-weight: bold; color: #021F7B; }
    .info-isi-biasa { font-size: 8.5pt; color: #1b2433; }

    h2 { font-size: 9.5pt; color: #021F7B; margin: 4mm 0 1.5mm; padding-left: 2mm; border-left: 1mm solid #E85F31; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.3mm 1.5mm; font-size: 7.5pt; font-weight: bold; }
    table.data td { border-bottom: 0.15mm solid #D6DEEA; padding: 1.1mm 1.5mm; vertical-align: top; }
    table.data tr.genap td { background: #F7F9FC; }
    table.data td.uang, table.data th.uang { text-align: right; white-space: nowrap; }
    table.data td.no { color: #5B6475; text-align: right; }
    table.data tr.jumlah td { font-weight: bold; color: #021F7B; border-bottom: none; border-top: 0.3mm solid #021F7B; background: #ffffff; }
    .kecil { font-size: 7.5pt; color: #5B6475; }
    .kosong { color: #5B6475; font-style: italic; margin: 0; }

    table.akhir { width: 100%; margin-top: 8mm; }
    table.akhir td { vertical-align: top; }
    td.stempel { padding-right: 8mm; }
    .catatan { font-size: 7.5pt; line-height: 1.5; color: #5B6475; margin-top: 4mm; }
    table.total { width: 100%; border-collapse: collapse; }
    table.total td { padding: 1mm 1.5mm; }
    table.total tr.akhir td { font-size: 11pt; font-weight: bold; color: #ffffff; background: #021F7B; padding: 1.8mm 2mm; }
</style>
</head>
<body>

<table class="kepala">
    <tr>
        <td width="26mm"><?php if ($logo) : ?><img src="var:logo" height="22mm"><?php endif ?></td>
        <td class="judul">
            <div class="label-bukti">BUKTI PENDAFTARAN</div>
            <div class="nama-acara"><?= esc($namaAcara) ?></div>
            <?php if ($seriTahun !== '') : ?><div class="seri-tahun"><?= esc($seriTahun) ?></div><?php endif ?>
        </td>
        <td width="26mm" style="text-align: right;">
            <img src="var:qr" width="24mm" height="24mm">
        </td>
    </tr>
</table>

<table class="info">
    <tr>
        <td width="34%"><div class="info-label">ID Pendaftaran</div><div class="info-isi"><?= esc($p['id_pendaftaran']) ?></div></td>
        <td width="33%"><div class="info-label">Tanggal Pendaftaran</div><div class="info-isi"><?= esc(tanggal_id($p['dibuat_pada'])) ?></div></td>
        <td width="33%"><div class="info-label">Status Pembayaran</div><div class="info-isi"><?= esc($p['status_label']) ?></div></td>
    </tr>
    <tr>
        <td colspan="2"><div class="info-label">Nama Sekolah</div><div class="info-isi"><?= esc($p['nama_sekolah']) ?></div></td>
        <td><div class="info-label">Kabupaten / Kota</div><div class="info-isi"><?= esc($p['kabupaten']) ?></div></td>
    </tr>
    <tr>
        <td colspan="3">
            <div class="info-label">Penanggung Jawab (Official)</div>
            <div class="info-isi-biasa"><?php foreach ($p['penanggung_jawab'] as $i => $pj) : ?><?= $i > 0 ? ', ' : '' ?><b><?= esc($pj['nama']) ?></b> (<img src="<?= $ikonWa ?>" width="3mm" height="3mm" style="vertical-align: middle;"> <?= esc($pj['nomor_wa']) ?>)<?php endforeach ?></div>
        </td>
    </tr>
</table>

<h2>Nomor Lomba Individu <span class="kecil">(<?= count($perAtlet) ?> atlet)</span></h2>
<?php if ($perAtlet === []) : ?>
    <p class="kosong">Tidak ada nomor individu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <thead>
            <tr><th width="7mm" style="text-align: right;">No</th><th>Nama Atlet</th><th width="22mm">NISN</th><th width="27mm" style="white-space: nowrap;">Kelompok Umur</th><th>Nomor Lomba</th><th class="uang" width="22mm">Biaya</th></tr>
        </thead>
        <tbody>
            <?php foreach (array_values($perAtlet) as $i => $atlet) : ?>
                <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                    <td class="no"><?= $i + 1 ?></td>
                    <td><?= esc($atlet['nama']) ?></td>
                    <td><?= esc($atlet['nisn']) ?></td>
                    <td><?= esc($atlet['kelompok_umur']) ?></td>
                    <td><?= esc(implode(', ', $atlet['nomor'])) ?></td>
                    <td class="uang"><?= $atlet['biaya'] === null ? '' : esc(rupiah($atlet['biaya'])) ?></td>
                </tr>
            <?php endforeach ?>
            <tr class="jumlah"><td colspan="5">Subtotal individu</td><td class="uang"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
        </tbody>
    </table>
<?php endif ?>

<h2>Nomor Lomba Beregu <span class="kecil">(<?= count($p['beregu']) ?> tim)</span></h2>
<?php if ($p['beregu'] === []) : ?>
    <p class="kosong">Tidak ada nomor beregu pada pendaftaran ini.</p>
<?php else : ?>
    <table class="data">
        <thead>
            <tr><th width="7mm" style="text-align: right;">No</th><th width="45mm">Nomor Lomba</th><th>Anggota</th><th class="uang" width="22mm">Biaya</th></tr>
        </thead>
        <tbody>
            <?php foreach ($p['beregu'] as $i => $tim) : ?>
                <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                    <td class="no"><?= $i + 1 ?></td>
                    <td><?= esc($tim['nomor_lomba']) ?></td>
                    <td><?= esc(implode(', ', array_column($tim['anggota'], 'nama'))) ?></td>
                    <td class="uang"><?= $tim['biaya_tim'] === null ? '<span class="kecil">Tanpa biaya (tim sudah terdaftar)</span>' : esc(rupiah($tim['biaya_tim'])) ?></td>
                </tr>
            <?php endforeach ?>
            <tr class="jumlah"><td colspan="3">Subtotal beregu</td><td class="uang"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
        </tbody>
    </table>
<?php endif ?>

<table class="akhir">
    <tr>
        <td width="55%" class="stempel stempel-<?= $lunas ? 'lunas' : 'menunggu' ?>">
            <img src="<?= $stempel ?>" width="58mm" alt="<?= esc($p['status_label'], 'attr') ?>">
            <div class="catatan">Bukti ini dibuat otomatis dan sah tanpa tanda tangan.<br>Keasliannya dapat diperiksa dengan memindai kode QR di pojok kanan atas.</div>
        </td>
        <td width="45%">
            <table class="total">
                <tr><td>Subtotal individu</td><td style="text-align: right;"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
                <tr><td>Subtotal beregu</td><td style="text-align: right;"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
                <tr class="akhir"><td>Total Pembayaran</td><td style="text-align: right;"><?= esc(rupiah($p['total'])) ?></td></tr>
            </table>
        </td>
    </tr>
</table>

</body>
</html>
