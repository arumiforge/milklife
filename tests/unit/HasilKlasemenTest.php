<?php

use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Klasemen sekolah per jenjang (F8-05, K124 butir 4–6): juara I, lalu II,
 * lalu III, peringkat bersama, kontingen per NPSN, piala sama untuk seri.
 * Metode poin per peringkat (F9-11, K140 butir 7a): poin, lalu juara I, II, III.
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

    public function testKolomPoinSelaluAdaDiMetodeMedali(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1'), self::piala(5, 'SD A', '1'),
            self::piala(2, 'SD B', '2'), self::piala(2, 'SD B', '2'), self::piala(4, 'SD B', '2'),
            self::piala(4, 'SD Tanpa Piala', '3'),
        ])['A']['baris'];

        // Medali tetap juknis: juara I lebih dulu walau poin B lebih besar; sekolah tanpa piala tidak tampil.
        $this->assertSame(['SD A', 'SD B'], array_column($baris, 'sekolah'));
        $this->assertSame([7 + 2, 5 + 5 + 3], array_column($baris, 'poin'));
        $this->assertSame([1, 2], array_column($baris, 'jumlah'));
        $this->assertSame(Klasemen::hitung([self::piala(1, 'SD A', '1')]), Klasemen::hitung([self::piala(1, 'SD A', '1')], 'tidak-dikenal'));
    }

    public function testMetodePoinUrutPoinLaluJuaraILaluIILaluIII(): void
    {
        $baris = Klasemen::hitung([
            // A: 1 emas = 7. B: 2 perak + 4 = 5+5+3 = 13. C: 3 + 3 + 3 + 3 (peringkat 3 empat kali) = 16.
            self::piala(1, 'SD A', '1'),
            self::piala(2, 'SD B', '2'), self::piala(2, 'SD B', '2'), self::piala(4, 'SD B', '2'),
            self::piala(3, 'SD C', '3'), self::piala(3, 'SD C', '3'), self::piala(3, 'SD C', '3'), self::piala(3, 'SD C', '3'),
            // D: 7 poin seperti A, tanpa juara I (peringkat 2 + 6 + 6 = 5+1+1).
            self::piala(2, 'SD D', '4'), self::piala(6, 'SD D', '4'), self::piala(6, 'SD D', '4'),
            // E: hanya peringkat 4–6, tanpa piala, tetap tampil di metode poin.
            self::piala(4, 'SD E', '5'), self::piala(5, 'SD E', '5'),
            // F: hanya peringkat 7–8 = 0 poin bawaan, tidak tampil.
            self::piala(7, 'SD F', '6'), self::piala(8, 'SD F', '6'),
        ], Klasemen::POIN)['A']['baris'];

        $this->assertSame(['SD C', 'SD B', 'SD A', 'SD D', 'SD E'], array_column($baris, 'sekolah'));
        $this->assertSame([16, 13, 7, 7, 5], array_column($baris, 'poin'));
        // A dan D sama-sama 7 poin: A punya juara I.
        $this->assertSame([1, 2, 3, 4, 5], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2, 3, null, null], array_column($baris, 'juara_umum'));
        $this->assertSame([], $baris[4]['piala']);
        $this->assertSame(0, $baris[4]['jumlah']);
    }

    public function testMetodePoinPemecahSeriDanPeringkatBersama(): void
    {
        // Poin diubah admin: 10-6-6-3, peringkat 5–8 tanpa poin.
        $poin  = [10, 6, 6, 3, 0, 0, 0, 0];
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1'), self::piala(5, 'SD A', '1'), self::piala(4, 'SD A', '1'), // 13 poin
            self::piala(2, 'SD B', '2'), self::piala(3, 'SD B', '2'), self::piala(4, 'SD B', '2'), // 15: 1 perak, 1 perunggu
            self::piala(3, 'SD C', '3'), self::piala(3, 'SD C', '3'), self::piala(4, 'SD C', '3'), // 15: 2 perunggu
            self::piala(2, 'SD D', '4'), self::piala(2, 'SD D', '4'), self::piala(4, 'SD D', '4'), // 15: 2 perak
            self::piala(2, 'SD E', '5'), self::piala(3, 'SD E', '5'), self::piala(4, 'SD E', '5'), // 15: sama persis dengan B
        ], Klasemen::POIN, $poin)['A']['baris'];

        $this->assertSame(['SD D', 'SD B', 'SD E', 'SD C', 'SD A'], array_column($baris, 'sekolah'));
        $this->assertSame([15, 15, 15, 15, 13], array_column($baris, 'poin'));
        $this->assertSame([1, 2, 2, 4, 5], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2, 2, null, null], array_column($baris, 'juara_umum'));
    }

    public function testMetodePoinPerJenjangDanDaftarPoinPendek(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 'A'),
            self::piala(8, 'SMP A', '7', 'B'),
            self::piala(2, 'SMP B', '8', 'B'),
            self::piala(9, 'SMP C', '9', 'B'),
        ], Klasemen::POIN, [3, 2, 1]);

        // Peringkat di luar daftar poin = 0 poin; sekolah tanpa piala dan tanpa poin tidak tampil.
        $this->assertSame(['A', 'B'], array_keys($klasemen));
        $this->assertSame(['SMP B'], array_column($klasemen['B']['baris'], 'sekolah'));
        $this->assertSame([2], array_column($klasemen['B']['baris'], 'poin'));
        $this->assertSame([3], array_column($klasemen['A']['baris'], 'poin'));
    }

    public function testKeteranganMengikutiMetode(): void
    {
        $this->assertStringContainsString('juara I terbanyak', Klasemen::keterangan(Klasemen::MEDALI));
        $this->assertStringNotContainsString('poin', Klasemen::keterangan(Klasemen::MEDALI));
        $this->assertSame(
            'Juara umum ditentukan jumlah poin terbanyak (peringkat 1 = 7, 2 = 5, 3 = 4, 4 = 3, 5 = 2, 6 = 1 poin); bila sama, juara I terbanyak, lalu juara II, lalu juara III; bila masih sama, peringkat bersama.',
            Klasemen::keterangan(Klasemen::POIN, Klasemen::POIN_BAWAAN),
        );
        $this->assertSame('semua peringkat 0 poin', Klasemen::teksPoin([0, 0, 0, 0, 0, 0, 0, 0]));
    }

    public function testPeriksaIsianPoin(): void
    {
        $this->assertSame([], MetodeKlasemen::periksa('poin', ['10', '8', '6', '5', '4', '3', '2', '1']));
        $this->assertSame([], MetodeKlasemen::periksa('medali', ['7', '5', '4', '3', '2', '1', '0', '0']));
        $this->assertArrayHasKey('metode', MetodeKlasemen::periksa('juara', ['7', '5', '4', '3', '2', '1', '0', '0']));
        $this->assertArrayHasKey('poin', MetodeKlasemen::periksa('poin', ['7', '5']));
        $this->assertSame(['poin_3'], array_keys(MetodeKlasemen::periksa('poin', ['7', '5', 'x', '3', '2', '1', '0', '0'])));
        $this->assertSame(['poin_2'], array_keys(MetodeKlasemen::periksa('poin', ['7', '1000', '4', '3', '2', '1', '0', '0'])));
        $this->assertSame(['poin_4'], array_keys(MetodeKlasemen::periksa('poin', ['7', '5', '4', '6', '2', '1', '0', '0'])));
        $this->assertSame(['poin_1'], array_keys(MetodeKlasemen::periksa('poin', ['0', '0', '0', '0', '0', '0', '0', '0'])));
        $this->assertSame([7, 5, 4, 3, 2, 1, 0, 0], MetodeKlasemen::urai('7,5,4,3,2,1,0,0'));
        $this->assertNull(MetodeKlasemen::urai('7,5,4'));
        $this->assertNull(MetodeKlasemen::urai('7,5,4,3,2,1,0,-1'));
    }
}
