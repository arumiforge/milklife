<?php

use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Percobaan;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\Prestasi;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Penguraian dan penulisan prestasi, urutan peringkat, dan tebakan jenis
 * hasil (F8-04, K124); percobaan lompat/lempar, seperseribu detik, dan
 * pemecah seri (F9-09, K140 butir 7c).
 *
 * @internal
 */
final class HasilPrestasiPeringkatTest extends CIUnitTestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string, 2: int}>
     */
    public static function prestasiBenar(): iterable
    {
        yield 'waktu detik dan perseratus' => ['12,34', 'waktu', 1234];
        yield 'waktu titik desimal' => ['9.87', 'waktu', 987];
        yield 'waktu satu angka desimal' => ['12,3', 'waktu', 1230];
        yield 'waktu tanpa desimal' => ['65', 'waktu', 6500];
        yield 'waktu menit' => ['2:05,10', 'waktu', 12510];
        yield 'waktu menit tanpa desimal' => ['12:00', 'waktu', 72000];
        yield 'waktu berspasi' => [' 1:02, 5 ', 'waktu', 6250];
        yield 'jarak meter' => ['3,45', 'jarak', 345];
        yield 'jarak satu desimal' => ['12,5', 'jarak', 1250];
        yield 'jarak bulat' => ['4', 'jarak', 400];
        yield 'poin' => ['1250', 'poin', 1250];
    }

    /**
     * @dataProvider prestasiBenar
     */
    public function testPrestasiBenarDiurai(string $teks, string $jenis, int $nilai): void
    {
        $this->assertSame(['nilai' => $nilai, 'galat' => null], Prestasi::urai($teks, $jenis));
    }

    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function prestasiSalah(): iterable
    {
        yield 'waktu huruf' => ['12 detik', 'waktu'];
        yield 'waktu detik 60 di menit' => ['1:60,00', 'waktu'];
        yield 'waktu tiga desimal' => ['12,345', 'waktu'];
        yield 'waktu nol' => ['0,00', 'waktu'];
        yield 'waktu terlalu lama' => ['100:00', 'waktu'];
        yield 'jarak bersatuan' => ['3 m', 'jarak'];
        yield 'jarak menit' => ['1:20', 'jarak'];
        yield 'jarak terlalu jauh' => ['100,00', 'jarak'];
        yield 'poin desimal' => ['12,5', 'poin'];
        yield 'poin negatif' => ['-5', 'poin'];
    }

    /**
     * @dataProvider prestasiSalah
     */
    public function testPrestasiSalahMenyebutContoh(string $teks, string $jenis): void
    {
        $hasil = Prestasi::urai($teks, $jenis);
        $this->assertNull($hasil['nilai']);
        $this->assertStringContainsString('misalnya', (string) $hasil['galat']);
    }

    public function testPrestasiKosongBukanGalat(): void
    {
        $this->assertSame(['nilai' => null, 'galat' => null], Prestasi::urai('  ', 'waktu'));
    }

    public function testPrestasiDitulis(): void
    {
        $this->assertSame('9,87', Prestasi::tulis(987, 'waktu'));
        $this->assertSame('12,30', Prestasi::tulis(1230, 'waktu'));
        $this->assertSame('1:00,00', Prestasi::tulis(6000, 'waktu'));
        $this->assertSame('2:05,10', Prestasi::tulis(12510, 'waktu'));
        $this->assertSame('3,05', Prestasi::tulis(305, 'jarak'));
        $this->assertSame('1250', Prestasi::tulis(1250, 'poin'));
        $this->assertSame('', Prestasi::tulis(null, 'jarak'));

        // Tulis lalu urai menghasilkan angka yang sama.
        foreach ([[12510, 'waktu'], [987, 'waktu'], [345, 'jarak']] as [$nilai, $jenis]) {
            $this->assertSame($nilai, Prestasi::urai(Prestasi::tulis($nilai, $jenis), $jenis)['nilai']);
        }
    }

    public function testPeringkatWaktuMakinKecilMakinBaikDanSeri(): void
    {
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 1250, 'keterangan' => null],
            'b' => ['prestasi' => 1210, 'keterangan' => null],
            'c' => ['prestasi' => 1250, 'keterangan' => null],
            'd' => ['prestasi' => 1300, 'keterangan' => null],
            'e' => ['prestasi' => null, 'keterangan' => 'DNS'],
            'f' => ['prestasi' => 1100, 'keterangan' => 'DQ'],
            'g' => ['prestasi' => null, 'keterangan' => null],
        ], JenisHasil::WAKTU);

        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 2, 'd' => 4, 'e' => null, 'f' => null, 'g' => null], $hasil);
    }

    public function testPeringkatJarakDanPoinMakinBesarMakinBaik(): void
    {
        $peserta = [
            1 => ['prestasi' => 345, 'keterangan' => null],
            2 => ['prestasi' => 410, 'keterangan' => null],
            3 => ['prestasi' => 299, 'keterangan' => 'DNF'],
        ];

        $this->assertSame([2 => 1, 1 => 2, 3 => null], Peringkat::hitung($peserta, JenisHasil::JARAK));
        $this->assertSame([2 => 1, 1 => 2, 3 => null], Peringkat::hitung($peserta, JenisHasil::POIN));
    }

    public function testPeringkatKoreksiAdminTetap(): void
    {
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 1210, 'keterangan' => null, 'peringkat' => 2, 'manual' => true],
            'b' => ['prestasi' => 1210, 'keterangan' => null],
            'c' => ['prestasi' => 1300, 'keterangan' => null],
        ], JenisHasil::WAKTU);

        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 2], $hasil);
    }

    public function testKeteranganSah(): void
    {
        $this->assertSame('DNS', Peringkat::keterangan(' dns '));
        $this->assertSame('DQ', Peringkat::keterangan('DQ'));
        $this->assertNull(Peringkat::keterangan('X'));
        $this->assertNull(Peringkat::keterangan(null));
    }

    // ---------- Kode NM, r, dan pasal DQ (F11-05) ----------

    public function testPeringkatNmTanpaPeringkatDanMundurTetapDinilai(): void
    {
        $hasil = Peringkat::hitung([
            1 => ['prestasi' => 345, 'keterangan' => null],
            2 => ['prestasi' => 410, 'keterangan' => 'r'],
            3 => ['prestasi' => null, 'keterangan' => 'NM'],
            4 => ['prestasi' => 380, 'keterangan' => null],
            5 => ['prestasi' => null, 'keterangan' => 'r'],
            6 => ['prestasi' => 500, 'keterangan' => 'DQ'],
        ], JenisHasil::JARAK);

        $this->assertSame([2 => 1, 4 => 2, 1 => 3, 3 => null, 5 => null, 6 => null], $hasil);
    }

    public function testKeteranganNmDanMundur(): void
    {
        $this->assertSame('NM', Peringkat::keterangan(' nm '));
        $this->assertSame('r', Peringkat::keterangan('R'));
        $this->assertSame('r', Peringkat::keterangan('r'));
        $this->assertTrue(Peringkat::tanpaPeringkat('NM'));
        $this->assertTrue(Peringkat::tanpaPeringkat('DQ'));
        $this->assertFalse(Peringkat::tanpaPeringkat('r'));
        $this->assertFalse(Peringkat::tanpaPeringkat(null));
        $this->assertArrayHasKey('NM', Peringkat::pilihan(JenisHasil::JARAK));
        $this->assertSame(['DNS', 'DNF', 'DQ'], array_keys(Peringkat::pilihan(JenisHasil::WAKTU)));
        $this->assertStringContainsString('NM = tanpa lompatan', Peringkat::legenda(JenisHasil::JARAK));
        $this->assertSame('DNS = tidak start, DNF = tidak finis, DQ = diskualifikasi.', Peringkat::legenda(JenisHasil::WAKTU));
    }

    public function testPasalDiskualifikasi(): void
    {
        $this->assertSame('TR16.8', Peringkat::pasal(' TR16.8 '));
        $this->assertSame('TR 17.3.1', Peringkat::pasal('TR  17.3.1'));
        $this->assertNull(Peringkat::pasal(''));
        $this->assertNull(Peringkat::pasal(null));
        $this->assertFalse(Peringkat::pasal('TR16.8 dan TR17.3'));
        $this->assertFalse(Peringkat::pasal('<b>TR</b>'));
        $this->assertSame('DQ (TR16.8)', Peringkat::teks('DQ', 'TR16.8'));
        $this->assertSame('DQ', Peringkat::teks('DQ', null));
        $this->assertSame('r', Peringkat::teks('r', 'TR16.8'));
        $this->assertSame('', Peringkat::teks(null));
    }

    public function testSemuaPercobaanGagal(): void
    {
        $this->assertTrue(Percobaan::semuaGagal(['X', 'X', 'X'], 3));
        $this->assertTrue(Percobaan::semuaGagal(['X', '-', 'X'], 3));
        $this->assertFalse(Percobaan::semuaGagal(['X', 'X', null], 3));
        $this->assertFalse(Percobaan::semuaGagal(['X', 345, 'X'], 3));
        $this->assertFalse(Percobaan::semuaGagal(['-', '-', '-'], 3));
        $this->assertFalse(Percobaan::semuaGagal(null, 3));
    }

    // ---------- Aturan nomor (F9-09) ----------

    public function testWaktuSeperseribuDibulatkanKeAtas(): void
    {
        $this->assertSame(['nilai' => 1235, 'seperseribu' => 12341, 'galat' => null], Prestasi::uraiWaktuRinci('12,341'));
        $this->assertSame(['nilai' => 1234, 'seperseribu' => 12340, 'galat' => null], Prestasi::uraiWaktuRinci('12.340'));
        $this->assertSame(['nilai' => 12513, 'seperseribu' => 125123, 'galat' => null], Prestasi::uraiWaktuRinci('2:05,123'));
        $this->assertSame(['nilai' => 12510, 'seperseribu' => null, 'galat' => null], Prestasi::uraiWaktuRinci('2:05,1'));
        $this->assertSame(['nilai' => null, 'seperseribu' => null, 'galat' => null], Prestasi::uraiWaktuRinci(''));
        $this->assertStringContainsString('12,345', (string) Prestasi::uraiWaktuRinci('12,3456')['galat']);
        $this->assertNotNull(Prestasi::uraiWaktuRinci('0,000')['galat']);
        $this->assertSame('12,341', Prestasi::tulisSeperseribu(12341));
        $this->assertSame('2:05,003', Prestasi::tulisSeperseribu(125003));

        // Urai biasa (tabel skor, isian lain) tetap menolak tiga angka.
        $this->assertNull(Prestasi::urai('12,341', 'waktu')['nilai']);
    }

    // ---------- Cara pencatatan waktu (F11-04) ----------

    public function testWaktuTanganDibulatkanKeAtasKePersepuluh(): void
    {
        // TR 19: 12,34 menjadi 12,4; 12,30 tetap 12,3. Hitungan bilangan bulat.
        $this->assertSame(1240, Prestasi::bulatTangan(1234));
        $this->assertSame(1230, Prestasi::bulatTangan(1230));
        $this->assertSame(1210, Prestasi::bulatTangan(1201));
        $this->assertSame(1300, Prestasi::bulatTangan(1291));
        $this->assertSame(6000, Prestasi::bulatTangan(5991));
        $this->assertSame(12510, Prestasi::bulatTangan(12501));

        // Seperseribu dari isian dibulatkan dua kali ke atas = sekali ke 0,1 detik.
        $this->assertSame(1240, Prestasi::bulatTangan((int) Prestasi::uraiWaktuRinci('12,301')['nilai']));
        $this->assertSame(1230, Prestasi::bulatTangan((int) Prestasi::uraiWaktuRinci('12,300')['nilai']));
    }

    public function testWaktuTanganDitulisSatuDesimal(): void
    {
        $this->assertSame('12,4', Prestasi::tulis(1234, 'waktu', true));
        $this->assertSame('12,3', Prestasi::tulis(1230, 'waktu', true));
        $this->assertSame('9,1', Prestasi::tulis(901, 'waktu', true));
        $this->assertSame('1:00,0', Prestasi::tulisTangan(5991));
        $this->assertSame('2:05,1', Prestasi::tulisTangan(12501));

        // Elektronik dan jenis lain tetap dua angka desimal.
        $this->assertSame('12,34', Prestasi::tulis(1234, 'waktu'));
        $this->assertSame('3,45', Prestasi::tulis(345, 'jarak', true));
    }

    public function testPercobaanDiuraiDanTerbaikDipakai(): void
    {
        $this->assertSame(['percobaan' => [345, 'X', 350], 'galat' => null], Percobaan::urai(['3,45', 'x', '3.5'], 3));
        $this->assertSame(['percobaan' => ['-', null, 410], 'galat' => null], Percobaan::urai(['–', '', '4,10', '9,99'], 3));
        $this->assertSame(['percobaan' => null, 'galat' => null], Percobaan::urai(['', ' '], 3));
        $this->assertStringContainsString('Percobaan 2', (string) Percobaan::urai(['3,45', '3 m'], 3)['galat']);

        $this->assertSame(350, Percobaan::terbaik([345, 'X', 350]));
        $this->assertNull(Percobaan::terbaik(['X', 'X', '-']));
        $this->assertSame([350, 345, 300], Percobaan::sah([300, 'X', 350, 345]));
        $this->assertSame(['3,45', 'X', '', ''], Percobaan::teks([345, 'X', null], 4));

        $json = Percobaan::tulis([345, 'X', null]);
        $this->assertSame('[345,"X",null]', $json);
        $this->assertSame([345, 'X', null], Percobaan::baca($json));
        $this->assertNull(Percobaan::tulis([null, null, null]));
        $this->assertNull(Percobaan::baca('[345,"Y"]'));
        $this->assertNull(Percobaan::baca('rusak'));

        $this->assertSame(3, Percobaan::jumlah(null));
        $this->assertSame(6, Percobaan::jumlah('6'));
        $this->assertSame(3, Percobaan::jumlah(7));
    }

    public function testPemecahSeriJarakMemakaiPrestasiTerbaikKedua(): void
    {
        $pemecah = static fn (array $p, bool $aktif = true): ?array => Peringkat::pemecah(JenisHasil::JARAK, $p, null, $aktif);

        $peserta = [
            'a' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah([500, 480, 'X'])],
            'b' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah([490, 500, 'X'])],
            'c' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah(['X', 500, 'X'])],
            'd' => ['prestasi' => 520, 'keterangan' => null, 'pemecah' => $pemecah([520, 'X', 'X'])],
            'e' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah([480, 500, 'X'])],
        ];

        // b (kedua 4,90) di atas a dan e (kedua 4,80, sama), c (tanpa jarak sah kedua) terakhir.
        $this->assertSame(['d' => 1, 'b' => 2, 'a' => 3, 'e' => 3, 'c' => 5], Peringkat::hitung($peserta, JenisHasil::JARAK));

        // Ketiga ikut memisah bila kedua juga sama.
        $this->assertSame(['y' => 1, 'x' => 2], Peringkat::hitung([
            'x' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah([500, 480, 400])],
            'y' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => $pemecah([500, 480, 450])],
        ], JenisHasil::JARAK));

        // Dimatikan: prestasi sama tetap berperingkat sama.
        $mati = array_map(static fn (array $p): array => ['pemecah' => null] + $p, $peserta);
        $this->assertSame(['d' => 1, 'a' => 2, 'b' => 2, 'c' => 2, 'e' => 2], Peringkat::hitung($mati, JenisHasil::JARAK));
        $this->assertNull($pemecah([500, 480], false));
    }

    public function testPemecahSeriDataLamaTetapSama(): void
    {
        // Satu saja tanpa percobaan (data lama) = semua yang sama tetap sama.
        $hasil = Peringkat::hitung([
            'a' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => Peringkat::pemecah(JenisHasil::JARAK, [500, 490], null)],
            'b' => ['prestasi' => 500, 'keterangan' => null, 'pemecah' => Peringkat::pemecah(JenisHasil::JARAK, null, null)],
            'c' => ['prestasi' => 400, 'keterangan' => null],
        ], JenisHasil::JARAK);

        $this->assertSame(['a' => 1, 'b' => 1, 'c' => 3], $hasil);
        $this->assertNull(Peringkat::pemecah(JenisHasil::POIN, [500], 123));
        $this->assertNull(Peringkat::pemecah(JenisHasil::MULTI, null, null));
    }

    public function testPemecahSeriLariMemakaiSeperseribuBilaDiisi(): void
    {
        $waktu = static fn (?int $ribu): ?array => Peringkat::pemecah(JenisHasil::WAKTU, null, $ribu);

        $this->assertSame(['b' => 1, 'a' => 2, 'c' => 3, 'd' => 4], Peringkat::hitung([
            'a' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12343)],
            'b' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12341)],
            'c' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12350)],
            'd' => ['prestasi' => 1300, 'keterangan' => null, 'pemecah' => null],
        ], JenisHasil::WAKTU));

        // Seperseribu sama, atau ada yang tidak diisi: bersama.
        $this->assertSame(['a' => 1, 'b' => 1], Peringkat::hitung([
            'a' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12341)],
            'b' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12341)],
        ], JenisHasil::WAKTU));
        $this->assertSame(['a' => 1, 'b' => 1], Peringkat::hitung([
            'a' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => $waktu(12341)],
            'b' => ['prestasi' => 1235, 'keterangan' => null, 'pemecah' => null],
        ], JenisHasil::WAKTU));
    }

    public function testJenisHasilDitebakDariNama(): void
    {
        $this->assertSame('waktu', JenisHasil::tebak('Lari 60 m'));
        $this->assertSame('waktu', JenisHasil::tebak('Estafet 8 × 50 m'));
        $this->assertSame('waktu', JenisHasil::tebak("Kanga's Escape"));
        $this->assertSame('waktu', JenisHasil::tebak('Formula 1'));
        $this->assertSame('waktu', JenisHasil::tebak('Jalan Cepat 2000 m'));
        $this->assertSame('jarak', JenisHasil::tebak('Frog Jump'));
        $this->assertSame('jarak', JenisHasil::tebak('Turbo Throw'));
        $this->assertSame('jarak', JenisHasil::tebak('Lompat Tinggi'));
        $this->assertSame('jarak', JenisHasil::tebak('Tolak Peluru'));
        $this->assertSame('jarak', JenisHasil::tebak('Lempar Cakram'));
        $this->assertSame('poin', JenisHasil::tebak('Trilomba'));
    }
}
