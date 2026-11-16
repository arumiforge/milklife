/**
 * Wizard pendaftaran (`/pendaftaran`, rancangan 7; rencana Tahap 4).
 *
 * Halaman PHP menyediakan kerangka lima langkah dan data JSON `#data-pendaftaran`
 * (`DataIndukPendaftaran::untukWizard()` ditambah `alamat` dan `kunci_draf`).
 * Skrip ini:
 *
 * - menjalankan pil langkah (langkah yang sudah dilewati bercentang dan bisa dibuka lagi),
 * - menyimpan isian di `localStorage` setiap ada perubahan (draf),
 * - memperkecil foto penanggung jawab di peramban (sisi terpanjang 800 piksel, JPEG),
 * - memeriksa isian untuk kenyamanan. Semua aturan diperiksa ulang di server.
 *
 * Teks dari isian dan data selalu ditulis lewat `textContent`, tidak pernah `innerHTML`.
 */
(function () {
    'use strict';

    var akar = document.getElementById('wizard-pendaftaran');
    var sumber = document.getElementById('data-pendaftaran');

    if (!akar || !sumber) {
        return;
    }

    var DATA = JSON.parse(sumber.textContent);
    var VERSI_DRAF = 1;
    var SISI_FOTO = 800;
    var BATAS_FOTO = 450 * 1024;
    // Bukti transfer dikecilkan di peramban seperti foto (K90); server menyimpan sisi paling besar 1600.
    var SISI_BUKTI = 1600;
    var BATAS_BUKTI = 1500 * 1024;
    var KU = {};
    var NOMOR = {};

    DATA.kelompok_umur.forEach(function (ku) { KU[ku.id] = ku; });
    DATA.nomor_lomba.forEach(function (nomor) { NOMOR[nomor.id] = nomor; });

    var perluSegarkanSusulan = false;
    var draf = bacaDraf();
    var atletDiubah = null;

    // ------------------------------------------------------------------
    // Bantu
    // ------------------------------------------------------------------

    /**
     * `fetch` dengan bilah muat di atas halaman (K89).
     */
    function ambil(alamat, opsi) {
        var bilah = window.Kemajuan;
        if (bilah) {
            bilah.mulai();
        }
        var akhiri = function () {
            if (bilah) {
                bilah.selesai();
            }
        };
        return fetch(alamat, opsi).then(function (jawaban) {
            akhiri();
            return jawaban;
        }, function (galat) {
            akhiri();
            throw galat;
        });
    }

    /**
     * Jendela konfirmasi (`konfirmasi.js`, K89); `window.confirm` bila belum termuat.
     */
    function yakin(judul, kalimat, tombol, jenis) {
        return window.Konfirmasi
            ? window.Konfirmasi.tanya(judul, kalimat, tombol, jenis)
            : Promise.resolve(window.confirm(kalimat));
    }

    function $(pemilih, induk) {
        return (induk || document).querySelector(pemilih);
    }

    function $$(pemilih, induk) {
        return Array.prototype.slice.call((induk || document).querySelectorAll(pemilih));
    }

    function buat(tag, kelas, teks) {
        var el = document.createElement(tag);
        if (kelas) {
            el.className = kelas;
        }
        if (teks !== undefined && teks !== null) {
            el.textContent = teks;
        }
        return el;
    }

    function ikon(nama) {
        var i = buat('i', 'bi bi-' + nama);
        i.setAttribute('aria-hidden', 'true');
        return i;
    }

    function rupiah(angka) {
        return 'Rp ' + String(Math.round(Number(angka) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function rapikanSpasi(teks) {
        return String(teks || '').replace(/\s+/g, ' ').trim();
    }

    function hariIni() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function uid() {
        return 'a' + Date.now().toString(36) + Math.random().toString(36).slice(2, 8);
    }

    function nomorWaSah(isian) {
        var teks = String(isian || '').trim();
        if (teks === '' || !/^\+?[0-9 .\-/()]+$/.test(teks)) {
            return false;
        }
        var angka = teks.replace(/\D/g, '');
        if (angka.charAt(0) === '0') {
            angka = '62' + angka.slice(1);
        } else if (angka.charAt(0) === '8') {
            angka = '62' + angka;
        }
        return angka.indexOf('628') === 0 && angka.length >= 10 && angka.length <= 15;
    }

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
            tersimpan: null
        };
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
                perluSegarkanSusulan = Object.keys(timLama).some(function (id) { return typeof timLama[id] !== 'object'; });
                if (perluSegarkanSusulan) {
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
        if (sudahSelesai) {
            return;
        }
        var peringatan = $('#peringatan-draf');
        try {
            window.localStorage.setItem(DATA.kunci_draf, JSON.stringify(draf));
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

    // ------------------------------------------------------------------
    // Langkah dan pil
    // ------------------------------------------------------------------

    var penyiapLangkah = {};
    var pemeriksaLangkah = {};

    function tampilkanLangkah(nomor, fokus) {
        draf.langkah = nomor;
        draf.terjauh = Math.max(draf.terjauh || 1, nomor);
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
            var selesai = ke < draf.terjauh || (draf.tersimpan && ke < 5) || sudahSelesai;
            var aktif = ke === draf.langkah && !sudahSelesai;
            var terkunci = draf.tersimpan ? ke !== 5 : ke > draf.terjauh;

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
        ringkasan.appendChild(buat('p', 'fw-semibold mb-1', galat.length === 1 ? 'Ada 1 isian yang perlu diperbaiki:' : 'Ada ' + galat.length + ' isian yang perlu diperbaiki:'));
        var daftar = buat('ul', 'mb-0');
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
                baris.appendChild(buat('p', 'galat-baris', pesan));
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
        var s = draf.sekolah;
        var usulan = rapikanSpasi(s.namaUsulan || '');
        if (s.tanpaNpsn || !s.referensi || usulan === '' || usulan.toLowerCase() === rapikanSpasi(s.referensi.nama).toLowerCase()) {
            return '';
        }
        return usulan;
    }

    function jenjangSekolah() {
        return draf.sekolah.referensi && draf.sekolah.referensi.jenjang ? draf.sekolah.referensi.jenjang : draf.sekolah.jenjang;
    }

    function kabupatenSekolah() {
        return draf.sekolah.referensi && draf.sekolah.referensi.kabupaten ? draf.sekolah.referensi.kabupaten : draf.sekolah.kabupaten;
    }

    function npsnSekolah() {
        return draf.sekolah.tanpaNpsn ? '' : draf.sekolah.npsn;
    }

    function susulan() {
        return npsnSekolah() !== '' && draf.sekolah.dicari === draf.sekolah.npsn
            ? draf.sekolah.susulan
            : { tim: {}, individu: {} };
    }

    function tampilkanSekolah() {
        var s = draf.sekolah;
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
            var timLama = (draf.sekolah.susulan && draf.sekolah.susulan.tim) || {};
            var timBaru = (perubahan.susulan && perubahan.susulan.tim) || {};
            var anggotaRegu = function (tim, kunci) {
                var b = kunci.split(':');
                return tim[b[0]] && typeof tim[b[0]] === 'object' && Array.isArray(tim[b[0]][b[1]]) ? tim[b[0]][b[1]] : null;
            };
            draf.konfirmasiSusulan = draf.konfirmasiSusulan.filter(function (kunci) {
                var lama = anggotaRegu(timLama, kunci);
                var baru = anggotaRegu(timBaru, kunci);
                return lama !== null && baru !== null && JSON.stringify(lama) === JSON.stringify(baru);
            });
            draf.turunCadangan = draf.turunCadangan.filter(function (kunci) {
                var b = kunci.split(':');
                return Object.keys(timBaru[b[0]] || {}).some(function (label) {
                    return (timBaru[b[0]][label] || []).some(function (a) { return String(a.id) === b[1] && a.peran === 'inti'; });
                });
            });
        }
        Object.keys(perubahan).forEach(function (k) { draf.sekolah[k] = perubahan[k]; });
        rincian = null;
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
    L1.nama.addEventListener('input', function () { draf.sekolah.nama = L1.nama.value; simpanDraf(); });
    L1.usulan.addEventListener('input', function () { draf.sekolah.namaUsulan = L1.usulan.value; simpanDraf(); });
    L1.usulan.addEventListener('change', function () { setelSekolah({ namaUsulan: L1.usulan.value }); });
    L1.npsn.addEventListener('input', function () {
        var npsn = L1.npsn.value.replace(/\D/g, '').slice(0, 8);
        if (npsn !== L1.npsn.value) {
            L1.npsn.value = npsn;
        }
        if (npsn !== draf.sekolah.dicari && (draf.sekolah.referensi || draf.sekolah.status)) {
            // NPSN diganti: hasil pencarian lama tidak berlaku.
            setelSekolah({ npsn: npsn, dicari: '', status: '', referensi: null, susulan: { tim: {}, individu: {} } });
        } else {
            draf.sekolah.npsn = npsn;
            simpanDraf();
        }
        if (npsn.length === 8 && npsn !== draf.sekolah.dicari) {
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
        if (!L1.tanpaNpsn.checked && draf.sekolah.npsn.length === 8 && draf.sekolah.dicari !== draf.sekolah.npsn) {
            cariSekolah();
        }
    });

    pemeriksaLangkah[1] = function () {
        var s = draf.sekolah;

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

    // ------------------------------------------------------------------
    // Langkah 2: Penanggung Jawab
    // ------------------------------------------------------------------

    var daftarPj = $('#daftar-penanggung-jawab');
    var templatPj = $('#templat-penanggung-jawab');
    var tambahPj = $('#tambah-penanggung-jawab');
    var minimalPj = Math.max(1, DATA.aturan.penanggung_jawab_minimal || 1);
    var maksimalPj = Math.max(minimalPj, DATA.aturan.penanggung_jawab_maksimal || 5);

    function tampilkanPenanggungJawab() {
        while (draf.penanggungJawab.length < minimalPj) {
            draf.penanggungJawab.push({ nama: '', nomor_wa: '', foto: '' });
        }

        daftarPj.textContent = '';
        draf.penanggungJawab.forEach(function (orang, i) {
            var kartu = templatPj.content.firstElementChild.cloneNode(true);
            var awalan = 'pj-' + i + '-';

            $('[data-judul]', kartu).textContent = 'Ofisial ' + (i + 1);

            ['nama', 'nomor_wa'].forEach(function (kunci) {
                var masukan = $('[data-isian="' + kunci + '"]', kartu);
                masukan.id = awalan + kunci;
                masukan.value = orang[kunci] || '';
                masukan.setAttribute('data-jalur', 'penanggung_jawab.' + i + '.' + kunci);
                $('[data-untuk="' + kunci + '"]', kartu).setAttribute('for', masukan.id);
                masukan.addEventListener('input', function () {
                    draf.penanggungJawab[i][kunci] = masukan.value;
                    simpanDraf();
                });
            });

            var kotakFoto = $('[data-isian-foto]', kartu);
            var pratinjau = $('.foto-pratinjau', kartu);
            var berkas = $('[data-isian="foto"]', kartu);
            berkas.id = awalan + 'foto';
            kotakFoto.setAttribute('data-jalur', 'penanggung_jawab.' + i + '.foto');
            $('[data-label-foto]', kartu).id = awalan + 'label-foto';
            berkas.setAttribute('aria-labelledby', awalan + 'label-foto');

            function tampilkanFoto() {
                var ada = !!draf.penanggungJawab[i].foto;
                pratinjau.hidden = !ada;
                $('.foto-kosong', kartu).hidden = ada;
                if (ada) {
                    pratinjau.src = draf.penanggungJawab[i].foto;
                    pratinjau.alt = 'Foto ofisial ' + (i + 1);
                }
                $('[data-teks-foto]', kartu).textContent = ada ? 'Ganti foto' : 'Ambil atau pilih foto';
            }

            berkas.addEventListener('change', function () {
                var dipilih = berkas.files && berkas.files[0];
                var galatFoto = $('[data-galat-foto]', kartu);
                galatFoto.textContent = '';
                if (!dipilih) {
                    return;
                }
                galatFoto.textContent = 'Memperkecil foto…';
                kecilkanFoto(dipilih).then(function (dataUrl) {
                    draf.penanggungJawab[i].foto = dataUrl;
                    simpanDraf();
                    galatFoto.textContent = '';
                    kotakFoto.classList.remove('is-invalid');
                    tampilkanFoto();
                }).catch(function () {
                    galatFoto.textContent = 'Foto belum bisa dibaca. Pilih foto lain (JPG atau PNG) atau ambil ulang dengan kamera.';
                });
                berkas.value = '';
            });

            var hapus = $('[data-hapus]', kartu);
            hapus.hidden = draf.penanggungJawab.length <= minimalPj;
            hapus.setAttribute('aria-label', 'Hapus ofisial ' + (i + 1));
            hapus.addEventListener('click', function () {
                draf.penanggungJawab.splice(i, 1);
                rincian = null;
                simpanDraf();
                tampilkanPenanggungJawab();
            });

            tampilkanFoto();
            daftarPj.appendChild(kartu);
        });

        tambahPj.hidden = draf.penanggungJawab.length >= maksimalPj;
    }

    tambahPj.addEventListener('click', function () {
        if (draf.penanggungJawab.length < maksimalPj) {
            draf.penanggungJawab.push({ nama: '', nomor_wa: '', foto: '' });
            simpanDraf();
            tampilkanPenanggungJawab();
            var masukan = $$('[data-isian="nama"]', daftarPj).pop();
            if (masukan) {
                masukan.focus();
            }
        }
    });

    /**
     * Perkecil foto: sisi terpanjang 800 piksel, JPEG. Kualitas diturunkan
     * bila hasilnya masih terlalu besar.
     *
     * @param {File} berkas
     * @returns {Promise<string>} data URL `data:image/jpeg;base64,…`
     */
    function kecilkanFoto(berkas, sisi, batas) {
        sisi = sisi || SISI_FOTO;
        batas = batas || BATAS_FOTO;
        return new Promise(function (selesai, gagal) {
            if (!berkas.type || berkas.type.indexOf('image/') !== 0) {
                gagal(new Error('bukan gambar'));
                return;
            }
            var alamat = URL.createObjectURL(berkas);
            var gambar = new Image();
            gambar.onload = function () {
                URL.revokeObjectURL(alamat);
                var skala = Math.min(1, sisi / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                var kanvas = document.createElement('canvas');
                kanvas.width = Math.max(1, Math.round(gambar.naturalWidth * skala));
                kanvas.height = Math.max(1, Math.round(gambar.naturalHeight * skala));
                var konteks = kanvas.getContext('2d');
                konteks.fillStyle = '#fff';
                konteks.fillRect(0, 0, kanvas.width, kanvas.height);
                konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

                var kualitas = 0.82;
                var hasil = kanvas.toDataURL('image/jpeg', kualitas);
                while (hasil.length * 0.75 > batas && kualitas > 0.4) {
                    kualitas -= 0.12;
                    hasil = kanvas.toDataURL('image/jpeg', kualitas);
                }
                if (hasil.indexOf('data:image/jpeg;base64,') !== 0 || hasil.length * 0.75 > batas) {
                    gagal(new Error('terlalu besar'));
                    return;
                }
                selesai(hasil);
            };
            gambar.onerror = function () {
                URL.revokeObjectURL(alamat);
                gagal(new Error('tidak terbaca'));
            };
            gambar.src = alamat;
        });
    }

    pemeriksaLangkah[2] = function () {
        var galat = [];
        var jumlah = draf.penanggungJawab.length;

        if (jumlah < minimalPj) {
            galat.push({ jalur: 'penanggung_jawab', pesan: 'Isi minimal ' + minimalPj + ' ofisial.' });
        }

        draf.penanggungJawab.forEach(function (orang, i) {
            var ke = 'Ofisial ' + (i + 1) + ': ';
            if (rapikanSpasi(orang.nama) === '') {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.nama', pesan: ke + 'nama belum diisi.' });
            }
            if (!nomorWaSah(orang.nomor_wa)) {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.nomor_wa', pesan: ke + 'nomor WA belum benar. Tulis nomor ponsel yang dipakai untuk WA, misalnya 0812-3456-7890.' });
            }
            if (!orang.foto) {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.foto', pesan: ke + 'foto belum diisi. Ambil foto dengan kamera atau pilih dari galeri.' });
            }
        });

        return galat;
    };

    penyiapLangkah[2] = tampilkanPenanggungJawab;

    // ------------------------------------------------------------------
    // Langkah 3: Atlet
    // ------------------------------------------------------------------

    var L3 = {
        formulir: $('#formulir-atlet'),
        judul: $('#formulir-atlet-judul'),
        nama: $('#atlet-nama'),
        nisn: $('#atlet-nisn'),
        lahir: $('#atlet-tanggal-lahir'),
        jk: $('#atlet-jenis-kelamin'),
        ku: $('#atlet-ku'),
        nomor: $('#atlet-nomor'),
        individu: $('#atlet-nomor-individu .daftar-pilihan-nomor'),
        beregu: $('#atlet-nomor-beregu .daftar-pilihan-nomor'),
        galatNomor: $('#atlet-nomor-galat'),
        simpan: $('#atlet-simpan'),
        batal: $('#atlet-batal')
    };

    L3.lahir.max = hariIni();

    var maksimalIndividu = DATA.aturan.nomor_individu_maksimal || 2;
    var kuotaNomor = DATA.aturan.atlet_per_nomor_maksimal || null;

    function namaKu(ku) {
        return ku.nama + (ku.teks_tahun_lahir ? ' (' + ku.teks_tahun_lahir.toLowerCase() + ')' : '');
    }

    /**
     * Kelompok umur dari jenjang dan tahun lahir (sama dengan `KelompokUmurAtlet`, K95):
     * jenjang menyaring kelompok umur dulu, lalu tahun lahir memilih di antaranya.
     */
    function masukKu(tahun, ku) {
        var awal = ku.tahun_lahir_awal;
        var akhir = ku.tahun_lahir_akhir;
        if (awal === null && akhir === null) {
            return false;
        }
        return (awal === null || tahun >= awal) && (akhir === null || tahun <= akhir);
    }

    function tentukanKu(tahun, jenjang) {
        var untukJenjang = DATA.kelompok_umur.filter(function (ku) { return ku.jenjang.indexOf(jenjang) !== -1; });
        var cocok = null;
        untukJenjang.some(function (ku) {
            if (masukKu(tahun, ku)) {
                cocok = ku;
                return true;
            }
            return false;
        });

        if (cocok) {
            return { ku: cocok, galat: null };
        }

        var labelJenjang = DATA.jenjang[jenjang] || jenjang || '-';

        if (!untukJenjang.length) {
            return { ku: null, galat: 'Belum ada kelompok umur yang dibuka untuk ' + labelJenjang + ', jadi atlet ini tidak bisa didaftarkan.' };
        }

        var bagian = untukJenjang.filter(function (ku) { return !!ku.teks_tahun_lahir; }).map(namaKu);
        var rentang = bagian.length ? '. Kelompok umur untuk ' + labelJenjang + ': ' + bagian.join(', ') : '';
        var lain = null;
        DATA.kelompok_umur.some(function (ku) {
            if (ku.jenjang.length && masukKu(tahun, ku)) {
                lain = ku;
                return true;
            }
            return false;
        });

        if (lain) {
            var boleh = lain.jenjang.map(function (k) { return DATA.jenjang[k]; }).join(', ');
            return {
                ku: null,
                galat: 'Atlet lahir tahun ' + tahun + ' masuk ' + lain.nama + ', yang hanya untuk ' + boleh
                    + '. Sekolah ini ' + labelJenjang + ', jadi atlet ini tidak bisa didaftarkan' + rentang + '.'
            };
        }

        return {
            ku: null,
            galat: 'Atlet lahir tahun ' + tahun + ' tidak masuk kelompok umur mana pun untuk ' + labelJenjang + rentang + '. Periksa lagi tanggal lahirnya.'
        };
    }

    function usia(tanggalLahir) {
        if (!DATA.tanggal_acara || !/^\d{4}-\d{2}-\d{2}$/.test(tanggalLahir || '')) {
            return '';
        }
        var l = tanggalLahir.split('-').map(Number);
        var a = DATA.tanggal_acara.split('-').map(Number);
        var bulan = (a[0] - l[0]) * 12 + (a[1] - l[1]) - (a[2] < l[2] ? 1 : 0);
        if (bulan < 0) {
            return '';
        }
        var tahun = Math.floor(bulan / 12);
        var sisa = bulan % 12;
        return DATA.tampilan_usia === 'tahun_bulan' && sisa > 0 ? tahun + ' tahun ' + sisa + ' bulan' : tahun + ' tahun';
    }

    function tanggalSah(teks) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(teks || '')) {
            return false;
        }
        var b = teks.split('-').map(Number);
        var d = new Date(b[0], b[1] - 1, b[2]);
        return d.getFullYear() === b[0] && d.getMonth() === b[1] - 1 && d.getDate() === b[2] && teks <= hariIni() && b[0] >= 1950;
    }

    function jkFormulir() {
        var r = $('input[name="atlet-jk"]:checked');
        return r ? r.value : '';
    }

    function nomorCocokJk(nomor, jk) {
        return nomor.kategori === 'mix' || (nomor.kategori === 'putra' && jk === 'L') || (nomor.kategori === 'putri' && jk === 'P');
    }

    function nomorDipilih() {
        return $$('input[name="atlet-nomor"]:checked', L3.formulir).map(function (c) { return Number(c.value); });
    }

    // ------------------------------------------------------------------
    // Regu beregu (K105)
    // ------------------------------------------------------------------

    /** Mode nama regu (K106): `bebas` = official mengetik nama regu; `ketentuan` = dari daftar admin. */
    var REGU_BEBAS = DATA.aturan.regu_mode === 'bebas';
    var LABEL_REGU = Array.isArray(DATA.aturan.regu_label) && DATA.aturan.regu_label.length ? DATA.aturan.regu_label : null;
    var HURUF = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    /** Kata di depan nama regu dari pengaturan admin (K107), misalnya "Regu" atau "Tim". */
    var AWALAN_REGU = String(DATA.aturan.regu_awalan || 'Regu');

    /** "Regu A" atau "Tim A". */
    function labelReguLengkap(label) {
        return AWALAN_REGU + ' ' + label;
    }

    function escRegex(t) {
        return t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /** Nama regu yang dirapikan seperti `Regu::rapikan()`: "  regu  merah " → "Merah" (juga "Tim Merah" bila awalannya Tim). */
    function rapikanRegu(teks) {
        var t = String(teks || '').replace(/\s+/g, ' ').trim().replace(/^regu(\s+|$)/i, '').trim();
        if (AWALAN_REGU.toLowerCase() !== 'regu') {
            t = t.replace(new RegExp('^' + escRegex(AWALAN_REGU) + '\\s+', 'i'), '').trim();
        }
        return t.toLowerCase().replace(/(^|[\s\-\/.])(\p{Ll})/gu, function (m, a, b) { return a + b.toUpperCase(); });
    }

    function namaReguSah(t) {
        return t.length > 0 && t.length <= 30 && /^[\p{L}\p{N}][\p{L}\p{N} .\-\/]*$/u.test(t);
    }

    /** Bagian id HTML yang aman untuk kunci "nomor:regu" (nama regu boleh berspasi). */
    function idAman(kunci) {
        return String(kunci).replace(/[^A-Za-z0-9]/g, function (c) { return '_' + c.charCodeAt(0).toString(16); });
    }

    /** Nama regu lama sekolah ini di satu nomor. */
    function reguLamaNomor(nomorId) {
        var tim = (susulan().tim || {})[nomorId];
        return tim && typeof tim === 'object' ? Object.keys(tim) : [];
    }

    /** Nama regu di daftar sementara untuk satu nomor (selain `kecuali`). */
    function reguDraf(nomorId, kecuali) {
        var hasil = [];
        draf.atlet.forEach(function (atlet) {
            var p = atlet !== kecuali && atlet.nomor.indexOf(nomorId) !== -1 && atlet.regu ? atlet.regu[nomorId] : null;
            if (p && p.regu && hasil.indexOf(p.regu) === -1) {
                hasil.push(p.regu);
            }
        });
        return hasil;
    }

    /**
     * Nama regu yang boleh dipilih di nomor ini. Ketentuan admin: daftar admin
     * (huruf A, B, C bila tidak ada) sebanyak batas regu, ditambah regu lama.
     * Bebas: usulan dari regu lama dan daftar sementara.
     */
    function labelRegu(nomor) {
        var lama = reguLamaNomor(nomor.id);
        var hasil;
        if (REGU_BEBAS) {
            hasil = lama.slice();
            reguDraf(nomor.id, null).forEach(function (l) {
                if (hasil.indexOf(l) === -1) {
                    hasil.push(l);
                }
            });
            return hasil;
        }
        var daftar = LABEL_REGU || HURUF;
        hasil = daftar.slice(0, nomor.regu_maksimal ? Math.min(Number(nomor.regu_maksimal), daftar.length) : daftar.length);
        lama.forEach(function (l) {
            if (hasil.indexOf(l) === -1) {
                hasil.push(l);
            }
        });
        return hasil;
    }

    /**
     * Pilihan regu di formulir. Huruf otomatis (daftar admin kosong, K107):
     * hanya regu yang sudah dipakai sekolah ini atau daftar sementara, `terpilih`,
     * dan satu regu baru berikutnya, supaya daftar tidak berisi 26 huruf.
     */
    function opsiRegu(nomor, terpilih) {
        var semua = labelRegu(nomor);
        if (LABEL_REGU) {
            return semua;
        }
        var terisi = reguLamaNomor(nomor.id).concat(reguDraf(nomor.id, atletDiubah));
        var hasil = semua.filter(function (l) { return terisi.indexOf(l) !== -1 || l === terpilih; });
        // Satu regu kosong untuk regu baru, bila belum ada di pilihan.
        var adaKosong = hasil.some(function (l) { return terisi.indexOf(l) === -1; });
        var baru = semua.filter(function (l) { return hasil.indexOf(l) === -1; })[0];
        if (!adaKosong && baru) {
            hasil.push(baru);
        }
        return semua.filter(function (l) { return hasil.indexOf(l) !== -1; });
    }

    /** Apakah nama regu boleh dipakai di nomor ini. */
    function reguBoleh(nomor, label) {
        return REGU_BEBAS ? namaReguSah(label) : labelRegu(nomor).indexOf(label) !== -1;
    }

    /** Anggota regu sekolah dari pendaftaran sebelumnya, atau null bila regu ini baru. */
    function reguLama(nomorId, label) {
        var tim = (susulan().tim || {})[nomorId];
        return tim && typeof tim === 'object' && Array.isArray(tim[label]) ? tim[label] : null;
    }

    /** Pilihan regu atlet di satu nomor beregu, atau null bila belum dipilih. */
    function pilihanRegu(atlet, nomorId) {
        var r = atlet.regu && atlet.regu[nomorId];
        return r && namaReguSah(String(r.regu || '')) && (r.peran === 'inti' || r.peran === 'cadangan') ? r : null;
    }

    /** Nama regu sekolah ini di satu nomor: regu lama dan daftar sementara (selain `kecuali`). */
    function reguSekolah(nomorId, kecuali) {
        var hasil = reguLamaNomor(nomorId).slice();
        reguDraf(nomorId, kecuali).forEach(function (l) {
            if (hasil.indexOf(l) === -1) {
                hasil.push(l);
            }
        });
        return hasil;
    }

    /**
     * Regu tunggal (K108): sekolah hanya punya satu regu di nomor ini, jadi
     * nama regunya tidak ditulis ("Estafet 4 × 50 m mixed", bukan "… Regu A").
     */
    function reguTunggal(nomorId) {
        return reguSekolah(nomorId, null).length <= 1;
    }

    function namaRegu(nomor, label) {
        return reguTunggal(nomor.id) ? nomor.nama_lengkap : nomor.nama_lengkap + ' ' + labelReguLengkap(label);
    }

    /**
     * Nomor ini hanya membolehkan satu regu untuk sekolah ini (K108, opsi A):
     * batas regu 1 (atau daftar nama admin berisi satu nama), tanpa regu lama
     * lain. Pilihan regu tidak pernah ditampilkan.
     */
    function hanyaSatuRegu(nomor) {
        if (REGU_BEBAS) {
            return Number(nomor.regu_maksimal) === 1 && reguLamaNomor(nomor.id).length <= 1;
        }
        return labelRegu(nomor).length <= 1;
    }

    /** Nomor beregu yang pilihan regunya dibuka lewat "Daftarkan regu lain" (atlet yang sedang diisi). */
    var reguDibuka = {};

    /** Inti lama yang dipilih menjadi cadangan (K105). */
    function diturunkan(nomorId, atletId) {
        return draf.turunCadangan.indexOf(nomorId + ':' + atletId) !== -1;
    }

    /** Jumlah anggota dan inti satu regu: lama (setelah diturunkan) ditambah daftar sementara selain `kecuali`. */
    function isiRegu(nomor, label, kecuali) {
        var anggota = 0;
        var inti = 0;
        (reguLama(nomor.id, label) || []).forEach(function (a) {
            anggota++;
            if (a.peran === 'inti' && !diturunkan(nomor.id, a.id)) {
                inti++;
            }
        });
        draf.atlet.forEach(function (atlet) {
            var p = atlet !== kecuali && atlet.nomor.indexOf(nomor.id) !== -1 ? pilihanRegu(atlet, nomor.id) : null;
            if (p && p.regu === label) {
                anggota++;
                if (p.peran === 'inti') {
                    inti++;
                }
            }
        });
        return { anggota: anggota, inti: inti };
    }

    /**
     * Regu bawaan untuk atlet baru: regu yang intinya belum lengkap, lalu
     * regu baru yang masih kosong, lalu regu yang masih punya tempat.
     */
    function reguBawaan(nomor) {
        var label = labelRegu(nomor);
        if (REGU_BEBAS) {
            // Usulan nama untuk regu baru: huruf pertama yang belum dipakai.
            HURUF.forEach(function (h) {
                if (label.indexOf(h) === -1) {
                    label.push(h);
                }
            });
        }
        var pilih = function (syarat) {
            for (var i = 0; i < label.length; i++) {
                var isi = isiRegu(nomor, label[i], atletDiubah);
                if (syarat(isi, label[i])) {
                    return label[i];
                }
            }
            return null;
        };
        return pilih(function (isi) { return isi.anggota > 0 && isi.inti < nomor.jumlah_inti && isi.anggota < nomor.batas_anggota; })
            || pilih(function (isi, l) { return isi.anggota === 0 && reguLama(nomor.id, l) === null; })
            || pilih(function (isi) { return isi.anggota < nomor.batas_anggota; })
            || label[0];
    }

    function peranBawaan(nomor, label) {
        return isiRegu(nomor, label, atletDiubah).inti < nomor.jumlah_inti ? 'inti' : 'cadangan';
    }

    /**
     * Pilihan regu dan peran (Inti/Cadangan) untuk nomor beregu yang
     * dicentang di formulir. Mode Bebas: nama regu diketik, dengan usulan
     * dari regu yang sudah ada (K106).
     */
    function kotakRegu(nomor, terpilih) {
        var kotak = buat('div', 'pilihan-regu');
        kotak.setAttribute('data-regu-untuk', String(nomor.id));
        // Regu sekolah ini di nomor ini selain atlet yang sedang diisi. Selama
        // hanya satu dan masih ada tempat, atlet baru masuk regu itu (K108).
        var lain = reguSekolah(nomor.id, atletDiubah);
        var label;
        if (terpilih && reguBoleh(nomor, terpilih.regu)) {
            label = terpilih.regu;
        } else if (!reguDibuka[nomor.id] && lain.length === 1 && isiRegu(nomor, lain[0], atletDiubah).anggota < nomor.batas_anggota) {
            label = lain[0];
        } else {
            label = reguBawaan(nomor);
        }

        var reguId = 'atlet-regu-' + nomor.id;
        var regu;
        if (REGU_BEBAS) {
            regu = buat('input', 'form-control form-control-sm');
            regu.type = 'text';
            regu.maxLength = 30;
            regu.placeholder = 'Misalnya A atau Merah';
            regu.autocomplete = 'off';
            regu.value = label;
            var daftar = buat('datalist');
            daftar.id = 'daftar-regu-' + nomor.id;
            labelRegu(nomor).forEach(function (l) {
                var opsi = buat('option');
                opsi.value = l;
                daftar.appendChild(opsi);
            });
            regu.setAttribute('list', daftar.id);
            kotak.appendChild(daftar);
        } else {
            regu = buat('select', 'form-select form-select-sm');
            var jumlahDraf = {};
            draf.atlet.forEach(function (a) {
                var p = a !== atletDiubah && a.nomor.indexOf(nomor.id) !== -1 ? pilihanRegu(a, nomor.id) : null;
                if (p) {
                    jumlahDraf[p.regu] = (jumlahDraf[p.regu] || 0) + 1;
                }
            });
            opsiRegu(nomor, label).forEach(function (l) {
                var lama = reguLama(nomor.id, l);
                var keterangan = lama ? ' · sudah terdaftar, ' + lama.length + ' atlet' : (jumlahDraf[l] ? ' · ' + jumlahDraf[l] + ' atlet di daftar' : ' · baru');
                var opsi = buat('option', null, labelReguLengkap(l) + keterangan);
                opsi.value = l;
                opsi.selected = l === label;
                regu.appendChild(opsi);
            });
        }
        regu.id = reguId;
        regu.setAttribute('data-regu', String(nomor.id));

        // Inti/Cadangan sebagai dua tombol bersebelahan.
        var nilaiPeran = terpilih && terpilih.regu === label ? terpilih.peran : peranBawaan(nomor, label);
        var peran = buat('div', 'btn-group btn-group-sm pilihan-peran');
        peran.setAttribute('role', 'group');
        peran.setAttribute('aria-label', 'Peran di regu');
        var tombolPeran = {};
        [['inti', 'Inti'], ['cadangan', 'Cadangan']].forEach(function (p) {
            var masukan = buat('input', 'btn-check');
            masukan.type = 'radio';
            masukan.name = 'atlet-peran-' + nomor.id;
            masukan.id = 'atlet-peran-' + nomor.id + '-' + p[0];
            masukan.value = p[0];
            masukan.checked = p[0] === nilaiPeran;
            masukan.setAttribute('data-peran', String(nomor.id));
            var tombol = buat('label', 'btn btn-outline-primary', p[1]);
            tombol.htmlFor = masukan.id;
            peran.appendChild(masukan);
            peran.appendChild(tombol);
            tombolPeran[p[0]] = masukan;
        });

        var pilihPeran = function () {
            var p = peranBawaan(nomor, rapikanRegu(regu.value));
            tombolPeran[p].checked = true;
        };
        regu.addEventListener('change', pilihPeran);

        var l1 = buat('label', 'pilihan-regu-label', AWALAN_REGU);
        l1.htmlFor = reguId;
        var l2 = buat('span', 'pilihan-regu-label', 'Sebagai');
        var b1 = buat('div', 'pilihan-regu-isian');
        b1.appendChild(l1);
        b1.appendChild(regu);
        var b2 = buat('div', 'pilihan-regu-isian');
        b2.appendChild(l2);
        b2.appendChild(peran);
        kotak.appendChild(b1);
        kotak.appendChild(b2);

        // Pilihan regu disembunyikan selama sekolah hanya punya satu regu di
        // nomor ini (K108): atlet otomatis masuk regu itu. Nomor yang hanya
        // membolehkan satu regu tidak pernah menampilkannya (opsi A); selain
        // itu official bisa membukanya lewat "Daftarkan regu lain".
        var satu = hanyaSatuRegu(nomor);
        if (satu || (!reguDibuka[nomor.id] && lain.length <= 1 && (lain.length === 0 || lain[0] === label))) {
            b1.hidden = true;
            kotak.classList.add('pilihan-regu-tunggal');
            if (!satu) {
                var buka = buat('button', 'btn btn-link btn-sm pilihan-regu-lain');
                buka.type = 'button';
                buka.appendChild(ikon('plus-circle'));
                buka.appendChild(document.createTextNode(' Daftarkan ' + AWALAN_REGU.toLowerCase() + ' lain'));
                buka.addEventListener('click', function () {
                    reguDibuka[nomor.id] = true;
                    b1.hidden = false;
                    kotak.classList.remove('pilihan-regu-tunggal');
                    buka.remove();
                    // Langsung arahkan ke regu baru; official tetap bisa memilih regu lama.
                    if (REGU_BEBAS) {
                        var dipakai = labelRegu(nomor).concat(lain);
                        regu.value = HURUF.filter(function (h) { return dipakai.indexOf(h) === -1; })[0] || '';
                    } else {
                        var baru = Array.prototype.filter.call(regu.options, function (o) { return lain.indexOf(o.value) === -1; })[0];
                        if (baru) {
                            regu.value = baru.value;
                        }
                    }
                    pilihPeran();
                    regu.focus();
                });
                kotak.appendChild(buka);
            }
        }
        kotak.appendChild(buat('p', 'pilihan-regu-bantuan', 'Inti: ' + nomor.jumlah_inti + ' atlet yang berlomba di tiap regu. Cadangan: pengganti bila ada inti yang berhalangan.'));
        return kotak;
    }

    /** Pilihan regu yang sedang tampil di formulir: id nomor => {regu, peran}. */
    function reguFormulir() {
        var hasil = {};
        $$('[data-regu-untuk]', L3.formulir).forEach(function (kotak) {
            var id = Number(kotak.getAttribute('data-regu-untuk'));
            var peran = $('[data-peran]:checked', kotak);
            hasil[id] = { regu: rapikanRegu($('[data-regu]', kotak).value), peran: peran ? peran.value : '' };
        });
        return hasil;
    }

    function tampilkanKotakRegu(centang) {
        var kotak = centang.closest('.pilihan-nomor-beregu');
        var regu = kotak && $('[data-regu-untuk]', kotak);
        if (regu) {
            regu.hidden = !centang.checked;
        }
    }

    function isiPilihanNomor() {
        var lahir = L3.lahir.value;
        var jk = jkFormulir();
        var dipilih = nomorDipilih();
        var reguSebelum = reguFormulir();

        if (atletDiubah && dipilih.length === 0 && L3.formulir.getAttribute('data-muat') === '1') {
            dipilih = atletDiubah.nomor.slice();
            reguSebelum = Object.assign({}, atletDiubah.regu || {});
        }
        L3.formulir.removeAttribute('data-muat');

        L3.ku.hidden = true;
        L3.ku.textContent = '';
        L3.ku.classList.remove('info-ku-galat');
        L3.nomor.hidden = true;
        L3.individu.textContent = '';
        L3.beregu.textContent = '';

        if (!tanggalSah(lahir)) {
            return;
        }

        var hasil = tentukanKu(Number(lahir.slice(0, 4)), jenjangSekolah());
        L3.ku.hidden = false;

        if (!hasil.ku) {
            L3.ku.classList.add('info-ku-galat');
            L3.ku.appendChild(ikon('exclamation-triangle'));
            L3.ku.appendChild(document.createTextNode(' ' + hasil.galat));
            return;
        }

        L3.ku.appendChild(ikon('person-badge'));
        L3.ku.appendChild(document.createTextNode(' ' + hasil.ku.nama + (usia(lahir) ? ' · usia ' + usia(lahir) + ' saat lomba' : '')));

        if (!jk) {
            L3.ku.appendChild(buat('span', 'd-block small', 'Pilih jenis kelamin untuk melihat nomor lomba.'));
            return;
        }

        var adaIndividu = false;
        var adaBeregu = false;

        DATA.nomor_lomba.forEach(function (nomor) {
            if (nomor.kelompok_umur_id !== hasil.ku.id || !nomorCocokJk(nomor, jk)) {
                return;
            }
            var wadah = nomor.jenis === 'individu' ? L3.individu : L3.beregu;
            var id = 'atlet-nomor-' + nomor.id;
            var kotak = buat('div', 'form-check');
            var centang = buat('input', 'form-check-input');
            centang.type = 'checkbox';
            centang.name = 'atlet-nomor';
            centang.id = id;
            centang.value = String(nomor.id);
            centang.checked = dipilih.indexOf(nomor.id) !== -1;
            centang.setAttribute('data-jenis', nomor.jenis);
            var label = buat('label', 'form-check-label', nomor.jenis === 'beregu' ? null : nomor.nama_lengkap);
            label.htmlFor = id;
            if (nomor.jenis === 'beregu') {
                // Judul nomor, lalu keterangan singkat per regu (K106).
                label.appendChild(buat('span', 'nomor-beregu-judul', nomor.nama_lengkap));
                var info = buat('span', 'nomor-beregu-info');
                var cadangan = Math.max(0, (nomor.batas_anggota || 0) - (nomor.jumlah_inti || 0));
                [
                    ['inti', nomor.jumlah_inti + ' inti'],
                    cadangan > 0 ? ['cadangan', '+ ' + cadangan + ' cadangan'] : null,
                    nomor.kategori === 'mix' ? ['', 'inti min. ' + nomor.jumlah_putra + ' putra, ' + nomor.jumlah_putri + ' putri'] : null,
                    nomor.regu_maksimal ? ['', 'maks. ' + nomor.regu_maksimal + ' regu'] : null,
                    nomor.biaya_beregu ? ['biaya', rupiah(nomor.biaya_beregu) + ' / regu'] : null
                ].filter(Boolean).forEach(function (b) {
                    info.appendChild(buat('span', 'nomor-beregu-chip' + (b[0] ? ' chip-' + b[0] : ''), b[1]));
                });
                label.appendChild(info);
            }
            kotak.appendChild(centang);
            kotak.appendChild(label);
            if (nomor.jenis === 'beregu') {
                kotak.classList.add('pilihan-nomor-beregu');
                var regu = kotakRegu(nomor, reguSebelum[nomor.id] || null);
                regu.hidden = !centang.checked;
                kotak.appendChild(regu);
            }
            wadah.appendChild(kotak);
            if (nomor.jenis === 'individu') {
                adaIndividu = true;
            } else {
                adaBeregu = true;
            }
        });

        $('#atlet-nomor-individu').hidden = !adaIndividu;
        $('#atlet-nomor-beregu').hidden = !adaBeregu;
        L3.nomor.hidden = !(adaIndividu || adaBeregu);
        if (!adaIndividu && !adaBeregu) {
            L3.ku.appendChild(buat('span', 'd-block small', 'Belum ada nomor lomba ' + hasil.ku.nama + ' untuk jenis kelamin ini.'));
        }
        batasiIndividu();
    }

    function batasiIndividu() {
        var individu = $$('input[name="atlet-nomor"][data-jenis="individu"]', L3.formulir);
        var jumlah = individu.filter(function (c) { return c.checked; }).length;
        individu.forEach(function (c) {
            c.disabled = !c.checked && jumlah >= maksimalIndividu;
        });
    }

    function kosongkanFormulirAtlet() {
        atletDiubah = null;
        reguDibuka = {};
        L3.formulir.reset();
        L3.judul.textContent = 'Tambah atlet';
        $('span', L3.simpan).textContent = 'Tambahkan ke daftar';
        L3.batal.hidden = true;
        bersihkanGalatFormulir();
        isiPilihanNomor();
    }

    function bersihkanGalatFormulir() {
        $$('.is-invalid', L3.formulir).forEach(function (el) { el.classList.remove('is-invalid'); });
        $$('.invalid-feedback', L3.formulir).forEach(function (el) { el.textContent = ''; });
    }

    function galatFormulir(masukan, pesan) {
        masukan.classList.add('is-invalid');
        var umpan = masukan.tagName === 'FIELDSET' ? $('.invalid-feedback', masukan) : masukan.parentElement.querySelector('.invalid-feedback');
        if (umpan) {
            umpan.textContent = pesan;
        }
    }

    function muatAtlet(atlet) {
        atletDiubah = atlet;
        reguDibuka = {};
        L3.nama.value = atlet.nama;
        L3.nisn.value = atlet.nisn;
        L3.lahir.value = atlet.tanggal_lahir;
        $$('input[name="atlet-jk"]').forEach(function (r) { r.checked = r.value === atlet.jenis_kelamin; });
        L3.judul.textContent = 'Ubah atlet ' + atlet.nama;
        $('span', L3.simpan).textContent = 'Simpan perubahan';
        L3.batal.hidden = false;
        bersihkanGalatFormulir();
        L3.formulir.setAttribute('data-muat', '1');
        isiPilihanNomor();
        L3.formulir.scrollIntoView({ behavior: 'smooth', block: 'start' });
        L3.nama.focus({ preventScroll: true });
    }

    // Nama tampil huruf besar lewat CSS (`huruf-besar`) dan diubah ke huruf
    // besar saat disimpan. Isinya tidak ditulis ulang saat mengetik: di
    // keyboard ponsel itu memutus ketikan dan teks bisa hilang setelah spasi.
    L3.nisn.addEventListener('input', function () {
        var nisn = L3.nisn.value.replace(/\D/g, '').slice(0, 10);
        if (nisn !== L3.nisn.value) {
            L3.nisn.value = nisn;
        }
    });
    L3.lahir.addEventListener('change', isiPilihanNomor);
    L3.lahir.addEventListener('input', isiPilihanNomor);
    $$('input[name="atlet-jk"]').forEach(function (r) { r.addEventListener('change', isiPilihanNomor); });
    L3.formulir.addEventListener('change', function (e) {
        if (e.target.name === 'atlet-nomor') {
            batasiIndividu();
            tampilkanKotakRegu(e.target);
        }
    });
    L3.batal.addEventListener('click', kosongkanFormulirAtlet);

    L3.formulir.addEventListener('submit', function (e) {
        e.preventDefault();
        bersihkanGalatFormulir();

        var nama = rapikanSpasi(L3.nama.value).toUpperCase();
        var nisn = L3.nisn.value.trim();
        var lahir = L3.lahir.value;
        var jk = jkFormulir();
        var nomor = nomorDipilih();
        var salah = false;

        if (nama === '') {
            galatFormulir(L3.nama, 'Nama atlet belum diisi.');
            salah = true;
        }
        if (!/^\d{10}$/.test(nisn)) {
            galatFormulir(L3.nisn, 'NISN harus tepat 10 angka.');
            salah = true;
        } else {
            var kembar = draf.atlet.filter(function (a) { return a.nisn === nisn && a !== atletDiubah; })[0];
            if (kembar) {
                galatFormulir(L3.nisn, 'NISN ini sudah dipakai ' + kembar.nama + ' di daftar. Satu atlet cukup didaftarkan sekali dengan semua nomornya.');
                salah = true;
            }
        }
        if (!tanggalSah(lahir)) {
            galatFormulir(L3.lahir, 'Tanggal lahir belum benar. Pilih tanggal lahir dari kalender.');
            salah = true;
        } else if (!tentukanKu(Number(lahir.slice(0, 4)), jenjangSekolah()).ku) {
            galatFormulir(L3.lahir, tentukanKu(Number(lahir.slice(0, 4)), jenjangSekolah()).galat);
            salah = true;
        }
        if (!jk) {
            galatFormulir(L3.jk, 'Jenis kelamin belum dipilih.');
            salah = true;
        }
        if (!salah && nomor.length === 0) {
            L3.galatNomor.textContent = 'Pilih minimal satu nomor individu atau beregu.';
            salah = true;
        } else {
            L3.galatNomor.textContent = '';
        }

        if (salah) {
            var pertama = $('.is-invalid', L3.formulir);
            if (pertama && pertama.focus) {
                pertama.focus();
            }
            return;
        }

        var reguDipilih = reguFormulir();
        var regu = {};
        nomor.forEach(function (id) {
            if (NOMOR[id] && NOMOR[id].jenis === 'beregu' && reguDipilih[id]) {
                regu[id] = reguDipilih[id];
            }
        });
        var data = { nama: nama, nisn: nisn, tanggal_lahir: lahir, jenis_kelamin: jk, nomor: nomor, regu: regu };

        if (atletDiubah) {
            Object.keys(data).forEach(function (k) { atletDiubah[k] = data[k]; });
        } else {
            data.id = uid();
            draf.atlet.push(data);
        }

        simpanDraf();
        kosongkanFormulirAtlet();
        tampilkanDaftarAtlet();
        L3.nama.focus();
    });

    /**
     * Masalah tiap atlet dengan jenjang sekolah dan data induk saat ini
     * (misalnya jenjang diganti setelah atlet ditambahkan).
     */
    function masalahAtlet(atlet) {
        var hasil = tentukanKu(Number(String(atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah());
        if (!hasil.ku) {
            return hasil.galat;
        }
        var individu = 0;
        for (var i = 0; i < atlet.nomor.length; i++) {
            var nomor = NOMOR[atlet.nomor[i]];
            if (!nomor || nomor.kelompok_umur_id !== hasil.ku.id || !nomorCocokJk(nomor, atlet.jenis_kelamin)) {
                return 'Ada nomor lomba yang tidak sesuai lagi. Tekan Ubah dan pilih ulang nomornya.';
            }
            if (nomor.jenis === 'individu') {
                individu++;
            } else if (!pilihanRegu(atlet, nomor.id) || !reguBoleh(nomor, pilihanRegu(atlet, nomor.id).regu)) {
                return 'Belum memilih regu untuk ' + nomor.nama_lengkap + '. Tekan Ubah, lalu pilih regu dan Inti atau Cadangan.';
            }
        }
        if (atlet.nomor.length === 0) {
            return 'Belum ikut nomor lomba apa pun. Tekan Ubah dan pilih nomornya.';
        }
        if (individu > maksimalIndividu) {
            return 'Ikut ' + individu + ' nomor individu, padahal paling banyak ' + maksimalIndividu + '. Tekan Ubah dan kurangi nomornya.';
        }
        return '';
    }

    /**
     * Keadaan tiap regu beregu (sama dengan `KelengkapanTim`, K105). Kunci
     * hasilnya "nomor:regu", berurutan menurut nomor lalu regu.
     */
    function keadaanTim() {
        var tim = {};

        draf.atlet.forEach(function (atlet, indeks) {
            atlet.nomor.forEach(function (id) {
                var nomor = NOMOR[id];
                var p = nomor && nomor.jenis === 'beregu' ? pilihanRegu(atlet, id) : null;
                if (p && reguBoleh(nomor, p.regu)) {
                    var kunci = id + ':' + p.regu;
                    tim[kunci] = tim[kunci] || { kunci: kunci, nomor: nomor, regu: p.regu, anggota: [] };
                    tim[kunci].anggota.push({ atlet: atlet, indeks: indeks, peran: p.peran });
                }
            });
        });

        Object.keys(tim).forEach(function (kunci) {
            var t = tim[kunci];
            var n = t.nomor;
            var nama = namaRegu(n, t.regu);
            var lama = reguLama(n.id, t.regu);
            var intiLama = 0;
            var semua = [];

            t.susulan = lama !== null;
            t.lamaAnggota = lama || [];
            t.lama = t.lamaAnggota.length;
            t.dikonfirmasi = t.susulan && draf.konfirmasiSusulan.indexOf(kunci) !== -1;
            t.pesan = [];

            t.lamaAnggota.forEach(function (a) {
                var peran = a.peran === 'inti' && !diturunkan(n.id, a.id) ? 'inti' : 'cadangan';
                if (peran === 'inti') {
                    intiLama++;
                }
                semua.push({ jk: a.jenis_kelamin, peran: peran });
            });
            t.anggota.forEach(function (a) { semua.push({ jk: a.atlet.jenis_kelamin, peran: a.peran }); });

            var jumlah = semua.length;
            var inti = semua.filter(function (a) { return a.peran === 'inti'; });
            var keterangan = t.susulan && intiLama > 0 ? ' (' + intiLama + ' di antaranya dari pendaftaran sebelumnya)' : '';
            t.inti = t.anggota.filter(function (a) { return a.peran === 'inti'; }).length;

            if (jumlah > n.batas_anggota) {
                if (t.susulan) {
                    var sisa = Math.max(0, n.batas_anggota - t.lama);
                    t.pesan.push(nama + ' sudah punya ' + t.lama + ' atlet dari pendaftaran sebelumnya, dan paling banyak ' + n.batas_anggota + ' atlet. '
                        + (sisa === 0 ? 'Regu ini sudah penuh; pilih regu lain untuk atlet tambahan.' : 'Atlet tambahan paling banyak ' + sisa + '; pindahkan ' + (jumlah - n.batas_anggota) + ' atlet ke regu lain.'));
                } else {
                    t.pesan.push(nama + ' punya ' + jumlah + ' atlet, padahal paling banyak ' + n.batas_anggota + '. Pindahkan ' + (jumlah - n.batas_anggota) + ' atlet ke regu lain atau hapus dari nomor ini.');
                }
            }
            if (inti.length > n.jumlah_inti) {
                t.pesan.push(nama + ' punya ' + inti.length + ' atlet inti' + keterangan + ', padahal satu regu tepat ' + n.jumlah_inti + ' atlet inti. Jadikan '
                    + (inti.length - n.jumlah_inti) + ' atlet cadangan' + (keterangan ? ', atau centang inti lama yang menjadi cadangan.' : '.'));
            } else if (inti.length < n.jumlah_inti) {
                t.pesan.push(jumlah < n.jumlah_inti
                    ? nama + ' baru punya ' + jumlah + ' atlet. Tambah ' + (n.jumlah_inti - jumlah) + ' atlet lagi; satu regu tepat ' + n.jumlah_inti + ' atlet inti.'
                    : nama + ' baru punya ' + inti.length + ' atlet inti' + keterangan + '. Tandai ' + (n.jumlah_inti - inti.length) + ' atlet lagi sebagai Inti; satu regu tepat ' + n.jumlah_inti + ' atlet inti.');
            } else if (n.kategori === 'mix') {
                var putra = inti.filter(function (a) { return a.jk === 'L'; }).length;
                var putri = inti.length - putra;
                if (putra < n.jumlah_putra || putri < n.jumlah_putri) {
                    t.pesan.push(nama + ': atlet inti minimal ' + n.jumlah_putra + ' putra dan ' + n.jumlah_putri + ' putri; sekarang ' + putra + ' putra dan ' + putri + ' putri.');
                }
            }
        });

        // Mode Bebas (K106): banyaknya regu per nomor, termasuk regu lama, dibatasi nomor lombanya.
        if (REGU_BEBAS) {
            var perNomor = {};
            Object.keys(tim).forEach(function (kunci) {
                var id = tim[kunci].nomor.id;
                perNomor[id] = perNomor[id] || [];
                perNomor[id].push(tim[kunci]);
            });
            Object.keys(perNomor).forEach(function (id) {
                var n = NOMOR[id];
                var lama = reguLamaNomor(Number(id));
                var semua = lama.slice();
                perNomor[id].forEach(function (t) {
                    if (semua.indexOf(t.regu) === -1) {
                        semua.push(t.regu);
                    }
                });
                if (n.regu_maksimal && semua.length > n.regu_maksimal) {
                    perNomor[id].forEach(function (t) {
                        if (lama.indexOf(t.regu) === -1) {
                            t.pesan.push(namaRegu(n, t.regu) + ': satu sekolah paling banyak ' + n.regu_maksimal + ' regu di nomor ini, sekarang ' + semua.length
                                + ' regu. Masukkan atletnya ke regu yang sudah ada.');
                        }
                    });
                }
            });
        }

        var urut = {};
        Object.keys(tim).sort(function (a, b) {
            var x = a.split(':');
            var y = b.split(':');
            return urutanNomor(Number(x[0])) - urutanNomor(Number(y[0])) || (x[1] < y[1] ? -1 : x[1] > y[1] ? 1 : 0);
        }).forEach(function (k) { urut[k] = tim[k]; });
        return urut;
    }

    function urutanNomor(id) {
        for (var i = 0; i < DATA.nomor_lomba.length; i++) {
            if (DATA.nomor_lomba[i].id === id) {
                return i;
            }
        }
        return DATA.nomor_lomba.length;
    }

    /**
     * Kuota atlet per sekolah per nomor individu (K51).
     */
    function masalahKuota() {
        var pesan = {};
        if (!kuotaNomor) {
            return pesan;
        }
        var lama = susulan().individu || {};
        var jumlah = {};
        draf.atlet.forEach(function (atlet) {
            atlet.nomor.forEach(function (id) {
                if (NOMOR[id] && NOMOR[id].jenis === 'individu') {
                    jumlah[id] = (jumlah[id] || 0) + 1;
                }
            });
        });
        Object.keys(jumlah).forEach(function (id) {
            var sebelum = Number(lama[id] || 0);
            if (sebelum + jumlah[id] > kuotaNomor) {
                pesan[id] = NOMOR[id].nama_lengkap + ': paling banyak ' + kuotaNomor + ' atlet per sekolah'
                    + (sebelum > 0 ? ', dan sekolah ini sudah mendaftarkan ' + sebelum + ' atlet sebelumnya' : '')
                    + '. Sekarang ' + (sebelum + jumlah[id]) + '. Hapus nomor ini dari ' + (sebelum + jumlah[id] - kuotaNomor) + ' atlet.';
            }
        });
        return pesan;
    }

    /** Tanda Inti atau Cadangan. */
    function lencanaPeran(peran) {
        return buat('span', 'lencana-peran lencana-' + peran, peran === 'inti' ? 'Inti' : 'Cadangan');
    }

    /**
     * Baris atlet nomor individu di daftar sementara. `hapusNomor`: lihat
     * `tombolBaris()`.
     */
    function barisAtlet(atlet, indeks, jalur, keterangan, hapusNomor) {
        var li = buat('li', 'baris-atlet');
        li.setAttribute('data-baris-jalur', jalur);
        var isi = buat('div', 'baris-atlet-isi');
        var nama = buat('strong', 'baris-atlet-nama', atlet.nama);
        isi.appendChild(nama);
        var ku = tentukanKu(Number(String(atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah()).ku;
        isi.appendChild(buat('span', 'baris-atlet-keterangan', ['NISN ' + atlet.nisn, ku ? ku.nama : null, atlet.jenis_kelamin === 'L' ? 'Putra' : 'Putri'].filter(Boolean).join(' · ')));
        if (keterangan) {
            isi.appendChild(buat('span', 'baris-atlet-nomor', keterangan));
        }
        var masalah = masalahAtlet(atlet);
        if (masalah) {
            isi.appendChild(buat('p', 'galat-baris', masalah));
        }
        li.appendChild(isi);

        li.appendChild(tombolBaris(atlet, hapusNomor, false));
        return li;
    }

    /** Teks tombol; `ringkas`: hanya tampil di layar lebar, di ponsel tombolnya berikon saja. */
    function teksTombol(teks, ringkas) {
        return ringkas ? buat('span', 'd-none d-sm-inline', teks) : document.createTextNode(teks);
    }

    /**
     * Tombol Ubah dan Hapus satu atlet. `hapusNomor`: ID nomor lomba yang
     * dihapus; nomor lain atlet itu tetap. Bila atlet tidak punya nomor lain
     * (atau `hapusNomor` kosong), atletnya ikut dihapus.
     */
    function tombolBaris(atlet, hapusNomor, ringkas) {
        var tombol = buat('div', 'baris-atlet-tombol');
        var ubah = buat('button', 'btn btn-sm btn-outline-primary');
        ubah.type = 'button';
        ubah.appendChild(ikon('pencil'));
        ubah.appendChild(teksTombol(' Ubah', ringkas));
        ubah.setAttribute('aria-label', 'Ubah ' + atlet.nama);
        ubah.title = 'Ubah';
        ubah.addEventListener('click', function () { muatAtlet(atlet); });
        var hapus = buat('button', 'btn btn-sm btn-outline-danger');
        hapus.type = 'button';
        hapus.appendChild(ikon('trash'));
        hapus.appendChild(teksTombol(' Hapus', ringkas));
        var namaNomor = (hapusNomor || []).map(function (id) { return NOMOR[id] ? NOMOR[id].nama_lengkap : ''; }).join(', ');
        var sisa = atlet.nomor.filter(function (id) { return (hapusNomor || []).indexOf(id) === -1; });
        hapus.setAttribute('aria-label', 'Hapus ' + (namaNomor ? namaNomor + ' untuk ' : '') + atlet.nama);
        hapus.title = 'Hapus';
        hapus.addEventListener('click', function () {
            var hanyaNomor = namaNomor && sisa.length > 0;
            yakin(
                hanyaNomor ? 'Hapus nomor lomba?' : 'Hapus atlet?',
                hanyaNomor
                    ? namaNomor + ' untuk ' + atlet.nama + ' akan dihapus. Nomor lomba lainnya tetap.'
                    : atlet.nama + ' akan dihapus dari daftar.' + (namaNomor ? ' Atlet ini tidak punya nomor lomba lain.' : ''),
                hanyaNomor ? 'Hapus nomor' : 'Hapus atlet'
            ).then(function (setuju) {
                if (!setuju) {
                    return;
                }
                if (hanyaNomor) {
                    atlet.nomor = sisa;
                } else {
                    draf.atlet.splice(draf.atlet.indexOf(atlet), 1);
                }
                // Tombol Hapus tidak memicu event input/change: total lama tidak berlaku lagi.
                rincian = null;
                if (atletDiubah === atlet) {
                    kosongkanFormulirAtlet();
                }
                simpanDraf();
                tampilkanDaftarAtlet();
            });
        });
        tombol.appendChild(ubah);
        tombol.appendChild(hapus);
        tombol.appendChild(ubah);
        tombol.appendChild(hapus);
        return tombol;
    }

    /**
     * Baris ringkas anggota regu di kartu regu: nomor urut, nama, keterangan,
     * dan tombol. Peran ditandai oleh kelompok Inti/Cadangan tempat baris ini.
     */
    function barisAnggota(a, urut, n) {
        var li = buat('li', 'anggota-regu anggota-' + a.peran);
        li.setAttribute('data-baris-jalur', 'atlet.' + a.indeks);
        li.appendChild(buat('span', 'anggota-regu-urut', String(urut)));
        var isi = buat('div', 'anggota-regu-isi');
        isi.appendChild(buat('strong', 'anggota-regu-nama', a.atlet.nama));
        var ku = tentukanKu(Number(String(a.atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah()).ku;
        isi.appendChild(buat('span', 'anggota-regu-keterangan', ['NISN ' + a.atlet.nisn, ku ? ku.nama : null, a.atlet.jenis_kelamin === 'L' ? 'Putra' : 'Putri'].filter(Boolean).join(' · ')));
        var masalah = masalahAtlet(a.atlet);
        if (masalah) {
            isi.appendChild(buat('p', 'galat-baris', masalah));
        }
        li.appendChild(isi);
        li.appendChild(tombolBaris(a.atlet, [n.id], true));
        return li;
    }

    /** Satu kelompok anggota regu (Inti atau Cadangan) dengan judul dan hitungan. */
    function kelompokPeran(peran, anggota, mulai, n, hitungan, kurang) {
        var wadah = buat('div', 'kelompok-peran kelompok-' + peran);
        var judul = buat('div', 'kelompok-peran-judul');
        judul.appendChild(lencanaPeran(peran));
        judul.appendChild(buat('span', 'kelompok-peran-hitungan' + (kurang ? ' hitungan-salah' : ''), hitungan));
        wadah.appendChild(judul);
        if (anggota.length === 0) {
            wadah.appendChild(buat('p', 'kelompok-peran-kosong', peran === 'inti' ? 'Belum ada atlet inti.' : 'Belum ada cadangan. Cadangan boleh tidak ada.'));
            return wadah;
        }
        var ul = buat('ol', 'anggota-regu-daftar');
        anggota.forEach(function (a, i) { ul.appendChild(barisAnggota(a, mulai + i, n)); });
        wadah.appendChild(ul);
        return wadah;
    }

    /**
     * Teks lencana kartu regu.
     */
    function lencanaTim(lencana, t) {
        var perluKonfirmasi = t.susulan && !t.dikonfirmasi;
        lencana.className = 'badge ' + (t.pesan.length || perluKonfirmasi ? 'text-bg-warning' : 'text-bg-success');
        lencana.textContent = t.pesan.length ? 'Belum lengkap' : (perluKonfirmasi ? 'Perlu dicentang' : (t.susulan ? 'Tambahan anggota' : 'Lengkap'));
    }

    /**
     * Anggota regu yang sudah terdaftar, dengan kotak "Jadikan cadangan" pada
     * setiap inti lama: atlet susulan boleh menjadi inti dengan menurunkan
     * inti lama (K105).
     */
    function daftarReguLama(n, t) {
        var wadah = buat('div', 'regu-lama');
        wadah.setAttribute('data-turun-nomor', String(n.id));
        wadah.appendChild(buat('p', 'small fw-semibold mb-1', 'Anggota yang sudah terdaftar di regu ini:'));
        var ul = buat('ul', 'list-unstyled small mb-2');
        t.lamaAnggota.forEach(function (a) {
            var li = buat('li', 'regu-lama-baris');
            var turun = a.peran === 'inti' && diturunkan(n.id, a.id);
            var namaLama = buat('span', 'regu-lama-nama', a.nama + ' ');
            namaLama.appendChild(lencanaPeran(turun ? 'cadangan' : a.peran));
            if (turun) {
                namaLama.appendChild(buat('span', 'small text-body-secondary', ' (sebelumnya inti)'));
            }
            li.appendChild(namaLama);
            if (a.peran === 'inti') {
                var kotak = buat('span', 'form-check form-check-inline ms-2 mb-0');
                var masukan = buat('input', 'form-check-input');
                masukan.type = 'checkbox';
                masukan.id = 'turun-' + n.id + '-' + a.id;
                masukan.checked = turun;
                masukan.setAttribute('data-jalur', 'turun.' + n.id);
                var label = buat('label', 'form-check-label', 'Jadikan cadangan');
                label.htmlFor = masukan.id;
                masukan.addEventListener('change', function () {
                    var kunci = n.id + ':' + a.id;
                    draf.turunCadangan = draf.turunCadangan.filter(function (k) { return k !== kunci; });
                    if (masukan.checked) {
                        draf.turunCadangan.push(kunci);
                    }
                    rincian = null;
                    simpanDraf();
                    tampilkanDaftarAtlet();
                });
                kotak.appendChild(masukan);
                kotak.appendChild(label);
                li.appendChild(kotak);
            }
            ul.appendChild(li);
        });
        wadah.appendChild(ul);
        return wadah;
    }

    /**
     * Kotak centang konfirmasi regu susulan (K57): official menyatakan sendiri
     * bahwa atletnya menjadi tambahan anggota regu sekolah yang sudah
     * terdaftar, tanpa biaya regu.
     */
    function kotakKonfirmasi(n, t, lencana) {
        var kunci = t.kunci;
        var kotak = buat('div', 'form-check konfirmasi-susulan');
        var masukan = buat('input', 'form-check-input');
        masukan.type = 'checkbox';
        masukan.id = 'konfirmasi-susulan-' + idAman(kunci);
        masukan.checked = t.dikonfirmasi;
        masukan.setAttribute('data-jalur', 'konfirmasi.' + kunci);
        var label = buat('label', 'form-check-label',
            'Saya mengerti ' + (t.anggota.length === 1 ? 'atlet ini' : t.anggota.length + ' atlet ini') + ' ditambahkan ke ' + namaRegu(n, t.regu)
            + ' sekolah yang sudah terdaftar (' + t.lama + ' atlet), tanpa biaya regu');
        label.htmlFor = masukan.id;
        var umpan = buat('div', 'invalid-feedback');

        masukan.addEventListener('change', function () {
            draf.konfirmasiSusulan = draf.konfirmasiSusulan.filter(function (k) { return k !== kunci; });
            if (masukan.checked) {
                draf.konfirmasiSusulan.push(kunci);
                masukan.classList.remove('is-invalid');
                umpan.textContent = '';
            }
            t.dikonfirmasi = masukan.checked;
            lencanaTim(lencana, t);
            simpanDraf();
        });

        kotak.appendChild(masukan);
        kotak.appendChild(label);
        kotak.appendChild(umpan);
        return kotak;
    }

    function tampilkanDaftarAtlet() {
        var individu = $('#daftar-individu');
        var beregu = $('#daftar-beregu');
        var kuota = masalahKuota();
        individu.textContent = '';
        beregu.textContent = '';

        $('#jumlah-atlet').textContent = draf.atlet.length + ' atlet';
        $('#daftar-kosong').hidden = draf.atlet.length > 0;

        var adaIndividu = false;
        draf.atlet.forEach(function (atlet, i) {
            var nomor = atlet.nomor.map(function (id) { return NOMOR[id]; }).filter(function (n) { return n && n.jenis === 'individu'; });
            var tanpaNomor = atlet.nomor.length === 0 || atlet.nomor.some(function (id) { return !NOMOR[id]; });
            // Satu baris per nomor individu, supaya Hapus hanya menghapus nomor itu.
            nomor.forEach(function (n) {
                adaIndividu = true;
                var baris = barisAtlet(atlet, i, 'atlet.' + i, n.nama_lengkap, [n.id]);
                if (kuota[n.id]) {
                    baris.firstChild.appendChild(buat('p', 'galat-baris', kuota[n.id]));
                }
                individu.appendChild(baris);
            });
            if (nomor.length === 0 && tanpaNomor) {
                adaIndividu = true;
                individu.appendChild(barisAtlet(atlet, i, 'atlet.' + i, ''));
            }
        });
        $('#bagian-individu').hidden = !adaIndividu;

        var tim = keadaanTim();
        var urutan = Object.keys(tim);
        urutan.forEach(function (kunci) {
            var t = tim[kunci];
            var n = t.nomor;
            var kartu = buat('div', 'kartu-tim');
            kartu.setAttribute('data-baris-jalur', 'beregu.' + kunci);
            var kepala = buat('div', 'kartu-tim-kepala');
            var judul = buat('div', 'kartu-tim-judul');
            judul.appendChild(buat('strong', 'kartu-tim-nomor', n.nama_lengkap));
            var tunggal = reguTunggal(n.id);
            if (!tunggal) {
                judul.appendChild(buat('span', 'regu-pil', labelReguLengkap(t.regu)));
            }
            kepala.appendChild(judul);
            var lencana = buat('span');
            lencanaTim(lencana, t);
            kepala.appendChild(lencana);
            kartu.appendChild(kepala);

            var inti = t.anggota.filter(function (a) { return a.peran === 'inti'; });
            var cadangan = t.anggota.filter(function (a) { return a.peran !== 'inti'; });
            var batasCadangan = Math.max(0, n.batas_anggota - n.jumlah_inti);
            var ringkas = buat('div', 'kartu-tim-ringkas');
            [
                t.susulan ? ['', 'tambahan untuk ' + (tunggal ? AWALAN_REGU.toLowerCase() : labelReguLengkap(t.regu)) + ' yang sudah terdaftar (' + t.lama + ' atlet)'] : null,
                ['', t.anggota.length + ' atlet'],
                ['biaya', t.susulan ? 'tanpa biaya regu' : rupiah(n.biaya_beregu)]
            ].filter(Boolean).forEach(function (b) {
                ringkas.appendChild(buat('span', 'nomor-beregu-chip' + (b[0] ? ' chip-' + b[0] : ''), b[1]));
            });
            kartu.appendChild(ringkas);
            t.pesan.forEach(function (p) { kartu.appendChild(buat('p', 'galat-baris', p)); });

            var badan = buat('div', 'kartu-tim-badan');
            badan.appendChild(kelompokPeran('inti', inti, 1, n,
                t.susulan ? inti.length + ' atlet baru' : inti.length + ' dari ' + n.jumlah_inti, !t.susulan && inti.length !== n.jumlah_inti));
            if (cadangan.length > 0 || batasCadangan > 0) {
                badan.appendChild(kelompokPeran('cadangan', cadangan, inti.length + 1, n,
                    t.susulan ? cadangan.length + ' atlet baru' : cadangan.length + ' dari paling banyak ' + batasCadangan, !t.susulan && cadangan.length > batasCadangan));
            }
            kartu.appendChild(badan);
            if (t.susulan) {
                kartu.appendChild(daftarReguLama(n, t));
                kartu.appendChild(kotakKonfirmasi(n, t, lencana));
            }
            beregu.appendChild(kartu);
        });
        $('#bagian-beregu').hidden = urutan.length === 0;
    }

    pemeriksaLangkah[3] = function () {
        var galat = [];

        if (atletDiubah) {
            galat.push({ jalur: 'atlet', pesan: 'Ada atlet yang sedang diubah. Tekan "Simpan perubahan" atau "Batal ubah" dulu.' });
        }
        if (draf.atlet.length === 0) {
            galat.push({ jalur: 'atlet', pesan: 'Belum ada atlet. Tambahkan minimal satu atlet.' });
        }
        draf.atlet.forEach(function (atlet, i) {
            var masalah = masalahAtlet(atlet);
            if (masalah) {
                galat.push({ jalur: 'atlet.' + i, pesan: atlet.nama + ': ' + masalah });
            }
        });
        var tim = keadaanTim();
        Object.keys(tim).forEach(function (kunci) {
            tim[kunci].pesan.forEach(function (p) { galat.push({ jalur: 'beregu.' + kunci, pesan: p }); });
            if (tim[kunci].susulan && !tim[kunci].dikonfirmasi) {
                galat.push({
                    jalur: 'konfirmasi.' + kunci,
                    pesan: namaRegu(tim[kunci].nomor, tim[kunci].regu) + ': sekolah ini sudah punya regu ini, jadi atlet yang dipilih menjadi tambahan anggota tanpa biaya regu. '
                        + 'Centang kotak "Saya mengerti …" pada regu ini, atau pilih regu lain.'
                });
            }
        });
        var kuota = masalahKuota();
        Object.keys(kuota).forEach(function (id) {
            galat.push({ jalur: 'individu.' + id, pesan: kuota[id] });
        });

        return galat;
    };

    penyiapLangkah[3] = function () {
        isiPilihanNomor();
        tampilkanDaftarAtlet();
    };


    // ------------------------------------------------------------------
    // Kiriman ke server (langkah 4)
    // ------------------------------------------------------------------

    var csrfKepala = ($('meta[name="csrf-kepala"]') || {}).content || 'X-CSRF-TOKEN';
    var csrfToken = ($('meta[name="csrf-token"]') || {}).content || '';
    var rincian = null;
    var sudahSelesai = false;

    function kiriman() {
        return {
            sekolah: {
                jenjang: jenjangSekolah(),
                kabupaten: kabupatenSekolah(),
                npsn: npsnSekolah(),
                nama: draf.sekolah.nama,
                nama_usulan: namaUsulan()
            },
            penanggung_jawab: draf.penanggungJawab.map(function (o) {
                return { nama: o.nama, nomor_wa: o.nomor_wa, foto: o.foto };
            }),
            atlet: draf.atlet.map(function (a) {
                var regu = {};
                a.nomor.forEach(function (id) {
                    if (NOMOR[id] && NOMOR[id].jenis === 'beregu' && a.regu && a.regu[id]) {
                        regu[id] = a.regu[id];
                    }
                });
                return { nama: a.nama, nisn: a.nisn, tanggal_lahir: a.tanggal_lahir, jenis_kelamin: a.jenis_kelamin, nomor: a.nomor, regu: regu };
            }),
            konfirmasi_susulan: konfirmasiTerkirim(),
            turun_cadangan: turunTerkirim()
        };
    }

    /**
     * Regu susulan ("nomor:regu") yang sudah dicentang (K57). Hanya regu
     * yang memang susulan menurut data sekolah saat ini.
     */
    function konfirmasiTerkirim() {
        var tim = keadaanTim();
        return Object.keys(tim).filter(function (kunci) { return tim[kunci].dikonfirmasi; });
    }

    /**
     * Inti lama ("nomor:id atlet") yang menjadi cadangan (K105), hanya di
     * regu susulan yang ditambah kiriman ini.
     */
    function turunTerkirim() {
        var tim = keadaanTim();
        var hasil = [];
        Object.keys(tim).forEach(function (kunci) {
            var t = tim[kunci];
            t.lamaAnggota.forEach(function (a) {
                if (a.peran === 'inti' && diturunkan(t.nomor.id, a.id)) {
                    hasil.push(t.nomor.id + ':' + a.id);
                }
            });
        });
        return hasil;
    }

    /**
     * POST JSON dengan token CSRF. Hasil: `{kode, isi}`; `isi` null bila
     * jawaban bukan JSON (misalnya sesi halaman habis).
     */
    function kirim(alamat, tambahan) {
        var kepala = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        kepala[csrfKepala] = csrfToken;

        return ambil(alamat, {
            method: 'POST',
            headers: kepala,
            credentials: 'same-origin',
            body: JSON.stringify(Object.assign(kiriman(), tambahan || {}))
        }).then(function (jawaban) {
            return jawaban.json().then(function (isi) {
                if (isi && isi.csrf) {
                    csrfToken = isi.csrf;
                }
                return { kode: jawaban.status, isi: isi };
            }, function () {
                return { kode: jawaban.status, isi: null };
            });
        }, function () {
            return { kode: 0, isi: null };
        });
    }

    function pesanGagalKirim(hasil) {
        if (hasil.kode === 0) {
            return 'Belum tersambung ke server. Periksa sambungan internet, lalu coba lagi. Isian tetap tersimpan.';
        }
        if (!hasil.isi) {
            return 'Halaman ini sudah terlalu lama terbuka. Muat ulang halaman (isian tetap tersimpan), lalu coba lagi.';
        }
        return hasil.isi.pesan || 'Terjadi kendala. Coba lagi beberapa saat lagi.';
    }

    /**
     * Galat dari server: buka langkah pertama yang salah dan tandai isiannya.
     */
    function tampilkanGalatServer(galat) {
        var langkah = Math.min.apply(null, galat.map(function (g) { return g.langkah; }));
        langkah = Math.max(1, Math.min(langkah, 5));
        var galatLangkah = galat.filter(function (g) { return g.langkah === langkah; });
        tampilkanLangkah(langkah, false);
        tampilkanGalat(langkah, galatLangkah);

        // Server menemukan tim susulan yang belum diketahui wizard (misalnya sekolah
        // yang sama baru saja mendaftar lagi): perbarui data sekolah supaya kotak
        // konfirmasinya tampil.
        var basi = langkah === 3 && npsnSekolah() !== '' && galatLangkah.some(function (g) {
            var bagian = String(g.jalur).split('.');
            var kunci = String(bagian[1] || '').split(':');
            return (bagian[0] === 'konfirmasi' && reguLama(Number(kunci[0]), kunci[1]) === null) || bagian[0] === 'turun';
        });
        if (basi) {
            perbaruiSusulan().then(function (berhasil) {
                if (berhasil && draf.langkah === 3) {
                    tampilkanDaftarAtlet();
                    tampilkanGalat(3, galatLangkah);
                }
            });
        }
    }

    /**
     * Ambil ulang data tim dan kuota sekolah (NPSN yang sudah dicari).
     */
    function perbaruiSusulan() {
        var npsn = draf.sekolah.dicari;
        return ambil(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (jawaban) {
            return jawaban.json();
        }).then(function (isi) {
            if (isi && isi.susulan && draf.sekolah.dicari === npsn) {
                setelSekolah({ susulan: isi.susulan });
                return true;
            }
            return false;
        }).catch(function () {
            return false;
        });
    }

    function periksaServer() {
        return kirim(DATA.alamat.periksa).then(function (hasil) {
            if (hasil.kode === 200 && hasil.isi && hasil.isi.status === 'sah') {
                rincian = hasil.isi.rincian;
                return [];
            }
            if (hasil.kode === 422 && hasil.isi && Array.isArray(hasil.isi.galat)) {
                tampilkanGalatServer(hasil.isi.galat);
                return false;
            }
            return [{ jalur: 'periksa', pesan: pesanGagalKirim(hasil) }];
        });
    }

    var pemeriksaLokal3 = pemeriksaLangkah[3];
    pemeriksaLangkah[3] = function () {
        var galat = pemeriksaLokal3();
        if (galat.length > 0) {
            return galat;
        }
        var tombol = $('#langkah-3 [data-lanjut]');
        tombol.disabled = true;
        return periksaServer().then(function (hasil) {
            tombol.disabled = false;
            return hasil;
        });
    };

    function tabel(judul, kolom, baris, subtotalLabel, subtotal) {
        var wadah = buat('div', 'kartu-isian tabel-biaya');
        wadah.appendChild(buat('h3', 'h6', judul));
        var gulir = buat('div', 'table-responsive');
        var t = buat('table', 'table table-sm align-middle mb-0 tabel-tumpuk');
        var kepala = buat('thead');
        var tr = buat('tr');
        kolom.forEach(function (k, i) {
            var th = buat('th', i === kolom.length - 1 ? 'text-end' : null, k);
            th.scope = 'col';
            tr.appendChild(th);
        });
        kepala.appendChild(tr);
        t.appendChild(kepala);
        var badan = buat('tbody');
        baris.forEach(function (sel) {
            var r = buat('tr');
            sel.forEach(function (isi, i) {
                var td = buat('td', i === sel.length - 1 ? 'text-end text-nowrap' : null, isi);
                td.setAttribute('data-label', kolom[i]);
                r.appendChild(td);
            });
            badan.appendChild(r);
        });
        t.appendChild(badan);
        var kaki = buat('tfoot');
        var rk = buat('tr');
        var label = buat('th', null, subtotalLabel);
        label.colSpan = kolom.length - 1;
        label.scope = 'row';
        rk.appendChild(label);
        rk.appendChild(buat('td', 'text-end text-nowrap fw-bold', rupiah(subtotal)));
        kaki.appendChild(rk);
        t.appendChild(kaki);
        gulir.appendChild(t);
        wadah.appendChild(gulir);
        return wadah;
    }

    function tampilkanRincian() {
        var wadah = $('#rincian-biaya');
        wadah.textContent = '';

        if (!rincian) {
            wadah.appendChild(buat('p', 'text-body-secondary', 'Menghitung biaya…'));
            return;
        }

        var s = rincian.sekolah;
        var sekolah = buat('div', 'kartu-isian ringkasan-sekolah');
        sekolah.appendChild(buat('strong', 'd-block', s.nama));
        if (s.nama_resmi) {
            sekolah.appendChild(buat('span', 'd-block small text-body-secondary', 'Nama usulan, menunggu persetujuan panitia. Nama di data sekolah: ' + s.nama_resmi + '.'));
        }
        sekolah.appendChild(buat('span', 'small text-body-secondary', [s.npsn ? 'NPSN ' + s.npsn : 'Tanpa NPSN', s.jenjang_label, s.kabupaten].filter(Boolean).join(' · ')
            + ' · ' + draf.penanggungJawab.length + ' ofisial · ' + draf.atlet.length + ' atlet'));
        wadah.appendChild(sekolah);

        if (rincian.individu.length > 0) {
            wadah.appendChild(tabel('Individu', ['No', 'Nama', 'NISN', 'Kelompok Umur', 'Nomor Lomba', 'Biaya'],
                rincian.individu.map(function (b, i) { return [String(i + 1), b.nama, b.nisn, b.kelompok_umur, b.nomor, rupiah(b.biaya)]; }),
                'Subtotal individu', rincian.subtotal_individu));
        }

        if (rincian.beregu.length > 0) {
            wadah.appendChild(tabel('Beregu', ['No', 'Nomor Lomba Beregu', 'Biaya'],
                rincian.beregu.map(function (b, i) {
                    return [String(i + 1), (b.regu_tunggal ? b.nomor : b.nomor + ' ' + labelReguLengkap(b.regu)) + ' (' + b.kelompok_umur + ', ' + b.anggota + ' atlet: ' + b.inti + ' inti, ' + b.cadangan + ' cadangan'
                        + (b.susulan ? '; tambahan anggota regu yang sudah terdaftar' : '')
                        + (b.turun ? '; ' + b.turun + ' inti lama menjadi cadangan' : '') + ')', b.susulan ? 'Rp 0' : rupiah(b.biaya)];
                }),
                'Subtotal beregu', rincian.subtotal_beregu));
        }

        var total = buat('div', 'kartu-isian total-biaya');
        var dl = buat('dl', 'mb-0');
        [['Subtotal individu', rincian.subtotal_individu], ['Subtotal beregu', rincian.subtotal_beregu], ['Total pembayaran', rincian.total]].forEach(function (b, i) {
            var baris = buat('div', i === 2 ? 'total-baris total-akhir' : 'total-baris');
            baris.appendChild(buat('dt', null, b[0]));
            baris.appendChild(buat('dd', null, rupiah(b[1])));
            dl.appendChild(baris);
        });
        total.appendChild(dl);
        wadah.appendChild(total);
    }

    penyiapLangkah[4] = function () {
        if (rincian) {
            tampilkanRincian();
            return;
        }
        tampilkanRincian();
        periksaServer().then(function (galat) {
            if (galat === false) {
                return;
            }
            if (galat.length > 0) {
                tampilkanGalat(4, galat);
            }
            tampilkanRincian();
        });
    };

    // Isian yang berubah membuat rincian lama tidak berlaku.
    // Memilih gambar bukti transfer tidak mengubah isian, jadi rincian tetap berlaku.
    function rincianBasi(e) {
        if (e.target.id !== 'bayar-berkas') {
            rincian = null;
        }
    }
    akar.addEventListener('input', rincianBasi);
    akar.addEventListener('change', rincianBasi);

    pemeriksaLangkah[4] = function () {
        return rincian ? [] : periksaServer();
    };

    // ------------------------------------------------------------------
    // Langkah 5: Pembayaran
    // ------------------------------------------------------------------

    function tampilkanBayar() {
        var bayar = DATA.pembayaran;
        $('#bayar-bank').textContent = bayar.bank;
        $('#bayar-rekening').textContent = bayar.nomor_rekening;
        $('#bayar-pemilik').textContent = bayar.pemilik_rekening;
        $('#bayar-total').textContent = rincian ? rupiah(rincian.total) : 'Menghitung…';
        $('#bayar-rincian').textContent = rincian
            ? 'Subtotal individu ' + rupiah(rincian.subtotal_individu) + ' · subtotal beregu ' + rupiah(rincian.subtotal_beregu) + '.'
            : '';
        $('#tombol-kirim').disabled = !rincian;
        var logo = $('#bayar-logo');
        logo.hidden = !bayar.logo_bank;
        if (bayar.logo_bank) {
            logo.src = bayar.logo_bank;
            logo.alt = 'Logo ' + bayar.bank;
        }
    }

    function tampilkanSelesai() {
        var t = draf.tersimpan;
        $('#selesai-sekolah').textContent = t.nama_sekolah;
        $('#selesai-id').textContent = t.id_pendaftaran;
        var wa = $('#bayar-wa');
        wa.hidden = !t.wa;
        if (t.wa) {
            wa.href = t.wa;
        }
        $('#cetak-bukti').href = t.bukti;
        $('#selesai-keterangan').textContent = 'Bukti juga bisa dicetak lagi kapan saja lewat menu Cek Pendaftaran dengan ID ' + t.id_pendaftaran
            + (t.npsn ? ' atau NPSN ' + t.npsn : '') + '.';
        $('#bayar-isi').hidden = true;
        $('#selesai').hidden = false;
        $('#tombol-mulai-ulang').closest('.mulai-ulang').hidden = true;
    }

    penyiapLangkah[5] = function () {
        if (sudahSelesai) {
            tampilkanSelesai();
            return;
        }
        tampilkanBayar();
        if (!rincian) {
            // Draf yang dibuka lagi lain waktu: hitung ulang total dari server.
            periksaServer().then(function (galat) {
                if (galat === false) {
                    return;
                }
                if (galat.length > 0) {
                    tampilkanGalat(5, galat);
                }
                tampilkanBayar();
            });
        }
    };

    function salin(tombol, teks) {
        var label = $('span', tombol);
        var asal = label.textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(teks).then(function () {
                label.textContent = 'Tersalin';
                window.setTimeout(function () { label.textContent = asal; }, 2000);
            }, function () {});
        }
    }

    $('#bayar-salin').addEventListener('click', function () {
        salin(this, DATA.pembayaran.nomor_rekening);
    });

    $('#selesai-salin').addEventListener('click', function () {
        salin(this, draf.tersimpan ? draf.tersimpan.id_pendaftaran : '');
    });

    // Pendaftaran dan bukti transfer dikirim bersama (K90): tanpa bukti tidak ada yang tersimpan.
    $('#tombol-kirim').addEventListener('click', function () {
        var berkas = $('#bayar-berkas');
        var dipilih = berkas.files[0];

        if (!rincian) {
            return;
        }
        if (!dipilih) {
            tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Bukti transfer belum dipilih. Pilih foto atau tangkapan layar bukti transfer.' }]);
            berkas.focus();
            return;
        }
        if (dipilih.size > 10 * 1024 * 1024) {
            tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Gambar terlalu besar. Paling besar 10 MB; perkecil dulu atau pakai tangkapan layar.' }]);
            return;
        }

        yakin('Kirim pendaftaran?', 'Pendaftaran ' + rincian.sekolah.nama + ' dengan ' + draf.atlet.length + ' atlet dan total ' + rupiah(rincian.total)
            + ' dikirim bersama bukti transfer "' + dipilih.name + '". Setelah terkirim, data tidak bisa diubah lewat halaman ini; bila ada yang perlu diubah, hubungi panitia.',
        'Kirim pendaftaran', 'utama').then(function (setuju) {
            if (setuju) {
                kirimPendaftaran(dipilih);
            }
        });
    });

    function kirimPendaftaran(dipilih) {
        var tombol = $('#tombol-kirim');
        var label = $('span', tombol);
        tombol.disabled = true;
        label.textContent = 'Mengirim…';
        var pulih = function () {
            tombol.disabled = false;
            label.textContent = 'Kirim Pendaftaran';
        };

        kecilkanFoto(dipilih, SISI_BUKTI, BATAS_BUKTI).then(function (dataUrl) {
            return kirim(DATA.alamat.simpan, { bukti_bayar: dataUrl });
        }, function () {
            return { kode: -1 };
        }).then(function (hasil) {
            pulih();

            if (hasil.kode === -1) {
                tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Gambar tidak bisa dibaca. Pilih foto atau tangkapan layar bukti transfer lain (JPG, PNG, atau WEBP).' }]);
                return;
            }

            if (hasil.kode === 201 && hasil.isi && hasil.isi.status === 'tersimpan') {
                var r = hasil.isi.rincian;
                draf.tersimpan = {
                    id_pendaftaran: hasil.isi.id_pendaftaran,
                    nama_sekolah: r.sekolah.nama,
                    npsn: r.sekolah.npsn,
                    wa: hasil.isi.wa,
                    bukti: hasil.isi.bukti
                };
                sudahSelesai = true;
                hapusDraf();
                penyiapLangkah[5]();
                perbaruiPil();
                $('#cetak-bukti').focus();
                return;
            }

            if (hasil.kode === 422 && hasil.isi && Array.isArray(hasil.isi.galat)) {
                rincian = null;
                tampilkanGalatServer(hasil.isi.galat);
                return;
            }

            tampilkanGalat(5, [{ jalur: 'kirim', pesan: pesanGagalKirim(hasil) }]);
        });
    }

    // ------------------------------------------------------------------
    // Tombol bersama dan mulai
    // ------------------------------------------------------------------

    $$('[data-lanjut]', akar).forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            lanjut(Number(tombol.closest('.langkah').getAttribute('data-langkah')));
        });
    });

    $$('[data-kembali]', akar).forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            tampilkanLangkah(Number(tombol.closest('.langkah').getAttribute('data-langkah')) - 1);
        });
    });

    $$('.pil', akar).forEach(function (pil) {
        pil.addEventListener('click', function () {
            tampilkanLangkah(Number(pil.getAttribute('data-ke-langkah')));
        });
    });

    $('#tombol-mulai-ulang').addEventListener('click', function () {
        yakin('Mulai dari awal?', 'Semua isian dikosongkan. Data sekolah, ofisial, dan atlet di halaman ini akan hilang.', 'Kosongkan isian').then(function (setuju) {
            if (!setuju) {
                return;
            }
            hapusDraf();
            draf = drafBaru();
            kosongkanFormulirAtlet();
            simpanDraf();
            tampilkanLangkah(1);
        });
    });

    // Antarmuka untuk langkah 4–5 (F4-04).
    window.WizardPendaftaran = {
        data: DATA,
        draf: function () { return draf; },
        simpanDraf: simpanDraf,
        hapusDraf: hapusDraf,
        drafBaru: function () { draf = drafBaru(); return draf; },
        tampilkanLangkah: tampilkanLangkah,
        tampilkanGalat: tampilkanGalat,
        perbaruiPil: perbaruiPil,
        penyiapLangkah: penyiapLangkah,
        pemeriksaLangkah: pemeriksaLangkah,
        rupiah: rupiah,
        buat: buat,
        ikon: ikon,
        jenjangSekolah: jenjangSekolah,
        kabupatenSekolah: kabupatenSekolah,
        npsnSekolah: npsnSekolah
    };

    if (draf.tersimpan) {
        // Draf dari alur lama yang sudah tersimpan di server.
        sudahSelesai = true;
        hapusDraf();
    }

    akar.hidden = false;
    tampilkanSekolah();
    if (perluSegarkanSusulan && npsnSekolah() !== '' && draf.sekolah.dicari === draf.sekolah.npsn) {
        perbaruiSusulan().then(function () {
            if (draf.langkah === 3) {
                tampilkanDaftarAtlet();
            }
        });
    }
    tampilkanLangkah(draf.tersimpan ? 5 : Math.min(draf.langkah || 1, draf.terjauh || 1), false);
}());
