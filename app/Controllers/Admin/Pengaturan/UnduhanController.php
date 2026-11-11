<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\Unggahan;
use App\Models\UnduhanModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Pengaturan unduhan: `admin/pengaturan/unduhan` (F2-07).
 *
 * Admin mengunggah dan mengelola berkas yang bisa diunduh sekolah (flyer,
 * juknis, dan lainnya). Berkas disimpan lewat `App\Libraries\Unggahan` di
 * folder `unduhan`; jalurnya disimpan di kolom `unduhan.berkas`.
 *
 * Urutan langkah supaya tidak ada berkas tanpa baris (yatim) atau baris tanpa
 * berkas bila salah satu langkah gagal:
 *
 * - Tambah: simpan berkas → simpan baris. Bila baris gagal disimpan, berkas
 *   baru dihapus lagi.
 * - Ganti berkas: simpan berkas baru → perbarui baris → hapus berkas lama.
 *   Bila baris gagal diperbarui, berkas baru dihapus dan baris tetap menunjuk
 *   berkas lama.
 * - Hapus: hapus baris di dalam transaksi → hapus berkas → selesaikan
 *   transaksi. Bila berkas tidak bisa dihapus, transaksi dibatalkan sehingga
 *   barisnya kembali. Berkas yang sudah tidak ada tidak menghalangi hapus.
 */
class UnduhanController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/unduhan';

    /**
     * Folder unggahan untuk berkas unduhan.
     */
    private const FOLDER = 'unduhan';

    private UnduhanModel $model;

    private Unggahan $unggahan;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->model    = model(UnduhanModel::class);
        $this->unggahan = new Unggahan();
    }

    public function index(): string
    {
        $daftar = array_map(
            fn (array $baris): array => $baris + ['info' => $this->infoBerkas((string) $baris['berkas'])],
            $this->model->daftar(),
        );

        return view('admin/pengaturan/unduhan/index', ['daftar' => $daftar]);
    }

    public function tambah(): string
    {
        return $this->tampilkanFormulir(null, [
            'judul'      => '',
            'keterangan' => '',
            'urutan'     => (string) $this->model->urutanBerikutnya(),
            'tampil'     => true,
        ]);
    }

    public function buat(): RedirectResponse|string
    {
        $isian  = $this->bacaIsian();
        $berkas = $this->request->getFile('berkas');
        $galat  = $this->periksaIsian($isian);

        // Berkas wajib saat menambah; pesan "belum dipilih" juga dari `periksaBerkas()`.
        $pesanBerkas = $this->unggahan->periksaBerkas($berkas);

        if ($pesanBerkas !== null) {
            $galat['berkas'] = $pesanBerkas;
        }

        if ($galat !== []) {
            return $this->tampilkanFormulir(null, $isian, $galat, $pesanBerkas === null);
        }

        try {
            $jalur = $this->unggahan->simpanBerkas($berkas, self::FOLDER);
        } catch (RuntimeException $e) {
            return $this->tampilkanFormulir(null, $isian, ['berkas' => $e->getMessage()]);
        }

        try {
            $id = $this->model->insert($this->dataBaris($isian) + ['berkas' => $jalur]);
        } catch (Throwable $e) {
            $this->unggahan->hapus($jalur);

            throw $e;
        }

        if ($id === false) {
            $this->unggahan->hapus($jalur);

            return redirect()->to(self::ALAMAT)->with(
                'pesan_galat',
                'Berkas "' . $isian['judul'] . '" belum tersimpan karena ada gangguan di server. Coba tambahkan sekali lagi; bila masih gagal, hubungi pengelola server.',
            );
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Berkas "' . $isian['judul'] . '" tersimpan.');
    }

    public function ubah(int $id): string
    {
        $baris = $this->cari($id);

        return $this->tampilkanFormulir($baris, [
            'judul'      => (string) $baris['judul'],
            'keterangan' => (string) ($baris['keterangan'] ?? ''),
            'urutan'     => (string) $baris['urutan'],
            'tampil'     => (int) $baris['tampil'] === 1,
        ]);
    }

    public function perbarui(int $id): RedirectResponse|string
    {
        $baris  = $this->cari($id);
        $isian  = $this->bacaIsian();
        $berkas = $this->request->getFile('berkas');
        $galat  = $this->periksaIsian($isian);
        $ganti  = Unggahan::dipilih($berkas);

        // Berkas boleh tidak diganti; bila dipilih, harus sah.
        $pesanBerkas = $ganti ? $this->unggahan->periksaBerkas($berkas) : null;

        if ($pesanBerkas !== null) {
            $galat['berkas'] = $pesanBerkas;
        }

        if ($galat !== []) {
            return $this->tampilkanFormulir($baris, $isian, $galat, $ganti && $pesanBerkas === null);
        }

        $data      = $this->dataBaris($isian);
        $jalurBaru = null;

        if ($ganti) {
            try {
                $jalurBaru = $this->unggahan->simpanBerkas($berkas, self::FOLDER);
            } catch (RuntimeException $e) {
                return $this->tampilkanFormulir($baris, $isian, ['berkas' => $e->getMessage()]);
            }

            $data['berkas'] = $jalurBaru;
        }

        try {
            $berhasil = $this->model->update($id, $data);
        } catch (Throwable $e) {
            $this->unggahan->hapus($jalurBaru);

            throw $e;
        }

        if (! $berhasil) {
            $this->unggahan->hapus($jalurBaru);

            return redirect()->to(self::ALAMAT)->with(
                'pesan_galat',
                'Perubahan berkas "' . $baris['judul'] . '" belum tersimpan karena ada gangguan di server. Berkas lama tetap dipakai. Coba ubah sekali lagi.',
            );
        }

        if ($jalurBaru !== null) {
            // Berkas lama dibuang setelah berkas baru tersimpan dan baris menunjuknya.
            $this->unggahan->hapus((string) $baris['berkas']);
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Perubahan berkas "' . $isian['judul'] . '" tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $baris = $this->cari($id);
        $jalur = (string) $baris['berkas'];
        $db    = $this->model->db;

        $db->transBegin();

        try {
            $terhapus = $this->model->delete($id);
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        if (! $terhapus) {
            $db->transRollback();

            return redirect()->to(self::ALAMAT)->with(
                'pesan_galat',
                'Berkas "' . $baris['judul'] . '" belum terhapus karena ada gangguan di server. Coba hapus sekali lagi.',
            );
        }

        // Berkas dihapus sebelum transaksi diselesaikan: bila gagal, baris kembali.
        if ($this->unggahan->ada($jalur) && ! $this->unggahan->hapus($jalur)) {
            $db->transRollback();

            return redirect()->to(self::ALAMAT)->with(
                'pesan_galat',
                'Berkas "' . $baris['judul'] . '" tidak bisa dihapus dari server, jadi datanya tetap disimpan. Pastikan folder public/unggahan boleh ditulisi, lalu coba hapus lagi.',
            );
        }

        $db->transCommit();

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Berkas "' . $baris['judul'] . '" sudah dihapus.');
    }

    /**
     * Baris unduhan, atau halaman tidak ditemukan.
     *
     * @return array<string, mixed>
     */
    private function cari(int $id): array
    {
        $baris = $this->model->find($id);

        if (! is_array($baris)) {
            throw PageNotFoundException::forPageNotFound('Berkas unduhan ini tidak ditemukan. Mungkin sudah dihapus; kembali ke daftar unduhan.');
        }

        return $baris;
    }

    /**
     * Isian formulir apa adanya (sudah dirapikan spasinya), untuk diperiksa
     * dan ditampilkan ulang.
     *
     * @return array{judul: string, keterangan: string, urutan: string, tampil: bool}
     */
    private function bacaIsian(): array
    {
        return [
            'judul'      => trim((string) $this->request->getPost('judul')),
            'keterangan' => trim((string) $this->request->getPost('keterangan')),
            'urutan'     => trim((string) $this->request->getPost('urutan')),
            'tampil'     => (string) $this->request->getPost('tampil') === '1',
        ];
    }

    /**
     * Periksa isian selain berkas. Kosong bila sah.
     *
     * @param array{judul: string, keterangan: string, urutan: string, tampil: bool} $isian
     *
     * @return array<string, string>
     */
    private function periksaIsian(array $isian): array
    {
        $aturan = [
            'judul' => [
                'rules'  => 'required|max_length[' . UnduhanModel::PANJANG_JUDUL . ']',
                'errors' => [
                    'required'   => 'Judul belum diisi. Tulis nama berkas yang mudah dikenali sekolah, misalnya Juknis Seri 2.',
                    'max_length' => 'Judul terlalu panjang. Persingkat menjadi paling banyak ' . UnduhanModel::PANJANG_JUDUL . ' huruf.',
                ],
            ],
            'keterangan' => [
                'rules'  => 'permit_empty|max_length[' . UnduhanModel::PANJANG_KETERANGAN . ']',
                'errors' => [
                    'max_length' => 'Keterangan terlalu panjang. Persingkat menjadi paling banyak ' . UnduhanModel::PANJANG_KETERANGAN . ' huruf.',
                ],
            ],
            'urutan' => [
                'rules'  => 'required|is_natural|less_than_equal_to[' . UnduhanModel::URUTAN_TERBESAR . ']',
                'errors' => [
                    'required'              => 'Urutan belum diisi. Tulis angka, misalnya 1; angka kecil tampil lebih dulu.',
                    'is_natural'            => 'Urutan harus angka bulat tanpa tanda, misalnya 1 atau 2.',
                    'less_than_equal_to'    => 'Urutan terlalu besar. Pakai angka 0 sampai ' . UnduhanModel::URUTAN_TERBESAR . '.',
                ],
            ],
        ];

        $isianTeks = ['judul' => $isian['judul'], 'keterangan' => $isian['keterangan'], 'urutan' => $isian['urutan']];

        return $this->validateData($isianTeks, $aturan) ? [] : $this->validator->getErrors();
    }

    /**
     * Data baris dari isian yang sudah sah (tanpa berkas).
     *
     * @param array{judul: string, keterangan: string, urutan: string, tampil: bool} $isian
     *
     * @return array{judul: string, keterangan: string|null, urutan: int, tampil: int}
     */
    private function dataBaris(array $isian): array
    {
        return [
            'judul'      => $isian['judul'],
            'keterangan' => $isian['keterangan'] === '' ? null : $isian['keterangan'],
            'urutan'     => (int) $isian['urutan'],
            'tampil'     => $isian['tampil'] ? 1 : 0,
        ];
    }

    /**
     * Keterangan berkas tersimpan untuk ditampilkan.
     *
     * @return array{ada: bool, jenis: string, ukuran: int|null, ukuran_teks: string, alamat: string}
     */
    private function infoBerkas(string $jalur): array
    {
        $ukuran = $this->unggahan->ukuran($jalur);

        return [
            'ada'         => $ukuran !== null,
            'jenis'       => $this->unggahan->jenis($jalur),
            'ukuran'      => $ukuran,
            'ukuran_teks' => $ukuran === null ? '' : Unggahan::ukuranTeks($ukuran),
            'alamat'      => base_url($jalur),
        ];
    }

    /**
     * Formulir tambah (`$baris` null) atau ubah.
     *
     * @param array<string, mixed>|null                                             $baris
     * @param array{judul: string, keterangan: string, urutan: string, tampil: bool} $isian
     * @param array<string, string>                                                 $galat
     * @param bool                                                                  $pilihUlang Berkas yang dipilih sah, tetapi belum disimpan karena isian lain salah.
     */
    private function tampilkanFormulir(?array $baris, array $isian, array $galat = [], bool $pilihUlang = false): string
    {
        return view('admin/pengaturan/unduhan/formulir', [
            'baris'      => $baris === null ? null : $baris + ['info' => $this->infoBerkas((string) $baris['berkas'])],
            'isian'      => $isian,
            'galat'      => $galat,
            'pilihUlang' => $pilihUlang,
        ]);
    }
}
