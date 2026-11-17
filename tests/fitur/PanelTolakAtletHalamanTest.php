<?php

use CodeIgniter\Config\Services;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman tolak atlet di panel (`admin/pendaftaran/<id>/tolak-atlet`) dan
 * keterangannya di rincian pendaftaran serta Cek Pendaftaran (K116); menu
 * Atlet ditolak, keadaan "Semua atlet ditolak", bukti PDF, dan jejak navigasi (K117).
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

        // Cek Pendaftaran lewat NPSN: bahasa untuk sekolah, tanpa angka internal (K117).
        $cek = $this->get('cek-pendaftaran?kode=99999911');
        $cek->assertOK();
        $cek->assertSee('1 atlet ditolak panitia dan perlu didaftarkan ulang.');
        $cek->assertSee('1 atlet perlu didaftarkan ulang');
        $cek->assertSee('1 atlet ditolak');
        $cek->assertSee($nisn);
        $cek->assertSee('lahir 2016');
        $cek->assertSee('Tahun lahir 2016 masuk KU 10, bukan KU 12.');
        $cek->assertSee('Daftarkan ulang di');
        $cek->assertSee('KU 10 (lahir 2016–2018)');
        $cek->assertSee('Pilihan nomor:');
        $cek->assertSee('Menunggu Konfirmasi Pembayaran');   // atlet lain tetap Menunggu
        $cek->assertDontSee('Total pendaftaran ini berubah');
        $cek->assertDontSee('Rp 325.000');
        $cek->assertSee('https://wa.me/6281234567890');
        $cek->assertDontSee('2016-05-01');   // tanggal lahir tetap tidak tampil (K52)

        // Bukti PDF mencatat atlet yang ditolak beserta keterangannya.
        $html = (new App\Libraries\BuktiPendaftaran((new App\Libraries\DataPendaftaran())->cariId($p['id_pendaftaran'])))->html();
        $this->assertStringContainsString('Atlet Ditolak Panitia', $html);
        $this->assertStringContainsString('Daftarkan ulang di KU 10 (lahir 2016–2018)', $html);
        $this->assertStringContainsString('BUKTI PENDAFTARAN', $html);

        // Didaftarkan lagi: keterangan lengkapnya hilang, tinggal tanda sudah didaftarkan ulang.
        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(['nisn' => $nisn, 'tanggal_lahir' => '2016-05-01'])]]);
        Services::resetSingle('throttler');
        $lagi = $this->get('cek-pendaftaran?kode=99999911');
        $lagi->assertDontSee('perlu didaftarkan ulang');
        $lagi->assertDontSee('Alasan dari panitia');
        $lagi->assertSee('Atlet yang ditolak sudah didaftarkan ulang');
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'])->assertSee('Sudah didaftarkan ulang');
    }

    public function testSemuaAtletDitolakTidakMenungguPembayaran(): void
    {
        $p = $this->pendaftaranDenganAtletSalah();

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', [
            'atlet'  => array_map('strval', $p['atlet']),
            'alasan' => 'Kelompok umur sempat salah diatur panitia.',
        ]);
        $this->assertSame(0, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->countAllResults());

        // Cek Pendaftaran: status merah, tanpa tabel atlet, tanpa kotak bukti bayar.
        $cek = $this->get('cek-pendaftaran?kode=' . $p['id_pendaftaran']);
        $cek->assertOK();
        $cek->assertSee('Semua atlet ditolak');
        $cek->assertSee('cek-kartu-ditolak');
        $cek->assertSee('Semua atlet di pendaftaran ini ditolak panitia');
        $cek->assertSee('Tidak ada tagihan untuk pendaftaran ini');
        $cek->assertSee('Cetak keterangan');
        $cek->assertDontSee('Menunggu Konfirmasi Pembayaran');
        $cek->assertDontSee('cek-unggah-bukti');
        $cek->assertDontSee('cek-tabel');

        // Bukti PDF menjadi keterangan penolakan.
        $data  = (new App\Libraries\DataPendaftaran())->cariId($p['id_pendaftaran']);
        $bukti = new App\Libraries\BuktiPendaftaran($data);
        $this->assertTrue($data['ditolak_semua']);
        $this->assertStringContainsString('KETERANGAN PENOLAKAN ATLET', $bukti->html());
        $this->assertStringContainsString('Tidak ada tagihan', $bukti->html());
        $this->assertStringNotContainsString('Nomor Lomba Individu', $bukti->html());
        $this->assertStringEndsWith('_Ditolak.pdf', $bukti->namaBerkas());

        // Panel: peringatan di rincian dan lencana di daftar pendaftaran.
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'])->assertSee('Semua atlet pendaftaran ini ditolak.');
        $json = json_decode($this->bukaSebagaiAdmin('admin/pendaftaran/data?draw=1')->getJSON(), true);
        $baris = array_column($json['data'], null, 'id')[$p['id']];
        $this->assertTrue($baris['ditolak_semua']);
        $this->assertSame('Semua atlet ditolak', $baris['status_label']);
    }

    public function testMenuAtletDitolakMemisahkanKeadaan(): void
    {
        $p     = $this->pendaftaranDenganAtletSalah();
        $salah = $p['atlet'][1];
        $nisn  = (string) $this->db->table('atlet')->where('id', $salah)->get()->getRow()->nisn;
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', ['atlet' => [(string) $salah], 'alasan' => 'Tahun lahir 2016 masuk KU 10.']);

        $halaman = $this->bukaSebagaiAdmin('admin/atlet-ditolak');
        $halaman->assertOK();
        $halaman->assertSee('Belum didaftarkan ulang');
        $this->assertMatchesRegularExpression('#href="' . preg_quote(site_url('admin/atlet-ditolak'), '#') . '"\s+aria-current="page"#', $halaman->getBody());

        $data = static fn (string $kueri): array => json_decode((string) $kueri, true);
        $semua = $data($this->bukaSebagaiAdmin('admin/atlet-ditolak/data?draw=1')->getJSON());
        $this->assertSame(1, $semua['recordsTotal']);
        $this->assertSame($nisn, $semua['data'][0]['nisn']);
        $this->assertSame(site_url('admin/pendaftaran/' . $p['id'] . '?dari=atlet-ditolak'), $semua['data'][0]['id_pendaftaran']['alamat']);
        $this->assertStringContainsString('Belum didaftarkan ulang', $semua['data'][0]['keadaan']);
        $this->assertSame(0, $data($this->bukaSebagaiAdmin('admin/atlet-ditolak/data?draw=1&keadaan=sudah')->getJSON())['recordsFiltered']);

        // Didaftarkan lagi: pindah ke keadaan "sudah".
        $baru = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN BARU'], 'atlet' => [$this->atletPanel(['nisn' => $nisn, 'tanggal_lahir' => '2016-05-01'])]]);
        $sudah = $data($this->bukaSebagaiAdmin('admin/atlet-ditolak/data?draw=1&keadaan=sudah')->getJSON());
        $this->assertSame(1, $sudah['recordsFiltered']);
        $this->assertStringContainsString($baru['id_pendaftaran'], $sudah['data'][0]['keadaan']);
        $this->assertSame(0, $data($this->bukaSebagaiAdmin('admin/atlet-ditolak/data?draw=1&keadaan=belum')->getJSON())['recordsFiltered']);

        $excel = $this->bukaSebagaiAdmin('admin/atlet-ditolak/excel?keadaan=sudah');
        $excel->assertStatus(200);
        $tanggapan = $excel->response();
        $this->assertInstanceOf(CodeIgniter\HTTP\DownloadResponse::class, $tanggapan);
        $tanggapan->buildHeaders();
        $this->assertStringContainsString('AtletDitolak_' . date('Ymd') . '.xlsx', $tanggapan->getHeaderLine('Content-Disposition'));
    }

    public function testJejakDanMenuMengikutiAsalRincian(): void
    {
        $p       = $this->pendaftaranDenganAtletSalah();
        $rincian = 'admin/pendaftaran/' . $p['id'];
        $aktif   = static fn (string $alamat): string => '#href="' . preg_quote(site_url($alamat), '#') . '"\s+aria-current="page"#';

        // Dibuka dari menu Atlet: jejak dan menu aktif Atlet.
        $dariAtlet = $this->bukaSebagaiAdmin($rincian . '?dari=atlet');
        $dariAtlet->assertOK();
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="' . site_url('admin/atlet') . '">Atlet</a></li>', $dariAtlet->getBody());
        $this->assertMatchesRegularExpression($aktif('admin/atlet'), $dariAtlet->getBody());
        $this->assertDoesNotMatchRegularExpression($aktif('admin/pendaftaran'), $dariAtlet->getBody());
        $this->assertSame([$p['id'] => 'atlet'], session('asal_rincian'));

        // Halaman turunan memakai asal yang tersimpan di sesi.
        $tolak = $this->bukaSebagaiAdmin($rincian . '/tolak-atlet', ['asal_rincian' => [$p['id'] => 'atlet']]);
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="' . site_url($rincian) . '">' . $p['id_pendaftaran'] . '</a></li>', $tolak->getBody());
        $this->assertStringContainsString('<li class="breadcrumb-item active" aria-current="page">Tolak atlet</li>', $tolak->getBody());
        $this->assertMatchesRegularExpression($aktif('admin/atlet'), $tolak->getBody());

        // Tanpa asal, atau asal tidak dikenal: Pendaftaran.
        $bawaan = $this->bukaSebagaiAdmin($rincian . '?dari=lain');
        $this->assertStringContainsString('<li class="breadcrumb-item"><a href="' . site_url('admin/pendaftaran') . '">Pendaftaran</a></li>', $bawaan->getBody());
        $this->assertMatchesRegularExpression($aktif('admin/pendaftaran'), $bawaan->getBody());
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
