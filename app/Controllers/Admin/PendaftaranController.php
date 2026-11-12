<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Jenjang;
use App\Libraries\Panel\DataPanelPendaftaran;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Panel\HapusPendaftaran;
use App\Libraries\Panel\StatusPembayaran;
use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\Kabupaten;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pendaftaran di panel admin: `admin/pendaftaran` (F6-02; rancangan 9).
 *
 * - `index`, `data`, `excel`: tabel semua pendaftaran (JSON untuk
 *   DataTables, Excel dengan saringan yang sama), lewat `DataPanelPendaftaran`.
 * - `rincian`: satu pendaftaran lengkap; `foto`: foto penanggung jawab, hanya
 *   untuk admin (rute dijaga filter admin).
 * - `lunas`, `menunggu`: status pembayaran dan nomor dada (`StatusPembayaran`).
 * - `hapus`: pendaftaran beserta berkasnya (`HapusPendaftaran`).
 *
 * `ubah` dan `perbarui` ditambahkan F6-04.
 */
class PendaftaranController extends BaseController
{
    private const ALAMAT = 'admin/pendaftaran';

    private const PESAN_SIBUK = 'Data sedang dipakai admin lain pada saat yang sama. Tunggu beberapa detik, lalu coba lagi.';

    public function index(): string
    {
        return view('admin/pendaftaran/index', [
            'jenjang'   => Jenjang::semua(),
            'kabupaten' => Kabupaten::semua(),
        ]);
    }

    public function data(): ResponseInterface
    {
        $data = new DataPanelPendaftaran();

        return $this->response->setJSON($data->tabel()->jawab($data->pembuat(), $this->request->getGet()));
    }

    public function excel(): DownloadResponse
    {
        $data  = new DataPanelPendaftaran();
        $excel = new EksporExcel('Pendaftaran', DataPanelPendaftaran::KOLOM_EXCEL);
        $excel->tambahBaris($data->tabel()->semuaBaris($data->pembuat(), $this->request->getGet()));

        return $excel->unduh('Pendaftaran_' . date('Ymd_His') . '.xlsx');
    }

    public function rincian(int $id): string
    {
        $pendaftaran = (new DataPanelPendaftaran())->rincian($id);

        if ($pendaftaran === null) {
            throw PageNotFoundException::forPageNotFound('Pendaftaran tidak ditemukan. Mungkin sudah dihapus.');
        }

        return view('admin/pendaftaran/rincian', ['p' => $pendaftaran]);
    }

    /**
     * Foto penanggung jawab. Hanya untuk admin; `AdminFilter` melarang
     * penyimpanan di tembolok.
     */
    public function foto(int $id, int $penanggungJawabId): ResponseInterface
    {
        $jalur = (new DataPanelPendaftaran())->jalurFoto($id, $penanggungJawabId);

        if ($jalur === null) {
            throw PageNotFoundException::forPageNotFound('Foto penanggung jawab tidak ditemukan.');
        }

        return $this->response
            ->setContentType('image/jpeg')
            ->setHeader('Content-Disposition', 'inline; filename="penanggung-jawab-' . $penanggungJawabId . '.jpg"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($jalur));
    }

    public function lunas(int $id): RedirectResponse
    {
        $alamat = self::ALAMAT . '/' . $id;

        try {
            $hasil = (new StatusPembayaran())->tandaiLunas($id);
        } catch (DatabaseException $e) {
            log_message('error', 'Tandai Lunas pendaftaran {id} gagal: {pesan}', ['id' => $id, 'pesan' => $e->getMessage()]);

            return redirect()->to($alamat)->with('pesan_galat', 'Pendaftaran belum ditandai Lunas. ' . self::PESAN_SIBUK);
        }

        if (! $hasil['berhasil']) {
            return redirect()->to($alamat)->with('pesan_galat', (string) $hasil['pesan']);
        }

        $jumlah = count($hasil['nomor']);
        $pesan  = 'Pendaftaran ditandai Lunas. ' . ($jumlah > 0
            ? $jumlah . ' atlet mendapat nomor dada baru.'
            : 'Semua atlet sudah punya nomor dada.');

        return redirect()->to($alamat)->with('pesan_sukses', $pesan);
    }

    public function menunggu(int $id): RedirectResponse
    {
        $alamat = self::ALAMAT . '/' . $id;

        try {
            $hasil = (new StatusPembayaran())->kembaliMenunggu($id);
        } catch (DatabaseException $e) {
            log_message('error', 'Kembali ke Menunggu pendaftaran {id} gagal: {pesan}', ['id' => $id, 'pesan' => $e->getMessage()]);

            return redirect()->to($alamat)->with('pesan_galat', 'Status belum diubah. ' . self::PESAN_SIBUK);
        }

        if (! $hasil['berhasil']) {
            return redirect()->to($alamat)->with('pesan_galat', (string) $hasil['pesan']);
        }

        return redirect()->to($alamat)->with('pesan_sukses', 'Status kembali menjadi Menunggu Konfirmasi Pembayaran. Nomor dada atlet tetap.');
    }

    public function hapus(int $id): RedirectResponse
    {
        try {
            $hasil = (new HapusPendaftaran())->hapus($id);
        } catch (DatabaseException $e) {
            log_message('error', 'Hapus pendaftaran {id} gagal: {pesan}', ['id' => $id, 'pesan' => $e->getMessage()]);

            return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_galat', 'Pendaftaran belum dihapus. ' . self::PESAN_SIBUK);
        }

        if ($hasil === null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
        }

        return redirect()->to(self::ALAMAT)->with(
            'pesan_sukses',
            'Pendaftaran ' . $hasil['id_pendaftaran'] . ' (' . $hasil['nama_sekolah'] . ') sudah dihapus beserta '
            . $hasil['jumlah_atlet'] . ' atlet. NISN atletnya bisa didaftarkan lagi.',
        );
    }

    /**
     * Formulir ubah pendaftaran (F6-04), atau formulir yang ditampilkan ulang
     * dengan galat dari `perbarui()`.
     *
     * @param array<string, mixed>|null                  $kiriman Isian yang ditampilkan ulang.
     * @param list<array{jalur: string, pesan: string}> $galat
     */
    public function ubah(int $id, ?array $kiriman = null, array $galat = []): string
    {
        $isian = (new UbahPendaftaran())->isian($id, $kiriman);

        if ($isian === null) {
            throw PageNotFoundException::forPageNotFound('Pendaftaran tidak ditemukan. Mungkin sudah dihapus.');
        }

        return view('admin/pendaftaran/ubah', [
            'isian' => $isian,
            'induk' => (new DataIndukPendaftaran())->untukWizard(),
            'galat' => $galat,
        ]);
    }

    /**
     * Simpan perubahan pendaftaran (F6-04; `UbahPendaftaran`).
     *
     * Dengan `pratinjau=1` (dikirim `aset/js/admin-ubah-pendaftaran.js`
     * sebelum jendela konfirmasi) tidak ada yang ditulis: jawabannya JSON
     * berisi galat atau kalimat konfirmasi (K32, K59) dan token CSRF baru.
     * Tanpa itu: berhasil dialihkan ke rincian dengan pesan kilat; galat
     * menampilkan ulang formulir.
     */
    public function perbarui(int $id): RedirectResponse|ResponseInterface|string
    {
        $post      = $this->request->getPost();
        $pratinjau = ($post['pratinjau'] ?? '') === '1';
        $kiriman   = [
            'sekolah'          => is_array($post['sekolah'] ?? null) ? $post['sekolah'] : [],
            'penanggung_jawab' => is_array($post['penanggung_jawab'] ?? null) ? $post['penanggung_jawab'] : [],
            'atlet'            => is_array($post['atlet'] ?? null) ? $post['atlet'] : [],
        ];
        $ubah = new UbahPendaftaran();

        try {
            $hasil = $pratinjau
                ? $ubah->periksa($id, $kiriman)
                : $ubah->simpan($id, $kiriman, ($post['setuju_menunggu'] ?? '') === '1');
        } catch (DatabaseException $e) {
            log_message('error', 'Ubah pendaftaran {id} gagal: {pesan}', ['id' => $id, 'pesan' => $e->getMessage()]);

            $hasil = ['galat' => [['jalur' => 'simpan', 'pesan' => 'Perubahan belum disimpan. ' . self::PESAN_SIBUK]]];
        }

        if ($hasil === null) {
            return $pratinjau
                ? $this->response->setStatusCode(404)->setJSON(['galat' => [['jalur' => 'simpan', 'pesan' => 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.']], 'csrf' => csrf_hash()])
                : redirect()->to(self::ALAMAT)->with('pesan_galat', 'Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus.');
        }

        $idPendaftaran = (string) ($hasil['id_pendaftaran'] ?? '');

        if ($pratinjau) {
            return $this->response->setHeader('Cache-Control', 'no-store')->setJSON([
                'galat'            => $hasil['galat'],
                'kembali_menunggu' => $hasil['kembali_menunggu'] ?? false,
                'total_lama'       => $hasil['total_lama'] ?? null,
                'total'            => $hasil['total'] ?? null,
                'judul'            => ($hasil['kembali_menunggu'] ?? false) ? 'Status akan kembali ke Menunggu' : 'Simpan perubahan?',
                'kalimat'          => $hasil['galat'] === [] ? UbahPendaftaran::kalimatKonfirmasi($hasil) : null,
                'csrf'             => csrf_hash(),
            ]);
        }

        if ($hasil['galat'] !== []) {
            return $this->ubah($id, $kiriman, $hasil['galat']);
        }

        $pesan = 'Perubahan pendaftaran ' . $idPendaftaran . ' tersimpan. Total sekarang ' . rupiah($hasil['total']) . '.';

        if ($hasil['kembali_menunggu']) {
            $pesan .= ' Status kembali menjadi Menunggu Konfirmasi Pembayaran; tandai Lunas lagi setelah selisihnya diurus.';
        }

        if ($hasil['nomor_dada'] !== []) {
            $pesan .= ' ' . count($hasil['nomor_dada']) . ' atlet baru mendapat nomor dada.';
        }

        return redirect()->to(self::ALAMAT . '/' . $id)->with('pesan_sukses', $pesan);
    }
}
