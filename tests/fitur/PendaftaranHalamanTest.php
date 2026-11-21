<?php

use App\Controllers\PendaftaranController;
use Tests\Support\KirimFormulir;
use Tests\Support\PendaftaranContoh;
use Tests\Support\UjiBasisData;

/**
 * Halaman wizard `/pendaftaran` (F4-03): tertutup saat pendaftaran belum
 * terbuka (6.1), kerangka lima langkah, data JSON wizard, aset sendiri, dan
 * token CSRF untuk kiriman JSON.
 *
 * @internal
 */
final class PendaftaranHalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PendaftaranContoh;

    public function testTertutupSaatPengaturanBelumLengkap(): void
    {
        setting('Acara.nama', 'Lomba Lari Karangan');

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $this->assertSame('Pendaftaran – Lomba Lari Karangan', trim($xpath->query('//title')->item(0)->textContent));
        $this->assertSame(1, $xpath->query('//h1')->length);
        $this->assertSame(1, $xpath->query('//*[@data-pendaftaran-tertutup]')->length);
        $hasil->assertSee('Pendaftaran sedang disiapkan.');
        $this->assertSame(0, $xpath->query('//*[@id="wizard-pendaftaran"]')->length);
        $this->assertSame(0, $xpath->query('//script[@id="data-pendaftaran"]')->length);
        $hasil->assertDontSee('pendaftaran.js');
    }

    public function testTertutupSetelahJadwalLewat(): void
    {
        $this->bukaPendaftaranUji();
        setting('Pendaftaran.mulai', '2025-01-01 00:00:00');
        setting('Pendaftaran.akhir', '2025-02-01 00:00:00');

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();
        $hasil->assertSee('Pendaftaran sudah ditutup pada 1 Februari 2025.');
        $hasil->assertSee('Ditutup');
        $this->assertSame(0, $this->xpathDari($hasil)->query('//*[@id="wizard-pendaftaran"]')->length);
    }

    public function testWizardSaatTerbuka(): void
    {
        $id = $this->bukaPendaftaranUji();

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();
        $xpath = $this->xpathDari($hasil);

        $hasil->assertSee('Pendaftaran dibuka sampai 31 Desember 2099 pukul 23.59.');
        $this->assertSame(1, $xpath->query('//h1')->length);

        // Lima pil langkah dan lima bagian langkah.
        $pil = array_map(static fn (DOMNode $n): string => trim(preg_replace('/\s+/', ' ', $n->textContent)), iterator_to_array($xpath->query('//ol[contains(@class, "pil-langkah")]//*[contains(@class, "pil-judul")]')));
        $this->assertSame(['Data Sekolah', 'Ofisial', 'Atlet', 'Biaya', 'Pembayaran'], $pil);
        $this->assertSame(5, $xpath->query('//section[contains(@class, "langkah")][@data-langkah]')->length);

        // Pilihan jenjang dan lima kabupaten.
        $this->assertSame(3, $xpath->query('//input[@name="sekolah-jenjang"]')->length);
        $this->assertSame(6, $xpath->query('//select[@id="sekolah-kabupaten"]/option')->length);

        // Tampilan Tailwind (F9-05): tanpa Bootstrap dan tanpa CSS sendiri; token CSRF untuk kiriman JSON.
        $this->assertSame(1, $xpath->query('//head/link[contains(@href, "aset/css/tw.css")]')->length);
        $this->assertSame(0, $xpath->query('//head/link[contains(@href, "bootstrap") or contains(@href, "pendaftaran.css")]')->length);
        $this->assertSame(0, $xpath->query('//script[contains(@src, "bootstrap")]')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "aset/js/pendaftaran.js")]')->length);
        // Pemilih tanggal lahir Tanggal, Bulan, Tahun (K89) dimuat sebelum bagian wizard.
        $this->assertSame(1, $xpath->query('//script[contains(@src, "aset/js/pilih-tanggal.js")][following::script[contains(@src, "aset/js/pendaftaran/atlet.js")]]')->length);
        $this->assertSame('X-CSRF-TOKEN', $xpath->query('//meta[@name="csrf-kepala"]')->item(0)->getAttribute('content'));
        $this->assertNotSame('', $xpath->query('//meta[@name="csrf-token"]')->item(0)->getAttribute('content'));

        // Data wizard.
        $data = json_decode($xpath->query('//script[@id="data-pendaftaran"]')->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(PendaftaranController::KUNCI_DRAF, $data['kunci_draf']);
        $this->assertSame(site_url('pendaftaran/sekolah'), $data['alamat']['sekolah']);
        $this->assertSame(['A' => 'SD/MI', 'B' => 'SMP/MTs', 'C' => 'SMA/SMK/MA'], $data['jenjang']);
        $this->assertSame(['Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara'], $data['kabupaten']);
        $this->assertSame(['KU 10', 'KU 12', 'KU 15'], array_column($data['kelompok_umur'], 'nama'));
        $this->assertSame('Lahir 2016–2018', $data['kelompok_umur'][0]['teks_tahun_lahir']);
        $this->assertSame(['A'], $data['kelompok_umur'][0]['jenjang']);
        $this->assertSame(150000, $data['kelompok_umur'][0]['biaya_individu']);
        $this->assertSame('2026-12-09', $data['tanggal_acara']);
        $this->assertSame('tahun', $data['tampilan_usia']);
        $this->assertSame(['penanggung_jawab_minimal' => 1, 'penanggung_jawab_maksimal' => 5, 'nomor_individu_maksimal' => 2, 'atlet_per_nomor_maksimal' => null,
                'regu_mode' => 'ketentuan', 'regu_label' => [], 'regu_awalan' => 'Regu'], $data['aturan']);

        $kanga = array_values(array_filter($data['nomor_lomba'], static fn (array $n): bool => $n['id'] === $id['kanga']))[0];
        $this->assertSame("Kanga's Escape putra", $kanga['nama_lengkap']);
        $this->assertSame(['beregu', 4, 6, 100000], [$kanga['jenis'], $kanga['jumlah_inti'], $kanga['batas_anggota'], $kanga['biaya_beregu']]);
    }

    public function testDataWizardHanyaYangAktifDanTerlindung(): void
    {
        $id = $this->bukaPendaftaranUji();
        $this->db->table('nomor_lomba')->where('id', $id['turbo'])->update(['aktif' => 0]);
        $this->db->table('nomor_lomba')->where('id', $id['frog'])->update(['nama' => '</script><script>alert(1)</script>']);

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();
        $this->assertStringNotContainsString('</script><script>alert(1)', (string) $hasil->response()->getBody());

        $data = json_decode($this->xpathDari($hasil)->query('//script[@id="data-pendaftaran"]')->item(0)->textContent, true, 512, JSON_THROW_ON_ERROR);
        $nama = array_column($data['nomor_lomba'], 'nama', 'id');

        $this->assertArrayNotHasKey($id['turbo'], $nama);
        $this->assertSame('</script><script>alert(1)</script>', $nama[$id['frog']]);
    }

    /**
     * Wizard dipecah per bagian (F7-30): semua berkas di `aset/js/pendaftaran/`
     * dimuat, `dasar.js` paling awal (membuat `window.Pendaftaran`) dan
     * `pendaftaran.js` paling akhir (menjalankan wizard).
     */
    public function testSemuaBagianWizardDimuatBerurutan(): void
    {
        $this->bukaPendaftaranUji();

        $hasil = $this->get('pendaftaran');
        $hasil->assertOK();

        $skrip = [];

        foreach ($this->xpathDari($hasil)->query('//script[contains(@src, "aset/js/pendaftaran")]') as $node) {
            $jalur = (string) parse_url($node->getAttribute('src'), PHP_URL_PATH);
            $skrip[] = substr($jalur, strpos($jalur, 'aset/js/') + strlen('aset/js/'));
        }

        $berkas = array_map(
            static fn (string $jalur): string => 'pendaftaran/' . basename($jalur),
            glob(FCPATH . 'aset/js/pendaftaran/*.js') ?: [],
        );
        $berkas = array_filter($berkas, static fn (string $jalur): bool => ! str_ends_with($jalur, '.min.js'));

        $this->assertSame('pendaftaran/dasar.js', $skrip[0]);
        $this->assertSame('pendaftaran.js', end($skrip));
        $this->assertCount(count($skrip), array_unique($skrip));
        $this->assertEqualsCanonicalizing(array_values($berkas), array_slice($skrip, 0, -1));
    }

    /**
     * Ilustrasi G9-03 di kepala langkah dan G9-04 di layar berhasil (F9-05)
     * hanya tampil bila berkasnya ada; tanpa berkas halaman tetap utuh.
     * Berkas uji dibuat sementara dan hanya yang dibuat uji ini dihapus lagi.
     */
    public function testIlustrasiLangkahDanBerhasilHanyaBilaBerkasAda(): void
    {
        $this->bukaPendaftaranUji();
        $berkas = [
            FCPATH . 'aset/' . App\Libraries\Bantuan\IsiBantuan::FOLDER_PANDUAN . App\Libraries\Bantuan\IsiBantuan::PANDUAN[0][0],
            FCPATH . 'aset/gambar/keadaan/berhasil.webp',
        ];
        $sudahAda = array_map('is_file', $berkas);

        if (! in_array(true, $sudahAda, true)) {
            $xpath = $this->xpathDari($this->get('pendaftaran'));
            $this->assertSame(0, $xpath->query('//img[@data-gambar-langkah]')->length);
            $this->assertSame(0, $xpath->query('//img[@data-gambar-berhasil]')->length);
            $this->assertSame(1, $xpath->query('//div[@id="selesai"]//use[contains(@href, "#trophy")]')->length, 'Ikon pengganti ilustrasi berhasil.');
        }

        $dibuat = [];

        try {
            foreach ($berkas as $i => $jalur) {
                if (! $sudahAda[$i]) {
                    if (! is_dir(dirname($jalur))) {
                        mkdir(dirname($jalur), 0777, true);
                        $dibuat[] = dirname($jalur);
                    }
                    file_put_contents($jalur, 'RIFF');
                    array_unshift($dibuat, $jalur);
                }
            }

            $xpath = $this->xpathDari($this->get('pendaftaran'));
            $gambar = $xpath->query('//section[@id="langkah-1"]//img[@data-gambar-langkah]');
            $this->assertSame(1, $gambar->length);
            $this->assertStringContainsString('aset/gambar/panduan/', $gambar->item(0)->getAttribute('src'));
            $this->assertSame('', $gambar->item(0)->getAttribute('alt'));
            $this->assertStringContainsString('aset/gambar/keadaan/berhasil.webp', $xpath->query('//div[@id="selesai"]//img[@data-gambar-berhasil]')->item(0)->getAttribute('src'));
        } finally {
            foreach ($dibuat as $jalur) {
                is_dir($jalur) ? @rmdir($jalur) : @unlink($jalur);
            }
        }
    }
}
