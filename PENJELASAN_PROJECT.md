# Penjelasan Project GameZone — Rental Warnet & PS

*Dokumen ini dibuat untuk presentasi ke dosen. Dijelaskan dengan bahasa sederhana agar mudah dipahami orang awam.*

---

## 1. GameZone itu apa?

GameZone adalah **sistem pemesanan (booking) perangkat game** seperti PlayStation dan PC Gaming di sebuah warnet/rental PS. Lewat website ini, pelanggan bisa:

- Melihat daftar perangkat yang tersedia
- Memesan perangkat untuk waktu tertentu
- Bayar langsung & dapat tiket (QR)
- Ikut turnamen & ambil promo
- Mengecek riwayat & wallet (poin) mereka

Sedangkan **admin & staff** bisa mengelola perangkat, pengguna, pembayaran, laporan, dan pengaturan sistem.

Sistem ini dibuat dengan **PHP Native** (bahasa pemrograman sisi server) dan **MySQL** (tempat menyimpan data), tanpa framework berat — jadi ringan dan mudah dipahami.

---

## 2. Struktur Folder & File (Fungsi Tiap Bagian)

Bayangkan project ini seperti sebuah kantor: ada **resepsionis** (halaman depan), **ruang kerja tiap divisi** (folder modules), **brankas data** (database), dan **buku panduan umum** (folder inc).

### 📁 File di Akar (Root) — "Halaman Utama"

| File | Fungsi (bahasa awam) |
|------|----------------------|
| `index.php` | **Beranda publik.** Halaman depan yang bisa dilihat siapa saja: tampilkan device, turnamen, promo unggulan. |
| `login.php` | **Masuk untuk pelanggan.** Form login khusus customer (pembeli). |
| `register.php` | **Daftar akun baru.** Pelanggan membuat akun di sini. |
| `logout.php` | **Keluar.** Menghapus sesi login, lalu kembali ke halaman login. |
| `dashboard.php` | **Pusat kontrol pengguna.** Semua menu (booking, wallet, profil, dll) dibuka dari sini setelah login. |
| `setup.php` | **Alat instalasi.** Dipakai sekali di awal untuk membuat struktur database otomatis. |

### 📁 `admin/` — "Ruang Khusus Karyawan"

| File | Fungsi |
|------|--------|
| `admin/index.php` | Halaman login **khusus admin & staff**. Customer tidak boleh masuk lewat sini. |

### 📁 `api/` — "Pelayan Data Otomatis"

Folder ini berisi endpoint yang mengembalikan data dalam format **JSON** (bukan halaman HTML), dipakai oleh tombol-tombol di dashboard secara otomatis tanpa reload halaman.

| File | Fungsi |
|------|--------|
| `bookings-api.php` | Mengubah **status booking** (mis. dari "main" jadi "selesai") lewat permintaan AJAX. |
| `notifications.php` | Mengambil **jumlah & isi notifikasi** pelanggan, serta memperbarui status booking dari sisi notifikasi. |

### 📁 `config/` — "Pengaturan Koneksi"

| File | Fungsi |
|------|--------|
| `database.php` | **Kunci brankas database.** Berisi alamat server, nama database, user & password MySQL. Menyediakan koneksi `$pdo` yang dipakai di seluruh sistem. Pakai PDO (aman dari SQL injection). |

### 📁 `inc/` — "Buku Panduan Bersama"

Berisi fungsi yang dipakai berulang-ulang di banyak tempat, supaya tidak ditulis ulang.

| File | Fungsi |
|------|--------|
| `auth.php` | **Sistem keamanan & login.** Mengatur session (status login), membedakan role (admin/staff/customer), dan proteksi halaman (`requireLogin`, `requireAdmin`, dll). Juga enkripsi password. |
| `functions.php` | **Kotak alat umum.** Fungsi bantu seperti format uang Rupiah, format tanggal, badge status, CSRF token, buat nomor invoice, hitung diskon membership, dan URL QR ticket. |

### 📁 `modules/` — "Divisi-Divisi Kerja"

Ini inti aplikasi. Tiap folder = satu fitur utama.

| Folder | Isi & Fungsi |
|--------|--------------|
| `bookings/` | **Pemesanan.** `create.php` (form booking + bayar), `index.php` (daftar booking), `view.php` (detail + QR ticket), `cancel.php` (batal booking), `promotions/` (booking lewat promo). |
| `devices/` | **Manajemen perangkat.** `index.php` (daftar), `create.php` & `edit.php` (tambah/ubah device + upload foto), `delete.php` (hapus). Status device: tersedia / dibooking / main / rusak. |
| `users/` | **Manajemen pengguna.** CRUD data user, hanya untuk admin. |
| `payments/` | **Pembayaran.** `index.php` (daftar pembayaran), `verify.php` (admin verifikasi bukti bayar). |
| `invoices/` | `generate.php` — mencetak **invoice/struk** booking dalam PDF. |
| `promotions/` | **Promo/diskon.** `index.php`, `create.php`, `edit.php`, `delete.php`, `view.php` — kelola kode promo. |
| `tournaments/` | **Turnamen.** `index.php` (daftar), `view.php` (detail), `create.php`/`edit.php`/`delete.php` (kelola admin), `register.php`/`unregister.php` (ikut/keluar turnamen). |
| `notifications.php` | Menampilkan **daftar notifikasi** pelanggan (booking konfirmasi, turnamen, dll). |
| `reports/` | **Laporan.** Grafik & tabel pendapatan, booking, dan device terlaris (khusus admin). |
| `settings/` | **Pengaturan sistem.** Admin mengubah konfigurasi umum (disimpan di tabel `settings`). |
| `profile.php` | Pelanggan mengubah **profil & password** sendiri. |
| `wallet.php` | **Dompet poin.** Top-up poin, lihat level membership (bronze/silver/gold) & bonus. |
| `reviews/` | `create.php` — pelanggan memberi **bintang/ulasan** setelah main. |
| `home.php` | Konten beranda **setelah login** (statistik & ringkasan). |

### 📁 `templates/` — "Cetakan Tampilan"

| File | Fungsi |
|------|--------|
| `header.php` | **Header/navbar** halaman depan (logo + menu). |
| `sidebar.php` | **Menu samping** dashboard (daftar menu navigasi per role). |

### 📁 `assets/` — "Bahan Tampilan"

| Folder | Fungsi |
|--------|--------|
| `css/` | File **style/tampilan** (warna, layout, efek neon). |
| `js/` | **JavaScript** (animasi, notifikasi otomatis, konfirmasi hapus, sidebar). |
| `uploads/` | Tempat **foto perangkat** yang di-upload admin. |

### 📁 `rental-warpes/` — "Project Terpisah"

Folder ini berisi **sub-sistem rental warpes** (warnet + PS terpisah) dengan struktur `api/` dan `modules/bookings/` sendiri. Bisa dianggap modul mandiri di dalam folder yang sama.

---

## 3. Cara Kerja Sistem (Alur Singkat)

1. **Pelanggan** buka `index.php` → daftar/lihat device.
2. **Login** via `login.php` → masuk `dashboard.php`.
3. Pilih **Booking** (`modules/bookings/create.php`): pilih device, waktu, durasi, metode bayar.
4. Sistem **validasi** → cek device tersedia → hitung total & diskon membership.
5. Sekali klik bayar → dalam **1 transaksi database**: booking tersimpan, device jadi "dibooking", invoice & payment otomatis dibuat.
6. Pelanggan dapat **QR ticket** (`view.php`) untuk check-in.
7. Saat main selesai, staff ubah status lewat `api/bookings-api.php` → device kembali "tersedia" & poin pelanggan bertambah.

---

## 4. Teknologi yang Dipakai

| Bagian | Teknologi |
|--------|-----------|
| Tampilan (Frontend) | HTML, CSS, JavaScript, Bootstrap 5, Tailwind |
| Logika (Backend) | PHP Native 8+ |
| Penyimpanan Data | MySQL / MariaDB (lewat PDO) |
| Keamanan | Password di-hash, token CSRF, session HttpOnly + SameSite |

---

## 5. Poin Keamanan (Bagus untuk Disebut ke Dosen)

- **Password aman:** tidak disimpan mentah, tapi di-hash (`password_hash`).
- **Anti manipulasi form:** tiap aksi penting pakai **CSRF token**.
- **Pembagian hak akses:** ada `requireAdmin()`, `requireStaffOrAdmin()`, `requireLogin()` — customer tidak bisa buka menu karyawan.
- **Transaksi database:** booking menggunakan `BEGIN TRANSACTION` … `COMMIT` agar data tidak setengah jadi jika gagal.
- **Cek ketersediaan ganda:** turnamen pakai `FOR UPDATE` (kunci baris) agar kuota tidak bentrok saat dua orang daftar bersamaan.

---

## 6. Pembagian Tugas Tim (Untuk Disebutkan)

| Anggota | Modul |
|---------|-------|
| Hafiz | Authentication & User Management |
| Rafli | Device Management |
| Dika | Booking System & API |
| Iyas | Payment & Invoice |
| Mico | Promotion, Tournament & Notification |
| Adi | Dashboard, Reports & Settings |

---

*Catatan: Folder `vendor/` sebelumnya sudah dihapus karena kosong & tidak dipakai (project tidak menggunakan library pihak ketiga / Composer).*
