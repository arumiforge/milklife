<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Pemeriksaan tanpa menjalankan halaman Monitoring `public/arumi.php` (K102).
 * Alur masuk, kunci, dan tampilannya diperiksa manual lewat peramban.
 *
 * @internal
 */
final class PemantauArumiTest extends CIUnitTestCase
{
    private string $isi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->isi = (string) file_get_contents(FCPATH . 'arumi.php');
    }

    public function testKataSandiHanyaSebagaiHash(): void
    {
        $this->assertSame(1, preg_match("/const ARUMI_HASH_BAWAAN = '([^']+)'/", $this->isi, $m));
        $this->assertStringStartsWith('$2y$', $m[1]);
        $this->assertTrue(password_verify('kynaaz', $m[1]));
        $this->assertStringNotContainsString('kynaaz', $this->isi, 'Kata sandi tidak boleh tertulis apa adanya.');
        $this->assertStringContainsString("env('arumi.sandiHash'", $this->isi);
    }

    public function testPengamanDasar(): void
    {
        foreach ([
            'X-Robots-Tag: noindex',
            'Cache-Control: no-store',
            "frame-ancestors 'none'",
            "'samesite' => 'Strict'",
            "'httponly' => true",
            'session_regenerate_id(true)',
            'hash_equals($_SESSION[\'token\']',
            'ARUMI_BATAS_GAGAL',
        ] as $potongan) {
            $this->assertStringContainsString($potongan, $this->isi, $potongan);
        }

        // Data baru dikumpulkan setelah masuk.
        $this->assertMatchesRegularExpression('/if \(\$sudahMasuk\) \{\s+\$mulai = microtime/', $this->isi);
    }

    public function testSintaksSah(): void
    {
        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg(FCPATH . 'arumi.php') . ' 2>&1', $keluaran, $kode);

        $this->assertSame(0, $kode, implode("\n", $keluaran));
    }
}
