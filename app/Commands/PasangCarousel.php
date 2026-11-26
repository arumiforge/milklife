<?php

namespace App\Commands;

use App\Libraries\CarouselHalamanDepan;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Throwable;

/**
 * `php spark halaman-depan:carousel` — memasang foto bawaan carousel pembuka
 * halaman depan (F9-03) di pemasangan lama yang sudah menjalankan
 * `data:awal` sebelum carousel ada. Pemasangan baru mendapatkannya dari
 * `data:awal`.
 *
 * Di server yang carousel-nya sudah diatur, perintah ini mengganti salinan
 * foto bawaan lama dengan foto bawaan baru (K191); foto unggahan admin dan
 * urutannya tidak diubah.
 *
 * Aman dijalankan berulang: daftar foto yang sudah diatur admin di
 * Pengaturan > Halaman depan (termasuk bila semua fotonya sudah dihapus)
 * tidak ditimpa.
 */
class PasangCarousel extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'halaman-depan:carousel';
    protected $description = 'Memasang foto bawaan carousel halaman depan, atau mengganti foto bawaan lama dengan yang baru.';
    protected $usage       = 'halaman-depan:carousel';

    public function run(array $params): int
    {
        try {
            $hasil = CarouselHalamanDepan::pasangBawaan();
            $ganti = $hasil === null ? CarouselHalamanDepan::gantiBawaanLama() : null;
        } catch (Throwable $e) {
            CLI::error('Foto carousel tidak dipasang karena basis data belum siap. Pastikan MySQL menyala dan "php spark migrate --all" sudah dijalankan, lalu coba lagi.');
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        if ($ganti !== null) {
            self::tulisGanti($ganti);

            return $ganti['dihapus'] === 0 ? EXIT_ERROR : EXIT_SUCCESS;
        }

        self::tulisHasil($hasil);

        return $hasil !== null && $hasil['dipasang'] === 0 ? EXIT_ERROR : EXIT_SUCCESS;
    }

    /**
     * Pesan hasil pemasangan, dipakai juga `data:awal`.
     *
     * @param array{dipasang: int, gagal: int}|null $hasil hasil `CarouselHalamanDepan::pasangBawaan()`
     */
    public static function tulisHasil(?array $hasil): void
    {
        if ($hasil === null) {
            CLI::write('Foto carousel halaman depan sudah diatur admin, jadi tidak diubah.');

            return;
        }

        if ($hasil['dipasang'] > 0) {
            CLI::write($hasil['dipasang'] . ' foto carousel halaman depan dipasang (bisa ditambah, diurutkan, dan dihapus di Pengaturan > Halaman depan).');
        }

        if ($hasil['gagal'] > 0) {
            CLI::error($hasil['gagal'] . ' foto carousel gagal disalin. Pastikan folder public/unggahan boleh ditulisi, lalu jalankan "php spark halaman-depan:carousel" lagi.');
        }
    }

    /**
     * Pesan hasil penggantian foto bawaan lama.
     *
     * @param array{dipasang: int, dihapus: int, gagal: int} $hasil hasil `CarouselHalamanDepan::gantiBawaanLama()`
     */
    private static function tulisGanti(array $hasil): void
    {
        if ($hasil['dihapus'] > 0) {
            CLI::write($hasil['dihapus'] . ' foto bawaan lama diganti ' . $hasil['dipasang'] . ' foto bawaan baru. Foto unggahan admin tidak diubah.');
        }

        if ($hasil['gagal'] > 0) {
            CLI::error($hasil['gagal'] . ' foto bawaan baru gagal disalin. Pastikan folder public/unggahan boleh ditulisi, lalu jalankan "php spark halaman-depan:carousel" lagi.');
        }
    }
}
