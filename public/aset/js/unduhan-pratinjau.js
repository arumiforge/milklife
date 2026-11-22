/*
 * Pratinjau berkas unduhan di modal Flowbite (F10-09).
 *
 * Tombol "Lihat" (`a[data-unduhan-lihat]`, dari `beranda/_daftar_unduhan.php`)
 * menuju rute `unduhan/<id>/lihat` yang mengirim PDF atau gambar dengan
 * `Content-Disposition: inline`, dan membuka tab baru. Di layar `md` ke atas
 * (768 piksel) skrip ini membukanya di modal: PDF dalam `<iframe>`, gambar
 * dalam `<img>`, keduanya dari berkas sendiri (CSP `frame-src 'self'`).
 * Di ponsel, tanpa JavaScript, atau bila Flowbite tidak termuat, tautan
 * tetap membuka tab baru seperti biasa.
 */
(function () {
    'use strict';

    var elemen = document.querySelector('[data-pratinjau-modal]');

    if (!elemen || typeof window.Modal !== 'function') {
        return;
    }

    var layarLebar = window.matchMedia('(min-width: 768px)');
    var isi = elemen.querySelector('[data-pratinjau-isi]');
    var judul = elemen.querySelector('[data-pratinjau-judul]');
    var tombolTutup = elemen.querySelector('[data-pratinjau-tutup]');
    var tautanTab = elemen.querySelector('[data-pratinjau-tab]');
    var tautanUnduh = elemen.querySelector('[data-pratinjau-unduh]');
    var pemicu = null;

    var modal = new window.Modal(elemen, {
        backdrop: 'dynamic',
        closable: true,
        onHide: function () {
            // Kosongkan supaya PDF berhenti dimuat dan tidak terbaca lagi saat tertutup.
            isi.replaceChildren();

            if (pemicu) {
                pemicu.focus();
                pemicu = null;
            }
        }
    });

    function buatPratinjau(tautan) {
        var alamat = tautan.getAttribute('href');
        var nama = tautan.getAttribute('data-judul') || 'Berkas';

        if (tautan.getAttribute('data-pratinjau') === 'pdf') {
            var bingkai = document.createElement('iframe');
            bingkai.src = alamat;
            bingkai.title = 'Pratinjau ' + nama;
            bingkai.className = 'pratinjau-bingkai rounded-lg';
            return bingkai;
        }

        var gambar = document.createElement('img');
        gambar.src = alamat;
        gambar.alt = nama;
        gambar.decoding = 'async';
        gambar.className = 'pratinjau-gambar rounded-lg';
        return gambar;
    }

    document.addEventListener('click', function (kejadian) {
        var tautan = kejadian.target.closest('a[data-unduhan-lihat]');

        // Klik tengah atau dengan Ctrl/Cmd/Shift tetap membuka tab sesuai kebiasaan peramban.
        if (!tautan || !layarLebar.matches || kejadian.button !== 0 || kejadian.ctrlKey || kejadian.metaKey || kejadian.shiftKey || kejadian.altKey) {
            return;
        }

        kejadian.preventDefault();
        pemicu = tautan;
        judul.textContent = tautan.getAttribute('data-judul') || 'Lihat berkas';
        tautanTab.href = tautan.getAttribute('href');
        tautanUnduh.href = tautan.getAttribute('data-unduh') || tautan.getAttribute('href');
        tautanUnduh.setAttribute('download', tautan.getAttribute('data-nama-simpan') || '');
        isi.replaceChildren(buatPratinjau(tautan));
        modal.show();
        tombolTutup.focus();
    });

    tombolTutup.addEventListener('click', function () {
        modal.hide();
    });
}());
