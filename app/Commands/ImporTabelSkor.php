<?php

namespace App\Commands;

use App\Libraries\Hasil\DataTabelSkor;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * `php spark tabel-skor:impor` — pasang tabel skor multi-lomba bawaan dari
 * `data/tabel-skor/*.json` (F9-10, K140 butir 7b): Trilomba O2SN SMP dan
 * Pancalomba SMA/SMK dari tabel penilaian panitia.
 *
 * - Tabel yang kodenya sudah ada dilewati, kecuali `--timpa`: semua barisnya
 *   diganti isi berkas (perubahan admin hilang), lalu poin hasil lomba yang
 *   memakainya dihitung ulang.
 * - `--trilomba`: nomor individu Trilomba yang masih berjenis hasil poin dan
 *   belum punya prestasi dipindah ke poin multi-lomba dengan tabel Trilomba.
 *
 * `data:awal` melakukan keduanya untuk pemasangan baru. Admin juga bisa
 * memasang tabel bawaan dari Pengaturan > Tabel skor.
 */
class ImporTabelSkor extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'tabel-skor:impor';
    protected $description = 'Memasang tabel skor multi-lomba bawaan (Trilomba, Pancalomba) dari data/tabel-skor.';
    protected $usage       = 'tabel-skor:impor [--timpa] [--trilomba]';
    protected $options     = [
        '--timpa'    => 'Ganti baris tabel yang sudah terpasang dengan isi berkas.',
        '--trilomba' => 'Pakai tabel Trilomba untuk nomor Trilomba yang belum punya prestasi.',
    ];

    public function run(array $params): int
    {
        $timpa = array_key_exists('timpa', $params) || CLI::getOption('timpa') !== null;
        $data  = new DataTabelSkor();

        try {
            $hasil = $data->pasangBawaan($timpa);
        } catch (Throwable $e) {
            CLI::error('Tabel skor gagal dipasang: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        foreach ($hasil['dipasang'] as $nama) {
            CLI::write('Dipasang: ' . $nama, 'green');
        }

        foreach ($hasil['sudah_ada'] as $nama) {
            CLI::write('Sudah ada, dilewati: ' . $nama . ' (pakai --timpa untuk mengganti barisnya).');
        }

        if (array_key_exists('trilomba', $params) || CLI::getOption('trilomba') !== null) {
            CLI::write($data->pakaiUntukTrilomba() . ' nomor Trilomba sekarang memakai poin multi-lomba dengan tabel Trilomba O2SN SMP.');
        } else {
            CLI::write('Pilih jenis hasil "Poin multi-lomba" untuk nomor Trilomba di Pengaturan > Nomor lomba, atau jalankan lagi dengan --trilomba.');
        }

        return EXIT_SUCCESS;
    }
}
