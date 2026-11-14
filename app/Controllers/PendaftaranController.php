<?php

namespace App\Controllers;

use App\Libraries\BuktiPendaftaran;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Jenjang;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Libraries\Pendaftaran\PesanKonfirmasi;
use App\Libraries\Pendaftaran\SimpanPendaftaran;
use App\Libraries\Unggahan;
use App\Models\SekolahModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Wizard pendaftaran publik (Tahap 4, rancangan 7) dan titik akhir JSON-nya.
 *
 * - `GET pendaftaran` (F4-03): halaman wizard, atau keterangan ramah bila
 *   pendaftaran belum terbuka (rancangan 6.1).
 * - `GET pendaftaran/sekolah?npsn=…` (F4-02): data sekolah referensi untuk
 *   langkah 1, penolakan SLB (K28), dan data susulan NPSN itu.
 * - `POST pendaftaran/periksa` (F4-04): pemeriksaan server seluruh kiriman
 *   dan rincian biaya untuk langkah 4.
 * - `POST pendaftaran/simpan` (F4-04): simpan dalam satu transaksi
 *   (`SimpanPendaftaran`), ID pendaftaran, dan alamat WA konfirmasi.
 *
 * Kiriman `periksa` dan `simpan` berupa JSON (bentuknya di komentar
 * `PemeriksaPendaftaran`) dengan token CSRF di kepala `X-CSRF-TOKEN`. Keduanya
 * ditolak bila pendaftaran tidak sedang terbuka (6.1).
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

    public const BATAS_PERIKSA = 30;

    public const JENDELA_PERIKSA = 60;

    public const BATAS_SIMPAN = 10;

    public const JENDELA_SIMPAN = 600;

    /**
     * Kunci `localStorage` draf wizard di peramban.
     */
    public const KUNCI_DRAF = 'milklife-pendaftaran-draf';

    /**
     * GET pendaftaran
     *
     * Wizard hanya tampil bila pendaftaran terbuka; selain itu halaman
     * menampilkan `BukaTutupPendaftaran::kalimat()` sebagai alasannya. Data
     * yang dibutuhkan wizard disematkan sebagai JSON (`DataIndukPendaftaran::untukWizard()`
     * ditambah `alamat` dan `kunci_draf`).
     */
    public function index(): string
    {
        $isi       = new IsiHalamanDepan();
        $bukaTutup = $isi->bukaTutup();
        $wizard    = null;

        if ($bukaTutup->terbuka()) {
            $wizard = (new DataIndukPendaftaran())->untukWizard() + [
                'kunci_draf' => self::KUNCI_DRAF,
                'alamat'     => [
                    'sekolah' => site_url('pendaftaran/sekolah'),
                    'periksa' => site_url('pendaftaran/periksa'),
                    'simpan'  => site_url('pendaftaran/simpan'),
                    'beranda' => site_url('/'),
                    'cek'     => site_url('cek-pendaftaran'),
                ],
                'pembayaran' => self::rekening(),
            ];
        }

        return view('pendaftaran', [
            'namaAcara' => $isi->acara()['nama'],
            'bukaTutup' => $bukaTutup,
            'wizard'    => $wizard,
        ]);
    }

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
                // Desa dan kecamatan, supaya official yakin sekolahnya benar
                // bila namanya umum (K97).
                'lokasi'        => SekolahModel::lokasi(['kabupaten' => ''] + $sekolah),
                // Banyak sekolah lain bernama sama; wizard menonjolkan tawaran
                // mengusulkan nama yang lebih lengkap bila lebih dari 0 (K99).
                'nama_kembar'   => model(SekolahModel::class)->where('nama', $sekolah['nama'])->where('id !=', $sekolah['id'])->countAllResults(),
            ],
            'susulan' => $susulan,
        ]);
    }

    /**
     * POST pendaftaran/periksa
     *
     *     200 ['status' => 'sah', 'pesan' => '…', 'rincian' => [...]]       // rincian: PemeriksaPendaftaran
     *     422 ['status' => 'galat', 'pesan' => '…', 'galat' => [{langkah, jalur, pesan}]]
     *     400 ['status' => 'tidak_sah', …]   409 ['status' => 'ditutup', …]   429 ['status' => 'terlalu_sering', …]
     */
    public function periksa(): ResponseInterface
    {
        $tolak = $this->batasiLaju('periksa', self::BATAS_PERIKSA, self::JENDELA_PERIKSA) ?? $this->tolakBilaTertutup();

        if ($tolak !== null) {
            return $tolak;
        }

        $kiriman = $this->kiriman();

        if ($kiriman === null) {
            return $this->kirimanTidakSah();
        }

        $data  = new DataIndukPendaftaran();
        $hasil = (new PemeriksaPendaftaran(
            $data->induk(),
            $data->ada(PemeriksaPendaftaran::npsnKiriman($kiriman), PemeriksaPendaftaran::nisnKiriman($kiriman)),
            Time::now(config('App')->appTimezone)->format('Y-m-d'),
        ))->periksa($kiriman);

        if ($hasil['galat'] !== []) {
            return $this->galat($hasil['galat']);
        }

        return $this->json(200, ['status' => 'sah', 'pesan' => 'Isian sudah benar.', 'rincian' => $hasil['rincian']]);
    }

    /**
     * POST pendaftaran/simpan
     *
     *     201 ['status' => 'tersimpan', 'pesan' => '…', 'id_pendaftaran' => 'MAC2A0000126',
     *          'rincian' => [...], 'wa' => 'https://wa.me/…' atau null, 'bukti' => 'http://…/bukti/<kode acak>/MAC2A0000126']
     *     422, 400, 409, 429 seperti `periksa()`.
     */
    public function simpan(): ResponseInterface
    {
        $tolak = $this->batasiLaju('simpan', self::BATAS_SIMPAN, self::JENDELA_SIMPAN) ?? $this->tolakBilaTertutup();

        if ($tolak !== null) {
            return $tolak;
        }

        $kiriman = $this->kiriman();

        if ($kiriman === null) {
            return $this->kirimanTidakSah();
        }

        $hasil = (new SimpanPendaftaran())->simpan($kiriman);

        if ($hasil['galat'] !== []) {
            return $this->galat($hasil['galat']);
        }

        $id      = $hasil['pendaftaran']['id_pendaftaran'];
        $rincian = $hasil['rincian'];
        $teks    = PesanKonfirmasi::teks((string) setting('Pembayaran.templat_pesan'), [
            'nama_sekolah'      => $rincian['sekolah']['nama'],
            'npsn'              => $rincian['sekolah']['npsn'],
            'id_pendaftaran'    => $id,
            'subtotal_individu' => $rincian['subtotal_individu'],
            'subtotal_beregu'   => $rincian['subtotal_beregu'],
            'total'             => $rincian['total'],
        ]);

        return $this->json(201, [
            'status'         => 'tersimpan',
            'pesan'          => 'Pendaftaran tersimpan dengan ID ' . $id . '.',
            'id_pendaftaran' => $id,
            'rincian'        => $rincian,
            'wa'             => PesanKonfirmasi::alamat((string) setting('Pembayaran.nomor_wa'), $teks),
            'bukti'          => BuktiPendaftaran::alamat(['id' => (int) $hasil['pendaftaran']['id'], 'id_pendaftaran' => $id]),
        ]);
    }

    /**
     * Data rekening untuk langkah 5.
     *
     * @return array{bank: string, nomor_rekening: string, pemilik_rekening: string, logo_bank: string|null}
     */
    private static function rekening(): array
    {
        $logo = trim((string) setting('Pembayaran.logo_bank'));

        return [
            'bank'             => trim((string) setting('Pembayaran.bank')),
            'nomor_rekening'   => trim((string) setting('Pembayaran.nomor_rekening')),
            'pemilik_rekening' => trim((string) setting('Pembayaran.pemilik_rekening')),
            'logo_bank'        => $logo !== '' && (new Unggahan())->ada($logo) ? base_url($logo) : null,
        ];
    }

    /**
     * Kiriman JSON sebagai larik, atau `null` bila bukan JSON objek.
     *
     * Kunci `konfirmasi_susulan` selalu diisi (kosong bila tidak dikirim),
     * supaya tim susulan yang belum dikonfirmasi official ditolak (K57).
     *
     * @return array<string, mixed>|null
     */
    private function kiriman(): ?array
    {
        try {
            $isi = $this->request->getJSON(true);
        } catch (\Throwable) {
            return null;
        }

        return is_array($isi) ? PemeriksaPendaftaran::wajibKonfirmasiSusulan($isi) : null;
    }

    private function kirimanTidakSah(): ResponseInterface
    {
        return $this->json(400, [
            'status' => 'tidak_sah',
            'pesan'  => 'Isian tidak terbaca. Muat ulang halaman (isian tetap tersimpan), lalu coba lagi.',
        ]);
    }

    private function tolakBilaTertutup(): ?ResponseInterface
    {
        $bukaTutup = (new IsiHalamanDepan())->bukaTutup();

        if ($bukaTutup->terbuka()) {
            return null;
        }

        return $this->json(409, ['status' => 'ditutup', 'pesan' => $bukaTutup->kalimat() . ' Pendaftaran tidak bisa disimpan sekarang.']);
    }

    /**
     * @param list<array{langkah: int, jalur: string, pesan: string}> $galat
     */
    private function galat(array $galat): ResponseInterface
    {
        return $this->json(422, [
            'status' => 'galat',
            'pesan'  => count($galat) === 1 ? 'Ada 1 isian yang perlu diperbaiki.' : 'Ada ' . count($galat) . ' isian yang perlu diperbaiki.',
            'galat'  => $galat,
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
