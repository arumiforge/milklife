<?php

namespace App\Libraries\Hasil;

/**
 * Peringkat satu babak dari prestasi (F8-04, K124). Tanpa basis data; ada uji unit.
 *
 * - Diurutkan menurut jenis hasil: waktu makin kecil makin baik, jarak dan
 *   poin makin besar makin baik.
 * - Prestasi sama = peringkat sama, peringkat berikutnya dilewati
 *   (1, 2, 2, 4).
 * - Peserta tanpa prestasi atau berketerangan DNS, DNF, DQ, atau NM tidak
 *   diberi peringkat. Keterangan r (mundur, F11-05, CR 25) tidak menghapus
 *   peringkat: prestasi sebelum mundur tetap dinilai.
 * - Peringkat yang dikoreksi admin (`manual`) tidak dihitung ulang; peserta
 *   lain tetap diurutkan dari prestasinya.
 * - Pemecah seri (F9-09, K140 butir 7c): peserta berprestasi sama dipisah
 *   dengan `pemecah` (`pemecah()`): jarak terbaik kedua, ketiga, dst. untuk
 *   nomor lompat/lempar, atau waktu seperseribu detik untuk lari. Bila ada
 *   satu saja di antara yang sama yang `pemecah`-nya kosong (data lama, atau
 *   seperseribu tidak diisi), semuanya tetap berperingkat sama, jadi data
 *   lama berperingkat persis seperti sebelumnya.
 *
 *     Peringkat::hitung([
 *         10 => ['prestasi' => 1234, 'keterangan' => null],
 *         11 => ['prestasi' => 1210, 'keterangan' => null],
 *         12 => ['prestasi' => null, 'keterangan' => 'DNS'],
 *     ], 'waktu');
 *     // [11 => 1, 10 => 2, 12 => null]
 */
final class Peringkat
{
    /**
     * Keterangan hasil. Semua menghapus peringkat kecuali `TETAP_DINILAI`.
     * NM dan r (F11-05, CR 25) hanya untuk nomor lompat dan lempar.
     *
     * @var array<string, string> kode => arti
     */
    public const KETERANGAN = [
        'DNS' => 'Tidak start',
        'DNF' => 'Tidak finis',
        'DQ'  => 'Diskualifikasi',
        'NM'  => 'Tanpa lompatan atau lemparan sah',
        'r'   => 'Mundur',
    ];

    /**
     * Keterangan yang tetap diberi peringkat dari prestasinya (F11-05).
     */
    public const TETAP_DINILAI = ['r'];

    /**
     * Keterangan khusus nomor lompat dan lempar (jenis hasil jarak, F11-05).
     */
    public const HANYA_JARAK = ['NM', 'r'];

    /**
     * Panjang paling banyak pasal diskualifikasi, misalnya `TR16.8` (F11-05).
     */
    public const PANJANG_PASAL = 12;

    /**
     * @param array<int|string, array{prestasi: int|null, keterangan: string|null, peringkat?: int|null, manual?: bool, pemecah?: list<int>|null}> $peserta kunci => data
     *
     * @return array<int|string, int|null> kunci => peringkat, urut dari peringkat terbaik; tanpa peringkat di akhir
     */
    public static function hitung(array $peserta, string $jenis): array
    {
        $dinilai = [];
        $pemecah = [];
        $hasil   = [];

        foreach ($peserta as $kunci => $p) {
            if (! empty($p['manual'])) {
                $hasil[$kunci] = $p['peringkat'] ?? null;

                continue;
            }

            if ($p['prestasi'] !== null && ! self::tanpaPeringkat($p['keterangan'])) {
                $dinilai[$kunci] = (int) $p['prestasi'];
                $pemecah[$kunci] = $p['pemecah'] ?? null;
            }
        }

        $besar = JenisHasil::besarLebihBaik($jenis);
        $besar ? arsort($dinilai) : asort($dinilai);

        // Kelompok berprestasi sama, berurutan dari yang terbaik.
        $kelompok = [];

        foreach ($dinilai as $kunci => $nilai) {
            $kelompok[$nilai][] = $kunci;
        }

        $urut = 0;

        foreach ($kelompok as $anggota) {
            $lengkap = count($anggota) > 1 && array_filter($anggota, static fn (int|string $k): bool => $pemecah[$k] === null) === [];

            if ($lengkap) {
                usort($anggota, static fn (int|string $a, int|string $b): int => self::bandingkanPemecah($pemecah[$a], $pemecah[$b], $besar));
            }

            $posisi  = $urut + 1;
            $sebelum = null;

            foreach ($anggota as $i => $kunci) {
                if ($lengkap && $sebelum !== null && self::bandingkanPemecah($pemecah[$sebelum], $pemecah[$kunci], $besar) !== 0) {
                    $posisi = $urut + 1 + $i;
                }

                $hasil[$kunci] = $posisi;
                $sebelum       = $kunci;
            }

            $urut += count($anggota);
        }

        foreach ($peserta as $kunci => $p) {
            $hasil[$kunci] ??= null;
        }

        uasort($hasil, static fn (?int $a, ?int $b): int => ($a ?? PHP_INT_MAX) <=> ($b ?? PHP_INT_MAX));

        return $hasil;
    }

    /**
     * Pemecah seri satu peserta untuk `hitung()`, atau `null` bila tidak ada.
     *
     * - Jarak: jarak sah kedua, ketiga, dst. dari `percobaan` (sudah urut
     *   dari yang terjauh), bila percobaannya dicatat dan `$aktif`.
     *   Percobaan dicatat tetapi hanya satu yang sah = `[]` (kalah dari yang
     *   punya jarak sah kedua).
     * - Waktu: `[seperseribu]` bila diisi.
     * - Poin dan poin multi-lomba: tidak ada.
     *
     * @param list<int|string|null>|null $percobaan
     *
     * @return list<int>|null
     */
    public static function pemecah(string $jenis, ?array $percobaan, ?int $seperseribu, bool $aktif = true): ?array
    {
        if ($jenis === JenisHasil::JARAK) {
            return $aktif && $percobaan !== null ? array_slice(Percobaan::sah($percobaan), 1) : null;
        }

        if ($jenis === JenisHasil::WAKTU) {
            return $seperseribu === null ? null : [$seperseribu];
        }

        return null;
    }

    /**
     * Bandingkan dua pemecah: negatif bila `$a` lebih baik.
     *
     * @param list<int> $a
     * @param list<int> $b
     */
    private static function bandingkanPemecah(array $a, array $b, bool $besar): int
    {
        // Tidak punya jarak sah ke-n kalah dari yang punya.
        $kosong = $besar ? 0 : PHP_INT_MAX;

        for ($i = 0, $n = max(count($a), count($b)); $i < $n; $i++) {
            $x = $a[$i] ?? $kosong;
            $y = $b[$i] ?? $kosong;

            if ($x !== $y) {
                return $besar ? $y <=> $x : $x <=> $y;
            }
        }

        return 0;
    }

    /**
     * Keterangan yang sah (huruf besar-kecil tidak dibedakan, ditulis sesuai
     * `KETERANGAN`), atau `null` untuk kosong dan isian lain.
     */
    public static function keterangan(mixed $nilai): ?string
    {
        $nilai = is_string($nilai) ? strtoupper(trim($nilai)) : '';

        foreach (array_keys(self::KETERANGAN) as $kode) {
            if (strtoupper($kode) === $nilai) {
                return $kode;
            }
        }

        return null;
    }

    /**
     * Keterangan ini menghapus peringkat? (DNS, DNF, DQ, NM; bukan r atau kosong)
     */
    public static function tanpaPeringkat(?string $keterangan): bool
    {
        $kode = self::keterangan($keterangan);

        return $kode !== null && ! in_array($kode, self::TETAP_DINILAI, true);
    }

    /**
     * Pasal diskualifikasi yang dirapikan (spasi ganda dibuang), `null` bila
     * kosong, atau `false` bila terlalu panjang atau memuat tanda lain selain
     * huruf, angka, titik, koma, garis miring, tanda hubung, dan kurung.
     */
    public static function pasal(mixed $nilai): string|false|null
    {
        $teks = is_string($nilai) ? trim((string) preg_replace('/\s+/u', ' ', $nilai)) : '';

        if ($teks === '') {
            return null;
        }

        return mb_strlen($teks) <= self::PANJANG_PASAL && preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .,\/()\-]*$/uD', $teks) === 1 ? $teks : false;
    }

    /**
     * Pilihan keterangan untuk isian sesuai jenis hasil nomor: NM dan r hanya
     * untuk nomor jarak (F11-05).
     *
     * @return array<string, string> kode => arti
     */
    public static function pilihan(string $jenis): array
    {
        return $jenis === JenisHasil::JARAK ? self::KETERANGAN : array_diff_key(self::KETERANGAN, array_flip(self::HANYA_JARAK));
    }

    /**
     * Arti kode keterangan untuk keterangan halaman dan PDF hasil satu nomor.
     * NM dan r hanya disebut di nomor jarak (F11-05).
     */
    public static function legenda(string $jenis): string
    {
        return 'DNS = tidak start, DNF = tidak finis, DQ = diskualifikasi.'
            . ($jenis === JenisHasil::JARAK ? ' NM = tanpa lompatan atau lemparan sah, r = mundur (prestasi sebelum mundur tetap dinilai).' : '');
    }

    /**
     * Teks keterangan untuk tampilan: kode, ditambah pasal untuk DQ, misalnya
     * `DQ (TR16.8)`. Kosong bila tanpa keterangan.
     */
    public static function teks(?string $keterangan, ?string $pasal = null): string
    {
        if ($keterangan === null || $keterangan === '') {
            return '';
        }

        return $keterangan === 'DQ' && $pasal !== null && $pasal !== '' ? 'DQ (' . $pasal . ')' : $keterangan;
    }
}
