/*
 * Tabel data panel admin (Tahap 6, F6-01).
 *
 * Membuat DataTables 2 dari atribut data-* pada <table data-tabel-admin>.
 * Data diambil dari alamat JSON (App\Libraries\Panel\TabelSisiServer):
 * halaman, pencarian, saringan, dan urutan dikerjakan server.
 * Dimuat oleh tata_letak/admin_tw.php bila halaman mengisi bagian `tabel`.
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
 *   data-kosong-gambar="…"       alamat ilustrasi keadaan kosong G9-04 (pilihan,
 *                                tampilan baru); server mengisinya hanya bila
 *                                berkasnya ada (K41)
 *
 * Atribut pada setiap <th> di <thead>:
 *   data-kolom="nama_sekolah"    kunci data di JSON (wajib, kecuali bentuk nomor)
 *   data-bentuk="rupiah"         cara menampilkan (pilihan): teks (bawaan), angka,
 *                                rupiah, tanggal, status, tautan, html, atau nama
 *                                yang didaftarkan halaman di TabelAdmin.bentuk
 *   data-urut="tidak"            kolom tidak bisa diurutkan
 *   class="text-end"             ikut dipakai untuk sel isinya
 *
 * Bentuk bawaan:
 *   teks    teks biasa (selalu di-escape)
 *   angka   1.234
 *   rupiah  Rp 1.234.000
 *   tanggal 12 Nov 2026 dari 2026-11-12 atau 2026-11-12 08:30:00
 *   status  lencana Lunas (hijau) atau Menunggu (kuning) dari lunas/menunggu;
 *           merah "Semua atlet ditolak" bila baris.ditolak_semua (K117)
 *   tautan  nilai {alamat, teks} menjadi <a>; teks di-escape
 *   html    disisipkan apa adanya; hanya untuk HTML yang sudah di-escape server
 *   nomor   nomor urut baris (1, 2, …) yang berlanjut antarhalaman; tanpa
 *           data-kolom, tidak bisa diurutkan
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
 * tabel diredupkan (`opacity-60`).
 *
 * Gaya (F9-14): DataTables dimuat tanpa gaya bawaannya; skrip ini memasang
 * kelas Tailwind sendiri (GAYA_TW di bawah) pada kotak cari, pilihan jumlah
 * baris, tabel, dan tombol halaman, juga lencana status dan pesan galat.
 * Kelasnya ditulis utuh di berkas ini supaya ikut dibangun ke `tw.css`. Panah
 * urutan kolom ditulis sebagai teks (↑ ↓ ↕) karena DataTables biasanya
 * menggambarnya dengan CSS bawaannya.
 *
 * Tabel sisi klien (F10-04): <table data-tabel-klien> yang barisnya sudah
 * ditulis server (tabel kecil atau sedang: Pengaturan, rekap, usulan nama
 * sekolah, tolak atlet, …) dibagi per halaman oleh DataTables di peramban,
 * bawaan 25 baris (`data-panjang`). Tabel dengan baris tidak lebih dari satu
 * halaman dibiarkan apa adanya. Urutan baris tetap dari server (tanpa urut
 * kolom), dan kotak cari ikut tampil. Baris di halaman lain tetap ikut
 * terkirim bila tabel ada di dalam <form> (peristiwa `formdata`), dan
 * `centang-semua.js` mencentang semua baris lewat TabelAdmin.semuaBaris().
 *
 * Daftar berhalaman (F10-04): <ol|ul data-daftar-berhalaman> (misalnya
 * riwayat tindakan admin) menampilkan 25 butir per halaman (`data-panjang`)
 * dengan tombol Sebelumnya/Berikutnya; tidak memerlukan DataTables.
 *
 * Skeleton (F9-17): sampai data pertama tiba, badan tabel
 * berisi baris kerangka abu berdenyut (`.kerangka`) dengan teks pembaca layar
 * "Memuat data…"; pemuatan berikutnya (cari, saring, pindah halaman) cukup
 * meredupkan tabel. Bila belum ada data sama sekali, pesan kosong diberi
 * ilustrasi dari `data-kosong-gambar` bila ada.
 */
(function () {
    'use strict';

    // ---------- F10-04: daftar berhalaman (tanpa DataTables) ----------
    var KELAS_TOMBOL_DAFTAR = 'inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:pointer-events-none disabled:opacity-50';

    function buatDaftar(daftar) {
        if (daftar.getAttribute('data-daftar-siap') === 'ya') {
            return;
        }
        var butir = Array.prototype.filter.call(daftar.children, function (el) {
            return el.tagName === 'LI';
        });
        var panjang = parseInt(daftar.getAttribute('data-panjang'), 10) || 25;
        if (butir.length <= panjang) {
            return;
        }
        daftar.setAttribute('data-daftar-siap', 'ya');

        var halaman = 0;
        var jumlahHalaman = Math.ceil(butir.length / panjang);
        var nav = document.createElement('nav');
        nav.className = 'mt-3 flex flex-wrap items-center justify-between gap-3';
        nav.setAttribute('aria-label', 'Halaman daftar');
        nav.setAttribute('data-navigasi-daftar', '');
        nav.innerHTML = '<p class="text-sm text-gray-600" aria-live="polite" data-info-daftar></p>'
            + '<div class="flex items-center gap-1">'
            + '<button type="button" class="' + KELAS_TOMBOL_DAFTAR + '" data-daftar-sebelumnya>Sebelumnya</button>'
            + '<button type="button" class="' + KELAS_TOMBOL_DAFTAR + '" data-daftar-berikutnya>Berikutnya</button>'
            + '</div>';
        daftar.insertAdjacentElement('afterend', nav);

        var info = nav.querySelector('[data-info-daftar]');
        var sebelumnya = nav.querySelector('[data-daftar-sebelumnya]');
        var berikutnya = nav.querySelector('[data-daftar-berikutnya]');

        function tampilkan(ke, gulir) {
            halaman = Math.max(0, Math.min(jumlahHalaman - 1, ke));
            var awal = halaman * panjang;
            butir.forEach(function (li, i) {
                li.hidden = i < awal || i >= awal + panjang;
            });
            info.textContent = 'Butir ' + (awal + 1) + '–' + Math.min(awal + panjang, butir.length)
                + ' dari ' + butir.length + ' (halaman ' + (halaman + 1) + ' dari ' + jumlahHalaman + ')';
            sebelumnya.disabled = halaman === 0;
            berikutnya.disabled = halaman === jumlahHalaman - 1;
            if (gulir && daftar.scrollIntoView) {
                daftar.scrollIntoView({ block: 'nearest' });
            }
        }

        sebelumnya.addEventListener('click', function () { tampilkan(halaman - 1, true); });
        berikutnya.addEventListener('click', function () { tampilkan(halaman + 1, true); });
        tampilkan(0, false);
    }

    function mulaiDaftar() {
        document.querySelectorAll('[data-daftar-berhalaman]').forEach(buatDaftar);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mulaiDaftar);
    } else {
        mulaiDaftar();
    }

    if (typeof window.DataTable !== 'function') {
        return;
    }


    // Kelas Tailwind untuk tampilan baru; padanan dataTables.bootstrap5.js.
    var GAYA_TW = {
        container: 'dt-container space-y-3',
        info: { container: 'dt-info text-sm text-gray-600' },
        layout: {
            row: 'dt-layout-row flex flex-wrap items-center justify-between gap-3',
            cell: 'dt-layout-cell flex flex-wrap items-center gap-2',
            tableRow: 'dt-layout-table',
            tableCell: 'relative block! w-full min-w-0 overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-lembut',
            start: 'dt-layout-start',
            end: 'dt-layout-end sm:ms-auto',
            full: 'dt-layout-full w-full'
        },
        length: {
            container: 'dt-length flex items-center gap-2 text-sm text-gray-700',
            select: 'dt-input min-h-11 rounded-lg border border-gray-300 bg-white py-2 ps-3 pe-8 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500'
        },
        search: {
            container: 'dt-search flex flex-wrap items-center gap-2 text-sm text-gray-700',
            input: 'dt-input min-h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 sm:w-64'
        },
        table: 'dataTable w-full text-left text-sm text-gray-700',
        thead: { row: '', cell: 'border-b border-gray-200 bg-gray-50 px-4 py-3 text-xs font-bold uppercase tracking-wide whitespace-nowrap text-gray-600' },
        tbody: { row: 'border-b border-gray-100 last:border-b-0 hover:bg-gray-50', cell: 'px-4 py-3 align-top' },
        empty: { row: 'dt-empty px-4 py-8 text-center text-gray-500' },
        paging: {
            container: 'dt-paging',
            nav: 'flex flex-wrap items-center gap-1',
            button: 'dt-paging-button inline-flex min-h-11 min-w-11 items-center justify-center rounded-lg border border-gray-200 bg-white px-3 text-sm font-semibold text-gray-700 hover:bg-gray-100 disabled:opacity-50',
            active: 'current border-primary-700! bg-primary-700! text-white!',
            disabled: 'disabled pointer-events-none opacity-50'
        }
    };

    var LENCANA_TW = {
        hijau: 'inline-flex items-center rounded-md bg-green-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-green-800',
        kuning: 'inline-flex items-center rounded-md bg-yellow-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-yellow-800',
        merah: 'inline-flex items-center rounded-md bg-red-100 px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap text-red-800'
    };

    Object.keys(GAYA_TW).forEach(function (kunci) {
        var nilai = GAYA_TW[kunci];
        DataTable.ext.classes[kunci] = typeof nilai === 'string'
            ? nilai
            : Object.assign({}, DataTable.ext.classes[kunci] || {}, nilai);
    });

    // Lebar kotak kerangka per kolom, bergantian supaya tidak tampak seperti garis.
    var LEBAR_KERANGKA = ['w-3/4', 'w-1/2', 'w-2/3', 'w-5/6', 'w-2/5'];

    /** Baris kerangka (skeleton) selama data pertama dimuat; diganti DataTables saat data tiba. */
    function isiKerangka(tabel, jumlahKolom, jumlahBaris) {
        var tubuh = tabel.querySelector('tbody');
        if (!tubuh) {
            tubuh = document.createElement('tbody');
            tabel.appendChild(tubuh);
        }
        var html = '';
        for (var b = 0; b < jumlahBaris; b++) {
            html += '<tr class="border-b border-gray-100 last:border-b-0" data-baris-kerangka>';
            for (var k = 0; k < jumlahKolom; k++) {
                html += '<td class="px-4 py-3.5">' + (b === 0 && k === 0 ? '<span class="sr-only">Memuat data…</span>' : '')
                    + '<div class="kerangka h-4 ' + LEBAR_KERANGKA[(b + k) % LEBAR_KERANGKA.length] + '" aria-hidden="true"></div></td>';
            }
            html += '</tr>';
        }
        tubuh.innerHTML = html;
        tabel.setAttribute('aria-busy', 'true');
    }

    function lencana(teks, warna) {
        return '<span class="' + LENCANA_TW[warna] + '">' + teks + '</span>';
    }

    /** Panah urutan pada judul kolom (tampilan baru), dari aria-sort DataTables. */
    function tandaiUrutan(tabel) {
        tabel.querySelectorAll('thead th').forEach(function (th) {
            var panah = th.querySelector('.dt-column-order');
            if (!panah || !(th.classList.contains('dt-orderable-asc') || th.classList.contains('dt-orderable-desc'))) {
                return;
            }
            var arah = th.getAttribute('aria-sort');
            th.classList.add('cursor-pointer', 'select-none');
            panah.classList.add('ms-1', 'inline-block', 'w-3', 'text-gray-400');
            panah.textContent = arah === 'ascending' ? '↑' : (arah === 'descending' ? '↓' : '↕');
        });
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
            return kosong(nilai) || isNaN(Number(nilai)) ? escape(nilai) : 'Rp ' + angkaId.format(Number(nilai));
        },
        tanggal: function (nilai) {
            var cocok = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(nilai || ''));
            if (!cocok) {
                return escape(nilai);
            }
            return Number(cocok[3]) + ' ' + BULAN[Number(cocok[2]) - 1] + ' ' + cocok[1];
        },
        status: function (nilai, baris) {
            // Pendaftaran yang semua atletnya ditolak panitia (K117).
            if (baris && baris.ditolak_semua) {
                return lencana('Semua atlet ditolak', 'merah');
            }
            if (nilai === 'lunas') {
                return lencana('Lunas', 'hijau');
            }
            if (nilai === 'menunggu') {
                return lencana('Menunggu', 'kuning');
            }
            return escape(nilai);
        },
        tautan: function (nilai) {
            if (!nilai || typeof nilai !== 'object' || !nilai.alamat) {
                return escape(nilai && nilai.teks);
            }
            return '<a' + ' class="font-semibold text-primary-700 hover:underline"' + ' href="' + escape(nilai.alamat) + '">' + escape(nilai.teks || nilai.alamat) + '</a>';
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
            var nomorUrut = namaBentuk === 'nomor';
            var kelas = (th.className || '').split(/\s+/).filter(function (k) {
                return /^text-/.test(k);
            }).join(' ');
            kolom.push({
                data: nomorUrut ? null : th.getAttribute('data-kolom'),
                orderable: !nomorUrut && th.getAttribute('data-urut') !== 'tidak',
                className: nomorUrut ? 'text-end text-gray-500' : kelas,
                render: function (nilai, jenis, baris, meta) {
                    if (nomorUrut) {
                        return jenis === 'display' ? String(meta.settings._iDisplayStart + meta.row + 1) : null;
                    }
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

        var gambarKosong = tabel.getAttribute('data-kosong-gambar');
        var htmlKosong = gambarKosong
            ? '<span class="flex flex-col items-center gap-3 py-4"><img src="' + escape(gambarKosong) + '" alt="" class="h-32 w-auto" loading="lazy">'
                + '<span class="max-w-md text-base font-semibold text-gray-700">' + escape(pesanKosong) + '</span></span>'
            : pesanKosong;
        var bahasa = Object.assign({}, BAHASA, { emptyTable: htmlKosong, zeroRecords: pesanKosongSaring });
        var dt;
        var bilahMenyala = false;

        var kelasMemuat = 'opacity-60';

        function mulaiMemuat() {
            tabel.classList.add(kelasMemuat);
            if (!bilahMenyala && window.Kemajuan) {
                window.Kemajuan.mulai();
                bilahMenyala = true;
            }
        }

        function selesaiMemuat() {
            tabel.classList.remove(kelasMemuat);
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
        dt.on('xhr.dt', function () {
            selesaiMemuat();
            tabel.removeAttribute('aria-busy');
        });

        // DataTables 2 menamai peristiwa galatnya `dt-error`.
        dt.on('dt-error', function () {
            selesaiMemuat();
            tabel.removeAttribute('aria-busy');
            var tubuh = tabel.querySelector('tbody');
            if (tubuh) {
                tubuh.innerHTML = '<tr><td colspan="' + kolom.length + '" class="px-4 py-6 font-semibold text-red-700" data-tabel-galat>'
                    + escape(PESAN_GALAT) + '</td></tr>';
            }
        });

        dt.on('draw.dt', function () {
            perbaruiExcel(tautanExcel, formulir, dt, kolom);
            tandaiUrutan(tabel);
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

        // Data pertama masih di jalan (ajax selalu tak serempak): ganti baris
        // "Memuat data…" DataTables dengan kerangka setinggi beberapa baris.
        isiKerangka(tabel, kolom.length, Math.min(panjang, 8));

        if (tabel.id) {
            daftarTabel['#' + tabel.id] = dt;
        }
    }

    // ---------- F10-04: tabel sisi klien ----------
    var daftarKlien = [];

    /** Nilai isian di baris yang sedang tidak tampil (halaman lain), untuk ikut dikirim. */
    function isianTersembunyi(dt, formulir) {
        var hasil = [];
        dt.rows().nodes().each(function (tr) {
            if (formulir.contains(tr)) {
                return;
            }
            tr.querySelectorAll('input, select, textarea').forEach(function (isian) {
                if (!isian.name || isian.disabled) {
                    return;
                }
                var jenis = (isian.type || '').toLowerCase();
                if (jenis === 'button' || jenis === 'submit' || jenis === 'reset' || jenis === 'file' || jenis === 'image') {
                    return;
                }
                if ((jenis === 'checkbox' || jenis === 'radio') && !isian.checked) {
                    return;
                }
                if (isian.tagName === 'SELECT' && isian.multiple) {
                    Array.prototype.forEach.call(isian.selectedOptions, function (o) {
                        hasil.push([isian.name, o.value]);
                    });
                    return;
                }
                hasil.push([isian.name, jenis === 'checkbox' || jenis === 'radio' ? (isian.value || 'on') : isian.value]);
            });
        });
        return hasil;
    }

    function buatKlien(tabel) {
        if (tabel.getAttribute('data-tabel-siap') === 'ya' || !tabel.tBodies.length) {
            return;
        }
        var panjang = parseInt(tabel.getAttribute('data-panjang'), 10) || 25;
        if (tabel.tBodies[0].rows.length <= panjang) {
            return;
        }
        tabel.setAttribute('data-tabel-siap', 'ya');

        // Formulir induk dicari sebelum DataTables membungkus tabel.
        var formulir = tabel.closest('form');

        // Kelas asli sel dan baris disimpan: GAYA_TW untuk tabel sisi server
        // (jarak sel, warna kepala) tidak boleh mengubah tampilan tabel ini.
        var kelasAsli = [];
        tabel.querySelectorAll('thead th, tbody tr, tbody td, tbody th, tfoot td, tfoot th').forEach(function (el) {
            kelasAsli.push([el, el.className]);
        });
        var kelasTabel = tabel.className;

        DataTable.ext.errMode = 'none';
        var dt = new DataTable(tabel, {
            pageLength: panjang,
            lengthMenu: [10, 25, 50, 100],
            ordering: false,
            autoWidth: false,
            language: BAHASA,
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: { paging: { firstLast: false, buttons: 5 } }
            }
        });

        kelasAsli.forEach(function (pasangan) {
            pasangan[0].className = pasangan[1];
        });
        tabel.className = kelasTabel + ' dataTable';

        // Tabel ini sudah berada di dalam kotak bergaris; bingkai kedua dari
        // GAYA_TW.layout.tableCell tidak diperlukan.
        var sel = tabel.parentElement;
        if (sel && sel.classList.contains('dt-layout-cell')) {
            sel.className = 'dt-layout-cell dt-layout-full relative block! w-full min-w-0 overflow-x-auto';
        }
        var wadah = tabel.closest('.dt-container');
        if (wadah && wadah.parentElement && wadah.parentElement.classList.contains('overflow-x-auto')) {
            // Baris cari dan halaman diberi jarak dari tepi kotak pembungkus.
            wadah.classList.add('py-3', '[&>.dt-layout-row:not(.dt-layout-table)]:px-3');
        }

        if (formulir) {
            formulir.addEventListener('formdata', function (e) {
                isianTersembunyi(dt, formulir).forEach(function (pasangan) {
                    e.formData.append(pasangan[0], pasangan[1]);
                });
            });
        }

        daftarKlien.push({ tabel: tabel, dt: dt });
        if (tabel.id) {
            daftarTabel['#' + tabel.id] = dt;
        }
    }

    /** Semua baris <tr> tabel klien, termasuk yang di halaman lain; null bila bukan tabel klien. */
    function semuaBaris(tabel) {
        for (var i = 0; i < daftarKlien.length; i++) {
            if (daftarKlien[i].tabel === tabel) {
                return daftarKlien[i].dt.rows().nodes().toArray();
            }
        }
        return null;
    }

    function mulai() {
        document.querySelectorAll('table[data-tabel-admin]').forEach(buat);
        document.querySelectorAll('table[data-tabel-klien]').forEach(buatKlien);
    }

    window.TabelAdmin = {
        bentuk: bentuk,
        escape: escape,
        /** Objek DataTable untuk tabel dengan id tertentu, contoh TabelAdmin.tabel('#tabel-pendaftaran'). */
        tabel: function (pemilih) {
            return daftarTabel[pemilih] || null;
        },
        /** Buat tabel yang ditambahkan ke halaman setelah halaman dimuat. */
        buat: buat,
        /** Buat tabel sisi klien (F10-04) yang ditambahkan setelah halaman dimuat. */
        buatKlien: buatKlien,
        /** Semua <tr> tabel sisi klien, termasuk halaman lain (F10-04); null bila bukan tabel klien. */
        semuaBaris: semuaBaris
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
