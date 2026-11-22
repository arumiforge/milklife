<?php

namespace App\Commands;

use App\Libraries\NomorWa;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * `php spark data:sesuaikan` (F10-01)
 *
 * Menyamakan data bawaan server yang diisi `data:awal` versi lama dengan data
 * bawaan terbaru (permintaan Tahap 10):
 *
 * - kelompok umur `KU 8 (Seneng Playon)` menjadi `KU 8`, supaya backup
 *   pendaftaran yang memakai nama `KU 8` bisa diimpor (`pendaftaran:impor`);
 *   dilewati bila `KU 8` sudah ada;
 * - `Pembayaran.nomor_wa` dan `HalamanDepan.nomor_wa_tanya` yang masih nomor
 *   contoh `628123456789` menjadi `6288215277227`;
 * - media sosial bawaan lama (`milklife.athletics`, `@milklife.athletics`,
 *   `@milklifeathletics`) menjadi alamat resmi MilkLife di `DataAwal::TAUTAN`.
 *
 * Setiap langkah hanya mengubah nilai bawaan lama; isian yang sudah diubah
 * admin dan data pendaftaran tidak disentuh, jadi aman dijalankan berkali-kali.
 * Perubahan basis data ditulis dalam satu transaksi.
 */
class SesuaikanData extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'data:sesuaikan';
    protected $description = 'Menyamakan data bawaan lama (nama KU 8, nomor WA contoh, media sosial lama) dengan data bawaan terbaru. Isian admin tidak ditimpa; aman dijalankan lagi.';
    protected $usage       = 'data:sesuaikan';

    /**
     * Nama kelompok umur KU 8 lama => baru.
     */
    public const NAMA_KU_LAMA = 'KU 8 (Seneng Playon)';

    public const NAMA_KU_BARU = 'KU 8';

    /**
     * Nomor WA contoh `data:awal` lama (bentuk baku).
     */
    public const WA_LAMA = '628123456789';

    /**
     * Kunci pengaturan nomor WA => nama untuk ringkasan.
     */
    public const KUNCI_WA = [
        'Pembayaran.nomor_wa'         => 'Nomor WA konfirmasi pembayaran (Pengaturan > Pembayaran)',
        'HalamanDepan.nomor_wa_tanya' => 'Nomor WA tanya admin (Pengaturan > Halaman Depan)',
    ];

    /**
     * Alamat media sosial bawaan lama => [label, alamat baru].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const MEDIA_SOSIAL_LAMA = [
        'https://www.instagram.com/milklife.athletics' => ['Instagram', 'https://www.instagram.com/milklife.id'],
        'https://www.tiktok.com/@milklife.athletics'   => ['TikTok', 'https://www.tiktok.com/@milklife_id'],
        'https://www.youtube.com/@milklifeathletics'   => ['YouTube', 'https://www.youtube.com/@milklife_id'],
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        try {
            $hasil = self::sesuaikan(db_connect());
        } catch (Throwable $e) {
            CLI::error(
                'Data tidak disesuaikan, dan tidak ada yang berubah. Pastikan MySQL menyala dan tabel sudah dibuat '
                . 'dengan "php spark migrate --all", lalu jalankan perintah ini lagi.',
            );
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Diubah:');

        foreach ($hasil['diubah'] === [] ? ['tidak ada; data sudah sesuai.'] : $hasil['diubah'] as $baris) {
            CLI::write('  - ' . $baris, $hasil['diubah'] === [] ? null : 'green');
        }

        CLI::write('Dilewati:');

        foreach ($hasil['dilewati'] === [] ? ['tidak ada.'] : $hasil['dilewati'] as $baris) {
            CLI::write('  - ' . $baris);
        }

        CLI::write('Data pendaftaran tidak diubah.');

        return EXIT_SUCCESS;
    }

    /**
     * Jalankan semua langkah dalam satu transaksi. Dengan `$ubah` false tidak
     * ada yang ditulis; `diubah` lalu berisi apa yang akan diubah (dipakai
     * `pembaruan:periksa`).
     *
     * @return array{diubah: list<string>, dilewati: list<string>}
     *
     * @throws Throwable Bila gagal; tidak ada yang berubah.
     */
    public static function sesuaikan(BaseConnection $db, bool $ubah = true): array
    {
        $hasil = ['diubah' => [], 'dilewati' => []];
        $waktu = Time::now()->format('Y-m-d H:i:s');

        if (! $ubah) {
            self::namaKelompokUmur($db, $waktu, $hasil, false);
            self::nomorWa($hasil, false);
            self::mediaSosial($db, $waktu, $hasil, false);

            return $hasil;
        }

        $db->transBegin();

        try {
            self::namaKelompokUmur($db, $waktu, $hasil);
            self::nomorWa($hasil);
            self::mediaSosial($db, $waktu, $hasil);

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();

            // Nilai pengaturan yang sempat ditulis di memori ikut dibuang.
            Services::resetSingle('settings');

            throw $e;
        }

        return $hasil;
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function namaKelompokUmur(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        if ($db->table('kelompok_umur')->where('nama', self::NAMA_KU_BARU)->countAllResults() > 0) {
            $hasil['dilewati'][] = 'Nama kelompok umur: ' . self::NAMA_KU_BARU . ' sudah ada.';

            return;
        }

        $lama = $db->table('kelompok_umur')->select('id')->where('nama', self::NAMA_KU_LAMA)->orderBy('id')->get()->getRowArray();

        if ($lama === null) {
            $hasil['dilewati'][] = 'Nama kelompok umur: tidak ada ' . self::NAMA_KU_LAMA . ' (nama sudah diubah admin).';

            return;
        }

        if ($ubah) {
            $db->table('kelompok_umur')->where('id', (int) $lama['id'])->update(['nama' => self::NAMA_KU_BARU, 'diubah_pada' => $waktu]);
        }

        $hasil['diubah'][] = 'Nama kelompok umur: ' . self::NAMA_KU_LAMA . ' menjadi ' . self::NAMA_KU_BARU . '.';
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function nomorWa(array &$hasil, bool $ubah = true): void
    {
        foreach (self::KUNCI_WA as $kunci => $nama) {
            if (NomorWa::normalkan(trim((string) setting($kunci))) !== self::WA_LAMA) {
                $hasil['dilewati'][] = $nama . ': bukan nomor contoh lama.';

                continue;
            }

            $baru = DataAwal::PENGATURAN_TAMBAHAN[$kunci];

            if ($ubah) {
                setting($kunci, $baru);
            }

            $hasil['diubah'][] = $nama . ': nomor contoh menjadi ' . NomorWa::tampilan($baru) . '.';
        }
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function mediaSosial(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        $lama = array_change_key_case(self::MEDIA_SOSIAL_LAMA);
        $ada  = false;

        foreach ($db->table('tautan')->select('id, nilai')->where('jenis', 'media_sosial')->orderBy('id')->get()->getResultArray() as $baris) {
            $kunci = strtolower(rtrim(trim((string) $baris['nilai']), '/'));

            if (! isset($lama[$kunci])) {
                continue;
            }

            [$label, $alamat] = $lama[$kunci];
            if ($ubah) {
                $db->table('tautan')->where('id', (int) $baris['id'])->update(['nilai' => $alamat, 'diubah_pada' => $waktu]);
            }

            $hasil['diubah'][] = 'Media sosial ' . $label . ': ' . $baris['nilai'] . ' menjadi ' . $alamat . '.';
            $ada               = true;
        }

        if (! $ada) {
            $hasil['dilewati'][] = 'Media sosial: tidak ada alamat bawaan lama.';
        }
    }
}
