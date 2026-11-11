<?php
/**
 * Halaman tidak ditemukan (404). Tampilan ada di `_galat.php`.
 *
 * @var string $message Pesan dari CodeIgniter; hanya ditampilkan di luar mode produksi.
 */

$galat = [
    'kode'   => '404',
    'judul'  => 'Halaman tidak ditemukan',
    'pesan'  => [
        'Alamat yang Anda buka tidak ada atau sudah dipindahkan.',
        'Periksa kembali alamatnya, atau kembali ke beranda untuk melanjutkan.',
    ],
    'teknis' => (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') ? (string) ($message ?? '') : '',
];

require __DIR__ . '/_galat.php';
