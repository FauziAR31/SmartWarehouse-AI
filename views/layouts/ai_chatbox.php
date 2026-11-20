<?php
// Only display AI Chatbox if user is logged in
if (!isset($_SESSION['user_id'])) {
    return;
}
?>

<!-- ============================================================== -->
<!-- SMART WAREHOUSE AI CHATBOX WIDGET -->
<!-- ============================================================== -->

<!-- Floating AI Trigger Button -->
<div id="ai-chat-launcher" class="ai-launcher shadow-lg" title="Buka Asisten AI SmartWarehouse">
    <div class="ai-launcher-pulse"></div>
    <div class="ai-launcher-icon">
        <i class="fas fa-robot"></i>
    </div>
    <span class="ai-launcher-badge">AI Assistant</span>
</div>

<!-- Floating AI Chat Window -->
<div id="ai-chat-window" class="ai-chat-container shadow-lg d-none">
    <!-- Chat Header -->
    <div class="ai-chat-header d-flex align-items-center justify-content-between px-3 py-3 text-white">
        <div class="d-flex align-items-center">
            <div class="ai-avatar-wrapper position-relative me-2">
                <div class="ai-avatar-circle">
                    <i class="fas fa-robot text-primary"></i>
                </div>
                <span class="ai-status-dot position-absolute bottom-0 end-0"></span>
            </div>
            <div>
                <h6 class="mb-0 fw-bold text-white fs-6 d-flex align-items-center">
                    Smart Warehouse AI
                    <span class="badge bg-light text-primary ms-2" style="font-size: 0.65rem; padding: 2px 6px;">PRO</span>
                </h6>
                <small class="text-white-50" style="font-size: 0.72rem;">
                    <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>Siap cek database, stok rak, SQL & mutasi
                </small>
            </div>
        </div>
        <div class="ai-header-actions d-flex align-items-center gap-1">
            <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="ai-btn-api-key" title="Pengaturan Gemini AI & API Key">
                <i class="fas fa-key"></i>
            </button>
            <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="ai-btn-clear" title="Hapus Riwayat Chat">
                <i class="fas fa-trash-alt"></i>
            </button>
            <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="ai-btn-minimize" title="Perkecil Window">
                <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white" id="ai-btn-close" title="Tutup Chat">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <!-- Gemini AI API Key Settings Drawer/Panel -->
    <div id="ai-api-key-panel" class="p-3 bg-light border-bottom d-none shadow-sm position-relative" style="z-index: 10;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fw-bold small text-dark d-flex align-items-center">
                <i class="fas fa-robot text-primary me-2"></i>Pengaturan Gemini AI
            </div>
            <button type="button" class="btn btn-sm btn-icon text-muted" id="ai-btn-close-settings" title="Tutup Panel">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div id="ai-key-status-badge" class="mb-2">
            <span class="badge bg-secondary"><i class="fas fa-spinner fa-spin me-1"></i>Memeriksa status API...</span>
        </div>
        <div class="mb-2">
            <label class="form-label small fw-semibold mb-1">Gemini API Key</label>
            <div class="input-group input-group-sm">
                <input type="password" id="ai-input-api-key" class="form-control font-monospace" placeholder="Tempel Google Gemini API Key (AIzaSy...)">
                <button class="btn btn-outline-secondary" type="button" id="ai-btn-toggle-key-eye"><i class="fas fa-eye"></i></button>
            </div>
            <small class="text-muted d-block mt-1" style="font-size:0.72rem;">
                Belum punya API Key? <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-primary fw-semibold"><i class="fas fa-external-link-alt me-1"></i>Dapatkan gratis di Google AI Studio</a>
            </small>
        </div>
        <div class="mb-3">
            <label class="form-label small fw-semibold mb-1">Pilih Model Gemini</label>
            <select id="ai-select-model" class="form-select form-select-sm">
                <option value="gemini-3.8-flash" selected>Gemini 3.8 Flash (Direkomendasikan - Generasi Terbaru)</option>
                <option value="gemini-2.0-flash">Gemini 2.0 Flash (Cepat & Stabil)</option>
                <option value="gemini-1.5-flash">Gemini 1.5 Flash (Generasi Sebelumnya)</option>
                <option value="gemini-1.5-pro">Gemini 1.5 Pro (Penalaran Rumit)</option>
                <option value="gemini-2.5-flash">Gemini 2.5 Flash</option>
            </select>
        </div>
        <div class="d-flex gap-2">
            <button type="button" id="ai-btn-save-key" class="btn btn-sm btn-primary flex-grow-1">
                <i class="fas fa-save me-1"></i>Simpan & Uji Koneksi
            </button>
            <button type="button" id="ai-btn-test-key" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-vial me-1"></i>Uji
            </button>
        </div>
        <div id="ai-key-feedback" class="small mt-2 d-none"></div>
    </div>

    <!-- Alert banner if API Key is not configured yet -->
    <div id="ai-key-unconfigured-notice" class="px-3 py-2 bg-warning bg-opacity-10 border-bottom d-none">
        <div class="d-flex align-items-center justify-content-between small">
            <div>
                <i class="fas fa-info-circle text-warning me-1"></i>
                <span class="text-dark">Gemini API Key belum aktif.</span>
            </div>
            <button type="button" id="ai-btn-open-settings-from-notice" class="btn btn-xs btn-outline-dark py-0 px-2 fw-semibold">
                Atur API Key
            </button>
        </div>
    </div>

    <!-- Quick Action Shortcut Chips -->
    <div class="ai-quick-chips px-3 py-2 border-bottom bg-light">
        <div class="d-flex gap-2 overflow-x-auto pb-1 ai-chips-scroll">
            <button class="btn btn-xs btn-chip ai-chip-btn bg-primary text-white border-primary" data-query="Panduan Jobdesk">
                <i class="fas fa-briefcase me-1"></i>Panduan Jobdesk
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn bg-info text-white border-info" data-query="Apa saja database yang ada?">
                <i class="fas fa-database me-1"></i>Semua Database
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn bg-dark text-white border-dark" data-query="Tampilkan semua tabel di database smartwarehouse">
                <i class="fas fa-table me-1"></i>Tabel Database
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn text-primary border-primary" data-query="Bagaimana relasi antar tabel (ERD) di database?">
                <i class="fas fa-project-diagram me-1"></i>Relasi ERD
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn text-success border-success" data-query="Statistik dan jumlah data di database">
                <i class="fas fa-chart-pie me-1"></i>Statistik Data
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Siapa saja user yang ada di database?">
                <i class="fas fa-users text-info me-1"></i>Data User
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Terima 10 box Susu UHT Full Cream 1 Liter di rak B.2.01 dari PT Indofood Sejahtera">
                <i class="fas fa-arrow-circle-down text-success me-1"></i>Barang Masuk
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Keluarkan 2 box Susu UHT Full Cream 1 Liter dari rak B.2.01 untuk Supermarket Indah">
                <i class="fas fa-arrow-circle-up text-danger me-1"></i>Barang Keluar
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Pindahkan 1 box Susu UHT Full Cream 1 Liter dari rak B.2.01 ke rak B.1.01">
                <i class="fas fa-exchange-alt text-warning me-1"></i>Pindah Rak
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Audit stok rak A.1.01">
                <i class="fas fa-clipboard-check text-warning me-1"></i>Stock Opname
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Tampilkan rak-rak yang masih kosong">
                <i class="fas fa-boxes text-info me-1"></i>Rak Kosong
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Dimana saja lokasi Laptop ASUS ROG Strix?">
                <i class="fas fa-search-location text-primary me-1"></i>Cek Lokasi
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Ada berapakah qty pada rak A.1.01?">
                <i class="fas fa-box text-secondary me-1"></i>Qty di Rak
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Tampilkan barang dengan stok kritis">
                <i class="fas fa-exclamation-triangle text-danger me-1"></i>Stok Kritis
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="5 transaksi terakhir">
                <i class="fas fa-history text-secondary me-1"></i>Riwayat Transaksi
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Laporan barang masuk hari ini">
                <i class="fas fa-file-invoice text-success me-1"></i>Laporan Hari Ini
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Tambah barang baru: nama Keyboard Mechanical RGB, kode BRG-ELK-006, kategori Elektronik, satuan pcs, min stok 10">
                <i class="fas fa-plus text-primary me-1"></i>+ Barang
            </button>
            <button class="btn btn-xs btn-chip ai-chip-btn" data-query="Tambah rak baru: kode D.1.01, nama Gudang D - Rak 1, kapasitas 150">
                <i class="fas fa-map-marker-alt text-danger me-1"></i>+ Rak Baru
            </button>
        </div>
    </div>

    <!-- Messages Container -->
    <div class="ai-chat-body px-3 py-3" id="ai-chat-messages">
        <!-- Initial Welcome Message from AI -->
        <div class="ai-message ai-message-incoming mb-3">
            <div class="ai-msg-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="ai-msg-bubble">
                <p class="mb-2">Halo <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Rekan Warehouse'); ?></strong>! 👋</p>
                <p class="mb-2">Saya <strong>SmartWarehouse AI Hub</strong>. Seluruh operasional gudang dan seluruh pertanyaan seputar database siap dijawab:</p>
                
                <div class="p-2 bg-light rounded-2 border mb-2 small">
                    <div class="fw-bold text-primary mb-1"><i class="fas fa-tasks me-1"></i>Fitur & Layanan AI yang Siap:</div>
                    <ul class="mb-0 ps-3">
                        <li><strong>🗄️ Tanya Semua Database:</strong> <em>"Apa saja database yang ada?"</em> / <em>"Tampilkan semua tabel di database"</em> / <em>"Struktur tabel barang"</em> / <em>"Siapa saja user di database?"</em></li>
                        <li><strong>📥 Inbound / Masuk:</strong> <em>"Terima 20 unit Laptop ASUS di rak A.1.01 dari PT Elektronik"</em></li>
                        <li><strong>📤 Outbound / Keluar:</strong> <em>"Keluarkan 5 unit Smartphone dari rak A.1.01 untuk Toko Sentosa"</em></li>
                        <li><strong>🔄 Mutasi Stok:</strong> <em>"Pindahkan 2 unit Laptop ASUS dari A.1.01 ke A.1.02"</em></li>
                        <li><strong>📋 Stock Opname:</strong> <em>"Audit stok rak A.1.01"</em></li>
                        <li><strong>🔍 Cek Lokasi & Rak:</strong> <em>"Dimana saja lokasi material ini?"</em> / <em>"Ada berapakah qty pada rak ini?"</em></li>
                        <li><strong>➕ Master Data:</strong> <em>"Tambah barang baru..."</em> / <em>"Tambah rak baru..."</em></li>
                    </ul>
                </div>

                <div class="small text-muted mb-0">Ketik pertanyaan database/instruksi Anda atau klik tombol cepat di atas untuk memulai.</div>
                <div class="ai-msg-time"><?php echo date('H:i'); ?></div>
            </div>
        </div>
    </div>

    <!-- Typing Loader -->
    <div class="ai-typing-indicator px-3 py-2 d-none" id="ai-typing-indicator">
        <div class="ai-typing-bubble">
            <span class="ai-typing-dot"></span>
            <span class="ai-typing-dot"></span>
            <span class="ai-typing-dot"></span>
            <small class="ms-2 text-muted" style="font-size: 0.75rem;">AI sedang berpikir...</small>
        </div>
    </div>

    <!-- Chat Footer / Input -->
    <div class="ai-chat-footer p-2 border-top bg-white">
        <form id="ai-chat-form" class="m-0" onsubmit="return false;">
            <input type="hidden" id="ai-csrf-token" value="<?php echo SecurityHelper::csrfToken(); ?>">
            <div class="input-group">
                <textarea 
                    id="ai-user-input" 
                    class="form-control ai-input-field" 
                    placeholder="Tanyakan database, tabel, stok, lokasi rak, atau query SQL..." 
                    rows="1" 
                    maxlength="500" 
                    required></textarea>
                <button type="submit" id="ai-btn-send" class="btn btn-primary ai-send-btn" title="Kirim Pesan">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-1 px-1">
                <small class="text-muted" style="font-size: 0.68rem;">
                    <i class="fas fa-keyboard me-1"></i>Enter untuk kirim &bull; Shift+Enter baris baru
                </small>
                <small class="text-muted" style="font-size: 0.68rem;" id="ai-char-count">0/500</small>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================== -->
<!-- CSS STYLES FOR AI CHATBOX -->
<!-- ============================================================== -->
<style>
/* Floating Launcher Button */
.ai-launcher {
    position: fixed;
    bottom: 24px;
    right: 24px;
    height: 56px;
    padding: 0 18px 0 14px;
    border-radius: 28px;
    background: linear-gradient(135deg, #0D8ABC 0%, #1e293b 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    z-index: 1060;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    border: 2px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 10px 25px rgba(13, 138, 188, 0.35);
}

.ai-launcher:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 14px 28px rgba(13, 138, 188, 0.45);
}

.ai-launcher-icon {
    width: 36px;
    height: 36px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
}

.ai-launcher-badge {
    font-weight: 600;
    font-size: 0.9rem;
    letter-spacing: 0.3px;
    white-space: nowrap;
}

.ai-launcher-pulse {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    border-radius: 28px;
    border: 2px solid rgba(13, 138, 188, 0.7);
    animation: aiPulse 2.2s infinite;
    pointer-events: none;
}

@keyframes aiPulse {
    0% { transform: scale(1); opacity: 0.8; }
    50% { transform: scale(1.08); opacity: 0; }
    100% { transform: scale(1); opacity: 0; }
}

/* Chat Window Container */
.ai-chat-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    width: 420px;
    max-width: calc(100vw - 32px);
    height: 600px;
    max-height: calc(100vh - 48px);
    background: #ffffff;
    border-radius: 20px;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    z-index: 1070;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 20px 45px rgba(15, 23, 42, 0.25) !important;
    animation: aiSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes aiSlideUp {
    from {
        opacity: 0;
        transform: translateY(30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.ai-chat-container.minimized {
    height: 64px !important;
}

/* Header */
.ai-chat-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0D8ABC 100%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
}

.ai-avatar-circle {
    width: 38px;
    height: 38px;
    background: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.ai-status-dot {
    width: 10px;
    height: 10px;
    background-color: #10b981;
    border: 2px solid #0f172a;
    border-radius: 50%;
}

.btn-icon {
    width: 30px;
    height: 30px;
    padding: 0;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s;
}

.btn-icon:hover {
    background-color: rgba(255, 255, 255, 0.15);
    color: #ffffff !important;
}

/* Quick Chips */
.ai-quick-chips {
    flex-shrink: 0;
}

.ai-chips-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.ai-chips-scroll::-webkit-scrollbar {
    height: 4px;
}

.ai-chips-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.btn-chip {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 20px;
    white-space: nowrap;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-chip:hover {
    background: #0D8ABC;
    color: #ffffff;
    border-color: #0D8ABC;
}

.btn-chip:hover i {
    color: #ffffff !important;
}

/* Chat Body / Messages */
.ai-chat-body {
    flex-grow: 1;
    overflow-y: auto;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.ai-chat-body::-webkit-scrollbar {
    width: 5px;
}

.ai-chat-body::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

/* Message Bubbles */
.ai-message {
    display: flex;
    gap: 8px;
    max-width: 92%;
    animation: aiFadeIn 0.25s ease both;
}

@keyframes aiFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
}

.ai-message-incoming {
    align-self: flex-start;
}

.ai-message-outgoing {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.ai-msg-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #0D8ABC;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.ai-msg-bubble {
    background: #ffffff;
    padding: 10px 14px;
    border-radius: 14px;
    border-top-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    font-size: 0.86rem;
    color: #1e293b;
    word-break: break-word;
}

.ai-message-outgoing .ai-msg-bubble {
    background: linear-gradient(135deg, #0D8ABC 0%, #0369a1 100%);
    color: #ffffff;
    border-radius: 14px;
    border-top-right-radius: 4px;
    border: none;
    box-shadow: 0 3px 8px rgba(13, 138, 188, 0.3);
}

.ai-msg-time {
    font-size: 0.65rem;
    color: #94a3b8;
    text-align: right;
    margin-top: 4px;
}

.ai-message-outgoing .ai-msg-time {
    color: rgba(255, 255, 255, 0.7);
}

/* Typing Indicator */
.ai-typing-indicator {
    background: #f8fafc;
}

.ai-typing-bubble {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
}

.ai-typing-dot {
    width: 6px;
    height: 6px;
    background-color: #0D8ABC;
    border-radius: 50%;
    margin: 0 2px;
    animation: aiBounce 1.4s infinite ease-in-out both;
}

.ai-typing-dot:nth-child(1) { animation-delay: -0.32s; }
.ai-typing-dot:nth-child(2) { animation-delay: -0.16s; }

@keyframes aiBounce {
    0%, 80%, 100% { transform: scale(0); }
    40% { transform: scale(1); }
}

/* Chat Input Footer */
.ai-chat-footer {
    flex-shrink: 0;
    border-top: 1px solid #e2e8f0;
}

.ai-input-field {
    border-radius: 20px 0 0 20px !important;
    border: 1px solid #cbd5e1;
    border-right: none;
    padding: 8px 14px;
    font-size: 0.86rem;
    resize: none;
    box-shadow: none !important;
    max-height: 90px;
}

.ai-input-field:focus {
    border-color: #0D8ABC;
}

.ai-send-btn {
    border-radius: 0 20px 20px 0 !important;
    padding: 8px 16px;
    background: #0D8ABC;
    border-color: #0D8ABC;
    transition: all 0.2s;
}

.ai-send-btn:hover {
    background: #0369a1;
    border-color: #0369a1;
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .ai-launcher {
        bottom: 16px;
        right: 16px;
        height: 46px;
        padding: 0 14px 0 10px;
    }
    .ai-launcher-icon {
        width: 30px;
        height: 30px;
        font-size: 1rem;
    }
    .ai-launcher-badge {
        font-size: 0.8rem;
    }
    .ai-chat-container {
        bottom: 0;
        right: 0;
        width: 100vw;
        height: 100%;
        max-width: 100vw;
        max-height: 100%;
        border-radius: 0;
    }
}

/* Prevent content from being hidden behind fixed AI launcher */
.main-content-container {
    padding-bottom: 90px !important;
}
@media (max-width: 576px) {
/* AI Database & SQL UI Enhancements */
.ai-db-card {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}
.ai-table-responsive {
    overflow-x: auto;
    max-width: 100%;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
}
.ai-table-responsive table {
    font-size: 0.76rem;
    margin-bottom: 0;
}
.ai-table-responsive th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    padding: 6px 10px;
    white-space: nowrap;
}
.ai-table-responsive td {
    padding: 5px 10px;
    vertical-align: middle;
}
.ai-sql-box {
    background: #0f172a;
    color: #38bdf8;
    border-radius: 6px;
    padding: 6px 10px;
    font-family: SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 0.72rem;
    overflow-x: auto;
    border: 1px solid rgba(255, 255, 255, 0.1);
}
</style>

<!-- ============================================================== -->
<!-- JAVASCRIPT LOGIC FOR AI CHATBOX -->
<!-- ============================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const launcher   = document.getElementById('ai-chat-launcher');
    const windowEl   = document.getElementById('ai-chat-window');
    const btnClose   = document.getElementById('ai-btn-close');
    const btnMin     = document.getElementById('ai-btn-minimize');
    const btnClear   = document.getElementById('ai-btn-clear');
    const form       = document.getElementById('ai-chat-form');
    const input      = document.getElementById('ai-user-input');
    const btnSend    = document.getElementById('ai-btn-send');
    const messagesEl = document.getElementById('ai-chat-messages');
    const typingEl   = document.getElementById('ai-typing-indicator');
    const charCount  = document.getElementById('ai-char-count');
    const csrfToken  = document.getElementById('ai-csrf-token') ? document.getElementById('ai-csrf-token').value : '';

    // API Key Settings Elements
    const btnApiKey          = document.getElementById('ai-btn-api-key');
    const apiKeyPanel        = document.getElementById('ai-api-key-panel');
    const btnCloseSettings   = document.getElementById('ai-btn-close-settings');
    const btnToggleEye       = document.getElementById('ai-btn-toggle-key-eye');
    const inputApiKey        = document.getElementById('ai-input-api-key');
    const selectModel        = document.getElementById('ai-select-model');
    const btnSaveKey         = document.getElementById('ai-btn-save-key');
    const btnTestKey         = document.getElementById('ai-btn-test-key');
    const statusBadge        = document.getElementById('ai-key-status-badge');
    const feedbackEl         = document.getElementById('ai-key-feedback');
    const unconfiguredNotice = document.getElementById('ai-key-unconfigured-notice');
    const btnOpenFromNotice  = document.getElementById('ai-btn-open-settings-from-notice');

    if (!launcher || !windowEl) return;

    // Fetch API Key status on load
    fetchApiKeyStatus();

    function fetchApiKeyStatus() {
        if (!statusBadge) return;
        fetch('<?php echo BASE_URL; ?>/aichat/getApiKey', {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.is_configured) {
                    statusBadge.innerHTML = `<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Gemini AI Aktif (${data.model})</span>`;
                    if (unconfiguredNotice) unconfiguredNotice.classList.add('d-none');
                    if (inputApiKey && !inputApiKey.value) {
                        inputApiKey.placeholder = `Tersimpan: ${data.masked_key} (isi jika ingin mengganti)`;
                    }
                    if (selectModel && data.model) {
                        selectModel.value = (data.model === 'gemini-2.5-flash') ? 'gemini-3.8-flash' : data.model;
                    }
                } else {
                    statusBadge.innerHTML = `<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i>Gemini API Key Belum Dikonfigurasi</span>`;
                    if (unconfiguredNotice) unconfiguredNotice.classList.remove('d-none');
                }
            }
        })
        .catch(err => {
            console.error('Failed to get Gemini status:', err);
        });
    }

    if (btnApiKey && apiKeyPanel) {
        btnApiKey.addEventListener('click', function () {
            const isHidden = apiKeyPanel.classList.contains('d-none');
            if (isHidden) {
                apiKeyPanel.classList.remove('d-none');
                fetchApiKeyStatus();
            } else {
                apiKeyPanel.classList.add('d-none');
            }
        });
    }

    if (btnCloseSettings && apiKeyPanel) {
        btnCloseSettings.addEventListener('click', function () {
            apiKeyPanel.classList.add('d-none');
        });
    }

    if (btnOpenFromNotice && apiKeyPanel) {
        btnOpenFromNotice.addEventListener('click', function () {
            apiKeyPanel.classList.remove('d-none');
            if (inputApiKey) inputApiKey.focus();
        });
    }

    if (btnToggleEye && inputApiKey) {
        btnToggleEye.addEventListener('click', function () {
            const isPassword = inputApiKey.getAttribute('type') === 'password';
            inputApiKey.setAttribute('type', isPassword ? 'text' : 'password');
            this.innerHTML = isPassword ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    }

    if (btnSaveKey) {
        btnSaveKey.addEventListener('click', function () {
            const apiKey = inputApiKey ? inputApiKey.value.trim() : '';
            const model = selectModel ? selectModel.value : 'gemini-3.8-flash';

            if (!apiKey) {
                showKeyFeedback('danger', 'Harap masukkan API Key Gemini.');
                if (inputApiKey) inputApiKey.focus();
                return;
            }

            const origHtml = btnSaveKey.innerHTML;
            btnSaveKey.disabled = true;
            btnSaveKey.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memverifikasi & Menyimpan...';
            if (feedbackEl) feedbackEl.classList.add('d-none');

            fetch('<?php echo BASE_URL; ?>/aichat/saveApiKey', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    api_key: apiKey,
                    model: model,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                btnSaveKey.disabled = false;
                btnSaveKey.innerHTML = origHtml;
                if (data.success) {
                    showKeyFeedback('success', `<i class="fas fa-check-circle me-1"></i>${escapeHtml(data.message)}`);
                    if (data.model && selectModel) {
                        selectModel.value = data.model;
                    }
                    fetchApiKeyStatus();
                    if (inputApiKey) {
                        inputApiKey.value = '';
                    }
                    setTimeout(() => {
                        if (apiKeyPanel) apiKeyPanel.classList.add('d-none');
                    }, 2500);
                } else {
                    showKeyFeedback('danger', `<i class="fas fa-exclamation-triangle me-1"></i>${escapeHtml(data.message || 'Gagal menyimpan API Key.')}`);
                }
            })
            .catch(err => {
                btnSaveKey.disabled = false;
                btnSaveKey.innerHTML = origHtml;
                showKeyFeedback('danger', `<i class="fas fa-times-circle me-1"></i>Kesalahan koneksi: ${escapeHtml(err.message)}`);
            });
        });
    }

    if (btnTestKey) {
        btnTestKey.addEventListener('click', function () {
            const apiKey = inputApiKey ? inputApiKey.value.trim() : '';
            const model = selectModel ? selectModel.value : 'gemini-3.8-flash';

            const origHtml = btnTestKey.innerHTML;
            btnTestKey.disabled = true;
            btnTestKey.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            if (feedbackEl) feedbackEl.classList.add('d-none');

            fetch('<?php echo BASE_URL; ?>/aichat/testApiKey', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    api_key: apiKey,
                    model: model,
                    csrf_token: csrfToken
                })
            })
            .then(res => res.json())
            .then(data => {
                btnTestKey.disabled = false;
                btnTestKey.innerHTML = origHtml;
                if (data.success) {
                    showKeyFeedback('success', `<i class="fas fa-check-circle me-1"></i>Koneksi Berhasil: ${escapeHtml(data.message || 'Gemini API merespons dengan baik.')}`);
                } else {
                    showKeyFeedback('danger', `<i class="fas fa-exclamation-triangle me-1"></i>${escapeHtml(data.message || 'Gagal terhubung ke Gemini API.')}`);
                }
            })
            .catch(err => {
                btnTestKey.disabled = false;
                btnTestKey.innerHTML = origHtml;
                showKeyFeedback('danger', `<i class="fas fa-times-circle me-1"></i>Kesalahan uji koneksi: ${escapeHtml(err.message)}`);
            });
        });
    }

    function showKeyFeedback(type, messageHtml) {
        if (!feedbackEl) return;
        feedbackEl.className = `small mt-2 alert alert-${type} py-1 px-2`;
        feedbackEl.innerHTML = messageHtml;
        feedbackEl.classList.remove('d-none');
    }

    // Toggle Chat Window
    launcher.addEventListener('click', function () {
        windowEl.classList.remove('d-none');
        windowEl.classList.remove('minimized');
        launcher.classList.add('d-none');
        scrollToBottom();
        input.focus();
    });

    btnClose.addEventListener('click', function () {
        windowEl.classList.add('d-none');
        launcher.classList.remove('d-none');
    });

    btnMin.addEventListener('click', function () {
        windowEl.classList.toggle('minimized');
    });

    btnClear.addEventListener('click', function () {
        if (confirm('Hapus seluruh riwayat percakapan asisten AI ini?')) {
            // Keep first welcome message
            const firstMsg = messagesEl.querySelector('.ai-message-incoming');
            messagesEl.innerHTML = '';
            if (firstMsg) messagesEl.appendChild(firstMsg);
        }
    });

    // Auto-expand textarea & char count
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
        charCount.textContent = this.value.length + '/500';
    });

    // Send on Enter (Shift+Enter for newline)
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        sendMessage();
    });

    // Handle Quick Action Chips
    document.addEventListener('click', function (e) {
        // Quick Query Buttons
        const chip = e.target.closest('.ai-chip-btn, .ai-quick-btn');
        if (chip) {
            e.preventDefault();
            const query = chip.getAttribute('data-query');
            if (query) {
                input.value = query;
                sendMessage();
            }
            return;
        }

        // Fill Transfer Button (pre-populates transfer query)
        const fillBtn = e.target.closest('.ai-fill-transfer-btn');
        if (fillBtn) {
            e.preventDefault();
            const bName = fillBtn.getAttribute('data-barang-name');
            const bCode = fillBtn.getAttribute('data-barang-code');
            const lKode = fillBtn.getAttribute('data-lokasi-kode');
            const maxQ  = parseInt(fillBtn.getAttribute('data-available-qty') || '1');
            const unit  = fillBtn.getAttribute('data-unit') || 'unit';

            // Ask user for destination and quantity conveniently
            const promptText = `Pindahkan 1 ${unit} ${bName} dari ${lKode} ke rak tujuan (contoh: A.1.02)`;
            input.value = `Pindahkan 1 ${unit} ${bName} dari ${lKode} ke `;
            input.focus();
            scrollToBottom();
            return;
        }

        // Cancel Action / Transfer Button
        const cancelBtn = e.target.closest('.ai-cancel-transfer-btn');
        if (cancelBtn) {
            e.preventDefault();
            const card = cancelBtn.closest('.ai-transfer-card, .ai-action-card');
            if (card) {
                card.innerHTML = '<div class="alert alert-secondary py-2 px-3 small mb-0"><i class="fas fa-times-circle me-1"></i> Aksi dibatalkan oleh pengguna.</div>';
            }
            return;
        }

        // Execute Action / Transfer Button
        const execBtn = e.target.closest('.ai-execute-action-btn, .ai-execute-transfer-btn');
        if (execBtn) {
            e.preventDefault();
            executeWarehouseAction(execBtn);
            return;
        }
    });

    // Send Message Function
    function sendMessage() {
        const text = input.value.trim();
        if (!text) return;

        // Render outgoing user message
        appendMessage('user', escapeHtml(text));
        input.value = '';
        input.style.height = 'auto';
        charCount.textContent = '0/500';
        scrollToBottom();

        // Show typing indicator
        showTyping(true);

        // Send AJAX Request
        fetch('<?php echo BASE_URL; ?>/aichat/message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                message: text,
                csrf_token: csrfToken
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => { throw err; });
            }
            return response.json();
        })
        .then(data => {
            showTyping(false);
            if (data.message) {
                appendMessage('ai', data.message);
            } else {
                appendMessage('ai', 'Maaf, tidak ada respon dari sistem.');
            }
            scrollToBottom();
        })
        .catch(err => {
            showTyping(false);
            console.error('AI Chat Error:', err);
            const errMsg = (err && err.message) ? err.message : 'Gagal menghubungi server. Pastikan koneksi stabil.';
            appendMessage('ai', `<div class="alert alert-danger py-2 px-3 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> ${escapeHtml(errMsg)}</div>`);
            scrollToBottom();
        });
    }

    // Execute Any Warehouse Action (Inbound, Outbound, Transfer, Opname, Master Data) via AJAX
    function executeWarehouseAction(btn) {
        const raw = btn.getAttribute('data-action') || btn.getAttribute('data-transfer');
        if (!raw) return;

        let payload;
        try {
            payload = JSON.parse(raw);
        } catch (e) {
            console.error('Invalid action payload', e);
            return;
        }

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses Aksi Gudang...';

        fetch('<?php echo BASE_URL; ?>/aichat/executeAction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                action: payload,
                transfer: payload,
                csrf_token: csrfToken
            })
        })
        .then(res => res.json())
        .then(data => {
            const card = btn.closest('.ai-action-card, .ai-transfer-card');
            if (data.success) {
                if (card) {
                    card.outerHTML = data.message;
                } else {
                    appendMessage('ai', data.message);
                }
            } else {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
                alert('Gagal: ' + (data.message || 'Terjadi kesalahan saat memproses aksi'));
            }
            scrollToBottom();
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
            alert('Kesalahan jaringan: ' + err.message);
        });
    }

    // Helper: Append Message Bubble
    function appendMessage(sender, htmlContent) {
        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        const isUser = (sender === 'user');

        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ${isUser ? 'ai-message-outgoing' : 'ai-message-incoming'} mb-3`;

        if (isUser) {
            msgDiv.innerHTML = `
                <div class="ai-msg-bubble">
                    <div>${htmlContent}</div>
                    <div class="ai-msg-time">${timeStr}</div>
                </div>
            `;
        } else {
            msgDiv.innerHTML = `
                <div class="ai-msg-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="ai-msg-bubble">
                    <div>${htmlContent}</div>
                    <div class="ai-msg-time">${timeStr}</div>
                </div>
            `;
        }

        messagesEl.appendChild(msgDiv);
    }

    function showTyping(show) {
        if (show) {
            typingEl.classList.remove('d-none');
        } else {
            typingEl.classList.add('d-none');
        }
    }

    function scrollToBottom() {
        setTimeout(() => {
            messagesEl.scrollTop = messagesEl.scrollHeight;
        }, 50);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
    }
});
</script>
