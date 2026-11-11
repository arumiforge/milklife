<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Rekening dan konfirmasi pembayaran lewat WA (rancangan 7, langkah 5).
 *
 * Nilai di sini hanya nilai awal; baca dan tulis lewat
 * `setting('Pembayaran.<kunci>')` (lihat `Config\Acara`).
 */
class Pembayaran extends BaseConfig
{
    /**
     * Penanda yang boleh dipakai di `templat_pesan`, beserta keterangannya.
     * Diganti dengan data pendaftaran saat pesan WA dibuat (Tahap 4).
     */
    public const PENANDA = [
        '{nama_sekolah}'      => 'Nama sekolah',
        '{npsn}'              => 'NPSN sekolah',
        '{id_pendaftaran}'    => 'ID pendaftaran',
        '{subtotal_individu}' => 'Subtotal biaya nomor individu',
        '{subtotal_beregu}'   => 'Subtotal biaya nomor beregu',
        '{total}'             => 'Total yang harus dibayar',
    ];

    /**
     * Nama bank, contoh `BCA`.
     */
    public string $bank = '';

    /**
     * Nomor rekening, hanya angka.
     */
    public string $nomor_rekening = '';

    /**
     * Nama pemilik rekening.
     */
    public string $pemilik_rekening = '';

    /**
     * Nomor WA pemilik rekening, bentuk baku `628…` (`App\Libraries\NomorWa`).
     */
    public string $nomor_wa = '';

    /**
     * Logo bank, jalur hasil `App\Libraries\Unggahan`; boleh kosong.
     */
    public string $logo_bank = '';

    /**
     * Isi pesan konfirmasi pembayaran yang dikirim official lewat WA.
     * Penanda di `PENANDA` diganti dengan data pendaftaran.
     */
    public string $templat_pesan = "Halo Admin, kami ingin mengonfirmasi pembayaran pendaftaran MilkLife Athletics Challenge.\n\n"
        . "Nama sekolah: {nama_sekolah}\n"
        . "NPSN: {npsn}\n"
        . "ID pendaftaran: {id_pendaftaran}\n"
        . "Subtotal individu: {subtotal_individu}\n"
        . "Subtotal beregu: {subtotal_beregu}\n"
        . "Total: {total}\n\n"
        . 'Bukti transfer kami lampirkan. Terima kasih.';
}
