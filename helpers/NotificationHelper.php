<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';

class NotificationHelper {

    /**
     * Record a new transaction notification for real-time broadcast
     */
    public static function record($type, $trxNumber, $barangName, $qty, $unit, $lokasiInfo, $operatorName = '', $notes = '', $trxId = null) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return false;

            if (empty($operatorName)) {
                $operatorName = $_SESSION['full_name'] ?? 'Petugas Gudang';
            }

            $title = 'Transaksi Gudang';
            $message = '';
            $qtyFmt = number_format((int)$qty);
            $unitStr = $unit ?: 'unit';

            if ($type === 'in') {
                $title = '📦 Barang Masuk (Inbound)';
                $message = "Material {$barangName} (+{$qtyFmt} {$unitStr}) masuk ke rak {$lokasiInfo} oleh {$operatorName}.";
            } elseif ($type === 'out') {
                $title = '📤 Barang Keluar (Outbound)';
                $message = "Material {$barangName} (-{$qtyFmt} {$unitStr}) keluar dari rak {$lokasiInfo} oleh {$operatorName}.";
            } elseif ($type === 'transfer') {
                $title = '🔄 Pemindahan Barang (Transfer)';
                $message = "Material {$barangName} ({$qtyFmt} {$unitStr}) dipindahkan: {$lokasiInfo} oleh {$operatorName}.";
            } elseif ($type === 'adjustment') {
                $title = '⚖️ Koreksi / Opname Stok';
                $message = "Penyesuaian stok material {$barangName} di rak {$lokasiInfo} oleh {$operatorName}.";
            } elseif ($type === 'low_stock') {
                $title = '⚠️ Peringatan Stok Menipis!';
                $message = "Material {$barangName} tersisa {$qtyFmt} {$unitStr} (di bawah batas minimum).";
            }

            if (!empty($notes) && $notes !== '-') {
                $message .= " Catatan: " . substr($notes, 0, 80);
            }

            $stmt = $db->prepare("INSERT INTO realtime_notifications 
                (trx_id, trx_number, trx_type, title, message, operator_name, barang_name, qty, unit, lokasi_info, is_read, created_at)
                VALUES (:trx_id, :trx_num, :type, :title, :msg, :op, :brg, :qty, :unit, :lok, 0, NOW())");
            
            $stmt->execute([
                ':trx_id'  => $trxId,
                ':trx_num' => $trxNumber,
                ':type'    => $type,
                ':title'   => $title,
                ':msg'     => $message,
                ':op'      => $operatorName,
                ':brg'     => $barangName,
                ':qty'     => (int)$qty,
                ':unit'    => $unitStr,
                ':lok'     => $lokasiInfo
            ]);

            return $db->lastInsertId();
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Poll new notifications since a given ID
     */
    public static function pollNew($sinceId = 0) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return [];

            $stmt = $db->prepare("SELECT * FROM realtime_notifications 
                WHERE id > :since_id 
                ORDER BY id ASC 
                LIMIT 15");
            $stmt->bindValue(':since_id', (int)$sinceId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get recent notifications for the header dropdown
     */
    public static function getRecent($limit = 10) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return [];

            $stmt = $db->prepare("SELECT * FROM realtime_notifications 
                ORDER BY id DESC 
                LIMIT :lim");
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Get count of unread notifications
     */
    public static function getUnreadCount() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return 0;

            $stmt = $db->query("SELECT COUNT(*) FROM realtime_notifications WHERE is_read = 0");
            return (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Mark all notifications as read
     */
    public static function markAllRead() {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return false;

            return $db->exec("UPDATE realtime_notifications SET is_read = 1 WHERE is_read = 0");
        } catch (Throwable $e) {
            return false;
        }
    }
}
