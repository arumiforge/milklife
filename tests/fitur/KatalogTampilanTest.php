<?php

use App\Libraries\MenuPublik;
use Tests\Support\KirimFormulir;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman Katalog tampilan (`admin/gaya`) dan tata letak bertampilan baru
 * `publik_tw.php` / `admin_tw.php` (Tahap 9, F9-01, K140, K141): Flowbite Pro
 * (Tailwind) tanpa Bootstrap, JS Flowbite lokal, menu baru, menu bawah ponsel
 * 5 butir (K133).
 *
 * @internal
 */
final class KatalogTampilanTest extends UjiBasisData
{
    use KirimFormulir;
    use PendaftaranContoh;

    public function testKatalogHanyaUntukAdmin(): void
    {
        $this->get('admin/gaya')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testKatalogMemakaiTataLetakBaru(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/gaya');
        $hasil->assertOK();
        $html = (string) $hasil->response()->getBody();

        $this->assertStringContainsString('aset/css/tw.css', $html);
        $this->assertStringContainsString('aset/vendor/flowbite/flowbite.min.js', $html);
        $this->assertStringContainsString('type="module" src="' . base_url('aset/js/adegan-merek.js'), $html);
        $this->assertStringNotContainsString('bootstrap', $html, 'Tata letak baru tidak memuat Bootstrap.');

        $xpath = $this->xpathDari($hasil);
        $this->assertSame('Katalog tampilan', trim($xpath->query('//header//h1')->item(0)->textContent));
        $this->assertGreaterThan(0, $xpath->query('//*[@data-carousel-item]')->length, 'Carousel berindikator.');
        $this->assertGreaterThan(0, $xpath->query('//*[@data-carousel-slide-to]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="menu-laci"]')->length);

        // Gaya editorial F10-05: semua kelas baru tampil di katalog.
        foreach (['judul-tampilan', 'alis', 'tombol-utama-berart', 'pemisah-lintasan', 'bingkai-foto', 'tekstur-kertas'] as $kelas) {
            $this->assertGreaterThan(0, $xpath->query('//*[@data-katalog-editorial]//*[contains(concat(" ", normalize-space(@class), " "), " ' . $kelas . ' ")]')->length, $kelas);
        }
    }

    public function testMenuBawahPonselLimaButirPendaftaran(): void
    {
        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/gaya'));
        $butir = $xpath->query('//nav[@aria-label="Menu pendaftaran"]//li/*[self::a or self::button]');

        $label = [];
        foreach ($butir as $a) {
            $label[] = trim($a->textContent);
        }

        // K133, dengan butir Menu yang membuka sidebar sebagai butir kelima (F10-03, permintaan A9).
        $this->assertSame(['Dasbor', 'Pendaftaran', 'Atlet', 'Rekap', 'Menu'], $label);
    }

    public function testTataLetakPublikBaruDanMenunya(): void
    {
        setting('Acara.nama', 'Lomba Lari Karangan');

        $html = view('tata_letak/publik_tw');

        $this->assertStringContainsString('Lomba Lari Karangan', $html);
        $this->assertStringContainsString('aset/css/tw.css', $html);
        $this->assertStringNotContainsString('bootstrap', $html);
        $this->assertStringContainsString('href="' . site_url('cek-pendaftaran') . '"', $html);
        $this->assertStringContainsString('Masuk panitia', $html);
        $this->assertStringNotContainsString('>Daftar<', $html, 'Tombol Daftar hanya saat pendaftaran terbuka.');
    }

    public function testTombolDaftarSaatPendaftaranTerbuka(): void
    {
        $this->bukaPendaftaranUji();

        $label = array_column(MenuPublik::tombol(), 'label');

        $this->assertSame(['Cek Pendaftaran', 'Daftar'], $label);
        // Menu utama F10-07 (K154): Jadwal dan Peserta bersakelar, Hasil hanya setelah ada yang terbit;
        // Unduhan dan Bantuan pindah ke bilah atas.
        $this->assertSame(['Beranda'], array_column(MenuPublik::butir(), 'label'), 'Hasil hanya setelah ada yang terbit.');
        $this->assertSame(['Unduhan', 'Bantuan', 'Masuk panitia'], array_column(MenuPublik::bilahAtas(), 'label'), 'Galeri bersakelar; Tanya Panitia hanya bila nomor WA diisi; Masuk panitia selalu di akhir (F10-08).');
        $this->assertSame(['Beranda', 'Bantuan', 'Unduhan', 'Daftar', 'Cek'], array_column(MenuPublik::bawah(), 'label'), 'Jadwal dan Hasil yang mati diganti Bantuan lalu Unduhan; Cek paling kanan (F10-08).');
    }
}
