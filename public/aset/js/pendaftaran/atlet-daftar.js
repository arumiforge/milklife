/**
 * Langkah 3: daftar sementara atlet, kartu regu, dan pemeriksaan langkah 3.
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
    var DATA = W.DATA;
    var NOMOR = W.NOMOR;
    var $ = W.$;
    var buat = W.buat;
    var ikon = W.ikon;
    var rupiah = W.rupiah;
    var yakin = W.yakin;
    var simpanDraf = W.simpanDraf;
    var pemeriksaLangkah = W.pemeriksaLangkah;
    var penyiapLangkah = W.penyiapLangkah;
    var jenjangSekolah = W.jenjangSekolah;
    var susulan = W.susulan;
    var kuotaNomor = W.kuotaNomor;
    var maksimalIndividu = W.maksimalIndividu;
    var nomorCocokJk = W.nomorCocokJk;
    var tentukanKu = W.tentukanKu;
    var AWALAN_REGU = W.AWALAN_REGU;
    var diturunkan = W.diturunkan;
    var idAman = W.idAman;
    var labelReguLengkap = W.labelReguLengkap;
    var namaRegu = W.namaRegu;
    var pilihanRegu = W.pilihanRegu;
    var REGU_BEBAS = W.REGU_BEBAS;
    var reguBoleh = W.reguBoleh;
    var reguLama = W.reguLama;
    var reguLamaNomor = W.reguLamaNomor;
    var reguTunggal = W.reguTunggal;
    var isiPilihanNomor = W.isiPilihanNomor;
    var kosongkanFormulirAtlet = W.kosongkanFormulirAtlet;
    var muatAtlet = W.muatAtlet;

    /**
     * Masalah tiap atlet dengan jenjang sekolah dan data induk saat ini
     * (misalnya jenjang diganti setelah atlet ditambahkan).
     */
    function masalahAtlet(atlet) {
        var hasil = tentukanKu(Number(String(atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah(), atlet.kelompok_umur_id);
        if (!hasil.ku) {
            return hasil.pilihan.length > 1 ? hasil.galat + ' Tekan Ubah, lalu pilih kelompok umurnya.' : hasil.galat;
        }
        var individu = 0;
        for (var i = 0; i < atlet.nomor.length; i++) {
            var nomor = NOMOR[atlet.nomor[i]];
            if (!nomor || nomor.kelompok_umur_id !== hasil.ku.id || !nomorCocokJk(nomor, atlet.jenis_kelamin)) {
                return 'Ada nomor lomba yang tidak sesuai lagi. Tekan Ubah dan pilih ulang nomornya.';
            }
            if (nomor.jenis === 'individu') {
                individu++;
            } else if (!pilihanRegu(atlet, nomor.id) || !reguBoleh(nomor, pilihanRegu(atlet, nomor.id).regu)) {
                return 'Belum memilih regu untuk ' + nomor.nama_lengkap + '. Tekan Ubah, lalu pilih regu dan Inti atau Cadangan.';
            }
        }
        if (atlet.nomor.length === 0) {
            return 'Belum ikut nomor lomba apa pun. Tekan Ubah dan pilih nomornya.';
        }
        if (individu > maksimalIndividu) {
            return 'Ikut ' + individu + ' nomor individu. Batasnya ' + maksimalIndividu + ' nomor. Tekan Ubah dan kurangi nomornya.';
        }
        return '';
    }

    /**
     * Keadaan tiap regu beregu (sama dengan `KelengkapanTim`, K105). Kunci
     * hasilnya "nomor:regu", berurutan menurut nomor lalu regu.
     */
    function keadaanTim() {
        var tim = {};

        W.draf.atlet.forEach(function (atlet, indeks) {
            atlet.nomor.forEach(function (id) {
                var nomor = NOMOR[id];
                var p = nomor && nomor.jenis === 'beregu' ? pilihanRegu(atlet, id) : null;
                if (p && reguBoleh(nomor, p.regu)) {
                    var kunci = id + ':' + p.regu;
                    tim[kunci] = tim[kunci] || { kunci: kunci, nomor: nomor, regu: p.regu, anggota: [] };
                    tim[kunci].anggota.push({ atlet: atlet, indeks: indeks, peran: p.peran });
                }
            });
        });

        Object.keys(tim).forEach(function (kunci) {
            var t = tim[kunci];
            var n = t.nomor;
            var nama = namaRegu(n, t.regu);
            var lama = reguLama(n.id, t.regu);
            var intiLama = 0;
            var semua = [];

            t.susulan = lama !== null;
            t.lamaAnggota = lama || [];
            t.lama = t.lamaAnggota.length;
            t.dikonfirmasi = t.susulan && W.draf.konfirmasiSusulan.indexOf(kunci) !== -1;
            t.pesan = [];

            t.lamaAnggota.forEach(function (a) {
                var peran = a.peran === 'inti' && !diturunkan(n.id, a.id) ? 'inti' : 'cadangan';
                if (peran === 'inti') {
                    intiLama++;
                }
                semua.push({ jk: a.jenis_kelamin, peran: peran });
            });
            t.anggota.forEach(function (a) { semua.push({ jk: a.atlet.jenis_kelamin, peran: a.peran }); });

            var jumlah = semua.length;
            var inti = semua.filter(function (a) { return a.peran === 'inti'; });
            var keterangan = t.susulan && intiLama > 0 ? ' (' + intiLama + ' di antaranya dari pendaftaran sebelumnya)' : '';
            t.inti = t.anggota.filter(function (a) { return a.peran === 'inti'; }).length;

            if (jumlah > n.batas_anggota) {
                if (t.susulan) {
                    var sisa = Math.max(0, n.batas_anggota - t.lama);
                    t.pesan.push(nama + ' sudah punya ' + t.lama + ' atlet dari pendaftaran sebelumnya, dan paling banyak ' + n.batas_anggota + ' atlet. '
                        + (sisa === 0 ? 'Regu ini sudah penuh. Pilih regu lain untuk atlet tambahan.' : 'Atlet tambahan paling banyak ' + sisa + '. Pindahkan ' + (jumlah - n.batas_anggota) + ' atlet ke regu lain.'));
                } else {
                    t.pesan.push(nama + ' punya ' + jumlah + ' atlet. Batasnya ' + n.batas_anggota + ' atlet. Pindahkan ' + (jumlah - n.batas_anggota) + ' atlet ke regu lain atau hapus dari nomor ini.');
                }
            }
            if (inti.length > n.jumlah_inti) {
                t.pesan.push(nama + ' punya ' + inti.length + ' atlet inti' + keterangan + '. Satu regu harus tepat ' + n.jumlah_inti + ' atlet inti. Jadikan '
                    + (inti.length - n.jumlah_inti) + ' atlet cadangan' + (keterangan ? ', atau centang inti lama yang menjadi cadangan.' : '.'));
            } else if (inti.length < n.jumlah_inti) {
                t.pesan.push(jumlah < n.jumlah_inti
                    ? nama + ' baru punya ' + jumlah + ' atlet. Tambah ' + (n.jumlah_inti - jumlah) + ' atlet lagi. Satu regu harus tepat ' + n.jumlah_inti + ' atlet inti.'
                    : nama + ' baru punya ' + inti.length + ' atlet inti' + keterangan + '. Tandai ' + (n.jumlah_inti - inti.length) + ' atlet lagi sebagai Inti. Satu regu harus tepat ' + n.jumlah_inti + ' atlet inti.');
            } else if (n.kategori === 'mix') {
                var putra = inti.filter(function (a) { return a.jk === 'L'; }).length;
                var putri = inti.length - putra;
                if (putra < n.jumlah_putra || putri < n.jumlah_putri) {
                    t.pesan.push(nama + ': atlet inti minimal ' + n.jumlah_putra + ' putra dan ' + n.jumlah_putri + ' putri. Sekarang ' + putra + ' putra dan ' + putri + ' putri.');
                }
            }
        });

        // Mode Bebas (K106): banyaknya regu per nomor, termasuk regu lama, dibatasi nomor lombanya.
        if (REGU_BEBAS) {
            var perNomor = {};
            Object.keys(tim).forEach(function (kunci) {
                var id = tim[kunci].nomor.id;
                perNomor[id] = perNomor[id] || [];
                perNomor[id].push(tim[kunci]);
            });
            Object.keys(perNomor).forEach(function (id) {
                var n = NOMOR[id];
                var lama = reguLamaNomor(Number(id));
                var semua = lama.slice();
                perNomor[id].forEach(function (t) {
                    if (semua.indexOf(t.regu) === -1) {
                        semua.push(t.regu);
                    }
                });
                if (n.regu_maksimal && semua.length > n.regu_maksimal) {
                    perNomor[id].forEach(function (t) {
                        if (lama.indexOf(t.regu) === -1) {
                            t.pesan.push(namaRegu(n, t.regu) + ': satu sekolah paling banyak ' + n.regu_maksimal + ' regu di nomor ini, sekarang ' + semua.length
                                + ' regu. Masukkan atletnya ke regu yang sudah ada.');
                        }
                    });
                }
            });
        }

        var urut = {};
        Object.keys(tim).sort(function (a, b) {
            var x = a.split(':');
            var y = b.split(':');
            return urutanNomor(Number(x[0])) - urutanNomor(Number(y[0])) || (x[1] < y[1] ? -1 : x[1] > y[1] ? 1 : 0);
        }).forEach(function (k) { urut[k] = tim[k]; });
        return urut;
    }

    function urutanNomor(id) {
        for (var i = 0; i < DATA.nomor_lomba.length; i++) {
            if (DATA.nomor_lomba[i].id === id) {
                return i;
            }
        }
        return DATA.nomor_lomba.length;
    }

    /**
     * Kuota atlet per sekolah per nomor individu (K51).
     */
    function masalahKuota() {
        var pesan = {};
        if (!kuotaNomor) {
            return pesan;
        }
        var lama = susulan().individu || {};
        var jumlah = {};
        W.draf.atlet.forEach(function (atlet) {
            atlet.nomor.forEach(function (id) {
                if (NOMOR[id] && NOMOR[id].jenis === 'individu') {
                    jumlah[id] = (jumlah[id] || 0) + 1;
                }
            });
        });
        Object.keys(jumlah).forEach(function (id) {
            var sebelum = Number(lama[id] || 0);
            if (sebelum + jumlah[id] > kuotaNomor) {
                pesan[id] = NOMOR[id].nama_lengkap + ': paling banyak ' + kuotaNomor + ' atlet per sekolah'
                    + (sebelum > 0 ? ', dan sekolah ini sudah mendaftarkan ' + sebelum + ' atlet sebelumnya' : '')
                    + '. Sekarang ' + (sebelum + jumlah[id]) + '. Hapus nomor ini dari ' + (sebelum + jumlah[id] - kuotaNomor) + ' atlet.';
            }
        });
        return pesan;
    }

    /** Tanda Inti atau Cadangan. */
    function lencanaPeran(peran) {
        return buat('span', 'lencana-peran lencana-' + peran + ' inline-block rounded-full px-2 py-px align-[0.1em] text-[0.7rem] font-semibold uppercase leading-normal tracking-wide '
            + (peran === 'inti' ? 'bg-primary-700 text-white' : 'border border-dashed border-gray-500 bg-white text-gray-600'), peran === 'inti' ? 'Inti' : 'Cadangan');
    }

    /**
     * Baris atlet nomor individu di daftar sementara. `hapusNomor`: lihat
     * `tombolBaris()`.
     */
    function barisAtlet(atlet, indeks, jalur, keterangan, hapusNomor) {
        var li = buat('li', 'baris-atlet flex flex-wrap items-start justify-between gap-2 rounded-lg border border-gray-200 bg-white p-3');
        li.setAttribute('data-baris-jalur', jalur);
        var isi = buat('div', 'baris-atlet-isi min-w-0 flex-[1_1_14rem] break-words');
        var nama = buat('strong', 'baris-atlet-nama block text-gray-900', atlet.nama);
        isi.appendChild(nama);
        var ku = tentukanKu(Number(String(atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah(), atlet.kelompok_umur_id).ku;
        isi.appendChild(buat('span', 'baris-atlet-keterangan block text-sm text-gray-500', ['NISN ' + atlet.nisn, ku ? ku.nama : null, atlet.jenis_kelamin === 'L' ? 'Putra' : 'Putri'].filter(Boolean).join(' · ')));
        if (keterangan) {
            isi.appendChild(buat('span', 'baris-atlet-nomor block text-sm text-primary-900', keterangan));
        }
        var masalah = masalahAtlet(atlet);
        if (masalah) {
            isi.appendChild(buat('p', W.KELAS.galatBaris, masalah));
        }
        li.appendChild(isi);

        li.appendChild(tombolBaris(atlet, hapusNomor, false));
        return li;
    }

    /** Teks tombol; `ringkas`: hanya tampil di layar lebar, di ponsel tombolnya berikon saja. */
    function teksTombol(teks, ringkas) {
        return ringkas ? buat('span', 'hidden sm:inline', teks) : document.createTextNode(teks);
    }

    /**
     * Tombol Ubah dan Hapus satu atlet. `hapusNomor`: ID nomor lomba yang
     * dihapus; nomor lain atlet itu tetap. Bila atlet tidak punya nomor lain
     * (atau `hapusNomor` kosong), atletnya ikut dihapus.
     */
    function tombolBaris(atlet, hapusNomor, ringkas) {
        var tombol = buat('div', 'baris-atlet-tombol flex flex-none gap-1.5');
        var ubah = buat('button', W.KELAS.garisKecil + ' min-w-9');
        ubah.type = 'button';
        ubah.appendChild(ikon('pencil'));
        ubah.appendChild(teksTombol(' Ubah', ringkas));
        ubah.setAttribute('aria-label', 'Ubah ' + atlet.nama);
        ubah.title = 'Ubah';
        ubah.addEventListener('click', function () { muatAtlet(atlet); });
        var hapus = buat('button', W.KELAS.hapusKecil + ' min-w-9');
        hapus.type = 'button';
        hapus.appendChild(ikon('trash-2'));
        hapus.appendChild(teksTombol(' Hapus', ringkas));
        var namaNomor = (hapusNomor || []).map(function (id) { return NOMOR[id] ? NOMOR[id].nama_lengkap : ''; }).join(', ');
        var sisa = atlet.nomor.filter(function (id) { return (hapusNomor || []).indexOf(id) === -1; });
        hapus.setAttribute('aria-label', 'Hapus ' + (namaNomor ? namaNomor + ' untuk ' : '') + atlet.nama);
        hapus.title = 'Hapus';
        hapus.addEventListener('click', function () {
            var hanyaNomor = namaNomor && sisa.length > 0;
            yakin(
                hanyaNomor ? 'Hapus nomor lomba?' : 'Hapus atlet?',
                hanyaNomor
                    ? namaNomor + ' untuk ' + atlet.nama + ' akan dihapus. Nomor lomba lainnya tetap.'
                    : atlet.nama + ' akan dihapus dari daftar.' + (namaNomor ? ' Atlet ini tidak punya nomor lomba lain.' : ''),
                hanyaNomor ? 'Hapus nomor' : 'Hapus atlet'
            ).then(function (setuju) {
                if (!setuju) {
                    return;
                }
                if (hanyaNomor) {
                    atlet.nomor = sisa;
                } else {
                    W.draf.atlet.splice(W.draf.atlet.indexOf(atlet), 1);
                }
                // Tombol Hapus tidak memicu event input/change: total lama tidak berlaku lagi.
                W.rincian = null;
                if (W.atletDiubah === atlet) {
                    kosongkanFormulirAtlet();
                }
                simpanDraf();
                tampilkanDaftarAtlet();
            });
        });
        tombol.appendChild(ubah);
        tombol.appendChild(hapus);
        tombol.appendChild(ubah);
        tombol.appendChild(hapus);
        return tombol;
    }

    /**
     * Baris ringkas anggota regu di kartu regu: nomor urut, nama, keterangan,
     * dan tombol. Peran ditandai oleh kelompok Inti/Cadangan tempat baris ini.
     */
    function barisAnggota(a, urut, n) {
        var li = buat('li', 'anggota-regu anggota-' + a.peran + ' flex items-center gap-2.5 border-t border-primary-50 py-1.5 first:border-t-0');
        li.setAttribute('data-baris-jalur', 'atlet.' + a.indeks);
        li.appendChild(buat('span', 'anggota-regu-urut inline-flex size-6.5 flex-none items-center justify-center rounded-full text-xs font-bold '
            + (a.peran === 'inti' ? 'bg-primary-700 text-white' : 'border border-dashed border-gray-500 bg-white text-gray-600'), String(urut)));
        var isi = buat('div', 'anggota-regu-isi min-w-0 flex-auto break-words');
        isi.appendChild(buat('strong', 'anggota-regu-nama me-1.5 block text-gray-900 sm:inline', a.atlet.nama));
        var ku = tentukanKu(Number(String(a.atlet.tanggal_lahir).slice(0, 4)), jenjangSekolah(), a.atlet.kelompok_umur_id).ku;
        isi.appendChild(buat('span', 'anggota-regu-keterangan text-xs text-gray-500', ['NISN ' + a.atlet.nisn, ku ? ku.nama : null, a.atlet.jenis_kelamin === 'L' ? 'Putra' : 'Putri'].filter(Boolean).join(' · ')));
        var masalah = masalahAtlet(a.atlet);
        if (masalah) {
            isi.appendChild(buat('p', W.KELAS.galatBaris, masalah));
        }
        li.appendChild(isi);
        li.appendChild(tombolBaris(a.atlet, [n.id], true));
        return li;
    }

    /** Satu kelompok anggota regu (Inti atau Cadangan) dengan judul dan hitungan. */
    function kelompokPeran(peran, anggota, mulai, n, hitungan, kurang) {
        var wadah = buat('div', 'kelompok-peran kelompok-' + peran + ' [&+&]:mt-1 [&+&]:border-t [&+&]:border-dashed [&+&]:border-gray-200');
        var judul = buat('div', 'kelompok-peran-judul flex items-center gap-2 pt-2 pb-1');
        judul.appendChild(lencanaPeran(peran));
        judul.appendChild(buat('span', 'kelompok-peran-hitungan text-xs ' + (kurang ? 'hitungan-salah font-semibold text-red-800' : 'text-gray-500'), hitungan));
        wadah.appendChild(judul);
        if (anggota.length === 0) {
            wadah.appendChild(buat('p', 'kelompok-peran-kosong mb-1.5 text-sm text-gray-500', peran === 'inti' ? 'Belum ada atlet inti.' : 'Belum ada cadangan. Cadangan boleh tidak ada.'));
            return wadah;
        }
        var ul = buat('ol', 'anggota-regu-daftar');
        anggota.forEach(function (a, i) { ul.appendChild(barisAnggota(a, mulai + i, n)); });
        wadah.appendChild(ul);
        return wadah;
    }

    /**
     * Teks lencana kartu regu.
     */
    function lencanaTim(lencana, t) {
        var perluKonfirmasi = t.susulan && !t.dikonfirmasi;
        lencana.className = 'inline-flex items-center rounded-md px-2.5 py-0.5 text-xs font-semibold ' + (t.pesan.length || perluKonfirmasi ? 'bg-yellow-100 text-yellow-900' : 'bg-green-100 text-green-800');
        lencana.textContent = t.pesan.length ? 'Belum lengkap' : (perluKonfirmasi ? 'Perlu dicentang' : (t.susulan ? 'Tambahan anggota' : 'Lengkap'));
    }

    /**
     * Anggota regu yang sudah terdaftar, dengan kotak "Jadikan cadangan" pada
     * setiap inti lama: atlet susulan boleh menjadi inti dengan menurunkan
     * inti lama (K105).
     */
    function daftarReguLama(n, t) {
        var wadah = buat('div', 'regu-lama mx-3 mt-3 break-words rounded-lg border border-dashed border-gray-300 bg-white px-3 py-2');
        wadah.setAttribute('data-turun-nomor', String(n.id));
        wadah.appendChild(buat('p', 'mb-1 text-sm font-semibold text-gray-900', 'Anggota yang sudah terdaftar di regu ini:'));
        var ul = buat('ul', 'mb-2 space-y-1 text-sm');
        t.lamaAnggota.forEach(function (a) {
            var li = buat('li', 'regu-lama-baris flex flex-wrap items-center gap-x-3 gap-y-1');
            var turun = a.peran === 'inti' && diturunkan(n.id, a.id);
            var namaLama = buat('span', 'regu-lama-nama', a.nama + ' ');
            namaLama.appendChild(lencanaPeran(turun ? 'cadangan' : a.peran));
            if (turun) {
                namaLama.appendChild(buat('span', 'text-sm text-gray-500', ' (sebelumnya inti)'));
            }
            li.appendChild(namaLama);
            if (a.peran === 'inti') {
                var kotak = buat('span', 'inline-flex items-center gap-2');
                var masukan = buat('input', W.KELAS.centang);
                masukan.type = 'checkbox';
                masukan.id = 'turun-' + n.id + '-' + a.id;
                masukan.checked = turun;
                masukan.setAttribute('data-jalur', 'turun.' + n.id);
                var label = buat('label', 'cursor-pointer', 'Jadikan cadangan');
                label.htmlFor = masukan.id;
                masukan.addEventListener('change', function () {
                    var kunci = n.id + ':' + a.id;
                    W.draf.turunCadangan = W.draf.turunCadangan.filter(function (k) { return k !== kunci; });
                    if (masukan.checked) {
                        W.draf.turunCadangan.push(kunci);
                    }
                    W.rincian = null;
                    simpanDraf();
                    tampilkanDaftarAtlet();
                });
                kotak.appendChild(masukan);
                kotak.appendChild(label);
                li.appendChild(kotak);
            }
            ul.appendChild(li);
        });
        wadah.appendChild(ul);
        return wadah;
    }

    /**
     * Kotak centang konfirmasi regu susulan (K57): official menyatakan sendiri
     * bahwa atletnya menjadi tambahan anggota regu sekolah yang sudah
     * terdaftar, tanpa biaya regu.
     */
    function kotakKonfirmasi(n, t, lencana) {
        var kunci = t.kunci;
        var kotak = buat('div', 'konfirmasi-susulan mx-3 mt-3 mb-3 flex flex-wrap items-start gap-x-3 gap-y-1 break-words rounded-lg border border-gray-300 bg-white px-3 py-2.5 has-[.is-invalid]:border-red-500');
        var masukan = buat('input', W.KELAS.centang + ' mt-0.5');
        masukan.type = 'checkbox';
        masukan.id = 'konfirmasi-susulan-' + idAman(kunci);
        masukan.checked = t.dikonfirmasi;
        masukan.setAttribute('data-jalur', 'konfirmasi.' + kunci);
        var label = buat('label', 'min-w-0 flex-1 cursor-pointer text-[0.9375rem]',
            'Saya mengerti ' + (t.anggota.length === 1 ? 'atlet ini' : t.anggota.length + ' atlet ini') + ' ditambahkan ke ' + namaRegu(n, t.regu)
            + ' sekolah yang sudah terdaftar (' + t.lama + ' atlet), tanpa biaya regu');
        label.htmlFor = masukan.id;
        var umpan = buat('div', W.KELAS.umpan + ' basis-full');

        masukan.addEventListener('change', function () {
            W.draf.konfirmasiSusulan = W.draf.konfirmasiSusulan.filter(function (k) { return k !== kunci; });
            if (masukan.checked) {
                W.draf.konfirmasiSusulan.push(kunci);
                masukan.classList.remove('is-invalid');
                umpan.textContent = '';
            }
            t.dikonfirmasi = masukan.checked;
            lencanaTim(lencana, t);
            simpanDraf();
        });

        kotak.appendChild(masukan);
        kotak.appendChild(label);
        kotak.appendChild(umpan);
        return kotak;
    }

    function tampilkanDaftarAtlet() {
        var individu = $('#daftar-individu');
        var beregu = $('#daftar-beregu');
        var kuota = masalahKuota();
        individu.textContent = '';
        beregu.textContent = '';

        $('#jumlah-atlet').textContent = W.draf.atlet.length + ' atlet';
        $('#daftar-kosong').hidden = W.draf.atlet.length > 0;

        var adaIndividu = false;
        W.draf.atlet.forEach(function (atlet, i) {
            var nomor = atlet.nomor.map(function (id) { return NOMOR[id]; }).filter(function (n) { return n && n.jenis === 'individu'; });
            var tanpaNomor = atlet.nomor.length === 0 || atlet.nomor.some(function (id) { return !NOMOR[id]; });
            // Satu baris per nomor individu, supaya Hapus hanya menghapus nomor itu.
            nomor.forEach(function (n) {
                adaIndividu = true;
                var baris = barisAtlet(atlet, i, 'atlet.' + i, n.nama_lengkap, [n.id]);
                if (kuota[n.id]) {
                    baris.firstChild.appendChild(buat('p', W.KELAS.galatBaris, kuota[n.id]));
                }
                individu.appendChild(baris);
            });
            if (nomor.length === 0 && tanpaNomor) {
                adaIndividu = true;
                individu.appendChild(barisAtlet(atlet, i, 'atlet.' + i, ''));
            }
        });
        $('#bagian-individu').hidden = !adaIndividu;

        var tim = keadaanTim();
        var urutan = Object.keys(tim);
        urutan.forEach(function (kunci) {
            var t = tim[kunci];
            var n = t.nomor;
            var kartu = buat('div', 'kartu-tim mb-4 overflow-hidden rounded-xl border border-gray-200 bg-white');
            kartu.setAttribute('data-baris-jalur', 'beregu.' + kunci);
            var kepala = buat('div', 'kartu-tim-kepala flex flex-wrap items-center justify-between gap-2 bg-primary-100 px-3 pt-2.5');
            var judul = buat('div', 'kartu-tim-judul flex min-w-0 flex-wrap items-center gap-1.5 break-words text-primary-900');
            judul.appendChild(buat('strong', 'kartu-tim-nomor', n.nama_lengkap));
            var tunggal = reguTunggal(n.id);
            if (!tunggal) {
                judul.appendChild(buat('span', 'regu-pil whitespace-nowrap rounded-full bg-oranye-600 px-2.5 py-0.5 text-xs font-bold text-white', labelReguLengkap(t.regu)));
            }
            kepala.appendChild(judul);
            var lencana = buat('span');
            lencanaTim(lencana, t);
            kepala.appendChild(lencana);
            kartu.appendChild(kepala);

            var inti = t.anggota.filter(function (a) { return a.peran === 'inti'; });
            var cadangan = t.anggota.filter(function (a) { return a.peran !== 'inti'; });
            var batasCadangan = Math.max(0, n.batas_anggota - n.jumlah_inti);
            var ringkas = buat('div', 'kartu-tim-ringkas flex flex-wrap gap-1 border-b border-gray-200 bg-primary-100 px-3 pt-1.5 pb-2.5');
            [
                t.susulan ? ['', 'tambahan untuk ' + (tunggal ? AWALAN_REGU.toLowerCase() : labelReguLengkap(t.regu)) + ' yang sudah terdaftar (' + t.lama + ' atlet)'] : null,
                ['', t.anggota.length + ' atlet'],
                ['biaya', t.susulan ? 'tanpa biaya regu' : rupiah(n.biaya_beregu)]
            ].filter(Boolean).forEach(function (b) {
                ringkas.appendChild(buat('span', W.kelasKeping(b[0]), b[1]));
            });
            kartu.appendChild(ringkas);
            t.pesan.forEach(function (p) { kartu.appendChild(buat('p', W.KELAS.galatBaris + ' mx-3', p)); });

            var badan = buat('div', 'kartu-tim-badan px-3 pt-1 pb-2');
            badan.appendChild(kelompokPeran('inti', inti, 1, n,
                t.susulan ? inti.length + ' atlet baru' : inti.length + ' dari ' + n.jumlah_inti, !t.susulan && inti.length !== n.jumlah_inti));
            if (cadangan.length > 0 || batasCadangan > 0) {
                badan.appendChild(kelompokPeran('cadangan', cadangan, inti.length + 1, n,
                    t.susulan ? cadangan.length + ' atlet baru' : cadangan.length + ' dari paling banyak ' + batasCadangan, !t.susulan && cadangan.length > batasCadangan));
            }
            kartu.appendChild(badan);
            if (t.susulan) {
                kartu.appendChild(daftarReguLama(n, t));
                kartu.appendChild(kotakKonfirmasi(n, t, lencana));
            }
            beregu.appendChild(kartu);
        });
        $('#bagian-beregu').hidden = urutan.length === 0;
    }

    pemeriksaLangkah[3] = function () {
        var galat = [];

        if (W.atletDiubah) {
            galat.push({ jalur: 'atlet', pesan: 'Ada atlet yang sedang diubah. Tekan "Simpan perubahan" atau "Batal ubah" dulu.' });
        }
        if (W.draf.atlet.length === 0) {
            galat.push({ jalur: 'atlet', pesan: 'Belum ada atlet. Tambahkan minimal satu atlet.' });
        }
        W.draf.atlet.forEach(function (atlet, i) {
            var masalah = masalahAtlet(atlet);
            if (masalah) {
                galat.push({ jalur: 'atlet.' + i, pesan: atlet.nama + ': ' + masalah });
            }
        });
        var tim = keadaanTim();
        Object.keys(tim).forEach(function (kunci) {
            tim[kunci].pesan.forEach(function (p) { galat.push({ jalur: 'beregu.' + kunci, pesan: p }); });
            if (tim[kunci].susulan && !tim[kunci].dikonfirmasi) {
                galat.push({
                    jalur: 'konfirmasi.' + kunci,
                    pesan: namaRegu(tim[kunci].nomor, tim[kunci].regu) + ': sekolah ini sudah punya regu ini, jadi atlet yang dipilih menjadi tambahan anggota tanpa biaya regu. '
                        + 'Centang kotak "Saya mengerti …" pada regu ini, atau pilih regu lain.'
                });
            }
        });
        var kuota = masalahKuota();
        Object.keys(kuota).forEach(function (id) {
            galat.push({ jalur: 'individu.' + id, pesan: kuota[id] });
        });

        return galat;
    };

    penyiapLangkah[3] = function () {
        isiPilihanNomor();
        tampilkanDaftarAtlet();
    };

    // Dipakai bagian lain.
    W.keadaanTim = keadaanTim;
    W.tampilkanDaftarAtlet = tampilkanDaftarAtlet;
}(window.Pendaftaran));
