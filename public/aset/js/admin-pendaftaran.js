/*
 * Halaman daftar pendaftaran panel admin (Tahap 6, F6-02).
 *
 * Mendaftarkan bentuk kolom untuk aset/js/tabel-admin.js, dipakai
 * admin/pendaftaran/index.php lewat atribut data-bentuk:
 *   pilih          kotak centang milik formulir #form-massal (K76)
 *   idPendaftaran  ID pendaftaran sebagai tautan ke rinciannya
 *   rincian        tombol "Rincian" ke halaman rincian
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
        return '<a class="fw-semibold text-nowrap" href="' + escape(alamat) + '">' + escape(nilai) + '</a>';
    };

    window.TabelAdmin.bentuk.pilih = function (nilai, baris) {
        var id = parseInt(nilai, 10);
        if (!(id > 0)) {
            return '';
        }
        return '<input class="form-check-input pilih-baris" type="checkbox" form="form-massal" name="id[]" value="' + id + '"'
            + ' aria-label="Centang ' + escape(baris && baris.id_pendaftaran) + '">';
    };

    var KALIMAT = {
        lunas: ['Tandai Lunas?', ' pendaftaran akan ditandai Lunas dan atletnya mendapat nomor dada. Pastikan transfernya sudah diperiksa.', 'Tandai Lunas', 'utama'],
        menunggu: ['Kembalikan ke Menunggu?', ' pendaftaran akan kembali menjadi Menunggu Konfirmasi Pembayaran. Nomor dada atlet tetap.', 'Kembalikan ke Menunggu', 'utama'],
        hapus: ['Hapus pendaftaran?', ' pendaftaran akan dihapus beserta atlet, foto penanggung jawab, dan bukti bayarnya. Tindakan ini tidak bisa dibatalkan.', 'Hapus', 'bahaya']
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
        return '<a class="btn btn-sm btn-outline-primary text-nowrap" href="' + escape(alamat) + '">'
            + '<i class="bi bi-eye" aria-hidden="true"></i> Rincian'
            + '<span class="visually-hidden"> ' + escape(baris && baris.id_pendaftaran) + '</span></a>';
    };
}());
