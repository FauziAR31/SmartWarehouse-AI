        <!-- Sidebar -->
        <div class="bg-dark text-white shadow-sm" id="sidebar-wrapper">
            <div class="sidebar-heading d-flex align-items-center justify-content-between px-3 py-3 border-bottom border-secondary border-opacity-50">
                <a href="<?php echo BASE_URL; ?>" class="d-flex align-items-center text-white text-decoration-none">
                    <div class="sidebar-brand-icon me-2 rounded-3 d-flex align-items-center justify-content-center bg-primary text-white">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5 tracking-tight brand-font mb-0"><?php echo APP_NAME; ?></div>
                        <small class="text-white-50" style="font-size: 0.68rem; letter-spacing: 0.5px;">MANAGEMENT SYSTEM</small>
                    </div>
                </a>
                <button type="button" class="btn btn-sm btn-icon text-white-50 hover-white d-lg-none" id="sidebar-close" aria-label="Tutup Menu">
                    <i class="fas fa-times fs-5"></i>
                </button>
            </div>
            <?php
            $currentUrl = $_SERVER['REQUEST_URI'] ?? '';
            $baseSegment = isset($_GET['url']) ? explode('/', rtrim($_GET['url'], '/')) : ['home'];
            $currentController = strtolower($baseSegment[0] ?? 'home');
            $currentAction = strtolower($baseSegment[1] ?? 'index');
            $userRole = $_SESSION['role_name'] ?? 'Guest';
            ?>
            <div class="list-group list-group-flush my-3">

                <!-- Dashboard -->
                <a href="<?php echo BASE_URL; ?>/home/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold <?php echo ($currentController == 'home' || $currentController == '') ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt me-3 text-primary"></i>Dashboard
                </a>

                <!-- Asisten AI & Otomasi Group -->
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1 d-flex align-items-center justify-content-between">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Asisten AI & Otomasi</small>
                    <span class="badge bg-primary bg-gradient rounded-pill px-2" style="font-size: 0.6rem;">PRO AI</span>
                </div>

                <a href="<?php echo BASE_URL; ?>/aichat/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'aichat') ? 'active' : ''; ?>">
                    <i class="fas fa-robot me-3 text-info"></i>Asisten AI (Chat)
                    <span class="badge bg-info bg-opacity-25 text-info border border-info border-opacity-25 rounded-pill ms-auto px-2" style="font-size: 0.62rem;">Live</span>
                </a>

                <?php if (in_array($userRole, ['Admin', 'Supervisor'])): ?>
                <a href="<?php echo BASE_URL; ?>/laporan/ai_summary" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'ai_summary') ? 'active' : ''; ?>">
                    <i class="fas fa-file-invoice me-3 text-warning"></i>Laporan AI Harian
                </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['Admin', 'Supervisor'])): ?>
                <!-- Master Data Group -->
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Master Data</small>
                </div>

                <a href="<?php echo BASE_URL; ?>/kategori/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'kategori') ? 'active' : ''; ?>">
                    <i class="fas fa-tags me-3 text-secondary"></i>Kategori
                </a>

                <a href="<?php echo BASE_URL; ?>/barang/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'barang') ? 'active' : ''; ?>">
                    <i class="fas fa-box me-3 text-warning"></i>Barang
                </a>

                <a href="<?php echo BASE_URL; ?>/lokasi/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'lokasi') ? 'active' : ''; ?>">
                    <i class="fas fa-map-marker-alt me-3 text-danger"></i>Lokasi Rak
                </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['Admin', 'Supervisor', 'Operator'])): ?>
                <!-- Transaksi Group -->
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Transaksi</small>
                </div>
                <a href="<?php echo BASE_URL; ?>/transaksi/masuk" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'transaksi' && $currentAction == 'masuk') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-down me-3 text-success"></i>Barang Masuk
                </a>
                <a href="<?php echo BASE_URL; ?>/transaksi/keluar" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'transaksi' && $currentAction == 'keluar') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-up me-3 text-danger"></i>Barang Keluar
                </a>
                <a href="<?php echo BASE_URL; ?>/transaksi/transfer" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'transaksi' && $currentAction == 'transfer') ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt me-3 text-warning"></i>Pemindahan Barang
                </a>
                <?php endif; ?>

                <!-- Inventory & Stok Group -->
                <?php if (in_array($userRole, ['Admin', 'Supervisor', 'Operator'])): ?>
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Inventory & Stok</small>
                </div>
                <a href="<?php echo BASE_URL; ?>/stok/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'stok') ? 'active' : ''; ?>">
                    <i class="fas fa-cubes me-3 text-info"></i>Stok Barang
                </a>
                <?php if (in_array($userRole, ['Admin', 'Supervisor'])): ?>
                <a href="<?php echo BASE_URL; ?>/riwayat/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'riwayat') ? 'active' : ''; ?>">
                    <i class="fas fa-history me-3 text-primary"></i>Riwayat Transaksi
                </a>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (in_array($userRole, ['Admin', 'Supervisor'])): ?>
                <!-- Laporan Group -->
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Laporan</small>
                </div>
                <a href="<?php echo BASE_URL; ?>/laporan/stok" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'stok') ? 'active' : ''; ?>">
                    <i class="fas fa-layer-group me-3 text-info"></i>Laporan Stok
                </a>
                <a href="<?php echo BASE_URL; ?>/laporan/masuk" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'masuk') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-down me-3 text-success"></i>Laporan Masuk
                </a>
                <a href="<?php echo BASE_URL; ?>/laporan/keluar" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'keluar') ? 'active' : ''; ?>">
                    <i class="fas fa-arrow-circle-up me-3 text-danger"></i>Laporan Keluar
                </a>
                <a href="<?php echo BASE_URL; ?>/laporan/pemindahan" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'pemindahan') ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt me-3 text-warning"></i>Laporan Pemindahan
                </a>
                <a href="<?php echo BASE_URL; ?>/laporan/aktivitas" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'laporan' && $currentAction == 'aktivitas') ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list me-3 text-secondary"></i>Laporan Log Aktivitas
                </a>
                <?php endif; ?>

                <?php if (in_array($userRole, ['Admin', 'Supervisor'])): ?>
                <!-- Pengaturan & Integrasi Group -->
                <div class="list-group-item bg-transparent border-0 px-3 pt-3 pb-1">
                    <small class="text-uppercase text-secondary fw-bold" style="font-size: 0.7rem; letter-spacing: 1.5px;">Pengaturan & Integrasi</small>
                </div>
                <?php if ($userRole === 'Admin'): ?>
                <a href="<?php echo BASE_URL; ?>/user/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'user') ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog me-3 text-light"></i>Manajemen User
                </a>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/whatsapp/index" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4 <?php echo ($currentController == 'whatsapp') ? 'active' : ''; ?>">
                    <i class="fab fa-whatsapp me-3 text-success"></i>Notifikasi WhatsApp
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill ms-auto px-2" style="font-size: 0.62rem;">Gateway</span>
                </a>
                <a href="<?php echo BASE_URL; ?>/aichat/index#settings" class="list-group-item list-group-item-action bg-transparent text-white fw-semibold ps-4">
                    <i class="fas fa-cog me-3 text-info"></i>Pengaturan Gemini AI
                </a>
                <?php endif; ?>

            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content Wrapper -->
        <div id="page-content-wrapper">

            <!-- Top Navbar -->
            <nav class="navbar navbar-light bg-white border-bottom shadow-sm px-2 px-md-3 sticky-top main-navbar">
                <div class="container-fluid px-0 d-flex justify-content-between align-items-center">
                    <!-- LEFT: Hamburger + Brand (mobile) -->
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center" id="menu-toggle" aria-label="Toggle Navigation" style="width: 36px; height: 36px; border-radius: 8px;">
                            <i class="fas fa-bars"></i>
                        </button>
                        <a href="<?php echo BASE_URL; ?>" class="navbar-brand mb-0 fw-bold fs-6 text-dark d-lg-none d-flex align-items-center text-decoration-none">
                            <i class="fas fa-warehouse text-primary me-2"></i>
                            <span class="brand-font"><?php echo APP_NAME; ?></span>
                        </a>
                    </div>

                    <!-- RIGHT: Notifications + Role Badge + User Dropdown -->
                    <div class="d-flex align-items-center gap-2">
                        <!-- Real-time Notification Bell -->
                        <div class="dropdown" id="dropdownRealtimeNotif">
                            <button class="btn btn-light position-relative d-flex align-items-center justify-content-center border rounded-3 text-secondary hover-primary"
                                    id="btnNotifBell"
                                    type="button"
                                    data-bs-toggle="dropdown"
                                    data-bs-auto-close="outside"
                                    aria-expanded="false"
                                    title="Notifikasi Real-time Gudang"
                                    style="width: 38px; height: 38px;">
                                <i class="fas fa-bell fs-6"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-white d-none"
                                      id="notifBadgeCount"
                                      style="font-size: 0.65rem; padding: 0.35em 0.55em; margin-left: -6px; margin-top: 6px;">
                                    0
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 p-0 overflow-hidden"
                                 style="width: 380px; max-width: 92vw; z-index: 1060;">
                                <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-broadcast-tower text-primary"></i>
                                        <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.92rem;">Notifikasi Real-Time</h6>
                                    </div>
                                    <button type="button" class="btn btn-link btn-xs text-decoration-none text-muted p-0" id="btnMarkAllNotifsRead" title="Tandai semua sudah dibaca">
                                        <small><i class="fas fa-check-double me-1"></i>Tandai Dibaca</small>
                                    </button>
                                </div>
                                <div class="list-group list-group-flush" id="notifDropdownList" style="max-height: 380px; overflow-y: auto;">
                                    <div class="text-center py-4 text-muted small" id="notifEmptyState">
                                        <i class="fas fa-bell-slash fs-4 d-block mb-2 text-secondary opacity-50"></i>
                                        Belum ada notifikasi transaksi baru.
                                    </div>
                                </div>
                                <div class="p-2 bg-light border-top text-center">
                                    <a href="<?php echo BASE_URL; ?>/riwayat/index" class="text-decoration-none small fw-semibold text-primary">
                                        <i class="fas fa-history me-1"></i>Lihat Seluruh Riwayat Transaksi
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Role Badge -->
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 d-none d-sm-inline-flex align-items-center gap-1 small">
                            <i class="fas fa-shield-alt"></i>
                            <?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Guest'); ?>
                        </span>

                        <!-- User Profile Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle d-flex align-items-center gap-2 py-1 px-2 border rounded-3 text-dark text-decoration-none" id="navbarDropdown" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="height:38px;">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'User'); ?>&background=0D8ABC&color=fff" class="rounded-circle" width="28" height="28" alt="Avatar" style="flex-shrink:0;">
                                <span class="fw-semibold small d-none d-sm-inline-block text-dark" style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 mt-2" aria-labelledby="navbarDropdown" style="min-width:220px;">
                                <div class="dropdown-header border-bottom pb-2 mb-2">
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?></div>
                                    <small class="text-muted d-block">@<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></small>
                                    <span class="badge bg-secondary-subtle text-secondary border mt-1" style="font-size:0.7rem;"><?php echo htmlspecialchars($_SESSION['role_name'] ?? ''); ?></span>
                                </div>
                                <a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/home/index"><i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard</a>
                                <a class="dropdown-item py-2" href="javascript:void(0)" onclick="var l=document.getElementById('ai-chat-launcher');if(l)l.click();"><i class="fas fa-robot me-2 text-info"></i>AI Assistant</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item py-2 text-danger fw-semibold" href="<?php echo BASE_URL; ?>/auth/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content Container with responsive padding -->
            <div class="container-fluid px-2 px-sm-3 px-md-4 py-3 py-md-4 main-content-container">
