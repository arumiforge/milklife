/**
 * Draf isian di `localStorage`.
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
    var DATA = W.DATA;
    var VERSI_DRAF = W.VERSI_DRAF;
    var $ = W.$;

    // ------------------------------------------------------------------
    // Draf
    // ------------------------------------------------------------------

    function drafBaru() {
        return {
            versi: VERSI_DRAF,
            langkah: 1,
            terjauh: 1,
            sekolah: {
                jenjang: '',
                kabupaten: '',
                npsn: '',
                tanpaNpsn: false,
                nama: '',
                namaUsulan: '',
                usulanTerbuka: false,
                dicari: '',
                status: '',
                referensi: null,
                susulan: { tim: {}, individu: {} }
            },
            penanggungJawab: [{ nama: '', nomor_wa: '', foto: '' }],
            atlet: [],
            konfirmasiSusulan: [],
            turunCadangan: [],
            // Kunci acak satu kiriman (K118, B2): dibuat saat Kirim Pendaftaran pertama
            // ditekan dan dipakai lagi untuk kirim ulang, sampai pendaftaran tersimpan.
            kunciKirim: '',
            tersimpan: null
        };
    }

    /**
     * 32 huruf heksadesimal acak untuk `kunci_kirim`.
     */
    function buatKunciKirim() {
        var angka = new Uint8Array(16);
        if (window.crypto && window.crypto.getRandomValues) {
            window.crypto.getRandomValues(angka);
        } else {
            for (var i = 0; i < angka.length; i++) {
                angka[i] = Math.floor(Math.random() * 256);
            }
        }
        return Array.prototype.map.call(angka, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
    }

    function bacaDraf() {
        try {
            var isi = JSON.parse(window.localStorage.getItem(DATA.kunci_draf) || 'null');
            if (isi && isi.versi === VERSI_DRAF && isi.sekolah && Array.isArray(isi.atlet) && Array.isArray(isi.penanggungJawab)) {
                // Konfirmasi regu susulan (K57) berbentuk "nomor:regu" (K105); bentuk lama
                // (id nomor saja) dibuang, jadi kotaknya tampil belum tercentang.
                isi.konfirmasiSusulan = Array.isArray(isi.konfirmasiSusulan)
                    ? isi.konfirmasiSusulan.filter(function (k) { return /^\d+:\S.*$/.test(String(k)); })
                    : [];
                isi.turunCadangan = Array.isArray(isi.turunCadangan)
                    ? isi.turunCadangan.filter(function (k) { return /^\d+:\d+$/.test(String(k)); })
                    : [];
                isi.atlet.forEach(function (a) {
                    if (!a.regu || typeof a.regu !== 'object') {
                        a.regu = {};
                    }
                });
                // Data susulan lama berisi jumlah per nomor, belum per regu: ambil ulang.
                var timLama = (isi.sekolah.susulan && isi.sekolah.susulan.tim) || {};
                W.perluSegarkanSusulan = Object.keys(timLama).some(function (id) { return typeof timLama[id] !== 'object'; });
                if (W.perluSegarkanSusulan) {
                    isi.sekolah.susulan = { tim: {}, individu: (isi.sekolah.susulan && isi.sekolah.susulan.individu) || {} };
                }
                return isi;
            }
        } catch (e) {
            // Draf rusak atau penyimpanan ditolak: mulai dari awal.
        }
        return drafBaru();
    }

    function simpanDraf() {
        if (W.sudahSelesai) {
            return;
        }
        var peringatan = $('#peringatan-draf');
        try {
            window.localStorage.setItem(DATA.kunci_draf, JSON.stringify(W.draf));
            peringatan.hidden = true;
        } catch (e) {
            peringatan.hidden = false;
        }
    }

    function hapusDraf() {
        try {
            window.localStorage.removeItem(DATA.kunci_draf);
        } catch (e) {
            // Abaikan.
        }
    }

    W.draf = bacaDraf();

    // Dipakai bagian lain.
    W.buatKunciKirim = buatKunciKirim;
    W.drafBaru = drafBaru;
    W.hapusDraf = hapusDraf;
    W.simpanDraf = simpanDraf;
}(window.Pendaftaran));
