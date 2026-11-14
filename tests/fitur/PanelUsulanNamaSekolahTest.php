<?php

use App\Libraries\Panel\UbahPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Usulan nama sekolah dari pendaftar (K99): halaman `admin/sekolah/usulan`,
 * setujui, tolak, penanda di dasbor dan rincian pendaftaran, dan nama usulan
 * yang tetap dipakai saat admin mengubah pendaftaran. Semua data karangan.
 *
 * @internal
 */
final class PanelUsulanNamaSekolahTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        $this->db->table('sekolah')->insertBatch([
            ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'status' => 'SWASTA', 'desa_kelurahan' => 'Rekaan', 'kecamatan' => 'Anggitan', 'kabupaten' => 'Kudus'],
            ['npsn' => '99100002', 'nama' => 'MIS KARANGAN', 'bentuk' => 'MI', 'jenjang' => 'SD', 'status' => 'SWASTA', 'desa_kelurahan' => 'Khayalan', 'kecamatan' => 'Contoh', 'kabupaten' => 'Kudus'],
        ]);
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * Pendaftaran ber-NPSN 99100001 yang memakai nama usulan, beserta usulannya.
     *
     * @return array{pendaftaran: array<string, mixed>, usulan: int}
     */
    private function pendaftaranDenganUsulan(string $usulan = 'MIS KARANGAN REKAAN'): array
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => '99100001', 'nama' => $usulan, 'jenjang' => 'A', 'kabupaten' => 'Kudus']]);
        $this->db->table('usulan_nama_sekolah')->insert([
            'npsn' => '99100001', 'pendaftaran_id' => $p['id'], 'nama_lama' => 'MIS KARANGAN', 'nama_usulan' => $usulan,
            'status' => 'menunggu', 'dibuat_pada' => date('Y-m-d H:i:s'), 'diubah_pada' => date('Y-m-d H:i:s'),
        ]);

        return ['pendaftaran' => $p, 'usulan' => (int) $this->db->insertID()];
    }

    public function testYangBelumMasukDialihkan(): void
    {
        $this->assertBelumMasukDialihkan('admin/sekolah/usulan');
        $this->assertBelumMasukDialihkan('admin/sekolah/usulan/1/setujui', 'post');
        $this->assertBelumMasukDialihkan('admin/sekolah/usulan/1/tolak', 'post');
    }

    public function testCariNpsnMenyebutJumlahSekolahBernamaSama(): void
    {
        $isi = json_decode((string) $this->get('pendaftaran/sekolah?npsn=99100001')->getJSON(), true);

        $this->assertSame(1, $isi['sekolah']['nama_kembar']);
    }

    public function testHalamanUsulanDanPenandaDiDasborSekolahDanRincian(): void
    {
        ['pendaftaran' => $p, 'usulan' => $id] = $this->pendaftaranDenganUsulan();

        $hasil = $this->bukaSebagaiAdmin('admin/sekolah/usulan');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $kartu = $xpath->query('//article[@data-usulan="' . $id . '"]')->item(0);
        $this->assertNotNull($kartu);
        $this->assertStringContainsString('MIS KARANGAN REKAAN', $kartu->textContent);
        $this->assertStringContainsString('Rekaan, Kec. Anggitan, Kab. Kudus', $kartu->textContent);
        $this->assertSame(site_url('admin/sekolah/usulan/' . $id . '/setujui'), $xpath->query('.//form[1]', $kartu)->item(0)->getAttribute('action'));

        $this->bukaSebagaiAdmin('admin/dasbor')->assertSee('usulan nama sekolah dari pendaftar menunggu keputusan');
        $this->bukaSebagaiAdmin('admin/sekolah')->assertSee('1 menunggu');
        $this->assertSame(1, $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']))->query('//*[@id="usulan-nama-pendaftaran"]')->length);
    }

    public function testSetujuiMenggantiDataSekolahDanSemuaPendaftaran(): void
    {
        ['pendaftaran' => $p, 'usulan' => $id] = $this->pendaftaranDenganUsulan();
        $lain = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()], 'sekolah' => ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'jenjang' => 'A', 'kabupaten' => 'Kudus']]);
        $this->db->table('usulan_nama_sekolah')->insert([
            'npsn' => '99100001', 'pendaftaran_id' => $lain['id'], 'nama_lama' => 'MIS KARANGAN', 'nama_usulan' => 'MI KARANGAN',
            'status' => 'menunggu', 'dibuat_pada' => date('Y-m-d H:i:s'), 'diubah_pada' => date('Y-m-d H:i:s'),
        ]);

        // Admin merapikan usulan sebelum menyetujui.
        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/usulan/' . $id . '/setujui', ['nama' => 'MIS KARANGAN  REKAAN ANGGITAN']);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah/usulan', 'Usulan disetujui. Nama sekolah sekarang MIS KARANGAN REKAAN ANGGITAN. Nama di 2 pendaftaran ikut diganti. 1 usulan lain untuk sekolah yang sama ikut ditutup.');
        $sekolah = $this->db->table('sekolah')->where('npsn', '99100001')->get()->getRowArray();
        $this->assertSame('MIS KARANGAN REKAAN ANGGITAN', $sekolah['nama']);
        $this->assertNotNull($sekolah['disunting_pada']);
        $this->seeInDatabase('pendaftaran', ['id' => $p['id'], 'nama_sekolah' => 'MIS KARANGAN REKAAN ANGGITAN']);
        $this->seeInDatabase('pendaftaran', ['id' => $lain['id'], 'nama_sekolah' => 'MIS KARANGAN REKAAN ANGGITAN']);
        $this->seeInDatabase('usulan_nama_sekolah', ['id' => $id, 'status' => 'disetujui', 'nama_disetujui' => 'MIS KARANGAN REKAAN ANGGITAN']);
        $this->seeInDatabase('usulan_nama_sekolah', ['pendaftaran_id' => $lain['id'], 'status' => 'ditolak']);

        // Sudah diputuskan: tidak bisa diputuskan lagi.
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/sekolah/usulan/' . $id . '/tolak'), 'admin/sekolah/usulan');
    }

    public function testSetujuiDenganNamaSekolahLainDitolak(): void
    {
        ['usulan' => $id] = $this->pendaftaranDenganUsulan();
        $this->db->table('sekolah')->where('npsn', '99100002')->update(['nama' => 'MIS KARANGAN KHAYALAN']);

        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/usulan/' . $id . '/setujui', ['nama' => 'MIS KARANGAN KHAYALAN']);

        $this->assertGagalDialihkan($hasil, 'admin/sekolah/usulan');
        $this->assertStringContainsString('NPSN 99100002', (string) $_SESSION['pesan_galat']);
        $this->seeInDatabase('usulan_nama_sekolah', ['id' => $id, 'status' => 'menunggu']);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN']);
    }

    public function testTolakMengembalikanNamaPendaftaranPengusul(): void
    {
        ['pendaftaran' => $p, 'usulan' => $id] = $this->pendaftaranDenganUsulan();

        $hasil = $this->kirimSebagaiAdmin('admin/sekolah/usulan/' . $id . '/tolak', ['alasan' => 'Nama desanya keliru.']);

        $this->assertBerhasilDialihkan($hasil, 'admin/sekolah/usulan', 'Usulan ditolak. Pendaftaran pengusul kembali memakai nama MIS KARANGAN.');
        $this->seeInDatabase('pendaftaran', ['id' => $p['id'], 'nama_sekolah' => 'MIS KARANGAN']);
        $this->seeInDatabase('usulan_nama_sekolah', ['id' => $id, 'status' => 'ditolak', 'alasan' => 'Nama desanya keliru.']);
        $this->seeInDatabase('sekolah', ['npsn' => '99100001', 'nama' => 'MIS KARANGAN', 'disunting_pada' => null]);
        $this->bukaSebagaiAdmin('admin/sekolah/usulan')->assertSee('Nama desanya keliru.');
    }

    public function testUsulanTetapAdaSaatPendaftaranDihapus(): void
    {
        ['pendaftaran' => $p, 'usulan' => $id] = $this->pendaftaranDenganUsulan();

        $this->db->table('pendaftaran')->where('id', $p['id'])->delete();

        $this->seeInDatabase('usulan_nama_sekolah', ['id' => $id, 'pendaftaran_id' => null, 'status' => 'menunggu']);
        $this->bukaSebagaiAdmin('admin/sekolah/usulan')->assertOK();
    }

    public function testAdminMengubahPendaftaranTanpaMengembalikanNamaUsulan(): void
    {
        ['pendaftaran' => $p] = $this->pendaftaranDenganUsulan();

        $isian   = (new UbahPendaftaran())->isian($p['id']);
        $kiriman = ['sekolah' => $isian['sekolah'], 'penanggung_jawab' => $isian['penanggung_jawab'], 'atlet' => $isian['atlet']];
        $kiriman['penanggung_jawab'][0]['nomor_wa'] = '0812-1111-3333';

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->seeInDatabase('pendaftaran', ['id' => $p['id'], 'nama_sekolah' => 'MIS KARANGAN REKAAN']);
    }

    public function testTokenCsrfWajib(): void
    {
        ['usulan' => $id] = $this->pendaftaranDenganUsulan();

        $this->assertTokenCsrfWajib('admin/sekolah/usulan/' . $id . '/setujui', ['nama' => 'MIS KARANGAN REKAAN']);
        $this->assertTokenCsrfWajib('admin/sekolah/usulan/' . $id . '/tolak');
    }
}
