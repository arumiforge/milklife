<?php

use App\Libraries\Panel\EksporExcel;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\Test\CIUnitTestCase;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * `EksporExcel`: berkas `.xlsx` yang ditulis bisa dibaca ulang dengan isi,
 * jenis sel, dan format yang benar. Data karangan.
 *
 * @internal
 */
final class PanelEksporExcelTest extends CIUnitTestCase
{
    /**
     * @var list<string>
     */
    private array $berkasSementara = [];

    protected function tearDown(): void
    {
        foreach ($this->berkasSementara as $berkas) {
            if (is_file($berkas)) {
                unlink($berkas);
            }
        }

        parent::tearDown();
    }

    private function excelContoh(): EksporExcel
    {
        $excel = new EksporExcel('Pendaftaran', [
            'id_pendaftaran' => 'ID Pendaftaran',
            'nisn'           => 'NISN',
            'nama_sekolah'   => 'Sekolah',
            'jumlah_atlet'   => ['judul' => 'Jumlah atlet', 'jenis' => 'angka'],
            'total'          => ['judul' => 'Total', 'jenis' => 'rupiah'],
        ]);

        return $excel->tambahBaris([
            ['id_pendaftaran' => 'MAC2A0000126', 'nisn' => '0099000001', 'nama_sekolah' => 'SD KARANGAN MAJU', 'jumlah_atlet' => '2', 'total' => '450000', 'lain' => 'diabaikan'],
            ['id_pendaftaran' => 'MAC2B0000226', 'nisn' => '9900000002', 'nama_sekolah' => '=1+2', 'jumlah_atlet' => 1, 'total' => 200000],
            ['id_pendaftaran' => 'MAC2A0000326', 'nisn' => null, 'nama_sekolah' => 'SD "Karangan" <Ceria>', 'total' => ''],
        ]);
    }

    private function bacaUlang(string $isi): Spreadsheet
    {
        $berkas = tempnam(sys_get_temp_dir(), 'milklife-excel-');
        $this->berkasSementara[] = $berkas;
        file_put_contents($berkas, $isi);

        return IOFactory::createReader('Xlsx')->load($berkas);
    }

    public function testBerkasTerbacaUlang(): void
    {
        $isi = $this->excelContoh()->isi();

        // Berkas .xlsx adalah arsip ZIP.
        $this->assertStringStartsWith("PK\x03\x04", $isi);

        $lembar = $this->bacaUlang($isi)->getActiveSheet();

        $this->assertSame('Pendaftaran', $lembar->getTitle());
        $this->assertSame(
            [
                ['ID Pendaftaran', 'NISN', 'Sekolah', 'Jumlah atlet', 'Total'],
                ['MAC2A0000126', '0099000001', 'SD KARANGAN MAJU', 2, 450000],
                ['MAC2B0000226', '9900000002', '=1+2', 1, 200000],
                ['MAC2A0000326', null, 'SD "Karangan" <Ceria>', null, null],
            ],
            $lembar->toArray(null, false, false, false),
        );
    }

    public function testJudulTebalDanDibekukan(): void
    {
        $lembar = $this->bacaUlang($this->excelContoh()->isi())->getActiveSheet();

        foreach (['A1', 'B1', 'C1', 'D1', 'E1'] as $sel) {
            $this->assertTrue($lembar->getStyle($sel)->getFont()->getBold(), $sel);
        }

        $this->assertFalse($lembar->getStyle('A2')->getFont()->getBold());
        $this->assertSame('A2', $lembar->getFreezePane());
    }

    public function testJenisSel(): void
    {
        $lembar = $this->bacaUlang($this->excelContoh()->isi())->getActiveSheet();

        // NISN dan teks yang diawali "=" tetap teks: nol di depan tidak hilang, rumus tidak dijalankan.
        $this->assertSame(DataType::TYPE_STRING, $lembar->getCell('B2')->getDataType());
        $this->assertSame(DataType::TYPE_STRING, $lembar->getCell('C3')->getDataType());
        $this->assertSame('=1+2', $lembar->getCell('C3')->getValue());

        // Rupiah: bilangan dengan format Rp.
        $this->assertSame(DataType::TYPE_NUMERIC, $lembar->getCell('E2')->getDataType());
        $this->assertSame(EksporExcel::FORMAT_RUPIAH, $lembar->getStyle('E2')->getNumberFormat()->getFormatCode());
        $this->assertSame('Rp450,000', $lembar->getCell('E2')->getFormattedValue());
        $this->assertSame(DataType::TYPE_NUMERIC, $lembar->getCell('D3')->getDataType());
    }

    public function testLebarKolomOtomatis(): void
    {
        $lembar = $this->excelContoh()->lembar()->getActiveSheet();

        foreach (['A', 'B', 'C', 'D', 'E'] as $kolom) {
            $this->assertTrue($lembar->getColumnDimension($kolom)->getAutoSize(), $kolom);
        }
    }

    public function testTanpaBarisTetapAdaJudul(): void
    {
        $lembar = $this->bacaUlang((new EksporExcel('Kosong', ['a' => 'Kolom A']))->isi())->getActiveSheet();

        $this->assertSame([['Kolom A']], $lembar->toArray(null, false, false, false));
    }

    public function testJudulLembarDibersihkan(): void
    {
        $lembar = (new EksporExcel('Rekap: Per/Sekolah [Lunas] dan judul yang sangat panjang sekali', ['a' => 'A']))->lembar()->getActiveSheet();

        $this->assertSame('Rekap Per Sekolah Lunas dan jud', $lembar->getTitle());
        $this->assertSame(31, mb_strlen($lembar->getTitle()));
    }

    public function testJenisKolomTidakDikenalDitolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new EksporExcel('A', ['a' => ['judul' => 'A', 'jenis' => 'tanggal']]);
    }

    public function testNamaBerkas(): void
    {
        $this->assertSame('Pendaftaran_20261112.xlsx', EksporExcel::namaBerkasSah('Pendaftaran_20261112.xlsx'));
        $this->assertSame('Pendaftaran_20261112.xlsx', EksporExcel::namaBerkasSah('Pendaftaran_20261112'));
        $this->assertSame('Rekap_Per_Sekolah.xlsx', EksporExcel::namaBerkasSah('Rekap Per/Sekolah'));
        $this->assertSame('etc_passwd.xlsx', EksporExcel::namaBerkasSah('../../etc/passwd'));
        $this->assertSame('Data.xlsx', EksporExcel::namaBerkasSah('///'));
    }

    public function testUnduh(): void
    {
        $tanggapan = $this->excelContoh()->unduh('Pendaftaran Lunas');

        $this->assertInstanceOf(DownloadResponse::class, $tanggapan);
        $tanggapan->buildHeaders();
        $this->assertStringContainsString(EksporExcel::MIME, $tanggapan->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Pendaftaran_Lunas.xlsx', $tanggapan->getHeaderLine('Content-Disposition'));
    }
}
