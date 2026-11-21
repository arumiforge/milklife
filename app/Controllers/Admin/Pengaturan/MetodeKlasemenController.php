<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan > Hasil (F9-11, K140 butir 7a): `GET|POST /admin/pengaturan/hasil`.
 *
 * Metode klasemen juara umum: **Medali** (bawaan, juknis bagian 11) atau
 * **Poin per peringkat** dengan poin peringkat 1–8 yang bisa diubah (bawaan
 * 7-5-4-3-2-1-0-0). Disimpan di `Klasemen.metode` dan `Klasemen.poin`; dibaca
 * `HasilLomba::klasemen()` untuk halaman klasemen admin, publik, unduhan, dan
 * videotron.
 */
class MetodeKlasemenController extends BaseController
{
    public function index(): string
    {
        return $this->tampilkan(MetodeKlasemen::metode(), array_map('strval', MetodeKlasemen::poin()));
    }

    public function simpan(): ResponseInterface|string
    {
        $metode = trim((string) $this->request->getPost('metode'));
        $poin   = $this->request->getPost('poin');
        $poin   = is_array($poin) ? array_values(array_map(static fn (mixed $p): string => is_scalar($p) ? trim((string) $p) : '', $poin)) : [];
        $galat  = MetodeKlasemen::periksa($metode, $poin);

        if ($galat !== []) {
            return $this->tampilkan($metode, $poin, $galat);
        }

        MetodeKlasemen::simpan($metode, $poin);

        return redirect()->to('admin/pengaturan/hasil')->with('pesan_sukses', 'Metode klasemen tersimpan: ' . Klasemen::METODE[$metode] . '.');
    }

    /**
     * @param list<string>          $poin
     * @param array<string, string> $galat
     */
    private function tampilkan(string $metode, array $poin, array $galat = []): string
    {
        return view('admin/pengaturan/hasil', [
            'metode' => $metode,
            'poin'   => array_pad(array_slice($poin, 0, Klasemen::PERINGKAT_POIN), Klasemen::PERINGKAT_POIN, ''),
            'galat'  => $galat,
        ]);
    }
}
