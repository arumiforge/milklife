<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Memperkecil HTML sebelum dikirim (global *after*, lihat `Config\Filters`).
 *
 * Hanya bekerja bila `aplikasi.minify = true` dan jawaban berjenis
 * `text/html`. PDF, Excel, JSON, gambar, dan berkas unduhan dilewati apa adanya.
 *
 * Yang dilakukan:
 * - deretan spasi, tab, dan baris baru di teks antar-tag dirapatkan menjadi
 *   satu spasi (tampilan sama, karena peramban juga merapatkannya);
 * - spasi di sekitar tag yang tidak pernah menampilkan spasi (`head`, `meta`,
 *   `link`, `title`, tabel, `option`, …) dibuang;
 * - komentar HTML dibuang, kecuali komentar bersyarat `<!--[if …]>`.
 *
 * Yang tidak diubah: isi tag (atribut dan nilainya) serta seluruh isi `<pre>`,
 * `<textarea>`, `<script>`, dan `<style>`.
 */
class PerkecilHtml implements FilterInterface
{
    /**
     * Tag yang spasi di sekitarnya tidak pernah tampil di halaman.
     */
    private const TAG_TANPA_SPASI = [
        '!doctype', 'html', 'head', 'body', 'meta', 'link', 'title', 'base',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'caption', 'colgroup', 'col',
        'option', 'optgroup',
    ];

    /**
     * Potongan HTML: blok yang dilindungi, komentar, tag, atau teks.
     */
    private const POLA = '~
        (?<blok><(pre|textarea|script|style)\b(?:[^>"\']|"[^"]*+"|\'[^\']*+\')*+>.*?</\2\s*>)
        | (?<komentar><!--.*?-->)
        | (?<tag></?[!a-zA-Z](?:[^>"\']|"[^"]*+"|\'[^\']*+\')*+>)
        | (?<teks>[^<]++|<)
    ~xsi';

    /**
     * @param list<string>|null $arguments
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    /**
     * @param list<string>|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        if (! config('Aplikasi')->minify || ! self::jawabanHtml($response)) {
            return null;
        }

        $isi = $response->getBody();

        if (! is_string($isi) || $isi === '') {
            return null;
        }

        return $response->setBody(self::perkecil($isi));
    }

    /**
     * Apakah jawaban ini halaman HTML (`Content-Type: text/html`).
     */
    public static function jawabanHtml(ResponseInterface $response): bool
    {
        $jenis = strtolower(trim(explode(';', $response->getHeaderLine('Content-Type'))[0]));

        return $jenis === 'text/html';
    }

    /**
     * HTML yang diperkecil. Bila HTML tidak bisa diurai, isi asli dikembalikan.
     */
    public static function perkecil(string $html): string
    {
        if (preg_match_all(self::POLA, $html, $cocok, PREG_SET_ORDER | PREG_UNMATCHED_AS_NULL) === false) {
            return $html;
        }

        // Pecah menjadi potongan: [jenis, isi, tanpaSpasi].
        $potongan = [];

        foreach ($cocok as $c) {
            if ($c['blok'] !== null) {
                $potongan[] = ['blok', $c['blok'], self::tagTanpaSpasi($c['blok'])];
            } elseif ($c['komentar'] !== null) {
                if (str_starts_with($c['komentar'], '<!--[if') || str_starts_with($c['komentar'], '<![endif')) {
                    $potongan[] = ['tag', $c['komentar'], false];
                }
            } elseif ($c['tag'] !== null) {
                $potongan[] = ['tag', $c['tag'], self::tagTanpaSpasi($c['tag'])];
            } else {
                $potongan[] = ['teks', (string) $c['teks'], false];
            }
        }

        // Gabungkan teks yang bersebelahan (setelah komentar dibuang).
        $rapi = [];

        foreach ($potongan as $p) {
            $akhir = array_key_last($rapi);

            if ($p[0] === 'teks' && $akhir !== null && $rapi[$akhir][0] === 'teks') {
                $rapi[$akhir][1] .= $p[1];
            } else {
                $rapi[] = $p;
            }
        }

        $hasil  = '';
        $jumlah = count($rapi);

        foreach ($rapi as $i => [$jenis, $isi]) {
            if ($jenis !== 'teks') {
                $hasil .= $isi;

                continue;
            }

            $isi = (string) preg_replace('/[ \t\n\r\f]++/', ' ', $isi);

            if ($i > 0 && $rapi[$i - 1][2]) {
                $isi = ltrim($isi, ' ');
            }

            if ($i < $jumlah - 1 && $rapi[$i + 1][2]) {
                $isi = rtrim($isi, ' ');
            }

            // Spasi di awal dan akhir dokumen tidak tampil.
            if ($i === 0) {
                $isi = ltrim($isi, ' ');
            }

            if ($i === $jumlah - 1) {
                $isi = rtrim($isi, ' ');
            }

            $hasil .= $isi;
        }

        return $hasil;
    }

    /**
     * Apakah tag (awal potongan) termasuk `TAG_TANPA_SPASI`.
     */
    private static function tagTanpaSpasi(string $tag): bool
    {
        if (preg_match('~^</?(!?[a-zA-Z][a-zA-Z0-9]*)~', $tag, $nama) !== 1) {
            return false;
        }

        return in_array(strtolower($nama[1]), self::TAG_TANPA_SPASI, true);
    }
}
