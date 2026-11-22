<?php

namespace App\Libraries\Panel\Ubah;

use App\Libraries\Panel\TimTanpaPembayar;

/**
 * Akibat Ubah pendaftaran pada status pembayaran (bagian dari
 * `UbahPendaftaran`, F7-30): K32 (Lunas yang totalnya berubah kembali ke
 * Menunggu) dan kalimat jendela konfirmasinya, termasuk nomor atlet baru (K59).
 */
final class StatusUbah
{
    /**
     * K32: pendaftaran Lunas yang totalnya berubah kembali ke Menunggu.
     */
    public static function kembaliMenunggu(string $statusLama, int $total, int $totalLama): bool
    {
        return $statusLama === 'lunas' && $total !== $totalLama;
    }

    /**
     * Kalimat akibat K32 untuk jendela konfirmasi dan pesan galat.
     */
    public static function kalimatMenunggu(int $totalLama, int $totalBaru): string
    {
        return 'Pendaftaran ini sudah Lunas, dan perubahan ini mengubah total dari ' . rupiah($totalLama) . ' menjadi ' . rupiah($totalBaru) . '. '
            . 'Setelah disimpan, status kembali menjadi Menunggu Konfirmasi Pembayaran dan perlu ditandai Lunas lagi setelah selisihnya diurus. '
            . 'Nomor atlet (BIB) yang sudah dibagikan tetap dipegang atletnya.';
    }

    /**
     * Kalimat jendela konfirmasi simpan dari hasil `periksa()` tanpa galat:
     * akibat K32 bila status akan kembali ke Menunggu, selain itu total baru
     * dan nomor dada atlet baru (K59).
     *
     * @param array<string, mixed> $hasil
     */
    public static function kalimatKonfirmasi(array $hasil): string
    {
        $tim = TimTanpaPembayar::kalimatDitinggalkan($hasil['tim_ditinggalkan'] ?? []);
        $tim = $tim === '' ? '' : ' ' . $tim;

        if ($hasil['kembali_menunggu']) {
            return self::kalimatMenunggu((int) $hasil['total_lama'], (int) $hasil['total']) . $tim;
        }

        $kalimat = 'Perubahan pendaftaran ' . $hasil['id_pendaftaran'] . ' akan disimpan. '
            . ((int) $hasil['total'] === (int) $hasil['total_lama']
                ? 'Total tetap ' . rupiah((int) $hasil['total']) . '.'
                : 'Total berubah dari ' . rupiah((int) $hasil['total_lama']) . ' menjadi ' . rupiah((int) $hasil['total']) . '.');

        if ($hasil['status'] === 'lunas' && $hasil['atlet_baru'] > 0) {
            $kalimat .= ' Karena pendaftaran ini sudah Lunas, ' . $hasil['atlet_baru'] . ' atlet baru langsung mendapat nomor atlet (BIB), dan nomor itu tidak bisa ditarik kembali.';
        }

        return $kalimat . $tim;
    }
}
