<?php

use App\Libraries\NomorWa;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Pembakuan nomor WA (`App\Libraries\NomorWa`). Nomor karangan.
 *
 * @internal
 */
final class NomorWaTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nomorSah(): iterable
    {
        yield 'diawali 0 dengan tanda hubung' => ['0812-3456-7890', '6281234567890'];
        yield 'diawali +62 dengan spasi' => ['+62 812 3456 7890', '6281234567890'];
        yield 'sudah baku' => ['6281234567890', '6281234567890'];
        yield 'diawali 8' => ['812 3456 7890', '6281234567890'];
        yield 'spasi di tepi' => ['  0812 3456 7890  ', '6281234567890'];
        yield 'titik dan kurung' => ['(0812) 3456.7890', '6281234567890'];
        yield 'paling pendek, 10 angka setelah baku' => ['081234567', '6281234567'];
        yield 'paling panjang, 15 angka setelah baku' => ['+62 812 3456 789012', '628123456789012'];
    }

    #[DataProvider('nomorSah')]
    public function testNomorSahDibakukan(string $isian, string $harapan): void
    {
        $this->assertSame($harapan, NomorWa::normalkan($isian));
        $this->assertTrue(NomorWa::sah($isian));
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function nomorTidakSah(): iterable
    {
        yield 'kosong' => [''];
        yield 'spasi saja' => ['   '];
        yield 'null' => [null];
        yield 'telepon rumah' => ['021-555-1234'];
        yield 'kode negara lain' => ['+60 12 345 6789'];
        yield 'terlalu pendek' => ['0812-345'];
        yield 'terlalu panjang' => ['0812-3456-7890-1234'];
        yield 'berisi huruf' => ['0812-3456-789O'];
        yield 'tanda lain' => ['0812#3456#7890'];
        yield 'plus di tengah' => ['0812+34567890'];
        yield 'bukan ponsel setelah 62' => ['622112345678'];
    }

    #[DataProvider('nomorTidakSah')]
    public function testNomorTidakSahMenghasilkanNull(?string $isian): void
    {
        $this->assertNull(NomorWa::normalkan($isian));
        $this->assertFalse(NomorWa::sah($isian));
    }

    public function testTampilanMudahDibaca(): void
    {
        $this->assertSame('0812-3456-7890', NomorWa::tampilan('6281234567890'));
        $this->assertSame('0812-3456-789', NomorWa::tampilan('+62 812 3456 789'));
        $this->assertSame('bukan nomor', NomorWa::tampilan('bukan nomor'));
        $this->assertSame('', NomorWa::tampilan(null));
    }

    public function testPesanGalatMenyebutCaraMemperbaiki(): void
    {
        $this->assertStringContainsString('0812-3456-7890', NomorWa::PESAN_GALAT);
    }
}
