<?php

namespace App\Libraries;

use App\Libraries\Hasil\HasilLomba;
use Throwable;

/**
 * Menu halaman publik bertampilan baru (`tata_letak/publik_tw.php`, Tahap 9, K140).
 *
 * Satu-satunya tempat menu publik baru ditulis. Tugas Tahap 9 menambah butirnya
 * di `butir()` bersama halamannya (Jadwal F9-07, Peserta F9-12, Galeri F9-06,
 * Bantuan F9-04), dengan syarat tampil yang sama dengan sakelar halaman itu:
 * butir yang halamannya belum dibuka admin tidak tampil (rencana Tahap 9).
 *
 * Tata letak lama (`tata_letak/publik.php`) tetap memakai menunya sendiri
 * sampai semua halaman publik dipindah (F9-03, F9-05).
 */
final class MenuPublik
{
    /**
     * Butir menu utama, urut dari kiri.
     *
     * @return list<array{label: string, alamat: string, aktif: bool}>
     */
    public static function butir(): array
    {
        $butir = [
            ['label' => 'Beranda', 'alamat' => site_url('/'), 'aktif' => url_is('/')],
        ];

        // ---------- Halaman depan (F9-03) ----------
        // Kategori menaut ke rincian kelompok umur di halaman depan, bila sudah ada.
        try {
            $adaKategori = db_connect()->table('kelompok_umur')->where('aktif', 1)->countAllResults() > 0;
        } catch (Throwable) {
            $adaKategori = false;
        }

        if ($adaKategori) {
            $butir[] = ['label' => 'Kategori', 'alamat' => site_url('/') . '#lomba', 'aktif' => false];
        }
        // ---------- Akhir halaman depan ----------

        $butir[] = ['label' => 'Unduhan', 'alamat' => site_url('unduhan'), 'aktif' => url_is('unduhan')];

        // Hasil setelah ada nomor yang diterbitkan (F8-04). Halaman galat tetap
        // tampil walau basis data tidak bisa dibaca.
        $diHasil = url_is('hasil') || url_is('hasil/*');

        try {
            $adaHasil = $diHasil || (new HasilLomba())->adaTerbit();
        } catch (Throwable) {
            $adaHasil = false;
        }

        if ($adaHasil) {
            $butir[] = ['label' => 'Hasil', 'alamat' => site_url('hasil'), 'aktif' => $diHasil];
        }

        // ---------- Bantuan (F9-04): selalu tampil ----------
        $butir[] = ['label' => 'Bantuan', 'alamat' => site_url('bantuan'), 'aktif' => url_is('bantuan')];
        // ---------- Akhir Bantuan ----------

        return $butir;
    }

    /**
     * Tombol di kanan menu: Cek Pendaftaran selalu, Daftar hanya saat pendaftaran terbuka.
     *
     * @return list<array{label: string, alamat: string, varian: string, ikon: string}>
     */
    public static function tombol(): array
    {
        $tombol = [
            ['label' => 'Cek Pendaftaran', 'alamat' => site_url('cek-pendaftaran'), 'varian' => 'kedua', 'ikon' => 'search'],
        ];

        try {
            $terbuka = (new IsiHalamanDepan())->bukaTutup()->terbuka();
        } catch (Throwable) {
            $terbuka = false;
        }

        if ($terbuka) {
            $tombol[] = ['label' => 'Daftar', 'alamat' => site_url('pendaftaran'), 'varian' => 'oranye', 'ikon' => 'arrow-right'];
        }

        return $tombol;
    }
}
