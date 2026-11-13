<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\I18n\Time;

/**
 * `php spark nomor-dada:reset` — mengosongkan semua nomor dada setelah uji
 * coba (K94), supaya nomor untuk peserta sungguhan mulai lagi dari awal.
 *
 * Dalam satu transaksi:
 * - `atlet.nomor_dada` semua atlet dikosongkan;
 * - `awalan_nomor_dada.nomor_terakhir` kembali 0 (awalannya sendiri tetap);
 * - pendaftaran Lunas kembali Menunggu (`waktu_lunas` dikosongkan), karena
 *   pendaftaran Lunas selalu punya nomor dada. Tandai Lunas lagi di panel
 *   untuk memberi nomor baru.
 *
 * Ini satu-satunya pengecualian aturan "nomor dada tidak pernah dipakai
 * ulang". Jangan dijalankan setelah nomor dada dibagikan ke peserta.
 * Perintah meminta pengguna mengetik RESET; `--ya` melewati pertanyaan itu
 * (untuk skrip).
 */
class ResetNomorDada extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'nomor-dada:reset';
    protected $description = 'Mengosongkan semua nomor dada (setelah uji coba) dan mengembalikan pendaftaran Lunas ke Menunggu.';
    protected $usage       = 'nomor-dada:reset [--ya]';
    protected $options     = [
        '--ya' => 'Jalankan tanpa pertanyaan konfirmasi.',
    ];

    public function run(array $params): int
    {
        $db     = db_connect();
        $nomor  = $db->table('atlet')->where('nomor_dada IS NOT NULL')->countAllResults();
        $lunas  = $db->table('pendaftaran')->where('status', 'lunas')->countAllResults();

        CLI::write("Akan dikosongkan: {$nomor} nomor dada. Pendaftaran Lunas yang kembali menjadi Menunggu: {$lunas}.", 'yellow');
        CLI::write('Penghitung semua awalan kembali ke awal. Tindakan ini tidak bisa dibatalkan; buat cadangan dulu bila ragu (php spark cadangan:buat).');

        if (! array_key_exists('ya', $params) && CLI::getOption('ya') === null
            && trim(CLI::prompt('Ketik RESET untuk melanjutkan')) !== 'RESET') {
            CLI::write('Dibatalkan. Tidak ada yang diubah.');

            return EXIT_SUCCESS;
        }

        $sekarang = Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');

        $db->transException(true)->transStart();
        $db->table('atlet')->where('nomor_dada IS NOT NULL')->update(['nomor_dada' => null, 'diubah_pada' => $sekarang]);
        $db->table('awalan_nomor_dada')->update(['nomor_terakhir' => 0, 'diubah_pada' => $sekarang]);
        $db->table('pendaftaran')->where('status', 'lunas')->update(['status' => 'menunggu', 'waktu_lunas' => null, 'diubah_pada' => $sekarang]);
        $db->transComplete();

        CLI::write('Selesai. Semua nomor dada kosong; tandai Lunas lagi di panel untuk memberi nomor baru mulai dari awal.', 'green');

        return EXIT_SUCCESS;
    }
}
