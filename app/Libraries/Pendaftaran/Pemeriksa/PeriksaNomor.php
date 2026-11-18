<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Models\NomorLombaModel;

/**
 * Nomor lomba satu atlet (bagian dari `PemeriksaPendaftaran`, F7-30): nomor
 * yang dibuka, sesuai kelompok umur dan jenis kelamin atlet, dan paling banyak
 * sekian nomor individu. Nomor yang sudah dimatikan hanya diterima untuk atlet
 * tersimpan yang sudah memakainya (K98).
 */
final class PeriksaNomor
{
    private const JENIS_KELAMIN_KATEGORI = ['putra' => 'L', 'putri' => 'P'];

    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * @param array<string, mixed>|null $ku
     * @param list<int>                 $nomorLama nomor tersimpan atlet ini; nomor yang sudah dimatikan hanya diterima bila ada di sini (K98)
     *
     * @return list<array<string, mixed>> baris nomor lomba yang sah untuk atlet ini
     */
    public function periksa(mixed $daftar, string $jalur, string $ke, ?array $ku, ?string $jk, int $maksimalIndividu, array $nomorLama = []): array
    {
        $id = [];

        foreach (MasukanPeriksa::daftar($daftar) as $nilai) {
            if ((is_int($nilai) || (is_string($nilai) && ctype_digit($nilai))) && (int) $nilai > 0) {
                $id[(int) $nilai] = true;
            } else {
                $this->galat->tambah("{$jalur}.nomor", "{$ke}: pilihan nomor lomba tidak dikenal. Pilih ulang nomor lombanya.");
            }
        }

        if ($id === []) {
            $this->galat->tambah("{$jalur}.nomor", "{$ke}: belum ikut nomor lomba apa pun. Pilih minimal satu nomor individu atau beregu.");

            return [];
        }

        $sah            = [];
        $jumlahIndividu = 0;

        foreach (array_keys($id) as $nomorId) {
            $nomor = $this->induk->nomorLomba[$nomorId] ?? null;

            if ($nomor !== null && isset($this->induk->nomorNonaktif[$nomorId]) && ! in_array($nomorId, $nomorLama, true)) {
                $nomor = null;
            }

            if ($nomor === null) {
                $this->galat->tambah("{$jalur}.nomor", "{$ke}: ada nomor lomba yang sudah tidak dibuka. Pilih ulang nomor lombanya.");

                continue;
            }

            $namaNomor = NomorLombaModel::namaLengkap($nomor);

            if ($ku !== null && (int) $nomor['kelompok_umur_id'] !== (int) $ku['id']) {
                $milik = $this->induk->kelompokUmur[(int) $nomor['kelompok_umur_id']]['nama'] ?? '';
                $this->galat->tambah("{$jalur}.nomor", "{$ke}: {$namaNomor} untuk {$milik}, sedangkan atlet ini {$ku['nama']}. Pilih nomor {$ku['nama']}.");

                continue;
            }

            $harus = self::JENIS_KELAMIN_KATEGORI[$nomor['kategori']] ?? null;

            if ($jk !== null && $harus !== null && $harus !== $jk) {
                $this->galat->tambah("{$jalur}.nomor", "{$ke}: {$namaNomor} hanya untuk atlet " . ($harus === 'L' ? 'laki-laki' : 'perempuan') . '. Pilih nomor yang sesuai jenis kelaminnya.');

                continue;
            }

            if ($nomor['jenis'] === 'individu') {
                $jumlahIndividu++;
            }

            $sah[] = $nomor;
        }

        if ($jumlahIndividu > $maksimalIndividu) {
            $this->galat->tambah("{$jalur}.nomor", "{$ke}: ikut {$jumlahIndividu} nomor individu, padahal paling banyak {$maksimalIndividu}. Hapus " . ($jumlahIndividu - $maksimalIndividu) . ' nomor individu.');
        }

        return $sah;
    }
}
