/*
 * Dipindah dari skrip sebaris admin/pengaturan/pembayaran.php supaya lolos CSP (F7-32, K127).
 */
(function () {
    'use strict';

    var kotak = document.getElementById('templat_pesan');
    var contoh = document.getElementById('contoh-pesan');
    var data = JSON.parse(contoh.getAttribute('data-contoh') || '{}');

    function perbaruiContoh() {
        var hasil = kotak.value;
        Object.keys(data).forEach(function (penanda) {
            hasil = hasil.split(penanda).join(data[penanda]);
        });
        contoh.textContent = hasil.trim();
    }

    kotak.addEventListener('input', perbaruiContoh);

    // Pesan yang sudah diubah admin tidak langsung ditimpa: tanya dulu lewat
    // jendela konfirmasi.js (modal Flowbite). Belum disimpan sampai tombol Simpan.
    document.getElementById('pakai-pesan-awal').addEventListener('click', function () {
        var awal = this.getAttribute('data-pesan-awal');

        function pakai() {
            kotak.value = awal;
            perbaruiContoh();
            kotak.focus();
        }

        if (kotak.value.trim() === '' || kotak.value === awal || !window.Konfirmasi) {
            pakai();
            return;
        }

        window.Konfirmasi.tanya(
            'Pakai pesan awal?',
            'Isi pesan sekarang diganti pesan awal. Perubahan baru tersimpan setelah menekan Simpan.',
            'Ganti dengan pesan awal',
            'utama'
        ).then(function (ya) {
            if (ya) {
                pakai();
            } else {
                kotak.focus();
            }
        });
    });

    // Penanda disisipkan di posisi kursor terakhir; bila kotak pesan belum
    // pernah disentuh, di akhir pesan.
    var disentuh = false;
    kotak.addEventListener('focus', function () { disentuh = true; });

    document.querySelectorAll('[data-penanda]').forEach(function (tombol) {
        tombol.addEventListener('click', function () {
            var penanda = tombol.getAttribute('data-penanda');
            var awal = disentuh ? kotak.selectionStart : kotak.value.length;
            var akhir = disentuh ? kotak.selectionEnd : kotak.value.length;
            kotak.value = kotak.value.slice(0, awal) + penanda + kotak.value.slice(akhir);
            kotak.focus();
            kotak.selectionStart = kotak.selectionEnd = awal + penanda.length;
            perbaruiContoh();
        });
    });

    // Pratinjau logo yang baru dipilih, sebelum disimpan.
    var berkas = document.getElementById('logo_bank');
    var wadah = document.querySelector('[data-pratinjau="logo_bank"]');
    berkas.addEventListener('change', function () {
        if (!berkas.files || !berkas.files[0] || !/^image\//.test(berkas.files[0].type)) {
            return;
        }
        var bingkai = document.createElement('div');
        bingkai.className = 'aspect-video overflow-hidden rounded-lg border border-gray-200 bg-white';
        var gambar = document.createElement('img');
        gambar.src = URL.createObjectURL(berkas.files[0]);
        gambar.alt = 'Logo bank yang baru dipilih (belum disimpan)';
        gambar.className = 'size-full object-contain p-2';
        bingkai.appendChild(gambar);
        wadah.replaceChildren(bingkai);
    });
}());
