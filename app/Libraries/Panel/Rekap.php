<?php

namespace App\Libraries\Panel;

use App\Libraries\Jenjang;
use CodeIgniter\Database\BaseConnection;
use InvalidArgumentException;

/**
 * Lima rekap panitia dan angka dasbor (F6-06; rencana Tahap 6 "Rekap").
 *
 *     $rekap = (new Rekap())->susun('sekolah', 'lunas');
 *     $rekap['kolom'];  // kunci => ['judul' => …, 'jenis' => teks|angka|rupiah], bentuk `EksporExcel`
 *     $rekap['baris'];  // nilai siap tampil; rupiah tetap bilangan bulat
 *     $rekap['jumlah']; // baris jumlah (kolom pertama "Jumlah"), atau null
 *
 * Halaman, Excel, dan PDF memakai hasil yang sama, jadi angkanya selalu sama.
 *
 * - Saringan status (K60): `null` = semua, `lunas`, atau `menunggu`, menurut
 *   status pendaftaran tiap atlet. Rekap keuangan selalu memuat keduanya.
 * - Satu tim = satu sekolah di satu nomor beregu: anggota dari pendaftaran
 *   susulan ber-NPSN sama ikut tim yang sama (K07). Sekolah tanpa NPSN
 *   dihitung per pendaftaran.
 * - Rekap keuangan punya bagian kedua `tim_tanpa_pembayar` (`kolom`,
 *   `baris`) dari `TimTanpaPembayar` (K68), ikut di halaman, Excel (lembar
 *   kedua), dan PDF; `ringkasan()['tim_tanpa_pembayar']` jumlahnya.
 */
final class Rekap
{
    /**
     * @var array<string, string> jenis (bagian alamat) => judul
     */
    public const JENIS = [
        'nomor-lomba'   => 'Peserta per Nomor Lomba',
        'tim-beregu'    => 'Tim Beregu',
        'sekolah'       => 'Per Sekolah',
        'kelompok-umur' => 'Per Kelompok Umur',
        'keuangan'      => 'Keuangan',
    ];

    /**
     * @var array<string, string> kode => label
     */
    public const STATUS = ['lunas' => 'Lunas', 'menunggu' => 'Menunggu'];

    /**
     * Kunci satu sekolah: NPSN, atau id pendaftaran bila tanpa NPSN.
     */
    private const SEKOLAH = "COALESCE(NULLIF(p.npsn, ''), CONCAT('#', p.id))";

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @return array{kolom: array<string, array{judul: string, jenis: string}>, baris: list<array<string, mixed>>, jumlah: array<string, mixed>|null}
     */
    public function susun(string $jenis, ?string $status = null): array
    {
        $status = isset(self::STATUS[$status]) ? $status : null;

        return match ($jenis) {
            'nomor-lomba'   => $this->nomorLomba($status),
            'tim-beregu'    => $this->timBeregu($status),
            'sekolah'       => $this->sekolah($status),
            'kelompok-umur' => $this->kelompokUmur($status),
            'keuangan'      => $this->keuangan(),
            default         => throw new InvalidArgumentException("Jenis rekap tidak dikenal: {$jenis}."),
        };
    }

    /**
     * Angka dasbor: jumlah pendaftaran, atlet, tim, dan sekolah, serta
     * tagihan, lunas, dan belum dibayar (rupiah).
     *
     * @return array{pendaftaran: int, atlet: int, tim: int, sekolah: int, tagihan: int, lunas: int, belum: int, tim_tanpa_pembayar: int}
     */
    public function ringkasan(): array
    {
        $p = $this->baca(
            'SELECT COUNT(*) AS pendaftaran, COUNT(DISTINCT ' . self::SEKOLAH . ') AS sekolah, COALESCE(SUM(p.total), 0) AS tagihan,'
            . " COALESCE(SUM(CASE WHEN p.status = 'lunas' THEN p.total ELSE 0 END), 0) AS lunas FROM {pendaftaran} p",
        )[0];

        $atlet = $this->baca('SELECT COUNT(*) AS n FROM {atlet}')[0]['n'];
        $tim   = $this->baca(
            'SELECT COUNT(DISTINCT an.nomor_lomba_id, ' . self::SEKOLAH . ') AS n FROM {atlet_nomor} an'
            . " JOIN {atlet} a ON a.id = an.atlet_id JOIN {pendaftaran} p ON p.id = a.pendaftaran_id JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id AND nl.jenis = 'beregu'",
        )[0]['n'];

        return [
            'pendaftaran' => (int) $p['pendaftaran'],
            'atlet'       => (int) $atlet,
            'tim'         => (int) $tim,
            'sekolah'     => (int) $p['sekolah'],
            'tagihan'     => (int) $p['tagihan'],
            'lunas'       => (int) $p['lunas'],
            'belum'       => (int) $p['tagihan'] - (int) $p['lunas'],

            'tim_tanpa_pembayar' => (new TimTanpaPembayar($this->db))->jumlah(),
        ];
    }

    private function nomorLomba(?string $status): array
    {
        $baris = $this->baca(
            'SELECT ku.nama AS kelompok_umur, CONCAT(nl.nama, \' \', nl.kategori) AS nomor_lomba, a.nomor_dada, a.nama, a.nisn,'
            . ' p.nama_sekolah, p.kabupaten, p.status'
            . ' FROM {atlet_nomor} an JOIN {atlet} a ON a.id = an.atlet_id JOIN {pendaftaran} p ON p.id = a.pendaftaran_id'
            . ' JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id JOIN {kelompok_umur} ku ON ku.id = nl.kelompok_umur_id'
            . self::saring($status, 'WHERE')
            . ' ORDER BY ku.urutan, ku.id, nl.urutan, nl.id, a.nomor_dada IS NULL, a.nomor_dada, a.id',
            $status,
        );

        return [
            'kolom' => self::kolom([
                'kelompok_umur' => 'Kelompok Umur',
                'nomor_lomba'   => 'Nomor Lomba',
                'nomor_dada'    => 'Nomor Dada',
                'nama'          => 'Nama',
                'nisn'          => 'NISN',
                'nama_sekolah'  => 'Sekolah',
                'kabupaten'     => 'Kabupaten',
                'status'        => 'Status',
            ]),
            'baris'  => array_map(self::labelStatus(...), $baris),
            'jumlah' => null,
        ];
    }

    private function timBeregu(?string $status): array
    {
        $baris = $this->baca(
            'SELECT MAX(ku.nama) AS kelompok_umur, MAX(CONCAT(nl.nama, \' \', nl.kategori)) AS nomor_lomba,'
            . ' MAX(p.nama_sekolah) AS nama_sekolah, MAX(p.kabupaten) AS kabupaten, COUNT(*) AS jumlah_anggota,'
            . " GROUP_CONCAT(CONCAT_WS(' ', a.nomor_dada, a.nama) ORDER BY a.nomor_dada IS NULL, a.nomor_dada, a.id SEPARATOR ', ') AS anggota"
            . ' FROM {atlet_nomor} an JOIN {atlet} a ON a.id = an.atlet_id JOIN {pendaftaran} p ON p.id = a.pendaftaran_id'
            . " JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id AND nl.jenis = 'beregu' JOIN {kelompok_umur} ku ON ku.id = nl.kelompok_umur_id"
            . self::saring($status, 'WHERE')
            . ' GROUP BY nl.id, ' . self::SEKOLAH
            . ' ORDER BY MAX(ku.urutan), MAX(ku.id), MAX(nl.urutan), nl.id, nama_sekolah',
            $status,
        );

        return [
            'kolom' => self::kolom([
                'kelompok_umur'  => 'Kelompok Umur',
                'nomor_lomba'    => 'Nomor Lomba',
                'nama_sekolah'   => 'Sekolah',
                'kabupaten'      => 'Kabupaten',
                'jumlah_anggota' => ['judul' => 'Jumlah Anggota', 'jenis' => 'angka'],
                'anggota'        => 'Anggota',
            ]),
            'baris'  => $baris,
            'jumlah' => null,
        ];
    }

    private function sekolah(?string $status): array
    {
        $baris = $this->baca(
            'SELECT ' . self::SEKOLAH . ' AS kunci, MAX(p.npsn) AS npsn, MAX(p.nama_sekolah) AS nama_sekolah, MAX(p.jenjang) AS jenjang,'
            . ' MAX(p.kabupaten) AS kabupaten, COUNT(*) AS jumlah_pendaftaran, COALESCE(SUM(ja.n), 0) AS jumlah_atlet, SUM(p.total) AS tagihan'
            . ' FROM {pendaftaran} p LEFT JOIN (SELECT pendaftaran_id, COUNT(*) AS n FROM {atlet} GROUP BY pendaftaran_id) ja ON ja.pendaftaran_id = p.id'
            . self::saring($status, 'WHERE')
            . ' GROUP BY kunci ORDER BY nama_sekolah, MIN(p.id)',
            $status,
        );

        $tim = array_column($this->baca(
            'SELECT ' . self::SEKOLAH . ' AS kunci, COUNT(DISTINCT an.nomor_lomba_id) AS n FROM {atlet_nomor} an'
            . ' JOIN {atlet} a ON a.id = an.atlet_id JOIN {pendaftaran} p ON p.id = a.pendaftaran_id'
            . " JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id AND nl.jenis = 'beregu'"
            . self::saring($status, 'WHERE') . ' GROUP BY kunci',
            $status,
        ), 'n', 'kunci');

        $baris = array_map(static fn (array $b): array => [
            'jenjang'   => Jenjang::label($b['jenjang']) ?? $b['jenjang'],
            'jumlah_tim' => (int) ($tim[$b['kunci']] ?? 0),
        ] + $b, $baris);

        return [
            'kolom' => self::kolom([
                'npsn'               => 'NPSN',
                'nama_sekolah'       => 'Sekolah',
                'jenjang'            => 'Jenjang',
                'kabupaten'          => 'Kabupaten',
                'jumlah_pendaftaran' => ['judul' => 'Pendaftaran', 'jenis' => 'angka'],
                'jumlah_atlet'       => ['judul' => 'Atlet', 'jenis' => 'angka'],
                'jumlah_tim'         => ['judul' => 'Tim', 'jenis' => 'angka'],
                'tagihan'            => ['judul' => 'Tagihan', 'jenis' => 'rupiah'],
            ]),
            'baris'  => $baris,
            'jumlah' => $baris === [] ? null : ['npsn' => 'Jumlah'] + self::jumlahkan($baris, ['jumlah_pendaftaran', 'jumlah_atlet', 'jumlah_tim', 'tagihan']),
        ];
    }

    private function kelompokUmur(?string $status): array
    {
        $atlet = 'SELECT COUNT(*) FROM {atlet} a JOIN {pendaftaran} p ON p.id = a.pendaftaran_id WHERE a.kelompok_umur_id = ku.id';

        $baris = $this->baca(
            "SELECT ku.nama, ({$atlet} AND a.jenis_kelamin = 'L'" . self::saring($status) . ") AS putra,"
            . " ({$atlet} AND a.jenis_kelamin = 'P'" . self::saring($status) . ') AS putri,'
            . ' (SELECT COUNT(DISTINCT an.nomor_lomba_id, ' . self::SEKOLAH . ') FROM {atlet_nomor} an'
            . ' JOIN {atlet} a ON a.id = an.atlet_id JOIN {pendaftaran} p ON p.id = a.pendaftaran_id'
            . " JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id AND nl.jenis = 'beregu'"
            . ' WHERE nl.kelompok_umur_id = ku.id' . self::saring($status) . ') AS tim'
            . ' FROM {kelompok_umur} ku ORDER BY ku.urutan, ku.id',
            $status,
        );

        $baris = array_map(static fn (array $b): array => ['jumlah_atlet' => (int) $b['putra'] + (int) $b['putri']] + $b, $baris);

        return [
            'kolom' => self::kolom([
                'nama'         => 'Kelompok Umur',
                'putra'        => ['judul' => 'Atlet Putra', 'jenis' => 'angka'],
                'putri'        => ['judul' => 'Atlet Putri', 'jenis' => 'angka'],
                'jumlah_atlet' => ['judul' => 'Jumlah Atlet', 'jenis' => 'angka'],
                'tim'          => ['judul' => 'Tim', 'jenis' => 'angka'],
            ]),
            'baris'  => $baris,
            'jumlah' => $baris === [] ? null : ['nama' => 'Jumlah'] + self::jumlahkan($baris, ['putra', 'putri', 'jumlah_atlet', 'tim']),
        ];
    }

    private function keuangan(): array
    {
        $baris = array_map(static fn (array $b): array => [
            'tanggal' => tanggal_id((string) $b['dibuat_pada']),
            'lunas'   => $b['status'] === 'lunas' ? (int) $b['total'] : 0,
            'belum'   => $b['status'] === 'lunas' ? 0 : (int) $b['total'],
        ] + self::labelStatus($b), $this->baca(
            'SELECT p.id_pendaftaran, p.nama_sekolah, p.kabupaten, p.dibuat_pada, p.subtotal_individu, p.subtotal_beregu, p.total, p.status'
            . ' FROM {pendaftaran} p ORDER BY p.id',
        ));

        return [
            'kolom' => self::kolom([
                'id_pendaftaran'    => 'ID Pendaftaran',
                'nama_sekolah'      => 'Sekolah',
                'kabupaten'         => 'Kabupaten',
                'tanggal'           => 'Tanggal Daftar',
                'subtotal_individu' => ['judul' => 'Individu', 'jenis' => 'rupiah'],
                'subtotal_beregu'   => ['judul' => 'Beregu', 'jenis' => 'rupiah'],
                'total'             => ['judul' => 'Tagihan', 'jenis' => 'rupiah'],
                'status'            => 'Status',
                'lunas'             => ['judul' => 'Lunas', 'jenis' => 'rupiah'],
                'belum'             => ['judul' => 'Belum Dibayar', 'jenis' => 'rupiah'],
            ]),
            'baris'  => $baris,
            'jumlah' => $baris === [] ? null : ['id_pendaftaran' => 'Jumlah'] + self::jumlahkan($baris, ['subtotal_individu', 'subtotal_beregu', 'total', 'lunas', 'belum']),

            'tim_tanpa_pembayar' => [
                'kolom' => self::kolom([
                    'kelompok_umur'  => 'Kelompok Umur',
                    'nomor_lomba'    => 'Nomor Lomba',
                    'nama_sekolah'   => 'Sekolah',
                    'kabupaten'      => 'Kabupaten',
                    'jumlah_anggota' => ['judul' => 'Anggota', 'jenis' => 'angka'],
                    'tarif'          => ['judul' => 'Biaya Tim Belum Ditagih', 'jenis' => 'rupiah'],
                    'pendaftaran'    => 'Pendaftaran Anggota',
                ]),
                'baris' => array_map(static fn (array $t): array => [
                    'pendaftaran' => implode('; ', array_map(static fn (array $p): string => $p['id_pendaftaran']
                        . ' (' . (self::STATUS[$p['status']] ?? $p['status']) . ', ' . $p['jumlah_anggota'] . ' anggota)', $t['pendaftaran'])),
                ] + $t, (new TimTanpaPembayar($this->db))->daftar()),
            ],
        ];
    }

    /**
     * Potongan SQL saringan status (`:status:` diikat oleh `baca()`).
     */
    private static function saring(?string $status, string $awalan = 'AND'): string
    {
        return $status === null ? '' : " {$awalan} p.status = :status:";
    }

    /**
     * @param array<string, array{judul: string, jenis?: string}|string> $kolom
     *
     * @return array<string, array{judul: string, jenis: string}>
     */
    private static function kolom(array $kolom): array
    {
        return array_map(static fn (array|string $k): array => is_string($k) ? ['judul' => $k, 'jenis' => 'teks'] : $k, $kolom);
    }

    /**
     * @param list<array<string, mixed>> $baris
     * @param list<string>               $kunci
     *
     * @return array<string, int>
     */
    private static function jumlahkan(array $baris, array $kunci): array
    {
        return array_combine($kunci, array_map(static fn (string $k): int => array_sum(array_map('intval', array_column($baris, $k))), $kunci));
    }

    /**
     * @param array<string, mixed> $baris
     *
     * @return array<string, mixed>
     */
    private static function labelStatus(array $baris): array
    {
        return ['status' => self::STATUS[$baris['status']] ?? $baris['status']] + $baris;
    }

    /**
     * Jalankan kueri; `{tabel}` diganti nama tabel berawalan.
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, ?string $status = null): array
    {
        $sql = (string) preg_replace_callback('/\{(\w+)\}/', fn (array $m): string => $this->db->prefixTable($m[1]), $sql);

        return $this->db->query($sql, $status === null ? [] : ['status' => $status])->getResultArray();
    }
}
