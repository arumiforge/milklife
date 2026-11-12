<?php

namespace App\Controllers;

use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\SekolahModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Wizard pendaftaran publik (Tahap 4, rancangan 7) dan titik akhir JSON-nya.
 *
 * - `GET pendaftaran/sekolah?npsn=…` (F4-02): data sekolah referensi untuk
 *   langkah 1, penolakan SLB (K28), dan data susulan NPSN itu.
 *
 * Setiap titik akhir dibatasi lajunya per alamat IP pengunjung
 * (`$this->request->getIPAddress()`, sudah membaca `CF-Connecting-IP`, K31)
 * dengan kunci berawalan `pendaftaran-` (kesepakatan dua sesi).
 */
class PendaftaranController extends BaseController
{
    /**
     * Pencarian NPSN paling banyak per alamat IP dalam `JENDELA_SEKOLAH` detik.
     */
    public const BATAS_SEKOLAH = 60;

    public const JENDELA_SEKOLAH = 60;

    /**
     * GET pendaftaran/sekolah?npsn=…
     *
     * Jawaban JSON selalu punya `status` dan `pesan`:
     *
     *     200 ['status' => 'ditemukan', 'pesan' => '…', 'sekolah' => [...], 'susulan' => [...]]
     *     404 ['status' => 'tidak_ditemukan', 'pesan' => '…', 'susulan' => [...]]
     *     422 ['status' => 'tidak_sah' | 'slb', 'pesan' => '…']
     *     429 ['status' => 'terlalu_sering', 'pesan' => '…']
     *
     * `sekolah`: `npsn`, `nama`, `jenjang` (kode A/B/C atau null), `jenjang_label`,
     * `kabupaten` (salah satu lima kabupaten, atau null).
     *
     * `susulan`: data pendaftaran sebelumnya dari NPSN ini, untuk aturan tim
     * dan kuota di wizard (6.6, K51). Hanya jumlah, tanpa data siswa:
     * `tim` (id nomor beregu => jumlah anggota tim sekolah ini) dan
     * `individu` (id nomor individu => jumlah atlet sekolah ini).
     */
    public function sekolah(): ResponseInterface
    {
        $batas = $this->batasiLaju('sekolah', self::BATAS_SEKOLAH, self::JENDELA_SEKOLAH);

        if ($batas !== null) {
            return $batas;
        }

        $npsn = preg_replace('/\s+/', '', (string) $this->request->getGet('npsn')) ?? '';

        if (! SekolahModel::npsnSah($npsn)) {
            return $this->json(422, [
                'status' => 'tidak_sah',
                'pesan'  => 'NPSN harus 8 angka. Periksa lagi NPSN sekolah, atau kosongkan bila sekolah belum punya NPSN.',
            ]);
        }

        $sekolah = model(SekolahModel::class)->cariNpsn($npsn);

        if ($sekolah !== null && PemeriksaPendaftaran::slb($sekolah)) {
            return $this->json(422, ['status' => 'slb', 'pesan' => PemeriksaPendaftaran::pesanSlb(trim((string) $sekolah['nama']))]);
        }

        $susulan = (new DataIndukPendaftaran())->dataSekolah($npsn);
        $susulan = ['tim' => (object) $susulan['tim'], 'individu' => (object) $susulan['individu']];

        if ($sekolah === null) {
            return $this->json(404, [
                'status'  => 'tidak_ditemukan',
                'pesan'   => 'NPSN ini tidak ada di data sekolah kami. Tulis nama sekolah secara lengkap.',
                'susulan' => $susulan,
            ]);
        }

        $jenjang = PemeriksaPendaftaran::JENJANG_REFERENSI[strtoupper((string) $sekolah['jenjang'])] ?? null;

        return $this->json(200, [
            'status'  => 'ditemukan',
            'pesan'   => 'Sekolah ditemukan.',
            'sekolah' => [
                'npsn'          => $npsn,
                'nama'          => trim((string) preg_replace('/\s+/u', ' ', (string) $sekolah['nama'])),
                'jenjang'       => $jenjang,
                'jenjang_label' => (string) Jenjang::label($jenjang),
                'kabupaten'     => Kabupaten::baku($sekolah['kabupaten'] ?? null),
            ],
            'susulan' => $susulan,
        ]);
    }

    /**
     * Pembatas laju per alamat IP. `null` bila masih boleh; selain itu
     * jawaban 429 dengan lama menunggu.
     */
    protected function batasiLaju(string $jenis, int $batas, int $detik): ?ResponseInterface
    {
        $pembatas = service('throttler');
        $kunci    = 'pendaftaran-' . $jenis . '-' . hash('sha256', $this->request->getIPAddress());

        if ($pembatas->check($kunci, $batas, $detik)) {
            return null;
        }

        $tunggu = max(1, $pembatas->getTokenTime());
        $this->response->setHeader('Retry-After', (string) $tunggu);

        return $this->json(429, [
            'status' => 'terlalu_sering',
            'pesan'  => "Terlalu banyak permintaan dalam waktu singkat. Tunggu {$tunggu} detik, lalu coba lagi.",
        ]);
    }

    /**
     * Jawaban JSON beserta token CSRF baru (token berganti setiap kiriman).
     *
     * @param array<string, mixed> $isi
     */
    protected function json(int $status, array $isi): ResponseInterface
    {
        return $this->response
            ->setStatusCode($status)
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON($isi + ['csrf' => csrf_hash()]);
    }
}
