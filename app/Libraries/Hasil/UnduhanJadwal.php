<?php

namespace App\Libraries\Hasil;

use App\Libraries\HariLomba\PdfHariLomba;

/**
 * Unduhan PDF jadwal pertandingan satu hari (F9-07), A4 tegak: waktu, nomor,
 * putra/putri, kelompok umur, babak, dan lokasi, seperti tabel di halaman
 * publik `jadwal` (tanpa status, karena PDF dicetak sebelum lomba).
 *
 *     $unduhan = new UnduhanJadwal('2026-12-05', $jadwal->perHari()['2026-12-05']);
 *     $unduhan->pdf();
 *     UnduhanJadwal::namaBerkas('2026-12-05');   // 'Jadwal_20261205_MilkLifeAthleticsChallenge_2026.pdf'
 */
final class UnduhanJadwal
{
    /**
     * @param string                     $tanggal 'Y-m-d'
     * @param list<array<string, mixed>> $babak   satu hari dari `JadwalPertandingan::perHari()`
     */
    public function __construct(private readonly string $tanggal, private readonly array $babak)
    {
    }

    public static function namaBerkas(string $tanggal): string
    {
        return 'Jadwal_' . PdfHariLomba::rapat($tanggal) . PdfHariLomba::akhiranBerkas() . '.pdf';
    }

    public function html(bool $logo): string
    {
        return view('jadwal/pdf', [
            'hari'      => JadwalPertandingan::namaHari($this->tanggal),
            'babak'     => $this->babak,
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

        $mpdf->SetTitle('Jadwal pertandingan ' . JadwalPertandingan::namaHari($this->tanggal));
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }
}
