<?php

namespace App\Commands;

use App\Libraries\Cadangan\PembuatCadangan;
use App\Libraries\Cadangan\SalinanR2;
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
 * `cadangan.simpanHari` hari atau lebih. Bila salinan Cloudflare R2 sudah
 * diatur di Pengaturan > Cadangan (F11-19, K185), zip baru juga dikirim ke
 * bucket. Dijalankan tiap malam oleh cron host; kode keluar 1 bila gagal
 * (termasuk kiriman R2, walau zip di server sudah jadi) supaya tercatat di
 * log cron. Rinciannya di `App\Libraries\Cadangan\PembuatCadangan` dan
 * `SalinanR2`.
 */
class BuatCadangan extends BaseCommand
{
    protected $group       = 'Cadangan';
    protected $name        = 'cadangan:buat';
    protected $description = 'Mencadangkan basis data dan semua unggahan ke satu berkas zip di cadangan.folder, menghapus cadangan yang melewati cadangan.simpanHari, lalu mengirim zip baru ke Cloudflare R2 bila sudah diatur.';
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

        try {
            $tujuan = SalinanR2::kirim($hasil['berkas']);
        } catch (RuntimeException $e) {
            CLI::error('Cadangan tersimpan di server, tetapi gagal dikirim ke Cloudflare R2: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write($tujuan === null
            ? 'Salinan Cloudflare R2 belum diatur (Pengaturan > Cadangan); cadangan hanya tersimpan di server.'
            : 'Dikirim ke Cloudflare R2: ' . $tujuan . '.', $tujuan === null ? 'yellow' : 'green');

        return EXIT_SUCCESS;
    }
}
