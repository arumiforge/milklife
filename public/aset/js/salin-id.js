/*
 * Salin ID pendaftaran dengan sekali klik (F10-08, permintaan pemilik).
 *
 * Setiap teks ID pendaftaran (pola `MAC2A0000126`, sama dengan
 * `IdPendaftaran::sah()`) di halaman publik dibungkus tombol
 * `button.salin-id[data-salin-id]` dengan ikon salin dan garis bawah
 * putus-putus sebagai tanda bisa disalin. Klik menyalin ID ke papan klip dan
 * menampilkan pesan singkat "ID … tersalin" (`[data-salin-pesan]`,
 * `role="status"`).
 *
 * Teks yang muncul belakangan ikut dibungkus lewat MutationObserver: panel
 * "Pendaftaran terkirim" di wizard, kartu Cek Pendaftaran, dan isi modal
 * (misalnya konfirmasi kirim bukti bayar). Teks di dalam tautan, tombol,
 * label, isian, `<script>`, teks `sr-only`, atau elemen bertanda
 * `data-tanpa-salin` tidak diubah.
 * Ikon diambil dari `<template id="templat-salin-id">` tata letak publik.
 * Tanpa JavaScript ID tetap tampil sebagai teks biasa.
 */
(function () {
    'use strict';

    var POLA = /\bMAC\d+[ABC]\d{7,}\b/gi;
    var LEWATI = { A: 1, BUTTON: 1, LABEL: 1, SCRIPT: 1, STYLE: 1, TEXTAREA: 1, INPUT: 1, SELECT: 1, OPTION: 1, TEMPLATE: 1, NOSCRIPT: 1 };
    var templat = document.getElementById('templat-salin-id');
    var pesan = document.querySelector('[data-salin-pesan]');
    var penghitung = null;

    if (!templat || !document.body) {
        return;
    }

    function dilewati(simpul) {
        for (var el = simpul.parentNode; el && el !== document.body; el = el.parentNode) {
            if (el.nodeType !== 1) {
                continue;
            }
            if (LEWATI[el.nodeName] || el.isContentEditable || el.hasAttribute('data-tanpa-salin') || el.hasAttribute('data-salin-id') || el.classList.contains('sr-only')) {
                return true;
            }
        }
        return false;
    }

    function buatTombol(id) {
        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'salin-id';
        tombol.setAttribute('data-salin-id', id);
        tombol.title = 'Klik untuk menyalin ID';
        // Nama untuk pembaca layar lewat aria-label, supaya teks yang disalin atau diblok tetap ID saja.
        tombol.setAttribute('aria-label', 'Salin ID ' + id);

        var teks = document.createElement('span');
        teks.textContent = id;
        tombol.appendChild(teks);

        tombol.appendChild(templat.content.cloneNode(true));
        return tombol;
    }

    function bungkus(simpul) {
        var isi = simpul.nodeValue;
        POLA.lastIndex = 0;
        if (!simpul.parentNode || !POLA.test(isi) || dilewati(simpul)) {
            return;
        }

        var hasil = document.createDocumentFragment();
        var awal = 0;
        POLA.lastIndex = 0;
        isi.replace(POLA, function (cocok, posisi) {
            if (posisi > awal) {
                hasil.appendChild(document.createTextNode(isi.slice(awal, posisi)));
            }
            hasil.appendChild(buatTombol(cocok.toUpperCase()));
            awal = posisi + cocok.length;
            return cocok;
        });
        if (awal < isi.length) {
            hasil.appendChild(document.createTextNode(isi.slice(awal)));
        }
        simpul.parentNode.replaceChild(hasil, simpul);
    }

    function telusuri(akar) {
        if (akar.nodeType === 3) {
            bungkus(akar);
            return;
        }
        if (akar.nodeType !== 1 || LEWATI[akar.nodeName]) {
            return;
        }
        var penjelajah = document.createTreeWalker(akar, NodeFilter.SHOW_TEXT);
        var daftar = [];
        while (penjelajah.nextNode()) {
            daftar.push(penjelajah.currentNode);
        }
        daftar.forEach(bungkus);
    }

    function tampilkanPesan(teks) {
        if (!pesan) {
            return;
        }
        pesan.textContent = teks;
        pesan.hidden = false;
        window.clearTimeout(penghitung);
        penghitung = window.setTimeout(function () {
            pesan.hidden = true;
        }, 2200);
    }

    function salinCadangan(teks) {
        var kotak = document.createElement('textarea');
        kotak.value = teks;
        kotak.setAttribute('readonly', '');
        kotak.className = 'sr-only';
        document.body.appendChild(kotak);
        kotak.select();
        var berhasil = false;
        try {
            berhasil = document.execCommand('copy');
        } catch (e) {
            berhasil = false;
        }
        document.body.removeChild(kotak);
        return berhasil;
    }

    function salin(tombol) {
        var id = tombol.getAttribute('data-salin-id');
        var selesai = function () {
            tombol.classList.add('salin-id-tersalin');
            window.setTimeout(function () { tombol.classList.remove('salin-id-tersalin'); }, 2200);
            tampilkanPesan('ID ' + id + ' tersalin');
        };
        var gagal = function () {
            tampilkanPesan(salinCadangan(id) ? 'ID ' + id + ' tersalin' : 'ID belum bisa disalin. Tekan lama teksnya untuk menyalin.');
        };

        if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
            navigator.clipboard.writeText(id).then(selesai, gagal);
        } else if (salinCadangan(id)) {
            selesai();
        } else {
            gagal();
        }
    }

    document.addEventListener('click', function (kejadian) {
        var tombol = kejadian.target.closest('button[data-salin-id]');
        if (tombol) {
            kejadian.preventDefault();
            salin(tombol);
        }
    });

    telusuri(document.body);

    new MutationObserver(function (daftar) {
        daftar.forEach(function (ubah) {
            if (ubah.type === 'characterData') {
                telusuri(ubah.target);
                return;
            }
            Array.prototype.forEach.call(ubah.addedNodes, function (simpul) {
                if (simpul.nodeType === 1 && simpul.hasAttribute('data-salin-id')) {
                    return;
                }
                telusuri(simpul);
            });
        });
    }).observe(document.body, { childList: true, subtree: true, characterData: true });
}());
