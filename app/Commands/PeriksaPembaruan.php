<?php

namespace App\Commands;

use App\Libraries\PemeriksaPembaruan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark pembaruan:periksa` (K115)
 *
 * Dijalankan setelah `git pull` di server. Memeriksa apakah pustaka
 * Composer, migrasi, data sekolah, penggabungan pendaftaran, dan CSS/JS kecil
 * sudah sesuai versi baru (`App\Libraries\PemeriksaPembaruan`), lalu menulis
 * perintah yang perlu dijalankan, berurutan. Tidak ada yang diubah.
 *
 *     php spark pembaruan:periksa
 *
 *     [beres] Pustaka PHP (Composer): Sudah sesuai composer.lock.
 *     [PERLU] Struktur basis data (migrasi): 1 migrasi baru: BuatTabelContoh.
 *     ...
 *     Jalankan berurutan:
 *       php spark cadangan:buat
 *       php spark migrate --all
 *
 * Kode keluar 0 bila tidak ada yang perlu dijalankan, 1 bila ada atau bila
 * ada pemeriksaan yang gagal (misalnya MySQL mati).
 */
class PeriksaPembaruan extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'pembaruan:periksa';
    protected $description = 'Setelah git pull: memeriksa perintah apa yang perlu dijalankan (composer, migrate, sekolah:impor, pendaftaran:gabung, aset:minify). Tidak mengubah apa pun.';
    protected $usage       = 'pembaruan:periksa';

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        CLI::write('Memeriksa kesesuaian server dengan versi kode saat ini ...');
        CLI::newLine();

        $hasil = (new PemeriksaPembaruan())->periksa();

        foreach ($hasil as $baris) {
            CLI::write(
                ($baris['perlu'] ? CLI::color('[PERLU]', 'yellow') : CLI::color('[beres]', 'green'))
                . ' ' . $baris['judul'] . ': ' . $baris['keterangan'],
            );
        }

        CLI::newLine();

        $perintah = array_values(array_filter(array_column($hasil, 'perintah')));
        $gagal    = array_filter($hasil, static fn (array $baris): bool => $baris['perlu'] && $baris['perintah'] === null);

        if ($gagal !== []) {
            CLI::write('Ada pemeriksaan yang belum bisa dilakukan. Perbaiki sebabnya (lihat di atas), lalu jalankan "php spark pembaruan:periksa" lagi.', 'red');

            if ($perintah !== []) {
                CLI::newLine();
            }
        }

        if ($perintah === [] && $gagal !== []) {
            return EXIT_ERROR;
        }

        if ($perintah === []) {
            CLI::write('Tidak ada perintah lain yang perlu dijalankan.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::write('Jalankan berurutan di folder proyek:', 'yellow');
        CLI::write('  php spark cadangan:buat   (cadangan dulu, bila belum)');

        foreach ($perintah as $baris) {
            CLI::write('  ' . $baris);
        }

        CLI::newLine();
        CLI::write('Setelah selesai, jalankan "php spark pembaruan:periksa" lagi untuk memastikan semuanya beres.');

        return EXIT_ERROR;
    }
}
