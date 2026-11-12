<?php

use CodeIgniter\I18n\Time;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Bagian jadwal, unduhan, dan kontak halaman depan (F3-03): data tersembunyi
 * dan berkas hilang tidak tampil, bagian kosong tidak tampil, alamat WA, tab
 * baru untuk tautan keluar, dan escape teks admin. Nama, nomor, dan alamat
 * karangan.
 *
 * @internal
 */
final class HalamanDepanJadwalKontakTest extends UjiBasisData
{
    use KirimFormulir;
    use UnggahanSementara;

    protected function setUp(): void
    {
        parent::setUp();

        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
        setting('Acara.nama', 'Lomba Lari Karangan');
    }

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    // ---------- Jadwal ----------

    public function testJadwalTampilBerurutanDenganRincian(): void
    {
        $this->tambahJadwal(['judul' => 'Technical meeting', 'urutan' => 2, 'waktu' => '2026-12-08 13:30:00', 'tempat' => 'Aula Karangan', 'keterangan' => "Bawa surat tugas.\nSatu orang per sekolah."]);
        $this->tambahJadwal(['judul' => 'Sosialisasi daring', 'urutan' => 1, 'tautan' => 'https://contoh.test/rapat']);
        $this->tambahJadwal(['judul' => 'Jadwal rahasia', 'urutan' => 3, 'tampil' => 0]);

        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $bagian = $xpath->query('//main/section[@id="jadwal"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame('judul-jadwal', $bagian->getAttribute('aria-labelledby'));
        $this->assertSame('Jadwal', $this->teks($xpath, '//section[@id="jadwal"]//h2[@id="judul-jadwal"]'));

        $butir = $xpath->query('//section[@id="jadwal"]//li[contains(@class, "jadwal-butir")]');
        $this->assertSame(2, $butir->length, 'Jadwal yang disembunyikan tidak tampil.');
        $hasil->assertDontSee('Jadwal rahasia');

        // Urutan dari angka urutan.
        $this->assertSame('Sosialisasi daring', $this->teks($xpath, './/h3', $butir->item(0)));
        $this->assertSame('Technical meeting', $this->teks($xpath, './/h3', $butir->item(1)));

        // Waktu kosong, tanpa tempat dan keterangan; tautan di tab baru.
        $this->assertSame('Waktu segera diumumkan', $this->teks($xpath, './/*[contains(@class, "jadwal-waktu")]', $butir->item(0)));
        $this->assertSame(0, $xpath->query('.//*[contains(@class, "jadwal-tempat") or contains(@class, "jadwal-keterangan")]', $butir->item(0))->length);
        $tautan = $xpath->query('.//a[contains(@class, "jadwal-tautan")]', $butir->item(0))->item(0);
        $this->assertNotNull($tautan);
        $this->assertSame('https://contoh.test/rapat', $tautan->getAttribute('href'));
        $this->assertTabBaru($tautan);

        // Waktu dengan jam dan WIB, tempat, keterangan dengan <br>, kotak tanggal.
        $this->assertSame('8 Desember 2026 pukul 13.30 WIB', $this->teks($xpath, './/*[contains(@class, "jadwal-waktu")]', $butir->item(1)));
        $this->assertSame('Aula Karangan', $this->teks($xpath, './/*[contains(@class, "jadwal-tempat")]', $butir->item(1)));
        $this->assertSame('Bawa surat tugas. Satu orang per sekolah.', $this->teks($xpath, './/*[contains(@class, "jadwal-keterangan")]', $butir->item(1)));
        $this->assertSame(1, $xpath->query('.//*[contains(@class, "jadwal-keterangan")]/br', $butir->item(1))->length);
        $this->assertSame('8 Des', $this->teks($xpath, './/*[contains(@class, "jadwal-tanggal")]', $butir->item(1)));
        $this->assertSame(0, $xpath->query('.//a', $butir->item(1))->length, 'Jadwal tanpa tautan tidak punya tombol tautan.');
    }

    public function testJadwalYangSemuanyaDisembunyikanTidakTampil(): void
    {
        $this->tambahJadwal(['judul' => 'Jadwal rahasia', 'tampil' => 0]);

        $hasil = $this->get('/');

        $this->assertSame(0, $this->xpathDari($hasil)->query('//section[@id="jadwal"]')->length);
        $hasil->assertDontSee('Jadwal rahasia');
    }

    // ---------- Unduhan ----------

    public function testUnduhanDiHalamanDepan(): void
    {
        $juknis = $this->simpanBerkasUji('pdf', self::isiPdf());
        $this->tambahUnduhan(['judul' => 'Juknis: Lomba/Seri 2', 'urutan' => 1, 'berkas' => $juknis, 'keterangan' => "Wajib dibaca.\nVersi terbaru."]);
        $this->tambahUnduhan(['judul' => 'Flyer', 'urutan' => 2, 'berkas' => $this->simpanBerkasUji('png', self::isiPng())]);
        $this->tambahUnduhan(['judul' => 'Berkas hilang', 'urutan' => 3, 'berkas' => 'unggahan/unduhan/' . str_repeat('e', 32) . '.pdf']);
        $this->tambahUnduhan(['judul' => 'Berkas tersembunyi', 'urutan' => 4, 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf()), 'tampil' => 0]);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $bagian = $xpath->query('//main/section[@id="unduhan"]')->item(0);
        $this->assertNotNull($bagian);
        $this->assertSame('judul-unduhan', $bagian->getAttribute('aria-labelledby'));
        $this->assertSame('Unduhan', $this->teks($xpath, '//section[@id="unduhan"]//h2[@id="judul-unduhan"]'));

        $butir = $xpath->query('//section[@id="unduhan"]//li[contains(@class, "unduhan-butir")]');
        $this->assertSame(2, $butir->length, 'Berkas yang hilang atau disembunyikan tidak tampil.');
        $hasil->assertDontSee('Berkas hilang');
        $hasil->assertDontSee('Berkas tersembunyi');

        $this->assertSame('Juknis: Lomba/Seri 2', $this->teks($xpath, './/h3', $butir->item(0)));
        $this->assertSame('Wajib dibaca. Versi terbaru.', $this->teks($xpath, './/*[contains(@class, "unduhan-keterangan")]', $butir->item(0)));
        $this->assertSame(1, $xpath->query('.//*[contains(@class, "unduhan-keterangan")]/br', $butir->item(0))->length);
        $this->assertMatchesRegularExpression('/^PDF · \d+ (B|KB)$/u', $this->teks($xpath, './/*[contains(@class, "unduhan-rincian")]', $butir->item(0)));
        $this->assertSame(1, $xpath->query('.//i[contains(@class, "bi-file-earmark-pdf")]', $butir->item(0))->length);

        $tombol = $xpath->query('.//a[contains(@class, "unduhan-tombol")]', $butir->item(0))->item(0);
        $this->assertNotNull($tombol);
        $this->assertSame(base_url($juknis), $tombol->getAttribute('href'));
        $this->assertSame('Juknis- Lomba-Seri 2.pdf', $tombol->getAttribute('download'), 'Nama simpan dari judul, tanpa tanda yang dilarang.');
        $this->assertStringStartsWith('Unduh Juknis: Lomba/Seri 2 (PDF · ', $this->teksSimpul($tombol));

        $this->assertSame('Flyer', $this->teks($xpath, './/h3', $butir->item(1)));
        $this->assertSame(1, $xpath->query('.//i[contains(@class, "bi-file-earmark-image")]', $butir->item(1))->length);
        $this->assertSame('Flyer.png', $xpath->query('.//a[contains(@class, "unduhan-tombol")]', $butir->item(1))->item(0)?->getAttribute('download'));

        // Tautan ke halaman semua unduhan.
        $semua = $xpath->query('//section[@id="unduhan"]//a[@href="' . site_url('unduhan') . '"]')->item(0);
        $this->assertNotNull($semua);
        $this->assertSame('Lihat semua unduhan', $this->teksSimpul($semua));
    }

    public function testUnduhanYangBerkasnyaHilangTidakTampil(): void
    {
        $this->tambahUnduhan(['judul' => 'Berkas hilang', 'berkas' => 'unggahan/unduhan/' . str_repeat('e', 32) . '.pdf']);

        $hasil = $this->get('/');

        $this->assertSame(0, $this->xpathDari($hasil)->query('//section[@id="unduhan"]')->length);
        $hasil->assertDontSee('Lihat semua unduhan');
    }

    // ---------- Kontak ----------

    public function testKontakTanpaIsiHanyaTombolBagikan(): void
    {
        $xpath = $this->xpathDari($this->get('/'));

        $bagian = $xpath->query('//main/section[@id="kontak"]')->item(0);
        $this->assertNotNull($bagian, 'Bagian kontak selalu tampil karena tombol bagikan selalu ada.');
        $this->assertSame('judul-kontak', $bagian->getAttribute('aria-labelledby'));
        $this->assertSame('Kontak', $this->teks($xpath, '//section[@id="kontak"]//h2[@id="judul-kontak"]'));

        $this->assertSame(0, $xpath->query('//section[@id="kontak"]//*[contains(@class, "daftar-narahubung") or contains(@class, "daftar-media-sosial") or contains(@class, "kontak-tanya")]')->length);
        $this->assertSame(0, $xpath->query('//section[@id="kontak"]//h3')->length);

        $tautan = $xpath->query('//section[@id="kontak"]//a');
        $this->assertSame(1, $tautan->length);
        $this->assertSame('Bagikan ke WA (di tab baru)', $this->teksSimpul($tautan->item(0)));
        $this->assertSame('https://wa.me/?text=' . rawurlencode("Lomba Lari Karangan\n" . site_url('/')), $tautan->item(0)->getAttribute('href'));
        $this->assertTabBaru($tautan->item(0));
    }

    public function testKontakLengkap(): void
    {
        $this->tambahTautan('narahubung', 'Ibu Karangan', '0812-3456-7890', 1);
        $this->tambahTautan('narahubung', 'Nomor Salah', '12345', 2);
        $this->tambahTautan('narahubung', 'Bapak Contoh', '+62 857 1111 2222', 3);
        $this->tambahTautan('media_sosial', 'Instagram', 'https://www.instagram.com/lomba.karangan/', 1);
        $this->tambahTautan('media_sosial', 'Situs lain', 'javascript:alert(1)', 2);
        $this->tambahTautan('media_sosial', 'YouTube', 'https://youtube.com/@karangan', 3);
        setting('HalamanDepan.nomor_wa_tanya', '081299998888');
        setting('HalamanDepan.pesan_tanya', 'Halo admin, saya mau tanya & daftar?');
        setting('HalamanDepan.pesan_bagikan', "Ayo ikut lomba!\nDaftar di sini");

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(['Narahubung', 'Media sosial'], $this->daftarTeks($xpath, '//section[@id="kontak"]//h3'));

        // Narahubung: nama, nomor tampil, tombol WA. Nomor yang tidak sah dilewati.
        $narahubung = [];

        foreach ($xpath->query('//section[@id="kontak"]//li[contains(@class, "narahubung-butir")]') as $li) {
            $tombol = $xpath->query('.//a', $li)->item(0);
            $this->assertTabBaru($tombol);
            $narahubung[] = [
                $this->teks($xpath, './/*[contains(@class, "narahubung-nama")]', $li),
                $this->teks($xpath, './/*[contains(@class, "narahubung-nomor")]', $li),
                $tombol->getAttribute('href'),
            ];
        }

        $this->assertSame([
            ['Ibu Karangan', '0812-3456-7890', 'https://wa.me/6281234567890'],
            ['Bapak Contoh', '0857-1111-2222', 'https://wa.me/6285711112222'],
        ], $narahubung);
        $hasil->assertDontSee('Nomor Salah');

        // Media sosial: ikon, label, alamat ringkas. Alamat yang bukan http/https dilewati.
        $media = [];

        foreach ($xpath->query('//section[@id="kontak"]//a[contains(@class, "media-sosial-tautan")]') as $a) {
            $this->assertTabBaru($a);
            $media[] = [
                $a->getAttribute('href'),
                $this->teks($xpath, './/*[contains(@class, "media-sosial-label")]', $a),
                $this->teks($xpath, './/*[contains(@class, "media-sosial-alamat")]', $a),
                $xpath->query('.//i', $a)->item(0)?->getAttribute('class'),
            ];
        }

        $this->assertSame([
            ['https://www.instagram.com/lomba.karangan/', 'Instagram', 'instagram.com/lomba.karangan', 'bi bi-instagram'],
            ['https://youtube.com/@karangan', 'YouTube', 'youtube.com/@karangan', 'bi bi-youtube'],
        ], $media);
        $hasil->assertDontSee('javascript:alert(1)');

        // Tombol tanya admin dan bagikan dengan pesan yang diatur (rawurlencode).
        $tanya = $xpath->query('//section[@id="kontak"]//a[contains(@class, "kontak-tanya")]')->item(0);
        $this->assertNotNull($tanya);
        $this->assertSame('Tanya admin lewat WA (di tab baru)', $this->teksSimpul($tanya));
        $this->assertSame('https://wa.me/6281299998888?text=' . rawurlencode('Halo admin, saya mau tanya & daftar?'), $tanya->getAttribute('href'));
        $this->assertStringContainsString('?text=Halo%20admin%2C%20saya%20mau%20tanya%20%26%20daftar%3F', $tanya->getAttribute('href'));
        $this->assertTabBaru($tanya);

        $bagikan = $xpath->query('//section[@id="kontak"]//a[contains(@class, "kontak-bagikan")]')->item(0);
        $this->assertNotNull($bagikan);
        $this->assertSame('https://wa.me/?text=' . rawurlencode("Ayo ikut lomba!\nDaftar di sini\n" . site_url('/')), $bagikan->getAttribute('href'));

        // Semua tautan keluar di bagian kontak dibuka di tab baru.
        foreach ($xpath->query('//section[@id="kontak"]//a') as $a) {
            $this->assertTabBaru($a);
        }
    }

    public function testTombolTanyaTanpaPesan(): void
    {
        setting('HalamanDepan.nomor_wa_tanya', '6281299998888');

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame('https://wa.me/6281299998888', $xpath->query('//a[contains(@class, "kontak-tanya")]')->item(0)?->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//section[@id="kontak"]//h3')->length, 'Tanpa narahubung dan media sosial, subjudulnya tidak tampil.');
    }

    // ---------- Escape dan susunan ----------

    public function testTeksAdminDiEscape(): void
    {
        $this->tambahJadwal([
            'judul'      => 'Rapat <b>penting</b>',
            'tempat'     => 'Ruang <i>A</i>',
            'keterangan' => "Catatan <script>alert(1)</script>\nbaris dua",
        ]);
        $this->tambahUnduhan(['judul' => 'Juknis <u>baru</u>', 'keterangan' => 'Isi <em>tebal</em>', 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf())]);
        $this->tambahTautan('narahubung', 'Ibu <s>Karangan</s>', '081234567890', 1);
        $this->tambahTautan('media_sosial', 'Insta<img src=x>', 'https://instagram.com/karangan', 1);

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        foreach (['<b>penting</b>', '<i>A</i>', '<script>alert(1)</script>', '<u>baru</u>', '<em>tebal</em>', '<s>Karangan</s>', '<img src=x>'] as $mentah) {
            $hasil->assertDontSee($mentah);
        }

        $this->assertSame('Rapat <b>penting</b>', $this->teks($xpath, '//section[@id="jadwal"]//h3'));
        $this->assertSame('Catatan <script>alert(1)</script> baris dua', $this->teks($xpath, '//*[contains(@class, "jadwal-keterangan")]'));
        $this->assertSame('Juknis <u>baru</u>', $this->teks($xpath, '//section[@id="unduhan"]//h3'));
        $this->assertSame('Ibu <s>Karangan</s>', $this->teks($xpath, '//*[contains(@class, "narahubung-nama")]'));
        $this->assertSame('Insta<img src=x>', $this->teks($xpath, '//*[contains(@class, "media-sosial-label")]'));
        $this->assertSame(0, $xpath->query('//main//script | //main//b | //main//img')->length);
    }

    public function testUrutanBagianDanJangkar(): void
    {
        $this->tambahJadwal(['judul' => 'Sosialisasi']);
        $this->tambahUnduhan(['judul' => 'Juknis', 'berkas' => $this->simpanBerkasUji('pdf', self::isiPdf())]);

        $xpath = $this->xpathDari($this->get('/'));

        $this->assertSame(['pembuka', 'jadwal', 'unduhan', 'kontak'], array_map(
            static fn (DOMElement $s): string => $s->getAttribute('id'),
            iterator_to_array($xpath->query('//main/section')),
        ));

        foreach (['jadwal', 'unduhan', 'kontak'] as $id) {
            $this->assertSame(1, $xpath->query('//section[@id="' . $id . '"]//h2[@id="judul-' . $id . '"]')->length, $id);
        }

        $this->assertSame(1, $xpath->query('//h1')->length, 'Tetap satu h1.');
    }

    public function testPotonganKosong(): void
    {
        $isi = static fn (string $potongan, array $data): string => trim((string) preg_replace('/<!--.*?-->/s', '', view($potongan, $data, ['saveData' => false])));

        $this->assertSame('', $isi('beranda/_jadwal', ['jadwal' => []]));
        $this->assertSame('', $isi('beranda/_unduhan', ['unduhan' => []]));
        $this->assertStringContainsString('Bagikan ke WA', $isi('beranda/_kontak', ['narahubung' => [], 'mediaSosial' => [], 'waTanya' => null, 'waBagikan' => 'https://wa.me/?text=a']));
    }

    // ---------- Bantuan ----------

    /**
     * @param array<string, mixed> $data
     */
    private function tambahJadwal(array $data): void
    {
        $this->db->table('jadwal')->insert($data + ['urutan' => 1, 'tampil' => 1]);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function tambahUnduhan(array $data): void
    {
        $this->db->table('unduhan')->insert($data + ['urutan' => 1, 'tampil' => 1]);
    }

    private function tambahTautan(string $jenis, string $label, string $nilai, int $urutan): void
    {
        $this->db->table('tautan')->insert(['jenis' => $jenis, 'label' => $label, 'nilai' => $nilai, 'urutan' => $urutan]);
    }

    /**
     * Berkas unduhan karangan di folder unggahan sementara; kembalikan jalurnya.
     */
    private function simpanBerkasUji(string $ekstensi, string $isi): string
    {
        $jalur = 'unggahan/unduhan/' . bin2hex(random_bytes(16)) . '.' . $ekstensi;
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, $isi);

        return $jalur;
    }

    private function assertTabBaru(?DOMElement $tautan): void
    {
        $this->assertNotNull($tautan);
        $this->assertSame('_blank', $tautan->getAttribute('target'), $tautan->getAttribute('href'));
        $this->assertSame('noopener noreferrer', $tautan->getAttribute('rel'), $tautan->getAttribute('href'));
    }

    /**
     * @return list<string>
     */
    private function daftarTeks(DOMXPath $xpath, string $kueri): array
    {
        return array_map(fn (DOMNode $simpul): string => $this->teksSimpul($simpul), iterator_to_array($xpath->query($kueri)));
    }

    private function teks(DOMXPath $xpath, string $kueri, ?DOMNode $konteks = null): string
    {
        $simpul = $xpath->query($kueri, $konteks)->item(0);
        $this->assertNotNull($simpul, "Tidak ditemukan: {$kueri}");

        return $this->teksSimpul($simpul);
    }

    private function teksSimpul(DOMNode $simpul): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $simpul->textContent));
    }
}
