/*
 * Dipindah dari skrip sebaris admin/atlet_ditolak/index.php supaya lolos CSP (F7-32, K127).
 */
// Kotak jumlah sebagai saringan cepat: mengisi pilihan Keadaan lalu memicu saringan tabel.
document.addEventListener('DOMContentLoaded', function () {
    var pilih = document.getElementById('saring-keadaan');
    var kotak = document.querySelectorAll('[data-saring-keadaan]');
    function tandai() {
        kotak.forEach(function (k) {
            k.setAttribute('aria-pressed', k.getAttribute('data-saring-keadaan') === pilih.value ? 'true' : 'false');
        });
    }
    kotak.forEach(function (k) {
        k.addEventListener('click', function () {
            pilih.value = k.getAttribute('data-saring-keadaan');
            pilih.dispatchEvent(new Event('change', { bubbles: true }));
            tandai();
        });
    });
    pilih.addEventListener('change', tandai);
    pilih.form.addEventListener('reset', function () { setTimeout(tandai, 0); });
});
