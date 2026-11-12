<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Penanggung jawab pendaftaran (tabel `penanggung_jawab`).
 *
 * `nomor_wa` dalam bentuk baku `628…`. `foto` jalur relatif terhadap folder
 * unggahan di luar `public/` (`App\Libraries\Pendaftaran\FotoPenanggungJawab`).
 */
class PenanggungJawabModel extends Model
{
    protected $table            = 'penanggung_jawab';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'nama', 'nomor_wa', 'foto'];

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
