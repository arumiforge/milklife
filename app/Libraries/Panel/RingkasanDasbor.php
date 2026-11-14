<?php

namespace App\Libraries\Panel;

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pengunjung;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Angka tambahan dan data grafik dasbor admin (K102), di samping
 * `Rekap::ringkasan()`.
 *
 *     $r = new RingkasanDasbor();
 *     $r->angka();   // kartu ringkasan tambahan, lihat bentuknya di bawah
 *     $r->grafik();  // data semua grafik, siap diubah ke JSON untuk ApexCharts
 *
 * Semua angka dibaca langsung dari tabel saat dasbor dibuka; tidak ada yang
 * disimpan terpisah. `$hariIni` (bawaan: hari ini WIB) bisa diganti di uji.
 */
final class RingkasanDasbor
{
    /**
     * Jumlah hari pada grafik tren pendaftaran dan pengunjung.
     */
    public const HARI_TREN = 30;

    private BaseConnection $db;

    private string $hariIni;

    public function __construct(?BaseConnection $db = null, ?string $hariIni = null)
    {
        $this->db      = $db ?? db_connect();
        $this->hariIni = Pengunjung::tanggalSah($hariIni, Time::now(config('App')->appTimezone)->format('Y-m-d'));
    }

    /**
     * Angka kartu tambahan.
     *
     * @return array{
     *     pendaftaran_lunas: int, pendaftaran_menunggu: int, perlu_diperiksa: int,
     *     atlet_putra: int, atlet_putri: int, nomor_dada: int, kabupaten: int,
     *     pendaftaran_hari_ini: int, atlet_hari_ini: int, rata_atlet: float,
     *     pengunjung_hari_ini: int, kunjungan_hari_ini: int
     * }
     */
    public function angka(): array
    {
        $p = $this->baca(
            "SELECT SUM(status = 'lunas') AS lunas, SUM(status = 'menunggu') AS menunggu, COUNT(DISTINCT NULLIF(kabupaten, '')) AS kabupaten,"
            . ' SUM(dibuat_pada >= ? AND dibuat_pada < ?) AS hari_ini, COUNT(*) AS semua FROM {pendaftaran}',
            [$this->hariIni . ' 00:00:00', Pengunjung::geser($this->hariIni, 1) . ' 00:00:00'],
        )[0];

        $a = $this->baca(
            "SELECT COUNT(*) AS semua, SUM(a.jenis_kelamin = 'L') AS putra, SUM(a.jenis_kelamin = 'P') AS putri,"
            . " SUM(a.nomor_dada IS NOT NULL AND a.nomor_dada <> '') AS nomor_dada,"
            . ' SUM(a.dibuat_pada >= ? AND a.dibuat_pada < ?) AS hari_ini FROM {atlet} a',
            [$this->hariIni . ' 00:00:00', Pengunjung::geser($this->hariIni, 1) . ' 00:00:00'],
        )[0];

        // Menunggu dan sudah punya bukti bayar: tinggal diperiksa lalu ditandai Lunas.
        $periksa = $this->baca(
            "SELECT COUNT(DISTINCT p.id) AS n FROM {pendaftaran} p JOIN {bukti_bayar} b ON b.pendaftaran_id = p.id WHERE p.status = 'menunggu'",
        )[0]['n'];

        $pengunjung = $this->pengunjungHariIni();

        return [
            'pendaftaran_lunas'    => (int) $p['lunas'],
            'pendaftaran_menunggu' => (int) $p['menunggu'],
            'perlu_diperiksa'      => (int) $periksa,
            'atlet_putra'          => (int) $a['putra'],
            'atlet_putri'          => (int) $a['putri'],
            'nomor_dada'           => (int) $a['nomor_dada'],
            'kabupaten'            => (int) $p['kabupaten'],
            'pendaftaran_hari_ini' => (int) $p['hari_ini'],
            'atlet_hari_ini'       => (int) $a['hari_ini'],
            'rata_atlet'           => (int) $p['semua'] > 0 ? round((int) $a['semua'] / (int) $p['semua'], 1) : 0.0,
            'pengunjung_hari_ini'  => $pengunjung['unik'],
            'kunjungan_hari_ini'   => $pengunjung['kunjungan'],
        ];
    }

    /**
     * Data grafik dasbor.
     *
     * @return array{
     *     tren: array{tanggal: list<string>, pendaftaran: list<int>, atlet: list<int>, tagihan: list<int>},
     *     status: array{label: list<string>, jumlah: list<int>, rupiah: list<int>},
     *     kabupaten: array{label: list<string>, putra: list<int>, putri: list<int>},
     *     kelompok_umur: array{label: list<string>, putra: list<int>, putri: list<int>},
     *     jenjang: array{label: list<string>, atlet: list<int>, pendaftaran: list<int>},
     *     nomor_lomba: array{label: list<string>, peserta: list<int>},
     *     jam_daftar: array{label: list<string>, pendaftaran: list<int>},
     *     pengunjung: array{tanggal: list<string>, kunjungan: list<int>, unik: list<int>}
     * }
     */
    public function grafik(): array
    {
        return [
            'tren'          => $this->tren(),
            'status'        => $this->status(),
            'kabupaten'     => $this->kabupaten(),
            'kelompok_umur' => $this->kelompokUmur(),
            'jenjang'       => $this->jenjang(),
            'nomor_lomba'   => $this->nomorLomba(),
            'jam_daftar'    => $this->jamDaftar(),
            'pengunjung'    => $this->pengunjung(),
        ];
    }

    /**
     * Pendaftaran, atlet, dan tagihan per hari selama `HARI_TREN` hari terakhir.
     */
    private function tren(): array
    {
        $dari   = Pengunjung::geser($this->hariIni, -(self::HARI_TREN - 1));
        $sampai = Pengunjung::geser($this->hariIni, 1);
        $hari   = [];

        for ($t = $dari; $t <= $this->hariIni; $t = Pengunjung::geser($t, 1)) {
            $hari[$t] = ['pendaftaran' => 0, 'atlet' => 0, 'tagihan' => 0];
        }

        foreach ($this->baca(
            'SELECT DATE(dibuat_pada) AS t, COUNT(*) AS n, COALESCE(SUM(total), 0) AS tagihan FROM {pendaftaran}'
            . ' WHERE dibuat_pada >= ? AND dibuat_pada < ? GROUP BY DATE(dibuat_pada)',
            [$dari . ' 00:00:00', $sampai . ' 00:00:00'],
        ) as $b) {
            if (isset($hari[$b['t']])) {
                $hari[$b['t']]['pendaftaran'] = (int) $b['n'];
                $hari[$b['t']]['tagihan']     = (int) $b['tagihan'];
            }
        }

        foreach ($this->baca(
            'SELECT DATE(dibuat_pada) AS t, COUNT(*) AS n FROM {atlet} WHERE dibuat_pada >= ? AND dibuat_pada < ? GROUP BY DATE(dibuat_pada)',
            [$dari . ' 00:00:00', $sampai . ' 00:00:00'],
        ) as $b) {
            if (isset($hari[$b['t']])) {
                $hari[$b['t']]['atlet'] = (int) $b['n'];
            }
        }

        return [
            'tanggal'     => array_keys($hari),
            'pendaftaran' => array_column($hari, 'pendaftaran'),
            'atlet'       => array_column($hari, 'atlet'),
            'tagihan'     => array_column($hari, 'tagihan'),
        ];
    }

    private function status(): array
    {
        $baris = [];

        foreach ($this->baca('SELECT status, COUNT(*) AS n, COALESCE(SUM(total), 0) AS rupiah FROM {pendaftaran} GROUP BY status') as $b) {
            $baris[$b['status']] = $b;
        }

        return [
            'label'  => ['Lunas', 'Menunggu'],
            'jumlah' => [(int) ($baris['lunas']['n'] ?? 0), (int) ($baris['menunggu']['n'] ?? 0)],
            'rupiah' => [(int) ($baris['lunas']['rupiah'] ?? 0), (int) ($baris['menunggu']['rupiah'] ?? 0)],
        ];
    }

    /**
     * Atlet putra dan putri per kabupaten; lima kabupaten peserta selalu
     * tampil, kabupaten lain (data lama) ditambahkan di belakang.
     */
    private function kabupaten(): array
    {
        $isi = array_fill_keys(Kabupaten::semua(), ['putra' => 0, 'putri' => 0]);

        foreach ($this->baca(
            "SELECT COALESCE(NULLIF(p.kabupaten, ''), 'Lainnya') AS kabupaten, SUM(a.jenis_kelamin = 'L') AS putra, SUM(a.jenis_kelamin = 'P') AS putri"
            . ' FROM {atlet} a JOIN {pendaftaran} p ON p.id = a.pendaftaran_id GROUP BY 1',
        ) as $b) {
            $nama       = Kabupaten::baku($b['kabupaten']) ?? (string) $b['kabupaten'];
            $isi[$nama] = [
                'putra' => ($isi[$nama]['putra'] ?? 0) + (int) $b['putra'],
                'putri' => ($isi[$nama]['putri'] ?? 0) + (int) $b['putri'],
            ];
        }

        return ['label' => array_keys($isi), 'putra' => array_column($isi, 'putra'), 'putri' => array_column($isi, 'putri')];
    }

    private function kelompokUmur(): array
    {
        $baris = $this->baca(
            "SELECT ku.nama, ku.aktif, (SELECT COUNT(*) FROM {atlet} a WHERE a.kelompok_umur_id = ku.id AND a.jenis_kelamin = 'L') AS putra,"
            . " (SELECT COUNT(*) FROM {atlet} a WHERE a.kelompok_umur_id = ku.id AND a.jenis_kelamin = 'P') AS putri"
            . ' FROM {kelompok_umur} ku ORDER BY ku.urutan, ku.id',
        );

        // Kelompok umur nonaktif tanpa atlet tidak perlu tampil.
        $baris = array_values(array_filter($baris, static fn (array $b): bool => (int) $b['aktif'] === 1 || (int) $b['putra'] + (int) $b['putri'] > 0));

        return [
            'label' => array_map(static fn (array $b): string => (string) $b['nama'], $baris),
            'putra' => array_map(static fn (array $b): int => (int) $b['putra'], $baris),
            'putri' => array_map(static fn (array $b): int => (int) $b['putri'], $baris),
        ];
    }

    private function jenjang(): array
    {
        $isi = array_fill_keys(Jenjang::kode(), ['atlet' => 0, 'pendaftaran' => 0]);

        foreach ($this->baca(
            'SELECT p.jenjang, COUNT(DISTINCT p.id) AS pendaftaran, COUNT(a.id) AS atlet FROM {pendaftaran} p'
            . ' LEFT JOIN {atlet} a ON a.pendaftaran_id = p.id GROUP BY p.jenjang',
        ) as $b) {
            if (isset($isi[$b['jenjang']])) {
                $isi[$b['jenjang']] = ['atlet' => (int) $b['atlet'], 'pendaftaran' => (int) $b['pendaftaran']];
            }
        }

        return [
            'label'       => array_values(Jenjang::semua()),
            'atlet'       => array_column($isi, 'atlet'),
            'pendaftaran' => array_column($isi, 'pendaftaran'),
        ];
    }

    /**
     * Sepuluh nomor lomba dengan peserta terbanyak.
     */
    private function nomorLomba(): array
    {
        $baris = $this->baca(
            "SELECT CONCAT(ku.nama, ' · ', nl.nama, ' ', nl.kategori) AS label, COUNT(*) AS n FROM {atlet_nomor} an"
            . ' JOIN {nomor_lomba} nl ON nl.id = an.nomor_lomba_id JOIN {kelompok_umur} ku ON ku.id = nl.kelompok_umur_id'
            . ' GROUP BY nl.id, ku.nama, nl.nama, nl.kategori ORDER BY n DESC, MIN(ku.urutan), MIN(nl.urutan) LIMIT 10',
        );

        return [
            'label'   => array_map(static fn (array $b): string => (string) $b['label'], $baris),
            'peserta' => array_map(static fn (array $b): int => (int) $b['n'], $baris),
        ];
    }

    /**
     * Jam berapa sekolah biasanya mendaftar (semua hari).
     */
    private function jamDaftar(): array
    {
        $jam = array_fill(0, 24, 0);

        foreach ($this->baca('SELECT HOUR(dibuat_pada) AS j, COUNT(*) AS n FROM {pendaftaran} WHERE dibuat_pada IS NOT NULL GROUP BY HOUR(dibuat_pada)') as $b) {
            $jam[(int) $b['j']] = (int) $b['n'];
        }

        return [
            'label'       => array_map(static fn (int $j): string => sprintf('%02d.00', $j), range(0, 23)),
            'pendaftaran' => $jam,
        ];
    }

    private function pengunjung(): array
    {
        try {
            $hari = (new Pengunjung($this->db))->perHari(Pengunjung::geser($this->hariIni, -(self::HARI_TREN - 1)), $this->hariIni);
        } catch (Throwable) {
            $hari = [];
        }

        return [
            'tanggal'   => array_column($hari, 'tanggal'),
            'kunjungan' => array_column($hari, 'kunjungan'),
            'unik'      => array_column($hari, 'unik'),
        ];
    }

    /**
     * @return array{kunjungan: int, unik: int}
     */
    private function pengunjungHariIni(): array
    {
        try {
            $hari = (new Pengunjung($this->db))->perHari($this->hariIni, $this->hariIni)[0];

            return ['kunjungan' => $hari['kunjungan'], 'unik' => $hari['unik']];
        } catch (Throwable) {
            return ['kunjungan' => 0, 'unik' => 0];
        }
    }

    /**
     * @param list<mixed> $ikat
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, array $ikat = []): array
    {
        $sql = (string) preg_replace_callback('/\{(\w+)\}/', fn (array $m): string => $this->db->prefixTable($m[1]), $sql);

        return $this->db->query($sql, $ikat)->getResultArray();
    }
}
