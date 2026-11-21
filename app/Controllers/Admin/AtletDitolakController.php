<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\DataPendaftaran;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Panel\TabelSisiServer;
use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Daftar semua atlet yang ditolak panitia (K116, K117): `admin/atlet-ditolak`.
 *
 * Terpisah dari halaman Atlet, yang hanya berisi atlet yang terdaftar. Satu
 * baris per catatan di `atlet_ditolak`, dengan keadaannya: **belum** atau
 * **sudah didaftarkan ulang** (NISN-nya sudah ada lagi di tabel `atlet`, di
 * pendaftaran mana pun). Halaman ini hanya untuk melihat dan mengunduh; atlet
 * ditolak dari rincian pendaftarannya.
 *
 * - `index`: halaman dengan tabel, saringan keadaan, dan jumlah per keadaan.
 * - `data`: JSON DataTables (`TabelSisiServer`).
 * - `excel`: semua baris yang lolos saringan dan pencarian.
 *
 * ID pendaftaran menaut ke rinciannya dengan `?dari=atlet-ditolak`, supaya
 * jejak navigasi dan menu yang aktif tetap "Atlet ditolak" (K117).
 */
class AtletDitolakController extends BaseController
{
    public const PESAN_KOSONG = 'Belum ada atlet yang ditolak. Atlet ditolak dari rincian pendaftaran yang masih Menunggu, lewat tombol "Tolak atlet".';

    /**
     * @var array<string, string> kode keadaan => label
     */
    public const KEADAAN = ['belum' => 'Belum didaftarkan ulang', 'sudah' => 'Sudah didaftarkan ulang'];

    public function index(): string
    {
        $db    = db_connect();
        $semua = $db->table('atlet_ditolak')->countAllResults();
        $sudah = $db->table('atlet_ditolak d')->join('atlet a', 'a.nisn = d.nisn')->countAllResults();

        return view('admin/atlet_ditolak/index', [
            'jumlah' => ['semua' => $semua, 'belum' => $semua - $sudah, 'sudah' => $sudah],
        ]);
    }

    public function data(): ResponseInterface
    {
        $jawaban = self::tabel()->jawab(self::pembuat(), $this->request->getGet());

        $jawaban['data'] = array_map(static function (array $baris): array {
            $baris['id_pendaftaran'] = [
                'alamat' => site_url('admin/pendaftaran/' . $baris['pendaftaran_id'] . '?dari=atlet-ditolak'),
                'teks'   => $baris['id_pendaftaran'],
            ];
            $baris['keadaan'] = trim(view('admin/atlet_ditolak/_keadaan', [
                'ulangId'     => $baris['ulang_id'] === null ? null : (string) $baris['ulang_id'],
                'ulangAlamat' => $baris['ulang_id'] === null ? null : site_url('admin/pendaftaran/' . $baris['ulang_pendaftaran_id'] . '?dari=atlet-ditolak'),
            ], ['saveData' => false, 'debug' => false]));
            $baris['alasan'] = nl2br(esc((string) $baris['alasan']));

            return $baris;
        }, $jawaban['data']);

        return $this->response->setJSON($jawaban);
    }

    public function excel(): DownloadResponse
    {
        $excel = new EksporExcel('Atlet ditolak', [
            'nama'                 => 'Nama',
            'nisn'                 => 'NISN',
            'jenis_kelamin'        => 'Jenis Kelamin',
            'tanggal_lahir'        => 'Tanggal Lahir',
            'nama_sekolah'         => 'Sekolah',
            'id_pendaftaran'       => 'ID Pendaftaran',
            'kelompok_umur'        => 'Kelompok Umur Didaftarkan',
            'nomor_lomba'          => 'Nomor Lomba Didaftarkan',
            'kelompok_umur_sesuai' => 'Kelompok Umur Sesuai',
            'alasan'               => 'Alasan',
            'dibuat_pada'          => 'Ditolak Pada',
            'ditolak_oleh'         => 'Ditolak Oleh',
            'keadaan'              => 'Keadaan',
            'ulang_id'             => 'ID Pendaftaran Ulang',
        ]);

        $excel->tambahBaris(array_map(static fn (array $baris): array => [
            'tanggal_lahir' => tanggal_id((string) $baris['tanggal_lahir']),
            'dibuat_pada'   => tanggal_id((string) $baris['dibuat_pada'], true),
            'keadaan'       => self::KEADAAN[$baris['ulang_id'] === null ? 'belum' : 'sudah'],
        ] + $baris, self::tabel()->semuaBaris(self::pembuat(), $this->request->getGet())));

        return $excel->unduh('AtletDitolak_' . date('Ymd'));
    }

    /**
     * Definisi tabel: kolom yang boleh dicari dan diurutkan, dan saringan keadaan.
     */
    public static function tabel(): TabelSisiServer
    {
        return (new TabelSisiServer(
            kolom: [
                'nama'                 => ['sql' => 'd.nama', 'cari' => true],
                'nisn'                 => ['sql' => 'd.nisn', 'cari' => true],
                'jenis_kelamin'        => ['sql' => 'd.jenis_kelamin'],
                'tanggal_lahir'        => ['sql' => 'd.tanggal_lahir'],
                'nama_sekolah'         => ['sql' => 'p.nama_sekolah', 'cari' => true],
                'id_pendaftaran'       => ['sql' => 'p.id_pendaftaran', 'cari' => true],
                'kelompok_umur'        => ['sql' => 'd.kelompok_umur'],
                'nomor_lomba'          => ['urut' => false],
                'kelompok_umur_sesuai' => ['sql' => 'd.kelompok_umur_sesuai'],
                'alasan'               => ['sql' => 'd.alasan', 'cari' => true, 'urut' => false],
                'dibuat_pada'          => ['sql' => 'd.dibuat_pada'],
                'ditolak_oleh'         => ['sql' => 'd.ditolak_oleh'],
                'keadaan'              => ['urut' => false],
            ],
            saringan: [
                'keadaan' => static function (BaseBuilder $builder, string $nilai): BaseBuilder {
                    return match ($nilai) {
                        'belum' => $builder->where('a2.id', null),
                        'sudah' => $builder->where('a2.id IS NOT NULL', null, false),
                        default => $builder,
                    };
                },
            ],
            urutBawaan: ['d.id' => 'desc'],
        ))->bentukBaris(static function (array $baris): array {
            $nomor = json_decode((string) $baris['nomor_lomba'], true);
            $nomor = is_array($nomor) ? array_column(array_filter($nomor, 'is_array'), 'nama') : [];

            return [
                'jenis_kelamin'        => DataPendaftaran::JENIS_KELAMIN[$baris['jenis_kelamin']] ?? $baris['jenis_kelamin'],
                'nomor_lomba'          => implode(', ', array_map('strval', $nomor)),
                'kelompok_umur_sesuai' => (string) ($baris['kelompok_umur_sesuai'] ?? ''),
                'ditolak_oleh'         => (string) ($baris['ditolak_oleh'] ?? ''),
                'pendaftaran_id'       => (int) $baris['pendaftaran_id'],
            ] + $baris;
        });
    }

    /**
     * Satu baris per catatan penolakan. NISN unik di `atlet`, jadi gabungan
     * kiri ke `atlet` paling banyak memberi satu baris: pendaftaran tempat
     * atlet itu terdaftar lagi.
     *
     * @return Closure(): BaseBuilder
     */
    public static function pembuat(): Closure
    {
        return static fn (): BaseBuilder => db_connect()->table('atlet_ditolak d')
            ->select('d.id, d.nama, d.nisn, d.jenis_kelamin, d.tanggal_lahir, d.kelompok_umur, d.nomor_lomba, d.kelompok_umur_sesuai, d.alasan, d.dibuat_pada, d.ditolak_oleh')
            ->select('p.id AS pendaftaran_id, p.id_pendaftaran, p.nama_sekolah')
            ->select('p2.id AS ulang_pendaftaran_id, p2.id_pendaftaran AS ulang_id')
            ->join('pendaftaran p', 'p.id = d.pendaftaran_id')
            ->join('atlet a2', 'a2.nisn = d.nisn', 'left')
            ->join('pendaftaran p2', 'p2.id = a2.pendaftaran_id', 'left');
    }
}
