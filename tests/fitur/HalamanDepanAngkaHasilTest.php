<?php

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Bagian angka acara dan hasil terbaru halaman depan (F9-03). Angka hanya
 * dari pendaftaran Lunas; hasil hanya dari nomor yang sudah terbit. Nama,
 * NISN, dan NPSN karangan.
 *
 * @internal
 */
final class HalamanDepanAngkaHasilTest extends UjiBasisData
{
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    /**
     * @var array<string, int> nama kelompok umur => id
     */
    private array $ku = [];

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-12-10 10:00:00', 'Asia/Jakarta');
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
        $this->ku = array_column($this->db->table('kelompok_umur')->select('id, nama')->get()->getResultArray(), 'id', 'nama');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    public function testBagianTidakTampilTanpaData(): void
    {
        // Pendaftaran Menunggu tidak dihitung; belum ada hasil yang terbit.
        $this->buatPendaftaran('SD KARANGAN MENUNGGU', '99990009', 'Kudus', 'menunggu', ['KU 10' => 3]);
        $this->buatFinal(false);

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(0, $xpath->query('//section[@id="angka"]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="hasil"]')->length);
        $this->assertSame(0, $xpath->query('//nav[@aria-label="Menu utama"]//a[normalize-space()="Hasil"]')->length);
    }

    public function testAngkaAcaraDariPendaftaranLunas(): void
    {
        $this->buatPendaftaran('SD KARANGAN SATU', '99990001', 'Kudus', 'lunas', ['KU 10' => 3, 'KU 12' => 1]);
        $this->buatPendaftaran('SD KARANGAN DUA', '99990002', 'Jepara', 'lunas', ['KU 10' => 1200]);
        // Tanpa NPSN dihitung sebagai sekolah sendiri; Menunggu tidak dihitung.
        $this->buatPendaftaran('SEKOLAH TANPA NPSN', null, 'Kudus', 'lunas', ['KU 15' => 2]);
        $this->buatPendaftaran('SD KARANGAN MENUNGGU', '99990009', 'Pati', 'menunggu', ['KU 8 (Seneng Playon)' => 5]);

        $xpath = $this->xpathDari($this->get('/'));
        $bagian = $xpath->query('//section[@id="angka"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame('judul-angka', $bagian->getAttribute('aria-labelledby'));

        $this->assertSame('1.206', $this->teks($xpath, '//*[@data-angka-atlet]'));
        $this->assertSame('3', $this->teks($xpath, '//*[@data-angka-sekolah]'));
        $this->assertSame('2', $this->teks($xpath, '//*[@data-angka-kabupaten]'));
        $this->assertSame(
            ['KU 10 1.203 atlet', 'KU 12 1 atlet', 'KU 15 2 atlet'],
            array_map($this->teksSimpul(...), iterator_to_array($xpath->query('//ul[@data-angka-ku]/li'))),
        );

        // Urutan bagian: angka sesudah jadwal, sebelum unduhan.
        $urutan = array_map(static fn (DOMElement $s): string => $s->getAttribute('id'), iterator_to_array($xpath->query('//main/section')));
        $this->assertSame(array_values(array_intersect(['pembuka', 'lomba', 'jadwal', 'angka', 'hasil', 'faq', 'unduhan', 'kontak'], $urutan)), $urutan);
    }

    public function testHasilTerbaruDanJuaraUmumHanyaYangTerbit(): void
    {
        $sd = $this->buatPendaftaran('SD KARANGAN SATU', '99990001', 'Kudus', 'lunas', ['KU 10' => 2]);
        $sd2 = $this->buatPendaftaran('SD KARANGAN DUA', '99990002', 'Kudus', 'lunas', ['KU 10' => 2]);
        $terbit = $this->buatFinal(true, [$sd[0], $sd2[0], $sd[1]]);
        $belum  = $this->buatFinal(false, [$sd2[1], $sd[0]]);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $bagian = $xpath->query('//section[@id="hasil"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame(1, $xpath->query('//section[@id="hasil"]//a[@href="' . site_url('hasil') . '"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="hasil"]//a[@href="' . site_url('hasil/klasemen') . '"]')->length);

        // Hanya nomor terbit, dengan juara I–III dan sekolahnya.
        $nomor = $xpath->query('//*[@data-hasil-terbaru]/li');
        $this->assertSame(1, $nomor->length);
        $this->assertSame((string) $terbit, $nomor->item(0)->getAttribute('data-hasil-nomor'));
        $teks = $this->teksSimpul($nomor->item(0));
        $this->assertStringContainsString('Juara I ' . $this->namaAtlet($sd[0]), $teks);
        $this->assertStringContainsString('Juara III ' . $this->namaAtlet($sd[1]) . ' SD KARANGAN SATU', $teks);
        $this->assertSame(0, $xpath->query('//*[@data-hasil-nomor="' . $belum . '"]')->length);

        // Juara umum SD/MI dari nomor terbit saja: SD Karangan Satu (I dan III) di atas Dua (II).
        $umum = array_map($this->teksSimpul(...), iterator_to_array($xpath->query('//*[@data-hasil-juara-umum]//li')));
        $this->assertCount(2, $umum);
        $this->assertStringStartsWith('1. SD KARANGAN SATU 1', $umum[0]);
        $this->assertStringStartsWith('2. SD KARANGAN DUA 0', $umum[1]);
    }

    public function testFaqTeratasDariBantuan(): void
    {
        // Data awal memasang tanya-jawab bawaan (F9-04): satu per kategori, paling banyak enam.
        $xpath  = $this->xpathDari($this->get('/'));
        $butir  = $xpath->query('//section[@id="faq"]//details[@data-faq-butir]');
        $jumlah = min(6, $this->db->table('faq')->select('kategori')->where('aktif', 1)->distinct()->countAllResults());

        $this->assertGreaterThan(0, $jumlah);
        $this->assertSame($jumlah, $butir->length);
        $this->assertSame(0, $xpath->query('//section[@id="faq"]//details[@open]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="faq"]//a[@href="' . site_url('bantuan') . '"]')->length);

        // Tanpa tanya-jawab aktif, bagiannya tidak tampil.
        $this->db->table('faq')->update(['aktif' => 0]);
        $this->assertSame(0, $this->xpathDari($this->get('/'))->query('//section[@id="faq"]')->length);
    }

    /**
     * Pendaftaran karangan dengan atlet per kelompok umur; kembalikan id atletnya.
     *
     * @param array<string, int> $atlet nama kelompok umur => jumlah
     *
     * @return list<int>
     */
    private function buatPendaftaran(string $sekolah, ?string $npsn, string $kabupaten, string $status, array $atlet): array
    {
        static $nisn = 0;

        $waktu = '2026-11-10 10:00:00';
        $this->db->table('pendaftaran')->insert([
            'jenjang' => 'A', 'npsn' => $npsn, 'nama_sekolah' => $sekolah, 'kabupaten' => $kabupaten,
            'subtotal_individu' => 0, 'subtotal_beregu' => 0, 'total' => 0, 'status' => $status,
            'waktu_lunas' => $status === 'lunas' ? $waktu : null, 'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
        ]);
        $id    = (int) $this->db->insertID();
        $baris = [];

        foreach ($atlet as $ku => $jumlah) {
            for ($i = 0; $i < $jumlah; $i++) {
                $nisn++;
                $baris[] = [
                    'pendaftaran_id' => $id, 'nama' => 'Atlet Karangan ' . $nisn, 'nisn' => sprintf('98%08d', $nisn),
                    'tanggal_lahir' => '2016-01-01', 'jenis_kelamin' => 'L', 'kelompok_umur_id' => $this->ku[$ku],
                    'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
                ];
            }
        }

        $this->db->table('atlet')->insertBatch($baris);

        return array_map('intval', array_column($this->db->table('atlet')->select('id')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray(), 'id'));
    }

    /**
     * Babak Final nomor individu KU 10 berikutnya dengan peringkat sesuai urutan atlet.
     *
     * @param list<int> $atlet
     */
    private function buatFinal(bool $terbit, array $atlet = []): int
    {
        static $lewati = 0;

        $nomor = (int) $this->db->table('nomor_lomba')->select('id')->where('kelompok_umur_id', $this->ku['KU 10'])->where('jenis', 'individu')
            ->orderBy('id')->limit(1, $lewati++)->get()->getRow()->id;

        $this->db->table('babak')->insert(['nomor_lomba_id' => $nomor, 'nama' => 'Final', 'final' => 1, 'urutan' => 1]);
        $babak = (int) $this->db->insertID();

        foreach ($atlet as $i => $a) {
            $this->db->table('hasil')->insert(['babak_id' => $babak, 'atlet_id' => $a, 'prestasi' => 1000 + $i, 'peringkat' => $i + 1]);
        }

        if ($terbit) {
            $this->db->table('nomor_lomba')->where('id', $nomor)->update(['hasil_terbit_pada' => '2026-12-10 09:00:00']);
        }

        return $nomor;
    }

    private function namaAtlet(int $id): string
    {
        return (string) $this->db->table('atlet')->select('nama')->where('id', $id)->get()->getRow()->nama;
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
