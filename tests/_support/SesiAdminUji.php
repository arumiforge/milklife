<?php

namespace Tests\Support;

use App\Libraries\SesiAdmin;
use CodeIgniter\Config\Factories;

/**
 * Sesi admin di uji (K67).
 *
 * `AdminFilter` membandingkan sidik kata sandi di sesi dengan hash di tabel
 * `admin` pada setiap permintaan halaman admin. Kebanyakan uji halaman tidak
 * menyiapkan akun admin sungguhan (sebagian bahkan tanpa basis data), jadi
 * sebelum setiap uji dipasang `SesiAdminTiruan`: semua akun dianggap sah
 * selama sesinya memuat sidik `SesiAdminTiruan::SIDIK`, seperti
 * `UjiHalaman::SESI_ADMIN`.
 *
 * Uji yang memeriksa sidik sungguhan (ganti kata sandi mengeluarkan sesi
 * lain) memanggil `pakaiSesiAdminAsli()` dan mengisi sesi dengan
 * `sesiAdminAsli()`.
 *
 * Sudah dipakai `UjiBasisData` dan `UjiHalaman`; dipanggil otomatis oleh
 * `CIUnitTestCase` lewat `setUpSesiAdminUji()` (sesudah Factories dikosongkan).
 */
trait SesiAdminUji
{
    protected function setUpSesiAdminUji(): void
    {
        Factories::injectMock('libraries', SesiAdmin::class, new SesiAdminTiruan());
    }

    /**
     * Pakai `SesiAdmin` sungguhan (membaca hash dari basis data uji).
     */
    protected function pakaiSesiAdminAsli(): void
    {
        Factories::injectMock('libraries', SesiAdmin::class, new SesiAdmin());
    }

    /**
     * Isi sesi admin yang masuk dengan kata sandi yang tersimpan sekarang.
     *
     * @return array{admin_id: int, admin_nama: string, admin_sidik: string}
     */
    protected function sesiAdminAsli(int $idAdmin, string $nama = 'Admin Uji'): array
    {
        $sidik = (new SesiAdmin())->sidikTersimpan($idAdmin);
        $this->assertNotNull($sidik, 'Akun admin ' . $idAdmin . ' tidak ada.');

        return [
            SesiAdmin::KUNCI_ID    => $idAdmin,
            SesiAdmin::KUNCI_NAMA  => $nama,
            SesiAdmin::KUNCI_SIDIK => $sidik,
        ];
    }
}
