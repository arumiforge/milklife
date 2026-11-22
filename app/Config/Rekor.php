<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Rekor kejuaraan (F11-06, K172 butir 3).
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Rekor.<kunci>')` (lihat `Config\Acara`).
 */
class Rekor extends BaseConfig
{
    /**
     * Tampilkan halaman Rekor publik (`/hasil/rekor`). Nilai awal mati:
     * halaman menjawab 404 sampai admin menyalakannya di menu Rekor setelah
     * isinya diperiksa. Tanda CR di hasil dan videotron tidak bergantung pada
     * sakelar ini.
     */
    public bool $tampil = false;
}
