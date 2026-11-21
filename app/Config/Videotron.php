<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Papan videotron `/layar` (F9-13, K141), diatur di Pengaturan > Videotron.
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Videotron.<kunci>')` (lihat `Config\Acara`), atau lewat
 * `App\Libraries\Hasil\PapanVideotron::pengaturan()` yang sudah merapikannya.
 */
class Videotron extends BaseConfig
{
    /**
     * Tampilkan papan videotron. Nilai awal mati: `/layar` dan `/layar/data`
     * menjawab 404 sampai admin menyalakannya setelah mencobanya di staging
     * (rencana Tahap 9, "Aman untuk produksi").
     */
    public bool $tampil = false;

    /**
     * Isi yang diputar, dipisah koma, urut tampil. Kode dari
     * `PapanVideotron::ISI`: `hasil`, `juara_umum`, `jadwal`, `berlangsung`.
     */
    public string $isi = 'hasil,juara_umum,berlangsung,jadwal';

    /**
     * Lama tiap tampilan dalam detik (`PapanVideotron::LAMA_MIN` sampai `LAMA_MAKS`).
     */
    public int $lama = 12;
}
