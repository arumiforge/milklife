<?php

use App\Libraries\Panel\NomorDada;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aturan murni nomor dada (rancangan 6.9, K05): 4 digit, awalan + urut
 * 001–999, berlanjut ke awalan berikutnya, dan tidak membagikan apa pun bila
 * awalan tidak cukup.
 *
 * @internal
 */
final class PanelNomorDadaTest extends CIUnitTestCase
{
    public function testNomorBerurutanDariSatu(): void
    {
        $hasil = NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => 0]], 3);

        $this->assertSame(['2001', '2002', '2003'], $hasil['nomor']);
        $this->assertSame([2 => 3], $hasil['nomor_terakhir']);
    }

    public function testMelanjutkanNomorTerakhir(): void
    {
        $hasil = NomorDada::bagikan([['awalan' => 3, 'nomor_terakhir' => '41']], 2);

        $this->assertSame(['3042', '3043'], $hasil['nomor']);
        $this->assertSame([3 => 43], $hasil['nomor_terakhir']);
    }

    public function testBerlanjutKeAwalanBerikutnya(): void
    {
        $awalan = [
            ['awalan' => '2', 'nomor_terakhir' => 998],
            ['awalan' => '6', 'nomor_terakhir' => 0],
            ['awalan' => '7', 'nomor_terakhir' => 0],
        ];

        $hasil = NomorDada::bagikan($awalan, 3);

        $this->assertSame(['2999', '6001', '6002'], $hasil['nomor']);
        // Awalan 7 belum disentuh, jadi tidak ikut di hasil.
        $this->assertSame([2 => 999, 6 => 2], $hasil['nomor_terakhir']);
    }

    public function testAwalanPenuhDilewati(): void
    {
        $hasil = NomorDada::bagikan([
            ['awalan' => '2', 'nomor_terakhir' => 999],
            ['awalan' => '6', 'nomor_terakhir' => 10],
        ], 1);

        $this->assertSame(['6011'], $hasil['nomor']);
        $this->assertSame([6 => 11], $hasil['nomor_terakhir']);
    }

    public function testUrutanDaftarMenentukanAwalanYangDipakaiDulu(): void
    {
        $hasil = NomorDada::bagikan([
            ['awalan' => '8', 'nomor_terakhir' => 0],
            ['awalan' => '1', 'nomor_terakhir' => 0],
        ], 1);

        $this->assertSame(['8001'], $hasil['nomor']);
    }

    public function testTidakCukupMenghasilkanNullTanpaNomor(): void
    {
        $awalan = [
            ['awalan' => '2', 'nomor_terakhir' => 997],
            ['awalan' => '6', 'nomor_terakhir' => 998],
        ];

        $this->assertSame(3, NomorDada::sisa($awalan));
        $this->assertNotNull(NomorDada::bagikan($awalan, 3));
        $this->assertNull(NomorDada::bagikan($awalan, 4));
    }

    public function testTanpaAwalanTidakBisaMemberiNomor(): void
    {
        $this->assertNull(NomorDada::bagikan([], 1));
        $this->assertSame(0, NomorDada::sisa([]));
    }

    public function testNolAtletTidakMengubahApaPun(): void
    {
        $this->assertSame(['nomor' => [], 'nomor_terakhir' => []], NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => 5]], 0));
        $this->assertSame(['nomor' => [], 'nomor_terakhir' => []], NomorDada::bagikan([], 0));
    }

    public function testSatuAwalanTepat999Atlet(): void
    {
        $hasil = NomorDada::bagikan([['awalan' => '5', 'nomor_terakhir' => 0]], 999);

        $this->assertCount(999, $hasil['nomor']);
        $this->assertSame('5001', $hasil['nomor'][0]);
        $this->assertSame('5999', $hasil['nomor'][998]);
        $this->assertSame([5 => 999], $hasil['nomor_terakhir']);
        $this->assertCount(999, array_unique($hasil['nomor']));
        $this->assertNull(NomorDada::bagikan([['awalan' => '5', 'nomor_terakhir' => 0]], 1000));
    }

    public function testNomorTerakhirDiLuarRentangDibatasi(): void
    {
        $this->assertNull(NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => 1500]], 1));
        $this->assertSame(['2001'], NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => -3]], 1)['nomor']);
        $this->assertSame(['2001'], NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => null]], 1)['nomor']);
    }

    public function testFormat(): void
    {
        $this->assertSame('2007', NomorDada::format('2', 7));
        $this->assertSame('9999', NomorDada::format(9, 999));
        $this->assertSame('1100', NomorDada::format('1', 100));
    }

    /**
     * @return iterable<string, array{0: Closure(): mixed}>
     */
    public static function masukanSalah(): iterable
    {
        yield 'jumlah negatif' => [static fn () => NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => 0]], -1)];

        yield 'awalan nol' => [static fn () => NomorDada::bagikan([['awalan' => '0', 'nomor_terakhir' => 0]], 1)];

        yield 'awalan dua angka' => [static fn () => NomorDada::bagikan([['awalan' => '12', 'nomor_terakhir' => 0]], 1)];

        yield 'awalan huruf' => [static fn () => NomorDada::sisa([['awalan' => 'A', 'nomor_terakhir' => 0]])];

        yield 'awalan ganda' => [static fn () => NomorDada::bagikan([['awalan' => '2', 'nomor_terakhir' => 0], ['awalan' => 2, 'nomor_terakhir' => 5]], 1)];

        yield 'urut nol' => [static fn () => NomorDada::format('2', 0)];

        yield 'urut seribu' => [static fn () => NomorDada::format('2', 1000)];
    }

    #[DataProvider('masukanSalah')]
    public function testMasukanSalahDitolak(Closure $panggil): void
    {
        $this->expectException(InvalidArgumentException::class);
        $panggil();
    }
}
