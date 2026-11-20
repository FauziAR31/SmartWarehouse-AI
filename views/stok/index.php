<?php
// views/stok/index.php
// Variables: $stockData, $totalRows, $totalPages, $page, $kategoriList, $lokasiList
// Filters: $search, $kategori_id, $lokasi_id, $status
?>

<div class="page-header-responsive mb-4">
    <div>
        <h1 class="h3 fw-bold text-dark mb-1">
            <i class="fas fa-cubes text-primary me-2"></i>Stok Barang
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>" class="text-decoration-none">Dashboard</a></li>
                <li class="breadcrumb-item">Inventory</li>
                <li class="breadcrumb-item active">Stok Barang</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-primary fs-6 px-3 py-2 shadow-sm">
            <i class="fas fa-layer-group me-1"></i>Total: <?php echo $totalRows; ?> Barang
        </span>
    </div>
</div>

<!-- Summary Cards -->
<?php
$amanCount = 0; $minCount = 0; $habisCount = 0;
foreach ($stockData as $row) {
    if ($row['total_qty'] == 0) $habisCount++;
    elseif ($row['total_qty'] <= $row['min_stock']) $minCount++;
    else $amanCount++;
}
?>
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 bg-success text-white">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fas fa-check-circle fs-4"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?php echo $amanCount; ?></div>
                    <small class="opacity-75">Stok Aman</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 bg-warning text-dark">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fas fa-exclamation-triangle fs-4"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?php echo $minCount; ?></div>
                    <small class="opacity-75 text-dark">Stok Minimum</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card border-0 shadow-sm rounded-4 bg-danger text-white">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="bg-white bg-opacity-25 rounded-3 p-2"><i class="fas fa-times-circle fs-4"></i></div>
                <div>
                    <div class="fw-bold fs-4"><?php echo $habisCount; ?></div>
                    <small class="opacity-75">Stok Habis</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="<?php echo BASE_URL; ?>/stok/index" class="row g-2 align-items-end form-filter-row">
            <div class="col-12 col-md-4">
                <label class="form-label fw-semibold small text-muted">Cari Barang</label>
                <div class="input-group">
                    <span class="input-group-text bg-primary text-white border-0"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="Kode atau nama barang..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
            </div>
            <div class="col-12 col-sm-4 col-md-2">
                <label class="form-label fw-semibold small text-muted">Kategori</label>
                <select class="form-select" name="kategori_id">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategoriList as $kat): ?>
                        <option value="<?php echo $kat['id']; ?>" <?php echo ($kategori_id == $kat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($kat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-4 col-md-2">
                <label class="form-label fw-semibold small text-muted">Lokasi</label>
                <select class="form-select" name="lokasi_id">
                    <option value="">Semua Lokasi</option>
                    <?php foreach ($lokasiList as $lok): ?>
                        <option value="<?php echo $lok['id']; ?>" <?php echo ($lokasi_id == $lok['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($lok['kode']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-sm-4 col-md-2">
                <label class="form-label fw-semibold small text-muted">Status</label>
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="AMAN" <?php echo ($status === 'AMAN') ? 'selected' : ''; ?>>✅ AMAN</option>
                    <option value="MINIMUM" <?php echo ($status === 'MINIMUM') ? 'selected' : ''; ?>>⚠️ MINIMUM</option>
                    <option value="HABIS" <?php echo ($status === 'HABIS') ? 'selected' : ''; ?>>❌ HABIS</option>
                </select>
            </div>
            <div class="col-12 col-md-2">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="<?php echo BASE_URL; ?>/stok/index" class="btn btn-outline-secondary" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Data Table -->
<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-0">
        <style>
            #stokTable { table-layout: fixed; min-width: 950px; }
            #stokTable colgroup col.col-no    { width: 48px; }
            #stokTable colgroup col.col-kode  { width: 130px; }
            #stokTable colgroup col.col-nama  { width: auto; }
            #stokTable colgroup col.col-kat   { width: 110px; }
            #stokTable colgroup col.col-qty   { width: 90px; }
            #stokTable colgroup col.col-min   { width: 80px; }
            #stokTable colgroup col.col-sat   { width: 72px; }
            #stokTable colgroup col.col-stat  { width: 100px; }
            #stokTable colgroup col.col-loksi { width: 90px; }
            #stokTable colgroup col.col-edit  { width: 90px; }
            #stokTable td, #stokTable th { vertical-align: middle; }
            #stokTable td.col-kode-td  { white-space: nowrap; }
            #stokTable td.col-nama-td  { word-break: break-word; }
            #stokTable td.col-short    { white-space: nowrap; text-align: center; }
        </style>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="stokTable">
                <colgroup>
                    <col class="col-no">
                    <col class="col-kode">
                    <col class="col-nama">
                    <col class="col-kat">
                    <col class="col-qty">
                    <col class="col-min">
                    <col class="col-sat">
                    <col class="col-stat">
                    <col class="col-loksi">
                    <col class="col-edit">
                </colgroup>
                <thead class="table-dark">
                    <tr>
                        <th class="ps-3" style="white-space:nowrap">#</th>
                        <th style="white-space:nowrap">Kode Barang</th>
                        <th>Nama Barang</th>
                        <th style="white-space:nowrap">Kategori</th>
                        <th class="text-center" style="white-space:nowrap">Total Stok</th>
                        <th class="text-center" style="white-space:nowrap">Min. Stok</th>
                        <th class="text-center" style="white-space:nowrap">Satuan</th>
                        <th class="text-center" style="white-space:nowrap">Status</th>
                        <th class="text-center" style="white-space:nowrap">Lokasi</th>
                        <th class="text-center" style="white-space:nowrap">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stockData)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="fas fa-box-open fs-1 mb-3 d-block opacity-25"></i>
                                    <p class="mb-0">Tidak ada data stok ditemukan.</p>
                                    <?php if ($search || $kategori_id || $lokasi_id || $status): ?>
                                        <a href="<?php echo BASE_URL; ?>/stok/index" class="btn btn-sm btn-outline-secondary mt-2">
                                            <i class="fas fa-undo me-1"></i>Reset Filter
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stockData as $i => $row): ?>
                            <?php
                            $qty = (int)$row['total_qty'];
                            $minStock = (int)$row['min_stock'];
                            if ($qty === 0) {
                                $statusClass = 'danger';
                                $statusText  = 'HABIS';
                                $statusIcon  = 'times-circle';
                            } elseif ($qty <= $minStock) {
                                $statusClass = 'warning';
                                $statusText  = 'MINIMUM';
                                $statusIcon  = 'exclamation-triangle';
                            } else {
                                $statusClass = 'success';
                                $statusText  = 'AMAN';
                                $statusIcon  = 'check-circle';
                            }
                            ?>
                            <tr class="<?php echo ($qty === 0) ? 'table-danger' : (($qty <= $minStock) ? 'table-warning' : ''); ?>">
                                <td class="ps-3 text-muted" style="white-space:nowrap"><?php echo $offset + $i + 1; ?></td>
                                <td class="col-kode-td">
                                    <code class="text-primary fw-bold"><?php echo htmlspecialchars($row['code']); ?></code>
                                </td>
                                <td class="col-nama-td">
                                    <div class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></div>
                                </td>
                                <td style="white-space:nowrap">
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                        <?php echo htmlspecialchars($row['kategori_name']); ?>
                                    </span>
                                </td>
                                <td class="col-short">
                                    <span class="fw-bold fs-5 text-<?php echo $statusClass; ?>">
                                        <?php echo number_format($qty); ?>
                                    </span>
                                </td>
                                <td class="col-short">
                                    <span class="text-muted"><?php echo number_format($minStock); ?></span>
                                </td>
                                <td class="col-short">
                                    <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['unit']); ?></span>
                                </td>
                                <td class="col-short">
                                    <span class="badge bg-<?php echo $statusClass; ?> px-2 py-1" style="white-space:nowrap">
                                        <i class="fas fa-<?php echo $statusIcon; ?> me-1"></i>
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="col-short">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                            onclick="lihatLokasi(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>')"
                                            title="Lihat detail per lokasi"
                                            style="white-space:nowrap">
                                        <i class="fas fa-map-marker-alt me-1"></i>Detail
                                    </button>
                                </td>
                                <td class="col-short">
                                    <button type="button"
                                            class="btn btn-sm btn-warning rounded-pill px-3"
                                            onclick="editStok(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['name'])); ?>', '<?php echo htmlspecialchars(addslashes($row['unit'])); ?>')"
                                            title="Edit / Sesuaikan Stok"
                                            style="white-space:nowrap">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="d-flex flex-wrap align-items-center justify-content-start gap-3 px-4 py-3 border-top bg-light rounded-bottom-4">
                <nav aria-label="Pagination">
                    <ul class="pagination pagination-sm mb-0 gap-1">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link rounded-2" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage   = min($totalPages, $page + 2);
                        for ($p = $startPage; $p <= $endPage; $p++): ?>
                            <li class="page-item <?php echo ($p === $page) ? 'active' : ''; ?>">
                                <a class="page-link rounded-2" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $p])); ?>">
                                    <?php echo $p; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link rounded-2" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <small class="text-muted border-start ps-3">
                    Menampilkan <strong><?php echo ($offset + 1); ?>–<?php echo min($offset + $limit, $totalRows); ?></strong> dari <strong><?php echo number_format($totalRows); ?></strong> barang
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detail Lokasi -->
<div class="modal fade" id="modalDetailLokasi" tabindex="-1" aria-labelledby="modalDetailLokasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header bg-primary text-white rounded-top-4 border-0">
                <h5 class="modal-title fw-bold" id="modalDetailLokasiLabel">
                    <i class="fas fa-map-marker-alt me-2"></i>Detail Stok per Lokasi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="modalBarangName" class="mb-3"></div>
                <div id="modalContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="mt-2 text-muted">Memuat data...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<!-- ===== Modal Edit / Sesuaikan Stok ===== -->
<div class="modal fade" id="modalEditStok" tabindex="-1" aria-labelledby="modalEditStokLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <!-- Header -->
            <div class="modal-header border-0 rounded-top-4 text-white" id="editModalHeader" style="background: linear-gradient(135deg,#f59e0b,#d97706);">
                <h5 class="modal-title fw-bold" id="modalEditStokLabel">
                    <i class="fas fa-edit me-2"></i>Sesuaikan Stok Barang
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <!-- Body -->
            <div class="modal-body p-4">
                <!-- Info Barang -->
                <div id="editBarangInfo" class="alert alert-warning border-0 d-flex align-items-center gap-2 mb-4" style="background:#fef3c7;">
                    <i class="fas fa-box text-warning fs-5"></i>
                    <div>
                        <div class="fw-bold" id="editBarangName">–</div>
                        <small class="text-muted">Satuan: <span id="editBarangUnit">–</span></small>
                    </div>
                </div>

                <!-- Mode Toggle -->
                <div class="mb-3">
                    <label class="form-label fw-semibold">Jenis Penyesuaian</label>
                    <div class="d-flex gap-2">
                        <button type="button" id="btnModeTambah"
                                class="btn btn-success flex-fill fw-semibold"
                                onclick="setMode('tambah')">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Stok
                        </button>
                        <button type="button" id="btnModeKurang"
                                class="btn btn-outline-danger flex-fill fw-semibold"
                                onclick="setMode('kurang')">
                            <i class="fas fa-minus-circle me-1"></i>Kurangi Stok
                        </button>
                    </div>
                </div>

                <!-- Pilih Lokasi -->
                <div class="mb-3">
                    <label for="editLokasi" class="form-label fw-semibold">Pilih Lokasi</label>
                    <select class="form-select" id="editLokasi" onchange="updateCurrentQty()">
                        <option value="">-- Pilih Lokasi --</option>
                    </select>
                    <div class="mt-2" id="currentQtyInfo" style="display:none">
                        <small class="text-muted">Stok saat ini di lokasi ini: 
                            <span class="fw-bold text-primary" id="currentQtyVal">0</span>
                        </small>
                    </div>
                </div>

                <!-- Jumlah -->
                <div class="mb-3">
                    <label for="editQty" class="form-label fw-semibold">Jumlah <span id="editQtyLabel" class="text-success">(Tambah)</span></label>
                    <div class="input-group">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustQtyInput(-1)"><i class="fas fa-minus"></i></button>
                        <input type="number" class="form-control text-center fw-bold fs-5" id="editQty"
                               min="1" value="1" placeholder="Masukkan jumlah">
                        <button class="btn btn-outline-secondary" type="button" onclick="adjustQtyInput(1)"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="qtyWarning" class="text-danger small mt-1" style="display:none">
                        <i class="fas fa-exclamation-triangle me-1"></i><span></span>
                    </div>
                </div>

                <!-- Keterangan -->
                <div class="mb-1">
                    <label for="editKeterangan" class="form-label fw-semibold">Keterangan <small class="text-muted fw-normal">(opsional)</small></label>
                    <textarea class="form-control" id="editKeterangan" rows="2"
                              placeholder="Contoh: Penyesuaian hasil opname, retur, dll..."></textarea>
                </div>
            </div>
            <!-- Footer -->
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Batal
                </button>
                <button type="button" class="btn btn-warning fw-semibold" id="btnSimpanStok" onclick="simpanAdjustStok()">
                    <i class="fas fa-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index:9999">
    <div id="toastStok" class="toast align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body fw-semibold" id="toastStokMsg">–</div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const CSRF_TOKEN = '<?php echo SecurityHelper::csrfToken(); ?>';

let _editBarangId = null;
let _editMode     = 'tambah';
let _lokasiData   = [];

// ---------- Detail Lokasi ----------
function lihatLokasi(barangId, barangName) {
    const modal   = new bootstrap.Modal(document.getElementById('modalDetailLokasi'));
    const content = document.getElementById('modalContent');
    const namDiv  = document.getElementById('modalBarangName');

    namDiv.innerHTML = `<div class="alert alert-primary border-0 py-2">
        <i class="fas fa-box me-2"></i><strong>${barangName}</strong></div>`;
    content.innerHTML = `<div class="text-center py-4">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 text-muted">Memuat data lokasi...</p></div>`;
    modal.show();

    fetch(BASE_URL + '/stok/apiDetailLokasi?barang_id=' + barangId)
        .then(r => r.json())
        .then(json => {
            if (json.success && json.data.length > 0) {
                const rows = json.data.map((loc, i) => `
                    <tr>
                        <td class="ps-3">${i + 1}</td>
                        <td><code class="text-primary fw-bold">${loc.kode}</code></td>
                        <td>${loc.nama}</td>
                        <td class="text-center"><span class="badge bg-success fs-6 px-3">${Number(loc.qty).toLocaleString()}</span></td>
                    </tr>`).join('');
                const total = json.data.reduce((s, l) => s + parseInt(l.qty), 0);
                content.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr><th class="ps-3">#</th><th>Kode Lokasi</th><th>Nama Lokasi</th><th class="text-center">Qty</th></tr>
                            </thead>
                            <tbody>${rows}</tbody>
                            <tfoot class="table-light fw-bold">
                                <tr><td colspan="3" class="ps-3 text-end">Total:</td>
                                    <td class="text-center"><span class="badge bg-primary fs-6 px-3">${total.toLocaleString()}</span></td></tr>
                            </tfoot>
                        </table>
                    </div>`;
            } else {
                content.innerHTML = `<div class="text-center py-4 text-muted">
                    <i class="fas fa-box-open fs-1 mb-3 d-block opacity-25"></i>
                    <p>Tidak ada stok tersedia di semua lokasi untuk barang ini.</p></div>`;
            }
        })
        .catch(() => {
            content.innerHTML = `<div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>Gagal memuat data. Coba lagi.</div>`;
        });
}

// ---------- Edit Stok ----------
function editStok(barangId, barangName, barangUnit) {
    _editBarangId = barangId;
    _editMode     = 'tambah';
    document.getElementById('editBarangName').textContent = barangName;
    document.getElementById('editBarangUnit').textContent = barangUnit;
    document.getElementById('editQty').value  = 1;
    document.getElementById('editKeterangan').value = '';
    document.getElementById('qtyWarning').style.display = 'none';
    document.getElementById('currentQtyInfo').style.display = 'none';
    setMode('tambah');

    // Load lokasi
    const sel = document.getElementById('editLokasi');
    sel.innerHTML = '<option value="">Memuat lokasi...</option>';
    fetch(BASE_URL + '/stok/apiGetLokasiBarang?barang_id=' + barangId)
        .then(r => r.json())
        .then(json => {
            _lokasiData = json.data || [];
            sel.innerHTML = '<option value="">-- Pilih Lokasi --</option>' +
                _lokasiData.map(l =>
                    `<option value="${l.lokasi_id}" data-qty="${l.qty}">${l.kode} – ${l.nama} (Stok: ${l.qty})</option>`
                ).join('');
        })
        .catch(() => {
            sel.innerHTML = '<option value="">Gagal memuat lokasi</option>';
        });

    const modal = new bootstrap.Modal(document.getElementById('modalEditStok'));
    modal.show();
}

function setMode(mode) {
    _editMode = mode;
    const btnT = document.getElementById('btnModeTambah');
    const btnK = document.getElementById('btnModeKurang');
    const lbl  = document.getElementById('editQtyLabel');
    const hdr  = document.getElementById('editModalHeader');
    const btn  = document.getElementById('btnSimpanStok');
    if (mode === 'tambah') {
        btnT.className = 'btn btn-success flex-fill fw-semibold';
        btnK.className = 'btn btn-outline-danger flex-fill fw-semibold';
        lbl.textContent = '(Tambah)';
        lbl.className = 'text-success';
        hdr.style.background = 'linear-gradient(135deg,#f59e0b,#d97706)';
        btn.className = 'btn btn-warning fw-semibold';
        btn.innerHTML = '<i class="fas fa-plus-circle me-1"></i>Tambah Stok';
    } else {
        btnT.className = 'btn btn-outline-success flex-fill fw-semibold';
        btnK.className = 'btn btn-danger flex-fill fw-semibold';
        lbl.textContent = '(Kurang)';
        lbl.className = 'text-danger';
        hdr.style.background = 'linear-gradient(135deg,#ef4444,#dc2626)';
        btn.className = 'btn btn-danger fw-semibold';
        btn.innerHTML = '<i class="fas fa-minus-circle me-1"></i>Kurangi Stok';
    }
    updateCurrentQty();
}

function updateCurrentQty() {
    const sel  = document.getElementById('editLokasi');
    const info = document.getElementById('currentQtyInfo');
    const val  = document.getElementById('currentQtyVal');
    const warn = document.getElementById('qtyWarning');
    const opt  = sel.options[sel.selectedIndex];
    warn.style.display = 'none';
    if (opt && opt.value) {
        const qty = parseInt(opt.getAttribute('data-qty')) || 0;
        val.textContent = qty.toLocaleString();
        info.style.display = 'block';
        if (_editMode === 'kurang' && qty === 0) {
            warn.querySelector('span').textContent = 'Stok di lokasi ini sudah 0, tidak bisa dikurangi.';
            warn.style.display = 'block';
        }
    } else {
        info.style.display = 'none';
    }
}

function adjustQtyInput(delta) {
    const inp = document.getElementById('editQty');
    const v   = parseInt(inp.value) || 0;
    inp.value = Math.max(1, v + delta);
}

function simpanAdjustStok() {
    const lokasiId   = document.getElementById('editLokasi').value;
    const qty        = parseInt(document.getElementById('editQty').value);
    const keterangan = document.getElementById('editKeterangan').value.trim();
    const warn       = document.getElementById('qtyWarning');
    warn.style.display = 'none';

    if (!lokasiId) { showWarn(warn, 'Pilih lokasi terlebih dahulu.'); return; }
    if (!qty || qty < 1) { showWarn(warn, 'Jumlah harus minimal 1.'); return; }

    if (_editMode === 'kurang') {
        const sel = document.getElementById('editLokasi');
        const opt = sel.options[sel.selectedIndex];
        const curQty = parseInt(opt.getAttribute('data-qty')) || 0;
        if (qty > curQty) {
            showWarn(warn, `Jumlah melebihi stok saat ini (${curQty}).`); return;
        }
    }

    const btn = document.getElementById('btnSimpanStok');
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Menyimpan...';

    const body = new URLSearchParams({
        csrf_token:  CSRF_TOKEN,
        barang_id:   _editBarangId,
        lokasi_id:   lokasiId,
        qty:         qty,
        mode:        _editMode,
        keterangan:  keterangan
    });

    fetch(BASE_URL + '/stok/apiAdjustStok', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString()
    })
    .then(r => r.json())
    .then(json => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        if (json.success) {
            // Update qty di dropdown option
            const sel = document.getElementById('editLokasi');
            const opt = sel.options[sel.selectedIndex];
            if (opt) {
                opt.setAttribute('data-qty', json.new_qty);
                opt.text = opt.text.replace(/\(Stok: \d+\)/, `(Stok: ${json.new_qty})`);
            }
            updateCurrentQty();
            bootstrap.Modal.getInstance(document.getElementById('modalEditStok')).hide();
            showToast('success', '<i class="fas fa-check-circle me-2"></i>' + json.message);
            // Reload tabel setelah 1.5 detik
            setTimeout(() => location.reload(), 1800);
        } else {
            showWarn(warn, json.message || 'Terjadi kesalahan.');
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        showWarn(warn, 'Gagal terhubung ke server. Coba lagi.');
    });
}

function showWarn(el, msg) {
    el.querySelector('span').textContent = msg;
    el.style.display = 'block';
}

function showToast(type, msg) {
    const toast = document.getElementById('toastStok');
    const body  = document.getElementById('toastStokMsg');
    toast.className = 'toast align-items-center border-0 shadow text-white bg-' + (type === 'success' ? 'success' : 'danger');
    body.innerHTML  = msg;
    new bootstrap.Toast(toast, { delay: 4000 }).show();
}
</script>
