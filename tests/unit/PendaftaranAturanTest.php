<?php

use App\Libraries\Pendaftaran\BiayaPendaftaran;
use App\Libraries\Pendaftaran\IdPendaftaran;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pendaftaran\KelengkapanTim;
use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Libraries\Pendaftaran\UsiaAtlet;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\PendaftaranContoh;

/**
 * Aturan murni pendaftaran (F4-01): kabupaten, kelompok umur dari tahun lahir
 * dan jenjang (K36, K23), usia tampilan (K37), ID pendaftaran (6.8), biaya
 * (6.7), dan kelengkapan tim (6.6, K07, K09, K10).
 *
 * @internal
 */
final class PendaftaranAturanTest extends CIUnitTestCase
{
    use PendaftaranContoh;

    // Kabupaten

    /**
     * @return iterable<string, array{0: mixed, 1: string|null}>
     */
    public static function contohKabupaten(): iterable
    {
        yield 'baku' => ['Kudus', 'Kudus'];
        yield 'huruf kecil' => ['jepara', 'Jepara'];
        yield 'data referensi' => ['Kab. Blora', 'Blora'];
        yield 'kabupaten' => ['Kabupaten Pati', 'Pati'];
        yield 'spasi' => ['  Rembang ', 'Rembang'];
        yield 'bukan peserta' => ['Demak', null];
        yield 'kosong' => ['', null];
        yield 'bukan teks' => [5, null];
    }

    #[DataProvider('contohKabupaten')]
    public function testKabupaten(mixed $isian, ?string $harapan): void
    {
        $this->assertSame($harapan, Kabupaten::baku($isian));
    }

    public function testLimaKabupaten(): void
    {
        $this->assertSame(['Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara'], Kabupaten::semua());
    }

    // Kelompok umur

    public function testKelompokUmurDariTahunLahir(): void
    {
        $daftar = self::indukContoh()['kelompok_umur'];

        $this->assertSame('KU 10', KelompokUmurAtlet::tentukan(2016, 'A', $daftar)['kelompok_umur']['nama']);
        $this->assertSame('KU 10', KelompokUmurAtlet::tentukan(2018, 'A', $daftar)['kelompok_umur']['nama']);
        $this->assertSame('KU 12', KelompokUmurAtlet::tentukan(2015, 'B', $daftar)['kelompok_umur']['nama']);
        $this->assertNull(KelompokUmurAtlet::tentukan(2015, 'B', $daftar)['galat']);
    }

    public function testTahunLahirDiLuarSemuaKelompokUmurDitolak(): void
    {
        $hasil = KelompokUmurAtlet::tentukan(2005, 'A', self::indukContoh()['kelompok_umur']);

        $this->assertNull($hasil['kelompok_umur']);
        $this->assertStringContainsString('lahir tahun 2005 tidak masuk kelompok umur mana pun', $hasil['galat']);
        $this->assertStringContainsString('KU 10 (lahir 2016–2018)', $hasil['galat']);
        $this->assertStringContainsString('Periksa lagi tanggal lahirnya', $hasil['galat']);
    }

    public function testKelompokUmurYangTidakDibukaUntukJenjangDitolak(): void
    {
        // Lahir 2012 masuk KU 15 (SMP/MTs), tetapi sekolahnya SD: tidak dipindah ke KU lain (K23).
        $hasil = KelompokUmurAtlet::tentukan(2012, 'A', self::indukContoh()['kelompok_umur']);

        $this->assertNull($hasil['kelompok_umur']);
        $this->assertSame('Atlet lahir tahun 2012 masuk KU 15, yang hanya untuk SMP/MTs. Sekolah ini SD/MI, jadi atlet ini tidak bisa didaftarkan.', $hasil['galat']);
    }

    public function testBatasKosongBerartiTidakDibatasi(): void
    {
        $daftar = [
            ['id' => 1, 'nama' => 'KU 8', 'tahun_lahir_awal' => 2019, 'tahun_lahir_akhir' => null, 'jenjang' => ['A']],
            ['id' => 5, 'nama' => 'KU 18', 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => 2010, 'jenjang' => ['C']],
            ['id' => 9, 'nama' => 'KU kosong', 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => null, 'jenjang' => ['A', 'B', 'C']],
        ];

        $this->assertSame('KU 8', KelompokUmurAtlet::tentukan(2022, 'A', $daftar)['kelompok_umur']['nama']);
        $this->assertSame('KU 18', KelompokUmurAtlet::tentukan(1999, 'C', $daftar)['kelompok_umur']['nama']);
        // Kelompok umur tanpa batas sama sekali tidak pernah cocok.
        $this->assertNull(KelompokUmurAtlet::tentukan(2014, 'B', $daftar)['kelompok_umur']);
    }

    public function testKelompokUmurTanpaJenjang(): void
    {
        $daftar = [['id' => 1, 'nama' => 'KU 8', 'tahun_lahir_awal' => 2019, 'tahun_lahir_akhir' => null, 'jenjang' => []]];

        $this->assertStringContainsString('belum dibuka untuk jenjang mana pun', KelompokUmurAtlet::tentukan(2020, 'A', $daftar)['galat']);
    }

    // Usia

    /**
     * @return iterable<string, array{0: string|null, 1: string|null, 2: string, 3: string}>
     */
    public static function contohUsia(): iterable
    {
        yield 'tahun' => ['2016-03-10', '2026-12-09', 'tahun', '10 tahun'];
        yield 'tahun dan bulan' => ['2016-03-10', '2026-12-09', 'tahun_bulan', '10 tahun 8 bulan'];
        yield 'tepat ulang tahun' => ['2016-12-09', '2026-12-09', 'tahun_bulan', '10 tahun'];
        yield 'sehari sebelum ulang tahun' => ['2016-12-10', '2026-12-09', 'tahun_bulan', '9 tahun 11 bulan'];
        yield 'tanggal acara kosong' => ['2016-03-10', null, 'tahun', ''];
        yield 'lahir setelah acara' => ['2027-01-01', '2026-12-09', 'tahun', ''];
        yield 'tanggal tidak sah' => ['bukan', '2026-12-09', 'tahun', ''];
    }

    #[DataProvider('contohUsia')]
    public function testUsia(?string $lahir, ?string $acara, string $tampilan, string $harapan): void
    {
        $this->assertSame($harapan, UsiaAtlet::teks($lahir, $acara, $tampilan));
    }

    // ID pendaftaran

    public function testIdPendaftaran(): void
    {
        $this->assertSame('MAC2A0000126', IdPendaftaran::buat(2, 'A', 1, 2026));
        $this->assertSame('MAC2C0123426', IdPendaftaran::buat(2, 'C', 1234, 2026));
        $this->assertSame('MAC10B0000507', IdPendaftaran::buat(10, 'B', 5, 2107));
        $this->assertSame('MAC2A12345626', IdPendaftaran::buat(2, 'A', 123456, 2026));
    }

    public function testIdPendaftaranBahanTidakSah(): void
    {
        $this->expectException(InvalidArgumentException::class);

        IdPendaftaran::buat(2, 'D', 1, 2026);
    }

    public function testBentukIdPendaftaran(): void
    {
        $this->assertTrue(IdPendaftaran::bentukSah('MAC2A0000126'));
        $this->assertTrue(IdPendaftaran::bentukSah(' mac2a0000126 '));
        $this->assertFalse(IdPendaftaran::bentukSah('MAC2D0000126'));
        $this->assertFalse(IdPendaftaran::bentukSah('MAC2A00126'));
        $this->assertFalse(IdPendaftaran::bentukSah(null));
    }

    // Biaya

    public function testBiaya(): void
    {
        $this->assertSame(
            ['subtotal_individu' => 500000, 'subtotal_beregu' => 100000, 'total' => 600000],
            BiayaPendaftaran::hitung([150000, 150000, 200000], [100000, 0]),
        );
        $this->assertSame(['subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0], BiayaPendaftaran::hitung([], []));
        $this->assertSame(100000, BiayaPendaftaran::tim(100000, false));
        $this->assertSame(0, BiayaPendaftaran::tim(100000, true));
    }

    // Kelengkapan tim

    private static function nomor(int $id): array
    {
        foreach (self::indukContoh()['nomor_lomba'] as $nomor) {
            if ($nomor['id'] === $id) {
                return $nomor;
            }
        }

        throw new RuntimeException('Nomor contoh tidak ada.');
    }

    public function testTimLengkap(): void
    {
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(20), ['L', 'L', 'L', 'L']));
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(20), array_fill(0, 6, 'L')));
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(21), ['L', 'L', 'L', 'L', 'P', 'P', 'P', 'P']));
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(21), ['L', 'L', 'L', 'L', 'L', 'P', 'P', 'P', 'P', 'P']));
    }

    public function testTimKurangDariInti(): void
    {
        $this->assertSame(
            ["Tim Kanga's Escape putra baru punya 3 atlet. Tambah 1 atlet lagi; tim ini minimal 4 atlet."],
            KelengkapanTim::periksa(self::nomor(20), ['L', 'L', 'L']),
        );
    }

    public function testTimMelebihiBatas(): void
    {
        $this->assertSame(
            ["Tim Kanga's Escape putra punya 7 atlet, padahal paling banyak 6. Hapus 1 atlet dari nomor ini."],
            KelengkapanTim::periksa(self::nomor(20), array_fill(0, 7, 'L')),
        );
    }

    public function testTimMixKurangPutri(): void
    {
        $pesan = KelengkapanTim::periksa(self::nomor(21), ['L', 'L', 'L', 'L', 'L', 'L', 'P', 'P']);

        $this->assertSame(['Tim Estafet 8 × 50 m mix minimal 4 putra dan 4 putri; sekarang 6 putra dan 2 putri. Tambah 2 putri.'], $pesan);
    }

    public function testTimMixKurangIntiDanPutraPutri(): void
    {
        $pesan = KelengkapanTim::periksa(self::nomor(21), ['L', 'P']);

        $this->assertCount(2, $pesan);
        $this->assertStringContainsString('baru punya 2 atlet', $pesan[0]);
        $this->assertStringContainsString('Tambah 3 putra dan 3 putri', $pesan[1]);
    }

    public function testTimSusulanHanyaMenambahAnggota(): void
    {
        // Sudah 4 anggota, batas 6: satu atau dua tambahan boleh, tanpa syarat inti.
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(20), ['L'], 4));
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(20), ['L', 'L'], 4));
        // Mix susulan tidak diperiksa putra/putri lagi.
        $this->assertSame([], KelengkapanTim::periksa(self::nomor(21), ['P'], 8));
    }

    public function testTimSusulanMelebihiBatas(): void
    {
        $this->assertSame(
            ["Tim Kanga's Escape putra sudah punya 5 atlet dari pendaftaran sebelumnya, dan paling banyak 6 atlet. Atlet tambahan paling banyak 1; hapus 1 atlet dari nomor ini."],
            KelengkapanTim::periksa(self::nomor(20), ['L', 'L'], 5),
        );
        $this->assertStringContainsString('Tim ini sudah penuh', KelengkapanTim::periksa(self::nomor(20), ['L'], 6)[0]);
    }
}
