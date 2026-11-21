<?php

/**
 * Pembantu tampilan Tahap 9 (K140, K141): ikon Lucide dan komponen Flowbite Pro.
 * Dimuat otomatis lewat `app/Config/Autoload.php`.
 *
 *     <?= ikon('search') ?>                         // <svg class="ikon" …><use …#search></svg>
 *     <?= ikon('trophy', 'size-8 text-oranye-500') ?>
 *     <?= ikon('x', '', 'Tutup') ?>                 // berlabel untuk pembaca layar
 *     <?= komponen('tombol', ['label' => 'Daftarkan sekolah', 'href' => site_url('pendaftaran')]) ?>
 *
 * Ikon diambil dari sprite `public/aset/vendor/lucide/lucide.svg` yang dibuat
 * `npm run ikon` dari semua pemanggilan `ikon('…')`. Ikon baru cukup dipakai
 * di view lalu jalankan `npm run ikon` (dan `npm run css`).
 */

if (! function_exists('ikon')) {
    /**
     * @param string      $nama  Nama ikon Lucide, misalnya `circle-check` (lihat lucide.dev/icons).
     * @param string      $kelas Kelas tambahan; ukuran bawaan `size-5` dari kelas `.ikon`.
     * @param string|null $label Teks untuk pembaca layar; tanpa label ikon disembunyikan (`aria-hidden`).
     */
    function ikon(string $nama, string $kelas = '', ?string $label = null): string
    {
        static $sprite = null;

        if ($sprite === null) {
            $berkas = FCPATH . 'aset/vendor/lucide/lucide.svg';
            $versi  = @filemtime($berkas);
            $sprite = base_url('aset/vendor/lucide/lucide.svg') . ($versi === false ? '' : '?v=' . $versi);
        }

        $kelas = trim('ikon ' . $kelas);
        $aria  = $label === null
            ? ' aria-hidden="true" focusable="false"'
            : ' role="img" aria-label="' . esc($label) . '"';

        return '<svg class="' . esc($kelas) . '"' . $aria . '><use href="' . $sprite . '#' . esc($nama) . '"></use></svg>';
    }
}

if (! function_exists('komponen')) {
    /**
     * Potongan komponen dari `app/Views/komponen/<nama>.php` (lihat kepala tiap berkas untuk datanya).
     *
     * @param array<string, mixed> $data
     */
    function komponen(string $nama, array $data = []): string
    {
        return view('komponen/' . $nama, $data, ['saveData' => false]);
    }
}

if (! function_exists('atribut_html')) {
    /**
     * Ubah larik atribut menjadi teks atribut HTML yang aman.
     *
     *     atribut_html(['data-modal-target' => 'ubah', 'disabled' => true, 'title' => null]);
     *     // ' data-modal-target="ubah" disabled'
     *
     * Nilai `true` menulis atribut tanpa nilai; `false` dan `null` dilewati.
 * Nilai di-escape HTML (aman karena selalu di dalam tanda kutip ganda).
     * Atribut `style` dan `on…` ditolak karena CSP (K127).
     *
     * @param array<string, scalar|null> $atribut
     */
    function atribut_html(array $atribut): string
    {
        $teks = '';

        foreach ($atribut as $nama => $nilai) {
            $nama = strtolower((string) $nama);

            if ($nilai === null || $nilai === false || $nama === 'style' || str_starts_with($nama, 'on')
                || preg_match('/^[a-z][a-z0-9:_.-]*$/', $nama) !== 1) {
                continue;
            }

            $teks .= $nilai === true ? ' ' . $nama : ' ' . $nama . '="' . esc((string) $nilai) . '"';
        }

        return $teks;
    }
}
