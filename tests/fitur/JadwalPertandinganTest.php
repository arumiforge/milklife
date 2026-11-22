<?php

use App\Controllers\JadwalController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\MenuPublik;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Exceptions\PageNotFoundException;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Jadwal pertandingan (F9-07): tanggal, jam mulai, dan lokasi per babak,
 * halaman admin (isi semua babak sekaligus dan dari halaman babak), halaman
 * publik `/jadwal` bersakelar bawaan mati, status otomatis, unduhan PDF per
 * hari, paket pindah server, dan transaksi yang tidak terganggu status gagal
 * yang tertinggal di koneksi.
 *
 * @internal
 */
final class JadwalPertandinganTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    private const HARI_LALU   = '2020-07-15';
    private const HARI_NANTI  = '2099-12-05';
    private const HARI_NANTI2 = '2099-12-06';

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

    public function testSakelarBawaanMatiMenjawab404DanMenuTersembunyi(): void
    {
        $this->assertFalse(JadwalController::tampil());
        $babak = $this->babakContoh();
        (new JadwalPertandingan())->simpan($babak['penyisihan'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '07:00']);

        foreach (['jadwal', 'jadwal/' . self::HARI_NANTI . '/pdf'] as $alamat) {
            try {
                $this->get($alamat);
                $this->fail('Alamat ' . $alamat . ' seharusnya 404 selama sakelar mati.');
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }

        $this->assertNotContains('Jadwal', array_column(MenuPublik::butir(), 'label'));

        setting('HalamanDepan.tampil_jadwal', true);
        $this->assertContains('Jadwal', array_column(MenuPublik::butir(), 'label'));
        $this->get('jadwal')->assertOK();
    }

    public function testHalamanAdminHanyaUntukAdmin(): void
    {
        foreach (['admin/jadwal-pertandingan', 'admin/jadwal-pertandingan/' . self::HARI_NANTI . '/pdf'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach (['admin/jadwal-pertandingan', 'admin/hasil/babak/1/jadwal', 'admin/pengaturan/halaman-depan/jadwal'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->assertTokenCsrfWajib('admin/jadwal-pertandingan', ['babak' => []]);
    }

    public function testSakelarDariPengaturanDanHalamanJadwal(): void
    {
        $this->assertTokenCsrfWajib('admin/pengaturan/halaman-depan/jadwal', ['tampil_jadwal' => '1']);

        $pengaturan = $this->bukaSebagaiAdmin('admin/pengaturan/halaman-depan');
        $pengaturan->assertOK();
        $this->assertSame(1, $this->xpathDari($pengaturan)->query('//form[@action="' . site_url('admin/pengaturan/halaman-depan/jadwal') . '"]//input[@name="tampil_jadwal"][not(@checked)]')->length);

        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/halaman-depan/jadwal', ['tampil_jadwal' => '1']);
        $hasil->assertRedirectTo(site_url('admin/pengaturan/halaman-depan') . '#halaman-jadwal');
        $this->assertTrue(JadwalController::tampil());

        // Dari halaman Jadwal pertandingan: sakelar komponen mengirim 0 lalu 1 (atau 0 saja).
        $hasil = $this->kirimSebagaiAdmin('admin/pengaturan/halaman-depan/jadwal', ['tampil_jadwal' => '0', 'kembali' => 'jadwal-pertandingan']);
        $hasil->assertRedirectTo(site_url('admin/jadwal-pertandingan') . '#sakelar-jadwal');
        $this->assertFalse(JadwalController::tampil());

        $halaman = $this->bukaSebagaiAdmin('admin/jadwal-pertandingan');
        $halaman->assertOK();
        $this->assertSame(1, $this->xpathDari($halaman)->query('//section[@id="sakelar-jadwal"]//input[@type="checkbox"][@name="tampil_jadwal"][not(@checked)]')->length);
    }

    // ---------- Isi jadwal ----------

    public function testIsiSemuaBabakSekaligusLaluTampilDiHalamanPublik(): void
    {
        $babak = $this->babakContoh();

        $halaman = $this->bukaSebagaiAdmin('admin/jadwal-pertandingan');
        $halaman->assertOK();
        $xpath = $this->xpathDari($halaman);
        $this->assertSame(3, $xpath->query('//li[@data-baris-jadwal]')->length);
        // Satu gaya pemilih tanggal di semua halaman admin (K89, F9-17).
        $this->assertSame(3, $xpath->query('//input[@type="date"][starts-with(@name, "babak[")]')->length);
        $this->assertSame(1, $xpath->query('//input[@type="date"][@id="urut-tanggal"]')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "js/pilih-tanggal")]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-pemilih-tanggal]|//script[contains(@src, "pemilih-tanggal-tw")]')->length);

        $simpan = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan', ['babak' => [
            $babak['penyisihan'] => ['tanggal' => '05/12/2099', 'jam_mulai' => '7.30', 'lokasi' => '  Lintasan   utama '],
            $babak['final']      => ['tanggal' => '5-12-2099', 'jam_mulai' => '10:00', 'lokasi' => ''],
            $babak['putri']      => ['tanggal' => '06/12/2099', 'jam_mulai' => '', 'lokasi' => 'Lapangan B'],
        ]]);
        $this->assertBerhasilDialihkan($simpan, 'admin/jadwal-pertandingan', 'Jadwal pertandingan tersimpan.');

        $this->assertSame([self::HARI_NANTI, '07:30:00', 'Lintasan utama'], $this->jadwalDb($babak['penyisihan']));
        $this->assertSame([self::HARI_NANTI, '10:00:00', null], $this->jadwalDb($babak['final']));
        $this->assertSame([self::HARI_NANTI2, null, 'Lapangan B'], $this->jadwalDb($babak['putri']));

        // Isian tanggal baku `YYYY-MM-DD`, ditampilkan pilih-tanggal.js sebagai Tanggal, Bulan, Tahun (K89).
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/jadwal-pertandingan'));
        $this->assertSame(self::HARI_NANTI, $xpath->query('//input[@name="babak[' . $babak['penyisihan'] . '][tanggal]"]')->item(0)->getAttribute('value'));
        $this->assertSame('07:30', $xpath->query('//input[@name="babak[' . $babak['penyisihan'] . '][jam_mulai]"]')->item(0)->getAttribute('value'));
        $this->assertSame(2, $xpath->query('//*[@data-unduh-hari]//a')->length);

        setting('HalamanDepan.tampil_jadwal', true);

        // Tanpa ?hari: hari terdekat berikutnya, urut jam.
        $publik = $this->get('jadwal');
        $publik->assertOK();
        $xpath = $this->xpathDari($publik);
        $this->assertSame([self::HARI_NANTI, self::HARI_NANTI2], array_map(static fn (DOMElement $a): string => $a->getAttribute('data-hari'), iterator_to_array($xpath->query('//nav[@data-tab-hari]//a'))));
        $this->assertSame(self::HARI_NANTI, $xpath->query('//nav[@data-tab-hari]//a[@aria-current="page"]')->item(0)->getAttribute('data-hari'));
        $this->assertSame('Sabtu, 5 Desember 2099', trim($xpath->query('//*[@data-judul-hari]')->item(0)->textContent));

        $baris = $xpath->query('//table[@data-tabel-jadwal]/tbody/tr');
        $this->assertSame(2, $baris->length);
        $this->assertSame([(string) $babak['penyisihan'], (string) $babak['final']], [$baris->item(0)->getAttribute('data-babak'), $baris->item(1)->getAttribute('data-babak')]);
        $this->assertStringContainsString('07:30', $baris->item(0)->textContent);
        $this->assertStringContainsString('Lari 60 m', $baris->item(0)->textContent);
        $this->assertStringContainsString('Putra', $baris->item(0)->textContent);
        $this->assertStringContainsString('KU 10', $baris->item(0)->textContent);
        $this->assertStringContainsString('Penyisihan', $baris->item(0)->textContent);
        $this->assertStringContainsString('Lintasan utama', $baris->item(0)->textContent);
        $this->assertSame(['belum', 'belum'], [$baris->item(0)->getAttribute('data-status'), $baris->item(1)->getAttribute('data-status')]);
        $this->assertSame(2, $xpath->query('//ol[@data-daftar-kartu-jadwal]/li')->length);
        $this->assertSame(site_url('jadwal/' . self::HARI_NANTI . '/pdf'), $xpath->query('//a[@data-unduh-jadwal]')->item(0)->getAttribute('href'));

        // Hari lain lewat tab; tanggal yang tidak ada kembali ke bawaan.
        $kedua = $this->xpathDari($this->get('jadwal?hari=' . self::HARI_NANTI2));
        $this->assertSame([(string) $babak['putri']], array_map(static fn (DOMElement $tr): string => $tr->getAttribute('data-babak'), iterator_to_array($kedua->query('//table[@data-tabel-jadwal]/tbody/tr'))));
        $this->assertStringContainsString('Menyusul', $kedua->query('//table[@data-tabel-jadwal]/tbody/tr')->item(0)->textContent);
        $this->assertSame(self::HARI_NANTI, $this->xpathDari($this->get('jadwal?hari=2001-01-01'))->query('//nav[@data-tab-hari]//a[@aria-current="page"]')->item(0)->getAttribute('data-hari'));
    }

    public function testBabakTanpaTanggalTidakTampilDanKeadaanKosong(): void
    {
        $this->babakContoh();
        setting('HalamanDepan.tampil_jadwal', true);

        $halaman = $this->get('jadwal');
        $halaman->assertOK();
        $halaman->assertSee('Jadwal belum diumumkan');
        $this->assertSame(0, $this->xpathDari($halaman)->query('//table[@data-tabel-jadwal]')->length);
    }

    public function testIsianSalahTidakAdaYangTersimpan(): void
    {
        $babak = $this->babakContoh();

        $hasil = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan', ['babak' => [
            $babak['penyisihan'] => ['tanggal' => '05/12/2099', 'jam_mulai' => '07:30', 'lokasi' => ''],
            $babak['final']      => ['tanggal' => '31/02/2099', 'jam_mulai' => '25:00', 'lokasi' => str_repeat('L', JadwalPertandingan::LOKASI_MAKSIMAL + 1)],
            $babak['putri']      => ['tanggal' => '', 'jam_mulai' => '08:00', 'lokasi' => ''],
        ]]);
        $hasil->assertRedirectTo(site_url('admin/jadwal-pertandingan') . '#jadwal-babak-' . $babak['final']);
        $hasil->assertSessionHas('pesan_galat');

        $galat = session('galat_jadwal');
        $this->assertSame(['tanggal', 'jam_mulai', 'lokasi'], array_keys($galat[$babak['final']]));
        $this->assertStringContainsString('Isi tanggalnya juga', $galat[$babak['putri']]['tanggal']);
        $this->assertSame([null, null, null], $this->jadwalDb($babak['penyisihan']));

        $halaman = $this->bukaSebagaiAdmin('admin/jadwal-pertandingan', ['galat_jadwal' => $galat, 'isian_jadwal' => [$babak['final'] => ['tanggal' => '31/02/2099']]]);
        $xpath   = $this->xpathDari($halaman);
        $this->assertSame('31/02/2099', $xpath->query('//input[@name="babak[' . $babak['final'] . '][tanggal]"]')->item(0)->getAttribute('value'));
        $this->assertSame('true', $xpath->query('//input[@name="babak[' . $babak['final'] . '][jam_mulai]"]')->item(0)->getAttribute('aria-invalid'));
        $halaman->assertSee('Tulis seperti 07:30.');
    }

    public function testJadwalDariHalamanBabak(): void
    {
        $babak = $this->babakContoh();
        $id    = $babak['final'];

        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $id);
        $halaman->assertOK();
        $this->assertSame(1, $this->xpathDari($halaman)->query('//section[@id="jadwal-babak"]//form[@action="' . site_url('admin/hasil/babak/' . $id . '/jadwal') . '"]//input[@type="date"][@name="tanggal"]')->length);

        $simpan = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $id . '/jadwal', ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '09:15', 'lokasi' => 'Lintasan utama']);
        $simpan->assertRedirectTo(site_url('admin/hasil/babak/' . $id) . '#jadwal-babak');
        $simpan->assertSessionHas('pesan_sukses', 'Jadwal babak tersimpan.');
        $this->assertSame([self::HARI_NANTI, '09:15:00', 'Lintasan utama'], $this->jadwalDb($id));

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/hasil/babak/' . $id));
        $this->assertSame(self::HARI_NANTI, $xpath->query('//input[@name="tanggal"]')->item(0)->getAttribute('value'));
        $this->assertSame('09:15', $xpath->query('//input[@name="jam_mulai"]')->item(0)->getAttribute('value'));

        $salah = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $id . '/jadwal', ['tanggal' => self::HARI_NANTI, 'jam_mulai' => 'pagi', 'lokasi' => '']);
        $salah->assertRedirectTo(site_url('admin/hasil/babak/' . $id) . '#jadwal-babak');
        $salah->assertSessionHas('galat_jadwal_babak');
        $this->assertSame([self::HARI_NANTI, '09:15:00', 'Lintasan utama'], $this->jadwalDb($id));

        // Mengosongkan semua = belum dijadwalkan.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $id . '/jadwal', ['tanggal' => '', 'jam_mulai' => '', 'lokasi' => '']);
        $this->assertSame([null, null, null], $this->jadwalDb($id));

        $this->expectException(PageNotFoundException::class);
        $this->kirimSebagaiAdmin('admin/hasil/babak/999999/jadwal', ['tanggal' => self::HARI_NANTI]);
    }

    // ---------- Nomor acara (F11-07) ----------

    public function testNomorAcaraOtomatisDariUrutanJadwal(): void
    {
        $babak  = $this->babakContoh();
        $jadwal = new JadwalPertandingan();
        // Final disimpan lebih dulu, tetapi Penyisihan lebih pagi, jadi Penyisihan nomor pertama.
        $jadwal->simpan($babak['final'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '10:00']);
        $jadwal->simpan($babak['penyisihan'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '07:30']);
        $tambahan = (new HasilLomba())->tambahBabak($this->idInduk['lari60putri'], 'Penyisihan', false, 'kosong')['id'];

        // Sebelum diberi nomor: babak tampil wajar tanpa kolom Acara (K139).
        setting('HalamanDepan.tampil_jadwal', true);
        $xpath = $this->xpathDari($this->get('jadwal'));
        $this->assertSame(0, $xpath->query('//table[@data-tabel-jadwal]//th[normalize-space()="Acara"]')->length);
        $this->assertSame(0, $xpath->query('//*[@data-nomor-acara]')->length);

        $admin = $this->xpathDari($this->bukaSebagaiAdmin('admin/jadwal-pertandingan'));
        $this->assertSame('101', $admin->query('//form[@action="' . site_url('admin/jadwal-pertandingan/nomor-acara') . '"]//input[@name="awal"]')->item(0)->getAttribute('value'));

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['putri'] . '/jadwal', ['tanggal' => self::HARI_NANTI2, 'jam_mulai' => '', 'lokasi' => '']);

        $beri = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan/nomor-acara', ['awal' => '201']);
        $beri->assertRedirectTo(site_url('admin/jadwal-pertandingan') . '#nomor-acara');
        $beri->assertSessionHas('pesan_sukses', '3 babak terjadwal diberi nomor acara 201 sampai 203.');

        $this->assertSame(201, $this->acaraDb($babak['penyisihan']));
        $this->assertSame(202, $this->acaraDb($babak['final']));
        $this->assertSame(203, $this->acaraDb($babak['putri']));
        $this->assertNull($this->acaraDb($tambahan));
        $this->assertSame(201, (int) setting('NomorAcara.awal'));

        // Tampil di jadwal publik (tabel dan kartu), PDF jadwal, lembar juri, dan isian admin.
        $xpath = $this->xpathDari($this->get('jadwal'));
        $this->assertSame(1, $xpath->query('//table[@data-tabel-jadwal]//th[normalize-space()="Acara"]')->length);
        $this->assertSame('201', trim($xpath->query('//table[@data-tabel-jadwal]/tbody/tr[@data-babak="' . $babak['penyisihan'] . '"]/td[1]')->item(0)->textContent));
        $this->assertSame('Acara 202', trim($xpath->query('//li[@data-babak="' . $babak['final'] . '"]//*[@data-nomor-acara]')->item(0)->textContent));

        $html = (new App\Libraries\Hasil\UnduhanJadwal(self::HARI_NANTI, (new JadwalPertandingan())->perHari()[self::HARI_NANTI]))->html(false);
        $this->assertStringContainsString('<th width="15mm">Acara</th>', $html);

        $juri = $this->bukaSebagaiAdmin('admin/juri');
        $this->assertStringContainsString('Acara 201 · Penyisihan', html_entity_decode((string) $juri->getBody()));
        $this->assertStringContainsString('Acara 202 · ', html_entity_decode((string) $this->bukaSebagaiAdmin('admin/juri/babak/' . $babak['final'])->getBody()));

        $admin = $this->xpathDari($this->bukaSebagaiAdmin('admin/jadwal-pertandingan'));
        $this->assertSame('203', $admin->query('//input[@name="babak[' . $babak['putri'] . '][nomor_acara]"]')->item(0)->getAttribute('value'));
        $this->assertSame('', $admin->query('//input[@name="babak[' . $tambahan . '][nomor_acara]"]')->item(0)->getAttribute('value'));
        $this->assertSame('201', $admin->query('//input[@name="awal"]')->item(0)->getAttribute('value'));

        // Diberi ulang: nomor lama diganti, tidak bertabrakan.
        $this->kirimSebagaiAdmin('admin/jadwal-pertandingan/nomor-acara', ['awal' => '202']);
        $this->assertSame([202, 203, 204], [$this->acaraDb($babak['penyisihan']), $this->acaraDb($babak['final']), $this->acaraDb($babak['putri'])]);
    }

    public function testNomorAcaraDiubahPerBabakDanTidakBolehKembar(): void
    {
        $babak = $this->babakContoh();
        $isi   = static fn (string $acara): array => ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '', 'lokasi' => '', 'nomor_acara' => $acara];

        $simpan = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan', ['babak' => [
            $babak['penyisihan'] => $isi('105'),
            $babak['final']      => $isi(' 110 '),
            $babak['putri']      => $isi(''),
        ]]);
        $this->assertBerhasilDialihkan($simpan, 'admin/jadwal-pertandingan', 'Jadwal pertandingan tersimpan.');
        $this->assertSame([105, 110, null], [$this->acaraDb($babak['penyisihan']), $this->acaraDb($babak['final']), $this->acaraDb($babak['putri'])]);

        // Kembar dengan babak lain atau bukan angka: tidak ada yang tersimpan.
        $kembar = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan', ['babak' => [
            $babak['putri'] => $isi('110'),
        ]]);
        $kembar->assertSessionHas('galat_jadwal');
        $this->assertStringContainsString('Nomor acara 110 sudah dipakai Lari 60 m putra KU 10 Final', session('galat_jadwal')[$babak['putri']]['nomor_acara']);
        $this->assertNull($this->acaraDb($babak['putri']));

        $this->assertArrayHasKey('nomor_acara', (new JadwalPertandingan())->simpanBanyak([$babak['putri'] => $isi('A1')])[$babak['putri']]);
        $this->assertArrayHasKey('nomor_acara', (new JadwalPertandingan())->simpanBanyak([$babak['putri'] => $isi('0')])[$babak['putri']]);

        // Bertukar nomor dalam satu simpanan boleh.
        $this->assertSame([], (new JadwalPertandingan())->simpanBanyak([$babak['penyisihan'] => $isi('110'), $babak['final'] => $isi('105')]));
        $this->assertSame([110, 105], [$this->acaraDb($babak['penyisihan']), $this->acaraDb($babak['final'])]);

        // Dari halaman babak; formulir tanpa kotak nomor acara tidak mengubahnya.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['putri'] . '/jadwal', ['tanggal' => self::HARI_NANTI2, 'jam_mulai' => '', 'lokasi' => '', 'nomor_acara' => '120']);
        $this->assertSame(120, $this->acaraDb($babak['putri']));
        $this->assertSame('120', $this->xpathDari($this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak['putri']))->query('//input[@name="nomor_acara"]')->item(0)->getAttribute('value'));
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['putri'] . '/jadwal', ['tanggal' => self::HARI_NANTI2, 'jam_mulai' => '08:00', 'lokasi' => '']);
        $this->assertSame(120, $this->acaraDb($babak['putri']));

        // Nomor awal salah atau belum ada babak terjadwal.
        $salah = $this->kirimSebagaiAdmin('admin/jadwal-pertandingan/nomor-acara', ['awal' => 'seratus']);
        $salah->assertSessionHas('pesan_galat');
        $this->assertSame(['awal' => null, 'galat' => 'Nomor awal terlalu besar untuk 3 babak. Nomor acara paling besar 9999.'], JadwalPertandingan::periksaAwal('9998', 3));
        $this->assertSame(['awal' => 9997, 'galat' => null], JadwalPertandingan::periksaAwal('9997', 3));
        $this->assertSame(110, $this->acaraDb($babak['penyisihan']));
    }

    public function testNomorAcaraDiHasilDaftarSeriDanUnduhan(): void
    {
        $babak = $this->babakContoh();
        (new JadwalPertandingan())->simpan($babak['final'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '10:00', 'lokasi' => '', 'nomor_acara' => '107']);

        $hasil = new HasilLomba();
        $nomor = $hasil->nomor($this->idInduk['lari60putra']);
        $semua = $hasil->semuaBabak($nomor);
        $this->assertSame([null, 107], array_column($semua, 'nomor_acara'));
        $this->assertSame(107, $hasil->babak($babak['final'])['nomor_acara']);

        // Halaman hasil admin dan PDF hasil.
        $this->assertStringContainsString('Acara 107', (string) $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $this->idInduk['lari60putra'])->getBody());
        $this->assertStringContainsString('Acara 107 · Final', (new App\Libraries\Hasil\UnduhanHasil($nomor, $semua))->html(false));

        // Excel hasil: kolom Acara hanya bila ada babak bernomor.
        $lembar = (new App\Libraries\Hasil\UnduhanHasil($nomor, $semua))->excel()->lembar();
        $this->assertSame('Acara', $lembar->getSheet(1)->getCell('A1')->getValue());
        $this->assertSame(107, (int) $lembar->getSheet(1)->getCell('A2')->getValue());
        $tanpa = (new App\Libraries\Hasil\UnduhanHasil($nomor, [$semua[0]]))->excel()->lembar();
        $this->assertSame('Peringkat', $tanpa->getSheet(0)->getCell('A1')->getValue());

        // Daftar seri: bagan, halaman admin, PDF, dan Excel.
        $seri = new App\Libraries\Hasil\SeriLintasan();
        $this->assertSame(107, $seri->babak($babak['final'])['nomor_acara']);
        $this->assertSame([null, 107], array_column($seri->bagan($this->idInduk['lari60putra'], false), 'nomor_acara'));
        $this->assertStringContainsString('Acara 107 · Final', html_entity_decode((string) $this->bukaSebagaiAdmin('admin/seri/babak/' . $babak['final'])->getBody()));
        $daftar = [['babak' => $seri->babak($babak['final']), 'seri' => [0 => []], 'lolos' => ['q' => 0, 'q_kecil' => 0]]];
        $this->assertStringContainsString('Acara 107 · Lari 60 m', (new App\Libraries\Hasil\UnduhanSeri('Lari 60 m', $daftar))->html(false));
    }

    // ---------- Status ----------

    public function testStatusOtomatisDanPilihHari(): void
    {
        $kini  = new DateTimeImmutable('2026-12-05 08:00');
        $babak = static fn (?string $tanggal, ?string $jam, bool $terbit = false, bool $adaHasil = false): array => ['tanggal' => $tanggal, 'jam' => $jam, 'terbit' => $terbit, 'ada_hasil' => $adaHasil];

        $this->assertSame('belum', JadwalPertandingan::status($babak(null, null), $kini));
        $this->assertSame('belum', JadwalPertandingan::status($babak('2026-12-05', '08:01'), $kini));
        $this->assertSame('berlangsung', JadwalPertandingan::status($babak('2026-12-05', '08:00'), $kini));
        $this->assertSame('berlangsung', JadwalPertandingan::status($babak('2026-12-05', null), $kini));
        $this->assertSame('berlangsung', JadwalPertandingan::status($babak('2026-12-04', '15:00', true, false), $kini));
        $this->assertSame('belum', JadwalPertandingan::status($babak('2026-12-06', '07:00', false, true), $kini));
        $this->assertSame('selesai', JadwalPertandingan::status($babak('2026-12-05', '07:00', true, true), $kini));

        $hari = ['2026-12-04', '2026-12-06', '2026-12-07'];
        $this->assertNull(JadwalPertandingan::pilihHari([], null, $kini));
        $this->assertSame('2026-12-06', JadwalPertandingan::pilihHari($hari, null, $kini));
        $this->assertSame('2026-12-04', JadwalPertandingan::pilihHari($hari, '2026-12-04', $kini));
        $this->assertSame('2026-12-06', JadwalPertandingan::pilihHari($hari, 'bukan-tanggal', $kini));
        $this->assertSame('2026-12-07', JadwalPertandingan::pilihHari($hari, null, new DateTimeImmutable('2027-01-01')));

        $this->assertSame('Rabu, 15 Juli 2026', JadwalPertandingan::namaHari('2026-07-15'));
        $this->assertSame(' min="2025-01-01" max="2028-12-31"', JadwalPertandingan::rentangIsian(new DateTimeImmutable('2026-07-15')));

        foreach (['05/12/2026', '5-12-2026', '2026-12-05', '5.12.2026'] as $teks) {
            $this->assertSame('2026-12-05', JadwalPertandingan::periksa(['tanggal' => $teks])['data']['tanggal'], $teks);
        }

        foreach (['7.30' => '07:30:00', '07:30' => '07:30:00', '23:59' => '23:59:00', '07:30:00' => '07:30:00'] as $teks => $jam) {
            $this->assertSame($jam, JadwalPertandingan::periksa(['tanggal' => '05/12/2026', 'jam_mulai' => $teks])['data']['jam_mulai'], $teks);
        }

        $this->assertSame(['tanggal' => null, 'jam_mulai' => null, 'lokasi' => null], JadwalPertandingan::periksa([])['data']);
        $this->assertArrayHasKey('tanggal', JadwalPertandingan::periksa(['tanggal' => '1999-12-05'])['galat']);
    }

    public function testStatusDiHalamanPublikDanTautanHasil(): void
    {
        $babak = $this->babakContoh();
        $hasil = new HasilLomba();
        $jadwal = new JadwalPertandingan();
        $jadwal->simpanBanyak([
            $babak['penyisihan'] => ['tanggal' => self::HARI_LALU, 'jam_mulai' => '07:00'],
            $babak['final']      => ['tanggal' => self::HARI_LALU, 'jam_mulai' => '09:00'],
        ]);

        // Final diisi lalu nomornya terbit: Final Selesai, Penyisihan (tanpa prestasi) Berlangsung.
        $isian = [];
        foreach ($this->db->table('hasil')->where('babak_id', $babak['final'])->get()->getResultArray() as $i => $h) {
            $isian[(int) $h['id']] = ['prestasi' => (9 + $i) . ',50', 'keterangan' => ''];
        }
        $this->assertSame([], $hasil->simpanHasil($babak['final'], $isian));

        setting('HalamanDepan.tampil_jadwal', true);
        $xpath = $this->xpathDari($this->get('jadwal'));
        $this->assertSame('berlangsung', $xpath->query('//tr[@data-babak="' . $babak['final'] . '"]')->item(0)->getAttribute('data-status'));
        $this->assertSame(0, $xpath->query('//a[@data-tautan-hasil]')->length);

        $hasil->terbitkan($this->idInduk['lari60putra'], true);
        $xpath = $this->xpathDari($this->get('jadwal'));
        $this->assertSame('selesai', $xpath->query('//tr[@data-babak="' . $babak['final'] . '"]')->item(0)->getAttribute('data-status'));
        $this->assertSame('berlangsung', $xpath->query('//tr[@data-babak="' . $babak['penyisihan'] . '"]')->item(0)->getAttribute('data-status'));
        $this->assertStringContainsString('Selesai', $xpath->query('//tr[@data-babak="' . $babak['final'] . '"]')->item(0)->textContent);
        $this->assertSame(site_url('hasil/' . $this->idInduk['lari60putra']), $xpath->query('//tr[@data-babak="' . $babak['final'] . '"]//a[@data-tautan-hasil]')->item(0)->getAttribute('href'));
    }

    // ---------- Unduhan ----------

    public function testUnduhPdfPerHari(): void
    {
        $babak = $this->babakContoh();
        (new JadwalPertandingan())->simpan($babak['penyisihan'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '07:30', 'lokasi' => 'Lintasan utama']);

        // Admin bisa mengunduh walau halaman publik belum dibuka.
        $admin = $this->bukaSebagaiAdmin('admin/jadwal-pertandingan/' . self::HARI_NANTI . '/pdf');
        $admin->assertOK();
        $this->assertStringStartsWith('application/pdf', $admin->response()->getHeaderLine('Content-Type'));
        $this->assertStringStartsWith('%PDF', $admin->response()->getBody());
        $this->assertStringContainsString('filename="Jadwal_20991205', $admin->response()->getHeaderLine('Content-Disposition'));

        setting('HalamanDepan.tampil_jadwal', true);
        $publik = $this->get('jadwal/' . self::HARI_NANTI . '/pdf');
        $publik->assertOK();
        $this->assertStringStartsWith('%PDF', $publik->response()->getBody());

        $html = (new App\Libraries\Hasil\UnduhanJadwal(self::HARI_NANTI, (new JadwalPertandingan())->perHari()[self::HARI_NANTI]))->html(false);
        $this->assertStringContainsString('Sabtu, 5 Desember 2099', $html);
        $this->assertStringContainsString('07:30 WIB', $html);
        $this->assertStringContainsString('Lintasan utama', $html);

        $this->expectException(PageNotFoundException::class);
        $this->get('jadwal/' . self::HARI_NANTI2 . '/pdf');
    }

    // ---------- Pindah server dan transaksi ----------

    public function testPaketPindahServerMembawaJadwal(): void
    {
        $babak = $this->babakContoh();
        (new JadwalPertandingan())->simpan($babak['final'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '10:00', 'lokasi' => 'Lintasan utama', 'nomor_acara' => '112']);
        $zip = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);

            foreach (['hasil', 'babak', 'atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $tabel) {
                $this->db->table($tabel)->where('id >', 0)->delete();
            }

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $baru = $this->db->table('babak')->where('nama', 'Final')->where('nomor_lomba_id', $this->idInduk['lari60putra'])->get()->getRow();
        $this->assertSame([self::HARI_NANTI, '10:00:00', 'Lintasan utama'], [$baru->tanggal, $baru->jam_mulai, $baru->lokasi]);
        // Nomor acara (F11-07) ikut paket.
        $this->assertSame('112', (string) $baru->nomor_acara);
        $this->assertSame(1, $this->db->table('babak')->where('tanggal IS NULL', null, false)->where('nama', 'Penyisihan')->countAllResults());
    }

    public function testTetapTersimpanWalauKoneksiPernahMenggagalkanTransaksi(): void
    {
        $babak = $this->babakContoh();
        $this->tinggalkanStatusTransaksiGagal();

        $this->assertSame([], (new JadwalPertandingan())->simpan($babak['final'], ['tanggal' => self::HARI_NANTI, 'jam_mulai' => '10:00']));
        $this->assertSame([self::HARI_NANTI, '10:00:00', null], $this->jadwalDb($babak['final']));

        // Penyimpanan babak di HasilLomba memakai pola yang sama.
        $this->tinggalkanStatusTransaksiGagal();
        $this->assertSame([], (new HasilLomba())->ubahBabak($babak['penyisihan'], 'Penyisihan seri 1', false));
        $this->assertSame('Penyisihan seri 1', $this->db->table('babak')->where('id', $babak['penyisihan'])->get()->getRow()->nama);

        $this->tinggalkanStatusTransaksiGagal();
        $baru = (new HasilLomba())->tambahBabak($this->idInduk['lari60putri'], 'Semifinal', false, 'kosong');
        $this->assertArrayHasKey('id', $baru);
        $this->assertSame(1, $this->db->table('babak')->where('id', $baru['id'])->countAllResults());
    }

    // ---------- Pembantu ----------

    /**
     * Penyisihan dan Final Lari 60 m putra (tiga atlet Lunas, semua ikut) dan
     * Final Lari 60 m putri (kosong), belum dijadwalkan.
     *
     * @return array{penyisihan: int, final: int, putri: int}
     */
    private function babakContoh(): array
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel(), $this->atletPanel()]]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $hasil = new HasilLomba();

        return [
            'penyisihan' => $hasil->tambahBabak($this->idInduk['lari60putra'], 'Penyisihan', false, 'semua')['id'],
            'final'      => $hasil->tambahBabak($this->idInduk['lari60putra'], 'Final', true, 'semua')['id'],
            'putri'      => $hasil->tambahBabak($this->idInduk['lari60putri'], 'Final', true, 'kosong')['id'],
        ];
    }

    private function acaraDb(int $babak): ?int
    {
        $nomor = $this->db->table('babak')->select('nomor_acara')->where('id', $babak)->get()->getRow()->nomor_acara;

        return $nomor === null ? null : (int) $nomor;
    }

    /**
     * @return array{0: string|null, 1: string|null, 2: string|null} tanggal, jam_mulai, lokasi
     */
    private function jadwalDb(int $babak): array
    {
        $b = $this->db->table('babak')->select('tanggal, jam_mulai, lokasi')->where('id', $babak)->get()->getRow();

        return [$b->tanggal, $b->jam_mulai, $b->lokasi];
    }
}
