<?php
// views/whatsapp/index.php
// Variables available: $config, $logs, $deviceStatus
$isDeviceConnected = !empty($deviceStatus['connected']);
$deviceNum = $deviceStatus['device'] ?? '';
$deviceName = $deviceStatus['name'] ?? '';
$deviceQuota = $deviceStatus['quota'] ?? 0;
$devicePkg = $deviceStatus['package'] ?? 'Free';
$deviceExp = $deviceStatus['expired'] ?? '';
?>

<div class="page-header-responsive mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="fab fa-whatsapp text-success me-2"></i>Pengaturan Notifikasi WhatsApp
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item">Pengaturan</li>
                <li class="breadcrumb-item active">WhatsApp Gateway & OTP</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm" id="btnRefreshDeviceStatus" title="Cek Status Perangkat Fonnte">
            <i class="fas fa-sync-alt me-1" id="iconSyncDevice"></i>Cek Status Gateway
        </button>
        <?php if (!empty($config['wa_enabled']) && !empty($config['wa_phone'])): ?>
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill fs-6" id="badgeGatewayGlobal">
                <i class="fas fa-check-circle me-1"></i>Gateway Aktif
            </span>
        <?php else: ?>
            <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-2 rounded-pill fs-6" id="badgeGatewayGlobal">
                <i class="fas fa-pause-circle me-1"></i>Gateway Nonaktif
            </span>
        <?php endif; ?>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fs-5 me-2"></i>
            <div><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-3 mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fs-5 me-2"></i>
            <div><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- TOP HERO: LIVE DEVICE STATUS & QUICK OTP REGISTRATION -->
<div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 50%, #f8fafc 100%); border-left: 6px solid #10b981 !important;">
    <div class="card-body p-4">
        <div class="row align-items-center g-4">
            <!-- Left: Device Live Status Badge -->
            <div class="col-lg-6 border-end-lg">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="bg-success text-white rounded-4 p-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px;">
                        <i class="fab fa-whatsapp fs-3"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="fw-bold text-dark mb-0">Status Server Gateway</h5>
                            <span class="badge <?php echo $isDeviceConnected ? 'bg-success' : 'bg-danger'; ?> rounded-pill px-2 py-1" id="badgeDeviceStatus">
                                <i class="fas <?php echo $isDeviceConnected ? 'fa-link' : 'fa-unlink'; ?> me-1"></i>
                                <?php echo $isDeviceConnected ? 'Terhubung (Online)' : 'Terputus (Offline)'; ?>
                            </span>
                        </div>
                        <small class="text-muted" id="deviceStatusSubtext">
                            <?php if ($isDeviceConnected): ?>
                                Perangkat WhatsApp aktif dan siap mengirimkan notifikasi real-time.
                            <?php else: ?>
                                Perangkat WhatsApp belum terhubung ke gateway API.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>

                <div class="row g-2 small">
                    <div class="col-sm-6">
                        <div class="p-2 bg-white rounded-3 border">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Nomor WhatsApp Gateway:</span>
                            <strong class="text-dark font-monospace" id="labelDevicePhone"><?php echo htmlspecialchars($deviceNum ?: ($config['wa_phone'] ?? '-')); ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 bg-white rounded-3 border">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Nama Akun WhatsApp:</span>
                            <strong class="text-dark" id="labelDeviceName"><?php echo htmlspecialchars($deviceName ?: 'SmartWarehouse Bot'); ?></strong>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 bg-white rounded-3 border">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Sisa Kuota Pesan API:</span>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle fw-bold" id="labelDeviceQuota">
                                <?php echo htmlspecialchars((string)$deviceQuota); ?> Pesan
                            </span>
                            <small class="text-muted">(Paket <?php echo htmlspecialchars($devicePkg); ?>)</small>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-2 bg-white rounded-3 border">
                            <span class="text-muted d-block" style="font-size: 0.72rem;">Masa Aktif Paket:</span>
                            <strong class="text-dark" id="labelDeviceExp"><?php echo htmlspecialchars($deviceExp ?: 'Selamanya'); ?></strong>
                        </div>
                    </div>
                </div>

                <div class="mt-3 d-flex align-items-center gap-2">
                    <button class="btn btn-xs btn-outline-success rounded-pill px-3 py-1" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePairingTool" aria-expanded="false">
                        <i class="fas fa-qrcode me-1"></i>Tautkan Perangkat Baru (Pairing / QR)
                    </button>
                    <a href="https://fonnte.com" target="_blank" class="btn btn-xs btn-link text-muted text-decoration-none">
                        <i class="fas fa-external-link-alt me-1"></i>Dashboard Fonnte
                    </a>
                </div>
            </div>

            <!-- Right: DAFTARKAN NOMOR DENGAN OTP LANGSUNG -->
            <div class="col-lg-6">
                <div class="bg-white p-3 p-md-4 rounded-4 shadow-sm border border-success-subtle">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-success p-1 rounded-circle"><i class="fas fa-shield-alt text-white" style="font-size: 0.7rem;"></i></span>
                            <h6 class="fw-bold mb-0 text-dark">Daftarkan Nomor via Kode OTP</h6>
                        </div>
                        <span class="badge bg-warning bg-opacity-10 text-dark border border-warning-subtle rounded-pill small">
                            <i class="fas fa-bolt text-warning me-1"></i>Otomatis & Real-Time
                        </span>
                    </div>
                    <p class="text-muted small mb-3" style="line-height: 1.4;">
                        Cukup daftarkan nomor WhatsApp Anda. Sistem akan langsung mengirimkan <strong>6-digit kode OTP</strong> untuk verifikasi dan terhubung secara otomatis ke notifikasi transaksi gudang.
                    </p>

                    <!-- STEP 1: Form Input Nomor Telepon -->
                    <div id="step-register-phone">
                        <label class="form-label fw-semibold text-dark small mb-1">Nomor WhatsApp yang Digunakan</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light border-end-0 text-success fw-bold">
                                <i class="fab fa-whatsapp me-1"></i>+62
                            </span>
                            <input type="text" class="form-control border-start-0" id="otp_target_phone"
                                   placeholder="Contoh: 089639590735 atau 89639590735"
                                   value="<?php echo htmlspecialchars($config['wa_phone'] ?? $deviceNum); ?>">
                            <button class="btn btn-success fw-semibold px-3" type="button" id="btnRequestOtp">
                                <i class="fas fa-paper-plane me-1"></i>Kirim Kode OTP
                            </button>
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">
                            <i class="fas fa-info-circle me-1 text-primary"></i>Pesan WhatsApp berisi kode OTP verifikasi akan dikirimkan ke nomor ini.
                        </small>
                    </div>

                    <!-- STEP 2: Input & Verifikasi Kode OTP (Hidden by default, shown upon OTP sent) -->
                    <div id="step-verify-otp" class="d-none mt-3 p-3 rounded-3 border border-success bg-success bg-opacity-10">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-success small"><i class="fas fa-key me-1"></i>Masukkan 6 Digit Kode OTP:</strong>
                            <span class="badge bg-danger rounded-pill font-monospace" id="otpTimerDisplay">05:00</span>
                        </div>
                        <div class="input-group mb-2">
                            <input type="text" class="form-control text-center fw-bold font-monospace fs-5 tracking-wider"
                                   id="otp_code_input"
                                   maxlength="6"
                                   placeholder="• • • • • •"
                                   autocomplete="one-time-code"
                                   style="letter-spacing: 6px;">
                            <button class="btn btn-primary fw-semibold px-3" type="button" id="btnSubmitOtp">
                                <i class="fas fa-check-circle me-1"></i>Verifikasi & Hubungkan
                            </button>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small">
                            <button class="btn btn-link btn-xs text-muted text-decoration-none p-0" type="button" id="btnResendOtp">
                                <i class="fas fa-redo me-1"></i>Kirim Ulang OTP
                            </button>
                            <span id="otpFeedbackStatus" class="text-muted">Menunggu verifikasi...</span>
                        </div>
                    </div>

                    <!-- Feedback Alert Container -->
                    <div id="otp-alert-box" class="d-none mt-2 p-2 rounded-3 small"></div>
                </div>
            </div>
        </div>

        <!-- COLLAPSIBLE: Fonnte Pairing Code (8 Karakter) & QR Code -->
        <div class="collapse mt-4 pt-3 border-top" id="collapsePairingTool">
            <div class="bg-white p-4 rounded-4 shadow-sm border">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-mobile-alt text-primary fs-5"></i>
                        <h6 class="fw-bold mb-0 text-dark">Tautkan Perangkat WhatsApp Baru ke Gateway (Fonnte Pairing / QR)</h6>
                    </div>
                    <span class="badge bg-light text-secondary border">Opsional</span>
                </div>
                <p class="text-muted small mb-3">
                    Jika Anda ingin menghubungkan akun WhatsApp baru sebagai pengirim, Anda dapat menggunakan <strong>Kode Pairing 8-Karakter</strong> (tanpa perlu scan QR) atau menggunakan scan QR Code standar.
                </p>

                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold text-dark">Nomor WhatsApp Perangkat Baru</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-phone"></i></span>
                            <input type="text" class="form-control" id="pairing_phone" placeholder="Contoh: 08123456789" value="<?php echo htmlspecialchars($config['wa_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="col-md-7 d-flex gap-2 align-items-end pt-md-4">
                        <button class="btn btn-outline-primary fw-semibold btn-sm rounded-3" type="button" id="btnGetPairingCode">
                            <i class="fas fa-key me-1"></i>Minta Kode Pairing (8 Digit)
                        </button>
                        <button class="btn btn-outline-secondary fw-semibold btn-sm rounded-3" type="button" id="btnGetQrCode">
                            <i class="fas fa-qrcode me-1"></i>Tampilkan QR Code
                        </button>
                    </div>
                </div>

                <!-- Pairing Code Result Box -->
                <div id="pairingResultBox" class="d-none mt-3 p-3 rounded-3 border bg-light"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- LEFT: Settings Form -->
    <div class="col-lg-7">
        <form action="<?php echo BASE_URL; ?>/whatsapp/save" method="POST" id="formWaConfig">
            <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::csrfToken(); ?>">

            <!-- Main Toggle & Recipient Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                <i class="fab fa-whatsapp fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0 text-dark">Status Gateway & Nomor Tujuan</h5>
                                <small class="text-muted">Kelola aktivasi pengiriman notifikasi otomatis</small>
                            </div>
                        </div>
                        <div class="form-check form-switch fs-5 mb-0">
                            <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="wa_enabled" name="wa_enabled" <?php echo !empty($config['wa_enabled']) ? 'checked' : ''; ?>>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pt-3 pb-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">
                                Nomor WhatsApp Penerima <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-success"><i class="fas fa-phone-alt"></i></span>
                                <input type="text" class="form-control border-start-0" name="wa_phone" id="wa_phone"
                                       placeholder="Contoh: 081234567890 atau 6281234567890"
                                       value="<?php echo htmlspecialchars($config['wa_phone'] ?? ''); ?>">
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-info-circle me-1"></i>Bisa memasukkan beberapa nomor dipisah koma (contoh: <code>08123456789, 08987654321</code>) untuk notifikasi ke Supervisor & Manajer sekaligus.
                            </small>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">
                                Nama Pengirim / Label Gudang
                            </label>
                            <input type="text" class="form-control" name="wa_sender_name"
                                   placeholder="Contoh: SmartWarehouse Sentral Jakarta"
                                   value="<?php echo htmlspecialchars($config['wa_sender_name'] ?? 'SmartWarehouse Management'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Provider Selection & API Credentials -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="fas fa-plug fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Penyedia Gateway (Provider)</h5>
                            <small class="text-muted">Pilih layanan gateway API atau mode simulasi gratis</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pt-3 pb-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold text-dark">Pilih Provider WhatsApp</label>
                            <select class="form-select" name="wa_provider" id="wa_provider">
                                <option value="fonnte" <?php echo ($config['wa_provider'] ?? '') === 'fonnte' ? 'selected' : ''; ?>>
                                    Fonnte (Direkomendasikan - Server Indonesia, Cepat & Handal)
                                </option>
                                <option value="wablas" <?php echo ($config['wa_provider'] ?? '') === 'wablas' ? 'selected' : ''; ?>>
                                    Wablas (API WhatsApp Blast & Gateway)
                                </option>
                                <option value="ultramsg" <?php echo ($config['wa_provider'] ?? '') === 'ultramsg' ? 'selected' : ''; ?>>
                                    UltraMsg (WhatsApp Cloud API)
                                </option>
                                <option value="custom_webhook" <?php echo ($config['wa_provider'] ?? '') === 'custom_webhook' ? 'selected' : ''; ?>>
                                    Custom Webhook / Self-Hosted Gateway (Node.js/Baileys/Wppconnect)
                                </option>
                                <option value="wame_log" <?php echo ($config['wa_provider'] ?? '') === 'wame_log' ? 'selected' : ''; ?>>
                                    Mode Simulasi wa.me (Gratis tanpa token, langsung buka WhatsApp Web / App)
                                </option>
                            </select>
                        </div>

                        <div class="col-12 provider-field" id="field-token">
                            <label class="form-label fw-semibold text-dark">API Token / Secret Key</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-key"></i></span>
                                <input type="password" class="form-control font-monospace" name="wa_api_token" id="wa_api_token"
                                       placeholder="Masukkan API Token dari dashboard provider"
                                       value="<?php echo htmlspecialchars($config['wa_api_token'] ?? ''); ?>">
                                <button class="btn btn-outline-secondary" type="button" id="btnToggleToken"><i class="fas fa-eye"></i></button>
                            </div>
                            <small class="text-muted d-block mt-1">Token didapatkan setelah mendaftar di situs resmi provider (Fonnte/Wablas).</small>
                        </div>

                        <div class="col-12 provider-field" id="field-api-url">
                            <label class="form-label fw-semibold text-dark">Custom API URL (Opsional)</label>
                            <input type="text" class="form-control" name="wa_api_url" id="wa_api_url"
                                   placeholder="Contoh: https://api.fonnte.com/send"
                                   value="<?php echo htmlspecialchars($config['wa_api_url'] ?? ''); ?>">
                            <small class="text-muted">Biarkan kosong untuk menggunakan endpoint resmi bawaan.</small>
                        </div>

                        <div class="col-12 provider-field d-none" id="field-instance">
                            <label class="form-label fw-semibold text-dark">Instance ID (Khusus UltraMsg)</label>
                            <input type="text" class="form-control" name="wa_instance_id"
                                   placeholder="Contoh: instance12345"
                                   value="<?php echo htmlspecialchars($config['wa_instance_id'] ?? ''); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Trigger Events Configuration -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-warning text-dark rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                            <i class="fas fa-bell fs-5"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Pemicu Notifikasi Otomatis</h5>
                            <small class="text-muted">Pilih peristiwa transaksi yang langsung mengirim pesan WhatsApp</small>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4 pt-3 pb-4">
                    <div class="list-group list-group-flush border rounded-3">
                        <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-circle">
                                    <i class="fas fa-arrow-circle-down"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">Barang Masuk (Inbound)</div>
                                    <small class="text-muted">Kirim rincian material masuk, nomor transaksi, rak tujuan, dan supplier</small>
                                </div>
                            </div>
                            <input class="form-check-input fs-5 me-1" type="checkbox" name="wa_notify_inbound" <?php echo !empty($config['wa_notify_inbound']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-circle">
                                    <i class="fas fa-arrow-circle-up"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">Barang Keluar (Outbound)</div>
                                    <small class="text-muted">Kirim rincian pengeluaran barang, rak asal, penerima, dan operator</small>
                                </div>
                            </div>
                            <input class="form-check-input fs-5 me-1" type="checkbox" name="wa_notify_outbound" <?php echo !empty($config['wa_notify_outbound']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-warning bg-opacity-10 text-warning p-2 rounded-circle">
                                    <i class="fas fa-exchange-alt"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">Pemindahan Barang Antar Rak (Transfer)</div>
                                    <small class="text-muted">Kirim mutasi stok dari rak asal ke rak tujuan baru secara real-time</small>
                                </div>
                            </div>
                            <input class="form-check-input fs-5 me-1" type="checkbox" name="wa_notify_transfer" <?php echo !empty($config['wa_notify_transfer']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-info bg-opacity-10 text-info p-2 rounded-circle">
                                    <i class="fas fa-balance-scale"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">Penyesuaian & Audit Stok (Stock Opname)</div>
                                    <small class="text-muted">Kirim koreksi saldo material di rak fisik saat stock opname</small>
                                </div>
                            </div>
                            <input class="form-check-input fs-5 me-1" type="checkbox" name="wa_notify_adjustment" <?php echo !empty($config['wa_notify_adjustment']) ? 'checked' : ''; ?>>
                        </label>

                        <label class="list-group-item d-flex align-items-center justify-content-between p-3 cursor-pointer">
                            <div class="d-flex align-items-center gap-3">
                                <span class="badge bg-danger bg-opacity-10 text-danger p-2 rounded-circle">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark">Peringatan Stok Kritis (Low Stock Alert)</div>
                                    <small class="text-muted">Kirim peringatan darurat saat stok material menyentuh batas minimum</small>
                                </div>
                            </div>
                            <input class="form-check-input fs-5 me-1" type="checkbox" name="wa_notify_low_stock" <?php echo !empty($config['wa_notify_low_stock']) ? 'checked' : ''; ?>>
                        </label>
                    </div>
                </div>
                <div class="card-footer bg-light border-0 py-3 px-4 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary px-4 fw-semibold shadow-sm">
                        <i class="fas fa-save me-2"></i>Simpan Konfigurasi
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- RIGHT: Testing & Real-Time Logs -->
    <div class="col-lg-5">
        <!-- Test Connection Card -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                        <i class="fas fa-paper-plane fs-5"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0 text-dark">Uji Coba Pengiriman</h5>
                        <small class="text-muted">Kirim pesan tes untuk memastikan gateway & nomor aktif</small>
                    </div>
                </div>
            </div>
            <div class="card-body px-4 pt-3 pb-4">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-dark">Nomor WhatsApp Pengujian</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="test_phone"
                               placeholder="Contoh: 081234567890"
                               value="<?php echo htmlspecialchars(explode(',', $config['wa_phone'] ?? '')[0] ?? $deviceNum); ?>">
                        <button class="btn btn-success fw-semibold" type="button" id="btnSendTest">
                            <i class="fas fa-paper-plane me-1"></i>Kirim Tes
                        </button>
                    </div>
                    <small class="text-muted mt-1 d-block">Pesan uji coba resmi akan langsung dikirimkan ke nomor ini.</small>
                </div>

                <div id="test-feedback-box" class="d-none mt-3 p-3 rounded-3 border small"></div>
            </div>
        </div>

        <!-- Real-Time Delivery Logs Card -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-2 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fas fa-list-alt text-primary me-2"></i>Log Notifikasi WhatsApp</h5>
                    <span class="badge bg-light text-dark border ms-1" id="logCountBadge"><?php echo count($logs); ?> Terakhir</span>
                </div>
                <button type="button" class="btn btn-xs btn-outline-secondary" id="btnRefreshLogs" title="Segarkan Log">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Waktu & Tipe</th>
                                <th>Tujuan</th>
                                <th>Status</th>
                                <th class="text-end pe-3">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-inbox fs-4 d-block mb-1 opacity-50"></i>
                                        Belum ada riwayat notifikasi WhatsApp tercatat.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $l): ?>
                                    <?php
                                    $stBadge = '<span class="badge bg-secondary">Simulasi</span>';
                                    if ($l['status'] === 'sent') {
                                        $stBadge = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Terkirim</span>';
                                    } elseif ($l['status'] === 'failed') {
                                        $stBadge = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Gagal</span>';
                                    } elseif ($l['status'] === 'simulated') {
                                        $stBadge = '<span class="badge bg-info text-dark"><i class="fas fa-info-circle me-1"></i>Tercatat</span>';
                                    }
                                    $waLink = WhatsAppHelper::generateWaMeLink($l['recipient'], $l['message']);
                                    ?>
                                    <tr>
                                        <td class="ps-3">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($l['message_type']); ?></div>
                                            <small class="text-muted"><?php echo date('d/m H:i', strtotime($l['created_at'])); ?></small>
                                        </td>
                                        <td>
                                            <code><?php echo htmlspecialchars($l['recipient']); ?></code>
                                            <?php if (!empty($l['transaction_number'])): ?>
                                                <br><small class="text-muted font-monospace"><?php echo htmlspecialchars($l['transaction_number']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $stBadge; ?></td>
                                        <td class="text-end pe-3">
                                            <a href="<?php echo $waLink; ?>" target="_blank" class="btn btn-xs btn-outline-success" title="Buka Pesan di WhatsApp Web">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const csrfToken = '<?php echo SecurityHelper::csrfToken(); ?>';
    const baseUrl = '<?php echo BASE_URL; ?>';

    // ==========================================
    // 1. REFRESH LIVE DEVICE STATUS
    // ==========================================
    const btnRefreshDevice = document.getElementById('btnRefreshDeviceStatus');
    const iconSync = document.getElementById('iconSyncDevice');
    const badgeDevice = document.getElementById('badgeDeviceStatus');
    const subtextDevice = document.getElementById('deviceStatusSubtext');
    const labelPhone = document.getElementById('labelDevicePhone');
    const labelName = document.getElementById('labelDeviceName');
    const labelQuota = document.getElementById('labelDeviceQuota');

    function checkLiveDeviceStatus() {
        if (iconSync) iconSync.classList.add('fa-spin');

        fetch(baseUrl + '/whatsapp/deviceStatus')
            .then(res => res.json())
            .then(data => {
                if (iconSync) iconSync.classList.remove('fa-spin');
                if (data.success && data.connected) {
                    if (badgeDevice) {
                        badgeDevice.className = 'badge bg-success rounded-pill px-2 py-1';
                        badgeDevice.innerHTML = '<i class="fas fa-link me-1"></i>Terhubung (Online)';
                    }
                    if (subtextDevice) subtextDevice.textContent = 'Perangkat WhatsApp aktif dan siap mengirimkan notifikasi real-time.';
                    if (labelPhone && data.device) labelPhone.textContent = data.device;
                    if (labelName && data.name) labelName.textContent = data.name;
                    if (labelQuota && typeof data.quota !== 'undefined') labelQuota.textContent = data.quota + ' Pesan';
                } else {
                    if (badgeDevice) {
                        badgeDevice.className = 'badge bg-danger rounded-pill px-2 py-1';
                        badgeDevice.innerHTML = '<i class="fas fa-unlink me-1"></i>Terputus (Offline)';
                    }
                    if (subtextDevice) subtextDevice.textContent = data.message || 'Perangkat WhatsApp tidak terhubung ke gateway.';
                }
            })
            .catch(() => {
                if (iconSync) iconSync.classList.remove('fa-spin');
            });
    }

    if (btnRefreshDevice) {
        btnRefreshDevice.addEventListener('click', checkLiveDeviceStatus);
    }

    // ==========================================
    // 2. DAFTARKAN NOMOR & KIRIM OTP WORKFLOW
    // ==========================================
    const btnRequestOtp = document.getElementById('btnRequestOtp');
    const inputTargetPhone = document.getElementById('otp_target_phone');
    const stepVerifyOtp = document.getElementById('step-verify-otp');
    const inputOtpCode = document.getElementById('otp_code_input');
    const btnSubmitOtp = document.getElementById('btnSubmitOtp');
    const btnResendOtp = document.getElementById('btnResendOtp');
    const otpAlertBox = document.getElementById('otp-alert-box');
    const timerDisplay = document.getElementById('otpTimerDisplay');
    const otpFeedback = document.getElementById('otpFeedbackStatus');

    let otpTimerInterval = null;
    let registeredPhone = '';

    function startOtpTimer(durationSeconds) {
        clearInterval(otpTimerInterval);
        let rem = durationSeconds;
        otpTimerInterval = setInterval(() => {
            rem--;
            if (rem <= 0) {
                clearInterval(otpTimerInterval);
                if (timerDisplay) timerDisplay.textContent = '00:00 (Kedaluwarsa)';
                if (btnSubmitOtp) btnSubmitOtp.disabled = true;
                if (btnResendOtp) btnResendOtp.disabled = false;
                return;
            }
            const m = Math.floor(rem / 60);
            const s = rem % 60;
            if (timerDisplay) {
                timerDisplay.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            }
        }, 1000);
    }

    if (btnRequestOtp) {
        btnRequestOtp.addEventListener('click', function() {
            const phone = inputTargetPhone.value.trim();
            if (!phone) {
                showOtpAlert('danger', 'Silakan ketik nomor WhatsApp terlebih dahulu.');
                return;
            }

            btnRequestOtp.disabled = true;
            btnRequestOtp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mengirim OTP...';
            otpAlertBox.classList.add('d-none');

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('csrf_token', csrfToken);

            fetch(baseUrl + '/whatsapp/sendOtp', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnRequestOtp.disabled = false;
                btnRequestOtp.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Kirim Kode OTP';

                if (data.success) {
                    registeredPhone = data.phone;
                    stepVerifyOtp.classList.remove('d-none');
                    inputOtpCode.value = '';
                    inputOtpCode.focus();
                    if (btnSubmitOtp) btnSubmitOtp.disabled = false;
                    startOtpTimer(300); // 5 menit

                    let html = '<div class="d-flex align-items-center mb-1"><i class="fas fa-check-circle text-success fs-5 me-2"></i><strong>Kode OTP 6-Digit Terkirim!</strong></div>';
                    html += '<p class="mb-1 text-dark">Silakan periksa WhatsApp pada nomor <strong>' + data.phone + '</strong> dan masukkan kode verifikasi di bawah.</p>';
                    if (data.wa_link) {
                        html += '<a href="' + data.wa_link + '" target="_blank" class="btn btn-xs btn-outline-success mt-1"><i class="fab fa-whatsapp me-1"></i>Buka Pesan di WhatsApp</a>';
                    }
                    showOtpAlert('success', html);
                    refreshLogsTable();
                } else {
                    showOtpAlert('danger', data.message || 'Gagal mengirim kode OTP.');
                }
            })
            .catch(() => {
                btnRequestOtp.disabled = false;
                btnRequestOtp.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Kirim Kode OTP';
                showOtpAlert('danger', 'Terjadi kesalahan sistem saat menghubungi server.');
            });
        });
    }

    if (btnResendOtp) {
        btnResendOtp.addEventListener('click', function() {
            if (btnRequestOtp) btnRequestOtp.click();
        });
    }

    // Submit OTP Code
    if (btnSubmitOtp) {
        btnSubmitOtp.addEventListener('click', function() {
            const otpVal = inputOtpCode.value.trim();
            if (!otpVal || otpVal.length < 6) {
                showOtpAlert('warning', 'Harap masukkan 6 digit kode OTP yang lengkap.');
                return;
            }

            btnSubmitOtp.disabled = true;
            btnSubmitOtp.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memverifikasi...';

            const fd = new FormData();
            fd.append('phone', registeredPhone || inputTargetPhone.value.trim());
            fd.append('otp', otpVal);
            fd.append('csrf_token', csrfToken);

            fetch(baseUrl + '/whatsapp/verifyOtp', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnSubmitOtp.disabled = false;
                btnSubmitOtp.innerHTML = '<i class="fas fa-check-circle me-1"></i>Verifikasi & Hubungkan';

                if (data.success) {
                    clearInterval(otpTimerInterval);
                    stepVerifyOtp.classList.add('d-none');

                    // Update form field wa_phone & wa_enabled toggle
                    const inputWaPhone = document.getElementById('wa_phone');
                    const toggleWaEnabled = document.getElementById('wa_enabled');
                    const badgeGlobal = document.getElementById('badgeGatewayGlobal');

                    if (inputWaPhone) inputWaPhone.value = data.phone;
                    if (toggleWaEnabled) toggleWaEnabled.checked = true;
                    if (badgeGlobal) {
                        badgeGlobal.className = 'badge bg-success bg-opacity-10 text-success border border-success-subtle px-3 py-2 rounded-pill fs-6';
                        badgeGlobal.innerHTML = '<i class="fas fa-check-circle me-1"></i>Gateway Aktif';
                    }

                    let successHtml = '<div class="d-flex align-items-center mb-1"><i class="fas fa-check-double text-success fs-4 me-2"></i><strong>Nomor WhatsApp Resmi Terhubung!</strong></div>';
                    successHtml += '<p class="mb-1 text-dark">Nomor <strong>' + data.phone + '</strong> telah berhasil diverifikasi dan kini aktif menerima seluruh notifikasi transaksi gudang secara real-time.</p>';
                    if (data.wa_link) {
                        successHtml += '<a href="' + data.wa_link + '" target="_blank" class="btn btn-xs btn-success mt-1"><i class="fab fa-whatsapp me-1"></i>Lihat Pesan Konfirmasi di WhatsApp</a>';
                    }
                    showOtpAlert('success', successHtml);
                    refreshLogsTable();
                } else {
                    showOtpAlert('danger', data.message || 'Verifikasi kode OTP gagal.');
                }
            })
            .catch(() => {
                btnSubmitOtp.disabled = false;
                btnSubmitOtp.innerHTML = '<i class="fas fa-check-circle me-1"></i>Verifikasi & Hubungkan';
                showOtpAlert('danger', 'Terjadi kesalahan sistem saat memverifikasi OTP.');
            });
        });
    }

    function showOtpAlert(type, content) {
        otpAlertBox.className = 'mt-2 p-3 rounded-3 small border border-' + type + '-subtle bg-' + type + ' bg-opacity-10 text-dark';
        otpAlertBox.innerHTML = content;
        otpAlertBox.classList.remove('d-none');
    }

    // ==========================================
    // 3. FONNTE PAIRING CODE & QR TOOLS
    // ==========================================
    const btnGetPairing = document.getElementById('btnGetPairingCode');
    const btnGetQr = document.getElementById('btnGetQrCode');
    const pairingPhone = document.getElementById('pairing_phone');
    const pairingBox = document.getElementById('pairingResultBox');

    if (btnGetPairing) {
        btnGetPairing.addEventListener('click', function() {
            const phone = pairingPhone.value.trim();
            if (!phone) {
                pairingBox.className = 'mt-3 p-3 rounded-3 border bg-warning bg-opacity-10 text-dark small';
                pairingBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Masukkan nomor WhatsApp untuk kode pairing.';
                pairingBox.classList.remove('d-none');
                return;
            }

            btnGetPairing.disabled = true;
            btnGetPairing.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Meminta Kode...';

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('csrf_token', csrfToken);

            fetch(baseUrl + '/whatsapp/requestPairingCode', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnGetPairing.disabled = false;
                btnGetPairing.innerHTML = '<i class="fas fa-key me-1"></i>Minta Kode Pairing (8 Digit)';
                pairingBox.classList.remove('d-none');

                if (data.success && data.code) {
                    pairingBox.className = 'mt-3 p-4 rounded-4 border border-primary bg-primary bg-opacity-10 text-dark';
                    let h = '<h6 class="fw-bold text-primary mb-2"><i class="fas fa-key me-1"></i>Kode Pairing WhatsApp Anda:</h6>';
                    h += '<div class="p-3 bg-white rounded-3 border text-center my-3"><span class="fs-2 fw-bold font-monospace text-dark tracking-widest">' + escapeHtml(data.code) + '</span></div>';
                    h += '<div class="small text-muted"><p class="mb-1"><strong>Cara menghubungkan di WhatsApp HP Anda:</strong></p>';
                    h += '<ol class="mb-0 ps-3"><li>Buka aplikasi WhatsApp di HP Anda.</li><li>Ketuk Titik Tiga (Menu) > <strong>Perangkat Tertaut (Linked Devices)</strong>.</li><li>Pilih <strong>Tautkan Perangkat > Tautkan dengan nomor telepon</strong>.</li><li>Masukkan 8-karakter kode di atas.</li></ol></div>';
                    pairingBox.innerHTML = h;
                } else {
                    pairingBox.className = 'mt-3 p-3 rounded-3 border border-warning-subtle bg-warning bg-opacity-10 text-dark small';
                    pairingBox.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + (data.message || 'Perangkat sudah terhubung atau permintaan ditolak.');
                }
            })
            .catch(() => {
                btnGetPairing.disabled = false;
                btnGetPairing.innerHTML = '<i class="fas fa-key me-1"></i>Minta Kode Pairing (8 Digit)';
                pairingBox.className = 'mt-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10 text-danger small';
                pairingBox.innerHTML = '<i class="fas fa-times-circle me-1"></i>Gagal memuat kode pairing.';
                pairingBox.classList.remove('d-none');
            });
        });
    }

    if (btnGetQr) {
        btnGetQr.addEventListener('click', function() {
            btnGetQr.disabled = true;
            btnGetQr.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Memuat QR...';

            fetch(baseUrl + '/whatsapp/requestQrCode')
            .then(res => res.json())
            .then(data => {
                btnGetQr.disabled = false;
                btnGetQr.innerHTML = '<i class="fas fa-qrcode me-1"></i>Tampilkan QR Code';
                pairingBox.classList.remove('d-none');

                if (data.success && data.qr_url) {
                    pairingBox.className = 'mt-3 p-4 rounded-4 border bg-white text-center';
                    let h = '<h6 class="fw-bold text-dark mb-3"><i class="fas fa-qrcode text-success me-1"></i>Scan QR Code dengan WhatsApp</h6>';
                    h += '<img src="data:image/png;base64,' + data.qr_url + '" class="img-fluid rounded-3 border shadow-sm p-2 mb-3" style="max-width:240px;">';
                    h += '<p class="small text-muted mb-0">Buka WhatsApp > Perangkat Tertaut > Tautkan Perangkat > Scan QR Code di atas.</p>';
                    pairingBox.innerHTML = h;
                } else {
                    pairingBox.className = 'mt-3 p-3 rounded-3 border border-warning-subtle bg-warning bg-opacity-10 text-dark small';
                    pairingBox.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + (data.message || 'Gagal memuat QR Code.');
                }
            })
            .catch(() => {
                btnGetQr.disabled = false;
                btnGetQr.innerHTML = '<i class="fas fa-qrcode me-1"></i>Tampilkan QR Code';
                pairingBox.className = 'mt-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10 text-danger small';
                pairingBox.innerHTML = '<i class="fas fa-times-circle me-1"></i>Gagal memuat QR Code.';
                pairingBox.classList.remove('d-none');
            });
        });
    }

    // ==========================================
    // 4. PROVIDER DROPDOWN TOGGLE
    // ==========================================
    const provSelect = document.getElementById('wa_provider');
    const fieldToken = document.getElementById('field-token');
    const fieldUrl = document.getElementById('field-api-url');
    const fieldInst = document.getElementById('field-instance');

    function updateProviderFields() {
        if (!provSelect) return;
        const val = provSelect.value;
        if (val === 'wame_log') {
            if (fieldToken) fieldToken.classList.add('d-none');
            if (fieldUrl) fieldUrl.classList.add('d-none');
            if (fieldInst) fieldInst.classList.add('d-none');
        } else if (val === 'ultramsg') {
            if (fieldToken) fieldToken.classList.remove('d-none');
            if (fieldUrl) fieldUrl.classList.remove('d-none');
            if (fieldInst) fieldInst.classList.remove('d-none');
        } else {
            if (fieldToken) fieldToken.classList.remove('d-none');
            if (fieldUrl) fieldUrl.classList.remove('d-none');
            if (fieldInst) fieldInst.classList.add('d-none');
        }
    }

    if (provSelect) {
        provSelect.addEventListener('change', updateProviderFields);
        updateProviderFields();
    }

    // Toggle Token Eye
    const btnToggle = document.getElementById('btnToggleToken');
    const inputToken = document.getElementById('wa_api_token');
    if (btnToggle && inputToken) {
        btnToggle.addEventListener('click', function() {
            if (inputToken.type === 'password') {
                inputToken.type = 'text';
                btnToggle.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                inputToken.type = 'password';
                btnToggle.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    }

    // ==========================================
    // 5. TEST CONNECTION AJAX
    // ==========================================
    const btnSendTest = document.getElementById('btnSendTest');
    const testPhone = document.getElementById('test_phone');
    const testBox = document.getElementById('test-feedback-box');

    if (btnSendTest) {
        btnSendTest.addEventListener('click', function() {
            const phone = testPhone.value.trim();
            if (!phone) {
                testBox.className = 'mt-3 p-3 rounded-3 border bg-warning bg-opacity-10 text-dark small';
                testBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>Silakan ketik nomor WhatsApp terlebih dahulu.';
                testBox.classList.remove('d-none');
                return;
            }

            btnSendTest.disabled = true;
            btnSendTest.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mengirim...';
            testBox.classList.add('d-none');

            const fd = new FormData();
            fd.append('phone', phone);
            fd.append('csrf_token', csrfToken);

            fetch(baseUrl + '/whatsapp/test', {
                method: 'POST',
                body: fd
            })
            .then(res => res.json())
            .then(data => {
                btnSendTest.disabled = false;
                btnSendTest.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Kirim Tes';
                testBox.classList.remove('d-none');

                if (data.success) {
                    testBox.className = 'mt-3 p-3 rounded-3 border border-success-subtle bg-success bg-opacity-10 text-dark small';
                    let html = '<div class="d-flex align-items-center mb-2"><i class="fas fa-check-circle text-success fs-5 me-2"></i><strong>Pesan Uji Coba Berhasil Terkirim!</strong></div>';
                    html += '<p class="mb-2">Status pengiriman: <span class="badge bg-success">Terkirim</span> via provider <strong>' + (data.provider || 'Fonnte') + '</strong>.</p>';
                    if (data.first_link) {
                        html += '<a href="' + data.first_link + '" target="_blank" class="btn btn-xs btn-success"><i class="fab fa-whatsapp me-1"></i>Buka Pesan di WhatsApp</a>';
                    }
                    testBox.innerHTML = html;
                } else {
                    testBox.className = 'mt-3 p-3 rounded-3 border border-warning-subtle bg-warning bg-opacity-10 text-dark small';
                    let html = '<div class="d-flex align-items-center mb-1"><i class="fas fa-info-circle text-warning fs-5 me-2"></i><strong>Catatan Pengiriman:</strong></div>';
                    html += '<p class="mb-2">' + (data.message || 'Pesan belum terkirim via API.') + '</p>';
                    if (data.first_link) {
                        html += '<p class="mb-2 text-muted">Anda dapat menguji coba via tautan WhatsApp Web:</p>';
                        html += '<a href="' + data.first_link + '" target="_blank" class="btn btn-xs btn-outline-success"><i class="fab fa-whatsapp me-1"></i>Kirim Manual via wa.me</a>';
                    }
                    testBox.innerHTML = html;
                }
                refreshLogsTable();
            })
            .catch(() => {
                btnSendTest.disabled = false;
                btnSendTest.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Kirim Tes';
                testBox.className = 'mt-3 p-3 rounded-3 border border-danger bg-danger bg-opacity-10 text-danger small';
                testBox.innerHTML = '<i class="fas fa-times-circle me-1"></i>Terjadi kesalahan sistem saat menghubungi server.';
                testBox.classList.remove('d-none');
            });
        });
    }

    // ==========================================
    // 6. REFRESH LOGS TABLE
    // ==========================================
    const btnRefreshLogs = document.getElementById('btnRefreshLogs');
    function refreshLogsTable() {
        fetch(baseUrl + '/whatsapp/getLogs?limit=25')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.logs) {
                const tbody = document.getElementById('logs-tbody');
                const countBadge = document.getElementById('logCountBadge');
                if (countBadge) countBadge.textContent = res.logs.length + ' Terakhir';

                if (!tbody) return;
                if (res.logs.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-4 text-muted">Belum ada log.</td></tr>';
                    return;
                }
                let h = '';
                res.logs.forEach(l => {
                    let st = '<span class="badge bg-secondary">Simulasi</span>';
                    if (l.status === 'sent') st = '<span class="badge bg-success"><i class="fas fa-check me-1"></i>Terkirim</span>';
                    else if (l.status === 'failed') st = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i>Gagal</span>';
                    else if (l.status === 'simulated') st = '<span class="badge bg-info text-dark"><i class="fas fa-info-circle me-1"></i>Tercatat</span>';

                    const cleanPhone = (l.recipient || '').replace(/[^0-9]/g, '');
                    const waLink = 'https://api.whatsapp.com/send?phone=' + cleanPhone + '&text=' + encodeURIComponent(l.message);

                    h += '<tr>';
                    h += '<td class="ps-3"><div class="fw-bold text-dark">' + escapeHtml(l.message_type) + '</div><small class="text-muted">' + (l.created_at || '').substring(5, 16) + '</small></td>';
                    h += '<td><code>' + escapeHtml(l.recipient) + '</code>' + (l.transaction_number ? '<br><small class="text-muted font-monospace">' + escapeHtml(l.transaction_number) + '</small>' : '') + '</td>';
                    h += '<td>' + st + '</td>';
                    h += '<td class="text-end pe-3"><a href="' + waLink + '" target="_blank" class="btn btn-xs btn-outline-success" title="Buka di WhatsApp"><i class="fab fa-whatsapp"></i></a></td>';
                    h += '</tr>';
                });
                tbody.innerHTML = h;
            }
        }).catch(() => {});
    }

    if (btnRefreshLogs) {
        btnRefreshLogs.addEventListener('click', function() {
            btnRefreshLogs.querySelector('i').classList.add('fa-spin');
            refreshLogsTable();
            setTimeout(() => { btnRefreshLogs.querySelector('i').classList.remove('fa-spin'); }, 600);
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>
