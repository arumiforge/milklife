<?php

namespace App\Controllers;

use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use App\Libraries\Hasil\UnduhanHasil;
use App\Libraries\Hasil\UnduhanKlasemen;
use App\Libraries\IsiHalamanDepan;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman publik hasil lomba (F8-04, K124 butir 7, asumsi 8 K125): `hasil`
 * (nomor yang sudah terbit per kelompok umur), `hasil/<id>` (semua babak satu
 * nomor), unduhan PDF/Excel, dan klasemen sekolah `hasil/klasemen` (F8-05,
 * hanya nomor terbit). Nomor yang belum terbit tidak ditemukan.
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

    public function klasemen(): string
    {
        return view('hasil/klasemen', [
            'namaAcara' => (new IsiHalamanDepan())->acara()['nama'],
            'klasemen'   => (new HasilLomba())->klasemen(true),
            'metode'     => MetodeKlasemen::metode(),
            'keterangan' => Klasemen::keterangan(MetodeKlasemen::metode(), MetodeKlasemen::poin()),
        ]);
    }

    public function unduhKlasemen(string $jenis): ResponseInterface
    {
        $batas = $this->batasUnduhan();

        if ($batas !== null) {
            return $batas;
        }

        $unduhan = new UnduhanKlasemen((new HasilLomba())->klasemen(true), true);

        if ($jenis === 'excel') {
            return $unduhan->excel()->unduh(UnduhanKlasemen::namaBerkas('xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanKlasemen::namaBerkas('pdf') . '"')
            ->setBody($unduhan->pdf());
    }

    public function unduh(int $id, string $jenis): ResponseInterface
    {
        $hasil = new HasilLomba();
        $nomor = $this->cariTerbit($hasil, $id);
        $batas = $this->batasUnduhan();

        if ($batas !== null) {
            return $batas;
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
     * Jawaban 429 bila alamat IP ini terlalu sering mengunduh, selain itu `null`.
     */
    private function batasUnduhan(): ?ResponseInterface
    {
        $pembatas = service('throttler');

        if ($pembatas->check('hasil-' . hash('sha256', $this->request->getIPAddress()), self::BATAS_UNDUHAN, MINUTE)) {
            return null;
        }

        return $this->response->setStatusCode(429)->setHeader('Retry-After', (string) max(1, $pembatas->getTokenTime()))
            ->setBody('Terlalu banyak unduhan dalam satu menit. Tunggu sebentar, lalu coba lagi.');
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
