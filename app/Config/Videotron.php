<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Videotron `/layar` (F9-13, K141, F11-09, K173), diatur di Pengaturan > Videotron.
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Videotron.<kunci>')` (lihat `Config\Acara`), atau lewat
 * `App\Libraries\Hasil\PapanVideotron::pengaturan()` yang sudah merapikannya.
 */
class Videotron extends BaseConfig
{
    /**
     * Tampilkan videotron. Nilai awal mati: `/layar`, semua tampilannya, dan
     * datanya menjawab 404 sampai admin menyalakannya setelah mencobanya di staging
     * (rencana Tahap 9, "Aman untuk produksi").
     */
    public bool $tampil = false;

    /**
     * Gambar latar unggahan admin (F11-09, K173), jalur relatif
     * `unggahan/videotron/….webp`; kosong = latar bawaan dengan animasi.
     */
    public string $latar = '';

    /**
     * Isi papan putar `/layar/putar`, dipisah koma, urut tampil. Kode dari
     * `PapanVideotron::ISI`: `hasil`, `juara_umum`, `jadwal`, `berlangsung`.
     */
    public string $isi = 'hasil,juara_umum,berlangsung,jadwal';

    /**
     * Lama tiap tampilan papan putar dalam detik (`PapanVideotron::LAMA_MIN` sampai `LAMA_MAKS`).
     */
    public int $lama = 12;
}
