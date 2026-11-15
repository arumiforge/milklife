<?php

namespace App\Libraries\Pendaftaran;

/**
 * Regu pada nomor beregu (K105, K106).
 *
 * Satu sekolah boleh punya beberapa regu di satu nomor beregu. Setiap anggota
 * regu berperan inti atau cadangan. Nama regu diatur admin di Pengaturan >
 * Pendaftaran, satu pilihan untuk semua nomor beregu:
 *
 * - **Ketentuan admin** (`ketentuan`, bawaan): official memilih dari daftar
 *   nama yang ditulis admin, misalnya "A, B, C". Banyaknya nama ikut
 *   membatasi regu per sekolah, bersama `regu_maksimal` nomor lomba.
 * - **Bebas** (`bebas`): official mengetik nama regu sendiri; hanya
 *   `regu_maksimal` nomor lomba yang membatasi banyaknya regu.
 *
 * Nama regu selalu dirapikan dengan `rapikan()` (spasi, kata "Regu" di depan
 * dibuang, huruf awal tiap kata kapital), supaya "merah" dan "MERAH" adalah
 * regu yang sama. Kunci regu dalam satu sekolah adalah "nomor:nama".
 *
 *     Regu::rapikan(' regu  merah ');            // 'Merah'
 *     Regu::daftarLabel('a, B , putra 1, a');    // ['A', 'B', 'Putra 1']
 *     Regu::dibolehkan(2, ['A', 'B', 'C']);      // ['A', 'B']
 *     Regu::kunci(40, 'B');                      // '40:B'
 *     Regu::pecah('40:Putra 1');                 // [40, 'Putra 1']
 *     Regu::nama("Kanga's Escape putra", 'B');   // "Kanga's Escape putra Regu B"
 */
final class Regu
{
    public const INTI     = 'inti';
    public const CADANGAN = 'cadangan';

    /**
     * @var array<string, string>
     */
    public const PERAN = [self::INTI => 'Inti', self::CADANGAN => 'Cadangan'];

    public const MODE_KETENTUAN = 'ketentuan';
    public const MODE_BEBAS     = 'bebas';

    /**
     * @var array<string, string>
     */
    public const MODE = [
        self::MODE_KETENTUAN => 'Ketentuan admin',
        self::MODE_BEBAS     => 'Bebas',
    ];

    /**
     * Nama regu bawaan mode Ketentuan admin.
     */
    public const LABEL_BAWAAN = 'A, B, C';

    /**
     * Regu paling banyak per sekolah per nomor, juga banyaknya nama di daftar admin.
     */
    public const PALING_BANYAK = 26;

    public const PANJANG_MAKSIMAL = 30;

    /**
     * Nama regu yang dirapikan: spasi berderet menjadi satu, kata "Regu" di
     * depan dibuang, huruf awal tiap kata kapital dan sisanya kecil.
     */
    public static function rapikan(mixed $nama): string
    {
        $teks = is_string($nama) || is_int($nama) ? (string) $nama : '';
        $teks = trim((string) preg_replace('/\s+/u', ' ', $teks));
        $teks = trim((string) preg_replace('/^regu(\s+|$)/iu', '', $teks));

        return (string) preg_replace_callback(
            '/(^|[\s\-\/.])(\p{Ll})/u',
            static fn (array $m): string => $m[1] . mb_strtoupper($m[2]),
            mb_strtolower($teks),
        );
    }

    /**
     * Apakah nama regu (sesudah `rapikan()`) boleh dipakai: 1–30 huruf, angka,
     * spasi, titik, garis miring, atau tanda hubung, diawali huruf atau angka.
     */
    public static function sah(mixed $nama): bool
    {
        return is_string($nama)
            && mb_strlen($nama) <= self::PANJANG_MAKSIMAL
            && preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .\-\/]*$/uD', $nama) === 1;
    }

    /**
     * Daftar nama regu dari isian admin yang dipisah koma, dirapikan, tanpa
     * nama kembar dan nama yang tidak sah.
     *
     * @return list<string>
     */
    public static function daftarLabel(mixed $teks): array
    {
        $hasil = [];

        foreach (explode(',', is_string($teks) ? $teks : '') as $bagian) {
            $nama = self::rapikan($bagian);

            if ($nama !== '' && self::sah($nama)) {
                $hasil[mb_strtolower($nama)] ??= $nama;
            }
        }

        return array_values($hasil);
    }

    /**
     * Nama regu yang boleh dipakai sekolah baru di nomor dengan batas ini.
     * Tanpa `$label`: huruf A, B, C, … (paling banyak 26).
     *
     * @param list<string>|null $label daftar nama dari admin (mode Ketentuan admin)
     *
     * @return list<string>
     */
    public static function dibolehkan(?int $maksimal, ?array $label = null): array
    {
        $label ??= array_map(static fn (int $i): string => chr(65 + $i), range(0, self::PALING_BANYAK - 1));
        $jumlah  = $maksimal === null || $maksimal < 1 ? count($label) : min($maksimal, count($label));

        return array_slice(array_values($label), 0, $jumlah);
    }

    public static function peranSah(mixed $peran): bool
    {
        return is_string($peran) && isset(self::PERAN[$peran]);
    }

    public static function kunci(int $nomorId, string $regu): string
    {
        return $nomorId . ':' . $regu;
    }

    /**
     * @return array{0: int, 1: string}|null
     */
    public static function pecah(mixed $kunci): ?array
    {
        if (! is_string($kunci) || preg_match('/^([1-9]\d*):(.+)$/uD', $kunci, $m) !== 1 || ! self::sah($m[2])) {
            return null;
        }

        return [(int) $m[1], $m[2]];
    }

    /**
     * Bagian id HTML yang aman untuk kunci regu (nama regu boleh berspasi).
     */
    public static function idAman(string $kunci): string
    {
        return 'r' . substr(md5($kunci), 0, 12);
    }

    /**
     * Nama regu untuk pesan dan tampilan, contoh "Kanga's Escape putra Regu A".
     */
    public static function nama(string $namaNomor, string $regu): string
    {
        return trim($namaNomor . ' Regu ' . $regu);
    }

    public static function labelPeran(?string $peran): string
    {
        return self::PERAN[(string) $peran] ?? '';
    }

    /**
     * Mode nama regu dan daftar nama dari Pengaturan > Pendaftaran.
     *
     * @return array{regu_mode: string, regu_label: list<string>}
     */
    public static function pengaturan(): array
    {
        $mode  = (string) setting('Pendaftaran.nama_regu');
        $label = self::daftarLabel(setting('Pendaftaran.label_regu'));

        return [
            'regu_mode'  => isset(self::MODE[$mode]) ? $mode : self::MODE_KETENTUAN,
            'regu_label' => $label !== [] ? $label : self::daftarLabel(self::LABEL_BAWAAN),
        ];
    }
}
