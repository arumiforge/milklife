<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\Prestasi;
use App\Libraries\Hasil\TabelSkor;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Unggahan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Files\UploadedFile;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;
use Throwable;

/**
 * Pengaturan > Tabel skor (F9-10, K140 butir 7b): tabel prestasi → poin untuk
 * nomor berjenis hasil poin multi-lomba.
 *
 * - `index`: daftar tabel, jumlah baris per komponen dan kategori, nomor yang
 *   memakainya; tombol pasang tabel bawaan panitia; tambah tabel baru.
 * - `baris`: baris satu komponen dan kategori; ubah, tambah, dan hapus baris;
 *   ganti semua baris dari Excel (kolom A prestasi, kolom B poin); unduh Excel.
 *
 * Setiap perubahan baris langsung menghitung ulang poin dan peringkat hasil
 * nomor yang memakai tabel itu.
 */
class TabelSkorController extends BaseController
{
    public const ALAMAT = 'admin/pengaturan/tabel-skor';

    /**
     * Ukuran berkas Excel paling besar (byte).
     */
    public const UKURAN_EXCEL = 2 * 1024 * 1024;

    private DataTabelSkor $data;

    public function __construct()
    {
        $this->data = new DataTabelSkor();
    }

    public function index(): string
    {
        $daftar = $this->data->daftar();
        $ada    = array_column($daftar, 'kode');
        $belum  = [];

        foreach (DataTabelSkor::berkasBawaan() as $b) {
            if (! in_array($b['kode'], $ada, true)) {
                $belum[] = (string) $b['nama'];
            }
        }

        return view('admin/pengaturan/tabel_skor/index', [
            'daftar'      => $daftar,
            'belumDipasang' => $belum,
            'galat'       => (array) (session('galat_tabel') ?? []),
            'isian'       => (array) (session('isian_tabel') ?? []),
        ]);
    }

    public function pasang(): RedirectResponse
    {
        try {
            $hasil = $this->data->pasangBawaan();
        } catch (RuntimeException $e) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Tabel bawaan gagal dipasang: ' . $e->getMessage());
        }

        if ($hasil['dipasang'] === []) {
            return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Semua tabel bawaan sudah terpasang.');
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Tabel bawaan dipasang: ' . implode(', ', $hasil['dipasang']) . '. Pilih tabelnya di Pengaturan > Nomor lomba untuk nomor Trilomba.');
    }

    public function buat(): RedirectResponse
    {
        $nama     = (string) $this->request->getPost('nama');
        $namaK    = (array) $this->request->getPost('komponen_nama');
        $jenisK   = (array) $this->request->getPost('komponen_jenis');
        $komponen = [];

        foreach ($namaK as $i => $n) {
            $komponen[] = ['nama' => is_string($n) ? $n : '', 'jenis' => is_string($jenisK[$i] ?? null) ? $jenisK[$i] : ''];
        }

        $hasil = $this->data->buatTabel($nama, $komponen);

        if (isset($hasil['galat'])) {
            return redirect()->to(site_url(self::ALAMAT) . '#tambah-tabel')
                ->with('galat_tabel', $hasil['galat'])
                ->with('isian_tabel', ['nama' => $nama, 'komponen' => $komponen])
                ->with('pesan_galat', 'Tabel belum ditambahkan. Periksa isian yang ditandai.');
        }

        return redirect()->to(site_url(self::ALAMAT) . '#tabel-' . $hasil['id'])
            ->with('pesan_sukses', 'Tabel ' . trim($nama) . ' ditambahkan. Isi barisnya lewat Excel atau satu per satu di tiap komponen.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $tabel = $this->cariTabel($id);

        if (! $this->data->hapusTabel($id)) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Tabel ' . $tabel['nama'] . ' masih dipakai nomor lomba, jadi tidak bisa dihapus. Ganti tabel skor nomor itu dulu di Pengaturan > Nomor lomba.');
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Tabel ' . $tabel['nama'] . ' dihapus.');
    }

    public function baris(int $id, string $komponen, string $kategori): string
    {
        [$tabel, $k] = $this->cari($id, $komponen);

        return view('admin/pengaturan/tabel_skor/baris', [
            'tabel'    => $tabel,
            'komponen' => $k,
            'kategori' => $kategori,
            'baris'    => $this->data->baris($id, $komponen, $kategori, $k['jenis']),
            'galat'    => (array) (session('galat_baris') ?? []),
            'galatImpor' => (array) (session('galat_impor') ?? []),
            'isian'    => (array) (session('isian_baris') ?? []),
        ]);
    }

    public function simpanBaris(int $id, string $komponen, string $kategori): RedirectResponse
    {
        $this->cari($id, $komponen);
        $teks    = (string) $this->request->getPost('prestasi');
        $poin    = (string) $this->request->getPost('poin');
        $galat   = $this->data->simpanBaris($id, $komponen, $kategori, $teks, $poin);
        $kembali = site_url(self::ALAMAT . '/' . $id . '/' . $komponen . '/' . $kategori);

        if ($galat !== []) {
            return redirect()->to($kembali . '#ubah-baris')
                ->with('galat_baris', $galat)
                ->with('isian_baris', ['prestasi' => $teks, 'poin' => $poin])
                ->with('pesan_galat', 'Baris belum tersimpan. ' . implode(' ', $galat));
        }

        return redirect()->to($kembali)->with('pesan_sukses', 'Baris ' . trim($teks) . ' = ' . (int) $poin . ' poin tersimpan. Poin hasil lomba yang memakai tabel ini dihitung ulang.');
    }

    public function hapusBaris(int $id, string $komponen, string $kategori): RedirectResponse
    {
        [, $k]    = $this->cari($id, $komponen);
        $prestasi = (string) $this->request->getPost('prestasi');
        $kembali  = site_url(self::ALAMAT . '/' . $id . '/' . $komponen . '/' . $kategori);

        if (! ctype_digit($prestasi) || ! $this->data->hapusBaris($id, $komponen, $kategori, (int) $prestasi)) {
            return redirect()->to($kembali)->with('pesan_galat', 'Baris itu tidak ditemukan, mungkin sudah dihapus.');
        }

        return redirect()->to($kembali)->with('pesan_sukses', 'Baris ' . Prestasi::tulis((int) $prestasi, $k['jenis']) . ' dihapus. Poin hasil lomba yang memakai tabel ini dihitung ulang.');
    }

    public function impor(int $id, string $komponen, string $kategori): RedirectResponse
    {
        [, $k]   = $this->cari($id, $komponen);
        $kembali = site_url(self::ALAMAT . '/' . $id . '/' . $komponen . '/' . $kategori);
        $berkas  = $this->request->getFile('berkas');

        // Berkas sementara dari PHP; hanya dibaca, tidak disimpan.
        if (! $berkas instanceof UploadedFile || ! Unggahan::dipilih($berkas) || $berkas->getError() !== UPLOAD_ERR_OK || ! is_file($berkas->getTempName())) {
            return redirect()->to($kembali . '#impor')->with('pesan_galat', 'Berkas Excel belum dipilih. Pilih berkas .xlsx berisi kolom A prestasi dan kolom B poin.');
        }

        if (! in_array(strtolower($berkas->getClientExtension()), ['xlsx', 'xls', 'csv'], true) || $berkas->getSize() > self::UKURAN_EXCEL) {
            return redirect()->to($kembali . '#impor')->with('pesan_galat', 'Berkas harus Excel (.xlsx, .xls) atau .csv, paling besar 2 MB.');
        }

        try {
            $sel = IOFactory::load($berkas->getTempName())->getActiveSheet()->toArray(null, true, false, false);
        } catch (Throwable) {
            return redirect()->to($kembali . '#impor')->with('pesan_galat', 'Berkas tidak bisa dibaca sebagai Excel. Simpan ulang sebagai .xlsx lalu coba lagi.');
        }

        $baca = TabelSkor::bacaSel(array_values(array_map(static fn (array $b): array => array_values($b), $sel)), $k['jenis']);

        if ($baca['galat'] !== []) {
            return redirect()->to($kembali . '#impor')
                ->with('galat_impor', array_slice($baca['galat'], 0, 20))
                ->with('pesan_galat', 'Tabel tidak diganti: ' . count($baca['galat']) . ' baris Excel belum benar. Perbaiki lalu impor lagi.');
        }

        $this->data->gantiBaris($id, $komponen, $kategori, $baca['baris']);

        return redirect()->to($kembali)->with('pesan_sukses', count($baca['baris']) . ' baris dari Excel menggantikan tabel ' . $k['nama'] . ' ' . $kategori . '. Poin hasil lomba yang memakai tabel ini dihitung ulang.');
    }

    public function excel(int $id, string $komponen, string $kategori): ResponseInterface
    {
        [$tabel, $k] = $this->cari($id, $komponen);

        $ekspor = (new EksporExcel(mb_substr($k['nama'] . ' ' . $kategori, 0, 31), [
            'prestasi' => 'Prestasi (' . ($k['jenis'] === JenisHasil::WAKTU ? 'detik' : 'meter') . ')',
            'poin'     => ['judul' => 'Poin', 'jenis' => 'angka'],
        ]))->tambahBaris(array_map(static fn (array $b): array => ['prestasi' => Prestasi::tulis($b[0], $k['jenis']), 'poin' => $b[1]], $this->data->baris($id, $komponen, $kategori, $k['jenis'])));

        return $ekspor->unduh('Tabel_skor_' . TabelSkor::kodeDariNama($tabel['nama'] . ' ' . $k['nama'] . ' ' . $kategori) . '.xlsx');
    }

    /**
     * @return array{id: int, kode: string, nama: string, keterangan: string, komponen: list<array{kode: string, nama: string, jenis: string}>}
     */
    private function cariTabel(int $id): array
    {
        return $this->data->tabel($id) ?? throw PageNotFoundException::forPageNotFound('Tabel skor tidak ditemukan.');
    }

    /**
     * @return array{0: array<string, mixed>, 1: array{kode: string, nama: string, jenis: string}}
     */
    private function cari(int $id, string $komponen): array
    {
        $tabel = $this->cariTabel($id);

        foreach ($tabel['komponen'] as $k) {
            if ($k['kode'] === $komponen) {
                return [$tabel, $k];
            }
        }

        throw PageNotFoundException::forPageNotFound('Komponen tabel skor tidak ditemukan.');
    }
}
