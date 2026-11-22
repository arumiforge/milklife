<?php
/**
 * Isi bukti pendaftaran PDF (F4 tegak, K77), diubah mPDF oleh
 * `App\Libraries\BuktiPendaftaran`. CSS ditulis di sini karena mPDF tidak
 * memuat berkas tema.
 *
 * Susunan hemat tempat untuk data banyak: kepala (logo kiri, judul tengah,
 * garis oranye) yang diulang di setiap halaman (K108), data pendaftaran
 * bergaya formulir resmi (nama sekolah sendiri di atas, lalu label kiri dan
 * isi kanan), tabel nomor individu satu baris per atlet (nomor lombanya
 * digabung), tabel nomor beregu satu baris per anggota regu (no, nomor lomba,
 * regu, dan biaya regu berupa sel gabungan setinggi anggota regunya; nomor
 * dada dan peran inti/cadangan di kolom sendiri), lalu status dan
 * total berdampingan. Kolom regu hanya ada bila sekolah punya lebih dari satu
 * regu di salah satu nomor; regu tunggal tidak ditulis nama regunya (K108).
 * Kode QR besar di pojok kanan bawah halaman terakhir dan keterangannya di
 * pojok kiri bawah (`bukti/pdf_qr`, ditulis `BuktiPendaftaran::pdf()`, K89).
 * Kepala tabel diulang di setiap halaman; ID, waktu cetak, dan nomor halaman
 * di kaki setiap halaman.
 *
 * Atlet yang ditolak panitia (K117) dicatat di tabel "Atlet Ditolak Panitia"
 * di bawah nomor beregu: alasan dan kelompok umur yang disarankan, atau
 * "Sudah didaftarkan ulang". Bila semua atlet ditolak, label kepala menjadi
 * "Keterangan Penolakan Atlet", tabel nomor lomba tidak dicetak, stempel
 * merah "DITOLAK", dan total Rp 0 tanpa tagihan.
 *
 * @var array<string, mixed> $p         satu pendaftaran (App\Libraries\DataPendaftaran)
 * @var string               $namaAcara nama acara berhuruf besar
 * @var string               $seriTahun 'Seri 2 Tahun 2026'
 * @var bool                 $logo      logo persegi ada (`var:logo`, PNG transparan)
 * @var string               $dicetak   tanggal cetak (tanpa pukul) untuk kaki halaman
 *
 */

use App\Libraries\Pendaftaran\Regu;

// Ikon WhatsApp (Bootstrap Icons, MIT), disisipkan sebagai SVG.
$ikonWa = 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16"><path fill="#25D366" d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232"/></svg>');

$lunas = $p['status'] === 'lunas';
// Semua atlet ditolak panitia (K117): dokumen menjadi keterangan penolakan, tanpa tagihan.
$ditolakSemua = (bool) ($p['ditolak_semua'] ?? false);
$semuaDitolak = [...($p['atlet_ditolak'] ?? []), ...($p['atlet_didaftarkan_ulang'] ?? [])];
usort($semuaDitolak, static fn (array $x, array $y): int => $x['id'] <=> $y['id']);

// Stempel status (K78): bingkai ganda miring, hijau "LUNAS" atau oranye "MENUNGGU", sebagai SVG.
// Sejak K90 pendaftaran masuk bersama bukti transfer, jadi yang belum Lunas menunggu konfirmasi panitia.
$warnaStempel = $lunas ? '#198754' : ($ditolakSemua ? '#B02A37' : '#C4461B');
$teksStempel  = $lunas ? ['LUNAS', 'PEMBAYARAN DITERIMA', 34] : ($ditolakSemua ? ['DITOLAK', 'SEMUA ATLET', 32] : ['MENUNGGU', 'KONFIRMASI PANITIA', 28]);
$stempel      = 'data:image/svg+xml;base64,' . base64_encode(
    '<svg xmlns="http://www.w3.org/2000/svg" width="240" height="120" viewBox="0 0 240 120">'
    . '<g transform="rotate(-7 120 60)">'
    . '<rect x="14" y="20" width="212" height="80" rx="12" fill="none" stroke="' . $warnaStempel . '" stroke-width="4.5"/>'
    . '<rect x="22" y="28" width="196" height="64" rx="7" fill="none" stroke="' . $warnaStempel . '" stroke-width="1.6"/>'
    . '<text x="120" y="66" font-family="dejavusans" font-weight="bold" font-size="' . $teksStempel[2] . '" fill="' . $warnaStempel . '" text-anchor="middle">' . $teksStempel[0] . '</text>'
    . '<text x="120" y="84" font-family="dejavusans" font-weight="bold" font-size="10.5" fill="' . $warnaStempel . '" text-anchor="middle">' . $teksStempel[1] . '</text>'
    . '</g></svg>',
);

// Nomor dada hanya dicetak bila sudah Lunas (K87); selain itu kosong.
$nomorDada = static fn (array $atlet): string => $lunas && ($atlet['nomor_dada'] ?? null) !== null ? (string) $atlet['nomor_dada'] : '';

// Nomor individu digabung per atlet: satu baris per atlet, nomor dipisah koma, biaya dijumlah.
$perAtlet = [];

foreach ($p['individu'] as $baris) {
    $perAtlet[$baris['nisn']] ??= ['nama' => $baris['nama'], 'nisn' => $baris['nisn'], 'nomor_dada' => $nomorDada($baris), 'kelompok_umur' => $baris['kelompok_umur'], 'nomor' => [], 'biaya' => null];
    $perAtlet[$baris['nisn']]['nomor'][] = $baris['nomor_lomba'];

    if ($baris['biaya'] !== null) {
        $perAtlet[$baris['nisn']]['biaya'] = ($perAtlet[$baris['nisn']]['biaya'] ?? 0) + $baris['biaya'];
    }
}

// Kolom regu hanya bila ada regu yang bukan satu-satunya regu sekolah di nomornya (K108).
$kolomRegu = array_filter($p['beregu'], static fn (array $t): bool => ! ($t['regu_tunggal'] ?? false)) !== [];
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<style>
    @page { header: html_kepala; footer: html_kaki; }
    body { font-family: dejavusans; font-size: 8.5pt; color: #1b2433; }
    .kepala { width: 100%; border-bottom: 0.8mm solid #E85F31; padding-bottom: 3mm; font-family: dejavusans; }
    .kepala td { vertical-align: middle; }
    .judul { text-align: center; }
    .label-bukti { font-size: 8pt; font-weight: bold; color: #C4461B; letter-spacing: 1.5pt; }
    .nama-acara { font-size: 17pt; font-weight: bold; color: #021F7B; }
    .seri-tahun { font-size: 10.5pt; font-weight: bold; color: #234987; }

    /* Data pendaftaran bergaya formulir resmi: nama sekolah sendiri di atas, lalu label kiri berlatar, isi kanan (K108). */
    table.sekolah { width: 100%; border-collapse: collapse; border: 0.3mm solid #021F7B; margin-bottom: 2mm; }
    table.sekolah td { padding: 2.2mm 2.4mm; vertical-align: middle; }
    td.sekolah-label { width: 34mm; background: #021F7B; color: #ffffff; font-size: 7.5pt; }
    td.sekolah-nama { font-size: 11pt; font-weight: bold; color: #021F7B; line-height: 1.3; }
    table.info { width: 100%; border-collapse: collapse; border: 0.3mm solid #021F7B; }
    table.info td { border: 0.15mm solid #C9D3E3; padding: 1.7mm 2.4mm; vertical-align: middle; }
    table.info td.info-label { width: 34mm; background: #EEF2F8; font-size: 7.5pt; color: #3A4660; }
    table.info td.info-isi { font-size: 9pt; font-weight: bold; color: #021F7B; }
    table.info td.info-id { font-family: dejavusansmono; font-size: 10.5pt; font-weight: bold; color: #021F7B; letter-spacing: 0.6pt; }
    table.info td.status-lunas { font-size: 9pt; font-weight: bold; color: #198754; }
    table.info td.status-menunggu { font-size: 9pt; font-weight: bold; color: #C4461B; }
    table.info td.status-ditolak { font-size: 9pt; font-weight: bold; color: #B02A37; }
    table.info td.info-pj { font-size: 8.5pt; color: #1b2433; line-height: 1.5; }
    .wa { color: #3A4660; }

    h2 { font-size: 9.5pt; color: #021F7B; margin: 4mm 0 1.5mm; padding-left: 2mm; border-left: 1mm solid #E85F31; }
    h2.pertama { margin-top: 0; }
    table.data { width: 100%; border-collapse: collapse; }
    table.data th { background: #021F7B; color: #ffffff; text-align: left; padding: 1.3mm 1.5mm; font-size: 7.5pt; font-weight: bold; }
    table.data td { border-bottom: 0.15mm solid #D6DEEA; padding: 1.1mm 1.5mm; vertical-align: top; }
    table.data tr.genap td { background: #F7F9FC; }
    table.data td.uang, table.data th.uang { text-align: right; white-space: nowrap; }
    table.data td.no { color: #5B6475; text-align: right; }
    table.data tr.jumlah td { font-weight: bold; color: #021F7B; border-bottom: none; border-top: 0.3mm solid #021F7B; background: #ffffff; }
    table.data tr.awal-regu td { border-top: 0.3mm solid #9BCCEC; }
    table.data td.gabung { background: #F3F6FB; border-bottom: 0.3mm solid #9BCCEC; }
    table.data td.regu { font-weight: bold; text-align: center; color: #021F7B; font-size: 9.5pt; }
    td.peran-inti { font-weight: bold; color: #234987; }
    td.peran-cadangan { color: #5B6475; font-style: italic; }
    .kecil { font-size: 7.5pt; color: #5B6475; }
    .kosong { color: #5B6475; font-style: italic; margin: 0; }

    /* Atlet ditolak panitia (K117): kepala tabel merah tua, keterangan di kolom terakhir. */
    h2.ditolak { border-left-color: #B02A37; }
    table.ditolak th { background: #842029; }
    td.sesuai { color: #0F5132; font-weight: bold; }
    td.ulang { color: #198754; }
    .alasan { color: #3A4660; }
    .catatan-ditolak { font-size: 7.5pt; line-height: 1.5; color: #3A4660; margin-top: 2mm; padding: 2mm 2.5mm; background: #FFF4F4; border-left: 0.8mm solid #B02A37; }

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

<?php // Kepala dan kaki di setiap halaman (`@page`, K108); mPDF menyesuaikan margin atas dengan tinggi kepala (`BuktiPendaftaran::pdf()`). ?>
<htmlpageheader name="kepala">
<table class="kepala">
    <tr>
        <td width="26mm"><?php if ($logo) : ?><img src="var:logo" height="22mm"><?php endif ?></td>
        <td class="judul">
            <div class="label-bukti"><?= $ditolakSemua ? 'KETERANGAN PENOLAKAN ATLET' : 'BUKTI PENDAFTARAN' ?></div>
            <div class="nama-acara"><?= esc($namaAcara) ?></div>
            <?php if ($seriTahun !== '') : ?><div class="seri-tahun"><?= esc($seriTahun) ?></div><?php endif ?>
        </td>
        <?php // Sel kosong selebar logo supaya judul tetap di tengah; kode QR di pojok kanan bawah (K88). ?>
        <td width="26mm"></td>
    </tr>
</table>
</htmlpageheader>
<htmlpagefooter name="kaki">
<table width="100%" style="font-family: dejavusans; font-size: 7pt; color: #5B6475; border-top: 0.2mm solid #D6DEEA;">
    <tr>
        <td style="padding-top: 1.5mm;"><?= esc($p['id_pendaftaran']) ?> · dicetak <?= esc($dicetak) ?></td>
        <td style="padding-top: 1.5mm; text-align: right;">Halaman {PAGENO} dari {nbpg}</td>
    </tr>
</table>
</htmlpagefooter>

<h2 class="pertama">Data pendaftaran</h2>
<?php // Nama sekolah dipisah di atas karena bisa panjang; data lain berukuran tetap di bawahnya (K108). ?>
<table class="sekolah">
    <tr>
        <td class="sekolah-label">Nama sekolah</td>
        <td class="sekolah-nama"><?= esc($p['nama_sekolah']) ?></td>
    </tr>
</table>
<table class="info">
    <tr>
        <td class="info-label">ID pendaftaran</td>
        <?php // ID lama yang sudah digabung ke pendaftaran ini (K109); gaya langsung karena mPDF tidak menerapkan pemilih bersarang di sel. ?>
        <td class="info-id" width="58mm"><?= esc($p['id_pendaftaran']) ?><?php if (($p['gabungan_dari'] ?? []) !== []) : ?><div class="gabungan" style="font-family: dejavusans; font-size: 6.5pt; font-weight: normal; color: #5B6475; letter-spacing: 0;">Gabungan dari <?= esc(implode(', ', $p['gabungan_dari'])) ?></div><?php endif ?></td>
        <td class="info-label">Tanggal pendaftaran</td>
        <td class="info-isi"><?= esc(tanggal_id($p['dibuat_pada'])) ?></td>
    </tr>
    <tr>
        <td class="info-label">NPSN</td>
        <td class="info-isi"><?= esc($p['npsn'] ?? '–') ?></td>
        <td class="info-label">Jenjang</td>
        <td class="info-isi"><?= esc($p['jenjang_label'] ?? '') ?></td>
    </tr>
    <tr>
        <td class="info-label">Kabupaten / Kota</td>
        <td class="info-isi"><?= esc($p['kabupaten']) ?></td>
        <td class="info-label">Status pembayaran</td>
        <td class="status-<?= $lunas ? 'lunas' : ($ditolakSemua ? 'ditolak' : 'menunggu') ?>"><?= esc($p['status_label']) ?></td>
    </tr>
    <tr>
        <td class="info-label">Ofisial</td>
        <td class="info-pj" colspan="3"><?php foreach ($p['penanggung_jawab'] as $i => $pj) : ?><?= $i > 0 ? ', ' : '' ?><b><?= esc($pj['nama']) ?></b> <span class="wa">(<img src="<?= $ikonWa ?>" width="2.8mm" height="2.8mm" style="vertical-align: middle;"> <?= esc($pj['nomor_wa']) ?>)</span><?php endforeach ?></td>
    </tr>
</table>

<?php if (! $ditolakSemua) : ?>
<h2>Nomor lomba individu <span class="kecil">(<?= count($perAtlet) ?> atlet)</span></h2>
<?php if ($perAtlet === []) : ?>
    <p class="kosong">Pendaftaran ini tidak memuat nomor individu.</p>
<?php else : ?>
    <table class="data">
        <thead>
            <tr><th width="7mm" style="text-align: right;">No</th><th>Nama atlet</th><th width="22mm">NISN</th><th width="17mm" style="white-space: nowrap;">No. Atlet</th><th width="27mm" style="white-space: nowrap;">Kelompok umur</th><th>Nomor lomba</th><th class="uang" width="22mm">Biaya</th></tr>
        </thead>
        <tbody>
            <?php foreach (array_values($perAtlet) as $i => $atlet) : ?>
                <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                    <td class="no"><?= $i + 1 ?></td>
                    <td><?= esc($atlet['nama']) ?></td>
                    <td><?= esc($atlet['nisn']) ?></td>
                    <td><b><?= esc($atlet['nomor_dada']) ?></b></td>
                    <td><?= esc($atlet['kelompok_umur']) ?></td>
                    <td><?= esc(implode(', ', $atlet['nomor'])) ?></td>
                    <td class="uang"><?= $atlet['biaya'] === null ? '' : esc(rupiah($atlet['biaya'])) ?></td>
                </tr>
            <?php endforeach ?>
            <tr class="jumlah"><td colspan="6">Subtotal individu</td><td class="uang"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
        </tbody>
    </table>
<?php endif ?>

<h2>Nomor lomba beregu <span class="kecil">(<?= count($p['beregu']) ?> regu, <?= array_sum(array_map(static fn (array $t): int => count($t['anggota']), $p['beregu'])) ?> anggota)</span></h2>
<?php if ($p['beregu'] === []) : ?>
    <p class="kosong">Pendaftaran ini tidak memuat nomor beregu.</p>
<?php else : ?>
    <table class="data beregu">
        <thead>
            <tr><th width="7mm" style="text-align: right;">No</th><th width="36mm">Nomor lomba</th><?php if ($kolomRegu) : ?><th width="12mm" style="text-align: center;"><?= esc(Regu::awalan()) ?></th><?php endif ?><th>Nama atlet</th><th width="22mm">NISN</th><th width="17mm" style="white-space: nowrap;">No. Atlet</th><th width="16mm">Peran</th><th class="uang" width="22mm">Biaya</th></tr>
        </thead>
        <tbody>
            <?php foreach ($p['beregu'] as $i => $tim) : ?>
                <?php // No, nomor lomba, regu, dan biaya digabung sepanjang anggota regu (rowspan, K108). ?>
                <?php $baris = max(1, count($tim['anggota'])) ?>
                <?php foreach ($tim['anggota'] as $j => $a) : ?>
                    <?php $cadangan = ($a['peran'] ?? '') === Regu::CADANGAN ?>
                    <tr<?= $j === 0 ? ' class="awal-regu"' : '' ?>>
                        <?php if ($j === 0) : ?>
                            <td class="no gabung" rowspan="<?= $baris ?>"><?= $i + 1 ?></td>
                            <td class="gabung" rowspan="<?= $baris ?>"><b><?= esc($tim['nama_nomor'] ?? $tim['nomor_lomba']) ?></b></td>
                            <?php if ($kolomRegu) : ?><td class="gabung regu" rowspan="<?= $baris ?>"><?= ($tim['regu_tunggal'] ?? false) ? '' : esc($tim['regu'] ?? '') ?></td><?php endif ?>
                        <?php endif ?>
                        <td><?= esc($a['nama']) ?></td>
                        <td><?= esc($a['nisn'] ?? '') ?></td>
                        <td><b><?= esc($nomorDada($a)) ?></b></td>
                        <td class="<?= $cadangan ? 'peran-cadangan' : 'peran-inti' ?>"><?= esc(Regu::labelPeran($a['peran'] ?? Regu::INTI)) ?></td>
                        <?php if ($j === 0) : ?>
                            <td class="uang gabung" rowspan="<?= $baris ?>"><?= $tim['biaya_tim'] === null ? '<span class="kecil">Tanpa biaya<br>' . esc(mb_strtolower(Regu::awalan())) . ' sudah terdaftar</span>' : esc(rupiah($tim['biaya_tim'])) ?></td>
                        <?php endif ?>
                    </tr>
                <?php endforeach ?>
            <?php endforeach ?>
            <tr class="jumlah"><td colspan="<?= $kolomRegu ? 7 : 6 ?>">Subtotal beregu</td><td class="uang"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
        </tbody>
    </table>
<?php endif ?>
<?php endif ?>

<?php // Atlet ditolak panitia (K117): tetap dicatat di bukti, dengan alasan dan kelompok umur yang disarankan. ?>
<?php if ($semuaDitolak !== []) : ?>
    <h2 class="ditolak">Atlet ditolak panitia <span class="kecil">(<?= count($semuaDitolak) ?> atlet, tidak termasuk tagihan)</span></h2>
    <table class="data ditolak">
        <thead>
            <tr><th width="7mm" style="text-align: right;">No</th><th>Nama atlet</th><th width="22mm">NISN</th><th width="42mm">Didaftarkan di</th><th>Alasan</th><th width="34mm">Keterangan</th></tr>
        </thead>
        <tbody>
            <?php foreach ($semuaDitolak as $i => $d) : ?>
                <tr<?= $i % 2 === 1 ? ' class="genap"' : '' ?>>
                    <td class="no"><?= $i + 1 ?></td>
                    <td><?= esc($d['nama']) ?></td>
                    <td><?= esc($d['nisn']) ?></td>
                    <td><b><?= esc($d['kelompok_umur']) ?></b><?php if ($d['nomor_lomba'] !== []) : ?><br><span class="kecil"><?= esc(implode(', ', array_column($d['nomor_lomba'], 'nama'))) ?></span><?php endif ?></td>
                    <td class="alasan"><?= nl2br(esc($d['alasan'])) ?></td>
                    <?php if ($d['didaftarkan_ulang'] !== null) : ?>
                        <td class="ulang">Sudah didaftarkan ulang</td>
                    <?php elseif ($d['sesuai_beda'] && $d['kelompok_umur_sesuai'] !== null) : ?>
                        <td class="sesuai">Daftarkan ulang di <?= esc($d['kelompok_umur_sesuai']) ?></td>
                    <?php else : ?>
                        <td>Perlu didaftarkan ulang</td>
                    <?php endif ?>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <div class="catatan-ditolak">
        <?= $ditolakSemua ? 'Panitia menolak semua atlet di pendaftaran ini, jadi pendaftaran ini tidak perlu dibayar.' : 'Atlet di atas dikeluarkan dari pendaftaran ini dan tidak ditagih.' ?>
        Daftarkan ulang atletnya lewat menu Pendaftaran. NISN yang sama boleh dipakai.
        Bila Anda sudah mentransfer biaya untuk atlet ini, hubungi panitia untuk mengurusnya.
    </div>
<?php endif ?>

<table class="akhir">
    <tr>
        <td width="55%" class="stempel stempel-<?= $lunas ? 'lunas' : ($ditolakSemua ? 'ditolak' : 'menunggu') ?>">
            <img src="<?= $stempel ?>" width="58mm" alt="<?= esc($p['status_label'], 'attr') ?>">
        </td>
        <td width="45%">
            <table class="total">
                <?php if ($ditolakSemua) : ?>
                <tr class="akhir"><td>Total pembayaran</td><td style="text-align: right;"><?= esc(rupiah($p['total'])) ?></td></tr>
                <tr><td colspan="2" class="kecil" style="text-align: right;">Tidak ada tagihan</td></tr>
                <?php else : ?>
                <tr><td>Subtotal individu</td><td style="text-align: right;"><?= esc(rupiah($p['subtotal_individu'])) ?></td></tr>
                <tr><td>Subtotal beregu</td><td style="text-align: right;"><?= esc(rupiah($p['subtotal_beregu'])) ?></td></tr>
                <tr class="akhir"><td>Total pembayaran</td><td style="text-align: right;"><?= esc(rupiah($p['total'])) ?></td></tr>
                <?php endif ?>
            </table>
        </td>
    </tr>
</table>


</body>
</html>
