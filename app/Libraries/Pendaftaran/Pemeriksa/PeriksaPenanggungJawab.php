<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\NomorWa;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;

/**
 * Langkah 2: ofisial (penanggung jawab) (bagian dari `PemeriksaPendaftaran`,
 * F7-30): jumlah minimal dan maksimal, nama, nomor WA, dan foto.
 */
final class PeriksaPenanggungJawab
{
    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * @param list<mixed> $daftar
     * @param bool        $periksaFoto false = foto hanya diperiksa ada atau tidak
     *
     * @return list<array{nama: string, nomor_wa: string, foto: string|null}>
     */
    public function periksa(array $daftar, bool $periksaFoto): array
    {
        $minimal  = (int) ($this->induk->aturan['penanggung_jawab_minimal'] ?? 1);
        $maksimal = (int) ($this->induk->aturan['penanggung_jawab_maksimal'] ?? 5);
        $jumlah   = count($daftar);
        $panjang  = PemeriksaPendaftaran::NAMA_MAKSIMAL;

        if ($jumlah < $minimal) {
            $this->galat->tambah('penanggung_jawab', "Isi minimal {$minimal} ofisial. Sekarang baru {$jumlah}.");
        } elseif ($jumlah > $maksimal) {
            $this->galat->tambah('penanggung_jawab', "Ofisial paling banyak {$maksimal} orang. Hapus " . ($jumlah - $maksimal) . ' orang.');
        }

        $hasil = [];

        foreach ($daftar as $i => $orang) {
            $orang = is_array($orang) ? $orang : [];
            $ke    = 'Ofisial ' . ($i + 1);
            $nama  = MasukanPeriksa::rapikanSpasi(MasukanPeriksa::teks($orang['nama'] ?? null));
            $wa    = NomorWa::normalkan(MasukanPeriksa::teks($orang['nomor_wa'] ?? null));
            $foto  = $orang['foto'] ?? null;

            if ($nama === '') {
                $this->galat->tambah("penanggung_jawab.{$i}.nama", "{$ke}: nama belum diisi.");
            } elseif (mb_strlen($nama) > $panjang) {
                $this->galat->tambah("penanggung_jawab.{$i}.nama", "{$ke}: nama terlalu panjang. Tulis paling banyak " . $panjang . ' huruf.');
            }

            if ($wa === null) {
                $this->galat->tambah("penanggung_jawab.{$i}.nomor_wa", "{$ke}: " . lcfirst(NomorWa::PESAN_GALAT));
            }

            $isiFoto = null;

            if ($foto === null || $foto === '' || $foto === false) {
                $this->galat->tambah("penanggung_jawab.{$i}.foto", "{$ke}: foto belum diisi. Ambil foto dengan kamera atau pilih dari galeri.");
            } elseif ($periksaFoto) {
                $isiFoto = FotoPenanggungJawab::baca($foto);

                if ($isiFoto === null) {
                    $this->galat->tambah("penanggung_jawab.{$i}.foto", "{$ke}: foto belum bisa dibaca. Ambil ulang fotonya.");
                }
            }

            $hasil[] = ['nama' => $nama, 'nomor_wa' => (string) $wa, 'foto' => $isiFoto];
        }

        return $hasil;
    }
}
