<?php

namespace App\Libraries\Panel;

use App\Libraries\Jenjang;
use App\Libraries\NomorWa;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Models\NomorLombaModel;
use App\Models\PendaftaranModel;
use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Data pendaftaran untuk panel admin (F6-02): tabel `/admin/pendaftaran`,
 * Excel-nya, dan rincian satu pendaftaran.
 *
 * Hanya membaca. Semua angka uang adalah nilai tersimpan (rancangan 6.7).
 * Teks belum di-escape.
 *
 * Tabel (lihat `TabelSisiServer`):
 *
 *     $data = new DataPanelPendaftaran();
 *     $data->tabel()->jawab($data->pembuat(), $this->request->getGet());       // JSON
 *     $data->tabel()->semuaBaris($data->pembuat(), $this->request->getGet());  // baris Excel
 *
 * Kunci baris tabel: `id`, `id_pendaftaran`, `tanggal` (`Y-m-d H:i:s`),
 * `nama_sekolah`, `npsn`, `jenjang` (label), `kabupaten`, `jumlah_atlet`,
 * `subtotal_individu`, `subtotal_beregu`, `total`, `status` (`lunas`/
 * `menunggu`), `status_label`, `waktu_lunas`, `alamat` (rincian).
 * Saringan: `status`, `jenjang` (A/B/C), `kabupaten`.
 *
 * Rincian: `rincian($id)` (bentuknya di docblock metode itu).
 */
final class DataPanelPendaftaran
{
    /**
     * Kolom Excel: kunci baris => judul (dan jenis).
     */
    public const KOLOM_EXCEL = [
        'id_pendaftaran'    => 'ID Pendaftaran',
        'tanggal'           => 'Tanggal Daftar',
        'nama_sekolah'      => 'Sekolah',
        'npsn'              => 'NPSN',
        'jenjang'           => 'Jenjang',
        'kabupaten'         => 'Kabupaten/Kota',
        'jumlah_atlet'      => ['judul' => 'Jumlah Atlet', 'jenis' => 'angka'],
        'subtotal_individu' => ['judul' => 'Subtotal Individu', 'jenis' => 'rupiah'],
        'subtotal_beregu'   => ['judul' => 'Subtotal Beregu', 'jenis' => 'rupiah'],
        'total'             => ['judul' => 'Total', 'jenis' => 'rupiah'],
        'status_label'      => 'Status',
        'waktu_lunas'       => 'Waktu Lunas',
    ];

    private const JUMLAH_ATLET = '(SELECT COUNT(*) FROM atlet ja WHERE ja.pendaftaran_id = p.id)';

    private BaseConnection $db;

    private FotoPenanggungJawab $foto;

    public function __construct(?BaseConnection $db = null, ?FotoPenanggungJawab $foto = null)
    {
        $this->db   = $db ?? db_connect();
        $this->foto = $foto ?? new FotoPenanggungJawab();
    }

    /**
     * Definisi tabel: kolom yang boleh dicari dan diurutkan, saringan, dan
     * bentuk baris.
     */
    public function tabel(): TabelSisiServer
    {
        $tabel = new TabelSisiServer(
            kolom: [
                'id_pendaftaran' => ['sql' => 'p.id', 'cari' => false],
                'tanggal'        => 'p.dibuat_pada',
                'nama_sekolah'   => ['sql' => 'p.nama_sekolah', 'cari' => true],
                'npsn'           => ['sql' => 'p.npsn', 'cari' => true],
                'jenjang'        => 'p.jenjang',
                'kabupaten'      => 'p.kabupaten',
                'jumlah_atlet'   => self::JUMLAH_ATLET,
                'total'          => 'p.total',
                'status'         => 'p.status',
                // Dicari, tidak tampil sebagai kolom sendiri.
                'cari_id'        => ['sql' => 'p.id_pendaftaran', 'cari' => true, 'urut' => false],
                'alamat'         => ['urut' => false],
            ],
            saringan: [
                'status'    => ['sql' => 'p.status', 'nilai' => array_keys(PendaftaranModel::STATUS)],
                'jenjang'   => ['sql' => 'p.jenjang', 'nilai' => Jenjang::kode()],
                'kabupaten' => ['sql' => 'p.kabupaten', 'nilai' => Kabupaten::semua()],
            ],
            urutBawaan: ['p.id' => 'desc'],
        );

        return $tabel->bentukBaris(static fn (array $baris): array => self::bentukBaris($baris));
    }

    /**
     * Pembuat kueri tabel; dipanggil berulang oleh `TabelSisiServer`.
     *
     * @return Closure(): BaseBuilder
     */
    public function pembuat(): Closure
    {
        $db = $this->db;

        return static fn (): BaseBuilder => $db->table('pendaftaran p')->select(
            'p.id, p.id_pendaftaran, p.dibuat_pada, p.nama_sekolah, p.npsn, p.jenjang, p.kabupaten, '
            . 'p.subtotal_individu, p.subtotal_beregu, p.total, p.status, p.waktu_lunas, '
            . self::JUMLAH_ATLET . ' AS jumlah_atlet',
            false,
        );
    }

    /**
     * @param array<string, mixed> $baris Baris mentah dari `pembuat()`.
     *
     * @return array<string, mixed>
     */
    public static function bentukBaris(array $baris): array
    {
        $status = (string) $baris['status'];

        return [
            'id'                => (int) $baris['id'],
            'id_pendaftaran'    => (string) $baris['id_pendaftaran'],
            'tanggal'           => $baris['dibuat_pada'],
            'nama_sekolah'      => (string) $baris['nama_sekolah'],
            'npsn'              => $baris['npsn'] === null || $baris['npsn'] === '' ? null : (string) $baris['npsn'],
            'jenjang'           => Jenjang::label($baris['jenjang']) ?? (string) $baris['jenjang'],
            'kabupaten'         => (string) $baris['kabupaten'],
            'jumlah_atlet'      => (int) $baris['jumlah_atlet'],
            'subtotal_individu' => (int) $baris['subtotal_individu'],
            'subtotal_beregu'   => (int) $baris['subtotal_beregu'],
            'total'             => (int) $baris['total'],
            'status'            => $status,
            'status_label'      => PendaftaranModel::STATUS[$status] ?? $status,
            'waktu_lunas'       => $baris['waktu_lunas'],
            'alamat'            => site_url('admin/pendaftaran/' . (int) $baris['id']),
        ];
    }

    /**
     * Rincian satu pendaftaran, atau `null` bila tidak ada.
     *
     * Bentuk (teks belum di-escape, uang belum diformat):
     *
     *     [
     *         'id' => 5, 'id_pendaftaran' => 'MAC2A0000526', 'jenjang' => 'A', 'jenjang_label' => 'SD/MI',
     *         'npsn' => '99999911', 'nama_sekolah' => 'SD …', 'kabupaten' => 'Kudus',
     *         'dibuat_pada' => '2026-11-12 09:15:00', 'diubah_pada' => '…',
     *         'status' => 'menunggu', 'status_label' => 'Menunggu Konfirmasi Pembayaran', 'waktu_lunas' => null,
     *         'subtotal_individu' => 450000, 'subtotal_beregu' => 0, 'total' => 450000,
     *         'penanggung_jawab' => [[
     *             'id' => 7, 'nama' => 'Ibu …', 'nomor_wa' => '6281200000001', 'nomor_wa_tampil' => '0812-0000-0001',
     *             'ada_foto' => true,   // berkasnya benar-benar ada
     *         ]],
     *         'atlet' => [[
     *             'id' => 11, 'nama' => '…', 'nisn' => '…', 'tanggal_lahir' => '2017-04-12',
     *             'jenis_kelamin' => 'L', 'jenis_kelamin_label' => 'Putra',
     *             'kelompok_umur' => 'KU 10', 'nomor_dada' => null,
     *             'nomor_lomba' => [['nama' => 'Lari 60 m putra', 'jenis' => 'individu', 'biaya' => 150000]],
     *         ]],
     *         'beregu' => [[
     *             'nomor_lomba_id' => 12, 'nomor_lomba' => "Kanga's Escape putra", 'biaya_tim' => 100000,   // 0 atau null: tanpa biaya tim (susulan)
     *             'anggota' => [['nama' => '…', 'nisn' => '…', 'nomor_dada' => null]],
     *         ]],
     *         'jumlah_atlet' => 2, 'tanpa_nomor_dada' => 2,
     *     ]
     *
     * @return array<string, mixed>|null
     */
    public function rincian(int $id): ?array
    {
        $p = $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();

        if (! is_array($p)) {
            return null;
        }

        $orang = $this->db->table('penanggung_jawab')
            ->select('id, nama, nomor_wa, foto')
            ->where('pendaftaran_id', $id)
            ->orderBy('id')
            ->get()->getResultArray();

        $atlet = $this->db->table('atlet a')
            ->select('a.id, a.nama, a.nisn, a.tanggal_lahir, a.jenis_kelamin, a.nomor_dada, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('a.id')
            ->get()->getResultArray();

        $nomor = $this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.nomor_lomba_id, an.biaya, nl.nama, nl.kategori, nl.jenis, nl.urutan')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        $tim = $this->db->table('pendaftaran_beregu pb')
            ->select('pb.nomor_lomba_id, pb.biaya_tim, nl.nama, nl.kategori, nl.urutan')
            ->join('nomor_lomba nl', 'nl.id = pb.nomor_lomba_id')
            ->where('pb.pendaftaran_id', $id)
            ->get()->getResultArray();

        $status = (string) $p['status'];

        $hasil = [
            'id'                => (int) $p['id'],
            'id_pendaftaran'    => (string) $p['id_pendaftaran'],
            'jenjang'           => (string) $p['jenjang'],
            'jenjang_label'     => Jenjang::label($p['jenjang']) ?? (string) $p['jenjang'],
            'npsn'              => $p['npsn'] === null || $p['npsn'] === '' ? null : (string) $p['npsn'],
            'nama_sekolah'      => (string) $p['nama_sekolah'],
            'kabupaten'         => (string) $p['kabupaten'],
            'dibuat_pada'       => $p['dibuat_pada'],
            'diubah_pada'       => $p['diubah_pada'],
            'status'            => $status,
            'status_label'      => PendaftaranModel::STATUS[$status] ?? $status,
            'waktu_lunas'       => $p['waktu_lunas'],
            'subtotal_individu' => (int) $p['subtotal_individu'],
            'subtotal_beregu'   => (int) $p['subtotal_beregu'],
            'total'             => (int) $p['total'],
            'penanggung_jawab'  => [],
            'atlet'             => [],
            'beregu'            => [],
            'jumlah_atlet'      => count($atlet),
            'tanpa_nomor_dada'  => 0,
        ];

        foreach ($orang as $baris) {
            $jalur = $this->foto->jalurPenuh($baris['foto'] === null ? null : (string) $baris['foto']);

            $hasil['penanggung_jawab'][] = [
                'id'              => (int) $baris['id'],
                'nama'            => (string) $baris['nama'],
                'nomor_wa'        => (string) $baris['nomor_wa'],
                'nomor_wa_tampil' => NomorWa::tampilan((string) $baris['nomor_wa']),
                'ada_foto'        => $jalur !== null && is_file($jalur),
            ];
        }

        $regu = [];

        foreach ($tim as $baris) {
            $regu[(int) $baris['nomor_lomba_id']] = [
                'nomor_lomba' => NomorLombaModel::namaLengkap($baris),
                'biaya_tim'   => (int) $baris['biaya_tim'],
                'anggota'     => [],
                'urutan'      => [(int) $baris['urutan'], (int) $baris['nomor_lomba_id']],
            ];
        }

        $nomorPerAtlet = [];
        $atletPerId    = array_column($atlet, null, 'id');

        foreach ($nomor as $baris) {
            $atletId = (int) $baris['atlet_id'];

            $nomorPerAtlet[$atletId][] = [
                'nama'  => NomorLombaModel::namaLengkap($baris),
                'jenis' => (string) $baris['jenis'],
                'biaya' => $baris['biaya'] === null ? null : (int) $baris['biaya'],
            ];

            if ($baris['jenis'] !== 'beregu') {
                continue;
            }

            $n = (int) $baris['nomor_lomba_id'];
            $a = $atletPerId[$atletId];

            $regu[$n] ??= [
                'nomor_lomba' => NomorLombaModel::namaLengkap($baris),
                'biaya_tim'   => null,
                'anggota'     => [],
                'urutan'      => [(int) $baris['urutan'], $n],
            ];
            $regu[$n]['anggota'][] = [
                'nama'       => (string) $a['nama'],
                'nisn'       => (string) $a['nisn'],
                'nomor_dada' => $a['nomor_dada'],
            ];
        }

        foreach ($atlet as $a) {
            if ($a['nomor_dada'] === null || $a['nomor_dada'] === '') {
                $hasil['tanpa_nomor_dada']++;
            }

            $hasil['atlet'][] = [
                'id'                  => (int) $a['id'],
                'nama'                => (string) $a['nama'],
                'nisn'                => (string) $a['nisn'],
                'tanggal_lahir'       => (string) $a['tanggal_lahir'],
                'jenis_kelamin'       => (string) $a['jenis_kelamin'],
                'jenis_kelamin_label' => $a['jenis_kelamin'] === 'P' ? 'Putri' : 'Putra',
                'kelompok_umur'       => (string) $a['kelompok_umur'],
                'nomor_dada'          => $a['nomor_dada'] === null || $a['nomor_dada'] === '' ? null : (string) $a['nomor_dada'],
                'nomor_lomba'         => $nomorPerAtlet[(int) $a['id']] ?? [],
            ];
        }

        usort($regu, static fn (array $x, array $y): int => $x['urutan'] <=> $y['urutan']);

        $hasil['beregu'] = array_map(static function (array $r): array {
            $r = ['nomor_lomba_id' => $r['urutan'][1]] + $r;
            unset($r['urutan']);

            return $r;
        }, $regu);

        return $hasil;
    }

    /**
     * Jalur lengkap foto penanggung jawab milik pendaftaran ini, atau `null`
     * bila penanggung jawabnya bukan milik pendaftaran itu, tidak berfoto, atau
     * berkasnya tidak ada.
     */
    public function jalurFoto(int $pendaftaranId, int $penanggungJawabId): ?string
    {
        $baris = $this->db->table('penanggung_jawab')
            ->select('foto')
            ->where('id', $penanggungJawabId)
            ->where('pendaftaran_id', $pendaftaranId)
            ->get()->getRowArray();

        if (! is_array($baris) || $baris['foto'] === null) {
            return null;
        }

        $jalur = $this->foto->jalurPenuh((string) $baris['foto']);

        return $jalur !== null && is_file($jalur) ? $jalur : null;
    }
}
