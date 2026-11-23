<?php

use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Setiap halaman admin punya jejak navigasi (F10-03) yang seragam (F11-10,
 * K174 butir 2: dimulai "Dasbor", tanpa ikon rumah, sejalan dengan pil
 * navigasi yang ditandai): semua rute admin `GET`
 * yang menampilkan halaman dibuka sebagai admin dengan data contoh
 * karangan, lalu diperiksa memakai tata letak `admin_tw` dan memuat satu
 * jejak (`admin/_jejak_tw`, `data-jejak`) yang diakhiri halaman itu sendiri.
 *
 * Rute baru otomatis ikut diperiksa. Rute yang bukan halaman (data JSON,
 * unduhan Excel/PDF/ZIP, gambar) dilewati menurut `BUKAN_HALAMAN`; rute
 * berparameter diisi id data contoh menurut nama bagian alamat sebelum
 * parameternya (`isiAlamat()`); parameter berpilihan tetap, misalnya
 * `(putra|putri)`, memakai pilihan pertama. Pola parameter yang belum dikenal membuat
 * uji gagal supaya contoh alamatnya ditambahkan.
 *
 * @internal
 */
final class JejakHalamanAdminTest extends UjiBasisData
{
    use FeatureTestTrait;
    use KirimFormulir;
    use PanelDataContoh;

    /**
     * Pola rute yang bukan halaman HTML bertata letak admin.
     */
    private const BUKAN_HALAMAN = [
        '#^admin$#',                                   // pengalihan ke dasbor
        '#^admin/masuk$#',                             // halaman masuk, tata letak polos
        '#/(data|excel|ekspor|pdf|zip|unduh)$#',       // data tabel dan unduhan
        '#\((pdf|xlsx)\|#',                            // unduhan PDF/Excel berjenis
        '#^admin/nomor-atlet/cetak/contoh$#',          // contoh BIB (PDF)
        '#^admin/pendaftaran/\(\[0-9\]\+\)/foto/#',    // foto penanggung jawab
        '#/bukti-bayar/\(\[0-9\]\+\)$#',               // berkas bukti bayar
    ];

    /**
     * Kueri yang wajib ada di alamat halaman tertentu.
     */
    private const KUERI = [
        'admin/pengaturan/kontak/tambah' => '?jenis=narahubung',
    ];

    /**
     * @var array<string, int|string>
     */
    private array $contoh = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
        $this->siapkanContoh();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    public function testSemuaHalamanAdminPunyaJejakNavigasi(): void
    {
        $rute    = service('routes')->loadRoutes()->getRoutes('GET');
        $dibuka  = [];

        foreach ($rute as $pola => $penangan) {
            if (! is_string($pola) || ! str_starts_with($pola . '/', 'admin/') || $this->bukanHalaman($pola)) {
                continue;
            }

            $alamat = $this->isiAlamat($pola) . (self::KUERI[$pola] ?? '');
            $hasil  = $this->bukaSebagaiAdmin($alamat);

            $this->assertSame(200, $hasil->response()->getStatusCode(), "{$pola} ({$alamat})");

            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $dom->loadHTML('<?xml encoding="utf-8">' . $hasil->getBody());
            libxml_clear_errors();
            $xpath = new DOMXPath($dom);

            $this->assertSame(1, $xpath->query('//body[@data-tata="tw"]')->length, "{$alamat} memakai tata letak admin_tw.");
            $this->assertSame(1, $xpath->query('//main[@id="isi"]//nav[@data-jejak][@aria-label="Jejak navigasi"]')->length, "{$alamat} punya tepat satu jejak navigasi.");
            $this->assertSame(1, $xpath->query('//nav[@data-jejak]//ol/li[last()]/*[@aria-current="page"]')->length, "{$alamat}: butir terakhir jejak adalah halaman ini.");

            // Jejak dimulai teks "Dasbor" tanpa ikon rumah dan tidak berulang (K174 butir 2).
            $label = array_map(fn (DOMNode $li): string => $this->teksTerbaca($xpath, $li), iterator_to_array($xpath->query('//nav[@data-jejak]//ol/li')));
            $jalur = implode(' › ', $label);
            $this->assertSame('Dasbor', $label[0] ?? null, "{$alamat}: jejak dimulai Dasbor ({$jalur}).");
            $this->assertSame(0, $xpath->query('//nav[@data-jejak]//*[name()="use"][contains(@href, "#house")]')->length, "{$alamat}: jejak tanpa ikon rumah.");
            $this->assertSame(count($label), count(array_unique($label)), "{$alamat}: butir jejak tidak berulang ({$jalur}).");

            // Jejak sejalan dengan pil navigasi (Pengaturan, Hari lomba, jenis Rekap): label pil yang ditandai ada di jejak.
            $pil = $xpath->query('//main//nav[@aria-label="Navigasi pengaturan" or @aria-label="Jenis rekap" or @data-tab-hari-lomba]//a[@aria-current="page"]')->item(0);
            if ($pil !== null) {
                $this->assertContains($this->teksTerbaca($xpath, $pil), $label, "{$alamat}: pil yang ditandai ada di jejak ({$jalur}).");
            }

            $dibuka[] = $pola;
        }

        // Pemeriksa dirinya sendiri: halaman utama dan halaman berparameter ikut dibuka.
        foreach ([
            'admin/dasbor', 'admin/pendaftaran/([0-9]+)', 'admin/pendaftaran/([0-9]+)/ubah', 'admin/pengaturan/acara',
            'admin/pengaturan/jadwal/([0-9]+)/ubah', 'admin/seri/babak/([0-9]+)', 'admin/sekolah/([0-9]+)/ubah', 'admin/gaya',
        ] as $wajib) {
            $this->assertContains($wajib, $dibuka);
        }
        $this->assertGreaterThan(50, count($dibuka));
    }

    /**
     * Teks simpul yang dibaca pembaca layar: tanpa bagian `aria-hidden`, spasi dirapikan.
     */
    private function teksTerbaca(DOMXPath $xpath, DOMNode $simpul): string
    {
        $teks = '';

        foreach ($xpath->query('.//text()[not(ancestor::*[@aria-hidden="true"])]', $simpul) as $satu) {
            $teks .= $satu->textContent . ' ';
        }

        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }

    private function bukanHalaman(string $pola): bool
    {
        foreach (self::BUKAN_HALAMAN as $pola2) {
            if (preg_match($pola2, $pola) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Ganti setiap parameter rute dengan contoh menurut bagian alamat sebelumnya.
     */
    private function isiAlamat(string $pola): string
    {
        $bagian = explode('/', $pola);
        $sebelum = '';

        foreach ($bagian as $i => $satu) {
            if (! str_starts_with($satu, '(')) {
                $sebelum = $satu;

                continue;
            }

            $kunci = $sebelum . ' ' . $satu;

            // Pilihan tetap, misalnya (nomor-lomba|keuangan): pakai pilihan pertama.
            if (! isset($this->contoh[$kunci]) && preg_match('#^\(([a-z0-9-]+)(\|[a-z0-9-]+)+\)$#', $satu, $cocok) === 1) {
                $bagian[$i] = $cocok[1];
                $sebelum    = $kunci;

                continue;
            }

            $this->assertArrayHasKey($kunci, $this->contoh, "Tambahkan contoh untuk parameter rute {$pola} ({$kunci}).");
            $bagian[$i] = (string) $this->contoh[$kunci];
            $sebelum    = $kunci;
        }

        return implode('/', $bagian);
    }

    /**
     * Data contoh karangan untuk halaman berparameter.
     */
    private function siapkanContoh(): void
    {
        $pendaftaran = $this->buatPendaftaranPanel();

        // Akun admin sesi uji (`admin_id` 1) supaya halaman Akun terbuka.
        $this->db->table('admin')->insert(['nama' => 'Admin Uji', 'nama_pengguna' => 'admin.uji', 'sandi' => password_hash('sandi-karangan-1', PASSWORD_DEFAULT)]);
        $this->assertSame(1, (int) $this->db->insertID());

        $this->db->table('sekolah')->insert(['npsn' => '99999971', 'nama' => 'SD KARANGAN JEJAK', 'jenjang' => 'A', 'kabupaten' => 'Kudus']);
        $sekolah = (int) $this->db->insertID();
        $this->db->table('jadwal')->insert(['judul' => 'Agenda karangan']);
        $jadwal = (int) $this->db->insertID();
        $this->db->table('tautan')->insert(['jenis' => 'narahubung', 'label' => 'Panitia karangan', 'nilai' => '081200000009']);
        $tautan = (int) $this->db->insertID();
        $this->db->table('unduhan')->insert(['judul' => 'Juknis karangan', 'berkas' => 'karangan.pdf']);
        $unduhan = (int) $this->db->insertID();
        $this->db->table('faq')->insert(['kategori' => 'umum', 'pertanyaan' => 'Pertanyaan karangan?', 'jawaban' => 'Jawaban karangan.']);
        $faq = (int) $this->db->insertID();
        $this->db->table('galeri')->insert(['judul' => 'Album karangan']);
        $galeri = (int) $this->db->insertID();
        $this->db->table('rekor')->insert(['nomor_lomba_id' => $this->idInduk['lari60putra'], 'kelompok_umur' => 'KU 10', 'nomor' => 'Lari 60 m putra', 'kategori' => 'putra', 'jenis_hasil' => 'waktu', 'cara_waktu' => 'elektronik', 'prestasi' => 950, 'atlet' => 'ATLET KARANGAN', 'sekolah' => 'SD KARANGAN', 'seri' => 'Seri 2']);
        $rekor = (int) $this->db->insertID();

        $nomor = $this->idInduk['lari60putra'];
        $this->db->table('babak')->insert(['nomor_lomba_id' => $nomor, 'nama' => 'Final', 'final' => 1, 'urutan' => 1]);
        $babak = (int) $this->db->insertID();

        $this->kirimSebagaiAdmin('admin/pengaturan/tabel-skor/pasang');
        $tabel    = $this->db->table('tabel_skor')->orderBy('id')->get()->getRowArray();
        $komponen = json_decode((string) $tabel['komponen'], true);
        $this->assertIsArray($komponen, 'Tabel skor bawaan terpasang.');

        $this->contoh = [
            'pendaftaran ([0-9]+)'                   => $pendaftaran['id'],
            'ganti-atlet ([0-9]+)'                   => $pendaftaran['atlet'][0],
            'sekolah ([0-9]+)'                       => $sekolah,
            'nomor ([0-9]+)'                         => $nomor,
            'babak ([0-9]+)'                         => $babak,
            'kelompok-umur ([0-9]+)'                 => $this->idInduk['ku10'],
            'nomor-lomba ([0-9]+)'                   => $nomor,
            'jadwal ([0-9]+)'                        => $jadwal,
            'kontak ([0-9]+)'                        => $tautan,
            'unduhan ([0-9]+)'                       => $unduhan,
            'bantuan ([0-9]+)'                       => $faq,
            'galeri ([0-9]+)'                        => $galeri,
            'rekor ([0-9]+)'                         => $rekor,
            'tabel-skor ([0-9]+)'                    => (int) $tabel['id'],
            'tabel-skor ([0-9]+) ([a-z0-9-]+)'       => (string) ($komponen[0]['kode'] ?? ''),
        ];
    }
}
