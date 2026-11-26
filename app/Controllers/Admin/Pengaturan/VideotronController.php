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
 * `unggahan/videotron/`, disimpan WEBP), latar bawaan dan sebutan Heat atau
 * Seri (K192), lalu isi dan lama tiap tampilan papan putar `/layar/putar`.
 * Kunci `Videotron.*` (`Config\Videotron`).
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
            'tampil'       => PapanVideotron::tampil(),
            'lama'         => (string) PapanVideotron::pengaturan()['lama'],
            'isi'          => PapanVideotron::pengaturan()['isi'],
            'latar_bawaan' => PapanVideotron::latarBawaan(),
            'sebutan_seri' => PapanVideotron::sebutanSeri(),
        ]);
    }

    /**
     * @return RedirectResponse|string
     */
    public function simpan()
    {
        $isi     = $this->request->getPost('isi');
        $latar   = (string) $this->request->getPost('latar_bawaan');
        $sebutan = (string) $this->request->getPost('sebutan_seri');
        $isian   = [
            'tampil'       => $this->request->getPost('tampil') === '1',
            'lama'         => trim((string) $this->request->getPost('lama')),
            'isi'          => PapanVideotron::rapikanIsi(is_array($isi) ? $isi : []),
            // Pilihan di luar daftar kembali ke bawaan, sama seperti saat dibaca.
            'latar_bawaan' => isset(PapanVideotron::LATAR_BAWAAN[$latar]) ? $latar : 'kuas',
            'sebutan_seri' => in_array($sebutan, PapanVideotron::SEBUTAN_SERI, true) ? $sebutan : PapanVideotron::SEBUTAN_SERI[0],
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
        setting('Videotron.latar_bawaan', $isian['latar_bawaan']);
        setting('Videotron.sebutan_seri', $isian['sebutan_seri']);
        cache()->delete(LayarController::KUNCI_TEMBOLOK);
        cache()->delete(LayarController::KUNCI_TEMBOLOK_DAFTAR);
        // Sebutan Heat atau Seri ada di data tampilan tetap yang ditembolok.
        cache()->deleteMatching(LayarController::AWALAN_TEMBOLOK_TAMPILAN . '*');

        return redirect()->to('admin/pengaturan/videotron')
            ->with('pesan_sukses', $isian['tampil'] ? 'Pengaturan videotron tersimpan. Daftar tampilan videotron bisa dibuka di /layar.' : 'Pengaturan videotron tersimpan. Videotron disembunyikan.');
    }

    /**
     * @param array{tampil: bool, lama: string, isi: list<string>, latar_bawaan: string, sebutan_seri: string} $isian
     * @param array<string, string> $galat
     */
    private function tampilkan(array $isian, array $galat = []): string
    {
        return view('admin/pengaturan/videotron', [
            'isian'          => $isian,
            'galat'          => $galat,
            'pilihanIsi'     => PapanVideotron::ISI,
            'pilihanLatar'   => PapanVideotron::LATAR_BAWAAN,
            'pilihanSebutan' => PapanVideotron::SEBUTAN_SERI,
            'latar'          => PapanVideotron::latar(),
        ]);
    }
}
