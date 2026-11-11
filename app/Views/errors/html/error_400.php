<?php
/**
 * Permintaan tidak bisa diproses (400). Tampilan ada di `_galat.php`.
 *
 * @var string $message Pesan dari CodeIgniter; hanya ditampilkan di luar mode produksi.
 */

$galat = [
    'kode'   => '400',
    'judul'  => 'Permintaan tidak bisa diproses',
    'pesan'  => [
        'Data yang dikirim ke halaman ini tidak lengkap atau tidak sesuai.',
        'Kembali ke halaman sebelumnya lalu coba lagi, atau mulai lagi dari beranda.',
    ],
    'teknis' => (defined('ENVIRONMENT') && ENVIRONMENT !== 'production') ? (string) ($message ?? '') : '',
];

require __DIR__ . '/_galat.php';
