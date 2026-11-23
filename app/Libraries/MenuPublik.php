<?php

namespace App\Libraries;

use App\Libraries\Hasil\HasilLomba;
use Throwable;

/**
 * Menu halaman publik bertampilan baru (`tata_letak/publik_tw.php`, Tahap 9, K140).
 *
 * Satu-satunya tempat menu publik ditulis. Butir yang halamannya belum dibuka
 * admin tidak tampil (sakelar halaman yang sama, rencana Tahap 9).
 *
 * Susunan navigasi F10-07 (K154):
 * - `butir()`: menu utama (Beranda, Jadwal, Hasil, Peserta);
 * - `tombol()`: Cek Pendaftaran dan Daftar di kanan menu utama;
 * - `bilahAtas()`: bilah submenu atas di layar `lg` (Unduhan, Galeri, Bantuan,
 *   Tanya panitia), dipilih admin di Pengaturan > Halaman Depan, lalu Masuk panitia;
 * - `mediaSosial()`: ikon media sosial dari Pengaturan > Kontak;
 * - `bawah()`: butir menu bawah ponsel, Cek Pendaftaran paling kanan (F10-08).
 *
 * ikon-sprite: house calendar-days trophy users download images circle-help message-circle pencil-line search lock
 */
final class MenuPublik
{
    /**
     * Butir bilah atas yang bisa dipilih admin: kunci => label di Pengaturan.
     * Disimpan sebagai `HalamanDepan.bilah_atas_<kunci>` (bawaan menyala).
     */
    public const BILAH_ATAS = [
        'unduhan' => 'Unduhan',
        'galeri'  => 'Galeri (hanya bila halaman Galeri dinyalakan)',
        'bantuan' => 'Bantuan',
        'tanya'   => 'Tanya panitia lewat WA (hanya bila nomor WA tanya admin diisi)',
    ];

    /**
     * Butir menu utama, urut dari kiri.
     *
     * @return list<array{label: string, alamat: string, aktif: bool, ikon: string}>
     */
    public static function butir(): array
    {
        $butir = [self::beranda()];

        if (($jadwal = self::jadwal()) !== null) {
            $butir[] = $jadwal;
        }

        if (($hasil = self::hasil()) !== null) {
            $butir[] = $hasil;
        }

        // ---------- Peserta (F9-12): hanya bila sakelar Pengaturan menyala ----------
        if (self::sakelar('HalamanDepan.tampil_peserta')) {
            $butir[] = ['label' => 'Peserta', 'alamat' => site_url('peserta'), 'aktif' => url_is('peserta'), 'ikon' => 'users'];
        }
        // ---------- Akhir Peserta ----------

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

        if (self::terbuka()) {
            $tombol[] = ['label' => 'Daftar', 'alamat' => site_url('pendaftaran'), 'varian' => 'oranye', 'ikon' => 'arrow-right'];
        }

        return $tombol;
    }

    /**
     * Butir bilah submenu atas (K154) yang dicentang admin dan halamannya hidup,
     * ditambah Masuk panitia yang selalu ada di akhir (F10-08).
     * `luar` = tautan ke situs lain (WA), dibuka di tab baru.
     *
     * @return list<array{kunci: string, label: string, alamat: string, aktif: bool, ikon: string, luar: bool}>
     */
    public static function bilahAtas(): array
    {
        $butir = [];

        if (self::dipilih('unduhan')) {
            $butir[] = ['kunci' => 'unduhan', 'label' => 'Unduhan', 'alamat' => site_url('unduhan'), 'aktif' => url_is('unduhan'), 'ikon' => 'download', 'luar' => false];
        }

        // Galeri (F9-06): hanya bila sakelar Pengaturan > Galeri juga menyala.
        if (self::dipilih('galeri') && self::sakelar('Galeri.tampil')) {
            $butir[] = ['kunci' => 'galeri', 'label' => 'Galeri', 'alamat' => site_url('galeri'), 'aktif' => url_is('galeri'), 'ikon' => 'images', 'luar' => false];
        }

        if (self::dipilih('bantuan')) {
            $butir[] = self::bantuan();
        }

        if (self::dipilih('tanya')) {
            try {
                $wa = (new IsiHalamanDepan())->waTanya();
            } catch (Throwable) {
                $wa = null;
            }

            if ($wa !== null) {
                $butir[] = ['kunci' => 'tanya', 'label' => 'Tanya panitia', 'alamat' => $wa, 'aktif' => false, 'ikon' => 'message-circle', 'luar' => true];
            }
        }

        // Masuk panitia selalu ada, paling akhir (F10-08, permintaan pemilik; dulu di kaki halaman).
        $butir[] = ['kunci' => 'masuk', 'label' => 'Masuk panitia', 'alamat' => site_url('admin/masuk'), 'aktif' => false, 'ikon' => 'lock', 'luar' => false];

        return $butir;
    }

    /**
     * Media sosial dari Pengaturan > Kontak dengan nama ikon Lucide.
     *
     * @return list<array{label: string, alamat: string, ikon: string}>
     */
    public static function mediaSosial(): array
    {
        try {
            $daftar = (new IsiHalamanDepan())->mediaSosial();
        } catch (Throwable) {
            return [];
        }

        return array_map(static fn (array $baris): array => [
            'label'  => $baris['label'] !== '' ? $baris['label'] : $baris['alamat_ringkas'],
            'alamat' => $baris['alamat'],
            // Nama Bootstrap Icons dari `TautanModel` ke Lucide (sama dengan `beranda/_kontak.php`).
            'ikon' => match ($baris['ikon']) {
                'instagram', 'facebook', 'youtube' => $baris['ikon'],
                'twitter-x' => 'twitter',
                'tiktok'    => 'music',
                'whatsapp'  => 'message-circle',
                'telegram'  => 'send',
                default     => 'globe',
            },
        ], $daftar);
    }

    /**
     * Butir menu bawah ponsel (K175, F11-12): Beranda, Bantuan, tombol tengah,
     * Cek, Jadwal. Tombol tengah (`utama`) tampil lebih besar dan menonjol:
     * Daftar, lalu Hasil setelah pendaftaran ditutup. Bila Jadwal dimatikan,
     * butir terakhir diganti Unduhan. Menggantikan susunan K154/K157/K168.
     *
     * @return list<array{label: string, alamat: string, aktif: bool, ikon: string, utama: bool}>
     */
    public static function bawah(): array
    {
        $tengah = self::ditutup()
            ? ['label' => 'Hasil', 'alamat' => site_url('hasil'), 'aktif' => url_is('hasil') || url_is('hasil/*'), 'ikon' => 'trophy']
            : ['label' => 'Daftar', 'alamat' => site_url('pendaftaran'), 'aktif' => url_is('pendaftaran') || url_is('pendaftaran/*'), 'ikon' => 'pencil-line'];

        $butir = [
            self::beranda(),
            self::bantuan(),
            $tengah + ['utama' => true],
            ['label' => 'Cek', 'alamat' => site_url('cek-pendaftaran'), 'aktif' => url_is('cek-pendaftaran') || url_is('cek-pendaftaran/*'), 'ikon' => 'search'],
            self::jadwal() ?? ['label' => 'Unduhan', 'alamat' => site_url('unduhan'), 'aktif' => url_is('unduhan'), 'ikon' => 'download'],
        ];

        return array_map(static fn (array $b): array => [
            'label'  => $b['label'],
            'alamat' => $b['alamat'],
            'aktif'  => $b['aktif'],
            'ikon'   => $b['ikon'],
            'utama'  => $b['utama'] ?? false,
        ], $butir);
    }

    // ---------- Butir bersama ----------

    /**
     * @return array{label: string, alamat: string, aktif: bool, ikon: string}
     */
    private static function beranda(): array
    {
        return ['label' => 'Beranda', 'alamat' => site_url('/'), 'aktif' => url_is('/'), 'ikon' => 'house'];
    }

    /**
     * Jadwal pertandingan (F9-07): hanya bila sakelar Pengaturan menyala.
     *
     * @return array{label: string, alamat: string, aktif: bool, ikon: string}|null
     */
    private static function jadwal(): ?array
    {
        if (! self::sakelar('HalamanDepan.tampil_jadwal')) {
            return null;
        }

        return ['label' => 'Jadwal', 'alamat' => site_url('jadwal'), 'aktif' => url_is('jadwal') || url_is('jadwal/*'), 'ikon' => 'calendar-days'];
    }

    /**
     * Hasil setelah ada nomor yang diterbitkan (F8-04). Halaman galat tetap
     * tampil walau basis data tidak bisa dibaca.
     *
     * @return array{label: string, alamat: string, aktif: bool, ikon: string}|null
     */
    private static function hasil(): ?array
    {
        $diHasil = url_is('hasil') || url_is('hasil/*');

        try {
            $adaHasil = $diHasil || (new HasilLomba())->adaTerbit();
        } catch (Throwable) {
            $adaHasil = false;
        }

        return $adaHasil
            ? ['label' => 'Hasil', 'alamat' => site_url('hasil'), 'aktif' => $diHasil, 'ikon' => 'trophy']
            : null;
    }

    /**
     * Bantuan (F9-04): halamannya selalu hidup.
     *
     * @return array{kunci: string, label: string, alamat: string, aktif: bool, ikon: string, luar: bool}
     */
    private static function bantuan(): array
    {
        return ['kunci' => 'bantuan', 'label' => 'Bantuan', 'alamat' => site_url('bantuan'), 'aktif' => url_is('bantuan'), 'ikon' => 'circle-help', 'luar' => false];
    }

    private static function terbuka(): bool
    {
        try {
            return (new IsiHalamanDepan())->bukaTutup()->terbuka();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Pendaftaran sudah ditutup (bukan belum dibuka atau masih disiapkan).
     */
    private static function ditutup(): bool
    {
        try {
            return (new IsiHalamanDepan())->bukaTutup()->keadaan() === BukaTutupPendaftaran::DITUTUP;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Butir bilah atas dicentang admin; bawaan menyala (`Config\HalamanDepan`).
     */
    private static function dipilih(string $kunci): bool
    {
        try {
            $nilai = setting('HalamanDepan.bilah_atas_' . $kunci);
        } catch (Throwable) {
            return true;
        }

        return $nilai === null || (bool) $nilai;
    }

    private static function sakelar(string $kunci): bool
    {
        try {
            return (bool) setting($kunci);
        } catch (Throwable) {
            return false;
        }
    }
}
