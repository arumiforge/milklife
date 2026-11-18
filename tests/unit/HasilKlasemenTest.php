<?php

use App\Libraries\Hasil\Klasemen;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Klasemen sekolah per jenjang (F8-05, K124 butir 4–6): juara I, lalu II,
 * lalu III, peringkat bersama, kontingen per NPSN, piala sama untuk seri.
 *
 * @internal
 */
final class HasilKlasemenTest extends CIUnitTestCase
{
    /**
     * @return array<string, mixed>
     */
    private static function piala(int $peringkat, string $sekolah, ?string $npsn, string $jenjang = 'A', int $pendaftaran = 1, string $nomor = 'Lari 60 m putra'): array
    {
        return ['peringkat' => $peringkat, 'nama' => 'Atlet', 'nomor' => $nomor, 'kelompok_umur' => 'KU 10', 'sekolah' => $sekolah, 'npsn' => $npsn, 'pendaftaran_id' => $pendaftaran, 'jenjang' => $jenjang];
    }

    public function testJuaraISatuSaja(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(2, 'SD B', '2'), self::piala(2, 'SD B', '2'), self::piala(3, 'SD B', '2'),
            self::piala(1, 'SD A', '1'),
        ]);

        $baris = $klasemen['A']['baris'];
        $this->assertSame(['SD A', 'SD B'], array_column($baris, 'sekolah'));
        $this->assertSame([1, 2], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2], array_column($baris, 'juara_umum'));
        $this->assertSame([1, 0, 0, 1], [$baris[0]['emas'], $baris[0]['perak'], $baris[0]['perunggu'], $baris[0]['jumlah']]);
        $this->assertSame([0, 2, 1, 3], [$baris[1]['emas'], $baris[1]['perak'], $baris[1]['perunggu'], $baris[1]['jumlah']]);
        $this->assertSame('SD/MI', $klasemen['A']['label']);
    }

    public function testPemecahSeriJuaraIILaluIIILaluBersama(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1'), self::piala(3, 'SD A', '1'),
            self::piala(1, 'SD B', '2'), self::piala(2, 'SD B', '2'),
            self::piala(1, 'SD D', '4'), self::piala(3, 'SD D', '4'),
            self::piala(3, 'SD C', '3'),
            self::piala(3, 'SD E', '5'),
        ])['A']['baris'];

        $this->assertSame(['SD B', 'SD A', 'SD D', 'SD C', 'SD E'], array_column($baris, 'sekolah'));
        // A dan D sama persis: peringkat bersama 2; C dan E bersama 4.
        $this->assertSame([1, 2, 2, 4, 4], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2, 2, null, null], array_column($baris, 'juara_umum'));
    }

    public function testKontingenPerNpsnDanTanpaNpsnSendiri(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 'A', 10),
            self::piala(1, 'SD A', '1', 'A', 11),
            self::piala(2, 'SD Tanpa', null, 'A', 20),
            self::piala(2, 'SD Tanpa', null, 'A', 21),
        ])['A']['baris'];

        $this->assertSame(['SD A', 'SD Tanpa', 'SD Tanpa'], array_column($baris, 'sekolah'));
        $this->assertSame([2, 1, 1], array_column($baris, 'jumlah'));
        $this->assertSame([1, 2, 2], array_column($baris, 'peringkat'));
    }

    public function testTerpisahPerJenjangDanPialaUrut(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(3, 'SMP A', '7', 'B', 1, 'Lari 80 m putri'),
            self::piala(1, 'SD A', '1', 'A'),
            self::piala(1, 'SMP A', '7', 'B', 1, 'Lompat Jauh putri'),
            self::piala(4, 'SD Lain', '9', 'A'),
        ]);

        $this->assertSame(['A', 'B'], array_keys($klasemen));
        $this->assertCount(1, $klasemen['A']['baris']);
        $this->assertSame(['Lompat Jauh putri', 'Lari 80 m putri'], array_column($klasemen['B']['baris'][0]['piala'], 'nomor'));
        $this->assertSame([], Klasemen::hitung([]));
    }
}
