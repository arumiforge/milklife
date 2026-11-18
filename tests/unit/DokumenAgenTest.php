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

    public function testSetiapTugasDiProgresDanArsipPunyaBerkasTugas(): void
    {
        // PROGRES.md boleh tanpa tabel tugas saat tidak ada tahap berjalan (F7-07);
        // tabel tahap yang selesai pindah ke agent/arsip/ dan tetap diperiksa.
        $berkas = ['agent/PROGRES.md' => 'tugas/'];

        foreach (glob(ROOTPATH . 'agent/arsip/*.md') ?: [] as $arsip) {
            $berkas['agent/arsip/' . basename($arsip)] = '../tugas/';
        }

        $jumlah = 0;

        foreach ($berkas as $jalur => $awalan) {
            preg_match_all('/^\|\s*\[(F\d+-\d{2})\b[^\]]*\]\(([^)]+)\)/m', $this->baca($jalur), $cocok, PREG_SET_ORDER);

            foreach ($cocok as [, $id, $tautan]) {
                $jumlah++;
                $this->assertMatchesRegularExpression(
                    '#^' . preg_quote($awalan . $id, '#') . '-[a-z0-9-]+\.md$#',
                    $tautan,
                    "Tautan tugas {$id} di {$jalur} harus menunjuk ke {$awalan}{$id}-<slug>.md.",
                );
                $this->assertFileExists(
                    ROOTPATH . dirname($jalur) . '/' . $tautan,
                    "Tugas {$id} di {$jalur} tidak punya berkas tugas {$tautan}.",
                );
            }
        }

        $this->assertGreaterThan(0, $jumlah, 'Tabel tugas tidak ditemukan di agent/PROGRES.md maupun agent/arsip/.');
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
