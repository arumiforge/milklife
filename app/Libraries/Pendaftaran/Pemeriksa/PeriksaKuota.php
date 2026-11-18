<?php

namespace App\Libraries\Pendaftaran\Pemeriksa;

use App\Models\NomorLombaModel;

/**
 * Kuota atlet per sekolah per nomor individu (bagian dari
 * `PemeriksaPendaftaran`, F7-30; K51), termasuk atlet sekolah yang sama dari
 * pendaftaran sebelumnya (NPSN sama).
 */
final class PeriksaKuota
{
    public function __construct(
        private readonly IndukPeriksa $induk,
        private readonly DaftarGalat $galat,
    ) {
    }

    /**
     * @param array<int, int> $individu Nomor individu => jumlah atlet kiriman ini.
     * @param mixed           $ada      `ada['individu']`: nomor individu => jumlah atlet sekolah ini sebelumnya.
     */
    public function periksa(array $individu, ?string $npsn, mixed $ada): void
    {
        $batas = $this->induk->aturan['atlet_per_nomor_maksimal'] ?? null;

        if ($batas === null || $batas === '' || (int) $batas < 1) {
            return;
        }

        $batas = (int) $batas;
        $ada   = $npsn !== null ? ($ada ?? []) : [];

        foreach ($individu as $nomorId => $jumlah) {
            $lama = (int) ($ada[$nomorId] ?? 0);

            if ($lama + $jumlah <= $batas) {
                continue;
            }

            $nama = NomorLombaModel::namaLengkap($this->induk->nomorLomba[$nomorId]);

            $this->galat->tambah("individu.{$nomorId}", $lama > 0
                ? "{$nama}: sekolah ini sudah mendaftarkan {$lama} atlet sebelumnya, dan paling banyak {$batas} atlet per sekolah. "
                    . ($lama >= $batas ? 'Nomor ini sudah penuh untuk sekolah ini.' : 'Atlet tambahan paling banyak ' . ($batas - $lama) . '.')
                : "{$nama}: paling banyak {$batas} atlet per sekolah, sekarang {$jumlah}. Hapus " . ($jumlah - $batas) . ' atlet dari nomor ini.');
        }
    }
}
