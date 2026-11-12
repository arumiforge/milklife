<?php

namespace App\Libraries;

use App\Controllers\Admin\Pengaturan\AcaraController;
use Mpdf\Mpdf;

/**
 * Bukti pendaftaran PDF, A4 mendatar (rancangan 8, K49).
 *
 * Menerima satu pendaftaran dari `DataPendaftaran` dan menyusun HTML-nya
 * (`app/Views/bukti/pdf.php`), PDF-nya (mPDF), dan nama berkasnya. Nama
 * acara, seri, dan tahun diambil dari pengaturan acara. PDF dibuat di memori
 * dan tidak pernah disimpan di disk.
 *
 *     $bukti = new BuktiPendaftaran((new DataPendaftaran())->cariId($id));
 *     $bukti->namaBerkas(); // 'BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_SDNEGERICONTOH1_2026.pdf'
 *     $bukti->pdf();        // isi berkas PDF
 */
final class BuktiPendaftaran
{
    /**
     * @param array<string, mixed> $pendaftaran
     */
    public function __construct(private readonly array $pendaftaran, private readonly Unggahan $unggahan = new Unggahan())
    {
    }

    /**
     * `BuktiPendaftaran_<NamaAcara>Seri<seri>_<NamaSekolah>_<tahun>.pdf`; bagian
     * nama hanya huruf dan angka (K49). Bagian yang kosong dilewati.
     */
    public function namaBerkas(): string
    {
        $seri  = self::rapat(setting('Acara.seri'));
        $bagian = [
            'BuktiPendaftaran',
            self::rapat(AcaraController::namaTampil()) . ($seri !== '' ? 'Seri' . $seri : ''),
            self::rapat($this->pendaftaran['nama_sekolah']),
            self::rapat(setting('Acara.tahun')),
        ];

        return implode('_', array_filter($bagian, static fn (string $b): bool => $b !== '')) . '.pdf';
    }

    /**
     * HTML bukti, siap diubah mPDF.
     */
    public function html(): string
    {
        $seri  = trim((string) setting('Acara.seri'));
        $tahun = trim((string) setting('Acara.tahun'));

        $logo = trim((string) setting('HalamanDepan.logo_persegi'));

        return view('bukti/pdf', [
            'p'         => $this->pendaftaran,
            'namaAcara' => mb_strtoupper(AcaraController::namaTampil()),
            'seriTahun' => trim(($seri !== '' ? 'Seri ' . $seri : '') . ($tahun !== '' ? ' Tahun ' . $tahun : '')),
            'logo'      => $logo !== '' && $this->unggahan->ada($logo) ? $this->unggahan->jalurPenuh($logo) : null,
        ], ['saveData' => false]);
    }

    /**
     * Isi berkas PDF.
     */
    public function pdf(): string
    {
        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'tempDir'       => WRITEPATH . 'cache/mpdf',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 10,
            'margin_bottom' => 10,
        ]);
        $mpdf->SetTitle('Bukti Pendaftaran ' . $this->pendaftaran['id_pendaftaran']);
        $mpdf->WriteHTML($this->html());

        return $mpdf->Output('', 'S');
    }

    /**
     * Teks tanpa spasi dan tanda baca (huruf apa adanya).
     */
    private static function rapat(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $nilai) : '';
    }
}
