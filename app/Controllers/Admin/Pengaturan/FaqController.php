<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\Bantuan\FaqBawaan;
use App\Libraries\Bantuan\JawabanFaq;
use App\Models\FaqModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan > Bantuan: `/admin/pengaturan/bantuan` (F9-04), untuk tabel `faq`.
 *
 * Pola halaman daftar data (rencana Tahap 2): daftar per kategori, tambah,
 * ubah, hapus. Ditambah:
 *
 * - `pindah`: tombol Naik/Turun menukar urutan dengan tetangganya di kategori yang sama.
 * - `aktif`: tombol Sembunyikan/Tampilkan tanpa membuka formulir.
 * - `bawaan`: tombol "Pasang tanya-jawab bawaan", hanya bila daftar masih
 *   kosong (sama dengan `php spark faq:awal`).
 *
 * Kategori baru dipilih lewat `bantuan/tambah?kategori=<kunci>`.
 */
class FaqController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/bantuan';

    private FaqModel $faq;

    public function __construct()
    {
        $this->faq = model(FaqModel::class);
    }

    public function index(): string
    {
        $perKategori = $this->faq->semuaPerKategori();

        return view('admin/pengaturan/faq/index', [
            'perKategori' => $perKategori,
            'jumlah'      => array_sum(array_map('count', $perKategori)),
        ]);
    }

    public function tambah(): string
    {
        $kategori = $this->request->getGet('kategori');
        $kategori = FaqModel::kategoriSah($kategori) ? (string) $kategori : '';

        return $this->tampilkanFormulir(null, [
            'kategori'   => $kategori,
            'pertanyaan' => '',
            'jawaban'    => '',
            'urutan'     => $kategori !== '' ? (string) $this->faq->urutanBerikutnya($kategori) : '',
            'aktif'      => '1',
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        $isian          = $this->isianFormulir();
        [$data, $galat] = $this->faq->periksa($isian);

        if ($galat !== []) {
            return $this->tampilkanFormulir(null, $isian, $galat);
        }

        $id = (int) $this->faq->insert($data);

        return redirect()->to(self::ALAMAT . '#faq-' . $id)->with('pesan_sukses', 'Tanya-jawab "' . $data['pertanyaan'] . '" tersimpan.');
    }

    public function ubah(int $id): ResponseInterface|string
    {
        $faq = $this->faq->find($id);

        if (! is_array($faq)) {
            return $this->tidakDitemukan();
        }

        return $this->tampilkanFormulir($faq, [
            'kategori'   => (string) $faq['kategori'],
            'pertanyaan' => (string) $faq['pertanyaan'],
            'jawaban'    => (string) $faq['jawaban'],
            'urutan'     => (string) $faq['urutan'],
            'aktif'      => (int) $faq['aktif'] === 1 ? '1' : '',
        ]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $faq = $this->faq->find($id);

        if (! is_array($faq)) {
            return $this->tidakDitemukan();
        }

        $isian          = $this->isianFormulir();
        [$data, $galat] = $this->faq->periksa($isian, $id);

        if ($galat !== []) {
            return $this->tampilkanFormulir($faq, $isian, $galat);
        }

        $this->faq->update($id, $data);

        return redirect()->to(self::ALAMAT . '#faq-' . $id)->with('pesan_sukses', 'Perubahan tanya-jawab "' . $data['pertanyaan'] . '" tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $faq = $this->faq->find($id);

        if (! is_array($faq)) {
            return $this->tidakDitemukan();
        }

        $this->faq->delete($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Tanya-jawab "' . $faq['pertanyaan'] . '" sudah dihapus.');
    }

    /**
     * Naik atau turun satu tempat di kategorinya.
     */
    public function pindah(int $id, string $arah): RedirectResponse
    {
        $faq = $this->faq->find($id);

        if (! is_array($faq)) {
            return $this->tidakDitemukan();
        }

        if (! $this->faq->pindah($id, $arah)) {
            return redirect()->to(self::ALAMAT . '#faq-' . $id)->with(
                'pesan_galat',
                $arah === 'naik' ? 'Tanya-jawab ini sudah paling atas di kategorinya.' : 'Tanya-jawab ini sudah paling bawah di kategorinya.',
            );
        }

        return redirect()->to(self::ALAMAT . '#faq-' . $id)->with('pesan_sukses', 'Urutan "' . $faq['pertanyaan'] . '" diubah.');
    }

    /**
     * Tampilkan atau sembunyikan dari halaman Bantuan.
     */
    public function aktif(int $id): RedirectResponse
    {
        $faq = $this->faq->find($id);

        if (! is_array($faq)) {
            return $this->tidakDitemukan();
        }

        $aktif = (int) $faq['aktif'] === 1 ? 0 : 1;
        $this->faq->update($id, ['aktif' => $aktif]);

        return redirect()->to(self::ALAMAT . '#faq-' . $id)->with(
            'pesan_sukses',
            '"' . $faq['pertanyaan'] . '" ' . ($aktif === 1 ? 'tampil lagi di halaman Bantuan.' : 'disembunyikan dari halaman Bantuan.'),
        );
    }

    /**
     * Pasang tanya-jawab bawaan bila daftar masih kosong.
     */
    public function bawaan(): RedirectResponse
    {
        $jumlah = FaqBawaan::pasang(db_connect());

        if ($jumlah === 0) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Tanya-jawab bawaan hanya bisa dipasang bila daftar masih kosong. Daftar yang ada tidak diubah.');
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', $jumlah . ' tanya-jawab bawaan dipasang. Periksa dan sesuaikan isinya.');
    }

    /**
     * @return array<string, string>
     */
    private function isianFormulir(): array
    {
        $isian = [];

        foreach (['kategori', 'pertanyaan', 'jawaban', 'urutan', 'aktif'] as $kotak) {
            $nilai         = $this->request->getPost($kotak);
            $isian[$kotak] = is_string($nilai) ? trim($nilai) : '';
        }

        return $isian;
    }

    /**
     * @param array<string, mixed>|null $faq   Yang diubah, `null` untuk yang baru.
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     */
    private function tampilkanFormulir(?array $faq, array $isian, array $galat = []): string
    {
        $jawaban = new JawabanFaq();

        return view('admin/pengaturan/faq/formulir', [
            'faq'           => $faq,
            'isian'         => $isian,
            'galat'         => $galat,
            'isianOtomatis' => JawabanFaq::ISIAN,
            'tidakDikenal'  => JawabanFaq::isianTidakDikenal($isian['jawaban']),
            'pratinjau'     => $isian['jawaban'] !== '' ? $jawaban->html($isian['jawaban']) : '',
        ]);
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Tanya-jawab itu tidak ditemukan, mungkin sudah dihapus. Pilih dari daftar di bawah.',
        );
    }
}
