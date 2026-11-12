<?php

namespace App\Libraries\Panel;

use CodeIgniter\HTTP\DownloadResponse;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Berkas Excel (`.xlsx`) dari judul kolom dan baris data (rencana Tahap 6,
 * "Tabel dan ekspor").
 *
 * - Baris pertama berisi judul kolom, ditebalkan dan dibekukan saat digulir.
 * - Lebar kolom menyesuaikan isi.
 * - Jenis kolom:
 *   - `teks` (bawaan): selalu disimpan sebagai teks, jadi NISN, NPSN, nomor
 *     WA, dan ID tidak kehilangan angka nol di depan, dan isian yang diawali
 *     `=` tidak pernah dijalankan sebagai rumus;
 *   - `angka`: bilangan bulat;
 *   - `rupiah`: bilangan bulat rupiah dengan format `"Rp"#,##0`, jadi tetap
 *     bisa dijumlahkan di Excel.
 *   Nilai kosong (`null` atau `''`) menjadi sel kosong.
 *
 * Pemakaian di pengendali (saringan sama dengan JSON-nya, lihat `TabelSisiServer`):
 *
 *     $excel = new EksporExcel('Pendaftaran', [
 *         'id_pendaftaran' => 'ID Pendaftaran',
 *         'nama_sekolah'   => 'Sekolah',
 *         'total'          => ['judul' => 'Total', 'jenis' => 'rupiah'],
 *     ]);
 *     $excel->tambahBaris($tabel->semuaBaris($pembuat, $this->request->getGet()));
 *
 *     return $excel->unduh('Pendaftaran_' . date('Ymd') . '.xlsx');
 *
 * Baris boleh berisi kunci lain; hanya kunci kolom yang ditulis, berurutan
 * sesuai daftar kolom.
 */
final class EksporExcel
{
    public const JENIS = ['teks', 'angka', 'rupiah'];

    public const FORMAT_RUPIAH = '"Rp"#,##0';

    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    /**
     * @var array<string, array{judul: string, jenis: string}>
     */
    private array $kolom = [];

    /**
     * @var list<array<string, mixed>>
     */
    private array $baris = [];

    private string $judulLembar;

    /**
     * @var list<self> lembar berikutnya di berkas yang sama
     */
    private array $lembarLain = [];

    /**
     * @param string                                                     $judulLembar Nama lembar kerja (tab di bawah Excel).
     * @param array<string, array{judul: string, jenis?: string}|string> $kolom       Kunci data => judul, atau `['judul' => …, 'jenis' => …]`.
     */
    public function __construct(string $judulLembar, array $kolom)
    {
        if ($kolom === []) {
            throw new InvalidArgumentException('Daftar kolom Excel kosong.');
        }

        foreach ($kolom as $kunci => $definisi) {
            $definisi = is_string($definisi) ? ['judul' => $definisi] : $definisi;
            $jenis    = $definisi['jenis'] ?? 'teks';

            if (! in_array($jenis, self::JENIS, true)) {
                throw new InvalidArgumentException("Jenis kolom Excel tidak dikenal: {$jenis}.");
            }

            $this->kolom[(string) $kunci] = ['judul' => $definisi['judul'], 'jenis' => $jenis];
        }

        $this->judulLembar = self::judulLembarSah($judulLembar);
    }

    /**
     * @param iterable<array<string, mixed>> $baris
     */
    public function tambahBaris(iterable $baris): self
    {
        foreach ($baris as $satu) {
            $this->baris[] = $satu;
        }

        return $this;
    }

    /**
     * Tambah lembar kerja lain di belakang lembar ini (misalnya rekap keuangan
     * dan tim tanpa pembayar).
     */
    public function tambahLembar(self $lembar): self
    {
        $this->lembarLain[] = $lembar;

        return $this;
    }

    /**
     * Lembar kerja PhpSpreadsheet yang sudah terisi.
     */
    public function lembar(): Spreadsheet
    {
        $buku   = new Spreadsheet();
        $lembar = $buku->getActiveSheet();
        $lembar->setTitle($this->judulLembar);

        $nomor = 1;

        foreach ($this->kolom as $kolom) {
            $lembar->setCellValueExplicit([$nomor, 1], $kolom['judul'], DataType::TYPE_STRING);
            $nomor++;
        }

        $akhir = Coordinate::stringFromColumnIndex(count($this->kolom));
        $lembar->getStyle('A1:' . $akhir . '1')->getFont()->setBold(true);
        $lembar->freezePane('A2');

        $barisKe = 2;

        foreach ($this->baris as $satu) {
            $nomor = 1;

            foreach ($this->kolom as $kunci => $kolom) {
                $this->tulisSel($lembar, $nomor, $barisKe, $satu[$kunci] ?? null, $kolom['jenis']);
                $nomor++;
            }

            $barisKe++;
        }

        $nomor = 1;

        foreach ($this->kolom as $kolom) {
            $huruf = Coordinate::stringFromColumnIndex($nomor);
            $lembar->getColumnDimension($huruf)->setAutoSize(true);

            if ($kolom['jenis'] === 'rupiah' && $barisKe > 2) {
                $lembar->getStyle($huruf . '2:' . $huruf . ($barisKe - 1))->getNumberFormat()->setFormatCode(self::FORMAT_RUPIAH);
            }

            $nomor++;
        }

        foreach ($this->lembarLain as $lain) {
            $bukuLain = $lain->lembar();
            $buku->addExternalSheet($bukuLain->getActiveSheet());
        }

        $buku->setActiveSheetIndex(0);
        $buku->getProperties()->setTitle($this->judulLembar)->setCreator('Panel admin')->setLastModifiedBy('Panel admin');

        return $buku;
    }

    /**
     * Isi berkas `.xlsx`.
     */
    public function isi(): string
    {
        $buku = $this->lembar();
        $penulis = new Xlsx($buku);

        ob_start();

        try {
            $penulis->save('php://output');
        } finally {
            $isi = (string) ob_get_clean();
            $buku->disconnectWorksheets();
        }

        return $isi;
    }

    /**
     * Tanggapan unduhan berkas `.xlsx`.
     */
    public function unduh(string $namaBerkas): DownloadResponse
    {
        $tanggapan = service('response')->download(self::namaBerkasSah($namaBerkas), $this->isi());
        $tanggapan->setContentType(self::MIME);

        return $tanggapan;
    }

    /**
     * Nama berkas tanpa karakter yang mengganggu unduhan, selalu berakhiran `.xlsx`.
     */
    public static function namaBerkasSah(string $nama): string
    {
        $nama = preg_replace('/\.xlsx$/iD', '', trim($nama)) ?? '';
        $nama = preg_replace('/[^A-Za-z0-9_\-]+/', '_', $nama) ?? '';
        $nama = trim($nama, '_');

        return ($nama === '' ? 'Data' : $nama) . '.xlsx';
    }

    /**
     * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $lembar
     */
    private function tulisSel($lembar, int $kolom, int $baris, mixed $nilai, string $jenis): void
    {
        if ($nilai === null || $nilai === '') {
            return;
        }

        if ($jenis !== 'teks' && is_numeric($nilai)) {
            $lembar->setCellValueExplicit([$kolom, $baris], (int) round((float) $nilai), DataType::TYPE_NUMERIC);

            return;
        }

        $lembar->setCellValueExplicit([$kolom, $baris], is_scalar($nilai) ? (string) $nilai : '', DataType::TYPE_STRING);
    }

    /**
     * Judul lembar Excel: paling panjang 31 karakter dan tanpa `\ / ? * : [ ]`.
     */
    private static function judulLembarSah(string $judul): string
    {
        $judul = preg_replace('/[\\\\\/\?\*:\[\]]+/', ' ', $judul) ?? '';
        $judul = trim(preg_replace('/\s+/u', ' ', $judul) ?? '');
        $judul = rtrim(mb_substr($judul, 0, 31));

        return $judul === '' ? 'Data' : $judul;
    }
}
