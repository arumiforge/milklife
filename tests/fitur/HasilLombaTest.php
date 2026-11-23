<?php

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\UnduhanHasil;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Hasil lomba semua babak (F8-04, K124): babak, peserta, prestasi,
 * peringkat, koreksi Final, Terbitkan, halaman publik, unduhan, penggabungan,
 * dan paket pindah server.
 *
 * @internal
 */
final class HasilLombaTest extends UjiBasisData
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

    public function testHalamanAdminHanyaUntukAdmin(): void
    {
        foreach (['admin/hasil', 'admin/hasil/nomor/1', 'admin/hasil/nomor/1/pdf', 'admin/hasil/babak/1'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        foreach (['admin/hasil/nomor/1/babak', 'admin/hasil/nomor/1/terbitkan', 'admin/hasil/babak/1', 'admin/hasil/babak/1/hapus'] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat, 'post');
        }

        $this->assertTokenCsrfWajib('admin/hasil/nomor/' . $this->idInduk['lari60putra'] . '/terbitkan');
    }

    public function testPenyisihanLaluFinalDariYangLolosDanTerbit(): void
    {
        $atlet = $this->tigaAtletLunas();
        $nomor = $this->idInduk['lari60putra'];

        $halaman = $this->bukaSebagaiAdmin('admin/hasil');
        $halaman->assertOK();
        $halaman->assertSee('Lari 60 m putra');

        // Babak pertama: semua peserta Lunas (yang Menunggu tidak ikut).
        $tambah = $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => ' Penyisihan  seri 1 ', 'isi' => 'semua']);
        $babak  = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->get()->getRow()->id;
        $this->assertBerhasilDialihkan($tambah, 'admin/hasil/babak/' . $babak);
        $this->assertSame('Penyisihan seri 1', $this->db->table('babak')->where('id', $babak)->get()->getRow()->nama);
        $this->assertSame(3, $this->db->table('hasil')->where('babak_id', $babak)->countAllResults());

        $id = $this->idHasil($babak);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[$atlet[0]] => ['prestasi' => '9,87', 'keterangan' => ''],
            $id[$atlet[1]] => ['prestasi' => '9.5', 'keterangan' => ''],
            $id[$atlet[2]] => ['prestasi' => '', 'keterangan' => 'DNS'],
        ]]), 'admin/hasil/babak/' . $babak);
        $this->assertSame([$atlet[1] => 1, $atlet[0] => 2, $atlet[2] => null], $this->peringkat($babak));

        $isi = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak);
        $isi->assertOK();
        $isi->assertSee('9,50');
        $isi->assertDontSee('Koreksi per.');

        // Final dari 2 teratas penyisihan.
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final', 'final' => '1', 'isi' => 'lolos', 'dari_babak' => (string) $babak, 'jumlah_lolos' => '2']);
        $final = (int) $this->db->table('babak')->where('nomor_lomba_id', $nomor)->where('final', 1)->get()->getRow()->id;
        $this->assertSame([$atlet[0], $atlet[1]], array_map('intval', array_column($this->db->table('hasil')->where('babak_id', $final)->orderBy('atlet_id')->get()->getResultArray(), 'atlet_id')));

        // Prestasi sama = peringkat sama; lalu koreksi admin dari foto finis.
        $idF = $this->idHasil($final);
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => [
            $idF[$atlet[0]] => ['prestasi' => '9,40', 'peringkat' => ''],
            $idF[$atlet[1]] => ['prestasi' => '9,40', 'peringkat' => ''],
        ]]);
        $this->assertSame([1, 1], array_values($this->peringkat($final)));

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $final, ['hasil' => [
            $idF[$atlet[0]] => ['prestasi' => '9,40', 'peringkat' => '2'],
            $idF[$atlet[1]] => ['prestasi' => '9,40', 'peringkat' => ''],
        ]]);
        $this->assertSame([$atlet[1] => 1, $atlet[0] => 2], $this->peringkat($final));
        $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor)->assertSee('diubah admin');

        // Belum terbit: publik tidak melihatnya.
        $this->assertFalse((new HasilLomba())->adaTerbit());
        $this->get('hasil')->assertSeeElement('#hasil-kosong');

        try {
            $this->get('hasil/' . $nomor);
            $this->fail('Nomor yang belum terbit seharusnya tidak ditemukan.');
        } catch (PageNotFoundException) {
        }

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/terbitkan'), 'admin/hasil/nomor/' . $nomor);
        $this->assertNotNull($this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->hasil_terbit_pada);

        $publik = $this->get('hasil');
        $publik->assertOK();
        $publik->assertSee('Lari 60 m putra');
        $publik->assertSee('Juara I');
        $this->assertSame(site_url('hasil'), $this->xpathDari($publik)->query('//nav//a[normalize-space()="Hasil"]')->item(0)?->getAttribute('href'));

        $rinci = $this->get('hasil/' . $nomor);
        $rinci->assertOK();
        $rinci->assertSee('Penyisihan seri 1');
        $rinci->assertDontSee('diubah admin');
        $this->assertStringStartsWith('%PDF', $this->get('hasil/' . $nomor . '/pdf')->response()->getBody());

        // K175: jejak lengkap di kiri atas, unduhan ikon saja dengan nama aksesibel lengkap.
        $x     = $this->xpathDari($rinci);
        $jejak = array_map(static fn (DOMElement $li): string => trim((string) preg_replace('/\s+/u', ' ', $li->textContent)), iterator_to_array($x->query('//nav[@data-jejak-publik][@aria-label="Jejak halaman"]//li')));
        $this->assertCount(4, $jejak);
        $this->assertSame(['Beranda', 'Hasil'], array_slice($jejak, 0, 2));
        $this->assertSame('Lari 60 m putra', $jejak[3]);
        $this->assertStringContainsString('#hasil-ku-', $x->query('//nav[@data-jejak-publik]//li[3]/a')->item(0)?->getAttribute('href') ?? '');
        $this->assertSame('page', $x->query('//nav[@data-jejak-publik]//li[4]/span')->item(0)?->getAttribute('aria-current'));
        $this->assertSame(0, $x->query('//*[@data-tautan-kembali]')->length, 'Tautan kembali diganti jejak.');
        $pdf = $x->query('//a[@data-tombol-unduh="pdf"]')->item(0);
        $this->assertSame(site_url('hasil/' . $nomor . '/pdf'), $pdf?->getAttribute('href'));
        $this->assertStringStartsWith('Unduh PDF hasil Lari 60 m putra KU', $pdf->getAttribute('aria-label'));
        $this->assertSame('Unduh PDF', trim($x->query('.//span[@aria-hidden="true"]', $pdf)->item(0)?->textContent ?? ''));
        $this->assertSame(site_url('hasil/' . $nomor . '/excel'), $x->query('//a[@data-tombol-unduh="excel"]')->item(0)?->getAttribute('href'));

        // Hasil publik memakai komponen tombol unduh yang sama dengan panel admin (F11-13, K177).
        $this->assertSame(1, $x->query('//a[@data-tombol-unduh="pdf"]//*[@data-ikon-pdf]')->length);
        $this->assertSame('Unduh Excel', trim($x->query('//a[@data-tombol-unduh="excel"]//span[@aria-hidden="true"]')->item(0)?->textContent ?? ''));
        $this->assertStringContainsString('"' . aset('js/tombol-unduh.js') . '"', $rinci->getBody());

        $klasemen = $this->xpathDari($this->get('hasil/klasemen'));
        $this->assertSame(1, $klasemen->query('//a[@data-tombol-unduh="pdf"][@aria-label="Unduh PDF klasemen sekolah"]')->length);
        $this->assertSame(1, $klasemen->query('//a[@data-tombol-unduh="excel"][@aria-label="Unduh Excel klasemen sekolah"]')->length);
        $this->assertSame(3, $klasemen->query('//nav[@data-jejak-publik]//li')->length);
        $this->assertSame(2, $this->xpathDari($publik)->query('//nav[@data-jejak-publik]//li')->length);

        // Halaman depan: Lihat semua hasil dan Lihat klasemen dalam satu deret yang melebar di ponsel.
        $beranda = $this->get('/');
        $deret   = $this->xpathDari($beranda)->query('//section[@id="hasil"]//*[@data-deret-tombol]/a[@data-deret-butir]');
        $this->assertSame([site_url('hasil'), site_url('hasil/klasemen')], array_map(static fn (DOMElement $a): string => $a->getAttribute('href'), iterator_to_array($deret)));
        $this->assertTrue($deret->item(0)->hasAttribute('data-lebar'));
        $this->assertFalse($deret->item(1)->hasAttribute('data-lebar'));
        $this->assertSame('Lihat klasemen sekolah', trim($deret->item(1)->textContent), 'Teks tetap ada untuk pembaca layar.');
        $this->assertStringContainsString(aset('js/deret-tombol.js'), $beranda->getBody());

        $juara = (new HasilLomba())->juara(true);
        $this->assertSame([1, 2], array_column($juara, 'peringkat'));
        $this->assertSame('SD KARANGAN MAJU', $juara[0]['sekolah']);

        // Tarik kembali.
        $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/tarik');
        $this->assertNull($this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->hasil_terbit_pada);
        $this->assertSame([], (new HasilLomba())->juara(true));
    }

    public function testIsianSalahTidakDisimpanDanMenyebutContoh(): void
    {
        $this->tigaAtletLunas();
        $babak = (new HasilLomba())->tambahBabak($this->idInduk['lari60putra'], 'Final', true, 'semua')['id'];
        $id    = array_values($this->idHasil($babak));

        $hasil = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[0] => ['prestasi' => '9,87'],
            $id[1] => ['prestasi' => '9 detik'],
        ]]);
        $this->assertGagalDialihkan($hasil, 'admin/hasil/babak/' . $babak);
        $this->assertSame(0, $this->db->table('hasil')->where('babak_id', $babak)->where('prestasi IS NOT NULL', null, false)->countAllResults());

        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak, ['galat_hasil' => [$id[1] => 'Prestasi "9detik" belum benar. Tulis detik, misalnya 12,34.'], 'isian_hasil' => [$id[1] => ['prestasi' => '9 detik']]]);
        $halaman->assertSee('misalnya 12,34');
        $this->assertSame(1, $this->xpathDari($halaman)->query('//input[@name="hasil[' . $id[1] . '][prestasi]" and @aria-invalid="true"]')->length);
    }

    public function testSatuFinalPerNomorDanNamaBabakUnik(): void
    {
        $hasil = new HasilLomba();
        $nomor = $this->idInduk['turbo'];
        $this->assertArrayHasKey('id', $hasil->tambahBabak($nomor, 'Final', true, 'kosong'));
        $this->assertArrayHasKey('final', $hasil->tambahBabak($nomor, 'Final ulang', true, 'kosong')['galat']);
        $this->assertArrayHasKey('nama', $hasil->tambahBabak($nomor, 'final', false, 'kosong')['galat']);
        $this->assertArrayHasKey('nama', $hasil->tambahBabak($nomor, '  ', false, 'kosong')['galat']);

        $tolak = $this->kirimSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/babak', ['nama' => 'Final 2', 'final' => '1', 'isi' => 'kosong']);
        $tolak->assertRedirectTo(site_url('admin/hasil/nomor/' . $nomor) . '#tambah-babak');
        $tolak->assertSessionHas('galat_babak');
    }

    public function testBereguPerReguDanUnduhan(): void
    {
        $reguA = $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'A', 'peran' => 'inti']]]);
        $reguB = $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]]);
        $p     = $this->buatPendaftaranPanel(['atlet' => [...$reguA, ...$reguB]]);
        $q     = $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999915', 'nama' => 'SD KARANGAN LAIN'], 'atlet' => $this->timPanel('kanga', 4)]);
        (new StatusPembayaran())->tandaiLunas($p['id']);
        (new StatusPembayaran())->tandaiLunas($q['id']);

        $hasil = new HasilLomba();
        $nomor = $hasil->nomor($this->idInduk['kanga']);
        $calon = $hasil->calon($nomor);
        // Sekolah dengan dua regu menulis nama regunya; regu tunggal cukup nama sekolah (K108).
        $this->assertSame(['SD KARANGAN LAIN', 'SD KARANGAN MAJU – Regu A', 'SD KARANGAN MAJU – Regu B'], array_column($calon, 'nama'));

        $babak = $hasil->tambahBabak($nomor['id'], 'Final', true, 'semua')['id'];
        $id    = [];
        foreach ($hasil->hasilBabak($babak, $nomor) as $h) {
            $id[$h['nama']] = $h['id'];
        }
        $this->assertSame([], $hasil->simpanHasil($babak, [
            $id['SD KARANGAN MAJU – Regu A'] => ['prestasi' => '45,10'],
            $id['SD KARANGAN MAJU – Regu B'] => ['prestasi' => '44,90'],
            $id['SD KARANGAN LAIN']          => ['prestasi' => '46,00'],
        ]));

        $baris = $hasil->hasilBabak($babak, $nomor);
        $this->assertSame(['SD KARANGAN MAJU – Regu B', 'SD KARANGAN MAJU – Regu A', 'SD KARANGAN LAIN'], array_column($baris, 'nama'));
        $this->assertCount(4, $baris[0]['anggota']);

        $unduhan = new UnduhanHasil($nomor, $hasil->semuaBabak($nomor));
        $this->assertStringContainsString('Hasil Kanga&#039;s Escape putra – KU 10', $unduhan->html(false));
        $this->assertSame('Hasil_KU10KangasEscapeputra_MilkLifeAthleticsChallenge_2026.pdf', UnduhanHasil::namaBerkas($nomor, 'pdf'));

        $this->assertStringStartsWith('%PDF', $this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor['id'] . '/pdf')->response()->getBody());
        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor['id'] . '/excel'))->getSheet(0)->toArray();
        $this->assertSame(['Peringkat', 'Regu', 'Anggota', 'Sekolah', 'Prestasi (Waktu)', 'Keterangan'], $lembar[0]);
        $this->assertSame([1, 'SD KARANGAN MAJU – Regu B', '44,90'], [(int) $lembar[1][0], $lembar[1][1], $lembar[1][4]]);
    }

    public function testAturPesertaUbahDanHapusBabak(): void
    {
        $atlet = $this->tigaAtletLunas();
        $babak = (new HasilLomba())->tambahBabak($this->idInduk['lari60putra'], 'Seri 1', false, 'kosong')['id'];
        $this->assertSame(0, $this->db->table('hasil')->where('babak_id', $babak)->countAllResults());

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a:' . $atlet[0], 'a:' . $atlet[2], 'a:999999']]);
        $this->assertSame([$atlet[0], $atlet[2]], array_keys($this->idHasil($babak)));

        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/peserta', ['peserta' => ['a:' . $atlet[2]]]);
        $this->assertSame([$atlet[2]], array_keys($this->idHasil($babak)));

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/ubah', ['nama' => 'Final', 'final' => '1']), 'admin/hasil/babak/' . $babak);
        $this->assertSame('1', (string) $this->db->table('babak')->where('id', $babak)->get()->getRow()->final);

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak . '/hapus'), 'admin/hasil/nomor/' . $this->idInduk['lari60putra']);
        $this->assertSame(0, $this->db->table('hasil')->countAllResults());
    }

    public function testJenisHasilDiPengaturanTerkunciSetelahAdaPrestasi(): void
    {
        $this->tigaAtletLunas();
        $hasil = new HasilLomba();
        $babak = $hasil->tambahBabak($this->idInduk['lari60putra'], 'Final', true, 'semua')['id'];
        $hasil->simpanHasil($babak, [array_values($this->idHasil($babak))[0] => ['prestasi' => '9,99']]);

        $isian = [
            'kelompok_umur_id' => (string) $this->idInduk['ku10'], 'nama' => 'Lari 60 m', 'jenis' => 'individu',
            'kategori' => 'putra', 'jenis_hasil' => 'jarak', 'urutan' => '1', 'aktif' => '1',
        ];
        $tolak = $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $this->idInduk['lari60putra'], $isian);
        $this->assertIsianSalahDitampilkanUlang($tolak, ['jenis_hasil']);

        // Waktu, jarak, poin, dan poin multi-lomba (F9-10).
        $this->assertSame(4, $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $this->idInduk['frog'] . '/ubah'))->query('//input[@name="jenis_hasil"]')->length);
        $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $this->idInduk['frog'], ['nama' => 'Frog Jump', 'jenis_hasil' => 'jarak', 'kelompok_umur_id' => (string) $this->idInduk['ku10']] + $isian);
        $this->assertSame('jarak', $this->db->table('nomor_lomba')->where('id', $this->idInduk['frog'])->get()->getRow()->jenis_hasil);
    }

    public function testWaktuTanganDibulatkanDitandaiDanPrestasiLamaTetap(): void
    {
        $atlet = $this->tigaAtletLunas();
        $nomor = $this->idInduk['lari60putra'];
        $hasil = new HasilLomba();
        $babak = $hasil->tambahBabak($nomor, 'Final', true, 'semua')['id'];
        $id    = $this->idHasil($babak);

        // Prestasi lama dari waktu elektronik.
        $this->assertSame([], $hasil->simpanHasil($babak, [$id[$atlet[0]] => ['prestasi' => '12,34']]));

        // Formulir nomor: bawaan elektronik, admin memilih tangan (F11-04).
        $formulir = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor . '/ubah'));
        $this->assertSame(1, $formulir->query('//fieldset[@id="bagian-cara-waktu"][not(@hidden)]//input[@name="cara_waktu"][@value="elektronik"][@checked]')->length);
        $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $nomor, [
            'kelompok_umur_id' => (string) $this->idInduk['ku10'], 'nama' => 'Lari 60 m', 'jenis' => 'individu',
            'kategori' => 'putra', 'jenis_hasil' => 'waktu', 'cara_waktu' => 'tangan', 'urutan' => '1', 'aktif' => '1',
        ]);
        $this->assertSame('tangan', $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRow()->cara_waktu);
        $this->assertSame('1234', (string) $this->db->table('hasil')->where('id', $id[$atlet[0]])->get()->getRow()->prestasi);

        // Disimpan dari halaman hasil: 12,31 dan 12,301 menjadi 12,4; 12,30 tetap 12,3.
        // Isian lama yang tampil sama (12,4) tidak menulis ulang 12,34.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [
            $id[$atlet[0]] => ['prestasi' => '12,4', 'peringkat' => ''],
            $id[$atlet[1]] => ['prestasi' => '12,301', 'peringkat' => ''],
            $id[$atlet[2]] => ['prestasi' => '12,30', 'peringkat' => ''],
        ]]), 'admin/hasil/babak/' . $babak);

        $tersimpan = array_column($this->db->table('hasil')->select('id, prestasi, seperseribu')->where('babak_id', $babak)->get()->getResultArray(), null, 'id');
        $this->assertSame(['1234', '1240', '1230'], [
            (string) $tersimpan[$id[$atlet[0]]]['prestasi'], (string) $tersimpan[$id[$atlet[1]]]['prestasi'], (string) $tersimpan[$id[$atlet[2]]]['prestasi'],
        ]);
        $this->assertNull($tersimpan[$id[$atlet[1]]]['seperseribu']);
        // Dibandingkan per 0,1 detik: 12,34 lama dan 12,4 baru berperingkat sama.
        $this->assertSame([$atlet[2] => 1, $atlet[0] => 2, $atlet[1] => 2], $this->peringkat($babak));

        $data = $hasil->nomor($nomor);
        $this->assertTrue($data['waktu_tangan']);
        $this->assertSame(['12,3', '12,4', '12,4'], array_column($hasil->hasilBabak($babak, $data), 'prestasi_teks'));

        $this->assertSame(1, $this->xpathDari($this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor))->query('//strong[@data-waktu-tangan]')->length);
        $this->bukaSebagaiAdmin('admin/juri/babak/' . $babak)->assertSee('dibulatkan ke atas ke 0,1 detik');

        $unduhan = new UnduhanHasil($data, $hasil->semuaBabak($data));
        $this->assertStringContainsString('Waktu tangan: dicatat dengan stopwatch', $unduhan->html(false));
        $lembar = $this->bacaExcel($this->bukaSebagaiAdmin('admin/hasil/nomor/' . $nomor . '/excel'))->getSheet(0)->toArray();
        $this->assertSame('Prestasi (Waktu tangan)', $lembar[0][4]);
        $this->assertSame('12,3', $lembar[1][4]);

        $hasil->terbitkan($nomor, true);
        $publik = $this->get('hasil/' . $nomor);
        $publik->assertSee('Waktu tangan: dicatat dengan stopwatch');
        $publik->assertSee('12,4');
        $publik->assertDontSee('12,34');

        // Nomor lain tetap elektronik dengan dua angka desimal.
        $this->assertSame('elektronik', $this->db->table('nomor_lomba')->where('id', $this->idInduk['frog'])->get()->getRow()->cara_waktu);
    }

    public function testHapusAtletDanGabungPendaftaranMembawaHasil(): void
    {
        $a = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4)]);
        $b = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4, ['regu' => ['kanga' => ['regu' => 'B', 'peran' => 'inti']]])]);
        $status = new StatusPembayaran();
        $status->tandaiLunas($a['id']);

        $hasil = new HasilLomba();
        $nomor = $hasil->nomor($this->idInduk['kanga']);
        $babak = $hasil->tambahBabak($nomor['id'], 'Final', true, 'semua')['id'];
        $this->db->table('hasil')->insert(['babak_id' => $babak, 'pendaftaran_id' => $b['id'], 'regu' => 'B', 'prestasi' => 4500]);

        // Pendaftaran kedua ber-NPSN sama ditandai Lunas: digabung ke yang pertama (K109).
        $status->tandaiLunas($b['id']);
        $this->assertSame([$a['id'], $a['id']], array_map('intval', array_column($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray(), 'pendaftaran_id')));
        $this->assertSame(0, $this->db->table('pendaftaran')->where('id', $b['id'])->countAllResults());

        // Pendaftaran dihapus: hasilnya ikut terhapus.
        $this->db->table('pendaftaran')->where('id', $a['id'])->delete();
        $this->assertSame(0, $this->db->table('hasil')->countAllResults());
    }

    public function testPaketPindahServerMembawaHasilLomba(): void
    {
        $atlet = $this->tigaAtletLunas();
        $hasil = new HasilLomba();
        $babak = $hasil->tambahBabak($this->idInduk['lari60putra'], 'Final', true, 'semua')['id'];
        $hasil->simpanHasil($babak, [$this->idHasil($babak)[$atlet[0]] => ['prestasi' => '9,87']]);
        $hasil->terbitkan($this->idInduk['lari60putra'], true);
        // Cara pencatatan waktu ikut paket (F11-04).
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putra'])->update(['cara_waktu' => 'tangan']);

        $zip = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);

            foreach (['hasil', 'babak', 'atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $tabel) {
                $this->db->table($tabel)->where('id >', 0)->delete();
            }
            $this->db->table('nomor_lomba')->update(['hasil_terbit_pada' => null, 'cara_waktu' => 'elektronik']);

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $baru = $this->db->table('hasil h')->select('h.prestasi, h.peringkat, a.nisn, b.nama, b.final')->join('atlet a', 'a.id = h.atlet_id')->join('babak b', 'b.id = h.babak_id')->where('h.prestasi IS NOT NULL', null, false)->get()->getResultArray();
        $this->assertCount(1, $baru);
        $this->assertSame(['987', '1', 'Final', '1'], [(string) $baru[0]['prestasi'], (string) $baru[0]['peringkat'], $baru[0]['nama'], (string) $baru[0]['final']]);
        $this->assertSame(3, $this->db->table('hasil')->countAllResults());
        $this->assertNotNull($this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putra'])->get()->getRow()->hasil_terbit_pada);
        $this->assertSame('tangan', $this->db->table('nomor_lomba')->where('id', $this->idInduk['lari60putra'])->get()->getRow()->cara_waktu);
    }

    /**
     * Tiga atlet Lari 60 m putra dari pendaftaran Lunas, satu lagi dari pendaftaran Menunggu.
     *
     * @return list<int> id atlet Lunas
     */
    private function tigaAtletLunas(): array
    {
        $p = $this->buatPendaftaranPanel(['atlet' => [$this->atletPanel(), $this->atletPanel(), $this->atletPanel()]]);
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN MENUNGGU']]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        return $p['atlet'];
    }

    /**
     * @return array<int, int> id atlet => id hasil
     */
    private function idHasil(int $babak): array
    {
        $id = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('atlet_id')->get()->getResultArray() as $h) {
            $id[(int) $h['atlet_id']] = (int) $h['id'];
        }

        return $id;
    }

    /**
     * @return array<int, int|null> id atlet => peringkat, urut peringkat
     */
    private function peringkat(int $babak): array
    {
        $hasil = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->orderBy('peringkat IS NULL', '', false)->orderBy('peringkat')->orderBy('atlet_id')->get()->getResultArray() as $h) {
            $hasil[(int) $h['atlet_id']] = $h['peringkat'] === null ? null : (int) $h['peringkat'];
        }

        return $hasil;
    }

    private function bacaExcel(TestResponse $hasil): Spreadsheet
    {
        $tanggapan = $hasil->response();
        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);

        ob_start();
        $tanggapan->sendBody();
        $isi = (string) ob_get_clean();

        $berkas = tempnam(sys_get_temp_dir(), 'milklife-hasil-') . '.xlsx';
        file_put_contents($berkas, $isi);

        try {
            return IOFactory::load($berkas);
        } finally {
            @unlink($berkas);
        }
    }
}
