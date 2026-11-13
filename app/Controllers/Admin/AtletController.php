<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Panel\TabelSisiServer;
use App\Models\KelompokUmurModel;
use App\Models\NomorLombaModel;
use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Daftar semua atlet (F6-03; rencana Tahap 6 hasil 6): `admin/atlet`.
 *
 * Halaman ini hanya untuk melihat dan mengunduh (asumsi 7 rencana Tahap 6);
 * atlet diubah dari rincian pendaftarannya (`admin/pendaftaran/<id>`), yang
 * ditautkan dari kolom ID pendaftaran.
 *
 * - `index`: halaman dengan tabel dan saringan (kelompok umur, jenis kelamin,
 *   nomor lomba, status pembayaran).
 * - `data`: JSON DataTables (`TabelSisiServer`): satu baris per atlet, nomor
 *   lomba atlet digabung dalam satu kolom.
 * - `excel`: semua baris yang lolos saringan, pencarian, dan urutan yang sama
 *   (`EksporExcel`), tanpa batas halaman.
 *
 * Saringan nomor lomba memilih atlet yang ikut nomor itu (individu atau
 * beregu); atlet tetap tampil dengan semua nomor lombanya.
 */
class AtletController extends BaseController
{
    /**
     * Pesan bila belum ada atlet sama sekali.
     */
    public const PESAN_KOSONG = 'Belum ada atlet. Atlet dari pendaftaran yang masuk lewat halaman Pendaftaran akan tampil di sini.';

    /**
     * @var array<string, string> kode status pembayaran => label
     */
    public const STATUS = ['lunas' => 'Lunas', 'menunggu' => 'Menunggu'];

    public function index(): string
    {
        $kelompokUmur = model(KelompokUmurModel::class)->select('id, nama')->orderBy('urutan')->orderBy('id')->findAll();
        $nomorLomba   = [];

        foreach (model(NomorLombaModel::class)->daftarLengkap() as $nomor) {
            $nomorLomba[(string) $nomor['nama_kelompok_umur']][] = [
                'id'   => (int) $nomor['id'],
                'nama' => NomorLombaModel::namaLengkap($nomor),
            ];
        }

        return view('admin/atlet/index', [
            'kelompokUmur' => $kelompokUmur,
            'nomorLomba'   => $nomorLomba,
            'jenisKelamin' => DataPendaftaran::JENIS_KELAMIN,
        ]);
    }

    public function data(): ResponseInterface
    {
        $jawaban = self::tabel()->jawab(self::pembuat(), $this->request->getGet());

        // Kolom ID pendaftaran menjadi tautan ke rincian pendaftarannya.
        $jawaban['data'] = array_map(static function (array $baris): array {
            $baris['id_pendaftaran'] = [
                'alamat' => site_url('admin/pendaftaran/' . $baris['pendaftaran_id']),
                'teks'   => $baris['id_pendaftaran'],
            ];

            return $baris;
        }, $jawaban['data']);

        return $this->response->setJSON($jawaban);
    }

    public function excel(): DownloadResponse
    {
        return self::lembarExcel($this->request->getGet())->unduh('Atlet_' . date('Ymd'));
    }

    /**
     * Lembar Excel atlet dengan saringan tabel; dipakai juga rekap gabungan (K85).
     *
     * @param array<string, mixed> $saringan
     */
    public static function lembarExcel(array $saringan = []): EksporExcel
    {
        $excel = new EksporExcel('Atlet', [
            'nomor_dada'     => 'Nomor Dada',
            'nama'           => 'Nama',
            'nisn'           => 'NISN',
            'jenis_kelamin'  => 'Jenis Kelamin',
            'tanggal_lahir'  => 'Tanggal Lahir',
            'kelompok_umur'  => 'Kelompok Umur',
            'nomor_lomba'    => 'Nomor Lomba',
            'nama_sekolah'   => 'Sekolah',
            'id_pendaftaran' => 'ID Pendaftaran',
            'status'         => 'Status',
        ]);

        $excel->tambahBaris(array_map(static fn (array $baris): array => [
            'tanggal_lahir' => tanggal_id((string) $baris['tanggal_lahir']),
            'status'        => self::STATUS[$baris['status']] ?? $baris['status'],
        ] + $baris, self::tabel()->semuaBaris(self::pembuat(), $saringan)));

        return $excel;
    }

    /**
     * Definisi tabel: kolom yang boleh dicari dan diurutkan, dan saringan.
     */
    public static function tabel(): TabelSisiServer
    {
        return (new TabelSisiServer(
            kolom: [
                'nomor_dada'     => ['sql' => 'a.nomor_dada', 'cari' => true],
                'nama'           => ['sql' => 'a.nama', 'cari' => true],
                'nisn'           => ['sql' => 'a.nisn', 'cari' => true],
                'jenis_kelamin'  => ['sql' => 'a.jenis_kelamin'],
                'tanggal_lahir'  => ['sql' => 'a.tanggal_lahir'],
                'kelompok_umur'  => ['sql' => 'ku.urutan'],
                'nomor_lomba'    => ['urut' => false],
                'nama_sekolah'   => ['sql' => 'p.nama_sekolah', 'cari' => true],
                'id_pendaftaran' => ['sql' => 'p.id_pendaftaran', 'cari' => true],
                'status'         => ['sql' => 'p.status'],
            ],
            saringan: [
                'kelompok_umur' => self::saringanId('a.kelompok_umur_id'),
                'jenis_kelamin' => ['sql' => 'a.jenis_kelamin', 'nilai' => array_keys(DataPendaftaran::JENIS_KELAMIN)],
                'nomor_lomba'   => self::saringanNomorLomba(),
                'status'        => ['sql' => 'p.status', 'nilai' => array_keys(self::STATUS)],
            ],
            urutBawaan: ['ku.urutan' => 'asc', 'a.nomor_dada' => 'asc', 'p.id' => 'asc', 'a.id' => 'asc'],
        ))->bentukBaris(static fn (array $baris): array => [
            'jenis_kelamin'  => DataPendaftaran::JENIS_KELAMIN[$baris['jenis_kelamin']] ?? $baris['jenis_kelamin'],
            'nomor_lomba'    => (string) ($baris['nomor_lomba'] ?? ''),
            'pendaftaran_id' => (int) $baris['pendaftaran_id'],
        ] + $baris);
    }

    /**
     * Pembuat query satu baris per atlet. Nomor lomba digabung lewat subquery
     * (urut kelompok umur nomornya, `urutan`, `id`), jadi tidak perlu `groupBy`.
     *
     * @return Closure(): BaseBuilder
     */
    public static function pembuat(): Closure
    {
        return static function (): BaseBuilder {
            $db = db_connect();

            $nomor = '(SELECT GROUP_CONCAT(CONCAT(nl.nama, \' \', nl.kategori) ORDER BY nl.urutan, nl.id SEPARATOR \', \')'
                . ' FROM ' . $db->prefixTable('atlet_nomor') . ' an'
                . ' JOIN ' . $db->prefixTable('nomor_lomba') . ' nl ON nl.id = an.nomor_lomba_id'
                . ' WHERE an.atlet_id = a.id)';

            return $db->table('atlet a')
                ->select('a.id, a.nomor_dada, a.nama, a.nisn, a.jenis_kelamin, a.tanggal_lahir')
                ->select('ku.nama AS kelompok_umur')
                ->select($nomor . ' AS nomor_lomba', false)
                ->select('p.id AS pendaftaran_id, p.id_pendaftaran, p.nama_sekolah, p.status')
                ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
                ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id');
        };
    }

    /**
     * Saringan berdasarkan id bilangan bulat; nilai lain diabaikan.
     */
    private static function saringanId(string $kolom): Closure
    {
        return static function (BaseBuilder $builder, string $nilai) use ($kolom): BaseBuilder {
            return ctype_digit($nilai) ? $builder->where($kolom, (int) $nilai) : $builder;
        };
    }

    /**
     * Atlet yang ikut nomor lomba tertentu (id bilangan bulat; nilai lain diabaikan).
     */
    private static function saringanNomorLomba(): Closure
    {
        return static function (BaseBuilder $builder, string $nilai): BaseBuilder {
            if (! ctype_digit($nilai)) {
                return $builder;
            }

            $db = $builder->db();

            return $builder->where(
                'EXISTS (SELECT 1 FROM ' . $db->prefixTable('atlet_nomor') . ' an_saring'
                . ' WHERE an_saring.atlet_id = a.id AND an_saring.nomor_lomba_id = ' . (int) $nilai . ')',
                null,
                false,
            );
        };
    }
}
