# 🎮 rental warnet & ps

Sistem Manajemen rental warnet & ps berbasis PHP Native dan MySQL untuk mengelola pengguna, booking perangkat, pembayaran, turnamen, promosi, serta laporan operasional.

---
## link demo : [https://www.hafizh-dev.my.id/gamezone](https://hafizh-dev.my.id/)
## 👥 Tim Pengembang

| Anggota | Branch | Modul |
|----------|----------|----------|
| Hafiz | `anggota/hafiz` | Authentication & User Management |
| Rafli | `anggota/rafli` | Device Management |
| Dika | `anggota/dika` | Booking System & API |
| Iyas | `anggota/iyas` | Payment & Invoice |
| Mico | `anggota/mico` | Promotion, Tournament & Notification |
| Adi | `anggota/adi` | Dashboard, Reports & Settings |

---

## 📂 Struktur Pembagian Tugas

### 👤 Hafiz
**Authentication & User Management**

Branch:
```bash
main (hafiz)
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

### 👤 Rafli
**Device Management**

Branch:

```bash
rafli
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

### 👤 Dika
**Booking & API**

Branch:

```bash
dika
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

### 👤 Iyas
**Payment & Invoice**

Branch:

```bash
iyas
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

### 👤 Mico
**Promotion, Tournament & Notification**

Branch:

```bash
mico
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

### 👤 Adi
**Dashboard, Reports & Settings**

Branch:

```bash
adi
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
git checkout anggota/hafiz
```

atau

```bash
git checkout anggota/rafli
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
