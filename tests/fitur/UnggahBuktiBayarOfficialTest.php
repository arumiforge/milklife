<?php

use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Models\BuktiBayarModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\CekDataPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Official mengunggah bukti bayar sendiri (K83) lewat
 * `POST bukti/<kode>/<ID>/bukti-bayar`: dari wizard (JSON) dan dari Cek
 * Pendaftaran (formulir, kembali ke hasil pencarian).
 *
 * @internal
 */
final class UnggahBuktiBayarOfficialTest extends UjiBasisData
{
    use CekDataPendaftaran;
    use KirimFormulir;
    use UnggahanSementara;

    private string $folder = '';

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('throttler');
        service('cache')->clean();
        $this->folder = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-bukti-official-' . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
        mkdir($this->folder, 0777, true);
        BerkasBuktiBayar::gantiFolderDasar($this->folder);

        $this->lengkapiPengaturanCek();
        $this->isiDataCek();
    }

    protected function tearDown(): void
    {
        BerkasBuktiBayar::gantiFolderDasar(null);
        exec('rm -rf ' . escapeshellarg($this->folder));
        parent::tearDown();
    }

    private function alamatUnggah(string $id, ?string $kode = null): string
    {
        $p     = (new DataPendaftaran())->cariId($id);
        $bukti = substr(BuktiPendaftaran::alamat($p), strlen(rtrim(site_url('/'), '/')) + 1);

        if ($kode !== null) {
            $bukti = preg_replace('#^bukti/[0-9a-f]{24}/#', 'bukti/' . $kode . '/', $bukti);
        }

        return $bukti . '/bukti-bayar';
    }

    /**
     * @param array<string, string> $kepala
     * @param array<string, mixed>  $isian
     */
    private function kirim(string $alamat, array $kepala = [], array $isian = []): TestResponse
    {
        $namaToken = config('Security')->tokenName;
        $token     = bin2hex(random_bytes(32));
        Services::resetSingle('security');

        return $this->withSession([$namaToken => $token])->withHeaders($kepala)->post($alamat, [$namaToken => $token] + $isian);
    }

    private static function gambar(): string
    {
        $g = imagecreatetruecolor(300, 200);
        ob_start();
        imagepng($g);
        imagedestroy($g);

        return (string) ob_get_clean();
    }

    private function jumlah(string $id): int
    {
        return model(BuktiBayarModel::class)->jumlahMilik((int) (new DataPendaftaran())->cariId($id)['id']);
    }

    public function testDariWizardJawabanJson(): void
    {
        $this->siapkanBerkas('bukti', 'transfer.png', self::gambar());
        $hasil = $this->kirim($this->alamatUnggah('MAC2A0000126'), ['Accept' => 'application/json']);

        $hasil->assertStatus(201);
        $isi = json_decode($hasil->getJSON(), true);
        $this->assertSame('tersimpan', $isi['status']);
        $this->assertStringStartsWith('Bukti bayar terkirim (1 dari 5 gambar).', $isi['pesan']);
        $this->assertNotEmpty($isi['csrf']);
        $this->assertSame(1, $this->jumlah('MAC2A0000126'));
    }

    public function testDariCekKembaliKeHasilPencarian(): void
    {
        $this->siapkanBerkas('bukti', 'transfer.png', self::gambar());
        $hasil = $this->kirim($this->alamatUnggah('MAC2A0000126'), [], ['kembali' => '20300001']);

        $hasil->assertRedirectTo(site_url('cek-pendaftaran') . '?kode=20300001');
        $hasil->assertSessionHas('pesan_sukses');
        $this->assertSame(1, $this->jumlah('MAC2A0000126'));

        // Tujuan kembali yang aneh diganti ID pendaftaran.
        $this->siapkanBerkas('bukti', 'transfer.png', self::gambar());
        $this->kirim($this->alamatUnggah('MAC2A0000126'), [], ['kembali' => '//jahat.example'])
            ->assertRedirectTo(site_url('cek-pendaftaran') . '?kode=MAC2A0000126');
    }

    public function testBukanGambarDitolak(): void
    {
        $this->siapkanBerkas('bukti', 'bukti.pdf', self::isiPdf());
        $hasil = $this->kirim($this->alamatUnggah('MAC2A0000126'), ['Accept' => 'application/json']);

        $hasil->assertStatus(422);
        $this->assertStringStartsWith('Bukti bayar belum tersimpan.', json_decode($hasil->getJSON(), true)['pesan']);
        $this->assertSame(0, $this->jumlah('MAC2A0000126'));
    }

    public function testPendaftaranLunasTidakMenerimaBukti(): void
    {
        $this->siapkanBerkas('bukti', 'transfer.png', self::gambar());
        $hasil = $this->kirim($this->alamatUnggah('MAC2A0000226'), ['Accept' => 'application/json']);

        $hasil->assertStatus(409);
        $this->assertSame(0, $this->jumlah('MAC2A0000226'));
    }

    public function testKodeSalahTidakDitemukan(): void
    {
        $this->siapkanBerkas('bukti', 'transfer.png', self::gambar());

        try {
            $this->kirim($this->alamatUnggah('MAC2A0000126', str_repeat('0', 24)), ['Accept' => 'application/json']);
            $this->fail('Kode bukti salah seharusnya tidak ditemukan.');
        } catch (PageNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertSame(0, $this->jumlah('MAC2A0000126'));
    }

    public function testTokenCsrfWajib(): void
    {
        $this->assertTokenCsrfWajib($this->alamatUnggah('MAC2A0000126'));
    }

    public function testCekMenampilkanKotakUnggahHanyaUntukYangBelumLunas(): void
    {
        $hasil = $this->get('cek-pendaftaran?kode=20300001');

        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);
        $form  = $xpath->query('//form[contains(@action, "/bukti-bayar")]');
        $this->assertSame(1, $form->length, 'Hanya pendaftaran Menunggu yang punya kotak unggah.');
        $this->assertStringContainsString('/MAC2A0000126/bukti-bayar', $form->item(0)->getAttribute('action'));
        $this->assertSame('20300001', $xpath->query('.//input[@name="kembali"]', $form->item(0))->item(0)->getAttribute('value'));
    }
}
