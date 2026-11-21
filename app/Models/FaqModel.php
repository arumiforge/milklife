<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;
use Throwable;

/**
 * Tanya-jawab halaman Bantuan (tabel `faq`, F9-04).
 *
 * - `kategori`: kunci di `KATEGORI`.
 * - `pertanyaan`: teks biasa; `jawaban`: Markdown sederhana dengan isian
 *   otomatis (`App\Libraries\Bantuan\JawabanFaq`).
 * - Urutan tampil per kategori: `urutan` kecil lebih dulu, lalu yang dibuat lebih dulu.
 * - `aktif` 0: disembunyikan dari halaman Bantuan dan laci bantuan wizard,
 *   tetap tampil di panel admin.
 *
 * Isian diperiksa `periksa()`; pengendali admin
 * `App\Controllers\Admin\Pengaturan\FaqController`.
 */
class FaqModel extends Model
{
    /**
     * Kategori berurutan seperti tampil di halaman Bantuan.
     *
     * - `label`: judul kategori.
     * - `ikon`: Bootstrap Icons tanpa `bi-` (panel admin dan laci wizard).
     * - `gambar`: nama berkas ilustrasi G9-07 di `public/aset/gambar/bantuan/`
     *   (ditampilkan hanya bila berkasnya ada).
     * - `keterangan`: satu kalimat untuk kartu kategori.
     *
     * @var array<string, array{label: string, ikon: string, gambar: string, keterangan: string}>
     */
    public const KATEGORI = [
        'sebelum' => [
            'label'      => 'Sebelum mendaftar',
            'ikon'       => 'clipboard-check',
            'gambar'     => 'sebelum-mendaftar.webp',
            'keterangan' => 'Siapa yang boleh ikut, kelompok umur, dan apa yang perlu disiapkan.',
        ],
        'mengisi' => [
            'label'      => 'Mengisi pendaftaran',
            'ikon'       => 'phone',
            'gambar'     => 'mengisi-pendaftaran.webp',
            'keterangan' => 'Langkah demi langkah mengisi data sekolah, ofisial, dan atlet.',
        ],
        'pembayaran' => [
            'label'      => 'Pembayaran dan bukti',
            'ikon'       => 'bank',
            'gambar'     => 'pembayaran.webp',
            'keterangan' => 'Biaya, transfer, dan cara mengirim bukti transfer.',
        ],
        'cek' => [
            'label'      => 'Cek pendaftaran dan bukti PDF',
            'ikon'       => 'search',
            'gambar'     => 'cek-bukti.webp',
            'keterangan' => 'Melihat status, mencetak bukti pendaftaran, dan memperbaiki data.',
        ],
        'hari_lomba' => [
            'label'      => 'Hari lomba',
            'ikon'       => 'flag',
            'gambar'     => 'hari-lomba.webp',
            'keterangan' => 'Pertemuan teknik, nomor atlet (BIB), daftar ulang, dan aturan di lapangan.',
        ],
        'hasil' => [
            'label'      => 'Hasil dan juara',
            'ikon'       => 'trophy',
            'gambar'     => 'hasil-juara.webp',
            'keterangan' => 'Kapan hasil terbit, juara, dan juara umum.',
        ],
    ];

    /**
     * Kategori yang dibuka laci bantuan tiap langkah wizard pendaftaran.
     *
     * @var array<int, list<string>>
     */
    public const KATEGORI_LANGKAH = [
        1 => ['mengisi', 'sebelum'],
        2 => ['mengisi'],
        3 => ['mengisi', 'sebelum'],
        4 => ['pembayaran'],
        5 => ['pembayaran'],
    ];

    /**
     * Urutan terbesar yang bisa diketik admin.
     */
    public const URUTAN_MAKSIMAL = 9999;

    /**
     * Panjang terpanjang tiap kolom teks (huruf). `jawaban` dibatasi aplikasi,
     * kolomnya TEXT.
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'pertanyaan' => 255,
        'jawaban'    => 5000,
    ];

    protected $table            = 'faq';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['kategori', 'pertanyaan', 'jawaban', 'urutan', 'aktif'];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'dibuat_pada';
    protected $updatedField  = 'diubah_pada';
    protected $deletedField  = '';

    /**
     * Apakah kunci kategori dikenal.
     */
    public static function kategoriSah(mixed $kategori): bool
    {
        return is_string($kategori) && array_key_exists($kategori, self::KATEGORI);
    }

    /**
     * Label kategori, atau kunci itu sendiri bila tidak dikenal.
     */
    public static function labelKategori(string $kategori): string
    {
        return self::KATEGORI[$kategori]['label'] ?? $kategori;
    }

    /**
     * Semua tanya-jawab (aktif dan tidak) dikelompokkan per kategori menurut
     * urutan `KATEGORI`. Kategori tanpa isi tetap ada sebagai daftar kosong.
     * Baris dengan kategori yang tidak dikenal dikumpulkan di kunci aslinya,
     * di akhir.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function semuaPerKategori(bool $hanyaAktif = false): array
    {
        $kueri = $this->orderBy('urutan', 'ASC')->orderBy('id', 'ASC');

        if ($hanyaAktif) {
            $kueri->where('aktif', 1);
        }

        $hasil = array_fill_keys(array_keys(self::KATEGORI), []);

        foreach ($kueri->findAll() as $baris) {
            $hasil[(string) $baris['kategori']][] = $baris;
        }

        return $hasil;
    }

    /**
     * Tanya-jawab aktif satu atau beberapa kategori, berurutan per kategori.
     *
     * @param list<string> $kategori
     *
     * @return list<array<string, mixed>>
     */
    public function aktifUntuk(array $kategori): array
    {
        $kategori = array_values(array_filter($kategori, self::kategoriSah(...)));

        if ($kategori === []) {
            return [];
        }

        $baris = $this->where('aktif', 1)
            ->whereIn('kategori', $kategori)
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $posisi = array_flip($kategori);
        usort($baris, static fn (array $a, array $b): int => $posisi[$a['kategori']] <=> $posisi[$b['kategori']]);

        return $baris;
    }

    /**
     * Urutan untuk tanya-jawab baru supaya tampil paling akhir di kategorinya:
     * urutan terbesar ditambah satu (paling besar `URUTAN_MAKSIMAL`), atau 1
     * bila kategori itu masih kosong.
     *
     * @param int|null $kecuali `id` yang tidak ikut dihitung (yang sedang diubah).
     */
    public function urutanBerikutnya(string $kategori, ?int $kecuali = null): int
    {
        $pembangun = $this->builder()->selectMax('urutan', 'terbesar')->where('kategori', $kategori);

        if ($kecuali !== null) {
            $pembangun->where('id !=', $kecuali);
        }

        $baris = $pembangun->get()->getRowArray();

        return min(self::URUTAN_MAKSIMAL, (int) ($baris['terbesar'] ?? 0) + 1);
    }

    /**
     * Tukar posisi satu tanya-jawab dengan tetangganya di kategori yang sama
     * (`naik` = ke atas, `turun` = ke bawah). Urutan kategori itu ditulis ulang
     * menjadi 1, 2, 3, … supaya angka kembar tidak membuat tombol diam.
     *
     * @return bool `false` bila sudah paling atas/bawah atau tidak ditemukan.
     *
     * @throws RuntimeException Bila penyimpanan gagal; tidak ada yang berubah.
     */
    public function pindah(int $id, string $arah): bool
    {
        $faq = $this->find($id);

        if (! is_array($faq) || ! in_array($arah, ['naik', 'turun'], true)) {
            return false;
        }

        $daftar = array_map(
            'intval',
            array_column(
                $this->select('id')->where('kategori', $faq['kategori'])->orderBy('urutan', 'ASC')->orderBy('id', 'ASC')->findAll(),
                'id',
            ),
        );
        $posisi = array_search($id, $daftar, true);
        $tujuan = $arah === 'naik' ? $posisi - 1 : $posisi + 1;

        if ($posisi === false || ! isset($daftar[$tujuan])) {
            return false;
        }

        [$daftar[$posisi], $daftar[$tujuan]] = [$daftar[$tujuan], $daftar[$posisi]];

        // transBegin/transCommit, bukan transStart/transStatus: dalam mode ketat
        // CodeIgniter tidak mengembalikan status gagal dari transaksi sebelumnya
        // di koneksi yang sama, sehingga transComplete() ikut membatalkan.
        $this->db->transBegin();

        try {
            foreach ($daftar as $indeks => $idBaris) {
                if (! $this->builder()->where('id', $idBaris)->update(['urutan' => min(self::URUTAN_MAKSIMAL, $indeks + 1)])) {
                    throw new RuntimeException('Urutan tanya-jawab gagal disimpan.');
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
     * Periksa isian formulir. Hasilnya data siap simpan dan pesan galat per
     * kotak (kosong bila semua benar).
     *
     * @param array<string, string> $isian kategori, pertanyaan, jawaban, urutan, aktif (`1` atau kosong)
     * @param int|null              $id    Yang sedang diubah, `null` untuk yang baru.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public function periksa(array $isian, ?int $id = null): array
    {
        $galat    = [];
        $kategori = $isian['kategori'] ?? '';

        if (! self::kategoriSah($kategori)) {
            $galat['kategori'] = 'Kategori belum dipilih. Pilih salah satu kategori dari daftar.';
        }

        $pertanyaan = self::rapikanBaris($isian['pertanyaan'] ?? '');

        if ($pertanyaan === '') {
            $galat['pertanyaan'] = 'Pertanyaan belum diisi. Tulis pertanyaan seperti yang biasa ditanyakan sekolah, misalnya "Apakah harus punya akun?".';
        } elseif (mb_strlen($pertanyaan) > self::PANJANG['pertanyaan']) {
            $galat['pertanyaan'] = 'Pertanyaan terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG['pertanyaan'] . ' huruf.';
        }

        $jawaban = trim(str_replace("\r\n", "\n", $isian['jawaban'] ?? ''));

        if ($jawaban === '') {
            $galat['jawaban'] = 'Jawaban belum diisi. Tulis jawaban singkat yang menyebut langkah yang perlu dilakukan.';
        } elseif (mb_strlen($jawaban) > self::PANJANG['jawaban']) {
            $galat['jawaban'] = 'Jawaban terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG['jawaban'] . ' huruf, atau pecah menjadi beberapa pertanyaan.';
        }

        $urutan  = null;
        $isianUr = trim($isian['urutan'] ?? '');

        if ($isianUr === '') {
            $urutan = self::kategoriSah($kategori) ? $this->urutanBerikutnya($kategori, $id) : 1;
        } elseif (preg_match('/^\d{1,4}$/', $isianUr) === 1) {
            $urutan = (int) $isianUr;
        } else {
            $galat['urutan'] = 'Urutan belum benar. Tulis angka 0 sampai ' . self::URUTAN_MAKSIMAL . '; angka kecil tampil lebih dulu.';
        }

        return [[
            'kategori'   => $kategori,
            'pertanyaan' => $pertanyaan,
            'jawaban'    => $jawaban,
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
