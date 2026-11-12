<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Models\JadwalModel;
use App\Models\TautanModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan jadwal: `/admin/pengaturan/jadwal` (pola halaman daftar data,
 * rencana Tahap 2 "Rute").
 *
 * Jadwal berisi judul, keterangan, waktu (boleh kosong), tempat, tautan
 * (alamat web `http`/`https`), urutan, dan tampil. Halaman depan (Tahap 3)
 * hanya menampilkan jadwal yang `tampil`.
 */
class JadwalController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/jadwal';

    private JadwalModel $jadwal;

    public function __construct()
    {
        $this->jadwal = model(JadwalModel::class);
    }

    public function index(): string
    {
        return view('admin/pengaturan/jadwal/index', [
            'daftar' => $this->jadwal->daftar(),
        ]);
    }

    public function tambah(): string
    {
        return $this->tampilkanFormulir(null, [
            'judul'      => '',
            'keterangan' => '',
            'waktu'      => '',
            'tempat'     => '',
            'tautan'     => '',
            'urutan'     => (string) $this->jadwal->urutanBerikutnya(),
            'tampil'     => '1',
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        $isian = $this->isianFormulir();
        [$data, $galat] = $this->periksa($isian, null);

        if ($galat !== []) {
            return $this->tampilkanFormulir(null, $isian, $galat);
        }

        $this->jadwal->insert($data);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Jadwal "' . $data['judul'] . '" tersimpan.');
    }

    public function ubah(int $id): ResponseInterface|string
    {
        $jadwal = $this->jadwal->find($id);

        if ($jadwal === null) {
            return $this->tidakDitemukan();
        }

        return $this->tampilkanFormulir($jadwal, [
            'judul'      => (string) $jadwal['judul'],
            'keterangan' => (string) $jadwal['keterangan'],
            'waktu'      => JadwalModel::waktuFormulir($jadwal['waktu']),
            'tempat'     => (string) $jadwal['tempat'],
            'tautan'     => (string) $jadwal['tautan'],
            'urutan'     => (string) $jadwal['urutan'],
            'tampil'     => (int) $jadwal['tampil'] === 1 ? '1' : '0',
        ]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $jadwal = $this->jadwal->find($id);

        if ($jadwal === null) {
            return $this->tidakDitemukan();
        }

        $isian = $this->isianFormulir();
        [$data, $galat] = $this->periksa($isian, $id);

        if ($galat !== []) {
            return $this->tampilkanFormulir($jadwal, $isian, $galat);
        }

        $this->jadwal->update($id, $data);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Perubahan jadwal "' . $data['judul'] . '" tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $jadwal = $this->jadwal->find($id);

        if ($jadwal === null) {
            return $this->tidakDitemukan();
        }

        $this->jadwal->delete($id);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Jadwal "' . $jadwal['judul'] . '" sudah dihapus.');
    }

    /**
     * Isian formulir apa adanya (dirapikan spasinya), untuk diperiksa dan
     * ditampilkan ulang bila ada yang salah.
     *
     * @return array<string, string>
     */
    private function isianFormulir(): array
    {
        $isian = [];

        foreach (['judul', 'keterangan', 'waktu', 'tempat', 'tautan', 'urutan'] as $kotak) {
            $nilai         = $this->request->getPost($kotak);
            $isian[$kotak] = is_string($nilai) ? trim($nilai) : '';
        }

        $isian['tampil'] = $this->request->getPost('tampil') === '1' ? '1' : '0';

        return $isian;
    }

    /**
     * Periksa isian. Hasilnya data siap simpan dan pesan galat per kotak
     * (kosong bila semua benar).
     *
     * @param array<string, string> $isian
     * @param int|null              $id    Jadwal yang sedang diubah, `null` untuk jadwal baru.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    private function periksa(array $isian, ?int $id): array
    {
        $galat = [];

        if ($isian['judul'] === '') {
            $galat['judul'] = 'Judul belum diisi. Tulis nama kegiatannya, misalnya Technical Meeting.';
        } elseif (mb_strlen($isian['judul']) > JadwalModel::PANJANG['judul']) {
            $galat['judul'] = 'Judul terlalu panjang. Persingkat menjadi paling banyak ' . JadwalModel::PANJANG['judul'] . ' huruf.';
        }

        if (mb_strlen($isian['keterangan']) > JadwalModel::PANJANG['keterangan']) {
            $galat['keterangan'] = 'Keterangan terlalu panjang. Persingkat menjadi paling banyak ' . JadwalModel::PANJANG['keterangan'] . ' huruf.';
        }

        $waktu = JadwalModel::bacaWaktu($isian['waktu']);

        if ($waktu === null) {
            $galat['waktu'] = 'Waktu belum benar. Pilih tanggal dan jam di kotak waktu, atau kosongkan bila waktunya belum pasti.';
        }

        if (mb_strlen($isian['tempat']) > JadwalModel::PANJANG['tempat']) {
            $galat['tempat'] = 'Tempat terlalu panjang. Persingkat menjadi paling banyak ' . JadwalModel::PANJANG['tempat'] . ' huruf.';
        }

        $tautan = null;

        if ($isian['tautan'] !== '') {
            $tautan = TautanModel::rapikanAlamatWeb($isian['tautan']);

            if ($tautan === null) {
                $galat['tautan'] = 'Tautan belum benar. Salin alamat lengkap dari peramban, misalnya https://zoom.us/j/1234567890, atau kosongkan bila tidak ada.';
            }
        }

        $urutan = $this->bacaUrutan($isian['urutan'], $id);

        if ($urutan === null) {
            $galat['urutan'] = 'Urutan belum benar. Tulis angka 0 sampai ' . JadwalModel::URUTAN_MAKSIMAL . '; angka kecil tampil lebih dulu.';
        }

        $data = [
            'judul'      => $isian['judul'],
            'keterangan' => $isian['keterangan'] === '' ? null : $isian['keterangan'],
            'waktu'      => $waktu === '' ? null : $waktu,
            'tempat'     => $isian['tempat'] === '' ? null : $isian['tempat'],
            'tautan'     => $tautan,
            'urutan'     => $urutan,
            'tampil'     => $isian['tampil'] === '1' ? 1 : 0,
        ];

        return [$data, $galat];
    }

    /**
     * Urutan dari isian: angka bulat 0–9999. Kosong berarti paling akhir.
     * `null` bila tidak sah.
     */
    private function bacaUrutan(string $isian, ?int $id): ?int
    {
        if ($isian === '') {
            return $this->jadwal->urutanBerikutnya($id);
        }

        if (preg_match('/^\d{1,4}$/', $isian) !== 1) {
            return null;
        }

        return (int) $isian;
    }

    /**
     * @param array<string, mixed>|null $jadwal Jadwal yang diubah, `null` untuk jadwal baru.
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     */
    private function tampilkanFormulir(?array $jadwal, array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/jadwal/formulir', [
            'jadwal' => $jadwal,
            'isian'  => $isian,
            'galat'  => $galat,
        ]);
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Jadwal itu tidak ditemukan, mungkin sudah dihapus. Pilih jadwal dari daftar di bawah.',
        );
    }
}
