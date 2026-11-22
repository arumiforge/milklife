/**
 * Wizard pendaftaran: dasar bersama (dimuat paling awal).
 *
 * Membaca data JSON `#data-pendaftaran` dan membuat `window.Pendaftaran`, satu
 * objek bersama untuk semua bagian wizard:
 *
 * - keadaan yang ditulis ulang beberapa bagian: `draf`, `rincian`,
 *   `sudahSelesai`, `atletDiubah`, `reguDibuka`, `perluSegarkanSusulan`;
 * - data dan alat bantu dari berkas ini (`DATA`, `$`, `buat`, `rupiah`, …);
 * - fungsi yang dipakai bagian lain, ditambahkan oleh tiap bagian di akhir berkasnya.
 *
 * Bagian yang dimuat lebih dulu disalin ke variabel lokal di awal berkas;
 * bagian yang dimuat sesudahnya dipanggil lewat `W.nama()`. Urutan muat
 * ada di `app/Views/pendaftaran.php`; `pendaftaran.js` dimuat terakhir dan
 * menjalankan wizard. Tanpa `#wizard-pendaftaran`, objek ini tidak dibuat
 * dan semua bagian berhenti.
 */
(function () {
    'use strict';

    var akar = document.getElementById('wizard-pendaftaran');
    var sumber = document.getElementById('data-pendaftaran');

    if (!akar || !sumber) {
        return;
    }

    var DATA = JSON.parse(sumber.textContent);
    var VERSI_DRAF = 1;
    var SISI_FOTO = 800;
    var BATAS_FOTO = 450 * 1024;
    // Bukti transfer dikecilkan di peramban seperti foto (K90); server menyimpan sisi paling besar 1600.
    var SISI_BUKTI = 1600;
    var BATAS_BUKTI = 1500 * 1024;
    var KU = {};
    var NOMOR = {};

    DATA.kelompok_umur.forEach(function (ku) { KU[ku.id] = ku; });
    DATA.nomor_lomba.forEach(function (nomor) { NOMOR[nomor.id] = nomor; });

    // ------------------------------------------------------------------
    // Bantu
    // ------------------------------------------------------------------

    /**
     * `fetch` dengan bilah muat di atas halaman (K89).
     */
    function ambil(alamat, opsi) {
        var bilah = window.Kemajuan;
        if (bilah) {
            bilah.mulai();
        }
        var akhiri = function () {
            if (bilah) {
                bilah.selesai();
            }
        };
        return fetch(alamat, opsi).then(function (jawaban) {
            akhiri();
            return jawaban;
        }, function (galat) {
            akhiri();
            throw galat;
        });
    }

    /**
     * Jendela konfirmasi (`konfirmasi.js`, K89); `window.confirm` bila belum termuat.
     */
    function yakin(judul, kalimat, tombol, jenis) {
        return window.Konfirmasi
            ? window.Konfirmasi.tanya(judul, kalimat, tombol, jenis)
            : Promise.resolve(window.confirm(kalimat));
    }

    function $(pemilih, induk) {
        return (induk || document).querySelector(pemilih);
    }

    function $$(pemilih, induk) {
        return Array.prototype.slice.call((induk || document).querySelectorAll(pemilih));
    }

    function buat(tag, kelas, teks) {
        var el = document.createElement(tag);
        if (kelas) {
            el.className = kelas;
        }
        if (teks !== undefined && teks !== null) {
            el.textContent = teks;
        }
        return el;
    }

    // Ikon Lucide dari sprite yang sama dengan `ikon()` PHP; alamatnya di `data-sprite`.
    // Nama yang dipakai bagian wizard (untuk `npm run ikon`):
    // ikon-sprite: pencil trash-2 circle-plus circle-arrow-up triangle-alert school id-card
    var SPRITE = akar.getAttribute('data-sprite') || '';
    var SVG = 'http://www.w3.org/2000/svg';

    function ikon(nama, kelas) {
        var svg = document.createElementNS(SVG, 'svg');
        var pakai = document.createElementNS(SVG, 'use');
        svg.setAttribute('class', 'ikon ' + (kelas || 'size-4'));
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        pakai.setAttribute('href', SPRITE + '#' + nama);
        svg.appendChild(pakai);
        return svg;
    }

    /**
     * Kelas Tailwind untuk elemen buatan JS (F9-05), kembaran
     * `App\Libraries\Pendaftaran\KelasTampilan`. `invalid-feedback` dan
     * `is-invalid` tetap menjadi kait galat (`langkah.js`).
     */
    var TOMBOL = 'inline-flex items-center justify-center gap-1.5 rounded-lg text-center font-semibold transition-colors focus:outline-none focus:ring-4 disabled:cursor-not-allowed disabled:opacity-60';
    var KELAS = {
        isian: 'block min-h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary-600 focus:outline-none focus:ring-4 focus:ring-primary-100 [&.is-invalid]:border-red-500 [&.is-invalid]:bg-red-50',
        umpan: 'invalid-feedback mt-1 text-sm font-medium text-red-700 empty:hidden',
        centang: 'size-5 shrink-0 rounded border-gray-300 text-primary-700 focus:ring-4 focus:ring-primary-200 [&.is-invalid]:border-red-500',
        // `<label class="pilihan"><input type="radio" class="sr-only">Teks</label>`
        pilihan: 'flex flex-auto cursor-pointer flex-col items-center justify-center rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-center font-bold text-primary-700 transition hover:bg-primary-50 has-checked:border-primary-700 has-checked:bg-primary-700 has-checked:text-white has-focus-visible:ring-4 has-focus-visible:ring-primary-200 [fieldset.is-invalid_&]:border-red-500',
        pilihanKecil: 'flex flex-1 cursor-pointer items-center justify-center border border-primary-700 bg-white px-3 py-1.5 text-sm font-semibold text-primary-700 first:rounded-s-lg last:rounded-e-lg hover:bg-primary-50 has-checked:bg-primary-700 has-checked:text-white has-focus-visible:ring-4 has-focus-visible:ring-primary-200',
        garisKecil: TOMBOL + ' min-h-9 border border-primary-700 bg-white px-3 py-1.5 text-sm text-primary-700 hover:bg-primary-50 focus:ring-primary-200',
        hapusKecil: TOMBOL + ' min-h-9 border border-red-600 bg-white px-3 py-1.5 text-sm text-red-700 hover:bg-red-50 focus:ring-red-200',
        tautan: 'inline-flex min-h-9 items-center gap-1.5 text-start text-sm font-semibold text-primary-700 hover:underline focus:outline-none focus-visible:ring-4 focus-visible:ring-primary-200',
        galatBaris: 'galat-baris mt-1.5 text-sm text-red-700',
        keping: 'nomor-beregu-chip inline-block whitespace-nowrap rounded-full border border-gray-200 bg-primary-50 px-2 py-0.5 text-xs leading-normal text-gray-600',
        kepingInti: 'nomor-beregu-chip chip-inti inline-block whitespace-nowrap rounded-full bg-primary-100 px-2 py-0.5 text-xs font-semibold leading-normal text-primary-900',
        kepingCadangan: 'nomor-beregu-chip chip-cadangan inline-block whitespace-nowrap rounded-full border border-dashed border-gray-300 bg-white px-2 py-0.5 text-xs leading-normal text-gray-800',
        kepingBiaya: 'nomor-beregu-chip chip-biaya inline-block whitespace-nowrap rounded-full bg-oranye-50 px-2 py-0.5 text-xs font-semibold leading-normal text-oranye-800',
        kotakInfo: 'mb-3 rounded-xl bg-primary-100 p-3 text-sm text-primary-900',
        kotakPeringatan: 'mb-3 rounded-xl border border-yellow-200 bg-yellow-50 p-3 text-sm text-yellow-900',
        kartu: 'kartu-isian mb-4 rounded-2xl border border-gray-200 border-t-4 border-t-oranye-500 bg-white p-4 shadow-lembut sm:p-7',
        redup: 'text-gray-500'
    };

    /** Kelas keping keterangan nomor beregu menurut jenisnya ('', 'inti', 'cadangan', 'biaya'). */
    function kelasKeping(jenis) {
        return { inti: KELAS.kepingInti, cadangan: KELAS.kepingCadangan, biaya: KELAS.kepingBiaya }[jenis] || KELAS.keping;
    }

    function rupiah(angka) {
        return 'Rp ' + String(Math.round(Number(angka) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function rapikanSpasi(teks) {
        return String(teks || '').replace(/\s+/g, ' ').trim();
    }

    function hariIni() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function uid() {
        return 'a' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function nomorWaSah(isian) {
        var teks = String(isian || '').trim();
        if (teks === '' || !/^\+?[0-9 .\-/()]+$/.test(teks)) {
            return false;
        }
        var angka = teks.replace(/\D/g, '');
        if (angka.charAt(0) === '0') {
            angka = '62' + angka.slice(1);
        } else if (angka.charAt(0) === '8') {
            angka = '62' + angka;
        }
        return angka.indexOf('628') === 0 && angka.length >= 10 && angka.length <= 15;
    }

    window.Pendaftaran = {
        // Keadaan bersama.
        draf: null,
        rincian: null,
        sudahSelesai: false,
        atletDiubah: null,
        reguDibuka: {},
        perluSegarkanSusulan: false,

        // Data dan alat bantu.
        akar: akar,
        DATA: DATA,
        NOMOR: NOMOR,
        VERSI_DRAF: VERSI_DRAF,
        SISI_FOTO: SISI_FOTO,
        BATAS_FOTO: BATAS_FOTO,
        SISI_BUKTI: SISI_BUKTI,
        BATAS_BUKTI: BATAS_BUKTI,
        $: $,
        $$: $$,
        ambil: ambil,
        buat: buat,
        hariIni: hariIni,
        ikon: ikon,
        KELAS: KELAS,
        kelasKeping: kelasKeping,
        nomorWaSah: nomorWaSah,
        rapikanSpasi: rapikanSpasi,
        rupiah: rupiah,
        uid: uid,
        yakin: yakin
    };
}());
