<?php

use App\Libraries\Hasil\StatusNomor;
use App\Models\NomorLombaModel;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Status nomor dan batas peserta, bagian murni (F11-03).
 *
 * @internal
 */
final class HasilStatusNomorTest extends CIUnitTestCase
{
    public function testKeterangan(): void
    {
        $this->assertSame('Tidak dilombakan.', StatusNomor::keterangan(null));
        $this->assertSame('Tidak dilombakan.', StatusNomor::keterangan('  '));
        $this->assertSame('Tidak dilombakan. Peserta kurang dari 3.', StatusNomor::keterangan('Peserta kurang dari 3'));
        $this->assertSame('Tidak dilombakan. Peserta kurang dari 3.', StatusNomor::keterangan('Peserta kurang dari 3.'));
    }

    public function testPeringatan(): void
    {
        $this->assertNull(StatusNomor::peringatan(null, 0, 0, false), 'Tanpa batas.');
        $this->assertNull(StatusNomor::peringatan(3, 3, null, false));
        $this->assertNull(StatusNomor::peringatan(3, 5, 3, false));
        $this->assertStringStartsWith('Peserta Lunas baru 2 atlet, kurang dari batas 3 atlet.', (string) StatusNomor::peringatan(3, 2, null, false));
        $this->assertStringStartsWith('Peserta Lunas baru 1 regu, kurang dari batas 2 regu.', (string) StatusNomor::peringatan(2, 1, 1, true));
        $this->assertStringStartsWith('Peserta hadir baru 2 atlet, kurang dari batas 3 atlet.', (string) StatusNomor::peringatan(3, 4, 2, false));
        $this->assertStringContainsString('Medali tidak dikurangi otomatis.', (string) StatusNomor::peringatan(3, 2, null, false));
    }

    public function testPeriksa(): void
    {
        $this->assertSame([], StatusNomor::periksa(['tidak_dilombakan' => '0', 'alasan_tidak_dilombakan' => '', 'peserta_minimal' => '']));
        $this->assertSame([], StatusNomor::periksa(['tidak_dilombakan' => '1', 'alasan_tidak_dilombakan' => 'Peserta kurang', 'peserta_minimal' => '99']));

        $galat = StatusNomor::periksa(['tidak_dilombakan' => '1', 'alasan_tidak_dilombakan' => '', 'peserta_minimal' => '0']);
        $this->assertSame(['alasan_tidak_dilombakan', 'peserta_minimal'], array_keys($galat));

        foreach (['100', '-1', '2,5', 'tiga'] as $salah) {
            $this->assertArrayHasKey('peserta_minimal', StatusNomor::periksa(['peserta_minimal' => $salah]), $salah);
        }

        $this->assertArrayHasKey('alasan_tidak_dilombakan', StatusNomor::periksa(['alasan_tidak_dilombakan' => str_repeat('a', StatusNomor::ALASAN_MAKSIMAL + 1)]));
    }

    public function testIsianNomorLomba(): void
    {
        $isian = NomorLombaModel::rapikanIsian(['nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putra', 'jenis_hasil' => 'waktu']);
        $this->assertSame(['0', '', ''], [$isian['tidak_dilombakan'], $isian['alasan_tidak_dilombakan'], $isian['peserta_minimal']], 'Bawaan: dilombakan, tanpa batas.');

        $data = NomorLombaModel::untukDisimpan($isian + ['kelompok_umur_id' => '1']);
        $this->assertSame([0, null, null], [$data['tidak_dilombakan'], $data['alasan_tidak_dilombakan'], $data['peserta_minimal']]);

        $isian = NomorLombaModel::rapikanIsian(['nama' => 'Lari 60 m', 'jenis' => 'individu', 'kategori' => 'putra', 'jenis_hasil' => 'waktu', 'tidak_dilombakan' => '1', 'alasan_tidak_dilombakan' => '  Peserta   kurang ', 'peserta_minimal' => ' 3 ']);
        $data  = NomorLombaModel::untukDisimpan($isian + ['kelompok_umur_id' => '1']);
        $this->assertSame([1, 'Peserta kurang', 3], [$data['tidak_dilombakan'], $data['alasan_tidak_dilombakan'], $data['peserta_minimal']]);

        $this->assertSame(['1', 'Peserta kurang', '3'], array_values(array_intersect_key(
            NomorLombaModel::isianDariBaris(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Peserta kurang', 'peserta_minimal' => 3]),
            array_flip(['tidak_dilombakan', 'alasan_tidak_dilombakan', 'peserta_minimal']),
        )));
    }
}
