<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\NomorWa;
use App\Models\TautanModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan kontak: `/admin/pengaturan/kontak` (pola halaman daftar data,
 * rencana Tahap 2 "Rute"), untuk tabel `tautan`.
 *
 * Satu halaman daftar menampilkan dua bagian: narahubung (nama dan nomor WA)
 * dan media sosial (nama dan alamat web). Jenis kontak baru dipilih lewat
 * parameter kueri: `kontak/tambah?jenis=narahubung` atau
 * `kontak/tambah?jenis=media-sosial`; formulirnya membawa jenis itu dalam
 * kotak tersembunyi `jenis`. Jenis kontak yang sudah ada tidak bisa diubah
 * (hapus lalu tambah baru di bagian lainnya).
 */
class KontakController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/kontak';

    /**
     * Jenis di alamat dan formulir => jenis di basis data.
     *
     * @var array<string, string>
     */
    public const JENIS_ALAMAT = [
        'narahubung'   => TautanModel::NARAHUBUNG,
        'media-sosial' => TautanModel::MEDIA_SOSIAL,
    ];

    /**
     * Teks formulir dan pesan per jenis.
     *
     * @var array<string, array<string, string>>
     */
    public const TEKS = [
        TautanModel::NARAHUBUNG => [
            'nama'           => 'narahubung',
            'Nama'           => 'Narahubung',
            'label'          => 'Nama narahubung',
            'label_bantuan'  => 'Nama yang dihubungi sekolah, misalnya Bu Rina (panitia).',
            'label_kosong'   => 'Nama narahubung belum diisi. Tulis nama orang yang bisa dihubungi, misalnya Bu Rina (panitia).',
            'nilai'          => 'Nomor WA',
            'nilai_bantuan'  => 'Nomor ponsel yang dipakai untuk WA, misalnya 0812-3456-7890.',
            'nilai_kosong'   => 'Nomor WA belum diisi. Tulis nomor ponsel yang dipakai untuk WA, misalnya 0812-3456-7890.',
            'nilai_kembar'   => 'Nomor WA ini sudah ada di daftar narahubung atas nama %s. Ubah narahubung itu saja, atau tulis nomor lain.',
        ],
        TautanModel::MEDIA_SOSIAL => [
            'nama'           => 'media sosial',
            'Nama'           => 'Media sosial',
            'label'          => 'Nama media sosial',
            'label_bantuan'  => 'Nama yang tampil di halaman depan, misalnya Instagram @milklife.',
            'label_kosong'   => 'Nama media sosial belum diisi. Tulis nama yang tampil di halaman depan, misalnya Instagram @milklife.',
            'nilai'          => 'Alamat',
            'nilai_bantuan'  => 'Salin alamat lengkap dari peramban, misalnya https://www.instagram.com/milklife.',
            'nilai_kosong'   => 'Alamat belum diisi. Salin alamat lengkap dari peramban, misalnya https://www.instagram.com/milklife.',
            'nilai_kembar'   => 'Alamat ini sudah ada di daftar media sosial atas nama %s. Ubah media sosial itu saja, atau tulis alamat lain.',
        ],
    ];

    private TautanModel $tautan;

    public function __construct()
    {
        $this->tautan = model(TautanModel::class);
    }

    public function index(): string
    {
        return view('admin/pengaturan/kontak/index', [
            'narahubung'  => $this->tautan->daftarJenis(TautanModel::NARAHUBUNG),
            'mediaSosial' => $this->tautan->daftarJenis(TautanModel::MEDIA_SOSIAL),
        ]);
    }

    public function tambah(): ResponseInterface|string
    {
        $jenis = self::jenisDariAlamat($this->request->getGet('jenis'));

        if ($jenis === null) {
            return $this->jenisTidakDikenal();
        }

        return $this->tampilkanFormulir($jenis, null, [
            'label'  => '',
            'nilai'  => '',
            'urutan' => (string) $this->tautan->urutanBerikutnya($jenis),
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        $jenis = self::jenisDariAlamat($this->request->getPost('jenis'));

        if ($jenis === null) {
            return $this->jenisTidakDikenal();
        }

        $isian = $this->isianFormulir();
        [$data, $galat] = $this->periksa($jenis, $isian, null);

        if ($galat !== []) {
            return $this->tampilkanFormulir($jenis, null, $isian, $galat);
        }

        $this->tautan->insert($data + ['jenis' => $jenis]);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', self::TEKS[$jenis]['Nama'] . ' "' . $data['label'] . '" tersimpan.');
    }

    public function ubah(int $id): ResponseInterface|string
    {
        $kontak = $this->cari($id);

        if ($kontak === null) {
            return $this->tidakDitemukan();
        }

        return $this->tampilkanFormulir($kontak['jenis'], $kontak, [
            'label'  => (string) $kontak['label'],
            'nilai'  => $kontak['jenis'] === TautanModel::NARAHUBUNG ? NomorWa::tampilan($kontak['nilai']) : (string) $kontak['nilai'],
            'urutan' => (string) $kontak['urutan'],
        ]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $kontak = $this->cari($id);

        if ($kontak === null) {
            return $this->tidakDitemukan();
        }

        // Jenis tetap dari data tersimpan; kiriman `jenis` diabaikan.
        $jenis = $kontak['jenis'];
        $isian = $this->isianFormulir();
        [$data, $galat] = $this->periksa($jenis, $isian, $id);

        if ($galat !== []) {
            return $this->tampilkanFormulir($jenis, $kontak, $isian, $galat);
        }

        $this->tautan->update($id, $data);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Perubahan ' . self::TEKS[$jenis]['nama'] . ' "' . $data['label'] . '" tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $kontak = $this->cari($id);

        if ($kontak === null) {
            return $this->tidakDitemukan();
        }

        $this->tautan->delete($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', self::TEKS[$kontak['jenis']]['Nama'] . ' "' . $kontak['label'] . '" sudah dihapus.');
    }

    /**
     * Jenis basis data dari isian alamat atau formulir (`narahubung`,
     * `media-sosial`), atau `null` bila tidak dikenal.
     */
    public static function jenisDariAlamat(mixed $isian): ?string
    {
        return is_string($isian) ? (self::JENIS_ALAMAT[$isian] ?? null) : null;
    }

    /**
     * Jenis di alamat dan formulir untuk jenis basis data.
     */
    public static function jenisUntukAlamat(string $jenis): string
    {
        return (string) array_search($jenis, self::JENIS_ALAMAT, true);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function cari(int $id): ?array
    {
        $kontak = $this->tautan->find($id);

        return is_array($kontak) && TautanModel::jenisSah($kontak['jenis'] ?? null) ? $kontak : null;
    }

    /**
     * @return array<string, string>
     */
    private function isianFormulir(): array
    {
        $isian = [];

        foreach (['label', 'nilai', 'urutan'] as $kotak) {
            $nilai         = $this->request->getPost($kotak);
            $isian[$kotak] = is_string($nilai) ? trim($nilai) : '';
        }

        return $isian;
    }

    /**
     * Periksa isian. Hasilnya data siap simpan (tanpa `jenis`) dan pesan galat
     * per kotak (kosong bila semua benar).
     *
     * @param array<string, string> $isian
     * @param int|null              $id    Kontak yang sedang diubah, `null` untuk kontak baru.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function periksa(string $jenis, array $isian, ?int $id): array
    {
        $teks  = self::TEKS[$jenis];
        $galat = [];

        if ($isian['label'] === '') {
            $galat['label'] = $teks['label_kosong'];
        } elseif (mb_strlen($isian['label']) > TautanModel::PANJANG['label']) {
            $galat['label'] = $teks['label'] . ' terlalu panjang. Persingkat menjadi paling banyak ' . TautanModel::PANJANG['label'] . ' huruf.';
        }

        $nilai = null;

        if ($isian['nilai'] === '') {
            $galat['nilai'] = $teks['nilai_kosong'];
        } else {
            $nilai = $jenis === TautanModel::NARAHUBUNG
                ? NomorWa::normalkan($isian['nilai'])
                : TautanModel::rapikanAlamatWeb($isian['nilai']);

            if ($nilai === null) {
                $galat['nilai'] = $jenis === TautanModel::NARAHUBUNG ? NomorWa::PESAN_GALAT : TautanModel::PESAN_ALAMAT_WEB;
            }
        }

        if ($nilai !== null) {
            $kembar = $this->tautan->where('jenis', $jenis)->where('nilai', $nilai);

            if ($id !== null) {
                $kembar->where('id !=', $id);
            }

            $kembar = $kembar->first();

            if ($kembar !== null) {
                $galat['nilai'] = sprintf($teks['nilai_kembar'], $kembar['label']);
            }
        }

        $urutan = null;

        if ($isian['urutan'] === '') {
            $urutan = $this->tautan->urutanBerikutnya($jenis, $id);
        } elseif (preg_match('/^\d{1,4}$/', $isian['urutan']) === 1) {
            $urutan = (int) $isian['urutan'];
        } else {
            $galat['urutan'] = 'Urutan belum benar. Tulis angka 0 sampai ' . TautanModel::URUTAN_MAKSIMAL . '; angka kecil tampil lebih dulu.';
        }

        return [['label' => $isian['label'], 'nilai' => $nilai, 'urutan' => $urutan], $galat];
    }

    /**
     * @param array<string, mixed>|null $kontak Kontak yang diubah, `null` untuk kontak baru.
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     */
    private function tampilkanFormulir(string $jenis, ?array $kontak, array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/kontak/formulir', [
            'jenis'       => $jenis,
            'jenisAlamat' => self::jenisUntukAlamat($jenis),
            'teks'        => self::TEKS[$jenis],
            'kontak'      => $kontak,
            'isian'       => $isian,
            'galat'       => $galat,
        ]);
    }

    private function jenisTidakDikenal(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Jenis kontak belum dipilih. Tekan tombol Tambah narahubung atau Tambah media sosial di bawah.',
        );
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Kontak itu tidak ditemukan, mungkin sudah dihapus. Pilih kontak dari daftar di bawah.',
        );
    }
}
