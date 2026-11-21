<?php

use App\Libraries\Bantuan\JawabanFaq;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Jawaban FAQ (F9-04): isian otomatis, Markdown, dan tautan yang aman.
 *
 * @internal
 */
final class BantuanJawabanFaqTest extends CIUnitTestCase
{
    private function jawaban(array $nilai = []): JawabanFaq
    {
        return new JawabanFaq($nilai + ['tutup_pendaftaran' => '30 November 2026 pukul 23.59', 'rekening' => '**BANK KARANGAN** nomor **111**']);
    }

    public function testIsianOtomatisDigantiDanYangTidakDikenalDibiarkan(): void
    {
        $html = $this->jawaban()->html("Ditutup **{tutup_pendaftaran}**. Transfer ke {rekening}. {tidak_ada} {Besar}");

        $this->assertSame('<p>Ditutup <strong>30 November 2026 pukul 23.59</strong>. Transfer ke <strong>BANK KARANGAN</strong> nomor <strong>111</strong>. {tidak_ada} {Besar}</p>', $html);
        $this->assertSame(['tidak_ada'], JawabanFaq::isianTidakDikenal('{tutup_pendaftaran} {tidak_ada} {tidak_ada}'));
    }

    public function testTeksDiEscapeDanDaftarBernomor(): void
    {
        $html = $this->jawaban()->html("<script>alert(1)</script>\n\n1. Tekan **Cari**.\n2. Tekan *Lanjut*.");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('<ol><li>Tekan <strong>Cari</strong>.</li><li>Tekan <em>Lanjut</em>.</li></ol>', $html);
    }

    public function testTautanDirapikan(): void
    {
        $html = $this->jawaban()->html('[luar](https://wa.me/628123) [dalam](/cek-pendaftaran) [polos](unduhan) [surel](mailto:panitia@contoh.id) [jangkar](#atas) [jahat](javascript:alert) [data](data:text/html,x) [ganda](//jahat.contoh) [naik](../rahasia)');

        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>');
        $tautan = [];

        foreach ($dom->getElementsByTagName('a') as $a) {
            $tautan[$a->textContent] = [$a->getAttribute('href'), $a->getAttribute('target'), $a->getAttribute('rel')];
        }

        $this->assertSame([
            'luar'    => ['https://wa.me/628123', '_blank', 'noopener noreferrer'],
            'dalam'   => [site_url('cek-pendaftaran'), '', ''],
            'polos'   => [site_url('unduhan'), '', ''],
            'surel'   => ['mailto:panitia@contoh.id', '', ''],
            'jangkar' => ['#atas', '', ''],
        ], $tautan);

        foreach (['jahat', 'data', 'ganda', 'naik'] as $teks) {
            $this->assertStringContainsString($teks, $html, 'Teks tautan yang dibuang tetap tampil.');
        }
    }

    public function testTeksPolosUntukPencarian(): void
    {
        $teks = $this->jawaban()->teks("Tekan **Cek Pendaftaran** lalu [Cari](/cek).\n\n- Ditutup {tutup_pendaftaran}\n1. Satu");

        $this->assertSame('Tekan Cek Pendaftaran lalu Cari. Ditutup 30 November 2026 pukul 23.59 Satu', $teks);
    }

    public function testDaftarKelompokUmurDenganPilihan(): void
    {
        $ku = [
            ['nama' => 'KU 10', 'jenjang' => 'SD/MI', 'tahun_lahir' => 'Lahir 2016–2018', 'pilihan' => ['SD/MI lahir 2016 boleh memilih KU 10 atau KU 12']],
            ['nama' => 'KU 12', 'jenjang' => 'SD/MI', 'tahun_lahir' => 'Lahir 2014–2016', 'pilihan' => ['SD/MI lahir 2016 boleh memilih KU 10 atau KU 12']],
        ];

        $this->assertSame(
            "- **KU 10**: SD/MI, lahir 2016–2018\n- **KU 12**: SD/MI, lahir 2014–2016\n\nSD/MI lahir 2016 boleh memilih KU 10 atau KU 12.",
            JawabanFaq::daftarKelompokUmur($ku),
        );
        $this->assertSame(JawabanFaq::BELUM_DIATUR, JawabanFaq::daftarKelompokUmur([]));
    }

    public function testDaftarBiayaIndividuDanBeregu(): void
    {
        $ku = [[
            'nama'           => 'KU 10',
            'biaya_individu' => 25000,
            'nomor_individu' => [['nama' => 'Lari 60 m']],
            'nomor_beregu'   => [
                ['nama' => 'Estafet 8 × 50 m', 'biaya_beregu' => 150000],
                ['nama' => "Kanga's Escape", 'biaya_beregu' => 100000],
                ['nama' => "Kanga's Escape", 'biaya_beregu' => 100000],
                ['nama' => 'Formula 1', 'biaya_beregu' => 100000],
            ],
        ]];

        $this->assertSame(
            "- **KU 10**: Rp 25.000 per atlet per nomor individu; Rp 100.000 per regu (Kanga's Escape, Formula 1); Rp 150.000 per regu (Estafet 8 × 50 m)",
            JawabanFaq::daftarBiaya($ku),
        );
        $this->assertSame(JawabanFaq::BELUM_DIATUR, JawabanFaq::daftarBiaya([]));
    }
}
