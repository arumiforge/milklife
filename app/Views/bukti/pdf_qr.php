<?php
/**
 * Blok penutup bukti PDF (K89): keterangan di kiri, kode QR besar di kanan,
 * sejajar di dasar halaman terakhir. `BuktiPendaftaran::pdf()` memindahkan
 * posisi tulis ke dasar halaman lebih dulu (halaman baru bila tidak muat).
 * Kode QR berisi alamat bukti ini (`var:qr`).
 *
 * Bila panitia sudah mengurutkan ulang nomor dada per sekolah (K111),
 * keterangannya ditulis di atas keterangan keaslian.
 *
 * @var string|null $keteranganNomorDada
 */
?>
<table width="100%" style="font-family: dejavusans;">
    <tr>
        <td style="vertical-align: bottom; font-size: 7.5pt; line-height: 1.5; color: #5B6475; font-style: italic;">
            <?php if (($keteranganNomorDada ?? null) !== null) : ?>
                <div class="keterangan-nomor-dada" style="font-style: normal; color: #021F7B; margin-bottom: 2mm; padding-right: 4mm;"><?= esc($keteranganNomorDada) ?></div>
            <?php endif ?>
            Bukti ini dibuat otomatis dan sah tanpa tanda tangan.<br>
            Pindai kode QR di sebelah kanan untuk memeriksa keasliannya.
        </td>
        <td width="40mm" style="text-align: right; vertical-align: bottom;">
            <img src="var:qr" width="38mm" height="38mm">
        </td>
    </tr>
</table>
