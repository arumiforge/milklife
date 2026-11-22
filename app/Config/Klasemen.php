<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Metode klasemen juara umum (F9-11, K140 butir 7a) dan gabungan kelompok
 * umur (F11-02, K172 butir 2).
 *
 * Diisi dari Pengaturan > Hasil, dibaca lewat `App\Libraries\Hasil\MetodeKlasemen`
 * (`setting('Klasemen.<kunci>')`).
 */
class Klasemen extends BaseConfig
{
    /**
     * `medali` (bawaan, juknis bagian 11) atau `poin` (poin per peringkat).
     */
    public string $metode = 'medali';

    /**
     * Poin peringkat 1–8, dipisah koma.
     */
    public string $poin = '7,5,4,3,2,1,0,0';

    /**
     * Gabungan kelompok umur dalam satu klasemen, JSON daftar id kelompok umur
     * (misalnya `[[1,2]]`). Kosong = satu klasemen per kelompok umur.
     */
    public string $gabungan = '[]';
}
