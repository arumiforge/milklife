<?php

use App\Libraries\AtletDitolak;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\HapusPendaftaran;
use App\Libraries\Panel\TolakAtlet;
use App\Models\AtletModel;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Tolak atlet dari pendaftaran (`App\Libraries\Panel\TolakAtlet`) dan catatan
 * atlet ditolak untuk Cek Pendaftaran (`App\Libraries\AtletDitolak`), K116.
 *
 * Data induk dari `PanelDataContoh`: KU 10 lahir 2016–2018 Rp 150.000 per
 * nomor individu, KU 12 lahir 2015 Rp 175.000, keduanya SD/MI. Atlet "salah
 * kelompok umur" dibuat langsung tersimpan di KU 12 dengan tahun lahir 2016,
 * seperti kejadian pengaturan yang sempat keliru.
 *
 * @internal
 */
final class PanelTolakAtletTest extends UjiBasisData
{
    use PanelDataContoh;

    private const ALASAN = 'Tahun lahir tidak sesuai kelompok umur. Daftarkan ulang di KU 10.';

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

    // ---------------------------------------------------------------
    // Formulir dan kecocokan
    // ---------------------------------------------------------------

    public function testFormulirMenandaiKelompokUmurYangTidakCocok(): void
    {
        $p    = $this->pendaftaranDenganAtletSalah();
        $form = (new TolakAtlet())->formulir($p['id']);

        $this->assertSame('menunggu', $form['pendaftaran']['status']);
        $this->assertSame([true, false], array_column($form['atlet'], 'cocok'));

        $salah = $form['atlet'][1];
        $this->assertSame('KU 12', $salah['kelompok_umur']);
        $this->assertSame($this->idInduk['ku10'], $salah['sesuai_id']);
        $this->assertSame('KU 10 (lahir 2016–2018)', $salah['sesuai']);
        $this->assertSame(175000, $salah['biaya_individu']);
        $this->assertSame('Lari 60 m putra', $salah['nomor_lomba'][0]['nama'] ?? null);
    }

    public function testKelompokUmurDimatikanTetapCocokBilaTahunLahirnyaSesuai(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()]]);
        $this->db->table('kelompok_umur')->where('id', $this->idInduk['ku10'])->update(['aktif' => 0]);

        $this->assertTrue((new TolakAtlet())->formulir($p['id'])['atlet'][0]['cocok']);
    }

    public function testKecocokanTanpaKelompokUmurYangCocok(): void
    {
        $ku = [['id' => 2, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'jenjang' => ['A']]];

        $hasil = TolakAtlet::kecocokan('A', '2010-01-01', 2, $ku);

        $this->assertFalse($hasil['cocok']);
        $this->assertNull($hasil['sesuai_id']);
        $this->assertStringContainsString('tidak masuk kelompok umur mana pun', $hasil['sesuai']);
    }

    public function testKecocokanKelompokUmurPilihan(): void
    {
        // K134: lahir 2016 boleh KU 10 atau KU 12 (SD/MI).
        $ku = [
            ['id' => 2, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'jenjang' => ['A']],
            ['id' => 3, 'nama' => 'KU 12', 'tahun_lahir_awal' => 2014, 'tahun_lahir_akhir' => 2016, 'jenjang' => ['A']],
            ['id' => 4, 'nama' => 'KU 15', 'tahun_lahir_awal' => 2011, 'tahun_lahir_akhir' => 2014, 'jenjang' => ['B']],
        ];

        // Pilihan mana pun di antara keduanya cocok.
        $this->assertTrue(TolakAtlet::kecocokan('A', '2016-03-01', 2, $ku)['cocok']);
        $this->assertTrue(TolakAtlet::kecocokan('A', '2016-03-01', 3, $ku)['cocok']);

        // Tersimpan di kelompok umur lain: seharusnya salah satu pilihan.
        $hasil = TolakAtlet::kecocokan('A', '2016-03-01', 4, $ku);
        $this->assertFalse($hasil['cocok']);
        $this->assertSame(2, $hasil['sesuai_id']);
        $this->assertSame('KU 10 (lahir 2016–2018) atau KU 12 (lahir 2014–2016)', $hasil['sesuai']);
    }

    // ---------------------------------------------------------------
    // Tolak sebagian
    // ---------------------------------------------------------------

    public function testTolakSebagianMengeluarkanAtletDanMencatatnya(): void
    {
        $p     = $this->pendaftaranDenganAtletSalah();
        $salah = $p['atlet'][1];
        $nisn  = $this->nisn($salah);

        $hasil = (new TolakAtlet())->tolak($p['id'], [(string) $salah], self::ALASAN, 'Admin Uji');

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['tersimpan']);
        $this->assertFalse($hasil['semua']);
        $this->assertSame(1, $hasil['jumlah']);
        $this->assertSame(150000 + 175000, $hasil['total_lama']);
        $this->assertSame(150000, $hasil['total']);

        // Atlet keluar, yang lain tetap; total dihitung ulang.
        $this->assertSame([$p['atlet'][0]], $this->idAtlet($p['id']));
        $this->assertSame(0, $this->db->table('atlet_nomor')->where('atlet_id', $salah)->countAllResults());
        $baris = $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRowArray();
        $this->assertSame(150000, (int) $baris['total']);
        $this->assertSame('menunggu', $baris['status']);

        // NISN-nya bisa didaftarkan lagi.
        $this->assertSame([], model(AtletModel::class)->nisnTerdaftar([$nisn]));

        // Salinan untuk Cek Pendaftaran.
        $catat = $this->db->table('atlet_ditolak')->where('pendaftaran_id', $p['id'])->get()->getResultArray();
        $this->assertCount(1, $catat);
        $this->assertSame($nisn, $catat[0]['nisn']);
        $this->assertSame('KU 12', $catat[0]['kelompok_umur']);
        $this->assertSame('KU 10 (lahir 2016–2018)', $catat[0]['kelompok_umur_sesuai']);
        $this->assertSame($this->idInduk['ku10'], (int) $catat[0]['kelompok_umur_sesuai_id']);
        $this->assertSame(175000, (int) $catat[0]['biaya_individu']);
        $this->assertSame(325000, (int) $catat[0]['total_sebelum']);
        $this->assertSame(150000, (int) $catat[0]['total_sesudah']);
        $this->assertSame(self::ALASAN, $catat[0]['alasan']);
        $this->assertSame('Admin Uji', $catat[0]['ditolak_oleh']);
    }

    public function testAtletTersisaYangJugaTidakCocokMembatalkanPenolakan(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletSalah(), $this->atletSalah()]]);

        $hasil = (new TolakAtlet())->tolak($p['id'], [$p['atlet'][0]], self::ALASAN, 'Admin');

        $this->assertFalse($hasil['tersimpan']);
        $this->assertStringContainsString('data atlet lain di pendaftaran ini perlu dibetulkan', $hasil['galat'][0]);
        $this->assertStringContainsString('untuk KU 12, sedangkan atlet ini KU 10', implode(' ', $hasil['galat']));
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
        $this->assertSame(0, $this->db->table('atlet_ditolak')->countAllResults());

        // Keduanya sekaligus: semua atlet ditolak.
        $this->assertTrue((new TolakAtlet())->tolak($p['id'], $p['atlet'], self::ALASAN, 'Admin')['tersimpan']);
    }

    public function testAnggotaReguYangDitolakMenghitungUlangBiayaRegu(): void
    {
        // Satu regu Kanga's Escape (Rp 100.000) dari 4 atlet, plus satu atlet individu KU 10.
        $p    = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [...$this->timPanel('kanga', 4), $this->atletPanel()]]);
        $regu = array_slice($p['atlet'], 0, 4);

        // Satu anggota: regu tetap (inti boleh kurang saat diubah admin), biaya regu tetap.
        $hasil = (new TolakAtlet())->tolak($p['id'], [$regu[0]], self::ALASAN, 'Admin');
        $this->assertTrue($hasil['tersimpan'], implode(' ', $hasil['galat']));
        $this->assertSame(100000 + 150000, $hasil['total']);
        $this->assertSame(1, $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $p['id'])->countAllResults());

        // Semua anggota regu: regunya hilang dari pendaftaran beserta biayanya.
        $hasil = (new TolakAtlet())->tolak($p['id'], array_slice($regu, 1), self::ALASAN, 'Admin');
        $this->assertTrue($hasil['tersimpan'], implode(' ', $hasil['galat']));
        $this->assertSame(150000, $hasil['total']);
        $this->assertSame(0, $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $p['id'])->countAllResults());
        $this->assertSame(4, $this->db->table('atlet_ditolak')->where('pendaftaran_id', $p['id'])->countAllResults());
    }

    // ---------------------------------------------------------------
    // Tolak semua
    // ---------------------------------------------------------------

    public function testTolakSemuaMenyisakanPendaftaranTanpaAtlet(): void
    {
        $this->pakaiFolderBuktiBayarSementara();
        $p = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [...$this->timPanel('kanga', 4), $this->atletSalah()]]);
        $this->buatBuktiBayarPanel($p['id']);

        $hasil = (new TolakAtlet())->tolak($p['id'], $p['atlet'], self::ALASAN, 'Admin');

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['semua']);
        $this->assertSame(5, $hasil['jumlah']);
        $this->assertSame(0, $hasil['total']);

        $baris = $this->db->table('pendaftaran')->where('id', $p['id'])->get()->getRowArray();
        $this->assertSame([0, 0, 0], [(int) $baris['subtotal_individu'], (int) $baris['subtotal_beregu'], (int) $baris['total']]);
        $this->assertSame([], $this->idAtlet($p['id']));
        $this->assertSame(0, $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $p['id'])->countAllResults());
        // Ofisial dan bukti bayar tetap, untuk urusan pengembalian uang.
        $this->assertSame(1, $this->db->table('penanggung_jawab')->where('pendaftaran_id', $p['id'])->countAllResults());
        $this->assertSame(1, $this->db->table('bukti_bayar')->where('pendaftaran_id', $p['id'])->countAllResults());

        $catat = $this->db->table('atlet_ditolak')->where('pendaftaran_id', $p['id'])->get()->getResultArray();
        $this->assertCount(5, $catat);
        $this->assertSame([0], array_values(array_unique(array_map('intval', array_column($catat, 'total_sesudah')))));
        $this->assertSame([$p['total']], array_values(array_unique(array_map('intval', array_column($catat, 'total_sebelum')))));
    }

    // ---------------------------------------------------------------
    // Penolakan yang tidak diterima
    // ---------------------------------------------------------------

    public function testPendaftaranLunasTidakBisaDitolak(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => [$this->atletPanel(['nomor_dada' => '2001'])]]);

        $hasil = (new TolakAtlet())->tolak($p['id'], $p['atlet'], self::ALASAN, 'Admin');

        $this->assertFalse($hasil['tersimpan']);
        $this->assertStringContainsString('sudah Lunas', $hasil['galat'][0]);
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
    }

    public function testPilihanDanAlasanDiperiksa(): void
    {
        $p    = $this->pendaftaranDenganAtletSalah();
        $lain = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);
        $tolak = new TolakAtlet();

        $this->assertStringContainsString('Belum ada atlet yang dipilih', implode(' ', $tolak->tolak($p['id'], [], self::ALASAN, 'Admin')['galat']));
        $this->assertStringContainsString('sudah tidak ada di pendaftaran ini', implode(' ', $tolak->tolak($p['id'], [$lain['atlet'][0]], self::ALASAN, 'Admin')['galat']));
        $this->assertStringContainsString('terlalu pendek', implode(' ', $tolak->tolak($p['id'], [$p['atlet'][1]], '  pendek ', 'Admin')['galat']));
        $this->assertStringContainsString('terlalu panjang', implode(' ', $tolak->tolak($p['id'], [$p['atlet'][1]], str_repeat('a', 1001), 'Admin')['galat']));
        $this->assertNull($tolak->tolak(999999, [1], self::ALASAN, 'Admin'));

        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
        $this->assertSame(0, $this->db->table('atlet_ditolak')->countAllResults());
    }

    // ---------------------------------------------------------------
    // Catatan untuk Cek Pendaftaran
    // ---------------------------------------------------------------

    public function testCatatanTampilSampaiNisnDidaftarkanLagi(): void
    {
        $p     = $this->pendaftaranDenganAtletSalah();
        $salah = $p['atlet'][1];
        $nisn  = $this->nisn($salah);
        (new TolakAtlet())->tolak($p['id'], [$salah], self::ALASAN, 'Admin');

        $cek = (new DataPendaftaran())->cariId($p['id_pendaftaran']);
        $this->assertCount(1, $cek['atlet']);
        $this->assertCount(1, $cek['atlet_ditolak']);

        $d = $cek['atlet_ditolak'][0];
        $this->assertSame($nisn, $d['nisn']);
        $this->assertSame(2016, $d['tahun_lahir']);
        $this->assertTrue($d['sesuai_beda']);
        $this->assertSame('KU 10 (lahir 2016–2018)', $d['kelompok_umur_sesuai']);
        $this->assertNull($d['didaftarkan_ulang']);
        // Nomor KU 10 untuk putra beserta biayanya sekarang; nomor putri tidak.
        $nomor = array_column($d['nomor_sesuai'], 'biaya', 'nama');
        $this->assertSame(150000, $nomor['Lari 60 m putra'] ?? null);
        $this->assertSame(100000, $nomor['Kanga\'s Escape putra'] ?? null);
        $this->assertArrayNotHasKey('Lari 60 m putri', $nomor);

        // Didaftarkan lagi di pendaftaran baru: catatannya hilang dari cek, admin tetap melihatnya.
        $baru = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(['nisn' => $nisn, 'tanggal_lahir' => '2016-05-01'])]]);

        $this->assertSame([], (new DataPendaftaran())->cariId($p['id_pendaftaran'])['atlet_ditolak']);
        $admin = (new AtletDitolak())->untukPendaftaran([$p['id']], semua: true)[$p['id']];
        $this->assertSame($baru['id_pendaftaran'], $admin[0]['didaftarkan_ulang']);
    }

    public function testHapusPendaftaranIkutMenghapusCatatan(): void
    {
        $p = $this->pendaftaranDenganAtletSalah();
        (new TolakAtlet())->tolak($p['id'], [$p['atlet'][1]], self::ALASAN, 'Admin');

        (new HapusPendaftaran())->hapus($p['id']);

        $this->assertSame(0, $this->db->table('atlet_ditolak')->countAllResults());
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    /**
     * Atlet tersimpan di KU 12 padahal lahir 2016 (seharusnya KU 10).
     *
     * @return array<string, mixed>
     */
    private function atletSalah(): array
    {
        return $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2016-05-01', 'nomor' => ['lari60putraKu12']]);
    }

    /**
     * Satu atlet KU 10 yang benar dan satu atlet salah kelompok umur.
     *
     * @return array<string, mixed>
     */
    private function pendaftaranDenganAtletSalah(): array
    {
        return $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(), $this->atletSalah()]]);
    }

    private function nisn(int $atletId): string
    {
        return (string) $this->db->table('atlet')->where('id', $atletId)->get()->getRow()->nisn;
    }

    /**
     * @return list<int>
     */
    private function idAtlet(int $pendaftaranId): array
    {
        return array_map('intval', array_column($this->db->table('atlet')->select('id')->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->get()->getResultArray(), 'id'));
    }
}
