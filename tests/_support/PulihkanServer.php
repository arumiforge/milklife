<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;

/**
 * Memulihkan `$_SERVER` sesudah uji yang menggantinya seluruhnya.
 *
 * `IncomingRequest::setGlobal('server', [...])` dan `new Superglobals($server)`
 * mengganti seluruh `$_SERVER`, termasuk `PHP_SELF` dan `SCRIPT_NAME` yang
 * selalu ada di server web dan dibaca mPDF. Tanpa pemulihan, uji sesudahnya
 * di proses yang sama gagal atau lulus bergantung urutan.
 *
 * Pemakaian:
 *
 *     protected function setUp(): void
 *     {
 *         parent::setUp();
 *         $this->simpanServer();
 *     }
 *
 *     protected function tearDown(): void
 *     {
 *         $this->pulihkanServer();
 *         parent::tearDown();
 *     }
 */
trait PulihkanServer
{
    /**
     * @var array<string, mixed>
     */
    private array $serverAsli = [];

    protected function simpanServer(): void
    {
        $this->serverAsli = $_SERVER;
    }

    protected function pulihkanServer(): void
    {
        $_SERVER = $this->serverAsli;

        // Layanan dibuat ulang dari `$_SERVER` yang sudah pulih, termasuk
        // bila uji menyuntikkan tiruannya sendiri.
        Services::resetSingle('superglobals');
    }
}
