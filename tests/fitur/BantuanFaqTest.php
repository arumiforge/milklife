<?php

use App\Libraries\Bantuan\FaqBawaan;
use App\Models\FaqModel;
use Tests\Support\KirimFormulir;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman publik Bantuan (`/bantuan`) dan laci bantuan wizard (F9-04).
 *
 * @internal
 */
final class BantuanFaqTest extends UjiBasisData
{
    use KirimFormulir;
    use PendaftaranContoh;

    private function buatFaq(string $kategori, string $pertanyaan, string $jawaban = 'Jawaban.', int $aktif = 1, int $urutan = 1): int
    {
        return (int) model(FaqModel::class)->insert(['kategori' => $kategori, 'pertanyaan' => $pertanyaan, 'jawaban' => $jawaban, 'urutan' => $urutan, 'aktif' => $aktif]);
    }

    // ---------- Halaman /bantuan ----------

    public function testHalamanKosongTetapTerbukaDenganTombolWa(): void
    {
        setting('HalamanDepan.nomor_wa_tanya', '628123456789');

        $hasil = $this->get('bantuan');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Bantuan', trim($xpath->query('//main//h1')->item(0)->textContent));
        $hasil->assertSee('Tanya-jawab belum tersedia');
        $this->assertSame(0, $xpath->query('//input[@id="cari-bantuan"]')->length, 'Kotak cari hanya bila ada tanya-jawab.');
        $this->assertSame(1, $xpath->query('//section[@id="tanya-panitia"]//a[starts-with(@href, "https://wa.me/628123456789")][@target="_blank"]')->length);
        // Panduan bergambar tetap tampil; tanpa gambar G9-03 memakai ikon.
        $this->assertSame(count(App\Libraries\Bantuan\IsiBantuan::PANDUAN), $xpath->query('//section[@data-bagian-panduan]//ol/li')->length);
    }

    public function testKategoriDanTanyaJawabAktifSaja(): void
    {
        $kedua   = $this->buatFaq('sebelum', 'Pertanyaan <kedua>?', 'Jawaban **tebal**.', 1, 2);
        $pertama = $this->buatFaq('sebelum', 'Apakah harus punya akun?', 'Tidak perlu.', 1, 1);
        $this->buatFaq('hasil', 'Tersembunyi?', 'Rahasia.', 0);
        $bayar = $this->buatFaq('pembayaran', 'Bukti transfer hilang?', 'Ambil tangkapan layar dari m-banking.');

        $hasil = $this->get('bantuan');
        $hasil->assertOK();
        $hasil->assertDontSee('Tersembunyi?');
        $this->assertStringNotContainsString('Pertanyaan <kedua>?', (string) $hasil->getBody());

        $xpath = $this->xpathDari($hasil);

        // Hanya kategori yang berisi, urut seperti KATEGORI; kartu kategori bertautan ke bagiannya.
        $bagian = array_map(static fn (DOMElement $e): string => $e->getAttribute('id'), iterator_to_array($xpath->query('//section[@data-kategori-faq]')));
        $this->assertSame(['kategori-sebelum', 'kategori-pembayaran'], $bagian);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Kategori bantuan"]//a[@href="#kategori-pembayaran"]')->length);

        // Butir berurutan, membawa teks pencarian huruf kecil, jawaban HTML.
        $butir = $xpath->query('//section[@id="kategori-sebelum"]//*[@data-butir-faq]');
        $this->assertSame(['faq-' . $pertama, 'faq-' . $kedua], [$butir->item(0)->getAttribute('id'), $butir->item(1)->getAttribute('id')]);
        $this->assertSame('pertanyaan <kedua>? jawaban tebal.', $butir->item(1)->getAttribute('data-cari'));
        $this->assertSame(1, $xpath->query('.//strong[.="tebal"]', $butir->item(1))->length);
        $this->assertSame(1, $xpath->query('//*[@id="faq-' . $bayar . '"]//button[@data-accordion-target="#faq-isi-' . $bayar . '"][@aria-controls="faq-isi-' . $bayar . '"]')->length);

        $this->assertSame(1, $xpath->query('//input[@id="cari-bantuan"][@type="search"]')->length);
        $hasil->assertSee(aset('js/bantuan.js'));
    }

    public function testIsianOtomatisMengikutiPengaturan(): void
    {
        setting('Pendaftaran.akhir', '2026-11-30 23:59:00');
        setting('Pembayaran.bank', 'BANK KARANGAN');
        setting('Pembayaran.nomor_rekening', '999888777');
        $this->buatFaq('pembayaran', 'Ke rekening mana?', 'Transfer ke {rekening} sebelum {tutup_pendaftaran}.');

        $hasil = $this->get('bantuan');

        $hasil->assertSee('BANK KARANGAN');
        $hasil->assertSee('999888777');
        $hasil->assertSee('30 November 2026 pukul 23.59');
        $hasil->assertDontSee('{rekening}');
    }

    /**
     * K143: rincian biaya di FAQ mengikuti sakelar "Tampilkan biaya di halaman
     * depan" (K74, bawaan mati).
     */
    public function testBiayaDiFaqMengikutiSakelarTampilkanBiaya(): void
    {
        $this->isiDataIndukPendaftaran();
        $this->buatFaq('pembayaran', 'Berapa biayanya?', '{daftar_biaya}');

        $tersembunyi = $this->get('bantuan');
        $tersembunyi->assertSee('Rincian biaya tampil di langkah');
        $tersembunyi->assertDontSee('Rp ');

        setting('HalamanDepan.tampilkan_biaya', true);

        $tampil = $this->get('bantuan');
        $tampil->assertSee('Rp ');
        $tampil->assertDontSee('Rincian biaya tampil di langkah');
    }

    public function testSemuaBawaanTampil(): void
    {
        FaqBawaan::pasang($this->db);

        $hasil = $this->get('bantuan');
        $hasil->assertOK();

        $xpath = $this->xpathDari($hasil);
        $this->assertSame(count(FaqBawaan::DAFTAR), $xpath->query('//*[@data-butir-faq]')->length);
        $this->assertSame(count(FaqModel::KATEGORI), $xpath->query('//section[@data-kategori-faq]')->length);
        $this->assertDoesNotMatchRegularExpression('/\{[a-z_]+\}/', (string) $hasil->getBody(), 'Semua isian otomatis bawaan dikenal.');
    }

    public function testMenuBantuanDiTataLetakBaruDanLama(): void
    {
        // Bantuan di bilah menu atas dan laci ponsel (F10-07, K154).
        $xpath = $this->xpathDari($this->get('bantuan'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Menu tambahan"]//a[@href="' . site_url('bantuan') . '"][@aria-current="page"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Menu ponsel"]//a[@href="' . site_url('bantuan') . '"][@aria-current="page"]')->length);

        $xpath = $this->xpathDari($this->get('unduhan'));
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Menu tambahan"]//a[@href="' . site_url('bantuan') . '"]')->length);
    }

    // ---------- Laci bantuan wizard ----------

    public function testTombolBantuanDiTiapLangkahDanLaciPerLangkah(): void
    {
        $this->bukaPendaftaranUji();
        $this->buatFaq('mengisi', 'Foto ofisial dari HP boleh?');
        $this->buatFaq('pembayaran', 'Bukti transfer hilang?');
        $this->buatFaq('hasil', 'Di mana hasil?');
        $this->buatFaq('mengisi', 'Tersembunyi?', 'x', 0);

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        for ($langkah = 1; $langkah <= 5; $langkah++) {
            $tombol = $xpath->query('//section[@id="langkah-' . $langkah . '"]//button[@data-buka-bantuan="' . $langkah . '"]')->item(0);
            $this->assertNotNull($tombol, "Tombol bantuan langkah {$langkah}.");
            $this->assertSame('laci-bantuan', $tombol->getAttribute('data-drawer-show'));
            $this->assertSame('laci-bantuan', $tombol->getAttribute('aria-controls'));
        }

        // Drawer Flowbite (F9-05): satu pemicu `data-drawer-target` (tombol tutup di dalam laci).
        $laci = $xpath->query('//div[@id="laci-bantuan"]')->item(0);
        $this->assertNotNull($laci);
        $this->assertSame(1, $xpath->query('//*[@data-drawer-target="laci-bantuan"]')->length);
        $this->assertSame(1, $xpath->query('.//*[@data-drawer-target="laci-bantuan"][@data-drawer-hide="laci-bantuan"]', $laci)->length);

        $pertanyaan = static fn (int $langkah): array => array_map(
            static fn (DOMElement $e): string => trim($e->textContent),
            iterator_to_array($xpath->query('.//*[@data-bantuan-langkah="' . $langkah . '"]//button[@data-accordion-target]', $laci)),
        );
        $this->assertSame(['Foto ofisial dari HP boleh?'], $pertanyaan(2));
        $this->assertSame(['Bukti transfer hilang?'], $pertanyaan(5));
        $this->assertSame(['Bukti transfer hilang?'], $pertanyaan(4));
        $this->assertSame(['Foto ofisial dari HP boleh?'], $pertanyaan(1), 'Kategori Sebelum mendaftar masih kosong.');
        $this->assertSame(1, $xpath->query('.//a[@href="' . site_url('bantuan') . '"]', $laci)->length);
        $hasil->assertSee(aset('js/bantuan.js'));
        $hasil->assertDontSee('Tersembunyi?');
    }

    public function testKepalaBantuanHanyaBilaBerkasnyaAdaDanTidakDiunduhDiPonsel(): void
    {
        $this->assertSame(0, $this->xpathDari($this->get('bantuan'))->query('//img[@data-kepala-bantuan]')->length);

        // Kepala G9-07 disembunyikan di bawah md dan dimuat lazy, jadi ponsel tidak mengunduhnya.
        $this->taruhGambarUji('bantuan/kepala-bantuan.webp');
        $img = $this->xpathDari($this->get('bantuan'))->query('//img[@data-kepala-bantuan]')->item(0);
        $this->assertNotNull($img);
        $this->assertStringContainsString('aset/gambar/bantuan/kepala-bantuan.webp', $img->getAttribute('src'));
        $this->assertSame('lazy', $img->getAttribute('loading'));
        $this->assertStringContainsString('md:block', $img->getAttribute('class'));
    }
}
