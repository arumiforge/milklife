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
     * Program `mysqldump`. Di Laragon tulis jalur lengkapnya, misalnya
     * `C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqldump.exe`.
     */
    public string $mysqldump = 'mysqldump';
}
