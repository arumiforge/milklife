/**
 * Carousel foto pembuka halaman depan (F9-03), di atas komponen carousel
 * Flowbite (`data-carousel="static"`, jadi Flowbite tidak memutar sendiri).
 *
 * - Putar otomatis tiap JEDA milidetik; berhenti bila tab disembunyikan
 *   atau carousel tidak terlihat di layar.
 * - Tombol jeda (`[data-jeda-carousel]`, aria-pressed) menghentikan dan
 *   melanjutkan putaran (WCAG 2.2.2).
 * - Pengunjung yang memilih kurangi gerak (`prefers-reduced-motion`) tidak
 *   mendapat putaran otomatis dan perpindahan tanpa animasi; tombol tetap bisa dipakai.
 * - Geser ke kiri/kanan di layar sentuh pindah ke foto berikutnya/sebelumnya.
 *
 * Dimuat setelah `flowbite.min.js`, yang membuat objek carousel saat `load`.
 */
(function () {
    'use strict';

    var JEDA = 6000;

    function mulai() {
        var wadah = document.querySelector('[data-carousel-beranda]');
        var daftar = window.FlowbiteInstances && window.FlowbiteInstances.getInstances ? window.FlowbiteInstances.getInstances('Carousel') : null;
        var carousel = wadah && daftar ? daftar[wadah.id] : null;

        if (!carousel) {
            return;
        }

        var kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');
        var tombolJeda = wadah.querySelector('[data-jeda-carousel]');
        var dijeda = kurangiGerak.matches;
        var terlihat = true;
        var pewaktu = null;

        if (kurangiGerak.matches) {
            wadah.querySelectorAll('[data-carousel-item]').forEach(function (butir) {
                butir.classList.remove('duration-700');
                butir.classList.add('duration-0');
            });
        }

        function berhenti() {
            if (pewaktu !== null) {
                window.clearInterval(pewaktu);
                pewaktu = null;
            }
        }

        function jalankan() {
            berhenti();
            if (!dijeda && terlihat && !document.hidden) {
                pewaktu = window.setInterval(function () { carousel.next(); }, JEDA);
            }
        }

        function tampilkanJeda() {
            if (!tombolJeda) {
                return;
            }
            tombolJeda.setAttribute('aria-pressed', dijeda ? 'true' : 'false');
            tombolJeda.setAttribute('aria-label', dijeda ? 'Putar foto bergeser' : 'Jeda foto bergeser');
            tombolJeda.querySelector('[data-ikon-jeda]').classList.toggle('hidden', dijeda);
            tombolJeda.querySelector('[data-ikon-putar]').classList.toggle('hidden', !dijeda);
        }

        if (tombolJeda) {
            tombolJeda.addEventListener('click', function () {
                dijeda = !dijeda;
                tampilkanJeda();
                jalankan();
            });
        }

        // Pindah manual: hitung ulang jeda supaya foto baru tidak langsung berganti.
        wadah.querySelectorAll('[data-carousel-prev], [data-carousel-next], [data-carousel-slide-to]').forEach(function (tombol) {
            tombol.addEventListener('click', jalankan);
        });

        var awalX = null;
        var awalY = null;
        wadah.addEventListener('touchstart', function (e) {
            awalX = e.touches[0].clientX;
            awalY = e.touches[0].clientY;
        }, { passive: true });
        wadah.addEventListener('touchend', function (e) {
            if (awalX === null) {
                return;
            }
            var dx = e.changedTouches[0].clientX - awalX;
            var dy = e.changedTouches[0].clientY - awalY;
            awalX = null;
            if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy)) {
                if (dx < 0) {
                    carousel.next();
                } else {
                    carousel.prev();
                }
                jalankan();
            }
        }, { passive: true });

        if ('IntersectionObserver' in window) {
            new IntersectionObserver(function (entri) {
                terlihat = entri[0].isIntersecting;
                jalankan();
            }).observe(wadah);
        }
        document.addEventListener('visibilitychange', jalankan);

        tampilkanJeda();
        jalankan();
    }

    if (document.readyState === 'complete') {
        mulai();
    } else {
        window.addEventListener('load', mulai);
    }
})();
