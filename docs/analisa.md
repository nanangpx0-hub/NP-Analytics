# Analisa Komprehensif NP Analytics (Revisi)

**Metadata**
| Item | Nilai |
| --- | --- |
| Tanggal analisis | 2026-02-07 |
| Lokasi kode | e:\laragon\www\NP Analytics |
| Metode | Review statik kode dan dokumen, tanpa menjalankan aplikasi |
| Target platform | Android (NativePHP, berdasarkan README dan blueprint) |
| Stack inti | Laravel 10, Livewire 3, SQLite, Tailwind CSS (build lokal via Vite) |

## Ringkasan Eksekutif
NP Analytics adalah aplikasi statistik daerah berbasis Laravel 10 dan Livewire 3 dengan pendekatan offline-first menggunakan SQLite lokal. Arsitektur aplikasi bersifat monolit in-app dengan UI reaktif berbasis Blade + Livewire. Pada revisi ini, pipeline asset telah dipindahkan ke build lokal (Vite + Tailwind), warna brand telah distandardisasi dalam konfigurasi Tailwind, dukungan `impact = neutral` telah diterapkan di UI dan statistik, serta seeder telah diselaraskan dengan blueprint dan data sample Jember.

Sisa area risiko utama: filter tahun pada daftar indikator masih statik, list indikator belum dipaginasi, pencarian belum dioptimasi indeks/FTS, belum ada test otomatis, dan konfigurasi NativePHP belum tersedia. Aplikasi sudah lebih konsisten untuk mode offline, namun proses build asset perlu dipastikan menjadi bagian standar rilis.

## Ruang Lingkup dan Metodologi
Analisis ini mencakup pemeriksaan file dan konfigurasi berikut:

| Kategori | Lokasi file |
| --- | --- |
| Dokumentasi | `README.md`, `docs/Blueprint NP Analytics.md`, `docs/SEEDER_GUIDE.md`, `docs/DATA_SAMPLE_JEMBER.csv` |
| Routing | `routes/web.php`, `routes/api.php` |
| Logika bisnis | `app/Livewire/*.php`, `app/Models/*.php` |
| Skema data | `database/migrations/*.php` |
| Seed data | `database/seeders/*.php` |
| UI | `resources/views/**/*.blade.php` |
| Asset pipeline | `package.json`, `vite.config.js`, `postcss.config.js`, `tailwind.config.js`, `config/vite.php`, `resources/css/app.css`, `resources/js/app.js` |
| Konfigurasi | `config/*.php`, `composer.json` |

Catatan penting:
- Tidak ada pengujian runtime, profiling, atau eksekusi query langsung.
- Temuan bersifat statik berdasarkan isi repositori saat analisis.

## Arsitektur Sistem
Aplikasi menggunakan pola monolit in-app dengan komponen UI reaktif dan asset build lokal.

```text
[Android Wrapper - NativePHP] (planned)
        |
[PHP Runtime]
        |
[Laravel 10 + Livewire 3]
        |
[Eloquent ORM]
        |
[SQLite database.sqlite]
        |
[Blade Views] <-> [Vite Build: Tailwind CSS + JS]
```

Implikasi arsitektur:
- Semua proses berjalan lokal sehingga latensi rendah dan tidak bergantung pada koneksi jaringan.
- Asset CSS/JS harus dibuild terlebih dahulu untuk memastikan aplikasi tetap fungsional di mode offline.

## Alur Bisnis dan User Flow
| Langkah | Deskripsi | Komponen |
| --- | --- | --- |
| 1 | Root dialihkan ke dashboard | `routes/web.php`, `app/Livewire/Dashboard.php` |
| 2 | Dashboard menampilkan ringkasan dan filter tahun | `resources/views/livewire/dashboard.blade.php` |
| 3 | User membuka daftar indikator, melakukan search dan filter kategori/tahun | `app/Livewire/IndicatorList.php`, `resources/views/livewire/indicator-list.blade.php` |
| 4 | User memilih indikator untuk melihat detail | `app/Livewire/IndicatorDetail.php`, `resources/views/livewire/indicator-detail.blade.php` |
| 5 | Detail menampilkan value, trend, dan fenomena (positif/negatif/netral) | Model `Indicator`, `Phenomenon` |

## Komponen Utama
| Komponen | Lokasi | Tanggung jawab |
| --- | --- | --- |
| Dashboard Livewire | `app/Livewire/Dashboard.php` | Ringkasan statistik per tahun, daftar kategori dan indikator |
| Indicator List Livewire | `app/Livewire/IndicatorList.php` | Pencarian dan filter indikator |
| Indicator Detail Livewire | `app/Livewire/IndicatorDetail.php` | Detail indikator dan fenomena |
| Model Category | `app/Models/Category.php` | Relasi kategori ke indikator |
| Model Indicator | `app/Models/Indicator.php` | Smart Trend Logic, format value |
| Model Phenomenon | `app/Models/Phenomenon.php` | Impact logic, label, urutan, sumber |
| Layout UI | `resources/views/layouts/app.blade.php` | Layout global dan navigasi |
| Komponen UI | `resources/views/components/indicator-card.blade.php` | Kartu indikator reusable |
| Asset Pipeline | `vite.config.js`, `tailwind.config.js` | Build CSS/JS lokal (offline-ready) |
| Migrasi DB | `database/migrations/*.php` | Skema dan indeks |
| Seeder | `database/seeders/*.php` | Data awal kategori, indikator, fenomena |

## Dependensi Teknologi
| Layer | Teknologi | Versi/Status | Catatan |
| --- | --- | --- | --- |
| Runtime | PHP | ^8.1 | Requirement composer |
| Backend | Laravel | ^10.0 | Framework utama |
| UI Reaktif | Livewire | ^3.0 | Komponen server-driven |
| Auth/API | Sanctum | ^3.2 | Belum digunakan pada routing saat ini |
| HTTP Client | Guzzle | ^7.2 | Belum tampak dipakai |
| Database | SQLite | lokal | Offline-first |
| Styling | Tailwind CSS | build lokal via Vite | Menggantikan CDN |
| Asset Pipeline | Vite + PostCSS | aktif | `@vite` di layout |
| Mobile Wrapper | NativePHP | planned | Belum ada konfigurasi di repo |

## Model Data dan Skema
| Tabel | Kolom kunci | Relasi | Indeks |
| --- | --- | --- | --- |
| `categories` | `id`, `name`, `icon` | 1-N ke `indicators` | default PK |
| `indicators` | `id`, `category_id`, `title`, `value`, `year`, `trend` | N-1 ke `categories`, 1-N ke `phenomena` | `category_id, year`, `year` |
| `phenomena` | `id`, `indicator_id`, `impact`, `source`, `order` | N-1 ke `indicators` | `indicator_id`, `impact` |

## Analisis Performa
Observasi utama:
- `IndicatorList` memuat seluruh data indikator dengan `get()` tanpa pagination.
- Dashboard menghitung statistik dengan beberapa query terpisah per render.
- Pencarian menggunakan `LIKE` tanpa indeks pada `title`, sehingga berpotensi lambat pada data besar.
- Asset sekarang dibuild lokal sehingga waktu render awal lebih stabil dan cocok untuk offline.

Optimasi yang disarankan:
- Tambahkan pagination dan lazy loading pada `IndicatorList`.
- Gabungkan agregasi statistik dashboard atau gunakan caching ringan.
- Tambahkan indeks pada `title` atau gunakan FTS SQLite jika data besar.
- Pastikan build asset (`npm run build`) menjadi bagian proses rilis.

## Analisis Keamanan
- Tidak ada autentikasi dan otorisasi. Jika aplikasi diekspose ke jaringan, data dapat diakses bebas.
- Tidak ada pembatasan rate untuk web routes, meskipun ada rate limiter untuk API (API masih kosong).
- Dependensi asset lokal mengurangi risiko supply-chain CDN, namun tetap perlu audit dependensi Node.
- File `.env` ada di root repo. Pastikan tidak berisi kredensial sensitif dan tidak dipublikasikan.
- Data SQLite tersimpan lokal tanpa enkripsi. Jika perangkat hilang, data bisa diambil langsung.

## Analisis Skalabilitas
- Arsitektur offline-first sangat cocok untuk single user lokal dan dataset kecil-menengah.
- Skalabilitas terbatas oleh ukuran file SQLite dan performa query saat data membesar.
- Untuk kebutuhan multi-user atau sinkronisasi data, perlu API terpisah, skema otorisasi, dan database server (misal MySQL/PostgreSQL).
- Cache driver masih file-based, cocok untuk lokal tetapi tidak untuk multi-instance.

## Temuan Utama (Status Saat Ini)
| ID | Kategori | Temuan | Dampak | Bukti |
| --- | --- | --- | --- | --- |
| T1 | UX/Data | Filter tahun di daftar indikator memakai rentang statik 2020 sampai tahun kini | Tahun di luar rentang tidak terlihat | `resources/views/livewire/indicator-list.blade.php` |
| T2 | Performa | Tidak ada pagination pada list indikator | Potensi lambat pada dataset besar | `app/Livewire/IndicatorList.php` |
| T3 | Performa | Pencarian `LIKE` tanpa indeks pada `title` | Pencarian melambat saat data besar | `app/Livewire/IndicatorList.php`, skema `indicators` |
| T4 | Kualitas | Tidak ada folder `tests/` | Risiko regresi lebih tinggi | struktur repo |
| T5 | Platform | Konfigurasi NativePHP belum ada | Build Android belum siap | `config/` |
| T6 | Release | Build asset wajib sebelum rilis offline | UI tanpa CSS bila build terlewat | `resources/views/layouts/app.blade.php`, `vite.config.js` |

## Perubahan Penting Sejak Analisa Sebelumnya
| Perubahan | Status | Catatan |
| --- | --- | --- |
| Tailwind CDN diganti build lokal | Selesai | `@vite` di layout, Vite config aktif |
| Warna brand distandardisasi | Selesai | `tailwind.config.js` + kelas `brand-*` di UI |
| Dukungan `impact = neutral` di UI | Selesai | Statistik dan tampilan fenomena netral sudah ada |
| Seeder disinkronkan dengan blueprint/data sample | Selesai | Indikator dan fenomena konsisten |

## Rekomendasi Perbaikan
| Prioritas | Tindakan | Dampak | Estimasi |
| --- | --- | --- | --- |
| Sedang | Ubah filter tahun agar berasal dari DB | UX lebih akurat | Rendah |
| Sedang | Tambahkan pagination dan optimasi query list indikator | Performa meningkat saat data besar | Sedang |
| Sedang | Tambahkan indeks atau FTS untuk pencarian | Query search lebih cepat | Sedang |
| Sedang | Tambahkan test dasar (unit dan Livewire) | Stabilitas rilis | Sedang |
| Sedang | Tambahkan konfigurasi NativePHP sesuai blueprint | Persiapan build Android | Sedang |
| Rendah | Tambahkan checklist build asset di dokumentasi rilis | Mengurangi risiko CSS hilang | Rendah |

## Lampiran Data Pendukung
**Cuplikan integrasi Vite (layout)**
```php
@vite(['resources/css/app.css', 'resources/js/app.js'])
```

**Cuplikan statistik fenomena (neutral)**
```php
$phenomenaStats = [
    'total' => $this->indicator->phenomena->count(),
    'positive' => $phenomenaGrouped->get('positive', collect())->count(),
    'negative' => $phenomenaGrouped->get('negative', collect())->count(),
    'neutral' => $phenomenaGrouped->get('neutral', collect())->count(),
];
```

**Cuplikan Tailwind brand config**
```js
colors: {
  brand: {
    navy: '#0f172a',
    gold: '#f59e0b',
    bg: '#f8fafc',
    surface: '#ffffff',
    success: '#10b981',
    danger: '#ef4444',
    neutral: '#94a3b8',
  },
},
```

**Cuplikan indikator seeder (sinkron dengan data sample)**
```php
[
    'title' => 'Inflasi Kota Jember (y-on-y)',
    'value' => 2.87,
    'unit' => '%',
    'year' => 2024,
    'trend' => -0.45,
    'is_higher_better' => false,
],
```

**Cuplikan query list indikator**
```php
$query = Indicator::with(['category', 'phenomena'])
    ->where('year', $this->selectedYear);
$indicators = $query->orderBy('title')->get();
```
