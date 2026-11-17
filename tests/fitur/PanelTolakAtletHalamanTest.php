<?php

use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman tolak atlet di panel (`admin/pendaftaran/<id>/tolak-atlet`) dan
 * keterangannya di rincian pendaftaran serta Cek Pendaftaran (K116).
 *
 * @internal
 */
final class PanelTolakAtletHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        Services::resetSingle('throttler');
        service('cache')->clean();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testRincianMenandaiAtletTidakCocokDanMenautkanTolak(): void
    {
        $p = $this->pendaftaranDenganAtletSalah();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);

        $hasil->assertOK();
        $hasil->assertSee('Tidak cocok dengan pengaturan sekarang, seharusnya KU 10 (lahir 2016–2018).');
        $this->assertStringContainsString(site_url('admin/pendaftaran/' . $p['id'] . '/tolak-atlet'), $hasil->getBody());

        // Pendaftaran Lunas tidak punya tombol tolak.
        $lunas = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);
        $this->assertStringNotContainsString('tombol-tolak-atlet', $this->bukaSebagaiAdmin('admin/pendaftaran/' . $lunas['id'])->getBody());
    }

    public function testFormulirMencentangAtletYangTidakCocok(): void
    {
        $p = $this->pendaftaranDenganAtletSalah();

        $hasil = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet');

        $hasil->assertOK();
        $hasil->assertSee('Seharusnya KU 10 (lahir 2016–2018)');
        $hasil->assertSee(App\Controllers\Admin\TolakAtletController::ALASAN_BAWAAN);
        $this->assertMatchesRegularExpression('/value="' . $p['atlet'][1] . '" id="tolak-' . $p['atlet'][1] . '"\s+checked/', $hasil->getBody());
        $this->assertDoesNotMatchRegularExpression('/value="' . $p['atlet'][0] . '" id="tolak-' . $p['atlet'][0] . '"\s+checked/', $hasil->getBody());
    }

    public function testTolakLaluCekPendaftaranMenampilkanKeterangan(): void
    {
        setting('HalamanDepan.nomor_wa_tanya', '081234567890');
        $p     = $this->pendaftaranDenganAtletSalah();
        $salah = $p['atlet'][1];
        $nisn  = (string) $this->db->table('atlet')->where('id', $salah)->get()->getRow()->nisn;

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', [
            'atlet'  => [(string) $salah],
            'alasan' => 'Tahun lahir 2016 masuk KU 10, bukan KU 12.',
        ]);

        $this->assertBerhasilDialihkan(
            $hasil,
            'admin/pendaftaran/' . $p['id'],
            '1 atlet ditolak dari pendaftaran ' . $p['id_pendaftaran'] . '. NISN-nya bisa didaftarkan lagi. Total berubah dari Rp 325.000 menjadi Rp 150.000.'
            . ' Alasan dan rinciannya tampil di Cek Pendaftaran sampai atlet itu didaftarkan lagi.',
        );

        // Rincian admin: daftar atlet ditolak.
        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertSee('Atlet ditolak');
        $rincian->assertSee('Belum didaftarkan ulang');

        // Cek Pendaftaran lewat NPSN.
        $cek = $this->get('cek-pendaftaran?kode=99999911');
        $cek->assertOK();
        $cek->assertSee('1 atlet ditolak panitia.');
        $cek->assertSee('Atlet ditolak panitia');
        $cek->assertSee($nisn);
        $cek->assertSee('lahir tahun 2016');
        $cek->assertSee('Tahun lahir 2016 masuk KU 10, bukan KU 12.');
        $cek->assertSee('KU 10 (lahir 2016–2018)');
        $cek->assertSee('Rp 150.000 per nomor');
        $cek->assertSee('Total pendaftaran ini berubah dari Rp 325.000 menjadi Rp 150.000');
        $cek->assertSee('https://wa.me/6281234567890');
        $cek->assertDontSee('2016-05-01');   // tanggal lahir tetap tidak tampil (K52)

        // Didaftarkan lagi: keterangannya hilang.
        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(['nisn' => $nisn, 'tanggal_lahir' => '2016-05-01'])]]);
        Services::resetSingle('throttler');
        $lagi = $this->get('cek-pendaftaran?kode=99999911');
        $lagi->assertDontSee('Atlet ditolak panitia');
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'])->assertSee('Sudah didaftarkan ulang');
    }

    public function testGalatDitampilkanUlangTanpaMengubahData(): void
    {
        $p = $this->pendaftaranDenganAtletSalah();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', ['atlet' => [(string) $p['atlet'][1]], 'alasan' => 'pendek']);

        $hasil->assertOK();
        $hasil->assertSee('Alasan penolakan belum diisi atau terlalu pendek.');
        $this->assertSame(2, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->countAllResults());
        $this->assertSame(0, $this->db->table('atlet_ditolak')->countAllResults());
    }

    /**
     * @return array<string, mixed>
     */
    private function pendaftaranDenganAtletSalah(): array
    {
        return $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [
            $this->atletPanel(),
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2016-05-01', 'nomor' => ['lari60putraKu12']]),
        ]]);
    }
}
