# Dokumentasi Optimasi Android, Performa, dan Gemini AI

**Proyek:** NP Analytics — Aplikasi Statistik Daerah Offline-First
**Target perangkat:** POCO X7 (Android 14–17 / API 34–37, HyperOS, MediaTek Dimensity 7300-Ultra, RAM 8/12 GB, layar 120 Hz)
**Tanggal rilis APK terakhir:** 25 Oktober 2026
**Status:** Selesai — 23 test lulus, build APK release tertanda tangan dan terverifikasi

---

## 1. Ringkasan

Optimasi mencakup lima area:

| Area | Hasil |
|------|-------|
| Kompatibilitas Android 14–17 | Predictive Back, multi-window, target SDK 36, tanpa kunci orientasi |
| UI/UX HyperOS | Edge-to-edge, safe area, status bar terang, navigasi SPA |
| Performa | SQLite WAL + mmap + cache, sinkronisasi transaksional, query dashboard tunggal, OPcache |
| Gemini AI | Analisis indikator & dashboard, cache offline di SQLite, fallback lokal |
| Distribution | APK release signed `arm64-v8a`, versionCode 35, siap sideload |

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

## 5. Gemini AI

### 5.1 Konfigurasi

File: `config/gemini.php`

```php
return [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
    'endpoint' => env('GEMINI_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
    'timeout' => (int) env('GEMINI_TIMEOUT', 30),
    'connect_timeout' => (int) env('GEMINI_CONNECT_TIMEOUT', 5),
];
```

Variabel `.env`:

```env
GEMINI_API_KEY=
GEMINI_MODEL=gemini-2.5-flash
GEMINI_TIMEOUT=30
GEMINI_CONNECT_TIMEOUT=5
```

### 5.2 Alur Analisis

File: `app/Services/GeminiInsightService.php`

```
Aksi Livewire
    │
    ▼
Bangun konteks (indikator / dashboard)
    │
    ▼
Hash SHA-256 = prompt_version | scope | model | JSON konteks
    │
    ├── Cache ditemukan & tidak dipaksa refresh → tampilkan cache
    │
    ▼
Request Gemini (JSON schema, temperature 0.4)
    │
    ├── Sukses → simpan ke ai_insights → tampilkan sumber "Gemini"
    │
    └── Gagal / offline
            ├── Cache tersedia → tampilkan cache + notice
            └── Tidak ada cache → analisis lokal + notice
```

Konten analisis:

1. `ringkasan_eksekutif` — 3–5 kalimat
2. `analisis_akar_masalah` — 3–5 kalimat
3. `rekomendasi_kebijakan` — 3–5 butir (maksimum 6 disimpan)

Konteks indikator mencakup nilai, satuan, tren, `is_higher_better`, deskripsi, time-series, dan maksimal 10 fenomena lokal. Konteks dashboard mencakup statistik agregat, daftar kategori, dan 10 indikator dengan perubahan tren terbesar.

### 5.3 Cache Offline

Tabel: `ai_insights`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `hash` | string(64), unique | Kunci cache SHA-256 |
| `scope` | string(20) | `indicator` atau `dashboard` |
| `indicator_id` | FK nullable | `nullOnDelete` |
| `year` | integer nullable | Tahun data |
| `model` | string(80) | Model Gemini yang dipakai |
| `source` | string(20) | Default `gemini` |
| `payload` | json | Ringkasan, akar masalah, rekomendasi |
| `generated_at` | timestamp | Waktu analisis |

Karena hash mencakup seluruh konteks data, perubahan angka atau fenomena otomatis menghasilkan cache baru. Cache tetap dapat dibaca saat perangkat offline.

### 5.4 Komponen UI

File: `resources/views/components/ai-insight-panel.blade.php`

- Header "Ringkasan & Rekomendasi AI"
- Badge sumber: **Gemini**, **Tersimpan offline**, atau **Analisis lokal**
- Tombol: "Analisis Gemini", "Analisis Offline", atau "Perbarui Analisis"
- Status loading "Menganalisis..." dengan `wire:target`
- Notice kegagalan/ketersediaan cache
- Tiga bagian semantik: ringkasan eksekutif, akar masalah, rekomendasi

Digunakan di dashboard (`.resources/views/livewire/dashboard.blade.php`) dan detail indikator (`resources/views/livewire/indicator-detail.blade.php`).

### 5.5 Catatan Implementasi Livewire

Livewire menggabungkan properti publik ke data view **setelah** data dari `render()`. Properti publik `?array $aiInsight = null` akan menimpa hasil cache jika dikirim dengan nama kunci yang sama. Karena itu kedua komponen mengirim hasil resolusi dengan nama **`resolvedAiInsight`**:

```php
$resolvedAiInsight = $this->aiInsight ?? $gemini->cachedForDashboard(...);
```

Jangan mengganti nama kunci ini tanpa memahami perilaku tersebut.

### 5.6 Keamanan API Key

NativePHP membundel `.env` ke dalam APK. Artinya `GEMINI_API_KEY` dapat diekstrak dari perangkat. Untuk distribusi luas:

- Gunakan API key terbatas (quota kecil, khusus aplikasi ini).
- Pertimbangkan proxy backend agar key tidak berada di perangkat.
- Rotasi key jika APK dibagikan di luar lingkungan internal.

`cleanup_env_keys` di `config/nativephp.php` tidak menghapus `GEMINI_API_KEY`; tambahkan secara eksplisit bila analisis AI ingin dinonaktifkan pada build tertentu.

---

## 6. Build APK Release

### 6.1 Prasyarat

| Komponen | Nilai terverifikasi |
|----------|---------------------|
| PHP | 8.3.32 (Laragon) |
| Node.js | 24.18.0 |
| JDK | 17 (`C:\laragon\java17`) |
| Android SDK | `C:\Users\IPDS\AppData\Local\Android\Sdk` |
| Build-tools | 37.0.0 |
| compileSdk / targetSdk | 36 / 36 |
| minSdk | 33 |
| ABI | `arm64-v8a` |
| Keystore | `android-keystore/np-analytics.keystore` |

Konfigurasi signing berada di `nativephp/android/gradle.properties`:

```properties
MYAPP_UPLOAD_STORE_FILE=...
MYAPP_UPLOAD_KEY_ALIAS=npanalytics
MYAPP_UPLOAD_STORE_PASSWORD=...
MYAPP_UPLOAD_KEY_PASSWORD=...
```

Jangan menuliskan atau membagikan nilai password. File ini bersifat rahasia.

### 6.2 Langkah Build

```bash
# 1. Build aset frontend
npm run build

# 2. Jalankan seluruh test
php artisan test

# 3. Pastikan identitas aplikasi di .env
#    NATIVEPHP_APP_ID=com.nanang.npanalytics
#    NATIVEPHP_APP_VERSION=1.0.0
#    NATIVEPHP_APP_VERSION_CODE=<angka build berikutnya>

# 4. Package APK release bertanda tangan
php artisan native:package android --build-type=release --no-tty --no-interaction \
  --keystore="C:/laragon/www/nanangpx/NP-Analytics/android-keystore/np-analytics.keystore" \
  --keystore-password="<STORE_PASSWORD>" \
  --key-alias="npanalytics" \
  --key-password="<KEY_PASSWORD>" \
  --output=dist
```

Catatan:

- Parameter signing harus diberikan eksplisit pada perintah; command memvalidasinya terpisah dari `gradle.properties`.
- Proses memakan waktu beberapa menit karena Gradle melakukan kompilasi native.
- NativePHP menaikkan `NATIVEPHP_APP_VERSION_CODE` di `.env` **setelah** build sukses. Build 25 Oktober 2026 menghasilkan versionCode **35**, lalu `.env` menjadi **36** untuk rilis berikutnya.
- Jangan memakai `--skip-prepare` kecuali memang ingin mempertahankan kondisi project Android yang ada.

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

### 6.4 Artefak Rilis 25 Oktober 2026

| Item | Nilai |
|------|-------|
| Lokasi utama | `dist/app-release.apk` |
| Salinan Gradle | `nativephp/android/app/build/outputs/apk/release/app-release.apk` |
| Ukuran | 69.187.540 bytes (65,98 MB) |
| Package | `com.nanang.npanalytics` |
| versionName | `1.0.0` |
| versionCode | `35` |
| Target SDK | `36` |
| ABI | `arm64-v8a` |
| SHA-256 APK | `437ec9f13ac60eac36352dd7c071a4ef576aa9adb25ef415509e9661d9391efb` |
| Sertifikat | v2, `CN=NP Analytics`, SHA-256 `048d92a411348d103ed860e8570fb92dc696c5922bff98866b10add059497b14` |

Instalasi ke perangkat:

```bash
adb install -r "C:\laragon\www\nanangpx\NP-Analytics\dist\app-release.apk"
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
```

Hasil terakhir: **23 test lulus, 107 assertions**.

Cakupan penting:

- `tests/Feature/GeminiInsightTest.php` — 6 test: parsing & cache, tanpa API key, kegagalan HTTP lalu fallback cache, aksi dashboard, aksi detail indikator, panel tanpa API key.
- `tests/Feature/SyncServiceTest.php` — 2 test: pull penuh dalam satu transaksi, pull kedua memperbarui tanpa duplikasi.
- `tests/Feature/MasterStrategicDataJemberTest.php` — data strategis dan statistik dashboard.
- `tests/Unit/IndicatorTest.php` — Smart Trend Logic, format nilai, UUID, relasi.

Verifikasi frontend:

```bash
npm run build
```

Build Vite berhasil dan utility safe-area ikut terkompilasi ke `public/build`.

---

## 8. Inventaris Perubahan

| File | Perubahan |
|------|-----------|
| `app/Providers/AppServiceProvider.php` | Pragma SQLite, auto-migrate/seed non-console |
| `app/Services/SyncService.php` | Pull transaksional + peta UUID |
| `app/Services/GeminiInsightService.php` | Analisis Gemini, cache, fallback lokal |
| `app/Models/AiInsight.php` | Model cache AI |
| `app/Livewire/Dashboard.php` | Statistik dari relasi, aksi AI, `resolvedAiInsight` |
| `app/Livewire/IndicatorDetail.php` | Histori time-series, aksi AI, `resolvedAiInsight` |
| `config/gemini.php` | Konfigurasi Gemini |
| `config/nativephp.php` | Status bar light, orientasi Android penuh |
| `database/migrations/2026_10_25_000001_create_ai_insights_table.php` | Tabel cache AI |
| `resources/views/components/ai-insight-panel.blade.php` | Panel AI semantik |
| `resources/views/layouts/app.blade.php` | Meta color-scheme, safe area, `wire:navigate` |
| `resources/views/livewire/dashboard.blade.php` | Integrasi panel AI |
| `resources/views/livewire/indicator-detail.blade.php` | Integrasi panel AI |
| `resources/css/app.css` | Utility safe area, progress bar, `color-scheme` |
| `nativephp/android/app/src/main/AndroidManifest.xml` | Predictive Back, multi-window, target API 36 |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt` | Status bar light, callback back, insets |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/bridge/LaravelEnvironment.kt` | OPcache, maintenance kondisional |
| `nativephp/android/app/src/main/java/com/nativephp/mobile/network/WebViewManager.kt` | Debug WebView kondisional, blokir dark mode algoritmik |
| `.env.example` | Variabel Gemini |
| `tests/Feature/GeminiInsightTest.php` | Test integrasi Gemini |
| `tests/Feature/SyncServiceTest.php` | Test sinkronisasi |

---

## 9. Catatan Pemeliharaan

1. **Backup keystore.** Kehilangan `android-keystore/np-analytics.keystore` berarti aplikasi tidak dapat diperbarui dengan tanda tangan yang sama.
2. **Naikkan versionCode setiap rilis.** NativePHP melakukannya otomatis saat packaging, tetapi verifikasi nilai pada APK tetap diperlukan.
3. **Perbarui dokumentasi bila mengubah perilaku Livewire.** Nama `resolvedAiInsight` adalah invariant yang mencegah cache tertimpa properti publik.
4. **Perubahan pada folder `nativephp/android`** dapat tertimpa saat upgrade NativePHP atau `native:install --force`. Folder `nativephp/` diabaikan Git, tetapi empat file patch (`AndroidManifest.xml`, `MainActivity.kt`, `LaravelEnvironment.kt`, `WebViewManager.kt`) di-commit secara selektif. Terapkan ulang patch setelah upgrade dan jangan commit `nativephp/android/gradle.properties` karena memuat password signing.
5. **API key Gemini** berada di dalam APK. Gunakan key terbatas atau proxy backend untuk distribusi eksternal.
6. **Uji di perangkat nyata.** Emulator tidak mereproduksi perilaku HyperOS (dark mode algoritmik, split-screen, gestur back).
