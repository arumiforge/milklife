<?php

use App\Libraries\BukaTutupPendaftaran;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\Unggahan;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\BarisUjiNomorLomba;
use Tests\Support\UjiBasisData;
use Tests\Support\UnggahanSementara;

/**
 * Pengumpul data halaman publik (F3-01): hanya data yang boleh dilihat
 * pengunjung, dalam bentuk larik yang disepakati (komentar kelas
 * `IsiHalamanDepan`). Nama, nomor, dan alamat karangan.
 *
 * @internal
 */
final class IsiHalamanDepanTest extends UjiBasisData
{
    use BarisUjiNomorLomba;
    use StreamFilterTrait;
    use UnggahanSementara;

    protected function tearDown(): void
    {
        Time::setTestNow();

        parent::tearDown();
    }

    // ---------- Acara ----------

    public function testAcaraKosongMemakaiTeksPengganti(): void
    {
        $this->assertSame(
            ['nama' => 'Pendaftaran Lomba', 'seri_tahun' => '', 'tempat' => '', 'tanggal' => ''],
            (new IsiHalamanDepan())->acara(),
        );
    }

    public function testAcaraTerisi(): void
    {
        setting('Acara.nama', '  Lomba Lari Karangan ');
        setting('Acara.seri', 2);
        setting('Acara.tahun', 2026);
        setting('Acara.tempat', ' Stadion Karangan, Pati ');
        setting('Acara.tanggal_mulai', '2026-12-09');
        setting('Acara.tanggal_selesai', '2026-12-12');

        $this->assertSame([
            'nama'       => 'Lomba Lari Karangan',
            'seri_tahun' => 'Seri 2 – 2026',
            'tempat'     => 'Stadion Karangan, Pati',
            'tanggal'    => '9–12 Desember 2026',
        ], (new IsiHalamanDepan())->acara());

        setting('Acara.seri', null);
        $this->assertSame('2026', (new IsiHalamanDepan())->acara()['seri_tahun']);
    }

    // ---------- Tampilan ----------

    public function testTampilanKosong(): void
    {
        $this->assertSame([
            'judul'                => '',
            'teks'                 => [],
            'footer'               => 'Pendaftaran Lomba',
            'gambar_kepala'        => null,
            'logo_persegi'         => null,
            'logo_persegi_panjang' => null,
            'logo_djarum'          => null,
            'ukuran'               => ['gambar_kepala' => null, 'logo_persegi' => null, 'logo_persegi_panjang' => null, 'logo_djarum' => null],
        ], (new IsiHalamanDepan())->tampilan());
    }

    public function testTeksDipecahMenjadiParagraf(): void
    {
        setting('HalamanDepan.judul', '  Ayo ikut!  ');
        setting('HalamanDepan.teks', "  Paragraf satu  \r\nbaris kedua  \r\n\r\n \r\n\r\nParagraf dua\n \nParagraf tiga  ");

        $tampilan = (new IsiHalamanDepan())->tampilan();

        $this->assertSame('Ayo ikut!', $tampilan['judul']);
        $this->assertSame(["Paragraf satu\nbaris kedua", 'Paragraf dua', 'Paragraf tiga'], $tampilan['teks']);
    }

    public function testKakiDariPengaturanAtauCadangan(): void
    {
        setting('Acara.nama', 'Lomba Lari Karangan');
        $this->assertSame('Lomba Lari Karangan', IsiHalamanDepan::kaki());

        setting('Acara.tahun', 2027);
        $this->assertSame('Lomba Lari Karangan 2027', IsiHalamanDepan::kaki());
        $this->assertSame('Lomba Lari Karangan 2027', (new IsiHalamanDepan())->tampilan()['footer']);

        setting('HalamanDepan.footer', "  Panitia Karangan  \r\nKudus  ");
        $this->assertSame("Panitia Karangan\nKudus", IsiHalamanDepan::kaki());
        $this->assertSame("Panitia Karangan\nKudus", (new IsiHalamanDepan())->tampilan()['footer']);
    }

    public function testGambarHanyaYangBerkasnyaAda(): void
    {
        $kepala = $this->simpanGambarUji(self::isiPng());
        $logo   = $this->simpanGambarUji(self::isiPng());

        setting('HalamanDepan.gambar_kepala', $kepala);
        setting('HalamanDepan.logo_persegi', $logo);
        // Berkas sudah dihapus dari disk, dan jalur yang bentuknya tidak sah.
        setting('HalamanDepan.logo_persegi_panjang', 'unggahan/halaman-depan/' . str_repeat('a', 32) . '.png');
        setting('HalamanDepan.logo_djarum', '../app/Config/App.php');

        $tampilan = (new IsiHalamanDepan())->tampilan();

        $this->assertSame(base_url($kepala), $tampilan['gambar_kepala']);
        $this->assertSame(base_url($logo), $tampilan['logo_persegi']);
        $this->assertNull($tampilan['logo_persegi_panjang']);
        $this->assertNull($tampilan['logo_djarum']);
        $this->assertSame([
            'gambar_kepala'        => ['lebar' => 1, 'tinggi' => 1],
            'logo_persegi'         => ['lebar' => 1, 'tinggi' => 1],
            'logo_persegi_panjang' => null,
            'logo_djarum'          => null,
        ], $tampilan['ukuran']);
    }

    // ---------- Keadaan pendaftaran dan tombol ----------

    public function testKeadaanPendaftaranMengikutiWaktu(): void
    {
        Time::setTestNow('2026-11-11 10:00:00', 'Asia/Jakarta');
        $this->assertSame(BukaTutupPendaftaran::DISIAPKAN, (new IsiHalamanDepan())->bukaTutup()->keadaan());

        $this->lengkapiPengaturan();

        $this->assertSame(BukaTutupPendaftaran::TERBUKA, (new IsiHalamanDepan())->bukaTutup()->keadaan());

        Time::setTestNow('2026-09-30 12:00:00', 'Asia/Jakarta');
        $this->assertSame(BukaTutupPendaftaran::BELUM_DIBUKA, (new IsiHalamanDepan())->bukaTutup()->keadaan());

        Time::setTestNow('2026-12-01 00:00:00', 'Asia/Jakarta');
        $isi = new IsiHalamanDepan();
        $this->assertSame(BukaTutupPendaftaran::DITUTUP, $isi->bukaTutup()->keadaan());
        $this->assertSame($isi->bukaTutup(), $isi->bukaTutup(), 'Keadaan dibaca sekali per objek.');
    }

    public function testTombolMengikutiKeadaanPendaftaran(): void
    {
        // Pengaturan belum lengkap: keduanya nonaktif, Pendaftaran "Segera".
        $this->assertSame([
            ['kunci' => 'pendaftaran', 'label' => 'Pendaftaran', 'alamat' => null, 'utama' => true, 'keterangan' => 'Segera'],
            ['kunci' => 'cek', 'label' => 'Cek Pendaftaran', 'alamat' => null, 'utama' => false],
        ], (new IsiHalamanDepan())->tombol());

        // Terbuka: Pendaftaran menaut ke wizard (Tahap 4), Cek ke halaman cek (Tahap 5).
        $this->lengkapiPengaturan();
        $this->assertSame([
            ['kunci' => 'pendaftaran', 'label' => 'Pendaftaran', 'alamat' => 'pendaftaran', 'utama' => true, 'keterangan' => null],
            ['kunci' => 'cek', 'label' => 'Cek Pendaftaran', 'alamat' => 'cek-pendaftaran', 'utama' => false],
        ], (new IsiHalamanDepan())->tombol());

        // Belum dibuka dan ditutup: Pendaftaran nonaktif dengan keterangannya, Cek tetap aktif.
        foreach (['2026-09-30 12:00:00' => 'Belum dibuka', '2026-12-01 00:00:00' => 'Ditutup'] as $waktu => $keterangan) {
            Time::setTestNow($waktu, 'Asia/Jakarta');
            $tombol = (new IsiHalamanDepan())->tombol();

            $this->assertNull($tombol[0]['alamat'], $keterangan);
            $this->assertSame($keterangan, $tombol[0]['keterangan']);
            $this->assertSame('cek-pendaftaran', $tombol[1]['alamat']);
        }
    }

    // ---------- Kelompok umur dan nomor lomba ----------

    public function testHanyaKelompokUmurDanNomorAktif(): void
    {
        $ku10 = $this->buatKelompokUmur('KU 10', ['tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'biaya_individu' => 150000, 'urutan' => 2]);
        $ku8  = $this->buatKelompokUmur('KU 8', ['tahun_lahir_awal' => 2019, 'biaya_individu' => 0, 'urutan' => 1]);
        $mati = $this->buatKelompokUmur('KU Mati', ['aktif' => 0, 'urutan' => 3]);
        $ku18 = $this->buatKelompokUmur('  KU 18 ', ['urutan' => 4]);

        $this->db->table('kelompok_umur_jenjang')->insertBatch([
            ['kelompok_umur_id' => $ku10, 'jenjang' => 'A'],
            ['kelompok_umur_id' => $ku8, 'jenjang' => 'B'],
            ['kelompok_umur_id' => $ku8, 'jenjang' => 'A'],
        ]);

        $lari   = $this->buatNomorLomba($ku10, ['nama' => 'Lari 60 m', 'kategori' => 'putri', 'urutan' => 2]);
        $lompat = $this->buatNomorLomba($ku10, ['nama' => 'Frog Jump', 'kategori' => 'putra', 'urutan' => 1]);
        $this->buatNomorLomba($ku10, ['nama' => 'Nomor Mati', 'aktif' => 0]);
        $this->buatNomorLomba($mati, ['nama' => 'Nomor di KU Mati']);
        $mix = $this->buatNomorLomba($ku10, [
            'nama' => 'Estafet 4 × 50 m', 'jenis' => 'beregu', 'kategori' => 'mix', 'urutan' => 3,
            'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2, 'jumlah_putri' => 2, 'biaya_beregu' => 200000,
        ]);
        // Nomor beregu putra dari data awal yang belum lengkap; jumlah putra/putri tidak berlaku.
        $beregu = $this->buatNomorLomba($ku10, [
            'nama' => 'Estafet 4 × 100 m', 'jenis' => 'beregu', 'kategori' => 'putra', 'urutan' => 4,
            'jumlah_inti' => 4, 'jumlah_putra' => 4,
        ]);

        $hasil = (new IsiHalamanDepan())->kelompokUmur();

        $this->assertSame([
            [
                'id'             => $ku8,
                'nama'           => 'KU 8',
                'tahun_lahir'    => 'Lahir 2019 ke atas',
                'jenjang'        => 'SD/MI, SMP/MTs',
                'biaya_individu' => 0,
                'nomor_individu' => [],
                'nomor_beregu'   => [],
            ],
            [
                'id'             => $ku10,
                'nama'           => 'KU 10',
                'tahun_lahir'    => 'Lahir 2016–2018',
                'jenjang'        => 'SD/MI',
                'biaya_individu' => 150000,
                'nomor_individu' => [
                    ['id' => $lompat, 'nama' => 'Frog Jump', 'kategori' => 'putra', 'kategori_label' => 'Putra'],
                    ['id' => $lari, 'nama' => 'Lari 60 m', 'kategori' => 'putri', 'kategori_label' => 'Putri'],
                ],
                'nomor_beregu' => [
                    [
                        'id' => $mix, 'nama' => 'Estafet 4 × 50 m', 'kategori' => 'mix', 'kategori_label' => 'Mix',
                        'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => 2, 'jumlah_putri' => 2, 'biaya_beregu' => 200000,
                    ],
                    [
                        'id' => $beregu, 'nama' => 'Estafet 4 × 100 m', 'kategori' => 'putra', 'kategori_label' => 'Putra',
                        'jumlah_inti' => 4, 'batas_anggota' => null, 'jumlah_putra' => null, 'jumlah_putri' => null, 'biaya_beregu' => null,
                    ],
                ],
            ],
            [
                'id'             => $ku18,
                'nama'           => 'KU 18',
                'tahun_lahir'    => '',
                'jenjang'        => '',
                'biaya_individu' => null,
                'nomor_individu' => [],
                'nomor_beregu'   => [],
            ],
        ], $hasil);
    }

    public function testDataAwalLimaKelompokUmur(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));

        $hasil = (new IsiHalamanDepan())->kelompokUmur();

        $this->assertSame(['KU 8 (Seneng Playon)', 'KU 10', 'KU 12', 'KU 15', 'KU 18'], array_column($hasil, 'nama'));
        $this->assertSame(69, array_sum(array_map(static fn (array $ku): int => count($ku['nomor_individu']) + count($ku['nomor_beregu']), $hasil)));
    }

    // ---------- Jadwal dan unduhan ----------

    public function testJadwalHanyaYangTampil(): void
    {
        $this->db->table('jadwal')->insertBatch([
            ['judul' => 'Technical meeting', 'waktu' => '2026-12-05 09:00:00', 'tempat' => ' Aula Karangan ', 'tautan' => null, 'keterangan' => null, 'urutan' => 2, 'tampil' => 1],
            ['judul' => ' Sosialisasi daring ', 'waktu' => null, 'tempat' => null, 'tautan' => 'https://meet.contoh.test/abc', 'keterangan' => "Bawa data siswa.  \r\nGratis.", 'urutan' => 1, 'tampil' => 1],
            ['judul' => 'Rapat tersembunyi', 'waktu' => null, 'tempat' => null, 'tautan' => null, 'keterangan' => null, 'urutan' => 3, 'tampil' => 0],
            ['judul' => 'Tautan tidak sah', 'waktu' => null, 'tempat' => null, 'tautan' => 'javascript:alert(1)', 'keterangan' => null, 'urutan' => 4, 'tampil' => 1],
        ]);

        $hasil = (new IsiHalamanDepan())->jadwal();

        $this->assertSame(['Sosialisasi daring', 'Technical meeting', 'Tautan tidak sah'], array_column($hasil, 'judul'));
        $this->assertSame(
            ['judul' => 'Sosialisasi daring', 'keterangan' => "Bawa data siswa.\nGratis.", 'waktu' => null, 'tempat' => '', 'tautan' => 'https://meet.contoh.test/abc'],
            array_diff_key($hasil[0], ['id' => true]),
        );
        $this->assertSame(
            ['judul' => 'Technical meeting', 'keterangan' => '', 'waktu' => '2026-12-05 09:00:00', 'tempat' => 'Aula Karangan', 'tautan' => ''],
            array_diff_key($hasil[1], ['id' => true]),
        );
        $this->assertSame('', $hasil[2]['tautan']);
        $this->assertIsInt($hasil[0]['id']);
    }

    public function testUnduhanHanyaYangTampilDanBerkasnyaAda(): void
    {
        $flyer = $this->simpanBerkasUji('unduhan', 'pdf', self::isiPdf());
        $juknis = $this->simpanBerkasUji('unduhan', 'pdf', str_repeat('x', 1536));
        $sembunyi = $this->simpanBerkasUji('unduhan', 'pdf', self::isiPdf());

        $this->db->table('unduhan')->insertBatch([
            ['judul' => 'Juknis', 'keterangan' => null, 'berkas' => $juknis, 'urutan' => 2, 'tampil' => 1],
            ['judul' => ' Flyer ', 'keterangan' => ' Flyer Seri 2 ', 'berkas' => $flyer, 'urutan' => 1, 'tampil' => 1],
            ['judul' => 'Disembunyikan', 'keterangan' => null, 'berkas' => $sembunyi, 'urutan' => 3, 'tampil' => 0],
            ['judul' => 'Berkas hilang', 'keterangan' => null, 'berkas' => 'unggahan/unduhan/' . str_repeat('b', 32) . '.pdf', 'urutan' => 4, 'tampil' => 1],
        ]);

        $hasil = (new IsiHalamanDepan())->unduhan();

        $this->assertCount(2, $hasil);
        $this->assertSame(
            ['judul' => 'Flyer', 'keterangan' => 'Flyer Seri 2', 'alamat' => base_url($flyer), 'jenis' => 'PDF', 'ukuran' => Unggahan::ukuranTeks(strlen(self::isiPdf()))],
            array_diff_key($hasil[0], ['id' => true]),
        );
        $this->assertSame(['Juknis', '', base_url($juknis), 'PDF', '2 KB'], [$hasil[1]['judul'], $hasil[1]['keterangan'], $hasil[1]['alamat'], $hasil[1]['jenis'], $hasil[1]['ukuran']]);
    }

    // ---------- Kontak dan WA ----------

    public function testNarahubungDanMediaSosial(): void
    {
        $this->db->table('tautan')->insertBatch([
            ['jenis' => 'narahubung', 'label' => ' Ibu Karangan ', 'nilai' => '6281234567890', 'urutan' => 2],
            ['jenis' => 'narahubung', 'label' => 'Pak Contoh', 'nilai' => '6285711112222', 'urutan' => 1],
            ['jenis' => 'narahubung', 'label' => 'Nomor rusak', 'nilai' => 'bukan nomor', 'urutan' => 3],
            ['jenis' => 'media_sosial', 'label' => 'Instagram', 'nilai' => 'https://www.instagram.com/contoh.karangan', 'urutan' => 1],
            ['jenis' => 'media_sosial', 'label' => 'Situs', 'nilai' => 'https://contoh.test/', 'urutan' => 2],
            ['jenis' => 'media_sosial', 'label' => 'Tidak sah', 'nilai' => 'javascript:alert(1)', 'urutan' => 3],
        ]);

        $isi = new IsiHalamanDepan();

        $this->assertSame([
            ['nama' => 'Pak Contoh', 'nomor' => '0857-1111-2222', 'alamat' => 'https://wa.me/6285711112222'],
            ['nama' => 'Ibu Karangan', 'nomor' => '0812-3456-7890', 'alamat' => 'https://wa.me/6281234567890'],
        ], $isi->narahubung());

        $this->assertSame([
            ['label' => 'Instagram', 'alamat' => 'https://www.instagram.com/contoh.karangan', 'alamat_ringkas' => 'instagram.com/contoh.karangan', 'ikon' => 'instagram'],
            ['label' => 'Situs', 'alamat' => 'https://contoh.test/', 'alamat_ringkas' => 'contoh.test', 'ikon' => 'globe2'],
        ], $isi->mediaSosial());
    }

    public function testWaTanya(): void
    {
        $this->assertNull((new IsiHalamanDepan())->waTanya(), 'Nomor belum diisi.');

        setting('HalamanDepan.nomor_wa_tanya', '6281234567890');
        $this->assertSame('https://wa.me/6281234567890', (new IsiHalamanDepan())->waTanya());

        setting('HalamanDepan.pesan_tanya', "  Halo admin, saya mau tanya & daftar.\r\nTerima kasih  ");
        $this->assertSame(
            'https://wa.me/6281234567890?text=' . rawurlencode("Halo admin, saya mau tanya & daftar.\nTerima kasih"),
            (new IsiHalamanDepan())->waTanya(),
        );
    }

    public function testWaBagikan(): void
    {
        $alamat = site_url('/');

        // Pesan kosong: nama acara dan alamat halaman depan.
        setting('Acara.nama', 'Lomba Lari Karangan');
        $this->assertSame('https://wa.me/?text=' . rawurlencode("Lomba Lari Karangan\n" . $alamat), (new IsiHalamanDepan())->waBagikan());

        // Pesan admin tanpa alamat: alamat ditambahkan.
        setting('HalamanDepan.pesan_bagikan', "Ayo daftarkan siswa Anda!  \r\n");
        $this->assertSame('https://wa.me/?text=' . rawurlencode("Ayo daftarkan siswa Anda!\n" . $alamat), (new IsiHalamanDepan())->waBagikan());

        // Alamat sudah tertulis (tanpa http dan garis miring akhir): tidak ditambahkan lagi.
        $ringkas = rtrim((string) preg_replace('#^https?://(www\.)?#', '', $alamat), '/');
        setting('HalamanDepan.pesan_bagikan', 'Daftar di ' . $ringkas . ' sekarang');
        $this->assertSame('https://wa.me/?text=' . rawurlencode('Daftar di ' . $ringkas . ' sekarang'), (new IsiHalamanDepan())->waBagikan());
    }

    // ---------- Tabel belum ada ----------

    public function testTabelTakTerbacaDianggapKosong(): void
    {
        $this->db->query('RENAME TABLE jadwal TO jadwal_sementara');

        try {
            $this->assertSame([], (new IsiHalamanDepan())->jadwal());
        } finally {
            $this->db->query('RENAME TABLE jadwal_sementara TO jadwal');
        }
    }

    // ---------- Bantuan ----------

    /**
     * Simpan berkas langsung ke folder unggahan uji, seperti hasil `Unggahan`.
     */
    private function simpanBerkasUji(string $folder, string $ekstensi, string $isi): string
    {
        $jalur = 'unggahan/' . $folder . '/' . bin2hex(random_bytes(16)) . '.' . $ekstensi;
        $penuh = $this->jalurUnggahan($jalur);

        if (! is_dir(dirname($penuh))) {
            mkdir(dirname($penuh), 0777, true);
        }

        file_put_contents($penuh, $isi);

        return $jalur;
    }

    private function simpanGambarUji(string $isi): string
    {
        return $this->simpanBerkasUji('halaman-depan', 'png', $isi);
    }

    /**
     * Data awal ditambah semua isian wajib, seperti admin mengisi semua
     * halaman pengaturan (data karangan).
     */
    private function lengkapiPengaturan(): void
    {
        $this->assertSame(EXIT_SUCCESS, service('commands')->run('data:awal', []));

        $this->db->query('UPDATE kelompok_umur SET biaya_individu = 50000');
        $this->db->query("UPDATE nomor_lomba SET batas_anggota = jumlah_inti + 2, biaya_beregu = 100000 WHERE jenis = 'beregu'");
        $this->db->query("UPDATE nomor_lomba SET jumlah_putra = jumlah_inti DIV 2, jumlah_putri = jumlah_inti DIV 2 WHERE kategori = 'mix'");

        foreach ($this->db->table('kelompok_umur')->select('id')->get()->getResultArray() as $ku) {
            $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $ku['id'], 'jenjang' => 'A']);
        }

        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281234567890');
    }
}
