<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

/**
 * Induk pemeriksaan (bagian dari `PemeriksaPendaftaran`, F7-30): kelompok
 * umur dan nomor lomba per id, termasuk yang sudah dimatikan tetapi masih
 * dipakai pendaftaran yang diubah admin (K98), serta aturan pendaftaran.
 */
final class IndukPeriksa
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public readonly array $kelompokUmur;

    /**
     * @var array<int, array<string, mixed>>
     */
    public readonly array $nomorLomba;

    /**
     * Id kelompok umur yang sudah dimatikan (K98).
     *
     * @var array<int, true>
     */
    public readonly array $kuNonaktif;

    /**
     * Id nomor lomba yang sudah dimatikan (K98).
     *
     * @var array<int, true>
     */
    public readonly array $nomorNonaktif;

    /**
     * `induk['aturan']`: batas ofisial, nomor individu, kuota, dan mode regu.
     *
     * @var array<string, mixed>
     */
    public readonly array $aturan;

    /**
     * @param array<string, mixed> $induk `DataIndukPendaftaran::induk()`, boleh ditambah
     *                                    `kelompok_umur_nonaktif` dan `nomor_lomba_nonaktif`.
     */
    public function __construct(array $induk)
    {
        $kelompokUmur  = [];
        $nomorLomba    = [];
        $kuNonaktif    = [];
        $nomorNonaktif = [];

        foreach ($induk['kelompok_umur'] ?? [] as $ku) {
            $kelompokUmur[(int) $ku['id']] = $ku;
        }

        foreach ($induk['kelompok_umur_nonaktif'] ?? [] as $ku) {
            if (! isset($kelompokUmur[(int) $ku['id']])) {
                $kelompokUmur[(int) $ku['id']] = $ku;
                $kuNonaktif[(int) $ku['id']]   = true;
            }
        }

        foreach ($induk['nomor_lomba'] ?? [] as $nomor) {
            if (isset($kelompokUmur[(int) $nomor['kelompok_umur_id']])) {
                $nomorLomba[(int) $nomor['id']] = $nomor;
            }
        }

        foreach ($induk['nomor_lomba_nonaktif'] ?? [] as $nomor) {
            if (isset($kelompokUmur[(int) $nomor['kelompok_umur_id']]) && ! isset($nomorLomba[(int) $nomor['id']])) {
                $nomorLomba[(int) $nomor['id']]    = $nomor;
                $nomorNonaktif[(int) $nomor['id']] = true;
            }
        }

        $this->kelompokUmur  = $kelompokUmur;
        $this->nomorLomba    = $nomorLomba;
        $this->kuNonaktif    = $kuNonaktif;
        $this->nomorNonaktif = $nomorNonaktif;
        $this->aturan        = is_array($induk['aturan'] ?? null) ? $induk['aturan'] : [];
    }
}
