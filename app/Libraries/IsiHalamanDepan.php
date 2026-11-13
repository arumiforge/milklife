<?php

namespace App\Libraries;

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Models\JadwalModel;
use App\Models\KelompokUmurModel;
use App\Models\NomorLombaModel;
use App\Models\TautanModel;
use App\Models\UnduhanModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

/**
 * Pengumpul data halaman publik (rencana Tahap 3, "Pengumpul data publik").
 *
 * Satu-satunya tempat halaman publik membaca pengaturan dan tabel. Tampilan
 * menerima larik siap tampil dan tidak memanggil model, `setting()`, atau
 * basis data sendiri. Hanya data yang boleh dilihat pengunjung yang keluar
 * dari kelas ini: kelompok umur aktif, nomor lomba aktif pada kelompok umur
 * aktif, jadwal dan unduhan yang ditandai tampil, unduhan dan gambar yang
 * berkasnya ada di disk.
 *
 * Teks sudah dirapikan (spasi di ujung dibuang) tetapi belum di-escape;
 * tampilan selalu memakai `esc()`. Teks kosong ditulis `''`, angka yang belum
 * diisi `null`. Uang dan waktu jadwal belum diformat (tampilan memakai
 * `rupiah()` dan `tanggal_id()`); tanggal acara dan ukuran berkas sudah
 * berupa teks.
 *
 * Pemakaian (pengendali halaman publik):
 *
 *     $isi = new IsiHalamanDepan();
 *
 *     return view('beranda', [
 *         'acara'        => $isi->acara(),
 *         'kelompokUmur' => $isi->kelompokUmur(),
 *         // …
 *     ]);
 *
 * ## Bentuk larik
 *
 * `acara()`:
 *
 *     [
 *         'nama'       => 'MilkLife Athletics Challenge', // AcaraController::namaTampil(), tidak pernah kosong
 *         'seri_tahun' => 'Seri 2 – 2026',                // 'Seri 2', '2026', atau '' bila kosong
 *         'tempat'     => 'Supersoccer Arena, Kudus',     // atau ''
 *         'tanggal'    => '9–12 Desember 2026',           // rentang_tanggal_id(), atau ''
 *     ]
 *
 * `tampilan()`:
 *
 *     [
 *         'judul'                => 'Ayo ikut!',          // HalamanDepan.judul, '' bila kosong (tampilan memakai nama acara)
 *         'teks'                 => ['Paragraf satu.', "Baris satu\nbaris dua"], // dipecah pada baris kosong; [] bila kosong
 *         'footer'               => "Teks kaki\nbaris dua", // HalamanDepan.footer, atau "nama acara tahun" bila kosong (kaki())
 *         'gambar_kepala'        => 'http://…/unggahan/halaman-depan/….webp', // base_url($jalur), atau null bila belum ada
 *         'logo_persegi'         => null,
 *         'logo_persegi_panjang' => null,
 *         'logo_djarum'          => null,
 *         'ukuran'               => [                     // ukuran asli gambar yang ada (untuk width/height)
 *             'gambar_kepala' => ['lebar' => 1920, 'tinggi' => 820],
 *             'logo_persegi'  => null,                    // null bila gambar belum ada atau tidak terbaca
 *             // … sama untuk logo_persegi_panjang dan logo_djarum
 *         ],
 *     ]
 *
 * `bukaTutup()`: objek `BukaTutupPendaftaran` (`keadaan()`, `kalimat()`, `label()`, …).
 *
 * `tombol()`: tombol utama di kepala halaman depan.
 *
 *     [
 *         ['kunci' => 'pendaftaran', 'label' => 'Daftar Sekarang', 'alamat' => 'pendaftaran', 'utama' => true, 'keterangan' => null],
 *         ['kunci' => 'cek',         'label' => 'Cek Pendaftaran', 'alamat' => null, 'utama' => false],
 *     ]
 *
 *     // alamat null = tombol nonaktif dengan `keterangan` ("Segera", "Belum dibuka",
 *     // "Ditutup"), atau "Segera" bila tanpa keterangan (K39). Pendaftaran
 *     // menaut hanya saat terbuka; Cek hanya saat pengaturan lengkap.
 *     // Alamat diisi di ALAMAT_PENDAFTARAN dan ALAMAT_CEK_PENDAFTARAN (Tahap 4 dan 5).
 *
 * `kelompokUmur()`: kelompok umur aktif, berurutan seperti di panel admin.
 *
 *     [
 *         [
 *             'id'             => 2,
 *             'nama'           => 'KU 10',
 *             'tahun_lahir'    => 'Lahir 2016–2018',     // KelompokUmurModel::teksTahunLahir(), '' bila belum diisi
 *             'jenjang'        => 'SD/MI',               // Jenjang::gabungLabel(), '' bila belum ada
 *             'biaya_individu' => 150000,                // int (0 = gratis) atau null (belum diisi)
 *             'nomor_individu' => [                      // nomor aktif, berurutan
 *                 ['id' => 7, 'nama' => 'Lari 60 m', 'kategori' => 'putra', 'kategori_label' => 'Putra'],
 *             ],
 *             'nomor_beregu'   => [
 *                 [
 *                     'id'             => 30,
 *                     'nama'           => 'Estafet 4 × 50 m',
 *                     'kategori'       => 'mix',          // 'putra', 'putri', atau 'mix'
 *                     'kategori_label' => 'Mix',
 *                     'jumlah_inti'    => 4,              // int atau null bila belum diisi
 *                     'batas_anggota'  => 6,              // int atau null
 *                     'jumlah_putra'   => 2,              // int atau null; selalu null bila bukan mix
 *                     'jumlah_putri'   => 2,              // int atau null; selalu null bila bukan mix
 *                     'biaya_beregu'   => 200000,         // int atau null
 *                 ],
 *             ],
 *         ],
 *     ]
 *
 * `jadwal()`: jadwal yang ditampilkan, berurutan (`JadwalModel::daftar(true)`).
 *
 *     [['id' => 1, 'judul' => 'Sosialisasi daring', 'keterangan' => '', 'waktu' => '2026-11-20 19:30:00', 'tempat' => '', 'tautan' => 'https://…']]
 *
 *     // waktu: 'Y-m-d H:i:s' atau null (belum pasti); tautan: alamat http/https atau ''.
 *
 * `unduhan()`: unduhan yang ditampilkan dan berkasnya ada.
 *
 *     [['id' => 1, 'judul' => 'Flyer', 'keterangan' => '', 'alamat' => 'http://…/unggahan/unduhan/….pdf', 'jenis' => 'PDF', 'ukuran' => '1,5 MB']]
 *
 * `narahubung()`:
 *
 *     [['nama' => 'Ibu Contoh', 'nomor' => '0812-3456-7890', 'alamat' => 'https://wa.me/6281234567890']]
 *
 * `mediaSosial()`:
 *
 *     [['label' => 'Instagram', 'alamat' => 'https://www.instagram.com/contoh', 'alamat_ringkas' => 'instagram.com/contoh', 'ikon' => 'instagram']]
 *
 *     // ikon: nama Bootstrap Icons tanpa awalan "bi-".
 *
 * `tampilkanBiaya()`: `true` bila biaya individu dan biaya tim ditampilkan di
 * bagian lomba (`HalamanDepan.tampilkan_biaya`, nilai awal `false`, K74). Bila
 * `false`, bagian lomba tidak menulis biaya sama sekali; datanya tetap ada di
 * `kelompokUmur()`.
 *
 * `waTanya()`: `'https://wa.me/6281234567890?text=…'` (tanpa `?text=` bila
 * pesan tanya kosong), atau `null` bila nomor WA tanya admin belum diisi.
 *
 * `waBagikan()`: `'https://wa.me/?text=…'`. Pesannya `HalamanDepan.pesan_bagikan`
 * (atau nama acara bila kosong), ditambah alamat halaman depan pada baris baru
 * bila alamat itu belum tertulis di pesan.
 *
 * Tabel yang belum bisa dibaca (basis data belum dimigrasi) dianggap kosong
 * dan galatnya dicatat di log, sama seperti `DataKelengkapan`, supaya halaman
 * depan tetap terbuka.
 */
final class IsiHalamanDepan
{
    /**
     * Alamat halaman Pendaftaran (Tahap 4). Tombol dan menu Pendaftaran hanya
     * menaut ke sini bila pendaftaran terbuka (rancangan 6.1).
     */
    public const ALAMAT_PENDAFTARAN = 'pendaftaran';

    /**
     * Alamat halaman Cek Pendaftaran (Tahap 5). Menu selalu menaut ke sini;
     * tombol di halaman depan hanya bila pengaturan lengkap (`tombol()`).
     */
    public const ALAMAT_CEK_PENDAFTARAN = 'cek-pendaftaran';

    /**
     * Kunci gambar di `HalamanDepan`, berurutan seperti tampil.
     */
    public const GAMBAR = ['gambar_kepala', 'logo_persegi', 'logo_persegi_panjang', 'logo_djarum'];

    /**
     * Logo yang tampil di kepala halaman depan (K93): logo persegi, atau
     * logo persegi panjang bila yang persegi belum ada. `null` bila
     * keduanya belum diunggah; kepala lalu menulis nama acara.
     *
     * @param array<string, mixed> $tampilan hasil `tampilan()`
     */
    public static function logoKepala(array $tampilan): ?string
    {
        foreach (['logo_persegi', 'logo_persegi_panjang'] as $kunci) {
            if (($tampilan[$kunci] ?? null) !== null) {
                return $kunci;
            }
        }

        return null;
    }

    /**
     * Logo yang sudah diunggah, berurutan seperti di pengaturan.
     *
     * @param array<string, mixed> $tampilan hasil `tampilan()`
     *
     * @return list<array{kunci: string, alamat: string, alt: string, ukuran: array{lebar: int, tinggi: int}|null}>
     */
    public static function logoPembuka(array $tampilan, string $namaAcara): array
    {
        $logo = [];

        foreach ([
            'logo_persegi'         => 'Logo ' . $namaAcara,
            'logo_persegi_panjang' => 'Logo ' . $namaAcara,
            'logo_djarum'          => 'Logo Bakti Olahraga Djarum Foundation',
        ] as $kunci => $alt) {
            if (($tampilan[$kunci] ?? null) !== null) {
                $logo[] = ['kunci' => $kunci, 'alamat' => $tampilan[$kunci], 'alt' => $alt, 'ukuran' => $tampilan['ukuran'][$kunci] ?? null];
            }
        }

        return $logo;
    }

    private Unggahan $unggahan;

    private ?BukaTutupPendaftaran $bukaTutup = null;

    public function __construct(?Unggahan $unggahan = null)
    {
        $this->unggahan = $unggahan ?? new Unggahan();
    }

    /**
     * Data acara untuk kepala halaman.
     *
     * @return array{nama: string, seri_tahun: string, tempat: string, tanggal: string}
     */
    public function acara(): array
    {
        $seri  = self::teks(setting('Acara.seri'));
        $tahun = self::teks(setting('Acara.tahun'));

        return [
            'nama'       => AcaraController::namaTampil(),
            'seri_tahun' => implode(' – ', array_filter([$seri !== '' ? 'Seri ' . $seri : '', $tahun], static fn (string $bagian): bool => $bagian !== '')),
            'tempat'     => self::teks(setting('Acara.tempat')),
            'tanggal'    => rentang_tanggal_id(setting('Acara.tanggal_mulai'), setting('Acara.tanggal_selesai')),
        ];
    }

    /**
     * Isi pengaturan Halaman Depan: judul, teks, kaki, gambar, dan logo.
     *
     * @return array{judul: string, teks: list<string>, footer: string, gambar_kepala: string|null, logo_persegi: string|null, logo_persegi_panjang: string|null, logo_djarum: string|null, ukuran: array<string, array{lebar: int, tinggi: int}|null>}
     */
    public function tampilan(): array
    {
        $hasil = [
            'judul'  => self::teks(setting('HalamanDepan.judul')),
            'teks'   => self::paragraf(setting('HalamanDepan.teks')),
            'footer' => self::kaki(),
        ];

        $ukuran = [];

        foreach (self::GAMBAR as $kunci) {
            $jalur = self::teks(setting('HalamanDepan.' . $kunci));
            $ada   = $jalur !== '' && $this->unggahan->ada($jalur);

            $hasil[$kunci]  = $ada ? base_url($jalur) : null;
            $ukuran[$kunci] = $ada ? $this->ukuranGambar($jalur) : null;
        }

        return $hasil + ['ukuran' => $ukuran];
    }

    /**
     * Teks kaki halaman publik: `HalamanDepan.footer`, atau "nama acara tahun"
     * bila kosong. Dipakai juga tata letak publik untuk semua halaman.
     */
    public static function kaki(): string
    {
        $footer = self::rapikanBaris(setting('HalamanDepan.footer'));

        if ($footer !== '') {
            return $footer;
        }

        return trim(AcaraController::namaTampil() . ' ' . self::teks(setting('Acara.tahun')));
    }

    /**
     * Keadaan pendaftaran saat ini (dibaca sekali per objek).
     */
    public function bukaTutup(): BukaTutupPendaftaran
    {
        return $this->bukaTutup ??= (new DataKelengkapan())->bukaTutup();
    }

    /**
     * Tombol Pendaftaran dan Cek Pendaftaran di kepala halaman depan.
     *
     * Tombol tanpa alamat tampil nonaktif dengan `keterangan`, atau "Segera"
     * bila tidak ada keterangan. Pendaftaran hanya aktif bila `terbuka()`
     * (Tahap 4), Cek hanya bila pengaturan lengkap (Tahap 5); rancangan 6.1.
     *
     * @return list<array{kunci: string, label: string, alamat: string|null, utama: bool, keterangan?: string|null}>
     */
    public function tombol(): array
    {
        $terbuka = $this->bukaTutup()->terbuka();

        return [
            // Pendaftaran hanya bisa dipakai saat terbuka (rancangan 6.1); selain itu nonaktif dengan keterangan keadaannya.
            // Saat terbuka berbunyi "Daftar Sekarang" seperti konsep visual (K81).
            ['kunci' => 'pendaftaran', 'label' => $terbuka ? 'Daftar Sekarang' : 'Pendaftaran', 'alamat' => $terbuka ? self::ALAMAT_PENDAFTARAN : null, 'utama' => true, 'keterangan' => self::keteranganPendaftaran($this->bukaTutup())],
            // Cek hanya bisa dipakai bila pengaturan lengkap (rancangan 6.1).
            ['kunci' => 'cek', 'label' => 'Cek Pendaftaran', 'alamat' => $this->bukaTutup()->keadaan() === BukaTutupPendaftaran::DISIAPKAN ? null : self::ALAMAT_CEK_PENDAFTARAN, 'utama' => false],
        ];
    }

    /**
     * Keterangan tombol dan menu Pendaftaran yang nonaktif: "Segera" selama
     * pengaturan belum lengkap, "Belum dibuka", atau "Ditutup". `null` bila
     * pendaftaran terbuka.
     */
    public static function keteranganPendaftaran(BukaTutupPendaftaran $bukaTutup): ?string
    {
        return match ($bukaTutup->keadaan()) {
            BukaTutupPendaftaran::TERBUKA      => null,
            BukaTutupPendaftaran::BELUM_DIBUKA => 'Belum dibuka',
            BukaTutupPendaftaran::DITUTUP      => 'Ditutup',
            default                            => 'Segera',
        };
    }

    /**
     * Kelompok umur aktif beserta nomor lomba aktifnya.
     *
     * @return list<array<string, mixed>> lihat komentar kelas
     */
    public function kelompokUmur(): array
    {
        $daftarKu    = $this->baca(static fn (): array => model(KelompokUmurModel::class)->daftarLengkap(), 'kelompok umur');
        $daftarNomor = $this->baca(static fn (): array => model(NomorLombaModel::class)->daftarLengkap(), 'nomor lomba');

        $nomor = [];

        foreach ($daftarNomor as $baris) {
            if ((int) $baris['aktif'] !== 1 || (int) ($baris['kelompok_umur_aktif'] ?? 0) !== 1) {
                continue;
            }

            $kategori = (string) $baris['kategori'];
            $isi      = [
                'id'             => (int) $baris['id'],
                'nama'           => self::teks($baris['nama']),
                'kategori'       => $kategori,
                'kategori_label' => NomorLombaModel::KATEGORI[$kategori] ?? ucfirst($kategori),
            ];

            if ($baris['jenis'] === 'beregu') {
                $mix = $kategori === 'mix';

                $nomor[(int) $baris['kelompok_umur_id']]['beregu'][] = $isi + [
                    'jumlah_inti'   => self::angka($baris['jumlah_inti']),
                    'batas_anggota' => self::angka($baris['batas_anggota']),
                    'jumlah_putra'  => $mix ? self::angka($baris['jumlah_putra']) : null,
                    'jumlah_putri'  => $mix ? self::angka($baris['jumlah_putri']) : null,
                    'biaya_beregu'  => self::angka($baris['biaya_beregu']),
                ];
            } else {
                $nomor[(int) $baris['kelompok_umur_id']]['individu'][] = $isi;
            }
        }

        $hasil = [];

        foreach ($daftarKu as $ku) {
            if ((int) $ku['aktif'] !== 1) {
                continue;
            }

            $id = (int) $ku['id'];

            $hasil[] = [
                'id'             => $id,
                'nama'           => self::teks($ku['nama']),
                'tahun_lahir'    => KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'] ?? null, $ku['tahun_lahir_akhir'] ?? null),
                'jenjang'        => Jenjang::gabungLabel($ku['jenjang'] ?? []),
                'biaya_individu' => self::angka($ku['biaya_individu']),
                'nomor_individu' => $nomor[$id]['individu'] ?? [],
                'nomor_beregu'   => $nomor[$id]['beregu'] ?? [],
            ];
        }

        return $hasil;
    }

    /**
     * Apakah biaya individu dan biaya tim ditampilkan di bagian lomba.
     */
    public function tampilkanBiaya(): bool
    {
        return (bool) setting('HalamanDepan.tampilkan_biaya');
    }

    /**
     * Jadwal yang ditampilkan di halaman depan.
     *
     * @return list<array{id: int, judul: string, keterangan: string, waktu: string|null, tempat: string, tautan: string}>
     */
    public function jadwal(): array
    {
        $hasil = [];

        foreach ($this->baca(static fn (): array => model(JadwalModel::class)->daftar(true), 'jadwal') as $baris) {
            $waktu = BukaTutupPendaftaran::bacaWaktu($baris['waktu'] ?? null);

            $hasil[] = [
                'id'         => (int) $baris['id'],
                'judul'      => self::teks($baris['judul']),
                'keterangan' => self::rapikanBaris($baris['keterangan'] ?? null),
                'waktu'      => $waktu?->format('Y-m-d H:i:s'),
                'tempat'     => self::teks($baris['tempat'] ?? null),
                'tautan'     => self::alamatWeb($baris['tautan'] ?? null) ?? '',
            ];
        }

        return $hasil;
    }

    /**
     * Berkas unduhan yang ditampilkan dan berkasnya ada di disk.
     *
     * @return list<array{id: int, judul: string, keterangan: string, alamat: string, jenis: string, ukuran: string}>
     */
    public function unduhan(): array
    {
        $hasil = [];

        foreach ($this->baca(static fn (): array => model(UnduhanModel::class)->daftar(true), 'unduhan') as $baris) {
            $berkas = (string) $baris['berkas'];
            $ukuran = $this->unggahan->ukuran($berkas);

            if ($ukuran === null) {
                continue;
            }

            $hasil[] = [
                'id'         => (int) $baris['id'],
                'judul'      => self::teks($baris['judul']),
                'keterangan' => self::rapikanBaris($baris['keterangan'] ?? null),
                'alamat'     => base_url($berkas),
                'jenis'      => $this->unggahan->jenis($berkas),
                'ukuran'     => Unggahan::ukuranTeks($ukuran),
            ];
        }

        return $hasil;
    }

    /**
     * Narahubung dengan tautan WA. Nomor yang tidak sah dilewati.
     *
     * @return list<array{nama: string, nomor: string, alamat: string}>
     */
    public function narahubung(): array
    {
        $hasil = [];

        foreach ($this->baca(static fn (): array => model(TautanModel::class)->daftarJenis(TautanModel::NARAHUBUNG), 'narahubung') as $baris) {
            $nomor = NomorWa::normalkan((string) $baris['nilai']);

            if ($nomor === null) {
                continue;
            }

            $hasil[] = [
                'nama'   => self::teks($baris['label']),
                'nomor'  => NomorWa::tampilan($nomor),
                'alamat' => 'https://wa.me/' . $nomor,
            ];
        }

        return $hasil;
    }

    /**
     * Media sosial. Alamat yang bukan `http`/`https` dilewati.
     *
     * @return list<array{label: string, alamat: string, alamat_ringkas: string, ikon: string}>
     */
    public function mediaSosial(): array
    {
        $hasil = [];

        foreach ($this->baca(static fn (): array => model(TautanModel::class)->daftarJenis(TautanModel::MEDIA_SOSIAL), 'media sosial') as $baris) {
            $alamat = self::alamatWeb($baris['nilai'] ?? null);

            if ($alamat === null) {
                continue;
            }

            $hasil[] = [
                'label'          => self::teks($baris['label']),
                'alamat'         => $alamat,
                'alamat_ringkas' => TautanModel::alamatRingkas($alamat),
                'ikon'           => TautanModel::ikonMediaSosial($alamat),
            ];
        }

        return $hasil;
    }

    /**
     * Alamat tombol "tanya admin" lewat WA, atau `null` bila nomornya belum diisi.
     */
    public function waTanya(): ?string
    {
        $nomor = NomorWa::normalkan(self::teks(setting('HalamanDepan.nomor_wa_tanya')));

        if ($nomor === null) {
            return null;
        }

        $pesan = self::rapikanBaris(setting('HalamanDepan.pesan_tanya'));

        return 'https://wa.me/' . $nomor . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
    }

    /**
     * Alamat tombol "bagikan ke WA" (tanpa nomor tujuan; pengunjung memilih sendiri).
     */
    public function waBagikan(): string
    {
        $pesan = self::rapikanBaris(setting('HalamanDepan.pesan_bagikan'));

        if ($pesan === '') {
            $pesan = AcaraController::namaTampil();
        }

        $alamat = site_url('/');

        if (! self::memuatAlamat($pesan, $alamat)) {
            $pesan .= "\n" . $alamat;
        }

        return 'https://wa.me/?text=' . rawurlencode($pesan);
    }

    // ---------------------------------------------------------------
    // Bantuan
    // ---------------------------------------------------------------

    /**
     * Jalankan pembacaan tabel; bila tabel tidak terbaca, catat di log dan kembalikan daftar kosong.
     *
     * @param callable(): list<array<string, mixed>> $baca
     *
     * @return list<array<string, mixed>>
     */
    private function baca(callable $baca, string $nama): array
    {
        try {
            return $baca();
        } catch (DatabaseException $e) {
            log_message('error', 'Data halaman depan ({nama}) tidak terbaca: {pesan}', ['nama' => $nama, 'pesan' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Lebar dan tinggi asli gambar tersimpan, atau `null` bila tidak terbaca.
     *
     * @return array{lebar: int, tinggi: int}|null
     */
    private function ukuranGambar(string $jalur): ?array
    {
        $penuh  = $this->unggahan->jalurPenuh($jalur);
        $ukuran = $penuh === null ? false : @getimagesize($penuh);

        if ($ukuran === false || $ukuran[0] < 1 || $ukuran[1] < 1) {
            return null;
        }

        return ['lebar' => $ukuran[0], 'tinggi' => $ukuran[1]];
    }

    /**
     * Nilai sebagai teks tanpa spasi di ujung; `null` menjadi `''`.
     */
    private static function teks(mixed $nilai): string
    {
        return is_scalar($nilai) ? trim((string) $nilai) : '';
    }

    /**
     * Teks banyak baris: akhir baris dibakukan `\n`, spasi di ujung tiap baris
     * dan di ujung teks dibuang.
     */
    private static function rapikanBaris(mixed $nilai): string
    {
        $baris = preg_split('/\R/u', self::teks($nilai)) ?: [];

        return trim(implode("\n", array_map(rtrim(...), $baris)));
    }

    /**
     * Teks dipecah menjadi paragraf pada baris kosong. Baris baru di dalam
     * paragraf tetap `\n` (tampilan menjadikannya `<br>`).
     *
     * @return list<string>
     */
    private static function paragraf(mixed $nilai): array
    {
        $teks = self::rapikanBaris($nilai);

        if ($teks === '') {
            return [];
        }

        $bagian = preg_split('/\n[ \t]*\n/u', $teks) ?: [];

        return array_values(array_filter(
            array_map(static fn (string $p): string => trim($p), $bagian),
            static fn (string $p): bool => $p !== '',
        ));
    }

    /**
     * Bilangan bulat dari kolom angka, atau `null` bila kosong.
     */
    private static function angka(mixed $nilai): ?int
    {
        return $nilai === null || $nilai === '' ? null : (int) $nilai;
    }

    /**
     * Alamat web `http`/`https` yang sudah dirapikan, atau `null` bila kosong atau tidak sah.
     */
    private static function alamatWeb(mixed $nilai): ?string
    {
        $teks = self::teks($nilai);

        return $teks === '' ? null : TautanModel::rapikanAlamatWeb($teks);
    }

    /**
     * Apakah pesan sudah memuat alamat halaman depan (dengan atau tanpa
     * `http(s)://`, `www.`, dan garis miring di akhir).
     */
    private static function memuatAlamat(string $pesan, string $alamat): bool
    {
        $ringkas = rtrim(TautanModel::alamatRingkas($alamat), '/');

        return $ringkas !== '' && stripos($pesan, $ringkas) !== false;
    }
}
