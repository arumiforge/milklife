<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\BerkasHasil;
use App\Libraries\Hasil\HasilLomba;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Hasil lomba di panel (F8-04, K124): `admin/hasil`.
 *
 * - `index`: semua nomor per kelompok umur dengan keadaan hasilnya.
 * - `nomor`: babak satu nomor, tambah babak, Terbitkan/Tarik kembali, unduh.
 * - `babak`: isian prestasi semua peserta satu babak, tambah peserta,
 *   ubah atau hapus babak.
 *
 * Diisi akun admin yang sama, tanpa peran baru (K124 butir 9).
 */
class HasilController extends BaseController
{
    private const ALAMAT = 'admin/hasil';

    private HasilLomba $hasil;

    public function __construct()
    {
        $this->hasil = new HasilLomba();
    }

    public function index(): string
    {
        $kelompok = $this->hasil->daftarNomor();
        $saring   = (int) $this->request->getGet('kelompok-umur');
        $ada      = array_column($kelompok, 'nama', 'id');

        if (! isset($ada[$saring])) {
            $saring = 0;
        }

        return view('admin/hasil/index', [
            'kelompok'      => $saring === 0 ? $kelompok : array_values(array_filter($kelompok, static fn (array $k): bool => $k['id'] === $saring)),
            'kelompokUmur'  => $ada,
            'saring'        => $saring,
        ]);
    }

    public function nomor(int $id): string
    {
        $nomor = $this->cariNomor($id);

        return view('admin/hasil/nomor', [
            'nomor'  => $nomor,
            'babak'  => $this->hasil->susunan($nomor),
            'galat'  => session()->getFlashdata('galat_babak') ?? [],
            'isian'  => session()->getFlashdata('isian_babak') ?? ['nama' => '', 'final' => ''],
        ]);
    }

    public function tambahBabak(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $nama  = (string) $this->request->getPost('nama');
        $final = $this->request->getPost('final') === '1';
        $hasil = $this->hasil->tambahBabak($id, $nama, $final);

        if ($hasil['galat'] !== []) {
            return redirect()->to(site_url(self::ALAMAT . '/' . $id) . '#tambah-babak')
                ->with('galat_babak', $hasil['galat'])
                ->with('isian_babak', ['nama' => $nama, 'final' => $final ? '1' : ''])
                ->with('pesan_galat', 'Babak belum ditambahkan. Periksa isian di bawah.');
        }

        return redirect()->to(self::ALAMAT . '/babak/' . $hasil['id'])
            ->with('pesan_sukses', 'Babak ' . HasilLomba::rapikanNama($nama) . ' ditambahkan ke ' . $nomor['nama_lengkap'] . '. Pilih pesertanya di bawah.');
    }

    public function terbitkan(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $galat = $this->hasil->terbitkan($id);

        if ($galat !== null) {
            return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_galat', $galat);
        }

        return redirect()->to(self::ALAMAT . '/' . $id)
            ->with('pesan_sukses', 'Hasil ' . $nomor['nama_lengkap'] . ' ' . $nomor['nama_kelompok_umur'] . ' sudah terbit di halaman depan. Perubahan berikutnya langsung tampil.');
    }

    public function tarik(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $this->hasil->tarikKembali($id);

        return redirect()->to(self::ALAMAT . '/' . $id)
            ->with('pesan_sukses', 'Hasil ' . $nomor['nama_lengkap'] . ' ' . $nomor['nama_kelompok_umur'] . ' ditarik dari halaman depan.');
    }

    /**
     * Unduh hasil satu nomor: PDF atau Excel.
     */
    public function unduh(int $id, string $format): ResponseInterface
    {
        $nomor  = $this->cariNomor($id);
        $berkas = new BerkasHasil($nomor, $this->hasil->susunan($nomor));

        return $format === 'pdf'
            ? $this->response->setContentType('application/pdf')
                ->setHeader('Content-Disposition', 'attachment; filename="' . $berkas->namaBerkas('pdf') . '"')
                ->setBody($berkas->pdf())
            : $berkas->excel()->unduh($berkas->namaBerkas('xlsx'));
    }

    public function babak(int $id): string
    {
        [$babak, $nomor] = $this->cariBabak($id);

        return $this->tampilkanBabak($nomor, $babak);
    }

    public function simpanBabak(int $id): ResponseInterface|string
    {
        [$babak, $nomor] = $this->cariBabak($id);
        $isian           = $this->request->getPost('hasil');
        $isian           = is_array($isian) ? $isian : [];
        $galat           = $this->hasil->simpanHasil($nomor, $babak, $isian);

        if ($galat !== []) {
            session()->setFlashdata('pesan_galat', 'Hasil belum tersimpan: ' . count($galat) . ' baris perlu diperbaiki. Periksa kotak bertanda merah.');

            return $this->tampilkanBabak($nomor, $babak, $isian, $galat);
        }

        return redirect()->to(self::ALAMAT . '/babak/' . $id)->with('pesan_sukses', 'Hasil ' . $babak['nama'] . ' tersimpan; peringkat sudah diurutkan.');
    }

    public function tambahPeserta(int $id): RedirectResponse
    {
        [$babak, $nomor] = $this->cariBabak($id);
        $kunci           = $this->request->getPost('peserta');
        $kunci           = is_array($kunci) ? array_values(array_filter($kunci, 'is_string')) : [];
        $jumlah          = $this->hasil->tambahPeserta($nomor, $babak, $kunci);

        if ($jumlah === 0) {
            return redirect()->to(site_url(self::ALAMAT . '/babak/' . $id) . '#tambah-peserta')
                ->with('pesan_galat', 'Belum ada peserta yang dicentang. Centang peserta yang ikut babak ini, lalu tekan Tambahkan.');
        }

        return redirect()->to(self::ALAMAT . '/babak/' . $id)->with('pesan_sukses', $jumlah . ' peserta ditambahkan ke ' . $babak['nama'] . '.');
    }

    public function ubahBabak(int $id): RedirectResponse
    {
        [$babak] = $this->cariBabak($id);
        $nama    = (string) $this->request->getPost('nama');
        $galat   = $this->hasil->ubahBabak($babak, $nama, $this->request->getPost('final') === '1');

        if ($galat !== []) {
            return redirect()->to(site_url(self::ALAMAT . '/babak/' . $id) . '#ubah-babak')->with('pesan_galat', implode(' ', $galat));
        }

        return redirect()->to(self::ALAMAT . '/babak/' . $id)->with('pesan_sukses', 'Babak ' . HasilLomba::rapikanNama($nama) . ' tersimpan.');
    }

    public function hapusBabak(int $id): RedirectResponse
    {
        [$babak, $nomor] = $this->cariBabak($id);
        $this->hasil->hapusBabak($id);

        return redirect()->to(self::ALAMAT . '/' . (int) $nomor['id'])->with('pesan_sukses', 'Babak ' . $babak['nama'] . ' dan hasilnya sudah dihapus.');
    }

    /**
     * @param array<string, mixed>                     $nomor
     * @param array<string, mixed>                     $babak
     * @param array<int|string, mixed>                 $isian Isian kiriman (ditampilkan ulang bila ada galat).
     * @param array<int, array<string, string>>        $galat
     */
    private function tampilkanBabak(array $nomor, array $babak, array $isian = [], array $galat = []): string
    {
        return view('admin/hasil/babak', [
            'nomor'      => $nomor,
            'babak'      => $babak,
            'semuaBabak' => $this->hasil->daftarBabak((int) $nomor['id']),
            'baris'      => $this->hasil->barisBabak($nomor, $babak),
            'bisaDitambah' => $this->hasil->pesertaBisaDitambah($nomor, $babak),
            'isian'      => $isian,
            'galat'      => $galat,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cariNomor(int $id): array
    {
        return $this->hasil->nomor($id) ?? throw PageNotFoundException::forPageNotFound('Nomor lomba tidak ditemukan.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>} babak dan nomornya
     */
    private function cariBabak(int $id): array
    {
        $babak = $this->hasil->babak($id) ?? throw PageNotFoundException::forPageNotFound('Babak tidak ditemukan.');

        return [$babak, $this->cariNomor((int) $babak['nomor_lomba_id'])];
    }
}
