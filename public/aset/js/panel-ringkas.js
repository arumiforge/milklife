/*
 * Panel admin ringkas (F11-10, K174 butir 4, 6, 7, 8). Dimuat
 * `tata_letak/admin_tw.php` di semua halaman admin.
 *
 * 1. Teks panjang: isi sel tabel (`main td`) dan paragraf di kartu admin
 *    (`main .shadow-lembut p`) yang lebih dari dua baris dipotong (kelas
 *    `teks-ringkas`, blok F11-10 `masukan.css`) dan diberi tombol
 *    "Selengkapnya" yang menampilkan teks penuh ("Lebih sedikit" memotong
 *    lagi). Teks tetap utuh di DOM, jadi pembaca layar membaca semuanya.
 *    Dilewati: sel atau paragraf yang berisi isian, tombol, atau pilihan;
 *    paragraf di dalam formulir (keterangan isian); peringatan (`role="alert"`/`status`, `aria-live`); dan
 *    yang bertanda `data-tanpa-ringkas` (atau di dalamnya). Baris yang muncul
 *    belakangan (DataTables, paginasi) ikut diperiksa lewat MutationObserver;
 *    saat lebar layar berubah, perlu tidaknya tombol dihitung ulang.
 *
 * 2. Tombol aksi di ponsel: di dalam `[data-tombol-aksi]` teks tombol
 *    dibungkus `.label-tombol-aksi`; di bawah 640 piksel CSS menyusunnya tiga
 *    per baris dengan teks terpotong. Tekan pertama pada tombol yang teksnya
 *    terpotong menampilkan teks penuh (`data-tombol-terbuka`, selebar satu
 *    baris) dan mengumumkan "Tekan sekali lagi untuk …"; tekan kedua
 *    menjalankan tindakannya.
 *
 * 3. Kotak cari dan pilihan jumlah baris DataTables: teks label ("Cari",
 *    "Tampilkan … baris") hanya untuk pembaca layar di ponsel.
 *
 * 4. Tombol unduh ikon (`komponen('tombol_unduh')`): ketukan di layar sentuh
 *    menampilkan labelnya sebentar (`data-tampil-label`).
 */
(function () {
    'use strict';

    var isi = document.getElementById('isi');

    if (!isi) {
        return;
    }

    var ponsel = window.matchMedia ? window.matchMedia('(max-width: 639px)') : { matches: false };
    var pengumuman = document.createElement('p');
    pengumuman.className = 'sr-only';
    pengumuman.setAttribute('role', 'status');
    pengumuman.setAttribute('data-panel-ringkas-status', '');
    document.body.appendChild(pengumuman);

    function umumkan(teks) {
        pengumuman.textContent = '';
        window.setTimeout(function () {
            pengumuman.textContent = teks;
        }, 50);
    }

    // ---------- 1. Teks panjang ----------

    var LEWATI = '[role="alert"], [role="status"], [aria-live], [data-tanpa-ringkas], .sr-only, [data-baris-kerangka], .dt-empty, [data-tombol-aksi]';
    var BERISI_KENDALI = 'input, select, textarea, button, [role="button"], [contenteditable], img, svg[role="img"]';
    var nomorRingkas = 0;

    function tampak(el) {
        return el.getClientRects().length > 0 && el.offsetWidth > 0;
    }

    function calon() {
        var hasil = [];
        Array.prototype.forEach.call(isi.querySelectorAll('td, .shadow-lembut p'), function (el) {
            if (el.hasAttribute('data-ringkas') || el.closest(LEWATI) || el.querySelector(BERISI_KENDALI) || (el.tagName === 'P' && el.closest('form'))) {
                return;
            }
            if (el.tagName === 'P' && el.closest('td')) {
                return;
            }
            if (!el.textContent.trim() || !tampak(el)) {
                return;
            }
            hasil.push(el);
        });
        return hasil;
    }

    function siapkan(el) {
        var sasaran = el;

        if (el.tagName === 'TD') {
            // Sel tabel tidak bisa dipotong per baris; isinya dibungkus <div>.
            sasaran = document.createElement('div');
            while (el.firstChild) {
                sasaran.appendChild(el.firstChild);
            }
            el.appendChild(sasaran);
        }

        el.setAttribute('data-ringkas', '');
        sasaran.classList.add('teks-ringkas');
        sasaran.id = sasaran.id || 'teks-ringkas-' + (++nomorRingkas);

        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'tombol-ringkas';
        tombol.textContent = 'Selengkapnya';
        tombol.hidden = true;
        tombol.setAttribute('aria-expanded', 'false');
        tombol.setAttribute('aria-controls', sasaran.id);
        tombol.setAttribute('data-tombol-ringkas', '');
        tombol.addEventListener('click', function () {
            var buka = !sasaran.classList.contains('teks-ringkas-buka');
            sasaran.classList.toggle('teks-ringkas-buka', buka);
            tombol.setAttribute('aria-expanded', buka ? 'true' : 'false');
            tombol.textContent = buka ? 'Lebih sedikit' : 'Selengkapnya';
        });

        if (el.tagName === 'TD') {
            el.appendChild(tombol);
        } else {
            el.insertAdjacentElement('afterend', tombol);
        }

        ukur(sasaran, tombol);
    }

    /** Tombol hanya tampil bila teksnya memang terpotong (atau sedang dibuka). */
    function ukur(sasaran, tombol) {
        if (sasaran.classList.contains('teks-ringkas-buka') || !tampak(sasaran)) {
            return;
        }
        tombol.hidden = sasaran.scrollHeight <= sasaran.clientHeight + 1;
    }

    function ukurSemua() {
        Array.prototype.forEach.call(isi.querySelectorAll('[data-tombol-ringkas]'), function (tombol) {
            var sasaran = document.getElementById(tombol.getAttribute('aria-controls'));
            if (sasaran) {
                ukur(sasaran, tombol);
            }
        });
    }

    // ---------- 2. Tombol aksi di ponsel ----------

    function bungkusLabel(grup) {
        Array.prototype.forEach.call(grup.querySelectorAll('a, button'), function (tombol) {
            if (tombol.hasAttribute('data-tombol-unduh') || tombol.querySelector('.label-tombol-aksi')) {
                return;
            }
            Array.prototype.forEach.call(tombol.childNodes, function (simpul) {
                if (simpul.nodeType === 3 && simpul.textContent.trim()) {
                    var span = document.createElement('span');
                    span.className = 'label-tombol-aksi';
                    span.textContent = simpul.textContent.trim();
                    tombol.replaceChild(span, simpul);
                }
            });
        });
    }

    function tutupLain(grup, kecuali) {
        Array.prototype.forEach.call(grup.querySelectorAll('[data-tombol-terbuka]'), function (t) {
            if (t !== kecuali) {
                t.removeAttribute('data-tombol-terbuka');
            }
        });
    }

    isi.addEventListener('click', function (e) {
        var tombol = e.target.closest ? e.target.closest('[data-tombol-aksi] a, [data-tombol-aksi] button') : null;
        if (!tombol || !ponsel.matches || tombol.hasAttribute('data-tombol-unduh') || tombol.disabled) {
            return;
        }
        var grup = tombol.closest('[data-tombol-aksi]');
        var label = tombol.querySelector('.label-tombol-aksi');

        if (tombol.hasAttribute('data-tombol-terbuka') || !label || label.scrollWidth <= label.clientWidth + 1) {
            tutupLain(grup, null);
            return;
        }

        // Teks terpotong: tekan pertama hanya menampilkan teks penuh.
        e.preventDefault();
        e.stopImmediatePropagation();
        tutupLain(grup, tombol);
        tombol.setAttribute('data-tombol-terbuka', '');
        umumkan('Tekan sekali lagi untuk ' + label.textContent.trim() + '.');
    }, true);

    // ---------- 3. Label kotak cari dan jumlah baris DataTables ----------

    function sembunyikanLabelTabel() {
        Array.prototype.forEach.call(isi.querySelectorAll('.dt-search label, .dt-length label'), function (label) {
            if (label.hasAttribute('data-label-ringkas')) {
                return;
            }
            label.setAttribute('data-label-ringkas', '');
            label.classList.add('max-sm:sr-only');
            // "Cari:" menjadi "Cari" (titik dua tidak perlu dibacakan).
            Array.prototype.forEach.call(label.childNodes, function (simpul) {
                if (simpul.nodeType === 3) {
                    simpul.textContent = simpul.textContent.replace(/:\s*$/, '');
                }
            });
        });
        Array.prototype.forEach.call(isi.querySelectorAll('.dt-search input[type="search"]'), function (kotak) {
            if (!kotak.getAttribute('placeholder')) {
                kotak.setAttribute('placeholder', 'Cari');
            }
        });
        Array.prototype.forEach.call(isi.querySelectorAll('.dt-length'), function (wadah) {
            // Teks lepas di luar <label> ("Tampilkan", "baris") ikut disembunyikan di ponsel.
            Array.prototype.forEach.call(wadah.childNodes, function (simpul) {
                if (simpul.nodeType === 3 && simpul.textContent.trim()) {
                    var span = document.createElement('span');
                    span.className = 'max-sm:sr-only';
                    span.textContent = simpul.textContent;
                    wadah.replaceChild(span, simpul);
                }
            });
        });
    }

    // ---------- 4. Label tombol unduh di layar sentuh ----------

    document.addEventListener('pointerdown', function (e) {
        var tombol = e.target.closest ? e.target.closest('[data-tombol-unduh]') : null;
        if (!tombol || e.pointerType === 'mouse') {
            return;
        }
        tombol.setAttribute('data-tampil-label', '');
        window.clearTimeout(tombol._waktuLabel);
        tombol._waktuLabel = window.setTimeout(function () {
            tombol.removeAttribute('data-tampil-label');
        }, 1800);
    });

    // ---------- Jalankan ----------

    var antre = 0;

    function proses() {
        antre = 0;
        Array.prototype.forEach.call(isi.querySelectorAll('[data-tombol-aksi]'), bungkusLabel);
        sembunyikanLabelTabel();
        calon().forEach(siapkan);
        ukurSemua();
    }

    function jadwalkan() {
        if (!antre) {
            antre = window.requestAnimationFrame ? window.requestAnimationFrame(proses) : window.setTimeout(proses, 16);
        }
    }

    if (window.MutationObserver) {
        new MutationObserver(function (catatan) {
            var perlu = catatan.some(function (c) {
                return Array.prototype.some.call(c.addedNodes, function (n) {
                    return n.nodeType === 1 && !n.hasAttribute('data-tombol-ringkas') && !(n.classList && (n.classList.contains('teks-ringkas') || n.classList.contains('label-tombol-aksi')));
                }) || (c.type === 'attributes' && c.attributeName === 'hidden');
            });
            if (perlu) {
                jadwalkan();
            }
        }).observe(isi, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden'] });
    }

    var waktuUkur = 0;
    window.addEventListener('resize', function () {
        window.clearTimeout(waktuUkur);
        waktuUkur = window.setTimeout(function () {
            jadwalkan();
            ukurSemua();
        }, 150);
    });
    document.addEventListener('toggle', jadwalkan, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', proses);
    } else {
        proses();
    }
    window.addEventListener('load', jadwalkan);
}());
