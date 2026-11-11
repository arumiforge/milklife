/*
 * Jendela konfirmasi untuk tindakan berisiko (hapus, tandai lunas).
 *
 * Setiap formulir yang punya atribut `data-konfirmasi` tidak langsung
 * dikirim: jendela Bootstrap muncul dengan kalimat dari atribut itu, dan
 * formulir baru dikirim bila admin menekan tombol konfirmasi.
 *
 * Pemakaian di tampilan:
 *
 *     <form action="<?= site_url('admin/pengaturan/jadwal/' . $id . '/hapus') ?>" method="post"
 *         data-konfirmasi="Jadwal &quot;Technical meeting&quot; akan dihapus dan tidak bisa dikembalikan."
 *         data-konfirmasi-tombol="Hapus jadwal">
 *         <?= csrf_field() ?>
 *         <button type="submit" class="btn btn-outline-danger">Hapus</button>
 *     </form>
 *
 *     <?= $this->section('skrip') ?>
 *     <script src="<?= aset('js/konfirmasi.js') ?>"></script>
 *     <?= $this->endSection() ?>
 *
 * Atribut:
 * - `data-konfirmasi` (wajib): kalimat yang menyebut apa yang akan terjadi.
 *   Isinya di-escape dengan `esc(..., 'attr')`; ditampilkan sebagai teks biasa.
 * - `data-konfirmasi-judul` (pilihan): judul jendela. Bawaan "Yakin?".
 * - `data-konfirmasi-tombol` (pilihan): label tombol konfirmasi. Bawaan "Ya, lanjutkan".
 * - `data-konfirmasi-jenis` (pilihan): `bahaya` (bawaan, tombol merah) atau
 *   `utama` (tombol oranye, untuk tindakan yang tidak menghapus).
 *
 * Tanpa JavaScript formulir tetap terkirim tanpa konfirmasi; server tetap
 * memeriksa semua aturan. Bila Bootstrap tidak termuat, `window.confirm` dipakai.
 */
(function () {
    'use strict';

    var jendela = null;
    var formulirMenunggu = null;
    var tombolPengirim = null;

    function buatJendela() {
        var elemen = document.createElement('div');
        elemen.className = 'modal fade';
        elemen.id = 'jendela-konfirmasi';
        elemen.tabIndex = -1;
        elemen.setAttribute('aria-labelledby', 'jendela-konfirmasi-judul');
        elemen.setAttribute('aria-describedby', 'jendela-konfirmasi-isi');
        elemen.setAttribute('aria-hidden', 'true');
        elemen.innerHTML =
            '<div class="modal-dialog modal-dialog-centered">' +
            '<div class="modal-content">' +
            '<div class="modal-header">' +
            '<h2 class="modal-title h5" id="jendela-konfirmasi-judul"></h2>' +
            '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>' +
            '</div>' +
            '<div class="modal-body"><p class="mb-0" id="jendela-konfirmasi-isi"></p></div>' +
            '<div class="modal-footer">' +
            '<button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>' +
            '<button type="button" class="btn" data-konfirmasi-setuju></button>' +
            '</div>' +
            '</div>' +
            '</div>';
        document.body.appendChild(elemen);

        elemen.querySelector('[data-konfirmasi-setuju]').addEventListener('click', function () {
            var formulir = formulirMenunggu;
            var pengirim = tombolPengirim;

            formulirMenunggu = null;
            tombolPengirim = null;
            jendela.hide();

            if (formulir) {
                kirim(formulir, pengirim);
            }
        });

        elemen.addEventListener('hidden.bs.modal', function () {
            formulirMenunggu = null;
            tombolPengirim = null;
        });

        return new window.bootstrap.Modal(elemen);
    }

    // Kirim tanpa memicu konfirmasi lagi, dengan tombol pengirim yang sama
    // (supaya nama dan nilai tombolnya ikut terkirim).
    function kirim(formulir, pengirim) {
        formulir.dataset.konfirmasiDisetujui = '1';

        if (typeof formulir.requestSubmit === 'function') {
            formulir.requestSubmit(pengirim && pengirim.form === formulir ? pengirim : undefined);
        } else {
            formulir.submit();
        }
    }

    document.addEventListener('submit', function (peristiwa) {
        var formulir = peristiwa.target;

        if (!(formulir instanceof HTMLFormElement) || !formulir.hasAttribute('data-konfirmasi')) {
            return;
        }

        if (formulir.dataset.konfirmasiDisetujui === '1') {
            delete formulir.dataset.konfirmasiDisetujui;
            return;
        }

        peristiwa.preventDefault();

        var kalimat = formulir.getAttribute('data-konfirmasi') || 'Lanjutkan tindakan ini?';
        var judul = formulir.getAttribute('data-konfirmasi-judul') || 'Yakin?';
        var labelTombol = formulir.getAttribute('data-konfirmasi-tombol') || 'Ya, lanjutkan';
        var jenis = formulir.getAttribute('data-konfirmasi-jenis') === 'utama' ? 'btn-primary' : 'btn-danger';
        var pengirim = peristiwa.submitter || null;

        if (!window.bootstrap || !window.bootstrap.Modal) {
            if (window.confirm(kalimat)) {
                kirim(formulir, pengirim);
            }
            return;
        }

        if (!jendela) {
            jendela = buatJendela();
        }

        var elemen = document.getElementById('jendela-konfirmasi');
        var setuju = elemen.querySelector('[data-konfirmasi-setuju]');

        elemen.querySelector('#jendela-konfirmasi-judul').textContent = judul;
        elemen.querySelector('#jendela-konfirmasi-isi').textContent = kalimat;
        setuju.textContent = labelTombol;
        setuju.className = 'btn ' + jenis;

        formulirMenunggu = formulir;
        tombolPengirim = pengirim;
        jendela.show();
    });
}());
