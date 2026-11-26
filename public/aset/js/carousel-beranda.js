/**
 * Slideshow foto publik (F9-03, F10-06), dipakai hero halaman depan dan
 * setiap wadah `[data-carousel-beranda]` lain. Tidak bergantung pada Flowbite.
 *
 * Bentuk HTML (lihat `app/Views/beranda/_kepala.php`):
 *
 *     <div data-carousel-beranda role="region" aria-roledescription="carousel" aria-label="…">
 *         <figure data-carousel-item="active" class="… opacity-100">…<img fetchpriority="high"></figure>
 *         <figure data-carousel-item class="… hidden opacity-0">…<img loading="lazy"></figure>
 *         <div data-indikator-carousel>
 *             <button data-carousel-slide-to="0" aria-current="true" aria-label="Foto 1 dari 2">…</button>
 *             <button data-carousel-slide-to="1" aria-current="false" aria-label="Foto 2 dari 2">…</button>
 *         </div>
 *     </div>
 *
 * - Ganti foto tiap JEDA milidetik (3 detik, K191) dengan memudar; hanya titik
 *   penanda, tanpa tombol maju/mundur. Geser di layar sentuh juga berpindah.
 * - Berhenti selama: pengunjung memilih kurangi gerak (`prefers-reduced-motion`,
 *   juga tanpa animasi), tab tidak terlihat, wadah di luar layar, fokus papan
 *   ketik ada di dalam wadah, atau penunjuk tetikus di atasnya.
 * - Foto selain yang tampil tersembunyi (`hidden`), jadi `loading="lazy"`
 *   menahannya. Foto berikutnya dibuka (masih transparan) SIAPKAN milidetik
 *   sebelum gilirannya, jadi baru diunduh menjelang tampil dan tidak ikut
 *   unduhan awal halaman. Foto yang dipilih lewat titik dimuat saat itu juga.
 */
(function () {
    'use strict';

    var JEDA = 3000;
    // Foto berikutnya mulai diunduh sekian milidetik sebelum gilirannya (kurang dari JEDA).
    var SIAPKAN = 2000;

    function pasang(wadah) {
        var butir = Array.prototype.slice.call(wadah.querySelectorAll('[data-carousel-item]'));
        var titik = Array.prototype.slice.call(wadah.querySelectorAll('[data-carousel-slide-to]'));

        if (butir.length < 2) {
            return;
        }

        var kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)');
        var sekarang = Math.max(0, butir.findIndex(function (b) { return b.getAttribute('data-carousel-item') === 'active'; }));
        var terlihat = true;
        var difokus = false;
        var ditunjuk = false;
        var pewaktu = null;
        var persiapan = null;

        function siapkan(i) {
            butir[i].classList.remove('hidden');
        }

        function tampilkan(baru) {
            baru = (baru + butir.length) % butir.length;
            if (baru === sekarang) {
                return;
            }
            siapkan(baru);
            butir.forEach(function (b, i) {
                var aktif = i === baru;
                b.classList.toggle('opacity-100', aktif);
                b.classList.toggle('opacity-0', !aktif);
                if (aktif) {
                    b.setAttribute('data-carousel-item', 'active');
                    b.removeAttribute('aria-hidden');
                } else {
                    b.setAttribute('data-carousel-item', '');
                    b.setAttribute('aria-hidden', 'true');
                }
            });
            titik.forEach(function (t, i) {
                t.setAttribute('aria-current', i === baru ? 'true' : 'false');
            });
            sekarang = baru;
        }

        function berhenti() {
            window.clearTimeout(pewaktu);
            window.clearTimeout(persiapan);
            pewaktu = null;
            persiapan = null;
        }

        // Satu putaran: siapkan foto berikutnya menjelang gilirannya, lalu tampilkan.
        function jalankan() {
            berhenti();
            if (!kurangiGerak.matches && terlihat && !difokus && !ditunjuk && !document.hidden) {
                persiapan = window.setTimeout(function () { siapkan((sekarang + 1) % butir.length); }, JEDA - SIAPKAN);
                pewaktu = window.setTimeout(function () {
                    tampilkan(sekarang + 1);
                    jalankan();
                }, JEDA);
            }
        }

        butir.forEach(function (b, i) {
            if (i !== sekarang) {
                b.setAttribute('aria-hidden', 'true');
            }
        });

        titik.forEach(function (t) {
            t.addEventListener('click', function () {
                tampilkan(parseInt(t.getAttribute('data-carousel-slide-to'), 10) || 0);
                jalankan();
            });
        });

        wadah.addEventListener('focusin', function () { difokus = true; jalankan(); });
        wadah.addEventListener('focusout', function (e) {
            difokus = wadah.contains(e.relatedTarget);
            jalankan();
        });
        wadah.addEventListener('mouseenter', function () { ditunjuk = true; jalankan(); });
        wadah.addEventListener('mouseleave', function () { ditunjuk = false; jalankan(); });

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
                tampilkan(sekarang + (dx < 0 ? 1 : -1));
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
        if (kurangiGerak.addEventListener) {
            kurangiGerak.addEventListener('change', jalankan);
        }

        jalankan();
    }

    function mulai() {
        document.querySelectorAll('[data-carousel-beranda]').forEach(pasang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai);
    } else {
        mulai();
    }
})();
