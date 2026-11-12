<?php

namespace App\Libraries;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Data\QRMatrix;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Kode QR PNG bergaya tema (K77), dibuat `chillerlan/php-qrcode`.
 *
 * Modul data bulat biru, tiga penanda sudut persegi biru tua dengan titik
 * oranye, dan (bila diberi) logo di tengah di atas ruang kosong. Koreksi galat
 * tingkat H (30 %) supaya kode tetap terbaca walau tengahnya tertutup logo.
 *
 *     $png = KodeQr::png('https://contoh.test/bukti/…', $logoPng);
 */
final class KodeQr
{
    /**
     * Lebar ruang logo dalam modul (ganjil supaya tepat di tengah).
     */
    private const RUANG_LOGO = 11;

    private const SKALA = 12;

    /**
     * @param string|null $logoPng Isi PNG/JPG/WEBP logo; `null` tanpa logo.
     */
    public static function png(string $isi, ?string $logoPng = null): string
    {
        $logo = $logoPng === null ? false : @imagecreatefromstring($logoPng);

        $biruTua = [2, 31, 123];
        $biru    = [35, 73, 135];
        $oranye  = [232, 95, 49];

        $opsi = new QROptions([
            'outputType'          => QROutputInterface::GDIMAGE_PNG,
            'outputBase64'        => false,
            'eccLevel'            => EccLevel::H,
            'scale'               => self::SKALA,
            'addQuietzone'        => true,
            'quietzoneSize'       => 2,
            'drawCircularModules' => true,
            'circleRadius'        => 0.42,
            'keepAsSquare'        => [QRMatrix::M_FINDER_DARK, QRMatrix::M_FINDER_DOT, QRMatrix::M_ALIGNMENT_DARK],
            'addLogoSpace'        => $logo !== false,
            'logoSpaceWidth'      => self::RUANG_LOGO,
            'logoSpaceHeight'     => self::RUANG_LOGO,
            'bgColor'             => [255, 255, 255],
            'moduleValues'        => [
                QRMatrix::M_FINDER_DARK    => $biruTua,
                QRMatrix::M_FINDER_DOT     => $oranye,
                QRMatrix::M_ALIGNMENT_DARK => $biruTua,
                QRMatrix::M_DATA_DARK      => $biru,
                QRMatrix::M_TIMING_DARK    => $biru,
                QRMatrix::M_FORMAT_DARK    => $biru,
                QRMatrix::M_VERSION_DARK   => $biru,
                QRMatrix::M_DARKMODULE     => $biru,
            ],
        ]);

        $qr  = new QRCode($opsi);
        $png = (string) $qr->render($isi);

        if ($logo === false) {
            return $png;
        }

        // Logo di tengah ruang kosong, disisakan satu modul di tiap sisi.
        $gambar = imagecreatefromstring($png);
        $sisi   = imagesx($gambar);
        $kotak  = (self::RUANG_LOGO - 2) * self::SKALA;
        $lebar  = imagesx($logo);
        $tinggi = imagesy($logo);
        $skala  = min($kotak / $lebar, $kotak / $tinggi);
        $w      = (int) round($lebar * $skala);
        $h      = (int) round($tinggi * $skala);

        imagealphablending($gambar, true);
        imagecopyresampled($gambar, $logo, (int) (($sisi - $w) / 2), (int) (($sisi - $h) / 2), 0, 0, $w, $h, $lebar, $tinggi);

        ob_start();
        imagepng($gambar);

        return (string) ob_get_clean();
    }
}
