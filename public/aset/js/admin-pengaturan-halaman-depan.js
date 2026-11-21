/*
 * Dipindah dari skrip sebaris admin/pengaturan/halaman_depan.php supaya lolos CSP (F7-32, K127).
 */
(function () {
    'use strict';

    // Rasio kotak gambar (`data-rasio`) => kelas Tailwind, sama dengan tampilannya.
    var RASIO = { '1x1': 'aspect-square', '4x3': 'aspect-[4/3]', '16x9': 'aspect-video', '21x9': 'aspect-[21/9]' };

    // Pratinjau gambar yang baru dipilih, sebelum disimpan.
    document.querySelectorAll('[data-pratinjau]').forEach(function (wadah) {
        var nama = wadah.getAttribute('data-pratinjau');
        var berkas = document.getElementById(nama);
        berkas.addEventListener('change', function () {
            if (!berkas.files || !berkas.files[0] || !/^image\//.test(berkas.files[0].type)) {
                return;
            }
            var bingkai = document.createElement('div');
            bingkai.className = (RASIO[wadah.getAttribute('data-rasio')] || 'aspect-video') + ' overflow-hidden rounded-lg border border-gray-200 bg-white';
            var gambar = document.createElement('img');
            gambar.src = URL.createObjectURL(berkas.files[0]);
            gambar.alt = 'Gambar yang baru dipilih (belum disimpan)';
            gambar.className = nama === 'gambar_kepala' ? 'size-full object-cover' : 'size-full object-contain p-2';
            bingkai.appendChild(gambar);
            wadah.replaceChildren(bingkai);
        });
    });
}());
