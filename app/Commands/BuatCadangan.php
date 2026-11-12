<?php

namespace App\Commands;

use App\Libraries\Cadangan\PembuatCadangan;
use App\Libraries\Unggahan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;
use RuntimeException;

/**
 * `php spark cadangan:buat` (K66)
 *
 * Menulis satu berkas `cadangan-milklife-<tttt-bb-hh-jjmm>.zip` ke
 * `cadangan.folder` berisi basis data, foto penanggung jawab, bukti bayar,
 * dan unggahan admin, lalu menghapus cadangan yang berumur
 * `cadangan.simpanHari` hari atau lebih. Dijalankan tiap malam oleh
 * Penjadwal Tugas Windows; kode keluar 1 bila gagal supaya tercatat di sana.
 * Rinciannya di `App\Libraries\Cadangan\PembuatCadangan`.
 */
class BuatCadangan extends BaseCommand
{
    protected $group       = 'Cadangan';
    protected $name        = 'cadangan:buat';
    protected $description = 'Mencadangkan basis data dan semua unggahan ke satu berkas zip di cadangan.folder, lalu menghapus cadangan yang melewati cadangan.simpanHari.';
    protected $usage       = 'cadangan:buat';

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $basisData = config(Database::class);

        try {
            $hasil = (new PembuatCadangan(config('Cadangan'), $basisData->{$basisData->defaultGroup}))->buat();
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf('Cadangan dibuat: %s (%s).', $hasil['berkas'], Unggahan::ukuranTeks((int) filesize($hasil['berkas']))), 'green');

        foreach ($hasil['dihapus'] as $nama) {
            CLI::write('Cadangan lama dihapus: ' . $nama);
        }

        return EXIT_SUCCESS;
    }
}
