<?php

use App\Controllers\Admin\Pengaturan\AcaraController;
use Tests\Support\KirimFormulir;
use Tests\Support\UjiBasisData;

/**
 * Judul tab panel admin dan halaman masuk mengikuti nama acara dari
 * pengaturan (F3-04), bukan tulisan "MilkLife" langsung.
 *
 * Halaman cukup menulis nama halamannya di bagian `judul`; tata letak
 * `admin` menambahkan " – Admin <nama acara>", tata letak `polos`
 * menambahkan " – <nama acara>".
 *
 * @internal
 */
final class JudulTabAdminTest extends UjiBasisData
{
    use KirimFormulir;

    private const NAMA = 'Lomba Lari Karangan';

    /**
     * Halaman admin (`GET`) => nama halaman di judul tab.
     */
    private const HALAMAN = [
        'admin/dasbor'                         => 'Dasbor',
        'admin/akun'                           => 'Akun',
        'admin/pengaturan'                     => 'Pengaturan',
        'admin/pengaturan/acara'               => 'Data Acara',
        'admin/pengaturan/pendaftaran'         => 'Pengaturan Pendaftaran',
        'admin/pengaturan/pembayaran'          => 'Pembayaran',
        'admin/pengaturan/halaman-depan'       => 'Halaman Depan',
        'admin/pengaturan/kelompok-umur'       => 'Kelompok Umur',
        'admin/pengaturan/nomor-lomba'         => 'Nomor Lomba',
        'admin/pengaturan/jadwal'              => 'Jadwal',
        'admin/pengaturan/jadwal/tambah'       => 'Tambah Jadwal',
        'admin/pengaturan/kontak'              => 'Kontak',
        'admin/pengaturan/unduhan'             => 'Unduhan',
        'admin/pengaturan/unduhan/tambah'      => 'Tambah Unduhan',
        'admin/pengaturan/kelompok-umur/tambah' => 'Tambah Kelompok Umur',
        'admin/pengaturan/nomor-lomba/tambah'  => 'Tambah Nomor Lomba',
    ];

    public function testJudulTabAdminMemakaiNamaAcara(): void
    {
        setting('Acara.nama', self::NAMA);

        foreach (self::HALAMAN as $alamat => $judul) {
            $hasil = $this->bukaSebagaiAdmin($alamat);
            $hasil->assertOK();

            $this->assertSame($judul . ' – Admin ' . self::NAMA, $this->judulTab($hasil->getBody()), "Judul tab {$alamat}.");
            $this->assertStringNotContainsString('MilkLife', $hasil->getBody(), "Masih ada tulisan MilkLife di {$alamat}.");
        }
    }

    public function testNamaPenggantiSebelumNamaAcaraDiisi(): void
    {
        $hasil = $this->bukaSebagaiAdmin('admin/dasbor');

        $this->assertSame('Dasbor – Admin ' . AcaraController::NAMA_PENGGANTI, $this->judulTab($hasil->getBody()));

        $hasil = $this->withSession([])->get('admin/masuk');

        $this->assertSame('Masuk Admin – ' . AcaraController::NAMA_PENGGANTI, $this->judulTab($hasil->getBody()));
        $hasil->assertSee('Panel panitia ' . AcaraController::NAMA_PENGGANTI);
    }

    public function testHalamanMasukMemakaiNamaAcara(): void
    {
        setting('Acara.nama', self::NAMA);

        $hasil = $this->withSession([])->get('admin/masuk');
        $hasil->assertOK();

        $this->assertSame('Masuk Admin – ' . self::NAMA, $this->judulTab($hasil->getBody()));
        $hasil->assertSee('Panel panitia ' . self::NAMA);
        $hasil->assertDontSee('MilkLife');
    }

    public function testNamaAcaraDiEscape(): void
    {
        setting('Acara.nama', 'Lomba <b>Lari</b> & "Cepat"');

        foreach (['admin/dasbor' => true, 'admin/masuk' => false] as $alamat => $admin) {
            $hasil = $admin ? $this->bukaSebagaiAdmin($alamat) : $this->withSession([])->get($alamat);
            $html  = $hasil->getBody();

            $this->assertStringNotContainsString('<b>Lari</b>', $html, "Nama acara tidak di-escape di {$alamat}.");
            $this->assertStringContainsString('Lomba &lt;b&gt;Lari&lt;/b&gt; &amp; &quot;Cepat&quot;</title>', $html);
        }
    }

    public function testJudulHalamanDiKepalaTidakBerubah(): void
    {
        setting('Acara.nama', self::NAMA);

        $xpath = $this->xpathDari($this->bukaSebagaiAdmin('admin/pengaturan/acara'));

        $this->assertSame('Data Acara', trim($xpath->query('//header//h1')->item(0)?->textContent ?? ''));
    }

    public function testTampilanAdminTidakMenulisNamaAcaraDiJudul(): void
    {
        $folder = APPPATH . 'Views/admin';
        $berkas = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS));

        foreach ($berkas as $info) {
            if ($info->getExtension() !== 'php') {
                continue;
            }

            $isi = (string) file_get_contents($info->getPathname());

            if (preg_match("/section\\('judul'\\) \\?>(.*?)<\\?= \\\$this->endSection/s", $isi, $cocok) === 1) {
                $this->assertStringNotContainsString('MilkLife', $cocok[1], 'Judul di ' . $info->getPathname());
                $this->assertStringNotContainsString(' – Admin', $cocok[1], 'Judul di ' . $info->getPathname());
            }
        }
    }

    private function judulTab(string $html): string
    {
        $this->assertSame(1, preg_match('#<title>(.*?)</title>#s', $html, $cocok), 'Tidak ada <title>.');

        return html_entity_decode(trim($cocok[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
