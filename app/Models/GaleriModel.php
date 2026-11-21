<?php

namespace App\Models;

use App\Libraries\Unggahan;
use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Album galeri (tabel `galeri`, F9-06, K140 butir 11): sampul dan tautan ke
 * album Instagram atau Google Drive panitia.
 *
 * - `tautan`: hanya `https://`; `NULL` = belum diisi. Album tanpa tautan
 *   tidak tampil di `/galeri` walaupun aktif.
 * - `sampul`: jalur `unggahan/galeri/<acak>.webp`, atau `NULL`.
 * - Urutan tampil: `urutan` kecil lebih dulu, lalu yang dibuat lebih dulu.
 * - `aktif` 0: disembunyikan dari `/galeri`, tetap tampil di panel admin.
 *
 * Isian diperiksa `periksa()`; pengendali admin
 * `App\Controllers\Admin\Pengaturan\GaleriController`.
 */
class GaleriModel extends Model
{
    /**
     * Folder unggahan sampul (`App\Libraries\Unggahan::FOLDER`).
     */
    public const FOLDER = 'galeri';

    /**
     * Sisi terpanjang sampul dalam piksel (kartu galeri paling lebar sekitar 600 piksel, layar rapat 2×).
     */
    public const SISI_SAMPUL = 1200;

    /**
     * Urutan terbesar yang bisa diketik admin.
     */
    public const URUTAN_MAKSIMAL = 9999;

    /**
     * Album paling banyak.
     */
    public const BATAS = 60;

    /**
     * Panjang terpanjang tiap kolom teks (huruf).
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'judul'      => 150,
        'keterangan' => 300,
        'tautan'     => 500,
    ];

    protected $table            = 'galeri';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['judul', 'keterangan', 'tautan', 'sampul', 'urutan', 'aktif'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Apakah tautan boleh dipakai: alamat `https://` lengkap dengan nama situs,
     * tanpa spasi dan tanpa nama pengguna/sandi di dalamnya.
     */
    public static function tautanSah(mixed $tautan): bool
    {
        if (! is_string($tautan) || mb_strlen($tautan) > self::PANJANG['tautan'] || preg_match('/\s/u', $tautan) === 1) {
            return false;
        }

        $bagian = parse_url($tautan);

        return is_array($bagian)
            && strtolower($bagian['scheme'] ?? '') === 'https'
            && preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?(\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)+$/i', $bagian['host'] ?? '') === 1
            && ! isset($bagian['user'])
            && ! isset($bagian['pass'])
            && str_starts_with(strtolower($tautan), 'https://');
    }

    /**
     * Nama tempat album untuk label tombol: `Instagram`, `Google Drive`, atau `''`.
     */
    public static function tempat(?string $tautan): string
    {
        $host = strtolower((string) parse_url((string) $tautan, PHP_URL_HOST));

        return match (true) {
            $host === 'instagram.com' || str_ends_with($host, '.instagram.com') || $host === 'instagr.am' => 'Instagram',
            $host === 'drive.google.com' || $host === 'photos.google.com' || $host === 'photos.app.goo.gl' => 'Google Drive',
            default => '',
        };
    }

    /**
     * Semua album (aktif dan tidak) menurut urutan tampil.
     *
     * @return list<array<string, mixed>>
     */
    public function semua(): array
    {
        return $this->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Album yang tampil di `/galeri`: aktif dan bertautan sah, menurut urutan.
     * Sampul yang berkasnya hilang dikosongkan (kartu memakai latar pengganti).
     *
     * @return list<array{id: int, judul: string, keterangan: string, tautan: string, sampul: string|null, tempat: string}>
     */
    public function publik(?Unggahan $unggahan = null): array
    {
        $unggahan ??= new Unggahan();
        $hasil = [];

        foreach ($this->where('aktif', 1)->where('tautan IS NOT NULL', null, false)->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll() as $baris) {
            if (! self::tautanSah($baris['tautan'])) {
                continue;
            }

            $sampul  = is_string($baris['sampul']) && $unggahan->ada($baris['sampul']) ? $baris['sampul'] : null;
            $hasil[] = [
                'id'         => (int) $baris['id'],
                'judul'      => (string) $baris['judul'],
                'keterangan' => (string) $baris['keterangan'],
                'tautan'     => (string) $baris['tautan'],
                'sampul'     => $sampul,
                'tempat'     => self::tempat($baris['tautan']),
            ];
        }

        return $hasil;
    }

    /**
     * Urutan untuk album baru supaya tampil paling akhir: urutan terbesar
     * ditambah satu (paling besar `URUTAN_MAKSIMAL`), atau 1 bila masih kosong.
     *
     * @param int|null $kecuali `id` yang tidak ikut dihitung (yang sedang diubah).
     */
    public function urutanBerikutnya(?int $kecuali = null): int
    {
        $pembangun = $this->builder()->selectMax('urutan', 'terbesar');

        if ($kecuali !== null) {
            $pembangun->where('id !=', $kecuali);
        }

        $baris = $pembangun->get()->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    /**
     * Tukar posisi satu album dengan tetangganya (`naik` = ke atas, `turun` =
     * ke bawah). Urutan semua album ditulis ulang menjadi 1, 2, 3, … supaya
     * angka kembar tidak membuat tombol diam.
     *
     * @return bool `false` bila sudah paling atas/bawah atau tidak ditemukan.
     *
     * @throws RuntimeException Bila penyimpanan gagal; tidak ada yang berubah.
     */
    public function pindah(int $id, string $arah): bool
    {
        if (! in_array($arah, ['naik', 'turun'], true)) {
            return false;
        }

        $daftar = array_map('intval', array_column($this->select('id')->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll(), 'id'));
        $posisi = array_search($id, $daftar, true);

        if ($posisi === false) {
            return false;
        }

        $tujuan = $arah === 'naik' ? $posisi - 1 : $posisi + 1;

        if (! isset($daftar[$tujuan])) {
            return false;
        }

        [$daftar[$posisi], $daftar[$tujuan]] = [$daftar[$tujuan], $daftar[$posisi]];

        // transBegin/transCommit seperti FaqModel::pindah() (mode ketat CodeIgniter).
        $this->db->transBegin();

        try {
            foreach ($daftar as $indeks => $idBaris) {
                if (! $this->builder()->where('id', $idBaris)->update(['urutan' => min(self::URUTAN_MAKSIMAL, $indeks + 1)])) {
                    throw new RuntimeException('Urutan album gagal disimpan.');
                }
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        return true;
    }

    /**
     * Periksa isian formulir (tanpa sampul). Hasilnya data siap simpan dan
     * pesan galat per kotak (kosong bila semua benar).
     *
     * @param array<string, string> $isian judul, keterangan, tautan, urutan, aktif (`1` atau kosong)
     * @param int|null              $id    Yang sedang diubah, `null` untuk yang baru.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public function periksa(array $isian, ?int $id = null): array
    {
        $galat = [];
        $judul = self::rapikanBaris($isian['judul'] ?? '');

        if ($judul === '') {
            $galat['judul'] = 'Judul album belum diisi. Tulis nama singkat, misalnya "Seri 1 – 2025".';
        } elseif (mb_strlen($judul) > self::PANJANG['judul']) {
            $galat['judul'] = 'Judul album terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG['judul'] . ' huruf.';
        }

        $keterangan = self::rapikanBaris($isian['keterangan'] ?? '');

        if (mb_strlen($keterangan) > self::PANJANG['keterangan']) {
            $galat['keterangan'] = 'Keterangan terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG['keterangan'] . ' huruf.';
        }

        $tautan = trim($isian['tautan'] ?? '');

        if ($tautan !== '' && ! self::tautanSah($tautan)) {
            $galat['tautan'] = str_starts_with(strtolower($tautan), 'http://')
                ? 'Tautan harus diawali https:// (dengan huruf s). Buka albumnya di peramban, lalu salin alamat lengkapnya dari kotak alamat.'
                : 'Tautan belum benar. Salin alamat lengkap album dari kotak alamat peramban, diawali https://, misalnya https://www.instagram.com/… atau https://drive.google.com/….';
        }

        $urutan  = null;
        $isianUr = trim($isian['urutan'] ?? '');

        if ($isianUr === '') {
            $urutan = $this->urutanBerikutnya($id);
        } elseif (preg_match('/^\d{1,4}$/', $isianUr) === 1) {
            $urutan = (int) $isianUr;
        } else {
            $galat['urutan'] = 'Urutan belum benar. Tulis angka 0 sampai ' . self::URUTAN_MAKSIMAL . '; angka kecil tampil lebih dulu.';
        }

        return [[
            'judul'      => $judul,
            'keterangan' => $keterangan,
            'tautan'     => $tautan === '' ? null : $tautan,
            'urutan'     => $urutan,
            'aktif'      => ($isian['aktif'] ?? '') === '1' ? 1 : 0,
        ], $galat];
    }

    /**
     * Satu baris: spasi berlebih dan baris baru dirapikan menjadi satu spasi.
     */
    private static function rapikanBaris(string $teks): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }
}
