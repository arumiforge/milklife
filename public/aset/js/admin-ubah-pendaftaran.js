/**
 * Formulir ubah pendaftaran di panel admin (`admin/pendaftaran/<id>/ubah`, F6-04).
 *
 * Halaman PHP (`app/Views/admin/pendaftaran/ubah.php`) menulis seluruh
 * formulir, templat kartu (`#templat-pj`, `#templat-atlet`), dan data JSON
 * `#data-ubah` (jenjang, kelompok umur aktif, aturan). Skrip ini:
 *
 * - menambah dan menghapus kartu penanggung jawab dan atlet,
 * - memperkecil foto baru di peramban (sisi terpanjang 800 piksel, JPEG) ke
 *   isian tersembunyi `penanggung_jawab[<k>][foto]`, seperti wizard,
 * - menampilkan kelompok umur atlet dan hanya pilihan nomor lomba yang cocok
 *   (yang sudah dicentang tetap tampil supaya bisa dihapus centangnya),
 * - sebelum menyimpan, mengirim isian dengan `pratinjau=1` ke alamat yang
 *   sama. Server memeriksa semua aturan tanpa menulis apa pun dan menjawab
 *   galat, atau kalimat konfirmasi (K32: status kembali ke Menunggu; K59:
 *   atlet baru langsung mendapat nomor dada). Setelah admin setuju di jendela
 *   konfirmasi, formulir dikirim biasa dengan `setuju_menunggu=1` bila perlu.
 *
 * Semua aturan diperiksa ulang di server saat menyimpan. Teks dari isian dan
 * server ditulis lewat `textContent`.
 */
(function () {
    'use strict';

    var formulir = document.getElementById('formulir-ubah');
    var sumber = document.getElementById('data-ubah');

    if (!formulir || !sumber) {
        return;
    }

    var DATA = JSON.parse(sumber.textContent);
    var SISI_FOTO = 800;
    var BATAS_FOTO = 450 * 1024;
    var kunciBaru = 0;
    var sedangMengirim = false;

    var daftarPj = document.getElementById('daftar-pj');
    var daftarAtlet = document.getElementById('daftar-atlet');
    var kotakGalat = document.getElementById('galat-pratinjau');
    var jenjangSekolah = document.getElementById('sekolah-jenjang');

    function $$(pemilih, induk) {
        return Array.prototype.slice.call((induk || document).querySelectorAll(pemilih));
    }

    // ------------------------------------------------------------------
    // Kartu: tambah, hapus, nomor urut
    // ------------------------------------------------------------------

    function nomoriKartu() {
        [daftarPj, daftarAtlet].forEach(function (daftar) {
            $$('.urut-kartu', daftar).forEach(function (el, i) { el.textContent = String(i + 1); });
        });
    }

    function tambahKartu(idTemplat, daftar) {
        var templat = document.getElementById(idTemplat);
        var kunci = 'b' + (++kunciBaru);
        var wadah = document.createElement('div');

        wadah.innerHTML = templat.innerHTML.split('__k__').join(kunci);
        var kartu = wadah.firstElementChild;
        daftar.appendChild(kartu);
        if (window.PilihTanggal) {
            window.PilihTanggal.pasang(kartu);
        }
        siapkanKartu(kartu);
        nomoriKartu();

        var pertama = kartu.querySelector('input[type="text"]');
        if (pertama) {
            pertama.focus();
        }
    }

    function siapkanKartu(kartu) {
        var hapus = kartu.querySelector('.hapus-kartu');
        if (hapus) {
            hapus.addEventListener('click', function () {
                kartu.remove();
                nomoriKartu();
            });
        }

        var foto = kartu.querySelector('.pilih-foto');
        if (foto) {
            foto.addEventListener('change', function () { gantiFoto(kartu, foto); });
        }

        if (kartu.classList.contains('kartu-atlet')) {
            $$('[data-isian="tanggal_lahir"], [data-isian="jenis_kelamin"]', kartu).forEach(function (el) {
                el.addEventListener('change', function () { saringNomor(kartu); });
            });
            $$('input[data-isian="nomor"]', kartu).forEach(function (el) {
                el.addEventListener('change', function () { saringNomor(kartu); });
            });
            saringNomor(kartu);
        }
    }

    document.getElementById('tambah-pj').addEventListener('click', function () {
        tambahKartu('templat-pj', daftarPj);
    });

    document.getElementById('tambah-atlet').addEventListener('click', function () {
        tambahKartu('templat-atlet', daftarAtlet);
    });

    // ------------------------------------------------------------------
    // Foto penanggung jawab
    // ------------------------------------------------------------------

    function kecilkanFoto(berkas) {
        return new Promise(function (selesai, gagal) {
            if (!berkas.type || berkas.type.indexOf('image/') !== 0) {
                gagal(new Error('bukan gambar'));
                return;
            }
            var alamat = URL.createObjectURL(berkas);
            var gambar = new Image();
            gambar.onload = function () {
                URL.revokeObjectURL(alamat);
                var skala = Math.min(1, SISI_FOTO / Math.max(gambar.naturalWidth, gambar.naturalHeight));
                var kanvas = document.createElement('canvas');
                kanvas.width = Math.max(1, Math.round(gambar.naturalWidth * skala));
                kanvas.height = Math.max(1, Math.round(gambar.naturalHeight * skala));
                var konteks = kanvas.getContext('2d');
                konteks.fillStyle = '#fff';
                konteks.fillRect(0, 0, kanvas.width, kanvas.height);
                konteks.drawImage(gambar, 0, 0, kanvas.width, kanvas.height);

                var kualitas = 0.82;
                var hasil = kanvas.toDataURL('image/jpeg', kualitas);
                while (hasil.length * 0.75 > BATAS_FOTO && kualitas > 0.4) {
                    kualitas -= 0.12;
                    hasil = kanvas.toDataURL('image/jpeg', kualitas);
                }
                if (hasil.indexOf('data:image/jpeg;base64,') !== 0 || hasil.length * 0.75 > BATAS_FOTO) {
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

    function gantiFoto(kartu, masukan) {
        var berkas = masukan.files && masukan.files[0];
        var tersembunyi = kartu.querySelector('.isi-foto');

        masukan.setCustomValidity('');
        if (!berkas) {
            return;
        }

        kecilkanFoto(berkas).then(function (dataUrl) {
            tersembunyi.value = dataUrl;
            var wadah = kartu.querySelector('.pratinjau-foto');
            var gambar = document.createElement('img');
            gambar.className = 'img-fluid rounded border';
            gambar.alt = 'Foto baru';
            gambar.src = dataUrl;
            wadah.textContent = '';
            wadah.appendChild(gambar);
            masukan.classList.remove('is-invalid');
        }).catch(function () {
            tersembunyi.value = '';
            masukan.value = '';
            masukan.setCustomValidity('Foto ini belum bisa dibaca. Pilih foto lain (JPG atau PNG).');
            masukan.reportValidity();
        });
    }

    // ------------------------------------------------------------------
    // Kelompok umur dan pilihan nomor lomba
    // ------------------------------------------------------------------

    /**
     * Kelompok umur dari jenjang dan tahun lahir (K95, sama dengan `KelompokUmurAtlet`):
     * bila jenjang terisi, hanya kelompok umur yang dibuka untuk jenjang itu yang dicari.
     */
    function tentukanKu(tahun, jenjang) {
        var cocok = null;
        DATA.kelompok_umur.some(function (ku) {
            var awal = ku.tahun_lahir_awal;
            var akhir = ku.tahun_lahir_akhir;
            if (awal === null && akhir === null) {
                return false;
            }
            if (jenjang && ku.jenjang.indexOf(jenjang) === -1) {
                return false;
            }
            if ((awal === null || tahun >= awal) && (akhir === null || tahun <= akhir)) {
                cocok = ku;
                return true;
            }
            return false;
        });

        if (!cocok) {
            return {
                ku: null,
                pesan: jenjang
                    ? 'Tidak ada kelompok umur untuk ' + (DATA.jenjang[jenjang] || jenjang) + ' yang menerima lahir tahun ' + tahun + '.'
                    : 'Tahun lahir ' + tahun + ' tidak masuk kelompok umur mana pun.'
            };
        }
        return { ku: cocok, pesan: cocok.nama };
    }

    function cocokJenisKelamin(kategori, jk) {
        return kategori === 'mix' || (kategori === 'putra' && jk === 'L') || (kategori === 'putri' && jk === 'P');
    }

    function saringNomor(kartu) {
        var lahir = (kartu.querySelector('[data-isian="tanggal_lahir"]') || {}).value || '';
        var jk = (kartu.querySelector('[data-isian="jenis_kelamin"]') || {}).value || '';
        var keterangan = kartu.querySelector('.keterangan-ku');
        var hasil = /^\d{4}-\d{2}-\d{2}$/.test(lahir) ? tentukanKu(Number(lahir.slice(0, 4)), jenjangSekolah.value) : null;
        var kuId = hasil && hasil.ku ? hasil.ku.id : null;

        if (!hasil) {
            keterangan.textContent = 'Isi tanggal lahir dan jenis kelamin supaya pilihan nomor lomba yang cocok tampil.';
        } else if (!kuId) {
            keterangan.textContent = hasil.pesan + ' Periksa lagi tanggal lahirnya.';
        } else {
            keterangan.textContent = 'Kelompok umur: ' + hasil.pesan + (jk ? '' : '. Pilih jenis kelamin.');
        }

        $$('.pilihan-nomor', kartu).forEach(function (pilihan) {
            var kotak = pilihan.querySelector('input');
            var cocok = kuId !== null && Number(pilihan.getAttribute('data-ku')) === kuId &&
                jk !== '' && cocokJenisKelamin(pilihan.getAttribute('data-kategori'), jk);
            pilihan.hidden = !cocok && !kotak.checked;
        });

        $$('.kelompok-nomor', kartu).forEach(function (kelompok) {
            kelompok.hidden = $$('.pilihan-nomor', kelompok).every(function (p) { return p.hidden; });
        });
    }

    jenjangSekolah.addEventListener('change', function () {
        $$('.kartu-atlet', daftarAtlet).forEach(saringNomor);
    });

    $$('.kartu-pj, .kartu-atlet', formulir).forEach(siapkanKartu);

    // ------------------------------------------------------------------
    // Pratinjau, galat, dan jendela konfirmasi
    // ------------------------------------------------------------------

    function tokenCsrf() {
        return document.getElementById('csrf-ubah');
    }

    function bersihkanGalat() {
        kotakGalat.classList.add('d-none');
        kotakGalat.textContent = '';
        $$('.is-invalid', formulir).forEach(function (el) { el.classList.remove('is-invalid'); });
        $$('.galat-js', formulir).forEach(function (el) { el.remove(); });
    }

    // Kotak isian dari jalur galat server, misalnya `atlet.2.nisn`.
    function kotakDariJalur(jalur) {
        var bagian = jalur.split('.');
        var kartu = null;

        if (bagian[0] === 'sekolah' && bagian[1]) {
            return [formulir.querySelector('#judul-sekolah').closest('section').querySelector('[data-isian="' + bagian[1] + '"]')];
        }
        if (bagian[0] === 'atlet' && bagian.length >= 2) {
            kartu = $$('.kartu-atlet', daftarAtlet)[Number(bagian[1])];
        }
        if (bagian[0] === 'penanggung_jawab' && bagian.length >= 2) {
            kartu = $$('.kartu-pj', daftarPj)[Number(bagian[1])];
        }
        if (!kartu || !bagian[2]) {
            return [];
        }
        return $$('[data-isian="' + bagian[2] + '"]', kartu);
    }

    function tampilkanGalat(galat) {
        bersihkanGalat();

        var judul = document.createElement('p');
        judul.className = 'fw-semibold mb-1';
        judul.textContent = galat.length === 1 ? 'Ada 1 isian yang perlu diperbaiki:' : 'Ada ' + galat.length + ' isian yang perlu diperbaiki:';
        var daftar = document.createElement('ul');
        daftar.className = 'mb-0 ps-3';

        galat.forEach(function (g) {
            var butir = document.createElement('li');
            butir.textContent = g.pesan;
            daftar.appendChild(butir);

            var kotak = kotakDariJalur(String(g.jalur || '')).filter(Boolean);
            kotak.forEach(function (el) { el.classList.add('is-invalid'); });

            if (kotak.length > 0 && kotak[0].type !== 'checkbox') {
                var pesan = document.createElement('div');
                pesan.className = 'invalid-feedback d-block galat-js';
                pesan.textContent = g.pesan;
                kotak[0].insertAdjacentElement('afterend', pesan);
            }
        });

        kotakGalat.appendChild(judul);
        kotakGalat.appendChild(daftar);
        kotakGalat.classList.remove('d-none');
        kotakGalat.focus();
        kotakGalat.scrollIntoView({ block: 'start' });
    }

    function tampilkanPesan(teks) {
        tampilkanGalat([{ jalur: '', pesan: teks }]);
    }

    function konfirmasi(judul, kalimat, labelTombol) {
        return new Promise(function (selesai) {
            if (!window.bootstrap || !window.bootstrap.Modal) {
                selesai(window.confirm(kalimat));
                return;
            }

            var elemen = document.getElementById('jendela-ubah');
            if (!elemen) {
                elemen = document.createElement('div');
                elemen.className = 'modal fade';
                elemen.id = 'jendela-ubah';
                elemen.tabIndex = -1;
                elemen.setAttribute('aria-labelledby', 'jendela-ubah-judul');
                elemen.setAttribute('aria-describedby', 'jendela-ubah-isi');
                elemen.setAttribute('aria-hidden', 'true');
                elemen.innerHTML =
                    '<div class="modal-dialog modal-dialog-centered"><div class="modal-content">' +
                    '<div class="modal-header"><h2 class="modal-title h5" id="jendela-ubah-judul"></h2>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>' +
                    '<div class="modal-body"><p class="mb-0" id="jendela-ubah-isi"></p></div>' +
                    '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>' +
                    '<button type="button" class="btn btn-primary" data-setuju></button>' +
                    '</div></div></div>';
                document.body.appendChild(elemen);
            }

            var jendela = window.bootstrap.Modal.getOrCreateInstance(elemen);
            var setuju = false;
            var tombol = elemen.querySelector('[data-setuju]');

            elemen.querySelector('#jendela-ubah-judul').textContent = judul;
            elemen.querySelector('#jendela-ubah-isi').textContent = kalimat;
            tombol.textContent = labelTombol;
            tombol.onclick = function () {
                setuju = true;
                jendela.hide();
            };
            elemen.addEventListener('hidden.bs.modal', function tutup() {
                elemen.removeEventListener('hidden.bs.modal', tutup);
                selesai(setuju);
            });
            jendela.show();
        });
    }

    function pratinjau() {
        var isi = new FormData(formulir);
        isi.set('pratinjau', '1');

        return fetch(formulir.action, {
            method: 'POST',
            body: isi,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (jawaban) {
            return jawaban.json().catch(function () { return null; });
        }, function () {
            return undefined;
        });
    }

    formulir.addEventListener('submit', function (peristiwa) {
        if (formulir.dataset.disetujui === '1') {
            return;
        }

        peristiwa.preventDefault();

        if (sedangMengirim) {
            return;
        }

        sedangMengirim = true;
        var tombolSimpan = document.getElementById('simpan-ubah');
        tombolSimpan.disabled = true;

        pratinjau().then(function (isi) {
            sedangMengirim = false;
            tombolSimpan.disabled = false;

            if (isi === undefined) {
                tampilkanPesan('Belum tersambung ke server. Periksa sambungan internet, lalu tekan Simpan perubahan lagi.');
                return;
            }
            if (!isi) {
                tampilkanPesan('Halaman ini sudah terlalu lama terbuka. Muat ulang halaman, lalu ubah lagi.');
                return;
            }

            var token = tokenCsrf();
            if (token && isi.csrf) {
                token.value = isi.csrf;
            }

            if (isi.galat && isi.galat.length > 0) {
                tampilkanGalat(isi.galat);
                return;
            }

            bersihkanGalat();

            konfirmasi(isi.judul || 'Simpan perubahan?', isi.kalimat || '', 'Simpan perubahan').then(function (setuju) {
                if (!setuju) {
                    return;
                }
                formulir.querySelector('input[name="setuju_menunggu"]').value = isi.kembali_menunggu ? '1' : '0';
                formulir.dataset.disetujui = '1';
                tombolSimpan.disabled = true;
                formulir.submit();
            });
        });
    });
}());
