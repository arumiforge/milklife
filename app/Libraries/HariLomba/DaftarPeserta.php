<?php

namespace App\Libraries\HariLomba;

use App\Libraries\Pendaftaran\Regu;
use App\Libraries\Panel\EksporExcel;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Daftar peserta per nomor lomba untuk pemanggilan atlet dan juri (F8-03, K124).
 *
 * Hanya atlet dari pendaftaran Lunas. Nomor individu: satu baris per atlet,
 * urut nomor atlet (BIB). Nomor beregu: satu kelompok per regu (sekolah per
 * NPSN, atau per pendaftaran bila tanpa NPSN, ditambah nama regu), anggota
 * inti dulu lalu cadangan; regu diurutkan menurut nomor atlet terkecilnya.
 *
 *     $daftar = new DaftarPeserta();
 *     $daftar->kelompokUmur();  // [['id' => 2, 'nama' => 'KU 10', 'nomor' => [['id' => 10, 'nama' => 'Lari 60 m putra', 'jenis' => 'individu', 'peserta' => 12], ...]], ...]
 *     $satu = $daftar->susun(10);
 *     // ['id' => 10, 'nama' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'jenis' => 'individu',
 *     //  'peserta' => [['nomor_dada' => '2001', 'nama' => '…', 'sekolah' => '…', 'hadir' => true, 'peran' => null]],
 *     //  'regu' => []]      // beregu: [['nama' => 'SD … – Regu A', 'sekolah' => '…', 'regu' => 'A', 'anggota' => [baris seperti peserta]]]
 */
final class DaftarPeserta
{
    /**
     * Kolom Excel daftar peserta. Kolom regu dan peran hanya untuk nomor beregu.
     *
     * @var array<string, string>
     */
    public const KOLOM_EXCEL = [
        'no'         => 'No',
        'nomor_dada' => 'No. Atlet',
        'nama'       => 'Nama',
        'sekolah'    => 'Sekolah',
        'regu'       => 'Regu',
        'peran'      => 'Peran',
        'hadir'      => 'Hadir',
        'catatan'    => 'Catatan juri',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Kelompok umur berurutan beserta nomor lombanya dan banyak peserta Lunas.
     * Kelompok umur tanpa nomor lomba dilewati.
     *
     * @return list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, peserta: int}>}>
     */
    public function kelompokUmur(): array
    {
        $jumlah = array_column($this->db->table('atlet_nomor an')
            ->select('an.nomor_lomba_id, COUNT(*) AS jumlah')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('p.status', 'lunas')
            ->groupBy('an.nomor_lomba_id')
            ->get()->getResultArray(), 'jumlah', 'nomor_lomba_id');

        $nomor = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori, jenis')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $n) {
            $nomor[(int) $n['kelompok_umur_id']][] = [
                'id'      => (int) $n['id'],
                'nama'    => NomorLombaModel::namaLengkap($n),
                'jenis'   => (string) $n['jenis'],
                'peserta' => (int) ($jumlah[$n['id']] ?? 0),
            ];
        }

        $hasil = [];

        foreach ($this->db->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $ku) {
            if (isset($nomor[(int) $ku['id']])) {
                $hasil[] = ['id' => (int) $ku['id'], 'nama' => (string) $ku['nama'], 'nomor' => $nomor[(int) $ku['id']]];
            }
        }

        return $hasil;
    }

    /**
     * Daftar peserta satu nomor lomba, atau `null` bila nomornya tidak ada.
     *
     * @return array{id: int, nama: string, kelompok_umur: string, jenis: string, peserta: list<array<string, mixed>>, regu: list<array<string, mixed>>}|null
     */
    public function susun(int $nomorLombaId): ?array
    {
        $nomor = $this->db->table('nomor_lomba nl')
            ->select('nl.id, nl.nama, nl.kategori, nl.jenis, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->where('nl.id', $nomorLombaId)
            ->get()->getRowArray();

        if (! is_array($nomor)) {
            return null;
        }

        $baris = $this->db->table('atlet_nomor an')
            ->select('a.nomor_dada, a.nama, a.hadir_pada, an.regu, an.peran, p.id AS pendaftaran_id, p.npsn, p.nama_sekolah')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('an.nomor_lomba_id', $nomorLombaId)
            ->where('p.status', 'lunas')
            ->orderBy('a.nomor_dada IS NULL', '', false)->orderBy('a.nomor_dada')->orderBy('a.nama')
            ->get()->getResultArray();

        $hasil = [
            'id'            => (int) $nomor['id'],
            'nama'          => NomorLombaModel::namaLengkap($nomor),
            'kelompok_umur' => (string) $nomor['kelompok_umur'],
            'jenis'         => (string) $nomor['jenis'],
            'peserta'       => [],
            'regu'          => [],
        ];

        $peserta = static fn (array $b): array => [
            'nomor_dada' => $b['nomor_dada'],
            'nama'       => (string) $b['nama'],
            'sekolah'    => (string) $b['nama_sekolah'],
            'hadir'      => $b['hadir_pada'] !== null,
            'peran'      => $b['peran'] === null ? null : (Regu::PERAN[$b['peran']] ?? null),
        ];

        if ($nomor['jenis'] !== 'beregu') {
            $hasil['peserta'] = array_map($peserta, $baris);

            return $hasil;
        }

        $regu = [];

        foreach ($baris as $b) {
            $label = Regu::sah($b['regu']) ? (string) $b['regu'] : 'A';
            $kunci = ($b['npsn'] !== null && $b['npsn'] !== '' ? 'n' . $b['npsn'] : 'p' . $b['pendaftaran_id']) . ':' . $label;

            $regu[$kunci] ??= [
                'nama'    => (string) $b['nama_sekolah'] . ' – ' . Regu::label($label),
                'sekolah' => (string) $b['nama_sekolah'],
                'regu'    => $label,
                'anggota' => ['inti' => [], 'cadangan' => []],
            ];
            $regu[$kunci]['anggota'][$b['peran'] === Regu::INTI ? 'inti' : 'cadangan'][] = $peserta($b);
        }

        foreach ($regu as &$satu) {
            $satu['anggota'] = [...$satu['anggota']['inti'], ...$satu['anggota']['cadangan']];
        }
        unset($satu);

        // Baris sudah urut nomor atlet, jadi regu tersusun menurut anggota pertamanya;
        // regu yang anggotanya tanpa nomor atlet ikut di akhir.
        $hasil['regu'] = array_values($regu);

        return $hasil;
    }

    /**
     * Daftar peserta semua nomor lomba satu kelompok umur, berurutan.
     *
     * @return list<array<string, mixed>>
     */
    public function susunKelompokUmur(int $kelompokUmurId): array
    {
        $id = array_map('intval', array_column($this->db->table('nomor_lomba')->select('id')->where('kelompok_umur_id', $kelompokUmurId)->orderBy('urutan')->orderBy('id')->get()->getResultArray(), 'id'));

        return array_values(array_filter(array_map(fn (int $i): ?array => $this->susun($i), $id)));
    }

    /**
     * Baris Excel satu nomor lomba; nomor beregu ditulis per regu.
     *
     * @param array<string, mixed> $daftar hasil `susun()`
     *
     * @return list<array<string, mixed>>
     */
    public static function barisExcel(array $daftar): array
    {
        $baris = [];

        if ($daftar['jenis'] !== 'beregu') {
            foreach ($daftar['peserta'] as $i => $p) {
                $baris[] = ['no' => $i + 1, 'nomor_dada' => $p['nomor_dada'], 'nama' => $p['nama'], 'sekolah' => $p['sekolah'], 'hadir' => $p['hadir'] ? 'Hadir' : '', 'catatan' => ''];
            }

            return $baris;
        }

        foreach ($daftar['regu'] as $regu) {
            foreach ($regu['anggota'] as $i => $p) {
                $baris[] = [
                    'no' => $i + 1, 'nomor_dada' => $p['nomor_dada'], 'nama' => $p['nama'], 'sekolah' => $p['sekolah'],
                    'regu' => Regu::label($regu['regu']), 'peran' => $p['peran'], 'hadir' => $p['hadir'] ? 'Hadir' : '', 'catatan' => '',
                ];
            }
        }

        return $baris;
    }

    /**
     * Excel satu lembar per nomor lomba.
     *
     * @param list<array<string, mixed>> $semua hasil `susun()`
     */
    public static function excel(array $semua): EksporExcel
    {
        $pakai = [];
        $utama = null;

        foreach ($semua as $daftar) {
            $kolom = self::KOLOM_EXCEL;

            if ($daftar['jenis'] !== 'beregu') {
                unset($kolom['regu'], $kolom['peran']);
            }

            $kolom['no'] = ['judul' => 'No', 'jenis' => 'angka'];

            $lembar = (new EksporExcel(self::judulLembar($daftar['kelompok_umur'] . ' ' . $daftar['nama'], $pakai), $kolom))->tambahBaris(self::barisExcel($daftar));

            if ($utama === null) {
                $utama = $lembar;
            } else {
                $utama->tambahLembar($lembar);
            }
        }

        return $utama ?? new EksporExcel('Daftar peserta', ['no' => 'No']);
    }

    /**
     * Nama lembar Excel: tanpa tanda yang dilarang, paling panjang 31 huruf, tidak kembar.
     *
     * @param array<string, true> $pakai nama yang sudah dipakai (diperbarui)
     */
    public static function judulLembar(string $judul, array &$pakai): string
    {
        $dasar = trim((string) preg_replace('/\s+/u', ' ', str_replace(['*', ':', '/', '\\', '?', '[', ']', "'"], ' ', $judul)));
        $dasar = $dasar === '' ? 'Nomor' : mb_substr($dasar, 0, 31);
        $nama  = $dasar;

        for ($i = 2; isset($pakai[mb_strtolower($nama)]); $i++) {
            $nama = mb_substr($dasar, 0, 31 - strlen(' ' . $i)) . ' ' . $i;
        }

        $pakai[mb_strtolower($nama)] = true;

        return $nama;
    }
}
