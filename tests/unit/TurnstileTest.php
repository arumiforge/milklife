<?php

use App\Libraries\Turnstile;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Turnstile as KonfigurasiTurnstile;
use Tests\Support\TiruanSiteverify;

/**
 * Pemeriksa token Cloudflare Turnstile (K122) dengan kunci uji resmi
 * Cloudflare dan tiruan siteverify, tanpa panggilan jaringan.
 *
 * @internal
 */
final class TurnstileTest extends CIUnitTestCase
{
    private static function konfigurasi(string $rahasia = TiruanSiteverify::RAHASIA_LOLOS, string $situs = TiruanSiteverify::SITUS_LOLOS, string $namaHost = 'example.com, Daftar.Contoh.sch.id'): KonfigurasiTurnstile
    {
        $k               = new KonfigurasiTurnstile();
        $k->kunciSitus   = $situs;
        $k->kunciRahasia = $rahasia;
        $k->namaHost     = $namaHost;

        return $k;
    }

    private static function tiruan(string $aksi = Turnstile::AKSI_MASUK, string $namaHost = 'example.com'): TiruanSiteverify
    {
        $t           = new TiruanSiteverify();
        $t->aksi     = $aksi;
        $t->namaHost = $namaHost;

        return $t;
    }

    public function testKunciKosongMematikanTurnstile(): void
    {
        foreach ([['', TiruanSiteverify::SITUS_LOLOS], [TiruanSiteverify::RAHASIA_LOLOS, ''], ['  ', '  ']] as [$rahasia, $situs]) {
            $tiruan    = self::tiruan();
            $turnstile = new Turnstile(self::konfigurasi($rahasia, $situs), $tiruan);

            $this->assertFalse($turnstile->aktif());
            $this->assertNull($turnstile->untukHalaman(Turnstile::AKSI_MASUK));
            $this->assertNull($turnstile->periksa(null, Turnstile::AKSI_MASUK, '203.0.113.5'));
            $this->assertSame([], $tiruan->kiriman, 'Tanpa panggilan ke Cloudflare.');
        }
    }

    public function testEnvContohMematikanTurnstile(): void
    {
        // .env.example: kunci situs publik terisi, kunci rahasia kosong.
        $isi = (string) file_get_contents(ROOTPATH . '.env.example');

        $this->assertMatchesRegularExpression('/^turnstile\.kunciSitus = 0x4AAAAAAGFuKUT0k64YcOX5$/m', $isi);
        $this->assertMatchesRegularExpression('/^turnstile\.kunciRahasia =\s*$/m', $isi);
    }

    public function testTokenLolos(): void
    {
        $tiruan    = self::tiruan();
        $turnstile = new Turnstile(self::konfigurasi(), $tiruan);

        $this->assertTrue($turnstile->aktif());
        $this->assertSame(['kunci_situs' => TiruanSiteverify::SITUS_LOLOS, 'aksi' => 'masuk_admin', 'skrip' => 'https://challenges.cloudflare.com/turnstile/v0/api.js'], $turnstile->untukHalaman(Turnstile::AKSI_MASUK));
        $this->assertNull($turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));

        // Satu POST berisi kunci rahasia, token, dan alamat IP pengunjung; batas waktu 10 detik.
        $this->assertSame([['secret' => TiruanSiteverify::RAHASIA_LOLOS, 'response' => TiruanSiteverify::TOKEN, 'remoteip' => '203.0.113.5']], $tiruan->kiriman);
        $this->assertSame('https://challenges.cloudflare.com/turnstile/v0/siteverify', $tiruan->pilihan[0][CURLOPT_URL]);
        $this->assertSame(10000, (int) $tiruan->pilihan[0][CURLOPT_TIMEOUT_MS]);
        $this->assertSame(10000, (int) $tiruan->pilihan[0][CURLOPT_CONNECTTIMEOUT_MS]);
    }

    public function testNamaHostDiizinkanTanpaMembedakanHurufBesar(): void
    {
        $turnstile = new Turnstile(self::konfigurasi(), self::tiruan(Turnstile::AKSI_CEK, 'daftar.contoh.sch.id'));

        $this->assertSame(['example.com', 'daftar.contoh.sch.id'], $turnstile->namaHost());
        $this->assertNull($turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_CEK, '203.0.113.5'));
    }

    public function testNamaHostKosongMemakaiBaseUrl(): void
    {
        $turnstile = new Turnstile(self::konfigurasi(namaHost: ''), self::tiruan());

        $this->assertSame([parse_url(config('App')->baseURL, PHP_URL_HOST)], $turnstile->namaHost());
    }

    public function testKunciRahasiaSelaluGagal(): void
    {
        $turnstile = new Turnstile(self::konfigurasi(TiruanSiteverify::RAHASIA_GAGAL), self::tiruan());

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testTokenSudahTerpakai(): void
    {
        $turnstile = new Turnstile(self::konfigurasi(TiruanSiteverify::RAHASIA_TERPAKAI), self::tiruan());

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testAksiTidakCocokDitolak(): void
    {
        // Token dari formulir Cek Pendaftaran dipakai untuk masuk admin.
        $turnstile = new Turnstile(self::konfigurasi(), self::tiruan(Turnstile::AKSI_CEK));

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testJawabanKunciUjiAsliTanpaAksiDitolak(): void
    {
        // Kunci uji Cloudflare tidak mengembalikan `action`, jadi tidak bisa lolos pemeriksaan aksi.
        $turnstile = new Turnstile(self::konfigurasi(), self::tiruan(''));

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testNamaHostTidakDiizinkanDitolak(): void
    {
        $turnstile = new Turnstile(self::konfigurasi(), self::tiruan(Turnstile::AKSI_MASUK, 'situs-lain.contoh'));

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testTokenKosongBukanTeksAtauTerlaluPanjangDitolakTanpaMenghubungiCloudflare(): void
    {
        $tiruan    = self::tiruan();
        $turnstile = new Turnstile(self::konfigurasi(), $tiruan);

        foreach ([null, '', '   ', ['token'], 123, true] as $token) {
            $this->assertSame(Turnstile::PESAN_BELUM, $turnstile->periksa($token, Turnstile::AKSI_MASUK, '203.0.113.5'));
        }

        $this->assertSame(Turnstile::PESAN_GAGAL, $turnstile->periksa(str_repeat('a', 2049), Turnstile::AKSI_MASUK, '203.0.113.5'));
        $this->assertSame([], $tiruan->kiriman);

        // Tepat 2048 huruf masih dikirim ke Cloudflare.
        $this->assertNull($turnstile->periksa(str_repeat('a', 2048), Turnstile::AKSI_MASUK, '203.0.113.5'));
        $this->assertCount(1, $tiruan->kiriman);
    }

    public function testCloudflareTidakBisaDihubungi(): void
    {
        $tiruan        = self::tiruan();
        $tiruan->putus = true;
        $turnstile     = new Turnstile(self::konfigurasi(), $tiruan);

        $this->assertSame(Turnstile::PESAN_TERPUTUS, $turnstile->periksa(TiruanSiteverify::TOKEN, Turnstile::AKSI_MASUK, '203.0.113.5'));
    }

    public function testPesanBerbahasaIndonesiaMenyebutCaraMemperbaiki(): void
    {
        foreach ([Turnstile::PESAN_BELUM, Turnstile::PESAN_GAGAL, Turnstile::PESAN_TERPUTUS] as $pesan) {
            $this->assertMatchesRegularExpression('/(lalu coba lagi|muat ulang)/i', $pesan);
        }
    }

    public function testAksiSesuaiAturanCloudflare(): void
    {
        foreach ([Turnstile::AKSI_PENDAFTARAN, Turnstile::AKSI_CEK, Turnstile::AKSI_MASUK] as $aksi) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{1,32}$/D', $aksi);
        }
    }
}
