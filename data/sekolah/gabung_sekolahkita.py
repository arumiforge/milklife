"""Gabungkan hasil penelusuran Sekolah Kita (sekolah.data.kemendikdasmen.go.id)
ke data/sekolah/sekolah.json.

Sekolah Kita adalah halaman data sekolah resmi Kemendikdasmen ("Data
Verifikasi, sesuai update sekolah"). Isinya ditelusuri lewat peramban per
kabupaten dan bentuk pendidikan, lalu disimpan apa adanya di
data/sekolah/mentah/. Lihat data/README.md bagian "Memperbarui".

Aturan penggabungan:
- Sekolah lama tetap dipakai sebagai dasar karena desa/kelurahan dan
  kecamatannya terpisah rapi; nama, bentuk, dan status mengikuti Sekolah Kita.
- NPSN yang hanya ada di Sekolah Kita ditambahkan. Kecamatan dibaca dari
  "Kec. ..." di alamat; desa/kelurahan diisi bila kata terakhir alamat sama
  dengan nama desa yang sudah dikenal di kecamatan itu.
- NPSN yang tidak ada di Sekolah Kita tidak dihapus (bisa madrasah baru yang
  belum tampil di sana).

Pakai:
    python3 -I data/sekolah/gabung_sekolahkita.py data/sekolah/mentah/sekolah-kita-2026-11-14.json \
        data/sekolah/sekolah.json > sekolah-baru.json
"""
import json, re, sys

KODE_KAB = {"Blora": "031600", "Rembang": "031700", "Pati": "031800", "Kudus": "031900", "Jepara": "032000"}
SMP = {"SMP", "MTs", "SMPTK", "PDF Wustha", "SPM Wustha"}
SMA = {"SMA", "SMK", "MA", "MAK", "PDF Ulya", "SPM Ulya", "SLB"}


def spasi(s):
    return re.sub(r"\s+", " ", s or "").strip()


def jenjang(bentuk):
    return "SMA" if bentuk in SMA else ("SMP" if bentuk in SMP else "SD")


sk = json.load(open(sys.argv[1], encoding="utf-8"))
lama = json.load(open(sys.argv[2], encoding="utf-8"))
per_npsn = {s["npsn"]: s for s in lama}

# Kecamatan dan desa yang dikenal per kabupaten, untuk sekolah baru.
kecamatan, desa = {}, {}
for s in lama:
    kab = s["kabupaten_kota"].removeprefix("Kab. ")
    kecamatan[(kab, s["kecamatan"].upper())] = (s["kecamatan"], s["kode_kecamatan"])
    if s["desa_kelurahan"]:
        desa.setdefault((kab, s["kecamatan"].upper()), {})[s["desa_kelurahan"].upper()] = s["desa_kelurahan"]

hasil, rekap = dict(per_npsn), {"diperbarui": 0, "ditambah": 0, "tanpa_kecamatan": []}
for r in sk:
    npsn, kab = r["npsn"], r["kabupaten"]
    nama, bentuk, status = spasi(r["nama"]), r["bentuk"], spasi(r["status"]).upper()
    if npsn in hasil:
        s = dict(hasil[npsn])
        if (s["nama"], s["bentuk"], s["status"]) != (nama, bentuk, status):
            rekap["diperbarui"] += 1
        s.update(nama=nama, bentuk=bentuk, status=status)
        hasil[npsn] = s
        continue

    alamat = spasi(r["alamat"])
    # Bagian akhir alamat selalu "... Kec. <kecamatan> Kab. <kabupaten> Prov. ...".
    m = re.search(r"^(.*)\bKec\.\s+(.+?),?\s+Kab\.\s+[^,]*$", alamat)
    jalan, nama_kec = (spasi(m.group(1)), m.group(2)) if m else (alamat, "")
    kec, kode_kec = kecamatan.get((kab, nama_kec.upper()), (nama_kec, ""))
    if not kode_kec:
        rekap["tanpa_kecamatan"].append(npsn)
    kata = jalan.split(" ")
    nama_desa = desa.get((kab, kec.upper()), {}).get(kata[-1].upper() if kata else "")
    if nama_desa and len(kata) > 1:
        jalan = " ".join(kata[:-1])
    hasil[npsn] = {
        "npsn": npsn, "nama": nama, "bentuk": bentuk, "jenjang": jenjang(bentuk), "status": status,
        "alamat": jalan, "desa_kelurahan": nama_desa or "", "kecamatan": kec, "kode_kecamatan": kode_kec,
        "kabupaten_kota": "Kab. " + kab, "kode_kabupaten_kota": KODE_KAB[kab],
    }
    rekap["ditambah"] += 1

daftar = sorted(hasil.values(), key=lambda s: (s["kode_kecamatan"], s["jenjang"], s["nama"], s["npsn"]))
print("[\n" + ",\n".join(json.dumps(s, ensure_ascii=False) for s in daftar) + "\n]")
print(json.dumps(rekap, ensure_ascii=False), file=sys.stderr)
