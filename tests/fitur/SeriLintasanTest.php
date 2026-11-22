<?php

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Panel\PaketPendaftaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Seri, lintasan, dan alur babak (F9-08, K140 butir 8): aturan seri per
 * nomor, bagi otomatis (Lunas, hadir, sekolah dipisah, benih), geser, kunci,
 * aturan lolos Q/q, babak berikut terisi otomatis dari hasil, unduhan
 * PDF/Excel per nomor dan per hari, halaman publik bersakelar bawaan mati,
 * dan paket pindah server.
 *
 * @internal
 */
final class SeriLintasanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private const HARI = '2099-12-09';

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

    // ---------- Sakelar dan hak akses ----------

    public function testSakelarBawaanMatiDanHalamanAdminHanyaUntukAdmin(): void
    {
        $this->assertFalse(SeriLintasan::tampil());
        $babak = $this->penyisihanDanFinal(10);
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan']);
        $this->assertNull((new SeriLintasan())->kunci($babak['penyisihan'], true));

        try {
            $this->get('jadwal/' . $babak['penyisihan']);
            $this->fail('Daftar seri publik seharusnya 404 selama sakelar mati.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }

        foreach (['admin/seri', 'admin/seri/nomor/' . $this->idInduk['lari60putra'], 'admin/seri/babak/' . $babak['penyisihan'], 'admin/seri/nomor/' . $this->idInduk['lari60putra'] . '/pdf', 'admin/seri/hari/' . self::HARI . '/excel'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach (['admin/seri/tampil', 'admin/seri/nomor/1', 'admin/seri/babak/1/bagi', 'admin/seri/babak/1/geser', 'admin/seri/babak/1/lolos', 'admin/seri/babak/1/kunci', 'admin/seri/babak/1/buka'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->assertTokenCsrfWajib('admin/seri/babak/' . $babak['penyisihan'] . '/bagi');
        $this->assertTokenCsrfWajib('admin/seri/tampil', ['tampil_seri' => '1']);

        // Sakelar dari halaman Seri dan lintasan (komponen mengirim 0 lalu 1).
        $halaman = $this->bukaSebagaiAdmin('admin/seri');
        $halaman->assertOK();
        $this->assertSame(1, $this->xpathDari($halaman)->query('//section[@id="sakelar-seri"]//input[@type="checkbox"][@name="tampil_seri"][not(@checked)]')->length);

        $hasil = $this->kirimSebagaiAdmin('admin/seri/tampil', ['tampil_seri' => '1']);
        $hasil->assertRedirectTo(site_url('admin/seri') . '#sakelar-seri');
        $this->assertTrue(SeriLintasan::tampil());
        $this->get('jadwal/' . $babak['penyisihan'])->assertOK();

        $this->kirimSebagaiAdmin('admin/seri/tampil', ['tampil_seri' => '0']);
        $this->assertFalse(SeriLintasan::tampil());
    }

    // ---------- Aturan nomor ----------

    public function testSimpanAturanNomorDanGalat(): void
    {
        $id = $this->idInduk['lari60putra'];

        $halaman = $this->bukaSebagaiAdmin('admin/seri/nomor/' . $id);
        $halaman->assertOK();
        $this->assertStringContainsString('3,4,5,6;7,8;1,2', $halaman->getBody());

        $galat = $this->kirimSebagaiAdmin('admin/seri/nomor/' . $id, ['tempat_per_seri' => '6', 'urutan_lintasan' => '3,4,5,6;7,8;1,2']);
        $this->assertGagalDialihkan($galat, 'admin/seri/nomor/' . $id . '#aturan-seri');
        $this->assertNull($this->db->table('nomor_lomba')->where('id', $id)->get()->getRow()->tempat_per_seri);

        $galat = $this->kirimSebagaiAdmin('admin/seri/nomor/' . $id, ['tempat_per_seri' => '13', 'urutan_lintasan' => '']);
        $this->assertGagalDialihkan($galat, 'admin/seri/nomor/' . $id . '#aturan-seri');

        $simpan = $this->kirimSebagaiAdmin('admin/seri/nomor/' . $id, ['tempat_per_seri' => '6', 'urutan_lintasan' => ' 3, 4 ; 2,5 ; 1,6 ']);
        $this->assertBerhasilDialihkan($simpan, 'admin/seri/nomor/' . $id . '#aturan-seri');
        $n = $this->db->table('nomor_lomba')->where('id', $id)->get()->getRow();
        $this->assertSame(['6', '3,4;2,5;1,6'], [$n->tempat_per_seri, $n->urutan_lintasan]);

        $aturan = (new SeriLintasan())->aturanNomor((new HasilLomba())->nomor($id));
        $this->assertSame([true, 6, '3,4;2,5;1,6'], [$aturan['lintasan'], $aturan['tempat'], $aturan['urutan']]);

        // Kosong = bawaan lagi.
        $this->kirimSebagaiAdmin('admin/seri/nomor/' . $id, ['tempat_per_seri' => '', 'urutan_lintasan' => '']);
        $aturan = (new SeriLintasan())->aturanNomor((new HasilLomba())->nomor($id));
        $this->assertSame([8, '3,4,5,6;7,8;1,2'], [$aturan['tempat'], $aturan['urutan']]);
    }

    // ---------- Bagi otomatis ----------

    public function testBagiOtomatisLunasRataSekolahDipisahDanBenihBisaDiulang(): void
    {
        $babak = $this->penyisihanDanFinal(0);
        $this->sekolahDenganAtlet(['10000001' => 4, '10000002' => 4, '10000003' => 3, '10000004' => 3, '10000005' => 3, '10000006' => 3]);
        // Belum Lunas: tidak ikut.
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '10000009', 'nama' => 'SD KARANGAN MENUNGGU'], 'atlet' => [$this->atletPanel()]]);

        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan']);
        $halaman->assertOK();

        $bagi = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/bagi', ['jumlah_seri' => '', 'benih' => '777']);
        $this->assertBerhasilDialihkan($bagi, 'admin/seri/babak/' . $babak['penyisihan'] . '#daftar-seri', '20 peserta dibagi ke 3 seri, diacak, sekolah sama dipisah. Periksa dan geser bila perlu, lalu kunci daftarnya.');

        $baris = $this->barisHasil($babak['penyisihan']);
        $this->assertCount(20, $baris);
        $this->assertSame([1 => 7, 2 => 7, 3 => 6], $this->hitungPerSeri($baris));

        foreach ($baris as $b) {
            $this->assertGreaterThanOrEqual(1, $b['lintasan']);
            $this->assertLessThanOrEqual(8, $b['lintasan']);
        }

        $this->assertSame(20, count(array_unique(array_map(static fn (array $b): string => $b['seri'] . '-' . $b['lintasan'], $baris))), 'Seri dan lintasan unik.');

        // Sekolah dengan 3 atlet: satu per seri; dengan 4 atlet: paling banyak 2 per seri.
        foreach ($this->sekolahPerSeri($baris) as $npsn => $perSeri) {
            $this->assertLessThanOrEqual(in_array((string) $npsn, ['10000001', '10000002'], true) ? 2 : 1, max($perSeri), 'Sekolah ' . $npsn);
        }

        $b = $this->db->table('babak')->where('id', $babak['penyisihan'])->get()->getRow();
        $this->assertSame(['3', '777'], [$b->jumlah_seri, $b->benih]);

        // Benih sama = pembagian sama.
        $sebelum = $baris;
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan'], ['benih' => 777]);
        $this->assertSame($sebelum, $this->barisHasil($babak['penyisihan']));

        // Banyak seri diminta admin, dan yang tidak muat ditolak.
        $this->assertSame(4, (new SeriLintasan())->bagiOtomatis($babak['penyisihan'], ['jumlah_seri' => 4])['seri']);
        $this->assertStringContainsString('harus 3 sampai 20', (string) ((new SeriLintasan())->bagiOtomatis($babak['penyisihan'], ['jumlah_seri' => 2])['galat'] ?? ''));
    }

    public function testHanyaYangHadirBilaDaftarUlangDipakai(): void
    {
        $babak = $this->penyisihanDanFinal(0);
        $atlet = $this->sekolahDenganAtlet(['10000001' => 3, '10000002' => 3]);
        $this->db->table('atlet')->whereIn('id', array_slice($atlet, 0, 4))->update(['hadir_pada' => date('Y-m-d H:i:s'), 'hadir_oleh' => 'Admin Uji']);

        $this->assertTrue((new SeriLintasan())->daftarUlangDipakai());
        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan']);
        $this->assertSame(1, $this->xpathDari($halaman)->query('//input[@name="hanya_hadir"][@checked]')->length);

        $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/bagi', ['hanya_hadir' => '1']);
        $this->assertCount(4, $this->barisHasil($babak['penyisihan']));

        $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/bagi', []);
        $this->assertCount(6, $this->barisHasil($babak['penyisihan']));
    }

    public function testNomorTanpaLintasanMemakaiUrutanTampil(): void
    {
        $frog = $this->idInduk['frog'];
        $this->db->table('nomor_lomba')->where('id', $frog)->update(['jenis_hasil' => JenisHasil::JARAK]);
        $p = $this->buatPendaftaranPanel(['status' => 'lunas', 'atlet' => array_map(fn (): array => $this->atletPanel(['nomor' => ['frog']]), range(1, 11))]);
        $this->assertCount(11, $p['atlet']);
        $babak = (new HasilLomba())->tambahBabak($frog, 'Final', true, 'kosong')['id'];

        $this->assertSame(1, (new SeriLintasan())->bagiOtomatis($babak)['seri'], 'Bawaan: satu kelompok.');
        $urutan = array_column($this->barisHasil($babak), 'lintasan');
        sort($urutan);
        $this->assertSame(range(1, 11), $urutan);

        // Urutan sampai 99 boleh, lintasan nomor lari tidak.
        $this->assertSame([], (new SeriLintasan())->simpanAturanNomor($frog, ['tempat_per_seri' => '6']));
        $this->assertSame(2, (new SeriLintasan())->bagiOtomatis($babak)['seri']);

        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak);
        $halaman->assertOK();
        $this->assertStringContainsString('Kelompok dan urutan tampil', $halaman->getBody());
    }

    // ---------- Geser dan kunci ----------

    public function testGeserBentrokDitolakLaluTersimpanDanKunci(): void
    {
        $babak = $this->penyisihanDanFinal(6);
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan'], ['benih' => 5]);
        $baris = $this->barisHasil($babak['penyisihan']);
        [$a, $b] = [$baris[0], $baris[1]];

        // Dua peserta di seri dan lintasan yang sama.
        $galat = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/geser', ['posisi' => [
            $a['id'] => ['seri' => '1', 'lintasan' => '2'],
            $b['id'] => ['seri' => '1', 'lintasan' => '2'],
        ]]);
        $galat->assertRedirectTo(site_url('admin/seri/babak/' . $babak['penyisihan']) . '#daftar-seri');
        $galat->assertSessionHas('galat_geser');
        $this->assertSame($baris, $this->barisHasil($babak['penyisihan']));

        // Lintasan di luar 1–8.
        $galat = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/geser', ['posisi' => [$a['id'] => ['seri' => '1', 'lintasan' => '9']]]);
        $galat->assertSessionHas('galat_geser');

        // Ke seri 2 lintasan 1, satu lagi keluar dari seri.
        $simpan = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/geser', ['posisi' => [
            $a['id'] => ['seri' => '2', 'lintasan' => '1'],
            $b['id'] => ['seri' => '', 'lintasan' => ''],
        ]]);
        $this->assertBerhasilDialihkan($simpan, 'admin/seri/babak/' . $babak['penyisihan'] . '#daftar-seri', 'Seri dan lintasan tersimpan.');
        $baru = array_column($this->barisHasil($babak['penyisihan']), null, 'id');
        $this->assertSame([2, 1], [$baru[$a['id']]['seri'], $baru[$a['id']]['lintasan']]);
        $this->assertNull($baru[$b['id']]['seri']);
        $this->assertSame('2', $this->db->table('babak')->where('id', $babak['penyisihan'])->get()->getRow()->jumlah_seri);

        // Belum semua dibagi: tidak bisa dikunci.
        $kunci = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/kunci');
        $this->assertGagalDialihkan($kunci, 'admin/seri/babak/' . $babak['penyisihan'] . '#kunci-seri');

        $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/geser', ['posisi' => [$b['id'] => ['seri' => '2', 'lintasan' => '8']]]);
        $kunci = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/kunci');
        $this->assertBerhasilDialihkan($kunci, 'admin/seri/babak/' . $babak['penyisihan'] . '#kunci-seri');

        // Dikunci: geser dan bagi ditolak, isian nonaktif.
        $this->assertSame([0], array_keys((new SeriLintasan())->geser($babak['penyisihan'], [$a['id'] => ['seri' => '1', 'lintasan' => '1']])));
        $this->assertArrayHasKey('galat', (new SeriLintasan())->bagiOtomatis($babak['penyisihan']));
        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan']);
        $this->assertSame(12, $this->xpathDari($halaman)->query('//input[starts-with(@name, "posisi[")][@disabled]')->length);

        $buka = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/buka');
        $this->assertBerhasilDialihkan($buka, 'admin/seri/babak/' . $babak['penyisihan'] . '#kunci-seri');
        $this->assertNull($this->db->table('babak')->where('id', $babak['penyisihan'])->get()->getRow()->seri_dikunci_pada);
    }

    // ---------- Alur babak: Q/q dan babak berikut otomatis ----------

    public function testFinalTerisiOtomatisDariQDanUnggulan(): void
    {
        $babak = $this->penyisihanDanFinal(12);
        $seri  = new SeriLintasan();
        $seri->bagiOtomatis($babak['penyisihan'], ['benih' => 99]);
        $baris = $this->barisHasil($babak['penyisihan']);
        $this->assertSame([1 => 6, 2 => 6], $this->hitungPerSeri($baris));

        // Otomatis: 2 seri, Final 8 lintasan → Q = 4, q = 0.
        $aturan = $seri->aturanLolos($seri->babak($babak['penyisihan']));
        $this->assertSame([4, 0, true], [$aturan['q'], $aturan['q_kecil'], $aturan['otomatis']]);

        // Q = 3 tiap seri, q = 2 waktu terbaik berikutnya.
        $this->assertSame([], $seri->aturLolos($babak['penyisihan'], ['lolos_q' => '3', 'lolos_q_kecil' => '2']));

        // Hasil belum lengkap: Final belum terisi.
        $isian  = [];
        $waktu  = [];
        $urutKe = 0;

        foreach ($baris as $b) {
            // Waktu makin lambat menurut urutan baris; satu DNS.
            $waktu[$b['id']] = 900 + 10 * $urutKe++;
            $isian[$b['id']] = ['prestasi' => number_format($waktu[$b['id']] / 100, 2, ',', ''), 'keterangan' => ''];
        }

        $kosong = array_key_last($isian);
        $isian[$kosong] = ['prestasi' => '', 'keterangan' => 'DNS'];
        $sebagian = array_slice($isian, 0, 5, true);
        $this->assertSame([], (new HasilLomba())->simpanHasil($babak['penyisihan'], $sebagian));
        $this->assertSame(0, $this->db->table('hasil')->where('babak_id', $babak['final'])->countAllResults());

        // Lengkap: Final terisi 3 terbaik tiap seri + 2 waktu terbaik berikutnya.
        $this->assertSame([], (new HasilLomba())->simpanHasil($babak['penyisihan'], $isian));

        $final = $this->barisHasil($babak['final']);
        $this->assertCount(8, $final);
        $this->assertSame([1 => 8], $this->hitungPerSeri($final));

        $harapan = $this->lolosHarapan($baris, $waktu, $kosong, 3, 2);
        $kunciFinal = array_map(static fn (array $b): int => (int) $b['atlet_id'], $final);
        sort($kunciFinal);
        $this->assertSame($harapan['semua'], $kunciFinal);

        // Unggulan: empat waktu terbaik di lintasan 3–6.
        $lajur = array_column($final, 'lintasan', 'atlet_id');
        $empat = array_map(static fn (int $id): int => (int) $lajur[$id], array_slice($harapan['urut'], 0, 4));
        sort($empat);
        $this->assertSame([3, 4, 5, 6], $empat);

        // Halaman admin menandai Q/q; Final tidak diisi ulang saat hasil disunting lagi.
        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan']);
        $xpath   = $this->xpathDari($halaman);
        $this->assertSame(6, $xpath->query('//*[@data-tanda-lolos="Q"]')->length);
        $this->assertSame(2, $xpath->query('//*[@data-tanda-lolos="q"]')->length);

        (new SeriLintasan())->geser($babak['final'], [$final[0]['id'] => ['seri' => '', 'lintasan' => '']]);
        (new HasilLomba())->simpanHasil($babak['penyisihan'], $isian);
        $this->assertNull(array_column($this->barisHasil($babak['final']), null, 'id')[$final[0]['id']]['seri'], 'Koreksi admin di Final tetap.');

        // Peserta Final dikoreksi: peringatan selisih.
        (new HasilLomba())->aturPeserta($babak['final'], array_map(static fn (array $b): string => 'a:' . $b['atlet_id'], array_slice($final, 1)));
        $halaman = $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['final']);
        $this->assertSame(1, $this->xpathDari($halaman)->query('//*[@data-selisih-lolos]')->length);

        // Bagan admin per nomor.
        $nomor = $this->bukaSebagaiAdmin('admin/seri/nomor/' . $this->idInduk['lari60putra']);
        $nomor->assertOK();
        $this->assertSame(2, $this->xpathDari($nomor)->query('//*[@data-bagan-babak]')->length);
    }

    public function testBagiDitolakBilaSudahAdaPrestasi(): void
    {
        $babak = $this->penyisihanDanFinal(4);
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan']);
        $baris = $this->barisHasil($babak['penyisihan']);
        (new HasilLomba())->simpanHasil($babak['penyisihan'], [$baris[0]['id'] => ['prestasi' => '9,50', 'keterangan' => '']]);

        $bagi = $this->kirimSebagaiAdmin('admin/seri/babak/' . $babak['penyisihan'] . '/bagi', []);
        $this->assertGagalDialihkan($bagi, 'admin/seri/babak/' . $babak['penyisihan'] . '#bagi-otomatis');
        $this->assertSame($baris[1]['seri'], $this->barisHasil($babak['penyisihan'])[1]['seri']);
    }

    // ---------- Publik ----------

    public function testHalamanPublikHanyaBabakDikunciDanTautanDiJadwal(): void
    {
        setting('HalamanDepan.tampil_seri', true);
        setting('HalamanDepan.tampil_jadwal', true);
        $babak = $this->penyisihanDanFinal(9);
        (new JadwalPertandingan())->simpan($babak['penyisihan'], ['tanggal' => self::HARI, 'jam_mulai' => '08:00', 'lokasi' => 'Lintasan utama']);
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan']);

        // Belum dikunci: 404 dan tanpa tautan.
        try {
            $this->get('jadwal/' . $babak['penyisihan']);
            $this->fail('Babak belum dikunci seharusnya 404.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $jadwal = $this->get('jadwal?hari=' . self::HARI);
        $this->assertSame(0, $this->xpathDari($jadwal)->query('//a[@data-tautan-seri]')->length);

        (new SeriLintasan())->kunci($babak['penyisihan'], true);
        $jadwal = $this->get('jadwal?hari=' . self::HARI);
        $this->assertGreaterThan(0, $this->xpathDari($jadwal)->query('//a[@data-tautan-seri][@href="' . site_url('jadwal/' . $babak['penyisihan']) . '"]')->length);

        $halaman = $this->get('jadwal/' . $babak['penyisihan']);
        $halaman->assertOK();
        $xpath = $this->xpathDari($halaman);
        $this->assertSame(2, $xpath->query('//*[@data-daftar-seri]/section[@data-seri]')->length);
        $this->assertSame(9, $xpath->query('//*[@data-daftar-seri]//li[@data-peserta]')->length);
        $this->assertSame(2, $xpath->query('//*[@data-bagan-babak]')->length);
        $this->assertStringContainsString('Lintasan utama', $halaman->getBody());
        // Final belum dikunci: pesertanya belum diumumkan, tanpa tanda lolos.
        $this->assertSame(0, $xpath->query('//*[@data-bagan-babak="' . $babak['final'] . '"]//li[@data-peserta-bagan]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-tanda-lolos]')->length);
        $this->assertStringNotContainsString('NISN', $halaman->getBody());

        try {
            $this->get('jadwal/' . $babak['final']);
            $this->fail('Final belum dikunci seharusnya 404.');
        } catch (PageNotFoundException) {
            $this->addToAssertionCount(1);
        }
    }

    // ---------- Unduhan ----------

    public function testUnduhDaftarSeriPerNomorDanPerHari(): void
    {
        $babak = $this->penyisihanDanFinal(10);
        (new JadwalPertandingan())->simpan($babak['penyisihan'], ['tanggal' => self::HARI, 'jam_mulai' => '07:30', 'lokasi' => 'Lintasan utama']);
        (new SeriLintasan())->bagiOtomatis($babak['penyisihan']);

        $pdf = $this->bukaSebagaiAdmin('admin/seri/nomor/' . $this->idInduk['lari60putra'] . '/pdf');
        $pdf->assertOK();
        $this->assertStringStartsWith('application/pdf', $pdf->response()->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('%PDF', $pdf->response()->getBody());
        $this->assertStringContainsString('filename="Daftar_Seri_', $pdf->response()->getHeaderLine('Content-Disposition'));

        $excel = $this->bukaSebagaiAdmin('admin/seri/hari/' . self::HARI . '/excel')->response();
        $this->assertInstanceOf(DownloadResponse::class, $excel);
        ob_start();
        $excel->sendBody();
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-seri-') . '.xlsx';
        file_put_contents($berkas, (string) ob_get_clean());

        try {
            $lembar = IOFactory::load($berkas)->getSheet(0)->toArray();
        } finally {
            @unlink($berkas);
        }

        $this->assertSame(['Nomor', 'Babak', 'Jadwal', 'Seri', 'Lintasan/Urutan', 'No. Atlet', 'Nama/Regu', 'Anggota', 'Sekolah', 'Prestasi'], $lembar[0]);
        $this->assertCount(11, $lembar);
        $this->assertSame('Penyisihan', $lembar[1][1]);

        $this->bukaSebagaiAdmin('admin/seri/hari/' . self::HARI . '/pdf')->assertOK();

        $seri = new SeriLintasan();
        $html = (new App\Libraries\Hasil\UnduhanSeri('Contoh', $seri->untukUnduhan($seri->babakHari(self::HARI))))->html(false);
        $this->assertStringContainsString('Seri 2', $html);
        $this->assertStringContainsString('07:30 WIB', $html);
        $this->assertStringContainsString('Belum dikunci', $html);
        $this->assertSame([self::HARI], $seri->hariDibagi());

        $indeks = $this->bukaSebagaiAdmin('admin/seri');
        $this->assertSame(2, $this->xpathDari($indeks)->query('//*[@data-unduh-hari-seri]//a')->length);

        $this->expectException(PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/seri/hari/2099-12-10/pdf');
    }

    // ---------- Pindah server ----------

    public function testPaketPindahServerMembawaSeri(): void
    {
        $babak = $this->penyisihanDanFinal(5);
        $seri  = new SeriLintasan();
        $seri->simpanAturanNomor($this->idInduk['lari60putra'], ['tempat_per_seri' => '6', 'urutan_lintasan' => '3,4;2,5;1,6']);
        $seri->bagiOtomatis($babak['penyisihan'], ['benih' => 31]);
        $seri->aturLolos($babak['penyisihan'], ['lolos_q' => '2', 'lolos_q_kecil' => '1']);
        $seri->kunci($babak['penyisihan'], true);
        $nisn   = array_column($this->db->table('atlet')->select('id, nisn')->get()->getResultArray(), 'nisn', 'id');
        $lama   = array_map(static fn (array $b): array => [$nisn[$b['atlet_id']], $b['seri'], $b['lintasan']], $this->barisHasil($babak['penyisihan']));
        $zip    = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);
            $this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putra'])->update(['tempat_per_seri' => null, 'urutan_lintasan' => null]);

            foreach (['hasil', 'babak', 'atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $tabel) {
                $this->db->table($tabel)->where('id >', 0)->delete();
            }

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $n = $this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putra'])->get()->getRow();
        $this->assertSame(['6', '3,4;2,5;1,6'], [$n->tempat_per_seri, $n->urutan_lintasan]);
        $b = $this->db->table('babak')->where('nomor_lomba_id', $this->idInduk['lari60putra'])->where('nama', 'Penyisihan')->get()->getRow();
        $this->assertSame(['1', '2', '1', '31'], [$b->jumlah_seri, $b->lolos_q, $b->lolos_q_kecil, $b->benih]);
        $this->assertNotNull($b->seri_dikunci_pada);

        $nisn = array_column($this->db->table('atlet')->select('id, nisn')->get()->getResultArray(), 'nisn', 'id');
        $baru = array_map(static fn (array $h): array => [$nisn[$h['atlet_id']], $h['seri'], $h['lintasan']], $this->barisHasil((int) $b->id));
        sort($lama);
        sort($baru);
        $this->assertCount(5, $baru);
        $this->assertSame($lama, $baru);
    }

    // ---------- Pembantu ----------

    /**
     * Penyisihan (kosong) dan Final (kosong) Lari 60 m putra, dengan `$atlet`
     * atlet Lunas dari tiga sekolah karangan.
     *
     * @return array{penyisihan: int, final: int}
     */
    private function penyisihanDanFinal(int $atlet): array
    {
        if ($atlet > 0) {
            $per  = intdiv($atlet, 3);
            $sisa = $atlet - 2 * $per;
            $this->sekolahDenganAtlet(['20000001' => $per, '20000002' => $per, '20000003' => $sisa]);
        }

        $hasil = new HasilLomba();

        return [
            'penyisihan' => $hasil->tambahBabak($this->idInduk['lari60putra'], 'Penyisihan', false, 'kosong')['id'],
            'final'      => $hasil->tambahBabak($this->idInduk['lari60putra'], 'Final', true, 'kosong')['id'],
        ];
    }

    /**
     * Pendaftaran Lunas per sekolah (NPSN => banyak atlet Lari 60 m putra).
     *
     * @param array<string, int> $sekolah
     *
     * @return list<int> id atlet
     */
    private function sekolahDenganAtlet(array $sekolah): array
    {
        $id = [];

        foreach ($sekolah as $npsn => $jumlah) {
            if ($jumlah < 1) {
                continue;
            }

            $p  = $this->buatPendaftaranPanel([
                'status'  => 'lunas',
                'sekolah' => ['npsn' => (string) $npsn, 'nama' => 'SD KARANGAN ' . $npsn],
                'atlet'   => array_map(fn (): array => $this->atletPanel(), range(1, $jumlah)),
            ]);
            $id = [...$id, ...$p['atlet']];
        }

        return $id;
    }

    /**
     * @return list<array{id: int, atlet_id: int, npsn: string, seri: int|null, lintasan: int|null}> urut id
     */
    private function barisHasil(int $babak): array
    {
        return array_map(static fn (array $h): array => [
            'id'       => (int) $h['id'],
            'atlet_id' => (int) $h['atlet_id'],
            'npsn'     => (string) $h['npsn'],
            'seri'     => $h['seri'] === null ? null : (int) $h['seri'],
            'lintasan' => $h['lintasan'] === null ? null : (int) $h['lintasan'],
        ], $this->db->table('hasil h')
            ->select('h.id, h.atlet_id, h.seri, h.lintasan, p.npsn')
            ->join('atlet a', 'a.id = h.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('h.babak_id', $babak)->orderBy('h.id')->get()->getResultArray());
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return array<int, int> seri => banyak peserta
     */
    private function hitungPerSeri(array $baris): array
    {
        $hitung = array_count_values(array_map(static fn (array $b): int => (int) $b['seri'], $baris));
        ksort($hitung);

        return $hitung;
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return array<string, array<int, int>> npsn => seri => banyak
     */
    private function sekolahPerSeri(array $baris): array
    {
        $hasil = [];

        foreach ($baris as $b) {
            $hasil[$b['npsn']][$b['seri']] = ($hasil[$b['npsn']][$b['seri']] ?? 0) + 1;
        }

        return $hasil;
    }

    /**
     * Yang seharusnya lolos (Q tiap seri, lalu q waktu terbaik).
     *
     * @param list<array<string, mixed>> $baris
     * @param array<int, int>            $waktu id hasil => waktu
     *
     * @return array{semua: list<int>, urut: list<int>} id atlet: urut naik, dan urut waktu
     */
    private function lolosHarapan(array $baris, array $waktu, int $dns, int $q, int $qKecil): array
    {
        $perSeri = [];
        $atlet   = array_column($baris, 'atlet_id', 'id');

        foreach ($baris as $b) {
            if ($b['id'] !== $dns) {
                $perSeri[$b['seri']][$b['id']] = $waktu[$b['id']];
            }
        }

        $lolos = [];
        $sisa  = [];

        foreach ($perSeri as $daftar) {
            asort($daftar);
            $lolos += array_slice($daftar, 0, $q, true);
            $sisa  += array_slice($daftar, $q, null, true);
        }

        asort($sisa);
        $lolos += array_slice($sisa, 0, $qKecil, true);
        asort($lolos);
        $urut  = array_map(static fn (int $id): int => $atlet[$id], array_keys($lolos));
        $semua = $urut;
        sort($semua);

        return ['semua' => $semua, 'urut' => $urut];
    }
}
