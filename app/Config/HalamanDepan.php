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

    // ---------- Peta lokasi (F10-02) ----------

    /**
     * Alamat embed Google Maps untuk bagian Lokasi halaman depan; hanya
     * `https://www.google.com/maps/embed?…` (`App\Libraries\PetaLokasi`).
     * Kosong = bagian Lokasi tidak tampil. Bawaan Supersoccer Arena (permintaan B14).
     */
    public string $peta_embed = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.743848796379!2d110.85445437499567!3d-6.800985593196346!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e70c5529fe28039%3A0x889ddf1d4265ec0f!2sSupersoccer%20Arena!5e0!3m2!1sid!2sid!4v1795321165239!5m2!1sid!2sid';

    /**
     * Tautan "Buka di Google Maps" (https). Kosong = tautan tidak tampil.
     */
    public string $peta_tautan = 'https://maps.google.com/?q=Supersoccer+Arena+Kudus';

    // ---------- Akhir peta lokasi ----------

    // ---------- Bilah menu atas (F10-07, K154) ----------

    /**
     * Butir bilah submenu atas tata letak publik (`App\Libraries\MenuPublik::BILAH_ATAS`).
     * Bawaan semua menyala, supaya server lama langsung menampilkannya.
     */
    public bool $bilah_atas_unduhan = true;

    public bool $bilah_atas_galeri = true;

    public bool $bilah_atas_bantuan = true;

    public bool $bilah_atas_tanya = true;

    // ---------- Akhir bilah menu atas ----------
}
