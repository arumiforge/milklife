<?php

namespace App\Libraries;

use App\Models\AdminModel;

/**
 * Sesi admin yang mengikuti kata sandi akunnya (K67).
 *
 * Saat masuk, sesi menyimpan **sidik** pendek dari hash kata sandi akun
 * (`admin_sidik`), bukan hash-nya. `AdminFilter` membandingkan sidik itu
 * dengan hash yang tersimpan di basis data pada setiap permintaan halaman
 * admin. Begitu kata sandi diganti (lewat halaman Akun di sesi lain atau
 * lewat `php spark admin:buat`), hash berubah, sidik tidak cocok lagi, dan
 * sesi lain itu diminta masuk ulang. Sesi yang mengganti kata sandinya
 * sendiri memperbarui sidiknya (`perbaruiSidik()`) sehingga tetap masuk.
 *
 * Tanpa kolom baru di basis data: hash kata sandi sudah berubah setiap kali
 * kata sandi diganti, karena garamnya selalu baru.
 *
 * Peran akun (`admin` atau `juri`, F11-16, K181) dibaca bersama hash di
 * pemeriksaan yang sama dan disalin ke sesi (`admin_peran`), jadi peran di
 * sesi selalu mengikuti basis data. `juri()` membaca salinan itu untuk menu
 * dan tampilan; `AdminFilter` membatasi halaman akun juri.
 *
 * Diambil lewat `Factories::libraries(SesiAdmin::class)` supaya uji halaman
 * tanpa basis data bisa memasang tiruan (`tests/_support/SesiAdminUji.php`).
 */
class SesiAdmin
{
    public const KUNCI_ID    = 'admin_id';
    public const KUNCI_NAMA  = 'admin_nama';
    public const KUNCI_SIDIK = 'admin_sidik';
    public const KUNCI_PERAN = 'admin_peran';

    /**
     * Hasil `periksa()`.
     */
    public const SAH           = 'sah';
    public const SANDI_DIGANTI = 'sandi_diganti';
    public const AKUN_HILANG   = 'akun_hilang';
    public const TANPA_SIDIK   = 'tanpa_sidik';

    public const PESAN_SANDI_DIGANTI = 'Kata sandi akun ini baru saja diganti. Silakan masuk lagi dengan kata sandi baru.';
    public const PESAN_AKUN_HILANG   = 'Akun Anda tidak ditemukan lagi, jadi Anda dikeluarkan dari panel. Masuk dengan akun lain atau hubungi pengelola web.';
    public const PESAN_TANPA_SIDIK   = 'Sesi Anda sudah berakhir. Silakan masuk lagi.';

    /**
     * Panjang sidik dalam karakter heksadesimal (64 bit).
     */
    private const PANJANG_SIDIK = 16;

    /**
     * Sidik pendek dari hash kata sandi. Cukup untuk mengenali bahwa hash
     * berubah, tanpa menyimpan hash itu sendiri di berkas sesi.
     */
    public static function sidik(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, self::PANJANG_SIDIK);
    }

    /**
     * Sidik hash kata sandi dan peran akun yang tersimpan sekarang, atau
     * `null` bila akunnya tidak ada. Satu bacaan basis data.
     *
     * @return array{sidik: string, peran: string}|null
     */
    public function akunTersimpan(int $idAdmin): ?array
    {
        $akun = model(AdminModel::class)->untukSesi($idAdmin);

        return $akun === null ? null : ['sidik' => self::sidik($akun['sandi']), 'peran' => $akun['peran']];
    }

    /**
     * Sidik hash kata sandi yang tersimpan sekarang, atau `null` bila akunnya
     * tidak ada.
     */
    public function sidikTersimpan(int $idAdmin): ?string
    {
        return $this->akunTersimpan($idAdmin)['sidik'] ?? null;
    }

    /**
     * Bandingkan sidik di sesi dengan hash tersimpan. Bila sah, peran akun
     * yang tersimpan disalin ke sesi.
     *
     * @return self::SAH|self::SANDI_DIGANTI|self::AKUN_HILANG|self::TANPA_SIDIK
     */
    public function periksa(int $idAdmin, mixed $sidikSesi): string
    {
        $akun = $this->akunTersimpan($idAdmin);

        if ($akun === null) {
            return self::AKUN_HILANG;
        }

        // Sesi dari sebelum aturan ini berlaku tidak punya sidik.
        if (! is_string($sidikSesi) || $sidikSesi === '') {
            return self::TANPA_SIDIK;
        }

        if (! hash_equals($akun['sidik'], $sidikSesi)) {
            return self::SANDI_DIGANTI;
        }

        if (session(self::KUNCI_PERAN) !== $akun['peran']) {
            session()->set(self::KUNCI_PERAN, $akun['peran']);
        }

        return self::SAH;
    }

    /**
     * Apakah sesi ini milik akun juri (salinan peran di sesi, diperbarui
     * `periksa()` di setiap permintaan halaman admin).
     */
    public static function juri(): bool
    {
        return session(self::KUNCI_PERAN) === AdminModel::PERAN_JURI;
    }

    /**
     * Pesan di halaman masuk untuk hasil `periksa()` yang bukan `SAH`.
     */
    public static function pesan(string $hasil): string
    {
        return match ($hasil) {
            self::SANDI_DIGANTI => self::PESAN_SANDI_DIGANTI,
            self::AKUN_HILANG   => self::PESAN_AKUN_HILANG,
            default             => self::PESAN_TANPA_SIDIK,
        };
    }

    /**
     * Isi sesi untuk admin yang baru masuk. ID sesi dibuat ulang oleh pemanggil.
     *
     * @param array<string, mixed> $admin baris tabel `admin` (`id`, `nama`, `sandi`, `peran`)
     */
    public static function mulai(array $admin): void
    {
        session()->set([
            self::KUNCI_ID    => (int) $admin['id'],
            self::KUNCI_NAMA  => (string) $admin['nama'],
            self::KUNCI_SIDIK => self::sidik((string) $admin['sandi']),
            self::KUNCI_PERAN => AdminModel::peranSah($admin['peran'] ?? null),
        ]);
    }

    /**
     * Setelah sesi ini mengganti kata sandinya sendiri: simpan sidik hash
     * yang baru supaya sesi ini tetap masuk.
     */
    public function perbaruiSidik(int $idAdmin): void
    {
        $sidik = $this->sidikTersimpan($idAdmin);

        if ($sidik !== null) {
            session()->set(self::KUNCI_SIDIK, $sidik);
        }
    }

    /**
     * Kosongkan seluruh isi sesi, lalu buat ID sesi baru dan hapus yang lama.
     */
    public static function akhiri(): void
    {
        $sesi = session();
        $sesi->remove(array_keys($_SESSION ?? []));
        $sesi->regenerate(true);
    }
}
