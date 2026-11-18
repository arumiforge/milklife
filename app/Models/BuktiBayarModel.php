<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Pembayaran;

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
     * Rentang yang boleh diisi admin untuk `Pembayaran.batas_bukti_bayar` (K135).
     */
    public const BATAS_TERKECIL = 1;

    public const BATAS_TERBESAR = 20;

    protected $table            = 'bukti_bayar';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = ['pendaftaran_id', 'berkas', 'ukuran', 'diperiksa_pada', 'diperiksa_oleh'];

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
        return $this->jumlahMilik($pendaftaranId) < self::batas();
    }

    /**
     * Bukti bayar paling banyak per pendaftaran, diatur admin di Pengaturan >
     * Pembayaran (`Pembayaran.batas_bukti_bayar`, bawaan 5, K135). Nilai di
     * luar rentang `BATAS_TERKECIL`–`BATAS_TERBESAR` dipepetkan ke ujungnya.
     *
     * Menurunkan batas tidak menghapus bukti yang sudah ada; pendaftaran yang
     * buktinya sudah sebanyak batas atau lebih hanya tidak bisa menambah lagi.
     */
    public static function batas(): int
    {
        $nilai = setting('Pembayaran.batas_bukti_bayar');

        if (! is_numeric($nilai)) {
            $nilai = (new Pembayaran())->batas_bukti_bayar;
        }

        return max(self::BATAS_TERKECIL, min(self::BATAS_TERBESAR, (int) $nilai));
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
