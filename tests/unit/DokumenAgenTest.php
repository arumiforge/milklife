<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Penjaga dokumen agen (K120): status tugas, aturan berlaku, dan petunjuk
 * agen tidak boleh menunjuk ke sesuatu yang tidak ada.
 *
 * @internal
 */
final class DokumenAgenTest extends CIUnitTestCase
{
    private function baca(string $jalur): string
    {
        $isi = file_get_contents(ROOTPATH . $jalur);
        $this->assertIsString($isi, "Berkas {$jalur} tidak bisa dibaca.");

        return $isi;
    }

    public function testSetiapTugasDiProgresPunyaBerkasTugas(): void
    {
        $progres = $this->baca('agent/PROGRES.md');

        preg_match_all('/^\|\s*\[(F\d+-\d{2})\b[^\]]*\]\(([^)]+)\)/m', $progres, $cocok, PREG_SET_ORDER);
        $this->assertNotEmpty($cocok, 'Tabel tugas di agent/PROGRES.md tidak ditemukan.');

        foreach ($cocok as [, $id, $tautan]) {
            $this->assertMatchesRegularExpression(
                '#^tugas/' . preg_quote($id, '#') . '-[a-z0-9-]+\.md$#',
                $tautan,
                "Tautan tugas {$id} di PROGRES.md harus menunjuk ke tugas/{$id}-<slug>.md.",
            );
            $this->assertFileExists(ROOTPATH . 'agent/' . $tautan, "Tugas {$id} di PROGRES.md tidak punya berkas agent/{$tautan}.");
        }
    }

    public function testSetiapNomorKDiAturanAdaDiKeputusan(): void
    {
        $aturan    = $this->baca('agent/ATURAN.md');
        $keputusan = $this->baca('agent/KEPUTUSAN.md');

        preg_match_all('/^\|\s*(K\d+)\s*\|/m', $keputusan, $tercatat);
        $tercatat = array_flip($tercatat[1]);

        preg_match_all('/\bK(\d+)\b/', $aturan, $disebut);
        $this->assertNotEmpty($disebut[0], 'agent/ATURAN.md tidak menyebut nomor K apa pun.');

        foreach (array_unique($disebut[0]) as $nomor) {
            $this->assertArrayHasKey($nomor, $tercatat, "{$nomor} disebut di ATURAN.md tetapi tidak ada di KEPUTUSAN.md.");
        }
    }

    public function testBerkasDiTabelBacaBilaPerluAda(): void
    {
        $petunjuk = $this->baca('CLAUDE.md');

        preg_match_all('#`((?:agent|dokumen)/[^`*<>]+\.md)`#', $petunjuk, $cocok);
        $this->assertNotEmpty($cocok[1], 'CLAUDE.md tidak menyebut berkas agent/ atau dokumen/.');

        foreach (array_unique($cocok[1]) as $jalur) {
            $this->assertFileExists(ROOTPATH . $jalur, "CLAUDE.md menyebut {$jalur}, tetapi berkasnya tidak ada.");
        }
    }

    public function testPetunjukAwalTetapRingkas(): void
    {
        $this->assertLessThanOrEqual(3072, strlen($this->baca('CLAUDE.md')), 'CLAUDE.md paling besar 3 KB (K120).');
        $this->assertLessThanOrEqual(4096, strlen($this->baca('agent/ALUR-KERJA.md')), 'agent/ALUR-KERJA.md paling besar 4 KB (K120).');
    }
}
