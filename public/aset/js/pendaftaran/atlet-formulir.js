/**
 * Langkah 3: pilihan nomor lomba, muat, dan simpan atlet di formulir.
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
    var NOMOR = W.NOMOR;
    var $ = W.$;
    var $$ = W.$$;
    var buat = W.buat;
    var ikon = W.ikon;
    var rapikanSpasi = W.rapikanSpasi;
    var rupiah = W.rupiah;
    var uid = W.uid;
    var simpanDraf = W.simpanDraf;
    var jenjangSekolah = W.jenjangSekolah;
    var jkFormulir = W.jkFormulir;
    var L3 = W.L3;
    var maksimalIndividu = W.maksimalIndividu;
    var nomorCocokJk = W.nomorCocokJk;
    var nomorDipilih = W.nomorDipilih;
    var tanggalSah = W.tanggalSah;
    var tentukanKu = W.tentukanKu;
    var namaKu = W.namaKu;
    var usia = W.usia;
    var kotakRegu = W.kotakRegu;
    var reguFormulir = W.reguFormulir;
    var tampilkanKotakRegu = W.tampilkanKotakRegu;

    var pilihKu = {
        kotak: $('#atlet-pilih-ku'),
        keterangan: $('#atlet-pilih-ku-keterangan'),
        daftar: $('#atlet-pilih-ku-daftar')
    };

    /**
     * Kelompok umur yang dipilih di formulir (K134), atau kelompok umur atlet
     * yang sedang dimuat untuk diubah.
     */
    function kuDipilih() {
        var r = $('input[name="atlet-ku"]:checked', L3.formulir);
        if (r) {
            return Number(r.value);
        }
        return W.atletDiubah && L3.formulir.getAttribute('data-muat') === '1' ? (W.atletDiubah.kelompok_umur_id || null) : null;
    }

    /**
     * Tombol pilihan kelompok umur bila tahun lahir masuk lebih dari satu
     * kelompok umur jenjang ini (K134). Tombol hanya dibuat ulang bila
     * daftarnya berubah, supaya pilihan dan fokus tidak hilang.
     */
    function tampilkanPilihanKu(tahun, hasil, pilihan) {
        if (hasil.pilihan.length < 2) {
            pilihKu.kotak.hidden = true;
            pilihKu.daftar.textContent = '';
            pilihKu.daftar.setAttribute('data-ku', '');
            return;
        }

        var kunci = hasil.pilihan.map(function (ku) { return ku.id; }).join(',');
        pilihKu.kotak.hidden = false;
        pilihKu.keterangan.textContent = 'Atlet lahir tahun ' + tahun + ' boleh ikut ' + hasil.pilihan.length + ' kelompok umur. '
            + 'Pilih satu: nomor lomba yang tampil mengikuti kelompok umur yang dipilih, dan atlet hanya ikut nomor di kelompok umur itu.';

        if (pilihKu.daftar.getAttribute('data-ku') === kunci) {
            return;
        }

        pilihKu.daftar.textContent = '';
        pilihKu.daftar.setAttribute('data-ku', kunci);
        hasil.pilihan.forEach(function (ku) {
            var id = 'atlet-ku-' + ku.id;
            var radio = buat('input', 'sr-only');
            radio.type = 'radio';
            radio.name = 'atlet-ku';
            radio.id = id;
            radio.value = String(ku.id);
            radio.checked = pilihan !== null && Number(pilihan) === ku.id;
            // Radio di dalam labelnya supaya tampilan terpilih memakai `has-checked:` (F9-05).
            var label = buat('label', W.KELAS.pilihan + ' !items-start text-start', ku.nama);
            label.htmlFor = id;
            label.insertBefore(radio, label.firstChild);
            if (ku.teks_tahun_lahir) {
                label.appendChild(buat('span', 'pilih-ku-rentang block text-sm font-normal', ku.teks_tahun_lahir));
            }
            pilihKu.daftar.appendChild(label);
        });
    }

    function isiPilihanNomor() {
        var lahir = L3.lahir.value;
        var jk = jkFormulir();
        var dipilih = nomorDipilih();
        var reguSebelum = reguFormulir();
        var pilihan = kuDipilih();

        if (W.atletDiubah && dipilih.length === 0 && L3.formulir.getAttribute('data-muat') === '1') {
            dipilih = W.atletDiubah.nomor.slice();
            reguSebelum = Object.assign({}, W.atletDiubah.regu || {});
        }
        L3.formulir.removeAttribute('data-muat');

        L3.ku.hidden = true;
        L3.ku.textContent = '';
        L3.ku.classList.remove('info-ku-galat');
        L3.nomor.hidden = true;
        L3.individu.textContent = '';
        L3.beregu.textContent = '';

        if (!tanggalSah(lahir)) {
            tampilkanPilihanKu(0, { pilihan: [] }, null);
            return;
        }

        var tahun = Number(lahir.slice(0, 4));
        var hasil = tentukanKu(tahun, jenjangSekolah(), pilihan);
        tampilkanPilihanKu(tahun, hasil, pilihan);
        L3.ku.hidden = false;

        if (!hasil.ku && hasil.pilihan.length > 1) {
            // Belum memilih: bukan galat, cukup arahan.
            L3.ku.appendChild(ikon('circle-arrow-up'));
            L3.ku.appendChild(document.createTextNode(' Pilih kelompok umur di atas supaya nomor lombanya tampil.'));
            return;
        }

        if (!hasil.ku) {
            L3.ku.classList.add('info-ku-galat');
            L3.ku.appendChild(ikon('triangle-alert'));
            L3.ku.appendChild(document.createTextNode(' ' + hasil.galat));
            return;
        }

        L3.ku.appendChild(ikon('id-card'));
        L3.ku.appendChild(document.createTextNode(' ' + (hasil.pilihan.length > 1 ? 'Dipilih ' + namaKu(hasil.ku) : hasil.ku.nama) + (usia(lahir) ? ' · usia ' + usia(lahir) + ' saat lomba' : '')));

        if (!jk) {
            L3.ku.appendChild(buat('span', 'block basis-full text-sm font-normal', 'Pilih jenis kelamin untuk melihat nomor lomba.'));
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
            var kotak = buat('div', 'flex flex-wrap items-start gap-x-3 gap-y-1 rounded-lg border border-gray-200 bg-white px-3 py-2.5 has-[>input:checked]:border-primary-700 has-[>input:checked]:bg-primary-50');
            var centang = buat('input', W.KELAS.centang + ' mt-0.5');
            centang.type = 'checkbox';
            centang.name = 'atlet-nomor';
            centang.id = id;
            centang.value = String(nomor.id);
            centang.checked = dipilih.indexOf(nomor.id) !== -1;
            centang.setAttribute('data-jenis', nomor.jenis);
            var label = buat('label', 'min-w-0 flex-1 cursor-pointer text-gray-900', nomor.jenis === 'beregu' ? null : nomor.nama_lengkap);
            label.htmlFor = id;
            if (nomor.jenis === 'beregu') {
                // Judul nomor, lalu keterangan singkat per regu (K106).
                label.appendChild(buat('span', 'nomor-beregu-judul block font-semibold text-primary-900', nomor.nama_lengkap));
                var info = buat('span', 'nomor-beregu-info mt-1 flex flex-wrap gap-1');
                var cadangan = Math.max(0, (nomor.batas_anggota || 0) - (nomor.jumlah_inti || 0));
                [
                    ['inti', nomor.jumlah_inti + ' inti'],
                    cadangan > 0 ? ['cadangan', '+ ' + cadangan + ' cadangan'] : null,
                    nomor.kategori === 'mix' ? ['', 'inti min. ' + nomor.jumlah_putra + ' putra, ' + nomor.jumlah_putri + ' putri'] : null,
                    nomor.regu_maksimal ? ['', 'maks. ' + nomor.regu_maksimal + ' regu'] : null,
                    nomor.biaya_beregu ? ['biaya', rupiah(nomor.biaya_beregu) + ' / regu'] : null
                ].filter(Boolean).forEach(function (b) {
                    info.appendChild(buat('span', W.kelasKeping(b[0]), b[1]));
                });
                label.appendChild(info);
            }
            kotak.appendChild(centang);
            kotak.appendChild(label);
            if (nomor.jenis === 'beregu') {
                kotak.classList.add('pilihan-nomor-beregu', 'sm:col-span-2');
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
            L3.ku.appendChild(buat('span', 'block basis-full text-sm font-normal', 'Belum ada nomor lomba ' + hasil.ku.nama + ' untuk jenis kelamin ini.'));
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
        W.atletDiubah = null;
        W.reguDibuka = {};
        L3.formulir.reset();
        L3.judul.textContent = 'Tambah atlet';
        $('span', L3.simpan).textContent = 'Tambahkan ke daftar';
        L3.batal.hidden = true;
        bersihkanGalatFormulir();
        pilihKu.daftar.textContent = '';
        pilihKu.daftar.setAttribute('data-ku', '');
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
        W.atletDiubah = atlet;
        W.reguDibuka = {};
        L3.nama.value = atlet.nama;
        L3.nisn.value = atlet.nisn;
        L3.lahir.value = atlet.tanggal_lahir;
        $$('input[name="atlet-jk"]').forEach(function (r) { r.checked = r.value === atlet.jenis_kelamin; });
        L3.judul.textContent = 'Ubah atlet ' + atlet.nama;
        $('span', L3.simpan).textContent = 'Simpan perubahan';
        L3.batal.hidden = false;
        bersihkanGalatFormulir();
        L3.formulir.setAttribute('data-muat', '1');
        pilihKu.daftar.textContent = '';
        pilihKu.daftar.setAttribute('data-ku', '');
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
        if (e.target.name === 'atlet-ku') {
            pilihKu.kotak.classList.remove('is-invalid');
            isiPilihanNomor();
        }
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
        var hasilKu = tanggalSah(lahir) ? tentukanKu(Number(lahir.slice(0, 4)), jenjangSekolah(), kuDipilih()) : null;

        if (nama === '') {
            galatFormulir(L3.nama, 'Nama atlet belum diisi.');
            salah = true;
        }
        if (!/^\d{10}$/.test(nisn)) {
            galatFormulir(L3.nisn, 'NISN harus tepat 10 angka.');
            salah = true;
        } else {
            var kembar = W.draf.atlet.filter(function (a) { return a.nisn === nisn && a !== W.atletDiubah; })[0];
            if (kembar) {
                galatFormulir(L3.nisn, 'NISN ini sudah dipakai ' + kembar.nama + ' di daftar. Satu atlet cukup didaftarkan sekali dengan semua nomornya.');
                salah = true;
            }
        }
        if (!tanggalSah(lahir)) {
            galatFormulir(L3.lahir, 'Tanggal lahir belum benar. Pilih tanggal lahir dari kalender.');
            salah = true;
        } else if (!hasilKu.ku && hasilKu.pilihan.length > 1) {
            galatFormulir(pilihKu.kotak, 'Kelompok umur belum dipilih. Pilih salah satu: ' + hasilKu.pilihan.map(function (ku) { return ku.nama; }).join(' atau ') + '.');
            salah = true;
        } else if (!hasilKu.ku) {
            galatFormulir(L3.lahir, hasilKu.galat);
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
        // Kelompok umur pilihan hanya dicatat bila memang ada pilihan (K134).
        var kuPilihan = hasilKu.pilihan.length > 1 ? hasilKu.ku.id : null;
        var data = { nama: nama, nisn: nisn, tanggal_lahir: lahir, jenis_kelamin: jk, kelompok_umur_id: kuPilihan, nomor: nomor, regu: regu };

        if (W.atletDiubah) {
            Object.keys(data).forEach(function (k) { W.atletDiubah[k] = data[k]; });
        } else {
            data.id = uid();
            W.draf.atlet.push(data);
        }

        simpanDraf();
        kosongkanFormulirAtlet();
        W.tampilkanDaftarAtlet();
        L3.nama.focus();
    });

    // Dipakai bagian lain.
    W.isiPilihanNomor = isiPilihanNomor;
    W.kosongkanFormulirAtlet = kosongkanFormulirAtlet;
    W.muatAtlet = muatAtlet;
}(window.Pendaftaran));
