<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTimeImmutable;

/**
 * Jadwal di halaman depan (tabel `jadwal`): sosialisasi daring, technical
 * meeting, dan jadwal lain yang ditambahkan admin.
 *
 * - `waktu` boleh kosong (belum pasti), disimpan `Y-m-d H:i:00` (WIB).
 * - `tempat` untuk acara luring, `tautan` (alamat web `http`/`https`) untuk
 *   acara daring; keduanya boleh diisi atau dikosongkan.
 * - Urutan tampil: `urutan` kecil lebih dulu, lalu waktu (yang kosong di
 *   belakang), lalu yang dibuat lebih dulu.
 * - `tampil` = 0 menyembunyikan jadwal dari halaman depan tanpa menghapusnya.
 *
 * Isian diperiksa di `JadwalController`; model ini hanya menyimpan dan membaca.
 */
class JadwalModel extends Model
{
    /**
     * Urutan terbesar yang bisa diketik admin.
     */
    public const URUTAN_MAKSIMAL = 9999;

    /**
     * Panjang terpanjang tiap kolom teks (sama dengan migrasi; `keterangan`
     * berupa TEXT, dibatasi aplikasi supaya tetap ringkas di halaman depan).
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'judul'      => 150,
        'keterangan' => 1000,
        'tempat'     => 150,
        'tautan'     => 255,
    ];

    /**
     * Bentuk waktu yang diterima dari formulir. Kotak `datetime-local`
     * mengirim `Y-m-d\TH:i` (kadang dengan detik).
     */
    private const FORMAT_WAKTU = ['!Y-m-d\TH:i', '!Y-m-d\TH:i:s', '!Y-m-d H:i', '!Y-m-d H:i:s'];

    protected $table            = 'jadwal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['judul', 'keterangan', 'waktu', 'tempat', 'tautan', 'urutan', 'tampil'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Semua jadwal menurut urutan tampil.
     *
     * @param bool $hanyaTampil Hanya jadwal yang ditampilkan di halaman depan.
     *
     * @return list<array<string, mixed>>
     */
    public function daftar(bool $hanyaTampil = false): array
    {
        if ($hanyaTampil) {
            $this->where('tampil', 1);
        }

        return $this->orderBy('urutan', 'ASC')
            ->orderBy('waktu IS NULL', 'ASC', false)
            ->orderBy('waktu', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Urutan untuk jadwal baru supaya tampil paling akhir: urutan terbesar
     * ditambah satu (paling besar `URUTAN_MAKSIMAL`), atau 1 bila belum ada.
     *
     * @param int|null $kecuali `id` yang tidak ikut dihitung (jadwal yang sedang diubah).
     */
    public function urutanBerikutnya(?int $kecuali = null): int
    {
        $pembangun = $this->builder()->selectMax('urutan', 'terbesar');

        if ($kecuali !== null) {
            $pembangun->where('id !=', $kecuali);
        }

        $baris = $pembangun->get()->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    /**
     * Baca isian waktu dari formulir. Hasil `Y-m-d H:i:00`, `''` bila isian
     * kosong (waktu belum pasti), atau `null` bila isian tidak sah.
     */
    public static function bacaWaktu(?string $isian): ?string
    {
        $isian = trim((string) $isian);

        if ($isian === '') {
            return '';
        }

        foreach (self::FORMAT_WAKTU as $format) {
            $waktu = DateTimeImmutable::createFromFormat($format, $isian);
            $galat = DateTimeImmutable::getLastErrors();

            if ($waktu === false || ($galat !== false && ($galat['warning_count'] > 0 || $galat['error_count'] > 0))) {
                continue;
            }

            $tahun = (int) $waktu->format('Y');

            return $tahun >= 2000 && $tahun <= 2100 ? $waktu->format('Y-m-d H:i:00') : null;
        }

        return null;
    }

    /**
     * Nilai `waktu` tersimpan dalam bentuk kotak `datetime-local` (`Y-m-d\TH:i`).
     */
    public static function waktuFormulir(?string $waktu): string
    {
        $waktu = trim((string) $waktu);

        if ($waktu === '') {
            return '';
        }

        $hasil = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $waktu);

        return $hasil === false ? '' : $hasil->format('Y-m-d\TH:i');
    }
}
