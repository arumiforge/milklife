<?php

use App\Libraries\Panel\RiwayatAdmin;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * F10-04: tabel admin yang barisnya ditulis server dibagi per halaman di
 * peramban (`<table data-tabel-klien>`, bawaan 25 baris), dan daftar riwayat
 * berhalaman (`data-daftar-berhalaman`). Halaman yang memakainya mengisi
 * bagian `tabel` sehingga DataTables dan `tabel-admin.js` dimuat. Perilaku
 * peramban (paginasi, pilih massal lintas halaman) diperiksa di Chromium.
 *
 * @internal
 */
final class PanelTabelBerhalamanTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    /**
     * @return list<string> id atau "-" untuk tabel tanpa id
     */
    private function tabelKlien(string $alamat): array
    {
        $hasil = $this->bukaSebagaiAdmin($alamat);
        $hasil->assertOK();
        $this->assertStringContainsString('aset/js/tabel-admin.js', (string) $hasil->getBody(), $alamat . ' memuat tabel-admin.js.');
        $this->assertStringContainsString('dataTables.min.js', (string) $hasil->getBody(), $alamat . ' memuat DataTables.');

        $id = [];

        foreach ($this->xpathDari($hasil)->query('//table[@data-tabel-klien]') as $tabel) {
            $id[] = $tabel->getAttribute('id') !== '' ? $tabel->getAttribute('id') : '-';
        }

        return $id;
    }

    public function testTabelHalamanAdminBerhalamanSisiKlien(): void
    {
        $p = $this->buatPendaftaranPanel();

        $this->assertSame(['tabel-atlet'], $this->tabelKlien('admin/pendaftaran/' . $p['id']));
        $this->assertSame(['tabel-tolak-atlet'], $this->tabelKlien('admin/pendaftaran/' . $p['id'] . '/tolak-atlet'));
        $this->assertContains('tabel-kelompok-umur', $this->tabelKlien('admin/pengaturan/kelompok-umur'));
        $this->assertNotSame([], $this->tabelKlien('admin/pengaturan/nomor-lomba'));
        $this->assertSame(['tabel-rekap'], $this->tabelKlien('admin/rekap/sekolah'));
    }

    public function testRiwayatBerhalaman(): void
    {
        $p = $this->buatPendaftaranPanel();
        (new RiwayatAdmin())->catat($p['id'], $p['id_pendaftaran'], 'lunas', 'Ditandai Lunas.');

        foreach (['admin/pendaftaran/' . $p['id'], 'admin/riwayat?id=' . $p['id_pendaftaran']] as $alamat) {
            $hasil = $this->bukaSebagaiAdmin($alamat);
            $this->assertSame(1, $this->xpathDari($hasil)->query('//ol[@data-riwayat-admin][@data-daftar-berhalaman]')->length, $alamat);
            $this->assertStringContainsString('aset/js/tabel-admin.js', (string) $hasil->getBody(), $alamat);
        }
    }

    public function testSkripMengirimBarisHalamanLainDanCentangSemuaHalaman(): void
    {
        $js = (string) file_get_contents(FCPATH . 'aset/js/tabel-admin.js');
        $this->assertStringContainsString('table[data-tabel-klien]', $js);
        $this->assertStringContainsString("addEventListener('formdata'", $js);
        $this->assertStringContainsString('[data-daftar-berhalaman]', $js);
        $this->assertStringContainsString('semuaBaris: semuaBaris', $js);
        $this->assertStringContainsString('TabelAdmin.semuaBaris(tabel)', (string) file_get_contents(FCPATH . 'aset/js/centang-semua.js'));
    }
}
