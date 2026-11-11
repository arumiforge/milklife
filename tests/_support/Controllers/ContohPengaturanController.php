<?php

namespace Tests\Support\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengendali contoh pola halaman pengaturan (hanya dipakai uji
 * `BerandaPengaturanPolaHalamanTest`). Halaman sungguhan mengikuti urutan
 * yang sama; lihat juga komentar `BerandaPengaturanController`.
 *
 * 1. `index`: tampilkan formulir berisi nilai tersimpan.
 * 2. `simpan` dengan isian salah: tampilkan ulang formulir (status 200) dengan
 *    isian yang diketik dan pesan per kotak.
 * 3. `simpan` yang berhasil: simpan, lalu alihkan dengan `pesan_sukses`.
 * 4. `hapus` yang tidak diizinkan keadaan data: alihkan dengan `pesan_galat`.
 */
class ContohPengaturanController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/contoh';

    public function index(): string
    {
        return $this->tampilkan([
            'nama'   => (string) setting('Acara.nama'),
            'tempat' => (string) setting('Acara.tempat'),
            'biaya'  => '',
        ]);
    }

    public function simpan(): ResponseInterface|string
    {
        $isian = [
            'nama'   => trim((string) $this->request->getPost('nama')),
            'tempat' => trim((string) $this->request->getPost('tempat')),
            'biaya'  => trim((string) $this->request->getPost('biaya')),
        ];

        $aturan = [
            'nama' => [
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama acara belum diisi. Tulis nama acara, misalnya MilkLife Athletics Challenge.',
                    'max_length' => 'Nama acara terlalu panjang. Persingkat menjadi paling banyak 100 huruf.',
                ],
            ],
            'tempat' => [
                'rules'  => 'max_length[100]',
                'errors' => ['max_length' => 'Tempat terlalu panjang. Persingkat menjadi paling banyak 100 huruf.'],
            ],
        ];

        $galat = $this->validateData($isian, $aturan) ? [] : $this->validator->getErrors();

        if ($isian['biaya'] !== '' && angka_rupiah($isian['biaya']) === null) {
            $galat['biaya'] = 'Biaya belum benar. Tulis angka rupiah tanpa koma, misalnya 150000.';
        }

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat);
        }

        setting('Acara.nama', $isian['nama']);
        setting('Acara.tempat', $isian['tempat']);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Data acara tersimpan.');
    }

    public function hapus(int $id): ResponseInterface
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            "Data nomor {$id} sudah dipakai, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.",
        );
    }

    /**
     * @param array<string, string> $isian
     * @param array<string, string> $galat
     */
    private function tampilkan(array $isian, array $galat = []): string
    {
        return view('Tests\Support\Views\contoh_pengaturan', ['isian' => $isian, 'galat' => $galat]);
    }
}
