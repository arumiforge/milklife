/*
 * Tombol Salin alamat di halaman daftar tampilan videotron `/layar`
 * (F11-09, `app/Views/layar/daftar.php`).
 *
 * Tombol `[data-salin-alamat]` tersembunyi tanpa JavaScript; skrip ini
 * menampilkannya bila peramban bisa menyalin ke papan klip. Klik menyalin
 * alamat, label tombol berubah "Tersalin" sebentar, dan pesan untuk pembaca
 * layar ditulis di `[data-salin-status]`.
 */
(function () {
    'use strict';

    if (!navigator.clipboard || !window.isSecureContext) {
        return;
    }

    var status = document.querySelector('[data-salin-status]');

    document.querySelectorAll('[data-salin-alamat]').forEach(function (tombol) {
        var label = tombol.querySelector('span');
        var pewaktu = null;

        tombol.classList.remove('hidden');
        tombol.classList.add('inline-flex');
        tombol.addEventListener('click', function () {
            navigator.clipboard.writeText(tombol.getAttribute('data-salin-alamat')).then(function () {
                if (label) {
                    label.textContent = 'Tersalin';
                }
                if (status) {
                    status.textContent = 'Alamat tersalin.';
                }
                clearTimeout(pewaktu);
                pewaktu = setTimeout(function () {
                    if (label) {
                        label.textContent = 'Salin';
                    }
                }, 2000);
            }).catch(function () {
                if (status) {
                    status.textContent = 'Alamat belum tersalin. Pilih alamatnya lalu salin manual.';
                }
            });
        });
    });
})();
