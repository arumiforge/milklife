/*
 * Formulir rekor kejuaraan (F11-06): saat nomor lomba dipilih, spesifikasi
 * alat dan cara pencatatan waktu diisi dari nomor itu, pilihan cara waktu
 * hanya tampil untuk nomor waktu, dan contoh prestasi menyesuaikan.
 */
(function () {
    'use strict';

    var pilih = document.getElementById('nomor_lomba_id');
    if (!pilih) return;

    var data = {};
    try { data = JSON.parse(pilih.getAttribute('data-nomor-rekor') || '{}'); } catch (e) { data = {}; }

    var alat = document.getElementById('berat_alat');
    var bagianCara = document.getElementById('bagian-cara-waktu-rekor');
    var contoh = document.querySelector('[data-contoh-prestasi]');

    pilih.addEventListener('change', function () {
        var n = data[pilih.value];
        if (!n) return;
        if (alat) alat.value = n.alat || '';
        if (bagianCara) {
            bagianCara.hidden = !n.waktu;
            var radio = document.getElementById('cara_waktu-' + (n.cara || 'elektronik'));
            if (radio) radio.checked = true;
        }
        if (contoh) contoh.textContent = n.contoh;
    });
})();
