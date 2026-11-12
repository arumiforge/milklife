<?php

namespace Tests\Support;

use App\Libraries\Panel\BerkasBuktiBayar;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use App\Libraries\Pendaftaran\IdPendaftaran;

/**
 * Data contoh untuk uji panel data (Tahap 6): pendaftaran, atlet, tim
 * beregu, foto penanggung jawab, dan bukti bayar. Semua nama, NISN, NPSN,
 * dan nomor WA karangan; gambar dibuat GD di uji.
 *
 * Dipakai di kelas turunan `UjiBasisData` (biasanya bersama `KirimFormulir`):
 *
 *     final class PanelPendaftaranDaftarTest extends UjiBasisData
 *     {
 *         use KirimFormulir;
 *         use PanelDataContoh;
 *
 *         protected function setUp(): void
 *         {
 *             parent::setUp();
 *             $this->siapkanDataPanel();          // data induk, awalan nomor dada, pengaturan
 *         }
 *
 *         protected function tearDown(): void
 *         {
 *             $this->bersihkanBerkasPanel();      // folder foto dan bukti bayar sementara
 *             parent::tearDown();
 *         }
 *
 *         public function testContoh(): void
 *         {
 *             $a = $this->buatPendaftaranPanel();                       // SD, Kudus, 2 atlet, Menunggu
 *             $b = $this->buatPendaftaranPanel([
 *                 'sekolah' => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
 *                 'status'  => 'lunas',
 *                 'atlet'   => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
 *             ]);
 *             $c = $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4)]);   // satu tim Kanga's Escape
 *
 *             $c['id'];               // pendaftaran.id
 *             $c['id_pendaftaran'];   // 'MAC2A0000326'
 *             $c['atlet'];            // daftar atlet.id, urut simpan
 *             $c['penanggung_jawab']; // daftar penanggung_jawab.id
 *             $c['total'];            // 100000
 *         }
 *     }
 *
 * Kunci nomor lomba dan kelompok umur sama dengan `isiDataIndukPendaftaran()`
 * (`PendaftaranContoh`): `ku10`, `ku12`, `ku15`, `lari60putra`, `lari60putri`,
 * `frog`, `turbo`, `kanga` (beregu putra, Rp100.000), `estafet` (beregu mix,
 * Rp150.000), `lari60putraKu12`, `lari80putri`.
 *
 * Biaya dihitung seperti `BiayaPendaftaran`: biaya kelompok umur untuk setiap
 * nomor individu, biaya tim untuk setiap nomor beregu (0 bila nomornya ada di
 * `susulan`).
 */
trait PanelDataContoh
{
    use PendaftaranContoh;

    /**
     * Peta kunci → id data induk (hasil `bukaPendaftaranUji()`).
     *
     * @var array<string, int>
     */
    protected array $idInduk = [];

    /**
     * Folder dasar bukti bayar selama uji (diisi `pakaiFolderBuktiBayarSementara()`).
     */
    protected string $folderBuktiBayar = '';

    private int $nisnPanelBerikut = 9900100001;

    /**
     * Data induk, awalan nomor dada (KU 10 → 2, KU 12 → 3, KU 15 → 4),
     * pengaturan acara, dan jadwal pendaftaran terbuka.
     *
     * @return array<string, int>
     */
    protected function siapkanDataPanel(): array
    {
        $this->idInduk = $this->bukaPendaftaranUji();

        return $this->idInduk;
    }

    /**
     * Satu atlet karangan untuk `buatPendaftaranPanel()`. Bawaan: putra KU 10
     * lahir 12 April 2017, NISN berurutan dari 9900100001, nomor Lari 60 m putra.
     *
     * @param array<string, mixed> $ubah Kunci: `nama`, `nisn`, `tanggal_lahir`, `jenis_kelamin`,
     *                                   `ku`, `nomor` (daftar kunci nomor lomba), `nomor_dada`.
     *
     * @return array<string, mixed>
     */
    protected function atletPanel(array $ubah = []): array
    {
        $nisn = (string) ($ubah['nisn'] ?? $this->nisnPanelBerikut++);

        return $ubah + [
            'nama'          => 'Atlet Karangan ' . substr($nisn, -3),
            'nisn'          => $nisn,
            'tanggal_lahir' => '2017-04-12',
            'jenis_kelamin' => 'L',
            'ku'            => 'ku10',
            'nomor'         => ['lari60putra'],
            'nomor_dada'    => null,
        ];
    }

    /**
     * Anggota tim karangan untuk satu nomor beregu KU 10.
     *
     * - `kanga`: `$jumlah` atlet putra.
     * - `estafet` (mix): separuh putra lalu separuh putri (sisa ganjil putra).
     *
     * @param array<string, mixed> $ubah Diterapkan ke setiap atlet (misalnya `nomor_dada`).
     *
     * @return list<array<string, mixed>>
     */
    protected function timPanel(string $nomor, int $jumlah, array $ubah = []): array
    {
        $hasil = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $jenisKelamin = $nomor === 'estafet' && $i >= (int) ceil($jumlah / 2) ? 'P' : 'L';
            $hasil[]      = $this->atletPanel($ubah + [
                'jenis_kelamin' => $jenisKelamin,
                'tanggal_lahir' => $jenisKelamin === 'P' ? '2016-08-30' : '2017-04-12',
                'nomor'         => [$nomor],
            ]);
        }

        return $hasil;
    }

    /**
     * Tulis satu pendaftaran lengkap langsung ke basis data, tanpa pemeriksaan
     * aturan (untuk menyiapkan keadaan uji panel dengan cepat).
     *
     * @param array<string, mixed> $data Kunci (semuanya pilihan):
     *                                   - `sekolah`: `nama`, `npsn` (`null` untuk tanpa NPSN), `jenjang`, `kabupaten`;
     *                                     bawaan SD KARANGAN MAJU, 99999911, A, Kudus
     *                                   - `status`: `menunggu` (bawaan) atau `lunas` (`waktu_lunas` ikut diisi)
     *                                   - `penanggung_jawab`: daftar `['nama' => …, 'nomor_wa' => …]`; bawaan satu orang
     *                                   - `foto`: `true` untuk menyimpan foto penanggung jawab buatan GD
     *                                     (folder sementara disiapkan otomatis)
     *                                   - `atlet`: daftar dari `atletPanel()`/`timPanel()`; bawaan satu putra
     *                                     (Lari 60 m dan Frog Jump) dan satu putri (Lari 60 m)
     *                                   - `susulan`: kunci nomor beregu yang timnya tanpa biaya (K07)
     *                                   - `dibuat_pada`: waktu daftar, `Y-m-d H:i:s`
     *
     * @return array{id: int, id_pendaftaran: string, atlet: list<int>, penanggung_jawab: list<int>, subtotal_individu: int, subtotal_beregu: int, total: int}
     */
    protected function buatPendaftaranPanel(array $data = []): array
    {
        if ($this->idInduk === []) {
            $this->siapkanDataPanel();
        }

        $sekolah = ($data['sekolah'] ?? []) + ['nama' => 'SD KARANGAN MAJU', 'npsn' => '99999911', 'jenjang' => 'A', 'kabupaten' => 'Kudus'];
        $status  = $data['status'] ?? 'menunggu';
        $atlet   = $data['atlet'] ?? [
            $this->atletPanel(['nomor' => ['lari60putra', 'frog']]),
            $this->atletPanel(['jenis_kelamin' => 'P', 'tanggal_lahir' => '2016-08-30', 'nomor' => ['lari60putri'], 'nama' => 'Siti Karangan']),
        ];
        $orang   = $data['penanggung_jawab'] ?? [['nama' => 'Ibu Karangan', 'nomor_wa' => '6281200000001']];
        $susulan = $data['susulan'] ?? [];
        $waktu   = $data['dibuat_pada'] ?? date('Y-m-d H:i:s');

        // Biaya dari data induk di basis data.
        $biayaKu = array_column($this->db->table('kelompok_umur')->select('id, biaya_individu')->get()->getResultArray(), 'biaya_individu', 'id');
        $nomorDb = array_column($this->db->table('nomor_lomba')->select('id, jenis, biaya_beregu')->get()->getResultArray(), null, 'id');

        $subtotalIndividu = 0;
        $tim              = [];

        foreach ($atlet as $satu) {
            foreach ($satu['nomor'] as $kunci) {
                $nomor = $nomorDb[$this->idInduk[$kunci]];

                if ($nomor['jenis'] === 'individu') {
                    $subtotalIndividu += (int) $biayaKu[$this->idInduk[$satu['ku']]];
                } else {
                    $tim[$kunci] = in_array($kunci, $susulan, true) ? 0 : (int) $nomor['biaya_beregu'];
                }
            }
        }

        $subtotalBeregu = array_sum($tim);

        $this->db->table('pendaftaran')->insert([
            'jenjang'           => $sekolah['jenjang'],
            'npsn'              => $sekolah['npsn'],
            'nama_sekolah'      => $sekolah['nama'],
            'kabupaten'         => $sekolah['kabupaten'],
            'subtotal_individu' => $subtotalIndividu,
            'subtotal_beregu'   => $subtotalBeregu,
            'total'             => $subtotalIndividu + $subtotalBeregu,
            'status'            => $status,
            'waktu_lunas'       => $status === 'lunas' ? $waktu : null,
            'dibuat_pada'       => $waktu,
            'diubah_pada'       => $waktu,
        ]);
        $id            = (int) $this->db->insertID();
        $idPendaftaran = IdPendaftaran::buat(2, $sekolah['jenjang'], $id, 2026);
        $this->db->table('pendaftaran')->where('id', $id)->update(['id_pendaftaran' => $idPendaftaran]);

        $idOrang = [];

        foreach ($orang as $satu) {
            $foto = null;

            if (! empty($data['foto'])) {
                if ($this->folderFoto === '') {
                    $this->pakaiFolderFotoSementara();
                }

                $foto = (new FotoPenanggungJawab())->simpan((string) FotoPenanggungJawab::baca(self::fotoContoh()));
            }

            $this->db->table('penanggung_jawab')->insert([
                'pendaftaran_id' => $id, 'nama' => $satu['nama'], 'nomor_wa' => $satu['nomor_wa'], 'foto' => $foto,
                'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
            ]);
            $idOrang[] = (int) $this->db->insertID();
        }

        $idAtlet = [];

        foreach ($atlet as $satu) {
            $this->db->table('atlet')->insert([
                'pendaftaran_id'   => $id,
                'nama'             => $satu['nama'],
                'nisn'             => $satu['nisn'],
                'tanggal_lahir'    => $satu['tanggal_lahir'],
                'jenis_kelamin'    => $satu['jenis_kelamin'],
                'kelompok_umur_id' => $this->idInduk[$satu['ku']],
                'nomor_dada'       => $satu['nomor_dada'],
                'dibuat_pada'      => $waktu,
                'diubah_pada'      => $waktu,
            ]);
            $atletId   = (int) $this->db->insertID();
            $idAtlet[] = $atletId;

            foreach ($satu['nomor'] as $kunci) {
                $nomor = $nomorDb[$this->idInduk[$kunci]];
                $this->db->table('atlet_nomor')->insert([
                    'atlet_id'       => $atletId,
                    'nomor_lomba_id' => $this->idInduk[$kunci],
                    'biaya'          => $nomor['jenis'] === 'individu' ? (int) $biayaKu[$this->idInduk[$satu['ku']]] : null,
                    'dibuat_pada'    => $waktu,
                    'diubah_pada'    => $waktu,
                ]);
            }
        }

        foreach ($tim as $kunci => $biaya) {
            $this->db->table('pendaftaran_beregu')->insert([
                'pendaftaran_id' => $id, 'nomor_lomba_id' => $this->idInduk[$kunci], 'biaya_tim' => $biaya,
                'dibuat_pada' => $waktu, 'diubah_pada' => $waktu,
            ]);
        }

        return [
            'id'                => $id,
            'id_pendaftaran'    => $idPendaftaran,
            'atlet'             => $idAtlet,
            'penanggung_jawab'  => $idOrang,
            'subtotal_individu' => $subtotalIndividu,
            'subtotal_beregu'   => $subtotalBeregu,
            'total'             => $subtotalIndividu + $subtotalBeregu,
        ];
    }

    /**
     * Ganti folder bukti bayar ke folder sementara. `bersihkanBerkasPanel()`
     * menghapusnya lagi.
     */
    protected function pakaiFolderBuktiBayarSementara(): void
    {
        $this->folderBuktiBayar = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-bukti-' . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
        mkdir($this->folderBuktiBayar, 0777, true);
        BerkasBuktiBayar::gantiFolderDasar($this->folderBuktiBayar);
    }

    /**
     * Simpan `$jumlah` bukti bayar (gambar buatan GD) untuk satu pendaftaran.
     *
     * @return list<int> id baris `bukti_bayar`
     */
    protected function buatBuktiBayarPanel(int $pendaftaranId, int $jumlah = 1): array
    {
        if ($this->folderBuktiBayar === '') {
            $this->pakaiFolderBuktiBayarSementara();
        }

        $id = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $hasil = (new BerkasBuktiBayar())->simpanIsi(self::gambarPanel(120, 80));
            $this->db->table('bukti_bayar')->insert(['pendaftaran_id' => $pendaftaranId] + $hasil + ['waktu_unggah' => date('Y-m-d H:i:s')]);
            $id[] = (int) $this->db->insertID();
        }

        return $id;
    }

    /**
     * Berkas bukti bayar yang ada di folder sementara, sebagai jalur relatif `bukti-bayar/….jpg`.
     *
     * @return list<string>
     */
    protected function berkasBuktiBayarTersimpan(): array
    {
        $daftar = array_map(
            static fn (string $berkas): string => BerkasBuktiBayar::FOLDER . '/' . basename($berkas),
            glob($this->folderBuktiBayar . BerkasBuktiBayar::FOLDER . DIRECTORY_SEPARATOR . '*.jpg') ?: [],
        );
        sort($daftar);

        return $daftar;
    }

    /**
     * Hapus folder foto dan bukti bayar sementara. Panggil di `tearDown()`.
     */
    protected function bersihkanBerkasPanel(): void
    {
        $this->hapusFolderFotoSementara();
        $this->folderFoto = '';

        BerkasBuktiBayar::gantiFolderDasar(null);

        if ($this->folderBuktiBayar !== '' && is_dir($this->folderBuktiBayar)) {
            helper('filesystem');
            delete_files($this->folderBuktiBayar, true);
            rmdir($this->folderBuktiBayar);
        }

        $this->folderBuktiBayar = '';
    }

    /**
     * Gambar karangan buatan GD (bukan foto orang), dua warna supaya tidak
     * kosong. `$jenis`: `png`, `jpg`, atau `webp`.
     */
    protected static function gambarPanel(int $lebar, int $tinggi, string $jenis = 'png'): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 2, 31, 123));
        imagefilledrectangle($gambar, 0, 0, intdiv($lebar, 2), intdiv($tinggi, 2), (int) imagecolorallocate($gambar, 232, 95, 49));

        ob_start();

        match ($jenis) {
            'jpg'  => imagejpeg($gambar, null, 85),
            'webp' => imagewebp($gambar),
            default => imagepng($gambar),
        };

        $isi = (string) ob_get_clean();
        imagedestroy($gambar);

        return $isi;
    }
}
