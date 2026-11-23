// Uji tombol unduh ikon (F11-13, K177) dengan Playwright (Chromium).
//
// Menyajikan public/ lewat server Node kecil dengan CSP seketat aplikasi dan
// halaman uji berisi dua tombol unduh bermarkah sama dengan
// app/Views/komponen/tombol_unduh.php, ditambah aset/js/tombol-unduh.js.
// Diperiksa:
// - ponsel (iPhone 13, layar sentuh): ketukan pertama hanya membuka label dan
//   mengumumkan "Ketuk sekali lagi untuk mengunduh …", ketukan kedua
//   mengunduh; label tertutup lagi bila mengetuk di luar, membuka tombol unduh
//   lain, atau setelah 4 detik, lalu ketukan berikutnya kembali hanya membuka;
// - desktop (kursor): label tampil saat kursor di atasnya, klik langsung
//   mengunduh;
// - papan ketik: fokus menampilkan label, Enter dan Spasi langsung mengunduh;
// - tanpa galat konsol dan tanpa pelanggaran CSP.
//
// Jalankan: npm run uji:unduh   (atau node alat/uji-unduh.mjs)
// Butuh Chromium Playwright, atau Chrome lain lewat CHROMIUM_PATH (di CI: Chrome bawaan runner).
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import { extname, join, normalize } from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium, devices } from 'playwright';

const akar = new URL('..', import.meta.url).pathname;
const publik = join(akar, 'public');

const CSP = [
    "default-src 'self'", "script-src 'self'", "style-src 'self'", "style-src-attr 'self'",
    "img-src 'self' data: blob:", "connect-src 'self'", "font-src 'self'", "object-src 'none'", "base-uri 'self'",
].join('; ');

// Markah sama dengan komponen tombol_unduh.php (ikon disederhanakan).
function tombol(jenis, label, teks) {
    const warna = jenis === 'pdf' ? 'text-red-700' : 'text-green-700';
    return `<a href="/unduh/uji.${jenis === 'pdf' ? 'pdf' : 'xlsx'}" class="group/unduh relative inline-flex size-11 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-100 ${warna}" aria-label="${label}" data-tombol-unduh="${jenis}">`
        + '<svg class="ikon size-6" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><rect x="4" y="2" width="16" height="20" fill="currentColor"/></svg>'
        + `<span class="pointer-events-none absolute end-0 bottom-full z-30 mb-1.5 hidden whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs font-semibold text-white shadow-lg group-hover/unduh:block group-focus-visible/unduh:block group-active/unduh:block group-data-tampil-label/unduh:block" aria-hidden="true">${teks}</span></a>`;
}

const HALAMAN = `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Uji tombol unduh</title><link rel="stylesheet" href="/aset/css/tw.css"></head>
<body class="bg-white p-4 font-sans">
<h1 class="text-lg font-bold">Uji tombol unduh</h1>
<p id="luar" class="mt-4 p-4">Area di luar tombol.</p>
<div class="mt-16 flex justify-end gap-2">
${tombol('pdf', 'Unduh PDF hasil Lari 60 m putra KU 10', 'Unduh PDF')}
${tombol('excel', 'Unduh Excel hasil Lari 60 m putra KU 10', 'Unduh Excel')}
</div>
<script src="/aset/js/tombol-unduh.js"></script>
</body></html>`;

const JENIS = { '.css': 'text/css', '.js': 'text/javascript', '.svg': 'image/svg+xml', '.woff2': 'font/woff2' };

function mulaiServer() {
    const server = createServer(async (minta, jawab) => {
        const alamat = new URL(minta.url, 'http://x');
        const kepala = { 'Content-Security-Policy': CSP };
        if (alamat.pathname === '/') {
            jawab.writeHead(200, { ...kepala, 'Content-Type': 'text/html; charset=utf-8' });
            return jawab.end(HALAMAN);
        }
        if (alamat.pathname.startsWith('/unduh/')) {
            jawab.writeHead(200, { 'Content-Type': 'application/octet-stream', 'Content-Disposition': 'attachment; filename="uji.bin"' });
            return jawab.end('uji');
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

const tunggu = (md) => new Promise((r) => setTimeout(r, md));

async function bukaHalaman(peramban, alamat, opsi) {
    const konteks = await peramban.newContext({ ...opsi, acceptDownloads: true });
    const page = await konteks.newPage();
    const galat = [];
    const unduhan = [];
    page.on('console', (p) => { if (p.type() === 'error') galat.push(p.text()); });
    page.on('pageerror', (e) => galat.push(String(e)));
    page.on('download', (d) => unduhan.push(d.url()));
    await page.goto(alamat);
    return { konteks, page, galat, unduhan };
}

const pdf = '[data-tombol-unduh="pdf"]';
const excel = '[data-tombol-unduh="excel"]';
const labelTampil = (page, pilih) => page.locator(pilih + ' span[aria-hidden="true"]').isVisible();
const terbuka = (page, pilih) => page.locator(pilih).evaluate((el) => el.hasAttribute('data-tampil-label'));

export async function jalankan() {
    const masalah = [];
    const cek = (benar, pesan) => { if (!benar) masalah.push(pesan); };
    const server = await mulaiServer();
    const alamat = `http://127.0.0.1:${server.address().port}/`;
    const peramban = await chromium.launch(process.env.CHROMIUM_PATH ? { executablePath: process.env.CHROMIUM_PATH } : {});

    try {
        // ---------- Ponsel ----------
        {
            const { konteks, page, galat, unduhan } = await bukaHalaman(peramban, alamat, devices['iPhone 13']);
            cek(!(await labelTampil(page, pdf)), 'ponsel: label tampil sebelum diketuk');

            await page.tap(pdf);
            await tunggu(400);
            cek(unduhan.length === 0, 'ponsel: ketukan pertama langsung mengunduh');
            cek(await terbuka(page, pdf), 'ponsel: ketukan pertama tidak membuka label');
            cek(await labelTampil(page, pdf), 'ponsel: label tidak tampil setelah ketukan pertama');
            const wadahStatus = page.locator('[data-tombol-unduh-status][role="status"]');
            const status = (await wadahStatus.count()) ? await wadahStatus.textContent() : '(tidak ada)';
            cek(status === 'Ketuk sekali lagi untuk mengunduh PDF hasil Lari 60 m putra KU 10.', `ponsel: pengumuman salah: "${status}"`);

            await page.tap(pdf);
            await tunggu(400);
            cek(unduhan.length === 1 && unduhan[0].endsWith('/unduh/uji.pdf'), `ponsel: ketukan kedua tidak mengunduh (${unduhan.length})`);
            cek(!(await terbuka(page, pdf)), 'ponsel: label tetap terbuka setelah mengunduh');

            // Ketuk di luar menutup label.
            await page.tap(pdf);
            await page.tap('#luar');
            await tunggu(200);
            cek(!(await terbuka(page, pdf)), 'ponsel: ketuk di luar tidak menutup label');
            await page.tap(pdf);
            await tunggu(400);
            cek(unduhan.length === 1, 'ponsel: setelah ketuk di luar, ketukan berikutnya langsung mengunduh');

            // Membuka tombol unduh lain menutup yang pertama, dan tombol lain itu belum mengunduh.
            await page.tap(excel);
            await tunggu(400);
            cek(!(await terbuka(page, pdf)) && (await terbuka(page, excel)), 'ponsel: membuka tombol lain tidak menutup label pertama');
            cek(unduhan.length === 1, 'ponsel: ketukan pertama tombol lain langsung mengunduh');
            await page.tap(pdf);
            await tunggu(400);
            cek(unduhan.length === 1, 'ponsel: label pertama yang sudah tertutup langsung mengunduh');

            // Tanpa ketukan selama 4 detik label tertutup lagi.
            await tunggu(4300);
            cek(!(await terbuka(page, pdf)) && !(await labelTampil(page, pdf)), 'ponsel: label tidak tertutup setelah 4 detik');
            await page.tap(pdf);
            await tunggu(400);
            cek(unduhan.length === 1, 'ponsel: setelah label tertutup sendiri, ketukan langsung mengunduh');

            cek(await page.locator(pdf).getAttribute('aria-label') === 'Unduh PDF hasil Lari 60 m putra KU 10', 'ponsel: nama aksesibel berubah');
            cek(galat.length === 0, 'ponsel: galat konsol ' + galat.join('; '));
            await konteks.close();
        }

        // ---------- Desktop (kursor) ----------
        {
            const { konteks, page, galat, unduhan } = await bukaHalaman(peramban, alamat, { viewport: { width: 1280, height: 720 } });
            await page.hover(pdf);
            cek(await labelTampil(page, pdf), 'desktop: label tidak tampil saat kursor di atas tombol');
            await page.click(pdf);
            await tunggu(400);
            cek(unduhan.length === 1, `desktop: klik tidak langsung mengunduh (${unduhan.length})`);
            cek(!(await terbuka(page, pdf)), 'desktop: klik memasang data-tampil-label');

            // Papan ketik: fokus menampilkan label, Enter dan Spasi mengunduh.
            await page.mouse.click(5, 5);
            await page.keyboard.press('Tab');
            await page.keyboard.press('Tab');
            cek(await page.evaluate((p) => document.activeElement?.matches(p), excel), 'papan ketik: fokus tidak di tombol Excel');
            cek(await labelTampil(page, excel), 'papan ketik: fokus tidak menampilkan label');
            await page.keyboard.press('Enter');
            await tunggu(400);
            cek(unduhan.length === 2, `papan ketik: Enter tidak langsung mengunduh (${unduhan.length})`);
            await page.keyboard.press(' ');
            await tunggu(400);
            cek(unduhan.length === 3, `papan ketik: Spasi tidak langsung mengunduh (${unduhan.length})`);

            cek(galat.length === 0, 'desktop: galat konsol ' + galat.join('; '));
            await konteks.close();
        }
    } finally {
        await peramban.close();
        server.close();
    }
    return masalah;
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    const masalah = await jalankan();
    if (masalah.length) {
        console.error('Uji tombol unduh gagal:\n- ' + masalah.join('\n- '));
        process.exit(1);
    }
    console.log('Uji tombol unduh lulus: ponsel ketuk dua kali, desktop dan papan ketik langsung mengunduh.');
}
