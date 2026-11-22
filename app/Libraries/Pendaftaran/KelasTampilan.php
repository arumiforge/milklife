<?php

namespace App\Libraries\Pendaftaran;

/**
 * Kelas Tailwind bersama untuk wizard pendaftaran dan Cek Pendaftaran (F9-05).
 *
 * Isian di wizard ditandai salah oleh JS dengan kelas `is-invalid`, pesannya
 * ditulis ke `.invalid-feedback` (`aset/js/pendaftaran/langkah.js`). Kedua
 * nama itu tetap dipakai sebagai kait; tampilannya diatur varian Tailwind di
 * sini (`[&.is-invalid]:…`, `empty:hidden`), jadi `masukan.css` tidak berubah.
 * Kembarannya untuk elemen yang dibuat JS ada di `W.KELAS` (`pendaftaran/dasar.js`).
 */
final class KelasTampilan
{
    /** Kotak isian teks, pilihan, dan berkas. */
    public const ISIAN = 'block min-h-12 w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-base text-gray-900 placeholder:text-gray-500 focus:border-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-100 [&.is-invalid]:border-red-500 [&.is-invalid]:bg-red-50 [&.is-invalid]:focus:ring-red-100';

    /** Kotak unggah berkas (tombol "Pilih berkas" bergaya merek). */
    public const BERKAS = 'block w-full cursor-pointer rounded-lg border border-gray-300 bg-white text-base text-gray-900 file:me-3 file:min-h-12 file:cursor-pointer file:border-0 file:bg-primary-100 file:px-4 file:font-semibold file:text-primary-800 hover:file:bg-primary-200 focus:border-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-100 [&.is-invalid]:border-red-500 [&.is-invalid]:bg-red-50';

    public const LABEL = 'mb-1.5 block text-sm font-semibold text-primary-900';

    /** Pesan galat isian; tersembunyi selama kosong. */
    public const UMPAN = 'invalid-feedback mt-1.5 text-sm font-medium text-red-700 empty:hidden';

    public const BANTU = 'mt-1.5 text-sm text-gray-500';

    /** Kartu isian berbingkai abu-abu, tanpa garis atas oranye (permintaan pemilik 22 November 2026). */
    public const KARTU = 'kartu-isian mb-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-lembut sm:p-7';

    /** Ringkasan galat di atas langkah. */
    public const RINGKASAN = 'ringkasan-galat mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900 [&_ul]:list-disc [&_ul]:ps-5';

    /** Kotak centang. */
    public const CENTANG = 'size-5 shrink-0 rounded border-gray-300 text-primary-700 focus:ring-4 focus:ring-primary-200 [&.is-invalid]:border-red-500';

    /** Pilihan berupa tombol besar: `<label class="PILIHAN"><input type="radio" class="sr-only">Teks</label>`. */
    public const PILIHAN = 'flex flex-auto cursor-pointer flex-col items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-3 text-center font-bold text-primary-700 transition hover:bg-primary-50 has-checked:border-primary-700 has-checked:bg-primary-700 has-checked:text-white has-checked:shadow-lembut has-focus-visible:ring-4 has-focus-visible:ring-primary-200 [fieldset.is-invalid_&]:border-red-500';

    public const TOMBOL = 'inline-flex items-center justify-center gap-2 rounded-lg text-center font-semibold transition-colors focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60';

    public const UTAMA = self::TOMBOL . ' min-h-11 bg-primary-700 px-5 py-2.5 text-sm text-white hover:bg-primary-800 focus:ring-primary-300';

    public const UTAMA_BESAR = self::TOMBOL . ' min-h-12 bg-oranye-600 px-6 py-3 text-base text-white hover:bg-oranye-700 focus:ring-oranye-300';

    public const KEDUA = self::TOMBOL . ' min-h-11 border border-gray-300 bg-white px-5 py-2.5 text-sm text-gray-900 hover:bg-gray-50 hover:text-primary-700 focus:ring-gray-100';

    public const GARIS = self::TOMBOL . ' min-h-11 border border-gray-300 bg-white px-4 py-2 text-sm text-primary-700 hover:bg-primary-50 focus:ring-primary-200';

    public const GARIS_KECIL = self::TOMBOL . ' min-h-9 border border-gray-300 bg-white px-3 py-1.5 text-sm text-primary-700 hover:bg-primary-50 focus:ring-primary-200';

    public const HAPUS_KECIL = self::TOMBOL . ' min-h-9 border border-red-600 bg-white px-3 py-1.5 text-sm text-red-700 hover:bg-red-50 focus:ring-red-200';

    public const WA = self::TOMBOL . ' min-h-11 bg-green-700 px-5 py-2.5 text-sm text-white hover:bg-green-800 focus:ring-green-300';

    public const TAUTAN = 'inline-flex min-h-11 items-center gap-1.5 font-semibold text-primary-700 hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200';
}
