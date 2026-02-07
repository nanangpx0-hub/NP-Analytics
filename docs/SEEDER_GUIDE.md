# 📊 NP Analytics - Panduan Database Seeder

## 🎯 Overview

Panduan ini menjelaskan cara menjalankan database migration dan seeder untuk aplikasi NP Analytics.

---

## 🚀 Quick Start

### 1. Migrate Database

Jalankan semua migrations untuk membuat tabel:

```bash
php artisan migrate
```

Output yang diharapkan:
```
Migration table created successfully.
Migrating: 2026_02_07_000001_create_categories_table
Migrated:  2026_02_07_000001_create_categories_table
Migrating: 2026_02_07_000002_create_indicators_table
Migrated:  2026_02_07_000002_create_indicators_table
Migrating: 2026_02_07_000003_create_phenomena_table
Migrated:  2026_02_07_000003_create_phenomena_table
```

---

### 2. Seed Database dengan Data Sample

Ada dua cara untuk seed database:

#### A. **Seed Semua Data (Recommended)**

```bash
php artisan db:seed
```

Ini akan menjalankan `DatabaseSeeder` yang secara otomatis memanggil:
- `CategorySeeder`
- `IndicatorSeeder`
- `PhenomenonSeeder`

#### B. **Seed Spesifik per Seeder**

```bash
# Seed hanya categories
php artisan db:seed --class=CategorySeeder

# Seed hanya indicators
php artisan db:seed --class=IndicatorSeeder

# Seed hanya phenomena
php artisan db:seed --class=PhenomenonSeeder
```

---

### 3. Reset & Re-seed (Fresh Start)

Jika ingin menghapus semua data dan mulai dari awal:

```bash
php artisan migrate:fresh --seed
```

⚠️ **WARNING**: Command ini akan **MENGHAPUS SEMUA DATA** di database!

---

## 📋 Data yang Di-seed

### 1. **Categories** (6 kategori)

| ID | Nama | Icon |
|----|------|------|
| 1 | Ekonomi | lucide-trending-up |
| 2 | Sosial | lucide-users |
| 3 | Pertanian | lucide-wheat |
| 4 | Kependudukan | lucide-user-check |
| 5 | Industri | lucide-factory |
| 6 | Infrastruktur | lucide-building-2 |

---

### 2. **Indicators** (14 indikator realistis)

#### Ekonomi
- PDRB Atas Dasar Harga Berlaku (125.876,45 miliar rupiah, +5.23%)
- PDRB ADHK (Pertumbuhan Ekonomi) (4,93%, +0,40%)
- Inflasi Kota Jember (y-on-y) (2,87%, -0,45%) ⬇️ **is_higher_better: false**
- Tingkat Pengangguran Terbuka (4,01%, -0,92%) ⬇️ **is_higher_better: false**

#### Sosial
- Indeks Pembangunan Manusia (74,24 poin, +0,85%)
- Tingkat Kemiskinan (9,82%, -0,45%) ⬇️ **is_higher_better: false**
- Rata-rata Lama Sekolah (8,92 tahun, +0,15%)
- Angka Harapan Hidup (71,25 tahun, +0,22%)

#### Pertanian
- Produksi Padi (612.450,00 ton GKG, +3,25%)
- Produktivitas Padi (5,82 ton/ha, +2,45%)
- Luas Panen Padi (145.320,00 ha, +0,72%)

#### Kependudukan
- Jumlah Penduduk (2.456.789 jiwa, +1,02%)
- Kepadatan Penduduk (1.245,32 jiwa/km², +1,05%) ⬇️ **is_higher_better: false**
- Rasio Jenis Kelamin (98,50 per 100, +0,12%)

---

### 3. **Phenomena** (10 fenomena)

Setiap indikator utama memiliki 1-2 fenomena yang menjelaskan faktor penyebab perubahan:

| Indicator | Phenomena Count | Contoh |
|-----------|----------------|--------|
| PDRB | 2 | Pertumbuhan Sektor Perdagangan (+), Penurunan Sektor Konstruksi (-) |
| IPM | 2 | Peningkatan Akses Pendidikan (+), Perbaikan Layanan Kesehatan (+) |
| Kemiskinan | 2 | Program Bantuan Sosial (+), Kenaikan Harga Bahan Pokok (-) |
| Produksi Padi | 2 | Program Intensifikasi Pertanian (+), Alih Fungsi Lahan (-) |
| Inflasi | 2 | Stabilitas Pasokan Pangan (neutral), Kenaikan Tarif Listrik (-) |

---

## 🎨 Smart Trend Logic

Seeder mengimplementasikan **Smart Trend Logic** dengan benar:

### Indikator Positif (`is_higher_better: true`)
- ✅ Trend naik (+) = **HIJAU** (bagus)
- ❌ Trend turun (-) = **MERAH** (buruk)

Contoh: PDRB, Produksi Padi, IPM

### Indikator Negatif (`is_higher_better: false`)
- ❌ Trend naik (+) = **MERAH** (buruk)
- ✅ Trend turun (-) = **HIJAU** (bagus)

Contoh: Kemiskinan, Inflasi, Pengangguran

---

## 🔧 Troubleshooting

### Error: "Class 'Database\Seeders\CategorySeeder' not found"

**Solusi**: Jalankan autoload composer:
```bash
composer dump-autoload
```

### Error: "SQLSTATE[23000]: Integrity constraint violation"

**Solusi**: Ada duplikasi data. Reset database:
```bash
php artisan migrate:fresh --seed
```

### Error: "Base table or view not found"

**Solusi**: Migrations belum dijalankan. Jalankan:
```bash
php artisan migrate
```

---

## 📊 Verifikasi Data

### Cek Jumlah Records

```bash
php artisan tinker
```

Lalu jalankan:
```php
// Cek jumlah data
App\Models\Category::count();      // Should be 6
App\Models\Indicator::count();     // Should be 14
App\Models\Phenomenon::count();    // Should be 10

// Cek data spesifik
App\Models\Category::all()->pluck('name');
App\Models\Indicator::where('is_higher_better', false)->get();
```

---

## 🧪 Testing dengan Factory

Jika ingin generate data random untuk testing:

```bash
php artisan tinker
```

```php
// Generate 5 random indicators
App\Models\Indicator::factory(5)->create();

// Generate indicators dengan relasi lengkap
App\Models\Category::factory()
    ->has(App\Models\Indicator::factory(3)->has(
        App\Models\Phenomenon::factory(2)
    ))
    ->create();
```

---

## 📝 Custom Seeding

Jika ingin menambahkan data sendiri, edit file seeder:

1. **CategorySeeder**: `database/seeders/CategorySeeder.php`
2. **IndicatorSeeder**: `database/seeders/IndicatorSeeder.php`
3. **PhenomenonSeeder**: `database/seeders/PhenomenonSeeder.php`

Setelah edit, jalankan ulang:
```bash
php artisan db:seed --class=NamaSeeder
```

---

## 🎯 Production Deployment

Untuk production (Android via NativePHP):

```bash
# 1. Migrate & seed in one command
php artisan migrate:fresh --seed --force

# 2. Optimize autoload
composer install --optimize-autoloader --no-dev

# 3. Cache config & routes
php artisan config:cache
php artisan route:cache
```

---

## 💡 Tips

1. **Offline-First**: Semua data di-seed ke SQLite, tidak perlu koneksi internet
2. **Realistic Data**: Data seeder menggunakan nilai statistik realistis Indonesia
3. **Smart Colors**: Warna trend otomatis berdasarkan `is_higher_better`
4. **Relational Integrity**: Foreign keys dijaga dengan `onDelete('cascade')`

---

## 📚 Referensi

- [Laravel Seeding Documentation](https://laravel.com/docs/10.x/seeding)
- [Laravel Factory Documentation](https://laravel.com/docs/10.x/eloquent-factories)
- Project: NP Analytics by Nanang Pamungkas
