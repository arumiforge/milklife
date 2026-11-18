<?php

namespace App\Libraries\HariLomba;

/**
 * Pengurai isian daftar ulang (F8-03): hasil pindai kode QR bukti
 * pendaftaran atau ID pendaftaran yang diketik admin.
 *
 * Kode QR bukti berisi alamat `…/bukti/<kode acak 24 heksa>/<ID>` (K77).
 * Admin sudah masuk, jadi cukup ID-nya yang dipakai; kode acaknya tidak
 * diperiksa. Kelas murni tanpa basis data, diuji di `tests/unit`.
 *
 *     KodeDaftarUlang::idDari('https://contoh.test/bukti/0123456789abcdef01234567/MAC2A0000126'); // 'MAC2A0000126'
 *     KodeDaftarUlang::idDari(' mac2a0000126 ');  // 'MAC2A0000126'
 *     KodeDaftarUlang::idDari('halo');            // null
 */
final class KodeDaftarUlang
{
    /**
     * Bentuk ID pendaftaran, sama dengan pemeriksaan di `BuktiController`.
     */
    public const POLA_ID = '/^MAC[0-9A-Z]{1,17}$/D';

    public static function idDari(mixed $teks): ?string
    {
        if (! is_string($teks)) {
            return null;
        }

        $teks = trim($teks);

        if (preg_match('~/bukti/[0-9a-f]{24}/([^/?#\s]+)~i', $teks, $m) === 1) {
            $teks = rawurldecode($m[1]);
        }

        $id = strtoupper(trim($teks));

        return preg_match(self::POLA_ID, $id) === 1 ? $id : null;
    }
}
