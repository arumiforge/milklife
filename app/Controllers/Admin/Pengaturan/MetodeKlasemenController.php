<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan > Hasil (F9-11, K140 butir 7a; F11-02, K172 butir 2):
 * `GET|POST /admin/pengaturan/hasil`.
 *
 * - Metode klasemen juara umum: **Medali** (bawaan, juknis bagian 11) atau
 *   **Poin per peringkat** dengan poin peringkat 1–8 yang bisa diubah (bawaan
 *   7-5-4-3-2-1-0-0). Disimpan di `Klasemen.metode` dan `Klasemen.poin`.
 * - Susunan klasemen: setiap kelompok umur diberi nomor klasemen; kelompok
 *   umur bernomor sama digabung (misalnya KU 8 dan KU 10). Disimpan di
 *   `Klasemen.gabungan`. Bila isian `klasemen` tidak dikirim, gabungan lama
 *   tidak berubah.
 *
 * Dibaca `HasilLomba::klasemen()` untuk halaman klasemen admin, publik,
 * unduhan, videotron, dan FAQ.
 */
class MetodeKlasemenController extends BaseController
{
    public function index(): string
    {
        return $this->tampilkan(MetodeKlasemen::metode(), array_map('strval', MetodeKlasemen::poin()), self::nomorKlasemen(MetodeKlasemen::susunan()));
    }

    public function simpan(): ResponseInterface|string
    {
        $metode   = trim((string) $this->request->getPost('metode'));
        $poin     = $this->request->getPost('poin');
        $poin     = is_array($poin) ? array_values(array_map(static fn (mixed $p): string => is_scalar($p) ? trim((string) $p) : '', $poin)) : [];
        $galat    = MetodeKlasemen::periksa($metode, $poin);
        $isianKu  = $this->request->getPost('klasemen');
        $gabungan = null;
        $nomor    = self::nomorKlasemen(MetodeKlasemen::susunan());

        if (is_array($isianKu)) {
            $periksa  = MetodeKlasemen::periksaGabungan($isianKu, array_keys($nomor));
            $galat   += $periksa['galat'];
            $gabungan = $periksa['gabungan'];

            foreach (array_keys($nomor) as $id) {
                $nomor[$id] = is_scalar($isianKu[$id] ?? null) ? trim((string) $isianKu[$id]) : '';
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($metode, $poin, $nomor, $galat);
        }

        MetodeKlasemen::simpan($metode, $poin);

        if ($gabungan !== null) {
            MetodeKlasemen::simpanGabungan($gabungan);
        }

        return redirect()->to('admin/pengaturan/hasil')->with('pesan_sukses', 'Pengaturan hasil tersimpan. Metode klasemen: ' . Klasemen::METODE[$metode] . '.');
    }

    /**
     * Nomor klasemen (mulai 1) per id kelompok umur, urut kelompok umur.
     *
     * @param list<array{kunci: string, label: string, kelompok_umur_id: list<int>}> $susunan
     *
     * @return array<int, string>
     */
    private static function nomorKlasemen(array $susunan): array
    {
        $nomor = [];

        foreach ($susunan as $i => $s) {
            foreach ($s['kelompok_umur_id'] as $id) {
                $nomor[$id] = (string) ($i + 1);
            }
        }

        $urut = [];

        foreach (self::kelompokUmur() as $id => $nama) {
            $urut[$id] = $nomor[$id] ?? '';
        }

        return $urut;
    }

    /**
     * @return array<int, string> id => nama, urutan Pengaturan > Kelompok umur
     */
    private static function kelompokUmur(): array
    {
        return array_map('strval', array_column(db_connect()->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray(), 'nama', 'id'));
    }

    /**
     * @param list<string>          $poin
     * @param array<int, string>    $nomor nomor klasemen per id kelompok umur
     * @param array<string, string> $galat
     */
    private function tampilkan(string $metode, array $poin, array $nomor, array $galat = []): string
    {
        return view('admin/pengaturan/hasil', [
            'metode'       => $metode,
            'poin'         => array_pad(array_slice($poin, 0, Klasemen::PERINGKAT_POIN), Klasemen::PERINGKAT_POIN, ''),
            'kelompokUmur' => self::kelompokUmur(),
            'nomor'        => $nomor,
            'susunan'      => MetodeKlasemen::susunan(),
            'galat'        => $galat,
        ]);
    }
}
