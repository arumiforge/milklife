<?php

namespace App\Commands;

use App\Libraries\Cadangan\CadanganLengkap;
use App\Libraries\Panel\RiwayatAdmin;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;

/**
 * `php spark cadangan:pulihkan <berkas.zip> [--ya]` (F11-11, K174)
 *
 * Sama dengan Impor cadangan di Pengaturan > Cadangan, untuk berkas yang
 * terlalu besar diunggah lewat peramban: semua data, pengaturan, dan berkas
 * unggahan diganti isi cadangan, setelah data sekarang dicadangkan otomatis
 * di `writable/cadangan/`. Tanpa `--ya` perintah meminta admin mengetik GANTI.
 * Aturannya di `App\Libraries\Cadangan\CadanganLengkap`.
 */
class PulihkanCadangan extends BaseCommand
{
    protected $group       = 'Cadangan';
    protected $name        = 'cadangan:pulihkan';
    protected $description = 'Mengganti semua data, pengaturan, dan berkas unggahan dengan isi berkas dari Pengaturan > Cadangan.';
    protected $usage       = 'cadangan:pulihkan <berkas.zip> [--ya]';
    protected $arguments   = ['berkas.zip' => 'Jalur berkas cadangan yang diunduh dari Pengaturan > Cadangan.'];
    protected $options     = ['--ya' => 'Lanjutkan tanpa pertanyaan konfirmasi.'];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $berkas = (string) ($params[0] ?? '');

        if ($berkas === '') {
            CLI::error('Sebutkan berkas cadangannya, contoh: php spark cadangan:pulihkan D:\Cadangan_MilkLife_20261124_101500.zip');

            return EXIT_ERROR;
        }

        if (! array_key_exists('ya', $params) && CLI::getOption('ya') === null
            && trim(CLI::prompt('Semua data, pengaturan, dan berkas unggahan di web ini akan diganti isi cadangan. Ketik GANTI untuk melanjutkan')) !== 'GANTI') {
            CLI::write('Dibatalkan. Tidak ada yang berubah.');

            return EXIT_SUCCESS;
        }

        @set_time_limit(0);

        try {
            $hasil = (new CadanganLengkap())->impor($berkas);
            (new RiwayatAdmin())->catat(null, RiwayatAdmin::TANPA_ID, RiwayatAdmin::IMPOR_CADANGAN, sprintf(
                'Cadangan %s dipulihkan lewat perintah server: semua data dan pengaturan diganti (%d baris data dari %d tabel, %d berkas unggahan). Data sebelumnya disimpan sebagai %s.',
                mb_substr(basename($berkas), 0, 150),
                $hasil['baris'],
                $hasil['tabel'],
                $hasil['berkas'],
                basename((string) $hasil['cadangan_otomatis']),
            ));
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write(sprintf('Cadangan dipulihkan: %d baris data dari %d tabel dan %d berkas unggahan.', $hasil['baris'], $hasil['tabel'], $hasil['berkas']), 'green');
        CLI::write('Data sebelumnya disimpan di ' . $hasil['cadangan_otomatis']);

        foreach ($hasil['peringatan'] as $peringatan) {
            CLI::error($peringatan);
        }

        return $hasil['peringatan'] === [] ? EXIT_SUCCESS : EXIT_ERROR;
    }
}
