<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Setiap kunci di `.env.example` harus benar-benar dibaca aplikasi:
 * `awalan.properti` menunjuk properti kelas `Config\Awalan`, dan
 * `awalan.properti.kunci` menunjuk kunci di dalam properti larik.
 *
 * @internal
 */
final class EnvContohTest extends CIUnitTestCase
{
    public function testSemuaKunciEnvContohDibaca(): void
    {
        $baris = file(ROOTPATH . '.env.example', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $this->assertNotFalse($baris);

        $jumlah = 0;

        foreach ($baris as $isi) {
            $isi = trim($isi);

            if ($isi === '' || str_starts_with($isi, '#')) {
                continue;
            }

            $kunci = trim(explode('=', $isi, 2)[0]);

            if ($kunci === 'CI_ENVIRONMENT') {
                continue;
            }

            $bagian = explode('.', $kunci);
            $this->assertGreaterThanOrEqual(2, count($bagian), "Kunci tanpa awalan: {$kunci}");

            $kelas = 'Config\\' . ucfirst($bagian[0]);
            $this->assertTrue(class_exists($kelas), "Kelas {$kelas} untuk kunci {$kunci} tidak ada");

            $konfigurasi = new $kelas();
            $this->assertTrue(property_exists($konfigurasi, $bagian[1]), "Kunci {$kunci} tidak dibaca: {$kelas} tidak punya properti {$bagian[1]}");

            if (count($bagian) === 3) {
                $nilai = $konfigurasi->{$bagian[1]};
                $this->assertIsArray($nilai, "{$kelas}::\${$bagian[1]} bukan larik");
                $this->assertArrayHasKey($bagian[2], $nilai, "Kunci {$kunci} tidak dibaca");
            }

            $jumlah++;
        }

        $this->assertGreaterThan(20, $jumlah);
    }
}
