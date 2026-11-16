<?php

use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Pendaftaran Lunas dengan NPSN sama digabung (K109): pesan dan pengalihan di
 * panel admin, kode QR bukti lama, Cek Pendaftaran dengan ID lama, dan
 * keterangan "Gabungan dari" di bukti PDF.
 *
 * @internal
 */
final class PanelGabungPendaftaranHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

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
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testTandaiLunasMengalihkanKePendaftaranGabungan(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $susul['id'] . '/lunas');

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $awal['id'],
            'Pendaftaran ditandai Lunas. 1 atlet mendapat nomor atlet (BIB) baru. Pendaftaran ' . $susul['id_pendaftaran'] . ' digabung ke ' . $awal['id_pendaftaran']
            . ' karena NPSN-nya sama. ID lama tetap bisa dipakai di Cek Pendaftaran, dan kode QR bukti lama membuka bukti gabungan.',
        );

        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $awal['id']);
        $rincian->assertSeeElement('#gabungan-dari');
        $rincian->assertSee($susul['id_pendaftaran']);
    }

    public function testTandaiLunasMassalMenyebutPenggabungan(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'lunas', 'id' => [$a['id'], $b['id']]]);

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran');
        $this->assertStringContainsString('2 pendaftaran ditandai Lunas.', (string) $_SESSION['pesan_sukses']);
        $this->assertStringContainsString('Pendaftaran ' . $b['id_pendaftaran'] . ' digabung ke ' . $a['id_pendaftaran'], (string) $_SESSION['pesan_sukses']);
        $this->assertSame(1, $this->db->table('pendaftaran')->countAllResults());
    }

    public function testKodeQrBuktiLamaDiarahkanKeBuktiGabungan(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $lama  = BuktiPendaftaran::alamat(['id' => $susul['id'], 'id_pendaftaran' => $susul['id_pendaftaran']]);
        (new StatusPembayaran())->tandaiLunas($susul['id']);

        $hasil = $this->get(substr($lama, strlen(site_url('/'))));

        $hasil->assertRedirectTo(BuktiPendaftaran::alamat(['id' => $awal['id'], 'id_pendaftaran' => $awal['id_pendaftaran']]));

        // ID lama dengan kode yang salah tetap tidak ditemukan.
        try {
            $this->get('bukti/' . str_repeat('a', 24) . '/' . $susul['id_pendaftaran']);
            $this->fail('Kode salah seharusnya 404.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    public function testCekDenganIdLamaMenampilkanPendaftaranGabungan(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        (new StatusPembayaran())->tandaiLunas($susul['id']);

        $hasil = $this->get('cek-pendaftaran?kode=' . $susul['id_pendaftaran']);

        $hasil->assertOK();
        $hasil->assertSeeElement('#kode-info');
        $hasil->assertSee('Pendaftaran ' . $susul['id_pendaftaran'] . ' sudah digabung ke ' . $awal['id_pendaftaran']);
        $hasil->assertSee('Gabungan dari ' . $susul['id_pendaftaran']);
        $hasil->assertDontSeeElement('#kode-galat');
    }

    public function testBuktiPdfMenulisIdLama(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        (new StatusPembayaran())->tandaiLunas($susul['id']);

        $html = (new BuktiPendaftaran((new DataPendaftaran())->cariId($awal['id_pendaftaran'])))->html();

        $this->assertMatchesRegularExpression('#<div class="gabungan"[^>]*>Gabungan dari ' . $susul['id_pendaftaran'] . '</div>#', $html);
        $this->assertStringStartsWith('%PDF', (new BuktiPendaftaran((new DataPendaftaran())->cariId($awal['id_pendaftaran'])))->pdf());
    }

    public function testCariIdLamaDiDaftarAdmin(): void
    {
        $awal  = $this->buatPendaftaranPanel(['status' => 'lunas']);
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN LAIN']]);
        (new StatusPembayaran())->tandaiLunas($susul['id']);

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/data?draw=1&start=0&length=10&search[value]=' . $susul['id_pendaftaran']);

        $data = json_decode($hasil->getJSON(), true);
        $this->assertSame([$awal['id_pendaftaran']], array_column($data['data'], 'id_pendaftaran'));
    }
}
