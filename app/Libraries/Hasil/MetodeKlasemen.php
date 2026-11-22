<?php

namespace App\Libraries\Hasil;

use CodeIgniter\Database\BaseConnection;

/**
 * Pengaturan klasemen juara umum di Pengaturan > Hasil: metode dan poin (F9-11,
 * K140 butir 7a) di `Klasemen.metode` dan `Klasemen.poin`, serta gabungan
 * kelompok umur (F11-02, K172 butir 2) di `Klasemen.gabungan` (JSON daftar
 * gabungan, tiap gabungan berisi id kelompok umur, misalnya `[[1,2]]`).
 *
 *     MetodeKlasemen::metode();   // 'medali' atau 'poin'
 *     MetodeKlasemen::poin();     // [7, 5, 4, 3, 2, 1, 0, 0]
 *     MetodeKlasemen::gabungan(); // [[1, 2]]; [] bila kosong atau rusak
 *     MetodeKlasemen::susunan();  // `Klasemen::susunan()` dari tabel kelompok_umur
 *     $galat = MetodeKlasemen::periksa('poin', ['10', '8', '6', '5', '4', '3', '2', '1']); // [] = sah
 */
final class MetodeKlasemen
{
    /**
     * Poin satu peringkat paling besar.
     */
    public const POIN_MAKSIMAL = 999;

    public static function metode(): string
    {
        $metode = (string) setting('Klasemen.metode');

        return array_key_exists($metode, Klasemen::METODE) ? $metode : Klasemen::MEDALI;
    }

    /**
     * Poin peringkat 1–8 tersimpan; bawaan bila tersimpan rusak.
     *
     * @return list<int>
     */
    public static function poin(): array
    {
        return self::urai((string) setting('Klasemen.poin')) ?? Klasemen::POIN_BAWAAN;
    }

    /**
     * Gabungan kelompok umur tersimpan: daftar gabungan berisi dua id kelompok
     * umur atau lebih. Pengaturan kosong, rusak, atau bentuk lain = `[]`
     * (satu klasemen per kelompok umur).
     *
     * @return list<list<int>>
     */
    public static function gabungan(): array
    {
        $data = json_decode((string) setting('Klasemen.gabungan'), true);

        if (! is_array($data)) {
            return [];
        }

        $gabungan = [];

        foreach ($data as $anggota) {
            if (! is_array($anggota)) {
                continue;
            }

            $id = array_values(array_unique(array_map('intval', array_filter($anggota, static fn (mixed $v): bool => is_int($v) || (is_string($v) && ctype_digit($v))))));
            $id = array_values(array_filter($id, static fn (int $v): bool => $v > 0));

            if (count($id) > 1) {
                $gabungan[] = $id;
            }
        }

        return $gabungan;
    }

    /**
     * Susunan klasemen dari semua kelompok umur (urutan Pengaturan > Kelompok
     * umur) dan gabungan tersimpan.
     *
     * @return list<array{kunci: string, label: string, kelompok_umur_id: list<int>}>
     */
    public static function susunan(?BaseConnection $db = null): array
    {
        $kelompokUmur = array_map(
            static fn (array $ku): array => ['id' => (int) $ku['id'], 'nama' => (string) $ku['nama']],
            ($db ?? db_connect())->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray(),
        );

        return Klasemen::susunan($kelompokUmur, self::gabungan());
    }

    /**
     * Periksa isian gabungan: nomor klasemen per kelompok umur (`[id => '1', …]`).
     * Kelompok umur bernomor sama digabung dalam satu klasemen. Hasil `galat`
     * berkunci `klasemen_<id>`; `gabungan` siap disimpan (hanya yang berisi dua
     * kelompok umur atau lebih).
     *
     * @param array<int|string, mixed> $isian
     * @param list<int>                $idKelompokUmur semua id kelompok umur
     *
     * @return array{galat: array<string, string>, gabungan: list<list<int>>}
     */
    public static function periksaGabungan(array $isian, array $idKelompokUmur): array
    {
        $galat  = [];
        $nomor  = [];
        $jumlah = count($idKelompokUmur);

        foreach ($idKelompokUmur as $id) {
            $nilai = $isian[$id] ?? null;
            $teks  = is_scalar($nilai) ? trim((string) $nilai) : '';

            if (preg_match('/^\d{1,3}$/', $teks) !== 1 || (int) $teks < 1 || (int) $teks > $jumlah) {
                $galat['klasemen_' . $id] = 'Pilih klasemen 1 sampai ' . $jumlah . '.';

                continue;
            }

            $nomor[(int) $teks][] = $id;
        }

        ksort($nomor);

        return [
            'galat'    => $galat,
            'gabungan' => $galat === [] ? array_values(array_filter($nomor, static fn (array $a): bool => count($a) > 1)) : [],
        ];
    }

    /**
     * @param list<list<int>> $gabungan hasil `periksaGabungan()`
     */
    public static function simpanGabungan(array $gabungan): void
    {
        setting('Klasemen.gabungan', json_encode(array_values($gabungan)));
    }

    /**
     * Periksa isian formulir. Kunci galat: `metode`, `poin`, atau `poin_<n>`
     * (peringkat n, mulai 1; sama dengan id kotak isian).
     *
     * @param array<int|string, mixed> $poin isian poin peringkat 1–8
     *
     * @return array<string, string>
     */
    public static function periksa(string $metode, array $poin): array
    {
        $galat = [];

        if (! array_key_exists($metode, Klasemen::METODE)) {
            $galat['metode'] = 'Pilih metode klasemen: Medali atau Poin per peringkat.';
        }

        $poin = array_values($poin);

        if (count($poin) !== Klasemen::PERINGKAT_POIN) {
            $galat['poin'] = 'Isi poin untuk peringkat 1 sampai ' . Klasemen::PERINGKAT_POIN . '.';

            return $galat;
        }

        foreach ($poin as $i => $nilai) {
            $teks = is_scalar($nilai) ? trim((string) $nilai) : '';

            if (preg_match('/^\d{1,3}$/', $teks) !== 1) {
                $galat['poin_' . ($i + 1)] = 'Poin peringkat ' . ($i + 1) . ' harus angka bulat 0 sampai ' . self::POIN_MAKSIMAL . '. Tulis 0 bila peringkat ini tidak diberi poin.';
            }
        }

        if ($galat !== []) {
            return $galat;
        }

        $angka = array_map('intval', $poin);

        for ($i = 1; $i < count($angka); $i++) {
            if ($angka[$i] > $angka[$i - 1]) {
                $galat['poin_' . ($i + 1)] = 'Poin peringkat ' . ($i + 1) . ' tidak boleh lebih besar dari poin peringkat ' . $i . '.';

                return $galat;
            }
        }

        if ($angka[0] === 0) {
            $galat['poin_1'] = 'Poin peringkat 1 harus lebih dari 0.';
        }

        return $galat;
    }

    /**
     * Simpan isian yang sudah lolos `periksa()`.
     *
     * @param array<int|string, mixed> $poin
     */
    public static function simpan(string $metode, array $poin): void
    {
        setting('Klasemen.metode', $metode);
        setting('Klasemen.poin', implode(',', array_map(static fn (mixed $p): int => (int) $p, array_values($poin))));
    }

    /**
     * "7,5,4,3,2,1,0,0" → [7, 5, 4, 3, 2, 1, 0, 0]; `null` bila bukan 8 angka.
     *
     * @return list<int>|null
     */
    public static function urai(string $teks): ?array
    {
        $bagian = array_map('trim', explode(',', $teks));

        if (count($bagian) !== Klasemen::PERINGKAT_POIN) {
            return null;
        }

        foreach ($bagian as $b) {
            if (preg_match('/^\d{1,3}$/', $b) !== 1) {
                return null;
            }
        }

        return array_map('intval', $bagian);
    }
}
