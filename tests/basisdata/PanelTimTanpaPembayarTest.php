<?php

use App\Libraries\Panel\HapusPendaftaran;
use App\Libraries\Panel\Rekap;
use App\Libraries\Panel\TimTanpaPembayar;
use App\Libraries\Panel\UbahPendaftaran;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Tim beregu tanpa pembayar biaya tim (`App\Libraries\Panel\TimTanpaPembayar`,
 * K68, F7-03), dan pemakaiannya di `UbahPendaftaran`, `HapusPendaftaran`, dan
 * `Rekap`.
 *
 * Contoh: sekolah karangan NPSN 99999911 mendaftarkan tim Kanga's Escape
 * (Rp100.000, KU 10, inti 4, batas 6) lewat pendaftaran A (pembayar, 4
 * anggota) dan pendaftaran susulan B (2 anggota, tanpa biaya tim). Setelah A
 * pergi, tim B kurang dari 4 anggota, jadi Ubah yang membebankan biaya tim
 * sekaligus melengkapi anggotanya. Semua nama dan NISN karangan.
 *
 * @internal
 */
final class PanelTimTanpaPembayarTest extends UjiBasisData
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
    // Penandaan
    // ---------------------------------------------------------------

    public function testTimDenganPembayarTidakDitandai(): void
    {
        $this->timDuaPendaftaran();

        $this->assertSame([], (new TimTanpaPembayar())->daftar());
        $this->assertSame(0, (new TimTanpaPembayar())->jumlah());
    }

    public function testDitandaiSetelahPendaftaranPembayarDihapus(): void
    {
        [$a, $b] = $this->timDuaPendaftaran();
        $tim     = new TimTanpaPembayar();

        // Sebelum dihapus: jendela hapus A menyebut tim yang ditinggalkan.
        $tinggal = $tim->ditinggalkan($a['id']);
        $this->assertCount(1, $tinggal);
        $this->assertSame([$b['id_pendaftaran']], array_column($tinggal[0]['pendaftaran'], 'id_pendaftaran'));
        $this->assertStringContainsString("Kanga's Escape putra", TimTanpaPembayar::kalimatDitinggalkan($tinggal));
        $this->assertSame([], $tim->ditinggalkan($b['id']), 'B tidak membayar, jadi tidak meninggalkan tim tanpa pembayar.');

        (new HapusPendaftaran())->hapus($a['id']);

        $daftar = $tim->daftar();
        $this->assertCount(1, $daftar);
        $this->assertSame([
            'npsn'           => '99999911',
            'nomor_lomba_id' => $this->idInduk['kanga'],
            'nomor_lomba'    => "Kanga's Escape putra",
            'kelompok_umur'  => 'KU 10',
            'nama_sekolah'   => 'SD KARANGAN MAJU',
            'kabupaten'      => 'Kudus',
            'tarif'          => 100000,
            'jumlah_anggota' => 2,
            'pendaftaran'    => [['id' => $b['id'], 'id_pendaftaran' => $b['id_pendaftaran'], 'status' => 'menunggu', 'jumlah_anggota' => 2]],
        ], $daftar[0]);
        $this->assertSame(1, $tim->jumlah());
        $this->assertSame([$this->idInduk['kanga']], array_keys($tim->untukPendaftaran($b['id'])));
    }

    public function testDitandaiSetelahTimnyaDihapusLewatUbah(): void
    {
        [$a, $b] = $this->timDuaPendaftaran(lariDiA: true);

        $kiriman = $this->kirimanDari($a['id'], fn (array $atlet): bool => ! in_array($this->idInduk['kanga'], $atlet['nomor'], true));

        $periksa = (new UbahPendaftaran())->periksa($a['id'], $kiriman);
        $this->assertSame([], $periksa['galat']);
        $this->assertSame([$b['id_pendaftaran']], array_column($periksa['tim_ditinggalkan'][0]['pendaftaran'], 'id_pendaftaran'));
        $this->assertStringContainsString('Perhatian: pendaftaran ini membayar biaya tim', UbahPendaftaran::kalimatKonfirmasi($periksa));
        $this->assertSame([], (new TimTanpaPembayar())->daftar(), 'Pratinjau tidak menulis apa pun.');

        $hasil = (new UbahPendaftaran())->simpan($a['id'], $kiriman);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertCount(1, $hasil['tim_ditinggalkan']);
        $this->assertSame([$b['id']], array_column((new TimTanpaPembayar())->daftar()[0]['pendaftaran'], 'id'));
    }

    public function testUbahYangMempertahankanTimTidakMemperingatkan(): void
    {
        [$a] = $this->timDuaPendaftaran(lariDiA: true);

        $hasil = (new UbahPendaftaran())->periksa($a['id'], $this->kirimanDari($a['id']));

        $this->assertSame([], $hasil['tim_ditinggalkan']);
        $this->assertStringNotContainsString('Perhatian', UbahPendaftaran::kalimatKonfirmasi($hasil));
    }

    public function testNpsnDiubahMeninggalkanTimSekolahLama(): void
    {
        [$a] = $this->timDuaPendaftaran(lariDiA: true);

        $kiriman            = $this->kirimanDari($a['id']);
        $kiriman['sekolah'] = ['jenjang' => 'A', 'kabupaten' => 'Kudus', 'npsn' => '', 'nama' => 'SD KARANGAN BARU'];

        $hasil = (new UbahPendaftaran())->periksa($a['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertCount(1, $hasil['tim_ditinggalkan']);
    }

    public function testTidakDitandaiBilaTarifBereguNol(): void
    {
        [$a] = $this->timDuaPendaftaran();
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['kanga'])->update(['biaya_beregu' => 0]);

        $this->assertSame([], (new TimTanpaPembayar())->ditinggalkan($a['id']));

        (new HapusPendaftaran())->hapus($a['id']);

        $this->assertSame([], (new TimTanpaPembayar())->daftar());
    }

    public function testTimTanpaBarisBerbiayaDitandaiSelamaTarifnyaLebihDariNol(): void
    {
        // Tarif sekarang yang menentukan: tim yang semua barisnya Rp0 ditandai begitu tarifnya lebih dari 0.
        $this->buatPendaftaranPanel(['atlet' => $this->timPanel('kanga', 4), 'susulan' => ['kanga']]);

        $this->assertSame(1, (new TimTanpaPembayar())->jumlah());

        $this->db->table('nomor_lomba')->where('id', $this->idInduk['kanga'])->update(['biaya_beregu' => 0]);

        $this->assertSame(0, (new TimTanpaPembayar())->jumlah());
    }

    public function testSekolahTanpaNpsnTidakPernahDitandai(): void
    {
        $p = $this->buatPendaftaranPanel([
            'sekolah' => ['npsn' => null, 'nama' => 'SD KARANGAN TANPA NPSN'],
            'atlet'   => $this->timPanel('kanga', 4),
            'susulan' => ['kanga'],
        ]);

        $tim = new TimTanpaPembayar();

        $this->assertSame([], $tim->daftar());
        $this->assertSame([], $tim->untukPendaftaran($p['id']));
        $this->assertNull($tim->tarifBebankan($p['id'], null, $this->idInduk['kanga']));
    }

    public function testTimSekolahLainTidakTercampur(): void
    {
        [$a] = $this->timDuaPendaftaran();
        $this->buatPendaftaranPanel([
            'sekolah' => ['npsn' => '99999913', 'nama' => 'SD KARANGAN LAIN'],
            'atlet'   => $this->timPanel('kanga', 1),
            'susulan' => ['kanga'],
        ]);

        // Sekolah lain hanya punya baris Rp0: ditandai sendiri, tidak "dibayar" oleh A.
        $daftar = (new TimTanpaPembayar())->daftar();
        $this->assertSame(['99999913'], array_column($daftar, 'npsn'));

        (new HapusPendaftaran())->hapus($a['id']);

        $this->assertSame(['99999913', '99999911'], array_column((new TimTanpaPembayar())->daftar(), 'npsn'), 'Urut nama sekolah: LAIN, lalu MAJU.');
    }

    // ---------------------------------------------------------------
    // Bebankan lewat Ubah
    // ---------------------------------------------------------------

    public function testIsianUbahMenyebutTimTanpaPembayar(): void
    {
        [$a, $b] = $this->timDuaPendaftaran();
        $this->assertSame([], (new UbahPendaftaran())->isian($b['id'])['tim_tanpa_pembayar']);

        (new HapusPendaftaran())->hapus($a['id']);

        $isian = (new UbahPendaftaran())->isian($b['id']);
        $this->assertSame([$this->idInduk['kanga']], array_keys($isian['tim_tanpa_pembayar']));
        $this->assertFalse($isian['tim_tanpa_pembayar'][$this->idInduk['kanga']]['dibebankan']);
        $this->assertSame(100000, $isian['tim_tanpa_pembayar'][$this->idInduk['kanga']]['tarif']);

        $tampilUlang = (new UbahPendaftaran())->isian($b['id'], [UbahPendaftaran::KUNCI_BEBANKAN => [(string) $this->idInduk['kanga']]]);
        $this->assertTrue($tampilUlang['tim_tanpa_pembayar'][$this->idInduk['kanga']]['dibebankan']);
    }

    public function testTandaHilangSetelahDibebankanLewatUbahDenganTarifSekarang(): void
    {
        [$a, $b] = $this->timDuaPendaftaran();
        (new HapusPendaftaran())->hapus($a['id']);
        $this->db->table('nomor_lomba')->where('id', $this->idInduk['kanga'])->update(['biaya_beregu' => 120000]);

        $kiriman = $this->tambahAnggota($this->kirimanDari($b['id']), 2) + [UbahPendaftaran::KUNCI_BEBANKAN => [(string) $this->idInduk['kanga']]];
        $hasil   = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame([$this->idInduk['kanga']], $hasil['tim_dibebankan']);
        $this->assertSame(120000, $hasil['subtotal_beregu']);
        $this->assertSame(120000, $hasil['total']);
        $this->assertSame('menunggu', $hasil['status']);
        $this->assertSame(120000, $this->biayaTim($b['id']));
        $this->assertSame(120000, (int) $this->db->table('pendaftaran')->where('id', $b['id'])->get()->getRow()->total);
        $this->assertSame([], (new TimTanpaPembayar())->daftar());

        // Simpan lagi tanpa centang: biaya tersimpan dipertahankan.
        $hasil = (new UbahPendaftaran())->simpan($b['id'], $this->kirimanDari($b['id']));
        $this->assertSame(120000, $hasil['total']);
        $this->assertSame(120000, $this->biayaTim($b['id']));
    }

    public function testBebankanDitolakBilaTimSudahPunyaPembayar(): void
    {
        [, $b] = $this->timDuaPendaftaran();

        $kiriman = $this->kirimanDari($b['id']) + [UbahPendaftaran::KUNCI_BEBANKAN => [$this->idInduk['kanga']]];
        $hasil   = (new UbahPendaftaran())->simpan($b['id'], $kiriman);

        $this->assertFalse($hasil['tersimpan']);
        $this->assertSame([UbahPendaftaran::KUNCI_BEBANKAN . '.' . $this->idInduk['kanga']], array_column($hasil['galat'], 'jalur'));
        $this->assertStringContainsString('sudah punya pembayar biaya tim', $hasil['galat'][0]['pesan']);
        $this->assertSame(0, $this->biayaTim($b['id']));
    }

    public function testBebankanNomorYangBukanTimPendaftaranIniDiabaikan(): void
    {
        [$a, $b] = $this->timDuaPendaftaran();
        (new HapusPendaftaran())->hapus($a['id']);

        $kiriman = $this->tambahAnggota($this->kirimanDari($b['id']), 2) + [UbahPendaftaran::KUNCI_BEBANKAN => [$this->idInduk['estafet'], 'bukan angka']];
        $hasil   = (new UbahPendaftaran())->periksa($b['id'], $kiriman);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame([], $hasil['tim_dibebankan']);
        $this->assertSame(0, $hasil['total']);
    }

    public function testK32SaatMembebankanKePendaftaranLunas(): void
    {
        $a = $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 4)]);
        $b = $this->buatPendaftaranPanel(['foto' => true, 'status' => 'lunas', 'atlet' => [$this->atletPanel(), ...$this->timPanel('kanga', 2)], 'susulan' => ['kanga']]);
        (new HapusPendaftaran())->hapus($a['id']);

        $kiriman = $this->tambahAnggota($this->kirimanDari($b['id']), 2) + [UbahPendaftaran::KUNCI_BEBANKAN => [$this->idInduk['kanga']]];

        $periksa = (new UbahPendaftaran())->periksa($b['id'], $kiriman);
        $this->assertTrue($periksa['kembali_menunggu']);
        $this->assertSame([150000, 250000], [$periksa['total_lama'], $periksa['total']]);
        $this->assertStringContainsString('status kembali menjadi Menunggu Konfirmasi Pembayaran', UbahPendaftaran::kalimatKonfirmasi($periksa));

        $tolak = (new UbahPendaftaran())->simpan($b['id'], $kiriman);
        $this->assertTrue($tolak['perlu_setuju']);
        $this->assertSame(0, $this->biayaTim($b['id']));
        $this->assertSame('lunas', $this->db->table('pendaftaran')->where('id', $b['id'])->get()->getRow()->status);

        $hasil = (new UbahPendaftaran())->simpan($b['id'], $kiriman, setujuMenunggu: true);

        $this->assertTrue($hasil['tersimpan']);
        $this->assertSame('menunggu', $hasil['status']);
        $this->assertSame(100000, $this->biayaTim($b['id']));
        $this->assertSame(0, (new TimTanpaPembayar())->jumlah());
    }

    // ---------------------------------------------------------------
    // Angka sama di rekap dan dasbor
    // ---------------------------------------------------------------

    public function testRekapKeuanganDanRingkasanMemakaiDaftarYangSama(): void
    {
        [$a, $b] = $this->timDuaPendaftaran();

        $this->assertSame(0, (new Rekap())->ringkasan()['tim_tanpa_pembayar']);
        $this->assertSame([], (new Rekap())->susun('keuangan')['tim_tanpa_pembayar']['baris']);

        (new HapusPendaftaran())->hapus($a['id']);

        $rekap = (new Rekap())->susun('keuangan')['tim_tanpa_pembayar'];

        $this->assertSame(1, (new Rekap())->ringkasan()['tim_tanpa_pembayar']);
        $this->assertCount(1, $rekap['baris']);
        $this->assertSame("Kanga's Escape putra", $rekap['baris'][0]['nomor_lomba']);
        $this->assertSame(100000, $rekap['baris'][0]['tarif']);
        $this->assertSame($b['id_pendaftaran'] . ' (Menunggu, 2 anggota)', $rekap['baris'][0]['pendaftaran']);
        $this->assertSame('rupiah', $rekap['kolom']['tarif']['jenis']);
        $this->assertArrayNotHasKey('tim_tanpa_pembayar', (new Rekap())->susun('tim-beregu'));
    }

    // ---------------------------------------------------------------
    // Bantu
    // ---------------------------------------------------------------

    /**
     * A membayar tim Kanga's Escape (4 anggota; dengan `$lariDiA` ditambah
     * satu atlet Lari 60 m supaya A tetap sah tanpa timnya); B susulan 2
     * anggota tanpa biaya tim. Keduanya NPSN 99999911.
     *
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function timDuaPendaftaran(bool $lariDiA = false): array
    {
        $atletA = $this->timPanel('kanga', 4);

        if ($lariDiA) {
            array_unshift($atletA, $this->atletPanel());
        }

        return [
            $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $atletA]),
            $this->buatPendaftaranPanel(['foto' => true, 'atlet' => $this->timPanel('kanga', 2), 'susulan' => ['kanga']]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function kirimanDari(int $id, ?callable $saringAtlet = null): array
    {
        $isian = (new UbahPendaftaran())->isian($id);
        $atlet = $saringAtlet === null ? $isian['atlet'] : array_values(array_filter($isian['atlet'], $saringAtlet));

        return [
            'sekolah'          => $isian['sekolah'],
            'penanggung_jawab' => $isian['penanggung_jawab'],
            'atlet'            => $atlet,
        ];
    }

    /**
     * Tambah `$jumlah` atlet baru Kanga's Escape ke kiriman.
     *
     * @param array<string, mixed> $kiriman
     *
     * @return array<string, mixed>
     */
    private function tambahAnggota(array $kiriman, int $jumlah): array
    {
        foreach ($this->timPanel('kanga', $jumlah) as $a) {
            $kiriman['atlet'][] = [
                'nama'          => $a['nama'],
                'nisn'          => $a['nisn'],
                'tanggal_lahir' => $a['tanggal_lahir'],
                'jenis_kelamin' => $a['jenis_kelamin'],
                'nomor'         => [$this->idInduk['kanga']],
            ];
        }

        return $kiriman;
    }

    private function biayaTim(int $pendaftaranId): int
    {
        return (int) $this->db->table('pendaftaran_beregu')->where('pendaftaran_id', $pendaftaranId)
            ->where('nomor_lomba_id', $this->idInduk['kanga'])->get()->getRow()->biaya_tim;
    }
}
