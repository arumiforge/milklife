/**
 * Langkah 3: isian formulir atlet dan kelompok umur (K95).
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
    var hariIni = W.hariIni;

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
     * Bila tahun lahir cocok dengan lebih dari satu kelompok umur, pendaftar memilih
     * (`pilihan`, K134); tanpa pilihan yang sah hasilnya `ku: null` dengan `pilihan` terisi.
     */
    function masukKu(tahun, ku) {
        var awal = ku.tahun_lahir_awal;
        var akhir = ku.tahun_lahir_akhir;
        if (awal === null && akhir === null) {
            return false;
        }
        return (awal === null || tahun >= awal) && (akhir === null || tahun <= akhir);
    }

    function gabungNamaKu(daftar) {
        var nama = daftar.map(function (ku) { return ku.nama; });
        return nama.length <= 2 ? nama.join(' atau ') : nama.slice(0, -1).join(', ') + ', atau ' + nama[nama.length - 1];
    }

    function tentukanKu(tahun, jenjang, pilihan) {
        var untukJenjang = DATA.kelompok_umur.filter(function (ku) { return ku.jenjang.indexOf(jenjang) !== -1; });
        var cocok = untukJenjang.filter(function (ku) { return masukKu(tahun, ku); });

        if (cocok.length === 1) {
            return { ku: cocok[0], galat: null, pilihan: cocok };
        }
        if (cocok.length > 1) {
            var dipilih = cocok.filter(function (ku) { return pilihan !== undefined && pilihan !== null && ku.id === Number(pilihan); })[0];
            return dipilih
                ? { ku: dipilih, galat: null, pilihan: cocok }
                : { ku: null, galat: 'Atlet lahir tahun ' + tahun + ' boleh ikut ' + gabungNamaKu(cocok) + '. Pilih salah satu kelompok umur untuk atlet ini.', pilihan: cocok };
        }

        var labelJenjang = DATA.jenjang[jenjang] || jenjang || '-';

        if (!untukJenjang.length) {
            return { ku: null, galat: 'Belum ada kelompok umur yang dibuka untuk ' + labelJenjang + '. Atlet ini belum bisa didaftarkan.', pilihan: [] };
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
                    + '. Sekolah ini ' + labelJenjang + '. Atlet ini belum bisa didaftarkan' + rentang + '.',
                pilihan: []
            };
        }

        return {
            ku: null,
            galat: 'Atlet lahir tahun ' + tahun + ' tidak masuk kelompok umur mana pun untuk ' + labelJenjang + rentang + '. Periksa lagi tanggal lahirnya.',
            pilihan: []
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

    // Dipakai bagian lain.
    W.L3 = L3;
    W.jkFormulir = jkFormulir;
    W.kuotaNomor = kuotaNomor;
    W.maksimalIndividu = maksimalIndividu;
    W.nomorCocokJk = nomorCocokJk;
    W.nomorDipilih = nomorDipilih;
    W.tanggalSah = tanggalSah;
    W.tentukanKu = tentukanKu;
    W.namaKu = namaKu;
    W.usia = usia;
}(window.Pendaftaran));
