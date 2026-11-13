/*
 * Pemilih tanggal yang ramah (K89): setiap `<input type="date">` dan
 * `<input type="datetime-local">` diganti tampilannya dengan pilihan Tanggal,
 * Bulan (nama bulan), dan Tahun, ditambah Jam dan Menit untuk tanggal-waktu.
 *
 * Isian aslinya tetap ada (disembunyikan) dan selalu berisi nilai baku
 * (`YYYY-MM-DD` atau `YYYY-MM-DDTHH:MM`), jadi formulir, pemeriksaan di
 * server, dan skrip lain tidak berubah. Setiap pilihan berubah, isian asli
 * menerima peristiwa `input` dan `change`. Nilai yang diisi skrip lain
 * (`isian.value = …`) langsung tampil di pilihan. Tanda `is-invalid` pada
 * isian asli ikut menandai pilihannya, begitu juga `disabled`.
 *
 * Rentang tahun dari atribut `min`/`max` bila ada; selain itu tanggal 25
 * tahun ke belakang (tanggal lahir) dan tanggal-waktu setahun ke belakang
 * sampai dua tahun ke depan. Isian bertanda `data-tanpa-pilih-tanggal`
 * dibiarkan.
 */
(function () {
    'use strict';

    var BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    var nilaiAsli = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');

    function dua(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function pilihan(kelas, label, isi) {
        var s = document.createElement('select');
        s.className = 'form-select ' + kelas;
        s.setAttribute('aria-label', label);
        var kosong = document.createElement('option');
        kosong.value = '';
        kosong.textContent = label;
        s.appendChild(kosong);
        isi.forEach(function (o) {
            var opsi = document.createElement('option');
            opsi.value = o[0];
            opsi.textContent = o[1];
            s.appendChild(opsi);
        });
        return s;
    }

    function rentang(a, b, langkah) {
        var hasil = [];
        for (var i = a; langkah > 0 ? i <= b : i >= b; i += langkah) {
            hasil.push(i);
        }
        return hasil;
    }

    function pasang(isian) {
        var waktu = isian.type === 'datetime-local';
        var sekarang = new Date().getFullYear();
        var min = parseInt((isian.getAttribute('min') || '').slice(0, 4), 10) || (waktu ? sekarang - 1 : sekarang - 25);
        var max = parseInt((isian.getAttribute('max') || '').slice(0, 4), 10) || (waktu ? sekarang + 2 : sekarang);

        var hari = pilihan('pilih-tanggal-hari', 'Tanggal', rentang(1, 31, 1).map(function (n) { return [dua(n), String(n)]; }));
        var bulan = pilihan('pilih-tanggal-bulan', 'Bulan', BULAN.map(function (nama, i) { return [dua(i + 1), nama]; }));
        var tahun = pilihan('pilih-tanggal-tahun', 'Tahun', rentang(max, min, -1).map(function (n) { return [String(n), String(n)]; }));
        var jam = waktu ? pilihan('pilih-tanggal-jam', 'Jam', rentang(0, 23, 1).map(function (n) { return [dua(n), dua(n)]; })) : null;
        var menit = waktu ? pilihan('pilih-tanggal-menit', 'Menit', rentang(0, 59, 1).map(function (n) { return [dua(n), dua(n)]; })) : null;
        var semua = [hari, bulan, tahun].concat(waktu ? [jam, menit] : []);

        var wadah = document.createElement('div');
        wadah.className = 'pilih-tanggal' + (waktu ? ' pilih-tanggal-waktu' : '');
        wadah.setAttribute('role', 'group');
        var label = isian.id ? document.querySelector('label[for="' + isian.id + '"]') : null;
        if (label) {
            if (!label.id) {
                label.id = isian.id + '-label';
            }
            wadah.setAttribute('aria-labelledby', label.id);
            // Menekan label memilih pilihan pertama, bukan isian tersembunyi.
            label.htmlFor = '';
            label.addEventListener('click', function () { hari.focus(); });
        }
        var tanggalBaris = document.createElement('div');
        tanggalBaris.className = 'pilih-tanggal-baris';
        [hari, bulan, tahun].forEach(function (s) { tanggalBaris.appendChild(s); });
        wadah.appendChild(tanggalBaris);
        if (waktu) {
            var jamBaris = document.createElement('div');
            jamBaris.className = 'pilih-tanggal-baris pilih-tanggal-jam-baris';
            var pukul = document.createElement('span');
            pukul.className = 'pilih-tanggal-pukul';
            pukul.textContent = 'pukul';
            jamBaris.appendChild(pukul);
            jamBaris.appendChild(jam);
            var titik = document.createElement('span');
            titik.className = 'pilih-tanggal-pukul';
            titik.textContent = '.';
            jamBaris.appendChild(titik);
            jamBaris.appendChild(menit);
            wadah.appendChild(jamBaris);
        }

        // Tanggal di luar jumlah hari bulan itu (mis. 31 Februari) dinonaktifkan.
        function batasiHari() {
            var jumlah = new Date(parseInt(tahun.value, 10) || 2000, parseInt(bulan.value, 10) || 1, 0).getDate();
            if (!bulan.value) {
                jumlah = 31;
            }
            Array.prototype.forEach.call(hari.options, function (o) {
                o.disabled = o.value !== '' && parseInt(o.value, 10) > jumlah;
            });
            if (hari.value && parseInt(hari.value, 10) > jumlah) {
                hari.value = '';
            }
        }

        function tampilkan() {
            var v = nilaiAsli.get.call(isian) || '';
            var c = v.match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
            if (c && !Array.prototype.some.call(tahun.options, function (o) { return o.value === c[1]; })) {
                // Tahun di luar rentang (mis. data lama) tetap bisa ditampilkan.
                var o = document.createElement('option');
                o.value = c[1];
                o.textContent = c[1];
                tahun.appendChild(o);
            }
            tahun.value = c ? c[1] : '';
            bulan.value = c ? c[2] : '';
            batasiHari();
            hari.value = c ? c[3] : '';
            if (waktu) {
                jam.value = c && c[4] ? c[4] : '';
                menit.value = c && c[5] ? c[5] : '';
            }
        }

        function simpan() {
            batasiHari();
            var lengkap = hari.value && bulan.value && tahun.value && (!waktu || (jam.value && menit.value));
            var v = lengkap ? tahun.value + '-' + bulan.value + '-' + hari.value + (waktu ? 'T' + jam.value + ':' + menit.value : '') : '';
            nilaiAsli.set.call(isian, v);
            isian.dispatchEvent(new Event('input', { bubbles: true }));
            isian.dispatchEvent(new Event('change', { bubbles: true }));
        }

        semua.forEach(function (s) { s.addEventListener('change', simpan); });

        Object.defineProperty(isian, 'value', {
            configurable: true,
            get: function () { return nilaiAsli.get.call(this); },
            set: function (v) {
                nilaiAsli.set.call(this, v);
                tampilkan();
            }
        });

        function tandai() {
            var salah = isian.classList.contains('is-invalid');
            semua.forEach(function (s) {
                s.classList.toggle('is-invalid', salah);
                s.disabled = isian.disabled;
            });
        }
        new MutationObserver(tandai).observe(isian, { attributes: true, attributeFilter: ['class', 'disabled'] });

        isian.hidden = true;
        isian.tabIndex = -1;
        isian.insertAdjacentElement('afterend', wadah);
        // Dipanggil skrip lain lewat isian.focus() (mis. isian salah pertama).
        isian.focus = function () { hari.focus(); };
        tampilkan();
        tandai();
    }

    function pasangSemua(akar) {
        Array.prototype.forEach.call(
            (akar || document).querySelectorAll('input[type="date"]:not([data-tanpa-pilih-tanggal]), input[type="datetime-local"]:not([data-tanpa-pilih-tanggal])'),
            function (isian) {
                if (!isian.dataset.pilihTanggal) {
                    isian.dataset.pilihTanggal = '1';
                    pasang(isian);
                }
            }
        );
    }

    window.PilihTanggal = { pasang: pasangSemua };
    pasangSemua(document);
}());
