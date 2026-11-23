<?php

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Hasil\Rekor;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Rekor kejuaraan (F11-06, K172 butir 3): rekor baru saat hasil Final
 * terbit, tanda CR, tarik terbitan mengembalikan rekor lama, waktu tangan
 * dan elektronik terpisah, spesifikasi alat, rekor isian admin, halaman
 * publik bersakelar, dan paket cadangan. Nama dan NISN karangan.
 *
 * @internal
 */
final class RekorKejuaraanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        setting('Acara.seri', 2);
    }

    protected function tearDown(): void
    {
        setting()->forget('Rekor.tampil');
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testHalamanAdminHanyaUntukAdmin(): void
    {
        foreach (['admin/rekor', 'admin/rekor/tambah'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach (['admin/rekor', 'admin/rekor/tampil', 'admin/rekor/1', 'admin/rekor/1/hapus'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->assertTokenCsrfWajib('admin/rekor/tampil');
    }

    public function testFinalTerbitMembuatRekorBaruDanTarikMengembalikanRekorLama(): void
    {
        [$atlet, $final, $id] = $this->finalLari(['9,87', '9,50', '']);
        $nomor = $this->idInduk['lari60putra'];

        // Belum terbit: belum ada rekor.
        $this->assertSame(0, $this->db->table('rekor')->countAllResults());

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');

        // Rekor pertama Seri 2: prestasi terbaik Final, peserta DNS tidak ikut.
        $rekor = $this->db->table('rekor')->get()->getResultArray();
        $this->assertCount(1, $rekor);
        $this->assertSame([(string) $id[$atlet[1]], '950', 'elektronik', 'Seri 2', 'Lari 60 m putra', 'KU 10'], [$rekor[0]['hasil_id'], $rekor[0]['prestasi'], $rekor[0]['cara_waktu'], $rekor[0]['seri'], $rekor[0]['nomor'], $rekor[0]['kelompok_umur']]);

        // Tanda CR di hasil publik, PDF/Excel memakai baris yang sama, dan videotron.
        $publik = $this->get('hasil/' . $nomor);
        $publik->assertOK();
        $this->assertSame(1, $this->xpathDari($publik)->query('//*[@data-rekor]')->length);
        $panel = (new PapanVideotron())->panelHasil((new HasilLomba())->nomor($nomor), ['id' => $final, 'nama' => 'Final']);
        $this->assertSame([true, false], array_column(array_slice($panel['baris'], 0, 2), 'rekor'));

        // Rekor isian admin yang lebih baik: hasil berikutnya tidak memecahkannya.
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/tarik');
        $this->assertSame(0, $this->db->table('rekor')->countAllResults(), 'Tarik kembali menghapus rekor dari hasil.');

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/rekor', [
            'nomor_lomba_id' => (string) $nomor, 'prestasi' => '9,40', 'atlet' => 'ATLET REKOR KARANGAN', 'sekolah' => 'SD KARANGAN REKOR', 'seri' => 'Seri 2', 'tanggal' => '2026-12-09', 'cara_waktu' => 'elektronik',
        ]), 'admin/rekor');
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');
        $this->assertSame(0, $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->countAllResults(), 'Prestasi 9,50 tidak memecahkan rekor 9,40.');
        $this->assertSame(0, $this->xpathDari($this->get('hasil/' . $nomor))->query('//*[@data-rekor]')->length);

        // Hasil terbit dikoreksi menjadi 9,30: rekor baru tanpa perlu terbit ulang.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => [$id[$atlet[1]] => ['prestasi' => '9,30']]]);
        $this->assertSame(['930'], array_column($this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->get()->getResultArray(), 'prestasi'));
        $berlaku = (new Rekor())->berlaku($nomor, 'waktu', null, 'elektronik');
        $this->assertSame(930, $berlaku['prestasi']);

        // Tarik kembali: rekor isian admin 9,40 berlaku lagi.
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/tarik');
        $this->assertSame(940, (new Rekor())->berlaku($nomor, 'waktu', null, 'elektronik')['prestasi']);
        $this->assertSame(1, $this->db->table('rekor')->countAllResults());
    }

    public function testDiskualifikasiDanPrestasiSamaTidakMembuatRekor(): void
    {
        $nomor = $this->idInduk['lari60putra'];
        [$atlet, $final, $id] = $this->finalLari(['9,10', '9,50', '9,60']);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => [$id[$atlet[0]] => ['prestasi' => '9,10', 'keterangan' => 'DQ', 'pasal' => 'TR16.8']]]);
        $this->rekorAdmin($nomor, '9,50');

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');

        // 9,10 berketerangan DQ diabaikan; 9,50 hanya menyamai rekor.
        $this->assertSame(0, $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->countAllResults());

        // Babak Final dihapus: tidak ada rekor yang tertinggal.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => [$id[$atlet[0]] => ['prestasi' => '9,10', 'keterangan' => '']]]);
        $this->assertSame(1, $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->countAllResults());
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final . '/hapus');
        $this->assertSame(0, $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->countAllResults());
    }

    public function testWaktuTanganDanSpesifikasiAlatTidakSalingMemecahkan(): void
    {
        $nomor = $this->idInduk['lari60putra'];
        $this->rekorAdmin($nomor, '9,00');
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['cara_waktu' => 'tangan']);
        $this->finalLari(['9,41', '9,80', '']);

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');

        // Rekor elektronik 9,00 tidak berlaku untuk waktu tangan: 9,41 menjadi 9,5 tangan.
        $tangan = (new Rekor())->berlaku($nomor, 'waktu', null, 'tangan');
        $this->assertSame(950, $tangan['prestasi']);
        $this->assertNotNull($tangan['hasil_id']);
        $this->assertSame(900, (new Rekor())->berlaku($nomor, 'waktu', null, 'elektronik')['prestasi']);

        // Spesifikasi alat diganti: kunci rekor baru, rekor lama tidak dibandingkan.
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['berat_alat' => 'Gawang 50 cm']);
        (new Rekor())->perbarui($nomor);
        $this->assertNotNull((new Rekor())->berlaku($nomor, 'waktu', '  gawang   50 CM ', 'tangan'));
        $this->assertSame('Gawang 50 cm', $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->get()->getRow()->berat_alat);

        // Halaman admin: rekor tangan dan elektronik tampil, keduanya berlaku.
        $admin = $this->bukaSebagaiAdmin('admin/rekor');
        $admin->assertOK();
        $admin->assertSee('9,5');
        $admin->assertSee('Waktu tangan');
        $this->assertSame(2, $this->xpathDari($admin)->query('//tr[@data-berlaku]')->length);
    }

    public function testRekorIsianAdminDanHalamanPublikBersakelar(): void
    {
        $nomor = $this->idInduk['frog'];
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['jenis_hasil' => 'jarak']);

        $this->bukaSebagaiAdmin('admin/rekor/tambah?nomor=' . $nomor)->assertOK();

        // Isian salah ditolak dengan pesan per kotak.
        $salah = $this->kirimSebagaiAdmin('admin/rekor', ['nomor_lomba_id' => (string) $nomor, 'prestasi' => '3 m', 'atlet' => '', 'sekolah' => 'SD KARANGAN', 'seri' => 'Seri 2', 'tanggal' => '2026-02-31']);
        $salah->assertOK();
        $salah->assertSee('Nama atlet belum diisi');
        $salah->assertSee('Tanggal belum benar');
        $this->assertSame(0, $this->db->table('rekor')->countAllResults());

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/rekor', [
            'nomor_lomba_id' => (string) $nomor, 'berat_alat' => '', 'prestasi' => '2,45', 'atlet' => 'ATLET LOMPAT KARANGAN', 'sekolah' => 'SD KARANGAN LOMPAT', 'seri' => 'Seri 2', 'tanggal' => '',
        ]), 'admin/rekor');
        $r = $this->db->table('rekor')->get()->getRowArray();
        $this->assertSame(['245', null, 'jarak', null], [$r['prestasi'], $r['cara_waktu'], $r['jenis_hasil'], $r['tanggal']]);

        $this->bukaSebagaiAdmin('admin/rekor/' . $r['id'] . '/ubah')->assertSee('2,45');
        $this->kirimSebagaiAdmin('admin/rekor/' . $r['id'], ['nomor_lomba_id' => (string) $nomor, 'prestasi' => '2,50', 'atlet' => 'ATLET LOMPAT KARANGAN', 'sekolah' => 'SD KARANGAN LOMPAT', 'seri' => 'Seri 2']);
        $this->assertSame('250', $this->db->table('rekor')->where('id', $r['id'])->get()->getRow()->prestasi);

        // Halaman publik mati secara bawaan.
        try {
            $this->get('rekor');
            $this->fail('Halaman Rekor seharusnya 404 selama sakelarnya mati.');
        } catch (PageNotFoundException) {
        }

        $this->kirimSebagaiAdmin('admin/rekor/tampil', ['tampil_rekor' => '1']);
        $this->assertTrue(Rekor::tampil());
        $publik = $this->get('rekor');
        $publik->assertOK();
        $publik->assertSee('2,50 m');
        $publik->assertSee('ATLET LOMPAT KARANGAN');
        $this->assertSame(1, $this->xpathDari($this->get('hasil'))->query('//*[@data-tautan-rekor]')->length);

        // Hapus.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/rekor/' . $r['id'] . '/hapus'), 'admin/rekor');
        $this->assertSame(0, $this->db->table('rekor')->countAllResults());
        $this->get('rekor')->assertSeeElement('#rekor-kosong');
    }

    public function testRekorDariHasilTidakBisaDiubahAdmin(): void
    {
        $nomor = $this->idInduk['lari60putra'];
        $this->finalLari(['9,87', '9,50', '']);
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');
        $id = (int) $this->db->table('rekor')->get()->getRow()->id;

        $this->bukaSebagaiAdmin('admin/rekor')->assertSee('Dari hasil');

        $this->expectException(PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/rekor/' . $id . '/ubah');
    }

    public function testPaketCadanganMembawaRekor(): void
    {
        $nomor = $this->idInduk['lari60putra'];
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['berat_alat' => 'Tanpa gawang']);
        $this->rekorAdmin($this->idInduk['frog'], '2,40');
        $this->finalLari(['9,87', '9,50', '']);
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan');
        $this->assertSame(2, $this->db->table('rekor')->countAllResults());

        $zip = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);

            foreach (['rekor', 'hasil', 'babak', 'atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $tabel) {
                $this->db->table($tabel)->where('id >', 0)->delete();
            }
            $this->db->table('nomor_lomba')->update(['hasil_terbit_pada' => null, 'berat_alat' => null]);

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $this->assertSame('Tanpa gawang', $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->berat_alat);
        $this->assertSame(['240' => null, '950' => 'Tanpa gawang'], array_column(array_map(
            static fn (array $r): array => ['prestasi' => (string) $r['prestasi'], 'alat' => $r['berat_alat'], 'dari_hasil' => $r['hasil_id'] !== null],
            $this->db->table('rekor')->orderBy('prestasi')->get()->getResultArray(),
        ), 'alat', 'prestasi'));
        $this->assertSame(1, $this->db->table('rekor')->where('hasil_id IS NOT NULL', null, false)->countAllResults(), 'Rekor dari hasil dihitung ulang setelah impor.');
    }

    /**
     * Final Lari 60 m putra dengan tiga atlet Lunas; prestasi kosong = DNS.
     *
     * @param list<string> $prestasi
     *
     * @return array{0: list<int>, 1: int, 2: array<int, int>} id atlet, id babak Final, id atlet => id hasil
     */
    private function finalLari(array $prestasi): array
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel(), $this->atletPanel()]]);
        (new StatusPembayaran())->tandaiLunas($p['id']);
        $nomor = $this->idInduk['lari60putra'];

        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'semua']);
        $final = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $id    = [];

        foreach ($this->db->table('hasil')->where('babak_id', $final)->get()->getResultArray() as $h) {
            $id[(int) $h['atlet_id']] = (int) $h['id'];
        }

        $isian = [];

        foreach ($p['atlet'] as $i => $a) {
            $isian[$id[$a]] = $prestasi[$i] === '' ? ['prestasi' => '', 'keterangan' => 'DNS'] : ['prestasi' => $prestasi[$i], 'keterangan' => ''];
        }

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => $isian]);

        return [$p['atlet'], $final, $id];
    }

    private function rekorAdmin(int $nomor, string $prestasi): void
    {
        $this->assertSame([], (new Rekor())->simpan(null, Rekor::rapikanIsian([
            'nomor_lomba_id' => (string) $nomor, 'prestasi' => $prestasi, 'atlet' => 'ATLET LAMA KARANGAN', 'sekolah' => 'SD KARANGAN LAMA', 'seri' => 'Seri 2',
        ])));
    }
}
