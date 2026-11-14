<?php

use App\Libraries\Pengunjung;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan catatan pengunjung tanpa basis data (K102, `App\Libraries\Pengunjung`).
 *
 * @internal
 */
final class PengunjungAturanTest extends CIUnitTestCase
{
    private const PERAMBAN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36';
    private const PONSEL   = 'Mozilla/5.0 (Linux; Android 14; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    public function testHalamanPublikDariPerambanDicatat(): void
    {
        $this->assertTrue(Pengunjung::layakDicatat('GET', '/', 200, 'text/html; charset=UTF-8', self::PERAMBAN));
        $this->assertTrue(Pengunjung::layakDicatat('get', 'cek-pendaftaran', 200, 'text/html', self::PONSEL));
    }

    public function testYangTidakDicatat(): void
    {
        $this->assertFalse(Pengunjung::layakDicatat('POST', '/pendaftaran/simpan', 200, 'text/html', self::PERAMBAN), 'Bukan GET.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/tidak-ada', 404, 'text/html', self::PERAMBAN), 'Bukan 200.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/', 302, 'text/html', self::PERAMBAN), 'Pengalihan.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/pendaftaran/sekolah', 200, 'application/json', self::PERAMBAN), 'JSON.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/bukti/abc/MAC', 200, 'application/pdf', self::PERAMBAN), 'PDF.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/admin', 200, 'text/html', self::PERAMBAN), 'Admin.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/admin/dasbor', 200, 'text/html', self::PERAMBAN), 'Admin.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/', 200, 'text/html', self::PERAMBAN, true), 'Prefetch.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/', 200, 'text/html', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'), 'Bot.');
        $this->assertFalse(Pengunjung::layakDicatat('GET', '/', 200, 'text/html', ''), 'Tanpa peramban.');

        // Halaman berawalan "admin" yang bukan panel admin tetap dicatat.
        $this->assertTrue(Pengunjung::layakDicatat('GET', '/administrasi', 200, 'text/html', self::PERAMBAN));
    }

    public function testBotDanPonsel(): void
    {
        foreach (['curl/8.5.0', 'Wget/1.21', 'python-requests/2.31', 'WhatsApp/2.23', 'facebookexternalhit/1.1', 'Mozilla/5.0 (compatible; bingbot/2.0)', 'Mozilla/5.0 HeadlessChrome/120', 'TelegramBot (like TwitterBot)'] as $bot) {
            $this->assertTrue(Pengunjung::bot($bot), $bot);
        }

        $this->assertFalse(Pengunjung::bot(self::PERAMBAN));
        $this->assertFalse(Pengunjung::bot(self::PONSEL));
        $this->assertTrue(Pengunjung::ponsel(self::PONSEL));
        $this->assertTrue(Pengunjung::ponsel('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X)'));
        $this->assertFalse(Pengunjung::ponsel(self::PERAMBAN));
    }

    public function testJalurDirapikan(): void
    {
        $this->assertSame('/', Pengunjung::rapikanJalur(''));
        $this->assertSame('/', Pengunjung::rapikanJalur('/'));
        $this->assertSame('/cek-pendaftaran', Pengunjung::rapikanJalur('cek-pendaftaran/?npsn=12345678'));
        // Kode rahasia bukti tidak disimpan.
        $this->assertSame('/bukti', Pengunjung::rapikanJalur('/bukti/0123456789abcdef01234567/MAC2A0000126'));
        $this->assertSame(191, mb_strlen(Pengunjung::rapikanJalur('/' . str_repeat('a', 300))));
    }

    public function testTanggal(): void
    {
        $this->assertSame('2026-11-15', Pengunjung::tanggalSah('2026-11-15', 'x'));
        $this->assertSame('bawaan', Pengunjung::tanggalSah('2026-02-30', 'bawaan'));
        $this->assertSame('bawaan', Pengunjung::tanggalSah('15-11-2026', 'bawaan'));
        $this->assertSame('bawaan', Pengunjung::tanggalSah("2026-11-15'--", 'bawaan'));
        $this->assertSame('bawaan', Pengunjung::tanggalSah(null, 'bawaan'));

        $this->assertSame('2026-12-01', Pengunjung::geser('2026-11-30', 1));
        $this->assertSame('2026-10-17', Pengunjung::geser('2026-11-15', -29));
        $this->assertSame('2026-11-15', Pengunjung::geser('2026-11-15', 0));
    }
}
