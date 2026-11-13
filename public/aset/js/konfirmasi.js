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
 *
 * Skrip yang sama memeriksa ukuran berkas sebelum formulir dikirim. Kotak
 * unggah dengan `data-ukuran-maksimal="<bita>"` menolak berkas yang lebih
 * besar dengan pesan ramah, supaya admin tidak mengirim berkas yang akan
 * ditolak server (berkas di atas batas PHP `post_max_size` bahkan tidak
 * sampai ke pengendali). Batasnya dari `App\Libraries\Unggahan`:
 *
 *     <input type="file" class="form-control" id="logo_bank" name="logo_bank"
 *         accept="image/jpeg,image/png,image/webp"
 *         data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>">
 */
(function () {
    'use strict';

    var jendela = null;
    // Dipanggil dengan true (setuju) atau false (batal/tutup) setelah jendela dijawab.
    var tindakanMenunggu = null;

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
            var tindakan = tindakanMenunggu;

            tindakanMenunggu = null;
            jendela.hide();

            if (tindakan) {
                tindakan(true);
            }
        });

        elemen.addEventListener('hidden.bs.modal', function () {
            var tindakan = tindakanMenunggu;

            tindakanMenunggu = null;

            if (tindakan) {
                tindakan(false);
            }
        });

        return new window.bootstrap.Modal(elemen);
    }

    /**
     * Tampilkan jendela; `tindakan(setuju)` dipanggil sekali setelah dijawab.
     * `jenis`: 'utama' (tombol oranye) atau lainnya (tombol merah).
     */
    function tanya(judul, kalimat, labelTombol, jenis, tindakan) {
        if (!window.bootstrap || !window.bootstrap.Modal) {
            tindakan(window.confirm(kalimat));
            return;
        }

        if (!jendela) {
            jendela = buatJendela();
        }

        var elemen = document.getElementById('jendela-konfirmasi');
        var setuju = elemen.querySelector('[data-konfirmasi-setuju]');

        elemen.querySelector('#jendela-konfirmasi-judul').textContent = judul || 'Yakin?';
        elemen.querySelector('#jendela-konfirmasi-isi').textContent = kalimat;
        setuju.textContent = labelTombol || 'Ya, lanjutkan';
        setuju.className = 'btn ' + (jenis === 'utama' ? 'btn-primary' : 'btn-danger');

        tindakanMenunggu = tindakan;
        jendela.show();
    }

    /**
     * Untuk skrip lain (mis. wizard pendaftaran), K89:
     *
     *     Konfirmasi.tanya('Hapus atlet?', 'BUDI akan dihapus.', 'Hapus', 'bahaya').then(function (ya) { … });
     */
    window.Konfirmasi = {
        tanya: function (judul, kalimat, labelTombol, jenis) {
            return new Promise(function (selesai) {
                tanya(judul, kalimat, labelTombol, jenis, selesai);
            });
        }
    };

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

    function teksUkuran(bita) {
        var mb = bita / (1024 * 1024);

        return mb >= 1 ? (Math.round(mb * 10) / 10).toString().replace('.', ',') + ' MB' : Math.max(1, Math.round(bita / 1024)) + ' KB';
    }

    // Periksa ukuran berkas di kotak unggah; kembalikan false bila terlalu besar.
    function periksaUkuran(kotak) {
        var batas = parseInt(kotak.getAttribute('data-ukuran-maksimal'), 10);
        var pesan = '';

        if (batas > 0 && kotak.files) {
            for (var i = 0; i < kotak.files.length; i++) {
                if (kotak.files[i].size > batas) {
                    pesan = 'Ukuran berkas ' + teksUkuran(kotak.files[i].size) + ', lebih dari batas ' +
                        teksUkuran(batas) + '. Perkecil dulu, lalu pilih lagi.';
                    break;
                }
            }
        }

        kotak.setCustomValidity(pesan);

        if (pesan !== '') {
            kotak.reportValidity();
        }

        return pesan === '';
    }

    document.addEventListener('change', function (peristiwa) {
        var kotak = peristiwa.target;

        if (kotak instanceof HTMLInputElement && kotak.type === 'file' && kotak.hasAttribute('data-ukuran-maksimal')) {
            periksaUkuran(kotak);
        }
    });

    document.addEventListener('submit', function (peristiwa) {
        var formulir = peristiwa.target;

        if (formulir instanceof HTMLFormElement) {
            var kotakBerkas = formulir.querySelectorAll('input[type="file"][data-ukuran-maksimal]');

            for (var i = 0; i < kotakBerkas.length; i++) {
                if (!periksaUkuran(kotakBerkas[i])) {
                    peristiwa.preventDefault();
                    kotakBerkas[i].focus();
                    return;
                }
            }
        }

        if (!(formulir instanceof HTMLFormElement) || !formulir.hasAttribute('data-konfirmasi')) {
            return;
        }

        if (formulir.dataset.konfirmasiDisetujui === '1') {
            delete formulir.dataset.konfirmasiDisetujui;
            return;
        }

        peristiwa.preventDefault();

        var pengirim = peristiwa.submitter || null;

        tanya(
            formulir.getAttribute('data-konfirmasi-judul'),
            formulir.getAttribute('data-konfirmasi') || 'Lanjutkan tindakan ini?',
            formulir.getAttribute('data-konfirmasi-tombol'),
            formulir.getAttribute('data-konfirmasi-jenis'),
            function (setuju) {
                if (setuju) {
                    kirim(formulir, pengirim);
                }
            }
        );
    });
}());
