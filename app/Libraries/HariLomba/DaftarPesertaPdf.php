<?php

namespace App\Libraries\HariLomba;

/**
 * PDF daftar peserta (F8-03): satu nomor lomba per halaman, A4 tegak.
 *
 *     $pdf = new DaftarPesertaPdf([(new DaftarPeserta())->susun(10)]);
 *     $pdf->pdf();                       // isi berkas PDF
 *     DaftarPesertaPdf::namaBerkas('KU 10 Lari 60 m putra', 'pdf');
 *     // 'DaftarPeserta_KU10Lari60mputra_MilkLifeAthleticsChallenge_2026.pdf'
 */
final class DaftarPesertaPdf
{
    /**
     * @param list<array<string, mixed>> $semua hasil `DaftarPeserta::susun()`
     */
    public function __construct(private readonly array $semua)
    {
    }

    public static function namaBerkas(string $judul, string $akhiran): string
    {
        return 'DaftarPeserta_' . PdfHariLomba::rapat($judul) . PdfHariLomba::akhiranBerkas() . '.' . $akhiran;
    }

    public function html(bool $logo): string
    {
        return view('admin/hari_lomba/daftar_peserta_pdf', [
            'semua'     => $this->semua,
            'namaAcara' => PdfHariLomba::namaAcara(),
            'seriTahun' => PdfHariLomba::seriTahun(),
            'logo'      => $logo,
        ], ['saveData' => false]);
    }

    public function pdf(): string
    {
        $mpdf = PdfHariLomba::mpdf(['format' => 'A4']);
        $logo = PdfHariLomba::logoPng();

        if ($logo !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }

        $mpdf->SetTitle('Daftar Peserta');
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }
}
