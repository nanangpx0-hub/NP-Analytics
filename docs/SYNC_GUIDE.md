# Panduan Sinkronisasi (Android ↔ Server)

Dokumen ini menjelaskan protokol sinkronisasi NP Analytics setelah migrasi ke
token perangkat, versi global, dan resolusi konflik. Aplikasi mobile tetap
offline-first: seluruh perubahan disimpan di SQLite dan dikirim saat online.

---

## 1. Arsitektur

- **Mobile** (`NP-Analytics`): Laravel 11 + Livewire 3 + NativePHP Mobile 3,
  database SQLite. Tidak memiliki API key Gemini maupun BPS.
- **Backend** (`np-analytics-backend`): admin panel + API sinkronisasi.
  Database MySQL (produksi) atau SQLite (test).
- **Paket bersama** `packages/np-analytics-core`: model, enum, mapper payload,
  dan data master dipakai kedua aplikasi melalui Composer path repository.
  Paket di-vendor (`"symlink": false`) agar `native:package android` tetap
  membawa seluruh kode.

Satu siklus sync mobile:

1. **Bootstrap** (hanya saat perangkat belum punya `last_server_version`):
   tarik seluruh data server sebelum push apa pun.
2. **Push** perubahan lokal yang bertanda `dirty` (maksimal 200 record/batch).
3. **Pull** perubahan server sejak `last_server_version` (halaman 500 record).

---

## 2. Aktivasi perangkat

Tidak ada shared API key. Setiap perangkat mendapat token Sanctum sendiri.

1. Admin membuka **Perangkat** di panel backend dan membuat kode aktivasi
   sekali pakai (berlaku 1–720 jam).
2. Di aplikasi, buka **Pengaturan Sync**, isi URL server, kode aktivasi, dan
   nama perangkat.
3. Aplikasi memanggil `POST /api/devices/register`.
4. Server mengembalikan token dengan ability `sync:pull`, `sync:push`, dan
   `ai:insight`. Token disimpan terenkripsi di tabel lokal
   `device_credentials`, bukan di `.env`.
5. Token dapat dicabut kapan saja dari panel; request berikutnya menerima
   `401 unauthorized`.

### `POST /api/devices/register`

```http
POST /api/devices/register
Content-Type: application/json

{
  "activation_code": "ABCD-1234",
  "device_uuid": "0f8fad5b-d9cb-469f-a165-70867728950e",
  "device_name": "POCO X7",
  "platform": "android"
}
```

Respons `201`:

```json
{
  "token": "1|xxxxxxxxxxxxxxxx",
  "abilities": ["sync:pull", "sync:push", "ai:insight"],
  "device": { "uuid": "...", "name": "POCO X7" },
  "server_time": "2026-10-25T10:00:00+00:00"
}
```

Kode salah/kedaluwarsa → `422 invalid_activation_code`. Rate limit registrasi
default 5/menit (`SYNC_REGISTER_RATE_LIMIT`).

Semua endpoint lain memakai header:

```http
Authorization: Bearer <token>
Accept: application/json
```

`X-SYNC-KEY` lama hanya diterima bila `LEGACY_SYNC_KEY_ENABLED=true`; default
mati. Key melalui query string **tidak pernah** diterima.

---

## 3. Model data dan versi

Setiap record sinkronisasi memiliki identitas dan status berikut.

| Field | Arti |
|-------|------|
| `uuid` | Identitas lintas perangkat. Dibuat deterministik (UUID v5) dari natural key saat record baru dibuat. |
| `server_version` | Versi global monoton naik yang diberikan server setiap kali record berubah. |
| `base_version` | Versi server yang menjadi dasar perubahan lokal. Dikirim saat push. |
| `dirty` | `1` bila record lokal belum dikirim/disinkronkan. Hanya record dirty yang di-push. |
| `deleted_at` | Tombstone. Record terhapus tetap dikirim agar perangkat lain ikut menghapus. |

Natural key:

- Kategori: `name`
- Indikator: `series_key` + `year` (`series_key` adalah slug stabil, bukan judul)
- Fenomena: `indicator` + `order`

Tidak ada pembandingan timestamp klien. Konflik ditentukan murni dari
`base_version` versus `server_version`, sehingga jam perangkat tidak berpengaruh.

Perangkat yang sudah ter-seed tidak menghasilkan duplikat karena:

- UUID deterministik menghasilkan UUID yang sama di server dan perangkat.
- Seeder melakukan upsert berdasarkan natural key.
- Bootstrap selalu **pull penuh lebih dulu**, baru push record dirty.
- `php artisan sync:dedupe` (tersedia di kedua aplikasi) menggabungkan data
  lama yang terlanjur duplikat dan memberi ulang UUID yang benar.

---

## 4. Pull

```http
GET /api/sync/pull?since_version=120&cursor=0
Authorization: Bearer <token>
```

- `since_version` opsional (default `0`). Hanya record dengan
  `server_version > since_version` yang dikirim.
- `cursor` opsional. Diisi `next_cursor` dari respons sebelumnya.

Respons:

```json
{
  "server_time": "2026-10-25T10:05:00+00:00",
  "max_version": 128,
  "next_cursor": 128,
  "records": [
    {
      "type": "indicator",
      "data": {
        "uuid": "...",
        "category_uuid": "...",
        "series_key": "pdrb-adhb",
        "title": "PDRB ADHB",
        "value": "123.4500",
        "unit": "miliar",
        "year": 2025,
        "trend": "2.5000",
        "is_higher_better": true,
        "server_version": 127,
        "updated_at": "2026-10-25T10:04:00+00:00",
        "deleted_at": null
      }
    }
  ]
}
```

Aturan klien:

- Maksimal 500 record per halaman. Ulangi request dengan `cursor=next_cursor`
  selama `next_cursor` tidak `null`.
- `max_version` disimpan sebagai `last_server_version` setelah seluruh halaman
  selesai diproses.
- Record lokal yang masih `dirty` **tidak boleh** ditimpa hasil pull; record
  tersebut akan dikirim pada push berikutnya sehingga server dapat menilai
  konflik.
- Field yang tidak dikirim (`null` pada payload) tidak mengubah kolom lokal;
  hanya field yang ada (`array_key_exists`) yang diterapkan. Ini memungkinkan
  pengosongan nilai secara eksplisit.

---

## 5. Push

```http
POST /api/sync/push
Authorization: Bearer <token>
Content-Type: application/json

{
  "idempotency_key": "np-3f2c9a...",
  "categories": [],
  "indicators": [
    {
      "uuid": "...",
      "base_version": 127,
      "category_uuid": "...",
      "series_key": "pdrb-adhb",
      "title": "PDRB ADHB",
      "value": 123.45,
      "unit": "miliar",
      "year": 2025,
      "trend": 2.5,
      "is_higher_better": true,
      "deleted_at": null
    }
  ],
  "phenomena": []
}
```

- `idempotency_key` wajib (8–100 karakter). Request dengan key yang sama
  mengembalikan respons tersimpan (`"replayed": true`) tanpa menerapkan ulang.
- Maksimal 200 record per batch (gabungan semua tipe).
- Record yang tidak dihapus wajib memiliki field domain lengkap; validasi
  mengembalikan `422` dengan detail field.
- `base_version` harus sama dengan `server_version` di server. Jika tidak,
  server mengembalikan status `conflict` beserta snapshot server dan **tidak
  menimpa** data.

Respons:

```json
{
  "server_time": "2026-10-25T10:06:00+00:00",
  "max_version": 130,
  "replayed": false,
  "results": [
    {
      "type": "indicator",
      "uuid": "...",
      "status": "applied",
      "server_version": 129,
      "server": { }
    },
    {
      "type": "category",
      "uuid": "...",
      "status": "conflict",
      "server_version": 128,
      "server": { }
    },
    {
      "type": "phenomenon",
      "uuid": "...",
      "status": "error",
      "code": "invalid_data",
      "message": "Record gagal diproses di server."
    }
  ]
}
```

Status yang mungkin: `applied`, `conflict`, `error`. Angka `max_version` dipakai
klien untuk memperbarui penanda versi bila perlu.

Urutan pemrosesan server: restore → upsert → delete (anak sebelum induk saat
delete) agar cascade soft-delete tidak membatalkan record lain dalam batch.
Restore anak dilakukan tanpa cascade; cascade hanya dipakai dari admin panel.

---

## 6. Konflik

Konflik muncul bila `base_version` tidak cocok dengan versi server (misalnya
record yang sama diubah dari perangkat lain atau admin).

1. Server menyimpan `local_payload` dan `server_payload` pada tabel
   `sync_conflicts` di perangkat.
2. Pengguna memilih **Pakai versi server** atau **Pertahankan versi lokal**
   pada layar Pengaturan Sync.
3. Pilihan server menerapkan snapshot server secara paksa.
4. Pilihan lokal menandai record `dirty` dengan `base_version` = versi server
   terbaru, lalu dikirim pada sync berikutnya.
5. Record konflik dihapus setelah resolusi.

---

## 7. AI Insight (opsional)

Mobile tidak pernah memanggil Gemini langsung. Endpoint backend:

```http
POST /api/ai/insight
Authorization: Bearer <token>   (ability ai:insight)

{
  "scope": "indicator",
  "context": { "indikator": { "judul": "PDRB ADHB", "...": "..." } }
}
```

Respons berisi `summary`, `root_cause`, `recommendations`, `source`, `model`,
`generated_at`. Server menyimpan cache berdasarkan hash konteks dan memberi
rate limit terpisah (`AI_RATE_LIMIT`). Bila server tidak dikonfigurasi
(`503 ai_not_configured`) atau offline, aplikasi memakai analisis lokal dan
cache `ai_insights` di perangkat.

---

## 8. Migrasi saat aplikasi diperbarui

Saat `NATIVEPHP_APP_VERSION_CODE` berubah, aplikasi menjalankan
`php artisan migrate --force` sekali sebelum UI memakai database. Status versi
migrasi disimpan di `sync_states`, sehingga pembaruan APK tidak menghapus data
lokal dan tidak menjalankan migrasi berulang.

---

## 9. Operasional dan troubleshooting

| Gejala | Penyebab umum | Tindakan |
|--------|---------------|----------|
| `401 unauthorized` | Token dicabut/kedaluwarsa | Buat kode aktivasi baru, aktifkan ulang perangkat |
| `422` saat sync | Kode aktivasi salah/kedaluwarsa atau payload tidak valid | Periksa pesan validasi; buat kode baru bila perlu |
| `429` | Rate limit sync/AI | Tunggu satu menit; jangan menekan sync berulang |
| Data ganda | Data lama sebelum dedupe | Jalankan `php artisan sync:dedupe` di server dan/atau perangkat |
| Konflik berulang | Perangkat lain terus mengubah record yang sama | Selesaikan konflik di Pengaturan Sync, lalu sync ulang |
| Offline | Tidak ada koneksi | Indikator offline tampil; perubahan tetap tersimpan |

Perintah bermanfaat:

```bash
# Backend
php artisan sync:dedupe
php artisan migrate --force
php artisan test

# Mobile (melalui tinker/CLI lokal)
php artisan sync:dedupe
php artisan migrate --force
```

---

## 10. Batas dan konfigurasi

| Konfigurasi | Default | Keterangan |
|-------------|---------|------------|
| `SYNC_RATE_LIMIT` | 120/menit | Pull/push per perangkat |
| `AI_RATE_LIMIT` | 30/menit | Endpoint AI per perangkat |
| `SYNC_REGISTER_RATE_LIMIT` | 5/menit | Registrasi perangkat |
| `LEGACY_SYNC_KEY_ENABLED` | `false` | Hanya untuk masa transisi |
| Pull page | 500 record | Tetap di server |
| Push batch | 200 record | Divalidasi server |

Lihat `README.md` untuk setup aplikasi dan `docs/PANDUAN_GIT.md` untuk alur
rilis/commit manual.
