<?php

namespace App\Libraries;

use App\Controllers\Admin\Pengaturan\AcaraController;
use App\Libraries\Bantuan\IsiBantuan;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use App\Libraries\Pendaftaran\KelompokUmurAtlet;
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
 *             'pilihan'        => ['SD/MI lahir 2016 boleh memilih KU 10 atau KU 12'], // K134, [] bila tidak ada
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
 * Halaman depan baru (F9-03):
 *
 * - `carousel()`: foto carousel pembuka yang berkasnya ada,
 *   `[['alamat' => 'http://…/unggahan/halaman-depan/….webp', 'keterangan' => 'Atlet melempar lembing', 'ukuran' => ['lebar' => 1500, 'tinggi' => 1000]]]`.
 * - `angkaAcara()`: `['atlet' => 2270, 'sekolah' => 196, 'kabupaten' => 4, 'kelompok_umur' => [['nama' => 'KU 8', 'atlet' => 364], …]]`
 *   dari pendaftaran Lunas, atau `null` bila belum ada.
 * - `hasilTerbaru()`: nomor yang paling akhir terbit beserta juaranya dan
 *   juara umum I–III per jenjang, atau `null` bila belum ada yang terbit.
 * - `faqTeratas()`: tanya-jawab pertama tiap kategori Bantuan (F9-04).
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
     * Banyak nomor yang paling akhir diterbitkan di bagian hasil halaman depan (F9-03).
     */
    public const JUMLAH_HASIL_TERBARU = 4;

    /**
     * Banyak tanya-jawab di bagian FAQ halaman depan (F9-03).
     */
    public const JUMLAH_FAQ_TERATAS = 6;

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
     * Logo bawaan: logo resmi MilkLife Athletics Challenge dari pemilik (K152),
     * dipakai di kepala halaman bila admin belum mengunggah logo sendiri.
     */
    public const LOGO_BAWAAN = 'logo.webp';

    /**
     * Logo untuk kepala halaman publik, panel admin, dan halaman masuk (K152):
     * logo unggahan admin (`logoKepala()`) bila ada, selain itu logo bawaan
     * `public/aset/gambar/logo.webp`, atau `null` bila keduanya tidak ada.
     *
     * @param array<string, mixed> $tampilan hasil `tampilan()`, atau `[]` bila basis data belum siap
     *
     * @return array{alamat: string, ukuran: array{lebar: int, tinggi: int}|null}|null
     */
    public static function logoMerek(array $tampilan): ?array
    {
        $kunci = self::logoKepala($tampilan);

        if ($kunci !== null) {
            return ['alamat' => (string) $tampilan[$kunci], 'ukuran' => $tampilan['ukuran'][$kunci] ?? null];
        }

        $alamat = GambarOpsional::alamat(self::LOGO_BAWAAN);

        if ($alamat === null) {
            return null;
        }

        $ukuran = GambarOpsional::ukuran(self::LOGO_BAWAAN);

        return ['alamat' => $alamat, 'ukuran' => $ukuran === null ? null : ['lebar' => $ukuran[0], 'tinggi' => $ukuran[1]]];
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
        $aktif = array_values(array_filter($daftarKu, static fn (array $ku): bool => (int) $ku['aktif'] === 1));

        // Tahun lahir yang masuk dua kelompok umur dalam satu jenjang (K134).
        $pilihan = [];

        foreach (KelompokUmurAtlet::irisan($aktif, KelompokUmurModel::tahunLahirTerbesar()) as $baris) {
            $awal  = $baris['tahun'][0];
            $akhir = $baris['tahun'][count($baris['tahun']) - 1];
            $teks  = (Jenjang::label($baris['jenjang']) ?? $baris['jenjang']) . ' lahir ' . ($awal === $akhir ? $awal : "{$awal}–{$akhir}")
                . ' boleh memilih ' . KelompokUmurAtlet::gabungNama(array_map(static fn (string $n): array => ['nama' => $n], $baris['kelompok_umur']));

            foreach ($baris['kelompok_umur'] as $nama) {
                $pilihan[$nama][] = $teks;
            }
        }

        foreach ($aktif as $ku) {
            $id = (int) $ku['id'];

            $hasil[] = [
                'id'             => $id,
                'nama'           => self::teks($ku['nama']),
                'tahun_lahir'    => KelompokUmurModel::teksTahunLahir($ku['tahun_lahir_awal'] ?? null, $ku['tahun_lahir_akhir'] ?? null),
                'jenjang'        => Jenjang::gabungLabel($ku['jenjang'] ?? []),
                'pilihan'        => $pilihan[(string) $ku['nama']] ?? [],
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
    // Halaman depan baru (F9-03)
    // ---------------------------------------------------------------

    /**
     * Foto carousel pembuka yang berkasnya ada, berurutan seperti diatur admin.
     * `warna` = warna rata-rata foto (`#rrggbb`) untuk latar selama foto dimuat
     * (skeleton carousel, F9-17). `srcset` = salinan 640/960 piksel dan foto
     * asli (`VarianFoto`, F10-06), atau `null` bila salinan tidak bisa dibuat.
     *
     * @return list<array{alamat: string, keterangan: string, ukuran: array{lebar: int, tinggi: int}|null, warna: string|null, srcset: string|null}>
     */
    public function carousel(): array
    {
        return array_map(function (array $foto): array {
            $penuh = $this->unggahan->jalurPenuh($foto['berkas']);

            return [
                'alamat'     => base_url($foto['berkas']),
                'keterangan' => $foto['keterangan'],
                'ukuran'     => $this->ukuranGambar($foto['berkas']),
                'warna'      => $penuh === null ? null : WarnaRataRata::dari($penuh),
                'srcset'     => VarianFoto::srcset($foto['berkas'], $this->unggahan),
            ];
        }, CarouselHalamanDepan::daftar($this->unggahan));
    }

    /**
     * Angka acara dari pendaftaran Lunas (seperti poster "Kompetisi ini
     * diikuti"): jumlah atlet, sekolah (kontingen = NPSN, atau pendaftaran
     * bila tanpa NPSN), kabupaten/kota, dan atlet per kelompok umur.
     * `null` bila belum ada atlet dari pendaftaran Lunas.
     *
     * @return array{atlet: int, sekolah: int, kabupaten: int, kelompok_umur: list<array{nama: string, atlet: int}>}|null
     */
    public function angkaAcara(): ?array
    {
        $db = db_connect();

        $jumlah = $this->baca(static fn (): array => $db->table('pendaftaran p')
            ->select("COUNT(a.id) AS atlet, COUNT(DISTINCT COALESCE(NULLIF(p.npsn, ''), CONCAT('p', p.id))) AS sekolah, COUNT(DISTINCT NULLIF(p.kabupaten, '')) AS kabupaten", false)
            ->join('atlet a', 'a.pendaftaran_id = p.id')
            ->where('p.status', 'lunas')
            ->get()->getResultArray(), 'angka acara')[0] ?? null;

        if ($jumlah === null || (int) $jumlah['atlet'] === 0) {
            return null;
        }

        $perKu = $this->baca(static fn (): array => $db->table('atlet a')
            ->select('ku.nama, COUNT(a.id) AS atlet')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('p.status', 'lunas')
            ->groupBy('ku.id, ku.nama, ku.urutan')
            ->orderBy('ku.urutan')->orderBy('ku.id')
            ->get()->getResultArray(), 'angka acara per kelompok umur');

        return [
            'atlet'         => (int) $jumlah['atlet'],
            'sekolah'       => (int) $jumlah['sekolah'],
            'kabupaten'     => (int) $jumlah['kabupaten'],
            'kelompok_umur' => array_map(static fn (array $b): array => ['nama' => self::teks($b['nama']), 'atlet' => (int) $b['atlet']], $perKu),
        ];
    }

    /**
     * Hasil yang sudah terbit untuk halaman depan: `JUMLAH_HASIL_TERBARU`
     * nomor yang paling akhir diterbitkan beserta juara I–III Final-nya, dan
     * tiga besar juara umum per jenjang (hanya dari nomor terbit, sama dengan
     * `hasil/klasemen`). `null` bila belum ada nomor yang terbit.
     *
     * @return array{nomor: list<array{id: int, nama: string, kelompok_umur: string, beregu: bool, juara: list<array{peringkat: int, nama: string, sekolah: string}>}>, juara_umum: list<array{jenjang: string, label: string, baris: list<array<string, mixed>>}>, metode: string}|null
     */
    public function hasilTerbaru(): ?array
    {
        try {
            $hasil = new HasilLomba();

            if (! $hasil->adaTerbit()) {
                return null;
            }

            // Peringkat 1–8 untuk klasemen metode poin (F9-11); juara nomor tetap I–III.
            $peringkat  = $hasil->juara(true, Klasemen::PERINGKAT_POIN);
            $semuaJuara = array_values(array_filter($peringkat, static fn (array $j): bool => $j['peringkat'] <= 3));
            $terbaru    = db_connect()->table('nomor_lomba')->select('id')
                ->where('hasil_terbit_pada IS NOT NULL', null, false)
                ->orderBy('hasil_terbit_pada', 'DESC')->orderBy('id', 'DESC')
                ->limit(self::JUMLAH_HASIL_TERBARU)
                ->get()->getResultArray();
        } catch (DatabaseException $e) {
            log_message('error', 'Data halaman depan (hasil) tidak terbaca: {pesan}', ['pesan' => $e->getMessage()]);

            return null;
        }

        $nomor = [];

        foreach ($terbaru as $baris) {
            $n = $hasil->nomor((int) $baris['id']);

            if ($n === null) {
                continue;
            }

            $nomor[] = [
                'id'            => $n['id'],
                'nama'          => $n['nama'],
                'kelompok_umur' => $n['kelompok_umur'],
                'beregu'        => $n['jenis'] === 'beregu',
                'juara'         => array_values(array_map(
                    static fn (array $j): array => ['peringkat' => $j['peringkat'], 'nama' => $j['nama'], 'sekolah' => $j['sekolah']],
                    array_filter($semuaJuara, static fn (array $j): bool => $j['nomor_id'] === $n['id']),
                )),
            ];
        }

        $juaraUmum = [];

        foreach (Klasemen::hitung($peringkat, MetodeKlasemen::metode(), MetodeKlasemen::poin()) as $jenjang) {
            $juaraUmum[] = [
                'jenjang' => $jenjang['jenjang'],
                'label'   => $jenjang['label'],
                'baris'   => array_values(array_filter($jenjang['baris'], static fn (array $b): bool => $b['juara_umum'] !== null)),
            ];
        }

        return ['nomor' => $nomor, 'juara_umum' => $juaraUmum, 'metode' => MetodeKlasemen::metode()];
    }

    /**
     * Apakah bagian hasil dipindah tepat di bawah pembuka (hari lomba,
     * `HalamanDepan.hasil_di_atas`, bawaan mati, K143).
     */
    public function hasilDiAtas(): bool
    {
        return (bool) setting('HalamanDepan.hasil_di_atas');
    }

    /**
     * Tanya-jawab teratas untuk halaman depan: butir pertama tiap kategori
     * Bantuan (F9-04), paling banyak `JUMLAH_FAQ_TERATAS`. `jawaban` sudah
     * HTML aman dari `JawabanFaq`. `[]` bila belum ada.
     *
     * @return list<array{id: int, pertanyaan: string, jawaban: string, kategori: string}>
     */
    public function faqTeratas(): array
    {
        $hasil = [];

        foreach ((new IsiBantuan())->kategori() as $kategori) {
            $hasil[] = ['id' => $kategori['butir'][0]['id'], 'pertanyaan' => $kategori['butir'][0]['pertanyaan'], 'jawaban' => $kategori['butir'][0]['jawaban'], 'kategori' => $kategori['label']];

            if (count($hasil) >= self::JUMLAH_FAQ_TERATAS) {
                break;
            }
        }

        return $hasil;
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
