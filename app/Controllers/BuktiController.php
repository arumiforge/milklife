<?php

namespace App\Controllers;

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Models\BuktiBayarModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Bukti pendaftaran PDF (`/bukti/{kode acak}/{id-pendaftaran}`, rancangan 8, K77).
 *
 * Kode acak harus cocok dengan `pendaftaran.kode_bukti`; tanpa kode yang
 * benar, bukti tidak ditemukan walau ID-nya benar.
 *
 * Tidak dibatasi kelengkapan pengaturan: pendaftaran yang sudah tersimpan
 * tetap bisa dicetak. Setiap permintaan dihitung pembatas laju per alamat IP
 * (rancangan 13). PDF dikirim langsung, tidak disimpan di disk.
 *
 * `POST /bukti/{kode acak}/{id-pendaftaran}/bukti-bayar` (K83): official
 * mengunggah bukti transfer sendiri, dari langkah Pembayaran di wizard
 * (fetch, jawaban JSON) atau dari Cek Pendaftaran (formulir biasa, kembali ke
 * hasil pencarian `kembali` dengan pesan kilat). Alamat bukti berkode acak
 * menjadi izinnya, jadi ID atau NPSN saja tidak cukup. Pendaftaran Lunas
 * tidak menerima bukti lagi; batas 5 gambar sama dengan panel admin.
 */
class BuktiController extends BaseController
{
    /**
     * Batas permintaan bukti per alamat IP dalam satu jendela waktu.
     */
    public const BATAS_PERMINTAAN = 10;

    /**
     * Lama jendela pembatasan, dalam detik.
     */
    public const JENDELA_DETIK = 60;

    public function index(string $kode, string $id): ResponseInterface
    {
        $pembatas = service('throttler');
        if (! $pembatas->check('bukti-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, self::JENDELA_DETIK)) {
            $detik = max(1, $pembatas->getTokenTime());

            return $this->response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $detik)
                ->setBody(view('bukti/terlalu_sering', [
                    'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
                    'detik'     => $detik,
                ]));
        }

        $pendaftaran = $this->cariPendaftaran($kode, $id);
        $bukti       = new BuktiPendaftaran($pendaftaran);

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $bukti->namaBerkas() . '"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($bukti->pdf());
    }

    public function unggahBayar(string $kode, string $id): ResponseInterface
    {
        $pendaftaran = $this->cariPendaftaran($kode, $id);
        $pembatas    = service('throttler');

        if (! $pembatas->check('bukti-bayar-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PERMINTAAN, self::JENDELA_DETIK)) {
            return $this->jawabUnggah($pendaftaran, 429, 'Terlalu banyak unggahan dalam waktu singkat. Tunggu ' . max(1, $pembatas->getTokenTime()) . ' detik, lalu coba lagi.');
        }

        if ($pendaftaran['status'] === 'lunas') {
            return $this->jawabUnggah($pendaftaran, 409, 'Pendaftaran ' . $pendaftaran['id_pendaftaran'] . ' sudah Lunas, jadi bukti bayar tidak perlu diunggah lagi.');
        }

        $pesan = (new BerkasBuktiBayar())->unggahKe((int) $pendaftaran['id'], $this->request->getFile(BuktiBayarController::KOTAK));

        if ($pesan === BerkasBuktiBayar::PENUH) {
            return $this->jawabUnggah($pendaftaran, 409, 'Bukti bayar pendaftaran ini sudah ' . BuktiBayarModel::BATAS . ' gambar, batas paling banyak. Bila perlu mengganti gambar, hubungi panitia lewat WA.');
        }

        if ($pesan !== null) {
            return $this->jawabUnggah($pendaftaran, 422, $pesan);
        }

        $jumlah = model(BuktiBayarModel::class)->jumlahMilik((int) $pendaftaran['id']);

        return $this->jawabUnggah($pendaftaran, 201, 'Bukti bayar terkirim (' . $jumlah . ' dari ' . BuktiBayarModel::BATAS . ' gambar). Panitia akan memeriksanya; status berubah menjadi Lunas setelah transfer diterima.');
    }

    /**
     * Pendaftaran dengan kode bukti yang cocok, atau halaman tidak ditemukan.
     *
     * @return array<string, mixed>
     */
    private function cariPendaftaran(string $kode, string $id): array
    {
        $pendaftaran = preg_match('/^MAC[0-9A-Za-z]{1,17}$/', $id) === 1 ? (new DataPendaftaran())->cariId($id) : null;

        if ($pendaftaran === null || ! hash_equals((string) ($pendaftaran['kode_bukti'] ?? ''), $kode)) {
            throw PageNotFoundException::forPageNotFound('Bukti pendaftaran tidak ditemukan. Buka bukti lewat tombol di halaman Cek Pendaftaran, atau pindai kode QR di bukti yang sudah dicetak.');
        }

        return $pendaftaran;
    }

    /**
     * Jawaban unggah: JSON untuk wizard, selain itu kembali ke Cek Pendaftaran
     * (hasil pencarian `kembali`, NPSN atau ID) dengan pesan kilat.
     *
     * @param array<string, mixed> $pendaftaran
     */
    private function jawabUnggah(array $pendaftaran, int $status, string $pesan): ResponseInterface
    {
        if (str_contains($this->request->getHeaderLine('Accept'), 'application/json')) {
            return $this->response
                ->setStatusCode($status)
                ->setHeader('Cache-Control', 'no-store')
                ->setJSON(['status' => $status === 201 ? 'tersimpan' : 'gagal', 'pesan' => $pesan, 'csrf' => csrf_hash()]);
        }

        $kembali = strtoupper((string) $this->request->getPost('kembali'));
        $kembali = preg_match('/^(\d{8}|MAC[0-9A-Z]{1,17})$/D', $kembali) === 1 ? $kembali : (string) $pendaftaran['id_pendaftaran'];

        return redirect()->to(site_url('cek-pendaftaran') . '?kode=' . rawurlencode($kembali))
            ->with($status === 201 ? 'pesan_sukses' : 'pesan_galat', $pesan);
    }
}
