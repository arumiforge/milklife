<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan data acara (rancangan 5 dan 6.1).
 *
 * Nilai di sini hanya nilai awal. Nilai yang berlaku dibaca lewat
 * `codeigniter4/settings`, yang mendahulukan isian admin di tabel `settings`:
 *
 *     setting('Acara.nama');           // baca
 *     setting('Acara.nama', $nilai);   // tulis
 *
 * Nilai kosong ditulis `''` (teks) atau `null` (angka, tanggal), bukan dihapus.
 * Kunci baru perlu persetujuan koordinator (rencana Tahap 2, "Kunci pengaturan").
 */
class Acara extends BaseConfig
{
    /**
     * Nama acara, contoh `MilkLife Athletics Challenge`.
     */
    public string $nama = '';

    /**
     * Nomor seri, bilangan bulat (contoh 2).
     */
    public ?int $seri = null;

    /**
     * Tahun acara, bilangan bulat (contoh 2026).
     */
    public ?int $tahun = null;

    /**
     * Tempat acara, contoh `Supersoccer Arena, Kudus`.
     */
    public string $tempat = '';

    /**
     * Tanggal pertama acara, `Y-m-d`.
     */
    public ?string $tanggal_mulai = null;

    /**
     * Tanggal terakhir acara, `Y-m-d`.
     */
    public ?string $tanggal_selesai = null;
}
