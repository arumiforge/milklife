<?php

use App\Libraries\Panel\UbahPendaftaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman ubah pendaftaran di panel (F6-04): tombol Ubah di rincian,
 * formulir `admin/pendaftaran/<id>/ubah`, pratinjau JSON untuk jendela
 * konfirmasi, simpan `POST admin/pendaftaran/<id>`, dan formulir yang
 * ditampilkan ulang dengan galat.
 *
 * @internal
 */
final class PanelUbahPendaftaranHalamanTest extends UjiBasisData
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

    public function testRincianPunyaTombolUbah(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']));
        $tombol = $xpath->query('//a[@id="tombol-ubah"]');

        $this->assertSame(1, $tombol->length);
        $this->assertSame(site_url('admin/pendaftaran/' . $p['id'] . '/ubah'), $tombol->item(0)->getAttribute('href'));
    }

    public function testFormulirTerisiDataTersimpan(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas']);
        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/ubah');

        $hasil->assertOK();
        $hasil->assertSee('Ubah Pendaftaran');
        $hasil->assertSee('status kembali menjadi Menunggu Konfirmasi Pembayaran');
        $this->assertStringContainsString(aset('js/admin-ubah-pendaftaran.js'), (string) $hasil->getBody());
        $this->assertFileExists(FCPATH . 'aset/js/admin-ubah-pendaftaran.js');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('99999911', $xpath->query('//input[@name="sekolah[npsn]"]')->item(0)->getAttribute('value'));
        $this->assertSame((string) $p['atlet'][0], $xpath->query('//input[@name="atlet[0][id]"]')->item(0)->getAttribute('value'));
        $this->assertSame((string) $p['penanggung_jawab'][0], $xpath->query('//input[@name="penanggung_jawab[0][id]"]')->item(0)->getAttribute('value'));
        $this->assertSame(1, $xpath->query('//input[@name="atlet[0][nomor][]" and @value="' . $this->idInduk['frog'] . '" and @checked]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="atlet[0][nomor][]" and @value="' . $this->idInduk['turbo'] . '" and @checked]')->length);
        $this->assertSame(1, $xpath->query('//template[@id="templat-atlet"]')->length);
        $this->assertSame(1, $xpath->query('//form[@id="formulir-ubah"]//input[@name="' . config('Security')->tokenName . '"]')->length);
    }

    public function testPendaftaranYangTidakAda(): void
    {
        try {
            $this->bukaSebagaiAdmin('admin/pendaftaran/999/ubah');
            $this->fail('Formulir pendaftaran yang tidak ada seharusnya tidak ditemukan.');
        } catch (PageNotFoundException $e) {
            $this->assertSame(404, $e->getCode());
        }

        $this->assertGagalDialihkan(
            $this->kirimSebagaiAdmin('admin/pendaftaran/999', ['sekolah' => []]),
            'admin/pendaftaran',
            'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.',
        );
    }

    public function testHanyaUntukAdminDanWajibCsrf(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);

        $this->assertBelumMasukDialihkan('admin/pendaftaran/' . $p['id'] . '/ubah');
        $this->assertBelumMasukDialihkan('admin/pendaftaran/' . $p['id'], 'post');
        $this->assertTokenCsrfWajib('admin/pendaftaran/' . $p['id'], $this->isianDari($p['id']));
    }

    public function testSimpanBerhasilKembaliKeRincian(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true]);
        $isian = $this->isianDari($p['id']);
        $isian['atlet'][1]['nama'] = 'siti karangan baru';

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian);

        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id'], 'Perubahan pendaftaran ' . $p['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 450.000.');
        $this->assertSame('SITI KARANGAN BARU', $this->db->table('atlet')->where('id', $p['atlet'][1])->get()->getRow()->nama);
    }

    public function testIsianSalahDitampilkanUlang(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);

        $isian = $this->isianDari($b['id']);
        $isian['atlet'][0]['nisn'] = (string) $this->db->table('atlet')->where('id', $a['atlet'][0])->get()->getRow()->nisn;
        $isian['atlet'][1]['nama'] = '';

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'], $isian);

        $this->assertIsianSalahDitampilkanUlang($hasil, ['atlet[0][nisn]', 'atlet[1][nama]']);
        $hasil->assertSee('sudah terdaftar');

        // Isian yang dikirim tetap tampil, termasuk id baris tersimpan.
        $xpath = $this->xpathDari($hasil);
        $this->assertSame($isian['atlet'][0]['nisn'], $xpath->query('//input[@name="atlet[0][nisn]"]')->item(0)->getAttribute('value'));
        $this->assertSame((string) $b['atlet'][0], $xpath->query('//input[@name="atlet[0][id]"]')->item(0)->getAttribute('value'));
    }

    public function testPratinjauK32TidakMenulisDanMenyebutAkibatnya(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas']);
        $isian = $this->isianDari($p['id']);
        $isian['atlet'][1]['nomor'] = [];   // atlet putri tanpa nomor → galat dulu

        $galat = $this->json($this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian + ['pratinjau' => '1']));
        $this->assertSame(['atlet.1.nomor'], array_column($galat['galat'], 'jalur'));
        $this->assertNotEmpty($galat['csrf']);

        $isian = $this->isianDari($p['id']);
        unset($isian['atlet'][1]);          // total turun
        $isian['atlet'] = array_values($isian['atlet']);

        $jawaban = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian + ['pratinjau' => '1']);
        $isi     = $this->json($jawaban);

        $this->assertFalse($jawaban->isRedirect());
        $this->assertSame([], $isi['galat']);
        $this->assertTrue($isi['kembali_menunggu']);
        $this->assertSame([450000, 300000], [$isi['total_lama'], $isi['total']]);
        $this->assertStringContainsString('status kembali menjadi Menunggu Konfirmasi Pembayaran', $isi['kalimat']);
        $this->assertSame(2, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->countAllResults());
    }

    public function testK32TanpaPersetujuanDitampilkanUlangLaluTersimpanSetelahDisetujui(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas']);
        $isian = $this->isianDari($p['id']);
        $baru             = $this->atletPanel();
        $isian['atlet'][] = [
            'id' => '', 'nama' => $baru['nama'], 'nisn' => $baru['nisn'], 'tanggal_lahir' => $baru['tanggal_lahir'],
            'jenis_kelamin' => 'L', 'nomor' => [(string) $this->idInduk['lari60putra']],
        ];

        $tolak = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian + ['setuju_menunggu' => '0']);
        $tolak->assertStatus(200);
        $tolak->assertSee('Tekan Simpan perubahan lagi');
        $this->assertSame('lunas', $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRow()->status);

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian + ['setuju_menunggu' => '1']);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $p['id'],
            'Perubahan pendaftaran ' . $p['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 600.000. '
            . 'Status kembali menjadi Menunggu Konfirmasi Pembayaran; tandai Lunas lagi setelah selisihnya diurus.',
        );
        $this->assertSame('menunggu', $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRow()->status);
    }

    public function testK59PesanMenyebutNomorDadaBaru(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => $this->timPanel('kanga', 4, ['nomor_dada' => null])]);
        $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->set('nomor_dada', "CONCAT('2', LPAD(id, 3, '0'))", false)->update();

        $isian            = $this->isianDari($p['id']);
        $baru             = $this->atletPanel(['nomor' => ['kanga']]);
        $isian['atlet'][] = [
            'id' => '', 'nama' => $baru['nama'], 'nisn' => $baru['nisn'], 'tanggal_lahir' => $baru['tanggal_lahir'],
            'jenis_kelamin' => 'L', 'nomor' => [(string) $this->idInduk['kanga']],
        ];

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $p['id'],
            'Perubahan pendaftaran ' . $p['id_pendaftaran'] . ' tersimpan. Total sekarang Rp 100.000. 1 atlet baru mendapat nomor dada.',
        );
        $this->assertSame(0, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->where('nomor_dada', null)->countAllResults());
    }

    /**
     * @return array<string, mixed>
     */
    private function json(TestResponse $hasil): array
    {
        return json_decode((string) $hasil->response()->getBody(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Isian formulir seperti yang dikirim peramban (semua nilai teks).
     *
     * @return array<string, mixed>
     */
    private function isianDari(int $id): array
    {
        $isian = (new UbahPendaftaran())->isian($id);

        return [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => array_map(static fn (array $o): array => [
                'id' => (string) $o['id'], 'nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => '',
            ], $isian['penanggung_jawab']),
            'atlet' => array_map(static fn (array $a): array => [
                'id'            => (string) $a['id'],
                'nama'          => $a['nama'],
                'nisn'          => $a['nisn'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'nomor'         => array_map('strval', $a['nomor']),
            ], $isian['atlet']),
        ];
    }
}
