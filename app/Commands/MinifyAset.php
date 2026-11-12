<?php

namespace App\Commands;

use App\Libraries\Unggahan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use FilesystemIterator;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

/**
 * `php spark aset:minify`
 *
 * Membuat versi kecil (`.min.css`, `.min.js`) dari setiap berkas CSS dan
 * JavaScript milik aplikasi di `public/aset/`, di sebelah berkas aslinya.
 * Folder `vendor/` (pustaka yang sudah memakai versi kecilnya sendiri) dan
 * berkas yang sudah berakhiran `.min` dilewati.
 *
 * Berkas `.min` tidak masuk git; perintah ini dijalankan di server setiap
 * kali aplikasi dipasang atau diperbarui. Halaman memakai berkas `.min` bila
 * `aplikasi.minify = true` di `.env` (lihat `aset()` di `aplikasi_helper`).
 * Menjalankan ulang aman: berkas `.min` lama ditimpa dengan hasil baru.
 */
class MinifyAset extends BaseCommand
{
    protected $group       = 'Aset';
    protected $name        = 'aset:minify';
    protected $description = 'Membuat versi kecil (.min.css, .min.js) dari CSS dan JavaScript aplikasi di public/aset/. Aman dijalankan berulang kali.';
    protected $usage       = 'aset:minify';

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $folder = FCPATH . 'aset';

        try {
            $hasil = self::perkecil($folder);
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        if ($hasil === []) {
            CLI::write('Tidak ada berkas CSS atau JavaScript aplikasi di public/aset/.');

            return EXIT_SUCCESS;
        }

        $totalSebelum = 0;
        $totalSesudah = 0;

        foreach ($hasil as $baris) {
            CLI::write(sprintf(
                '%s: %s menjadi %s',
                $baris['berkas'],
                Unggahan::ukuranTeks($baris['sebelum']),
                Unggahan::ukuranTeks($baris['sesudah']),
            ));
            $totalSebelum += $baris['sebelum'];
            $totalSesudah += $baris['sesudah'];
        }

        CLI::newLine();
        CLI::write(sprintf(
            '%d berkas diperkecil, dari %s menjadi %s.',
            count($hasil),
            Unggahan::ukuranTeks($totalSebelum),
            Unggahan::ukuranTeks($totalSesudah),
        ), 'green');

        return EXIT_SUCCESS;
    }

    /**
     * Tulis berkas `.min` untuk setiap `*.css` dan `*.js` di bawah `$folder`,
     * kecuali di folder `vendor/` dan berkas yang sudah `.min`.
     *
     * @return list<array{berkas: string, sebelum: int, sesudah: int}> Berurutan menurut jalur; `berkas` relatif terhadap `$folder`, pemisah `/`.
     *
     * @throws RuntimeException Bila folder tidak ada atau berkas `.min` gagal ditulis; pesannya ramah.
     */
    public static function perkecil(string $folder): array
    {
        $folder = rtrim($folder, '/\\');

        if (! is_dir($folder)) {
            throw new RuntimeException('Folder aset tidak ditemukan: ' . $folder . '. Jalankan perintah ini dari folder proyek.');
        }

        $hasil = [];

        foreach (self::daftarBerkas($folder) as $relatif) {
            $asli     = $folder . DIRECTORY_SEPARATOR . $relatif;
            $ekstensi = strtolower(pathinfo($asli, PATHINFO_EXTENSION));
            $tujuan   = substr($asli, 0, -strlen($ekstensi)) . 'min.' . $ekstensi;
            $pengecil = $ekstensi === 'css' ? new CSS($asli) : new JS($asli);
            $isi      = $pengecil->minify();

            if (@file_put_contents($tujuan, $isi) === false) {
                throw new RuntimeException('Berkas ' . $tujuan . ' gagal ditulis. Pastikan folder public/aset boleh ditulisi, lalu jalankan perintah ini lagi.');
            }

            $hasil[] = [
                'berkas'  => str_replace(DIRECTORY_SEPARATOR, '/', $relatif),
                'sebelum' => (int) filesize($asli),
                'sesudah' => strlen($isi),
            ];
        }

        return $hasil;
    }

    /**
     * Berkas CSS/JS asli di bawah `$folder`, relatif dan berurutan.
     *
     * @return list<string>
     */
    private static function daftarBerkas(string $folder): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $berkas): bool => ! ($berkas->isDir() && strtolower($berkas->getFilename()) === 'vendor'),
        ));

        $daftar = [];

        foreach ($iterator as $berkas) {
            /** @var SplFileInfo $berkas */
            $nama = strtolower($berkas->getFilename());

            if (! $berkas->isFile() || preg_match('/\.(css|js)$/', $nama) !== 1 || preg_match('/\.min\.(css|js)$/', $nama) === 1) {
                continue;
            }

            $daftar[] = substr($berkas->getPathname(), strlen($folder) + 1);
        }

        sort($daftar);

        return $daftar;
    }
}
