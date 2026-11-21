<?php

namespace App\Libraries\Panel;

use App\Libraries\Hasil\DataTabelSkor;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\JenisHasil;
use App\Libraries\Hasil\PembagiSeri;
use App\Libraries\Hasil\Peringkat;
use App\Libraries\Hasil\TabelSkor;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\PeranAwalRegu;
use App\Libraries\Pendaftaran\Regu;
use App\Libraries\Pendaftaran\SimpanPendaftaran;
use App\Libraries\Unggahan;
use App\Models\FaqModel;
use App\Models\GaleriModel;
use App\Models\SekolahModel;
use App\Models\UsulanNamaSekolahModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Paket data pendaftaran untuk pindah server (K76): ekspor ke satu zip dan
 * impor zip itu di server baru, tanpa memindahkan seluruh basis data.
 *
 * Isi zip:
 * - `data.json`: semua pendaftaran beserta penanggung jawab, atlet, nomor lomba
 *   yang diikuti (dengan regu dan peran inti/cadangan, K105), regu beregu, dan bukti bayar; juga `nomor_terakhir` tiap
 *   awalan nomor dada (supaya nomor dada tidak dipakai ulang, K05) dan nomor
 *   urut ID pendaftaran berikutnya; usulan nama sekolah dari pendaftar (K99)
 *   dan sekolah yang namanya disunting admin (K97), supaya keduanya tidak hilang
 *   di server baru. Dua kunci terakhir boleh tidak ada (paket lama). Setiap
 *   pendaftaran juga membawa `gabungan_dari`: ID dan kode bukti lama yang
 *   sudah digabung ke situ (K109), supaya kode QR bukti lama tetap berlaku,
 *   dan `atlet_ditolak`: atlet yang ditolak panitia (K116; boleh tidak ada), serta
 *   `kunci_kirim` dari wizard (K118; boleh tidak ada).
 *   `riwayat_admin`: riwayat tindakan admin (F8-01), juga milik pendaftaran
 *   yang sudah dihapus (boleh tidak ada).
 *   Atlet membawa `hadir_pada` dan `hadir_oleh` (tanda hadir hari lomba,
 *   F8-03; boleh tidak ada di paket lama).
 *   `hasil_lomba`: jenis hasil, waktu terbit, babak, dan hasil per nomor
 *   lomba (F8-04; boleh tidak ada di paket lama). Nomor poin multi-lomba
 *   membawa kode tabel skor dan komponennya, hasilnya membawa `rincian`
 *   per komponen (F9-10). Babak membawa jadwal (F9-07) dan keadaan seri,
 *   hasil membawa seri dan lintasan, nomor membawa aturan seri (F9-08).
 *   `tabel_skor`: semua tabel skor beserta barisnya, termasuk ubahan admin
 *   (F9-10; boleh tidak ada). Tabel berkode sama di server baru diganti.
 *   `faq`: tanya-jawab halaman Bantuan (F9-04). Bila ada dan berisi, isinya
 *   menggantikan tanya-jawab di server ini (biasanya bawaan `data:awal`);
 *   bila tidak ada (paket lama) atau kosong, tanya-jawab server ini tetap.
 *   `galeri`: album Galeri (F9-06), sampulnya di `galeri/<acak>.<ekstensi>`
 *   dalam zip. Sama seperti `faq`: bila ada dan berisi, menggantikan album
 *   server ini (sampul lama dihapus setelah impor berhasil); bila tidak ada
 *   atau kosong, album server ini tetap.
 * - `berkas/penanggung-jawab/<acak>.jpg` dan `berkas/bukti-bayar/<acak>.jpg`.
 *
 * Kelompok umur dicocokkan menurut namanya, nomor lomba menurut nama kelompok
 * umur + nama nomor + kategori. Jadi server baru harus sudah punya pengaturan
 * yang sama (misalnya lewat `php spark data:awal`, lalu disamakan di panel).
 *
 * Impor hanya ke server yang **belum punya pendaftaran**. Bila ada kelompok
 * umur atau nomor lomba yang tidak ditemukan, tidak ada yang diimpor.
 * Berkas disalin lebih dulu; bila penyimpanan basis data gagal, berkas yang
 * sudah disalin dihapus lagi.
 *
 *     $jumlah = (new PaketPendaftaran())->ekspor('/tmp/paket.zip');
 *     $jumlah = (new PaketPendaftaran())->impor('/tmp/paket.zip');
 *     // ['pendaftaran' => 12, 'atlet' => 85, 'berkas' => 20]
 */
final class PaketPendaftaran
{
    public const VERSI = 1;

    /**
     * Jalur berkas yang sah di dalam zip dan di kolom basis data.
     */
    private const POLA_BERKAS = '#^(' . FotoPenanggungJawab::FOLDER . '|' . BerkasBuktiBayar::FOLDER . ')/[0-9a-f]{32}\.jpg$#D';

    /**
     * Folder zip sementara tombol Backup data di panel (F7-25, K118).
     */
    public const FOLDER_SEMENTARA = WRITEPATH . 'cache/ekspor';

    /**
     * Umur zip sementara (detik) sebelum dihapus saat ekspor berikutnya.
     */
    public const UMUR_SEMENTARA = 3600;

    /**
     * Jalur sampul album Galeri yang sah (F9-06).
     */
    private const POLA_SAMPUL_GALERI = '#^unggahan/' . GaleriModel::FOLDER . '/[0-9a-f]{32}\.(webp|jpg|png)$#D';

    private BaseConnection $db;

    /**
     * Sampul album Galeri server ini yang diganti impor; dihapus setelah impor berhasil.
     *
     * @var list<string>
     */
    private array $sampulGaleriLama = [];

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Jalur baru untuk zip sementara tombol Backup data (F7-25, K118).
     *
     * Zip dikirim ke peramban langsung dari berkas (tanpa dimuat ke memori),
     * jadi tidak bisa dihapus di permintaan yang sama. Sebagai gantinya, setiap
     * ekspor baru menghapus zip sementara yang lebih tua dari
     * `UMUR_SEMENTARA`. Nama berkas acak supaya dua admin yang mengekspor
     * bersamaan tidak saling menimpa.
     *
     * @param string|null $folder   Untuk uji; bawaan `FOLDER_SEMENTARA`.
     * @param int|null    $sekarang Untuk uji; bawaan `time()`.
     *
     * @throws RuntimeException Bila folder tidak bisa dibuat.
     */
    public static function jalurSementara(?string $folder = null, ?int $sekarang = null): string
    {
        $folder = rtrim($folder ?? self::FOLDER_SEMENTARA, '/');

        if (! is_dir($folder) && ! @mkdir($folder, 0700, true) && ! is_dir($folder)) {
            throw new RuntimeException('Folder sementara untuk Backup data tidak bisa dibuat. Minta pengelola server memeriksa izin tulis folder writable/cache.');
        }

        self::hapusSementaraLama($folder, $sekarang ?? time());

        return $folder . '/Backup_' . bin2hex(random_bytes(16)) . '.zip';
    }

    /**
     * Hapus zip sementara di `$folder` yang lebih tua dari `UMUR_SEMENTARA`.
     *
     * @return int Jumlah berkas yang dihapus.
     */
    public static function hapusSementaraLama(string $folder, int $sekarang): int
    {
        $jumlah = 0;

        foreach (glob(rtrim($folder, '/') . '/Backup_*.zip') ?: [] as $berkas) {
            $waktu = @filemtime($berkas);

            if (is_file($berkas) && $waktu !== false && $sekarang - $waktu > self::UMUR_SEMENTARA && @unlink($berkas)) {
                $jumlah++;
            }
        }

        return $jumlah;
    }

    /**
     * Tulis paket ke `$tujuan` (ditimpa bila ada).
     *
     * @return array{pendaftaran: int, atlet: int, berkas: int}
     *
     * @throws RuntimeException Bila zip gagal ditulis.
     */
    public function ekspor(string $tujuan): array
    {
        $ku    = array_column($this->db->table('kelompok_umur')->select('id, nama')->get()->getResultArray(), 'nama', 'id');
        $nomor = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori')->get()->getResultArray() as $n) {
            $nomor[(int) $n['id']] = ['kelompok_umur' => $ku[$n['kelompok_umur_id']] ?? '', 'nama' => $n['nama'], 'kategori' => $n['kategori']];
        }

        $kelompok = static function (array $baris): array {
            $hasil = [];

            foreach ($baris as $b) {
                $hasil[(int) $b['pendaftaran_id']][] = $b;
            }

            return $hasil;
        };

        $pj    = $kelompok($this->db->table('penanggung_jawab')->orderBy('id')->get()->getResultArray());
        $atlet = $kelompok($this->db->table('atlet')->orderBy('id')->get()->getResultArray());
        $tim   = $kelompok($this->db->table('pendaftaran_beregu')->orderBy('id')->get()->getResultArray());
        $bukti = $kelompok($this->db->table('bukti_bayar')->orderBy('id')->get()->getResultArray());
        $lama  = $kelompok($this->db->table('pendaftaran_gabungan')->orderBy('id')->get()->getResultArray());
        $tolak = $kelompok($this->db->table('atlet_ditolak')->orderBy('id')->get()->getResultArray());

        $ikutNomor = [];

        foreach ($this->db->table('atlet_nomor')->orderBy('id')->get()->getResultArray() as $an) {
            $ikutNomor[(int) $an['atlet_id']][] = $nomor[(int) $an['nomor_lomba_id']] + [
                'biaya' => $an['biaya'] === null ? null : (int) $an['biaya'],
                'regu'  => $an['regu'] ?? null,
                'peran' => $an['peran'] ?? null,
            ];
        }

        $berkas = [];
        $data   = [];

        foreach ($this->db->table('pendaftaran')->orderBy('id')->get()->getResultArray() as $p) {
            $id = (int) $p['id'];

            $data[] = [
                'id'                => $id,
                'id_pendaftaran'    => $p['id_pendaftaran'],
                'kode_bukti'        => $p['kode_bukti'] ?? null,
                'kunci_kirim'       => $p['kunci_kirim'] ?? null,
                'jenjang'           => $p['jenjang'],
                'npsn'              => $p['npsn'],
                'nama_sekolah'      => $p['nama_sekolah'],
                'kabupaten'         => $p['kabupaten'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
                'status'            => $p['status'],
                'waktu_lunas'       => $p['waktu_lunas'],
                'dibuat_pada'       => $p['dibuat_pada'],
                'diubah_pada'       => $p['diubah_pada'],
                'penanggung_jawab'  => array_map(function (array $o) use (&$berkas): array {
                    $foto = $this->berkasSah($o['foto']) ? $o['foto'] : null;

                    if ($foto !== null) {
                        $berkas[$foto] = FotoPenanggungJawab::folderDasar() . $foto;
                    }

                    return ['nama' => $o['nama'], 'nomor_wa' => $o['nomor_wa'], 'foto' => $foto, 'dibuat_pada' => $o['dibuat_pada'], 'diubah_pada' => $o['diubah_pada']];
                }, $pj[$id] ?? []),
                'atlet' => array_map(static fn (array $a): array => [
                    'nama'          => $a['nama'],
                    'nisn'          => $a['nisn'],
                    'tanggal_lahir' => $a['tanggal_lahir'],
                    'jenis_kelamin' => $a['jenis_kelamin'],
                    'kelompok_umur' => $ku[$a['kelompok_umur_id']] ?? '',
                    'nomor_dada'    => $a['nomor_dada'],
                    'hadir_pada'    => $a['hadir_pada'] ?? null,
                    'hadir_oleh'    => $a['hadir_oleh'] ?? null,
                    'dibuat_pada'   => $a['dibuat_pada'],
                    'diubah_pada'   => $a['diubah_pada'],
                    'nomor'         => $ikutNomor[(int) $a['id']] ?? [],
                ], $atlet[$id] ?? []),
                'beregu' => array_map(static fn (array $t): array => $nomor[(int) $t['nomor_lomba_id']] + [
                    'regu' => (string) ($t['regu'] ?? 'A'), 'biaya_tim' => (int) $t['biaya_tim'], 'dibuat_pada' => $t['dibuat_pada'], 'diubah_pada' => $t['diubah_pada'],
                ], $tim[$id] ?? []),
                'bukti_bayar' => array_values(array_filter(array_map(function (array $b) use (&$berkas): ?array {
                    if (! $this->berkasSah($b['berkas'])) {
                        return null;
                    }

                    $berkas[$b['berkas']] = BerkasBuktiBayar::folderDasar() . $b['berkas'];

                    return [
                        'berkas' => $b['berkas'], 'ukuran' => (int) $b['ukuran'], 'waktu_unggah' => $b['waktu_unggah'],
                        // F8-02: penanda bukti yang sudah diperiksa admin.
                        'diperiksa_pada' => $b['diperiksa_pada'] ?? null, 'diperiksa_oleh' => $b['diperiksa_oleh'] ?? null,
                    ];
                }, $bukti[$id] ?? []))),
                // ID dan kode bukti lama yang sudah digabung ke sini (K109).
                'gabungan_dari' => array_map(static fn (array $g): array => [
                    'id_pendaftaran' => $g['id_pendaftaran_lama'], 'kode_bukti' => $g['kode_bukti_lama'], 'dibuat_pada' => $g['dibuat_pada'],
                ], $lama[$id] ?? []),
                // Atlet yang ditolak panitia (K116); kelompok umur yang sesuai dicocokkan menurut nama.
                'atlet_ditolak' => array_map(static fn (array $d): array => [
                    'nama'                 => $d['nama'],
                    'nisn'                 => $d['nisn'],
                    'tanggal_lahir'        => $d['tanggal_lahir'],
                    'jenis_kelamin'        => $d['jenis_kelamin'],
                    'kelompok_umur'        => $d['kelompok_umur'],
                    'nomor_lomba'          => json_decode((string) $d['nomor_lomba'], true) ?? [],
                    'biaya_individu'       => (int) $d['biaya_individu'],
                    'kelompok_umur_sesuai_nama' => $d['kelompok_umur_sesuai_id'] === null ? null : ($ku[$d['kelompok_umur_sesuai_id']] ?? null),
                    'kelompok_umur_sesuai' => $d['kelompok_umur_sesuai'],
                    'alasan'               => $d['alasan'],
                    'total_sebelum'        => (int) $d['total_sebelum'],
                    'total_sesudah'        => (int) $d['total_sesudah'],
                    'ditolak_oleh'         => $d['ditolak_oleh'],
                    'dibuat_pada'          => $d['dibuat_pada'],
                ], $tolak[$id] ?? []),
            ];
        }

        $galeri       = $this->db->table('galeri')
            ->select('judul, keterangan, tautan, sampul, urutan, aktif, dibuat_pada, diubah_pada')
            ->orderBy('urutan')->orderBy('id')->get()->getResultArray();
        $unggahan     = new Unggahan();
        $sampulGaleri = [];

        foreach ($galeri as $i => $album) {
            if (is_string($album['sampul']) && preg_match(self::POLA_SAMPUL_GALERI, $album['sampul']) === 1 && $unggahan->ada($album['sampul'])) {
                $sampulGaleri[$album['sampul']] = (string) $unggahan->jalurPenuh($album['sampul']);
            } else {
                $galeri[$i]['sampul'] = null;
            }
        }

        $isi = [
            'versi'           => self::VERSI,
            'dibuat'          => date('Y-m-d H:i:s'),
            'acara'           => trim(setting('Acara.nama') . ' Seri ' . setting('Acara.seri') . ' ' . setting('Acara.tahun')),
            'urut_berikutnya' => $this->urutBerikutnya(),
            'awalan'          => array_map(
                static fn (array $a): array => ['awalan' => (string) $a['awalan'], 'nomor_terakhir' => (int) $a['nomor_terakhir']],
                $this->db->table('awalan_nomor_dada')->select('awalan, nomor_terakhir')->get()->getResultArray(),
            ),
            'pendaftaran' => $data,
            'usulan_nama' => $this->db->table('usulan_nama_sekolah')
                ->select('npsn, pendaftaran_id, nama_lama, nama_usulan, status, nama_disetujui, alasan, diputuskan_pada, dibuat_pada, diubah_pada')
                ->orderBy('id')->get()->getResultArray(),
            // Riwayat tindakan admin (F8-01), termasuk milik pendaftaran yang sudah dihapus.
            'riwayat_admin' => $this->db->table('riwayat_admin')
                ->select('pendaftaran_id, id_pendaftaran, nama_admin, tindakan, ringkasan, dibuat_pada')
                ->orderBy('id')->get()->getResultArray(),
            // Hasil lomba semua babak dan tanda terbit per nomor (F8-04).
            'hasil_lomba' => $this->eksporHasilLomba($nomor),
            // Tanya-jawab halaman Bantuan (F9-04).
            'faq' => $this->db->table('faq')
                ->select('kategori, pertanyaan, jawaban, urutan, aktif, dibuat_pada, diubah_pada')
                ->orderBy('id')->get()->getResultArray(),
            // Tabel skor multi-lomba beserta baris yang diubah admin (F9-10).
            'tabel_skor' => (new DataTabelSkor($this->db))->eksporSemua(),
            // Album Galeri (F9-06); sampulnya ikut di folder `galeri/` zip.
            'galeri' => $galeri,
            'sekolah_disunting' => $this->db->table('sekolah')
                ->select('npsn, nama, bentuk, jenjang, status, alamat, desa_kelurahan, kecamatan, kabupaten, disunting_pada')
                ->where('disunting_pada IS NOT NULL', null, false)->orderBy('npsn')->get()->getResultArray(),
        ];

        $zip = new ZipArchive();

        if ($zip->open($tujuan, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Berkas ekspor gagal dibuat di ' . $tujuan . '. Periksa sisa ruang diska.');
        }

        $zip->addFromString('data.json', (string) json_encode($isi, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $tersalin = 0;

        foreach ($berkas as $jalur => $penuh) {
            if (is_file($penuh)) {
                $zip->addFile($penuh, 'berkas/' . $jalur);
                $tersalin++;
            }
        }

        foreach ($sampulGaleri as $jalur => $penuh) {
            $zip->addFile($penuh, 'galeri/' . basename($jalur));
        }

        if (! $zip->close()) {
            throw new RuntimeException('Berkas ekspor gagal ditulis. Periksa sisa ruang diska, lalu coba lagi.');
        }

        return ['pendaftaran' => count($data), 'atlet' => array_sum(array_map(static fn (array $p): int => count($p['atlet']), $data)), 'berkas' => $tersalin];
    }

    /**
     * Masukkan paket ke basis data dan folder unggahan server ini.
     *
     * @return array{pendaftaran: int, atlet: int, berkas: int}
     *
     * @throws RuntimeException Dengan pesan ramah bila paket ditolak atau gagal disimpan; tidak ada yang tersimpan.
     */
    public function impor(string $sumber): array
    {
        $this->sampulGaleriLama = [];
        $zip = new ZipArchive();

        if (! is_file($sumber) || $zip->open($sumber) !== true) {
            throw new RuntimeException('Berkas ' . $sumber . ' tidak ditemukan atau bukan zip ekspor pendaftaran.');
        }

        try {
            $isi = json_decode((string) $zip->getFromName('data.json'), true);

            if (! is_array($isi) || ($isi['versi'] ?? null) !== self::VERSI || ! is_array($isi['pendaftaran'] ?? null)) {
                throw new RuntimeException('Isi zip bukan ekspor pendaftaran yang dikenal. Buat ulang lewat tombol Backup data di panel admin server lama.');
            }

            $ada = $this->db->table('pendaftaran')->countAllResults();

            if ($ada > 0) {
                throw new RuntimeException("Server ini sudah punya {$ada} pendaftaran. Impor hanya untuk server yang belum punya pendaftaran, supaya data tidak bentrok.");
            }

            [$petaKu, $petaNomor] = $this->peta($isi['pendaftaran']);
            $berkas               = $this->salinBerkas($zip, $isi['pendaftaran']);

            try {
                [$isi['galeri'], $sampulBaru] = $this->salinSampulGaleri($zip, is_array($isi['galeri'] ?? null) ? $isi['galeri'] : []);
            } catch (RuntimeException $e) {
                foreach ($berkas as $penuh) {
                    @unlink($penuh);
                }

                throw $e;
            }

            $berkas = array_merge($berkas, $sampulBaru);
        } finally {
            $zip->close();
        }

        try {
            $jumlah = $this->simpan($isi, $petaKu, $petaNomor);
        } catch (Throwable $e) {
            foreach ($berkas as $penuh) {
                @unlink($penuh);
            }

            throw new RuntimeException('Data gagal disimpan, dan tidak ada yang tersimpan: ' . $e->getMessage(), 0, $e);
        }

        // Sampul album Galeri lama yang sudah diganti album dari paket (F9-06).
        $unggahan = new Unggahan();

        foreach ($this->sampulGaleriLama as $jalur) {
            $unggahan->hapus($jalur);
        }

        return $jumlah + ['berkas' => count($berkas) - count($sampulBaru)];
    }

    /**
     * Salin sampul album Galeri (F9-06) dari zip ke `unggahan/galeri/` dengan
     * nama acak baru, lalu ganti jalur `sampul` di daftar album dengan jalur
     * barunya. Sampul yang tidak ada di zip dikosongkan.
     *
     * @param list<mixed> $galeri
     *
     * @return array{0: list<mixed>, 1: list<string>} daftar album, jalur lengkap berkas yang disalin
     *
     * @throws RuntimeException Bila sebuah sampul gagal disalin; yang sudah disalin dihapus lagi.
     */
    private function salinSampulGaleri(ZipArchive $zip, array $galeri): array
    {
        $unggahan = new Unggahan();
        $folder   = Unggahan::folderDasar() . GaleriModel::FOLDER;
        $tersalin = [];

        foreach ($galeri as $i => $album) {
            if (! is_array($album)) {
                continue;
            }

            $jalur = $album['sampul'] ?? null;
            $isi   = is_string($jalur) && preg_match(self::POLA_SAMPUL_GALERI, $jalur) === 1 ? $zip->getFromName('galeri/' . basename($jalur)) : false;

            if ($isi === false) {
                $galeri[$i]['sampul'] = null;

                continue;
            }

            $baru   = 'unggahan/' . GaleriModel::FOLDER . '/' . bin2hex(random_bytes(16)) . '.' . pathinfo((string) $jalur, PATHINFO_EXTENSION);
            $tujuan = (string) $unggahan->jalurPenuh($baru);

            if ((! is_dir($folder) && ! @mkdir($folder, 0755, true) && ! is_dir($folder)) || @file_put_contents($tujuan, $isi) === false) {
                foreach ($tersalin as $penuh) {
                    @unlink($penuh);
                }

                throw new RuntimeException('Sampul album galeri gagal disalin ke ' . $folder . '. Pastikan folder public/unggahan boleh ditulisi, lalu impor lagi.');
            }

            @chmod($tujuan, 0644);
            $tersalin[]           = $tujuan;
            $galeri[$i]['sampul'] = $baru;
        }

        return [$galeri, $tersalin];
    }

    /**
     * Peta nama kelompok umur => id dan "KU|nama|kategori" => id nomor lomba di
     * server ini, untuk semua yang dipakai paket.
     *
     * @param list<array<string, mixed>> $pendaftaran
     *
     * @return array{0: array<string, int>, 1: array<string, int>}
     *
     * @throws RuntimeException Bila ada yang tidak ditemukan.
     */
    private function peta(array $pendaftaran): array
    {
        $ku = [];

        foreach ($this->db->table('kelompok_umur')->select('id, nama')->orderBy('id')->get()->getResultArray() as $k) {
            $ku[$k['nama']] ??= (int) $k['id'];
        }

        $nomor = [];

        foreach ($this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori')->orderBy('id')->get()->getResultArray() as $n) {
            $nama = array_search((int) $n['kelompok_umur_id'], $ku, true);

            if ($nama !== false) {
                $nomor[self::kunciNomor($nama, $n['nama'], $n['kategori'])] ??= (int) $n['id'];
            }
        }

        $hilang = [];

        foreach ($pendaftaran as $p) {
            foreach ($p['atlet'] ?? [] as $a) {
                if (! isset($ku[$a['kelompok_umur'] ?? ''])) {
                    $hilang['Kelompok umur ' . ($a['kelompok_umur'] ?? '')] = true;
                }

                foreach ($a['nomor'] ?? [] as $n) {
                    if (! isset($nomor[self::kunciNomor($n['kelompok_umur'] ?? '', $n['nama'] ?? '', $n['kategori'] ?? '')])) {
                        $hilang['Nomor ' . ($n['nama'] ?? '') . ' ' . ($n['kategori'] ?? '') . ' (' . ($n['kelompok_umur'] ?? '') . ')'] = true;
                    }
                }
            }

            foreach ($p['beregu'] ?? [] as $n) {
                if (! isset($nomor[self::kunciNomor($n['kelompok_umur'] ?? '', $n['nama'] ?? '', $n['kategori'] ?? '')])) {
                    $hilang['Nomor ' . ($n['nama'] ?? '') . ' ' . ($n['kategori'] ?? '') . ' (' . ($n['kelompok_umur'] ?? '') . ')'] = true;
                }
            }
        }

        if ($hilang !== []) {
            throw new RuntimeException(
                'Pengaturan server ini belum sama dengan server lama. Tidak ditemukan: ' . implode(', ', array_keys($hilang))
                . '. Samakan nama kelompok umur dan nomor lomba di Pengaturan, lalu impor lagi. Tidak ada yang diimpor.',
            );
        }

        return [$ku, $nomor];
    }

    /**
     * Salin foto dan bukti bayar dari zip ke folder unggahan.
     *
     * @param list<array<string, mixed>> $pendaftaran
     *
     * @return list<string> Jalur lengkap berkas yang disalin.
     *
     * @throws RuntimeException Bila sebuah berkas gagal disalin; yang sudah disalin dihapus lagi.
     */
    private function salinBerkas(ZipArchive $zip, array $pendaftaran): array
    {
        $daftar = [];

        foreach ($pendaftaran as $p) {
            foreach ($p['penanggung_jawab'] ?? [] as $o) {
                if ($this->berkasSah($o['foto'] ?? null)) {
                    $daftar[$o['foto']] = FotoPenanggungJawab::folderDasar();
                }
            }

            foreach ($p['bukti_bayar'] ?? [] as $b) {
                if ($this->berkasSah($b['berkas'] ?? null)) {
                    $daftar[$b['berkas']] = BerkasBuktiBayar::folderDasar();
                }
            }
        }

        $tersalin = [];

        foreach ($daftar as $jalur => $dasar) {
            $isi    = $zip->getFromName('berkas/' . $jalur);
            $tujuan = $dasar . $jalur;
            $folder = dirname($tujuan);

            if ($isi === false) {
                continue; // Berkas yang memang tidak ada di server lama dilewati; rujukannya tetap.
            }

            if ((! is_dir($folder) && ! @mkdir($folder, 0775, true) && ! is_dir($folder)) || @file_put_contents($tujuan, $isi) === false) {
                foreach ($tersalin as $penuh) {
                    @unlink($penuh);
                }

                throw new RuntimeException('Berkas ' . $jalur . ' gagal disalin ke ' . $folder . '. Pastikan folder writable/unggahan boleh ditulisi, lalu impor lagi.');
            }

            $tersalin[] = $tujuan;
        }

        return $tersalin;
    }

    /**
     * @param array<string, mixed> $isi
     * @param array<string, int>   $ku
     * @param array<string, int>   $nomor
     *
     * @return array{pendaftaran: int, atlet: int}
     */
    private function simpan(array $isi, array $ku, array $nomor): array
    {
        $jumlahAtlet = 0;
        $tabel       = fn (string $nama, array $baris): int => $this->db->table($nama)->insert($baris) ? (int) $this->db->insertID() : throw new RuntimeException('Baris ' . $nama . ' gagal disimpan.');

        $this->db->transBegin();

        try {
            $this->simpanBaris($isi, $ku, $nomor, $tabel, $jumlahAtlet);
            // Paket dari sebelum ada regu (K105): Regu A, inti menurut urutan daftar.
            PeranAwalRegu::isi($this->db);
            $this->db->transCommit();
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }

        // Di luar transaksi: ALTER TABLE di MySQL menutup transaksi yang sedang berjalan.
        $urut = max((int) ($isi['urut_berikutnya'] ?? 0), $this->urutBerikutnya());
        $this->db->query('ALTER TABLE pendaftaran AUTO_INCREMENT = ' . $urut);

        return ['pendaftaran' => count($isi['pendaftaran']), 'atlet' => $jumlahAtlet];
    }

    /**
     * @param array<string, mixed>     $isi
     * @param array<string, int>       $ku
     * @param array<string, int>       $nomor
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanBaris(array $isi, array $ku, array $nomor, callable $tabel, int &$jumlahAtlet): void
    {
        foreach ($isi['pendaftaran'] as $p) {
            $id = $tabel('pendaftaran', [
                'id'                => (int) $p['id'],
                'id_pendaftaran'    => (string) $p['id_pendaftaran'],
                // Kode bukti tetap supaya kode QR di bukti yang sudah dicetak tetap berlaku (K77).
                'kode_bukti'        => is_string($p['kode_bukti'] ?? null) && preg_match('/^[0-9a-f]{24}$/D', $p['kode_bukti']) === 1 ? $p['kode_bukti'] : null,
                // Kunci kirim wizard (K118); paket lama tidak punya, dan bentuk yang salah dikosongkan.
                'kunci_kirim'       => SimpanPendaftaran::kunciKirim($p),
                'jenjang'           => (string) $p['jenjang'],
                'npsn'              => $p['npsn'],
                'nama_sekolah'      => (string) $p['nama_sekolah'],
                'kabupaten'         => (string) $p['kabupaten'],
                'subtotal_individu' => (int) $p['subtotal_individu'],
                'subtotal_beregu'   => (int) $p['subtotal_beregu'],
                'total'             => (int) $p['total'],
                'status'            => (string) $p['status'],
                'waktu_lunas'       => $p['waktu_lunas'],
                'dibuat_pada'       => $p['dibuat_pada'],
                'diubah_pada'       => $p['diubah_pada'],
            ]);

            foreach ($p['penanggung_jawab'] ?? [] as $o) {
                $tabel('penanggung_jawab', [
                    'pendaftaran_id' => $id, 'nama' => (string) $o['nama'], 'nomor_wa' => (string) $o['nomor_wa'],
                    'foto' => $this->berkasSah($o['foto'] ?? null) ? $o['foto'] : null,
                    'dibuat_pada' => $o['dibuat_pada'], 'diubah_pada' => $o['diubah_pada'],
                ]);
            }

            foreach ($p['atlet'] ?? [] as $a) {
                $idAtlet = $tabel('atlet', [
                    'pendaftaran_id'   => $id,
                    'nama'             => (string) $a['nama'],
                    'nisn'             => (string) $a['nisn'],
                    'tanggal_lahir'    => $a['tanggal_lahir'],
                    'jenis_kelamin'    => (string) $a['jenis_kelamin'],
                    'kelompok_umur_id' => $ku[$a['kelompok_umur']],
                    'nomor_dada'       => $a['nomor_dada'],
                    'hadir_pada'       => $a['hadir_pada'] ?? null,
                    'hadir_oleh'       => $a['hadir_oleh'] ?? null,
                    'dibuat_pada'      => $a['dibuat_pada'],
                    'diubah_pada'      => $a['diubah_pada'],
                ]);
                $jumlahAtlet++;

                foreach ($a['nomor'] ?? [] as $n) {
                    $tabel('atlet_nomor', [
                        'atlet_id' => $idAtlet, 'nomor_lomba_id' => $nomor[self::kunciNomor($n['kelompok_umur'], $n['nama'], $n['kategori'])],
                        'biaya' => $n['biaya'] === null ? null : (int) $n['biaya'],
                        'regu'  => Regu::sah($n['regu'] ?? null) ? $n['regu'] : null,
                        'peran' => Regu::peranSah($n['peran'] ?? null) ? $n['peran'] : null,
                        'dibuat_pada' => $a['dibuat_pada'], 'diubah_pada' => $a['diubah_pada'],
                    ]);
                }
            }

            foreach ($p['beregu'] ?? [] as $t) {
                $tabel('pendaftaran_beregu', [
                    'pendaftaran_id' => $id, 'nomor_lomba_id' => $nomor[self::kunciNomor($t['kelompok_umur'], $t['nama'], $t['kategori'])],
                    'regu' => Regu::sah($t['regu'] ?? null) ? $t['regu'] : 'A',
                    'biaya_tim' => (int) $t['biaya_tim'], 'dibuat_pada' => $t['dibuat_pada'], 'diubah_pada' => $t['diubah_pada'],
                ]);
            }

            foreach ($p['bukti_bayar'] ?? [] as $b) {
                if ($this->berkasSah($b['berkas'] ?? null)) {
                    // Paket dari sebelum F8-02 tidak punya penanda: bukti pendaftaran Lunas dianggap sudah diperiksa.
                    $diperiksa = array_key_exists('diperiksa_pada', $b)
                        ? (is_string($b['diperiksa_pada']) ? $b['diperiksa_pada'] : null)
                        : ((string) $p['status'] === 'lunas' ? ($p['waktu_lunas'] ?? $b['waktu_unggah']) : null);
                    $tabel('bukti_bayar', [
                        'pendaftaran_id' => $id, 'berkas' => $b['berkas'], 'ukuran' => (int) $b['ukuran'], 'waktu_unggah' => $b['waktu_unggah'],
                        'diperiksa_pada' => $diperiksa,
                        'diperiksa_oleh' => $diperiksa === null ? null : mb_substr(is_string($b['diperiksa_oleh'] ?? null) ? $b['diperiksa_oleh'] : 'Data lama', 0, 100),
                    ]);
                }
            }

            foreach ($p['gabungan_dari'] ?? [] as $g) {
                if (is_string($g['id_pendaftaran'] ?? null) && preg_match('/^MAC[0-9A-Z]{1,17}$/D', $g['id_pendaftaran']) === 1) {
                    $tabel('pendaftaran_gabungan', [
                        'pendaftaran_id'      => $id,
                        'id_pendaftaran_lama' => $g['id_pendaftaran'],
                        'kode_bukti_lama'     => is_string($g['kode_bukti'] ?? null) && preg_match('/^[0-9a-f]{24}$/D', $g['kode_bukti']) === 1 ? $g['kode_bukti'] : null,
                        'dibuat_pada'         => $g['dibuat_pada'] ?? null,
                    ]);
                }
            }

            foreach ($p['atlet_ditolak'] ?? [] as $d) {
                if (! is_array($d) || preg_match('/^\d{10}$/D', (string) ($d['nisn'] ?? '')) !== 1 || ! in_array($d['jenis_kelamin'] ?? null, ['L', 'P'], true)) {
                    continue;
                }

                $tabel('atlet_ditolak', [
                    'pendaftaran_id'          => $id,
                    'nama'                    => (string) ($d['nama'] ?? ''),
                    'nisn'                    => (string) $d['nisn'],
                    'tanggal_lahir'           => (string) ($d['tanggal_lahir'] ?? ''),
                    'jenis_kelamin'           => (string) $d['jenis_kelamin'],
                    'kelompok_umur'           => (string) ($d['kelompok_umur'] ?? ''),
                    'nomor_lomba'             => json_encode(is_array($d['nomor_lomba'] ?? null) ? $d['nomor_lomba'] : [], JSON_UNESCAPED_UNICODE),
                    'biaya_individu'          => (int) ($d['biaya_individu'] ?? 0),
                    'kelompok_umur_sesuai_id' => $ku[(string) ($d['kelompok_umur_sesuai_nama'] ?? '')] ?? null,
                    'kelompok_umur_sesuai'    => is_string($d['kelompok_umur_sesuai'] ?? null) ? $d['kelompok_umur_sesuai'] : null,
                    'alasan'                  => (string) ($d['alasan'] ?? ''),
                    'total_sebelum'           => (int) ($d['total_sebelum'] ?? 0),
                    'total_sesudah'           => (int) ($d['total_sesudah'] ?? 0),
                    'ditolak_oleh'            => is_string($d['ditolak_oleh'] ?? null) ? $d['ditolak_oleh'] : null,
                    'dibuat_pada'             => $d['dibuat_pada'] ?? null,
                ]);
            }
        }

        // Nomor dada dan ID pendaftaran tidak dipakai ulang: lanjutkan dari angka server lama.
        $terakhir = [];

        foreach ($isi['awalan'] ?? [] as $a) {
            $terakhir[(string) $a['awalan']] = max($terakhir[(string) $a['awalan']] ?? 0, (int) $a['nomor_terakhir']);
        }

        foreach ($isi['pendaftaran'] as $p) {
            foreach ($p['atlet'] ?? [] as $a) {
                if (is_string($a['nomor_dada'] ?? null) && strlen($a['nomor_dada']) === 4) {
                    $terakhir[$a['nomor_dada'][0]] = max($terakhir[$a['nomor_dada'][0]] ?? 0, (int) substr($a['nomor_dada'], 1));
                }
            }
        }

        foreach ($terakhir as $awalan => $angka) {
            $this->db->query('UPDATE awalan_nomor_dada SET nomor_terakhir = GREATEST(nomor_terakhir, ?) WHERE awalan = ?', [$angka, (string) $awalan]);
        }

        $this->simpanRiwayat(is_array($isi['riwayat_admin'] ?? null) ? $isi['riwayat_admin'] : [], $isi['pendaftaran'], $tabel);
        $this->simpanTabelSkor(is_array($isi['tabel_skor'] ?? null) ? $isi['tabel_skor'] : []);
        $this->simpanHasilLomba(is_array($isi['hasil_lomba'] ?? null) ? $isi['hasil_lomba'] : [], $isi['pendaftaran'], $nomor, $tabel);
        $this->simpanSekolahDisunting(is_array($isi['sekolah_disunting'] ?? null) ? $isi['sekolah_disunting'] : [], $tabel);
        $this->simpanUsulanNama(is_array($isi['usulan_nama'] ?? null) ? $isi['usulan_nama'] : [], $isi['pendaftaran'], $tabel);
        $this->simpanFaq(is_array($isi['faq'] ?? null) ? $isi['faq'] : [], $tabel);
        $this->simpanGaleri(is_array($isi['galeri'] ?? null) ? $isi['galeri'] : [], $tabel);
    }

    /**
     * Album Galeri (F9-06) dari paket menggantikan album server ini. Paket
     * tanpa album yang sah tidak mengubah apa pun. Sampul album lama dicatat
     * untuk dihapus setelah impor berhasil.
     *
     * @param list<mixed>                                  $daftar sampul sudah diganti `salinSampulGaleri()`
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanGaleri(array $daftar, callable $tabel): void
    {
        $baris = [];

        foreach ($daftar as $g) {
            if (! is_array($g) || ! is_string($g['judul'] ?? null) || trim($g['judul']) === '') {
                continue;
            }

            $baris[] = [
                'judul'       => mb_substr(trim($g['judul']), 0, GaleriModel::PANJANG['judul']),
                'keterangan'  => mb_substr(is_string($g['keterangan'] ?? null) ? $g['keterangan'] : '', 0, GaleriModel::PANJANG['keterangan']),
                'tautan'      => GaleriModel::tautanSah($g['tautan'] ?? null) ? $g['tautan'] : null,
                'sampul'      => is_string($g['sampul'] ?? null) && preg_match(self::POLA_SAMPUL_GALERI, $g['sampul']) === 1 ? $g['sampul'] : null,
                'urutan'      => min(GaleriModel::URUTAN_MAKSIMAL, max(0, (int) ($g['urutan'] ?? 0))),
                'aktif'       => (int) ($g['aktif'] ?? 1) === 1 ? 1 : 0,
                'dibuat_pada' => is_string($g['dibuat_pada'] ?? null) ? $g['dibuat_pada'] : null,
                'diubah_pada' => is_string($g['diubah_pada'] ?? null) ? $g['diubah_pada'] : null,
            ];
        }

        if ($baris === []) {
            return;
        }

        $this->sampulGaleriLama = array_values(array_filter(array_column(
            $this->db->table('galeri')->select('sampul')->get()->getResultArray(),
            'sampul',
        ), 'is_string'));

        if (! $this->db->table('galeri')->where('id >', 0)->delete()) {
            throw new RuntimeException('Album galeri lama gagal dihapus.');
        }

        foreach ($baris as $b) {
            $tabel('galeri', $b);
        }
    }

    /**
     * Tanya-jawab Bantuan (F9-04) dari paket menggantikan tanya-jawab server
     * ini. Paket tanpa tanya-jawab yang sah tidak mengubah apa pun.
     *
     * @param list<mixed>                                  $daftar
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanFaq(array $daftar, callable $tabel): void
    {
        $baris = [];

        foreach ($daftar as $f) {
            if (! is_array($f) || ! FaqModel::kategoriSah($f['kategori'] ?? null) || ! is_string($f['pertanyaan'] ?? null) || ! is_string($f['jawaban'] ?? null)
                || trim($f['pertanyaan']) === '' || trim($f['jawaban']) === '') {
                continue;
            }

            $baris[] = [
                'kategori'    => $f['kategori'],
                'pertanyaan'  => mb_substr($f['pertanyaan'], 0, FaqModel::PANJANG['pertanyaan']),
                'jawaban'     => $f['jawaban'],
                'urutan'      => min(FaqModel::URUTAN_MAKSIMAL, max(0, (int) ($f['urutan'] ?? 0))),
                'aktif'       => (int) ($f['aktif'] ?? 1) === 1 ? 1 : 0,
                'dibuat_pada' => is_string($f['dibuat_pada'] ?? null) ? $f['dibuat_pada'] : null,
                'diubah_pada' => is_string($f['diubah_pada'] ?? null) ? $f['diubah_pada'] : null,
            ];
        }

        if ($baris === []) {
            return;
        }

        if (! $this->db->table('faq')->where('id >', 0)->delete()) {
            throw new RuntimeException('Tanya-jawab Bantuan lama gagal dihapus.');
        }

        foreach ($baris as $b) {
            $tabel('faq', $b);
        }
    }

    /**
     * Riwayat tindakan admin (F8-01). `pendaftaran_id` dipertahankan bila
     * pendaftarannya ikut diimpor (ID-nya sama), selain itu kosong (pendaftaran
     * yang sudah dihapus). `admin_id` dikosongkan karena akun admin server baru
     * berbeda; nama admin saat itu tetap.
     *
     * @param list<mixed>                                  $daftar
     * @param list<array<string, mixed>>                   $pendaftaran
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanRiwayat(array $daftar, array $pendaftaran, callable $tabel): void
    {
        $ada = array_flip(array_map(static fn (array $p): int => (int) $p['id'], $pendaftaran));

        foreach ($daftar as $r) {
            if (! is_array($r) || ! is_string($r['id_pendaftaran'] ?? null) || ! is_string($r['tindakan'] ?? null) || ! is_string($r['ringkasan'] ?? null)) {
                continue;
            }

            $id = is_numeric($r['pendaftaran_id'] ?? null) ? (int) $r['pendaftaran_id'] : null;

            $tabel('riwayat_admin', [
                'pendaftaran_id' => $id !== null && isset($ada[$id]) ? $id : null,
                'id_pendaftaran' => mb_substr($r['id_pendaftaran'], 0, 20),
                'admin_id'       => null,
                'nama_admin'     => mb_substr(is_string($r['nama_admin'] ?? null) ? $r['nama_admin'] : 'Admin', 0, 100),
                'tindakan'       => mb_substr($r['tindakan'], 0, 30),
                'ringkasan'      => $r['ringkasan'],
                'dibuat_pada'    => is_string($r['dibuat_pada'] ?? null) ? $r['dibuat_pada'] : null,
            ]);
        }
    }

    /**
     * Nama dan data sekolah yang disunting admin di server lama (K97) menimpa
     * data impor sekolah di server ini; NPSN yang belum ada ditambahkan.
     *
     * @param list<mixed>                                  $daftar
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanSekolahDisunting(array $daftar, callable $tabel): void
    {
        $kolom = ['nama', 'bentuk', 'jenjang', 'status', 'alamat', 'desa_kelurahan', 'kecamatan', 'kabupaten', 'disunting_pada'];
        $waktu = date('Y-m-d H:i:s');

        foreach ($daftar as $baris) {
            if (! is_array($baris) || ! SekolahModel::npsnSah((string) ($baris['npsn'] ?? '')) || trim((string) ($baris['nama'] ?? '')) === '') {
                continue;
            }

            $data = array_intersect_key($baris, array_flip($kolom)) + ['diubah_pada' => $waktu];
            $ada  = $this->db->table('sekolah')->where('npsn', $baris['npsn'])->countAllResults() > 0;

            if ($ada) {
                if (! $this->db->table('sekolah')->where('npsn', $baris['npsn'])->update($data)) {
                    throw new RuntimeException('Data sekolah ' . $baris['npsn'] . ' gagal disimpan.');
                }
            } else {
                $tabel('sekolah', ['npsn' => $baris['npsn'], 'dibuat_pada' => $waktu] + $data);
            }
        }
    }

    /**
     * Usulan nama sekolah (K99), beserta riwayat keputusannya. Pendaftaran
     * pengusul memakai id yang sama dengan server lama.
     *
     * @param list<mixed>                                  $daftar
     * @param list<array<string, mixed>>                   $pendaftaran
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanUsulanNama(array $daftar, array $pendaftaran, callable $tabel): void
    {
        $idAda  = array_flip(array_map(static fn (array $p): int => (int) $p['id'], $pendaftaran));
        $status = [UsulanNamaSekolahModel::MENUNGGU, UsulanNamaSekolahModel::DISETUJUI, UsulanNamaSekolahModel::DITOLAK];

        foreach ($daftar as $u) {
            if (! is_array($u) || ! SekolahModel::npsnSah((string) ($u['npsn'] ?? '')) || ! in_array($u['status'] ?? null, $status, true)) {
                continue;
            }

            $pendaftaranId = isset($u['pendaftaran_id']) && isset($idAda[(int) $u['pendaftaran_id']]) ? (int) $u['pendaftaran_id'] : null;

            $tabel('usulan_nama_sekolah', [
                'npsn'            => (string) $u['npsn'],
                'pendaftaran_id'  => $pendaftaranId,
                'nama_lama'       => (string) ($u['nama_lama'] ?? ''),
                'nama_usulan'     => (string) ($u['nama_usulan'] ?? ''),
                'status'          => (string) $u['status'],
                'nama_disetujui'  => $u['nama_disetujui'] ?? null,
                'alasan'          => $u['alasan'] ?? null,
                'diputuskan_pada' => $u['diputuskan_pada'] ?? null,
                'dibuat_pada'     => $u['dibuat_pada'] ?? null,
                'diubah_pada'     => $u['diubah_pada'] ?? null,
            ]);
        }
    }

    /**
     * Jadwal babak dari paket (F9-07), diperiksa seperti isian admin; isian
     * yang tidak sah dikosongkan.
     *
     * @param array<string, mixed> $b
     *
     * @return array{tanggal: string|null, jam_mulai: string|null, lokasi: string|null}
     */
    private static function jadwalBabak(array $b): array
    {
        $kosong  = ['tanggal' => null, 'jam_mulai' => null, 'lokasi' => null];
        $periksa = JadwalPertandingan::periksa([
            'tanggal'   => is_string($b['tanggal'] ?? null) ? $b['tanggal'] : '',
            'jam_mulai' => is_string($b['jam_mulai'] ?? null) ? $b['jam_mulai'] : '',
            'lokasi'    => is_string($b['lokasi'] ?? null) ? mb_substr($b['lokasi'], 0, JadwalPertandingan::LOKASI_MAKSIMAL) : '',
        ]);

        return $periksa['galat'] === [] ? $periksa['data'] : $kosong;
    }

    /**
     * Aturan seri satu nomor dari paket (F9-08); isian tidak sah = bawaan.
     *
     * @param array<string, mixed> $n
     *
     * @return array{tempat_per_seri: int|null, urutan_lintasan: string|null}
     */
    private static function aturanSeriNomor(array $n): array
    {
        $urutan = is_string($n['urutan_lintasan'] ?? null) ? trim($n['urutan_lintasan']) : '';
        $tempat = self::angkaKecil($n['tempat_per_seri'] ?? null, 0);

        return [
            'tempat_per_seri' => $tempat,
            'urutan_lintasan' => $urutan !== '' && PembagiSeri::periksaUrutan($urutan, $tempat ?? PembagiSeri::LINTASAN_BAWAAN) === null ? PembagiSeri::rapikanUrutan($urutan) : null,
        ];
    }

    /**
     * Keadaan seri satu babak dari paket (F9-08); paket lama = belum dibagi.
     *
     * @param array<string, mixed> $b
     *
     * @return array{jumlah_seri: int|null, lolos_q: int|null, lolos_q_kecil: int|null, benih: int|null, seri_dikunci_pada: string|null}
     */
    private static function seriBabak(array $b): array
    {
        $kunci = is_string($b['seri_dikunci_pada'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/D', $b['seri_dikunci_pada']) === 1 ? $b['seri_dikunci_pada'] : null;
        $benih = is_numeric($b['benih'] ?? null) && (int) $b['benih'] > 0 && (int) $b['benih'] <= 4_294_967_295 ? (int) $b['benih'] : null;

        return [
            'jumlah_seri'       => self::angkaKecil($b['jumlah_seri'] ?? null),
            'lolos_q'           => self::angkaKecil($b['lolos_q'] ?? null, 0),
            'lolos_q_kecil'     => self::angkaKecil($b['lolos_q_kecil'] ?? null, 0),
            'benih'             => $benih,
            'seri_dikunci_pada' => $kunci,
        ];
    }

    /**
     * Angka 1–99 (atau mulai `$terkecil`) dari paket, selain itu `null`.
     */
    private static function angkaKecil(mixed $nilai, int $terkecil = 1): ?int
    {
        return is_numeric($nilai) && (int) $nilai >= $terkecil && (int) $nilai <= 99 ? (int) $nilai : null;
    }

    /**
     * Hasil lomba per nomor (F8-04): jenis hasil, waktu terbit, dan babak
     * beserta hasilnya. Peserta individu dikenali dari NISN, regu dari ID
     * pendaftaran (tetap sama saat diimpor) dan nama regunya.
     *
     * @param array<int, array{kelompok_umur: string, nama: string, kategori: string}> $nomor id => kunci nomor
     *
     * @return list<array<string, mixed>>
     */
    private function eksporHasilLomba(array $nomor): array
    {
        $hasil = [];

        foreach ($this->db->table('hasil h')->select('h.*, a.nisn')->join('atlet a', 'a.id = h.atlet_id', 'left')->orderBy('h.id')->get()->getResultArray() as $h) {
            $hasil[(int) $h['babak_id']][] = [
                'nisn'             => $h['nisn'],
                'pendaftaran_id'   => $h['pendaftaran_id'] === null ? null : (int) $h['pendaftaran_id'],
                'regu'             => $h['regu'],
                'prestasi'         => $h['prestasi'] === null ? null : (int) $h['prestasi'],
                'rincian'          => $h['rincian'],
                'keterangan'       => $h['keterangan'],
                'peringkat'        => $h['peringkat'] === null ? null : (int) $h['peringkat'],
                'peringkat_manual' => (int) $h['peringkat_manual'],
                // Seri dan lintasan (F9-08).
                'seri'     => $h['seri'] === null ? null : (int) $h['seri'],
                'lintasan' => $h['lintasan'] === null ? null : (int) $h['lintasan'],
            ];
        }

        $babak = [];

        foreach ($this->db->table('babak')->orderBy('id')->get()->getResultArray() as $b) {
            $babak[(int) $b['nomor_lomba_id']][] = [
                'nama' => $b['nama'], 'final' => (int) $b['final'], 'urutan' => (int) $b['urutan'], 'dibuat_pada' => $b['dibuat_pada'],
                // Jadwal pertandingan (F9-07).
                'tanggal' => $b['tanggal'], 'jam_mulai' => $b['jam_mulai'], 'lokasi' => $b['lokasi'],
                // Seri dan lintasan (F9-08).
                'jumlah_seri' => $b['jumlah_seri'], 'lolos_q' => $b['lolos_q'], 'lolos_q_kecil' => $b['lolos_q_kecil'],
                'benih' => $b['benih'], 'seri_dikunci_pada' => $b['seri_dikunci_pada'],
                'hasil' => $hasil[(int) $b['id']] ?? [],
            ];
        }

        $daftar = [];

        $kodeTabel = array_column($this->db->table('tabel_skor')->select('id, kode')->get()->getResultArray(), 'kode', 'id');

        foreach ($this->db->table('nomor_lomba')->select('id, jenis_hasil, tabel_skor_id, komponen_skor, tempat_per_seri, urutan_lintasan, hasil_terbit_pada')->orderBy('id')->get()->getResultArray() as $n) {
            $id = (int) $n['id'];

            if (isset($nomor[$id]) && (isset($babak[$id]) || $n['hasil_terbit_pada'] !== null || $n['tabel_skor_id'] !== null || $n['tempat_per_seri'] !== null || $n['urutan_lintasan'] !== null)) {
                $daftar[] = $nomor[$id] + [
                    'jenis_hasil'   => $n['jenis_hasil'],
                    'tabel_skor'    => $kodeTabel[$n['tabel_skor_id']] ?? null,
                    'komponen_skor' => $n['komponen_skor'],
                    'terbit_pada'   => $n['hasil_terbit_pada'],
                    // Aturan seri (F9-08).
                    'tempat_per_seri' => $n['tempat_per_seri'] === null ? null : (int) $n['tempat_per_seri'],
                    'urutan_lintasan' => $n['urutan_lintasan'],
                    'babak'         => $babak[$id] ?? [],
                ];
            }
        }

        return $daftar;
    }

    /**
     * Hasil lomba dari paket (F8-04). Nomor yang tidak ada di server ini dan
     * peserta yang tidak ditemukan dilewati; peringkat disalin apa adanya.
     *
     * @param list<mixed>                                  $daftar
     * @param list<array<string, mixed>>                   $pendaftaran
     * @param array<string, int>                           $nomor
     * @param callable(string, array<string, mixed>): int $tabel
     */
    private function simpanHasilLomba(array $daftar, array $pendaftaran, array $nomor, callable $tabel): void
    {
        if ($daftar === []) {
            return;
        }

        $ada       = array_flip(array_map(static fn (array $p): int => (int) $p['id'], $pendaftaran));
        $atlet     = array_map('intval', array_column($this->db->table('atlet')->select('id, nisn')->get()->getResultArray(), 'id', 'nisn'));
        $kodeTabel = array_map('intval', array_column($this->db->table('tabel_skor')->select('id, kode')->get()->getResultArray(), 'id', 'kode'));

        foreach ($daftar as $n) {
            $idNomor = is_array($n) ? ($nomor[self::kunciNomor((string) ($n['kelompok_umur'] ?? ''), (string) ($n['nama'] ?? ''), (string) ($n['kategori'] ?? ''))] ?? null) : null;

            if ($idNomor === null) {
                continue;
            }

            $ubah = ['hasil_terbit_pada' => is_string($n['terbit_pada'] ?? null) ? $n['terbit_pada'] : null];

            if (JenisHasil::sah($n['jenis_hasil'] ?? null)) {
                $ubah['jenis_hasil'] = $n['jenis_hasil'];
            }

            // Poin multi-lomba (F9-10): tabel dicocokkan menurut kodenya.
            $idTabel = is_string($n['tabel_skor'] ?? null) ? ($kodeTabel[$n['tabel_skor']] ?? null) : null;

            if (($n['jenis_hasil'] ?? null) === JenisHasil::MULTI && $idTabel === null) {
                unset($ubah['jenis_hasil']);
            } elseif ($idTabel !== null) {
                $ubah['tabel_skor_id'] = $idTabel;
                $ubah['komponen_skor'] = is_string($n['komponen_skor'] ?? null) && $n['komponen_skor'] !== '' ? mb_substr($n['komponen_skor'], 0, 255) : null;
            }

            // Aturan seri (F9-08); paket lama tanpa aturan = bawaan.
            $ubah += self::aturanSeriNomor($n);

            $this->db->table('nomor_lomba')->where('id', $idNomor)->update($ubah);

            foreach (is_array($n['babak'] ?? null) ? $n['babak'] : [] as $b) {
                if (! is_array($b) || ! is_string($b['nama'] ?? null)) {
                    continue;
                }

                $idBabak = $tabel('babak', [
                    'nomor_lomba_id' => $idNomor, 'nama' => mb_substr($b['nama'], 0, 60), 'final' => (int) ($b['final'] ?? 0) === 1 ? 1 : 0,
                    'urutan' => (int) ($b['urutan'] ?? 0), 'dibuat_pada' => $b['dibuat_pada'] ?? null, 'diubah_pada' => $b['dibuat_pada'] ?? null,
                    // Jadwal pertandingan (F9-07) menyusul; paket lama tanpa jadwal = belum dijadwalkan.
                ] + self::jadwalBabak($b) + self::seriBabak($b));

                foreach (is_array($b['hasil'] ?? null) ? $b['hasil'] : [] as $h) {
                    $idAtlet = is_array($h) && is_string($h['nisn'] ?? null) ? ($atlet[$h['nisn']] ?? null) : null;
                    $idDaftar = is_array($h) && is_numeric($h['pendaftaran_id'] ?? null) && isset($ada[(int) $h['pendaftaran_id']]) ? (int) $h['pendaftaran_id'] : null;

                    if ($idAtlet === null && ($idDaftar === null || ! Regu::sah($h['regu'] ?? null))) {
                        continue;
                    }

                    $tabel('hasil', [
                        'babak_id'         => $idBabak,
                        'atlet_id'         => $idAtlet,
                        'pendaftaran_id'   => $idAtlet === null ? $idDaftar : null,
                        'regu'             => $idAtlet === null ? $h['regu'] : null,
                        'prestasi'         => is_numeric($h['prestasi'] ?? null) ? (int) $h['prestasi'] : null,
                        'rincian'          => is_string($h['rincian'] ?? null) && TabelSkor::bacaRincian($h['rincian']) !== [] ? TabelSkor::tulisRincian(TabelSkor::bacaRincian($h['rincian'])) : null,
                        'keterangan'       => Peringkat::keterangan($h['keterangan'] ?? null),
                        'peringkat'        => is_numeric($h['peringkat'] ?? null) ? (int) $h['peringkat'] : null,
                        'peringkat_manual' => (int) ($h['peringkat_manual'] ?? 0) === 1 ? 1 : 0,
                        // Seri dan lintasan (F9-08).
                        'seri'     => self::angkaKecil($h['seri'] ?? null),
                        'lintasan' => self::angkaKecil($h['lintasan'] ?? null),
                    ]);
                }
            }
        }
    }

    /**
     * Tabel skor dari paket (F9-10): dibuat, atau barisnya diganti bila kodenya
     * sudah ada di server ini. Tabel yang rusak dilewati.
     *
     * @param list<mixed> $daftar
     */
    private function simpanTabelSkor(array $daftar): void
    {
        $data = new DataTabelSkor($this->db);

        foreach ($daftar as $t) {
            if (is_array($t) && TabelSkor::periksaData($t) === []) {
                $data->simpanData($t);
            }
        }
    }

    /**
     * Nomor `pendaftaran.id` berikutnya (nomor urut ID pendaftaran, K32).
     */
    private function urutBerikutnya(): int
    {
        $status = $this->db->query("SHOW TABLE STATUS LIKE 'pendaftaran'")->getRowArray();
        $max    = (int) ($this->db->table('pendaftaran')->selectMax('id')->get()->getRow()->id ?? 0);

        return max((int) ($status['Auto_increment'] ?? 0), $max + 1);
    }

    private function berkasSah(mixed $jalur): bool
    {
        return is_string($jalur) && preg_match(self::POLA_BERKAS, $jalur) === 1;
    }

    private static function kunciNomor(string $ku, string $nama, string $kategori): string
    {
        return $ku . '|' . $nama . '|' . $kategori;
    }
}
