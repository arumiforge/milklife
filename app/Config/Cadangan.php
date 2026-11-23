<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Pengaturan cadangan harian (`php spark cadangan:buat`, K66).
 *
 * Nilai dibaca dari `.env` dengan awalan `cadangan.`, misalnya
 * `cadangan.folder = 'D:\cadangan-milklife'`.
 */
class Cadangan extends BaseConfig
{
    /**
     * Folder tujuan berkas zip cadangan. Wajib diisi, sudah ada, dan harus
     * di luar folder proyek (supaya cadangan tidak ikut terhapus atau
     * terunggah bersama proyek). Sebaiknya di diska lain.
     */
    public string $folder = '';

    /**
     * Cadangan yang berumur sebanyak ini hari atau lebih dihapus setiap
     * perintah berhasil membuat cadangan baru.
     */
    public int $simpanHari = 14;

    /**
     * Program `mysqldump`. Di image Docker (K182) cukup `mysqldump` (klien
     * MySQL 8.4 sudah terpasang). Di Windows tulis jalur lengkap
     * `mysqldump.exe`.
     */
    public string $mysqldump = 'mysqldump';
}
