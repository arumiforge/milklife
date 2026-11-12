<?php

/**
 * Router server bawaan PHP untuk `HalamanGalatProduksiTest`.
 *
 * Pengaturan dikirim lewat variabel lingkungan `MILKLIFE_UJI_PENGATURAN`
 * (JSON), lalu diisikan ke `$_ENV` dan `$_SERVER` dengan nama aslinya
 * (`app.baseURL`, dan seterusnya) sebelum CodeIgniter memuat `.env`. Nilai
 * yang sudah terisi tidak ditimpa `.env` (`DotEnv::setVariable()`). Nama
 * bertitik tidak bisa dikirim langsung sebagai variabel lingkungan, karena
 * PHP mengubah titik menjadi garis bawah.
 */

$pengaturan = json_decode((string) getenv('MILKLIFE_UJI_PENGATURAN'), true);

foreach (is_array($pengaturan) ? $pengaturan : [] as $nama => $nilai) {
    $_ENV[$nama]    = (string) $nilai;
    $_SERVER[$nama] = (string) $nilai;
    putenv($nama . '=' . $nilai);
}

return require (string) getenv('MILKLIFE_UJI_REWRITE');
