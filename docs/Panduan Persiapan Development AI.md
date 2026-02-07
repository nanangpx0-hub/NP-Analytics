# **🚀 Panduan Persiapan Development AI-Driven**

**Project:** NP Analytics (NativePHP Android App)

Dokumen ini berisi hal-hal yang wajib Anda siapkan *sebelum* menulis satu baris kode pun. Tujuannya adalah menyamakan frekuensi semua agen AI agar outputnya konsisten.

## **1\. Buat File "Project Context" (Kitab Suci)**

Buatlah satu file teks bernama project\_context.txt di root folder komputer Anda. Setiap kali Anda membuka sesi chat baru dengan Claude atau ChatGPT, **copy-paste isi file ini dulu** sebagai prompt pertama.

### **Isi File project\_context.txt (Copy Template Ini):**

\*\*\* PROJECT CONTEXT START \*\*\*

APP NAME: NP Analytics  
PLATFORM: Android (via NativePHP Wrapper)  
AUTHOR: Nanang Pamungkas  
STACK:  
\- Backend: Laravel 10 (Serverless/Local)  
\- Frontend: Livewire 3 \+ Blade  
\- CSS: Tailwind CSS  
\- Database: SQLite (Offline/Embedded)  
\- Mobile Wrapper: NativePHP (Alpha/Beta Version)

CORE RULES:  
1\. NO API CALLS: Aplikasi ini offline-first. Semua logic ada di controller lokal.  
2\. NO MYSQL: Gunakan SQLite sintaks. Jangan gunakan fitur spesifik MySQL.  
3\. SINGLE FILE COMPONENTS: Gunakan Livewire Volt (jika diminta) atau struktur standar Livewire 3\.  
4\. BRANDING:  
   \- Primary Color: Navy Blue (\#0f172a)  
   \- Accent Color: Amber Gold (\#f59e0b)  
   \- Style: Professional, Minimalist, Analyst.

LOGIC ATURAN WARNA (SMART TREND):  
\- Indikator Positif (Padi/PDRB): Naik \= Hijau, Turun \= Merah.  
\- Indikator Negatif (Kemiskinan/Inflasi): Naik \= Merah, Turun \= Hijau.  
\- Gunakan field 'is\_higher\_better' di database untuk menentukan ini.

DATABASE SCHEMA:  
\- categories (id, name, icon)  
\- indicators (id, category\_id, title, value, unit, year, trend, is\_higher\_better, description, image\_path)  
\- phenomena (id, indicator\_id, title, description, impact\[positive/negative\])

\*\*\* PROJECT CONTEXT END \*\*\*

Saya ingin Anda bertindak sebagai Senior Laravel Developer yang ahli dalam NativePHP.  
Tugas sekarang: \[GANTI TEKS INI DENGAN SALAH SATU CONTOH TUGAS DI BAWAH\]

### **📋 Contoh Tugas untuk Diisikan (Pilih Satu):**

Berikut adalah variasi tugas yang bisa Anda tuliskan di bagian \[Tugas sekarang\]:

* **Untuk Database (Opus):**"Buatkan kode Migration Laravel lengkap untuk ketiga tabel di schema atas. Pastikan relasi foreign key dan tipe data SQLite benar."  
* **Untuk Tampilan/UI (Sonnet):**"Buatkan kode untuk resources/views/components/indicator-card.blade.php. Terapkan Tailwind CSS dengan warna Navy Blue sebagai background header kartu, dan logika warna merah/hijau untuk icon panah trend."  
* **Untuk Data Dummy (Copilot/Opus):**"Buatkan file IndicatorSeeder.php. Tolong isi dengan 5 data contoh realistik untuk kategori Ekonomi (PDRB, Inflasi) dan Pertanian (Padi). Jangan gunakan Lorem Ipsum."  
* **Untuk Logic Detail (Sonnet/Gemini):**"Saya sedang mengerjakan IndicatorDetail.php. Buatkan method render() yang mengirimkan data indikator beserta relasi phenomena ke view."

## **2\. Persiapan Aset Desain (Design Tokens)**

Agar **Sonnet** tidak mengarang warna sendiri, Anda perlu mendefinisikan "Design Tokens" di awal.

**Siapkan file tailwind.config.js di awal dengan konfigurasi ini:**

(AI sering lupa kode warna hex, jadi hardcode di config adalah kunci).

// Siapkan snippet ini untuk diberikan ke AI saat meminta styling  
theme: {  
    extend: {  
        colors: {  
            brand: {  
                navy: '\#0f172a',  // Background Header  
                gold: '\#f59e0b',  // Tombol/Aksen  
                bg: '\#f8fafc',    // Background App  
                surface: '\#ffffff' // Kartu  
            }  
        }  
    }  
}

## **3\. Persiapan Data Dummy (Realistis)**

AI sering membuat data dummy yang jelek ("Lorem Ipsum", "Test 1", "Test 2"). Ini membuat aplikasi terlihat murahan saat testing.

**Tugas Anda:**

Siapkan 1 file Excel/Notepad berisi 5 data asli BPS Jember (seperti yang kita bahas sebelumnya: Inflasi, Padi, PDRB).

**Contoh Prompt untuk AI (Opus):**

"Gunakan data berikut untuk membuat file Seeder. JANGAN gunakan Lorem Ipsum."

## **![][image1]4\. Pembagian Peran AI (Agent Orchestration)**

Agar maksimal, jangan minta satu AI mengerjakan semuanya. Gunakan strategi estafet:

### **Langkah A: Perencanaan (Architecting) \-\> Gunakan Claude 3 Opus**

* **Input:** Berikan Blueprint NP Analytics.  
* **Prompt:** "Analisis blueprint ini. Apakah ada relasi database yang hilang untuk fitur 'Fenomena'? Buatkan struktur tabel finalnya."  
* **Output:** Skema Database fix (Migration files).

### **Langkah B: Koding Tampilan (Frontend) \-\> Gunakan Claude 3.5 Sonnet**

* **Input:** Skema Database dari Opus \+ Design Tokens (Warna).  
* **Prompt:** "Buatkan komponen Livewire IndicatorCard.blade.php. Gunakan warna brand-navy untuk judul dan logika warna trend hijau/merah berdasarkan variabel $is\_higher\_better."  
* **Output:** Kode Blade/HTML yang cantik.

### **Langkah C: Logika & Debugging \-\> Gunakan Gemini 1.5 Pro / GPT-4o**

* **Input:** Kode Error dari Terminal.  
* **Prompt:** "Saya dapat error NativePHP driver not found saat build Android. Apa penyebabnya di environment Windows?"  
* **Output:** Solusi perbaikan teknis.

## **5\. Struktur Folder (Scaffolding)**

Sebelum AI mulai koding, Anda (sebagai manusia) wajib menyiapkan struktur folder agar AI tidak bingung menaruh file.

**Jalankan perintah ini manual di terminal:**

1. php artisan make:model Category \-m  
2. php artisan make:model Indicator \-m  
3. php artisan make:model Phenomenon \-m  
4. php artisan make:livewire HomeDashboard  
5. php artisan make:livewire IndicatorDetail

Setelah file kosong ini ada, baru suruh AI: *"Isi file HomeDashboard.php dengan logika pencarian..."*

## **Checklist Sebelum Start Coding 🏁**

1. ![][image2]**Install Environment:** PHP 8.2, Composer, Node.js, Android Studio.  
2. ![][image2]**Install NativePHP:** composer require nativephp/mobile.  
3. ![][image2]**Copy Context File:** Simpan teks "PROJECT CONTEXT" di catatan Anda.  
4. ![][image2]**Siapkan Gambar:** Download 3-5 gambar grafik dummy, simpan di folder public/images/.  
5. **Mulai\!** Buka chat dengan AI pilihan Anda.

[image1]: <data:image/png;base64,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>

[image2]: <data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAmwAAAAUCAYAAAAwe2GgAAAAR0lEQVR4Xu3BMQEAAADCoPVPbQlPoAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAP4GwdQAAZuLbQIAAAAASUVORK5CYII=>