/**
 * Papan videotron `/layar` (F9-13, K141). Modul ES, dimuat `app/Views/layar/index.php`.
 *
 * - Data awal dari atribut `data-awal`, lalu `fetch(data-sumber)` setiap
 *   `data-segar` detik. Bila jaringan putus atau server menolak, data
 *   terakhir tetap tampil dengan tanda "Sambungan terputus".
 * - Isi diputar per `pengaturan.lama` detik: hasil nomor yang baru terbit,
 *   juara umum per jenjang, sedang berlangsung, jadwal berikutnya (urutan dan
 *   pilihan dari Pengaturan > Videotron). Isi kosong dilewati.
 * - Tata letak cair untuk ukuran apa pun: satuan huruf `--u` memakai
 *   `clamp()` dari ukuran layar (kelas di `#layar`), dikali `?skala=`.
 *   Susunan dipilih dari rasio: `lebar` (≥ 2:1, hasil dan juara umum
 *   berdampingan), `datar` (16:9 sampai 4:3), `tegak` (≤ 3:4), `kecil`
 *   (sisi pendek < 400 px: nomor, nama, prestasi saja). `?susunan=` memaksa.
 *   Kolom tabel dipilih dari lebar panel (container query lewat
 *   `ResizeObserver`), baris per halaman dihitung dengan mengukur tinggi
 *   panel sebenarnya (atau `?baris=`); bila tetap tidak muat, huruf panel
 *   itu diperkecil sampai muat.
 * - Latar: adegan Three.js ringan (tanpa animasi bila kurangi gerak atau
 *   WebGL tidak ada), cadangannya potongan gambar G9-06 dari `data-latar-*`,
 *   lalu gradien.
 * - Satu `setInterval` (detak 1 detik) mengatur jam, putaran, dan pengambilan
 *   data; tidak ada pewaktu lain yang menumpuk, dan adegan dibuang saat
 *   halaman ditutup, jadi aman berjalan berjam-jam.
 * - Tombol layar penuh (juga tombol F), panah kiri/kanan berpindah tampilan.
 *
 * Kelas Tailwind ditulis utuh di berkas ini supaya ikut dipindai `npm run css`.
 */
const akar = document.querySelector('[data-layar]');

const SUSUNAN = ['lebar', 'datar', 'tegak', 'kecil'];
const LABEL = {
    hasil: 'Hasil',
    juara_umum: 'Juara umum',
    berlangsung: 'Sedang berlangsung',
    jadwal: 'Jadwal berikutnya',
};
const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function angka(nilai, min, maks) {
    const n = Number.parseFloat(nilai);
    return Number.isFinite(n) ? Math.min(maks, Math.max(min, n)) : null;
}

function el(tag, kelas = '', teks = null) {
    const e = document.createElement(tag);
    if (kelas) e.className = kelas;
    if (teks !== null && teks !== undefined) e.textContent = String(teks);
    return e;
}

function jamMenit(waktu) {
    return String(waktu.getHours()).padStart(2, '0') + '.' + String(waktu.getMinutes()).padStart(2, '0');
}

if (akar) {
    const param = new URLSearchParams(window.location.search);
    const paksa = {
        skala: angka(param.get('skala'), 0.5, 3) ?? 1,
        susunan: SUSUNAN.includes(param.get('susunan')) ? param.get('susunan') : null,
        baris: param.has('baris') ? Math.round(angka(param.get('baris'), 1, 60) ?? 0) || null : null,
        lama: param.has('lama') ? angka(param.get('lama'), 3, 600) : null,
    };
    const sumber = akar.dataset.sumber;
    const segarMs = (angka(akar.dataset.segar, 5, 600) ?? 30) * 1000;

    let data = null;
    try {
        data = JSON.parse(akar.dataset.awal || 'null');
    } catch {
        data = null;
    }
    akar.removeAttribute('data-awal');

    const keadaan = {
        susunan: 'datar',
        slide: [],
        indeks: 0,
        gantiPada: 0,
        ambilPada: Date.now() + segarMs,
        sedangAmbil: false,
        putus: false,
        diperbarui: new Date(),
        menitJam: '',
        diam: 0,
        ukurUlang: 0,
    };

    // ---------- Kerangka ----------
    akar.classList.add(
        '[--skala:1]',
        '[--u:calc(clamp(8px,1.6vmin+0.55vmax,84px)*var(--skala))]',
        'data-[susunan=kecil]:[--u:calc(clamp(10px,5.6vmin,48px)*var(--skala))]',
        'data-[susunan=tegak]:[--u:calc(clamp(9px,2.5vmin+0.3vmax,84px)*var(--skala))]',
    );
    if (paksa.skala !== 1) akar.style.setProperty('--skala', String(paksa.skala));

    const latar = el('div', 'absolute inset-0 bg-linear-to-br from-primary-950 via-primary-900 to-primary-700');
    const lapisGambar = el('div', 'absolute inset-0');
    latar.append(lapisGambar);

    const kerangka = el('div', 'relative z-10 flex h-full flex-col gap-[calc(var(--u)*0.55)] p-[calc(var(--u)*0.8)]');
    const kepala = el('header', 'flex shrink-0 items-center gap-[calc(var(--u)*0.8)] pe-[max(calc(var(--u)*2.6),4.5rem)] in-data-[susunan=kecil]:hidden');
    const merek = el('div', 'min-w-0 flex-1 leading-tight');
    const namaAcara = el('p', 'text-[length:calc(var(--u)*1.05)] font-extrabold wrap-anywhere');
    const seri = el('p', 'text-[length:calc(var(--u)*0.75)] font-semibold text-oranye-300 wrap-anywhere');
    merek.append(namaAcara, seri);
    const jam = el('p', 'shrink-0 text-[length:calc(var(--u)*1.25)] font-bold tabular-nums');
    jam.setAttribute('aria-label', 'Jam');
    kepala.append(merek, jam);

    const isi = el('main', 'flex min-h-0 flex-1 gap-[calc(var(--u)*0.7)]');
    isi.setAttribute('aria-live', 'off');

    const kaki = el('footer', 'flex shrink-0 items-center justify-between gap-[calc(var(--u)*0.6)] text-[length:calc(var(--u)*0.65)] text-primary-100');
    const titik = el('div', 'flex min-w-0 flex-wrap items-center gap-[calc(var(--u)*0.3)] in-data-[susunan=kecil]:hidden');
    const status = el('p', 'ms-auto rounded-full px-[calc(var(--u)*0.6)] py-[calc(var(--u)*0.1)] text-end tabular-nums wrap-anywhere');
    status.setAttribute('role', 'status');
    kaki.append(titik, status);
    kerangka.append(kepala, isi, kaki);

    // Pengukur satuan --u dalam piksel (lebarnya 1u).
    const pengukur = el('div', 'invisible absolute start-0 top-0 h-px w-[var(--u)]');
    // Kerangka muat dari server (F9-17) diganti papan yang sesungguhnya.
    akar.querySelector('[data-kerangka-layar]')?.remove();
    akar.append(latar, kerangka, pengukur);

    function satuan() {
        return pengukur.getBoundingClientRect().width || 16;
    }

    // ---------- Susunan ----------
    function pilihSusunan() {
        if (paksa.susunan) return paksa.susunan;
        const l = window.innerWidth;
        const t = window.innerHeight;
        if (Math.min(l, t) < 400) return 'kecil';
        const rasio = l / t;
        if (rasio >= 2) return 'lebar';
        if (rasio <= 0.75) return 'tegak';
        return 'datar';
    }

    function urutanIsi() {
        const isiData = data?.pengaturan?.isi;
        return Array.isArray(isiData) ? isiData.filter((k) => LABEL[k]) : Object.keys(LABEL);
    }

    function lamaTampil() {
        return (paksa.lama ?? angka(data?.pengaturan?.lama, 3, 600) ?? 12) * 1000;
    }

    // ---------- Panel dan tabel ----------
    const kelasSel = 'px-[calc(var(--u)*0.35)] py-[calc(var(--u)*0.22)] align-middle';
    const kelasJudulKolom = 'px-[calc(var(--u)*0.35)] pb-[calc(var(--u)*0.25)] text-start align-bottom text-[length:calc(var(--u)*0.62)] font-semibold uppercase tracking-wide text-primary-200';

    function lencanaPeringkat(peringkat) {
        const p = Number(peringkat);
        const warna = p === 1 ? 'bg-amber-300 text-primary-950'
            : p === 2 ? 'bg-slate-200 text-primary-950'
                : p === 3 ? 'bg-oranye-300 text-primary-950'
                    : 'bg-white/10 text-white';
        const e = el('span', 'inline-flex min-w-[calc(var(--u)*1.7)] items-center justify-center rounded-full px-[calc(var(--u)*0.25)] py-[calc(var(--u)*0.05)] font-extrabold tabular-nums ' + warna, peringkat ?? '–');
        return e;
    }

    function selTeks(utama, kedua, kelasUtama = 'font-semibold') {
        const td = el('td', kelasSel + ' wrap-anywhere');
        td.append(el('div', kelasUtama, utama));
        if (kedua) td.append(el('div', 'text-[length:calc(var(--u)*0.7)] text-primary-200', kedua));
        return td;
    }

    /**
     * Definisi kolom per jenis, menurut lebar panel dalam satuan u.
     * Setiap kolom: judul, kelas lebar (th), isi(baris) → <td>.
     */
    function kolomUntuk(jenis, butir, lebarU, susunan) {
        const kecil = susunan === 'kecil';
        if (jenis === 'hasil') {
            const beregu = !!butir.beregu;
            const dada = !kecil && !beregu && lebarU >= 46 && butir.baris.some((b) => b.nomor_dada);
            const sekolahSendiri = !kecil && !beregu && lebarU >= 38;
            const tumpuk = !kecil && !beregu && !sekolahSendiri;
            const kolom = [
                { judul: 'Per.', kelas: 'w-[calc(var(--u)*2.4)]', isi: (b) => { const td = el('td', kelasSel); td.append(lencanaPeringkat(b.peringkat)); return td; } },
            ];
            if (dada) kolom.push({ judul: 'No. atlet', kelas: 'w-[calc(var(--u)*4)]', isi: (b) => el('td', kelasSel + ' font-semibold tabular-nums text-primary-100', b.nomor_dada || '') });
            kolom.push({ judul: beregu ? 'Sekolah / regu' : 'Nama', kelas: '', isi: (b) => selTeks(b.nama, tumpuk ? b.sekolah : null) });
            if (sekolahSendiri) kolom.push({ judul: 'Sekolah', kelas: 'w-[38%]', isi: (b) => selTeks(b.sekolah, null, 'text-primary-100') });
            kolom.push({
                judul: butir.satuan ? 'Prestasi (' + butir.satuan + ')' : 'Prestasi',
                kelas: 'w-[calc(var(--u)*5.2)] text-end',
                isi: (b) => el('td', kelasSel + ' whitespace-nowrap text-end font-extrabold tabular-nums text-amber-200', b.prestasi || b.keterangan || '–'),
            });
            return kolom;
        }
        if (jenis === 'juara_umum') {
            const adaPoin = butir.baris.some((b) => b.poin !== undefined && b.poin !== null);
            const jumlah = !kecil && lebarU >= 30;
            const medali = [['emas', 'I', 'bg-amber-300'], ['perak', 'II', 'bg-slate-200'], ['perunggu', 'III', 'bg-oranye-300']];
            const kolom = [
                { judul: 'Per.', kelas: 'w-[calc(var(--u)*2.4)]', isi: (b) => { const td = el('td', kelasSel); td.append(lencanaPeringkat(b.peringkat)); return td; } },
                { judul: 'Sekolah', kelas: '', isi: (b) => selTeks(b.sekolah, null) },
            ];
            for (const [kunci, label, warna] of medali) {
                kolom.push({
                    judul: label, judulKelas: 'inline-flex min-w-[calc(var(--u)*1.5)] justify-center rounded-full text-primary-950 ' + warna,
                    judulPanjang: 'Juara ' + label,
                    kelas: 'w-[calc(var(--u)*2.3)] text-center',
                    isi: (b) => el('td', kelasSel + ' text-center font-bold tabular-nums', b[kunci] ?? 0),
                });
            }
            if (jumlah) kolom.push({ judul: 'Jml', judulPanjang: 'Jumlah', kelas: 'w-[calc(var(--u)*2.6)] text-center', isi: (b) => el('td', kelasSel + ' text-center tabular-nums text-primary-100', b.jumlah ?? '') });
            if (adaPoin) kolom.push({ judul: 'Poin', kelas: 'w-[calc(var(--u)*3.2)] text-end', isi: (b) => el('td', kelasSel + ' text-end font-extrabold tabular-nums text-amber-200', b.poin ?? '') });
            return kolom;
        }
        // jadwal dan berlangsung
        const adaLokasi = butir.baris.some((b) => b.lokasi);
        const lokasiSendiri = adaLokasi && !kecil && lebarU >= 40;
        const kolom = [
            {
                judul: 'Jam', kelas: 'w-[calc(var(--u)*4.4)]',
                isi: (b) => {
                    const td = el('td', kelasSel + ' tabular-nums');
                    if (b.hari && b.hari !== 'Hari ini') td.append(el('div', 'text-[length:calc(var(--u)*0.62)] text-primary-200 wrap-anywhere', b.hari));
                    td.append(el('div', 'font-extrabold text-amber-200', b.jam));
                    return td;
                },
            },
            {
                judul: 'Nomor lomba', kelas: '',
                isi: (b) => selTeks(b.nomor, kecil ? null : [b.kelompok_umur, b.babak, lokasiSendiri ? null : b.lokasi].filter(Boolean).join(' · ')),
            },
        ];
        if (lokasiSendiri) kolom.push({ judul: 'Lokasi', kelas: 'w-[30%]', isi: (b) => selTeks(b.lokasi || '', null, 'text-primary-100') });
        return kolom;
    }

    function buatPanel(halaman, info) {
        const panel = el('section', 'flex h-full min-h-0 flex-col gap-[calc(var(--u)*0.4)] overflow-hidden rounded-[calc(var(--u)*0.6)] bg-primary-950/80 p-[calc(var(--u)*0.7)] ring-1 ring-white/15 text-[length:var(--u)] leading-tight');
        panel.dataset.jenis = halaman.jenis;

        const kepalaPanel = el('div', 'flex shrink-0 flex-wrap items-center gap-x-[calc(var(--u)*0.6)] gap-y-[calc(var(--u)*0.2)]');
        const warnaLabel = halaman.jenis === 'berlangsung' ? 'bg-magenta-500 text-white'
            : halaman.jenis === 'juara_umum' ? 'bg-amber-300 text-primary-950'
                : halaman.jenis === 'jadwal' ? 'bg-langit-300 text-primary-950'
                    : 'bg-oranye-500 text-white';
        kepalaPanel.append(el('span', 'shrink-0 rounded-full px-[calc(var(--u)*0.55)] py-[calc(var(--u)*0.1)] text-[length:calc(var(--u)*0.62)] font-bold uppercase tracking-wide ' + warnaLabel, LABEL[halaman.jenis]));
        const judul = el('h2', 'min-w-0 flex-1 basis-[12em] text-[length:calc(var(--u)*1.3)] font-extrabold wrap-anywhere in-data-[susunan=kecil]:text-[length:calc(var(--u)*1.05)]', halaman.judul);
        if (halaman.sub) {
            judul.append(' ');
            judul.append(el('span', 'text-[length:calc(var(--u)*0.8)] font-semibold text-primary-200', halaman.sub));
        }
        kepalaPanel.append(judul);
        if (info.halaman) kepalaPanel.append(el('span', 'ms-auto shrink-0 text-[length:calc(var(--u)*0.65)] font-semibold tabular-nums text-primary-200', info.halaman));
        panel.append(kepalaPanel);

        const badan = el('div', 'min-h-0 flex-1 overflow-hidden');
        if (halaman.kosong) {
            badan.className = 'flex min-h-0 flex-1 items-center justify-center overflow-hidden text-center';
            badan.append(el('p', 'max-w-[30em] text-[length:calc(var(--u)*1.1)] font-semibold text-primary-100 wrap-anywhere', halaman.kosong));
            panel.append(badan);
            return { panel, badan, tbody: null };
        }

        const tabel = el('table', 'w-full table-fixed border-collapse');
        const kepalaTabel = el('thead');
        const barisKepala = el('tr');
        for (const k of info.kolom) {
            const th = el('th', kelasJudulKolom + ' ' + k.kelas);
            th.scope = 'col';
            if (k.judulKelas) {
                const s = el('span', k.judulKelas, k.judul);
                s.title = k.judulPanjang || k.judul;
                th.append(s);
            } else {
                th.textContent = k.judul;
            }
            if (k.judulPanjang) th.setAttribute('aria-label', k.judulPanjang);
            barisKepala.append(th);
        }
        kepalaTabel.append(barisKepala);
        const tbody = el('tbody');
        tabel.append(kepalaTabel, tbody);
        badan.append(tabel);
        panel.append(badan);
        return { panel, badan, tbody };
    }

    function barisTabel(kolom, b) {
        const tr = el('tr', 'odd:bg-white/6');
        for (const k of kolom) tr.append(k.isi(b));
        return tr;
    }

    function meluap(e) {
        return e.scrollHeight > e.clientHeight + 1 || e.scrollWidth > e.clientWidth + 1;
    }

    /**
     * Pecah satu butir isi (misalnya satu nomor) menjadi halaman yang muat di
     * slot, dengan mengisi baris satu per satu di panel ukur yang tak terlihat.
     */
    function pecah(slot, butir) {
        const lebarU = slot.clientWidth / satuan();
        const kolom = butir.kosong ? [] : kolomUntuk(butir.jenis, butir, lebarU, keadaan.susunan);
        const baris = butir.baris || [];
        const kelompok = [];

        if (butir.kosong) {
            kelompok.push([]);
        } else if (paksa.baris) {
            for (let i = 0; i < baris.length; i += paksa.baris) kelompok.push(baris.slice(i, i + paksa.baris));
        } else {
            const ukur = el('div', 'invisible absolute inset-0');
            slot.append(ukur);
            let mulai = 0;
            while (mulai < baris.length) {
                const { panel, badan, tbody } = buatPanel(butir, { kolom, halaman: '99/99' });
                ukur.replaceChildren(panel);
                let n = 0;
                for (let i = mulai; i < baris.length; i++) {
                    tbody.append(barisTabel(kolom, baris[i]));
                    if (badan.scrollHeight > badan.clientHeight + 1) {
                        tbody.lastChild.remove();
                        break;
                    }
                    n++;
                }
                n = Math.max(1, n);
                kelompok.push(baris.slice(mulai, mulai + n));
                mulai += n;
            }
            ukur.remove();
        }

        if (!kelompok.length) kelompok.push([]);
        return kelompok.map((isiBaris, i) => ({
            ...butir,
            baris: isiBaris,
            kolom,
            halaman: kelompok.length > 1 ? (i + 1) + '/' + kelompok.length : '',
            kunci: butir.kunci + ':' + i,
        }));
    }

    /** Butir isi per jenis dari data, sebelum dipecah per halaman. */
    function butirIsi(jenis) {
        if (!data) return [];
        if (jenis === 'hasil') {
            return (data.hasil || []).filter((n) => n.baris?.length).map((n) => ({
                jenis, kunci: 'hasil:' + n.id, judul: n.nomor, sub: [n.kelompok_umur, n.babak].filter(Boolean).join(' · '),
                beregu: n.beregu, satuan: n.satuan, baris: n.baris,
            }));
        }
        if (jenis === 'juara_umum') {
            return (data.juara_umum || []).filter((j) => j.baris?.length).map((j) => ({
                jenis, kunci: 'juara:' + j.jenjang, judul: j.label, sub: '', baris: j.baris,
            }));
        }
        const daftar = data[jenis];
        if (!Array.isArray(daftar) || !daftar.length) return [];
        return [{ jenis, kunci: jenis, judul: jenis === 'jadwal' ? 'Babak berikutnya' : 'Babak yang sedang berjalan', sub: '', baris: daftar }];
    }

    function buatSlot(kelasTambahan = '') {
        const slot = el('div', 'relative min-h-0 min-w-0 flex-1 ' + kelasTambahan);
        isi.append(slot);
        return slot;
    }

    /** Susun ulang semua tampilan (data baru atau ukuran layar berubah). */
    function susun() {
        const kunciSekarang = keadaan.slide[keadaan.indeks]?.map((h) => h.kunci).join('|');
        keadaan.susunan = pilihSusunan();
        akar.dataset.susunan = keadaan.susunan;
        isi.className = 'flex min-h-0 flex-1 gap-[calc(var(--u)*0.7)]';
        isi.replaceChildren();

        namaAcara.textContent = data?.acara?.nama || 'Papan hasil';
        seri.textContent = data?.acara?.seri_tahun || '';

        const urutan = urutanIsi();
        const kiri = [];
        const kanan = [];
        const berdampingan = keadaan.susunan === 'lebar' && urutan.includes('juara_umum') && butirIsi('juara_umum').length > 0
            && urutan.some((j) => j !== 'juara_umum' && butirIsi(j).length > 0);

        const slotKiri = buatSlot();
        const slotKanan = berdampingan ? buatSlot('max-w-[46%]') : null;

        for (const jenis of urutan) {
            for (const butir of butirIsi(jenis)) {
                if (berdampingan && jenis === 'juara_umum') kanan.push(...pecah(slotKanan, butir));
                else kiri.push(...pecah(slotKiri, butir));
            }
        }

        if (!kiri.length && !kanan.length) {
            kiri.push(...pecah(slotKiri, {
                jenis: 'hasil', kunci: 'kosong', judul: data?.acara?.nama || 'Papan hasil', sub: '',
                kosong: 'Hasil lomba akan tampil di sini setelah diterbitkan panitia.',
            }));
        }

        const jumlah = Math.max(kiri.length, kanan.length);
        keadaan.slide = [];
        for (let i = 0; i < jumlah; i++) {
            const s = [];
            if (kiri.length) s.push(kiri[i % kiri.length]);
            if (kanan.length) s.push(kanan[i % kanan.length]);
            keadaan.slide.push(s);
        }
        keadaan.slot = berdampingan ? [slotKiri, slotKanan] : [slotKiri];

        const lama = keadaan.slide.findIndex((s) => s.map((h) => h.kunci).join('|') === kunciSekarang);
        tampilkan(lama >= 0 ? lama : Math.min(keadaan.indeks, keadaan.slide.length - 1), false);
    }

    function pasPanel(panel, badan) {
        // Bila masih meluap (misalnya `?baris=` terlalu banyak), perkecil huruf panel ini.
        const u = satuan();
        let skala = 1;
        while ((meluap(badan) || meluap(panel)) && skala > 0.45) {
            skala -= 0.05;
            panel.style.setProperty('--u', (u * skala).toFixed(2) + 'px');
        }
    }

    function tampilkan(indeks, animasi = true) {
        const jumlah = keadaan.slide.length;
        if (!jumlah) return;
        keadaan.indeks = ((indeks % jumlah) + jumlah) % jumlah;
        const slide = keadaan.slide[keadaan.indeks];

        slide.forEach((halaman, i) => {
            const slot = keadaan.slot[i];
            if (!slot) return;
            const { panel, badan, tbody } = buatPanel(halaman, { kolom: halaman.kolom, halaman: halaman.halaman });
            if (tbody) for (const b of halaman.baris) tbody.append(barisTabel(halaman.kolom, b));
            const bungkus = el('div', 'absolute inset-0' + (animasi && !kurangiGerak ? ' opacity-0 transition-opacity duration-500' : ''));
            bungkus.append(panel);
            slot.replaceChildren(bungkus);
            pasPanel(panel, badan);
            if (animasi && !kurangiGerak) requestAnimationFrame(() => bungkus.classList.remove('opacity-0'));
        });

        // Titik penanda tampilan (paling banyak 40).
        titik.replaceChildren();
        if (jumlah > 1 && jumlah <= 40) {
            for (let i = 0; i < jumlah; i++) {
                titik.append(el('span', 'block size-[calc(var(--u)*0.45)] rounded-full ' + (i === keadaan.indeks ? 'bg-amber-300' : 'bg-white/30')));
            }
        } else if (jumlah > 40) {
            titik.append(el('span', 'tabular-nums', (keadaan.indeks + 1) + ' / ' + jumlah));
        }

        keadaan.gantiPada = Date.now() + lamaTampil();
    }

    // ---------- Data ----------
    function tulisStatus() {
        const waktu = 'Diperbarui ' + jamMenit(keadaan.diperbarui);
        status.textContent = keadaan.putus ? 'Sambungan terputus · data terakhir ' + jamMenit(keadaan.diperbarui) : waktu;
        status.className = 'ms-auto rounded-full px-[calc(var(--u)*0.6)] py-[calc(var(--u)*0.1)] text-end tabular-nums wrap-anywhere '
            + (keadaan.putus ? 'bg-oranye-600 font-semibold text-white' : 'in-data-[susunan=kecil]:hidden');
        akar.dataset.putus = keadaan.putus ? '1' : '0';
    }

    function terapkan(baru) {
        if (!baru || typeof baru !== 'object') return;
        const berubah = !data || baru.versi !== data.versi;
        data = baru;
        keadaan.diperbarui = new Date();
        keadaan.putus = false;
        tulisStatus();
        if (berubah) susun();
    }

    async function ambil() {
        if (!sumber || keadaan.sedangAmbil) return;
        keadaan.sedangAmbil = true;
        const batal = new AbortController();
        const batasWaktu = setTimeout(() => batal.abort(), 10000);
        try {
            const jawaban = await fetch(sumber, { headers: { Accept: 'application/json' }, cache: 'no-cache', signal: batal.signal });
            if (!jawaban.ok) throw new Error('HTTP ' + jawaban.status);
            terapkan(await jawaban.json());
            keadaan.ambilPada = Date.now() + segarMs;
        } catch {
            keadaan.putus = true;
            tulisStatus();
            // Coba lagi lebih cepat selama terputus, data terakhir tetap tampil.
            keadaan.ambilPada = Date.now() + Math.min(segarMs, 10000);
        } finally {
            clearTimeout(batasWaktu);
            keadaan.sedangAmbil = false;
        }
    }

    // ---------- Tombol layar penuh dan kendali ----------
    const tombolPenuh = document.querySelector('[data-layar-penuh]');
    const bisaPenuh = !!(document.fullscreenEnabled && document.documentElement.requestFullscreen);

    function alihPenuh() {
        if (!bisaPenuh) return;
        if (document.fullscreenElement) document.exitFullscreen?.().catch(() => {});
        else document.documentElement.requestFullscreen().catch(() => {});
    }

    function tampilkanKendali() {
        keadaan.diam = Date.now() + 3000;
        if (bisaPenuh) {
            tombolPenuh?.classList.remove('hidden', 'opacity-0');
            tombolPenuh?.classList.add('inline-flex');
        }
        document.body.classList.remove('cursor-none');
    }

    function aturIkonPenuh() {
        if (!tombolPenuh) return;
        const [masuk, keluar] = tombolPenuh.querySelectorAll('svg');
        const penuh = !!document.fullscreenElement;
        masuk?.classList.toggle('hidden', penuh);
        keluar?.classList.toggle('hidden', !penuh);
        tombolPenuh.setAttribute('aria-label', penuh ? 'Keluar dari layar penuh' : 'Layar penuh');
        tombolPenuh.title = (penuh ? 'Keluar dari layar penuh' : 'Layar penuh') + ' (F)';
    }

    function tombol(e) {
        if (e.key === 'f' || e.key === 'F') alihPenuh();
        else if (e.key === 'ArrowRight') tampilkan(keadaan.indeks + 1);
        else if (e.key === 'ArrowLeft') tampilkan(keadaan.indeks - 1);
        else return;
        tampilkanKendali();
    }

    tombolPenuh?.addEventListener('click', alihPenuh);
    document.addEventListener('fullscreenchange', aturIkonPenuh);
    document.addEventListener('keydown', tombol);
    document.addEventListener('pointermove', tampilkanKendali, { passive: true });
    document.addEventListener('pointerdown', tampilkanKendali, { passive: true });

    // ---------- Detak ----------
    function detak() {
        const kini = Date.now();
        const waktu = new Date(kini);
        const menit = jamMenit(waktu);
        if (menit !== keadaan.menitJam) {
            keadaan.menitJam = menit;
            jam.textContent = menit;
        }
        if (keadaan.diam && kini > keadaan.diam) {
            keadaan.diam = 0;
            tombolPenuh?.classList.add('opacity-0');
            document.body.classList.add('cursor-none');
        }
        if (keadaan.ukurUlang && kini >= keadaan.ukurUlang) {
            keadaan.ukurUlang = 0;
            susun();
        }
        if (keadaan.slide.length > 1 && kini >= keadaan.gantiPada) tampilkan(keadaan.indeks + 1);
        else if (kini >= keadaan.gantiPada) keadaan.gantiPada = kini + lamaTampil();
        if (kini >= keadaan.ambilPada) ambil();
    }

    const pengamatUkuran = new ResizeObserver(() => {
        // Tunggu ukuran tenang sebentar sebelum menyusun ulang.
        keadaan.ukurUlang = Date.now() + 250;
    });

    // ---------- Latar ----------
    let adegan = null;

    function pasangGambarLatar() {
        const d = akar.dataset;
        if (d.latarDasar) {
            const img = el('img', 'absolute inset-0 size-full object-cover');
            img.src = d.latarDasar;
            img.alt = '';
            lapisGambar.append(img);
        }
        if (d.latarPita) {
            for (const kelas of ['absolute inset-x-0 top-0 h-[7vmin] bg-repeat-x bg-size-[auto_100%]', 'absolute inset-x-0 bottom-0 h-[7vmin] rotate-180 bg-repeat-x bg-size-[auto_100%]']) {
                const pita = el('div', kelas);
                pita.style.backgroundImage = 'url("' + encodeURI(d.latarPita) + '")';
                lapisGambar.append(pita);
            }
        }
        if (d.latarKiriAtas) {
            const img = el('img', 'absolute start-0 top-0 size-[22vmin]');
            img.src = d.latarKiriAtas;
            img.alt = '';
            lapisGambar.append(img);
        }
        if (d.latarKananBawah) {
            const img = el('img', 'absolute end-0 bottom-0 size-[22vmin]');
            img.src = d.latarKananBawah;
            img.alt = '';
            lapisGambar.append(img);
        }
    }

    function adaWebgl() {
        try {
            const kanvas = document.createElement('canvas');
            return !!(window.WebGLRenderingContext && (kanvas.getContext('webgl2') || kanvas.getContext('webgl')));
        } catch {
            return false;
        }
    }

    async function pasangAdegan() {
        if (kurangiGerak || !adaWebgl() || akar.dataset.tanpaAdegan !== undefined) return;
        let THREE;
        try {
            THREE = await import('../vendor/three/three.module.min.js');
        } catch {
            return;
        }

        const renderer = new THREE.WebGLRenderer({ antialias: false, alpha: true, powerPreference: 'low-power' });
        renderer.setPixelRatio(1);
        renderer.domElement.setAttribute('aria-hidden', 'true');
        renderer.domElement.className = 'pointer-events-none absolute inset-0 size-full';
        latar.append(renderer.domElement);

        const scene = new THREE.Scene();
        const kamera = new THREE.PerspectiveCamera(40, 1, 0.1, 100);
        kamera.position.set(0, 0, 14);
        scene.add(new THREE.HemisphereLight(0xffffff, 0x234987, 2));
        const matahari = new THREE.DirectionalLight(0xffffff, 1.4);
        matahari.position.set(-4, 6, 8);
        scene.add(matahari);

        const WARNA = [0xe85f31, 0xf0005a, 0x9bccec, 0x234987, 0xfcd34d];
        const bentuk = [
            new THREE.SphereGeometry(0.9, 32, 20),
            new THREE.TorusGeometry(0.8, 0.26, 16, 48),
            new THREE.ConeGeometry(0.7, 1.4, 28),
            new THREE.CapsuleGeometry(0.35, 1.1, 8, 16),
        ];
        const bahan = WARNA.map((color) => new THREE.MeshStandardMaterial({ color, roughness: 0.55, metalness: 0.05, transparent: true, opacity: 0.55 }));

        // Posisi dalam koordinat ternormal (-1..1) supaya tersebar di tepi layar rasio apa pun.
        const titikDasar = [
            [-0.92, 0.75], [-0.6, 0.95], [-0.15, 0.9], [0.35, 0.95], [0.85, 0.8], [0.97, 0.2],
            [0.9, -0.45], [0.55, -0.92], [0.05, -0.95], [-0.45, -0.9], [-0.9, -0.6], [-0.97, 0.05],
        ];
        const benda = titikDasar.map(([nx, ny], i) => {
            const mesh = new THREE.Mesh(bentuk[i % bentuk.length], bahan[i % bahan.length]);
            mesh.userData = { nx, ny, fase: i * 0.9, laju: 0.15 + (i % 5) * 0.04, z: -((i * 7) % 5) - 1 };
            mesh.rotation.set(i, i * 0.7, i * 0.3);
            scene.add(mesh);
            return mesh;
        });

        function ukur() {
            const l = akar.clientWidth;
            const t = akar.clientHeight;
            if (!l || !t) return;
            // Layar besar digambar setengah resolusi lalu direntangkan: latar saja, hemat GPU.
            const faktor = l * t > 2_500_000 ? 0.5 : 1;
            renderer.setSize(Math.round(l * faktor), Math.round(t * faktor), false);
            kamera.aspect = l / t;
            kamera.updateProjectionMatrix();
            for (const m of benda) {
                const jarak = kamera.position.z - m.userData.z;
                const setengahT = Math.tan(THREE.MathUtils.degToRad(kamera.fov / 2)) * jarak;
                const setengahL = setengahT * kamera.aspect;
                m.position.set(m.userData.nx * setengahL, m.userData.ny * setengahT, m.userData.z);
                m.userData.dasarY = m.position.y;
                // Besar benda sekitar 8% sisi pendek layar, di rasio apa pun.
                m.scale.setScalar((Math.min(setengahT, setengahL) * 2 * 0.08) / 0.9);
            }
        }

        const jamAdegan = new THREE.Clock();
        let terakhir = 0;
        renderer.setAnimationLoop(() => {
            if (document.hidden) return;
            const w = jamAdegan.getElapsedTime();
            if (w - terakhir < 1 / 30) return;
            terakhir = w;
            for (const m of benda) {
                m.position.y = m.userData.dasarY + Math.sin(w * m.userData.laju + m.userData.fase) * 0.3;
                m.rotation.x += 0.004;
                m.rotation.y += 0.006;
            }
            renderer.render(scene, kamera);
        });

        lapisGambar.classList.add('hidden');
        akar.dataset.adeganJalan = '1';

        adegan = {
            ukur,
            buang() {
                renderer.setAnimationLoop(null);
                bentuk.forEach((g) => g.dispose());
                bahan.forEach((b) => b.dispose());
                renderer.dispose();
                renderer.domElement.remove();
            },
        };
        ukur();
    }

    // ---------- Mulai ----------
    tulisStatus();
    pasangGambarLatar();
    susun();
    detak();
    const pewaktu = setInterval(detak, 1000);
    pengamatUkuran.observe(akar);
    const pengamatAdegan = new ResizeObserver(() => adegan?.ukur());
    pengamatAdegan.observe(akar);
    pasangAdegan();

    function berhenti() {
        clearInterval(pewaktu);
        pengamatUkuran.disconnect();
        pengamatAdegan.disconnect();
        document.removeEventListener('fullscreenchange', aturIkonPenuh);
        document.removeEventListener('keydown', tombol);
        document.removeEventListener('pointermove', tampilkanKendali);
        document.removeEventListener('pointerdown', tampilkanKendali);
        adegan?.buang();
        adegan = null;
    }

    window.addEventListener('pagehide', berhenti, { once: true });

    // Kait uji tampilan (`alat/uji-layar.mjs`), hanya bila `data-uji` dipasang.
    if (akar.dataset.uji !== undefined) {
        window.layarUji = {
            jumlah: () => keadaan.slide.length,
            susunan: () => keadaan.susunan,
            tampilkan: (i) => tampilkan(i, false),
            terapkan: (baru) => terapkan(baru),
            susunUlang: () => susun(),
            adegan: () => akar.dataset.adeganJalan === '1',
            ambil: () => ambil(),
            kendali: () => tampilkanKendali(),
            putus: () => keadaan.putus,
            berhenti,
        };
    }
}
