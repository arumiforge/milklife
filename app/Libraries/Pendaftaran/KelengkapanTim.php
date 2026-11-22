<?php

namespace App\Libraries\Pendaftaran;

use App\Models\NomorLombaModel;

/**
 * Kelengkapan satu regu beregu (rancangan 6.6; K09, K10, K105).
 *
 * Anggota regu (lama dari pendaftaran sebelumnya ditambah yang baru) paling
 * banyak batas anggota. Atlet inti harus **tepat** sejumlah inti nomor itu;
 * nomor mix juga harus punya putra dan putri minimal di antara atlet inti.
 *
 * Mode longgar (admin mengubah regu yang sudah terdaftar): inti boleh kurang
 * dari jumlah inti supaya perubahan lain tetap bisa disimpan, tetapi tidak
 * boleh lebih; syarat mix diperiksa bila inti sudah lengkap.
 *
 *     $anggota = [['jenis_kelamin' => 'L', 'peran' => 'inti'], …];
 *     KelengkapanTim::periksa($nomor, 'A', $anggota);                 // list pesan, [] bila lengkap
 *     KelengkapanTim::periksa($nomor, 'A', $baru, lama: $anggotaLama); // susulan
 *     KelengkapanTim::periksa($nomor, 'A', $anggota, longgar: true);   // admin, regu tersimpan
 *     KelengkapanTim::periksa($nomor, 'A', $anggota, tunggal: true);   // pesan tanpa nama regu (K108)
 *
 * `$nomor` adalah baris `nomor_lomba` (`nama`, `kategori`, `jumlah_inti`,
 * `batas_anggota`, `jumlah_putra`, `jumlah_putri`).
 */
final class KelengkapanTim
{
    /**
     * @param array<string, mixed>                                     $nomor
     * @param list<array{jenis_kelamin: string|null, peran: string}>      $baru Anggota dari kiriman ini.
     * @param list<array{jenis_kelamin: string|null, peran: string}>|null $lama Anggota regu dari pendaftaran
     *                                                                         sebelumnya (inti yang diturunkan
     *                                                                         sudah cadangan); `null` = regu baru.
     * @param bool                                                     $tunggal Satu-satunya regu sekolah di nomor ini:
     *                                                                          pesan menulis nama nomor saja (K108).
     *
     * @return list<string>
     */
    public static function periksa(array $nomor, string $regu, array $baru, ?array $lama = null, bool $longgar = false, bool $tunggal = false): array
    {
        $nama   = Regu::nama(NomorLombaModel::namaLengkap($nomor), $regu, $tunggal);
        $semua  = array_merge($lama ?? [], $baru);
        $jumlah = count($semua);
        $jLama  = count($lama ?? []);
        $inti   = (int) ($nomor['jumlah_inti'] ?? 0);
        $batas  = (int) ($nomor['batas_anggota'] ?? 0);
        $pesan  = [];

        if ($jumlah > $batas) {
            if ($lama !== null) {
                $sisa    = max(0, $batas - $jLama);
                $pesan[] = "{$nama} sudah punya {$jLama} atlet dari pendaftaran sebelumnya, dan paling banyak {$batas} atlet. "
                    . ($sisa === 0
                        ? 'Regu ini sudah penuh. Pilih regu lain untuk atlet tambahan.'
                        : "Atlet tambahan paling banyak {$sisa}. Pindahkan " . ($jumlah - $batas) . ' atlet ke regu lain atau hapus dari nomor ini.');
            } else {
                $pesan[] = "{$nama} punya {$jumlah} atlet. Batasnya {$batas} atlet. Pindahkan " . ($jumlah - $batas) . ' atlet ke regu lain atau hapus dari nomor ini.';
            }
        }

        $atletInti = array_values(array_filter($semua, static fn (array $a): bool => ($a['peran'] ?? '') === Regu::INTI));
        $adaInti   = count($atletInti);
        $intiLama  = count(array_filter($lama ?? [], static fn (array $a): bool => ($a['peran'] ?? '') === Regu::INTI));
        $keterangan = $lama !== null && $intiLama > 0
            ? " ({$intiLama} di antaranya dari " . ($longgar ? 'pendaftaran lain sekolah ini' : 'pendaftaran sebelumnya') . ')'
            : '';

        if ($adaInti > $inti) {
            // Admin (longgar) mengubah inti pendaftaran lain lewat Ubah pendaftaran itu.
            $pesan[] = "{$nama} punya {$adaInti} atlet inti{$keterangan}. Satu regu harus tepat {$inti} atlet inti. "
                . 'Jadikan ' . ($adaInti - $inti) . ' atlet cadangan'
                . ($lama !== null && $intiLama > 0
                    ? ($longgar ? ' di pendaftaran ini, atau ubah dulu pendaftaran lain itu.' : ', atau pilih inti lama yang menjadi cadangan.')
                    : '.');
        } elseif ($adaInti < $inti && ! $longgar) {
            $pesan[] = $jumlah < $inti
                ? "{$nama} baru punya {$jumlah} atlet. Tambah " . ($inti - $jumlah) . " atlet lagi. Satu regu harus tepat {$inti} atlet inti."
                : "{$nama} baru punya {$adaInti} atlet inti{$keterangan}. Tandai " . ($inti - $adaInti) . " atlet lagi sebagai Inti. Satu regu harus tepat {$inti} atlet inti.";
        }

        if (($nomor['kategori'] ?? '') === 'mix' && $adaInti === $inti) {
            $perlu = ['L' => (int) ($nomor['jumlah_putra'] ?? 0), 'P' => (int) ($nomor['jumlah_putri'] ?? 0)];
            $ada   = ['L' => 0, 'P' => 0];

            foreach ($atletInti as $a) {
                if (isset($ada[$a['jenis_kelamin'] ?? ''])) {
                    $ada[$a['jenis_kelamin']]++;
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
                $pesan[] = "{$nama}: atlet inti minimal {$perlu['L']} putra dan {$perlu['P']} putri. Sekarang {$ada['L']} putra dan {$ada['P']} putri. "
                    . 'Jadikan ' . implode(' dan ', $kurang) . ' sebagai inti.';
            }
        }

        return $pesan;
    }
}
