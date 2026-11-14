<?php

namespace App\Commands;

use App\Models\SekolahModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use RuntimeException;
use Throwable;

/**
 * `php spark sekolah:impor [berkas]`
 *
 * Mengisi atau memperbarui tabel `sekolah` dari berkas JSON. Logika
 * pemeriksaan dan penyimpanan ada di `SekolahModel::impor()`; perintah ini
 * membaca berkas dan menampilkan ringkasannya.
 */
class ImporSekolah extends BaseCommand
{
    /**
     * Berkas bawaan, relatif terhadap folder proyek.
     */
    public const BERKAS_BAWAAN = 'data/sekolah/sekolah.json';

    protected $group       = 'Sekolah';
    protected $name        = 'sekolah:impor';
    protected $description = 'Mengisi atau memperbarui daftar sekolah dari berkas JSON. Aman dijalankan berulang kali.';
    protected $usage       = 'sekolah:impor [berkas]';
    protected $arguments   = [
        'berkas' => 'Berkas JSON daftar sekolah, relatif terhadap folder proyek. Bila dikosongkan: ' . self::BERKAS_BAWAAN,
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $mulai  = microtime(true);
        $berkas = (string) ($params[0] ?? '');
        $berkas = trim($berkas) === '' ? self::BERKAS_BAWAAN : trim($berkas);
        $jalur  = self::jalurLengkap($berkas);

        try {
            $daftar = self::bacaBerkas($jalur);
        } catch (RuntimeException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Membaca ' . $berkas . ' ...');

        $model = model(SekolahModel::class);

        try {
            $hasil = $model->impor($daftar);
        } catch (Throwable $e) {
            CLI::error(
                'Daftar sekolah gagal disimpan ke basis data. Pastikan MySQL menyala, pengaturan basis data di .env benar, '
                . 'dan tabel sudah dibuat dengan "php spark migrate --all", lalu jalankan perintah ini lagi. '
                . 'Data yang sudah tersimpan sebelum galat tetap ada; menjalankan ulang akan melengkapinya.',
            );
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $this->tampilkanRingkasan($hasil, $model->countAllResults(), microtime(true) - $mulai);

        return EXIT_SUCCESS;
    }

    /**
     * Ubah jalur relatif menjadi jalur di bawah folder proyek (`ROOTPATH`).
     */
    public static function jalurLengkap(string $berkas): string
    {
        // Jalur mutlak: /..., \..., atau C:\... (Windows/Laragon).
        if (preg_match('#^([a-zA-Z]:)?[\\\\/]#', $berkas) === 1) {
            return $berkas;
        }

        return ROOTPATH . $berkas;
    }

    /**
     * Baca berkas JSON berisi daftar sekolah.
     *
     * @return list<mixed>
     *
     * @throws RuntimeException Dengan pesan yang menyebut cara memperbaikinya
     */
    public static function bacaBerkas(string $jalur): array
    {
        if (! is_file($jalur)) {
            throw new RuntimeException(
                'Berkas daftar sekolah tidak ditemukan: ' . $jalur . '. '
                . 'Periksa nama dan letak berkasnya, atau jalankan "php spark sekolah:impor" tanpa nama berkas '
                . 'untuk memakai ' . self::BERKAS_BAWAAN . '.',
            );
        }

        $isi = is_readable($jalur) ? @file_get_contents($jalur) : false;

        if ($isi === false) {
            throw new RuntimeException(
                'Berkas daftar sekolah tidak bisa dibuka: ' . $jalur . '. '
                . 'Pastikan berkas tidak sedang dikunci program lain dan boleh dibaca oleh pengguna komputer ini, lalu coba lagi.',
            );
        }

        // Buang BOM UTF-8 yang kadang ditambahkan penyunting teks di Windows.
        if (str_starts_with($isi, "\xEF\xBB\xBF")) {
            $isi = substr($isi, 3);
        }

        $daftar = json_decode($isi, true, 16);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(
                'Isi berkas ' . $jalur . ' bukan JSON yang benar, mungkin rusak atau terpotong. '
                . 'Ambil ulang berkasnya dengan cara di data/README.md, atau periksa tanda koma, kutip, dan kurung di dalamnya.',
            );
        }

        if (! is_array($daftar) || ! array_is_list($daftar)) {
            throw new RuntimeException(
                'Isi berkas ' . $jalur . ' harus berupa daftar sekolah yang diawali "[" dan diakhiri "]", '
                . 'dengan satu { ... } untuk tiap sekolah. Contoh bentuk yang benar ada di ' . self::BERKAS_BAWAAN . '.',
            );
        }

        return $daftar;
    }

    /**
     * @param array<string, mixed> $hasil Hasil `SekolahModel::impor()`
     */
    private function tampilkanRingkasan(array $hasil, int $jumlahTabel, float $detik): void
    {
        $angka = static fn (int $n): string => number_format($n, 0, ',', '.');

        if ($hasil['contoh_dilewati'] !== []) {
            CLI::newLine();
            CLI::write('Data yang dilewati (paling banyak ' . SekolahModel::BATAS_CONTOH . ' contoh pertama):', 'yellow');

            foreach ($hasil['contoh_dilewati'] as $contoh) {
                CLI::write('  - ' . $contoh);
            }
        }

        CLI::newLine();
        CLI::write('Ringkasan impor sekolah', 'green');
        CLI::write('  Dibaca     : ' . $angka($hasil['dibaca']));
        CLI::write('  Ditambah   : ' . $angka($hasil['ditambah']));
        CLI::write('  Diperbarui : ' . $angka($hasil['diperbarui']));
        CLI::write('  Dilewati   : ' . $angka($hasil['dilewati']));

        if ($hasil['dipertahankan'] > 0) {
            CLI::write('  Tidak diubah karena sudah disunting admin: ' . $angka($hasil['dipertahankan']));
        }

        if ($hasil['per_kabupaten'] !== []) {
            CLI::newLine();
            CLI::write('Jumlah per kabupaten (yang tersimpan dari berkas ini):');
            $lebar = max(array_map(mb_strlen(...), array_keys($hasil['per_kabupaten'])));

            foreach ($hasil['per_kabupaten'] as $kabupaten => $jumlah) {
                CLI::write('  ' . str_pad((string) $kabupaten, $lebar) . ' : ' . $angka($jumlah));
            }
        }

        CLI::newLine();
        CLI::write('Jumlah sekolah di basis data sekarang: ' . $angka($jumlahTabel));
        CLI::write('Selesai dalam ' . number_format($detik, 1, ',', '.') . ' detik.');
    }
}
