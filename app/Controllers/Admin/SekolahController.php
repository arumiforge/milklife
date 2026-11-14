<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Jenjang;
use App\Libraries\Panel\EksporExcel;
use App\Libraries\Panel\TabelSisiServer;
use App\Libraries\Panel\UsulanNamaSekolah;
use App\Libraries\Pendaftaran\Kabupaten;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\SekolahModel;
use App\Models\UsulanNamaSekolahModel;
use Closure;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Data sekolah di panel (K97): `admin/sekolah`.
 *
 * Data resmi Kemendikdasmen memakai nama umum untuk banyak madrasah
 * ("MIS MUHAMMADIYAH" ada di beberapa desa), jadi di rekap dan bukti
 * sekolah yang berbeda tampil dengan nama yang sama. Di sini admin melihat
 * semua sekolah, menyaring yang namanya kembar, lalu menyunting namanya
 * menjadi nama pembeda (misalnya "MIS MUHAMMADIYAH JATI").
 *
 * - `index`: tabel dengan saringan kabupaten, jenjang, nama kembar, dan
 *   sudah disunting.
 * - `data`: JSON DataTables (`TabelSisiServer`).
 * - `excel`: semua baris yang lolos saringan yang sama.
 * - `usulan`, `setujuiUsulan`, `tolakUsulan`: usulan nama dari pendaftar
 *   (K99, `App\Libraries\Panel\UsulanNamaSekolah`).
 * - `ubah` / `perbarui`: formulir satu sekolah. NPSN tidak bisa diubah.
 *   Sekolah yang disimpan ditandai `disunting_pada`, sehingga
 *   `php spark sekolah:impor` tidak menimpanya. Pilihan "terapkan ke
 *   pendaftaran" ikut mengganti nama sekolah di pendaftaran yang sudah
 *   masuk dengan NPSN itu.
 */
class SekolahController extends BaseController
{
    private const ALAMAT = 'admin/sekolah';

    public const PESAN_KOSONG = 'Data sekolah masih kosong. Isi dengan perintah "php spark sekolah:impor" di komputer server.';

    /**
     * Pilihan saringan "Nama" => label.
     *
     * @var array<string, string>
     */
    public const SARING_NAMA = ['kembar' => 'Sama dengan sekolah lain', 'tunggal' => 'Tidak ada yang sama'];

    /**
     * Pilihan saringan "Disunting" => label.
     *
     * @var array<string, string>
     */
    public const SARING_DISUNTING = ['ya' => 'Sudah disunting admin', 'tidak' => 'Belum disunting'];

    /**
     * Kotak formulir yang bisa diubah.
     */
    private const KOTAK = ['nama', 'bentuk', 'jenjang', 'status', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten'];

    public function index(): string
    {
        return view('admin/sekolah/index', [
            'kabupaten'    => Kabupaten::semua(),
            'jenjang'      => self::labelJenjang(),
            'jumlahKembar' => $this->jumlahKembar(),
            'jumlahUsulan' => model(UsulanNamaSekolahModel::class)->jumlahMenunggu(),
        ]);
    }

    /**
     * Usulan nama sekolah dari pendaftar (K99): yang menunggu di atas, lalu
     * riwayat keputusan terbaru.
     */
    public function usulan(): string
    {
        return view('admin/sekolah/usulan', [
            'usulan' => model(UsulanNamaSekolahModel::class)->daftarLengkap(),
        ]);
    }

    public function setujuiUsulan(int $id): RedirectResponse
    {
        $nama  = $this->request->getPost('nama');
        $hasil = (new UsulanNamaSekolah())->setujui($id, is_string($nama) ? $nama : '');

        if ($hasil['galat'] !== null) {
            return redirect()->to(self::ALAMAT . '/usulan')->with('pesan_galat', $hasil['galat']);
        }

        $pesan = 'Usulan disetujui. Nama sekolah sekarang ' . $hasil['nama'] . '.';

        if ($hasil['pendaftaran'] > 0) {
            $pesan .= ' Nama di ' . $hasil['pendaftaran'] . ' pendaftaran ikut diganti.';
        }

        if ($hasil['usulan_lain'] > 0) {
            $pesan .= ' ' . $hasil['usulan_lain'] . ' usulan lain untuk sekolah yang sama ikut ditutup.';
        }

        return redirect()->to(self::ALAMAT . '/usulan')->with('pesan_sukses', $pesan);
    }

    public function tolakUsulan(int $id): RedirectResponse
    {
        $alasan = $this->request->getPost('alasan');
        $hasil  = (new UsulanNamaSekolah())->tolak($id, is_string($alasan) ? $alasan : '');

        if ($hasil['galat'] !== null) {
            return redirect()->to(self::ALAMAT . '/usulan')->with('pesan_galat', $hasil['galat']);
        }

        $pesan = 'Usulan ditolak.';

        if ($hasil['pendaftaran'] > 0) {
            $pesan .= ' Pendaftaran pengusul kembali memakai nama ' . $hasil['nama'] . '.';
        }

        return redirect()->to(self::ALAMAT . '/usulan')->with('pesan_sukses', $pesan);
    }

    public function data(): ResponseInterface
    {
        $jawaban = self::tabel()->jawab(self::pembuat(), $this->request->getGet());

        $jawaban['data'] = array_map(static function (array $baris): array {
            $baris['ubah'] = ['alamat' => site_url(self::ALAMAT . '/' . $baris['id'] . '/ubah'), 'teks' => 'Ubah'];

            return $baris;
        }, $jawaban['data']);

        return $this->response->setJSON($jawaban);
    }

    public function excel(): DownloadResponse
    {
        $excel = new EksporExcel('Sekolah', [
            'npsn'           => 'NPSN',
            'nama'           => 'Nama',
            'bentuk'         => 'Bentuk',
            'jenjang'        => 'Jenjang',
            'status'         => 'Status',
            'alamat'         => 'Alamat',
            'desa_kelurahan' => 'Desa/Kelurahan',
            'kecamatan'      => 'Kecamatan',
            'kabupaten'      => 'Kabupaten',
            'nama_sama'      => 'Sekolah Lain Bernama Sama',
            'disunting'      => 'Disunting Admin',
        ]);

        $excel->tambahBaris(self::tabel()->semuaBaris(self::pembuat(), $this->request->getGet()));

        return $excel->unduh('Sekolah_' . date('Ymd'));
    }

    public function ubah(int $id): RedirectResponse|string
    {
        $sekolah = model(SekolahModel::class)->find($id);

        if ($sekolah === null) {
            return $this->tidakDitemukan();
        }

        $isian = [];

        foreach (self::KOTAK as $kotak) {
            $isian[$kotak] = (string) ($sekolah[$kotak] ?? '');
        }

        $isian['terapkan'] = '1';

        return $this->tampilkanFormulir($sekolah, $isian);
    }

    public function perbarui(int $id): RedirectResponse|string
    {
        $model   = model(SekolahModel::class);
        $sekolah = $model->find($id);

        if ($sekolah === null) {
            return $this->tidakDitemukan();
        }

        $isian = [];

        foreach (self::KOTAK as $kotak) {
            $nilai         = $this->request->getPost($kotak);
            $isian[$kotak] = is_string($nilai) ? trim((string) preg_replace('/\s+/u', ' ', $nilai)) : '';
        }

        $isian['terapkan'] = $this->request->getPost('terapkan') === '1' ? '1' : '';

        [$data, $galat] = $this->periksa($sekolah, $isian);

        if ($galat !== []) {
            return $this->tampilkanFormulir($sekolah, $isian, $galat);
        }

        $data['disunting_pada'] = date('Y-m-d H:i:s');
        $db                     = db_connect();
        $jumlahPendaftaran      = 0;

        $db->transStart();
        $model->update($id, $data);

        if ($isian['terapkan'] === '1' && $data['nama'] !== $sekolah['nama']) {
            $db->table('pendaftaran')
                ->where('npsn', $sekolah['npsn'])
                ->where('nama_sekolah !=', $data['nama'])
                ->update(['nama_sekolah' => $data['nama'], 'diubah_pada' => date('Y-m-d H:i:s')]);
            $jumlahPendaftaran = $db->affectedRows();
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            return $this->tampilkanFormulir($sekolah, $isian, [
                'nama' => 'Perubahan belum tersimpan karena basis data sedang bermasalah. Tunggu sebentar, lalu tekan Simpan perubahan lagi.',
            ]);
        }

        $pesan = 'Perubahan sekolah ' . $data['nama'] . ' (NPSN ' . $sekolah['npsn'] . ') tersimpan.';

        if ($jumlahPendaftaran > 0) {
            $pesan .= ' Nama sekolah di ' . $jumlahPendaftaran . ' pendaftaran ikut diganti.';
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', $pesan);
    }

    /**
     * Definisi tabel: kolom yang boleh dicari dan diurutkan, dan saringan.
     */
    public static function tabel(): TabelSisiServer
    {
        $labelJenjang = self::labelJenjang();

        return (new TabelSisiServer(
            kolom: [
                'npsn'           => ['sql' => 's.npsn', 'cari' => true],
                'nama'           => ['sql' => 's.nama', 'cari' => true],
                'bentuk'         => ['sql' => 's.bentuk'],
                'jenjang'        => ['sql' => 's.jenjang'],
                'status'         => ['sql' => 's.status'],
                'alamat'         => ['sql' => 's.alamat', 'cari' => true],
                'desa_kelurahan' => ['sql' => 's.desa_kelurahan', 'cari' => true],
                'kecamatan'      => ['sql' => 's.kecamatan', 'cari' => true],
                'kabupaten'      => ['sql' => 's.kabupaten'],
                'nama_sama'      => ['sql' => 'nama_sama'],
                'disunting'      => ['sql' => 's.disunting_pada'],
                'ubah'           => ['urut' => false],
            ],
            saringan: [
                'kabupaten' => ['sql' => 's.kabupaten', 'nilai' => Kabupaten::semua()],
                'jenjang'   => ['sql' => 's.jenjang', 'nilai' => SekolahModel::JENJANG],
                'nama'      => self::saringanNama(),
                'disunting' => static fn (BaseBuilder $b, string $nilai): BaseBuilder => match ($nilai) {
                    'ya'    => $b->where('s.disunting_pada IS NOT NULL', null, false),
                    'tidak' => $b->where('s.disunting_pada IS NULL', null, false),
                    default => $b,
                },
            ],
            urutBawaan: ['s.nama' => 'asc', 's.kabupaten' => 'asc', 's.npsn' => 'asc'],
        ))->bentukBaris(static fn (array $baris): array => [
            'id'        => (int) $baris['id'],
            'jenjang'   => $labelJenjang[$baris['jenjang']] ?? (string) $baris['jenjang'],
            'status'    => SekolahModel::STATUS[strtoupper((string) $baris['status'])] ?? (string) $baris['status'],
            'nama_sama' => (int) $baris['nama_sama'],
            'disunting' => $baris['disunting'] === null ? '' : 'Ya',
        ] + $baris);
    }

    /**
     * Pembuat query satu baris per sekolah, ditambah jumlah sekolah lain
     * yang namanya sama persis (huruf besar-kecil tidak dibedakan).
     *
     * @return Closure(): BaseBuilder
     */
    public static function pembuat(): Closure
    {
        return static function (): BaseBuilder {
            $db = db_connect();

            return $db->table('sekolah s')
                ->select('s.id, s.npsn, s.nama, s.bentuk, s.jenjang, s.status, s.alamat, s.desa_kelurahan, s.kecamatan, s.kabupaten')
                ->select('s.disunting_pada AS disunting')
                ->select('(SELECT COUNT(*) FROM ' . $db->prefixTable('sekolah') . ' s2 WHERE s2.nama = s.nama AND s2.id <> s.id) AS nama_sama', false);
        };
    }

    /**
     * Label jenjang data sekolah: SD => SD/MI, dan seterusnya.
     *
     * @return array<string, string>
     */
    public static function labelJenjang(): array
    {
        $label = [];

        foreach (SekolahModel::JENJANG as $kode) {
            $label[$kode] = (string) Jenjang::label(PemeriksaPendaftaran::JENJANG_REFERENSI[$kode]);
        }

        return $label;
    }

    /**
     * Saringan nama kembar atau tunggal; nilai lain diabaikan.
     */
    private static function saringanNama(): Closure
    {
        return static function (BaseBuilder $builder, string $nilai): BaseBuilder {
            if (! isset(self::SARING_NAMA[$nilai])) {
                return $builder;
            }

            $tabel = $builder->db()->prefixTable('sekolah');

            return $builder->where(
                ($nilai === 'kembar' ? '' : 'NOT ') . 'EXISTS (SELECT 1 FROM ' . $tabel . ' s3 WHERE s3.nama = s.nama AND s3.id <> s.id)',
                null,
                false,
            );
        };
    }

    /**
     * Banyak sekolah yang namanya sama dengan sekolah lain.
     */
    private function jumlahKembar(): int
    {
        $tabel = db_connect()->prefixTable('sekolah');

        return (int) (db_connect()->query(
            'SELECT COALESCE(SUM(n), 0) AS jumlah FROM (SELECT COUNT(*) AS n FROM ' . $tabel . ' GROUP BY nama HAVING COUNT(*) > 1) kembar',
        )->getRowArray()['jumlah'] ?? 0);
    }

    /**
     * Periksa isian. Hasilnya data siap simpan dan pesan galat per kotak.
     *
     * @param array<string, mixed>  $sekolah
     * @param array<string, string> $isian
     *
     * @return array{0: array<string, string|null>, 1: array<string, string>}
     */
    private function periksa(array $sekolah, array $isian): array
    {
        $galat = [];
        $data  = [];

        foreach (['nama', 'bentuk', 'alamat', 'desa_kelurahan', 'kecamatan'] as $kotak) {
            $data[$kotak] = $isian[$kotak] === '' ? null : $isian[$kotak];
        }

        if ($data['nama'] === null) {
            $galat['nama'] = 'Nama sekolah belum diisi. Tulis nama lengkap, misalnya MIS MUHAMMADIYAH JATI.';
        }

        $label = [
            'nama'           => 'Nama sekolah',
            'bentuk'         => 'Bentuk',
            'alamat'         => 'Alamat',
            'desa_kelurahan' => 'Desa/kelurahan',
            'kecamatan'      => 'Kecamatan',
        ];

        foreach ($label as $kotak => $teks) {
            $panjang = SekolahModel::PANJANG[$kotak];

            if ($data[$kotak] !== null && mb_strlen($data[$kotak]) > $panjang) {
                $galat[$kotak] = $teks . ' terlalu panjang. Persingkat menjadi paling banyak ' . $panjang . ' huruf.';
            }
        }

        $data['jenjang'] = strtoupper($isian['jenjang']);

        if (! in_array($data['jenjang'], SekolahModel::JENJANG, true)) {
            $galat['jenjang'] = 'Jenjang belum dipilih. Pilih SD/MI, SMP/MTs, atau SMA/SMK/MA.';
        }

        $data['status'] = $isian['status'] === '' ? null : strtoupper($isian['status']);

        if ($data['status'] !== null && ! isset(SekolahModel::STATUS[$data['status']])) {
            $galat['status'] = 'Status belum benar. Pilih Negeri atau Swasta, atau biarkan kosong.';
        }

        $data['kabupaten'] = Kabupaten::baku($isian['kabupaten']);

        if ($data['kabupaten'] === null) {
            $galat['kabupaten'] = 'Kabupaten belum dipilih. Pilih salah satu dari ' . implode(', ', Kabupaten::semua()) . '.';
        }

        // Nama baru tidak boleh sama dengan sekolah lain, supaya tujuan
        // penyuntingan (nama pembeda) tercapai. Nama lama yang tidak diubah
        // boleh tetap, jadi admin tetap bisa membetulkan alamat saja.
        if (! isset($galat['nama']) && mb_strtolower((string) $data['nama']) !== mb_strtolower((string) $sekolah['nama'])) {
            $kembar = model(SekolahModel::class)->where('nama', $data['nama'])->where('id !=', $sekolah['id'])->first();

            if ($kembar !== null) {
                $galat['nama'] = 'Nama ini sudah dipakai ' . $kembar['nama'] . ' (NPSN ' . $kembar['npsn'] . ', '
                    . SekolahModel::lokasi($kembar) . '). Tambahkan pembeda, misalnya nama desa atau kecamatan.';
            }
        }

        return [$data, $galat];
    }

    /**
     * @param array<string, mixed>  $sekolah
     * @param array<string, string> $isian
     * @param array<string, string> $galat
     */
    private function tampilkanFormulir(array $sekolah, array $isian, array $galat = []): string
    {
        $model = model(SekolahModel::class);
        $sama  = $model->where('nama', $sekolah['nama'])->where('id !=', $sekolah['id'])->orderBy('kabupaten')->orderBy('npsn')->findAll(20);

        return view('admin/sekolah/ubah', [
            'sekolah'           => $sekolah,
            'isian'             => $isian,
            'galat'             => $galat,
            'kabupaten'         => Kabupaten::semua(),
            'jenjang'           => self::labelJenjang(),
            'namaSama'          => $sama,
            'jumlahPendaftaran' => db_connect()->table('pendaftaran')->where('npsn', $sekolah['npsn'])->countAllResults(),
        ]);
    }

    private function tidakDitemukan(): RedirectResponse
    {
        return redirect()->to(self::ALAMAT)->with(
            'pesan_galat',
            'Sekolah itu tidak ditemukan. Pilih sekolah dari daftar di bawah.',
        );
    }
}
