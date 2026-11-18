<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HariLomba\DaftarUlang;
use App\Libraries\HariLomba\KodeDaftarUlang;
use App\Libraries\SesiAdmin;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Daftar ulang hari lomba (F8-03, K124): `admin/daftar-ulang`.
 *
 * - `index`: pemindai kode QR bukti pendaftaran (`aset/js/daftar-ulang.js`,
 *   fitur `BarcodeDetector` bawaan peramban) dan isian ID. `?id=` boleh
 *   berisi ID pendaftaran atau alamat bukti lengkap hasil pindai. ID lama yang
 *   sudah digabung (K109) menampilkan pendaftaran gabungannya.
 * - `hadir`: tandai hadir atlet yang dipilih (`atlet[]`) atau semua atlet
 *   (`semua=1`). Hanya pendaftaran Lunas.
 * - `batalHadir`: hapus tanda hadir satu atlet (`atlet`), untuk salah tekan.
 *
 * Setelah tindakan, admin kembali ke pendaftaran yang sama dengan pesan.
 */
class DaftarUlangController extends BaseController
{
    private const ALAMAT = 'admin/daftar-ulang';

    public function index(): string
    {
        $masukan = trim((string) $this->request->getGet('id'));
        $hasil   = null;
        $galat   = null;

        if ($masukan !== '') {
            $id = KodeDaftarUlang::idDari($masukan);

            if ($id === null) {
                $galat = 'Kode QR atau ID pendaftaran tidak dikenali. Pindai kode QR di bukti pendaftaran, atau ketik ID yang diawali MAC, misalnya MAC2A0000126.';
            } else {
                $hasil = (new DaftarUlang())->cari($id);
                $galat = $hasil === null ? 'Pendaftaran ' . $id . ' tidak ditemukan. Periksa lagi ID-nya, atau cari sekolahnya di menu Pendaftaran.' : null;
            }
        }

        return view('admin/hari_lomba/daftar_ulang', [
            'masukan' => $hasil === null ? $masukan : '',
            'hasil'   => $hasil,
            'galat'   => $galat,
        ]);
    }

    public function hadir(int $id): RedirectResponse
    {
        $pendaftaran = $this->pendaftaran($id);

        if ($pendaftaran === null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Pendaftaran tidak ditemukan. Mungkin sudah dihapus atau digabung; pindai lagi kode QR-nya.');
        }

        $kembali = $this->kembali($pendaftaran['id_pendaftaran']);

        if ($pendaftaran['status'] !== 'lunas') {
            return redirect()->to($kembali)->with('pesan_galat', 'Pendaftaran ' . $pendaftaran['id_pendaftaran'] . ' belum Lunas, jadi atletnya belum bisa ditandai hadir. Periksa pembayarannya dulu di menu Pendaftaran.');
        }

        $semua = $this->request->getPost('semua') === '1';
        $pilih = array_values(array_filter(array_map('intval', (array) ($this->request->getPost('atlet') ?? [])), static fn (int $i): bool => $i > 0));

        if (! $semua && $pilih === []) {
            return redirect()->to($kembali)->with('pesan_galat', 'Belum ada atlet yang dipilih.');
        }

        $jumlah = (new DaftarUlang())->tandaiHadir($id, $semua ? null : $pilih, $this->namaAdmin());

        return redirect()->to($kembali)->with('pesan_sukses', $jumlah === 0
            ? 'Atlet yang dipilih sudah tercatat hadir sebelumnya.'
            : $jumlah . ' atlet ditandai hadir.');
    }

    public function batalHadir(int $id): RedirectResponse
    {
        $pendaftaran = $this->pendaftaran($id);

        if ($pendaftaran === null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Pendaftaran tidak ditemukan. Mungkin sudah dihapus atau digabung; pindai lagi kode QR-nya.');
        }

        $berhasil = (new DaftarUlang())->batalHadir($id, (int) $this->request->getPost('atlet'));

        return redirect()->to($this->kembali($pendaftaran['id_pendaftaran']))->with(
            $berhasil ? 'pesan_sukses' : 'pesan_galat',
            $berhasil ? 'Tanda hadir dibatalkan.' : 'Atlet itu belum tercatat hadir.',
        );
    }

    /**
     * @return array{id_pendaftaran: string, status: string}|null
     */
    private function pendaftaran(int $id): ?array
    {
        $baris = db_connect()->table('pendaftaran')->select('id_pendaftaran, status')->where('id', $id)->get()->getRowArray();

        return is_array($baris) ? ['id_pendaftaran' => (string) $baris['id_pendaftaran'], 'status' => (string) $baris['status']] : null;
    }

    private function kembali(string $idPendaftaran): string
    {
        return site_url(self::ALAMAT) . '?id=' . rawurlencode($idPendaftaran) . '#daftar-atlet';
    }

    private function namaAdmin(): string
    {
        return (string) (session(SesiAdmin::KUNCI_NAMA) ?? 'Admin');
    }
}
