<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Nomor lomba per kelompok umur: individu atau beregu, putra, putri, atau mix.
 *
 * Isian formulir dirapikan dengan `rapikanIsian()`, diperiksa dengan
 * `periksaIsian()` (tanpa basis data, punya uji unit), lalu diubah ke bentuk
 * kolom dengan `untukDisimpan()`. Aturannya:
 *
 * - Nomor individu tidak boleh berkategori mix, dan kolom timnya selalu kosong.
 * - Nomor beregu yang disimpan lewat formulir wajib lengkap: jumlah inti,
 *   batas anggota (tidak kurang dari inti), dan biaya beregu. Nomor mix juga
 *   jumlah putra dan putri minimal, yang jumlahnya tidak lebih dari inti (K09).
 * - Nomor beregu dari `data:awal` boleh belum lengkap; `kekuranganTim()`
 *   menyebut apa yang belum diisi, untuk tanda "belum lengkap" di daftar.
 *
 * Nomor yang sudah dipakai atlet (`atlet_nomor`) atau tim
 * (`pendaftaran_beregu`) tidak bisa dihapus, dan kelompok umur, jenis,
 * kategori, serta isian timnya (selain biaya) tidak bisa diubah (`dipakai()`,
 * `TERKUNCI_BILA_DIPAKAI`).
 */
class NomorLombaModel extends Model
{
    /**
     * @var array<string, string> kode => label
     */
    public const JENIS = [
        'individu' => 'Individu',
        'beregu'   => 'Beregu',
    ];

    /**
     * @var array<string, string> kode => label
     */
    public const KATEGORI = [
        'putra' => 'Putra',
        'putri' => 'Putri',
        'mix'   => 'Mix',
    ];

    /**
     * Kolom yang hanya dipakai nomor beregu.
     *
     * @var list<string>
     */
    public const KOLOM_TIM = ['jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu'];

    /**
     * Isian yang terkunci begitu nomor dipakai atlet atau tim (F6-07), dengan
     * lanjutan pesan galatnya (`%s` = nilai lama). Nama, biaya beregu,
     * urutan, dan aktif tetap bisa diubah.
     *
     * @var array<string, string>
     */
    public const TERKUNCI_BILA_DIPAKAI = [
        'kelompok_umur_id' => 'kelompok umurnya tidak bisa dipindah. Buat nomor baru di kelompok umur lain bila perlu.',
        'jenis'            => 'jenisnya tidak bisa diubah. Buat nomor baru bila perlu.',
        'kategori'         => 'kategorinya tidak bisa diubah. Buat nomor baru bila perlu.',
        'jumlah_inti'      => 'jumlah anggota intinya tetap %s. Buat nomor baru bila aturan timnya berubah.',
        'batas_anggota'    => 'batas anggotanya tetap %s. Buat nomor baru bila aturan timnya berubah.',
        'jumlah_putra'     => 'jumlah putra minimalnya tetap %s. Buat nomor baru bila aturan timnya berubah.',
        'jumlah_putri'     => 'jumlah putri minimalnya tetap %s. Buat nomor baru bila aturan timnya berubah.',
    ];

    public const INTI_MINIMAL     = 2;
    public const ANGGOTA_MAKSIMAL = 50;
    public const URUTAN_MAKSIMAL  = 9999;
    public const NAMA_MAKSIMAL    = 100;

    protected $table            = 'nomor_lomba';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useAutoIncrement = true;
    protected $allowedFields    = [
        'kelompok_umur_id', 'nama', 'jenis', 'kategori',
        'jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu',
        'urutan', 'aktif',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';

    /**
     * Rapikan isian mentah formulir (semua nilai teks, untuk ditampilkan ulang
     * bila ada yang salah).
     *
     * - Spasi di tepi dibuang; spasi berderet di nama menjadi satu.
     * - Nomor individu: kolom tim dikosongkan.
     * - Nomor beregu putra/putri: jumlah putra dan putri dikosongkan.
     * - `aktif` menjadi `'1'` atau `'0'`.
     *
     * @param array<string, mixed> $mentah
     *
     * @return array{kelompok_umur_id: string, nama: string, jenis: string, kategori: string, jumlah_inti: string, batas_anggota: string, jumlah_putra: string, jumlah_putri: string, biaya_beregu: string, urutan: string, aktif: string}
     */
    public static function rapikanIsian(array $mentah): array
    {
        $teks = static fn (string $kunci): string => is_scalar($mentah[$kunci] ?? null) ? trim((string) $mentah[$kunci]) : '';

        $isian = [
            'kelompok_umur_id' => $teks('kelompok_umur_id'),
            'nama'             => (string) preg_replace('/\s+/u', ' ', $teks('nama')),
            'jenis'            => $teks('jenis'),
            'kategori'         => $teks('kategori'),
            'jumlah_inti'      => $teks('jumlah_inti'),
            'batas_anggota'    => $teks('batas_anggota'),
            'jumlah_putra'     => $teks('jumlah_putra'),
            'jumlah_putri'     => $teks('jumlah_putri'),
            'biaya_beregu'     => $teks('biaya_beregu'),
            'urutan'           => $teks('urutan'),
            'aktif'            => $teks('aktif') === '1' ? '1' : '0',
        ];

        if ($isian['jenis'] === 'individu') {
            foreach (self::KOLOM_TIM as $kolom) {
                $isian[$kolom] = '';
            }
        } elseif ($isian['jenis'] === 'beregu' && $isian['kategori'] !== 'mix') {
            $isian['jumlah_putra'] = '';
            $isian['jumlah_putri'] = '';
        }

        return $isian;
    }

    /**
     * Periksa isian yang sudah dirapikan, tanpa basis data.
     *
     * @param array<string, string> $isian           Hasil `rapikanIsian()`.
     * @param list<int|string>      $idKelompokUmur  ID kelompok umur yang ada.
     *
     * @return array<string, string> nama kotak => pesan yang menyebut cara memperbaikinya; kosong bila benar
     */
    public static function periksaIsian(array $isian, array $idKelompokUmur): array
    {
        $galat = [];

        $idKelompokUmur = array_map('strval', $idKelompokUmur);

        if ($isian['kelompok_umur_id'] === '') {
            $galat['kelompok_umur_id'] = 'Kelompok umur belum dipilih. Pilih kelompok umur untuk nomor ini.';
        } elseif (! in_array($isian['kelompok_umur_id'], $idKelompokUmur, true)) {
            $galat['kelompok_umur_id'] = 'Kelompok umur yang dipilih tidak ditemukan. Pilih lagi dari daftar.';
        }

        if ($isian['nama'] === '') {
            $galat['nama'] = 'Nama nomor belum diisi. Tulis nama nomornya tanpa putra/putri, misalnya Lari 60 m.';
        } elseif (mb_strlen($isian['nama']) > self::NAMA_MAKSIMAL) {
            $galat['nama'] = 'Nama nomor terlalu panjang. Persingkat menjadi paling banyak ' . self::NAMA_MAKSIMAL . ' huruf.';
        }

        if (! isset(self::JENIS[$isian['jenis']])) {
            $galat['jenis'] = 'Jenis nomor belum dipilih. Pilih individu atau beregu.';
        }

        if (! isset(self::KATEGORI[$isian['kategori']])) {
            $galat['kategori'] = 'Kategori belum dipilih. Pilih putra, putri, atau mix.';
        } elseif ($isian['jenis'] === 'individu' && $isian['kategori'] === 'mix') {
            $galat['kategori'] = 'Nomor individu tidak bisa berkategori mix. Pilih putra atau putri, atau ubah jenisnya menjadi beregu.';
        }

        if ($isian['jenis'] === 'beregu') {
            $galat += self::periksaTim($isian);
        }

        if ($isian['urutan'] !== '' && self::angka($isian['urutan'], 0, self::URUTAN_MAKSIMAL) === null) {
            $galat['urutan'] = 'Urutan harus angka bulat 0 sampai ' . self::URUTAN_MAKSIMAL . '. Angka kecil tampil lebih dulu; kosongkan untuk menaruhnya paling akhir.';
        }

        return $galat;
    }

    /**
     * Ubah isian yang sudah benar menjadi nilai kolom. Kolom yang kosong
     * menjadi `null`; `urutan` kosong juga `null` (pengendali yang mengisinya).
     *
     * @param array<string, string> $isian Hasil `rapikanIsian()` yang lolos `periksaIsian()`.
     *
     * @return array<string, int|string|null>
     */
    public static function untukDisimpan(array $isian): array
    {
        $angka = static fn (string $nilai): ?int => $nilai === '' ? null : (int) $nilai;

        return [
            'kelompok_umur_id' => (int) $isian['kelompok_umur_id'],
            'nama'             => $isian['nama'],
            'jenis'            => $isian['jenis'],
            'kategori'         => $isian['kategori'],
            'jumlah_inti'      => $angka($isian['jumlah_inti']),
            'batas_anggota'    => $angka($isian['batas_anggota']),
            'jumlah_putra'     => $angka($isian['jumlah_putra']),
            'jumlah_putri'     => $angka($isian['jumlah_putri']),
            'biaya_beregu'     => $isian['biaya_beregu'] === '' ? null : angka_rupiah($isian['biaya_beregu']),
            'urutan'           => $angka($isian['urutan']),
            'aktif'            => $isian['aktif'] === '1' ? 1 : 0,
        ];
    }

    /**
     * Isian formulir dari satu baris basis data (untuk halaman ubah).
     *
     * @param array<string, mixed> $baris
     *
     * @return array<string, string>
     */
    public static function isianDariBaris(array $baris): array
    {
        $isian = [];

        foreach (['kelompok_umur_id', 'nama', 'jenis', 'kategori', 'jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu', 'urutan'] as $kolom) {
            $isian[$kolom] = (string) ($baris[$kolom] ?? '');
        }

        $isian['aktif'] = (int) ($baris['aktif'] ?? 0) === 1 ? '1' : '0';

        return $isian;
    }

    /**
     * Isian tim yang belum diisi pada nomor beregu (data awal atau data lama).
     * Nomor individu selalu lengkap.
     *
     * @param array<string, mixed> $baris Baris `nomor_lomba`.
     *
     * @return list<string> label yang belum diisi, misalnya `['batas anggota', 'jumlah putra dan putri minimal']`
     */
    public static function kekuranganTim(array $baris): array
    {
        if (($baris['jenis'] ?? '') !== 'beregu') {
            return [];
        }

        $kosong = static fn (string $kolom): bool => ($baris[$kolom] ?? null) === null || $baris[$kolom] === '';

        $kurang = [];

        if ($kosong('jumlah_inti')) {
            $kurang[] = 'jumlah inti';
        }

        if ($kosong('batas_anggota')) {
            $kurang[] = 'batas anggota';
        }

        if ($kosong('biaya_beregu')) {
            $kurang[] = 'biaya beregu';
        }

        if (($baris['kategori'] ?? '') === 'mix' && ($kosong('jumlah_putra') || $kosong('jumlah_putri'))) {
            $kurang[] = 'jumlah putra dan putri minimal';
        }

        return $kurang;
    }

    /**
     * Nama lengkap untuk kalimat, misalnya "Lari 60 m putra" atau "Estafet 8 × 50 m mix".
     *
     * @param array<string, mixed> $baris
     */
    public static function namaLengkap(array $baris): string
    {
        return trim((string) ($baris['nama'] ?? '') . ' ' . (string) ($baris['kategori'] ?? ''));
    }

    /**
     * Semua nomor lomba beserta nama dan keadaan aktif kelompok umurnya,
     * berurutan seperti kelompok umurnya, lalu `urutan` dan `id`.
     *
     * @return list<array<string, mixed>>
     */
    public function daftarLengkap(): array
    {
        return $this->select('nomor_lomba.*, kelompok_umur.nama AS nama_kelompok_umur, kelompok_umur.aktif AS kelompok_umur_aktif')
            ->join('kelompok_umur', 'kelompok_umur.id = nomor_lomba.kelompok_umur_id')
            ->orderBy('kelompok_umur.urutan', 'ASC')
            ->orderBy('kelompok_umur.id', 'ASC')
            ->orderBy('nomor_lomba.urutan', 'ASC')
            ->orderBy('nomor_lomba.id', 'ASC')
            ->findAll();
    }

    /**
     * Sudah dipakai atlet atau tim beregu?
     */
    public function dipakai(int $id): bool
    {
        return $this->db->table('atlet_nomor')->where('nomor_lomba_id', $id)->countAllResults() > 0
            || $this->db->table('pendaftaran_beregu')->where('nomor_lomba_id', $id)->countAllResults() > 0;
    }

    /**
     * ID semua nomor lomba yang sudah dipakai atlet atau tim beregu.
     *
     * @return array<int, true> id => true
     */
    public function idDipakai(): array
    {
        $id = [];

        foreach (['atlet_nomor', 'pendaftaran_beregu'] as $tabel) {
            foreach ($this->db->table($tabel)->select('nomor_lomba_id')->distinct()->get()->getResultArray() as $baris) {
                $id[(int) $baris['nomor_lomba_id']] = true;
            }
        }

        return $id;
    }

    /**
     * Sudah ada nomor bernama sama (huruf besar-kecil diabaikan) dengan
     * kategori yang sama di kelompok umur itu?
     */
    public function adaKembar(int $kelompokUmurId, string $nama, string $kategori, ?int $kecualiId = null): bool
    {
        $kueri = $this->builder()
            ->where('kelompok_umur_id', $kelompokUmurId)
            ->where('kategori', $kategori)
            ->where('LOWER(nama)', mb_strtolower($nama));

        if ($kecualiId !== null) {
            $kueri->where('id !=', $kecualiId);
        }

        return $kueri->countAllResults() > 0;
    }

    /**
     * Urutan untuk menaruh nomor baru paling akhir di kelompok umurnya.
     */
    public function urutanBerikutnya(int $kelompokUmurId): int
    {
        $baris = $this->builder()
            ->selectMax('urutan', 'terbesar')
            ->where('kelompok_umur_id', $kelompokUmurId)
            ->get()
            ->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    /**
     * @param array<string, string> $isian
     *
     * @return array<string, string>
     */
    private static function periksaTim(array $isian): array
    {
        $galat = [];
        $batas = self::INTI_MINIMAL . ' sampai ' . self::ANGGOTA_MAKSIMAL;

        $inti = self::angka($isian['jumlah_inti'], self::INTI_MINIMAL, self::ANGGOTA_MAKSIMAL);

        if ($isian['jumlah_inti'] === '') {
            $galat['jumlah_inti'] = 'Jumlah anggota inti belum diisi. Tulis berapa orang dalam satu tim, misalnya 4.';
        } elseif ($inti === null) {
            $galat['jumlah_inti'] = "Jumlah anggota inti harus angka {$batas}, misalnya 4.";
        }

        $anggota = self::angka($isian['batas_anggota'], self::INTI_MINIMAL, self::ANGGOTA_MAKSIMAL);

        if ($isian['batas_anggota'] === '') {
            $galat['batas_anggota'] = 'Batas anggota belum diisi. Tulis jumlah anggota paling banyak satu tim termasuk cadangan; samakan dengan jumlah inti bila tanpa cadangan.';
        } elseif ($anggota === null) {
            $galat['batas_anggota'] = "Batas anggota harus angka {$batas}, misalnya 6.";
        } elseif ($inti !== null && $anggota < $inti) {
            $galat['batas_anggota'] = "Batas anggota ({$anggota}) kurang dari jumlah inti ({$inti}). Tulis {$inti} atau lebih.";
        }

        $biaya = angka_rupiah($isian['biaya_beregu']);

        if ($isian['biaya_beregu'] === '') {
            $galat['biaya_beregu'] = 'Biaya beregu belum diisi. Tulis biaya per tim dalam rupiah tanpa titik, misalnya 100000.';
        } elseif ($biaya === null || $biaya < 1) {
            $galat['biaya_beregu'] = 'Biaya beregu belum benar. Tulis biaya per tim lebih dari 0, angka rupiah tanpa koma, misalnya 100000.';
        }

        if ($isian['kategori'] === 'mix') {
            $jumlah = [];

            foreach (['jumlah_putra' => 'putra', 'jumlah_putri' => 'putri'] as $kolom => $label) {
                $jumlah[$kolom] = self::angka($isian[$kolom], 1, self::ANGGOTA_MAKSIMAL);

                if ($isian[$kolom] === '') {
                    $galat[$kolom] = "Jumlah {$label} minimal belum diisi. Tulis berapa {$label} paling sedikit dalam satu tim, misalnya 4.";
                } elseif ($jumlah[$kolom] === null) {
                    $galat[$kolom] = "Jumlah {$label} minimal harus angka 1 sampai " . self::ANGGOTA_MAKSIMAL . '.';
                }
            }

            if ($inti !== null && $jumlah['jumlah_putra'] !== null && $jumlah['jumlah_putri'] !== null
                && $jumlah['jumlah_putra'] + $jumlah['jumlah_putri'] > $inti) {
                $total                 = $jumlah['jumlah_putra'] + $jumlah['jumlah_putri'];
                $galat['jumlah_putra'] = "Jumlah putra dan putri minimal ({$jumlah['jumlah_putra']} + {$jumlah['jumlah_putri']} = {$total}) lebih dari jumlah inti ({$inti}). Kurangi salah satunya supaya jumlahnya paling banyak {$inti}.";
            }
        }

        return $galat;
    }

    /**
     * Angka bulat dalam rentang, atau `null` bila bukan.
     */
    private static function angka(string $nilai, int $minimal, int $maksimal): ?int
    {
        if (preg_match('/^\d{1,5}$/', $nilai) !== 1) {
            return null;
        }

        $angka = (int) $nilai;

        return $angka >= $minimal && $angka <= $maksimal ? $angka : null;
    }
}
