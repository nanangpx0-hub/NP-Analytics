# Panduan Setup NP Analytics di Komputer Baru

Panduan ini menjelaskan langkah-langkah lengkap untuk melanjutkan pengerjaan project NP Analytics di komputer atau laptop lain.

---

## Prasyarat (Software yang Harus Diinstall)

Sebelum memulai, pastikan software berikut sudah terinstall di komputer baru:

| Software | Fungsi | Link Download |
|----------|--------|---------------|
| **Laragon** | Server lokal (PHP, MySQL, Apache) | [laragon.org](https://laragon.org/download/) |
| **Git** | Version control untuk sync kode | [git-scm.com](https://git-scm.com/downloads) |
| **Node.js** | Menjalankan frontend build tools | [nodejs.org](https://nodejs.org/) (pilih versi LTS) |
| **Composer** | Package manager untuk PHP | Sudah termasuk di Laragon |

> **💡 Tips:** Laragon sudah menyertakan PHP, MySQL, dan Composer. Anda hanya perlu install Git dan Node.js secara terpisah.

---

## Langkah 1: Clone Repository dari GitHub

1. Buka **Laragon** dan klik tombol **Terminal**.
2. Masuk ke folder `www` (biasanya `C:\laragon\www`):
   ```bash
   cd C:\laragon\www
   ```
3. Clone repository:
   ```bash
   git clone https://github.com/nanangpx0-hub/NP-Analytics.git
   ```
4. Masuk ke folder project:
   ```bash
   cd NP-Analytics
   ```

---

## Langkah 2: Install Dependencies

Jalankan perintah berikut satu per satu:

### Install library PHP (Composer)
```bash
composer install
```
*Proses ini akan membuat folder `vendor/` dan menginstall semua library PHP yang dibutuhkan.*

### Install library JavaScript (NPM)
```bash
npm install
```
*Proses ini akan membuat folder `node_modules/` dan menginstall semua library frontend.*

---

## Langkah 3: Konfigurasi Environment (.env)

File `.env` berisi konfigurasi rahasia yang tidak di-upload ke GitHub. Anda perlu membuatnya secara manual.

1. **Copy file template:**
   ```bash
   copy .env.example .env
   ```
   
2. **Generate Application Key:**
   ```bash
   php artisan key:generate
   ```

3. **Edit file `.env`** (opsional, jika diperlukan):
   Buka file `.env` dengan text editor dan sesuaikan konfigurasi berikut jika diperlukan:
   
   ```env
   # Nama aplikasi
   APP_NAME="NP Analytics"
   
   # URL aplikasi (sesuaikan dengan domain Laragon Anda)
   APP_URL=http://np-analytics.test
   
   # Path PHP (sesuaikan dengan lokasi PHP di komputer Anda)
   PHP_BINARY=C:\laragon\bin\php\php-8.x.x\php.exe
   
   # Database (default menggunakan SQLite, tidak perlu diubah)
   DB_CONNECTION=sqlite
   ```

> **⚠️ Penting:** Pastikan path `PHP_BINARY` sesuai dengan lokasi PHP di komputer Anda. Cek versi PHP yang terinstall di folder `C:\laragon\bin\php\`.

---

## Langkah 4: Setup Database

Project ini menggunakan **SQLite** sehingga tidak perlu membuat database manual.

1. **Buat file database SQLite:**
   ```bash
   copy NUL database\database.sqlite
   ```
   
2. **Jalankan migrasi untuk membuat tabel-tabel:**
   ```bash
   php artisan migrate
   ```

3. **Isi data awal (seeder):**
   ```bash
   php artisan db:seed
   ```

   Atau jalankan keduanya sekaligus:
   ```bash
   php artisan migrate --seed
   ```

---

## Langkah 5: Build Assets Frontend

Compile file CSS dan JavaScript:

```bash
npm run build
```

Atau jika ingin mode development (auto-refresh saat ada perubahan):
```bash
npm run dev
```

---

## Langkah 6: Jalankan Aplikasi

### Opsi A: Menggunakan Laragon (Rekomendasi)
1. Pastikan Laragon sudah **Start All**.
2. Klik kanan pada icon Laragon di system tray → **www** → **NP-Analytics**.
3. Browser akan terbuka dengan aplikasi.

### Opsi B: Menggunakan PHP Artisan
```bash
php artisan serve
```
Kemudian buka browser dan akses: `http://localhost:8000`

---

## Langkah 7: Sinkronisasi dengan GitHub

### Mengambil Update Terbaru (Pull)
Sebelum mulai bekerja, selalu ambil update terbaru:
```bash
git pull
```

### Mengirim Perubahan (Push)
Setelah selesai bekerja, kirim perubahan Anda:
```bash
git add .
git commit -m "Deskripsi perubahan Anda"
git push
```

Atau gunakan script otomatis yang sudah disediakan:
```bash
.\update-github.bat
```

---

## Troubleshooting (Solusi Masalah Umum)

### ❌ Error: "php artisan" tidak dikenali
**Solusi:** Pastikan PHP sudah ditambahkan ke PATH sistem atau jalankan melalui Terminal Laragon.

### ❌ Error: "pdo_sqlite driver not found"
**Solusi:** Aktifkan extension SQLite di `php.ini`:
1. Buka file `php.ini` (biasanya di `C:\laragon\bin\php\php-x.x.x\php.ini`)
2. Cari baris `;extension=pdo_sqlite` dan hapus tanda semicolon (`;`) di depannya
3. Restart Laragon

### ❌ Error: "npm command not found"
**Solusi:** Install Node.js dari [nodejs.org](https://nodejs.org/) dan restart terminal.

### ❌ Error saat `composer install`
**Solusi:** Pastikan versi PHP minimal 8.1. Cek dengan perintah:
```bash
php -v
```

### ❌ Halaman blank atau error 500
**Solusi:** 
1. Pastikan file `.env` sudah dibuat
2. Jalankan `php artisan key:generate`
3. Jalankan `php artisan config:clear`

---

## Checklist Setup Cepat

Berikut ringkasan langkah-langkah dalam bentuk checklist:

- [ ] Install Laragon, Git, dan Node.js
- [ ] Clone repository: `git clone https://github.com/nanangpx0-hub/NP-Analytics.git`
- [ ] `cd NP-Analytics`
- [ ] `composer install`
- [ ] `npm install`
- [ ] `copy .env.example .env`
- [ ] `php artisan key:generate`
- [ ] `php artisan migrate --seed`
- [ ] `npm run build`
- [ ] Test aplikasi di browser

---

## Kontak & Bantuan

Jika mengalami kesulitan, silakan:
1. Baca pesan error dengan teliti
2. Cek bagian Troubleshooting di atas
3. Tanyakan ke AI Assistant (Gemini/Claude)

---

*Dokumen ini dibuat pada: 8 Februari 2026*
