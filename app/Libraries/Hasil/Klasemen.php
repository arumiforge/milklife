<?php

namespace App\Libraries\Hasil;

/**
 * Klasemen sekolah per kelompok umur (F11-02, K172 butir 2, menggantikan per
 * jenjang K124) dengan metode yang dipilih admin (F9-11, K140 butir 7a). Tanpa
 * basis data dan tanpa permintaan HTTP; ada uji unit.
 *
 * - Masukan: peringkat 1–8 babak Final (`HasilLomba::juara(…, 8)`), satu baris
 *   per atlet individu atau regu. Peringkat 1–3 = satu piala sesuai
 *   peringkatnya; regu juara = satu piala untuk sekolahnya. Peringkat sama di
 *   Final memberi piala dan poin yang sama kepada masing-masing (asumsi 7 K125).
 * - Kontingen = NPSN; pendaftaran tanpa NPSN = kontingen sendiri.
 * - Satu klasemen per kelompok umur. Susunan (`susunan()`) bisa menggabung
 *   beberapa kelompok umur dalam satu klasemen, misalnya KU 8 dan KU 10;
 *   kelompok umur yang tidak masuk susunan tetap punya klasemen sendiri.
 * - Poin tiap baris = daftar poin peringkat 1–8 (bawaan 7-5-4-3-2-1-0-0).
 *   Kolom poin selalu diisi, apa pun metodenya.
 * - Metode **medali** (bawaan, juknis): juara I terbanyak, lalu juara II, lalu
 *   juara III. Hanya sekolah yang punya piala yang tampil.
 * - Metode **poin**: poin terbanyak, lalu juara I, II, III. Sekolah yang punya
 *   piala atau poin tampil.
 * - Bila masih sama: peringkat bersama (1, 2, 2, 4). Peringkat 1–3 = juara
 *   umum I–III.
 *
 *     $susunan  = Klasemen::susunan([['id' => 1, 'nama' => 'KU 8'], ['id' => 2, 'nama' => 'KU 10'], ['id' => 3, 'nama' => 'KU 12']], [[1, 2]]);
 *     $klasemen = Klasemen::hitung($hasil->juara(false, 8), Klasemen::POIN, [7, 5, 4, 3, 2, 1, 0, 0], $susunan);
 *     // ['ku-1-2' => ['kunci' => 'ku-1-2', 'label' => 'KU 8 dan KU 10', 'baris' => [
 *     //     ['peringkat' => 1, 'juara_umum' => 1, 'sekolah' => 'SD …', 'npsn' => '…', 'emas' => 3, 'perak' => 1, 'perunggu' => 0, 'jumlah' => 4, 'poin' => 26,
 *     //      'piala' => [['peringkat' => 1, 'nomor' => 'Lari 60 m putra', 'kelompok_umur' => 'KU 10', 'nama' => '…'], …]], …]],
 *     //  'ku-3' => ['kunci' => 'ku-3', 'label' => 'KU 12', 'baris' => […]]]
 *
 * Halaman dan unduhan memakai `HasilLomba::klasemen()`, yang membaca metode,
 * poin, dan gabungan kelompok umur dari Pengaturan > Hasil (`MetodeKlasemen`).
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
     * @param iterable<array{peringkat: int, terbit?: bool, nama?: string, nomor?: string, kelompok_umur_id?: int, kelompok_umur?: string, sekolah: string, npsn: string|null, pendaftaran_id: int}> $juara
     * @param string                                                                                                                                                                                  $metode  `MEDALI` atau `POIN`; nilai lain dianggap `MEDALI`
     * @param list<int>                                                                                                                                                                               $poin    poin peringkat 1, 2, … (kurang dari 8 = sisanya 0)
     * @param list<array{kunci: string, label: string, kelompok_umur_id: list<int>}>                                                                                                                  $susunan `susunan()`; kosong = satu klasemen per kelompok umur
     *
     * @return array<string, array{kunci: string, label: string, baris: list<array{peringkat: int, juara_umum: int|null, sekolah: string, npsn: string|null, emas: int, perak: int, perunggu: int, jumlah: int, poin: int, piala: list<array<string, mixed>>}>}>
     */
    public static function hitung(iterable $juara, string $metode = self::MEDALI, array $poin = self::POIN_BAWAAN, array $susunan = []): array
    {
        $metodePoin = $metode === self::POIN;
        $poin       = array_values(array_map('intval', $poin));
        $kontingen  = [];
        $peta       = [];
        $label      = [];

        // Urutan klasemen mengikuti susunan, lalu kelompok umur lain sesuai urutan masukan.
        foreach ($susunan as $s) {
            $label[$s['kunci']] = $s['label'];

            foreach ($s['kelompok_umur_id'] as $id) {
                $peta[(int) $id] = $s['kunci'];
            }
        }

        foreach ($juara as $j) {
            $peringkat = (int) $j['peringkat'];

            if ($peringkat < 1 || $peringkat > self::PERINGKAT_POIN) {
                continue;
            }

            $npsn   = is_string($j['npsn'] ?? null) && $j['npsn'] !== '' ? $j['npsn'] : null;
            $kuId   = (int) ($j['kelompok_umur_id'] ?? 0);
            $kuNama = (string) ($j['kelompok_umur'] ?? '');
            $grup   = $peta[$kuId] ?? self::kunci($kuId > 0 ? [$kuId] : [], $kuNama);
            $kunci  = $npsn !== null ? 'n' . $npsn : 'p' . (int) $j['pendaftaran_id'];

            $label[$grup] ??= $kuNama !== '' ? $kuNama : 'Kelompok umur';
            $kontingen[$grup][$kunci] ??= ['sekolah' => (string) $j['sekolah'], 'npsn' => $npsn, 'emas' => 0, 'perak' => 0, 'perunggu' => 0, 'poin' => 0, 'piala' => []];
            $kontingen[$grup][$kunci]['poin'] += max(0, $poin[$peringkat - 1] ?? 0);

            if ($peringkat > 3) {
                continue;
            }

            $kontingen[$grup][$kunci][[1 => 'emas', 2 => 'perak', 3 => 'perunggu'][$peringkat]]++;
            $kontingen[$grup][$kunci]['piala'][] = [
                'peringkat'     => $peringkat,
                'nomor'         => (string) ($j['nomor'] ?? ''),
                'kelompok_umur' => $kuNama,
                'nama'          => (string) ($j['nama'] ?? ''),
                'terbit'        => (bool) ($j['terbit'] ?? true),
            ];
        }

        $hasil = [];

        foreach (array_keys($label) as $grup) {
            if (! isset($kontingen[$grup])) {
                continue;
            }

            // Medali: hanya sekolah berpiala. Poin: sekolah berpiala atau berpoin.
            $baris = array_values(array_filter(
                $kontingen[$grup],
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

            $hasil[(string) $grup] = ['kunci' => (string) $grup, 'label' => $label[$grup], 'baris' => $baris];
        }

        return $hasil;
    }

    /**
     * Susunan klasemen dari daftar kelompok umur (urut) dan gabungan yang diatur
     * admin. Setiap kelompok umur masuk tepat satu klasemen: gabungan pertama
     * yang memuatnya, atau klasemen sendiri. Id yang tidak dikenal diabaikan;
     * gabungan yang tinggal satu kelompok umur menjadi klasemen biasa. Urutan
     * klasemen mengikuti kelompok umur pertamanya.
     *
     * @param list<array{id: int, nama: string}> $kelompokUmur
     * @param list<list<int>>                    $gabungan
     *
     * @return list<array{kunci: string, label: string, kelompok_umur_id: list<int>}>
     */
    public static function susunan(array $kelompokUmur, array $gabungan = []): array
    {
        $nama = [];

        foreach ($kelompokUmur as $ku) {
            $nama[(int) $ku['id']] = (string) $ku['nama'];
        }

        $grupKu = [];

        foreach ($gabungan as $i => $anggota) {
            foreach ($anggota as $id) {
                if (isset($nama[(int) $id])) {
                    $grupKu[(int) $id] ??= $i;
                }
            }
        }

        $susunan = [];
        $selesai = [];

        foreach ($nama as $id => $namaKu) {
            if (isset($selesai[$id])) {
                continue;
            }

            $anggota = isset($grupKu[$id])
                ? array_keys(array_filter($grupKu, static fn (int $g): bool => $g === $grupKu[$id]))
                : [$id];
            // Anggota mengikuti urutan kelompok umur.
            $anggota = array_values(array_filter(array_keys($nama), static fn (int $k): bool => in_array($k, $anggota, true)));

            foreach ($anggota as $k) {
                $selesai[$k] = true;
            }

            $susunan[] = [
                'kunci'            => self::kunci($anggota, $namaKu),
                'label'            => self::gabungNama(array_map(static fn (int $k): string => $nama[$k], $anggota)),
                'kelompok_umur_id' => $anggota,
            ];
        }

        return $susunan;
    }

    /**
     * Nama beberapa kelompok umur terbaca: "KU 8", "KU 8 dan KU 10",
     * "KU 8, KU 10, dan KU 12".
     *
     * @param list<string> $nama
     */
    public static function gabungNama(array $nama): string
    {
        $nama = array_values($nama);

        return match (count($nama)) {
            0       => '',
            1       => $nama[0],
            2       => $nama[0] . ' dan ' . $nama[1],
            default => implode(', ', array_slice($nama, 0, -1)) . ', dan ' . $nama[count($nama) - 1],
        };
    }

    /**
     * Kalimat susunan klasemen untuk halaman, PDF, dan FAQ, misalnya
     * "Klasemen dihitung per kelompok umur. KU 8 dan KU 10 digabung dalam satu klasemen."
     *
     * @param list<array{kunci: string, label: string, kelompok_umur_id: list<int>}> $susunan
     */
    public static function teksSusunan(array $susunan): string
    {
        $teks = 'Klasemen dihitung per kelompok umur.';

        foreach ($susunan as $s) {
            if (count($s['kelompok_umur_id']) > 1) {
                $teks .= ' ' . $s['label'] . ' digabung dalam satu klasemen.';
            }
        }

        return $teks;
    }

    /**
     * Kunci klasemen untuk id HTML dan JSON: `ku-<id>[-<id>…]`, atau dari nama
     * bila id tidak ada.
     *
     * @param list<int> $id
     */
    private static function kunci(array $id, string $nama): string
    {
        if ($id !== []) {
            return 'ku-' . implode('-', $id);
        }

        return 'ku-' . trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');
    }

    /**
     * Kalimat cara juara umum ditentukan, untuk halaman, PDF, dan FAQ.
     *
     * @param list<int> $poin
     */
    public static function keterangan(string $metode = self::MEDALI, array $poin = self::POIN_BAWAAN): string
    {
        if ($metode !== self::POIN) {
            return 'Juara umum adalah sekolah dengan juara I terbanyak. Bila sama, dilihat juara II, lalu juara III. Bila masih sama, sekolah itu berbagi peringkat.';
        }

        return 'Juara umum adalah sekolah dengan poin terbanyak (' . self::teksPoin($poin) . '). Bila sama, dilihat juara I terbanyak, lalu juara II, lalu juara III. Bila masih sama, sekolah itu berbagi peringkat.';
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
