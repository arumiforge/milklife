<?php

/**
 * Pastikan berkas bawaan yang disalin `spark data:awal` ikut masuk image
 * Docker (`.dockerignore`). Dijalankan CI di dalam image:
 *
 *     docker run --rm --entrypoint php milklife-app:ci docker/periksa-berkas-bawaan.php
 */

use App\Commands\DataAwal;
use App\Libraries\CarouselHalamanDepan;

define('ROOTPATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);

require ROOTPATH . 'vendor/autoload.php';

$berkas = array_column(DataAwal::UNDUHAN, 2);

foreach (DataAwal::GAMBAR_BAWAAN as [$jalur]) {
    $berkas[] = 'data/' . $jalur;
}

foreach (array_keys(CarouselHalamanDepan::BAWAAN) as $jalur) {
    $berkas[] = 'data/' . $jalur;
}

$hilang = array_values(array_filter($berkas, static fn (string $jalur): bool => ! is_file(ROOTPATH . $jalur)));

if ($hilang !== []) {
    fwrite(STDERR, "Berkas bawaan tidak ada di image (periksa .dockerignore):\n- " . implode("\n- ", $hilang) . "\n");

    exit(1);
}

echo count($berkas) . " berkas bawaan ada di image.\n";
