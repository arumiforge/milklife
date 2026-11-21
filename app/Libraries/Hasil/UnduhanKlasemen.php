<?php

namespace App\Libraries\Hasil;

use App\Libraries\HariLomba\PdfHariLomba;
use App\Libraries\Panel\EksporExcel;

/**
 * Unduhan PDF dan Excel klasemen sekolah (F8-05, K124 butir 8). PDF A4 tegak,
 * satu tabel per jenjang; Excel satu lembar per jenjang. Kolom poin selalu
 * ada; kalimat cara juara umum ditentukan mengikuti metode di Pengaturan >
 * Hasil (F9-11).
 *
 *     $unduhan = new UnduhanKlasemen($hasil->klasemen(true), true);
 *     $unduhan->pdf();
 *     $unduhan->excel()->unduh(UnduhanKlasemen::namaBerkas('xlsx'));
 */
final class UnduhanKlasemen
{
    /**
     * @var array<string, string>
     */
    public const KOLOM_EXCEL = [
        'peringkat'  => 'Peringkat',
        'sekolah'    => 'Sekolah',
        'npsn'       => 'NPSN',
        'emas'       => 'Juara I',
        'perak'      => 'Juara II',
        'perunggu'   => 'Juara III',
        'jumlah'     => 'Jumlah',
        'poin'       => 'Poin',
        'juara_umum' => 'Juara umum',
    ];

    /**
     * @param array<string, array<string, mixed>> $klasemen `HasilLomba::klasemen()` atau `Klasemen::hitung()`
     * @param bool                                $publik   hanya nomor terbit (judul berbeda)
     */
    public function __construct(private readonly array $klasemen, private readonly bool $publik)
    {
    }

    public static function namaBerkas(string $akhiran): string
    {
        return 'Klasemen' . PdfHariLomba::akhiranBerkas() . '.' . $akhiran;
    }

    public function html(bool $logo): string
    {
        return view('hasil/klasemen_pdf', [
            'klasemen'   => $this->klasemen,
            'publik'     => $this->publik,
            'keterangan' => Klasemen::keterangan(MetodeKlasemen::metode(), MetodeKlasemen::poin()),
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

        $mpdf->SetTitle('Klasemen Sekolah');
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }

    public function excel(): EksporExcel
    {
        $kolom = self::KOLOM_EXCEL;

        foreach (['peringkat', 'emas', 'perak', 'perunggu', 'jumlah', 'poin'] as $angka) {
            $kolom[$angka] = ['judul' => self::KOLOM_EXCEL[$angka], 'jenis' => 'angka'];
        }

        $utama = null;

        foreach ($this->klasemen as $jenjang) {
            $lembar = (new EksporExcel(str_replace('/', ' ', $jenjang['label']), $kolom))->tambahBaris(array_map(static fn (array $b): array => [
                'juara_umum' => $b['juara_umum'] === null ? '' : 'Juara umum ' . str_repeat('I', $b['juara_umum']),
            ] + $b, $jenjang['baris']));

            if ($utama === null) {
                $utama = $lembar;
            } else {
                $utama->tambahLembar($lembar);
            }
        }

        return $utama ?? new EksporExcel('Klasemen', $kolom);
    }
}
