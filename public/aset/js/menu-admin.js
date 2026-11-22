/*
 * Menu lipat di sidebar panel admin (F10-03).
 *
 * Setiap tombol `[data-lipat="<kunci>"]` membuka dan menutup daftar yang
 * disebut `aria-controls` (atribut `hidden`) dan memperbarui
 * `aria-expanded`. Keadaan terakhir disimpan di localStorage
 * (`menu-admin-lipat`, objek kunci → true/false) supaya tetap sama setelah
 * halaman dimuat ulang. Tombol bertanda `data-lipat-aktif` memuat halaman
 * yang sedang dibuka, jadi selalu terbuka saat halaman dimuat.
 *
 * localStorage bisa tidak ada atau menolak (mode privat, situs diblokir),
 * jadi setiap baca dan tulis dibungkus try/catch; tanpa penyimpanan menu
 * tetap bisa dilipat, hanya tidak diingat.
 */
(function () {
    'use strict';

    var KUNCI_SIMPAN = 'menu-admin-lipat';
    var tombol = document.querySelectorAll('[data-lipat]');

    if (!tombol.length) {
        return;
    }

    function baca() {
        try {
            var isi = JSON.parse(window.localStorage.getItem(KUNCI_SIMPAN) || '{}');
            return isi && typeof isi === 'object' ? isi : {};
        } catch (e) {
            return {};
        }
    }

    function simpan(keadaan) {
        try {
            window.localStorage.setItem(KUNCI_SIMPAN, JSON.stringify(keadaan));
        } catch (e) {
            // Tidak bisa menyimpan: menu tetap jalan tanpa diingat.
        }
    }

    function atur(satu, buka) {
        var daftar = document.getElementById(satu.getAttribute('aria-controls'));
        satu.setAttribute('aria-expanded', buka ? 'true' : 'false');
        if (daftar) {
            daftar.hidden = !buka;
        }
    }

    var keadaan = baca();

    Array.prototype.forEach.call(tombol, function (satu) {
        var kunci = satu.getAttribute('data-lipat');

        if (!satu.hasAttribute('data-lipat-aktif') && typeof keadaan[kunci] === 'boolean') {
            atur(satu, keadaan[kunci]);
        }

        satu.addEventListener('click', function () {
            var buka = satu.getAttribute('aria-expanded') !== 'true';
            atur(satu, buka);
            keadaan = baca();
            keadaan[kunci] = buka;
            simpan(keadaan);
        });
    });
}());
