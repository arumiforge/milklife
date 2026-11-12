<?php

use App\Libraries\DataPendaftaran;
use Tests\Support\CekDataPendaftaran;
use Tests\Support\UjiBasisData;

/**
 * @internal
 */
final class DataPendaftaranTest extends UjiBasisData
{
    use CekDataPendaftaran;

    public function testIdTidakAdaDanNpsnTanpaPendaftaran(): void
    {
        $this->isiDataCek();
        $data = new DataPendaftaran();

        $this->assertNull($data->cariId('MAC2A9999926'));
        $this->assertSame([], $data->cariNpsn('20399999'));
    }

    public function testCariIdMembacaNilaiTersimpan(): void
    {
        $this->isiDataCek();
        $p = (new DataPendaftaran())->cariId(' mac2a0000126 ');

        $this->assertSame('MAC2A0000126', $p['id_pendaftaran']);
        $this->assertSame('SD/MI', $p['jenjang_label']);
        $this->assertSame('Menunggu Konfirmasi Pembayaran', $p['status_label']);
        $this->assertSame([450000, 200000, 650000], [$p['subtotal_individu'], $p['subtotal_beregu'], $p['total']]);

        // Tarif KU Contoh sekarang Rp 175.000; yang dibaca tetap biaya tersimpan.
        $this->assertSame([150000, 150000, 150000], array_column($p['individu'], 'biaya'));
        $this->assertSame(['Lari 60 m putra', 'Lari 60 m putra', 'Lompat Jauh putra'], array_column($p['individu'], 'nomor_lomba'));
        $this->assertSame(['BUDI KARANGAN', 'CANDRA REKAAN', 'CANDRA REKAAN'], array_column($p['individu'], 'nama'));

        $this->assertCount(1, $p['beregu']);
        $this->assertSame('Estafet 4 × 50 m putra', $p['beregu'][0]['nomor_lomba']);
        $this->assertSame(200000, $p['beregu'][0]['biaya_tim']);
        $this->assertSame(['9900000001', '9900000002', '9900000003', '9900000004'], array_column($p['beregu'][0]['anggota'], 'nisn'));

        $this->assertCount(4, $p['atlet']);
        $this->assertSame(['Lari 60 m putra', 'Lompat Jauh putra', 'Estafet 4 × 50 m putra'], $p['atlet'][1]['nomor_lomba']);
        $this->assertSame('Laki-laki', $p['atlet'][0]['jenis_kelamin_label']);
        $this->assertSame('KU Contoh', $p['atlet'][0]['kelompok_umur']);
        $this->assertNull($p['atlet'][0]['nomor_dada']);
    }

    public function testFotoPenanggungJawabTidakIkut(): void
    {
        $this->isiDataCek();
        $p = (new DataPendaftaran())->cariId('MAC2A0000126');

        $this->assertSame([
            ['nama' => 'Ibu Karangan', 'nomor_wa' => '0812-0000-0001'],
            ['nama' => 'Pak Rekaan', 'nomor_wa' => '0812-0000-0002'],
        ], $p['penanggung_jawab']);
        $this->assertStringNotContainsString('foto', json_encode($p));
    }

    public function testCariNpsnUrutDanSusulanTanpaBiayaTim(): void
    {
        $this->isiDataCek();
        $daftar = (new DataPendaftaran())->cariNpsn('20300001');

        $this->assertSame(['MAC2A0000126', 'MAC2A0000226'], array_column($daftar, 'id_pendaftaran'));

        $susulan = $daftar[1];
        $this->assertSame('Lunas', $susulan['status_label']);
        $this->assertSame([], $susulan['individu']);
        $this->assertNull($susulan['beregu'][0]['biaya_tim']);
        $this->assertSame([['nama' => 'FAJAR REKAAN', 'nisn' => '9900000005', 'nomor_dada' => '1001']], $susulan['beregu'][0]['anggota']);
    }

    public function testPendaftaranTanpaNpsn(): void
    {
        $this->isiDataCek();
        $p = (new DataPendaftaran())->cariId('MAC2B0000326');

        $this->assertNull($p['npsn']);
        $this->assertSame('SMP/MTs', $p['jenjang_label']);
        $this->assertSame([], $p['beregu']);
    }
}
