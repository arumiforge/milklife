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
        usulanKotak: $('#sekolah-usulan-kotak'),
        usulanBatal: $('#sekolah-usulan-batal')
    };

    /**
     * Pemilih kabupaten berkotak cari untuk ponsel (K162). `<select>` tetap
     * menjadi sumber nilai (draf, galat, kunci); pemilih ini hanya tampilan
     * di layar di bawah sm dan mengikuti nilai, kunci, dan tanda galatnya.
     */
    var kabupatenCari = (function () {
        var wadah = $('[data-kabupaten-cari]');
        if (!wadah) {
            return { perbarui: function () {} };
        }
        var pilih = L1.kabupaten;
        var tombol = $('#sekolah-kabupaten-tombol');
        var teks = $('[data-kabupaten-teks]', wadah);
        var panel = $('#sekolah-kabupaten-panel');
        var saring = $('#sekolah-kabupaten-saring');
        var daftar = $('#sekolah-kabupaten-daftar');
        var kosong = $('[data-kabupaten-kosong]', wadah);

        // Dengan JS: pemilih ini tampil di ponsel, `<select>` di layar sm ke atas.
        wadah.classList.replace('hidden', 'sm:hidden');
        pilih.classList.replace('block', 'hidden');
        pilih.classList.add('sm:block');

        function tutup() {
            panel.hidden = true;
            tombol.setAttribute('aria-expanded', 'false');
        }

        function pilihNilai(nilai) {
            pilih.value = nilai;
            pilih.dispatchEvent(new Event('change', { bubbles: true }));
            tutup();
            tombol.focus();
        }

        function isiDaftar() {
            var kata = saring.value.trim().toLowerCase();
            var jumlah = 0;
            daftar.textContent = '';
            Array.prototype.forEach.call(pilih.options, function (opsi) {
                if (opsi.value === '' || (kata !== '' && opsi.text.toLowerCase().indexOf(kata) === -1)) {
                    return;
                }
                jumlah++;
                var terpilih = opsi.value === pilih.value;
                var butir = buat('li');
                var b = buat('button', 'flex min-h-11 w-full items-center justify-between gap-2 rounded-lg px-3 text-start text-gray-900 hover:bg-primary-50 focus:bg-primary-50 focus:outline-none' + (terpilih ? ' font-semibold text-primary-800' : ''), opsi.text);
                b.type = 'button';
                b.setAttribute('data-nilai', opsi.value);
                if (terpilih) {
                    b.setAttribute('aria-current', 'true');
                    b.appendChild(ikon('check', 'size-5 shrink-0 text-primary-700'));
                }
                b.addEventListener('click', function () { pilihNilai(opsi.value); });
                butir.appendChild(b);
                daftar.appendChild(butir);
            });
            kosong.hidden = jumlah > 0;
        }

        function buka() {
            panel.hidden = false;
            tombol.setAttribute('aria-expanded', 'true');
            saring.value = '';
            isiDaftar();
            saring.focus();
        }

        function perbarui() {
            var opsi = pilih.options[pilih.selectedIndex];
            var ada = pilih.value !== '' && !!opsi;
            teks.textContent = ada ? opsi.text : 'Pilih kabupaten';
            teks.classList.toggle('text-gray-500', !ada);
            tombol.disabled = pilih.disabled;
            tombol.classList.toggle('is-invalid', pilih.classList.contains('is-invalid'));
            if (pilih.disabled) {
                tutup();
            }
        }

        tombol.addEventListener('click', function () {
            if (panel.hidden) {
                buka();
            } else {
                tutup();
            }
        });
        saring.addEventListener('input', isiDaftar);
        saring.addEventListener('keydown', function (e) {
            // Enter memilih hasil pertama; panah bawah pindah ke daftar.
            var pertama = $('button', daftar);
            if (e.key === 'Enter' && pertama) {
                e.preventDefault();
                pilihNilai(pertama.getAttribute('data-nilai'));
            } else if (e.key === 'ArrowDown' && pertama) {
                e.preventDefault();
                pertama.focus();
            }
        });
        daftar.addEventListener('keydown', function (e) {
            var semua = $$('button', daftar);
            var i = semua.indexOf(document.activeElement);
            if (e.key === 'ArrowDown' && i < semua.length - 1) {
                e.preventDefault();
                semua[i + 1].focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                (i > 0 ? semua[i - 1] : saring).focus();
            }
        });
        wadah.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !panel.hidden) {
                tutup();
                tombol.focus();
            }
        });
        document.addEventListener('click', function (e) {
            if (!panel.hidden && !wadah.contains(e.target)) {
                tutup();
            }
        });
        pilih.addEventListener('change', perbarui);
        new MutationObserver(perbarui).observe(pilih, { attributes: true, attributeFilter: ['class', 'disabled'] });
        perbarui();

        return { perbarui: perbarui };
    })();


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
        // Daftar pilihan jenjang di ponsel (K161) mengikuti tombol pilihan.
        var pilihJenjang = $('#sekolah-jenjang-pilih');
        if (pilihJenjang) {
            pilihJenjang.value = jenjangSekolah() || '';
            pilihJenjang.disabled = !!(ref && ref.jenjang);
        }
        L1.kabupaten.value = kabupatenSekolah() || '';
        L1.kabupaten.disabled = !!(ref && ref.kabupaten);
        kabupatenCari.perbarui();
        // Keterangan gembok di bawah jenjang dan kabupaten yang terkunci (K163).
        var kunci = $$('[data-kunci-sekolah]');
        if (kunci[0]) {
            kunci[0].hidden = !(ref && ref.jenjang);
        }
        if (kunci[1]) {
            kunci[1].hidden = !(ref && ref.kabupaten);
        }
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
        // Usulan yang dibuka sendiri boleh dibatalkan; untuk nama kembar usulan dianjurkan (K99).
        L1.usulanBatal.hidden = !!kembar;

        L1.hasil.textContent = '';
        if (!s.tanpaNpsn && ref) {
            var kotak = buat('div', 'sekolah-ditemukan mb-3 flex items-start gap-3 rounded-xl border border-green-200 bg-green-50 p-4 text-green-900');
            kotak.appendChild(ikon('school', 'size-6 shrink-0'));
            var isi = buat('div');
            isi.appendChild(buat('strong', 'block', usulan || ref.nama));
            if (usulan) {
                isi.appendChild(buat('span', 'block text-sm', 'Nama usulan Anda, menunggu persetujuan panitia. Nama di data sekolah: ' + ref.nama + '.'));
            }
            // Letak sekolah membedakan sekolah yang namanya sama (K97).
            if (ref.lokasi) {
                isi.appendChild(buat('span', 'block text-sm', ref.lokasi));
            }
            isi.appendChild(buat('span', 'block text-sm', [ref.jenjang_label, ref.kabupaten].filter(Boolean).join(' · ') + '. Jenjang dan kabupaten mengikuti data sekolah.'));
            // Tawaran usulan nama di dalam kotak sekolah (K99, dirapikan K162).
            if (!kembar && L1.usulanKotak.hidden) {
                var tawaran = buat('div', 'mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 border-t border-green-200 pt-3 text-sm');
                tawaran.appendChild(buat('span', null, 'Nama sekolah kurang lengkap?'));
                var tautan = buat('button', W.KELAS.tautan + ' text-green-900');
                tautan.type = 'button';
                tautan.appendChild(ikon('pencil', 'size-4'));
                tautan.appendChild(document.createTextNode('Usulkan nama lengkap'));
                tautan.addEventListener('click', function () {
                    setelSekolah({ usulanTerbuka: true });
                    L1.usulan.focus();
                });
                tawaran.appendChild(tautan);
                isi.appendChild(tawaran);
            }
            kotak.appendChild(isi);
            L1.hasil.appendChild(kotak);

            // Nama umum yang dipakai sekolah lain juga (K99): tawarkan usulan nama lengkap.
            if (kembar && !usulan) {
                L1.hasil.appendChild(buat('div', W.KELAS.kotakPeringatan,
                    'Nama ' + ref.nama + ' juga dipakai ' + ref.nama_kembar + ' sekolah lain di data sekolah. Supaya tidak tertukar di rekap dan bukti pendaftaran, '
                    + 'usulkan nama lengkap sekolah Anda di bawah, misalnya dengan menambahkan nama desa.'));
            }

            var timSekolah = s.susulan.tim || {};
            var jumlahRegu = 0;
            Object.keys(timSekolah).forEach(function (id) { jumlahRegu += Object.keys(timSekolah[id] || {}).length; });
            if (jumlahRegu > 0) {
                L1.hasil.appendChild(buat('div', W.KELAS.kotakInfo,
                    'Sekolah ini sudah pernah mendaftar dan punya ' + jumlahRegu + ' regu di ' + Object.keys(timSekolah).length + ' nomor beregu. '
                    + 'Di langkah 3 (Atlet), Anda bisa memasukkan atlet ke regu yang sudah ada tanpa biaya regu lagi (centang konfirmasinya). '
                    + 'Anda juga bisa membuat regu baru dengan biaya regu.'));
            }
        } else if (!s.tanpaNpsn && s.status === 'tidak_ditemukan') {
            L1.hasil.appendChild(buat('div', W.KELAS.kotakInfo, 'NPSN ' + s.dicari + ' belum ada di data sekolah. Tulis nama sekolah secara lengkap di bawah.'));
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

    /**
     * Kerangka (skeleton) kartu sekolah selama NPSN dicari (F9-17), bentuknya
     * sama dengan kartu hasil supaya halaman tidak melompat saat data tiba.
     */
    function kerangkaSekolah() {
        var kotak = buat('div', 'mb-3 flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4');
        kotak.setAttribute('role', 'status');
        kotak.setAttribute('data-kerangka', '');
        kotak.appendChild(buat('span', 'sr-only', 'Mencari sekolah…'));
        var ikonKerangka = buat('div', 'kerangka size-6 shrink-0');
        var isi = buat('div', 'min-w-0 flex-1 space-y-2');
        ['h-4 w-2/3', 'h-3 w-11/12', 'h-3 w-1/2'].forEach(function (k) { isi.appendChild(buat('div', 'kerangka ' + k)); });
        ikonKerangka.setAttribute('aria-hidden', 'true');
        isi.setAttribute('aria-hidden', 'true');
        kotak.appendChild(ikonKerangka);
        kotak.appendChild(isi);
        return kotak;
    }

    var nomorCari = 0;

    function cariSekolah() {
        var npsn = L1.npsn.value.replace(/\s+/g, '');
        L1.npsn.value = npsn;
        tampilkanGalat(1, []);

        if (!/^\d{8}$/.test(npsn)) {
            tampilkanGalat(1, [{ jalur: 'sekolah.npsn', pesan: 'NPSN harus 8 angka. Periksa lagi NPSN sekolah, atau centang "Sekolah belum punya NPSN".' }]);
            return Promise.resolve(false);
        }

        var nomorIni = ++nomorCari;
        L1.cari.disabled = true;
        L1.cari.setAttribute('aria-busy', 'true');
        L1.hasil.textContent = '';
        L1.hasil.appendChild(kerangkaSekolah());

        return ambil(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (jawaban) {
            return jawaban.json();
        }).then(function (isi) {
            L1.cari.disabled = false;
            L1.cari.removeAttribute('aria-busy');

            // NPSN sudah diganti selagi menunggu jawaban: jawaban lama diabaikan.
            if (L1.npsn.value.replace(/\s+/g, '') !== npsn) {
                // Kerangka dibuang bila tidak ada pencarian lebih baru yang sedang berjalan.
                if (nomorIni === nomorCari && L1.hasil.querySelector('[data-kerangka]')) {
                    tampilkanSekolah();
                }
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
            L1.cari.removeAttribute('aria-busy');
            L1.hasil.textContent = '';
            tampilkanGalat(1, [{ jalur: 'sekolah.npsn', pesan: 'Sekolah belum bisa dicari. Periksa sambungan internet, lalu tekan Cari lagi.' }]);
            return false;
        });
    }

    $$('input[name="sekolah-jenjang"]').forEach(function (r) {
        r.addEventListener('change', function () { setelSekolah({ jenjang: r.value }); });
    });
    if ($('#sekolah-jenjang-pilih')) {
        $('#sekolah-jenjang-pilih').addEventListener('change', function () { setelSekolah({ jenjang: this.value }); });
    }
    L1.kabupaten.addEventListener('change', function () { setelSekolah({ kabupaten: L1.kabupaten.value }); });
    L1.nama.addEventListener('input', function () { W.draf.sekolah.nama = L1.nama.value; simpanDraf(); });
    L1.usulan.addEventListener('input', function () { W.draf.sekolah.namaUsulan = L1.usulan.value; simpanDraf(); });
    L1.usulan.addEventListener('change', function () { setelSekolah({ namaUsulan: L1.usulan.value }); });
    L1.usulanBatal.addEventListener('click', function () { setelSekolah({ usulanTerbuka: false, namaUsulan: '' }); });
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
