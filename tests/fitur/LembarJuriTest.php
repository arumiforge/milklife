<?php

use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\UnduhanHasil;
use App\Libraries\Panel\PaketPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\TestResponse;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Lembar juri dan aturan nomor (F9-09, K140 butir 7c dan 7d): halaman
 * `admin/juri` hanya untuk admin, pilih babak dan seri, simpan per baris
 * (JSON dan tanpa skrip), percobaan lompat/lempar dengan pemecah seri
 * prestasi terbaik kedua, seperseribu detik lari, poin multi-lomba per
 * komponen, versi baris (simpanan admin lain ditolak), koreksi peringkat
 * Final tetap, halaman hasil tetap memakai percobaan, dan paket pindah server.
 *
 * @internal
 */
final class LembarJuriTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

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

    public function testHalamanHanyaUntukAdmin(): void
    {
        $babak = $this->babakLari(2);

        foreach (['admin/juri', 'admin/juri/babak/' . $babak['id']] as $alamat) {
            $this->assertBelumMasukDialihkan($alamat);
        }

        $this->assertBelumMasukDialihkan('admin/juri/babak/' . $babak['id'] . '/' . $babak['hasil'][0], 'post');
        $this->assertTokenCsrfWajib('admin/juri/babak/' . $babak['id'] . '/' . $babak['hasil'][0], ['prestasi' => '9,87']);
    }

    public function testDaftarBabakDanBarisPerSeri(): void
    {
        $babak = $this->babakLari(10);
        $this->db->table('babak')->where('id', $babak['id'])->update(['tanggal' => date('Y-m-d'), 'jam_mulai' => '08:30:00']);
        (new SeriLintasan())->bagiOtomatis($babak['id'], ['jumlah_seri' => 2]);

        $daftar = $this->bukaSebagaiAdmin('admin/juri');
        $daftar->assertOK();
        $x = $this->xpathDari($daftar);
        $this->assertSame(1, $x->query('//details[@data-hari-juri="' . date('Y-m-d') . '"][@open]//a[@data-babak-juri="' . $babak['id'] . '"]')->length);
        $this->assertStringContainsString('0/10 terisi', $daftar->getBody());

        $halaman = $this->bukaSebagaiAdmin('admin/juri/babak/' . $babak['id'] . '?seri=2');
        $halaman->assertOK();
        $x = $this->xpathDari($halaman);
        $this->assertSame(2, $x->query('//nav[@data-pilih-seri]/a')->length);
        $this->assertSame(1, $x->query('//nav[@data-pilih-seri]/a[@aria-current="page"][contains(., "Seri 2")]')->length);

        $diSeri2 = $this->db->table('hasil')->where('babak_id', $babak['id'])->where('seri', 2)->countAllResults();
        $this->assertSame($diSeri2, $x->query('//form[@data-baris-juri]')->length);
        $this->assertSame($diSeri2, $x->query('//form[@data-baris-juri]//*[@data-lintasan]')->length);
        $this->assertSame($diSeri2, $x->query('//form[@data-baris-juri]//*[@data-bib]')->length);
        $this->assertSame($diSeri2, $x->query('//form[@data-baris-juri]//input[@name="prestasi"][@inputmode="decimal"]')->length);
        $this->assertSame(1, $x->query('//script[contains(@src, "lembar-juri.js")]')->length);

        // Tanpa skrip sebaris (CSP, K127).
        $this->assertSame(0, $x->query('//script[not(@src)] | //*[@style]')->length);

        // Babak tidak ada: 404.
        $this->expectException(CodeIgniter\Exceptions\PageNotFoundException::class);
        $this->bukaSebagaiAdmin('admin/juri/babak/999999');
    }

    public function testSimpanWaktuPerBarisDenganSeperseribu(): void
    {
        $babak = $this->babakLari(3);
        [$a, $b, $c] = $babak['hasil'];

        $jawab = $this->simpanJuri($babak['id'], $a, ['prestasi' => '12,343', 'versi' => '0']);
        $this->assertSame(200, $jawab['kode']);
        $this->assertSame('tersimpan', $jawab['isi']['status']);
        $this->assertSame(['12,35', '12,343', 1], [$jawab['isi']['baris']['prestasi'], $jawab['isi']['baris']['isian'], $jawab['isi']['baris']['versi']]);
        $this->assertNotEmpty($jawab['isi']['csrf']);

        $this->simpanJuri($babak['id'], $b, ['prestasi' => '12,341', 'versi' => '0']);
        $this->simpanJuri($babak['id'], $c, ['prestasi' => '12,35', 'versi' => '0']);

        $baris = $this->baris($babak['id']);
        $this->assertSame([1235, 12343, 1], [(int) $baris[$a]['prestasi'], (int) $baris[$a]['seperseribu'], (int) $baris[$a]['versi']]);

        // b (12,341) di atas a (12,343); c tanpa seperseribu: a, b, c sama.
        $this->assertSame([1, 1, 1], [(int) $baris[$a]['peringkat'], (int) $baris[$b]['peringkat'], (int) $baris[$c]['peringkat']]);
        $this->simpanJuri($babak['id'], $c, ['prestasi' => '12,350', 'versi' => '1']);
        $baris = $this->baris($babak['id']);
        $this->assertSame([2, 1, 3], [(int) $baris[$a]['peringkat'], (int) $baris[$b]['peringkat'], (int) $baris[$c]['peringkat']]);

        // Isian salah: tidak tersimpan, keadaan di `status`.
        $salah = $this->simpanJuri($babak['id'], $a, ['prestasi' => '12 detik', 'versi' => '1']);
        $this->assertSame([200, 'galat'], [$salah['kode'], $salah['isi']['status']]);
        $this->assertStringContainsString('12,345', $salah['isi']['pesan']);
        $this->assertSame('1235', (string) $this->baris($babak['id'])[$a]['prestasi']);

        // Keterangan DNS menghapus peringkat.
        $this->simpanJuri($babak['id'], $a, ['prestasi' => '', 'keterangan' => 'DNS', 'versi' => '1']);
        $this->assertNull($this->baris($babak['id'])[$a]['peringkat']);

        // Peserta babak lain.
        $lain = $this->simpanJuri($babak['id'], 999999, ['prestasi' => '9,00']);
        $this->assertSame([200, 'hilang'], [$lain['kode'], $lain['isi']['status']]);
    }

    public function testVersiBarisMenolakSimpananAdminLain(): void
    {
        $babak = $this->babakLari(2);
        [$a] = $babak['hasil'];

        $this->assertSame('tersimpan', $this->simpanJuri($babak['id'], $a, ['prestasi' => '9,87', 'versi' => '0'])['isi']['status']);

        // Juri kedua membuka halaman sebelum simpanan pertama (versi 0).
        $bentrok = $this->simpanJuri($babak['id'], $a, ['prestasi' => '9,99', 'versi' => '0']);
        $this->assertSame([200, 'bentrok', HasilLomba::PESAN_BENTROK], [$bentrok['kode'], $bentrok['isi']['status'], $bentrok['isi']['pesan']]);
        $this->assertSame('987', (string) $this->baris($babak['id'])[$a]['prestasi']);

        // Isian sama dengan yang tersimpan tidak dianggap bentrok dan tidak menaikkan versi.
        $this->assertSame('tersimpan', $this->simpanJuri($babak['id'], $a, ['prestasi' => '9,87', 'versi' => '0'])['isi']['status']);
        $this->assertSame('1', (string) $this->baris($babak['id'])[$a]['versi']);

        // Halaman hasil yang dibuka sebelum lembar juri menyimpan juga ditolak.
        $hasil = $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['id'], ['hasil' => [$a => ['prestasi' => '10,00', 'keterangan' => '', 'peringkat' => '', 'versi' => '0']]]);
        $hasil->assertRedirectTo(site_url('admin/hasil/babak/' . $babak['id']));
        $hasil->assertSessionHas('galat_hasil', [$a => HasilLomba::PESAN_BENTROK]);
        $this->assertSame('987', (string) $this->baris($babak['id'])[$a]['prestasi']);
    }

    public function testKoreksiPeringkatFinalTetapSetelahDisimpanJuri(): void
    {
        $babak = $this->babakLari(2);
        [$a, $b] = $babak['hasil'];

        (new HasilLomba())->simpanHasil($babak['id'], [
            $a => ['prestasi' => '9,87', 'peringkat' => '2'],
            $b => ['prestasi' => '9,90', 'peringkat' => '1'],
        ]);

        $this->simpanJuri($babak['id'], $a, ['prestasi' => '9,80', 'versi' => '1']);
        $baris = $this->baris($babak['id']);
        $this->assertSame(['980', '2', '1'], [(string) $baris[$a]['prestasi'], (string) $baris[$a]['peringkat'], (string) $baris[$a]['peringkat_manual']]);
    }

    public function testPercobaanLompatLemparDanPemecahSeri(): void
    {
        $babak = $this->babakJarak(4);
        [$a, $b, $c, $d] = $babak['hasil'];

        $halaman = $this->bukaSebagaiAdmin('admin/juri/babak/' . $babak['id']);
        $x       = $this->xpathDari($halaman);
        $this->assertSame(3, $x->query('//form[@data-baris-juri="' . $a . '"]//input[@name="percobaan[]"][@inputmode="decimal"]')->length);
        $this->assertSame(3, $x->query('//form[@data-baris-juri="' . $a . '"]//button[@data-tombol-gagal]')->length);

        $jawab = $this->simpanJuri($babak['id'], $a, ['percobaan' => ['5,00', '4,80', 'X'], 'versi' => '0']);
        $this->assertSame('tersimpan', $jawab['isi']['status']);
        $this->assertSame(['5,00', '4,80', 'X'], $jawab['isi']['baris']['percobaan']);
        $this->assertSame('Terbaik 5,00', $jawab['isi']['baris']['ringkasan']);

        $this->simpanJuri($babak['id'], $b, ['percobaan' => ['4,90', '5,00', ''], 'versi' => '0']);
        $this->simpanJuri($babak['id'], $c, ['percobaan' => ['x', '5,00', '-'], 'versi' => '0']);
        $this->simpanJuri($babak['id'], $d, ['percobaan' => ['X', 'X', 'X'], 'versi' => '0']);

        $baris = $this->baris($babak['id']);
        $this->assertSame('[500,480,"X"]', $baris[$a]['percobaan']);
        $this->assertSame(['500', '500', '500', null], [$baris[$a]['prestasi'], $baris[$b]['prestasi'], $baris[$c]['prestasi'], $baris[$d]['prestasi']]);

        // Prestasi sama dipisah terbaik kedua: b (4,90), a (4,80), c (tanpa jarak sah kedua).
        $this->assertSame([2, 1, 3, null], $this->peringkat($babak['id'], [$a, $b, $c, $d]));

        // Isian percobaan salah.
        $salah = $this->simpanJuri($babak['id'], $a, ['percobaan' => ['5,00', '4 m'], 'versi' => '1']);
        $this->assertSame('galat', $salah['isi']['status']);
        $this->assertStringContainsString('Percobaan 2', $salah['isi']['pesan']);

        // Pemecah seri dimatikan di Pengaturan > Nomor lomba: ketiganya sama.
        $this->kirimSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $babak['nomor'], $this->isianNomor($babak['nomor'], ['jumlah_percobaan' => '4', 'pemecah_seri' => '0']));
        $n = $this->db->table('nomor_lomba')->where('id', $babak['nomor'])->get()->getRow();
        $this->assertSame(['4', '0'], [(string) $n->jumlah_percobaan, (string) $n->pemecah_seri]);
        (new HasilLomba())->simpanHasil($babak['id'], []);
        $this->assertSame([1, 1, 1, null], $this->peringkat($babak['id'], [$a, $b, $c, $d]));

        // Formulir pengaturan menampilkan isian percobaan; lembar juri 4 kotak.
        $formulir = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan/nomor-lomba/' . $babak['nomor'] . '/ubah'));
        $this->assertSame(1, $formulir->query('//fieldset[@id="bagian-percobaan"][not(@hidden)]//input[@name="jumlah_percobaan"][@value="4"]')->length);
        $this->assertSame(1, $formulir->query('//input[@type="checkbox"][@name="pemecah_seri"][not(@checked)]')->length);
        $x = $this->xpathDari($this->bukaSebagaiAdmin('admin/juri/babak/' . $babak['id']));
        $this->assertSame(4, $x->query('//form[@data-baris-juri="' . $d . '"]//input[@name="percobaan[]"]')->length);
    }

    public function testKodeNmMundurDanPasalDiskualifikasi(): void
    {
        $babak = $this->babakJarak(4);
        [$a, $b, $c, $d] = $babak['hasil'];

        $x = $this->xpathDari($this->bukaSebagaiAdmin('admin/juri/babak/' . $babak['id']));
        $this->assertSame(1, $x->query('//form[@data-baris-juri="' . $a . '"]//select[@name="keterangan"]/option[@value="NM"]')->length);
        $this->assertSame(1, $x->query('//form[@data-baris-juri="' . $a . '"]//select[@name="keterangan"]/option[@value="r"]')->length);
        $this->assertSame(1, $x->query('//form[@data-baris-juri="' . $a . '"]//input[@name="pasal"][@maxlength="12"]')->length);

        $this->simpanJuri($babak['id'], $a, ['percobaan' => ['5,00', 'X', 'X'], 'keterangan' => '', 'versi' => '0']);

        // r: prestasi sebelum mundur tetap dinilai.
        $mundur = $this->simpanJuri($babak['id'], $b, ['percobaan' => ['5,20', '', ''], 'keterangan' => 'r', 'versi' => '0']);
        $this->assertSame(['r', 'Terbaik 5,20, mundur'], [$mundur['isi']['baris']['keterangan'], $mundur['isi']['baris']['ringkasan']]);

        // Semua percobaan X: NM terisi sendiri.
        $nm = $this->simpanJuri($babak['id'], $c, ['percobaan' => ['X', 'X', 'X'], 'keterangan' => '', 'versi' => '0']);
        $this->assertSame(['NM', 'Tanpa lompatan atau lemparan sah'], [$nm['isi']['baris']['keterangan'], $nm['isi']['baris']['ringkasan']]);

        // DQ dengan pasal.
        $dq = $this->simpanJuri($babak['id'], $d, ['percobaan' => ['5,50', 'X', 'X'], 'keterangan' => 'DQ', 'pasal' => ' TR16.8 ', 'versi' => '0']);
        $this->assertSame(['DQ', 'TR16.8', 'Diskualifikasi (TR16.8)'], [$dq['isi']['baris']['keterangan'], $dq['isi']['baris']['pasal'], $dq['isi']['baris']['ringkasan']]);

        $baris = $this->baris($babak['id']);
        $this->assertSame(['NM', 'TR16.8', null], [$baris[$c]['keterangan'], $baris[$d]['pasal'], $baris[$b]['pasal']]);
        $this->assertSame([2, 1, null, null], $this->peringkat($babak['id'], [$a, $b, $c, $d]));

        // Juara: r boleh juara, NM dan DQ tidak.
        $juara = array_column(array_filter((new HasilLomba())->juara(false, 8), static fn (array $j): bool => $j['nomor_id'] === $babak['nomor']), 'peringkat');
        $this->assertSame([1, 2], $juara);

        // Percobaan sah ditambahkan: NM dibuang walau masih terkirim.
        $this->simpanJuri($babak['id'], $c, ['percobaan' => ['X', 'X', '4,10'], 'keterangan' => 'NM', 'versi' => '1']);
        $this->assertNull($this->baris($babak['id'])[$c]['keterangan']);
        $this->assertSame([2, 1, 3, null], $this->peringkat($babak['id'], [$a, $b, $c, $d]));

        // Pasal salah, NM dengan prestasi di halaman hasil, dan NM di nomor lari ditolak.
        $salah = $this->simpanJuri($babak['id'], $d, ['percobaan' => ['5,50', 'X', 'X'], 'keterangan' => 'DQ', 'pasal' => 'TR16.8 dan TR17.3', 'versi' => '1']);
        $this->assertSame('galat', $salah['isi']['status']);
        $this->assertStringContainsString('misalnya TR16.8', $salah['isi']['pesan']);
        $galat = (new HasilLomba())->simpanHasil($babak['id'], [$a => ['prestasi' => '5,00', 'keterangan' => 'NM']]);
        $this->assertStringContainsString('NM berarti tidak ada lompatan atau lemparan sah', $galat[$a]);
        $lari = $this->babakLari(1, 'Final', $this->idInduk['lari60putri'], 'P');
        $x    = $this->xpathDari($this->bukaSebagaiAdmin('admin/juri/babak/' . $lari['id']));
        $this->assertSame(0, $x->query('//select[@name="keterangan"]/option[@value="NM" or @value="r"]')->length);
        $nmLari = $this->simpanJuri($lari['id'], $lari['hasil'][0], ['prestasi' => '', 'keterangan' => 'NM', 'versi' => '0']);
        $this->assertStringContainsString('hanya untuk nomor lompat dan lempar', $nmLari['isi']['pesan']);

        // Bukan DQ: pasal tidak disimpan.
        $this->simpanJuri($babak['id'], $d, ['percobaan' => ['5,50', 'X', 'X'], 'keterangan' => '', 'pasal' => 'TR16.8', 'versi' => '1']);
        $this->assertNull($this->baris($babak['id'])[$d]['pasal']);
        $this->simpanJuri($babak['id'], $d, ['percobaan' => ['5,50', 'X', 'X'], 'keterangan' => 'DQ', 'pasal' => 'TR16.8', 'versi' => '2']);

        // Halaman hasil admin: isian pasal; simpan ulang tanpa pasal mengosongkannya.
        $halaman = $this->xpathDari($this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak['id']));
        $this->assertSame(1, $halaman->query('//input[@name="hasil[' . $d . '][pasal]"][@value="TR16.8"]')->length);

        // Hasil publik, PDF, dan Excel.
        $hasil = new HasilLomba();
        $hasil->terbitkan($babak['nomor'], true);
        $publik = $this->get('hasil/' . $babak['nomor']);
        $publik->assertOK();
        $this->assertStringContainsString('DQ (TR16.8)', $publik->getBody());
        $this->assertStringContainsString('r = mundur', $publik->getBody());

        $nomor   = $hasil->nomor($babak['nomor']);
        $unduhan = new UnduhanHasil($nomor, $hasil->semuaBabak($nomor));
        $html    = $unduhan->html(false);
        $this->assertStringContainsString('<td>DQ (TR16.8)</td>', $html);
        $this->assertStringContainsString('<td>r</td>', $html);
        $this->assertStringContainsString('NM = tanpa lompatan atau lemparan sah', $html);
        $ket = array_column($unduhan->excel()->lembar()->getSheet(0)->toArray(), 5);
        $this->assertContains('DQ (Diskualifikasi, TR16.8)', $ket);
        $this->assertContains('r (Mundur)', $ket);
    }

    public function testHalamanHasilMempertahankanPercobaan(): void
    {
        $babak = $this->babakJarak(2);
        [$a, $b] = $babak['hasil'];

        $this->simpanJuri($babak['id'], $a, ['percobaan' => ['5,00', '4,80', 'X'], 'versi' => '0']);
        $this->simpanJuri($babak['id'], $b, ['percobaan' => ['5,00', '4,90', 'X'], 'versi' => '0']);

        $halaman = $this->bukaSebagaiAdmin('admin/hasil/babak/' . $babak['id']);
        $this->assertStringContainsString('Percobaan: 5,00 / 4,80 / X', $halaman->getBody());
        $this->assertSame(1, $this->xpathDari($halaman)->query('//a[@id="tautan-lembar-juri"]')->length);

        // Prestasi tidak diubah: percobaan dan pemecah seri tetap.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['id'], ['hasil' => [
            $a => ['prestasi' => '5,00', 'keterangan' => '', 'peringkat' => '', 'versi' => '1'],
            $b => ['prestasi' => '5,00', 'keterangan' => '', 'peringkat' => '', 'versi' => '1'],
        ]]);
        $this->assertSame([2, 1], $this->peringkat($babak['id'], [$a, $b]));
        $this->assertNotNull($this->baris($babak['id'])[$a]['percobaan']);

        // Prestasi diganti di halaman hasil: percobaan baris itu dilepas.
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak['id'], ['hasil' => [
            $a => ['prestasi' => '5,10', 'keterangan' => '', 'peringkat' => '', 'versi' => '1'],
        ]]);
        $baris = $this->baris($babak['id']);
        $this->assertSame(['510', null], [(string) $baris[$a]['prestasi'], $baris[$a]['percobaan']]);
        $this->assertSame([1, 2], $this->peringkat($babak['id'], [$a, $b]));
    }

    public function testPoinMultiLombaPerKomponen(): void
    {
        (new DataTabelSkor())->pasangBawaan();
        $nomor = $this->idInduk['lari60putraKu12'];
        $tabel = (int) $this->db->table('tabel_skor')->where('kode', 'trilomba-o2sn-smp')->get()->getRow()->id;
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['nama' => 'Trilomba', 'jenis_hasil' => 'multi', 'tabel_skor_id' => $tabel, 'komponen_skor' => null]);

        $atlet = [];

        for ($i = 0; $i < 2; $i++) {
            $atlet[] = $this->atletPanel(['ku' => 'ku12', 'tanggal_lahir' => '2015-03-0' . ($i + 1), 'nomor' => ['lari60putraKu12']]);
        }

        $p = $this->buatPendaftaranPanel(['atlet' => $atlet]);
        (new StatusPembayaran())->tandaiLunas($p['id']);
        $babak = (new HasilLomba())->tambahBabak($nomor, 'Final', true, 'semua')['id'];
        $id    = array_map('intval', array_column($this->db->table('hasil')->where('babak_id', $babak)->orderBy('id')->get()->getResultArray(), 'id'));

        $x = $this->xpathDari($this->bukaSebagaiAdmin('admin/juri/babak/' . $babak));
        $this->assertSame(3, $x->query('//form[@data-baris-juri="' . $id[0] . '"]//input[starts-with(@name, "komponen[")][@inputmode="decimal"]')->length);

        $jawab = $this->simpanJuri($babak, $id[0], ['komponen' => ['lari-60' => '7,00'], 'versi' => '0']);
        $this->assertSame('tersimpan', $jawab['isi']['status']);
        $this->assertSame('882', $jawab['isi']['baris']['prestasi']);
        $this->assertSame('Total 882 poin', $jawab['isi']['baris']['ringkasan']);
        $this->assertSame(882, $jawab['isi']['baris']['rincian'][0]['poin']);

        // Sama dengan isian di halaman hasil babak (kunci `komponen` yang sama).
        $this->kirimSebagaiAdmin('admin/hasil/babak/' . $babak, ['hasil' => [$id[1] => ['komponen' => ['lari-60' => '7,00'], 'keterangan' => '', 'peringkat' => '']]]);
        $baris = $this->baris($babak);
        $this->assertSame([$baris[$id[0]]['prestasi'], $baris[$id[0]]['rincian']], [$baris[$id[1]]['prestasi'], $baris[$id[1]]['rincian']]);
    }

    public function testTanpaSkripDialihkanKeBaris(): void
    {
        $babak = $this->babakLari(1);
        [$a] = $babak['hasil'];

        $hasil = $this->kirimSebagaiAdmin('admin/juri/babak/' . $babak['id'] . '/' . $a, ['prestasi' => '9,87', 'versi' => '0', 'seri' => '0']);
        $this->assertBerhasilDialihkan($hasil, 'admin/juri/babak/' . $babak['id'] . '?seri=0#baris-' . $a, 'Prestasi tersimpan.');

        $hasil = $this->kirimSebagaiAdmin('admin/juri/babak/' . $babak['id'] . '/' . $a, ['prestasi' => 'salah', 'versi' => '1']);
        $this->assertGagalDialihkan($hasil, 'admin/juri/babak/' . $babak['id'] . '#baris-' . $a);
    }

    public function testPaketPindahServerMembawaPercobaanDanAturan(): void
    {
        $babak = $this->babakJarak(2);
        [$a] = $babak['hasil'];
        $this->simpanJuri($babak['id'], $a, ['percobaan' => ['5,00', '4,80', 'X'], 'versi' => '0']);
        // Pasal DQ (F11-05).
        $this->simpanJuri($babak['id'], $babak['hasil'][1], ['percobaan' => ['4,00', 'X', 'X'], 'keterangan' => 'DQ', 'pasal' => 'TR16.8', 'versi' => '0']);
        $this->db->table('nomor_lomba')->where('id', $babak['nomor'])->update(['jumlah_percobaan' => 4, 'pemecah_seri' => 0]);

        $lari = $this->babakLari(1, 'Final', $this->idInduk['lari60putri'], 'P');
        $this->simpanJuri($lari['id'], $lari['hasil'][0], ['prestasi' => '10,123', 'versi' => '0']);

        $zip = tempnam(sys_get_temp_dir(), 'milklife-paket-') . '.zip';

        try {
            (new PaketPendaftaran())->ekspor($zip);

            foreach (['hasil', 'babak', 'atlet_nomor', 'atlet', 'penanggung_jawab', 'pendaftaran'] as $t) {
                $this->db->table($t)->where('id >', 0)->delete();
            }
            $this->db->table('nomor_lomba')->update(['jumlah_percobaan' => null, 'pemecah_seri' => 1]);

            (new PaketPendaftaran())->impor($zip);
        } finally {
            @unlink($zip);
        }

        $n = $this->db->table('nomor_lomba')->where('id', $babak['nomor'])->get()->getRow();
        $this->assertSame(['4', '0'], [(string) $n->jumlah_percobaan, (string) $n->pemecah_seri]);
        $this->assertSame('[500,480,"X"]', $this->db->table('hasil')->where('percobaan IS NOT NULL', null, false)->get()->getRow()->percobaan);
        $this->assertSame('10123', (string) $this->db->table('hasil')->where('seperseribu IS NOT NULL', null, false)->get()->getRow()->seperseribu);
        $this->assertSame(['DQ', 'TR16.8'], array_values((array) $this->db->table('hasil')->select('keterangan, pasal')->where('pasal IS NOT NULL', null, false)->get()->getRowArray()));
    }

    // ---------- Pembantu ----------

    /**
     * Kirim satu baris lembar juri seperti skrip `lembar-juri.js`.
     *
     * @param array<string, mixed> $isian
     *
     * @return array{kode: int, isi: array<string, mixed>}
     */
    private function simpanJuri(int $babak, int $hasil, array $isian): array
    {
        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json']);
        $jawaban = $this->kirimSebagaiAdmin('admin/juri/babak/' . $babak . '/' . $hasil, $isian);
        $this->withHeaders([]);

        return ['kode' => $jawaban->response()->getStatusCode(), 'isi' => (array) json_decode((string) $jawaban->getJSON(), true)];
    }

    /**
     * Babak waktu dengan `$jumlah` atlet Lunas (urut id hasil).
     *
     * @return array{id: int, nomor: int, hasil: list<int>}
     */
    private function babakLari(int $jumlah, string $nama = 'Final', ?int $nomor = null, string $kelamin = 'L'): array
    {
        $nomor ??= $this->idInduk['lari60putra'];
        $kunci   = array_search($nomor, $this->idInduk, true);
        $atlet   = array_map(fn (): array => $this->atletPanel(['nomor' => [$kunci], 'jenis_kelamin' => $kelamin]), range(1, $jumlah));
        $p       = $this->buatPendaftaranPanel(['atlet' => $atlet]);
        (new StatusPembayaran())->tandaiLunas($p['id']);

        $id = (new HasilLomba())->tambahBabak($nomor, $nama, true, 'semua')['id'];

        return ['id' => $id, 'nomor' => $nomor, 'hasil' => $this->idHasil($id)];
    }

    /**
     * Babak Turbo Throw (jenis hasil jarak) dengan `$jumlah` atlet Lunas.
     *
     * @return array{id: int, nomor: int, hasil: list<int>}
     */
    private function babakJarak(int $jumlah): array
    {
        $nomor = $this->idInduk['turbo'];
        $this->db->table('nomor_lomba')->where('id', $nomor)->update(['jenis_hasil' => 'jarak']);

        return $this->babakLari($jumlah, 'Final', $nomor);
    }

    /**
     * @return list<int>
     */
    private function idHasil(int $babak): array
    {
        return array_map('intval', array_column($this->db->table('hasil')->where('babak_id', $babak)->orderBy('id')->get()->getResultArray(), 'id'));
    }

    /**
     * @return array<int, array<string, mixed>> id hasil => baris
     */
    private function baris(int $babak): array
    {
        $baris = [];

        foreach ($this->db->table('hasil')->where('babak_id', $babak)->get()->getResultArray() as $h) {
            $baris[(int) $h['id']] = $h;
        }

        return $baris;
    }

    /**
     * @param list<int> $id
     *
     * @return list<int|null>
     */
    private function peringkat(int $babak, array $id): array
    {
        $baris = $this->baris($babak);

        return array_map(static fn (int $i): ?int => $baris[$i]['peringkat'] === null ? null : (int) $baris[$i]['peringkat'], $id);
    }

    /**
     * Isian formulir Pengaturan > Nomor lomba untuk nomor jarak.
     *
     * @param array<string, string> $ubah
     *
     * @return array<string, string>
     */
    private function isianNomor(int $nomor, array $ubah = []): array
    {
        $n = $this->db->table('nomor_lomba')->where('id', $nomor)->get()->getRowArray();

        return $ubah + [
            'kelompok_umur_id' => (string) $n['kelompok_umur_id'],
            'nama'             => (string) $n['nama'],
            'jenis'            => 'individu',
            'kategori'         => (string) $n['kategori'],
            'jenis_hasil'      => 'jarak',
            'urutan'           => (string) $n['urutan'],
            'aktif'            => '1',
        ];
    }
}
