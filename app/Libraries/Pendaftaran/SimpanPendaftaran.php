<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\BuktiPendaftaran;
use App\Libraries\DataPendaftaran;
use App\Libraries\Jenjang;
use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Panel\UsulanNamaSekolah;
use App\Models\BuktiBayarModel;
use App\Models\AtletModel;
use App\Models\AtletNomorModel;
use App\Models\PenanggungJawabModel;
use App\Models\PendaftaranBereguModel;
use App\Models\PendaftaranModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Satu-satunya jalan menulis pendaftaran baru (rencana Tahap 4, "Penyimpanan").
 *
 * 1. Kunci bernama MySQL per NPSN, supaya dua kiriman bersamaan dari sekolah
 *    yang sama tidak sama-sama membuat regu yang sama atau melewati
 *    kuota (6.6, K51).
 * 2. Data yang sudah ada dibaca ulang dan seluruh kiriman diperiksa
 *    (`PemeriksaPendaftaran`).
 * 3. Bukti transfer wajib (K90): `bukti_bayar` berupa data URL gambar. Tanpa
 *    bukti, pendaftaran tidak masuk sistem (galat langkah 5).
 * 4. Foto penanggung jawab dan bukti transfer disimpan di luar `public/`.
 * 5. Satu transaksi: pendaftaran (status menunggu), ID
 *    pendaftaran dari nomor baris (6.8), penanggung jawab, atlet, nomor atlet
 *    (biaya individu disalin; regu dan peran untuk beregu), regu beregu,
 *    inti lama yang diturunkan menjadi cadangan (K105), dan baris bukti bayar.
 * 6. Bila gagal, transaksi dibatalkan dan foto serta bukti dihapus. NISN yang ternyata
 *    terdaftar pada saat yang sama ditolak kunci unik basis data dan
 *    dilaporkan sebagai galat NISN biasa.
 *
 * Kirim ulang (K118, B2): wizard mengirim `kunci_kirim` acak yang sama untuk
 * satu isian sampai tersimpan. Bila kunci itu sudah dipakai pendaftaran yang
 * tersimpan, tidak ada yang ditulis; hasilnya pendaftaran yang sudah ada
 * (`ulang` bernilai `true`) dengan rincian yang dibaca dari basis data.
 * Kunci diperiksa sebelum kunci NPSN, lagi di dalamnya, dan sekali lagi bila
 * penulisan gagal (dua kiriman bersamaan tanpa NPSN ditahan kunci unik
 * `pendaftaran.kunci_kirim`).
 *
 * Pemeriksaan buka-tutup pendaftaran (6.1) dilakukan pengendali sebelum
 * memanggil kelas ini.
 *
 *     $hasil = (new SimpanPendaftaran())->simpan($kiriman);
 *
 *     $hasil['galat'];        // list galat seperti PemeriksaPendaftaran, [] bila tersimpan
 *     $hasil['pendaftaran'];  // ['id' => 1, 'id_pendaftaran' => 'MAC2A0000126'] atau null
 *     $hasil['rincian'];      // rincian biaya (PemeriksaPendaftaran) atau null
 *     $hasil['ulang'];        // true bila kunci_kirim sudah pernah tersimpan
 */
final class SimpanPendaftaran
{
    /**
     * Lama menunggu kunci per NPSN, dalam detik.
     */
    public const TUNGGU_KUNCI = 10;

    /**
     * Bentuk `kunci_kirim` yang diterima; selain itu kiriman diperlakukan
     * tanpa kunci (halaman wizard lama).
     */
    public const POLA_KUNCI_KIRIM = '/^[A-Za-z0-9_-]{16,64}$/D';

    public const PESAN_TANPA_BUKTI = 'Bukti transfer belum dipilih. Transfer dulu sebesar total, lalu pilih foto atau tangkapan layar bukti transfer (JPG, PNG, atau WEBP).';

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
     * @param array<string, mixed> $kiriman Lihat `PemeriksaPendaftaran`.
     * @param string|null          $hariIni `Y-m-d`; bawaan hari ini (zona waktu aplikasi).
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: array{id: int, id_pendaftaran: string}|null, rincian: array<string, mixed>|null, ulang: bool}
     */
    public function simpan(array $kiriman, ?string $hariIni = null): array
    {
        $hariIni ??= Time::now(config('App')->appTimezone)->format('Y-m-d');
        $ulang = $this->tersimpan(self::kunciKirim($kiriman));

        if ($ulang !== null) {
            return $ulang;
        }

        $npsn  = PemeriksaPendaftaran::npsnKiriman($kiriman);
        $kunci = $npsn !== null ? 'milklife-pendaftaran-' . $npsn : null;

        if ($kunci !== null && ! $this->ambilKunci($kunci)) {
            return self::gagal([[
                'langkah' => 5,
                'jalur'   => 'kirim',
                'pesan'   => 'Pendaftaran lain dari sekolah ini sedang disimpan. Tunggu beberapa detik, lalu tekan Kirim Pendaftaran lagi.',
            ]]);
        }

        try {
            return $this->simpanTerkunci($kiriman, $hariIni);
        } finally {
            if ($kunci !== null) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
            }
        }
    }

    /**
     * @param array<string, mixed> $kiriman
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: array{id: int, id_pendaftaran: string}|null, rincian: array<string, mixed>|null, ulang: bool}
     */
    private function simpanTerkunci(array $kiriman, string $hariIni): array
    {
        $kunciKirim = self::kunciKirim($kiriman);
        $ulang      = $this->tersimpan($kunciKirim);

        if ($ulang !== null) {
            return $ulang;
        }

        $hasil = $this->periksa($kiriman, $hariIni);

        if ($hasil['galat'] !== []) {
            return self::gagal($hasil['galat']);
        }

        $isiBukti = BerkasBuktiBayar::bacaDataUrl($kiriman['bukti_bayar'] ?? null);

        if ($isiBukti === null) {
            return self::gagal([['langkah' => 5, 'jalur' => 'bukti_bayar', 'pesan' => self::PESAN_TANPA_BUKTI]]);
        }

        $berkasBukti = new BerkasBuktiBayar();

        try {
            $bukti = $berkasBukti->simpanIsi($isiBukti);
        } catch (RuntimeException $e) {
            return self::gagal([['langkah' => 5, 'jalur' => 'bukti_bayar', 'pesan' => $e->getMessage()]]);
        }

        $data  = $hasil['data'];
        $foto  = [];
        $seri  = setting('Acara.seri');
        $tahun = setting('Acara.tahun');

        if (! is_numeric($seri) || ! is_numeric($tahun)) {
            $berkasBukti->hapus($bukti['berkas']);

            throw new RuntimeException('Seri dan tahun acara belum diisi; ID pendaftaran tidak bisa dibuat.');
        }

        try {
            foreach ($data['penanggung_jawab'] as $i => $orang) {
                $foto[$i] = $this->foto->simpan((string) $orang['foto']);
            }

            // Penanda gagal transaksi CodeIgniter melekat dari kegagalan sebelumnya.
            $this->db->resetTransStatus();
            $this->db->transBegin();

            $pendaftaran = model(PendaftaranModel::class);
            $id          = $pendaftaran->insert([
                'jenjang'           => $data['sekolah']['jenjang'],
                'npsn'              => $data['sekolah']['npsn'],
                'nama_sekolah'      => $data['sekolah']['nama_sekolah'],
                'kabupaten'         => $data['sekolah']['kabupaten'],
                'subtotal_individu' => $data['subtotal_individu'],
                'subtotal_beregu'   => $data['subtotal_beregu'],
                'total'             => $data['total'],
                'status'            => 'menunggu',
                'kode_bukti'        => BuktiPendaftaran::kodeBaru(),
                'kunci_kirim'       => $kunciKirim,
            ], true);
            self::pastikan($id);

            $idPendaftaran = IdPendaftaran::buat((int) $seri, $data['sekolah']['jenjang'], (int) $id, (int) $tahun);
            self::pastikan($pendaftaran->update($id, ['id_pendaftaran' => $idPendaftaran]));

            // Usulan nama sekolah dari pendaftar (K99), diputuskan admin di menu Sekolah.
            if (($data['sekolah']['nama_usulan'] ?? null) !== null) {
                (new UsulanNamaSekolah($this->db))->catat(
                    (string) $data['sekolah']['npsn'],
                    (int) $id,
                    (string) $data['sekolah']['nama_resmi'],
                    (string) $data['sekolah']['nama_usulan'],
                );
            }

            foreach ($data['penanggung_jawab'] as $i => $orang) {
                self::pastikan(model(PenanggungJawabModel::class)->insert([
                    'pendaftaran_id' => $id,
                    'nama'           => $orang['nama'],
                    'nomor_wa'       => $orang['nomor_wa'],
                    'foto'           => $foto[$i],
                ]));
            }

            foreach ($data['atlet'] as $atlet) {
                $atletId = model(AtletModel::class)->insert([
                    'pendaftaran_id'   => $id,
                    'nama'             => $atlet['nama'],
                    'nisn'             => $atlet['nisn'],
                    'tanggal_lahir'    => $atlet['tanggal_lahir'],
                    'jenis_kelamin'    => $atlet['jenis_kelamin'],
                    'kelompok_umur_id' => $atlet['kelompok_umur_id'],
                ], true);
                self::pastikan($atletId);

                foreach ($atlet['nomor'] as $nomor) {
                    self::pastikan(model(AtletNomorModel::class)->insert([
                        'atlet_id'       => $atletId,
                        'nomor_lomba_id' => $nomor['nomor_lomba_id'],
                        'biaya'          => $nomor['biaya'],
                        'regu'           => $nomor['regu'] ?? null,
                        'peran'          => $nomor['peran'] ?? null,
                    ]));
                }
            }

            foreach ($data['beregu'] as $tim) {
                self::pastikan(model(PendaftaranBereguModel::class)->insert([
                    'pendaftaran_id' => $id,
                    'nomor_lomba_id' => $tim['nomor_lomba_id'],
                    'regu'           => $tim['regu'],
                    'biaya_tim'      => $tim['biaya_tim'],
                ]));
            }

            $this->turunkanInti($data['turun_cadangan'] ?? []);

            self::pastikan(model(BuktiBayarModel::class)->insert(['pendaftaran_id' => $id] + $bukti));

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi pendaftaran gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            foreach ($foto as $jalur) {
                $this->foto->hapus($jalur);
            }

            $berkasBukti->hapus($bukti['berkas']);

            // Kiriman yang sama (kunci kirim sama) baru saja tersimpan lebih dulu.
            $ulang = $this->tersimpan($kunciKirim);

            if ($ulang !== null) {
                return $ulang;
            }

            // Kiriman lain mungkin baru saja mendaftarkan NISN yang sama:
            // laporkan sebagai galat biasa bila pemeriksaan ulang menemukannya.
            $ulang = $this->periksa($kiriman, $hariIni);

            if ($ulang['galat'] !== []) {
                return self::gagal($ulang['galat']);
            }

            throw $e;
        }

        return [
            'galat'       => [],
            'pendaftaran' => ['id' => (int) $id, 'id_pendaftaran' => $idPendaftaran],
            'rincian'     => $hasil['rincian'],
            'ulang'       => false,
        ];
    }

    /**
     * Hasil `simpan()` untuk kiriman yang `kunci_kirim`-nya sudah tersimpan,
     * atau `null`. Hanya membaca; dipakai pengendali supaya kirim ulang tetap
     * dijawab walaupun pendaftaran baru saja ditutup.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return array{galat: list<never>, pendaftaran: array{id: int, id_pendaftaran: string}, rincian: array<string, mixed>, ulang: true}|null
     */
    public function sudahTersimpan(array $kiriman): ?array
    {
        return $this->tersimpan(self::kunciKirim($kiriman));
    }

    /**
     * `kunci_kirim` dari kiriman, atau `null` bila tidak ada atau bentuknya salah.
     *
     * @param array<string, mixed> $kiriman
     */
    public static function kunciKirim(array $kiriman): ?string
    {
        $kunci = $kiriman['kunci_kirim'] ?? null;

        return is_string($kunci) && preg_match(self::POLA_KUNCI_KIRIM, $kunci) === 1 ? $kunci : null;
    }

    /**
     * Pendaftaran yang sudah tersimpan dengan kunci kirim ini, dalam bentuk
     * hasil `simpan()`, atau `null`. Hanya membaca.
     *
     * Rinciannya dibaca dari basis data (nilai tersimpan, rancangan 6.7) dalam
     * bentuk rincian `PemeriksaPendaftaran` yang dipakai wizard: `sekolah`,
     * `individu` (nama, nisn, kelompok_umur, nomor, biaya), `beregu` (nomor,
     * regu, regu_tunggal, biaya, anggota), dan subtotal serta total.
     *
     * @return array{galat: list<never>, pendaftaran: array{id: int, id_pendaftaran: string}, rincian: array<string, mixed>, ulang: true}|null
     */
    private function tersimpan(?string $kunciKirim): ?array
    {
        if ($kunciKirim === null) {
            return null;
        }

        $baris = $this->db->table('pendaftaran')->select('id, id_pendaftaran')->where('kunci_kirim', $kunciKirim)->get()->getRowArray();

        if (! is_array($baris) || (string) ($baris['id_pendaftaran'] ?? '') === '') {
            return null;
        }

        $p = (new DataPendaftaran($this->db))->cariId((string) $baris['id_pendaftaran']);

        if ($p === null) {
            return null;
        }

        return [
            'galat'       => [],
            'pendaftaran' => ['id' => (int) $baris['id'], 'id_pendaftaran' => (string) $baris['id_pendaftaran']],
            'rincian'     => [
                'sekolah' => [
                    'nama'          => (string) $p['nama_sekolah'],
                    'nama_resmi'    => (string) $p['nama_sekolah'],
                    'npsn'          => $p['npsn'],
                    'kabupaten'     => (string) $p['kabupaten'],
                    'jenjang'       => (string) $p['jenjang'],
                    'jenjang_label' => (string) Jenjang::label((string) $p['jenjang']),
                ],
                'individu' => array_map(static fn (array $b): array => [
                    'nama'          => (string) $b['nama'],
                    'nisn'          => (string) $b['nisn'],
                    'kelompok_umur' => (string) $b['kelompok_umur'],
                    'nomor'         => (string) $b['nomor_lomba'],
                    'biaya'         => (int) $b['biaya'],
                ], $p['individu']),
                'beregu' => array_map(static fn (array $t): array => [
                    'nomor'        => (string) $t['nama_nomor'],
                    'regu'         => (string) $t['regu'],
                    'regu_tunggal' => (bool) $t['regu_tunggal'],
                    'biaya'        => (int) ($t['biaya_tim'] ?? 0),
                    'susulan'      => $t['biaya_tim'] === null,
                    'anggota'      => array_map(static fn (array $a): array => ['nama' => (string) $a['nama'], 'peran' => $a['peran'] ?? null], $t['anggota']),
                ], $p['beregu']),
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
            ],
            'ulang' => true,
        ];
    }

    /**
     * @param array<string, mixed> $kiriman
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, data: array<string, mixed>|null, rincian: array<string, mixed>|null}
     */
    private function periksa(array $kiriman, string $hariIni): array
    {
        $ada = $this->data->ada(PemeriksaPendaftaran::npsnKiriman($kiriman), PemeriksaPendaftaran::nisnKiriman($kiriman));

        return (new PemeriksaPendaftaran($this->data->induk(), $ada, $hariIni))->periksa($kiriman);
    }

    /**
     * Inti lama di pendaftaran lain menjadi cadangan karena atlet susulan
     * menjadi inti (K105). Waktu ubah pendaftaran pemiliknya ikut diperbarui,
     * supaya admin yang sedang membuka halaman ubahnya diminta memuat ulang.
     *
     * @param list<array{atlet_id: int, nomor_lomba_id: int}> $turun
     */
    private function turunkanInti(array $turun): void
    {
        if ($turun === []) {
            return;
        }

        $sekarang    = Time::now(config('App')->appTimezone)->format('Y-m-d H:i:s');
        $pendaftaran = [];

        foreach ($turun as $t) {
            self::pastikan($this->db->table('atlet_nomor')
                ->where('atlet_id', $t['atlet_id'])->where('nomor_lomba_id', $t['nomor_lomba_id'])->where('peran', Regu::INTI)
                ->update(['peran' => Regu::CADANGAN, 'diubah_pada' => $sekarang]));

            if ($this->db->affectedRows() !== 1) {
                throw new RuntimeException('Inti lama yang diturunkan menjadi cadangan tidak ditemukan.');
            }

            $pendaftaran[] = (int) ($this->db->table('atlet')->select('pendaftaran_id')->where('id', $t['atlet_id'])->get()->getRowArray()['pendaftaran_id'] ?? 0);
        }

        self::pastikan($this->db->table('pendaftaran')->whereIn('id', array_values(array_unique($pendaftaran)))->update(['diubah_pada' => $sekarang]));
    }

    private function ambilKunci(string $kunci): bool
    {
        $baris = $this->db->query('SELECT GET_LOCK(?, ?) AS dapat', [$kunci, self::TUNGGU_KUNCI])->getRowArray();

        return (int) ($baris['dapat'] ?? 0) === 1;
    }

    private static function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            throw new RuntimeException('Data pendaftaran gagal ditulis.');
        }
    }

    /**
     * @param list<array{langkah: int, jalur: string, pesan: string}> $galat
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: null, rincian: null, ulang: false}
     */
    private static function gagal(array $galat): array
    {
        return ['galat' => $galat, 'pendaftaran' => null, 'rincian' => null, 'ulang' => false];
    }
}
