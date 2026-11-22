<?php

namespace App\Libraries\Bantuan;

use App\Libraries\Hasil\Klasemen;
use App\Libraries\Hasil\MetodeKlasemen;
use App\Libraries\IsiHalamanDepan;
use App\Libraries\MarkdownSederhana;

/**
 * Jawaban FAQ (F9-04) dari Markdown sederhana ke HTML, dengan isian otomatis
 * dari pengaturan.
 *
 * Jawaban tersimpan boleh memuat isian otomatis dalam kurung kurawal, misalnya
 * `{tutup_pendaftaran}`. Saat ditampilkan, isian itu diganti nilai pengaturan
 * yang berlaku, sehingga jawaban tetap benar walau panitia mengubah tanggal,
 * rekening, atau kelompok umur. Daftar isian dan keterangannya di `ISIAN`;
 * isian yang tidak dikenal dibiarkan apa adanya.
 *
 * Markdown diubah `MarkdownSederhana` (tebal, miring, daftar, tabel, tautan).
 * Tautan dirapikan: `https://`, `http://`, `mailto:`, `tel:`, `#…`, dan alamat
 * di web ini (`/cek-pendaftaran` atau `cek-pendaftaran`) dipertahankan;
 * tautan lain (misalnya `javascript:`) dibuang dan teksnya saja yang tampil.
 * Tautan ke luar dibuka di tab baru.
 *
 *     $jawaban = new JawabanFaq();
 *     $html    = $jawaban->html("Pendaftaran ditutup **{tutup_pendaftaran}**.");
 *     // '<p>Pendaftaran ditutup <strong>30 November 2026 pukul 23.59</strong>.</p>'
 */
final class JawabanFaq
{
    /**
     * Isian otomatis yang dikenal => keterangan untuk admin.
     *
     * @var array<string, string>
     */
    public const ISIAN = [
        'nama_acara'              => 'Nama acara beserta seri dan tahun',
        'tempat'                  => 'Tempat lomba',
        'tanggal_lomba'           => 'Tanggal lomba',
        'buka_pendaftaran'        => 'Tanggal dan jam pendaftaran dibuka',
        'tutup_pendaftaran'       => 'Tanggal dan jam pendaftaran ditutup',
        'rekening'                => 'Bank, nomor rekening, dan nama pemilik rekening',
        'nomor_individu_maksimal' => 'Nomor individu paling banyak per atlet',
        'ofisial_minimal'         => 'Ofisial paling sedikit',
        'ofisial_maksimal'        => 'Ofisial paling banyak',
        'daftar_kelompok_umur'    => 'Daftar kelompok umur aktif: jenjang dan tahun lahir',
        'daftar_biaya'            => 'Daftar biaya individu dan beregu per kelompok umur. Bila "Tampilkan biaya di halaman depan" mati, yang tampil kalimat bahwa rincian biaya ada di langkah Biaya (K143)',
        'cara_juara_umum'         => 'Kalimat susunan klasemen per kelompok umur dan cara juara umum ditentukan, sesuai Pengaturan > Hasil (F9-11, F11-02)',
    ];

    /**
     * Pengganti isian yang pengaturannya masih kosong.
     */
    public const BELUM_DIATUR = '(belum diumumkan panitia)';

    /**
     * Pengganti `{daftar_biaya}` selama rincian biaya disembunyikan dari
     * halaman depan (`HalamanDepan.tampilkan_biaya` mati, K74, K143).
     */
    public const BIAYA_TERSEMBUNYI = 'Rincian biaya tampil di langkah **Biaya** saat mendaftar, setelah atlet dan nomor lombanya diisi.';

    /**
     * Nilai isian, diisi sekali saat pertama dipakai.
     *
     * @var array<string, string>|null
     */
    private ?array $nilai = null;

    /**
     * @param array<string, string>|null $nilai Nilai isian (untuk uji); bawaan dari pengaturan.
     */
    public function __construct(?array $nilai = null)
    {
        $this->nilai = $nilai;
    }

    /**
     * HTML jawaban siap tampil (sudah di-escape).
     */
    public function html(string $jawaban): string
    {
        return self::rapikanTautan(MarkdownSederhana::html($this->isi($jawaban)));
    }

    /**
     * Teks polos jawaban (untuk pencarian): isian diganti, tanda Markdown dibuang.
     */
    public function teks(string $jawaban): string
    {
        $teks = $this->isi($jawaban);
        $teks = (string) preg_replace('/\[([^\]]+)\]\([^)\s]+\)/u', '$1', $teks);
        $teks = str_replace(['**', '`', '|'], ['', '', ' '], $teks);
        $teks = (string) preg_replace('/^\s*(?:[-*]|\d+\.|#{1,6})\s+/mu', '', $teks);

        return trim((string) preg_replace('/\s+/u', ' ', $teks));
    }

    /**
     * Ganti isian otomatis yang dikenal dengan nilainya.
     */
    public function isi(string $jawaban): string
    {
        if (! str_contains($jawaban, '{')) {
            return $jawaban;
        }

        $nilai = $this->nilai();

        return (string) preg_replace_callback(
            '/\{([a-z_]+)\}/',
            static fn (array $m): string => array_key_exists($m[1], $nilai) ? $nilai[$m[1]] : $m[0],
            $jawaban,
        );
    }

    /**
     * Isian otomatis di jawaban yang tidak dikenal (untuk peringatan di formulir admin).
     *
     * @return list<string>
     */
    public static function isianTidakDikenal(string $jawaban): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $jawaban, $cocok);

        return array_values(array_unique(array_diff($cocok[1], array_keys(self::ISIAN))));
    }

    /**
     * Nilai semua isian otomatis dari pengaturan yang berlaku.
     *
     * @return array<string, string>
     */
    public function nilai(): array
    {
        if ($this->nilai !== null) {
            return $this->nilai;
        }

        helper('aplikasi');

        $isi     = new IsiHalamanDepan();
        $acara   = $isi->acara();
        $isiKu   = $isi->kelompokUmur();
        $kosong  = static fn (string $teks): string => trim($teks) !== '' ? $teks : self::BELUM_DIATUR;
        $bank    = trim((string) setting('Pembayaran.bank'));
        $rek     = trim((string) setting('Pembayaran.nomor_rekening'));
        $pemilik = trim((string) setting('Pembayaran.pemilik_rekening'));

        return $this->nilai = [
            'nama_acara'              => $kosong(trim($acara['nama'] . ' ' . $acara['seri_tahun'])),
            'tempat'                  => $kosong($acara['tempat']),
            'tanggal_lomba'           => $kosong($acara['tanggal']),
            'buka_pendaftaran'        => $kosong(tanggal_id(setting('Pendaftaran.mulai'), true)),
            'tutup_pendaftaran'       => $kosong(tanggal_id(setting('Pendaftaran.akhir'), true)),
            'rekening'                => $bank !== '' && $rek !== '' ? "**{$bank}** nomor **{$rek}**" . ($pemilik !== '' ? " atas nama **{$pemilik}**" : '') : self::BELUM_DIATUR,
            'nomor_individu_maksimal' => (string) (int) setting('Pendaftaran.nomor_individu_maksimal'),
            'ofisial_minimal'         => (string) (int) setting('Pendaftaran.penanggung_jawab_minimal'),
            'ofisial_maksimal'        => (string) (int) setting('Pendaftaran.penanggung_jawab_maksimal'),
            'daftar_kelompok_umur'    => self::daftarKelompokUmur($isiKu),
            // Biaya hanya dirinci bila panitia menampilkannya di halaman depan (K74, K143).
            'daftar_biaya'            => (bool) setting('HalamanDepan.tampilkan_biaya') ? self::daftarBiaya($isiKu) : self::BIAYA_TERSEMBUNYI,
            'cara_juara_umum'         => Klasemen::teksSusunan(MetodeKlasemen::susunan()) . ' ' . Klasemen::keterangan(MetodeKlasemen::metode(), MetodeKlasemen::poin()),
        ];
    }

    /**
     * Daftar Markdown kelompok umur aktif, lalu kalimat tahun lahir yang boleh
     * memilih (K134), dipisah baris kosong.
     *
     * @param list<array<string, mixed>> $kelompokUmur dari `IsiHalamanDepan::kelompokUmur()`
     */
    public static function daftarKelompokUmur(array $kelompokUmur): string
    {
        if ($kelompokUmur === []) {
            return self::BELUM_DIATUR;
        }

        $baris   = [];
        $pilihan = [];

        foreach ($kelompokUmur as $ku) {
            $keterangan = array_filter([(string) $ku['jenjang'], mb_strtolower((string) $ku['tahun_lahir'])], static fn (string $s): bool => $s !== '');
            $baris[]    = '- **' . $ku['nama'] . '**' . ($keterangan !== [] ? ': ' . implode(', ', $keterangan) : '');

            foreach ($ku['pilihan'] as $kalimat) {
                $pilihan[$kalimat] = true;
            }
        }

        $hasil = implode("\n", $baris);

        if ($pilihan !== []) {
            $hasil .= "\n\n" . implode(' ', array_map(static fn (string $k): string => $k . '.', array_keys($pilihan)));
        }

        return $hasil;
    }

    /**
     * Daftar Markdown biaya per kelompok umur aktif: biaya individu, lalu
     * biaya beregu per regu dikelompokkan menurut besarnya.
     *
     * @param list<array<string, mixed>> $kelompokUmur dari `IsiHalamanDepan::kelompokUmur()`
     */
    public static function daftarBiaya(array $kelompokUmur): string
    {
        helper('aplikasi');

        $baris = [];

        foreach ($kelompokUmur as $ku) {
            $bagian = [];

            if ($ku['biaya_individu'] !== null && $ku['nomor_individu'] !== []) {
                $bagian[] = rupiah($ku['biaya_individu']) . ' per atlet per nomor individu';
            }

            $beregu = [];

            foreach ($ku['nomor_beregu'] as $nomor) {
                if ($nomor['biaya_beregu'] !== null) {
                    $beregu[(int) $nomor['biaya_beregu']][$nomor['nama']] = true;
                }
            }

            ksort($beregu);

            foreach ($beregu as $biaya => $nama) {
                $bagian[] = rupiah($biaya) . ' per regu (' . implode(', ', array_keys($nama)) . ')';
            }

            if ($bagian !== []) {
                $baris[] = '- **' . $ku['nama'] . '**: ' . implode('; ', $bagian);
            }
        }

        return $baris !== [] ? implode("\n", $baris) : self::BELUM_DIATUR;
    }

    /**
     * Rapikan tautan hasil `MarkdownSederhana`: buang alamat yang tidak aman,
     * arahkan alamat web ini lewat `site_url()`, dan buka tautan luar di tab baru.
     */
    public static function rapikanTautan(string $html): string
    {
        return (string) preg_replace_callback('/<a href="([^"]*)">(.*?)<\/a>/su', static function (array $m): string {
            $alamat = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $teks   = $m[2];

            if (preg_match('#^https?://[^\s/]+\.[^\s]+$#i', $alamat) === 1) {
                return '<a href="' . esc($alamat, 'attr') . '" target="_blank" rel="noopener noreferrer">' . $teks . '</a>';
            }

            if (preg_match('#^(mailto|tel):[^\s]+$#i', $alamat) === 1 || preg_match('/^#[\w\-]+$/u', $alamat) === 1) {
                return '<a href="' . esc($alamat, 'attr') . '">' . $teks . '</a>';
            }

            // Alamat di web ini: tanpa skema, tanpa `//` di depan, tanpa titik dua.
            if (preg_match('#^/?(?!/)[\w\-./?=&%\#]*$#u', $alamat) === 1 && ! str_contains($alamat, ':') && ! str_contains($alamat, '..')) {
                return '<a href="' . esc(site_url(ltrim($alamat, '/')), 'attr') . '">' . $teks . '</a>';
            }

            return $teks;
        }, $html);
    }
}
