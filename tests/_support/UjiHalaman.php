<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Kelas dasar uji halaman dan rute (tanpa basis data).
 *
 * Halaman di bawah `/admin` selalu dibuka dengan `SESI_ADMIN`, supaya uji
 * tetap lulus setelah penjaga halaman admin (F1-04) terpasang:
 *
 *     $hasil = $this->withSession(self::SESI_ADMIN)->get('admin/dasbor');
 *
 * Uji halaman yang juga butuh basis data memakai `UjiBasisData` dan
 * menambahkan `use FeatureTestTrait;` sendiri.
 */
abstract class UjiHalaman extends CIUnitTestCase
{
    use FeatureTestTrait;

    /**
     * Isi sesi admin yang sudah masuk.
     */
    public const SESI_ADMIN = ['admin_id' => 1, 'admin_nama' => 'Admin Uji'];
}
