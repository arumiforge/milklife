<?php
/**
 * Galat umum di mode produksi (rincian galat tidak ditampilkan).
 * Tampilan ada di `_galat.php`. Mode pengembangan memakai `error_exception.php`.
 */

$galat = [
    'judul' => 'Maaf, ada kendala',
    'pesan' => [
        'Halaman ini sedang tidak bisa ditampilkan karena ada kendala di sistem kami.',
        'Coba muat ulang halaman ini beberapa menit lagi. Bila kendala masih muncul, hubungi panitia.',
    ],
];

require __DIR__ . '/_galat.php';
