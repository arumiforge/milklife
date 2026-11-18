<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

/**
 * NISN atlet (bagian dari `PemeriksaPendaftaran`, F7-30): tepat 10 angka,
 * sekali saja di satu kiriman, dan belum terdaftar di basis data. Satu
 * objek untuk satu kiriman, karena mengingat NISN yang sudah dipakai.
 */
final class PeriksaNisn
{
    /**
     * @var array<string, int>
     */
    private readonly array $terdaftar;

    /**
     * NISN yang sudah dipakai atlet sebelumnya di kiriman ini => nama atlet itu.
     *
     * @var array<string, string>
     */
    private array $dipakai = [];

    /**
     * @param list<mixed> $terdaftar `ada['nisn_terdaftar']`: NISN kiriman yang sudah ada di basis data.
     */
    public function __construct(array $terdaftar, private readonly DaftarGalat $galat)
    {
        $this->terdaftar = array_flip(array_map('strval', $terdaftar));
    }

    public function periksa(string $nisn, string $jalur, string $ke): void
    {
        if (preg_match('/^\d{10}$/D', $nisn) !== 1) {
            $this->galat->tambah("{$jalur}.nisn", "{$ke}: NISN harus tepat 10 angka. Periksa lagi NISN-nya.");
        } elseif (isset($this->dipakai[$nisn])) {
            $this->galat->tambah("{$jalur}.nisn", "{$ke}: NISN {$nisn} sudah dipakai {$this->dipakai[$nisn]} di pendaftaran ini. Satu atlet cukup didaftarkan sekali dengan semua nomornya.");
        } elseif (isset($this->terdaftar[$nisn])) {
            $this->galat->tambah("{$jalur}.nisn", "{$ke}: NISN {$nisn} sudah terdaftar. Atlet yang sudah terdaftar tidak bisa didaftarkan lagi; hubungi panitia bila perlu mengubah datanya.");
        } else {
            $this->dipakai[$nisn] = $ke;
        }
    }
}
