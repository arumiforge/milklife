<?php

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\Prestasi;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Penguraian dan penulisan prestasi, urutan peringkat, dan tebakan jenis
 * hasil (F8-04, K124).
 *
 * @internal
 */
final class HasilPrestasiPeringkatTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: int}>
     */
    public static function prestasiBenar(): iterable
    {
        yield 'waktu detik dan perseratus' => ['12,34', 'waktu', 1234];
        yield 'waktu titik desimal' => ['9.87', 'waktu', 987];
        yield 'waktu satu angka desimal' => ['12,3', 'waktu', 1230];
        yield 'waktu tanpa desimal' => ['65', 'waktu', 6500];
        yield 'waktu menit' => ['2:05,10', 'waktu', 12510];
        yield 'waktu menit tanpa desimal' => ['12:00', 'waktu', 72000];
        yield 'waktu berspasi' => [' 1:02, 5 ', 'waktu', 6250];
        yield 'jarak meter' => ['3,45', 'jarak', 345];
        yield 'jarak satu desimal' => ['12,5', 'jarak', 1250];
        yield 'jarak bulat' => ['4', 'jarak', 400];
        yield 'poin' => ['1250', 'poin', 1250];
    }

    /**
     * @dataProvider prestasiBenar
     */
    public function testPrestasiBenarDiurai(string $teks, string $jenis, int $nilai): void
    {
        $this->assertSame(['nilai' => $nilai, 'galat' => null], Prestasi::urai($teks, $jenis));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function prestasiSalah(): iterable
    {
        yield 'waktu huruf' => ['12 detik', 'waktu'];
        yield 'waktu detik 60 di menit' => ['1:60,00', 'waktu'];
        yield 'waktu tiga desimal' => ['12,345', 'waktu'];
        yield 'waktu nol' => ['0,00', 'waktu'];
        yield 'waktu terlalu lama' => ['100:00', 'waktu'];
        yield 'jarak bersatuan' => ['3 m', 'jarak'];
        yield 'jarak menit' => ['1:20', 'jarak'];
        yield 'jarak terlalu jauh' => ['100,00', 'jarak'];
        yield 'poin desimal' => ['12,5', 'poin'];
        yield 'poin negatif' => ['-5', 'poin'];
    }

    /**
     * @dataProvider prestasiSalah
     */
    public function testPrestasiSalahMenyebutContoh(string $teks, string $jenis): void
    {
        $hasil = Prestasi::urai($teks, $jenis);
        $this->assertNull($hasil['nilai']);
        $this->assertStringContainsString('misalnya', (string) $hasil['galat']);
    }

    public function testPrestasiKosongBukanGalat(): void
    {
        $this->assertSame(['nilai' => null, 'galat' => null], Prestasi::urai('  ', 'waktu'));
    }

    public function testPrestasiDitulis(): void
    {
        $this->assertSame('9,87', Prestasi::tulis(987, 'waktu'));
        $this->assertSame('12,30', Prestasi::tulis(1230, 'waktu'));
        $this->assertSame('1:00,00', Prestasi::tulis(6000, 'waktu'));
        $this->assertSame('2:05,10', Prestasi::tulis(12510, 'waktu'));
        $this->assertSame('3,05', Prestasi::tulis(305, 'jarak'));
        $this->assertSame('1250', Prestasi::tulis(1250, 'poin'));
        $this->assertSame('', Prestasi::tulis(null, 'jarak'));

        // Tulis lalu urai menghasilkan angka yang sama.
        foreach ([[12510, 'waktu'], [987, 'waktu'], [345, 'jarak']] as [$nilai, $jenis]) {
            $this->assertSame($nilai, Prestasi::urai(Prestasi::tulis($nilai, $jenis), $jenis)['nilai']);
        }
    }

    public function testPeringkatWaktuMakinKecilMakinBaikDanSeri(): void
    {
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 1250, 'keterangan' => null],
            'b' => ['prestasi' => 1210, 'keterangan' => null],
            'c' => ['prestasi' => 1250, 'keterangan' => null],
            'd' => ['prestasi' => 1300, 'keterangan' => null],
            'e' => ['prestasi' => null, 'keterangan' => 'DNS'],
            'f' => ['prestasi' => 1100, 'keterangan' => 'DQ'],
            'g' => ['prestasi' => null, 'keterangan' => null],
        ], JenisHasil::WAKTU);

        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 2, 'd' => 4, 'e' => null, 'f' => null, 'g' => null], $hasil);
    }

    public function testPeringkatJarakDanPoinMakinBesarMakinBaik(): void
    {
        $peserta = [
            1 => ['prestasi' => 345, 'keterangan' => null],
            2 => ['prestasi' => 410, 'keterangan' => null],
            3 => ['prestasi' => 299, 'keterangan' => 'DNF'],
        ];

        $this->assertSame([2 => 1, 1 => 2, 3 => null], Peringkat::hitung($peserta, JenisHasil::JARAK));
        $this->assertSame([2 => 1, 1 => 2, 3 => null], Peringkat::hitung($peserta, JenisHasil::POIN));
    }

    public function testPeringkatKoreksiAdminTetap(): void
    {
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 1210, 'keterangan' => null, 'peringkat' => 2, 'manual' => true],
            'b' => ['prestasi' => 1210, 'keterangan' => null],
            'c' => ['prestasi' => 1300, 'keterangan' => null],
        ], JenisHasil::WAKTU);

        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 2], $hasil);
    }

    public function testKeteranganSah(): void
    {
        $this->assertSame('DNS', Peringkat::keterangan(' dns '));
        $this->assertSame('DQ', Peringkat::keterangan('DQ'));
        $this->assertNull(Peringkat::keterangan('X'));
        $this->assertNull(Peringkat::keterangan(null));
    }

    public function testJenisHasilDitebakDariNama(): void
    {
        $this->assertSame('waktu', JenisHasil::tebak('Lari 60 m'));
        $this->assertSame('waktu', JenisHasil::tebak('Estafet 8 × 50 m'));
        $this->assertSame('waktu', JenisHasil::tebak("Kanga's Escape"));
        $this->assertSame('waktu', JenisHasil::tebak('Formula 1'));
        $this->assertSame('waktu', JenisHasil::tebak('Jalan Cepat 2000 m'));
        $this->assertSame('jarak', JenisHasil::tebak('Frog Jump'));
        $this->assertSame('jarak', JenisHasil::tebak('Turbo Throw'));
        $this->assertSame('jarak', JenisHasil::tebak('Lompat Tinggi'));
        $this->assertSame('jarak', JenisHasil::tebak('Tolak Peluru'));
        $this->assertSame('jarak', JenisHasil::tebak('Lempar Cakram'));
        $this->assertSame('poin', JenisHasil::tebak('Trilomba'));
    }
}
