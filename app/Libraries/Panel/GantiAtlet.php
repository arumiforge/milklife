<?php

namespace App\Libraries\Panel;

use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\KelompokUmurAtlet;
use App\Libraries\Pendaftaran\Pemeriksa\MasukanPeriksa;
use App\Libraries\Pendaftaran\PemeriksaPendaftaran;
use App\Models\AtletModel;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use RuntimeException;
use Throwable;

/**
 * Ganti atlet oleh admin (F11-01, K172).
 *
 * Contoh kasusnya: atlet cedera menjelang lomba, dan sekolah mengirim atlet
 * lain. Penggantinya dicatat resmi, jadi tidak ada atlet tanpa pendaftaran di
 * lembar hasil (masalah M5 di telaah Seri 1).
 *
 * ## Aturan
 *
 * - Hanya untuk pendaftaran **Lunas**. Pendaftaran Menunggu cukup diubah lewat
 *   Ubah atau Tolak atlet.
 * - Atlet baru dari sekolah yang sama (pendaftaran yang sama), dengan
 *   **jenis kelamin sama** dan **kelompok umur sama** dengan atlet lama.
 *   Kelompok umur dihitung dari tahun lahir dan jenjang (K95, K134); atlet
 *   lama yang kelompok umurnya sudah dimatikan tetap boleh diganti (K98).
 * - NISN atlet baru tepat 10 angka dan belum terdaftar (K06).
 * - Atlet baru **mewarisi** nomor lomba, biaya, regu, dan peran inti atau
 *   cadangan. Total pendaftaran tidak berubah, jadi status tetap Lunas.
 * - Atlet baru mendapat **nomor atlet (BIB) baru**; nomor atlet lama tidak
 *   dipakai ulang (K11).
 * - Ditolak bila atlet lama sudah punya prestasi atau keterangan (DNS, DNF,
 *   DQ) di babak mana pun, atau masuk seri di babak yang **sudah dikunci**.
 *   Untuk nomor beregu, ditolak bila regunya sudah punya hasil.
 * - Baris hasil atlet lama yang masih kosong (seri dan lintasan babak yang
 *   belum dikunci) dipindah ke atlet baru, jadi tempatnya di seri tetap.
 * - Atlet lama dihapus (NISN-nya bisa didaftarkan lagi). Salinannya dan
 *   penggantinya dicatat di `atlet_diganti`, ditambah riwayat tindakan admin.
 *   Semua dalam satu transaksi dengan kunci per NPSN.
 *
 *     $ganti = new GantiAtlet();
 *     $form  = $ganti->formulir($id, $atletId);   // null bila tidak ada
 *     $hasil = $ganti->ganti($id, $atletId, ['nama' => '…', 'nisn' => '…', 'tanggal_lahir' => '2014-03-01'], 'Cedera lutut', 'Admin');
 *     // ['galat' => [], 'tersimpan' => true, 'nomor_dada' => '3101', 'nama' => 'DEWI LESTARI']
 */
final class GantiAtlet
{
    public const PANJANG_ALASAN_MIN = 3;

    public const PANJANG_ALASAN_MAKS = 500;

    /**
     * Lama menunggu kunci per NPSN, dalam detik (sama dengan `UbahPendaftaran`).
     */
    private const TUNGGU_KUNCI = 10;

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Pendaftaran, atlet yang akan diganti, nomor lombanya, dan alasan bila
     * penggantian tidak bisa dilakukan. `null` bila pendaftaran atau atletnya
     * tidak ada.
     *
     *     [
     *         'pendaftaran' => ['id', 'id_pendaftaran', 'nama_sekolah', 'npsn', 'jenjang', 'status'],
     *         'atlet' => ['id', 'nama', 'nisn', 'tanggal_lahir', 'jenis_kelamin', 'kelompok_umur_id', 'kelompok_umur', 'nomor_dada'],
     *         'nomor_lomba' => [['nomor_lomba_id' => 4, 'nama' => 'Lari 60 M putra', 'jenis' => 'individu', 'biaya' => 25000, 'regu' => null, 'peran' => null]],
     *         'hambatan' => [],   // kalimat untuk admin; kosong = boleh diganti
     *     ]
     *
     * @return array<string, mixed>|null
     */
    public function formulir(int $id, int $atletId): ?array
    {
        $p = $this->db->table('pendaftaran')
            ->select('id, id_pendaftaran, nama_sekolah, npsn, jenjang, status')
            ->where('id', $id)->get()->getRowArray();

        if (! is_array($p)) {
            return null;
        }

        $a = $this->db->table('atlet a')
            ->select('a.id, a.nama, a.nisn, a.tanggal_lahir, a.jenis_kelamin, a.kelompok_umur_id, a.nomor_dada, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('a.id', $atletId)->where('a.pendaftaran_id', $id)
            ->get()->getRowArray();

        if (! is_array($a)) {
            return null;
        }

        $nomor = [];

        foreach ($this->db->table('atlet_nomor an')
            ->select('an.nomor_lomba_id, an.biaya, an.regu, an.peran, nl.nama, nl.kategori, nl.jenis')
            ->join('nomor_lomba nl', 'nl.id = an.nomor_lomba_id')
            ->where('an.atlet_id', $atletId)
            ->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray() as $n) {
            $nomor[] = [
                'nomor_lomba_id' => (int) $n['nomor_lomba_id'],
                'nama'           => NomorLombaModel::namaLengkap($n),
                'jenis'          => (string) $n['jenis'],
                'biaya'          => $n['biaya'] === null ? null : (int) $n['biaya'],
                'regu'           => $n['regu'] ?? null,
                'peran'          => $n['peran'] ?? null,
            ];
        }

        $hambatan = [];

        if ($p['status'] !== 'lunas') {
            $hambatan[] = 'Ganti atlet hanya untuk pendaftaran Lunas. Untuk pendaftaran Menunggu, pakai Ubah atau Tolak atlet.';
        }

        $hambatan = [...$hambatan, ...$this->hambatanHasil($id, $atletId, $nomor)];

        return [
            'pendaftaran' => [
                'id'             => (int) $p['id'],
                'id_pendaftaran' => (string) $p['id_pendaftaran'],
                'nama_sekolah'   => (string) $p['nama_sekolah'],
                'npsn'           => $p['npsn'] === null || $p['npsn'] === '' ? null : (string) $p['npsn'],
                'jenjang'        => (string) $p['jenjang'],
                'status'         => (string) $p['status'],
            ],
            'atlet' => [
                'id'               => (int) $a['id'],
                'nama'             => (string) $a['nama'],
                'nisn'             => (string) $a['nisn'],
                'tanggal_lahir'    => (string) $a['tanggal_lahir'],
                'jenis_kelamin'    => (string) $a['jenis_kelamin'],
                'kelompok_umur_id' => (int) $a['kelompok_umur_id'],
                'kelompok_umur'    => (string) $a['kelompok_umur'],
                'nomor_dada'       => $a['nomor_dada'] === null ? null : (string) $a['nomor_dada'],
            ],
            'nomor_lomba' => $nomor,
            'hambatan'    => $hambatan,
        ];
    }

    /**
     * Periksa data atlet baru terhadap atlet lama. Aturan murni kecuali
     * pemeriksaan NISN terdaftar (`$nisnTerdaftar`).
     *
     * @param array<string, mixed>       $lama          `formulir()['atlet']`
     * @param array<string, mixed>       $baru          nama, nisn, tanggal_lahir
     * @param list<array<string, mixed>> $kelompokUmur  kelompok umur aktif (`DataIndukPendaftaran::induk()`) ditambah kelompok umur atlet lama bila sudah dimatikan
     * @param list<string>               $nisnTerdaftar NISN yang sudah ada di tabel atlet
     *
     * @return array{galat: array<string, string>, data: array{nama: string, nisn: string, tanggal_lahir: string}|null}
     */
    public static function periksa(array $lama, array $baru, string $jenjang, array $kelompokUmur, array $nisnTerdaftar, string $alasan, string $hariIni): array
    {
        $galat = [];
        $nama  = mb_strtoupper(MasukanPeriksa::rapikanSpasi(MasukanPeriksa::teks($baru['nama'] ?? '')));
        $nisn  = trim(MasukanPeriksa::teks($baru['nisn'] ?? ''));
        $lahir = MasukanPeriksa::tanggal(MasukanPeriksa::teks($baru['tanggal_lahir'] ?? ''));

        if ($nama === '') {
            $galat['nama'] = 'Isi nama atlet pengganti.';
        } elseif (mb_strlen($nama) > PemeriksaPendaftaran::NAMA_MAKSIMAL) {
            $galat['nama'] = 'Nama terlalu panjang. Persingkat menjadi paling banyak ' . PemeriksaPendaftaran::NAMA_MAKSIMAL . ' huruf.';
        }

        if (preg_match('/^\d{10}$/D', $nisn) !== 1) {
            $galat['nisn'] = 'NISN harus tepat 10 angka. Periksa lagi NISN-nya.';
        } elseif ($nisn === (string) $lama['nisn']) {
            $galat['nisn'] = 'NISN ini milik atlet yang diganti. Isi NISN atlet pengganti.';
        } elseif (in_array($nisn, $nisnTerdaftar, true)) {
            $galat['nisn'] = 'NISN ini sudah terdaftar sebagai atlet. Satu NISN hanya boleh terdaftar sekali.';
        }

        if ($lahir === null) {
            $galat['tanggal_lahir'] = 'Isi tanggal lahir dengan benar.';
        } elseif ($lahir->format('Y-m-d') > $hariIni) {
            $galat['tanggal_lahir'] = 'Tanggal lahir tidak boleh setelah hari ini.';
        } elseif ((int) $lahir->format('Y') < PemeriksaPendaftaran::TAHUN_LAHIR_TERKECIL) {
            $galat['tanggal_lahir'] = 'Tahun lahir terlalu jauh ke belakang. Periksa lagi tanggal lahirnya.';
        } else {
            $kuLama = (int) $lama['kelompok_umur_id'];
            $tentu  = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, $kelompokUmur, $kuLama);
            $ku     = $tentu['kelompok_umur'];

            if ($ku === null || (int) $ku['id'] !== $kuLama) {
                $galat['tanggal_lahir'] = 'Tahun lahir ini tidak masuk ' . $lama['kelompok_umur']
                    . '. Atlet pengganti harus dari kelompok umur yang sama dengan atlet yang diganti.';
            }
        }

        $alasan = trim($alasan);

        if (mb_strlen($alasan) < self::PANJANG_ALASAN_MIN) {
            $galat['alasan'] = 'Tulis alasan penggantian, misalnya "cedera" atau "sakit".';
        } elseif (mb_strlen($alasan) > self::PANJANG_ALASAN_MAKS) {
            $galat['alasan'] = 'Alasan terlalu panjang. Persingkat menjadi paling banyak ' . self::PANJANG_ALASAN_MAKS . ' huruf.';
        }

        return [
            'galat' => $galat,
            'data'  => $galat === [] && $lahir !== null ? ['nama' => $nama, 'nisn' => $nisn, 'tanggal_lahir' => $lahir->format('Y-m-d')] : null,
        ];
    }

    /**
     * Ganti atlet. `null` bila pendaftaran atau atletnya tidak ada.
     *
     * Jenis kelamin atlet baru mengikuti atlet lama.
     *
     * @param array<string, mixed> $baru nama, nisn, tanggal_lahir
     *
     * @return array{galat: array<string, string>, tersimpan: bool, nomor_dada: string|null, nama: string|null}|null
     */
    public function ganti(int $id, int $atletId, array $baru, string $alasan, string $admin, ?string $hariIni = null): ?array
    {
        $form = $this->formulir($id, $atletId);

        if ($form === null) {
            return null;
        }

        $hasil = ['galat' => [], 'tersimpan' => false, 'nomor_dada' => null, 'nama' => null];

        if ($form['hambatan'] !== []) {
            $hasil['galat']['umum'] = implode(' ', $form['hambatan']);

            return $hasil;
        }

        $induk        = new DataIndukPendaftaran();
        $kelompokUmur = $induk->induk()['kelompok_umur'];
        $kuLama       = (int) $form['atlet']['kelompok_umur_id'];

        if (! in_array($kuLama, array_map('intval', array_column($kelompokUmur, 'id')), true)) {
            // Kelompok umur yang sudah dimatikan tetap sah untuk atlet yang memakainya (K98).
            $kelompokUmur = [...$kelompokUmur, ...$induk->nonaktif([$kuLama], [])['kelompok_umur']];
        }

        $nisn    = trim(MasukanPeriksa::teks($baru['nisn'] ?? ''));
        $periksa = self::periksa(
            $form['atlet'],
            $baru,
            $form['pendaftaran']['jenjang'],
            $kelompokUmur,
            model(AtletModel::class)->nisnTerdaftar(preg_match('/^\d{10}$/D', $nisn) === 1 ? [$nisn] : []),
            $alasan,
            $hariIni ?? Time::today()->toDateString(),
        );

        if ($periksa['data'] === null) {
            $hasil['galat'] = $periksa['galat'];

            return $hasil;
        }

        $data  = $periksa['data'];
        $npsn  = $form['pendaftaran']['npsn'];
        $kunci = $npsn === null ? null : 'milklife-pendaftaran-' . $npsn;

        if ($kunci !== null) {
            $dapat = $this->db->query('SELECT GET_LOCK(?, ?) AS dapat', [$kunci, self::TUNGGU_KUNCI])->getRowArray();

            if ((int) ($dapat['dapat'] ?? 0) !== 1) {
                $hasil['galat']['umum'] = 'Pendaftaran lain dari sekolah ini sedang disimpan. Tunggu beberapa detik, lalu simpan lagi.';

                return $hasil;
            }
        }

        try {
            $this->db->resetTransStatus();
            $this->db->transBegin();

            $kini = $this->db->query('SELECT status FROM pendaftaran WHERE id = ? FOR UPDATE', [$id]);
            $kini = is_object($kini) ? $kini->getRowArray() : null;
            $lama = $this->db->query('SELECT id, nomor_dada FROM atlet WHERE id = ? AND pendaftaran_id = ? FOR UPDATE', [$atletId, $id]);
            $lama = is_object($lama) ? $lama->getRowArray() : null;

            if (! is_array($kini) || $kini['status'] !== 'lunas' || ! is_array($lama)) {
                $this->db->transRollback();
                $hasil['galat']['umum'] = 'Pendaftaran ini baru saja diubah di tempat lain. Muat ulang halaman, lalu coba lagi.';

                return $hasil;
            }

            // Periksa ulang di dalam kunci: hasil bisa saja baru diisi juri.
            $hambatan = $this->hambatanHasil($id, $atletId, $form['nomor_lomba']);

            if ($hambatan !== []) {
                $this->db->transRollback();
                $hasil['galat']['umum'] = implode(' ', $hambatan);

                return $hasil;
            }

            $sekarang = Time::now()->toDateTimeString();
            $a        = $form['atlet'];

            $this->pastikan($this->db->table('atlet')->insert([
                'pendaftaran_id'   => $id,
                'nama'             => $data['nama'],
                'nisn'             => $data['nisn'],
                'tanggal_lahir'    => $data['tanggal_lahir'],
                'jenis_kelamin'    => $a['jenis_kelamin'],
                'kelompok_umur_id' => $a['kelompok_umur_id'],
                'dibuat_pada'      => $sekarang,
                'diubah_pada'      => $sekarang,
            ]));
            $baruId = (int) $this->db->insertID();

            foreach ($form['nomor_lomba'] as $n) {
                $this->pastikan($this->db->table('atlet_nomor')->insert([
                    'atlet_id'       => $baruId,
                    'nomor_lomba_id' => $n['nomor_lomba_id'],
                    'biaya'          => $n['biaya'],
                    'regu'           => $n['regu'],
                    'peran'          => $n['peran'],
                ]));
            }

            // Tempat atlet lama di seri dan lintasan babak yang belum dikunci pindah ke atlet baru.
            $this->pastikan($this->db->table('hasil')->where('atlet_id', $atletId)->update(['atlet_id' => $baruId]));

            // Nomor atlet baru dibagi sebelum atlet lama dihapus, supaya nomor
            // atlet lama tetap terhitung dan tidak dipakai ulang (K11).
            $bib = (new StatusPembayaran($this->db))->beriNomorDada($id);

            if ($bib['penuh'] !== []) {
                $this->db->transRollback();
                $hasil['galat']['umum'] = 'Atlet belum diganti karena awalan nomor atlet (BIB) ' . $a['kelompok_umur']
                    . ' sudah penuh. Tambahkan awalan baru di Pengaturan > Kelompok umur, lalu ganti lagi.';

                return $hasil;
            }

            $nomorBaru = $bib['nomor'][$baruId] ?? null;

            $this->pastikan($this->db->table('atlet')->where('id', $atletId)->delete());

            $this->pastikan($this->db->table('atlet_diganti')->insert([
                'pendaftaran_id'       => $id,
                'nama'                 => $a['nama'],
                'nisn'                 => $a['nisn'],
                'tanggal_lahir'        => $a['tanggal_lahir'],
                'jenis_kelamin'        => $a['jenis_kelamin'],
                'kelompok_umur'        => $a['kelompok_umur'],
                'nomor_dada'           => $lama['nomor_dada'],
                'nomor_lomba'          => json_encode(array_map(
                    static fn (array $n): array => array_diff_key($n, ['nomor_lomba_id' => true]),
                    $form['nomor_lomba'],
                ), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'pengganti_nama'       => $data['nama'],
                'pengganti_nisn'       => $data['nisn'],
                'pengganti_nomor_dada' => $nomorBaru,
                'alasan'               => trim($alasan),
                'diganti_oleh'         => mb_substr($admin, 0, 100),
                'dibuat_pada'          => $sekarang,
            ]));

            $this->pastikan($this->db->table('pendaftaran')->where('id', $id)->update(['diubah_pada' => $sekarang]));

            (new RiwayatAdmin($this->db))->catat($id, $form['pendaftaran']['id_pendaftaran'], RiwayatAdmin::GANTI_ATLET,
                RiwayatAdmin::namaAtlet($a) . ($lama['nomor_dada'] === null ? '' : ', nomor atlet ' . $lama['nomor_dada'])
                . ' diganti ' . RiwayatAdmin::namaAtlet($data) . ($nomorBaru === null ? '' : ', nomor atlet ' . $nomorBaru)
                . '. Alasan: ' . rtrim(trim($alasan), '. ') . '.');

            if ($this->db->transStatus() === false) {
                throw new RuntimeException('Transaksi ganti atlet gagal.');
            }

            $this->db->transCommit();
        } catch (Throwable $e) {
            while ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }

            throw $e;
        } finally {
            if ($kunci !== null) {
                $this->db->query('SELECT RELEASE_LOCK(?)', [$kunci]);
            }
        }

        $hasil['tersimpan']  = true;
        $hasil['nomor_dada'] = $nomorBaru;
        $hasil['nama']       = $data['nama'];

        return $hasil;
    }

    /**
     * Catatan atlet yang diganti per pendaftaran, terbaru di akhir.
     *
     * @return list<array<string, mixed>>
     */
    public function riwayat(int $id): array
    {
        return array_map(static function (array $b): array {
            $b['nomor_lomba'] = json_decode((string) $b['nomor_lomba'], true) ?: [];

            return $b;
        }, $this->db->table('atlet_diganti')->where('pendaftaran_id', $id)->orderBy('id')->get()->getResultArray());
    }

    /**
     * Alasan hasil lomba yang membuat atlet tidak bisa diganti.
     *
     * @param list<array<string, mixed>> $nomor
     *
     * @return list<string>
     */
    private function hambatanHasil(int $id, int $atletId, array $nomor): array
    {
        $hambatan = [];

        $individu = $this->db->table('hasil h')
            ->select('nl.nama, nl.kategori, nl.jenis, b.nama AS babak, b.seri_dikunci_pada, h.prestasi, h.keterangan')
            ->join('babak b', 'b.id = h.babak_id')
            ->join('nomor_lomba nl', 'nl.id = b.nomor_lomba_id')
            ->where('h.atlet_id', $atletId)
            ->get()->getResultArray();

        foreach ($individu as $h) {
            $nama = NomorLombaModel::namaLengkap($h) . ' (' . $h['babak'] . ')';

            if ($h['prestasi'] !== null || ($h['keterangan'] ?? '') !== '') {
                $hambatan[] = 'Atlet ini sudah punya hasil di ' . $nama . ', jadi tidak bisa diganti.';
            } elseif ($h['seri_dikunci_pada'] !== null) {
                $hambatan[] = 'Seri ' . $nama . ' sudah dikunci. Buka kunci seri babak itu di menu Seri dulu, lalu ganti atlet.';
            }
        }

        foreach ($nomor as $n) {
            if ($n['jenis'] !== 'beregu' || $n['regu'] === null) {
                continue;
            }

            $ada = $this->db->table('hasil h')
                ->join('babak b', 'b.id = h.babak_id')
                ->where('h.pendaftaran_id', $id)->where('h.regu', $n['regu'])
                ->where('b.nomor_lomba_id', $n['nomor_lomba_id'])
                ->groupStart()->where('h.prestasi IS NOT NULL', null, false)->orWhere("COALESCE(h.keterangan, '') <> ''", null, false)->groupEnd()
                ->countAllResults();

            if ($ada > 0) {
                $hambatan[] = 'Regu ' . $n['nama'] . ' sudah punya hasil, jadi anggotanya tidak bisa diganti.';
            }
        }

        return array_values(array_unique($hambatan));
    }

    private function pastikan(mixed $hasil): void
    {
        if ($hasil === false) {
            $galat = $this->db->error();

            throw new DatabaseException('Ganti atlet gagal: ' . (string) ($galat['message'] ?? 'tanpa keterangan') . '.', (int) ($galat['code'] ?? 0));
        }
    }
}
