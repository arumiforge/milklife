<?php

use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Status pembayaran dan nomor dada (`App\Libraries\Panel\StatusPembayaran`;
 * rancangan 6.9, K11, K32, K59).
 *
 * Awalan uji: KU 10 → 2, KU 12 → 3, KU 15 → 4 (`PanelDataContoh`).
 *
 * @internal
 */
final class PanelPendaftaranStatusTest extends UjiBasisData
{
    use PanelDataContoh;

    private ?BaseConnection $dbLain = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        if ($this->dbLain !== null) {
            if ($this->dbLain->transDepth > 0) {
                $this->dbLain->transRollback();
            }

            $this->dbLain->close();
            $this->dbLain = null;
        }

        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testTandaiLunasMemberiNomorBerurutanMenurutUrutanSimpan(): void
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(),
            $this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri']]),
            $this->atletPanel(),
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-03', 'nomor' => ['lari60putraKu12']]),
        ]]);

        $hasil = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertTrue($hasil['berhasil']);
        $this->assertNull($hasil['pesan']);

        [$a, $b, $c, $d] = $p['atlet'];
        $harapan         = [$a => '2001', $b => '4001', $c => '2002', $d => '3001'];
        ksort($harapan);
        $this->assertSame($harapan, $hasil['nomor']);
        $this->assertSame($harapan, $this->nomorDada($p['atlet']));

        $baris = $this->pendaftaran($p['id']);
        $this->assertSame('lunas', $baris['status']);
        $this->assertNotNull($baris['waktu_lunas']);
        $this->assertSame(['2' => 2, '3' => 1, '4' => 1], $this->nomorTerakhir());
    }

    public function testPendaftaranBerikutnyaMelanjutkanNomor(): void
    {
        $status = new StatusPembayaran();
        $satu   = $this->buatPendaftaranPanel();
        $dua    = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);

        $status->tandaiLunas($dua['id']);
        $status->tandaiLunas($satu['id']);

        $this->assertSame(['2001', '2002'], array_values($this->nomorDada($dua['atlet'])));
        $this->assertSame(['2003', '2004'], array_values($this->nomorDada($satu['atlet'])));
        $this->assertSame(4, $this->nomorTerakhir()['2']);
    }

    public function testBerlanjutKeAwalanBerikutnya(): void
    {
        // KU 10 punya awalan 2 (tinggal satu nomor) lalu 6.
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 998]);
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $this->idInduk['ku10'], 'awalan' => '6', 'urutan' => 2]);

        $p = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4)]);

        $hasil = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(['2999', '6001', '6002', '6003'], array_values($this->nomorDada($p['atlet'])));
        $this->assertSame(999, $this->nomorTerakhir()['2']);
        $this->assertSame(3, $this->nomorTerakhir()['6']);
    }

    public function testAwalanPenuhMembatalkanSeluruhPenandaan(): void
    {
        // KU 12 masih lapang, KU 10 hanya cukup untuk satu dari dua atlet.
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 998]);

        $p = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-03', 'nomor' => ['lari60putraKu12']]),
            $this->atletPanel(),
            $this->atletPanel(),
        ]]);

        $sebelum = $this->nomorTerakhir();
        $hasil   = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertFalse($hasil['berhasil']);
        $this->assertSame([], $hasil['nomor']);
        $this->assertStringContainsString('awalan nomor dada KU 10 (2) sudah penuh', (string) $hasil['pesan']);
        $this->assertStringContainsString('Tambahkan awalan baru untuk KU 10', (string) $hasil['pesan']);
        $this->assertStringNotContainsString('KU 12', (string) $hasil['pesan']);

        // Tidak ada yang berubah, termasuk atlet KU 12 yang awalannya masih lapang.
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
        $this->assertNull($this->pendaftaran($p['id'])['waktu_lunas']);
        $this->assertSame(array_fill_keys($p['atlet'], null), $this->nomorDada($p['atlet']));
        $this->assertSame($sebelum, $this->nomorTerakhir());

        // Setelah admin menambah awalan, penandaan berhasil.
        $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $this->idInduk['ku10'], 'awalan' => '7', 'urutan' => 2]);
        $ulang = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertTrue($ulang['berhasil']);
        $this->assertSame(['3001', '2999', '7001'], array_values($this->nomorDada($p['atlet'])));
    }

    public function testKelompokUmurTanpaAwalanDisebut(): void
    {
        $this->db->table('awalan_nomor_dada')->where('awalan', '4')->delete();
        $p = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri']]),
        ]]);

        $hasil = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringContainsString('KU 15 belum punya awalan nomor dada', (string) $hasil['pesan']);
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
    }

    public function testDuaKelompokUmurPenuhDisebutSemua(): void
    {
        $this->db->table('awalan_nomor_dada')->whereIn('awalan', ['2', '3'])->update(['nomor_terakhir' => 999]);
        $p = $this->buatPendaftaranPanel(['atlet' => [
            $this->atletPanel(),
            $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-03', 'nomor' => ['lari60putraKu12']]),
        ]]);

        $hasil = (new StatusPembayaran())->tandaiLunas($p['id']);

        $this->assertFalse($hasil['berhasil']);
        $this->assertStringContainsString('Tambahkan awalan baru untuk KU 10 dan KU 12', (string) $hasil['pesan']);
    }

    public function testKembaliKeMenungguTidakMenghapusNomorDanLunasLagiTidakMengubahnya(): void
    {
        $status = new StatusPembayaran();
        $p      = $this->buatPendaftaranPanel();

        $status->tandaiLunas($p['id']);
        $nomor = $this->nomorDada($p['atlet']);

        $hasil = $status->kembaliMenunggu($p['id']);
        $this->assertTrue($hasil['berhasil']);
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
        $this->assertNull($this->pendaftaran($p['id'])['waktu_lunas']);
        $this->assertSame($nomor, $this->nomorDada($p['atlet']));

        // Pendaftaran lain yang Lunas di antaranya tidak memakai nomor yang dipegang.
        $lain = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null]]);
        $status->tandaiLunas($lain['id']);
        $this->assertSame(['2003', '2004'], array_values($this->nomorDada($lain['atlet'])));

        $ulang = $status->tandaiLunas($p['id']);
        $this->assertTrue($ulang['berhasil']);
        $this->assertSame([], $ulang['nomor']);
        $this->assertSame($nomor, $this->nomorDada($p['atlet']));
        $this->assertSame(4, $this->nomorTerakhir()['2']);
    }

    public function testStatusYangSamaDitolakTanpaPerubahan(): void
    {
        $status = new StatusPembayaran();
        $p      = $this->buatPendaftaranPanel();

        $menunggu = $status->kembaliMenunggu($p['id']);
        $this->assertFalse($menunggu['berhasil']);
        $this->assertStringContainsString('masih Menunggu', (string) $menunggu['pesan']);

        $status->tandaiLunas($p['id']);
        $waktu = $this->pendaftaran($p['id'])['waktu_lunas'];

        $lunas = $status->tandaiLunas($p['id']);
        $this->assertFalse($lunas['berhasil']);
        $this->assertSame('Pendaftaran ini sudah Lunas.', $lunas['pesan']);
        $this->assertSame($waktu, $this->pendaftaran($p['id'])['waktu_lunas']);
    }

    public function testPendaftaranTidakAda(): void
    {
        $status = new StatusPembayaran();

        $this->assertFalse($status->tandaiLunas(999)['berhasil']);
        $this->assertFalse($status->kembaliMenunggu(999)['berhasil']);
        $this->assertSame(0, $this->db->transDepth);
    }

    public function testBeriNomorDadaHanyaUntukAtletBaruTanpaMengubahStatus(): void
    {
        // K59: atlet baru di pendaftaran Lunas yang totalnya tetap.
        $status = new StatusPembayaran();
        $p      = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4)]);
        $status->tandaiLunas($p['id']);
        $sebelum = $this->nomorDada($p['atlet']);

        $this->db->table('atlet')->insert([
            'pendaftaran_id' => $p['id'], 'nama' => 'Atlet Baru Karangan', 'nisn' => '9900199999',
            'tanggal_lahir' => '2017-01-01', 'jenis_kelamin' => 'L', 'kelompok_umur_id' => $this->idInduk['ku10'],
        ]);
        $baru = (int) $this->db->insertID();

        $hasil = $status->beriNomorDada($p['id']);

        $this->assertSame([$baru => '2005'], $hasil['nomor']);
        $this->assertSame([], $hasil['penuh']);
        $this->assertSame($sebelum, $this->nomorDada($p['atlet']));
        $this->assertSame('lunas', $this->pendaftaran($p['id'])['status']);

        // Dipanggil lagi: tidak ada yang perlu nomor.
        $this->assertSame(['nomor' => [], 'penuh' => [], 'pesan' => null], $status->beriNomorDada($p['id']));
        $this->assertSame(0, $this->db->transDepth);
    }

    public function testBeriNomorDadaDalamTransaksiPemanggil(): void
    {
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);
        $p = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => [$this->atletPanel()]]);

        $this->db->transBegin();
        $this->db->table('pendaftaran')->where('id', $p['id'])->update(['nama_sekolah' => 'SD UBAHAN']);

        $hasil = (new StatusPembayaran($this->db))->beriNomorDada($p['id']);

        $this->assertSame(['KU 10'], $hasil['penuh']);
        $this->assertNotNull($hasil['pesan']);
        // Transaksi pemanggil masih terbuka; pemanggil yang membatalkannya.
        $this->assertSame(1, $this->db->transDepth);
        $this->db->transRollback();

        $this->assertSame('SD KARANGAN MAJU', $this->pendaftaran($p['id'])['nama_sekolah']);
    }

    public function testNomorTersimpanDiAtasNomorTerakhirTidakDipakaiUlang(): void
    {
        // Data lama: atlet sudah memegang 2001–2002 walau nomor_terakhir masih 0.
        $lama = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => [
            $this->atletPanel(['nomor_dada' => '2001']),
            $this->atletPanel(['nomor_dada' => '2002']),
        ]]);
        $baru = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null]]);

        $hasil = (new StatusPembayaran())->tandaiLunas($baru['id']);

        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(['2003', '2004'], array_values($this->nomorDada($baru['atlet'])));
        $this->assertSame(['2001', '2002'], array_values($this->nomorDada($lama['atlet'])));
        $this->assertSame(4, $this->nomorTerakhir()['2']);
    }

    public function testDuaPenandaanBersamaanTidakMemberiNomorGanda(): void
    {
        $satu = $this->buatPendaftaranPanel();
        $dua  = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => null]]);

        // Sambungan lain sedang memberi nomor kepada pendaftaran pertama dan
        // belum selesai (transaksinya masih terbuka).
        $this->dbLain = Database::connect('tests', false);
        $this->dbLain->transBegin();
        $hasilLain = (new StatusPembayaran($this->dbLain))->tandaiLunas($satu['id']);
        $this->assertSame(['2001', '2002'], array_values($hasilLain['nomor']));

        // Penandaan kedua menunggu kunci awalan; dibuat cepat menyerah.
        $this->db->query('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            (new StatusPembayaran($this->db))->tandaiLunas($dua['id']);
            $this->fail('Penandaan kedua seharusnya menunggu kunci awalan nomor dada.');
        } catch (DatabaseException $e) {
            $this->assertStringContainsStringIgnoringCase('lock wait timeout', $e->getMessage());
        }

        $this->assertSame(0, $this->db->transDepth);
        $this->assertSame('menunggu', $this->pendaftaran($dua['id'])['status']);
        $this->assertSame(array_fill_keys($dua['atlet'], null), $this->nomorDada($dua['atlet']));

        // Setelah yang pertama selesai, yang kedua melanjutkan nomornya.
        $this->dbLain->transCommit();

        $hasil = (new StatusPembayaran($this->db))->tandaiLunas($dua['id']);
        $this->assertTrue($hasil['berhasil']);
        $this->assertSame(['2003', '2004'], array_values($this->nomorDada($dua['atlet'])));

        $semua = array_column($this->db->table('atlet')->select('nomor_dada')->where('nomor_dada IS NOT NULL')->get()->getResultArray(), 'nomor_dada');
        $this->assertCount(4, $semua);
        $this->assertSame($semua, array_values(array_unique($semua)));
    }

    /**
     * @param list<int> $atlet
     *
     * @return array<int, string|null> id atlet => nomor dada
     */
    private function nomorDada(array $atlet): array
    {
        $hasil = [];

        foreach ($this->db->table('atlet')->select('id, nomor_dada')->whereIn('id', $atlet)->orderBy('id')->get()->getResultArray() as $baris) {
            $hasil[(int) $baris['id']] = $baris['nomor_dada'];
        }

        return $hasil;
    }

    /**
     * @return array<string, int> awalan => nomor terakhir
     */
    private function nomorTerakhir(): array
    {
        $hasil = [];

        foreach ($this->db->table('awalan_nomor_dada')->orderBy('awalan')->get()->getResultArray() as $baris) {
            $hasil[(string) $baris['awalan']] = (int) $baris['nomor_terakhir'];
        }

        return $hasil;
    }

    /**
     * @return array<string, mixed>
     */
    private function pendaftaran(int $id): array
    {
        return $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();
    }
}
