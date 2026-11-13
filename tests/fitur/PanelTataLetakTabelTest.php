<?php

use Tests\Support\UjiHalaman;

/**
 * Aset tabel data panel (Tahap 6, F6-01): DataTables 2 disimpan lokal dan
 * hanya dimuat di halaman admin yang mengisi bagian `tabel`.
 *
 * @internal
 */
final class PanelTataLetakTabelTest extends UjiHalaman
{
    private const BERKAS_VENDOR = [
        'vendor/datatables/js/dataTables.min.js',
        'vendor/datatables/js/dataTables.bootstrap5.min.js',
        'vendor/datatables/css/dataTables.bootstrap5.min.css',
        'vendor/datatables/LICENSE',
        'vendor/jquery/jquery.min.js',
        'vendor/jquery/LICENSE',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = self::SESI_ADMIN;
    }

    public function testBerkasVendorAdaBesertaLisensinya(): void
    {
        foreach (self::BERKAS_VENDOR as $berkas) {
            $this->assertFileExists(FCPATH . 'aset/' . $berkas);
            $this->assertGreaterThan(0, filesize(FCPATH . 'aset/' . $berkas), $berkas);
        }

        $this->assertStringContainsString('MIT', (string) file_get_contents(FCPATH . 'aset/vendor/datatables/LICENSE'));
        $this->assertStringContainsString('DataTables 2.3.8', (string) file_get_contents(FCPATH . 'aset/vendor/datatables/js/dataTables.min.js'));
        $this->assertStringContainsString('jQuery v3.7.1', (string) file_get_contents(FCPATH . 'aset/vendor/jquery/jquery.min.js'));

        $readme = (string) file_get_contents(FCPATH . 'aset/vendor/README.md');
        $this->assertStringContainsString('datatables.net-2.3.8.tgz', $readme);
        $this->assertStringContainsString('jquery-3.7.1.tgz', $readme);
    }

    public function testHalamanTanpaTabelTidakMemuatDataTables(): void
    {
        $html = view('Tests\Support\Views\contoh_admin');

        $this->assertStringNotContainsString('datatables', strtolower($html));
        $this->assertStringNotContainsString('jquery', $html);
        $this->assertStringNotContainsString('tabel-admin.js', $html);
    }

    public function testHalamanDenganTabelMemuatAsetLokalBerurutan(): void
    {
        $html = view('Tests\Support\Views\PanelContohTabel');

        $css = '<link rel="stylesheet" href="' . aset('vendor/datatables/css/dataTables.bootstrap5.min.css') . '">';
        $this->assertStringContainsString($css, $html);
        $this->assertLessThan(strpos($html, '</head>'), strpos($html, $css), 'Gaya DataTables dimuat di kepala halaman.');

        $urutan = [
            aset('vendor/bootstrap/js/bootstrap.bundle.min.js'),
            aset('js/kemajuan.js'),
            aset('vendor/jquery/jquery.min.js'),
            aset('vendor/datatables/js/dataTables.min.js'),
            aset('vendor/datatables/js/dataTables.bootstrap5.min.js'),
            aset('js/tabel-admin.js'),
        ];
        $posisi = array_map(static function (string $alamat) use ($html): int {
            $letak = strpos($html, '<script src="' . $alamat . '"></script>');

            return $letak === false ? -1 : $letak;
        }, $urutan);

        $this->assertNotContains(-1, $posisi, 'Semua skrip dimuat.');
        $urut = $posisi;
        sort($urut);
        $this->assertSame($urut, $posisi, 'Urutan skrip: Bootstrap, kemajuan, jQuery, DataTables, gaya Bootstrap 5, tabel-admin.');

        // Tanpa CDN: semua skrip dan gaya dari situs sendiri.
        preg_match_all('/(?:src|href)="([^"]+\.(?:js|css))"/', $html, $cocok);
        foreach ($cocok[1] as $alamat) {
            $this->assertStringStartsWith(base_url('aset/'), $alamat);
        }
    }

    public function testTabelMemakaiAtributData(): void
    {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8">' . view('Tests\Support\Views\PanelContohTabel'));
        libxml_clear_errors();
        $xpath = new DOMXPath($dom);

        $tabel = $xpath->query('//table[@data-tabel-admin]')->item(0);
        $this->assertNotNull($tabel);
        $this->assertSame(site_url('admin/pendaftaran/data'), $tabel->getAttribute('data-alamat'));
        $this->assertSame(1, $xpath->query('//form[@id="saringan-contoh"]')->length);
        $this->assertSame(1, $xpath->query('//a[@id="excel-contoh"]')->length);
        $this->assertSame(
            ['id_pendaftaran', 'nama_sekolah', 'total', 'status'],
            array_map(static fn (DOMElement $th): string => $th->getAttribute('data-kolom'), iterator_to_array($xpath->query('//table//th'))),
        );
    }

    public function testSkripTabelAdmin(): void
    {
        $js = (string) file_get_contents(FCPATH . 'aset/js/tabel-admin.js');

        // Bahasa Indonesia dan bilah kemajuan.
        foreach (['Sebelumnya', 'Berikutnya', 'Tampilkan _MENU_ baris', 'Kemajuan.mulai()', 'Kemajuan.selesai()', 'serverSide: true', "kueri.set('cari'"] as $potongan) {
            $this->assertStringContainsString($potongan, $js);
        }

        // Tidak memuat apa pun dari luar.
        $this->assertDoesNotMatchRegularExpression('#https?://#', $js);
    }

    public function testWilayahTahapEnamDiAdminCss(): void
    {
        $css = (string) file_get_contents(FCPATH . 'aset/css/admin.css');

        $this->assertStringContainsString('Tahap 6', $css);
        $this->assertStringContainsString('.tabel-admin-memuat', $css);
        $this->assertStringContainsString('.saringan-tabel', $css);
        // Bilah kemajuan dipakai admin dan publik, jadi gayanya di tema.css (K89).
        $this->assertStringContainsString('.bilah-kemajuan {', (string) file_get_contents(FCPATH . 'aset/css/tema.css'));
        $this->assertStringNotContainsString('.bilah-kemajuan {', $css);
    }
}
