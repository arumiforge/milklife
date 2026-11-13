<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\NomorWa;
use Config\Pembayaran;

/**
 * Pesan WA konfirmasi pembayaran (rancangan 7, langkah 5).
 *
 * Penanda di `Pembayaran.templat_pesan` (`Config\Pembayaran::PENANDA`) diganti
 * dengan data pendaftaran. Uang ditulis `Rp 150.000`; NPSN kosong ditulis "-".
 *
 *     PesanKonfirmasi::teks($templat, [
 *         'nama_sekolah' => 'SD Karangan', 'npsn' => null, 'id_pendaftaran' => 'MAC2A0000126',
 *         'subtotal_individu' => 150000, 'subtotal_beregu' => 0, 'total' => 150000,
 *     ]);
 *
 *     PesanKonfirmasi::alamat('6281234567890', $teks); // 'https://wa.me/6281234567890?text=…'
 */
final class PesanKonfirmasi
{
    /**
     * @param array{nama_sekolah: string, npsn: string|null, id_pendaftaran: string, subtotal_individu: int, subtotal_beregu: int, total: int} $data
     */
    public static function teks(string $templat, array $data): string
    {
        $npsn = trim((string) ($data['npsn'] ?? ''));

        $ganti = [
            '{nama_sekolah}'      => $data['nama_sekolah'],
            '{npsn}'              => $npsn !== '' ? $npsn : '-',
            '{id_pendaftaran}'    => $data['id_pendaftaran'],
            '{subtotal_individu}' => rupiah((int) $data['subtotal_individu']),
            '{subtotal_beregu}'   => rupiah((int) $data['subtotal_beregu']),
            '{total}'             => rupiah((int) $data['total']),
        ];

        return strtr($templat, array_intersect_key($ganti, Pembayaran::PENANDA));
    }

    /**
     * Alamat `https://wa.me/<nomor>?text=…`, atau `null` bila nomor WA belum sah.
     */
    public static function alamat(?string $nomorWa, string $teks): ?string
    {
        $nomor = NomorWa::normalkan($nomorWa);

        if ($nomor === null) {
            return null;
        }

        return 'https://wa.me/' . $nomor . ($teks !== '' ? '?text=' . rawurlencode($teks) : '');
    }
}
