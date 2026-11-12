<?php

namespace App\Libraries\Pendaftaran;

use App\Models\NomorLombaModel;

/**
 * Kelengkapan tim beregu (rancangan 6.6; K07, K09, K10).
 *
 * Tim baru: anggota paling sedikit jumlah inti dan paling banyak batas
 * anggota; nomor mix juga harus punya putra dan putri minimal. Tim susulan
 * (sekolah sudah punya tim di nomor itu dari pendaftaran sebelumnya): hanya
 * menambah anggota, jadi cukup anggota lama + baru tidak melewati batas.
 *
 *     KelengkapanTim::periksa($nomor, ['L', 'L', 'P']);          // list pesan, [] bila lengkap
 *     KelengkapanTim::periksa($nomor, ['L'], anggotaLama: 4);    // susulan
 *
 * `$nomor` adalah baris `nomor_lomba` (`nama`, `kategori`, `jumlah_inti`,
 * `batas_anggota`, `jumlah_putra`, `jumlah_putri`).
 */
final class KelengkapanTim
{
    /**
     * @param array<string, mixed> $nomor
     * @param list<string>         $jenisKelamin Jenis kelamin (`L`/`P`) tiap anggota baru.
     * @param int|null             $anggotaLama  Jumlah anggota tim dari pendaftaran sebelumnya;
     *                                           `null` = tim baru.
     *
     * @return list<string>
     */
    public static function periksa(array $nomor, array $jenisKelamin, ?int $anggotaLama = null): array
    {
        $nama   = 'Tim ' . NomorLombaModel::namaLengkap($nomor);
        $jumlah = count($jenisKelamin);
        $inti   = (int) ($nomor['jumlah_inti'] ?? 0);
        $batas  = (int) ($nomor['batas_anggota'] ?? 0);

        if ($anggotaLama !== null) {
            $sisa = max(0, $batas - $anggotaLama);

            if ($anggotaLama + $jumlah > $batas) {
                return ["{$nama} sudah punya {$anggotaLama} atlet dari pendaftaran sebelumnya, dan paling banyak {$batas} atlet. "
                    . ($sisa === 0 ? 'Tim ini sudah penuh; hapus nomor ini dari atlet tambahan.' : "Atlet tambahan paling banyak {$sisa}; hapus " . ($anggotaLama + $jumlah - $batas) . ' atlet dari nomor ini.')];
            }

            return [];
        }

        $pesan = [];

        if ($jumlah < $inti) {
            $pesan[] = "{$nama} baru punya {$jumlah} atlet. Tambah " . ($inti - $jumlah) . " atlet lagi; tim ini minimal {$inti} atlet.";
        } elseif ($jumlah > $batas) {
            $pesan[] = "{$nama} punya {$jumlah} atlet, padahal paling banyak {$batas}. Hapus " . ($jumlah - $batas) . ' atlet dari nomor ini.';
        }

        if (($nomor['kategori'] ?? '') === 'mix') {
            $perlu = ['L' => (int) ($nomor['jumlah_putra'] ?? 0), 'P' => (int) ($nomor['jumlah_putri'] ?? 0)];
            $ada   = ['L' => 0, 'P' => 0];

            foreach ($jenisKelamin as $jk) {
                if (isset($ada[$jk])) {
                    $ada[$jk]++;
                }
            }

            $kurang = [];

            if ($ada['L'] < $perlu['L']) {
                $kurang[] = ($perlu['L'] - $ada['L']) . ' putra';
            }

            if ($ada['P'] < $perlu['P']) {
                $kurang[] = ($perlu['P'] - $ada['P']) . ' putri';
            }

            if ($kurang !== []) {
                $pesan[] = "{$nama} minimal {$perlu['L']} putra dan {$perlu['P']} putri; sekarang {$ada['L']} putra dan {$ada['P']} putri. Tambah " . implode(' dan ', $kurang) . '.';
            }
        }

        return $pesan;
    }
}
