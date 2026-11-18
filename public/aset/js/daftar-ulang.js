/*
 * Pemindai kode QR bukti pendaftaran di halaman Daftar ulang (F8-03).
 *
 * Memakai `BarcodeDetector` bawaan peramban (Chrome di Android), tanpa
 * pustaka. Bila peramban tidak punya fitur itu atau kamera tidak bisa
 * dibuka, admin mengetik ID pendaftaran di kotak pencarian.
 *
 * Isi kode QR (alamat bukti `…/bukti/<kode>/<ID>`) dimasukkan apa adanya ke
 * kotak `id`, lalu formulir pencarian dikirim; server yang mengambil ID-nya
 * (`App\Libraries\HariLomba\KodeDaftarUlang`).
 */
(function () {
    'use strict';

    var pemindai = document.getElementById('pemindai');
    var tanpa = document.getElementById('tanpa-pemindai');
    var formulir = document.getElementById('formulir-cari');

    if (!pemindai || !formulir) {
        return;
    }

    var bisa = 'BarcodeDetector' in window && navigator.mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function';

    if (!bisa) {
        return;
    }

    var tombol = document.getElementById('tombol-pindai');
    var tombolBerhenti = document.getElementById('tombol-berhenti');
    var bingkai = document.getElementById('bingkai-kamera');
    var video = document.getElementById('video-pindai');
    var status = document.getElementById('status-pindai');
    var isian = document.getElementById('isian-id');
    var aliran = null;
    var pendeteksi = null;
    var berjalan = false;

    pemindai.hidden = false;
    tanpa.hidden = true;

    function tulis(pesan) {
        status.textContent = pesan;
    }

    function berhenti() {
        berjalan = false;

        if (aliran) {
            aliran.getTracks().forEach(function (jalur) { jalur.stop(); });
            aliran = null;
        }

        video.srcObject = null;
        bingkai.hidden = true;
        tombolBerhenti.hidden = true;
        tombol.hidden = false;
    }

    function periksa() {
        if (!berjalan) {
            return;
        }

        pendeteksi.detect(video).then(function (hasil) {
            if (!berjalan) {
                return;
            }

            if (hasil.length > 0 && hasil[0].rawValue) {
                isian.value = hasil[0].rawValue;
                berhenti();
                tulis('Kode QR terbaca. Membuka pendaftaran…');

                if (window.Kemajuan) {
                    window.Kemajuan.mulai();
                }

                formulir.submit();

                return;
            }

            window.requestAnimationFrame(periksa);
        }).catch(function () {
            window.setTimeout(periksa, 300);
        });
    }

    function mulai() {
        BarcodeDetector.getSupportedFormats().then(function (format) {
            if (format.indexOf('qr_code') === -1) {
                throw new Error('qr');
            }

            pendeteksi = new BarcodeDetector({ formats: ['qr_code'] });

            return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
        }).then(function (sumber) {
            aliran = sumber;
            video.srcObject = sumber;
            bingkai.hidden = false;
            tombol.hidden = true;
            tombolBerhenti.hidden = false;
            tulis('Arahkan kamera ke kode QR di bukti pendaftaran.');

            return video.play();
        }).then(function () {
            berjalan = true;
            periksa();
        }).catch(function () {
            berhenti();
            pemindai.hidden = true;
            tanpa.hidden = false;
            tulis('');
            tanpa.textContent = 'Kamera tidak bisa dibuka atau peramban tidak bisa membaca kode QR. Izinkan kamera untuk situs ini, atau ketik ID pendaftaran di bawah.';
        });
    }

    tombol.addEventListener('click', mulai);
    tombolBerhenti.addEventListener('click', function () {
        berhenti();
        tulis('');
    });
    window.addEventListener('pagehide', berhenti);
}());
