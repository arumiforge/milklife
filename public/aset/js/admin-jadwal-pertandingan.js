/*
 * Halaman admin Jadwal pertandingan (F9-07): alat "Isi berurutan".
 *
 * Kotak centang per babak dan bagian "Isi berurutan" baru tampil bila skrip
 * berjalan. Babak yang dicentang (urut dari atas) diberi jam mulai pertama,
 * lalu bertambah sebanyak jeda menit; tanggal dan lokasi ikut diisi bila
 * ditulis. Tidak ada yang tersimpan sampai admin menekan "Simpan jadwal".
 */
(function () {
    'use strict';

    var alat = document.querySelector('[data-isi-berurutan]');
    var baris = Array.prototype.slice.call(document.querySelectorAll('[data-baris-jadwal]'));

    if (!alat || baris.length === 0) {
        return;
    }

    var centang = baris.map(function (b) { return b.querySelector('[data-centang-babak]'); });
    var semua = document.querySelector('[data-centang-semua]');
    var wadahSemua = document.querySelector('[data-centang-semua-wadah]');
    var pesan = alat.querySelector('[data-pesan-isi-berurutan]');

    alat.hidden = false;
    centang.forEach(function (c) { c.classList.remove('hidden'); });
    if (wadahSemua) {
        wadahSemua.classList.remove('hidden');
        wadahSemua.classList.add('inline-flex');
    }

    if (semua) {
        semua.addEventListener('change', function () {
            centang.forEach(function (c) { c.checked = semua.checked; });
        });
    }

    function dua(n) {
        return (n < 10 ? '0' : '') + n;
    }

    function isi(kotak, nilai) {
        kotak.value = nilai;
        kotak.dispatchEvent(new Event('input', { bubbles: true }));
        kotak.dispatchEvent(new Event('change', { bubbles: true }));
    }

    alat.querySelector('[data-tombol-isi-berurutan]').addEventListener('click', function () {
        var jam = (document.getElementById('urut-jam').value || '').split(':');
        var jeda = parseInt(document.getElementById('urut-jeda').value, 10);
        var tanggal = document.getElementById('urut-tanggal').value.trim();
        var lokasi = document.getElementById('urut-lokasi').value.trim();
        var dipilih = baris.filter(function (b, i) { return centang[i].checked; });

        if (dipilih.length === 0) {
            pesan.textContent = 'Centang dulu babak yang ingin diisi.';
            return;
        }

        if (jam.length < 2 || isNaN(parseInt(jam[0], 10))) {
            pesan.textContent = 'Isi jam mulai pertama, misalnya 07:00.';
            return;
        }

        var menit = parseInt(jam[0], 10) * 60 + parseInt(jam[1], 10);
        jeda = isNaN(jeda) || jeda < 0 ? 0 : jeda;

        dipilih.forEach(function (b, i) {
            var m = (menit + i * jeda) % (24 * 60);
            isi(b.querySelector('[data-kotak="jam"]'), dua(Math.floor(m / 60)) + ':' + dua(m % 60));
            if (tanggal !== '') {
                isi(b.querySelector('[data-kotak="tanggal"]'), tanggal);
            }
            if (lokasi !== '') {
                isi(b.querySelector('[data-kotak="lokasi"]'), lokasi);
            }
        });

        pesan.textContent = dipilih.length + ' babak terisi. Periksa lagi, lalu tekan Simpan jadwal.';
    });
}());
