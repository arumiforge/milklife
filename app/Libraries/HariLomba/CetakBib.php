<?php

namespace App\Libraries\HariLomba;

use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;

/**
 * Cetak nomor atlet (BIB) massal (F8-03, K124, asumsi 5 K125).
 *
 * Hanya atlet dari pendaftaran Lunas yang sudah punya nomor atlet, urut
 * nomor atlet. Satu BIB = setengah A4 mendatar, dua BIB per halaman A4 tegak
 * (panitia memotongnya di garis tengah). Isi tiap BIB: nomor atlet besar,
 * nama atlet, nama sekolah, kelompok umur, dan nama acara.
 *
 * Per sekolah (K137): sekolah = NPSN (K109, K124); pendaftaran tanpa NPSN
 * menjadi sekolah sendiri dengan kunci `p<id pendaftaran>`. Unduhan per
 * sekolah berupa PDF, atau ZIP berisi gambar JPG per atlet (`GambarBib`)
 * dan PDF sekolah itu. ZIP semua sekolah berisi satu folder per sekolah.
 * Latar, kepala, dan warna tulisan dari `TampilanBib`.
 *
 *     $bib = new CetakBib();
 *     $bib->kelompokUmur();      // [['id' => 2, 'nama' => 'KU 10', 'atlet' => 40], ...]
 *     $bib->atlet(2);            // atlet KU 10; null = semua kelompok umur
 *     $bib->pdf(2);              // isi berkas PDF
 *     $bib->namaBerkas(2);       // 'BIB_KU10_MilkLifeAthleticsChallenge_2026.pdf'
 *     $bib->sekolah();           // [['kunci' => '20318001', 'nama' => 'SD …', 'npsn' => '20318001', 'atlet' => 12], ...]
 *     $bib->atletSekolah('20318001');
 *     $bib->pdfSekolah('20318001');
 *     $bib->isiZip($zip, '20318001');   // ZipAlir; null = semua sekolah, satu folder per sekolah
 */
final class CetakBib
{
    /**
     * Kunci sekolah: NPSN, atau `p<id pendaftaran>` bila tanpa NPSN.
     */
    private const KUNCI_SEKOLAH = "COALESCE(NULLIF(p.npsn, ''), CONCAT('p', p.id))";

    private BaseConnection $db;

    private ?TampilanBib $tampilan;

    public function __construct(?BaseConnection $db = null, ?TampilanBib $tampilan = null)
    {
        $this->db       = $db ?? db_connect();
        $this->tampilan = $tampilan;
    }

    /**
     * Kelompok umur berurutan beserta banyak atlet ber-BIB dari pendaftaran Lunas.
     *
     * @return list<array{id: int, nama: string, atlet: int}>
     */
    public function kelompokUmur(): array
    {
        $jumlah = array_column($this->kueriAtlet(null)->select('a.kelompok_umur_id, COUNT(*) AS jumlah')->groupBy('a.kelompok_umur_id')->get()->getResultArray(), 'jumlah', 'kelompok_umur_id');

        return array_map(static fn (array $ku): array => [
            'id'    => (int) $ku['id'],
            'nama'  => (string) $ku['nama'],
            'atlet' => (int) ($jumlah[$ku['id']] ?? 0),
        ], $this->db->table('kelompok_umur')->select('id, nama')->orderBy('urutan')->orderBy('id')->get()->getResultArray());
    }

    public function namaKelompokUmur(int $id): ?string
    {
        $baris = $this->db->table('kelompok_umur')->select('nama')->where('id', $id)->get()->getRow();

        return $baris === null ? null : (string) $baris->nama;
    }

    /**
     * @return list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}>
     */
    public function atlet(?int $kelompokUmurId): array
    {
        return self::bentukAtlet($this->kueriAtlet($kelompokUmurId)
            ->select('a.nomor_dada, a.nama, p.nama_sekolah, ku.nama AS kelompok_umur')
            ->orderBy('a.nomor_dada')
            ->get()->getResultArray());
    }

    public function namaBerkas(?int $kelompokUmurId): string
    {
        $ku = $kelompokUmurId === null ? 'Semua' : PdfHariLomba::rapat($this->namaKelompokUmur($kelompokUmurId));

        return 'BIB_' . $ku . PdfHariLomba::akhiranBerkas() . '.pdf';
    }

    /**
     * Sekolah yang punya atlet ber-BIB dari pendaftaran Lunas, urut nama.
     *
     * @return list<array{kunci: string, nama: string, npsn: string, atlet: int}>
     */
    public function sekolah(): array
    {
        $baris = $this->kueriAtlet(null)
            ->select(self::KUNCI_SEKOLAH . ' AS kunci, MAX(p.nama_sekolah) AS nama, MAX(p.npsn) AS npsn, COUNT(*) AS atlet', false)
            ->groupBy('kunci')
            ->orderBy('nama')->orderBy('kunci')
            ->get()->getResultArray();

        return array_map(static fn (array $s): array => [
            'kunci' => (string) $s['kunci'],
            'nama'  => (string) $s['nama'],
            'npsn'  => (string) $s['npsn'],
            'atlet' => (int) $s['atlet'],
        ], $baris);
    }

    /**
     * Atlet ber-BIB satu sekolah (NPSN, atau `p<id>` untuk pendaftaran tanpa
     * NPSN), urut nomor atlet. Kunci tidak dikenal = daftar kosong.
     *
     * @return list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}>
     */
    public function atletSekolah(string $kunci): array
    {
        if (preg_match('/^(\d{8}|p\d{1,10})$/', $kunci) !== 1) {
            return [];
        }

        $kueri = $this->kueriAtlet(null);

        if ($kunci[0] === 'p') {
            $kueri->where('p.id', (int) substr($kunci, 1))->groupStart()->where('p.npsn', null)->orWhere('p.npsn', '')->groupEnd();
        } else {
            $kueri->where('p.npsn', $kunci);
        }

        return self::bentukAtlet($kueri->select('a.nomor_dada, a.nama, p.nama_sekolah, ku.nama AS kelompok_umur')->orderBy('a.nomor_dada')->get()->getResultArray());
    }

    /**
     * Nama berkas per sekolah: `BIB_SDNEGERI1KARANGAN_MilkLife…_2026.pdf` atau `.zip`.
     */
    public static function namaBerkasSekolah(string $namaSekolah, string $ekstensi): string
    {
        return 'BIB_' . substr(PdfHariLomba::rapat($namaSekolah), 0, 60) . PdfHariLomba::akhiranBerkas() . '.' . $ekstensi;
    }

    /**
     * Nama berkas ZIP semua sekolah.
     */
    public static function namaBerkasSemua(): string
    {
        return 'BIB_SemuaSekolah' . PdfHariLomba::akhiranBerkas() . '.zip';
    }

    public function pdfSekolah(string $kunci): string
    {
        return $this->pdfDari($this->atletSekolah($kunci));
    }

    /**
     * Isi ZIP: gambar JPG per atlet dan PDF sekolah. Satu sekolah: berkas
     * langsung di akar ZIP; `null` = semua sekolah, satu folder per sekolah
     * (`<nama sekolah> - <NPSN>/`). Tiap berkas langsung dikirim lewat
     * `ZipAlir`, jadi memori tidak membengkak untuk ribuan atlet.
     *
     * @return array{sekolah: int, atlet: int}
     */
    public function isiZip(ZipAlir $zip, ?string $kunci = null): array
    {
        $daftar = $kunci === null ? $this->sekolah() : array_values(array_filter($this->sekolah(), static fn (array $s): bool => $s['kunci'] === $kunci));

        $tampilan = $this->tampilan();
        $logo     = PdfHariLomba::logoPng();
        $latar    = $tampilan->latarJpg();
        $gambar   = new GambarBib($tampilan, PdfHariLomba::namaAcara(), PdfHariLomba::seriTahun(), $logo, $latar);
        $jumlah   = 0;

        foreach ($daftar as $s) {
            $atlet = $this->atletSekolah($s['kunci']);
            $awal  = $kunci === null ? self::namaFolder($s['nama'] . ($s['npsn'] !== '' ? ' - ' . $s['npsn'] : '')) . '/' : '';

            foreach ($atlet as $a) {
                $zip->tambah($awal . self::namaFolder($a['nomor_dada'] . ' - ' . mb_strtoupper($a['nama'])) . '.jpg', $gambar->jpg($a));
                $jumlah++;
            }

            $zip->tambah($awal . self::namaBerkasSekolah($s['nama'], 'pdf'), $this->pdfDari($atlet, $logo, $latar));
        }

        if ($daftar === []) {
            $zip->tambah('BACA.txt', 'Belum ada atlet dengan nomor atlet (BIB) dari pendaftaran Lunas.');
        }

        return ['sekolah' => count($daftar), 'atlet' => $jumlah];
    }

    /**
     * Nama folder atau berkas yang aman di semua sistem: huruf, angka, spasi,
     * titik, koma, tanda kurung, dan tanda hubung; paling panjang 100 huruf.
     */
    public static function namaFolder(string $nama): string
    {
        $nama = (string) preg_replace('/[^A-Za-z0-9 .,()\-]+/', '', $nama);
        $nama = trim((string) preg_replace('/\s+/', ' ', $nama), ' .');

        return $nama === '' ? 'Tanpa nama' : rtrim(substr($nama, 0, 100), ' .');
    }

    /**
     * @param list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}> $atlet
     */
    public static function html(array $atlet, string $namaAcara, string $seriTahun, bool $logo, ?TampilanBib $tampilan = null, bool $latar = false): string
    {
        $tampilan ??= TampilanBib::buat(null, true, 'gelap');

        return view('admin/hari_lomba/bib_pdf', [
            'atlet'     => $atlet,
            'namaAcara' => $namaAcara,
            'seriTahun' => $seriTahun,
            'logo'      => $logo,
            'latar'     => $latar,
            'kepala'    => $tampilan->kepala,
            'warna'     => $tampilan->warna,
        ], ['saveData' => false]);
    }

    public function pdf(?int $kelompokUmurId): string
    {
        return $this->pdfDari($this->atlet($kelompokUmurId));
    }

    /**
     * PDF dari daftar atlet. Logo dan latar boleh diberikan supaya tidak
     * dibaca ulang dari disk untuk tiap sekolah.
     *
     * @param list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}> $atlet
     */
    private function pdfDari(array $atlet, ?string $logo = null, ?string $latar = null): string
    {
        $tampilan = $this->tampilan();
        $logo   ??= PdfHariLomba::logoPng();
        $latar  ??= $tampilan->latarJpg();

        $mpdf = PdfHariLomba::mpdf([
            'format'        => 'A4',
            'margin_left'   => 0,
            'margin_right'  => 0,
            'margin_top'    => 0,
            'margin_bottom' => 0,
        ]);
        if ($logo !== null) {
            $mpdf->imageVars['logo'] = $logo;
        }

        if ($latar !== null) {
            $mpdf->imageVars['latar'] = $latar;
        }

        $mpdf->SetTitle('Nomor Atlet (BIB)');
        $mpdf->WriteHTML(self::html($atlet, PdfHariLomba::namaAcara(), PdfHariLomba::seriTahun(), $logo !== null, $tampilan, $latar !== null));

        return $mpdf->Output('', 'S');
    }

    private function tampilan(): TampilanBib
    {
        return $this->tampilan ??= TampilanBib::dariPengaturan();
    }

    /**
     * @param list<array<string, mixed>> $baris
     *
     * @return list<array{nomor_dada: string, nama: string, sekolah: string, kelompok_umur: string}>
     */
    private static function bentukAtlet(array $baris): array
    {
        return array_map(static fn (array $a): array => [
            'nomor_dada'    => (string) $a['nomor_dada'],
            'nama'          => (string) $a['nama'],
            'sekolah'       => (string) $a['nama_sekolah'],
            'kelompok_umur' => (string) $a['kelompok_umur'],
        ], $baris);
    }

    /**
     * Atlet ber-BIB dari pendaftaran Lunas.
     */
    private function kueriAtlet(?int $kelompokUmurId): BaseBuilder
    {
        $kueri = $this->db->table('atlet a')
            ->join('pendaftaran p', 'p.id = a.pendaftaran_id')
            ->join('kelompok_umur ku', 'ku.id = a.kelompok_umur_id')
            ->where('p.status', 'lunas')
            ->where('a.nomor_dada IS NOT NULL', null, false);

        if ($kelompokUmurId !== null) {
            $kueri->where('a.kelompok_umur_id', $kelompokUmurId);
        }

        return $kueri;
    }
}
