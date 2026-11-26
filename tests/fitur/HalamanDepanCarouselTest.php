<?php

use App\Libraries\CarouselHalamanDepan;
use App\Libraries\Unggahan;
use App\Libraries\VarianFoto;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Carousel foto pembuka halaman depan (F9-03, F10-06): foto bawaan dari
 * `data:awal` dan `halaman-depan:carousel`, tampilan slideshow hero, salinan
 * kecil `srcset` (`VarianFoto`), dan pengelolaan foto di Pengaturan > Halaman
 * Depan. Keterangan foto karangan.
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

    public function testFotoBawaanLamaDigantiTanpaMenyentuhUnggahanAdmin(): void
    {
        // K191: dua salinan foto bawaan lama mengapit foto unggahan admin. Sidik lama
        // diganti sidik berkas karangan, karena berkas aslinya tidak ada lagi di repo.
        $folder = $this->folderUnggahan . CarouselHalamanDepan::FOLDER;
        mkdir($folder, 0755, true);
        $lama = ['unggahan/halaman-depan/' . str_repeat('a', 32) . '.webp', 'unggahan/halaman-depan/' . str_repeat('c', 32) . '.webp'];
        $admin = 'unggahan/halaman-depan/' . str_repeat('b', 32) . '.webp';
        file_put_contents($this->jalurUnggahan($lama[0]), 'foto lama satu');
        file_put_contents($this->jalurUnggahan($lama[1]), 'foto lama dua');
        file_put_contents($this->jalurUnggahan($admin), 'foto admin');
        $sidik = [sha1('foto lama satu'), sha1('foto lama dua')];

        CarouselHalamanDepan::simpan([
            ['berkas' => $admin, 'keterangan' => 'Foto admin'],
            ['berkas' => $lama[0], 'keterangan' => 'Lama satu'],
            ['berkas' => $lama[1], 'keterangan' => 'Lama dua'],
        ]);
        $this->assertSame(2, CarouselHalamanDepan::jumlahBawaanLama(null, $sidik));
        $this->assertSame(0, CarouselHalamanDepan::jumlahBawaanLama(), 'Berkas karangan bukan foto bawaan lama yang asli.');

        $hasil = CarouselHalamanDepan::gantiBawaanLama(null, $sidik);
        $this->assertSame(['dipasang' => count(CarouselHalamanDepan::BAWAAN), 'dihapus' => 2, 'gagal' => 0], $hasil);

        // Foto admin tetap di depan; foto baru menempati tempat foto lama pertama.
        $daftar = CarouselHalamanDepan::tersimpan();
        $this->assertSame(['Foto admin', ...array_values(CarouselHalamanDepan::BAWAAN)], array_column($daftar, 'keterangan'));
        $this->assertSame($admin, $daftar[0]['berkas']);
        $this->assertFileExists($this->jalurUnggahan($admin));
        $this->assertFileDoesNotExist($this->jalurUnggahan($lama[0]));
        $this->assertFileDoesNotExist($this->jalurUnggahan($lama[1]));
        $this->assertSame(0, CarouselHalamanDepan::jumlahBawaanLama(null, $sidik));

        // Diulang: tidak ada lagi yang diganti.
        $this->assertNull(CarouselHalamanDepan::gantiBawaanLama(null, $sidik));
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $this->assertStringContainsString('sudah diatur admin', $this->getStreamFilterBuffer());
        $this->assertSame($daftar, CarouselHalamanDepan::tersimpan());
    }

    public function testFotoBaruMengikutiBatasCarousel(): void
    {
        $folder = $this->folderUnggahan . CarouselHalamanDepan::FOLDER;
        mkdir($folder, 0755, true);
        $daftar = [];

        // 18 foto admin dan 1 foto lama: hanya 2 foto baru yang muat dari batas 20.
        for ($i = 0; $i < CarouselHalamanDepan::BATAS - 2; $i++) {
            $berkas = 'unggahan/halaman-depan/' . str_pad((string) $i, 32, 'd', STR_PAD_LEFT) . '.webp';
            file_put_contents($this->jalurUnggahan($berkas), 'admin ' . $i);
            $daftar[] = ['berkas' => $berkas, 'keterangan' => 'Admin ' . $i];
        }

        $lama = 'unggahan/halaman-depan/' . str_repeat('e', 32) . '.webp';
        file_put_contents($this->jalurUnggahan($lama), 'foto lama');
        $daftar[] = ['berkas' => $lama, 'keterangan' => 'Lama'];
        CarouselHalamanDepan::simpan($daftar);

        $this->assertSame(['dipasang' => 2, 'dihapus' => 1, 'gagal' => 0], CarouselHalamanDepan::gantiBawaanLama(null, [sha1('foto lama')]));
        $this->assertCount(CarouselHalamanDepan::BATAS, CarouselHalamanDepan::tersimpan());
    }

    public function testFotoBawaanBaruBukanFotoLama(): void
    {
        $this->assertCount(9, CarouselHalamanDepan::BAWAAN);
        $this->assertCount(11, CarouselHalamanDepan::SIDIK_BAWAAN_LAMA);

        foreach (array_keys(CarouselHalamanDepan::BAWAAN) as $berkas) {
            $this->assertFileExists(ROOTPATH . 'data/' . $berkas);
            $this->assertNotContains(sha1_file(ROOTPATH . 'data/' . $berkas), CarouselHalamanDepan::SIDIK_BAWAAN_LAMA, $berkas);
        }
    }

    public function testCarouselDiPembuka(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('halaman-depan:carousel', []));
        $jumlah = count(CarouselHalamanDepan::BAWAAN);

        $hasil = $this->get('/');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        // Slideshow sendiri (F10-06), bukan carousel Flowbite.
        $carousel = $xpath->query('//section[@id="pembuka"]//*[@id="carousel-beranda"][@data-carousel-beranda]');
        $this->assertSame(1, $carousel->length);
        $this->assertSame('carousel', $carousel->item(0)->getAttribute('aria-roledescription'));
        $this->assertSame(0, $xpath->query('//*[@data-carousel]')->length);

        $gambar = $xpath->query('//*[@data-carousel-item]/img');
        $this->assertSame($jumlah, $gambar->length);
        $this->assertSame(array_values(CarouselHalamanDepan::BAWAAN), array_map(static fn (DOMElement $img): string => $img->getAttribute('alt'), iterator_to_array($gambar)));

        // Foto pertama dimuat lebih dulu; sisanya tersembunyi dan menunggu skrip menyiapkannya.
        $this->assertSame('high', $gambar->item(0)->getAttribute('fetchpriority'));
        $this->assertFalse($gambar->item(0)->hasAttribute('loading'));
        $slide = $xpath->query('//*[@data-carousel-item]');
        $this->assertSame('active', $slide->item(0)->getAttribute('data-carousel-item'));
        $this->assertSame('Foto 1 dari ' . $jumlah, $slide->item(0)->getAttribute('aria-label'));
        $this->assertStringNotContainsString('hidden', $slide->item(0)->getAttribute('class'));
        for ($i = 1; $i < $jumlah; $i++) {
            $this->assertSame('lazy', $gambar->item($i)->getAttribute('loading'), 'Foto ' . ($i + 1));
            $this->assertContains('hidden', explode(' ', $slide->item($i)->getAttribute('class')), 'Foto ' . ($i + 1));
        }

        // Semua foto ber-srcset 640/960/asli; salinannya ada di folder unggahan.
        foreach ($gambar as $img) {
            $this->assertMatchesRegularExpression('#/varian/[a-f0-9]{32}-640\.webp 640w, \S+/varian/[a-f0-9]{32}-960\.webp 960w, \S+/[a-f0-9]{32}\.webp \d{3,4}w$#', $img->getAttribute('srcset'));
            $this->assertSame('(min-width: 1024px) 66vw, 100vw', $img->getAttribute('sizes'));
        }

        // Keterangan foto kecil di pojok, tidak dibaca dua kali.
        $keterangan = $xpath->query('//*[@data-carousel-item]/figcaption[@data-keterangan-foto]');
        $this->assertSame($jumlah, $keterangan->length);
        $this->assertSame('true', $keterangan->item(0)->getAttribute('aria-hidden'));
        $this->assertSame(array_values(CarouselHalamanDepan::BAWAAN)[0], trim($keterangan->item(0)->textContent));

        // Hanya titik penanda (F10-06): tanpa tombol maju/mundur dan jeda.
        $titik = $xpath->query('//*[@data-indikator-carousel]/button[@data-carousel-slide-to]');
        $this->assertSame($jumlah, $titik->length);
        $this->assertSame('Foto 2 dari ' . $jumlah, $titik->item(1)->getAttribute('aria-label'));
        $this->assertSame(['true', 'false'], [$titik->item(0)->getAttribute('aria-current'), $titik->item(1)->getAttribute('aria-current')]);
        $this->assertSame(0, $xpath->query('//*[@data-carousel-prev or @data-carousel-next or @data-jeda-carousel]')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "js/carousel-beranda")]')->length);

        // Judul dan dua tombol di hero, keadaan di pita fakta tepat di bawahnya (K81, F10-06).
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]/*[@data-pembuka-hero]//h1[@id="judul-acara"]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]/*[@data-pembuka-hero]//nav[@data-tombol-utama]')->length);
        $this->assertSame(1, $xpath->query('//section[@id="pembuka"]/*[@data-pembuka-hero]/following-sibling::*[1][@data-pembuka-info]//*[@data-keadaan]')->length);

        // Foto ada: tanpa adegan Three.js.
        $this->assertSame(0, $xpath->query('//*[@data-adegan-merek]')->length);
    }

    public function testSalinanKecilDibuatSekaliDanIkutTerhapus(): void
    {
        $besar = $this->simpanFotoUji(1000, 500);
        $kecil = $this->simpanFotoUji();
        CarouselHalamanDepan::simpan([
            ['berkas' => $besar, 'keterangan' => 'Atlet karangan melompat'],
            ['berkas' => $kecil, 'keterangan' => 'Atlet karangan berlari'],
        ]);

        $gambar = $this->xpathDari($this->get('/'))->query('//*[@data-carousel-item]/img');
        $nama   = pathinfo($besar, PATHINFO_FILENAME);
        $this->assertSame(
            base_url('unggahan/halaman-depan/varian/' . $nama . '-640.webp') . ' 640w, ' . base_url('unggahan/halaman-depan/varian/' . $nama . '-960.webp') . ' 960w, ' . base_url($besar) . ' 1000w',
            $gambar->item(0)->getAttribute('srcset'),
        );
        $this->assertFalse($gambar->item(1)->hasAttribute('srcset'), 'Foto yang lebih kecil dari 640 piksel tidak punya salinan.');

        $varian = [];

        foreach (VarianFoto::LEBAR as $lebar) {
            $varian[$lebar] = $this->jalurUnggahan(VarianFoto::jalurRelatif($besar, $lebar));
            $ukuran         = getimagesize($varian[$lebar]);
            $this->assertNotFalse($ukuran);
            $this->assertSame([$lebar, $lebar / 2, IMAGETYPE_WEBP], [$ukuran[0], $ukuran[1], $ukuran[2]]);
        }

        // Dibuat sekali: permintaan berikutnya memakai salinan yang sama.
        $waktu = filemtime($varian[640]);
        touch($varian[640], $waktu - 60);
        clearstatcache();
        touch($this->jalurUnggahan($besar), $waktu - 120);
        $this->get('/');
        clearstatcache();
        $this->assertSame($waktu - 60, filemtime($varian[640]));

        // Admin menghapus foto: salinannya ikut terhapus.
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/carousel/1/hapus', ['berkas' => $besar]), self::ALAMAT . '#carousel');
        foreach ($varian as $jalur) {
            $this->assertFileDoesNotExist($jalur);
        }
        $this->assertNull(VarianFoto::srcset('unggahan/halaman-depan/' . str_repeat('e', 32) . '.webp'), 'Foto yang tidak ada tanpa srcset.');
    }

    // ---------- Logo kepala kecil (F10-07) ----------

    /**
     * Logo persegi unggahan yang besar diganti salinan 160 piksel di kepala
     * tata letak publik, dan salinannya ikut terhapus bersama logonya.
     */
    public function testLogoKepalaMemakaiSalinanKecil(): void
    {
        $logo = $this->simpanFotoUji(600, 600);
        setting('HalamanDepan.logo_persegi', $logo);

        $src    = $this->xpathDari($this->get('unduhan'))->query('//a[@data-merek]/img')->item(0)?->getAttribute('src');
        $varian = VarianFoto::jalurRelatif($logo, 160);
        $this->assertSame(base_url($varian), $src);
        $ukuran = getimagesize($this->jalurUnggahan($varian));
        $this->assertNotFalse($ukuran);
        $this->assertSame([160, 160], [$ukuran[0], $ukuran[1]]);

        // Logo yang sudah kecil dipakai apa adanya.
        $this->assertNull(VarianFoto::kecil($this->simpanFotoUji(100, 100), 160));

        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT, ['hapus_logo_persegi' => '1']), self::ALAMAT);
        $this->assertFileDoesNotExist($this->jalurUnggahan($varian));
        $this->assertFileDoesNotExist($this->jalurUnggahan($logo));
    }

    // ---------- Akhir logo kepala kecil ----------

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

    public function testFotoUnggahanDikecilkanKe1600Piksel(): void
    {
        // Foto mendatar 2400 × 1200 piksel (karangan, dibuat GD).
        $gambar = imagecreatetruecolor(2400, 1200);
        imagefill($gambar, 0, 0, imagecolorallocate($gambar, 35, 73, 135));
        ob_start();
        imagejpeg($gambar, null, 80);
        $isi = (string) ob_get_clean();

        $this->siapkanBerkas('foto', 'besar.jpg', $isi);
        $this->assertBerhasilDialihkan($this->kirimSebagaiAdmin(self::ALAMAT . '/carousel', ['keterangan' => 'Foto besar']), self::ALAMAT . '#carousel');
        service('superglobals')->setFilesArray([]);

        $jalur  = CarouselHalamanDepan::tersimpan()[0]['berkas'];
        $ukuran = getimagesize((string) (new Unggahan())->jalurPenuh($jalur));
        $this->assertNotFalse($ukuran);
        $this->assertSame([CarouselHalamanDepan::SISI, 800], [$ukuran[0], $ukuran[1]], 'Sama dengan foto bawaan (K143).');
        $this->assertSame(1920, Unggahan::SISI_GAMBAR, 'Aturan gambar lain tidak berubah.');
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

    /**
     * Simpan foto uji: PNG 1×1 bawaan, atau WebP polos selebar `$lebar` piksel.
     */
    private function simpanFotoUji(int $lebar = 0, int $tinggi = 0): string
    {
        $jalur = 'unggahan/halaman-depan/' . bin2hex(random_bytes(16)) . ($lebar > 0 ? '.webp' : '.png');
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        if ($lebar > 0) {
            $gambar = imagecreatetruecolor($lebar, $tinggi);
            imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 35, 73, 135));
            imagewebp($gambar, $penuh, 80);
            imagedestroy($gambar);
        } else {
            file_put_contents($penuh, self::isiPng());
        }

        return $jalur;
    }
}
