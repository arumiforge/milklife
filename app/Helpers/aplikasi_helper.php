<?php

/**
 * Fungsi bantu yang dipakai di seluruh aplikasi. Dimuat otomatis lewat
 * `app/Config/Autoload.php`.
 */

// `setting()` dari `codeigniter4/settings` ikut dimuat, supaya bisa dipakai di
// pengendali, tampilan, perintah spark, dan uji tanpa memanggil `helper()` lagi.
helper('setting');

if (! function_exists('aset')) {
    /**
     * Alamat lengkap sebuah berkas di `public/aset/`.
     *
     * Bila `aplikasi.minify` aktif dan berkas `.min` padanannya ada
     * (`css/tema.css` → `css/tema.min.css`, juga `.js` dan `.svg`), alamat
     * berkas `.min` yang dipakai.
     * Jalur yang sudah `.min` atau berada di `vendor/` tidak diubah.
     *
     * @param string $jalur Jalur relatif terhadap `public/aset/`, misalnya `css/tema.css`.
     */
    function aset(string $jalur): string
    {
        $jalur = ltrim($jalur, '/');

        if (config('Aplikasi')->minify && ! str_starts_with($jalur, 'vendor/')) {
            $jalurMin = preg_replace('/(?<!\.min)\.(css|js|svg)$/', '.min.$1', $jalur);

            if ($jalurMin !== null && $jalurMin !== $jalur && is_file(FCPATH . 'aset/' . $jalurMin)) {
                $jalur = $jalurMin;
            }
        }

        return base_url('aset/' . $jalur);
    }
}

if (! function_exists('rupiah')) {
    /**
     * Tampilan uang rupiah tanpa angka di belakang koma: `Rp150.000`.
     *
     *     rupiah(150000);   // 'Rp150.000'
     *     rupiah('0');      // 'Rp0'
     *     rupiah(null);     // ''
     *
     * Angka dari basis data (teks berisi angka bulat) juga diterima. Nilai
     * kosong atau bukan angka bulat menghasilkan `''`, supaya tampilan bisa
     * menulis "Belum diisi" sendiri.
     */
    function rupiah(int|string|null $angka): string
    {
        if (is_string($angka)) {
            $angka = trim($angka);

            if (preg_match('/^-?\d+$/', $angka) !== 1) {
                return '';
            }

            $angka = (int) $angka;
        }

        if ($angka === null) {
            return '';
        }

        return ($angka < 0 ? '-' : '') . 'Rp' . number_format(abs($angka), 0, ',', '.');
    }
}

if (! function_exists('angka_rupiah')) {
    /**
     * Ubah isian uang dari formulir menjadi bilangan bulat rupiah.
     *
     * Admin mengetik angka tanpa titik; bila `Rp`, titik, atau spasi ikut
     * diketik, semuanya dibuang. Isian kosong atau yang masih berisi tanda
     * lain (huruf, koma, minus) menghasilkan `null`, untuk ditolak validasi.
     *
     *     angka_rupiah('150000');      // 150000
     *     angka_rupiah('Rp 150.000');  // 150000
     *     angka_rupiah('150.000,50');  // null
     *     angka_rupiah('');            // null
     */
    function angka_rupiah(?string $isian): ?int
    {
        $isian = preg_replace('/^rp\.?/i', '', trim((string) $isian));
        $isian = str_replace(['.', ' '], '', (string) $isian);

        if ($isian === '' || preg_match('/^\d{1,10}$/', $isian) !== 1) {
            return null;
        }

        $angka = (int) $isian;

        return $angka <= 4_294_967_295 ? $angka : null;
    }
}

if (! function_exists('tanggal_id')) {
    /**
     * Tanggal dalam bahasa Indonesia.
     *
     *     tanggal_id('2026-11-30');                   // '30 November 2026'
     *     tanggal_id('2026-11-30 23:59:00', true);    // '30 November 2026 pukul 23.59'
     *     tanggal_id(null);                           // ''
     *
     * Menerima teks `Y-m-d` atau `Y-m-d H:i[:s]` (zona waktu aplikasi, WIB)
     * dan objek `DateTimeInterface`. Nilai kosong atau tidak sah menghasilkan `''`.
     *
     * @param bool $denganJam Tambahkan jam dan menit, contoh `pukul 23.59`.
     */
    function tanggal_id(DateTimeInterface|string|null $nilai, bool $denganJam = false): string
    {
        $waktu = _aplikasi_baca_tanggal($nilai);

        if ($waktu === null) {
            return '';
        }

        $teks = (int) $waktu->format('j') . ' ' . _aplikasi_nama_bulan((int) $waktu->format('n')) . ' ' . $waktu->format('Y');

        return $denganJam ? $teks . ' pukul ' . $waktu->format('H.i') : $teks;
    }
}

if (! function_exists('rentang_tanggal_id')) {
    /**
     * Rentang dua tanggal dalam bahasa Indonesia, sependek mungkin.
     *
     *     rentang_tanggal_id('2026-12-09', '2026-12-12');  // '9–12 Desember 2026'
     *     rentang_tanggal_id('2026-11-30', '2026-12-02');  // '30 November – 2 Desember 2026'
     *     rentang_tanggal_id('2026-12-31', '2027-01-01');  // '31 Desember 2026 – 1 Januari 2027'
     *     rentang_tanggal_id('2026-12-09', '2026-12-09');  // '9 Desember 2026'
     *     rentang_tanggal_id('2026-12-09', null);          // '9 Desember 2026'
     *
     * Bila hanya satu tanggal yang sah, tanggal itu saja yang ditampilkan;
     * bila keduanya kosong, hasilnya `''`.
     */
    function rentang_tanggal_id(DateTimeInterface|string|null $mulai, DateTimeInterface|string|null $selesai): string
    {
        $awal  = _aplikasi_baca_tanggal($mulai);
        $akhir = _aplikasi_baca_tanggal($selesai);

        if ($awal === null || $akhir === null || $awal->format('Y-m-d') === $akhir->format('Y-m-d')) {
            return tanggal_id($awal ?? $akhir);
        }

        if ($awal->format('Y') !== $akhir->format('Y')) {
            return tanggal_id($awal) . ' – ' . tanggal_id($akhir);
        }

        if ($awal->format('n') !== $akhir->format('n')) {
            return (int) $awal->format('j') . ' ' . _aplikasi_nama_bulan((int) $awal->format('n')) . ' – ' . tanggal_id($akhir);
        }

        return (int) $awal->format('j') . '–' . tanggal_id($akhir);
    }
}

if (! function_exists('_aplikasi_baca_tanggal')) {
    /**
     * Baca nilai tanggal untuk `tanggal_id()` dan `rentang_tanggal_id()`.
     * Hanya untuk dipakai fungsi di berkas ini.
     *
     * @internal
     */
    function _aplikasi_baca_tanggal(DateTimeInterface|string|null $nilai): ?DateTimeImmutable
    {
        if ($nilai instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($nilai);
        }

        $nilai = trim((string) $nilai);

        foreach (['!Y-m-d', '!Y-m-d H:i:s', '!Y-m-d H:i', '!Y-m-d\TH:i', '!Y-m-d\TH:i:s'] as $format) {
            $waktu  = DateTimeImmutable::createFromFormat($format, $nilai);
            $galat  = DateTimeImmutable::getLastErrors();
            $bersih = $galat === false || ($galat['warning_count'] === 0 && $galat['error_count'] === 0);

            if ($waktu !== false && $bersih) {
                return $waktu;
            }
        }

        return null;
    }
}

if (! function_exists('_aplikasi_nama_bulan')) {
    /**
     * Nama bulan bahasa Indonesia, 1 = Januari.
     *
     * @internal
     */
    function _aplikasi_nama_bulan(int $bulan): string
    {
        return [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ][$bulan] ?? '';
    }
}
