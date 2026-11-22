<?php

namespace App\Libraries\Hasil;

use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\HariLomba\PdfHariLomba;
use App\Libraries\Panel\EksporExcel;

/**
 * Unduhan PDF dan Excel hasil satu nomor lomba, semua babak (F8-04, K124 butir 8).
 * PDF A4 tegak, babak berurutan dengan Final paling akhir; Excel satu lembar per babak.
 *
 *     $unduhan = new UnduhanHasil($nomor, $semuaBabak);   // HasilLomba::nomor(), ::semuaBabak()
 *     $unduhan->pdf();
 *     $unduhan->excel()->unduh(UnduhanHasil::namaBerkas($nomor, 'xlsx'));
 */
final class UnduhanHasil
{
    /**
     * @param array<string, mixed>       $nomor
     * @param list<array<string, mixed>> $babak
     */
    public function __construct(private readonly array $nomor, private readonly array $babak)
    {
    }

    /**
     * @param array<string, mixed> $nomor
     */
    public static function namaBerkas(array $nomor, string $akhiran): string
    {
        return 'Hasil_' . PdfHariLomba::rapat($nomor['kelompok_umur'] . ' ' . $nomor['nama']) . PdfHariLomba::akhiranBerkas() . '.' . $akhiran;
    }

    public function html(bool $logo): string
    {
        return view('hasil/pdf', [
            'nomor'     => $this->nomor,
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

        $mpdf->SetTitle('Hasil ' . $this->nomor['nama'] . ' ' . $this->nomor['kelompok_umur']);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }

    public function excel(): EksporExcel
    {
        $beregu = $this->nomor['jenis'] === 'beregu';
        $kolom  = [
            'peringkat'  => ['judul' => 'Peringkat', 'jenis' => 'angka'],
            'nomor_dada' => 'No. Atlet',
            'nama'       => $beregu ? 'Regu' : 'Nama',
            'anggota'    => 'Anggota',
            'sekolah'    => 'Sekolah',
            'prestasi'   => 'Prestasi (' . JenisHasil::LABEL[$this->nomor['jenis_hasil']] . (! empty($this->nomor['waktu_tangan']) ? ' tangan' : '') . ')',
            'keterangan' => 'Keterangan',
        ];

        // Poin multi-lomba (F9-10): prestasi dan poin tiap komponen.
        foreach ($this->nomor['jenis_hasil'] === JenisHasil::MULTI ? $this->nomor['komponen'] : [] as $i => $k) {
            $kolom['komponen_' . $i]      = $k['nama'];
            $kolom['komponen_poin_' . $i] = ['judul' => 'Poin ' . $k['nama'], 'jenis' => 'angka'];
        }

        if ($beregu) {
            unset($kolom['nomor_dada']);
        } else {
            unset($kolom['anggota']);
        }

        $pakai = [];
        $utama = null;

        foreach ($this->babak as $b) {
            $lembar = (new EksporExcel(DaftarPeserta::judulLembar($b['nama'], $pakai), $kolom))->tambahBaris(array_map(static fn (array $h): array => self::kolomRincian($h) + [
                'peringkat'  => $h['peringkat'],
                'nomor_dada' => $h['nomor_dada'],
                'nama'       => $h['nama'],
                'anggota'    => implode(', ', $h['anggota']),
                'sekolah'    => $h['sekolah'],
                'prestasi'   => $h['prestasi_teks'],
                'keterangan' => $h['keterangan'] === null ? ($h['manual'] ? 'Peringkat diubah admin' : '') : $h['keterangan'] . ' (' . Peringkat::KETERANGAN[$h['keterangan']] . ')',
            ], $b['hasil']));

            if ($utama === null) {
                $utama = $lembar;
            } else {
                $utama->tambahLembar($lembar);
            }
        }

        return $utama ?? new EksporExcel('Hasil', $kolom);
    }

    /**
     * @param array<string, mixed> $h satu hasil `HasilLomba::hasilBabak()`
     *
     * @return array<string, int|string|null>
     */
    private static function kolomRincian(array $h): array
    {
        $kolom = [];

        foreach ($h['rincian'] ?? [] as $i => $r) {
            // Komponen kosong ditulis "–" dengan 0 poin (K143).
            $kolom['komponen_' . $i]      = $r['prestasi'] === '' ? '–' : $r['prestasi'];
            $kolom['komponen_poin_' . $i] = $r['poin'];
        }

        return $kolom;
    }
}
