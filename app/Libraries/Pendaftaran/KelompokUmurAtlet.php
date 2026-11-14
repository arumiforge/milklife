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
 *   sama-sama menerima 2012), tetapi tidak dalam satu jenjang (dijaga
 *   pengaturan), jadi yang cocok pertama yang dipakai.
 *
 *     $hasil = KelompokUmurAtlet::tentukan(2012, 'B', $daftar);
 *     $hasil['kelompok_umur']; // baris KU 15, atau null
 *     $hasil['galat'];         // null, atau kalimat penolakan
 */
final class KelompokUmurAtlet
{
    /**
     * @param list<array<string, mixed>> $daftar Kelompok umur aktif: `id`, `nama`,
     *                                           `tahun_lahir_awal`, `tahun_lahir_akhir`, `jenjang` (list kode)
     *
     * @return array{kelompok_umur: array<string, mixed>|null, galat: string|null}
     */
    public static function tentukan(int $tahunLahir, string $jenjang, array $daftar): array
    {
        $untukJenjang = array_values(array_filter(
            $daftar,
            static fn (array $ku): bool => in_array($jenjang, Jenjang::rapikan($ku['jenjang'] ?? []), true),
        ));

        foreach ($untukJenjang as $ku) {
            if (self::masuk($tahunLahir, $ku)) {
                return ['kelompok_umur' => $ku, 'galat' => null];
            }
        }

        $labelJenjang = Jenjang::label($jenjang) ?? $jenjang;

        if ($untukJenjang === []) {
            return [
                'kelompok_umur' => null,
                'galat'         => "Belum ada kelompok umur yang dibuka untuk {$labelJenjang}, jadi atlet ini tidak bisa didaftarkan.",
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
                ];
            }
        }

        return [
            'kelompok_umur' => null,
            'galat'         => "Atlet lahir tahun {$tahunLahir} tidak masuk kelompok umur mana pun untuk {$labelJenjang}{$rentang}. Periksa lagi tanggal lahirnya.",
        ];
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
