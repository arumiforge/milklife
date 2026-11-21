/*
 * Jendela konfirmasi untuk tindakan berisiko (hapus, tandai lunas).
 *
 * Setiap formulir yang punya atribut `data-konfirmasi` tidak langsung
 * dikirim: jendela (modal Flowbite) muncul dengan kalimat dari atribut itu, dan
 * formulir baru dikirim bila admin menekan tombol konfirmasi.
 *
 * Pemakaian di tampilan:
 *
 *     <form action="<?= site_url('admin/pengaturan/jadwal/' . $id . '/hapus') ?>" method="post"
 *         data-konfirmasi="Jadwal &quot;Technical meeting&quot; akan dihapus dan tidak bisa dikembalikan."
 *         data-konfirmasi-tombol="Hapus jadwal">
 *         <?= csrf_field() ?>
 *         <?= komponen('tombol', ['label' => 'Hapus', 'tipe' => 'submit', 'varian' => 'bahaya', 'ikon' => 'trash-2']) ?>
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
 * memeriksa semua aturan. Jendelanya modal Flowbite (`window.Modal`, F9-05;
 * Bootstrap dilepas di F9-17); bila Flowbite tidak termuat, `window.confirm` dipakai.
 *
 * Kotak centang yang menghapus sesuatu saat formulir disimpan ("Hapus gambar
 * ini", "Hapus logo ini") diberi `data-konfirmasi-centang="<kalimat>"`
 * (pilihan: `data-konfirmasi-judul`, `data-konfirmasi-tombol`). Saat dicentang
 * jendela yang sama muncul; bila admin memilih Batal, centangnya dilepas lagi.
 *
 * Skrip yang sama memeriksa ukuran berkas sebelum formulir dikirim. Kotak
 * unggah dengan `data-ukuran-maksimal="<bita>"` menolak berkas yang lebih
 * besar dengan pesan ramah, supaya admin tidak mengirim berkas yang akan
 * ditolak server (berkas di atas batas PHP `post_max_size` bahkan tidak
 * sampai ke pengendali). Batasnya dari `App\Libraries\Unggahan`:
 *
 *     <input type="file" id="logo_bank" name="logo_bank"
 *         accept="image/jpeg,image/png,image/webp"
 *         data-ukuran-maksimal="<?= Unggahan::BATAS_GAMBAR ?>">
 */
(function () {
    'use strict';

    // Dipanggil dengan true (setuju) atau false (batal/tutup) setelah jendela dijawab.
    var tindakanMenunggu = null;

    // ---------- Modal Flowbite (halaman Tailwind, F9-05) ----------

    var jendelaTw = null;

    function buatJendelaTw() {
        var elemen = document.createElement('div');
        elemen.id = 'jendela-konfirmasi';
        elemen.tabIndex = -1;
        elemen.className = 'fixed inset-x-0 top-0 z-50 hidden h-full max-h-full w-full items-center justify-center overflow-y-auto overflow-x-hidden p-4';
        elemen.setAttribute('role', 'dialog');
        elemen.setAttribute('aria-modal', 'true');
        elemen.setAttribute('aria-labelledby', 'jendela-konfirmasi-judul');
        elemen.setAttribute('aria-describedby', 'jendela-konfirmasi-isi');
        elemen.setAttribute('aria-hidden', 'true');
        elemen.innerHTML =
            '<div class="relative max-h-full w-full max-w-md">' +
            '<div class="relative rounded-2xl bg-white shadow-angkat">' +
            '<div class="border-b border-gray-200 p-4 sm:px-6"><h2 class="text-lg font-bold text-gray-900" id="jendela-konfirmasi-judul"></h2></div>' +
            '<div class="p-4 text-gray-700 sm:p-6"><p id="jendela-konfirmasi-isi"></p></div>' +
            '<div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 p-4 sm:px-6">' +
            '<button type="button" class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-900 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100" data-konfirmasi-batal>Batal</button>' +
            '<button type="button" data-konfirmasi-setuju></button>' +
            '</div>' +
            '</div>' +
            '</div>';
        document.body.appendChild(elemen);

        var modal = new window.Modal(elemen, {
            backdrop: 'dynamic',
            closable: true,
            onHide: function () {
                var tindakan = tindakanMenunggu;

                tindakanMenunggu = null;

                if (tindakan) {
                    tindakan(false);
                }
            }
        }, { id: 'jendela-konfirmasi', override: true });

        elemen.querySelector('[data-konfirmasi-setuju]').addEventListener('click', function () {
            var tindakan = tindakanMenunggu;

            tindakanMenunggu = null;
            modal.hide();

            if (tindakan) {
                tindakan(true);
            }
        });

        elemen.querySelector('[data-konfirmasi-batal]').addEventListener('click', function () {
            modal.hide();
        });

        return modal;
    }

    function tanyaTw(judul, kalimat, labelTombol, jenis, tindakan) {
        if (!jendelaTw) {
            jendelaTw = buatJendelaTw();
        }

        var elemen = document.getElementById('jendela-konfirmasi');
        var setuju = elemen.querySelector('[data-konfirmasi-setuju]');

        elemen.querySelector('#jendela-konfirmasi-judul').textContent = judul || 'Yakin?';
        elemen.querySelector('#jendela-konfirmasi-isi').textContent = kalimat;
        setuju.textContent = labelTombol || 'Ya, lanjutkan';
        setuju.className = 'inline-flex min-h-11 items-center justify-center rounded-lg px-5 py-2.5 text-sm font-semibold text-white focus:outline-none focus:ring-4 '
            + (jenis === 'utama' ? 'bg-oranye-600 hover:bg-oranye-700 focus:ring-oranye-300' : 'bg-red-700 hover:bg-red-800 focus:ring-red-300');

        tindakanMenunggu = tindakan;
        jendelaTw.show();
        setuju.focus();
    }

    /**
     * Tampilkan jendela; `tindakan(setuju)` dipanggil sekali setelah dijawab.
     * `jenis`: 'utama' (tombol oranye) atau lainnya (tombol merah).
     */
    function tanya(judul, kalimat, labelTombol, jenis, tindakan) {
        if (typeof window.Modal === 'function') {
            tanyaTw(judul, kalimat, labelTombol, jenis, tindakan);
            return;
        }

        tindakan(window.confirm(kalimat));
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

        if (kotak instanceof HTMLInputElement && kotak.type === 'checkbox' && kotak.checked && kotak.hasAttribute('data-konfirmasi-centang')) {
            tanya(
                kotak.getAttribute('data-konfirmasi-judul') || 'Hapus gambar?',
                kotak.getAttribute('data-konfirmasi-centang'),
                kotak.getAttribute('data-konfirmasi-tombol') || 'Ya, hapus saat disimpan',
                'bahaya',
                function (setuju) {
                    if (!setuju) {
                        kotak.checked = false;
                    }

                    kotak.focus();
                }
            );
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
