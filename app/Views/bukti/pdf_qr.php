<?php
/**
 * Blok penutup bukti PDF (K89): keterangan di kiri, kode QR besar di kanan,
 * sejajar di dasar halaman terakhir. `BuktiPendaftaran::pdf()` memindahkan
 * posisi tulis ke dasar halaman lebih dulu (halaman baru bila tidak muat).
 * Kode QR berisi alamat bukti ini (`var:qr`).
 */
?>
<table width="100%" style="font-family: dejavusans;">
    <tr>
        <td style="vertical-align: bottom; font-size: 7.5pt; line-height: 1.5; color: #5B6475; font-style: italic;">
            Bukti ini dibuat otomatis dan sah tanpa tanda tangan.<br>
            Keasliannya dapat diperiksa dengan memindai kode QR di sebelah kanan.
        </td>
        <td width="40mm" style="text-align: right; vertical-align: bottom;">
            <img src="var:qr" width="38mm" height="38mm">
        </td>
    </tr>
</table>
