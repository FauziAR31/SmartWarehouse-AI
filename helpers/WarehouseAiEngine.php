<?php
if (!defined('BASE_URL')) {
    @require_once __DIR__ . '/../config/config.php';
    if (!defined('BASE_URL')) {
        define('BASE_URL', '/05092026/SmartWarehouse');
    }
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Barang.php';
require_once __DIR__ . '/../models/Lokasi.php';
require_once __DIR__ . '/../models/Stok.php';
require_once __DIR__ . '/../models/Transaksi.php';
require_once __DIR__ . '/../models/LogAktivitas.php';
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../models/Laporan.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Setting.php';

class WarehouseAiEngine {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    /**
     * Check if a role is allowed to perform warehouse actions in Chatbox AI
     * All warehouse roles (Super Admin, Admin, Supervisor, Operator, Staff) are authorized
     */
    public function isAllowedRole($roleName) {
        if (!empty($_SESSION['user_id'])) {
            return true;
        }
        if (empty($roleName)) return false;
        $allowed = ['admin', 'super admin', 'supervisor', 'operator', 'staff', 'gudang'];
        return in_array(strtolower(trim($roleName)), $allowed);
    }

    /**
     * Main entry point to process a natural language query or action
     */
    public function process($message, $userContext = []) {
        $rawMessage = trim($message);
        $clean = mb_strtolower($rawMessage, 'UTF-8');

        // Detect response mode: 'count_only' or 'show_data'
        $responseMode = $this->detectResponseMode($clean);

        // 0. Filter Unrelated Queries (e.g. coding python, general programming, trivia, recipes, etc.)
        if ($this->isUnrelatedQuery($rawMessage, $clean)) {
            return $this->getUnrelatedResponse();
        }

        // 0b. Direct SQL Command Check (SELECT, SHOW, DESCRIBE, DESC, EXPLAIN)
        if ($this->isDirectSqlCommand($clean)) {
            return $this->handleDirectSqlCommand($rawMessage, $userContext);
        }

        // 1. Check Jobdesk Guide / Help
        if ($this->isJobdeskHelpIntent($clean)) {
            return $this->getJobdeskHelpResponse($userContext);
        }

        // 2. Check General Help
        if ($this->isHelpIntent($clean)) {
            return $this->getHelpResponse();
        }

        // 2b. Check WhatsApp Gateway Intent
        if ($this->isWhatsAppIntent($clean)) {
            return $this->handleWhatsAppIntent($rawMessage, $clean, $userContext);
        }

        // 3. Process with Gemini AI if configured
        if ($this->isGeminiReady()) {
            $geminiRes = $this->processWithGemini($rawMessage, $clean, $userContext, $responseMode);
            if ($geminiRes) {
                if (!empty($geminiRes['success']) || ($geminiRes['type'] ?? '') === 'unrelated_query') {
                    return $geminiRes;
                }
            }
        }

        // 3b. Check Database Intent (Rule-based Fallback for listing dbs, tables, schema, ERD, stats)
        if ($this->isDatabaseIntent($clean)) {
            return $this->handleDatabaseIntent($rawMessage, $clean, $userContext, $responseMode);
        }

        // 4. Check Warehouse Summary / Stats
        if ($this->isSummaryIntent($clean)) {
            return $this->getWarehouseSummary($responseMode);
        }

        // 4. Check Low Stock / Critical items
        if ($this->isLowStockIntent($clean)) {
            return $this->getLowStock($responseMode);
        }

        // 5. Check Empty Racks ("tampilkan rak-rak kosong", "cari rak kosong", etc.)
        if ($this->isEmptyRacksIntent($clean)) {
            return $this->handleEmptyRacksIntent($responseMode);
        }

        // 6. Check Recent Transactions / History ("5 transaksi terakhir", "riwayat mutasi", etc.)
        if ($this->isRecentTransactionsIntent($clean)) {
            return $this->handleRecentTransactionsIntent($responseMode);
        }

        // 7. Check Reports ("laporan masuk hari ini", "laporan keluar", etc.)
        if ($this->isReportIntent($clean)) {
            return $this->handleReportIntent($clean);
        }

        // 8. Check User Management ("daftar user", "siapa operator", etc.)
        if ($this->isUserManagementIntent($clean)) {
            return $this->handleUserManagementIntent($userContext, $responseMode);
        }

        // 9. Check Master Data - Tambah Barang Baru ("tambah barang baru...")
        if ($this->isCreateBarangIntent($clean)) {
            return $this->handleCreateBarangIntent($rawMessage, $clean, $userContext);
        }

        // 10. Check Master Data - Tambah Rak / Lokasi Baru ("tambah rak baru...")
        if ($this->isCreateLokasiIntent($clean)) {
            return $this->handleCreateLokasiIntent($rawMessage, $clean, $userContext);
        }

        // 11. Check Master Data - Tambah Kategori Baru ("tambah kategori baru...")
        if ($this->isCreateKategoriIntent($clean)) {
            return $this->handleCreateKategoriIntent($rawMessage, $clean, $userContext);
        }

        // 12. Check Stock Opname / Audit ("audit stok rak A.1.01", "stock opname...", etc.)
        if ($this->isStockAuditIntent($clean)) {
            return $this->handleStockAuditIntent($rawMessage, $clean, $userContext);
        }

        // 13. Check Stock Adjustment / Koreksi Stok ("sesuaikan stok... jadi X", etc.)
        if ($this->isStockAdjustmentIntent($clean)) {
            return $this->handleStockAdjustmentIntent($rawMessage, $clean, $userContext);
        }

        // 14. Check Inbound / Barang Masuk ("terima 20 unit...", "barang masuk...", etc.)
        if ($this->isInboundIntent($clean)) {
            return $this->handleInboundIntent($rawMessage, $clean, $userContext);
        }

        // 15. Check Outbound / Barang Keluar ("keluarkan 5 unit...", "kirim...", etc.)
        if ($this->isOutboundIntent($clean)) {
            return $this->handleOutboundIntent($rawMessage, $clean, $userContext);
        }

        // 16. Check Transfer / Mutasi Antar Rak ("pindahkan material...")
        if ($this->isTransferIntent($clean)) {
            return $this->handleTransferIntent($rawMessage, $clean, $userContext);
        }

        // 17. Check Rack Quantity ("ada berapakah qty pada rak ini", "isi rak A.1.01", etc.)
        if ($this->isRackQtyIntent($clean)) {
            return $this->handleRackQtyIntent($rawMessage, $clean, $responseMode);
        }

        // 18. Check Material Location ("Dimana saja lokasi material ini", "dimana letak semen", etc.)
        if ($this->isMaterialLocationIntent($clean)) {
            return $this->handleMaterialLocationIntent($rawMessage, $clean, $responseMode);
        }

        // 19. General search fallback: Check if input matches a Barang directly
        $barangMatches = $this->searchBarang($rawMessage);
        if (!empty($barangMatches)) {
            return $this->getMaterialLocationResponse($barangMatches[0]['id'], $barangMatches[0]['name'], '', '', $responseMode);
        }

        // 20. General search fallback: Check if input matches a Lokasi directly
        $lokasiMatch = $this->findLokasiExactOrLike($rawMessage);
        if ($lokasiMatch) {
            return $this->getRackQtyResponse($lokasiMatch['id'], $lokasiMatch['kode'], $responseMode);
        }

        // 21. Optional Gemini fallback
        $geminiRes = $this->tryGeminiFallback($rawMessage);
        if ($geminiRes) {
            return $geminiRes;
        }

        // 22. Default friendly smart fallback with quick action chips
        return [
            'success' => true,
            'type' => 'fallback',
            'title' => 'Bantuan Asisten SmartWarehouse',
            'message' => '<p>Maaf, saya belum memahami instruksi tersebut. Sebagai asisten gudang terpadu, saya dapat mengeksekusi semua jobdesk gudang berikut:</p>' .
                '<ul>' .
                '<li><strong>📥 Penerimaan (Inbound):</strong> <em>"Terima 20 unit Laptop ASUS di rak A.1.01 dari supplier PT Elektronik"</em></li>' .
                '<li><strong>📤 Pengeluaran (Outbound):</strong> <em>"Keluarkan 5 unit Smartphone dari rak A.1.01 untuk Toko Sentosa"</em></li>' .
                '<li><strong>🔄 Mutasi / Pindah:</strong> <em>"Pindahkan 2 unit Laptop ASUS dari A.1.01 ke A.1.02"</em></li>' .
                '<li><strong>🔍 Cek Lokasi & Rak:</strong> <em>"Dimana saja lokasi material ini?"</em> / <em>"Ada berapakah qty pada rak ini?"</em></li>' .
                '<li><strong>📋 Audit / Opname:</strong> <em>"Audit stok rak A.1.01"</em></li>' .
                '<li><strong>➕ Master Data:</strong> <em>"Tambah barang baru..."</em> / <em>"Tambah rak baru..."</em></li>' .
                '</ul>' .
                '<p class="mb-0 text-muted small">Ketik <strong>"Jobdesk"</strong> atau pilih tombol cepat di bawah untuk melihat seluruh panduan.</p>',
            'suggestions' => [
                'Panduan Jobdesk Gudang',
                'Terima 10 box Susu UHT di rak B.2.01 dari PT Indofood',
                'Keluarkan 2 box Susu UHT dari rak B.2.01 untuk Toko ABC',
                'Audit stok rak A.1.01',
                'Tampilkan rak-rak yang masih kosong'
            ]
        ];
    }

    // =========================================================================
    //  RESPONSE MODE DETECTION
    // =========================================================================

    /**
     * Detect whether user wants count-only or full data display.
     * Returns: 'count_only' | 'show_data'
     *
     * Rules:
     *  - "berapa" alone (without "tampilkan") → count_only
     *  - "tampilkan" / "lihat" / "daftar" / "list" → show_data
     *  - "berapa ... tampilkan" or "berapa dan tampilkan" → show_data
     */
    public function detectResponseMode($clean) {
        $hasShowKeyword = (bool)preg_match('/\b(tampilkan|lihat|daftar|list|tunjukkan|perlihatkan|show|detail|rincian|isi|sebutkan)\b/i', $clean);
        $hasCountKeyword = (bool)preg_match('/\b(berapa|jumlah|total|count|hitung|banyaknya)\b/i', $clean);

        if ($hasShowKeyword) {
            return 'show_data'; // explicit show always wins
        }
        if ($hasCountKeyword) {
            return 'count_only'; // only count keyword without show
        }
        return 'show_data'; // default: show full data
    }

    // =========================================================================
    //  INTENT DETECTION HELPERS
    // =========================================================================

    private function isJobdeskHelpIntent($clean) {
        return preg_match('/\b(jobdesk|tugas gudang|menu jobdesk|peran|operator|supervisor|admin gudang)\b/i', $clean) ||
               preg_match('/\b(semua perintah|daftar perintah|apa saja yang bisa|perintah warehouse)\b/i', $clean);
    }

    private function isHelpIntent($clean) {
        return preg_match('/\b(bantuan|help|panduan|fitur|menu)\b/i', $clean) &&
               !preg_match('/\b(pindah|rak|lokasi|masuk|keluar|terima|wa|whatsapp)\b/i', $clean);
    }

    private function isWhatsAppIntent($clean) {
        return preg_match('/\b(whatsapp|notifikasi wa|notif wa|gateway wa|status wa|kirim wa|uji wa|tes wa|pengaturan wa|setting wa)\b/i', $clean);
    }

    public function handleWhatsAppIntent($rawMessage, $clean, $userContext) {
        require_once __DIR__ . '/../models/Setting.php';
        require_once __DIR__ . '/WhatsAppHelper.php';
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();

        // Check if user is asking to send test message to a specific number
        if (preg_match('/\b(tes|uji|kirim|send)\b/i', $clean) && preg_match('/(08\d{8,12}|628\d{8,12})/', $rawMessage, $m)) {
            $phone = $m[1];
            $testRes = WhatsAppHelper::sendTestMessage($phone);
            $msgStatus = !empty($testRes['success']) ? 'Berhasil Terkirim' : 'Tercatat dalam Log / Menunggu Verifikasi';
            $html = '<div class="ai-success-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
            $html .= '<h6 class="fw-bold text-success mb-2"><i class="fab fa-whatsapp me-2"></i>Uji Coba Pengiriman WhatsApp</h6>';
            $html .= '<p class="small mb-2">Pesan konfirmasi telah diproses ke nomor <code>' . htmlspecialchars($phone) . '</code>.</p>';
            $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
            $html .= 'Status: <strong>' . $msgStatus . '</strong> | Provider: <code>' . htmlspecialchars(strtoupper($config['wa_provider'])) . '</code>';
            $html .= '</div>';
            if (!empty($testRes['first_link'])) {
                $html .= '<div class="text-end"><a href="' . $testRes['first_link'] . '" target="_blank" class="btn btn-xs btn-success"><i class="fab fa-whatsapp me-1"></i>Buka Pesan di WhatsApp Web</a></div>';
            }
            $html .= '</div>';
            return [
                'success' => true,
                'type' => 'wa_test',
                'title' => 'Uji Coba WhatsApp',
                'message' => $html
            ];
        }

        // Show status & configuration overview
        $isEnabled = !empty($config['wa_enabled']);
        $statusBadge = $isEnabled ? '<span class="badge bg-success">Aktif Real-time</span>' : '<span class="badge bg-secondary">Nonaktif</span>';
        $phoneDisplay = !empty($config['wa_phone']) ? htmlspecialchars($config['wa_phone']) : '<em class="text-danger">Belum dikonfigurasi</em>';
        $providerName = strtoupper($config['wa_provider']);

        $triggers = [];
        if (!empty($config['wa_notify_inbound'])) $triggers[] = 'Barang Masuk (Inbound)';
        if (!empty($config['wa_notify_outbound'])) $triggers[] = 'Barang Keluar (Outbound)';
        if (!empty($config['wa_notify_transfer'])) $triggers[] = 'Pemindahan Rak (Transfer)';
        if (!empty($config['wa_notify_adjustment'])) $triggers[] = 'Koreksi Stok (Opname)';
        if (!empty($config['wa_notify_low_stock'])) $triggers[] = 'Peringatan Stok Kritis';

        $html = '<div class="ai-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center justify-content-between mb-2 pb-2 border-bottom">';
        $html .= '<h6 class="fw-bold mb-0 text-success"><i class="fab fa-whatsapp me-2"></i>Status Notifikasi WhatsApp</h6>';
        $html .= $statusBadge;
        $html .= '</div>';
        $html .= '<p class="small text-muted mb-2">SmartWarehouse terintegrasi dengan WhatsApp Gateway untuk mengirim notifikasi real-time transaksi gudang.</p>';
        $html .= '<ul class="list-group list-group-flush small mb-3 border rounded-2">';
        $html .= '<li class="list-group-item d-flex justify-content-between"><span>Nomor Tujuan:</span> <strong>' . $phoneDisplay . '</strong></li>';
        $html .= '<li class="list-group-item d-flex justify-content-between"><span>Provider:</span> <code>' . $providerName . '</code></li>';
        $html .= '<li class="list-group-item d-flex justify-content-between"><span>Pengirim:</span> <span>' . htmlspecialchars($config['wa_sender_name']) . '</span></li>';
        $html .= '<li class="list-group-item"><span>Pemicu Notifikasi Aktif:</span> <br><small class="text-primary">' . (empty($triggers) ? 'Tidak ada pemicu aktif' : implode(', ', $triggers)) . '</small></li>';
        $html .= '</ul>';
        $html .= '<div class="d-flex justify-content-between align-items-center">';
        $html .= '<a href="' . BASE_URL . '/whatsapp/index" class="btn btn-xs btn-primary"><i class="fas fa-cog me-1"></i>Buka Pengaturan WhatsApp</a>';
        $html .= '<small class="text-muted">Ketik "kirim tes wa ke 08..."</small>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'wa_info',
            'title' => 'Status WhatsApp Gateway',
            'message' => $html,
            'suggestions' => [
                'Buka Pengaturan WhatsApp',
                '5 transaksi terakhir',
                'Ringkasan stok gudang'
            ]
        ];
    }

    private function isSummaryIntent($clean) {
        return preg_match('/\b(ringkasan|summary|statistik|total barang|total stok|kondisi gudang|status gudang)\b/i', $clean);
    }

    private function isLowStockIntent($clean) {
        return preg_match('/\b(menipis|kritis|habis|stok minim|stok minimum|stok kurang|hampir habis)\b/i', $clean);
    }

    private function isEmptyRacksIntent($clean) {
        return preg_match('/\b(rak|lokasi)\b.*\b(kosong|belum terisi|tersedia)\b/i', $clean) ||
               preg_match('/\b(cari|cek|tampilkan|daftar)\b.*\b(rak kosong|lokasi kosong)\b/i', $clean);
    }

    private function isRecentTransactionsIntent($clean) {
        return preg_match('/\b(transaksi terakhir|transaksi terbaru|riwayat transaksi|aktivitas terakhir|riwayat mutasi)\b/i', $clean);
    }

    private function isReportIntent($clean) {
        return preg_match('/\blaporan\b/i', $clean) &&
               preg_match('/\b(masuk|keluar|pemindahan|aktivitas|stok|hari ini|bulan ini)\b/i', $clean);
    }

    private function isUserManagementIntent($clean) {
        return preg_match('/\b(daftar user|manajemen user|data user|siapa operator|siapa admin|pengguna sistem)\b/i', $clean);
    }

    private function isCreateBarangIntent($clean) {
        return preg_match('/\b(tambah barang|buat barang|input barang baru|tambah produk|tambah material baru)\b/i', $clean);
    }

    private function isCreateLokasiIntent($clean) {
        return preg_match('/\b(tambah rak|buat rak|tambah lokasi|buat lokasi|tambah rak baru)\b/i', $clean);
    }

    private function isCreateKategoriIntent($clean) {
        return preg_match('/\b(tambah kategori|buat kategori|input kategori)\b/i', $clean);
    }

    private function isStockAuditIntent($clean) {
        return preg_match('/\b(audit stok|stock opname|opname rak|audit rak|periksa fisik rak)\b/i', $clean);
    }

    private function isStockAdjustmentIntent($clean) {
        return preg_match('/\b(sesuaikan stok|koreksi stok|penyesuaian stok|adjust stok|update stok fisik)\b/i', $clean);
    }

    private function isInboundIntent($clean) {
        // Examples: "terima 20 unit...", "catat barang masuk...", "barang masuk: ...", "input penerimaan", "input barang ini..."
        return preg_match('/\b(terima|penerimaan|barang masuk|masuk barang|catat masuk|input masuk|input barang|input|masukkan|taruh barang|simpan barang)\b/i', $clean) &&
               !preg_match('/\b(laporan|riwayat)\b/i', $clean);
    }

    private function isOutboundIntent($clean) {
        // Examples: "keluarkan 5 unit...", "catat barang keluar...", "kirim barang...", "pengeluaran barang"
        return preg_match('/\b(keluarkan|pengeluaran|barang keluar|keluar barang|catat keluar|kirim)\b/i', $clean) &&
               !preg_match('/\b(laporan|riwayat|pesan)\b/i', $clean);
    }

    private function isTransferIntent($clean) {
        return preg_match('/\b(pindah|pindahkan|transfer|geser|mutasi|alihkan)\b/i', $clean) &&
               !preg_match('/\b(laporan|riwayat)\b/i', $clean);
    }

    private function isRackQtyIntent($clean) {
        return preg_match('/\b(qty|jumlah|stok|isi|barang apa saja)\b.*\b(rak|lokasi|blok|gudang)\b/i', $clean) ||
               preg_match('/\b(rak|lokasi)\b.*\b(qty|jumlah|stok|isi)\b/i', $clean) ||
               preg_match('/\b(cek rak|periksa rak|isi rak|ada apa di rak)\b/i', $clean);
    }

    private function isMaterialLocationIntent($clean) {
        return preg_match('/\b(dimana|di mana|lokasi|letak|posisi|ditaruh di|disimpan di|rak mana)\b/i', $clean);
    }

    // =========================================================================
    //  JOBDESK OVERVIEW GUIDE
    // =========================================================================

    public function getJobdeskHelpResponse($userContext) {
        $roleName = $userContext['role_name'] ?? 'User';

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-primary text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">';
        $html .= '<i class="fas fa-briefcase"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-dark">Panduan Perintah Berdasarkan Jobdesk Gudang</h6>';
        $html .= '<small class="text-muted">Peran Anda saat ini: <span class="badge bg-primary">' . htmlspecialchars($roleName) . '</span></small>';
        $html .= '</div>';
        $html .= '</div>';

        // 1. Operator Jobdesk
        $html .= '<div class="mb-3">';
        $html .= '<h6 class="text-success fw-bold small mb-1"><i class="fas fa-dolly me-1"></i> 1. JOBDESK OPERATOR (Operasional Harian)</h6>';
        $html .= '<div class="small p-2 bg-light rounded-2 border">';
        $html .= '<div class="mb-1"><strong>📥 Barang Masuk:</strong> <em>"Terima 20 unit Laptop ASUS di rak A.1.01 dari PT Elektronik"</em></div>';
        $html .= '<div class="mb-1"><strong>📤 Barang Keluar:</strong> <em>"Keluarkan 5 unit Smartphone dari rak A.1.01 untuk Toko Sentosa"</em></div>';
        $html .= '<div class="mb-1"><strong>🔄 Mutasi Rak:</strong> <em>"Pindahkan 2 unit Laptop ASUS dari A.1.01 ke A.1.02"</em></div>';
        $html .= '<div class="mb-1"><strong>🔍 Cek Lokasi:</strong> <em>"Dimana saja lokasi Laptop ASUS?"</em></div>';
        $html .= '<div><strong>📦 Cek Rak & Kosong:</strong> <em>"Ada berapakah qty pada rak A.1.01?"</em> atau <em>"Tampilkan rak kosong"</em></div>';
        $html .= '</div></div>';

        // 2. Supervisor Jobdesk
        $html .= '<div class="mb-3">';
        $html .= '<h6 class="text-warning text-dark fw-bold small mb-1"><i class="fas fa-clipboard-check me-1"></i> 2. JOBDESK SUPERVISOR (Pengawasan & Audit)</h6>';
        $html .= '<div class="small p-2 bg-light rounded-2 border">';
        $html .= '<div class="mb-1"><strong>📋 Stock Opname:</strong> <em>"Audit stok rak A.1.01"</em></div>';
        $html .= '<div class="mb-1"><strong>⚖️ Koreksi Stok:</strong> <em>"Sesuaikan stok Laptop ASUS di rak A.1.01 menjadi 15 unit"</em></div>';
        $html .= '<div class="mb-1"><strong>⚠️ Stok Kritis:</strong> <em>"Tampilkan barang dengan stok kritis"</em></div>';
        $html .= '<div class="mb-1"><strong>📊 Laporan:</strong> <em>"Laporan barang masuk hari ini"</em> / <em>"Laporan pemindahan hari ini"</em></div>';
        $html .= '<div><strong>🕒 Monitoring:</strong> <em>"Tampilkan 5 transaksi terakhir"</em></div>';
        $html .= '</div></div>';

        // 3. Admin Jobdesk
        $html .= '<div>';
        $html .= '<h6 class="text-info text-dark fw-bold small mb-1"><i class="fas fa-cogs me-1"></i> 3. JOBDESK ADMIN (Master Data & Pengaturan)</h6>';
        $html .= '<div class="small p-2 bg-light rounded-2 border">';
        $html .= '<div class="mb-1"><strong>📦 Tambah Barang:</strong> <em>"Tambah barang baru: nama SSD Samsung 1TB, kode BRG-ELK-004, kategori Elektronik, satuan unit, min stok 5"</em></div>';
        $html .= '<div class="mb-1"><strong>📍 Tambah Rak:</strong> <em>"Tambah rak baru: kode D.1.01, nama Gudang D - Rak 1, kapasitas 150"</em></div>';
        $html .= '<div class="mb-1"><strong>🏷️ Tambah Kategori:</strong> <em>"Tambah kategori baru: Sparepart, deskripsi: Komponen mesin"</em></div>';
        $html .= '<div><strong>👥 Manajemen User:</strong> <em>"Tampilkan daftar user gudang"</em></div>';
        $html .= '</div></div>';

        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'jobdesk_help',
            'title' => 'Panduan Perintah Jobdesk',
            'message' => $html,
            'suggestions' => [
                'Terima 10 box Susu UHT di rak B.2.01 dari PT Indofood',
                'Keluarkan 2 box Susu UHT dari rak B.2.01 untuk Toko ABC',
                'Audit stok rak A.1.01',
                'Tampilkan rak-rak yang masih kosong',
                'Tampilkan 5 transaksi terakhir'
            ]
        ];
    }

    // =========================================================================
    //  JOBDESK 1: INBOUND / PENERIMAAN BARANG MASUK
    // =========================================================================

    private function handleInboundIntent($rawMessage, $clean, $userContext) {
        $multiRacks = $this->extractAllRackCodes($rawMessage);
        $parsed = $this->parseInboundEntities($rawMessage);

        if (count($multiRacks) > 1) {
            $parsedQty = $this->parseQty($rawMessage);
            $isTotal = (bool)preg_match('/\b(total|dibagi|bagi rata)\b/i', $clean);
            $qtyPerRack = ($isTotal && count($multiRacks) > 1) ? max(1, (int)round($parsedQty / count($multiRacks))) : $parsedQty;
            $materialTerm = $parsed['material'] ?: $this->extractMaterialTerm($rawMessage);
            if (empty($materialTerm)) $materialTerm = $this->findBarangNameInText($clean);
            return $this->handleBatchInboundMultiRack($materialTerm, $multiRacks, $qtyPerRack, $parsed['supplier'] ?: 'Supplier Umum', $parsed['notes'] ?: 'Barang masuk sekaligus via AI Assistant', $userContext);
        }

        $materialTerm = $parsed['material'];
        $rackTerm     = $parsed['rack'];
        $qty          = $parsed['qty'];
        $supplier     = $parsed['supplier'] ?: 'Supplier Umum';
        $notes        = $parsed['notes'] ?: 'Penerimaan barang masuk via AI Assistant';

        // Check template request
        if (empty($materialTerm) || empty($rackTerm) || $qty <= 0) {
            $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
            $html .= '<h6 class="fw-bold text-success mb-2"><i class="fas fa-arrow-circle-down me-2"></i>Penerimaan Barang Masuk (Inbound)</h6>';
            $html .= '<p class="small text-muted mb-2">Untuk mencatat barang masuk dari supplier ke rak gudang, gunakan format:</p>';
            $html .= '<div class="alert alert-light border small py-2 px-3 mb-3 font-monospace">';
            $html .= 'Terima [jumlah] [nama/kode material] di rak [kode rak] dari [nama supplier]';
            $html .= '</div>';
            $html .= '<p class="small fw-semibold mb-2">Contoh siap klik:</p>';
            $html .= '<div class="d-flex flex-column gap-2">';
            $html .= '<button class="btn btn-sm btn-outline-success text-start ai-quick-btn" data-query="Terima 15 unit Laptop ASUS ROG Strix di rak A.1.01 dari PT Elektronik Jaya"><i class="fas fa-plus me-1"></i>Terima 15 unit Laptop ASUS di rak A.1.01 dari PT Elektronik Jaya</button>';
            $html .= '<button class="btn btn-sm btn-outline-success text-start ai-quick-btn" data-query="Terima 50 box Susu UHT Full Cream 1 Liter di rak B.2.01 dari PT Indofood Sejahtera"><i class="fas fa-plus me-1"></i>Terima 50 box Susu UHT di rak B.2.01 dari PT Indofood Sejahtera</button>';
            $html .= '</div></div>';

            return [
                'success' => true,
                'type' => 'inbound_guide',
                'title' => 'Formulir Barang Masuk',
                'message' => $html
            ];
        }

        // Validate Material
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan.</p>" .
                    "<p class='small text-muted mb-0'>Pastikan nama atau kode barang sesuai.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }
        $barang = $barangMatches[0];

        // Validate Location
        $lokasi = $this->findLokasiExactOrLike($rackTerm);
        if (!$lokasi) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak tujuan <strong>\"" . htmlspecialchars($rackTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleRackSuggestions()
            ];
        }

        if ($lokasi['status'] === 'tidak_aktif') {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Tidak Aktif',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-ban me-1'></i> Rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> berstatus tidak aktif.</p>"
            ];
        }

        // Fetch current stock in this location
        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
        $newTotal = $currentQty + $qty;

        $payload = [
            'action_type'   => 'inbound',
            'barang_id'     => (int)$barang['id'],
            'barang_name'   => $barang['name'],
            'barang_code'   => $barang['code'],
            'lokasi_id'     => (int)$lokasi['id'],
            'lokasi_kode'   => $lokasi['kode'],
            'lokasi_nama'   => $lokasi['nama'],
            'qty'           => (int)$qty,
            'unit'          => $barang['unit'],
            'supplier_name' => $supplier,
            'notes'         => $notes,
            'current_qty'   => $currentQty,
            'new_qty'       => $newTotal
        ];

        $html = '<div class="ai-action-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-arrow-circle-down"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-success">Konfirmasi Penerimaan Barang Masuk</h6>';
        $html .= '<small class="text-muted">Jobdesk: Penerimaan & Penempatan Stok (Inbound)</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row g-2 mb-2">';
        $html .= '<div class="col-12"><div class="p-2 bg-light rounded-2 border">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Rak Penempatan</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span>';
        $html .= '<div class="small text-muted text-truncate">' . htmlspecialchars($lokasi['nama']) . '</div>';
        $html .= '<div class="small text-success mt-1">Stok: ' . $currentQty . ' &rarr; <strong>' . $newTotal . '</strong> ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Supplier</small>';
        $html .= '<strong class="text-dark small d-block text-truncate">' . htmlspecialchars($supplier) . '</strong>';
        $html .= '<small class="text-muted" style="font-size:0.75rem;">Qty: +' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-success btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Catat Barang Masuk</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Barang Masuk',
            'message' => $html,
            'data' => $payload
        ];
    }

    private function parseInboundEntities($text) {
        $clean = ' ' . mb_strtolower(trim($text), 'UTF-8') . ' ';
        $orig  = ' ' . trim($text) . ' ';

        $res = ['material' => '', 'rack' => '', 'qty' => 0, 'supplier' => '', 'notes' => ''];

        // Qty
        if (preg_match('/\b(?:terima|masuk|catat)\s+(\d+)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(?:qty|jumlah|sebanyak)\s*[:=]?\s*(\d+)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(\d+)\s*(?:unit|pcs|box|kg|rim|item|dus|sak|meter|liter)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        }

        // Rack (di rak... / ke rak... / pada rak... / di... / ke...)
        if (preg_match('/\b(?:di\s+rak|ke\s+rak|pada\s+rak|di\s+lokasi|ke\s+lokasi|di|ke)\s+([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2}|gudang\s+[A-Z]\s*-\s*rak\s*\d+)/i', $orig, $m)) {
            $res['rack'] = $this->normalizeRackCode($m[1]);
        }

        // Supplier (dari supplier... / dari PT... / dari CV...)
        if (preg_match('/\b(?:dari\s+supplier|supplier|dari\s+pt|dari\s+cv|dari)\s+([A-Z0-9\.\,\s\-\_]+?)(?:\s*(?:pada|di|sebanyak|catatan|$))/i', $orig, $m)) {
            $cand = trim($m[1]);
            if (!preg_match('/^(?:rak|lokasi|gudang)\b/i', $cand)) {
                $res['supplier'] = $cand;
            }
        }

        // Material
        if (preg_match('/(?:terima|catat\s+barang\s+masuk|barang\s+masuk)\s+(?:\d+\s*(?:unit|pcs|box|kg|rim|item)?\s+)?(.+?)\s+(?:di\s+rak|ke\s+rak|pada\s+rak|di|ke|dari)/i', $orig, $m)) {
            $res['material'] = trim($m[1]);
        }

        if (empty($res['material'])) {
            $found = $this->findBarangNameInText($clean);
            if ($found) $res['material'] = $found;
        }

        return $res;
    }

    public function executeInbound($params, $userContext) {
        $barangId = (int)($params['barang_id'] ?? 0);
        $lokasiId = (int)($params['lokasi_id'] ?? 0);
        $qty      = (int)($params['qty'] ?? 0);
        $supplier = trim($params['supplier_name'] ?? 'Supplier Umum');
        $notes    = trim($params['notes'] ?? 'Barang masuk via AI Assistant');
        $userId   = $userContext['user_id'] ?? $_SESSION['user_id'] ?? null;
        $roleName = $userContext['role_name'] ?? $_SESSION['role_name'] ?? 'Guest';

        if (!$this->isAllowedRole($roleName)) {
            return ['success' => false, 'message' => 'Role Anda tidak memiliki izin untuk mencatat barang masuk.'];
        }

        $transaksiModel = new Transaksi();
        $nomorTrx = $transaksiModel->generateTransactionNumber('IN');

        $dataTransaksi = [
            'transaction_number' => $nomorTrx,
            'user_id'            => $userId,
            'supplier_name'      => $supplier,
            'transaction_date'   => date('Y-m-d H:i:s'),
            'notes'              => $notes,
        ];

        $dataDetail = [
            'barang_id' => $barangId,
            'lokasi_id' => $lokasiId,
            'qty'       => $qty,
        ];

        $res = $transaksiModel->createTransaksiIn($dataTransaksi, $dataDetail);

        if ($res) {
            $logModel = new LogAktivitas();
            $logModel->record('BARANG_MASUK', "AI Assistant: Transaksi masuk #$nomorTrx berhasil. Qty: $qty, Supplier: $supplier", 'AI Assistant', $nomorTrx);

            $stmtS = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtS->execute([$barangId, $lokasiId]);
            $updatedQty = (int)($stmtS->fetchColumn() ?: 0);

            $html = '<div class="ai-success-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
            $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
            $html .= '<div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">';
            $html .= '<i class="fas fa-check"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<h6 class="fw-bold mb-0 text-success">Penerimaan Barang Masuk Berhasil!</h6>';
            $html .= '<small class="text-muted">No. Bukti: <strong class="font-monospace text-dark">' . htmlspecialchars($nomorTrx) . '</strong></small>';
            $html .= '</div></div>';
            $html .= '<p class="small mb-2">Sebanyak <strong class="text-success">+' . number_format($qty) . ' ' . htmlspecialchars($params['unit'] ?? '') . '</strong> ' . htmlspecialchars($params['barang_name'] ?? '') . ' telah ditempatkan di rak <strong>' . htmlspecialchars($params['lokasi_kode'] ?? '') . '</strong>.</p>';
            $html .= '<div class="p-2 bg-light rounded-2 border text-center small mb-2">';
            $html .= 'Stok Rak Sekarang: <strong class="text-primary">' . number_format($updatedQty) . ' ' . htmlspecialchars($params['unit'] ?? '') . '</strong>';
            $html .= '</div>';
            $html .= '<div class="text-end pt-1"><a href="' . BASE_URL . '/riwayat/index" class="btn btn-xs btn-outline-primary"><i class="fas fa-history me-1"></i>Lihat Riwayat</a></div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'inbound_success',
                'title' => 'Barang Masuk Berhasil',
                'message' => $html,
                'data' => [
                    'transaction_number' => $nomorTrx,
                    'qty' => $qty,
                    'lokasi_kode' => $params['lokasi_kode'] ?? ''
                ]
            ];
        }

        return ['success' => false, 'message' => 'Gagal memproses transaksi barang masuk.'];
    }

    // =========================================================================
    //  JOBDESK 2: OUTBOUND / PENGELUARAN BARANG KELUAR
    // =========================================================================

    private function handleOutboundIntent($rawMessage, $clean, $userContext) {
        $multiRacks = $this->extractAllRackCodes($rawMessage);
        $parsed = $this->parseOutboundEntities($rawMessage);

        if (count($multiRacks) > 1) {
            $parsedQty = $this->parseQty($rawMessage);
            $isTotal = (bool)preg_match('/\b(total|dibagi|bagi rata)\b/i', $clean);
            $qtyPerRack = ($isTotal && count($multiRacks) > 1) ? max(1, (int)round($parsedQty / count($multiRacks))) : $parsedQty;
            $materialTerm = $parsed['material'] ?: $this->extractMaterialTerm($rawMessage);
            if (empty($materialTerm)) $materialTerm = $this->findBarangNameInText($clean);
            return $this->handleBatchOutboundMultiRack($materialTerm, $multiRacks, $qtyPerRack, $parsed['recipient'] ?: 'Penerima Umum', $parsed['notes'] ?: 'Pengeluaran sekaligus via AI Assistant', $userContext);
        }

        $materialTerm = $parsed['material'];
        $rackTerm     = $parsed['rack'];
        $qty          = $parsed['qty'];
        $recipient    = $parsed['recipient'] ?: 'Penerima Umum';
        $notes        = $parsed['notes'] ?: 'Pengeluaran barang keluar via AI Assistant';

        if (empty($materialTerm) || empty($rackTerm) || $qty <= 0) {
            $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
            $html .= '<h6 class="fw-bold text-danger mb-2"><i class="fas fa-arrow-circle-up me-2"></i>Pengeluaran Barang Keluar (Outbound)</h6>';
            $html .= '<p class="small text-muted mb-2">Untuk mengeluarkan barang ke pelanggan / divisi penerima, gunakan format:</p>';
            $html .= '<div class="alert alert-light border small py-2 px-3 mb-3 font-monospace">';
            $html .= 'Keluarkan [jumlah] [nama/kode material] dari rak [kode rak] untuk [nama penerima]';
            $html .= '</div>';
            $html .= '<p class="small fw-semibold mb-2">Contoh siap klik:</p>';
            $html .= '<div class="d-flex flex-column gap-2">';
            $html .= '<button class="btn btn-sm btn-outline-danger text-start ai-quick-btn" data-query="Keluarkan 2 unit Laptop ASUS ROG Strix dari rak A.1.01 untuk Toko Sentosa Jaya"><i class="fas fa-minus me-1"></i>Keluarkan 2 unit Laptop ASUS dari rak A.1.01 untuk Toko Sentosa Jaya</button>';
            $html .= '<button class="btn btn-sm btn-outline-danger text-start ai-quick-btn" data-query="Keluarkan 5 box Susu UHT Full Cream 1 Liter dari rak B.2.01 untuk Supermarket Indah"><i class="fas fa-minus me-1"></i>Keluarkan 5 box Susu UHT dari rak B.2.01 untuk Supermarket Indah</button>';
            $html .= '</div></div>';

            return [
                'success' => true,
                'type' => 'outbound_guide',
                'title' => 'Formulir Barang Keluar',
                'message' => $html
            ];
        }

        // Validate Material
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }
        $barang = $barangMatches[0];

        // Validate Location
        $lokasi = $this->findLokasiExactOrLike($rackTerm);
        if (!$lokasi) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Asal Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak asal <strong>\"" . htmlspecialchars($rackTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleRackSuggestions()
            ];
        }

        // Check stock availability
        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentStock = (int)($stmtCurrent->fetchColumn() ?: 0);

        if ($currentStock <= 0) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Habis di Rak Ini',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-times-circle me-1'></i> Material <strong>" . htmlspecialchars($barang['name']) . "</strong> tidak memiliki stok di rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong>.</p>"
            ];
        }

        if ($currentStock < $qty) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Kurang',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-exclamation-triangle me-1'></i> Stok di rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> hanya tersisa <strong>" . $currentStock . " " . htmlspecialchars($barang['unit']) . "</strong>, tidak mencukupi untuk permintaan " . $qty . " " . htmlspecialchars($barang['unit']) . ".</p>"
            ];
        }

        $payload = [
            'action_type'    => 'outbound',
            'barang_id'      => (int)$barang['id'],
            'barang_name'    => $barang['name'],
            'barang_code'    => $barang['code'],
            'lokasi_id'      => (int)$lokasi['id'],
            'lokasi_kode'    => $lokasi['kode'],
            'lokasi_nama'    => $lokasi['nama'],
            'qty'            => (int)$qty,
            'unit'           => $barang['unit'],
            'recipient_name' => $recipient,
            'notes'          => $notes,
            'current_qty'    => $currentStock,
            'remaining_qty'  => $currentStock - $qty
        ];

        $html = '<div class="ai-action-card border border-danger-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-danger text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-arrow-circle-up"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-danger">Konfirmasi Pengeluaran Barang Keluar</h6>';
        $html .= '<small class="text-muted">Jobdesk: Pengeluaran & Pengiriman (Outbound)</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row g-2 mb-2">';
        $html .= '<div class="col-12"><div class="p-2 bg-light rounded-2 border">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Rak Asal</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span>';
        $html .= '<div class="small text-muted text-truncate">' . htmlspecialchars($lokasi['nama']) . '</div>';
        $html .= '<div class="small text-danger mt-1">Sisa: ' . ($currentStock - $qty) . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Penerima / Customer</small>';
        $html .= '<strong class="text-dark small d-block text-truncate">' . htmlspecialchars($recipient) . '</strong>';
        $html .= '<small class="text-danger" style="font-size:0.75rem;">Keluar: -' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-danger btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Keluarkan Barang</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Barang Keluar',
            'message' => $html,
            'data' => $payload
        ];
    }

    private function parseOutboundEntities($text) {
        $clean = ' ' . mb_strtolower(trim($text), 'UTF-8') . ' ';
        $orig  = ' ' . trim($text) . ' ';

        $res = ['material' => '', 'rack' => '', 'qty' => 0, 'recipient' => '', 'notes' => ''];

        // Qty
        if (preg_match('/\b(?:keluarkan|kirim|catat)\s+(\d+)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(?:qty|jumlah|sebanyak)\s*[:=]?\s*(\d+)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(\d+)\s*(?:unit|pcs|box|kg|rim|item|dus|sak|meter|liter)\b/i', $clean, $m)) {
            $res['qty'] = (int)$m[1];
        }

        // Rack (dari rak... / pada rak... / dari... / di rak... / di...)
        if (preg_match('/\b(?:dari\s+rak|pada\s+rak|dari\s+lokasi|dari|di\s+rak|di)\s+([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2}|gudang\s+[A-Z]\s*-\s*rak\s*\d+)/i', $orig, $m)) {
            $res['rack'] = $this->normalizeRackCode($m[1]);
        }

        // Recipient (untuk... / penerima... / kepada...)
        if (preg_match('/\b(?:untuk|penerima|tujuan|kepada|ke)\s+([A-Z0-9\.\,\s\-\_]+?)(?:\s*(?:dari|sebanyak|catatan|$))/i', $orig, $m)) {
            $cand = trim($m[1]);
            if (!preg_match('/^(?:rak|lokasi|gudang)\b/i', $cand)) {
                $res['recipient'] = $cand;
            }
        }

        // Material
        if (preg_match('/(?:keluarkan|kirim|catat\s+barang\s+keluar|barang\s+keluar)\s+(?:\d+\s*(?:unit|pcs|box|kg|rim|item)?\s+)?(.+?)\s+(?:dari\s+rak|dari|di|untuk)/i', $orig, $m)) {
            $res['material'] = trim($m[1]);
        }

        if (empty($res['material'])) {
            $found = $this->findBarangNameInText($clean);
            if ($found) $res['material'] = $found;
        }

        return $res;
    }

    public function executeOutbound($params, $userContext) {
        $barangId  = (int)($params['barang_id'] ?? 0);
        $lokasiId  = (int)($params['lokasi_id'] ?? 0);
        $qty       = (int)($params['qty'] ?? 0);
        $recipient = trim($params['recipient_name'] ?? 'Penerima Umum');
        $notes     = trim($params['notes'] ?? 'Barang keluar via AI Assistant');
        $userId    = $userContext['user_id'] ?? $_SESSION['user_id'] ?? null;
        $roleName  = $userContext['role_name'] ?? $_SESSION['role_name'] ?? 'Guest';

        if (!$this->isAllowedRole($roleName)) {
            return ['success' => false, 'message' => 'Role Anda tidak memiliki izin untuk mencatat pengeluaran barang.'];
        }

        $transaksiModel = new Transaksi();
        $nomorTrx = $transaksiModel->generateTransactionNumber('OUT');

        $dataTransaksi = [
            'transaction_number' => $nomorTrx,
            'user_id'            => $userId,
            'recipient_name'     => $recipient,
            'transaction_date'   => date('Y-m-d H:i:s'),
            'notes'              => $notes,
        ];

        $dataDetail = [
            'barang_id' => $barangId,
            'lokasi_id' => $lokasiId,
            'qty'       => $qty,
        ];

        $res = $transaksiModel->createTransaksiOut($dataTransaksi, $dataDetail);

        if ($res['success']) {
            $logModel = new LogAktivitas();
            $logModel->record('BARANG_KELUAR', "AI Assistant: Transaksi keluar #$nomorTrx berhasil. Qty: $qty, Penerima: $recipient", 'AI Assistant', $nomorTrx);

            $stmtS = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtS->execute([$barangId, $lokasiId]);
            $updatedQty = (int)($stmtS->fetchColumn() ?: 0);

            $html = '<div class="ai-success-card border border-danger-subtle rounded-3 p-3 bg-white shadow-sm">';
            $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
            $html .= '<div class="bg-danger text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">';
            $html .= '<i class="fas fa-check"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<h6 class="fw-bold mb-0 text-danger">Pengeluaran Barang Berhasil!</h6>';
            $html .= '<small class="text-muted">No. Bukti: <strong class="font-monospace text-dark">' . htmlspecialchars($nomorTrx) . '</strong></small>';
            $html .= '</div></div>';
            $html .= '<p class="small mb-2">Sebanyak <strong class="text-danger">-' . number_format($qty) . ' ' . htmlspecialchars($params['unit'] ?? '') . '</strong> ' . htmlspecialchars($params['barang_name'] ?? '') . ' telah dikeluarkan untuk <strong>' . htmlspecialchars($recipient) . '</strong>.</p>';
            $html .= '<div class="p-2 bg-light rounded-2 border text-center small mb-2">';
            $html .= 'Sisa Stok Rak: <strong class="text-primary">' . number_format($updatedQty) . ' ' . htmlspecialchars($params['unit'] ?? '') . '</strong>';
            $html .= '</div>';
            $html .= '<div class="text-end pt-1"><a href="' . BASE_URL . '/riwayat/index" class="btn btn-xs btn-outline-danger"><i class="fas fa-history me-1"></i>Lihat Riwayat</a></div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'outbound_success',
                'title' => 'Barang Keluar Berhasil',
                'message' => $html,
                'data' => [
                    'transaction_number' => $nomorTrx,
                    'qty' => $qty,
                    'lokasi_kode' => $params['lokasi_kode'] ?? ''
                ]
            ];
        }

        return ['success' => false, 'message' => 'Gagal: ' . ($res['message'] ?? 'Stok tidak mencukupi')];
    }

    // =========================================================================
    //  JOBDESK 3: STOCK AUDIT / OPNAME (SUPERVISOR)
    // =========================================================================

    private function handleStockAuditIntent($rawMessage, $clean, $userContext) {
        $rackCode = $this->extractRackCode($rawMessage);

        if (empty($rackCode)) {
            $lokasi = $this->findLokasiByKeyword($clean);
            if ($lokasi) $rackCode = $lokasi['kode'];
        }

        if (empty($rackCode)) {
            return $this->getRackSelectionPrompt('Pilih atau sebutkan rak yang ingin diaudit fisik (Stock Opname):');
        }

        $stmt = $this->db->prepare("SELECT * FROM lokasi WHERE kode = :kode LIMIT 1");
        $stmt->execute([':kode' => strtoupper($rackCode)]);
        $lokasi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lokasi) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Tidak Ditemukan', 'message' => "Rak $rackCode tidak ditemukan."];
        }

        $lokasiModel = new Lokasi();
        $items = $lokasiModel->getItemsByLokasiId($lokasi['id']);

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div>';
        $html .= '<span class="badge bg-warning text-dark me-2"><i class="fas fa-clipboard-check me-1"></i>STOCK OPNAME</span>';
        $html .= '<h6 class="fw-bold mb-0 text-dark d-inline">Rak: ' . htmlspecialchars($lokasi['kode']) . '</h6>';
        $html .= '<div class="small text-muted">' . htmlspecialchars($lokasi['nama']) . '</div>';
        $html .= '</div>';
        $html .= '<div class="text-end">';
        $html .= '<span class="badge bg-light text-dark border">Kapasitas: ' . ($lokasi['kapasitas'] ?? 100) . '</span>';
        $html .= '</div></div>';

        if (empty($items)) {
            $html .= '<div class="alert alert-info py-2 px-3 small mb-0"><i class="fas fa-check-circle me-1"></i> Rak ini tercatat <strong>KOSONG</strong> pada sistem.</div>';
        } else {
            $html .= '<p class="small text-muted mb-2">Bandingkan hitungan fisik lapangan dengan saldo sistem berikut:</p>';
            $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small align-middle mb-2">';
            $html .= '<thead class="table-light"><tr><th>Kode</th><th>Material</th><th class="text-end">Stok Sistem</th><th class="text-center">Aksi Opname</th></tr></thead><tbody>';

            foreach ($items as $it) {
                $html .= '<tr>';
                $html .= '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($it['code']) . '</span></td>';
                $html .= '<td><strong>' . htmlspecialchars($it['name']) . '</strong></td>';
                $html .= '<td class="text-end fw-bold text-primary">' . number_format($it['qty']) . ' ' . htmlspecialchars($it['unit']) . '</td>';
                $html .= '<td class="text-center">';
                $adjustPrompt = "Sesuaikan stok {$it['name']} di rak {$lokasi['kode']} menjadi ";
                $html .= '<button class="btn btn-xs btn-outline-warning py-0 px-2 ai-quick-btn" data-query="' . htmlspecialchars($adjustPrompt) . '" title="Koreksi stok barang ini"><i class="fas fa-edit me-1"></i>Koreksi</button>';
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }

        $html .= '<div class="small text-muted mt-2 pt-2 border-top">';
        $html .= '<i class="fas fa-info-circle me-1"></i>Jika ada selisih fisik, ketik: <em>"Sesuaikan stok [Nama Barang] di rak ' . htmlspecialchars($lokasi['kode']) . ' menjadi [Qty Baru]"</em>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'stock_audit',
            'title' => 'Audit Rak: ' . $lokasi['kode'],
            'message' => $html
        ];
    }

    // =========================================================================
    //  JOBDESK 4: STOCK ADJUSTMENT / KOREKSI STOK (SUPERVISOR / ADMIN)
    // =========================================================================

    private function handleStockAdjustmentIntent($rawMessage, $clean, $userContext) {
        $roleName = $userContext['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'type' => 'permission_denied',
                'title' => 'Hak Akses Dibatasi',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-lock me-1"></i> Penyesuaian stok (Stock Adjustment / Opname) hanya dapat dilakukan oleh role berwenang. Akun Anda saat ini: <em>' . htmlspecialchars($roleName) . '</em>.</div>'
            ];
        }

        // Pattern: "sesuaikan stok [barang] di rak [rak] menjadi [qty]"
        $rackCode = $this->extractRackCode($rawMessage);
        $newQty = -1;
        if (preg_match('/\b(?:menjadi|jadi|ke)\s+(\d+)\b/i', $clean, $m)) {
            $newQty = (int)$m[1];
        }

        $materialTerm = '';
        if (preg_match('/(?:sesuaikan|koreksi|adjust)\s+stok\s+(.+?)\s+(?:di\s+rak|pada\s+rak|menjadi|jadi)/i', $rawMessage, $m)) {
            $materialTerm = trim($m[1]);
        }
        if (empty($materialTerm)) {
            $materialTerm = $this->findBarangNameInText($clean);
        }

        if (empty($materialTerm) || empty($rackCode) || $newQty < 0) {
            $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
            $html .= '<h6 class="fw-bold text-warning mb-2"><i class="fas fa-balance-scale me-2"></i>Penyesuaian Stok (Stock Adjustment)</h6>';
            $html .= '<p class="small text-muted mb-2">Gunakan format ini untuk menyesuaikan saldo stok sistem dengan hasil hitungan fisik:</p>';
            $html .= '<div class="alert alert-light border small py-2 px-3 mb-2 font-monospace">';
            $html .= 'Sesuaikan stok [Nama Material] di rak [Kode Rak] menjadi [Qty Baru]';
            $html .= '</div>';
            $html .= '<small class="text-muted">Contoh: <em>"Sesuaikan stok Laptop ASUS ROG Strix di rak A.1.01 menjadi 12"</em></small>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'adjust_guide',
                'title' => 'Format Penyesuaian Stok',
                'message' => $html
            ];
        }

        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return ['success' => false, 'type' => 'error', 'title' => 'Material Tidak Ditemukan', 'message' => "Material $materialTerm tidak ditemukan."];
        }
        $barang = $barangMatches[0];

        $lokasi = $this->findLokasiExactOrLike($rackCode);
        if (!$lokasi) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Tidak Ditemukan', 'message' => "Rak $rackCode tidak ditemukan."];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
        $diff = $newQty - $currentQty;

        $payload = [
            'action_type' => 'stock_adjustment',
            'barang_id'   => (int)$barang['id'],
            'barang_name' => $barang['name'],
            'barang_code' => $barang['code'],
            'lokasi_id'   => (int)$lokasi['id'],
            'lokasi_kode' => $lokasi['kode'],
            'old_qty'     => $currentQty,
            'new_qty'     => $newQty,
            'unit'        => $barang['unit'],
            'diff'        => $diff
        ];

        $diffText = ($diff > 0) ? "<span class='text-success fw-bold'>+$diff</span>" : "<span class='text-danger fw-bold'>$diff</span>";

        $html = '<div class="ai-action-card border border-warning-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-edit text-warning me-2"></i>Konfirmasi Penyesuaian Stok</h6>';
        $html .= '<p class="small text-muted mb-2">Periksa perubahan saldo stok fisik rak berikut:</p>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Material:</strong> [' . htmlspecialchars($barang['code']) . '] ' . htmlspecialchars($barang['name']) . '</div>';
        $html .= '<div><strong>Rak:</strong> ' . htmlspecialchars($lokasi['kode']) . ' (' . htmlspecialchars($lokasi['nama']) . ')</div>';
        $html .= '<div><strong>Stok Saat Ini:</strong> ' . $currentQty . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '<div><strong>Stok Baru (Hasil Opname):</strong> ' . $newQty . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '<div><strong>Selisih Penyesuaian:</strong> ' . $diffText . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-warning btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Simpan Koreksi Stok</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Penyesuaian Stok',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function executeStockAdjustment($params, $userContext) {
        $barangId = (int)($params['barang_id'] ?? 0);
        $lokasiId = (int)($params['lokasi_id'] ?? 0);
        $userId   = $userContext['user_id'] ?? $_SESSION['user_id'] ?? null;

        $stmtB = $this->db->prepare("SELECT name, unit FROM barang WHERE id = ?");
        $stmtB->execute([$barangId]);
        $bRow = $stmtB->fetch(PDO::FETCH_ASSOC);
        $barangName = $params['barang_name'] ?? ($bRow['name'] ?? 'Material');
        $unit       = $params['unit'] ?? ($bRow['unit'] ?? 'unit');

        $stmtL = $this->db->prepare("SELECT kode FROM lokasi WHERE id = ?");
        $stmtL->execute([$lokasiId]);
        $lokasiKode = $params['lokasi_kode'] ?? ($stmtL->fetchColumn() ?: 'Rak');

        // Check if record exists
        $stmtCheck = $this->db->prepare("SELECT id, qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCheck->execute([$barangId, $lokasiId]);
        $stokRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        $stokId  = $stokRow['id'] ?? null;
        $currentOldQty = isset($stokRow['qty']) ? (int)$stokRow['qty'] : 0;

        $newQty = isset($params['new_qty']) ? (int)$params['new_qty'] : (isset($params['qty_fisik']) ? (int)$params['qty_fisik'] : (isset($params['qty']) ? (int)$params['qty'] : $currentOldQty));
        $oldQty = isset($params['old_qty']) ? (int)$params['old_qty'] : $currentOldQty;
        $diff   = isset($params['diff']) ? (int)$params['diff'] : ($newQty - $oldQty);

        if ($stokId) {
            $stmtUp = $this->db->prepare("UPDATE stok SET qty = :qty WHERE id = :id");
            $stmtUp->execute([':qty' => $newQty, ':id' => $stokId]);
        } else {
            $stmtIn = $this->db->prepare("INSERT INTO stok (barang_id, lokasi_id, qty) VALUES (?, ?, ?)");
            $stmtIn->execute([$barangId, $lokasiId, $newQty]);
        }

        // Update location status
        $stmtTot = $this->db->prepare("SELECT SUM(qty) FROM stok WHERE lokasi_id = ?");
        $stmtTot->execute([$lokasiId]);
        $tot = (int)($stmtTot->fetchColumn() ?: 0);
        $stat = ($tot > 0) ? 'terisi' : 'kosong';
        $stmtLoc = $this->db->prepare("UPDATE lokasi SET status = ? WHERE id = ? AND status != 'tidak_aktif'");
        $stmtLoc->execute([$stat, $lokasiId]);

        $logModel = new LogAktivitas();
        $logModel->record('STOCK_ADJUSTMENT', "AI Assistant: Koreksi stok $barangName di rak $lokasiKode dari $oldQty menjadi $newQty (Selisih: $diff)", 'Inventory', "Rak $lokasiKode");

        try {
            require_once __DIR__ . '/WhatsAppHelper.php';
            $operatorName = $userContext['full_name'] ?? $_SESSION['full_name'] ?? 'AI Assistant';
            WhatsAppHelper::notifyStockAdjustment($barangName, '', $lokasiKode, $oldQty, $newQty, "Koreksi stok via AI Assistant", $operatorName);
        } catch (Throwable $e) {}

        $html = '<div class="ai-success-card border border-warning-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-success mb-1"><i class="fas fa-check-circle me-1"></i>Penyesuaian Stok Berhasil Disimpan!</h6>';
        $html .= '<p class="small text-muted mb-2">Saldo material <strong>' . htmlspecialchars($barangName) . '</strong> di rak <strong>' . htmlspecialchars($lokasiKode) . '</strong> kini diperbarui menjadi <strong>' . number_format($newQty) . ' ' . htmlspecialchars($unit) . '</strong>.</p>';
        $html .= '<div class="text-end"><a href="' . BASE_URL . '/stok/index" class="btn btn-xs btn-outline-primary">Lihat Data Stok</a></div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'adjust_success',
            'title' => 'Penyesuaian Stok Selesai',
            'message' => $html
        ];
    }

    // =========================================================================
    //  JOBDESK 5: EMPTY RACKS FINDER (OPERATOR / SUPERVISOR)
    // =========================================================================

    public function handleEmptyRacksIntent($responseMode = 'show_data') {
        $sql = "SELECT l.id, l.kode, l.nama, l.rack, l.level, l.posisi, l.kapasitas 
                FROM lokasi l 
                LEFT JOIN stok s ON s.lokasi_id = l.id 
                WHERE l.status != 'tidak_aktif' 
                GROUP BY l.id 
                HAVING COALESCE(SUM(s.qty), 0) = 0 
                ORDER BY l.rack, l.level, l.posisi ASC";
        $stmt = $this->db->query($sql);
        $emptyRacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($emptyRacks)) {
            return [
                'success' => true,
                'type' => 'empty_racks',
                'title' => 'Ketersediaan Rak Gudang',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Semua rak gudang saat ini berstatus <strong>TERISI</strong>. Tidak ada rak yang kosong.</div>'
            ];
        }

        // ── COUNT ONLY MODE ──────────────────────────────────────────────────
        // Jika user hanya bertanya "berapa" (tanpa "tampilkan"), kembalikan
        // jumlah angka rak kosong saja.
        if ($responseMode === 'count_only') {
            $jumlah = count($emptyRacks);
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-boxes fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">Jumlah rak kosong siap pakai</div>';
            $html .= '<div class="fs-3 fw-bold text-success lh-1">' . $jumlah . ' <small class="fs-6 text-muted fw-normal">rak</small></div>';
            $html .= '<div class="small text-muted mt-1">siap digunakan untuk penempatan barang</div>';
            $html .= '</div></div>';
            $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan rak yang kosong&quot;</em> untuk melihat daftar lengkapnya.</div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type'    => 'empty_racks_count',
                'title'   => 'Jumlah Rak Kosong',
                'message' => $html,
                'suggestions' => [
                    'Tampilkan rak yang kosong',
                    'Ringkasan gudang',
                    'Barang stok kritis'
                ]
            ];
        }

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<h6 class="fw-bold mb-0 text-success"><i class="fas fa-boxes me-2"></i>Daftar Rak Kosong Siap Pakai (' . count($emptyRacks) . ')</h6>';
        $html .= '</div>';
        $html .= '<p class="small text-muted mb-2">Rak-rak berikut belum memiliki muatan dan siap digunakan untuk penempatan barang masuk atau tujuan pemindahan:</p>';

        $html .= '<div class="d-flex flex-wrap gap-2">';
        foreach ($emptyRacks as $r) {
            $html .= '<div class="p-2 bg-light border rounded-2 small text-center" style="min-width: 100px;">';
            $html .= '<span class="badge bg-dark font-monospace d-block mb-1">' . htmlspecialchars($r['kode']) . '</span>';
            $html .= '<div class="text-truncate" style="max-width: 110px; font-size: 0.72rem;">' . htmlspecialchars($r['nama']) . '</div>';
            $html .= '<small class="text-muted d-block" style="font-size:0.68rem;">Kapasitas: ' . ($r['kapasitas'] ?? 100) . '</small>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'empty_racks',
            'title' => 'Rak Gudang Kosong',
            'message' => $html,
            'suggestions' => [
                'Terima barang di rak ' . $emptyRacks[0]['kode'],
                'Audit stok rak ' . $emptyRacks[0]['kode'],
                'Ringkasan status gudang'
            ]
        ];
    }

    // =========================================================================
    //  JOBDESK 6: MASTER DATA - CREATE BARANG (ADMIN / SUPERVISOR)
    // =========================================================================

    private function handleCreateBarangIntent($rawMessage, $clean, $userContext) {
        $roleName = $userContext['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'type' => 'permission_denied',
                'title' => 'Hak Akses Dibatasi',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-lock me-1"></i> Menambah Master Data Barang hanya diizinkan untuk role berwenang.</div>'
            ];
        }

        // Parse: name, code, kategori, unit, min_stock
        $parsed = [
            'name'      => '',
            'code'      => '',
            'kategori'  => '',
            'unit'      => 'pcs',
            'min_stock' => 10
        ];

        if (preg_match('/nama\s*[:=]?\s*([^,\;]+)/i', $rawMessage, $m)) $parsed['name'] = trim($m[1]);
        if (preg_match('/kode\s*[:=]?\s*([A-Z0-9\-\_]+)/i', $rawMessage, $m)) $parsed['code'] = strtoupper(trim($m[1]));
        if (preg_match('/kategori\s*[:=]?\s*([^,\;]+)/i', $rawMessage, $m)) $parsed['kategori'] = trim($m[1]);
        if (preg_match('/satuan\s*[:=]?\s*([a-zA-Z]+)/i', $rawMessage, $m)) $parsed['unit'] = strtolower(trim($m[1]));
        if (preg_match('/(?:min stok|min_stock|minimum)\s*[:=]?\s*(\d+)/i', $rawMessage, $m)) $parsed['min_stock'] = (int)$m[1];

        if (empty($parsed['name'])) {
            $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
            $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-box-open me-2"></i>Tambah Master Barang Baru</h6>';
            $html .= '<p class="small text-muted mb-2">Gunakan format terstruktur berikut untuk menambahkan katalog produk baru:</p>';
            $html .= '<div class="alert alert-light border small py-2 px-3 mb-2 font-monospace">';
            $html .= 'Tambah barang baru: nama [Nama Barang], kode [Kode Barang], kategori [Kategori], satuan [unit/pcs/box/dll], min stok [Angka]';
            $html .= '</div>';
            $html .= '<small class="text-muted">Contoh siap klik:</small>';
            $html .= '<div class="mt-2"><button class="btn btn-sm btn-outline-primary ai-quick-btn" data-query="Tambah barang baru: nama Keyboard Mechanical RGB, kode BRG-ELK-006, kategori Elektronik, satuan pcs, min stok 10"><i class="fas fa-plus me-1"></i>Tambah Keyboard Mechanical RGB (BRG-ELK-006)</button></div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'create_barang_guide',
                'title' => 'Formulir Tambah Barang',
                'message' => $html
            ];
        }

        // Auto-generate code if missing
        if (empty($parsed['code'])) {
            $maxId = (int)$this->db->query("SELECT MAX(id) FROM barang")->fetchColumn() + 1;
            $parsed['code'] = 'BRG-GEN-' . str_pad($maxId, 3, '0', STR_PAD_LEFT);
        }

        // Check unique code
        $stmtCheck = $this->db->prepare("SELECT id FROM barang WHERE code = ?");
        $stmtCheck->execute([$parsed['code']]);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'type' => 'error', 'title' => 'Kode Duplikat', 'message' => "Kode barang {$parsed['code']} sudah digunakan oleh produk lain."];
        }

        // Resolve category
        $kategoriId = 1;
        if (!empty($parsed['kategori'])) {
            $stmtK = $this->db->prepare("SELECT id FROM kategori WHERE name LIKE ? LIMIT 1");
            $stmtK->execute(['%' . $parsed['kategori'] . '%']);
            $kId = $stmtK->fetchColumn();
            if ($kId) $kategoriId = $kId;
        }

        $payload = [
            'action_type' => 'create_barang',
            'code'        => $parsed['code'],
            'name'        => $parsed['name'],
            'kategori_id' => $kategoriId,
            'kategori'    => $parsed['kategori'] ?: 'Umum',
            'unit'        => $parsed['unit'],
            'min_stock'   => $parsed['min_stock']
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-2"></i>Konfirmasi Tambah Master Barang</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Kode:</strong> <span class="badge bg-dark font-monospace">' . htmlspecialchars($payload['code']) . '</span></div>';
        $html .= '<div><strong>Nama:</strong> ' . htmlspecialchars($payload['name']) . '</div>';
        $html .= '<div><strong>Kategori:</strong> ' . htmlspecialchars($payload['kategori']) . '</div>';
        $html .= '<div><strong>Satuan:</strong> ' . htmlspecialchars($payload['unit']) . '</div>';
        $html .= '<div><strong>Minimum Stok:</strong> ' . $payload['min_stock'] . '</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Simpan Barang</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Tambah Barang',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function executeCreateBarang($params, $userContext) {
        $barangModel = new Barang();

        $kategoriId = $params['kategori_id'] ?? null;
        if (!$kategoriId && !empty($params['kategori_name'])) {
            $stmt = $this->db->prepare("SELECT id FROM kategori WHERE name = ? LIMIT 1");
            $stmt->execute([trim($params['kategori_name'])]);
            $kategoriId = $stmt->fetchColumn() ?: null;
        }
        if (!$kategoriId) {
            $kategoriId = $this->db->query("SELECT id FROM kategori LIMIT 1")->fetchColumn();
            if (!$kategoriId) {
                $this->db->exec("INSERT INTO kategori (name, description) VALUES ('Umum', 'Kategori Umum Default')");
                $kategoriId = $this->db->lastInsertId();
            }
        }

        $data = [
            'code'        => $params['code'] ?? ('BRG-' . strtoupper(substr(uniqid(), -6))),
            'name'        => $params['name'] ?? 'Barang Tanpa Nama',
            'kategori_id' => $kategoriId,
            'description' => 'Ditambahkan melalui AI Assistant',
            'unit'        => !empty($params['unit']) ? $params['unit'] : 'pcs',
            'min_stock'   => isset($params['min_stock']) ? (int)$params['min_stock'] : 10,
            'status'      => 'active'
        ];

        try {
            $existing = $barangModel->getByCode($data['code']);
            if ($existing) {
                return ['success' => false, 'message' => "Kode barang '{$data['code']}' sudah ada di database."];
            }

            $res = $barangModel->create($data);
            if ($res) {
                $logModel = new LogAktivitas();
                $logModel->record('TAMBAH_BARANG', "AI Assistant: Berhasil menambahkan master barang [{$params['code']}] {$params['name']}", 'Master Data', $params['code']);

                $html = '<div class="ai-success-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
                $html .= '<h6 class="fw-bold text-success mb-1"><i class="fas fa-check-circle me-1"></i>Master Barang Berhasil Ditambahkan!</h6>';
                $html .= '<p class="small text-muted mb-2">Produk <strong>[' . htmlspecialchars($data['code']) . '] ' . htmlspecialchars($data['name']) . '</strong> telah aktif dalam katalog.</p>';
                $html .= '<div class="text-end"><a href="' . BASE_URL . '/barang/index" class="btn btn-xs btn-outline-primary">Lihat Daftar Barang</a></div>';
                $html .= '</div>';

                return [
                    'success' => true,
                    'type' => 'create_barang_success',
                    'title' => 'Barang Tersimpan',
                    'message' => $html
                ];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Gagal menyimpan barang: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Gagal menyimpan data barang baru.'];
    }

    // =========================================================================
    //  JOBDESK 7: MASTER DATA - CREATE RAK / LOKASI (ADMIN / SUPERVISOR)
    // =========================================================================

    private function handleCreateLokasiIntent($rawMessage, $clean, $userContext) {
        $roleName = $userContext['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'type' => 'permission_denied',
                'title' => 'Hak Akses Dibatasi',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-lock me-1"></i> Menambah Master Rak hanya diizinkan untuk role berwenang.</div>'
            ];
        }

        $kode = $this->extractRackCode($rawMessage);
        $nama = '';
        if (preg_match('/nama\s*[:=]?\s*([^,\;]+)/i', $rawMessage, $m)) $nama = trim($m[1]);
        $kapasitas = 100;
        if (preg_match('/kapasitas\s*[:=]?\s*(\d+)/i', $clean, $m)) $kapasitas = (int)$m[1];

        if (empty($kode)) {
            $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
            $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-map-marker-alt me-2"></i>Tambah Rak / Lokasi Baru</h6>';
            $html .= '<p class="small text-muted mb-2">Format pembuatan rak penyimpanan baru:</p>';
            $html .= '<div class="alert alert-light border small py-2 px-3 mb-2 font-monospace">';
            $html .= 'Tambah rak baru: kode [Kode: A.1.01], nama [Nama Rak], kapasitas [Kapasitas]';
            $html .= '</div>';
            $html .= '<button class="btn btn-sm btn-outline-primary ai-quick-btn" data-query="Tambah rak baru: kode D.1.01, nama Gudang D - Rak 1, kapasitas 150"><i class="fas fa-plus me-1"></i>Tambah Rak D.1.01</button>';
            $html .= '</div>';

            return ['success' => true, 'type' => 'create_lokasi_guide', 'title' => 'Tambah Rak Baru', 'message' => $html];
        }

        if (empty($nama)) $nama = "Gudang " . substr($kode, 0, 1) . " - " . $kode;

        // Check unique kode
        $stmtCheck = $this->db->prepare("SELECT id FROM lokasi WHERE kode = ?");
        $stmtCheck->execute([$kode]);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'type' => 'error', 'title' => 'Kode Rak Duplikat', 'message' => "Kode rak $kode sudah ada dalam database."];
        }

        // Parse rack, level, posisi
        $parts = explode('.', $kode);
        $rack   = $parts[0] ?? 'A';
        $level  = (int)($parts[1] ?? 1);
        $posisi = (int)($parts[2] ?? 1);

        $payload = [
            'action_type' => 'create_lokasi',
            'kode'        => $kode,
            'rack'        => $rack,
            'level'       => $level,
            'posisi'      => $posisi,
            'nama'        => $nama,
            'kapasitas'   => $kapasitas
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-2"></i>Konfirmasi Tambah Master Rak</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Kode Rak:</strong> <span class="badge bg-dark font-monospace">' . htmlspecialchars($kode) . '</span></div>';
        $html .= '<div><strong>Nama Rak:</strong> ' . htmlspecialchars($nama) . '</div>';
        $html .= '<div><strong>Kapasitas:</strong> ' . $kapasitas . ' unit</div>';
        $html .= '<div><strong>Status Awal:</strong> <span class="badge bg-secondary">kosong</span></div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Buat Rak</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Tambah Rak',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function executeCreateLokasi($params, $userContext) {
        $lokasiModel = new Lokasi();
        $kode = trim($params['kode'] ?? '');
        $parts = explode('.', $kode);
        $rack   = !empty($params['rack']) ? $params['rack'] : ($parts[0] ?? 'A');
        $level  = !empty($params['level']) ? $params['level'] : ($parts[1] ?? '1');
        $posisi = !empty($params['posisi']) ? $params['posisi'] : ($parts[2] ?? '01');
        $nama   = !empty($params['nama']) ? $params['nama'] : "Rak $kode";
        $kapasitas = !empty($params['kapasitas']) ? (int)$params['kapasitas'] : 100;

        $data = [
            'kode'      => $kode,
            'rack'      => $rack,
            'level'     => $level,
            'posisi'    => $posisi,
            'nama'      => $nama,
            'deskripsi' => 'Dibuat melalui AI Assistant',
            'kapasitas' => $kapasitas,
            'status'    => 'kosong'
        ];

        try {
            $existing = $lokasiModel->getByKode($data['kode']);
            if ($existing) {
                return ['success' => false, 'message' => "Kode rak '{$data['kode']}' sudah ada di database."];
            }

            $res = $lokasiModel->create($data);
            if ($res) {
                $logModel = new LogAktivitas();
                $logModel->record('TAMBAH_LOKASI', "AI Assistant: Berhasil membuat rak baru [{$data['kode']}] {$data['nama']}", 'Master Data', $data['kode']);

                $html = '<div class="ai-success-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
                $html .= '<h6 class="fw-bold text-success mb-1"><i class="fas fa-check-circle me-1"></i>Master Rak Berhasil Dibuat!</h6>';
                $html .= '<p class="small text-muted mb-2">Rak <strong>' . htmlspecialchars($data['kode']) . '</strong> telah aktif dan siap digunakan untuk penempatan stok.</p>';
                $html .= '<div class="text-end"><a href="' . BASE_URL . '/lokasi/index" class="btn btn-xs btn-outline-primary">Lihat Daftar Rak</a></div>';
                $html .= '</div>';

                return [
                    'success' => true,
                    'type' => 'create_lokasi_success',
                    'title' => 'Rak Dibuat',
                    'message' => $html
                ];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => 'Gagal membuat rak: ' . $e->getMessage()];
        }

        return ['success' => false, 'message' => 'Gagal membuat rak baru.'];
    }

    // =========================================================================
    //  JOBDESK 8: MASTER DATA - CREATE KATEGORI (ADMIN / SUPERVISOR)
    // =========================================================================

    private function handleCreateKategoriIntent($rawMessage, $clean, $userContext) {
        $roleName = $userContext['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'type' => 'permission_denied',
                'title' => 'Hak Akses Dibatasi',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-lock me-1"></i> Menambah Kategori hanya diizinkan untuk role berwenang.</div>'
            ];
        }

        $nama = '';
        $deskripsi = 'Dibuat via AI Assistant';

        if (preg_match('/(?:tambah\s+kategori|buat\s+kategori)\s+(?:baru\s*)?[:=]?\s*([^,\;]+)/i', $rawMessage, $m)) {
            $nama = trim($m[1]);
        }
        if (preg_match('/deskripsi\s*[:=]?\s*([^,\;]+)/i', $rawMessage, $m)) {
            $deskripsi = trim($m[1]);
        }

        if (empty($nama)) {
            return [
                'success' => true,
                'type' => 'create_kategori_guide',
                'title' => 'Tambah Kategori Baru',
                'message' => '<p class="small">Format: <em>"Tambah kategori baru: [Nama], deskripsi: [Keterangan]"</em></p>' .
                    '<button class="btn btn-sm btn-outline-primary ai-quick-btn" data-query="Tambah kategori baru: Sparepart Komputer, deskripsi: Komponen pengganti PC"><i class="fas fa-plus me-1"></i>Tambah Kategori Sparepart Komputer</button>'
            ];
        }

        $payload = [
            'action_type' => 'create_kategori',
            'name'        => $nama,
            'description' => $deskripsi
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-tags me-2"></i>Konfirmasi Tambah Kategori</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Nama Kategori:</strong> ' . htmlspecialchars($nama) . '</div>';
        $html .= '<div><strong>Deskripsi:</strong> ' . htmlspecialchars($deskripsi) . '</div>';
        $html .= '</div>';
        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Buat Kategori</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Kategori',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function executeCreateKategori($params, $userContext) {
        $kategoriModel = new Kategori();
        $res = $kategoriModel->create(['name' => $params['name'], 'description' => $params['description']]);

        if ($res) {
            $logModel = new LogAktivitas();
            $logModel->record('TAMBAH_KATEGORI', "AI Assistant: Berhasil membuat kategori baru {$params['name']}", 'Master Data', $params['name']);

            return [
                'success' => true,
                'type' => 'create_kategori_success',
                'title' => 'Kategori Tersimpan',
                'message' => '<div class="alert alert-success py-2 px-3 small mb-0"><i class="fas fa-check-circle me-1"></i> Kategori <strong>' . htmlspecialchars($params['name']) . '</strong> berhasil dibuat!</div>'
            ];
        }

        return ['success' => false, 'message' => 'Gagal membuat kategori baru.'];
    }

    // =========================================================================
    //  JOBDESK 9: REPORTS & TRANSACTIONS MONITORING
    // =========================================================================

    public function handleRecentTransactionsIntent($responseMode = 'show_data') {
        if ($responseMode === 'count_only') {
            $totalTrx = (int)$this->db->query("SELECT COUNT(*) FROM transaksi")->fetchColumn();
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-history fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">Total seluruh transaksi gudang</div>';
            $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . number_format($totalTrx) . ' <small class="fs-6 text-muted fw-normal">transaksi</small></div>';
            $html .= '<div class="small text-muted mt-1">transaksi masuk, keluar, dan mutasi</div>';
            $html .= '</div></div>';
            $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan 5 transaksi terakhir&quot;</em> untuk melihat riwayatnya.</div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'transactions_count',
                'title' => 'Jumlah Transaksi',
                'message' => $html,
                'suggestions' => [
                    'Tampilkan 5 transaksi terakhir',
                    'Ringkasan status gudang'
                ]
            ];
        }

        $transaksiModel = new Transaksi();
        $riwayat = $transaksiModel->getRiwayat([], 5, 0);

        if (empty($riwayat)) {
            return [
                'success' => true,
                'type' => 'recent_transactions',
                'title' => 'Riwayat Transaksi',
                'message' => '<div class="alert alert-info py-2 px-3 small mb-0"><i class="fas fa-info-circle me-1"></i> Belum ada transaksi tercatat dalam sistem.</div>'
            ];
        }

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-history text-primary me-2"></i>5 Transaksi Terakhir di Gudang</h6>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small align-middle mb-1">';
        $html .= '<thead class="table-light"><tr><th>No. Transaksi</th><th>Tipe</th><th>Material</th><th class="text-end">Qty</th><th>Operator</th></tr></thead><tbody>';

        foreach ($riwayat as $trx) {
            $typeBadge = ($trx['type'] === 'in') ? '<span class="badge bg-success">MASUK</span>' :
                         (($trx['type'] === 'out') ? '<span class="badge bg-danger">KELUAR</span>' : '<span class="badge bg-warning text-dark">MUTASI</span>');
            $html .= '<tr>';
            $html .= '<td><span class="badge bg-light text-dark font-monospace border">' . htmlspecialchars($trx['transaction_number']) . '</span></td>';
            $html .= '<td>' . $typeBadge . '</td>';
            $html .= '<td>' . htmlspecialchars($trx['barang_name']) . '</td>';
            $html .= '<td class="text-end fw-bold">' . number_format($trx['qty']) . ' ' . htmlspecialchars($trx['barang_unit'] ?? '') . '</td>';
            $html .= '<td><small class="text-muted">' . htmlspecialchars($trx['operator'] ?? '-') . '</small></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="text-end mt-2"><a href="' . BASE_URL . '/riwayat/index" class="btn btn-xs btn-outline-primary">Lihat Seluruh Riwayat</a></div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'recent_transactions',
            'title' => 'Riwayat Transaksi Terakhir',
            'message' => $html
        ];
    }

    public function handleReportIntent($clean) {
        $laporan = new Laporan();
        $today = date('Y-m-d');

        if (strpos($clean, 'masuk') !== false) {
            $rows = $laporan->getLaporanMasuk(['date_from' => $today, 'date_to' => $today]);
            $title = 'Laporan Barang Masuk Hari Ini';
            $type = 'Masuk';
        } elseif (strpos($clean, 'keluar') !== false) {
            $rows = $laporan->getLaporanKeluar(['date_from' => $today, 'date_to' => $today]);
            $title = 'Laporan Barang Keluar Hari Ini';
            $type = 'Keluar';
        } elseif (strpos($clean, 'pemindahan') !== false || strpos($clean, 'mutasi') !== false) {
            $rows = $laporan->getLaporanPemindahan(['date_from' => $today, 'date_to' => $today]);
            $title = 'Laporan Pemindahan Barang Hari Ini';
            $type = 'Pemindahan';
        } else {
            $rows = $laporan->getLaporanStok();
            $title = 'Laporan Ringkasan Stok Gudang';
            $type = 'Stok';
        }

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-file-alt text-primary me-2"></i>' . $title . '</h6>';

        if (empty($rows)) {
            $html .= '<div class="alert alert-light border py-2 px-3 small mb-0"><i class="fas fa-info-circle me-1"></i> Tidak ada aktivitas <strong>' . $type . '</strong> tercatat untuk tanggal hari ini (' . date('d M Y') . ').</div>';
        } else {
            $totalQty = 0;
            foreach ($rows as $r) {
                $totalQty += (int)($r['qty'] ?? $r['total_qty'] ?? 0);
            }
            $html .= '<p class="small text-muted mb-2">Total Transaksi: <strong>' . count($rows) . '</strong> &bull; Akumulasi Volume: <strong>' . number_format($totalQty) . ' unit</strong></p>';
            $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small mb-1">';
            $html .= '<thead class="table-light"><tr><th>Bukti / Kode</th><th>Material</th><th class="text-end">Qty</th><th>Ket / Lokasi</th></tr></thead><tbody>';

            foreach (array_slice($rows, 0, 5) as $r) {
                $html .= '<tr>';
                $html .= '<td><span class="badge bg-light text-dark font-monospace">' . htmlspecialchars($r['transaction_number'] ?? $r['code'] ?? '') . '</span></td>';
                $html .= '<td>' . htmlspecialchars($r['barang_name'] ?? '') . '</td>';
                $html .= '<td class="text-end fw-bold">' . number_format($r['qty'] ?? $r['total_qty'] ?? 0) . '</td>';
                $html .= '<td><small class="text-muted">' . htmlspecialchars($r['lokasi_kode'] ?? $r['lokasi_asal_kode'] ?? $r['lokasi_list'] ?? '-') . '</small></td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            if (count($rows) > 5) {
                $html .= '<small class="text-muted d-block text-center mt-1">...dan ' . (count($rows) - 5) . ' data lainnya.</small>';
            }
        }

        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'report_summary',
            'title' => $title,
            'message' => $html
        ];
    }

    // =========================================================================
    //  JOBDESK 10: USER MANAGEMENT (ADMIN ONLY)
    // =========================================================================

    private function handleUserManagementIntent($userContext, $responseMode = 'show_data') {
        $roleName = $userContext['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return [
                'success' => false,
                'type' => 'permission_denied',
                'title' => 'Akses Dibatasi',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-lock me-1"></i> Data pengguna sistem hanya dapat diakses oleh role berwenang.</div>'
            ];
        }

        $userModel = new User();
        $users = $userModel->getAllUsers();

        if ($responseMode === 'count_only') {
            $jumlah = count($users);
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-users-cog fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">Total pengguna sistem terdaftar</div>';
            $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . $jumlah . ' <small class="fs-6 text-muted fw-normal">pengguna</small></div>';
            $html .= '<div class="small text-muted mt-1">user aktif dan non-aktif</div>';
            $html .= '</div></div>';
            $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan daftar user&quot;</em> untuk melihat rinciannya.</div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'user_count',
                'title' => 'Jumlah User',
                'message' => $html,
                'suggestions' => [
                    'Tampilkan daftar user',
                    'Ringkasan status gudang'
                ]
            ];
        }

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-users-cog text-primary me-2"></i>Daftar Pengguna SmartWarehouse (' . count($users) . ')</h6>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small align-middle mb-1">';
        $html .= '<thead class="table-light"><tr><th>Nama</th><th>Username</th><th>Peran / Role</th><th>Status</th></tr></thead><tbody>';

        foreach ($users as $u) {
            $roleBadge = ($u['role_name'] === 'Admin') ? '<span class="badge bg-danger">Admin</span>' :
                         (($u['role_name'] === 'Supervisor') ? '<span class="badge bg-warning text-dark">Supervisor</span>' : '<span class="badge bg-primary">Operator</span>');
            $statusBadge = ($u['status'] === 'active') ? '<span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i>Aktif</span>' : '<span class="text-muted small">Non-aktif</span>';
            $html .= '<tr>';
            $html .= '<td><strong>' . htmlspecialchars($u['full_name']) . '</strong></td>';
            $html .= '<td>' . htmlspecialchars($u['username']) . '</td>';
            $html .= '<td>' . $roleBadge . '</td>';
            $html .= '<td>' . $statusBadge . '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="text-end mt-2"><a href="' . BASE_URL . '/user/index" class="btn btn-xs btn-outline-primary">Buka Manajemen User</a></div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'user_list',
            'title' => 'Manajemen User',
            'message' => $html
        ];
    }

    // =========================================================================
    //  EXISTING FEATURES: MATERIAL LOCATION & RACK QTY
    // =========================================================================

    private function handleMaterialLocationIntent($rawMessage, $clean, $responseMode = 'show_data') {
        $patterns = [
            '/(?:dimana|di mana|letak|posisi|lokasi)\s+(?:saja\s+)?(?:lokasi\s+)?(?:material|barang|produk)?\s*(?:ini\s+)?(?:adalah\s+)?[:\-]?\s*(.+)/i',
            '/(?:cari|cek|periksa)\s+(?:lokasi|posisi)\s+(?:material|barang)?\s*(.+)/i',
            '/(?:material|barang)\s+(.+)\s+(?:ada\s+)?(?:di mana|dimana|disimpan di mana)/i'
        ];

        $extractedName = '';
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $rawMessage, $matches)) {
                $candidate = trim($matches[1]);
                $candidate = preg_replace('/[\?\.\,\!]+$/', '', $candidate);
                $candidate = trim($candidate);
                if (!in_array(mb_strtolower($candidate), ['ini', 'itu', 'tersebut', 'ini?', ''])) {
                    $extractedName = $candidate;
                    break;
                }
            }
        }

        if (empty($extractedName)) {
            $extractedName = $this->findBarangNameInText($clean);
        }

        if (empty($extractedName)) {
            return $this->getMaterialSelectionPrompt(
                'Anda ingin mengetahui lokasi untuk material apa? Silakan sebutkan nama atau kode material, atau pilih salah satu di bawah ini:'
            );
        }

        $items = $this->searchBarang($extractedName);
        if (empty($items)) {
            return [
                'success' => false,
                'type' => 'not_found',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p>Material dengan kata kunci <strong>\"" . htmlspecialchars($extractedName) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }

        return $this->getMaterialLocationResponse($items[0]['id'], $items[0]['name'], $items[0]['code'], $items[0]['unit'], $responseMode);
    }

    private function getMaterialLocationResponse($barangId, $barangName, $barangCode = '', $unit = '', $responseMode = 'show_data') {
        $barangModel = new Barang();
        $barangData = $barangModel->getById($barangId);
        if ($barangData) {
            $barangCode = $barangData['code'];
            $barangName = $barangData['name'];
            $unit = $barangData['unit'];
            $totalQty = (int)$barangData['total_qty'];
            $kategoriName = $barangData['kategori_name'] ?? '-';
            $minStock = (int)($barangData['min_stock'] ?? 0);
        } else {
            $totalQty = 0;
            $kategoriName = '-';
            $minStock = 0;
        }

        $sql = "SELECT l.id as lokasi_id, l.kode, l.nama as lokasi_nama, l.rack, l.level, l.posisi, l.status as lokasi_status, s.qty 
                FROM stok s 
                JOIN lokasi l ON s.lokasi_id = l.id 
                WHERE s.barang_id = :barang_id AND s.qty > 0 
                ORDER BY l.kode ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
        $stmt->execute();
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── COUNT ONLY MODE ──────────────────────────────────────────────────
        // Jika user hanya bertanya "berapa" (tanpa "tampilkan"), kembalikan
        // jawaban singkat: jumlah total stok saja.
        if ($responseMode === 'count_only') {
            $lokasiCount = count($locations);
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-box-open fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="small text-muted mb-1">';
            $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . htmlspecialchars($barangCode) . '</span>';
            $html .= '<strong>' . htmlspecialchars($barangName) . '</strong>';
            $html .= '</div>';
            $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . number_format($totalQty) . ' <small class="fs-6 text-muted fw-normal">' . htmlspecialchars($unit) . '</small></div>';
            $html .= '<div class="small text-muted mt-1">Tersebar di <strong>' . $lokasiCount . ' rak</strong></div>';
            $html .= '</div></div>';
            if ($lokasiCount > 0) {
                $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan lokasi ' . htmlspecialchars($barangName) . '&quot;</em> untuk melihat detailnya.</div>';
            }
            $html .= '</div>';
            return [
                'success' => true,
                'type'    => 'material_count',
                'title'   => 'Total Stok: ' . $barangName,
                'message' => $html,
                'data'    => [
                    'barang_id'    => $barangId,
                    'barang_name'  => $barangName,
                    'barang_code'  => $barangCode,
                    'total_qty'    => $totalQty,
                    'unit'         => $unit,
                    'lokasi_count' => $lokasiCount
                ],
                'suggestions' => [
                    'Tampilkan lokasi ' . $barangName,
                    !empty($locations) ? 'Berapa qty di rak ' . $locations[0]['kode'] . '?' : 'Tampilkan semua stok'
                ]
            ];
        }

        // ── SHOW DATA MODE (default) ─────────────────────────────────────────
        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex justify-content-between align-items-start mb-2">';
        $html .= '<div>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">' . htmlspecialchars($barangCode) . '</span>';
        $html .= '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">' . htmlspecialchars($kategoriName) . '</span>';
        $html .= '<h6 class="fw-bold text-dark mt-1 mb-0">' . htmlspecialchars($barangName) . '</h6>';
        $html .= '</div>';
        $html .= '<div class="text-end">';
        $html .= '<span class="fs-5 fw-bold text-primary">' . number_format($totalQty) . '</span> <small class="text-muted">' . htmlspecialchars($unit) . '</small>';
        $html .= '<div class="text-muted small" style="font-size:0.75rem;">Total Stok</div>';
        $html .= '</div></div>';

        if (empty($locations)) {
            $html .= '<div class="alert alert-warning py-2 px-3 small rounded-2 mb-0 mt-3">';
            $html .= '<i class="fas fa-exclamation-triangle me-1"></i> Material ini saat ini <strong>tidak memiliki stok</strong> di rak gudang mana pun.';
            $html .= '</div>';
        } else {
            $html .= '<div class="table-responsive mt-3">';
            $html .= '<table class="table table-sm table-bordered align-middle mb-2 small">';
            $html .= '<thead class="table-light"><tr><th>Kode Rak</th><th>Nama Lokasi</th><th class="text-end">Jumlah</th><th class="text-center">Aksi Cepat</th></tr></thead><tbody>';

            foreach ($locations as $loc) {
                $html .= '<tr>';
                $html .= '<td><span class="badge bg-dark font-monospace">' . htmlspecialchars($loc['kode']) . '</span></td>';
                $html .= '<td>' . htmlspecialchars($loc['lokasi_nama']) . '</td>';
                $html .= '<td class="text-end fw-bold text-success">' . number_format($loc['qty']) . ' ' . htmlspecialchars($unit) . '</td>';
                $html .= '<td class="text-center">';
                $html .= '<button class="btn btn-xs btn-outline-primary py-0 px-2 ai-fill-transfer-btn" ' .
                         'data-barang-id="' . $barangId . '" ' .
                         'data-barang-name="' . htmlspecialchars($barangName, ENT_QUOTES) . '" ' .
                         'data-barang-code="' . htmlspecialchars($barangCode, ENT_QUOTES) . '" ' .
                         'data-lokasi-id="' . $loc['lokasi_id'] . '" ' .
                         'data-lokasi-kode="' . htmlspecialchars($loc['kode'], ENT_QUOTES) . '" ' .
                         'data-available-qty="' . $loc['qty'] . '" ' .
                         'data-unit="' . htmlspecialchars($unit, ENT_QUOTES) . '" ' .
                         'title="Pindahkan stok dari rak ini"><i class="fas fa-exchange-alt me-1"></i>Pindahkan</button>';
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'location_info',
            'title' => 'Lokasi Material: ' . $barangName,
            'message' => $html,
            'data' => [
                'barang_id' => $barangId,
                'barang_name' => $barangName,
                'barang_code' => $barangCode,
                'total_qty' => $totalQty,
                'unit' => $unit,
                'locations' => $locations
            ],
            'suggestions' => [
                "Ada berapakah qty pada rak " . (!empty($locations) ? $locations[0]['kode'] : 'A.1.01') . "?",
                !empty($locations) ? "Pindahkan 1 {$unit} {$barangName} dari {$locations[0]['kode']} ke " : "Cek barang lain"
            ]
        ];
    }

    private function handleRackQtyIntent($rawMessage, $clean, $responseMode = 'show_data') {
        $rackCode = $this->extractRackCode($rawMessage);

        if (empty($rackCode)) {
            $lokasi = $this->findLokasiByKeyword($clean);
            if ($lokasi) {
                return $this->getRackQtyResponse($lokasi['id'], $lokasi['kode'], $responseMode);
            }

            return $this->getRackSelectionPrompt(
                'Rak mana yang ingin Anda periksa? Sebutkan kode rak (contoh: <strong>A.1.01</strong>) atau klik salah satu rak di bawah:'
            );
        }

        $stmt = $this->db->prepare("SELECT * FROM lokasi WHERE kode = :kode OR kode LIKE :likeKode LIMIT 1");
        $stmt->bindValue(':kode', strtoupper($rackCode));
        $stmt->bindValue(':likeKode', '%' . strtoupper($rackCode) . '%');
        $stmt->execute();
        $lokasi = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lokasi) {
            return [
                'success' => false,
                'type' => 'not_found',
                'title' => 'Rak Tidak Ditemukan',
                'message' => "<p>Rak dengan kode <strong>\"" . htmlspecialchars($rackCode) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => $this->getSampleRackSuggestions()
            ];
        }

        return $this->getRackQtyResponse($lokasi['id'], $lokasi['kode'], $responseMode);
    }

    private function getRackQtyResponse($lokasiId, $lokasiKode, $responseMode = 'show_data') {
        $lokasiModel = new Lokasi();
        $lokasi = $lokasiModel->getById($lokasiId);
        $items = $lokasiModel->getItemsByLokasiId($lokasiId);

        $totalQty = (int)($lokasi['total_qty'] ?? 0);
        $jumlahItem = count($items);
        $status = $lokasi['status'] ?? 'kosong';

        // ── COUNT ONLY MODE ──────────────────────────────────────────────────
        // Jika user hanya bertanya "berapa" (tanpa "tampilkan"), kembalikan
        // jawaban singkat berupa angka saja.
        if ($responseMode === 'count_only') {
            $statusText = ($status === 'terisi') ? 'terisi' : 'kosong';
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-warehouse fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">Total qty di rak <span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span></div>';
            $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . number_format($totalQty) . '</div>';
            $html .= '<div class="small text-muted mt-1">' . $jumlahItem . ' jenis material &bull; Status: <strong>' . $statusText . '</strong></div>';
            $html .= '</div></div>';
            if ($totalQty > 0) {
                $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan isi rak ' . htmlspecialchars($lokasi['kode']) . '&quot;</em> untuk melihat detailnya.</div>';
            }
            $html .= '</div>';
            return [
                'success' => true,
                'type'    => 'rack_count',
                'title'   => 'Jumlah Qty Rak: ' . $lokasi['kode'],
                'message' => $html,
                'data'    => [
                    'lokasi_id'   => $lokasiId,
                    'lokasi_kode' => $lokasi['kode'],
                    'total_qty'   => $totalQty,
                    'item_count'  => $jumlahItem
                ],
                'suggestions' => [
                    'Tampilkan isi rak ' . $lokasi['kode'],
                    'Audit stok rak ' . $lokasi['kode']
                ]
            ];
        }

        // ── SHOW DATA MODE (default) ─────────────────────────────────────────
        $statusBadge = ($status === 'terisi') 
            ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Terisi</span>' 
            : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Kosong</span>';

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex justify-content-between align-items-start mb-2">';
        $html .= '<div>';
        $html .= '<span class="badge bg-dark font-monospace me-2">' . htmlspecialchars($lokasi['kode']) . '</span>';
        $html .= $statusBadge;
        $html .= '<h6 class="fw-bold text-dark mt-1 mb-0">' . htmlspecialchars($lokasi['nama']) . '</h6>';
        $html .= '<small class="text-muted">' . htmlspecialchars($lokasi['deskripsi'] ?? '-') . '</small>';
        $html .= '</div>';
        $html .= '<div class="text-end">';
        $html .= '<span class="fs-5 fw-bold text-primary">' . number_format($totalQty) . '</span>';
        $html .= '<div class="text-muted small" style="font-size:0.75rem;">Total Item: ' . $jumlahItem . '</div>';
        $html .= '</div></div>';

        if (empty($items)) {
            $html .= '<div class="alert alert-info py-2 px-3 small rounded-2 mb-0 mt-3">';
            $html .= '<i class="fas fa-info-circle me-1"></i> Rak ini saat ini <strong>kosong</strong> (belum ada material). Siap digunakan untuk penempatan stok.';
            $html .= '</div>';
        } else {
            $html .= '<div class="table-responsive mt-3">';
            $html .= '<table class="table table-sm table-bordered align-middle mb-2 small">';
            $html .= '<thead class="table-light"><tr><th>Kode</th><th>Nama Material</th><th>Kategori</th><th class="text-end">Qty</th><th class="text-center">Aksi</th></tr></thead><tbody>';

            foreach ($items as $item) {
                $html .= '<tr>';
                $html .= '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($item['code']) . '</span></td>';
                $html .= '<td class="fw-medium">' . htmlspecialchars($item['name']) . '</td>';
                $html .= '<td><span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">' . htmlspecialchars($item['kategori'] ?? '-') . '</span></td>';
                $html .= '<td class="text-end fw-bold text-primary">' . number_format($item['qty']) . ' ' . htmlspecialchars($item['unit']) . '</td>';
                $html .= '<td class="text-center">';
                $html .= '<button class="btn btn-xs btn-outline-primary py-0 px-2 ai-fill-transfer-btn" ' .
                         'data-barang-name="' . htmlspecialchars($item['name'], ENT_QUOTES) . '" ' .
                         'data-barang-code="' . htmlspecialchars($item['code'], ENT_QUOTES) . '" ' .
                         'data-lokasi-id="' . $lokasiId . '" ' .
                         'data-lokasi-kode="' . htmlspecialchars($lokasi['kode'], ENT_QUOTES) . '" ' .
                         'data-available-qty="' . $item['qty'] . '" ' .
                         'data-unit="' . htmlspecialchars($item['unit'], ENT_QUOTES) . '" ' .
                         'title="Pindahkan material ini"><i class="fas fa-exchange-alt me-1"></i>Pindahkan</button>';
                $html .= '</td></tr>';
            }
            $html .= '</tbody></table></div>';
        }
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'rack_info',
            'title' => 'Isi Rak: ' . $lokasi['kode'],
            'message' => $html,
            'data' => [
                'lokasi_id' => $lokasiId,
                'lokasi_kode' => $lokasi['kode'],
                'items' => $items
            ],
            'suggestions' => [
                "Audit stok rak " . $lokasi['kode'],
                "Tampilkan rak-rak yang masih kosong"
            ]
        ];
    }

    // =========================================================================
    //  EXISTING FEATURES: TRANSFER (OPERATOR / SUPERVISOR)
    // =========================================================================

    private function handleTransferIntent($rawMessage, $clean, $userContext) {
        $multiRacks = $this->extractAllRackCodes($rawMessage);
        $parsed = $this->parseTransferEntities($rawMessage);

        if (count($multiRacks) > 2) {
            $fromRack = '';
            if (preg_match('/\bdari\s+(?:rak\s+)?([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2})\b/i', $rawMessage, $mFrom)) {
                $fromRack = $this->normalizeRackCode($mFrom[1]);
            }
            if (!empty($fromRack)) {
                $toRacks = array_values(array_filter($multiRacks, fn($r) => $r !== $fromRack));
                if (!empty($toRacks)) {
                    $parsedQty = $this->parseQty($rawMessage);
                    $isTotal = (bool)preg_match('/\b(total|dibagi|bagi rata)\b/i', $clean);
                    $qtyPerRack = ($isTotal && count($toRacks) > 1) ? max(1, (int)round($parsedQty / count($toRacks))) : $parsedQty;
                    $materialTerm = $parsed['material'] ?: $this->extractMaterialTerm($rawMessage);
                    if (empty($materialTerm)) $materialTerm = $this->findBarangNameInText($clean);
                    return $this->handleBatchTransferMultiRack($materialTerm, $fromRack, $toRacks, $qtyPerRack, 'Pemindahan sekaligus via AI Assistant', $userContext);
                }
            }
        }

        $materialTerm = $parsed['material'];
        $fromRackTerm = $parsed['from_rack'];
        $toRackTerm   = $parsed['to_rack'];
        $qty          = $parsed['qty'];

        $isTemplateQuery = (
            stripos($clean, 'material ini') !== false ||
            stripos($clean, 'rak ini') !== false ||
            stripos($clean, 'rak tujuan') !== false ||
            stripos($clean, 'qty yang kita minta') !== false
        );

        if ($isTemplateQuery && (empty($materialTerm) || empty($fromRackTerm) || empty($toRackTerm) || $qty <= 0)) {
            return $this->getTransferInstructionGuide();
        }

        $missing = [];
        if (empty($materialTerm)) $missing[] = 'Material/Barang';
        if (empty($fromRackTerm)) $missing[] = 'Rak Asal';
        if (empty($toRackTerm))   $missing[] = 'Rak Tujuan';
        if ($qty <= 0)            $missing[] = 'Jumlah Qty';

        if (!empty($missing)) {
            return $this->getTransferClarificationPrompt($materialTerm, $fromRackTerm, $toRackTerm, $qty, $missing);
        }

        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return ['success' => false, 'type' => 'transfer_error', 'title' => 'Material Tidak Ditemukan', 'message' => "Material $materialTerm tidak ditemukan."];
        }
        $barang = $barangMatches[0];

        $fromLokasi = $this->findLokasiExactOrLike($fromRackTerm);
        if (!$fromLokasi) return ['success' => false, 'type' => 'transfer_error', 'title' => 'Rak Asal Tidak Ditemukan', 'message' => "Rak asal $fromRackTerm tidak ditemukan."];

        $toLokasi = $this->findLokasiExactOrLike($toRackTerm);
        if (!$toLokasi) return ['success' => false, 'type' => 'transfer_error', 'title' => 'Rak Tujuan Tidak Ditemukan', 'message' => "Rak tujuan $toRackTerm tidak ditemukan."];

        if ($fromLokasi['id'] == $toLokasi['id']) {
            return ['success' => false, 'type' => 'transfer_error', 'title' => 'Lokasi Tidak Valid', 'message' => "Rak asal dan rak tujuan tidak boleh sama."];
        }

        $stmtStock = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = :b_id AND lokasi_id = :l_id");
        $stmtStock->execute([':b_id' => $barang['id'], ':l_id' => $fromLokasi['id']]);
        $currentStock = (int)($stmtStock->fetchColumn() ?: 0);

        if ($currentStock < $qty) {
            return ['success' => false, 'type' => 'transfer_error', 'title' => 'Stok Kurang', 'message' => "Stok di rak {$fromLokasi['kode']} hanya ada $currentStock {$barang['unit']}."];
        }

        $transferPayload = [
            'action_type'      => 'transfer',
            'barang_id'        => (int)$barang['id'],
            'barang_name'      => $barang['name'],
            'barang_code'      => $barang['code'],
            'lokasi_id'        => (int)$fromLokasi['id'],
            'lokasi_kode'      => $fromLokasi['kode'],
            'lokasi_nama'      => $fromLokasi['nama'],
            'lokasi_tujuan_id' => (int)$toLokasi['id'],
            'lokasi_tujuan_kode'=> $toLokasi['kode'],
            'lokasi_tujuan_nama'=> $toLokasi['nama'],
            'qty'              => (int)$qty,
            'unit'             => $barang['unit'],
            'remaining_stock'  => $currentStock - $qty,
            'current_stock'    => $currentStock
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold mb-2 text-dark"><i class="fas fa-exchange-alt text-primary me-2"></i>Konfirmasi Pemindahan Material</h6>';

        $html .= '<div class="row g-2 mb-2">';
        $html .= '<div class="col-12"><div class="p-2 bg-light rounded-2 border">';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;"><i class="fas fa-arrow-up text-danger me-1"></i>Rak Asal</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($fromLokasi['kode']) . '</span>';
        $html .= '<div class="small text-secondary mt-1">Sisa: ' . ($currentStock - $qty) . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;"><i class="fas fa-arrow-down text-success me-1"></i>Rak Tujuan</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($toLokasi['kode']) . '</span>';
        $html .= '<div class="small text-success mt-1">Masuk: +' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div></div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($transferPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Eksekusi Pemindahan Sekarang</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'transfer_preview',
            'title' => 'Konfirmasi Pemindahan Barang',
            'message' => $html,
            'data' => $transferPayload
        ];
    }

    private function parseTransferEntities($text) {
        $clean = ' ' . mb_strtolower(trim($text), 'UTF-8') . ' ';
        $orig  = ' ' . trim($text) . ' ';

        $result = ['material' => '', 'from_rack' => '', 'to_rack' => '', 'qty' => 0];

        if (preg_match('/\b(?:qty|jumlah|sebanyak|sebesar)\s*[:=]?\s*(\d+)\b/i', $clean, $m)) {
            $result['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(\d+)\s*(?:unit|pcs|box|kg|rim|item|dus|sak|meter|liter|buah|pack)\b/i', $clean, $m)) {
            $result['qty'] = (int)$m[1];
        } elseif (preg_match('/\b(?:pindah|pindahkan|transfer)\s+(\d+)\b/i', $clean, $m)) {
            $result['qty'] = (int)$m[1];
        }

        if (preg_match('/\b(?:dari\s+rak|pada\s+rak|dari\s+lokasi|pada\s+lokasi|dari)\s+([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2}|gudang\s+[A-Z]\s*-\s*rak\s*\d+)/i', $orig, $m)) {
            $result['from_rack'] = $this->normalizeRackCode($m[1]);
        }

        if (preg_match('/\b(?:ke\s+rak\s+tujuan|ke\s+rak|ke\s+lokasi|tujuan\s+ke|ke)\s+([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2}|gudang\s+[A-Z]\s*-\s*rak\s*\d+)/i', $orig, $m)) {
            $cand = $this->normalizeRackCode($m[1]);
            if ($cand !== $result['from_rack']) $result['to_rack'] = $cand;
        }

        if (preg_match('/(?:pindah|pindahkan|transfer|geser)\s+(?:material|barang|produk)?\s*(?:ini\s+)?(.+?)\s+(?:pada\s+rak|dari\s+rak|dari|di\s+rak)/i', $orig, $m)) {
            $mat = trim($m[1]);
            $mat = preg_replace('/^\d+\s*(?:unit|pcs|box|kg|rim|item|dus)?\s*/i', '', $mat);
            $result['material'] = trim($mat);
        } elseif (preg_match('/(?:pindah|pindahkan|transfer)\s+(\d+)\s*(?:unit|pcs|box|kg|rim|item)?\s+(.+?)\s+(?:dari|pada|ke)/i', $orig, $m)) {
            $result['material'] = trim($m[2]);
            if ($result['qty'] <= 0) $result['qty'] = (int)$m[1];
        }

        if (empty($result['material'])) {
            $foundBarang = $this->findBarangNameInText($clean);
            if (!empty($foundBarang)) $result['material'] = $foundBarang;
        }

        if (in_array(mb_strtolower($result['material']), ['material ini', 'barang ini', 'ini', 'material', 'barang'])) {
            $result['material'] = '';
        }

        return $result;
    }

    public function executeTransfer($params, $userContext) {
        $barangId       = (int)($params['barang_id'] ?? 0);
        // Support both lokasi_id and lokasi_asal_id as the source location
        $lokasiId       = (int)($params['lokasi_id'] ?? $params['lokasi_asal_id'] ?? $params['lokasi_dari_id'] ?? 0);
        $lokasiTujuanId = (int)($params['lokasi_tujuan_id'] ?? $params['lokasi_ke_id'] ?? 0);
        $qty            = (int)($params['qty'] ?? 0);
        $userId         = $userContext['user_id'] ?? $_SESSION['user_id'] ?? null;
        $roleName       = $userContext['role_name'] ?? $_SESSION['role_name'] ?? 'Guest';

        if (!$this->isAllowedRole($roleName)) {
            return ['success' => false, 'message' => 'Role Anda tidak memiliki hak akses untuk memindahkan barang.'];
        }

        $transaksiModel = new Transaksi();
        $nomorTransaksi = $transaksiModel->generateTransactionNumber('TRF');

        $dataTransaksi = [
            'transaction_number' => $nomorTransaksi,
            'user_id'            => $userId,
            'transaction_date'   => date('Y-m-d H:i:s'),
            'notes'              => 'Pemindahan dieksekusi melalui SmartWarehouse AI Assistant',
        ];

        $dataDetail = [
            'barang_id'        => $barangId,
            'lokasi_id'        => $lokasiId,
            'lokasi_tujuan_id' => $lokasiTujuanId,
            'qty'              => $qty,
        ];

        $result = $transaksiModel->createTransaksiTransfer($dataTransaksi, $dataDetail);

        if ($result['success']) {
            $logModel = new LogAktivitas();
            $logModel->record('PEMINDAHAN_BARANG', "AI Assistant: Pemindahan barang #$nomorTransaksi berhasil. Qty: $qty.", 'AI Assistant', $nomorTransaksi);

            $stmtS1 = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtS1->execute([$barangId, $lokasiId]);
            $s1 = (int)($stmtS1->fetchColumn() ?: 0);

            $stmtS2 = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtS2->execute([$barangId, $lokasiTujuanId]);
            $s2 = (int)($stmtS2->fetchColumn() ?: 0);

            $html = '<div class="ai-success-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
            $html .= '<h6 class="fw-bold mb-1 text-success"><i class="fas fa-check-circle me-1"></i>Pemindahan Berhasil Dilakukan!</h6>';
            $html .= '<small class="text-muted">Nomor Transaksi: <strong class="text-dark font-monospace">' . htmlspecialchars($nomorTransaksi) . '</strong></small>';
            $html .= '<p class="small my-2">Sebanyak <strong class="text-primary">' . number_format($qty) . ' ' . htmlspecialchars($params['unit'] ?? '') . '</strong> ' . htmlspecialchars($params['barang_name'] ?? '') . ' berhasil dipindahkan.</p>';
            $html .= '<div class="row g-2 mb-2"><div class="col-6"><div class="p-2 bg-light border rounded text-center small">Sisa di ' . htmlspecialchars($params['lokasi_kode'] ?? '') . ': <strong>' . $s1 . '</strong></div></div><div class="col-6"><div class="p-2 bg-light border rounded text-center small text-success">Total di ' . htmlspecialchars($params['lokasi_tujuan_kode'] ?? '') . ': <strong>' . $s2 . '</strong></div></div></div>';
            $html .= '<div class="text-end"><a href="' . BASE_URL . '/riwayat/index" class="btn btn-xs btn-outline-primary"><i class="fas fa-history me-1"></i>Lihat Riwayat</a></div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'transfer_success',
                'title' => 'Pemindahan Berhasil',
                'message' => $html,
                'data' => [
                    'transaction_number' => $nomorTransaksi,
                    'qty' => $qty
                ]
            ];
        }

        return ['success' => false, 'message' => 'Gagal: ' . ($result['message'] ?? 'Kesalahan transaksi.')];
    }

    // =========================================================================
    //  UNIFIED ACTION DISPATCHER
    // =========================================================================

    public function executeAction($actionData, $userContext) {
        $actionType = $actionData['action_type'] ?? $actionData['type'] ?? '';

        switch ($actionType) {
            case 'batch':
            case 'batch_transaction':
            case 'batch_inbound':
            case 'batch_outbound':
            case 'batch_transfer':
            case 'batch_stock_adjustment':
                return $this->executeBatch($actionData, $userContext);
            case 'inbound':
                return $this->executeInbound($actionData, $userContext);
            case 'outbound':
                return $this->executeOutbound($actionData, $userContext);
            case 'transfer':
                return $this->executeTransfer($actionData, $userContext);
            case 'stock_adjustment':
                return $this->executeStockAdjustment($actionData, $userContext);
            case 'create_barang':
                return $this->executeCreateBarang($actionData, $userContext);
            case 'create_lokasi':
                return $this->executeCreateLokasi($actionData, $userContext);
            case 'create_kategori':
                return $this->executeCreateKategori($actionData, $userContext);
            default:
                return ['success' => false, 'message' => 'Tipe aksi tidak dikenali: ' . htmlspecialchars($actionType)];
        }
    }

    /**
     * Execute simultaneous / batch transactions (multi-rack / multi-item)
     */
    public function executeBatch($actionData, $userContext) {
        $roleName = $userContext['role_name'] ?? $_SESSION['role_name'] ?? 'Guest';
        if (!$this->isAllowedRole($roleName)) {
            return ['success' => false, 'message' => 'Role Anda tidak memiliki izin untuk melakukan transaksi.'];
        }

        $subActions = $actionData['sub_actions'] ?? [];
        if (empty($subActions)) {
            return ['success' => false, 'message' => 'Tidak ada transaksi yang dapat dieksekusi.'];
        }

        $executed = [];
        $failed = [];
        $transaksiModel = new Transaksi();
        $logModel = new LogAktivitas();
        $userId = $userContext['user_id'] ?? $_SESSION['user_id'] ?? null;

        foreach ($subActions as $act) {
            $type = $act['action_type'] ?? $act['type'] ?? $actionData['batch_type'] ?? 'inbound';
            if ($type === 'inbound') {
                $nomorTrx = $transaksiModel->generateTransactionNumber('IN');
                $dataTransaksi = [
                    'transaction_number' => $nomorTrx,
                    'user_id'            => $userId,
                    'supplier_name'      => $act['supplier_name'] ?? 'Supplier Umum',
                    'transaction_date'   => date('Y-m-d H:i:s'),
                    'notes'              => $act['notes'] ?? 'Barang masuk sekaligus via AI Assistant',
                ];
                $dataDetail = [
                    'barang_id' => (int)$act['barang_id'],
                    'lokasi_id' => (int)$act['lokasi_id'],
                    'qty'       => (int)$act['qty'],
                ];
                $res = $transaksiModel->createTransaksiIn($dataTransaksi, $dataDetail);
                if ($res) {
                    $logModel->record('BARANG_MASUK', "AI Assistant (Batch): Transaksi masuk #$nomorTrx berhasil ke rak {$act['lokasi_kode']}. Qty: {$act['qty']}.", 'AI Assistant', $nomorTrx);
                    $stmtS = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
                    $stmtS->execute([$act['barang_id'], $act['lokasi_id']]);
                    $newQty = (int)($stmtS->fetchColumn() ?: 0);
                    $executed[] = array_merge($act, ['transaction_number' => $nomorTrx, 'updated_qty' => $newQty]);
                } else {
                    $failed[] = $act;
                }
            } elseif ($type === 'outbound') {
                $nomorTrx = $transaksiModel->generateTransactionNumber('OUT');
                $dataTransaksi = [
                    'transaction_number' => $nomorTrx,
                    'user_id'            => $userId,
                    'recipient_name'     => $act['recipient_name'] ?? 'Penerima Umum',
                    'transaction_date'   => date('Y-m-d H:i:s'),
                    'notes'              => $act['notes'] ?? 'Pengeluaran sekaligus via AI Assistant',
                ];
                $dataDetail = [
                    'barang_id' => (int)$act['barang_id'],
                    'lokasi_id' => (int)$act['lokasi_id'],
                    'qty'       => (int)$act['qty'],
                ];
                $res = $transaksiModel->createTransaksiOut($dataTransaksi, $dataDetail);
                if ($res) {
                    $logModel->record('BARANG_KELUAR', "AI Assistant (Batch): Transaksi keluar #$nomorTrx berhasil dari rak {$act['lokasi_kode']}. Qty: {$act['qty']}.", 'AI Assistant', $nomorTrx);
                    $stmtS = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
                    $stmtS->execute([$act['barang_id'], $act['lokasi_id']]);
                    $newQty = (int)($stmtS->fetchColumn() ?: 0);
                    $executed[] = array_merge($act, ['transaction_number' => $nomorTrx, 'updated_qty' => $newQty]);
                } else {
                    $failed[] = $act;
                }
            } elseif ($type === 'transfer') {
                $nomorTrx = $transaksiModel->generateTransactionNumber('TRF');
                $dataTransaksi = [
                    'transaction_number' => $nomorTrx,
                    'user_id'            => $userId,
                    'transaction_date'   => date('Y-m-d H:i:s'),
                    'notes'              => $act['notes'] ?? 'Pemindahan sekaligus via AI Assistant',
                ];
                $dataDetail = [
                    'barang_id'        => (int)$act['barang_id'],
                    'lokasi_id'        => (int)($act['lokasi_id'] ?? $act['lokasi_asal_id'] ?? 0),
                    'lokasi_tujuan_id' => (int)($act['lokasi_tujuan_id'] ?? $act['lokasi_ke_id'] ?? 0),
                    'qty'              => (int)$act['qty'],
                ];
                $res = $transaksiModel->createTransaksiTransfer($dataTransaksi, $dataDetail);
                if (!empty($res['success'])) {
                    $logModel->record('PEMINDAHAN_BARANG', "AI Assistant (Batch): Pemindahan #$nomorTrx berhasil dari rak {$act['lokasi_kode']} ke {$act['lokasi_tujuan_kode']}. Qty: {$act['qty']}.", 'AI Assistant', $nomorTrx);
                    $executed[] = array_merge($act, ['transaction_number' => $nomorTrx]);
                } else {
                    $failed[] = $act;
                }
            } elseif ($type === 'stock_adjustment') {
                $bId = (int)$act['barang_id'];
                $lId = (int)$act['lokasi_id'];
                $newQty = (int)$act['new_qty'];

                $stmtCheck = $this->db->prepare("SELECT id, qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
                $stmtCheck->execute([$bId, $lId]);
                $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $stmtUp = $this->db->prepare("UPDATE stok SET qty = ?, last_updated = NOW() WHERE id = ?");
                    $stmtUp->execute([$newQty, $existing['id']]);
                } else {
                    $stmtIns = $this->db->prepare("INSERT INTO stok (barang_id, lokasi_id, qty) VALUES (?, ?, ?)");
                    $stmtIns->execute([$bId, $lId, $newQty]);
                }

                $stmtLoc = $this->db->prepare("SELECT SUM(qty) FROM stok WHERE lokasi_id = ?");
                $stmtLoc->execute([$lId]);
                $totalLoc = (int)($stmtLoc->fetchColumn() ?: 0);
                $st = ($totalLoc > 0) ? 'terisi' : 'kosong';
                $stmtSt = $this->db->prepare("UPDATE lokasi SET status = ? WHERE id = ? AND status != 'tidak_aktif'");
                $stmtSt->execute([$st, $lId]);

                $logModel->record('STOCK_ADJUSTMENT', "AI Assistant (Batch): Koreksi stok barang {$act['barang_name']} di rak {$act['lokasi_kode']} menjadi $newQty.", 'AI Assistant', 'ADJ-BATCH-' . date('Ymd'));
                $executed[] = array_merge($act, ['updated_qty' => $newQty]);
            }
        }

        if (empty($executed)) {
            return ['success' => false, 'message' => 'Gagal memproses transaksi sekaligus.'];
        }

        $totalQty = 0;
        $itemsHtml = '';
        foreach ($executed as $ex) {
            $q = $ex['qty'] ?? $ex['new_qty'] ?? 0;
            $totalQty += $q;
            $trxLabel = !empty($ex['transaction_number']) ? '<span class="badge bg-light text-dark border font-monospace">' . htmlspecialchars($ex['transaction_number']) . '</span>' : '';
            $itemsHtml .= '<li class="list-group-item px-2 py-2 d-flex justify-content-between align-items-center">';
            $itemsHtml .= '<div>';
            $targetKode = $ex['lokasi_kode'] ?? $ex['lokasi_tujuan_kode'] ?? '';
            $itemsHtml .= '<span class="badge bg-dark font-monospace me-1">' . htmlspecialchars($targetKode) . '</span> ';
            $itemsHtml .= '<strong>+' . number_format($q) . ' ' . htmlspecialchars($ex['unit'] ?? '') . '</strong>';
            if (isset($ex['updated_qty'])) {
                $itemsHtml .= ' <small class="text-muted">(Total di rak: ' . number_format($ex['updated_qty']) . ')</small>';
            }
            $itemsHtml .= '</div>';
            $itemsHtml .= $trxLabel;
            $itemsHtml .= '</li>';
        }

        $firstItem = $executed[0];
        $html = '<div class="ai-success-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:34px; height:34px;">';
        $html .= '<i class="fas fa-check-double"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-success">Transaksi Sekaligus Berhasil Dieksekusi!</h6>';
        $html .= '<small class="text-muted">' . count($executed) . ' Transaksi Selesai Dicatat ke Sistem</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="mb-2 p-2 bg-light rounded-2 border small">';
        $html .= '<span class="text-muted">Material:</span> <strong>' . htmlspecialchars($firstItem['barang_name'] ?? '') . '</strong> ';
        $html .= '<span class="badge bg-primary-subtle text-primary font-monospace">[' . htmlspecialchars($firstItem['barang_code'] ?? '') . ']</span><br>';
        $html .= '<span class="text-muted">Total Keseluruhan:</span> <strong class="text-success fs-6">+' . number_format($totalQty) . ' ' . htmlspecialchars($firstItem['unit'] ?? '') . '</strong> (' . count($executed) . ' Rak)';
        $html .= '</div>';

        $html .= '<ul class="list-group list-group-flush small mb-3 border rounded-2">' . $itemsHtml . '</ul>';

        $html .= '<div class="d-flex justify-content-between align-items-center pt-1">';
        $html .= '<small class="text-muted"><i class="fas fa-shield-alt text-success me-1"></i>Stok & mutasi terupdate otomatis</small>';
        $html .= '<a href="' . BASE_URL . '/riwayat/index" class="btn btn-xs btn-outline-primary"><i class="fas fa-history me-1"></i>Buka Riwayat Transaksi</a>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'batch_success',
            'title' => 'Transaksi Sekaligus Berhasil',
            'message' => $html,
            'data' => [
                'count' => count($executed),
                'total_qty' => $totalQty
            ],
            'suggestions' => []
        ];
    }

    // =========================================================================
    //  LOW STOCK & WAREHOUSE STATS
    // =========================================================================

    public function getLowStock($responseMode = 'show_data') {
        $sql = "SELECT b.id, b.code, b.name, b.unit, b.min_stock, COALESCE(SUM(s.qty), 0) as total_qty 
                FROM barang b 
                LEFT JOIN stok s ON b.id = s.barang_id 
                WHERE b.status = 'active'
                GROUP BY b.id 
                HAVING total_qty <= b.min_stock 
                ORDER BY total_qty ASC LIMIT 10";
        $stmt = $this->db->query($sql);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($items)) {
            return [
                'success' => true,
                'type' => 'low_stock',
                'title' => 'Status Stok Gudang',
                'message' => '<div class="alert alert-success py-2 px-3 small rounded-2 mb-0">' .
                    '<i class="fas fa-check-circle me-1"></i> <strong>Semua stok dalam kondisi AMAN!</strong> Tidak ada material yang berada di bawah batas minimum stok.' .
                    '</div>'
            ];
        }

        $jumlah = count($items);

        // ── COUNT ONLY MODE ──────────────────────────────────────────────────
        if ($responseMode === 'count_only') {
            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-exclamation-triangle fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">Barang dengan stok kritis</div>';
            $html .= '<div class="fs-3 fw-bold text-danger lh-1">' . $jumlah . '</div>';
            $html .= '<div class="small text-muted mt-1">material di bawah minimum stok</div>';
            $html .= '</div></div>';
            $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan barang stok kritis&quot;</em> untuk melihat daftarnya.</div>';
            $html .= '</div>';
            return [
                'success' => true,
                'type'    => 'low_stock_count',
                'title'   => 'Jumlah Stok Kritis',
                'message' => $html
            ];
        }

        // ── SHOW DATA MODE (default) ─────────────────────────────────────────
        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold mb-2 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Daftar Material Stok Kritis (' . $jumlah . ')</h6>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-1 small">';
        $html .= '<thead class="table-light"><tr><th>Kode</th><th>Material</th><th class="text-end">Stok</th><th class="text-end">Min</th><th>Aksi</th></tr></thead><tbody>';

        foreach ($items as $it) {
            $inboundPrompt = "Terima 10 {$it['unit']} {$it['name']} di rak ";
            $html .= '<tr>';
            $html .= '<td><span class="badge bg-light text-dark border">' . htmlspecialchars($it['code']) . '</span></td>';
            $html .= '<td>' . htmlspecialchars($it['name']) . '</td>';
            $html .= '<td class="text-end fw-bold text-danger">' . number_format($it['total_qty']) . ' ' . htmlspecialchars($it['unit']) . '</td>';
            $html .= '<td class="text-end text-muted">' . number_format($it['min_stock']) . '</td>';
            $html .= '<td class="text-center"><button class="btn btn-xs btn-outline-success py-0 px-2 ai-quick-btn" data-query="' . htmlspecialchars($inboundPrompt) . '"><i class="fas fa-plus me-1"></i>Terima</button></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'low_stock',
            'title' => 'Material Stok Kritis',
            'message' => $html
        ];
    }

    public function getWarehouseSummary($responseMode = 'show_data') {
        $totalBarang = (int)$this->db->query("SELECT COUNT(*) FROM barang WHERE status = 'active'")->fetchColumn();
        $totalStok = (int)$this->db->query("SELECT COALESCE(SUM(qty), 0) FROM stok")->fetchColumn();
        $totalLokasi = (int)$this->db->query("SELECT COUNT(*) FROM lokasi")->fetchColumn();
        $lokasiTerisi = (int)$this->db->query("SELECT COUNT(*) FROM lokasi WHERE status = 'terisi'")->fetchColumn();
        $lokasiKosong = (int)$this->db->query("SELECT COUNT(*) FROM lokasi WHERE status = 'kosong'")->fetchColumn();
        $trxHariIni = (int)$this->db->query("SELECT COUNT(*) FROM transaksi WHERE DATE(transaction_date) = CURDATE()")->fetchColumn();

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold mb-3 text-dark"><i class="fas fa-tachometer-alt text-primary me-2"></i>Ringkasan Kondisi SmartWarehouse</h6>';
        $html .= '<div class="row g-2">';
        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border text-center">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Total Material</small>';
        $html .= '<strong class="fs-5 text-dark">' . number_format($totalBarang) . '</strong>';
        $html .= '</div></div>';
        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border text-center">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Total Unit Stok</small>';
        $html .= '<strong class="fs-5 text-primary">' . number_format($totalStok) . '</strong>';
        $html .= '</div></div>';
        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border text-center">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Rak Terisi / Total</small>';
        $html .= '<strong class="fs-5 text-success">' . number_format($lokasiTerisi) . '</strong> <small class="text-muted">/ ' . $totalLokasi . '</small>';
        $html .= '</div></div>';
        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border text-center">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Transaksi Hari Ini</small>';
        $html .= '<strong class="fs-5 text-warning">' . number_format($trxHariIni) . '</strong>';
        $html .= '</div></div>';
        $html .= '</div>';
        $html .= '<div class="mt-3 pt-2 border-top d-flex justify-content-between small text-muted">';
        $html .= '<span>Rak Kosong: <strong>' . $lokasiKosong . '</strong></span>';
        $html .= '<a href="' . BASE_URL . '/stok/index" class="text-decoration-none fw-bold">Detail Stok &rarr;</a>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'stats',
            'title' => 'Ringkasan Gudang',
            'message' => $html
        ];
    }

    public function getHelpResponse() {
        return $this->getJobdeskHelpResponse(['role_name' => $_SESSION['role_name'] ?? 'User']);
    }

    // =========================================================================
    //  PROMPT & TEMPLATE BUILDERS
    // =========================================================================

    private function getTransferInstructionGuide() {
        $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-exchange-alt me-2"></i>Panduan Pemindahan Material</h6>';
        $html .= '<p class="small text-muted mb-2">Pola kalimat pemindahan material:</p>';
        $html .= '<div class="alert alert-light border small py-2 px-3 mb-3 font-monospace">';
        $html .= 'Pindahkan [jumlah] [nama/kode material] dari [rak asal] ke [rak tujuan]';
        $html .= '</div>';
        $html .= '<button class="btn btn-sm btn-outline-primary text-start ai-quick-btn" data-query="Pindahkan 2 unit Laptop ASUS ROG Strix dari rak A.1.01 ke rak A.1.02"><i class="fas fa-arrow-right me-1"></i>Pindahkan 2 unit Laptop ASUS dari A.1.01 ke A.1.02</button>';
        $html .= '</div>';

        return ['success' => true, 'type' => 'guide', 'title' => 'Panduan Pemindahan', 'message' => $html];
    }

    private function getTransferClarificationPrompt($material, $fromRack, $toRack, $qty, $missing) {
        $html = '<div class="p-3 bg-white border rounded-3 shadow-sm">';
        $html .= '<h6 class="fw-bold text-warning mb-2"><i class="fas fa-info-circle me-2"></i>Rincian Belum Lengkap</h6>';
        $html .= '<p class="small text-muted mb-2">Lengkapi: <strong class="text-danger">' . implode(', ', $missing) . '</strong>.</p>';
        $html .= '<p class="small text-muted mb-0">Contoh lengkap: <em>\"Pindahkan 2 unit Laptop ASUS dari A.1.01 ke A.1.02\"</em></p>';
        $html .= '</div>';

        return ['success' => true, 'type' => 'clarification', 'title' => 'Lengkapi Pemindahan', 'message' => $html];
    }

    private function getMaterialSelectionPrompt($messageText) {
        $items = $this->db->query("SELECT id, code, name FROM barang WHERE status = 'active' LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

        $html = '<p class="mb-2">' . htmlspecialchars($messageText) . '</p>';
        $html .= '<div class="d-flex flex-wrap gap-2">';
        foreach ($items as $it) {
            $html .= '<button class="btn btn-sm btn-outline-primary ai-quick-btn" data-query="Dimana saja lokasi ' . htmlspecialchars($it['name']) . '">' .
                     '<i class="fas fa-box me-1"></i>' . htmlspecialchars($it['name']) . '</button>';
        }
        $html .= '</div>';

        return ['success' => true, 'type' => 'selection', 'title' => 'Pilih Material', 'message' => $html];
    }

    private function getRackSelectionPrompt($messageText) {
        $racks = $this->db->query("SELECT id, kode, nama, status FROM lokasi WHERE status != 'tidak_aktif' LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);

        $html = '<p class="mb-2">' . htmlspecialchars($messageText) . '</p>';
        $html .= '<div class="d-flex flex-wrap gap-2">';
        foreach ($racks as $r) {
            $badgeClass = ($r['status'] === 'terisi') ? 'btn-outline-success' : 'btn-outline-secondary';
            $html .= '<button class="btn btn-sm ' . $badgeClass . ' ai-quick-btn" data-query="Ada berapakah qty pada rak ' . htmlspecialchars($r['kode']) . '">' .
                     '<i class="fas fa-map-marker-alt me-1"></i>' . htmlspecialchars($r['kode']) . '</button>';
        }
        $html .= '</div>';

        return ['success' => true, 'type' => 'selection', 'title' => 'Pilih Rak Gudang', 'message' => $html];
    }

    // =========================================================================
    //  DATABASE & UTILITY HELPERS
    // =========================================================================

    private function searchBarang($term) {
        $term = trim($term);
        if (empty($term)) return [];

        $sql = "SELECT id, code, name, unit, min_stock FROM barang 
                WHERE status = 'active' AND (code LIKE :search OR name LIKE :search) 
                ORDER BY (name = :exact) DESC, (code = :exactCode) DESC, name ASC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':search', '%' . $term . '%');
        $stmt->bindValue(':exact', $term);
        $stmt->bindValue(':exactCode', strtoupper($term));
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function findBarangNameInText($text) {
        $barangList = $this->db->query("SELECT name, code FROM barang WHERE status = 'active'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($barangList as $b) {
            if (mb_stripos($text, mb_strtolower($b['name'])) !== false) return $b['name'];
            if (mb_stripos($text, mb_strtolower($b['code'])) !== false) return $b['code'];
            $words = explode(' ', $b['name']);
            if (count($words) >= 2) {
                $sub = mb_strtolower($words[0] . ' ' . $words[1]);
                if (mb_stripos($text, $sub) !== false) return $b['name'];
            }
        }
        return '';
    }

    private function extractRackCode($text) {
        if (preg_match('/\b([A-Z])\s*[\.\-]?\s*(\d{1,2})\s*[\.\-]?\s*(\d{1,2})\b/i', $text, $m)) {
            return strtoupper($m[1]) . '.' . intval($m[2]) . '.' . str_pad(intval($m[3]), 2, '0', STR_PAD_LEFT);
        }
        return '';
    }

    private function normalizeRackCode($code) {
        $code = trim($code);
        if (preg_match('/([A-Z])\s*[\.\-]?\s*(\d{1,2})\s*[\.\-]?\s*(\d{1,2})/i', $code, $m)) {
            return strtoupper($m[1]) . '.' . intval($m[2]) . '.' . str_pad(intval($m[3]), 2, '0', STR_PAD_LEFT);
        }
        return $code;
    }

    private function findLokasiExactOrLike($term) {
        $term = trim($term);
        if (empty($term)) return null;

        $normalized = $this->normalizeRackCode($term);
        $stmt = $this->db->prepare("SELECT * FROM lokasi WHERE kode = :kode LIMIT 1");
        $stmt->execute([':kode' => strtoupper($normalized)]);
        $loc = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($loc) return $loc;

        $stmt2 = $this->db->prepare("SELECT * FROM lokasi WHERE nama LIKE :term LIMIT 1");
        $stmt2->execute([':term' => '%' . $term . '%']);
        return $stmt2->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function findLokasiByKeyword($clean) {
        $lokasiList = $this->db->query("SELECT id, kode, nama FROM lokasi WHERE status != 'tidak_aktif'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lokasiList as $l) {
            if (mb_stripos($clean, mb_strtolower($l['kode'])) !== false) return $l;
            if (mb_stripos($clean, mb_strtolower($l['nama'])) !== false) return $l;
        }
        return null;
    }

    public function extractAllRackCodes($text) {
        $racks = [];
        if (preg_match_all('/\b([A-Z])\s*[\.\-]?\s*(\d{1,2})\s*[\.\-]?\s*(\d{1,2})\b/i', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $code = strtoupper($m[1]) . '.' . intval($m[2]) . '.' . str_pad(intval($m[3]), 2, '0', STR_PAD_LEFT);
                if (!in_array($code, $racks)) {
                    $racks[] = $code;
                }
            }
        }
        return $racks;
    }

    public function extractMaterialTerm($text) {
        if (preg_match('/\b([0-9]{6,15}|BRG-[A-Z0-9\-]+)\b/i', $text, $m)) {
            $mFound = $this->searchBarang($m[1]);
            if (!empty($mFound)) return $m[1];
        }

        $name = $this->findBarangNameInText($text);
        if (!empty($name)) return $name;

        if (preg_match('/(?:input|masuk|terima|keluarkan|keluar|pindah|pindahkan)\s+(?:barang|material|produk)?\s*(?:ini\s+)?(.+?)\s+(?:pada\s+rak|di\s+rak|dari\s+rak|ke\s+rak|dengan\s+qty|qty)/i', $text, $m)) {
            $cand = trim($m[1]);
            if (!in_array(strtolower($cand), ['barang', 'material', 'produk', 'ini', 'barang ini', 'material ini'])) {
                return $cand;
            }
        }

        $tokens = preg_split('/[\s,\.\;\:\(\)]+/', $text);
        foreach ($tokens as $tk) {
            $tk = trim($tk);
            if (strlen($tk) >= 4) {
                $matches = $this->searchBarang($tk);
                if (!empty($matches)) return $matches[0]['code'];
            }
        }

        return '';
    }

    public function parseQty($text) {
        if (preg_match('/\b(?:qty|jumlah|sebanyak)\s*[:=]?\s*(\d+)\b/i', $text, $m)) {
            return (int)$m[1];
        }
        if (preg_match('/\b(\d+)\s*(?:unit|pcs|box|kg|rim|item|dus|sak|meter|liter|rol)\b/i', $text, $m)) {
            return (int)$m[1];
        }
        if (preg_match('/\b(?:terima|masuk|catat|keluar|pindah|input)\s+(\d+)\b/i', $text, $m)) {
            return (int)$m[1];
        }
        return 1;
    }

    public function handleBatchInboundMultiRack($materialTerm, array $rackCodes, $qtyPerRack, $supplier, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan dalam sistem database gudang.</p>",
                'suggestions' => []
            ];
        }
        $barang = $barangMatches[0];

        $subActions = [];
        $rowsHtml = '';
        $totalQty = 0;

        foreach ($rackCodes as $rCode) {
            $lokasi = $this->findLokasiExactOrLike($rCode);
            if (!$lokasi) {
                return [
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Rak Tidak Ditemukan',
                    'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak tujuan <strong>\"" . htmlspecialchars($rCode) . "\"</strong> tidak ditemukan.</p>",
                    'suggestions' => []
                ];
            }

            if ($lokasi['status'] === 'tidak_aktif') {
                return [
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Rak Tidak Aktif',
                    'message' => "<p class='text-danger mb-0'><i class='fas fa-ban me-1'></i> Rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> berstatus tidak aktif.</p>",
                    'suggestions' => []
                ];
            }

            $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
            $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
            $newTotal = $currentQty + $qtyPerRack;
            $totalQty += $qtyPerRack;

            $subActions[] = [
                'action_type'   => 'inbound',
                'barang_id'     => (int)$barang['id'],
                'barang_name'   => $barang['name'],
                'barang_code'   => $barang['code'],
                'lokasi_id'     => (int)$lokasi['id'],
                'lokasi_kode'   => $lokasi['kode'],
                'lokasi_nama'   => $lokasi['nama'],
                'qty'           => (int)$qtyPerRack,
                'unit'          => $barang['unit'],
                'supplier_name' => $supplier,
                'notes'         => $notes,
                'current_qty'   => $currentQty,
                'new_qty'       => $newTotal
            ];

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td><span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span> <small class="text-muted d-block">' . htmlspecialchars($lokasi['nama']) . '</small></td>';
            $rowsHtml .= '<td class="text-center fw-bold text-success">+' . number_format($qtyPerRack) . ' ' . htmlspecialchars($barang['unit']) . '</td>';
            $rowsHtml .= '<td class="text-end small"><span class="text-muted">' . number_format($currentQty) . '</span> &rarr; <strong class="text-primary">' . number_format($newTotal) . ' ' . htmlspecialchars($barang['unit']) . '</strong></td>';
            $rowsHtml .= '</tr>';
        }

        $batchPayload = [
            'action_type' => 'batch',
            'batch_type'  => 'inbound',
            'sub_actions' => $subActions,
            'summary'     => [
                'barang_id'   => (int)$barang['id'],
                'barang_name' => $barang['name'],
                'barang_code' => $barang['code'],
                'total_qty'   => $totalQty,
                'rack_count'  => count($subActions),
                'unit'        => $barang['unit']
            ]
        ];

        $html = '<div class="ai-action-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-layer-group"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-success">Konfirmasi Transaksi Masuk Sekaligus (' . count($subActions) . ' Rak)</h6>';
        $html .= '<small class="text-muted">Jobdesk: Penerimaan & Penempatan Multi-Rak Sekaligus</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="p-2 bg-light rounded-2 border mb-2">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div>';

        $html .= '<div class="table-responsive mb-2">';
        $html .= '<table class="table table-sm table-bordered align-middle mb-0 small">';
        $html .= '<thead class="table-light"><tr><th>Rak Tujuan</th><th class="text-center">Kuantitas</th><th class="text-end">Stok Rak</th></tr></thead>';
        $html .= '<tbody>' . $rowsHtml . '</tbody>';
        $html .= '<tfoot class="table-light"><tr><th class="fw-bold">Total (' . count($subActions) . ' Rak)</th><th class="text-center text-success fw-bold">+' . number_format($totalQty) . ' ' . htmlspecialchars($barang['unit']) . '</th><th class="text-end">-</th></tr></tfoot>';
        $html .= '</table>';
        $html .= '</div>';

        if (!empty($supplier) && $supplier !== 'Supplier Umum') {
            $html .= '<div class="small text-muted mb-2"><i class="fas fa-truck me-1"></i> Supplier: <strong>' . htmlspecialchars($supplier) . '</strong></div>';
        }

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-success btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($batchPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-double me-1"></i>Konfirmasi & Eksekusi Semua Transaksi Sekaligus</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'batch_action_preview',
            'title' => 'Konfirmasi Transaksi Sekaligus',
            'message' => $html,
            'data' => $batchPayload,
            'suggestions' => []
        ];
    }

    public function handleBatchOutboundMultiRack($materialTerm, array $rackCodes, $qtyPerRack, $recipient, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => []
            ];
        }
        $barang = $barangMatches[0];

        $subActions = [];
        $rowsHtml = '';
        $totalQty = 0;

        foreach ($rackCodes as $rCode) {
            $lokasi = $this->findLokasiExactOrLike($rCode);
            if (!$lokasi) {
                return [
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Rak Asal Tidak Ditemukan',
                    'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak asal <strong>\"" . htmlspecialchars($rCode) . "\"</strong> tidak ditemukan.</p>",
                    'suggestions' => []
                ];
            }

            $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
            $currentStock = (int)($stmtCurrent->fetchColumn() ?: 0);

            if ($currentStock < $qtyPerRack) {
                return [
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Stok Tidak Mencukupi',
                    'message' => "<p class='text-danger mb-0'><i class='fas fa-exclamation-triangle me-1'></i> Stok di rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> hanya tersisa <strong>" . $currentStock . " " . htmlspecialchars($barang['unit']) . "</strong>, tidak mencukupi permintaan " . $qtyPerRack . " " . htmlspecialchars($barang['unit']) . ".</p>",
                    'suggestions' => []
                ];
            }

            $newStock = $currentStock - $qtyPerRack;
            $totalQty += $qtyPerRack;

            $subActions[] = [
                'action_type'    => 'outbound',
                'barang_id'      => (int)$barang['id'],
                'barang_name'    => $barang['name'],
                'barang_code'    => $barang['code'],
                'lokasi_id'      => (int)$lokasi['id'],
                'lokasi_kode'    => $lokasi['kode'],
                'lokasi_nama'    => $lokasi['nama'],
                'qty'            => (int)$qtyPerRack,
                'unit'           => $barang['unit'],
                'recipient_name' => $recipient,
                'notes'          => $notes,
                'current_qty'    => $currentStock,
                'new_qty'        => $newStock
            ];

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td><span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span> <small class="text-muted d-block">' . htmlspecialchars($lokasi['nama']) . '</small></td>';
            $rowsHtml .= '<td class="text-center fw-bold text-danger">-' . number_format($qtyPerRack) . ' ' . htmlspecialchars($barang['unit']) . '</td>';
            $rowsHtml .= '<td class="text-end small"><span class="text-muted">' . number_format($currentStock) . '</span> &rarr; <strong class="text-danger">' . number_format($newStock) . ' ' . htmlspecialchars($barang['unit']) . '</strong></td>';
            $rowsHtml .= '</tr>';
        }

        $batchPayload = [
            'action_type' => 'batch',
            'batch_type'  => 'outbound',
            'sub_actions' => $subActions,
            'summary'     => [
                'barang_id'   => (int)$barang['id'],
                'barang_name' => $barang['name'],
                'barang_code' => $barang['code'],
                'total_qty'   => $totalQty,
                'rack_count'  => count($subActions),
                'unit'        => $barang['unit']
            ]
        ];

        $html = '<div class="ai-action-card border border-danger-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-danger text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-layer-group"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-danger">Konfirmasi Pengeluaran Barang Sekaligus (' . count($subActions) . ' Rak)</h6>';
        $html .= '<small class="text-muted">Jobdesk: Pengeluaran Multi-Rak Sekaligus</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="p-2 bg-light rounded-2 border mb-2">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div>';

        $html .= '<div class="table-responsive mb-2">';
        $html .= '<table class="table table-sm table-bordered align-middle mb-0 small">';
        $html .= '<thead class="table-light"><tr><th>Rak Asal</th><th class="text-center">Kuantitas</th><th class="text-end">Sisa Stok</th></tr></thead>';
        $html .= '<tbody>' . $rowsHtml . '</tbody>';
        $html .= '<tfoot class="table-light"><tr><th class="fw-bold">Total (' . count($subActions) . ' Rak)</th><th class="text-center text-danger fw-bold">-' . number_format($totalQty) . ' ' . htmlspecialchars($barang['unit']) . '</th><th class="text-end">-</th></tr></tfoot>';
        $html .= '</table>';
        $html .= '</div>';

        if (!empty($recipient) && $recipient !== 'Penerima Umum') {
            $html .= '<div class="small text-muted mb-2"><i class="fas fa-user-tag me-1"></i> Penerima: <strong>' . htmlspecialchars($recipient) . '</strong></div>';
        }

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-danger btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($batchPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-double me-1"></i>Konfirmasi & Eksekusi Semua Pengeluaran Sekaligus</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'batch_action_preview',
            'title' => 'Konfirmasi Pengeluaran Sekaligus',
            'message' => $html,
            'data' => $batchPayload,
            'suggestions' => []
        ];
    }

    public function handleBatchTransferMultiRack($materialTerm, $fromRack, array $toRacks, $qtyPerRack, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => []
            ];
        }
        $barang = $barangMatches[0];

        $lokasiAsal = $this->findLokasiExactOrLike($fromRack);
        if (!$lokasiAsal) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Asal Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak asal <strong>\"" . htmlspecialchars($fromRack) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => []
            ];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasiAsal['id']]);
        $currentStockAsal = (int)($stmtCurrent->fetchColumn() ?: 0);

        $totalNeeded = $qtyPerRack * count($toRacks);
        if ($currentStockAsal < $totalNeeded) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Rak Asal Tidak Mencukupi',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-exclamation-triangle me-1'></i> Stok di rak asal <strong>" . htmlspecialchars($lokasiAsal['kode']) . "</strong> hanya tersisa <strong>" . $currentStockAsal . " " . htmlspecialchars($barang['unit']) . "</strong>, tidak mencukupi untuk total pemindahan ke " . count($toRacks) . " rak (butuh " . $totalNeeded . " " . htmlspecialchars($barang['unit']) . ").</p>",
                'suggestions' => []
            ];
        }

        $subActions = [];
        $rowsHtml = '';

        foreach ($toRacks as $tRCode) {
            $lokasiTujuan = $this->findLokasiExactOrLike($tRCode);
            if (!$lokasiTujuan) {
                return [
                    'success' => false,
                    'type' => 'error',
                    'title' => 'Rak Tujuan Tidak Ditemukan',
                    'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak tujuan <strong>\"" . htmlspecialchars($tRCode) . "\"</strong> tidak ditemukan.</p>",
                    'suggestions' => []
                ];
            }

            $stmtTujuan = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtTujuan->execute([$barang['id'], $lokasiTujuan['id']]);
            $currentTujuan = (int)($stmtTujuan->fetchColumn() ?: 0);
            $newTujuan = $currentTujuan + $qtyPerRack;

            $subActions[] = [
                'action_type'         => 'transfer',
                'barang_id'           => (int)$barang['id'],
                'barang_name'         => $barang['name'],
                'barang_code'         => $barang['code'],
                'lokasi_id'           => (int)$lokasiAsal['id'],
                'lokasi_kode'         => $lokasiAsal['kode'],
                'lokasi_nama'         => $lokasiAsal['nama'],
                'lokasi_tujuan_id'    => (int)$lokasiTujuan['id'],
                'lokasi_tujuan_kode'  => $lokasiTujuan['kode'],
                'lokasi_tujuan_nama'  => $lokasiTujuan['nama'],
                'qty'                 => (int)$qtyPerRack,
                'unit'                => $barang['unit'],
                'notes'               => $notes
            ];

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td><span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasiTujuan['kode']) . '</span> <small class="text-muted d-block">' . htmlspecialchars($lokasiTujuan['nama']) . '</small></td>';
            $rowsHtml .= '<td class="text-center fw-bold text-primary">' . number_format($qtyPerRack) . ' ' . htmlspecialchars($barang['unit']) . '</td>';
            $rowsHtml .= '<td class="text-end small"><span class="text-muted">' . number_format($currentTujuan) . '</span> &rarr; <strong class="text-success">' . number_format($newTujuan) . '</strong></td>';
            $rowsHtml .= '</tr>';
        }

        $batchPayload = [
            'action_type' => 'batch',
            'batch_type'  => 'transfer',
            'sub_actions' => $subActions,
            'summary'     => [
                'barang_id'   => (int)$barang['id'],
                'barang_name' => $barang['name'],
                'barang_code' => $barang['code'],
                'total_qty'   => $totalNeeded,
                'from_rack'   => $lokasiAsal['kode'],
                'rack_count'  => count($subActions),
                'unit'        => $barang['unit']
            ]
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-primary text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-layer-group"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-primary">Konfirmasi Pemindahan Sekaligus (' . count($subActions) . ' Rak)</h6>';
        $html .= '<small class="text-muted">Jobdesk: Mutasi Stok Multi-Rak Sekaligus</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="p-2 bg-light rounded-2 border mb-2">';
        $html .= '<div class="d-flex justify-content-between align-items-center">';
        $html .= '<div>';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div>';
        $html .= '<div class="text-end">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Dari Rak Asal</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasiAsal['kode']) . '</span> (Sisa: ' . ($currentStockAsal - $totalNeeded) . ' ' . htmlspecialchars($barang['unit']) . ')';
        $html .= '</div></div></div>';

        $html .= '<div class="table-responsive mb-2">';
        $html .= '<table class="table table-sm table-bordered align-middle mb-0 small">';
        $html .= '<thead class="table-light"><tr><th>Rak Tujuan</th><th class="text-center">Kuantitas Pindah</th><th class="text-end">Stok Rak Tujuan</th></tr></thead>';
        $html .= '<tbody>' . $rowsHtml . '</tbody>';
        $html .= '<tfoot class="table-light"><tr><th class="fw-bold">Total Pindah (' . count($subActions) . ' Rak)</th><th class="text-center text-primary fw-bold">' . number_format($totalNeeded) . ' ' . htmlspecialchars($barang['unit']) . '</th><th class="text-end">-</th></tr></tfoot>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($batchPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-double me-1"></i>Konfirmasi & Eksekusi Semua Pemindahan Sekaligus</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'batch_action_preview',
            'title' => 'Konfirmasi Pemindahan Sekaligus',
            'message' => $html,
            'data' => $batchPayload,
            'suggestions' => []
        ];
    }

    public function handleBatchStockAdjustmentMultiRack($materialTerm, array $rackQtyMap, $reason, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => []
            ];
        }
        $barang = $barangMatches[0];

        $subActions = [];
        $rowsHtml = '';

        foreach ($rackQtyMap as $rCode => $newQty) {
            $lokasi = $this->findLokasiExactOrLike($rCode);
            if (!$lokasi) continue;

            $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
            $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
            $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
            $diff = $newQty - $currentQty;
            $diffText = ($diff >= 0 ? '+' : '') . $diff;

            $subActions[] = [
                'action_type'   => 'stock_adjustment',
                'barang_id'     => (int)$barang['id'],
                'barang_name'   => $barang['name'],
                'barang_code'   => $barang['code'],
                'lokasi_id'     => (int)$lokasi['id'],
                'lokasi_kode'   => $lokasi['kode'],
                'lokasi_nama'   => $lokasi['nama'],
                'new_qty'       => (int)$newQty,
                'current_qty'   => $currentQty,
                'diff'          => $diff,
                'unit'          => $barang['unit'],
                'reason'        => $reason
            ];

            $rowsHtml .= '<tr>';
            $rowsHtml .= '<td><span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span></td>';
            $rowsHtml .= '<td class="text-muted">' . number_format($currentQty) . ' ' . htmlspecialchars($barang['unit']) . '</td>';
            $rowsHtml .= '<td class="fw-bold text-warning">' . number_format($newQty) . ' ' . htmlspecialchars($barang['unit']) . '</td>';
            $rowsHtml .= '<td class="text-end ' . ($diff >= 0 ? 'text-success' : 'text-danger') . ' fw-bold">' . $diffText . '</td>';
            $rowsHtml .= '</tr>';
        }

        if (empty($subActions)) {
            return ['success' => false, 'message' => 'Tidak ada rak valid untuk penyesuaian stok.'];
        }

        $batchPayload = [
            'action_type' => 'batch',
            'batch_type'  => 'stock_adjustment',
            'sub_actions' => $subActions
        ];

        $html = '<div class="ai-action-card border border-warning-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-warning text-dark rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-layer-group"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-dark">Konfirmasi Koreksi Stok Sekaligus (' . count($subActions) . ' Rak)</h6>';
        $html .= '<small class="text-muted">Jobdesk: Stock Opname & Penyesuaian Multi-Rak</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="p-2 bg-light rounded-2 border mb-2">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div>';

        $html .= '<div class="table-responsive mb-2">';
        $html .= '<table class="table table-sm table-bordered align-middle mb-0 small">';
        $html .= '<thead class="table-light"><tr><th>Rak</th><th>Stok Sistem</th><th>Fisik Baru</th><th class="text-end">Selisih</th></tr></thead>';
        $html .= '<tbody>' . $rowsHtml . '</tbody>';
        $html .= '</table>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-warning btn-sm flex-grow-1 fw-bold ai-execute-action-btn text-dark" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($batchPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-double me-1"></i>Konfirmasi & Eksekusi Koreksi Stok Sekaligus</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'batch_action_preview',
            'title' => 'Konfirmasi Koreksi Stok Sekaligus',
            'message' => $html,
            'data' => $batchPayload,
            'suggestions' => []
        ];
    }

    public function handleBatchWithParams($params, $userContext) {
        $trxType = $params['transaction_type'] ?? 'inbound';
        $items = $params['items'] ?? $params['transactions'] ?? [];

        if (empty($items)) {
            $mat = $params['material'] ?? '';
            $racks = $params['racks'] ?? [];
            if (!empty($mat) && !empty($racks) && is_array($racks)) {
                $qty = (int)($params['qty'] ?? 1);
                if ($trxType === 'outbound') {
                    return $this->handleBatchOutboundMultiRack($mat, $racks, $qty, $params['recipient'] ?? 'Penerima Umum', $params['notes'] ?? 'Pengeluaran sekaligus via AI', $userContext);
                } else {
                    return $this->handleBatchInboundMultiRack($mat, $racks, $qty, $params['supplier'] ?? 'Supplier Umum', $params['notes'] ?? 'Barang masuk sekaligus via AI', $userContext);
                }
            }
            return null;
        }

        $topMaterial = $params['material'] ?? '';

        if ($trxType === 'inbound') {
            $rackCodes = [];
            $qtyPerRack = 0;
            foreach ($items as $it) {
                $r = $it['rack'] ?? $it['lokasi_kode'] ?? '';
                if ($r) $rackCodes[] = $this->normalizeRackCode($r);
                if (isset($it['qty']) && (int)$it['qty'] > 0) $qtyPerRack = (int)$it['qty'];
                if (empty($topMaterial) && !empty($it['material'])) $topMaterial = $it['material'];
            }
            if (!empty($topMaterial) && !empty($rackCodes)) {
                return $this->handleBatchInboundMultiRack($topMaterial, $rackCodes, $qtyPerRack ?: 1, $params['supplier'] ?? 'Supplier Umum', $params['notes'] ?? 'Barang masuk sekaligus via Gemini AI', $userContext);
            }
        } elseif ($trxType === 'outbound') {
            $rackCodes = [];
            $qtyPerRack = 0;
            foreach ($items as $it) {
                $r = $it['rack'] ?? $it['lokasi_kode'] ?? '';
                if ($r) $rackCodes[] = $this->normalizeRackCode($r);
                if (isset($it['qty']) && (int)$it['qty'] > 0) $qtyPerRack = (int)$it['qty'];
                if (empty($topMaterial) && !empty($it['material'])) $topMaterial = $it['material'];
            }
            if (!empty($topMaterial) && !empty($rackCodes)) {
                return $this->handleBatchOutboundMultiRack($topMaterial, $rackCodes, $qtyPerRack ?: 1, $params['recipient'] ?? 'Penerima Umum', $params['notes'] ?? 'Barang keluar sekaligus via Gemini AI', $userContext);
            }
        } elseif ($trxType === 'transfer') {
            $fromRack = '';
            $toRacks = [];
            $qtyPerRack = 0;
            foreach ($items as $it) {
                if (empty($fromRack) && !empty($it['from_rack'])) $fromRack = $this->normalizeRackCode($it['from_rack']);
                $to = $it['to_rack'] ?? $it['rack'] ?? '';
                if ($to) $toRacks[] = $this->normalizeRackCode($to);
                if (isset($it['qty']) && (int)$it['qty'] > 0) $qtyPerRack = (int)$it['qty'];
                if (empty($topMaterial) && !empty($it['material'])) $topMaterial = $it['material'];
            }
            if (!empty($topMaterial) && !empty($fromRack) && !empty($toRacks)) {
                return $this->handleBatchTransferMultiRack($topMaterial, $fromRack, $toRacks, $qtyPerRack ?: 1, $params['notes'] ?? 'Pemindahan sekaligus via Gemini AI', $userContext);
            }
        } elseif ($trxType === 'stock_adjustment') {
            $map = [];
            foreach ($items as $it) {
                $r = $it['rack'] ?? $it['lokasi_kode'] ?? '';
                if ($r && isset($it['new_qty'])) {
                    $map[$this->normalizeRackCode($r)] = (int)$it['new_qty'];
                }
                if (empty($topMaterial) && !empty($it['material'])) $topMaterial = $it['material'];
            }
            if (!empty($topMaterial) && !empty($map)) {
                return $this->handleBatchStockAdjustmentMultiRack($topMaterial, $map, $params['reason'] ?? 'Koreksi stok sekaligus via Gemini AI', $userContext);
            }
        }

        return null;
    }

    public function handleMultiRackFromText($rawMessage, $clean, $userContext) {
        $racks = $this->extractAllRackCodes($rawMessage);
        if (count($racks) <= 1) {
            return null;
        }

        $materialTerm = $this->extractMaterialTerm($rawMessage);
        if (empty($materialTerm)) {
            $materialTerm = $this->findBarangNameInText($clean);
        }

        if (empty($materialTerm)) {
            return null;
        }

        $parsedQty = $this->parseQty($rawMessage);
        $isTotal = (bool)preg_match('/\b(total|dibagi|bagi rata)\b/i', $clean);
        $qtyPerRack = ($isTotal && count($racks) > 1) ? max(1, (int)round($parsedQty / count($racks))) : $parsedQty;

        if (preg_match('/\b(keluar|keluarkan|pengeluaran|kirim)\b/i', $clean)) {
            return $this->handleBatchOutboundMultiRack($materialTerm, $racks, $qtyPerRack, 'Penerima Umum', 'Pengeluaran sekaligus via AI Assistant', $userContext);
        } elseif (preg_match('/\b(pindah|pindahkan|transfer|geser|mutasi)\b/i', $clean)) {
            $fromRack = '';
            if (preg_match('/\bdari\s+(?:rak\s+)?([A-Z]\s*[\.\-]?\s*\d{1,2}\s*[\.\-]?\s*\d{1,2})\b/i', $rawMessage, $mFrom)) {
                $fromRack = $this->normalizeRackCode($mFrom[1]);
            }
            $toRacks = array_values(array_filter($racks, fn($r) => $r !== $fromRack));
            if (!empty($fromRack) && !empty($toRacks)) {
                return $this->handleBatchTransferMultiRack($materialTerm, $fromRack, $toRacks, $qtyPerRack, 'Pemindahan sekaligus via AI Assistant', $userContext);
            }
        } elseif (preg_match('/\b(sesuaikan|koreksi|adjust|opname|audit)\b/i', $clean)) {
            $map = [];
            foreach ($racks as $r) {
                $map[$r] = $qtyPerRack;
            }
            return $this->handleBatchStockAdjustmentMultiRack($materialTerm, $map, 'Koreksi stok sekaligus via AI Assistant', $userContext);
        } else {
            return $this->handleBatchInboundMultiRack($materialTerm, $racks, $qtyPerRack, 'Supplier Umum', 'Barang masuk sekaligus via AI Assistant', $userContext);
        }

        return null;
    }

    private function getSampleBarangSuggestions() {
        return [];
    }

    private function getSampleRackSuggestions() {
        return [];
    }

    private function getDefaultSuggestions() {
        return [];
    }

    /**
     * Check if user query is completely unrelated to SmartWarehouse operations
     */
    public function isUnrelatedQuery($rawMessage, $clean) {
        $clean = trim($clean);

        // Whitelist all database and SQL related queries - NEVER treat database inquiries as unrelated!
        if ($this->isDatabaseQueryKeyword($clean)) {
            return false;
        }

        // 1. Programming & Coding requests (e.g. "buatkan coding python", "bikin kodingan", etc.)
        if (preg_match('/\b(coding|koding|source code|pemrograman|script|skrip|kodingan)\b/i', $clean)) {
            return true;
        }
        if (preg_match('/\b(buatkan|bikin|tuliskan|tulis|buat|ajarkan|contoh)\s+(program|aplikasi|website|web|game|bot|software|coding|koding|code|skrip|script)\b/i', $clean)) {
            return true;
        }
        if (preg_match('/\b(python|typescript|java|c\+\+|c\#|golang|ruby|rust|swift|kotlin)\b/i', $clean)) {
            // Only allowed if user is querying a product or rack
            if (!preg_match('/\b(rak|lokasi|stok|terima|keluar|pindah|masuk|opname|audit)\b/i', $clean)) {
                return true;
            }
        }

        // 2. Cooking / Food Recipes
        if (preg_match('/\b(resep|cara\s+memasak|cara\s+masak|bikin\s+kue|resep\s+makanan|bumbu\s+dapur|nasi\s+goreng)\b/i', $clean)) {
            if (!preg_match('/\b(rak|lokasi|stok|terima|keluar|pindah|masuk|opname|audit)\b/i', $clean)) {
                return true;
            }
        }

        // 3. Weather / Forecast
        if (preg_match('/\b(cuaca\s+hari\s+ini|ramalan\s+cuaca|prakiraan\s+cuaca|suhu\s+udara|hujan\s+hari\s+ini)\b/i', $clean)) {
            return true;
        }

        // 4. Entertainment, Jokes, Poems, Stories, Lyrics
        if (preg_match('/\b(cerita\s+lucu|lelucon|tebak-tebakan|pantun|buatkan\s+puisi|cerpen|dongeng|lirik\s+lagu|chord\s+gitar)\b/i', $clean)) {
            return true;
        }

        // 5. Academic / Homework / Non-warehouse general knowledge
        if (preg_match('/\b(tugas\s+sekolah|pr\s+matematika|rumus\s+fisika|rumus\s+kimia|tata\s+surya|sejarah\s+kemerdekaan|ibukota\s+negara)\b/i', $clean)) {
            return true;
        }

        // 6. Politics, Celebrities, Sports
        if (preg_match('/\b(siapa\s+presiden|menteri|partai\s+politik|pemilu|pilpres|gosip\s+artis|artis\s+indonesia|sepak\s+bola|klasemen\s+liga|piala\s+dunia|skor\s+bola)\b/i', $clean)) {
            return true;
        }

        // 7. Horoscope, Dating, General Trivia
        if (preg_match('/\b(zodiak|ramalan\s+bintang|cara\s+cari\s+pacar|jodoh|cheat\s+game)\b/i', $clean)) {
            return true;
        }

        // 8. Translation requests
        if (preg_match('/\b(terjemahkan|translate)\s+(ke|to)\s+(bahasa|english|inggris|indonesia)\b/i', $clean)) {
            return true;
        }

        return false;
    }

    /**
     * Standard response when user query is outside SmartWarehouse scope
     */
    public function getUnrelatedResponse($customMessage = null) {
        $msg = !empty($customMessage) 
            ? htmlspecialchars($customMessage) 
            : 'Maaf, permintaan ini tidak berhubungan dengan operasional SmartWarehouse sehingga tidak dapat dijawab. Saya adalah asisten yang didedikasikan khusus untuk transaksi dan operasional pergudangan SmartWarehouse.';

        return [
            'success' => false,
            'type' => 'unrelated_query',
            'title' => 'Di Luar Cakupan SmartWarehouse',
            'message' => '<div class="alert alert-secondary py-2 px-3 small mb-0"><i class="fas fa-ban text-muted me-1"></i> ' . $msg . '</div>',
            'suggestions' => [
                'Panduan Jobdesk',
                'Tampilkan barang dengan stok kritis',
                'Tampilkan rak-rak yang masih kosong',
                'Terima 10 box Susu UHT di rak B.2.01 dari PT Indofood',
                'Keluarkan 2 box Susu UHT dari rak B.2.01 untuk Toko ABC'
            ]
        ];
    }

    // =========================================================================
    //  GEMINI AI ENGINE & API INTEGRATION
    // =========================================================================

    public function getGeminiApiKey() {
        $setting = new Setting();
        $key = $setting->getGeminiApiKey();
        if (!empty($key) && $key !== 'gen-lang-client-0527739822') {
            return $key;
        }
        if (defined('GEMINI_API_KEY')) {
            $c = constant('GEMINI_API_KEY');
            if (!empty($c) && $c !== 'YOUR_GEMINI_API_KEY_HERE' && $c !== 'gen-lang-client-0527739822') {
                return $c;
            }
        }
        return '';
    }

    public function getGeminiModel() {
        $setting = new Setting();
        return $setting->getGeminiModel() ?: 'gemini-3.5-flash-lite';
    }

    public function isGeminiReady() {
        $key = $this->getGeminiApiKey();
        return !empty($key) && strlen($key) > 10;
    }

    public function testGeminiApiKey($apiKey, $model = 'gemini-3.5-flash-lite') {
        $apiKey = trim($apiKey);
        if (empty($apiKey)) {
            return ['success' => false, 'message' => 'API Key tidak boleh kosong.'];
        }

        // Upgrade legacy or deprecated model names
        if (in_array($model, ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            $model = 'gemini-3.5-flash-lite';
        }

        // Active models with verified support on v1beta
        $modelsToAttempt = array_unique([$model, 'gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.6-flash', 'gemini-3.8-flash']);
        $lastErrMessage = '';
        $lastHttpCode = 0;

        foreach ($modelsToAttempt as $curModel) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($curModel) . ':generateContent?key=' . urlencode($apiKey);
            $payload = [
                'contents' => [
                    ['parts' => [['text' => 'Halo Gemini, status OK.']]]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 300
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $startTime = microtime(true);
            $res = curl_exec($ch);
            $latency = round((microtime(true) - $startTime) * 1000);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                return ['success' => false, 'message' => 'Koneksi jaringan gagal: ' . $curlErr];
            }

            $json = json_decode($res, true);
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
            if ($httpCode === 200 && (!empty($text) || isset($json['candidates'][0]))) {
                return [
                    'success' => true,
                    'message' => 'Koneksi berhasil terhubung ke Google Gemini (' . $curModel . ') dalam ' . $latency . 'ms',
                    'latency' => $latency,
                    'model'   => $curModel
                ];
            }

            $lastErrMessage = $json['error']['message'] ?? ('HTTP Status ' . $httpCode);
            $lastHttpCode = $httpCode;

            // If invalid API key, stop trying other models
            if (stripos($lastErrMessage, 'API_KEY_INVALID') !== false || stripos($lastErrMessage, 'API key not valid') !== false) {
                break;
            }
        }

        return [
            'success'   => false,
            'message'   => $lastErrMessage,
            'http_code' => $lastHttpCode
        ];
    }

    public function callGemini($prompt, $systemInstruction = null) {
        $apiKey = $this->getGeminiApiKey();
        if (empty($apiKey)) return null;

        $primaryModel = $this->getGeminiModel();
        if (in_array($primaryModel, ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-2.0-flash'])) {
            $primaryModel = 'gemini-3.5-flash-lite';
        }
        $modelsToTry = array_unique([$primaryModel, 'gemini-3.5-flash-lite', 'gemini-3.5-flash', 'gemini-3.6-flash', 'gemini-3.8-flash']);

        foreach ($modelsToTry as $m) {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . urlencode($m) . ':generateContent?key=' . urlencode($apiKey);

            $payload = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]]
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'maxOutputTokens' => 1500
                ]
            ];

            if (!empty($systemInstruction)) {
                $payload['system_instruction'] = [
                    'parts' => [['text' => $systemInstruction]]
                ];
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $res) {
                $json = json_decode($res, true);
                $aiText = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if (!empty($aiText)) {
                    return $aiText;
                }
            }
        }

        return null;
    }

    /**
     * Intelligent parsing & execution using Gemini AI
     */
    public function processWithGemini($rawMessage, $clean, $userContext = [], $responseMode = 'show_data') {
        $roleName = $userContext['role_name'] ?? 'User';
        $userName = $userContext['full_name'] ?? $userContext['username'] ?? 'User';

        // Fetch warehouse master snapshot
        $items = $this->db->query("SELECT b.code, b.name, b.unit, COALESCE(SUM(s.qty), 0) as total_qty 
            FROM barang b LEFT JOIN stok s ON b.id = s.barang_id 
            WHERE b.status = 'active' GROUP BY b.id LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);

        $racks = $this->db->query("SELECT kode, nama, status FROM lokasi WHERE status != 'tidak_aktif' LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $this->db->query("SELECT name FROM kategori LIMIT 20")->fetchAll(PDO::FETCH_COLUMN);

        $itemsSummary = "";
        // Inject any item specifically mentioned in user query first
        if (preg_match_all('/\b([0-9]{6,15}|BRG-[A-Z0-9\-]+)\b/i', $rawMessage, $codeMatches)) {
            foreach ($codeMatches[1] as $c) {
                $stmtSpecific = $this->db->prepare("SELECT b.code, b.name, b.unit, COALESCE(SUM(s.qty), 0) as total_qty 
                    FROM barang b LEFT JOIN stok s ON b.id = s.barang_id 
                    WHERE b.status = 'active' AND b.code = ? GROUP BY b.id LIMIT 1");
                $stmtSpecific->execute([$c]);
                $sp = $stmtSpecific->fetch(PDO::FETCH_ASSOC);
                if ($sp) {
                    $itemsSummary .= "- {$sp['name']} (Kode: {$sp['code']}, Satuan: {$sp['unit']}, Total Stok: {$sp['total_qty']})\n";
                }
            }
        }

        foreach ($items as $it) {
            $itemsSummary .= "- {$it['name']} (Kode: {$it['code']}, Satuan: {$it['unit']}, Total Stok: {$it['total_qty']})\n";
        }

        $racksSummary = "";
        $extRacks = $this->extractAllRackCodes($rawMessage);
        foreach ($extRacks as $er) {
            $stmtR = $this->db->prepare("SELECT kode, nama, status FROM lokasi WHERE kode = ? LIMIT 1");
            $stmtR->execute([$er]);
            $spR = $stmtR->fetch(PDO::FETCH_ASSOC);
            if ($spR) {
                $racksSummary .= "- {$spR['kode']} ({$spR['nama']}, Status: {$spR['status']})\n";
            }
        }

        foreach ($racks as $rk) {
            $racksSummary .= "- {$rk['kode']} ({$rk['nama']}, Status: {$rk['status']})\n";
        }

        // Fetch database metadata snapshot for AI
        $allDbs = $this->getAllDatabasesList();
        $allDbsText = "";
        foreach ($allDbs as $d) {
            $allDbsText .= "- {$d['name']} ({$d['table_count']} tabel)" . ($d['is_active'] ? ' [DATABASE AKTIF SAAT INI]' : '') . "\n";
        }

        $allTables = $this->getDatabaseTables('smartwarehouse');
        $schemaText = "";
        foreach ($allTables as $t) {
            $cols = $this->getTableSchemaDetails($t['table'], 'smartwarehouse');
            $colList = [];
            foreach ($cols as $c) {
                $colList[] = "{$c['Field']} ({$c['Type']}" . ($c['Key'] ? ", {$c['Key']}" : "") . ")";
            }
            $schemaText .= "Tabel '{$t['table']}' ({$t['rows']} baris, {$t['description']}):\n  Kolom: " . implode(', ', $colList) . "\n";
        }

        $systemInstruction = "Anda adalah SmartWarehouse AI Engine resmi Indonesia.
Anda melayani pengguna: {$userName} (Role: {$roleName}).
Role pengguna ini BERHAK dan MEMILIKI AKSES PENUH untuk melakukan SEMUA transaksi gudang (inbound, outbound, transfer, opname) DAN MENJAWAB SEMUA PERTANYAAN TENTANG SELURUH DATABASE YANG ADA di server MySQL!

DAFTAR DATABASE TERSEDIA DI SERVER MYSQL:
{$allDbsText}

SKEMA DATABASE AKTIF (smartwarehouse):
{$schemaText}

RELASI ANTAR TABEL (ERD):
- kategori (1) -> barang (N) via barang.kategori_id = kategori.id
- barang (1) -> stok (N) via stok.barang_id = barang.id
- lokasi (1) -> stok (N) via stok.lokasi_id = lokasi.id
- users (1) -> transaksi (N) via transaksi.user_id = users.id
- transaksi (1) -> transaksi_detail (N) via transaksi_detail.transaksi_id = transaksi.id
- barang (1) -> transaksi_detail (N) via transaksi_detail.barang_id = barang.id
- lokasi (1) -> transaksi_detail (N) via transaksi_detail.lokasi_id (asal) & lokasi_tujuan_id = lokasi.id
- roles (1) -> users (N) via users.role_id = roles.id
- users (1) -> log_aktivitas (N) via log_aktivitas.user_id = users.id

DAFTAR MASTER BARANG AKTIF (Snapshot):
{$itemsSummary}

DAFTAR RAK / LOKASI GUDANG (Snapshot):
{$racksSummary}

KATEGORI TERSEDIA: " . implode(', ', $categories) . "

KEMAMPUAN MENJAWAB SEMUA PERTANYAAN DATABASE (DATABASE & SQL INTELLIGENCE):
1. Anda BISA dan DIIZINKAN menjawab SEMUA pertanyaan seputar database, baik database 'smartwarehouse' maupun database lain di server MySQL (cvkti, db_produksi_rokok, db_tabungan, db_praktikum_web, jadwal_db, stockbarang, test, dll.).
2. JIKA pengguna menanyakan data spesifik dari database (misal: 'siapa saja user di database?', 'berapa total transaksi?', 'barang apa yang stoknya paling banyak?', 'tampilkan 5 log aktivitas terakhir', 'apa saja data di tabel X?'):
   Keluarkan intent: 'database_query' dengan parameter:
   {
     \"sql\": \"Query SELECT / SHOW / DESCRIBE / EXPLAIN yang valid dan aman untuk menjawab pertanyaan\",
     \"explanation\": \"Penjelasan singkat data apa yang diambil\"
   }
   Sistem akan secara otomatis mengeksekusi query SQL tersebut dan menampilkan tabel hasil riilnya kepada pengguna!
3. JIKA pengguna menanyakan informasi umum database (daftar database, daftar tabel, struktur/skema kolom, relasi tabel ERD, atau statistik):
   Keluarkan intent: 'database_info' dengan parameter:
   {
     \"action\": \"list_databases\" | \"list_tables\" | \"table_schema\" | \"relationships\" | \"stats\",
     \"table\": \"nama_tabel\" (jika relevan)
   }
   Serta 'reply' berisi penjelasan lengkap dalam format HTML.
4. JIKA pengguna meminta query SQL atau penjelasan SQL/database:
   Anda dapat langsung menjawab dalam format HTML dengan blok query SQL (intent: 'chat').
5. JANGAN PERNAH menolak pertanyaan yang berkaitan dengan database, tabel, kolom, SQL, skema, atau data sistem! Topik ini 100% didukung.

ATURAN BATASAN TOPIK DI LUAR SISTEM (OUT OF SCOPE):
Hanya tolak jika pertanyaan BENAR-BENAR di luar urusan sistem pergudangan dan database (misal: resep masakan, puisi fiksi, ramalan cuaca, gosip artis, atau cheat game). Set intent: 'unrelated'.

KEMAMPUAN TRANSAKSI SEKALIGUS (BATCH TRANSACTION) - SANGAT PENTING:
Sistem SmartWarehouse SEPENUHNYA MENDUKUNG eksekusi transaksi secara SEKALIGUS (batch) untuk SEMUA ROLE dan APAPUN JENIS TRANSAKSINYA (inbound/masuk/penempatan, outbound/keluar, transfer/mutasi, stock_adjustment/opname)!
JANGAN PERNAH menolak transaksi atau meminta pengguna membagi transaksi satu per satu dengan alasan transaksi hanya bisa satu rak! Sistem ini MAMPU memproses beberapa rak atau beberapa barang sekaligus dalam satu aksi!

Contoh Transaksi Sekaligus:
1. Multi-Rak Inbound (Penerimaan / Input Barang):
   Contoh: '2011100049 input barang ini pada rak A.1.05,A.1.06,dan A.1.07 dengan qty 15'
   -> Intent: 'batch_transaction'
   -> params: {
        'transaction_type': 'inbound',
        'material': '2011100049',
        'items': [
          { 'type': 'inbound', 'material': '2011100049', 'rack': 'A.1.05', 'qty': 15 },
          { 'type': 'inbound', 'material': '2011100049', 'rack': 'A.1.06', 'qty': 15 },
          { 'type': 'inbound', 'material': '2011100049', 'rack': 'A.1.07', 'qty': 15 }
        ]
      }
   (Catatan: Jika pengguna menyebut 'dengan qty 15', atau 'qty 15', atau 'masing-masing 15', gunakan qty 15 untuk setiap rak tujuan tersebut. Jika pengguna secara eksplisit menyebut 'total qty 15 dibagi rata', maka bagi rata, misal 5 per rak).

2. Multi-Rak Outbound (Pengeluaran Barang):
   Contoh: 'Keluarkan barang 2011100049 dari rak A.1.05 dan A.1.06 dengan qty 10 untuk Toko ABC'
   -> Intent: 'batch_transaction'
   -> params: {
        'transaction_type': 'outbound',
        'material': '2011100049',
        'recipient': 'Toko ABC',
        'items': [
          { 'type': 'outbound', 'material': '2011100049', 'rack': 'A.1.05', 'qty': 10 },
          { 'type': 'outbound', 'material': '2011100049', 'rack': 'A.1.06', 'qty': 10 }
        ]
      }

3. Multi-Rak Transfer (Pemindahan Barang):
   Contoh: 'Pindahkan 2011100049 dari rak A.1.01 ke rak A.1.05, A.1.06 dan A.1.07 masing-masing 5'
   -> Intent: 'batch_transaction'
   -> params: {
        'transaction_type': 'transfer',
        'material': '2011100049',
        'items': [
          { 'type': 'transfer', 'material': '2011100049', 'from_rack': 'A.1.01', 'to_rack': 'A.1.05', 'qty': 5 },
          { 'type': 'transfer', 'material': '2011100049', 'from_rack': 'A.1.01', 'to_rack': 'A.1.06', 'qty': 5 },
          { 'type': 'transfer', 'material': '2011100049', 'from_rack': 'A.1.01', 'to_rack': 'A.1.07', 'qty': 5 }
        ]
      }

4. Multi-Rak Stock Adjustment (Opname / Koreksi Stok):
   Contoh: 'Sesuaikan stok barang 2011100049 di rak A.1.05 jadi 20 dan A.1.06 jadi 30'
   -> Intent: 'batch_transaction'
   -> params: {
        'transaction_type': 'stock_adjustment',
        'material': '2011100049',
        'items': [
          { 'type': 'stock_adjustment', 'material': '2011100049', 'rack': 'A.1.05', 'new_qty': 20 },
          { 'type': 'stock_adjustment', 'material': '2011100049', 'rack': 'A.1.06', 'new_qty': 30 }
        ]
      }

ATURAN PENTING - MODE RESPON BERDASARKAN KATA KUNCI PERTANYAAN:
Sistem ini mendeteksi dua mode respon berbeda berdasarkan kata kunci dalam pertanyaan pengguna:
Mode respon saat ini yang terdeteksi: {$responseMode}

1. MODE 'count_only' (Hanya Angka/Jumlah):
   Aktif jika pengguna hanya menanyakan 'berapa', 'jumlah', 'total', 'hitung', 'banyaknya' TANPA kata 'tampilkan', 'lihat', 'daftar', 'list', 'show', 'detail', 'rincian', atau 'sebutkan'.
   Contoh: 'berapa rak yang kosong?', 'berapa user di database?', 'total stok semen?', 'berapa jumlah barang kritis?'
   JIKA response_mode = 'count_only':
   - JIKA intent = 'database_query': BUAT SQL AGREGASI (COUNT(*) / SUM()) BUKAN SELECT SEMUA KOLOM!
     Contoh pertanyaan: 'berapa rak yang kosong?' -> SQL: 'SELECT COUNT(*) as total FROM lokasi WHERE status = \'kosong\'' (atau query COUNT yang relevan).
     JANGAN SELECT kode, nama, kapasitas, status karena pengguna HANYA butuh ANGKA/JUMLAH.
     Set 'explanation' menjadi judul singkat angka tersebut, misal: 'Jumlah rak kosong'.
   - JIKA intent = 'query_empty_racks': Pilih intent ini jika menanyakan rak kosong.
   - JIKA intent = 'chat': Berikan jawaban SINGKAT hanya menyebutkan angka/jumlah dalam format HTML ringkas, JANGAN tampilkan tabel data.

2. MODE 'show_data' (Data + Jumlah):
   Aktif jika pengguna menggunakan kata 'tampilkan', 'lihat', 'daftar', 'list', 'sebutkan', atau kombinasi 'berapa dan tampilkan'.
   Contoh: 'tampilkan rak yang kosong', 'daftar user', 'lihat transaksi terakhir', 'berapa dan tampilkan barang stok kritis'
   JIKA response_mode = 'show_data':
   - JIKA intent = 'database_query': Buat SQL SELECT dengan kolom-kolom deskriptif untuk ditampilkan dalam tabel.
   - JIKA intent = 'query_empty_racks': Tampilkan daftar rak kosong lengkap.

TUGAS ANDA:
Analisis pesan pengguna untuk mendeteksi intent transaksi atau pertanyaan.
Intent yang dapat dieksekusi:
1. 'database_query': Query SQL untuk membaca data tabel database.
   Params: { 'sql': 'SELECT ...', 'explanation': 'deskripsi data' }
2. 'database_info': Info daftar database, daftar tabel, skema struktur tabel, relasi ERD, atau statistik.
   Params: { 'action': 'list_databases'|'list_tables'|'table_schema'|'relationships'|'stats', 'table': 'nama_tabel' }
3. 'batch_transaction': Transaksi sekaligus (inbound, outbound, transfer, opname) melibatkan beberapa rak atau beberapa barang.
4. 'inbound': Penerimaan barang masuk dari supplier ke 1 rak.
   Params: { 'material': 'nama/kode', 'rack': 'kode rak', 'qty': int, 'supplier': 'nama supplier', 'notes': 'catatan' }
5. 'outbound': Pengeluaran barang keluar dari 1 rak ke customer/divisi.
   Params: { 'material': 'nama/kode', 'rack': 'kode rak', 'qty': int, 'recipient': 'nama penerima', 'notes': 'catatan' }
6. 'transfer': Pemindahan antar 1 rak asal ke 1 rak tujuan.
   Params: { 'material': 'nama/kode', 'from_rack': 'rak asal', 'to_rack': 'rak tujuan', 'qty': int, 'notes': 'catatan' }
7. 'stock_adjustment': Koreksi / opname stok di suatu rak.
   Params: { 'material': 'nama/kode', 'rack': 'kode rak', 'new_qty': int, 'reason': 'alasan' }
8. 'create_barang': Tambah master barang baru.
   Params: { 'code': 'kode unik', 'name': 'nama barang', 'category': 'kategori', 'unit': 'pcs/box/unit/kg', 'min_stock': int, 'description': 'deskripsi' }
9. 'create_lokasi': Tambah master rak baru.
   Params: { 'code': 'format A.1.01', 'name': 'nama rak', 'capacity': int, 'description': 'deskripsi' }
10. 'create_kategori': Tambah kategori baru.
   Params: { 'name': 'nama kategori', 'description': 'deskripsi' }
11. 'report': Permintaan laporan.
   Params: { 'report_type': 'masuk'|'keluar'|'pemindahan'|'stok'|'aktivitas' }
12. 'query_location': Menanyakan di rak mana saja letak suatu barang.
   Params: { 'material': 'nama/kode' }
13. 'query_rack': Menanyakan ada apa saja atau berapa qty di suatu rak.
    Params: { 'rack': 'kode rak' }
14. 'query_empty_racks': Mencari rak-rak yang masih kosong.
15. 'unrelated': Pertanyaan/permintaan di luar SmartWarehouse & Database.
16. 'chat': Pertanyaan umum operasional pergudangan, konsultasi stok gudang, penjelasan SQL, sapaan kerja, atau penjelasan teknis.

FORMAT OUTPUT WAJIB:
Hanya keluarkan JSON murni tanpa markdown triple backticks:
{
  \"intent\": \"database_query|database_info|batch_transaction|inbound|outbound|transfer|stock_adjustment|create_barang|create_lokasi|create_kategori|report|query_location|query_rack|query_empty_racks|unrelated|chat\",
  \"params\": { ... },
  \"reply\": \"Penjelasan ramah dan konfirmasi dalam bahasa Indonesia format HTML (<p>, <strong>, <ul>, <li>)\"
}";

        $rawResponse = $this->callGemini($rawMessage, $systemInstruction);
        if (!$rawResponse) {
            return null;
        }

        // Clean codeblock wrapper if any
        $cleanJson = preg_replace('/^```(?:json)?\s*/i', '', trim($rawResponse));
        $cleanJson = preg_replace('/```$/', '', trim($cleanJson));

        $data = json_decode($cleanJson, true);
        if (!is_array($data) || empty($data['intent'])) {
            return [
                'success' => true,
                'type' => 'gemini_answer',
                'title' => 'Asisten SmartWarehouse (Gemini AI)',
                'message' => strip_tags($rawResponse, '<p><br><strong><b><em><i><ul><ol><li><h3><h4><table><thead><tbody><tr><th><td><code><pre>'),
                'suggestions' => []
            ];
        }

        $intent = $data['intent'];
        $params = $data['params'] ?? [];

        switch ($intent) {
            case 'database_query':
                if (!empty($params['sql'])) {
                    return $this->handleDatabaseQueryFromGemini($params['sql'], $params['explanation'] ?? '', $data['reply'] ?? '', $userContext, $responseMode, $rawMessage);
                }
                break;

            case 'database_info':
                return $this->handleDatabaseInfoFromGemini(
                    $params['action'] ?? '',
                    $params['target'] ?? ($params['table'] ?? ''),
                    $data['reply'] ?? '',
                    $userContext
                );

            case 'batch_transaction':
                $batchRes = $this->handleBatchWithParams($params, $userContext);
                if ($batchRes) return $batchRes;
                break;

            case 'inbound':
                if (!empty($params['racks']) && is_array($params['racks'])) {
                    return $this->handleBatchInboundMultiRack($params['material'], $params['racks'], (int)($params['qty'] ?? 1), $params['supplier'] ?? 'Supplier Umum', $params['notes'] ?? 'Barang masuk sekaligus via Gemini AI', $userContext);
                }
                if (!empty($params['rack']) && (strpos($params['rack'], ',') !== false || stripos($params['rack'], ' dan ') !== false)) {
                    $ext = $this->extractAllRackCodes($params['rack']);
                    if (count($ext) > 1) {
                        return $this->handleBatchInboundMultiRack($params['material'], $ext, (int)($params['qty'] ?? 1), $params['supplier'] ?? 'Supplier Umum', $params['notes'] ?? 'Barang masuk sekaligus via Gemini AI', $userContext);
                    }
                }
                if (!empty($params['material']) && !empty($params['rack']) && !empty($params['qty'])) {
                    return $this->handleInboundWithParams($params['material'], $params['rack'], (int)$params['qty'], $params['supplier'] ?? 'Supplier Umum', $params['notes'] ?? 'Barang masuk via Gemini AI', $userContext);
                }
                break;

            case 'outbound':
                if (!empty($params['racks']) && is_array($params['racks'])) {
                    return $this->handleBatchOutboundMultiRack($params['material'], $params['racks'], (int)($params['qty'] ?? 1), $params['recipient'] ?? 'Penerima Umum', $params['notes'] ?? 'Barang keluar sekaligus via Gemini AI', $userContext);
                }
                if (!empty($params['rack']) && (strpos($params['rack'], ',') !== false || stripos($params['rack'], ' dan ') !== false)) {
                    $ext = $this->extractAllRackCodes($params['rack']);
                    if (count($ext) > 1) {
                        return $this->handleBatchOutboundMultiRack($params['material'], $ext, (int)($params['qty'] ?? 1), $params['recipient'] ?? 'Penerima Umum', $params['notes'] ?? 'Barang keluar sekaligus via Gemini AI', $userContext);
                    }
                }
                if (!empty($params['material']) && !empty($params['rack']) && !empty($params['qty'])) {
                    return $this->handleOutboundWithParams($params['material'], $params['rack'], (int)$params['qty'], $params['recipient'] ?? 'Penerima Umum', $params['notes'] ?? 'Barang keluar via Gemini AI', $userContext);
                }
                break;

            case 'transfer':
                if (!empty($params['to_racks']) && is_array($params['to_racks'])) {
                    return $this->handleBatchTransferMultiRack($params['material'], $params['from_rack'] ?? '', $params['to_racks'], (int)($params['qty'] ?? 1), $params['notes'] ?? 'Pemindahan sekaligus via Gemini AI', $userContext);
                }
                if (!empty($params['to_rack']) && (strpos($params['to_rack'], ',') !== false || stripos($params['to_rack'], ' dan ') !== false)) {
                    $ext = $this->extractAllRackCodes($params['to_rack']);
                    if (count($ext) > 1) {
                        return $this->handleBatchTransferMultiRack($params['material'], $params['from_rack'] ?? '', $ext, (int)($params['qty'] ?? 1), $params['notes'] ?? 'Pemindahan sekaligus via Gemini AI', $userContext);
                    }
                }
                if (!empty($params['material']) && !empty($params['from_rack']) && !empty($params['to_rack']) && !empty($params['qty'])) {
                    return $this->handleTransferWithParams($params['material'], $params['from_rack'], $params['to_rack'], (int)$params['qty'], $params['notes'] ?? 'Pemindahan via Gemini AI', $userContext);
                }
                break;

            case 'stock_adjustment':
                if (!empty($params['racks']) && is_array($params['racks'])) {
                    $map = [];
                    foreach ($params['racks'] as $r) {
                        $map[$r] = (int)($params['new_qty'] ?? 0);
                    }
                    return $this->handleBatchStockAdjustmentMultiRack($params['material'], $map, $params['reason'] ?? 'Koreksi stok sekaligus via Gemini AI', $userContext);
                }
                if (!empty($params['material']) && !empty($params['rack']) && isset($params['new_qty'])) {
                    return $this->handleStockAdjustmentWithParams($params['material'], $params['rack'], (int)$params['new_qty'], $params['reason'] ?? 'Koreksi stok via Gemini AI', $userContext);
                }
                break;

            case 'create_barang':
                if (!empty($params['name'])) {
                    return $this->handleCreateBarangWithParams($params['code'] ?? '', $params['name'], $params['category'] ?? 'Umum', $params['unit'] ?? 'pcs', (int)($params['min_stock'] ?? 5), $userContext);
                }
                break;

            case 'create_lokasi':
                if (!empty($params['code'])) {
                    return $this->handleCreateLokasiWithParams($params['code'], $params['name'] ?? '', (int)($params['capacity'] ?? 100), $userContext);
                }
                break;

            case 'create_kategori':
                if (!empty($params['name'])) {
                    return $this->handleCreateKategoriWithParams($params['name'], $params['description'] ?? 'Dibuat via Gemini AI', $userContext);
                }
                break;

            case 'report':
                $type = $params['report_type'] ?? 'semua';
                return $this->handleReportIntent($type);

            case 'query_location':
                if (!empty($params['material'])) {
                    $m = $this->searchBarang($params['material']);
                    if (!empty($m)) {
                        return $this->getMaterialLocationResponse($m[0]['id'], $m[0]['name'], '', '', $responseMode);
                    }
                }
                break;

            case 'query_rack':
                if (!empty($params['rack'])) {
                    $l = $this->findLokasiExactOrLike($params['rack']);
                    if ($l) {
                        return $this->getRackQtyResponse($l['id'], $l['kode'], $responseMode);
                    }
                }
                break;

            case 'query_empty_racks':
                return $this->handleEmptyRacksIntent($responseMode);

            case 'unrelated':
                return $this->getUnrelatedResponse($data['reply'] ?? null);

            case 'chat':
            default:
                if ($this->isUnrelatedQuery($rawMessage, $clean)) {
                    return $this->getUnrelatedResponse();
                }

                // Check if user actually intended a multi-rack batch transaction that Gemini misclassified as chat
                $batchFallback = $this->handleMultiRackFromText($rawMessage, $clean, $userContext);
                if ($batchFallback) {
                    return $batchFallback;
                }

                if (!empty($data['reply'])) {
                    // Guard against stray programming answers (Python, Java, etc.) but ALLOW SQL & HTML
                    if (preg_match('/```(?:python|typescript|java|c\+\+|golang|ruby|rust)/i', $data['reply']) ||
                        preg_match('/\b(def |import sys|import os|class Program|public static void|<\?php)/i', $data['reply'])) {
                        return $this->getUnrelatedResponse();
                    }

                    return [
                        'success' => true,
                        'type' => 'gemini_answer',
                        'title' => 'Asisten SmartWarehouse (Gemini AI)',
                        'message' => strip_tags($data['reply'], '<p><br><strong><b><em><i><ul><ol><li><h3><h4><table><thead><tbody><tr><th><td><code><pre><button><span><div>'),
                        'suggestions' => [
                            'Apa saja database yang ada?',
                            'Tampilkan semua tabel di database smartwarehouse',
                            'Statistik database smartwarehouse',
                            'Bagaimana relasi antar tabel (ERD)?'
                        ]
                    ];
                }
                break;
        }

        return null;
    }

    // =========================================================================
    //  PARAMETERIZED ACTION BUILDERS (USED BY GEMINI & DIRECT CALLS)
    // =========================================================================

    public function handleInboundWithParams($materialTerm, $rackTerm, $qty, $supplier, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan dalam database.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }
        $barang = $barangMatches[0];

        $lokasi = $this->findLokasiExactOrLike($rackTerm);
        if (!$lokasi) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak tujuan <strong>\"" . htmlspecialchars($rackTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleRackSuggestions()
            ];
        }

        if ($lokasi['status'] === 'tidak_aktif') {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Tidak Aktif',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-ban me-1'></i> Rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> berstatus tidak aktif.</p>"
            ];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
        $newTotal = $currentQty + $qty;

        $payload = [
            'action_type'   => 'inbound',
            'barang_id'     => (int)$barang['id'],
            'barang_name'   => $barang['name'],
            'barang_code'   => $barang['code'],
            'lokasi_id'     => (int)$lokasi['id'],
            'lokasi_kode'   => $lokasi['kode'],
            'lokasi_nama'   => $lokasi['nama'],
            'qty'           => (int)$qty,
            'unit'          => $barang['unit'],
            'supplier_name' => $supplier,
            'notes'         => $notes,
            'current_qty'   => $currentQty,
            'new_qty'       => $newTotal
        ];

        $html = '<div class="ai-action-card border border-success-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-success text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-arrow-circle-down"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-success">Konfirmasi Penerimaan Barang Masuk</h6>';
        $html .= '<small class="text-muted">Jobdesk: Penerimaan & Penempatan Stok (Inbound)</small>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="row g-2 mb-2">';
        $html .= '<div class="col-12"><div class="p-2 bg-light rounded-2 border">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Rak Penempatan</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span>';
        $html .= '<div class="small text-muted text-truncate">' . htmlspecialchars($lokasi['nama']) . '</div>';
        $html .= '<div class="small text-success mt-1">Stok: ' . $currentQty . ' &rarr; <strong>' . $newTotal . '</strong> ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Supplier</small>';
        $html .= '<strong class="text-dark small d-block text-truncate">' . htmlspecialchars($supplier) . '</strong>';
        $html .= '<small class="text-muted" style="font-size:0.75rem;">Qty: +' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-success btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Catat Barang Masuk</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Barang Masuk',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function handleOutboundWithParams($materialTerm, $rackTerm, $qty, $recipient, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }
        $barang = $barangMatches[0];

        $lokasi = $this->findLokasiExactOrLike($rackTerm);
        if (!$lokasi) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Rak Asal Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Rak asal <strong>\"" . htmlspecialchars($rackTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleRackSuggestions()
            ];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentStock = (int)($stmtCurrent->fetchColumn() ?: 0);

        if ($currentStock <= 0) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Habis di Rak Ini',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-times-circle me-1'></i> Material <strong>" . htmlspecialchars($barang['name']) . "</strong> tidak memiliki stok di rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong>.</p>"
            ];
        }

        if ($currentStock < $qty) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Kurang',
                'message' => "<p class='text-danger mb-0'><i class='fas fa-exclamation-triangle me-1'></i> Stok di rak <strong>" . htmlspecialchars($lokasi['kode']) . "</strong> hanya tersisa <strong>" . $currentStock . " " . htmlspecialchars($barang['unit']) . "</strong>, tidak mencukupi untuk permintaan " . $qty . " " . htmlspecialchars($barang['unit']) . ".</p>"
            ];
        }

        $newStock = $currentStock - $qty;
        $payload = [
            'action_type'    => 'outbound',
            'barang_id'      => (int)$barang['id'],
            'barang_name'    => $barang['name'],
            'barang_code'    => $barang['code'],
            'lokasi_id'      => (int)$lokasi['id'],
            'lokasi_kode'    => $lokasi['kode'],
            'lokasi_nama'    => $lokasi['nama'],
            'qty'            => (int)$qty,
            'unit'           => $barang['unit'],
            'recipient_name' => $recipient,
            'notes'          => $notes,
            'current_qty'    => $currentStock,
            'new_qty'        => $newStock
        ];

        $html = '<div class="ai-action-card border border-danger-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<div class="d-flex align-items-center mb-2 pb-2 border-bottom">';
        $html .= '<div class="bg-danger text-white rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width:36px; height:36px;">';
        $html .= '<i class="fas fa-arrow-circle-up"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<h6 class="fw-bold mb-0 text-danger">Konfirmasi Pengeluaran Barang Keluar</h6>';
        $html .= '<small class="text-muted">Jobdesk: Pengeluaran Barang (Outbound)</small>';
        $html .= '</div></div>';

        $html .= '<div class="row g-2 mb-2">';
        $html .= '<div class="col-12"><div class="p-2 bg-light rounded-2 border">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Material</small>';
        $html .= '<span class="badge bg-primary-subtle text-primary border border-primary-subtle me-1">[' . htmlspecialchars($barang['code']) . ']</span> ';
        $html .= '<strong class="text-dark">' . htmlspecialchars($barang['name']) . '</strong>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Rak Asal</small>';
        $html .= '<span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span>';
        $html .= '<div class="small text-danger mt-1">Sisa: ' . $currentStock . ' &rarr; <strong>' . $newStock . '</strong> ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '</div></div>';

        $html .= '<div class="col-6"><div class="p-2 bg-light rounded-2 border h-100">';
        $html .= '<small class="text-muted d-block" style="font-size:0.75rem;">Penerima</small>';
        $html .= '<strong class="text-dark small d-block text-truncate">' . htmlspecialchars($recipient) . '</strong>';
        $html .= '<small class="text-danger" style="font-size:0.75rem;">Qty: -' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</small>';
        $html .= '</div></div></div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-danger btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Catat Pengeluaran</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Barang Keluar',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function handleTransferWithParams($materialTerm, $fromRackTerm, $toRackTerm, $qty, $notes, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Material Tidak Ditemukan',
                'message' => "<p class='text-danger mb-1'><i class='fas fa-times-circle me-1'></i> Material <strong>\"" . htmlspecialchars($materialTerm) . "\"</strong> tidak ditemukan.</p>",
                'suggestions' => $this->getSampleBarangSuggestions()
            ];
        }
        $barang = $barangMatches[0];

        $fromLokasi = $this->findLokasiExactOrLike($fromRackTerm);
        $toLokasi   = $this->findLokasiExactOrLike($toRackTerm);

        if (!$fromLokasi) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Asal Tidak Ditemukan', 'message' => "Rak asal $fromRackTerm tidak ditemukan."];
        }
        if (!$toLokasi) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Tujuan Tidak Ditemukan', 'message' => "Rak tujuan $toRackTerm tidak ditemukan."];
        }
        if ($fromLokasi['id'] === $toLokasi['id']) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Sama', 'message' => "Rak asal dan rak tujuan tidak boleh sama."];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $fromLokasi['id']]);
        $currentStock = (int)($stmtCurrent->fetchColumn() ?: 0);

        if ($currentStock < $qty) {
            return [
                'success' => false,
                'type' => 'error',
                'title' => 'Stok Tidak Mencukupi',
                'message' => "Stok di rak {$fromLokasi['kode']} hanya tersisa $currentStock {$barang['unit']}, tidak cukup untuk memindahkan $qty."
            ];
        }

        $transferPayload = [
            'action_type'       => 'transfer',
            'barang_id'         => (int)$barang['id'],
            'barang_name'       => $barang['name'],
            'barang_code'       => $barang['code'],
            'lokasi_id'         => (int)$fromLokasi['id'],
            'lokasi_kode'       => $fromLokasi['kode'],
            'lokasi_tujuan_id'   => (int)$toLokasi['id'],
            'lokasi_tujuan_kode' => $toLokasi['kode'],
            'qty'               => (int)$qty,
            'unit'              => $barang['unit'],
            'notes'             => $notes
        ];

        $html = '<div class="ai-transfer-card border border-warning-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-exchange-alt text-warning me-2"></i>Konfirmasi Pemindahan Barang</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border mb-2 small">';
        $html .= '<div><strong>Material:</strong> ' . htmlspecialchars($barang['name']) . ' (' . htmlspecialchars($barang['code']) . ')</div>';
        $html .= '<div><strong>Dari Rak:</strong> <span class="badge bg-dark font-monospace">' . htmlspecialchars($fromLokasi['kode']) . '</span> (Sisa: ' . ($currentStock - $qty) . ')</div>';
        $html .= '<div><strong>Ke Rak:</strong> <span class="badge bg-primary font-monospace">' . htmlspecialchars($toLokasi['kode']) . '</span></div>';
        $html .= '<div><strong>Qty Pindah:</strong> <strong class="text-primary">' . $qty . ' ' . htmlspecialchars($barang['unit']) . '</strong></div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-warning btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($transferPayload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Pindahkan Sekarang</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'transfer_preview',
            'title' => 'Konfirmasi Pemindahan Barang',
            'message' => $html,
            'data' => $transferPayload
        ];
    }

    public function handleStockAdjustmentWithParams($materialTerm, $rackTerm, $newQty, $reason, $userContext) {
        $barangMatches = $this->searchBarang($materialTerm);
        if (empty($barangMatches)) {
            return ['success' => false, 'type' => 'error', 'title' => 'Material Tidak Ditemukan', 'message' => "Material \"$materialTerm\" tidak ditemukan."];
        }
        $barang = $barangMatches[0];

        $lokasi = $this->findLokasiExactOrLike($rackTerm);
        if (!$lokasi) {
            return ['success' => false, 'type' => 'error', 'title' => 'Rak Tidak Ditemukan', 'message' => "Rak \"$rackTerm\" tidak ditemukan."];
        }

        $stmtCurrent = $this->db->prepare("SELECT qty FROM stok WHERE barang_id = ? AND lokasi_id = ?");
        $stmtCurrent->execute([$barang['id'], $lokasi['id']]);
        $currentQty = (int)($stmtCurrent->fetchColumn() ?: 0);
        $diff = $newQty - $currentQty;

        $payload = [
            'action_type' => 'stock_adjustment',
            'barang_id'   => (int)$barang['id'],
            'barang_name' => $barang['name'],
            'lokasi_id'   => (int)$lokasi['id'],
            'lokasi_kode' => $lokasi['kode'],
            'old_qty'     => $currentQty,
            'new_qty'     => (int)$newQty,
            'diff'        => $diff,
            'unit'        => $barang['unit'],
            'reason'      => $reason
        ];

        $html = '<div class="ai-action-card border border-warning-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-balance-scale text-warning me-2"></i>Konfirmasi Penyesuaian / Opname Stok</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Material:</strong> ' . htmlspecialchars($barang['name']) . '</div>';
        $html .= '<div><strong>Rak:</strong> <span class="badge bg-dark font-monospace">' . htmlspecialchars($lokasi['kode']) . '</span></div>';
        $html .= '<div><strong>Stok di Sistem:</strong> ' . $currentQty . ' ' . htmlspecialchars($barang['unit']) . '</div>';
        $html .= '<div><strong>Stok Fisik Baru:</strong> <strong class="text-primary">' . $newQty . ' ' . htmlspecialchars($barang['unit']) . '</strong> (Selisih: ' . ($diff >= 0 ? '+' : '') . $diff . ')</div>';
        $html .= '<div><strong>Alasan:</strong> ' . htmlspecialchars($reason) . '</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-warning btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Perbarui Stok</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Penyesuaian Stok',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function handleCreateBarangWithParams($code, $name, $category, $unit, $minStock, $userContext) {
        if (empty($code)) {
            $code = 'BRG-' . strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $category ?: 'GEN'), 0, 3)) . '-' . rand(100, 999);
        }

        // Check if code exists
        $stmtCheck = $this->db->prepare("SELECT id FROM barang WHERE code = ?");
        $stmtCheck->execute([$code]);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'type' => 'error', 'title' => 'Kode Duplikat', 'message' => "Kode barang $code sudah digunakan."];
        }

        // Category resolution
        $stmtCat = $this->db->prepare("SELECT id FROM kategori WHERE name LIKE ? LIMIT 1");
        $stmtCat->execute(['%' . $category . '%']);
        $catId = $stmtCat->fetchColumn();
        if (!$catId) {
            $stmtNewCat = $this->db->prepare("INSERT INTO kategori (name, description) VALUES (?, ?)");
            $stmtNewCat->execute([$category, 'Kategori otomatis via AI Assistant']);
            $catId = $this->db->lastInsertId();
        }

        $payload = [
            'action_type' => 'create_barang',
            'code'        => $code,
            'name'        => $name,
            'kategori_id' => (int)$catId,
            'unit'        => $unit ?: 'pcs',
            'min_stock'   => $minStock ?: 5,
            'description' => 'Ditambahkan melalui SmartWarehouse AI Assistant'
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-2"></i>Konfirmasi Tambah Master Barang</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Nama Barang:</strong> ' . htmlspecialchars($name) . '</div>';
        $html .= '<div><strong>Kode:</strong> <span class="badge bg-light text-dark border font-monospace">' . htmlspecialchars($code) . '</span></div>';
        $html .= '<div><strong>Kategori:</strong> ' . htmlspecialchars($category) . '</div>';
        $html .= '<div><strong>Satuan:</strong> ' . htmlspecialchars($unit) . ' | <strong>Min Stok:</strong> ' . $minStock . '</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Simpan Barang</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Tambah Barang',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function handleCreateLokasiWithParams($code, $nama, $capacity, $userContext) {
        $kode = strtoupper($this->normalizeRackCode($code));

        $stmtCheck = $this->db->prepare("SELECT id FROM lokasi WHERE kode = ?");
        $stmtCheck->execute([$kode]);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'type' => 'error', 'title' => 'Kode Rak Duplikat', 'message' => "Kode rak $kode sudah ada."];
        }

        $parts = explode('.', $kode);
        $rack   = $parts[0] ?? 'A';
        $level  = (int)($parts[1] ?? 1);
        $posisi = (int)($parts[2] ?? 1);
        if (empty($nama)) $nama = "Rak " . $kode;

        $payload = [
            'action_type' => 'create_lokasi',
            'kode'        => $kode,
            'rack'        => $rack,
            'level'       => $level,
            'posisi'      => $posisi,
            'nama'        => $nama,
            'kapasitas'   => $capacity ?: 100
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-2"></i>Konfirmasi Tambah Master Rak</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Kode Rak:</strong> <span class="badge bg-dark font-monospace">' . htmlspecialchars($kode) . '</span></div>';
        $html .= '<div><strong>Nama Rak:</strong> ' . htmlspecialchars($nama) . '</div>';
        $html .= '<div><strong>Kapasitas:</strong> ' . ($capacity ?: 100) . ' unit</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Buat Rak</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Tambah Rak',
            'message' => $html,
            'data' => $payload
        ];
    }

    public function handleCreateKategoriWithParams($nama, $description, $userContext) {
        $stmtCheck = $this->db->prepare("SELECT id FROM kategori WHERE name = ?");
        $stmtCheck->execute([$nama]);
        if ($stmtCheck->fetchColumn()) {
            return ['success' => false, 'type' => 'error', 'title' => 'Kategori Duplikat', 'message' => "Kategori \"$nama\" sudah ada."];
        }

        $payload = [
            'action_type' => 'create_kategori',
            'name'        => $nama,
            'description' => $description ?: 'Dibuat via AI Assistant'
        ];

        $html = '<div class="ai-action-card border border-primary-subtle rounded-3 p-3 bg-white shadow-sm">';
        $html .= '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-plus-circle me-2"></i>Konfirmasi Tambah Kategori</h6>';
        $html .= '<div class="p-2 bg-light rounded-2 border small mb-2">';
        $html .= '<div><strong>Nama Kategori:</strong> ' . htmlspecialchars($nama) . '</div>';
        $html .= '<div><strong>Deskripsi:</strong> ' . htmlspecialchars($payload['description']) . '</div>';
        $html .= '</div>';

        $html .= '<div class="d-flex gap-2 mt-3 pt-2 border-top">';
        $html .= '<button class="btn btn-primary btn-sm flex-grow-1 fw-bold ai-execute-action-btn" ' .
                 'data-action=\'' . htmlspecialchars(json_encode($payload), ENT_QUOTES, 'UTF-8') . '\'>' .
                 '<i class="fas fa-check-circle me-1"></i>Konfirmasi & Buat Kategori</button>';
        $html .= '<button class="btn btn-outline-secondary btn-sm ai-cancel-transfer-btn"><i class="fas fa-times me-1"></i>Batal</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'action_preview',
            'title' => 'Konfirmasi Tambah Kategori',
            'message' => $html,
            'data' => $payload
        ];
    }

    private function tryGeminiFallback($userQuery) {
        $clean = mb_strtolower(trim($userQuery), 'UTF-8');
        if ($this->isUnrelatedQuery($userQuery, $clean)) {
            return $this->getUnrelatedResponse();
        }

        $prompt = "Anda adalah asisten cerdas SmartWarehouse. Anda berwenang menjawab semua hal seputar operasional pergudangan, stok, rak, transaksi, dan SEMUA hal seputar DATABASE (MySQL, tabel-tabel, struktur skema, data, dan query SQL pergudangan). Jika pertanyaan TIDAK berhubungan dengan gudang maupun database (seperti resep masakan, puisi fiksi, ramalan cuaca, dll.), jawab HANYA kata 'DI_LUAR_CAKUPAN'. Jika berhubungan, jawab secara informatif dalam format HTML: " . $userQuery;
        $res = $this->callGemini($prompt);
        if ($res) {
            if (stripos($res, 'DI_LUAR_CAKUPAN') !== false || stripos($res, 'tidak berhubungan') !== false) {
                return $this->getUnrelatedResponse();
            }
            return [
                'success' => true,
                'type' => 'gemini_answer',
                'title' => 'Asisten SmartWarehouse',
                'message' => strip_tags($res, '<p><br><strong><b><em><i><ul><ol><li><h3><h4><table><thead><tbody><tr><th><td><code><pre>')
            ];
        }
        return null;
    }

    // =========================================================================
    //  DATABASE & SQL INTELLIGENCE ENGINE (FULL DATABASE SUPPORT)
    // =========================================================================

    public function isDatabaseQueryKeyword($clean) {
        if (preg_match('/^\s*(select|show|describe|desc|explain)\b/i', $clean)) {
            return true;
        }
        if (preg_match('/\b(database|basis\s*data|db|tabel|table|tables|kolom|column|columns|field|fields|skema|schema|schemas|relasi|erd|sql|mysql|mariadb|query|record|records|baris|foreign\s*key|primary\s*key)\b/i', $clean)) {
            return true;
        }
        if (preg_match('/\b(smartwarehouse|stockbarang|cvkti|db_produksi_rokok|db_tabungan|db_praktikum_web|jadwal_db|stocker)\b/i', $clean)) {
            return true;
        }
        return false;
    }

    public function isDirectSqlCommand($clean) {
        return (bool)preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', trim($clean));
    }

    public function handleDirectSqlCommand($rawSql, $userContext = []) {
        $cleanSql = trim($rawSql);
        $res = $this->executeSafeSqlQuery($cleanSql);
        if (!$res['success']) {
            return [
                'success' => false,
                'type' => 'database_error',
                'title' => 'Query Database Gagal',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> ' . htmlspecialchars($res['message']) . '</div>',
                'suggestions' => ['SHOW DATABASES', 'SHOW TABLES', 'SELECT * FROM barang LIMIT 5']
            ];
        }

        $html = $this->renderSqlQueryResultHtml($res, 'Hasil Eksekusi SQL', 'Perintah SQL berhasil dieksekusi secara aman pada server database MySQL.');
        return [
            'success' => true,
            'type' => 'database_query_result',
            'title' => 'Hasil Eksekusi SQL',
            'message' => $html,
            'suggestions' => [
                'SHOW DATABASES',
                'SHOW TABLES FROM smartwarehouse',
                'DESCRIBE barang',
                'SELECT * FROM users'
            ]
        ];
    }

    public function isDatabaseIntent($clean) {
        if ($this->isDirectSqlCommand($clean)) {
            return true;
        }

        // List databases
        if (preg_match('/\b(apa saja database|daftar database|semua database|database apa saja|database yang ada|database di mysql|list database|show databases)\b/i', $clean)) {
            return true;
        }

        // List tables
        if (preg_match('/\b(tabel apa saja|daftar tabel|semua tabel|tabel di database|tabel database|list table|show tables)\b/i', $clean)) {
            return true;
        }

        // Table structure / schema
        if (preg_match('/\b(struktur tabel|skema tabel|kolom tabel|field tabel|describe tabel|deskripsi tabel)\b/i', $clean)) {
            return true;
        }

        // Relationships / ERD
        if (preg_match('/\b(relasi tabel|hubungan antar tabel|erd database|relasi database|foreign key|diagram database)\b/i', $clean)) {
            return true;
        }

        // Stats / row count
        if (preg_match('/\b(statistik database|ukuran database|jumlah baris database|jumlah data di tabel|ringkasan database|info database)\b/i', $clean)) {
            return true;
        }

        // Specific table view ("isi tabel users", "tampilkan data tabel barang", etc.)
        if (preg_match('/\b(isi tabel|tampilkan tabel|tampilkan data tabel|lihat tabel|buka tabel)\s+([a-zA-Z0-9_\-]+)\b/i', $clean)) {
            return true;
        }

        // Specific database mention ("database cvkti", "tabel di stockbarang")
        if (preg_match('/\b(database|tabel)\s+(cvkti|stockbarang|db_produksi_rokok|db_tabungan|db_praktikum_web|jadwal_db|test)\b/i', $clean)) {
            return true;
        }

        return false;
    }

    public function handleDatabaseIntent($rawMessage, $clean, $userContext = [], $responseMode = 'show_data') {
        // Direct SQL check
        if ($this->isDirectSqlCommand($clean)) {
            return $this->handleDirectSqlCommand($rawMessage, $userContext);
        }

        // Detect target database name if mentioned (e.g. cvkti, stockbarang, etc.)
        $targetDb = 'smartwarehouse';
        if (preg_match('/\b(cvkti|stockbarang|db_produksi_rokok|db_tabungan|db_praktikum_web|jadwal_db|test)\b/i', $clean, $dbMatch)) {
            $targetDb = $dbMatch[1];
        }

        // 1. List Databases
        if (preg_match('/\b(apa saja database|daftar database|semua database|database apa saja|database yang ada|database di mysql|list database|show databases)\b/i', $clean)) {
            return $this->handleListDatabasesResponse();
        }

        // 2. ERD / Relationships
        if (preg_match('/\b(relasi tabel|hubungan antar tabel|erd database|relasi database|foreign key|diagram database)\b/i', $clean)) {
            return $this->handleDatabaseErdResponse();
        }

        // 3. Stats
        if (preg_match('/\b(statistik database|ukuran database|jumlah baris database|jumlah data di tabel|ringkasan database|info database)\b/i', $clean)) {
            return $this->handleDatabaseStatsResponse($targetDb);
        }

        // 4. Table Schema ("struktur tabel barang", "skema tabel transaksi")
        if (preg_match('/\b(struktur|skema|kolom|field|describe)\s+tabel\s+([a-zA-Z0-9_\-]+)/i', $clean, $schemaMatch) ||
            preg_match('/\b(struktur|skema)\s+([a-zA-Z0-9_\-]+)\s+di\s+database/i', $clean, $schemaMatch)) {
            $tbl = $schemaMatch[2];
            return $this->handleTableSchemaResponse($tbl, $targetDb);
        }

        // 5a. Table Count ("berapa data tabel barang", "hitung baris tabel users")
        if (preg_match('/\b(berapa|jumlah|total|hitung)\s+(data|baris|record)?\s*(di|pada)?\s*tabel\s+([a-zA-Z0-9_\-]+)/i', $clean, $countMatch)) {
            $tbl = $countMatch[4];
            return $this->handleTableCountQuery($tbl, $targetDb);
        }

        // 5b. Table Data ("isi tabel users", "tampilkan data tabel barang")
        if (preg_match('/\b(isi tabel|tampilkan data tabel|lihat tabel|tampilkan data)\s+([a-zA-Z0-9_\-]+)/i', $clean, $dataMatch)) {
            $tbl = $dataMatch[2];
            if ($responseMode === 'count_only') {
                return $this->handleTableCountQuery($tbl, $targetDb);
            }
            return $this->handleTableDataQuery($tbl, $targetDb);
        }

        // 6. List Tables
        if (preg_match('/\b(tabel apa saja|daftar tabel|semua tabel|tabel di database|tabel database|list table|show tables)\b/i', $clean)) {
            return $this->handleListTablesResponse($targetDb);
        }

        // Default to database list
        return $this->handleListDatabasesResponse();
    }

    public function getAllDatabasesList() {
        try {
            $stmt = $this->db->query("SHOW DATABASES");
            $all = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $result = [];
            foreach ($all as $db) {
                if (in_array($db, ['information_schema', 'performance_schema', 'mysql', 'phpmyadmin'])) {
                    continue;
                }
                try {
                    $cnt = $this->db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = " . $this->db->quote($db))->fetchColumn();
                } catch (Throwable $e) {
                    $cnt = 0;
                }
                $result[] = [
                    'name' => $db,
                    'is_active' => ($db === 'smartwarehouse'),
                    'table_count' => (int)$cnt
                ];
            }
            return $result;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getDatabaseTables($dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        try {
            $tables = $this->db->query("SHOW TABLES FROM `$dbName`")->fetchAll(PDO::FETCH_COLUMN);
            $meta = [];
            $descriptions = [
                'barang' => 'Master data material/barang gudang (kode, nama, satuan, min stock, status)',
                'kategori' => 'Master kategori klasifikasi material gudang',
                'lokasi' => 'Master rak dan koordinat lokasi penyimpanan gudang (rack, level, posisi, kapasitas)',
                'stok' => 'Kuantitas stok riil aktual per barang dan per rak lokasi',
                'transaksi' => 'Header bukti transaksi gudang (inbound masuk, outbound keluar, transfer mutasi)',
                'transaksi_detail' => 'Rincian material per transaksi gudang (qty, rak asal, rak tujuan)',
                'users' => 'Data akun pengguna, email, kredensial, peran, dan status login',
                'roles' => 'Master peran dan hak akses otorisasi sistem (Super Admin, Admin, Supervisor, Operator)',
                'log_aktivitas' => 'Audit trail jejak aktivitas pengguna untuk keamanan dan pelacakan sistem',
                'app_settings' => 'Konfigurasi pengaturan aplikasi dan integrasi model Google Gemini AI'
            ];

            foreach ($tables as $t) {
                try {
                    $count = (int)$this->db->query("SELECT COUNT(*) FROM `$dbName`.`$t`")->fetchColumn();
                } catch (Throwable $e) {
                    $count = 0;
                }
                $meta[] = [
                    'table' => $t,
                    'rows' => $count,
                    'description' => $descriptions[$t] ?? 'Tabel data ' . $t . ' pada database ' . $dbName
                ];
            }
            return $meta;
        } catch (Throwable $e) {
            return [];
        }
    }

    public function getTableSchemaDetails($tableName, $dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        $tableName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tableName);
        try {
            return $this->db->query("DESCRIBE `$dbName`.`$tableName`")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    public function executeSafeSqlQuery($sql, $dbName = 'smartwarehouse') {
        $trimmed = trim($sql);
        // Clean leading/trailing markdown backticks
        $trimmed = preg_replace('/^```(?:sql)?\s*/i', '', $trimmed);
        $trimmed = preg_replace('/```$/', '', trim($trimmed));

        // Read-only safety guard
        if (!preg_match('/^\s*(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN)\b/i', $trimmed)) {
            return [
                'success' => false,
                'message' => 'Hanya perintah baca (SELECT, SHOW, DESCRIBE, EXPLAIN) yang diizinkan untuk keamanan database.'
            ];
        }

        // Dangerous destructive keywords block
        if (preg_match('/\b(DROP|ALTER|TRUNCATE|DELETE|UPDATE|INSERT|RENAME|GRANT|REVOKE|INTO\s+OUTFILE|LOAD_FILE)\b/i', $trimmed)) {
            return [
                'success' => false,
                'message' => 'Perintah modifikasi langsung atau perubahan struktur database tidak diizinkan dieksekusi secara raw.'
            ];
        }

        // Auto-limit to 50 if SELECT and no LIMIT specified
        if (preg_match('/^\s*SELECT\b/i', $trimmed) && !preg_match('/\bLIMIT\s+\d+/i', $trimmed)) {
            $trimmed .= " LIMIT 50";
        }

        $start = microtime(true);
        try {
            $stmt = $this->db->query($trimmed);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $duration = round((microtime(true) - $start) * 1000, 2);

            // Mask sensitive fields
            foreach ($rows as &$r) {
                if (isset($r['password'])) {
                    $r['password'] = '•••••••• [terenkripsi]';
                }
            }

            return [
                'success' => true,
                'sql' => $trimmed,
                'count' => count($rows),
                'duration_ms' => $duration,
                'data' => $rows
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Query error: ' . $e->getMessage()
            ];
        }
    }

    public function renderSqlQueryResultHtml($result, $title = null, $explanation = null, $customSuggestions = []) {
        $title = $title ?: 'Hasil Query Database';
        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex align-items-center justify-content-between mb-2">';
        $html .= '<h6 class="fw-bold text-dark mb-0 d-flex align-items-center"><i class="fas fa-database text-primary me-2"></i>' . htmlspecialchars($title) . '</h6>';
        if (isset($result['count'])) {
            $html .= '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary font-monospace" style="font-size:0.7rem;">' . $result['count'] . ' Baris &bull; ' . ($result['duration_ms'] ?? 0) . 'ms</span>';
        }
        $html .= '</div>';

        if (empty($result['data'])) {
            $html .= '<div class="alert alert-light border py-2 px-3 small mb-0"><i class="fas fa-info-circle me-1"></i> Tidak ada baris data yang ditemukan.</div>';
        } else {
            $headers = array_keys($result['data'][0]);
            $html .= '<div class="ai-table-responsive">';
            $html .= '<table class="table table-sm table-hover table-striped mb-0">';
            $html .= '<thead><tr>';
            foreach ($headers as $h) {
                $html .= '<th class="text-nowrap">' . htmlspecialchars(str_replace('_', ' ', $h)) . '</th>';
            }
            $html .= '</tr></thead><tbody>';

            foreach ($result['data'] as $row) {
                $html .= '<tr>';
                foreach ($row as $val) {
                    $html .= '<td class="text-nowrap">' . htmlspecialchars((string)($val ?? 'NULL')) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        $html .= '</div>';
        return $html;
    }

    public function handleListDatabasesResponse() {
        $dbs = $this->getAllDatabasesList();
        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-database text-primary me-2"></i>Daftar Database di Server MySQL</h6>';
        $html .= '<p class="small text-muted mb-2">Ditemukan <strong>' . count($dbs) . ' database</strong> yang siap Anda akses dan tanyakan informasinya:</p>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-hover table-bordered small mb-2">';
        $html .= '<thead class="table-light"><tr><th>Nama Database</th><th>Jml Tabel</th><th>Status</th><th>Aksi</th></tr></thead><tbody>';
        foreach ($dbs as $d) {
            $html .= '<tr>';
            $html .= '<td class="fw-bold font-monospace text-dark">' . htmlspecialchars($d['name']) . '</td>';
            $html .= '<td>' . $d['table_count'] . ' tabel</td>';
            $html .= '<td>' . ($d['is_active'] ? '<span class="badge bg-success">Aktif Aplikasi</span>' : '<span class="badge bg-light text-secondary">Tersedia</span>') . '</td>';
            $html .= '<td><button class="btn btn-xs btn-outline-primary py-0 px-2 ai-chip-btn" data-query="Tampilkan tabel di database ' . htmlspecialchars($d['name']) . '">Lihat Tabel</button></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<small class="text-muted"><i class="fas fa-lightbulb text-warning me-1"></i>Anda dapat menanyakan data, struktur tabel, atau isi dari semua database di atas.</small>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'database_list',
            'title' => 'Daftar Database MySQL',
            'message' => $html,
            'suggestions' => [
                'Tampilkan semua tabel di database smartwarehouse',
                'Statistik database smartwarehouse',
                'Bagaimana relasi antar tabel (ERD)?'
            ]
        ];
    }

    public function handleListTablesResponse($dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        $tables = $this->getDatabaseTables($dbName);

        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex align-items-center justify-content-between mb-2">';
        $html .= '<h6 class="fw-bold text-dark mb-0"><i class="fas fa-table text-primary me-2"></i>Tabel di Database: <code class="text-primary">' . htmlspecialchars($dbName) . '</code></h6>';
        $html .= '<span class="badge bg-primary bg-opacity-10 text-primary font-monospace">' . count($tables) . ' Tabel</span>';
        $html .= '</div>';
        $html .= '<p class="small text-muted mb-2">Berikut adalah tabel-tabel yang tersedia beserta jumlah baris dan fungsinya:</p>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-hover table-bordered small mb-2">';
        $html .= '<thead class="table-light"><tr><th>Nama Tabel</th><th class="text-end">Baris</th><th>Deskripsi / Fungsi</th><th>Aksi</th></tr></thead><tbody>';
        foreach ($tables as $t) {
            $html .= '<tr>';
            $html .= '<td class="fw-bold font-monospace text-dark">' . htmlspecialchars($t['table']) . '</td>';
            $html .= '<td class="text-end fw-semibold">' . number_format($t['rows']) . '</td>';
            $html .= '<td><small class="text-secondary">' . htmlspecialchars($t['description']) . '</small></td>';
            $html .= '<td class="text-nowrap">';
            $html .= '<button class="btn btn-xs btn-outline-info py-0 px-1 me-1 ai-chip-btn" data-query="Struktur tabel ' . htmlspecialchars($t['table']) . ' di database ' . htmlspecialchars($dbName) . '">Skema</button>';
            $html .= '<button class="btn btn-xs btn-outline-secondary py-0 px-1 ai-chip-btn" data-query="Tampilkan data tabel ' . htmlspecialchars($t['table']) . '">Data</button>';
            $html .= '</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="d-flex gap-1 mt-2">';
        $html .= '<button class="btn btn-xs btn-outline-primary ai-chip-btn" data-query="Bagaimana relasi antar tabel (ERD)?">Lihat Relasi ERD</button>';
        $html .= '<button class="btn btn-xs btn-outline-secondary ai-chip-btn" data-query="Apa saja database yang ada?">Daftar Database Lain</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'table_list',
            'title' => 'Daftar Tabel Database ' . $dbName,
            'message' => $html,
            'suggestions' => [
                'Struktur tabel barang',
                'Struktur tabel lokasi',
                'Struktur tabel stok',
                'Struktur tabel transaksi',
                'Struktur tabel users'
            ]
        ];
    }

    public function handleTableSchemaResponse($tableName, $dbName = 'smartwarehouse') {
        $cols = $this->getTableSchemaDetails($tableName, $dbName);
        if (empty($cols)) {
            return [
                'success' => false,
                'type' => 'table_not_found',
                'title' => 'Tabel Tidak Ditemukan',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Tabel <code>' . htmlspecialchars($tableName) . '</code> tidak ditemukan pada database <code>' . htmlspecialchars($dbName) . '</code>.</div>',
                'suggestions' => ['Tampilkan semua tabel di database smartwarehouse']
            ];
        }

        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex align-items-center justify-content-between mb-2">';
        $html .= '<h6 class="fw-bold text-dark mb-0"><i class="fas fa-columns text-primary me-2"></i>Struktur Kolom: <code class="text-primary">' . htmlspecialchars($tableName) . '</code></h6>';
        $html .= '<span class="badge bg-secondary font-monospace">' . htmlspecialchars($dbName) . '</span>';
        $html .= '</div>';
        $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small mb-2">';
        $html .= '<thead class="table-light"><tr><th>Kolom</th><th>Tipe Data</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead><tbody>';
        foreach ($cols as $c) {
            $html .= '<tr>';
            $html .= '<td class="fw-bold font-monospace text-dark">' . htmlspecialchars($c['Field']) . '</td>';
            $html .= '<td class="font-monospace text-info">' . htmlspecialchars($c['Type']) . '</td>';
            $html .= '<td>' . htmlspecialchars($c['Null']) . '</td>';
            $keyBadge = '';
            if ($c['Key'] === 'PRI') $keyBadge = '<span class="badge bg-warning text-dark">PRI</span>';
            elseif ($c['Key'] === 'UNI') $keyBadge = '<span class="badge bg-info">UNI</span>';
            elseif ($c['Key'] === 'MUL') $keyBadge = '<span class="badge bg-secondary">MUL</span>';
            $html .= '<td>' . ($keyBadge ?: '-') . '</td>';
            $html .= '<td><small class="text-muted">' . htmlspecialchars((string)($c['Default'] ?? 'NULL')) . '</small></td>';
            $html .= '<td><small class="text-muted">' . htmlspecialchars($c['Extra'] ?? '-') . '</small></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '<div class="d-flex gap-1 mt-2">';
        $html .= '<button class="btn btn-xs btn-outline-primary ai-chip-btn" data-query="Tampilkan data tabel ' . htmlspecialchars($tableName) . '">Lihat Data Sampel</button>';
        $html .= '<button class="btn btn-xs btn-outline-secondary ai-chip-btn" data-query="Tampilkan semua tabel di database smartwarehouse">Kembali ke Daftar Tabel</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'table_schema',
            'title' => 'Struktur Tabel ' . $tableName,
            'message' => $html,
            'suggestions' => [
                'Tampilkan data tabel ' . $tableName,
                'Tampilkan semua tabel di database smartwarehouse',
                'Bagaimana relasi antar tabel (ERD)?'
            ]
        ];
    }

    public function handleTableDataQuery($tableName, $dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        $tableName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tableName);
        $sql = "SELECT * FROM `$dbName`.`$tableName` LIMIT 15";
        $res = $this->executeSafeSqlQuery($sql);

        $html = $this->renderSqlQueryResultHtml(
            $res,
            "Isi Tabel: $tableName",
            "Menampilkan 15 data teratas dari tabel <code>$tableName</code> pada database <code>$dbName</code>."
        );

        return [
            'success' => true,
            'type' => 'table_data',
            'title' => 'Data Tabel ' . $tableName,
            'message' => $html,
            'suggestions' => [
                'Struktur tabel ' . $tableName,
                'Tampilkan semua tabel di database smartwarehouse',
                'Statistik database smartwarehouse'
            ]
        ];
    }

    public function handleTableCountQuery($tableName, $dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        $tableName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $tableName);
        try {
            $count = (int)$this->db->query("SELECT COUNT(*) FROM `$dbName`.`$tableName`")->fetchColumn();
        } catch (Throwable $e) {
            return [
                'success' => false,
                'type' => 'table_count_error',
                'title' => 'Gagal Menghitung Tabel',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> Gagal membaca tabel <code>' . htmlspecialchars($tableName) . '</code>: ' . htmlspecialchars($e->getMessage()) . '</div>'
            ];
        }

        $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<div class="d-flex align-items-center gap-3">';
        $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
        $html .= '<i class="fas fa-calculator fa-lg"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<div class="text-muted small mb-1">Total data di tabel <span class="badge bg-light text-dark font-monospace border">' . htmlspecialchars($tableName) . '</span></div>';
        $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . number_format($count) . ' <small class="fs-6 text-muted fw-normal">baris</small></div>';
        $html .= '<div class="small text-muted mt-1">Database: <code>' . htmlspecialchars($dbName) . '</code></div>';
        $html .= '</div></div>';
        $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan data tabel ' . htmlspecialchars($tableName) . '&quot;</em> untuk melihat isi tabelnya.</div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'table_count',
            'title' => 'Jumlah Data Tabel ' . $tableName,
            'message' => $html,
            'count' => $count,
            'suggestions' => [
                'Tampilkan data tabel ' . $tableName,
                'Struktur tabel ' . $tableName,
                'Tampilkan semua tabel di database smartwarehouse'
            ]
        ];
    }

    public function handleDatabaseErdResponse() {
        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-project-diagram text-primary me-2"></i>Struktur Relasi Antar Tabel (ERD) SmartWarehouse</h6>';
        $html .= '<p class="small text-secondary mb-2">Relasi database <code>smartwarehouse</code> didesain ternormalisasi dengan integritas referensial sebagai berikut:</p>';
        
        $html .= '<div class="bg-light p-2 rounded border small mb-3">';
        $html .= '<div class="mb-2"><strong>1. Master Barang & Kategori:</strong><br><span class="badge bg-secondary">kategori.id</span> &rarr; <span class="badge bg-primary">barang.kategori_id</span> <small class="text-muted">(1 kategori memiliki banyak barang)</small></div>';
        $html .= '<div class="mb-2"><strong>2. Inventori Stok di Rak:</strong><br><span class="badge bg-primary">barang.id</span> &rarr; <span class="badge bg-warning text-dark">stok.barang_id</span><br><span class="badge bg-info">lokasi.id</span> &rarr; <span class="badge bg-warning text-dark">stok.lokasi_id</span> <small class="text-muted">(Pivot kuantitas riil per rak)</small></div>';
        $html .= '<div class="mb-2"><strong>3. Transaksi & Bukti Mutasi:</strong><br><span class="badge bg-dark">users.id</span> &rarr; <span class="badge bg-success">transaksi.user_id</span><br><span class="badge bg-success">transaksi.id</span> &rarr; <span class="badge bg-success">transaksi_detail.transaksi_id</span><br><span class="badge bg-primary">barang.id</span> &rarr; <span class="badge bg-success">transaksi_detail.barang_id</span><br><span class="badge bg-info">lokasi.id</span> &rarr; <span class="badge bg-success">transaksi_detail.lokasi_id</span> (asal & tujuan)</div>';
        $html .= '<div><strong>4. Autentikasi & Log Audit:</strong><br><span class="badge bg-danger">roles.id</span> &rarr; <span class="badge bg-dark">users.role_id</span><br><span class="badge bg-dark">users.id</span> &rarr; <span class="badge bg-secondary">log_aktivitas.user_id</span></div>';
        $html .= '</div>';

        $html .= '<div class="d-flex flex-wrap gap-1">';
        $html .= '<button class="btn btn-xs btn-outline-primary ai-chip-btn" data-query="Tampilkan semua tabel di database smartwarehouse">Lihat Semua Tabel</button>';
        $html .= '<button class="btn btn-xs btn-outline-info ai-chip-btn" data-query="Statistik database smartwarehouse">Cek Statistik Data</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'database_erd',
            'title' => 'Relasi Tabel Database',
            'message' => $html,
            'suggestions' => [
                'Tampilkan semua tabel di database smartwarehouse',
                'Struktur tabel barang',
                'Struktur tabel transaksi',
                'Statistik database smartwarehouse'
            ]
        ];
    }

    public function handleDatabaseStatsResponse($dbName = 'smartwarehouse') {
        $dbName = preg_replace('/[^a-zA-Z0-9_\-]/', '', $dbName) ?: 'smartwarehouse';
        $tables = $this->getDatabaseTables($dbName);
        $totalRows = 0;
        foreach ($tables as $t) {
            $totalRows += $t['rows'];
        }

        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-chart-pie text-primary me-2"></i>Statistik Database: <code class="text-primary">' . htmlspecialchars($dbName) . '</code></h6>';
        
        $html .= '<div class="row g-2 mb-3">';
        $html .= '<div class="col-6"><div class="p-2 border rounded bg-light text-center"><small class="text-muted d-block">Total Tabel</small><span class="fs-5 fw-bold text-primary">' . count($tables) . '</span></div></div>';
        $html .= '<div class="col-6"><div class="p-2 border rounded bg-light text-center"><small class="text-muted d-block">Akumulasi Baris</small><span class="fs-5 fw-bold text-success">' . number_format($totalRows) . '</span></div></div>';
        $html .= '</div>';

        $html .= '<div class="table-responsive"><table class="table table-sm table-bordered small mb-2">';
        $html .= '<thead class="table-light"><tr><th>Tabel</th><th class="text-end">Baris</th><th>Deskripsi</th></tr></thead><tbody>';
        foreach ($tables as $t) {
            $html .= '<tr><td class="font-monospace fw-bold">' . htmlspecialchars($t['table']) . '</td><td class="text-end fw-semibold">' . number_format($t['rows']) . '</td><td><small class="text-muted">' . htmlspecialchars($t['description']) . '</small></td></tr>';
        }
        $html .= '</tbody></table></div>';
        $html .= '</div>';

        return [
            'success' => true,
            'type' => 'database_stats',
            'title' => 'Statistik Database',
            'message' => $html,
            'suggestions' => [
                'Tampilkan semua tabel di database smartwarehouse',
                'Bagaimana relasi tabel ERD di database?',
                'Apa saja database yang ada?'
            ]
        ];
    }

    public function handleDatabaseQueryFromGemini($sql, $explanation, $reply, $userContext = [], $responseMode = 'show_data', $rawMessage = '') {
        $res = $this->executeSafeSqlQuery($sql);
        if (!$res['success']) {
            return [
                'success' => false,
                'type' => 'database_error',
                'title' => 'Query Database Gagal',
                'message' => '<div class="alert alert-warning py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> ' . htmlspecialchars($res['message']) . '</div>',
                'suggestions' => ['Tampilkan semua tabel di database smartwarehouse', 'Statistik database smartwarehouse']
            ];
        }

        // ── COUNT ONLY MODE ──────────────────────────────────────────────────
        // Jika user hanya bertanya "berapa" (tanpa "tampilkan"), kembalikan
        // angka/jumlah saja, bukan tabel penuh.
        if ($responseMode === 'count_only') {
            $totalCount = null;
            $dataRows = $res['data'] ?? [];

            // 1. Jika query menghasilkan agregasi 1 baris 1 kolom (misal: COUNT(*) atau SUM(*))
            if (count($dataRows) === 1 && count($dataRows[0]) === 1) {
                $firstVal = reset($dataRows[0]);
                if (is_numeric($firstVal)) {
                    $totalCount = (int)$firstVal;
                }
            }

            // 2. Jika query mengembalikan baris data biasa (SELECT ...), hitung total baris sesungguhnya
            if ($totalCount === null) {
                $cleanSql = preg_replace('/^```(?:sql)?\s*/i', '', trim($sql));
                $cleanSql = preg_replace('/```$/', '', trim($cleanSql));
                $cleanSql = rtrim($cleanSql, ';');
                // Hapus LIMIT bawaan agar menghitung jumlah total riil
                $sqlNoLimit = preg_replace('/\s+LIMIT\s+\d+(\s*,\s*\d+)?\s*$/i', '', $cleanSql);

                try {
                    $countStmt = $this->db->query("SELECT COUNT(*) AS total_count FROM ($sqlNoLimit) AS _sub_count");
                    if ($countStmt) {
                        $countRow = $countStmt->fetch(PDO::FETCH_ASSOC);
                        if ($countRow && isset($countRow['total_count'])) {
                            $totalCount = (int)$countRow['total_count'];
                        }
                    }
                } catch (Throwable $e) {
                    $totalCount = count($dataRows);
                }

                if ($totalCount === null) {
                    $totalCount = count($dataRows);
                }
            }

            // Deskripsi label
            $descText = 'Total hasil data';
            if (!empty($explanation)) {
                $descText = htmlspecialchars($explanation);
            } elseif (!empty($reply)) {
                $cleanReply = trim(strip_tags($reply));
                if (mb_strlen($cleanReply) > 0 && mb_strlen($cleanReply) < 100) {
                    $descText = htmlspecialchars($cleanReply);
                }
            }

            $html = '<div class="ai-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
            $html .= '<div class="d-flex align-items-center gap-3">';
            $html .= '<div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">';
            $html .= '<i class="fas fa-calculator fa-lg"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<div class="text-muted small mb-1">' . $descText . '</div>';
            $html .= '<div class="fs-3 fw-bold text-primary lh-1">' . number_format($totalCount) . '</div>';
            $html .= '<div class="small text-muted mt-1 font-monospace" style="font-size:0.75rem;">' . ($res['duration_ms'] ?? 0) . 'ms</div>';
            $html .= '</div></div>';
            $html .= '<div class="mt-2 pt-2 border-top small text-muted">Ketik <em>&quot;Tampilkan data...&quot;</em> untuk melihat daftar lengkap dalam bentuk tabel.</div>';
            $html .= '</div>';

            return [
                'success' => true,
                'type' => 'database_count_result',
                'title' => 'Jumlah Data Database',
                'message' => $html,
                'count' => $totalCount,
                'suggestions' => [
                    'Tampilkan ' . (!empty($rawMessage) ? htmlspecialchars($rawMessage) : 'data terkait'),
                    'Tampilkan semua tabel di database smartwarehouse',
                    'Statistik database smartwarehouse'
                ]
            ];
        }

        // ── SHOW DATA MODE (DEFAULT) ─────────────────────────────────────────
        $html = $this->renderSqlQueryResultHtml($res, 'Hasil Data Database (Gemini AI)');

        return [
            'success' => true,
            'type' => 'database_query_result',
            'title' => 'Data Database',
            'message' => $html,
            'suggestions' => [
                'Tampilkan semua tabel di database smartwarehouse',
                'Statistik database smartwarehouse',
                'Bagaimana relasi tabel ERD di database?'
            ]
        ];
    }

    public function handleDatabaseInfoFromGemini($action, $target, $reply, $userContext = []) {
        if ($action === 'list_databases') {
            return $this->handleListDatabasesResponse();
        }
        if ($action === 'list_tables') {
            return $this->handleListTablesResponse(!empty($target) ? $target : 'smartwarehouse');
        }
        if ($action === 'table_schema' && !empty($target)) {
            return $this->handleTableSchemaResponse($target, 'smartwarehouse');
        }
        if ($action === 'relationships' || $action === 'erd') {
            return $this->handleDatabaseErdResponse();
        }
        if ($action === 'stats') {
            return $this->handleDatabaseStatsResponse(!empty($target) ? $target : 'smartwarehouse');
        }

        // If Gemini provided a direct reply HTML, wrap it in a nice database card
        $html = '<div class="ai-db-card p-3 rounded-3 mb-2 bg-white border shadow-sm">';
        $html .= '<h6 class="fw-bold text-dark mb-2"><i class="fas fa-database text-primary me-2"></i>Informasi Database SmartWarehouse</h6>';
        $html .= '<div class="small text-secondary mb-2">' . $reply . '</div>';
        $html .= '<div class="d-flex gap-1 mt-2">';
        $html .= '<button class="btn btn-xs btn-outline-primary ai-chip-btn" data-query="Apa saja database yang ada?">Daftar Database</button>';
        $html .= '<button class="btn btn-xs btn-outline-info ai-chip-btn" data-query="Tampilkan semua tabel di database smartwarehouse">Daftar Tabel</button>';
        $html .= '</div></div>';

        return [
            'success' => true,
            'type' => 'database_info',
            'title' => 'Informasi Database',
            'message' => $html,
            'suggestions' => [
                'Apa saja database yang ada?',
                'Tampilkan semua tabel di database smartwarehouse',
                'Bagaimana relasi tabel ERD di database?'
            ]
        ];
    }
}


