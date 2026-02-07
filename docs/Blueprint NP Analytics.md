# **📘 BLUEPRINT: NP ANALYTICS**

**Master Plan Aplikasi Android Statistik Jember**

| Informasi Proyek | Detail |
| :---- | :---- |
| **Nama Aplikasi** | NP Analytics |
| **Author / Developer** | Nanang Pamungkas |
| **Platform** | Android (via NativePHP) |
| **Tech Stack** | Laravel 10/11, Livewire 3, Tailwind CSS |
| **Database** | SQLite (Embedded / Offline) |
| **Versi Dokumen** | 1.0.0 |

## **1\. Arsitektur Sistem (System Architecture)**

Aplikasi ini menggunakan pendekatan **"Serverless-in-App"**. Seluruh *logic* berjalan secara lokal di perangkat pengguna tanpa memerlukan koneksi internet wajib.

1. **Wrapper:** NativePHP (Kotlin/Android Shell) membungkus runtime PHP.  
2. **Runtime:** PHP 8.2 statis dibundel di dalam APK.  
3. **Backend:** Laravel Framework menangani logika bisnis dan ORM.  
4. **Database:** File database.sqlite tersimpan di penyimpanan internal aplikasi.  
5. **Frontend:** Blade Templates \+ Livewire untuk antarmuka reaktif.

## **2\. Struktur Database (Schema & ERD)**

Terdapat 3 tabel utama dengan relasi hierarki (One-to-Many).

### **A. Tabel categories**

*Pengelompokan data (Ekonomi, Sosial, Pertanian).*

| Kolom | Tipe Data | Keterangan |
| :---- | :---- | :---- |
| id | BigInteger (PK) | ID Unik |
| name | String | Contoh: "Ekonomi Makro" |
| icon | String | Nama file icon / class icon |
| timestamps | \- | created\_at, updated\_at |

### **B. Tabel indicators**

*Data utama statistik BPS (Angka & Grafik).*

| Kolom | Tipe Data | Keterangan |
| :---- | :---- | :---- |
| id | BigInteger (PK) | ID Unik |
| category\_id | BigInteger (FK) | Relasi ke tabel categories |
| title | String | Judul Indikator (ex: "Inflasi YoY") |
| value | Decimal/Double | Nilai mentah (ex: 2.51) |
| unit | String | Satuan (ex: "%", "Rupiah", "Ton") |
| year | String | Tahun Data (ex: "2024") |
| trend | Enum | 'up', 'down', 'stable' |
| is\_higher\_better | Boolean | true (Naik Bagus), false (Turun Bagus) |
| description | Text | Penjelasan definisi BPS |
| image\_path | String | Path ke file grafik di folder public |

### **C. Tabel phenomena**

*Analisis sebab-akibat (Fitur Insight).*

| Kolom | Tipe Data | Keterangan |
| :---- | :---- | :---- |
| id | BigInteger (PK) | ID Unik |
| indicator\_id | BigInteger (FK) | Relasi ke tabel indicators |
| title | String | Judul Fenomena (ex: "Gagal Panen") |
| description | Text | Penjelasan detail konteks kejadian |
| impact | Enum | 'positive', 'negative', 'neutral' |

## **3\. Peta Direktori & File Penting**

Pastikan file-file berikut ada di project Laravel Anda.

NP-Analytics/  
├── app/  
│   ├── Livewire/  
│   │   ├── HomeDashboard.php      (Halaman Utama & Search)  
│   │   ├── IndicatorDetail.php    (Halaman Detail & Analisis)  
│   │   └── Components/  
│   │       └── IndicatorCard.php  (Komponen Kartu Cerdas)  
│   └── Models/  
│       ├── Category.php  
│       ├── Indicator.php  
│       └── Phenomenon.php  
├── config/  
│   └── nativephp.php              (Konfigurasi ID App & Nama)  
├── database/  
│   ├── migrations/                (3 file migrasi tabel di atas)  
│   └── seeders/  
│       ├── DatabaseSeeder.php     (Master Seeder)  
│       ├── IndicatorSeeder.php    (Data BPS Jember)  
│       └── PhenomenonSeeder.php   (Data Analisis Nanang Pamungkas)  
└── resources/  
    ├── css/  
    │   └── app.css                (Konfigurasi Tailwind)  
    └── views/  
        ├── components/  
        │   └── indicator-card.blade.php  
        └── livewire/  
            ├── home-dashboard.blade.php  
            └── indicator-detail.blade.php

## **4\. Design System & Branding**

Identitas visual **"NP Analytics"** (The Professional Analyst).

### **Palet Warna (Tailwind Config)**

Tambahkan konfigurasi ini di tailwind.config.js.

* **Primary (Navy Blue):** \#0f172a (Kepercayaan, Data, Profesional)  
* **Accent (Amber Gold):** \#f59e0b (Insight, Pamungkas, Highlight)  
* **Background:** \#f8fafc (Abu-abu terang, bersih)  
* **Success (Good Trend):** \#10b981  
* **Danger (Bad Trend):** \#ef4444

### **Tipografi**

* **Font:** Inter / Roboto (System Default Android).  
* **Gaya:** Minimalis, Clean, spacing yang cukup antar elemen.

## **5\. Logika Cerdas (Smart Logic)**

Logika untuk menentukan warna indikator secara otomatis.

**Tabel Logika Warna Trend:**

| Kondisi Data | Arah Trend | Logic: is\_higher\_better | Hasil Warna | Ikon Visual |
| :---- | :---- | :---- | :---- | :---- |
| Produksi Padi | Naik | TRUE | **Hijau (Bagus)** | Panah Atas |
| Produksi Padi | Turun | TRUE | **Merah (Buruk)** | Panah Bawah |
| Kemiskinan | Naik | FALSE | **Merah (Buruk)** | Panah Atas |
| Kemiskinan | Turun | FALSE | **Hijau (Bagus)** | Panah Bawah |

## **6\. Alur Pengguna (User Flow)**

1. **Buka Aplikasi:** Splash screen logo NP Analytics.  
2. **Dashboard:**  
   * User melihat ringkasan data terbaru.  
   * User mengetik di kolom pencarian ("Inflasi").  
3. **Interaksi:**  
   * User melihat kartu "Inflasi" dengan trend panah merah (Naik).  
   * User klik kartu tersebut.  
4. **Detail Page:**  
   * User melihat grafik historis 5 tahun.  
   * User membaca narasi analisis.  
   * User membuka bagian "Fenomena" \-\> Muncul penyebab ("Kenaikan BBM").  
5. **About:** User melihat profil Nanang Pamungkas sebagai analis.

## **7\. Checklist Deployment (Rilis)**

Lakukan langkah ini berurutan sebelum upload ke Play Store.

* \[ \] **Data Finalization:** Update data di IndicatorSeeder.php dengan data BPS terbaru.  
* \[ \] **Reset Database:** Jalankan php artisan migrate:fresh \--seed.  
* \[ \] **Compile Assets:** Jalankan npm run build (Minify CSS).  
* \[ \] **Keystore:** Buat kunci digital .jks via Android Studio.  
* \[ \] **Build Production:** Jalankan php artisan native:build android.  
* \[ \] **Upload:** Upload file .aab atau .apk ke Google Play Console.