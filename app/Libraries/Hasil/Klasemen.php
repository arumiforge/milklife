<?php

namespace App\Libraries\Hasil;

use App\Libraries\Jenjang;

/**
 * Klasemen sekolah per jenjang (F8-05, K124 butir 4–6, juknis bagian 11) dengan
 * metode yang dipilih admin (F9-11, K140 butir 7a). Tanpa basis data dan tanpa
 * permintaan HTTP; ada uji unit.
 *
 * - Masukan: peringkat 1–8 babak Final (`HasilLomba::juara(…, 8)`), satu baris
 *   per atlet individu atau regu. Peringkat 1–3 = satu piala sesuai
 *   peringkatnya; regu juara = satu piala untuk sekolahnya. Peringkat sama di
 *   Final memberi piala dan poin yang sama kepada masing-masing (asumsi 7 K125).
 * - Kontingen = NPSN; pendaftaran tanpa NPSN = kontingen sendiri. Jenjang
 *   kontingen = jenjang pendaftaran. Klasemen dihitung terpisah per jenjang.
 * - Poin tiap baris = daftar poin peringkat 1–8 (bawaan 7-5-4-3-2-1-0-0).
 *   Kolom poin selalu diisi, apa pun metodenya.
 * - Metode **medali** (bawaan, juknis): juara I terbanyak, lalu juara II, lalu
 *   juara III. Hanya sekolah yang punya piala yang tampil.
 * - Metode **poin**: poin terbanyak, lalu juara I, II, III. Sekolah yang punya
 *   piala atau poin tampil.
 * - Bila masih sama: peringkat bersama (1, 2, 2, 4). Peringkat 1–3 = juara
 *   umum I–III.
 *
 *     $klasemen = Klasemen::hitung($hasil->juara(false, 8), Klasemen::POIN, [7, 5, 4, 3, 2, 1, 0, 0]);
 *     // ['A' => ['jenjang' => 'A', 'label' => 'SD/MI', 'baris' => [
 *     //     ['peringkat' => 1, 'juara_umum' => 1, 'sekolah' => 'SD …', 'npsn' => '…', 'emas' => 3, 'perak' => 1, 'perunggu' => 0, 'jumlah' => 4, 'poin' => 26,
 *     //      'piala' => [['peringkat' => 1, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'nama' => '…'], …]], …]], …]
 *
 * Halaman dan unduhan memakai `HasilLomba::klasemen()`, yang membaca metode dan
 * poin dari Pengaturan > Hasil (`MetodeKlasemen`).
 */
final class Klasemen
{
    public const MEDALI = 'medali';

    public const POIN = 'poin';

    /**
     * Metode => label untuk admin.
     *
     * @var array<string, string>
     */
    public const METODE = [
        self::MEDALI => 'Medali',
        self::POIN   => 'Poin per peringkat',
    ];

    /**
     * Banyak peringkat yang diberi poin.
     */
    public const PERINGKAT_POIN = 8;

    /**
     * Poin peringkat 1–8 bawaan.
     *
     * @var list<int>
     */
    public const POIN_BAWAAN = [7, 5, 4, 3, 2, 1, 0, 0];

    /**
     * @param iterable<array{peringkat: int, terbit?: bool, nama?: string, nomor?: string, kelompok_umur?: string, sekolah: string, npsn: string|null, pendaftaran_id: int, jenjang: string}> $juara
     * @param string                                                                                                                                                                         $metode `MEDALI` atau `POIN`; nilai lain dianggap `MEDALI`
     * @param list<int>                                                                                                                                                                      $poin   poin peringkat 1, 2, … (kurang dari 8 = sisanya 0)
     *
     * @return array<string, array{jenjang: string, label: string, baris: list<array{peringkat: int, juara_umum: int|null, sekolah: string, npsn: string|null, emas: int, perak: int, perunggu: int, jumlah: int, poin: int, piala: list<array<string, mixed>>}>}>
     */
    public static function hitung(iterable $juara, string $metode = self::MEDALI, array $poin = self::POIN_BAWAAN): array
    {
        $metodePoin = $metode === self::POIN;
        $poin       = array_values(array_map('intval', $poin));
        $kontingen  = [];

        foreach ($juara as $j) {
            $peringkat = (int) $j['peringkat'];

            if ($peringkat < 1 || $peringkat > self::PERINGKAT_POIN) {
                continue;
            }

            $npsn    = is_string($j['npsn'] ?? null) && $j['npsn'] !== '' ? $j['npsn'] : null;
            $jenjang = (string) $j['jenjang'];
            $kunci   = $npsn !== null ? 'n' . $npsn : 'p' . (int) $j['pendaftaran_id'];

            $kontingen[$jenjang][$kunci] ??= ['sekolah' => (string) $j['sekolah'], 'npsn' => $npsn, 'emas' => 0, 'perak' => 0, 'perunggu' => 0, 'poin' => 0, 'piala' => []];
            $kontingen[$jenjang][$kunci]['poin'] += max(0, $poin[$peringkat - 1] ?? 0);

            if ($peringkat > 3) {
                continue;
            }

            $kontingen[$jenjang][$kunci][[1 => 'emas', 2 => 'perak', 3 => 'perunggu'][$peringkat]]++;
            $kontingen[$jenjang][$kunci]['piala'][] = [
                'peringkat'     => $peringkat,
                'nomor'         => (string) ($j['nomor'] ?? ''),
                'kelompok_umur' => (string) ($j['kelompok_umur'] ?? ''),
                'nama'          => (string) ($j['nama'] ?? ''),
                'terbit'        => (bool) ($j['terbit'] ?? true),
            ];
        }

        $hasil = [];

        // Urutan jenjang mengikuti daftar jenjang (SD/MI, SMP/MTs, SMA/SMK/MA), lalu kode lain.
        $urutJenjang = array_unique([...Jenjang::kode(), ...array_keys($kontingen)]);

        foreach ($urutJenjang as $jenjang) {
            if (! isset($kontingen[$jenjang])) {
                continue;
            }

            // Medali: hanya sekolah berpiala. Poin: sekolah berpiala atau berpoin.
            $baris = array_values(array_filter(
                $kontingen[$jenjang],
                static fn (array $k): bool => $k['piala'] !== [] || ($metodePoin && $k['poin'] > 0),
            ));

            if ($baris === []) {
                continue;
            }

            $nilai = static fn (array $k): array => $metodePoin
                ? [$k['poin'], $k['emas'], $k['perak'], $k['perunggu']]
                : [$k['emas'], $k['perak'], $k['perunggu']];

            usort($baris, static fn (array $a, array $b): int => [...$nilai($b), $a['sekolah']] <=> [...$nilai($a), $b['sekolah']]);

            $sebelum = null;
            $posisi  = 0;

            foreach ($baris as $i => &$k) {
                if ($nilai($k) !== $sebelum) {
                    $posisi  = $i + 1;
                    $sebelum = $nilai($k);
                }

                usort($k['piala'], static fn (array $x, array $y): int => [$x['peringkat'], $x['kelompok_umur'], $x['nomor']] <=> [$y['peringkat'], $y['kelompok_umur'], $y['nomor']]);

                $k = [
                    'peringkat'  => $posisi,
                    'juara_umum' => $posisi <= 3 ? $posisi : null,
                    'sekolah'    => $k['sekolah'],
                    'npsn'       => $k['npsn'],
                    'emas'       => $k['emas'],
                    'perak'      => $k['perak'],
                    'perunggu'   => $k['perunggu'],
                    'jumlah'     => $k['emas'] + $k['perak'] + $k['perunggu'],
                    'poin'       => $k['poin'],
                    'piala'      => $k['piala'],
                ];
            }
            unset($k);

            $hasil[(string) $jenjang] = ['jenjang' => (string) $jenjang, 'label' => Jenjang::label($jenjang) ?? (string) $jenjang, 'baris' => $baris];
        }

        return $hasil;
    }

    /**
     * Kalimat cara juara umum ditentukan, untuk halaman, PDF, dan FAQ.
     *
     * @param list<int> $poin
     */
    public static function keterangan(string $metode = self::MEDALI, array $poin = self::POIN_BAWAAN): string
    {
        if ($metode !== self::POIN) {
            return 'Juara umum ditentukan jumlah juara I terbanyak, lalu juara II, lalu juara III; bila masih sama, peringkat bersama.';
        }

        return 'Juara umum ditentukan jumlah poin terbanyak (' . self::teksPoin($poin) . '); bila sama, juara I terbanyak, lalu juara II, lalu juara III; bila masih sama, peringkat bersama.';
    }

    /**
     * Daftar poin terbaca, misalnya "peringkat 1 = 7, 2 = 5, 3 = 4, 4 = 3, 5 = 2, 6 = 1 poin".
     * Peringkat berpoin 0 di ujung daftar tidak ditulis.
     *
     * @param list<int> $poin
     */
    public static function teksPoin(array $poin): string
    {
        $poin = array_values(array_map('intval', array_slice($poin, 0, self::PERINGKAT_POIN)));

        while ($poin !== [] && end($poin) <= 0) {
            array_pop($poin);
        }

        if ($poin === []) {
            return 'semua peringkat 0 poin';
        }

        $bagian = [];

        foreach ($poin as $i => $p) {
            $bagian[] = ($i === 0 ? 'peringkat ' : '') . ($i + 1) . ' = ' . max(0, $p);
        }

        return implode(', ', $bagian) . ' poin';
    }
}
