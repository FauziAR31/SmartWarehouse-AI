<div class="page-header-responsive mb-4">
    <h2 class="fw-bold text-dark mb-0"><?php echo isset($barang) ? 'Edit Barang' : 'Tambah Barang'; ?></h2>
    <a href="<?php echo BASE_URL; ?>/barang/index" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Kembali</a>
</div>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-3 p-md-4">
        <?php if (!isset($barang)): ?>
            <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex align-items-center justify-content-between p-3 mb-4 rounded-3">
                <div class="d-flex align-items-center">
                    <i class="fas fa-file-csv fa-2x text-info me-3"></i>
                    <div>
                        <div class="fw-bold text-dark">Punya banyak data barang?</div>
                        <small class="text-muted">Gunakan fitur Import CSV untuk menambahkan puluhan hingga ribuan data barang sekaligus dari file CSV.</small>
                    </div>
                </div>
                <a href="<?php echo BASE_URL; ?>/barang/index" class="btn btn-sm btn-info text-white shadow-sm">
                    <i class="fas fa-upload me-1"></i>Ke Menu Import CSV
                </a>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo BASE_URL; ?>/barang/<?php echo isset($barang) ? 'update/'.$barang['id'] : 'store'; ?>" id="barangForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo SecurityHelper::csrfToken(); ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="code" class="form-label fw-bold">Kode Barang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control font-monospace" id="code" name="code" 
                           value="<?php echo isset($barang) ? htmlspecialchars($barang['code']) : ''; ?>"
                           placeholder="BRG-XXX-001" required
                           <?php echo isset($barang) ? '' : ''; ?>>
                    <div class="form-text">Kode harus unik. Contoh: BRG-ELK-009</div>
                </div>

                <div class="col-md-8">
                    <label for="name" class="form-label fw-bold">Nama Barang <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?php echo isset($barang) ? htmlspecialchars($barang['name']) : ''; ?>"
                           placeholder="Nama produk/barang" required>
                </div>

                <div class="col-md-4">
                    <label for="kategori_id" class="form-label fw-bold">Kategori <span class="text-danger">*</span></label>
                    <select class="form-select" id="kategori_id" name="kategori_id" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo (isset($barang) && $barang['kategori_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="unit" class="form-label fw-bold">Satuan <span class="text-danger">*</span></label>
                    <select class="form-select" id="unit" name="unit" required>
                        <option value="">-- Pilih Satuan --</option>
                        <?php
                        $units = ['pcs', 'unit', 'box', 'kg', 'liter', 'rim', 'lusin', 'roll', 'meter'];
                        foreach ($units as $u):
                            $selected = (isset($barang) && $barang['unit'] == $u) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $u; ?>" <?php echo $selected; ?>><?php echo strtoupper($u); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="min_stock" class="form-label fw-bold">Minimum Stok</label>
                    <input type="number" class="form-control" id="min_stock" name="min_stock" min="0"
                           value="<?php echo isset($barang) ? htmlspecialchars($barang['min_stock']) : '10'; ?>">
                    <div class="form-text">Batas minimum stok sebelum peringatan muncul.</div>
                </div>

                <div class="col-md-4">
                    <label for="status" class="form-label fw-bold">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="active" <?php echo (!isset($barang) || $barang['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                        <option value="inactive" <?php echo (isset($barang) && $barang['status'] == 'inactive') ? 'selected' : ''; ?>>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label fw-bold">Deskripsi</label>
                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Deskripsi singkat barang (opsional)"><?php echo isset($barang) ? htmlspecialchars($barang['description']) : ''; ?></textarea>
                </div>

                <div class="col-12 mt-3 pt-3 border-top">
                    <div class="form-actions-responsive">
                        <a href="<?php echo BASE_URL; ?>/barang/index" class="btn btn-light px-4">
                            <i class="fas fa-times me-1"></i>Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-2"></i>Simpan Barang</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Client-side validation
document.getElementById('barangForm').addEventListener('submit', function(e) {
    const code = document.getElementById('code').value.trim();
    const name = document.getElementById('name').value.trim();
    const kategori = document.getElementById('kategori_id').value;
    const unit = document.getElementById('unit').value;

    if (!code || !name || !kategori || !unit) {
        e.preventDefault();
        alert('Mohon lengkapi semua field yang wajib diisi (*).');
    }
});
</script>
