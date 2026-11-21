<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Tampilan nomor atlet (BIB) cetak (K137, `App\Libraries\HariLomba\TampilanBib`).
 *
 * Diisi dari halaman Hari lomba > Cetak BIB, dibaca lewat `setting('Bib.<kunci>')`.
 */
class Bib extends BaseConfig
{
    /**
     * Gambar latar penuh satu BIB, jalur `unggahan/bib/<nama>.jpg`
     * (sudah dipotong pas 2480 × 1754 piksel); `''` bila tanpa latar.
     */
    public string $latar = '';

    /**
     * Kepala BIB (logo, nama acara, kelompok umur) tetap dicetak di atas latar.
     * Tanpa latar, kepala selalu dicetak.
     */
    public bool $tampilkan_kepala = true;

    /**
     * Warna tulisan di atas latar: `gelap` atau `terang`. Tanpa latar selalu gelap.
     */
    public string $warna_teks = 'gelap';
}
