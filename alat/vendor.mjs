// Menyalin berkas jadi pustaka tampilan dari node_modules ke public/aset/vendor/
// (K140, K141). Hasilnya di-commit supaya server produksi tidak butuh Node.
// Jalankan: npm run vendor
import { cpSync, mkdirSync, rmSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';

const akar = new URL('..', import.meta.url).pathname;
const nm = (j) => join(akar, 'node_modules', j);
const tujuan = (j) => join(akar, 'public/aset/vendor', j);

const salinan = [
    // JS komponen Flowbite yang dipasang templat Pro (carousel, laci, modal, dropdown, tab, tooltip,
    // akordeon, pemilih tanggal); pemilih tanggal sudah termasuk di flowbite.min.js.
    ['flowbite/dist/flowbite.min.js', 'flowbite/flowbite.min.js'],
    ['flowbite/LICENSE.md', 'flowbite/LICENSE-flowbite.md'],
    // Pemilih tanggal Flowbite termasuk di flowbite.min.js; panel admin memakai `js/pilih-tanggal.js` (K89, F9-17).
    ['flowbite-datepicker/LICENSE', 'flowbite/LICENSE-flowbite-datepicker'],
    // Three.js: modul ES; three.module.min.js mengimpor three.core.min.js dari folder yang sama.
    ['three/build/three.module.min.js', 'three/three.module.min.js'],
    ['three/build/three.core.min.js', 'three/three.core.min.js'],
    ['three/LICENSE', 'three/LICENSE'],
    // Huruf Inter (OFL), huruf templat Flowbite Pro, disimpan lokal.
    ['@fontsource-variable/inter/files/inter-latin-wght-normal.woff2', 'inter/inter-latin-wght-normal.woff2'],
    ['@fontsource-variable/inter/files/inter-latin-ext-wght-normal.woff2', 'inter/inter-latin-ext-wght-normal.woff2'],
    ['@fontsource-variable/inter/LICENSE', 'inter/LICENSE'],
    ['lucide-static/LICENSE', 'lucide/LICENSE'],
];

for (const folder of ['three', 'inter']) {
    rmSync(tujuan(folder), { recursive: true, force: true });
}

for (const [dari, ke] of salinan) {
    mkdirSync(dirname(tujuan(ke)), { recursive: true });
    cpSync(nm(dari), tujuan(ke));
}

const versi = (p) => JSON.parse(readFileSync(nm(p + '/package.json'), 'utf8')).version;
writeFileSync(
    tujuan('VERSI-TAMPILAN.txt'),
    ['flowbite', 'flowbite-datepicker', 'three', '@fontsource-variable/inter', 'lucide-static', 'tailwindcss']
        .map((p) => `${p} ${versi(p)}`)
        .join('\n') + '\n',
);

console.log('Pustaka tampilan disalin ke public/aset/vendor/.');
