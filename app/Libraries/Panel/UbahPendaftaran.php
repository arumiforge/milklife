<?php

namespace App\Libraries\Panel;

use App\Libraries\Panel\Ubah\BiayaUbah;
use App\Libraries\Panel\Ubah\DataTersimpan;
use App\Libraries\Panel\Ubah\IsianUbah;
use App\Libraries\Panel\Ubah\MasukanUbah;
use App\Libraries\Panel\Ubah\RencanaUbah;
use App\Libraries\Panel\Ubah\StatusUbah;
use App\Libraries\Panel\Ubah\TulisUbah;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use Throwable;

/**
 * Ubah pendaftaran yang sudah tersimpan dari panel admin (F6-04; rancangan
 * 6.9 dan 9; K32, K57, K59; rencana Tahap 6, "Ubah pendaftaran").
 *
 * ## Kiriman
 *
 * Bentuknya sama dengan kiriman wizard (`PemeriksaPendaftaran`), ditambah
 * `id` pada penanggung jawab dan atlet yang sudah tersimpan:
 *
 *     [
 *         'sekolah' => ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '20317000', 'nama' => ''],
 *         'penanggung_jawab' => [
 *             ['id' => 7, 'nama' => 'Ibu Contoh', 'nomor_wa' => '0812-3456-7890', 'foto' => ''],   // foto tetap
 *             ['id' => 8, 'nama' => '…', 'nomor_wa' => '…', 'foto' => 'data:image/jpeg;base64,…'], // ganti foto
 *             ['nama' => '…', 'nomor_wa' => '…', 'foto' => 'data:image/jpeg;base64,…'],            // baru
 *         ],
 *         'atlet' => [
 *             ['id' => 11, 'nama' => 'Budi Contoh', 'nisn' => '0012345678', 'tanggal_lahir' => '2016-05-01',
 *              'jenis_kelamin' => 'L', 'nomor' => [12, 40], 'regu' => [40 => ['regu' => 'A', 'peran' => 'inti']]],
 *             ['nama' => '…', …],                                                                   // atlet baru
 *         ],
 *     ]
 *
 * Penanggung jawab dan atlet tersimpan yang tidak ada di kiriman dihapus.
 * Kunci `konfirmasi_susulan` dan `turun_cadangan` diabaikan: admin tidak
 * diminta mengonfirmasi regu susulan (K57), dan inti atlet pendaftaran lain
 * diubah lewat Ubah pendaftaran itu. Kunci `bebankan_tim` (`KUNCI_BEBANKAN`)
 * berisi regu ("nomor:regu") tanpa pembayar yang biaya regunya dibebankan ke
 * pendaftaran ini (K68, K105).
 *
 * ## Aturan
 *
 * - Pemeriksaan memakai `PemeriksaPendaftaran` (dan lewat itu
 *   `KelengkapanTim`) dengan data "sudah ada" tanpa pendaftaran ini: NISN
 *   milik pendaftaran lain ditolak, NISN milik pendaftaran ini diterima, dan
 *   regu serta kuota dihitung dari pendaftaran lain dengan NPSN yang sama.
 *   Regu yang sudah terdaftar boleh kurang inti (mode longgar
 *   `KelengkapanTim`, K105), supaya perubahan lain tetap bisa disimpan; regu
 *   baru harus lengkap. Buka-tutup pendaftaran tidak diperiksa.
 * - Biaya (asumsi 4 rencana Tahap 6): baris atlet-nomor (atlet tersimpan,
 *   nomor yang sama) dan regu (nomor dan label regu yang sama) yang tetap ada
 *   mempertahankan biaya tersimpan; baris baru memakai tarif saat ini (regu
 *   susulan tetap Rp 0). Subtotal dan total dihitung ulang dari baris itu.
 * - Regu tanpa pembayar (K68, `TimTanpaPembayar`): regu di `bebankan_tim`
 *   milik pendaftaran ini yang biaya regunya Rp 0 diberi tarif saat ini;
 *   bila regu itu sudah punya pembayar lain, kiriman ditolak. Regu lain
 *   diabaikan. `tim_ditinggalkan` menyebut tim yang kehilangan pembayar
 *   karena perubahan ini (untuk jendela konfirmasi dan pesan sesudahnya).
 * - K32: pendaftaran Lunas yang totalnya berubah kembali ke Menunggu.
 *   Perubahan ini hanya disimpan bila `$setujuMenunggu` (admin sudah melihat
 *   jendela konfirmasinya); tanpa itu hasilnya `perlu_setuju` dan tidak ada
 *   yang ditulis.
 * - K59: pendaftaran Lunas yang totalnya tetap langsung memberi nomor dada
 *   kepada atlet yang belum punya (`StatusPembayaran::beriNomorDada()`), di
 *   transaksi yang sama. Bila awalan penuh, seluruh perubahan dibatalkan.
 * - Atlet tersimpan mempertahankan nomor dadanya, juga bila kelompok umurnya
 *   berubah. ID pendaftaran tidak berubah, juga bila jenjang sekolah diubah,
 *   supaya bukti dan cek pendaftaran yang sudah dipegang official tetap berlaku.
 *
 * ## Penyimpanan
 *
 * Kunci bernama per NPSN seperti `SimpanPendaftaran` (NPSN lama dan baru bila
 * NPSN diubah). Foto baru ditulis sebelum transaksi dan dihapus bila gagal;
 * foto lama (diganti atau penanggung jawabnya dihapus) dihapus setelah
 * transaksi berhasil. Di dalam transaksi baris `pendaftaran` dikunci dan
 * dibandingkan dengan yang dibaca saat pemeriksaan; bila sudah berubah
 * (misalnya baru ditandai Lunas admin lain), perubahan dibatalkan.
 *
 * ## Pemakaian
 *
 *     $ubah  = new UbahPendaftaran();
 *     $isian = $ubah->isian($id);                 // isian formulir, null bila tidak ada
 *     $cek   = $ubah->periksa($id, $kiriman);     // pratinjau tanpa menulis
 *     $hasil = $ubah->simpan($id, $kiriman, setujuMenunggu: true);
 *
 * Hasil `periksa()` dan `simpan()` (`null` bila pendaftaran tidak ada):
 *
 *     [
 *         'galat'             => [['jalur' => 'atlet.0.nisn', 'pesan' => '…'], …],   // [] bila sah
 *         'perlu_setuju'      => false,   // true: K32 berlaku dan belum disetujui (tidak ada yang ditulis)
 *         'tersimpan'         => true,    // false untuk periksa() dan setiap kegagalan
 *         'status_lama'       => 'lunas', 'status' => 'menunggu', 'kembali_menunggu' => true,
 *         'total_lama'        => 450000,
 *         'subtotal_individu' => 300000, 'subtotal_beregu' => 100000, 'total' => 400000,
 *         'atlet_baru'        => 1,       // atlet tanpa id di kiriman
 *         'nomor_dada'        => [21 => '2005'],   // nomor dada yang baru dibagikan (K59), hanya simpan()
 *         'tim_dibebankan'    => ['12:A'], // regu yang biaya regunya baru dibebankan (K68)
 *         'tim_ditinggalkan'  => [ … ],   // `TimTanpaPembayar::ditinggalkan()` untuk perubahan ini
 *         'gabung'            => null,    // hanya simpan(): `GabungPendaftaran::gabungNpsn()` bila pendaftaran
 *                                         // Lunas ini digabung dengan pendaftaran Lunas lain ber-NPSN sama (K109)
 *     ]
 *
 * ## Letak kode
 *
 * Kelas ini pintu masuk; aturannya ada di `App\Libraries\Panel\Ubah` (F7-30):
 * `DataTersimpan` (baca data tersimpan, induk dengan data nonaktif K98),
 * `IsianUbah` (isian formulir), `RencanaUbah` (pemeriksaan kiriman),
 * `BiayaUbah` (biaya baris dan regu, K68), `StatusUbah` (K32 dan kalimat
 * konfirmasi), `TulisUbah` (transaksi simpan, K59, riwayat F8-01, K109), dan
 * `MasukanUbah` (pembaca nilai kiriman).
 */
final class UbahPendaftaran
{
    /**
     * Lama menunggu kunci per NPSN, dalam detik.
     */
    public const TUNGGU_KUNCI = 10;

    /**
     * Kunci kiriman: regu ("nomor:regu") yang biaya regunya dibebankan ke pendaftaran ini (K68).
     */
    public const KUNCI_BEBANKAN = 'bebankan_tim';

    private BaseConnection $db;

    private DataTersimpan $tersimpan;

    private RencanaUbah $rencana;

    private TulisUbah $tulis;

    public function __construct(?BaseConnection $db = null, ?DataIndukPendaftaran $data = null, ?FotoPenanggungJawab $foto = null)
    {
        $this->db        = $db ?? db_connect();
        $foto          ??= new FotoPenanggungJawab();
        $this->tersimpan = new DataTersimpan($this->db, $data ?? new DataIndukPendaftaran(), $foto);
        $this->rencana   = new RencanaUbah($this->tersimpan, new BiayaUbah($this->db));
        $this->tulis     = new TulisUbah($this->db, $foto);
    }

    /**
     * Isian formulir ubah: dari data tersimpan, atau dari kiriman yang
     * ditampilkan ulang (`$kiriman`), dilengkapi nomor dada atlet dan tanda
     * foto penanggung jawab dari data tersimpan. `null` bila pendaftaran
     * tidak ada.
     *
     *     [
     *         'pendaftaran'      => ['id', 'id_pendaftaran', 'status', 'subtotal_individu', 'subtotal_beregu', 'total'],
     *         'sekolah'          => ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '99999911', 'nama' => '…'],
     *         'penanggung_jawab' => [['id' => 7, 'nama' => '…', 'nomor_wa' => '0812-…', 'foto' => '', 'ada_foto' => true]],
     *         'atlet'            => [['id' => 11, 'nama' => '…', 'nisn' => '…', 'tanggal_lahir' => '2017-04-12',
     *                                 'jenis_kelamin' => 'L', 'nomor' => [3, 7], 'regu' => [7 => ['regu' => 'A', 'peran' => 'inti']],
     *                                 'nomor_dada' => '2001']],
     *         'tim_tanpa_pembayar' => ['12:A' => [… bentuk TimTanpaPembayar …, 'dibebankan' => false]],
     *     ]
     *
     * `id` bernilai `null` untuk baris baru; `foto` berisi data URL foto baru
     * yang belum tersimpan (hanya pada tampilan ulang).
     *
     * @param array<string, mixed>|null $kiriman
     *
     * @return array<string, mixed>|null
     */
    public function isian(int $id, ?array $kiriman = null): ?array
    {
        $lama = $this->tersimpan->baca($id);

        if ($lama === null) {
            return null;
        }

        return (new IsianUbah($this->db, $this->tersimpan))->susun($id, $lama, $kiriman);
    }

    /**
     * Periksa kiriman dan hitung biaya serta akibat status tanpa menulis apa
     * pun (pratinjau untuk jendela konfirmasi).
     *
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>|null
     */
    public function periksa(int $id, array $kiriman, ?string $hariIni = null): ?array
    {
        $lama = $this->tersimpan->baca($id);

        if ($lama === null) {
            return null;
        }

        return self::ringkas($this->rencana->hitung($lama, $kiriman, $hariIni ?? self::hariIni()));
    }

    /**
     * Simpan perubahan.
     *
     * @param array<string, mixed> $kiriman
     * @param bool                 $setujuMenunggu Admin sudah menyetujui status kembali ke Menunggu (K32).
     * @param callable|null        $dalamTransaksi Dipanggil di dalam transaksi simpan sebelum commit,
     *                                             dengan `(BaseConnection $db, array $rencana)`; bila
     *                                             melempar, seluruh perubahan dibatalkan. Dipakai
     *                                             `TolakAtlet` untuk mencatat atlet yang ditolak (K116).
     *                                             Tanpa itu, perubahan dicatat di riwayat tindakan
     *                                             admin sebagai "Ubah" (F8-01); dengan itu, pemanggil
     *                                             yang mencatat riwayatnya sendiri.
     *
     * @return array<string, mixed>|null
     */
    public function simpan(int $id, array $kiriman, bool $setujuMenunggu = false, ?string $hariIni = null, ?callable $dalamTransaksi = null): ?array
    {
        $hariIni ??= self::hariIni();
        $lama = $this->tersimpan->baca($id);

        if ($lama === null) {
            return null;
        }

        $kunci = array_values(array_unique(array_filter([
            MasukanUbah::npsn($lama['pendaftaran']['npsn']),
            PemeriksaPendaftaran::npsnKiriman($kiriman),
        ])));
        sort($kunci);

        $dipegang = [];

        try {
            foreach ($kunci as $npsn) {
                $nama = 'milklife-pendaftaran-' . $npsn;

                if (! $this->ambilKunci($nama)) {
                    return self::ringkas(self::gagal($lama, 'simpan', 'Pendaftaran lain dari sekolah ini sedang disimpan. Tunggu beberapa detik, lalu tekan simpan lagi.'));
                }

                $dipegang[] = $nama;
            }

            // Baca ulang setelah kunci didapat.
            $lama = $this->tersimpan->baca($id);

            if ($lama === null) {
                return null;
            }

            return $this->simpanTerkunci($lama, $kiriman, $setujuMenunggu, $hariIni, $dalamTransaksi);
        } finally {
            foreach ($dipegang as $nama) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$nama]);
            }
        }
    }

    /**
     * Kalimat akibat K32 untuk jendela konfirmasi dan pesan galat.
     */
    public static function kalimatMenunggu(int $totalLama, int $totalBaru): string
    {
        return StatusUbah::kalimatMenunggu($totalLama, $totalBaru);
    }

    /**
     * Kalimat jendela konfirmasi simpan dari hasil `periksa()` tanpa galat:
     * akibat K32 bila status akan kembali ke Menunggu, selain itu total baru
     * dan nomor dada atlet baru (K59).
     *
     * @param array<string, mixed> $hasil
     */
    public static function kalimatKonfirmasi(array $hasil): string
    {
        return StatusUbah::kalimatKonfirmasi($hasil);
    }

    /**
     * Nama nomor lomba dan kelompok umur yang sudah dimatikan tetapi masih
     * dipakai pendaftaran ini, untuk ditampilkan di halaman ubah (K98).
     *
     * @return array{nomor_lomba: array<int, string>, kelompok_umur: array<int, string>}
     */
    public function nonaktifTerpakai(int $id): array
    {
        $lama = $this->tersimpan->baca($id);

        if ($lama === null) {
            return ['nomor_lomba' => [], 'kelompok_umur' => []];
        }

        [$induk] = $this->tersimpan->indukDenganSimpanan($lama);
        $ku      = array_column($induk['kelompok_umur_nonaktif'], 'nama', 'id');
        $semuaKu = $ku + array_column($induk['kelompok_umur'], 'nama', 'id');
        $nomor   = [];

        foreach ($induk['nomor_lomba_nonaktif'] as $baris) {
            $nomor[(int) $baris['id']] = trim(($semuaKu[(int) $baris['kelompok_umur_id']] ?? '') . ' ' . NomorLombaModel::namaLengkap($baris));
        }

        return ['nomor_lomba' => $nomor, 'kelompok_umur' => $ku];
    }

    /**
     * Periksa ulang, K32, lalu tulis (`TulisUbah`). Dipanggil saat kunci NPSN dipegang.
     *
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function simpanTerkunci(array $lama, array $kiriman, bool $setujuMenunggu, string $hariIni, ?callable $dalamTransaksi = null): array
    {
        $rencana = $this->rencana->hitung($lama, $kiriman, $hariIni);

        if ($rencana['galat'] !== []) {
            return self::ringkas($rencana);
        }

        if ($rencana['kembali_menunggu'] && ! $setujuMenunggu) {
            $rencana['perlu_setuju'] = true;
            $rencana['galat']        = [['jalur' => 'setuju_menunggu', 'pesan' => StatusUbah::kalimatMenunggu($rencana['total_lama'], $rencana['total'])
                . ' Tekan Simpan perubahan lagi, lalu setujui di jendela konfirmasi.']];

            return self::ringkas($rencana);
        }

        try {
            $tulis = $this->tulis->tulis($lama, $rencana, $dalamTransaksi);
        } catch (Throwable $e) {
            // NISN yang baru saja didaftarkan kiriman lain: laporkan sebagai galat biasa.
            $ulang = $this->tersimpan->baca((int) $lama['pendaftaran']['id']);

            if ($ulang !== null) {
                $periksa = $this->rencana->hitung($ulang, $kiriman, $hariIni);

                if ($periksa['galat'] !== []) {
                    return self::ringkas($periksa);
                }
            }

            throw $e;
        }

        if ($tulis['pesan'] !== null) {
            return self::ringkas(self::gagal($lama, 'simpan', $tulis['pesan']));
        }

        $rencana['tersimpan']  = true;
        $rencana['nomor_dada'] = $tulis['nomor_dada'];
        $rencana['gabung']     = $tulis['gabung'];

        return self::ringkas($rencana);
    }

    /**
     * Hanya kunci yang dipakai pengendali dan uji; data rencana simpan dibuang.
     *
     * @param array<string, mixed> $rencana
     *
     * @return array<string, mixed>
     */
    private static function ringkas(array $rencana): array
    {
        return array_intersect_key($rencana, array_flip([
            'galat', 'id_pendaftaran', 'perlu_setuju', 'tersimpan', 'status_lama', 'status', 'kembali_menunggu',
            'total_lama', 'subtotal_individu', 'subtotal_beregu', 'total', 'atlet_baru', 'nomor_dada',
            'tim_dibebankan', 'tim_ditinggalkan', 'gabung',
        ]));
    }

    /**
     * @param array<string, mixed> $lama
     *
     * @return array<string, mixed>
     */
    private static function gagal(array $lama, string $jalur, string $pesan): array
    {
        $p = $lama['pendaftaran'];

        return [
            'galat'             => [['jalur' => $jalur, 'pesan' => $pesan]],
            'id_pendaftaran'    => (string) $p['id_pendaftaran'],
            'perlu_setuju'      => false,
            'tersimpan'         => false,
            'status_lama'       => (string) $p['status'],
            'status'            => (string) $p['status'],
            'kembali_menunggu'  => false,
            'total_lama'        => (int) $p['total'],
            'subtotal_individu' => (int) $p['subtotal_individu'],
            'subtotal_beregu'   => (int) $p['subtotal_beregu'],
            'total'             => (int) $p['total'],
            'atlet_baru'        => 0,
            'nomor_dada'        => [],
            'tim_dibebankan'    => [],
            'tim_ditinggalkan'  => [],
        ];
    }

    private function ambilKunci(string $kunci): bool
    {
        $baris = $this->db->query('SELECT GET_LOCK(?, ?) AS dapat', [$kunci, self::TUNGGU_KUNCI])->getRowArray();

        return (int) ($baris['dapat'] ?? 0) === 1;
    }

    private static function hariIni(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d');
    }
}
