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

    /**
     * Hari lomba: bagian hasil dan juara umum dipindah tepat di bawah pembuka
     * halaman depan (bila sudah ada nomor yang terbit). Bawaan mati (K143, F9-03).
     */
    public bool $hasil_di_atas = false;

    /**
     * Foto carousel pembuka (F9-03): daftar berurutan
     * `[['berkas' => 'unggahan/halaman-depan/….webp', 'keterangan' => '…'], …]`.
     * `null` = belum pernah diatur (foto bawaan boleh dipasang); `[]` = admin
     * sudah menghapus semuanya. Dibaca lewat `App\Libraries\CarouselHalamanDepan`.
     *
     * @var list<array{berkas: string, keterangan: string}>|null
     */
    public ?array $carousel = null;

    /**
     * Tampilkan halaman Peserta publik (`/peserta`, F9-12) dan butir menunya.
     * Nilai awal mati: halaman menjawab 404 sampai admin menyalakannya di
     * Pengaturan > Halaman Depan setelah mencobanya di staging (rencana Tahap 9).
     */
    public bool $tampil_peserta = false;

    // ---------- Jadwal pertandingan (F9-07) ----------

    /**
     * Tampilkan halaman Jadwal pertandingan (`/jadwal`, F9-07) dan butir menunya.
     * Nilai awal mati: halaman menjawab 404 sampai admin menyalakannya di
     * Pengaturan > Halaman Depan setelah mencobanya di staging (rencana Tahap 9).
     */
    public bool $tampil_jadwal = false;

    // ---------- Akhir jadwal pertandingan ----------

    // ---------- Seri dan lintasan (F9-08) ----------

    /**
     * Tampilkan daftar seri dan lintasan serta bagan alur babak di halaman
     * publik (`/jadwal/<id babak>`, F9-08) dan tautan Daftar seri di halaman
     * Jadwal. Nilai awal mati: alamat itu 404 sampai admin menyalakannya di
     * halaman Seri dan lintasan setelah mencobanya di staging (rencana Tahap 9).
     */
    public bool $tampil_seri = false;

    // ---------- Akhir seri dan lintasan ----------
}
