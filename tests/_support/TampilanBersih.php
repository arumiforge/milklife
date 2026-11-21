<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;

/**
 * Data view tidak terbawa dari satu uji ke uji berikutnya.
 *
 * `view()` menyimpan data yang diberikan di layanan bersama `renderer`
 * (`Config\View::$saveData = true`), yang tetap hidup sepanjang proses
 * PHPUnit. Di server setiap permintaan proses baru, jadi tidak masalah; di uji,
 * `$galat` dari formulir yang ditampilkan ulang di satu uji bisa muncul lagi
 * di view uji lain yang tidak mengirim `$galat` (misalnya
 * `admin/_galat_isian` di `BerandaPengaturanTest`). Sifat ini membuat
 * `renderer` ulang sebelum dan sesudah setiap uji.
 *
 * Sudah dipakai `UjiBasisData` dan `UjiHalaman`; dipanggil otomatis oleh
 * `CIUnitTestCase` lewat `setUpTampilanBersih()` dan `tearDownTampilanBersih()`.
 */
trait TampilanBersih
{
    protected function setUpTampilanBersih(): void
    {
        Services::resetSingle('renderer');
    }

    protected function tearDownTampilanBersih(): void
    {
        Services::resetSingle('renderer');
    }
}
