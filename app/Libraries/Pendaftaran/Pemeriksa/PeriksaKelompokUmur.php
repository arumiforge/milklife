<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use DateTimeImmutable;

/**
 * Tanggal lahir dan kelompok umur atlet (bagian dari `PemeriksaPendaftaran`,
 * F7-30): jenjang sekolah dan tahun lahir menentukan kelompok umur aktif
 * (`KelompokUmurAtlet`, K95). Bila tahun lahirnya masuk lebih dari satu
 * kelompok umur jenjang itu, kelompok umur pilihan pendaftar yang dipakai
 * (K134); atlet tersimpan tanpa pilihan tetap di kelompok umur tersimpannya.
 * Atlet tersimpan yang kelompok umurnya sudah dimatikan tetap di kelompok umur
 * itu selama masih cocok (K98).
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
     * @param array{kelompok_umur_id: int, nomor: list<int>}|null $simpan  Atlet tersimpan (admin mengubah pendaftaran), atau null.
     * @param int|null                                            $pilihan Kelompok umur pilihan pendaftar (K134), atau null.
     *
     * @return array<string, mixed>|null Kelompok umur atlet, atau null bila belum bisa ditentukan.
     */
    public function periksa(?DateTimeImmutable $lahir, ?string $jenjang, ?array $simpan, string $jalur, string $ke, ?int $pilihan = null): ?array
    {
        $pilihan ??= $simpan !== null ? (int) $simpan['kelompok_umur_id'] : null;

        $ku = null;

        if ($lahir === null) {
            $this->galat->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Pilih tanggal lahir dari kalender.");
        } elseif ($lahir->format('Y-m-d') > $this->hariIni || (int) $lahir->format('Y') < PemeriksaPendaftaran::TAHUN_LAHIR_TERKECIL) {
            $this->galat->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Periksa lagi tahunnya.");
        } elseif ($jenjang !== null) {
            $tentu = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, $this->aktif, $pilihan);

            // Atlet tersimpan yang kelompok umurnya sudah dimatikan tetap di
            // kelompok umur itu selama tahun lahir dan jenjangnya masih cocok (K98).
            $kuLama = $simpan !== null && isset($this->induk->kuNonaktif[$simpan['kelompok_umur_id']]) ? $this->induk->kelompokUmur[$simpan['kelompok_umur_id']] : null;

            if ($kuLama !== null && ($pilihan === null || $pilihan === (int) $kuLama['id'])) {
                $tetap = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, [$kuLama]);
                $tentu = $tetap['galat'] === null ? $tetap : $tentu;
            }

            $ku = $tentu['kelompok_umur'];

            if ($tentu['galat'] !== null) {
                // Belum memilih di antara beberapa kelompok umur: galat di kotak pilihan KU.
                $kotak = $tentu['pilihan'] === [] ? 'tanggal_lahir' : 'kelompok_umur_id';
                $this->galat->tambah("{$jalur}.{$kotak}", "{$ke}: {$tentu['galat']}");
            }
        }

        return $ku;
    }
}
