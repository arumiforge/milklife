<?php

use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Klasemen sekolah per kelompok umur (F8-05, K124 butir 4–6; F11-02, K172 butir 2): juara I, lalu II,
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
    private static function piala(int $peringkat, string $sekolah, ?string $npsn, int $ku = 1, int $pendaftaran = 1, string $nomor = 'Lari 60 m putra'): array
    {
        return ['peringkat' => $peringkat, 'nama' => 'Atlet', 'nomor' => $nomor, 'kelompok_umur_id' => $ku, 'kelompok_umur' => self::KU[$ku], 'sekolah' => $sekolah, 'npsn' => $npsn, 'pendaftaran_id' => $pendaftaran];
    }

    /**
     * @var array<int, string>
     */
    private const KU = [1 => 'KU 8', 2 => 'KU 10', 3 => 'KU 12', 4 => 'KU 15'];

    /**
     * @return list<array{id: int, nama: string}>
     */
    private static function daftarKu(): array
    {
        return array_map(static fn (int $id, string $nama): array => ['id' => $id, 'nama' => $nama], array_keys(self::KU), self::KU);
    }

    public function testJuaraISatuSaja(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(2, 'SD B', '2'), self::piala(2, 'SD B', '2'), self::piala(3, 'SD B', '2'),
            self::piala(1, 'SD A', '1'),
        ]);

        $baris = $klasemen['ku-1']['baris'];
        $this->assertSame(['SD A', 'SD B'], array_column($baris, 'sekolah'));
        $this->assertSame([1, 2], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2], array_column($baris, 'juara_umum'));
        $this->assertSame([1, 0, 0, 1], [$baris[0]['emas'], $baris[0]['perak'], $baris[0]['perunggu'], $baris[0]['jumlah']]);
        $this->assertSame([0, 2, 1, 3], [$baris[1]['emas'], $baris[1]['perak'], $baris[1]['perunggu'], $baris[1]['jumlah']]);
        $this->assertSame('KU 8', $klasemen['ku-1']['label']);
    }

    public function testPemecahSeriJuaraIILaluIIILaluBersama(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1'), self::piala(3, 'SD A', '1'),
            self::piala(1, 'SD B', '2'), self::piala(2, 'SD B', '2'),
            self::piala(1, 'SD D', '4'), self::piala(3, 'SD D', '4'),
            self::piala(3, 'SD C', '3'),
            self::piala(3, 'SD E', '5'),
        ])['ku-1']['baris'];

        $this->assertSame(['SD B', 'SD A', 'SD D', 'SD C', 'SD E'], array_column($baris, 'sekolah'));
        // A dan D sama persis: peringkat bersama 2; C dan E bersama 4.
        $this->assertSame([1, 2, 2, 4, 4], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2, 2, null, null], array_column($baris, 'juara_umum'));
    }

    public function testKontingenPerNpsnDanTanpaNpsnSendiri(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 1, 10),
            self::piala(1, 'SD A', '1', 1, 11),
            self::piala(2, 'SD Tanpa', null, 1, 20),
            self::piala(2, 'SD Tanpa', null, 1, 21),
        ])['ku-1']['baris'];

        $this->assertSame(['SD A', 'SD Tanpa', 'SD Tanpa'], array_column($baris, 'sekolah'));
        $this->assertSame([2, 1, 1], array_column($baris, 'jumlah'));
        $this->assertSame([1, 2, 2], array_column($baris, 'peringkat'));
    }

    public function testTerpisahPerKelompokUmurDanPialaUrut(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 1),
            self::piala(3, 'SD B', '7', 2, 1, 'Lari 80 m putri'),
            self::piala(1, 'SD B', '7', 2, 1, 'Lompat Jauh putri'),
            self::piala(4, 'SD Lain', '9', 1),
        ]);

        $this->assertSame(['ku-1', 'ku-2'], array_keys($klasemen));
        $this->assertSame(['ku-1', 'KU 8'], [$klasemen['ku-1']['kunci'], $klasemen['ku-1']['label']]);
        $this->assertCount(1, $klasemen['ku-1']['baris']);
        $this->assertSame(['Lompat Jauh putri', 'Lari 80 m putri'], array_column($klasemen['ku-2']['baris'][0]['piala'], 'nomor'));
        $this->assertSame([], Klasemen::hitung([]));
    }

    public function testKolomPoinSelaluAdaDiMetodeMedali(): void
    {
        $baris = Klasemen::hitung([
            self::piala(1, 'SD A', '1'), self::piala(5, 'SD A', '1'),
            self::piala(2, 'SD B', '2'), self::piala(2, 'SD B', '2'), self::piala(4, 'SD B', '2'),
            self::piala(4, 'SD Tanpa Piala', '3'),
        ])['ku-1']['baris'];

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
        ], Klasemen::POIN)['ku-1']['baris'];

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
        ], Klasemen::POIN, $poin)['ku-1']['baris'];

        $this->assertSame(['SD D', 'SD B', 'SD E', 'SD C', 'SD A'], array_column($baris, 'sekolah'));
        $this->assertSame([15, 15, 15, 15, 13], array_column($baris, 'poin'));
        $this->assertSame([1, 2, 2, 4, 5], array_column($baris, 'peringkat'));
        $this->assertSame([1, 2, 2, null, null], array_column($baris, 'juara_umum'));
    }

    public function testMetodePoinPerKelompokUmurDanDaftarPoinPendek(): void
    {
        $klasemen = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 1),
            self::piala(8, 'SMP A', '7', 2),
            self::piala(2, 'SMP B', '8', 2),
            self::piala(9, 'SMP C', '9', 2),
        ], Klasemen::POIN, [3, 2, 1]);

        // Peringkat di luar daftar poin = 0 poin; sekolah tanpa piala dan tanpa poin tidak tampil.
        $this->assertSame(['ku-1', 'ku-2'], array_keys($klasemen));
        $this->assertSame(['SMP B'], array_column($klasemen['ku-2']['baris'], 'sekolah'));
        $this->assertSame([2], array_column($klasemen['ku-2']['baris'], 'poin'));
        $this->assertSame([3], array_column($klasemen['ku-1']['baris'], 'poin'));
    }

    public function testGabunganKelompokUmurSatuKlasemenDanSisanyaSendiri(): void
    {
        // KU 8 dan KU 10 digabung (seperti Seri 1); KU 12 dan KU 15 tidak masuk gabungan mana pun.
        $susunan = Klasemen::susunan(self::daftarKu(), [[2, 1]]);

        $this->assertSame([
            ['kunci' => 'ku-1-2', 'label' => 'KU 8 dan KU 10', 'kelompok_umur_id' => [1, 2]],
            ['kunci' => 'ku-3', 'label' => 'KU 12', 'kelompok_umur_id' => [3]],
            ['kunci' => 'ku-4', 'label' => 'KU 15', 'kelompok_umur_id' => [4]],
        ], $susunan);

        $klasemen = Klasemen::hitung([
            self::piala(1, 'SD A', '1', 2),
            self::piala(1, 'SD B', '2', 1),
            self::piala(2, 'SD A', '1', 1),
            self::piala(1, 'SD A', '1', 3),
        ], Klasemen::MEDALI, Klasemen::POIN_BAWAAN, $susunan);

        // Sekolah yang sama (NPSN sama) di KU 8 dan KU 10 menjadi satu baris; KU 15 tanpa juara tidak tampil.
        $this->assertSame(['ku-1-2', 'ku-3'], array_keys($klasemen));
        $this->assertSame('KU 8 dan KU 10', $klasemen['ku-1-2']['label']);
        $this->assertSame(['SD A', 'SD B'], array_column($klasemen['ku-1-2']['baris'], 'sekolah'));
        $this->assertSame([1, 1], [$klasemen['ku-1-2']['baris'][0]['emas'], $klasemen['ku-1-2']['baris'][0]['perak']]);
        $this->assertSame(['KU 10', 'KU 8'], array_column($klasemen['ku-1-2']['baris'][0]['piala'], 'kelompok_umur'));
        $this->assertSame(['SD A'], array_column($klasemen['ku-3']['baris'], 'sekolah'));

        // Kelompok umur yang belum ada di susunan (misalnya baru ditambah) tetap punya klasemen sendiri, di akhir.
        $lain = Klasemen::hitung([['peringkat' => 1, 'kelompok_umur_id' => 9, 'kelompok_umur' => 'KU 18', 'sekolah' => 'SMA A', 'npsn' => '5', 'pendaftaran_id' => 5], self::piala(1, 'SD A', '1', 3)], Klasemen::MEDALI, Klasemen::POIN_BAWAAN, $susunan);
        $this->assertSame(['ku-3', 'ku-9'], array_keys($lain));
        $this->assertSame('KU 18', $lain['ku-9']['label']);
    }

    public function testSusunanBawaanDanGabunganTidakSah(): void
    {
        // Bawaan: satu klasemen per kelompok umur.
        $this->assertSame(['ku-1', 'ku-2', 'ku-3', 'ku-4'], array_column(Klasemen::susunan(self::daftarKu()), 'kunci'));

        // Id tidak dikenal diabaikan; gabungan tinggal satu = klasemen biasa; kelompok umur di dua gabungan ikut yang pertama.
        $this->assertSame(['ku-1', 'ku-2', 'ku-3', 'ku-4'], array_column(Klasemen::susunan(self::daftarKu(), [[1, 99]]), 'kunci'));
        $susunan = Klasemen::susunan(self::daftarKu(), [[2, 3, 4], [3, 1]]);
        $this->assertSame(['ku-1', 'ku-2-3-4'], array_column($susunan, 'kunci'));
        $this->assertSame('KU 10, KU 12, dan KU 15', $susunan[1]['label']);
        $this->assertSame([], Klasemen::susunan([], [[1, 2]]));
    }

    public function testTeksSusunan(): void
    {
        $this->assertSame('Klasemen dihitung per kelompok umur.', Klasemen::teksSusunan(Klasemen::susunan(self::daftarKu())));
        $this->assertSame(
            'Klasemen dihitung per kelompok umur. KU 8 dan KU 10 digabung dalam satu klasemen.',
            Klasemen::teksSusunan(Klasemen::susunan(self::daftarKu(), [[1, 2]])),
        );
        $this->assertSame('', Klasemen::gabungNama([]));
    }

    public function testPeriksaIsianGabungan(): void
    {
        $this->assertSame(['galat' => [], 'gabungan' => [[1, 2]]], MetodeKlasemen::periksaGabungan([1 => '1', 2 => '1', 3 => '3', 4 => '4'], [1, 2, 3, 4]));
        $this->assertSame(['galat' => [], 'gabungan' => [[2, 4], [1, 3]]], MetodeKlasemen::periksaGabungan([1 => '3', 2 => '2', 3 => '3', 4 => '2'], [1, 2, 3, 4]));
        $this->assertSame(['galat' => [], 'gabungan' => []], MetodeKlasemen::periksaGabungan([1 => '1', 2 => '2'], [1, 2]));
        $this->assertSame(['klasemen_2', 'klasemen_3'], array_keys(MetodeKlasemen::periksaGabungan([1 => '1', 2 => '5', 3 => 'x'], [1, 2, 3])['galat']));
    }

    public function testKeteranganMengikutiMetode(): void
    {
        $this->assertStringContainsString('juara I terbanyak', Klasemen::keterangan(Klasemen::MEDALI));
        $this->assertStringNotContainsString('poin', Klasemen::keterangan(Klasemen::MEDALI));
        $this->assertSame(
            'Juara umum adalah sekolah dengan poin terbanyak (peringkat 1 = 7, 2 = 5, 3 = 4, 4 = 3, 5 = 2, 6 = 1 poin). Bila sama, dilihat juara I terbanyak, lalu juara II, lalu juara III. Bila masih sama, sekolah itu berbagi peringkat.',
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
