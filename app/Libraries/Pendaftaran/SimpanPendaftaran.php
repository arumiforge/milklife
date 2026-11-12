<?php

namespace App\Libraries\Pendaftaran;

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
 *    yang sama tidak sama-sama membuat tim di nomor yang sama atau melewati
 *    kuota (6.6, K51).
 * 2. Data yang sudah ada dibaca ulang dan seluruh kiriman diperiksa
 *    (`PemeriksaPendaftaran`).
 * 3. Foto penanggung jawab disimpan di luar `public/`.
 * 4. Satu transaksi: pendaftaran (status menunggu), ID pendaftaran dari nomor
 *    baris (6.8), penanggung jawab, atlet, nomor atlet (biaya individu
 *    disalin), dan tim beregu.
 * 5. Bila gagal, transaksi dibatalkan dan foto dihapus. NISN yang ternyata
 *    terdaftar pada saat yang sama ditolak kunci unik basis data dan
 *    dilaporkan sebagai galat NISN biasa.
 *
 * Pemeriksaan buka-tutup pendaftaran (6.1) dilakukan pengendali sebelum
 * memanggil kelas ini.
 *
 *     $hasil = (new SimpanPendaftaran())->simpan($kiriman);
 *
 *     $hasil['galat'];        // list galat seperti PemeriksaPendaftaran, [] bila tersimpan
 *     $hasil['pendaftaran'];  // ['id' => 1, 'id_pendaftaran' => 'MAC2A0000126'] atau null
 *     $hasil['rincian'];      // rincian biaya (PemeriksaPendaftaran) atau null
 */
final class SimpanPendaftaran
{
    /**
     * Lama menunggu kunci per NPSN, dalam detik.
     */
    public const TUNGGU_KUNCI = 10;

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
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: array{id: int, id_pendaftaran: string}|null, rincian: array<string, mixed>|null}
     */
    public function simpan(array $kiriman, ?string $hariIni = null): array
    {
        $hariIni ??= Time::now(config('App')->appTimezone)->format('Y-m-d');
        $npsn  = PemeriksaPendaftaran::npsnKiriman($kiriman);
        $kunci = $npsn !== null ? 'milklife-pendaftaran-' . $npsn : null;

        if ($kunci !== null && ! $this->ambilKunci($kunci)) {
            return self::gagal([[
                'langkah' => 4,
                'jalur'   => 'simpan',
                'pesan'   => 'Pendaftaran lain dari sekolah ini sedang disimpan. Tunggu beberapa detik, lalu tekan simpan lagi.',
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
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: array{id: int, id_pendaftaran: string}|null, rincian: array<string, mixed>|null}
     */
    private function simpanTerkunci(array $kiriman, string $hariIni): array
    {
        $hasil = $this->periksa($kiriman, $hariIni);

        if ($hasil['galat'] !== []) {
            return self::gagal($hasil['galat']);
        }

        $data  = $hasil['data'];
        $foto  = [];
        $seri  = setting('Acara.seri');
        $tahun = setting('Acara.tahun');

        if (! is_numeric($seri) || ! is_numeric($tahun)) {
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
            ], true);
            self::pastikan($id);

            $idPendaftaran = IdPendaftaran::buat((int) $seri, $data['sekolah']['jenjang'], (int) $id, (int) $tahun);
            self::pastikan($pendaftaran->update($id, ['id_pendaftaran' => $idPendaftaran]));

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
                    ]));
                }
            }

            foreach ($data['beregu'] as $tim) {
                self::pastikan(model(PendaftaranBereguModel::class)->insert([
                    'pendaftaran_id' => $id,
                    'nomor_lomba_id' => $tim['nomor_lomba_id'],
                    'biaya_tim'      => $tim['biaya_tim'],
                ]));
            }

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
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, pendaftaran: null, rincian: null}
     */
    private static function gagal(array $galat): array
    {
        return ['galat' => $galat, 'pendaftaran' => null, 'rincian' => null];
    }
}
