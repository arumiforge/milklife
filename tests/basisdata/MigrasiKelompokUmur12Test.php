<?php

use App\Database\Migrations\KelompokUmur12SesuaiJuknis;
use Tests\Support\UjiBasisData;

/**
 * Migrasi KU 12 lahir 2014–2015 sesuai juknis (K112): hanya mengubah nilai
 * bawaan 2015–2015, dan tidak bila menimbulkan irisan dalam satu jenjang.
 *
 * @internal
 */
final class MigrasiKelompokUmur12Test extends UjiBasisData
{
    public function testHanyaMengubahNilaiBawaan(): void
    {
        $ku12 = $this->buatKu('KU 12', 2015, 2015, ['A']);
        $this->buatKu('KU 15', 2011, 2014, ['B']);   // jenjang lain boleh beririsan (K95)

        (new KelompokUmur12SesuaiJuknis())->up();

        $this->seeInDatabase('kelompok_umur', ['id' => $ku12, 'tahun_lahir_awal' => 2014, 'tahun_lahir_akhir' => 2015]);

        // Dijalankan lagi atau setelah admin mengubah: tidak menyentuh apa pun.
        $this->db->table('kelompok_umur')->where('id', $ku12)->update(['tahun_lahir_awal' => 2013]);
        (new KelompokUmur12SesuaiJuknis())->up();
        $this->seeInDatabase('kelompok_umur', ['id' => $ku12, 'tahun_lahir_awal' => 2013]);
    }

    public function testTidakDiubahBilaBeririsanDalamSatuJenjang(): void
    {
        $ku12 = $this->buatKu('KU 12', 2015, 2015, ['A']);
        $this->buatKu('KU 11', 2014, 2014, ['A']);

        (new KelompokUmur12SesuaiJuknis())->up();

        $this->seeInDatabase('kelompok_umur', ['id' => $ku12, 'tahun_lahir_awal' => 2015]);
    }

    /**
     * @param list<string> $jenjang
     */
    private function buatKu(string $nama, int $awal, int $akhir, array $jenjang): int
    {
        $this->db->table('kelompok_umur')->insert(['nama' => $nama, 'tahun_lahir_awal' => $awal, 'tahun_lahir_akhir' => $akhir, 'biaya_individu' => 25000, 'aktif' => 1]);
        $id = (int) $this->db->insertID();

        foreach ($jenjang as $j) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $id, 'jenjang' => $j]);
        }

        return $id;
    }
}
