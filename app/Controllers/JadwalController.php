<?php

namespace App\Controllers;

use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\UnduhanJadwal;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Halaman publik Jadwal pertandingan (`/jadwal`, F9-07): babak terjadwal per
 * hari dengan status otomatis dan tautan ke hasil, serta unduhan PDF per hari
 * (`/jadwal/<Y-m-d>/pdf`).
 *
 * - Tab hari memakai tautan `?hari=<Y-m-d>`, jadi tetap bekerja tanpa
 *   JavaScript. Tanpa `hari`, yang dibuka hari ini atau hari terdekat berikutnya.
 * - Selama sakelar `HalamanDepan.tampil_jadwal` mati (bawaan), keduanya 404.
 * - Unduhan PDF dibatasi per alamat IP seperti unduhan hasil.
 */
class JadwalController extends BaseController
{
    /**
     * Unduhan PDF paling banyak per alamat IP per menit.
     */
    public const BATAS_UNDUHAN = 20;

    /**
     * Sakelar tampil halaman Jadwal pertandingan dari Pengaturan > Halaman depan.
     */
    public static function tampil(): bool
    {
        return (bool) setting('HalamanDepan.tampil_jadwal');
    }

    public function index(): string
    {
        $this->pastikanTampil();

        $perHari = (new JadwalPertandingan())->perHari();
        $kini    = new DateTimeImmutable('now');
        $diminta = $this->request->getGet('hari');
        $hari    = JadwalPertandingan::pilihHari(array_keys($perHari), is_string($diminta) ? $diminta : null, $kini);
        $babak   = $hari === null ? [] : $perHari[$hari];

        // Tautan Daftar seri (F9-08): babak yang daftar serinya sudah dikunci,
        // selama sakelar `HalamanDepan.tampil_seri` menyala.
        if (SeriLintasan::tampil()) {
            $babak = array_map(static fn (array $b): array => $b + ['alamat_seri' => $b['seri_dikunci'] ? site_url('jadwal/' . $b['id']) : null], $babak);
        }

        return view('jadwal/index', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'hari'      => array_keys($perHari),
            'dipilih'   => $hari,
            'babak'     => $babak,
            'kini'      => $kini,
        ]);
    }

    public function pdf(string $tanggal): ResponseInterface
    {
        $this->pastikanTampil();

        $perHari = (new JadwalPertandingan())->perHari();

        if (! isset($perHari[$tanggal])) {
            throw PageNotFoundException::forPageNotFound('Belum ada jadwal pertandingan di tanggal ini.');
        }

        $pembatas = service('throttler');

        if (! $pembatas->check('jadwal-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_UNDUHAN, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', (string) max(1, $pembatas->getTokenTime()))
                ->setBody('Terlalu banyak unduhan dalam satu menit. Tunggu sebentar, lalu coba lagi.');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanJadwal::namaBerkas($tanggal) . '"')
            ->setBody((new UnduhanJadwal($tanggal, $perHari[$tanggal]))->pdf());
    }

    private function pastikanTampil(): void
    {
        if (! self::tampil()) {
            throw PageNotFoundException::forPageNotFound();
        }
    }
}
