<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Controllers\GaleriController as GaleriPublik;
use App\Libraries\Unggahan;
use App\Models\GaleriModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Pengaturan > Galeri: `/admin/pengaturan/galeri` (F9-06), untuk tabel `galeri`.
 *
 * Pola halaman daftar data (rencana Tahap 2): daftar, tambah, ubah, hapus
 * (dengan konfirmasi). Ditambah:
 *
 * - `pindah`: tombol Naik/Turun menukar urutan dengan tetangganya.
 * - `aktif`: tombol Sembunyikan/Tampilkan tanpa membuka formulir.
 * - `tampil`: sakelar halaman `/galeri` dan butir menunya (`Galeri.tampil`,
 *   bawaan mati).
 *
 * Sampul diunggah lewat `Unggahan::simpanGambarWebp()` (WebP,
 * `GaleriModel::SISI_SAMPUL` piksel). Sampul lama dihapus setelah yang baru
 * tersimpan, dan ikut dihapus bersama albumnya.
 */
class GaleriController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/galeri';

    private GaleriModel $galeri;

    public function __construct()
    {
        $this->galeri = model(GaleriModel::class);
    }

    public function index(): string
    {
        return view('admin/pengaturan/galeri/index', [
            'album'    => $this->galeri->semua(),
            'tampil'   => GaleriPublik::tampil(),
            'unggahan' => new Unggahan(),
        ]);
    }

    public function tambah(): ResponseInterface|string
    {
        if ($this->galeri->countAllResults() >= GaleriModel::BATAS) {
            return $this->penuh();
        }

        return $this->tampilkanFormulir(null, [
            'judul'      => '',
            'keterangan' => '',
            'tautan'     => '',
            'urutan'     => (string) $this->galeri->urutanBerikutnya(),
            'aktif'      => '1',
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        if ($this->galeri->countAllResults() >= GaleriModel::BATAS) {
            return $this->penuh();
        }

        $isian          = $this->isianFormulir();
        [$data, $galat] = $this->galeri->periksa($isian);
        $sampul         = $this->periksaSampul($galat);

        if ($galat !== []) {
            return $this->tampilkanFormulir(null, $isian, $galat, $sampul !== false);
        }

        try {
            $data['sampul'] = $sampul === false ? null : $this->simpanSampul();
        } catch (RuntimeException $e) {
            return $this->tampilkanFormulir(null, $isian, ['sampul' => $e->getMessage()]);
        }

        $id = (int) $this->galeri->insert($data);

        return redirect()->to(self::ALAMAT . '#album-' . $id)->with('pesan_sukses', $this->pesanTersimpan('Album "' . $data['judul'] . '" tersimpan.', $data));
    }

    public function ubah(int $id): ResponseInterface|string
    {
        $album = $this->galeri->find($id);

        if (! is_array($album)) {
            return $this->tidakDitemukan();
        }

        return $this->tampilkanFormulir($album, [
            'judul'      => (string) $album['judul'],
            'keterangan' => (string) $album['keterangan'],
            'tautan'     => (string) $album['tautan'],
            'urutan'     => (string) $album['urutan'],
            'aktif'      => (int) $album['aktif'] === 1 ? '1' : '',
        ]);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $album = $this->galeri->find($id);

        if (! is_array($album)) {
            return $this->tidakDitemukan();
        }

        $isian          = $this->isianFormulir();
        [$data, $galat] = $this->galeri->periksa($isian, $id);
        $sampul         = $this->periksaSampul($galat);

        if ($galat !== []) {
            return $this->tampilkanFormulir($album, $isian, $galat, $sampul !== false);
        }

        $lama     = is_string($album['sampul']) ? $album['sampul'] : null;
        $unggahan = new Unggahan();

        if ($sampul !== false) {
            try {
                $data['sampul'] = $this->simpanSampul();
            } catch (RuntimeException $e) {
                return $this->tampilkanFormulir($album, $isian, ['sampul' => $e->getMessage()]);
            }
        } elseif ($this->request->getPost('hapus_sampul') === '1') {
            $data['sampul'] = null;
        }

        $this->galeri->update($id, $data);

        // Berkas lama dibuang setelah perubahan tersimpan.
        if (array_key_exists('sampul', $data) && $lama !== null && $lama !== $data['sampul']) {
            $unggahan->hapus($lama);
        }

        return redirect()->to(self::ALAMAT . '#album-' . $id)->with('pesan_sukses', $this->pesanTersimpan('Perubahan album "' . $data['judul'] . '" tersimpan.', $data));
    }

    public function hapus(int $id): RedirectResponse
    {
        $album = $this->galeri->find($id);

        if (! is_array($album)) {
            return $this->tidakDitemukan();
        }

        $this->galeri->delete($id);
        (new Unggahan())->hapus(is_string($album['sampul']) ? $album['sampul'] : null);

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Album "' . $album['judul'] . '" sudah dihapus beserta sampulnya.');
    }

    /**
     * Naik atau turun satu tempat.
     */
    public function pindah(int $id, string $arah): RedirectResponse
    {
        $album = $this->galeri->find($id);

        if (! is_array($album)) {
            return $this->tidakDitemukan();
        }

        if (! $this->galeri->pindah($id, $arah)) {
            return redirect()->to(self::ALAMAT . '#album-' . $id)->with(
                'pesan_galat',
                $arah === 'naik' ? 'Album ini sudah paling atas.' : 'Album ini sudah paling bawah.',
            );
        }

        return redirect()->to(self::ALAMAT . '#album-' . $id)->with('pesan_sukses', 'Urutan album "' . $album['judul'] . '" diubah.');
    }

    /**
     * Tampilkan atau sembunyikan dari halaman Galeri.
     */
    public function aktif(int $id): RedirectResponse
    {
        $album = $this->galeri->find($id);

        if (! is_array($album)) {
            return $this->tidakDitemukan();
        }

        $aktif = (int) $album['aktif'] === 1 ? 0 : 1;
        $this->galeri->update($id, ['aktif' => $aktif]);

        return redirect()->to(self::ALAMAT . '#album-' . $id)->with(
            'pesan_sukses',
            'Album "' . $album['judul'] . '" ' . ($aktif === 1 ? 'tampil lagi di halaman Galeri.' : 'disembunyikan dari halaman Galeri.'),
        );
    }

    /**
     * Sakelar halaman Galeri publik dan butir menunya.
     */
    public function tampil(): RedirectResponse
    {
        // Kotak centang: tidak terkirim sama sekali bila tidak dicentang.
        $tampil = $this->request->getPost('tampil_galeri') === '1';

        setting('Galeri.tampil', $tampil);

        return redirect()->to(self::ALAMAT . '#sakelar-galeri')
            ->with('pesan_sukses', $tampil ? 'Halaman Galeri sekarang tampil untuk umum.' : 'Halaman Galeri disembunyikan.');
    }

    /**
     * Periksa sampul bila dipilih. `false` bila tidak dipilih, `true` bila sah;
     * pesan galat ditambahkan ke `$galat`.
     *
     * @param array<string, string> $galat
     */
    private function periksaSampul(array &$galat): bool
    {
        $berkas = $this->request->getFile('sampul');

        if (! Unggahan::dipilih($berkas)) {
            return false;
        }

        $pesan = (new Unggahan())->periksaGambar($berkas);

        if ($pesan !== null) {
            $galat['sampul'] = $pesan;
        }

        return true;
    }

    /**
     * @throws RuntimeException
     */
    private function simpanSampul(): string
    {
        return (new Unggahan())->simpanGambarWebp($this->request->getFile('sampul'), GaleriModel::FOLDER, GaleriModel::SISI_SAMPUL);
    }

    /**
     * Pesan sukses, ditambah pengingat bila album belum tampil untuk umum.
     *
     * @param array<string, mixed> $data
     */
    private function pesanTersimpan(string $pesan, array $data): string
    {
        if ($data['tautan'] === null) {
            return $pesan . ' Album belum tampil di halaman Galeri sampai tautannya diisi.';
        }

        return $pesan;
    }

    /**
     * @return array<string, string>
     */
    private function isianFormulir(): array
    {
        $isian = [];

        foreach (['judul', 'keterangan', 'tautan', 'urutan', 'aktif'] as $kotak) {
            $nilai         = $this->request->getPost($kotak);
            $isian[$kotak] = is_string($nilai) ? trim($nilai) : '';
        }

        return $isian;
    }

    /**
     * @param array<string, mixed>|null $album      Yang diubah, `null` untuk yang baru.
     * @param array<string, string>     $isian
     * @param array<string, string>     $galat
     * @param bool                      $pilihUlang Sampul tadi dipilih tetapi belum tersimpan.
     */
    private function tampilkanFormulir(?array $album, array $isian, array $galat = [], bool $pilihUlang = false): string
    {
        return view('admin/pengaturan/galeri/formulir', [
            'album'      => $album,
            'isian'      => $isian,
            'galat'      => $galat,
            'pilihUlang' => $pilihUlang && ! isset($galat['sampul']),
            'unggahan'   => new Unggahan(),
        ]);
    }

    private function penuh(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Galeri sudah berisi ' . GaleriModel::BATAS . ' album. Hapus album yang tidak dipakai dulu, lalu tambahkan album baru.',
        );
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Album itu tidak ditemukan, mungkin sudah dihapus. Pilih dari daftar di bawah.',
        );
    }
}
