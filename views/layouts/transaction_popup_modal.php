<?php
// views/layouts/transaction_popup_modal.php
$flashModalData = $_SESSION['transaction_success_modal'] ?? null;
if ($flashModalData) {
    unset($_SESSION['transaction_success_modal']);
}
?>

<!-- Modal Bukti Transaksi Real-Time & Sukses -->
<div class="modal fade" id="modalTransactionSuccess" tabindex="-1" aria-labelledby="modalTransactionSuccessLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header -->
            <div class="modal-header border-0 text-white p-4" id="modalTrxHeader" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-white text-success d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px;">
                            <i class="fas fa-check-circle fs-4" id="modalTrxHeaderIcon"></i>
                        </div>
                        <div>
                            <span class="badge bg-white bg-opacity-25 rounded-pill px-2 py-1 small text-uppercase" id="modalTrxTypeBadge" style="letter-spacing: 0.5px;">
                                TRANSAKSI SUKSES
                            </span>
                            <h5 class="modal-title fw-bold text-white mb-0" id="modalTrxTitle">Transaksi Berhasil!</h5>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-light bg-opacity-50">
                <!-- Transaction Number & Copy Box -->
                <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 border mb-3 shadow-sm">
                    <div>
                        <span class="text-muted d-block small" style="font-size: 0.72rem;">Nomor Transaksi Resmi:</span>
                        <strong class="font-monospace text-dark fs-6" id="modalTrxNumber">TRX-000000</strong>
                    </div>
                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3 py-1" type="button" id="btnCopyTrxNumber" title="Salin Nomor Transaksi">
                        <i class="fas fa-copy me-1"></i><span id="copyTrxText">Salin</span>
                    </button>
                </div>

                <!-- Receipt Detail Card -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-3">
                    <div class="card-body p-3">
                        <!-- Material Name & Qty Highlight -->
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom mb-3">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Nama Material / Barang:</small>
                                <h6 class="fw-bold text-dark mb-0" id="modalTrxMaterial">Nama Barang</h6>
                                <small class="text-muted font-monospace" id="modalTrxMaterialCode">KODE-001</small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block" style="font-size: 0.72rem;">Jumlah Mutasi:</small>
                                <span class="badge bg-success rounded-pill px-3 py-2 fs-6 fw-bold shadow-sm" id="modalTrxQtyBadge">
                                    +0 unit
                                </span>
                            </div>
                        </div>

                        <!-- Locations & Partner Details -->
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Lokasi / Rak Asal:</span>
                                <strong class="text-dark" id="modalTrxLokasiAsal">-</strong>
                            </div>
                            <div class="col-6">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Lokasi / Rak Tujuan:</span>
                                <strong class="text-dark" id="modalTrxLokasiTujuan">-</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted d-block" id="modalTrxPartnerLabel" style="font-size: 0.72rem;">Supplier / Penerima:</span>
                                <strong class="text-dark" id="modalTrxPartnerName">-</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Petugas Operator:</span>
                                <strong class="text-dark" id="modalTrxOperator">-</strong>
                            </div>
                            <div class="col-12 mt-2" id="modalTrxNotesRow">
                                <span class="text-muted d-block" style="font-size: 0.72rem;">Catatan Transaksi:</span>
                                <span class="text-secondary" id="modalTrxNotes">-</span>
                            </div>
                            <div class="col-12 mt-2 pt-2 border-top d-flex align-items-center justify-content-between text-muted" style="font-size: 0.75rem;">
                                <span><i class="fas fa-clock me-1"></i><span id="modalTrxTime"><?php echo date('d/m/Y H:i'); ?> WIB</span></span>
                                <span><i class="fas fa-shield-alt text-success me-1"></i>Dokumen Sah Sistem</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- WhatsApp Notification Badge Indicator -->
                <div class="p-3 rounded-3 border border-success-subtle bg-success bg-opacity-10 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fab fa-whatsapp text-success fs-5"></i>
                        <div>
                            <strong class="text-dark small d-block" style="font-size: 0.82rem;">Notifikasi WhatsApp Real-Time</strong>
                            <small class="text-muted" style="font-size: 0.72rem;" id="modalTrxWaStatus">Rincian transaksi otomatis dikirim ke nomor terdaftar.</small>
                        </div>
                    </div>
                    <span class="badge bg-success rounded-pill px-2 py-1 small">
                        <i class="fas fa-check me-1"></i>Terkirim
                    </span>
                </div>
            </div>

            <!-- Modal Footer with Action Buttons -->
            <div class="modal-footer border-0 p-3 bg-white d-flex align-items-center justify-content-between">
                <a href="<?php echo BASE_URL; ?>/riwayat/index" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="fas fa-history me-1"></i>Lihat Riwayat
                </a>
                <div class="d-flex gap-2">
                    <a href="#" target="_blank" class="btn btn-success btn-sm rounded-pill px-3 fw-semibold shadow-sm" id="btnShareWaModal">
                        <i class="fab fa-whatsapp me-1"></i>Bagikan Bukti WA
                    </a>
                    <button type="button" class="btn btn-primary btn-sm rounded-pill px-4 fw-semibold shadow-sm" data-bs-dismiss="modal">
                        <i class="fas fa-check me-1"></i>Selesai
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Global function to trigger transaction pop-up modal anywhere
window.showTransactionPopup = function(data) {
    if (!data) return;

    const modalEl = document.getElementById('modalTransactionSuccess');
    if (!modalEl) return;

    const header = document.getElementById('modalTrxHeader');
    const headerIcon = document.getElementById('modalTrxHeaderIcon');
    const typeBadge = document.getElementById('modalTrxTypeBadge');
    const title = document.getElementById('modalTrxTitle');
    const trxNumber = document.getElementById('modalTrxNumber');
    const material = document.getElementById('modalTrxMaterial');
    const materialCode = document.getElementById('modalTrxMaterialCode');
    const qtyBadge = document.getElementById('modalTrxQtyBadge');
    const lokasiAsal = document.getElementById('modalTrxLokasiAsal');
    const lokasiTujuan = document.getElementById('modalTrxLokasiTujuan');
    const partnerLabel = document.getElementById('modalTrxPartnerLabel');
    const partnerName = document.getElementById('modalTrxPartnerName');
    const operator = document.getElementById('modalTrxOperator');
    const notes = document.getElementById('modalTrxNotes');
    const notesRow = document.getElementById('modalTrxNotesRow');
    const time = document.getElementById('modalTrxTime');
    const btnShareWa = document.getElementById('btnShareWaModal');

    const type = data.type || data.trx_type || 'in';
    const num = data.trx_number || data.nomorTransaksi || '-';
    const matName = data.barang_name || data.title || 'Material';
    const matCode = data.barang_code ? `(${data.barang_code})` : '';
    const qVal = Number(data.qty || 1).toLocaleString('id-ID');
    const unit = data.unit || 'unit';

    if (trxNumber) trxNumber.textContent = num;
    if (material) material.textContent = matName;
    if (materialCode) materialCode.textContent = matCode;
    if (time) time.textContent = data.time || (new Date().toLocaleString('id-ID') + ' WIB');
    if (operator) operator.textContent = data.operator || data.operator_name || 'Petugas Gudang';

    if (notes && notesRow) {
        if (data.notes && data.notes !== '-') {
            notes.textContent = data.notes;
            notesRow.classList.remove('d-none');
        } else {
            notesRow.classList.add('d-none');
        }
    }

    // Dynamic style based on transaction type
    if (type === 'in') {
        if (header) header.style.background = 'linear-gradient(135deg, #059669 0%, #10b981 100%)';
        if (headerIcon) { headerIcon.className = 'fas fa-arrow-circle-down fs-4 text-success'; }
        if (typeBadge) typeBadge.textContent = 'BARANG MASUK (INBOUND)';
        if (title) title.textContent = 'Barang Masuk Berhasil!';
        if (qtyBadge) {
            qtyBadge.className = 'badge bg-success rounded-pill px-3 py-2 fs-6 fw-bold shadow-sm';
            qtyBadge.textContent = `+${qVal} ${unit}`;
        }
        if (lokasiAsal) lokasiAsal.textContent = 'Pemasok / Eksternal';
        if (lokasiTujuan) lokasiTujuan.textContent = data.lokasi_tujuan || data.lokasi_info || '-';
        if (partnerLabel) partnerLabel.textContent = 'Supplier / Vendor:';
        if (partnerName) partnerName.textContent = data.partner_name || data.supplier_name || 'Supplier Resmi';
    } else if (type === 'out') {
        if (header) header.style.background = 'linear-gradient(135deg, #dc2626 0%, #ef4444 100%)';
        if (headerIcon) { headerIcon.className = 'fas fa-arrow-circle-up fs-4 text-danger'; }
        if (typeBadge) typeBadge.textContent = 'BARANG KELUAR (OUTBOUND)';
        if (title) title.textContent = 'Barang Keluar Berhasil!';
        if (qtyBadge) {
            qtyBadge.className = 'badge bg-danger rounded-pill px-3 py-2 fs-6 fw-bold shadow-sm';
            qtyBadge.textContent = `-${qVal} ${unit}`;
        }
        if (lokasiAsal) lokasiAsal.textContent = data.lokasi_asal || data.lokasi_info || '-';
        if (lokasiTujuan) lokasiTujuan.textContent = 'Penerima / Eksternal';
        if (partnerLabel) partnerLabel.textContent = 'Penerima / Divisi:';
        if (partnerName) partnerName.textContent = data.partner_name || data.recipient_name || 'Penerima Umum';
    } else if (type === 'transfer') {
        if (header) header.style.background = 'linear-gradient(135deg, #d97706 0%, #f59e0b 100%)';
        if (headerIcon) { headerIcon.className = 'fas fa-exchange-alt fs-4 text-warning'; }
        if (typeBadge) typeBadge.textContent = 'PEMINDAHAN RAK (TRANSFER)';
        if (title) title.textContent = 'Pemindahan Barang Berhasil!';
        if (qtyBadge) {
            qtyBadge.className = 'badge bg-warning text-dark rounded-pill px-3 py-2 fs-6 fw-bold shadow-sm';
            qtyBadge.textContent = `${qVal} ${unit}`;
        }
        if (lokasiAsal) lokasiAsal.textContent = data.lokasi_asal || (data.lokasi_info ? data.lokasi_info.split('➔')[0].trim() : '-');
        if (lokasiTujuan) lokasiTujuan.textContent = data.lokasi_tujuan || (data.lokasi_info ? (data.lokasi_info.split('➔')[1] || '').trim() : '-');
        if (partnerLabel) partnerLabel.textContent = 'Kategori Mutasi:';
        if (partnerName) partnerName.textContent = 'Mutasi Internal Antar-Rak';
    }

    // Generate WhatsApp direct share text
    const shareText = `📋 *BUKTI RESMI TRANSAKSI GUDANG* 📋\nSmartWarehouse Management\n----------------------------------------\n📄 *No. Transaksi:* \`${num}\`\n🏷️ *Material:* ${matName} ${matCode}\n📊 *Jumlah:* ${qVal} ${unit}\n📍 *Lokasi:* ${data.lokasi_info || (data.lokasi_asal + ' ➔ ' + data.lokasi_tujuan)}\n👤 *Operator:* ${data.operator || data.operator_name || 'Petugas'}\n⏰ *Waktu:* ${data.time || (new Date().toLocaleString('id-ID') + ' WIB')}\n----------------------------------------\n_Dokumen resmi digital SmartWarehouse_`;
    if (btnShareWa) {
        btnShareWa.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(shareText);
    }

    // Trigger bootstrap modal
    const bsModal = new bootstrap.Modal(modalEl);
    bsModal.show();
};

document.addEventListener('DOMContentLoaded', function() {
    // Copy Transaction Number button
    const btnCopy = document.getElementById('btnCopyTrxNumber');
    const trxNumber = document.getElementById('modalTrxNumber');
    const copyText = document.getElementById('copyTrxText');
    if (btnCopy && trxNumber) {
        btnCopy.addEventListener('click', function() {
            navigator.clipboard.writeText(trxNumber.textContent.trim()).then(() => {
                if (copyText) copyText.textContent = 'Tersalin!';
                btnCopy.classList.replace('btn-outline-secondary', 'btn-success');
                setTimeout(() => {
                    if (copyText) copyText.textContent = 'Salin';
                    btnCopy.classList.replace('btn-success', 'btn-outline-secondary');
                }, 2000);
            });
        });
    }

    // Check if flash modal data exists from PHP session
    <?php if ($flashModalData): ?>
    const flashData = <?php echo json_encode($flashModalData); ?>;
    if (flashData) {
        window.showTransactionPopup(flashData);
    }
    <?php endif; ?>
});
</script>
