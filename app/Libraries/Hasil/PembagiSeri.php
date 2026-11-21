<?php

namespace App\Libraries\Hasil;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Pembagi seri dan lintasan (F9-08, K140 butir 8), murni tanpa basis data.
 *
 * - **Babak pertama** (`bagiAcak()`): peserta dibagi rata ke seri, peserta
 *   satu sekolah dipisah ke seri berbeda sebisanya, urutan dan lintasan
 *   diacak. Tidak ada unggulan karena pendaftaran tidak membawa prestasi
 *   (asumsi 5 rencana Tahap 9). Lintasan tengah dipakai lebih dulu bila seri
 *   tidak penuh.
 * - **Babak lanjutan** (`bagiUnggulan()`): peserta yang lolos diurutkan dari
 *   prestasi terbaik, dibagi ke seri secara zig-zag, lalu lintasan diundi per
 *   kelompok urutan lintasan unggulan. Bawaan World Athletics untuk 8
 *   lintasan: empat terbaik diundi di lintasan 3–6, dua berikutnya di 7–8,
 *   dua terakhir di 1–2 (`3,4,5,6;7,8;1,2`).
 * - **Tanpa lintasan** (lompat, lempar, poin): nomor urut tampil 1, 2, …
 *   diacak; `tempat` = peserta per kelompok, 0 = semua satu kelompok.
 * - Acak memakai Mt19937 dengan **benih** yang disimpan, jadi pembagian yang
 *   sama bisa diulang persis.
 * - **Lolos** (`lolos()`): Q = n terbaik tiap seri, q = m prestasi terbaik
 *   berikutnya dari semua seri; peringkat sama di batas ikut lolos.
 *
 *     PembagiSeri::bagiAcak([['kunci' => 'a:1', 'sekolah' => 'n20301234'], …], 8, true, 12345);
 *     // ['a:1' => ['seri' => 2, 'lintasan' => 5], …]
 *     PembagiSeri::bagiUnggulan(['a:7', 'a:3', …], 8, true, 12345);
 *     PembagiSeri::lolos($baris, 3, 2, JenisHasil::WAKTU);   // ['a:7' => 'Q', 'a:9' => 'q', …]
 */
final class PembagiSeri
{
    /**
     * Lintasan per seri bawaan untuk nomor lari.
     */
    public const LINTASAN_BAWAAN = 8;

    public const LINTASAN_MAKSIMAL = 12;

    /**
     * Peserta per kelompok paling banyak untuk nomor tanpa lintasan.
     */
    public const KELOMPOK_MAKSIMAL = 99;

    public const SERI_MAKSIMAL = 99;

    /**
     * Urutan lintasan World Athletics untuk 8 lintasan (TR 20.4.4): kelompok
     * undian dipisah titik koma.
     */
    public const URUTAN_WA = '3,4,5,6;7,8;1,2';

    /**
     * Kapasitas babak berikutnya untuk aturan lolos otomatis nomor tanpa
     * lintasan (delapan besar).
     */
    public const KAPASITAS_TANPA_LINTASAN = 8;

    public static function pakaiLintasan(string $jenisHasil): bool
    {
        return $jenisHasil === JenisHasil::WAKTU;
    }

    /**
     * Tempat per seri bawaan: 8 lintasan untuk nomor lari, satu kelompok
     * (0) untuk lainnya.
     */
    public static function tempatBawaan(string $jenisHasil): int
    {
        return self::pakaiLintasan($jenisHasil) ? self::LINTASAN_BAWAAN : 0;
    }

    /**
     * Banyak seri paling sedikit untuk `$peserta` orang.
     */
    public static function jumlahSeri(int $peserta, int $tempat): int
    {
        if ($peserta <= 0) {
            return 0;
        }

        return $tempat <= 0 ? 1 : min(self::SERI_MAKSIMAL, intdiv($peserta + $tempat - 1, $tempat));
    }

    /**
     * Ukuran tiap seri sama rata; seri awal mendapat sisanya.
     *
     * @return list<int>
     */
    public static function ukuranSeri(int $peserta, int $jumlahSeri): array
    {
        if ($peserta <= 0 || $jumlahSeri <= 0) {
            return [];
        }

        $dasar = intdiv($peserta, $jumlahSeri);
        $sisa  = $peserta % $jumlahSeri;
        $hasil = [];

        for ($s = 0; $s < $jumlahSeri; $s++) {
            $hasil[] = $dasar + ($s < $sisa ? 1 : 0);
        }

        return $hasil;
    }

    /**
     * Urutan lintasan bawaan: World Athletics untuk 8 lintasan; selain itu
     * dari tengah ke tepi, sepasang-sepasang.
     */
    public static function urutanBawaan(int $tempat): string
    {
        if ($tempat === 8) {
            return self::URUTAN_WA;
        }

        if ($tempat <= 0) {
            return '';
        }

        $kelompok = [];
        $kiri     = intdiv($tempat + 1, 2);
        $kanan    = $kiri + 1;

        if ($tempat % 2 === 1) {
            $kelompok[] = (string) $kiri;
            $kiri--;
        }

        for (; $kiri >= 1; $kiri--, $kanan++) {
            $kelompok[] = $kiri . ',' . $kanan;
        }

        return implode(';', $kelompok);
    }

    /**
     * Periksa urutan lintasan isian admin: setiap lintasan 1 sampai `$tempat`
     * tepat sekali, dipisah koma, kelompok undian dipisah titik koma.
     *
     * @return string|null pesan galat; `null` bila benar
     */
    public static function periksaUrutan(string $teks, int $tempat): ?string
    {
        $teks = trim($teks);

        if ($teks === '') {
            return null;
        }

        if (preg_match('/^[\d\s,;]+$/D', $teks) !== 1) {
            return 'Urutan lintasan hanya boleh berisi angka, koma, dan titik koma. Contoh: ' . self::urutanBawaan($tempat) . '.';
        }

        $lintasan = array_map('intval', preg_split('/[\s,;]+/', $teks, -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $harus    = range(1, max(1, $tempat));
        $urut     = $lintasan;
        sort($urut);

        if ($urut !== $harus) {
            return 'Urutan lintasan harus memuat lintasan 1 sampai ' . $tempat . ', masing-masing tepat sekali. Contoh: ' . self::urutanBawaan($tempat) . '.';
        }

        return null;
    }

    /**
     * Urai urutan lintasan menjadi kelompok undian. Isian kosong atau salah
     * memakai bawaan.
     *
     * @return list<list<int>>
     */
    public static function kelompokLintasan(?string $teks, int $tempat): array
    {
        $teks = trim((string) $teks);

        if ($teks === '' || self::periksaUrutan($teks, $tempat) !== null) {
            $teks = self::urutanBawaan($tempat);
        }

        $kelompok = [];

        foreach (explode(';', $teks) as $bagian) {
            $lintasan = array_map('intval', preg_split('/[\s,]+/', $bagian, -1, PREG_SPLIT_NO_EMPTY) ?: []);

            if ($lintasan !== []) {
                $kelompok[] = $lintasan;
            }
        }

        return $kelompok;
    }

    /**
     * Rapikan isian urutan lintasan untuk disimpan: `3, 4 ,5;7,8` → `3,4,5;7,8`.
     */
    public static function rapikanUrutan(string $teks): string
    {
        $kelompok = [];

        foreach (explode(';', $teks) as $bagian) {
            $lintasan = preg_split('/[\s,]+/', $bagian, -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if ($lintasan !== []) {
                $kelompok[] = implode(',', array_map('intval', $lintasan));
            }
        }

        return implode(';', $kelompok);
    }

    /**
     * Babak pertama: bagi rata, sekolah sama dipisah sebisanya, urutan acak.
     *
     * @param list<array{kunci: string, sekolah: string}> $peserta    `sekolah` = kunci sekolah (NPSN atau pendaftaran)
     * @param int                                         $tempat     lintasan per seri, atau peserta per kelompok (0 = satu kelompok)
     * @param int|null                                    $jumlahSeri `null` = paling sedikit yang muat
     *
     * @return array<string, array{seri: int, lintasan: int}> kunci => seri dan lintasan (atau urutan tampil)
     */
    public static function bagiAcak(array $peserta, int $tempat, bool $lintasan, int $benih, ?int $jumlahSeri = null, ?string $urutan = null): array
    {
        $n = count($peserta);

        if ($n === 0) {
            return [];
        }

        $acak       = new Randomizer(new Mt19937($benih));
        $jumlahSeri = self::seriSah($n, $tempat, $jumlahSeri);
        $sisa       = self::ukuranSeri($n, $jumlahSeri);

        // Kelompokkan per sekolah setelah diacak; sekolah dengan peserta
        // terbanyak dibagi lebih dulu supaya bisa tersebar ke semua seri.
        $sekolah = [];

        foreach ($acak->shuffleArray($peserta) as $p) {
            $sekolah[$p['sekolah']][] = $p['kunci'];
        }

        $urutSekolah = array_keys($sekolah);
        $posisi      = array_flip($urutSekolah);
        usort($urutSekolah, static fn (string $a, string $b): int => [count($sekolah[$b]), $posisi[$a]] <=> [count($sekolah[$a]), $posisi[$b]]);

        $isiSekolah = [];
        $seri       = array_fill(0, $jumlahSeri, []);

        foreach ($urutSekolah as $s) {
            foreach ($sekolah[$s] as $kunci) {
                $pilih = null;

                for ($i = 0; $i < $jumlahSeri; $i++) {
                    if ($sisa[$i] <= 0) {
                        continue;
                    }

                    $nilai = [$isiSekolah[$i][$s] ?? 0, -$sisa[$i], $i];

                    if ($pilih === null || $nilai < $pilih) {
                        $pilih = $nilai;
                    }
                }

                $i                  = (int) $pilih[2];
                $seri[$i][]         = $kunci;
                $isiSekolah[$i][$s] = ($isiSekolah[$i][$s] ?? 0) + 1;
                $sisa[$i]--;
            }
        }

        $hasil = [];

        foreach ($seri as $i => $anggota) {
            $anggota = $acak->shuffleArray($anggota);
            $tempatS = $lintasan ? self::lintasanTerpakai(count($anggota), $tempat, $urutan) : range(1, count($anggota));
            $tempatS = $acak->shuffleArray($tempatS);

            foreach ($anggota as $j => $kunci) {
                $hasil[$kunci] = ['seri' => $i + 1, 'lintasan' => $tempatS[$j]];
            }
        }

        return $hasil;
    }

    /**
     * Babak lanjutan: unggulan dari prestasi babak sebelumnya. Seri diisi
     * zig-zag (1, 2, …, n, n, …, 1); di tiap seri peserta terbaik mendapat
     * kelompok lintasan pertama (diundi di dalam kelompok), dan seterusnya.
     * Nomor tanpa lintasan: urutan tampil diacak.
     *
     * @param list<string> $peringkat kunci peserta, urut prestasi terbaik
     *
     * @return array<string, array{seri: int, lintasan: int}>
     */
    public static function bagiUnggulan(array $peringkat, int $tempat, bool $lintasan, int $benih, ?int $jumlahSeri = null, ?string $urutan = null): array
    {
        $n = count($peringkat);

        if ($n === 0) {
            return [];
        }

        $acak       = new Randomizer(new Mt19937($benih));
        $jumlahSeri = self::seriSah($n, $tempat, $jumlahSeri);
        $sisa       = self::ukuranSeri($n, $jumlahSeri);
        $seri       = array_fill(0, $jumlahSeri, []);
        $i          = 0;
        $arah       = 1;

        foreach ($peringkat as $kunci) {
            // Lewati seri yang sudah penuh, tetap zig-zag.
            for ($coba = 0; $coba < 2 * $jumlahSeri && $sisa[$i] <= 0; $coba++) {
                [$i, $arah] = self::langkahZigzag($i, $arah, $jumlahSeri);
            }

            $seri[$i][] = $kunci;
            $sisa[$i]--;
            [$i, $arah] = self::langkahZigzag($i, $arah, $jumlahSeri);
        }

        $hasil = [];

        foreach ($seri as $s => $anggota) {
            if (! $lintasan) {
                foreach ($acak->shuffleArray(range(1, count($anggota))) as $j => $nomor) {
                    $hasil[$anggota[$j]] = ['seri' => $s + 1, 'lintasan' => $nomor];
                }

                continue;
            }

            $j = 0;

            foreach (self::kelompokLintasan($urutan, $tempat) as $kelompok) {
                if ($j >= count($anggota)) {
                    break;
                }

                $undian = $acak->shuffleArray($kelompok);

                foreach ($undian as $lajur) {
                    if ($j >= count($anggota)) {
                        break;
                    }

                    $hasil[$anggota[$j]] = ['seri' => $s + 1, 'lintasan' => $lajur];
                    $j++;
                }
            }
        }

        return $hasil;
    }

    /**
     * Yang lolos ke babak berikutnya: Q = `$q` terbaik tiap seri, lalu q =
     * `$qKecil` prestasi terbaik berikutnya dari semua seri. Peserta tanpa
     * prestasi atau ber-DNS/DNF/DQ tidak lolos. Peringkat sama di batas ikut lolos;
     * prestasi sama dipisah dulu dengan `pemecah` bila ada (F9-09).
     *
     * @param list<array{kunci: string, seri: int|null, prestasi: int|null, keterangan: string|null, pemecah?: list<int>|null}> $baris
     *
     * @return array<string, 'Q'|'q'> kunci => tanda, urut prestasi terbaik
     */
    public static function lolos(array $baris, int $q, int $qKecil, string $jenis): array
    {
        $perSeri  = [];
        $prestasi = [];
        $pemecah  = [];

        foreach ($baris as $b) {
            $perSeri[(int) $b['seri']][$b['kunci']] = ['prestasi' => $b['prestasi'], 'keterangan' => $b['keterangan'], 'pemecah' => $b['pemecah'] ?? null];

            if ($b['prestasi'] !== null && Peringkat::keterangan($b['keterangan']) === null) {
                $prestasi[$b['kunci']] = $b['prestasi'];
                $pemecah[$b['kunci']]  = $b['pemecah'] ?? null;
            }
        }

        $tanda = [];

        foreach ($perSeri as $peserta) {
            foreach (Peringkat::hitung($peserta, $jenis) as $kunci => $peringkat) {
                if ($peringkat !== null && $peringkat <= $q) {
                    $tanda[(string) $kunci] = 'Q';
                }
            }
        }

        $sisa = [];

        foreach ($prestasi as $kunci => $nilai) {
            if (! isset($tanda[$kunci])) {
                $sisa[$kunci] = ['prestasi' => $nilai, 'keterangan' => null, 'pemecah' => $pemecah[$kunci]];
            }
        }

        foreach (Peringkat::hitung($sisa, $jenis) as $kunci => $peringkat) {
            if ($peringkat !== null && $peringkat <= $qKecil) {
                $tanda[(string) $kunci] = 'q';
            }
        }

        $besar = JenisHasil::besarLebihBaik($jenis);
        uksort($tanda, static fn (string $a, string $b): int => ($besar ? [$prestasi[$b], $a] <=> [$prestasi[$a], $b] : [$prestasi[$a], $a] <=> [$prestasi[$b], $b]));

        return $tanda;
    }

    /**
     * Aturan lolos otomatis supaya babak berikutnya terisi penuh: Q = bagian
     * rata tiap seri, q = sisanya.
     *
     * @return array{q: int, q_kecil: int}
     */
    public static function aturanOtomatis(int $seriAsal, int $kapasitasBerikut): array
    {
        if ($seriAsal <= 0 || $kapasitasBerikut <= 0) {
            return ['q' => 0, 'q_kecil' => 0];
        }

        $q = intdiv($kapasitasBerikut, $seriAsal);

        return ['q' => $q, 'q_kecil' => $kapasitasBerikut - $q * $seriAsal];
    }

    /**
     * Benih acak baru untuk pembagian.
     */
    public static function benihBaru(): int
    {
        return random_int(1, 2_147_483_647);
    }

    /**
     * Banyak seri yang dipakai: isian admin bila muat, selain itu paling sedikit.
     */
    private static function seriSah(int $peserta, int $tempat, ?int $diminta): int
    {
        $paling = self::jumlahSeri($peserta, $tempat);

        if ($diminta === null || $diminta < $paling) {
            return $paling;
        }

        // Lebih banyak seri boleh, tetapi tidak ada seri yang kosong.
        return min($diminta, $peserta, self::SERI_MAKSIMAL);
    }

    /**
     * Lintasan yang dipakai seri berisi `$jumlah` peserta: urut kelompok
     * unggulan, jadi lintasan tengah lebih dulu.
     *
     * @return list<int>
     */
    private static function lintasanTerpakai(int $jumlah, int $tempat, ?string $urutan): array
    {
        return array_slice(array_merge(...self::kelompokLintasan($urutan, $tempat)), 0, $jumlah);
    }

    /**
     * @return array{0: int, 1: int} seri berikut dan arahnya
     */
    private static function langkahZigzag(int $i, int $arah, int $jumlah): array
    {
        if ($jumlah <= 1) {
            return [0, 1];
        }

        $berikut = $i + $arah;

        if ($berikut >= $jumlah || $berikut < 0) {
            return [$i, -$arah];
        }

        return [$berikut, $arah];
    }
}
