<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\HariLomba\CetakBib;
use App\Libraries\HariLomba\GambarBib;
use App\Libraries\HariLomba\PdfHariLomba;
use App\Libraries\HariLomba\TampilanBib;
use App\Libraries\HariLomba\UnduhanZipAlir;
use App\Libraries\HariLomba\ZipAlir;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use RuntimeException;

/**
 * Cetak nomor atlet (BIB) massal (F8-03, K137): `admin/nomor-atlet/cetak`.
 *
 * - `index`: tampilan BIB (latar, kepala, warna tulisan) dengan contoh gambar,
 *   unduhan per kelompok umur (PDF), per sekolah (PDF atau ZIP gambar), dan
 *   ZIP semua sekolah.
 * - `pdf`: `?ku=<id kelompok umur>` atau `?ku=semua`; dua BIB per A4.
 * - `simpanTampilan`: unggah latar sendiri, pilih latar bawaan G9-01 (bila
 *   berkasnya ada), atau hapus latar; kepala dan warna tulisan.
 * - `contoh`: gambar JPG contoh dengan tampilan yang berlaku.
 * - `sekolah`: `<kunci sekolah>/pdf` atau `/zip` (gambar JPG per atlet dan PDF).
 * - `zip`: semua sekolah, satu folder per sekolah.
 */
class CetakBibController extends BaseController
{
    private const ALAMAT = 'admin/nomor-atlet/cetak';

    public function index(): string
    {
        $bib      = new CetakBib();
        $kelompok = $bib->kelompokUmur();

        return view('admin/hari_lomba/bib', [
            'kelompokUmur' => $kelompok,
            'jumlahSemua'  => array_sum(array_column($kelompok, 'atlet')),
            'sekolahBib'   => $bib->sekolah(),
            'tampilanBib'  => TampilanBib::dariPengaturan(),
            'latarBawaan'  => TampilanBib::daftarBawaan(),
            'kepalaDipilih' => (bool) setting('Bib.tampilkan_kepala'),
            'warnaDipilih' => (string) setting('Bib.warna_teks'),
        ]);
    }

    public function pdf(): ResponseInterface
    {
        $bib = new CetakBib();
        $ku  = (string) $this->request->getGet('ku');
        $id  = $ku === 'semua' || $ku === '' ? null : (int) $ku;

        if ($id !== null && $bib->namaKelompokUmur($id) === null) {
            throw PageNotFoundException::forPageNotFound('Kelompok umur tidak ditemukan.');
        }

        // Ratusan halaman bisa butuh waktu lebih lama dari batas bawaan.
        @set_time_limit(300);

        return $this->unduhPdf($bib->pdf($id), $bib->namaBerkas($id));
    }

    public function simpanTampilan(): ResponseInterface
    {
        $bawaan = $this->request->getPost('latar_bawaan');

        try {
            TampilanBib::simpan(
                $this->request->getFile('latar'),
                $this->request->getPost('hapus_latar') === '1',
                $this->request->getPost('tampilkan_kepala') === '1',
                (string) $this->request->getPost('warna_teks'),
                bawaan: is_string($bawaan) ? $bawaan : null,
            );
        } catch (RuntimeException $e) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', $e->getMessage());
        }

        return redirect()->to(self::ALAMAT)->with('pesan_sukses', 'Tampilan BIB tersimpan. Periksa gambar contoh sebelum mencetak.');
    }

    public function contoh(): ResponseInterface
    {
        $atlet = (new CetakBib())->atlet(null)[0] ?? ['nomor_dada' => '1001', 'nama' => 'Nama Atlet Contoh', 'sekolah' => 'SD Contoh Karesidenan Pati', 'kelompok_umur' => 'KU 10'];
        $jpg   = (new GambarBib(TampilanBib::dariPengaturan(), PdfHariLomba::namaAcara(), PdfHariLomba::seriTahun(), PdfHariLomba::logoPng()))->jpg($atlet, 80);

        return $this->response
            ->setContentType('image/jpeg')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($jpg);
    }

    public function sekolah(string $kunci, string $jenis): ResponseInterface
    {
        $bib     = new CetakBib();
        $sekolah = array_values(array_filter($bib->sekolah(), static fn (array $s): bool => $s['kunci'] === $kunci))[0] ?? null;

        if ($sekolah === null) {
            throw PageNotFoundException::forPageNotFound('Sekolah tidak ditemukan atau belum punya atlet dengan nomor atlet (BIB).');
        }

        if ($jenis === 'pdf') {
            @set_time_limit(300);

            return $this->unduhPdf($bib->pdfSekolah($kunci), CetakBib::namaBerkasSekolah($sekolah['nama'], 'pdf'));
        }

        return $this->unduhZip($bib, $kunci, CetakBib::namaBerkasSekolah($sekolah['nama'], 'zip'));
    }

    public function zip(): ResponseInterface
    {
        return $this->unduhZip(new CetakBib(), null, CetakBib::namaBerkasSemua());
    }

    private function unduhPdf(string $isi, string $namaBerkas): ResponseInterface
    {
        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $namaBerkas . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($isi);
    }

    /**
     * ZIP dibuat sambil dikirim (`UnduhanZipAlir`): unduhan mulai sejak berkas
     * pertama, jadi tidak diputus Cloudflare walau butuh beberapa menit.
     * Kunci sesi dilepas lebih dulu supaya admin tetap bisa membuka halaman
     * lain selama ZIP dibuat.
     */
    private function unduhZip(CetakBib $bib, ?string $kunci, string $namaBerkas): ResponseInterface
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        return new UnduhanZipAlir($namaBerkas, static function (ZipAlir $zip) use ($bib, $kunci): void {
            // Ribuan gambar bisa butuh beberapa menit.
            @set_time_limit(0);
            $bib->isiZip($zip, $kunci);
        });
    }
}
