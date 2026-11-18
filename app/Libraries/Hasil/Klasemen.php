<?php

namespace App\Libraries\Hasil;

use App\Libraries\Jenjang;

/**
 * Klasemen sekolah per jenjang (F8-05, K124 butir 4–6, juknis bagian 11).
 * Tanpa basis data dan tanpa permintaan HTTP; ada uji unit.
 *
 * - Masukan: juara I–III babak Final (`HasilLomba::juara()`), satu baris per
 *   piala. Atlet individu = satu piala sesuai peringkatnya; regu juara = satu
 *   piala untuk sekolahnya. Peringkat sama di Final memberi piala yang sama
 *   kepada masing-masing (asumsi 7 K125).
 * - Kontingen = NPSN; pendaftaran tanpa NPSN = kontingen sendiri. Jenjang
 *   kontingen = jenjang pendaftaran. Klasemen dihitung terpisah per jenjang.
 * - Urutan: juara I terbanyak, lalu juara II, lalu juara III; bila masih sama,
 *   peringkat bersama (1, 2, 2, 4). Peringkat 1–3 = juara umum I–III.
 *
 *     $klasemen = Klasemen::hitung($hasil->juara());
 *     // ['A' => ['jenjang' => 'A', 'label' => 'SD/MI', 'baris' => [
 *     //     ['peringkat' => 1, 'juara_umum' => 1, 'sekolah' => 'SD …', 'npsn' => '…', 'emas' => 3, 'perak' => 1, 'perunggu' => 0, 'jumlah' => 4,
 *     //      'piala' => [['peringkat' => 1, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'nama' => '…'], …]], …]], …]
 */
final class Klasemen
{
    /**
     * @param iterable<array{peringkat: int, terbit?: bool, nama?: string, nomor?: string, kelompok_umur?: string, sekolah: string, npsn: string|null, pendaftaran_id: int, jenjang: string}> $juara
     *
     * @return array<string, array{jenjang: string, label: string, baris: list<array{peringkat: int, juara_umum: int|null, sekolah: string, npsn: string|null, emas: int, perak: int, perunggu: int, jumlah: int, piala: list<array<string, mixed>>}>}>
     */
    public static function hitung(iterable $juara): array
    {
        $kontingen = [];

        foreach ($juara as $j) {
            $peringkat = (int) $j['peringkat'];

            if ($peringkat < 1 || $peringkat > 3) {
                continue;
            }

            $npsn    = is_string($j['npsn'] ?? null) && $j['npsn'] !== '' ? $j['npsn'] : null;
            $jenjang = (string) $j['jenjang'];
            $kunci   = $npsn !== null ? 'n' . $npsn : 'p' . (int) $j['pendaftaran_id'];

            $kontingen[$jenjang][$kunci] ??= ['sekolah' => (string) $j['sekolah'], 'npsn' => $npsn, 'emas' => 0, 'perak' => 0, 'perunggu' => 0, 'piala' => []];
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

            $baris = array_values($kontingen[$jenjang]);

            usort($baris, static fn (array $a, array $b): int => [$b['emas'], $b['perak'], $b['perunggu'], $a['sekolah']] <=> [$a['emas'], $a['perak'], $a['perunggu'], $b['sekolah']]);

            $sebelum = null;
            $posisi  = 0;

            foreach ($baris as $i => &$k) {
                $nilai = [$k['emas'], $k['perak'], $k['perunggu']];

                if ($nilai !== $sebelum) {
                    $posisi  = $i + 1;
                    $sebelum = $nilai;
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
                    'piala'      => $k['piala'],
                ];
            }
            unset($k);

            $hasil[(string) $jenjang] = ['jenjang' => (string) $jenjang, 'label' => Jenjang::label($jenjang) ?? (string) $jenjang, 'baris' => $baris];
        }

        return $hasil;
    }
}
