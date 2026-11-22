/**
 * Laci kanan ponsel tata letak publik (F10-07, K154; dari kanan sejak F10-08), tanpa skrip sebaris (K127).
 *
 * - Tombol `[data-buka-laci]` membuka `#laci-publik`; `aria-expanded` semua
 *   tombol pembuka mengikuti keadaan laci.
 * - Menutup: tombol `[data-tutup-laci]`, Esc, ketuk latar `[data-latar-laci]`,
 *   atau layar melebar ke `lg` (laci hanya untuk ponsel).
 * - Fokus terkunci di dalam laci selama terbuka (Tab dan Shift+Tab berputar),
 *   lalu kembali ke tombol yang membukanya.
 */
(function () {
    'use strict';

    var laci = document.querySelector('[data-laci-publik]');
    var latar = document.querySelector('[data-latar-laci]');

    if (!laci || !latar) {
        return;
    }

    var pembuka = Array.prototype.slice.call(document.querySelectorAll('[data-buka-laci]'));
    var asal = null;
    var FOKUS = 'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])';

    function terbuka() {
        return laci.hasAttribute('data-terbuka');
    }

    function aturPembuka(nilai) {
        pembuka.forEach(function (tombol) {
            tombol.setAttribute('aria-expanded', nilai ? 'true' : 'false');
        });
    }

    function bisaFokus() {
        return Array.prototype.slice.call(laci.querySelectorAll(FOKUS)).filter(function (el) {
            return el.offsetParent !== null || el === document.activeElement;
        });
    }

    function buka(tombol) {
        if (terbuka()) {
            return;
        }

        asal = tombol || document.activeElement;
        laci.setAttribute('data-terbuka', '');
        latar.classList.remove('hidden');
        document.documentElement.classList.add('laci-terbuka');
        aturPembuka(true);

        var tutup = laci.querySelector('[data-tutup-laci]');
        (tutup || laci).focus();
    }

    function tutup() {
        if (!terbuka()) {
            return;
        }

        laci.removeAttribute('data-terbuka');
        latar.classList.add('hidden');
        document.documentElement.classList.remove('laci-terbuka');
        aturPembuka(false);

        if (asal && typeof asal.focus === 'function' && asal.offsetParent !== null) {
            asal.focus();
        }

        asal = null;
    }

    pembuka.forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            if (terbuka()) {
                tutup();
            } else {
                buka(tombol);
            }
        });
    });

    Array.prototype.forEach.call(laci.querySelectorAll('[data-tutup-laci]'), function (tombol) {
        tombol.addEventListener('click', tutup);
    });

    latar.addEventListener('click', tutup);

    document.addEventListener('keydown', function (e) {
        if (!terbuka()) {
            return;
        }

        if (e.key === 'Escape') {
            e.preventDefault();
            tutup();
            return;
        }

        if (e.key !== 'Tab') {
            return;
        }

        var daftar = bisaFokus();

        if (daftar.length === 0) {
            e.preventDefault();
            laci.focus();
            return;
        }

        var awal = daftar[0];
        var akhir = daftar[daftar.length - 1];

        if (!laci.contains(document.activeElement)) {
            e.preventDefault();
            awal.focus();
        } else if (e.shiftKey && (document.activeElement === awal || document.activeElement === laci)) {
            e.preventDefault();
            akhir.focus();
        } else if (!e.shiftKey && document.activeElement === akhir) {
            e.preventDefault();
            awal.focus();
        }
    });

    // Fokus yang lolos keluar laci (misalnya klik di luar) ditarik kembali.
    document.addEventListener('focusin', function (e) {
        if (terbuka() && !laci.contains(e.target)) {
            var daftar = bisaFokus();
            (daftar[0] || laci).focus();
        }
    });

    if (window.matchMedia) {
        var lebar = window.matchMedia('(min-width: 64rem)');
        var ubah = function (m) {
            if (m.matches) {
                tutup();
            }
        };

        if (lebar.addEventListener) {
            lebar.addEventListener('change', ubah);
        } else if (lebar.addListener) {
            lebar.addListener(ubah);
        }
    }
})();
