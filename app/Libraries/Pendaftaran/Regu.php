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
 *   membatasi regu per sekolah, bersama `regu_maksimal` nomor lomba. Daftar
 *   kosong (bawaan) berarti huruf A, B, C, … berurutan tanpa batas selain
 *   `regu_maksimal` (K107).
 * - **Bebas** (`bebas`): official mengetik nama regu sendiri; hanya
 *   `regu_maksimal` nomor lomba yang membatasi banyaknya regu.
 *
 * Kata di depan nama regu ("Regu", "Tim", …) juga diatur admin (`awalan()`,
 * K107). Nama regu selalu dirapikan dengan `rapikan()` (spasi, kata "Regu"
 * atau awalan di depan dibuang, huruf awal tiap kata kapital), supaya "merah"
 * dan "MERAH" adalah regu yang sama. Kunci regu dalam satu sekolah adalah
 * "nomor:nama".
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
     * Daftar nama regu bawaan mode Ketentuan admin: kosong, yaitu huruf
     * A, B, C, … tanpa batas selain batas regu nomor lomba (K107).
     */
    public const LABEL_BAWAAN = '';

    /**
     * Kata di depan nama regu bawaan, contoh "Regu A" (K107).
     */
    public const AWALAN_BAWAAN = 'Regu';

    public const AWALAN_PANJANG_MAKSIMAL = 15;

    /**
     * Regu paling banyak per sekolah per nomor, juga banyaknya nama di daftar admin.
     */
    public const PALING_BANYAK = 26;

    public const PANJANG_MAKSIMAL = 30;

    /**
     * Nama regu yang dirapikan: spasi berderet menjadi satu, kata "Regu" atau
     * awalan dari pengaturan (misalnya "Tim") di depan dibuang, huruf awal
     * tiap kata kapital dan sisanya kecil.
     */
    public static function rapikan(mixed $nama): string
    {
        $teks   = is_string($nama) || is_int($nama) ? (string) $nama : '';
        $teks   = trim((string) preg_replace('/\s+/u', ' ', $teks));
        $teks   = trim((string) preg_replace('/^regu(\s+|$)/iu', '', $teks));
        $awalan = self::awalan();

        if (strcasecmp($awalan, self::AWALAN_BAWAAN) !== 0) {
            $teks = trim((string) preg_replace('/^' . preg_quote($awalan, '/') . '\s+/iu', '', $teks));
        }

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
     * Nama regu untuk pesan dan tampilan, contoh "Kanga's Escape putra Regu A"
     * (atau "… Tim A" bila awalannya "Tim").
     */
    public static function nama(string $namaNomor, string $regu): string
    {
        return trim($namaNomor . ' ' . self::label($regu));
    }

    /**
     * Nama regu dengan awalannya saja, contoh "Regu A" atau "Tim A".
     */
    public static function label(string $regu): string
    {
        return trim(self::awalan() . ' ' . $regu);
    }

    /**
     * Kata di depan nama regu dari Pengaturan > Pendaftaran (K107), bawaan
     * "Regu". Bila pengaturan tidak bisa dibaca (uji unit tanpa basis data),
     * memakai bawaan.
     */
    public static function awalan(): string
    {
        try {
            $awalan = self::rapikanAwalan(setting('Pendaftaran.awalan_regu'));
        } catch (\Throwable) {
            $awalan = '';
        }

        return self::awalanSah($awalan) ? $awalan : self::AWALAN_BAWAAN;
    }

    /**
     * Awalan yang dirapikan: spasi di tepi dibuang dan spasi berderet menjadi satu.
     * Huruf besar kecil dibiarkan seperti ditulis admin ("Tim", "TIM").
     */
    public static function rapikanAwalan(mixed $teks): string
    {
        return is_string($teks) ? trim((string) preg_replace('/\s+/u', ' ', $teks)) : '';
    }

    /**
     * Awalan boleh dipakai: 2–15 huruf, angka, spasi, atau titik, diawali huruf.
     */
    public static function awalanSah(mixed $awalan): bool
    {
        return is_string($awalan)
            && mb_strlen($awalan) <= self::AWALAN_PANJANG_MAKSIMAL
            && preg_match('/^\p{L}[\p{L}\p{N} .]+$/uD', $awalan) === 1;
    }

    public static function labelPeran(?string $peran): string
    {
        return self::PERAN[(string) $peran] ?? '';
    }

    /**
     * Mode nama regu, daftar nama, dan awalan dari Pengaturan > Pendaftaran.
     * Daftar kosong berarti huruf A, B, C, … (K107).
     *
     * @return array{regu_mode: string, regu_label: list<string>, regu_awalan: string}
     */
    public static function pengaturan(): array
    {
        $mode = (string) setting('Pendaftaran.nama_regu');

        return [
            'regu_mode'   => isset(self::MODE[$mode]) ? $mode : self::MODE_KETENTUAN,
            'regu_label'  => self::daftarLabel(setting('Pendaftaran.label_regu')),
            'regu_awalan' => self::awalan(),
        ];
    }
}
