<?php

namespace App\Libraries\Panel;

use App\Libraries\NomorWa;
use App\Libraries\Pendaftaran\BiayaPendaftaran;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\SekolahModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use RuntimeException;
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
 *              'jenis_kelamin' => 'L', 'nomor' => [12, 40]],
 *             ['nama' => '…', …],                                                                   // atlet baru
 *         ],
 *     ]
 *
 * Penanggung jawab dan atlet tersimpan yang tidak ada di kiriman dihapus.
 * Kunci `konfirmasi_susulan` diabaikan: admin tidak diminta mengonfirmasi
 * tim susulan (K57).
 *
 * ## Aturan
 *
 * - Pemeriksaan memakai `PemeriksaPendaftaran` (dan lewat itu
 *   `KelengkapanTim`) dengan data "sudah ada" tanpa pendaftaran ini: NISN
 *   milik pendaftaran lain ditolak, NISN milik pendaftaran ini diterima, dan
 *   tim serta kuota dihitung dari pendaftaran lain dengan NPSN yang sama.
 *   Buka-tutup pendaftaran tidak diperiksa.
 * - Biaya (asumsi 4 rencana Tahap 6): baris atlet-nomor (atlet tersimpan,
 *   nomor yang sama) dan tim (nomor beregu yang sama) yang tetap ada
 *   mempertahankan biaya tersimpan; baris baru memakai tarif saat ini (tim
 *   susulan tetap Rp0, K07). Subtotal dan total dihitung ulang dari baris itu.
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
 *     ]
 */
final class UbahPendaftaran
{
    /**
     * Lama menunggu kunci per NPSN, dalam detik.
     */
    public const TUNGGU_KUNCI = 10;

    /**
     * Isi `foto` pengganti untuk pemeriksa bila fotonya tidak dikirim ulang.
     */
    private const FOTO_ADA = 'ada';

    private BaseConnection $db;

    private DataIndukPendaftaran $data;

    private FotoPenanggungJawab $foto;

    public function __construct(?BaseConnection $db = null, ?DataIndukPendaftaran $data = null, ?FotoPenanggungJawab $foto = null)
    {
        $this->db   = $db ?? db_connect();
        $this->data = $data ?? new DataIndukPendaftaran();
        $this->foto = $foto ?? new FotoPenanggungJawab();
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
     *                                 'jenis_kelamin' => 'L', 'nomor' => [3, 7], 'nomor_dada' => '2001']],
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
        $lama = $this->bacaLama($id);

        if ($lama === null) {
            return null;
        }

        $p     = $lama['pendaftaran'];
        $hasil = [
            'pendaftaran' => [
                'id'                => (int) $p['id'],
                'id_pendaftaran'    => (string) $p['id_pendaftaran'],
                'status'            => (string) $p['status'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
            ],
        ];

        if ($kiriman === null) {
            $hasil['sekolah'] = [
                'jenjang'   => (string) $p['jenjang'],
                'kabupaten' => (string) $p['kabupaten'],
                'npsn'      => (string) ($p['npsn'] ?? ''),
                'nama'      => (string) $p['nama_sekolah'],
            ];

            $hasil['penanggung_jawab'] = array_map(fn (array $o): array => [
                'id'       => (int) $o['id'],
                'nama'     => (string) $o['nama'],
                'nomor_wa' => NomorWa::tampilan((string) $o['nomor_wa']),
                'foto'     => '',
                'ada_foto' => $this->adaFoto($o['foto']),
            ], array_values($lama['penanggung_jawab']));

            $hasil['atlet'] = array_map(static fn (array $a): array => [
                'id'            => (int) $a['id'],
                'nama'          => (string) $a['nama'],
                'nisn'          => (string) $a['nisn'],
                'tanggal_lahir' => (string) $a['tanggal_lahir'],
                'jenis_kelamin' => (string) $a['jenis_kelamin'],
                'nomor'         => array_keys($lama['nomor'][(int) $a['id']] ?? []),
                'nomor_dada'    => $a['nomor_dada'] === null || $a['nomor_dada'] === '' ? null : (string) $a['nomor_dada'],
            ], array_values($lama['atlet']));

            return $hasil;
        }

        $sekolah          = is_array($kiriman['sekolah'] ?? null) ? $kiriman['sekolah'] : [];
        $hasil['sekolah'] = [
            'jenjang'   => self::teks($sekolah['jenjang'] ?? null),
            'kabupaten' => self::teks($sekolah['kabupaten'] ?? null),
            'npsn'      => self::teks($sekolah['npsn'] ?? null),
            'nama'      => self::teks($sekolah['nama'] ?? null),
        ];

        $hasil['penanggung_jawab'] = [];

        foreach (self::daftar($kiriman['penanggung_jawab'] ?? null) as $o) {
            $o    = is_array($o) ? $o : [];
            $idPj = self::id($o['id'] ?? null);
            $baru = self::teks($o['foto'] ?? null);

            $hasil['penanggung_jawab'][] = [
                'id'       => isset($lama['penanggung_jawab'][$idPj]) ? $idPj : null,
                'nama'     => self::teks($o['nama'] ?? null),
                'nomor_wa' => self::teks($o['nomor_wa'] ?? null),
                'foto'     => FotoPenanggungJawab::baca($baru) !== null ? $baru : '',
                'ada_foto' => isset($lama['penanggung_jawab'][$idPj]) && $this->adaFoto($lama['penanggung_jawab'][$idPj]['foto']),
            ];
        }

        $hasil['atlet'] = [];

        foreach (self::daftar($kiriman['atlet'] ?? null) as $a) {
            $a       = is_array($a) ? $a : [];
            $idAtlet = self::id($a['id'] ?? null);
            $simpan  = $lama['atlet'][$idAtlet] ?? null;

            $hasil['atlet'][] = [
                'id'            => $simpan !== null ? $idAtlet : null,
                'nama'          => self::teks($a['nama'] ?? null),
                'nisn'          => self::teks($a['nisn'] ?? null),
                'tanggal_lahir' => self::teks($a['tanggal_lahir'] ?? null),
                'jenis_kelamin' => self::teks($a['jenis_kelamin'] ?? null),
                'nomor'         => array_values(array_filter(array_map(self::id(...), self::daftar($a['nomor'] ?? null)))),
                'nomor_dada'    => $simpan !== null && $simpan['nomor_dada'] !== null && $simpan['nomor_dada'] !== '' ? (string) $simpan['nomor_dada'] : null,
            ];
        }

        return $hasil;
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
        $lama = $this->bacaLama($id);

        if ($lama === null) {
            return null;
        }

        return self::ringkas($this->hitung($lama, $kiriman, $hariIni ?? self::hariIni()));
    }

    /**
     * Simpan perubahan.
     *
     * @param array<string, mixed> $kiriman
     * @param bool                 $setujuMenunggu Admin sudah menyetujui status kembali ke Menunggu (K32).
     *
     * @return array<string, mixed>|null
     */
    public function simpan(int $id, array $kiriman, bool $setujuMenunggu = false, ?string $hariIni = null): ?array
    {
        $hariIni ??= self::hariIni();
        $lama = $this->bacaLama($id);

        if ($lama === null) {
            return null;
        }

        $kunci = array_values(array_unique(array_filter([
            self::npsn($lama['pendaftaran']['npsn']),
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
            $lama = $this->bacaLama($id);

            if ($lama === null) {
                return null;
            }

            return $this->simpanTerkunci($lama, $kiriman, $setujuMenunggu, $hariIni);
        } finally {
            foreach ($dipegang as $nama) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$nama]);
            }
        }
    }

    // ---------------------------------------------------------------
    // Simpan
    // ---------------------------------------------------------------

    /**
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function simpanTerkunci(array $lama, array $kiriman, bool $setujuMenunggu, string $hariIni): array
    {
        $rencana = $this->hitung($lama, $kiriman, $hariIni);

        if ($rencana['galat'] !== []) {
            return self::ringkas($rencana);
        }

        if ($rencana['kembali_menunggu'] && ! $setujuMenunggu) {
            $rencana['perlu_setuju'] = true;
            $rencana['galat']        = [['jalur' => 'setuju_menunggu', 'pesan' => self::kalimatMenunggu($rencana['total_lama'], $rencana['total'])
                . ' Tekan Simpan perubahan lagi, lalu setujui di jendela konfirmasi.']];

            return self::ringkas($rencana);
        }

        $id        = (int) $lama['pendaftaran']['id'];
        $fotoBaru  = [];
        $fotoLama  = [];
        $nomorDada = [];

        try {
            foreach ($rencana['penanggung_jawab'] as $i => $orang) {
                if ($orang['foto_baru'] !== null) {
                    $fotoBaru[$i] = $this->foto->simpan($orang['foto_baru']);
                }
            }

            // Penanda gagal transaksi CodeIgniter melekat dari kegagalan sebelumnya.
            $this->db->resetTransStatus();
            $this->db->transBegin();

            $kini = $this->baca('SELECT status, total, diubah_pada FROM pendaftaran WHERE id = ? FOR UPDATE', [$id])[0] ?? null;

            if ($kini === null || $kini['status'] !== $lama['pendaftaran']['status'] || (int) $kini['total'] !== (int) $lama['pendaftaran']['total']
                || (string) $kini['diubah_pada'] !== (string) $lama['pendaftaran']['diubah_pada']) {
                $this->db->transRollback();
                $this->hapusFoto($fotoBaru);

                return self::ringkas(self::gagal($lama, 'simpan', 'Pendaftaran ini baru saja diubah di tempat lain. Muat ulang halaman ini, lalu ubah lagi.'));
            }

            $sekarang = self::sekarang();
            $menunggu = $rencana['status'] === 'menunggu';

            $this->pastikan($this->db->table('pendaftaran')->where('id', $id)->update([
                'jenjang'           => $rencana['sekolah']['jenjang'],
                'npsn'              => $rencana['sekolah']['npsn'],
                'nama_sekolah'      => $rencana['sekolah']['nama_sekolah'],
                'kabupaten'         => $rencana['sekolah']['kabupaten'],
                'subtotal_individu' => $rencana['subtotal_individu'],
                'subtotal_beregu'   => $rencana['subtotal_beregu'],
                'total'             => $rencana['total'],
                'status'            => $rencana['status'],
                'waktu_lunas'       => $menunggu ? null : $lama['pendaftaran']['waktu_lunas'],
                'diubah_pada'       => $sekarang,
            ]));

            // Penanggung jawab.
            $dipakaiPj = array_filter(array_column($rencana['penanggung_jawab'], 'id'));

            foreach ($lama['penanggung_jawab'] as $idPj => $orang) {
                if (! in_array($idPj, $dipakaiPj, true)) {
                    $this->pastikan($this->db->table('penanggung_jawab')->where('id', $idPj)->delete());
                    $fotoLama[] = $orang['foto'];
                }
            }

            foreach ($rencana['penanggung_jawab'] as $i => $orang) {
                $baris = ['nama' => $orang['nama'], 'nomor_wa' => $orang['nomor_wa'], 'diubah_pada' => $sekarang];

                if (isset($fotoBaru[$i])) {
                    $baris['foto'] = $fotoBaru[$i];
                }

                if ($orang['id'] !== null) {
                    if (isset($fotoBaru[$i])) {
                        $fotoLama[] = $lama['penanggung_jawab'][$orang['id']]['foto'];
                    }

                    $this->pastikan($this->db->table('penanggung_jawab')->where('id', $orang['id'])->update($baris));
                } else {
                    $this->pastikan($this->db->table('penanggung_jawab')->insert($baris + ['pendaftaran_id' => $id, 'dibuat_pada' => $sekarang]));
                }
            }

            // Atlet yang dihapus (nomor atletnya ikut terhapus lewat kunci asing).
            $dipakaiAtlet = array_filter(array_column($rencana['atlet'], 'id'));

            foreach (array_keys($lama['atlet']) as $idAtlet) {
                if (! in_array($idAtlet, $dipakaiAtlet, true)) {
                    $this->pastikan($this->db->table('atlet')->where('id', $idAtlet)->delete());
                }
            }

            // NISN yang bertukar antar-atlet pendaftaran ini: kosongkan dulu
            // dengan nilai sementara supaya kunci unik tidak menolak.
            foreach ($rencana['atlet'] as $atlet) {
                if ($atlet['id'] !== null && $lama['atlet'][$atlet['id']]['nisn'] !== $atlet['nisn']) {
                    $this->pastikan($this->db->table('atlet')->where('id', $atlet['id'])->update(['nisn' => sprintf('X%09d', $atlet['id'])]));
                }
            }

            foreach ($rencana['atlet'] as $atlet) {
                $baris = [
                    'nama'             => $atlet['nama'],
                    'nisn'             => $atlet['nisn'],
                    'tanggal_lahir'    => $atlet['tanggal_lahir'],
                    'jenis_kelamin'    => $atlet['jenis_kelamin'],
                    'kelompok_umur_id' => $atlet['kelompok_umur_id'],
                    'diubah_pada'      => $sekarang,
                ];

                if ($atlet['id'] !== null) {
                    // Nomor dada tidak disentuh.
                    $idAtlet = $atlet['id'];
                    $this->pastikan($this->db->table('atlet')->where('id', $idAtlet)->update($baris));

                    $simpan = $lama['nomor'][$idAtlet] ?? [];
                    $hapus  = array_diff(array_keys($simpan), array_column($atlet['nomor'], 'nomor_lomba_id'));

                    if ($hapus !== []) {
                        $this->pastikan($this->db->table('atlet_nomor')->where('atlet_id', $idAtlet)->whereIn('nomor_lomba_id', array_values($hapus))->delete());
                    }
                } else {
                    $this->pastikan($this->db->table('atlet')->insert($baris + ['pendaftaran_id' => $id, 'dibuat_pada' => $sekarang]));
                    $idAtlet = (int) $this->db->insertID();
                    $simpan  = [];
                    self::pastikanId($idAtlet);
                }

                foreach ($atlet['nomor'] as $nomor) {
                    if (array_key_exists($nomor['nomor_lomba_id'], $simpan)) {
                        continue;
                    }

                    $this->pastikan($this->db->table('atlet_nomor')->insert([
                        'atlet_id'       => $idAtlet,
                        'nomor_lomba_id' => $nomor['nomor_lomba_id'],
                        'biaya'          => $nomor['biaya'],
                        'dibuat_pada'    => $sekarang,
                        'diubah_pada'    => $sekarang,
                    ]));
                }
            }

            // Tim beregu.
            $timBaru = array_column($rencana['beregu'], 'biaya_tim', 'nomor_lomba_id');
            $hapus   = array_diff(array_keys($lama['beregu']), array_keys($timBaru));

            if ($hapus !== []) {
                $this->pastikan($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->whereIn('nomor_lomba_id', array_values($hapus))->delete());
            }

            foreach ($timBaru as $nomorId => $biaya) {
                if (! array_key_exists($nomorId, $lama['beregu'])) {
                    $this->pastikan($this->db->table('pendaftaran_beregu')->insert([
                        'pendaftaran_id' => $id,
                        'nomor_lomba_id' => $nomorId,
                        'biaya_tim'      => $biaya,
                        'dibuat_pada'    => $sekarang,
                        'diubah_pada'    => $sekarang,
                    ]));
                }
            }

            // K59: Lunas dan total tetap → atlet tanpa nomor langsung mendapat nomor.
            if ($rencana['status'] === 'lunas') {
                $beri = (new StatusPembayaran($this->db))->beriNomorDada($id);

                if ($beri['penuh'] !== []) {
                    $this->db->transRollback();
                    $this->hapusFoto($fotoBaru);

                    return self::ringkas(self::gagal($lama, 'simpan', 'Perubahan belum disimpan. Pendaftaran ini sudah Lunas, jadi atlet baru langsung mendapat nomor dada, '
                        . 'tetapi awalan nomor dada ' . implode(', ', $beri['penuh']) . ' sudah penuh atau belum ada. '
                        . 'Tambahkan awalan baru di Pengaturan, Kelompok Umur, lalu simpan lagi.'));
                }

                $nomorDada = $beri['nomor'];
            }

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi ubah pendaftaran gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            while ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            $this->hapusFoto($fotoBaru);

            // NISN yang baru saja didaftarkan kiriman lain: laporkan sebagai galat biasa.
            $ulang = $this->bacaLama((int) $lama['pendaftaran']['id']);

            if ($ulang !== null) {
                $periksa = $this->hitung($ulang, $kiriman, $hariIni);

                if ($periksa['galat'] !== []) {
                    return self::ringkas($periksa);
                }
            }

            throw $e;
        }

        $this->hapusFoto($fotoLama);

        $rencana['tersimpan']  = true;
        $rencana['nomor_dada'] = $nomorDada;

        return self::ringkas($rencana);
    }

    // ---------------------------------------------------------------
    // Pemeriksaan dan biaya
    // ---------------------------------------------------------------

    /**
     * Periksa kiriman terhadap data tersimpan dan susun rencana simpan.
     *
     * @param array<string, mixed> $lama
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function hitung(array $lama, array $kiriman, string $hariIni): array
    {
        $galat   = [];
        $pj      = self::daftar($kiriman['penanggung_jawab'] ?? null);
        $atlet   = self::daftar($kiriman['atlet'] ?? null);
        $idPj    = [];
        $idAtlet = [];
        $fotoIsi = [];

        unset($kiriman[PemeriksaPendaftaran::KUNCI_KONFIRMASI]);

        foreach ($pj as $i => $orang) {
            $orang = is_array($orang) ? $orang : [];
            $id    = self::id($orang['id'] ?? null);
            $ke    = 'Penanggung jawab ' . ($i + 1);

            if ($id !== null && (! isset($lama['penanggung_jawab'][$id]) || in_array($id, $idPj, true))) {
                $galat[] = ['jalur' => "penanggung_jawab.{$i}", 'pesan' => "{$ke}: data tersimpannya tidak dikenal. Muat ulang halaman ini, lalu ubah lagi."];
                $id      = null;
            }

            $idPj[$i]    = $id;
            $fotoIsi[$i] = null;
            $foto        = self::teks($orang['foto'] ?? null);

            if ($foto !== '') {
                $fotoIsi[$i] = FotoPenanggungJawab::baca($foto);

                if ($fotoIsi[$i] === null) {
                    $galat[] = ['jalur' => "penanggung_jawab.{$i}.foto", 'pesan' => "{$ke}: foto belum bisa dibaca. Pilih ulang fotonya."];
                }

                $orang['foto'] = self::FOTO_ADA;
            } else {
                $orang['foto'] = $id !== null && $this->adaFoto($lama['penanggung_jawab'][$id]['foto']) ? self::FOTO_ADA : '';
            }

            $pj[$i] = $orang;
        }

        foreach ($atlet as $i => $isian) {
            $isian = is_array($isian) ? $isian : [];
            $id    = self::id($isian['id'] ?? null);

            if ($id !== null && (! isset($lama['atlet'][$id]) || in_array($id, $idAtlet, true))) {
                $galat[] = ['jalur' => "atlet.{$i}", 'pesan' => 'Atlet ' . ($i + 1) . ': data tersimpannya tidak dikenal. Muat ulang halaman ini, lalu ubah lagi.'];
                $id      = null;
            }

            $idAtlet[$i] = $id;
        }

        $kiriman['penanggung_jawab'] = $pj;
        $kiriman['atlet']            = $atlet;

        $pendaftaranId = (int) $lama['pendaftaran']['id'];
        $npsn          = PemeriksaPendaftaran::npsnKiriman($kiriman);
        $ada           = [
            'referensi'      => $npsn !== null ? model(SekolahModel::class)->cariNpsn($npsn) : null,
            'nisn_terdaftar' => $this->nisnLain($pendaftaranId, PemeriksaPendaftaran::nisnKiriman($kiriman)),
        ] + $this->sekolahLain($pendaftaranId, $npsn);

        $periksa = (new PemeriksaPendaftaran($this->data->induk(), $ada, $hariIni))->periksa($kiriman, false);

        foreach ($periksa['galat'] as $g) {
            $galat[] = ['jalur' => $g['jalur'], 'pesan' => $g['pesan']];
        }

        $statusLama = (string) $lama['pendaftaran']['status'];
        $totalLama  = (int) $lama['pendaftaran']['total'];
        $rencana    = [
            'galat'             => $galat,
            'id_pendaftaran'    => (string) $lama['pendaftaran']['id_pendaftaran'],
            'perlu_setuju'      => false,
            'tersimpan'         => false,
            'status_lama'       => $statusLama,
            'status'            => $statusLama,
            'kembali_menunggu'  => false,
            'total_lama'        => $totalLama,
            'subtotal_individu' => (int) $lama['pendaftaran']['subtotal_individu'],
            'subtotal_beregu'   => (int) $lama['pendaftaran']['subtotal_beregu'],
            'total'             => $totalLama,
            'atlet_baru'        => count(array_filter($idAtlet, static fn (?int $id): bool => $id === null)),
            'nomor_dada'        => [],
        ];

        if ($galat !== []) {
            return $rencana;
        }

        $data = $periksa['data'];

        // Biaya individu: baris tersimpan dipertahankan, baris baru tarif saat ini.
        $biayaIndividu = [];

        foreach ($data['atlet'] as $i => $a) {
            $simpan = $idAtlet[$i] !== null ? ($lama['nomor'][$idAtlet[$i]] ?? []) : [];

            foreach ($a['nomor'] as $j => $nomor) {
                if (array_key_exists($nomor['nomor_lomba_id'], $simpan)) {
                    $nomor['biaya'] = $simpan[$nomor['nomor_lomba_id']];
                }

                $data['atlet'][$i]['nomor'][$j] = $nomor;

                if ($nomor['biaya'] !== null) {
                    $biayaIndividu[] = (int) $nomor['biaya'];
                }
            }

            $data['atlet'][$i]['id'] = $idAtlet[$i];
        }

        // Biaya tim: tim tersimpan dipertahankan, tim baru tarif saat ini (susulan Rp0).
        $biayaBeregu = [];

        foreach ($data['beregu'] as $j => $tim) {
            if (array_key_exists($tim['nomor_lomba_id'], $lama['beregu'])) {
                $tim['biaya_tim'] = $lama['beregu'][$tim['nomor_lomba_id']];
            }

            $data['beregu'][$j] = $tim;
            $biayaBeregu[]      = (int) $tim['biaya_tim'];
        }

        foreach ($data['penanggung_jawab'] as $i => $orang) {
            $data['penanggung_jawab'][$i] = [
                'id'        => $idPj[$i],
                'nama'      => $orang['nama'],
                'nomor_wa'  => $orang['nomor_wa'],
                'foto_baru' => $fotoIsi[$i],
            ];
        }

        $total   = BiayaPendaftaran::hitung($biayaIndividu, $biayaBeregu);
        $kembali = $statusLama === 'lunas' && $total['total'] !== $totalLama;

        return [
            'status'           => $kembali ? 'menunggu' : $statusLama,
            'kembali_menunggu' => $kembali,
            'sekolah'          => $data['sekolah'],
            'penanggung_jawab' => $data['penanggung_jawab'],
            'atlet'            => $data['atlet'],
            'beregu'           => $data['beregu'],
        ] + $total + $rencana;
    }

    /**
     * Kalimat akibat K32 untuk jendela konfirmasi dan pesan galat.
     */
    public static function kalimatMenunggu(int $totalLama, int $totalBaru): string
    {
        return 'Pendaftaran ini sudah Lunas, dan perubahan ini mengubah total dari ' . rupiah($totalLama) . ' menjadi ' . rupiah($totalBaru) . '. '
            . 'Setelah disimpan, status kembali menjadi Menunggu Konfirmasi Pembayaran dan perlu ditandai Lunas lagi setelah selisihnya diurus. '
            . 'Nomor dada yang sudah dibagikan tetap dipegang atletnya.';
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
        if ($hasil['kembali_menunggu']) {
            return self::kalimatMenunggu((int) $hasil['total_lama'], (int) $hasil['total']);
        }

        $kalimat = 'Perubahan pendaftaran ' . $hasil['id_pendaftaran'] . ' akan disimpan. '
            . ((int) $hasil['total'] === (int) $hasil['total_lama']
                ? 'Total tetap ' . rupiah((int) $hasil['total']) . '.'
                : 'Total berubah dari ' . rupiah((int) $hasil['total_lama']) . ' menjadi ' . rupiah((int) $hasil['total']) . '.');

        if ($hasil['status'] === 'lunas' && $hasil['atlet_baru'] > 0) {
            $kalimat .= ' Pendaftaran ini sudah Lunas, jadi ' . $hasil['atlet_baru'] . ' atlet baru langsung mendapat nomor dada, dan nomor itu tidak bisa ditarik kembali.';
        }

        return $kalimat;
    }

    /**
     * NISN kiriman yang sudah dipakai pendaftaran lain.
     *
     * @param list<string> $nisn
     *
     * @return list<string>
     */
    private function nisnLain(int $pendaftaranId, array $nisn): array
    {
        if ($nisn === []) {
            return [];
        }

        $baris = $this->db->table('atlet')->select('nisn')
            ->whereIn('nisn', $nisn)
            ->where('pendaftaran_id !=', $pendaftaranId)
            ->get()->getResultArray();

        return array_map(static fn (array $b): string => (string) $b['nisn'], $baris);
    }

    /**
     * Tim beregu (nomor => jumlah anggota) dan jumlah atlet per nomor
     * individu milik NPSN ini dari pendaftaran lain (sama dengan
     * `DataIndukPendaftaran::dataSekolah()` tanpa pendaftaran ini).
     *
     * @return array{tim: array<int, int>, individu: array<int, int>}
     */
    private function sekolahLain(int $pendaftaranId, ?string $npsn): array
    {
        if ($npsn === null) {
            return ['tim' => [], 'individu' => []];
        }

        $jumlah = [];
        $baris  = $this->db->table('atlet_nomor an')
            ->select('an.nomor_lomba_id, nl.jenis, COUNT(*) AS jumlah')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('p.npsn', $npsn)
            ->where('p.id !=', $pendaftaranId)
            ->groupBy('an.nomor_lomba_id, nl.jenis')
            ->get()->getResultArray();

        foreach ($baris as $b) {
            $jumlah[(string) $b['jenis']][(int) $b['nomor_lomba_id']] = (int) $b['jumlah'];
        }

        $tim = [];
        $ada = $this->db->table('pendaftaran_beregu pb')->distinct()
            ->select('pb.nomor_lomba_id')
            ->join('pendaftaran p', 'p.id = pb.pendaftaran_id')
            ->where('p.npsn', $npsn)
            ->where('p.id !=', $pendaftaranId)
            ->get()->getResultArray();

        foreach ($ada as $b) {
            $tim[(int) $b['nomor_lomba_id']] = $jumlah['beregu'][(int) $b['nomor_lomba_id']] ?? 0;
        }

        return ['tim' => $tim, 'individu' => $jumlah['individu'] ?? []];
    }

    // ---------------------------------------------------------------
    // Data tersimpan
    // ---------------------------------------------------------------

    /**
     * Data tersimpan satu pendaftaran, atau `null`.
     *
     * @return array{pendaftaran: array<string, mixed>, penanggung_jawab: array<int, array<string, mixed>>, atlet: array<int, array<string, mixed>>, nomor: array<int, array<int, int|null>>, beregu: array<int, int>}|null
     */
    private function bacaLama(int $id): ?array
    {
        $p = $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();

        if (! is_array($p)) {
            return null;
        }

        $pj = [];

        foreach ($this->db->table('penanggung_jawab')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray() as $b) {
            $pj[(int) $b['id']] = $b;
        }

        $atlet = [];

        foreach ($this->db->table('atlet')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray() as $b) {
            $atlet[(int) $b['id']] = $b;
        }

        $nomor = [];
        $baris = $this->db->table('atlet_nomor an')
            ->select('an.atlet_id, an.nomor_lomba_id, an.biaya')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('a.pendaftaran_id', $id)
            ->orderBy('an.atlet_id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();

        foreach ($baris as $b) {
            $nomor[(int) $b['atlet_id']][(int) $b['nomor_lomba_id']] = $b['biaya'] === null ? null : (int) $b['biaya'];
        }

        $beregu = [];

        foreach ($this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $id)->get()->getResultArray() as $b) {
            $beregu[(int) $b['nomor_lomba_id']] = (int) $b['biaya_tim'];
        }

        return ['pendaftaran' => $p, 'penanggung_jawab' => $pj, 'atlet' => $atlet, 'nomor' => $nomor, 'beregu' => $beregu];
    }

    private function adaFoto(mixed $jalur): bool
    {
        $penuh = $this->foto->jalurPenuh(is_string($jalur) ? $jalur : null);

        return $penuh !== null && is_file($penuh);
    }

    /**
     * @param array<int|string, string|null> $jalur
     */
    private function hapusFoto(array $jalur): void
    {
        foreach ($jalur as $satu) {
            $this->foto->hapus($satu);
        }
    }

    // ---------------------------------------------------------------
    // Bantu
    // ---------------------------------------------------------------

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
        ];
    }

    private function ambilKunci(string $kunci): bool
    {
        $baris = $this->db->query('SELECT GET_LOCK(?, ?) AS dapat', [$kunci, self::TUNGGU_KUNCI])->getRowArray();

        return (int) ($baris['dapat'] ?? 0) === 1;
    }

    /**
     * @param list<int|string> $ikat
     *
     * @return list<array<string, mixed>>
     */
    private function baca(string $sql, array $ikat): array
    {
        $hasil = $this->db->query($sql, $ikat);

        if (! is_object($hasil)) {
            $galat = $this->db->error();

            throw new DatabaseException('Data pendaftaran gagal dibaca: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.');
        }

        return $hasil->getResultArray();
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Data pendaftaran gagal ditulis: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }

    private static function pastikanId(int $id): void
    {
        if ($id < 1) {
            throw new RuntimeException('Atlet baru gagal ditulis.');
        }
    }

    private static function npsn(mixed $nilai): ?string
    {
        return is_string($nilai) && preg_match('/^\d{8}$/D', $nilai) === 1 ? $nilai : null;
    }

    private static function id(mixed $nilai): ?int
    {
        return (is_int($nilai) || (is_string($nilai) && ctype_digit($nilai))) && (int) $nilai > 0 ? (int) $nilai : null;
    }

    private static function teks(mixed $nilai): string
    {
        return is_string($nilai) || is_int($nilai) ? (string) $nilai : '';
    }

    /**
     * @return list<mixed>
     */
    private static function daftar(mixed $nilai): array
    {
        return is_array($nilai) ? array_values($nilai) : [];
    }

    private static function hariIni(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d');
    }

    private static function sekarang(): string
    {
        return Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');
    }
}
