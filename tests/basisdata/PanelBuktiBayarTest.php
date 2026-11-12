<?php

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Models\BuktiBayarModel;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Tabel `bukti_bayar` (migrasi F6-01), `BuktiBayarModel`, dan bagian
 * `BerkasBuktiBayar` yang memakai basis data.
 *
 * @internal
 */
final class PanelBuktiBayarTest extends UjiBasisData
{
    use PanelDataContoh;

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

    public function testStrukturTabel(): void
    {
        $kolom = $this->db->query(
            'SELECT COLUMN_NAME AS kolom, COLUMN_TYPE AS tipe, IS_NULLABLE AS kosong
             FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$this->db->getDatabase(), 'bukti_bayar'],
        )->getResultArray();

        $this->assertSame([
            ['kolom' => 'id', 'tipe' => 'int unsigned', 'kosong' => 'NO'],
            ['kolom' => 'pendaftaran_id', 'tipe' => 'int unsigned', 'kosong' => 'NO'],
            ['kolom' => 'berkas', 'tipe' => 'varchar(255)', 'kosong' => 'NO'],
            ['kolom' => 'ukuran', 'tipe' => 'int unsigned', 'kosong' => 'NO'],
            ['kolom' => 'waktu_unggah', 'tipe' => 'datetime', 'kosong' => 'YES'],
        ], $kolom);

        $tabel = $this->db->query(
            'SELECT ENGINE AS mesin, TABLE_COLLATION AS kolasi FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$this->db->getDatabase(), 'bukti_bayar'],
        )->getRowArray();
        $this->assertSame(['mesin' => 'InnoDB', 'kolasi' => 'utf8mb4_unicode_ci'], $tabel);
    }

    public function testMigrasiHanyaMenambahTabelBaru(): void
    {
        $migrasi = new App\Database\Migrations\BuatTabelBuktiBayar(Config\Database::forge($this->DBGroup));
        $tabelLain = array_values(array_diff($this->db->listTables(true), ['bukti_bayar']));

        // Digulung balik: hanya bukti_bayar yang hilang, tabel lain tetap.
        $migrasi->down();
        $this->assertFalse($this->db->tableExists('bukti_bayar', false));
        $this->assertSame($tabelLain, array_values($this->db->listTables(true)));

        $migrasi->up();
        $this->assertTrue($this->db->tableExists('bukti_bayar', false));
    }

    public function testIkutTerhapusBersamaPendaftaran(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $this->buatBuktiBayarPanel($a['id'], 2);
        $this->buatBuktiBayarPanel($b['id'], 1);

        $this->db->table('pendaftaran')->where('id', $a['id'])->delete();

        $this->assertSame(0, $this->db->table('bukti_bayar')->where('pendaftaran_id', $a['id'])->countAllResults());
        $this->assertSame(1, $this->db->table('bukti_bayar')->where('pendaftaran_id', $b['id'])->countAllResults());
    }

    public function testPendaftaranHarusAda(): void
    {
        try {
            $this->db->table('bukti_bayar')->insert(['pendaftaran_id' => 9999, 'berkas' => 'bukti-bayar/x.jpg', 'ukuran' => 1]);
            $this->fail('Bukti bayar tanpa pendaftaran seharusnya ditolak.');
        } catch (CodeIgniter\Database\Exceptions\DatabaseException $e) {
            $this->assertStringContainsString('foreign key', $e->getMessage());
        }

        $this->assertSame(0, $this->db->table('bukti_bayar')->countAllResults());
    }

    public function testModel(): void
    {
        $a     = $this->buatPendaftaranPanel();
        $b     = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $model = model(BuktiBayarModel::class);

        $this->assertSame(5, BuktiBayarModel::BATAS);
        $this->assertTrue($model->masihBolehTambah($a['id']));

        $id = $model->insert(['pendaftaran_id' => $a['id'], 'berkas' => 'bukti-bayar/' . str_repeat('ab', 16) . '.jpg', 'ukuran' => 1234], true);
        $baris = $model->find($id);
        $this->assertNotEmpty($baris['waktu_unggah'], 'Waktu unggah diisi otomatis.');
        $this->assertArrayNotHasKey('diubah_pada', $baris);

        $idLain = $this->buatBuktiBayarPanel($b['id'])[0];
        $this->buatBuktiBayarPanel($a['id'], 4);

        $this->assertSame(5, $model->jumlahMilik($a['id']));
        $this->assertFalse($model->masihBolehTambah($a['id']));
        $this->assertTrue($model->masihBolehTambah($b['id']));
        $this->assertCount(5, $model->milikPendaftaran($a['id']));
        $this->assertSame((int) $id, (int) $model->milikPendaftaran($a['id'])[0]['id']);

        // Bukti milik pendaftaran lain tidak terbuka lewat id pendaftaran ini.
        $this->assertNotNull($model->milik($a['id'], (int) $id));
        $this->assertNull($model->milik($a['id'], $idLain));
        $this->assertNotNull($model->milik($b['id'], $idLain));
    }

    public function testBerkasMilikDanHapusMilik(): void
    {
        $a = $this->buatPendaftaranPanel();
        $b = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel()]]);
        $this->buatBuktiBayarPanel($a['id'], 3);
        $this->buatBuktiBayarPanel($b['id'], 1);

        $berkas = new BerkasBuktiBayar();
        $milikA = $berkas->berkasMilik($a['id']);
        $milikB = $berkas->berkasMilik($b['id']);
        $this->assertCount(3, $milikA);
        $this->assertCount(1, $milikB);
        $this->assertCount(4, $this->berkasBuktiBayarTersimpan());

        $this->assertSame(3, $berkas->hapusMilik($a['id']));
        $this->assertSame(0, $this->db->table('bukti_bayar')->where('pendaftaran_id', $a['id'])->countAllResults());
        $this->assertSame($milikB, $this->berkasBuktiBayarTersimpan());

        // Tidak ada lagi yang dihapus.
        $this->assertSame(0, $berkas->hapusMilik($a['id']));
    }

    public function testPolaHapusPendaftaran(): void
    {
        // Pola F6-02: ambil daftar berkas, hapus pendaftaran, lalu hapus berkasnya.
        $a = $this->buatPendaftaranPanel();
        $this->buatBuktiBayarPanel($a['id'], 2);

        $berkas = new BerkasBuktiBayar();
        $daftar = $berkas->berkasMilik($a['id']);
        $this->db->table('pendaftaran')->where('id', $a['id'])->delete();

        $this->assertSame(2, $berkas->hapusBerkas($daftar));
        $this->assertSame([], $this->berkasBuktiBayarTersimpan());
    }
}
