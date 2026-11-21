<?php

use App\Libraries\BuktiPendaftaran;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Daftar ulang hari lomba (F8-03): cari lewat ID atau isi kode QR, tandai
 * hadir per atlet dan semua, batal hadir, kode QR gabungan (K109), tautan
 * dari halaman bukti untuk admin, menu, serta ekspor/impor dan penggabungan
 * yang membawa tanda hadir.
 *
 * @internal
 */
final class HariLombaDaftarUlangTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private const ALAMAT = 'admin/daftar-ulang';

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();

        Services::resetSingle('throttler');
        service('cache')->clean();
        $_SERVER['SCRIPT_NAME'] ??= '/index.php';
    }

    protected function tearDown(): void
    {
        unset($_COOKIE[config('Session')->cookieName]);
        service('superglobals')->unsetCookie(config('Session')->cookieName);
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testHalamanDanTindakanHanyaUntukAdmin(): void
    {
        $this->assertBelumMasukDialihkan(self::ALAMAT);
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/hadir', 'post');
        $this->assertBelumMasukDialihkan(self::ALAMAT . '/1/batal-hadir', 'post');
    }

    public function testHalamanKosongPunyaPemindaiDanIsianId(): void
    {
        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT);

        $hasil->assertOK();
        $hasil->assertSeeElement('#pemindai');
        $hasil->assertSeeElement('#isian-id');
        $hasil->assertSee('js/daftar-ulang.js');
        $hasil->assertDontSeeElement('#hasil-daftar-ulang');

        // Kelompok "Hari lomba" di menu samping (F9-14): setiap halaman hari
        // lomba menandai butirnya sendiri. Butir ini hanya di menu samping
        // (sidebar di ponsel), tidak di menu bawah (K133).
        foreach ([self::ALAMAT, 'admin/nomor-atlet/cetak', 'admin/daftar-peserta'] as $alamat) {
            $aktif = $this->xpathDari($this->bukaSebagaiAdmin($alamat))->query('//a[@data-menu-tautan][@aria-current="page"]');
            $this->assertSame(1, $aktif->length, $alamat);
            $this->assertSame(site_url($alamat), $aktif->item(0)?->getAttribute('href'), $alamat);
            $this->assertSame(1, $this->xpathDari($this->bukaSebagaiAdmin($alamat))->query('//*[@data-menu-kelompok="Hari lomba"]//a[@aria-current="page"]')->length, $alamat);
        }
    }

    public function testCariLewatIdDanAlamatKodeQr(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($p['id']);

        foreach ([strtolower($p['id_pendaftaran']), BuktiPendaftaran::alamat(['id' => $p['id'], 'id_pendaftaran' => $p['id_pendaftaran']])] as $isian) {
            $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . rawurlencode($isian));
            $hasil->assertOK();
            $hasil->assertSee('SD KARANGAN MAJU');
            $hasil->assertSee('Lunas');
            $hasil->assertSeeElement('#formulir-hadir-semua');
            $hasil->assertDontSeeElement('#keterangan-gabung');
            // Nomor atlet (BIB), nomor lomba, dan jumlah hadir.
            $hasil->assertSee('2001');
            $hasil->assertSee('Lari 60 m putra');
            $this->assertStringContainsString('0</strong> dari 2 atlet hadir', (string) $hasil->response()->getBody());
        }
    }

    public function testIsianSalahDanTidakDitemukan(): void
    {
        $salah = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=halo');
        $salah->assertSeeElement('#galat-cari');
        $salah->assertSee('Kode QR atau ID pendaftaran tidak dikenali');

        $tidakAda = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=MAC2A9999926');
        $tidakAda->assertSee('Pendaftaran MAC2A9999926 tidak ditemukan.');
        $tidakAda->assertDontSeeElement('#hasil-daftar-ulang');
    }

    public function testMenungguTampilTetapiBelumBisaHadir(): void
    {
        $p = $this->buatPendaftaranPanel();

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . $p['id_pendaftaran']);
        $hasil->assertSee('Menunggu');
        $hasil->assertSeeElement('#peringatan-menunggu');
        $hasil->assertDontSeeElement('#formulir-hadir-semua');

        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/hadir', ['semua' => '1']),
            site_url(self::ALAMAT) . '?id=' . $p['id_pendaftaran'] . '#daftar-atlet',
            'Pendaftaran ' . $p['id_pendaftaran'] . ' belum Lunas, jadi atletnya belum bisa ditandai hadir. Periksa pembayarannya dulu di menu Pendaftaran.',
        );
        $this->assertSame(0, $this->db->table('atlet')->where('hadir_pada IS NOT NULL', null, false)->countAllResults());
    }

    public function testSemuaAtletDitolakTampilDenganStatusnya(): void
    {
        $p = $this->buatPendaftaranPanel(['atlet' => []]);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . $p['id_pendaftaran']);
        $hasil->assertOK();
        $hasil->assertSee('Pendaftaran ini tidak punya atlet.');
    }

    public function testTandaiHadirPerAtletSemuaLaluBatal(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new StatusPembayaran())->tandaiLunas($p['id']);
        $kembali = site_url(self::ALAMAT) . '?id=' . $p['id_pendaftaran'] . '#daftar-atlet';
        [$satu, $dua] = $p['atlet'];

        // Satu atlet.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/hadir', ['atlet' => [$satu]]), $kembali, '1 atlet ditandai hadir.');
        $baris = $this->db->table('atlet')->where('id', $satu)->get()->getRowArray();
        $this->assertNotNull($baris['hadir_pada']);
        $this->assertSame('Admin Uji', $baris['hadir_oleh']);
        $this->assertNull($this->db->table('atlet')->where('id', $dua)->get()->getRow()->hadir_pada);

        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . $p['id_pendaftaran']);
        $this->assertStringContainsString('1</strong> dari 2 atlet hadir', (string) $halaman->response()->getBody());
        $halaman->assertSee('Batal hadir');

        // Semua: hanya yang belum hadir yang dihitung, waktu yang lama tetap.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/hadir', ['semua' => '1']), $kembali, '1 atlet ditandai hadir.');
        $this->assertSame($baris['hadir_pada'], $this->db->table('atlet')->where('id', $satu)->get()->getRow()->hadir_pada);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/hadir', ['semua' => '1']), $kembali, 'Atlet yang dipilih sudah tercatat hadir sebelumnya.');
        $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . $p['id_pendaftaran'])->assertDontSeeElement('#formulir-hadir-semua');

        // Batal hadir.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/batal-hadir', ['atlet' => $dua]), $kembali, 'Tanda hadir dibatalkan.');
        $this->seeInDatabase('atlet', ['id' => $dua, 'hadir_pada' => null, 'hadir_oleh' => null]);
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/batal-hadir', ['atlet' => $dua]), $kembali, 'Atlet itu belum tercatat hadir.');

        // Tanpa pilihan, dan pendaftaran yang tidak ada.
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/' . $p['id'] . '/hadir'), $kembali, 'Belum ada atlet yang dipilih.');
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/99999/hadir', ['semua' => '1']), self::ALAMAT);
    }

    public function testAtletPendaftaranLainTidakIkutDitandai(): void
    {
        $a = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $b = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN LAIN'], 'status' => 'lunas', 'atlet' => [$this->atletPanel()]]);

        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a['id'] . '/hadir', ['atlet' => [$b['atlet'][0]]]);
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $a['id'] . '/batal-hadir', ['atlet' => $b['atlet'][0]]);

        $this->seeInDatabase('atlet', ['id' => $b['atlet'][0], 'hadir_pada' => null]);
    }

    public function testKodeQrLamaYangSudahDigabungMembukaPendaftaranGabungan(): void
    {
        $status = new StatusPembayaran();
        $awal   = $this->buatPendaftaranPanel();
        $status->tandaiLunas($awal['id']);
        $this->kirimSebagaiAdmin(self::ALAMAT . '/' . $awal['id'] . '/hadir', ['atlet' => [$awal['atlet'][0]]]);

        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $lama  = BuktiPendaftaran::alamat(['id' => $susul['id'], 'id_pendaftaran' => $susul['id_pendaftaran']]);
        $status->tandaiLunas($susul['id']);

        // Tanda hadir ikut pendaftaran gabungan (barisnya pindah, K109).
        $this->assertNotNull($this->db->table('atlet')->where('id', $awal['atlet'][0])->get()->getRow()->hadir_pada);

        $hasil = $this->bukaSebagaiAdmin(self::ALAMAT . '?id=' . rawurlencode($lama));
        $hasil->assertSeeElement('#keterangan-gabung');
        $hasil->assertSee('ID ' . $susul['id_pendaftaran'] . ' sudah digabung ke ' . $awal['id_pendaftaran']);
        $this->assertStringContainsString('1</strong> dari 3 atlet hadir', (string) $hasil->response()->getBody());
    }

    public function testHalamanBuktiUntukAdminMenautkanDaftarUlang(): void
    {
        $p      = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $alamat = substr(BuktiPendaftaran::alamat(['id' => $p['id'], 'id_pendaftaran' => $p['id_pendaftaran']]), strlen(site_url('/')));

        // Pengunjung biasa langsung mendapat PDF.
        $publik = $this->get($alamat);
        $this->assertStringStartsWith('application/pdf', $publik->response()->getHeaderLine('Content-Type'));

        // Admin yang sedang masuk mendapat pilihan.
        $_COOKIE[config('Session')->cookieName] = 'sesi-uji';
        service('superglobals')->setCookie(config('Session')->cookieName, 'sesi-uji');
        $admin = $this->bukaSebagaiAdmin($alamat);
        $admin->assertOK();
        $xpath = $this->xpathDari($admin);
        $this->assertSame(site_url(self::ALAMAT) . '?id=' . $p['id_pendaftaran'], $xpath->query('//a[@id="tautan-daftar-ulang"]')->item(0)->getAttribute('href'));
        $this->assertStringEndsWith('/' . $p['id_pendaftaran'] . '?pdf=1', $xpath->query('//a[@id="tautan-bukti-pdf"]')->item(0)->getAttribute('href'));

        // Bukti PDF tetap bisa dibuka admin.
        Services::resetSingle('throttler');
        $pdf = $this->bukaSebagaiAdmin($alamat . '?pdf=1');
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
    }

    public function testEksporImporMembawaTandaHadir(): void
    {
        $p = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $this->db->table('atlet')->where('id', $p['atlet'][0])->update(['hadir_pada' => '2026-12-09 07:15:00', 'hadir_oleh' => 'Admin Gerbang']);
        $zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-hadir-' . bin2hex(random_bytes(6)) . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $this->db->query('DELETE FROM pendaftaran');
            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->seeInDatabase('atlet', ['hadir_oleh' => 'Admin Gerbang', 'hadir_pada' => '2026-12-09 07:15:00']);
        $this->assertSame(1, $this->db->table('atlet')->where('hadir_pada IS NOT NULL', null, false)->countAllResults());
    }
}
