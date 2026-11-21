/**
 * Langkah 2: Ofisial (penanggung jawab) dan pengecilan foto di peramban.
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
    var SISI_FOTO = W.SISI_FOTO;
    var BATAS_FOTO = W.BATAS_FOTO;
    var $ = W.$;
    var $$ = W.$$;
    var nomorWaSah = W.nomorWaSah;
    var rapikanSpasi = W.rapikanSpasi;
    var simpanDraf = W.simpanDraf;
    var pemeriksaLangkah = W.pemeriksaLangkah;
    var penyiapLangkah = W.penyiapLangkah;

    // ------------------------------------------------------------------
    // Langkah 2: Penanggung Jawab
    // ------------------------------------------------------------------

    var daftarPj = $('#daftar-penanggung-jawab');
    var templatPj = $('#templat-penanggung-jawab');
    var tambahPj = $('#tambah-penanggung-jawab');
    var minimalPj = Math.max(1, DATA.aturan.penanggung_jawab_minimal || 1);
    var maksimalPj = Math.max(minimalPj, DATA.aturan.penanggung_jawab_maksimal || 5);

    function tampilkanPenanggungJawab() {
        while (W.draf.penanggungJawab.length < minimalPj) {
            W.draf.penanggungJawab.push({ nama: '', nomor_wa: '', foto: '' });
        }

        daftarPj.textContent = '';
        W.draf.penanggungJawab.forEach(function (orang, i) {
            var kartu = templatPj.content.firstElementChild.cloneNode(true);
            var awalan = 'pj-' + i + '-';

            $('[data-judul]', kartu).textContent = 'Ofisial ' + (i + 1);

            ['nama', 'nomor_wa'].forEach(function (kunci) {
                var masukan = $('[data-isian="' + kunci + '"]', kartu);
                masukan.id = awalan + kunci;
                masukan.value = orang[kunci] || '';
                masukan.setAttribute('data-jalur', 'penanggung_jawab.' + i + '.' + kunci);
                $('[data-untuk="' + kunci + '"]', kartu).setAttribute('for', masukan.id);
                masukan.addEventListener('input', function () {
                    W.draf.penanggungJawab[i][kunci] = masukan.value;
                    simpanDraf();
                });
            });

            var kotakFoto = $('[data-isian-foto]', kartu);
            var pratinjau = $('.foto-pratinjau', kartu);
            var berkas = $('[data-isian="foto"]', kartu);
            berkas.id = awalan + 'foto';
            kotakFoto.setAttribute('data-jalur', 'penanggung_jawab.' + i + '.foto');
            $('[data-label-foto]', kartu).id = awalan + 'label-foto';
            berkas.setAttribute('aria-labelledby', awalan + 'label-foto');

            function tampilkanFoto() {
                var ada = !!W.draf.penanggungJawab[i].foto;
                pratinjau.hidden = !ada;
                $('.foto-kosong', kartu).hidden = ada;
                if (ada) {
                    pratinjau.src = W.draf.penanggungJawab[i].foto;
                    pratinjau.alt = 'Foto ofisial ' + (i + 1);
                }
                $('[data-teks-foto]', kartu).textContent = ada ? 'Ganti foto' : 'Ambil atau pilih foto';
            }

            berkas.addEventListener('change', function () {
                var dipilih = berkas.files && berkas.files[0];
                var galatFoto = $('[data-galat-foto]', kartu);
                galatFoto.textContent = '';
                if (!dipilih) {
                    return;
                }
                galatFoto.textContent = 'Memperkecil foto…';
                kecilkanFoto(dipilih).then(function (dataUrl) {
                    W.draf.penanggungJawab[i].foto = dataUrl;
                    simpanDraf();
                    galatFoto.textContent = '';
                    kotakFoto.classList.remove('is-invalid');
                    tampilkanFoto();
                }).catch(function () {
                    galatFoto.textContent = 'Foto belum bisa dibaca. Pilih foto lain (JPG atau PNG) atau ambil ulang dengan kamera.';
                });
                berkas.value = '';
            });

            var hapus = $('[data-hapus]', kartu);
            hapus.hidden = W.draf.penanggungJawab.length <= minimalPj;
            hapus.setAttribute('aria-label', 'Hapus ofisial ' + (i + 1));
            hapus.addEventListener('click', function () {
                // Tidak bisa dibatalkan, jadi ditanyakan dulu (K89, F9-17).
                W.yakin('Hapus ofisial ' + (i + 1) + '?', 'Nama, nomor WhatsApp, dan foto ofisial ini dihapus dari isian.', 'Hapus ofisial', 'bahaya').then(function (ya) {
                    if (!ya) {
                        return;
                    }
                    W.draf.penanggungJawab.splice(i, 1);
                    W.rincian = null;
                    simpanDraf();
                    tampilkanPenanggungJawab();
                });
            });

            tampilkanFoto();
            daftarPj.appendChild(kartu);
        });

        tambahPj.hidden = W.draf.penanggungJawab.length >= maksimalPj;
    }

    tambahPj.addEventListener('click', function () {
        if (W.draf.penanggungJawab.length < maksimalPj) {
            W.draf.penanggungJawab.push({ nama: '', nomor_wa: '', foto: '' });
            simpanDraf();
            tampilkanPenanggungJawab();
            var masukan = $$('[data-isian="nama"]', daftarPj).pop();
            if (masukan) {
                masukan.focus();
            }
        }
    });

    /**
     * Perkecil foto: sisi terpanjang 800 piksel, JPEG. Kualitas diturunkan
     * bila hasilnya masih terlalu besar.
     *
     * @param {File} berkas
     * @returns {Promise<string>} data URL `data:image/jpeg;base64,…`
     */
    function kecilkanFoto(berkas, sisi, batas) {
        sisi = sisi || SISI_FOTO;
        batas = batas || BATAS_FOTO;
        return new Promise(function (selesai, gagal) {
            if (!berkas.type || berkas.type.indexOf('image/') !== 0) {
                gagal(new Error('bukan gambar'));
                return;
            }
            var alamat = URL.createObjectURL(berkas);
            var gambar = new Image();
            gambar.onload = function () {
                URL.revokeObjectURL(alamat);
                var skala = Math.min(1, sisi / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                var kanvas = document.createElement('canvas');
                kanvas.width = Math.max(1, Math.round(gambar.naturalWidth * skala));
                kanvas.height = Math.max(1, Math.round(gambar.naturalHeight * skala));
                var konteks = kanvas.getContext('2d');
                konteks.fillStyle = '#fff';
                konteks.fillRect(0, 0, kanvas.width, kanvas.height);
                konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

                var kualitas = 0.82;
                var hasil = kanvas.toDataURL('image/jpeg', kualitas);
                while (hasil.length * 0.75 > batas && kualitas > 0.4) {
                    kualitas -= 0.12;
                    hasil = kanvas.toDataURL('image/jpeg', kualitas);
                }
                if (hasil.indexOf('data:image/jpeg;base64,') !== 0 || hasil.length * 0.75 > batas) {
                    gagal(new Error('terlalu besar'));
                    return;
                }
                selesai(hasil);
            };
            gambar.onerror = function () {
                URL.revokeObjectURL(alamat);
                gagal(new Error('tidak terbaca'));
            };
            gambar.src = alamat;
        });
    }

    pemeriksaLangkah[2] = function () {
        var galat = [];
        var jumlah = W.draf.penanggungJawab.length;

        if (jumlah < minimalPj) {
            galat.push({ jalur: 'penanggung_jawab', pesan: 'Isi minimal ' + minimalPj + ' ofisial.' });
        }

        W.draf.penanggungJawab.forEach(function (orang, i) {
            var ke = 'Ofisial ' + (i + 1) + ': ';
            if (rapikanSpasi(orang.nama) === '') {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.nama', pesan: ke + 'nama belum diisi.' });
            }
            if (!nomorWaSah(orang.nomor_wa)) {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.nomor_wa', pesan: ke + 'nomor WA belum benar. Tulis nomor ponsel yang dipakai untuk WA, misalnya 0812-3456-7890.' });
            }
            if (!orang.foto) {
                galat.push({ jalur: 'penanggung_jawab.' + i + '.foto', pesan: ke + 'foto belum diisi. Ambil foto dengan kamera atau pilih dari galeri.' });
            }
        });

        return galat;
    };

    penyiapLangkah[2] = tampilkanPenanggungJawab;

    // Dipakai bagian lain.
    W.kecilkanFoto = kecilkanFoto;
}(window.Pendaftaran));
