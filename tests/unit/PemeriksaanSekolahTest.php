<?php

use App\Commands\ImporSekolah;
use App\Models\SekolahModel;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Aturan pemeriksaan data sekolah dan pembacaan berkas impor, tanpa basis data.
 *
 * @internal
 */
final class PemeriksaanSekolahTest extends CIUnitTestCase
{
    private const DATA = SUPPORTPATH . 'data/';

    public function testNpsnSahHanyaTepatDelapanAngka(): void
    {
        $this->assertTrue(SekolahModel::npsnSah('99000001'));
        $this->assertTrue(SekolahModel::npsnSah('00000000'));
        $this->assertFalse(SekolahModel::npsnSah('9900001'));
        $this->assertFalse(SekolahModel::npsnSah('990000011'));
        $this->assertFalse(SekolahModel::npsnSah('99A00001'));
        $this->assertFalse(SekolahModel::npsnSah(''));
        $this->assertFalse(SekolahModel::npsnSah("99000001\n"));
    }

    public function testKabupatenTanpaAwalan(): void
    {
        $this->assertSame('Blora', SekolahModel::rapikanKabupaten('Kab. Blora'));
        $this->assertSame('Kudus', SekolahModel::rapikanKabupaten('  Kab. Kudus '));
        $this->assertSame('Rekaan', SekolahModel::rapikanKabupaten('Kota Rekaan'));
        $this->assertSame('Pati', SekolahModel::rapikanKabupaten('Kabupaten Pati'));
        $this->assertSame('Jepara', SekolahModel::rapikanKabupaten('Jepara'));
        $this->assertSame('Rembang', SekolahModel::rapikanKabupaten('Kab.Rembang'));
        $this->assertSame('Kotabaru', SekolahModel::rapikanKabupaten('Kotabaru'));
        $this->assertSame('', SekolahModel::rapikanKabupaten('Kab.'));
        $this->assertSame('', SekolahModel::rapikanKabupaten('   '));
    }

    public function testBarisSahDirapikan(): void
    {
        [$data, $alasan] = SekolahModel::periksaBaris([
            'npsn'                => ' 99000001 ',
            'nama'                => '  SD KARANGAN SATU ',
            'bentuk'              => 'SD',
            'jenjang'             => 'sd',
            'status'              => 'NEGERI',
            'alamat'              => '',
            'desa_kelurahan'      => 'Sukamaju',
            'kecamatan'           => 'Kecamatan Contoh',
            'kode_kecamatan'      => '999901',
            'kabupaten_kota'      => 'Kab. Blora',
            'kode_kabupaten_kota' => '999900',
        ]);

        $this->assertNull($alasan);
        $this->assertSame([
            'npsn'           => '99000001',
            'nama'           => 'SD KARANGAN SATU',
            'bentuk'         => 'SD',
            'jenjang'        => 'SD',
            'status'         => 'NEGERI',
            'alamat'         => null,
            'desa_kelurahan' => 'Sukamaju',
            'kecamatan'      => 'Kecamatan Contoh',
            'kabupaten'      => 'Blora',
        ], $data);
    }

    /**
     * @return iterable<string, array{0: mixed, 1: string}>
     */
    public static function barisTidakSah(): iterable
    {
        $dasar = ['npsn' => '99000001', 'nama' => 'SD KARANGAN', 'jenjang' => 'SD', 'kabupaten_kota' => 'Kab. Blora'];

        yield 'bukan objek' => ['teks biasa', 'bukan data sekolah'];
        yield 'NPSN tujuh angka' => [['npsn' => '9900001'] + $dasar, 'NPSN'];
        yield 'NPSN berhuruf' => [['npsn' => '99A00001'] + $dasar, 'NPSN'];
        yield 'NPSN tidak ada' => [array_diff_key($dasar, ['npsn' => 1]), 'NPSN'];
        yield 'nama kosong' => [['nama' => '  '] + $dasar, 'nama sekolah kosong'];
        yield 'nama berupa daftar' => [['nama' => ['SD']] + $dasar, 'nama sekolah kosong'];
        yield 'jenjang tak dikenal' => [['jenjang' => 'PAUD'] + $dasar, 'jenjang'];
        yield 'kabupaten kosong' => [['kabupaten_kota' => ''] + $dasar, 'kabupaten kosong'];
        yield 'kabupaten hanya awalan' => [['kabupaten_kota' => 'Kab. '] + $dasar, 'kabupaten kosong'];
        yield 'nama terlalu panjang' => [['nama' => str_repeat('A', 151)] + $dasar, 'lebih dari 150'];
    }

    #[DataProvider('barisTidakSah')]
    public function testBarisTidakSahDitolakDenganAlasan(mixed $baris, string $potonganAlasan): void
    {
        [$data, $alasan] = SekolahModel::periksaBaris($baris);

        $this->assertNull($data);
        $this->assertStringContainsString($potonganAlasan, (string) $alasan);
    }

    public function testJalurRelatifDiukurDariFolderProyek(): void
    {
        $this->assertSame(ROOTPATH . 'data/sekolah/sekolah.json', ImporSekolah::jalurLengkap('data/sekolah/sekolah.json'));
        $this->assertSame('/tmp/sekolah.json', ImporSekolah::jalurLengkap('/tmp/sekolah.json'));
        $this->assertSame('C:\\laragon\\sekolah.json', ImporSekolah::jalurLengkap('C:\\laragon\\sekolah.json'));
    }

    public function testBacaBerkasContoh(): void
    {
        $daftar = ImporSekolah::bacaBerkas(self::DATA . 'sekolah-contoh.json');

        $this->assertCount(4, $daftar);
        $this->assertSame('99000001', $daftar[0]['npsn']);
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function berkasDitolak(): iterable
    {
        yield 'tidak ada' => ['sekolah-tidak-ada.json', 'tidak ditemukan'];
        yield 'folder' => ['', 'tidak ditemukan'];
        yield 'JSON rusak' => ['sekolah-rusak.json', 'bukan JSON yang benar'];
        yield 'bukan daftar' => ['sekolah-bukan-daftar.json', 'harus berupa daftar sekolah'];
    }

    #[DataProvider('berkasDitolak')]
    public function testBerkasBermasalahDitolakDenganPesanPerbaikan(string $berkas, string $potonganPesan): void
    {
        try {
            ImporSekolah::bacaBerkas(self::DATA . $berkas);
            $this->fail('Berkas seharusnya ditolak.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString($potonganPesan, $e->getMessage());
        }
    }
}
