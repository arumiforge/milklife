<?php

use App\Models\NomorLombaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Aturan isian nomor lomba tanpa basis data (`NomorLombaModel::rapikanIsian()`,
 * `periksaIsian()`, `untukDisimpan()`, `isianDariBaris()`, `kekuranganTim()`).
 *
 * @internal
 */
final class NomorLombaAturanTest extends CIUnitTestCase
{
    private const KU = [3, 7];

    /**
     * @param array<string, string> $ubah
     *
     * @return array<string, string>
     */
    private static function individu(array $ubah = []): array
    {
        return NomorLombaModel::rapikanIsian($ubah + [
            'kelompok_umur_id' => '3',
            'nama'             => 'Lari 60 m',
            'jenis'            => 'individu',
            'kategori'         => 'putra',
            'urutan'           => '',
            'aktif'            => '1',
        ]);
    }

    /**
     * @param array<string, string> $ubah
     *
     * @return array<string, string>
     */
    private static function beregu(array $ubah = []): array
    {
        return NomorLombaModel::rapikanIsian($ubah + [
            'kelompok_umur_id' => '7',
            'nama'             => 'Estafet 8 × 50 m',
            'jenis'            => 'beregu',
            'kategori'         => 'mix',
            'jumlah_inti'      => '8',
            'batas_anggota'    => '10',
            'jumlah_putra'     => '4',
            'jumlah_putri'     => '4',
            'biaya_beregu'     => '150000',
            'urutan'           => '11',
            'aktif'            => '1',
        ]);
    }

    public function testIsianBenar(): void
    {
        $this->assertSame([], NomorLombaModel::periksaIsian(self::individu(), self::KU));
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(), self::KU));
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['kategori' => 'putri', 'jumlah_putra' => '', 'jumlah_putri' => '']), self::KU));
    }

    public function testRapikanMembuangSpasiDanMengosongkanIsianTimNomorIndividu(): void
    {
        $isian = self::individu([
            'nama'          => "  Lari   60\tm ",
            'jumlah_inti'   => '4',
            'batas_anggota' => '6',
            'jumlah_putra'  => '2',
            'jumlah_putri'  => '2',
            'biaya_beregu'  => '100000',
            'urutan'        => ' 5 ',
            'aktif'         => 'ya',
        ]);

        $this->assertSame('Lari 60 m', $isian['nama']);
        $this->assertSame('5', $isian['urutan']);
        $this->assertSame('0', $isian['aktif'], 'Hanya "1" yang berarti aktif.');

        foreach (NomorLombaModel::KOLOM_TIM as $kolom) {
            $this->assertSame('', $isian[$kolom], $kolom);
        }
    }

    public function testRapikanMengosongkanJumlahPutraPutriNomorBereguBukanMix(): void
    {
        $isian = self::beregu(['kategori' => 'putra']);

        $this->assertSame('', $isian['jumlah_putra']);
        $this->assertSame('', $isian['jumlah_putri']);
        $this->assertSame('8', $isian['jumlah_inti']);
    }

    public function testRapikanMenerimaIsianBukanTeks(): void
    {
        $isian = NomorLombaModel::rapikanIsian(['nama' => ['Lari'], 'jenis' => null]);

        $this->assertSame('', $isian['nama']);
        $this->assertSame('', $isian['jenis']);
        $this->assertSame('0', $isian['aktif']);
    }

    public function testIsianWajib(): void
    {
        $galat = NomorLombaModel::periksaIsian(NomorLombaModel::rapikanIsian([]), self::KU);

        $this->assertSame(['kelompok_umur_id', 'nama', 'jenis', 'kategori'], array_keys($galat));
        $this->assertStringContainsString('Kelompok umur belum dipilih', $galat['kelompok_umur_id']);
        $this->assertStringContainsString('Nama nomor belum diisi', $galat['nama']);
        $this->assertStringContainsString('Pilih individu atau beregu', $galat['jenis']);
        $this->assertStringContainsString('Pilih putra, putri, atau mix', $galat['kategori']);
    }

    public function testKelompokUmurHarusAda(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::individu(['kelompok_umur_id' => '99']), self::KU);

        $this->assertSame(['kelompok_umur_id'], array_keys($galat));
        $this->assertStringContainsString('tidak ditemukan', $galat['kelompok_umur_id']);

        $galat = NomorLombaModel::periksaIsian(self::individu(['kelompok_umur_id' => '3abc']), self::KU);
        $this->assertArrayHasKey('kelompok_umur_id', $galat);
    }

    public function testNamaPalingBanyakSeratusHuruf(): void
    {
        $this->assertSame([], NomorLombaModel::periksaIsian(self::individu(['nama' => str_repeat('é', 100)]), self::KU));

        $galat = NomorLombaModel::periksaIsian(self::individu(['nama' => str_repeat('a', 101)]), self::KU);
        $this->assertStringContainsString('paling banyak 100 huruf', $galat['nama']);
    }

    public function testJenisDanKategoriDariDaftar(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::individu(['jenis' => 'tim', 'kategori' => 'campuran']), self::KU);

        $this->assertSame(['jenis', 'kategori'], array_keys($galat));
    }

    public function testNomorIndividuTidakBolehMix(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::individu(['kategori' => 'mix']), self::KU);

        $this->assertSame(['kategori'], array_keys($galat));
        $this->assertSame(
            'Nomor individu tidak bisa berkategori mix. Pilih putra atau putri, atau ubah jenisnya menjadi beregu.',
            $galat['kategori'],
        );
    }

    public function testNomorBereguWajibLengkap(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::beregu([
            'jumlah_inti'   => '',
            'batas_anggota' => '',
            'jumlah_putra'  => '',
            'jumlah_putri'  => '',
            'biaya_beregu'  => '',
        ]), self::KU);

        $this->assertSame(['jumlah_inti', 'batas_anggota', 'biaya_beregu', 'jumlah_putra', 'jumlah_putri'], array_keys($galat));
        $this->assertStringContainsString('Jumlah anggota inti belum diisi', $galat['jumlah_inti']);
        $this->assertStringContainsString('Batas anggota belum diisi', $galat['batas_anggota']);
        $this->assertStringContainsString('Biaya beregu belum diisi', $galat['biaya_beregu']);
        $this->assertStringContainsString('Jumlah putra minimal belum diisi', $galat['jumlah_putra']);
        $this->assertStringContainsString('Jumlah putri minimal belum diisi', $galat['jumlah_putri']);
    }

    public function testNomorBereguPutraTidakMemintaJumlahPutraPutri(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::beregu(['kategori' => 'putra', 'batas_anggota' => '']), self::KU);

        $this->assertSame(['batas_anggota'], array_keys($galat));
    }

    public function testJumlahIntiDalamRentang(): void
    {
        foreach (['1', '51', '4,5', '-4', 'empat'] as $nilai) {
            $galat = NomorLombaModel::periksaIsian(self::beregu(['kategori' => 'putra', 'jumlah_inti' => $nilai, 'batas_anggota' => '8']), self::KU);
            $this->assertSame(['jumlah_inti'], array_keys($galat), $nilai);
            $this->assertStringContainsString('angka 2 sampai 50', $galat['jumlah_inti']);
        }

        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['kategori' => 'putra', 'jumlah_inti' => '2', 'batas_anggota' => '2']), self::KU));
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['kategori' => 'putra', 'jumlah_inti' => '50', 'batas_anggota' => '50']), self::KU));
    }

    public function testBatasAnggotaTidakKurangDariInti(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::beregu(['batas_anggota' => '7']), self::KU);

        $this->assertSame(['batas_anggota'], array_keys($galat));
        $this->assertSame('Batas anggota (7) kurang dari jumlah inti (8). Tulis 8 atau lebih.', $galat['batas_anggota']);

        // Sama dengan inti berarti tanpa cadangan.
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['batas_anggota' => '8']), self::KU));

        $galat = NomorLombaModel::periksaIsian(self::beregu(['batas_anggota' => '60']), self::KU);
        $this->assertStringContainsString('angka 2 sampai 50', $galat['batas_anggota']);
    }

    public function testBiayaBereguRupiahLebihDariNol(): void
    {
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['biaya_beregu' => 'Rp 150.000']), self::KU));

        foreach (['0', '150.000,50', 'seratus ribu', '-100000'] as $nilai) {
            $galat = NomorLombaModel::periksaIsian(self::beregu(['biaya_beregu' => $nilai]), self::KU);
            $this->assertSame(['biaya_beregu'], array_keys($galat), $nilai);
            $this->assertStringContainsString('Biaya beregu belum benar', $galat['biaya_beregu']);
        }
    }

    public function testJumlahPutraPutriMixTidakLebihDariInti(): void
    {
        $galat = NomorLombaModel::periksaIsian(self::beregu(['jumlah_putra' => '5', 'jumlah_putri' => '4']), self::KU);

        $this->assertSame(['jumlah_putra'], array_keys($galat));
        $this->assertSame(
            'Jumlah putra dan putri minimal (5 + 4 = 9) lebih dari jumlah inti (8). Kurangi salah satunya supaya jumlahnya paling banyak 8.',
            $galat['jumlah_putra'],
        );

        // Jumlahnya boleh kurang dari inti; sisanya bebas putra atau putri.
        $this->assertSame([], NomorLombaModel::periksaIsian(self::beregu(['jumlah_putra' => '3', 'jumlah_putri' => '3']), self::KU));

        $galat = NomorLombaModel::periksaIsian(self::beregu(['jumlah_putra' => '0', 'jumlah_putri' => 'x']), self::KU);
        $this->assertSame(['jumlah_putra', 'jumlah_putri'], array_keys($galat));
        $this->assertStringContainsString('angka 1 sampai 50', $galat['jumlah_putra']);
    }

    public function testUrutanBolehKosongAtauAngka(): void
    {
        $this->assertSame([], NomorLombaModel::periksaIsian(self::individu(['urutan' => '']), self::KU));
        $this->assertSame([], NomorLombaModel::periksaIsian(self::individu(['urutan' => '0']), self::KU));
        $this->assertSame([], NomorLombaModel::periksaIsian(self::individu(['urutan' => '9999']), self::KU));

        foreach (['10000', '1,5', 'awal'] as $nilai) {
            $galat = NomorLombaModel::periksaIsian(self::individu(['urutan' => $nilai]), self::KU);
            $this->assertSame(['urutan'], array_keys($galat), $nilai);
        }
    }

    public function testUntukDisimpan(): void
    {
        $this->assertSame([
            'kelompok_umur_id' => 7,
            'nama'             => 'Estafet 8 × 50 m',
            'jenis'            => 'beregu',
            'kategori'         => 'mix',
            'jumlah_inti'      => 8,
            'batas_anggota'    => 10,
            'jumlah_putra'     => 4,
            'jumlah_putri'     => 4,
            'biaya_beregu'     => 150000,
            'urutan'           => 11,
            'aktif'            => 1,
        ], NomorLombaModel::untukDisimpan(self::beregu(['biaya_beregu' => 'Rp 150.000'])));

        $data = NomorLombaModel::untukDisimpan(self::individu(['aktif' => '0']));
        $this->assertNull($data['urutan']);
        $this->assertSame(0, $data['aktif']);

        foreach (NomorLombaModel::KOLOM_TIM as $kolom) {
            $this->assertNull($data[$kolom], $kolom);
        }
    }

    public function testIsianDariBaris(): void
    {
        $isian = NomorLombaModel::isianDariBaris([
            'id' => '5', 'kelompok_umur_id' => '2', 'nama' => 'Formula 1', 'jenis' => 'beregu', 'kategori' => 'putri',
            'jumlah_inti' => '4', 'batas_anggota' => null, 'jumlah_putra' => null, 'jumlah_putri' => null,
            'biaya_beregu' => '100000', 'urutan' => '10', 'aktif' => '1',
        ]);

        $this->assertSame([
            'kelompok_umur_id' => '2', 'nama' => 'Formula 1', 'jenis' => 'beregu', 'kategori' => 'putri',
            'jumlah_inti' => '4', 'batas_anggota' => '', 'jumlah_putra' => '', 'jumlah_putri' => '',
            'biaya_beregu' => '100000', 'urutan' => '10', 'aktif' => '1',
        ], $isian);

        $this->assertSame('0', NomorLombaModel::isianDariBaris(['aktif' => '0'])['aktif']);
    }

    public function testKekuranganTim(): void
    {
        $this->assertSame([], NomorLombaModel::kekuranganTim(['jenis' => 'individu', 'kategori' => 'putra']));

        // Seperti hasil data:awal: inti dan biaya ada, batas anggota belum.
        $this->assertSame(['batas anggota'], NomorLombaModel::kekuranganTim([
            'jenis' => 'beregu', 'kategori' => 'putra', 'jumlah_inti' => '4', 'batas_anggota' => null, 'biaya_beregu' => '100000',
        ]));
        $this->assertSame(['batas anggota', 'jumlah putra dan putri minimal'], NomorLombaModel::kekuranganTim([
            'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => '8', 'batas_anggota' => null,
            'jumlah_putra' => '4', 'jumlah_putri' => null, 'biaya_beregu' => '150000',
        ]));
        $this->assertSame(['jumlah inti', 'batas anggota', 'biaya beregu'], NomorLombaModel::kekuranganTim([
            'jenis' => 'beregu', 'kategori' => 'putri', 'jumlah_inti' => null, 'batas_anggota' => '', 'biaya_beregu' => null,
        ]));
        $this->assertSame([], NomorLombaModel::kekuranganTim([
            'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => '8', 'batas_anggota' => '8',
            'jumlah_putra' => '4', 'jumlah_putri' => '4', 'biaya_beregu' => '0',
        ]));
    }

    public function testNamaLengkap(): void
    {
        $this->assertSame('Lari 60 m putra', NomorLombaModel::namaLengkap(['nama' => 'Lari 60 m', 'kategori' => 'putra']));
        $this->assertSame('Estafet 8 × 50 m mix', NomorLombaModel::namaLengkap(['nama' => 'Estafet 8 × 50 m', 'kategori' => 'mix']));
    }
}
