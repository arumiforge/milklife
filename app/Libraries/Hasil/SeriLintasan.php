<?php

namespace App\Libraries\Hasil;

use CodeIgniter\Database\BaseConnection;
use DateTimeImmutable;
use DateTimeInterface;
use Throwable;

/**
 * Seri, lintasan, dan alur babak (F9-08, K140 butir 8) di atas babak dan
 * hasil lomba (F8-04).
 *
 * - **Aturan per nomor** (`aturanNomor()`, `simpanAturanNomor()`): lintasan
 *   per seri (nomor lari, bawaan 8) atau peserta per kelompok (lompat/lempar,
 *   urutan tampil, bawaan satu kelompok) dan urutan lintasan unggulan
 *   (bawaan World Athletics untuk 8 lintasan).
 * - **Aturan lolos per babak** (`aturLolos()`): Q = n terbaik tiap seri,
 *   q = m prestasi terbaik berikutnya; kosong = otomatis mengisi penuh babak
 *   berikutnya.
 * - **Bagi otomatis** (`bagiOtomatis()`): babak pertama memakai peserta
 *   Lunas nomor itu (dan yang hadir bila daftar ulang dipakai), diacak dengan
 *   sekolah sama dipisah; babak lanjutan memakai yang lolos dari babak
 *   sebelumnya bila hasilnya lengkap, diunggulkan dari prestasinya. Benih
 *   acak disimpan di `babak.benih`.
 * - **Geser** (`geser()`): admin mengubah seri dan lintasan tiap peserta.
 * - **Kunci** (`kunci()`): daftar seri tidak bisa diubah lagi dan tampil di
 *   halaman publik `jadwal/<id babak>` (bila sakelar `HalamanDepan.tampil_seri` menyala).
 * - **Babak berikut otomatis** (`setelahHasilTersimpan()`, dipanggil
 *   `HasilLomba::simpanHasil()`): setelah hasil babak yang sudah dibagi ke
 *   seri lengkap, babak berikutnya yang belum dibagi, belum dikunci, dan
 *   belum berprestasi diisi dari Q/q. Admin tetap bisa mengoreksi.
 *
 * Babak berurutan seperti di halaman hasil: urutan, Final paling akhir.
 *
 *     $seri = new SeriLintasan();
 *     $seri->bagiOtomatis(12, ['hanya_hadir' => true]);   // ['jumlah' => 20, 'seri' => 3, 'mode' => 'acak']
 *     $seri->geser(12, [55 => ['seri' => '2', 'lintasan' => '4']]);
 *     $seri->kunci(12, true);
 *     $seri->bagan(10, false);                           // alur babak publik satu nomor
 */
final class SeriLintasan
{
    /**
     * Mode pembagian => keterangan untuk admin.
     *
     * @var array<string, string>
     */
    public const MODE = [
        'acak'     => 'diacak, sekolah sama dipisah',
        'unggulan' => 'diunggulkan dari prestasi babak sebelumnya',
    ];

    private BaseConnection $db;
    private HasilLomba $hasil;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db    = $db ?? db_connect();
        $this->hasil = new HasilLomba($this->db);
    }

    /**
     * Sakelar tampil daftar seri dan bagan di halaman publik (bawaan mati).
     */
    public static function tampil(): bool
    {
        return (bool) setting('HalamanDepan.tampil_seri');
    }

    // ---------- Aturan nomor ----------

    /**
     * Aturan seri satu nomor: `tempat` dan `urutan` yang berlaku (bawaan bila
     * kosong) serta isian tersimpan.
     *
     * @param array<string, mixed> $nomor `HasilLomba::nomor()`
     *
     * @return array{lintasan: bool, tempat: int, urutan: string, tempat_isian: string, urutan_isian: string}
     */
    public function aturanNomor(array $nomor): array
    {
        $n        = $this->db->table('nomor_lomba')->select('tempat_per_seri, urutan_lintasan')->where('id', $nomor['id'])->get()->getRowArray() ?? [];
        $lintasan = PembagiSeri::pakaiLintasan((string) $nomor['jenis_hasil']);
        $simpan   = isset($n['tempat_per_seri']) ? (int) $n['tempat_per_seri'] : null;
        $tempat   = $simpan ?? PembagiSeri::tempatBawaan((string) $nomor['jenis_hasil']);

        if ($lintasan && $tempat < 1) {
            $tempat = PembagiSeri::LINTASAN_BAWAAN;
        }

        $urutan = is_string($n['urutan_lintasan'] ?? null) ? $n['urutan_lintasan'] : '';

        return [
            'lintasan'     => $lintasan,
            'tempat'       => $tempat,
            'urutan'       => $lintasan ? ($urutan !== '' && PembagiSeri::periksaUrutan($urutan, $tempat) === null ? $urutan : PembagiSeri::urutanBawaan($tempat)) : '',
            'tempat_isian' => $simpan === null ? '' : (string) $simpan,
            'urutan_isian' => $urutan,
        ];
    }

    /**
     * Simpan aturan seri satu nomor. Isian kosong = bawaan.
     *
     * @param array<string, mixed> $isian ['tempat_per_seri' => …, 'urutan_lintasan' => …]
     *
     * @return array<string, string> galat per kotak; kosong bila tersimpan
     */
    public function simpanAturanNomor(int $nomorId, array $isian): array
    {
        $nomor = $this->hasil->nomor($nomorId);

        if ($nomor === null) {
            return ['tempat_per_seri' => 'Nomor lomba tidak ditemukan. Muat ulang halaman.'];
        }

        $lintasan = PembagiSeri::pakaiLintasan($nomor['jenis_hasil']);
        $teks     = static fn (string $k): string => is_string($isian[$k] ?? null) ? trim($isian[$k]) : '';
        $tempat   = $teks('tempat_per_seri');
        $urutan   = $lintasan ? $teks('urutan_lintasan') : '';
        $galat    = [];
        $data     = ['tempat_per_seri' => null, 'urutan_lintasan' => null];

        if ($tempat !== '') {
            $batas = $lintasan ? [1, PembagiSeri::LINTASAN_MAKSIMAL] : [0, PembagiSeri::KELOMPOK_MAKSIMAL];

            if (preg_match('/^\d{1,3}$/D', $tempat) !== 1 || (int) $tempat < $batas[0] || (int) $tempat > $batas[1]) {
                $galat['tempat_per_seri'] = $lintasan
                    ? 'Lintasan per seri harus angka 1 sampai ' . PembagiSeri::LINTASAN_MAKSIMAL . ', misalnya 8. Kosongkan untuk memakai 8.'
                    : 'Peserta per kelompok harus angka 0 sampai ' . PembagiSeri::KELOMPOK_MAKSIMAL . '. Tulis 0 atau kosongkan supaya semua peserta satu kelompok.';
            } else {
                $data['tempat_per_seri'] = (int) $tempat;
            }
        }

        if ($urutan !== '' && ! isset($galat['tempat_per_seri'])) {
            $efektif = $data['tempat_per_seri'] ?? PembagiSeri::LINTASAN_BAWAAN;
            $salah   = PembagiSeri::periksaUrutan($urutan, $efektif);

            if ($salah !== null) {
                $galat['urutan_lintasan'] = $salah;
            } else {
                $data['urutan_lintasan'] = PembagiSeri::rapikanUrutan($urutan);
            }
        }

        if ($galat !== []) {
            return $galat;
        }

        $this->db->table('nomor_lomba')->where('id', $nomorId)->update($data + ['diubah_pada' => date('Y-m-d H:i:s')]);

        return [];
    }

    // ---------- Babak ----------

    /**
     * Babak satu nomor berurutan (Final paling akhir) dengan keadaan seri.
     *
     * @return list<array{id: int, nama: string, final: bool, jumlah_seri: int|null, dikunci: bool, peserta: int, dibagi: int, ada_hasil: bool, tanggal: string|null, jam: string|null}>
     */
    public function daftarBabak(int $nomorId): array
    {
        $hitung = [];

        foreach ($this->db->table('hasil h')
            ->select('h.babak_id, COUNT(*) AS peserta, SUM(h.seri IS NOT NULL) AS dibagi, SUM(h.prestasi IS NOT NULL OR h.keterangan IS NOT NULL) AS berhasil', false)
            ->join('babak b', 'b.id = h.babak_id')
            ->where('b.nomor_lomba_id', $nomorId)
            ->groupBy('h.babak_id')
            ->get()->getResultArray() as $h) {
            $hitung[(int) $h['babak_id']] = $h;
        }

        $daftar = [];

        foreach ($this->db->table('babak')->where('nomor_lomba_id', $nomorId)->orderBy('final')->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $b) {
            $id       = (int) $b['id'];
            $daftar[] = [
                'id'          => $id,
                'nama'        => (string) $b['nama'],
                'final'       => (int) $b['final'] === 1,
                'jumlah_seri' => $b['jumlah_seri'] === null ? null : (int) $b['jumlah_seri'],
                'dikunci'     => $b['seri_dikunci_pada'] !== null,
                'peserta'     => (int) ($hitung[$id]['peserta'] ?? 0),
                'dibagi'      => (int) ($hitung[$id]['dibagi'] ?? 0),
                'ada_hasil'   => (int) ($hitung[$id]['berhasil'] ?? 0) > 0,
                'tanggal'     => $b['tanggal'] === null ? null : (string) $b['tanggal'],
                'jam'         => $b['jam_mulai'] === null ? null : substr((string) $b['jam_mulai'], 0, 5),
            ];
        }

        return $daftar;
    }

    /**
     * Satu babak beserta nomor, aturan seri, babak sebelum dan berikutnya.
     *
     * @return array<string, mixed>|null
     */
    public function babak(int $babakId): ?array
    {
        $dasar = $this->hasil->babak($babakId);

        if ($dasar === null) {
            return null;
        }

        $b       = $this->db->table('babak')->where('id', $babakId)->get()->getRowArray();
        $urut    = $this->daftarBabak($dasar['nomor']['id']);
        $posisi  = array_search($babakId, array_column($urut, 'id'), true);
        $sebelum = $posisi !== false && $posisi > 0 ? $urut[$posisi - 1] : null;
        $berikut = $posisi !== false && isset($urut[$posisi + 1]) ? $urut[$posisi + 1] : null;
        $ini     = $posisi === false ? null : $urut[$posisi];

        return $dasar + [
            'jumlah_seri'   => $b['jumlah_seri'] === null ? null : (int) $b['jumlah_seri'],
            'lolos_q'       => $b['lolos_q'] === null ? null : (int) $b['lolos_q'],
            'lolos_q_kecil' => $b['lolos_q_kecil'] === null ? null : (int) $b['lolos_q_kecil'],
            'benih'         => $b['benih'] === null ? null : (int) $b['benih'],
            'dikunci'       => $b['seri_dikunci_pada'] !== null,
            'dikunci_pada'  => $b['seri_dikunci_pada'],
            'tanggal'       => $b['tanggal'] === null ? null : (string) $b['tanggal'],
            'jam'           => $b['jam_mulai'] === null ? null : substr((string) $b['jam_mulai'], 0, 5),
            'lokasi'        => (string) ($b['lokasi'] ?? ''),
            'peserta'       => $ini['peserta'] ?? 0,
            'dibagi'        => $ini['dibagi'] ?? 0,
            'ada_hasil'     => $ini['ada_hasil'] ?? false,
            'sebelum'       => $sebelum,
            'berikut'       => $berikut,
            'aturan'        => $this->aturanNomor($dasar['nomor']),
        ];
    }

    /**
     * Peserta babak urut seri lalu lintasan; yang belum dibagi di akhir.
     *
     * @param array<string, mixed> $babak `babak()`
     *
     * @return list<array<string, mixed>> `HasilLomba::hasilBabak()`
     */
    public function peserta(array $babak): array
    {
        $baris = $this->hasil->hasilBabak((int) $babak['id'], $babak['nomor']);

        usort($baris, static fn (array $a, array $b): int => [$a['seri'] ?? PHP_INT_MAX, $a['lintasan'] ?? PHP_INT_MAX, (string) $a['nomor_dada'], $a['nama']]
            <=> [$b['seri'] ?? PHP_INT_MAX, $b['lintasan'] ?? PHP_INT_MAX, (string) $b['nomor_dada'], $b['nama']]);

        return $baris;
    }

    /**
     * Peserta dikelompokkan per seri (urut lintasan); kunci 0 = belum dibagi.
     *
     * @param list<array<string, mixed>> $peserta `peserta()`
     *
     * @return array<int, list<array<string, mixed>>>
     */
    public static function perSeri(array $peserta): array
    {
        $seri = [];

        foreach ($peserta as $p) {
            $seri[$p['seri'] ?? 0][] = $p;
        }

        ksort($seri);

        if (isset($seri[0])) {
            $belum = $seri[0];
            unset($seri[0]);
            $seri[0] = $belum;
        }

        return $seri;
    }

    /**
     * Aturan lolos yang berlaku dari babak ini ke babak berikutnya.
     *
     * @param array<string, mixed> $babak `babak()`
     *
     * @return array{q: int, q_kecil: int, otomatis: bool, kapasitas: int}
     */
    public function aturanLolos(array $babak): array
    {
        if ($babak['berikut'] === null) {
            return ['q' => 0, 'q_kecil' => 0, 'otomatis' => true, 'kapasitas' => 0];
        }

        $aturan     = $babak['aturan'];
        $seriLanjut = max(1, (int) ($babak['berikut']['jumlah_seri'] ?? 1));
        $kapasitas  = $aturan['tempat'] > 0 ? $aturan['tempat'] * $seriLanjut : PembagiSeri::KAPASITAS_TANPA_LINTASAN;

        if ($babak['lolos_q'] !== null || $babak['lolos_q_kecil'] !== null) {
            return ['q' => (int) $babak['lolos_q'], 'q_kecil' => (int) $babak['lolos_q_kecil'], 'otomatis' => false, 'kapasitas' => $kapasitas];
        }

        $seriAsal = count(array_unique(array_map(static fn (array $h): int => (int) $h['seri'], $this->db->table('hasil')->select('seri')->where('babak_id', $babak['id'])->get()->getResultArray())));

        return PembagiSeri::aturanOtomatis(max(1, $seriAsal), $kapasitas) + ['otomatis' => true, 'kapasitas' => $kapasitas];
    }

    /**
     * Simpan aturan lolos satu babak. Kedua kotak kosong = otomatis.
     *
     * @param array<string, mixed> $isian ['lolos_q' => …, 'lolos_q_kecil' => …]
     *
     * @return array<string, string> galat per kotak
     */
    public function aturLolos(int $babakId, array $isian): array
    {
        if ($this->babak($babakId) === null) {
            return ['lolos_q' => 'Babak tidak ditemukan. Muat ulang halaman.'];
        }

        $data  = [];
        $galat = [];

        foreach (['lolos_q' => 'Q', 'lolos_q_kecil' => 'q'] as $kotak => $label) {
            $nilai = is_string($isian[$kotak] ?? null) ? trim($isian[$kotak]) : '';

            if ($nilai === '') {
                $data[$kotak] = null;
            } elseif (preg_match('/^\d{1,3}$/D', $nilai) !== 1 || (int) $nilai > 99) {
                $galat[$kotak] = 'Isi ' . $label . ' dengan angka 0 sampai 99, atau kosongkan keduanya supaya dihitung otomatis.';
            } else {
                $data[$kotak] = (int) $nilai;
            }
        }

        if ($galat !== []) {
            return $galat;
        }

        // Satu kotak diisi: yang kosong berarti 0.
        if ($data['lolos_q'] !== null || $data['lolos_q_kecil'] !== null) {
            $data['lolos_q'] ??= 0;
            $data['lolos_q_kecil'] ??= 0;
        }

        $this->db->table('babak')->where('id', $babakId)->update($data + ['diubah_pada' => date('Y-m-d H:i:s')]);

        return [];
    }

    /**
     * Yang lolos dari satu babak menurut aturannya, urut prestasi terbaik.
     *
     * @param array<string, mixed> $babak `babak()`
     *
     * @return array<string, 'Q'|'q'>
     */
    public function lolosDari(array $babak): array
    {
        $aturan = $this->aturanLolos($babak);

        if ($babak['berikut'] === null || $aturan['q'] + $aturan['q_kecil'] === 0) {
            return [];
        }

        $baris = array_map(static fn (array $h): array => [
            'kunci'      => $h['kunci'],
            'seri'       => $h['seri'],
            'prestasi'   => $h['prestasi'],
            'keterangan' => $h['keterangan'],
        ], $this->hasil->hasilBabak((int) $babak['id'], $babak['nomor']));

        return PembagiSeri::lolos($baris, $aturan['q'], $aturan['q_kecil'], (string) $babak['nomor']['jenis_hasil']);
    }

    /**
     * Semua peserta babak sudah punya prestasi atau keterangan?
     */
    public function lengkap(int $babakId): bool
    {
        $jumlah = $this->db->table('hasil')->where('babak_id', $babakId)->countAllResults();
        $kosong = $this->db->table('hasil')->where('babak_id', $babakId)->where('prestasi IS NULL', null, false)->where('keterangan IS NULL', null, false)->countAllResults();

        return $jumlah > 0 && $kosong === 0;
    }

    /**
     * Peserta babak yang berbeda dari yang lolos menurut hasil babak
     * sebelumnya (bila hasil itu lengkap): `kurang` belum ikut, `lebih` tidak lolos.
     *
     * @param array<string, mixed>       $babak   `babak()`
     * @param list<array<string, mixed>> $peserta `peserta()`
     *
     * @return array{kurang: list<string>, lebih: list<string>}|null `null` bila tidak ada pembanding
     */
    public function selisihLolos(array $babak, array $peserta): ?array
    {
        if ($babak['sebelum'] === null || ! $this->lengkap($babak['sebelum']['id'])) {
            return null;
        }

        $sebelum = $this->babak($babak['sebelum']['id']);

        if ($sebelum === null) {
            return null;
        }

        $lolos = $this->lolosDari($sebelum);

        if ($lolos === []) {
            return null;
        }

        $nama = array_column($this->hasil->hasilBabak($sebelum['id'], $sebelum['nomor']), 'nama', 'kunci');
        $ada  = array_column($peserta, 'nama', 'kunci');

        return [
            'kurang' => array_values(array_map(static fn (string $k): string => $nama[$k] ?? $k, array_keys(array_diff_key($lolos, $ada)))),
            'lebih'  => array_values(array_diff_key($ada, $lolos)),
        ];
    }

    // ---------- Bagi, geser, kunci ----------

    /**
     * Ada atlet yang sudah ditandai hadir (daftar ulang dipakai)?
     */
    public function daftarUlangDipakai(): bool
    {
        return $this->db->table('atlet a')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->where('p.status', 'lunas')
            ->where('a.hadir_pada IS NOT NULL', null, false)
            ->countAllResults() > 0;
    }

    /**
     * Bagi otomatis peserta babak ke seri dan lintasan.
     *
     * - Babak pertama: semua atlet atau regu Lunas nomor itu (`hanya_hadir`:
     *   yang sudah hadir saja; regu hadir bila ada anggotanya yang hadir), acak.
     * - Babak lanjutan dengan hasil babak sebelumnya lengkap: yang lolos (Q/q), unggulan.
     * - Selain itu: peserta babak yang sudah ada, acak.
     *
     * Ditolak bila babak dikunci atau sudah ada prestasinya.
     *
     * @param array{hanya_hadir?: bool, jumlah_seri?: int|null, benih?: int|null} $opsi
     *
     * @return array{galat?: string, jumlah?: int, seri?: int, mode?: string, benih?: int}
     */
    public function bagiOtomatis(int $babakId, array $opsi = []): array
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return ['galat' => 'Babak tidak ditemukan. Muat ulang halaman.'];
        }

        if ($babak['dikunci']) {
            return ['galat' => 'Daftar seri ' . $babak['nama'] . ' sudah dikunci. Buka kuncinya dulu bila memang perlu dibagi ulang.'];
        }

        if ($babak['ada_hasil']) {
            return ['galat' => 'Babak ' . $babak['nama'] . ' sudah berisi prestasi, jadi tidak dibagi ulang supaya hasilnya tidak tertukar. Geser peserta satu per satu bila perlu.'];
        }

        $nomor  = $babak['nomor'];
        $aturan = $babak['aturan'];
        $mode   = 'acak';

        if ($babak['sebelum'] === null) {
            $calon = $this->hasil->calon($nomor);

            if (! empty($opsi['hanya_hadir'])) {
                $calon = array_intersect_key($calon, $this->kunciHadir($nomor));
            }

            $kunci = array_keys($calon);

            if ($kunci === []) {
                return ['galat' => empty($opsi['hanya_hadir'])
                    ? 'Belum ada peserta Lunas di nomor ini.'
                    : 'Belum ada peserta yang ditandai hadir di nomor ini. Tandai hadir di Daftar ulang, atau hilangkan centang "Hanya yang sudah hadir".'];
            }
        } elseif ($this->lengkap($babak['sebelum']['id'])) {
            $sebelum = $this->babak($babak['sebelum']['id']);
            $kunci   = array_keys($sebelum === null ? [] : $this->lolosDari($sebelum));
            $mode    = 'unggulan';

            if ($kunci === []) {
                return ['galat' => 'Belum ada yang lolos dari ' . $babak['sebelum']['nama'] . '. Periksa aturan lolos (Q dan q) babak itu.'];
            }
        } else {
            $kunci = array_column($this->hasil->hasilBabak($babakId, $nomor), 'kunci');

            if ($kunci === []) {
                return ['galat' => 'Babak ini belum punya peserta. Lengkapi hasil ' . $babak['sebelum']['nama'] . ' dulu supaya yang lolos masuk otomatis, atau pilih peserta di halaman hasil babak.'];
            }
        }

        $jumlahSeri = isset($opsi['jumlah_seri']) ? (int) $opsi['jumlah_seri'] : null;
        $paling     = PembagiSeri::jumlahSeri(count($kunci), $aturan['tempat']);

        if ($jumlahSeri !== null && ($jumlahSeri < $paling || $jumlahSeri > count($kunci))) {
            return ['galat' => 'Banyak seri untuk ' . count($kunci) . ' peserta harus ' . ($paling === count($kunci) ? $paling : $paling . ' sampai ' . min(count($kunci), PembagiSeri::SERI_MAKSIMAL)) . '. Kosongkan supaya dipilih otomatis.'];
        }

        $benih = isset($opsi['benih']) && $opsi['benih'] > 0 ? (int) $opsi['benih'] : PembagiSeri::benihBaru();

        return $this->transaksi(function () use ($babakId, $nomor, $kunci, $mode, $aturan, $benih, $jumlahSeri): array {
            $this->hasil->aturPeserta($babakId, $kunci);

            $baris = $this->hasil->hasilBabak($babakId, $nomor);
            $ada   = array_flip(array_column($baris, 'kunci'));

            if ($mode === 'unggulan') {
                $bagi = PembagiSeri::bagiUnggulan(array_values(array_filter($kunci, static fn (string $k): bool => isset($ada[$k]))), $aturan['tempat'], $aturan['lintasan'], $benih, $jumlahSeri, $aturan['urutan']);
            } else {
                $bagi = PembagiSeri::bagiAcak(array_map(static fn (array $h): array => [
                    'kunci'   => $h['kunci'],
                    'sekolah' => $h['npsn'] !== null ? 'n' . $h['npsn'] : 'p' . $h['pendaftaran_id'],
                ], $baris), $aturan['tempat'], $aturan['lintasan'], $benih, $jumlahSeri, $aturan['urutan']);
            }

            $sekarang = date('Y-m-d H:i:s');

            foreach ($baris as $h) {
                $t = $bagi[$h['kunci']] ?? null;
                $this->db->table('hasil')->where('id', $h['id'])->update(['seri' => $t['seri'] ?? null, 'lintasan' => $t['lintasan'] ?? null, 'diubah_pada' => $sekarang]);
            }

            $seri = $bagi === [] ? 0 : max(array_column($bagi, 'seri'));
            $this->db->table('babak')->where('id', $babakId)->update(['jumlah_seri' => $seri === 0 ? null : $seri, 'benih' => $benih, 'diubah_pada' => $sekarang]);

            return ['jumlah' => count($bagi), 'seri' => $seri, 'mode' => $mode, 'benih' => $benih];
        });
    }

    /**
     * Geser peserta: seri dan lintasan baru per baris hasil. Keduanya kosong
     * = belum dibagi. Bila ada isian salah tidak ada yang tersimpan.
     *
     * @param array<int|string, mixed> $isian id hasil => ['seri' => …, 'lintasan' => …]
     *
     * @return array<int, string> id hasil => galat (0 = galat umum); kosong bila tersimpan
     */
    public function geser(int $babakId, array $isian): array
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return [0 => 'Babak tidak ditemukan. Muat ulang halaman.'];
        }

        if ($babak['dikunci']) {
            return [0 => 'Daftar seri ' . $babak['nama'] . ' sudah dikunci. Buka kuncinya dulu untuk menggeser peserta.'];
        }

        $aturan  = $babak['aturan'];
        $batas   = $aturan['lintasan'] ? $aturan['tempat'] : PembagiSeri::KELOMPOK_MAKSIMAL;
        $sebutan = $aturan['lintasan'] ? 'Lintasan' : 'Urutan';
        $ada     = array_flip(array_map('intval', array_column($this->db->table('hasil')->select('id')->where('babak_id', $babakId)->get()->getResultArray(), 'id')));
        $ubah    = [];
        $galat   = [];
        $pakai   = [];

        foreach ($isian as $id => $baris) {
            $id = (int) $id;

            if (! isset($ada[$id]) || ! is_array($baris)) {
                continue;
            }

            $seri     = is_string($baris['seri'] ?? null) ? trim($baris['seri']) : '';
            $lintasan = is_string($baris['lintasan'] ?? null) ? trim($baris['lintasan']) : '';

            if ($seri === '' && $lintasan === '') {
                $ubah[$id] = ['seri' => null, 'lintasan' => null];

                continue;
            }

            if (preg_match('/^\d{1,2}$/D', $seri) !== 1 || (int) $seri < 1) {
                $galat[$id] = 'Seri harus angka 1 sampai ' . PembagiSeri::SERI_MAKSIMAL . '.';

                continue;
            }

            if (preg_match('/^\d{1,2}$/D', $lintasan) !== 1 || (int) $lintasan < 1 || (int) $lintasan > $batas) {
                $galat[$id] = $sebutan . ' harus angka 1 sampai ' . $batas . '.';

                continue;
            }

            $tempat = (int) $seri . '-' . (int) $lintasan;

            if (isset($pakai[$tempat])) {
                $galat[$id]             = $sebutan . ' ' . (int) $lintasan . ' di seri ' . (int) $seri . ' sudah dipakai peserta lain.';
                $galat[$pakai[$tempat]] ??= $galat[$id];

                continue;
            }

            $pakai[$tempat] = $id;
            $ubah[$id]      = ['seri' => (int) $seri, 'lintasan' => (int) $lintasan];
        }

        if ($galat !== []) {
            return $galat;
        }

        // Baris yang tidak dikirim tetap; periksa bentrok dengan yang tetap.
        foreach ($this->db->table('hasil')->select('id, seri, lintasan')->where('babak_id', $babakId)->where('seri IS NOT NULL', null, false)->get()->getResultArray() as $h) {
            $id = (int) $h['id'];

            if (! isset($ubah[$id]) && isset($pakai[(int) $h['seri'] . '-' . (int) $h['lintasan']])) {
                $lain = $pakai[(int) $h['seri'] . '-' . (int) $h['lintasan']];

                $galat[$lain] = $sebutan . ' ' . (int) $h['lintasan'] . ' di seri ' . (int) $h['seri'] . ' sudah dipakai peserta lain.';
            }
        }

        if ($galat !== []) {
            return $galat;
        }

        $this->transaksi(function () use ($ubah, $babakId): void {
            $sekarang = date('Y-m-d H:i:s');

            foreach ($ubah as $id => $data) {
                $this->db->table('hasil')->where('id', $id)->update($data + ['diubah_pada' => $sekarang]);
            }

            $seri = (int) ($this->db->table('hasil')->selectMax('seri', 'terbesar')->where('babak_id', $babakId)->get()->getRow()->terbesar ?? 0);
            $this->db->table('babak')->where('id', $babakId)->update(['jumlah_seri' => $seri === 0 ? null : $seri, 'diubah_pada' => $sekarang]);
        });

        return [];
    }

    /**
     * Kunci (`true`) atau buka (`false`) daftar seri satu babak. Mengunci
     * ditolak bila masih ada peserta yang belum dibagi.
     *
     * @return string|null galat
     */
    public function kunci(int $babakId, bool $kunci): ?string
    {
        $babak = $this->babak($babakId);

        if ($babak === null) {
            return 'Babak tidak ditemukan. Muat ulang halaman.';
        }

        if ($kunci) {
            if ($babak['peserta'] === 0) {
                return 'Babak ' . $babak['nama'] . ' belum punya peserta. Bagi otomatis dulu.';
            }

            if ($babak['dibagi'] < $babak['peserta']) {
                return 'Masih ada ' . ($babak['peserta'] - $babak['dibagi']) . ' peserta tanpa seri. Bagi otomatis atau isi seri dan lintasannya dulu.';
            }
        }

        $this->db->table('babak')->where('id', $babakId)->update(['seri_dikunci_pada' => $kunci ? date('Y-m-d H:i:s') : null, 'diubah_pada' => date('Y-m-d H:i:s')]);

        return null;
    }

    /**
     * Setelah hasil babak tersimpan: bila babak ini sudah dibagi ke seri dan
     * hasilnya lengkap, babak berikutnya yang belum dibagi, belum dikunci, dan
     * belum berprestasi diisi dari yang lolos (Q/q) dan diunggulkan.
     */
    public function setelahHasilTersimpan(int $babakId): void
    {
        $babak = $this->babak($babakId);

        if ($babak === null || $babak['berikut'] === null || $babak['dibagi'] === 0 || ! $this->lengkap($babakId)) {
            return;
        }

        $berikut = $babak['berikut'];

        if ($berikut['dikunci'] || $berikut['ada_hasil'] || $berikut['dibagi'] > 0) {
            return;
        }

        $this->bagiOtomatis($berikut['id']);
    }

    // ---------- Publik dan unduhan ----------

    /**
     * Alur babak satu nomor untuk bagan: tiap babak dengan status, seri dan
     * pesertanya, serta tanda lolos.
     *
     * Untuk publik (`$publik = true`) peserta hanya tampil bila daftar
     * serinya dikunci; tanda Q/q dan prestasi hanya bila hasil nomornya terbit.
     *
     * @return list<array{id: int, nama: string, final: bool, dikunci: bool, status: string, tanggal: string|null, jam: string|null, jumlah_seri: int, peserta: int, seri: array<int, list<array<string, mixed>>>, tanda: array<string, string>, aturan: array{q: int, q_kecil: int, otomatis: bool, kapasitas: int}}>
     */
    public function bagan(int $nomorId, bool $publik, ?DateTimeInterface $kini = null): array
    {
        $nomor = $this->hasil->nomor($nomorId);

        if ($nomor === null) {
            return [];
        }

        $terbit = $nomor['terbit_pada'] !== null;
        $hasil  = [];

        foreach ($this->daftarBabak($nomorId) as $ringkas) {
            $babak   = $this->babak($ringkas['id']);
            $peserta = $babak === null ? [] : $this->peserta($babak);
            $tampil  = ! $publik || $ringkas['dikunci'];
            $tanda   = $babak !== null && (! $publik || $terbit) && $this->lengkap($ringkas['id']) ? $this->lolosDari($babak) : [];

            if ($publik && ! $terbit) {
                $peserta = array_map(static fn (array $p): array => ['prestasi_teks' => '', 'keterangan' => null, 'peringkat' => null] + $p, $peserta);
            }

            $hasil[] = [
                'id'          => $ringkas['id'],
                'nama'        => $ringkas['nama'],
                'final'       => $ringkas['final'],
                'dikunci'     => $ringkas['dikunci'],
                'status'      => JadwalPertandingan::status(['tanggal' => $ringkas['tanggal'], 'jam' => $ringkas['jam'], 'terbit' => $terbit, 'ada_hasil' => $ringkas['ada_hasil']], $kini ?? new DateTimeImmutable('now')),
                'tanggal'     => $ringkas['tanggal'],
                'jam'         => $ringkas['jam'],
                'jumlah_seri' => (int) ($ringkas['jumlah_seri'] ?? 0),
                'peserta'     => $ringkas['peserta'],
                'seri'        => $tampil ? self::perSeri($peserta) : [],
                'tanda'       => $tanda,
                'aturan'      => $babak === null ? ['q' => 0, 'q_kecil' => 0, 'otomatis' => true, 'kapasitas' => 0] : $this->aturanLolos($babak),
            ];
        }

        return $hasil;
    }

    /**
     * Daftar seri untuk unduhan: babak yang sudah dibagi, dengan pesertanya per seri.
     *
     * @param list<int> $babakId urut tampil
     *
     * @return list<array{babak: array<string, mixed>, seri: array<int, list<array<string, mixed>>>, lolos: array{q: int, q_kecil: int, otomatis: bool, kapasitas: int}}>
     */
    public function untukUnduhan(array $babakId): array
    {
        $daftar = [];

        foreach ($babakId as $id) {
            $babak = $this->babak($id);

            if ($babak === null || $babak['dibagi'] === 0) {
                continue;
            }

            $daftar[] = ['babak' => $babak, 'seri' => self::perSeri($this->peserta($babak)), 'lolos' => $this->aturanLolos($babak)];
        }

        return $daftar;
    }

    /**
     * Babak yang sudah dibagi di satu hari, urut jadwal.
     *
     * @return list<int>
     */
    public function babakHari(string $tanggal): array
    {
        $dibagi = array_flip(array_map('intval', array_column($this->db->table('hasil')->select('babak_id')->where('seri IS NOT NULL', null, false)->groupBy('babak_id')->get()->getResultArray(), 'babak_id')));

        return array_values(array_filter(array_map(static fn (array $b): int => $b['id'], (new JadwalPertandingan($this->db))->perHari()[$tanggal] ?? []), static fn (int $id): bool => isset($dibagi[$id])));
    }

    /**
     * Hari terjadwal yang punya babak sudah dibagi.
     *
     * @return list<string> 'Y-m-d'
     */
    public function hariDibagi(): array
    {
        return array_values(array_filter(array_keys((new JadwalPertandingan($this->db))->perHari()), fn (string $h): bool => $this->babakHari($h) !== []));
    }

    /**
     * Keterangan aturan lolos untuk admin dan juri: "Q: 2 terbaik tiap seri,
     * q: 2 waktu terbaik berikutnya". Kosong bila tidak ada babak berikutnya.
     *
     * @param array{q: int, q_kecil: int} $aturan
     */
    public static function teksLolos(array $aturan, string $jenisHasil): string
    {
        $bagian = [];

        if ($aturan['q'] > 0) {
            $bagian[] = 'Q: ' . $aturan['q'] . ' terbaik tiap seri';
        }

        if ($aturan['q_kecil'] > 0) {
            $bagian[] = 'q: ' . $aturan['q_kecil'] . ' ' . ($jenisHasil === JenisHasil::WAKTU ? 'waktu' : 'prestasi') . ' terbaik berikutnya';
        }

        return implode(', ', $bagian);
    }

    /**
     * Kunci peserta yang hadir (daftar ulang) di satu nomor.
     *
     * @param array<string, mixed> $nomor
     *
     * @return array<string, true>
     */
    private function kunciHadir(array $nomor): array
    {
        $kueri = $this->db->table('atlet_nomor an')
            ->join('atlet a', 'a.id = an.atlet_id')
            ->where('an.nomor_lomba_id', $nomor['id'])
            ->where('a.hadir_pada IS NOT NULL', null, false);

        $kunci = [];

        if ($nomor['jenis'] === 'beregu') {
            foreach ($kueri->select('a.pendaftaran_id, an.regu')->where('an.regu IS NOT NULL', null, false)->groupBy('a.pendaftaran_id, an.regu')->get()->getResultArray() as $r) {
                $kunci['r:' . $r['pendaftaran_id'] . ':' . $r['regu']] = true;
            }
        } else {
            foreach ($kueri->select('a.id')->get()->getResultArray() as $a) {
                $kunci['a:' . $a['id']] = true;
            }
        }

        return $kunci;
    }

    /**
     * Jalankan `$kerja` dalam satu transaksi (pola `HasilLomba`, #129).
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
}
