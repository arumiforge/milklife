<?php

use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\GabungPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\Mock\MockInputOutput;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Penggabungan pendaftaran Lunas dengan NPSN sama (K109,
 * `App\Libraries\Panel\GabungPendaftaran`) lewat `StatusPembayaran` dan
 * perintah `php spark pendaftaran:gabung`.
 *
 * @internal
 */
final class PanelGabungPendaftaranTest extends UjiBasisData
{
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        CLI::resetInputOutput();
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testLunasKeduaDigabungKePendaftaranYangLunasLebihDulu(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel();
        $susul  = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()], 'penanggung_jawab' => [['nama' => 'Pak Karangan', 'nomor_wa' => '6281200000002']]]);
        $this->setWaktuLunas($status, $awal['id'], '2026-11-10 08:00:00');
        $kodeLama = $this->kodeBukti($susul['id']);

        $hasil = $status->tandaiLunas($susul['id']);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(['id' => $awal['id'], 'id_pendaftaran' => $awal['id_pendaftaran']], $hasil['gabung']['tujuan']);
        $this->assertSame([$susul['id_pendaftaran']], $hasil['gabung']['digabung']);

        // Pendaftaran susulan hilang; isinya pindah ke pendaftaran awal.
        $this->assertNull($this->db->table('pendaftaran')->where('id', $susul['id'])->get()->getRowArray());
        $p = $this->db->table('pendaftaran')->where('id', $awal['id'])->get()->getRowArray();
        $this->assertSame('lunas', $p['status']);
        $this->assertSame('2026-11-10 08:00:00', $p['waktu_lunas']);
        $this->assertSame($awal['total'] + $susul['total'], (int) $p['total']);
        $this->assertSame($awal['subtotal_individu'] + $susul['subtotal_individu'], (int) $p['subtotal_individu']);
        $this->assertSame(3, $this->db->table('atlet')->where('pendaftaran_id', $awal['id'])->countAllResults());
        $this->assertSame(['6281200000001', '6281200000002'], array_column($this->db->table('penanggung_jawab')->where('pendaftaran_id', $awal['id'])->orderBy('id')->get()->getResultArray(), 'nomor_wa'));

        // Nomor dada atlet susulan tetap yang dibagikan saat Lunas.
        $this->assertSame('2003', $this->db->table('atlet')->where('id', $susul['atlet'][0])->get()->getRow()->nomor_dada);

        // ID dan kode bukti lama dicatat.
        $lama = $this->db->table('pendaftaran_gabungan')->get()->getResultArray();
        $this->assertCount(1, $lama);
        $this->assertSame($awal['id'], (int) $lama[0]['pendaftaran_id']);
        $this->assertSame($susul['id_pendaftaran'], $lama[0]['id_pendaftaran_lama']);
        $this->assertSame($kodeLama, $lama[0]['kode_bukti_lama']);
    }

    public function testYangMenungguBedaNpsnAtauTanpaNpsnTidakDigabung(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $tunggu = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $lain   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]);
        $tanpa  = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD TANPA NPSN'], 'atlet' => [$this->atletPanel()]]);
        $tanpa2 = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD TANPA NPSN'], 'atlet' => [$this->atletPanel()]]);

        $this->assertNull($status->tandaiLunas($lain['id'])['gabung']);
        $this->assertNull($status->tandaiLunas($tanpa['id'])['gabung']);
        $this->assertNull($status->tandaiLunas($tanpa2['id'])['gabung']);

        $this->assertSame(5, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame('menunggu', $this->db->table('pendaftaran')->where('id', $tunggu['id'])->get()->getRow()->status);
        $this->assertSame(0, $this->db->table('pendaftaran_gabungan')->countAllResults());

        // Yang Menunggu ikut digabung begitu Lunas.
        $this->assertSame($awal['id'], $status->tandaiLunas($tunggu['id'])['gabung']['tujuan']['id']);
    }

    public function testReguSusulanBerbiayaNolDijumlahkanKeReguTujuan(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => $this->timPanel('kanga', 4)]);
        $susul  = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 1), 'susulan' => ['kanga']]);
        $reguB  = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]])]);

        $status->tandaiLunas($susul['id']);
        $status->tandaiLunas($reguB['id']);

        $regu = $this->db->table('pendaftaran_beregu')->select('pendaftaran_id, regu, biaya_tim')->orderBy('regu')->get()->getResultArray();
        $this->assertSame([
            ['pendaftaran_id' => (string) $awal['id'], 'regu' => 'A', 'biaya_tim' => '100000'],
            ['pendaftaran_id' => (string) $awal['id'], 'regu' => 'B', 'biaya_tim' => '100000'],
        ], $regu);

        $p = $this->db->table('pendaftaran')->where('id', $awal['id'])->get()->getRowArray();
        $this->assertSame(200000, (int) $p['subtotal_beregu']);
        $this->assertSame(200000, (int) $p['total']);
        $this->assertSame(9, $this->db->table('atlet')->where('pendaftaran_id', $awal['id'])->countAllResults());

        $data = (new DataPendaftaran())->cariId($awal['id_pendaftaran']);
        $this->assertSame([$susul['id_pendaftaran'], $reguB['id_pendaftaran']], $data['gabungan_dari']);
        $this->assertSame([5, 4], array_map(static fn (array $r): int => count($r['anggota']), $data['beregu']));
    }

    public function testPenanggungJawabNomorWaSamaTidakDobelDanBuktiBayarPindah(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul  = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()], 'foto' => true]);
        $this->buatBuktiBayarPanel($awal['id']);
        $this->buatBuktiBayarPanel($susul['id'], 2);
        $foto = (string) $this->db->table('penanggung_jawab')->where('pendaftaran_id', $susul['id'])->get()->getRow()->foto;

        $status->tandaiLunas($susul['id']);

        // Orang yang sama (nomor WA sama) satu baris saja; tujuan belum punya foto, jadi memakai foto susulan.
        $orang = $this->db->table('penanggung_jawab')->where('pendaftaran_id', $awal['id'])->get()->getResultArray();
        $this->assertCount(1, $orang);
        $this->assertSame($foto, $orang[0]['foto']);
        $this->assertSame(3, $this->db->table('bukti_bayar')->where('pendaftaran_id', $awal['id'])->countAllResults());
        $this->assertCount(3, $this->berkasBuktiBayarTersimpan());
    }

    public function testCatatanLamaIkutPindahBilaGabunganDigabungLagi(): void
    {
        $status = new StatusPembayaran();
        $a      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $b      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $c      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);

        $status->tandaiLunas($a['id']);
        $status->tandaiLunas($b['id']);           // B → A
        $status->kembaliMenunggu($a['id']);
        $status->tandaiLunas($c['id']);           // C satu-satunya yang Lunas
        $this->db->table('pendaftaran')->where('id', $c['id'])->update(['waktu_lunas' => '2026-11-01 08:00:00']);
        $hasil = $status->tandaiLunas($a['id']);  // A (dengan B) → C, karena C Lunas lebih dulu

        $this->assertSame($c['id'], $hasil['gabung']['tujuan']['id']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(
            [$b['id_pendaftaran'] => (string) $c['id'], $a['id_pendaftaran'] => (string) $c['id']],
            array_column($this->db->table('pendaftaran_gabungan')->orderBy('id')->get()->getResultArray(), 'pendaftaran_id', 'id_pendaftaran_lama'),
        );

        $data = new DataPendaftaran();
        $this->assertSame($c['id_pendaftaran'], $data->cariId($b['id_pendaftaran'])['id_pendaftaran']);
        $this->assertSame($c['id_pendaftaran'], $data->cariId(strtolower($a['id_pendaftaran']))['id_pendaftaran']);
        $this->assertSame(3, $this->db->table('atlet')->where('pendaftaran_id', $c['id'])->countAllResults());
    }

    public function testHapusPendaftaranGabunganMenghapusCatatanLama(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul  = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($susul['id']);

        (new App\Libraries\Panel\HapusPendaftaran())->hapus($awal['id']);

        $this->assertSame(0, $this->db->table('pendaftaran_gabungan')->countAllResults());
        $this->assertNull((new DataPendaftaran())->cariId($susul['id_pendaftaran']));
    }

    public function testPerintahMenggabungkanDataLamaSekali(): void
    {
        // Data dari sebelum K109: dua pendaftaran Lunas ber-NPSN sama.
        $satu = $this->buatPendaftaranPanel(['status' => 'lunas', 'dibuat_pada' => '2026-11-12 10:00:00']);
        $dua  = $this->buatPendaftaranPanel(['status' => 'lunas', 'dibuat_pada' => '2026-11-11 09:00:00', 'atlet' => [$this->atletPanel()]]);
        $lain = $this->buatPendaftaranPanel(['status' => 'lunas', 'sekolah' => ['npsn' => '99999912'], 'atlet' => [$this->atletPanel()]]);

        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        $io->setInputs(['GABUNG']);
        command('pendaftaran:gabung');

        // `dua` Lunas lebih dulu, jadi `satu` digabung ke `dua`.
        $this->assertStringContainsString($satu['id_pendaftaran'] . ' → ' . $dua['id_pendaftaran'], $io->getOutput());
        $this->assertSame([$dua['id'], $lain['id']], array_map('intval', array_column($this->db->table('pendaftaran')->orderBy('id')->get()->getResultArray(), 'id')));
        $this->assertSame($satu['total'] + $dua['total'], (int) $this->db->table('pendaftaran')->where('id', $dua['id'])->get()->getRow()->total);

        // Dijalankan lagi: tidak ada yang perlu digabung.
        $io2 = new MockInputOutput();
        CLI::setInputOutput($io2);
        command('pendaftaran:gabung');
        $this->assertStringContainsString('Tidak ada yang perlu digabung', $io2->getOutput());
    }

    public function testPerintahTanpaKetikGabungTidakMengubahApaPun(): void
    {
        $this->buatPendaftaranPanel(['status' => 'lunas']);
        $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => [$this->atletPanel()]]);

        $io = new MockInputOutput();
        CLI::setInputOutput($io);
        $io->setInputs(['ya']);
        command('pendaftaran:gabung');

        $this->assertSame(2, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(0, $this->db->table('pendaftaran_gabungan')->countAllResults());
    }

    public function testKalimatUntukAdmin(): void
    {
        $this->assertSame(
            'Pendaftaran MAC2A0000926 digabung ke MAC2A0000426 karena NPSN-nya sama. ID lama tetap bisa dipakai di Cek Pendaftaran, dan kode QR bukti lama membuka bukti gabungan.',
            GabungPendaftaran::kalimat(['tujuan' => ['id' => 4, 'id_pendaftaran' => 'MAC2A0000426'], 'digabung' => ['MAC2A0000926']]),
        );
    }

    private function setWaktuLunas(StatusPembayaran $status, int $id, string $waktu): void
    {
        $status->tandaiLunas($id);
        $this->db->table('pendaftaran')->where('id', $id)->update(['waktu_lunas' => $waktu]);
    }

    /**
     * Kode bukti pendaftaran (dibuat bila belum ada, seperti saat bukti pertama kali dibuka).
     */
    private function kodeBukti(int $id): string
    {
        App\Libraries\BuktiPendaftaran::alamat(['id' => $id, 'id_pendaftaran' => '']);

        return (string) $this->db->table('pendaftaran')->where('id', $id)->get()->getRow()->kode_bukti;
    }
}
