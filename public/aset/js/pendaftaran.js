/**
 * Wizard pendaftaran (`/pendaftaran`, rancangan 7; rencana Tahap 4): tombol bersama dan mulai.
 *
 * Halaman PHP menyediakan kerangka lima langkah dan data JSON `#data-pendaftaran`
 * (`DataIndukPendaftaran::untukWizard()` ditambah `alamat` dan `kunci_draf`).
 * Bersama bagian-bagiannya, wizard ini:
 *
 * - menjalankan pil langkah (langkah yang sudah dilewati bercentang dan bisa dibuka lagi),
 * - menyimpan isian di `localStorage` setiap ada perubahan (draf),
 * - memperkecil foto penanggung jawab di peramban (sisi terpanjang 800 piksel, JPEG),
 * - memeriksa isian untuk kenyamanan. Semua aturan diperiksa ulang di server.
 *
 * Teks dari isian dan data selalu ditulis lewat `textContent`, tidak pernah `innerHTML`.
 *
 * Dimuat terakhir, setelah bagian-bagian di `aset/js/pendaftaran/` (F7-30):
 * `dasar`, `draf`, `langkah`, `sekolah`, `ofisial`, `atlet`, `regu`,
 * `atlet-formulir`, `atlet-daftar`, `biaya`, `turnstile`, dan `pembayaran`.
 * Urutannya diatur `app/Views/pendaftaran.php`.
 */
(function (W) {
    'use strict';

    if (!W) {
        return;
    }

    // Dari bagian yang dimuat lebih dulu.
    var akar = W.akar;
    var DATA = W.DATA;
    var $ = W.$;
    var $$ = W.$$;
    var buat = W.buat;
    var ikon = W.ikon;
    var rupiah = W.rupiah;
    var yakin = W.yakin;
    var drafBaru = W.drafBaru;
    var hapusDraf = W.hapusDraf;
    var simpanDraf = W.simpanDraf;
    var lanjut = W.lanjut;
    var pemeriksaLangkah = W.pemeriksaLangkah;
    var penyiapLangkah = W.penyiapLangkah;
    var perbaruiPil = W.perbaruiPil;
    var tampilkanGalat = W.tampilkanGalat;
    var tampilkanLangkah = W.tampilkanLangkah;
    var jenjangSekolah = W.jenjangSekolah;
    var kabupatenSekolah = W.kabupatenSekolah;
    var npsnSekolah = W.npsnSekolah;
    var tampilkanSekolah = W.tampilkanSekolah;
    var kosongkanFormulirAtlet = W.kosongkanFormulirAtlet;
    var tampilkanDaftarAtlet = W.tampilkanDaftarAtlet;
    var perbaruiSusulan = W.perbaruiSusulan;

    // ------------------------------------------------------------------
    // Tombol bersama dan mulai
    // ------------------------------------------------------------------

    $$('[data-lanjut]', akar).forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            lanjut(Number(tombol.closest('.langkah').getAttribute('data-langkah')));
        });
    });

    $$('[data-kembali]', akar).forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            tampilkanLangkah(Number(tombol.closest('.langkah').getAttribute('data-langkah')) - 1);
        });
    });

    $$('.pil', akar).forEach(function (pil) {
        pil.addEventListener('click', function () {
            tampilkanLangkah(Number(pil.getAttribute('data-ke-langkah')));
        });
    });

    $('#tombol-mulai-ulang').addEventListener('click', function () {
        yakin('Mulai dari awal?', 'Data sekolah, ofisial, dan atlet di halaman ini akan dihapus dan tidak bisa dikembalikan.', 'Kosongkan isian').then(function (setuju) {
            if (!setuju) {
                return;
            }
            hapusDraf();
            W.draf = drafBaru();
            kosongkanFormulirAtlet();
            simpanDraf();
            tampilkanLangkah(1);
        });
    });

    // Antarmuka untuk langkah 4–5 (F4-04).
    window.WizardPendaftaran = {
        data: DATA,
        draf: function () { return W.draf; },
        simpanDraf: simpanDraf,
        hapusDraf: hapusDraf,
        drafBaru: function () { W.draf = drafBaru(); return W.draf; },
        tampilkanLangkah: tampilkanLangkah,
        tampilkanGalat: tampilkanGalat,
        perbaruiPil: perbaruiPil,
        penyiapLangkah: penyiapLangkah,
        pemeriksaLangkah: pemeriksaLangkah,
        rupiah: rupiah,
        buat: buat,
        ikon: ikon,
        jenjangSekolah: jenjangSekolah,
        kabupatenSekolah: kabupatenSekolah,
        npsnSekolah: npsnSekolah
    };

    if (W.draf.tersimpan) {
        // Draf dari alur lama yang sudah tersimpan di server.
        W.sudahSelesai = true;
        hapusDraf();
    }

    akar.hidden = false;
    tampilkanSekolah();
    if (W.perluSegarkanSusulan && npsnSekolah() !== '' && W.draf.sekolah.dicari === W.draf.sekolah.npsn) {
        perbaruiSusulan().then(function () {
            if (W.draf.langkah === 3) {
                tampilkanDaftarAtlet();
            }
        });
    }
    tampilkanLangkah(W.draf.tersimpan ? 5 : Math.min(W.draf.langkah || 1, W.draf.terjauh || 1), false);
}(window.Pendaftaran));
