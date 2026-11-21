<?php

namespace App\Libraries;

use RuntimeException;
use Throwable;

/**
 * Foto carousel pembuka halaman depan (F9-03, K140 butir 6).
 *
 * Daftar foto disimpan berurutan di pengaturan `HalamanDepan.carousel`:
 *
 *     [['berkas' => 'unggahan/halaman-depan/3f2a….webp', 'keterangan' => 'Atlet melempar lembing'], …]
 *
 * `keterangan` menjadi teks pengganti gambar (`alt`) untuk pembaca layar.
 * Berkasnya di folder unggahan `halaman-depan` (`App\Libraries\Unggahan`),
 * jadi ikut cadangan harian bersama gambar lain.
 *
 * Foto bawaan dari pemilik (`data/halaman-depan/carousel/`, sudah dikecilkan
 * ke sisi terpanjang 1600 piksel) dipasang `php spark data:awal` untuk
 * pemasangan baru dan `php spark halaman-depan:carousel` untuk pemasangan
 * lama. Keduanya hanya memasang bila pengaturannya belum pernah diisi
 * (`null`); daftar yang sudah diatur admin, termasuk daftar kosong karena
 * admin menghapus semua foto, tidak ditimpa.
 *
 *     CarouselHalamanDepan::daftar();       // foto yang berkasnya ada, berurutan
 *     CarouselHalamanDepan::pasangBawaan(); // ['dipasang' => 11, 'gagal' => 0] atau null bila dilewati
 */
final class CarouselHalamanDepan
{
    public const KUNCI = 'HalamanDepan.carousel';

    public const FOLDER = 'halaman-depan';

    /**
     * Foto paling banyak di carousel.
     */
    public const BATAS = 20;

    /**
     * Panjang keterangan paling banyak.
     */
    public const BATAS_KETERANGAN = 150;

    /**
     * Foto bawaan di `data/`, berurutan: berkas => keterangan.
     *
     * @var array<string, string>
     */
    public const BAWAAN = [
        'halaman-depan/carousel/01-lari-cepat.webp'              => 'Atlet putra berlari kencang di lintasan',
        'halaman-depan/carousel/02-lompat-jauh.webp'             => 'Atlet putri mendarat di bak pasir lompat jauh',
        'halaman-depan/carousel/03-lempar-lembing.webp'          => 'Atlet putra melempar lembing',
        'halaman-depan/carousel/04-podium-juara.webp'            => 'Para juara berdiri di podium pemenang',
        'halaman-depan/carousel/05-estafet.webp'                 => 'Pergantian tongkat estafet di lintasan',
        'halaman-depan/carousel/06-lari-estafet-putri.webp'      => 'Atlet putri berlari membawa tongkat estafet',
        'halaman-depan/carousel/07-lari-rintangan-bendera.webp'  => 'Anak-anak berlomba lari berkelok di antara bendera',
        'halaman-depan/carousel/08-lari-gawang-mini.webp'        => 'Atlet melompati gawang mini di lintasan',
        'halaman-depan/carousel/09-lempar-cakram.webp'           => 'Atlet putra bersiap melempar cakram',
        'halaman-depan/carousel/10-lari-jarak-menengah.webp'     => 'Para atlet berlari jarak menengah di lintasan',
        'halaman-depan/carousel/11-estafet-lapangan.webp'        => 'Atlet berlari membawa tongkat estafet di lapangan rumput',
    ];

    /**
     * Isi pengaturan apa adanya (dirapikan), termasuk foto yang berkasnya
     * sudah hilang. `null` bila belum pernah diatur.
     *
     * @return list<array{berkas: string, keterangan: string}>|null
     */
    public static function tersimpan(): ?array
    {
        try {
            $nilai = setting(self::KUNCI);
        } catch (Throwable $e) {
            log_message('error', 'Pengaturan carousel tidak terbaca: {pesan}', ['pesan' => $e->getMessage()]);

            return [];
        }

        if ($nilai === null) {
            return null;
        }

        if (is_string($nilai)) {
            $nilai = json_decode($nilai, true);
        }

        if (! is_array($nilai)) {
            return [];
        }

        $hasil = [];

        foreach ($nilai as $butir) {
            if (is_array($butir) && is_string($butir['berkas'] ?? null) && $butir['berkas'] !== '') {
                $hasil[] = ['berkas' => $butir['berkas'], 'keterangan' => trim((string) ($butir['keterangan'] ?? ''))];
            }
        }

        return $hasil;
    }

    /**
     * Foto yang berkasnya ada di disk, berurutan seperti diatur admin.
     *
     * @return list<array{berkas: string, keterangan: string}>
     */
    public static function daftar(?Unggahan $unggahan = null): array
    {
        $unggahan ??= new Unggahan();

        return array_values(array_filter(
            self::tersimpan() ?? [],
            static fn (array $butir): bool => $unggahan->ada($butir['berkas']),
        ));
    }

    /**
     * Simpan daftar foto berurutan.
     *
     * @param list<array{berkas: string, keterangan: string}> $daftar
     */
    public static function simpan(array $daftar): void
    {
        setting(self::KUNCI, array_values(array_map(
            static fn (array $butir): array => ['berkas' => (string) $butir['berkas'], 'keterangan' => (string) $butir['keterangan']],
            $daftar,
        )));
    }

    /**
     * Pasang foto bawaan bila pengaturan carousel belum pernah diisi.
     * Foto yang gagal disalin dilewati.
     *
     * @return array{dipasang: int, gagal: int}|null `null` bila dilewati karena sudah diatur
     */
    public static function pasangBawaan(): ?array
    {
        if (self::tersimpan() !== null) {
            return null;
        }

        $daftar = [];
        $gagal  = 0;

        foreach (self::BAWAAN as $berkas => $keterangan) {
            try {
                $daftar[] = ['berkas' => self::salinKeUnggahan(ROOTPATH . 'data/' . $berkas), 'keterangan' => $keterangan];
            } catch (RuntimeException) {
                $gagal++;
            }
        }

        // Bila semua gagal, pengaturan tetap kosong supaya perintah bisa diulang.
        if ($daftar !== []) {
            self::simpan($daftar);
        }

        return ['dipasang' => count($daftar), 'gagal' => $gagal];
    }

    /**
     * Pindahkan foto ke posisi baru (0 = pertama). Posisi di luar daftar diabaikan.
     *
     * @param list<array{berkas: string, keterangan: string}> $daftar
     *
     * @return list<array{berkas: string, keterangan: string}>
     */
    public static function geser(array $daftar, int $dari, int $ke): array
    {
        if (! isset($daftar[$dari]) || ! isset($daftar[$ke]) || $dari === $ke) {
            return $daftar;
        }

        $butir = array_splice($daftar, $dari, 1);
        array_splice($daftar, $ke, 0, $butir);

        return array_values($daftar);
    }

    /**
     * Salin satu berkas ke `unggahan/halaman-depan/` dengan nama acak.
     *
     * @throws RuntimeException Bila berkas sumber tidak ada atau tidak bisa disalin.
     */
    private static function salinKeUnggahan(string $sumber): string
    {
        $tujuan = Unggahan::folderDasar() . self::FOLDER;
        $nama   = bin2hex(random_bytes(16)) . '.' . strtolower(pathinfo($sumber, PATHINFO_EXTENSION));

        if (! is_file($sumber) || (! is_dir($tujuan) && ! @mkdir($tujuan, 0755, true)) || ! @copy($sumber, $tujuan . DIRECTORY_SEPARATOR . $nama)) {
            throw new RuntimeException('Foto bawaan gagal disalin: ' . basename($sumber));
        }

        @chmod($tujuan . DIRECTORY_SEPARATOR . $nama, 0644);

        return 'unggahan/' . self::FOLDER . '/' . $nama;
    }
}
