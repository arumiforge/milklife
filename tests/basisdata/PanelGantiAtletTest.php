<?php

use App\Libraries\Panel\GantiAtlet;
use App\Libraries\Panel\RiwayatAdmin;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Ganti atlet oleh admin di pendaftaran Lunas (`App\Libraries\Panel\GantiAtlet`,
 * F11-01, K172).
 *
 * Data induk dari `PanelDataContoh`: KU 10 lahir 2016–2018 (awalan nomor atlet
 * 2), KU 12 lahir 2015 (awalan 3). Semua nama dan NISN karangan.
 *
 * @internal
 */
final class PanelGantiAtletTest extends UjiBasisData
{
    use PanelDataContoh;

    private const ALASAN = 'Cedera lutut saat latihan';

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

    /**
     * @return array<string, string>
     */
    private function pengganti(array $ubah = []): array
    {
        return $ubah + ['nama' => '  dewi   karangan  ', 'nisn' => '9900199001', 'tanggal_lahir' => '2017-09-09'];
    }

    /**
     * Pendaftaran Lunas berisi satu putra KU 10 (Lari 60 m dan Frog Jump, BIB 2001)
     * dan satu putri KU 10.
     *
     * @return array<string, mixed>
     */
    private function pendaftaranLunas(): array
    {
        return $this->buatPendaftaranPanel([
            'status' => 'lunas',
            'atlet'  => [
                $this->atletPanel(['nomor' => ['lari60putra', 'frog'], 'nomor_dada' => '2001']),
                $this->atletPanel(['jenis_kelamin' => 'P', 'tanggal_lahir' => '2016-08-30', 'nomor' => ['lari60putri'], 'nomor_dada' => '2002']),
            ],
        ]);
    }

    public function testGantiAtletMewarisiNomorDanMendapatNomorAtletBaru(): void
    {
        $p       = $this->pendaftaranLunas();
        $lama    = $p['atlet'][0];
        $nisnLama = (string) $this->db->table('atlet')->where('id', $lama)->get()->getRow()->nisn;
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 2]);
        $total   = (int) $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRow()->total;

        $hasil = (new GantiAtlet())->ganti($p['id'], $lama, $this->pengganti(), self::ALASAN, 'Admin Uji', '2026-12-01');

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame('DEWI KARANGAN', $hasil['nama']);
        $this->assertSame('2003', $hasil['nomor_dada']);

        $this->assertSame(0, $this->db->table('atlet')->where('id', $lama)->countAllResults());
        $baru = $this->db->table('atlet')->where('nisn', '9900199001')->get()->getRowArray();
        $this->assertSame('DEWI KARANGAN', $baru['nama']);
        $this->assertSame('L', $baru['jenis_kelamin']);
        $this->assertSame($this->idInduk['ku10'], (int) $baru['kelompok_umur_id']);
        $this->assertSame((string) $p['id'], (string) $baru['pendaftaran_id']);

        $nomor = array_map('intval', array_column($this->db->table('atlet_nomor')->where('atlet_id', $baru['id'])->orderBy('nomor_lomba_id')->get()->getResultArray(), 'nomor_lomba_id'));
        $harap = [$this->idInduk['lari60putra'], $this->idInduk['frog']];
        sort($harap);
        $this->assertSame($harap, $nomor);

        $pendaftaran = $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRowArray();
        $this->assertSame('lunas', $pendaftaran['status']);
        $this->assertSame($total, (int) $pendaftaran['total']);

        $catatan = (new GantiAtlet())->riwayat($p['id']);
        $this->assertCount(1, $catatan);
        $this->assertSame($nisnLama, $catatan[0]['nisn']);
        $this->assertSame('2001', $catatan[0]['nomor_dada']);
        $this->assertSame('9900199001', $catatan[0]['pengganti_nisn']);
        $this->assertSame('2003', $catatan[0]['pengganti_nomor_dada']);
        $this->assertSame(self::ALASAN, $catatan[0]['alasan']);
        $this->assertCount(2, $catatan[0]['nomor_lomba']);

        $riwayat = $this->db->table('riwayat_admin')->where('pendaftaran_id', $p['id'])->where('tindakan', RiwayatAdmin::GANTI_ATLET)->get()->getRowArray();
        $this->assertNotNull($riwayat);
        $this->assertStringContainsString('nomor atlet 2001 diganti DEWI KARANGAN (9900199001), nomor atlet 2003', $riwayat['ringkasan']);

        // NISN atlet lama bisa didaftarkan lagi.
        $this->assertSame([], model(App\Models\AtletModel::class)->nisnTerdaftar([$nisnLama]));
    }

    public function testHanyaUntukPendaftaranLunas(): void
    {
        $p = $this->buatPendaftaranPanel();

        $form = (new GantiAtlet())->formulir($p['id'], $p['atlet'][0]);
        $this->assertStringContainsString('hanya untuk pendaftaran Lunas', $form['hambatan'][0]);

        $hasil = (new GantiAtlet())->ganti($p['id'], $p['atlet'][0], $this->pengganti(), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame(1, $this->db->table('atlet')->where('id', $p['atlet'][0])->countAllResults());
    }

    public function testAtletDariPendaftaranLainTidakDitemukan(): void
    {
        $p    = $this->pendaftaranLunas();
        $lain = $this->buatPendaftaranPanel(['status' => 'lunas', 'sekolah' => ['npsn' => '99999922', 'nama' => 'SD KARANGAN LAIN']]);

        $this->assertNull((new GantiAtlet())->formulir($p['id'], $lain['atlet'][0]));
        $this->assertNull((new GantiAtlet())->ganti($p['id'], $lain['atlet'][0], $this->pengganti(), self::ALASAN, 'Admin Uji'));
    }

    public function testPenggantiHarusSatuKelompokUmurDanNisnBelumTerdaftar(): void
    {
        $p     = $this->pendaftaranLunas();
        $ganti = new GantiAtlet();

        // Lahir 2015 masuk KU 12, bukan KU 10.
        $hasil = $ganti->ganti($p['id'], $p['atlet'][0], $this->pengganti(['tanggal_lahir' => '2015-01-10']), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertStringContainsString('tidak masuk KU 10', $hasil['galat']['tanggal_lahir']);

        // NISN atlet lain di pendaftaran yang sama.
        $nisnLain = (string) $this->db->table('atlet')->where('id', $p['atlet'][1])->get()->getRow()->nisn;
        $hasil    = $ganti->ganti($p['id'], $p['atlet'][0], $this->pengganti(['nisn' => $nisnLain]), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertStringContainsString('sudah terdaftar', $hasil['galat']['nisn']);

        // Isian kosong dan alasan terlalu pendek.
        $hasil = $ganti->ganti($p['id'], $p['atlet'][0], ['nama' => '', 'nisn' => '123', 'tanggal_lahir' => '2017-02-30'], 'a', 'Admin Uji', '2026-12-01');
        $this->assertSame(['nama', 'nisn', 'tanggal_lahir', 'alasan'], array_keys($hasil['galat']));

        $this->assertSame(1, $this->db->table('atlet')->where('id', $p['atlet'][0])->countAllResults());
        $this->assertSame(0, $this->db->table('atlet_diganti')->countAllResults());
    }

    public function testTempatDiSeriPindahKeAtletBaru(): void
    {
        $p     = $this->pendaftaranLunas();
        $babak = $this->babak('lari60putra');
        $this->db->table('hasil')->insert(['babak_id' => $babak, 'atlet_id' => $p['atlet'][0], 'seri' => 2, 'lintasan' => 5]);

        $hasil = (new GantiAtlet())->ganti($p['id'], $p['atlet'][0], $this->pengganti(), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertTrue($hasil['tersimpan']);

        $baru  = (int) $this->db->table('atlet')->where('nisn', '9900199001')->get()->getRow()->id;
        $baris = $this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray();
        $this->assertCount(1, $baris);
        $this->assertSame($baru, (int) $baris[0]['atlet_id']);
        $this->assertSame(2, (int) $baris[0]['seri']);
        $this->assertSame(5, (int) $baris[0]['lintasan']);
    }

    public function testAtletBerhasilAtauSeriDikunciTidakBisaDiganti(): void
    {
        $p     = $this->pendaftaranLunas();
        $babak = $this->babak('lari60putra');
        $this->db->table('hasil')->insert(['babak_id' => $babak, 'atlet_id' => $p['atlet'][0], 'seri' => 1, 'lintasan' => 4, 'prestasi' => 912]);

        $form = (new GantiAtlet())->formulir($p['id'], $p['atlet'][0]);
        $this->assertStringContainsString('sudah punya hasil', $form['hambatan'][0]);

        $this->db->table('hasil')->where('babak_id', $babak)->update(['prestasi' => null]);
        $this->db->table('babak')->where('id', $babak)->update(['seri_dikunci_pada' => '2026-12-09 07:00:00']);

        $form = (new GantiAtlet())->formulir($p['id'], $p['atlet'][0]);
        $this->assertStringContainsString('sudah dikunci', $form['hambatan'][0]);

        $hasil = (new GantiAtlet())->ganti($p['id'], $p['atlet'][0], $this->pengganti(), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame(1, $this->db->table('atlet')->where('id', $p['atlet'][0])->countAllResults());
    }

    public function testAnggotaReguMewarisiReguDanPeran(): void
    {
        $tim = $this->timPanel('kanga', 5);
        $p   = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => $tim]);

        // Atlet kelima adalah cadangan (inti Kanga's Escape 4 orang).
        $cadangan = $p['atlet'][4];
        $hasil    = (new GantiAtlet())->ganti($p['id'], $cadangan, $this->pengganti(), self::ALASAN, 'Admin Uji', '2026-12-01');
        $this->assertTrue($hasil['tersimpan']);

        $baru = (int) $this->db->table('atlet')->where('nisn', '9900199001')->get()->getRow()->id;
        $an   = $this->db->table('atlet_nomor')->where('atlet_id', $baru)->get()->getRowArray();
        $this->assertSame('A', $an['regu']);
        $this->assertSame('cadangan', $an['peran']);

        // Regu yang sudah punya hasil tidak bisa diganti anggotanya.
        $babak = $this->babak('kanga');
        $this->db->table('hasil')->insert(['babak_id' => $babak, 'pendaftaran_id' => $p['id'], 'regu' => 'A', 'prestasi' => 7012]);

        $form = (new GantiAtlet())->formulir($p['id'], $p['atlet'][0]);
        $this->assertStringContainsString('sudah punya hasil', implode(' ', $form['hambatan']));
    }

    public function testPeriksaMurni(): void
    {
        $lama = ['nisn' => '9900100001', 'kelompok_umur_id' => 2, 'kelompok_umur' => 'KU 10'];
        $ku   = [['id' => 2, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'jenjang' => ['A']]];

        $hasil = GantiAtlet::periksa($lama, ['nama' => 'budi', 'nisn' => '9900100001', 'tanggal_lahir' => '2017-01-01'], 'A', $ku, [], 'sakit', '2026-12-01');
        $this->assertStringContainsString('milik atlet yang diganti', $hasil['galat']['nisn']);

        $hasil = GantiAtlet::periksa($lama, ['nama' => 'budi', 'nisn' => '9900100009', 'tanggal_lahir' => '2027-01-01'], 'A', $ku, [], 'sakit', '2026-12-01');
        $this->assertStringContainsString('setelah hari ini', $hasil['galat']['tanggal_lahir']);

        $hasil = GantiAtlet::periksa($lama, ['nama' => 'budi', 'nisn' => '9900100009', 'tanggal_lahir' => '2017-01-01'], 'A', $ku, [], 'sakit', '2026-12-01');
        $this->assertSame([], $hasil['galat']);
        $this->assertSame(['nama' => 'BUDI', 'nisn' => '9900100009', 'tanggal_lahir' => '2017-01-01'], $hasil['data']);
    }

    private function babak(string $nomor): int
    {
        $this->db->table('babak')->insert(['nomor_lomba_id' => $this->idInduk[$nomor], 'nama' => 'Penyisihan', 'final' => 0, 'urutan' => 1]);

        return (int) $this->db->insertID();
    }
}
