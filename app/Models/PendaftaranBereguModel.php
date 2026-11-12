<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tim beregu dalam satu pendaftaran (tabel `pendaftaran_beregu`).
 *
 * `biaya_tim` 0 untuk pendaftaran susulan yang hanya menambah anggota tim
 * sekolah yang sudah ada (K07). Satu tim per nomor beregu per sekolah dijaga
 * `SimpanPendaftaran`.
 */
class PendaftaranBereguModel extends Model
{
    protected $table            = 'pendaftaran_beregu';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'nomor_lomba_id', 'biaya_tim'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Nomor beregu yang sudah punya tim dari sekolah ini (NPSN).
     *
     * @return list<int>
     */
    public function nomorUntukNpsn(string $npsn): array
    {
        $baris = $this->db->table('pendaftaran_beregu')
            ->distinct()
            ->select('pendaftaran_beregu.nomor_lomba_id')
            ->join('pendaftaran', 'pendaftaran.id = pendaftaran_beregu.pendaftaran_id')
            ->where('pendaftaran.npsn', $npsn)
            ->get()->getResultArray();

        return array_map(static fn (array $b): int => (int) $b['nomor_lomba_id'], $baris);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function milikPendaftaran(int $pendaftaranId): array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->findAll();
    }
}
