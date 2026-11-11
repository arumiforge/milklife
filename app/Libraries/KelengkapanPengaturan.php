<?php

namespace App\Libraries;

use DateTimeImmutable;

/**
 * Daftar centang kelengkapan pengaturan (rancangan 6.1, revisi 3).
 *
 * Kelas murni: tidak membaca basis data, pengaturan, atau permintaan HTTP.
 * Semua data diterima sebagai satu potret (larik) yang dikumpulkan
 * `App\Libraries\DataKelengkapan`, sehingga aturannya bisa diuji dengan larik
 * biasa.
 *
 * Pengaturan dianggap lengkap bila kelima butir ini lengkap. Kunci butir sama
 * dengan `kunci` halaman di `BerandaPengaturanController::daftarHalaman()`,
 * dan `alamat`-nya alamat halaman yang melengkapinya (untuk `site_url()`):
 *
 * | Kunci           | Butir                                                                                  |
 * |-----------------|----------------------------------------------------------------------------------------|
 * | `acara`         | Nama, seri, tahun, tempat, tanggal mulai dan selesai acara                             |
 * | `pendaftaran`   | Tanggal mulai dan tanggal akhir pendaftaran                                            |
 * | `kelompok-umur` | Minimal satu kelompok umur aktif; tiap kelompok umur aktif punya batas tahun lahir     |
 * |                 | (minimal salah satu), minimal satu jenjang, biaya individu, dan minimal satu awalan    |
 * |                 | nomor dada                                                                             |
 * | `nomor-lomba`   | Minimal satu nomor lomba aktif pada kelompok umur aktif; tiap nomor beregu aktif punya |
 * |                 | jumlah inti, batas anggota, dan biaya beregu (mix juga jumlah putra dan putri)          |
 * | `pembayaran`    | Nama bank, nomor rekening, nama pemilik rekening, dan nomor WA pembayaran              |
 *
 * Tanggal patokan usia bukan lagi butir kelengkapan (K37).
 *
 * ## Potret
 *
 *     $potret = [
 *         'acara' => [
 *             'nama' => 'MilkLife Athletics Challenge', 'seri' => 2, 'tahun' => 2026,
 *             'tempat' => 'Supersoccer Arena, Kudus',
 *             'tanggal_mulai' => '2026-12-09', 'tanggal_selesai' => '2026-12-12',
 *         ],
 *         'pendaftaran' => ['mulai' => '2026-10-01 00:00:00', 'akhir' => '2026-11-30 23:59:00'],
 *         'pembayaran'  => ['bank' => 'BCA', 'nomor_rekening' => '1234567890', 'pemilik_rekening' => 'Panitia', 'nomor_wa' => '6281234567890'],
 *         'kelompok_umur' => [
 *             // Berurutan seperti tampil. `jenjang`: kode jenjang; `awalan`: awalan nomor dada.
 *             ['id' => 1, 'nama' => 'KU 10', 'aktif' => 1, 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018,
 *              'biaya_individu' => 75000, 'jenjang' => ['A'], 'awalan' => ['2']],
 *         ],
 *         'nomor_lomba' => [
 *             ['id' => 1, 'kelompok_umur_id' => 1, 'nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putra', 'aktif' => 1,
 *              'jumlah_inti' => null, 'batas_anggota' => null, 'jumlah_putra' => null, 'jumlah_putri' => null, 'biaya_beregu' => null],
 *         ],
 *     ];
 *
 * Bagian yang tidak ada dianggap kosong. Nilai boleh berupa teks seperti yang
 * dikembalikan basis data (`'1'`, `'75000'`).
 *
 * ## Pemakaian
 *
 *     $kelengkapan = new KelengkapanPengaturan($potret);
 *     // Biasanya: $kelengkapan = (new DataKelengkapan())->kelengkapan();
 *
 *     $kelengkapan->lengkap();                    // true bila semua butir lengkap
 *     $kelengkapan->butir();                      // semua butir, berurutan seperti tabel di atas
 *     $kelengkapan->belumLengkap();               // hanya butir yang belum lengkap
 *     $kelengkapan->untukHalaman('kelompok-umur'); // satu butir menurut kuncinya, atau null
 *     $kelengkapan->jumlahLengkap();              // banyak butir yang sudah lengkap
 *
 * Tiap butir:
 *
 *     [
 *         'kunci'      => 'kelompok-umur',
 *         'label'      => 'Kelompok umur',
 *         'lengkap'    => false,
 *         'keterangan' => 'KU 10, KU 12 belum punya jenjang sekolah.',
 *         'alamat'     => 'admin/pengaturan/kelompok-umur',
 *     ]
 *
 * `keterangan` butir yang belum lengkap menyebut apa yang kurang secara
 * spesifik; butir yang lengkap diberi ringkasan singkat.
 */
final class KelengkapanPengaturan
{
    /**
     * Daftar nama dalam keterangan dipendekkan bila lebih dari ini:
     * tiga nama pertama ditulis, sisanya "dan N lainnya".
     */
    public const BATAS_NAMA = 5;

    /**
     * @var list<array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}>
     */
    private array $butir;

    /**
     * @param array<string, mixed> $potret Lihat komentar kelas.
     */
    public function __construct(array $potret)
    {
        $acara        = self::larik($potret['acara'] ?? []);
        $pendaftaran  = self::larik($potret['pendaftaran'] ?? []);
        $pembayaran   = self::larik($potret['pembayaran'] ?? []);
        $kelompokUmur = array_values(array_map(self::larik(...), self::larik($potret['kelompok_umur'] ?? [])));
        $nomorLomba   = array_values(array_map(self::larik(...), self::larik($potret['nomor_lomba'] ?? [])));

        $this->butir = [
            self::butirAcara($acara),
            self::butirPendaftaran($pendaftaran),
            self::butirKelompokUmur($kelompokUmur),
            self::butirNomorLomba($nomorLomba, $kelompokUmur),
            self::butirPembayaran($pembayaran),
        ];
    }

    /**
     * Apakah semua butir lengkap (syarat pendaftaran bisa dibuka).
     */
    public function lengkap(): bool
    {
        return $this->belumLengkap() === [];
    }

    /**
     * Semua butir, berurutan.
     *
     * @return list<array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}>
     */
    public function butir(): array
    {
        return $this->butir;
    }

    /**
     * Butir yang belum lengkap, berurutan.
     *
     * @return list<array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}>
     */
    public function belumLengkap(): array
    {
        return array_values(array_filter($this->butir, static fn (array $butir): bool => ! $butir['lengkap']));
    }

    /**
     * Banyak butir yang sudah lengkap.
     */
    public function jumlahLengkap(): int
    {
        return count($this->butir) - count($this->belumLengkap());
    }

    /**
     * Butir untuk satu halaman pengaturan menurut kuncinya, atau `null` bila
     * halaman itu tidak punya butir kelengkapan (misalnya `jadwal`).
     *
     * @return array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}|null
     */
    public function untukHalaman(string $kunci): ?array
    {
        foreach ($this->butir as $butir) {
            if ($butir['kunci'] === $kunci) {
                return $butir;
            }
        }

        return null;
    }

    // ---------- Butir ----------

    /**
     * @param array<array-key, mixed> $acara
     */
    private static function butirAcara(array $acara): array
    {
        $kurang = [];

        if (! self::teksTerisi($acara['nama'] ?? null)) {
            $kurang[] = 'nama acara';
        }

        if (! self::angkaPositif($acara['seri'] ?? null)) {
            $kurang[] = 'seri';
        }

        if (! self::angkaPositif($acara['tahun'] ?? null)) {
            $kurang[] = 'tahun';
        }

        if (! self::teksTerisi($acara['tempat'] ?? null)) {
            $kurang[] = 'tempat';
        }

        if (self::tanggal($acara['tanggal_mulai'] ?? null) === null) {
            $kurang[] = 'tanggal mulai acara';
        }

        if (self::tanggal($acara['tanggal_selesai'] ?? null) === null) {
            $kurang[] = 'tanggal selesai acara';
        }

        return self::buat(
            'acara',
            'Data acara',
            'admin/pengaturan/acara',
            $kurang === [] ? [] : ['Belum diisi: ' . implode(', ', $kurang) . '.'],
            'Nama, seri, tahun, tempat, dan tanggal acara sudah diisi.',
        );
    }

    /**
     * @param array<array-key, mixed> $pendaftaran
     */
    private static function butirPendaftaran(array $pendaftaran): array
    {
        $kurang = [];

        if (self::tanggal($pendaftaran['mulai'] ?? null) === null) {
            $kurang[] = 'tanggal mulai pendaftaran';
        }

        if (self::tanggal($pendaftaran['akhir'] ?? null) === null) {
            $kurang[] = 'tanggal akhir pendaftaran';
        }

        return self::buat(
            'pendaftaran',
            'Jadwal pendaftaran',
            'admin/pengaturan/pendaftaran',
            $kurang === [] ? [] : ['Belum diisi: ' . implode(', ', $kurang) . '.'],
            'Tanggal mulai dan akhir pendaftaran sudah diisi.',
        );
    }

    /**
     * @param list<array<array-key, mixed>> $kelompokUmur
     */
    private static function butirKelompokUmur(array $kelompokUmur): array
    {
        $aktif = array_values(array_filter($kelompokUmur, self::aktif(...)));

        if ($aktif === []) {
            $pesan = $kelompokUmur === []
                ? 'Belum ada kelompok umur. Tambahkan minimal satu kelompok umur.'
                : 'Belum ada kelompok umur yang aktif. Aktifkan minimal satu kelompok umur.';

            return self::buat('kelompok-umur', 'Kelompok umur', 'admin/pengaturan/kelompok-umur', [$pesan], '');
        }

        // Hal yang kurang => nama kelompok umur yang kurang, berurutan.
        $kurang = [
            'batas tahun lahir'      => [],
            'jenjang sekolah'        => [],
            'biaya individu'         => [],
            'awalan nomor dada'      => [],
        ];

        foreach ($aktif as $ku) {
            $nama = self::nama($ku['nama'] ?? null, 'Kelompok umur tanpa nama');

            if (! self::angkaPositif($ku['tahun_lahir_awal'] ?? null) && ! self::angkaPositif($ku['tahun_lahir_akhir'] ?? null)) {
                $kurang['batas tahun lahir'][] = $nama;
            }

            if (Jenjang::rapikan(self::larik($ku['jenjang'] ?? [])) === []) {
                $kurang['jenjang sekolah'][] = $nama;
            }

            if (! self::angkaTakNegatif($ku['biaya_individu'] ?? null)) {
                $kurang['biaya individu'][] = $nama;
            }

            if (array_filter(self::larik($ku['awalan'] ?? []), self::teksTerisi(...)) === []) {
                $kurang['awalan nomor dada'][] = $nama;
            }
        }

        $kalimat = [];

        foreach ($kurang as $hal => $namaNama) {
            if ($namaNama !== []) {
                $kalimat[] = self::daftarNama($namaNama) . ' belum punya ' . $hal . '.';
            }
        }

        return self::buat(
            'kelompok-umur',
            'Kelompok umur',
            'admin/pengaturan/kelompok-umur',
            $kalimat,
            count($aktif) . ' kelompok umur aktif sudah lengkap.',
        );
    }

    /**
     * @param list<array<array-key, mixed>> $nomorLomba
     * @param list<array<array-key, mixed>> $kelompokUmur
     */
    private static function butirNomorLomba(array $nomorLomba, array $kelompokUmur): array
    {
        // id kelompok umur aktif => nama.
        $kuAktif = [];

        foreach ($kelompokUmur as $ku) {
            if (self::aktif($ku)) {
                $kuAktif[(string) ($ku['id'] ?? '')] = self::nama($ku['nama'] ?? null, 'kelompok umur tanpa nama');
            }
        }

        // Hanya nomor aktif pada kelompok umur aktif yang ikut dinilai: nomor
        // pada kelompok umur yang mati tidak muncul di pendaftaran.
        $dinilai = array_values(array_filter(
            $nomorLomba,
            static fn (array $nomor): bool => self::aktif($nomor) && isset($kuAktif[(string) ($nomor['kelompok_umur_id'] ?? '')]),
        ));

        if ($dinilai === []) {
            $pesan = $kuAktif === []
                ? 'Belum ada nomor lomba aktif, karena belum ada kelompok umur yang aktif.'
                : 'Belum ada nomor lomba aktif pada kelompok umur yang aktif. Tambahkan atau aktifkan minimal satu nomor lomba.';

            return self::buat('nomor-lomba', 'Nomor lomba', 'admin/pengaturan/nomor-lomba', [$pesan], '');
        }

        $kurang = [
            'jumlah inti'          => [],
            'batas anggota'        => [],
            'biaya beregu'         => [],
            'jumlah putra dan putri' => [],
        ];

        foreach ($dinilai as $nomor) {
            if (($nomor['jenis'] ?? '') !== 'beregu') {
                continue;
            }

            $kategori = (string) ($nomor['kategori'] ?? '');
            $nama     = trim(self::nama($nomor['nama'] ?? null, 'Nomor tanpa nama') . ' ' . $kategori)
                . ' (' . $kuAktif[(string) $nomor['kelompok_umur_id']] . ')';

            if (! self::angkaPositif($nomor['jumlah_inti'] ?? null)) {
                $kurang['jumlah inti'][] = $nama;
            }

            if (! self::angkaPositif($nomor['batas_anggota'] ?? null)) {
                $kurang['batas anggota'][] = $nama;
            }

            if (! self::angkaTakNegatif($nomor['biaya_beregu'] ?? null)) {
                $kurang['biaya beregu'][] = $nama;
            }

            if ($kategori === 'mix'
                && (! self::angkaTakNegatif($nomor['jumlah_putra'] ?? null) || ! self::angkaTakNegatif($nomor['jumlah_putri'] ?? null))) {
                $kurang['jumlah putra dan putri'][] = $nama;
            }
        }

        $kalimat = [];

        foreach ($kurang as $hal => $namaNama) {
            if ($namaNama !== []) {
                $kalimat[] = self::daftarNama($namaNama, 'nomor') . ' belum punya ' . $hal . '.';
            }
        }

        return self::buat(
            'nomor-lomba',
            'Nomor lomba',
            'admin/pengaturan/nomor-lomba',
            $kalimat,
            count($dinilai) . ' nomor lomba aktif; nomor beregu sudah lengkap.',
        );
    }

    /**
     * @param array<array-key, mixed> $pembayaran
     */
    private static function butirPembayaran(array $pembayaran): array
    {
        $kurang = [];

        foreach ([
            'bank'             => 'nama bank',
            'nomor_rekening'   => 'nomor rekening',
            'pemilik_rekening' => 'nama pemilik rekening',
            'nomor_wa'         => 'nomor WA pembayaran',
        ] as $kunci => $label) {
            if (! self::teksTerisi($pembayaran[$kunci] ?? null)) {
                $kurang[] = $label;
            }
        }

        return self::buat(
            'pembayaran',
            'Rekening pembayaran',
            'admin/pengaturan/pembayaran',
            $kurang === [] ? [] : ['Belum diisi: ' . implode(', ', $kurang) . '.'],
            'Rekening dan nomor WA pembayaran sudah diisi.',
        );
    }

    // ---------- Bantuan ----------

    /**
     * @param list<string> $kurang Kalimat apa yang kurang; kosong berarti lengkap.
     *
     * @return array{kunci: string, label: string, lengkap: bool, keterangan: string, alamat: string}
     */
    private static function buat(string $kunci, string $label, string $alamat, array $kurang, string $ringkasan): array
    {
        return [
            'kunci'      => $kunci,
            'label'      => $label,
            'lengkap'    => $kurang === [],
            'keterangan' => $kurang === [] ? $ringkasan : implode(' ', $kurang),
            'alamat'     => $alamat,
        ];
    }

    /**
     * "KU 8, KU 10" atau, bila lebih dari `BATAS_NAMA`,
     * "A, B, C, dan 4 nomor lainnya".
     *
     * @param list<string> $nama
     */
    private static function daftarNama(array $nama, string $satuan = 'kelompok umur'): string
    {
        if (count($nama) <= self::BATAS_NAMA) {
            return implode(', ', $nama);
        }

        return implode(', ', array_slice($nama, 0, 3)) . ', dan ' . (count($nama) - 3) . ' ' . $satuan . ' lainnya';
    }

    /**
     * @return array<array-key, mixed>
     */
    private static function larik(mixed $nilai): array
    {
        return is_array($nilai) ? $nilai : [];
    }

    /**
     * @param array<array-key, mixed> $baris
     */
    private static function aktif(array $baris): bool
    {
        return (bool) (int) ($baris['aktif'] ?? 1);
    }

    private static function nama(mixed $nilai, string $cadangan): string
    {
        return self::teksTerisi($nilai) ? trim((string) $nilai) : $cadangan;
    }

    private static function teksTerisi(mixed $nilai): bool
    {
        return (is_string($nilai) || is_int($nilai)) && trim((string) $nilai) !== '';
    }

    private static function angkaPositif(mixed $nilai): bool
    {
        return self::angkaTakNegatif($nilai) && (int) $nilai > 0;
    }

    /**
     * Bilangan bulat 0 atau lebih (biaya 0 berarti gratis, tetap terisi).
     */
    private static function angkaTakNegatif(mixed $nilai): bool
    {
        if (is_int($nilai)) {
            return $nilai >= 0;
        }

        return is_string($nilai) && preg_match('/^\d+$/', trim($nilai)) === 1;
    }

    /**
     * Tanggal atau waktu yang terbaca (`Y-m-d` atau `Y-m-d H:i[:s]`), atau `null`.
     */
    private static function tanggal(mixed $nilai): ?DateTimeImmutable
    {
        return BukaTutupPendaftaran::bacaWaktu($nilai);
    }
}
