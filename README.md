# SmartWarehouse AI - Intelligent Warehouse Management System

[![SmartWarehouse CI/CD Pipeline](https://github.com/username/proyek-rpl-smartwarehouse/actions/workflows/ci.yml/badge.svg)](https://github.com/username/proyek-rpl-smartwarehouse/actions)

> **Tugas Proyek Akhir Mandiri Mata Kuliah Rekayasa Perangkat Lunak (TIKB2503021) — Semester 3**  
> **Program Studi S1 Teknik Informatika — STMIK IKMI Cirebon**  
> **Dosen Pengampu:** Yudhistira Arie Wijaya, M.Kom., CDS.

---

## 👤 Identitas Pengembang (Mandiri / Perorangan)
- **Nama Mahasiswa** : Achmad Fauzi
- **NIM**            : 41257174
- **Kelas / Semester**: 1F-2025 / Semester 3
- **Tema Topik**     : Sistem Informasi Berbasis Web untuk Pemberdayaan Operasional
- **Sifat Pengerjaan**: **100% Mandiri / Perorangan (Individual Ownership & Full End-to-End Build)**
- **Mata Kuliah**    : Rekayasa Perangkat Lunak — 4 SKS
- **Institusi**      : Program Studi S1 Teknik Informatika — STMIK IKMI Cirebon
- **Dosen Pengampu** : Yudhistira Arie Wijaya, M.Kom.
- **Judul Proyek**   : **SmartWarehouse AI: Sistem Informasi Manajemen Pergudangan Cerdas Terintegrasi Artificial Intelligence (Google Gemini & Heuristic NLP) dan WhatsApp Notification Gateway**
- **Dokumen Laporan**: `docs/LAPORAN_PROYEK_AKHIR_RPL.pdf`
- **Slide Presentasi**: `docs/PRESENTASI_PROYEK_AKHIR.pptx`

---

## 🎥 Tautan Video Presentasi Progres & Demo Final

| Tahapan Video | Topik Bahasan | Durasi | Tautan Video (YouTube / G-Drive) |
|---|---|---|---|
| **Video Tahap 1** | Inisiasi PRD, Identifikasi Masalah & Konsep AI | [Tautan Video Tahap 1](https://youtu.be/VsBLO6mK9rc) |
| **Video Tahap 2** | Pemodelan 4 Diagram UML & Walkthrough UI/UX | [Tautan Video Tahap 2](https://youtu.be/D6_aMIjdjcQ) |
| **Video Tahap 2** | Konstruksi Kode, Integrasi AI, Testing dan CI/CD | [Tautan Video Tahap 3](https://youtu.be/_2yQ6J5XAUo) |
| **Video Final** | Live Demo Aplikasi Utuh, Unit Test & CI/CD Pipeline | [Tautan Video Final Demo](https://youtu.be/bTLfD4p9ZwU) |

---

## 📦 Deskripsi Aplikasi & Arsitektur Sistem

**SmartWarehouse AI** adalah aplikasi web Enterprise Resource Planning (ERP) pergudangan modern berskala penuh (*Full End-to-End Build*) yang dibangun secara independen. Sistem ini mentransformasikan proses inventarisasi konvensional yang rentan *human error*, keterlambatan *stock opname*, dan lambatnya respon kehabisan barang menjadi sistem pergudangan otonom berbasis kecerdasan buatan.

### Fitur Utama Sistem:
1. **Multi-Role Access Control**: Autentikasi ketat berbasis Role (Admin, Supervisor, Operator) dengan proteksi Session Hijacking dan CSRF.
2. **Katalog Master Data & Manajemen Lokasi Dinamis**: Pengelolaan kategori barang, unit satuan, titik lokasi rak/gudang, dan pengaturan batas minimum stok (*safety stock*).
3. **Pencatatan Transaksi 3 Arah**: 
   - *Inbound* (Barang Masuk dari Supplier)
   - *Outbound* (Barang Keluar ke Customer/Divisi)
   - *Transfer* (Pemindahan Antar-Rak/Gudang)
4. **Smart Warehouse AI Engine (Dual-Core Intelligence)**:
   - **Google Gemini API Core**: Pemrosesan Natural Language Query (NLQ) kontekstual terhadap database real-time.
   - **Heuristic NLP Core**: Deteksi *intent* instan untuk query stok, rekomendasi rak kosong, ringkasan mutasi, dan verifikasi ketersediaan barang.
   - **Direct Safe SQL Execution**: Eksekusi query baca (*read-only*) langsung dari asisten AI.
5. **WhatsApp Notification Gateway**: Otomasi pengiriman pesan peringatan stok kritis (*low stock alert*) dan notifikasi bukti mutasi transaksi melalui WhatsApp API / Fonnte.
6. **Laporan & Audit Log Aktivitas**: Pelaporan komprehensif (Stok, Mutasi Masuk/Keluar/Transfer) serta pelacakan audit trail setiap aksi pengguna (*Activity Logging*).

---

## 🛠️ Petunjuk Instalasi & Menjalankan Aplikasi (Setup Instructions)

### 1. Persyaratan Sistem (Prerequisites)
- PHP versi 8.1 atau 8.2 (dengan ekstensi `pdo`, `pdo_mysql`, `curl`, `mbstring`, `openssl`).
- Web Server Apache & Basis Data MySQL (disarankan menggunakan XAMPP versi terbaru).
- Composer (opsional untuk pustaka tambahan).
- Web Browser modern (Google Chrome / Mozilla Firefox / Microsoft Edge).

### 2. Langkah Instalasi Lokal
1. **Klon Repositori ke Direktori Web Server (`htdocs`)**:
   ```bash
   cd c:/xampp/htdocs/
   git clone https://github.com/FauziAR31/proyek-rpl-smartwarehouse.git SmartWarehouse
   ```

2. **Konfigurasi Database MySQL**:
   - Buka XAMPP Control Panel dan nyalakan modul **Apache** dan **MySQL**.
   - Buka phpMyAdmin di browser: `http://localhost/phpmyadmin/`.
   - Buat database baru bernama `smartwarehouse`.
   - Impor berkas struktur dan seed data dari:
     `d:/xampp/htdocs/SmartWarehouse/database/smartwarehouse.sql`.
   - Jalankan patch migrasi skema tabel terbaru dengan mengeksekusi:
     ```bash
     php patch_db.php
     ```

3. **Konfigurasi Basis Aplikasi (`config/config.php` & `config/database.php`)**:
   - Pastikan konfigurasi koneksi database di `config/database.php` sesuai dengan environment lokal:
     ```php
     private $host = 'localhost';
     private $user = 'root';
     private $pass = '';
     private $dbname = 'smartwarehouse';
     ```
   - Sesuaikan `BASE_URL` di `config/config.php`:
     ```php
     define('BASE_URL', 'http://localhost/SmartWarehouse');
     ```

4. **Kredensial Akun Default**:
   | Role | Username | Password |
   |---|---|---|
   | **Admin** | `admin` | `password123` |
   | **Supervisor** | `supervisor` | `password123` |
   | **Operator** | `operator` | `password123` |

5. **Pengujian Otomatis (Unit Testing)**:
   Jalankan script pengujian fungsional otomatis di terminal:
   ```bash
   php functional_test.php
   ```

---

## 🧪 CI/CD Pipeline (GitHub Actions)

Proyek ini telah dikonfigurasi dengan alur otomatisasi *Continuous Integration / Continuous Deployment* (CI/CD) pada file `.github/workflows/ci.yml`. Setiap *commit* atau *pull request* ke branch `main`/`master` akan memicu:
1. Pemeriksaan dan verifikasi sintaks seluruh file PHP (*PHP Linting*).
2. Pembangunan container MySQL 8.0 dan inisialisasi skema basis data `smartwarehouse`.
3. Eksekusi rangkaian *Automated Unit & Functional Tests* dengan status *passing* hijau.

---

## 📜 Lisensi & Hak Cipta
Dikembangkan untuk memenuhi Tugas Proyek Akhir Mandiri Rekayasa Perangkat Lunak STMIK IKMI Cirebon. Hak cipta dilindungi oleh pengembang.


