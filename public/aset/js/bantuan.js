/**
 * Bantuan dan FAQ (F9-04).
 *
 * 1. Halaman `/bantuan` (Tailwind, akordeon Flowbite):
 *    - Pencarian langsung tanpa muat ulang: `[data-cari-bantuan]` menyaring
 *      `[data-butir-faq]` menurut `data-cari` (semua kata harus ada, tanpa
 *      membedakan huruf besar dan tanda aksen). Kategori tanpa hasil, kartu
 *      kategori, dan panduan disembunyikan selama mencari; jumlah hasil
 *      dibacakan lewat `#hasil-cari-bantuan` (`aria-live`).
 *    - Alamat `#faq-<id>` membuka tanya-jawab itu dan menggulir ke sana.
 *
 * 2. Laci bantuan wizard (Bootstrap offcanvas `#laci-bantuan`, sampai F9-05):
 *    tombol `[data-buka-bantuan="<langkah>"]` menampilkan hanya kelompok
 *    `[data-bantuan-langkah="<langkah>"]` di laci. Kotak cari di laci menyaring
 *    dengan cara yang sama.
 *
 * Tanpa skrip sebaris (CSP, K127). Dimuat sebagai `<script src>`.
 */
(function () {
    'use strict';

    function rapikan(teks) {
        return (teks || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function cocok(butir, kata) {
        var teks = rapikan(butir.getAttribute('data-cari'));

        return kata.every(function (k) { return teks.indexOf(k) !== -1; });
    }

    /**
     * Saring butir di dalam `wadah`. Kembalikan jumlah yang tampil.
     */
    function saring(wadah, isian) {
        var kata = rapikan(isian).split(' ').filter(Boolean);
        var tampil = 0;

        wadah.querySelectorAll('[data-butir-faq]').forEach(function (butir) {
            var ya = kata.length === 0 || cocok(butir, kata);
            butir.hidden = !ya;
            if (ya) {
                tampil++;
            }
        });

        wadah.querySelectorAll('[data-kategori-faq]').forEach(function (kategori) {
            var ada = kategori.querySelector('[data-butir-faq]:not([hidden])') !== null;
            kategori.hidden = !ada;
        });

        return tampil;
    }

    // ---------- Halaman /bantuan ----------

    var formulir = document.querySelector('[data-cari-bantuan]');

    if (formulir) {
        var kotak = formulir.querySelector('input[type="search"]');
        var keterangan = document.getElementById('hasil-cari-bantuan');
        var daftar = document.querySelector('[data-daftar-faq]');
        var kosong = document.querySelector('[data-cari-kosong]');
        var lainnya = document.querySelectorAll('[data-bagian-kategori], [data-bagian-panduan]');
        var jeda = null;

        var jalankan = function () {
            var isian = kotak.value;
            var mencari = rapikan(isian) !== '';
            var tampil = saring(daftar, isian);

            lainnya.forEach(function (bagian) { bagian.hidden = mencari; });
            kosong.classList.toggle('hidden', !mencari || tampil > 0);
            keterangan.textContent = !mencari ? '' : (tampil > 0 ? tampil + ' jawaban ditemukan.' : 'Belum ada jawaban yang cocok.');
        };

        formulir.addEventListener('submit', function (e) { e.preventDefault(); jalankan(); });
        kotak.addEventListener('input', function () {
            clearTimeout(jeda);
            jeda = setTimeout(jalankan, 150);
        });
        kotak.addEventListener('search', jalankan);
    }

    function bukaDariAlamat() {
        var cocokAlamat = /^#faq-(\d+)$/.exec(window.location.hash);

        if (!cocokAlamat) {
            return;
        }

        var tombol = document.querySelector('[data-accordion-target="#faq-isi-' + cocokAlamat[1] + '"]');

        if (tombol && tombol.getAttribute('aria-expanded') !== 'true') {
            tombol.click();
        }

        var butir = document.getElementById('faq-' + cocokAlamat[1]);

        if (butir) {
            butir.scrollIntoView({ block: 'start' });
        }
    }

    // Akordeon Flowbite dipasang saat `load`; buka setelahnya.
    if (document.querySelector('[data-daftar-faq]')) {
        window.addEventListener('load', function () { setTimeout(bukaDariAlamat, 0); });
        window.addEventListener('hashchange', bukaDariAlamat);
    }

    // ---------- Laci bantuan wizard ----------

    var laci = document.getElementById('laci-bantuan');

    if (laci) {
        var kotakLaci = laci.querySelector('input[type="search"]');
        var kosongLaci = laci.querySelector('[data-cari-kosong-laci]');
        var langkahAktif = null;

        var kelompokAktif = function () {
            return laci.querySelector('[data-bantuan-langkah="' + langkahAktif + '"]');
        };

        var saringLaci = function () {
            var kelompok = kelompokAktif();

            if (!kelompok) {
                return;
            }

            var isian = kotakLaci ? kotakLaci.value : '';
            var tampil = saring(kelompok, isian);
            kosongLaci.hidden = rapikan(isian) === '' || tampil > 0;
        };

        document.addEventListener('click', function (e) {
            var tombol = e.target.closest('[data-buka-bantuan]');

            if (!tombol) {
                return;
            }

            langkahAktif = tombol.getAttribute('data-buka-bantuan');
            laci.querySelectorAll('[data-bantuan-langkah]').forEach(function (kelompok) {
                kelompok.hidden = kelompok.getAttribute('data-bantuan-langkah') !== langkahAktif;
            });

            if (kotakLaci) {
                kotakLaci.value = '';
            }

            saringLaci();
        });

        if (kotakLaci) {
            kotakLaci.addEventListener('input', saringLaci);
        }
    }
}());
