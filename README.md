# 🎮 rental warnet & ps

Sistem Manajemen rental warnet & ps berbasis PHP Native dan MySQL untuk mengelola pengguna, booking perangkat, pembayaran, turnamen, promosi, serta laporan operasional.

---
## link demo : [hafizh-dev.my.id](https://hafizh-dev.my.id/)
## 👥 Tim Pengembang

| Anggota | Branch | Modul |
|----------|----------|----------|
| muhammad hafizh ramadhan (19251193) | `anggota/muhammad hafizh ramadhan (19251193)` | Authentication & User Management |
| ⁠Rafli Hera Sumadi (19250069) | `anggota/⁠Rafli Hera Sumadi (19250069)` | Device Management |
| m andika farhansyah (19250604) | `anggota/m andika farhansyah (19250604)` | Booking System & API |
| muhammad iyas arkan (19252261) | `anggota/muhammad iyas arkan (19252261)` | Payment & Invoice |
| Mico Kurniawan (19251446) | `anggota/Mico Kurniawan (19251446)` | Promotion, Tournament & Notification |
| Adi hermawandi (19250225) | `anggota/Adi hermawandi (19250225)` | Dashboard, Reports & Settings |

---

## 📂 Struktur Pembagian Tugas

### 👤 muhammad hafizh ramadhan (19251193)
**Authentication & User Management**

Branch:
```bash
muhammad hafizh ramadhan (19251193)
```

Modul:

```text
inc/auth.php
login.php
register.php
logout.php
modules/profile.php
modules/users/
```

Fitur:
- Login
- Register
- Logout
- Session Management
- Role Management
- Profile Management
- User CRUD

---

### 👤 ⁠Rafli Hera Sumadi (19250069)
**Device Management**

Branch:

```bash
⁠Rafli Hera Sumadi (19250069)
```

Modul:

```text
modules/devices/
```

Fitur:
- CRUD Device
- Status Device
- Device Availability
- Device Monitoring

---

### 👤 m andika farhansyah (19250604)
**Booking & API**

Branch:

```bash
m andika farhansyah (19250604) (main)
```

Modul:

```text
modules/bookings/
api/bookings-api.php
```

Fitur:
- Booking Device
- Booking Schedule
- Booking History
- REST API Booking

---

### 👤 muhammad iyas arkan (19252261)
**Payment & Invoice**

Branch:

```bash
muhammad iyas arkan (19252261)
```

Modul:

```text
modules/payments/
modules/invoices/
```

Fitur:
- Pembayaran Booking
- Generate Invoice
- Riwayat Pembayaran
- Status Pembayaran

---

### 👤 Mico Kurniawan (19251446)
**Promotion, Tournament & Notification**

Branch:

```bash
Mico Kurniawan (19251446)
```

Modul:

```text
modules/promotions/
modules/tournaments/
modules/notifications.php
api/notifications.php
```

Fitur:
- CRUD Promo
- CRUD Turnamen
- Broadcast Notifikasi
- API Notifikasi

---

### 👤 Adi hermawandi (19250225)
**Dashboard, Reports & Settings**

Branch:

```bash
Adi hermawandi (19250225)
```

Modul:

```text
dashboard.php
modules/reports/
modules/settings/
modules/home.php
templates/
assets/
```

Fitur:
- Dashboard Statistik
- Laporan Booking
- Laporan Pendapatan
- Pengaturan Sistem
- Template UI
- Asset Management

---

## 🌳 Git Workflow

### Clone Repository

```bash
git clone https://github.com/USERNAME/REPOSITORY.git
cd REPOSITORY
```

### Pindah ke Branch Masing-Masing

```bash
git checkout anggota/muhammad hafizh ramadhan (19251193)
```

atau

```bash
git checkout anggota/⁠Rafli Hera Sumadi (19250069)
```

### Commit Perubahan

```bash
git add .
git commit -m "feat: menambahkan fitur baru"
git push origin nama-branch
```

### Merge ke Main

```bash
git checkout main
git pull origin main
git merge anggota/nama-branch
git push origin main
```

---

## 🛠️ Tech Stack

### Frontend
- HTML5
- CSS3
- JavaScript
- Bootstrap 5

### Backend
- PHP Native 8+

### Database
- MySQL / MariaDB

### Version Control
- Git
- GitHub

---

## 📋 Modul Utama

- Authentication
- User Management
- Device Management
- Booking System
- Payment System
- Invoice System
- Tournament Management
- Promotion Management
- Notification System
- Dashboard & Reporting

---

## 🚀 Cara Menjalankan Project

1. Clone repository
2. Import database ke MySQL
3. Atur konfigurasi database pada:

```text
config/database.php
```

4. Jalankan menggunakan Laragon/XAMPP

```text
http://localhost/gamezome-rental
```

---

## 📄 License

Project ini dibuat untuk keperluan akademik dan pembelajaran.
