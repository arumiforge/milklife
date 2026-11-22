<?php

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\RiwayatAdmin;
use App\Libraries\Panel\StatusPembayaran;
use App\Libraries\Panel\UbahPendaftaran;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Riwayat tindakan admin (F8-01, K124): setiap tindakan yang dicatat, bagian
 * Riwayat di rincian pendaftaran, halaman `admin/riwayat`, penggabungan
 * (K109), serta ekspor/impor pindah server (K76).
 *
 * @internal
 */
final class PanelRiwayatAdminTest extends UjiBasisData
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

    public function testTandaiLunasDicatatDanTampilDiRincian(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $riwayat = $this->riwayat($p['id']);
        $this->assertCount(1, $riwayat);
        $this->assertSame(RiwayatAdmin::LUNAS, $riwayat[0]['tindakan']);
        $this->assertSame('Admin Uji', $riwayat[0]['nama_admin']);
        $this->assertSame(1, (int) $riwayat[0]['admin_id']);
        $this->assertSame($p['id_pendaftaran'], $riwayat[0]['id_pendaftaran']);
        $this->assertSame('Ditandai Lunas. 2 atlet mendapat nomor atlet (BIB) baru: 2001, 2002.', $riwayat[0]['ringkasan']);

        $rincian = $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id']);
        $rincian->assertSeeElement('#riwayat');
        $rincian->assertSee('Ditandai Lunas. 2 atlet mendapat nomor atlet (BIB) baru: 2001, 2002.');
        $rincian->assertSee('Admin Uji');
    }

    public function testRincianTanpaRiwayat(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $p['id'])->assertSee('Belum ada tindakan admin yang tercatat.');
    }

    public function testKembaliMenungguTerbaruDiAtas(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/menunggu');

        $daftar = (new RiwayatAdmin())->untukPendaftaran($p['id']);

        $this->assertSame([RiwayatAdmin::MENUNGGU, RiwayatAdmin::LUNAS], array_column($daftar, 'tindakan'));
        $this->assertSame('Status kembali dari Lunas ke Menunggu. Nomor atlet (BIB) tetap.', $daftar[0]['ringkasan']);
        $this->assertSame('Kembali ke Menunggu', $daftar[0]['label']);
    }

    public function testTindakanGagalTidakDicatat(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/menunggu');
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->assertSame([], $this->riwayat($p['id']));
    }

    public function testTindakanMassalDicatatPerPendaftaran(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);

        $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'lunas', 'id' => [$a['id'], $b['id']]]);
        $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'menunggu', 'id' => [$b['id']]]);

        $this->assertSame(['Ditandai Lunas (tindakan massal). 2 atlet mendapat nomor atlet (BIB) baru: 2001, 2002.'], array_column($this->riwayat($a['id']), 'ringkasan'));
        $this->assertSame(
            ['Ditandai Lunas (tindakan massal). 2 atlet mendapat nomor atlet (BIB) baru: 2003, 2004.', 'Status kembali dari Lunas ke Menunggu (tindakan massal). Nomor atlet (BIB) tetap.'],
            array_column($this->riwayat($b['id']), 'ringkasan'),
        );
    }

    public function testUbahMencatatPerubahanAtletTotalDanStatus(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas']);
        $isian = $this->isianDari($p['id']);
        $hapus = $isian['atlet'][1];
        unset($isian['atlet'][1]);
        $isian['atlet']            = array_values($isian['atlet']);
        $isian['atlet'][0]['nama'] = 'Budi Karangan Baru';

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian + ['setuju_menunggu' => '1']);
        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id']);

        $riwayat = $this->riwayat($p['id']);
        $this->assertCount(1, $riwayat);
        $this->assertSame(RiwayatAdmin::UBAH, $riwayat[0]['tindakan']);
        $this->assertSame(
            'Atlet dihapus: ' . $hapus['nama'] . ' (' . $hapus['nisn'] . '). Atlet diubah: BUDI KARANGAN BARU (' . $isian['atlet'][0]['nisn'] . ') (data diri). '
            . 'Total Rp 450.000 → Rp 300.000. Status kembali dari Lunas ke Menunggu karena total berubah.',
            $riwayat[0]['ringkasan'],
        );
    }

    public function testUbahMenambahAtletDanMengubahOfisial(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true]);
        $isian = $this->isianDari($p['id']);
        $baru  = $this->atletPanel(['nama' => 'Dewi Karangan']);
        $isian['atlet'][] = [
            'id' => '', 'nama' => $baru['nama'], 'nisn' => $baru['nisn'], 'tanggal_lahir' => $baru['tanggal_lahir'],
            'jenis_kelamin' => 'L', 'nomor' => [(string) $this->idInduk['lari60putra']],
        ];
        $isian['penanggung_jawab'][0]['nama'] = 'Ibu Karangan Baru';

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'], $isian);

        $this->assertSame(
            'Data ofisial diubah. Atlet ditambah: DEWI KARANGAN (' . $baru['nisn'] . '). Total Rp 450.000 → Rp 600.000.',
            $this->riwayat($p['id'])[0]['ringkasan'],
        );
    }

    public function testTolakAtletDicatatSekaliTanpaBarisUbah(): void
    {
        $p     = $this->buatPendaftaranPanel(['foto' => true]);
        $atlet = $this->db->table('atlet')->where('id', $p['atlet'][1])->get()->getRowArray();

        $hasil = $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', [
            'atlet'  => [(string) $p['atlet'][1]],
            'alasan' => 'Tahun lahir tidak sesuai kelompok umur.',
        ]);
        $this->assertBerhasilDialihkan($hasil, 'admin/pendaftaran/' . $p['id']);

        $riwayat = $this->riwayat($p['id']);
        $this->assertSame([RiwayatAdmin::TOLAK_ATLET], array_column($riwayat, 'tindakan'));
        $this->assertSame(
            '1 atlet ditolak: ' . $atlet['nama'] . ' (' . $atlet['nisn'] . '). Alasan: Tahun lahir tidak sesuai kelompok umur. Total Rp 450.000 → Rp 300.000.',
            $riwayat[0]['ringkasan'],
        );
    }

    public function testTolakSemuaAtletDicatat(): void
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(['nama' => 'Eko Karangan'])]]);

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/tolak-atlet', [
            'atlet'  => [(string) $p['atlet'][0]],
            'alasan' => 'Tahun lahir tidak sesuai kelompok umur',
        ]);

        $riwayat = $this->riwayat($p['id']);
        $this->assertSame([RiwayatAdmin::TOLAK_ATLET], array_column($riwayat, 'tindakan'));
        $this->assertStringEndsWith('Alasan: Tahun lahir tidak sesuai kelompok umur. Total Rp 150.000 → Rp 0.', $riwayat[0]['ringkasan']);
    }

    public function testHapusMenyimpanRiwayatDanBisaDicari(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($p['id']);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');

        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/hapus');

        $sisa = $this->db->table('riwayat_admin')->where('id_pendaftaran', $p['id_pendaftaran'])->orderBy('id')->get()->getResultArray();
        $this->assertSame([RiwayatAdmin::LUNAS, RiwayatAdmin::HAPUS], array_column($sisa, 'tindakan'));
        $this->assertSame([null, null], array_column($sisa, 'pendaftaran_id'));
        $this->assertSame('Pendaftaran SD KARANGAN MAJU dihapus beserta 2 atlet dan 1 bukti bayar. Status saat dihapus: Lunas, total Rp 450.000.', $sisa[1]['ringkasan']);

        $cari = $this->bukaSebagaiAdmin('admin/riwayat?id=' . strtolower(substr($p['id_pendaftaran'], 0, 8)));
        $cari->assertOK();
        $cari->assertSee('2 catatan, terbaru di atas.');
        $cari->assertSee('(sudah dihapus)');
        $cari->assertSee('Status saat dihapus: Lunas');
    }

    public function testHapusMassalDicatat(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->kirimSebagaiAdmin('admin/pendaftaran/massal', ['tindakan' => 'hapus', 'id' => [$p['id']]]);

        $this->assertStringContainsString('dihapus (tindakan massal) beserta 2 atlet', (string) $this->db->table('riwayat_admin')->get()->getRow()->ringkasan);
    }

    public function testHalamanRiwayatKosongDanHanyaUntukAdmin(): void
    {
        $this->assertBelumMasukDialihkan('admin/riwayat');

        $kosong = $this->bukaSebagaiAdmin('admin/riwayat');
        $kosong->assertOK();
        $kosong->assertSeeElement('#form-cari-riwayat');
        $kosong->assertDontSee('catatan, terbaru di atas');

        $this->bukaSebagaiAdmin('admin/riwayat?id=MAC9Z')->assertSee('Belum ada tindakan admin yang tercatat.');

        // Pendaftaran yang masih ada ditautkan ke rinciannya.
        $p = $this->buatPendaftaranPanel();
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/lunas');
        $this->assertStringContainsString(site_url('admin/pendaftaran/' . $p['id']), $this->bukaSebagaiAdmin('admin/riwayat?id=' . $p['id_pendaftaran'])->getBody());
    }

    public function testUnggahDanHapusBuktiBayarOlehAdminDicatat(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'bukti.png', self::gambarPanel(200, 100));
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar'), 'admin/pendaftaran/' . $p['id']);

        $bukti = (int) $this->db->table('bukti_bayar')->where('pendaftaran_id', $p['id'])->get()->getRow()->id;
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/pendaftaran/' . $p['id'] . '/bukti-bayar/' . $bukti . '/hapus'), 'admin/pendaftaran/' . $p['id']);

        $riwayat = $this->riwayat($p['id']);
        $this->assertSame([RiwayatAdmin::UNGGAH_BUKTI, RiwayatAdmin::HAPUS_BUKTI], array_column($riwayat, 'tindakan'));
        $this->assertSame('Bukti bayar diunggah admin (gambar ke-1 dari paling banyak 100).', $riwayat[0]['ringkasan']);
        $this->assertStringStartsWith('Bukti bayar ke-1 dihapus (diunggah ', $riwayat[1]['ringkasan']);
    }

    public function testUnggahOlehOfisialTidakDicatat(): void
    {
        $p = $this->buatPendaftaranPanel();
        $this->siapkanBerkas(BuktiBayarController::KOTAK, 'bukti.png', self::gambarPanel(200, 100));

        $this->assertNull((new App\Libraries\Panel\BerkasBuktiBayar())->unggahKe($p['id'], service('request')->getFile(BuktiBayarController::KOTAK)));
        $this->assertSame([], $this->riwayat($p['id']));
    }

    public function testUrutkanNomorAtletDicatatUntukPendaftaranYangBerubah(): void
    {
        $status = new StatusPembayaran();
        $x      = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $y      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA'], 'atlet' => [$this->atletPanel()]]);
        $z      = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN TIGA'], 'atlet' => [$this->atletPanel()]]);
        $status->tandaiLunas($y['id']);   // 2001
        $status->tandaiLunas($x['id']);   // 2002
        $status->tandaiLunas($z['id']);   // 2003
        $status->kembaliMenunggu($z['id']);
        $this->db->table('riwayat_admin')->emptyTable();

        $this->kirimSebagaiAdmin('admin/atlet/nomor-dada', ['konfirmasi' => 'URUTKAN']);

        $this->assertSame(['Nomor atlet (BIB) diurutkan ulang per sekolah. 1 atlet berganti nomor: 2002 → 2001.'], array_column($this->riwayat($x['id']), 'ringkasan'));
        $this->assertSame(['Nomor atlet (BIB) diurutkan ulang per sekolah. 1 atlet berganti nomor: 2001 → 2002.'], array_column($this->riwayat($y['id']), 'ringkasan'));
        $this->assertSame(['Nomor atlet (BIB) diurutkan ulang per sekolah. Nomor 2003 dikosongkan karena pendaftaran belum Lunas.'], array_column($this->riwayat($z['id']), 'ringkasan'));
        $this->assertSame(RiwayatAdmin::URUTKAN, $this->riwayat($x['id'])[0]['tindakan']);
    }

    public function testGabungMemindahkanRiwayatKeTujuan(): void
    {
        $awal  = $this->buatPendaftaranPanel();
        $susul = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $awal['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $susul['id'] . '/lunas');

        $riwayat = (new RiwayatAdmin())->untukPendaftaran($awal['id']);

        $this->assertSame([RiwayatAdmin::GABUNG, RiwayatAdmin::LUNAS, RiwayatAdmin::LUNAS], array_column($riwayat, 'tindakan'));
        $this->assertSame([$awal['id_pendaftaran'], $susul['id_pendaftaran'], $awal['id_pendaftaran']], array_column($riwayat, 'id_pendaftaran'));
        $this->assertSame('Digabung dari ' . $susul['id_pendaftaran'] . ' karena NPSN-nya sama. Atlet, ofisial, regu, dan bukti bayarnya pindah ke sini. Total ditambah Rp 150.000.', $riwayat[0]['ringkasan']);

        // Baris dari pendaftaran yang digabung diberi keterangan asalnya.
        $this->bukaSebagaiAdmin('admin/pendaftaran/' . $awal['id'])->assertSee('dari ' . $susul['id_pendaftaran']);
    }

    public function testPerintahSparkTercatatSebagaiSistem(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_nama']);
        $p = $this->buatPendaftaranPanel();

        (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertSame([RiwayatAdmin::SISTEM], array_column($this->riwayat($p['id']), 'nama_admin'));
        $this->assertSame([null], array_column($this->riwayat($p['id']), 'admin_id'));
    }

    public function testEksporImporMembawaRiwayatTermasukYangDihapus(): void
    {
        $zip = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-paket-' . bin2hex(random_bytes(6)) . '.zip';
        $a   = $this->buatPendaftaranPanel();
        $b   = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999912', 'nama' => 'SD KARANGAN DUA']]);
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $a['id'] . '/lunas');
        $this->kirimSebagaiAdmin('admin/pendaftaran/' . $b['id'] . '/hapus');

        $kolom   = 'pendaftaran_id, id_pendaftaran, nama_admin, tindakan, ringkasan, dibuat_pada';
        $sebelum = $this->db->table('riwayat_admin')->select($kolom)->orderBy('id')->get()->getResultArray();
        $this->assertCount(2, $sebelum);

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $this->db->query('DELETE FROM pendaftaran');
            $this->db->query('DELETE FROM riwayat_admin');
            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->assertSame($sebelum, $this->db->table('riwayat_admin')->select($kolom)->orderBy('id')->get()->getResultArray());
        $this->assertSame(0, $this->db->table('riwayat_admin')->where('admin_id IS NOT NULL', null, false)->countAllResults());
    }

    /**
     * Baris riwayat satu pendaftaran, urut dicatat.
     *
     * @return list<array<string, mixed>>
     */
    private function riwayat(int $pendaftaranId): array
    {
        return $this->db->table('riwayat_admin')->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->get()->getResultArray();
    }

    /**
     * Isian formulir Ubah seperti yang dikirim peramban.
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
                'regu'          => $a['regu'],
            ], $isian['atlet']),
        ];
    }
}
