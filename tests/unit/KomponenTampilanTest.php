<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Fondasi tampilan Tahap 9 (F9-01, K140, K141): pembantu `ikon()`,
 * `atribut_html()`, potongan `app/Views/komponen/`, sprite Lucide yang lengkap,
 * dan berkas hasil bangun yang di-commit (server tanpa Node).
 *
 * @internal
 */
final class KomponenTampilanTest extends CIUnitTestCase
{
    public function testIkonMemakaiSpriteLokalDanTersembunyiTanpaLabel(): void
    {
        $svg = ikon('search', 'size-6');

        $this->assertMatchesRegularExpression('#<use href="[^"]*aset/vendor/lucide/lucide\.svg(\?v=\d+)?\#search">#', $svg);
        $this->assertStringContainsString('class="ikon size-6"', $svg);
        $this->assertStringContainsString('aria-hidden="true"', $svg);

        $berlabel = ikon('x', '', 'Tutup "menu"');
        $this->assertStringContainsString('role="img" aria-label="Tutup &quot;menu&quot;"', $berlabel);
        $this->assertStringNotContainsString('aria-hidden', $berlabel);
    }

    public function testAtributHtmlMenolakStyleDanOnDanMengescape(): void
    {
        $teks = atribut_html([
            'data-modal-target' => 'ubah',
            'disabled'          => true,
            'hidden'            => false,
            'title'             => null,
            'style'             => 'color:red',
            'onclick'           => 'alert(1)',
            'aria-label'        => 'A "B" <C>',
            'bad name'          => 'x',
        ]);

        $this->assertSame(' data-modal-target="ubah" disabled aria-label="A &quot;B&quot; &lt;C&gt;"', $teks);
    }

    /**
     * Komponen tidak mewarisi data halaman yang sedang dirender: parameter
     * opsional yang tidak diberikan tetap memakai nilai bawaannya.
     */
    public function testKomponenTidakMewarisiDataPenyajiView(): void
    {
        service('renderer')->setData(['ikon' => 'trophy', 'atribut' => [['bersarang']], 'kelas' => 'bocor', 'href' => '/bocor']);

        try {
            $html = komponen('tombol', ['label' => 'Simpan']);
        } finally {
            service('renderer')->resetData();
        }

        $this->assertStringContainsString('<button type="button"', $html);
        $this->assertStringNotContainsString('trophy', $html);
        $this->assertStringNotContainsString('bocor', $html);
    }

    public function testKomponenTidakDikenalDitolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        komponen('../tata_letak/admin');
    }

    public function testTombolMenjadiTautanAtauTombol(): void
    {
        $tautan = komponen('tombol', ['label' => 'Daftar <sekarang>', 'href' => '/pendaftaran', 'ikon' => 'arrow-right', 'ikonKanan' => true, 'varian' => 'oranye']);
        $this->assertMatchesRegularExpression('#<a href="/pendaftaran" class="[^"]*"#', $tautan);
        $this->assertStringContainsString('tombol-utama-berart tombol-berart-oranye', $tautan, 'Warna dan latar berart dari kelas F10-05.');
        $this->assertStringContainsString('min-h-11', $tautan, 'Target sentuh paling kecil 44 piksel.');
        $this->assertStringContainsString('Daftar &lt;sekarang&gt;<svg', $tautan, 'Label di-escape, ikon di kanan.');

        $tombol = komponen('tombol', ['label' => 'Simpan', 'nonaktif' => true, 'atribut' => ['data-x' => '1', 'onclick' => 'jahat()']]);
        $this->assertStringContainsString('<button type="button"', $tombol);
        $this->assertStringContainsString(' data-x="1" disabled', $tombol);
        $this->assertStringNotContainsString('onclick', $tombol);

        $this->assertStringContainsString('type="submit"', komponen('tombol', ['label' => 'Kirim', 'tipe' => 'submit']));
        $this->assertMatchesRegularExpression('#class="[^"]*tombol-utama-berart focus:ring-primary-300#', $tombol, 'Varian utama memakai tombol berart (F10-05).');
        $this->assertStringNotContainsString('tombol-utama-berart', komponen('tombol', ['label' => 'Batal', 'varian' => 'kedua']));
    }

    /**
     * Tombol unduh ikon saja (F11-10, K174 butir 8): nama aksesibel lengkap,
     * label kecil hanya hiasan, ikon lembar kerja atau PDF.
     */
    public function testTombolUnduhIkonSajaBernamaAksesibel(): void
    {
        $excel = komponen('tombol_unduh', ['jenis' => 'excel', 'href' => '/admin/atlet/excel', 'atribut' => ['id' => 'excel-atlet']]);
        $this->assertStringContainsString('aria-label="Unduh Excel"', $excel);
        $this->assertStringContainsString('id="excel-atlet"', $excel);
        $this->assertStringContainsString('data-tombol-unduh="excel"', $excel);
        $this->assertStringContainsString('#file-spreadsheet', $excel);
        $this->assertMatchesRegularExpression('#<span[^>]*aria-hidden="true">Unduh Excel</span>#', $excel);

        $pdf = komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => '/x.pdf', 'label' => 'Unduh PDF daftar seri <Sabtu>']);
        $this->assertStringContainsString('aria-label="Unduh PDF daftar seri &lt;Sabtu&gt;"', $pdf);
        $this->assertStringContainsString('data-ikon-pdf', $pdf);
        $this->assertMatchesRegularExpression('#<span[^>]*aria-hidden="true">Unduh PDF</span>#', $pdf);
        // Tanpa teks terlihat selain label kecil yang tersembunyi dari pembaca layar.
        $this->assertSame('PDF Unduh PDF', trim((string) preg_replace('/\s+/', ' ', strip_tags($pdf))));

        // Label kecil tertambat di tepi kanan secara bawaan; deret rata tengah hasil publik memakai `tengah` (F11-13).
        $this->assertMatchesRegularExpression('#<span class="[^"]*\bend-0\b[^"]*" aria-hidden="true">#', $excel);
        $tengah = komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => '/x.pdf', 'tambat' => 'tengah']);
        $this->assertMatchesRegularExpression('#<span class="[^"]*left-1/2 -translate-x-1/2[^"]*" aria-hidden="true">#', $tengah);
        $this->assertStringNotContainsString(' end-0 ', $tengah);
        $this->assertStringContainsString('start-0', komponen('tombol_unduh', ['jenis' => 'pdf', 'href' => '/x.pdf', 'tambat' => 'awal']));
    }

    public function testIsianMenandaiGalatUntukPembacaLayar(): void
    {
        $html = komponen('isian', ['label' => 'NISN', 'nama' => 'nisn', 'nilai' => '12"3', 'wajib' => true, 'bantuan' => '10 angka.', 'galat' => 'NISN harus 10 angka.']);

        $this->assertStringContainsString('for="nisn"', $html);
        $this->assertStringContainsString('value="12&quot;3"', $html);
        $this->assertStringContainsString(' required aria-invalid="true" aria-describedby="nisn-bantuan nisn-galat"', $html);
        $this->assertStringContainsString('id="nisn-galat"', $html);
        $this->assertStringContainsString('NISN harus 10 angka.', $html);

        $tanpaGalat = komponen('isian', ['label' => 'Nama', 'nama' => 'nama']);
        $this->assertStringNotContainsString('aria-invalid', $tanpaGalat);
        $this->assertStringNotContainsString('aria-describedby', $tanpaGalat);
    }

    public function testPilihanDanSakelar(): void
    {
        $pilihan = komponen('pilihan', ['label' => 'Kabupaten', 'nama' => 'kab', 'opsi' => ['pati' => 'Kab. Pati', 'kudus' => 'Kab. Kudus'], 'nilai' => 'kudus', 'kosong' => 'Pilih']);
        $this->assertStringContainsString('<option value="">Pilih</option>', $pilihan);
        $this->assertStringContainsString('<option value="kudus" selected>Kab. Kudus</option>', $pilihan);

        $sakelar = komponen('sakelar', ['label' => 'Tampilkan', 'nama' => 'tampil', 'centang' => true]);
        $this->assertStringContainsString('<input type="hidden" name="tampil" value="0">', $sakelar, 'Nilai mati ikut terkirim.');
        $this->assertMatchesRegularExpression('/<input type="checkbox" id="tampil" name="tampil" value="1"[^>]* checked/', $sakelar);
    }

    public function testTabelMengescapeSelKecualiHtml(): void
    {
        $html = komponen('tabel', ['judul' => 'Hasil', 'kepala' => ['Nama', 'Prestasi'], 'baris' => [['<b>A</b>', ['html' => '<em>8,21</em>']]]]);

        $this->assertStringContainsString('<caption class="sr-only">Hasil</caption>', $html);
        $this->assertStringContainsString('&lt;b&gt;A&lt;/b&gt;', $html);
        $this->assertStringContainsString('<em>8,21</em>', $html);
        $this->assertStringContainsString('Belum ada data.', komponen('tabel', ['kepala' => ['Nama'], 'baris' => []]));
    }

    public function testKeadaanKosongMenyembunyikanGambarYangBelumAda(): void
    {
        $html = komponen('kosong', ['gambar' => 'gambar/keadaan/tidak-ada-berkas-ini.webp', 'ikon' => 'trophy', 'judul' => 'Hasil belum terbit']);

        $this->assertStringNotContainsString('<img', $html, 'K41: gambar yang belum ada tidak tampil sebagai kotak kosong.');
        $this->assertStringContainsString('#trophy', $html);
    }

    public function testLangkahMenandaiLangkahSekarang(): void
    {
        $html = komponen('langkah', ['daftar' => ['Sekolah', 'Ofisial', 'Atlet'], 'aktif' => 2]);

        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        $this->assertStringContainsString('aria-label="Selesai"', $html);
    }

    public function testKerangkaDibacakanSebagaiMemuat(): void
    {
        foreach (['teks', 'kartu', 'tabel', 'gambar'] as $jenis) {
            $html = komponen('kerangka', ['jenis' => $jenis]);
            $this->assertStringContainsString('role="status"', $html, $jenis);
            $this->assertStringContainsString('Memuat data…', $html, $jenis);
        }
    }

    /**
     * Setiap ikon yang ditulis di view baru ada di sprite. Bila gagal:
     * jalankan `npm run ikon` lalu `npm run css` dan commit hasilnya.
     */
    public function testSemuaIkonYangDipakaiAdaDiSprite(): void
    {
        $sprite = (string) file_get_contents(FCPATH . 'aset/vendor/lucide/lucide.svg');
        preg_match_all('/<symbol id="([a-z0-9-]+)"/', $sprite, $ada);
        $ada = array_flip($ada[1]);

        $dipakai = [];
        $berkas  = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(APPPATH . 'Views', FilesystemIterator::SKIP_DOTS));

        foreach ([...iterator_to_array($berkas, false), new SplFileInfo(APPPATH . 'Helpers/tampilan_helper.php')] as $b) {
            if ($b->getExtension() !== 'php') {
                continue;
            }

            $isi = (string) preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($b->getPathname()));

            if (preg_match('/(?<![$\w>:])ikon\(/', $isi) !== 1) {
                continue;
            }

            preg_match_all('/(?<![$\w>:])ikon\(\s*[\'"]([a-z0-9-]+)[\'"]|[\'"]ikon[\'"]\s*=>\s*[\'"]([a-z0-9-]+)[\'"]/', $isi, $cocok, PREG_SET_ORDER);

            foreach ($cocok as $c) {
                $dipakai[$c[2] ?? '' ?: $c[1]] = $b->getFilename();
            }
        }

        $this->assertNotEmpty($dipakai);

        foreach ($dipakai as $nama => $berkasPemakai) {
            $this->assertArrayHasKey($nama, $ada, "Ikon \"{$nama}\" ({$berkasPemakai}) belum ada di sprite: jalankan npm run ikon.");
        }
    }

    public function testBerkasHasilBangunDiCommit(): void
    {
        foreach ([
            'css/tw.css',
            'vendor/flowbite/flowbite.min.js',
            'vendor/three/three.module.min.js',
            'vendor/three/three.core.min.js',
            'vendor/inter/inter-latin-wght-normal.woff2',
            'vendor/lucide/lucide.svg',
        ] as $jalur) {
            $this->assertFileExists(FCPATH . 'aset/' . $jalur, "{$jalur} hilang: jalankan npm run bangun.");
        }

        $css = (string) file_get_contents(FCPATH . 'aset/css/tw.css');
        $this->assertStringContainsString('--color-primary-700: #234987', $css, 'Warna utama Flowbite = biru MilkLife.');
        $this->assertStringContainsString('.ikon', $css);
        $this->assertStringNotContainsString('fonts.googleapis.com', $css, 'Huruf lokal, tanpa Google Fonts.');
    }
}
