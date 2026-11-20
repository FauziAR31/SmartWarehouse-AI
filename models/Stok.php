<?php
require_once __DIR__ . '/../config/database.php';

class Stok {
    private $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    public function getStockReport($search = '', $kategori_id = '', $lokasi_id = '', $status = '', $limit = 10, $offset = 0) {
        $params = [];
        
        $sql = "SELECT b.id, b.code, b.name, b.unit, b.min_stock, k.name as kategori_name,
                       COALESCE(SUM(s.qty), 0) as total_qty
                FROM barang b
                LEFT JOIN kategori k ON b.kategori_id = k.id
                LEFT JOIN stok s ON b.id = s.barang_id ";
                
        $whereClauses = [];
        if (!empty($lokasi_id)) {
            // If location is filtered, we only sum up stock for that specific location
            // We use a subquery or join condition. It's safer to just do a WHERE on the joined table
            $whereClauses[] = "s.lokasi_id = :lokasi";
            $params[':lokasi'] = $lokasi_id;
        }

        if (!empty($search)) {
            $whereClauses[] = "(b.code LIKE :search OR b.name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($kategori_id)) {
            $whereClauses[] = "b.kategori_id = :kategori";
            $params[':kategori'] = $kategori_id;
        }

        if (count($whereClauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " GROUP BY b.id, b.code, b.name, b.unit, b.min_stock, k.name ";

        // HAVING clause for Status
        if (!empty($status)) {
            if ($status === 'AMAN') {
                $sql .= " HAVING total_qty > b.min_stock ";
            } else if ($status === 'MINIMUM') {
                $sql .= " HAVING total_qty <= b.min_stock AND total_qty > 0 ";
            } else if ($status === 'HABIS') {
                $sql .= " HAVING total_qty = 0 ";
            }
        }

        $sql .= " ORDER BY b.code ASC ";
        $sql .= " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        
        // Bind params carefully because LIMIT/OFFSET need integers
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countStockReport($search = '', $kategori_id = '', $lokasi_id = '', $status = '') {
        $params = [];
        
        // Count with GROUP BY requires wrapping in a subquery
        $sql = "SELECT COUNT(*) as total_rows FROM (
                    SELECT b.id, COALESCE(SUM(s.qty), 0) as total_qty, b.min_stock
                    FROM barang b
                    LEFT JOIN stok s ON b.id = s.barang_id ";
        
        $whereClauses = [];
        if (!empty($lokasi_id)) {
            $whereClauses[] = "s.lokasi_id = :lokasi";
            $params[':lokasi'] = $lokasi_id;
        }

        if (!empty($search)) {
            $whereClauses[] = "(b.code LIKE :search OR b.name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        if (!empty($kategori_id)) {
            $whereClauses[] = "b.kategori_id = :kategori";
            $params[':kategori'] = $kategori_id;
        }

        if (count($whereClauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $sql .= " GROUP BY b.id, b.min_stock ";

        if (!empty($status)) {
            if ($status === 'AMAN') {
                $sql .= " HAVING total_qty > b.min_stock ";
            } else if ($status === 'MINIMUM') {
                $sql .= " HAVING total_qty <= b.min_stock AND total_qty > 0 ";
            } else if ($status === 'HABIS') {
                $sql .= " HAVING total_qty = 0 ";
            }
        }

        $sql .= ") as subquery";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total_rows'];
    }

    public function getDetailLokasi($barangId) {
        $sql = "SELECT l.kode, l.nama, s.qty 
                FROM stok s
                JOIN lokasi l ON s.lokasi_id = l.id
                WHERE s.barang_id = :barang_id AND s.qty > 0
                ORDER BY l.kode ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ambil semua lokasi aktif beserta qty stok untuk barang tertentu (termasuk qty=0) */
    public function getLokasiForBarang($barangId) {
        $sql = "SELECT l.id as lokasi_id, l.kode, l.nama, COALESCE(s.qty, 0) as qty
                FROM lokasi l
                LEFT JOIN stok s ON s.lokasi_id = l.id AND s.barang_id = :barang_id
                WHERE l.status != 'tidak_aktif'
                ORDER BY l.kode ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Adjust stok: tambah atau kurang qty pada lokasi tertentu.
     * $mode: 'tambah' | 'kurang'
     * Returns ['success'=>bool, 'message'=>string]
     */
    public function adjustStok($barangId, $lokasiId, $qty, $mode, $userId, $keterangan = '') {
        try {
            $this->db->beginTransaction();

            // Cek stok saat ini
            $sqlCheck = "SELECT id, qty FROM stok WHERE barang_id = :barang_id AND lokasi_id = :lokasi_id";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
            $stmtCheck->bindParam(':lokasi_id', $lokasiId, PDO::PARAM_INT);
            $stmtCheck->execute();
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if ($mode === 'tambah') {
                $trxType = 'in';
                if ($existing) {
                    $newQty = $existing['qty'] + $qty;
                    $sqlUpd = "UPDATE stok SET qty = :qty WHERE id = :id";
                    $stmtUpd = $this->db->prepare($sqlUpd);
                    $stmtUpd->bindParam(':qty', $newQty, PDO::PARAM_INT);
                    $stmtUpd->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                    $stmtUpd->execute();
                } else {
                    $newQty = $qty;
                    $sqlIns = "INSERT INTO stok (barang_id, lokasi_id, qty) VALUES (:barang_id, :lokasi_id, :qty)";
                    $stmtIns = $this->db->prepare($sqlIns);
                    $stmtIns->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
                    $stmtIns->bindParam(':lokasi_id', $lokasiId, PDO::PARAM_INT);
                    $stmtIns->bindParam(':qty', $qty, PDO::PARAM_INT);
                    $stmtIns->execute();
                }
            } else { // kurang
                $trxType = 'out';
                if (!$existing || $existing['qty'] < $qty) {
                    $current = $existing ? $existing['qty'] : 0;
                    throw new Exception("Stok tidak mencukupi. Stok saat ini: {$current}");
                }
                $newQty = $existing['qty'] - $qty;
                $sqlUpd = "UPDATE stok SET qty = :qty WHERE id = :id";
                $stmtUpd = $this->db->prepare($sqlUpd);
                $stmtUpd->bindParam(':qty', $newQty, PDO::PARAM_INT);
                $stmtUpd->bindParam(':id', $existing['id'], PDO::PARAM_INT);
                $stmtUpd->execute();
            }

            // Buat nomor transaksi adjustment
            $prefix = 'ADJ-' . date('Ymd') . '-';
            $sqlLastTrx = "SELECT transaction_number FROM transaksi WHERE transaction_number LIKE :prefix ORDER BY id DESC LIMIT 1";
            $stmtLast = $this->db->prepare($sqlLastTrx);
            $likePfx = $prefix . '%';
            $stmtLast->bindParam(':prefix', $likePfx);
            $stmtLast->execute();
            $lastRow = $stmtLast->fetch(PDO::FETCH_ASSOC);
            $seq = $lastRow ? (intval(substr($lastRow['transaction_number'], -4)) + 1) : 1;
            $trxNo = $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $notes = $keterangan ?: 'Penyesuaian stok manual (' . ($mode === 'tambah' ? 'Tambah' : 'Kurang') . ')';
            $today = date('Y-m-d');

            // Insert transaksi header
            $sqlTrx = "INSERT INTO transaksi (transaction_number, type, user_id, transaction_date, notes) 
                        VALUES (:trx_no, :type, :user_id, :trx_date, :notes)";
            $stmtTrx = $this->db->prepare($sqlTrx);
            $stmtTrx->bindParam(':trx_no',   $trxNo);
            $stmtTrx->bindParam(':type',     $trxType);
            $stmtTrx->bindParam(':user_id',  $userId, PDO::PARAM_INT);
            $stmtTrx->bindParam(':trx_date', $today);
            $stmtTrx->bindParam(':notes',    $notes);
            $stmtTrx->execute();
            $trxId = $this->db->lastInsertId();

            // Insert transaksi_detail
            $sqlDet = "INSERT INTO transaksi_detail (transaksi_id, barang_id, lokasi_id, qty)
                        VALUES (:trx_id, :barang_id, :lokasi_id, :qty)";
            $stmtDet = $this->db->prepare($sqlDet);
            $stmtDet->bindParam(':trx_id',    $trxId, PDO::PARAM_INT);
            $stmtDet->bindParam(':barang_id', $barangId, PDO::PARAM_INT);
            $stmtDet->bindParam(':lokasi_id', $lokasiId, PDO::PARAM_INT);
            $stmtDet->bindParam(':qty',       $qty, PDO::PARAM_INT);
            $stmtDet->execute();

            // Update status lokasi
            $sqlTotalLok = "SELECT COALESCE(SUM(qty),0) as total FROM stok WHERE lokasi_id = :lokasi_id";
            $stmtTotalLok = $this->db->prepare($sqlTotalLok);
            $stmtTotalLok->bindParam(':lokasi_id', $lokasiId, PDO::PARAM_INT);
            $stmtTotalLok->execute();
            $totalLok = (int)$stmtTotalLok->fetch(PDO::FETCH_ASSOC)['total'];
            $lokasiStatus = $totalLok > 0 ? 'terisi' : 'kosong';
            $sqlUpdLok = "UPDATE lokasi SET status = :status WHERE id = :id AND status != 'tidak_aktif'";
            $stmtUpdLok = $this->db->prepare($sqlUpdLok);
            $stmtUpdLok->bindParam(':status', $lokasiStatus);
            $stmtUpdLok->bindParam(':id', $lokasiId, PDO::PARAM_INT);
            $stmtUpdLok->execute();

            $this->db->commit();

            // Real-time WhatsApp Notification for Stock Adjustment
            try {
                require_once __DIR__ . '/../helpers/WhatsAppHelper.php';
                $stmtB = $this->db->prepare("SELECT name, code FROM barang WHERE id = ? LIMIT 1");
                $stmtB->execute([$barangId]);
                $bRow = $stmtB->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Barang #' . $barangId, 'code' => ''];

                $stmtL = $this->db->prepare("SELECT kode FROM lokasi WHERE id = ? LIMIT 1");
                $stmtL->execute([$lokasiId]);
                $lKode = $stmtL->fetchColumn() ?: ('LOK-' . $lokasiId);

                $oldQty = $existing ? (int)$existing['qty'] : 0;
                $userName = $_SESSION['full_name'] ?? 'Petugas Gudang';
                WhatsAppHelper::notifyStockAdjustment($bRow['name'], $bRow['code'], $lKode, $oldQty, $newQty, $notes, $userName);
            } catch (Throwable $e) {}

            return ['success' => true, 'message' => 'Stok berhasil diperbarui. No. Transaksi: ' . $trxNo, 'trx_no' => $trxNo, 'new_qty' => $newQty];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
