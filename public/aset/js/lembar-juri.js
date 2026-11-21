/*
 * Lembar juri (F9-09): simpan otomatis per baris di admin/juri/babak/<id>.
 *
 * - Setiap kartu peserta adalah formulir sendiri (`form[data-baris-juri]`).
 *   Mengetik menandai baris "Belum tersimpan" lalu disimpan 1,2 detik
 *   setelah berhenti; pindah kotak atau memilih keterangan langsung menyimpan.
 * - Satu kiriman per baris pada satu waktu; isian yang berubah selama
 *   menyimpan dikirim lagi setelahnya dengan versi baris terbaru.
 * - Keadaan ditulis di `data-keadaan` formulir (kosong, belum, menyimpan,
 *   tersimpan, galat, bentrok); warnanya diatur kelas Tailwind di view.
 * - Tombol X mengisi atau mengosongkan percobaan gagal (nomor lompat/lempar).
 * - Sambungan putus: dicoba lagi saat tersambung kembali atau 5 detik kemudian.
 * - Tanpa skrip, tombol Simpan di tiap kartu mengirim formulir biasa.
 */
(function () {
    'use strict';

    var formulir = document.querySelectorAll('form[data-baris-juri]');
    if (formulir.length === 0) {
        return;
    }

    var JEDA_KETIK = 1200;
    var JEDA_ULANG = 5000;
    var barisan = [];

    function namaToken(form) {
        var token = form.querySelector('input[type="hidden"][name^="csrf"]');
        return token ? token.name : null;
    }

    function perbaruiToken(nilai) {
        if (!nilai) {
            return;
        }
        formulir.forEach(function (form) {
            var nama = namaToken(form);
            if (nama) {
                form.querySelector('input[name="' + nama + '"]').value = nilai;
            }
        });
    }

    function jam() {
        var kini = new Date();
        return String(kini.getHours()).padStart(2, '0') + ':' + String(kini.getMinutes()).padStart(2, '0');
    }

    function Baris(form) {
        this.form = form;
        this.status = form.querySelector('[data-status-baris]');
        this.pewaktu = null;
        this.sedang = false;
        this.lagi = false;
        this.berubah = false;
    }

    Baris.prototype.tulis = function (keadaan, pesan) {
        this.form.setAttribute('data-keadaan', keadaan);
        this.status.textContent = pesan;
    };

    Baris.prototype.tandaiSalah = function (salah) {
        this.form.querySelectorAll('input[data-isian-juri]').forEach(function (kotak) {
            if (salah) {
                kotak.setAttribute('aria-invalid', 'true');
            } else {
                kotak.removeAttribute('aria-invalid');
            }
        });
    };

    Baris.prototype.ubah = function (segera) {
        var ini = this;
        this.berubah = true;
        if (this.form.getAttribute('data-keadaan') !== 'bentrok') {
            this.tulis('belum', 'Belum tersimpan…');
        }
        clearTimeout(this.pewaktu);
        this.pewaktu = setTimeout(function () {
            ini.kirim();
        }, segera ? 0 : JEDA_KETIK);
    };

    Baris.prototype.kirim = function () {
        var ini = this;
        clearTimeout(this.pewaktu);

        if (this.sedang) {
            this.lagi = true;
            return;
        }
        if (!this.berubah) {
            return;
        }

        this.sedang = true;
        this.berubah = false;
        this.tulis('menyimpan', 'Menyimpan…');

        fetch(this.form.action, {
            method: 'POST',
            body: new FormData(this.form),
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (jawaban) {
            return jawaban.json().catch(function () { return null; });
        }, function () {
            return undefined;
        }).then(function (isi) {
            ini.sedang = false;
            ini.terima(isi);
            if (ini.lagi || ini.berubah) {
                ini.lagi = false;
                ini.berubah = true;
                ini.kirim();
            }
        });
    };

    Baris.prototype.terima = function (isi) {
        var ini = this;

        if (isi === undefined) {
            this.berubah = true;
            this.tulis('galat', 'Belum tersambung ke server. Isian tetap di layar dan dicoba lagi otomatis.');
            clearTimeout(this.pewaktu);
            this.pewaktu = setTimeout(function () {
                ini.kirim();
            }, JEDA_ULANG);
            return;
        }
        if (!isi || !isi.status) {
            this.berubah = true;
            this.tulis('galat', 'Halaman ini sudah terlalu lama terbuka atau sesi masuk sudah habis. Muat ulang halaman (masuk lagi bila diminta), lalu isi lagi.');
            return;
        }

        perbaruiToken(isi.csrf);

        if (isi.status === 'kedaluwarsa') {
            this.berubah = true;
            this.tulis('galat', isi.pesan || 'Halaman ini sudah terlalu lama terbuka. Muat ulang halaman, lalu isi lagi.');
            return;
        }
        if (isi.status === 'bentrok' || isi.status === 'hilang') {
            this.tulis('bentrok', isi.pesan);
            return;
        }
        if (isi.status !== 'tersimpan') {
            this.tandaiSalah(true);
            this.tulis('galat', isi.pesan);
            return;
        }

        this.tandaiSalah(false);
        this.isiBaris(isi.baris);
        if (!this.berubah) {
            var ringkasan = isi.baris.ringkasan ? ': ' + isi.baris.ringkasan : '';
            this.tulis(ringkasan || isi.baris.keterangan ? 'tersimpan' : 'kosong', (ringkasan || isi.baris.keterangan ? 'Tersimpan' + ringkasan : 'Dikosongkan') + ' (' + jam() + ')');
        }
    };

    // Versi baris dan poin komponen dari server; isian yang sedang diketik tidak ditimpa.
    Baris.prototype.isiBaris = function (baris) {
        var versi = this.form.querySelector('[data-versi]');
        if (versi) {
            versi.value = baris.versi;
        }
        if (this.berubah) {
            return;
        }
        var prestasi = this.form.querySelector('input[name="prestasi"]');
        if (prestasi && document.activeElement !== prestasi) {
            prestasi.value = baris.isian;
        }
        (baris.rincian || []).forEach(function (r, i) {
            var poin = this.form.querySelector('[data-poin-komponen="' + i + '"]');
            if (poin) {
                poin.textContent = r.prestasi === '' ? '' : r.poin + ' poin' + (r.luar ? ', di luar tabel' : '');
            }
        }, this);
    };

    formulir.forEach(function (form) {
        var baris = new Baris(form);
        barisan.push(baris);

        var simpan = form.querySelector('[data-tombol-simpan]');
        if (simpan) {
            simpan.hidden = true;
        }

        form.addEventListener('input', function (peristiwa) {
            if (peristiwa.target.matches('input[data-isian-juri]')) {
                var gagal = form.querySelector('[data-tombol-gagal="' + peristiwa.target.id + '"]');
                if (gagal) {
                    gagal.setAttribute('aria-pressed', peristiwa.target.value.trim().toUpperCase() === 'X' ? 'true' : 'false');
                }
                baris.ubah(false);
            }
        });

        form.addEventListener('change', function (peristiwa) {
            if (peristiwa.target.matches('[data-isian-juri]')) {
                baris.ubah(true);
            }
        });

        form.addEventListener('submit', function (peristiwa) {
            peristiwa.preventDefault();
            baris.berubah = true;
            baris.kirim();
        });

        form.querySelectorAll('[data-tombol-gagal]').forEach(function (tombol) {
            tombol.hidden = false;
            tombol.addEventListener('click', function () {
                var kotak = document.getElementById(tombol.getAttribute('data-tombol-gagal'));
                var gagal = kotak.value.trim().toUpperCase() !== 'X';
                kotak.value = gagal ? 'X' : '';
                tombol.setAttribute('aria-pressed', gagal ? 'true' : 'false');
                baris.ubah(true);
            });
        });
    });

    window.addEventListener('online', function () {
        barisan.forEach(function (baris) {
            if (baris.berubah && !baris.sedang) {
                baris.kirim();
            }
        });
    });

    window.addEventListener('beforeunload', function (peristiwa) {
        var belum = barisan.some(function (baris) {
            return baris.berubah || baris.sedang;
        });
        if (belum) {
            peristiwa.preventDefault();
            peristiwa.returnValue = '';
        }
    });
}());
