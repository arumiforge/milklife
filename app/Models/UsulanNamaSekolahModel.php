<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Usulan nama sekolah dari pendaftar (tabel `usulan_nama_sekolah`, K99).
 * Keputusan admin ditulis `App\Libraries\Panel\UsulanNamaSekolah`.
 */
class UsulanNamaSekolahModel extends Model
{
    public const MENUNGGU  = 'menunggu';
    public const DISETUJUI = 'disetujui';
    public const DITOLAK   = 'ditolak';

    /**
     * Panjang alasan penolakan paling banyak, sama dengan migrasi.
     */
    public const ALASAN_MAKSIMAL = 200;

    protected $table            = 'usulan_nama_sekolah';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['npsn', 'pendaftaran_id', 'nama_lama', 'nama_usulan', 'status', 'nama_disetujui', 'alasan', 'diputuskan_pada'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    public function jumlahMenunggu(): int
    {
        return $this->where('status', self::MENUNGGU)->countAllResults();
    }

    /**
     * Usulan beserta data sekolah dan pendaftaran pengusulnya, yang menunggu
     * lebih dulu (terlama di atas), lalu yang sudah diputuskan (terbaru di atas).
     *
     * @return list<array<string, mixed>>
     */
    public function daftarLengkap(int $batasDiputuskan = 50): array
    {
        $dasar = fn () => $this->db->table($this->table . ' u')
            ->select('u.*, s.id AS sekolah_id, s.nama AS nama_sekarang, s.desa_kelurahan, s.kecamatan, s.kabupaten')
            ->select('p.id_pendaftaran, p.nama_sekolah AS nama_di_pendaftaran')
            ->join('sekolah s', 's.npsn = u.npsn', 'left')
            ->join('pendaftaran p', 'p.id = u.pendaftaran_id', 'left');

        $menunggu   = $dasar()->where('u.status', self::MENUNGGU)->orderBy('u.id', 'ASC')->get()->getResultArray();
        $diputuskan = $dasar()->where('u.status !=', self::MENUNGGU)->orderBy('u.diputuskan_pada', 'DESC')->orderBy('u.id', 'DESC')
            ->limit($batasDiputuskan)->get()->getResultArray();

        return [...$menunggu, ...$diputuskan];
    }
}
