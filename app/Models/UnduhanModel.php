<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Berkas unduhan (tabel `unduhan`): flyer, juknis, dan berkas lain yang bisa
 * diunduh sekolah dari halaman depan (Tahap 3).
 *
 * `berkas` berisi jalur relatif hasil `App\Libraries\Unggahan::simpanBerkas()`,
 * `unggahan/unduhan/<nama acak>.<ekstensi>`; alamatnya `base_url($berkas)`.
 * Kolom ini wajib: setiap baris selalu punya berkas.
 *
 * Urutan tampil: `urutan` kecil lebih dulu, lalu yang lebih dulu dibuat.
 */
class UnduhanModel extends Model
{
    /**
     * Panjang terpanjang judul, sama dengan migrasi.
     */
    public const PANJANG_JUDUL = 150;

    /**
     * Panjang terpanjang keterangan. Kolomnya `TEXT`, tetapi keterangan
     * ditampilkan sebagai kalimat pendek di bawah judul.
     */
    public const PANJANG_KETERANGAN = 500;

    /**
     * Nilai `urutan` terbesar (`SMALLINT UNSIGNED`).
     */
    public const URUTAN_TERBESAR = 65535;

    protected $table            = 'unduhan';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'judul',
        'keterangan',
        'berkas',
        'urutan',
        'tampil',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Semua berkas unduhan, berurutan seperti tampil.
     *
     * @param bool $hanyaTampil `true` = hanya yang ditandai tampil (untuk halaman depan).
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(bool $hanyaTampil = false): array
    {
        if ($hanyaTampil) {
            $this->where('tampil', 1);
        }

        return $this->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Urutan usulan untuk berkas baru: satu lebih besar dari urutan terbesar
     * yang ada, atau 1 bila belum ada berkas. Tidak melebihi `URUTAN_TERBESAR`.
     */
    public function urutanBerikutnya(): int
    {
        $baris = $this->builder()->selectMax('urutan', 'terbesar')->get()->getRowArray();

        if ($baris === null || $baris['terbesar'] === null) {
            return 1;
        }

        return min(self::URUTAN_TERBESAR, (int) $baris['terbesar'] + 1);
    }
}
