<?php

use App\Models\JadwalModel;
use App\Models\TautanModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aturan murni F2-06 tanpa basis data: alamat web untuk media sosial dan
 * tautan jadwal (`TautanModel::rapikanAlamatWeb()`), ikon dan tampilan ringkas
 * alamat, serta pembacaan waktu jadwal (`JadwalModel::bacaWaktu()`).
 *
 * @internal
 */
final class KontakAlamatWebTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function alamatSah(): iterable
    {
        yield 'https lengkap' => ['https://www.instagram.com/milklife', 'https://www.instagram.com/milklife'];
        yield 'http' => ['http://contoh.sch.id/jadwal', 'http://contoh.sch.id/jadwal'];
        yield 'tanpa awalan' => ['instagram.com/milklife', 'https://instagram.com/milklife'];
        yield 'tanpa awalan dengan www' => ['www.youtube.com/@milklife', 'https://www.youtube.com/@milklife'];
        yield 'awalan huruf besar' => ['HTTPS://zoom.us/j/1234567890?pwd=abc', 'https://zoom.us/j/1234567890?pwd=abc'];
        yield 'spasi di tepi' => ['  https://meet.google.com/abc-defg-hij  ', 'https://meet.google.com/abc-defg-hij'];
        yield 'awalan dua garis miring' => ['//tiktok.com/@milklife', 'https://tiktok.com/@milklife'];
        yield 'dengan port' => ['contoh.id:8080/jadwal', 'https://contoh.id:8080/jadwal'];
        yield 'dengan pagar' => ['https://contoh.id/halaman#jadwal', 'https://contoh.id/halaman#jadwal'];
    }

    #[DataProvider('alamatSah')]
    public function testAlamatSahDirapikan(string $isian, string $harapan): void
    {
        $this->assertSame($harapan, TautanModel::rapikanAlamatWeb($isian));
    }

    /**
     * @return iterable<string, array{string|null}>
     */
    public static function alamatTidakSah(): iterable
    {
        yield 'kosong' => [''];
        yield 'null' => [null];
        yield 'hanya spasi' => ['   '];
        yield 'javascript' => ['javascript:alert(1)'];
        yield 'javascript berhuruf besar' => ['JavaScript:alert(1)'];
        yield 'data' => ['data:text/html,<b>hai</b>'];
        yield 'mailto' => ['mailto:panitia@contoh.id'];
        yield 'ftp' => ['ftp://contoh.id/berkas'];
        yield 'bukan nama situs' => ['milklife'];
        yield 'tanpa titik' => ['https://localhost/jadwal'];
        yield 'spasi di tengah' => ['https://contoh.id/jadwal lomba'];
        yield 'baris baru' => ["https://contoh.id/\njadwal"];
        yield 'nama pengguna' => ['https://admin:rahasia@contoh.id/'];
        yield 'nama situs rusak' => ['https://contoh..id/'];
        yield 'tanda hubung di tepi' => ['https://-contoh.id/'];
        yield 'hanya awalan' => ['https://'];
        yield 'terlalu panjang' => ['https://contoh.id/' . str_repeat('a', 240)];
    }

    #[DataProvider('alamatTidakSah')]
    public function testAlamatTidakSahDitolak(?string $isian): void
    {
        $this->assertNull(TautanModel::rapikanAlamatWeb($isian));
    }

    public function testAlamatTepat255KarakterDiterima(): void
    {
        $alamat = 'https://contoh.id/' . str_repeat('a', 255 - strlen('https://contoh.id/'));

        $this->assertSame(255, strlen($alamat));
        $this->assertSame($alamat, TautanModel::rapikanAlamatWeb($alamat));
    }

    public function testIkonMediaSosialMenurutSitus(): void
    {
        $this->assertSame('instagram', TautanModel::ikonMediaSosial('https://www.instagram.com/milklife'));
        $this->assertSame('youtube', TautanModel::ikonMediaSosial('https://youtu.be/abc'));
        $this->assertSame('youtube', TautanModel::ikonMediaSosial('https://m.youtube.com/@milklife'));
        $this->assertSame('tiktok', TautanModel::ikonMediaSosial('https://www.tiktok.com/@milklife'));
        $this->assertSame('twitter-x', TautanModel::ikonMediaSosial('https://x.com/milklife'));
        $this->assertSame('facebook', TautanModel::ikonMediaSosial('https://web.facebook.com/milklife'));
        $this->assertSame('globe2', TautanModel::ikonMediaSosial('https://bukaninstagram.com/milklife'));
        $this->assertSame('globe2', TautanModel::ikonMediaSosial('https://contoh.sch.id'));
        $this->assertSame('globe2', TautanModel::ikonMediaSosial(null));
    }

    public function testAlamatRingkas(): void
    {
        $this->assertSame('instagram.com/milklife', TautanModel::alamatRingkas('https://www.instagram.com/milklife/'));
        $this->assertSame('zoom.us/j/123', TautanModel::alamatRingkas('http://zoom.us/j/123'));
    }

    public function testJenisSah(): void
    {
        $this->assertTrue(TautanModel::jenisSah('narahubung'));
        $this->assertTrue(TautanModel::jenisSah('media_sosial'));
        $this->assertFalse(TautanModel::jenisSah('media-sosial'));
        $this->assertFalse(TautanModel::jenisSah(null));
        $this->assertFalse(TautanModel::jenisSah(['narahubung']));
    }

    /**
     * @return iterable<string, array{string|null, string|null}>
     */
    public static function waktuJadwal(): iterable
    {
        yield 'kotak datetime-local' => ['2026-11-20T09:00', '2026-11-20 09:00:00'];
        yield 'dengan detik' => ['2026-11-20T09:00:45', '2026-11-20 09:00:00'];
        yield 'dengan spasi' => ['2026-11-20 13:30', '2026-11-20 13:30:00'];
        yield 'kosong berarti belum pasti' => ['', ''];
        yield 'null berarti belum pasti' => [null, ''];
        yield 'tanggal tidak ada' => ['2026-02-30T09:00', null];
        yield 'jam tidak ada' => ['2026-11-20T25:00', null];
        yield 'tanpa jam' => ['2026-11-20', null];
        yield 'bentuk lain' => ['20/11/2026 09.00', null];
        yield 'tahun terlalu jauh' => ['1999-11-20T09:00', null];
        yield 'teks' => ['besok pagi', null];
    }

    #[DataProvider('waktuJadwal')]
    public function testBacaWaktuJadwal(?string $isian, ?string $harapan): void
    {
        $this->assertSame($harapan, JadwalModel::bacaWaktu($isian));
    }

    public function testWaktuFormulir(): void
    {
        $this->assertSame('2026-11-20T09:00', JadwalModel::waktuFormulir('2026-11-20 09:00:00'));
        $this->assertSame('', JadwalModel::waktuFormulir(null));
        $this->assertSame('', JadwalModel::waktuFormulir('rusak'));
    }
}
