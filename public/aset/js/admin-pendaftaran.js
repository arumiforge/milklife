/*
 * Halaman daftar pendaftaran panel admin (Tahap 6, F6-02).
 *
 * Mendaftarkan bentuk kolom untuk aset/js/tabel-admin.js, dipakai
 * admin/pendaftaran/index.php lewat atribut data-bentuk:
 *   pilih          kotak centang milik formulir #form-massal (K76)
 *   idPendaftaran  ID pendaftaran sebagai tautan ke rinciannya
 *   rincian        tombol "Rincian" ke halaman rincian
 *   statusBukti    lencana status + tanda "Bukti baru" (F8-02)
 * Alamat rincian dari kunci `alamat` di setiap baris JSON
 * (App\Libraries\Panel\DataPanelPendaftaran::bentukBaris()).
 *
 * Dimuat di bagian `skrip`, setelah tabel-admin.js dan konfirmasi.js dan
 * sebelum tabel dibuat (tabel dibuat setelah seluruh halaman terbaca).
 *
 * Bilah tindakan massal: jumlah yang dicentang, tombol aktif bila ada yang
 * dicentang, dan kalimat jendela konfirmasi (konfirmasi.js) yang menyebut
 * jumlahnya. Centang dikosongkan setiap tabel digambar ulang.
 */
(function () {
    'use strict';

    if (!window.TabelAdmin) {
        return;
    }

    var escape = window.TabelAdmin.escape;

    function alamatAman(alamat) {
        // Hanya alamat http(s) atau relatif yang dipakai sebagai tautan.
        return typeof alamat === 'string' && /^(https?:\/\/|\/)/i.test(alamat) ? alamat : '';
    }

    window.TabelAdmin.bentuk.idPendaftaran = function (nilai, baris) {
        var alamat = alamatAman(baris && baris.alamat);
        if (!alamat) {
            return escape(nilai);
        }
        return '<a class="font-semibold whitespace-nowrap text-primary-700 hover:underline" href="' + escape(alamat) + '">' + escape(nilai) + '</a>';
    };

    // F8-02: lencana status, ditambah tanda "Bukti baru" bila ada bukti bayar yang belum diperiksa.
    window.TabelAdmin.bentuk.statusBukti = function (nilai, baris) {
        var html = window.TabelAdmin.bentuk.status(nilai, baris);
        var baru = parseInt(baris && baris.bukti_baru, 10) || 0;
        if (baru > 0) {
            html += ' <span class="inline-flex items-center rounded-md bg-primary-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-primary-800" data-bukti-baru title="' + baru + ' bukti bayar baru belum diperiksa">Bukti baru</span>';
        }
        return html;
    };

    // F8-02: saringan dari alamat, misalnya admin/pendaftaran?bukti=baru dari kartu dasbor.
    // Hanya nilai yang ada di pilihan; tombol "Kosongkan saringan" tetap mengosongkannya.
    (function () {
        var formulir = document.getElementById('saringan-pendaftaran');
        if (!formulir || !window.URLSearchParams) {
            return;
        }
        new URLSearchParams(window.location.search).forEach(function (nilai, nama) {
            var kotak = formulir.elements.namedItem(nama);
            if (kotak && kotak.tagName === 'SELECT' && Array.prototype.some.call(kotak.options, function (o) { return o.value === nilai; })) {
                kotak.value = nilai;
            }
        });
    }());

    window.TabelAdmin.bentuk.pilih = function (nilai, baris) {
        var id = parseInt(nilai, 10);
        if (!(id > 0)) {
            return '';
        }
        return '<input class="pilih-baris size-5 rounded border-gray-300 text-primary-600 focus:ring-2 focus:ring-primary-500" type="checkbox" form="form-massal" name="id[]" value="' + id + '"'
            + ' aria-label="Centang ' + escape(baris && baris.id_pendaftaran) + '">';
    };

    var KALIMAT = {
        lunas: ['Tandai Lunas?', ' pendaftaran akan ditandai Lunas dan atletnya mendapat nomor atlet (BIB). Pastikan transfernya sudah diperiksa.', 'Tandai Lunas', 'utama'],
        menunggu: ['Kembalikan ke Menunggu?', ' pendaftaran akan kembali menjadi Menunggu Konfirmasi Pembayaran. Nomor atlet (BIB) tetap.', 'Kembalikan ke Menunggu', 'utama'],
        hapus: ['Hapus pendaftaran?', ' pendaftaran akan dihapus beserta atlet, foto ofisial, dan bukti bayarnya. Tindakan ini tidak bisa dibatalkan.', 'Hapus', 'bahaya']
    };

    function siapkanMassal() {
        var formulir = document.getElementById('form-massal');
        var semua = document.getElementById('pilih-semua');
        var tabel = document.getElementById('tabel-pendaftaran');
        if (!formulir || !semua || !tabel) {
            return;
        }

        function tercentang() {
            return tabel.querySelectorAll('input.pilih-baris:checked');
        }

        function perbarui() {
            var jumlah = tercentang().length;
            var total = tabel.querySelectorAll('input.pilih-baris').length;
            document.getElementById('jumlah-dipilih').textContent = String(jumlah);
            formulir.classList.toggle('bilah-massal-aktif', jumlah > 0);
            formulir.querySelectorAll('button[name="tindakan"]').forEach(function (tombol) {
                tombol.disabled = jumlah === 0;
            });
            semua.checked = total > 0 && jumlah === total;
            semua.indeterminate = jumlah > 0 && jumlah < total;
        }

        semua.addEventListener('change', function () {
            tabel.querySelectorAll('input.pilih-baris').forEach(function (kotak) {
                kotak.checked = semua.checked;
            });
            perbarui();
        });

        tabel.addEventListener('change', function (peristiwa) {
            if (peristiwa.target.classList.contains('pilih-baris')) {
                perbarui();
            }
        });

        // Dipasang di formulir, jadi berjalan sebelum pendengar konfirmasi.js di dokumen.
        formulir.addEventListener('submit', function (peristiwa) {
            var jumlah = tercentang().length;
            var tombol = peristiwa.submitter;
            var kalimat = KALIMAT[tombol && tombol.value];
            if (jumlah === 0 || !kalimat) {
                peristiwa.preventDefault();
                peristiwa.stopImmediatePropagation();
                return;
            }
            formulir.setAttribute('data-konfirmasi-judul', kalimat[0]);
            formulir.setAttribute('data-konfirmasi', jumlah + kalimat[1]);
            formulir.setAttribute('data-konfirmasi-tombol', kalimat[2]);
            formulir.setAttribute('data-konfirmasi-jenis', kalimat[3]);
        });

        var dt = window.TabelAdmin.tabel('#tabel-pendaftaran');
        if (dt) {
            dt.on('draw.dt', perbarui);
        }
        perbarui();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            // Tunggu tabel dibuat tabel-admin.js (pendengar DOMContentLoaded yang lebih dulu).
            window.setTimeout(siapkanMassal, 0);
        });
    } else {
        window.setTimeout(siapkanMassal, 0);
    }

    window.TabelAdmin.bentuk.rincian = function (nilai, baris) {
        var alamat = alamatAman(nilai);
        if (!alamat) {
            return '';
        }
        return '<a class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 text-sm font-semibold whitespace-nowrap text-gray-900 hover:bg-gray-50 hover:text-primary-700" href="' + escape(alamat) + '">'
            + 'Rincian'
            + '<span class="sr-only"> ' + escape(baris && baris.id_pendaftaran) + '</span></a>';
    };
}());
