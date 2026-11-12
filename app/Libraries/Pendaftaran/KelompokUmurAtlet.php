<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Jenjang;
use App\Models\KelompokUmurModel;

/**
 * Kelompok umur atlet dari tahun lahir, dibatasi jenjang sekolah
 * (rancangan 6.4; K36, K23).
 *
 * Aturan murni: daftar kelompok umur diberikan pemanggil (hanya yang aktif).
 *
 * - Hanya tahun lahir yang dipakai; bulan dan tanggal diabaikan.
 * - Batas kosong berarti tidak dibatasi di sisi itu. Kelompok umur yang kedua
 *   batasnya kosong belum punya rentang dan tidak pernah cocok.
 * - Rentang kelompok umur aktif tidak tumpang tindih (dijaga pengaturan), jadi
 *   yang cocok pertama yang dipakai.
 * - Bila kelompok umurnya tidak dibuka untuk jenjang sekolah, atlet ditolak;
 *   atlet tidak dipindah ke kelompok umur lain.
 *
 *     $hasil = KelompokUmurAtlet::tentukan(2017, 'A', $daftar);
 *     $hasil['kelompok_umur']; // baris KU 10, atau null
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
        $cocok = null;

        foreach ($daftar as $ku) {
            if (self::masuk($tahunLahir, $ku)) {
                $cocok = $ku;
                break;
            }
        }

        if ($cocok === null) {
            return [
                'kelompok_umur' => null,
                'galat'         => "Atlet lahir tahun {$tahunLahir} tidak masuk kelompok umur mana pun"
                    . self::daftarRentang($daftar) . '. Periksa lagi tanggal lahirnya.',
            ];
        }

        $boleh = Jenjang::rapikan($cocok['jenjang'] ?? []);

        if (! in_array($jenjang, $boleh, true)) {
            $labelBoleh = $boleh === [] ? 'belum dibuka untuk jenjang mana pun' : 'hanya untuk ' . Jenjang::gabungLabel($boleh);

            return [
                'kelompok_umur' => null,
                'galat'         => "Atlet lahir tahun {$tahunLahir} masuk {$cocok['nama']}, yang {$labelBoleh}. "
                    . 'Sekolah ini ' . (Jenjang::label($jenjang) ?? $jenjang) . ', jadi atlet ini tidak bisa didaftarkan.',
            ];
        }

        return ['kelompok_umur' => $cocok, 'galat' => null];
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
     * ": KU 8 (lahir 2019 ke atas), KU 10 (lahir 2016–2018)", atau '' bila kosong.
     *
     * @param list<array<string, mixed>> $daftar
     */
    private static function daftarRentang(array $daftar): string
    {
        $bagian = [];

        foreach ($daftar as $ku) {
            $teks = KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'] ?? null, $ku['tahun_lahir_akhir'] ?? null);

            if ($teks !== '') {
                $bagian[] = $ku['nama'] . ' (' . lcfirst($teks) . ')';
            }
        }

        return $bagian === [] ? '' : '. Kelompok umur yang dibuka: ' . implode(', ', $bagian);
    }

    private static function tahun(mixed $nilai): ?int
    {
        return $nilai === null || $nilai === '' ? null : (int) $nilai;
    }
}
