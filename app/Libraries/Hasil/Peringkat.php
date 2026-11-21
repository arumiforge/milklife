<?php

namespace App\Libraries\Hasil;

/**
 * Peringkat satu babak dari prestasi (F8-04, K124). Tanpa basis data; ada uji unit.
 *
 * - Diurutkan menurut jenis hasil: waktu makin kecil makin baik, jarak dan
 *   poin makin besar makin baik.
 * - Prestasi sama = peringkat sama, peringkat berikutnya dilewati
 *   (1, 2, 2, 4).
 * - Peserta tanpa prestasi atau berketerangan DNS, DNF, atau DQ tidak diberi
 *   peringkat.
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
     * Keterangan yang menghapus peringkat.
     *
     * @var array<string, string> kode => arti
     */
    public const KETERANGAN = [
        'DNS' => 'Tidak start',
        'DNF' => 'Tidak finis',
        'DQ'  => 'Diskualifikasi',
    ];

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

            if ($p['prestasi'] !== null && ! isset(self::KETERANGAN[(string) $p['keterangan']])) {
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
     * Keterangan yang sah, atau `null` untuk kosong dan isian lain.
     */
    public static function keterangan(mixed $nilai): ?string
    {
        $nilai = is_string($nilai) ? strtoupper(trim($nilai)) : '';

        return isset(self::KETERANGAN[$nilai]) ? $nilai : null;
    }
}
