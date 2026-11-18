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

    function ikon(nama) {
        var i = buat('i', 'bi bi-' + nama);
        i.setAttribute('aria-hidden', 'true');
        return i;
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
        nomorWaSah: nomorWaSah,
        rapikanSpasi: rapikanSpasi,
        rupiah: rupiah,
        uid: uid,
        yakin: yakin
    };
}());
