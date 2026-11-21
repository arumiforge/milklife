/**
 * Langkah 4: kiriman ke server, pemeriksaan server, dan rincian biaya.
 *
 * Bagian dari wizard pendaftaran (`pendaftaran.js`, dipecah di F7-30). Semua
 * bagian dimuat berurutan oleh `app/Views/pendaftaran.php`; keadaan bersama
 * dan fungsi antarbagian ada di `window.Pendaftaran` (`dasar.js`).
 */
(function (W) {
    'use strict';

    if (!W) {
        return;
    }

    // Dari bagian yang dimuat lebih dulu.
    var akar = W.akar;
    var DATA = W.DATA;
    var NOMOR = W.NOMOR;
    var $ = W.$;
    var ambil = W.ambil;
    var buat = W.buat;
    var rupiah = W.rupiah;
    var pemeriksaLangkah = W.pemeriksaLangkah;
    var penyiapLangkah = W.penyiapLangkah;
    var tampilkanGalat = W.tampilkanGalat;
    var tampilkanLangkah = W.tampilkanLangkah;
    var jenjangSekolah = W.jenjangSekolah;
    var kabupatenSekolah = W.kabupatenSekolah;
    var namaUsulan = W.namaUsulan;
    var npsnSekolah = W.npsnSekolah;
    var setelSekolah = W.setelSekolah;
    var diturunkan = W.diturunkan;
    var labelReguLengkap = W.labelReguLengkap;
    var reguLama = W.reguLama;
    var keadaanTim = W.keadaanTim;
    var tampilkanDaftarAtlet = W.tampilkanDaftarAtlet;

    // ------------------------------------------------------------------
    // Kiriman ke server (langkah 4)
    // ------------------------------------------------------------------

    var csrfKepala = ($('meta[name="csrf-kepala"]') || {}).content || 'X-CSRF-TOKEN';
    var csrfToken = ($('meta[name="csrf-token"]') || {}).content || '';

    function kiriman() {
        return {
            sekolah: {
                jenjang: jenjangSekolah(),
                kabupaten: kabupatenSekolah(),
                npsn: npsnSekolah(),
                nama: W.draf.sekolah.nama,
                nama_usulan: namaUsulan()
            },
            penanggung_jawab: W.draf.penanggungJawab.map(function (o) {
                return { nama: o.nama, nomor_wa: o.nomor_wa, foto: o.foto };
            }),
            atlet: W.draf.atlet.map(function (a) {
                var regu = {};
                a.nomor.forEach(function (id) {
                    if (NOMOR[id] && NOMOR[id].jenis === 'beregu' && a.regu && a.regu[id]) {
                        regu[id] = a.regu[id];
                    }
                });
                return { nama: a.nama, nisn: a.nisn, tanggal_lahir: a.tanggal_lahir, jenis_kelamin: a.jenis_kelamin, kelompok_umur_id: a.kelompok_umur_id || null, nomor: a.nomor, regu: regu };
            }),
            konfirmasi_susulan: konfirmasiTerkirim(),
            turun_cadangan: turunTerkirim()
        };
    }

    /**
     * Regu susulan ("nomor:regu") yang sudah dicentang (K57). Hanya regu
     * yang memang susulan menurut data sekolah saat ini.
     */
    function konfirmasiTerkirim() {
        var tim = keadaanTim();
        return Object.keys(tim).filter(function (kunci) { return tim[kunci].dikonfirmasi; });
    }

    /**
     * Inti lama ("nomor:id atlet") yang menjadi cadangan (K105), hanya di
     * regu susulan yang ditambah kiriman ini.
     */
    function turunTerkirim() {
        var tim = keadaanTim();
        var hasil = [];
        Object.keys(tim).forEach(function (kunci) {
            var t = tim[kunci];
            t.lamaAnggota.forEach(function (a) {
                if (a.peran === 'inti' && diturunkan(t.nomor.id, a.id)) {
                    hasil.push(t.nomor.id + ':' + a.id);
                }
            });
        });
        return hasil;
    }

    /**
     * POST JSON dengan token CSRF. Hasil: `{kode, isi}`; `isi` null bila
     * jawaban bukan JSON (misalnya sesi halaman habis).
     */
    function kirim(alamat, tambahan) {
        var kepala = { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
        kepala[csrfKepala] = csrfToken;

        return ambil(alamat, {
            method: 'POST',
            headers: kepala,
            credentials: 'same-origin',
            body: JSON.stringify(Object.assign(kiriman(), tambahan || {}))
        }).then(function (jawaban) {
            return jawaban.json().then(function (isi) {
                if (isi && isi.csrf) {
                    csrfToken = isi.csrf;
                }
                return { kode: jawaban.status, isi: isi };
            }, function () {
                return { kode: jawaban.status, isi: null };
            });
        }, function () {
            return { kode: 0, isi: null };
        });
    }

    function pesanGagalKirim(hasil) {
        if (hasil.kode === 0) {
            return 'Belum tersambung ke server. Periksa sambungan internet, lalu coba lagi. Isian tetap tersimpan.';
        }
        if (!hasil.isi) {
            return 'Halaman ini sudah terlalu lama terbuka. Muat ulang halaman (isian tetap tersimpan), lalu coba lagi.';
        }
        return hasil.isi.pesan || 'Terjadi kendala. Coba lagi beberapa saat lagi.';
    }

    /**
     * Galat dari server: buka langkah pertama yang salah dan tandai isiannya.
     */
    function tampilkanGalatServer(galat) {
        var langkah = Math.min.apply(null, galat.map(function (g) { return g.langkah; }));
        langkah = Math.max(1, Math.min(langkah, 5));
        var galatLangkah = galat.filter(function (g) { return g.langkah === langkah; });
        tampilkanLangkah(langkah, false);
        tampilkanGalat(langkah, galatLangkah);

        // Server menemukan tim susulan yang belum diketahui wizard (misalnya sekolah
        // yang sama baru saja mendaftar lagi): perbarui data sekolah supaya kotak
        // konfirmasinya tampil.
        var basi = langkah === 3 && npsnSekolah() !== '' && galatLangkah.some(function (g) {
            var bagian = String(g.jalur).split('.');
            var kunci = String(bagian[1] || '').split(':');
            return (bagian[0] === 'konfirmasi' && reguLama(Number(kunci[0]), kunci[1]) === null) || bagian[0] === 'turun';
        });
        if (basi) {
            perbaruiSusulan().then(function (berhasil) {
                if (berhasil && W.draf.langkah === 3) {
                    tampilkanDaftarAtlet();
                    tampilkanGalat(3, galatLangkah);
                }
            });
        }
    }

    /**
     * Ambil ulang data tim dan kuota sekolah (NPSN yang sudah dicari).
     */
    function perbaruiSusulan() {
        var npsn = W.draf.sekolah.dicari;
        return ambil(DATA.alamat.sekolah + '?npsn=' + encodeURIComponent(npsn), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (jawaban) {
            return jawaban.json();
        }).then(function (isi) {
            if (isi && isi.susulan && W.draf.sekolah.dicari === npsn) {
                setelSekolah({ susulan: isi.susulan });
                return true;
            }
            return false;
        }).catch(function () {
            return false;
        });
    }

    function periksaServer() {
        return kirim(DATA.alamat.periksa).then(function (hasil) {
            if (hasil.kode === 200 && hasil.isi && hasil.isi.status === 'sah') {
                W.rincian = hasil.isi.rincian;
                return [];
            }
            if (hasil.kode === 422 && hasil.isi && Array.isArray(hasil.isi.galat)) {
                tampilkanGalatServer(hasil.isi.galat);
                return false;
            }
            return [{ jalur: 'periksa', pesan: pesanGagalKirim(hasil) }];
        });
    }

    var pemeriksaLokal3 = pemeriksaLangkah[3];
    pemeriksaLangkah[3] = function () {
        var galat = pemeriksaLokal3();
        if (galat.length > 0) {
            return galat;
        }
        var tombol = $('#langkah-3 [data-lanjut]');
        tombol.disabled = true;
        return periksaServer().then(function (hasil) {
            tombol.disabled = false;
            return hasil;
        });
    };

    function tabel(judul, kolom, baris, subtotalLabel, subtotal) {
        var wadah = buat('div', W.KELAS.kartu + ' tabel-biaya');
        wadah.appendChild(buat('h3', 'mb-2 font-bold text-primary-900', judul));
        var gulir = buat('div', 'overflow-x-auto');
        // Di ponsel tiap baris menjadi kartu kecil berlabel kolom (`data-label`).
        var t = buat('table', 'tabel-tumpuk w-full text-left text-sm text-gray-800 [&_td]:px-2 [&_td]:py-1.5 [&_th]:px-2 [&_th]:py-1.5 max-sm:[&_td]:px-0 max-sm:[&_th]:px-0');
        var kepala = buat('thead', 'border-b border-gray-200 text-xs uppercase text-gray-500 max-sm:sr-only');
        var tr = buat('tr');
        kolom.forEach(function (k, i) {
            var th = buat('th', 'font-semibold' + (i === kolom.length - 1 ? ' text-end' : ''), k);
            th.scope = 'col';
            tr.appendChild(th);
        });
        kepala.appendChild(tr);
        t.appendChild(kepala);
        var badan = buat('tbody', 'divide-y divide-gray-100');
        baris.forEach(function (sel) {
            var r = buat('tr', 'max-sm:block max-sm:py-2');
            sel.forEach(function (isi, i) {
                var td = buat('td', 'max-sm:flex max-sm:justify-between max-sm:gap-4 max-sm:py-0.5! max-sm:text-end max-sm:before:flex-none max-sm:before:text-start max-sm:before:text-gray-500 max-sm:before:content-[attr(data-label)]'
                    + (i === sel.length - 1 ? ' text-end sm:whitespace-nowrap' : ''), isi);
                td.setAttribute('data-label', kolom[i]);
                r.appendChild(td);
            });
            badan.appendChild(r);
        });
        t.appendChild(badan);
        var kaki = buat('tfoot', 'border-t border-gray-200');
        var rk = buat('tr', 'max-sm:flex max-sm:justify-between max-sm:gap-4');
        var label = buat('th', 'font-semibold', subtotalLabel);
        label.colSpan = kolom.length - 1;
        label.scope = 'row';
        rk.appendChild(label);
        rk.appendChild(buat('td', 'whitespace-nowrap text-end font-bold', rupiah(subtotal)));
        kaki.appendChild(rk);
        t.appendChild(kaki);
        gulir.appendChild(t);
        wadah.appendChild(gulir);
        return wadah;
    }

    function tampilkanRincian() {
        var wadah = $('#rincian-biaya');
        wadah.textContent = '';

        if (!W.rincian) {
            wadah.appendChild(buat('p', 'text-gray-500', 'Menghitung biaya…'));
            return;
        }

        var s = W.rincian.sekolah;
        var sekolah = buat('div', W.KELAS.kartu + ' ringkasan-sekolah');
        sekolah.appendChild(buat('strong', 'block text-lg text-primary-900', s.nama));
        if (s.nama_resmi) {
            sekolah.appendChild(buat('span', 'block text-sm text-gray-500', 'Nama usulan, menunggu persetujuan panitia. Nama di data sekolah: ' + s.nama_resmi + '.'));
        }
        sekolah.appendChild(buat('span', 'block text-sm text-gray-500', [s.npsn ? 'NPSN ' + s.npsn : 'Tanpa NPSN', s.jenjang_label, s.kabupaten].filter(Boolean).join(' · ')
            + ' · ' + W.draf.penanggungJawab.length + ' ofisial · ' + W.draf.atlet.length + ' atlet'));
        wadah.appendChild(sekolah);

        if (W.rincian.individu.length > 0) {
            wadah.appendChild(tabel('Individu', ['No', 'Nama', 'NISN', 'Kelompok Umur', 'Nomor Lomba', 'Biaya'],
                W.rincian.individu.map(function (b, i) { return [String(i + 1), b.nama, b.nisn, b.kelompok_umur, b.nomor, rupiah(b.biaya)]; }),
                'Subtotal individu', W.rincian.subtotal_individu));
        }

        if (W.rincian.beregu.length > 0) {
            wadah.appendChild(tabel('Beregu', ['No', 'Nomor Lomba Beregu', 'Biaya'],
                W.rincian.beregu.map(function (b, i) {
                    return [String(i + 1), (b.regu_tunggal ? b.nomor : b.nomor + ' ' + labelReguLengkap(b.regu)) + ' (' + b.kelompok_umur + ', ' + b.anggota + ' atlet: ' + b.inti + ' inti, ' + b.cadangan + ' cadangan'
                        + (b.susulan ? '; tambahan anggota regu yang sudah terdaftar' : '')
                        + (b.turun ? '; ' + b.turun + ' inti lama menjadi cadangan' : '') + ')', b.susulan ? 'Rp 0' : rupiah(b.biaya)];
                }),
                'Subtotal beregu', W.rincian.subtotal_beregu));
        }

        var total = buat('div', 'kartu-isian total-biaya mb-4 rounded-2xl border border-langit-300 bg-primary-100 p-4 sm:p-7');
        var dl = buat('dl');
        [['Subtotal individu', W.rincian.subtotal_individu], ['Subtotal beregu', W.rincian.subtotal_beregu], ['Total pembayaran', W.rincian.total]].forEach(function (b, i) {
            var baris = buat('div', 'total-baris flex justify-between gap-4 py-1 [&_dd]:whitespace-nowrap [&_dd]:font-semibold'
                + (i === 2 ? ' total-akhir mt-1.5 border-t border-langit-300 pt-2.5 text-lg text-primary-900 [&_dd]:font-extrabold! [&_dt]:font-extrabold' : ''));
            baris.appendChild(buat('dt', null, b[0]));
            baris.appendChild(buat('dd', null, rupiah(b[1])));
            dl.appendChild(baris);
        });
        total.appendChild(dl);
        wadah.appendChild(total);
    }

    penyiapLangkah[4] = function () {
        if (W.rincian) {
            tampilkanRincian();
            return;
        }
        tampilkanRincian();
        periksaServer().then(function (galat) {
            if (galat === false) {
                return;
            }
            if (galat.length > 0) {
                tampilkanGalat(4, galat);
            }
            tampilkanRincian();
        });
    };

    // Isian yang berubah membuat rincian lama tidak berlaku.
    // Memilih gambar bukti transfer tidak mengubah isian, jadi rincian tetap berlaku.
    function rincianBasi(e) {
        if (e.target.id !== 'bayar-berkas') {
            W.rincian = null;
        }
    }
    akar.addEventListener('input', rincianBasi);
    akar.addEventListener('change', rincianBasi);

    pemeriksaLangkah[4] = function () {
        return W.rincian ? [] : periksaServer();
    };

    // Dipakai bagian lain.
    W.kirim = kirim;
    W.perbaruiSusulan = perbaruiSusulan;
    W.periksaServer = periksaServer;
    W.pesanGagalKirim = pesanGagalKirim;
    W.tampilkanGalatServer = tampilkanGalatServer;
}(window.Pendaftaran));
