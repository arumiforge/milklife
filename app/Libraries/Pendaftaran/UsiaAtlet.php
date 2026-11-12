<?php

namespace App\Libraries\Pendaftaran;

use DateTimeImmutable;

/**
 * Usia atlet sebagai keterangan saja (K37), dihitung pada tanggal mulai acara.
 * Tidak menentukan kelompok umur.
 *
 *     UsiaAtlet::teks('2016-03-10', '2026-12-09', 'tahun');        // '10 tahun'
 *     UsiaAtlet::teks('2016-03-10', '2026-12-09', 'tahun_bulan');  // '10 tahun 8 bulan'
 *     UsiaAtlet::teks('2016-03-10', null, 'tahun');                // ''
 */
final class UsiaAtlet
{
    /**
     * @param string $tampilan `tahun` atau `tahun_bulan` (`Pendaftaran.tampilan_usia`).
     */
    public static function teks(?string $tanggalLahir, ?string $tanggalAcara, string $tampilan = 'tahun'): string
    {
        $lahir = self::tanggal($tanggalLahir);
        $acara = self::tanggal($tanggalAcara);

        if ($lahir === null || $acara === null || $lahir > $acara) {
            return '';
        }

        $selisih = $lahir->diff($acara);

        if ($tampilan === 'tahun_bulan' && $selisih->m > 0) {
            return "{$selisih->y} tahun {$selisih->m} bulan";
        }

        return "{$selisih->y} tahun";
    }

    private static function tanggal(?string $nilai): ?DateTimeImmutable
    {
        if ($nilai === null || preg_match('/^\d{4}-\d{2}-\d{2}/', $nilai) !== 1) {
            return null;
        }

        $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', substr($nilai, 0, 10));

        return $tanggal === false ? null : $tanggal;
    }
}
