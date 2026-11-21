<?php

namespace App\Libraries;

use App\Libraries\HariLomba\DaftarPeserta;
use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Data halaman Peserta publik (`/peserta`, F9-12, K140 butir 10).
 *
 * Hanya atlet dari pendaftaran **Lunas**. Isi tiap baris dibatasi ke daftar
 * kunci tetap: nama, sekolah, nomor atlet (BIB), kelompok umur, putra/putri,
 * dan nomor lombanya. NISN, tanggal lahir, foto, dan data ofisial **tidak
 * pernah** dibaca dari basis data di sini, jadi tidak mungkin ikut keluar di
 * HTML maupun JSON.
 *
 * Nomor beregu ditulis dengan nama regunya hanya bila sekolah itu punya lebih
 * dari satu regu di nomor tersebut (K108); sekolah dihitung per NPSN, atau per
 * pendaftaran bila tanpa NPSN.
 *
 *     $peserta = new PesertaPublik();
 *     $saring  = PesertaPublik::saringan($request->getGet());
 *     $hasil   = $peserta->cari($saring);
 *     // ['baris' => [['nama' => 'Budi', 'sekolah' => 'SD …', 'nomor_atlet' => '1001',
 *     //               'kelompok_umur' => 'KU 10', 'jenis_kelamin' => 'Putra',
 *     //               'nomor' => ['Lari 60 m putra', "Kanga's Escape putra Regu B"]]],
 *     //  'total' => 1480, 'halaman' => 1, 'jumlah_halaman' => 74, 'per_halaman' => 20]
 */
final class PesertaPublik
{
    /**
     * Banyak atlet per halaman.
     */
    public const PER_HALAMAN = 20;

    /**
     * Panjang kata pencarian paling banyak, dan banyak kata yang dipakai.
     */
    public const PANJANG_CARI = 60;

    public const KATA_CARI = 5;

    /**
     * Kunci yang boleh keluar per baris. Uji memastikan tidak ada kunci lain.
     */
    public const KUNCI_BARIS = ['nama', 'sekolah', 'nomor_atlet', 'kelompok_umur', 'jenis_kelamin', 'nomor'];

    public const JENIS_KELAMIN = ['L' => 'Putra', 'P' => 'Putri'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Saringan bersih dari isian GET: `q`, `ku`, `nomor`, `jk`, `halaman`.
     * Nilai yang tidak sah dibuang (dianggap tidak disaring).
     *
     * @param array<string, mixed> $masukan
     *
     * @return array{q: string, ku: int|null, nomor: int|null, jk: string|null, halaman: int}
     */
    public static function saringan(array $masukan): array
    {
        $q = is_string($masukan['q'] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', $masukan['q'])) : '';
        $q = mb_substr($q, 0, self::PANJANG_CARI);

        $angka = static function (mixed $nilai): ?int {
            if (! is_string($nilai) && ! is_int($nilai)) {
                return null;
            }

            $nilai = (string) $nilai;

            return preg_match('/^[1-9]\d{0,8}$/', $nilai) === 1 ? (int) $nilai : null;
        };

        $jk = $masukan['jk'] ?? null;

        return [
            'q'       => $q,
            'ku'      => $angka($masukan['ku'] ?? null),
            'nomor'   => $angka($masukan['nomor'] ?? null),
            'jk'      => is_string($jk) && isset(self::JENIS_KELAMIN[$jk]) ? $jk : null,
            'halaman' => $angka($masukan['halaman'] ?? null) ?? 1,
        ];
    }

    /**
     * Satu halaman atlet yang cocok dengan saringan, urut nomor atlet.
     *
     * @param array{q: string, ku: int|null, nomor: int|null, jk: string|null, halaman: int} $saring
     *
     * @return array{baris: list<array{nama: string, sekolah: string, nomor_atlet: string|null, kelompok_umur: string, jenis_kelamin: string, nomor: list<string>}>, total: int, halaman: int, jumlah_halaman: int, per_halaman: int}
     */
    public function cari(array $saring): array
    {
        $total         = $this->dasar($saring)->countAllResults();
        $jumlahHalaman = max(1, (int) ceil($total / self::PER_HALAMAN));
        $halaman       = min(max(1, $saring['halaman']), $jumlahHalaman);

        $atlet = $total === 0 ? [] : $this->dasar($saring)
            ->select('a.id, a.nama, a.nomor_dada, a.jenis_kelamin, p.id AS pendaftaran_id, p.npsn, p.nama_sekolah, ku.nama AS kelompok_umur')
            ->orderBy('a.nomor_dada IS NULL', '', false)
            ->orderBy('a.nomor_dada')
            ->orderBy('a.nama')
            ->orderBy('a.id')
            ->limit(self::PER_HALAMAN, ($halaman - 1) * self::PER_HALAMAN)
            ->get()->getResultArray();

        $nomor = $this->nomorAtlet($atlet);
        $baris = [];

        foreach ($atlet as $a) {
            $baris[] = [
                'nama'          => (string) $a['nama'],
                'sekolah'       => (string) $a['nama_sekolah'],
                'nomor_atlet'   => $a['nomor_dada'] === null ? null : (string) $a['nomor_dada'],
                'kelompok_umur' => (string) $a['kelompok_umur'],
                'jenis_kelamin' => self::JENIS_KELAMIN[$a['jenis_kelamin']] ?? '',
                'nomor'         => $nomor[(int) $a['id']] ?? [],
            ];
        }

        return [
            'baris'          => $baris,
            'total'          => $total,
            'halaman'        => $halaman,
            'jumlah_halaman' => $jumlahHalaman,
            'per_halaman'    => self::PER_HALAMAN,
        ];
    }

    /**
     * Pilihan saringan: kelompok umur dan nomor lombanya yang punya peserta Lunas.
     *
     * @return list<array{id: int, nama: string, nomor: list<array{id: int, nama: string}>}>
     */
    public function pilihan(): array
    {
        $hasil = [];

        foreach ((new DaftarPeserta($this->db))->kelompokUmur() as $ku) {
            $nomor = array_values(array_map(
                static fn (array $n): array => ['id' => $n['id'], 'nama' => $n['nama']],
                array_filter($ku['nomor'], static fn (array $n): bool => $n['peserta'] > 0),
            ));

            if ($nomor !== []) {
                $hasil[] = ['id' => $ku['id'], 'nama' => $ku['nama'], 'nomor' => $nomor];
            }
        }

        return $hasil;
    }

    /**
     * Pembangun kueri atlet Lunas dengan saringan, tanpa kolom pilihan.
     *
     * @param array{q: string, ku: int|null, nomor: int|null, jk: string|null, halaman: int} $saring
     */
    private function dasar(array $saring): BaseBuilder
    {
        $kueri = $this->db->table('atlet a')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('p.status', 'lunas');

        if ($saring['ku'] !== null) {
            $kueri->where('a.kelompok_umur_id', $saring['ku']);
        }

        if ($saring['jk'] !== null) {
            $kueri->where('a.jenis_kelamin', $saring['jk']);
        }

        if ($saring['nomor'] !== null) {
            $kueri->whereIn('a.id', static fn (BaseBuilder $sub): BaseBuilder => $sub->select('atlet_id')->from('atlet_nomor')->where('nomor_lomba_id', $saring['nomor']));
        }

        $kata = array_slice(array_values(array_filter(explode(' ', $saring['q']), static fn (string $k): bool => $k !== '')), 0, self::KATA_CARI);

        foreach ($kata as $k) {
            $kueri->groupStart()
                ->like('a.nama', $k)
                ->orLike('p.nama_sekolah', $k)
                ->orLike('a.nomor_dada', $k, 'after')
                ->groupEnd();
        }

        return $kueri;
    }

    /**
     * Nama nomor lomba tiap atlet, urut seperti pengaturan nomor lomba.
     *
     * @param list<array<string, mixed>> $atlet
     *
     * @return array<int, list<string>> id atlet => nama nomor
     */
    private function nomorAtlet(array $atlet): array
    {
        if ($atlet === []) {
            return [];
        }

        $sekolah = [];

        foreach ($atlet as $a) {
            $sekolah[(int) $a['id']] = self::kunciSekolah($a['npsn'], (int) $a['pendaftaran_id']);
        }

        $baris = $this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.nomor_lomba_id, an.regu, nl.nama, nl.kategori, nl.jenis')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->whereIn('an.atlet_id', array_keys($sekolah))
            ->orderBy('ku.urutan')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        $beregu = array_values(array_unique(array_map(
            static fn (array $b): int => (int) $b['nomor_lomba_id'],
            array_filter($baris, static fn (array $b): bool => $b['jenis'] === 'beregu'),
        )));
        $banyakRegu = $this->banyakRegu($beregu);
        $hasil      = [];

        foreach ($baris as $b) {
            $nama = NomorLombaModel::namaLengkap($b);

            if ($b['jenis'] === 'beregu') {
                $regu    = Regu::sah($b['regu']) ? (string) $b['regu'] : 'A';
                $tunggal = ($banyakRegu[(int) $b['nomor_lomba_id'] . '|' . $sekolah[(int) $b['atlet_id']]] ?? 1) <= 1;
                $nama    = Regu::nama($nama, $regu, $tunggal);
            }

            $hasil[(int) $b['atlet_id']][] = $nama;
        }

        return $hasil;
    }

    /**
     * Banyak regu berbeda tiap sekolah di nomor beregu, dari pendaftaran Lunas (K108).
     *
     * @param list<int> $nomorId
     *
     * @return array<string, int> "<nomor>|<kunci sekolah>" => banyak regu
     */
    private function banyakRegu(array $nomorId): array
    {
        if ($nomorId === []) {
            return [];
        }

        $baris = $this->db->table('atlet_nomor an')
            ->select('an.nomor_lomba_id, an.regu, p.id AS pendaftaran_id, p.npsn')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('p.status', 'lunas')
            ->whereIn('an.nomor_lomba_id', $nomorId)
            ->distinct()
            ->get()->getResultArray();

        $regu = [];

        foreach ($baris as $b) {
            $kunci = (int) $b['nomor_lomba_id'] . '|' . self::kunciSekolah($b['npsn'], (int) $b['pendaftaran_id']);

            $regu[$kunci][Regu::sah($b['regu']) ? (string) $b['regu'] : 'A'] = true;
        }

        return array_map('count', $regu);
    }

    private static function kunciSekolah(mixed $npsn, int $pendaftaranId): string
    {
        return is_string($npsn) && $npsn !== '' ? 'n' . $npsn : 'p' . $pendaftaranId;
    }
}
