<?php

namespace App\Libraries\Pendaftaran;

use App\Libraries\Jenjang;
use App\Libraries\NomorWa;
use App\Models\NomorLombaModel;
use DateTimeImmutable;

/**
 * Pemeriksaan seluruh kiriman wizard pendaftaran terhadap aturan rancangan
 * bagian 6 dan keputusan K23, K28, K36, K50, K51 (rencana Tahap 4, "Aturan
 * yang diperiksa server").
 *
 * Aturan murni: semua data dari basis data dan pengaturan diberikan lewat
 * konstruktor, dikumpulkan `DataIndukPendaftaran`. Waktu "hari ini" juga
 * diberikan, supaya bisa diuji pada tanggal berapa pun.
 *
 * ## Kiriman (JSON dari wizard)
 *
 *     [
 *         'sekolah' => ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '20317000', 'nama' => ''],
 *         'penanggung_jawab' => [
 *             ['nama' => 'Ibu Contoh', 'nomor_wa' => '0812-3456-7890', 'foto' => 'data:image/jpeg;base64,…'],
 *         ],
 *         'atlet' => [
 *             ['nama' => 'Budi Contoh', 'nisn' => '0012345678', 'tanggal_lahir' => '2016-05-01',
 *              'jenis_kelamin' => 'L', 'nomor' => [12, 40]],   // id nomor lomba
 *         ],
 *     ]
 *
 * ## Induk (`DataIndukPendaftaran::induk()`)
 *
 *     [
 *         'kelompok_umur' => [...],  // aktif: id, nama, tahun_lahir_awal, tahun_lahir_akhir, biaya_individu, jenjang
 *         'nomor_lomba'   => [...],  // aktif pada KU aktif: id, kelompok_umur_id, nama, jenis, kategori, kolom tim
 *         'aturan'        => ['penanggung_jawab_minimal' => 1, 'penanggung_jawab_maksimal' => 5,
 *                             'nomor_individu_maksimal' => 2, 'atlet_per_nomor_maksimal' => 2], // batas kuota boleh null
 *     ]
 *
 * ## Ada (`DataIndukPendaftaran::ada($npsn, $nisn)`)
 *
 *     [
 *         'referensi'      => ['npsn' => …, 'nama' => …, 'bentuk' => 'SD', 'jenjang' => 'SD', 'kabupaten' => 'Kudus'], // atau null
 *         'nisn_terdaftar' => ['0012345678'],      // NISN kiriman yang sudah ada di basis data
 *         'tim'            => [40 => 5],           // nomor beregu => jumlah anggota tim sekolah ini
 *         'individu'       => [12 => 1],           // nomor individu => jumlah atlet sekolah ini
 *     ]
 *
 * ## Hasil `periksa()`
 *
 *     [
 *         'galat'   => [['langkah' => 3, 'jalur' => 'atlet.0.nisn', 'pesan' => '…']],  // [] bila sah
 *         'data'    => [...],   // siap disimpan `SimpanPendaftaran`, atau null bila ada galat
 *         'rincian' => [...],   // untuk langkah 4, atau null bila ada galat
 *     ]
 *
 * `data`: `sekolah` (jenjang, kabupaten, npsn atau null, nama_sekolah),
 * `penanggung_jawab` (nama, nomor_wa baku, foto = bita JPEG), `atlet` (nama
 * huruf besar, nisn, tanggal_lahir, jenis_kelamin, kelompok_umur_id, nomor:
 * list `{nomor_lomba_id, biaya}` dengan biaya `null` untuk beregu), `beregu`
 * (nomor_lomba_id, biaya_tim), `subtotal_individu`, `subtotal_beregu`, `total`.
 *
 * `rincian`: `sekolah` (nama, npsn, kabupaten, jenjang, jenjang_label),
 * `individu` (nama, nisn, kelompok_umur, nomor, biaya; satu baris per atlet per
 * nomor individu), `beregu` (nomor, biaya, susulan, anggota, anggota_lama),
 * `subtotal_individu`, `subtotal_beregu`, `total`.
 */
final class PemeriksaPendaftaran
{
    public const NAMA_SEKOLAH_MAKSIMAL = 150;
    public const NAMA_MAKSIMAL        = 100;
    public const TAHUN_LAHIR_TERKECIL = 1950;

    /**
     * Jenjang di data referensi sekolah → kode jenjang pendaftaran.
     */
    public const JENJANG_REFERENSI = ['SD' => 'A', 'SMP' => 'B', 'SMA' => 'C'];

    private const LANGKAH = ['sekolah' => 1, 'penanggung_jawab' => 2, 'atlet' => 3, 'beregu' => 3, 'individu' => 3];

    private const JENIS_KELAMIN_KATEGORI = ['putra' => 'L', 'putri' => 'P'];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $kelompokUmur = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $nomorLomba = [];

    /**
     * @var list<array{langkah: int, jalur: string, pesan: string}>
     */
    private array $galat = [];

    /**
     * @param array<string, mixed> $induk
     * @param array<string, mixed> $ada
     */
    public function __construct(
        private readonly array $induk,
        private readonly array $ada,
        private readonly string $hariIni,
    ) {
        foreach ($induk['kelompok_umur'] ?? [] as $ku) {
            $this->kelompokUmur[(int) $ku['id']] = $ku;
        }

        foreach ($induk['nomor_lomba'] ?? [] as $nomor) {
            if (isset($this->kelompokUmur[(int) $nomor['kelompok_umur_id']])) {
                $this->nomorLomba[(int) $nomor['id']] = $nomor;
            }
        }
    }

    /**
     * Apakah sekolah di data referensi berbentuk SLB (K28).
     *
     * @param array<string, mixed> $sekolah
     */
    public static function slb(array $sekolah): bool
    {
        return strtoupper(trim((string) ($sekolah['bentuk'] ?? ''))) === 'SLB';
    }

    /**
     * Pesan penolakan SLB, dipakai juga pencarian NPSN.
     */
    public static function pesanSlb(string $nama = ''): string
    {
        return ($nama !== '' ? $nama . ' adalah ' : '') . 'Sekolah Luar Biasa (SLB). SLB tidak termasuk peserta lomba ini. '
            . 'Bila NPSN salah ketik, periksa lagi NPSN-nya.';
    }

    /**
     * NPSN kiriman yang sudah dirapikan (8 angka), atau `null`.
     *
     * @param array<string, mixed> $kiriman
     */
    public static function npsnKiriman(array $kiriman): ?string
    {
        $npsn = trim((string) self::teks($kiriman['sekolah']['npsn'] ?? null));

        return preg_match('/^\d{8}$/D', $npsn) === 1 ? $npsn : null;
    }

    /**
     * NISN kiriman yang berbentuk sah (10 angka), untuk ditanyakan ke basis data.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return list<string>
     */
    public static function nisnKiriman(array $kiriman): array
    {
        $hasil = [];

        foreach (self::daftar($kiriman['atlet'] ?? null) as $atlet) {
            $nisn = trim(self::teks(is_array($atlet) ? ($atlet['nisn'] ?? null) : null));

            if (preg_match('/^\d{10}$/D', $nisn) === 1) {
                $hasil[$nisn] = $nisn;
            }
        }

        return array_values($hasil);
    }

    /**
     * Periksa kiriman.
     *
     * @param array<string, mixed> $kiriman
     * @param bool                 $periksaFoto false = foto hanya diperiksa ada atau tidak
     *                                          (dipakai bila isi foto tidak ikut dikirim)
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, data: array<string, mixed>|null, rincian: array<string, mixed>|null}
     */
    public function periksa(array $kiriman, bool $periksaFoto = true): array
    {
        $this->galat = [];

        $sekolah         = $this->periksaSekolah(is_array($kiriman['sekolah'] ?? null) ? $kiriman['sekolah'] : []);
        $penanggungJawab = $this->periksaPenanggungJawab(self::daftar($kiriman['penanggung_jawab'] ?? null), $periksaFoto);
        [$atlet, $individu, $regu] = $this->periksaAtlet(self::daftar($kiriman['atlet'] ?? null), $sekolah['jenjang']);

        $beregu = $this->periksaTim($regu, $sekolah['npsn']);
        $this->periksaKuota($individu, $sekolah['npsn']);

        if ($this->galat !== []) {
            return ['galat' => $this->galat, 'data' => null, 'rincian' => null];
        }

        return $this->susun($sekolah, $penanggungJawab, $atlet, $beregu);
    }

    // ---------------------------------------------------------------
    // Sekolah
    // ---------------------------------------------------------------

    /**
     * @param array<string, mixed> $isian
     *
     * @return array{jenjang: string|null, kabupaten: string|null, npsn: string|null, nama_sekolah: string}
     */
    private function periksaSekolah(array $isian): array
    {
        $jenjang   = strtoupper(trim(self::teks($isian['jenjang'] ?? null)));
        $jenjang   = Jenjang::sah($jenjang) ? $jenjang : null;
        $kabupaten = Kabupaten::baku($isian['kabupaten'] ?? null);
        $npsnTeks  = preg_replace('/\s+/', '', self::teks($isian['npsn'] ?? null)) ?? '';
        $nama      = self::rapikanSpasi(self::teks($isian['nama'] ?? null));
        $npsn      = null;

        if ($npsnTeks !== '') {
            if (preg_match('/^\d{8}$/D', $npsnTeks) !== 1) {
                $this->tambah('sekolah.npsn', 'NPSN harus 8 angka. Periksa lagi NPSN sekolah, atau kosongkan bila sekolah belum punya NPSN.');
            } else {
                $npsn = $npsnTeks;
            }
        }

        $referensi = $npsn !== null ? ($this->ada['referensi'] ?? null) : null;

        if (is_array($referensi) && ($referensi['npsn'] ?? null) === $npsn) {
            if (self::slb($referensi)) {
                $this->tambah('sekolah.npsn', self::pesanSlb(self::rapikanSpasi((string) $referensi['nama'])));
            }

            // Data referensi menentukan nama, kabupaten, dan jenjang (asumsi 1 rencana Tahap 4).
            $nama      = self::rapikanSpasi((string) $referensi['nama']);
            $kabupaten = Kabupaten::baku($referensi['kabupaten'] ?? null) ?? $kabupaten;
            $jenjang   = self::JENJANG_REFERENSI[strtoupper((string) ($referensi['jenjang'] ?? ''))] ?? $jenjang;
        } else {
            if ($nama === '') {
                $this->tambah('sekolah.nama', $npsn === null
                    ? 'Nama sekolah belum diisi. Tulis nama sekolah, atau isi NPSN supaya nama sekolah tampil sendiri.'
                    : 'NPSN ini tidak ada di data sekolah kami. Tulis nama sekolah secara lengkap.');
            } elseif (mb_strlen($nama) > self::NAMA_SEKOLAH_MAKSIMAL) {
                $this->tambah('sekolah.nama', 'Nama sekolah terlalu panjang. Tulis paling banyak ' . self::NAMA_SEKOLAH_MAKSIMAL . ' huruf.');
            } elseif (preg_match('/\bSLB\b|sekolah\s+luar\s+biasa/iu', $nama) === 1) {
                $this->tambah('sekolah.nama', self::pesanSlb());
            }
        }

        if ($jenjang === null) {
            $this->tambah('sekolah.jenjang', 'Jenjang sekolah belum dipilih. Pilih SD/MI, SMP/MTs, atau SMA/SMK/MA.');
        }

        if ($kabupaten === null) {
            $this->tambah('sekolah.kabupaten', 'Kabupaten belum dipilih. Pilih salah satu: ' . implode(', ', Kabupaten::semua()) . '.');
        }

        return ['jenjang' => $jenjang, 'kabupaten' => $kabupaten, 'npsn' => $npsn, 'nama_sekolah' => $nama];
    }

    // ---------------------------------------------------------------
    // Penanggung jawab
    // ---------------------------------------------------------------

    /**
     * @param list<mixed> $daftar
     *
     * @return list<array{nama: string, nomor_wa: string, foto: string|null}>
     */
    private function periksaPenanggungJawab(array $daftar, bool $periksaFoto): array
    {
        $minimal  = (int) ($this->induk['aturan']['penanggung_jawab_minimal'] ?? 1);
        $maksimal = (int) ($this->induk['aturan']['penanggung_jawab_maksimal'] ?? 5);
        $jumlah   = count($daftar);

        if ($jumlah < $minimal) {
            $this->tambah('penanggung_jawab', "Isi minimal {$minimal} penanggung jawab. Sekarang baru {$jumlah}.");
        } elseif ($jumlah > $maksimal) {
            $this->tambah('penanggung_jawab', "Penanggung jawab paling banyak {$maksimal} orang. Hapus " . ($jumlah - $maksimal) . ' orang.');
        }

        $hasil = [];

        foreach ($daftar as $i => $orang) {
            $orang = is_array($orang) ? $orang : [];
            $ke    = 'Penanggung jawab ' . ($i + 1);
            $nama  = self::rapikanSpasi(self::teks($orang['nama'] ?? null));
            $wa    = NomorWa::normalkan(self::teks($orang['nomor_wa'] ?? null));
            $foto  = $orang['foto'] ?? null;

            if ($nama === '') {
                $this->tambah("penanggung_jawab.{$i}.nama", "{$ke}: nama belum diisi.");
            } elseif (mb_strlen($nama) > self::NAMA_MAKSIMAL) {
                $this->tambah("penanggung_jawab.{$i}.nama", "{$ke}: nama terlalu panjang. Tulis paling banyak " . self::NAMA_MAKSIMAL . ' huruf.');
            }

            if ($wa === null) {
                $this->tambah("penanggung_jawab.{$i}.nomor_wa", "{$ke}: " . lcfirst(NomorWa::PESAN_GALAT));
            }

            $isiFoto = null;

            if ($foto === null || $foto === '' || $foto === false) {
                $this->tambah("penanggung_jawab.{$i}.foto", "{$ke}: foto belum diisi. Ambil foto dengan kamera atau pilih dari galeri.");
            } elseif ($periksaFoto) {
                $isiFoto = FotoPenanggungJawab::baca($foto);

                if ($isiFoto === null) {
                    $this->tambah("penanggung_jawab.{$i}.foto", "{$ke}: foto belum bisa dibaca. Ambil ulang fotonya.");
                }
            }

            $hasil[] = ['nama' => $nama, 'nomor_wa' => (string) $wa, 'foto' => $isiFoto];
        }

        return $hasil;
    }

    // ---------------------------------------------------------------
    // Atlet dan nomor lomba
    // ---------------------------------------------------------------

    /**
     * @param list<mixed> $daftar
     *
     * @return array{0: list<array<string, mixed>>, 1: array<int, int>, 2: array<int, list<string>>}
     *                                                                                                atlet bersih, jumlah atlet per nomor individu, jenis kelamin anggota per nomor beregu
     */
    private function periksaAtlet(array $daftar, ?string $jenjang): array
    {
        if ($daftar === []) {
            $this->tambah('atlet', 'Belum ada atlet. Tambahkan minimal satu atlet.');

            return [[], [], []];
        }

        $maksimalIndividu = (int) ($this->induk['aturan']['nomor_individu_maksimal'] ?? 2);
        $kelompokUmur     = array_values($this->kelompokUmur);
        $nisnTerdaftar    = array_flip(array_map('strval', $this->ada['nisn_terdaftar'] ?? []));
        $nisnDipakai      = [];
        $hasil            = [];
        $individu         = [];
        $regu             = [];

        foreach ($daftar as $i => $isian) {
            $isian = is_array($isian) ? $isian : [];
            $jalur = "atlet.{$i}";
            $nama  = mb_strtoupper(self::rapikanSpasi(self::teks($isian['nama'] ?? null)));
            $ke    = $nama !== '' ? $nama : 'Atlet ' . ($i + 1);
            $nisn  = trim(self::teks($isian['nisn'] ?? null));
            $jk    = strtoupper(trim(self::teks($isian['jenis_kelamin'] ?? null)));
            $lahir = self::tanggal(self::teks($isian['tanggal_lahir'] ?? null));

            if ($nama === '') {
                $this->tambah("{$jalur}.nama", "{$ke}: nama belum diisi.");
            } elseif (mb_strlen($nama) > self::NAMA_MAKSIMAL) {
                $this->tambah("{$jalur}.nama", "{$ke}: nama terlalu panjang. Tulis paling banyak " . self::NAMA_MAKSIMAL . ' huruf.');
            }

            if (preg_match('/^\d{10}$/D', $nisn) !== 1) {
                $this->tambah("{$jalur}.nisn", "{$ke}: NISN harus tepat 10 angka. Periksa lagi NISN-nya.");
            } elseif (isset($nisnDipakai[$nisn])) {
                $this->tambah("{$jalur}.nisn", "{$ke}: NISN {$nisn} sudah dipakai {$nisnDipakai[$nisn]} di pendaftaran ini. Satu atlet cukup didaftarkan sekali dengan semua nomornya.");
            } elseif (isset($nisnTerdaftar[$nisn])) {
                $this->tambah("{$jalur}.nisn", "{$ke}: NISN {$nisn} sudah terdaftar. Atlet yang sudah terdaftar tidak bisa didaftarkan lagi; hubungi panitia bila perlu mengubah datanya.");
            } else {
                $nisnDipakai[$nisn] = $ke;
            }

            if (! in_array($jk, ['L', 'P'], true)) {
                $this->tambah("{$jalur}.jenis_kelamin", "{$ke}: jenis kelamin belum dipilih.");
                $jk = null;
            }

            $ku = null;

            if ($lahir === null) {
                $this->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Pilih tanggal lahir dari kalender.");
            } elseif ($lahir->format('Y-m-d') > $this->hariIni || (int) $lahir->format('Y') < self::TAHUN_LAHIR_TERKECIL) {
                $this->tambah("{$jalur}.tanggal_lahir", "{$ke}: tanggal lahir belum benar. Periksa lagi tahunnya.");
            } elseif ($jenjang !== null) {
                $tentu = KelompokUmurAtlet::tentukan((int) $lahir->format('Y'), $jenjang, $kelompokUmur);
                $ku    = $tentu['kelompok_umur'];

                if ($tentu['galat'] !== null) {
                    $this->tambah("{$jalur}.tanggal_lahir", "{$ke}: {$tentu['galat']}");
                }
            }

            $nomor = $this->periksaNomorAtlet($isian['nomor'] ?? null, $jalur, $ke, $ku, $jk, $maksimalIndividu);

            foreach ($nomor as $baris) {
                $id = (int) $baris['id'];

                if ($baris['jenis'] === 'individu') {
                    $individu[$id] = ($individu[$id] ?? 0) + 1;
                } elseif ($jk !== null) {
                    $regu[$id][] = $jk;
                }
            }

            $hasil[] = [
                'nama'          => $nama,
                'nisn'          => $nisn,
                'tanggal_lahir' => $lahir?->format('Y-m-d'),
                'jenis_kelamin' => $jk,
                'kelompok_umur' => $ku,
                'nomor'         => $nomor,
            ];
        }

        return [$hasil, $individu, $regu];
    }

    /**
     * @param array<string, mixed>|null $ku
     *
     * @return list<array<string, mixed>> baris nomor lomba yang sah untuk atlet ini
     */
    private function periksaNomorAtlet(mixed $daftar, string $jalur, string $ke, ?array $ku, ?string $jk, int $maksimalIndividu): array
    {
        $id = [];

        foreach (self::daftar($daftar) as $nilai) {
            if ((is_int($nilai) || (is_string($nilai) && ctype_digit($nilai))) && (int) $nilai > 0) {
                $id[(int) $nilai] = true;
            } else {
                $this->tambah("{$jalur}.nomor", "{$ke}: pilihan nomor lomba tidak dikenal. Pilih ulang nomor lombanya.");
            }
        }

        if ($id === []) {
            $this->tambah("{$jalur}.nomor", "{$ke}: belum ikut nomor lomba apa pun. Pilih minimal satu nomor individu atau beregu.");

            return [];
        }

        $sah            = [];
        $jumlahIndividu = 0;

        foreach (array_keys($id) as $nomorId) {
            $nomor = $this->nomorLomba[$nomorId] ?? null;

            if ($nomor === null) {
                $this->tambah("{$jalur}.nomor", "{$ke}: ada nomor lomba yang sudah tidak dibuka. Pilih ulang nomor lombanya.");

                continue;
            }

            $namaNomor = NomorLombaModel::namaLengkap($nomor);

            if ($ku !== null && (int) $nomor['kelompok_umur_id'] !== (int) $ku['id']) {
                $milik = $this->kelompokUmur[(int) $nomor['kelompok_umur_id']]['nama'] ?? '';
                $this->tambah("{$jalur}.nomor", "{$ke}: {$namaNomor} untuk {$milik}, sedangkan atlet ini {$ku['nama']}. Pilih nomor {$ku['nama']}.");

                continue;
            }

            $harus = self::JENIS_KELAMIN_KATEGORI[$nomor['kategori']] ?? null;

            if ($jk !== null && $harus !== null && $harus !== $jk) {
                $this->tambah("{$jalur}.nomor", "{$ke}: {$namaNomor} hanya untuk atlet " . ($harus === 'L' ? 'laki-laki' : 'perempuan') . '. Pilih nomor yang sesuai jenis kelaminnya.');

                continue;
            }

            if ($nomor['jenis'] === 'individu') {
                $jumlahIndividu++;
            }

            $sah[] = $nomor;
        }

        if ($jumlahIndividu > $maksimalIndividu) {
            $this->tambah("{$jalur}.nomor", "{$ke}: ikut {$jumlahIndividu} nomor individu, padahal paling banyak {$maksimalIndividu}. Hapus " . ($jumlahIndividu - $maksimalIndividu) . ' nomor individu.');
        }

        return $sah;
    }

    // ---------------------------------------------------------------
    // Tim dan kuota
    // ---------------------------------------------------------------

    /**
     * @param array<int, list<string>> $regu
     *
     * @return list<array{nomor: array<string, mixed>, susulan: bool, anggota: int, anggota_lama: int|null}>
     */
    private function periksaTim(array $regu, ?string $npsn): array
    {
        $timAda = $npsn !== null ? ($this->ada['tim'] ?? []) : [];
        $hasil  = [];

        foreach ($regu as $nomorId => $jenisKelamin) {
            $nomor = $this->nomorLomba[$nomorId];
            $lama  = array_key_exists($nomorId, $timAda) ? (int) $timAda[$nomorId] : null;

            foreach (KelengkapanTim::periksa($nomor, $jenisKelamin, $lama) as $pesan) {
                $this->tambah("beregu.{$nomorId}", $pesan);
            }

            $hasil[] = ['nomor' => $nomor, 'susulan' => $lama !== null, 'anggota' => count($jenisKelamin), 'anggota_lama' => $lama];
        }

        return $hasil;
    }

    /**
     * Kuota atlet per sekolah per nomor individu (K51).
     *
     * @param array<int, int> $individu
     */
    private function periksaKuota(array $individu, ?string $npsn): void
    {
        $batas = $this->induk['aturan']['atlet_per_nomor_maksimal'] ?? null;

        if ($batas === null || $batas === '' || (int) $batas < 1) {
            return;
        }

        $batas = (int) $batas;
        $ada   = $npsn !== null ? ($this->ada['individu'] ?? []) : [];

        foreach ($individu as $nomorId => $jumlah) {
            $lama = (int) ($ada[$nomorId] ?? 0);

            if ($lama + $jumlah <= $batas) {
                continue;
            }

            $nama = NomorLombaModel::namaLengkap($this->nomorLomba[$nomorId]);

            $this->tambah("individu.{$nomorId}", $lama > 0
                ? "{$nama}: sekolah ini sudah mendaftarkan {$lama} atlet sebelumnya, dan paling banyak {$batas} atlet per sekolah. "
                    . ($lama >= $batas ? 'Nomor ini sudah penuh untuk sekolah ini.' : 'Atlet tambahan paling banyak ' . ($batas - $lama) . '.')
                : "{$nama}: paling banyak {$batas} atlet per sekolah, sekarang {$jumlah}. Hapus " . ($jumlah - $batas) . ' atlet dari nomor ini.');
        }
    }

    // ---------------------------------------------------------------
    // Hasil
    // ---------------------------------------------------------------

    /**
     * @param array<string, mixed>       $sekolah
     * @param list<array<string, mixed>> $penanggungJawab
     * @param list<array<string, mixed>> $atlet
     * @param list<array<string, mixed>> $beregu
     *
     * @return array{galat: list<array{langkah: int, jalur: string, pesan: string}>, data: array<string, mixed>, rincian: array<string, mixed>}
     */
    private function susun(array $sekolah, array $penanggungJawab, array $atlet, array $beregu): array
    {
        $dataAtlet     = [];
        $rincianIndiv  = [];
        $biayaIndividu = [];

        foreach ($atlet as $a) {
            $ku    = $a['kelompok_umur'];
            $biaya = (int) ($ku['biaya_individu'] ?? 0);
            $nomor = [];

            foreach ($a['nomor'] as $n) {
                if ($n['jenis'] === 'individu') {
                    $nomor[]         = ['nomor_lomba_id' => (int) $n['id'], 'biaya' => $biaya];
                    $biayaIndividu[] = $biaya;
                    $rincianIndiv[]  = [
                        'nama'          => $a['nama'],
                        'nisn'          => $a['nisn'],
                        'kelompok_umur' => (string) $ku['nama'],
                        'nomor'         => NomorLombaModel::namaLengkap($n),
                        'biaya'         => $biaya,
                    ];
                } else {
                    $nomor[] = ['nomor_lomba_id' => (int) $n['id'], 'biaya' => null];
                }
            }

            $dataAtlet[] = [
                'nama'             => $a['nama'],
                'nisn'             => $a['nisn'],
                'tanggal_lahir'    => $a['tanggal_lahir'],
                'jenis_kelamin'    => $a['jenis_kelamin'],
                'kelompok_umur_id' => (int) $ku['id'],
                'nomor'            => $nomor,
            ];
        }

        $dataBeregu    = [];
        $rincianBeregu = [];
        $biayaBeregu   = [];

        foreach ($beregu as $tim) {
            $biaya = BiayaPendaftaran::tim((int) ($tim['nomor']['biaya_beregu'] ?? 0), $tim['susulan']);

            $dataBeregu[]    = ['nomor_lomba_id' => (int) $tim['nomor']['id'], 'biaya_tim' => $biaya];
            $biayaBeregu[]   = $biaya;
            $rincianBeregu[] = [
                'nomor'         => NomorLombaModel::namaLengkap($tim['nomor']),
                'kelompok_umur' => (string) ($this->kelompokUmur[(int) $tim['nomor']['kelompok_umur_id']]['nama'] ?? ''),
                'biaya'         => $biaya,
                'susulan'       => $tim['susulan'],
                'anggota'       => $tim['anggota'],
                'anggota_lama'  => $tim['anggota_lama'],
            ];
        }

        $total = BiayaPendaftaran::hitung($biayaIndividu, $biayaBeregu);

        return [
            'galat' => [],
            'data'  => [
                'sekolah'          => $sekolah,
                'penanggung_jawab' => $penanggungJawab,
                'atlet'            => $dataAtlet,
                'beregu'           => $dataBeregu,
            ] + $total,
            'rincian' => [
                'sekolah' => [
                    'nama'          => $sekolah['nama_sekolah'],
                    'npsn'          => $sekolah['npsn'],
                    'kabupaten'     => $sekolah['kabupaten'],
                    'jenjang'       => $sekolah['jenjang'],
                    'jenjang_label' => (string) Jenjang::label($sekolah['jenjang']),
                ],
                'individu' => $rincianIndiv,
                'beregu'   => $rincianBeregu,
            ] + $total,
        ];
    }

    // ---------------------------------------------------------------
    // Bantu
    // ---------------------------------------------------------------

    private function tambah(string $jalur, string $pesan): void
    {
        $akar = explode('.', $jalur, 2)[0];

        $this->galat[] = ['langkah' => self::LANGKAH[$akar] ?? 3, 'jalur' => $jalur, 'pesan' => $pesan];
    }

    private static function teks(mixed $nilai): string
    {
        return is_string($nilai) || is_int($nilai) ? (string) $nilai : '';
    }

    private static function rapikanSpasi(string $teks): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }

    /**
     * @return list<mixed>
     */
    private static function daftar(mixed $nilai): array
    {
        return is_array($nilai) ? array_values($nilai) : [];
    }

    private static function tanggal(string $teks): ?DateTimeImmutable
    {
        $teks = trim($teks);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/D', $teks) !== 1) {
            return null;
        }

        $tanggal = DateTimeImmutable::createFromFormat('!Y-m-d', $teks);
        $galat   = DateTimeImmutable::getLastErrors();

        if ($tanggal === false || ($galat !== false && ($galat['warning_count'] > 0 || $galat['error_count'] > 0))) {
            return null;
        }

        return $tanggal;
    }
}
