<?php

namespace App\Libraries\Hasil;

use App\Models\NomorLombaModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Rekor kejuaraan MilkLife (F11-06, K172 butir 3), dicatat mulai Seri 2.
 * Hasil Seri 1 tidak diimpor; admin boleh mengisi rekor dengan tangan.
 *
 * Kunci rekor: nomor lomba (kelompok umur dan jenis kelamin ikut nomornya),
 * berat alat (`nomor_lomba.berat_alat`, teks bebas, huruf besar-kecil dan
 * spasi tidak dibedakan), dan cara pencatatan waktu untuk nomor waktu.
 * Waktu tangan dan waktu elektronik tidak saling memecahkan.
 *
 * Rekor yang berlaku untuk satu kunci = prestasi terbaik di tabel `rekor`;
 * bila sama, yang lebih dulu tercatat.
 *
 * Rekor otomatis (`perbarui()`), dipanggil `HasilLomba` saat hasil
 * diterbitkan, ditarik, atau diubah:
 * - Hanya babak Final nomor yang terbit dan dilombakan.
 * - Prestasi tanpa keterangan, atau dengan keterangan r (tetap dinilai),
 *   yang **lebih baik** dari rekor yang berlaku menjadi rekor baru. Bila
 *   belum ada rekor, prestasi terbaik Final menjadi rekor pertama. NM, DNS,
 *   DNF, dan DQ tidak pernah membuat rekor. Prestasi sama = tidak memecahkan.
 * - Peserta yang prestasinya sama-sama terbaik dicatat semua.
 * - Baris rekor otomatis menyimpan `hasil_id`. Saat terbitan ditarik atau
 *   hasilnya berubah, baris dari nomor itu dihapus dan dihitung ulang, jadi
 *   rekor lama berlaku kembali.
 *
 *     $rekor = new Rekor();
 *     $rekor->perbarui(10);              // setelah terbit/tarik/ubah hasil nomor 10
 *     $rekor->penanda([501, 502]);       // [501 => true] bila hasil 501 membuat rekor
 */
final class Rekor
{
    /**
     * Tanda rekor kejuaraan di hasil, PDF, Excel, dan videotron.
     */
    public const TANDA = 'CR';

    public const ARTI_TANDA = 'Rekor kejuaraan MilkLife';

    /**
     * Panjang paling banyak isian teks.
     *
     * @var array<string, int>
     */
    public const PANJANG = [
        'berat_alat' => 60,
        'atlet'      => 255,
        'sekolah'    => 150,
        'seri'       => 40,
    ];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? db_connect();
    }

    /**
     * Sakelar halaman Rekor publik (`Rekor.tampil`, bawaan mati).
     */
    public static function tampil(): bool
    {
        return (bool) setting('Rekor.tampil');
    }

    /**
     * Teks seri bawaan dari Pengaturan > Acara, misalnya "Seri 2".
     */
    public static function seriBawaan(): string
    {
        $seri = setting('Acara.seri');

        return $seri === null || $seri === '' ? 'Seri' : 'Seri ' . $seri;
    }

    /**
     * Berat alat yang dirapikan untuk dibandingkan: huruf kecil, spasi tunggal.
     */
    public static function kunciAlat(?string $berat): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', (string) $berat)));
    }

    /**
     * Cara pencatatan waktu sebagai bagian kunci rekor: `tangan`,
     * `elektronik`, atau `null` untuk nomor yang bukan waktu.
     */
    public static function caraWaktu(string $jenisHasil, bool $tangan): ?string
    {
        if ($jenisHasil !== JenisHasil::WAKTU) {
            return null;
        }

        return $tangan ? 'tangan' : 'elektronik';
    }

    /**
     * `$a` lebih baik dari `$b`? Waktu makin kecil makin baik, selain itu makin besar.
     */
    public static function lebihBaik(int $a, int $b, string $jenisHasil): bool
    {
        return JenisHasil::besarLebihBaik($jenisHasil) ? $a > $b : $a < $b;
    }

    /**
     * Prestasi terbaik Final yang membuat rekor, dari baris `HasilLomba::hasilBabak()`.
     * Tanpa memandang basis data; dipakai `perbarui()` dan uji unit.
     *
     * @param list<array<string, mixed>> $baris  hasil semua babak Final
     * @param int|null                   $rekor  prestasi rekor yang berlaku, `null` bila belum ada
     *
     * @return list<array<string, mixed>> baris pemecah rekor (lebih dari satu bila prestasinya sama)
     */
    public static function pemecah(array $baris, ?int $rekor, string $jenisHasil): array
    {
        $sah = array_values(array_filter($baris, static fn (array $h): bool => $h['prestasi'] !== null
            && ($h['keterangan'] === null || in_array($h['keterangan'], Peringkat::TETAP_DINILAI, true))));

        if ($sah === []) {
            return [];
        }

        $terbaik = (int) $sah[0]['prestasi'];

        foreach ($sah as $h) {
            if (self::lebihBaik((int) $h['prestasi'], $terbaik, $jenisHasil)) {
                $terbaik = (int) $h['prestasi'];
            }
        }

        if ($rekor !== null && ! self::lebihBaik($terbaik, $rekor, $jenisHasil)) {
            return [];
        }

        return array_values(array_filter($sah, static fn (array $h): bool => (int) $h['prestasi'] === $terbaik));
    }

    /**
     * Hitung ulang rekor otomatis dari satu nomor: baris dari hasil nomor itu
     * dihapus, lalu bila hasilnya terbit, prestasi Final yang memecahkan
     * rekor dicatat lagi.
     */
    public function perbarui(int $nomorId): void
    {
        $hasil = new HasilLomba($this->db);
        $nomor = $hasil->nomor($nomorId);

        $this->lepasHasil(array_map('intval', array_column(
            $this->db->table('hasil h')->select('h.id')->join('babak b', 'b.id = h.babak_id')->where('b.nomor_lomba_id', $nomorId)->get()->getResultArray(),
            'id',
        )));

        if ($nomor === null || $nomor['terbit_pada'] === null || ! $nomor['dilombakan']) {
            return;
        }

        $final   = [];
        $tanggal = null;

        foreach ($this->db->table('babak')->where('nomor_lomba_id', $nomorId)->where('final', 1)->orderBy('urutan')->orderBy('id')->get()->getResultArray() as $b) {
            $final   = array_merge($final, $hasil->hasilBabak((int) $b['id'], $nomor));
            $tanggal ??= $b['tanggal'] ?? null;
        }

        $alat    = $this->beratAlat($nomorId);
        $cara    = self::caraWaktu($nomor['jenis_hasil'], $nomor['waktu_tangan']);
        $berlaku = $this->berlaku($nomorId, $nomor['jenis_hasil'], $alat, $cara);
        $baru    = self::pemecah($final, $berlaku === null ? null : $berlaku['prestasi'], $nomor['jenis_hasil']);

        if ($baru === []) {
            return;
        }

        $sekarang = date('Y-m-d H:i:s');

        foreach ($baru as $h) {
            $this->db->table('rekor')->insert([
                'nomor_lomba_id' => $nomorId,
                'kelompok_umur'  => mb_substr($nomor['kelompok_umur'], 0, 50),
                'nomor'          => mb_substr($nomor['nama'], 0, 120),
                'kategori'       => $nomor['kategori'],
                'jenis_hasil'    => $nomor['jenis_hasil'],
                'cara_waktu'     => $cara,
                'berat_alat'     => $alat,
                'prestasi'       => (int) $h['prestasi'],
                'atlet'          => mb_substr($nomor['jenis'] === 'beregu' && $h['anggota'] !== [] ? implode(', ', $h['anggota']) : (string) $h['nama'], 0, self::PANJANG['atlet']),
                'sekolah'        => mb_substr((string) $h['sekolah'], 0, self::PANJANG['sekolah']),
                'seri'           => self::seriBawaan(),
                'tanggal'        => is_string($tanggal) && $tanggal !== '' ? $tanggal : substr((string) $nomor['terbit_pada'], 0, 10),
                'hasil_id'       => (int) $h['id'],
                'dibuat_pada'    => $sekarang,
                'diubah_pada'    => $sekarang,
            ]);
        }
    }

    /**
     * Hitung ulang rekor otomatis semua nomor yang terbit (setelah impor paket).
     */
    public function perbaruiSemua(): void
    {
        foreach ($this->db->table('nomor_lomba')->select('id')->where('hasil_terbit_pada IS NOT NULL', null, false)->get()->getResultArray() as $n) {
            $this->perbarui((int) $n['id']);
        }
    }

    /**
     * Hapus rekor otomatis milik hasil-hasil ini (sebelum hasilnya dihapus),
     * supaya tidak tertinggal sebagai rekor tanpa hasil.
     *
     * @param list<int> $hasilId
     */
    public function lepasHasil(array $hasilId): void
    {
        if ($hasilId !== []) {
            $this->db->table('rekor')->whereIn('hasil_id', $hasilId)->delete();
        }
    }

    /**
     * Rekor yang berlaku untuk satu kunci, atau `null` bila belum ada.
     *
     * @return array<string, mixed>|null baris `rekor` dengan `prestasi` int
     */
    public function berlaku(int $nomorId, string $jenisHasil, ?string $beratAlat, ?string $caraWaktu): ?array
    {
        $kunci   = self::kunciAlat($beratAlat);
        $terbaik = null;

        foreach ($this->db->table('rekor')->where('nomor_lomba_id', $nomorId)->where('jenis_hasil', $jenisHasil)->orderBy('tanggal')->orderBy('id')->get()->getResultArray() as $r) {
            if (self::kunciAlat($r['berat_alat']) !== $kunci || $r['cara_waktu'] !== $caraWaktu) {
                continue;
            }

            $r['prestasi'] = (int) $r['prestasi'];

            if ($terbaik === null || self::lebihBaik($r['prestasi'], $terbaik['prestasi'], $jenisHasil)) {
                $terbaik = $r;
            }
        }

        return $terbaik;
    }

    /**
     * Hasil yang membuat rekor kejuaraan, untuk tanda CR.
     *
     * @param list<int> $hasilId
     *
     * @return array<int, true>
     */
    public function penanda(array $hasilId): array
    {
        if ($hasilId === []) {
            return [];
        }

        $ada = [];

        foreach ($this->db->table('rekor')->select('hasil_id')->whereIn('hasil_id', $hasilId)->get()->getResultArray() as $r) {
            $ada[(int) $r['hasil_id']] = true;
        }

        return $ada;
    }

    /**
     * Semua rekor per kelompok umur, untuk halaman admin dan publik.
     *
     * - `$hanyaBerlaku`: hanya rekor yang berlaku tiap kunci (halaman publik).
     *   Rekor yang nomornya sudah dihapus tidak ikut.
     * - Tiap butir: `teks` prestasi, `berlaku`, `otomatis` (dari hasil),
     *   `cara` label cara waktu, dan `kunci`.
     *
     * @return list<array{kelompok_umur: string, rekor: list<array<string, mixed>>}>
     */
    public function daftar(bool $hanyaBerlaku = false): array
    {
        $urutNomor = [];

        foreach ($this->nomorBerurutan() as $i => $n) {
            $urutNomor[(int) $n['id']] = $i;
        }

        $semua   = $this->db->table('rekor')->orderBy('tanggal')->orderBy('id')->get()->getResultArray();
        $terbaik = [];

        foreach ($semua as $r) {
            $kunci = self::kunciBaris($r);

            if (! isset($terbaik[$kunci]) || self::lebihBaik((int) $r['prestasi'], (int) $terbaik[$kunci]['prestasi'], (string) $r['jenis_hasil'])) {
                $terbaik[$kunci] = $r;
            }
        }

        $baris = [];

        foreach ($semua as $r) {
            $kunci   = self::kunciBaris($r);
            $berlaku = $terbaik[$kunci]['id'] === $r['id'];

            if ($hanyaBerlaku && (! $berlaku || $r['nomor_lomba_id'] === null)) {
                continue;
            }

            $baris[] = [
                'id'             => (int) $r['id'],
                'nomor_lomba_id' => $r['nomor_lomba_id'] === null ? null : (int) $r['nomor_lomba_id'],
                'kelompok_umur'  => (string) $r['kelompok_umur'],
                'nomor'          => (string) $r['nomor'],
                'kategori'       => (string) $r['kategori'],
                'jenis_hasil'    => (string) $r['jenis_hasil'],
                'cara_waktu'     => $r['cara_waktu'],
                'cara'           => $r['cara_waktu'] === null ? '' : 'Waktu ' . mb_strtolower(NomorLombaModel::CARA_WAKTU[$r['cara_waktu']] ?? $r['cara_waktu']),
                'berat_alat'     => $r['berat_alat'] === null ? '' : (string) $r['berat_alat'],
                'prestasi'       => (int) $r['prestasi'],
                'teks'           => self::teksPrestasi((int) $r['prestasi'], (string) $r['jenis_hasil'], $r['cara_waktu'] === 'tangan'),
                'atlet'          => (string) $r['atlet'],
                'sekolah'        => (string) $r['sekolah'],
                'seri'           => (string) $r['seri'],
                'tanggal'        => $r['tanggal'],
                'otomatis'       => $r['hasil_id'] !== null,
                'berlaku'        => $berlaku,
                'kunci'          => $kunci,
                'urut'           => $r['nomor_lomba_id'] !== null && isset($urutNomor[(int) $r['nomor_lomba_id']]) ? $urutNomor[(int) $r['nomor_lomba_id']] : PHP_INT_MAX,
            ];
        }

        // Urut nomor seperti Pengaturan > Nomor lomba; satu kunci: yang berlaku di atas, lalu terbaru.
        usort($baris, static fn (array $a, array $b): int => [$a['urut'], $a['kelompok_umur'], $a['nomor'], self::kunciAlat($a['berat_alat']), (string) $a['cara_waktu'], ! $a['berlaku'], (string) $b['tanggal'], $b['id']]
            <=> [$b['urut'], $b['kelompok_umur'], $b['nomor'], self::kunciAlat($b['berat_alat']), (string) $b['cara_waktu'], ! $b['berlaku'], (string) $a['tanggal'], $a['id']]);

        $kelompok = [];

        foreach ($baris as $r) {
            $kelompok[$r['kelompok_umur']][] = $r;
        }

        $hasil = [];

        foreach ($kelompok as $ku => $daftar) {
            $hasil[] = ['kelompok_umur' => (string) $ku, 'rekor' => $daftar];
        }

        return $hasil;
    }

    /**
     * Satu baris rekor, atau `null`.
     *
     * @return array<string, mixed>|null
     */
    public function cari(int $id): ?array
    {
        $r = $this->db->table('rekor')->where('id', $id)->get()->getRowArray();

        return is_array($r) ? $r : null;
    }

    /**
     * Nomor lomba yang bisa dipilih di formulir rekor: id => data.
     *
     * @return array<int, array{id: int, nama: string, kelompok_umur: string, jenis_hasil: string, cara_waktu: string|null, berat_alat: string}>
     */
    public function pilihanNomor(): array
    {
        $pilihan = [];

        foreach ($this->nomorBerurutan() as $n) {
            $jenis = JenisHasil::sah($n['jenis_hasil'] ?? null) ? (string) $n['jenis_hasil'] : JenisHasil::WAKTU;

            $pilihan[(int) $n['id']] = [
                'id'            => (int) $n['id'],
                'nama'          => NomorLombaModel::namaLengkap($n),
                'kelompok_umur' => (string) ($n['kelompok_umur'] ?? ''),
                'jenis_hasil'   => $jenis,
                'cara_waktu'    => self::caraWaktu($jenis, NomorLombaModel::waktuTangan($n + ['jenis_hasil' => $jenis])),
                'berat_alat'    => (string) ($n['berat_alat'] ?? ''),
            ];
        }

        return $pilihan;
    }

    /**
     * Isian formulir dari baris rekor (halaman ubah).
     *
     * @param array<string, mixed> $r
     *
     * @return array<string, string>
     */
    public static function isianDariBaris(array $r): array
    {
        return [
            'nomor_lomba_id' => (string) ($r['nomor_lomba_id'] ?? ''),
            'berat_alat'     => (string) ($r['berat_alat'] ?? ''),
            'cara_waktu'     => (string) ($r['cara_waktu'] ?? 'elektronik'),
            'prestasi'       => self::teksPrestasi((int) $r['prestasi'], (string) $r['jenis_hasil'], ($r['cara_waktu'] ?? null) === 'tangan'),
            'atlet'          => (string) $r['atlet'],
            'sekolah'        => (string) $r['sekolah'],
            'seri'           => (string) $r['seri'],
            'tanggal'        => (string) ($r['tanggal'] ?? ''),
        ];
    }

    /**
     * Rapikan isian mentah formulir rekor.
     *
     * @param array<string, mixed> $mentah
     *
     * @return array<string, string>
     */
    public static function rapikanIsian(array $mentah): array
    {
        $teks = static fn (string $k): string => is_scalar($mentah[$k] ?? null) ? trim((string) preg_replace('/\s+/u', ' ', (string) $mentah[$k])) : '';

        return [
            'nomor_lomba_id' => $teks('nomor_lomba_id'),
            'berat_alat'     => $teks('berat_alat'),
            'cara_waktu'     => $teks('cara_waktu') === 'tangan' ? 'tangan' : 'elektronik',
            'prestasi'       => $teks('prestasi'),
            'atlet'          => $teks('atlet'),
            'sekolah'        => $teks('sekolah'),
            'seri'           => $teks('seri'),
            'tanggal'        => $teks('tanggal'),
        ];
    }

    /**
     * Periksa isian, lalu simpan rekor yang diisi admin (`$id` null = baru).
     *
     * @param array<string, string> $isian `rapikanIsian()`
     *
     * @return array<string, string> nama kotak => pesan; kosong bila tersimpan
     */
    public function simpan(?int $id, array $isian): array
    {
        $pilihan = $this->pilihanNomor();
        $galat   = [];
        $nomor   = ctype_digit($isian['nomor_lomba_id']) ? ($pilihan[(int) $isian['nomor_lomba_id']] ?? null) : null;

        if ($nomor === null) {
            $galat['nomor_lomba_id'] = $isian['nomor_lomba_id'] === ''
                ? 'Nomor lomba belum dipilih. Pilih nomor lomba rekor ini.'
                : 'Nomor lomba yang dipilih tidak ditemukan. Pilih lagi dari daftar.';
        }

        $jenis  = $nomor['jenis_hasil'] ?? JenisHasil::WAKTU;
        $tangan = $jenis === JenisHasil::WAKTU && $isian['cara_waktu'] === 'tangan';
        $urai   = Prestasi::urai($isian['prestasi'], $jenis === JenisHasil::MULTI ? JenisHasil::POIN : $jenis);

        if ($isian['prestasi'] === '') {
            $galat['prestasi'] = 'Prestasi belum diisi. ' . self::contohPrestasi($jenis);
        } elseif ($urai['galat'] !== null) {
            $galat['prestasi'] = $urai['galat'];
        }

        foreach (['atlet' => 'Nama atlet belum diisi. Untuk nomor beregu, tulis nama anggota regu.', 'sekolah' => 'Nama sekolah belum diisi.', 'seri' => 'Seri belum diisi, misalnya ' . self::seriBawaan() . '.'] as $kotak => $pesan) {
            if ($isian[$kotak] === '') {
                $galat[$kotak] = $pesan;
            }
        }

        foreach (self::PANJANG as $kotak => $panjang) {
            if (! isset($galat[$kotak]) && mb_strlen($isian[$kotak]) > $panjang) {
                $galat[$kotak] = 'Isian terlalu panjang. Persingkat menjadi paling banyak ' . $panjang . ' huruf.';
            }
        }

        $tanggal = \DateTimeImmutable::createFromFormat('!Y-m-d', $isian['tanggal']);

        if ($isian['tanggal'] !== '' && ($tanggal === false || $tanggal->format('Y-m-d') !== $isian['tanggal'])) {
            $galat['tanggal'] = 'Tanggal belum benar. Pilih tanggal dari kalender, atau kosongkan.';
        }

        if ($galat !== [] || $nomor === null || $urai['nilai'] === null) {
            return $galat;
        }

        $baris   = $this->db->table('nomor_lomba')->select('kategori')->where('id', $nomor['id'])->get()->getRowArray() ?? [];
        $nilai   = $tangan ? Prestasi::bulatTangan($urai['nilai']) : $urai['nilai'];
        $data    = [
            'nomor_lomba_id' => $nomor['id'],
            'kelompok_umur'  => mb_substr($nomor['kelompok_umur'], 0, 50),
            'nomor'          => mb_substr($nomor['nama'], 0, 120),
            'kategori'       => (string) ($baris['kategori'] ?? 'putra'),
            'jenis_hasil'    => $jenis,
            'cara_waktu'     => self::caraWaktu($jenis, $tangan),
            'berat_alat'     => $isian['berat_alat'] === '' ? null : $isian['berat_alat'],
            'prestasi'       => $nilai,
            'atlet'          => $isian['atlet'],
            'sekolah'        => $isian['sekolah'],
            'seri'           => $isian['seri'],
            'tanggal'        => $isian['tanggal'] === '' ? null : $isian['tanggal'],
            'diubah_pada'    => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $this->db->table('rekor')->insert($data + ['dibuat_pada' => $data['diubah_pada']]);
        } else {
            $this->db->table('rekor')->where('id', $id)->where('hasil_id', null)->update($data);
        }

        return [];
    }

    /**
     * Hapus rekor yang diisi admin. Rekor dari hasil tidak bisa dihapus di
     * sini; tarik terbitan atau ubah hasilnya.
     */
    public function hapus(int $id): bool
    {
        $r = $this->cari($id);

        if ($r === null || $r['hasil_id'] !== null) {
            return false;
        }

        return (bool) $this->db->table('rekor')->where('id', $id)->delete();
    }

    /**
     * Teks prestasi rekor seperti di lembar hasil.
     */
    public static function teksPrestasi(int $nilai, string $jenisHasil, bool $tangan): string
    {
        $teks = Prestasi::tulis($nilai, $jenisHasil, $tangan);

        return match ($jenisHasil) {
            JenisHasil::JARAK => $teks . ' m',
            JenisHasil::POIN, JenisHasil::MULTI => $teks . ' poin',
            default => $teks,
        };
    }

    /**
     * Contoh cara menulis prestasi per jenis hasil.
     */
    public static function contohPrestasi(string $jenisHasil): string
    {
        return match ($jenisHasil) {
            JenisHasil::JARAK => 'Tulis dalam meter, misalnya 3,45.',
            JenisHasil::POIN, JenisHasil::MULTI => 'Tulis poinnya, misalnya 1250.',
            default => 'Tulis waktunya, misalnya 12,34 atau 2:05,10.',
        };
    }

    /**
     * Rekor untuk paket cadangan (`PaketPendaftaran`): hanya yang diisi admin
     * atau yang hasilnya sudah tidak ada. Rekor dari hasil terbit dihitung
     * ulang setelah impor (`perbaruiSemua()`).
     *
     * @param array<int, array{kelompok_umur: string, nama: string, kategori: string}> $nomor id => kunci nomor
     *
     * @return list<array<string, mixed>>
     */
    public function ekspor(array $nomor): array
    {
        $daftar = [];

        foreach ($this->db->table('rekor')->where('hasil_id', null)->orderBy('id')->get()->getResultArray() as $r) {
            $kunci    = $r['nomor_lomba_id'] === null ? null : ($nomor[(int) $r['nomor_lomba_id']] ?? null);
            $daftar[] = [
                'nomor_kunci' => $kunci,
                'kelompok_umur' => $r['kelompok_umur'], 'nomor' => $r['nomor'], 'kategori' => $r['kategori'], 'jenis_hasil' => $r['jenis_hasil'],
                'cara_waktu' => $r['cara_waktu'], 'berat_alat' => $r['berat_alat'], 'prestasi' => (int) $r['prestasi'],
                'atlet' => $r['atlet'], 'sekolah' => $r['sekolah'], 'seri' => $r['seri'], 'tanggal' => $r['tanggal'],
                'dibuat_pada' => $r['dibuat_pada'], 'diubah_pada' => $r['diubah_pada'],
            ];
        }

        return $daftar;
    }

    /**
     * Baris rekor dari paket cadangan yang sudah dicocokkan nomornya. Baris
     * yang rusak dilewati.
     *
     * @param mixed    $r       satu butir `ekspor()`
     * @param int|null $nomorId nomor lomba di server ini, `null` bila tidak ditemukan
     *
     * @return array<string, mixed>|null
     */
    public static function barisImpor(mixed $r, ?int $nomorId): ?array
    {
        if (! is_array($r) || ! JenisHasil::sah($r['jenis_hasil'] ?? null) || ! is_int($r['prestasi'] ?? null) || $r['prestasi'] < 1
            || ! in_array($r['kategori'] ?? null, array_keys(NomorLombaModel::KATEGORI), true)) {
            return null;
        }

        $teks = static fn (string $k, int $panjang): string => mb_substr(is_string($r[$k] ?? null) ? $r[$k] : '', 0, $panjang);
        $cara = $r['jenis_hasil'] === JenisHasil::WAKTU ? (($r['cara_waktu'] ?? null) === 'tangan' ? 'tangan' : 'elektronik') : null;

        return [
            'nomor_lomba_id' => $nomorId,
            'kelompok_umur'  => $teks('kelompok_umur', 50),
            'nomor'          => $teks('nomor', 120),
            'kategori'       => $r['kategori'],
            'jenis_hasil'    => $r['jenis_hasil'],
            'cara_waktu'     => $cara,
            'berat_alat'     => is_string($r['berat_alat'] ?? null) && trim($r['berat_alat']) !== '' ? $teks('berat_alat', self::PANJANG['berat_alat']) : null,
            'prestasi'       => $r['prestasi'],
            'atlet'          => $teks('atlet', self::PANJANG['atlet']),
            'sekolah'        => $teks('sekolah', self::PANJANG['sekolah']),
            'seri'           => $teks('seri', self::PANJANG['seri']),
            'tanggal'        => is_string($r['tanggal'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $r['tanggal']) === 1 ? $r['tanggal'] : null,
            'dibuat_pada'    => is_string($r['dibuat_pada'] ?? null) ? $r['dibuat_pada'] : null,
            'diubah_pada'    => is_string($r['diubah_pada'] ?? null) ? $r['diubah_pada'] : null,
        ];
    }

    /**
     * Berat alat nomor saat ini, `null` bila kosong.
     */
    private function beratAlat(int $nomorId): ?string
    {
        $alat = $this->db->table('nomor_lomba')->select('berat_alat')->where('id', $nomorId)->get()->getRowArray()['berat_alat'] ?? null;

        return is_string($alat) && trim($alat) !== '' ? $alat : null;
    }

    /**
     * Semua nomor lomba berurutan seperti Pengaturan > Nomor lomba, dengan
     * nama kelompok umurnya.
     *
     * @return list<array<string, mixed>>
     */
    private function nomorBerurutan(): array
    {
        return $this->db->table('nomor_lomba nl')
            ->select('nl.*, ku.nama AS kelompok_umur')
            ->join('kelompok_umur ku', 'ku.id = nl.kelompok_umur_id')
            ->orderBy('ku.urutan')->orderBy('ku.id')->orderBy('nl.urutan')->orderBy('nl.id')
            ->get()->getResultArray();
    }

    /**
     * Kunci rekor satu baris `rekor`.
     *
     * @param array<string, mixed> $r
     */
    private static function kunciBaris(array $r): string
    {
        // Rekor yang nomornya sudah dihapus dikelompokkan menurut salinan nama nomornya.
        return ($r['nomor_lomba_id'] === null ? 'x:' . $r['kelompok_umur'] . ':' . $r['nomor'] : (string) $r['nomor_lomba_id']) . '|' . $r['jenis_hasil'] . '|' . self::kunciAlat($r['berat_alat']) . '|' . (string) $r['cara_waktu'];
    }
}
