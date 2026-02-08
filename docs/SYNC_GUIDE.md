# Panduan Sync Data (Android <-> Server)

Panduan ini menjelaskan cara melakukan sinkronisasi dua arah antara aplikasi Android (offline) dan server.

---

## Konsep Sinkronisasi

- **Edit di Android (offline)**: perubahan disimpan ke SQLite lokal.
- **Sync ke server (online)**: perubahan dikirim via endpoint `push`.
- **Update di server**: perubahan diambil dari endpoint `pull`.
- **Conflict**: jika data di server lebih baru daripada data di HP, server akan menolak perubahan tersebut dan mengirim daftar conflict.

---

## Keamanan (API Key)

Tambahkan di `.env` server:

```
SYNC_API_KEY=isi_dengan_token_rahasia
```

Lalu kirim header berikut saat request:

```
X-SYNC-KEY: isi_dengan_token_rahasia
```

Jika `SYNC_API_KEY` kosong, endpoint akan tetap bisa diakses (mode development).

---

## Endpoint Sync

Set `SYNC_BASE_URL` di `.env` aplikasi mobile ke alamat backend, contoh:

```
SYNC_BASE_URL=https://api.domain-kamu.com
```

Base URL:

```
{SYNC_BASE_URL}/api/sync
```

### 1) Pull (ambil data terbaru)

```
GET /api/sync/pull?since=2026-02-08T00:00:00Z
```

Jika `since` tidak diisi, server akan mengirim semua data.

Response contoh (ringkas):

```json
{
  "server_time": "2026-02-08T09:10:00Z",
  "categories": [
    {
      "uuid": "...",
      "name": "Ekonomi",
      "icon": "lucide-trending-up",
      "updated_at": "...",
      "deleted_at": null
    }
  ],
  "indicators": [
    {
      "uuid": "...",
      "category_uuid": "...",
      "title": "PDRB",
      "value": "123.4500",
      "unit": "miliar",
      "year": 2024,
      "trend": "2.5000",
      "is_higher_better": true,
      "description": "...",
      "image_path": null,
      "updated_at": "...",
      "deleted_at": null
    }
  ],
  "phenomena": [
    {
      "uuid": "...",
      "indicator_uuid": "...",
      "title": "Gagal Panen",
      "description": "...",
      "impact": "negative",
      "source": "BPS 2024",
      "order": 1,
      "updated_at": "...",
      "deleted_at": null
    }
  ]
}
```

### 2) Push (kirim perubahan ke server)

```
POST /api/sync/push
```

Body contoh:

```json
{
  "categories": [
    {
      "uuid": "...",
      "name": "Ekonomi",
      "icon": "lucide-trending-up",
      "updated_at": "2026-02-08T07:00:00Z",
      "deleted_at": null
    }
  ],
  "indicators": [
    {
      "uuid": "...",
      "category_uuid": "...",
      "title": "PDRB",
      "value": 123.45,
      "unit": "miliar",
      "year": 2024,
      "trend": 2.5,
      "is_higher_better": true,
      "description": "...",
      "image_path": null,
      "updated_at": "2026-02-08T07:00:00Z",
      "deleted_at": null
    }
  ],
  "phenomena": [
    {
      "uuid": "...",
      "indicator_uuid": "...",
      "title": "Gagal Panen",
      "description": "...",
      "impact": "negative",
      "source": "BPS 2024",
      "order": 1,
      "updated_at": "2026-02-08T07:00:00Z",
      "deleted_at": null
    }
  ]
}
```

Response contoh:

```json
{
  "server_time": "2026-02-08T09:10:00Z",
  "applied": {
    "categories": 1,
    "indicators": 1,
    "phenomena": 1
  },
  "conflicts": [],
  "errors": []
}
```

---

## Catatan Penting

- UUID menjadi identitas utama data antar perangkat.
- `deleted_at` dikirim jika record dihapus, agar perangkat lain ikut menghapus.
- Setelah `push`, lakukan `pull` lagi agar timestamp lokal mengikuti server.

---

## Flow Sync Minimal (disarankan)

1. Ambil `server_time` terakhir dari `pull`.
2. Simpan semua perubahan lokal ke queue.
3. Saat online, kirim `push`.
4. Jika sukses, kosongkan queue.
5. Lakukan `pull` dengan `since=last_server_time`.
