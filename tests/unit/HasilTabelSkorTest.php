<?php

use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\Prestasi;
use App\Libraries\Hasil\TabelSkor;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Kalkulator poin multi-lomba tanpa basis data (F9-10, K140 butir 7b): isi
 * `data/tabel-skor/*.json` cocok dengan baris PDF panitia, aturan pencarian
 * poin, hitung total, dan baca Excel.
 *
 * @internal
 */
final class HasilTabelSkorTest extends CIUnitTestCase
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private static array $tabel = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        foreach (DataTabelSkor::berkasBawaan() as $t) {
            self::$tabel[$t['kode']] = $t;
        }
    }

    /**
     * Baris sampel dibaca langsung dari `sumber/tabel-skor/*.pdf`.
     *
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: string, 4: int}>
     */
    public static function sampelPdf(): iterable
    {
        // Trilomba O2SN SMP (Scoring Table IAAF & PASI), kolom "Mark Pts".
        yield 'trilomba 60 m putra 7,00 (contoh tugas)' => ['trilomba-o2sn-smp', 'lari-60', 'putra', '7,00', 882];
        yield 'trilomba 60 m putra 5,46 baris teratas' => ['trilomba-o2sn-smp', 'lari-60', 'putra', '5,46', 1500];
        yield 'trilomba 60 m putra 6,50' => ['trilomba-o2sn-smp', 'lari-60', 'putra', '6,50', 1068];
        yield 'trilomba 60 m putra 8,00' => ['trilomba-o2sn-smp', 'lari-60', 'putra', '8,00', 560];
        yield 'trilomba 60 m putra 10,10' => ['trilomba-o2sn-smp', 'lari-60', 'putra', '10,10', 106];
        yield 'trilomba lompat jauh putra 9,63' => ['trilomba-o2sn-smp', 'lompat-jauh', 'putra', '9,63', 1500];
        yield 'trilomba lompat jauh putra 8,10' => ['trilomba-o2sn-smp', 'lompat-jauh', 'putra', '8,10', 1086];
        yield 'trilomba tolak peluru putra 23,99' => ['trilomba-o2sn-smp', 'tolak-peluru', 'putra', '23,99', 1350];
        yield 'trilomba tolak peluru putra 4,44' => ['trilomba-o2sn-smp', 'tolak-peluru', 'putra', '4,44', 159];
        yield 'trilomba 60 m putri 7,10' => ['trilomba-o2sn-smp', 'lari-60', 'putri', '7,10', 1144];
        yield 'trilomba tolak peluru putri 4,21' => ['trilomba-o2sn-smp', 'tolak-peluru', 'putri', '4,21', 159];
        yield 'trilomba tolak peluru putri 1,53 baris terakhir' => ['trilomba-o2sn-smp', 'tolak-peluru', 'putri', '1,53', 1];
        // Pancalomba SMA/SMK (World Athletics), kolom "Perf. Points".
        yield 'pancalomba 100 m putra 11,00' => ['pancalomba-sma-smk', 'lari-100', 'putra', '11,00', 861];
        yield 'pancalomba lompat jauh putra 9,49' => ['pancalomba-sma-smk', 'lompat-jauh', 'putra', '9,49', 1461];
        yield 'pancalomba lempar lembing putra 102,85' => ['pancalomba-sma-smk', 'lempar-lembing', 'putra', '102,85', 1400];
        yield 'pancalomba 800 m putra 2:04,31' => ['pancalomba-sma-smk', 'lari-800', 'putra', '2:04,31', 1050];
        yield 'pancalomba 800 m putri 3:27,12 (nomor halaman menempel di PDF)' => ['pancalomba-sma-smk', 'lari-800', 'putri', '3:27,12', 155];
        yield 'pancalomba 800 m putri 4:10,79 baris terakhir' => ['pancalomba-sma-smk', 'lari-800', 'putri', '4:10,79', 1];
    }

    /**
     * @dataProvider sampelPdf
     */
    public function testBarisCocokDenganPdf(string $tabel, string $komponen, string $kategori, string $teks, int $poin): void
    {
        $k     = $this->komponen($tabel, $komponen);
        $nilai = TabelSkor::uraiPrestasiTabel($teks, $k['jenis'])['nilai'];
        $this->assertIsInt($nilai);
        $this->assertSame(['poin' => $poin, 'luar' => null], TabelSkor::cariPoin($k['baris'][$kategori], $nilai, $k['jenis']));
    }

    public function testBerkasBawaanLengkap(): void
    {
        $this->assertSame(['pancalomba-sma-smk', 'trilomba-o2sn-smp'], array_keys(self::$tabel));
        $this->assertSame(['lari-60', 'lompat-jauh', 'tolak-peluru'], array_column(self::$tabel['trilomba-o2sn-smp']['komponen'], 'kode'));
        $this->assertSame(['lari-100', 'lari-800', 'lompat-jauh', 'tolak-peluru', 'lempar-lembing'], array_column(self::$tabel['pancalomba-sma-smk']['komponen'], 'kode'));

        foreach (self::$tabel as $t) {
            $this->assertSame([], TabelSkor::periksaData($t), $t['kode']);

            foreach ($t['komponen'] as $k) {
                foreach (array_keys(TabelSkor::KATEGORI) as $kategori) {
                    $baris = $k['baris'][$kategori];
                    $this->assertGreaterThan(500, count($baris), "{$t['kode']} {$k['kode']} {$kategori}");
                    $this->assertSame(1, $baris[count($baris) - 1][1], "{$t['kode']} {$k['kode']} {$kategori} berakhir di 1 poin");

                    // Urut dari prestasi terbaik, prestasi unik, poin tidak naik.
                    for ($i = 1; $i < count($baris); $i++) {
                        $this->assertTrue($k['jenis'] === JenisHasil::WAKTU ? $baris[$i][0] > $baris[$i - 1][0] : $baris[$i][0] < $baris[$i - 1][0], "{$t['kode']} {$k['kode']} {$kategori} baris {$i}");
                        $this->assertLessThanOrEqual($baris[$i - 1][1], $baris[$i][1]);
                    }
                }
            }
        }
    }

    public function testDiAntaraDuaBarisMemakaiBarisYangLebihBuruk(): void
    {
        $lari = [[546, 1500], [700, 882], [710, 847]];
        $this->assertSame(['poin' => 847, 'luar' => null], TabelSkor::cariPoin($lari, 705, 'waktu'));
        $this->assertSame(['poin' => 882, 'luar' => null], TabelSkor::cariPoin($lari, 700, 'waktu'));
        $this->assertSame(['poin' => 847, 'luar' => null], TabelSkor::cariPoin($lari, 710, 'waktu'));

        $tolak = [[2636, 1500], [2635, 1499], [2633, 1498]];
        $this->assertSame(['poin' => 1498, 'luar' => null], TabelSkor::cariPoin($tolak, 2634, 'jarak'));
        $this->assertSame(['poin' => 1500, 'luar' => null], TabelSkor::cariPoin(array_reverse($tolak), 2636, 'jarak'));

        // Contoh nyata Trilomba: 26,34 m di antara 26,35 (1499) dan 26,33 (1498).
        $k = $this->komponen('trilomba-o2sn-smp', 'tolak-peluru');
        $this->assertSame(1498, TabelSkor::cariPoin($k['baris']['putra'], 2634, 'jarak')['poin']);
    }

    public function testDiLuarTabelNolDenganPeringatan(): void
    {
        $lari = [[546, 1500], [1106, 1]];
        $this->assertSame(['poin' => 0, 'luar' => 'bawah'], TabelSkor::cariPoin($lari, 1200, 'waktu'));
        $this->assertSame(['poin' => 0, 'luar' => 'atas'], TabelSkor::cariPoin($lari, 70, 'waktu'));
        $this->assertSame(['poin' => 0, 'luar' => 'bawah'], TabelSkor::cariPoin([[400, 100], [300, 1]], 250, 'jarak'));
        $this->assertSame(['poin' => 0, 'luar' => 'atas'], TabelSkor::cariPoin([[400, 100], [300, 1]], 401, 'jarak'));
        $this->assertSame(['poin' => 0, 'luar' => 'bawah'], TabelSkor::cariPoin([], 700, 'waktu'));
    }

    public function testHitungTrilombaContoh(): void
    {
        $tabel    = self::$tabel['trilomba-o2sn-smp'];
        $komponen = TabelSkor::bacaKomponen($tabel['komponen']);
        $baris    = [];

        foreach ($tabel['komponen'] as $k) {
            $baris[$k['kode']] = $k['baris']['putra'];
        }

        // 60 m 7,00 = 882; lompat jauh 8,10 = 1086; tolak peluru 23,99 = 1350.
        $hasil = TabelSkor::hitung($komponen, $baris, ['lari-60' => '7,00', 'lompat-jauh' => '8.10', 'tolak-peluru' => '23,99']);
        $this->assertSame([], $hasil['galat']);
        $this->assertSame(882 + 1086 + 1350, $hasil['total']);
        $this->assertSame(['prestasi' => 700, 'poin' => 882, 'luar' => null], $hasil['rincian']['lari-60']);

        // Komponen kosong = 0 poin; semua kosong = belum ada total.
        $sebagian = TabelSkor::hitung($komponen, $baris, ['lari-60' => '7,00']);
        $this->assertSame(882, $sebagian['total']);
        $this->assertSame(['prestasi' => null, 'poin' => 0, 'luar' => null], $sebagian['rincian']['tolak-peluru']);
        $this->assertNull(TabelSkor::hitung($komponen, $baris, [])['total']);

        // Isian salah menyebut komponennya; tidak ada yang dihitung.
        $salah = TabelSkor::hitung($komponen, $baris, ['lari-60' => '7 detik', 'lompat-jauh' => '4,10']);
        $this->assertSame(['lari-60'], array_keys($salah['galat']));
        $this->assertStringStartsWith('Lari 60 m: Prestasi "7detik" belum benar.', $salah['galat']['lari-60']);
        $this->assertNull($salah['total']);

        // Di luar tabel: 0 poin, ditandai.
        $luar = TabelSkor::hitung($komponen, $baris, ['lari-60' => '0,70', 'lompat-jauh' => '8,10']);
        $this->assertSame(1086, $luar['total']);
        $this->assertSame('atas', $luar['rincian']['lari-60']['luar']);

        // Total dipakai sebagai prestasi berjenis multi: makin besar makin baik, sama = peringkat sama.
        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 2, 'd' => null], Peringkat::hitung([
            'a' => ['prestasi' => 2500, 'keterangan' => null],
            'b' => ['prestasi' => 3318, 'keterangan' => null],
            'c' => ['prestasi' => 2500, 'keterangan' => null],
            'd' => ['prestasi' => 2000, 'keterangan' => 'DQ'],
        ], JenisHasil::MULTI));
        $this->assertSame('3318', Prestasi::tulis(3318, JenisHasil::MULTI));
    }

    public function testRincianDanHitungUlang(): void
    {
        $komponen = [['kode' => 'lari-60', 'nama' => 'Lari 60 m', 'jenis' => 'waktu'], ['kode' => 'lompat-jauh', 'nama' => 'Lompat Jauh', 'jenis' => 'jarak']];
        $rincian  = ['lari-60' => ['prestasi' => 700, 'poin' => 882, 'luar' => null], 'lompat-jauh' => ['prestasi' => null, 'poin' => 0, 'luar' => null]];
        $json     = TabelSkor::tulisRincian($rincian);

        $this->assertSame($rincian, TabelSkor::bacaRincian($json));
        $this->assertSame([], TabelSkor::bacaRincian('rusak'));
        $this->assertNull(TabelSkor::tulisRincian([]));

        // Baris tabel diubah admin: 7,00 sekarang 900 poin.
        $baru = TabelSkor::hitungUlang($komponen, ['lari-60' => [[546, 1500], [700, 900]], 'lompat-jauh' => []], $rincian);
        $this->assertSame(900, $baru['total']);
        $this->assertSame(900, $baru['rincian']['lari-60']['poin']);

        $this->assertSame([
            ['nama' => 'Lari 60 m', 'prestasi' => '7,00', 'poin' => 882, 'luar' => null],
            ['nama' => 'Lompat Jauh', 'prestasi' => '', 'poin' => 0, 'luar' => null],
        ], TabelSkor::rincianTampil($komponen, $rincian));
    }

    public function testBacaSelExcel(): void
    {
        $baca = TabelSkor::bacaSel([
            ['Prestasi', 'Poin'],
            ['7,00', 882],
            [7.01, '879'],
            [null, null],
            ['7.02', 875.0],
        ], 'waktu');
        $this->assertSame([], $baca['galat']);
        $this->assertSame([[700, 882], [701, 879], [702, 875]], $baca['baris']);

        // Waktu yang dibaca Excel sebagai jam: 2:05,10 = 125,1 detik / 86400.
        $this->assertSame([[12510, 1000]], TabelSkor::bacaSel([[125.1 / 86400, 1000]], 'waktu')['baris']);

        // Lempar lembing di atas 100 m tetap diterima di tabel.
        $this->assertSame([[10285, 1400]], TabelSkor::bacaSel([['102,85', 1400]], 'jarak')['baris']);

        $salah = TabelSkor::bacaSel([['7,00', 882], ['7,00', 880], ['tujuh', 5], ['7,10', 'banyak'], ['7,20', 20000]], 'waktu');
        $this->assertSame([], $salah['baris'] === [[700, 882]] ? [] : ['baris']);
        $this->assertCount(4, $salah['galat']);
        $this->assertStringStartsWith('Baris 2: prestasi 7,00 sudah ada di baris 1.', $salah['galat'][0]);

        $this->assertSame(['Berkas tidak berisi baris tabel. Isi kolom A dengan prestasi dan kolom B dengan poin.'], TabelSkor::bacaSel([['Prestasi', 'Poin']], 'waktu')['galat']);
    }

    public function testKodeDanKomponen(): void
    {
        $this->assertSame('lari-60-m', TabelSkor::kodeDariNama('Lari 60 m'));
        $this->assertSame('komponen', TabelSkor::kodeDariNama('—'));
        $this->assertTrue(TabelSkor::kodeSah('tolak-peluru'));
        $this->assertFalse(TabelSkor::kodeSah('Tolak Peluru'));
        $this->assertSame(
            [['kode' => 'lari-60', 'nama' => 'Lari 60 m', 'jenis' => 'waktu']],
            TabelSkor::bacaKomponen('[{"kode":"lari-60","nama":"Lari 60 m","jenis":"waktu"},{"kode":"x","nama":"X","jenis":"poin"}]'),
        );

        $semua = [['kode' => 'a', 'nama' => 'A', 'jenis' => 'waktu'], ['kode' => 'b', 'nama' => 'B', 'jenis' => 'jarak']];
        $this->assertSame($semua, DataTabelSkor::pilihKomponen($semua, null));
        $this->assertSame([$semua[1], $semua[0]], DataTabelSkor::pilihKomponen($semua, 'b,a,zz'));
    }

    /**
     * @return array<string, mixed>
     */
    private function komponen(string $tabel, string $kode): array
    {
        foreach (self::$tabel[$tabel]['komponen'] as $k) {
            if ($k['kode'] === $kode) {
                return $k;
            }
        }

        $this->fail("Komponen {$kode} tidak ada di {$tabel}.");
    }
}
