/*
 * Tabel data panel admin (Tahap 6, F6-01).
 *
 * Membuat DataTables 2 dari atribut data-* pada <table data-tabel-admin>.
 * Data diambil dari alamat JSON (App\Libraries\Panel\TabelSisiServer):
 * halaman, pencarian, saringan, dan urutan dikerjakan server.
 * Dimuat oleh tata_letak/admin.php bila halaman mengisi bagian `tabel`.
 *
 * Atribut pada <table>:
 *   data-tabel-admin             penanda (wajib)
 *   data-alamat="…/data"         alamat JSON (wajib)
 *   data-saringan="#id-form"     formulir saringan (pilihan); isinya ikut dikirim
 *                                dengan nama yang sama, dan tabel dimuat ulang
 *                                setiap isian berubah
 *   data-excel="#id-tautan"      tautan unduh Excel (pilihan); alamatnya diberi
 *                                saringan, pencarian, dan urutan yang sedang aktif
 *   data-kosong="…"              pesan bila belum ada data sama sekali
 *   data-kosong-saring="…"       pesan bila tidak ada yang cocok dengan saringan
 *   data-urut="2" data-arah="desc"   urutan awal (nomor kolom, mulai 0; bawaan: tanpa urutan, server memakai urutan bawaannya)
 *   data-panjang="25"            baris per halaman (bawaan 25)
 *
 * Atribut pada setiap <th> di <thead>:
 *   data-kolom="nama_sekolah"    kunci data di JSON (wajib)
 *   data-bentuk="rupiah"         cara menampilkan (pilihan): teks (bawaan), angka,
 *                                rupiah, tanggal, status, tautan, html, atau nama
 *                                yang didaftarkan halaman di TabelAdmin.bentuk
 *   data-urut="tidak"            kolom tidak bisa diurutkan
 *   class="text-end"             ikut dipakai untuk sel isinya
 *
 * Bentuk bawaan:
 *   teks    teks biasa (selalu di-escape)
 *   angka   1.234
 *   rupiah  Rp1.234.000
 *   tanggal 12 Nov 2026 dari 2026-11-12 atau 2026-11-12 08:30:00
 *   status  lencana Lunas (hijau) atau Menunggu (kuning) dari lunas/menunggu
 *   tautan  nilai {alamat, teks} menjadi <a>; teks di-escape
 *   html    disisipkan apa adanya; hanya untuk HTML yang sudah di-escape server
 *
 * Halaman bisa menambah bentuk sendiri sebelum DOMContentLoaded selesai:
 *   TabelAdmin.bentuk.aksi = function (nilai, baris) { return '…'; };
 * Tabel yang sudah dibuat: TabelAdmin.tabel('#id-tabel') (objek DataTable).
 *
 * Bila data gagal dimuat (sambungan putus, atau sesi admin berakhir sehingga
 * server mengirim halaman masuk alih-alih JSON), tabel menampilkan pesan
 * yang menyebut cara memperbaikinya.
 *
 * Selama data dimuat, bilah kemajuan di atas halaman menyala (kemajuan.js) dan
 * tabel diredupkan (.tabel-admin-memuat di admin.css, wilayah Tahap 6).
 */
(function () {
    'use strict';

    if (typeof window.DataTable !== 'function') {
        return;
    }

    var BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    var angkaId = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

    function escape(teks) {
        return String(teks === null || teks === undefined ? '' : teks)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function kosong(nilai) {
        return nilai === null || nilai === undefined || nilai === '';
    }

    var bentuk = {
        teks: function (nilai) {
            return kosong(nilai) ? '' : escape(nilai);
        },
        angka: function (nilai) {
            return kosong(nilai) || isNaN(Number(nilai)) ? escape(nilai) : angkaId.format(Number(nilai));
        },
        rupiah: function (nilai) {
            return kosong(nilai) || isNaN(Number(nilai)) ? escape(nilai) : 'Rp' + angkaId.format(Number(nilai));
        },
        tanggal: function (nilai) {
            var cocok = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(nilai || ''));
            if (!cocok) {
                return escape(nilai);
            }
            return Number(cocok[3]) + ' ' + BULAN[Number(cocok[2]) - 1] + ' ' + cocok[1];
        },
        status: function (nilai) {
            if (nilai === 'lunas') {
                return '<span class="badge text-bg-success">Lunas</span>';
            }
            if (nilai === 'menunggu') {
                return '<span class="badge text-bg-warning">Menunggu</span>';
            }
            return escape(nilai);
        },
        tautan: function (nilai) {
            if (!nilai || typeof nilai !== 'object' || !nilai.alamat) {
                return escape(nilai && nilai.teks);
            }
            return '<a href="' + escape(nilai.alamat) + '">' + escape(nilai.teks || nilai.alamat) + '</a>';
        },
        html: function (nilai) {
            return kosong(nilai) ? '' : String(nilai);
        }
    };

    // Teks DataTables dalam bahasa Indonesia, untuk orang non-teknis.
    var BAHASA = {
        processing: 'Memuat data…',
        search: 'Cari:',
        searchPlaceholder: 'Ketik untuk mencari',
        lengthMenu: 'Tampilkan _MENU_ baris',
        info: 'Baris _START_–_END_ dari _TOTAL_',
        infoEmpty: 'Tidak ada baris',
        infoFiltered: '(disaring dari _MAX_ baris)',
        infoThousands: '.',
        thousands: '.',
        decimal: ',',
        loadingRecords: 'Memuat data…',
        zeroRecords: 'Tidak ada data yang cocok.',
        emptyTable: 'Belum ada data.',
        paginate: {
            first: 'Pertama',
            previous: 'Sebelumnya',
            next: 'Berikutnya',
            last: 'Terakhir'
        },
        aria: {
            sortAscending: ': urutkan naik',
            sortDescending: ': urutkan turun',
            paginate: {
                first: 'Halaman pertama',
                previous: 'Halaman sebelumnya',
                next: 'Halaman berikutnya',
                last: 'Halaman terakhir',
                number: 'Halaman '
            }
        },
        entries: { _: 'baris', 1: 'baris' }
    };

    var PESAN_GALAT = 'Data gagal dimuat. Periksa sambungan internet, lalu muat ulang halaman. '
        + 'Bila Anda sudah lama tidak membuka panel, masuk lagi lebih dulu.';

    var daftarTabel = {};

    function pilih(pemilih) {
        return pemilih ? document.querySelector(pemilih) : null;
    }

    /** Isian formulir saringan yang terisi, sebagai pasangan nama → nilai. */
    function isiSaringan(formulir) {
        var hasil = {};
        if (!formulir) {
            return hasil;
        }
        new FormData(formulir).forEach(function (nilai, nama) {
            if (typeof nilai === 'string' && nilai.trim() !== '') {
                hasil[nama] = nilai.trim();
            }
        });
        return hasil;
    }

    function perbaruiExcel(tautan, formulir, dt, kolom) {
        if (!tautan) {
            return;
        }
        var dasar = tautan.getAttribute('data-alamat-dasar');
        if (!dasar) {
            dasar = tautan.getAttribute('href').split('?')[0];
            tautan.setAttribute('data-alamat-dasar', dasar);
        }

        var kueri = new URLSearchParams(isiSaringan(formulir));
        var cari = dt.search();
        if (cari) {
            kueri.set('cari', cari);
        }
        var urut = dt.order();
        if (urut.length && kolom[urut[0][0]]) {
            kueri.set('urut', kolom[urut[0][0]].data);
            kueri.set('arah', urut[0][1] === 'desc' ? 'desc' : 'asc');
        }

        var teks = kueri.toString();
        tautan.setAttribute('href', dasar + (teks ? '?' + teks : ''));
    }

    function buat(tabel) {
        var alamat = tabel.getAttribute('data-alamat');
        if (!alamat || tabel.getAttribute('data-tabel-siap') === 'ya') {
            return;
        }
        tabel.setAttribute('data-tabel-siap', 'ya');

        var formulir = pilih(tabel.getAttribute('data-saringan'));
        var tautanExcel = pilih(tabel.getAttribute('data-excel'));
        var pesanKosong = tabel.getAttribute('data-kosong') || BAHASA.emptyTable;
        var pesanKosongSaring = tabel.getAttribute('data-kosong-saring')
            || 'Tidak ada data yang cocok dengan pencarian atau saringan. Ubah atau kosongkan saringan.';
        var panjang = parseInt(tabel.getAttribute('data-panjang'), 10) || 25;

        var kolom = [];
        tabel.querySelectorAll('thead th').forEach(function (th) {
            var namaBentuk = th.getAttribute('data-bentuk') || 'teks';
            var kelas = (th.className || '').split(/\s+/).filter(function (k) {
                return /^text-/.test(k);
            }).join(' ');
            kolom.push({
                data: th.getAttribute('data-kolom'),
                orderable: th.getAttribute('data-urut') !== 'tidak',
                className: kelas,
                render: function (nilai, jenis, baris) {
                    var fungsi = bentuk[namaBentuk] || bentuk.teks;
                    if (jenis !== 'display') {
                        return nilai;
                    }
                    return fungsi(nilai, baris);
                }
            });
        });

        var urutAwal = [];
        if (tabel.hasAttribute('data-urut')) {
            urutAwal.push([parseInt(tabel.getAttribute('data-urut'), 10) || 0, tabel.getAttribute('data-arah') === 'desc' ? 'desc' : 'asc']);
        }

        var bahasa = Object.assign({}, BAHASA, { emptyTable: pesanKosong, zeroRecords: pesanKosongSaring });
        var dt;
        var bilahMenyala = false;

        function mulaiMemuat() {
            tabel.classList.add('tabel-admin-memuat');
            if (!bilahMenyala && window.Kemajuan) {
                window.Kemajuan.mulai();
                bilahMenyala = true;
            }
        }

        function selesaiMemuat() {
            tabel.classList.remove('tabel-admin-memuat');
            if (bilahMenyala && window.Kemajuan) {
                window.Kemajuan.selesai();
            }
            bilahMenyala = false;
        }

        DataTable.ext.errMode = 'none';

        dt = new DataTable(tabel, {
            serverSide: true,
            processing: false,
            searchDelay: 400,
            pageLength: panjang,
            lengthMenu: [10, 25, 50, 100],
            order: urutAwal,
            autoWidth: false,
            columns: kolom,
            language: bahasa,
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                // Tanpa tombol Pertama/Terakhir supaya muat di ponsel.
                bottomEnd: { paging: { firstLast: false, buttons: 5 } }
            },
            ajax: {
                url: alamat,
                type: 'GET',
                data: function (d) {
                    // Dipanggil tepat sebelum setiap permintaan, termasuk yang pertama
                    // saat tabel dibuat (sebelum pendengar peristiwa sempat dipasang).
                    mulaiMemuat();
                    // Kirim nama kolom dan urutan saja; daftar kolom lengkap tidak dibutuhkan server.
                    var kirim = {
                        draw: d.draw,
                        start: d.start,
                        length: d.length,
                        'search[value]': d.search.value
                    };
                    d.order.forEach(function (o, i) {
                        kirim['order[' + i + '][column]'] = o.column;
                        kirim['order[' + i + '][dir]'] = o.dir;
                        kirim['columns[' + o.column + '][data]'] = d.columns[o.column].data;
                    });
                    return Object.assign(kirim, isiSaringan(formulir));
                }
            }
        });

        // DataTables sendiri memilih pesan kosong: emptyTable bila belum ada data
        // sama sekali (recordsTotal 0), zeroRecords bila saringan terlalu ketat.
        dt.on('xhr.dt', selesaiMemuat);

        // DataTables 2 menamai peristiwa galatnya `dt-error`.
        dt.on('dt-error', function () {
            selesaiMemuat();
            var tubuh = tabel.querySelector('tbody');
            if (tubuh) {
                tubuh.innerHTML = '<tr><td colspan="' + kolom.length + '" class="text-danger py-4">'
                    + '<i class="bi bi-exclamation-triangle me-2" aria-hidden="true"></i>'
                    + escape(PESAN_GALAT) + '</td></tr>';
            }
        });

        dt.on('draw.dt', function () {
            perbaruiExcel(tautanExcel, formulir, dt, kolom);
        });

        if (formulir) {
            var muatUlang = function () {
                dt.ajax.reload();
                perbaruiExcel(tautanExcel, formulir, dt, kolom);
            };
            formulir.addEventListener('change', muatUlang);
            formulir.addEventListener('submit', function (e) {
                e.preventDefault();
                muatUlang();
            });
            formulir.addEventListener('reset', function () {
                window.setTimeout(muatUlang, 0);
            });
        }

        perbaruiExcel(tautanExcel, formulir, dt, kolom);

        if (tabel.id) {
            daftarTabel['#' + tabel.id] = dt;
        }
    }

    function mulai() {
        document.querySelectorAll('table[data-tabel-admin]').forEach(buat);
    }

    window.TabelAdmin = {
        bentuk: bentuk,
        escape: escape,
        /** Objek DataTable untuk tabel dengan id tertentu, contoh TabelAdmin.tabel('#tabel-pendaftaran'). */
        tabel: function (pemilih) {
            return daftarTabel[pemilih] || null;
        },
        /** Buat tabel yang ditambahkan ke halaman setelah halaman dimuat. */
        buat: buat
    };

    // Skrip ini dimuat di akhir <body>, sebelum bagian `skrip` halaman; tabel
    // dibuat setelah seluruh halaman terbaca supaya bentuk tambahan halaman
    // (TabelAdmin.bentuk.…) sudah terdaftar.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulai);
    } else {
        mulai();
    }
}());
