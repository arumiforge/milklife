<?php

use App\Libraries\Panel\RingkasanDasbor;
use App\Libraries\Pengunjung;
use CodeIgniter\I18n\Time;
use Tests\Support\KirimFormulir;
use Tests\Support\PanelDataContoh;
use Tests\Support\UjiBasisData;

/**
 * Angka tambahan dan data grafik dasbor (K102, `RingkasanDasbor`).
 *
 * @internal
 */
final class RingkasanDasborTest extends UjiBasisData
{
    use KirimFormulir;
    use PanelDataContoh;

    protected function tearDown(): void
    {
        $this->bersihkanBerkasPanel();

        parent::tearDown();
    }

    public function testBasisDataKosong(): void
    {
        $r = new RingkasanDasbor($this->db, '2026-11-15');

        $angka = $r->angka();
        $this->assertSame(0, $angka['pendaftaran_lunas']);
        $this->assertSame(0, $angka['perlu_diperiksa']);
        $this->assertSame(0.0, $angka['rata_atlet']);
        $this->assertSame(0, $angka['pengunjung_hari_ini']);

        $grafik = $r->grafik();
        $this->assertCount(RingkasanDasbor::HARI_TREN, $grafik['tren']['tanggal']);
        $this->assertSame('2026-11-15', end($grafik['tren']['tanggal']));
        $this->assertSame([0, 0], $grafik['status']['jumlah']);
        $this->assertSame(['Blora', 'Rembang', 'Pati', 'Kudus', 'Jepara'], $grafik['kabupaten']['label']);
        $this->assertSame(['SD/MI', 'SMP/MTs', 'SMA/SMK/MA'], $grafik['jenjang']['label']);
        $this->assertSame([], $grafik['nomor_lomba']['label']);
        $this->assertCount(24, $grafik['jam_daftar']['pendaftaran']);
        $this->assertCount(RingkasanDasbor::HARI_TREN, $grafik['pengunjung']['tanggal']);
    }

    public function testAngkaDanGrafik(): void
    {
        // Menunggu dengan bukti bayar, hari ini pukul 09: 1 putra + 1 putri, Kudus, SD.
        $a = $this->buatPendaftaranPanel(['dibuat_pada' => '2026-11-15 09:15:00']);
        $this->db->table('bukti_bayar')->insert(['pendaftaran_id' => $a['id'], 'berkas' => 'karangan.jpg', 'ukuran' => 1000, 'waktu_unggah' => '2026-11-15 09:20:00']);

        // Lunas kemarin pukul 20, Jepara, SMP, satu putri bernomor dada.
        $this->buatPendaftaranPanel([
            'sekolah'     => ['nama' => 'SMP KARANGAN JAYA', 'npsn' => '99999913', 'jenjang' => 'B', 'kabupaten' => 'Jepara'],
            'status'      => 'lunas',
            'atlet'       => [$this->atletPanel(['ku' => 'ku15', 'jenis_kelamin' => 'P', 'tanggal_lahir' => '2012-05-01', 'nomor' => ['lari80putri'], 'nomor_dada' => '4001'])],
            'dibuat_pada' => '2026-11-14 20:05:00',
        ]);

        // Menunggu tanpa bukti, di luar rentang tren.
        $this->buatPendaftaranPanel(['sekolah' => ['npsn' => '99999914', 'nama' => 'SD KARANGAN LAMA'], 'dibuat_pada' => '2026-09-01 10:00:00']);

        (new Pengunjung($this->db, 'uji'))->catat('10.0.0.1', 'Mozilla/5.0', '/', Time::parse('2026-11-15 08:00:00', 'Asia/Jakarta'));

        $r     = new RingkasanDasbor($this->db, '2026-11-15');
        $angka = $r->angka();

        $this->assertSame(1, $angka['pendaftaran_lunas']);
        $this->assertSame(2, $angka['pendaftaran_menunggu']);
        $this->assertSame(1, $angka['perlu_diperiksa']);
        $this->assertSame(2, $angka['atlet_putra']);
        $this->assertSame(3, $angka['atlet_putri']);
        $this->assertSame(1, $angka['nomor_dada']);
        $this->assertSame(2, $angka['kabupaten']);
        $this->assertSame(1, $angka['pendaftaran_hari_ini']);
        $this->assertSame(2, $angka['atlet_hari_ini']);
        $this->assertSame(1.7, $angka['rata_atlet']);
        $this->assertSame(1, $angka['pengunjung_hari_ini']);
        $this->assertSame(1, $angka['kunjungan_hari_ini']);

        $g = $r->grafik();

        $this->assertSame(1, $g['tren']['pendaftaran'][RingkasanDasbor::HARI_TREN - 1]);
        $this->assertSame(2, $g['tren']['atlet'][RingkasanDasbor::HARI_TREN - 1]);
        $this->assertSame(1, $g['tren']['pendaftaran'][RingkasanDasbor::HARI_TREN - 2]);
        $this->assertSame(2, array_sum($g['tren']['pendaftaran']), 'Pendaftaran September di luar 30 hari.');

        $this->assertSame([1, 2], $g['status']['jumlah']);

        $kudus  = array_search('Kudus', $g['kabupaten']['label'], true);
        $jepara = array_search('Jepara', $g['kabupaten']['label'], true);
        $this->assertSame([2, 2], [$g['kabupaten']['putra'][$kudus], $g['kabupaten']['putri'][$kudus]]);
        $this->assertSame([0, 1], [$g['kabupaten']['putra'][$jepara], $g['kabupaten']['putri'][$jepara]]);

        $this->assertSame([4, 1, 0], $g['jenjang']['atlet']);
        $this->assertSame([2, 1, 0], $g['jenjang']['pendaftaran']);

        $this->assertSame(1, $g['jam_daftar']['pendaftaran'][9]);
        $this->assertSame(1, $g['jam_daftar']['pendaftaran'][20]);
        $this->assertSame(1, $g['jam_daftar']['pendaftaran'][10]);

        // Nomor terpopuler: Lari 60 m putra (2 peserta) di atas.
        $this->assertStringContainsString('Lari 60 m putra', $g['nomor_lomba']['label'][0]);
        $this->assertSame(2, $g['nomor_lomba']['peserta'][0]);

        $this->assertSame(1, end($g['pengunjung']['unik']));
    }
}
