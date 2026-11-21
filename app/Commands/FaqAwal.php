<?php

namespace App\Commands;

use App\Libraries\Bantuan\FaqBawaan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * `php spark faq:awal`
 *
 * Memasang tanya-jawab bawaan halaman Bantuan (F9-04) di server yang
 * dipasang sebelum ada halaman Bantuan. Pemasangan baru mendapatkannya dari
 * `php spark data:awal`.
 *
 * Hanya mengisi bila tabel `faq` masih kosong: tanya-jawab yang sudah ditulis
 * atau diubah admin tidak pernah ditimpa. Jalankan setelah
 * `php spark migrate --all`.
 */
class FaqAwal extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'faq:awal';
    protected $description = 'Memasang tanya-jawab bawaan halaman Bantuan bila belum ada. Isian admin tidak ditimpa.';
    protected $usage       = 'faq:awal';

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        try {
            $jumlah = FaqBawaan::pasang(db_connect());
        } catch (Throwable $e) {
            CLI::error(
                'Tanya-jawab bawaan gagal dipasang. Pastikan MySQL menyala dan tabel sudah dibuat dengan '
                . '"php spark migrate --all", lalu jalankan perintah ini lagi.',
            );
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if ($jumlah === 0) {
            CLI::write('Tanya-jawab Bantuan sudah ada, jadi tidak ada yang diubah. Kelola isinya di Pengaturan > Bantuan.');

            return EXIT_SUCCESS;
        }

        CLI::write($jumlah . ' tanya-jawab bawaan dipasang. Periksa dan sesuaikan isinya di Pengaturan > Bantuan.', 'green');

        return EXIT_SUCCESS;
    }
}
