/**
 * SmartWarehouse Real-Time Transaction Notification Engine
 * Displays live floating toasts, audio chime, and updates navbar notification center
 */
(function() {
    'use strict';

    let lastNotifId = 0;
    let isInitialized = false;
    let pollInterval = null;
    let audioCtx = null;

    // Initialize Web Audio API for notification chimes
    function getAudioContext() {
        if (!audioCtx) {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (AudioContext) {
                audioCtx = new AudioContext();
            }
        }
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume();
        }
        return audioCtx;
    }

    // Play subtle, elegant two-tone notification chime
    function playNotificationChime() {
        try {
            const ctx = getAudioContext();
            if (!ctx) return;

            const now = ctx.currentTime;
            
            // Tone 1: 523.25 Hz (C5)
            const osc1 = ctx.createOscillator();
            const gain1 = ctx.createGain();
            osc1.type = 'sine';
            osc1.frequency.setValueAtTime(523.25, now);
            gain1.gain.setValueAtTime(0.12, now);
            gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.22);
            osc1.connect(gain1);
            gain1.connect(ctx.destination);
            osc1.start(now);
            osc1.stop(now + 0.22);

            // Tone 2: 783.99 Hz (G5)
            const osc2 = ctx.createOscillator();
            const gain2 = ctx.createGain();
            osc2.type = 'sine';
            osc2.frequency.setValueAtTime(783.99, now + 0.12);
            gain2.gain.setValueAtTime(0.18, now + 0.12);
            gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.45);
            osc2.connect(gain2);
            gain2.connect(ctx.destination);
            osc2.start(now + 0.12);
            osc2.stop(now + 0.45);
        } catch (e) {
            // Audio policy / autoplay restriction resilience
        }
    }

    // Ensure Floating Toast Container exists in DOM
    function getToastContainer() {
        let container = document.getElementById('realtime-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'realtime-toast-container';
            container.style.cssText = 'position:fixed; top:75px; right:20px; z-index:1099; display:flex; flex-direction:column; gap:12px; max-width:420px; width:calc(100% - 40px); pointer-events:none;';
            document.body.appendChild(container);
        }
        return container;
    }

    // Map transaction type to styles and icons
    function getTypeMeta(type) {
        switch (type) {
            case 'in':
                return {
                    label: 'BARANG MASUK',
                    icon: 'fa-arrow-circle-down',
                    badgeClass: 'bg-success',
                    borderClass: 'border-success',
                    shadowColor: 'rgba(25, 135, 84, 0.25)',
                    sign: '+'
                };
            case 'out':
                return {
                    label: 'BARANG KELUAR',
                    icon: 'fa-arrow-circle-up',
                    badgeClass: 'bg-danger',
                    borderClass: 'border-danger',
                    shadowColor: 'rgba(220, 53, 69, 0.25)',
                    sign: '-'
                };
            case 'transfer':
                return {
                    label: 'PEMINDAHAN RAK',
                    icon: 'fa-exchange-alt',
                    badgeClass: 'bg-warning text-dark',
                    borderClass: 'border-warning',
                    shadowColor: 'rgba(255, 193, 7, 0.25)',
                    sign: ''
                };
            case 'adjustment':
                return {
                    label: 'KOREKSI STOK',
                    icon: 'fa-balance-scale',
                    badgeClass: 'bg-info text-dark',
                    borderClass: 'border-info',
                    shadowColor: 'rgba(13, 202, 240, 0.25)',
                    sign: ''
                };
            case 'low_stock':
                return {
                    label: 'STOK MENIPIS',
                    icon: 'fa-exclamation-triangle',
                    badgeClass: 'bg-danger',
                    borderClass: 'border-danger',
                    shadowColor: 'rgba(220, 53, 69, 0.35)',
                    sign: ''
                };
            default:
                return {
                    label: 'TRANSAKSI GUDANG',
                    icon: 'fa-box',
                    badgeClass: 'bg-primary',
                    borderClass: 'border-primary',
                    shadowColor: 'rgba(13, 110, 253, 0.25)',
                    sign: ''
                };
        }
    }

    // Display rich floating Toast Notification
    function showLiveToast(item) {
        const container = getToastContainer();
        const meta = getTypeMeta(item.trx_type);

        const toast = document.createElement('div');
        toast.className = 'card border-0 shadow-lg rounded-4 overflow-hidden mb-2 animate-toast-in';
        toast.style.cssText = `pointer-events:auto; border-left: 5px solid !important; transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 10px 25px -5px ${meta.shadowColor}, 0 8px 10px -6px rgba(0,0,0,0.1) !important; background: #ffffff;`;
        toast.style.borderLeftColor = meta.borderClass.includes('success') ? '#198754' : (meta.borderClass.includes('danger') ? '#dc3545' : (meta.borderClass.includes('warning') ? '#ffc107' : '#0dcaf0'));

        const qtyFormatted = item.qty ? Number(item.qty).toLocaleString('id-ID') : '';
        const qtyPill = item.qty ? `<span class="badge ${meta.badgeClass} rounded-pill px-2 py-1">${meta.sign}${qtyFormatted} ${escapeHtml(item.unit || 'unit')}</span>` : '';

        // Clean WhatsApp text for quick share
        const shareMsg = `*Notifikasi Real-time SmartWarehouse*\n${item.title}\nNo: ${item.trx_number}\nOperator: ${item.operator_name}\n${item.message}`;
        const waLink = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(shareMsg);

        toast.innerHTML = `
            <div class="card-body p-3">
                <div class="d-flex align-items-start justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge ${meta.badgeClass} rounded-pill px-2 py-1 fw-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">
                            <i class="fas ${meta.icon} me-1"></i>${meta.label}
                        </span>
                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">${escapeHtml(item.trx_number)}</small>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge bg-light text-secondary border rounded-pill" style="font-size: 0.65rem;">Baru saja</span>
                        <button type="button" class="btn-close ms-1" style="font-size: 0.7rem;" aria-label="Close"></button>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-1" style="font-size: 0.95rem;">${escapeHtml(item.barang_name || item.title)}</h6>
                        ${qtyPill}
                    </div>
                    <p class="text-secondary small mb-1" style="line-height: 1.35; font-size: 0.82rem;">${escapeHtml(item.message)}</p>
                    <div class="d-flex align-items-center gap-3 text-muted small mt-1" style="font-size: 0.75rem;">
                        <span><i class="fas fa-user-circle me-1 text-primary"></i>${escapeHtml(item.operator_name || 'Petugas')}</span>
                        ${item.lokasi_info ? `<span><i class="fas fa-map-marker-alt me-1 text-danger"></i>${escapeHtml(item.lokasi_info)}</span>` : ''}
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-2 border-top mt-2">
                    <button type="button" class="btn btn-xs btn-primary rounded-pill px-3 py-1 fw-semibold btn-show-popup" style="font-size: 0.72rem;">
                        <i class="fas fa-file-invoice me-1"></i>Buka Pop-up Bukti
                    </button>
                    <a href="${waLink}" target="_blank" class="btn btn-xs btn-success rounded-pill px-2 py-1 fw-semibold" style="font-size: 0.72rem;" title="Bagikan ke WhatsApp">
                        <i class="fab fa-whatsapp me-1"></i>Bagikan WA
                    </a>
                </div>
            </div>
            <div class="toast-progress-bar" style="height: 3px; background: rgba(0,0,0,0.06); width: 100%;">
                <div class="progress-fill" style="height: 100%; width: 100%; background: #0d6efd; transition: width 8s linear;"></div>
            </div>
        `;

        container.prepend(toast);

        // Click handler on "Buka Pop-up Bukti" button
        const btnOpen = toast.querySelector('.btn-show-popup');
        if (btnOpen) {
            btnOpen.addEventListener('click', function(e) {
                e.stopPropagation();
                if (typeof window.showTransactionPopup === 'function') {
                    window.showTransactionPopup(item);
                }
            });
        }

        // Click handler on toast card body
        toast.addEventListener('click', function(e) {
            if (e.target.closest('.btn-close') || e.target.closest('a')) return;
            if (typeof window.showTransactionPopup === 'function') {
                window.showTransactionPopup(item);
            }
        });

        // Animate progress bar
        setTimeout(() => {
            const fill = toast.querySelector('.progress-fill');
            if (fill) fill.style.width = '0%';
        }, 50);

        // Close button handler
        const btnClose = toast.querySelector('.btn-close');
        function removeToast() {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(60px)';
            setTimeout(() => {
                if (toast.parentNode) toast.parentNode.removeChild(toast);
            }, 300);
        }

        if (btnClose) {
            btnClose.addEventListener('click', function(e) {
                e.stopPropagation();
                removeToast();
            });
        }

        // Auto remove after 8.5 seconds
        setTimeout(removeToast, 8500);

        // Native Browser OS Notification if permission granted
        if ('Notification' in window && Notification.permission === 'granted') {
            try {
                new Notification(`SmartWarehouse: ${meta.label}`, {
                    body: `${item.barang_name ? item.barang_name + ' - ' : ''}${item.message}`,
                    icon: '/05092026/SmartWarehouse/assets/img/icon.png'
                });
            } catch (e) {}
        }
    }

    // Update Dropdown List & Unread Counter
    function updateDropdownUi(items, unreadCount) {
        const badge = document.getElementById('notifBadgeCount');
        const list = document.getElementById('notifDropdownList');
        const empty = document.getElementById('notifEmptyState');

        if (badge) {
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                badge.classList.remove('d-none');
            } else {
                badge.classList.add('d-none');
            }
        }

        if (list && Array.isArray(items) && items.length > 0) {
            if (empty) empty.classList.add('d-none');

            list.innerHTML = '';
            items.slice(0, 10).forEach(item => {
                const meta = getTypeMeta(item.trx_type);
                const isUnread = (item.is_read == 0 || item.is_read === '0');
                const bgStyle = isUnread ? 'bg-primary bg-opacity-10' : 'bg-white';

                const el = document.createElement('div');
                el.className = `list-group-item list-group-item-action p-3 border-0 border-bottom ${bgStyle} transition-all cursor-pointer`;
                el.style.cursor = 'pointer';
                el.innerHTML = `
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge ${meta.badgeClass} rounded-circle p-2 mt-1">
                            <i class="fas ${meta.icon}"></i>
                        </span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-bold text-dark small text-truncate" style="font-size: 0.84rem;">
                                    ${escapeHtml(item.title)}
                                </span>
                                <small class="text-muted ms-2" style="font-size: 0.68rem;">
                                    ${formatTimeAgo(item.created_at)}
                                </small>
                            </div>
                            <p class="text-muted small mb-1" style="font-size: 0.78rem; line-height: 1.35;">
                                ${escapeHtml(item.message)}
                            </p>
                            <div class="d-flex align-items-center justify-content-between small text-muted" style="font-size: 0.72rem;">
                                <span><i class="fas fa-barcode me-1"></i><code>${escapeHtml(item.trx_number)}</code></span>
                                <span class="text-primary fw-semibold"><i class="fas fa-eye me-1"></i>Buka Pop-up</span>
                            </div>
                        </div>
                    </div>
                `;

                // On click: Open rich popup modal
                el.addEventListener('click', function() {
                    if (typeof window.showTransactionPopup === 'function') {
                        window.showTransactionPopup(item);
                    }
                });

                list.appendChild(el);
            });
        }
    }

    // Format Relative Time (e.g. 5m lalu, baru saja)
    function formatTimeAgo(dateStr) {
        if (!dateStr) return 'Baru saja';
        const d = new Date(dateStr.replace(/-/g, '/'));
        const now = new Date();
        const diffSec = Math.floor((now - d) / 1000);

        if (diffSec < 45) return 'Baru saja';
        if (diffSec < 3600) return Math.floor(diffSec / 60) + 'm lalu';
        if (diffSec < 86400) return Math.floor(diffSec / 3600) + 'j lalu';
        return Math.floor(diffSec / 86400) + 'h lalu';
    }

    // Escape HTML Helper
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Main Polling Loop
    function pollServer() {
        const baseUrl = window.SMART_BASE_URL || '';
        const url = `${baseUrl}/transaksi/pollNotifications?since_id=${lastNotifId}`;

        fetch(url, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (Array.isArray(data.items) && data.items.length > 0) {
                        data.items.forEach(item => {
                            const itemId = parseInt(item.id, 10);
                            if (itemId > lastNotifId) {
                                lastNotifId = itemId;
                            }

                            // If not the very first page load initialization, pop toast and ring chime!
                            if (isInitialized) {
                                playNotificationChime();
                                showLiveToast(item);
                            }
                        });

                        // Dispatch global window event for live table updates
                        window.dispatchEvent(new CustomEvent('smartwarehouse:transaction', { detail: data.items }));
                    }

                    // Refresh dropdown list data
                    fetchRecentNotifications();
                    isInitialized = true;
                }
            })
            .catch(() => {
                // Resilient failure
            });
    }

    // Fetch Recent Notifications for Dropdown
    function fetchRecentNotifications() {
        const baseUrl = window.SMART_BASE_URL || '';
        fetch(`${baseUrl}/transaksi/getNotifications?limit=10`, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateDropdownUi(data.items, data.unread_count);
                }
            })
            .catch(() => {});
    }

    // Mark All As Read
    function markAllAsRead() {
        const baseUrl = window.SMART_BASE_URL || '';
        fetch(`${baseUrl}/transaksi/markNotificationsRead`, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const badge = document.getElementById('notifBadgeCount');
                    if (badge) badge.classList.add('d-none');
                    fetchRecentNotifications();
                }
            })
            .catch(() => {});
    }

    // Initialize on DOM Ready
    document.addEventListener('DOMContentLoaded', function() {
        // Wire up Base URL if not set
        if (!window.SMART_BASE_URL) {
            const link = document.querySelector('a[href*="/home"]') || document.querySelector('a[href*="/transaksi"]');
            if (link) {
                const href = link.getAttribute('href');
                const idx = href.indexOf('/05092026/SmartWarehouse');
                if (idx !== -1) {
                    window.SMART_BASE_URL = '/05092026/SmartWarehouse';
                }
            }
            if (!window.SMART_BASE_URL) {
                window.SMART_BASE_URL = window.location.origin + '/05092026/SmartWarehouse';
            }
        }

        // Setup Mark As Read Button
        const btnMarkRead = document.getElementById('btnMarkAllNotifsRead');
        if (btnMarkRead) {
            btnMarkRead.addEventListener('click', function(e) {
                e.preventDefault();
                markAllAsRead();
            });
        }

        // Unlock audio context on any user click
        document.addEventListener('click', function() {
            getAudioContext();
        }, { once: true });

        // Request browser desktop notification permission if supported
        if ('Notification' in window && Notification.permission === 'default') {
            setTimeout(() => {
                Notification.requestPermission();
            }, 3000);
        }

        // First initial check
        pollServer();

        // Start 3-second heartbeat polling
        pollInterval = setInterval(pollServer, 2800);
    });

})();
