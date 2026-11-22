<?php
/**
 * Galat umum di mode produksi (rincian galat tidak ditampilkan).
 * Tampilan ada di `_galat.php`. Mode pengembangan memakai `error_exception.php`.
 */

$galat = [
    'judul'  => 'Maaf, ada kendala',
    'gambar' => 'galat-500.webp',
    'pesan'  => [
        'Halaman ini sedang tidak bisa ditampilkan karena ada gangguan di server.',
        'Muat ulang halaman ini beberapa menit lagi. Bila pesan ini masih muncul, hubungi panitia.',
    ],
];

require __DIR__ . '/_galat.php';
