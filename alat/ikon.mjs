// Membuat sprite SVG Lucide yang hanya berisi ikon yang dipakai (K140).
// Ikon dicari di app/Views/**/*.php dan public/aset/js/**/*.js dengan pola
// ikon('nama' …) di PHP dan data-ikon="nama" / ikonSvg('nama') di JS (komentar blok diabaikan),
// ditambah daftar tetap di IKON_DASAR. Hasil: public/aset/vendor/lucide/lucide.svg.
// Jalankan: npm run ikon
import { readdirSync, readFileSync, statSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { join } from 'node:path';

const akar = new URL('..', import.meta.url).pathname;

// Ikon yang dipakai komponen tanpa nama tertulis langsung (misalnya dipilih dari larik).
const IKON_DASAR = [
    'check', 'x', 'menu', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up',
    'circle-check', 'circle-alert', 'triangle-alert', 'info', 'circle-help', 'loader-circle',
    'search', 'external-link', 'arrow-right', 'pause', 'play', 'image',
];

function telusuri(folder, akhiran, hasil = []) {
    if (!existsSync(folder)) return hasil;
    for (const nama of readdirSync(folder)) {
        const jalur = join(folder, nama);
        if (statSync(jalur).isDirectory()) telusuri(jalur, akhiran, hasil);
        else if (akhiran.some((a) => nama.endsWith(a)) && !nama.includes('.min.')) hasil.push(jalur);
    }
    return hasil;
}

const nama = new Set(IKON_DASAR);
// PHP: pembantu global `ikon('nama')` (bukan `$ikon(...)` atau metode `->ikon(`).
const polaPhp = [/(?<![$\w>:])ikon\(\s*['"]([a-z0-9-]+)['"]/g, /\bdata-ikon="([a-z0-9-]+)"/g];
// JS: `ikonSvg('nama')` dan `data-ikon="nama"`; fungsi `ikon()` lama di wizard masih Bootstrap Icons.
const polaJs = [/\bikonSvg\(\s*['"]([a-z0-9-]+)['"]/g, /\bdata-ikon="([a-z0-9-]+)"/g];

const kumpulan = [
    [telusuri(join(akar, 'app/Views'), ['.php']), polaPhp],
    [telusuri(join(akar, 'app/Libraries'), ['.php']), polaPhp],
    [telusuri(join(akar, 'app/Helpers'), ['.php']), polaPhp],
    [telusuri(join(akar, 'public/aset/js'), ['.js']), polaJs],
];

// Nama di larik (`'ikon' => 'nama'`) hanya dibaca di berkas yang memakai pembantu
// `ikon()` baru, karena view lama menyimpan nama Bootstrap Icons dengan kunci yang sama.
const polaLarik = /['"]ikon['"]\s*=>\s*['"]([a-z0-9-]+)['"]/g;
const pakaiIkonBaru = /(?<![$\w>:])ikon\(/;
// Daftar nama biasa: tulis komentar baris `// ikon-sprite: nama-1 nama-2` di berkasnya.
const polaPenanda = /ikon-sprite:\s*([a-z0-9 -]+)/g;

for (const [daftar, pola] of kumpulan) {
    for (const berkas of daftar) {
        const mentah = readFileSync(berkas, 'utf8');
        const isi = mentah.replace(/\/\*[\s\S]*?\*\//g, '');
        for (const p of pola) for (const c of isi.matchAll(p)) nama.add(c[1]);
        if (berkas.endsWith('.php') && pakaiIkonBaru.test(isi)) {
            for (const c of isi.matchAll(polaLarik)) nama.add(c[1]);
        }
        for (const c of mentah.matchAll(polaPenanda)) {
            c[1].trim().split(/\s+/).forEach((n) => nama.add(n));
        }
    }
}

const hilang = [];
const simbol = [...nama].sort().map((n) => {
    const berkas = join(akar, 'node_modules/lucide-static/icons', n + '.svg');
    if (!existsSync(berkas)) {
        hilang.push(n);
        return '';
    }
    const svg = readFileSync(berkas, 'utf8');
    const isi = svg.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '').replace(/\s*\n\s*/g, '');
    return `<symbol id="${n}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${isi}</symbol>`;
});

if (hilang.length) {
    console.error('Ikon Lucide tidak ditemukan: ' + hilang.join(', '));
    process.exit(1);
}

mkdirSync(join(akar, 'public/aset/vendor/lucide'), { recursive: true });
writeFileSync(
    join(akar, 'public/aset/vendor/lucide/lucide.svg'),
    '<svg xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        + simbol.filter(Boolean).join('')
        + '</svg>\n',
);
console.log(`Sprite Lucide: ${simbol.filter(Boolean).length} ikon.`);
