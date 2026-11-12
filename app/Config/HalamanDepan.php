<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Isi halaman depan (rancangan 10).
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('HalamanDepan.<kunci>')` (lihat `Config\Acara`).
 *
 * Gambar disimpan sebagai jalur relatif hasil `App\Libraries\Unggahan`
 * (contoh `unggahan/halaman-depan/3f2a….webp`), alamatnya `base_url($jalur)`.
 * Gambar kosong ditampilkan dengan `tata_letak/_tanpa_gambar`.
 */
class HalamanDepan extends BaseConfig
{
    /**
     * Gambar kepala mendatar selebar layar.
     */
    public string $gambar_kepala = '';

    /**
     * Logo MilkLife persegi.
     */
    public string $logo_persegi = '';

    /**
     * Logo MilkLife persegi panjang.
     */
    public string $logo_persegi_panjang = '';

    /**
     * Logo Bakti Olahraga Djarum Foundation.
     */
    public string $logo_djarum = '';

    /**
     * Judul besar halaman depan.
     */
    public string $judul = '';

    /**
     * Teks pengantar di bawah judul.
     */
    public string $teks = '';

    /**
     * Teks kaki halaman.
     */
    public string $footer = '';

    /**
     * Nomor WA tombol "tanya admin", bentuk baku `628…` (`App\Libraries\NomorWa`).
     */
    public string $nomor_wa_tanya = '';

    /**
     * Isi pesan WA yang sudah diketik saat tombol "tanya admin" ditekan.
     */
    public string $pesan_tanya = '';

    /**
     * Isi pesan WA untuk tombol "bagikan".
     */
    public string $pesan_bagikan = '';

    /**
     * Tampilkan biaya individu dan biaya tim di bagian lomba halaman depan.
     * Bila dimatikan, rincian biaya hanya muncul di bagian pembayaran saat
     * sekolah mendaftar. Nilai awal sembunyi (K74).
     */
    public bool $tampilkan_biaya = false;
}
