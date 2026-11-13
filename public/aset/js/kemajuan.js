/*
 * Bilah kemajuan tipis di atas halaman admin.
 *
 * Dipakai saat halaman memuat data (mis. tabel di tahap berikutnya):
 *
 *     Kemajuan.mulai();
 *     fetch(alamat).then(...).finally(Kemajuan.selesai);
 *
 * Beberapa pemuatan boleh berjalan bersamaan: bilah baru selesai setelah
 * setiap mulai() dibalas selesai(). Bilah tidak tampil sampai mulai()
 * dipanggil. Bila pengguna meminta gerak dikurangi, bilah langsung tampil
 * penuh tanpa animasi.
 *
 * Markup bilah ada di tata letak admin dan publik (#bilah-kemajuan). Selama
 * memuat, <main> diberi aria-busy="true".
 *
 * Bilah juga menyala sendiri (K89) saat formulir dikirim (yang tidak dibatalkan
 * skrip lain, misalnya jendela konfirmasi) dan saat tautan ke halaman lain di
 * situs ini dibuka. Tidak menyala untuk tautan tab baru, unduhan (atribut
 * `download`, alamat berakhiran /excel, /pdf, /unduh, /ekspor, atau bukti
 * PDF), dan tautan bertanda `data-tanpa-kemajuan`. Saat halaman dibuka lagi
 * dari riwayat (tombol kembali), bilah dimatikan.
 */
(function () {
    'use strict';

    var bilah = document.getElementById('bilah-kemajuan');
    var isi = bilah ? bilah.querySelector('.bilah-kemajuan-isi') : null;
    var utama = document.getElementById('isi');
    var gerakDikurangi = window.matchMedia
        && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    var jumlahBerjalan = 0;
    var nilai = 0;
    var pewaktuMerayap = null;
    var pewaktuSembunyi = null;

    function atur(persen) {
        nilai = Math.max(0, Math.min(100, persen));
        if (isi) {
            isi.style.width = nilai + '%';
        }
    }

    function merayap() {
        // Makin dekat ke ujung, makin pelan; berhenti di 90 % sampai selesai().
        if (nilai < 90) {
            atur(nilai + (90 - nilai) * 0.08);
        }
    }

    function mulai() {
        jumlahBerjalan += 1;
        if (!bilah || jumlahBerjalan > 1) {
            return;
        }

        window.clearTimeout(pewaktuSembunyi);
        bilah.classList.remove('selesai');
        bilah.hidden = false;
        if (utama) {
            utama.setAttribute('aria-busy', 'true');
        }

        if (gerakDikurangi) {
            atur(100);
            return;
        }

        atur(0);
        // Paksa peramban menggambar 0 % dulu supaya peralihan lebar terlihat.
        void bilah.offsetWidth;
        atur(15);
        pewaktuMerayap = window.setInterval(merayap, 200);
    }

    function selesai() {
        if (jumlahBerjalan === 0) {
            return;
        }
        jumlahBerjalan -= 1;
        if (!bilah || jumlahBerjalan > 0) {
            return;
        }

        window.clearInterval(pewaktuMerayap);
        atur(100);
        if (utama) {
            utama.removeAttribute('aria-busy');
        }

        pewaktuSembunyi = window.setTimeout(function () {
            bilah.classList.add('selesai');
            pewaktuSembunyi = window.setTimeout(function () {
                bilah.hidden = true;
                atur(0);
            }, gerakDikurangi ? 0 : 300);
        }, gerakDikurangi ? 0 : 200);
    }

    var BERKAS = /\/(excel|pdf|unduh|ekspor)(\/|$)|\/bukti\/[0-9a-f]{24}\//;

    function pindahHalaman() {
        // Bila halaman tidak berganti (mis. unduhan tak dikenali), jangan menggantung.
        mulai();
        window.setTimeout(function () {
            while (jumlahBerjalan > 0) {
                selesai();
            }
        }, 15000);
    }

    // Didengar di window supaya pendengar submit lain (konfirmasi) jalan lebih dulu.
    window.addEventListener('submit', function (e) {
        var formulir = e.target;
        if (e.defaultPrevented || !(formulir instanceof HTMLFormElement) || formulir.target === '_blank') {
            return;
        }
        if (formulir.method.toLowerCase() === 'get' && BERKAS.test(formulir.action)) {
            return;
        }
        pindahHalaman();
    });

    window.addEventListener('click', function (e) {
        var tautan = e.target.closest ? e.target.closest('a[href]') : null;
        if (!tautan || e.defaultPrevented || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) {
            return;
        }
        if (tautan.target === '_blank' || tautan.hasAttribute('download') || tautan.hasAttribute('data-tanpa-kemajuan')
            || tautan.origin !== window.location.origin || BERKAS.test(tautan.pathname)) {
            return;
        }
        // Lompat di halaman yang sama (#bagian) tidak memuat apa pun.
        if (tautan.pathname === window.location.pathname && tautan.search === window.location.search && tautan.hash) {
            return;
        }
        pindahHalaman();
    });

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            while (jumlahBerjalan > 0) {
                selesai();
            }
        }
    });

    window.Kemajuan = {
        mulai: mulai,
        selesai: selesai,
        /** Benar selama masih ada pemuatan yang berjalan. */
        sedangBerjalan: function () {
            return jumlahBerjalan > 0;
        }
    };
}());
