<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pendaftaran (tabel `pendaftaran`): satu kali pendaftaran dari satu sekolah.
 *
 * Model ini hanya tabel dan pembacaan sederhana. Pendaftaran baru ditulis
 * lewat `App\Libraries\Pendaftaran\SimpanPendaftaran` (satu transaksi beserta
 * penanggung jawab, atlet, dan tim). Tahap 5 boleh memakainya untuk membaca,
 * tidak mengubahnya (kesepakatan dua sesi).
 */
class PendaftaranModel extends Model
{
    public const STATUS = [
        'menunggu' => 'Menunggu Konfirmasi Pembayaran',
        'lunas'    => 'Lunas',
    ];

    protected $table            = 'pendaftaran';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id_pendaftaran', 'kode_bukti', 'jenjang', 'npsn', 'nama_sekolah', 'kabupaten',
        'subtotal_individu', 'subtotal_beregu', 'total', 'status', 'waktu_lunas',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Pendaftaran menurut ID pendaftaran (contoh `MAC2A0000126`), atau `null`.
     *
     * @return array<string, mixed>|null
     */
    public function cariIdPendaftaran(string $idPendaftaran): ?array
    {
        return $this->where('id_pendaftaran', strtoupper(trim($idPendaftaran)))->first();
    }
}
