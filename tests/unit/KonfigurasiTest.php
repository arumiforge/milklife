<?php

use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Config\Database;
use Config\Security;
use Config\Session;

/**
 * Konfigurasi kerangka sesuai kesepakatan Tahap 1.
 *
 * @internal
 */
final class KonfigurasiTest extends CIUnitTestCase
{
    public function testBahasaZonaWaktuDanAlamat(): void
    {
        $app = config(App::class);

        $this->assertSame('id', $app->defaultLocale);
        $this->assertSame(['id'], $app->supportedLocales);
        $this->assertSame('Asia/Jakarta', $app->appTimezone);
        $this->assertSame('', $app->indexPage);
    }

    public function testCsrfBerbasisSesiDanAktifGlobal(): void
    {
        $this->assertSame('session', config(Security::class)->csrfProtection);
        $this->assertContains('csrf', config('Filters')->globals['before']);
    }

    public function testSesiBerkas(): void
    {
        $sesi = config(Session::class);

        $this->assertSame(CodeIgniter\Session\Handlers\FileHandler::class, ltrim($sesi->driver, '\\'));
        $this->assertSame(WRITEPATH . 'session', $sesi->savePath);
        $this->assertSame(7200, $sesi->expiration);
    }

    public function testSavePathNullDariEnvBerartiFolderBawaan(): void
    {
        $_ENV['session.savePath'] = 'null';

        try {
            $this->assertSame(WRITEPATH . 'session', (new Session())->savePath);
        } finally {
            unset($_ENV['session.savePath']);
        }
    }

    public function testPeruteanOtomatisMati(): void
    {
        $this->assertFalse(config('Routing')->autoRoute);
    }

    public function testGrupBasisDataUji(): void
    {
        $tests = config(Database::class)->tests;

        $this->assertSame('MySQLi', $tests['DBDriver']);
        $this->assertSame('', $tests['DBPrefix']);
        $this->assertSame('tests', config(Database::class)->defaultGroup);
    }

    public function testPesanValidasiBerbahasaIndonesia(): void
    {
        $validasi = service('validation');
        $validasi->setRules(['nama' => ['label' => 'Nama', 'rules' => 'required']]);

        $this->assertFalse($validasi->run([]));
        $this->assertSame('Bidang Nama diperlukan.', $validasi->getError('nama'));
    }
}
