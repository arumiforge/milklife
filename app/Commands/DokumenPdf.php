<?php

namespace App\Commands;

use App\Libraries\MarkdownSederhana;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Mpdf\Mpdf;

/**
 * Membuat PDF di `dokumen/pdf/` dari setiap dokumen final
 * (`dokumen/final/*.md`). PDF tidak disunting tangan (ALUR-KERJA bagian
 * Dokumen): setiap dokumen final berubah, jalankan perintah ini lagi.
 */
class DokumenPdf extends BaseCommand
{
    protected $group       = 'Dokumen';
    protected $name        = 'dokumen:pdf';
    protected $description = 'Membuat PDF di dokumen/pdf/ dari semua dokumen final (dokumen/final/*.md).';
    protected $usage       = 'dokumen:pdf';

    /**
     * Folder sumber dan hasil; bisa diganti uji.
     */
    public static string $folderFinal = ROOTPATH . 'dokumen/final/';

    public static string $folderPdf = ROOTPATH . 'dokumen/pdf/';

    private const GAYA = <<<'CSS'
        body { font-family: dejavusans; font-size: 9.5pt; line-height: 1.4; color: #1b2433; }
        h1 { font-size: 18pt; color: #021F7B; }
        h2 { font-size: 14pt; color: #021F7B; margin-top: 6mm; }
        h3 { font-size: 11.5pt; color: #021F7B; }
        h4, h5, h6 { font-size: 10pt; }
        table { width: 100%; border-collapse: collapse; margin: 2mm 0 3mm; }
        th { background: #E6F2FB; text-align: left; }
        th, td { border: 0.2mm solid #D6DEEA; padding: 1.2mm 1.5mm; vertical-align: top; }
        code { font-family: dejavusansmono; font-size: 8.5pt; color: #0B2E8A; }
        CSS;

    public function run(array $params): int
    {
        $berkas = glob(self::$folderFinal . '*.md') ?: [];

        if ($berkas === []) {
            CLI::error('Belum ada dokumen final di ' . self::$folderFinal . '.');

            return EXIT_ERROR;
        }

        if (! is_dir(self::$folderPdf)) {
            mkdir(self::$folderPdf, 0775, true);
        }

        foreach ($berkas as $md) {
            $nama = basename($md, '.md');
            $teks = (string) file_get_contents($md);

            $mpdf = new Mpdf(['format' => 'A4', 'tempDir' => WRITEPATH . 'cache/mpdf']);
            $mpdf->SetTitle(preg_match('/^#\s+(.+)$/m', $teks, $m) === 1 ? trim($m[1]) : $nama);
            $mpdf->setFooter('{PAGENO} / {nbpg}');
            $mpdf->WriteHTML('<style>' . self::GAYA . '</style>' . MarkdownSederhana::html($teks));
            $mpdf->Output(self::$folderPdf . $nama . '.pdf', 'F');

            CLI::write('Dibuat: dokumen/pdf/' . $nama . '.pdf');
        }

        return EXIT_SUCCESS;
    }
}
