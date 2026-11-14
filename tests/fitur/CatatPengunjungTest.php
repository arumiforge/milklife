<?php

use CodeIgniter\Config\Services;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\UjiBasisData;

/**
 * Filter `CatatPengunjung` (K102): halaman publik yang dibuka peramban
 * tercatat di tabel `kunjungan`; halaman admin, JSON, dan bot tidak.
 *
 * @internal
 */
final class CatatPengunjungTest extends UjiBasisData
{
    use FeatureTestTrait;

    private const PERAMBAN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0 Safari/537.36';

    protected function tearDown(): void
    {
        service('superglobals')->unsetServer('HTTP_USER_AGENT');

        parent::tearDown();
    }

    private function buka(string $alamat, string $peramban = self::PERAMBAN): void
    {
        service('superglobals')->setServer('HTTP_USER_AGENT', $peramban);
        // Jawaban bersama bisa masih membawa Content-Type PDF/JSON dari uji lain;
        // di server sungguhan setiap permintaan mulai dengan jawaban baru.
        Services::resetSingle('response');
        Services::resetSingle('request');

        $this->get($alamat);
    }

    /**
     * @return list<string>
     */
    private function halamanTercatat(): array
    {
        return array_column($this->db->table('kunjungan')->orderBy('id')->get()->getResultArray(), 'halaman');
    }

    public function testHalamanPublikTercatat(): void
    {
        $this->buka('/');
        $this->buka('cek-pendaftaran');

        $this->assertSame(['/', '/cek-pendaftaran'], $this->halamanTercatat());
    }

    public function testAdminJsonDanBotTidakTercatat(): void
    {
        $this->buka('admin/masuk');
        $this->buka('pendaftaran/sekolah?npsn=12345678');
        $this->buka('/', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');
        $this->buka('/', '');

        $this->assertSame([], $this->halamanTercatat());
    }

    public function testHalamanTetapTampilBilaTabelBelumAda(): void
    {
        $this->db->query('DROP TABLE ' . $this->db->prefixTable('kunjungan'));

        service('superglobals')->setServer('HTTP_USER_AGENT', self::PERAMBAN);
        Services::resetSingle('response');
        Services::resetSingle('request');

        $this->get('/')->assertOK();
    }
}
