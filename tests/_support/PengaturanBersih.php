<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;

/**
 * Nilai `codeigniter4/settings` tidak terbawa dari satu uji ke uji berikutnya.
 *
 * Pustaka settings menyimpan nilai yang sudah dibaca di layanan bersama
 * `service('settings')`, yang tetap hidup sepanjang proses PHPUnit. Sifat ini
 * membuat layanan itu ulang sebelum dan sesudah setiap uji:
 *
 * - Uji berbasis data (`UjiBasisData`): pengaturan dibaca dan ditulis ke tabel
 *   `settings` di basis data uji, yang dikosongkan migrasi ulang sebelum setiap uji.
 * - Uji tanpa basis data (`UjiHalaman`): pengaturan disimpan di memori saja
 *   (penangan `array`), kosong di awal setiap uji, dan tidak menyentuh basis data.
 *
 * Sudah dipakai `UjiBasisData` dan `UjiHalaman`; kelas uji turunannya tidak
 * perlu memasangnya lagi. Di dalam satu uji, nilai yang ditulis pengendali
 * (`setting('Acara.nama', …)`) langsung terbaca oleh permintaan berikutnya dan
 * oleh `setting('Acara.nama')` di uji.
 *
 * Dipanggil otomatis oleh `CIUnitTestCase` lewat `setUpPengaturanBersih()`
 * dan `tearDownPengaturanBersih()`.
 */
trait PengaturanBersih
{
    protected function setUpPengaturanBersih(): void
    {
        // `config('Settings')` baru di setiap uji (Factories dikosongkan
        // `CIUnitTestCase`), jadi perubahan ini tidak terbawa.
        if (! method_exists($this, 'setUpDatabase')) {
            config('Settings')->handlers = ['array'];
        }

        Services::resetSingle('settings');
    }

    protected function tearDownPengaturanBersih(): void
    {
        Services::resetSingle('settings');
    }
}
