<?php

namespace App\Commands;

use App\Libraries\Panel\PaketPendaftaran;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

/**
 * `php spark pendaftaran:impor <berkas.zip>` (K76)
 *
 * Memasukkan zip dari tombol "Ekspor untuk pindah server" di panel
 * Pendaftaran ke server ini: pendaftaran, penanggung jawab, atlet, nomor
 * lomba, tim beregu, foto, dan bukti bayar, dengan ID pendaftaran, status, dan
 * nomor dada yang sama. Aturannya di `App\Libraries\Panel\PaketPendaftaran`:
 * hanya untuk server yang belum punya pendaftaran, dan pengaturan kelompok
 * umur serta nomor lomba harus sudah sama dengan server lama.
 */
class ImporPendaftaran extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'pendaftaran:impor';
    protected $description = 'Memasukkan zip ekspor pendaftaran dari server lama (tombol "Ekspor untuk pindah server" di panel).';
    protected $usage       = 'pendaftaran:impor <berkas.zip>';
    protected $arguments   = ['berkas.zip' => 'Jalur zip hasil ekspor dari panel Pendaftaran server lama.'];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $berkas = (string) ($params[0] ?? '');

        if ($berkas === '') {
            CLI::error('Sebutkan berkas zipnya, contoh: php spark pendaftaran:impor D:\Ekspor_Pendaftaran.zip');

            return EXIT_ERROR;
        }

        try {
            $hasil = (new PaketPendaftaran())->impor($berkas);
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf('%d pendaftaran, %d atlet, dan %d berkas (foto dan bukti bayar) berhasil diimpor.', $hasil['pendaftaran'], $hasil['atlet'], $hasil['berkas']), 'green');

        return EXIT_SUCCESS;
    }
}
