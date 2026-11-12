<?php

namespace App\Libraries\Cadangan;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

/**
 * Nama berkas cadangan dan aturan masa simpannya (K66). Kelas murni: tanpa
 * berkas, basis data, atau jam sistem.
 *
 * Nama berkas: `cadangan-milklife-<tttt-bb-hh-jjmm>.zip`. Umur cadangan
 * dibaca dari waktu di namanya, bukan dari waktu berkas di diska, supaya
 * tetap benar setelah berkas disalin atau dipindahkan.
 *
 *     MasaSimpan::namaBerkas($sekarang);                    // 'cadangan-milklife-2026-11-12-0200.zip'
 *     MasaSimpan::kedaluwarsa($daftarNama, $sekarang, 14);  // nama yang harus dihapus
 */
final class MasaSimpan
{
    private const POLA = '/^cadangan-milklife-(\d{4}-\d{2}-\d{2}-\d{4})\.zip$/';

    public static function namaBerkas(DateTimeInterface $waktu): string
    {
        return 'cadangan-milklife-' . $waktu->format('Y-m-d-Hi') . '.zip';
    }

    /**
     * Nama cadangan yang berumur `$hari` hari atau lebih pada `$sekarang`,
     * jadi cadangan harian yang tersisa paling banyak `$hari` berkas. Nama
     * yang bukan cadangan aplikasi ini tidak pernah disebut.
     *
     * @param list<string> $nama Nama berkas (tanpa folder).
     *
     * @return list<string>
     *
     * @throws InvalidArgumentException Bila `$hari` kurang dari 1.
     */
    public static function kedaluwarsa(array $nama, DateTimeInterface $sekarang, int $hari): array
    {
        if ($hari < 1) {
            throw new InvalidArgumentException('cadangan.simpanHari paling sedikit 1. Perbaiki di .env, lalu jalankan perintah ini lagi.');
        }

        $batas = DateTimeImmutable::createFromInterface($sekarang)->modify("-{$hari} days");
        $hapus = [];

        foreach ($nama as $berkas) {
            if (preg_match(self::POLA, $berkas, $cocok) !== 1) {
                continue;
            }

            $waktu = DateTimeImmutable::createFromFormat('!Y-m-d-Hi', $cocok[1], $sekarang->getTimezone());

            if ($waktu !== false && $waktu <= $batas) {
                $hapus[] = $berkas;
            }
        }

        sort($hapus);

        return $hapus;
    }
}
