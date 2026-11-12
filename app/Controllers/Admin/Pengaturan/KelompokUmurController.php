<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Models\AwalanNomorDadaModel;
use App\Models\KelompokUmurModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan kelompok umur: `admin/pengaturan/kelompok-umur` (F2-03).
 *
 * Daftar, tambah, ubah, dan hapus kelompok umur beserta batas tahun lahir
 * (K36), jenjang sekolah yang boleh ikut (K23, K24), biaya individu, dan awalan
 * nomor dada (K05). Aturan periksa, simpan, dan hapus ada di
 * `App\Models\KelompokUmurModel`.
 */
class KelompokUmurController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/kelompok-umur';

    public function index(): string
    {
        $daftar = model(KelompokUmurModel::class)->daftarLengkap();

        foreach ($daftar as &$ku) {
            $ku['alasan_tidak_bisa_dihapus'] = model(KelompokUmurModel::class)->alasanTidakBisaDihapus($ku);
        }
        unset($ku);

        return view('admin/pengaturan/kelompok_umur/index', ['daftar' => $daftar]);
    }

    public function tambah(): string
    {
        return $this->tampilkanFormulir(null, [
            'nama'              => '',
            'tahun_lahir_awal'  => '',
            'tahun_lahir_akhir' => '',
            'jenjang'           => [],
            'biaya_individu'    => '',
            'awalan'            => '',
            'urutan'            => (string) model(KelompokUmurModel::class)->urutanBerikutnya(),
            'aktif'             => '1',
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        $isian = $this->ambilIsian();
        $model = model(KelompokUmurModel::class);

        [$data, $galat] = $model->periksa($isian);

        if ($galat !== []) {
            return $this->tampilkanFormulir(null, $isian, $galat);
        }

        $model->simpanLengkap($data);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Kelompok umur ' . $data['nama'] . ' tersimpan.');
    }

    public function ubah(int $id): string
    {
        $ku = $this->cariAtau404($id);

        return $this->tampilkanFormulir($ku, [
            'nama'              => (string) $ku['nama'],
            'tahun_lahir_awal'  => (string) $ku['tahun_lahir_awal'],
            'tahun_lahir_akhir' => (string) $ku['tahun_lahir_akhir'],
            'jenjang'           => $ku['jenjang'],
            'biaya_individu'    => (string) $ku['biaya_individu'],
            'awalan'            => implode(', ', array_column($ku['awalan'], 'awalan')),
            'urutan'            => (string) $ku['urutan'],
            'aktif'             => (string) $ku['aktif'],
        ]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $ku    = $this->cariAtau404($id);
        $isian = $this->ambilIsian();
        $model = model(KelompokUmurModel::class);

        [$data, $galat] = $model->periksa($isian, $id);

        if ($galat !== []) {
            return $this->tampilkanFormulir($ku, $isian, $galat);
        }

        $model->simpanLengkap($data, $id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Kelompok umur ' . $data['nama'] . ' tersimpan.');
    }

    public function hapus(int $id): ResponseInterface
    {
        $ku     = $this->cariAtau404($id);
        $model  = model(KelompokUmurModel::class);
        $alasan = $model->alasanTidakBisaDihapus($ku);

        if ($alasan !== null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', $alasan);
        }

        $model->hapusLengkap($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Kelompok umur ' . $ku['nama'] . ' sudah dihapus beserta jenjang dan awalan nomor dadanya.');
    }

    /**
     * Isian formulir apa adanya (teks), untuk diperiksa dan ditampilkan ulang.
     *
     * @return array<string, mixed>
     */
    private function ambilIsian(): array
    {
        $isian = [];

        foreach (['nama', 'tahun_lahir_awal', 'tahun_lahir_akhir', 'biaya_individu', 'awalan', 'urutan', 'aktif'] as $kunci) {
            $nilai         = $this->request->getPost($kunci);
            $isian[$kunci] = is_string($nilai) ? trim($nilai) : '';
        }

        $jenjang          = $this->request->getPost('jenjang');
        $isian['jenjang'] = is_array($jenjang) ? array_values(array_filter($jenjang, is_string(...))) : [];

        return $isian;
    }

    /**
     * @return array<string, mixed>
     */
    private function cariAtau404(int $id): array
    {
        $ku = model(KelompokUmurModel::class)->cariLengkap($id);

        if ($ku === null) {
            throw PageNotFoundException::forPageNotFound('Kelompok umur ini tidak ditemukan. Mungkin sudah dihapus.');
        }

        return $ku;
    }

    /**
     * @param array<string, mixed>|null $ku    Kelompok umur tersimpan (ubah) atau `null` (tambah).
     * @param array<string, mixed>      $isian
     * @param array<string, string>     $galat
     */
    private function tampilkanFormulir(?array $ku, array $isian, array $galat = []): string
    {
        // Awalan yang sudah dimiliki kelompok umur lain, sebagai petunjuk.
        $awalanLain = model(AwalanNomorDadaModel::class)->dipakaiKelompokUmurLain(
            array_map('strval', range(1, 9)),
            $ku === null ? null : (int) $ku['id'],
        );
        ksort($awalanLain);

        return view('admin/pengaturan/kelompok_umur/formulir', [
            'ku'         => $ku,
            'isian'      => $isian,
            'galat'      => $galat,
            'awalanLain' => $awalanLain,
        ]);
    }
}
