<?php

use App\Libraries\Panel\UbahPendaftaran;
use App\Libraries\Pendaftaran\DataIndukPendaftaran;
use App\Libraries\Pendaftaran\FotoPenanggungJawab;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Ubah pendaftaran dari panel (`App\Libraries\Panel\UbahPendaftaran`, F6-04;
 * rancangan 6.9 dan 9; K32, K57, K59; asumsi 4 rencana Tahap 6).
 *
 * Data induk dan biaya dari `PanelDataContoh`: KU 10 Rp 150.000 per nomor
 * individu, Kanga's Escape beregu putra Rp 100.000 (inti 4, batas 6), awalan
 * nomor dada KU 10 = 2, KU 12 = 3.
 *
 * @internal
 */
final class PanelUbahPendaftaranTest extends UjiBasisData
{
    use PanelDataContoh;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataPanel();
    }

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();
        parent::tearDown();
    }

    // ---------------------------------------------------------------
    // Pemeriksaan
    // ---------------------------------------------------------------

    public function testTanpaPerubahanTersimpanDenganNisnSendiri(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $this->kirimanDari($p['id']));

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame($p['total'], $hasil['total']);
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
    }

    public function testNisnPendaftaranLainDitolak(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);

        $nisnA   = (string) $this->db->table('atlet')->where('id', $a['atlet'][0])->get()->getRow()->nisn;
        $kiriman = $this->kirimanDari($b['id']);
        $kiriman['atlet'][0]['nisn'] = $nisnA;

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame(['atlet.0.nisn'], array_column($hasil['galat'], 'jalur'));
        $this->assertStringContainsString("NISN {$nisnA} sudah terdaftar", $hasil['galat'][0]['pesan']);
    }

    public function testNisnBertukarAntarAtletSendiriDiterima(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel(), $this->atletPanel()]]);
        $kiriman = $this->kirimanDari($p['id']);
        [$kiriman['atlet'][0]['nisn'], $kiriman['atlet'][1]['nisn']] = [$kiriman['atlet'][1]['nisn'], $kiriman['atlet'][0]['nisn']];

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame($kiriman['atlet'][0]['nisn'], (string) $this->db->table('atlet')->where('id', $p['atlet'][0])->get()->getRow()->nisn);
        $this->assertSame($kiriman['atlet'][1]['nisn'], (string) $this->db->table('atlet')->where('id', $p['atlet'][1])->get()->getRow()->nisn);
    }

    public function testIdBarisMilikPendaftaranLainDitolak(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN']]);

        $kiriman                       = $this->kirimanDari($b['id']);
        $kiriman['atlet'][0]['id']     = $a['atlet'][0];
        $kiriman['penanggung_jawab'][0]['id'] = $a['penanggung_jawab'][0];

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertContains('atlet.0', array_column($hasil['galat'], 'jalur'));
        $this->assertContains('penanggung_jawab.0', array_column($hasil['galat'], 'jalur'));
        $this->assertSame(1, $this->db->table('atlet')->where('id', $a['atlet'][0])->where('pendaftaran_id', $a['id'])->countAllResults());
    }

    public function testBukaTutupDanKonfirmasiSusulanTidakBerlakuUntukAdmin(): void
    {
        setting('Pendaftaran.akhir', '2026-01-02 00:00:00');   // pendaftaran sudah tutup

        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 4)]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 1), 'susulan' => ['kanga']]);

        $kiriman            = $this->kirimanDari($b['id']);
        $kiriman['atlet'][] = $this->atletBaru(['kanga']);

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['tersimpan']);
    }

    public function testBatasNomorIndividuDanPenanggungJawabTetapBerlaku(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][0]['nomor'] = [$this->idInduk['lari60putra'], $this->idInduk['frog'], $this->idInduk['turbo']];
        $kiriman['penanggung_jawab']  = [];

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);
        $jalur = array_column($hasil['galat'], 'jalur');

        $this->assertContains('atlet.0.nomor', $jalur);
        $this->assertContains('penanggung_jawab', $jalur);
        $this->assertFalse($hasil['tersimpan']);
    }

    public function testKuotaMenghitungPendaftaranLainTanpaPendaftaranIni(): void
    {
        setting('Pendaftaran.atlet_per_nomor_maksimal', 2);

        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()]]);   // 1 atlet Lari 60 m putra
        $b = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()]]);

        // Pendaftaran ini sendiri tidak dihitung dua kali: 1 + 1 = 2, masih boleh.
        $this->assertSame([], (new UbahPendaftaran())->periksa($b['id'], $this->kirimanDari($b['id']))['galat']);

        $kiriman            = $this->kirimanDari($b['id']);
        $kiriman['atlet'][] = $this->atletBaru(['lari60putra']);

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame(['individu.' . $this->idInduk['lari60putra']], array_column($hasil['galat'], 'jalur'));
    }

    public function testPeriksaTidakMenulisApaPun(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][] = $this->atletBaru(['lari60putra']);

        $hasil = (new UbahPendaftaran())->periksa($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame($p['total'] + 150000, $hasil['total']);
        $this->assertSame(1, $hasil['atlet_baru']);
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
        $this->assertSame($p['total'], $this->pendaftaran($p['id'])['total']);
    }

    public function testPendaftaranYangTidakAda(): void
    {
        $ubah = new UbahPendaftaran();

        $this->assertNull($ubah->isian(999));
        $this->assertNull($ubah->periksa(999, []));
        $this->assertNull($ubah->simpan(999, []));
    }

    // ---------------------------------------------------------------
    // Biaya
    // ---------------------------------------------------------------

    public function testBarisTetapMempertahankanBiayaTersimpanDanBarisBaruTarifSekarang(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true]);   // putra: Lari 60 m + Frog Jump, putri: Lari 60 m = 450.000
        $this->db->table('kelompok_umur')->where('id', $this->idInduk['ku10'])->update(['biaya_individu' => 200000]);

        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][0]['nomor'] = [$this->idInduk['lari60putra'], $this->idInduk['turbo']];   // Frog Jump → Turbo Throw

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(150000 + 200000 + 150000, $hasil['subtotal_individu']);
        $this->assertSame(500000, $this->pendaftaran($p['id'])['total']);
        $this->assertSame(
            [$this->idInduk['lari60putra'] => 150000, $this->idInduk['turbo'] => 200000],
            $this->biayaNomor($p['atlet'][0]),
        );
    }

    public function testTimBaruMemakaiTarifSekarangDanTimDihapusIkutHilang(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()]]);
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['kanga'])->update(['biaya_beregu' => 120000]);

        $kiriman = $this->kirimanDari($p['id']);

        for ($i = 0; $i < 4; $i++) {
            $kiriman['atlet'][] = $this->atletBaru(['kanga']);
        }

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(120000, $hasil['subtotal_beregu']);
        $this->assertSame(150000 + 120000, $this->pendaftaran($p['id'])['total']);

        // Tim dihapus lagi: semua anggota baru dihapus.
        $hasil = (new UbahPendaftaran())->simpan($p['id'], $this->kirimanDari($p['id'], static fn (array $a): bool => ! in_array(self::$idKanga, $a['nomor'], true)));

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(0, $hasil['subtotal_beregu']);
        $this->assertSame(0, $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $p['id'])->countAllResults());
        $this->assertSame(1, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->countAllResults());
    }

    public function testTimSusulanTetapTanpaBiayaTim(): void
    {
        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 4)]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 1), 'susulan' => ['kanga']]);

        $kiriman            = $this->kirimanDari($b['id']);
        $kiriman['atlet'][] = $this->atletBaru(['kanga']);

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(0, $hasil['total']);
        $this->assertSame(0, (int) $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $b['id'])->get()->getRow()->biaya_tim);

        // Batas anggota dihitung bersama tim pendaftaran pertama: 4 + 2 + 1 > 6.
        $kiriman            = $this->kirimanDari($b['id']);
        $kiriman['atlet'][] = $this->atletBaru(['kanga']);
        $hasil              = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame(['beregu.' . $this->idInduk['kanga']], array_column($hasil['galat'], 'jalur'));
    }

    public function testTimSusulanBaruTanpaBiayaTanpaKonfirmasi(): void
    {
        $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 4)]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => [$this->atletPanel()]]);

        $kiriman = $this->kirimanDari($b['id']);
        $kiriman['atlet'][0]['nomor'][] = $this->idInduk['kanga'];

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(0, $hasil['subtotal_beregu']);
        $this->assertSame(0, (int) $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $b['id'])->get()->getRow()->biaya_tim);
    }

    // ---------------------------------------------------------------
    // Status dan nomor dada (K32, K59)
    // ---------------------------------------------------------------

    public function testK32TotalNaikMembutuhkanPersetujuanLaluKembaliMenunggu(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => [$this->atletPanel(['nomor_dada' => '2001'])]]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][0]['nomor'][] = $this->idInduk['frog'];

        $tolak = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertTrue($tolak['perlu_setuju']);
        $this->assertFalse($tolak['tersimpan']);
        $this->assertSame(['setuju_menunggu'], array_column($tolak['galat'], 'jalur'));
        $this->assertStringContainsString('dari Rp 150.000 menjadi Rp 300.000', $tolak['galat'][0]['pesan']);
        $this->assertSame('lunas', $this->pendaftaran($p['id'])['status']);
        $this->assertSame(150000, $this->pendaftaran($p['id'])['total']);

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman, setujuMenunggu: true);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertTrue($hasil['kembali_menunggu']);
        $this->assertSame([], $hasil['nomor_dada']);
        $simpan = $this->pendaftaran($p['id']);
        $this->assertSame(['menunggu', 300000, null], [$simpan['status'], $simpan['total'], $simpan['waktu_lunas']]);
        $this->assertSame('2001', $this->nomorDada($p['atlet'][0]));
    }

    public function testK32TotalTurunJugaKembaliMenunggu(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => [$this->atletPanel(['nomor' => ['lari60putra', 'frog'], 'nomor_dada' => '2001'])]]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][0]['nomor'] = [$this->idInduk['lari60putra']];

        $cek = (new UbahPendaftaran())->periksa($p['id'], $kiriman);
        $this->assertTrue($cek['kembali_menunggu']);
        $this->assertStringContainsString('kembali menjadi Menunggu Konfirmasi Pembayaran', UbahPendaftaran::kalimatKonfirmasi($cek));

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman, setujuMenunggu: true);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
        $this->assertSame(150000, $this->pendaftaran($p['id'])['total']);
        $this->assertSame('2001', $this->nomorDada($p['atlet'][0]));
    }

    public function testK59TotalTetapAtletBaruLangsungMendapatNomorDada(): void
    {
        $p = $this->buatPendaftaranPanel([
            'foto'   => true,
            'status' => 'lunas',
            'atlet'  => $this->timPanel('kanga', 4),
        ]);
        $this->beriNomor($p['atlet'], ['2001', '2002', '2003', '2004']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 4]);

        $kiriman            = $this->kirimanDari($p['id']);
        $kiriman['atlet'][] = $this->atletBaru(['kanga']);

        $cek = (new UbahPendaftaran())->periksa($p['id'], $kiriman);
        $this->assertFalse($cek['kembali_menunggu']);
        $this->assertStringContainsString('1 atlet baru langsung mendapat nomor dada', UbahPendaftaran::kalimatKonfirmasi($cek));

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame('lunas', $this->pendaftaran($p['id'])['status']);
        $this->assertSame(100000, $this->pendaftaran($p['id'])['total']);
        $this->assertSame(['2005'], array_values($hasil['nomor_dada']));
        $this->assertSame(['2001', '2002', '2003', '2004', '2005'], array_map($this->nomorDada(...), $this->idAtlet($p['id'])));
    }

    public function testK59AwalanPenuhMembatalkanSemuaPerubahanDanFotoBaru(): void
    {
        $p = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => $this->timPanel('kanga', 4)]);
        $this->beriNomor($p['atlet'], ['2996', '2997', '2998', '2999']);
        $this->db->table('awalan_nomor_dada')->where('awalan', '2')->update(['nomor_terakhir' => 999]);
        $fotoAwal = $this->berkasFoto();

        $kiriman            = $this->kirimanDari($p['id']);
        $kiriman['atlet'][] = $this->atletBaru(['kanga']);
        $kiriman['penanggung_jawab'][0]['foto'] = self::fotoContoh(60, 40);
        $kiriman['sekolah']['nama'] = 'NAMA YANG TIDAK JADI';

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertFalse($hasil['tersimpan']);
        $this->assertStringContainsString('awalan nomor dada KU 10 sudah penuh', $hasil['galat'][0]['pesan']);
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
        $this->assertSame('SD KARANGAN MAJU', $this->pendaftaran($p['id'])['nama_sekolah']);
        $this->assertSame($fotoAwal, $this->berkasFoto(), 'Foto baru dihapus lagi dan foto lama tetap.');
    }

    public function testNomorDadaDipertahankanWalauKelompokUmurBerubah(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => [$this->atletPanel(['nomor_dada' => '2001'])]]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][0]['tanggal_lahir'] = '2015-03-10';                    // KU 12
        $kiriman['atlet'][0]['nomor']         = [$this->idInduk['lari60putraKu12']];

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman, setujuMenunggu: true);

        $this->assertTrue($hasil['tersimpan']);
        $atlet = $this->db->table('atlet')->where('id', $p['atlet'][0])->get()->getRowArray();
        $this->assertSame('2001', $atlet['nomor_dada']);
        $this->assertSame($this->idInduk['ku12'], (int) $atlet['kelompok_umur_id']);
        $this->assertSame(175000, $this->pendaftaran($p['id'])['total']);
    }

    public function testMenungguTetapMenungguDanTanpaNomorDada(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['atlet'][] = $this->atletBaru(['lari60putra']);

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertFalse($hasil['kembali_menunggu']);
        $this->assertSame('menunggu', $this->pendaftaran($p['id'])['status']);
        $this->assertSame(0, $this->db->table('atlet')->where('pendaftaran_id', $p['id'])->where('nomor_dada IS NOT NULL')->countAllResults());
    }

    // ---------------------------------------------------------------
    // Penanggung jawab dan foto
    // ---------------------------------------------------------------

    public function testGantiTambahHapusPenanggungJawabDanFotonya(): void
    {
        $p = $this->buatPendaftaranPanel([
            'foto'             => true,
            'penanggung_jawab' => [['nama' => 'Ibu Karangan', 'nomor_wa' => '6281200000001'], ['nama' => 'Bapak Karangan', 'nomor_wa' => '6281200000002']],
        ]);
        $lama = $this->db->table('penanggung_jawab')->select('id, foto')->where('pendaftaran_id', $p['id'])->orderBy('id')->get()->getResultArray();

        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['penanggung_jawab'][0]['foto']     = self::fotoContoh(60, 40);   // ganti foto
        $kiriman['penanggung_jawab'][0]['nomor_wa'] = '0812-0000-0009';
        unset($kiriman['penanggung_jawab'][1]);                                     // hapus
        $kiriman['penanggung_jawab'][] = ['nama' => 'Kak Karangan', 'nomor_wa' => '081200000003', 'foto' => self::fotoContoh()];

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $baru = $this->db->table('penanggung_jawab')->where('pendaftaran_id', $p['id'])->orderBy('id')->get()->getResultArray();

        $this->assertSame([(int) $lama[0]['id'], 'Kak Karangan'], [(int) $baru[0]['id'], $baru[1]['nama']]);
        $this->assertCount(2, $baru);
        $this->assertSame('6281200000009', $baru[0]['nomor_wa']);
        $this->assertNotSame($lama[0]['foto'], $baru[0]['foto']);

        $foto = new FotoPenanggungJawab();
        $this->assertFileDoesNotExist((string) $foto->jalurPenuh($lama[0]['foto']), 'Foto yang diganti dihapus.');
        $this->assertFileDoesNotExist((string) $foto->jalurPenuh($lama[1]['foto']), 'Foto penanggung jawab yang dihapus ikut dihapus.');
        $this->assertCount(2, $this->berkasFoto());
    }

    public function testFotoRusakDanPenanggungJawabBaruTanpaFotoDitolak(): void
    {
        $p       = $this->buatPendaftaranPanel(['foto' => true]);
        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['penanggung_jawab'][0]['foto'] = 'data:image/jpeg;base64,' . base64_encode('bukan gambar');
        $kiriman['penanggung_jawab'][]          = ['nama' => 'Kak Karangan', 'nomor_wa' => '081200000003', 'foto' => ''];

        $hasil = (new UbahPendaftaran())->simpan($p['id'], $kiriman);

        $this->assertSame(['penanggung_jawab.0.foto', 'penanggung_jawab.1.foto'], array_column($hasil['galat'], 'jalur'));
        $this->assertCount(1, $this->berkasFoto());
    }

    public function testNisnBersamaanDilaporkanSebagaiGalatTanpaMeninggalkanBerkas(): void
    {
        $lain = $this->buatPendaftaranPanel(['foto' => true, 'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN LAIN'], 'atlet' => [$this->atletPanel()]]);
        $p    = $this->buatPendaftaranPanel(['foto' => true]);
        $awal = $this->berkasFoto();

        $kiriman = $this->kirimanDari($p['id']);
        $kiriman['penanggung_jawab'][0]['foto'] = self::fotoContoh(60, 40);
        $kiriman['atlet'][] = $this->atletBaru(['lari60putra'], '9988776655');

        // Kiriman lain mendaftarkan NISN yang sama tepat setelah pemeriksaan.
        $db    = $this->db;
        $data  = new class ($db, $lain['id'], $this->idInduk['ku10']) extends DataIndukPendaftaran {
            private bool $sudah = false;

            public function __construct(private readonly object $db, private readonly int $pendaftaranId, private readonly int $kuId)
            {
            }

            public function induk(): array
            {
                if (! $this->sudah) {
                    $this->sudah = true;
                    $this->db->table('atlet')->insert([
                        'pendaftaran_id' => $this->pendaftaranId, 'nama' => 'ATLET BERSAMAAN', 'nisn' => '9988776655',
                        'tanggal_lahir'  => '2017-01-01', 'jenis_kelamin' => 'L', 'kelompok_umur_id' => $this->kuId,
                        'dibuat_pada'    => date('Y-m-d H:i:s'), 'diubah_pada' => date('Y-m-d H:i:s'),
                    ]);
                }

                return parent::induk();
            }
        };

        $hasil = (new UbahPendaftaran(null, $data))->simpan($p['id'], $kiriman);

        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame(['atlet.2.nisn'], array_column($hasil['galat'], 'jalur'));
        $this->assertSame($awal, $this->berkasFoto(), 'Foto baru dihapus setelah transaksi gagal.');
        $this->assertSame($p['atlet'], $this->idAtlet($p['id']));
    }

    // ---------------------------------------------------------------
    // Bantu
    // ---------------------------------------------------------------

    private static int $idKanga = 0;

    /**
     * Kiriman dari data tersimpan (isian formulir tanpa perubahan).
     *
     * @param (callable(array<string, mixed>): bool)|null $saringAtlet
     *
     * @return array<string, mixed>
     */
    private function kirimanDari(int $id, ?callable $saringAtlet = null): array
    {
        self::$idKanga = $this->idInduk['kanga'];
        $isian         = (new UbahPendaftaran())->isian($id);
        $atlet         = $saringAtlet === null ? $isian['atlet'] : array_values(array_filter($isian['atlet'], $saringAtlet));

        return [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => $isian['penanggung_jawab'],
            'atlet'            => $atlet,
        ];
    }

    /**
     * @param list<string> $nomor kunci nomor lomba
     *
     * @return array<string, mixed>
     */
    private function atletBaru(array $nomor, ?string $nisn = null): array
    {
        $a = $this->atletPanel($nisn === null ? [] : ['nisn' => $nisn]);

        return [
            'nama'          => $a['nama'],
            'nisn'          => $a['nisn'],
            'tanggal_lahir' => $a['tanggal_lahir'],
            'jenis_kelamin' => $a['jenis_kelamin'],
            'nomor'         => array_map(fn (string $k): int => $this->idInduk[$k], $nomor),
        ];
    }

    /**
     * @param list<int>    $atlet
     * @param list<string> $nomor
     */
    private function beriNomor(array $atlet, array $nomor): void
    {
        foreach ($atlet as $i => $id) {
            $this->db->table('atlet')->where('id', $id)->update(['nomor_dada' => $nomor[$i]]);
        }
    }

    /**
     * @return list<int>
     */
    private function idAtlet(int $pendaftaranId): array
    {
        return array_map('intval', array_column($this->db->table('atlet')->select('id')->where('pendaftaran_id', $pendaftaranId)->orderBy('id')->get()->getResultArray(), 'id'));
    }

    private function nomorDada(int $atletId): ?string
    {
        return $this->db->table('atlet')->where('id', $atletId)->get()->getRow()->nomor_dada;
    }

    /**
     * @return array<int, int|null> nomor lomba => biaya
     */
    private function biayaNomor(int $atletId): array
    {
        $hasil = [];

        foreach ($this->db->table('atlet_nomor')->where('atlet_id', $atletId)->orderBy('nomor_lomba_id')->get()->getResultArray() as $b) {
            $hasil[(int) $b['nomor_lomba_id']] = $b['biaya'] === null ? null : (int) $b['biaya'];
        }

        return $hasil;
    }

    /**
     * @return array<string, mixed>
     */
    private function pendaftaran(int $id): array
    {
        $baris = $this->db->table('pendaftaran')->where('id', $id)->get()->getRowArray();

        foreach (['subtotal_individu', 'subtotal_beregu', 'total'] as $kunci) {
            $baris[$kunci] = (int) $baris[$kunci];
        }

        return $baris;
    }
}
