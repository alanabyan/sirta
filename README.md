# SIRTA — Sistem Informasi RT Griya Kreasi Aqilla

- `backend/` — Laravel 13 (REST API, Sanctum token auth, MySQL)
- `frontend/` — Vue 3 + Vite + Pinia + Vue Router

## Menjalankan

```bash
# 1. Backend (database MySQL "sirta" sudah dibuat; atur di backend/.env bila perlu)
cd backend
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve --port=8000

# 2. Frontend (terminal lain)
cd frontend
npm install
npm run dev        # http://localhost:5173  (request /api diteruskan ke :8000)
```

Akun contoh (kata sandi semua: `password`): `admin`, `ketua.rt`, `sekretaris`, `bendahara`, `operator`.

Halaman publik untuk warga: `/lacak` (lacak permohonan tanpa login).

## Hak akses

| Peran | Lihat | Tambah/ubah | Verifikasi, hapus, pengurus & template | Kelola pengguna |
|---|---|---|---|---|
| Administrator | ✓ | ✓ | ✓ | ✓ |
| Ketua RT / Sekretaris | ✓ | ✓ | ✓ | – |
| Operator | ✓ | ✓ | – | – |
| Bendahara | ✓ | – | – | – |

Produksi: `npm run build` lalu sajikan `frontend/dist`, dan teruskan `/api` & `/storage` ke Laravel.

## Deploy

Panduan lengkap pemasangan ke Hostinger (susunan folder, `.htaccess`, `.env`, perintah `sirta:admin` & `sirta:periksa`, cadangan, dan pemecahan masalah): lihat [DEPLOY.md](DEPLOY.md). Berkas konfigurasi server ada di folder [`deploy/`](deploy/) dan `backend/.env.production.example`.
