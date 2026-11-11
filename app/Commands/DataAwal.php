<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * `php spark data:awal`
 *
 * Mengisi data awal dari flyer Seri 2 – 2026 (K25, rencana Tahap 2 "Data
 * awal", lampiran rancangan): data acara, tanggal pendaftaran, tanggal patokan
 * usia, lima kelompok umur beserta satu awalan nomor dada masing-masing (K33),
 * dan semua nomor lomba. Dijalankan sekali saat pemasangan, setelah
 * `php spark migrate --all`.
 *
 * Batas usia, jenjang, biaya individu, serta batas anggota dan jumlah
 * putra/putri nomor beregu dikosongkan untuk diisi admin (K12, K23). Awalan
 * nomor dada bisa diubah admin. Nomor individu dibuat dua kali, putra dan putri.
 *
 * Perintah menolak berjalan bila kelompok umur, awalan nomor dada, atau nomor
 * lomba sudah ada, atau pengaturan acara atau tanggal pendaftaran sudah
 * terisi, supaya isian admin tidak tertimpa. Semua ditulis dalam satu transaksi: tersimpan semua atau
 * tidak sama sekali.
 *
 * Data ini berasal dari flyer, bukan data siswa, jadi boleh ada di repo.
 */
class DataAwal extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'data:awal';
    protected $description = 'Mengisi data acara, jadwal pendaftaran, kelompok umur, dan nomor lomba dari flyer Seri 2. Sekali jalan saat pemasangan.';
    protected $usage       = 'data:awal';

    /**
     * Pengaturan yang diisi: kunci `setting()` => nilai.
     */
    public const PENGATURAN = [
        'Acara.nama'                       => 'MilkLife Athletics Challenge',
        'Acara.seri'                       => 2,
        'Acara.tahun'                      => 2026,
        'Acara.tempat'                     => 'Supersoccer Arena, Kudus',
        'Acara.tanggal_mulai'              => '2026-12-09',
        'Acara.tanggal_selesai'            => '2026-12-12',
        'Pendaftaran.mulai'                => '2026-10-01 00:00:00',
        'Pendaftaran.akhir'                => '2026-11-30 23:59:00',
        'Pendaftaran.tanggal_patokan_usia' => '2026-11-30',
    ];

    /**
     * Kunci pengaturan yang, bila sudah terisi, membuat perintah menolak berjalan.
     */
    private const PENGATURAN_PENJAGA = [
        'Acara.nama', 'Acara.seri', 'Acara.tahun', 'Acara.tempat', 'Acara.tanggal_mulai', 'Acara.tanggal_selesai',
        'Pendaftaran.mulai', 'Pendaftaran.akhir', 'Pendaftaran.tanggal_patokan_usia',
    ];

    /**
     * Kelompok umur dan nomor lombanya, berurutan seperti di flyer.
     *
     * - `awalan`: awalan nomor dada (satu angka), `urutan` 1 dan `nomor_terakhir` 0 (K33).
 * - `individu`: nama nomor; masing-masing dibuat putra dan putri.
     * - `beregu`: [nama, kategori (`putra_putri` = dibuat putra dan putri, atau `mix`), jumlah inti, biaya beregu].
     *
     * KU 8 diberi nama "KU 8 (Seneng Playon)" seperti di flyer, supaya sekolah
     * mengenalinya; admin bisa mengubahnya.
     *
     * @var list<array{nama: string, awalan: string, individu: list<string>, beregu: list<array{0: string, 1: string, 2: int, 3: int}>}>
     */
    public const KELOMPOK_UMUR = [
        [
            'nama'     => 'KU 8 (Seneng Playon)',
            'awalan'   => '1',
            'individu' => ['Lari 40 m'],
            'beregu'   => [],
        ],
        [
            'nama'     => 'KU 10',
            'awalan'   => '2',
            'individu' => ['Lari 60 m', 'Frog Jump', 'Turbo Throw'],
            'beregu'   => [
                ["Kanga's Escape", 'putra_putri', 4, 100000],
                ['Formula 1', 'putra_putri', 4, 100000],
                ['Estafet 8 × 50 m', 'mix', 8, 150000],
            ],
        ],
        [
            'nama'     => 'KU 12',
            'awalan'   => '3',
            'individu' => ['Lompat Jauh', 'Tolak Peluru', 'Lari 60 m', 'Lari 1000 m', 'Trilomba'],
            'beregu'   => [
                ['Estafet 8 × 50 m', 'mix', 8, 150000],
            ],
        ],
        [
            'nama'     => 'KU 15',
            'awalan'   => '4',
            'individu' => [
                'Jalan Cepat 2000 m', 'Lari 80 m', 'Lari 400 m', 'Lari 800 m', 'Lari 1500 m',
                'Lompat Jauh', 'Lompat Tinggi', 'Lempar Lembing', 'Lempar Cakram', 'Tolak Peluru',
            ],
            'beregu' => [
                ['Estafet 5 × 80 m', 'putra_putri', 5, 100000],
            ],
        ],
        [
            'nama'     => 'KU 18',
            'awalan'   => '5',
            'individu' => [
                'Jalan Cepat 3000 m', 'Lari 100 m', 'Lari 400 m', 'Lari 800 m', 'Lari 1500 m',
                'Lompat Jauh', 'Lompat Tinggi', 'Lempar Lembing', 'Lempar Cakram', 'Tolak Peluru',
            ],
            'beregu' => [
                ['Estafet 4 × 100 m', 'putra_putri', 4, 100000],
                ['Estafet 4 × 400 m', 'mix', 4, 100000],
            ],
        ],
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        $db = db_connect();

        try {
            $alasan = self::alasanMenolak($db);
        } catch (Throwable $e) {
            CLI::error(
                'Basis data belum siap. Pastikan MySQL menyala, pengaturan basis data di .env benar, '
                . 'dan tabel sudah dibuat dengan "php spark migrate --all", lalu jalankan perintah ini lagi.',
            );
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if ($alasan !== null) {
            CLI::error('Data awal tidak diisi: ' . $alasan);
            CLI::write('Data yang sudah ada tidak diubah. Periksa dan ubah datanya lewat menu Pengaturan di panel admin.');

            return EXIT_ERROR;
        }

        try {
            $hasil = self::isi($db);
        } catch (Throwable $e) {
            CLI::error('Data awal gagal disimpan, dan tidak ada yang tersimpan. Perbaiki sebabnya lalu jalankan perintah ini lagi.');
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        $this->tampilkanRingkasan($hasil);

        return EXIT_SUCCESS;
    }

    /**
     * Alasan perintah tidak boleh berjalan, atau `null` bila boleh.
     */
    public static function alasanMenolak(BaseConnection $db): ?string
    {
        $jumlahKelompokUmur = $db->table('kelompok_umur')->countAllResults();

        if ($jumlahKelompokUmur > 0) {
            return "sudah ada {$jumlahKelompokUmur} kelompok umur di basis data.";
        }

        $jumlahAwalan = $db->table('awalan_nomor_dada')->countAllResults();

        if ($jumlahAwalan > 0) {
            return "sudah ada {$jumlahAwalan} awalan nomor dada di basis data.";
        }

        $jumlahNomorLomba = $db->table('nomor_lomba')->countAllResults();

        if ($jumlahNomorLomba > 0) {
            return "sudah ada {$jumlahNomorLomba} nomor lomba di basis data.";
        }

        foreach (self::PENGATURAN_PENJAGA as $kunci) {
            $nilai = setting($kunci);

            if ($nilai !== null && $nilai !== '') {
                return "pengaturan {$kunci} sudah terisi.";
            }
        }

        return null;
    }

    /**
     * Isi semua data awal dalam satu transaksi.
     *
     * @return array{kelompok_umur: int, nomor_individu: int, nomor_beregu: int, per_kelompok_umur: array<string, array{awalan: string, individu: int, beregu: int}>}
     *
     * @throws Throwable Bila gagal; tidak ada yang tersimpan.
     */
    public static function isi(BaseConnection $db): array
    {
        $waktu = Time::now()->format('Y-m-d H:i:s');
        $hasil = ['kelompok_umur' => 0, 'nomor_individu' => 0, 'nomor_beregu' => 0, 'per_kelompok_umur' => []];

        $db->transBegin();

        try {
            foreach (self::KELOMPOK_UMUR as $urutanKu => $ku) {
                if (! $db->table('kelompok_umur')->insert([
                    'nama'        => $ku['nama'],
                    'urutan'      => $urutanKu + 1,
                    'aktif'       => 1,
                    'dibuat_pada' => $waktu,
                    'diubah_pada' => $waktu,
                ])) {
                    throw new RuntimeException('Kelompok umur ' . $ku['nama'] . ' gagal disimpan.');
                }

                $idKu = (int) $db->insertID();

                if (! $db->table('awalan_nomor_dada')->insert([
                    'kelompok_umur_id' => $idKu,
                    'awalan'           => $ku['awalan'],
                    'urutan'           => 1,
                    'nomor_terakhir'   => 0,
                    'dibuat_pada'      => $waktu,
                    'diubah_pada'      => $waktu,
                ])) {
                    throw new RuntimeException('Awalan nomor dada ' . $ku['nama'] . ' gagal disimpan.');
                }

                $baris    = self::barisNomorLomba($idKu, $ku, $waktu);
                $individu = count(array_filter($baris, static fn (array $b): bool => $b['jenis'] === 'individu'));

                if ($baris !== [] && $db->table('nomor_lomba')->insertBatch($baris) !== count($baris)) {
                    throw new RuntimeException('Nomor lomba ' . $ku['nama'] . ' gagal disimpan.');
                }

                $hasil['kelompok_umur']++;
                $hasil['nomor_individu'] += $individu;
                $hasil['nomor_beregu'] += count($baris) - $individu;
                $hasil['per_kelompok_umur'][$ku['nama']] = ['awalan' => $ku['awalan'], 'individu' => $individu, 'beregu' => count($baris) - $individu];
            }

            foreach (self::PENGATURAN as $kunci => $nilai) {
                setting($kunci, $nilai);
            }

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
     * Baris `nomor_lomba` untuk satu kelompok umur: individu (putra lalu putri
     * per nomor), lalu beregu, dengan `urutan` berjalan dari 1.
     *
     * @param array{nama: string, awalan: string, individu: list<string>, beregu: list<array{0: string, 1: string, 2: int, 3: int}>} $ku
     *
     * @return list<array<string, mixed>>
     */
    private static function barisNomorLomba(int $idKu, array $ku, string $waktu): array
    {
        $baris  = [];
        $urutan = 0;
        $dasar  = [
            'kelompok_umur_id' => $idKu,
            'jumlah_inti'      => null,
            'batas_anggota'    => null,
            'jumlah_putra'     => null,
            'jumlah_putri'     => null,
            'biaya_beregu'     => null,
            'aktif'            => 1,
            'dibuat_pada'      => $waktu,
            'diubah_pada'      => $waktu,
        ];

        foreach ($ku['individu'] as $nama) {
            foreach (['putra', 'putri'] as $kategori) {
                $baris[] = ['nama' => $nama, 'jenis' => 'individu', 'kategori' => $kategori, 'urutan' => ++$urutan] + $dasar;
            }
        }

        foreach ($ku['beregu'] as [$nama, $kategori, $inti, $biaya]) {
            foreach ($kategori === 'mix' ? ['mix'] : ['putra', 'putri'] as $k) {
                $baris[] = [
                    'nama'         => $nama,
                    'jenis'        => 'beregu',
                    'kategori'     => $k,
                    'urutan'       => ++$urutan,
                    'jumlah_inti'  => $inti,
                    'biaya_beregu' => $biaya,
                ] + $dasar;
            }
        }

        return $baris;
    }

    /**
     * @param array{kelompok_umur: int, nomor_individu: int, nomor_beregu: int, per_kelompok_umur: array<string, array{awalan: string, individu: int, beregu: int}>} $hasil
     */
    private function tampilkanRingkasan(array $hasil): void
    {
        helper('aplikasi');

        CLI::write('Data awal tersimpan.', 'green');
        CLI::newLine();
        CLI::write('Acara        : ' . self::PENGATURAN['Acara.nama'] . ' Seri ' . self::PENGATURAN['Acara.seri'] . ' – ' . self::PENGATURAN['Acara.tahun']);
        CLI::write('Tempat       : ' . self::PENGATURAN['Acara.tempat'] . ', ' . rentang_tanggal_id(self::PENGATURAN['Acara.tanggal_mulai'], self::PENGATURAN['Acara.tanggal_selesai']));
        CLI::write('Pendaftaran  : ' . tanggal_id(self::PENGATURAN['Pendaftaran.mulai'], true) . ' sampai ' . tanggal_id(self::PENGATURAN['Pendaftaran.akhir'], true));
        CLI::write('Patokan usia : ' . tanggal_id(self::PENGATURAN['Pendaftaran.tanggal_patokan_usia']));
        CLI::newLine();
        CLI::write('Kelompok umur: ' . $hasil['kelompok_umur']);

        foreach ($hasil['per_kelompok_umur'] as $nama => $jumlah) {
            CLI::write(sprintf('  %-21s: awalan nomor dada %s, %2d nomor individu, %d nomor beregu', $nama, $jumlah['awalan'], $jumlah['individu'], $jumlah['beregu']));
        }

        CLI::write('Nomor lomba  : ' . ($hasil['nomor_individu'] + $hasil['nomor_beregu']) . ' (' . $hasil['nomor_individu'] . ' individu, ' . $hasil['nomor_beregu'] . ' beregu)');
        CLI::newLine();
        CLI::write('Langkah berikutnya: masuk ke panel admin, buka menu Pengaturan, lalu isi batas usia, jenjang sekolah,');
        CLI::write('dan biaya individu tiap kelompok umur, serta batas anggota nomor beregu.');
    }
}
