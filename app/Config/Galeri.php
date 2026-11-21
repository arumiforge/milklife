<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Halaman Galeri publik (`/galeri`, F9-06, K140 butir 11).
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Galeri.<kunci>')` (lihat `Config\Acara`).
 */
class Galeri extends BaseConfig
{
    /**
     * Tampilkan halaman Galeri (`/galeri`) dan butir menunya. Nilai awal mati:
     * halaman menjawab 404 sampai admin menyalakannya di Pengaturan > Galeri
     * setelah mencobanya di staging (rencana Tahap 9).
     */
    public bool $tampil = false;

    /**
     * Album bawaan "Seri sebelumnya" sudah pernah dipasang (`data:awal` atau
     * `galeri:awal`). Supaya album yang sudah dihapus admin tidak dipasang lagi.
     */
    public bool $bawaan_dipasang = false;
}
