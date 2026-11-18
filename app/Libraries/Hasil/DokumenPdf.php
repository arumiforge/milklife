<?php

namespace App\Libraries\Hasil;

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\Unggahan;
use Mpdf\Mpdf;

/**
 * PDF hasil lomba dan klasemen (F8-04, F8-05), A4 tegak, dengan kepala yang
 * sama dengan rekap PDF: logo persegi dan nama acara dari pengaturan.
 *
 *     $pdf = DokumenPdf::buat('Hasil Lari 60 m putra', view('hasil/_pdf_hasil', [...]));
 */
final class DokumenPdf
{
    /**
     * Nama berkas `<Awalan>_<bagian>_<NamaAcara>_<Tahun>.<akhiran>`, hanya huruf dan angka.
     *
     * @param list<string> $bagian
     */
    public static function namaBerkas(array $bagian, string $akhiran): string
    {
        $bagian = [...$bagian, AcaraController::namaTampil(), (string) setting('Acara.tahun')];
        $bagian = array_map(static fn (string $b): string => (string) preg_replace('/[^A-Za-z0-9]+/', '', ucwords(str_replace(['×', '-'], ' ', $b))), $bagian);

        return implode('_', array_filter($bagian, static fn (string $b): bool => $b !== '')) . '.' . $akhiran;
    }

    /**
     * Isi berkas PDF dari judul dan isi HTML (tanpa kepala; kepala ditambahkan di sini).
     */
    public static function buat(string $judul, string $isi, Unggahan $unggahan = new Unggahan()): string
    {
        $mpdf = new Mpdf([
            'format'        => 'A4',
            'tempDir'       => WRITEPATH . 'cache/mpdf',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 10,
            'margin_bottom' => 12,
        ]);
        $logo = self::logoPng($unggahan);

        if ($logo !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }

        $seri  = trim((string) setting('Acara.seri'));
        $tahun = trim((string) setting('Acara.tahun'));

        $mpdf->SetTitle($judul);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML(view('admin/hasil/pdf', [
            'judul'     => $judul,
            'isi'       => $isi,
            'namaAcara' => mb_strtoupper(AcaraController::namaTampil()),
            'seriTahun' => trim(($seri !== '' ? 'Seri ' . $seri : '') . ($tahun !== '' ? ' Tahun ' . $tahun : '')),
            'logo'      => $logo !== null,
        ], ['saveData' => false]));

        return $mpdf->Output('', 'S');
    }

    private static function logoPng(Unggahan $unggahan): ?string
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
