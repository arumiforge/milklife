<?php

use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Bagian lomba halaman depan (F3-02): kelompok umur aktif dengan tahun lahir,
 * jenjang, dan biaya individu; nomor individu dan beregu; nomor dan kelompok
 * umur mati tidak tampil; teks admin di-escape. Nama dan isi karangan.
 *
 * @internal
 */
final class HalamanDepanLombaTest extends UjiBasisData
{
    use KirimFormulir;

    // ---------- Bagian kosong ----------

    public function testBagianTidakTampilTanpaKelompokUmurAktif(): void
    {
        $xpath = $this->xpathDari($this->get('/'));
        $this->assertSame(0, $xpath->query('//section[@id="lomba"]')->length, 'Belum ada kelompok umur.');

        $ku = $this->tambahKu(['nama' => 'KU Karangan', 'aktif' => 0]);
        $this->tambahNomor($ku, ['nama' => 'Lari Karangan']);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);
        $this->assertSame(0, $xpath->query('//section[@id="lomba"]')->length, 'Kelompok umur mati saja.');
        $hasil->assertDontSee('Lari Karangan');
    }

    // ---------- Data awal ----------

    public function testDataAwalLimaKelompokUmurDan69Nomor(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));

        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $bagian = $xpath->query('//section[@id="lomba"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame('judul-lomba', $bagian->getAttribute('aria-labelledby'));
        $this->assertSame(1, $xpath->query('//section[@id="lomba"]//h2[@id="judul-lomba"]')->length);
        $this->assertSame(1, $xpath->query('//h1')->length, 'Bagian lomba tidak menambah h1.');
        $this->assertSame(0, $xpath->query('//section[@id="lomba"]//table')->length, 'Tanpa tabel lebar.');

        $this->assertSame(
            ['KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'],
            $this->daftarTeks($xpath, '//article[contains(@class, "lomba-ku")]/h3'),
        );
        $this->assertSame(
            ['Lahir 2019 ke atas', 'Lahir 2016–2018', 'Lahir 2015', 'Lahir 2011–2014', 'Lahir 2008–2010'],
            $this->daftarTeks($xpath, '//*[contains(@class, "lomba-tahun-lahir")]'),
        );

        // Jenjang dan biaya individu masih kosong setelah data awal.
        $this->assertSame(array_fill(0, 5, 'Jenjang sekolah segera diumumkan'), $this->daftarTeks($xpath, '//*[contains(@class, "lomba-jenjang")]'));
        $this->assertSame(array_fill(0, 5, 'Biaya segera diumumkan'), $this->daftarTeks($xpath, '//*[contains(@class, "lomba-biaya-individu")]'));

        // Ringkasan jumlah per kelompok umur; totalnya 69 nomor.
        $jumlah = $this->daftarTeks($xpath, '//*[contains(@class, "lomba-nomor-jumlah")]');
        $this->assertSame(['2 individu', '6 individu · 5 beregu', '10 individu · 1 beregu', '20 individu · 2 beregu', '20 individu · 3 beregu'], $jumlah);
        preg_match_all('/\d+/', implode(' ', $jumlah), $angka);
        $this->assertSame(69, array_sum(array_map(intval(...), $angka[0])));

        // Putra dan putri dengan nama yang sama digabung menjadi satu baris.
        $ku10 = '//article[contains(@class, "lomba-ku")][2]';
        $this->assertSame(
            ['Lari 60 m — Putra, Putri', 'Frog Jump — Putra, Putri', 'Turbo Throw — Putra, Putri'],
            $this->daftarTeks($xpath, $ku10 . '//ul[contains(@class, "lomba-daftar-individu")]/li'),
        );
        $this->assertSame(
            ["Kanga's Escape — Putra, Putri", 'Formula 1 — Putra, Putri', 'Estafet 8 × 50 m — Mix'],
            $this->daftarTeks($xpath, $ku10 . '//*[contains(@class, "lomba-beregu-kepala")]'),
        );
        $this->assertSame(
            ['Rp100.000 per tim', 'Rp100.000 per tim', 'Rp150.000 per tim'],
            $this->daftarTeks($xpath, $ku10 . '//*[contains(@class, "lomba-biaya-beregu")]'),
        );
        $this->assertSame(['4 atlet per tim', '4 atlet per tim', '8 atlet per tim'], $this->daftarTeks($xpath, $ku10 . '//ul[contains(@class, "lomba-beregu-info")]/li[1]'));

        // KU 8 tanpa nomor beregu: tanpa judul "Nomor beregu".
        $this->assertSame(0, $xpath->query('//article[contains(@class, "lomba-ku")][1]//ul[contains(@class, "lomba-daftar-beregu")]')->length);

        // Daftar nomor bisa dilipat dan tertutup saat halaman dibuka.
        $this->assertSame(5, $xpath->query('//article[contains(@class, "lomba-ku")]/details[contains(@class, "lomba-nomor")]/summary')->length);
        $this->assertSame(0, $xpath->query('//details[@open]')->length);
    }

    // ---------- Data tersembunyi ----------

    public function testKelompokUmurDanNomorMatiTidakTampil(): void
    {
        $hidup = $this->tambahKu(['nama' => 'KU Hidup', 'urutan' => 1]);
        $mati  = $this->tambahKu(['nama' => 'KU Tidur', 'urutan' => 2, 'aktif' => 0]);

        $this->tambahNomor($hidup, ['nama' => 'Lari Kancil', 'kategori' => 'putra']);
        $this->tambahNomor($hidup, ['nama' => 'Lari Kancil', 'kategori' => 'putri', 'aktif' => 0]);
        $this->tambahNomor($hidup, ['nama' => 'Lompat Katak', 'kategori' => 'putra', 'aktif' => 0]);
        $this->tambahNomor($hidup, ['nama' => 'Estafet Bangau', 'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => 4, 'aktif' => 0]);
        $this->tambahNomor($mati, ['nama' => 'Lari Tupai', 'kategori' => 'putra']);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(['KU Hidup'], $this->daftarTeks($xpath, '//article[contains(@class, "lomba-ku")]/h3'));
        $this->assertSame(['Lari Kancil — Putra'], $this->daftarTeks($xpath, '//ul[contains(@class, "lomba-daftar-individu")]/li'));
        $this->assertSame(['1 individu'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-nomor-jumlah")]'));
        $this->assertSame(0, $xpath->query('//ul[contains(@class, "lomba-daftar-beregu")]')->length);

        foreach (['KU Tidur', 'Lari Tupai', 'Lompat Katak', 'Estafet Bangau'] as $teks) {
            $hasil->assertDontSee($teks);
        }
    }

    public function testKelompokUmurTanpaNomorAktif(): void
    {
        $ku = $this->tambahKu(['nama' => 'KU Sepi']);
        $this->tambahNomor($ku, ['nama' => 'Lari Senyap', 'aktif' => 0]);

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(['KU Sepi'], $this->daftarTeks($xpath, '//article[contains(@class, "lomba-ku")]/h3'));
        $this->assertSame('Nomor lomba segera diumumkan.', $this->teks($xpath, '//*[contains(@class, "lomba-kosong")]'));
        $this->assertSame(0, $xpath->query('//details')->length);
    }

    // ---------- Tahun lahir, jenjang, dan biaya ----------

    public function testTahunLahirJenjangDanBiayaIndividu(): void
    {
        $belum  = $this->tambahKu(['nama' => 'KU Belum', 'urutan' => 1, 'tahun_lahir_awal' => null, 'tahun_lahir_akhir' => null, 'biaya_individu' => null]);
        $gratis = $this->tambahKu(['nama' => 'KU Gratis', 'urutan' => 2, 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'biaya_individu' => 0]);
        $bayar  = $this->tambahKu(['nama' => 'KU Bayar', 'urutan' => 3, 'tahun_lahir_awal' => 2011, 'tahun_lahir_akhir' => 2014, 'biaya_individu' => 150000]);

        $this->tambahJenjang($gratis, ['A']);
        $this->tambahJenjang($bayar, ['B', 'C']);

        foreach ([$belum, $gratis, $bayar] as $ku) {
            $this->tambahNomor($ku, ['nama' => 'Lari Karangan']);
        }

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(['Tahun lahir segera diumumkan', 'Lahir 2016–2018', 'Lahir 2011–2014'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-tahun-lahir")]'));
        $this->assertSame(['Jenjang sekolah segera diumumkan', 'SD/MI', 'SMP/MTs, SMA/SMK/MA'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-jenjang")]'));
        $this->assertSame(['Biaya segera diumumkan', 'Gratis', 'Rp150.000 per atlet per nomor'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-biaya-individu")]'));
    }

    // ---------- Nomor beregu ----------

    public function testNomorBereguDanMix(): void
    {
        $ku = $this->tambahKu(['nama' => 'KU Regu']);

        // Putra dan putri dengan aturan sama digabung; mix dengan jumlah putra/putri.
        $this->tambahNomor($ku, ['nama' => 'Estafet Rusa', 'jenis' => 'beregu', 'kategori' => 'putra', 'jumlah_inti' => 4, 'batas_anggota' => 6, 'biaya_beregu' => 100000]);
        $this->tambahNomor($ku, ['nama' => 'Estafet Rusa', 'jenis' => 'beregu', 'kategori' => 'putri', 'jumlah_inti' => 4, 'batas_anggota' => 6, 'biaya_beregu' => 100000]);
        $this->tambahNomor($ku, ['nama' => 'Estafet Campur', 'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => 8, 'batas_anggota' => 10, 'jumlah_putra' => 4, 'jumlah_putri' => 4, 'biaya_beregu' => 150000]);
        // Biaya berbeda: tetap dua baris.
        $this->tambahNomor($ku, ['nama' => 'Tarik Tambang', 'jenis' => 'beregu', 'kategori' => 'putra', 'jumlah_inti' => 5, 'biaya_beregu' => 0]);
        $this->tambahNomor($ku, ['nama' => 'Tarik Tambang', 'jenis' => 'beregu', 'kategori' => 'putri', 'jumlah_inti' => 5, 'batas_anggota' => 5]);
        // Belum diisi sama sekali.
        $this->tambahNomor($ku, ['nama' => 'Estafet Kosong', 'jenis' => 'beregu', 'kategori' => 'mix']);

        $xpath = $this->xpathDari($this->get('/'));
        $baris = $xpath->query('//ul[contains(@class, "lomba-daftar-beregu")]/li');

        $this->assertSame(5, $baris->length);
        $this->assertSame(['5 beregu'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-nomor-jumlah")]'), 'Jumlah menghitung nomor, bukan baris.');
        $this->assertSame(0, $xpath->query('//ul[contains(@class, "lomba-daftar-individu")]')->length);

        $this->assertSame([
            ['Estafet Rusa — Putra, Putri', '4 atlet inti, paling banyak 6 anggota', 'Rp100.000 per tim'],
            ['Estafet Campur — Mix', '8 atlet inti, paling banyak 10 anggota', 'Minimal 4 putra dan 4 putri', 'Rp150.000 per tim'],
            ['Tarik Tambang — Putra', '5 atlet per tim', 'Gratis'],
            ['Tarik Tambang — Putri', '5 atlet per tim', 'Biaya tim segera diumumkan'],
            ['Estafet Kosong — Mix', 'Biaya tim segera diumumkan'],
        ], array_map(fn (DOMElement $li): array => [
            $this->teksSimpul($xpath->query('.//*[contains(@class, "lomba-beregu-kepala")]', $li)->item(0)),
            ...array_map($this->teksSimpul(...), iterator_to_array($xpath->query('.//ul[contains(@class, "lomba-beregu-info")]/li', $li))),
        ], iterator_to_array($baris)));
    }

    public function testMixDenganSatuJumlahSaja(): void
    {
        $ku = $this->tambahKu(['nama' => 'KU Mix']);
        $this->tambahNomor($ku, ['nama' => 'Estafet Putra Saja', 'jenis' => 'beregu', 'kategori' => 'mix', 'batas_anggota' => 6, 'jumlah_putra' => 2, 'biaya_beregu' => 50000]);

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(
            ['Paling banyak 6 anggota per tim', 'Minimal 2 putra', 'Rp50.000 per tim'],
            $this->daftarTeks($xpath, '//ul[contains(@class, "lomba-beregu-info")]/li'),
        );
    }

    // ---------- Escape ----------

    public function testTeksAdminDiEscape(): void
    {
        $ku = $this->tambahKu(['nama' => 'KU <b>Tebal</b>']);
        $this->tambahNomor($ku, ['nama' => 'Lari <script>alert(1)</script>']);
        $this->tambahNomor($ku, ['nama' => 'Estafet <i>Miring</i> & "Kutip"', 'jenis' => 'beregu', 'kategori' => 'mix', 'jumlah_inti' => 4]);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(['KU <b>Tebal</b>'], $this->daftarTeks($xpath, '//article[contains(@class, "lomba-ku")]/h3'));
        $this->assertSame(['Lari <script>alert(1)</script> — Putra'], $this->daftarTeks($xpath, '//ul[contains(@class, "lomba-daftar-individu")]/li'));
        $this->assertSame(['Estafet <i>Miring</i> & "Kutip" — Mix'], $this->daftarTeks($xpath, '//*[contains(@class, "lomba-beregu-kepala")]'));

        $hasil->assertDontSee('<b>Tebal</b>');
        $hasil->assertDontSee('<script>alert(1)</script>');
        $hasil->assertDontSee('<i>Miring</i>');
        $this->assertSame(0, $xpath->query('//section[@id="lomba"]//b | //section[@id="lomba"]//script | //section[@id="lomba"]//i[not(contains(@class, "bi"))]')->length);
    }

    // ---------- Bantuan ----------

    /**
     * @param array<string, mixed> $isian
     */
    private function tambahKu(array $isian): int
    {
        $this->db->table('kelompok_umur')->insert($isian + [
            'tahun_lahir_awal'  => 2016,
            'tahun_lahir_akhir' => 2018,
            'biaya_individu'    => null,
            'urutan'            => 1,
            'aktif'             => 1,
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * @param array<string, mixed> $isian
     */
    private function tambahNomor(int $kelompokUmur, array $isian): void
    {
        static $urutan = 0;

        $this->db->table('nomor_lomba')->insert($isian + [
            'kelompok_umur_id' => $kelompokUmur,
            'jenis'            => 'individu',
            'kategori'         => 'putra',
            'urutan'           => ++$urutan,
            'aktif'            => 1,
        ]);
    }

    /**
     * @param list<string> $jenjang
     */
    private function tambahJenjang(int $kelompokUmur, array $jenjang): void
    {
        foreach ($jenjang as $kode) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $kelompokUmur, 'jenjang' => $kode]);
        }
    }

    /**
     * @return list<string>
     */
    private function daftarTeks(DOMXPath $xpath, string $kueri): array
    {
        return array_map($this->teksSimpul(...), iterator_to_array($xpath->query($kueri)));
    }

    private function teks(DOMXPath $xpath, string $kueri): string
    {
        $simpul = $xpath->query($kueri)->item(0);
        $this->assertNotNull($simpul, "Tidak ditemukan: {$kueri}");

        return $this->teksSimpul($simpul);
    }

    private function teksSimpul(DOMNode $simpul): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
