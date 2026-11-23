<?php

namespace App\Commands;

use App\Libraries\Bantuan\FaqBawaan;
use App\Libraries\NomorWa;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Config\Services;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * `php spark data:sesuaikan` (F10-01)
 *
 * Menyamakan data bawaan server yang diisi `data:awal` versi lama dengan data
 * bawaan terbaru (permintaan Tahap 10):
 *
 * - kelompok umur `KU 8 (Seneng Playon)` menjadi `KU 8`, supaya backup
 *   pendaftaran yang memakai nama `KU 8` bisa diimpor (`pendaftaran:impor`);
 *   dilewati bila `KU 8` sudah ada;
 * - `Pembayaran.nomor_wa` dan `HalamanDepan.nomor_wa_tanya` yang masih nomor
 *   contoh `628123456789` menjadi `6288215277227`;
 * - media sosial bawaan lama (`milklife.athletics`, `@milklife.athletics`,
 *   `@milklifeathletics`) menjadi alamat resmi MilkLife di `DataAwal::TAUTAN`;
 * - kalimat bawaan lama "Juara umum dihitung per jenjang sekolah." di
 *   tanya-jawab dihapus, karena klasemen kini per kelompok umur dan
 *   `{cara_juara_umum}` sudah menyebut susunannya (F11-02, K172 butir 2);
 * - jawaban bawaan lama "Bolehkah mengganti atau menambah atlet …" yang belum
 *   diubah admin diganti jawaban baru tentang ganti atlet oleh panitia
 *   (F11-01, F11-08);
 * - tanya-jawab bawaan Tahap 11 (nomor acara, kode hasil, waktu tangan,
 *   tidak dilombakan, rekor) ditambahkan **sekali** bila pertanyaannya belum
 *   ada; penanda `Bantuan.faqTahap11` mencegahnya ditambah lagi setelah
 *   dihapus admin. Tabel `faq` yang masih kosong dilewati, karena `faq:awal`
 *   memasang semuanya (F11-08).
 *
 * Setiap langkah hanya mengubah nilai bawaan lama; isian yang sudah diubah
 * admin dan data pendaftaran tidak disentuh, jadi aman dijalankan berkali-kali.
 * Perubahan basis data ditulis dalam satu transaksi.
 */
class SesuaikanData extends BaseCommand
{
    protected $group       = 'Aplikasi';
    protected $name        = 'data:sesuaikan';
    protected $description = 'Menyamakan data bawaan lama (nama KU 8, nomor WA contoh, media sosial lama, tanya-jawab juara umum dan ganti atlet, tanya-jawab baru Tahap 11) dengan data bawaan terbaru. Isian admin tidak ditimpa; aman dijalankan lagi.';
    protected $usage       = 'data:sesuaikan';

    /**
     * Nama kelompok umur KU 8 lama => baru.
     */
    public const NAMA_KU_LAMA = 'KU 8 (Seneng Playon)';

    public const NAMA_KU_BARU = 'KU 8';

    /**
     * Nomor WA contoh `data:awal` lama (bentuk baku).
     */
    public const WA_LAMA = '628123456789';

    /**
     * Kunci pengaturan nomor WA => nama untuk ringkasan.
     */
    public const KUNCI_WA = [
        'Pembayaran.nomor_wa'         => 'Nomor WA konfirmasi pembayaran (Pengaturan > Pembayaran)',
        'HalamanDepan.nomor_wa_tanya' => 'Nomor WA tanya admin (Pengaturan > Halaman depan)',
    ];

    /**
     * Alamat media sosial bawaan lama => [label, alamat baru].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const MEDIA_SOSIAL_LAMA = [
        'https://www.instagram.com/milklife.athletics' => ['Instagram', 'https://www.instagram.com/milklife.id'],
        'https://www.tiktok.com/@milklife.athletics'   => ['TikTok', 'https://www.tiktok.com/@milklife_id'],
        'https://www.youtube.com/@milklifeathletics'   => ['YouTube', 'https://www.youtube.com/@milklife_id'],
    ];

    /**
     * Kalimat tanya-jawab bawaan lama (klasemen per jenjang) => pengganti.
     */
    public const FAQ_JUARA_UMUM_LAMA = 'Juara umum dihitung **per jenjang sekolah**. {cara_juara_umum}';

    public const FAQ_JUARA_UMUM_BARU = '{cara_juara_umum}';

    /**
     * Pertanyaan dan jawaban bawaan lama tentang ganti atlet (sebelum F11-01).
     * Jawaban diganti hanya bila isinya masih persis sama.
     */
    public const FAQ_GANTI_ATLET_PERTANYAAN = 'Bolehkah mengganti atau menambah atlet setelah terdaftar di nomor lomba?';

    public const FAQ_GANTI_ATLET_LAMA = 'Menurut petunjuk teknis, atlet yang sudah terdaftar di nomor lomba **tidak dapat ditambah atau digantikan**, kecuali dicoret. Pastikan data atlet dan nomor lombanya benar sebelum pendaftaran ditutup.';

    /**
     * Pertanyaan bawaan baru Tahap 11 yang ditambahkan sekali ke server lama.
     */
    public const FAQ_TAHAP_11 = [
        'Apa itu nomor acara, misalnya Acara 101?',
        'Apa arti DNS, DNF, DQ, NM, r, dan CR di hasil lomba?',
        'Mengapa waktu di beberapa nomor hanya satu angka di belakang koma?',
        'Apa artinya nomor lomba bertanda Tidak dilombakan?',
        'Apa itu rekor kejuaraan dan di mana melihatnya?',
    ];

    /**
     * @param array<int|string, string|null> $params
     */
    public function run(array $params): int
    {
        try {
            $hasil = self::sesuaikan(db_connect());
        } catch (Throwable $e) {
            CLI::error(
                'Data tidak disesuaikan, dan tidak ada yang berubah. Pastikan MySQL menyala dan tabel sudah dibuat '
                . 'dengan "php spark migrate --all", lalu jalankan perintah ini lagi.',
            );
            CLI::error('Pesan dari basis data: ' . $e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Diubah:');

        foreach ($hasil['diubah'] === [] ? ['tidak ada; data sudah sesuai.'] : $hasil['diubah'] as $baris) {
            CLI::write('  - ' . $baris, $hasil['diubah'] === [] ? null : 'green');
        }

        CLI::write('Dilewati:');

        foreach ($hasil['dilewati'] === [] ? ['tidak ada.'] : $hasil['dilewati'] as $baris) {
            CLI::write('  - ' . $baris);
        }

        CLI::write('Data pendaftaran tidak diubah.');

        return EXIT_SUCCESS;
    }

    /**
     * Jalankan semua langkah dalam satu transaksi. Dengan `$ubah` false tidak
     * ada yang ditulis; `diubah` lalu berisi apa yang akan diubah (dipakai
     * `pembaruan:periksa`).
     *
     * @return array{diubah: list<string>, dilewati: list<string>}
     *
     * @throws Throwable Bila gagal; tidak ada yang berubah.
     */
    public static function sesuaikan(BaseConnection $db, bool $ubah = true): array
    {
        $hasil = ['diubah' => [], 'dilewati' => []];
        $waktu = Time::now()->format('Y-m-d H:i:s');

        if (! $ubah) {
            self::namaKelompokUmur($db, $waktu, $hasil, false);
            self::nomorWa($hasil, false);
            self::mediaSosial($db, $waktu, $hasil, false);
            self::faqJuaraUmum($db, $waktu, $hasil, false);
            self::faqGantiAtlet($db, $waktu, $hasil, false);
            self::faqTahap11($db, $waktu, $hasil, false);

            return $hasil;
        }

        $db->transBegin();

        try {
            self::namaKelompokUmur($db, $waktu, $hasil);
            self::nomorWa($hasil);
            self::mediaSosial($db, $waktu, $hasil);
            self::faqJuaraUmum($db, $waktu, $hasil);
            self::faqGantiAtlet($db, $waktu, $hasil);
            self::faqTahap11($db, $waktu, $hasil);

            $db->transCommit();
        } catch (Throwable $e) {
            $db->transRollback();

            // Nilai pengaturan yang sempat ditulis di memori ikut dibuang.
            Services::resetSingle('settings');

            throw $e;
        }

        return $hasil;
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function namaKelompokUmur(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        if ($db->table('kelompok_umur')->where('nama', self::NAMA_KU_BARU)->countAllResults() > 0) {
            $hasil['dilewati'][] = 'Nama kelompok umur: ' . self::NAMA_KU_BARU . ' sudah ada.';

            return;
        }

        $lama = $db->table('kelompok_umur')->select('id')->where('nama', self::NAMA_KU_LAMA)->orderBy('id')->get()->getRowArray();

        if ($lama === null) {
            $hasil['dilewati'][] = 'Nama kelompok umur: tidak ada ' . self::NAMA_KU_LAMA . ' (nama sudah diubah admin).';

            return;
        }

        if ($ubah) {
            $db->table('kelompok_umur')->where('id', (int) $lama['id'])->update(['nama' => self::NAMA_KU_BARU, 'diubah_pada' => $waktu]);
        }

        $hasil['diubah'][] = 'Nama kelompok umur: ' . self::NAMA_KU_LAMA . ' menjadi ' . self::NAMA_KU_BARU . '.';
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function nomorWa(array &$hasil, bool $ubah = true): void
    {
        foreach (self::KUNCI_WA as $kunci => $nama) {
            if (NomorWa::normalkan(trim((string) setting($kunci))) !== self::WA_LAMA) {
                $hasil['dilewati'][] = $nama . ': bukan nomor contoh lama.';

                continue;
            }

            $baru = DataAwal::PENGATURAN_TAMBAHAN[$kunci];

            if ($ubah) {
                setting($kunci, $baru);
            }

            $hasil['diubah'][] = $nama . ': nomor contoh menjadi ' . NomorWa::tampilan($baru) . '.';
        }
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function mediaSosial(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        $lama = array_change_key_case(self::MEDIA_SOSIAL_LAMA);
        $ada  = false;

        foreach ($db->table('tautan')->select('id, nilai')->where('jenis', 'media_sosial')->orderBy('id')->get()->getResultArray() as $baris) {
            $kunci = strtolower(rtrim(trim((string) $baris['nilai']), '/'));

            if (! isset($lama[$kunci])) {
                continue;
            }

            [$label, $alamat] = $lama[$kunci];
            if ($ubah) {
                $db->table('tautan')->where('id', (int) $baris['id'])->update(['nilai' => $alamat, 'diubah_pada' => $waktu]);
            }

            $hasil['diubah'][] = 'Media sosial ' . $label . ': ' . $baris['nilai'] . ' menjadi ' . $alamat . '.';
            $ada               = true;
        }

        if (! $ada) {
            $hasil['dilewati'][] = 'Media sosial: tidak ada alamat bawaan lama.';
        }
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function faqJuaraUmum(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        $baris = $db->table('faq')->select('id, jawaban')->like('jawaban', self::FAQ_JUARA_UMUM_LAMA, 'both', null, true)->orderBy('id')->get()->getResultArray();

        if ($baris === []) {
            $hasil['dilewati'][] = 'Tanya-jawab juara umum: tidak ada kalimat bawaan lama "per jenjang sekolah".';

            return;
        }

        foreach ($baris as $b) {
            if ($ubah) {
                $db->table('faq')->where('id', (int) $b['id'])->update([
                    'jawaban'     => str_replace(self::FAQ_JUARA_UMUM_LAMA, self::FAQ_JUARA_UMUM_BARU, (string) $b['jawaban']),
                    'diubah_pada' => $waktu,
                ]);
            }
        }

        $hasil['diubah'][] = 'Tanya-jawab juara umum: kalimat "per jenjang sekolah" dihapus dari ' . count($baris) . ' jawaban; susunan klasemen per kelompok umur kini disebut otomatis.';
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function faqGantiAtlet(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        $baru  = null;
        $ganti = 0;

        foreach (FaqBawaan::baris($waktu) as $b) {
            if ($b['pertanyaan'] === self::FAQ_GANTI_ATLET_PERTANYAAN) {
                $baru = $b['jawaban'];
            }
        }

        foreach ($db->table('faq')->select('id, jawaban')->orderBy('id')->get()->getResultArray() as $b) {
            if (str_replace("\r\n", "\n", trim((string) $b['jawaban'])) !== self::FAQ_GANTI_ATLET_LAMA) {
                continue;
            }

            if ($ubah && $baru !== null) {
                $db->table('faq')->where('id', (int) $b['id'])->update(['jawaban' => $baru, 'diubah_pada' => $waktu]);
            }

            $ganti++;
        }

        if ($ganti === 0) {
            $hasil['dilewati'][] = 'Tanya-jawab ganti atlet: tidak ada jawaban bawaan lama.';

            return;
        }

        $hasil['diubah'][] = 'Tanya-jawab ganti atlet: jawaban bawaan lama diganti jawaban tentang ganti atlet oleh panitia (' . $ganti . ' jawaban).';
    }

    /**
     * @param array{diubah: list<string>, dilewati: list<string>} $hasil
     */
    private static function faqTahap11(BaseConnection $db, string $waktu, array &$hasil, bool $ubah = true): void
    {
        if ((bool) setting('Bantuan.faqTahap11')) {
            $hasil['dilewati'][] = 'Tanya-jawab baru Tahap 11: sudah pernah ditambahkan.';

            return;
        }

        if ($db->table('faq')->countAllResults() === 0) {
            $hasil['dilewati'][] = 'Tanya-jawab baru Tahap 11: tanya-jawab belum dipasang; jalankan "php spark faq:awal".';

            return;
        }

        $ada = array_column($db->table('faq')->select('pertanyaan')->get()->getResultArray(), 'pertanyaan');
        $ada = array_map(static fn ($p): string => mb_strtolower(trim((string) $p)), $ada);

        $urutan = [];

        foreach ($db->table('faq')->select('kategori, MAX(urutan) AS urutan')->groupBy('kategori')->get()->getResultArray() as $b) {
            $urutan[(string) $b['kategori']] = (int) $b['urutan'];
        }

        $tambah = [];

        foreach (FaqBawaan::baris($waktu) as $b) {
            if (! in_array($b['pertanyaan'], self::FAQ_TAHAP_11, true) || in_array(mb_strtolower($b['pertanyaan']), $ada, true)) {
                continue;
            }

            $urutan[$b['kategori']] = ($urutan[$b['kategori']] ?? 0) + 1;
            $b['urutan']            = $urutan[$b['kategori']];
            $tambah[]               = $b;
        }

        if ($ubah) {
            if ($tambah !== [] && $db->table('faq')->insertBatch($tambah) !== count($tambah)) {
                throw new RuntimeException('Tanya-jawab baru Tahap 11 gagal disimpan.');
            }

            setting('Bantuan.faqTahap11', true);
        }

        if ($tambah === []) {
            $hasil['dilewati'][] = 'Tanya-jawab baru Tahap 11: semua pertanyaannya sudah ada.';

            return;
        }

        $hasil['diubah'][] = 'Tanya-jawab baru Tahap 11 ditambahkan (' . count($tambah) . '): ' . implode(' ', array_column($tambah, 'pertanyaan'));
    }
}
