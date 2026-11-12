<?php

use App\Libraries\Panel\HapusPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Hapus pendaftaran (`App\Libraries\Panel\HapusPendaftaran`; rancangan 6.9,
 * asumsi 3 rencana Tahap 6): baris anak dan berkas ikut terhapus, NISN bisa
 * didaftarkan lagi, nomor dada tidak dipakai ulang.
 *
 * @internal
 */
final class PanelPendaftaranHapusTest extends UjiBasisData
{
    use PanelDataContoh;

    private const TABEL_ANAK = ['penanggung_jawab', 'atlet', 'pendaftaran_beregu', 'bukti_bayar'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testMenghapusBarisDanBerkasHanyaMilikPendaftaranItu(): void
    {
        $hapus = $this->buatPendaftaranPanel([
            'foto'             => true,
            'penanggung_jawab' => [['nama' => 'Ibu Karangan', 'nomor_wa' => '6281200000001'], ['nama' => 'Bapak Karangan', 'nomor_wa' => '6281200000002']],
            'atlet'            => [...$this->timPanel('kanga', 4), $this->atletPanel(['nomor' => ['lari60putra', 'frog']])],
        ]);
        $tetap = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN TETAP']]);

        $this->buatBuktiBayarPanel($hapus['id'], 3);
        $buktiTetap = $this->buatBuktiBayarPanel($tetap['id'], 1);

        $fotoTetap  = $this->db->table('penanggung_jawab')->select('foto')->where('pendaftaran_id', $tetap['id'])->get()->getRow()->foto;
        $berkasTetap = $this->db->table('bukti_bayar')->select('berkas')->where('id', $buktiTetap[0])->get()->getRow()->berkas;

        $this->assertCount(3, $this->berkasFoto());
        $this->assertCount(4, $this->berkasBuktiBayarTersimpan());

        $hasil = (new HapusPendaftaran())->hapus($hapus['id']);

        $this->assertSame(['id_pendaftaran' => $hapus['id_pendaftaran'], 'nama_sekolah' => 'SD KARANGAN MAJU', 'jumlah_atlet' => 5], $hasil);

        $this->assertSame(0, $this->db->table('pendaftaran')->where('id', $hapus['id'])->countAllResults());

        foreach (self::TABEL_ANAK as $tabel) {
            $this->assertSame(0, $this->db->table($tabel)->where('pendaftaran_id', $hapus['id'])->countAllResults(), $tabel);
        }

        $this->assertSame(0, $this->db->table('atlet_nomor')->whereIn('atlet_id', $hapus['atlet'])->countAllResults());

        // Pendaftaran lain utuh, termasuk berkasnya.
        $this->assertSame(1, $this->db->table('pendaftaran')->where('id', $tetap['id'])->countAllResults());
        $this->assertSame(2, $this->db->table('atlet')->where('pendaftaran_id', $tetap['id'])->countAllResults());
        $this->assertSame(3, $this->db->table('atlet_nomor')->countAllResults());
        $this->assertSame([basename((string) $fotoTetap)], array_map('basename', $this->berkasFoto()));
        $this->assertSame([$berkasTetap], $this->berkasBuktiBayarTersimpan());
    }

    public function testNisnBisaDidaftarkanLagi(): void
    {
        $p    = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(['nisn' => '9900155555'])]]);
        $baru = ['atlet' => [$this->atletPanel(['nisn' => '9900155555'])]];

        (new HapusPendaftaran())->hapus($p['id']);

        $lagi = $this->buatPendaftaranPanel($baru);
        $this->assertSame('9900155555', $this->db->table('atlet')->where('id', $lagi['atlet'][0])->get()->getRow()->nisn);
    }

    public function testNomorDadaPendaftaranLunasTidakDipakaiUlang(): void
    {
        $status = new StatusPembayaran();
        $lunas  = $this->buatPendaftaranPanel();
        $status->tandaiLunas($lunas['id']);

        (new HapusPendaftaran())->hapus($lunas['id']);

        $this->assertSame(2, (int) $this->db->table('awalan_nomor_dada')->where('awalan', '2')->get()->getRow()->nomor_terakhir);

        $berikut = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null]]);
        $status->tandaiLunas($berikut['id']);

        $nomor = array_column($this->db->table('atlet')->select('nomor_dada')->whereIn('id', $berikut['atlet'])->orderBy('id')->get()->getResultArray(), 'nomor_dada');
        $this->assertSame(['2003', '2004'], $nomor);
    }

    public function testPendaftaranTanpaFotoDanBuktiTetapTerhapus(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->assertSame(2, (new HapusPendaftaran())->hapus($p['id'])['jumlah_atlet']);
        $this->assertSame(0, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(0, $this->db->transDepth);
    }

    public function testPendaftaranTidakAda(): void
    {
        $this->buatPendaftaranPanel();

        $this->assertNull((new HapusPendaftaran())->hapus(999));
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
        $this->assertSame(0, $this->db->transDepth);
    }
}
