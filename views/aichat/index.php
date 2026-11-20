<div class="page-header-responsive mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h2 class="fw-bold text-dark mb-0">Asisten AI SmartWarehouse</h2>
            <span class="badge bg-primary bg-gradient px-3 py-1 rounded-pill fs-6 shadow-sm">
                <i class="fas fa-sparkles me-1"></i>PRO AI
            </span>
        </div>
        <p class="text-secondary mb-0">Asisten cerdas berbasis Google Gemini & Database Real-time untuk cek stok, eksekusi mutasi, dan analisis gudang.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="<?php echo BASE_URL; ?>/laporan/ai_summary" class="btn btn-outline-primary btn-sm rounded-3">
            <i class="fas fa-file-invoice me-1"></i>Laporan AI Harian
        </a>
        <a href="<?php echo BASE_URL; ?>/whatsapp/index" class="btn btn-outline-success btn-sm rounded-3">
            <i class="fab fa-whatsapp me-1"></i>Notifikasi WhatsApp
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT: AI CHAT MAIN INTERFACE (8 COLUMNS) -->
    <div class="col-12 col-xl-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden d-flex flex-column" style="min-height: 680px;">
            <!-- Chat Card Header -->
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative">
                        <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; font-size: 1.2rem;">
                            <i class="fas fa-robot"></i>
                        </div>
                        <span class="position-absolute bottom-0 end-0 bg-success border border-white rounded-circle p-1" style="width: 12px; height: 12px;"></span>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                            Gemini Warehouse Intelligence
                            <span class="badge bg-light text-primary border border-primary-subtle" style="font-size: 0.7rem;" id="ai-active-model-badge">
                                <?php echo htmlspecialchars($gemini['model']); ?>
                            </span>
                        </h6>
                        <small class="text-muted" style="font-size: 0.78rem;">
                            <i class="fas fa-circle text-success me-1" style="font-size: 0.5rem;"></i>
                            Terhubung langsung ke database stok, mutasi, dan lokasi rak
                        </small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" id="page-ai-clear-btn" title="Hapus Percakapan">
                        <i class="fas fa-trash-alt me-1 text-secondary"></i><span class="d-none d-sm-inline">Bersihkan</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border rounded-pill px-3" onclick="document.getElementById('ai-settings-card').scrollIntoView({behavior:'smooth'});" title="Pengaturan AI">
                        <i class="fas fa-cog text-secondary"></i>
                    </button>
                </div>
            </div>

            <!-- Chat Messages Flow Container -->
            <div class="card-body p-3 p-md-4 flex-grow-1 overflow-auto bg-light bg-opacity-25" id="page-ai-chat-thread" style="height: 500px; max-height: 540px;">
                <!-- Welcome Greeting Box -->
                <div id="page-ai-welcome-box" class="text-center py-4 px-2">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary p-3 shadow-sm">
                            <i class="fas fa-robot fa-3x"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Halo, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>!</h5>
                    <p class="text-secondary small mb-4 mx-auto" style="max-width: 480px;">
                        Saya asisten gudang berbasis AI. Anda bisa bertanya tentang stok barang, posisi rak, mutasi harian, hingga meminta saya mengeksekusi transfer barang atau mengirim laporan via WhatsApp.
                    </p>

                    <!-- Prompt Suggestion Cards -->
                    <div class="row g-2 justify-content-center text-start mb-2">
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-white border rounded-3 shadow-xs h-100 cursor-pointer prompt-chip-card hover-lift" data-prompt="Cek barang yang stoknya di bawah minimum atau hampir habis">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    <span class="fw-bold small text-dark">Stok Kritis / Habis</span>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">"Cek barang yang stoknya di bawah minimum atau hampir habis"</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-white border rounded-3 shadow-xs h-100 cursor-pointer prompt-chip-card hover-lift" data-prompt="Cari rak atau lokasi gudang yang masih kosong">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-map-marker-alt text-info"></i>
                                    <span class="fw-bold small text-dark">Lokasi Rak Kosong</span>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">"Cari rak atau lokasi gudang yang masih kosong"</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-white border rounded-3 shadow-xs h-100 cursor-pointer prompt-chip-card hover-lift" data-prompt="Tampilkan ringkasan mutasi dan transaksi barang hari ini">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fas fa-chart-line text-success"></i>
                                    <span class="fw-bold small text-dark">Mutasi Hari Ini</span>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">"Tampilkan ringkasan mutasi dan transaksi barang hari ini"</small>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="p-3 bg-white border rounded-3 shadow-xs h-100 cursor-pointer prompt-chip-card hover-lift" data-prompt="Kirim laporan stok minimum via WhatsApp ke manajemen">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <i class="fab fa-whatsapp text-success"></i>
                                    <span class="fw-bold small text-dark">WhatsApp Alert</span>
                                </div>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">"Kirim laporan stok minimum via WhatsApp ke manajemen"</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Will Render Here Dynamically -->
                <div id="page-ai-messages-list" class="d-flex flex-column gap-3"></div>
            </div>

            <!-- Chat Footer & Input Form -->
            <div class="card-footer bg-white border-top p-3 p-md-4">
                <!-- Action / Suggestion Chips Bar -->
                <div class="d-flex gap-2 overflow-auto pb-2 mb-2 no-scrollbar" style="white-space: nowrap;">
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 font-monospace page-chip-btn" data-text="Cek stok barang">
                        📦 Cek Stok
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 font-monospace page-chip-btn" data-text="Cari rak kosong">
                        📍 Rak Kosong
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 font-monospace page-chip-btn" data-text="Barang paling sering keluar minggu ini">
                        📈 Fast Moving
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 font-monospace page-chip-btn" data-text="Pindahkan 10 unit barang dari rak A ke rak B">
                        🔄 Pindah Barang
                    </button>
                    <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill px-3 py-1 font-monospace page-chip-btn" data-text="Kirim status WhatsApp">
                        📱 Cek WhatsApp
                    </button>
                </div>

                <form id="page-ai-chat-form" class="position-relative">
                    <div class="input-group">
                        <textarea class="form-control rounded-start-4 border-end-0 py-2 px-3 shadow-none" 
                                  id="page-ai-input" 
                                  rows="2" 
                                  placeholder="Ketik pertanyaan atau perintah untuk gudang (contoh: 'Cek stok BRG-001' atau 'Pindahkan 5 unit'). Tekan Enter untuk mengirim..." 
                                  style="resize: none; font-size: 0.95rem;"></textarea>
                        <button class="btn btn-primary bg-gradient rounded-end-4 px-4 d-flex align-items-center justify-content-center shadow-sm" type="submit" id="page-ai-send-btn">
                            <i class="fas fa-paper-plane fa-lg me-1"></i>
                            <span class="d-none d-sm-inline fw-semibold">Kirim</span>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2 px-1">
                        <small class="text-muted" style="font-size: 0.72rem;">
                            <i class="fas fa-keyboard me-1"></i>Tekan <strong>Enter</strong> untuk mengirim, <strong>Shift + Enter</strong> untuk baris baru
                        </small>
                        <small class="text-muted" style="font-size: 0.72rem;">
                            Model: <span class="fw-semibold text-primary" id="page-ai-footer-model"><?php echo htmlspecialchars($gemini['model']); ?></span>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- RIGHT: WAREHOUSE INTELLIGENCE & CONTROL CENTER (4 COLUMNS) -->
    <div class="col-12 col-xl-4 d-flex flex-column gap-4">
        
        <!-- Live Warehouse Telemetry -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fas fa-database text-primary me-2"></i>Telemetri Gudang Real-time
                </h6>
            </div>
            <div class="card-body p-3">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="fs-4 fw-bold text-dark"><?php echo number_format($stats['total_barang']); ?></div>
                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;">Macam Barang</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="fs-4 fw-bold text-info"><?php echo number_format($stats['total_stok']); ?></div>
                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;">Total Stok Fisik</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="fs-4 fw-bold text-warning"><?php echo number_format($stats['stok_minimum']); ?></div>
                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;">Stok Minimum</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-light rounded-3 text-center border">
                            <div class="fs-4 fw-bold text-danger"><?php echo number_format($stats['barang_habis']); ?></div>
                            <small class="text-muted fw-semibold" style="font-size: 0.75rem;">Barang Habis</small>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top text-center">
                    <small class="text-muted">Total Titik Lokasi Rak: <strong><?php echo number_format($stats['total_lokasi']); ?></strong></small>
                </div>
            </div>
        </div>

        <!-- Gemini AI Configuration Card -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="ai-settings-card">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fas fa-microchip text-primary me-2"></i>Pengaturan Gemini AI
                </h6>
                <span class="badge <?php echo $gemini['configured'] ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle'; ?> rounded-pill px-2" id="gemini-status-pill">
                    <?php echo $gemini['configured'] ? 'Aktif' : 'Default / Offline'; ?>
                </span>
            </div>
            <div class="card-body p-4">
                <form id="page-gemini-config-form">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Google Gemini API Key</label>
                        <div class="input-group">
                            <input type="password" class="form-control font-monospace" id="page-api-key-input" 
                                   placeholder="<?php echo $gemini['configured'] ? htmlspecialchars($gemini['masked_key']) : 'AIzaSy...'; ?>"
                                   value="">
                            <button class="btn btn-outline-secondary" type="button" id="page-toggle-key-eye">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text" style="font-size: 0.75rem;">
                            Dapatkan gratis di <a href="https://aistudio.google.com/app/apikey" target="_blank" class="fw-semibold text-primary"><i class="fas fa-external-link-alt me-1"></i>Google AI Studio</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-dark">Pilih Model Generatif</label>
                        <select class="form-select form-select-sm" id="page-model-select">
                            <option value="gemini-3.8-flash" <?php echo ($gemini['model'] === 'gemini-3.8-flash') ? 'selected' : ''; ?>>Gemini 3.8 Flash (Generasi Terbaru - Cepat & Cerdas)</option>
                            <option value="gemini-2.0-flash" <?php echo ($gemini['model'] === 'gemini-2.0-flash') ? 'selected' : ''; ?>>Gemini 2.0 Flash (Stabil)</option>
                            <option value="gemini-1.5-flash" <?php echo ($gemini['model'] === 'gemini-1.5-flash') ? 'selected' : ''; ?>>Gemini 1.5 Flash</option>
                            <option value="gemini-1.5-pro"   <?php echo ($gemini['model'] === 'gemini-1.5-pro')   ? 'selected' : ''; ?>>Gemini 1.5 Pro</option>
                        </select>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm flex-grow-1" id="page-btn-save-key">
                            <i class="fas fa-save me-1"></i>Simpan Konfigurasi
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="page-btn-test-key">
                            <i class="fas fa-vial me-1"></i>Uji
                        </button>
                    </div>

                    <div id="page-api-key-feedback" class="small mt-3 d-none"></div>
                </form>
            </div>
        </div>

        <!-- WhatsApp Gateway Integration Status -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark d-flex align-items-center">
                    <i class="fab fa-whatsapp text-success me-2"></i>Gateway WhatsApp
                </h6>
                <span class="badge <?php echo (!empty($waConfig['enabled'])) ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary'; ?> rounded-pill px-2">
                    <?php echo (!empty($waConfig['enabled'])) ? 'Aktif (' . htmlspecialchars($waConfig['provider'] ?? 'Simulasi') . ')' : 'Non-aktif'; ?>
                </span>
            </div>
            <div class="card-body p-3">
                <p class="text-secondary small mb-3">
                    Asisten AI terhubung ke Gateway WhatsApp untuk mengirimkan alert stok minimum, bukti mutasi, dan laporan harian secara otomatis.
                </p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">Target Penerima: <strong><?php echo !empty($waConfig['recipients']) ? htmlspecialchars(explode(',', $waConfig['recipients'])[0]) : 'Belum diisi'; ?></strong></small>
                    <a href="<?php echo BASE_URL; ?>/whatsapp/index" class="btn btn-sm btn-outline-success rounded-pill px-3">
                        <i class="fas fa-cog me-1"></i>Pengaturan WA
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- STYLES -->
<style>
.cursor-pointer { cursor: pointer; }
.hover-lift { transition: transform 0.15s ease, box-shadow 0.15s ease; }
.hover-lift:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.08) !important; }
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.chat-bubble-user {
    background: linear-gradient(135deg, #0d6efd, #0b5ed7);
    color: #ffffff;
    border-radius: 18px 18px 4px 18px;
    padding: 12px 16px;
    max-width: 82%;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2);
    word-break: break-word;
}
.chat-bubble-bot {
    background: #ffffff;
    color: #212529;
    border: 1px solid #e2e8f0;
    border-radius: 18px 18px 18px 4px;
    padding: 14px 18px;
    max-width: 88%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    word-break: break-word;
}
.chat-bubble-bot table {
    font-size: 0.82rem;
    margin-top: 8px;
    margin-bottom: 8px;
}
.chat-bubble-bot table th {
    background: #f8fafc;
}
.chat-action-card {
    background: #f8fafc;
    border: 1px solid #cbd5e1;
    border-left: 4px solid #0d6efd;
    border-radius: 10px;
    padding: 12px 16px;
    margin-top: 10px;
}
</style>

<!-- SCRIPT LOGIC FOR FULL PAGE AI ASSISTANT -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatThread    = document.getElementById('page-ai-chat-thread');
    const messagesList  = document.getElementById('page-ai-messages-list');
    const welcomeBox    = document.getElementById('page-ai-welcome-box');
    const chatForm      = document.getElementById('page-ai-chat-form');
    const chatInput     = document.getElementById('page-ai-input');
    const sendBtn       = document.getElementById('page-ai-send-btn');
    const clearBtn      = document.getElementById('page-ai-clear-btn');
    const promptChips   = document.querySelectorAll('.prompt-chip-card, .page-chip-btn');

    // Auto-scroll on anchor
    if (window.location.hash === '#settings') {
        const settingsCard = document.getElementById('ai-settings-card');
        if (settingsCard) {
            settingsCard.scrollIntoView({ behavior: 'smooth' });
            settingsCard.classList.add('border-primary');
            setTimeout(() => settingsCard.classList.remove('border-primary'), 2000);
        }
    }

    // Click handler for suggestion chips
    promptChips.forEach(chip => {
        chip.addEventListener('click', function() {
            const text = this.getAttribute('data-prompt') || this.getAttribute('data-text');
            if (text) {
                chatInput.value = text;
                chatInput.focus();
                sendMessage(text);
            }
        });
    });

    // Enter key submits (Shift+Enter for newline)
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    });

    // Form submit
    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const text = chatInput.value.trim();
        if (!text) return;
        sendMessage(text);
    });

    // Clear chat
    clearBtn.addEventListener('click', function() {
        if (confirm('Hapus seluruh riwayat percakapan pada sesi ini?')) {
            messagesList.innerHTML = '';
            welcomeBox.classList.remove('d-none');
            chatInput.value = '';
            chatInput.focus();
        }
    });

    // Main Send Message Function
    function sendMessage(text) {
        welcomeBox.classList.add('d-none');
        chatInput.value = '';
        chatInput.style.height = 'auto';

        // Render User Message
        appendMessage('user', text);

        // Render Loading Indicator
        const loadingId = 'page-ai-loading-' + Date.now();
        appendLoading(loadingId);
        scrollToBottom();

        // Send POST to /aichat/message
        fetch('<?php echo BASE_URL; ?>/aichat/message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text })
        })
        .then(res => res.json())
        .then(data => {
            removeLoading(loadingId);
            if (data.success) {
                appendMessage('bot', data.reply || 'Permintaan diproses.', data);
            } else {
                appendMessage('bot', '⚠️ ' + (data.message || 'Terjadi kesalahan sistem.'));
            }
            scrollToBottom();
        })
        .catch(err => {
            removeLoading(loadingId);
            appendMessage('bot', '⚠️ Terjadi kesalahan jaringan: ' + err.message);
            scrollToBottom();
        });
    }

    function appendMessage(sender, text, extra = null) {
        const row = document.createElement('div');
        row.className = sender === 'user' ? 'd-flex justify-content-end' : 'd-flex justify-content-start align-items-start gap-2';

        if (sender === 'user') {
            row.innerHTML = `
                <div class="chat-bubble-user">
                    <div>${escapeHtml(text)}</div>
                    <div class="text-end text-white-50 mt-1" style="font-size:0.68rem;">${getCurrentTime()}</div>
                </div>
            `;
        } else {
            let formattedReply = formatMarkdown(text);
            let actionHtml = '';

            // If AI returned an actionable confirmation (Transfer / Mutasi / WA)
            if (extra && extra.action && extra.action.confirmed === false) {
                const act = extra.action;
                const actId = 'act-' + Date.now();
                window['currentAction_' + actId] = act;

                actionHtml = `
                    <div class="chat-action-card">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-primary">${(act.type || 'AKSI').toUpperCase()}</span>
                            <small class="text-muted font-monospace">${act.action_code || ''}</small>
                        </div>
                        <div class="small mb-3 text-secondary">
                            <strong>Konfirmasi Aksi:</strong> ${escapeHtml(act.summary || act.description || 'Apakah Anda ingin mengeksekusi aksi ini?')}
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3 btn-confirm-action" data-actid="${actId}">
                                <i class="fas fa-check me-1"></i>Eksekusi Sekarang
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 btn-cancel-action">
                                Batalkan
                            </button>
                        </div>
                    </div>
                `;
            }

            row.innerHTML = `
                <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width:34px; height:34px; font-size:0.9rem;">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="chat-bubble-bot">
                    <div class="chat-markdown-content">${formattedReply}</div>
                    ${actionHtml}
                    <div class="d-flex align-items-center justify-content-between mt-2 pt-1 border-top border-light">
                        <small class="text-muted" style="font-size:0.68rem;">SmartWarehouse AI • ${getCurrentTime()}</small>
                        <button class="btn btn-xs btn-link text-muted p-0 text-decoration-none copy-btn" title="Salin Jawaban" onclick="navigator.clipboard.writeText(\`${escapeJs(text)}\`); this.innerHTML='<i class=\\'fas fa-check text-success\\'></i>';">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        messagesList.appendChild(row);

        // Bind any newly generated action confirm buttons
        row.querySelectorAll('.btn-confirm-action').forEach(btn => {
            btn.addEventListener('click', function() {
                const actId = this.getAttribute('data-actid');
                const actionData = window['currentAction_' + actId];
                if (!actionData) return;

                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mengeksekusi...';

                fetch('<?php echo BASE_URL; ?>/aichat/executeAction', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: actionData })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        appendMessage('bot', '✅ **Berhasil dieksekusi!** ' + (res.message || 'Data mutasi telah tersimpan ke database.'));
                    } else {
                        appendMessage('bot', '❌ **Gagal:** ' + (res.message || 'Terjadi kesalahan saat menyimpan aksi.'));
                    }
                    scrollToBottom();
                })
                .catch(err => {
                    appendMessage('bot', '⚠️ Terjadi kesalahan saat eksekusi: ' + err.message);
                    scrollToBottom();
                });
            });
        });

        row.querySelectorAll('.btn-cancel-action').forEach(btn => {
            btn.addEventListener('click', function() {
                const card = this.closest('.chat-action-card');
                if (card) {
                    card.innerHTML = '<small class="text-muted"><i class="fas fa-times me-1"></i>Aksi dibatalkan oleh pengguna.</small>';
                }
            });
        });
    }

    function appendLoading(id) {
        const div = document.createElement('div');
        div.id = id;
        div.className = 'd-flex justify-content-start align-items-start gap-2';
        div.innerHTML = `
            <div class="rounded-circle bg-primary bg-gradient text-white d-flex align-items-center justify-content-center shadow-xs flex-shrink-0" style="width:34px; height:34px; font-size:0.9rem;">
                <i class="fas fa-robot"></i>
            </div>
            <div class="chat-bubble-bot d-flex align-items-center gap-2 py-3">
                <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                <span class="small text-muted">Sedang menganalisis gudang & menyusun jawaban...</span>
            </div>
        `;
        messagesList.appendChild(div);
    }

    function removeLoading(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        chatThread.scrollTo({ top: chatThread.scrollHeight, behavior: 'smooth' });
    }

    function getCurrentTime() {
        const d = new Date();
        return d.getHours().toString().padStart(2, '0') + ':' + d.getMinutes().toString().padStart(2, '0');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function escapeJs(str) {
        if (!str) return '';
        return String(str).replace(/\\/g, '\\\\').replace(/`/g, '\\`').replace(/\$/g, '\\$');
    }

    // Markdown Parser
    function formatMarkdown(text) {
        if (!text) return '';
        let html = escapeHtml(text);

        // Code blocks
        html = html.replace(/```([\s\S]*?)```/g, '<pre class="bg-dark text-light p-2 rounded font-monospace small"><code>$1</code></pre>');
        // Inline code
        html = html.replace(/`([^`]+)`/g, '<code class="bg-light text-dark px-1 py-0.5 rounded border font-monospace small">$1</code>');
        // Bold
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        // Italic
        html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
        // Markdown Tables
        if (html.includes('|')) {
            html = parseMarkdownTable(html);
        }
        // Bullet lists
        html = html.replace(/^\s*[-•]\s+(.+)$/gm, '<li class="ms-3">$1</li>');
        html = html.replace(/(<li class="ms-3">.*<\/li>(\n|$) *)+/g, '<ul class="mb-2 ps-1">$0</ul>');
        // Line breaks
        html = html.replace(/\n/g, '<br>');

        return html;
    }

    function parseMarkdownTable(text) {
        const lines = text.split('\n');
        let tableLines = [];
        let inTable = false;
        let result = [];

        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            if (line.startsWith('|') && line.endsWith('|')) {
                inTable = true;
                tableLines.push(line);
            } else {
                if (inTable) {
                    result.push(renderTableHtml(tableLines));
                    tableLines = [];
                    inTable = false;
                }
                result.push(line);
            }
        }
        if (inTable && tableLines.length > 0) {
            result.push(renderTableHtml(tableLines));
        }
        return result.join('\n');
    }

    function renderTableHtml(lines) {
        if (lines.length < 2) return lines.join('<br>');
        let html = '<div class="table-responsive my-2"><table class="table table-sm table-bordered align-middle mb-0"><thead>';
        const headers = lines[0].split('|').slice(1, -1);
        html += '<tr>' + headers.map(h => `<th class="px-2 py-1">${h.trim()}</th>`).join('') + '</tr></thead><tbody>';

        const rows = lines.slice(2);
        for (let r of rows) {
            const cols = r.split('|').slice(1, -1);
            html += '<tr>' + cols.map(c => `<td class="px-2 py-1">${c.trim()}</td>`).join('') + '</tr>';
        }
        html += '</tbody></table></div>';
        return html;
    }

    // ==========================================
    // GEMINI CONFIGURATION LOGIC (Right Column)
    // ==========================================
    const configForm    = document.getElementById('page-gemini-config-form');
    const apiKeyInput   = document.getElementById('page-api-key-input');
    const modelSelect   = document.getElementById('page-model-select');
    const toggleEyeBtn  = document.getElementById('page-toggle-key-eye');
    const saveKeyBtn    = document.getElementById('page-btn-save-key');
    const testKeyBtn    = document.getElementById('page-btn-test-key');
    const feedbackBox   = document.getElementById('page-api-key-feedback');

    if (toggleEyeBtn && apiKeyInput) {
        toggleEyeBtn.addEventListener('click', function() {
            const isPass = apiKeyInput.type === 'password';
            apiKeyInput.type = isPass ? 'text' : 'password';
            toggleEyeBtn.innerHTML = isPass ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        });
    }

    if (configForm) {
        configForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const key   = apiKeyInput.value.trim();
            const model = modelSelect.value;

            if (!key) {
                showFeedback('danger', 'Ketik atau tempel Google Gemini API Key Anda.');
                return;
            }

            saveKeyBtn.disabled = true;
            saveKeyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memverifikasi...';

            fetch('<?php echo BASE_URL; ?>/aichat/saveApiKey', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ api_key: key, model: model })
            })
            .then(r => r.json())
            .then(res => {
                saveKeyBtn.disabled = false;
                saveKeyBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan Konfigurasi';
                if (res.success) {
                    showFeedback('success', res.message || 'API Key berhasil disimpan & aktif!');
                    document.getElementById('gemini-status-pill').className = 'badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2';
                    document.getElementById('gemini-status-pill').textContent = 'Aktif (' + (res.model || model) + ')';
                    document.getElementById('ai-active-model-badge').textContent = res.model || model;
                    document.getElementById('page-ai-footer-model').textContent = res.model || model;
                } else {
                    showFeedback('danger', res.message || 'Gagal memverifikasi API Key.');
                }
            })
            .catch(err => {
                saveKeyBtn.disabled = false;
                saveKeyBtn.innerHTML = '<i class="fas fa-save me-1"></i>Simpan Konfigurasi';
                showFeedback('danger', 'Kesalahan jaringan: ' + err.message);
            });
        });
    }

    if (testKeyBtn) {
        testKeyBtn.addEventListener('click', function() {
            const key   = apiKeyInput.value.trim();
            const model = modelSelect.value;

            testKeyBtn.disabled = true;
            testKeyBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Menguji...';

            fetch('<?php echo BASE_URL; ?>/aichat/testApiKey', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ api_key: key, model: model })
            })
            .then(r => r.json())
            .then(res => {
                testKeyBtn.disabled = false;
                testKeyBtn.innerHTML = '<i class="fas fa-vial me-1"></i>Uji';
                if (res.success) {
                    showFeedback('success', '✅ ' + (res.message || 'Koneksi ke Gemini API sukses!'));
                } else {
                    showFeedback('danger', '❌ ' + (res.message || 'Koneksi gagal.'));
                }
            })
            .catch(err => {
                testKeyBtn.disabled = false;
                testKeyBtn.innerHTML = '<i class="fas fa-vial me-1"></i>Uji';
                showFeedback('danger', 'Kesalahan: ' + err.message);
            });
        });
    }

    function showFeedback(type, message) {
        feedbackBox.className = `alert alert-${type} py-2 px-3 small mt-3`;
        feedbackBox.innerHTML = message;
        feedbackBox.classList.remove('d-none');
    }
});
</script>
