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

        // Gaya DataTables dari kelas Tailwind di tabel-admin.js, tanpa CSS bawaan (F9-14, F9-17).
        $this->assertStringNotContainsString('dataTables.bootstrap5', $html);

        $urutan = [
            aset('js/kemajuan.js'),
            aset('vendor/jquery/jquery.min.js'),
            aset('vendor/datatables/js/dataTables.min.js'),
            aset('js/tabel-admin.js'),
        ];
        $posisi = array_map(static function (string $alamat) use ($html): int {
            $letak = strpos($html, '<script src="' . $alamat . '"></script>');

            return $letak === false ? -1 : $letak;
        }, $urutan);

        $this->assertNotContains(-1, $posisi, 'Semua skrip dimuat.');
        $urut = $posisi;
        sort($urut);
        $this->assertSame($urut, $posisi, 'Urutan skrip: kemajuan, jQuery, DataTables, tabel-admin.');

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

    public function testGayaBootstrapDataTablesDilepas(): void
    {
        $this->assertFileDoesNotExist(FCPATH . 'aset/vendor/datatables/js/dataTables.bootstrap5.min.js');
        $this->assertFileDoesNotExist(FCPATH . 'aset/vendor/datatables/css/dataTables.bootstrap5.min.css');
        $this->assertFileDoesNotExist(FCPATH . 'aset/css/admin.css');

        // Skeleton dan peredupan memakai kelas Tailwind (F9-17).
        $js = (string) file_get_contents(FCPATH . 'aset/js/tabel-admin.js');
        $this->assertStringContainsString('data-baris-kerangka', $js);
        $this->assertStringContainsString("'opacity-60'", $js);
        $this->assertStringNotContainsString('bi bi-', $js);
    }
}
