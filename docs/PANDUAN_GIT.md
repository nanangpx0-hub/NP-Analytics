# Panduan Git Manual

Project ini **tidak** memakai script auto-commit/auto-push. Semua perubahan di-commit dan di-push secara manual agar setiap commit dapat ditinjau dan tidak ada rahasia yang ikut terunggah.

## Sebelum Commit

```bash
git status
git diff
```

Periksa daftar file. **Jangan** commit file berikut (sudah masuk `.gitignore`, tetapi tetap periksa):

- `.env`, `.env.*` (kecuali `.env.example`)
- `database/private/`, `database/*.sqlite`
- `android-keystore/`, `keys/`, `credentials/`, `*.jks`, `*.keystore`, `*.p12`
- `dist/`, `build-output/`, `*.apk`, `*.aab`
- `changes.tmp`, `.phpunit.result.cache`

Jika sebuah rahasia telanjur ter-commit, hapus dari riwayat (misalnya dengan `git filter-repo`), rotasi rahasia tersebut, lalu force-push hanya setelah koordinasi dengan pemilik repo.

## Alur Manual

1. Tambahkan file secara spesifik (hindari `git add .` agar file tak terduga tidak ikut):
   ```bash
   git add path/ke/file.php path/ke/file-lain.blade.php
   ```
2. Tinjau staging:
   ```bash
   git diff --staged
   ```
3. Commit dengan pesan yang menjelaskan alasan perubahan:
   ```bash
   git commit -m "fix(sync): pulihkan token perangkat setelah aktivasi ulang"
   ```
4. Tarik update terbaru sebelum push:
   ```bash
   git pull --rebase origin main
   ```
5. Push:
   ```bash
   git push origin main
   ```

## Build APK dan Rahasia

- Password keystore **tidak boleh** ditulis di script atau file yang ter-track. `gen_keystore.bat` membaca `KEYSTORE_PASSWORD` dari environment atau prompt interaktif.
- Keystore dan APK tidak pernah masuk Git. Simpan salinan keystore di tempat aman di luar repo.
- Keystore lama yang password-nya pernah tertulis plaintext dianggap bocor dan **wajib dirotasi** (buat keystore baru, perbarui signing config, lalu distribusikan APK dengan signing baru).
