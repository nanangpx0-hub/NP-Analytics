# NP Analytics

Aplikasi statistik daerah **offline-first** untuk Android. Data BPS Kabupaten
Jember disimpan di SQLite perangkat, disinkronkan dua arah dengan backend admin
melalui token perangkat, dan analisis AI diproksikan server — tidak ada API key
Gemini di dalam APK.

Dibangun dengan Laravel 11, Livewire 3, NativePHP Mobile 3, dan Tailwind CSS.

---

## Fitur utama

- **Offline-first** — dashboard, daftar indikator, detail, dan grafik tetap
  berjalan tanpa jaringan.
- **Smart Trend Logic** — satu sumber kebenaran `is_higher_better`; arah
  perbaikan diturunkan otomatis.
- **Sinkronisasi aman** — aktivasi perangkat sekali pakai, token Sanctum per
  perangkat, protokol versi global, deteksi konflik, dan tombstone.
- **Analisis AI** — proxy Gemini di backend dengan cache hash konteks; fallback
  analisis lokal saat offline.
- **Grafik time-series** — komponen SVG lokal tanpa CDN.
- **Multi-tahun** — seri indikator ditautkan lewat `series_key`, bukan judul.
- **HyperOS/Android 14–17** — safe area, edge-to-edge, predictive back, dan
  penyesuaian performa SQLite.

---

## Tech stack

| Layer | Teknologi |
|-------|-----------|
| Aplikasi | Laravel 11, Livewire 3, Blade |
| Mobile wrapper | NativePHP Mobile 3 |
| Database | SQLite (WAL) |
| CSS/JS | Tailwind CSS 3 + Vite |
| Paket bersama | `packages/np-analytics-core` (Composer path repository) |
| Server | `np-analytics-backend` (Laravel 11 + MySQL/SQLite) |

---

## Prasyarat

- PHP 8.3+ dengan ekstensi `pdo_sqlite`, `sqlite3`, `mbstring`, `intl`, `zip`.
  Pastikan `php` tersedia di `PATH`.
- Composer 2.
- Node.js 18+ dan npm.
- Android SDK + JDK 17 (hanya untuk build APK).

Tidak ada path absolut mesin tertentu yang disimpan di repository. Jika `php`
belum ada di `PATH`, panggil binary PHP Anda secara eksplisit saat menjalankan
perintah.

---

## Setup lokal

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

Database SQLite lokal berada di `database/private/database.sqlite` dan dibuat
otomatis saat aplikasi/command pertama kali dijalankan. Folder tersebut
dikecualikan dari Git dan dari bundel APK.

Buka `http://localhost:8000`. Untuk pengembangan aset:

```bash
npm run dev
```

---

## Struktur project

```
NP-Analytics/
├── app/
│   ├── Livewire/                  # Dashboard, daftar/detail indikator, pengaturan sync
│   ├── Models/                    # Model aplikasi + status sync lokal
│   ├── Services/                  # SyncService, GeminiInsightService, AI client
│   └── Providers/
├── database/
│   ├── migrations/                # Termasuk migrasi protokol sync
│   └── seeders/                   # Master data (upsert idempoten)
├── packages/np-analytics-core/    # Model/enum/mapper/seeder bersama (vendored)
├── resources/
│   ├── views/                     # Layout, komponen, Livewire views, grafik SVG
│   └── css/js/
├── routes/web.php
├── tests/                         # Feature & unit test mobile
├── docs/
│   ├── SYNC_GUIDE.md              # Protokol sync lengkap
│   ├── OPTIMASI_ANDROID_DAN_GEMINI.md
│   ├── PANDUAN_GIT.md
│   └── SEEDER_GUIDE.md
└── pint.json
```

Paket bersama dipakai lewat `repositories` bertipe `path` dengan
`"symlink": false`. Dengan begitu kode ikut tersalin ke APK dan tidak bergantung
pada symlink Windows.

---

## Database dan data master

Seeder master menyediakan data BPS Kabupaten Jember:

- 8 kategori
- 66 indikator (PDRB, inflasi, kemiskinan, ketenagakerjaan, IPM, pertanian,
  pariwisata, kependudukan/PODES)
- 16 fenomena

Seeder memakai **upsert berdasarkan natural key** (`name`, `series_key`+`year`,
`indicator`+`order`) dan UUID v5 deterministik, sehingga menjalankan ulang
seeder tidak membuat duplikat. Data lama yang sudah terlanjur ganda dapat
dirapikan dengan:

```bash
php artisan sync:dedupe
```

Tabel tambahan pada perangkat: `sync_states`, `sync_conflicts`,
`device_credentials`, `ai_insights`. Database lokal berada di
`database/private/` dan tidak boleh masuk Git.

---

## Sinkronisasi

1. Admin backend membuat kode aktivasi sekali pakai.
2. Di aplikasi, buka **Pengaturan Sync** dan isi URL server, kode aktivasi,
   serta nama perangkat.
3. Sync pertama melakukan **pull penuh**, lalu mengirim record lokal yang
   `dirty`, lalu pull inkremental. Perangkat yang sudah ter-seed tidak
   menghasilkan duplikat.

Token perangkat disimpan terenkripsi di SQLite, bukan di `.env`. Detail
protokol, bentuk payload, konflik, dan troubleshooting ada di
[docs/SYNC_GUIDE.md](docs/SYNC_GUIDE.md).

---

## Analisis AI

Mobile tidak menyimpan API key. `GeminiInsightService` memanggil
`POST /api/ai/insight` di backend dengan token perangkat (ability
`ai:insight`). Server memakai `GEMINI_API_KEY` miliknya sendiri, cache hash
konteks, dan rate limit per perangkat. Saat offline atau server tidak
dikonfigurasi, aplikasi menampilkan analisis lokal yang disusun dari data
perangkat.

---

## Pengujian dan kualitas kode

```bash
php artisan test                  # Feature + unit test (SQLite in-memory)
vendor/bin/pint --test            # Gaya kode Laravel
vendor/bin/phpstan analyse        # Larastan level 5
```

CI GitHub Actions (`.github/workflows/ci.yml`) menjalankan ketiganya pada PHP
8.3 untuk setiap push dan pull request.

---

## Build APK Android

Naikkan `NATIVEPHP_APP_VERSION_CODE` di `.env` setiap kali skema database
berubah; aplikasi akan menjalankan `php artisan migrate --force` sekali saat
versi kode berubah.

```bash
npm run build

# Password dibaca dari environment shell, jangan ditulis di repository.
php artisan native:package android --build-type=release --no-tty \
  --keystore="android-keystore/np-analytics.keystore" \
  --keystore-password="$KEYSTORE_PASSWORD" \
  --key-alias="npanalytics" \
  --key-password="$KEY_PASSWORD" \
  --output=dist
```

Exit code `0` dari NativePHP **tidak menjamin** Gradle menghasilkan APK.
Selalu verifikasi:

```bash
ls -lh dist/*.apk
apksigner verify --print-certs dist/app-release.apk

# Bundel Laravel tidak boleh membawa keystore, database lokal, log, atau APK lama.
unzip -l dist/app-release.apk | grep -Ei 'keystore|\.jks|\.sqlite|laravel\.log|dist/app-release'
```

Perintah terakhir harus tidak menghasilkan output. APK baru membuat
`database/private/database.sqlite` saat pertama kali dijalankan, lalu
menjalankan migrasi dan seeder.

Panduan lengkap optimasi Android, signing, dan troubleshooting:
[docs/OPTIMASI_ANDROID_DAN_GEMINI.md](docs/OPTIMASI_ANDROID_DAN_GEMINI.md).

### Keamanan signing

- Password keystore tidak disimpan di repository. `gen_keystore.bat` membaca
  `KEYSTORE_PASSWORD` atau meminta input interaktif.
- `android-keystore/`, `keys/`, `*.jks`, `*.keystore`, `dist/`, `build-output/`,
  `*.apk`, `database/private/`, dan `.env` dikecualikan dari Git.
- Bundel APK juga mengecualikan keystore, database lokal, log, dan artefak
  `dist/` melalui `config/nativephp.php`.
- **Keystore lama harus dirotasi** karena password-nya pernah tersimpan sebagai
  plaintext. Buat keystore baru, simpan salinan aman di luar repository, lalu
  distribusikan APK yang ditandatangani ulang.
- Jika kredensial telanjur masuk riwayat Git, hapus dari riwayat dan rotasi
  kredensial tersebut.
- Alur commit/push manual ada di [docs/PANDUAN_GIT.md](docs/PANDUAN_GIT.md).

---

## Lisensi

Copyright © 2026 Nanang Pamungkas. All rights reserved.
