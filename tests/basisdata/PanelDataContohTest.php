<?php

use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Pembantu data uji panel (`tests/_support/PanelDataContoh.php`) menulis
 * pendaftaran yang utuh dan biayanya benar, supaya uji Tahap 6 lain bisa
 * memercayainya.
 *
 * @internal
 */
final class PanelDataContohTest extends UjiBasisData
{
    use PanelDataContoh;

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testPendaftaranBawaan(): void
    {
        $hasil = $this->buatPendaftaranPanel();

        $this->assertMatchesRegularExpression('/^MAC2A\d{5}26$/', $hasil['id_pendaftaran']);
        $this->assertCount(2, $hasil['atlet']);
        $this->assertCount(1, $hasil['penanggung_jawab']);

        // Putra 2 nomor individu + putri 1 nomor individu, KU 10 Rp 150.000.
        $this->assertSame(450000, $hasil['subtotal_individu']);
        $this->assertSame(0, $hasil['subtotal_beregu']);
        $this->assertSame(450000, $hasil['total']);

        $baris = $this->db->table('pendaftaran')->where('id', $hasil['id'])->get()->getRowArray();
        $this->assertSame('menunggu', $baris['status']);
        $this->assertNull($baris['waktu_lunas']);
        $this->assertSame('SD KARANGAN MAJU', $baris['nama_sekolah']);
        $this->assertSame(450000, (int) $baris['total']);
        $this->assertSame(3, $this->db->table('atlet_nomor')->countAllResults());
        $this->assertSame(450000, (int) $this->db->table('atlet_nomor')->selectSum('biaya')->get()->getRow()->biaya);
    }

    public function testTimDanSusulan(): void
    {
        $tim = $this->buatPendaftaranPanel(['atlet' => [...$this->timPanel('kanga', 4), ...$this->timPanel('estafet', 8)]]);
        $this->assertSame(250000, $tim['total']);
        $this->assertCount(12, $tim['atlet']);
        $this->assertSame(2, $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $tim['id'])->countAllResults());

        $jenisKelamin = array_column($this->db->table('atlet a')
            ->select('a.jenis_kelamin')
            ->join('atlet_nomor n', 'n.atlet_id = a.id')
            ->where('n.nomor_lomba_id', $this->idInduk['estafet'])
            ->get()->getResultArray(), 'jenis_kelamin');
        $this->assertSame(['L' => 4, 'P' => 4], array_count_values($jenisKelamin));

        $susulan = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 1), 'susulan' => ['kanga']]);
        $this->assertSame(0, $susulan['total']);
        $this->assertSame(0, (int) $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $susulan['id'])->get()->getRow()->biaya_tim);
    }

    public function testLunasNomorDadaDanFoto(): void
    {
        $hasil = $this->buatPendaftaranPanel([
            'sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'  => 'lunas',
            'foto'    => true,
            'atlet'   => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
        ]);

        $this->assertMatchesRegularExpression('/^MAC2B\d{5}26$/', $hasil['id_pendaftaran']);
        $this->assertSame(200000, $hasil['total']);

        $baris = $this->db->table('pendaftaran')->where('id', $hasil['id'])->get()->getRowArray();
        $this->assertSame('lunas', $baris['status']);
        $this->assertNotNull($baris['waktu_lunas']);
        $this->assertSame('4001', $this->db->table('atlet')->where('id', $hasil['atlet'][0])->get()->getRow()->nomor_dada);

        $foto = $this->db->table('penanggung_jawab')->where('id', $hasil['penanggung_jawab'][0])->get()->getRow()->foto;
        $this->assertMatchesRegularExpression('#^penanggung-jawab/[0-9a-f]{32}\.jpg$#', $foto);
        $this->assertCount(1, $this->berkasFoto());
    }

    public function testNisnBerbedaSetiapAtlet(): void
    {
        $this->buatPendaftaranPanel();
        $this->buatPendaftaranPanel();
        $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 6)]);

        $this->assertSame(10, $this->db->table('atlet')->countAllResults());
        $this->assertSame(10, $this->db->table('atlet')->select('nisn')->distinct()->countAllResults());
    }
}
