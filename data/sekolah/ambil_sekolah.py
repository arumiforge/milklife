"""Ambil daftar sekolah formal (SD s.d. SMA sederajat) per kabupaten dari
referensi.data.kemendikdasmen.go.id (sumber resmi, memuat madrasah).
Pakai: lihat data/README.md
"""
import html, json, re, sys, time, urllib.request

BASE = "https://referensi.data.kemendikdasmen.go.id"
SMP = {"SMP", "MTs", "SMPTK", "SPK SMP", "Madyama W P", "Muda Dhammasekha",
       "PDF Wustha", "SPM Wustha", "Madyama Widyalaya"}


def ambil(path):
    for jeda in (0, 2, 4, 8, 16):
        time.sleep(jeda or 0.3)
        try:
            req = urllib.request.Request(BASE + path, headers={"User-Agent": "Mozilla/5.0"})
            return urllib.request.urlopen(req, timeout=40).read().decode("utf-8", "replace")
        except Exception as e:  # coba lagi dengan jeda
            galat = e
    raise SystemExit(f"gagal {path}: {galat}")


def bersih(s):
    return re.sub(r"\s+", " ", html.unescape(re.sub(r"<[^>]+>", "", s))).strip()


hasil, rekap = [], {}
for arg in sys.argv[1:]:
    kode_kab, nama_kab = arg.split(":")
    for tingkat in ("dikdas", "dikmen"):
        hal = ambil(f"/satpen/{tingkat}/{kode_kab}/2?jalur=jf&status=all")
        kec = re.findall(rf'href="/satpen/{tingkat}/(\d{{6}})/3[^"]*"[^>]*>\s*([^<]+)<', hal)
        assert kec, f"tidak ada kecamatan: {tingkat} {kode_kab}"
        for kode_kec, nama_kec in dict(kec).items():
            hal = ambil(f"/satpen/{tingkat}/{kode_kec}/3?jalur=jf&status=all")
            badan = hal[hal.find('<table id="dataTable"'):]
            for baris in re.findall(r"<tr>(.*?)</tr>", badan, re.S):
                sel = [bersih(x) for x in re.findall(r"<td[^>]*>(.*?)</td>", baris, re.S)]
                if len(sel) != 7:
                    continue
                _, npsn, nama, bentuk, status, alamat, desa = sel
                jenjang = "SMA" if tingkat == "dikmen" else ("SMP" if bentuk in SMP else "SD")
                hasil.append({
                    "npsn": npsn, "nama": nama, "bentuk": bentuk, "jenjang": jenjang,
                    "status": status, "alamat": alamat, "desa_kelurahan": desa,
                    "kecamatan": bersih(nama_kec).removeprefix("Kec. "),
                    "kode_kecamatan": kode_kec,
                    "kabupaten_kota": nama_kab, "kode_kabupaten_kota": kode_kab,
                })
                rekap[(nama_kab, bentuk)] = rekap.get((nama_kab, bentuk), 0) + 1
            print(tingkat, nama_kab, bersih(nama_kec), len(hasil), file=sys.stderr)

npsn = [s["npsn"] for s in hasil]
assert len(npsn) == len(set(npsn)), "NPSN ganda"
# Satu sekolah per baris dan urutan tetap, supaya selisih di git mudah dibaca.
hasil.sort(key=lambda s: (s["kode_kecamatan"], s["jenjang"], s["nama"], s["npsn"]))
print("[\n" + ",\n".join(json.dumps(s, ensure_ascii=False) for s in hasil) + "\n]")
for k, v in sorted(rekap.items()):
    print(*k, v, file=sys.stderr)
