<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * `perpanjang_batas_waktu()` di `app/Helpers/aplikasi_helper.php` (F11-17,
 * K182): batas waktu skrip hanya diperpanjang, tidak pernah diperpendek.
 *
 * @internal
 */
final class PerpanjangBatasWaktuTest extends CIUnitTestCase
{
    private string $awal = '0';

    protected function setUp(): void
    {
        parent::setUp();
        $this->awal = (string) ini_get('max_execution_time');
    }

    protected function tearDown(): void
    {
        set_time_limit((int) $this->awal);
        parent::tearDown();
    }

    public function testTanpaBatasTetapTanpaBatas(): void
    {
        set_time_limit(0);
        perpanjang_batas_waktu(300);

        $this->assertSame('0', ini_get('max_execution_time'));
    }

    public function testBatasPendekDiperpanjang(): void
    {
        set_time_limit(30);
        perpanjang_batas_waktu(300);

        $this->assertSame('300', ini_get('max_execution_time'));
    }

    public function testBatasPanjangTidakDiperpendek(): void
    {
        set_time_limit(600);
        perpanjang_batas_waktu(300);

        $this->assertSame('600', ini_get('max_execution_time'));
    }

    public function testNolBerartiTanpaBatas(): void
    {
        set_time_limit(30);
        perpanjang_batas_waktu(0);

        $this->assertSame('0', ini_get('max_execution_time'));
    }
}
