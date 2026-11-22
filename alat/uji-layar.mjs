// Uji tampilan videotron (F9-13, K141, F11-09, K173) dengan Playwright (Chromium).
//
// Menyajikan public/ lewat server Node kecil dengan CSP seketat aplikasi
// (Config/ContentSecurityPolicy.php), memuat kerangka yang sama dengan
// app/Views/layar/index.php berisi data karangan yang panjang: papan putar
// (/layar/putar) dan tampilan tetap (/layar/<jenis>/…: hasil dan daftar start
// beberapa nomor, klasemen, keadaan kosong). Untuk setiap ukuran layar semua
// halaman tampilan dibuka dan diperiksa:
// - tidak ada teks yang keluar dari layar, terpotong, atau saling tindih;
// - huruf tidak lebih kecil dari batas baca;
// - tanpa galat konsol dan tanpa pelanggaran CSP.
// Lalu tampilan tetap tidak berganti sendiri, `?halaman=` memilih halaman yang
// tampil, data baru tidak memindah halaman, dan susunan lebar memasang dua
// halaman berdampingan, tanpa jam, waktu pembaruan, dan nomor halaman. Terakhir satu uji lama berjalan: ribuan pergantian
// tampilan dan pembaruan data tanpa jumlah elemen dan memori JS yang terus naik.
//
// Jalankan: npm run uji:layar   (atau node alat/uji-layar.mjs)
// Butuh Chromium Playwright, atau Chrome lain lewat CHROMIUM_PATH (di CI: Chrome bawaan runner).
import { existsSync } from 'node:fs';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from 'playwright';

const akar = new URL('..', import.meta.url).pathname;
const publik = join(akar, 'public');

export const UKURAN = [
    [3840, 2160], [1920, 1080], [1280, 720], [1024, 768],
    [3840, 1080], [1920, 480], [1080, 1920], [512, 256],
];

const CSP = [
    "default-src 'self'", "script-src 'self'", "style-src 'self'", "style-src-attr 'self'",
    "img-src 'self' data: blob:", "connect-src 'self'", "font-src 'self'", "object-src 'none'", "base-uri 'self'",
].join('; ');

// ---------- Data karangan (nama, sekolah, NISN tidak ada yang asli) ----------
export function dataUji(versi = 'uji-1') {
    const namaDepan = ['Bagas', 'Citra', 'Dimas', 'Eka', 'Fajar', 'Gita', 'Hendra', 'Intan', 'Joko', 'Kartika', 'Lukas', 'Maya', 'Nanda', 'Oki', 'Putri', 'Raka'];
    const namaBelakang = ['Karangan', 'Rekaan Sejahtera', 'Contoh', 'Andai Wijayakusuma Permatasari', 'Fiksi'];
    const sekolah = ['SD KARANGAN MAJU 1 KUDUS', 'SD NEGERI REKAAN BERSINAR HARAPAN BANGSA KECAMATAN CONTOH', 'MI CONTOH', 'SD FIKSI', 'SMP KARANGAN TERPADU', 'SMA REKAAN'];
    const hasil = [];
    const nomor = ['Lari 60 m putra', 'Lompat jauh tanpa awalan putri', 'Kanga\'s Escape mixed', 'Trilomba putra', 'Lari 4 × 50 m estafet putri', 'Turbo Throw putra'];
    nomor.forEach((n, i) => {
        const beregu = n.includes('Kanga') || n.includes('estafet');
        const jumlah = [16, 24, 8, 30, 12, 3][i];
        hasil.push({
            id: i + 1, nomor: n, kelompok_umur: 'KU ' + (10 + (i % 3) * 2), babak: i === 2 ? 'Penyisihan seri 2' : 'Final',
            beregu, satuan: n.includes('Lompat') || n.includes('Throw') ? 'm' : n.includes('Trilomba') ? 'poin' : '',
            baris: Array.from({ length: jumlah }, (_, j) => ({
                peringkat: j === jumlah - 1 ? null : j + 1,
                nama: beregu ? sekolah[j % sekolah.length] + (j > 5 ? ' – Regu B' : '') : namaDepan[j % namaDepan.length] + ' ' + namaBelakang[(j + i) % namaBelakang.length],
                sekolah: sekolah[(j + i) % sekolah.length],
                nomor_dada: beregu ? null : String(1000 + j * 7 + i),
                prestasi: j === jumlah - 1 ? '' : n.includes('Trilomba') ? String(2400 - j * 37) : n.includes('estafet') ? '1:0' + (j % 10) + ',2' + (j % 10) : (9 + j / 10).toFixed(2).replace('.', ','),
                keterangan: j === jumlah - 1 ? 'DNS' : null,
            })),
        });
    });
    const klasemen = [['ku-1-2', 'KU 8 dan KU 10', 14], ['ku-3', 'KU 12', 6], ['ku-4', 'KU 15', 3]];
    const juara_umum = klasemen.map(([kunci, label, n]) => ({
        kunci, label,
        baris: Array.from({ length: n }, (_, j) => ({
            peringkat: j + 1, juara_umum: j < 3 ? j + 1 : null, sekolah: sekolah[j % sekolah.length] + (j > 5 ? ' ' + j : ''),
            emas: Math.max(0, 9 - j), perak: (j * 3) % 7, perunggu: (j * 5) % 6, jumlah: 20 - j,
        })),
    }));
    const babak = (j, hari) => ({
        id: j + 1, nomor: nomor[j % nomor.length], kelompok_umur: 'KU ' + (10 + (j % 3) * 2), babak: j % 4 === 0 ? 'Final' : 'Penyisihan seri ' + ((j % 3) + 1),
        hari, jam: String(8 + Math.floor(j / 3)).padStart(2, '0') + '.' + ['00', '20', '40'][j % 3], lokasi: ['Lintasan utama', 'Bak lompat timur', 'Area lempar'][j % 3],
    });
    return {
        versi,
        acara: { nama: 'MilkLife Athletics Challenge', seri_tahun: 'Seri 2 – 2026' },
        pengaturan: { isi: ['hasil', 'juara_umum', 'berlangsung', 'jadwal'], lama: 12 },
        hasil,
        juara_umum,
        berlangsung: [babak(0, 'Hari ini'), babak(1, 'Hari ini'), babak(2, 'Hari ini')],
        jadwal: Array.from({ length: 24 }, (_, j) => babak(j + 3, j < 12 ? 'Hari ini' : j < 20 ? 'Besok' : 'Sabtu, 12 Desember')),
    };
}

// Tampilan tetap (TampilanVideotron::tampilan()): beberapa panel hasil dengan
// tanda lolos dan daftar start, satu klasemen, atau keadaan kosong.
export function dataTetap(jenis = 'sesi', versi = 'tetap-1') {
    const dasar = dataUji(versi);
    const data = {
        versi,
        acara: dasar.acara,
        judul: 'Sesi pagi · Rabu, 9 Desember 2026',
        pengaturan: { isi: ['hasil', 'start'], tetap: true },
        kosong: 'Hasil sesi ini tampil di sini setelah panitia menerbitkannya.',
        start: [],
        hasil: [],
        juara_umum: [],
    };
    if (jenis === 'sesi') {
        data.hasil = dasar.hasil.slice(0, 4).map((n, i) => ({
            ...n, kunci: 'babak:' + (i + 1), babak: i === 1 ? 'Penyisihan' : n.babak,
            baris: n.baris.map((b, j) => ({ ...b, tanda: i === 1 && j < 4 ? (j < 2 ? 'Q' : 'q') : null })),
        }));
        data.start = [{
            id: 9, kunci: 'start:9:2', nomor: 'Lari 60 m putri', kelompok_umur: 'KU 12', babak: 'Penyisihan · Seri 2', beregu: false, lintasan: true,
            baris: dasar.hasil[0].baris.slice(0, 8).map((b, j) => ({ lintasan: j + 1, nama: b.nama, sekolah: b.sekolah, nomor_dada: b.nomor_dada })),
        }];
    } else if (jenis === 'klasemen') {
        data.judul = 'Klasemen juara umum · SD/MI';
        data.pengaturan.isi = ['juara_umum'];
        data.juara_umum = [{ ...dasar.juara_umum[0], kunci: 'A', baris: [...dasar.juara_umum[0].baris, ...dasar.juara_umum[1].baris] }];
    }
    return data;
}

const HALAMAN_TETAP = { '/layar/tetap': () => dataTetap('sesi'), '/layar/tetap-klasemen': () => dataTetap('klasemen'), '/layar/tetap-kosong': () => dataTetap('kosong') };

// Gambar juara umum G9-06 yang berkasnya ada, seperti LayarController::GAMBAR_JUARA.
const GAMBAR_JUARA = { podium: 'podium.webp', 'medali-emas': 'medali-emas.webp', 'medali-perak': 'medali-perak.webp', 'medali-perunggu': 'medali-perunggu.webp' };

function halamanUji(data) {
    const attr = (s) => String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    const gambar = Object.entries(GAMBAR_JUARA)
        .filter(([, berkas]) => existsSync(join(publik, 'aset/gambar/layar', berkas)))
        .map(([kunci, berkas]) => ` data-gambar-${kunci}="/aset/gambar/layar/${berkas}"`).join('');
    // Sama dengan app/Views/layar/index.php, ditambah data-uji dan tanpa sumber jaringan.
    return `<!doctype html>
<html lang="id" class="h-full bg-primary-950">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Uji papan hasil</title><link rel="stylesheet" href="/aset/css/tw.css"></head>
<body class="h-full overflow-hidden bg-primary-950 font-sans text-white antialiased">
<div id="layar" class="fixed inset-0 overflow-hidden" data-layar data-uji data-sumber="/layar/data" data-segar="30" data-awal="${attr(JSON.stringify(data))}"${gambar}><h1 class="sr-only">Papan hasil</h1></div>
<button type="button" data-layar-penuh class="fixed end-3 top-3 z-30 hidden size-11 items-center justify-center rounded-lg bg-primary-900/80 text-white ring-1 ring-white/30 transition-opacity hover:bg-primary-800 focus:outline-none focus:ring-4 focus:ring-white/60" aria-label="Layar penuh"><svg class="ikon size-6"></svg><svg class="ikon hidden size-6"></svg></button>
<script type="module" src="/aset/js/layar.js"></script>
</body></html>`;
}

const JENIS = { '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.woff2': 'font/woff2', '.webp': 'image/webp', '.png': 'image/png' };

export function mulaiServer(data) {
    const server = createServer(async (minta, jawab) => {
        const alamat = new URL(minta.url, 'http://x');
        const kepala = { 'Content-Security-Policy': CSP };
        if (alamat.pathname === '/layar/putar' || HALAMAN_TETAP[alamat.pathname]) {
            jawab.writeHead(200, { ...kepala, 'Content-Type': 'text/html; charset=utf-8' });
            return jawab.end(halamanUji(HALAMAN_TETAP[alamat.pathname]?.() ?? data));
        }
        if (alamat.pathname === '/layar/data') {
            // Selama uji jaringan dianggap putus: data terakhir harus tetap tampil.
            jawab.writeHead(503, kepala);
            return jawab.end();
        }
        const jalur = normalize(join(publik, decodeURIComponent(alamat.pathname)));
        if (!jalur.startsWith(publik)) {
            jawab.writeHead(403);
            return jawab.end();
        }
        try {
            const isi = await readFile(jalur);
            jawab.writeHead(200, { ...kepala, 'Content-Type': JENIS[extname(jalur)] || 'application/octet-stream' });
            jawab.end(isi);
        } catch {
            jawab.writeHead(404, kepala);
            jawab.end();
        }
    });
    return new Promise((selesai) => server.listen(0, '127.0.0.1', () => selesai(server)));
}

// ---------- Pemeriksaan di halaman ----------
function periksaHalaman() {
    const masalah = [];
    const L = window.innerWidth;
    const T = window.innerHeight;
    const daun = [];
    for (const e of document.querySelectorAll('#layar *')) {
        if (e.closest('.sr-only, .invisible, .hidden') || e.matches('.sr-only')) continue;
        const gaya = getComputedStyle(e);
        if (gaya.display === 'none' || gaya.visibility === 'hidden') continue;
        const adaTeks = [...e.childNodes].some((n) => n.nodeType === 3 && n.textContent.trim());
        if (!adaTeks) continue;
        const r = e.getBoundingClientRect();
        if (r.width === 0 || r.height === 0) continue;
        const teks = e.textContent.trim().slice(0, 40);
        if (r.left < -1 || r.top < -1 || r.right > L + 1 || r.bottom > T + 1) masalah.push(`keluar layar: "${teks}"`);
        if (e.scrollWidth > e.clientWidth + 1 && gaya.display !== 'inline') masalah.push(`terpotong mendatar: "${teks}"`);
        if (parseFloat(gaya.fontSize) < 7.5) masalah.push(`huruf ${gaya.fontSize}: "${teks}"`);
        // Terpotong oleh induk yang menyembunyikan luapan.
        for (let p = e.parentElement; p && p.id !== 'layar'; p = p.parentElement) {
            const g = getComputedStyle(p);
            if (g.overflow !== 'visible' || g.overflowY !== 'visible') {
                const pr = p.getBoundingClientRect();
                if (r.top < pr.top - 1 || r.bottom > pr.bottom + 1 || r.left < pr.left - 1 || r.right > pr.right + 1) masalah.push(`terpotong induk: "${teks}"`);
                break;
            }
        }
        // Kotak teks sebenarnya (baris-baris teks), untuk uji tindih.
        const range = document.createRange();
        for (const n of e.childNodes) {
            if (n.nodeType !== 3 || !n.textContent.trim()) continue;
            range.selectNodeContents(n);
            for (const k of range.getClientRects()) if (k.width > 0.5 && k.height > 0.5) daun.push({ k, teks, e });
        }
    }
    // Tombol layar penuh (tampil saat tetikus bergerak) tidak boleh menutupi teks, kecuali di susunan kecil.
    const tombol = document.querySelector('[data-layar-penuh]');
    if (tombol && !tombol.classList.contains('hidden') && document.querySelector('#layar').dataset.susunan !== 'kecil') {
        const b = tombol.getBoundingClientRect();
        for (const { k: a, teks } of daun) {
            if (Math.min(a.right, b.right) - Math.max(a.left, b.left) > 1 && Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top) > 1) masalah.push(`tertutup tombol: "${teks}"`);
        }
    }
    for (let i = 0; i < daun.length; i++) {
        for (let j = i + 1; j < daun.length; j++) {
            const a = daun[i].k;
            const b = daun[j].k;
            const x = Math.min(a.right, b.right) - Math.max(a.left, b.left);
            const y = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);
            if (x > 1 && y > 1 && daun[i].e !== daun[j].e) masalah.push(`tindih: "${daun[i].teks}" × "${daun[j].teks}"`);
        }
    }
    return masalah;
}

async function ujiUkuran(peramban, alamat, [l, t], jalur = '/layar/putar') {
    const konteks = await peramban.newContext({ viewport: { width: l, height: t }, deviceScaleFactor: 1, reducedMotion: 'reduce' });
    const halaman = await konteks.newPage();
    const galat = [];
    halaman.on('console', (p) => {
        if (p.type() === 'error' && !p.text().includes('503')) galat.push('konsol: ' + p.text());
    });
    halaman.on('pageerror', (g) => galat.push('skrip: ' + g.message));
    await halaman.goto(alamat + jalur, { waitUntil: 'load' });
    await halaman.waitForFunction(() => window.layarUji && window.layarUji.jumlah() > 0);
    await halaman.evaluate(() => document.fonts.ready);
    await halaman.evaluate(() => { window.layarUji.susunUlang(); window.layarUji.kendali(); });
    const susunan = await halaman.evaluate(() => window.layarUji.susunan());
    const jumlah = await halaman.evaluate(() => window.layarUji.jumlah());
    const masalah = [];
    for (let i = 0; i < jumlah; i++) {
        await halaman.evaluate((n) => window.layarUji.tampilkan(n), i);
        for (const m of await halaman.evaluate(periksaHalaman)) masalah.push(`tampilan ${i + 1}: ${m}`);
    }
    if (process.env.LAYAR_GAMBAR) {
        await halaman.evaluate(() => window.layarUji.tampilkan(0));
        await halaman.screenshot({ path: join(process.env.LAYAR_GAMBAR, `${jalur.slice(1).replace(/\//g, '-')}-${l}x${t}.png`) });
    }
    // Jaringan putus (server uji menjawab 503): data terakhir tetap tampil dengan tanda.
    const putus = await halaman.evaluate(async () => {
        await window.layarUji.ambil();
        return { putus: window.layarUji.putus(), jumlah: window.layarUji.jumlah() };
    });
    if (!putus.putus || putus.jumlah !== jumlah) masalah.push('jaringan putus: data terakhir tidak tetap tampil');
    await konteks.close();
    return { susunan, jumlah, masalah: [...galat, ...masalah] };
}

/**
 * Tampilan tetap: tidak berganti sendiri walau `?lama=` pendek, `?halaman=`
 * memilih halaman, data baru tidak memindah halaman, dan susunan lebar
 * memasang dua halaman berdampingan.
 */
async function ujiTetap(peramban, alamat) {
    const masalah = [];
    const konteks = await peramban.newContext({ viewport: { width: 1920, height: 1080 }, reducedMotion: 'reduce' });
    const halaman = await konteks.newPage();
    await halaman.goto(alamat + '/layar/tetap?halaman=2&lama=3', { waitUntil: 'load' });
    await halaman.waitForFunction(() => window.layarUji && window.layarUji.jumlah() > 0);
    const awal = await halaman.evaluate(() => ({ tetap: window.layarUji.tetap(), indeks: window.layarUji.indeks(), jumlah: window.layarUji.jumlah(), judul: window.layarUji.judulPanel() }));
    if (!awal.tetap) masalah.push('pengaturan.tetap tidak terbaca');
    if (awal.jumlah < 3) masalah.push(`halaman terlalu sedikit untuk diuji: ${awal.jumlah}`);
    if (awal.indeks !== 1) masalah.push(`?halaman=2 membuka halaman ${awal.indeks + 1}`);
    // Tanpa jam, waktu pembaruan, dan nomor halaman (K173).
    const teksKaki = (await halaman.locator('#layar footer').innerText()).trim();
    if (teksKaki !== '') masalah.push(`kaki layar berisi teks: "${teksKaki}"`);
    if (/\d{2}\.\d{2}/.test(await halaman.locator('#layar header').innerText())) masalah.push('jam masih tampil di kepala');
    await halaman.waitForTimeout(4500);
    const sesudah = await halaman.evaluate(() => ({ indeks: window.layarUji.indeks(), judul: window.layarUji.judulPanel() }));
    if (sesudah.indeks !== 1 || sesudah.judul.join('|') !== awal.judul.join('|')) masalah.push('tampilan tetap berganti sendiri');
    // Data baru (versi lain, baris tambahan di panel terakhir) masuk tanpa memindah halaman.
    await halaman.evaluate((baru) => window.layarUji.terapkan(baru), (() => {
        const d = dataTetap('sesi', 'tetap-2');
        d.hasil[3].baris.push({ ...d.hasil[3].baris[0], nama: 'Atlet Susulan Rekaan', peringkat: null, prestasi: '', keterangan: 'DNF' });
        return d;
    })());
    const baru = await halaman.evaluate(() => ({ indeks: window.layarUji.indeks(), judul: window.layarUji.judulPanel() }));
    if (baru.indeks !== 1 || baru.judul.join('|') !== awal.judul.join('|')) masalah.push('data baru memindah halaman');
    // Panah kanan tetap bisa dipakai operator.
    await halaman.keyboard.press('ArrowRight');
    if ((await halaman.evaluate(() => window.layarUji.indeks())) !== 2) masalah.push('panah kanan tidak berpindah halaman');
    await konteks.close();

    const lebar = await peramban.newContext({ viewport: { width: 3840, height: 1080 }, reducedMotion: 'reduce' });
    const pita = await lebar.newPage();
    await pita.goto(alamat + '/layar/tetap', { waitUntil: 'load' });
    await pita.waitForFunction(() => window.layarUji && window.layarUji.jumlah() > 0);
    const panel = await pita.evaluate(() => document.querySelectorAll('#layar section[data-jenis]').length);
    if (panel !== 2) masalah.push(`susunan lebar memasang ${panel} panel, seharusnya 2`);
    await lebar.close();
    return masalah;
}

async function ujiLama(peramban, alamat) {
    const konteks = await peramban.newContext({ viewport: { width: 1920, height: 1080 } });
    const halaman = await konteks.newPage();
    await halaman.goto(alamat + '/layar/putar', { waitUntil: 'load' });
    await halaman.waitForFunction(() => window.layarUji && window.layarUji.jumlah() > 0);
    const cdp = await konteks.newCDPSession(halaman);
    const ukur = async () => {
        // Ukur di tampilan yang sama supaya jumlah baris tabel sebanding.
        await halaman.evaluate(() => window.layarUji.tampilkan(0));
        await cdp.send('HeapProfiler.collectGarbage');
        const { usedSize } = await cdp.send('Runtime.getHeapUsage');
        const elemen = await halaman.evaluate(() => document.getElementsByTagName('*').length);
        return { memori: usedSize, elemen };
    };
    const putar = (n) => halaman.evaluate(async (jumlah) => {
        const dasar = JSON.parse(JSON.stringify(window.__dataUji));
        for (let i = 0; i < jumlah; i++) {
            window.layarUji.tampilkan(i);
            if (i % 25 === 0) {
                dasar.versi = 'lama-' + i;
                window.layarUji.terapkan(JSON.parse(JSON.stringify(dasar)));
            }
            if (i % 50 === 0) await new Promise((r) => requestAnimationFrame(r));
        }
    }, n);
    await halaman.evaluate((d) => { window.__dataUji = d; }, dataUji());
    await putar(400);
    const awal = await ukur();
    await putar(2000);
    const akhir = await ukur();
    await konteks.close();
    const masalah = [];
    if (akhir.elemen > awal.elemen * 1.1 + 20) masalah.push(`elemen naik ${awal.elemen} → ${akhir.elemen}`);
    if (akhir.memori > awal.memori * 1.5 + 2_000_000) masalah.push(`memori naik ${awal.memori} → ${akhir.memori}`);
    return { awal, akhir, masalah };
}

async function utama() {
    const server = await mulaiServer(dataUji());
    const alamat = `http://127.0.0.1:${server.address().port}`;
    const peramban = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});
    let gagal = 0;
    try {
        for (const jalur of ['/layar/putar', ...Object.keys(HALAMAN_TETAP)]) {
            for (const ukuran of UKURAN) {
                const { susunan, jumlah, masalah } = await ujiUkuran(peramban, alamat, ukuran, jalur);
                const label = `${jalur} ${ukuran[0]}×${ukuran[1]} (${susunan}, ${jumlah} tampilan)`;
                if (masalah.length) {
                    gagal++;
                    console.log(`GAGAL ${label}`);
                    for (const m of [...new Set(masalah)].slice(0, 25)) console.log('  - ' + m);
                } else {
                    console.log(`lulus ${label}`);
                }
            }
        }
        const tetap = await ujiTetap(peramban, alamat);
        if (tetap.length) {
            gagal++;
            console.log('GAGAL tampilan tetap: ' + tetap.join('; '));
        } else {
            console.log('lulus tampilan tetap (tidak berganti sendiri, ?halaman=, data baru, dua panel di layar lebar)');
        }
        const lama = await ujiLama(peramban, alamat);
        if (lama.masalah.length) {
            gagal++;
            console.log('GAGAL berjalan lama: ' + lama.masalah.join('; '));
        } else {
            console.log(`lulus berjalan lama (elemen ${lama.awal.elemen} → ${lama.akhir.elemen}, memori ${(lama.awal.memori / 1e6).toFixed(1)} → ${(lama.akhir.memori / 1e6).toFixed(1)} MB)`);
        }
    } finally {
        await peramban.close();
        server.close();
    }
    if (gagal) {
        console.log(`${gagal} pemeriksaan gagal.`);
        process.exit(1);
    }
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    utama().catch((g) => {
        console.error(g);
        process.exit(1);
    });
}
