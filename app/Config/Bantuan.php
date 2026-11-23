<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Penanda tanya-jawab bawaan (F11-08).
 *
 * Nilai di sini hanya nilai awal; dibaca dan ditulis lewat
 * `setting('Bantuan.<kunci>')`.
 */
class Bantuan extends BaseConfig
{
    /**
     * Tanya-jawab bawaan Tahap 11 (nomor acara, kode hasil, waktu tangan,
     * tidak dilombakan, rekor) sudah ditambahkan ke server ini, oleh
     * `faq:awal`/`data:awal` atau sekali oleh `data:sesuaikan`. Setelah
     * menyala, `data:sesuaikan` tidak menambahkannya lagi, jadi tanya-jawab
     * yang dihapus admin tidak kembali.
     */
    public bool $faqTahap11 = false;
}
