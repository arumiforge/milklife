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
    var KU = {};
    var NOMOR = {};

    DATA.kelompok_umur.forEach(function (ku) { KU[ku.id] = ku; });
    DATA.nomor_lomba.forEach(function (nomor) { NOMOR[nomor.id] = nomor; });

    var draf = bacaDraf();
    var atletDiubah = null;

    // ------------------------------------------------------------------
    // Bantu
    // ------------------------------------------------------------------

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
        return 'Rp' + String(Math.round(Number(angka) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
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
                dicari: '',
                status: '',
                referensi: null,
                susulan: { tim: {}, individu: {} }
            },
            penanggungJawab: [{ nama: '', nomor_wa: '', foto: '' }],
            atlet: [],
            konfirmasiSusulan: [],
            tersimpan: null
        };
    }

    function bacaDraf() {
        try {
            var isi = JSON.parse(window.localStorage.getItem(DATA.kunci_draf) || 'null');
            if (isi && isi.versi === VERSI_DRAF && isi.sekolah && Array.isArray(isi.atlet) && Array.isArray(isi.penanggungJawab)) {
                // Draf lama belum punya konfirmasi tim susulan (K57): kotaknya tampil belum tercentang.
                isi.konfirmasiSusulan = Array.isArray(isi.konfirmasiSusulan)
                    ? isi.konfirmasiSusulan.map(Number).filter(function (id) { return id > 0; })
                    : [];
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

    function tandaiIsian(bagian, jalur, pesan) {
        var sasaran = $('[data-jalur="' + jalur + '"]', bagian);

        if (!sasaran) {
            // Galat atlet dan tim ditulis di baris daftar sementara. Galat konfirmasi
            // tim susulan yang kotaknya belum tampil ditulis di kartu timnya.
            var akarJalur = jalur.split('.').slice(0, 2);
            if (akarJalur[0] === 'konfirmasi') {
                akarJalur[0] = 'beregu';
            }
            var baris = $('[data-baris-jalur="' + akarJalur.join('.') + '"]', bagian);
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
        hasil: $('#sekolah-hasil')
    };

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

        L1.hasil.textContent = '';
        if (!s.tanpaNpsn && ref) {
            var kotak = buat('div', 'alert alert-success sekolah-ditemukan');
            kotak.appendChild(ikon('building-check'));
            var isi = buat('div');
            isi.appendChild(buat('strong', 'd-block', ref.nama));
            isi.appendChild(buat('span', 'small', [ref.jenjang_label, ref.kabupaten].filter(Boolean).join(' · ') + '. Jenjang dan kabupaten mengikuti data sekolah.'));
            kotak.appendChild(isi);
            L1.hasil.appendChild(kotak);

            var tim = Object.keys(s.susulan.tim || {}).length;
            if (tim > 0) {
                L1.hasil.appendChild(buat('div', 'alert alert-info small',
                    'Sekolah ini sudah pernah mendaftar dan sudah punya tim di ' + tim + ' nomor beregu. Atlet yang ditambahkan ke nomor itu menjadi tambahan anggota tim, tanpa biaya tim lagi. '
                    + 'Di langkah 3 (Atlet), centang konfirmasinya pada setiap tim itu.'));
            }
        } else if (!s.tanpaNpsn && s.status === 'tidak_ditemukan') {
            L1.hasil.appendChild(buat('div', 'alert alert-info small', 'NPSN ' + s.dicari + ' tidak ada di data sekolah kami. Tulis nama sekolah secara lengkap di bawah.'));
        }
    }

    function setelSekolah(perubahan) {
        if (Object.prototype.hasOwnProperty.call(perubahan, 'susulan')) {
            // Konfirmasi tim susulan hanya berlaku untuk tim yang jumlah anggota lamanya tidak berubah.
            var timLama = (draf.sekolah.susulan && draf.sekolah.susulan.tim) || {};
            var timBaru = (perubahan.susulan && perubahan.susulan.tim) || {};
            draf.konfirmasiSusulan = draf.konfirmasiSusulan.filter(function (id) {
                return Object.prototype.hasOwnProperty.call(timBaru, id) && Number(timBaru[id]) === Number(timLama[id]);
            });
        }
        Object.keys(perubahan).forEach(function (k) { draf.sekolah[k] = perubahan[k]; });
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

        return fetch(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (jawaban) {
            return jawaban.json();
        }).then(function (isi) {
            L1.cari.disabled = false;

            if (isi.status === 'ditemukan') {
                setelSekolah({ npsn: npsn, dicari: npsn, status: 'ditemukan', referensi: isi.sekolah, susulan: isi.susulan, nama: '' });
                return true;
            }

            if (isi.status === 'tidak_ditemukan') {
                setelSekolah({ npsn: npsn, dicari: npsn, status: 'tidak_ditemukan', referensi: null, susulan: isi.susulan });
                L1.nama.focus();
                return true;
            }

            setelSekolah({ npsn: npsn, dicari: npsn, status: isi.status || 'galat', referensi: null, susulan: { tim: {}, individu: {} } });
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

            $('[data-judul]', kartu).textContent = 'Penanggung jawab ' + (i + 1);

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
                    pratinjau.alt = 'Foto penanggung jawab ' + (i + 1);
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
            hapus.setAttribute('aria-label', 'Hapus penanggung jawab ' + (i + 1));
            hapus.addEventListener('click', function () {
                draf.penanggungJawab.splice(i, 1);
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
    function kecilkanFoto(berkas) {
        return new Promise(function (selesai, gagal) {
            if (!berkas.type || berkas.type.indexOf('image/') !== 0) {
                gagal(new Error('bukan gambar'));
                return;
            }
            var alamat = URL.createObjectURL(berkas);
            var gambar = new Image();
            gambar.onload = function () {
                URL.revokeObjectURL(alamat);
                var skala = Math.min(1, SISI_FOTO / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                var kanvas = document.createElement('canvas');
                kanvas.width = Math.max(1, Math.round(gambar.naturalWidth * skala));
                kanvas.height = Math.max(1, Math.round(gambar.naturalHeight * skala));
                var konteks = kanvas.getContext('2d');
                konteks.fillStyle = '#fff';
                konteks.fillRect(0, 0, kanvas.width, kanvas.height);
                konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

                var kualitas = 0.82;
                var hasil = kanvas.toDataURL('image/jpeg', kualitas);
                while (hasil.length * 0.75 > BATAS_FOTO && kualitas > 0.4) {
                    kualitas -= 0.12;
                    hasil = kanvas.toDataURL('image/jpeg', kualitas);
                }
                if (hasil.indexOf('data:image/jpeg;base64,') !== 0 || hasil.length * 0.75 > BATAS_FOTO) {
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
            galat.push({ jalur: 'penanggung_jawab', pesan: 'Isi minimal ' + minimalPj + ' penanggung jawab.' });
        }

        draf.penanggungJawab.forEach(function (orang, i) {
            var ke = 'Penanggung jawab ' + (i + 1) + ': ';
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
     * Kelompok umur dari tahun lahir dan jenjang (sama dengan `KelompokUmurAtlet`).
     */
    function tentukanKu(tahun, jenjang) {
        var cocok = null;
        DATA.kelompok_umur.some(function (ku) {
            var awal = ku.tahun_lahir_awal;
            var akhir = ku.tahun_lahir_akhir;
            if (awal === null && akhir === null) {
                return false;
            }
            if ((awal === null || tahun >= awal) && (akhir === null || tahun <= akhir)) {
                cocok = ku;
                return true;
            }
            return false;
        });

        if (!cocok) {
            return {
                ku: null,
                galat: 'Atlet lahir tahun ' + tahun + ' tidak masuk kelompok umur mana pun. Kelompok umur yang dibuka: '
                    + DATA.kelompok_umur.map(namaKu).join(', ') + '. Periksa lagi tanggal lahirnya.'
            };
        }

        if (cocok.jenjang.indexOf(jenjang) === -1) {
            var boleh = cocok.jenjang.map(function (k) { return DATA.jenjang[k]; }).join(', ');
            return {
                ku: null,
                galat: 'Atlet lahir tahun ' + tahun + ' masuk ' + cocok.nama + ', yang ' + (boleh ? 'hanya untuk ' + boleh : 'belum dibuka untuk jenjang mana pun')
                    + '. Sekolah ini ' + (DATA.jenjang[jenjang] || '-') + ', jadi atlet ini tidak bisa didaftarkan.'
            };
        }

        return { ku: cocok, galat: null };
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

    function isiPilihanNomor() {
        var lahir = L3.lahir.value;
        var jk = jkFormulir();
        var dipilih = nomorDipilih();

        if (atletDiubah && dipilih.length === 0 && L3.formulir.getAttribute('data-muat') === '1') {
            dipilih = atletDiubah.nomor.slice();
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
            var label = buat('label', 'form-check-label', nomor.nama_lengkap);
            label.htmlFor = id;
            if (nomor.jenis === 'beregu') {
                label.appendChild(buat('span', 'keterangan-nomor', ' · tim ' + nomor.jumlah_inti
                    + (nomor.batas_anggota > nomor.jumlah_inti ? '–' + nomor.batas_anggota : '') + ' atlet'
                    + (nomor.kategori === 'mix' ? ', minimal ' + nomor.jumlah_putra + ' putra dan ' + nomor.jumlah_putri + ' putri' : '')));
            }
            kotak.appendChild(centang);
            kotak.appendChild(label);
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

    L3.nama.addEventListener('input', function () {
        var posisi = L3.nama.selectionStart;
        L3.nama.value = L3.nama.value.toUpperCase();
        L3.nama.setSelectionRange(posisi, posisi);
    });
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

        var data = { nama: nama, nisn: nisn, tanggal_lahir: lahir, jenis_kelamin: jk, nomor: nomor };

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
     * Keadaan tiap tim beregu (sama dengan `KelengkapanTim`).
     */
    function keadaanTim() {
        var tim = {};
        var lama = susulan().tim || {};

        draf.atlet.forEach(function (atlet, indeks) {
            atlet.nomor.forEach(function (id) {
                var nomor = NOMOR[id];
                if (nomor && nomor.jenis === 'beregu') {
                    tim[id] = tim[id] || { nomor: nomor, anggota: [] };
                    tim[id].anggota.push({ atlet: atlet, indeks: indeks });
                }
            });
        });

        Object.keys(tim).forEach(function (id) {
            var t = tim[id];
            var n = t.nomor;
            var jumlah = t.anggota.length;
            var namaTim = 'Tim ' + n.nama_lengkap;
            t.susulan = Object.prototype.hasOwnProperty.call(lama, id);
            t.lama = t.susulan ? Number(lama[id]) : 0;
            t.dikonfirmasi = t.susulan && draf.konfirmasiSusulan.indexOf(Number(id)) !== -1;
            t.pesan = [];

            if (t.susulan) {
                if (t.lama + jumlah > n.batas_anggota) {
                    var sisa = Math.max(0, n.batas_anggota - t.lama);
                    t.pesan.push(namaTim + ' sudah punya ' + t.lama + ' atlet dari pendaftaran sebelumnya, dan paling banyak ' + n.batas_anggota + ' atlet. '
                        + (sisa === 0 ? 'Tim ini sudah penuh; hapus nomor ini dari atlet tambahan.' : 'Atlet tambahan paling banyak ' + sisa + '.'));
                }
            } else {
                if (jumlah < n.jumlah_inti) {
                    t.pesan.push(namaTim + ' baru punya ' + jumlah + ' atlet. Tambah ' + (n.jumlah_inti - jumlah) + ' atlet lagi; tim ini minimal ' + n.jumlah_inti + ' atlet.');
                } else if (jumlah > n.batas_anggota) {
                    t.pesan.push(namaTim + ' punya ' + jumlah + ' atlet, padahal paling banyak ' + n.batas_anggota + '. Hapus ' + (jumlah - n.batas_anggota) + ' atlet dari nomor ini.');
                }
                if (n.kategori === 'mix') {
                    var putra = t.anggota.filter(function (a) { return a.atlet.jenis_kelamin === 'L'; }).length;
                    var putri = jumlah - putra;
                    if (putra < n.jumlah_putra || putri < n.jumlah_putri) {
                        t.pesan.push(namaTim + ' minimal ' + n.jumlah_putra + ' putra dan ' + n.jumlah_putri + ' putri; sekarang ' + putra + ' putra dan ' + putri + ' putri.');
                    }
                }
            }
        });

        return tim;
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

    function barisAtlet(atlet, indeks, jalur, rincian) {
        var li = buat('li', 'baris-atlet');
        li.setAttribute('data-baris-jalur', jalur);
        var isi = buat('div', 'baris-atlet-isi');
        isi.appendChild(buat('strong', 'd-block', atlet.nama));
        var ku = tentukanKu(Number(String(atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah()).ku;
        isi.appendChild(buat('span', 'baris-atlet-keterangan', ['NISN ' + atlet.nisn, ku ? ku.nama : null, atlet.jenis_kelamin === 'L' ? 'Putra' : 'Putri'].filter(Boolean).join(' · ')));
        if (rincian) {
            isi.appendChild(buat('span', 'baris-atlet-nomor', rincian));
        }
        var masalah = masalahAtlet(atlet);
        if (masalah) {
            isi.appendChild(buat('p', 'galat-baris', masalah));
        }
        li.appendChild(isi);

        var tombol = buat('div', 'baris-atlet-tombol');
        var ubah = buat('button', 'btn btn-sm btn-outline-primary');
        ubah.type = 'button';
        ubah.appendChild(ikon('pencil'));
        ubah.appendChild(document.createTextNode(' Ubah'));
        ubah.setAttribute('aria-label', 'Ubah ' + atlet.nama);
        ubah.addEventListener('click', function () { muatAtlet(atlet); });
        var hapus = buat('button', 'btn btn-sm btn-outline-danger');
        hapus.type = 'button';
        hapus.appendChild(ikon('trash'));
        hapus.appendChild(document.createTextNode(' Hapus'));
        hapus.setAttribute('aria-label', 'Hapus ' + atlet.nama);
        hapus.addEventListener('click', function () {
            if (window.confirm('Hapus ' + atlet.nama + ' dari daftar? Semua nomor lombanya ikut terhapus.')) {
                draf.atlet.splice(draf.atlet.indexOf(atlet), 1);
                if (atletDiubah === atlet) {
                    kosongkanFormulirAtlet();
                }
                simpanDraf();
                tampilkanDaftarAtlet();
            }
        });
        tombol.appendChild(ubah);
        tombol.appendChild(hapus);
        li.appendChild(tombol);
        return li;
    }

    /**
     * Teks lencana kartu tim beregu.
     */
    function lencanaTim(lencana, t) {
        var perluKonfirmasi = t.susulan && !t.dikonfirmasi;
        lencana.className = 'badge ' + (t.pesan.length || perluKonfirmasi ? 'text-bg-warning' : 'text-bg-success');
        lencana.textContent = t.pesan.length ? 'Belum lengkap' : (perluKonfirmasi ? 'Perlu dicentang' : (t.susulan ? 'Tambahan anggota' : 'Lengkap'));
    }

    /**
     * Kotak centang konfirmasi tim susulan (K57): official menyatakan sendiri
     * bahwa atletnya menjadi tambahan anggota tim sekolah yang sudah
     * terdaftar, tanpa biaya tim.
     */
    function kotakKonfirmasi(n, t, lencana) {
        var nomorId = Number(n.id);
        var kotak = buat('div', 'form-check konfirmasi-susulan');
        var masukan = buat('input', 'form-check-input');
        masukan.type = 'checkbox';
        masukan.id = 'konfirmasi-susulan-' + nomorId;
        masukan.checked = t.dikonfirmasi;
        masukan.setAttribute('data-jalur', 'konfirmasi.' + nomorId);
        var label = buat('label', 'form-check-label',
            'Saya mengerti ' + (t.anggota.length === 1 ? 'atlet ini' : t.anggota.length + ' atlet ini') + ' ditambahkan ke tim ' + n.nama_lengkap
            + ' sekolah yang sudah terdaftar (' + t.lama + ' atlet), tanpa biaya tim');
        label.htmlFor = masukan.id;
        var umpan = buat('div', 'invalid-feedback');

        masukan.addEventListener('change', function () {
            draf.konfirmasiSusulan = draf.konfirmasiSusulan.filter(function (id) { return id !== nomorId; });
            if (masukan.checked) {
                draf.konfirmasiSusulan.push(nomorId);
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
            if (nomor.length > 0 || tanpaNomor) {
                adaIndividu = true;
                var baris = barisAtlet(atlet, i, 'atlet.' + i, nomor.map(function (n) { return n.nama_lengkap; }).join(', '));
                nomor.forEach(function (n) {
                    if (kuota[n.id]) {
                        baris.firstChild.appendChild(buat('p', 'galat-baris', kuota[n.id]));
                    }
                });
                individu.appendChild(baris);
            }
        });
        $('#bagian-individu').hidden = !adaIndividu;

        var tim = keadaanTim();
        var urutan = DATA.nomor_lomba.filter(function (n) { return tim[n.id]; });
        urutan.forEach(function (n) {
            var t = tim[n.id];
            var kartu = buat('div', 'kartu-tim');
            kartu.setAttribute('data-baris-jalur', 'beregu.' + n.id);
            var kepala = buat('div', 'kartu-tim-kepala');
            kepala.appendChild(buat('strong', null, n.nama_lengkap));
            var lencana = buat('span');
            lencanaTim(lencana, t);
            kepala.appendChild(lencana);
            kartu.appendChild(kepala);
            kartu.appendChild(buat('p', 'kartu-tim-keterangan', t.anggota.length + ' atlet'
                + (t.susulan ? ' tambahan untuk tim sekolah yang sudah ada (' + t.lama + ' atlet), tanpa biaya tim' : ' · minimal ' + n.jumlah_inti + ', paling banyak ' + n.batas_anggota)));
            t.pesan.forEach(function (p) { kartu.appendChild(buat('p', 'galat-baris', p)); });
            var ul = buat('ul', 'daftar-atlet');
            t.anggota.forEach(function (a) {
                ul.appendChild(barisAtlet(a.atlet, a.indeks, 'atlet.' + a.indeks));
            });
            kartu.appendChild(ul);
            if (t.susulan) {
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
        Object.keys(tim).forEach(function (id) {
            tim[id].pesan.forEach(function (p) { galat.push({ jalur: 'beregu.' + id, pesan: p }); });
            if (tim[id].susulan && !tim[id].dikonfirmasi) {
                galat.push({
                    jalur: 'konfirmasi.' + id,
                    pesan: 'Tim ' + tim[id].nomor.nama_lengkap + ': sekolah ini sudah punya tim di nomor ini, jadi atlet yang dipilih menjadi tambahan anggota tanpa biaya tim. '
                        + 'Centang kotak "Saya mengerti …" pada tim ini.'
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
                nama: draf.sekolah.nama
            },
            penanggung_jawab: draf.penanggungJawab.map(function (o) {
                return { nama: o.nama, nomor_wa: o.nomor_wa, foto: o.foto };
            }),
            atlet: draf.atlet.map(function (a) {
                return { nama: a.nama, nisn: a.nisn, tanggal_lahir: a.tanggal_lahir, jenis_kelamin: a.jenis_kelamin, nomor: a.nomor };
            }),
            konfirmasi_susulan: konfirmasiTerkirim()
        };
    }

    /**
     * ID nomor beregu yang tim susulannya sudah dicentang (K57). Hanya tim
     * yang memang susulan menurut data sekolah saat ini.
     */
    function konfirmasiTerkirim() {
        var tim = keadaanTim();
        return Object.keys(tim).filter(function (id) { return tim[id].dikonfirmasi; }).map(Number);
    }

    /**
     * POST JSON dengan token CSRF. Hasil: `{kode, isi}`; `isi` null bila
     * jawaban bukan JSON (misalnya sesi halaman habis).
     */
    function kirim(alamat) {
        var kepala = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        kepala[csrfKepala] = csrfToken;

        return fetch(alamat, {
            method: 'POST',
            headers: kepala,
            credentials: 'same-origin',
            body: JSON.stringify(kiriman())
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
        langkah = Math.max(1, Math.min(langkah, 4));
        var galatLangkah = galat.filter(function (g) { return g.langkah === langkah; });
        tampilkanLangkah(langkah, false);
        tampilkanGalat(langkah, galatLangkah);

        // Server menemukan tim susulan yang belum diketahui wizard (misalnya sekolah
        // yang sama baru saja mendaftar lagi): perbarui data sekolah supaya kotak
        // konfirmasinya tampil.
        var timLama = susulan().tim || {};
        var basi = langkah === 3 && npsnSekolah() !== '' && galatLangkah.some(function (g) {
            var bagian = String(g.jalur).split('.');
            return bagian[0] === 'konfirmasi' && !Object.prototype.hasOwnProperty.call(timLama, bagian[1]);
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
        return fetch(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
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
        var simpan = $('#tombol-simpan');
        wadah.textContent = '';
        simpan.disabled = !rincian;

        if (!rincian) {
            wadah.appendChild(buat('p', 'text-body-secondary', 'Menghitung biaya…'));
            return;
        }

        var s = rincian.sekolah;
        var sekolah = buat('div', 'kartu-isian ringkasan-sekolah');
        sekolah.appendChild(buat('strong', 'd-block', s.nama));
        sekolah.appendChild(buat('span', 'small text-body-secondary', [s.npsn ? 'NPSN ' + s.npsn : 'Tanpa NPSN', s.jenjang_label, s.kabupaten].filter(Boolean).join(' · ')
            + ' · ' + draf.penanggungJawab.length + ' penanggung jawab · ' + draf.atlet.length + ' atlet'));
        wadah.appendChild(sekolah);

        if (rincian.individu.length > 0) {
            wadah.appendChild(tabel('Individu', ['No', 'Nama', 'NISN', 'Kelompok Umur', 'Nomor Lomba', 'Biaya'],
                rincian.individu.map(function (b, i) { return [String(i + 1), b.nama, b.nisn, b.kelompok_umur, b.nomor, rupiah(b.biaya)]; }),
                'Subtotal individu', rincian.subtotal_individu));
        }

        if (rincian.beregu.length > 0) {
            wadah.appendChild(tabel('Beregu', ['No', 'Nomor Lomba Beregu', 'Biaya'],
                rincian.beregu.map(function (b, i) {
                    return [String(i + 1), b.nomor + ' (' + b.kelompok_umur + ', ' + b.anggota + ' atlet' + (b.susulan ? ', tambahan anggota tim yang sudah terdaftar' : '') + ')', b.susulan ? 'Rp0' : rupiah(b.biaya)];
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
    akar.addEventListener('input', function () { rincian = null; });
    akar.addEventListener('change', function () { rincian = null; });

    var jendela = $('#konfirmasi-simpan');

    $('#tombol-simpan').addEventListener('click', function () {
        if (!rincian) {
            return;
        }
        $('#konfirmasi-sekolah').textContent = rincian.sekolah.nama;
        $('#konfirmasi-atlet').textContent = draf.atlet.length + ' atlet';
        $('#konfirmasi-total').textContent = rupiah(rincian.total);
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(jendela).show();
        } else if (window.confirm('Simpan pendaftaran? Setelah disimpan, data tidak bisa diubah lewat halaman ini.')) {
            simpanPendaftaran();
        }
    });

    $('#konfirmasi-ya').addEventListener('click', function () {
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(jendela).hide();
        }
        simpanPendaftaran();
    });

    function simpanPendaftaran() {
        var tombol = $('#tombol-simpan');
        tombol.disabled = true;
        tombol.lastChild.textContent = ' Menyimpan…';

        kirim(DATA.alamat.simpan).then(function (hasil) {
            tombol.lastChild.textContent = ' Simpan Pendaftaran';
            tombol.disabled = false;

            if (hasil.kode === 201 && hasil.isi && hasil.isi.status === 'tersimpan') {
                var r = hasil.isi.rincian;
                draf.tersimpan = {
                    id_pendaftaran: hasil.isi.id_pendaftaran,
                    nama_sekolah: r.sekolah.nama,
                    npsn: r.sekolah.npsn,
                    subtotal_individu: r.subtotal_individu,
                    subtotal_beregu: r.subtotal_beregu,
                    total: r.total,
                    wa: hasil.isi.wa,
                    bukti: hasil.isi.bukti
                };
                // Foto tidak dibutuhkan lagi di peramban setelah tersimpan.
                draf.penanggungJawab.forEach(function (o) { o.foto = ''; });
                rincian = null;
                simpanDraf();
                tampilkanLangkah(5);
                return;
            }

            if (hasil.kode === 422 && hasil.isi && Array.isArray(hasil.isi.galat)) {
                rincian = null;
                tampilkanGalatServer(hasil.isi.galat);
                return;
            }

            tampilkanGalat(4, [{ jalur: 'simpan', pesan: pesanGagalKirim(hasil) }]);
        });
    }

    // ------------------------------------------------------------------
    // Langkah 5: Pembayaran
    // ------------------------------------------------------------------

    penyiapLangkah[5] = function () {
        var t = draf.tersimpan;
        if (!t) {
            tampilkanLangkah(4, false);
            return;
        }
        var bayar = DATA.pembayaran;
        $('#bayar-sekolah').textContent = t.nama_sekolah;
        $('#bayar-id').textContent = t.id_pendaftaran;
        $('#bayar-bank').textContent = bayar.bank;
        $('#bayar-rekening').textContent = bayar.nomor_rekening;
        $('#bayar-pemilik').textContent = bayar.pemilik_rekening;
        $('#bayar-total').textContent = rupiah(t.total);
        $('#bayar-rincian').textContent = 'Subtotal individu ' + rupiah(t.subtotal_individu) + ' · subtotal beregu ' + rupiah(t.subtotal_beregu) + '.';
        var logo = $('#bayar-logo');
        logo.hidden = !bayar.logo_bank;
        if (bayar.logo_bank) {
            logo.src = bayar.logo_bank;
            logo.alt = 'Logo ' + bayar.bank;
        }
        var wa = $('#bayar-wa');
        wa.hidden = !t.wa;
        if (t.wa) {
            wa.href = t.wa;
        }
        $('#cetak-bukti').href = t.bukti;
        $('#selesai-keterangan').textContent = 'Bukti juga bisa dicetak lagi kapan saja lewat menu Cek Pendaftaran dengan ID ' + t.id_pendaftaran
            + (t.npsn ? ' atau NPSN ' + t.npsn : '') + '.';
        $('#bayar-isi').hidden = sudahSelesai;
        $('#selesai').hidden = !sudahSelesai;
        $('#tombol-mulai-ulang').closest('.mulai-ulang').hidden = true;
    };

    $('#bayar-salin').addEventListener('click', function () {
        var teks = DATA.pembayaran.nomor_rekening;
        var label = $('#bayar-salin span');
        var tandai = function () {
            label.textContent = 'Tersalin';
            window.setTimeout(function () { label.textContent = 'Salin'; }, 2000);
        };
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(teks).then(tandai, function () {});
        }
    });

    $('#bayar-selesai').addEventListener('click', function () {
        sudahSelesai = true;
        hapusDraf();
        penyiapLangkah[5]();
        perbaruiPil();
        $('#cetak-bukti').focus();
    });

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
        if (window.confirm('Kosongkan semua isian dan mulai dari awal? Data sekolah, penanggung jawab, dan atlet di halaman ini akan hilang.')) {
            hapusDraf();
            draf = drafBaru();
            kosongkanFormulirAtlet();
            simpanDraf();
            tampilkanLangkah(1);
        }
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

    akar.hidden = false;
    tampilkanSekolah();
    tampilkanLangkah(draf.tersimpan ? 5 : Math.min(draf.langkah || 1, draf.terjauh || 1), false);
}());
