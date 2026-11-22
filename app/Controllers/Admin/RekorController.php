<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\Rekor;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rekor kejuaraan di panel admin (F11-06, K172 butir 3): `admin/rekor`.
 *
 * - `index`: semua rekor per kelompok umur (yang berlaku dan riwayatnya),
 *   sakelar halaman Rekor publik (`Rekor.tampil`, bawaan mati).
 * - `tambah`/`buat`, `ubah`/`perbarui`, `hapus`: rekor yang diisi admin.
 *   Rekor dari hasil terbit hanya berubah lewat hasilnya (ubah hasil atau
 *   tarik terbitan di Hasil lomba).
 */
class RekorController extends BaseController
{
    private const ALAMAT = 'admin/rekor';

    private Rekor $rekor;

    public function __construct()
    {
        $this->rekor = new Rekor();
    }

    public function index(): string
    {
        return view('admin/rekor/index', [
            'kelompok' => $this->rekor->daftar(),
            'tampil'   => Rekor::tampil(),
        ]);
    }

    public function tambah(): string
    {
        $nomor = $this->request->getGet('nomor');
        $isian = Rekor::rapikanIsian(['seri' => Rekor::seriBawaan(), 'nomor_lomba_id' => is_string($nomor) && ctype_digit($nomor) ? $nomor : '']);
        $pilih = $this->rekor->pilihanNomor()[(int) $isian['nomor_lomba_id']] ?? null;

        if ($pilih !== null) {
            $isian['berat_alat'] = $pilih['berat_alat'];
            $isian['cara_waktu'] = $pilih['cara_waktu'] ?? 'elektronik';
        }

        return $this->formulir(null, $isian);
    }

    public function buat(): ResponseInterface|string
    {
        $isian = Rekor::rapikanIsian((array) $this->request->getPost());
        $galat = $this->rekor->simpan(null, $isian);

        if ($galat !== []) {
            return $this->formulir(null, $isian, $galat);
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Rekor tersimpan.');
    }

    public function ubah(int $id): string
    {
        $rekor = $this->cariIsianAdmin($id);

        return $this->formulir($rekor, Rekor::isianDariBaris($rekor));
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $rekor = $this->cariIsianAdmin($id);
        $isian = Rekor::rapikanIsian((array) $this->request->getPost());
        $galat = $this->rekor->simpan($id, $isian);

        if ($galat !== []) {
            return $this->formulir($rekor, $isian, $galat);
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Rekor diubah.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $rekor = $this->cariIsianAdmin($id);
        $this->rekor->hapus($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Rekor ' . $rekor['nomor'] . ' ' . $rekor['kelompok_umur'] . ' dihapus.');
    }

    /**
     * Sakelar halaman Rekor publik.
     */
    public function tampil(): RedirectResponse
    {
        // Kotak centang: tidak terkirim sama sekali bila tidak dicentang.
        $tampil = $this->request->getPost('tampil_rekor') === '1';

        setting('Rekor.tampil', $tampil);

        return redirect()->to(self::ALAMAT . '#sakelar-rekor')
            ->with('pesan_sukses', $tampil ? 'Halaman Rekor sekarang tampil untuk umum.' : 'Halaman Rekor disembunyikan.');
    }

    /**
     * @param array<string, mixed>|null $rekor
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     */
    private function formulir(?array $rekor, array $isian, array $galat = []): string
    {
        return view('admin/rekor/formulir', [
            'rekor' => $rekor,
            'isian' => $isian,
            'galat' => $galat,
            'nomor' => $this->rekor->pilihanNomor(),
        ]);
    }

    /**
     * Rekor yang diisi admin; rekor dari hasil tidak bisa diubah di sini.
     *
     * @return array<string, mixed>
     */
    private function cariIsianAdmin(int $id): array
    {
        $rekor = $this->rekor->cari($id);

        if ($rekor === null || $rekor['hasil_id'] !== null) {
            throw PageNotFoundException::forPageNotFound('Rekor tidak ditemukan atau berasal dari hasil lomba.');
        }

        return $rekor;
    }
}
