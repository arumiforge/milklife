<?php

namespace App\Libraries;

use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Pembaca data pendaftaran untuk halaman cek dan bukti PDF (Tahap 5).
 *
 * Hanya membaca, lewat pembangun kueri langsung ke tabel migrasi Tahap 1
 * (`pendaftaran`, `penanggung_jawab`, `atlet`, `atlet_nomor`,
 * `pendaftaran_beregu`, ditambah `kelompok_umur` dan `nomor_lomba` untuk
 * nama). Tidak memakai model Tahap 4 (kesepakatan dua sesi, K48).
 *
 * Semua angka uang adalah nilai yang tersimpan saat pendaftaran dibuat
 * (rancangan 6.7), tidak dihitung ulang dari tarif sekarang. Foto penanggung
 * jawab tidak pernah keluar dari kelas ini (rancangan 8).
 *
 * Pemakaian:
 *
 *     $data = new DataPendaftaran();
 *     $satu = $data->cariId('MAC2A0000126');   // larik atau null
 *     $semua = $data->cariNpsn('20300001');    // list, urut dari yang pertama dibuat
 *
 * Bentuk satu pendaftaran (teks belum di-escape, uang belum diformat):
 *
 *     [
 *         'id_pendaftaran'    => 'MAC2A0000126',
 *         'jenjang'           => 'A',
 *         'jenjang_label'     => 'SD/MI',
 *         'npsn'              => '20300001',          // atau null
 *         'nama_sekolah'      => 'SD NEGERI CONTOH 1',
 *         'kabupaten'         => 'Kudus',
 *         'dibuat_pada'       => '2026-11-12 09:15:00', // atau null
 *         'status'            => 'menunggu',          // atau 'lunas'
 *         'status_label'      => 'Menunggu Konfirmasi Pembayaran',
 *         'subtotal_individu' => 300000,
 *         'subtotal_beregu'   => 200000,
 *         'total'             => 500000,
 *         'penanggung_jawab'  => [['nama' => 'Ibu Contoh', 'nomor_wa' => '0812-3456-7890']], // NomorWa::tampilan()
 *         'atlet'             => [[
 *             'nama' => 'BUDI KARANGAN', 'nisn' => '9900000001', 'tanggal_lahir' => '2016-05-01',
 *             'jenis_kelamin' => 'L', 'jenis_kelamin_label' => 'Laki-laki',
 *             'kelompok_umur' => 'KU 10', 'nomor_dada' => null,  // '1001' setelah Lunas
 *             'nomor_lomba' => ['Lari 60 m putra', 'Estafet 4 × 50 m mix'], // NomorLombaModel::namaLengkap()
 *         ]],
 *         'individu'          => [[                 // satu baris per atlet per nomor individu
 *             'nama' => 'BUDI KARANGAN', 'nisn' => '9900000001', 'tanggal_lahir' => '2016-05-01',
 *             'kelompok_umur' => 'KU 10', 'nomor_dada' => null,
 *             'nomor_lomba' => 'Lari 60 m putra', 'biaya' => 150000,
 *         ]],
 *         'beregu'            => [[                 // satu baris per nomor beregu
 *             'nomor_lomba' => 'Estafet 4 × 50 m mix',
 *             'biaya_tim'   => 200000,               // null: pendaftaran susulan yang hanya menambah anggota
 *             'anggota'     => [['nama' => 'BUDI KARANGAN', 'nisn' => '9900000001', 'nomor_dada' => null]],
 *         ]],
 *     ]
 */
final class DataPendaftaran
{
    public const STATUS = [
        'menunggu' => 'Menunggu Konfirmasi Pembayaran',
        'lunas'    => 'Lunas',
    ];

    public const JENIS_KELAMIN = ['L' => 'Laki-laki', 'P' => 'Perempuan'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Satu pendaftaran menurut ID pendaftaran (tanpa membedakan huruf besar-kecil), atau `null`.
     *
     * @return array<string, mixed>|null
     */
    public function cariId(string $id): ?array
    {
        $kepala = $this->db->table('pendaftaran')
            ->where('id_pendaftaran', strtoupper(trim($id)))
            ->get()->getResultArray();

        return $this->lengkapi($kepala)[0] ?? null;
    }

    /**
     * Semua pendaftaran dengan NPSN ini, urut dari yang pertama dibuat.
     *
     * @return list<array<string, mixed>>
     */
    public function cariNpsn(string $npsn): array
    {
        $kepala = $this->db->table('pendaftaran')
            ->where('npsn', trim($npsn))
            ->orderBy('id')
            ->get()->getResultArray();

        return $this->lengkapi($kepala);
    }

    /**
     * Lengkapi baris `pendaftaran` dengan penanggung jawab, atlet, dan tim.
     * Empat kueri untuk semua pendaftaran sekaligus.
     *
     * @param list<array<string, mixed>> $kepala
     *
     * @return list<array<string, mixed>>
     */
    private function lengkapi(array $kepala): array
    {
        if ($kepala === []) {
            return [];
        }

        $ids = array_map(static fn (array $b): int => (int) $b['id'], $kepala);

        $pj = $this->db->table('penanggung_jawab')
            ->select('pendaftaran_id, nama, nomor_wa')
            ->whereIn('pendaftaran_id', $ids)
            ->orderBy('id')
            ->get()->getResultArray();

        $atlet = $this->db->table('atlet a')
            ->select('a.id, a.pendaftaran_id, a.nama, a.nisn, a.tanggal_lahir, a.jenis_kelamin, a.nomor_dada, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->whereIn('a.pendaftaran_id', $ids)
            ->orderBy('a.id')
            ->get()->getResultArray();

        $nomor = $this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.nomor_lomba_id, an.biaya, nl.nama, nl.kategori, nl.jenis, nl.urutan')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->whereIn('a.pendaftaran_id', $ids)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        $tim = $this->db->table('pendaftaran_beregu pb')
            ->select('pb.pendaftaran_id, pb.nomor_lomba_id, pb.biaya_tim, nl.nama, nl.kategori, nl.urutan')
            ->join('nomor_lomba nl', 'nl.id = pb.nomor_lomba_id')
            ->whereIn('pb.pendaftaran_id', $ids)
            ->get()->getResultArray();

        $hasil = [];

        foreach ($kepala as $baris) {
            $hasil[(int) $baris['id']] = [
                'id'                => (int) $baris['id'],
                'id_pendaftaran'    => (string) $baris['id_pendaftaran'],
                'kode_bukti'        => $baris['kode_bukti'] ?? null,
                'jenjang'           => (string) $baris['jenjang'],
                'jenjang_label'     => Jenjang::label($baris['jenjang']) ?? (string) $baris['jenjang'],
                'npsn'              => $baris['npsn'] === null || $baris['npsn'] === '' ? null : (string) $baris['npsn'],
                'nama_sekolah'      => (string) $baris['nama_sekolah'],
                'kabupaten'         => (string) $baris['kabupaten'],
                'dibuat_pada'       => $baris['dibuat_pada'],
                'status'            => (string) $baris['status'],
                'status_label'      => self::STATUS[$baris['status']] ?? (string) $baris['status'],
                'subtotal_individu' => (int) $baris['subtotal_individu'],
                'subtotal_beregu'   => (int) $baris['subtotal_beregu'],
                'total'             => (int) $baris['total'],
                'penanggung_jawab'  => [],
                'atlet'             => [],
                'individu'          => [],
                'beregu'            => [],
            ];
        }

        foreach ($pj as $baris) {
            $hasil[(int) $baris['pendaftaran_id']]['penanggung_jawab'][] = [
                'nama'     => (string) $baris['nama'],
                'nomor_wa' => NomorWa::tampilan((string) $baris['nomor_wa']),
            ];
        }

        // Tim dari pendaftaran_beregu dulu (punya biaya), lalu anggota dari atlet_nomor.
        $regu = [];

        foreach ($tim as $baris) {
            $regu[(int) $baris['pendaftaran_id']][(int) $baris['nomor_lomba_id']] = [
                'nomor_lomba' => NomorLombaModel::namaLengkap($baris),
                'biaya_tim'   => (int) $baris['biaya_tim'],
                'anggota'     => [],
                'urutan'      => [(int) $baris['urutan'], (int) $baris['nomor_lomba_id']],
            ];
        }

        $atletPerId = [];

        foreach ($atlet as $baris) {
            $atletPerId[(int) $baris['id']] = $baris;
        }

        $nomorPerAtlet = [];

        foreach ($nomor as $baris) {
            $a = $atletPerId[(int) $baris['atlet_id']];
            $p = (int) $a['pendaftaran_id'];

            $nomorPerAtlet[(int) $baris['atlet_id']][] = NomorLombaModel::namaLengkap($baris);

            if ($baris['jenis'] === 'beregu') {
                $n = (int) $baris['nomor_lomba_id'];

                $regu[$p][$n] ??= [
                    'nomor_lomba' => NomorLombaModel::namaLengkap($baris),
                    'biaya_tim'   => null,
                    'anggota'     => [],
                    'urutan'      => [(int) $baris['urutan'], $n],
                ];
                $regu[$p][$n]['anggota'][] = [
                    'nama'       => (string) $a['nama'],
                    'nisn'       => (string) $a['nisn'],
                    'nomor_dada' => $a['nomor_dada'],
                ];

                continue;
            }

            $hasil[$p]['individu'][] = [
                'nama'          => (string) $a['nama'],
                'nisn'          => (string) $a['nisn'],
                'tanggal_lahir' => (string) $a['tanggal_lahir'],
                'kelompok_umur' => (string) $a['kelompok_umur'],
                'nomor_dada'    => $a['nomor_dada'],
                'nomor_lomba'   => NomorLombaModel::namaLengkap($baris),
                'biaya'         => $baris['biaya'] === null ? null : (int) $baris['biaya'],
            ];
        }

        foreach ($atletPerId as $id => $a) {
            $hasil[(int) $a['pendaftaran_id']]['atlet'][] = [
                'nama'                => (string) $a['nama'],
                'nisn'                => (string) $a['nisn'],
                'tanggal_lahir'       => (string) $a['tanggal_lahir'],
                'jenis_kelamin'       => (string) $a['jenis_kelamin'],
                'jenis_kelamin_label' => self::JENIS_KELAMIN[$a['jenis_kelamin']] ?? (string) $a['jenis_kelamin'],
                'kelompok_umur'       => (string) $a['kelompok_umur'],
                'nomor_dada'          => $a['nomor_dada'],
                'nomor_lomba'         => $nomorPerAtlet[$id] ?? [],
            ];
        }

        foreach ($regu as $p => $daftar) {
            usort($daftar, static fn (array $x, array $y): int => $x['urutan'] <=> $y['urutan']);

            $hasil[$p]['beregu'] = array_map(static function (array $r): array {
                unset($r['urutan']);

                return $r;
            }, $daftar);
        }

        return array_values($hasil);
    }
}
