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
 * Diambil lewat `Factories::libraries(SesiAdmin::class)` supaya uji halaman
 * tanpa basis data bisa memasang tiruan (`tests/_support/SesiAdminUji.php`).
 */
class SesiAdmin
{
    public const KUNCI_ID    = 'admin_id';
    public const KUNCI_NAMA  = 'admin_nama';
    public const KUNCI_SIDIK = 'admin_sidik';

    /**
     * Hasil `periksa()`.
     */
    public const SAH           = 'sah';
    public const SANDI_DIGANTI = 'sandi_diganti';
    public const AKUN_HILANG   = 'akun_hilang';
    public const TANPA_SIDIK   = 'tanpa_sidik';

    public const PESAN_SANDI_DIGANTI = 'Kata sandi akun ini baru saja diganti. Silakan masuk lagi dengan kata sandi baru.';
    public const PESAN_AKUN_HILANG   = 'Akun Anda tidak ditemukan lagi, jadi Anda dikeluarkan. Masuk dengan akun lain atau hubungi pengelola web.';
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
     * Sidik hash kata sandi yang tersimpan sekarang, atau `null` bila akunnya
     * tidak ada.
     */
    public function sidikTersimpan(int $idAdmin): ?string
    {
        $hash = model(AdminModel::class)->sandiTersimpan($idAdmin);

        return $hash === null ? null : self::sidik($hash);
    }

    /**
     * Bandingkan sidik di sesi dengan hash tersimpan.
     *
     * @return self::SAH|self::SANDI_DIGANTI|self::AKUN_HILANG|self::TANPA_SIDIK
     */
    public function periksa(int $idAdmin, mixed $sidikSesi): string
    {
        $tersimpan = $this->sidikTersimpan($idAdmin);

        if ($tersimpan === null) {
            return self::AKUN_HILANG;
        }

        // Sesi dari sebelum aturan ini berlaku tidak punya sidik.
        if (! is_string($sidikSesi) || $sidikSesi === '') {
            return self::TANPA_SIDIK;
        }

        return hash_equals($tersimpan, $sidikSesi) ? self::SAH : self::SANDI_DIGANTI;
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
     * @param array<string, mixed> $admin baris tabel `admin` (`id`, `nama`, `sandi`)
     */
    public static function mulai(array $admin): void
    {
        session()->set([
            self::KUNCI_ID    => (int) $admin['id'],
            self::KUNCI_NAMA  => (string) $admin['nama'],
            self::KUNCI_SIDIK => self::sidik((string) $admin['sandi']),
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
