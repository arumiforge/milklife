<?php

use App\Libraries\Pengunjung;
use CodeIgniter\I18n\Time;
use Tests\Support\UjiBasisData;

/**
 * Catatan pengunjung di tabel `kunjungan` (K102, `App\Libraries\Pengunjung`).
 *
 * @internal
 */
final class PengunjungTest extends UjiBasisData
{
    private const PERAMBAN = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0';
    private const PONSEL   = 'Mozilla/5.0 (Linux; Android 14) Chrome/130.0 Mobile Safari/537.36';

    private Pengunjung $pengunjung;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pengunjung = new Pengunjung($this->db, 'kunci-uji');
    }

    private function catat(string $ip, string $waktu, string $jalur = '/', string $peramban = self::PERAMBAN): void
    {
        $this->pengunjung->catat($ip, $peramban, $jalur, Time::parse($waktu, 'Asia/Jakarta'));
    }

    public function testCatatTanpaMenyimpanIp(): void
    {
        $this->catat('203.0.113.5', '2026-11-15 08:10:00', '/cek-pendaftaran?npsn=12345678', self::PONSEL);

        $baris = $this->db->table('kunjungan')->get()->getRowArray();

        $this->assertSame('2026-11-15 08:10:00', $baris['waktu']);
        $this->assertSame('/cek-pendaftaran', $baris['halaman']);
        $this->assertSame('1', (string) $baris['ponsel']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{16}$/', $baris['sidik']);
        $this->assertStringNotContainsString('203.0.113.5', implode('|', $baris));

        // Sidik orang yang sama berbeda di hari lain dan dengan kunci lain.
        $this->assertSame($baris['sidik'], $this->pengunjung->sidik('203.0.113.5', self::PONSEL, '2026-11-15'));
        $this->assertNotSame($baris['sidik'], $this->pengunjung->sidik('203.0.113.5', self::PONSEL, '2026-11-16'));
        $this->assertNotSame($baris['sidik'], (new Pengunjung($this->db, 'kunci-lain'))->sidik('203.0.113.5', self::PONSEL, '2026-11-15'));
    }

    public function testPerJamDanPerHari(): void
    {
        // 15 November: A dua kali jam 08, B sekali jam 08, A sekali jam 20.
        $this->catat('10.0.0.1', '2026-11-15 08:05:00');
        $this->catat('10.0.0.1', '2026-11-15 08:40:00', '/pendaftaran');
        $this->catat('10.0.0.2', '2026-11-15 08:59:59');
        $this->catat('10.0.0.1', '2026-11-15 20:00:00');
        // 13 November: satu kunjungan; 14 November kosong.
        $this->catat('10.0.0.3', '2026-11-13 00:00:00');
        // Di luar rentang.
        $this->catat('10.0.0.3', '2026-11-16 00:00:00');

        $jam = $this->pengunjung->perJam('2026-11-15');
        $this->assertCount(24, $jam);
        $this->assertSame(['jam' => 8, 'kunjungan' => 3, 'unik' => 2], $jam[8]);
        $this->assertSame(['jam' => 20, 'kunjungan' => 1, 'unik' => 1], $jam[20]);
        $this->assertSame(['jam' => 0, 'kunjungan' => 0, 'unik' => 0], $jam[0]);

        $this->assertSame([
            ['tanggal' => '2026-11-13', 'kunjungan' => 1, 'unik' => 1],
            ['tanggal' => '2026-11-14', 'kunjungan' => 0, 'unik' => 0],
            ['tanggal' => '2026-11-15', 'kunjungan' => 4, 'unik' => 2],
        ], $this->pengunjung->perHari('2026-11-13', '2026-11-15'));

        // Urutan terbalik ditukar; rentang dibatasi 366 hari.
        $this->assertCount(3, $this->pengunjung->perHari('2026-11-15', '2026-11-13'));
        $this->assertCount(366, $this->pengunjung->perHari('2020-01-01', '2026-11-15'));

        $this->assertSame([
            ['halaman' => '/', 'kunjungan' => 3, 'unik' => 2],
            ['halaman' => '/pendaftaran', 'kunjungan' => 1, 'unik' => 1],
        ], $this->pengunjung->halamanTeratas('2026-11-15'));

        $this->assertSame('2026-11-13', $this->pengunjung->tanggalPertama());
    }

    public function testRingkasan(): void
    {
        $this->catat('10.0.0.1', '2026-11-15 08:00:00', '/', self::PONSEL);
        $this->catat('10.0.0.2', '2026-11-15 09:00:00');
        $this->catat('10.0.0.1', '2026-11-14 09:00:00');
        $this->catat('10.0.0.1', '2026-11-01 09:00:00');
        $this->catat('10.0.0.1', '2026-09-01 09:00:00');

        $r = $this->pengunjung->ringkasan('2026-11-15');

        $this->assertSame(['kunjungan' => 2, 'unik' => 2], $r['hari_ini']);
        $this->assertSame(['kunjungan' => 1, 'unik' => 1], $r['kemarin']);
        $this->assertSame(['kunjungan' => 3, 'unik' => 3], $r['tujuh_hari']);
        $this->assertSame(['kunjungan' => 4, 'unik' => 4], $r['tiga_puluh_hari']);
        $this->assertSame(['kunjungan' => 5, 'unik' => 5], $r['semua']);
        $this->assertSame(20, $r['ponsel_persen']);
    }

    public function testKosong(): void
    {
        $this->assertNull($this->pengunjung->tanggalPertama());
        $this->assertSame([], $this->pengunjung->halamanTeratas('2026-11-15'));
        $this->assertSame(0, $this->pengunjung->ringkasan('2026-11-15')['ponsel_persen']);
    }

    public function testHapusLama(): void
    {
        $this->catat('10.0.0.1', '2025-01-01 09:00:00');
        $this->catat('10.0.0.1', '2026-11-15 09:00:00');

        $this->assertSame(1, $this->pengunjung->hapusLama(Time::parse('2026-11-15 10:00:00', 'Asia/Jakarta')));
        $this->assertSame(1, $this->db->table('kunjungan')->countAllResults());
    }
}
