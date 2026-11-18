/*
 * Dipindah dari skrip sebaris admin/pengaturan/halaman_depan.php supaya lolos CSP (F7-32, K127).
 */
(function () {
    'use strict';

    // Pratinjau gambar yang baru dipilih, sebelum disimpan.
    document.querySelectorAll('[data-pratinjau]').forEach(function (wadah) {
        var nama = wadah.getAttribute('data-pratinjau');
        var berkas = document.getElementById(nama);
        berkas.addEventListener('change', function () {
            if (!berkas.files || !berkas.files[0] || !/^image\//.test(berkas.files[0].type)) {
                return;
            }
            var bingkai = document.createElement('div');
            bingkai.className = 'ratio ratio-' + wadah.getAttribute('data-rasio') + ' border rounded bg-white';
            var gambar = document.createElement('img');
            gambar.src = URL.createObjectURL(berkas.files[0]);
            gambar.alt = 'Gambar yang baru dipilih (belum disimpan)';
            gambar.style.objectFit = nama === 'gambar_kepala' ? 'cover' : 'contain';
            gambar.className = nama === 'gambar_kepala' ? 'rounded' : 'p-2';
            bingkai.appendChild(gambar);
            wadah.replaceChildren(bingkai);
        });
    });
}());
