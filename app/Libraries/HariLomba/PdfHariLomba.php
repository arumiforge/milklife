<?php

namespace App\Libraries\HariLomba;

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\Unggahan;
use Mpdf\Mpdf;

/**
 * Bagian bersama PDF hari lomba (F8-03): pembuat mPDF, nama acara, seri dan
 * tahun, logo persegi, dan bagian nama berkas. Polanya sama dengan
 * `Panel\RekapPdf` dan bukti pendaftaran.
 */
final class PdfHariLomba
{
    /**
     * @param array<string, mixed> $opsi opsi mPDF tambahan (format, margin)
     */
    public static function mpdf(array $opsi): Mpdf
    {
        return new Mpdf($opsi + [
            'tempDir'       => WRITEPATH . 'cache/mpdf',
            'default_font'  => 'dejavusans',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 10,
            'margin_bottom' => 12,
        ]);
    }

    public static function namaAcara(): string
    {
        return mb_strtoupper(AcaraController::namaTampil());
    }

    /**
     * `Seri 2 Tahun 2026`; bagian yang kosong dilewati.
     */
    public static function seriTahun(): string
    {
        $seri  = trim((string) setting('Acara.seri'));
        $tahun = trim((string) setting('Acara.tahun'));

        return trim(($seri !== '' ? 'Seri ' . $seri : '') . ($tahun !== '' ? ' Tahun ' . $tahun : ''));
    }

    /**
     * Bagian akhir nama berkas: `_MilkLifeAthleticsChallenge_2026`.
     */
    public static function akhiranBerkas(): string
    {
        $bagian = array_filter([self::rapat(AcaraController::namaTampil()), self::rapat(setting('Acara.tahun'))], static fn (string $b): bool => $b !== '');

        return $bagian === [] ? '' : '_' . implode('_', $bagian);
    }

    /**
     * Teks yang aman untuk nama berkas: huruf dan angka saja.
     */
    public static function rapat(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $nilai) : '';
    }

    /**
     * Logo persegi sebagai PNG transparan, atau `null` bila belum diunggah.
     */
    public static function logoPng(Unggahan $unggahan = new Unggahan()): ?string
    {
        $jalur  = trim((string) setting('HalamanDepan.logo_persegi'));
        $penuh  = $jalur !== '' && $unggahan->ada($jalur) ? $unggahan->jalurPenuh($jalur) : null;
        $gambar = $penuh === null ? false : @imagecreatefromstring((string) file_get_contents($penuh));

        if ($gambar === false) {
            return null;
        }

        imagesavealpha($gambar, true);
        ob_start();
        imagepng($gambar);

        return (string) ob_get_clean();
    }
}
