<?php

use App\Libraries\CarouselHalamanDepan;
use App\Libraries\Unggahan;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Carousel foto pembuka halaman depan (F9-03): foto bawaan dari `data:awal`
 * dan `halaman-depan:carousel`, tampilan carousel, dan pengelolaan foto di
 * Pengaturan > Halaman Depan. Keterangan foto karangan.
 *
 * @internal
 */
final class HalamanDepanCarouselTest extends UjiBasisData
{
    use KirimFormulir;
    use StreamFilterTrait;
    use UnggahanSementara;

    private const ALAMAT = 'admin/pengaturan/halaman-depan';

    public function testDataAwalMemasangFotoBawaanSekali(): void
    {
        $this->assertNull(CarouselHalamanDepan::tersimpan());
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));
        $this->assertStringContainsString(count(CarouselHalamanDepan::BAWAAN) . ' foto carousel halaman depan dipasang', $this->getStreamFilterBuffer());

        $daftar = CarouselHalamanDepan::daftar();
        $this->assertCount(count(CarouselHalamanDepan::BAWAAN), $daftar);
        $this->assertSame(array_values(CarouselHalamanDepan::BAWAAN), array_column($daftar, 'keterangan'));

        foreach ($daftar as $foto) {
            $this->assertMatchesRegularExpression('#^unggahan/halaman-depan/[a-f0-9]{32}\.webp$#', $foto['berkas']);
            $this->assertStringStartsWith($this->folderUnggahan, (string) (new Unggahan())->jalurPenuh($foto['berkas']), 'Uji tidak menulis ke public/unggahan.');
        }

        // Foto bawaan sudah dikecilkan: sisi terpanjang paling banyak 1600 piksel.
        foreach (array_keys(CarouselHalamanDepan::BAWAAN) as $berkas) {
            $ukuran = getimagesize(ROOTPATH . 'data/' . $berkas);
            $this->assertNotFalse($ukuran, $berkas);
            $this->assertLessThanOrEqual(1600, max($ukuran[0], $ukuran[1]), $berkas);
        }

        // Perintah pemasangan lama tidak menimpa daftar yang sudah ada.
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $this->assertStringContainsString('sudah diatur admin', $this->getStreamFilterBuffer());
        $this->assertSame($daftar, CarouselHalamanDepan::daftar());
    }

    public function testPerintahPemasanganLamaMenghormatiIsianAdmin(): void
    {
        // Admin sudah menghapus semua foto: daftar kosong tetap kosong.
        CarouselHalamanDepan::simpan([]);
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $this->assertSame([], CarouselHalamanDepan::tersimpan());

        // Belum pernah diatur: foto bawaan dipasang.
        setting()->forget(CarouselHalamanDepan::KUNCI);
        $this->assertNull(CarouselHalamanDepan::tersimpan());
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $this->assertCount(count(CarouselHalamanDepan::BAWAAN), CarouselHalamanDepan::daftar());
    }

    public function testCarouselDiPembuka(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $jumlah = count(CarouselHalamanDepan::BAWAAN);

        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $carousel = $xpath->query('//section[@id="pembuka"]//*[@id="carousel-beranda"][@data-carousel="static"][@data-carousel-beranda]');
        $this->assertSame(1, $carousel->length);
        $this->assertSame('carousel', $carousel->item(0)->getAttribute('aria-roledescription'));

        $gambar = $xpath->query('//*[@data-carousel-item]/img');
        $this->assertSame($jumlah, $gambar->length);
        $this->assertSame(array_values(CarouselHalamanDepan::BAWAAN), array_map(static fn (DOMElement $img): string => $img->getAttribute('alt'), iterator_to_array($gambar)));

        // Foto pertama dimuat lebih dulu, sisanya menunggu.
        $this->assertSame('high', $gambar->item(0)->getAttribute('fetchpriority'));
        $this->assertFalse($gambar->item(0)->hasAttribute('loading'));
        for ($i = 1; $i < $jumlah; $i++) {
            $this->assertSame('lazy', $gambar->item($i)->getAttribute('loading'), 'Foto ' . ($i + 1));
        }
        $this->assertSame('active', $xpath->query('//*[@data-carousel-item]')->item(0)->getAttribute('data-carousel-item'));

        // Indikator, tombol geser, dan tombol jeda.
        $this->assertSame($jumlah, $xpath->query('//*[@data-indikator-carousel]/button[@data-carousel-slide-to]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-carousel-prev]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-carousel-next]')->length);
        $jeda = $xpath->query('//button[@data-jeda-carousel]')->item(0);
        $this->assertNotNull($jeda);
        $this->assertSame('false', $jeda->getAttribute('aria-pressed'));
        $this->assertSame('Jeda foto bergeser', $jeda->getAttribute('aria-label'));
        $this->assertSame(1, $xpath->query('//script[contains(@src, "js/carousel-beranda")]')->length);

        // Judul, keadaan, dan dua tombol tetap ada di pembuka, sebelum carousel (K81).
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]//h1[@id="judul-acara"][following::*[@id="carousel-beranda"]]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]//nav[@data-tombol-utama][following::*[@id="carousel-beranda"]]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]//*[@data-keadaan][following::*[@id="carousel-beranda"]]')->length);
    }

    public function testSatuFotoTampilDiamDanFotoHilangDisembunyikan(): void
    {
        $ada = $this->simpanFotoUji();
        CarouselHalamanDepan::simpan([
            ['berkas' => 'unggahan/halaman-depan/' . str_repeat('a', 32) . '.webp', 'keterangan' => 'Berkas hilang'],
            ['berkas' => $ada, 'keterangan' => 'Atlet karangan berlari'],
        ]);
        // Gambar kepala tidak dipakai selama carousel berisi foto.
        setting('HalamanDepan.gambar_kepala', $this->simpanFotoUji());

        $hasil = $this->get('/');
        $xpath = $this->xpathDari($hasil);

        $this->assertSame(0, $xpath->query('//*[@data-carousel]')->length, 'Satu foto tidak berputar.');
        $this->assertSame(0, $xpath->query('//script[contains(@src, "js/carousel-beranda")]')->length);
        $img = $xpath->query('//section[@id="pembuka"]//*[@data-foto-pembuka]/img');
        $this->assertSame(1, $img->length);
        $this->assertSame(base_url($ada), $img->item(0)->getAttribute('src'));
        $this->assertSame('Atlet karangan berlari', $img->item(0)->getAttribute('alt'));
        $hasil->assertDontSee('Berkas hilang');
    }

    public function testAdminMenambahMengurutkanDanMenghapusFoto(): void
    {
        $alamat = self::ALAMAT . '/carousel';
        $tujuan = self::ALAMAT . '#carousel';

        $this->assertBelumMasukDialihkan($alamat, 'post');

        // Tambah dua foto.
        foreach (['Foto <b>satu</b>', 'Foto dua'] as $keterangan) {
            $this->siapkanBerkas('foto', 'foto.jpg', self::isiJpg());
            $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat, ['keterangan' => '  ' . $keterangan . ' ']), $tujuan, 'Foto ditambahkan ke carousel halaman depan.');
        }
        service('superglobals')->setFilesArray([]);

        $daftar = CarouselHalamanDepan::tersimpan();
        $this->assertSame(['Foto <b>satu</b>', 'Foto dua'], array_column($daftar, 'keterangan'));

        // Daftar di halaman pengaturan, teks di-escape.
        $halaman = $this->bukaSebagaiAdmin(self::ALAMAT);
        $halaman->assertDontSee('Foto <b>satu</b>');
        $xpath = $this->xpathDari($halaman);
        $this->assertSame(2, $xpath->query('//section[@id="carousel"]//li[@data-foto-carousel]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="carousel"]//form[@data-tambah-carousel]//input[@type="file"][@name="foto"]')->length);

        // Turunkan foto pertama.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat . '/1/turun', ['berkas' => $daftar[0]['berkas']]), $tujuan);
        $this->assertSame(['Foto dua', 'Foto <b>satu</b>'], array_column(CarouselHalamanDepan::tersimpan(), 'keterangan'));

        // Tindakan dari halaman lama (berkas tidak cocok) ditolak.
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin($alamat . '/1/hapus', ['berkas' => $daftar[0]['berkas']]), $tujuan);
        $this->assertCount(2, CarouselHalamanDepan::tersimpan());

        // Hapus foto beserta berkasnya.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin($alamat . '/2/hapus', ['berkas' => $daftar[0]['berkas']]), $tujuan);
        $this->assertSame(['Foto dua'], array_column(CarouselHalamanDepan::tersimpan(), 'keterangan'));
        $this->assertFalse((new Unggahan())->ada($daftar[0]['berkas']));
        $this->assertTrue((new Unggahan())->ada($daftar[1]['berkas']));
    }

    public function testTambahFotoDitolakDenganPesanRamah(): void
    {
        $alamat = self::ALAMAT . '/carousel';
        $tujuan = self::ALAMAT . '#carousel';

        // Tanpa foto.
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin($alamat, ['keterangan' => 'Tanpa foto']), $tujuan, 'Pilih foto yang akan ditambahkan dulu.');

        // Bukan gambar.
        $this->siapkanBerkas('foto', 'bukan.jpg', self::isiPdf());
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin($alamat, ['keterangan' => 'PDF']), $tujuan);

        // Batas jumlah foto.
        CarouselHalamanDepan::simpan(array_fill(0, CarouselHalamanDepan::BATAS, ['berkas' => 'unggahan/halaman-depan/' . str_repeat('b', 32) . '.webp', 'keterangan' => '']));
        $this->siapkanBerkas('foto', 'foto.jpg', self::isiJpg());
        $this->assertGagalDialihkan($this->kirimSebagaiAdmin($alamat, ['keterangan' => 'Kebanyakan']), $tujuan);
        service('superglobals')->setFilesArray([]);

        $this->assertCount(CarouselHalamanDepan::BATAS, CarouselHalamanDepan::tersimpan());
        $this->assertSame([], $this->isiFolderUnggahan());
    }

    public function testGeserFotoMurni(): void
    {
        $daftar = [['berkas' => 'a', 'keterangan' => '1'], ['berkas' => 'b', 'keterangan' => '2'], ['berkas' => 'c', 'keterangan' => '3']];

        $this->assertSame(['3', '1', '2'], array_column(CarouselHalamanDepan::geser($daftar, 2, 0), 'keterangan'));
        $this->assertSame(['2', '1', '3'], array_column(CarouselHalamanDepan::geser($daftar, 0, 1), 'keterangan'));
        $this->assertSame($daftar, CarouselHalamanDepan::geser($daftar, 0, 5), 'Posisi di luar daftar diabaikan.');
    }

    private function simpanFotoUji(): string
    {
        $jalur = 'unggahan/halaman-depan/' . bin2hex(random_bytes(16)) . '.png';
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, self::isiPng());

        return $jalur;
    }
}
