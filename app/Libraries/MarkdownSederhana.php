<?php

namespace App\Libraries;

/**
 * Pengubah Markdown ke HTML untuk PDF dokumen repo (`php spark dokumen:pdf`).
 *
 * Hanya bagian Markdown yang dipakai dokumen di `dokumen/final/`: judul
 * (`#`–`######`), paragraf, daftar `-`/`*` dan bernomor (nomor awal
 * dipertahankan), tabel berbatas `|`, blok kode berpagar ```` ``` ````, tebal,
 * miring, kode sebaris, dan tautan. Semua teks di-escape.
 *
 * shortcut: tanpa daftar bersarang, kutipan, dan HTML mentah; blok kode di
 * dalam butir daftar ditulis di antara daftar. Tambahkan bila dokumen final
 * mulai memakainya.
 */
final class MarkdownSederhana
{
    /** @var list<string> */
    private array $hasil = [];

    /** @var list<string> */
    private array $paragraf = [];

    /** @var list<string> */
    private array $butir = [];

    private string $jenisDaftar = '';

    private int $nomorAwal = 1;

    /**
     * Baris blok kode yang sedang terbuka; `null` = tidak di dalam blok kode.
     *
     * @var list<string>|null
     */
    private ?array $kode = null;

    private int $jorokKode = 0;

    /** @var list<string> */
    private array $tabel = [];

    public static function html(string $markdown): string
    {
        $pengubah = new self();

        foreach (preg_split('/\R/u', $markdown) ?: [] as $baris) {
            $pengubah->baris($baris);
        }

        $pengubah->tutup();

        return implode("\n", $pengubah->hasil);
    }

    private function baris(string $baris): void
    {
        if (preg_match('/^(\s*)```/u', $baris, $m) === 1) {
            if ($this->kode === null) {
                $this->tutup();
                $this->kode      = [];
                $this->jorokKode = strlen($m[1]);
            } else {
                $this->hasil[] = '<pre><code>' . htmlspecialchars(implode("\n", $this->kode), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code></pre>';
                $this->kode    = null;
            }

            return;
        }

        if ($this->kode !== null) {
            // Jorok pagar (blok kode di dalam butir daftar) dibuang dari setiap baris.
            $this->kode[] = (string) preg_replace('/^ {0,' . $this->jorokKode . '}/', '', $baris);

            return;
        }

        if (trim($baris) === '') {
            $this->tutup();

            return;
        }

        if (preg_match('/^(#{1,6})\s+(.+)$/u', $baris, $m) === 1) {
            $this->tutup();
            $n             = strlen($m[1]);
            $this->hasil[] = "<h{$n}>" . self::sebaris(trim($m[2])) . "</h{$n}>";

            return;
        }

        if (str_starts_with(ltrim($baris), '|')) {
            if ($this->tabel === []) {
                $this->tutup();
            }
            $this->tabel[] = trim($baris);

            return;
        }

        if (preg_match('/^([-*]|\d+\.)\s+(.+)$/u', $baris, $m) === 1) {
            $jenis = ctype_digit($m[1][0]) ? 'ol' : 'ul';

            if ($this->jenisDaftar !== $jenis) {
                $this->tutup();
                $this->jenisDaftar = $jenis;
                $this->nomorAwal   = (int) $m[1];
            }
            $this->butir[] = trim($m[2]);

            return;
        }

        // Baris lanjutan butir daftar (menjorok) ikut butir terakhir.
        if ($this->butir !== [] && preg_match('/^\s+\S/u', $baris) === 1) {
            $this->butir[array_key_last($this->butir)] .= ' ' . trim($baris);

            return;
        }

        if ($this->butir !== [] || $this->tabel !== []) {
            $this->tutup();
        }
        $this->paragraf[] = trim($baris);
    }

    /**
     * Tulis blok yang sedang terbuka (paragraf, daftar, atau tabel).
     */
    private function tutup(): void
    {
        if ($this->paragraf !== []) {
            $this->hasil[]  = '<p>' . self::sebaris(implode(' ', $this->paragraf)) . '</p>';
            $this->paragraf = [];
        }

        if ($this->butir !== []) {
            $isi               = array_map(static fn (string $b): string => '<li>' . self::sebaris($b) . '</li>', $this->butir);
            $mulai             = $this->jenisDaftar === 'ol' && $this->nomorAwal > 1 ? ' start="' . $this->nomorAwal . '"' : '';
            $this->hasil[]     = "<{$this->jenisDaftar}{$mulai}>" . implode('', $isi) . "</{$this->jenisDaftar}>";
            $this->butir       = [];
            $this->jenisDaftar = '';
        }

        if ($this->tabel !== []) {
            $this->hasil[] = self::tabel($this->tabel);
            $this->tabel   = [];
        }
    }

    /**
     * @param list<string> $baris
     */
    private static function tabel(array $baris): string
    {
        // Buang satu pipa di ujung saja, supaya sel kosong di ujung tetap ada.
        $sel  = static fn (string $b): array => array_map('trim', explode('|', (string) preg_replace(['/^\s*\|/', '/\|\s*$/'], '', $b)));
        $html = '<table>';

        foreach ($baris as $i => $b) {
            // Baris pemisah kepala: | --- | :---: |
            if (preg_match('/^\|?\s*:?-{3,}/', $b) === 1) {
                continue;
            }

            $tag  = $i === 0 ? 'th' : 'td';
            $html .= '<tr>' . implode('', array_map(static fn (string $s): string => "<{$tag}>" . self::sebaris($s) . "</{$tag}>", $sel($b))) . '</tr>';
        }

        return $html . '</table>';
    }

    /**
     * Unsur sebaris: kode, tebal, miring, tautan. Teks di-escape lebih dulu.
     */
    private static function sebaris(string $teks): string
    {
        $kode = [];
        $teks = (string) preg_replace_callback('/`([^`]+)`/u', static function (array $m) use (&$kode): string {
            $kode[] = '<code>' . htmlspecialchars($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</code>';

            return "\x00" . (count($kode) - 1) . "\x00";
        }, $teks);

        $teks = htmlspecialchars($teks, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $teks = (string) preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $teks);
        $teks = (string) preg_replace('/(?<![*\w])\*(?=\S)(.+?)(?<=\S)\*(?![*\w])/u', '<em>$1</em>', $teks);
        $teks = (string) preg_replace('/\[([^\]]+)\]\(([^)\s]+)\)/u', '<a href="$2">$1</a>', $teks);

        return (string) preg_replace_callback('/\x00(\d+)\x00/', static fn (array $m): string => $kode[(int) $m[1]], $teks);
    }
}
