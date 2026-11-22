/*
 * Kotak "centang semua" untuk tabel biasa (bukan DataTables).
 *
 * Kotak dengan atribut `data-centang-semua="<pemilih tabel>"` mencentang atau
 * mengosongkan semua kotak centang lain di tabel itu, dan ikut berubah
 * (tercentang, kosong, atau setengah) saat kotak di tabel diubah satu per satu.
 *
 *     <input type="checkbox" checked data-centang-semua="#tabel-nama-baru" aria-label="Centang semua sekolah">
 *
 * Dipakai admin/sekolah/nama_desa.php (K114); bila tabelnya berhalaman
 * (`data-tabel-klien`, F10-04), semua halaman ikut. Tanpa JavaScript, setiap kotak
 * tetap bisa dicentang sendiri.
 */
(function () {
    'use strict';

    function siapkan(induk) {
        var tabel = document.querySelector(induk.getAttribute('data-centang-semua'));
        if (!tabel) {
            return;
        }

        function anak() {
            // Tabel berhalaman (F10-04): baris di halaman lain ikut dicentang.
            var baris = window.TabelAdmin && window.TabelAdmin.semuaBaris
                ? window.TabelAdmin.semuaBaris(tabel)
                : null;
            var kotak = [];
            if (baris) {
                baris.forEach(function (tr) {
                    Array.prototype.push.apply(kotak, tr.querySelectorAll('input[type="checkbox"]'));
                });
            } else {
                kotak = tabel.querySelectorAll('input[type="checkbox"]');
            }
            return Array.prototype.filter.call(kotak, function (k) { return k !== induk; });
        }

        function perbarui() {
            var semua = anak();
            var dicentang = semua.filter(function (kotak) { return kotak.checked; }).length;
            induk.checked = semua.length > 0 && dicentang === semua.length;
            induk.indeterminate = dicentang > 0 && dicentang < semua.length;
        }

        induk.addEventListener('change', function () {
            anak().forEach(function (kotak) { kotak.checked = induk.checked; });
            induk.indeterminate = false;
        });

        tabel.addEventListener('change', function (e) {
            if (e.target !== induk && e.target.type === 'checkbox') {
                perbarui();
            }
        });

        perbarui();
    }

    document.querySelectorAll('[data-centang-semua]').forEach(siapkan);
})();
