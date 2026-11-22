<?php

namespace App\Libraries\Hasil;

/**
 * Kalkulator poin multi-lomba (F9-10, K140 butir 7b). Tanpa basis data; ada uji unit.
 *
 * Tabel skor = daftar baris `[prestasi, poin]` per komponen dan kategori
 * (putra/putri). Prestasi memakai satuan `hasil.prestasi`: perseratus detik
 * untuk komponen lari, sentimeter untuk lompat, tolak, dan lempar.
 *
 * Aturan pencarian poin (kebiasaan tabel IAAF/PASI dan World Athletics):
 * - Prestasi yang sama dengan satu baris mendapat poin baris itu.
 * - Prestasi di antara dua baris memakai **baris yang lebih buruk** (waktu
 *   lebih lambat, jarak lebih pendek), jadi poin tidak pernah dibulatkan ke atas.
 * - Prestasi lebih buruk dari baris terakhir = 0 poin, dengan peringatan.
 * - Prestasi lebih baik dari baris teratas juga di luar tabel: 0 poin dengan
 *   peringatan, supaya salah ketik (misalnya 0,70 untuk 7,00) tidak lolos.
 *
 *     TabelSkor::cariPoin([[546, 1500], [700, 882], [701, 879]], 700, 'waktu');
 *     // ['poin' => 882, 'luar' => null]
 *     TabelSkor::cariPoin([[546, 1500], [700, 882], [710, 847]], 705, 'waktu');
 *     // ['poin' => 847, 'luar' => null]        (di antara 7,00 dan 7,10 → baris 7,10)
 *     TabelSkor::cariPoin([[546, 1500], [1106, 1]], 1200, 'waktu');
 *     // ['poin' => 0, 'luar' => 'bawah']
 *
 *     TabelSkor::hitung($komponen, $baris, ['lari-60' => '7,00', 'lompat-jauh' => '4,10', 'tolak-peluru' => '']);
 *     // ['rincian' => ['lari-60' => ['prestasi' => 700, 'poin' => 882, 'luar' => null], …], 'total' => …, 'galat' => []]
 */
final class TabelSkor
{
    public const KATEGORI = ['putra' => 'Putra', 'putri' => 'Putri'];

    /**
     * Jenis prestasi komponen: hanya waktu dan jarak.
     *
     * @var array<string, string>
     */
    public const JENIS_KOMPONEN = [
        JenisHasil::WAKTU => 'Waktu (lari)',
        JenisHasil::JARAK => 'Jarak (lompat, tolak, lempar)',
    ];

    public const POIN_MAKSIMAL         = 9999;
    public const NAMA_MAKSIMAL         = 100;
    public const NAMA_KOMPONEN_MAKSIMAL = 40;
    public const KOMPONEN_MAKSIMAL     = 10;

    /**
     * Penjelasan "di luar tabel" untuk pesan.
     *
     * @var array<string, string>
     */
    public const LUAR = [
        'atas'  => 'lebih baik dari baris teratas tabel skor',
        'bawah' => 'lebih buruk dari baris terakhir tabel skor',
    ];

    /**
     * Poin satu prestasi.
     *
     * @param list<array{0: int, 1: int}> $baris    [prestasi, poin], urutan bebas
     * @param string                      $jenis    `waktu` atau `jarak`
     *
     * @return array{poin: int, luar: string|null} `luar`: `atas`, `bawah`, atau null
     */
    public static function cariPoin(array $baris, int $prestasi, string $jenis): array
    {
        if ($baris === []) {
            return ['poin' => 0, 'luar' => 'bawah'];
        }

        $waktu = $jenis === JenisHasil::WAKTU;

        // Urut dari yang paling buruk: waktu terbesar dulu, jarak terkecil dulu.
        usort($baris, static fn (array $a, array $b): int => $waktu ? $b[0] <=> $a[0] : $a[0] <=> $b[0]);

        $lebihBaik = static fn (int $x, int $y): bool => $waktu ? $x < $y : $x > $y;   // x lebih baik dari y

        $terburuk = $baris[0][0];
        $terbaik  = $baris[count($baris) - 1][0];

        if ($lebihBaik($terburuk, $prestasi)) {
            return ['poin' => 0, 'luar' => 'bawah'];
        }

        if ($lebihBaik($prestasi, $terbaik)) {
            return ['poin' => 0, 'luar' => 'atas'];
        }

        // Cari baris terakhir (dari yang terburuk) yang tidak lebih baik dari prestasi.
        $kiri  = 0;
        $kanan = count($baris) - 1;

        while ($kiri < $kanan) {
            $tengah = intdiv($kiri + $kanan + 1, 2);

            if ($lebihBaik($baris[$tengah][0], $prestasi)) {
                $kanan = $tengah - 1;
            } else {
                $kiri = $tengah;
            }
        }

        return ['poin' => (int) $baris[$kiri][1], 'luar' => null];
    }

    /**
     * Hitung poin semua komponen satu peserta dari isian teks.
     *
     * - Isian kosong = komponen belum diisi (0 poin, `prestasi` null).
     * - Isian salah = galat per komponen; tidak ada poin yang dihitung.
     * - Total = jumlah poin komponen; null bila semua komponen kosong.
     *
     * @param list<array{kode: string, nama: string, jenis: string}> $komponen
     * @param array<string, list<array{0: int, 1: int}>>             $baris  kode komponen => baris tabel untuk kategori peserta
     * @param array<string, mixed>                                   $isian  kode komponen => teks prestasi
     *
     * @return array{rincian: array<string, array{prestasi: int|null, poin: int, luar: string|null}>, total: int|null, galat: array<string, string>}
     */
    public static function hitung(array $komponen, array $baris, array $isian): array
    {
        $rincian = [];
        $galat   = [];
        $total   = null;

        foreach ($komponen as $k) {
            $teks   = is_scalar($isian[$k['kode']] ?? null) ? (string) $isian[$k['kode']] : '';
            $urai   = Prestasi::urai($teks, $k['jenis']);

            if ($urai['galat'] !== null) {
                $galat[$k['kode']] = $k['nama'] . ': ' . $urai['galat'];

                continue;
            }

            if ($urai['nilai'] === null) {
                $rincian[$k['kode']] = ['prestasi' => null, 'poin' => 0, 'luar' => null];

                continue;
            }

            $cari                = self::cariPoin($baris[$k['kode']] ?? [], $urai['nilai'], $k['jenis']);
            $rincian[$k['kode']] = ['prestasi' => $urai['nilai'], 'poin' => $cari['poin'], 'luar' => $cari['luar']];
            $total               = ($total ?? 0) + $cari['poin'];
        }

        return ['rincian' => $galat === [] ? $rincian : [], 'total' => $galat === [] ? $total : null, 'galat' => $galat];
    }

    /**
     * Hitung ulang poin dari rincian yang tersimpan (setelah baris tabel diubah).
     *
     * @param list<array{kode: string, nama: string, jenis: string}> $komponen
     * @param array<string, list<array{0: int, 1: int}>>             $baris
     * @param array<string, mixed>                                   $rincian hasil `bacaRincian()`
     *
     * @return array{rincian: array<string, array{prestasi: int|null, poin: int, luar: string|null}>, total: int|null}
     */
    public static function hitungUlang(array $komponen, array $baris, array $rincian): array
    {
        $baru  = [];
        $total = null;

        foreach ($komponen as $k) {
            $prestasi = $rincian[$k['kode']]['prestasi'] ?? null;

            if (! is_int($prestasi)) {
                $baru[$k['kode']] = ['prestasi' => null, 'poin' => 0, 'luar' => null];

                continue;
            }

            $cari             = self::cariPoin($baris[$k['kode']] ?? [], $prestasi, $k['jenis']);
            $baru[$k['kode']] = ['prestasi' => $prestasi, 'poin' => $cari['poin'], 'luar' => $cari['luar']];
            $total            = ($total ?? 0) + $cari['poin'];
        }

        return ['rincian' => $baru, 'total' => $total];
    }

    /**
     * Rincian dari kolom `hasil.rincian` (JSON). Isi yang rusak diabaikan.
     *
     * @return array<string, array{prestasi: int|null, poin: int, luar: string|null}>
     */
    public static function bacaRincian(mixed $json): array
    {
        $data = is_string($json) && $json !== '' ? json_decode($json, true) : null;

        if (! is_array($data)) {
            return [];
        }

        $rincian = [];

        foreach ($data as $kode => $r) {
            if (is_string($kode) && is_array($r)) {
                $rincian[$kode] = [
                    'prestasi' => is_int($r['prestasi'] ?? null) ? $r['prestasi'] : null,
                    'poin'     => is_int($r['poin'] ?? null) ? $r['poin'] : 0,
                    'luar'     => isset(self::LUAR[$r['luar'] ?? '']) ? $r['luar'] : null,
                ];
            }
        }

        return $rincian;
    }

    /**
     * @param array<string, array{prestasi: int|null, poin: int, luar: string|null}> $rincian
     */
    public static function tulisRincian(array $rincian): ?string
    {
        return $rincian === [] ? null : json_encode($rincian, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }

    /**
     * Daftar komponen dari kolom `tabel_skor.komponen` (JSON). Isi yang rusak diabaikan.
     *
     * @return list<array{kode: string, nama: string, jenis: string}>
     */
    public static function bacaKomponen(mixed $json): array
    {
        $data = is_string($json) ? json_decode($json, true) : $json;

        if (! is_array($data)) {
            return [];
        }

        $komponen = [];

        foreach ($data as $k) {
            if (is_array($k) && self::kodeSah($k['kode'] ?? null) && is_string($k['nama'] ?? null) && isset(self::JENIS_KOMPONEN[$k['jenis'] ?? ''])) {
                $komponen[] = ['kode' => $k['kode'], 'nama' => $k['nama'], 'jenis' => $k['jenis']];
            }
        }

        return $komponen;
    }

    public static function kodeSah(mixed $kode): bool
    {
        return is_string($kode) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $kode) === 1 && strlen($kode) <= 30;
    }

    /**
     * Kode dari nama: "Lari 60 m" → "lari-60-m".
     */
    public static function kodeDariNama(string $nama): string
    {
        $kode = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-');

        return substr($kode === '' ? 'komponen' : $kode, 0, 30);
    }

    /**
     * Teks singkat rincian untuk tabel hasil: "Lari 60 m 7,00 (882) · Lompat Jauh 4,10 (…)".
     *
     * @param list<array{kode: string, nama: string, jenis: string}>                 $komponen
     * @param array<string, array{prestasi: int|null, poin: int, luar: string|null}> $rincian
     *
     * @return list<array{nama: string, prestasi: string, poin: int, luar: string|null}>
     */
    public static function rincianTampil(array $komponen, array $rincian): array
    {
        $tampil = [];

        foreach ($komponen as $k) {
            $r        = $rincian[$k['kode']] ?? ['prestasi' => null, 'poin' => 0, 'luar' => null];
            $tampil[] = [
                'nama'     => $k['nama'],
                'prestasi' => Prestasi::tulis($r['prestasi'], $k['jenis']),
                'poin'     => $r['poin'],
                'luar'     => $r['luar'],
            ];
        }

        return $tampil;
    }

    /**
     * Baris tabel dari sel Excel (dua kolom: prestasi, poin). Baris pertama
     * yang bukan angka (judul kolom) dan baris kosong dilewati.
     *
     * Prestasi boleh ditulis seperti di formulir hasil (7,00; 2:05,10; 4,25)
     * atau angka Excel. Waktu yang dibaca Excel sebagai jam (pecahan hari,
     * misalnya 2:05,10) diubah ke detik.
     *
     * @param list<list<mixed>> $sel
     *
     * @return array{baris: list<array{0: int, 1: int}>, galat: list<string>}
     */
    public static function bacaSel(array $sel, string $jenis): array
    {
        $baris = [];
        $galat = [];
        $ada   = [];

        foreach ($sel as $i => $s) {
            $nomor    = $i + 1;
            $prestasi = $s[0] ?? null;
            $poin     = $s[1] ?? null;

            if (($prestasi === null || $prestasi === '') && ($poin === null || $poin === '')) {
                continue;
            }

            if ($jenis === JenisHasil::WAKTU && is_float($prestasi) && $prestasi > 0 && $prestasi < 1) {
                $prestasi = number_format($prestasi * 86400, 2, '.', '');
            } elseif (is_float($prestasi) || is_int($prestasi)) {
                $prestasi = number_format((float) $prestasi, 2, '.', '');
            }

            $urai     = self::uraiPrestasiTabel(is_scalar($prestasi) ? (string) $prestasi : '', $jenis);
            $poinTeks = is_scalar($poin) ? trim((string) $poin) : '';

            if ($urai['nilai'] === null || preg_match('/^\d{1,4}(?:\.0+)?$/D', $poinTeks) !== 1) {
                if ($baris === [] && $galat === [] && $nomor === 1) {
                    continue;   // judul kolom
                }

                $galat[] = 'Baris ' . $nomor . ': ' . ($urai['nilai'] === null
                    ? ($urai['galat'] ?? 'prestasi kosong.')
                    : 'poin "' . $poinTeks . '" harus angka bulat 0 sampai ' . self::POIN_MAKSIMAL . '.');

                continue;
            }

            $nilaiPoin = (int) $poinTeks;

            if ($nilaiPoin > self::POIN_MAKSIMAL) {
                $galat[] = 'Baris ' . $nomor . ': poin ' . $nilaiPoin . ' terlalu besar. Batasnya ' . self::POIN_MAKSIMAL . '.';

                continue;
            }

            if (isset($ada[$urai['nilai']])) {
                $galat[] = 'Baris ' . $nomor . ': prestasi ' . Prestasi::tulis($urai['nilai'], $jenis) . ' sudah ada di baris ' . $ada[$urai['nilai']] . '.';

                continue;
            }

            $ada[$urai['nilai']] = $nomor;
            $baris[]             = [$urai['nilai'], $nilaiPoin];
        }

        if ($baris === [] && $galat === []) {
            $galat[] = 'Berkas tidak berisi baris tabel. Isi kolom A dengan prestasi dan kolom B dengan poin.';
        }

        return ['baris' => $baris, 'galat' => $galat];
    }

    /**
     * Prestasi untuk baris tabel skor. Sama dengan `Prestasi::urai()`, tetapi
     * jarak boleh sampai 999,99 m (tabel lempar lembing dimulai di atas 100 m).
     *
     * @return array{nilai: int|null, galat: string|null}
     */
    public static function uraiPrestasiTabel(string $teks, string $jenis): array
    {
        $teks = trim(str_replace(' ', '', $teks));

        if ($jenis === JenisHasil::JARAK && preg_match('/^(\d{1,3})(?:[,.](\d{1,2}))?$/D', $teks, $m) === 1) {
            $nilai = (int) $m[1] * 100 + (($m[2] ?? '') === '' ? 0 : (int) str_pad($m[2], 2, '0'));

            return $nilai >= 1 ? ['nilai' => $nilai, 'galat' => null] : ['nilai' => null, 'galat' => Prestasi::pesanGalat($teks, $jenis)];
        }

        return Prestasi::urai($teks, $jenis);
    }

    /**
     * Periksa isi satu tabel lengkap (berkas `data/tabel-skor/*.json` atau
     * paket pindah server). Baris boleh kosong untuk tabel buatan admin.
     *
     * @param array<string, mixed> $data
     *
     * @return list<string> galat; kosong bila benar
     */
    public static function periksaData(array $data): array
    {
        $galat = [];

        if (! self::kodeSah($data['kode'] ?? null)) {
            $galat[] = 'kode tidak sah';
        }

        if (! is_string($data['nama'] ?? null) || $data['nama'] === '') {
            $galat[] = 'nama kosong';
        }

        $komponen = self::bacaKomponen($data['komponen'] ?? null);

        if ($komponen === [] || count($komponen) !== count((array) ($data['komponen'] ?? []))) {
            $galat[] = 'komponen tidak lengkap';
        }

        foreach ((array) ($data['komponen'] ?? []) as $k) {
            foreach (array_keys(self::KATEGORI) as $kategori) {
                $baris = $k['baris'][$kategori] ?? null;

                if (! is_array($baris)) {
                    $galat[] = ($k['kode'] ?? '?') . ' ' . $kategori . ': baris tidak ada';

                    continue;
                }

                foreach ($baris as $b) {
                    if (! is_array($b) || ! is_int($b[0] ?? null) || ! is_int($b[1] ?? null) || $b[0] < 1 || $b[1] < 0 || $b[1] > self::POIN_MAKSIMAL) {
                        $galat[] = ($k['kode'] ?? '?') . ' ' . $kategori . ': baris tidak sah';

                        break;
                    }
                }
            }
        }

        return $galat;
    }
}
