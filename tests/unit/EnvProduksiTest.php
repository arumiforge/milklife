<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Bagian PRODUKSI di `.env.example` (F7-02): berkomentar, tanpa rahasia,
 * dan setiap kunci di dalamnya benar-benar dibaca aplikasi.
 *
 * @internal
 */
final class EnvProduksiTest extends CIUnitTestCase
{
    public function testIsianProduksiLengkapDanBenar(): void
    {
        $isian = $this->isianProduksi();

        $this->assertSame('production', $isian['CI_ENVIRONMENT'] ?? null);
        $this->assertStringStartsWith("'https://", $isian['app.baseURL'] ?? '');
        $this->assertStringEndsWith("/'", $isian['app.baseURL']);
        $this->assertSame('true', $isian['app.forceGlobalSecureRequests'] ?? null);
        $this->assertSame('true', $isian['cookie.secure'] ?? null);
        $this->assertSame('true', $isian['cookie.httponly'] ?? null);
        $this->assertSame('true', $isian['aplikasi.minify'] ?? null);
        $this->assertSame('4', $isian['logger.threshold'] ?? null);
    }

    public function testKunciProduksiDibacaAplikasi(): void
    {
        foreach (array_keys($this->isianProduksi()) as $kunci) {
            if ($kunci === 'CI_ENVIRONMENT') {
                continue;
            }

            [$awalan, $properti] = explode('.', $kunci, 3) + [1 => ''];
            $kelas = 'Config\\' . ucfirst($awalan);

            $this->assertTrue(class_exists($kelas), "Kelas {$kelas} untuk kunci {$kunci} tidak ada");
            $this->assertTrue(property_exists(new $kelas(), $properti), "Kunci {$kunci} tidak dibaca");
        }
    }

    public function testTanpaRahasia(): void
    {
        $isian = $this->isianProduksi();

        $this->assertArrayNotHasKey('encryption.key', $isian);
        $this->assertArrayNotHasKey('database.default.password', $isian);
    }

    public function testAlasanForceGlobalSecureRequestsDitulis(): void
    {
        $bagian = $this->bagianProduksi();

        foreach (['IncomingRequest::isSecure()', 'X-Forwarded-Proto: https', 'App::$proxyIPs', 'cloudflared'] as $kata) {
            $this->assertStringContainsString($kata, $bagian);
        }
    }

    /**
     * Baris `# kunci = nilai` di bagian PRODUKSI (sampai judul bagian
     * berikutnya yang ditulis huruf besar, atau akhir berkas).
     *
     * @return array<string, string>
     */
    private function isianProduksi(): array
    {
        $isian = [];

        foreach (explode("\n", $this->bagianProduksi()) as $baris) {
            if (preg_match('/^# ([A-Za-z_]+(?:\.[A-Za-z_]+)*) = (.*)$/', trim($baris), $cocok) === 1) {
                $this->assertArrayNotHasKey($cocok[1], $isian, 'Kunci ganda: ' . $cocok[1]);
                $isian[$cocok[1]] = trim($cocok[2]);
            }
        }

        return $isian;
    }

    private function bagianProduksi(): string
    {
        $isi = (string) file_get_contents(ROOTPATH . '.env.example');
        $this->assertSame(1, preg_match('/^# PRODUKSI\n(.*?)(?=^#-{20,}\n# [A-Z][A-Z ]+\n|\z)/ms', $isi, $cocok), 'Bagian PRODUKSI tidak ditemukan');

        return $cocok[1];
    }
}
