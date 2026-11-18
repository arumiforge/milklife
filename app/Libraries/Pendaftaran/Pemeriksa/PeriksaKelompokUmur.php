<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use DateTimeImmutable;

/**
 * Tanggal lahir dan kelompok umur atlet (bagian dari `PemeriksaPendaftaran`,
 * F7-30): jenjang sekolah dan tahun lahir menentukan kelompok umur aktif
 * (`KelompokUmurAtlet`, K95). Atlet tersimpan yang kelompok umurnya sudah
 * dimatikan tetap di kelompok umur itu selama masih cocok (K98).
 */
final class PeriksaKelompokUmur
{
    /**
     * Kelompok umur aktif.
     *
     * @var list<array<string, mixed>>
     */
    private readonly array $aktif;

    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly string $hariIni,
        private readonly DaftarGalat $galat,
    ) {
        $this->aktif = array_values(array_diff_key($induk->kelompokUmur, $induk->kuNonaktif));
    }

    /**
     * @param array{kelompok_umur_id: int, nomor: list<int>}|null $simpan Atlet tersimpan (admin mengubah pendaftaran), atau null.
     *
     * @return array<string, mixed>|null Kelompok umur atlet, atau null bila belum bisa ditentukan.
     */
    public function periksa(?DateTimeImmutable $lahir, ?string $jenjang, ?array $simpan, string $jalur, string $ke): ?array
    {
        $ku = null;

        if ($lahir === null) {
            $this->galat->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Pilih tanggal lahir dari kalender.");
        } elseif ($lahir->format('Y-m-d') > $this->hariIni || (int) $lahir->format('Y') < PemeriksaPendaftaran::TAHUN_LAHIR_TERKECIL) {
            $this->galat->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Periksa lagi tahunnya.");
        } elseif ($jenjang !== null) {
            $tentu = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, $this->aktif);

            // Atlet tersimpan yang kelompok umurnya sudah dimatikan tetap di
            // kelompok umur itu selama tahun lahir dan jenjangnya masih cocok (K98).
            $kuLama = $simpan !== null && isset($this->induk->kuNonaktif[$simpan['kelompok_umur_id']]) ? $this->induk->kelompokUmur[$simpan['kelompok_umur_id']] : null;

            if ($kuLama !== null) {
                $tetap = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, [$kuLama]);
                $tentu = $tetap['galat'] === null ? $tetap : $tentu;
            }

            $ku = $tentu['kelompok_umur'];

            if ($tentu['galat'] !== null) {
                $this->galat->tambah("{$jalur}.tanggal_lahir", "{$ke}: {$tentu['galat']}");
            }
        }

        return $ku;
    }
}
