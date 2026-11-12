<?php

use App\Models\AwalanNomorDadaModel;
use App\Models\KelompokUmurModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aturan murni kelompok umur (F2-03): teks batas tahun lahir, tabrakan
 * rentang tahun lahir (K36, rancangan 6.4), dan urai isian awalan nomor dada.
 *
 * @internal
 */
final class KelompokUmurAturanTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{0: int|string|null, 1: int|string|null, 2: string}>
     */
    public static function contohTeksTahunLahir(): iterable
    {
        yield 'rentang' => [2016, 2018, 'Lahir 2016–2018'];
        yield 'satu tahun' => [2015, 2015, 'Lahir 2015'];
        yield 'ke atas' => [2019, null, 'Lahir 2019 ke atas'];
        yield 'ke bawah' => [null, 2010, 'Lahir 2010 ke bawah'];
        yield 'kosong' => [null, null, ''];
        yield 'teks dari basis data' => ['2008', '2010', 'Lahir 2008–2010'];
        yield 'teks kosong' => ['', '2010', 'Lahir 2010 ke bawah'];
    }

    #[DataProvider('contohTeksTahunLahir')]
    public function testTeksTahunLahir(int|string|null $awal, int|string|null $akhir, string $harapan): void
    {
        $this->assertSame($harapan, KelompokUmurModel::teksTahunLahir($awal, $akhir));
    }

    /**
     * @return iterable<string, array{0: list<int|string|null>, 1: list<int|string|null>, 2: bool}>
     */
    public static function contohTabrakan(): iterable
    {
        // Data awal K36 tidak saling bertabrakan.
        yield 'KU 8 dan KU 10' => [[2019, null], [2016, 2018], false];
        yield 'KU 10 dan KU 12' => [[2016, 2018], [2015, 2015], false];
        yield 'KU 15 dan KU 18' => [[2011, 2014], [2008, 2010], false];

        yield 'berbagi satu tahun di ujung' => [[2016, 2018], [2018, 2020], true];
        yield 'satu di dalam yang lain' => [[2010, 2020], [2014, 2015], true];
        yield 'sama persis' => [[2015, 2015], [2015, 2015], true];
        yield 'bersebelahan' => [[2016, 2018], [2019, 2020], false];

        // Batas kosong = tidak terbatas di sisi itu.
        yield 'ke atas menabrak rentang di atasnya' => [[2019, null], [2020, 2021], true];
        yield 'ke atas tidak menabrak rentang di bawahnya' => [[2019, null], [2010, 2018], false];
        yield 'ke bawah menabrak rentang di bawahnya' => [[null, 2010], [2000, 2005], true];
        yield 'ke atas dan ke bawah bertemu' => [[2019, null], [null, 2019], true];
        yield 'ke atas dan ke bawah berjauhan' => [[2019, null], [null, 2018], false];
        yield 'dua-duanya ke atas' => [[2019, null], [2025, null], true];

        // Kedua batas kosong: belum punya rentang, tidak dibandingkan.
        yield 'belum diisi' => [[null, null], [2016, 2018], false];
        yield 'belum diisi di sisi lain' => [[2016, 2018], ['', ''], false];
    }

    /**
     * @param list<int|string|null> $a
     * @param list<int|string|null> $b
     */
    #[DataProvider('contohTabrakan')]
    public function testRentangBertabrakan(array $a, array $b, bool $harapan): void
    {
        $this->assertSame($harapan, KelompokUmurModel::rentangBertabrakan($a[0], $a[1], $b[0], $b[1]));
        $this->assertSame($harapan, KelompokUmurModel::rentangBertabrakan($b[0], $b[1], $a[0], $a[1]), 'Urutan pembanding tidak berpengaruh.');
    }

    public function testTahunLahirTerbesarAdalahTahunBerjalan(): void
    {
        $this->assertSame((int) date('Y'), KelompokUmurModel::tahunLahirTerbesar());
    }

    /**
     * @return iterable<string, array{0: string|null, 1: list<string>}>
     */
    public static function contohAwalanSah(): iterable
    {
        yield 'satu' => ['2', ['2']];
        yield 'koma' => ['2, 6', ['2', '6']];
        yield 'urut sesuai ketikan' => ['6,2,9', ['6', '2', '9']];
        yield 'spasi' => [' 4 7 ', ['4', '7']];
        yield 'titik koma dan garis miring' => ['1;3/5', ['1', '3', '5']];
        yield 'kosong' => ['', []];
        yield 'null' => [null, []];
        yield 'hanya pemisah' => [' , ', []];
    }

    /**
     * @param list<string> $harapan
     */
    #[DataProvider('contohAwalanSah')]
    public function testUraiAwalanSah(?string $isian, array $harapan): void
    {
        $this->assertSame([$harapan, null], AwalanNomorDadaModel::urai($isian));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function contohAwalanSalah(): iterable
    {
        yield 'nol' => ['0', 'Awalan "0" belum benar. Awalan berupa satu angka 1 sampai 9; pisahkan beberapa awalan dengan koma, misalnya 4, 6.'];
        yield 'dua angka' => ['46', 'Awalan "46" belum benar.'];
        yield 'huruf' => ['2, a', 'Awalan "a" belum benar.'];
        yield 'minus' => ['-3', 'Awalan "-3" belum benar.'];
        yield 'ganda' => ['2, 6, 2', 'Awalan 2 tertulis lebih dari sekali. Tulis setiap awalan sekali saja.'];
    }

    #[DataProvider('contohAwalanSalah')]
    public function testUraiAwalanSalah(string $isian, string $awalPesan): void
    {
        [$awalan, $pesan] = AwalanNomorDadaModel::urai($isian);

        $this->assertSame([], $awalan);
        $this->assertIsString($pesan);
        $this->assertStringStartsWith($awalPesan, $pesan);
    }
}
