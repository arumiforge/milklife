<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Atlet (tabel `atlet`). NISN unik di seluruh basis data (K06), dijaga kunci
 * unik tabel. Nomor dada diisi saat Lunas (Tahap 6).
 */
class AtletModel extends Model
{
    protected $table            = 'atlet';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'nama', 'nisn', 'tanggal_lahir', 'jenis_kelamin', 'kelompok_umur_id', 'nomor_dada'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * NISN dari daftar ini yang sudah terdaftar.
     *
     * @param list<string> $nisn
     *
     * @return list<string>
     */
    public function nisnTerdaftar(array $nisn): array
    {
        if ($nisn === []) {
            return [];
        }

        return array_map(
            static fn (array $baris): string => (string) $baris['nisn'],
            $this->select('nisn')->whereIn('nisn', array_values($nisn))->findAll(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function milikPendaftaran(int $pendaftaranId): array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->findAll();
    }
}
