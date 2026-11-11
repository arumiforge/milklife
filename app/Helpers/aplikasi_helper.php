<?php

/**
 * Fungsi bantu yang dipakai di seluruh aplikasi. Dimuat otomatis lewat
 * `app/Config/Autoload.php`.
 */

if (! function_exists('aset')) {
    /**
     * Alamat lengkap sebuah berkas di `public/aset/`.
     *
     * Bila `aplikasi.minify` aktif dan berkas `.min` padanannya ada
     * (`css/tema.css` → `css/tema.min.css`), alamat berkas `.min` yang dipakai.
     * Jalur yang sudah `.min` atau berada di `vendor/` tidak diubah.
     *
     * @param string $jalur Jalur relatif terhadap `public/aset/`, misalnya `css/tema.css`.
     */
    function aset(string $jalur): string
    {
        $jalur = ltrim($jalur, '/');

        if (config('Aplikasi')->minify && ! str_starts_with($jalur, 'vendor/')) {
            $jalurMin = preg_replace('/(?<!\.min)\.(css|js)$/', '.min.$1', $jalur);

            if ($jalurMin !== null && $jalurMin !== $jalur && is_file(FCPATH . 'aset/' . $jalurMin)) {
                $jalur = $jalurMin;
            }
        }

        return base_url('aset/' . $jalur);
    }
}
