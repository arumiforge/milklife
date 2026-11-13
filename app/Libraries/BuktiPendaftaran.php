<?php

namespace App\Libraries;

use App\Controllers\Admin\Pengaturan\AcaraController;
use Mpdf\Mpdf;

/**
 * Bukti pendaftaran PDF, kertas F4 tegak dengan margin atas 1,5 cm dan bawah 2 cm (K77, K78;
 * sebelumnya A4 mendatar, rancangan 8, K49).
 *
 * Alamatnya `/bukti/<kode acak>/<ID pendaftaran>` (`alamat()`, K77): kode
 * 24 huruf heksadesimal di `pendaftaran.kode_bukti`, supaya bukti tidak bisa
 * dibuka hanya dengan menebak ID yang berurutan. Kode QR di pojok kanan atas
 * PDF berisi alamat itu (`KodeQr`), dengan logo persegi di tengahnya.
 *
 * Menerima satu pendaftaran dari `DataPendaftaran` dan menyusun HTML-nya
 * (`app/Views/bukti/pdf.php`), PDF-nya (mPDF), dan nama berkasnya. Nama
 * acara, seri, dan tahun diambil dari pengaturan acara. PDF dibuat di memori
 * dan tidak pernah disimpan di disk.
 *
 *     $bukti = new BuktiPendaftaran((new DataPendaftaran())->cariId($id));
 *     $bukti->namaBerkas(); // 'BuktiPendaftaran_MilkLifeAthleticsChallengeSeri2_SDNEGERICONTOH1_2026.pdf'
 *     $bukti->pdf();        // isi berkas PDF
 */
final class BuktiPendaftaran
{
    /**
     * Ukuran kertas F4 (lebar, tinggi) dalam milimeter.
     */
    public const KERTAS = [215, 330];

    /**
     * Kode acak baru untuk `pendaftaran.kode_bukti`.
     */
    public static function kodeBaru(): string
    {
        return bin2hex(random_bytes(12));
    }

    /**
     * Alamat lengkap bukti satu pendaftaran. Pendaftaran yang belum punya
     * kode (misalnya dibuat sebelum kolomnya ada) langsung diberi kode.
     *
     * @param array<string, mixed> $pendaftaran Minimal `id` dan `id_pendaftaran`; `kode_bukti` bila ada.
     */
    public static function alamat(array $pendaftaran): string
    {
        $kode = (string) ($pendaftaran['kode_bukti'] ?? '');

        if (preg_match('/^[0-9a-f]{24}$/D', $kode) !== 1) {
            $db   = db_connect();
            $kode = (string) ($db->table('pendaftaran')->select('kode_bukti')->where('id', (int) $pendaftaran['id'])->get()->getRow()->kode_bukti ?? '');

            if ($kode === '') {
                $kode = self::kodeBaru();
                $db->table('pendaftaran')->where('id', (int) $pendaftaran['id'])->where('kode_bukti', null)->update(['kode_bukti' => $kode]);
            }
        }

        return site_url('bukti/' . $kode . '/' . rawurlencode((string) $pendaftaran['id_pendaftaran']));
    }

    /**
     * Logo PNG yang sudah dibaca (`false` = belum dibaca).
     */
    private string|false|null $logo = false;

    /**
     * @param array<string, mixed> $pendaftaran
     */
    public function __construct(private readonly array $pendaftaran, private readonly Unggahan $unggahan = new Unggahan())
    {
    }

    /**
     * `BuktiPendaftaran_<NamaAcara>Seri<seri>_<NamaSekolah>_<tahun>.pdf`; bagian
     * nama hanya huruf dan angka (K49). Bagian yang kosong dilewati.
     */
    public function namaBerkas(): string
    {
        $seri  = self::rapat(setting('Acara.seri'));
        $bagian = [
            'BuktiPendaftaran',
            self::rapat(AcaraController::namaTampil()) . ($seri !== '' ? 'Seri' . $seri : ''),
            self::rapat($this->pendaftaran['nama_sekolah']),
            self::rapat(setting('Acara.tahun')),
            // ID dan status ikut di nama (K87): berkas Lunas tidak bernama sama
            // dengan berkas Menunggu yang sudah tersimpan di ponsel, jadi
            // penampil PDF tidak membuka berkas lama.
            self::rapat($this->pendaftaran['id_pendaftaran']),
            $this->pendaftaran['status'] === 'lunas' ? 'Lunas' : 'Menunggu',
        ];

        return implode('_', array_filter($bagian, static fn (string $b): bool => $b !== '')) . '.pdf';
    }

    /**
     * HTML bukti, siap diubah mPDF.
     */
    public function html(): string
    {
        $seri  = trim((string) setting('Acara.seri'));
        $tahun = trim((string) setting('Acara.tahun'));

        return view('bukti/pdf', [
            'p'         => $this->pendaftaran,
            'namaAcara' => mb_strtoupper(AcaraController::namaTampil()),
            'seriTahun' => trim(($seri !== '' ? 'Seri ' . $seri : '') . ($tahun !== '' ? ' Tahun ' . $tahun : '')),
            'logo'      => $this->logoPng() !== null,
        ], ['saveData' => false]);
    }

    /**
     * Logo persegi sebagai PNG, atau `null` bila belum ada atau tidak terbaca.
     * mPDF mengubah WebP menjadi JPG (latar transparan jadi hitam), jadi
     * gambar diubah ke PNG yang tetap transparan.
     */
    private function logoPng(): ?string
    {
        if ($this->logo !== false) {
            return $this->logo;
        }

        $jalur  = trim((string) setting('HalamanDepan.logo_persegi'));
        $penuh  = $jalur !== '' && $this->unggahan->ada($jalur) ? $this->unggahan->jalurPenuh($jalur) : null;
        $gambar = $penuh === null ? false : @imagecreatefromstring((string) file_get_contents($penuh));

        if ($gambar === false) {
            return $this->logo = null;
        }

        imagesavealpha($gambar, true);
        ob_start();
        imagepng($gambar);

        return $this->logo = (string) ob_get_clean();
    }

    /**
     * Isi berkas PDF.
     */
    public function pdf(): string
    {
        $mpdf = new Mpdf([
            'format'        => self::KERTAS,
            'orientation'   => 'P',
            'tempDir'       => WRITEPATH . 'cache/mpdf',
            'margin_left'   => 15,
            'margin_right'  => 15,
            'margin_top'    => 15,
            'margin_bottom' => 20,
            'margin_footer' => 8,
        ]);
        if ($this->logoPng() !== null) {
            $mpdf->imageVars['logo'] = $this->logoPng();
        }
        $mpdf->imageVars['qr'] = KodeQr::png(self::alamat($this->pendaftaran), $this->logoPng());
        $mpdf->SetHTMLFooter('<table width="100%" style="font-family: dejavusans; font-size: 7pt; color: #5B6475; border-top: 0.2mm solid #D6DEEA;"><tr>'
            . '<td style="padding-top: 1.5mm;">' . esc($this->pendaftaran['id_pendaftaran']) . ' · dicetak ' . esc(tanggal_id(date('Y-m-d H:i:s'), true)) . '</td>'
            . '<td style="padding-top: 1.5mm; text-align: right;">Halaman {PAGENO} dari {nbpg}</td></tr></table>');
        $mpdf->SetTitle('Bukti Pendaftaran ' . $this->pendaftaran['id_pendaftaran']);
        $mpdf->WriteHTML($this->html());

        return $mpdf->Output('', 'S');
    }

    /**
     * Teks tanpa spasi dan tanda baca (huruf apa adanya).
     */
    private static function rapat(mixed $nilai): string
    {
        return is_scalar($nilai) ? (string) preg_replace('/[^A-Za-z0-9]+/', '', (string) $nilai) : '';
    }
}
