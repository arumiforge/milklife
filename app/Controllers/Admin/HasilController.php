<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\UnduhanHasil;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Hasil lomba di panel admin (F8-04, K124): `admin/hasil`.
 *
 * - `index`: nomor lomba per kelompok umur dengan banyak babak, ada Final, dan tanda terbit.
 * - `nomor`: babak satu nomor beserta hasilnya, tambah babak, Terbitkan/Tarik kembali, unduh PDF/Excel.
 * - `babak`: isian prestasi, keterangan, dan (Final) koreksi peringkat; atur peserta; ubah dan hapus babak.
 */
class HasilController extends BaseController
{
    private HasilLomba $hasil;

    public function __construct()
    {
        $this->hasil = new HasilLomba();
    }

    public function index(): string
    {
        return view('admin/hasil/index', ['kelompokUmur' => $this->hasil->daftarNomor()]);
    }

    public function nomor(int $id): string
    {
        $nomor = $this->cariNomor($id);

        return view('admin/hasil/nomor', [
            'nomor' => $nomor,
            'babak' => $this->hasil->semuaBabak($nomor),
            'calon' => count($this->hasil->calon($nomor)),
            'galat' => (array) (session('galat_babak') ?? []),
            'isian' => (array) (session('isian_babak') ?? []),
        ]);
    }

    public function tambahBabak(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $isi   = (string) $this->request->getPost('isi');
        $dari  = $this->request->getPost('dari_babak');
        $lolos = trim((string) $this->request->getPost('jumlah_lolos'));
        $nama  = (string) $this->request->getPost('nama');
        $final = $this->request->getPost('final') === '1';

        $hasil = $this->hasil->tambahBabak(
            $id,
            $nama,
            $final,
            $isi,
            is_numeric($dari) ? (int) $dari : null,
            ctype_digit($lolos) ? (int) $lolos : null,
        );

        if (isset($hasil['galat'])) {
            return redirect()->to(site_url('admin/hasil/nomor/' . $id) . '#tambah-babak')
                ->with('galat_babak', $hasil['galat'])
                ->with('isian_babak', ['nama' => $nama, 'final' => $final, 'isi' => $isi, 'dari_babak' => (string) $dari, 'jumlah_lolos' => $lolos])
                ->with('pesan_galat', 'Babak belum ditambahkan. Periksa isian yang ditandai.');
        }

        return redirect()->to('admin/hasil/babak/' . $hasil['id'])
            ->with('pesan_sukses', 'Babak ' . HasilLomba::rapikanNama($nama) . ' untuk ' . $nomor['nama'] . ' ditambahkan. Isi prestasinya di bawah.');
    }

    public function terbitkan(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $this->hasil->terbitkan($id, true);

        return redirect()->to('admin/hasil/nomor/' . $id)
            ->with('pesan_sukses', 'Hasil ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' terbit di halaman Hasil dan dihitung di klasemen publik.');
    }

    public function tarik(int $id): RedirectResponse
    {
        $nomor = $this->cariNomor($id);
        $this->hasil->terbitkan($id, false);

        return redirect()->to('admin/hasil/nomor/' . $id)
            ->with('pesan_sukses', 'Hasil ' . $nomor['nama'] . ' ' . $nomor['kelompok_umur'] . ' ditarik dari halaman publik.');
    }

    public function unduh(int $id, string $jenis): ResponseInterface
    {
        $nomor   = $this->cariNomor($id);
        $unduhan = new UnduhanHasil($nomor, $this->hasil->semuaBabak($nomor));

        if ($jenis === 'excel') {
            return $unduhan->excel()->unduh(UnduhanHasil::namaBerkas($nomor, 'xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanHasil::namaBerkas($nomor, 'pdf') . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($unduhan->pdf());
    }

    public function babak(int $id): string
    {
        $babak = $this->cariBabak($id);

        return view('admin/hasil/babak', [
            'babak'      => $babak,
            'hasil'      => $this->hasil->hasilBabak($id, $babak['nomor']),
            'calon'      => $this->hasil->calon($babak['nomor']),
            'galat'      => (array) (session('galat_hasil') ?? []),
            'isian'      => (array) (session('isian_hasil') ?? []),
            'galatBabak' => (array) (session('galat_ubah_babak') ?? []),
        ]);
    }

    public function simpanHasil(int $id): RedirectResponse
    {
        $this->cariBabak($id);
        $isian = $this->request->getPost('hasil');
        $isian = is_array($isian) ? $isian : [];
        $galat = $this->hasil->simpanHasil($id, $isian);

        if ($galat !== []) {
            return redirect()->to('admin/hasil/babak/' . $id)
                ->with('galat_hasil', $galat)
                ->with('isian_hasil', $isian)
                ->with('pesan_galat', 'Hasil belum tersimpan: ' . count($galat) . ' isian belum benar. Perbaiki yang ditandai merah, lalu simpan lagi.');
        }

        return redirect()->to('admin/hasil/babak/' . $id)->with('pesan_sukses', 'Hasil tersimpan dan peringkat dihitung ulang.');
    }

    public function peserta(int $id): RedirectResponse
    {
        $this->cariBabak($id);
        $kunci  = $this->request->getPost('peserta');
        $kunci  = is_array($kunci) ? array_values(array_filter($kunci, 'is_string')) : [];
        $jumlah = $this->hasil->aturPeserta($id, $kunci);

        return redirect()->to('admin/hasil/babak/' . $id)
            ->with('pesan_sukses', 'Peserta babak tersimpan: ' . $jumlah['tambah'] . ' ditambah, ' . $jumlah['hapus'] . ' dikeluarkan.');
    }

    public function ubahBabak(int $id): RedirectResponse
    {
        $this->cariBabak($id);
        $galat = $this->hasil->ubahBabak($id, (string) $this->request->getPost('nama'), $this->request->getPost('final') === '1');

        if ($galat !== []) {
            return redirect()->to(site_url('admin/hasil/babak/' . $id) . '#ubah-babak')
                ->with('galat_ubah_babak', $galat)
                ->with('pesan_galat', 'Babak belum diubah. ' . implode(' ', $galat));
        }

        return redirect()->to('admin/hasil/babak/' . $id)->with('pesan_sukses', 'Babak diubah.');
    }

    public function hapusBabak(int $id): RedirectResponse
    {
        $babak = $this->cariBabak($id);
        $this->hasil->hapusBabak($id);

        return redirect()->to('admin/hasil/nomor/' . $babak['nomor']['id'])
            ->with('pesan_sukses', 'Babak ' . $babak['nama'] . ' beserta hasilnya dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function cariNomor(int $id): array
    {
        return $this->hasil->nomor($id) ?? throw PageNotFoundException::forPageNotFound('Nomor lomba tidak ditemukan.');
    }

    /**
     * @return array{id: int, nama: string, final: bool, urutan: int, nomor: array<string, mixed>}
     */
    private function cariBabak(int $id): array
    {
        return $this->hasil->babak($id) ?? throw PageNotFoundException::forPageNotFound('Babak tidak ditemukan.');
    }
}
