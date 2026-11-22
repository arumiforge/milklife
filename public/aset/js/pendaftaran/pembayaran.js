/**
 * Langkah 5: Pembayaran, kirim pendaftaran dengan bukti transfer (K90, K118).
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
    var SISI_BUKTI = W.SISI_BUKTI;
    var BATAS_BUKTI = W.BATAS_BUKTI;
    var $ = W.$;
    var rupiah = W.rupiah;
    var yakin = W.yakin;
    var buatKunciKirim = W.buatKunciKirim;
    var hapusDraf = W.hapusDraf;
    var simpanDraf = W.simpanDraf;
    var penyiapLangkah = W.penyiapLangkah;
    var perbaruiPil = W.perbaruiPil;
    var tampilkanGalat = W.tampilkanGalat;
    var kecilkanFoto = W.kecilkanFoto;
    var kirim = W.kirim;
    var periksaServer = W.periksaServer;
    var pesanGagalKirim = W.pesanGagalKirim;
    var tampilkanGalatServer = W.tampilkanGalatServer;
    var pasangTurnstile = W.pasangTurnstile;
    var resetTurnstile = W.resetTurnstile;
    var tokenTurnstile = W.tokenTurnstile;

    // ------------------------------------------------------------------
    // Langkah 5: Pembayaran
    // ------------------------------------------------------------------

    function tampilkanBayar() {
        var bayar = DATA.pembayaran;
        $('#bayar-bank').textContent = bayar.bank;
        $('#bayar-rekening').textContent = bayar.nomor_rekening;
        $('#bayar-pemilik').textContent = bayar.pemilik_rekening;
        $('#bayar-total').textContent = W.rincian ? rupiah(W.rincian.total) : 'Menghitung…';
        $('#bayar-rincian').textContent = W.rincian
            ? 'Subtotal individu ' + rupiah(W.rincian.subtotal_individu) + ' · subtotal beregu ' + rupiah(W.rincian.subtotal_beregu) + '.'
            : '';
        $('#tombol-kirim').disabled = !W.rincian;
        var logo = $('#bayar-logo');
        logo.hidden = !bayar.logo_bank;
        if (bayar.logo_bank) {
            logo.src = bayar.logo_bank;
            logo.alt = 'Logo ' + bayar.bank;
        }
    }

    function tampilkanSelesai() {
        var t = W.draf.tersimpan;
        $('#selesai-sekolah').textContent = t.nama_sekolah;
        $('#selesai-id').textContent = t.id_pendaftaran;
        var wa = $('#bayar-wa');
        wa.hidden = !t.wa;
        if (t.wa) {
            wa.href = t.wa;
        }
        $('#cetak-bukti').href = t.bukti;
        $('#selesai-keterangan').textContent = 'Bukti juga bisa dicetak lagi kapan saja lewat menu Cek Pendaftaran dengan ID ' + t.id_pendaftaran
            + (t.npsn ? ' atau NPSN ' + t.npsn : '') + '.';
        $('#bayar-isi').hidden = true;
        $('#selesai').hidden = false;
        document.querySelectorAll('[data-mulai-ulang]').forEach(function (tombol) {
            tombol.hidden = true;
        });
    }

    penyiapLangkah[5] = function () {
        if (W.sudahSelesai) {
            tampilkanSelesai();
            return;
        }
        tampilkanBayar();
        pasangTurnstile();
        if (!W.rincian) {
            // Draf yang dibuka lagi lain waktu: hitung ulang total dari server.
            periksaServer().then(function (galat) {
                if (galat === false) {
                    return;
                }
                if (galat.length > 0) {
                    tampilkanGalat(5, galat);
                }
                tampilkanBayar();
            });
        }
    };

    function salin(tombol, teks) {
        var label = $('span', tombol);
        var asal = label.textContent;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(teks).then(function () {
                label.textContent = 'Tersalin';
                window.setTimeout(function () { label.textContent = asal; }, 2000);
            }, function () {});
        }
    }

    $('#bayar-salin').addEventListener('click', function () {
        salin(this, DATA.pembayaran.nomor_rekening);
    });

    $('#selesai-salin').addEventListener('click', function () {
        salin(this, W.draf.tersimpan ? W.draf.tersimpan.id_pendaftaran : '');
    });

    // Pendaftaran dan bukti transfer dikirim bersama (K90): tanpa bukti tidak ada yang tersimpan.
    $('#tombol-kirim').addEventListener('click', function () {
        var berkas = $('#bayar-berkas');
        var dipilih = berkas.files[0];

        if (!W.rincian) {
            return;
        }
        if (!dipilih) {
            tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Bukti transfer belum dipilih. Pilih foto atau tangkapan layar bukti transfer.' }]);
            berkas.focus();
            return;
        }
        if (dipilih.size > 10 * 1024 * 1024) {
            tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Gambar lebih dari 10 MB. Perkecil dulu, atau pakai tangkapan layar.' }]);
            return;
        }
        if (DATA.turnstile && tokenTurnstile() === '') {
            pasangTurnstile();
            tampilkanGalat(5, [{ jalur: 'turnstile', pesan: 'Pemeriksaan keamanan belum selesai. Tunggu sampai kotak di atas tombol bertanda centang hijau, lalu tekan Kirim pendaftaran lagi.' }]);
            return;
        }

        yakin('Kirim pendaftaran?', 'Pendaftaran ' + W.rincian.sekolah.nama + ' dengan ' + W.draf.atlet.length + ' atlet dan total ' + rupiah(W.rincian.total)
            + ' dikirim bersama bukti transfer "' + dipilih.name + '". Setelah terkirim, data tidak bisa diubah lewat halaman ini. Bila ada yang perlu diubah, hubungi panitia.',
        'Kirim pendaftaran', 'utama').then(function (setuju) {
            if (setuju) {
                kirimPendaftaran(dipilih);
            }
        });
    });

    function kirimPendaftaran(dipilih) {
        var tombol = $('#tombol-kirim');
        var label = $('span', tombol);
        tombol.disabled = true;
        label.textContent = 'Mengirim…';
        var pulih = function () {
            tombol.disabled = false;
            label.textContent = 'Kirim pendaftaran';
        };

        // Kunci yang sama untuk kirim ulang: bila kiriman sebelumnya sudah tersimpan
        // tetapi jawabannya tidak sampai, server menjawab dengan pendaftaran itu.
        if (!/^[0-9a-f]{32}$/.test(String(W.draf.kunciKirim || ''))) {
            W.draf.kunciKirim = buatKunciKirim();
        }
        simpanDraf();

        kecilkanFoto(dipilih, SISI_BUKTI, BATAS_BUKTI).then(function (dataUrl) {
            var tambahan = { bukti_bayar: dataUrl, kunci_kirim: W.draf.kunciKirim };
            if (DATA.turnstile) {
                tambahan['cf-turnstile-response'] = tokenTurnstile();
            }
            return kirim(DATA.alamat.simpan, tambahan);
        }, function () {
            return { kode: -1 };
        }).then(function (hasil) {
            pulih();

            if (hasil.kode === -1) {
                tampilkanGalat(5, [{ jalur: 'bukti_bayar', pesan: 'Gambar tidak bisa dibaca. Pilih foto atau tangkapan layar bukti transfer lain (JPG, PNG, atau WEBP).' }]);
                return;
            }

            if (hasil.kode === 201 && hasil.isi && hasil.isi.status === 'tersimpan') {
                var r = hasil.isi.rincian;
                W.draf.tersimpan = {
                    id_pendaftaran: hasil.isi.id_pendaftaran,
                    nama_sekolah: r.sekolah.nama,
                    npsn: r.sekolah.npsn,
                    wa: hasil.isi.wa,
                    bukti: hasil.isi.bukti
                };
                W.sudahSelesai = true;
                hapusDraf();
                penyiapLangkah[5]();
                perbaruiPil();
                $('#cetak-bukti').focus();
                return;
            }

            // Token sudah terpakai (atau ditolak): siapkan token baru untuk kirim ulang.
            resetTurnstile();

            if (hasil.kode === 422 && hasil.isi && Array.isArray(hasil.isi.galat)) {
                W.rincian = null;
                tampilkanGalatServer(hasil.isi.galat);
                return;
            }

            tampilkanGalat(5, [{ jalur: 'kirim', pesan: pesanGagalKirim(hasil) }]);
        });
    }
}(window.Pendaftaran));
