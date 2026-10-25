# 📊 NP Analytics - Panduan Database & Data Strategis Jember

## 🎯 Overview

Dokumentasi ini menjelaskan arsitektur data, database migration, dan master seeder untuk aplikasi **NP Analytics** yang telah mengintegrasikan seluruh data empiris resmi dari **BPS Kabupaten Jember** (sebagaimana tersimpan di `C:\laragon\www\data-strategis-jember`).

---

## 🚀 Quick Start

### 1. Eksekusi Migration & Seeding
```bash
# Menggunakan binary PHP Laragon
& "C:\laragon\bin\php\php-8.3.32-nts-Win32-vs16-x64\php.exe" artisan migrate --seed
```

Atau cukup jalankan seeder:
```bash
& "C:\laragon\bin\php\php-8.3.32-nts-Win32-vs16-x64\php.exe" artisan db:seed
```

---

## 📋 Struktur Data Strategis yang Terakomodasi

### 1. **Kategori Strategis (8 Kategori)**
1. **Ekonomi & PDRB** (`lucide-trending-up`)
2. **Sosial & Kemiskinan** (`lucide-users`)
3. **Ketenagakerjaan & SDM** (`lucide-briefcase`)
4. **Indeks Pembangunan Manusia** (`lucide-graduation-cap`)
5. **Pertanian & Ketahanan Pangan** (`lucide-wheat`)
6. **Pariwisata & Jasa** (`lucide-hotel`)
7. **Kependudukan** (`lucide-user-check`)
8. **Potensi Wilayah & Desa (PODES)** (`lucide-building-2`)

---

### 2. **Cakupan Indikator Utama (66 Indikator Time-Series 2021–2026)**

#### A. Ekonomi & PDRB
- PDRB ADHB (2023: Rp95,29 T; 2024: Rp102,76 T; 2025: Rp110,98 T)
- PDRB ADHK Pertumbuhan Ekonomi (2021: 4,00%; 2022: 4,53%; 2023: 3,16%; 2024: 4,86%; 2025: 5,47% — melampaui Jatim 5,33%)
- PDRB per Kapita (2023: Rp36,83 Jt; 2024: Rp39,46 Jt; 2025: Rp42,36 Jt)
- Inflasi IHK Kota Jember y-on-y (2024: 1,55%; 2025: 2,77%; Sep 2026: 3,04%)
- Inflasi Makanan, Minuman, Tembakau (4,58% dengan andil 1,47 poin)
- Struktur Pengeluaran: Konsumsi Rumah Tangga 69,11%
- Kontribusi Industri Pengolahan: 21,61% (tumbuh kencang 7,53%)

#### B. Sosial & Kemiskinan
- Tingkat Kemiskinan P0 (2021: 10,41%; 2022: 9,39%; 2023: 9,51%; 2024: 9,01%; 2025: 8,67%; 2026: 8,00% — rekor terendah 10 tahun)
- Penduduk Miskin (2025: 216,76 ribu jiwa; 2026: 200,77 ribu jiwa)
- Garis Kemiskinan (2025: Rp474.903; 2026: Rp517.125 /kapita/bulan, naik +8,89%)
- Indeks Kedalaman Kemiskinan P1 (2025: 0,53; 2026: 0,71)
- Indeks Keparahan Kemiskinan P2 (2025: 0,05; 2026: 0,10)

#### C. Ketenagakerjaan & SDM
- TPT (2023: 4,01%; 2024: 3,23%; 2025: 3,07%)
- TPAK (2024: 73,76%; 2025: 75,78%)
- Angkatan Kerja (1,58 juta jiwa); Bekerja (1,53 juta jiwa)
- Distribusi Sektor: Pertanian (41,57%), Manufaktur (21,49%), Jasa (36,94%)
- Proporsi Pekerja Berpendidikan ≤ SD (58,46%)
- Paradoks Vokasi: TPT SMK 7,62% (tertinggi di antara semua jenjang pendidikan)

#### D. Indeks Pembangunan Manusia (IPM)
- IPM Total (2021: 69,20; 2022: 69,80; 2023: 70,42; 2024: 70,93; 2025: 71,57 — Status Tinggi)
- Umur Harapan Hidup UHH (74,37 tahun)
- Harapan Lama Sekolah HLS (13,51 tahun)
- Rata-rata Lama Sekolah RLS (6,64 tahun — kesenjangan 6,87 tahun dari HLS)
- Pengeluaran Riil per Kapita (Rp11,15 juta/tahun)

#### E. Pertanian & Ketahanan Pangan
- Produksi Padi GKG (2024: 623.264,88 ton; 2025: 679.513,67 ton, +9,02%)
- Luas Panen Padi (2024: 120.069,41 ha; 2025: 135.436,35 ha, +12,80%)
- Produksi Beras Konsumsi (392.364,89 ton beras)
- Produktivitas Padi (5,02 ton GKG/ha)

#### F. Pariwisata & Jasa
- TPK Hotel Total (Ags 2025: 28,77%; Ags 2026: 33,26%)
- TPK Hotel Bintang (Ags 2026: 48,09%, saat puncak JFC >90%)
- Rata-rata Lama Menginap Tamu Domestik (1,07 hari — isu short-stay wisata)
- RLMT Tamu Asing (3,08 hari)

#### G. Kependudukan
- Jumlah Penduduk (2024: 2.606.330 jiwa; 2025: 2.620.140 jiwa)
- Kepadatan Penduduk (792,58 jiwa/km²)
- Rasio Jenis Kelamin (99,18)

#### H. Potensi Wilayah & Desa (PODES 2025)
- Wilayah Administrasi: 31 Kecamatan, 226 Desa, 22 Kelurahan (total 248)
- 211 desa (85,1%) bertumpu pada Pertanian
- 127 desa memiliki Produk Unggulan Daerah
- 240 desa (96,8%) penduduknya telah mengakses KUR
- 197 SMK di 31 Kecamatan
- Kerawanan Bencana: 16 desa rawan banjir, 2 desa banjir bandang, 7 desa rawan longsor lereng, 21 desa tercemar perairan

---

### 3. **Fenomena & Analisis Isu Strategis (16 Fenomena Terpadu)**
Mencakup telaah 5 Jebakan Struktural (*Structural Bottlenecks*):
1. **Agro-Industrial Disconnect** (Pertanian 41,5% Pekerja vs Pertumbuhan 3,30%)
2. **Divergensi Kemiskinan** ($P_0$ turun ke 8,00%, namun $P_1$ dan $P_2$ melebar akibat lonjakan Garis Kemiskinan Rp517.125)
3. **Paradoks Vokasi** (TPT SMK tertinggi 7,62% di tengah 58,5% pekerja berpendidikan rendah)
4. **Tekanan Inflasi Pangan** (Kelompok makanan menyumbang 1,47 poin dari total 3,04%)
5. **Pariwisata Transit Short-Stay** (RLMT domestik hanya 1,07 hari)
Serta rekomendasi 5 Pilar Transformasi RPJMD/RKPD Jember 2026–2030.

---

## 🎨 Smart Trend Logic Validation

Logika otomatis arah dan warna indikator:
- **`is_higher_better: true`** (PDRB, IPM, Padi, TPAK):
  - Trend `>` 0 ➔ 🟢 **Hijau** (Bagus)
  - Trend `<` 0 ➔ 🔴 **Merah** (Buruk)
- **`is_higher_better: false`** (Kemiskinan, Pengangguran, Inflasi, Bencana):
  - Trend `<` 0 ➔ 🟢 **Hijau** (Bagus)
  - Trend `>` 0 ➔ 🔴 **Merah** (Buruk)
