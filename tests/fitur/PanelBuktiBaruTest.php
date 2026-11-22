<?php

use App\Controllers\Admin\BuktiBayarController;
use App\Database\Migrations\TambahDiperiksaBuktiBayar;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\Panel\RingkasanDasbor;
use App\Libraries\Panel\TolakAtlet;
use Config\Database;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UjiHalaman;
use Tests\Support\UnggahanSementara;

/**
 * Penanda bukti bayar baru yang belum diperiksa (F8-02, K124; asumsi 2 di
 * K125): tanda dan tombol "Sudah diperiksa" di rincian, Lunas menandai
 * diperiksa, unggahan admin langsung diperiksa, saringan dan tanda di daftar
 * pendaftaran, kartu dasbor (tanpa "Semua atlet ditolak", K117), migrasi data
 * lama, dan ekspor/impor.
 *
 * @internal
 */
final class PanelBuktiBaruTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->pakaiFolderBuktiBayarSementara();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testBuktiOfisialBaruDanMembukaRincianTidakMenandai(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], 2);

        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertSeeElement('#bukti-baru');
        $rincian->assertSeeElement('#form-bukti-diperiksa');
        $rincian->assertSee('2 bukti bayar baru dari ofisial belum diperiksa.');
        $this->assertSame(2, $this->xpathDari($rincian)->query('//*[@data-bukti-baru-tanda]')->length);

        // Membuka rincian saja tidak menandai.
        $this->assertSame(2, $this->belumDiperiksa($p['id']));
    }

    public function testTombolSudahDiperiksaMenandaiDanMencatatRiwayat(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], 2);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/diperiksa');

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], '2 bukti bayar baru ditandai sudah diperiksa.');
        $this->assertSame(0, $this->belumDiperiksa($p['id']));
        $this->assertSame(['Admin Uji', 'Admin Uji'], array_column($this->db->table('bukti_bayar')->get()->getResultArray(), 'diperiksa_oleh'));
        $this->assertSame('menunggu', $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRow()->status);

        $riwayat = $this->db->table('riwayat_admin')->where('pendaftaran_id', $p['id'])->get()->getResultArray();
        $this->assertSame([RiwayatAdmin::PERIKSA_BUKTI], array_column($riwayat, 'tindakan'));
        $this->assertSame('2 bukti bayar baru ditandai sudah diperiksa.', $riwayat[0]['ringkasan']);

        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertDontSeeElement('#bukti-baru');
        $this->assertSame(0, $this->xpathDari($rincian)->query('//*[@data-bukti-baru-tanda]')->length);

        // Ditekan lagi: tidak ada yang ditandai, tidak dicatat lagi.
        $this->assertBerhasilDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/diperiksa'),
            'admin/pendaftaran/' . $p['id'],
            'Tidak ada bukti bayar baru. Semuanya sudah diperiksa.',
        );
        $this->assertSame(1, $this->db->table('riwayat_admin')->where('pendaftaran_id', $p['id'])->countAllResults());
    }

    public function testTombolSudahDiperiksaHanyaUntukAdminDanPendaftaranYangAda(): void
    {
        $this->assertBelumMasukDialihkan('admin/pendaftaran/1/bukti-bayar/diperiksa', 'post');
        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/999/bukti-bayar/diperiksa'),
            'admin/pendaftaran',
            'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.',
        );
    }

    public function testTandaiLunasMenandaiBuktiDiperiksa(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id']);

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->assertSame(0, $this->belumDiperiksa($p['id']));
        $this->assertStringEndsWith(' 1 bukti bayar baru ditandai sudah diperiksa.', (string) $this->db->table('riwayat_admin')->where('pendaftaran_id', $p['id'])->get()->getRow()->ringkasan);
    }

    public function testUnggahanAdminLangsungDiperiksaUnggahanOfisialBelum(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'bukti.png', self::gambarPanel(200, 100));
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar');
        $admin = $this->db->table('bukti_bayar')->where('pendaftaran_id', $p['id'])->get()->getRowArray();
        $this->assertNotNull($admin['diperiksa_pada']);
        $this->assertSame('Admin Uji', $admin['diperiksa_oleh']);

        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'bukti.png', self::gambarPanel(200, 100));
        $this->assertNull((new BerkasBuktiBayar())->unggahKe($p['id'], service('request')->getFile(BuktiBayarController::KOTAK)));
        $this->assertSame(1, $this->belumDiperiksa($p['id']));
    }

    public function testTandaDanSaringanDiDaftarPendaftaran(): void
    {
        $baru  = $this->buatPendaftaranPanel();
        $sudah = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA']]);
        $tanpa = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN TIGA']]);
        $this->buatBuktiBayarPanel($baru['id'], 2);
        $this->buatBuktiBayarPanel($sudah['id']);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $sudah['id'] . '/bukti-bayar/diperiksa');

        $semua = array_column($this->json()['data'], 'bukti_baru', 'id');
        $this->assertSame([$tanpa['id'] => 0, $sudah['id'] => 0, $baru['id'] => 2], $semua);

        $this->assertSame([$baru['id']], array_column($this->json(['bukti' => 'baru'])['data'], 'id'));
        // Nilai lain diabaikan.
        $this->assertCount(3, $this->json(['bukti' => 'apa'])['data']);

        $halaman = $this->bukaSebagaiAdmin('admin/pendaftaran');
        $xpath   = $this->xpathDari($halaman);
        $this->assertSame(['', 'baru', 'ada'], array_map(static fn (DOMElement $o): string => $o->getAttribute('value'), iterator_to_array($xpath->query('//select[@id="saring-bukti"]/option'))));
        $halaman->assertSee('Bukti baru belum diperiksa');
        $this->assertSame('statusBukti', $xpath->query('//th[@data-kolom="status"]')->item(0)->getAttribute('data-bentuk'));
        $this->assertStringContainsString('bentuk.statusBukti', (string) file_get_contents(FCPATH . 'aset/js/admin-pendaftaran.js'));
    }

    public function testKartuDasborTanpaSemuaAtletDitolak(): void
    {
        $baru    = $this->buatPendaftaranPanel();
        $ditolak = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA'], 'atlet' => [$this->atletPanel()]]);
        $lunas   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN TIGA'], 'status' => 'lunas']);
        $this->buatBuktiBayarPanel($baru['id']);
        $this->buatBuktiBayarPanel($ditolak['id']);
        $this->buatBuktiBayarPanel($lunas['id']);   // bukti baru setelah Lunas tetap dihitung

        $hasil = (new TolakAtlet())->tolak($ditolak['id'], [$ditolak['atlet'][0]], 'Tahun lahir tidak sesuai kelompok umur.', 'Admin Uji');
        $this->assertTrue($hasil['tersimpan'] && $hasil['semua']);

        $this->assertSame(2, (new RingkasanDasbor())->angka()['bukti_baru']);

        $dasbor = $this->bukaSebagaiAdmin('admin/dasbor');
        $kartu  = $this->xpathDari($dasbor)->query('//*[@data-tambahan="bukti_baru"]')->item(0);
        $this->assertNotNull($kartu);
        $this->assertTrue($kartu->hasAttribute('data-sorot'), 'Kartu bukti baru disorot.');
        $this->assertStringContainsString('2', $kartu->textContent);
        $this->assertStringContainsString('Bukti bayar baru belum diperiksa', $kartu->textContent);
        $this->assertSame(site_url('admin/pendaftaran?bukti=baru'), $this->xpathDari($dasbor)->query('//*[@data-tambahan="bukti_baru"]//a')->item(0)->getAttribute('href'));
    }

    /**
     * F10-04: "Menunggu, bukti sudah ada" (perlu_diperiksa) dan "Bukti bayar
     * baru belum diperiksa" (bukti_baru) dua hitungan berbeda; kartunya
     * menyebut bedanya dan tautannya membuka daftar dengan hitungan yang sama.
     */
    public function testDuaAngkaBuktiBayarDasborBedaDanCocokDenganSaringan(): void
    {
        $baru    = $this->buatPendaftaranPanel();
        $sudah   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA']]);
        $lunas   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN TIGA'], 'status' => 'lunas']);
        $tanpa   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN EMPAT']]);
        $ditolak = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => '99999915', 'nama' => 'SD KARANGAN LIMA'], 'atlet' => [$this->atletPanel()]]);
        $this->buatBuktiBayarPanel($baru['id']);
        $this->buatBuktiBayarPanel($sudah['id']);
        $this->buatBuktiBayarPanel($lunas['id']);     // bukti tambahan setelah Lunas
        $this->buatBuktiBayarPanel($ditolak['id']);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $sudah['id'] . '/bukti-bayar/diperiksa');
        $hasil = (new TolakAtlet())->tolak($ditolak['id'], [$ditolak['atlet'][0]], 'Tahun lahir tidak sesuai kelompok umur.', 'Admin Uji');
        $this->assertTrue($hasil['tersimpan'] && $hasil['semua']);

        $angka = (new RingkasanDasbor())->angka();
        // Menunggu dengan bukti (sudah diperiksa atau belum): baru dan sudah.
        $this->assertSame(2, $angka['perlu_diperiksa']);
        // Bukti belum diperiksa di status apa pun: baru dan lunas.
        $this->assertSame(2, $angka['bukti_baru']);

        $ada = array_column($this->json(['status' => 'menunggu', 'bukti' => 'ada'])['data'], 'id');
        sort($ada);
        $this->assertSame([$baru['id'], $sudah['id']], $ada);
        $this->assertNotContains($tanpa['id'], array_column($this->json(['bukti' => 'ada'])['data'], 'id'));
        $this->assertContains($lunas['id'], array_column($this->json(['bukti' => 'ada'])['data'], 'id'));

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/dasbor'));
        $kartu = $xpath->query('//*[@data-tambahan="perlu_diperiksa"]')->item(0);
        $this->assertStringContainsString('Menunggu, bukti sudah ada', $kartu->textContent);
        $this->assertStringContainsString('ditandai Lunas', $xpath->query('.//*[@data-keterangan]', $kartu)->item(0)->textContent);
        $this->assertSame(site_url('admin/pendaftaran?status=menunggu&bukti=ada'), $xpath->query('.//a', $kartu)->item(0)->getAttribute('href'));
        $kartuBaru = $xpath->query('//*[@data-tambahan="bukti_baru"]')->item(0);
        $this->assertStringContainsString('termasuk di pendaftaran Lunas', $xpath->query('.//*[@data-keterangan]', $kartuBaru)->item(0)->textContent);
        $this->assertStringNotContainsString('Bukti bayar perlu diperiksa', $xpath->document->saveHTML());
    }

    public function testMigrasiMenandaiBuktiPendaftaranLunasSebagaiDiperiksa(): void
    {
        $migrasi = new TambahDiperiksaBuktiBayar(Database::forge($this->DBGroup));
        $migrasi->down();

        $menunggu = $this->buatPendaftaranPanel();
        $lunas    = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA'], 'status' => 'lunas']);
        $this->db->table('pendaftaran')->where('id', $lunas['id'])->update(['waktu_lunas' => '2026-11-15 09:00:00']);

        foreach ([$menunggu['id'], $lunas['id']] as $id) {
            $this->db->table('bukti_bayar')->insert(['pendaftaran_id' => $id, 'berkas' => 'bukti-bayar/' . str_repeat('a', 31) . $id . '.jpg', 'ukuran' => 10, 'waktu_unggah' => '2026-11-14 08:00:00']);
        }

        $migrasi->up();

        $baris = array_column($this->db->table('bukti_bayar')->get()->getResultArray(), null, 'pendaftaran_id');
        $this->assertNull($baris[$menunggu['id']]['diperiksa_pada']);
        $this->assertSame('2026-11-15 09:00:00', $baris[$lunas['id']]['diperiksa_pada']);
        $this->assertSame('Data lama', $baris[$lunas['id']]['diperiksa_oleh']);
    }

    public function testEksporImporMembawaPenanda(): void
    {
        $zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-paket-' . bin2hex(random_bytes(6)) . '.zip';
        $p   = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id'], 2);
        $id = $this->db->table('bukti_bayar')->select('id')->orderBy('id')->get()->getRow()->id;
        $this->db->table('bukti_bayar')->where('id', $id)->update(['diperiksa_pada' => '2026-11-16 10:00:00', 'diperiksa_oleh' => 'Admin Uji']);

        $kolom   = 'berkas, diperiksa_pada, diperiksa_oleh';
        $sebelum = $this->db->table('bukti_bayar')->select($kolom)->orderBy('id')->get()->getResultArray();

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $this->db->query('DELETE FROM pendaftaran');
            $this->db->query('DELETE FROM riwayat_admin');

            foreach (glob(BerkasBuktiBayar::folderDasar() . BerkasBuktiBayar::FOLDER . '/*.jpg') ?: [] as $berkas) {
                unlink($berkas);
            }

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->assertSame($sebelum, $this->db->table('bukti_bayar')->select($kolom)->orderBy('id')->get()->getResultArray());
    }

    private function belumDiperiksa(int $pendaftaranId): int
    {
        return $this->db->table('bukti_bayar')->where('pendaftaran_id', $pendaftaranId)->where('diperiksa_pada', null)->countAllResults();
    }

    /**
     * @param array<string, mixed> $parameter
     *
     * @return array<string, mixed>
     */
    private function json(array $parameter = []): array
    {
        $hasil = $this->withSession(UjiHalaman::SESI_ADMIN)->get('admin/pendaftaran/data', $parameter);
        $hasil->assertOK();

        return json_decode((string) $hasil->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }
}
