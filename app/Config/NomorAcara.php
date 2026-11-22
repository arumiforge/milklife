<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Nomor acara per babak (F11-07, K172 butir 7).
 *
 * Nilai di sini hanya nilai awal; dibaca dan ditulis lewat
 * `setting('NomorAcara.awal')` dari halaman admin Jadwal pertandingan.
 */
class NomorAcara extends BaseConfig
{
    /**
     * Nomor acara babak pertama saat nomor diberi otomatis dari urutan jadwal.
     */
    public int $awal = 101;
}
