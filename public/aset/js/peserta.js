/**
 * Halaman Peserta publik (`/peserta`, F9-12).
 *
 * Halaman pertama sudah dirender server, jadi tanpa skrip formulir GET dan
 * tombol halaman tetap bekerja. Dengan skrip:
 * - mengetik di kotak cari (jeda 350 ms), mengganti saringan, atau menekan
 *   Cari/halaman mengambil `/peserta/data` (JSON) tanpa muat ulang;
 * - selama memuat, daftar diganti kerangka (`[data-kerangka-peserta]`);
 * - kartu (ponsel) dan baris tabel (layar lebar) dibuat dari `<template>` dan
 *   diisi dengan `textContent` (tanpa HTML dari data);
 * - memilih kelompok umur menyembunyikan nomor lomba kelompok umur lain;
 * - nomor lomba yang dipilih menampilkan ilustrasinya (`data-gambar` pada
 *   `<option>`, G9-05, F9-17) di `[data-ilustrasi-nomor]`;
 * - alamat peramban ikut diperbarui supaya hasil bisa dibagikan.
 *
 * Tanpa skrip sebaris (CSP, K127). Dimuat sebagai `<script src>`.
 */
(function () {
    'use strict';

    var form = document.querySelector('[data-saring-peserta]');
    var wadah = document.querySelector('[data-wadah-peserta]');

    if (!form || !wadah || !window.fetch || !window.URLSearchParams) {
        return;
    }

    var alamatData = form.getAttribute('data-alamat-data');
    var alamatHalaman = form.getAttribute('action');
    var cari = form.querySelector('[name="q"]');
    var pilihKu = form.querySelector('[name="ku"]');
    var pilihNomor = form.querySelector('[name="nomor"]');
    var pilihJk = form.querySelector('[name="jk"]');
    var tombolHapus = form.querySelector('[data-hapus-saringan]');

    var daftarKartu = wadah.querySelector('[data-daftar-kartu]');
    var daftarBaris = wadah.querySelector('[data-daftar-baris]');
    var kerangka = document.querySelector('[data-kerangka-peserta]');
    var keterangan = document.querySelector('[data-keterangan-peserta]');
    var galat = document.querySelector('[data-pesan-galat]');
    var teksGalat = galat ? galat.querySelector('[data-teks-galat]') : null;
    var teksGalatAwal = teksGalat ? teksGalat.textContent : '';
    var kosongCari = document.querySelector('[data-kosong-cari]');
    var kosongSemua = document.querySelector('[data-kosong-semua]');
    var navHalaman = document.querySelector('[data-halaman-peserta]');
    var posisi = document.querySelector('[data-posisi-halaman]');
    var keSebelum = document.querySelector('[data-ke-halaman="sebelum"]');
    var keBerikut = document.querySelector('[data-ke-halaman="berikut"]');

    var templatKartu = document.querySelector('[data-templat-kartu]');
    var templatBaris = document.querySelector('[data-templat-baris]');
    var templatNomor = document.querySelector('[data-templat-nomor]');

    var ilustrasi = document.querySelector('[data-ilustrasi-nomor]');

    var KELAS_NONAKTIF = ['pointer-events-none', 'cursor-not-allowed', 'opacity-60'];
    var pengendali = null;
    var jeda = null;
    var halamanSekarang = 1;

    function tampil(el, ya) {
        if (el) {
            el.classList.toggle('hidden', !ya);
        }
    }

    function angka(n) {
        return Number(n).toLocaleString('id-ID');
    }

    function saringan(halaman) {
        var p = new URLSearchParams();
        var q = cari.value.trim();

        if (q !== '') { p.set('q', q); }
        if (pilihKu.value !== '') { p.set('ku', pilihKu.value); }
        if (pilihNomor.value !== '') { p.set('nomor', pilihNomor.value); }
        if (pilihJk.value !== '') { p.set('jk', pilihJk.value); }
        if (halaman > 1) { p.set('halaman', String(halaman)); }

        return p;
    }

    function adaSaringan() {
        return cari.value.trim() !== '' || pilihKu.value !== '' || pilihNomor.value !== '' || pilihJk.value !== '';
    }

    /** Nomor lomba hanya dari kelompok umur yang dipilih. */
    function rapikanNomor() {
        var ku = pilihKu.value;
        var grup = pilihNomor.querySelectorAll('optgroup');

        for (var i = 0; i < grup.length; i++) {
            var cocok = ku === '' || grup[i].getAttribute('data-ku') === ku;
            grup[i].hidden = !cocok;
            grup[i].disabled = !cocok;
        }

        var terpilih = pilihNomor.options[pilihNomor.selectedIndex];

        if (terpilih && terpilih.parentNode && terpilih.parentNode.disabled) {
            pilihNomor.value = '';
        }

        tampilkanIlustrasi();
    }

    /** Ilustrasi G9-05 nomor lomba yang dipilih, bila berkasnya ada. */
    function tampilkanIlustrasi() {
        if (!ilustrasi) {
            return;
        }

        var terpilih = pilihNomor.value === '' ? null : pilihNomor.options[pilihNomor.selectedIndex];
        var gambar = terpilih ? terpilih.getAttribute('data-gambar') : null;

        if (gambar) {
            ilustrasi.querySelector('[data-gambar-nomor]').setAttribute('src', gambar);
            ilustrasi.querySelector('[data-nama-nomor]').textContent = terpilih.textContent;
        }

        tampil(ilustrasi, !!gambar);
    }

    function isi(akar, p) {
        akar.querySelector('[data-isi="nama"]').textContent = p.nama;
        akar.querySelector('[data-isi="sekolah"]').textContent = p.sekolah;
        akar.querySelector('[data-isi="nomor_atlet"]').textContent = p.nomor_atlet || '–';
        akar.querySelector('[data-isi="kelompok_umur"]').textContent = p.kelompok_umur;
        akar.querySelector('[data-isi="jenis_kelamin"]').textContent = p.jenis_kelamin;

        var daftar = akar.querySelector('[data-isi="nomor"]');
        daftar.textContent = '';

        (p.nomor || []).forEach(function (n) {
            var li = templatNomor.content.firstElementChild.cloneNode(true);
            li.textContent = n;
            daftar.appendChild(li);
        });

        return akar;
    }

    function aturTombol(tombol, halaman, aktif) {
        if (!tombol) {
            return;
        }

        tombol.setAttribute('href', alamatHalaman + (function () {
            var p = saringan(halaman).toString();
            return p === '' ? '' : '?' + p;
        })());
        tombol.setAttribute('data-halaman', String(halaman));

        KELAS_NONAKTIF.forEach(function (k) { tombol.classList.toggle(k, !aktif); });

        if (aktif) {
            tombol.removeAttribute('aria-disabled');
            tombol.removeAttribute('tabindex');
        } else {
            tombol.setAttribute('aria-disabled', 'true');
            tombol.setAttribute('tabindex', '-1');
        }
    }

    function tulis(data) {
        var kartu = document.createDocumentFragment();
        var baris = document.createDocumentFragment();

        data.baris.forEach(function (p) {
            kartu.appendChild(isi(templatKartu.content.firstElementChild.cloneNode(true), p));
            baris.appendChild(isi(templatBaris.content.querySelector('tr').cloneNode(true), p));
        });

        daftarKartu.textContent = '';
        daftarBaris.textContent = '';
        daftarKartu.appendChild(kartu);
        daftarBaris.appendChild(baris);

        var disaring = adaSaringan();

        if (data.total === 0) {
            keterangan.textContent = disaring ? 'Tidak ada atlet yang cocok.' : 'Belum ada atlet.';
        } else {
            var awal = (data.halaman - 1) * data.per_halaman + 1;
            keterangan.textContent = 'Menampilkan ' + angka(awal) + '–' + angka(awal + data.baris.length - 1)
                + ' dari ' + angka(data.total) + ' atlet';
        }

        halamanSekarang = data.halaman;
        tampil(wadah, data.total > 0);
        tampil(kosongCari, data.total === 0 && disaring);
        tampil(kosongSemua, data.total === 0 && !disaring);
        tampil(navHalaman, data.jumlah_halaman > 1);

        if (posisi) {
            posisi.textContent = 'Halaman ' + data.halaman + ' dari ' + data.jumlah_halaman;
        }

        aturTombol(keSebelum, Math.max(1, data.halaman - 1), data.halaman > 1);
        aturTombol(keBerikut, Math.min(data.jumlah_halaman, data.halaman + 1), data.halaman < data.jumlah_halaman);
    }

    function muat(halaman, gulir) {
        if (pengendali) {
            pengendali.abort();
        }

        pengendali = window.AbortController ? new AbortController() : null;

        var p = saringan(halaman);
        var qs = p.toString();

        tampil(galat, false);
        tampil(wadah, false);
        tampil(kosongCari, false);
        tampil(kosongSemua, false);
        tampil(kerangka, true);
        tampil(tombolHapus, adaSaringan());
        keterangan.textContent = 'Mencari…';

        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', alamatHalaman + (qs === '' ? '' : '?' + qs));
        }

        fetch(alamatData + (qs === '' ? '' : '?' + qs), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            signal: pengendali ? pengendali.signal : undefined,
        })
            .then(function (r) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    if (!r.ok) {
                        throw new Error(data && data.pesan ? data.pesan : '');
                    }

                    return data;
                });
            })
            .then(function (data) {
                tampil(kerangka, false);
                tulis(data);

                if (gulir) {
                    keterangan.scrollIntoView({ block: 'start' });
                }
            })
            .catch(function (e) {
                if (e && e.name === 'AbortError') {
                    return;
                }

                tampil(kerangka, false);
                tampil(wadah, daftarKartu.children.length > 0);

                if (teksGalat) {
                    teksGalat.textContent = e && e.message ? e.message : teksGalatAwal;
                }

                tampil(galat, true);
            });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        window.clearTimeout(jeda);
        muat(1, false);
    });

    cari.addEventListener('input', function () {
        window.clearTimeout(jeda);
        jeda = window.setTimeout(function () { muat(1, false); }, 350);
    });

    pilihKu.addEventListener('change', function () {
        rapikanNomor();
        muat(1, false);
    });

    pilihNomor.addEventListener('change', tampilkanIlustrasi);

    [pilihNomor, pilihJk].forEach(function (el) {
        el.addEventListener('change', function () { muat(1, false); });
    });

    if (tombolHapus) {
        tombolHapus.addEventListener('click', function (e) {
            e.preventDefault();
            cari.value = '';
            pilihKu.value = '';
            pilihNomor.value = '';
            pilihJk.value = '';
            rapikanNomor();
            muat(1, false);
            cari.focus();
        });
    }

    [keSebelum, keBerikut].forEach(function (tombol) {
        if (!tombol) {
            return;
        }

        tombol.addEventListener('click', function (e) {
            e.preventDefault();

            if (tombol.getAttribute('aria-disabled') === 'true') {
                return;
            }

            var tujuan = parseInt(tombol.getAttribute('data-halaman') || '', 10);

            if (!tujuan) {
                tujuan = tombol === keSebelum ? halamanSekarang - 1 : halamanSekarang + 1;
            }

            muat(Math.max(1, tujuan), true);
        });
    });

    var awal = parseInt(new URLSearchParams(window.location.search).get('halaman') || '1', 10);
    halamanSekarang = awal > 0 ? awal : 1;
    rapikanNomor();
}());
