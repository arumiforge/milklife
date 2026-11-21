<?php

namespace App\Libraries\Hasil;

use App\Libraries\Pendaftaran\Regu;
use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;
use Throwable;

/**
 * Hasil lomba per nomor dan babak (F8-04, K124, asumsi 6–8 K125).
 *
 * - Babak diatur admin per nomor lomba; satu Final per nomor. Juara I–III
 *   diambil dari Final (dipakai klasemen).
 * - Peserta babak dipilih dari atlet pendaftaran Lunas di nomor itu (nomor
 *   individu) atau regu pendaftaran Lunas (nomor beregu). Babak baru bisa
 *   diisi semua peserta, kosong, atau peringkat teratas babak lain (yang lolos).
 * - Prestasi diurai `Prestasi`, peringkat dihitung `Peringkat`. Di Final admin
 *   boleh mengoreksi peringkat; baris itu bertanda `peringkat_manual`.
 * - Nomor berjenis hasil `multi` (F9-10): admin mengisi prestasi tiap
 *   komponen, poinnya dicari di tabel skor nomor itu (`TabelSkor`), total
 *   poin disimpan di `prestasi` dan rinciannya di `rincian`.
 * - Nomor lompat/lempar (F9-09): prestasi tiap percobaan (`Percobaan`),
 *   prestasi = jarak sah terbaik, prestasi sama dipisah jarak terbaik kedua
 *   bila `pemecah_seri` menyala. Lari: waktu seperseribu detik bila diisi
 *   memisah waktu yang sama (`Peringkat::pemecah()`).
 * - Penyimpanan (halaman hasil dan lembar juri) mengunci baris babaknya
 *   (`SELECT … FOR UPDATE`), jadi beberapa admin bisa mengisi babak berbeda
 *   bersamaan; baris yang sudah diubah admin lain sejak dibuka (`versi`)
 *   ditolak dengan `PESAN_BENTROK`.
 * - Terbitkan dan Tarik kembali per nomor (`nomor_lomba.hasil_terbit_pada`);
 *   halaman publik hanya memuat nomor yang terbit.
 *
 * Kunci peserta: `a:<id atlet>` untuk nomor individu, `r:<id pendaftaran>:<regu>`
 * untuk nomor beregu.
 *
 *     $hasil = new HasilLomba();
 *     $hasil->daftarNomor();                       // per kelompok umur, semua nomor
 *     $nomor = $hasil->nomor(10);                  // ['id' => 10, 'nama' => 'Lari 60 m putra', 'jenis_hasil' => 'waktu', …]
 *     $babak = $hasil->tambahBabak(10, 'Final', true, 'semua');   // ['id' => 3] atau ['galat' => […]]
 *     $hasil->simpanHasil(3, [55 => ['prestasi' => '9,87', 'keterangan' => '', 'peringkat' => '']]);
 *     $hasil->terbitkan(10, true);
 */
final class HasilLomba
{
    public const NAMA_BABAK_MAKSIMAL = 60;

    /**
     * Pesan bila baris yang disimpan sudah diubah admin lain sejak halamannya dibuka (F9-09).
     */
    public const PESAN_BENTROK = 'Baris ini baru saja diubah admin lain. Muat ulang halaman untuk melihat isian terbaru, lalu ubah lagi bila perlu.';

    /**
     * Isi peserta babak baru.
     *
     * @var array<string, string>
     */
    public const ISI_BABAK = [
        'semua'  => 'Semua peserta nomor ini',
        'lolos'  => 'Peringkat teratas dari babak lain',
        'kosong' => 'Kosong, pilih peserta nanti',
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Kelompok umur berurutan beserta nomor lombanya. Kelompok umur tanpa nomor dilewati.
     *
     * @return list<array{id: int, nama: string, nomor: list<array{id: int, nama: string, jenis: string, jenis_hasil: string, terbit_pada: string|null, babak: int, final: bool, peserta: int}>}>
     */
    public function daftarNomor(bool $hanyaTerbit = false): array
    {
        $babak = [];

        foreach ($this->db->table('babak')->select('nomor_lomba_id, COUNT(*) AS jumlah, MAX(final) AS final')->groupBy('nomor_lomba_id')->get()->getResultArray() as $b) {
            $babak[(int) $b['nomor_lomba_id']] = ['jumlah' => (int) $b['jumlah'], 'final' => (int) $b['final'] === 1];
        }

        $peserta = array_column($this->db->table('babak b')
            ->select('b.nomor_lomba_id, COUNT(h.id) AS jumlah')
            ->join('hasil h', 'h.babak_id = b.id')
            ->where('b.final', 1)
            ->groupBy('b.nomor_lomba_id')
            ->get()->getResultArray(), 'jumlah', 'nomor_lomba_id');

        $kueri = $this->db->table('nomor_lomba')->select('id, kelompok_umur_id, nama, kategori, jenis, jenis_hasil, hasil_terbit_pada');

        if ($hanyaTerbit) {
            $kueri->where('hasil_terbit_pada IS NOT NULL', null, false);
        }

        $nomor = [];

        foreach ($kueri->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $n) {
            $id = (int) $n['id'];

            $nomor[(int) $n['kelompok_umur_id']][] = [
                'id'          => $id,
                'nama'        => NomorLombaModel::namaLengkap($n),
                'jenis'       => (string) $n['jenis'],
                'jenis_hasil' => (string) $n['jenis_hasil'],
                'terbit_pada' => $n['hasil_terbit_pada'],
                'babak'       => $babak[$id]['jumlah'] ?? 0,
                'final'       => $babak[$id]['final'] ?? false,
                'peserta'     => (int) ($peserta[$id] ?? 0),
            ];
        }

        $hasil = [];

        foreach ($this->db->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $ku) {
            if (isset($nomor[(int) $ku['id']])) {
                $hasil[] = ['id' => (int) $ku['id'], 'nama' => (string) $ku['nama'], 'nomor' => $nomor[(int) $ku['id']]];
            }
        }

        return $hasil;
    }

    /**
     * Ada nomor yang hasilnya sudah terbit? (tautan Hasil di halaman publik)
     */
    public function adaTerbit(): bool
    {
        return $this->db->table('nomor_lomba')->where('hasil_terbit_pada IS NOT NULL', null, false)->countAllResults() > 0;
    }

    /**
     * Satu nomor lomba, atau `null` bila tidak ada.
     *
     * `komponen` hanya terisi untuk jenis hasil `multi`: komponen tabel skor
     * yang dipakai nomor itu.
     *
     * `jumlah_percobaan` dan `pemecah_seri` (F9-09): aturan nomor lompat/lempar
     * (berlaku untuk jenis hasil jarak).
     *
     * @return array{id: int, nama: string, jenis: string, kategori: string, jenis_hasil: string, terbit_pada: string|null, kelompok_umur_id: int, kelompok_umur: string, tabel_skor_id: int|null, komponen: list<array{kode: string, nama: string, jenis: string}>, jumlah_percobaan: int, pemecah_seri: bool}|null
     */
    public function nomor(int $id): ?array
    {
        $n = $this->db->table('nomor_lomba nl')
            ->select('nl.id, nl.nama, nl.kategori, nl.jenis, nl.jenis_hasil, nl.tabel_skor_id, nl.komponen_skor, nl.hasil_terbit_pada, nl.kelompok_umur_id, nl.jumlah_percobaan, nl.pemecah_seri, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->where('nl.id', $id)
            ->get()->getRowArray();

        if (! is_array($n)) {
            return null;
        }

        $jenisHasil = JenisHasil::sah($n['jenis_hasil']) ? (string) $n['jenis_hasil'] : JenisHasil::WAKTU;
        $tabelSkor  = $n['tabel_skor_id'] === null ? null : (int) $n['tabel_skor_id'];

        return [
            'id'               => (int) $n['id'],
            'nama'             => NomorLombaModel::namaLengkap($n),
            'jenis'            => (string) $n['jenis'],
            'kategori'         => (string) $n['kategori'],
            'jenis_hasil'      => $jenisHasil,
            'terbit_pada'      => $n['hasil_terbit_pada'],
            'kelompok_umur_id' => (int) $n['kelompok_umur_id'],
            'kelompok_umur'    => (string) $n['kelompok_umur'],
            'tabel_skor_id'    => $tabelSkor,
            'komponen'         => $jenisHasil === JenisHasil::MULTI ? (new DataTabelSkor($this->db))->komponenNomor($tabelSkor, $n['komponen_skor']) : [],
            // Aturan nomor (F9-09).
            'jumlah_percobaan' => Percobaan::jumlah($n['jumlah_percobaan']),
            'pemecah_seri'     => (int) ($n['pemecah_seri'] ?? 1) === 1,
        ];
    }

    /**
     * Satu babak beserta nomornya, atau `null`.
     *
     * @return array{id: int, nama: string, final: bool, urutan: int, nomor: array<string, mixed>}|null
     */
    public function babak(int $id): ?array
    {
        $b = $this->db->table('babak')->where('id', $id)->get()->getRowArray();

        if (! is_array($b)) {
            return null;
        }

        $nomor = $this->nomor((int) $b['nomor_lomba_id']);

        return $nomor === null ? null : ['id' => (int) $b['id'], 'nama' => (string) $b['nama'], 'final' => (int) $b['final'] === 1, 'urutan' => (int) $b['urutan'], 'nomor' => $nomor];
    }

    /**
     * Semua babak satu nomor (urutan, Final paling akhir) beserta hasilnya.
     *
     * @param array<string, mixed> $nomor hasil `nomor()`
     *
     * @return list<array{id: int, nama: string, final: bool, urutan: int, hasil: list<array<string, mixed>>}>
     */
    public function semuaBabak(array $nomor): array
    {
        $daftar = [];

        foreach ($this->db->table('babak')->where('nomor_lomba_id', $nomor['id'])->orderBy('final')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $b) {
            $daftar[] = ['id' => (int) $b['id'], 'nama' => (string) $b['nama'], 'final' => (int) $b['final'] === 1, 'urutan' => (int) $b['urutan'], 'hasil' => $this->hasilBabak((int) $b['id'], $nomor)];
        }

        return $daftar;
    }

    /**
     * Hasil satu babak, urut peringkat (tanpa peringkat di akhir, lalu nomor atlet).
     *
     * @param array<string, mixed> $nomor hasil `nomor()`
     *
     * `rincian`: prestasi dan poin per komponen untuk nomor `multi` (kosong
     * untuk jenis hasil lain); `rincian_isian` kode komponen => teks prestasi.
     *
     * `seri` dan `lintasan` (F9-08): seri dan lintasan atau urutan tampil;
     * `null` bila babak belum dibagi.
     *
     * F9-09: `percobaan` prestasi tiap percobaan (nomor jarak; `null` bila
     * tidak dicatat), `percobaan_teks` teksnya sepanjang jumlah percobaan
     * nomor, `seperseribu` waktu dalam seperseribu detik, `prestasi_isian`
     * teks untuk kotak isian (dengan seperseribu bila ada), `pemecah` untuk
     * `Peringkat::hitung()`, `versi` penanda perubahan baris.
     *
     * @return list<array{id: int, kunci: string, nama: string, sekolah: string, npsn: string|null, jenjang: string, pendaftaran_id: int, nomor_dada: string|null, anggota: list<string>, prestasi: int|null, prestasi_teks: string, rincian: list<array{nama: string, prestasi: string, poin: int, luar: string|null}>, rincian_isian: array<string, string>, keterangan: string|null, peringkat: int|null, manual: bool, seri: int|null, lintasan: int|null, percobaan: list<int|string|null>|null, percobaan_teks: list<string>, seperseribu: int|null, prestasi_isian: string, pemecah: list<int>|null, versi: int}>
     */
    public function hasilBabak(int $babakId, array $nomor): array
    {
        $baris = $nomor['jenis'] === 'beregu'
            ? $this->db->table('hasil h')
                ->select('h.*, p.nama_sekolah, p.npsn, p.jenjang, NULL AS nomor_dada, NULL AS nama_atlet', false)
                ->join('pendaftaran p', 'p.id = h.pendaftaran_id')
                ->where('h.babak_id', $babakId)
                ->get()->getResultArray()
            : $this->db->table('hasil h')
                ->select('h.*, a.nama AS nama_atlet, a.nomor_dada, a.pendaftaran_id AS pendaftaran_atlet, p.nama_sekolah, p.npsn, p.jenjang')
                ->join('atlet a', 'a.id = h.atlet_id')
                ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
                ->where('h.babak_id', $babakId)
                ->get()->getResultArray();

        $reguSekolah = $nomor['jenis'] === 'beregu' ? $this->jumlahReguSekolah((int) $nomor['id']) : [];
        $anggota     = $nomor['jenis'] === 'beregu' ? $this->anggotaRegu((int) $nomor['id']) : [];
        $hasil       = [];
        $komponen    = $nomor['jenis_hasil'] === JenisHasil::MULTI ? (array) ($nomor['komponen'] ?? []) : [];

        $jenisHasil = (string) $nomor['jenis_hasil'];
        $jarak      = $jenisHasil === JenisHasil::JARAK;
        $jumlahP    = (int) ($nomor['jumlah_percobaan'] ?? Percobaan::BAWAAN);

        foreach ($baris as $b) {
            $prestasi  = $b['prestasi'] === null ? null : (int) $b['prestasi'];
            $percobaan = $jarak ? Percobaan::baca($b['percobaan'] ?? null) : null;
            $ribu      = $jenisHasil === JenisHasil::WAKTU && isset($b['seperseribu']) ? (int) $b['seperseribu'] : null;
            $teks      = Prestasi::tulis($prestasi, $jenisHasil);
            $rincian  = $komponen === [] ? [] : TabelSkor::bacaRincian($b['rincian'] ?? null);
            $isianK   = [];

            foreach ($komponen as $k) {
                $isianK[$k['kode']] = Prestasi::tulis($rincian[$k['kode']]['prestasi'] ?? null, $k['jenis']);
            }

            if ($nomor['jenis'] === 'beregu') {
                $pid   = (int) $b['pendaftaran_id'];
                $regu  = (string) $b['regu'];
                $kunci = 'r:' . $pid . ':' . $regu;
                $nama  = self::namaRegu((string) $b['nama_sekolah'], $regu, ($reguSekolah[self::kunciSekolah($b['npsn'], $pid)] ?? 1) <= 1);
            } else {
                $pid   = (int) $b['pendaftaran_atlet'];
                $kunci = 'a:' . (int) $b['atlet_id'];
                $nama  = (string) $b['nama_atlet'];
            }

            $hasil[] = [
                'id'             => (int) $b['id'],
                'kunci'          => $kunci,
                'nama'           => $nama,
                'sekolah'        => (string) $b['nama_sekolah'],
                'npsn'           => $b['npsn'] === null || $b['npsn'] === '' ? null : (string) $b['npsn'],
                'jenjang'        => (string) $b['jenjang'],
                'pendaftaran_id' => $pid,
                'nomor_dada'     => $b['nomor_dada'],
                'anggota'        => $anggota[$kunci] ?? [],
                'prestasi'       => $prestasi,
                'prestasi_teks'  => $teks,
                'rincian'        => $komponen === [] || $rincian === [] ? [] : TabelSkor::rincianTampil($komponen, $rincian),
                'rincian_isian'  => $isianK,
                'keterangan'     => Peringkat::keterangan($b['keterangan']),
                'peringkat'      => $b['peringkat'] === null ? null : (int) $b['peringkat'],
                'manual'         => (int) $b['peringkat_manual'] === 1,
                // Seri dan lintasan (F9-08).
                'seri'           => isset($b['seri']) ? (int) $b['seri'] : null,
                'lintasan'       => isset($b['lintasan']) ? (int) $b['lintasan'] : null,
                // Aturan nomor dan lembar juri (F9-09).
                'percobaan'      => $percobaan,
                'percobaan_teks' => $jarak ? Percobaan::teks($percobaan, max($jumlahP, count($percobaan ?? []))) : [],
                'seperseribu'    => $ribu,
                'prestasi_isian' => $ribu !== null ? Prestasi::tulisSeperseribu($ribu) : $teks,
                'pemecah'        => Peringkat::pemecah($jenisHasil, $percobaan, $ribu, (bool) ($nomor['pemecah_seri'] ?? true)),
                'versi'          => (int) ($b['versi'] ?? 0),
            ];
        }

        usort($hasil, static fn (array $x, array $y): int => [$x['peringkat'] ?? PHP_INT_MAX, $x['keterangan'] ?? '', (string) $x['nomor_dada'], $x['nama']]
            <=> [$y['peringkat'] ?? PHP_INT_MAX, $y['keterangan'] ?? '', (string) $y['nomor_dada'], $y['nama']]);

        return $hasil;
    }

    /**
     * Calon peserta satu nomor: atlet Lunas (individu) atau regu Lunas (beregu),
     * urut nomor atlet atau nama sekolah.
     *
     * @param array<string, mixed> $nomor hasil `nomor()`
     *
     * @return array<string, array{kunci: string, nama: string, sekolah: string, nomor_dada: string|null}> kunci => calon
     */
    public function calon(array $nomor): array
    {
        $calon = [];

        if ($nomor['jenis'] !== 'beregu') {
            $baris = $this->db->table('atlet_nomor an')
                ->select('a.id, a.nama, a.nomor_dada, p.nama_sekolah')
                ->join('atlet a', 'a.id = an.atlet_id')
                ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
                ->where('an.nomor_lomba_id', $nomor['id'])
                ->where('p.status', 'lunas')
                ->orderBy('a.nomor_dada IS NULL', '', false)->orderBy('a.nomor_dada')->orderBy('a.nama')
                ->get()->getResultArray();

            foreach ($baris as $b) {
                $calon['a:' . $b['id']] = ['kunci' => 'a:' . $b['id'], 'nama' => (string) $b['nama'], 'sekolah' => (string) $b['nama_sekolah'], 'nomor_dada' => $b['nomor_dada']];
            }

            return $calon;
        }

        $reguSekolah = $this->jumlahReguSekolah((int) $nomor['id']);

        foreach ($this->reguLunas((int) $nomor['id']) as $r) {
            $kunci         = 'r:' . $r['pendaftaran_id'] . ':' . $r['regu'];
            $calon[$kunci] = [
                'kunci'      => $kunci,
                'nama'       => self::namaRegu((string) $r['nama_sekolah'], (string) $r['regu'], ($reguSekolah[self::kunciSekolah($r['npsn'], (int) $r['pendaftaran_id'])] ?? 1) <= 1),
                'sekolah'    => (string) $r['nama_sekolah'],
                'nomor_dada' => null,
            ];
        }

        return $calon;
    }

    /**
     * Tambah babak. `$isi`: `semua`, `kosong`, atau `lolos` (peringkat 1 sampai
     * `$jumlahLolos` dari `$dariBabak`, termasuk peringkat sama).
     *
     * @return array{id?: int, galat?: array<string, string>}
     */
    public function tambahBabak(int $nomorId, string $nama, bool $final, string $isi, ?int $dariBabak = null, ?int $jumlahLolos = null): array
    {
        $nomor = $this->nomor($nomorId);

        if ($nomor === null) {
            return ['galat' => ['nama' => 'Nomor lomba tidak ditemukan. Muat ulang halaman.']];
        }

        $nama  = self::rapikanNama($nama);
        $galat = $this->periksaBabak($nomorId, $nama, $final, null);

        if (! isset(self::ISI_BABAK[$isi])) {
            $galat['isi'] = 'Pilih cara mengisi peserta babak.';
        }

        $lolos = [];

        if ($isi === 'lolos') {
            $sumber = $dariBabak === null ? null : $this->babak($dariBabak);

            if ($sumber === null || $sumber['nomor']['id'] !== $nomorId) {
                $galat['dari_babak'] = 'Pilih babak asal peserta yang lolos.';
            } elseif ($jumlahLolos === null || $jumlahLolos < 1 || $jumlahLolos > 999) {
                $galat['jumlah_lolos'] = 'Tulis berapa peringkat teratas yang lolos, misalnya 8.';
            } else {
                foreach ($this->hasilBabak($sumber['id'], $nomor) as $h) {
                    if ($h['peringkat'] !== null && $h['peringkat'] <= $jumlahLolos) {
                        $lolos[] = $h['kunci'];
                    }
                }

                if ($lolos === []) {
                    $galat['jumlah_lolos'] = 'Babak ' . $sumber['nama'] . ' belum punya peringkat. Isi prestasinya dulu, atau pilih isi peserta yang lain.';
                }
            }
        }

        if ($galat !== []) {
            return ['galat' => $galat];
        }

        $sekarang = date('Y-m-d H:i:s');
        $urutan   = (int) ($this->db->table('babak')->selectMax('urutan', 'terbesar')->where('nomor_lomba_id', $nomorId)->get()->getRow()->terbesar ?? 0) + 1;

        $id = $this->transaksi(function () use ($nomorId, $nama, $final, $urutan, $sekarang, $nomor, $isi, $lolos): int {
            $this->db->table('babak')->insert(['nomor_lomba_id' => $nomorId, 'nama' => $nama, 'final' => $final ? 1 : 0, 'urutan' => $urutan, 'dibuat_pada' => $sekarang, 'diubah_pada' => $sekarang]);
            $id = (int) $this->db->insertID();
            $this->tulisPeserta($id, $nomor, $isi === 'semua' ? array_keys($this->calon($nomor)) : $lolos, $sekarang);

            return $id;
        });

        return ['id' => $id];
    }

    /**
     * Ganti nama atau tanda Final satu babak.
     *
     * @return array<string, string> galat; kosong bila tersimpan
     */
    public function ubahBabak(int $babakId, string $nama, bool $final): array
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return ['nama' => 'Babak tidak ditemukan. Muat ulang halaman.'];
        }

        $nama  = self::rapikanNama($nama);
        $galat = $this->periksaBabak($babak['nomor']['id'], $nama, $final, $babakId);

        if ($galat !== []) {
            return $galat;
        }

        $this->transaksi(function () use ($babakId, $nama, $final, $babak): void {
            $this->db->table('babak')->where('id', $babakId)->update(['nama' => $nama, 'final' => $final ? 1 : 0, 'diubah_pada' => date('Y-m-d H:i:s')]);

            // Koreksi peringkat hanya untuk Final: babak yang bukan Final lagi dihitung ulang.
            if (! $final && $babak['final']) {
                $this->db->table('hasil')->where('babak_id', $babakId)->update(['peringkat_manual' => 0]);
                $this->hitungUlang($babakId, $babak['nomor']['jenis_hasil']);
            }
        });

        return [];
    }

    public function hapusBabak(int $babakId): bool
    {
        return $this->db->table('babak')->where('id', $babakId)->delete() !== false && $this->db->affectedRows() > 0;
    }

    /**
     * Samakan peserta babak dengan daftar kunci yang dicentang: yang baru
     * ditambah, yang tidak dicentang dihapus beserta prestasinya. Kunci yang
     * bukan calon nomor itu diabaikan.
     *
     * @param list<string> $kunci
     *
     * @return array{tambah: int, hapus: int}
     */
    public function aturPeserta(int $babakId, array $kunci): array
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return ['tambah' => 0, 'hapus' => 0];
        }

        $calon = $this->calon($babak['nomor']);
        $ada   = [];

        foreach ($this->hasilBabak($babakId, $babak['nomor']) as $h) {
            $ada[$h['kunci']] = $h['id'];
        }

        $dipilih = array_values(array_filter(array_unique($kunci), static fn (string $k): bool => isset($calon[$k]) || isset($ada[$k])));
        $baru    = array_values(array_diff($dipilih, array_keys($ada)));
        $buang   = array_values(array_diff_key($ada, array_flip($dipilih)));

        $this->transaksi(function () use ($buang, $babakId, $babak, $baru): void {
            if ($buang !== []) {
                $this->db->table('hasil')->whereIn('id', $buang)->delete();
            }

            $this->tulisPeserta($babakId, $babak['nomor'], $baru, date('Y-m-d H:i:s'));
            $this->hitungUlang($babakId, $babak['nomor']['jenis_hasil']);
        });

        return ['tambah' => count($baru), 'hapus' => count($buang)];
    }

    /**
     * Simpan prestasi, keterangan, dan (khusus Final) koreksi peringkat, lalu
     * hitung ulang peringkat. Bila ada isian salah tidak ada yang tersimpan.
     *
     * Kunci tiap baris (semua boleh tidak ada):
     * - `prestasi`: teks prestasi. Waktu boleh memuat seperseribu detik
     *   (`12,345`, F9-09).
     * - `percobaan`: daftar teks tiap percobaan (nomor jarak, F9-09); bila ada,
     *   prestasi = jarak sah terbaik dan `prestasi` diabaikan. Tanpa
     *   `percobaan`, percobaan tersimpan dipertahankan selama prestasinya
     *   tidak diubah.
     * - `komponen`: kode komponen => teks prestasi (poin multi-lomba, F9-10).
     * - `keterangan`: DNS, DNF, DQ, atau kosong.
     * - `peringkat`: koreksi peringkat (khusus Final); bila kunci ini tidak
     *   ada (lembar juri), koreksi yang tersimpan dipertahankan.
     * - `versi`: `hasilBabak()['versi']` saat isian dibuka; bila baris sudah
     *   diubah sejak itu, simpanan ditolak dengan `PESAN_BENTROK`.
     *
     * Baris yang isinya sama dengan yang tersimpan dilewati. Penyimpanan
     * mengunci baris babak (`SELECT … FOR UPDATE`), jadi admin lain di babak
     * yang sama menunggu sebentar; babak lain tidak terpengaruh.
     *
     * @param array<int|string, mixed> $isian id hasil => isian baris
     *
     * @return array<int, string> id hasil => pesan galat; kosong bila tersimpan
     */
    public function simpanHasil(int $babakId, array $isian): array
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return [0 => 'Babak tidak ditemukan. Muat ulang halaman.'];
        }

        $nomor = $babak['nomor'];
        $jenis = $nomor['jenis_hasil'];
        $ada   = array_column($this->db->table('hasil')->select('id')->where('babak_id', $babakId)->get()->getResultArray(), 'id', 'id');
        $galat = [];
        $siap  = [];
        $multi = $jenis === JenisHasil::MULTI;

        if ($multi && $nomor['komponen'] === []) {
            return [0 => 'Nomor ini belum punya tabel skor. Pilih tabel skor dan komponennya di Pengaturan > Nomor Lomba.'];
        }

        $barisSkor = $multi ? (new DataTabelSkor($this->db))->barisKomponen((int) $nomor['tabel_skor_id'], $nomor['komponen'], $nomor['kategori']) : [];

        foreach ($isian as $id => $baris) {
            $id = (int) $id;

            if (! isset($ada[$id]) || ! is_array($baris)) {
                continue;
            }

            $urai = $this->uraiBaris($nomor, $barisSkor, $baris);

            if (isset($urai['galat'])) {
                $galat[$id] = $urai['galat'];

                continue;
            }

            $peringkat = is_string($baris['peringkat'] ?? null) ? trim($baris['peringkat']) : '';
            $manual    = $babak['final'] && $peringkat !== '' && $urai['data']['keterangan'] === null;

            if ($manual && preg_match('/^[1-9]\d{0,2}$/D', $peringkat) !== 1) {
                $galat[$id] = 'Peringkat "' . $peringkat . '" belum benar. Tulis angka 1 atau lebih, atau kosongkan supaya dihitung otomatis dari prestasi.';

                continue;
            }

            $siap[$id] = [
                'data'      => $urai['data'] + ['peringkat' => $manual ? (int) $peringkat : null, 'peringkat_manual' => $manual ? 1 : 0],
                'percobaan' => $urai['percobaan'],
                'koreksi'   => array_key_exists('peringkat', $baris),
                'versi'     => is_numeric($baris['versi'] ?? null) ? (int) $baris['versi'] : null,
            ];
        }

        if ($galat !== []) {
            return $galat;
        }

        $sekarang = date('Y-m-d H:i:s');

        $galat = $this->transaksi(function () use ($siap, $sekarang, $babakId, $jenis): array {
            $this->kunciBabak($babakId);

            $kini    = [];
            $ubah    = [];
            $bentrok = [];

            foreach ($this->db->table('hasil')->select('id, prestasi, rincian, percobaan, seperseribu, keterangan, peringkat, peringkat_manual, versi')->where('babak_id', $babakId)->get()->getResultArray() as $h) {
                $kini[(int) $h['id']] = $h;
            }

            foreach ($siap as $id => $s) {
                if (! isset($kini[$id])) {
                    continue;
                }

                $lama = $kini[$id];
                $data = $s['data'];

                // Lembar juri tidak mengirim koreksi peringkat: koreksi yang ada tetap.
                if (! $s['koreksi']) {
                    $tetap                    = (int) $lama['peringkat_manual'] === 1 && $data['keterangan'] === null;
                    $data['peringkat']        = $tetap ? (int) $lama['peringkat'] : null;
                    $data['peringkat_manual'] = $tetap ? 1 : 0;
                }

                // Prestasi tidak diubah di halaman hasil: percobaan tersimpan tetap.
                if (! $s['percobaan'] && $lama['percobaan'] !== null && $data['prestasi'] !== null && (int) $lama['prestasi'] === $data['prestasi']) {
                    $data['percobaan'] = $lama['percobaan'];
                }

                if (! self::berubah($lama, $data)) {
                    continue;
                }

                if ($s['versi'] !== null && $s['versi'] !== (int) $lama['versi']) {
                    $bentrok[$id] = self::PESAN_BENTROK;

                    continue;
                }

                $ubah[$id] = $data + ['versi' => (int) $lama['versi'] + 1];
            }

            if ($bentrok !== []) {
                return $bentrok;
            }

            foreach ($ubah as $id => $data) {
                $this->db->table('hasil')->where('id', $id)->update($data + ['diubah_pada' => $sekarang]);
            }

            $this->hitungUlang($babakId, $jenis);

            return [];
        });

        if ($galat !== []) {
            return $galat;
        }

        // Seri dan lintasan (F9-08): babak berikut terisi dari Q/q bila babak
        // ini sudah dibagi ke seri dan hasilnya lengkap.
        (new SeriLintasan($this->db))->setelahHasilTersimpan($babakId);

        return [];
    }

    /**
     * Terbitkan (`true`) atau tarik kembali (`false`) hasil satu nomor.
     */
    public function terbitkan(int $nomorId, bool $terbit): void
    {
        $this->db->table('nomor_lomba')->where('id', $nomorId)->update(['hasil_terbit_pada' => $terbit ? date('Y-m-d H:i:s') : null]);
    }

    /**
     * Hitung ulang peringkat otomatis satu babak; koreksi admin tetap.
     * Prestasi sama dipisah pemecah seri nomornya (F9-09).
     */
    public function hitungUlang(int $babakId, string $jenis): void
    {
        $aktif   = (int) ($this->db->table('babak b')->select('nl.pemecah_seri')->join('nomor_lomba nl', 'nl.id = b.nomor_lomba_id')->where('b.id', $babakId)->get()->getRow()->pemecah_seri ?? 1) === 1;
        $peserta = [];

        foreach ($this->db->table('hasil')->select('id, prestasi, percobaan, seperseribu, keterangan, peringkat, peringkat_manual')->where('babak_id', $babakId)->get()->getResultArray() as $h) {
            $peserta[(int) $h['id']] = [
                'prestasi'   => $h['prestasi'] === null ? null : (int) $h['prestasi'],
                'keterangan' => $h['keterangan'],
                'peringkat'  => $h['peringkat'] === null ? null : (int) $h['peringkat'],
                'manual'     => (int) $h['peringkat_manual'] === 1,
                'pemecah'    => Peringkat::pemecah($jenis, Percobaan::baca($h['percobaan']), $h['seperseribu'] === null ? null : (int) $h['seperseribu'], $aktif),
            ];
        }

        foreach (Peringkat::hitung($peserta, $jenis) as $id => $peringkat) {
            if (! $peserta[$id]['manual'] && $peserta[$id]['peringkat'] !== $peringkat) {
                $this->db->table('hasil')->where('id', $id)->update(['peringkat' => $peringkat]);
            }
        }
    }

    /**
     * Juara I–III (atau peringkat 1 sampai `$sampai`) dari babak Final semua
     * nomor (atau yang terbit saja), untuk klasemen (F8-05); metode poin (F9-11)
     * memakai `$sampai` 8.
     *
     * @return list<array{nomor_id: int, nomor: string, kelompok_umur: string, terbit: bool, peringkat: int, nama: string, sekolah: string, npsn: string|null, pendaftaran_id: int, jenjang: string}>
     */
    public function juara(bool $hanyaTerbit = false, int $sampai = 3): array
    {
        $juara = [];

        foreach ($this->daftarNomor($hanyaTerbit) as $ku) {
            foreach ($ku['nomor'] as $n) {
                if (! $n['final']) {
                    continue;
                }

                $nomor = $this->nomor($n['id']);
                $final = $this->db->table('babak')->select('id')->where('nomor_lomba_id', $n['id'])->where('final', 1)->get()->getRow();

                if ($nomor === null || $final === null) {
                    continue;
                }

                foreach ($this->hasilBabak((int) $final->id, $nomor) as $h) {
                    if ($h['peringkat'] !== null && $h['peringkat'] <= $sampai) {
                        $juara[] = [
                            'nomor_id'       => $n['id'],
                            'nomor'          => $n['nama'],
                            'kelompok_umur'  => $ku['nama'],
                            'terbit'         => $n['terbit_pada'] !== null,
                            'peringkat'      => $h['peringkat'],
                            'nama'           => $h['nama'],
                            'sekolah'        => $h['sekolah'],
                            'npsn'           => $h['npsn'],
                            'pendaftaran_id' => $h['pendaftaran_id'],
                            'jenjang'        => $h['jenjang'],
                        ];
                    }
                }
            }
        }

        return $juara;
    }

    /**
     * Klasemen sekolah per jenjang memakai metode dan poin dari Pengaturan >
     * Hasil (F9-11): `Klasemen::hitung()` atas peringkat 1–8 babak Final.
     * Dipakai halaman klasemen admin dan publik, unduhan, dan videotron.
     *
     * @return array<string, array<string, mixed>>
     */
    public function klasemen(bool $hanyaTerbit = false): array
    {
        return Klasemen::hitung($this->juara($hanyaTerbit, Klasemen::PERINGKAT_POIN), MetodeKlasemen::metode(), MetodeKlasemen::poin());
    }

    /**
     * Nama peserta regu: nama sekolah, ditambah nama regu bila sekolah itu
     * punya lebih dari satu regu di nomor tersebut (K108).
     */
    public static function namaRegu(string $sekolah, string $regu, bool $tunggal): string
    {
        return $tunggal ? $sekolah : $sekolah . ' – ' . Regu::label($regu);
    }

    public static function rapikanNama(string $nama): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $nama));
    }

    /**
     * Urai isian satu baris menjadi kolom `hasil` (tanpa peringkat).
     *
     * @param array<string, mixed>                      $nomor     `nomor()`
     * @param array<string, list<array{0: int, 1: int}>> $barisSkor baris tabel skor (multi)
     * @param array<string, mixed>                      $baris     isian baris
     *
     * @return array{data: array{prestasi: int|null, rincian: string|null, percobaan: string|null, seperseribu: int|null, keterangan: string|null}, percobaan: bool}|array{galat: string}
     */
    private function uraiBaris(array $nomor, array $barisSkor, array $baris): array
    {
        $jenis     = $nomor['jenis_hasil'];
        $rincian   = null;
        $percobaan = null;
        $ribu      = null;
        $adaCoba   = $jenis === JenisHasil::JARAK && is_array($baris['percobaan'] ?? null);

        if ($jenis === JenisHasil::MULTI) {
            $hitung   = TabelSkor::hitung($nomor['komponen'], $barisSkor, is_array($baris['komponen'] ?? null) ? $baris['komponen'] : []);
            $prestasi = ['nilai' => $hitung['total'], 'galat' => $hitung['galat'] === [] ? null : implode(' ', $hitung['galat'])];
            $rincian  = $hitung['total'] === null ? null : TabelSkor::tulisRincian($hitung['rincian']);
        } elseif ($adaCoba) {
            $coba      = Percobaan::urai($baris['percobaan'], (int) $nomor['jumlah_percobaan']);
            $prestasi  = ['nilai' => $coba['percobaan'] === null ? null : Percobaan::terbaik($coba['percobaan']), 'galat' => $coba['galat']];
            $percobaan = Percobaan::tulis($coba['percobaan']);
        } elseif ($jenis === JenisHasil::WAKTU) {
            $prestasi = Prestasi::uraiWaktuRinci(is_string($baris['prestasi'] ?? null) ? $baris['prestasi'] : '');
            $ribu     = $prestasi['seperseribu'];
        } else {
            $prestasi = Prestasi::urai(is_string($baris['prestasi'] ?? null) ? $baris['prestasi'] : '', $jenis);
        }

        if ($prestasi['galat'] !== null) {
            return ['galat' => $prestasi['galat']];
        }

        return [
            'data' => [
                'prestasi'    => $prestasi['nilai'],
                'rincian'     => $rincian,
                'percobaan'   => $percobaan,
                'seperseribu' => $ribu,
                'keterangan'  => Peringkat::keterangan($baris['keterangan'] ?? null),
            ],
            'percobaan' => $adaCoba,
        ];
    }

    /**
     * Isi baris berbeda dari yang tersimpan?
     *
     * @param array<string, mixed> $lama baris `hasil`
     * @param array<string, mixed> $baru kolom yang akan disimpan
     */
    private static function berubah(array $lama, array $baru): bool
    {
        // Peringkat otomatis dihitung ulang sendiri; hanya koreksi admin yang dibandingkan.
        if ((int) ($baru['peringkat_manual'] ?? 0) === 0 && (int) ($lama['peringkat_manual'] ?? 0) === 0) {
            unset($baru['peringkat']);
        }

        foreach ($baru as $kolom => $nilai) {
            $tersimpan = $lama[$kolom] ?? null;

            if (($tersimpan === null) !== ($nilai === null) || ($nilai !== null && (string) $tersimpan !== (string) $nilai)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kunci baris babak sampai transaksi selesai (F9-09): penyimpanan hasil
     * babak yang sama berjalan bergantian, babak lain tidak tertahan.
     */
    private function kunciBabak(int $babakId): void
    {
        $this->db->query('SELECT id FROM ' . $this->db->prefixTable('babak') . ' WHERE id = ? FOR UPDATE', [$babakId]);
    }

    /**
     * Jalankan `$kerja` dalam satu transaksi: semua tersimpan atau tidak ada.
     *
     * transBegin/transCommit dalam try/catch, bukan transStart/transComplete:
     * dalam mode ketat status gagal transaksi lain di koneksi yang sama tidak
     * ikut membatalkan penyimpanan ini (lihat `FaqModel::pindah()`, #129).
     *
     * @template T
     *
     * @param callable(): T $kerja
     *
     * @return T
     */
    private function transaksi(callable $kerja): mixed
    {
        $this->db->transBegin();

        try {
            $hasil = $kerja();
            $this->db->transCommit();

            return $hasil;
        } catch (Throwable $e) {
            $this->db->transRollback();

            throw $e;
        }
    }

    /**
     * @return array<string, string>
     */
    private function periksaBabak(int $nomorId, string $nama, bool $final, ?int $kecuali): array
    {
        $galat = [];

        if ($nama === '') {
            $galat['nama'] = 'Nama babak belum diisi. Contoh: Penyisihan seri 1, Semifinal, atau Final.';
        } elseif (mb_strlen($nama) > self::NAMA_BABAK_MAKSIMAL) {
            $galat['nama'] = 'Nama babak terlalu panjang. Persingkat menjadi paling banyak ' . self::NAMA_BABAK_MAKSIMAL . ' huruf.';
        } else {
            $kembar = $this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('LOWER(nama)', mb_strtolower($nama));

            if ($kecuali !== null) {
                $kembar->where('id !=', $kecuali);
            }

            if ($kembar->countAllResults() > 0) {
                $galat['nama'] = 'Babak ' . $nama . ' sudah ada di nomor ini. Pakai nama lain, misalnya tambahkan nomor serinya.';
            }
        }

        if ($final) {
            $ada = $this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('final', 1);

            if ($kecuali !== null) {
                $ada->where('id !=', $kecuali);
            }

            if ($ada->countAllResults() > 0) {
                $galat['final'] = 'Nomor ini sudah punya babak Final. Satu nomor hanya satu Final; hapus tanda Final di babak lain dulu.';
            }
        }

        return $galat;
    }

    /**
     * @param array<string, mixed> $nomor
     * @param list<string>         $kunci
     */
    private function tulisPeserta(int $babakId, array $nomor, array $kunci, string $sekarang): void
    {
        $baris = [];

        foreach ($kunci as $k) {
            if ($nomor['jenis'] === 'beregu' && preg_match('/^r:(\d+):(.+)$/uD', $k, $m) === 1) {
                $baris[] = ['babak_id' => $babakId, 'atlet_id' => null, 'pendaftaran_id' => (int) $m[1], 'regu' => $m[2], 'dibuat_pada' => $sekarang, 'diubah_pada' => $sekarang];
            } elseif ($nomor['jenis'] !== 'beregu' && preg_match('/^a:(\d+)$/D', $k, $m) === 1) {
                $baris[] = ['babak_id' => $babakId, 'atlet_id' => (int) $m[1], 'pendaftaran_id' => null, 'regu' => null, 'dibuat_pada' => $sekarang, 'diubah_pada' => $sekarang];
            }
        }

        if ($baris !== []) {
            $this->db->table('hasil')->insertBatch($baris);
        }
    }

    /**
     * Regu pendaftaran Lunas di satu nomor (unik per pendaftaran dan regu).
     *
     * @return list<array{pendaftaran_id: int, regu: string, nama_sekolah: string, npsn: string|null}>
     */
    private function reguLunas(int $nomorId): array
    {
        return array_map(static fn (array $r): array => [
            'pendaftaran_id' => (int) $r['pendaftaran_id'],
            'regu'           => (string) $r['regu'],
            'nama_sekolah'   => (string) $r['nama_sekolah'],
            'npsn'           => $r['npsn'],
        ], $this->db->table('atlet_nomor an')
            ->select('a.pendaftaran_id, an.regu, p.nama_sekolah, p.npsn')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('an.nomor_lomba_id', $nomorId)
            ->where('p.status', 'lunas')
            ->where('an.regu IS NOT NULL', null, false)
            ->groupBy('a.pendaftaran_id, an.regu, p.nama_sekolah, p.npsn')
            ->orderBy('p.nama_sekolah')->orderBy('an.regu')
            ->get()->getResultArray());
    }

    /**
     * Banyak regu per sekolah (NPSN, atau pendaftaran bila tanpa NPSN) di satu nomor.
     *
     * @return array<string, int>
     */
    private function jumlahReguSekolah(int $nomorId): array
    {
        $jumlah = [];

        foreach ($this->db->table('atlet_nomor an')
            ->select('a.pendaftaran_id, an.regu, p.npsn')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('an.nomor_lomba_id', $nomorId)
            ->where('an.regu IS NOT NULL', null, false)
            ->groupBy('a.pendaftaran_id, an.regu, p.npsn')
            ->get()->getResultArray() as $r) {
            $kunci          = self::kunciSekolah($r['npsn'], (int) $r['pendaftaran_id']);
            $jumlah[$kunci] = ($jumlah[$kunci] ?? 0) + 1;
        }

        return $jumlah;
    }

    /**
     * Nama anggota inti tiap regu satu nomor, urut nomor atlet.
     *
     * @return array<string, list<string>> kunci peserta => nama
     */
    private function anggotaRegu(int $nomorId): array
    {
        $anggota = [];

        foreach ($this->db->table('atlet_nomor an')
            ->select('a.pendaftaran_id, a.nama, an.regu')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->where('an.nomor_lomba_id', $nomorId)
            ->where('an.peran', Regu::INTI)
            ->orderBy('a.nomor_dada IS NULL', '', false)->orderBy('a.nomor_dada')->orderBy('a.nama')
            ->get()->getResultArray() as $a) {
            $anggota['r:' . $a['pendaftaran_id'] . ':' . $a['regu']][] = (string) $a['nama'];
        }

        return $anggota;
    }

    private static function kunciSekolah(mixed $npsn, int $pendaftaranId): string
    {
        return is_string($npsn) && $npsn !== '' ? 'n' . $npsn : 'p' . $pendaftaranId;
    }
}
