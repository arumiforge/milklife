<?php

namespace App\Libraries\HariLomba;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Cetak nomor atlet (BIB) massal (F8-03, K124, asumsi 5 K125).
 *
 * Hanya atlet dari pendaftaran Lunas yang sudah punya nomor atlet, urut
 * nomor atlet. Satu BIB = setengah A4 mendatar, dua BIB per halaman A4 tegak
 * (panitia memotongnya di garis tengah). Isi tiap BIB: nomor atlet besar,
 * nama atlet, nama sekolah, kelompok umur, dan nama acara.
 *
 *     $bib = new CetakBib();
 *     $bib->kelompokUmur();      // [['id' => 2, 'nama' => 'KU 10', 'atlet' => 40], ...]
 *     $bib->atlet(2);            // atlet KU 10; null = semua kelompok umur
 *     $bib->pdf(2);              // isi berkas PDF
 *     $bib->namaBerkas(2);       // 'BIB_KU10_MilkLifeAthleticsChallenge_2026.pdf'
 */
final class CetakBib
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Kelompok umur berurutan beserta banyak atlet ber-BIB dari pendaftaran Lunas.
     *
     * @return list<array{id: int, nama: string, atlet: int}>
     */
    public function kelompokUmur(): array
    {
        $jumlah = array_column($this->kueriAtlet(null)->select('a.kelompok_umur_id, COUNT(*) AS jumlah')->groupBy('a.kelompok_umur_id')->get()->getResultArray(), 'jumlah', 'kelompok_umur_id');

        return array_map(static fn (array $ku): array => [
            'id'    => (int) $ku['id'],
            'nama'  => (string) $ku['nama'],
            'atlet' => (int) ($jumlah[$ku['id']] ?? 0),
        ], $this->db->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray());
    }

    public function namaKelompokUmur(int $id): ?string
    {
        $baris = $this->db->table('kelompok_umur')->select('nama')->where('id', $id)->get()->getRow();

        return $baris === null ? null : (string) $baris->nama;
    }

    /**
     * @return list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}>
     */
    public function atlet(?int $kelompokUmurId): array
    {
        return array_map(static fn (array $a): array => [
            'nomor_dada'    => (string) $a['nomor_dada'],
            'nama'          => (string) $a['nama'],
            'sekolah'       => (string) $a['nama_sekolah'],
            'kelompok_umur' => (string) $a['kelompok_umur'],
        ], $this->kueriAtlet($kelompokUmurId)
            ->select('a.nomor_dada, a.nama, p.nama_sekolah, ku.nama AS kelompok_umur')
            ->orderBy('a.nomor_dada')
            ->get()->getResultArray());
    }

    public function namaBerkas(?int $kelompokUmurId): string
    {
        $ku = $kelompokUmurId === null ? 'Semua' : PdfHariLomba::rapat($this->namaKelompokUmur($kelompokUmurId));

        return 'BIB_' . $ku . PdfHariLomba::akhiranBerkas() . '.pdf';
    }

    /**
     * @param list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}> $atlet
     */
    public static function html(array $atlet, string $namaAcara, string $seriTahun, bool $logo): string
    {
        return view('admin/hari_lomba/bib_pdf', [
            'atlet'     => $atlet,
            'namaAcara' => $namaAcara,
            'seriTahun' => $seriTahun,
            'logo'      => $logo,
        ], ['saveData' => false]);
    }

    public function pdf(?int $kelompokUmurId): string
    {
        $mpdf = PdfHariLomba::mpdf([
            'format'        => 'A4',
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
        ]);
        $logo = PdfHariLomba::logoPng();

        if ($logo !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }

        $mpdf->SetTitle('Nomor Atlet (BIB)');
        $mpdf->WriteHTML(self::html($this->atlet($kelompokUmurId), PdfHariLomba::namaAcara(), PdfHariLomba::seriTahun(), $logo !== null));

        return $mpdf->Output('', 'S');
    }

    /**
     * Atlet ber-BIB dari pendaftaran Lunas.
     */
    private function kueriAtlet(?int $kelompokUmurId): BaseBuilder
    {
        $kueri = $this->db->table('atlet a')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('p.status', 'lunas')
            ->where('a.nomor_dada IS NOT NULL', null, false);

        if ($kelompokUmurId !== null) {
            $kueri->where('a.kelompok_umur_id', $kelompokUmurId);
        }

        return $kueri;
    }
}
