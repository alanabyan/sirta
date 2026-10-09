# Panduan Deploy SIRTA ke Hostinger

## Susunan di server

```
/home/u903592286/                       ← folder rumah akun (tempat Anda masuk lewat PuTTY)
├── sirta/                              ← salinan repo GitHub (git) — DI LUAR public_html
│   ├── backend/                        ← Laravel  (+ .env, vendor/, storage/ yang tidak ikut git)
│   ├── frontend/  deploy/  …
├── sirta-build/                        ← hasil build frontend dari GitHub Actions (sementara)
└── domains/sirta.org/public_html/      ← HANYA berkas publik
    ├── index.html  assets/  favicon.svg  robots.txt
    ├── core-api/index.php              ← pintu masuk kecil → ~/sirta/backend
    └── .htaccess                       ← /api/… → core-api, sisanya → aplikasi Vue
```

**Mengapa begini:** kode, `.env` (sandi database & APP_KEY), `vendor`, dan berkas unggahan warga (KK/KTP, arsip,
tanda tangan) tidak pernah berada di folder publik. Walaupun `.htaccess` rusak atau terhapus, berkas-berkas itu
tetap tidak dapat diunduh dari internet.

**Alur otomatis setiap `git push` ke `main`** (`.github/workflows/deploy.yml`):

1. GitHub membangun frontend → mengunggahnya ke `~/sirta-build` (belum tampil ke pengunjung).
2. Di server: `git reset --hard` di `~/sirta` → `composer install` → mode pemeliharaan → `migrate` → nyala lagi.
3. Baru setelah backend berhasil: aset, `core-api/index.php`, dan `.htaccess` dipasang; `index.html` paling akhir.
4. `php artisan sirta:periksa` mencetak hasil pemeriksaan kesiapan di log Actions.

Bila langkah 2 gagal, deploy berhenti, situs dinyalakan kembali, dan frontend lama **tidak** ditimpa.
Deploy juga bisa dijalankan manual: GitHub → **Actions** → *Deploy ke Hostinger* → **Run workflow**.

> Workflow memakai secret yang sudah Anda buat: `HOSTINGER_IP`, `HOSTINGER_USERNAME`, `HOSTINGER_SSH_KEY`, `HOSTINGER_PORT`.

---

## 1. Hal yang perlu dicek dulu

- **Alamat situs.** Saat saya periksa, `sirta.org` mengarah ke IP `217.160.0.83`, yang bukan server Hostinger.
  Pastikan domain memang milik Anda dan DNS-nya sudah diarahkan ke Hostinger (hPanel → Domains), atau pakai domain
  sementara dari Hostinger. Nama folder `domains/sirta.org` di workflow harus sama dengan nama folder di File Manager.
- **PHP 8.3 untuk terminal:** `/usr/bin/php -v`. Bila bukan 8.3, cari yang benar (`ls /opt/alt/php8*/usr/bin/php`)
  lalu ubah baris `PHP=/usr/bin/php` di `deploy.yml`.
- **PHP 8.3 untuk situs** — hPanel → Advanced → PHP Configuration. Naikkan `upload_max_filesize` ke **10M** dan
  `post_max_size` ke **40M**.

---

## 2. Urutan pemindahan

1. **Push perubahan ini ke GitHub.** Deploy pertama akan *sengaja berhenti* dengan pesan
   "`~/sirta belum disiapkan`" — situs lama tidak disentuh sama sekali.
2. Kerjakan **bagian 3** lewat PuTTY.
3. Jalankan ulang deploy: GitHub → Actions → *Deploy ke Hostinger* → **Run workflow**.
4. Uji (**bagian 4**), lalu hapus folder lama (**bagian 5**).

---

## 3. Pemasangan pertama (sekali, lewat PuTTY)

### 3.1 Cadangkan dulu

```bash
cd ~/domains/sirta.org/public_html
cp .htaccess ~/htaccess-lama.bak
tar -czf ~/cadangan-sirta-$(date +%F).tar.gz backend core-api storage .htaccess
ls -lh ~/cadangan-sirta-*.tar.gz          # pastikan berkasnya ada & tidak kosong
```

Ekspor juga database: hPanel → Databases → **phpMyAdmin** → pilih database → **Export** → Go.

> Buka `~/htaccess-lama.bak` (`cat ~/htaccess-lama.bak`). Bila berisi baris buatan Hostinger seperti `AddHandler`,
> `php_value`, atau aturan lain yang bukan milik SIRTA, kabari saya — `.htaccess` kini ditimpa otomatis setiap deploy,
> jadi baris seperti itu perlu dipindahkan ke `deploy/public_html.htaccess` di repo.

### 3.2 Izinkan server mengambil kode dari GitHub

*Lewati langkah ini bila repo GitHub Anda publik.* Untuk repo privat, buat **deploy key** (kunci baca-saja khusus repo ini):

```bash
ssh-keygen -t ed25519 -C "hostinger-sirta" -f ~/.ssh/github_sirta -N ""
cat ~/.ssh/github_sirta.pub
```

Salin baris yang muncul → GitHub → repo **sirta** → **Settings → Deploy keys → Add deploy key** →
tempel, beri nama "Hostinger", **jangan** centang *Allow write access* → Add key.

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
  HostName ssh.github.com
  Port 443
  User git
  IdentityFile ~/.ssh/github_sirta
  IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
ssh -T git@github.com        # ketik yes bila ditanya; harus muncul "successfully authenticated"
```

(Port 443 dipakai karena sebagian hosting bersama memblokir koneksi keluar ke port 22.)

### 3.3 Ambil kode ke luar public_html

```bash
cd ~
git clone git@github.com:alanabyan/sirta.git sirta
#   repo publik: git clone https://github.com/alanabyan/sirta.git sirta
ls ~/sirta/backend/artisan     # harus ada
```

### 3.4 Pindahkan konfigurasi & berkas unggahan dari susunan lama

```bash
cp ~/domains/sirta.org/public_html/backend/.env ~/sirta/backend/.env
cp -R ~/domains/sirta.org/public_html/backend/storage/app/. ~/sirta/backend/storage/app/
```

Lalu periksa isi `.env` (`nano ~/sirta/backend/.env`; simpan: Ctrl+O, Enter; keluar: Ctrl+X). Contoh lengkapnya ada di
`backend/.env.production.example`. Yang wajib:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alamat-situs-anda
DB_DATABASE=…   DB_USERNAME=…   DB_PASSWORD=…
```

### 3.5 Pasang

```bash
cd ~/sirta/backend
composer install --no-dev --optimize-autoloader --no-interaction
chmod -R 775 storage bootstrap/cache
/usr/bin/php artisan migrate --force
/usr/bin/php artisan db:seed --class=ProduksiSeeder --force     # template surat & identitas RT; aman diulang
/usr/bin/php artisan sirta:admin                                 # bila belum punya akun Administrator
/usr/bin/php artisan sirta:periksa
```

`sirta:periksa` harus berakhir **"Siap dipakai"** (peringatan HTTPS boleh sampai SSL aktif).
Lupa sandi Administrator: `/usr/bin/php artisan sirta:admin --username=NAMA --reset`.

### 3.6 Jalankan deploy

GitHub → **Actions** → *Deploy ke Hostinger* → **Run workflow**. Langkah ini memasang `core-api/index.php` dan
`.htaccess` yang baru. Lognya harus berakhir dengan **✅ Deploy selesai**.

---

## 4. Uji setelah deploy

Ganti `ALAMAT` dengan alamat situs Anda:

```bash
curl -s -o /dev/null -w "beranda        %{http_code}  (200)\n" https://ALAMAT/
curl -s -o /dev/null -w "halaman Vue    %{http_code}  (200)\n" https://ALAMAT/warga
curl -s -o /dev/null -w "API            %{http_code}  (401 = benar, belum login)\n" -H "Accept: application/json" https://ALAMAT/api/dashboard
curl -s -o /dev/null -w "core-api       %{http_code}  (403)\n" https://ALAMAT/core-api/index.php
curl -s -o /dev/null -w ".env lama      %{http_code}  (403 atau 404 — JANGAN 200)\n" https://ALAMAT/backend/.env
```

Lalu di browser: masuk sebagai Administrator, buka **Identitas & Tanda Tangan**, isi nama Ketua RT, unggah tanda
tangan & stempel, buat satu surat percobaan, cetak, dan pindai kode QR-nya dengan HP.

---

## 5. Bersihkan susunan lama (setelah bagian 4 berhasil)

Folder `public_html/backend` masih berisi salinan lama `.env` dan kode. Hapus setelah yakin semuanya berjalan
dan cadangan dari 3.1 sudah ada:

```bash
ls -lh ~/cadangan-sirta-*.tar.gz                          # cadangan masih ada?
rm -rf ~/domains/sirta.org/public_html/backend
rm -rf ~/domains/sirta.org/public_html/storage            # tidak dipakai lagi (berkas kini privat di ~/sirta/backend/storage)
```

---

## 6. Rutin

- **Rilis:** cukup `git push` ke `main`. Pantau di tab **Actions**.
- **Cadangan** (sebelum rilis yang membawa migrasi, dan berkala):
  - database: phpMyAdmin → Export;
  - berkas unggahan: `tar -czf ~/berkas-$(date +%F).tar.gz -C ~/sirta/backend/storage app/private`
    lalu unduh lewat File Manager. Berkas ini berisi data pribadi warga — simpan di tempat aman, jangan dibagikan ke grup.
- **Halaman tampak lama setelah rilis:** Ctrl+F5 (`index.html` sengaja tidak di-cache).

---

## 7. Apa yang sudah diuji

- `.htaccess` + `core-api/index.php` dengan Apache 2.4 + PHP 8.3 pada susunan folder yang sama
  (Laravel di luar `public_html`): **22 pemeriksaan lolos** — halaman Vue saat di-refresh, login, header
  `Authorization`, laporan, 404 JSON, serta `/core-api/`, `/.env`, `/.htaccess`, `/.git` ditolak.
- Skrip deploy dari `deploy.yml` dijalankan apa adanya pada server tiruan (repo asal, folder rumah, database
  terpisah): deploy pertama & kedua berhasil; bila migrasi gagal → deploy berhenti, situs nyala lagi, frontend lama
  utuh; bila `~/sirta` belum ada → berhenti dengan pesan jelas.

Belum bisa saya uji: server Hostinger sendiri (LiteSpeed), koneksi GitHub Actions ↔ server Anda, dan HTTPS.

---

## 8. Masalah umum

| Gejala | Penyebab & jalan keluar |
|---|---|
| Actions: `~/sirta belum disiapkan` | Bagian 3 belum dikerjakan. |
| Actions: `Permission denied (publickey)` saat `git fetch` | Deploy key (3.2) belum ditambahkan / `~/.ssh/config` belum benar. Uji: `ssh -T git@github.com`. |
| Actions: `composer: command not found` | Jalankan `which composer` lewat PuTTY; bila kosong, pakai `/usr/bin/php /usr/local/bin/composer` dan sesuaikan baris composer di `deploy.yml`. |
| API menjawab 503 "Layanan sedang disiapkan" | `core-api` tidak menemukan `~/sirta/backend/vendor`. Jalankan `composer install` (3.5). |
| Error 500 | Lihat `~/sirta/backend/storage/logs/laravel-*.log`. Sering: `.env` salah, atau `chmod -R 775 storage bootstrap/cache` belum dijalankan. |
| Login berhasil lalu langsung keluar (401) | Header `Authorization` tidak sampai — pastikan `.htaccess` dari deploy terpasang. |
| Refresh `/warga` → 404 | `.htaccess` belum terpasang (jalankan deploy dari Actions). |
| Unggah arsip/lampiran gagal | Naikkan `upload_max_filesize` & `post_max_size` di hPanel. |
| `/ajukan` selalu "tidak cocok" | NIK & tanggal lahir harus sama persis dengan Data Warga, dan warga berstatus Aktif. |
