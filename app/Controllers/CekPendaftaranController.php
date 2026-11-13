<?php

namespace App\Controllers;

use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\IsiHalamanDepan;
use App\Models\BuktiBayarModel;

/**
 * Cek pendaftaran lewat NPSN atau ID pendaftaran (`/cek-pendaftaran`,
 * rancangan 8).
 *
 * Formulir memakai GET (`?kode=`): tidak mengubah data, jadi hasilnya bisa
 * dibuka ulang. NPSN menampilkan semua pendaftaran sekolah itu berurutan
 * per pendaftaran (K49); ID menampilkan satu pendaftaran. Hanya bisa dipakai
 * bila pengaturan lengkap (rancangan 6.1). Setiap pencarian dihitung oleh
 * pembatas laju per alamat IP (rancangan 13).
 */
class CekPendaftaranController extends BaseController
{
    /**
     * Batas pencarian per alamat IP dalam satu jendela waktu.
     */
    public const BATAS_PENCARIAN = 10;

    /**
     * Lama jendela pembatasan pencarian, dalam detik.
     */
    public const JENDELA_DETIK = 60;

    public function index(): string
    {
        $isi  = new IsiHalamanDepan();
        $data = [
            'namaAcara' => $isi->acara()['nama'],
            'siap'      => $isi->bukaTutup()->keadaan() !== BukaTutupPendaftaran::DISIAPKAN,
            'kode'      => '',
            'galat'     => null,
            'hasil'     => [],
        ];

        $masukan = $this->request->getGet('kode');

        if (! $data['siap'] || $masukan === null) {
            return view('cek_pendaftaran', $data);
        }

        $kode         = strtoupper((string) preg_replace('/\s+/u', '', is_string($masukan) ? $masukan : ''));
        $data['kode'] = mb_substr($kode, 0, 30);

        if ($kode === '') {
            $data['galat'] = 'NPSN atau ID pendaftaran belum diisi. Tulis 8 angka NPSN sekolah, atau ID pendaftaran seperti MAC2A0000126.';

            return view('cek_pendaftaran', $data);
        }

        $pembatas = service('throttler');
        if (! $pembatas->check('cek-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_PENCARIAN, self::JENDELA_DETIK)) {
            $detik = max(1, $pembatas->getTokenTime());

            $this->response->setStatusCode(429);
            $this->response->setHeader('Retry-After', (string) $detik);
            $data['galat'] = 'Terlalu banyak pencarian dalam waktu singkat. Tunggu ' . $detik . ' detik lagi, lalu coba cari kembali.';

            return view('cek_pendaftaran', $data);
        }

        $baca = new DataPendaftaran();

        if (preg_match('/^\d{8}$/', $kode) === 1) {
            $data['hasil'] = $baca->cariNpsn($kode);
            $data['galat'] = $data['hasil'] === [] ? 'Belum ada pendaftaran dengan NPSN ' . $kode . '. Periksa lagi 8 angka NPSN sekolah. Bila sekolah mendaftar tanpa NPSN, cari dengan ID pendaftaran.' : null;
        } elseif (preg_match('/^MAC[0-9A-Z]{1,17}$/', $kode) === 1) {
            $satu          = $baca->cariId($kode);
            $data['hasil'] = $satu === null ? [] : [$satu];
            $data['galat'] = $satu === null ? 'Pendaftaran dengan ID ' . $kode . ' tidak ditemukan. Periksa lagi ID pada bukti pendaftaran atau pesan konfirmasi WA, misalnya MAC2A0000126.' : null;
        } else {
            $data['galat'] = 'Isian belum sesuai. Tulis 8 angka NPSN sekolah (contoh 20300001), atau ID pendaftaran yang diawali MAC (contoh MAC2A0000126).';
        }

        // Jumlah bukti bayar yang sudah diunggah, untuk kotak unggah di kartu (K83).
        foreach ($data['hasil'] as &$p) {
            $p['jumlah_bukti_bayar'] = model(BuktiBayarModel::class)->jumlahMilik((int) $p['id']);
        }
        unset($p);

        return view('cek_pendaftaran', $data);
    }
}
