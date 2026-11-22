/**
 * Peta lokasi halaman depan (F10-02, `beranda/_lokasi.php`).
 *
 * Iframe Google Maps baru disisipkan saat kotak `[data-peta-lokasi]`
 * mendekati layar (IntersectionObserver, jarak 300 piksel), supaya tidak ada
 * permintaan ke google.com sebelum pengunjung menggulir ke bagian Lokasi.
 * Peramban tanpa IntersectionObserver langsung mendapat iframe-nya.
 * Isi pengganti (tautan "Buka di Google Maps") tetap di bawah iframe dan
 * tertutup setelah peta dimuat.
 */
(function () {
    'use strict';

    function sisipkan(kotak) {
        if (kotak.querySelector('iframe')) {
            return;
        }

        var bingkai = document.createElement('iframe');
        bingkai.src = kotak.getAttribute('data-peta-src');
        bingkai.title = kotak.getAttribute('data-peta-judul') || 'Peta lokasi';
        bingkai.loading = 'lazy';
        bingkai.referrerPolicy = 'no-referrer-when-downgrade';
        bingkai.allowFullscreen = true;
        bingkai.className = 'absolute inset-0 size-full border-0';
        kotak.appendChild(bingkai);
    }

    function mulai() {
        var semua = document.querySelectorAll('[data-peta-lokasi][data-peta-src]');

        if (!semua.length) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            Array.prototype.forEach.call(semua, sisipkan);
            return;
        }

        var pengamat = new IntersectionObserver(function (catatan) {
            catatan.forEach(function (butir) {
                if (butir.isIntersecting) {
                    pengamat.unobserve(butir.target);
                    sisipkan(butir.target);
                }
            });
        }, { rootMargin: '300px 0px' });

        Array.prototype.forEach.call(semua, function (kotak) {
            pengamat.observe(kotak);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai);
    } else {
        mulai();
    }
}());
