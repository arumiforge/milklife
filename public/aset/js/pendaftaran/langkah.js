/**
 * Pil langkah, pindah langkah, dan tampilan galat.
 *
 * Bagian dari wizard pendaftaran (`pendaftaran.js`, dipecah di F7-30). Semua
 * bagian dimuat berurutan oleh `app/Views/pendaftaran.php`; keadaan bersama
 * dan fungsi antarbagian ada di `window.Pendaftaran` (`dasar.js`).
 */
(function (W) {
    'use strict';

    if (!W) {
        return;
    }

    // Dari bagian yang dimuat lebih dulu.
    var akar = W.akar;
    var $ = W.$;
    var $$ = W.$$;
    var buat = W.buat;
    var simpanDraf = W.simpanDraf;

    // ------------------------------------------------------------------
    // Langkah dan pil
    // ------------------------------------------------------------------

    var penyiapLangkah = {};
    var pemeriksaLangkah = {};

    function tampilkanLangkah(nomor, fokus) {
        W.draf.langkah = nomor;
        W.draf.terjauh = Math.max(W.draf.terjauh || 1, nomor);
        simpanDraf();

        $$('.langkah', akar).forEach(function (bagian) {
            bagian.hidden = Number(bagian.getAttribute('data-langkah')) !== nomor;
        });

        perbaruiPil();

        if (penyiapLangkah[nomor]) {
            penyiapLangkah[nomor]();
        }

        if (fokus !== false) {
            var judul = $('#judul-langkah-' + nomor);
            if (judul) {
                judul.focus({ preventScroll: true });
            }
            akar.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function perbaruiPil() {
        $$('.pil', akar).forEach(function (pil) {
            var ke = Number(pil.getAttribute('data-ke-langkah'));
            var selesai = ke < W.draf.terjauh || (W.draf.tersimpan && ke < 5) || W.sudahSelesai;
            var aktif = ke === W.draf.langkah && !W.sudahSelesai;
            var terkunci = W.draf.tersimpan ? ke !== 5 : ke > W.draf.terjauh;

            pil.classList.toggle('pil-selesai', !!selesai && !aktif);
            pil.classList.toggle('pil-aktif', aktif);
            pil.disabled = terkunci;
            if (aktif) {
                pil.setAttribute('aria-current', 'step');
            } else {
                pil.removeAttribute('aria-current');
            }
            $('.pil-keadaan', pil).textContent = aktif ? '(sedang diisi)' : (selesai ? '(sudah diisi)' : '');
        });
    }

    function lanjut(dari) {
        var periksa = pemeriksaLangkah[dari];
        var hasil = periksa ? periksa() : [];

        Promise.resolve(hasil).then(function (galat) {
            if (galat === false) {
                return;
            }
            tampilkanGalat(dari, galat || []);
            if (!galat || galat.length === 0) {
                tampilkanLangkah(dari + 1);
            }
        });
    }

    // ------------------------------------------------------------------
    // Galat
    // ------------------------------------------------------------------

    function bersihkanGalat(bagian) {
        $$('.is-invalid', bagian).forEach(function (el) { el.classList.remove('is-invalid'); });
        $$('.invalid-feedback', bagian).forEach(function (el) { el.textContent = ''; });
        $$('.galat-baris', bagian).forEach(function (el) { el.remove(); });
        var ringkasan = $('.ringkasan-galat', bagian);
        ringkasan.hidden = true;
        ringkasan.textContent = '';
    }

    /**
     * @param {number} langkah
     * @param {Array<{jalur: string, pesan: string}>} galat
     */
    function tampilkanGalat(langkah, galat) {
        var bagian = $('#langkah-' + langkah);
        bersihkanGalat(bagian);

        if (galat.length === 0) {
            return;
        }

        var ringkasan = $('.ringkasan-galat', bagian);
        ringkasan.appendChild(buat('p', 'mb-1 font-semibold', galat.length === 1 ? 'Ada 1 isian yang perlu diperbaiki:' : 'Ada ' + galat.length + ' isian yang perlu diperbaiki:'));
        var daftar = buat('ul');
        galat.forEach(function (g) {
            daftar.appendChild(buat('li', null, g.pesan));
            tandaiIsian(bagian, g.jalur, g.pesan);
        });
        ringkasan.appendChild(daftar);
        ringkasan.hidden = false;
        ringkasan.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Isian yang ditandai salah dan mulai diisi lagi: tanda dan pesannya
     * hilang. Ringkasan di atas langkah ikut tersembunyi bila tidak ada
     * isian salah lagi. Semua isian diperiksa ulang saat Lanjut ditekan.
     */
    function hapusGalatIsian(e) {
        var isian = e.target.closest && e.target.closest('.is-invalid');
        if (!isian || !akar.contains(isian)) {
            return;
        }
        isian.classList.remove('is-invalid');
        var umpan = isian.parentElement.querySelector(':scope > .invalid-feedback')
            || (isian.closest('.input-group') && isian.closest('.input-group').querySelector('.invalid-feedback'))
            || $('.invalid-feedback', isian);
        if (umpan) {
            umpan.textContent = '';
        }
        var bagian = isian.closest('.langkah');
        var ringkasan = bagian && $('.ringkasan-galat', bagian);
        if (ringkasan && !$('.is-invalid', bagian)) {
            ringkasan.hidden = true;
        }
    }

    akar.addEventListener('input', hapusGalatIsian);
    akar.addEventListener('change', hapusGalatIsian);

    function tandaiIsian(bagian, jalur, pesan) {
        var sasaran = $('[data-jalur="' + jalur + '"]', bagian);

        if (!sasaran) {
            // Galat atlet dan tim ditulis di baris daftar sementara. Galat konfirmasi
            // tim susulan yang kotaknya belum tampil ditulis di kartu timnya.
            var akarJalur = jalur.split('.').slice(0, 2);
            if (akarJalur[0] === 'konfirmasi') {
                akarJalur[0] = 'beregu';
            }
            var baris = akarJalur[0] === 'turun'
                ? $('[data-turun-nomor="' + akarJalur[1] + '"]', bagian)
                : $('[data-baris-jalur="' + akarJalur.join('.') + '"]', bagian);
            if (baris) {
                baris.appendChild(buat('p', W.KELAS.galatBaris, pesan));
            }
            return;
        }

        sasaran.classList.add('is-invalid');
        var umpan = sasaran.parentElement.querySelector(':scope > .invalid-feedback')
            || (sasaran.closest('.input-group') && sasaran.closest('.input-group').querySelector('.invalid-feedback'))
            || $('.invalid-feedback', sasaran);
        if (umpan) {
            umpan.textContent = pesan;
        }
    }

    // Dipakai bagian lain.
    W.lanjut = lanjut;
    W.pemeriksaLangkah = pemeriksaLangkah;
    W.penyiapLangkah = penyiapLangkah;
    W.perbaruiPil = perbaruiPil;
    W.tampilkanGalat = tampilkanGalat;
    W.tampilkanLangkah = tampilkanLangkah;
}(window.Pendaftaran));
