<?php

namespace App\Controllers\Admin\Pengaturan;

use App\Controllers\BaseController;
use App\Models\NomorLombaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pengaturan nomor lomba: `admin/pengaturan/nomor-lomba` (F2-04).
 *
 * - Daftar dikelompokkan per kelompok umur dan bisa disaring per kelompok umur
 *   (`?kelompok-umur=<id>`) dan yang belum lengkap saja (`?belum-lengkap=1`).
 * - Nomor beregu yang disimpan lewat formulir wajib lengkap; nomor beregu dari
 *   `data:awal` yang belum lengkap ditandai di daftar.
 * - Nomor yang sudah dipakai atlet atau tim tidak bisa dihapus, dan kelompok
 *   umur, jenis, kategori, dan isian timnya (selain biaya) tidak bisa diubah.
 *
 * Kelompok umur dibaca dengan pembangun kueri (`kelompok_umur` milik F2-03).
 */
class NomorLombaController extends BaseController
{
    private const ALAMAT = 'admin/pengaturan/nomor-lomba';

    private NomorLombaModel $model;

    public function __construct()
    {
        $this->model = model(NomorLombaModel::class);
    }

    public function index(): string
    {
        $kelompokUmur = $this->kelompokUmur();
        $semuaNomor   = $this->model->daftarLengkap();
        $idDipakai    = $this->model->idDipakai();

        $saringKu = (int) $this->request->getGet('kelompok-umur');
        if (! isset($kelompokUmur[$saringKu])) {
            $saringKu = 0;
        }
        $saringBelumLengkap = $this->request->getGet('belum-lengkap') === '1';

        $jumlahPerKu  = array_fill_keys(array_keys($kelompokUmur), 0);
        $jumlahBelum  = 0;
        $kelompok     = [];
        $jumlahTampil = 0;

        foreach ($kelompokUmur as $id => $ku) {
            $kelompok[$id] = $ku + ['nomor' => []];
        }

        foreach ($semuaNomor as $nomor) {
            $kuId                = (int) $nomor['kelompok_umur_id'];
            $nomor['kekurangan'] = NomorLombaModel::kekuranganTim($nomor);
            $nomor['dipakai']    = isset($idDipakai[(int) $nomor['id']]);
            $jumlahPerKu[$kuId]  = ($jumlahPerKu[$kuId] ?? 0) + 1;

            if ($nomor['kekurangan'] !== []) {
                $jumlahBelum++;
            }

            if (($saringKu !== 0 && $kuId !== $saringKu) || ($saringBelumLengkap && $nomor['kekurangan'] === [])) {
                continue;
            }

            $kelompok[$kuId]['nomor'][] = $nomor;
            $jumlahTampil++;
        }

        // Kelompok umur di luar saringan tidak ditampilkan; saat menyaring
        // yang belum lengkap, kelompok umur tanpa nomor yang cocok juga tidak.
        $kelompok = array_filter(
            $kelompok,
            static fn (array $ku, int $id): bool => ($saringKu === 0 || $id === $saringKu)
                && (! $saringBelumLengkap || $ku['nomor'] !== []),
            ARRAY_FILTER_USE_BOTH,
        );

        return view('admin/pengaturan/nomor_lomba/index', [
            'kelompokUmur'       => $kelompokUmur,
            'kelompok'           => $kelompok,
            'jumlahSemua'        => count($semuaNomor),
            'jumlahPerKu'        => $jumlahPerKu,
            'jumlahBelum'        => $jumlahBelum,
            'jumlahTampil'       => $jumlahTampil,
            'saringKu'           => $saringKu,
            'saringBelumLengkap' => $saringBelumLengkap,
        ]);
    }

    public function tambah(): string
    {
        $kuDipilih = (string) (int) $this->request->getGet('kelompok-umur');

        return $this->tampilkanFormulir([
            'kelompok_umur_id' => $kuDipilih === '0' ? '' : $kuDipilih,
            'nama'             => '',
            'jenis'            => 'individu',
            'kategori'         => 'putra',
            'jumlah_inti'      => '',
            'batas_anggota'    => '',
            'jumlah_putra'     => '',
            'jumlah_putri'     => '',
            'biaya_beregu'     => '',
            'urutan'           => '',
            'aktif'            => '1',
        ]);
    }

    public function buat(): ResponseInterface|string
    {
        $isian = NomorLombaModel::rapikanIsian($this->isianKiriman());
        $galat = $this->periksa($isian);

        if ($galat !== []) {
            return $this->tampilkanFormulir($isian, null, $galat);
        }

        $data = NomorLombaModel::untukDisimpan($isian);
        $data['urutan'] ??= $this->model->urutanBerikutnya($data['kelompok_umur_id']);

        $id = (int) $this->model->insert($data);

        return $this->kembaliKeDaftar($data, $id, 'Nomor ' . $this->namaDiKu($data) . ' tersimpan.');
    }

    public function ubah(int $id): string
    {
        $nomor = $this->cari($id);

        return $this->tampilkanFormulir(NomorLombaModel::isianDariBaris($nomor), $nomor);
    }

    public function perbarui(int $id): ResponseInterface|string
    {
        $nomor = $this->cari($id);
        $isian = NomorLombaModel::rapikanIsian($this->isianKiriman());
        $galat = $this->periksa($isian, $nomor);

        if ($galat !== []) {
            return $this->tampilkanFormulir($isian, $nomor, $galat);
        }

        $data = NomorLombaModel::untukDisimpan($isian);

        // Urutan kosong: tetap di tempatnya, atau paling akhir bila pindah kelompok umur.
        if ($data['urutan'] === null) {
            $data['urutan'] = $data['kelompok_umur_id'] === (int) $nomor['kelompok_umur_id']
                ? (int) $nomor['urutan']
                : $this->model->urutanBerikutnya($data['kelompok_umur_id']);
        }

        $this->model->update($id, $data);

        return $this->kembaliKeDaftar($data, $id, 'Perubahan nomor ' . $this->namaDiKu($data) . ' tersimpan.');
    }

    public function hapus(int $id): RedirectResponse
    {
        $nomor = $this->model->find($id);

        if ($nomor === null) {
            return redirect()->to(self::ALAMAT)->with('pesan_galat', 'Nomor lomba itu tidak ditemukan; mungkin sudah dihapus. Periksa daftar di bawah.');
        }

        $nama = $this->namaDiKu($nomor);

        if ($this->model->dipakai($id)) {
            return redirect()->to(self::ALAMAT)->with(
                'pesan_galat',
                "Nomor {$nama} sudah dipakai atlet atau tim, jadi tidak bisa dihapus. Matikan saja supaya tidak muncul di pendaftaran.",
            );
        }

        $this->model->delete($id);

        return redirect()->to(site_url(self::ALAMAT) . '?kelompok-umur=' . (int) $nomor['kelompok_umur_id'])
            ->with('pesan_sukses', "Nomor {$nama} sudah dihapus.");
    }

    /**
     * Kelompok umur yang ada, berurutan seperti di halaman kelompok umur.
     *
     * @return array<int, array{id: int, nama: string, aktif: bool}>
     */
    private function kelompokUmur(): array
    {
        $hasil = [];

        $baris = db_connect()->table('kelompok_umur')
            ->select('id, nama, aktif')
            ->orderBy('urutan', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()
            ->getResultArray();

        foreach ($baris as $ku) {
            $hasil[(int) $ku['id']] = ['id' => (int) $ku['id'], 'nama' => (string) $ku['nama'], 'aktif' => (int) $ku['aktif'] === 1];
        }

        return $hasil;
    }

    /**
     * @return array<string, mixed>
     */
    private function isianKiriman(): array
    {
        $kiriman = [];

        foreach (['kelompok_umur_id', 'nama', 'jenis', 'kategori', 'jumlah_inti', 'batas_anggota', 'jumlah_putra', 'jumlah_putri', 'biaya_beregu', 'urutan', 'aktif'] as $kotak) {
            $kiriman[$kotak] = $this->request->getPost($kotak);
        }

        return $kiriman;
    }

    /**
     * Pemeriksaan isian ditambah aturan yang butuh basis data: nama kembar dan
     * nomor yang sudah dipakai.
     *
     * @param array<string, string>     $isian
     * @param array<string, mixed>|null $lama  Baris yang diubah, `null` untuk nomor baru.
     *
     * @return array<string, string>
     */
    private function periksa(array $isian, ?array $lama = null): array
    {
        $kelompokUmur = $this->kelompokUmur();
        $galat        = NomorLombaModel::periksaIsian($isian, array_keys($kelompokUmur));

        if ($lama !== null && $this->model->dipakai((int) $lama['id'])) {
            $sebab = 'Nomor ini sudah dipakai atlet atau tim, jadi ';

            foreach (NomorLombaModel::TERKUNCI_BILA_DIPAKAI as $kolom => $alasan) {
                $nilaiLama = (string) ($lama[$kolom] ?? '');

                // Isian tim yang masih kosong (data awal) boleh dilengkapi.
                if ($nilaiLama !== '' && $isian[$kolom] !== $nilaiLama) {
                    $galat[$kolom] = $sebab . sprintf($alasan, $nilaiLama);
                }
            }
        }

        if (array_intersect_key($galat, array_flip(['kelompok_umur_id', 'nama', 'kategori'])) === []
            && $this->model->adaKembar((int) $isian['kelompok_umur_id'], $isian['nama'], $isian['kategori'], $lama === null ? null : (int) $lama['id'])) {
            $ku            = $kelompokUmur[(int) $isian['kelompok_umur_id']]['nama'];
            $galat['nama'] = "Nomor {$isian['nama']} {$isian['kategori']} sudah ada di {$ku}. Ganti nama atau kategorinya, atau ubah nomor yang sudah ada.";
        }

        return $galat;
    }

    /**
     * @param array<string, string>     $isian
     * @param array<string, mixed>|null $nomor Baris yang diubah, `null` untuk nomor baru.
     * @param array<string, string>     $galat
     */
    private function tampilkanFormulir(array $isian, ?array $nomor = null, array $galat = []): string
    {
        return view('admin/pengaturan/nomor_lomba/formulir', [
            'isian'        => $isian,
            'nomor'        => $nomor,
            'dipakai'      => $nomor !== null && $this->model->dipakai((int) $nomor['id']),
            'kekurangan'   => $nomor !== null ? NomorLombaModel::kekuranganTim($nomor) : [],
            'kelompokUmur' => $this->kelompokUmur(),
            'galat'        => $galat,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function cari(int $id): array
    {
        $nomor = $this->model->find($id);

        if ($nomor === null) {
            throw PageNotFoundException::forPageNotFound('Nomor lomba tidak ditemukan.');
        }

        return $nomor;
    }

    /**
     * Kembali ke daftar, disaring ke kelompok umur nomor itu dan digulir ke barisnya.
     *
     * @param array<string, mixed> $data
     */
    private function kembaliKeDaftar(array $data, int $id, string $pesan): RedirectResponse
    {
        return redirect()->to(site_url(self::ALAMAT) . '?kelompok-umur=' . (int) $data['kelompok_umur_id'] . '#nomor-' . $id)
            ->with('pesan_sukses', $pesan);
    }

    /**
     * "Lari 60 m putra di KU 10".
     *
     * @param array<string, mixed> $data
     */
    private function namaDiKu(array $data): string
    {
        $ku = $this->kelompokUmur()[(int) $data['kelompok_umur_id']]['nama'] ?? '';

        return NomorLombaModel::namaLengkap($data) . ($ku !== '' ? ' di ' . $ku : '');
    }
}
