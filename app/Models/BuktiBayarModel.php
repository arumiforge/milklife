<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Bukti bayar pendaftaran (tabel `bukti_bayar`, K34).
 *
 * `berkas` jalur relatif yang dibuat `App\Libraries\Panel\BerkasBuktiBayar`
 * (`bukti-bayar/<acak>.jpg`), `ukuran` dalam bita, `waktu_unggah` diisi
 * otomatis saat baris dibuat.
 */
class BuktiBayarModel extends Model
{
    /**
     * Bukti bayar paling banyak per pendaftaran.
     */
    public const BATAS = 5;

    protected $table            = 'bukti_bayar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'berkas', 'ukuran'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'waktu_unggah';
    protected $updatedField  = '';

    /**
     * Bukti bayar satu pendaftaran, urut waktu unggah.
     *
     * @return list<array<string, mixed>>
     */
    public function milikPendaftaran(int $pendaftaranId): array
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->findAll();
    }

    public function jumlahMilik(int $pendaftaranId): int
    {
        return $this->where('pendaftaran_id', $pendaftaranId)->countAllResults();
    }

    /**
     * Apakah pendaftaran ini masih boleh menambah bukti bayar.
     */
    public function masihBolehTambah(int $pendaftaranId): bool
    {
        return $this->jumlahMilik($pendaftaranId) < self::BATAS;
    }

    /**
     * Satu bukti bayar milik pendaftaran tertentu, atau `null` (mencegah
     * membuka bukti pendaftaran lain lewat alamat).
     *
     * @return array<string, mixed>|null
     */
    public function milik(int $pendaftaranId, int $id): ?array
    {
        $baris = $this->where('pendaftaran_id', $pendaftaranId)->find($id);

        return is_array($baris) ? $baris : null;
    }
}
