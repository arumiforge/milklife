/*
 * Deret tombol sejenis di ponsel (K175 butir 8, `komponen/deret_tombol.php`).
 *
 * Di bawah layar `sm` hanya satu tombol berteks (`data-lebar`), yang lain ikon
 * saja. Ketukan pertama pada tombol ikon melebarkannya (teks tampil, tombol
 * lain menjadi ikon) tanpa membuka halaman; ketukan kedua membuka halaman.
 * Aktivasi papan ketik (Enter, `event.detail === 0`) selalu langsung membuka
 * halaman, dan tombol yang difokus papan ketik ikut melebar supaya teksnya
 * terlihat. Di layar lebar semua tombol berteks dan langsung membuka halaman.
 */
(function () {
    'use strict';

    var ponsel = window.matchMedia('(max-width: 39.999rem)');

    function lebarkan(deret, butir) {
        Array.prototype.forEach.call(deret.querySelectorAll('[data-deret-butir]'), function (b) {
            if (b === butir) {
                b.setAttribute('data-lebar', '');
            } else {
                b.removeAttribute('data-lebar');
            }
        });
    }

    Array.prototype.forEach.call(document.querySelectorAll('[data-deret-tombol]'), function (deret) {
        var lebarSaatDitekan = null;

        deret.addEventListener('pointerdown', function (e) {
            var butir = e.target.closest('[data-deret-butir]');
            lebarSaatDitekan = butir ? butir.hasAttribute('data-lebar') : null;
        });

        deret.addEventListener('click', function (e) {
            var butir = e.target.closest('[data-deret-butir]');
            var lebar = lebarSaatDitekan;
            lebarSaatDitekan = null;

            if (!butir || !ponsel.matches || e.detail === 0) {
                return;
            }

            if (!(lebar === null ? butir.hasAttribute('data-lebar') : lebar)) {
                e.preventDefault();
                lebarkan(deret, butir);
            }
        });

        // Fokus papan ketik (bukan akibat ketukan): tombol yang difokus ikut melebar.
        deret.addEventListener('focusin', function (e) {
            var butir = e.target.closest('[data-deret-butir]');

            if (butir && ponsel.matches && butir.matches(':focus-visible')) {
                lebarkan(deret, butir);
            }
        });
    });
}());
