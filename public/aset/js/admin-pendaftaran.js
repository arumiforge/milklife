/*
 * Halaman daftar pendaftaran panel admin (Tahap 6, F6-02).
 *
 * Mendaftarkan dua bentuk kolom untuk aset/js/tabel-admin.js, dipakai
 * admin/pendaftaran/index.php lewat atribut data-bentuk:
 *   idPendaftaran  ID pendaftaran sebagai tautan ke rinciannya
 *   rincian        tombol "Rincian" ke halaman rincian
 * Alamat rincian dari kunci `alamat` di setiap baris JSON
 * (App\Libraries\Panel\DataPanelPendaftaran::bentukBaris()).
 *
 * Dimuat di bagian `skrip`, setelah tabel-admin.js dan sebelum tabel dibuat
 * (tabel dibuat setelah seluruh halaman terbaca).
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
