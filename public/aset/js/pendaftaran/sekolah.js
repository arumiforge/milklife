/**
 * Langkah 1: Data Sekolah (cari NPSN, usulan nama K99, data susulan).
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
    var $ = W.$;
    var $$ = W.$$;
    var ambil = W.ambil;
    var buat = W.buat;
    var ikon = W.ikon;
    var rapikanSpasi = W.rapikanSpasi;
    var simpanDraf = W.simpanDraf;
    var pemeriksaLangkah = W.pemeriksaLangkah;
    var penyiapLangkah = W.penyiapLangkah;
    var tampilkanGalat = W.tampilkanGalat;

    // ------------------------------------------------------------------
    // Langkah 1: Data Sekolah
    // ------------------------------------------------------------------

    var L1 = {
        npsn: $('#sekolah-npsn'),
        cari: $('#sekolah-cari'),
        tanpaNpsn: $('#sekolah-tanpa-npsn'),
        kabupaten: $('#sekolah-kabupaten'),
        nama: $('#sekolah-nama'),
        namaKotak: $('#sekolah-nama-kotak'),
        hasil: $('#sekolah-hasil'),
        usulan: $('#sekolah-nama-usulan'),
        usulanKotak: $('#sekolah-usulan-kotak')
    };

    /** Nama usulan pendaftar yang berlaku (K99): hanya untuk sekolah yang ditemukan lewat NPSN. */
    function namaUsulan() {
        var s = W.draf.sekolah;
        var usulan = rapikanSpasi(s.namaUsulan || '');
        if (s.tanpaNpsn || !s.referensi || usulan === '' || usulan.toLowerCase() === rapikanSpasi(s.referensi.nama).toLowerCase()) {
            return '';
        }
        return usulan;
    }

    function jenjangSekolah() {
        return W.draf.sekolah.referensi && W.draf.sekolah.referensi.jenjang ? W.draf.sekolah.referensi.jenjang : W.draf.sekolah.jenjang;
    }

    function kabupatenSekolah() {
        return W.draf.sekolah.referensi && W.draf.sekolah.referensi.kabupaten ? W.draf.sekolah.referensi.kabupaten : W.draf.sekolah.kabupaten;
    }

    function npsnSekolah() {
        return W.draf.sekolah.tanpaNpsn ? '' : W.draf.sekolah.npsn;
    }

    function susulan() {
        return npsnSekolah() !== '' && W.draf.sekolah.dicari === W.draf.sekolah.npsn
            ? W.draf.sekolah.susulan
            : { tim: {}, individu: {} };
    }

    function tampilkanSekolah() {
        var s = W.draf.sekolah;
        var ref = s.referensi;

        $$('input[name="sekolah-jenjang"]').forEach(function (r) {
            r.checked = r.value === jenjangSekolah();
            r.disabled = !!(ref && ref.jenjang);
        });
        L1.kabupaten.value = kabupatenSekolah() || '';
        L1.kabupaten.disabled = !!(ref && ref.kabupaten);
        L1.npsn.value = s.npsn;
        L1.npsn.disabled = s.tanpaNpsn;
        L1.cari.disabled = s.tanpaNpsn;
        L1.tanpaNpsn.checked = s.tanpaNpsn;
        L1.nama.value = s.nama;
        L1.namaKotak.hidden = !(s.tanpaNpsn || s.status === 'tidak_ditemukan');

        var kembar = !s.tanpaNpsn && ref && Number(ref.nama_kembar || 0) > 0;
        var usulan = namaUsulan();
        if (L1.usulan !== document.activeElement) {
            L1.usulan.value = s.namaUsulan || '';
        }
        L1.usulanKotak.hidden = !(!s.tanpaNpsn && ref && (kembar || s.usulanTerbuka || usulan !== ''));

        L1.hasil.textContent = '';
        if (!s.tanpaNpsn && ref) {
            var kotak = buat('div', 'alert alert-success sekolah-ditemukan');
            kotak.appendChild(ikon('building-check'));
            var isi = buat('div');
            isi.appendChild(buat('strong', 'd-block', usulan || ref.nama));
            if (usulan) {
                isi.appendChild(buat('span', 'd-block small', 'Nama usulan Anda, menunggu persetujuan panitia. Nama di data sekolah: ' + ref.nama + '.'));
            }
            // Letak sekolah membedakan sekolah yang namanya sama (K97).
            if (ref.lokasi) {
                isi.appendChild(buat('span', 'd-block small', ref.lokasi));
            }
            isi.appendChild(buat('span', 'small', [ref.jenjang_label, ref.kabupaten].filter(Boolean).join(' · ') + '. Jenjang dan kabupaten mengikuti data sekolah.'));
            kotak.appendChild(isi);
            L1.hasil.appendChild(kotak);

            // Nama umum yang dipakai sekolah lain juga (K99): tawarkan usulan nama lengkap.
            if (kembar && !usulan) {
                L1.hasil.appendChild(buat('div', 'alert alert-warning small',
                    'Nama ' + ref.nama + ' juga dipakai ' + ref.nama_kembar + ' sekolah lain di data kami. Supaya tidak tertukar di rekap dan bukti pendaftaran, '
                    + 'usulkan nama lengkap sekolah Anda di bawah, misalnya dengan menambahkan nama desa.'));
            } else if (!kembar && L1.usulanKotak.hidden) {
                var tautan = buat('button', 'btn btn-link btn-sm p-0 mb-3', 'Nama sekolah kurang lengkap? Usulkan nama yang benar');
                tautan.type = 'button';
                tautan.addEventListener('click', function () {
                    setelSekolah({ usulanTerbuka: true });
                    L1.usulan.focus();
                });
                L1.hasil.appendChild(tautan);
            }

            var timSekolah = s.susulan.tim || {};
            var jumlahRegu = 0;
            Object.keys(timSekolah).forEach(function (id) { jumlahRegu += Object.keys(timSekolah[id] || {}).length; });
            if (jumlahRegu > 0) {
                L1.hasil.appendChild(buat('div', 'alert alert-info small',
                    'Sekolah ini sudah pernah mendaftar dan sudah punya ' + jumlahRegu + ' regu di ' + Object.keys(timSekolah).length + ' nomor beregu. '
                    + 'Di langkah 3 (Atlet), atlet bisa dimasukkan ke regu yang sudah ada (tanpa biaya regu lagi, centang konfirmasinya) '
                    + 'atau ke regu baru (dengan biaya regu).'));
            }
        } else if (!s.tanpaNpsn && s.status === 'tidak_ditemukan') {
            L1.hasil.appendChild(buat('div', 'alert alert-info small', 'NPSN ' + s.dicari + ' tidak ada di data sekolah kami. Tulis nama sekolah secara lengkap di bawah.'));
        }
    }

    function setelSekolah(perubahan) {
        if (Object.prototype.hasOwnProperty.call(perubahan, 'susulan')) {
            // Konfirmasi regu susulan hanya berlaku untuk regu yang anggota lamanya tidak berubah,
            // dan inti lama yang diturunkan harus masih inti.
            var timLama = (W.draf.sekolah.susulan && W.draf.sekolah.susulan.tim) || {};
            var timBaru = (perubahan.susulan && perubahan.susulan.tim) || {};
            var anggotaRegu = function (tim, kunci) {
                var b = kunci.split(':');
                return tim[b[0]] && typeof tim[b[0]] === 'object' && Array.isArray(tim[b[0]][b[1]]) ? tim[b[0]][b[1]] : null;
            };
            W.draf.konfirmasiSusulan = W.draf.konfirmasiSusulan.filter(function (kunci) {
                var lama = anggotaRegu(timLama, kunci);
                var baru = anggotaRegu(timBaru, kunci);
                return lama !== null && baru !== null && JSON.stringify(lama) === JSON.stringify(baru);
            });
            W.draf.turunCadangan = W.draf.turunCadangan.filter(function (kunci) {
                var b = kunci.split(':');
                return Object.keys(timBaru[b[0]] || {}).some(function (label) {
                    return (timBaru[b[0]][label] || []).some(function (a) { return String(a.id) === b[1] && a.peran === 'inti'; });
                });
            });
        }
        Object.keys(perubahan).forEach(function (k) { W.draf.sekolah[k] = perubahan[k]; });
        W.rincian = null;
        simpanDraf();
        tampilkanSekolah();
    }

    function cariSekolah() {
        var npsn = L1.npsn.value.replace(/\s+/g, '');
        L1.npsn.value = npsn;
        tampilkanGalat(1, []);

        if (!/^\d{8}$/.test(npsn)) {
            tampilkanGalat(1, [{ jalur: 'sekolah.npsn', pesan: 'NPSN harus 8 angka. Periksa lagi NPSN sekolah, atau centang "Sekolah belum punya NPSN".' }]);
            return Promise.resolve(false);
        }

        L1.cari.disabled = true;
        L1.hasil.textContent = 'Mencari sekolah…';

        return ambil(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (jawaban) {
            return jawaban.json();
        }).then(function (isi) {
            L1.cari.disabled = false;

            // NPSN sudah diganti selagi menunggu jawaban: jawaban lama diabaikan.
            if (L1.npsn.value.replace(/\s+/g, '') !== npsn) {
                return false;
            }

            if (isi.status === 'ditemukan') {
                setelSekolah({ npsn: npsn, dicari: npsn, status: 'ditemukan', referensi: isi.sekolah, susulan: isi.susulan, nama: '', namaUsulan: '', usulanTerbuka: false });
                return true;
            }

            if (isi.status === 'tidak_ditemukan') {
                setelSekolah({ npsn: npsn, dicari: npsn, status: 'tidak_ditemukan', referensi: null, susulan: isi.susulan });
                L1.nama.focus();
                return true;
            }

            // SLB, terlalu sering, atau galat lain: Lanjut mencari ulang supaya pesannya tampil lagi.
            setelSekolah({ npsn: npsn, dicari: '', status: isi.status || 'galat', referensi: null, susulan: { tim: {}, individu: {} } });
            tampilkanGalat(1, [{ jalur: 'sekolah.npsn', pesan: isi.pesan || 'NPSN belum bisa dicari. Coba lagi.' }]);
            return false;
        }).catch(function () {
            L1.cari.disabled = false;
            L1.hasil.textContent = '';
            tampilkanGalat(1, [{ jalur: 'sekolah.npsn', pesan: 'Sekolah belum bisa dicari. Periksa sambungan internet, lalu tekan Cari lagi.' }]);
            return false;
        });
    }

    $$('input[name="sekolah-jenjang"]').forEach(function (r) {
        r.addEventListener('change', function () { setelSekolah({ jenjang: r.value }); });
    });
    L1.kabupaten.addEventListener('change', function () { setelSekolah({ kabupaten: L1.kabupaten.value }); });
    L1.nama.addEventListener('input', function () { W.draf.sekolah.nama = L1.nama.value; simpanDraf(); });
    L1.usulan.addEventListener('input', function () { W.draf.sekolah.namaUsulan = L1.usulan.value; simpanDraf(); });
    L1.usulan.addEventListener('change', function () { setelSekolah({ namaUsulan: L1.usulan.value }); });
    L1.npsn.addEventListener('input', function () {
        var npsn = L1.npsn.value.replace(/\D/g, '').slice(0, 8);
        if (npsn !== L1.npsn.value) {
            L1.npsn.value = npsn;
        }
        if (npsn !== W.draf.sekolah.dicari && (W.draf.sekolah.referensi || W.draf.sekolah.status)) {
            // NPSN diganti: hasil pencarian lama tidak berlaku.
            setelSekolah({ npsn: npsn, dicari: '', status: '', referensi: null, susulan: { tim: {}, individu: {} } });
        } else {
            W.draf.sekolah.npsn = npsn;
            simpanDraf();
        }
        if (npsn.length === 8 && npsn !== W.draf.sekolah.dicari) {
            cariSekolah();
        }
    });
    L1.npsn.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            cariSekolah();
        }
    });
    L1.cari.addEventListener('click', cariSekolah);
    L1.tanpaNpsn.addEventListener('change', function () {
        if (L1.tanpaNpsn.checked) {
            setelSekolah({ tanpaNpsn: true, dicari: '', status: '', referensi: null, susulan: { tim: {}, individu: {} } });
        } else {
            setelSekolah({ tanpaNpsn: false });
        }
        if (!L1.tanpaNpsn.checked && W.draf.sekolah.npsn.length === 8 && W.draf.sekolah.dicari !== W.draf.sekolah.npsn) {
            cariSekolah();
        }
    });

    pemeriksaLangkah[1] = function () {
        var s = W.draf.sekolah;

        if (!s.tanpaNpsn && /^\d{8}$/.test(s.npsn) && s.dicari !== s.npsn) {
            return cariSekolah().then(function (berhasil) {
                return berhasil ? pemeriksaLangkah[1]() : false;
            });
        }

        var galat = [];
        if (!jenjangSekolah()) {
            galat.push({ jalur: 'sekolah.jenjang', pesan: 'Jenjang sekolah belum dipilih. Pilih SD/MI, SMP/MTs, atau SMA/SMK/MA.' });
        }
        if (!kabupatenSekolah()) {
            galat.push({ jalur: 'sekolah.kabupaten', pesan: 'Kabupaten belum dipilih.' });
        }
        if (!s.tanpaNpsn) {
            if (!/^\d{8}$/.test(s.npsn)) {
                galat.push({ jalur: 'sekolah.npsn', pesan: 'NPSN harus 8 angka. Bila sekolah belum punya NPSN, centang "Sekolah belum punya NPSN".' });
            } else if (s.status !== 'ditemukan' && s.status !== 'tidak_ditemukan') {
                galat.push({ jalur: 'sekolah.npsn', pesan: 'NPSN ini tidak bisa dipakai. Periksa lagi NPSN-nya.' });
            }
        }
        if ((s.tanpaNpsn || s.status === 'tidak_ditemukan') && rapikanSpasi(s.nama) === '') {
            galat.push({ jalur: 'sekolah.nama', pesan: 'Nama sekolah belum diisi. Tulis nama sekolah secara lengkap.' });
        }
        if (namaUsulan().length > 150) {
            galat.push({ jalur: 'sekolah.nama_usulan', pesan: 'Usulan nama sekolah terlalu panjang. Tulis paling banyak 150 huruf.' });
        }
        return galat;
    };

    penyiapLangkah[1] = tampilkanSekolah;

    // Dipakai bagian lain.
    W.jenjangSekolah = jenjangSekolah;
    W.kabupatenSekolah = kabupatenSekolah;
    W.namaUsulan = namaUsulan;
    W.npsnSekolah = npsnSekolah;
    W.setelSekolah = setelSekolah;
    W.susulan = susulan;
    W.tampilkanSekolah = tampilkanSekolah;
}(window.Pendaftaran));
