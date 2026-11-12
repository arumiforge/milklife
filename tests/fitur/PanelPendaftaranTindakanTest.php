<?php

use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Tindakan di rincian pendaftaran (F6-02): tandai Lunas, kembali ke
 * Menunggu, dan hapus, lewat formulir `POST` dengan token CSRF.
 *
 * @internal
 */
final class PanelPendaftaranTindakanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

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

    public function testTandaiLunasMemberiNomorDada(): void
    {
        $p     = $this->buatPendaftaranPanel();
        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Pendaftaran ditandai Lunas. 2 atlet mendapat nomor dada baru.');
        $this->assertSame('lunas', $this->statusPendaftaran($p['id']));
        $this->assertSame(['2001', '2002'], $this->nomorDada($p['atlet']));

        // Rincian sesudahnya menampilkan nomornya.
        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertSee('2001');
        $rincian->assertSee('2002');
    }

    public function testAwalanPenuhMenampilkanPesanDanTidakMengubahApaPun(): void
    {
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);
        $p = $this->buatPendaftaranPanel();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->assertGagalDialihkan($hasil, 'admin/pendaftaran/' . $p['id']);
        $this->assertStringContainsString('Tambahkan awalan baru untuk KU 10', (string) $_SESSION['pesan_galat']);
        $this->assertSame('menunggu', $this->statusPendaftaran($p['id']));
        $this->assertSame([null, null], $this->nomorDada($p['atlet']));
    }

    public function testKembaliKeMenungguMempertahankanNomor(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/menunggu');

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Status kembali menjadi Menunggu Konfirmasi Pembayaran. Nomor dada atlet tetap.');
        $this->assertSame('menunggu', $this->statusPendaftaran($p['id']));
        $this->assertSame(['2001', '2002'], $this->nomorDada($p['atlet']));

        $lagi = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');
        $this->assertBerhasilDialihkan($lagi, 'admin/pendaftaran/' . $p['id'], 'Pendaftaran ditandai Lunas. Semua atlet sudah punya nomor dada.');
        $this->assertSame(['2001', '2002'], $this->nomorDada($p['atlet']));
    }

    public function testStatusYangSamaDitolakDenganPesan(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/menunggu'),
            'admin/pendaftaran/' . $p['id'],
            'Pendaftaran ini memang masih Menunggu Konfirmasi Pembayaran.',
        );

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas'),
            'admin/pendaftaran/' . $p['id'],
            'Pendaftaran ini sudah Lunas.',
        );
    }

    public function testHapusMenghapusPendaftaranDanBerkasnya(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true]);
        $tetap = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null]]);
        $this->buatBuktiBayarPanel($p['id'], 2);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/hapus');

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran',
            'Pendaftaran ' . $p['id_pendaftaran'] . ' (SD KARANGAN MAJU) sudah dihapus beserta 2 atlet. NISN atletnya bisa didaftarkan lagi.',
        );
        $this->assertSame([$tetap['id']], array_map('intval', array_column($this->db->table('pendaftaran')->select('id')->get()->getResultArray(), 'id')));
        $this->assertSame(0, $this->db->table('atlet')->whereIn('id', $p['atlet'])->countAllResults());
        $this->assertSame([], $this->berkasFoto());
        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }

    public function testTindakanPadaPendaftaranYangTidakAda(): void
    {
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/999/hapus'), 'admin/pendaftaran', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/999/lunas'), 'admin/pendaftaran/999', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/999/menunggu'), 'admin/pendaftaran/999', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
    }

    public function testTokenCsrfWajib(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);

        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'] . '/lunas');
        $this->assertSame('menunggu', $this->statusPendaftaran($p['id']));
        $this->assertSame([null, null], $this->nomorDada($p['atlet']));

        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'] . '/hapus');
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
        $this->assertCount(1, $this->berkasFoto());

        $this->db->table('pendaftaran')->where('id', $p['id'])->update(['status' => 'lunas']);
        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'] . '/menunggu');
        $this->assertSame('lunas', $this->statusPendaftaran($p['id']));
    }

    public function testTindakanHanyaLewatPost(): void
    {
        $p = $this->buatPendaftaranPanel();

        foreach (['lunas', 'menunggu', 'hapus'] as $tindakan) {
            try {
                $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/' . $tindakan);
                $this->fail("GET {$tindakan} seharusnya tidak ditemukan.");
            } catch (CodeIgniter\Exceptions\PageNotFoundException $e) {
                $this->assertSame(404, $e->getCode());
            }
        }

        $this->assertSame('menunggu', $this->statusPendaftaran($p['id']));
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    // ---------- Tindakan massal (K76) ----------

    public function testMassalLunasMenungguDanYangGagalDisebut(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN JAYA', 'npsn' => '99999912']]);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'lunas', 'id' => [$a['id'], $b['id']]]);
        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran', '2 pendaftaran ditandai Lunas. 4 atlet mendapat nomor dada baru.');
        $this->assertSame(['lunas', 'lunas'], [$this->statusPendaftaran($a['id']), $this->statusPendaftaran($b['id'])]);

        // Yang sudah Menunggu tidak diubah dan disebut dengan ID-nya; yang lain tetap diproses.
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/menunggu');
        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'menunggu', 'id' => [$a['id'], $b['id']]]);
        $hasil->assertRedirectTo(site_url('admin/pendaftaran'));
        $this->assertSame('1 pendaftaran kembali menjadi Menunggu Konfirmasi Pembayaran. Nomor dada atlet tetap.', $_SESSION['pesan_sukses']);
        $this->assertStringStartsWith('1 pendaftaran tidak diubah: ' . $b['id_pendaftaran'] . ': ', (string) $_SESSION['pesan_galat']);
        $this->assertSame('menunggu', $this->statusPendaftaran($a['id']));
        $this->assertNotContains(null, $this->nomorDada($a['atlet']), 'Nomor dada tetap.');
    }

    public function testMassalHapus(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN JAYA', 'npsn' => '99999912']]);
        $c = $this->buatPendaftaranPanel(['sekolah' => ['nama' => 'SD KARANGAN SENTOSA', 'npsn' => '99999913']]);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'hapus', 'id' => [$a['id'], $c['id'], 999999]]);

        $hasil->assertRedirectTo(site_url('admin/pendaftaran'));
        $this->assertStringStartsWith('2 pendaftaran dihapus', (string) $_SESSION['pesan_sukses']);
        $this->assertStringContainsString('tidak ditemukan', (string) $_SESSION['pesan_galat']);
        $this->seeNumRecords(1, 'pendaftaran', []);
        $this->seeInDatabase('pendaftaran', ['id' => $b['id']]);
    }

    public function testMassalTanpaCentangAtauTindakanSalahDitolak(): void
    {
        $a = $this->buatPendaftaranPanel();

        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'lunas']), 'admin/pendaftaran', 'Belum ada pendaftaran yang dicentang. Centang pendaftarannya dulu, lalu pilih tindakan.');
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'kosongkan', 'id' => [$a['id']]]), 'admin/pendaftaran');
        $this->assertSame('menunggu', $this->statusPendaftaran($a['id']));
        $this->assertTokenCsrfWajib('admin/pendaftaran/massal', ['tindakan' => 'hapus', 'id' => [$a['id']]]);
        $this->seeInDatabase('pendaftaran', ['id' => $a['id']]);
    }

    private function statusPendaftaran(int $id): string
    {
        return (string) $this->db->table('pendaftaran')->where('id', $id)->get()->getRow()->status;
    }

    /**
     * @param list<int> $atlet
     *
     * @return list<string|null>
     */
    private function nomorDada(array $atlet): array
    {
        return array_column($this->db->table('atlet')->select('nomor_dada')->whereIn('id', $atlet)->orderBy('id')->get()->getResultArray(), 'nomor_dada');
    }
}
