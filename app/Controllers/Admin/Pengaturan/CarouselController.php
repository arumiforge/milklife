<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\CarouselHalamanDepan;
use App\Libraries\Unggahan;
use App\Libraries\VarianFoto;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

/**
 * Foto carousel pembuka halaman depan (F9-03), dikelola di bagian
 * "Foto carousel" halaman Pengaturan > Halaman depan:
 *
 * - `POST admin/pengaturan/halaman-depan/carousel`: tambah foto (`foto`, `keterangan`);
 * - `POST …/carousel/<urutan>/naik|turun`: pindah satu tempat;
 * - `POST …/carousel/<urutan>/hapus`: hapus foto beserta berkasnya dan salinan kecilnya (`VarianFoto`).
 *
 * `<urutan>` mulai dari 1. Isian `berkas` wajib sama dengan foto di urutan
 * itu, supaya tindakan dari halaman yang sudah lama dibuka tidak mengenai
 * foto lain.
 */
class CarouselController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/halaman-depan#carousel';

    public function tambah(): RedirectResponse
    {
        $daftar     = CarouselHalamanDepan::tersimpan() ?? [];
        $berkas     = $this->request->getFile('foto');
        $keterangan = trim((string) preg_replace('/\s+/u', ' ', (string) $this->request->getPost('keterangan')));

        if (count($daftar) >= CarouselHalamanDepan::BATAS) {
            return $this->galat('Carousel sudah berisi ' . CarouselHalamanDepan::BATAS . ' foto. Hapus foto yang tidak dipakai dulu, lalu tambahkan foto baru.');
        }

        if (! Unggahan::dipilih($berkas)) {
            return $this->galat('Pilih foto yang akan ditambahkan dulu.');
        }

        if (mb_strlen($keterangan) > CarouselHalamanDepan::BATAS_KETERANGAN) {
            return $this->galat('Keterangan foto terlalu panjang. Persingkat menjadi paling banyak ' . CarouselHalamanDepan::BATAS_KETERANGAN . ' huruf.');
        }

        $unggahan = new Unggahan();
        $pesan    = $unggahan->periksaGambar($berkas);

        if ($pesan !== null) {
            return $this->galat('Foto carousel: ' . $pesan);
        }

        try {
            $jalur = $unggahan->simpanGambar($berkas, CarouselHalamanDepan::FOLDER);
        } catch (RuntimeException $e) {
            return $this->galat('Foto carousel: ' . $e->getMessage());
        }

        // Foto carousel lebih kecil dari gambar lain (1600 piksel, sama dengan foto bawaan, K143).
        Unggahan::kecilkanGambar((string) $unggahan->jalurPenuh($jalur), CarouselHalamanDepan::SISI);

        $daftar[] = ['berkas' => $jalur, 'keterangan' => $keterangan];
        CarouselHalamanDepan::simpan($daftar);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Foto ditambahkan ke carousel halaman depan.');
    }

    public function geser(int $urutan, string $arah): RedirectResponse
    {
        $daftar = CarouselHalamanDepan::tersimpan() ?? [];
        $indeks = $this->indeks($daftar, $urutan);

        if ($indeks === null) {
            return $this->galat('Foto itu sudah berubah atau dihapus. Periksa lagi urutan foto di bawah.');
        }

        $tujuan = $arah === 'naik' ? $indeks - 1 : $indeks + 1;

        if (! isset($daftar[$tujuan])) {
            return redirect()->to(self::ALAMAT);
        }

        CarouselHalamanDepan::simpan(CarouselHalamanDepan::geser($daftar, $indeks, $tujuan));

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Urutan foto carousel tersimpan.');
    }

    public function hapus(int $urutan): RedirectResponse
    {
        $daftar = CarouselHalamanDepan::tersimpan() ?? [];
        $indeks = $this->indeks($daftar, $urutan);

        if ($indeks === null) {
            return $this->galat('Foto itu sudah berubah atau dihapus. Periksa lagi daftar foto di bawah.');
        }

        $jalur = $daftar[$indeks]['berkas'];
        array_splice($daftar, $indeks, 1);
        CarouselHalamanDepan::simpan($daftar);
        VarianFoto::hapus($jalur);
        (new Unggahan())->hapus($jalur);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Foto dihapus dari carousel halaman depan.');
    }

    /**
     * Indeks foto untuk `<urutan>` (mulai 1), atau `null` bila tidak ada atau
     * berkasnya tidak sama dengan isian `berkas`.
     *
     * @param list<array{berkas: string, keterangan: string}> $daftar
     */
    private function indeks(array $daftar, int $urutan): ?int
    {
        $indeks = $urutan - 1;

        return isset($daftar[$indeks]) && $daftar[$indeks]['berkas'] === (string) $this->request->getPost('berkas') ? $indeks : null;
    }

    private function galat(string $pesan): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with('pesan_galat', $pesan);
    }
}
