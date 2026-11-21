#!/usr/bin/env python3
"""Ubah tabel skor multi-lomba panitia (PDF) menjadi data/tabel-skor/<kode>.json (F9-10, K140 7b).

Pemakaian (dari akar repo, butuh pdftotext dari poppler-utils):

    python3 -I data/alat/tabel_skor_pdf.py            # tulis kedua berkas JSON
    python3 -I data/alat/tabel_skor_pdf.py --periksa  # hanya periksa, tanpa menulis

Cara kerja:
- `pdftotext -layout` membaca PDF per halaman. Judul halaman menyebut jenis
  kelamin dan komponen ("Putra 60 Meter", "Putri Lempar Lembing").
- Setiap pasangan "prestasi poin" di halaman itu diambil. Prestasi waktu
  disimpan dalam perseratus detik (7.00 -> 700, 2:05.10 -> 12510), jarak dalam
  sentimeter (5.46 -> 546), sama seperti kolom `hasil.prestasi`.
- Judul pertama di halaman yang dipakai (halaman terakhir Pancalomba juga
  memuat teks "Putri 100 Meter" di bawah judul 800 m). Bila bentuk prestasi
  (menit:detik atau bukan) tidak cocok dengan judulnya, halaman dianggap
  lanjutan halaman sebelumnya dan dilaporkan.
- Nomor halaman yang menempel ke angka poin di PDF Pancalomba ("137155")
  diperbaiki menjadi poin yang cocok dengan baris tetangganya (155) dan
  dilaporkan.
- Setiap tabel diperiksa: prestasi unik, poin naik searah prestasi makin baik,
  dan baris sampel yang dicatat di SAMPEL harus cocok. Bila ada yang tidak
  cocok, alat berhenti tanpa menulis berkas.
"""

import json
import os
import re
import subprocess
import sys

AKAR = os.path.dirname(os.path.dirname(os.path.dirname(os.path.abspath(__file__))))

# Komponen: kode -> (nama, jenis hasil, kata kunci di judul halaman)
KOMPONEN = {
    'lari-60': ('Lari 60 m', 'waktu', '60 Meter'),
    'lari-100': ('Lari 100 m', 'waktu', '100 Meter'),
    'lari-800': ('Lari 800 m', 'waktu', '800 Meter'),
    'lompat-jauh': ('Lompat Jauh', 'jarak', 'Lompat Jauh'),
    'tolak-peluru': ('Tolak Peluru', 'jarak', 'Tolak Peluru'),
    'lempar-lembing': ('Lempar Lembing', 'jarak', 'Lempar Lembing'),
}

TABEL = [
    {
        'kode': 'trilomba-o2sn-smp',
        'nama': 'Trilomba O2SN SMP',
        'sumber': 'sumber/tabel-skor/tabel-penilaian-trilomba-o2sn-smp.pdf',
        'keterangan': 'Scoring Table Licenced by IAAF & PASI Modification (tabel penilaian Trilomba O2SN SMP dari panitia).',
        'komponen': ['lari-60', 'lompat-jauh', 'tolak-peluru'],
    },
    {
        'kode': 'pancalomba-sma-smk',
        'nama': 'Pancalomba SMA/SMK',
        'sumber': 'sumber/tabel-skor/tabel-penilaian-pancalomba-sma-smk.pdf',
        'keterangan': 'World Athletics Scoring Tables for Combined Events (tabel penilaian Pancalomba SMA/SMK dari panitia).',
        'komponen': ['lari-100', 'lari-800', 'lompat-jauh', 'tolak-peluru', 'lempar-lembing'],
    },
]

# Baris sampel yang dibaca langsung dari PDF: (tabel, komponen, kategori, prestasi teks, poin)
SAMPEL = [
    ('trilomba-o2sn-smp', 'lari-60', 'putra', '5.46', 1500),
    ('trilomba-o2sn-smp', 'lari-60', 'putra', '7.00', 882),
    ('trilomba-o2sn-smp', 'lari-60', 'putra', '8.00', 560),
    ('trilomba-o2sn-smp', 'lari-60', 'putra', '10.10', 106),
    ('trilomba-o2sn-smp', 'lompat-jauh', 'putra', '9.63', 1500),
    ('trilomba-o2sn-smp', 'lompat-jauh', 'putra', '8.10', 1086),
    ('trilomba-o2sn-smp', 'tolak-peluru', 'putra', '26.36', 1500),
    ('trilomba-o2sn-smp', 'tolak-peluru', 'putra', '23.99', 1350),
    ('pancalomba-sma-smk', 'lari-100', 'putra', '9.50', 1223),
    ('pancalomba-sma-smk', 'lari-100', 'putra', '11.00', 861),
    ('pancalomba-sma-smk', 'lompat-jauh', 'putra', '9.49', 1461),
    ('pancalomba-sma-smk', 'tolak-peluru', 'putra', '23.99', 1350),
    ('pancalomba-sma-smk', 'lempar-lembing', 'putra', '102.85', 1400),
    ('pancalomba-sma-smk', 'lari-800', 'putra', '1:51.71', 1250),
    ('pancalomba-sma-smk', 'lari-800', 'putra', '2:04.31', 1050),
    ('pancalomba-sma-smk', 'lari-800', 'putri', '3:27.12', 155),
    ('pancalomba-sma-smk', 'lari-800', 'putri', '4:10.79', 1),
]

POLA_PASANGAN = re.compile(r'(?<![\d:.])(\d{1,2}:\d{2}\.\d{2}|\d{1,3}\.\d{2})\s+(\d+)(?![\d.:])')


def prestasi(teks):
    """'7.00' -> 700, '2:05.10' -> 12510, '102.85' -> 10285."""
    if ':' in teks:
        menit, sisa = teks.split(':')
        detik, ratus = sisa.split('.')
        return (int(menit) * 60 + int(detik)) * 100 + int(ratus)
    utuh, ratus = teks.split('.')
    return int(utuh) * 100 + int(ratus)


def baca_pdf(jalur):
    keluaran = subprocess.run(['pdftotext', '-layout', jalur, '-'], check=True, capture_output=True, text=True).stdout
    return keluaran.split('\f')


def judul(halaman, kode_komponen):
    """(kategori, kode komponen) dari judul halaman, atau None."""
    for baris in halaman.splitlines():
        m = re.match(r'^\s*(Putra|Putri)\s+(.+?)\s*(Multi-Event Table)?\s*$', baris)
        if not m:
            continue
        for kode in kode_komponen:
            if KOMPONEN[kode][2].lower() == m.group(2).strip().lower():
                return m.group(1).lower(), kode
    return None


def urai(tabel, laporan):
    halaman_pdf = baca_pdf(os.path.join(AKAR, tabel['sumber']))
    data = {}
    sebelum = None
    for nomor, halaman in enumerate(halaman_pdf, start=1):
        pasangan = POLA_PASANGAN.findall(halaman)
        if not pasangan:
            continue
        kepala = judul(halaman, tabel['komponen'])
        if kepala is None:
            if sebelum is None:
                raise SystemExit(f"{tabel['sumber']} halaman {nomor}: judul tidak dikenali.")
            kepala = sebelum
            laporan.append(f"halaman {nomor}: tanpa judul, dianggap lanjutan {kepala[0]} {kepala[1]}")
        bermenit = any(':' in p for p, _ in pasangan)
        if bermenit != (kepala[1] == 'lari-800'):
            if sebelum is not None and (sebelum[1] == 'lari-800') == bermenit:
                laporan.append(f"halaman {nomor}: judul {kepala[0]} {KOMPONEN[kepala[1]][2]} tidak cocok dengan isinya, dianggap lanjutan {sebelum[0]} {KOMPONEN[sebelum[1]][2]}")
                kepala = sebelum
            else:
                raise SystemExit(f"{tabel['sumber']} halaman {nomor}: bentuk prestasi tidak cocok dengan judul.")
        sebelum = kepala
        baris = data.setdefault(kepala, [])
        for teks, poin in pasangan:
            baris.append([prestasi(teks), poin, nomor])
    return data


def rapikan(baris, waktu, nama, laporan):
    """Urutkan dari prestasi terbaik, perbaiki poin yang ketempelan nomor halaman, lalu periksa."""
    baris.sort(key=lambda b: b[0], reverse=not waktu)
    hasil = []
    for i, (nilai, poin, halaman) in enumerate(baris):
        if int(poin) > 5000:
            atas = hasil[-1][1] if hasil else None
            bawah = int(baris[i + 1][1]) if i + 1 < len(baris) and int(baris[i + 1][1]) <= 5000 else None
            calon = [int(poin[-k:]) for k in range(1, len(poin)) if (atas is None or int(poin[-k:]) <= atas) and (bawah is None or int(poin[-k:]) >= bawah)]
            if not calon:
                raise SystemExit(f"{nama}: poin {poin} di halaman {halaman} tidak bisa diperbaiki.")
            laporan.append(f"{nama}: poin {poin} (halaman {halaman}) dibaca {max(calon)}, nomor halaman menempel")
            poin = str(max(calon))
        hasil.append([nilai, int(poin)])
    for i in range(1, len(hasil)):
        if hasil[i][0] == hasil[i - 1][0]:
            raise SystemExit(f"{nama}: prestasi {hasil[i][0]} muncul dua kali.")
        if hasil[i][1] > hasil[i - 1][1]:
            raise SystemExit(f"{nama}: poin tidak urut di prestasi {hasil[i][0]} ({hasil[i - 1][1]} lalu {hasil[i][1]}).")
    return hasil


def susun():
    laporan = []
    semua = {}
    for tabel in TABEL:
        data = urai(tabel, laporan)
        komponen = []
        for kode in tabel['komponen']:
            nama, jenis, _ = KOMPONEN[kode]
            baris = {}
            for kategori in ('putra', 'putri'):
                if (kategori, kode) not in data:
                    raise SystemExit(f"{tabel['sumber']}: {kategori} {nama} tidak ditemukan.")
                baris[kategori] = rapikan(data[(kategori, kode)], jenis == 'waktu', f"{tabel['kode']} {kategori} {nama}", laporan)
            komponen.append({'kode': kode, 'nama': nama, 'jenis': jenis, 'baris': baris})
        semua[tabel['kode']] = {
            'kode': tabel['kode'],
            'nama': tabel['nama'],
            'keterangan': tabel['keterangan'],
            'sumber': tabel['sumber'],
            'komponen': komponen,
        }
    for kode, komp, kategori, teks, poin in SAMPEL:
        baris = next(k for k in semua[kode]['komponen'] if k['kode'] == komp)['baris'][kategori]
        cocok = [p for n, p in baris if n == prestasi(teks)]
        if cocok != [poin]:
            raise SystemExit(f"Sampel tidak cocok: {kode} {kategori} {komp} {teks} -> {cocok}, seharusnya {poin}.")
    return semua, laporan


def tulis_json(tabel):
    """JSON rapi, satu baris tabel skor per baris berkas supaya selisih git mudah dibaca."""
    baris = ['{']
    for kunci in ('kode', 'nama', 'keterangan', 'sumber'):
        baris.append(f'    {json.dumps(kunci)}: {json.dumps(tabel[kunci], ensure_ascii=False)},')
    baris.append('    "komponen": [')
    for i, k in enumerate(tabel['komponen']):
        baris.append('        {')
        baris.append(f'            "kode": {json.dumps(k["kode"])}, "nama": {json.dumps(k["nama"], ensure_ascii=False)}, "jenis": {json.dumps(k["jenis"])},')
        baris.append('            "baris": {')
        for j, kategori in enumerate(('putra', 'putri')):
            isi = ',\n'.join(f'                    [{n}, {p}]' for n, p in k['baris'][kategori])
            baris.append(f'                "{kategori}": [\n{isi}\n                ]' + (',' if j == 0 else ''))
        baris.append('            }')
        baris.append('        }' + (',' if i + 1 < len(tabel['komponen']) else ''))
    baris.append('    ]')
    baris.append('}')
    return '\n'.join(baris) + '\n'


def main(argumen):
    semua, laporan = susun()
    for pesan in laporan:
        print('Catatan: ' + pesan)
    for tabel in semua.values():
        for k in tabel['komponen']:
            for kategori, baris in k['baris'].items():
                print(f"{tabel['kode']:20} {kategori:5} {k['nama']:15} {len(baris):5} baris, poin {baris[-1][1]}-{baris[0][1]}")
    print(f'{len(SAMPEL)} baris sampel cocok.')
    if '--periksa' in argumen:
        return 0
    for tabel in semua.values():
        jalur = os.path.join(AKAR, 'data', 'tabel-skor', tabel['kode'] + '.json')
        with open(jalur, 'w', encoding='utf-8') as berkas:
            berkas.write(tulis_json(tabel))
        print('Ditulis: ' + os.path.relpath(jalur, AKAR))
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv[1:]))
