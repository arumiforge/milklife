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
 * Markup bilah ada di tata_letak/admin.php (#bilah-kemajuan). Selama memuat,
 * <main> diberi aria-busy="true".
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

    window.Kemajuan = {
        mulai: mulai,
        selesai: selesai,
        /** Benar selama masih ada pemuatan yang berjalan. */
        sedangBerjalan: function () {
            return jumlahBerjalan > 0;
        }
    };
}());
