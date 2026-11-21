<?php

/**
 * Pembantu tampilan Tahap 9 (K140, K141): ikon Lucide dan komponen Flowbite Pro.
 * Gambar opsional (F9-19): `gambar_opsional()` dan `ada_gambar()`.
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
     * Dirender **terpisah**: komponen hanya melihat `$data` yang diberikan, bukan
     * data halaman yang sedang dirender. `view()` CodeIgniter menggabungkan data
     * dengan data penyaji bersama, sehingga parameter opsional (`$ikon`, `$kelas`,
     * `$atribut`, …) bisa terisi variabel halaman lain. Komponen tidak memakai
     * `$this` (bagian/section) dan tidak diberi komentar DEBUG-VIEW.
     *
     * @param array<string, mixed> $data
     */
    function komponen(string $nama, array $data = []): string
    {
        if (preg_match('/^[a-z][a-z0-9_]*$/', $nama) !== 1 || ! is_file($berkas = APPPATH . 'Views/komponen/' . $nama . '.php')) {
            throw new InvalidArgumentException('Komponen tidak dikenal: ' . $nama);
        }

        return (static function (string $__berkas, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();

            try {
                include $__berkas;
            } catch (Throwable $e) {
                ob_end_clean();

                throw $e;
            }

            return (string) ob_get_clean();
        })($berkas, $data);
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

if (! function_exists('lebar_tw')) {
    /**
     * Kelas lebar Tailwind untuk bilah kemajuan, dibulatkan ke kelipatan 5 %
     * (F9-14). Kelasnya ditulis utuh supaya
     * ikut dibangun Tailwind; CSP melarang `style="width: …"` (K127).
     *
     *     <div class="h-2 rounded-full bg-green-600 <?= lebar_tw(45) ?>"></div>
     */
    function lebar_tw(int|float $persen): string
    {
        $kelas = [
            'w-0', 'w-[5%]', 'w-[10%]', 'w-[15%]', 'w-[20%]', 'w-[25%]', 'w-[30%]', 'w-[35%]', 'w-[40%]', 'w-[45%]', 'w-[50%]',
            'w-[55%]', 'w-[60%]', 'w-[65%]', 'w-[70%]', 'w-[75%]', 'w-[80%]', 'w-[85%]', 'w-[90%]', 'w-[95%]', 'w-full',
        ];

        return $kelas[(int) round(max(0, min(100, $persen)) / 5)];
    }
}

if (! function_exists('gambar_opsional')) {
    /**
     * Tag `<img>` gambar di `public/aset/gambar/` bila berkasnya ada, selain itu
     * teks kosong (F9-19, K41). Lihat `App\Libraries\GambarOpsional::img()`.
     *
     *     <?= gambar_opsional('admin/masuk.webp', ['class' => 'h-full w-full object-cover', 'loading' => false]) ?>
     *     <?= gambar_opsional('judul/jadwal.webp', ['class' => 'h-32 w-auto', 'alt' => 'Jam pasir']) ?>
     *
     * @param array<string, scalar|null> $atribut
     */
    function gambar_opsional(string $jalur, array $atribut = []): string
    {
        return App\Libraries\GambarOpsional::img($jalur, $atribut);
    }
}

if (! function_exists('ada_gambar')) {
    /**
     * Apakah berkas `public/aset/gambar/<jalur>` sudah ada (untuk wadah yang hanya dipasang bila gambarnya ada).
     */
    function ada_gambar(string $jalur): bool
    {
        return App\Libraries\GambarOpsional::ada($jalur);
    }
}
