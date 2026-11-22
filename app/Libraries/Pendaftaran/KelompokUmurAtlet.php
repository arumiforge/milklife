<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Jenjang;
use App\Models\KelompokUmurModel;

/**
 * Kelompok umur atlet dari jenjang sekolah dan tahun lahir (rancangan 6.4;
 * K95, yang menggantikan sebagian K23 dan K36).
 *
 * Aturan murni: daftar kelompok umur diberikan pemanggil (hanya yang aktif).
 *
 * - Jenjang dulu: hanya kelompok umur yang dibuka untuk jenjang sekolah yang
 *   dipertimbangkan.
 * - Baru tahun lahir: di antara kelompok umur itu, dicari yang rentangnya
 *   cocok. Bulan dan tanggal diabaikan.
 * - Batas kosong berarti tidak dibatasi di sisi itu. Kelompok umur yang kedua
 *   batasnya kosong belum punya rentang dan tidak pernah cocok.
 * - Rentang boleh beririsan antar-jenjang (KU 12 untuk SD dan KU 15 untuk SMP
 *   sama-sama menerima 2012). Dalam satu jenjang rentang hanya boleh beririsan
 *   bila admin menyalakan "Pendaftar memilih kelompok umur" (K134; dijaga
 *   pengaturan). Bila tahun lahir cocok dengan lebih dari satu kelompok umur,
 *   pendaftar memilih salah satunya (`$pilihan`); tanpa pilihan yang sah,
 *   hasilnya galat yang meminta memilih, beserta daftar `pilihan`.
 *
 *     $hasil = KelompokUmurAtlet::tentukan(2012, 'B', $daftar);
 *     $hasil['kelompok_umur']; // baris KU 15, atau null
 *     $hasil['galat'];         // null, atau kalimat penolakan
 *     $hasil['pilihan'];       // kelompok umur yang cocok (lebih dari satu = pendaftar memilih)
 */
final class KelompokUmurAtlet
{
    /**
     * @param list<array<string, mixed>> $daftar  Kelompok umur aktif: `id`, `nama`,
     *                                            `tahun_lahir_awal`, `tahun_lahir_akhir`, `jenjang` (list kode)
     * @param int|null                   $pilihan Id kelompok umur yang dipilih pendaftar (K134), atau null.
     *
     * @return array{kelompok_umur: array<string, mixed>|null, galat: string|null, pilihan: list<array<string, mixed>>}
     */
    public static function tentukan(int $tahunLahir, string $jenjang, array $daftar, ?int $pilihan = null): array
    {
        $untukJenjang = array_values(array_filter(
            $daftar,
            static fn (array $ku): bool => in_array($jenjang, Jenjang::rapikan($ku['jenjang'] ?? []), true),
        ));

        $cocok = array_values(array_filter($untukJenjang, static fn (array $ku): bool => self::masuk($tahunLahir, $ku)));

        if (count($cocok) === 1) {
            return ['kelompok_umur' => $cocok[0], 'galat' => null, 'pilihan' => $cocok];
        }

        if ($cocok !== []) {
            foreach ($cocok as $ku) {
                if ($pilihan !== null && (int) $ku['id'] === $pilihan) {
                    return ['kelompok_umur' => $ku, 'galat' => null, 'pilihan' => $cocok];
                }
            }

            return [
                'kelompok_umur' => null,
                'galat'         => "Atlet lahir tahun {$tahunLahir} boleh ikut " . self::gabungNama($cocok) . '. Pilih salah satu kelompok umur untuk atlet ini.',
                'pilihan'       => $cocok,
            ];
        }

        $labelJenjang = Jenjang::label($jenjang) ?? $jenjang;

        if ($untukJenjang === []) {
            return [
                'kelompok_umur' => null,
                'galat'         => "Belum ada kelompok umur yang dibuka untuk {$labelJenjang}. Atlet ini belum bisa didaftarkan.",
                'pilihan'       => [],
            ];
        }

        $rentang = self::daftarRentang($untukJenjang, $labelJenjang);

        foreach ($daftar as $ku) {
            $boleh = Jenjang::rapikan($ku['jenjang'] ?? []);

            if ($boleh !== [] && self::masuk($tahunLahir, $ku)) {
                return [
                    'kelompok_umur' => null,
                    'galat'         => "Atlet lahir tahun {$tahunLahir} masuk {$ku['nama']}, yang hanya untuk " . Jenjang::gabungLabel($boleh) . '. '
                        . "Sekolah ini {$labelJenjang}, jadi atlet ini tidak bisa didaftarkan{$rentang}.",
                    'pilihan' => [],
                ];
            }
        }

        return [
            'kelompok_umur' => null,
            'galat'         => "Atlet lahir tahun {$tahunLahir} tidak masuk kelompok umur mana pun untuk {$labelJenjang}{$rentang}. Periksa lagi tanggal lahirnya.",
            'pilihan'       => [],
        ];
    }

    /**
     * "KU 10 atau KU 12", "KU 8, KU 10, atau KU 12".
     *
     * @param list<array<string, mixed>> $daftar
     */
    public static function gabungNama(array $daftar): string
    {
        $nama = array_map(static fn (array $ku): string => (string) $ku['nama'], $daftar);

        if (count($nama) <= 2) {
            return implode(' atau ', $nama);
        }

        $akhir = array_pop($nama);

        return implode(', ', $nama) . ', atau ' . $akhir;
    }

    /**
     * Tahun lahir yang masuk lebih dari satu kelompok umur dalam satu jenjang
     * (K134), untuk keterangan admin dan pendaftar. Hanya tahun yang kedua
     * kelompok umurnya punya batas di sisi irisan; rentang terbuka dibatasi
     * tahun berjalan.
     *
     *     KelompokUmurAtlet::irisan($daftarAktif);
     *     // [['jenjang' => 'A', 'tahun' => [2016], 'kelompok_umur' => ['KU 10', 'KU 12']]]
     *
     * @param list<array<string, mixed>> $daftar Kelompok umur aktif (dengan `jenjang`).
     *
     * @return list<array{jenjang: string, tahun: list<int>, kelompok_umur: list<string>}>
     */
    public static function irisan(array $daftar, ?int $tahunTerbesar = null): array
    {
        $tahunTerbesar ??= (int) date('Y');
        $hasil = [];

        foreach (Jenjang::kode() as $jenjang) {
            $untukJenjang = array_values(array_filter(
                $daftar,
                static fn (array $ku): bool => in_array($jenjang, Jenjang::rapikan($ku['jenjang'] ?? []), true)
                    && (self::tahun($ku['tahun_lahir_awal'] ?? null) !== null || self::tahun($ku['tahun_lahir_akhir'] ?? null) !== null),
            ));

            $kelompok = [];

            foreach ($untukJenjang as $i => $a) {
                foreach (array_slice($untukJenjang, $i + 1) as $b) {
                    $awal  = max(self::tahun($a['tahun_lahir_awal'] ?? null) ?? KelompokUmurModel::TAHUN_LAHIR_TERKECIL, self::tahun($b['tahun_lahir_awal'] ?? null) ?? KelompokUmurModel::TAHUN_LAHIR_TERKECIL);
                    $akhir = min(self::tahun($a['tahun_lahir_akhir'] ?? null) ?? $tahunTerbesar, self::tahun($b['tahun_lahir_akhir'] ?? null) ?? $tahunTerbesar);

                    for ($t = $awal; $t <= $akhir; $t++) {
                        $kelompok[$t] ??= [];
                        $kelompok[$t][(int) $a['id']] = (string) $a['nama'];
                        $kelompok[$t][(int) $b['id']] = (string) $b['nama'];
                    }
                }
            }

            ksort($kelompok);

            // Tahun berurutan dengan kelompok umur yang sama digabung satu baris.
            foreach ($kelompok as $tahun => $nama) {
                $nama  = array_values($nama);
                $akhir = $hasil === [] ? null : $hasil[array_key_last($hasil)];

                if ($akhir !== null && $akhir['jenjang'] === $jenjang && $akhir['kelompok_umur'] === $nama && end($akhir['tahun']) === $tahun - 1) {
                    $hasil[array_key_last($hasil)]['tahun'][] = $tahun;

                    continue;
                }

                $hasil[] = ['jenjang' => $jenjang, 'tahun' => [$tahun], 'kelompok_umur' => $nama];
            }
        }

        return $hasil;
    }

    /**
     * Apakah tahun lahir masuk rentang kelompok umur ini.
     *
     * @param array<string, mixed> $ku
     */
    public static function masuk(int $tahunLahir, array $ku): bool
    {
        $awal  = self::tahun($ku['tahun_lahir_awal'] ?? null);
        $akhir = self::tahun($ku['tahun_lahir_akhir'] ?? null);

        if ($awal === null && $akhir === null) {
            return false;
        }

        return ($awal === null || $tahunLahir >= $awal) && ($akhir === null || $tahunLahir <= $akhir);
    }

    /**
     * ". Kelompok umur untuk SD/MI: KU 8 (lahir 2019 ke atas), KU 10 (lahir 2016–2018)",
     * atau '' bila tidak ada yang punya rentang.
     *
     * @param list<array<string, mixed>> $daftar
     */
    private static function daftarRentang(array $daftar, string $labelJenjang): string
    {
        $bagian = [];

        foreach ($daftar as $ku) {
            $teks = KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'] ?? null, $ku['tahun_lahir_akhir'] ?? null);

            if ($teks !== '') {
                $bagian[] = $ku['nama'] . ' (' . lcfirst($teks) . ')';
            }
        }

        return $bagian === [] ? '' : ". Kelompok umur untuk {$labelJenjang}: " . implode(', ', $bagian);
    }

    private static function tahun(mixed $nilai): ?int
    {
        return $nilai === null || $nilai === '' ? null : (int) $nilai;
    }
}
