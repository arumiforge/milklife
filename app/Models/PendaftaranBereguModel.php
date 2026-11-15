<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Regu beregu dalam satu pendaftaran (tabel `pendaftaran_beregu`), satu
 * baris per nomor per regu (K105).
 *
 * `biaya_tim` adalah biaya regu; 0 untuk pendaftaran susulan yang hanya
 * menambah anggota regu sekolah yang sudah ada. Satu label regu per nomor per
 * sekolah dijaga `SimpanPendaftaran`.
 */
class PendaftaranBereguModel extends Model
{
    protected $table            = 'pendaftaran_beregu';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'nomor_lomba_id', 'regu', 'biaya_tim'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * @return list<array<string, mixed>>
     */
    public function milikPendaftaran(int $pendaftaranId): array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->findAll();
    }
}
