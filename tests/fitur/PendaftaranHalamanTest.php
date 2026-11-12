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
        $this->assertSame(1, $xpath->query('//*[contains(@class, "pendaftaran-tertutup")]')->length);
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
        $this->assertSame(['Data Sekolah', 'Penanggung Jawab', 'Atlet', 'Biaya', 'Pembayaran'], $pil);
        $this->assertSame(5, $xpath->query('//section[contains(@class, "langkah")][@data-langkah]')->length);

        // Pilihan jenjang dan lima kabupaten.
        $this->assertSame(3, $xpath->query('//input[@name="sekolah-jenjang"]')->length);
        $this->assertSame(6, $xpath->query('//select[@id="sekolah-kabupaten"]/option')->length);

        // Aset sendiri dan token CSRF untuk kiriman JSON.
        $this->assertSame(1, $xpath->query('//head/link[contains(@href, "aset/css/pendaftaran.css")]')->length);
        $this->assertSame(1, $xpath->query('//script[contains(@src, "aset/js/pendaftaran.js")]')->length);
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
        $this->assertSame(['penanggung_jawab_minimal' => 1, 'penanggung_jawab_maksimal' => 5, 'nomor_individu_maksimal' => 2, 'atlet_per_nomor_maksimal' => null], $data['aturan']);

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
}
