<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan > Akun juri: `/admin/pengaturan/akun-juri` (F11-16, K181),
 * pola halaman daftar data.
 *
 * Admin membuat satu akun per juri, mengganti nama, nama pengguna, atau kata
 * sandinya, dan menghapusnya. Akun juri hanya bisa membuka Lembar juri dan
 * Akun miliknya (`AdminFilter`), jadi halaman ini tidak terbuka untuk juri.
 *
 * - Hanya baris berperan `juri` yang bisa diubah atau dihapus di sini; ID akun
 *   admin dijawab "tidak ditemukan". Akun admin tetap dibuat lewat
 *   `php spark admin:buat`.
 * - Mengganti kata sandi atau menghapus akun mengeluarkan juri yang sedang
 *   masuk dengan akun itu pada permintaan berikutnya (K67, `SesiAdmin`).
 * - Kata sandi tidak pernah ditampilkan atau diisi ulang di formulir.
 */
class AkunJuriController extends BaseController
{
    public const ALAMAT = 'admin/pengaturan/akun-juri';

    private AdminModel $akun;

    public function __construct()
    {
        $this->akun = model(AdminModel::class);
    }

    public function index(): string
    {
        return view('admin/pengaturan/akun_juri/index', [
            'daftar' => $this->akun->daftarJuri(),
        ]);
    }

    public function tambah(): string
    {
        return $this->formulir(null, ['nama' => '', 'nama_pengguna' => '']);
    }

    public function buat(): ResponseInterface|string
    {
        [$isian, $sandi, $ulangi] = $this->bacaIsian();
        $galat                    = $this->periksa($isian, $sandi, $ulangi, true, null);

        if ($galat !== []) {
            return $this->formulir(null, $isian, $galat);
        }

        $berhasil = $this->akun->insert($isian + ['peran' => AdminModel::PERAN_JURI, 'sandi' => $sandi]);

        if ($berhasil === false) {
            return $this->formulir(null, $isian, $this->akun->errors());
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Akun juri "' . $isian['nama_pengguna'] . '" sudah dibuat. Berikan nama pengguna dan kata sandinya kepada ' . $isian['nama'] . '.');
    }

    public function ubah(int $id): RedirectResponse|string
    {
        $juri = $this->akun->cariJuri($id);

        if ($juri === null) {
            return $this->tidakDitemukan();
        }

        return $this->formulir($juri, ['nama' => (string) $juri['nama'], 'nama_pengguna' => (string) $juri['nama_pengguna']]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $juri = $this->akun->cariJuri($id);

        if ($juri === null) {
            return $this->tidakDitemukan();
        }

        [$isian, $sandi, $ulangi] = $this->bacaIsian();
        $gantiSandi               = $sandi !== '' || $ulangi !== '';
        $galat                    = $this->periksa($isian, $sandi, $ulangi, $gantiSandi, $id);

        if ($galat !== []) {
            return $this->formulir($juri, $isian, $galat);
        }

        $data = $isian + ($gantiSandi ? ['sandi' => $sandi] : []);

        if (! $this->akun->update($id, $data)) {
            return $this->formulir($juri, $isian, $this->akun->errors());
        }

        $pesan = 'Akun juri "' . $isian['nama_pengguna'] . '" sudah diubah.';

        if ($gantiSandi) {
            $pesan .= ' Kata sandinya diganti, jadi juri yang sedang masuk dengan akun ini diminta masuk lagi dengan kata sandi baru.';
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', $pesan);
    }

    public function hapus(int $id): RedirectResponse
    {
        $juri = $this->akun->cariJuri($id);

        if ($juri === null) {
            return $this->tidakDitemukan();
        }

        $this->akun->delete($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Akun juri "' . $juri['nama_pengguna'] . '" sudah dihapus. Prestasi yang sudah diisinya tetap tersimpan.');
    }

    /**
     * @return array{0: array{nama: string, nama_pengguna: string}, 1: string, 2: string}
     */
    private function bacaIsian(): array
    {
        return [
            [
                'nama'          => trim((string) $this->request->getPost('nama')),
                'nama_pengguna' => AdminModel::normalkanNamaPengguna((string) $this->request->getPost('nama_pengguna')),
            ],
            (string) $this->request->getPost('sandi'),
            (string) $this->request->getPost('ulangi_sandi'),
        ];
    }

    /**
     * Pesan per kotak; kosong bila semua benar.
     *
     * @param array{nama: string, nama_pengguna: string} $isian
     *
     * @return array<string, string>
     */
    private function periksa(array $isian, string $sandi, string $ulangi, bool $periksaSandi, ?int $idSendiri): array
    {
        $galat = $this->akun->periksaIsian($isian + ($periksaSandi ? ['sandi' => $sandi] : []), false, $idSendiri);

        if ($isian['nama'] === '') {
            $galat['nama'] = 'Nama belum diisi. Tulis nama juri, misalnya Pak Budi (juri lompat jauh).';
        }

        if ($periksaSandi && ! isset($galat['sandi']) && $ulangi !== $sandi) {
            $galat['ulangi_sandi'] = 'Kedua kata sandi tidak sama. Ketik kata sandi yang sama di kedua kotak.';
        }

        return $galat;
    }

    /**
     * @param array<string, mixed>|null $juri  akun yang diubah, `null` untuk akun baru
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     */
    private function formulir(?array $juri, array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/akun_juri/formulir', [
            'juri'  => $juri,
            'isian' => $isian,
            'galat' => $galat,
        ]);
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Akun juri itu tidak ditemukan, mungkin sudah dihapus. Pilih akun dari daftar di bawah.',
        );
    }
}
