/*
 * Tombol unduh ikon (`komponen('tombol_unduh')`, F11-10, F11-13, K177).
 * Dimuat tata letak admin dan publik.
 *
 * - Kursor (desktop): label kecil tampil saat kursor di atas tombol (CSS
 *   `group-hover`), klik langsung mengunduh. Skrip ini tidak ikut campur.
 * - Papan ketik: fokus sudah menampilkan label (`group-focus-visible`), Enter
 *   langsung mengunduh. Spasi juga mengunduh (tautan bawaannya menggulir).
 * - Layar sentuh: bila label tertutup, ketukan pertama hanya membuka label
 *   (`data-tampil-label`) dan mengumumkan "Ketuk sekali lagi untuk
 *   mengunduh …" lewat `role="status"`; ketukan saat label terbuka langsung
 *   mengunduh. Label tertutup lagi bila mengetuk di luar tombol, membuka
 *   tombol unduh lain, atau setelah LAMA_TERBUKA milidetik tanpa ketukan.
 *
 * Nama aksesibel tetap dari `aria-label`; label kecil `aria-hidden`.
 */
(function () {
    'use strict';

    var PILIH = '[data-tombol-unduh]';
    var LAMA_TERBUKA = 4000;

    var terbuka = null;
    var waktuTutup = 0;
    var sentuhTerakhir = null;
    var pengumuman = null;

    function umumkan(teks) {
        if (!pengumuman) {
            pengumuman = document.createElement('p');
            pengumuman.className = 'sr-only';
            pengumuman.setAttribute('role', 'status');
            pengumuman.setAttribute('data-tombol-unduh-status', '');
            document.body.appendChild(pengumuman);
        }
        pengumuman.textContent = '';
        window.setTimeout(function () {
            pengumuman.textContent = teks;
        }, 50);
    }

    function tutup() {
        window.clearTimeout(waktuTutup);
        if (terbuka) {
            terbuka.removeAttribute('data-tampil-label');
            terbuka = null;
        }
    }

    function buka(tombol) {
        tutup();
        terbuka = tombol;
        tombol.setAttribute('data-tampil-label', '');
        waktuTutup = window.setTimeout(tutup, LAMA_TERBUKA);
    }

    /** "Unduh PDF hasil Lari 60 m" menjadi "Ketuk sekali lagi untuk mengunduh PDF hasil Lari 60 m." */
    function kalimatUlang(tombol) {
        var nama = (tombol.getAttribute('aria-label') || tombol.textContent || '').trim();
        var sisa = nama.replace(/^unduh\s+/i, '');
        return 'Ketuk sekali lagi untuk mengunduh' + (sisa ? ' ' + sisa : '') + '.';
    }

    document.addEventListener('pointerdown', function (e) {
        var tombol = e.target.closest ? e.target.closest(PILIH) : null;

        if (e.pointerType === 'mouse') {
            sentuhTerakhir = null;
            return;
        }
        // Ketukan di luar tombol yang terbuka menutup labelnya.
        if (terbuka && tombol !== terbuka) {
            tutup();
        }
        sentuhTerakhir = tombol ? { tombol: tombol, waktu: Date.now() } : null;
    }, true);

    document.addEventListener('click', function (e) {
        var tombol = e.target.closest ? e.target.closest(PILIH) : null;
        var sentuh = sentuhTerakhir;
        sentuhTerakhir = null;

        // Klik kursor atau Enter papan ketik: tidak ada ketukan sentuh sebelumnya.
        if (!tombol || !sentuh || sentuh.tombol !== tombol || Date.now() - sentuh.waktu > 2000) {
            return;
        }
        if (tombol === terbuka) {
            // Label sudah tampil: ketukan ini mengunduh.
            tutup();
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        buka(tombol);
        umumkan(kalimatUlang(tombol));
    }, true);

    document.addEventListener('keydown', function (e) {
        var tombol = e.target.closest ? e.target.closest(PILIH) : null;
        if (tombol && tombol.tagName === 'A' && (e.key === ' ' || e.key === 'Spacebar')) {
            e.preventDefault();
            tombol.click();
        }
    });
}());
