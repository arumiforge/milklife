<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\Regu;
use CodeIgniter\Database\BaseConnection;

/**
 * Data tersimpan satu pendaftaran untuk Ubah pendaftaran (bagian dari
 * `UbahPendaftaran`, F7-30): baris pendaftaran, penanggung jawab, atlet,
 * nomor, regu, dan biaya regu, ditambah induk dengan kelompok umur dan nomor
 * lomba nonaktif yang masih dipakai (K98).
 */
final class DataTersimpan
{
    public function __construct(
        private readonly BaseConnection $db,
        private readonly DataIndukPendaftaran $data,
        private readonly FotoPenanggungJawab $foto,
    ) {
    }

    /**
     * Data tersimpan satu pendaftaran, atau `null`.
     *
     * `regu`: atlet => nomor beregu => ['regu' => 'A', 'peran' => 'inti']; `beregu`: "nomor:regu" => biaya regu.
     *
     * @return array{pendaftaran: array<string, mixed>, penanggung_jawab: array<int, array<string, mixed>>, atlet: array<int, array<string, mixed>>, nomor: array<int, array<int, int|null>>, regu: array<int, array<int, array{regu: string|null, peran: string|null}>>, beregu: array<string, int>}|null
     */
    public function baca(int $id): ?array
    {
        $p = $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();

        if (! is_array($p)) {
            return null;
        }

        $pj = [];

        foreach ($this->db->table('penanggung_jawab')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray() as $b) {
            $pj[(int) $b['id']] = $b;
        }

        $atlet = [];

        foreach ($this->db->table('atlet')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray() as $b) {
            $atlet[(int) $b['id']] = $b;
        }

        $nomor = [];
        $baris = $this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.nomor_lomba_id, an.biaya, an.regu, an.peran, nl.jenis')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        $regu = [];

        foreach ($baris as $b) {
            $nomor[(int) $b['atlet_id']][(int) $b['nomor_lomba_id']] = $b['biaya'] === null ? null : (int) $b['biaya'];

            if ($b['jenis'] === 'beregu') {
                $regu[(int) $b['atlet_id']][(int) $b['nomor_lomba_id']] = ['regu' => $b['regu'], 'peran' => $b['peran']];
            }
        }

        $beregu = [];

        foreach ($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->orderBy('nomor_lomba_id')->orderBy('regu')->get()->getResultArray() as $b) {
            $beregu[Regu::kunci((int) $b['nomor_lomba_id'], (string) $b['regu'])] = (int) $b['biaya_tim'];
        }

        return ['pendaftaran' => $p, 'penanggung_jawab' => $pj, 'atlet' => $atlet, 'nomor' => $nomor, 'regu' => $regu, 'beregu' => $beregu];
    }

    /**
     * Induk aktif ditambah kelompok umur dan nomor lomba nonaktif yang masih
     * dipakai pendaftaran ini, serta peta atlet tersimpan untuk
     * `PemeriksaPendaftaran` (K98).
     *
     * @param array<string, mixed> $lama
     *
     * @return array{0: array<string, mixed>, 1: array<int, array{kelompok_umur_id: int, nomor: list<int>}>}
     */
    public function indukDenganSimpanan(array $lama): array
    {
        $simpanan = [];

        foreach ($lama['atlet'] as $id => $atlet) {
            $simpanan[(int) $id] = [
                'kelompok_umur_id' => (int) $atlet['kelompok_umur_id'],
                'nomor'            => array_map('intval', array_keys($lama['nomor'][(int) $id] ?? [])),
            ];
        }

        $nonaktif = $this->data->nonaktif(
            array_values(array_unique(array_column($simpanan, 'kelompok_umur_id'))),
            array_values(array_unique(array_merge([], ...array_column($simpanan, 'nomor')))),
        );

        return [$this->data->induk() + [
            'kelompok_umur_nonaktif' => $nonaktif['kelompok_umur'],
            'nomor_lomba_nonaktif'   => $nonaktif['nomor_lomba'],
        ], $simpanan];
    }

    /**
     * Data sekolah untuk `PemeriksaPendaftaran` tanpa pendaftaran ini (`DataIndukPendaftaran::dataSekolah()`).
     *
     * @return array<string, mixed>
     */
    public function dataSekolah(?string $npsn, int $pendaftaranId): array
    {
        return $this->data->dataSekolah($npsn, $pendaftaranId);
    }

    /**
     * NISN kiriman yang sudah dipakai pendaftaran lain.
     *
     * @param list<string> $nisn
     *
     * @return list<string>
     */
    public function nisnLain(int $pendaftaranId, array $nisn): array
    {
        if ($nisn === []) {
            return [];
        }

        $baris = $this->db->table('atlet')->select('nisn')
            ->whereIn('nisn', $nisn)
            ->where('pendaftaran_id !=', $pendaftaranId)
            ->get()->getResultArray();

        return array_map(static fn (array $b): string => (string) $b['nisn'], $baris);
    }

    /**
     * Apakah berkas foto penanggung jawab tersimpan ada di disk.
     */
    public function adaFoto(mixed $jalur): bool
    {
        $penuh = $this->foto->jalurPenuh(is_string($jalur) ? $jalur : null);

        return $penuh !== null && is_file($penuh);
    }
}
