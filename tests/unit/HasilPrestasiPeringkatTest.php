<?php

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\Prestasi;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Pustaka murni hasil lomba (F8-04, K124): jenis hasil dari nama nomor,
 * penguraian dan penulisan prestasi, dan peringkat otomatis.
 *
 * @internal
 */
final class HasilPrestasiPeringkatTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function namaNomor(): array
    {
        return [
            'lari'          => ['Lari 60 m', 'waktu'],
            'jalan cepat'   => ['Jalan Cepat 2000 m', 'waktu'],
            'estafet'       => ['Estafet 8 × 50 m', 'waktu'],
            'beregu lain'   => ["Kanga's Escape", 'waktu'],
            'lompat jauh'   => ['Lompat Jauh', 'jarak'],
            'lompat tinggi' => ['Lompat Tinggi', 'jarak'],
            'lempar'        => ['Lempar Cakram', 'jarak'],
            'tolak'         => ['Tolak Peluru', 'jarak'],
            'frog jump'     => ['Frog Jump', 'jarak'],
            'turbo throw'   => ['Turbo Throw', 'jarak'],
            'trilomba'      => ['Trilomba', 'poin'],
            'tri lomba'     => ['Tri Lomba', 'poin'],
        ];
    }

    #[DataProvider('namaNomor')]
    public function testJenisHasilDariNamaMengikutiJuknis(string $nama, string $jenis): void
    {
        $this->assertSame($jenis, JenisHasil::dariNama($nama));
    }

    /**
     * @return array<string, array{string, string, int}>
     */
    public static function prestasiBenar(): array
    {
        return [
            'waktu menit'          => ['1:05,32', 'waktu', 6532],
            'waktu menit titik'    => ['1:05.32', 'waktu', 6532],
            'waktu menit tanpa ,'  => ['12:30', 'waktu', 75000],
            'waktu detik'          => ['8,45', 'waktu', 845],
            'waktu persepuluh'     => ['12,3', 'waktu', 1230],
            'waktu spasi'          => [' 8, 45 ', 'waktu', 845],
            'jarak'                => ['3,45', 'jarak', 345],
            'jarak persepuluh'     => ['3,5', 'jarak', 350],
            'jarak bulat'          => ['15', 'jarak', 1500],
            'jarak akhiran m'      => ['3,05 m', 'jarak', 305],
            'poin'                 => ['1250', 'poin', 1250],
            'poin nol'             => ['0', 'poin', 0],
        ];
    }

    #[DataProvider('prestasiBenar')]
    public function testPrestasiBenarDiurai(string $teks, string $jenis, int $nilai): void
    {
        $this->assertSame(['nilai' => $nilai, 'galat' => null], Prestasi::urai($teks, $jenis));
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public static function prestasiSalah(): array
    {
        return [
            'waktu huruf'        => ['delapan', 'waktu', '1:05,32'],
            'waktu detik 60'     => ['1:60,00', 'waktu', '8,45'],
            'waktu tiga desimal' => ['8,456', 'waktu', '1:05,32'],
            'waktu nol'          => ['0,00', 'waktu', '8,45'],
            'jarak titik dua'    => ['3:45', 'jarak', '3,45'],
            'jarak nol'          => ['0', 'jarak', '3,45'],
            'poin desimal'       => ['1250,5', 'poin', '1250'],
            'poin ribuan'        => ['1.250', 'poin', '1250'],
        ];
    }

    #[DataProvider('prestasiSalah')]
    public function testPrestasiSalahDitolakDenganContoh(string $teks, string $jenis, string $contoh): void
    {
        $hasil = Prestasi::urai($teks, $jenis);

        $this->assertNull($hasil['nilai']);
        $this->assertNotNull($hasil['galat']);
        $this->assertStringContainsString($contoh, (string) $hasil['galat']);
    }

    public function testPrestasiKosongTanpaGalat(): void
    {
        $this->assertSame(['nilai' => null, 'galat' => null], Prestasi::urai('  ', 'waktu'));
    }

    public function testTulisPrestasi(): void
    {
        $this->assertSame('1:05,32', Prestasi::tulis(6532, 'waktu'));
        $this->assertSame('8,45', Prestasi::tulis(845, 'waktu'));
        $this->assertSame('12:30,00', Prestasi::tulis(75000, 'waktu'));
        $this->assertSame('3,05', Prestasi::tulis(305, 'jarak'));
        $this->assertSame('1250', Prestasi::tulis(1250, 'poin'));
        $this->assertSame('', Prestasi::tulis(null, 'waktu'));

        foreach (self::prestasiBenar() as [$teks, $jenis]) {
            $nilai = Prestasi::urai($teks, $jenis)['nilai'];
            $this->assertSame($nilai, Prestasi::urai(Prestasi::tulis($nilai, $jenis), $jenis)['nilai'], "Bolak-balik {$teks}");
        }
    }

    public function testPeringkatWaktuTercepatPertamaDenganSeri(): void
    {
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 845, 'keterangan' => null],
            'b' => ['prestasi' => 832, 'keterangan' => null],
            'c' => ['prestasi' => 845, 'keterangan' => null],
            'd' => ['prestasi' => 900, 'keterangan' => null],
            'e' => ['prestasi' => null, 'keterangan' => 'DNS'],
            'f' => ['prestasi' => 700, 'keterangan' => 'DQ'],
            'g' => ['prestasi' => null, 'keterangan' => null],
        ], JenisHasil::WAKTU);

        $this->assertSame(['a' => 2, 'b' => 1, 'c' => 2, 'd' => 4, 'e' => null, 'f' => null, 'g' => null], $hasil);
    }

    public function testPeringkatJarakDanPoinTerbesarPertama(): void
    {
        $baris = [
            1 => ['prestasi' => 345, 'keterangan' => null],
            2 => ['prestasi' => 410, 'keterangan' => null],
            3 => ['prestasi' => 345, 'keterangan' => null],
            4 => ['prestasi' => 300, 'keterangan' => ''],
        ];

        $this->assertSame([1 => 2, 2 => 1, 3 => 2, 4 => 4], Peringkat::hitung($baris, JenisHasil::JARAK));
        $this->assertSame([1 => 2, 2 => 1, 3 => 2, 4 => 4], Peringkat::hitung($baris, JenisHasil::POIN));
    }

    public function testUrutanTampil(): void
    {
        $b = static fn (string $nama, ?int $peringkat, ?string $keterangan = null): array => ['nama' => $nama, 'peringkat' => $peringkat, 'prestasi' => null, 'keterangan' => $keterangan];

        $urut = Peringkat::urutkan([
            $b('dns', null, 'DNS'),
            $b('kosong', null),
            $b('ketiga', 3),
            $b('dq', null, 'DQ'),
            $b('pertama', 1),
            $b('dnf', null, 'DNF'),
            $b('pertama juga', 1),
        ]);

        $this->assertSame(['pertama', 'pertama juga', 'ketiga', 'kosong', 'dnf', 'dq', 'dns'], array_column($urut, 'nama'));
    }
}
