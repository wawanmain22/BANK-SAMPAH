# Bank Sampah Pak Toni

Sistem operasional **bank sampah** berbasis web — kelola setoran sampah nasabah jadi saldo & poin, rekap stok, tangani pencairan, jual ke mitra, olah jadi produk bernilai, dan publikasikan edukasi/merchandise ke publik.

Dibangun dengan **Laravel 13 + Livewire 4 SFC + MaryUI** bertema **GreenNature** (dark forest green + warm cream).

---

## Stack

| Area | Tools |
| --- | --- |
| Backend | PHP 8.3, Laravel 13, Fortify 1 (auth + 2FA) |
| Frontend | Livewire 4 (SFC), MaryUI, Tailwind CSS 4, DaisyUI 5 |
| DB | PostgreSQL (production), SQLite in-memory (tests) |
| Tooling | Laravel Boost MCP, Laravel Pail, Laravel Pint, PHPUnit 12 |

---

## Fitur Utama

### Public
- Landing page dengan stats, edukasi terbaru, produk unggulan, cara kerja
- Halaman **Edukasi** (artikel publik + galeri foto)
- Halaman **Merchandise** (produk hasil olahan)

### Panel Nasabah
- Dashboard saldo tersedia, tertahan, poin
- Histori transaksi nabung (setoran sampah)
- Pencairan saldo (cash / transfer)
- Histori poin + tukar merchandise

### Panel Admin / Owner
- **Master data** — Nasabah, Kategori Sampah, Barang Sampah (+ riwayat harga), Produk, Mitra
- **Transaksi** — Nabung, Sedekah, Pencairan, Penjualan ke Mitra, Pengolahan, Tukar Poin
- **Inventory & Release Saldo** — snapshot stok per kategori + alur release saldo setelah mitra membayar
- **Histori Poin** & **Edukasi** (CMS artikel dengan cover + galeri)
- Dashboard ringkasan operasional (saldo, kategori aktif, transaksi bulan ini)

---

## Setup Lokal

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database (pastikan PostgreSQL jalan & .env sudah diset)
php artisan migrate --seed

# 4. Jalankan dev stack (server + queue + vite)
composer run dev
```

Akses di `http://127.0.0.1:8000`.

### Akun Demo (dari seeder)

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@banksampah.test` | `password` |
| Owner | `owner@banksampah.test` | `password` |
| Nasabah | `nasabah@banksampah.test` | `password` |
| Nasabah | `budi@banksampah.test` | `password` |
| Nasabah | `dewi@banksampah.test` | `password` |
| Nasabah | `eko@banksampah.test` | `password` |
| Nasabah | `fatimah@banksampah.test` | `password` |

Seeder otomatis men-generate transaksi nabung, sedekah, pencairan, penjualan, dan histori poin agar dashboard tidak kosong.

---

## Testing

```bash
# Semua tes (PHPUnit 12, SQLite :memory:)
php artisan test --compact

# Satu file
php artisan test --compact tests/Feature/SavingTest.php

# Filter nama
php artisan test --compact --filter=canRecordSaving
```

Linting & style (Pint):

```bash
composer run lint
```

---

## Struktur Penting

```
app/
├── Actions/Fortify/          # Custom registration, profile, password
├── Concerns/                 # Trait validasi reusable
├── Livewire/                 # (class-based, jika ada)
├── Models/                   # User, WasteCategory, SavingTransaction, ...
└── Services/                 # BalanceService, InventoryService, ...

resources/views/
├── components/layouts/       # app (panel), auth, public
├── pages/admin/*             # Livewire SFC per modul admin
├── pages/nasabah/*           # Livewire SFC nasabah
├── pages/auth/*              # Login, register, 2FA, dsb.
├── public/                   # Landing + edukasi + merchandise
└── settings/                 # Profil & keamanan (tab layout)

database/
├── migrations/
├── factories/
└── seeders/                  # DatabaseSeeder + demo data
```

---

## Tema

Palet GreenNature ada di `resources/css/app.css` sebagai custom DaisyUI theme `greennature`:

- Primary (sage): `#5aa15e`
- Secondary (dark forest — navbar & sidebar): `#253526`
- Accent (gold — CTA): `#ecb338`
- Base-100 (card): `#faf8f3`
- Base-200 (body): `#ebe8de`

Font utama: **Lato** via [Bunny Fonts](https://fonts.bunny.net) (GDPR-friendly).

---

## Catatan Dev

- Panel admin, owner, dan nasabah memakai **layout yang sama** (`layouts::app`) — menu sidebar disesuaikan role.
- Transaksi setoran sampah menggunakan **price snapshot pattern** — harga kategori bisa berubah, tapi nilai transaksi historis tetap.
- Semua mutasi saldo / stok / poin dijalankan **atomik** via service + `DB::transaction`, dengan audit trail polymorphic (`balance_histories`, `point_histories`, `inventory_movements`).
