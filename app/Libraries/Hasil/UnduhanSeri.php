<?php

namespace App\Libraries\Hasil;

use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\HariLomba\PdfHariLomba;
use App\Libraries\Panel\EksporExcel;

/**
 * Unduhan daftar seri dan lintasan (*start list*, F9-08) untuk pertemuan
 * teknik dan juri: satu nomor (semua babak yang sudah dibagi) atau satu hari
 * (babak terjadwal hari itu, urut jam).
 *
 * - PDF A4 tegak: tiap babak satu bagian, tiap seri satu tabel (lintasan,
 *   nomor atlet, nama, sekolah) dengan kolom Prestasi kosong untuk juri.
 *   Babak yang belum dikunci diberi tanda "Belum dikunci".
 * - Excel: satu lembar per babak, kolom seri dan lintasan.
 * - Nomor acara babak (F11-07) di kepala tiap babak dan kolom Acara Excel.
 *
 *     $unduhan = new UnduhanSeri('Lari 60 m putra – KU 10', $seri->untukUnduhan([12, 13]));
 *     $unduhan->pdf();
 *     $unduhan->excel()->unduh(UnduhanSeri::namaBerkas('Lari 60 m putra KU 10', 'xlsx'));
 */
final class UnduhanSeri
{
    /**
     * @param string                     $judul  judul kepala, misalnya nama nomor atau hari
     * @param list<array<string, mixed>> $daftar `SeriLintasan::untukUnduhan()`
     */
    public function __construct(private readonly string $judul, private readonly array $daftar)
    {
    }

    public static function namaBerkas(string $judul, string $akhiran): string
    {
        return 'Daftar_Seri_' . PdfHariLomba::rapat($judul) . PdfHariLomba::akhiranBerkas() . '.' . $akhiran;
    }

    public function html(bool $logo): string
    {
        return view('seri/pdf', [
            'judul'     => $this->judul,
            'daftar'    => $this->daftar,
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

        $mpdf->SetTitle('Daftar seri ' . $this->judul);
        $mpdf->SetFooter('{PAGENO} / {nbpg}');
        $mpdf->WriteHTML($this->html($logo !== null));

        return $mpdf->Output('', 'S');
    }

    public function excel(): EksporExcel
    {
        $kolom = [
            'nomor'      => 'Nomor',
            'babak'      => 'Babak',
            'jadwal'     => 'Jadwal',
            'seri'       => ['judul' => 'Seri', 'jenis' => 'angka'],
            'lintasan'   => ['judul' => 'Lintasan/Urutan', 'jenis' => 'angka'],
            'nomor_dada' => 'No. Atlet',
            'nama'       => 'Nama/Regu',
            'anggota'    => 'Anggota',
            'sekolah'    => 'Sekolah',
            'prestasi'   => 'Prestasi',
        ];

        // Nomor acara babak (F11-07), hanya bila ada babak yang punya nomor.
        if (array_filter(array_map(static fn (array $d): ?int => $d['babak']['nomor_acara'] ?? null, $this->daftar)) !== []) {
            $kolom = ['acara' => ['judul' => 'Acara', 'jenis' => 'angka']] + $kolom;
        }

        $pakai = [];
        $utama = null;

        foreach ($this->daftar as $d) {
            $b      = $d['babak'];
            $jadwal = self::jadwal($b);
            $baris  = [];

            foreach ($d['seri'] as $seri => $peserta) {
                foreach ($peserta as $p) {
                    $baris[] = [
                        'acara'      => $b['nomor_acara'] ?? null,
                        'nomor'      => $b['nomor']['nama'] . ' – ' . $b['nomor']['kelompok_umur'],
                        'babak'      => $b['nama'],
                        'jadwal'     => $jadwal,
                        'seri'       => $seri === 0 ? null : $seri,
                        'lintasan'   => $p['lintasan'],
                        'nomor_dada' => $p['nomor_dada'],
                        'nama'       => $p['nama'],
                        'anggota'    => implode(', ', $p['anggota']),
                        'sekolah'    => $p['sekolah'],
                        'prestasi'   => $p['prestasi_teks'],
                    ];
                }
            }

            $lembar = (new EksporExcel(DaftarPeserta::judulLembar($b['nomor']['kelompok_umur'] . ' ' . $b['nomor']['nama'] . ' ' . $b['nama'], $pakai), $kolom))->tambahBaris($baris);

            if ($utama === null) {
                $utama = $lembar;
            } else {
                $utama->tambahLembar($lembar);
            }
        }

        return $utama ?? new EksporExcel('Daftar seri', $kolom);
    }

    /**
     * "Sabtu, 5 Desember 2026 07:30 WIB · Lintasan utama", kosong bila belum dijadwalkan.
     *
     * @param array<string, mixed> $babak `SeriLintasan::babak()`
     */
    public static function jadwal(array $babak): string
    {
        if ($babak['tanggal'] === null) {
            return '';
        }

        $teks = JadwalPertandingan::namaHari($babak['tanggal']) . ($babak['jam'] === null ? '' : ' ' . $babak['jam'] . ' WIB');

        return $babak['lokasi'] === '' ? $teks : $teks . ' · ' . $babak['lokasi'];
    }
}
