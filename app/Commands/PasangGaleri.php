<?php

namespace App\Commands;

use App\Libraries\Galeri\AlbumBawaan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * `php spark galeri:awal` — memasang album galeri bawaan "Seri sebelumnya"
 * (F9-06) di pemasangan lama yang sudah menjalankan `data:awal` sebelum
 * galeri ada. Pemasangan baru mendapatkannya dari `data:awal`.
 *
 * Aman dijalankan berulang: album hanya dipasang sekali, dan tidak dipasang
 * bila admin sudah mengisi galeri sendiri. Jalankan setelah
 * `php spark migrate --all`.
 */
class PasangGaleri extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'galeri:awal';
    protected $description = 'Memasang album galeri bawaan "Seri sebelumnya" bila belum pernah dipasang. Isian admin tidak ditimpa.';
    protected $usage       = 'galeri:awal';

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        try {
            $hasil = AlbumBawaan::pasang();
        } catch (Throwable $e) {
            CLI::error('Album galeri bawaan gagal dipasang. Pastikan MySQL menyala dan "php spark migrate --all" sudah dijalankan, lalu coba lagi.');
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        self::tulisHasil($hasil);

        return EXIT_SUCCESS;
    }

    /**
     * Pesan hasil pemasangan, dipakai juga `data:awal`.
     *
     * @param array{sampul: bool}|null $hasil hasil `AlbumBawaan::pasang()`
     */
    public static function tulisHasil(?array $hasil): void
    {
        if ($hasil === null) {
            CLI::write('Album galeri bawaan sudah pernah dipasang atau galeri sudah diisi admin, jadi tidak diubah.');

            return;
        }

        CLI::write('Album galeri "' . AlbumBawaan::JUDUL . '" dipasang. Isi tautan Instagram atau Google Drive-nya di Pengaturan > Galeri supaya tampil untuk umum.');

        if (! $hasil['sampul']) {
            CLI::error('Sampul album gagal disalin. Pastikan folder public/unggahan boleh ditulisi, lalu unggah sampulnya lewat Pengaturan > Galeri.');
        }
    }
}
