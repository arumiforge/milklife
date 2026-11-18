<?php

namespace App\Libraries\Hasil;

use App\Libraries\Panel\EksporExcel;

/**
 * Unduhan hasil satu nomor lomba (F8-04, K124 butir 8): PDF dan Excel,
 * semua babak, Final paling akhir. Isinya dari `HasilLomba::susunan()`, jadi
 * sama dengan halaman.
 *
 *     $berkas = new BerkasHasil($nomor, $hasil->susunan($nomor));
 *     $berkas->namaBerkas('pdf'); // 'HasilLari60mPutra_KU10_MilkLifeAthleticsChallenge_2026.pdf'
 */
final class BerkasHasil
{
    /**
     * @param array<string, mixed>       $nomor  Hasil `HasilLomba::nomor()`.
     * @param list<array<string, mixed>> $babak  Hasil `HasilLomba::susunan()`.
     */
    public function __construct(private readonly array $nomor, private readonly array $babak)
    {
    }

    public function judul(): string
    {
        return 'Hasil ' . $this->nomor['nama_lengkap'] . ' – ' . $this->nomor['nama_kelompok_umur'];
    }

    public function namaBerkas(string $akhiran): string
    {
        return DokumenPdf::namaBerkas(['Hasil ' . $this->nomor['nama_lengkap'], (string) $this->nomor['nama_kelompok_umur']], $akhiran);
    }

    public function pdf(): string
    {
        return DokumenPdf::buat($this->judul(), view('admin/hasil/_isi_pdf', ['nomor' => $this->nomor, 'babak' => $this->babak], ['saveData' => false]));
    }

    public function excel(): EksporExcel
    {
        $beregu = $this->nomor['jenis'] === 'beregu';
        $kolom  = [
            'peringkat'  => ['judul' => 'Peringkat', 'jenis' => 'angka'],
            'nomor_dada' => 'No. Atlet',
            'nama'       => $beregu ? 'Regu' : 'Nama',
            'sekolah'    => 'Sekolah',
            'prestasi'   => Prestasi::JUDUL[$this->nomor['jenis_hasil']] ?? 'Prestasi',
            'keterangan' => 'Keterangan',
        ];

        if ($beregu) {
            unset($kolom['nomor_dada']);
            $kolom['anggota'] = 'Anggota';
        }

        $pertama = null;

        foreach ($this->babak === [] ? [['nama' => 'Hasil', 'baris' => []]] : $this->babak as $i => $b) {
            $lembar = new EksporExcel(($i + 1) . ' ' . $b['nama'], $kolom);
            $lembar->tambahBaris(array_map(static fn (array $h): array => [
                'peringkat'  => $h['peringkat'],
                'nomor_dada' => $h['nomor_dada'],
                'nama'       => $h['nama'],
                'sekolah'    => $h['sekolah'],
                'prestasi'   => $h['prestasi_teks'],
                'keterangan' => self::keterangan($h),
                'anggota'    => implode(', ', array_map(static fn (array $a): string => trim($a['nomor_dada'] . ' ' . $a['nama']), $h['anggota'])),
            ], $b['baris']));

            $pertama === null ? $pertama = $lembar : $pertama->tambahLembar($lembar);
        }

        return $pertama;
    }

    /**
     * Keterangan baris: DNS/DNF/DQ dengan artinya, atau tanda koreksi admin.
     *
     * @param array<string, mixed> $h
     */
    public static function keterangan(array $h): string
    {
        $kode = (string) ($h['keterangan'] ?? '');

        if ($kode !== '') {
            return $kode . ' (' . (Peringkat::KETERANGAN[$kode] ?? '') . ')';
        }

        return ($h['peringkat_manual'] ?? false) ? 'Peringkat diubah admin' : '';
    }
}
