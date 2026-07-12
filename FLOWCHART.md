# Flowchart Sistem GameZone — Rental Warnet & PS

Sistem manajemen rental warnet & PS berbasis **PHP Native 8+** dan **MySQL/MariaDB**.

---

## 1. Flowchart Alur Umum (User & Admin)

```mermaid
flowchart TD
    A[User Buka Website] --> B{Langganan?}
    B -->|Belum| C[Register / Login]
    B -->|Sudah| D[Dashboard]
    C --> D

    D --> E[Pilih Modul]
    E --> F[Booking Device]
    E --> G[Lihat Tournament]
    E --> H[Lihat Promotion]
    E --> I[Wallet / Profile]
    E --> J[Notifications]

    F --> K[Isi Form: Device, Tanggal, Jam, Durasi]
    K --> L[Pilih Metode Bayar: QRIS / Bank Transfer / E-Wallet]
    L --> M[Hitung Total + Diskon Membership]
    M --> N{Pembayaran Berhasil?}
    N -->|Ya| O[Booking CONFIRMED + Invoice Auto-Generate + QR Ticket]
    N -->|Tidak| P[Tampil Error, Ulangi]
    O --> Q[Notifikasi ke User]

    subgraph ADMIN_STAFF [Admin / Staff]
        R[Login Admin] --> S[Manajemen Device: CRUD + Status]
        S --> T[Manajemen User]
        T --> U[Verifikasi Payment]
        U --> V[Laporan Booking & Pendapatan]
        V --> W[Pengaturan Sistem]
    end
```

---

## 2. Flowchart Detail Proses Booking + Pembayaran

```mermaid
flowchart TD
    A[Customer: Pilih Device & Waktu] --> B[Submit Form Booking POST]
    B --> C{Validasi Input?<br/>device/date/time/durasi 1-8 jam}
    C -->|Invalid| Z[Tampilkan Error]
    C -->|Valid| D[Cek Ketersediaan Device di DB<br/>checkDeviceAvailability]
    D -->|Tidak Tersedia| Z
    D -->|Tersedia| E[Hitung Harga: base - diskon membership]
    E --> F[BEGIN TRANSACTION]
    F --> G[INSERT bookings status=confirmed]
    G --> H[UPDATE device status=booked]
    H --> I[INSERT invoice_details status=paid]
    I --> J[INSERT payments status=paid + transaction_id]
    J --> K[COMMIT]
    K --> L[Kirim Notifikasi Booking Confirmed]
    L --> M[Redirect ke Booking View + QR Ticket]
```

---

## 3. Flowchart Arsitektur Sistem

```mermaid
flowchart LR
    U[Browser / Client] -->|HTTP Request| WEB[index.php / dashboard.php]
    WEB --> AUTH[inc/auth.php: Session & Role]
    WEB --> FN[inc/functions.php: Helper]
    WEB --> MOD[modules/*: Booking, Device, Payment, Tournament, Promotion]
    MOD --> API[api/*: bookings-api, notifications]
    MOD --> DB[(MySQL: devices, users, bookings, payments, invoice_details, tournaments, promotions, notifications)]
    WEB --> TPL[templates/: header, sidebar]
    TPL --> ASSET[assets/: CSS, JS, uploads]
```

---

## 4. Pembagian Modul per Anggota

| Anggota | Modul | File Utama |
|---------|-------|-----------|
| Hafiz | Auth & User | login.php, register.php, modules/users/ |
| Rafli | Device | modules/devices/ |
| Dika | Booking & API | modules/bookings/, api/bookings-api.php |
| Iyas | Payment & Invoice | modules/payments/, modules/invoices/ |
| Mico | Promotion, Tournament, Notification | modules/promotions/, modules/tournaments/, api/notifications.php |
| Adi | Dashboard, Reports, Settings | dashboard.php, modules/reports/, modules/settings/ |

---

## 6. Flowchart Tournament

```mermaid
flowchart TD
    A[Buka Daftar Tournament] --> B{ID valid & Role Customer?}
    B -->|Tidak| Z[Redirect]
    B -->|Ya| C{Status = registration_open?}
    C -->|Tutup| W[Warning]
    C -->|Buka| D{Sudah terdaftar?}
    D -->|Ya| I[Info & stop]
    D -->|Belum| E{Kuota penuh?}
    E -->|Penuh| F[Closed]
    E -->|Ada slot| G[Confirm Registration POST]
    G --> H[BEGIN TRANSACTION + FOR UPDATE lock]
    H --> J[INSERT participants]
    J --> K[COMMIT + Notifikasi + Redirect]
```

---

## 7. Flowchart Promotion

```mermaid
flowchart TD
    A[Admin: Add / Edit Promotion] --> B[Isi Code, Title, Discount, Periode]
    B --> C[SIMPAN is_active=1]
    C --> D[Tampil di Beranda & List Promo]
    D --> E[Customer lihat Promo]
    E --> F{Filter is_active & tanggal}
    F -->|Kedaluwarsa| G[Sembunyi]
    F -->|Aktif| H[Promo diterapkan ke booking diskon otomatis]
```

---

## 5. Tech Stack

- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5, Tailwind (CDN)
- **Backend:** PHP Native 8+
- **Database:** MySQL / MariaDB
- **Keamanan:** password_hash, CSRF token, session httpOnly + sameSite
