<?php

namespace App\Libraries;

use App\Libraries\Pendaftaran\Regu;
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
 *     $satu = $data->cariId('MAC2A0000126');   // larik atau null; ID lama yang sudah digabung memberi pendaftaran gabungannya (K109)
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
 *         'gabungan_dari'     => ['MAC2A0000926'],  // ID lama yang sudah digabung ke sini (K109)
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
 *         'beregu'            => [[                 // satu baris per regu, inti dulu lalu cadangan
 *             'nomor_lomba'  => 'Estafet 4 × 50 m mix Regu A', // Regu::nama(); tanpa "Regu A" bila tunggal
 *             'nama_nomor'   => 'Estafet 4 × 50 m mix',
 *             'regu'         => 'A',
 *             'regu_tunggal' => false,              // true: satu-satunya regu sekolah di nomor ini (K108)
 *             'biaya_tim'   => 200000,               // null: pendaftaran susulan yang hanya menambah anggota
 *             'anggota'     => [['nama' => 'BUDI KARANGAN', 'nisn' => '9900000001', 'nomor_dada' => null, 'peran' => 'inti']],
 *         ]],
 *         'atlet_ditolak'     => [ … ],             // AtletDitolak::untukPendaftaran(), hanya yang belum didaftarkan ulang (K116)
 *     ]
 *
 * Regu tunggal (K108): bila sekolah (NPSN sama, semua pendaftarannya; tanpa
 * NPSN: pendaftaran itu saja) hanya punya satu regu di satu nomor beregu,
 * nama regunya tidak ditulis di `nomor_lomba` regu maupun atlet.
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
        $id     = strtoupper(trim($id));
        $kepala = $this->db->table('pendaftaran')
            ->where('id_pendaftaran', $id)
            ->get()->getResultArray();

        if ($kepala === []) {
            // ID lama dari pendaftaran yang sudah digabung (K109).
            $kepala = $this->db->table('pendaftaran p')
                ->select('p.*')
                ->join('pendaftaran_gabungan g', 'g.pendaftaran_id = p.id')
                ->where('g.id_pendaftaran_lama', $id)
                ->get()->getResultArray();
        }

        return $this->lengkapi($kepala)[0] ?? null;
    }

    /**
     * Pendaftaran gabungan untuk ID dan kode bukti lama (K109), atau `null`.
     * Dipakai alamat bukti lama: kode QR di bukti yang sudah dicetak.
     *
     * @return array{id: int, id_pendaftaran: string, kode_bukti: string|null}|null
     */
    public function cariBuktiLama(string $id, string $kode): ?array
    {
        if ($kode === '') {
            return null;
        }

        $baris = $this->db->table('pendaftaran_gabungan g')
            ->select('g.kode_bukti_lama, p.id, p.id_pendaftaran, p.kode_bukti')
            ->join('pendaftaran p', 'p.id = g.pendaftaran_id')
            ->where('g.id_pendaftaran_lama', strtoupper(trim($id)))
            ->get()->getRowArray();

        if (! is_array($baris) || ! hash_equals((string) ($baris['kode_bukti_lama'] ?? ''), $kode)) {
            return null;
        }

        return ['id' => (int) $baris['id'], 'id_pendaftaran' => (string) $baris['id_pendaftaran'], 'kode_bukti' => $baris['kode_bukti']];
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
            ->select('an.atlet_id, an.nomor_lomba_id, an.biaya, an.regu, an.peran, nl.nama, nl.kategori, nl.jenis, nl.urutan')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->whereIn('a.pendaftaran_id', $ids)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        $tim = $this->db->table('pendaftaran_beregu pb')
            ->select('pb.pendaftaran_id, pb.nomor_lomba_id, pb.regu, pb.biaya_tim, nl.nama, nl.kategori, nl.urutan')
            ->join('nomor_lomba nl', 'nl.id = pb.nomor_lomba_id')
            ->whereIn('pb.pendaftaran_id', $ids)
            ->get()->getResultArray();

        $lama = $this->db->table('pendaftaran_gabungan')
            ->select('pendaftaran_id, id_pendaftaran_lama')
            ->whereIn('pendaftaran_id', $ids)
            ->orderBy('id')
            ->get()->getResultArray();

        $jumlahRegu  = $this->jumlahRegu($kepala);
        $sekolahDari = [];
        $hasil       = [];

        foreach ($kepala as $baris) {
            $sekolahDari[(int) $baris['id']] = self::kunciSekolah($baris);
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
                'gabungan_dari'     => [],
                'penanggung_jawab'  => [],
                'atlet'             => [],
                'individu'          => [],
                'beregu'            => [],
                'atlet_ditolak'     => [],
            ];
        }

        // Atlet yang ditolak panitia dan belum didaftarkan ulang (K116).
        foreach ((new AtletDitolak($this->db))->untukPendaftaran($ids) as $pid => $baris) {
            $hasil[$pid]['atlet_ditolak'] = $baris;
        }

        foreach ($lama as $baris) {
            $hasil[(int) $baris['pendaftaran_id']]['gabungan_dari'][] = (string) $baris['id_pendaftaran_lama'];
        }

        foreach ($pj as $baris) {
            $hasil[(int) $baris['pendaftaran_id']]['penanggung_jawab'][] = [
                'nama'     => (string) $baris['nama'],
                'nomor_wa' => NomorWa::tampilan((string) $baris['nomor_wa']),
            ];
        }

        // Tim dari pendaftaran_beregu dulu (punya biaya), lalu anggota dari atlet_nomor.
        $regu = [];

        // Satu-satunya regu sekolah di nomor itu (K108).
        $tunggal = static fn (int $pendaftaranId, int $nomorId): bool => ($jumlahRegu[$sekolahDari[$pendaftaranId]][$nomorId] ?? 0) <= 1;

        foreach ($tim as $baris) {
            $p = (int) $baris['pendaftaran_id'];
            $n = (int) $baris['nomor_lomba_id'];
            $t = $tunggal($p, $n);

            $regu[$p][Regu::kunci($n, (string) $baris['regu'])] = [
                'nomor_lomba'  => Regu::nama(NomorLombaModel::namaLengkap($baris), (string) $baris['regu'], $t),
                'nama_nomor'   => NomorLombaModel::namaLengkap($baris),
                'regu'         => (string) $baris['regu'],
                'regu_tunggal' => $t,
                'biaya_tim'    => (int) $baris['biaya_tim'],
                'anggota'      => [],
                'urutan'       => [(int) $baris['urutan'], $n, (string) $baris['regu']],
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

            if ($baris['jenis'] === 'beregu') {
                $n     = (int) $baris['nomor_lomba_id'];
                $label = Regu::sah($baris['regu']) ? (string) $baris['regu'] : 'A';
                $peran = $baris['peran'] === Regu::INTI ? Regu::INTI : Regu::CADANGAN;
                $kunci = Regu::kunci($n, $label);
                $t     = $tunggal($p, $n);

                $nomorPerAtlet[(int) $baris['atlet_id']][] = Regu::nama(NomorLombaModel::namaLengkap($baris), $label, $t) . ' (' . Regu::labelPeran($peran) . ')';

                $regu[$p][$kunci] ??= [
                    'nomor_lomba'  => Regu::nama(NomorLombaModel::namaLengkap($baris), $label, $t),
                    'nama_nomor'   => NomorLombaModel::namaLengkap($baris),
                    'regu'         => $label,
                    'regu_tunggal' => $t,
                    'biaya_tim'    => null,
                    'anggota'      => [],
                    'urutan'       => [(int) $baris['urutan'], $n, $label],
                ];
                $regu[$p][$kunci]['anggota'][] = [
                    'nama'       => (string) $a['nama'],
                    'nisn'       => (string) $a['nisn'],
                    'nomor_dada' => $a['nomor_dada'],
                    'peran'      => $peran,
                ];

                continue;
            }

            $nomorPerAtlet[(int) $baris['atlet_id']][] = NomorLombaModel::namaLengkap($baris);

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
                // Inti dulu, lalu cadangan (urutan daftar tetap di dalamnya).
                usort($r['anggota'], static fn (array $x, array $y): int => ($x['peran'] === Regu::INTI ? 0 : 1) <=> ($y['peran'] === Regu::INTI ? 0 : 1));

                return $r;
            }, $daftar);
        }

        return array_values($hasil);
    }

    /**
     * Kunci sekolah untuk menghitung regu: NPSN, atau pendaftaran itu sendiri bila tanpa NPSN.
     *
     * @param array<string, mixed> $baris baris `pendaftaran` (`id`, `npsn`)
     */
    private static function kunciSekolah(array $baris): string
    {
        $npsn = (string) ($baris['npsn'] ?? '');

        return $npsn !== '' ? 'n' . $npsn : 'p' . (int) $baris['id'];
    }

    /**
     * Banyaknya regu tiap sekolah di tiap nomor beregu, dihitung dari semua
     * pendaftaran sekolah itu (K108): kunci sekolah => nomor => jumlah regu.
     *
     * @param list<array<string, mixed>> $kepala
     *
     * @return array<string, array<int, int>>
     */
    private function jumlahRegu(array $kepala): array
    {
        $npsn      = [];
        $tanpaNpsn = [];

        foreach ($kepala as $baris) {
            if ((string) ($baris['npsn'] ?? '') !== '') {
                $npsn[(string) $baris['npsn']] = true;
            } else {
                $tanpaNpsn[] = (int) $baris['id'];
            }
        }

        $saring = function ($kueri) use ($npsn, $tanpaNpsn) {
            return $kueri->groupStart()
                ->whereIn('p.npsn', $npsn === [] ? [''] : array_map('strval', array_keys($npsn)))
                ->orWhereIn('p.id', $tanpaNpsn === [] ? [0] : $tanpaNpsn)
                ->groupEnd();
        };

        $baris = array_merge(
            $saring($this->db->table('atlet_nomor an')
                ->select('p.id, p.npsn, an.nomor_lomba_id, an.regu')
                ->join('atlet a', 'a.id = an.atlet_id')
                ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
                ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
                ->where('nl.jenis', 'beregu'))
                ->distinct()->get()->getResultArray(),
            $saring($this->db->table('pendaftaran_beregu pb')
                ->select('p.id, p.npsn, pb.nomor_lomba_id, pb.regu')
                ->join('pendaftaran p', 'p.id = pb.pendaftaran_id'))
                ->distinct()->get()->getResultArray(),
        );

        $regu = [];

        foreach ($baris as $b) {
            $label = Regu::sah($b['regu']) ? (string) $b['regu'] : 'A';

            $regu[self::kunciSekolah($b)][(int) $b['nomor_lomba_id']][$label] = true;
        }

        return array_map(static fn (array $perNomor): array => array_map('count', $perNomor), $regu);
    }
}
