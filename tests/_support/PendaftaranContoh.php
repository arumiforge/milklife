<?php

namespace Tests\Support;

use App\Libraries\Pendaftaran\FotoPenanggungJawab;

/**
 * Data contoh untuk uji pendaftaran (Tahap 4). Semua nama, NISN, NPSN, dan
 * nomor WA karangan; foto dibuat GD di uji, bukan foto orang.
 *
 * - `indukContoh()` dan `kirimanContoh()`: untuk uji unit `PemeriksaPendaftaran`.
 * - `isiDataIndukPendaftaran()`: mengisi basis data uji (kelompok umur,
 *   jenjang, nomor lomba, sekolah referensi, pengaturan) untuk uji basis
 *   data; hasilnya peta nama → id.
 * - `folderFotoUji()`: folder foto sementara pengganti `writable/unggahan/`.
 */
trait PendaftaranContoh
{
    /**
     * Folder dasar foto selama uji (diisi `pakaiFolderFotoSementara()`).
     */
    protected string $folderFoto = '';

    /**
     * Data URL JPEG kecil buatan GD.
     */
    protected static function fotoContoh(int $lebar = 40, int $tinggi = 30): string
    {
        $gambar = imagecreatetruecolor($lebar, $tinggi);
        imagefill($gambar, 0, 0, (int) imagecolorallocate($gambar, 30, 80, 200));
        ob_start();
        imagejpeg($gambar, null, 80);
        $isi = (string) ob_get_clean();
        imagedestroy($gambar);

        return 'data:image/jpeg;base64,' . base64_encode($isi);
    }

    /**
     * Data induk karangan (bentuk `DataIndukPendaftaran::induk()`).
     *
     * - KU 10: lahir 2016–2018, SD/MI, Rp150.000; nomor 10 Lari 60 m putra,
     *   11 Lari 60 m putri, 12 Frog Jump putra, 13 Turbo Throw putra,
     *   20 Kanga's Escape putra (beregu, inti 4, batas 6, Rp100.000),
     *   21 Estafet 8 × 50 m mix (inti 8, batas 10, 4 putra 4 putri, Rp150.000).
     * - KU 12: lahir 2015, SD/MI dan SMP/MTs, Rp175.000; nomor 30 Lari 60 m putra.
     * - KU 15: lahir 2011–2014, SMP/MTs, Rp200.000; nomor 40 Lari 80 m putri.
     *
     * @return array<string, mixed>
     */
    protected static function indukContoh(): array
    {
        $individu = static fn (int $id, int $ku, string $nama, string $kategori): array => [
            'id' => $id, 'kelompok_umur_id' => $ku, 'nama' => $nama, 'jenis' => 'individu', 'kategori' => $kategori,
            'jumlah_inti' => null, 'batas_anggota' => null, 'jumlah_putra' => null, 'jumlah_putri' => null, 'biaya_beregu' => null,
        ];

        return [
            'kelompok_umur' => [
                ['id' => 2, 'nama' => 'KU 10', 'tahun_lahir_awal' => 2016, 'tahun_lahir_akhir' => 2018, 'biaya_individu' => 150000, 'jenjang' => ['A']],
                ['id' => 3, 'nama' => 'KU 12', 'tahun_lahir_awal' => 2015, 'tahun_lahir_akhir' => 2015, 'biaya_individu' => 175000, 'jenjang' => ['A', 'B']],
                ['id' => 4, 'nama' => 'KU 15', 'tahun_lahir_awal' => 2011, 'tahun_lahir_akhir' => 2014, 'biaya_individu' => 200000, 'jenjang' => ['B']],
            ],
            'nomor_lomba' => [
                $individu(10, 2, 'Lari 60 m', 'putra'),
                $individu(11, 2, 'Lari 60 m', 'putri'),
                $individu(12, 2, 'Frog Jump', 'putra'),
                $individu(13, 2, 'Turbo Throw', 'putra'),
                [
                    'id' => 20, 'kelompok_umur_id' => 2, 'nama' => "Kanga's Escape", 'jenis' => 'beregu', 'kategori' => 'putra',
                    'jumlah_inti' => 4, 'batas_anggota' => 6, 'jumlah_putra' => null, 'jumlah_putri' => null, 'biaya_beregu' => 100000,
                ],
                [
                    'id' => 21, 'kelompok_umur_id' => 2, 'nama' => 'Estafet 8 × 50 m', 'jenis' => 'beregu', 'kategori' => 'mix',
                    'jumlah_inti' => 8, 'batas_anggota' => 10, 'jumlah_putra' => 4, 'jumlah_putri' => 4, 'biaya_beregu' => 150000,
                ],
                $individu(30, 3, 'Lari 60 m', 'putra'),
                $individu(40, 4, 'Lari 80 m', 'putri'),
            ],
            'aturan' => [
                'penanggung_jawab_minimal'  => 1,
                'penanggung_jawab_maksimal' => 5,
                'nomor_individu_maksimal'   => 2,
                'atlet_per_nomor_maksimal'  => 2,
            ],
        ];
    }

    /**
     * Atlet karangan.
     *
     * @param list<int> $nomor
     *
     * @return array<string, mixed>
     */
    protected static function atletContoh(string $nisn, array $nomor, string $jenisKelamin = 'L', string $tanggalLahir = '2017-04-12', string $nama = 'budi karangan'): array
    {
        return ['nama' => $nama . ' ' . substr($nisn, -2), 'nisn' => $nisn, 'tanggal_lahir' => $tanggalLahir, 'jenis_kelamin' => $jenisKelamin, 'nomor' => $nomor];
    }

    /**
     * Kiriman sah: SD tanpa NPSN di Kudus, satu penanggung jawab, dua atlet
     * KU 10 (satu dengan dua nomor individu, satu dengan satu).
     *
     * @return array<string, mixed>
     */
    protected static function kirimanContoh(): array
    {
        return [
            'sekolah' => ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '', 'nama' => 'SD Karangan  Maju'],
            'penanggung_jawab' => [
                ['nama' => 'Ibu Karangan', 'nomor_wa' => '0812-0000-0001', 'foto' => self::fotoContoh()],
            ],
            'atlet' => [
                self::atletContoh('9900000001', [10, 12]),
                self::atletContoh('9900000002', [11], 'P', '2016-08-30', 'siti karangan'),
            ],
        ];
    }

    /**
     * Ganti folder foto ke folder sementara. Panggil di `setUp()`; folder
     * dihapus `hapusFolderFotoSementara()` di `tearDown()`.
     */
    protected function pakaiFolderFotoSementara(): void
    {
        $this->folderFoto = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'milklife-foto-' . bin2hex(random_bytes(6)) . DIRECTORY_SEPARATOR;
        mkdir($this->folderFoto, 0777, true);
        FotoPenanggungJawab::gantiFolderDasar($this->folderFoto);
    }

    protected function hapusFolderFotoSementara(): void
    {
        FotoPenanggungJawab::gantiFolderDasar(null);

        if ($this->folderFoto !== '' && is_dir($this->folderFoto)) {
            helper('filesystem');
            delete_files($this->folderFoto, true);
            rmdir($this->folderFoto);
        }
    }

    /**
     * Berkas foto yang ada di folder sementara.
     *
     * @return list<string>
     */
    protected function berkasFoto(): array
    {
        $daftar = glob($this->folderFoto . FotoPenanggungJawab::FOLDER . DIRECTORY_SEPARATOR . '*.jpg') ?: [];
        sort($daftar);

        return $daftar;
    }

    /**
     * Isi basis data uji dengan data induk yang sama bentuknya dengan
     * `indukContoh()` (id dari basis data), satu sekolah referensi SD
     * (NPSN 99999911), satu SLB (NPSN 99999912), dan pengaturan acara.
     *
     * @return array<string, int> kunci: `ku10`, `ku12`, `ku15`, `lari60putra`, `lari60putri`,
     *                            `frog`, `turbo`, `kanga`, `estafet`, `lari60putraKu12`, `lari80putri`
     */
    protected function isiDataIndukPendaftaran(): array
    {
        $ku = function (string $nama, int $awal, int $akhir, int $biaya, array $jenjang, int $urutan): int {
            $this->db->table('kelompok_umur')->insert([
                'nama' => $nama, 'tahun_lahir_awal' => $awal, 'tahun_lahir_akhir' => $akhir,
                'biaya_individu' => $biaya, 'urutan' => $urutan, 'aktif' => 1,
            ]);
            $id = (int) $this->db->insertID();

            foreach ($jenjang as $kode) {
                $this->db->table('kelompok_umur_jenjang')->insert(['kelompok_umur_id' => $id, 'jenjang' => $kode]);
            }

            return $id;
        };

        $nomor = function (int $kuId, string $nama, string $jenis, string $kategori, array $tim = []): int {
            $this->db->table('nomor_lomba')->insert($tim + [
                'kelompok_umur_id' => $kuId, 'nama' => $nama, 'jenis' => $jenis, 'kategori' => $kategori, 'aktif' => 1,
            ]);

            return (int) $this->db->insertID();
        };

        $id         = [];
        $id['ku10'] = $ku('KU 10', 2016, 2018, 150000, ['A'], 1);
        $id['ku12'] = $ku('KU 12', 2015, 2015, 175000, ['A', 'B'], 2);
        $id['ku15'] = $ku('KU 15', 2011, 2014, 200000, ['B'], 3);

        $id['lari60putra']     = $nomor($id['ku10'], 'Lari 60 m', 'individu', 'putra');
        $id['lari60putri']     = $nomor($id['ku10'], 'Lari 60 m', 'individu', 'putri');
        $id['frog']            = $nomor($id['ku10'], 'Frog Jump', 'individu', 'putra');
        $id['turbo']           = $nomor($id['ku10'], 'Turbo Throw', 'individu', 'putra');
        $id['kanga']           = $nomor($id['ku10'], "Kanga's Escape", 'beregu', 'putra', ['jumlah_inti' => 4, 'batas_anggota' => 6, 'biaya_beregu' => 100000]);
        $id['estafet']         = $nomor($id['ku10'], 'Estafet 8 × 50 m', 'beregu', 'mix', ['jumlah_inti' => 8, 'batas_anggota' => 10, 'jumlah_putra' => 4, 'jumlah_putri' => 4, 'biaya_beregu' => 150000]);
        $id['lari60putraKu12'] = $nomor($id['ku12'], 'Lari 60 m', 'individu', 'putra');
        $id['lari80putri']     = $nomor($id['ku15'], 'Lari 80 m', 'individu', 'putri');

        $this->db->table('sekolah')->insertBatch([
            ['npsn' => '99999911', 'nama' => 'SD KARANGAN MAJU', 'bentuk' => 'SD', 'jenjang' => 'SD', 'status' => 'NEGERI', 'kabupaten' => 'Kudus'],
            ['npsn' => '99999912', 'nama' => 'SLB KARANGAN', 'bentuk' => 'SLB', 'jenjang' => 'SMA', 'status' => 'SWASTA', 'kabupaten' => 'Pati'],
            ['npsn' => '99999913', 'nama' => 'SMP KARANGAN JAYA', 'bentuk' => 'SMP', 'jenjang' => 'SMP', 'status' => 'NEGERI', 'kabupaten' => 'Jepara'],
        ]);

        setting('Acara.nama', 'MilkLife Athletics Challenge');
        setting('Acara.seri', 2);
        setting('Acara.tahun', 2026);
        setting('Acara.tanggal_mulai', '2026-12-09');
        setting('Acara.tanggal_selesai', '2026-12-12');

        return $id;
    }

    /**
     * Lengkapi pengaturan supaya pendaftaran terbuka (rancangan 6.1): data
     * induk `isiDataIndukPendaftaran()`, awalan nomor dada, rekening karangan,
     * dan jadwal pendaftaran yang sedang berjalan.
     *
     * @return array<string, int> Hasil `isiDataIndukPendaftaran()`.
     */
    protected function bukaPendaftaranUji(): array
    {
        $id = $this->isiDataIndukPendaftaran();

        foreach (['ku10' => 2, 'ku12' => 3, 'ku15' => 4] as $kunci => $awalan) {
            $this->db->table('awalan_nomor_dada')->insert(['kelompok_umur_id' => $id[$kunci], 'awalan' => $awalan, 'urutan' => 1]);
        }

        setting('Acara.tempat', 'Stadion Karangan');
        setting('Pendaftaran.mulai', '2026-01-01 00:00:00');
        setting('Pendaftaran.akhir', '2099-12-31 23:59:00');
        setting('Pembayaran.bank', 'Bank Karangan');
        setting('Pembayaran.nomor_rekening', '1234567890');
        setting('Pembayaran.pemilik_rekening', 'Panitia Karangan');
        setting('Pembayaran.nomor_wa', '6281200000099');

        return $id;
    }

    /**
     * Ganti id nomor lomba `indukContoh()` dengan id di basis data.
     *
     * @param array<string, mixed> $kiriman
     * @param array<string, int>   $id      Hasil `isiDataIndukPendaftaran()`.
     *
     * @return array<string, mixed>
     */
    protected static function kirimanUntukBasisData(array $kiriman, array $id): array
    {
        $peta = [
            10 => $id['lari60putra'], 11 => $id['lari60putri'], 12 => $id['frog'], 13 => $id['turbo'],
            20 => $id['kanga'], 21 => $id['estafet'], 30 => $id['lari60putraKu12'], 40 => $id['lari80putri'],
        ];

        foreach ($kiriman['atlet'] as $i => $atlet) {
            $kiriman['atlet'][$i]['nomor'] = array_map(static fn (int $n): int => $peta[$n], $atlet['nomor']);
        }

        return $kiriman;
    }
}
