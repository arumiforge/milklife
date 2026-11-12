<?php

use App\Commands\DokumenPdf;
use App\Libraries\MarkdownSederhana;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;

/**
 * Pengubah Markdown dan perintah `dokumen:pdf` (F5-04).
 *
 * @internal
 */
final class MarkdownSederhanaTest extends CIUnitTestCase
{
    use StreamFilterTrait;

    public function testJudulParagrafDanDaftar(): void
    {
        $html = MarkdownSederhana::html(implode("\n", [
            '# Judul',
            '',
            'Baris satu',
            'baris dua.',
            '',
            '- butir a',
            '- butir b',
            '  lanjutan b',
            '1. pertama',
            '2. kedua',
            '### Sub',
        ]));

        $this->assertSame(implode("\n", [
            '<h1>Judul</h1>',
            '<p>Baris satu baris dua.</p>',
            '<ul><li>butir a</li><li>butir b lanjutan b</li></ul>',
            '<ol><li>pertama</li><li>kedua</li></ol>',
            '<h3>Sub</h3>',
        ]), $html);
    }

    public function testTabel(): void
    {
        $html = MarkdownSederhana::html("| A | B |\n|---|:---:|\n| 1 | **dua** |\n| `x` |  |");

        $this->assertSame('<table><tr><th>A</th><th>B</th></tr><tr><td>1</td><td><strong>dua</strong></td></tr><tr><td><code>x</code></td><td></td></tr></table>', $html);
    }

    public function testBlokKodeDanNomorAwalDaftar(): void
    {
        $html = MarkdownSederhana::html(implode("\n", [
            '1. Jalankan:',
            '',
            '   ```',
            '   php spark <perintah>',
            '',
            '   # bukan judul',
            '   ```',
            '',
            '2. Selesai.',
        ]));

        $this->assertSame(implode("\n", [
            '<ol><li>Jalankan:</li></ol>',
            "<pre><code>php spark &lt;perintah&gt;\n\n# bukan judul</code></pre>",
            '<ol start="2"><li>Selesai.</li></ol>',
        ]), $html);
    }

    public function testSebarisDanEscape(): void
    {
        $this->assertSame(
            '<p><strong>tebal</strong>, <em>miring</em>, <code>&lt;Kode&gt; **bukan**</code>, <a href="https://contoh.id">tautan</a>, 2 * 3 * 4 &lt;b&gt; &amp;</p>',
            MarkdownSederhana::html('**tebal**, *miring*, `<Kode> **bukan**`, [tautan](https://contoh.id), 2 * 3 * 4 <b> &'),
        );
    }

    public function testPerintahMembuatPdfDariDokumenFinal(): void
    {
        $asal  = [DokumenPdf::$folderFinal, DokumenPdf::$folderPdf];
        $dasar = sys_get_temp_dir() . '/dokumen-pdf-' . bin2hex(random_bytes(4)) . '/';
        mkdir($dasar . 'final', 0777, true);
        file_put_contents($dasar . 'final/contoh.md', "# Contoh\n\n| A | B |\n|---|---|\n| 1 | 2 |\n");

        DokumenPdf::$folderFinal = $dasar . 'final/';
        DokumenPdf::$folderPdf   = $dasar . 'pdf/';

        try {
            $this->assertSame(EXIT_SUCCESS, service('commands')->run('dokumen:pdf', []));
            $this->assertStringStartsWith('%PDF', (string) file_get_contents($dasar . 'pdf/contoh.pdf'));
            $this->assertStringContainsString('dokumen/pdf/contoh.pdf', $this->getStreamFilterBuffer());
        } finally {
            [DokumenPdf::$folderFinal, DokumenPdf::$folderPdf] = $asal;
            array_map('unlink', glob($dasar . '*/*'));
            array_map('rmdir', glob($dasar . '*'));
            rmdir($dasar);
        }
    }
}
