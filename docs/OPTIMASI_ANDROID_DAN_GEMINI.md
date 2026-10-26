# Dokumentasi Optimasi Android, Performa, dan Gemini AI

**Proyek:** NP Analytics — Aplikasi Statistik Daerah Offline-First
**Target perangkat:** POCO X7 (Android 14–17 / API 34–37, HyperOS, MediaTek Dimensity 7300-Ultra, RAM 8/12 GB, layar 120 Hz)
**Tanggal rilis APK terakhir:** 26 Oktober 2026
**Status:** Hardening FASE 1–6 selesai — 50 test mobile lulus. APK release bertanda tangan sudah dibangun dan diverifikasi; bundel tidak memuat keystore, database lokal, log, atau API key.

---

## 1. Ringkasan

Optimasi mencakup lima area:

| Area | Hasil |
|------|-------|
| Kompatibilitas Android 14–17 | Predictive Back, multi-window, target SDK 36, tanpa kunci orientasi |
| UI/UX HyperOS | Edge-to-edge, safe area, status bar terang, navigasi SPA |
| Performa | SQLite WAL + mmap + cache, sinkronisasi transaksional, query dashboard tunggal, OPcache |
| Gemini AI | Analisis indikator & dashboard, cache offline di SQLite, fallback lokal |
| Distribution | APK release signed `arm64-v8a`, versionCode 41, siap sideload |

---

## 2. Kompatibilitas Android 14–17

### 2.1 AndroidManifest

File: `nativephp/android/app/src/main/AndroidManifest.xml`

| Atribut | Nilai | Tujuan |
|---------|-------|--------|
| `android:enableOnBackInvokedCallback` | `true` | Mengaktifkan Predictive Back Android 13+ |
| `android:resizeableActivity` | `true` | Dukungan split-screen, floating window, dan desktop mode |
| `android:configChanges` | `uiMode\|colorMode\|orientation\|screenSize\|smallestScreenSize\|screenLayout\|keyboardHidden` | Activity tidak dibuat ulang saat rotasi/resize |
| `tools:targetApi` | `36` | Menandai kompatibilitas API 36 |
| `android:screenOrientation` | tidak ada | Orientasi bebas; dikonfigurasi di `config/nativephp.php` |
| `android:networkSecurityConfig` | `@xml/network_security_config` | Kebijakan koneksi jaringan eksplisit |

`config/nativephp.php` mengaktifkan seluruh orientasi Android:

```php
'android' => [
    'portrait' => true,
    'upside_down' => true,
    'landscape_left' => true,
    'landscape_right' => true,
],
```

### 2.2 Predictive Back

File: `nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt`

```kotlin
onBackPressedDispatcher.addCallback(this, object : OnBackPressedCallback(true) {
    override fun handleOnBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack()
        } else {
            isEnabled = false
            onBackPressedDispatcher.onBackPressed()
        }
    }
})
```

Perilaku:

1. Tombol/gestur back menelusuri riwayat WebView terlebih dahulu.
2. Jika riwayat habis, callback dinonaktifkan dan sistem menangani back (keluar aplikasi).
3. Gestur prediktif tetap berjalan karena callback terdaftar pada `OnBackPressedDispatcher`.

### 2.3 Rotasi dan Multi-Window

`onConfigurationChanged()` meminta ulang insets dan mengonfigurasi status bar setiap kali orientasi, split-screen, atau floating window berubah:

```kotlin
override fun onConfigurationChanged(newConfig: Configuration) {
    super.onConfigurationChanged(newConfig)
    window.decorView.requestApplyInsets()
    configureStatusBar()
}
```

---

## 3. UI/UX HyperOS

### 3.1 Edge-to-Edge dan Status Bar

File: `MainActivity.kt`

- `WindowCompat.setDecorFitsSystemWindows(window, false)` — konten menggambar sampai tepi layar.
- `statusBarStyle = "light"` — ikon status bar putih di atas header navy.
- Status bar dan navigation bar transparan.
- Nilai default yang sama di `config/nativephp.php`: `'status_bar_style' => 'light'`.

### 3.2 Safe Area

NativePHP menyuntikkan `WindowInsets` sebagai CSS variable melalui `injectSafeAreaInsets()`:

```css
:root {
    --inset-top: ...;
    --inset-right: ...;
    --inset-bottom: ...;
    --inset-left: ...;
}
```

Style disuntikkan ulang pada event `livewire:navigated` agar tetap berlaku setelah navigasi Livewire.

File: `resources/css/app.css`

| Class | Fungsi |
|-------|--------|
| `.safe-area-top` | Padding atas = `max(--inset-top, env(safe-area-inset-top))` |
| `.safe-area-bottom` | Padding bawah = `max(--inset-bottom, env(safe-area-inset-bottom))` |
| `.livewire-progress-top` | Loading bar Livewire turun sesuai tinggi status bar |
| `.app-main-bottom-safe` | Ruang bawah `7rem` + inset; `2.5rem` di desktop |

Penerapan di `resources/views/layouts/app.blade.php`:

- Top bar: `safe-area-top`
- Bottom tab bar: `safe-area-bottom`
- `<main>`: `app-main-bottom-safe`
- Loading bar: `livewire-progress-top`

### 3.3 HyperOS Smart Dark Mode

File: `nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt`

```kotlin
if (WebViewFeature.isFeatureSupported(WebViewFeature.ALGORITHMIC_DARKENING)) {
    WebSettingsCompat.setAlgorithmicDarkeningAllowed(webView.settings, false)
}
```

HyperOS tidak diizinkan membalik warna aplikasi secara algoritmik; palet navy/amber/green/red tetap konsisten.

Dukungan tambahan:

- `<meta name="color-scheme" content="light">`
- `html { color-scheme: light; }`
- `-webkit-tap-highlight-color: transparent`

### 3.4 Navigasi SPA

Seluruh tautan navigasi utama memakai `wire:navigate`:

- Logo dan menu Dashboard
- Menu Indikator
- Bottom tab bar (Beranda, Indikator)

Navigasi tidak memuat ulang seluruh halaman, sehingga transisi lebih cepat dan insets tidak berkedip.

---

## 4. Performa

### 4.1 Pragma SQLite

File: `app/Providers/AppServiceProvider.php` — `configureSqlite()`

| Pragma | Nilai | Dampak |
|--------|-------|--------|
| `journal_mode` | `WAL` | Baca dan tulis tidak saling memblokir |
| `synchronous` | `NORMAL` | Trade-off aman untuk mobile, lebih cepat dari `FULL` |
| `temp_store` | `MEMORY` | Operasi sorting/temp tidak menyentuh disk |
| `mmap_size` | `67108864` (64 MB) | Memori-mapped I/O |
| `cache_size` | `-16000` (±16 MB) | Cache halaman lebih besar |

Pragma diterapkan sekali saat boot, termasuk pada konteks console, dan dibungkus `try/catch` agar kegagalan tidak menghentikan aplikasi.

### 4.2 Sinkronisasi Transaksional

File: `app/Services/SyncService.php` — `applyPull()`

Sebelumnya setiap record disimpan terpisah. Sekarang:

1. Seluruh payload diterapkan dalam satu `DB::transaction()`.
2. Model yang sudah ada dipetakan berdasarkan UUID:

```php
$categoryMap   = Category::withTrashed()->get()->keyBy('uuid');
$indicatorMap  = Indicator::withTrashed()->get()->keyBy('uuid');
$phenomenonMap = Phenomenon::withTrashed()->get()->keyBy('uuid');
```

3. Record baru langsung dimasukkan ke map agar relasi dalam payload yang sama dapat ditemukan.
4. Jika transaksi gagal, semua perubahan di-rollback dan penghitung `pulled` direset ke nol.
5. Error per record tetap dikumpulkan tanpa membatalkan keseluruhan proses.

Manfaat: konsistensi data, jumlah query tetap, dan tidak ada data setengah jadi saat koneksi terputus.

### 4.3 Query Dashboard

File: `app/Livewire/Dashboard.php`

- Satu eager load untuk kategori beserta indikator tahun terpilih:

```php
Category::with(['indicators' => fn ($query) => $query->where('year', $this->selectedYear)])
    ->whereHas('indicators', fn ($query) => $query->where('year', $this->selectedYear))
    ->get();
```

- Statistik dihitung dari koleksi yang sudah dimuat (`$categories->flatMap->indicators`), bukan tiga query terpisah.
- Trend `null` dihitung netral (tidak masuk positif maupun negatif).

### 4.4 Runtime NativePHP

File: `nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/LaravelEnvironment.kt`

- `optimize:clear` dan `migrate --force` hanya dijalankan jika bundle berubah atau database baru:

```kotlin
if (bundleChanged || isNewDatabase) {
    phpBridge.runArtisanCommand("optimize:clear")
    phpBridge.runArtisanCommand("migrate --force")
}
```

- `php.ini` runtime:

```ini
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=64
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
```

File: `WebViewManager.kt`

- WebView debugging hanya aktif pada build debug:

```kotlin
val isDebuggable = (context.applicationInfo.flags and ApplicationInfo.FLAG_DEBUGGABLE) != 0
if (isDebuggable) {
    WebView.setWebContentsDebuggingEnabled(true)
}
```

---

## 5. Analisis AI (Proxy Backend)

### 5.1 Arsitektur

Mobile **tidak pernah** memanggil Gemini langsung dan **tidak menyimpan API key
Gemini**. Semua analisis diproksikan ke backend:

- Mobile: membangun konteks dari data lokal, memanggil
  `POST /api/ai/insight` dengan token perangkat (ability `ai:insight`).
- Backend: memanggil Gemini memakai `GEMINI_API_KEY` milik server, menyimpan
  cache berdasarkan hash konteks, dan menerapkan rate limit per perangkat.
- Offline atau server tidak dikonfigurasi: mobile memakai cache lokal, lalu
  fallback analisis lokal deterministik dari data perangkat.

Konfigurasi mobile (`config/gemini.php`) hanya berisi path dan timeout:

```php
return [
    'path' => env('AI_INSIGHT_PATH', '/api/ai/insight'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
];
```

`GEMINI_API_KEY`, `GEMINI_MODEL`, dan `AI_CACHE_TTL_MINUTES` hanya ada di
`.env` backend.

### 5.2 Alur Analisis

```
Aksi Livewire
    │
    ▼
Bangun konteks (indikator / dashboard)
    │
    ▼
Hash SHA-256 = prompt_version | scope | model | JSON konteks
    │
    ├── Cache lokal ditemukan & tidak dipaksa refresh → tampilkan cache
    │
    ▼
POST /api/ai/insight (Bearer token perangkat)
    │
    ▼
Backend: cache hash konteks → Gemini (key server)
    │
    ├── Sukses → simpan ke ai_insights lokal → tampilkan sumber "Gemini"
    │
    └── Offline / 503 / gagal
            ├── Cache tersedia → tampilkan cache + notice
            └── Tidak ada cache → analisis lokal + notice
```

Konten analisis: `summary`, `root_cause`, dan `recommendations` (maksimum 6
disimpan). Konteks indikator mencakup nilai, satuan, tren,
`is_higher_better`, deskripsi, time-series, dan maksimal 10 fenomena lokal.
Konteks dashboard mencakup statistik agregat, kategori, dan 10 indikator dengan
perubahan tren terbesar.

### 5.3 Cache Offline

Tabel: `ai_insights` (mobile)

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `hash` | string(64), unique | Kunci cache SHA-256 |
| `scope` | string(20) | `indicator` atau `dashboard` |
| `indicator_id` | FK nullable | `nullOnDelete` |
| `year` | integer nullable | Tahun data |
| `model` | string(80) nullable | Nama model stabil dari server |
| `source` | string(20) | `gemini`, `cache`, atau `local` |
| `payload` | json | Ringkasan, akar masalah, rekomendasi |
| `generated_at` | timestamp nullable | Waktu analisis |

Perubahan angka atau fenomena menghasilkan hash baru sehingga cache tidak
basi. Cache tetap dapat dibaca saat perangkat offline.

### 5.4 Komponen UI

File: `resources/views/components/ai-insight-panel.blade.php`

- Header "Ringkasan & Rekomendasi AI"
- Badge sumber: **Gemini**, **Tersimpan offline**, atau **Analisis lokal**
- Tombol analisis/perbarui sesuai ketersediaan perangkat dan cache
- Status loading "Menganalisis..." dengan `wire:target`
- Notice kegagalan/ketersediaan cache
- Tiga bagian semantik: ringkasan eksekutif, akar masalah, rekomendasi

Digunakan di dashboard dan detail indikator.

### 5.5 Catatan Implementasi Livewire

Livewire menggabungkan properti publik ke data view **setelah** data dari
`render()`. Properti publik `?array $aiInsight = null` akan menimpa hasil cache
jika dikirim dengan nama kunci yang sama. Karena itu kedua komponen mengirim
hasil resolusi dengan nama **`resolvedAiInsight`**:

```php
$resolvedAiInsight = $this->aiInsight ?? $gemini->cachedForDashboard(...);
```

Jangan mengganti nama kunci ini tanpa memahami perilaku tersebut.

### 5.6 Keamanan

- Tidak ada `GEMINI_API_KEY`, `BPS_API_KEY`, atau password di source, `.env`
  mobile, maupun APK.
- Token perangkat disimpan terenkripsi di SQLite dan dapat dicabut admin.
- `cleanup_env_keys` di `config/nativephp.php` memuat `*_KEY`, `SYNC_*`, dan
  `GEMINI_*` sehingga variabel sensitif tidak ikut dibundel.
- Rate limit AI dan cache di server mencegah penyalahgunaan kunci.
- Rotasi `GEMINI_API_KEY` cukup dilakukan di server; APK tidak perlu diubah.

---

## 6. Build APK Release

### 6.1 Prasyarat

- PHP 8.3, Node.js 20+, JDK 17, Android SDK + build-tools.
- compileSdk/targetSdk 36, minSdk 33, ABI `arm64-v8a`.
- Keystore release di `android-keystore/` (diabaikan Git).

Signing dibaca dari environment berikut; nilainya tidak boleh disimpan di
repository, dokumentasi, atau shell history bersama:

| Environment variable | Keterangan |
|----------------------|------------|
| `ANDROID_KEYSTORE_FILE` | Path ke `android-keystore/np-analytics.keystore` |
| `ANDROID_KEYSTORE_PASSWORD` | Password keystore |
| `ANDROID_KEY_ALIAS` | `npanalytics` |
| `ANDROID_KEY_PASSWORD` | Password key |

`nativephp/android/gradle.properties` bersifat generated dan diabaikan Git;
file tersebut dapat memuat konfigurasi signing, jadi jangan pernah di-commit.

### 6.2 Langkah Build

```bash
# 1. Build aset frontend
npm run build

# 2. Jalankan seluruh test
php artisan test

# 3. Set kredensial signing untuk sesi shell ini (jangan ditulis di repo)
export ANDROID_KEYSTORE_FILE="android-keystore/np-analytics.keystore"
export ANDROID_KEY_ALIAS="npanalytics"
export ANDROID_KEYSTORE_PASSWORD="..."   # dari tempat penyimpanan aman
export ANDROID_KEY_PASSWORD="..."

# 4. Package APK release bertanda tangan
php artisan native:package android --build-type=release --no-tty --no-interaction --output=dist
```

Catatan:

- Command memvalidasi empat parameter signing tersebut secara terpisah dari
  `gradle.properties`.
- Proses memakan waktu beberapa menit karena Gradle mengompilasi native.
- NativePHP menaikkan `NATIVEPHP_APP_VERSION_CODE` di `.env` **setelah** build
  sukses.
- Jangan memakai `--skip-prepare` kecuali memang ingin mempertahankan kondisi
  project Android yang ada.

### 6.3 Verifikasi Wajib

Jangan menganggap exit code `0` sebagai bukti build berhasil. NativePHP dapat keluar dengan kode `0` sementara Gradle gagal dan tidak menghasilkan APK. Selalu periksa file dan tanda tangannya.

```bat
set SDK=%LOCALAPPDATA%\Android\Sdk
set BT=%SDK%\build-tools\37.0.0

dir dist\app-release.apk
"%BT%\apksigner.bat" verify --verbose --print-certs dist\app-release.apk
"%BT%\zipalign.exe" -c -v 4 dist\app-release.apk
"%BT%\aapt2.exe" dump badging dist\app-release.apk
"%BT%\aapt2.exe" dump xmltree --file AndroidManifest.xml dist\app-release.apk
```

Yang harus terlihat:

- `Verifies` dan `Verified using v2 scheme: true`
- Sertifikat `CN=NP Analytics` dengan SHA-256 `04:8D:92:A4:...:7B:14`
- `zipalign` OK
- Package `com.nanang.npanalytics`
- `targetSdkVersion 36`
- `enableOnBackInvokedCallback=true`
- `resizeableActivity=true`
- Tidak ada `screenOrientation`

Periksa juga isi bundel Laravel. Hasil `findstr` harus kosong:

```bat
"%ProgramFiles%\7-Zip\7z.exe" e -so dist\app-release.apk assets\laravel_bundle.zip > bundle-audit.zip
"%ProgramFiles%\7-Zip\7z.exe" l bundle-audit.zip | findstr /I "android-keystore .jks .sqlite laravel.log dist/app-release"
del bundle-audit.zip
```

APK tidak membawa keystore, database pengembangan, log, atau APK lama.
Satu-satunya nilai sensitif di `.env` bundel adalah `APP_KEY` Laravel yang
dibutuhkan untuk enkripsi lokal; tidak ada API key atau password layanan.
Database lokal dibuat otomatis di `database/private/database.sqlite` saat
aplikasi pertama kali dijalankan, lalu migrasi dan seeder dijalankan.

### 6.4 Artefak rilis terakhir (26 Oktober 2026)

| Item | Nilai |
|------|-------|
| Lokasi utama | `dist/app-release.apk` |
| Salinan Gradle | `nativephp/android/app/build/outputs/apk/release/app-release.apk` |
| Rilis GitHub | `https://github.com/nanangpx0-hub/NP-Analytics/releases/tag/v1.0.0-41` |
| Unduhan APK | `https://github.com/nanangpx0-hub/NP-Analytics/releases/download/v1.0.0-41/app-release.apk` |
| Ukuran | 69.196.840 bytes (65,99 MB) |
| Package | `com.nanang.npanalytics` |
| versionName | `1.0.0` |
| versionCode | `41` |
| Target SDK | `36` |
| ABI | `arm64-v8a` |
| SHA-256 APK | `89b5758ba4afca65ec4e971e5cbf34fe5e3bf3f6d1a16c340d133376ae2d104c` |
| Sertifikat | v2, `CN=NP Analytics`, SHA-256 `048d92a411348d103ed860e8570fb92dc696c5922bff98866b10add059497b14` |

Instalasi ke perangkat (ganti path sesuai lokasi Anda):

```bash
adb install -r dist/app-release.apk
```

### 6.5 Troubleshooting

| Gejala | Penyebab | Solusi |
|--------|----------|--------|
| `Unresolved reference 'BuildConfig'` | `buildConfig` tidak diaktifkan di `build.gradle.kts` | Gunakan `ApplicationInfo.FLAG_DEBUGGABLE` (sudah diterapkan) atau aktifkan `buildFeatures.buildConfig = true` |
| Exit code `0` tetapi APK tidak ada | Gradle gagal di tahap kompilasi | Periksa `nativephp/android-build.log` dan folder `app/build/outputs/apk/release` |
| Log memuat "Build output is unsigned" | Pesan Gradle untuk output sebelum proses signing NativePHP | Verifikasi dengan `apksigner`; selama `Verifies` muncul, APK sudah tertanda tangan |
| versionCode APK ≠ nilai `.env` | `.env` sudah dinaikkan otomatis setelah build | Normal; APK memakai nilai sebelum increment |
| Gradle memakai JDK salah | `JAVA_HOME` tidak konsisten | Set `NATIVEPHP_GRADLE_PATH` ke JDK 17 |
| Aplikasi tidak bisa update | versionCode tidak naik | Pastikan `NATIVEPHP_APP_VERSION_CODE` naik sebelum build |

---

## 7. Pengujian

Perintah:

```bash
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse
```

Hasil terakhir: **50 test lulus, 233 assertions**.

Cakupan penting:

- `tests/Feature/SyncServiceTest.php` — 12 test: bootstrap tanpa duplikat,
  dirty tracking, konflik & resolusi, null eksplisit, pagination, offline,
  rate limit, lock sync.
- `tests/Feature/DatabaseBootstrapperTest.php` — 4 test: migrasi saat
  versionCode berubah, idempoten, dan tidak menghapus data.
- `tests/Feature/ReleaseBundleSafetyTest.php` — 3 test: pembuatan database baru,
  database in-memory, dan daftar eksklusi bundel/API key.
- `tests/Feature/GeminiInsightTest.php` — 7 test: klien proxy, cache lokal,
  fallback lokal saat offline/503, panel dashboard/detail.
- `tests/Feature/DeviceCredentialTest.php` — 3 test: penyimpanan token
  terenkripsi dan pemutusan perangkat.
- `tests/Feature/SourceEncodingTest.php` — UTF-8 tanpa BOM.
- `tests/Feature/SyncButtonTest.php` — pesan sync dan pencegahan sync ganda.
- `tests/Feature/MasterStrategicDataJemberTest.php` — data master dan statistik.
- `tests/Unit/IndicatorTest.php` — Smart Trend Logic, format, UUID, relasi.

Verifikasi frontend:

```bash
npm run build
```

Build Vite berhasil dan utility safe-area ikut terkompilasi ke `public/build`.

---

## 8. Inventaris Perubahan

| File | Perubahan |
|------|-----------|
| `app/Providers/AppServiceProvider.php` | Pragma SQLite, pembuatan file DB, lewati inisialisasi saat package discovery |
| `app/Services/SyncService.php` | Protokol versi, pull/push, konflik, dedupe |
| `app/Services/GeminiInsightService.php` | Klien proxy AI, cache lokal, fallback lokal |
| `app/Services/DatabaseBootstrapper.php` | Migrasi sekali per versionCode, pembuatan database baru |
| `config/database.php` | Database lokal di `database/private` |
| `app/Livewire/SyncSettings.php` | Aktivasi perangkat & resolusi konflik |
| `app/Models/Concerns/HasSyncState.php` | Penanda `dirty`/`base_version` |
| `app/Models/AiInsight.php` | Model cache AI |
| `app/Models/DeviceCredential.php` | Token perangkat terenkripsi |
| `packages/np-analytics-core` | Model/enum/mapper/data master bersama |
| `config/gemini.php` | Path dan timeout proxy AI |
| `config/nativephp.php` | Status bar light, orientasi, eksklusi bundel dan kunci env |
| `database/migrations/2026_10_25_000001_create_ai_insights_table.php` | Tabel cache AI |
| `database/migrations/2026_10_25_100000_create_device_credentials_table.php` | Tabel token perangkat |
| `database/migrations/2026_10_25_120001_add_sync_protocol_to_mobile_tables.php` | Kolom versi/dirty/konflik |
| `resources/views/components/ai-insight-panel.blade.php` | Panel AI semantik |
| `resources/views/livewire/sync-settings.blade.php` | UI aktivasi & konflik |
| `nativephp/android/app/src/main/AndroidManifest.xml` | Predictive Back, multi-window, target API 36 |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt` | Status bar light, callback back, insets |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/LaravelEnvironment.kt` | OPcache, maintenance kondisional |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt` | Debug WebView kondisional, blokir dark mode algoritmik |
| `.env.example` | Path proxy AI dan timeout (tanpa API key) |
| `tests/Feature/SyncServiceTest.php` | Test protokol sinkronisasi |
| `tests/Feature/DatabaseBootstrapperTest.php` | Test migrasi saat upgrade |
| `tests/Feature/ReleaseBundleSafetyTest.php` | Test keamanan bundel APK dan bootstrap DB |

---

## 9. Catatan Pemeliharaan

1. **Backup keystore.** Kehilangan `android-keystore/np-analytics.keystore` berarti aplikasi tidak dapat diperbarui dengan tanda tangan yang sama.
2. **Naikkan versionCode setiap rilis.** NativePHP melakukannya otomatis saat packaging, tetapi verifikasi nilai pada APK tetap diperlukan.
3. **Perbarui dokumentasi bila mengubah perilaku Livewire.** Nama `resolvedAiInsight` adalah invariant yang mencegah cache tertimpa properti publik.
4. **Perubahan pada folder `nativephp/android`** dapat tertimpa saat upgrade NativePHP atau `native:install --force`. Folder `nativephp/` diabaikan Git, tetapi empat file patch (`AndroidManifest.xml`, `MainActivity.kt`, `LaravelEnvironment.kt`, `WebViewManager.kt`) di-commit secara selektif. Terapkan ulang patch setelah upgrade dan jangan commit `nativephp/android/gradle.properties` karena dapat memuat konfigurasi signing.
5. **Tidak ada API key di APK.** Analisis AI memakai proxy backend; kunci Gemini hanya ada di environment server. Variabel sensitif dibersihkan dari bundle melalui `cleanup_env_keys`.
6. **Uji di perangkat nyata.** Emulator tidak mereproduksi perilaku HyperOS (dark mode algoritmik, split-screen, gestur back).
