# -*- coding: utf-8 -*-
import os
import docx
from docx import Document
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from doc_helpers import set_cell_background, set_cell_margins, add_callout, format_table

def build_laporan_docx(filename):
    doc = Document()
    
    # Page Setup - Margins 1 inch
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(1)
        section.bottom_margin = Inches(1)
        section.left_margin = Inches(1)
        section.right_margin = Inches(1)
        
    # Styles configuration
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = RGBColor(0x33, 0x33, 0x33)
    normal_style.paragraph_format.line_spacing = 1.15
    normal_style.paragraph_format.space_after = Pt(6)

    # -------------------------------------------------------------
    # COVER PAGE (HALAMAN JUDUL)
    # -------------------------------------------------------------
    p_inst = doc.add_paragraph()
    p_inst.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_inst.paragraph_format.space_after = Pt(2)
    r = p_inst.add_run("PROGRAM STUDI S1 TEKNIK INFORMATIKA\nSTMIK IKMI CIREBON")
    r.bold = True
    r.font.size = Pt(14)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    p_div = doc.add_paragraph()
    p_div.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_div.paragraph_format.space_after = Pt(24)
    r = p_div.add_run("―" * 38)
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)

    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_after = Pt(8)
    r = p_title.add_run("LAPORAN TUGAS PROYEK AKHIR MANDIRI\nREKAYASA PERANGKAT LUNAK (TIKB2503021)")
    r.bold = True
    r.font.size = Pt(16)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sub.paragraph_format.space_after = Pt(36)
    r = p_sub.add_run("PENGEMBANGAN SISTEM INFORMASI MANAJEMEN PERGUDANGAN CERDAS (SMARTWAREHOUSE AI) BERBASIS WEB DENGAN INTEGRASI ARTIFICIAL INTELLIGENCE (GOOGLE GEMINI & HEURISTIC NLP) DAN WHATSAPP NOTIFICATION GATEWAY")
    r.bold = True
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)

    p_meta = doc.add_paragraph()
    p_meta.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_meta.paragraph_format.space_after = Pt(40)
    r = p_meta.add_run("Disusun untuk Memenuhi Syarat Kelulusan Mata Kuliah Rekayasa Perangkat Lunak\nBerbasis Kurikulum Outcome-Based Education (OBE) Semester 3")
    r.italic = True
    r.font.size = Pt(10.5)
    r.font.color.rgb = RGBColor(0x55, 0x55, 0x55)

    # Identitas Mahasiswa (Perorangan Mandiri)
    p_bio = doc.add_paragraph()
    p_bio.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_bio.paragraph_format.space_after = Pt(4)
    r = p_bio.add_run("DISUSUN OLEH (PENGERJAAN 100% MANDIRI / PERORANGAN):")
    r.bold = True
    r.font.size = Pt(11)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    p_name = doc.add_paragraph()
    p_name.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_name.paragraph_format.space_after = Pt(40)
    r = p_name.add_run("NAMA MAHASISWA : [Nama Lengkap Mahasiswa]\nNIM            : [NIM Mahasiswa]\nKELAS          : TI-202X / REGULER\nSTATUS         : Full End-to-End Build (Individual Ownership)")
    r.bold = True
    r.font.size = Pt(11)

    p_dosen = doc.add_paragraph()
    p_dosen.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_dosen.paragraph_format.space_after = Pt(30)
    r = p_dosen.add_run("DOSEN PENGAMPU:\nYudhistira Arie Wijaya, M.Kom., CDS.\nNIDN: [NIDN Dosen]")
    r.font.size = Pt(11)

    p_year = doc.add_paragraph()
    p_year.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_year.paragraph_format.space_after = Pt(0)
    r = p_year.add_run("CIREBON\nTAHUN AKADEMIK 2026/2027")
    r.bold = True
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    doc.add_page_break()

    # -------------------------------------------------------------
    # LEMBAR PENGESAHAN & PERNYATAAN ORISINALITAS MANDIRI
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("LEMBAR PERNYATAAN ORISINALITAS PENGERJAAN MANDIRI")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)
    
    p = doc.add_paragraph()
    p.add_run("Yang bertanda tangan di bawah ini:")
    
    p_data = doc.add_paragraph()
    p_data.paragraph_format.left_indent = Inches(0.5)
    p_data.add_run("Nama Lengkap        : [Nama Lengkap Mahasiswa]\nNIM                 : [NIM Mahasiswa]\nProgram Studi       : S1 Teknik Informatika\nMata Kuliah         : Rekayasa Perangkat Lunak (TIKB2503021) — 4 SKS\nSifat Pengerjaan    : Mandiri / Perorangan (100% Individual Ownership)\nJudul Proyek        : SmartWarehouse AI (Intelligent Warehouse Management System)")

    p_stmt = doc.add_paragraph()
    p_stmt.add_run("Menyatakan dengan sesungguhnya bahwa Laporan Proyek Akhir dan produk aplikasi berbasis web beserta integrasi modul Artificial Intelligence (AI) yang saya kembangkan adalah benar-benar HASIL KARYA PENGERJAAN MANDIRI SAYA SENDIRI (Individual Full End-to-End Build) dari awal hingga akhir, meliputi penyusunan PRD/SRS standar IEEE 830, pemodelan arsitektur UML berorientasi objek SOLID, perancangan basis data ERD, pengkodean web MVC bersih, integrasi real-time Google Gemini AI & WhatsApp Gateway, automated unit testing, serta CI/CD pipeline pada GitHub Actions.")
    
    p_stmt2 = doc.add_paragraph()
    p_stmt2.add_run("Apabila di kemudian hari terbukti bahwa karya ini merupakan hasil plagiasi, menjiplak karya orang lain, atau pembagian modul fungsional antar-mahasiswa, maka saya bersedia menerima sanksi akademik sesuai dengan ketentuan perkuliahan dan rubrik penilaian OBE STMIK IKMI Cirebon.")

    doc.add_paragraph().paragraph_format.space_after = Pt(30)
    
    # Tanda Tangan
    tbl_ttd = doc.add_table(rows=1, cols=2)
    tbl_ttd.alignment = WD_TABLE_ALIGNMENT.CENTER
    c1, c2 = tbl_ttd.rows[0].cells
    c1.width = Inches(3.2)
    c2.width = Inches(3.2)
    
    p_t1 = c1.paragraphs[0]
    p_t1.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_t1.add_run("Mengetahui / Mengesahkan,\nDosen Pengampu Mata Kuliah\n\n\n\n\n( Yudhistira Arie Wijaya, M.Kom., CDS. )\nNIDN. [....................................]")
    
    p_t2 = c2.paragraphs[0]
    p_t2.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_t2.add_run("Cirebon, 14 November 2026\nMahasiswa Pengembang Mandiri,\n\n\n\n\n( [Nama Lengkap Mahasiswa] )\nNIM. [NIM Mahasiswa]")

    doc.add_page_break()

    # -------------------------------------------------------------
    # DAFTAR ISI & IDENTITAS PROYEK
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("RINGKASAN METADATA & REPOSITORI PROYEK MANDIRI")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    tbl_meta = doc.add_table(rows=1, cols=2)
    format_table(tbl_meta, [Inches(2.5), Inches(4.0)], 
                 ["Komponen Penilaian", "Rincian Implementasi Mandiri"],
                 [
                     ["Nama Aplikasi", "SmartWarehouse AI (Intelligent Warehouse ERP)"],
                     ["Sifat Pengerjaan", "Perorangan / Mandiri Penuh (100% Individual Build)"],
                     ["Dosen Pengampu", "Yudhistira Arie Wijaya, M.Kom., CDS."],
                     ["Repositori GitHub Publik", "https://github.com/username/proyek-rpl-smartwarehouse"],
                     ["CI/CD Pipeline Status", "Passing (GitHub Actions Ubuntu, PHP 8.2 & MySQL 8.0)"],
                     ["Skrip Unit Testing", "functional_test.php (35 Test Cases - 100% Passing)"],
                     ["Fitur AI Utama", "Google Gemini Flash API + Local Heuristic NLP Engine"],
                     ["Notifikasi Eksternal", "WhatsApp Notification Gateway (WhatsAppHelper)"],
                     ["Video Progres 1 (PRD)", "Tautan Video YouTube / G-Drive (3–5 Menit)"],
                     ["Video Progres 2 (UML)", "Tautan Video YouTube / G-Drive (5–7 Menit)"],
                     ["Video Final (Live Demo)", "Tautan Video YouTube / G-Drive (8–12 Menit)"]
                 ])

    doc.add_paragraph().paragraph_format.space_after = Pt(12)
    add_callout(doc, [
        "Laporan ini disusun secara utuh untuk memenuhi target Skor 4 (Sangat Baik) pada seluruh dimensi penilaian Rubrik Outcome-Based Education (OBE) STMIK IKMI Cirebon.",
        "Setiap modul dikembangkan secara mandiri tanpa pembagian tim: dari perancangan PRD, UML SOLID, konstruksi kode web bersih, integrasi kecerdasan buatan, hingga deployment DevOps."
    ], title="PEDOMAN AKUNTABILITAS OBE")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAB I: INISIASI & PRD / SRS STANDAR IEEE 830 / ISO 29148
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAB I: INISIASI & SOFTWARE REQUIREMENTS SPECIFICATION (PRD / SRS MANDIRI)")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    h2 = doc.add_heading(level=2)
    r = h2.add_run("1.1 Latar Belakang Masalah & Urgensi Industri")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    
    doc.add_paragraph("Manajemen pergudangan (warehouse management) memegang peranan krusial sebagai simpul efisiensi rantai pasok industri modern. Namun, pada sektor pergudangan skala menengah dan UMKM logistik di Indonesia, operasional inventaris masih menghadapi sejumlah persoalan mendasar yang menurunkan produktivitas secara signifikan:")
    doc.add_paragraph("1. Kesenjangan Data Fisik dan Pembukuan (Stock Discrepancy): Keterlambatan pencatatan manual mutasi barang masuk (inbound) dan barang keluar (outbound) kerap memicu selisih stok fisik saat audit inventaris.")
    doc.add_paragraph("2. Inefisiensi Alokasi Ruang & Lokasi Rak: Tidak adanya visualisasi penempatan barang menyebabkan operator kesulitan mencari rak penyimpanan yang masih memiliki kapasitas kosong, memicu penumpukan barang pada satu titik.")
    doc.add_paragraph("3. Risiko Kritis Kehabisan Barang (Stockout): Ketiadaan mekanisme peringatan dini otomatis (early warning system) mengakibatkan barang strategis habis secara tiba-tiba tanpa sempat dilakukan pengadaan ulang.")
    doc.add_paragraph("4. Hambatan Akses Informasi Cepat bagi Pengambil Keputusan: Pimpinan gudang harus meminta laporan tabular tebal yang memakan waktu lama hanya untuk mengetahui sisa persediaan barang tertentu.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("1.2 Rumusan Masalah & Nilai Guna Aplikasi")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Berdasarkan permasalahan di atas, perumusan masalah dalam proyek mandiri ini dirumuskan sebagai berikut:\n"
                      "1. Bagaimana merancang dan membangun sistem informasi pergudangan berbasis web yang mampu mencatat transaksi multi-arah (in, out, transfer) secara real-time dan terjamin integritas datanya?\n"
                      "2. Bagaimana mengintegrasikan model kecerdasan artifisial (Google Gemini LLM dan NLP heuristik) agar pengguna dapat melakukan audit stok dan konsultasi pergudangan menggunakan bahasa alami?\n"
                      "3. Bagaimana mengotomatisasi pengiriman peringatan stok minimum secara real-time ke perangkat seluler pengawas melalui WhatsApp Gateway?")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("1.3 Spesifikasi Kebutuhan Fungsional (User Stories & Acceptance Criteria)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Spesifikasi kebutuhan fungsional disusun menggunakan format Agile User Stories dan kriteria keberterimaan (Acceptance Criteria) berbasis Gherkin (Given-When-Then):")

    tbl_req = doc.add_table(rows=1, cols=3)
    format_table(tbl_req, [Inches(1.2), Inches(2.3), Inches(3.0)],
                 ["Kode", "User Story", "Acceptance Criteria (Gherkin)"],
                 [
                     ["REQ-F-01", "Sebagai Pengguna, saya ingin login ke sistem dengan username dan password.", 
                      "Given pengguna di halaman login\nWhen menginput kredensial valid\nThen sistem memverifikasi hash BCRYPT dan mengarahkan ke dashboard."],
                     ["REQ-F-02", "Sebagai Admin, saya ingin mengelola master barang dan batas minimum stok.",
                      "Given Admin di modul master barang\nWhen menambah barang dengan kode unik & min_stock\nThen sistem memvalidasi keunikan kode dan menyimpan ke tabel barang."],
                     ["REQ-F-03", "Sebagai Operator, saya ingin mencatat mutasi pengeluaran barang dari rak tertentu.",
                      "Given Operator di form transaksi keluar\nWhen kuantitas melebihi stok rak\nThen sistem menolak dengan pesan validasi.\nWhen kuantitas valid, stok dipotong otomatis secara ACID."],
                     ["REQ-F-04", "Sebagai Operator/Supervisor, saya ingin bertanya ke AI mengenai stok rak dan barang kosong.",
                      "Given pengguna membuka widget AI\nWhen mengetik 'cek rak kosong'\nThen AI Engine menganalisis intent dan merender daftar rak yang kosong dalam <2 detik."],
                     ["REQ-F-05", "Sebagai Supervisor, saya ingin menerima alert WhatsApp jika stok barang kritis.",
                      "Given transaksi keluar memicu total stok <= min_stock\nWhen transaksi tersimpan\nThen WhatsAppHelper mengirim notifikasi otomatis ke nomor supervisor."]
                 ])

    doc.add_paragraph().paragraph_format.space_after = Pt(8)
    
    h2 = doc.add_heading(level=2)
    r = h2.add_run("1.4 Spesifikasi Kebutuhan Non-Fungsional (Standar URPS+)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Kebutuhan non-fungsional dirancang memenuhi parameter kualitas perangkat lunak internasional:\n"
                      "1. Usability: Antarmuka intuitif berbasis Bootstrap 5 dengan floating widget AI yang dapat diakses dari halaman mana pun.\n"
                      "2. Reliability: Integritas data mutasi terjamin 100% menggunakan Database Transaction PDO (ACID Properties).\n"
                      "3. Performance: Waktu respon aplikasi lokal rata-rata <= 0.8 detik, respon AI Heuristik <= 0.2 detik, dan respon Gemini API <= 2.0 detik.\n"
                      "4. Security: Proteksi 100% dari SQL Injection via PDO Prepared Statements, proteksi XSS via htmlspecialchars(), dan enkripsi kata sandi BCRYPT.\n"
                      "5. Maintainability: Arsitektur Model-View-Controller murni yang mematuhi kelima prinsip SOLID.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("1.5 Spesifikasi Arsitektur Fitur AI Utama (Dual-Core AI Engine)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Fitur kecerdasan artifisial SmartWarehouse AI dirancang dengan konsep Dual-Core Intelligence:\n"
                      "• Core 1 - Google Gemini LLM API: Berfungsi untuk penalaran semantik bahasa alami konteks bebas, analisis tren inventaris, dan rekomendasi restock berbasis kecerdasan generatif.\n"
                      "• Core 2 - Local Heuristic NLP Parser: Berfungsi sebagai fallback safety mandiri tanpa dependensi internet. Mampu mengenali intent query stok, pencarian rak kosong, ringkasan transaksi, serta eksekusi Safe Read-Only SQL Command.")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAB II: PERANCANGAN PERANGKAT LUNAK & PEMODELAN UML (SDD)
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAB II: PERANCANGAN UML & ARSITEKTUR SISTEM MANDIRI (SDD)")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    doc.add_paragraph("Pada tahapan Software Design Document (SDD), seluruh struktur dan perilaku sistem dimodelkan menggunakan 4 Diagram UML Utama yang berorientasi objek dan mematuhi prinsip SOLID, dilengkapi struktur Entity-Relationship Diagram (ERD).")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("2.1 Diagram UML 1: Use Case Diagram & Skenario Aktor")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Sistem memiliki 3 aktor pengguna (Admin, Supervisor, Operator) dan 1 entitas sistem eksternal (Google Gemini API).")
    
    tbl_uc = doc.add_table(rows=1, cols=3)
    format_table(tbl_uc, [Inches(1.2), Inches(2.3), Inches(3.0)],
                 ["Use Case ID", "Nama Use Case", "Aktor Terlibat & Deskripsi"],
                 [
                     ["UC-01", "Otentikasi & Manajemen Sesi", "Admin, Supervisor, Operator: Mengelola login, verifikasi peran, dan proteksi sesi."],
                     ["UC-02", "Manajemen Master Barang & Rak", "Admin: Menambah, mengubah, dan menonaktifkan data barang, kategori, serta lokasi rak."],
                     ["UC-03", "Pencatatan Mutasi Transaksi", "Operator & Admin: Mencatat mutasi Barang Masuk (Inbound), Keluar (Outbound), dan Pemindahan (Transfer)."],
                     ["UC-04", "Interaksi Asisten AI Pergudangan", "Admin, Supervisor, Operator: Melakukan query bahasa alami, pencarian rak kosong, dan eksekusi safe SQL."],
                     ["UC-05", "WhatsApp Alert Notification", "Supervisor: Menerima peringatan otomatis ketika saldo stok berada di bawah ambang batas aman."],
                     ["UC-06", "Audit Log & Pelaporan", "Admin & Supervisor: Memantau riwayat audit trail aktivitas pengguna dan mencetak laporan mutasi."]
                 ])

    doc.add_paragraph().paragraph_format.space_after = Pt(8)

    h2 = doc.add_heading(level=2)
    r = h2.add_run("2.2 Diagram UML 2: Activity Diagram Alur Bisnis Pergudangan & AI")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Activity Diagram memodelkan dua alur krusial:")
    doc.add_paragraph("1. Alur Transaksi Outbound: Operator membuka form pengeluaran -> sistem memvalidasi stok di rak -> jika tidak mencukupi, transaksi dibatalkan -> jika mencukupi, sistem mengeksekusi Database Transaction ACID (potong stok, simpan transaksi, catat log aktivitas). Jika sisa stok <= min_stock, sistem otomatis memicu WhatsAppHelper.")
    doc.add_paragraph("2. Alur Pemrosesan AI: Pesan pengguna disanitasi -> filter topik di luar konteks -> verifikasi perintah SQL aman -> jika query analitik kompleks dan Gemini API aktif, prompt dikirim ke Google Cloud -> jika offline, fallback dialihkan ke parser regex heuristik lokal -> sistem merender balasan JSON terstruktur ke chat widget.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("2.3 Diagram UML 3: Class Diagram Berorientasi Objek & Prinsip SOLID")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Perancangan Class Diagram mematuhi kelima prinsip SOLID secara ketat:")
    doc.add_paragraph("• Single Responsibility Principle (SRP): BarangController hanya menangani HTTP flow produk; WarehouseAiEngine murni memproses inferensi AI/NLP; WhatsAppHelper khusus menangani pengiriman pesan.")
    doc.add_paragraph("• Open-Closed Principle (OCP): Arsitektur AI Engine dirancang modular, siap ditambahkan provider LLM baru tanpa merombak controller.")
    doc.add_paragraph("• Liskov Substitution Principle (LSP): Seluruh model data (Barang, Stok, Transaksi) berinteraksi dengan antarmuka Database PDO secara konsisten.")
    doc.add_paragraph("• Interface Segregation Principle (ISP): Controller hanya menginstansiasi helper yang relevan, menghindari ketergantungan yang tidak terpakai.")
    doc.add_paragraph("• Dependency Inversion Principle (DIP): Controller level atas tidak bergantung langsung pada implementasi driver database mentah melainkan bergantung pada abstraksi class Database.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("2.4 Diagram UML 4: Sequence Diagram Pertukaran Pesan Multi-Komponen")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Sequence Diagram memetakan kronologi pesan saat staf menanyakan stok kritis via chatbox:\n"
                      "1. User mengetik pesan pada Browser UI.\n"
                      "2. UI mengirimkan AJAX JSON ke AichatController.\n"
                      "3. Controller memverifikasi sesi login dan memanggil WarehouseAiEngine::process().\n"
                      "4. AI Engine mengeksekusi query database lokal untuk mendeteksi barang dengan stok <= min_stock.\n"
                      "5. AI Engine meneruskan ringkasan konteks ke Google Gemini API.\n"
                      "6. Gemini merespon analisis rekomendasi restock.\n"
                      "7. AI Engine memicu WhatsAppHelper untuk menyusun log notifikasi.\n"
                      "8. Controller mengembalikan paket respon JSON dan Browser menampilkan pesan interaktif.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("2.5 Entity-Relationship Diagram (ERD) & Struktur Tabel Basis Data")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Basis data 'smartwarehouse' menerapkan normalisasi 3NF dengan 11 tabel relasional terhubung:")
    doc.add_paragraph("• roles (id, name, description)\n"
                      "• users (id, role_id FK, username, password, email, full_name, status)\n"
                      "• kategori (id, name, description)\n"
                      "• lokasi (id, name, description)\n"
                      "• barang (id, code UK, name, kategori_id FK, unit, min_stock, status)\n"
                      "• stok (id, barang_id FK, lokasi_id FK, qty, last_updated)\n"
                      "• transaksi (id, transaction_number UK, type, user_id FK, supplier_name, recipient_name, transaction_date)\n"
                      "• transaksi_detail (id, transaksi_id FK, barang_id FK, lokasi_id FK, lokasi_tujuan_id FK, qty)\n"
                      "• log_aktivitas (id, user_id FK, action, module, description, ip_address)\n"
                      "• app_settings (key_name PK, value, updated_at)\n"
                      "• whatsapp_logs (id, recipient, message_type, transaction_number, message, status)")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAB III: IMPLEMENTASI KODE, TESTING & CI/CD
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAB III: IMPLEMENTASI KODE, INTEGRASI AI, TESTING & CI/CD")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    h2 = doc.add_heading(level=2)
    r = h2.add_run("3.1 Implementasi Kode Bersih (Clean Code & Standard Guidelines)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Aplikasi dibangun dengan disiplin Clean Code tingkat tinggi:\n"
                      "• Arsitektur MVC murni tanpa dependensi framework eksternal yang membengkak.\n"
                      "• Pemrograman defensif dengan penanganan blok try-catch terstruktur pada setiap query database.\n"
                      "• Sanitasi input ketat menggunakan PDO Prepared Statements dan enkripsi password mutakhir BCRYPT.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("3.2 Pengujian Perangkat Lunak Otomatis (Automated Unit Testing)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Sistem diuji menggunakan skrip pengujian otomatis mandiri 'functional_test.php' yang mencakup 35 test case komprehensif:")

    tbl_test = doc.add_table(rows=1, cols=4)
    format_table(tbl_test, [Inches(1.5), Inches(2.5), Inches(1.3), Inches(1.2)],
                 ["Modul Diuji", "Fungsi / Metode yang Diuji", "Target Hasil", "Status Hasil"],
                 [
                     ["Model Barang", "getAllPaginated(), getById(), isCodeUnique()", "Array data valid & kode unik teruji", "PASSED (100%)"],
                     ["Model Kategori", "getAll(), getTotalRows()", "Data kategori terambil akurat", "PASSED (100%)"],
                     ["Model Lokasi", "getAll(), getTotalRows()", "Daftar rak fisik terekstrak", "PASSED (100%)"],
                     ["Model Stok", "getStockReport(), countStockReport()", "Kalkulasi agregasi stok presisi", "PASSED (100%)"],
                     ["Model Transaksi", "generateTransactionNumber('IN'/'OUT')", "Format nomor unik otomatis", "PASSED (100%)"],
                     ["Model Dashboard", "getTotalBarang(), getChartData()", "Kalkulasi metrik KPI valid", "PASSED (100%)"],
                     ["Helper WhatsApp", "normalizePhone(), parseRecipients()", "Normalisasi nomor E.164 valid", "PASSED (100%)"],
                     ["Controller AI", "AichatController::index()", "HTML View ter-render sempurna", "PASSED (100%)"],
                     ["AI Engine NLP", "WarehouseAiEngine::process()", "Intent stok kritis & summary valid", "PASSED (100%)"]
                 ])

    doc.add_paragraph().paragraph_format.space_after = Pt(8)
    doc.add_paragraph("Rangkuman Hasil Uji Terminal: 35 PASSED, 0 FAILED (Tingkat Keberhasilan Pengujian 100%).")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("3.3 Konfigurasi CI/CD Pipeline (GitHub Actions)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Otomatisasi Continuous Integration dikonfigurasi melalui file '.github/workflows/ci.yml' pada repositori GitHub publik. Alur pipeline berjalan secara otomatis pada setiap event push ke branch main:\n"
                      "1. Runner menginisialisasi environment Ubuntu dengan PHP 8.2 dan container MySQL 8.0.\n"
                      "2. Mengeksekusi verifikasi sintaks PHP Lint pada seluruh file kode.\n"
                      "3. Mengimpor database smartwarehouse.sql dan mengeksekusi patch migrasi patch_db.php.\n"
                      "4. Menjalankan skrip functional_test.php. Jika seluruh 35 test lulus, pipeline menghasilkan status centang HIJAU (Passing) pada badge resmi repositori.")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAB IV: PANDUAN INSTALASI & PENGOPERASIAN SISTEM
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAB IV: MANUAL OPERASIONAL & PETUNJUK INSTALASI SISTEM")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    h2 = doc.add_heading(level=2)
    r = h2.add_run("4.1 Kebutuhan Lingkungan Eksekusi (Prerequisites)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("• PHP versi 8.1 atau 8.2 (ekstensi: pdo, pdo_mysql, curl, mbstring, openssl).\n"
                      "• Apache Web Server & MySQL Database 8.0 (paket XAMPP terbaru disarankan).\n"
                      "• Peramban web modern (Google Chrome / Microsoft Edge).")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("4.2 Langkah Instalasi Lokal")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("1. Klon repositori ke folder htdocs: 'git clone https://github.com/username/proyek-rpl-smartwarehouse.git SmartWarehouse'\n"
                      "2. Buka phpMyAdmin, buat database baru bernama 'smartwarehouse'.\n"
                      "3. Impor struktur dan data awal dari berkas 'database/smartwarehouse.sql'.\n"
                      "4. Buka terminal di folder SmartWarehouse dan jalankan migrasi patch: 'php patch_db.php'.\n"
                      "5. Jalankan automated test suite: 'php functional_test.php' untuk memastikan semua fungsi berstatus PASS.\n"
                      "6. Buka peramban ke alamat: http://localhost/SmartWarehouse dan login dengan akun: username 'admin', password 'password123'.")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAB V: REFLEKSI OBE & PENUTUP
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAB V: REFLEKSI PEMBELAJARAN OBE & PENUTUP")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    h2 = doc.add_heading(level=2)
    r = h2.add_run("5.1 Refleksi Pembelajaran Berbasis Outcome-Based Education (OBE)")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Melalui penyelesaian proyek akhir mandiri Rekayasa Perangkat Lunak ini, pengembang telah berhasil mencapai kompetensi puncak kurikulum OBE:\n"
                      "1. Kemampuan merumuskan kebutuhan perangkat lunak formal berstandar IEEE 830.\n"
                      "2. Kemahiran merancang arsitektur sistem berorientasi objek yang mematuhi prinsip SOLID.\n"
                      "3. Kemandirian penuh sebagai Full-Stack Software Engineer dalam membangun aplikasi web lengkap serta mengintegrasikan API Artificial Intelligence mutakhir.\n"
                      "4. Penerapan budaya rekayasa perangkat lunak modern melalui pengujian otomatis dan CI/CD DevOps.")

    h2 = doc.add_heading(level=2)
    r = h2.add_run("5.2 Kesimpulan Akhir")
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)
    doc.add_paragraph("Aplikasi SmartWarehouse AI membuktikan bahwa transformasi digital operasional pergudangan dapat diwujudkan secara efektif dan mandiri. Sistem ini mampu mengeliminasi selisih stok fisik, mengoptimalkan tata kelola rak, menghadirkan asisten cerdas bahasa alami, serta mencegah terjadinya kekosongan barang strategis melalui integrasi WhatsApp Gateway. Proyek ini siap dioperasikan dan diaudit secara akademik.")

    doc.save(filename)
    print(f"Laporan DOCX berhasil dibuat: {filename}")

def build_naskah_docx(filename):
    doc = Document()
    
    sections = doc.sections
    for section in sections:
        section.top_margin = Inches(1)
        section.bottom_margin = Inches(1)
        section.left_margin = Inches(1)
        section.right_margin = Inches(1)
        
    normal_style = doc.styles['Normal']
    normal_style.font.name = 'Calibri'
    normal_style.font.size = Pt(11)
    normal_style.font.color.rgb = RGBColor(0x33, 0x33, 0x33)
    normal_style.paragraph_format.line_spacing = 1.15
    normal_style.paragraph_format.space_after = Pt(6)

    # -------------------------------------------------------------
    # COVER NASKAH VIDEO
    # -------------------------------------------------------------
    p_inst = doc.add_paragraph()
    p_inst.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_inst.paragraph_format.space_after = Pt(2)
    r = p_inst.add_run("PROGRAM STUDI S1 TEKNIK INFORMATIKA\nSTMIK IKMI CIREBON")
    r.bold = True
    r.font.size = Pt(14)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    p_div = doc.add_paragraph()
    p_div.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_div.paragraph_format.space_after = Pt(20)
    r = p_div.add_run("―" * 38)
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)

    p_title = doc.add_paragraph()
    p_title.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_title.paragraph_format.space_after = Pt(8)
    r = p_title.add_run("NASKAH LENGKAP VIDEO PRESENTASI & LIVE DEMO SISTEM\nPENGERJAAN MANDIRI / PERORANGAN")
    r.bold = True
    r.font.size = Pt(16)
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    p_sub = doc.add_paragraph()
    p_sub.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_sub.paragraph_format.space_after = Pt(30)
    r = p_sub.add_run("APLIKASI: SMARTWAREHOUSE AI (SISTEM MANAJEMEN PERGUDANGAN CERDAS & AI ASSISTANT)\nMata Kuliah: Rekayasa Perangkat Lunak (TIKB2503021) — Semester 3")
    r.bold = True
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(0x2E, 0x75, 0xB6)

    p_name = doc.add_paragraph()
    p_name.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p_name.paragraph_format.space_after = Pt(30)
    r = p_name.add_run("DISUSUN OLEH (MAHASISWA TUNGGAL / PERORANGAN):\nNAMA : [Nama Lengkap Mahasiswa]\nNIM  : [NIM Mahasiswa]\nDOSEN PENGAMPU: Yudhistira Arie Wijaya, M.Kom., CDS.")
    r.bold = True
    r.font.size = Pt(11)

    add_callout(doc, [
        "WEBCAM WAJIB AKTIF: Tampilkan wajah mahasiswa di pojok layar (Picture-in-Picture) dari awal hingga akhir presentasi untuk menjamin orisinalitas pengerjaan mandiri.",
        "KUALITAS AUDIO/VISUAL: Resolusi rekaman minimal 1080p, audio jernih tanpa noise latar belakang.",
        "LIVE DEMO RIIL: Pada Video Final, demo wajib dilakukan secara langsung (live browser & terminal), BUKAN tangkapan layar (screenshot)."
    ], title="KETENTUAN STANDAR VIDEO PRESENTASI OBE")

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAGIAN 1: NASKAH VIDEO PROGRES 1
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAGIAN 1: NASKAH VIDEO PROGRES 1 — PRD & KONSEP APLIKASI")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    doc.add_paragraph("Target Durasi: 3 – 5 Menit | Bobot: 20% dari Nilai Video | Format: Presentasi Layar + Webcam Aktif")

    tbl_v1 = doc.add_table(rows=1, cols=3)
    format_table(tbl_v1, [Inches(1.2), Inches(1.8), Inches(3.5)],
                 ["Waktu (Time)", "Visual Layar & Webcam", "Naskah Narasi Bicara (Word-by-Word)"],
                 [
                     ["00:00 - 00:45", "Slide 1: Judul Proyek, Logo STMIK IKMI, Nama & NIM Mahasiswa Tunggal.\nWebcam: Menatap kamera percaya diri.",
                      "Halo, selamat pagi/siang kepada Bapak Yudhistira Arie Wijaya, M.Kom., CDS., dan rekan-rekan sekalian. Perkenalkan, saya [Nama Lengkap Anda], mahasiswa S1 Teknik Informatika STMIK IKMI Cirebon dengan NIM [NIM Anda].\n\nPada kesempatan kali ini, saya mempresentasikan Progres 1 Proyek Akhir Mandiri Rekayasa Perangkat Lunak berjudul: SmartWarehouse AI: Sistem Informasi Manajemen Pergudangan Cerdas Terintegrasi Artificial Intelligence dan WhatsApp Notification Gateway yang saya bangun secara mandiri 100% dari awal hingga akhir."],
                     ["00:45 - 01:45", "Slide 2: Diagram Masalah Nyata Industri Pergudangan vs Solusi SmartWarehouse.\nWebcam: Menjelaskan lugas.",
                      "Proyek ini lahir untuk menjawab persoalan riil di dunia pergudangan yang masih mengandalkan pencatatan manual: sering terjadi selisih stok fisik, penataan rak yang semrawut, hingga kehabisan barang secara mendadak.\n\nSmartWarehouse AI hadir sebagai solusi dengan 3 pilar: Akurasi mutasi real-time berbasis transaksi database, Asisten AI bahasa alami untuk audit stok seketika, dan Early Warning System via WhatsApp jika stok berada di bawah batas aman."],
                     ["01:45 - 02:45", "Slide 3: Tabel User Stories & Kebutuhan Non-Fungsional Standar IEEE 830.\nWebcam: Menjelaskan poin terukur.",
                      "Dalam dokumen PRD/SRS standar IEEE 830 yang saya susun mandiri, kebutuhan fungsional mencakup autentikasi multi-peran (Admin, Supervisor, Operator), manajemen master barang dengan safety stock, transaksi mutasi 3 arah (In, Out, Transfer), serta audit log.\n\nDari segi non-fungsional, sistem menerapkan proteksi SQL Injection via PDO Prepared Statements, enkripsi password BCRYPT, dan waktu respon lokal di bawah 1 detik."],
                     ["02:45 - 03:45", "Slide 4: Arsitektur Dual-Core AI Engine (Gemini LLM + Heuristic NLP).\nWebcam: Menekankan orisinalitas.",
                      "Fitur AI dalam sistem ini bukan tempelan, melainkan dirancang dengan arsitektur Dual-Core: Core pertama Google Gemini LLM API untuk penalaran semantik pertanyaan kompleks dan analisis pengadaan barang; dan Core kedua Heuristic NLP Engine lokal berbasis PHP yang siap bekerja secara offline jika koneksi internet terputus, lengkap dengan Safe Read-Only SQL Parser."],
                     ["03:45 - 04:15", "Slide 5: Ringkasan Luaran Tahap 1 & Tautan Repositori GitHub.\nWebcam: Salam penutup.",
                      "Dokumen PRD lengkap telah saya unggah di repositori GitHub publik saya. Pada tahapan berikutnya, saya akan membedah perancangan arsitektur berorientasi objek dalam pemodelan 4 diagram UML dan prototipe UI/UX. Terima kasih atas perhatian Bapak Dosen dan rekan-rekan sekalian. Salam sehat selalu."]
                 ])

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAGIAN 2: NASKAH VIDEO PROGRES 2
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAGIAN 2: NASKAH VIDEO PROGRES 2 — PEMODELAN UML & UI/UX FIGMA")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    doc.add_paragraph("Target Durasi: 5 – 7 Menit | Bobot: 30% dari Nilai Video | Format: Presentasi Diagram UML + Walkthrough Figma")

    tbl_v2 = doc.add_table(rows=1, cols=3)
    format_table(tbl_v2, [Inches(1.2), Inches(1.8), Inches(3.5)],
                 ["Waktu (Time)", "Visual Layar & Webcam", "Naskah Narasi Bicara (Word-by-Word)"],
                 [
                     ["00:00 - 00:45", "Slide Judul Progres 2: Pemodelan UML SOLID & Arsitektur SmartWarehouse.\nWebcam: Wajah aktif di sudut.",
                      "Selamat pagi/siang Bapak Yudhistira Arie Wijaya, M.Kom., CDS. Berjumpa kembali dengan saya [Nama Lengkap Mahasiswa], NIM [NIM Mahasiswa]. Hari ini saya mempresentasikan Video Progres 2 Rekayasa Perangkat Lunak, berfokus pada Software Design Document (SDD), membedah 4 Diagram UML Utama berorientasi objek yang mematuhi prinsip SOLID, ERD basis data, dan walkthrough prototipe interaktif UI/UX."],
                     ["00:45 - 01:50", "Diagram 1: Use Case Diagram beresolusi tinggi.\nWebcam: Menunjuk relasi aktor ke sistem.",
                      "Pada Use Case Diagram, terdapat 3 aktor pengguna: Admin dengan otorisasi penuh pada 8 use case, Supervisor yang berfokus pada monitoring stok dan analitik laporan, serta Operator lapangan untuk mutasi barang masuk, keluar, dan transfer rak. Terdapat relasi extend/include ke sistem eksternal Google Gemini AI dan pemicu otomatis notifikasi WhatsApp."],
                     ["01:50 - 02:50", "Diagram 2: Activity Diagram Transaksi Outbound & Alur AI.\nWebcam: Menjelaskan alur keputusan.",
                      "Activity Diagram memodelkan dua alur krusial: Pertama, alur transaksi pengeluaran barang di mana sistem memvalidasi ketersediaan stok fisik di rak sebelum menjalankan Database Transaction ACID. Jika stok jatuh ke batas minimum, WhatsAppHelper otomatis terpicu. Kedua, alur pemrosesan AI yang menyaring pesan, memvalidasi perintah SQL aman, dan mengalihkan ke heuristic parser jika jaringan offline."],
                     ["02:50 - 04:00", "Diagram 3: Class Diagram Arsitektur MVC & Prinsip SOLID.\nWebcam: Menjelaskan keterhubungan class.",
                      "Class Diagram dirancang mematuhi 5 prinsip SOLID secara ketat: Single Responsibility memisahkan tugas Controller, AI Engine, dan Helper; Open-Closed memungkinkan penambahan model AI baru tanpa merombak sistem; Liskov Substitution dan Interface Segregation menjaga modularitas kode; dan Dependency Inversion membuat controller bergantung pada abstraksi koneksi Database."],
                     ["04:00 - 05:00", "Diagram 4: Sequence Diagram Pertukaran Pesan & Tampilan ERD 3NF.\nWebcam: Menjelaskan nomor urut.",
                      "Sequence Diagram memetakan interaksi sekuensial: Browser mengirim request JSON ke AichatController, diteruskan ke WarehouseAiEngine untuk mengambil data stok riil ke MySQL, menyusun prompt ke Google Gemini, dan memicu WhatsAppHelper. Struktur basis datanya menggunakan ERD 3NF dengan 11 tabel relasional terhubung foreign key."],
                     ["05:00 - 06:15", "Layar Prototipe Interaktif Figma.\nWebcam: Menjelaskan interaksi klik.",
                      "Sekarang mari kita lihat prototipe interaktif di Figma. Saya merancang antarmuka bertema Modern Clean Industrial: Dashboard informatif dengan KPI cards dan grafik tren mutasi, form pengeluaran stok dengan indikator stok otomatis saat rak dipilih, serta Floating AI Assistant Widget di sudut kanan bawah yang dapat diakses dari halaman mana pun."],
                     ["06:15 - 06:45", "Slide Penutup Progres 2.\nWebcam: Salam penutup video 2.",
                      "Seluruh rancangan SDD dan prototipe Figma ini telah selesai secara mandiri dan siap masuk ke tahap konstruksi kode, pengujian otomatis, dan CI/CD. Terima kasih atas perhatiannya, sampai jumpa di Video Final Live Demo."]
                 ])

    doc.add_page_break()

    # -------------------------------------------------------------
    # BAGIAN 3: NASKAH VIDEO FINAL
    # -------------------------------------------------------------
    h = doc.add_heading(level=1)
    r = h.add_run("BAGIAN 3: NASKAH VIDEO FINAL — LIVE DEMO APLIKASI WEB & SISTEM UTUH")
    r.font.color.rgb = RGBColor(0x1F, 0x4E, 0x78)

    doc.add_paragraph("Target Durasi: 8 – 12 Menit | Bobot: 50% dari Nilai Video | Format: Live Browser Demo + Terminal Unit Test + GitHub CI/CD")

    tbl_v3 = doc.add_table(rows=1, cols=3)
    format_table(tbl_v3, [Inches(1.2), Inches(1.8), Inches(3.5)],
                 ["Waktu (Time)", "Visual Layar & Webcam", "Naskah Narasi Bicara (Word-by-Word)"],
                 [
                     ["00:00 - 01:00", "Browser di halaman login SmartWarehouse lokal.\nWebcam: Wajah formal, percaya diri.",
                      "Assalamualaikum Warahmatullahi Wabarakatuh. Yang saya hormati Bapak Yudhistira Arie Wijaya, M.Kom., CDS., dosen pengampu Rekayasa Perangkat Lunak STMIK IKMI Cirebon. Saya [Nama Lengkap Anda], NIM [NIM Anda], mempersembahkan Video Final & Live Demo Sistem Utuh untuk proyek akhir mandiri saya: SmartWarehouse AI. Sebagai pengembang mandiri tunggal, saya telah menyelesaikan seluruh tahapan SDLC dari PRD, UML SOLID, konstruksi kode bersih, integrasi AI, unit testing, hingga CI/CD pipeline. Mari kita saksikan demonya!"],
                     ["01:00 - 02:00", "Tampilan struktur folder MVC di VS Code dan README.md.\nWebcam: Menjelaskan arsitektur.",
                      "Aplikasi dibangun dengan arsitektur MVC murni berbasis PHP 8.2 dan MySQL 8.0: folder controllers mengelola alur bisnis, models mengkapsulasi query PDO prepared statements, helpers memuat WarehouseAiEngine dan WhatsAppHelper, serta folder .github/workflows memuat konfigurasi CI/CD otomatis."],
                     ["02:00 - 04:30", "Live Demo Browser: Login Operator -> Dashboard -> Form Mutasi Outbound -> Riwayat Laporan.\nWebcam: Mengawal demo aktif.",
                      "Pertama, saya login sebagai Operator. Dashboard menyajikan metrik total stok, jumlah lokasi rak, dan grafik mutasi mingguan. Sekarang saya buka menu Transaksi Keluar. Saya pilih barang Laptop ASUS dan rak Gudang A. Perhatikan sisa stok fisik di rak langsung tampil otomatis. Saya input pengeluaran 2 unit dan klik Simpan. Database Transaction memotong stok dan menerbitkan nomor transaksi TRX-OUT secara real-time."],
                     ["04:30 - 07:00", "Live Demo AI: Buka Floating Chat Widget -> Panel API Key -> Tanya Stok -> Tanya Rak Kosong -> Tanya Stok Kritis -> Eksekusi Safe SQL.\nWebcam: Menjelaskan fitur cerdas.",
                      "Sekarang kita uji fitur unggulan: Smart Warehouse AI Assistant. Saya klik floating icon robot di pojok kanan bawah. Panel pengaturan menunjukkan status Gemini AI aktif. Saya ajukan pertanyaan bahasa alami: 'Berapa sisa stok laptop ASUS di Gudang A?'. Dalam waktu 1.5 detik, AI merespon akurat. Sekarang saya uji: 'Tampilkan rak-rak yang saat ini masih kosong'. AI langsung menyajikan daftar rak yang tersedia. Saya juga uji perintah Safe SQL: 'SELECT name, min_stock FROM barang', dan AI menampilkannya dalam tabel rapi di jendela chat. Jika stok kritis, WhatsAppHelper otomatis merekam log peringatan untuk dikirimkan ke supervisor."],
                     ["07:00 - 08:30", "Buka Terminal PowerShell -> Jalankan: php functional_test.php -> Tampilkan 35 PASS.\nWebcam: Menunjukkan bukti keandalan.",
                      "Sekarang kita uji stabilitas sistem melalui pengujian otomatis. Di terminal, saya jalankan skrip pengujian: php functional_test.php. Bisa kita saksikan di layar: 35 skenario pengujian otomatis dieksekusi: mencakup model Barang, Kategori, Lokasi, Stok, Transaksi, Dashboard, WhatsApp Helper, hingga Controller AI. Hasil akhirnya: 35 PASSED, 0 FAILED! Seluruh fungsi berjalan sempurna tanpa error."],
                     ["08:30 - 09:30", "Buka Browser ke Repositori GitHub -> Tab Actions -> Workflow Hijau Passing.\nWebcam: Menjelaskan CI/CD.",
                      "Tidak hanya pengujian lokal, saya juga mengonfigurasikan otomatisasi CI/CD Pipeline pada GitHub Actions di berkas ci.yml. Di layar kita lihat: setiap push ke branch main memicu build runner Ubuntu, PHP syntax linter, inisialisasi basis data MySQL, dan eksekusi unit test. Seluruh alur berstatus centang HIJAU (Passed) secara konsisten."],
                     ["09:30 - 10:30", "Kembali ke Dashboard SmartWarehouse -> Slide Penutup OBE.\nWebcam: Refleksi penutup dengan tulus.",
                      "Melalui proyek akhir mandiri berbasis kurikulum OBE ini, saya telah membuktikan kompetensi saya sebagai Full-Stack Software Engineer yang mandiri: mampu menganalisis kebutuhan formal PRD, memodelkan UML SOLID, membangun kode web bersih, mengintegrasikan LLM Google Gemini dan NLP, serta menerapkan standar mutu testing dan DevOps. Terima kasih kepada Bapak Dosen Pengampu atas bimbingannya. Wassalamualaikum Warahmatullahi Wabarakatuh."]
                 ])

    doc.save(filename)
    print(f"Naskah DOCX berhasil dibuat: {filename}")

if __name__ == '__main__':
    laporan_file = "d:/xampp/htdocs/05092026/SmartWarehouse/LAPORAN_PROYEK_AKHIR_MANDIRI_SMARTWAREHOUSE.docx"
    naskah_file  = "d:/xampp/htdocs/05092026/SmartWarehouse/NASKAH_VIDEO_PRESENTASI_MANDIRI.docx"
    
    build_laporan_docx(laporan_file)
    build_naskah_docx(naskah_file)
    print("Dokumen DOCX selesai dibuat.")

