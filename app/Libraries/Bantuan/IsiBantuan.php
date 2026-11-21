<?php

namespace App\Libraries\Bantuan;

use App\Libraries\IsiHalamanDepan;
use App\Models\FaqModel;
use Throwable;

/**
 * Data halaman Bantuan (`/bantuan`) dan laci bantuan wizard (F9-04).
 *
 * - `kategori()`: kategori berisi tanya-jawab aktif, jawaban sudah HTML
 *   (`JawabanFaq`) dan teks polos untuk pencarian langsung di peramban.
 * - `untukLangkah()`: tanya-jawab kategori satu langkah wizard
 *   (`FaqModel::KATEGORI_LANGKAH`).
 * - `panduan()`: langkah pendaftaran bergambar; gambar G9-03 hanya dipakai bila
 *   berkasnya ada (K41).
 * - `kontak()`: tombol WA tanya admin dan narahubung dari Pengaturan.
 *
 * Basis data yang tidak bisa dibaca menghasilkan daftar kosong, bukan halaman galat.
 */
final class IsiBantuan
{
    /**
     * Folder ilustrasi kategori (G9-07) dan panduan (G9-03), relatif `public/aset/`.
     */
    public const FOLDER_KATEGORI = 'gambar/bantuan/';

    public const FOLDER_PANDUAN = 'gambar/panduan/';

    /**
     * Langkah panduan bergambar: [berkas G9-03, judul, keterangan]. Ikon pengganti
     * ditulis di tampilan (`bantuan/index.php`) supaya ikut sprite Lucide.
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    public const PANDUAN = [
        ['langkah-1-sekolah.webp', 'Isi data sekolah', 'Pilih jenjang dan kabupaten, ketik NPSN, lalu tekan Cari.'],
        ['langkah-2-ofisial.webp', 'Isi ofisial', 'Tulis nama dan nomor WA pendamping, lalu pasang fotonya dari HP.'],
        ['langkah-3-atlet.webp', 'Tambahkan atlet', 'Isi data atlet, pilih nomor lombanya, lalu tekan Tambahkan ke daftar.'],
        ['langkah-4-biaya.webp', 'Periksa biaya', 'Total biaya dihitung otomatis. Periksa rinciannya.'],
        ['langkah-5-transfer.webp', 'Transfer dan kirim', 'Transfer, pilih foto bukti transfer, lalu tekan Kirim Pendaftaran.'],
        ['langkah-6-cek.webp', 'Cetak bukti', 'Cetak bukti pendaftaran dan pantau statusnya di Cek Pendaftaran.'],
        ['hari-lomba-qr.webp', 'Daftar ulang', 'Di hari lomba, tunjukkan kode QR di bukti pendaftaran ke panitia.'],
    ];

    private JawabanFaq $jawaban;

    public function __construct(?JawabanFaq $jawaban = null)
    {
        $this->jawaban = $jawaban ?? new JawabanFaq();
    }

    /**
     * Kategori yang punya tanya-jawab aktif, berurutan seperti `FaqModel::KATEGORI`.
     *
     * @return list<array{kunci: string, id: string, label: string, keterangan: string, ikon: string, gambar: string|null, butir: list<array{id: int, pertanyaan: string, jawaban: string, cari: string}>}>
     */
    public function kategori(): array
    {
        try {
            $perKategori = model(FaqModel::class)->semuaPerKategori(true);
        } catch (Throwable $e) {
            log_message('error', 'Tanya-jawab Bantuan tidak bisa dibaca: ' . $e->getMessage());

            return [];
        }

        $hasil = [];

        foreach (FaqModel::KATEGORI as $kunci => $kategori) {
            $butir = array_map($this->butir(...), $perKategori[$kunci] ?? []);

            if ($butir === []) {
                continue;
            }

            $hasil[] = [
                'kunci'      => $kunci,
                'id'         => 'kategori-' . str_replace('_', '-', $kunci),
                'label'      => $kategori['label'],
                'keterangan' => $kategori['keterangan'],
                'ikon'       => $kategori['ikon'],
                'gambar'     => self::gambar(self::FOLDER_KATEGORI . $kategori['gambar']),
                'butir'      => $butir,
            ];
        }

        return $hasil;
    }

    /**
     * Tanya-jawab aktif untuk laci bantuan satu langkah wizard.
     *
     * @return list<array{id: int, pertanyaan: string, jawaban: string, cari: string}>
     */
    public function untukLangkah(int $langkah): array
    {
        try {
            $baris = model(FaqModel::class)->aktifUntuk(FaqModel::KATEGORI_LANGKAH[$langkah] ?? []);
        } catch (Throwable $e) {
            log_message('error', 'Tanya-jawab Bantuan tidak bisa dibaca: ' . $e->getMessage());

            return [];
        }

        return array_map($this->butir(...), $baris);
    }

    /**
     * Langkah panduan; `gambar` alamat gambar G9-03 atau `null` bila belum ada.
     *
     * @return list<array{nomor: int, judul: string, keterangan: string, gambar: string|null}>
     */
    public static function panduan(): array
    {
        $hasil = [];

        foreach (self::PANDUAN as $i => [$berkas, $judul, $keterangan]) {
            $hasil[] = [
                'nomor'      => $i + 1,
                'judul'      => $judul,
                'keterangan' => $keterangan,
                'gambar'     => self::gambar(self::FOLDER_PANDUAN . $berkas),
            ];
        }

        return $hasil;
    }

    /**
     * Gambar panduan satu langkah wizard (1–5), atau `null` bila belum ada.
     */
    public static function gambarLangkah(int $langkah): ?string
    {
        $berkas = self::PANDUAN[$langkah - 1][0] ?? null;

        return $berkas === null ? null : self::gambar(self::FOLDER_PANDUAN . $berkas);
    }

    /**
     * Kontak panitia: alamat WA tanya admin (atau `null`) dan narahubung.
     *
     * @return array{wa_tanya: string|null, narahubung: list<array{nama: string, nomor: string, alamat: string}>}
     */
    public static function kontak(): array
    {
        $isi = new IsiHalamanDepan();

        try {
            $waTanya = $isi->waTanya();
        } catch (Throwable) {
            $waTanya = null;
        }

        return ['wa_tanya' => $waTanya, 'narahubung' => $isi->narahubung()];
    }

    /**
     * Alamat gambar di `public/aset/` bila berkasnya ada, selain itu `null`.
     */
    public static function gambar(string $jalur): ?string
    {
        return is_file(FCPATH . 'aset/' . $jalur) ? aset($jalur) : null;
    }

    /**
     * @param array<string, mixed> $baris
     *
     * @return array{id: int, pertanyaan: string, jawaban: string, cari: string}
     */
    private function butir(array $baris): array
    {
        $pertanyaan = (string) $baris['pertanyaan'];
        $jawaban    = (string) $baris['jawaban'];

        return [
            'id'         => (int) $baris['id'],
            'pertanyaan' => $pertanyaan,
            'jawaban'    => $this->jawaban->html($jawaban),
            'cari'       => mb_strtolower($pertanyaan . ' ' . $this->jawaban->teks($jawaban)),
        ];
    }
}
