# 📊 NP Analytics

> **Aplikasi Statistik Daerah Offline-First untuk Android**
> 
> Built with Laravel 11 + Livewire 3 + NativePHP

---

## 🎯 About

**NP Analytics** adalah aplikasi mobile Android untuk menampilkan dan menganalisis indikator statistik daerah secara offline. Aplikasi ini menggunakan **Smart Trend Logic** untuk menampilkan warna trend (hijau/merah) secara otomatis berdasarkan jenis indikator.

### Key Features

✨ **Offline-First** - Semua data tersimpan di SQLite lokal  
🎨 **Smart Trend Logic** - Warna otomatis (hijau = bagus, merah = buruk)  
📱 **Mobile-Ready** - Optimized untuk NativePHP Android wrapper  
🚀 **Fast & Responsive** - Livewire 3 + Blade components  
🔍 **Advanced Filtering** - Cari berdasarkan kategori, tahun, keyword  

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 11 |
| **Frontend** | Livewire 3 + Blade |
| **CSS** | Tailwind CSS 3 |
| **Database** | SQLite (offline) |
| **Mobile Wrapper** | NativePHP (Alpha/Beta) |

---

## 🚀 Installation

### Prerequisites

- PHP 8.3 or higher
- PHP binary (Laragon): `E:\laragon\bin\php\php-8.5.1-nts-Win32-vs17-x64\php.exe`
- Composer
- Node.js 18+ (untuk build Tailwind lokal)
- SQLite extension enabled

Catatan: Path PHP lokal juga disimpan di `.env` sebagai `PHP_BINARY` agar konsisten dengan tooling lokal.

### Setup Steps

1. **Clone/Extract ke direktori project**
   ```bash
   cd "e:\laragon\www\NP Analytics"
   ```

   Jika `php` belum ada di PATH, gunakan path di atas saat menjalankan perintah. Contoh: `E:\laragon\bin\php\php-8.5.1-nts-Win32-vs17-x64\php.exe artisan serve`.

2. **Install Dependencies (PHP)**
   ```bash
   composer install
   ```

3. **Install Dependencies (Frontend)**
   ```bash
   npm install
   ```

4. **Build Assets (Tailwind)**
   ```bash
   npm run dev
   ```
   Untuk production/offline bundle:
   ```bash
   npm run build
   ```

5. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

6. **Setup Database (SQLite)**
   
   Edit `.env`:
   ```env
   DB_CONNECTION=sqlite
   DB_DATABASE=/absolute/path/to/database/database.sqlite
   ```

   Create SQLite file:
   ```bash
   touch database/database.sqlite
   ```

7. **Run Migrations**
   ```bash
   php artisan migrate
   ```

8. **Seed Database dengan Data Sample**
   ```bash
   php artisan db:seed
   ```

9. **Run Development Server**
   ```bash
   php artisan serve
   ```

10. **Akses Aplikasi**
   
   Buka browser: `http://localhost:8000`

---

## 🖥️ Backend Server (PHP 8.2)

Untuk kebutuhan update data di server dan sinkronisasi dengan aplikasi mobile, backend **dipisah sebagai repo terpisah**.
Lokasi lokal saat ini: `E:\laragon\www\np-analytics-backend`

**Setup singkat (di repo backend):**
1. `cd E:\laragon\www\np-analytics-backend`
2. `composer install`
3. `cp .env.example .env`
4. Atur `DB_*`, `SYNC_API_KEY`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`
5. `php artisan key:generate`
6. `php artisan migrate --seed`
7. `php artisan serve`

Admin panel: `http://localhost:8000/login`  
Sync API: `GET /api/sync/pull` dan `POST /api/sync/push`

---

## 📁 Project Structure

```
NP Analytics/
├── app/
│   ├── Livewire/
│   │   ├── Dashboard.php          # Dashboard component
│   │   └── IndicatorList.php      # Indicator list component
│   └── Models/
│       ├── Category.php            # Category model
│       ├── Indicator.php           # Indicator model (Smart Trend Logic)
│       └── Phenomenon.php          # Phenomenon model
├── database/
│   ├── migrations/                 # Database migrations
│   ├── seeders/                    # Database seeders (14 indikator)
│   └── factories/                  # Model factories
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── app.blade.php       # Main layout
│       ├── components/
│       │   └── indicator-card.blade.php  # Reusable card component
│       └── livewire/
│           ├── dashboard.blade.php
│           └── indicator-list.blade.php
├── routes/
│   └── web.php                     # Web routes
└── docs/
    ├── SEEDER_GUIDE.md             # Database seeding guide
    └── Blueprint NP Analytics.md    # Project blueprint
```

---

## 🎨 Branding & Design

### Color Palette

| Color | Hex Code | Usage |
|-------|----------|-------|
| **Navy Blue** | `#0f172a` | Primary, headers, navigation |
| **Amber Gold** | `#f59e0b` | Accent, badges, highlights |
| **Green** | `#10b981` | Positive trends |
| **Red** | `#ef4444` | Negative trends |

### Design Principles

- ✅ Professional & Minimalist
- ✅ Mobile-first responsive design
- ✅ Touch-friendly (48×48dp tap targets)
- ✅ High contrast for readability

---

## 📊 Smart Trend Logic

Aplikasi ini menggunakan logika cerdas untuk menampilkan warna trend:

### Indikator Positif (`is_higher_better: true`)
```
Contoh: PDRB, Produksi Padi, IPM
• Trend NAIK (+)   → 🟢 HIJAU (bagus)
• Trend TURUN (-)  → 🔴 MERAH (buruk)
```

### Indikator Negatif (`is_higher_better: false`)
```
Contoh: Kemiskinan, Inflasi, Pengangguran
• Trend NAIK (+)   → 🔴 MERAH (buruk)
• Trend TURUN (-)  → 🟢 HIJAU (bagus)
```

---

## 📚 Usage

### Dashboard

URL: `/dashboard`

Menampilkan:
- 4 statistik cards (Kategori, Indikator, Trend Positif/Negatif)
- Indikator per kategori dalam grid
- Filter berdasarkan tahun

### Daftar Indikator

URL: `/indicators`

Fitur:
- 🔍 Search indikator by title
- 🗂️ Filter by category
- 📅 Filter by year
- 📱 Responsive grid layout

---

## 🗄️ Database Schema

### Tables

1. **categories**
   - `id`, `name`, `icon`, `timestamps`

2. **indicators**
   - `id`, `category_id` (FK), `title`, `value`, `unit`, `year`
   - `trend`, `is_higher_better`, `description`, `image_path`
   - `timestamps`

3. **phenomena**
   - `id`, `indicator_id` (FK), `title`, `description`
   - `impact` (enum: positive/negative)
   - `timestamps`

### Strategic Data (BPS Kabupaten Jember)

Seeder master menyediakan data empiris resmi BPS Jember (2021–2026):
- **8 Kategori**: Ekonomi & PDRB, Sosial & Kemiskinan, Ketenagakerjaan & SDM, Indeks Pembangunan Manusia, Pertanian & Ketahanan Pangan, Pariwisata & Jasa, Kependudukan, Potensi Wilayah & Desa (PODES)
- **66 Indikator**: PDRB ADHB/ADHK, Inflasi IHK, Kemiskinan (P0, P1, P2, GK), Ketenagakerjaan (TPT, TPAK, Vokasi), IPM (UHH, HLS, RLS), Padi & Beras, TPK Perhotelan, PODES 2025.
- **16 Fenomena**: Telaah 5 Jebakan Struktural daerah & roadmap transformasi sosio-ekonomi 2026–2030

📖 [Lihat SEEDER_GUIDE.md](docs/SEEDER_GUIDE.md) untuk detail lengkap

---

## 🧪 Testing

### Run Tinker untuk Cek Data

```bash
php artisan tinker
```

```php
// Cek jumlah data
App\Models\Category::count();      // 8 Kategori
App\Models\Indicator::count();     // 66 Indikator
App\Models\Phenomenon::count();    // 16 Fenomena

// Lihat data
App\Models\Category::all()->pluck('name');
App\Models\Indicator::where('year', 2025)->get();
```

---

## 📱 Build for Android (NativePHP)

APK release ditandatangani dan terverifikasi untuk perangkat `arm64-v8a` (POCO X7). Panduan lengkap—kompatibilitas Android 14–17, safe area HyperOS, optimasi performa, Gemini AI, signing, verifikasi, dan troubleshooting—tersedia di:

📖 **[Dokumentasi Optimasi Android & Gemini AI](docs/OPTIMASI_ANDROID_DAN_GEMINI.md)**

Build cepat:

```bash
npm run build
php artisan test
php artisan native:package android --build-type=release --no-tty \
  --keystore="android-keystore/np-analytics.keystore" \
  --keystore-password="<STORE_PASSWORD>" \
  --key-alias="npanalytics" \
  --key-password="<KEY_PASSWORD>" \
  --output=dist
```

Hasil: `dist/app-release.apk`. Selalu verifikasi dengan `apksigner` karena exit code `0` dari NativePHP tidak menjamin Gradle berhasil dan APK terbentuk.

---

## 🔄 Sync (Android <-> Server)

Aplikasi mendukung sinkronisasi dua arah:
- Edit data di HP (offline) lalu sync ke server saat online
- Update data di server lalu tarik ke HP saat sync

Set `SYNC_BASE_URL` di `.env` aplikasi mobile agar mengarah ke backend server.

Panduan lengkap ada di: `docs/SYNC_GUIDE.md`

---

## 🤝 Contributing

This is a private project by **Nanang Pamungkas**. If you have suggestions, feel free to contact the author.

---

## 📄 License

Copyright © 2026 **Nanang Pamungkas**. All rights reserved.

---

## 👨‍💻 Author

**Nanang Pamungkas**
- Role: Developer & Analyst
- Project: NP Analytics - Offline Statistical Analysis App
- Stack: Laravel + Livewire + NativePHP

---

## 📞 Support

Untuk pertanyaan atau issue, silakan buat issue di repository atau hubungi author.

---

**Built with ❤️ using Laravel & NativePHP**
