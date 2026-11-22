<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Controllers\JadwalController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\UnduhanJadwal;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Jadwal pertandingan di panel admin (F9-07): `admin/jadwal-pertandingan`.
 *
 * - `index`: semua babak dalam satu tabel (urut jadwal, yang belum dijadwalkan
 *   di akhir) dengan isian tanggal (pemilih tanggal Flowbite berbahasa
 *   Indonesia), jam mulai, dan lokasi; alat "Isi berurutan"; sakelar tampil
 *   untuk umum; unduhan PDF per hari.
 * - `simpan`: simpan semua baris sekaligus; bila ada yang salah tidak ada yang tersimpan.
 * - `nomorAcara`: beri nomor acara semua babak terjadwal berurutan mulai
 *   nomor awal (F11-07).
 * - `simpanBabak`: isian jadwal satu babak dari halaman hasil babak.
 * - `pdf`: jadwal satu hari, juga selama halaman publik belum dibuka.
 */
class JadwalPertandinganController extends BaseController
{
    public function index(): string
    {
        $jadwal = new JadwalPertandingan();

        return view('admin/jadwal_pertandingan/index', [
            'babak'  => $jadwal->semua(),
            'hari'   => array_keys($jadwal->perHari()),
            'tampil' => JadwalController::tampil(),
            'kini'   => new DateTimeImmutable('now'),
            'galat'  => (array) (session('galat_jadwal') ?? []),
            'isian'  => (array) (session('isian_jadwal') ?? []),
            // Nomor acara (F11-07).
            'acaraAwal' => (string) (session('isian_acara_awal') ?? setting('NomorAcara.awal') ?? 101),
        ]);
    }

    public function simpan(): RedirectResponse
    {
        $isian = $this->request->getPost('babak');
        $isian = is_array($isian) ? $isian : [];
        $galat = (new JadwalPertandingan())->simpanBanyak($isian);

        if ($galat !== []) {
            return redirect()->to(site_url('admin/jadwal-pertandingan') . '#jadwal-babak-' . array_key_first($galat))
                ->with('galat_jadwal', $galat)
                ->with('isian_jadwal', $isian)
                ->with('pesan_galat', 'Jadwal belum tersimpan: ' . count($galat) . ' babak isiannya belum benar. Perbaiki yang ditandai merah, lalu simpan lagi.');
        }

        return redirect()->to('admin/jadwal-pertandingan')->with('pesan_sukses', 'Jadwal pertandingan tersimpan.');
    }

    public function nomorAcara(): RedirectResponse
    {
        $jadwal    = new JadwalPertandingan();
        $terjadwal = count(array_filter($jadwal->semua(), static fn (array $b): bool => $b['tanggal'] !== null));
        $isian     = $this->request->getPost('awal');
        $periksa   = JadwalPertandingan::periksaAwal($isian, $terjadwal);

        if ($periksa['awal'] === null) {
            return redirect()->to(site_url('admin/jadwal-pertandingan') . '#nomor-acara')
                ->with('isian_acara_awal', is_string($isian) ? $isian : '')
                ->with('pesan_galat', 'Nomor acara belum diberi. ' . $periksa['galat']);
        }

        if ($terjadwal === 0) {
            return redirect()->to(site_url('admin/jadwal-pertandingan') . '#nomor-acara')
                ->with('pesan_galat', 'Belum ada babak yang punya tanggal. Isi tanggal babak dan simpan jadwal dulu, lalu beri nomor acara.');
        }

        $jumlah = $jadwal->beriNomorAcara($periksa['awal']);

        return redirect()->to(site_url('admin/jadwal-pertandingan') . '#nomor-acara')
            ->with('pesan_sukses', $jumlah . ' babak terjadwal diberi nomor acara ' . $periksa['awal'] . ' sampai ' . ($periksa['awal'] + $jumlah - 1) . '.');
    }

    public function simpanBabak(int $id): RedirectResponse
    {
        if ((new HasilLomba())->babak($id) === null) {
            throw PageNotFoundException::forPageNotFound('Babak tidak ditemukan.');
        }

        $isian = [
            'tanggal'   => $this->request->getPost('tanggal'),
            'jam_mulai' => $this->request->getPost('jam_mulai'),
            'lokasi'    => $this->request->getPost('lokasi'),
        ];

        // Isian nomor acara (F11-07); formulir lama tanpa kotak ini tidak mengubahnya.
        if ($this->request->getPost('nomor_acara') !== null) {
            $isian['nomor_acara'] = $this->request->getPost('nomor_acara');
        }
        $galat = (new JadwalPertandingan())->simpan($id, $isian);

        if ($galat !== []) {
            return redirect()->to(site_url('admin/hasil/babak/' . $id) . '#jadwal-babak')
                ->with('galat_jadwal_babak', $galat)
                ->with('isian_jadwal_babak', $isian)
                ->with('pesan_galat', 'Jadwal babak belum tersimpan. ' . implode(' ', $galat));
        }

        return redirect()->to(site_url('admin/hasil/babak/' . $id) . '#jadwal-babak')->with('pesan_sukses', 'Jadwal babak tersimpan.');
    }

    public function pdf(string $tanggal): ResponseInterface
    {
        $perHari = (new JadwalPertandingan())->perHari();

        if (! isset($perHari[$tanggal])) {
            throw PageNotFoundException::forPageNotFound('Belum ada babak di tanggal ini.');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanJadwal::namaBerkas($tanggal) . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody((new UnduhanJadwal($tanggal, $perHari[$tanggal]))->pdf());
    }
}
