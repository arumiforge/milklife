<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\LembarJuri;
use App\Libraries\Hasil\SeriLintasan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Lembar juri di panel admin (F9-09, K140 butir 7d): `admin/juri`.
 *
 * - `index`: babak per hari jadwal, hari ini di atas.
 * - `babak`: pilih seri, lalu satu baris besar per peserta (BIB, nama,
 *   lintasan) dengan isian prestasi berpapan angka ponsel.
 * - `simpan`: satu baris; dari skrip `aset/js/lembar-juri.js` dijawab JSON
 *   (simpan otomatis per baris), tanpa skrip dialihkan kembali ke barisnya.
 *
 * Peringkat dan Terbitkan tetap di halaman hasil (F8-04).
 */
class JuriController extends BaseController
{
    public function index(): string
    {
        $daftar = (new LembarJuri())->daftar(new DateTimeImmutable());

        return view('admin/juri/index', [
            'hari'    => $daftar['hari'],
            'hariIni' => $daftar['hari_ini'],
        ]);
    }

    public function babak(int $id): string
    {
        $seri  = new SeriLintasan();
        $babak = $seri->babak($id) ?? throw PageNotFoundException::forPageNotFound('Babak tidak ditemukan.');

        $perSeri = SeriLintasan::perSeri($seri->peserta($babak));
        $pilih   = (int) $this->request->getGet('seri');
        $pilih   = isset($perSeri[$pilih]) ? $pilih : (int) (array_key_first($perSeri) ?? 0);

        return view('admin/juri/babak', [
            'babak'   => $babak,
            'perSeri' => $perSeri,
            'seri'    => $pilih,
        ]);
    }

    public function simpan(int $babakId, int $hasilId): RedirectResponse|ResponseInterface
    {
        $isian = [];

        foreach (['prestasi', 'percobaan', 'komponen', 'keterangan', 'pasal', 'versi'] as $kotak) {
            $isian[$kotak] = $this->request->getPost($kotak);
        }

        $jawaban = (new LembarJuri())->simpan($babakId, $hasilId, $isian);

        if ($this->request->isAJAX() || str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            // Selalu 200: keadaan ada di `status`, supaya isian salah tidak menjadi galat di konsol peramban.
            return $this->response->setJSON($jawaban + ['csrf' => csrf_hash()]);
        }

        $seri    = (string) $this->request->getPost('seri');
        $kembali = site_url('admin/juri/babak/' . $babakId) . (preg_match('/^\d{1,2}$/D', $seri) === 1 ? '?seri=' . $seri : '') . '#baris-' . $hasilId;

        return redirect()->to($kembali)->with($jawaban['status'] === 'tersimpan' ? 'pesan_sukses' : 'pesan_galat', $jawaban['status'] === 'tersimpan' ? 'Prestasi tersimpan.' : $jawaban['pesan']);
    }
}
