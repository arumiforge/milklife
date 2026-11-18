/*
 * Dipindah dari skrip sebaris admin/pengaturan/nomor_lomba/index.php supaya lolos CSP (F7-32, K127).
 */
// Saringan langsung diterapkan saat diganti; tombol Tampilkan tetap ada bila skrip mati.
(function () {
    var saringan = document.getElementById('saringan');
    if (!saringan) {
        return;
    }
    saringan.addEventListener('change', function () {
        saringan.requestSubmit ? saringan.requestSubmit() : saringan.submit();
    });
})();
