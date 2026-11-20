<?php

class Barang {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getAllPaginated($limit, $offset, $search = '', $kategori_id = '') {
        $query = "SELECT b.*, k.name as kategori_name, COALESCE(SUM(s.qty), 0) as total_qty 
                  FROM barang b
                  LEFT JOIN kategori k ON b.kategori_id = k.id
                  LEFT JOIN stok s ON b.id = s.barang_id
                  WHERE (b.name LIKE :search OR b.code LIKE :search)";

        if (!empty($kategori_id)) {
            $query .= " AND b.kategori_id = :kategori_id";
        }

        $query .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
        
        if (!empty($kategori_id)) {
            $stmt->bindParam(':kategori_id', $kategori_id);
        }

        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRows($search = '', $kategori_id = '') {
        $query = "SELECT COUNT(*) as total FROM barang b WHERE (b.name LIKE :search OR b.code LIKE :search)";
        
        if (!empty($kategori_id)) {
            $query .= " AND b.kategori_id = :kategori_id";
        }

        $stmt = $this->db->prepare($query);
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam);
        
        if (!empty($kategori_id)) {
            $stmt->bindParam(':kategori_id', $kategori_id);
        }

        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function getById($id) {
        $query = "SELECT b.*, k.name as kategori_name, COALESCE(SUM(s.qty), 0) as total_qty 
                  FROM barang b
                  LEFT JOIN kategori k ON b.kategori_id = k.id
                  LEFT JOIN stok s ON b.id = s.barang_id
                  WHERE b.id = :id
                  GROUP BY b.id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isCodeUnique($code, $excludeId = null) {
        $query = "SELECT COUNT(*) as count FROM barang WHERE code = :code";
        if ($excludeId) {
            $query .= " AND id != :excludeId";
        }
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':code', $code);
        if ($excludeId) {
            $stmt->bindParam(':excludeId', $excludeId);
        }
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] == 0;
    }

    public function create($data) {
        $query = "INSERT INTO barang (code, name, kategori_id, description, unit, min_stock, status) 
                  VALUES (:code, :name, :kategori_id, :description, :unit, :min_stock, :status)";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':kategori_id', $data['kategori_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_stock', $data['min_stock']);
        $stmt->bindParam(':status', $data['status']);
        return $stmt->execute();
    }

    public function update($id, $data) {
        $query = "UPDATE barang SET 
                  code = :code, 
                  name = :name, 
                  kategori_id = :kategori_id, 
                  description = :description, 
                  unit = :unit, 
                  min_stock = :min_stock, 
                  status = :status 
                  WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':code', $data['code']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':kategori_id', $data['kategori_id']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':min_stock', $data['min_stock']);
        $stmt->bindParam(':status', $data['status']);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getByCode($code) {
        $query = "SELECT b.*, k.name as kategori_name 
                  FROM barang b
                  LEFT JOIN kategori k ON b.kategori_id = k.id
                  WHERE b.code = :code AND b.status = 'active'
                  LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':code', $code);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllActive() {
        $query = "SELECT id, code, name, unit FROM barang WHERE status = 'active' ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Used by LaporanController/RiwayatController for filter dropdowns
    public function getAll() {
        $query = "SELECT id, code, name, unit FROM barang ORDER BY name ASC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id) {
        // Check relationships (e.g., in transactions or stock)
        $checkQuery = "SELECT COUNT(*) as count FROM transaksi_detail WHERE barang_id = :id";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();
        if ($checkStmt->fetch(PDO::FETCH_ASSOC)['count'] > 0) {
            return false; // Cannot delete, involved in transactions
        }
        
        $checkStock = "SELECT COALESCE(SUM(qty), 0) as total FROM stok WHERE barang_id = :id";
        $checkStockStmt = $this->db->prepare($checkStock);
        $checkStockStmt->bindParam(':id', $id);
        $checkStockStmt->execute();
        if ($checkStockStmt->fetch(PDO::FETCH_ASSOC)['total'] > 0) {
            return false; // Cannot delete, still has stock
        }

        $query = "DELETE FROM barang WHERE id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function getAvailableLocations($barangId) {
        $query = "SELECT l.id, l.kode, l.nama, s.qty 
                  FROM stok s
                  JOIN lokasi l ON s.lokasi_id = l.id
                  WHERE s.barang_id = :barang_id AND s.qty > 0 AND l.status != 'tidak_aktif'
                  ORDER BY l.kode ASC";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':barang_id', $barangId);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Import master barang dari file CSV
     * Mendukung format file database.csv / barang_masuk.csv:
     * No;No Material;Nama Barang;Kategori;Satuan;Min Stock;Deskripsi
     */
    public function importFromCsv($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['success' => false, 'message' => 'File CSV tidak dapat dibaca.'];
        }

        $content = file_get_contents($filePath);
        if ($content === false || strlen(trim($content)) === 0) {
            return ['success' => false, 'message' => 'File CSV kosong.'];
        }

        // Remove UTF-8 BOM if present
        $bom = pack('H*', 'EFBBBF');
        $content = preg_replace("/^$bom/", '', $content);

        // Normalize newlines
        $content = str_replace(["\r\n", "\r"], "\n", $content);
        $lines = explode("\n", trim($content));
        if (count($lines) < 2) {
            return ['success' => false, 'message' => 'File CSV harus memiliki minimal 1 baris header dan 1 baris data.'];
        }

        // Detect delimiter: semicolon ';' or comma ',' or tab '\t'
        $firstLine = $lines[0];
        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');
        $tabCount = substr_count($firstLine, "\t");

        $delimiter = ';';
        if ($commaCount > $semicolonCount && $commaCount > $tabCount) {
            $delimiter = ',';
        } elseif ($tabCount > $semicolonCount && $tabCount > $commaCount) {
            $delimiter = "\t";
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header) {
            fclose($handle);
            return ['success' => false, 'message' => 'Gagal membaca header CSV.'];
        }

        // Strip BOM from first header column if present
        $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
        $normalizedHeaders = [];
        foreach ($header as $idx => $col) {
            $c = strtolower(trim($col));
            $c = preg_replace('/[^a-z0-9_]/', '', str_replace(' ', '_', $c));
            $normalizedHeaders[$idx] = $c;
        }

        // Map column indices
        $colCode = null;
        $colName = null;
        $colKategori = null;
        $colUnit = null;
        $colMinStock = null;
        $colDesc = null;

        foreach ($normalizedHeaders as $idx => $col) {
            if ($colCode === null && in_array($col, ['no_material', 'nomaterial', 'material', 'kode', 'kode_barang', 'code', 'barcode'])) {
                $colCode = $idx;
            } elseif ($colName === null && in_array($col, ['nama_barang', 'nama_material', 'nama', 'name', 'deskripsi_barang'])) {
                $colName = $idx;
            } elseif ($colKategori === null && in_array($col, ['kategori', 'category', 'kategori_id', 'group'])) {
                $colKategori = $idx;
            } elseif ($colUnit === null && in_array($col, ['satuan', 'unit', 'uom'])) {
                $colUnit = $idx;
            } elseif ($colMinStock === null && in_array($col, ['min_stock', 'minstock', 'stok_minimum', 'minimum'])) {
                $colMinStock = $idx;
            } elseif ($colDesc === null && in_array($col, ['deskripsi', 'description', 'keterangan', 'notes'])) {
                $colDesc = $idx;
            }
        }

        // Fallback mapping for user format: "No;No Material;Nama Barang"
        if ($colCode === null && isset($normalizedHeaders[1])) {
            $colCode = 1;
        }
        if ($colName === null && isset($normalizedHeaders[2])) {
            $colName = 2;
        }

        if ($colCode === null || $colName === null) {
            fclose($handle);
            return [
                'success' => false,
                'message' => 'Kolom "No Material" (Kode Barang) dan "Nama Barang" wajib ada di baris header CSV.'
            ];
        }

        // Cache categories
        $stmtCat = $this->db->query("SELECT id, LOWER(name) as lname FROM kategori");
        $categoriesMap = [];
        $firstCatId = 1;
        while ($cRow = $stmtCat->fetch(PDO::FETCH_ASSOC)) {
            $categoriesMap[$cRow['lname']] = $cRow['id'];
            if (!$firstCatId) $firstCatId = $cRow['id'];
        }

        $inserted = 0;
        $updated = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNum++;
            if (empty(array_filter($row, 'trim'))) {
                continue;
            }

            $code = trim($row[$colCode] ?? '');
            $name = trim($row[$colName] ?? '');

            if (empty($code) || empty($name)) {
                $errors[] = "Baris $rowNum: Kode atau Nama barang kosong.";
                continue;
            }

            // Kategori resolution
            $kategoriId = $firstCatId;
            if ($colKategori !== null && !empty($row[$colKategori])) {
                $catInput = trim($row[$colKategori]);
                $catLower = strtolower($catInput);
                if (isset($categoriesMap[$catLower])) {
                    $kategoriId = $categoriesMap[$catLower];
                } elseif (is_numeric($catInput)) {
                    $kategoriId = (int)$catInput;
                } else {
                    $stmtNewCat = $this->db->prepare("INSERT INTO kategori (name, description) VALUES (?, ?)");
                    $stmtNewCat->execute([$catInput, 'Dibuat otomatis dari import CSV']);
                    $newId = $this->db->lastInsertId();
                    $categoriesMap[$catLower] = $newId;
                    $kategoriId = $newId;
                }
            }

            $unit = ($colUnit !== null && !empty($row[$colUnit])) ? strtolower(trim($row[$colUnit])) : 'pcs';
            $minStock = ($colMinStock !== null && !empty($row[$colMinStock])) ? (int)$row[$colMinStock] : 5;
            $description = ($colDesc !== null && !empty($row[$colDesc])) ? trim($row[$colDesc]) : 'Import CSV';

            // Check if code exists
            $stmtCheck = $this->db->prepare("SELECT id FROM barang WHERE code = ? LIMIT 1");
            $stmtCheck->execute([$code]);
            $existingId = $stmtCheck->fetchColumn();

            if ($existingId) {
                $stmtUpdate = $this->db->prepare("UPDATE barang SET 
                    name = :name, 
                    kategori_id = :kategori_id, 
                    unit = :unit, 
                    min_stock = :min_stock, 
                    description = :description, 
                    status = 'active', 
                    updated_at = NOW() 
                    WHERE id = :id");
                $stmtUpdate->execute([
                    ':name' => $name,
                    ':kategori_id' => $kategoriId,
                    ':unit' => $unit,
                    ':min_stock' => $minStock,
                    ':description' => $description,
                    ':id' => $existingId
                ]);
                $updated++;
            } else {
                $stmtInsert = $this->db->prepare("INSERT INTO barang (code, name, kategori_id, unit, min_stock, description, status) 
                    VALUES (:code, :name, :kategori_id, :unit, :min_stock, :description, 'active')");
                $stmtInsert->execute([
                    ':code' => $code,
                    ':name' => $name,
                    ':kategori_id' => $kategoriId,
                    ':unit' => $unit,
                    ':min_stock' => $minStock,
                    ':description' => $description
                ]);
                $inserted++;
            }
        }
        fclose($handle);

        return [
            'success' => true,
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => $inserted + $updated,
            'errors' => $errors
        ];
    }
}

