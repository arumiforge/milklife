<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Controllers\LayarController;
use App\Libraries\Hasil\PapanVideotron;
use App\Libraries\Unggahan;
use CodeIgniter\HTTP\RedirectResponse;
use RuntimeException;

/**
 * Pengaturan > Videotron (F9-13, F11-09): sakelar semua alamat videotron
 * `/layar` (bawaan mati), gambar latar unggahan (K173, folder
 * `unggahan/videotron/`, disimpan WEBP), lalu isi dan lama tiap tampilan
 * papan putar `/layar/putar`. Kunci `Videotron.*` (`Config\Videotron`).
 */
class VideotronController extends BaseController
{
    public const FOLDER_UNGGAHAN = 'videotron';

    /**
     * Sisi terpanjang gambar latar (layar 4K).
     */
    public const SISI_LATAR = 3840;

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

        $unggahan = new Unggahan();
        $berkas   = $this->request->getFile('latar');
        $adaBaru  = Unggahan::dipilih($berkas);

        if ($adaBaru) {
            $pesan = $unggahan->periksaGambar($berkas);

            if ($pesan !== null) {
                $galat['latar'] = 'Gambar latar: ' . $pesan;
            }
        }

        if ($galat !== []) {
            return $this->tampilkan($isian, $galat);
        }

        // Gambar baru disimpan dulu; berkas lama dibuang setelah pengaturan berubah.
        // Gambar baru mengalahkan centang "Hapus gambar latar".
        $lama = (string) setting('Videotron.latar');

        if ($adaBaru) {
            try {
                setting('Videotron.latar', $unggahan->simpanGambarWebp($berkas, self::FOLDER_UNGGAHAN, self::SISI_LATAR));
            } catch (RuntimeException $e) {
                return $this->tampilkan($isian, ['latar' => 'Gambar latar: ' . $e->getMessage()]);
            }
        } elseif ($this->request->getPost('hapus_latar') === '1') {
            setting('Videotron.latar', '');
        }

        if ((string) setting('Videotron.latar') !== $lama) {
            $unggahan->hapus($lama);
        }

        setting('Videotron.tampil', $isian['tampil']);
        setting('Videotron.lama', (int) $isian['lama']);
        setting('Videotron.isi', implode(',', $isian['isi']));
        cache()->delete(LayarController::KUNCI_TEMBOLOK);

        return redirect()->to('admin/pengaturan/videotron')
            ->with('pesan_sukses', $isian['tampil'] ? 'Pengaturan videotron tersimpan. Daftar tampilan videotron bisa dibuka di /layar.' : 'Pengaturan videotron tersimpan. Videotron disembunyikan.');
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
            'latar'      => PapanVideotron::latar(),
        ]);
    }
}
