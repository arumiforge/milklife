<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\Hasil\HasilLomba;
use App\Libraries\Hasil\JadwalPertandingan;
use App\Libraries\Hasil\SeriLintasan;
use App\Libraries\Hasil\StatusNomor;
use App\Libraries\Hasil\UnduhanSeri;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\DownloadResponse;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
use DateTimeImmutable;

/**
 * Seri dan lintasan di panel admin (F9-08, K140 butir 8): `admin/seri`.
 *
 * - `index`: semua nomor per kelompok umur dengan keadaan seri tiap babak,
 *   sakelar tampil untuk umum, dan unduhan daftar seri per hari.
 * - `nomor`: aturan seri nomor itu (lintasan per seri atau peserta per
 *   kelompok, urutan lintasan unggulan), daftar babak, bagan alur babak,
 *   dan unduhan daftar seri nomor itu.
 * - `babak`: bagi otomatis, aturan lolos Q/q, geser peserta antarseri dan
 *   lintasan, kunci daftar seri.
 * - Unduhan PDF/Excel per nomor dan per hari, juga selama halaman publik belum dibuka.
 */
class SeriController extends BaseController
{
    public function index(): string
    {
        $seri  = new SeriLintasan();
        $hasil = new HasilLomba();
        $ku    = $hasil->daftarNomor();

        foreach ($ku as &$k) {
            foreach ($k['nomor'] as &$n) {
                $n['babak_seri'] = $seri->daftarBabak($n['id']);
            }

            unset($n);
        }

        unset($k);

        return view('admin/seri/index', [
            'kelompokUmur' => $ku,
            'peringatan'   => (new StatusNomor())->peringatanNomor(array_merge(...array_column($ku, 'nomor'))),
            'tampil'       => SeriLintasan::tampil(),
            'hari'         => $seri->hariDibagi(),
        ]);
    }

    public function tampil(): RedirectResponse
    {
        // Sakelar komponen mengirim 0 lalu 1 bila dicentang; ambil nilai terakhir.
        $tampil = $this->request->getPost('tampil_seri') === '1';
        setting('HalamanDepan.tampil_seri', $tampil);

        return redirect()->to(site_url('admin/seri') . '#sakelar-seri')
            ->with('pesan_sukses', $tampil ? 'Daftar seri yang sudah dikunci dan bagan alur babak sekarang tampil untuk umum.' : 'Daftar seri dan bagan disembunyikan dari umum.');
    }

    public function nomor(int $id): string
    {
        $nomor = $this->nomorAtau404($id);
        $seri  = new SeriLintasan();

        return view('admin/seri/nomor', [
            'nomor'  => $nomor,
            'peringatan' => (new StatusNomor())->peringatanNomor([$nomor])[$nomor['id']] ?? null,
            'aturan' => $seri->aturanNomor($nomor),
            'babak'  => $seri->daftarBabak($id),
            'bagan'  => $seri->bagan($id, false),
            'galat'  => (array) (session('galat_aturan_seri') ?? []),
            'isian'  => (array) (session('isian_aturan_seri') ?? []),
        ]);
    }

    public function simpanAturan(int $id): RedirectResponse
    {
        $this->nomorAtau404($id);

        $isian = [
            'tempat_per_seri' => $this->request->getPost('tempat_per_seri'),
            'urutan_lintasan' => $this->request->getPost('urutan_lintasan'),
        ];
        $galat = (new SeriLintasan())->simpanAturanNomor($id, $isian);

        if ($galat !== []) {
            return redirect()->to(site_url('admin/seri/nomor/' . $id) . '#aturan-seri')
                ->with('galat_aturan_seri', $galat)
                ->with('isian_aturan_seri', $isian)
                ->with('pesan_galat', 'Aturan seri belum tersimpan. ' . implode(' ', $galat));
        }

        return redirect()->to(site_url('admin/seri/nomor/' . $id) . '#aturan-seri')->with('pesan_sukses', 'Aturan seri tersimpan. Berlaku untuk pembagian berikutnya.');
    }

    public function babak(int $id): string
    {
        $seri  = new SeriLintasan();
        $babak = $seri->babak($id) ?? throw PageNotFoundException::forPageNotFound('Babak tidak ditemukan.');

        $peserta = $seri->peserta($babak);

        return view('admin/seri/babak', [
            'babak'       => $babak,
            'peringatan'  => (new StatusNomor())->peringatanNomor([$babak['nomor']])[$babak['nomor']['id']] ?? null,
            'peserta'     => $peserta,
            'perSeri'     => SeriLintasan::perSeri($peserta),
            'lolos'       => $seri->aturanLolos($babak),
            'tanda'       => $babak['berikut'] !== null && $seri->lengkap($id) ? $seri->lolosDari($babak) : [],
            'selisih'     => $seri->selisihLolos($babak, $peserta),
            'daftarUlang' => $seri->daftarUlangDipakai(),
            'galat'       => (array) (session('galat_geser') ?? []),
            'isian'       => (array) (session('isian_geser') ?? []),
            'galatLolos'  => (array) (session('galat_lolos') ?? []),
        ]);
    }

    public function bagi(int $id): RedirectResponse
    {
        $jumlah = trim((string) $this->request->getPost('jumlah_seri'));
        $benih  = trim((string) $this->request->getPost('benih'));

        if (($jumlah !== '' && preg_match('/^\d{1,2}$/D', $jumlah) !== 1) || ($benih !== '' && preg_match('/^\d{1,10}$/D', $benih) !== 1)) {
            return redirect()->to(site_url('admin/seri/babak/' . $id) . '#bagi-otomatis')
                ->with('pesan_galat', 'Banyak seri dan benih acak harus berupa angka. Kosongkan bila ingin dipilih otomatis.');
        }

        $hasil = (new SeriLintasan())->bagiOtomatis($id, [
            'hanya_hadir' => $this->request->getPost('hanya_hadir') === '1',
            'jumlah_seri' => $jumlah === '' ? null : (int) $jumlah,
            'benih'       => $benih === '' ? null : min((int) $benih, 4_294_967_295),
        ]);

        if (isset($hasil['galat'])) {
            return redirect()->to(site_url('admin/seri/babak/' . $id) . '#bagi-otomatis')->with('pesan_galat', $hasil['galat']);
        }

        return redirect()->to(site_url('admin/seri/babak/' . $id) . '#daftar-seri')
            ->with('pesan_sukses', $hasil['jumlah'] . ' peserta dibagi ke ' . $hasil['seri'] . ' seri, ' . SeriLintasan::MODE[$hasil['mode']] . '. Periksa dan geser bila perlu, lalu kunci daftarnya.');
    }

    public function geser(int $id): RedirectResponse
    {
        $isian = $this->request->getPost('posisi');
        $isian = is_array($isian) ? $isian : [];
        $galat = (new SeriLintasan())->geser($id, $isian);

        if ($galat !== []) {
            $umum = $galat[0] ?? null;
            unset($galat[0]);

            return redirect()->to(site_url('admin/seri/babak/' . $id) . '#daftar-seri')
                ->with('galat_geser', $galat)
                ->with('isian_geser', $isian)
                ->with('pesan_galat', $umum ?? 'Seri dan lintasan belum tersimpan: ' . count($galat) . ' peserta isiannya belum benar. Perbaiki yang ditandai merah, lalu simpan lagi.');
        }

        return redirect()->to(site_url('admin/seri/babak/' . $id) . '#daftar-seri')->with('pesan_sukses', 'Seri dan lintasan tersimpan.');
    }

    public function lolos(int $id): RedirectResponse
    {
        $galat = (new SeriLintasan())->aturLolos($id, [
            'lolos_q'       => $this->request->getPost('lolos_q'),
            'lolos_q_kecil' => $this->request->getPost('lolos_q_kecil'),
        ]);

        if ($galat !== []) {
            return redirect()->to(site_url('admin/seri/babak/' . $id) . '#aturan-lolos')
                ->with('galat_lolos', $galat)
                ->with('pesan_galat', 'Aturan lolos belum tersimpan. ' . implode(' ', $galat));
        }

        return redirect()->to(site_url('admin/seri/babak/' . $id) . '#aturan-lolos')->with('pesan_sukses', 'Aturan lolos tersimpan.');
    }

    public function kunci(int $id, string $aksi): RedirectResponse
    {
        $galat = (new SeriLintasan())->kunci($id, $aksi === 'kunci');

        if ($galat !== null) {
            return redirect()->to(site_url('admin/seri/babak/' . $id) . '#kunci-seri')->with('pesan_galat', $galat);
        }

        return redirect()->to(site_url('admin/seri/babak/' . $id) . '#kunci-seri')
            ->with('pesan_sukses', $aksi === 'kunci' ? 'Daftar seri dikunci. Daftar ini yang dicetak dan tampil untuk umum.' : 'Kunci dibuka. Peserta bisa dibagi ulang atau digeser lagi.');
    }

    public function unduhNomor(int $id, string $jenis): DownloadResponse|ResponseInterface
    {
        $nomor  = $this->nomorAtau404($id);
        $seri   = new SeriLintasan();
        $judul  = $nomor['nama'] . ' – ' . $nomor['kelompok_umur'];
        $daftar = $seri->untukUnduhan(array_column($seri->daftarBabak($id), 'id'));

        return $this->unduh(new UnduhanSeri($judul, $daftar), $nomor['kelompok_umur'] . ' ' . $nomor['nama'], $jenis);
    }

    public function unduhHari(string $tanggal, string $jenis): DownloadResponse|ResponseInterface
    {
        $seri  = new SeriLintasan();
        $babak = $seri->babakHari($tanggal);

        if ($babak === []) {
            throw PageNotFoundException::forPageNotFound('Belum ada babak yang dibagi ke seri di tanggal ini.');
        }

        return $this->unduh(new UnduhanSeri(JadwalPertandingan::namaHari($tanggal), $seri->untukUnduhan($babak)), (new DateTimeImmutable($tanggal))->format('Ymd'), $jenis);
    }

    private function unduh(UnduhanSeri $unduhan, string $nama, string $jenis): DownloadResponse|ResponseInterface
    {
        if ($jenis === 'excel') {
            return $unduhan->excel()->unduh(UnduhanSeri::namaBerkas($nama, 'xlsx'));
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="' . UnduhanSeri::namaBerkas($nama, 'pdf') . '"')
            ->setHeader('Cache-Control', 'no-store')
            ->setBody($unduhan->pdf());
    }

    /**
     * @return array<string, mixed>
     */
    private function nomorAtau404(int $id): array
    {
        return (new HasilLomba())->nomor($id) ?? throw PageNotFoundException::forPageNotFound('Nomor lomba tidak ditemukan.');
    }
}
