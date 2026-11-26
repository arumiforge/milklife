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
 * admin menghapus semua foto, tidak ditimpa. Pengecualiannya salinan foto
 * bawaan lama: `halaman-depan:carousel` menggantinya dengan foto bawaan baru
 * (K191) dan membiarkan foto unggahan admin.
 *
 *     CarouselHalamanDepan::daftar();          // foto yang berkasnya ada, berurutan
 *     CarouselHalamanDepan::pasangBawaan();    // ['dipasang' => 9, 'gagal' => 0] atau null bila dilewati
 *     CarouselHalamanDepan::gantiBawaanLama(); // ['dipasang' => 9, 'dihapus' => 11, 'gagal' => 0] atau null
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
     * Sisi terpanjang foto carousel dalam piksel, untuk foto bawaan dan unggahan admin (K143).
     */
    public const SISI = 1600;

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
        'halaman-depan/carousel/01-lari-putri.webp'        => 'Atlet putri berlomba lari di lintasan',
        'halaman-depan/carousel/02-lompat-jauh-putri.webp' => 'Atlet putri tersenyum saat mendarat di bak pasir lompat jauh',
        'halaman-depan/carousel/03-lompat-kerucut.webp'    => 'Anak-anak berlomba melompat di antara kerucut lintasan',
        'halaman-depan/carousel/04-lompat-jauh-putra.webp' => 'Atlet putra melayang di atas bak pasir lompat jauh',
        'halaman-depan/carousel/05-selebrasi-finis.webp'   => 'Atlet putra merentangkan tangan merayakan finis',
        'halaman-depan/carousel/06-lari-bendera.webp'      => 'Anak-anak berlari berkelok di antara tiang bendera',
        'halaman-depan/carousel/07-tolak-peluru.webp'      => 'Atlet putri melakukan tolak peluru',
        'halaman-depan/carousel/08-lempar-cakram.webp'     => 'Atlet putra bersiap melempar cakram',
        'halaman-depan/carousel/09-lari-cepat.webp'        => 'Atlet putra berlari kencang di depan layar stadion',
    ];

    /**
     * Sidik SHA-1 sebelas foto bawaan lama (F9-03, dipakai sampai 26 November
     * 2026). Foto bawaan disalin ke unggahan tanpa diubah, jadi salinannya
     * di server lama punya sidik yang sama dan bisa diganti foto bawaan baru
     * oleh `gantiBawaanLama()` tanpa menyentuh foto unggahan admin (K191).
     *
     * @var list<string>
     */
    public const SIDIK_BAWAAN_LAMA = [
        '457139746d1d3d0cb5113264c1f50e6328eeea29', // 01-lari-cepat
        '34c96ff03d82d02a28301ccd471bf11178d2549c', // 02-lompat-jauh
        '42c17f42161363e5e52b88487b785c6a5039e0a4', // 03-lempar-lembing
        '6f99439bad5f5fac414c8e4d63e8ed1230a03eb2', // 04-podium-juara
        'dcc0d370900187aff3109ce620a6d80a102498d2', // 05-estafet
        '3ca77d2ed325d5761ad687532fec2b6e71aed0ef', // 06-lari-estafet-putri
        '7bd3d1036d17b1a3c1ab9453ee5a31975120a70e', // 07-lari-rintangan-bendera
        '9785eb01d403f0fc0b317a867135e76de7273861', // 08-lari-gawang-mini
        '350cf9331018aac195a9537ed22c215fba8ce93d', // 09-lempar-cakram
        'b0882e05bc75bfb9829c99e02a31ce043303334c', // 10-lari-jarak-menengah
        '578f8d4c52510d72e4b275c6ed2ea695c36dae43', // 11-estafet-lapangan
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
     * Ganti salinan foto bawaan lama (`SIDIK_BAWAAN_LAMA`) di daftar yang
     * sudah diatur dengan foto bawaan baru (K191). Foto baru menempati
     * tempat foto lama pertama; foto unggahan admin tetap di daftar dengan
     * urutan yang sama. Berkas foto lama dan salinan kecilnya dihapus.
     * Foto baru yang tidak muat karena `BATAS` dilewati.
     *
     * @param list<string> $sidikLama sidik foto lama (untuk uji); bawaan `SIDIK_BAWAAN_LAMA`
     *
     * @return array{dipasang: int, dihapus: int, gagal: int}|null `null` bila tidak ada foto bawaan lama
     */
    public static function gantiBawaanLama(?Unggahan $unggahan = null, array $sidikLama = self::SIDIK_BAWAAN_LAMA): ?array
    {
        $unggahan ??= new Unggahan();
        $sisa       = [];
        $lama       = [];
        $posisi     = null;

        foreach (self::tersimpan() ?? [] as $butir) {
            if (self::bawaanLama($butir['berkas'], $unggahan, $sidikLama)) {
                $lama[] = $butir['berkas'];
                $posisi ??= count($sisa);

                continue;
            }

            $sisa[] = $butir;
        }

        if ($lama === []) {
            return null;
        }

        $baru  = [];
        $gagal = 0;

        foreach (array_slice(self::BAWAAN, 0, max(0, self::BATAS - count($sisa)), true) as $berkas => $keterangan) {
            try {
                $baru[] = ['berkas' => self::salinKeUnggahan(ROOTPATH . 'data/' . $berkas), 'keterangan' => $keterangan];
            } catch (RuntimeException) {
                $gagal++;
            }
        }

        // Bila semua foto baru gagal disalin, foto lama dibiarkan supaya perintah bisa diulang.
        if ($baru === [] && $gagal > 0) {
            return ['dipasang' => 0, 'dihapus' => 0, 'gagal' => $gagal];
        }

        array_splice($sisa, (int) $posisi, 0, $baru);
        self::simpan($sisa);

        foreach ($lama as $berkas) {
            VarianFoto::hapus($berkas, $unggahan);
            $unggahan->hapus($berkas);
        }

        return ['dipasang' => count($baru), 'dihapus' => count($lama), 'gagal' => $gagal];
    }

    /**
     * Banyak salinan foto bawaan lama yang masih ada di daftar carousel.
     *
     * @param list<string> $sidikLama sidik foto lama (untuk uji); bawaan `SIDIK_BAWAAN_LAMA`
     */
    public static function jumlahBawaanLama(?Unggahan $unggahan = null, array $sidikLama = self::SIDIK_BAWAAN_LAMA): int
    {
        $unggahan ??= new Unggahan();

        return count(array_filter(self::tersimpan() ?? [], static fn (array $butir): bool => self::bawaanLama($butir['berkas'], $unggahan, $sidikLama)));
    }

    /**
     * `true` bila berkas unggahan itu salinan salah satu foto bawaan lama.
     *
     * @param list<string> $sidikLama
     */
    private static function bawaanLama(string $berkas, Unggahan $unggahan, array $sidikLama): bool
    {
        $penuh = $unggahan->jalurPenuh($berkas);

        return $penuh !== null && is_file($penuh) && in_array(sha1_file($penuh), $sidikLama, true);
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
