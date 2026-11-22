<?php

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Status nomor "Tidak dilombakan" dan batas peserta minimal (F11-03, K172
 * butir 4): nomor yang tidak dilombakan keluar dari jadwal, seri, klasemen,
 * dan videotron dengan keterangan di halaman hasil; batas peserta hanya
 * memberi peringatan di Hasil dan Seri, medali tidak dikurangi.
 *
 * @internal
 */
final class HasilLombaStatusNomorTest extends UjiBasisData
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

    public function testBawaanDilombakanTanpaBatas(): void
    {
        $nomor = (new HasilLomba())->nomor($this->idInduk['lari60putra']);

        $this->assertNotNull($nomor);
        $this->assertTrue($nomor['dilombakan']);
        $this->assertSame('', $nomor['keterangan']);
        $this->assertNull($nomor['peserta_minimal']);
    }

    public function testNomorTidakDilombakanKeluarDariJadwalSeriKlasemenDanVideotron(): void
    {
        [$nomor, $final] = $this->finalTerbit();
        $hasil           = new HasilLomba();
        setting('Videotron.tampil', true);
        setting('HalamanDepan.tampil_seri', true);
        $this->db->table('babak')->where('id', $final)->update(['tanggal' => date('Y-m-d', strtotime('+1 day')), 'jam_mulai' => '08:00:00', 'seri_dikunci_pada' => date('Y-m-d H:i:s')]);

        // Sebelum ditandai: tampil di semua tempat.
        $this->assertNotSame([], $hasil->juara(true));
        $this->assertContains($final, array_column((new JadwalPertandingan())->semua(), 'id'));
        $this->assertContains($nomor, array_column((new PapanVideotron())->data()['hasil'], 'id'));
        $this->get('jadwal/' . $final)->assertOK();

        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Peserta kurang dari 3']);

        $this->assertSame([], $hasil->juara(true));
        $this->assertSame([], $hasil->juara(false));
        $this->assertSame([], $hasil->klasemen(true));
        $this->assertNotContains($final, array_column((new JadwalPertandingan())->semua(), 'id'));
        $data = (new PapanVideotron())->data();
        $this->assertNotContains($nomor, array_column($data['hasil'], 'id'));
        $this->assertSame([], $data['juara_umum']);
        $this->assertSame([], $data['jadwal']);

        try {
            $this->get('jadwal/' . $final);
            $this->fail('Daftar seri nomor yang tidak dilombakan seharusnya tidak ditemukan.');
        } catch (PageNotFoundException) {
        }

        // Halaman hasil publik menulis keterangannya, tanpa juara dan unduhan.
        $publik = $this->get('hasil');
        $publik->assertOK();
        $publik->assertSee('Tidak dilombakan. Peserta kurang dari 3.');
        $publik->assertDontSee('Juara I');

        $rinci = $this->get('hasil/' . $nomor);
        $rinci->assertOK();
        $this->assertAda($rinci, '//*[@data-tidak-dilombakan]');
        $rinci->assertSee('Tidak dilombakan. Peserta kurang dari 3.');
        $rinci->assertDontSee('Unduh PDF');

        // Admin: tanda di daftar Hasil dan Seri, keterangan di halaman nomor.
        $this->bukaSebagaiAdmin('admin/hasil')->assertSee('Tidak dilombakan');
        $halaman = $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor);
        $this->assertAda($halaman, '//*[@data-status-nomor="tidak-dilombakan"]');
        $halaman->assertSee('Nomor ini tidak dilombakan');
        $seri = $this->bukaSebagaiAdmin('admin/seri');
        $this->assertAda($seri, '//*[@data-nomor-seri="' . $nomor . '"]//*[@data-tidak-dilombakan]');
        $this->assertAda($seri, '//*[@data-babak-seri="' . $final . '"]', false);
        $this->assertAda($this->bukaSebagaiAdmin('admin/seri/babak/' . $final), '//*[@data-status-nomor="tidak-dilombakan"]');
    }

    public function testNomorTidakDilombakanTampilDiHasilPublikWalauBelumTerbit(): void
    {
        $nomor = $this->idInduk['lari60putri'];
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Tidak ada peserta.']);

        $this->assertFalse((new HasilLomba())->adaTerbit(), 'Tautan Hasil tetap menunggu nomor yang terbit.');
        $this->get('hasil')->assertSee('Tidak dilombakan. Tidak ada peserta.');
        $this->get('hasil/' . $nomor)->assertOK();

        // Nomor lain yang belum terbit tetap tidak ditemukan.
        $this->expectException(PageNotFoundException::class);
        $this->get('hasil/' . $this->idInduk['lari60putra']);
    }

    public function testBatasPesertaMemberiPeringatanTanpaMengurangiMedali(): void
    {
        [$nomor, $final] = $this->finalTerbit();
        $juara           = (new HasilLomba())->juara(true);

        // Tanpa batas: tanpa peringatan.
        $this->assertAda($this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor), '//*[@data-status-nomor]', false);

        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['peserta_minimal' => 4]);

        $daftar = $this->bukaSebagaiAdmin('admin/hasil');
        $daftar->assertSee('Peserta kurang dari batas');
        $halaman = $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor);
        $this->assertAda($halaman, '//*[@data-status-nomor="peserta-kurang"]');
        $halaman->assertSee('Peserta Lunas baru 3 atlet, kurang dari batas 4 atlet.');
        $this->assertAda($this->bukaSebagaiAdmin('admin/seri'), '//*[@data-nomor-seri="' . $nomor . '"]//*[@data-peserta-kurang]');
        $this->bukaSebagaiAdmin('admin/seri/nomor/' . $nomor)->assertSee('Peserta Lunas baru 3 atlet');
        $this->bukaSebagaiAdmin('admin/seri/babak/' . $final)->assertSee('Peserta Lunas baru 3 atlet');

        // Medali tetap: juara dan klasemen tidak berubah.
        $this->assertSame($juara, (new HasilLomba())->juara(true));

        // Peserta Lunas cukup; setelah daftar ulang dipakai, yang hadir dihitung.
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['peserta_minimal' => 3]);
        $this->assertAda($this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor), '//*[@data-status-nomor]', false);

        $atlet = (int) $this->db->table('hasil')->where('babak_id', $final)->get()->getRow()->atlet_id;
        $this->db->table('atlet')->where('id', $atlet)->update(['hadir_pada' => date('Y-m-d H:i:s')]);
        $this->assertTrue((new SeriLintasan())->daftarUlangDipakai());
        $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor)->assertSee('Peserta hadir baru 1 atlet, kurang dari batas 3 atlet.');
    }

    public function testFormulirNomorLombaMenyimpanStatusDanBatas(): void
    {
        $nomor  = $this->idInduk['frog'];
        $baris  = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $alamat = 'admin/pengaturan/nomor-lomba/' . $nomor;
        $isian  = [
            'kelompok_umur_id' => (string) $baris['kelompok_umur_id'],
            'nama'             => (string) $baris['nama'],
            'jenis'            => (string) $baris['jenis'],
            'kategori'         => (string) $baris['kategori'],
            'jenis_hasil'      => (string) $baris['jenis_hasil'],
            'urutan'           => (string) $baris['urutan'],
            'aktif'            => '1',
        ];

        $ubah = $this->bukaSebagaiAdmin($alamat . '/ubah');
        $ubah->assertSeeElement('#bagian-pelaksanaan #peserta_minimal');
        $ubah->assertSeeElement('#bagian-pelaksanaan #tidak_dilombakan');

        // Tidak dilombakan tanpa alasan dan batas bukan angka: ditolak.
        $tolak = $this->kirimSebagaiAdmin($alamat, $isian + ['tidak_dilombakan' => '1', 'alasan_tidak_dilombakan' => ' ', 'peserta_minimal' => 'tiga']);
        $tolak->assertOK();
        $tolak->assertSee('Alasan belum diisi');
        $tolak->assertSee('Batas peserta harus angka 1 sampai 99');
        $this->assertSame('0', (string) $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->tidak_dilombakan);

        $this->assertTrue($this->kirimSebagaiAdmin($alamat, $isian + ['tidak_dilombakan' => '1', 'alasan_tidak_dilombakan' => ' Peserta  kurang dari 3 ', 'peserta_minimal' => '3'])->isRedirect());
        $simpan = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $this->assertSame(['1', 'Peserta kurang dari 3', '3'], [(string) $simpan['tidak_dilombakan'], $simpan['alasan_tidak_dilombakan'], (string) $simpan['peserta_minimal']]);
        $this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba')->assertSee('Tidak dilombakan');

        // Dilombakan lagi: alasan dibuang, batas kosong = tanpa batas.
        $this->kirimSebagaiAdmin($alamat, $isian + ['tidak_dilombakan' => '0', 'alasan_tidak_dilombakan' => 'Peserta kurang dari 3', 'peserta_minimal' => '']);
        $simpan = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $this->assertSame(['0', null, null], [(string) $simpan['tidak_dilombakan'], $simpan['alasan_tidak_dilombakan'], $simpan['peserta_minimal']]);
    }

    public function testPaketPindahServerMembawaStatusDanBatas(): void
    {
        $nomor = $this->idInduk['lari60putri'];
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['tidak_dilombakan' => 1, 'alasan_tidak_dilombakan' => 'Peserta kurang dari 3', 'peserta_minimal' => 3]);
        $zip = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $this->db->table('nomor_lomba')->update(['tidak_dilombakan' => 0, 'alasan_tidak_dilombakan' => null, 'peserta_minimal' => null]);
            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $baris = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();
        $this->assertSame(['1', 'Peserta kurang dari 3', '3'], [(string) $baris['tidak_dilombakan'], $baris['alasan_tidak_dilombakan'], (string) $baris['peserta_minimal']]);
    }

    private function assertAda(TestResponse $halaman, string $xpath, bool $ada = true): void
    {
        $this->assertSame($ada, $this->xpathDari($halaman)->query($xpath)->length > 0, $xpath);
    }

    /**
     * Tiga atlet Lunas di Lari 60 m putra dengan Final berprestasi dan terbit.
     *
     * @return array{0: int, 1: int} id nomor, id babak Final
     */
    private function finalTerbit(): array
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel(), $this->atletPanel()]]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $hasil = new HasilLomba();
        $nomor = $this->idInduk['lari60putra'];
        $final = $hasil->tambahBabak($nomor, 'Final', true, 'semua')['id'];
        $isian = [];

        foreach ($this->db->table('hasil')->where('babak_id', $final)->orderBy('atlet_id')->get()->getResultArray() as $i => $h) {
            $isian[(int) $h['id']] = ['prestasi' => ['9,40', '9,80', '10,10'][$i], 'keterangan' => '', 'peringkat' => ''];
        }

        $this->assertSame([], $hasil->simpanHasil($final, $isian));
        $hasil->terbitkan($nomor, true);

        return [$nomor, $final];
    }
}
