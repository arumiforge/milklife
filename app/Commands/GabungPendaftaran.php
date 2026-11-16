<?php

namespace App\Commands;

use App\Libraries\Panel\GabungPendaftaran as Gabung;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark pendaftaran:gabung` — menggabungkan pendaftaran Lunas dengan
 * NPSN sama yang sudah ada sebelum penggabungan otomatis dipasang (K109).
 *
 * Dijalankan sekali setelah pembaruan dipasang. Untuk setiap NPSN yang punya
 * dua pendaftaran Lunas atau lebih, semuanya digabung ke pendaftaran yang
 * Lunas paling dulu (`App\Libraries\Panel\GabungPendaftaran`), satu NPSN satu
 * transaksi. Setelah itu penggabungan berjalan sendiri setiap kali admin
 * menandai Lunas, jadi menjalankannya lagi tidak mengubah apa pun.
 *
 * Perintah menampilkan daftarnya dulu dan meminta pengguna mengetik GABUNG;
 * `--ya` melewati pertanyaan itu (untuk skrip).
 */
class GabungPendaftaran extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'pendaftaran:gabung';
    protected $description = 'Menggabungkan pendaftaran Lunas dengan NPSN sama yang sudah ada (sekali setelah pembaruan K109).';
    protected $usage       = 'pendaftaran:gabung [--ya]';
    protected $options     = [
        '--ya' => 'Jalankan tanpa pertanyaan konfirmasi.',
    ];

    public function run(array $params): int
    {
        $db    = db_connect();
        $calon = $db->query(
            "SELECT npsn, COUNT(*) AS jumlah, MIN(nama_sekolah) AS nama_sekolah FROM pendaftaran WHERE status = 'lunas' AND npsn IS NOT NULL AND npsn <> '' GROUP BY npsn HAVING COUNT(*) > 1 ORDER BY npsn",
        )->getResultArray();

        if ($calon === []) {
            CLI::write('Tidak ada yang perlu digabung: setiap sekolah paling banyak punya satu pendaftaran Lunas.', 'green');

            return EXIT_SUCCESS;
        }

        CLI::write('Sekolah yang punya lebih dari satu pendaftaran Lunas:', 'yellow');

        foreach ($calon as $baris) {
            CLI::write('  ' . $baris['npsn'] . '  ' . $baris['nama_sekolah'] . '  (' . $baris['jumlah'] . ' pendaftaran)');
        }

        CLI::write('Setiap sekolah digabung ke pendaftaran yang Lunas paling dulu. ID lama tetap bisa dipakai di Cek Pendaftaran dan kode QR bukti lama.');
        CLI::write('Penggabungan tidak bisa dipisah lagi; buat cadangan dulu bila ragu (php spark cadangan:buat).');

        if (! array_key_exists('ya', $params) && CLI::getOption('ya') === null
            && trim(CLI::prompt('Ketik GABUNG untuk melanjutkan')) !== 'GABUNG') {
            CLI::write('Dibatalkan. Tidak ada yang diubah.');

            return EXIT_SUCCESS;
        }

        $gabung = new Gabung($db);
        $jumlah = 0;

        foreach ($calon as $baris) {
            $kunci = 'milklife-pendaftaran-' . $baris['npsn'];

            if ((int) ($db->query('SELECT GET_LOCK(?, 10) AS dapat', [$kunci])->getRowArray()['dapat'] ?? 0) !== 1) {
                CLI::error('  ' . $baris['npsn'] . ': pendaftaran sekolah ini sedang disimpan di tempat lain. Jalankan perintah ini lagi sebentar lagi.');

                continue;
            }

            try {
                $hasil = $gabung->gabungNpsn((string) $baris['npsn']);
            } finally {
                $db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
            }

            if ($hasil === null) {
                continue;
            }

            $gabung->hapusFoto($hasil['foto']);
            $jumlah++;
            CLI::write('  ' . implode(', ', $hasil['digabung']) . ' → ' . $hasil['tujuan']['id_pendaftaran'], 'green');
        }

        CLI::write("Selesai. {$jumlah} sekolah digabung.", 'green');

        return EXIT_SUCCESS;
    }
}
