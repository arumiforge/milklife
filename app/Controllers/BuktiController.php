<?php

namespace App\Controllers;

use App\Controllers\Admin\BuktiBayarController;
use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\SesiAdmin;
use App\Models\BuktiBayarModel;
use CodeIgniter\Config\Factories;
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
 *
 * Alamat bukti lama dari pendaftaran yang sudah digabung (K109) diarahkan ke
 * alamat bukti pendaftaran gabungannya.
 *
 * Admin yang sedang masuk mendapat halaman pilihan "Buka daftar ulang" atau
 * bukti PDF (F8-03); `?pdf=1` langsung memberi PDF-nya.
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

        // Alamat bukti lama dari pendaftaran yang sudah digabung (K109): kode QR
        // di bukti yang sudah dicetak diarahkan ke bukti pendaftaran gabungan.
        if (strcasecmp((string) $pendaftaran['id_pendaftaran'], $id) !== 0) {
            return redirect()->to(BuktiPendaftaran::alamat($pendaftaran))
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        // Admin yang sedang masuk dan memindai kode QR dengan aplikasi kamera
        // (F8-03): pilihan "Buka daftar ulang" atau PDF-nya (`?pdf=1`).
        if ($this->request->getGet('pdf') !== '1' && $this->adminMasuk()) {
            return $this->response
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->setBody(view('admin/hari_lomba/bukti_admin', [
                    'idPendaftaran'     => (string) $pendaftaran['id_pendaftaran'],
                    'namaSekolah'       => (string) $pendaftaran['nama_sekolah'],
                    'alamatDaftarUlang' => site_url('admin/daftar-ulang') . '?id=' . rawurlencode((string) $pendaftaran['id_pendaftaran']),
                    'alamatPdf'         => BuktiPendaftaran::alamat($pendaftaran) . '?pdf=1',
                ]));
        }

        $bukti = new BuktiPendaftaran($pendaftaran);

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
            return $this->jawabUnggah($pendaftaran, 409, 'Bukti bayar pendaftaran ini sudah mencapai batas, paling banyak ' . BuktiBayarModel::batas() . ' gambar. Bila perlu mengganti gambar, hubungi panitia lewat WA.');
        }

        if ($pesan !== null) {
            return $this->jawabUnggah($pendaftaran, 422, $pesan);
        }

        $jumlah = model(BuktiBayarModel::class)->jumlahMilik((int) $pendaftaran['id']);

        return $this->jawabUnggah($pendaftaran, 201, 'Bukti bayar terkirim (' . $jumlah . ' dari ' . BuktiBayarModel::batas() . ' gambar). Panitia akan memeriksanya; status berubah menjadi Lunas setelah transfer diterima.');
    }

    /**
     * Admin sedang masuk (F8-03). Sesi hanya dibaca bila kuki sesinya ada,
     * supaya pengunjung biasa tidak dibuatkan sesi baru.
     */
    private function adminMasuk(): bool
    {
        if ($this->request->getCookie(config('Session')->cookieName) === null) {
            return false;
        }

        $id = (int) session(SesiAdmin::KUNCI_ID);

        return $id > 0 && Factories::libraries(SesiAdmin::class)->periksa($id, session(SesiAdmin::KUNCI_SIDIK)) === SesiAdmin::SAH;
    }

    /**
     * Pendaftaran dengan kode bukti yang cocok, atau halaman tidak ditemukan.
     * ID dan kode bukti lama dari pendaftaran yang sudah digabung (K109)
     * memberi pendaftaran gabungannya.
     *
     * @return array<string, mixed>
     */
    private function cariPendaftaran(string $kode, string $id): array
    {
        $baca        = new DataPendaftaran();
        $sah         = preg_match('/^MAC[0-9A-Za-z]{1,17}$/', $id) === 1;
        $pendaftaran = $sah ? $baca->cariId($id) : null;

        if ($pendaftaran !== null && strcasecmp((string) $pendaftaran['id_pendaftaran'], $id) !== 0) {
            // ID lama: hanya sah bersama kode bukti lamanya.
            $tujuan      = $baca->cariBuktiLama($id, $kode);
            $pendaftaran = $tujuan === null ? null : $baca->cariId($tujuan['id_pendaftaran']);
            $kode        = (string) ($pendaftaran['kode_bukti'] ?? '');
        }

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
