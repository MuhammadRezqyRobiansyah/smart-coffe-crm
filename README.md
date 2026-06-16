# Smart Coffee CRM

Ini adalah proyek aplikasi web berbasis Laravel dan Livewire.

## Prasyarat

Pastikan sistem Anda sudah memiliki perangkat lunak berikut:
- **PHP** (minimal versi 8.3)
- **Composer**
- **Node.js** dan **NPM**
- **Database** (MySQL / PostgreSQL / SQLite, dll.)

## Cara Setup dan Menjalankan Proyek

### 1. Masuk ke Direktori Proyek
Pastikan Anda menjalankan perintah-perintah berikut di dalam folder **`smartcoffecrm`** (tempat di mana file `artisan` dan `composer.json` berada):
```bash
cd "c:\smart coffe crm\smartcoffecrm"
```

### 2. Setup Otomatis (Direkomendasikan)
Proyek ini memiliki skrip `setup` bawaan dari composer yang akan otomatis melakukan instalasi dependensi, menyalin file environment, men-generate key, dan melakukan migrasi database. Jalankan perintah:

```bash
composer setup
```
*(Catatan: pastikan Anda sudah mengatur konfigurasi database di file `.env` jika menggunakan MySQL/PostgreSQL. Jika Anda menggunakan SQLite, konfigurasi bawaan biasanya sudah cukup.)*

### 3. Menjalankan Server Development
Untuk menjalankan proyek, Anda tidak perlu repot menjalankan server PHP dan Vite secara terpisah. Cukup gunakan perintah:

```bash
composer dev
```
Perintah ini akan menjalankan tiga proses sekaligus secara bersamaan:
- Laravel Development Server (`php artisan serve`)
- Vite Development Server (`npm run dev`)
- Laravel Queue (`php artisan queue:listen`)

Setelah server berjalan, Anda dapat mengakses aplikasinya melalui browser pada tautan:
**[http://localhost:8000](http://localhost:8000)**

---

### Langkah Manual (Jika tidak menggunakan `composer setup`)

Jika Anda ingin melakukan setup langkah demi langkah, berikut adalah urutannya:
1. Instal dependensi PHP: `composer install`
2. Salin file environment: `cp .env.example .env` (lalu sesuaikan isi file `.env`)
3. Generate App Key: `php artisan key:generate`
4. Instal dependensi frontend: `npm install`
5. Jalankan migrasi database: `php artisan migrate`
6. Jalankan server backend: `php artisan serve`
7. Jalankan server frontend (di terminal terpisah): `npm run dev`
