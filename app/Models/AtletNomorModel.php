<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Nomor lomba tiap atlet (tabel `atlet_nomor`). `biaya` terisi untuk nomor
 * individu (rupiah, disalin saat mendaftar) dan kosong untuk nomor beregu;
 * `regu` (A–Z) dan `peran` (inti/cadangan) hanya terisi untuk nomor beregu
 * (K105).
 */
class AtletNomorModel extends Model
{
    protected $table            = 'atlet_nomor';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['atlet_id', 'nomor_lomba_id', 'biaya', 'regu', 'peran'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
}
