<?php

namespace App\Controllers;

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\UnduhanHasil;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman publik hasil lomba (F8-04, K124 butir 7, asumsi 8 K125): `hasil`
 * (nomor yang sudah terbit per kelompok umur), `hasil/<id>` (semua babak satu
 * nomor), dan unduhan PDF/Excel. Nomor yang belum terbit tidak ditemukan.
 * Unduhan dibatasi per alamat IP supaya pembuatan PDF tidak dibanjiri.
 */
class HasilController extends BaseController
{
    /**
     * Unduhan PDF/Excel paling banyak per alamat IP per menit.
     */
    public const BATAS_UNDUHAN = 20;

    public function index(): string
    {
        $hasil = new HasilLomba();
        $juara = [];

        foreach ($hasil->juara(true) as $j) {
            $juara[$j['nomor_id']][] = $j;
        }

        return view('hasil/index', [
            'namaAcara'    => (new IsiHalamanDepan())->acara()['nama'],
            'kelompokUmur' => $hasil->daftarNomor(true),
            'juara'        => $juara,
        ]);
    }

    public function nomor(int $id): string
    {
        $hasil = new HasilLomba();
        $nomor = $this->cariTerbit($hasil, $id);

        return view('hasil/nomor', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'nomor'     => $nomor,
            'babak'     => $hasil->semuaBabak($nomor),
        ]);
    }

    public function unduh(int $id, string $jenis): ResponseInterface
    {
        $hasil = new HasilLomba();
        $nomor = $this->cariTerbit($hasil, $id);

        $pembatas = service('throttler');
        if (! $pembatas->check('hasil-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_UNDUHAN, MINUTE)) {
            return $this->response->setStatusCode(429)->setHeader('Retry-After', (string) max(1, $pembatas->getTokenTime()))
                ->setBody('Terlalu banyak unduhan dalam satu menit. Tunggu sebentar, lalu coba lagi.');
        }

        $unduhan = new UnduhanHasil($nomor, $hasil->semuaBabak($nomor));

        if ($jenis === 'excel') {
            return $unduhan->excel()->unduh(UnduhanHasil::namaBerkas($nomor, 'xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanHasil::namaBerkas($nomor, 'pdf') . '"')
            ->setBody($unduhan->pdf());
    }

    /**
     * @return array<string, mixed>
     */
    private function cariTerbit(HasilLomba $hasil, int $id): array
    {
        $nomor = $hasil->nomor($id);

        if ($nomor === null || $nomor['terbit_pada'] === null) {
            throw PageNotFoundException::forPageNotFound('Hasil nomor lomba ini belum diterbitkan.');
        }

        return $nomor;
    }
}
