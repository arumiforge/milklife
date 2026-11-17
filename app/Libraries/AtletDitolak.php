<?php

namespace App\Libraries;

use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Pembaca catatan atlet yang ditolak panitia (`atlet_ditolak`, K116), untuk
 * Cek Pendaftaran dan rincian pendaftaran di panel admin.
 *
 * Aturan tampil: catatan hanya muncul di Cek Pendaftaran selama NISN-nya
 * **belum terdaftar lagi**. Begitu NISN yang sama tersimpan lagi sebagai atlet
 * (lewat wizard, pendaftaran lain, atau Ubah admin), keterangannya hilang
 * sendiri tanpa perlu dihapus. Admin tetap melihat semuanya, dengan ID
 * pendaftaran tempat atlet itu terdaftar lagi.
 *
 *     $baca = new AtletDitolak();
 *     $baca->untukPendaftaran([12, 13]);             // [12 => [baris, …]], hanya yang belum didaftarkan ulang
 *     $baca->untukPendaftaran([12], semua: true);    // admin: termasuk yang sudah didaftarkan ulang
 *
 * Bentuk satu baris (teks belum di-escape, uang belum diformat):
 *
 *     [
 *         'id' => 3, 'nama' => 'BUDI KARANGAN', 'nisn' => '9900000001', 'tanggal_lahir' => '2018-05-01',
 *         'tahun_lahir' => 2018, 'jenis_kelamin' => 'L', 'jenis_kelamin_label' => 'Laki-laki',
 *         'kelompok_umur' => 'KU 8',
 *         'nomor_lomba' => [['nama' => 'Lari 40 M putra', 'jenis' => 'individu', 'biaya' => 25000, 'regu' => null, 'peran' => null]],
 *         'biaya_individu' => 25000,
 *         'kelompok_umur_sesuai' => 'KU 10 (lahir 2016–2018)',   // atau kalimat bila tidak ada yang cocok, atau null
 *         'sesuai_beda' => true,                                  // kelompok umur yang sesuai berbeda dari yang didaftarkan
 *         'nomor_sesuai' => [['nama' => 'Lari 60 M putra', 'jenis' => 'individu', 'biaya' => 25000]], // dari pengaturan sekarang
 *         'alasan' => '…', 'total_sebelum' => 125000, 'total_sesudah' => 100000,
 *         'ditolak_oleh' => 'Admin', 'dibuat_pada' => '2026-11-17 10:00:00',
 *         'didaftarkan_ulang' => null,   // atau ID pendaftaran tempat NISN ini terdaftar lagi
 *     ]
 */
final class AtletDitolak
{
    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * @param list<int> $pendaftaranId
     *
     * @return array<int, list<array<string, mixed>>> per id pendaftaran, urut waktu ditolak
     */
    public function untukPendaftaran(array $pendaftaranId, bool $semua = false): array
    {
        $pendaftaranId = array_values(array_unique(array_map('intval', $pendaftaranId)));

        if ($pendaftaranId === []) {
            return [];
        }

        $kueri = $this->db->table('atlet_ditolak d')
            ->select('d.*, (SELECT p2.id_pendaftaran FROM atlet a2 JOIN pendaftaran p2 ON p2.id = a2.pendaftaran_id WHERE a2.nisn = d.nisn LIMIT 1) AS didaftarkan_ulang', false)
            ->whereIn('d.pendaftaran_id', $pendaftaranId)
            ->orderBy('d.id');

        if (! $semua) {
            $kueri->where('NOT EXISTS (SELECT 1 FROM atlet a3 WHERE a3.nisn = d.nisn)', null, false);
        }

        $baris = $kueri->get()->getResultArray();

        if ($baris === []) {
            return [];
        }

        $nomorSesuai = $this->nomorSesuai();
        $hasil       = [];

        foreach ($baris as $b) {
            $nomor = json_decode((string) $b['nomor_lomba'], true);
            $nomor = is_array($nomor) ? array_values(array_filter($nomor, 'is_array')) : [];
            $jk    = (string) $b['jenis_kelamin'];
            $idKu  = $b['kelompok_umur_sesuai_id'] === null ? null : (int) $b['kelompok_umur_sesuai_id'];
            $teks  = $b['kelompok_umur_sesuai'] === null ? null : (string) $b['kelompok_umur_sesuai'];

            $hasil[(int) $b['pendaftaran_id']][] = [
                'id'                   => (int) $b['id'],
                'nama'                 => (string) $b['nama'],
                'nisn'                 => (string) $b['nisn'],
                'tanggal_lahir'        => (string) $b['tanggal_lahir'],
                'tahun_lahir'          => (int) substr((string) $b['tanggal_lahir'], 0, 4),
                'jenis_kelamin'        => $jk,
                'jenis_kelamin_label'  => DataPendaftaran::JENIS_KELAMIN[$jk] ?? $jk,
                'kelompok_umur'        => (string) $b['kelompok_umur'],
                'nomor_lomba'          => $nomor,
                'biaya_individu'       => (int) $b['biaya_individu'],
                'kelompok_umur_sesuai' => $teks,
                'sesuai_beda'          => $teks !== null && ! str_starts_with($teks, (string) $b['kelompok_umur'] . ' ('),
                'nomor_sesuai'         => $idKu === null ? [] : array_values(array_filter(
                    $nomorSesuai[$idKu] ?? [],
                    static fn (array $n): bool => $n['kategori'] === 'mix' || $n['kategori'] === ($jk === 'P' ? 'putri' : 'putra'),
                )),
                'alasan'               => (string) $b['alasan'],
                'total_sebelum'        => (int) $b['total_sebelum'],
                'total_sesudah'        => (int) $b['total_sesudah'],
                'ditolak_oleh'         => $b['ditolak_oleh'] === null ? null : (string) $b['ditolak_oleh'],
                'dibuat_pada'          => $b['dibuat_pada'] === null ? null : (string) $b['dibuat_pada'],
                'didaftarkan_ulang'    => $b['didaftarkan_ulang'] === null ? null : (string) $b['didaftarkan_ulang'],
            ];
        }

        return $hasil;
    }

    /**
     * Nomor lomba aktif per kelompok umur aktif, dengan biayanya saat ini:
     * individu memakai biaya individu kelompok umurnya (per nomor), beregu
     * memakai biaya beregu nomornya (per regu).
     *
     * @return array<int, list<array{nama: string, jenis: string, kategori: string, biaya: int|null}>>
     */
    private function nomorSesuai(): array
    {
        $induk = (new DataIndukPendaftaran())->induk();
        $biaya = array_column($induk['kelompok_umur'], 'biaya_individu', 'id');
        $hasil = [];

        foreach ($induk['nomor_lomba'] as $n) {
            $hasil[(int) $n['kelompok_umur_id']][] = [
                'nama'     => NomorLombaModel::namaLengkap($n),
                'jenis'    => (string) $n['jenis'],
                'kategori' => (string) $n['kategori'],
                'biaya'    => $n['jenis'] === 'individu' ? ($biaya[(int) $n['kelompok_umur_id']] ?? null) : $n['biaya_beregu'],
            ];
        }

        return $hasil;
    }
}
