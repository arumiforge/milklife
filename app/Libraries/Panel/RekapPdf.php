<?php

namespace App\Libraries\Panel;

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\Unggahan;
use Mpdf\Mpdf;

/**
 * Rekap PDF, A4 mendatar, dengan pola `BuktiPendaftaran` (asumsi 6 rencana
 * Tahap 6): judul dari pengaturan acara dan logo persegi. Isinya hasil
 * `Rekap::susun()`, jadi sama dengan halaman dan Excel-nya.
 *
 *     $pdf = new RekapPdf('sekolah', 'lunas', (new Rekap())->susun('sekolah', 'lunas'));
 *     $pdf->namaBerkas(); // 'RekapSekolahLunas_MilkLifeAthleticsChallenge_2026.pdf'
 *     $pdf->pdf();        // isi berkas PDF
 */
final class RekapPdf
{
    /**
     * @param array{kolom: array<string, array{judul: string, jenis: string}>, baris: list<array<string, mixed>>, jumlah: array<string, mixed>|null} $rekap
     */
    public function __construct(
        private readonly string $jenis,
        private readonly ?string $status,
        private readonly array $rekap,
        private readonly Unggahan $unggahan = new Unggahan(),
    ) {
    }

    /**
     * `Rekap<Jenis><Status>_<NamaAcara>_<Tahun>.pdf`; bagian kosong dilewati.
     */
    public function namaBerkas(string $akhiran = 'pdf'): string
    {
        $bagian = [
            'Rekap' . str_replace(' ', '', ucwords(str_replace('-', ' ', $this->jenis))) . (Rekap::STATUS[$this->status] ?? ''),
            self::rapat(AcaraController::namaTampil()),
            self::rapat(setting('Acara.tahun')),
        ];

        return implode('_', array_filter($bagian, static fn (string $b): bool => $b !== '')) . '.' . $akhiran;
    }

    public function html(bool $logo): string
    {
        $seri  = trim((string) setting('Acara.seri'));
        $tahun = trim((string) setting('Acara.tahun'));

        return view('admin/rekap/pdf', [
            'rekap'     => $this->rekap,
            'judul'     => 'Rekap ' . Rekap::JENIS[$this->jenis],
            'status'    => $this->jenis === 'keuangan' ? 'Lunas dan Menunggu' : (Rekap::STATUS[$this->status] ?? 'Semua status'),
            'namaAcara' => mb_strtoupper(AcaraController::namaTampil()),
            'seriTahun' => trim(($seri !== '' ? 'Seri ' . $seri : '') . ($tahun !== '' ? ' Tahun ' . $tahun : '')),
            'logo'      => $logo,
        ], ['saveData' => false]);
    }

    public function pdf(): string
    {
        $mpdf = new Mpdf([
            'format'        => 'A4-L',
            'tempDir'       => WRITEPATH . 'cache/mpdf',
            'margin_left'   => 12,
            'margin_right'  => 12,
            'margin_top'    => 10,
            'margin_bottom' => 12,
        ]);
        $logo = $this->logoPng();

        if ($logo !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }

        $mpdf->SetTitle('Rekap ' . Rekap::JENIS[$this->jenis]);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }

    /**
     * Logo persegi sebagai PNG transparan, atau `null` (sama dengan
     * `BuktiPendaftaran`, yang milik Tahap 5 dan hanya dibaca).
     */
    private function logoPng(): ?string
    {
        $jalur  = trim((string) setting('HalamanDepan.logo_persegi'));
        $penuh  = $jalur !== '' && $this->unggahan->ada($jalur) ? $this->unggahan->jalurPenuh($jalur) : null;
        $gambar = $penuh === null ? false : @imagecreatefromstring((string) file_get_contents($penuh));

        if ($gambar === false) {
            return null;
        }

        imagesavealpha($gambar, true);
        ob_start();
        imagepng($gambar);

        return (string) ob_get_clean();
    }

    private static function rapat(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $nilai) : '';
    }
}
