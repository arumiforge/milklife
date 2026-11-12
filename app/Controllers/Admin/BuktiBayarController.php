<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Models\BuktiBayarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;
use Throwable;

/**
 * Bukti bayar pendaftaran (K34; F6-03): unggah, lihat, unduh, dan hapus.
 *
 * Official mengirim bukti transfer lewat WA; admin mengunggahnya ke
 * pendaftaran yang bersangkutan dari rincian pendaftaran
 * (`admin/pendaftaran/<id>`, potongan `admin/pendaftaran/_bukti_bayar`).
 *
 * Rute (blok "Panel data (Tahap 6)" di `app/Config/Routes.php`, dijaga
 * filter admin):
 *
 * - `POST admin/pendaftaran/<id>/bukti-bayar`: unggah satu gambar (kotak
 *   `bukti`). Gambar diperiksa dan dikecilkan `BerkasBuktiBayar` (sisi
 *   terpanjang 1600 piksel, JPEG). Paling banyak `BuktiBayarModel::BATAS`
 *   gambar per pendaftaran.
 * - `GET admin/pendaftaran/<id>/bukti-bayar/<bukti>`: gambar untuk
 *   ditampilkan di halaman (gambar kecil dan gambar besar).
 * - `GET admin/pendaftaran/<id>/bukti-bayar/<bukti>/unduh`: gambar sebagai
 *   unduhan, bernama `BuktiBayar_<ID pendaftaran>_<urutan>.jpg`.
 * - `POST admin/pendaftaran/<id>/bukti-bayar/<bukti>/hapus`: hapus baris dan
 *   berkasnya.
 *
 * Setelah unggah atau hapus, admin dikembalikan ke rincian pendaftaran
 * dengan pesan kilat `pesan_sukses` atau `pesan_galat`.
 *
 * Bukti yang bukan milik pendaftaran di alamat (`BuktiBayarModel::milik()`),
 * pendaftaran yang tidak ada, dan berkas yang hilang dari disk menampilkan
 * halaman tidak ditemukan. Berkas hanya disajikan lewat rute ini; filter
 * admin menambahkan `Cache-Control: no-store`.
 *
 * Urutan langkah supaya tidak ada berkas tanpa baris atau baris tanpa berkas:
 *
 * - Unggah: simpan berkas (di luar transaksi, karena mengecilkan gambar
 *   butuh waktu) → di dalam transaksi kunci baris pendaftaran, hitung bukti,
 *   simpan baris → selesaikan transaksi. Bila batas sudah tercapai (misalnya
 *   dua unggahan bersamaan) atau baris gagal disimpan, berkas baru dihapus.
 * - Hapus: hapus baris di dalam transaksi → hapus berkas → selesaikan
 *   transaksi. Bila berkas tidak bisa dihapus, transaksi dibatalkan sehingga
 *   barisnya kembali. Berkas yang sudah tidak ada tidak menghalangi hapus.
 */
class BuktiBayarController extends BaseController
{
    /**
     * Nama kotak unggah di formulir.
     */
    public const KOTAK = 'bukti';

    public function unggah(int $id): RedirectResponse
    {
        $pendaftaran = $this->cariPendaftaran($id);
        $model       = model(BuktiBayarModel::class);
        $kembali     = self::alamatRincian($id);

        if (! $model->masihBolehTambah($id)) {
            return redirect()->to($kembali)->with('pesan_galat', self::pesanPenuh());
        }

        $berkas  = new BerkasBuktiBayar();
        $unggah  = $this->request->getFile(self::KOTAK);
        $pesan   = $berkas->periksa($unggah);

        if ($pesan !== null) {
            return redirect()->to($kembali)->with('pesan_galat', 'Bukti bayar belum tersimpan. ' . $pesan);
        }

        try {
            $hasil = $berkas->simpan($unggah);
        } catch (RuntimeException $e) {
            return redirect()->to($kembali)->with('pesan_galat', 'Bukti bayar belum tersimpan. ' . $e->getMessage());
        }

        $db = $model->db;
        $db->transBegin();

        try {
            // Kunci baris pendaftaran supaya dua unggahan bersamaan tidak
            // sama-sama lolos batas.
            $db->query('SELECT id FROM ' . $db->prefixTable('pendaftaran') . ' WHERE id = ? FOR UPDATE', [$id]);

            if ($model->jumlahMilik($id) >= BuktiBayarModel::BATAS) {
                $db->transRollback();
                $berkas->hapus($hasil['berkas']);

                return redirect()->to($kembali)->with('pesan_galat', self::pesanPenuh());
            }

            $baru = $model->insert(['pendaftaran_id' => $id] + $hasil);
        } catch (Throwable $e) {
            $db->transRollback();
            $berkas->hapus($hasil['berkas']);

            throw $e;
        }

        if ($baru === false) {
            $db->transRollback();
            $berkas->hapus($hasil['berkas']);

            return redirect()->to($kembali)->with(
                'pesan_galat',
                'Bukti bayar belum tersimpan karena ada gangguan di server. Coba unggah sekali lagi; bila masih gagal, hubungi pengelola server.',
            );
        }

        $db->transCommit();

        $jumlah = $model->jumlahMilik($id);

        return redirect()->to($kembali)->with(
            'pesan_sukses',
            'Bukti bayar tersimpan untuk pendaftaran ' . $pendaftaran['id_pendaftaran'] . ' (' . $jumlah . ' dari ' . BuktiBayarModel::BATAS . ' gambar).',
        );
    }

    public function lihat(int $id, int $buktiId): ResponseInterface
    {
        $this->cariPendaftaran($id);
        $penuh = $this->jalurBerkas($id, $buktiId);

        return $this->response
            ->setContentType('image/jpeg', '')
            ->setHeader('Content-Disposition', 'inline; filename="' . $this->namaUnduhan($id, $buktiId) . '"')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((string) file_get_contents($penuh));
    }

    public function unduh(int $id, int $buktiId): DownloadResponse
    {
        $this->cariPendaftaran($id);
        $penuh = $this->jalurBerkas($id, $buktiId);

        $unduhan = $this->response->download($penuh, null);
        $unduhan->setFileName($this->namaUnduhan($id, $buktiId));
        $unduhan->setContentType('image/jpeg', '');

        return $unduhan;
    }

    public function hapus(int $id, int $buktiId): RedirectResponse
    {
        $this->cariPendaftaran($id);

        $model   = model(BuktiBayarModel::class);
        $baris   = $this->cariBukti($id, $buktiId);
        $urutan  = $this->urutan($id, $buktiId);
        $berkas  = new BerkasBuktiBayar();
        $penuh   = $berkas->jalurPenuh((string) $baris['berkas']);
        $kembali = self::alamatRincian($id);
        $db      = $model->db;

        $db->transBegin();

        try {
            $terhapus = $model->delete($buktiId);
        } catch (Throwable $e) {
            $db->transRollback();

            throw $e;
        }

        if (! $terhapus) {
            $db->transRollback();

            return redirect()->to($kembali)->with('pesan_galat', 'Bukti bayar ke-' . $urutan . ' belum terhapus karena ada gangguan di server. Coba hapus sekali lagi.');
        }

        // Berkas dihapus sebelum transaksi diselesaikan: bila gagal, baris kembali.
        if ($penuh !== null && is_file($penuh) && ! $berkas->hapus((string) $baris['berkas'])) {
            $db->transRollback();

            return redirect()->to($kembali)->with(
                'pesan_galat',
                'Bukti bayar ke-' . $urutan . ' tidak bisa dihapus dari server, jadi gambarnya tetap disimpan. Pastikan folder writable/unggahan boleh ditulisi, lalu coba hapus lagi.',
            );
        }

        $db->transCommit();

        return redirect()->to($kembali)->with('pesan_sukses', 'Bukti bayar ke-' . $urutan . ' sudah dihapus.');
    }

    /**
     * Alamat rincian pendaftaran (halaman F6-02), tujuan setelah unggah dan hapus.
     */
    public static function alamatRincian(int $id): string
    {
        return 'admin/pendaftaran/' . $id;
    }

    private static function pesanPenuh(): string
    {
        return 'Bukti bayar pendaftaran ini sudah ' . BuktiBayarModel::BATAS . ' gambar, batas paling banyak. Hapus gambar yang tidak dipakai lebih dulu, lalu unggah lagi.';
    }

    /**
     * Baris pendaftaran, atau halaman tidak ditemukan.
     *
     * @return array<string, mixed>
     */
    private function cariPendaftaran(int $id): array
    {
        $baris = db_connect()->table('pendaftaran')->select('id, id_pendaftaran')->where('id', $id)->get()->getRowArray();

        if (! is_array($baris)) {
            throw PageNotFoundException::forPageNotFound('Pendaftaran ini tidak ditemukan. Mungkin sudah dihapus; kembali ke daftar pendaftaran.');
        }

        return $baris;
    }

    /**
     * Baris bukti bayar milik pendaftaran ini, atau halaman tidak ditemukan.
     *
     * @return array<string, mixed>
     */
    private function cariBukti(int $id, int $buktiId): array
    {
        $baris = model(BuktiBayarModel::class)->milik($id, $buktiId);

        if ($baris === null) {
            throw PageNotFoundException::forPageNotFound('Bukti bayar ini tidak ditemukan. Mungkin sudah dihapus; kembali ke rincian pendaftaran.');
        }

        return $baris;
    }

    /**
     * Jalur berkas bukti bayar di disk, atau halaman tidak ditemukan bila
     * barisnya tidak ada atau berkasnya hilang.
     */
    private function jalurBerkas(int $id, int $buktiId): string
    {
        $baris = $this->cariBukti($id, $buktiId);
        $penuh = (new BerkasBuktiBayar())->jalurPenuh((string) $baris['berkas']);

        if ($penuh === null || ! is_file($penuh)) {
            throw PageNotFoundException::forPageNotFound('Gambar bukti bayar ini tidak ditemukan di server. Hapus bukti ini dari rincian pendaftaran, lalu unggah lagi.');
        }

        return $penuh;
    }

    /**
     * Urutan bukti (mulai 1) di antara bukti bayar pendaftaran ini, urut waktu unggah.
     */
    private function urutan(int $id, int $buktiId): int
    {
        $semua = array_map('intval', array_column(model(BuktiBayarModel::class)->milikPendaftaran($id), 'id'));
        $letak = array_search($buktiId, $semua, true);

        return $letak === false ? 1 : $letak + 1;
    }

    /**
     * Nama berkas saat diunduh, contoh `BuktiBayar_MAC2A0000326_2.jpg`.
     */
    private function namaUnduhan(int $id, int $buktiId): string
    {
        $pendaftaran = $this->cariPendaftaran($id);
        $kode        = preg_replace('/[^0-9A-Za-z]+/', '', (string) $pendaftaran['id_pendaftaran']) ?: (string) $id;

        return 'BuktiBayar_' . $kode . '_' . $this->urutan($id, $buktiId) . '.jpg';
    }
}
