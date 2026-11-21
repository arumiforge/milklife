<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Controllers\LayarController;
use App\Libraries\Hasil\PapanVideotron;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Pengaturan > Videotron (F9-13): sakelar papan `/layar` (bawaan mati), isi
 * yang diputar, dan lama tiap tampilan. Kunci `Videotron.*` (`Config\Videotron`).
 */
class VideotronController extends BaseController
{
    public function index(): string
    {
        return $this->tampilkan([
            'tampil' => PapanVideotron::tampil(),
            'lama'   => (string) PapanVideotron::pengaturan()['lama'],
            'isi'    => PapanVideotron::pengaturan()['isi'],
        ]);
    }

    /**
     * @return RedirectResponse|string
     */
    public function simpan()
    {
        $isi   = $this->request->getPost('isi');
        $isian = [
            'tampil' => $this->request->getPost('tampil') === '1',
            'lama'   => trim((string) $this->request->getPost('lama')),
            'isi'    => PapanVideotron::rapikanIsi(is_array($isi) ? $isi : []),
        ];
        $galat = [];

        if (preg_match('/^\d{1,3}$/', $isian['lama']) !== 1 || (int) $isian['lama'] < PapanVideotron::LAMA_MIN || (int) $isian['lama'] > PapanVideotron::LAMA_MAKS) {
            $galat['lama'] = 'Lama tiap tampilan harus angka bulat ' . PapanVideotron::LAMA_MIN . ' sampai ' . PapanVideotron::LAMA_MAKS . ' detik, misalnya 12.';
        }

        if ($isian['isi'] === []) {
            $galat['isi'] = 'Pilih paling sedikit satu isi yang tampil, misalnya Hasil nomor yang baru terbit.';
        }

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat);
        }

        setting('Videotron.tampil', $isian['tampil']);
        setting('Videotron.lama', (int) $isian['lama']);
        setting('Videotron.isi', implode(',', $isian['isi']));
        cache()->delete(LayarController::KUNCI_TEMBOLOK);

        return redirect()->to('admin/pengaturan/videotron')
            ->with('pesan_sukses', $isian['tampil'] ? 'Pengaturan videotron tersimpan. Papan hasil tampil di /layar.' : 'Pengaturan videotron tersimpan. Papan hasil disembunyikan.');
    }

    /**
     * @param array{tampil: bool, lama: string, isi: list<string>} $isian
     * @param array<string, string>                                $galat
     */
    private function tampilkan(array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/videotron', [
            'isian'      => $isian,
            'galat'      => $galat,
            'pilihanIsi' => PapanVideotron::ISI,
        ]);
    }
}
