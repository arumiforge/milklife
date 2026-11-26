<?php

namespace App\Libraries\Galeri;

use App\Libraries\Unggahan;
use App\Models\GaleriModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;

/**
 * Album galeri bawaan "Seri sebelumnya" (F9-06), dipasang `php spark
 * data:awal` (pemasangan baru) dan `php spark galeri:awal` (pemasangan lama).
 *
 * Sampulnya salah satu foto carousel bawaan F9-03 (`data/halaman-depan/carousel/`,
 * foto acara sebelumnya dari pemilik, K140 butir 6), disalin ke
 * `unggahan/galeri/` dan dikecilkan ke WebP `GaleriModel::SISI_SAMPUL`.
 * Tautannya kosong sampai admin mengisinya di Pengaturan > Galeri, jadi
 * album ini belum tampil di `/galeri`.
 *
 * Hanya dipasang sekali: bila sudah pernah dipasang (`Galeri.bawaan_dipasang`)
 * atau admin sudah punya album sendiri, tidak ada yang diubah. Album yang
 * sudah dihapus admin tidak muncul lagi.
 *
 *     AlbumBawaan::pasang(); // ['sampul' => true] atau null bila dilewati
 */
final class AlbumBawaan
{
    public const JUDUL = 'Seri sebelumnya';

    public const KETERANGAN = 'Foto-foto MilkLife Athletics Challenge seri sebelumnya.';

    /**
     * Foto sampul di `data/`.
     */
    public const SAMPUL = 'halaman-depan/carousel/05-selebrasi-finis.webp';

    /**
     * Pasang album bawaan bila belum pernah dipasang dan tabel `galeri` masih kosong.
     *
     * @param string|null $akarData Folder `data/` (untuk uji); bawaan `ROOTPATH . 'data/'`.
     *
     * @return array{sampul: bool}|null `null` bila dilewati
     *
     * @throws RuntimeException Bila album gagal disimpan.
     */
    public static function pasang(?BaseConnection $db = null, ?string $akarData = null): ?array
    {
        $db ??= db_connect();

        if ((bool) setting('Galeri.bawaan_dipasang')) {
            return null;
        }

        if ($db->table('galeri')->countAllResults() > 0) {
            // Admin sudah mengisi galeri sendiri: jangan ditambah.
            setting('Galeri.bawaan_dipasang', true);

            return null;
        }

        $sampul = self::salinSampul(rtrim($akarData ?? ROOTPATH . 'data', '/\\') . DIRECTORY_SEPARATOR . self::SAMPUL);
        $waktu  = Time::now()->toDateTimeString();

        if (! $db->table('galeri')->insert([
            'judul'       => self::JUDUL,
            'keterangan'  => self::KETERANGAN,
            'tautan'      => null,
            'sampul'      => $sampul,
            'urutan'      => 1,
            'aktif'       => 1,
            'dibuat_pada' => $waktu,
            'diubah_pada' => $waktu,
        ])) {
            (new Unggahan())->hapus($sampul);

            throw new RuntimeException('Album galeri bawaan gagal disimpan.');
        }

        setting('Galeri.bawaan_dipasang', true);

        return ['sampul' => $sampul !== null];
    }

    /**
     * Salin foto sampul ke `unggahan/galeri/` sebagai WebP kecil. `null` bila gagal.
     */
    private static function salinSampul(string $sumber): ?string
    {
        $folder = Unggahan::folderDasar() . GaleriModel::FOLDER;
        $tujuan = $folder . DIRECTORY_SEPARATOR . bin2hex(random_bytes(16)) . '.' . strtolower(pathinfo($sumber, PATHINFO_EXTENSION));

        if (! is_file($sumber) || (! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) || ! @copy($sumber, $tujuan)) {
            return null;
        }

        @chmod($tujuan, 0644);
        $webp = Unggahan::jadikanWebp($tujuan, GaleriModel::SISI_SAMPUL) ?? $tujuan;

        return 'unggahan/' . GaleriModel::FOLDER . '/' . basename($webp);
    }
}
