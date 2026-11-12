<?php

namespace App\Commands;

use App\Libraries\Unggahan;
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
 * Mengisi data awal yang lengkap (K25, diperluas K73): data acara, jadwal
 * pendaftaran, lima kelompok umur beserta batas tahun lahir (K36), jenjang,
 * biaya individu, dan satu awalan nomor dada masing-masing (K33), semua nomor
 * lomba beserta aturan timnya, rekening pembayaran, isi halaman depan, jadwal,
 * narahubung, media sosial, dan berkas unduhan. Dijalankan sekali saat
 * pemasangan, setelah `php spark migrate --all`.
 *
 * Isinya dari flyer Seri 2 – 2026 dan permintaan pemilik. Rekening, nomor WA,
 * tautan Zoom, media sosial, dan sebagian berkas unduhan masih **contoh**
 * (berkas kosong) yang harus diganti admin dengan data sebenarnya.
 * Semua bisa diubah lewat panel admin. Nomor individu dibuat dua kali, putra
 * dan putri.
 *
 * Perintah menolak berjalan bila kelompok umur, awalan nomor dada, atau nomor
 * lomba sudah ada, atau pengaturan acara atau tanggal pendaftaran sudah
 * terisi, supaya isian admin tidak tertimpa. Pengaturan lain, jadwal,
 * kontak, gambar, dan unduhan yang sudah diisi admin dilewati, tidak ditimpa.
 * Isian basis data ditulis dalam satu transaksi: tersimpan semua atau tidak
 * sama sekali.
 *
 * Data ini bukan data siswa, jadi boleh ada di repo.
 */
class DataAwal extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'data:awal';
    protected $description = 'Mengisi data awal lengkap Seri 2: acara, pendaftaran, kelompok umur, nomor lomba, pembayaran, halaman depan, jadwal, kontak, dan unduhan. Sekali jalan saat pemasangan.';
    protected $usage       = 'data:awal';

    /**
     * Pengaturan yang diisi: kunci `setting()` => nilai.
     */
    public const PENGATURAN = [
        'Acara.nama'            => 'MilkLife Athletics Challenge',
        'Acara.seri'            => 2,
        'Acara.tahun'           => 2026,
        'Acara.tempat'          => 'Supersoccer Arena, Kudus',
        'Acara.tanggal_mulai'   => '2026-12-09',
        'Acara.tanggal_selesai' => '2026-12-12',
        'Pendaftaran.mulai'     => '2026-10-01 00:00:00',
        'Pendaftaran.akhir'     => '2026-11-30 23:59:00',
    ];

    /**
     * Pengaturan yang diisi hanya bila masih kosong; isian admin tidak ditimpa
     * dan tidak membuat perintah menolak. Nomor WA dalam bentuk baku `628…`.
     * Rekening dan nomor WA masih contoh (K73).
     */
    public const PENGATURAN_TAMBAHAN = [
        'Pembayaran.bank'             => 'BANK BPD JATENG',
        'Pembayaran.nomor_rekening'   => '0123456789',
        'Pembayaran.pemilik_rekening' => 'Nama Panitia',
        'Pembayaran.nomor_wa'         => '628123456789',
        'HalamanDepan.judul'          => 'MilkLife Athletics Challenge!',
        'HalamanDepan.teks'           => 'A youth sports event organized by the Bakti Olahraga Djarum Foundation and MilkLife to discover and train young track and field athletes in Indonesia.',
        'HalamanDepan.footer'         => 'Bakti Olahraga Djarum Foundation dan MilkLife',
        'HalamanDepan.nomor_wa_tanya' => '628123456789',
        'HalamanDepan.pesan_tanya'    => 'Tanya admin',
        'HalamanDepan.pesan_bagikan'  => 'Bagikan ke WA',
    ];

    /**
     * Batas anggota nomor beregu = jumlah inti × angka ini (K73).
     */
    public const KALI_BATAS_ANGGOTA = 2;

    /**
     * Contoh jadwal (diisi bila tabel `jadwal` masih kosong):
     * [judul, waktu `Y-m-d H:i:s` WIB, tempat, tautan, keterangan].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    public const JADWAL = [
        ['Zoom Meeting Sosialisasi', '2026-10-05 19:00:00', 'Zoom Meeting', 'https://zoom.us/j/1234567890', "Penjelasan aturan lomba dan cara mendaftar untuk guru dan official sekolah.\nTautan Zoom di atas masih contoh."],
        ['Batas akhir pendaftaran dan pembayaran', '2026-11-30 23:59:00', 'Web pendaftaran', '', 'Pendaftaran ditutup otomatis. Pastikan bukti transfer sudah dikirim lewat WA sebelum batas ini.'],
        ['Technical Meeting', '2026-12-05 09:00:00', 'Supersoccer Arena, Kudus', '', "Pembahasan teknis lomba bersama official sekolah.\nWajib dihadiri minimal satu penanggung jawab tiap sekolah."],
        ['Pengambilan nomor dada', '2026-12-08 13:00:00', 'Supersoccer Arena, Kudus', '', 'Bawa bukti pendaftaran (PDF atau cetak) dengan status Lunas.'],
        ['Pelaksanaan lomba', '2026-12-09 07:00:00', 'Supersoccer Arena, Kudus', '', 'Lomba berlangsung 9–12 Desember 2026. Jadwal per nomor lomba diumumkan saat technical meeting.'],
    ];

    /**
     * Narahubung (dari flyer) dan media sosial (contoh), diisi bila tabel
     * `tautan` masih kosong: [jenis, label, nilai].
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const TAUTAN = [
        ['narahubung', 'Just', '085601137077'],
        ['narahubung', 'Iqbal', '088215277227'],
        ['media_sosial', 'Instagram', 'https://www.instagram.com/milklife.athletics'],
        ['media_sosial', 'TikTok', 'https://www.tiktok.com/@milklife.athletics'],
        ['media_sosial', 'YouTube', 'https://www.youtube.com/@milklifeathletics'],
    ];

    /**
     * Berkas unduhan, diisi bila tabel `unduhan` masih kosong:
     * [judul, keterangan, berkas relatif terhadap folder proyek].
     * Berkas di `data/unduhan/` masih contoh kosong (K73).
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const UNDUHAN = [
        ['Flyer Seri 2 – 2026', 'Kategori, nomor lomba, jadwal, dan biaya pendaftaran.', 'sumber/flyer/flyer-seri2-2026.jpg'],
        ['Banner Seri 2 – 2026', 'Gambar untuk dibagikan di media sosial sekolah.', 'sumber/flyer/banner-seri2-2026.jpg'],
        ['Petunjuk teknis lomba', 'Aturan lomba tiap kelompok umur dan nomor lomba.', 'data/unduhan/petunjuk-teknis.pdf'],
        ['Surat keterangan sekolah', 'Templat surat keterangan siswa aktif dari kepala sekolah.', 'data/unduhan/surat-keterangan-sekolah.docx'],
        ['Daftar atlet sekolah', 'Templat untuk menyiapkan data atlet sebelum mengisi formulir pendaftaran.', 'data/unduhan/daftar-atlet.xlsx'],
        ['Denah lokasi', 'Denah Supersoccer Arena, Kudus.', 'data/unduhan/denah-lokasi.png'],
    ];

    /**
     * Kunci pengaturan yang, bila sudah terisi, membuat perintah menolak berjalan.
     */
    private const PENGATURAN_PENJAGA = [
        'Acara.nama', 'Acara.seri', 'Acara.tahun', 'Acara.tempat', 'Acara.tanggal_mulai', 'Acara.tanggal_selesai',
        'Pendaftaran.mulai', 'Pendaftaran.akhir',
    ];

    /**
     * Kelompok umur dan nomor lombanya, berurutan seperti di flyer.
     *
     * - `lahir`: [tahun lahir paling awal, tahun lahir paling akhir] (K36);
     *   `null` = tidak dibatasi di sisi itu (KU 8: lahir 2019 ke atas).
     * - `jenjang`: kode jenjang yang boleh ikut (`App\Libraries\Jenjang`).
     * - `biaya`: biaya individu, rupiah per atlet per nomor (K73).
     * - `awalan`: awalan nomor dada (satu angka), `urutan` 1 dan `nomor_terakhir` 0 (K33).
     * - `individu`: nama nomor; masing-masing dibuat putra dan putri.
     * - `beregu`: [nama, kategori (`putra_putri` = dibuat putra dan putri, atau `mix`), jumlah inti, biaya beregu].
     *   Batas anggota = jumlah inti × `KALI_BATAS_ANGGOTA`; nomor mix minimal
     *   separuh inti putra dan sisanya putri.
     *
     * KU 8 diberi nama "KU 8 (Seneng Playon)" seperti di flyer, supaya sekolah
     * mengenalinya; admin bisa mengubahnya.
     *
     * @var list<array{nama: string, lahir: array{0: int|null, 1: int|null}, jenjang: list<string>, biaya: int, awalan: string, individu: list<string>, beregu: list<array{0: string, 1: string, 2: int, 3: int}>}>
     */
    public const KELOMPOK_UMUR = [
        [
            'nama'     => 'KU 8 (Seneng Playon)',
            'lahir'    => [2019, null],
            'jenjang'  => ['A'],
            'biaya'    => 25000,
            'awalan'   => '1',
            'individu' => ['Lari 40 m'],
            'beregu'   => [],
        ],
        [
            'nama'     => 'KU 10',
            'lahir'    => [2016, 2018],
            'jenjang'  => ['A'],
            'biaya'    => 25000,
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
            'lahir'    => [2015, 2015],
            'jenjang'  => ['A'],
            'biaya'    => 25000,
            'awalan'   => '3',
            'individu' => ['Lompat Jauh', 'Tolak Peluru', 'Lari 60 m', 'Lari 1000 m', 'Trilomba'],
            'beregu'   => [
                ['Estafet 8 × 50 m', 'mix', 8, 150000],
            ],
        ],
        [
            'nama'     => 'KU 15',
            'lahir'    => [2011, 2014],
            'jenjang'  => ['B'],
            'biaya'    => 35000,
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
            'lahir'    => [2008, 2010],
            'jenjang'  => ['C'],
            'biaya'    => 50000,
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

        foreach (self::pasangGambarBawaan() as $kunci => $terpasang) {
            $nama    = self::GAMBAR_BAWAAN[$kunci][1];
            $halaman = str_starts_with($kunci, 'Pembayaran.') ? 'Pengaturan > Pembayaran' : 'Pengaturan > Halaman Depan';

            if ($terpasang) {
                CLI::write($nama . ' bawaan dipasang (bisa diganti di ' . $halaman . ').');
            } else {
                CLI::error($nama . ' bawaan gagal disalin. Pastikan folder public/unggahan boleh ditulisi, lalu unggah gambarnya lewat ' . $halaman . '.');
            }
        }

        $unduhan = self::pasangUnduhanBawaan($db);

        if ($unduhan['tersimpan'] > 0) {
            CLI::write($unduhan['tersimpan'] . ' berkas unduhan dipasang (bisa diganti di Pengaturan > Unduhan).');
        }

        if ($unduhan['gagal'] !== []) {
            CLI::error('Berkas unduhan gagal disalin: ' . implode(', ', $unduhan['gagal']) . '. Pastikan folder public/unggahan boleh ditulisi, lalu unggah berkasnya lewat Pengaturan > Unduhan.');
        }

        return EXIT_SUCCESS;
    }

    /**
     * Gambar bawaan dari pemilik (K47, K73), disalin ke folder unggahan
     * supaya admin tetap bisa mengganti atau menghapusnya di panel. Folder
     * unggahannya sama dengan folder berkas di `data/`.
     *
     * @var array<string, array{0: string, 1: string}> kunci `setting()` => [berkas di `data/`, nama untuk pesan]
     */
    public const GAMBAR_BAWAAN = [
        'HalamanDepan.gambar_kepala' => ['halaman-depan/gambar-kepala.webp', 'Gambar kepala'],
        'HalamanDepan.logo_persegi'  => ['halaman-depan/logo-persegi.webp', 'Logo persegi'],
        'Pembayaran.logo_bank'       => ['pembayaran/logo-bank.png', 'Logo bank'],
    ];

    /**
     * Salin gambar bawaan ke `unggahan/halaman-depan/` dan isi pengaturannya.
     * Pengaturan yang sudah terisi (admin sudah mengunggah) tidak ditimpa.
     *
     * @return array<string, bool> kunci => berhasil; kunci yang dilewati tidak ada
     */
    public static function pasangGambarBawaan(): array
    {
        $hasil = [];

        foreach (self::GAMBAR_BAWAAN as $kunci => [$berkas]) {
            if (trim((string) setting($kunci)) !== '') {
                continue;
            }

            $jalur = self::salinKeUnggahan(ROOTPATH . 'data/' . $berkas, dirname($berkas));

            if ($jalur !== null) {
                setting($kunci, $jalur);
            }

            $hasil[$kunci] = $jalur !== null;
        }

        return $hasil;
    }

    /**
     * Salin berkas `UNDUHAN` ke `unggahan/unduhan/` dan catat di tabel
     * `unduhan`, hanya bila tabelnya masih kosong. Berkas yang gagal disalin
     * dilewati.
     *
     * @return array{tersimpan: int, gagal: list<string>} `gagal` berisi judul berkas
     */
    public static function pasangUnduhanBawaan(BaseConnection $db): array
    {
        $hasil = ['tersimpan' => 0, 'gagal' => []];

        if ($db->table('unduhan')->countAllResults() > 0) {
            return $hasil;
        }

        $waktu = Time::now()->format('Y-m-d H:i:s');

        foreach (self::UNDUHAN as $urutan => [$judul, $keterangan, $berkas]) {
            $jalur = self::salinKeUnggahan(ROOTPATH . $berkas, 'unduhan');

            if ($jalur === null || ! $db->table('unduhan')->insert([
                'judul' => $judul, 'keterangan' => $keterangan, 'berkas' => $jalur, 'urutan' => $urutan + 1, 'tampil' => 1,
                'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
            ])) {
                $hasil['gagal'][] = $judul;

                continue;
            }

            $hasil['tersimpan']++;
        }

        return $hasil;
    }

    /**
     * Salin satu berkas ke `unggahan/<folder>/` dengan nama acak.
     *
     * @return string|null Jalur relatif (`unggahan/<folder>/….ext`), atau `null` bila gagal.
     */
    private static function salinKeUnggahan(string $sumber, string $folder): ?string
    {
        $tujuan = Unggahan::folderDasar() . $folder;
        $nama   = bin2hex(random_bytes(16)) . '.' . strtolower(pathinfo($sumber, PATHINFO_EXTENSION));

        if (! is_file($sumber) || (! is_dir($tujuan) && ! @mkdir($tujuan, 0755, true)) || ! @copy($sumber, $tujuan . DIRECTORY_SEPARATOR . $nama)) {
            return null;
        }

        @chmod($tujuan . DIRECTORY_SEPARATOR . $nama, 0644);

        return 'unggahan/' . $folder . '/' . $nama;
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
     * @return array{kelompok_umur: int, nomor_individu: int, nomor_beregu: int, per_kelompok_umur: array<string, array{lahir: string, awalan: string, individu: int, beregu: int}>}
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
                    'nama'              => $ku['nama'],
                    'tahun_lahir_awal'  => $ku['lahir'][0],
                    'tahun_lahir_akhir' => $ku['lahir'][1],
                    'biaya_individu'    => $ku['biaya'],
                    'urutan'            => $urutanKu + 1,
                    'aktif'             => 1,
                    'dibuat_pada'       => $waktu,
                    'diubah_pada'       => $waktu,
                ])) {
                    throw new RuntimeException('Kelompok umur ' . $ku['nama'] . ' gagal disimpan.');
                }

                $idKu = (int) $db->insertID();

                foreach ($ku['jenjang'] as $jenjang) {
                    if (! $db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $idKu, 'jenjang' => $jenjang, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu])) {
                        throw new RuntimeException('Jenjang ' . $ku['nama'] . ' gagal disimpan.');
                    }
                }

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
                $hasil['per_kelompok_umur'][$ku['nama']] = ['lahir' => self::teksLahir($ku['lahir']), 'awalan' => $ku['awalan'], 'individu' => $individu, 'beregu' => count($baris) - $individu];
            }

            foreach (self::PENGATURAN as $kunci => $nilai) {
                setting($kunci, $nilai);
            }

            foreach (self::PENGATURAN_TAMBAHAN as $kunci => $nilai) {
                if (trim((string) setting($kunci)) === '') {
                    setting($kunci, $nilai);
                }
            }

            if ($db->table('jadwal')->countAllResults() === 0) {
                foreach (self::JADWAL as $urutan => [$judul, $waktuJadwal, $tempat, $tautan, $keterangan]) {
                    if (! $db->table('jadwal')->insert([
                        'judul'       => $judul,
                        'keterangan'  => $keterangan,
                        'waktu'       => $waktuJadwal,
                        'tempat'      => $tempat,
                        'tautan'      => $tautan === '' ? null : $tautan,
                        'urutan'      => $urutan + 1,
                        'tampil'      => 1,
                        'dibuat_pada' => $waktu,
                        'diubah_pada' => $waktu,
                    ])) {
                        throw new RuntimeException('Jadwal ' . $judul . ' gagal disimpan.');
                    }
                }
            }

            if ($db->table('tautan')->countAllResults() === 0) {
                foreach (self::TAUTAN as $urutan => [$jenis, $label, $nilai]) {
                    if (! $db->table('tautan')->insert(['jenis' => $jenis, 'label' => $label, 'nilai' => $nilai, 'urutan' => $urutan + 1, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu])) {
                        throw new RuntimeException('Kontak ' . $label . ' gagal disimpan.');
                    }
                }
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
     * Batas tahun lahir untuk ringkasan di layar: `lahir 2016–2018`,
     * `lahir 2015`, `lahir 2019 ke atas`.
     *
     * @param array{0: int|null, 1: int|null} $lahir
     */
    private static function teksLahir(array $lahir): string
    {
        [$awal, $akhir] = $lahir;

        return match (true) {
            $awal !== null && $akhir === null => "lahir {$awal} ke atas",
            $awal === null && $akhir !== null => "lahir {$akhir} ke bawah",
            $awal === $akhir                  => "lahir {$awal}",
            default                           => "lahir {$awal}–{$akhir}",
        };
    }

    /**
     * Baris `nomor_lomba` untuk satu kelompok umur: individu (putra lalu putri
     * per nomor), lalu beregu, dengan `urutan` berjalan dari 1.
     *
     * @param array{nama: string, lahir: array{0: int|null, 1: int|null}, jenjang: list<string>, biaya: int, awalan: string, individu: list<string>, beregu: list<array{0: string, 1: string, 2: int, 3: int}>} $ku
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
                    'jumlah_inti'   => $inti,
                    'batas_anggota' => $inti * self::KALI_BATAS_ANGGOTA,
                    'jumlah_putra'  => $k === 'mix' ? intdiv($inti, 2) : null,
                    'jumlah_putri'  => $k === 'mix' ? $inti - intdiv($inti, 2) : null,
                    'biaya_beregu'  => $biaya,
                ] + $dasar;
            }
        }

        return $baris;
    }

    /**
     * @param array{kelompok_umur: int, nomor_individu: int, nomor_beregu: int, per_kelompok_umur: array<string, array{lahir: string, awalan: string, individu: int, beregu: int}>} $hasil
     */
    private function tampilkanRingkasan(array $hasil): void
    {
        helper('aplikasi');

        CLI::write('Data awal tersimpan.', 'green');
        CLI::newLine();
        CLI::write('Acara        : ' . self::PENGATURAN['Acara.nama'] . ' Seri ' . self::PENGATURAN['Acara.seri'] . ' – ' . self::PENGATURAN['Acara.tahun']);
        CLI::write('Tempat       : ' . self::PENGATURAN['Acara.tempat'] . ', ' . rentang_tanggal_id(self::PENGATURAN['Acara.tanggal_mulai'], self::PENGATURAN['Acara.tanggal_selesai']));
        CLI::write('Pendaftaran  : ' . tanggal_id(self::PENGATURAN['Pendaftaran.mulai'], true) . ' sampai ' . tanggal_id(self::PENGATURAN['Pendaftaran.akhir'], true));
        CLI::newLine();
        CLI::write('Kelompok umur: ' . $hasil['kelompok_umur']);

        foreach ($hasil['per_kelompok_umur'] as $nama => $jumlah) {
            // Rata kiri menurut jumlah huruf, bukan bita (tanda "–" tiga bita).
            $lahir = $jumlah['lahir'] . ',' . str_repeat(' ', max(1, 20 - mb_strlen($jumlah['lahir'])));

            CLI::write(sprintf('  %-21s: %sawalan nomor dada %s, %2d nomor individu, %d nomor beregu', $nama, $lahir, $jumlah['awalan'], $jumlah['individu'], $jumlah['beregu']));
        }

        CLI::write('Nomor lomba  : ' . ($hasil['nomor_individu'] + $hasil['nomor_beregu']) . ' (' . $hasil['nomor_individu'] . ' individu, ' . $hasil['nomor_beregu'] . ' beregu)');
        CLI::newLine();
        CLI::write('Langkah berikutnya: masuk ke panel admin, buka menu Pengaturan, lalu ganti data contoh dengan data sebenarnya:');
        CLI::write('nomor rekening, nomor WA pembayaran dan tanya admin, tautan Zoom, media sosial, dan berkas unduhan yang masih kosong.');
    }
}
