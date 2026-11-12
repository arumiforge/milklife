<?php

use App\Filters\PerkecilHtml;
use CodeIgniter\HTTP\Response;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;

/**
 * Filter `perkecilhtml` (`App\Filters\PerkecilHtml`): aturan perkecil HTML
 * dan kapan filter bekerja.
 *
 * @internal
 */
final class PerkecilHtmlTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        config('Aplikasi')->minify = false;

        parent::tearDown();
    }

    public function testSpasiAntarTagDirapatkan(): void
    {
        $html = "<!DOCTYPE html>\n<html lang=\"id\">\n  <head>\n    <meta charset=\"utf-8\">\n    <title>  Pendaftaran  </title>\n  </head>\n"
            . "  <body>\n    <div class=\"kartu\">\n      <p>Halo   dunia,\n\t  selamat datang.</p>\n      <a href=\"#\">Satu</a> <a href=\"#\">Dua</a>\n    </div>\n  </body>\n</html>\n";

        $this->assertSame(
            '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>Pendaftaran</title></head><body><div class="kartu"> <p>Halo dunia, selamat datang.</p> <a href="#">Satu</a> <a href="#">Dua</a> </div></body></html>',
            PerkecilHtml::perkecil($html),
        );
    }

    public function testSpasiAntarElemenSebarisTetapAda(): void
    {
        // Tombol dan tautan sebaris dipisah satu spasi, sama seperti tampilan aslinya.
        $this->assertSame(
            '<button class="btn">Simpan</button> <button class="btn">Batal</button> <span>a</span><span>b</span>',
            PerkecilHtml::perkecil("<button class=\"btn\">Simpan</button>\n    <button class=\"btn\">Batal</button>\n<span>a</span><span>b</span>"),
        );
    }

    public function testTabelDanPilihanTanpaSpasi(): void
    {
        $this->assertSame(
            '<table><thead><tr><th>Nama</th><th>NISN</th></tr></thead><tbody><tr><td>Budi Karangan</td><td>9900000001</td></tr></tbody></table>'
            . '<select name="kab"><option value="1">Kudus</option><option value="2" selected>Pati</option></select>',
            PerkecilHtml::perkecil(
                "<table>\n  <thead>\n    <tr>\n      <th>Nama</th>\n      <th>NISN</th>\n    </tr>\n  </thead>\n  <tbody>\n    <tr>\n      <td>\n        Budi Karangan\n      </td>\n      <td>9900000001</td>\n    </tr>\n  </tbody>\n</table>\n"
                . "<select name=\"kab\">\n  <option value=\"1\">Kudus</option>\n  <option value=\"2\" selected>Pati</option>\n</select>",
            ),
        );
    }

    public function testIsiPreTextareaScriptStyleUtuh(): void
    {
        $pre      = "<pre class=\"kode\">baris 1\n    baris  2\n\n</pre>";
        $textarea = "<textarea name=\"catatan\" rows=\"3\">Catatan   panjang\n  baris kedua\n</textarea>";
        $script   = "<script>\n  // komentar <!-- bukan komentar HTML -->\n  var a = '<b>  tebal  </b>';\n  if (a < 2 && 3 > 1) { a = 1; }\n</script>";
        $style    = "<style>\n  .a  >  .b { color: red; }\n</style>";

        $hasil = PerkecilHtml::perkecil("<div>\n  {$pre}\n  {$textarea}\n  {$script}\n  {$style}\n</div>");

        $this->assertSame("<div> {$pre} {$textarea} {$script} {$style} </div>", $hasil);
    }

    public function testNilaiAtributTidakDiubah(): void
    {
        $tag = "<input type=\"text\" name=\"nama\" value=\"Budi   Karangan\" data-pesan='Isi  &gt;  nama\n baris'>";

        $this->assertSame($tag . ' <img src="a.png" alt="Logo  bank">', PerkecilHtml::perkecil("{$tag}\n   <img src=\"a.png\" alt=\"Logo  bank\">"));
    }

    public function testKomentarDibuangKecualiBersyarat(): void
    {
        $this->assertSame(
            '<p>Satu dua</p><!--[if IE]><p>Lama</p><![endif]-->',
            PerkecilHtml::perkecil("<p>Satu <!-- catatan pengembang --> dua</p><!--[if IE]><p>Lama</p><![endif]-->"),
        );
    }

    public function testTandaKurangDariDiTeksTetapAda(): void
    {
        $this->assertSame('<p>3 < 5 dan a <b>tebal</b></p>', PerkecilHtml::perkecil("<p>3  <  5 dan a\n<b>tebal</b></p>"));
    }

    public function testFilterHanyaBilaMinifyAktif(): void
    {
        $html = "<p>\n  Halo\n</p>";

        config('Aplikasi')->minify = false;
        $this->assertNull((new PerkecilHtml())->after(service('request'), $this->jawaban($html)));

        config('Aplikasi')->minify = true;
        $jawaban = (new PerkecilHtml())->after(service('request'), $this->jawaban($html));
        $this->assertInstanceOf(Response::class, $jawaban);
        $this->assertSame('<p> Halo </p>', $jawaban->getBody());
    }

    public function testSelainHtmlTidakDiubah(): void
    {
        config('Aplikasi')->minify = true;

        foreach ([
            'application/pdf'                                                   => "%PDF-1.4\n  isi   pdf\n",
            'application/json'                                                  => "{\n  \"nama\": \"Budi   Karangan\"\n}",
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => "PK\x03\x04  <x>\n  </x>",
            'image/jpeg'                                                        => "\xFF\xD8\xFF  <a>\n",
            'text/plain'                                                        => "baris  1\n  baris 2",
            'text/csv'                                                          => "a,  b\n<c>,  d",
        ] as $jenis => $isi) {
            $jawaban = $this->jawaban($isi, $jenis);

            $this->assertNull((new PerkecilHtml())->after(service('request'), $jawaban), $jenis);
            $this->assertSame($isi, $jawaban->getBody(), $jenis);
        }
    }

    public function testJenisHtml(): void
    {
        $this->assertTrue(PerkecilHtml::jawabanHtml($this->jawaban('', 'text/html')));
        $this->assertTrue(PerkecilHtml::jawabanHtml($this->jawaban('', 'TEXT/HTML')));
        $this->assertFalse(PerkecilHtml::jawabanHtml($this->jawaban('', 'application/xhtml+xml')));
        $this->assertFalse(PerkecilHtml::jawabanHtml($this->jawaban('', 'application/json')));
    }

    public function testFilterTerdaftarSebagaiGlobalAfter(): void
    {
        $filters = config('Filters');

        $this->assertSame(PerkecilHtml::class, $filters->aliases['perkecilhtml']);
        $this->assertContains('perkecilhtml', $filters->globals['after']);
    }

    private function jawaban(string $isi, string $jenis = 'text/html'): Response
    {
        $jawaban = new Response(config(App::class));
        $jawaban->setContentType($jenis);
        $jawaban->setBody($isi);

        return $jawaban;
    }
}
