/**
 * Langkah 3: pilihan regu dan peran untuk nomor beregu (K105–K108).
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
    var $ = W.$;
    var $$ = W.$$;
    var buat = W.buat;
    var ikon = W.ikon;
    var susulan = W.susulan;
    var L3 = W.L3;

    // ------------------------------------------------------------------
    // Regu beregu (K105)
    // ------------------------------------------------------------------

    /** Mode nama regu (K106): `bebas` = official mengetik nama regu; `ketentuan` = dari daftar admin. */
    var REGU_BEBAS = DATA.aturan.regu_mode === 'bebas';
    var LABEL_REGU = Array.isArray(DATA.aturan.regu_label) && DATA.aturan.regu_label.length ? DATA.aturan.regu_label : null;
    var HURUF = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
    /** Kata di depan nama regu dari pengaturan admin (K107), misalnya "Regu" atau "Tim". */
    var AWALAN_REGU = String(DATA.aturan.regu_awalan || 'Regu');

    /** "Regu A" atau "Tim A". */
    function labelReguLengkap(label) {
        return AWALAN_REGU + ' ' + label;
    }

    function escRegex(t) {
        return t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    /** Nama regu yang dirapikan seperti `Regu::rapikan()`: "  regu  merah " → "Merah" (juga "Tim Merah" bila awalannya Tim). */
    function rapikanRegu(teks) {
        var t = String(teks || '').replace(/\s+/g, ' ').trim().replace(/^regu(\s+|$)/i, '').trim();
        if (AWALAN_REGU.toLowerCase() !== 'regu') {
            t = t.replace(new RegExp('^' + escRegex(AWALAN_REGU) + '\\s+', 'i'), '').trim();
        }
        return t.toLowerCase().replace(/(^|[\s\-\/.])(\p{Ll})/gu, function (m, a, b) { return a + b.toUpperCase(); });
    }

    function namaReguSah(t) {
        return t.length > 0 && t.length <= 30 && /^[\p{L}\p{N}][\p{L}\p{N} .\-\/]*$/u.test(t);
    }

    /** Bagian id HTML yang aman untuk kunci "nomor:regu" (nama regu boleh berspasi). */
    function idAman(kunci) {
        return String(kunci).replace(/[^A-Za-z0-9]/g, function (c) { return '_' + c.charCodeAt(0).toString(16); });
    }

    /** Nama regu lama sekolah ini di satu nomor. */
    function reguLamaNomor(nomorId) {
        var tim = (susulan().tim || {})[nomorId];
        return tim && typeof tim === 'object' ? Object.keys(tim) : [];
    }

    /** Nama regu di daftar sementara untuk satu nomor (selain `kecuali`). */
    function reguDraf(nomorId, kecuali) {
        var hasil = [];
        W.draf.atlet.forEach(function (atlet) {
            var p = atlet !== kecuali && atlet.nomor.indexOf(nomorId) !== -1 && atlet.regu ? atlet.regu[nomorId] : null;
            if (p && p.regu && hasil.indexOf(p.regu) === -1) {
                hasil.push(p.regu);
            }
        });
        return hasil;
    }

    /**
     * Nama regu yang boleh dipilih di nomor ini. Ketentuan admin: daftar admin
     * (huruf A, B, C bila tidak ada) sebanyak batas regu, ditambah regu lama.
     * Bebas: usulan dari regu lama dan daftar sementara.
     */
    function labelRegu(nomor) {
        var lama = reguLamaNomor(nomor.id);
        var hasil;
        if (REGU_BEBAS) {
            hasil = lama.slice();
            reguDraf(nomor.id, null).forEach(function (l) {
                if (hasil.indexOf(l) === -1) {
                    hasil.push(l);
                }
            });
            return hasil;
        }
        var daftar = LABEL_REGU || HURUF;
        hasil = daftar.slice(0, nomor.regu_maksimal ? Math.min(Number(nomor.regu_maksimal), daftar.length) : daftar.length);
        lama.forEach(function (l) {
            if (hasil.indexOf(l) === -1) {
                hasil.push(l);
            }
        });
        return hasil;
    }

    /**
     * Pilihan regu di formulir. Huruf otomatis (daftar admin kosong, K107):
     * hanya regu yang sudah dipakai sekolah ini atau daftar sementara, `terpilih`,
     * dan satu regu baru berikutnya, supaya daftar tidak berisi 26 huruf.
     */
    function opsiRegu(nomor, terpilih) {
        var semua = labelRegu(nomor);
        if (LABEL_REGU) {
            return semua;
        }
        var terisi = reguLamaNomor(nomor.id).concat(reguDraf(nomor.id, W.atletDiubah));
        var hasil = semua.filter(function (l) { return terisi.indexOf(l) !== -1 || l === terpilih; });
        // Satu regu kosong untuk regu baru, bila belum ada di pilihan.
        var adaKosong = hasil.some(function (l) { return terisi.indexOf(l) === -1; });
        var baru = semua.filter(function (l) { return hasil.indexOf(l) === -1; })[0];
        if (!adaKosong && baru) {
            hasil.push(baru);
        }
        return semua.filter(function (l) { return hasil.indexOf(l) !== -1; });
    }

    /** Apakah nama regu boleh dipakai di nomor ini. */
    function reguBoleh(nomor, label) {
        return REGU_BEBAS ? namaReguSah(label) : labelRegu(nomor).indexOf(label) !== -1;
    }

    /** Anggota regu sekolah dari pendaftaran sebelumnya, atau null bila regu ini baru. */
    function reguLama(nomorId, label) {
        var tim = (susulan().tim || {})[nomorId];
        return tim && typeof tim === 'object' && Array.isArray(tim[label]) ? tim[label] : null;
    }

    /** Pilihan regu atlet di satu nomor beregu, atau null bila belum dipilih. */
    function pilihanRegu(atlet, nomorId) {
        var r = atlet.regu && atlet.regu[nomorId];
        return r && namaReguSah(String(r.regu || '')) && (r.peran === 'inti' || r.peran === 'cadangan') ? r : null;
    }

    /** Nama regu sekolah ini di satu nomor: regu lama dan daftar sementara (selain `kecuali`). */
    function reguSekolah(nomorId, kecuali) {
        var hasil = reguLamaNomor(nomorId).slice();
        reguDraf(nomorId, kecuali).forEach(function (l) {
            if (hasil.indexOf(l) === -1) {
                hasil.push(l);
            }
        });
        return hasil;
    }

    /**
     * Regu tunggal (K108): sekolah hanya punya satu regu di nomor ini, jadi
     * nama regunya tidak ditulis ("Estafet 4 × 50 m mixed", bukan "… Regu A").
     */
    function reguTunggal(nomorId) {
        return reguSekolah(nomorId, null).length <= 1;
    }

    function namaRegu(nomor, label) {
        return reguTunggal(nomor.id) ? nomor.nama_lengkap : nomor.nama_lengkap + ' ' + labelReguLengkap(label);
    }

    /**
     * Nomor ini hanya membolehkan satu regu untuk sekolah ini (K108, opsi A):
     * batas regu 1 (atau daftar nama admin berisi satu nama), tanpa regu lama
     * lain. Pilihan regu tidak pernah ditampilkan.
     */
    function hanyaSatuRegu(nomor) {
        if (REGU_BEBAS) {
            return Number(nomor.regu_maksimal) === 1 && reguLamaNomor(nomor.id).length <= 1;
        }
        return labelRegu(nomor).length <= 1;
    }

    /** Nomor beregu yang pilihan regunya dibuka lewat "Daftarkan regu lain" (atlet yang sedang diisi). */

    /** Inti lama yang dipilih menjadi cadangan (K105). */
    function diturunkan(nomorId, atletId) {
        return W.draf.turunCadangan.indexOf(nomorId + ':' + atletId) !== -1;
    }

    /** Jumlah anggota dan inti satu regu: lama (setelah diturunkan) ditambah daftar sementara selain `kecuali`. */
    function isiRegu(nomor, label, kecuali) {
        var anggota = 0;
        var inti = 0;
        (reguLama(nomor.id, label) || []).forEach(function (a) {
            anggota++;
            if (a.peran === 'inti' && !diturunkan(nomor.id, a.id)) {
                inti++;
            }
        });
        W.draf.atlet.forEach(function (atlet) {
            var p = atlet !== kecuali && atlet.nomor.indexOf(nomor.id) !== -1 ? pilihanRegu(atlet, nomor.id) : null;
            if (p && p.regu === label) {
                anggota++;
                if (p.peran === 'inti') {
                    inti++;
                }
            }
        });
        return { anggota: anggota, inti: inti };
    }

    /**
     * Regu bawaan untuk atlet baru: regu yang intinya belum lengkap, lalu
     * regu baru yang masih kosong, lalu regu yang masih punya tempat.
     */
    function reguBawaan(nomor) {
        var label = labelRegu(nomor);
        if (REGU_BEBAS) {
            // Usulan nama untuk regu baru: huruf pertama yang belum dipakai.
            HURUF.forEach(function (h) {
                if (label.indexOf(h) === -1) {
                    label.push(h);
                }
            });
        }
        var pilih = function (syarat) {
            for (var i = 0; i < label.length; i++) {
                var isi = isiRegu(nomor, label[i], W.atletDiubah);
                if (syarat(isi, label[i])) {
                    return label[i];
                }
            }
            return null;
        };
        return pilih(function (isi) { return isi.anggota > 0 && isi.inti < nomor.jumlah_inti && isi.anggota < nomor.batas_anggota; })
            || pilih(function (isi, l) { return isi.anggota === 0 && reguLama(nomor.id, l) === null; })
            || pilih(function (isi) { return isi.anggota < nomor.batas_anggota; })
            || label[0];
    }

    function peranBawaan(nomor, label) {
        return isiRegu(nomor, label, W.atletDiubah).inti < nomor.jumlah_inti ? 'inti' : 'cadangan';
    }

    /**
     * Pilihan regu dan peran (Inti/Cadangan) untuk nomor beregu yang
     * dicentang di formulir. Mode Bebas: nama regu diketik, dengan usulan
     * dari regu yang sudah ada (K106).
     */
    function kotakRegu(nomor, terpilih) {
        var kotak = buat('div', 'pilihan-regu mt-2 flex basis-full flex-wrap items-start gap-x-4 gap-y-2 rounded-lg bg-primary-100 px-3 py-2.5');
        kotak.setAttribute('data-regu-untuk', String(nomor.id));
        // Regu sekolah ini di nomor ini selain atlet yang sedang diisi. Selama
        // hanya satu dan masih ada tempat, atlet baru masuk regu itu (K108).
        var lain = reguSekolah(nomor.id, W.atletDiubah);
        var label;
        if (terpilih && reguBoleh(nomor, terpilih.regu)) {
            label = terpilih.regu;
        } else if (!W.reguDibuka[nomor.id] && lain.length === 1 && isiRegu(nomor, lain[0], W.atletDiubah).anggota < nomor.batas_anggota) {
            label = lain[0];
        } else {
            label = reguBawaan(nomor);
        }

        var reguId = 'atlet-regu-' + nomor.id;
        var regu;
        if (REGU_BEBAS) {
            regu = buat('input', W.KELAS.isian);
            regu.type = 'text';
            regu.maxLength = 30;
            regu.placeholder = 'Misalnya A atau Merah';
            regu.autocomplete = 'off';
            regu.value = label;
            var daftar = buat('datalist');
            daftar.id = 'daftar-regu-' + nomor.id;
            labelRegu(nomor).forEach(function (l) {
                var opsi = buat('option');
                opsi.value = l;
                daftar.appendChild(opsi);
            });
            regu.setAttribute('list', daftar.id);
            kotak.appendChild(daftar);
        } else {
            regu = buat('select', W.KELAS.isian);
            var jumlahDraf = {};
            W.draf.atlet.forEach(function (a) {
                var p = a !== W.atletDiubah && a.nomor.indexOf(nomor.id) !== -1 ? pilihanRegu(a, nomor.id) : null;
                if (p) {
                    jumlahDraf[p.regu] = (jumlahDraf[p.regu] || 0) + 1;
                }
            });
            opsiRegu(nomor, label).forEach(function (l) {
                var lama = reguLama(nomor.id, l);
                var keterangan = lama ? ' · sudah terdaftar, ' + lama.length + ' atlet' : (jumlahDraf[l] ? ' · ' + jumlahDraf[l] + ' atlet di daftar' : ' · baru');
                var opsi = buat('option', null, labelReguLengkap(l) + keterangan);
                opsi.value = l;
                opsi.selected = l === label;
                regu.appendChild(opsi);
            });
        }
        regu.id = reguId;
        regu.setAttribute('data-regu', String(nomor.id));

        // Inti/Cadangan sebagai dua tombol bersebelahan.
        var nilaiPeran = terpilih && terpilih.regu === label ? terpilih.peran : peranBawaan(nomor, label);
        var peran = buat('div', 'pilihan-peran flex w-full');
        peran.setAttribute('role', 'group');
        peran.setAttribute('aria-label', 'Peran di regu');
        var tombolPeran = {};
        [['inti', 'Inti'], ['cadangan', 'Cadangan']].forEach(function (p) {
            var masukan = buat('input', 'sr-only');
            masukan.type = 'radio';
            masukan.name = 'atlet-peran-' + nomor.id;
            masukan.id = 'atlet-peran-' + nomor.id + '-' + p[0];
            masukan.value = p[0];
            masukan.checked = p[0] === nilaiPeran;
            masukan.setAttribute('data-peran', String(nomor.id));
            // Radio di dalam labelnya supaya tampilan terpilih memakai `has-checked:` (F9-05).
            var tombol = buat('label', W.KELAS.pilihanKecil + ' min-h-10', p[1]);
            tombol.htmlFor = masukan.id;
            tombol.insertBefore(masukan, tombol.firstChild);
            peran.appendChild(tombol);
            tombolPeran[p[0]] = masukan;
        });

        var pilihPeran = function () {
            var p = peranBawaan(nomor, rapikanRegu(regu.value));
            tombolPeran[p].checked = true;
        };
        regu.addEventListener('change', pilihPeran);

        var l1 = buat('label', 'pilihan-regu-label text-xs font-semibold uppercase tracking-wide text-primary-900', AWALAN_REGU);
        l1.htmlFor = reguId;
        var l2 = buat('span', 'pilihan-regu-label text-xs font-semibold uppercase tracking-wide text-primary-900', 'Sebagai');
        var b1 = buat('div', 'pilihan-regu-isian flex min-w-0 flex-[1_1_10rem] flex-col gap-1');
        b1.appendChild(l1);
        b1.appendChild(regu);
        var b2 = buat('div', 'pilihan-regu-isian flex min-w-0 flex-[1_1_10rem] flex-col gap-1');
        b2.appendChild(l2);
        b2.appendChild(peran);
        kotak.appendChild(b1);
        kotak.appendChild(b2);

        // Pilihan regu disembunyikan selama sekolah hanya punya satu regu di
        // nomor ini (K108): atlet otomatis masuk regu itu. Nomor yang hanya
        // membolehkan satu regu tidak pernah menampilkannya (opsi A); selain
        // itu official bisa membukanya lewat "Daftarkan regu lain".
        var satu = hanyaSatuRegu(nomor);
        if (satu || (!W.reguDibuka[nomor.id] && lain.length <= 1 && (lain.length === 0 || lain[0] === label))) {
            b1.hidden = true;
            kotak.classList.add('pilihan-regu-tunggal');
            if (!satu) {
                var buka = buat('button', 'pilihan-regu-lain basis-full ' + W.KELAS.tautan);
                buka.type = 'button';
                buka.appendChild(ikon('circle-plus'));
                buka.appendChild(document.createTextNode(' Daftarkan ' + AWALAN_REGU.toLowerCase() + ' lain'));
                buka.addEventListener('click', function () {
                    W.reguDibuka[nomor.id] = true;
                    b1.hidden = false;
                    kotak.classList.remove('pilihan-regu-tunggal');
                    buka.remove();
                    // Langsung arahkan ke regu baru; official tetap bisa memilih regu lama.
                    if (REGU_BEBAS) {
                        var dipakai = labelRegu(nomor).concat(lain);
                        regu.value = HURUF.filter(function (h) { return dipakai.indexOf(h) === -1; })[0] || '';
                    } else {
                        var baru = Array.prototype.filter.call(regu.options, function (o) { return lain.indexOf(o.value) === -1; })[0];
                        if (baru) {
                            regu.value = baru.value;
                        }
                    }
                    pilihPeran();
                    regu.focus();
                });
                kotak.appendChild(buka);
            }
        }
        kotak.appendChild(buat('p', 'pilihan-regu-bantuan basis-full text-xs text-gray-600', 'Inti: ' + nomor.jumlah_inti + ' atlet yang berlomba di tiap regu. Cadangan: pengganti bila ada inti yang berhalangan.'));
        return kotak;
    }

    /** Pilihan regu yang sedang tampil di formulir: id nomor => {regu, peran}. */
    function reguFormulir() {
        var hasil = {};
        $$('[data-regu-untuk]', L3.formulir).forEach(function (kotak) {
            var id = Number(kotak.getAttribute('data-regu-untuk'));
            var peran = $('[data-peran]:checked', kotak);
            hasil[id] = { regu: rapikanRegu($('[data-regu]', kotak).value), peran: peran ? peran.value : '' };
        });
        return hasil;
    }

    function tampilkanKotakRegu(centang) {
        var kotak = centang.closest('.pilihan-nomor-beregu');
        var regu = kotak && $('[data-regu-untuk]', kotak);
        if (regu) {
            regu.hidden = !centang.checked;
        }
    }

    // Dipakai bagian lain.
    W.AWALAN_REGU = AWALAN_REGU;
    W.REGU_BEBAS = REGU_BEBAS;
    W.diturunkan = diturunkan;
    W.idAman = idAman;
    W.kotakRegu = kotakRegu;
    W.labelReguLengkap = labelReguLengkap;
    W.namaRegu = namaRegu;
    W.pilihanRegu = pilihanRegu;
    W.reguBoleh = reguBoleh;
    W.reguFormulir = reguFormulir;
    W.reguLama = reguLama;
    W.reguLamaNomor = reguLamaNomor;
    W.reguTunggal = reguTunggal;
    W.tampilkanKotakRegu = tampilkanKotakRegu;
}(window.Pendaftaran));
