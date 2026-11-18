/**
 * Pemeriksaan keamanan Cloudflare Turnstile di langkah 5 (K122).
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

    // ------------------------------------------------------------------
    // Pemeriksaan keamanan Cloudflare Turnstile (K122)
    // ------------------------------------------------------------------
    //
    // Hanya bila `DATA.turnstile` terisi (kunci di .env server). Widget dipasang
    // saat langkah 5 pertama kali tampil. Token sekali pakai: setelah setiap
    // kiriman yang tidak berhasil, widget di-reset supaya kirim ulang (juga
    // kirim ulang B2) memakai token baru.

    var turnstileId = null;
    var turnstileToken = '';
    var turnstileCoba = 0;

    function pasangTurnstile() {
        var wadah = $('#turnstile-kirim');
        if (!DATA.turnstile || !wadah || turnstileId !== null) {
            return;
        }
        if (!window.turnstile || typeof window.turnstile.render !== 'function') {
            // Skrip Cloudflare dimuat async; coba lagi sebentar (paling lama sekitar 30 detik).
            if (turnstileCoba++ < 150) {
                window.setTimeout(pasangTurnstile, 200);
            }
            return;
        }
        turnstileId = window.turnstile.render(wadah, {
            sitekey: DATA.turnstile.kunci_situs,
            action: DATA.turnstile.aksi,
            language: 'id',
            callback: function (token) { turnstileToken = String(token || ''); },
            'expired-callback': function () { turnstileToken = ''; },
            'error-callback': function () { turnstileToken = ''; }
        });
    }

    function resetTurnstile() {
        turnstileToken = '';
        if (turnstileId !== null && window.turnstile) {
            window.turnstile.reset(turnstileId);
        }
    }

    /** Token terakhir dari widget; kosong bila belum selesai, kedaluwarsa, atau sudah di-reset. */
    function tokenTurnstile() {
        return turnstileToken;
    }

    // Dipakai bagian lain.
    W.pasangTurnstile = pasangTurnstile;
    W.resetTurnstile = resetTurnstile;
    W.tokenTurnstile = tokenTurnstile;
}(window.Pendaftaran));
