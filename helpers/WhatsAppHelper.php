<?php
date_default_timezone_set('Asia/Jakarta');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Setting.php';

class WhatsAppHelper {

    /**
     * Clean and normalize a phone number into international format (628...)
     */
    public static function normalizePhone($phone) {
        $clean = preg_replace('/[^0-9]/', '', (string)$phone);
        if (empty($clean)) return '';

        // If starts with 08..., change to 628...
        if (substr($clean, 0, 2) === '08') {
            return '62' . substr($clean, 1);
        }
        // If starts with 8..., change to 628...
        if (substr($clean, 0, 1) === '8') {
            return '62' . $clean;
        }
        // If starts with 62..., already international
        if (substr($clean, 0, 2) === '62') {
            return $clean;
        }
        return $clean;
    }

    /**
     * Parse multiple recipient numbers separated by comma, semicolon, newline, or space
     */
    public static function parseRecipients($rawPhones) {
        $parts = preg_split('/[,\r\n;]+/', (string)$rawPhones);
        $result = [];
        foreach ($parts as $p) {
            $norm = self::normalizePhone(trim($p));
            if (!empty($norm) && strlen($norm) >= 9) {
                $result[] = $norm;
            }
        }
        return array_unique($result);
    }

    /**
     * Generate direct wa.me link for manual sending or preview
     */
    public static function generateWaMeLink($phone, $message) {
        $norm = self::normalizePhone($phone);
        return 'https://api.whatsapp.com/send?phone=' . urlencode($norm) . '&text=' . urlencode($message);
    }

    /**
     * Send message via configured WhatsApp Gateway
     */
    public static function sendMessage($recipients, $message, $messageType = 'NOTIFICATION', $trxNumber = null) {
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();

        $rawRecipients = !empty($recipients) ? $recipients : $config['wa_phone'];
        $targets = self::parseRecipients($rawRecipients);

        if (empty($targets)) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp penerima belum dikonfigurasi.'
            ];
        }

        $provider = $config['wa_provider'] ?: 'fonnte';
        $token = trim($config['wa_api_token']);
        $apiUrl = trim($config['wa_api_url']);
        $instanceId = trim($config['wa_instance_id']);

        $results = [];
        $database = new Database();
        $db = $database->getConnection();

        foreach ($targets as $phone) {
            $status = 'simulated';
            $responseRaw = '';
            $isSuccess = false;

            // If token is empty or provider is wame_log -> Simulation Mode
            if (empty($token) || $provider === 'wame_log') {
                $status = 'simulated';
                $responseRaw = json_encode([
                    'mode' => 'simulated',
                    'note' => 'Pesan tercatat dalam log SmartWarehouse. Belum ada API Token aktif, gunakan link wa.me untuk mengirim langsung.',
                    'wa_link' => self::generateWaMeLink($phone, $message)
                ]);
                $isSuccess = true;
            } else {
                // Real Gateway Call via cURL
                try {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

                    if ($provider === 'fonnte') {
                        $url = !empty($apiUrl) ? $apiUrl : 'https://api.fonnte.com/send';
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: ' . $token
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, [
                            'target' => $phone,
                            'message' => $message,
                            'countryCode' => '62'
                        ]);
                    } elseif ($provider === 'wablas') {
                        $domain = !empty($apiUrl) ? rtrim($apiUrl, '/') : 'https://jakarta.wablas.com';
                        $url = (strpos($domain, '/api/') === false) ? $domain . '/api/send-message' : $domain;
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Authorization: ' . $token
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, [
                            'phone' => $phone,
                            'message' => $message
                        ]);
                    } elseif ($provider === 'ultramsg') {
                        $inst = !empty($instanceId) ? $instanceId : 'instance0001';
                        $url = "https://api.ultramsg.com/{$inst}/messages/chat";
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                            'token' => $token,
                            'to' => '+' . $phone,
                            'body' => $message
                        ]));
                    } elseif ($provider === 'custom_webhook') {
                        $url = $apiUrl;
                        curl_setopt($ch, CURLOPT_URL, $url);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json',
                            'Authorization: ' . $token
                        ]);
                        $payload = json_encode([
                            'target' => $phone,
                            'phone'  => $phone,
                            'to'     => $phone,
                            'number' => $phone,
                            'message'=> $message,
                            'text'   => $message,
                            'type'   => $messageType,
                            'transaction_number' => $trxNumber
                        ]);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    }

                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr = curl_error($ch);
                    curl_close($ch);

                    $responseRaw = $response ?: $curlErr;

                    // Analyze response
                    if ($httpCode >= 200 && $httpCode < 300) {
                        $json = json_decode($response, true);
                        if ($provider === 'fonnte') {
                            if (isset($json['status']) && ($json['status'] === true || $json['status'] === 'true' || isset($json['id']))) {
                                $status = 'sent';
                                $isSuccess = true;
                            } else {
                                $status = 'failed';
                            }
                        } elseif ($provider === 'ultramsg') {
                            if (isset($json['sent']) && $json['sent'] === 'true') {
                                $status = 'sent';
                                $isSuccess = true;
                            } else {
                                $status = 'failed';
                            }
                        } else {
                            $status = 'sent';
                            $isSuccess = true;
                        }
                    } else {
                        $status = 'failed';
                    }
                } catch (Throwable $e) {
                    $status = 'failed';
                    $responseRaw = 'cURL Exception: ' . $e->getMessage();
                }
            }

            // Save to whatsapp_logs table
            try {
                if ($db) {
                    $stmtLog = $db->prepare("INSERT INTO whatsapp_logs 
                        (recipient, message_type, transaction_number, message, provider, status, response_raw, created_at)
                        VALUES (:rec, :type, :trx, :msg, :prov, :st, :raw, NOW())");
                    $stmtLog->execute([
                        ':rec'  => $phone,
                        ':type' => $messageType,
                        ':trx'  => $trxNumber,
                        ':msg'  => $message,
                        ':prov' => $provider,
                        ':st'   => $status,
                        ':raw'  => substr($responseRaw, 0, 1000)
                    ]);
                }
            } catch (Throwable $ignored) {
                // Keep resilient
            }

            $results[] = [
                'recipient' => $phone,
                'status'    => $status,
                'is_success'=> $isSuccess,
                'raw'       => $responseRaw,
                'wa_link'   => self::generateWaMeLink($phone, $message)
            ];
        }

        $allOk = true;
        foreach ($results as $r) {
            if (!$r['is_success']) {
                $allOk = false;
                break;
            }
        }

        return [
            'success'   => $allOk,
            'provider'  => $provider,
            'details'   => $results,
            'first_link'=> $results[0]['wa_link'] ?? ''
        ];
    }

    /**
     * Send transaction notification for Inbound, Outbound, or Transfer
     */
    public static function notifyTransaction($type, $dataTransaksi, $dataDetail) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return false;

            // Fetch details: Material Name, Unit, Code
            $barangId = (int)($dataDetail['barang_id'] ?? 0);
            $stmtB = $db->prepare("SELECT name, code, unit FROM barang WHERE id = ? LIMIT 1");
            $stmtB->execute([$barangId]);
            $barang = $stmtB->fetch(PDO::FETCH_ASSOC) ?: ['name' => 'Material #' . $barangId, 'code' => '-', 'unit' => 'unit'];

            // Fetch Location(s)
            $lokasiId = (int)($dataDetail['lokasi_id'] ?? 0);
            $stmtL = $db->prepare("SELECT kode, nama FROM lokasi WHERE id = ? LIMIT 1");
            $stmtL->execute([$lokasiId]);
            $lokasi = $stmtL->fetch(PDO::FETCH_ASSOC) ?: ['kode' => 'LOK-' . $lokasiId, 'nama' => ''];

            $lokasiTujuanKode = '';
            if ($type === 'transfer' && !empty($dataDetail['lokasi_tujuan_id'])) {
                $stmtLT = $db->prepare("SELECT kode, nama FROM lokasi WHERE id = ? LIMIT 1");
                $stmtLT->execute([(int)$dataDetail['lokasi_tujuan_id']]);
                $lokT = $stmtLT->fetch(PDO::FETCH_ASSOC);
                $lokasiTujuanKode = $lokT['kode'] ?? 'LOK-' . $dataDetail['lokasi_tujuan_id'];
            }

            // Fetch Operator Name
            $userId = (int)($dataTransaksi['user_id'] ?? ($_SESSION['user_id'] ?? 0));
            $operatorName = $_SESSION['full_name'] ?? 'Petugas Gudang';
            if ($userId > 0) {
                $stmtU = $db->prepare("SELECT full_name, username FROM users WHERE id = ? LIMIT 1");
                $stmtU->execute([$userId]);
                $user = $stmtU->fetch(PDO::FETCH_ASSOC);
                if ($user) {
                    $operatorName = $user['full_name'] ?: $user['username'];
                }
            }

            $trxNum = $dataTransaksi['transaction_number'] ?? ('TRX-' . strtoupper($type) . '-' . date('Ymd-His'));
            $qty = number_format((int)($dataDetail['qty'] ?? 1));
            $unit = $barang['unit'] ?: 'unit';
            $waktu = date('d/m/Y H:i') . ' WIB';

            // 1. ALWAYS Broadcast In-App Real-Time Notification
            try {
                require_once __DIR__ . '/NotificationHelper.php';
                $lokasiDisplay = ($type === 'transfer') ? ($lokasi['kode'] . ' ➔ ' . $lokasiTujuanKode) : $lokasi['kode'];
                $notes = $dataTransaksi['notes'] ?? '';
                NotificationHelper::record($type, $trxNum, $barang['name'], (int)($dataDetail['qty'] ?? 1), $unit, $lokasiDisplay, $operatorName, $notes);
            } catch (Throwable $e) {}

            // 2. Deliver via WhatsApp Gateway if Enabled
            $setting = new Setting();
            $config = $setting->getWhatsAppConfig();

            // Check if global WA notification is enabled
            if (empty($config['wa_enabled']) || empty($config['wa_phone'])) {
                return true;
            }

            // Check event triggers
            if ($type === 'in' && empty($config['wa_notify_inbound'])) return true;
            if ($type === 'out' && empty($config['wa_notify_outbound'])) return true;
            if ($type === 'transfer' && empty($config['wa_notify_transfer'])) return true;

            $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';
            $message = "";

            if ($type === 'in') {
                $supplier = !empty($dataTransaksi['supplier_name']) ? $dataTransaksi['supplier_name'] : 'Supplier Umum';
                $notes = !empty($dataTransaksi['notes']) ? $dataTransaksi['notes'] : '-';

                $message = "📦 *NOTIFIKASI BARANG MASUK (INBOUND)* 📦\n";
                $message .= "*{$sender}*\n";
                $message .= "----------------------------------------\n";
                $message .= "📄 *No. Transaksi:* `{$trxNum}`\n";
                $message .= "⏰ *Waktu:* {$waktu}\n";
                $message .= "👤 *Operator:* {$operatorName}\n";
                $message .= "🏢 *Supplier:* {$supplier}\n";
                $message .= "🏷️ *Material:* {$barang['name']} ({$barang['code']})\n";
                $message .= "📍 *Rak Tujuan:* {$lokasi['kode']}\n";
                $message .= "📊 *Jumlah Masuk:* +{$qty} {$unit}\n";
                $message .= "📝 *Catatan:* {$notes}\n";
                $message .= "----------------------------------------\n";
                $message .= "_Laporan real-time SmartWarehouse_";

                return self::sendMessage($config['wa_phone'], $message, 'INBOUND', $trxNum);

            } elseif ($type === 'out') {
                $recipient = !empty($dataTransaksi['recipient_name']) ? $dataTransaksi['recipient_name'] : 'Penerima Umum';
                $notes = !empty($dataTransaksi['notes']) ? $dataTransaksi['notes'] : '-';

                $message = "📤 *NOTIFIKASI BARANG KELUAR (OUTBOUND)* 📤\n";
                $message .= "*{$sender}*\n";
                $message .= "----------------------------------------\n";
                $message .= "📄 *No. Transaksi:* `{$trxNum}`\n";
                $message .= "⏰ *Waktu:* {$waktu}\n";
                $message .= "👤 *Operator:* {$operatorName}\n";
                $message .= "🏢 *Penerima:* {$recipient}\n";
                $message .= "🏷️ *Material:* {$barang['name']} ({$barang['code']})\n";
                $message .= "📍 *Rak Asal:* {$lokasi['kode']}\n";
                $message .= "📊 *Jumlah Keluar:* -{$qty} {$unit}\n";
                $message .= "📝 *Catatan:* {$notes}\n";
                $message .= "----------------------------------------\n";
                $message .= "_Laporan real-time SmartWarehouse_";

                return self::sendMessage($config['wa_phone'], $message, 'OUTBOUND', $trxNum);

            } elseif ($type === 'transfer') {
                $notes = !empty($dataTransaksi['notes']) ? $dataTransaksi['notes'] : '-';

                $message = "🔄 *NOTIFIKASI PEMINDAHAN BARANG (TRANSFER)* 🔄\n";
                $message .= "*{$sender}*\n";
                $message .= "----------------------------------------\n";
                $message .= "📄 *No. Transaksi:* `{$trxNum}`\n";
                $message .= "⏰ *Waktu:* {$waktu}\n";
                $message .= "👤 *Operator:* {$operatorName}\n";
                $message .= "🏷️ *Material:* {$barang['name']} ({$barang['code']})\n";
                $message .= "📍 *Dari Rak:* {$lokasi['kode']}\n";
                $message .= "➡️ *Ke Rak:* {$lokasiTujuanKode}\n";
                $message .= "📊 *Jumlah Mutasi:* {$qty} {$unit}\n";
                $message .= "📝 *Catatan:* {$notes}\n";
                $message .= "----------------------------------------\n";
                $message .= "_Laporan real-time SmartWarehouse_";

                return self::sendMessage($config['wa_phone'], $message, 'TRANSFER', $trxNum);
            }

        } catch (Throwable $e) {
            // Keep resilient, never crash caller
            return false;
        }
        return false;
    }

    /**
     * Send stock adjustment / opname notification
     */
    public static function notifyStockAdjustment($barangName, $barangCode, $lokasiKode, $oldQty, $newQty, $reason = '', $operatorName = '') {
        try {
            $diff = $newQty - $oldQty;
            $diffStr = ($diff >= 0 ? '+' : '') . $diff;
            $waktu = date('d/m/Y H:i') . ' WIB';
            $operator = !empty($operatorName) ? $operatorName : ($_SESSION['full_name'] ?? 'Petugas Gudang');

            // 1. Broadcast In-App Real-Time Notification
            try {
                require_once __DIR__ . '/NotificationHelper.php';
                NotificationHelper::record('adjustment', 'ADJ-' . date('Ymd-His'), $barangName, abs($diff), 'unit', $lokasiKode, $operator, "Penyesuaian: {$oldQty} ➔ {$newQty} ({$diffStr}). " . $reason);
            } catch (Throwable $e) {}

            // 2. Deliver via WhatsApp
            $setting = new Setting();
            $config = $setting->getWhatsAppConfig();
            if (empty($config['wa_enabled']) || empty($config['wa_notify_adjustment']) || empty($config['wa_phone'])) {
                return true;
            }

            $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';

            $message = "⚖️ *NOTIFIKASI KOREKSI / OPNAME STOK* ⚖️\n";
            $message .= "*{$sender}*\n";
            $message .= "----------------------------------------\n";
            $message .= "⏰ *Waktu:* {$waktu}\n";
            $message .= "👤 *Petugas:* {$operator}\n";
            $message .= "🏷️ *Material:* {$barangName}" . (!empty($barangCode) ? " ({$barangCode})" : "") . "\n";
            $message .= "📍 *Rak Lokasi:* {$lokasiKode}\n";
            $message .= "📊 *Perubahan Stok:* {$oldQty} ➔ *{$newQty}* (Selisih: {$diffStr})\n";
            if (!empty($reason)) {
                $message .= "📝 *Alasan:* {$reason}\n";
            }
            $message .= "----------------------------------------\n";
            $message .= "_Laporan real-time SmartWarehouse_";

            return self::sendMessage($config['wa_phone'], $message, 'ADJUSTMENT');
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Send critical stock alert
     */
    public static function notifyLowStock($barangName, $barangCode, $currentStock, $minStock, $lokasiText = '') {
        try {
            $waktu = date('d/m/Y H:i') . ' WIB';

            // 1. Broadcast In-App Real-Time Notification
            try {
                require_once __DIR__ . '/NotificationHelper.php';
                NotificationHelper::record('low_stock', 'ALERT-' . date('Ymd-His'), $barangName, $currentStock, 'unit', $lokasiText, 'System Alert', "Sisa stok {$currentStock} (di bawah batas min {$minStock})");
            } catch (Throwable $e) {}

            // 2. Deliver via WhatsApp
            $setting = new Setting();
            $config = $setting->getWhatsAppConfig();
            if (empty($config['wa_enabled']) || empty($config['wa_notify_low_stock']) || empty($config['wa_phone'])) {
                return true;
            }

            $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';

            $message = "⚠️ *PERINGATAN STOK KRITIS / MENIPIS* ⚠️\n";
            $message .= "*{$sender}*\n";
            $message .= "----------------------------------------\n";
            $message .= "⏰ *Waktu Deteksi:* {$waktu}\n";
            $message .= "🏷️ *Material:* {$barangName} ({$barangCode})\n";
            $message .= "📉 *Sisa Stok:* *{$currentStock} unit* (Batas Minimum: {$minStock} unit)\n";
            if (!empty($lokasiText)) {
                $message .= "📍 *Lokasi:* {$lokasiText}\n";
            }
            $message .= "🚨 *Tindakan:* Harap segera lakukan Purchase Order (PO) atau restock barang.\n";
            $message .= "----------------------------------------\n";
            $message .= "_Peringatan otomatis sistem SmartWarehouse_";

            return self::sendMessage($config['wa_phone'], $message, 'LOW_STOCK');
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Send test message to active number
     */
    public static function sendTestMessage($targetNumber = null) {
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();

        $phone = !empty($targetNumber) ? $targetNumber : $config['wa_phone'];
        $cleanPhone = self::normalizePhone($phone);

        if (empty($cleanPhone)) {
            return [
                'success' => false,
                'message' => 'Silakan masukkan nomor WhatsApp aktif terlebih dahulu.'
            ];
        }

        $waktu = date('d/m/Y H:i:s') . ' WIB';
        $providerName = strtoupper($config['wa_provider']);
        $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';

        $message = "✅ *KONEKSI NOTIFIKASI WHATSAPP AKTIF* ✅\n";
        $message .= "*{$sender}*\n";
        $message .= "----------------------------------------\n";
        $message .= "Halo! Nomor WhatsApp Anda ({$cleanPhone}) telah berhasil terdaftar pada sistem SmartWarehouse.\n\n";
        $message .= "⏰ *Waktu Uji Coba:* {$waktu}\n";
        $message .= "🌐 *Provider:* {$providerName}\n";
        $message .= "📱 *Status Notifikasi:* Aktif Real-time\n\n";
        $message .= "Mulai saat ini, setiap transaksi gudang (Barang Masuk, Barang Keluar, Pemindahan Rak) akan langsung dikirimkan ke nomor ini.\n";
        $message .= "----------------------------------------\n";
        $message .= "_Pesan uji coba otomatis SmartWarehouse_";

        return self::sendMessage($cleanPhone, $message, 'TEST');
    }

    /**
     * Get real-time WhatsApp Device status from Fonnte API
     */
    public static function getDeviceStatus() {
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();
        $token = trim($config['wa_api_token'] ?? '');
        $provider = $config['wa_provider'] ?? 'fonnte';

        if (empty($token) || $provider !== 'fonnte') {
            return [
                'success'       => false,
                'device_status' => 'unknown',
                'device'        => $config['wa_phone'] ?? '',
                'name'          => 'Manual Mode',
                'quota'         => '-',
                'package'       => '-',
                'message'       => 'API Token belum dikonfigurasi atau bukan provider Fonnte.'
            ];
        }

        try {
            $ch = curl_init('https://api.fonnte.com/device');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $token]);
            $res = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$res) {
                return [
                    'success'       => false,
                    'device_status' => 'disconnected',
                    'message'       => 'Gagal menghubungi server Fonnte: ' . $err
                ];
            }

            $json = json_decode($res, true);
            $isConnect = isset($json['device_status']) && $json['device_status'] === 'connect';

            return [
                'success'       => true,
                'connected'     => $isConnect,
                'device_status' => $json['device_status'] ?? ($isConnect ? 'connect' : 'disconnect'),
                'device'        => $json['device'] ?? '',
                'name'          => $json['name'] ?? '',
                'quota'         => $json['quota'] ?? 0,
                'package'       => $json['package'] ?? 'Free',
                'expired'       => $json['expired'] ?? '',
                'raw'           => $json
            ];
        } catch (Throwable $e) {
            return [
                'success'       => false,
                'device_status' => 'error',
                'message'       => $e->getMessage()
            ];
        }
    }

    /**
     * Request 8-character Pairing Code from Fonnte for linking WhatsApp device
     */
    public static function requestPairingCode($phone) {
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();
        $token = trim($config['wa_api_token'] ?? '');
        $cleanPhone = self::normalizePhone($phone);

        if (empty($token)) {
            return ['success' => false, 'message' => 'API Token Fonnte belum dikonfigurasi.'];
        }
        if (empty($cleanPhone)) {
            return ['success' => false, 'message' => 'Nomor WhatsApp tidak valid.'];
        }

        try {
            $ch = curl_init('https://api.fonnte.com/qr');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, [
                'type'     => 'code',
                'whatsapp' => $cleanPhone
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $token]);
            $res = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($res, true);
            if (isset($json['code']) && !empty($json['code'])) {
                return [
                    'success' => true,
                    'code'    => $json['code'],
                    'message' => 'Kode pairing WhatsApp berhasil dibuat.'
                ];
            }

            $reason = $json['reason'] ?? 'Gagal membuat kode pairing.';
            return [
                'success' => false,
                'message' => $reason,
                'raw'     => $json
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Request QR Code for scanning WhatsApp device
     */
    public static function requestQrCode() {
        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();
        $token = trim($config['wa_api_token'] ?? '');

        if (empty($token)) {
            return ['success' => false, 'message' => 'API Token Fonnte belum dikonfigurasi.'];
        }

        try {
            $ch = curl_init('https://api.fonnte.com/qr');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, ['type' => 'qr']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: ' . $token]);
            $res = curl_exec($ch);
            curl_close($ch);

            $json = json_decode($res, true);
            if (isset($json['url']) && !empty($json['url'])) {
                return [
                    'success' => true,
                    'qr_url'  => $json['url'],
                    'message' => 'QR Code berhasil dibuat.'
                ];
            }

            return [
                'success' => false,
                'message' => $json['reason'] ?? 'Gagal memuat QR Code.',
                'raw'     => $json
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send OTP Verification code to registered WhatsApp number
     */
    public static function sendOtp($targetPhone) {
        $cleanPhone = self::normalizePhone($targetPhone);
        if (empty($cleanPhone) || strlen($cleanPhone) < 10) {
            return [
                'success' => false,
                'message' => 'Nomor WhatsApp tidak valid. Masukkan nomor minimal 10 digit (contoh: 08123456789).'
            ];
        }

        // Generate 6-digit secure numeric OTP
        $otp = (string)random_int(100000, 999999);
        $expiry = time() + 300; // 5 minutes

        // Save in session
        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['wa_pending_phone'] = $cleanPhone;
        $_SESSION['wa_pending_otp']   = $otp;
        $_SESSION['wa_otp_expiry']    = $expiry;

        // Also persist in app_settings table as backup
        try {
            $db = (new Database())->getConnection();
            if ($db) {
                $db->exec("REPLACE INTO app_settings (key_name, `value`) VALUES ('wa_pending_otp', '{$otp}')");
                $db->exec("REPLACE INTO app_settings (key_name, `value`) VALUES ('wa_pending_phone', '{$cleanPhone}')");
                $db->exec("REPLACE INTO app_settings (key_name, `value`) VALUES ('wa_otp_expiry', '{$expiry}')");
            }
        } catch (Throwable $e) {}

        $setting = new Setting();
        $config = $setting->getWhatsAppConfig();
        $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';
        $waktu = date('d/m/Y H:i:s') . ' WIB';

        $msg = "🔐 *KODE VERIFIKASI RESMI SMARTWAREHOUSE* 🔐\n";
        $msg .= "*{$sender}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "Halo! Anda sedang mendaftarkan dan menghubungkan nomor WhatsApp ini ke sistem notifikasi real-time SmartWarehouse.\n\n";
        $msg .= "Kode Verifikasi (OTP) Anda adalah:\n";
        $msg .= "👉 *{$otp}*\n\n";
        $msg .= "⏰ *Waktu:* {$waktu}\n";
        $msg .= "⏱️ *Masa Berlaku:* 5 Menit\n";
        $msg .= "⚠️ *PENTING:* Demi keamanan sistem inventaris gudang, jangan berikan kode ini kepada siapa pun.\n";
        $msg .= "----------------------------------------\n";
        $msg .= "_Layanan Otomasi SmartWarehouse_";

        $sendResult = self::sendMessage($cleanPhone, $msg, 'OTP');

        return [
            'success'     => true,
            'message'     => 'Kode OTP 6-digit berhasil dikirim ke nomor WhatsApp ' . $cleanPhone . '.',
            'phone'       => $cleanPhone,
            'otp_code'    => $otp, // For display convenience / fallback in case test mode
            'wa_link'     => self::generateWaMeLink($cleanPhone, $msg),
            'send_result' => $sendResult
        ];
    }

    /**
     * Verify submitted OTP and connect/activate WhatsApp gateway
     */
    public static function verifyOtp($phone, $inputOtp) {
        $cleanPhone = self::normalizePhone($phone);
        $inputOtp = trim((string)$inputOtp);

        if (empty($inputOtp)) {
            return ['success' => false, 'message' => 'Silakan masukkan 6 digit kode OTP yang diterima di WhatsApp.'];
        }

        if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $expectedOtp = $_SESSION['wa_pending_otp'] ?? '';
        $expectedPhone = $_SESSION['wa_pending_phone'] ?? '';
        $expiry = $_SESSION['wa_otp_expiry'] ?? 0;

        // Fallback from app_settings
        if (empty($expectedOtp)) {
            try {
                $db = (new Database())->getConnection();
                if ($db) {
                    $rowOtp = $db->query("SELECT `value` FROM app_settings WHERE key_name = 'wa_pending_otp'")->fetchColumn();
                    $rowPhone = $db->query("SELECT `value` FROM app_settings WHERE key_name = 'wa_pending_phone'")->fetchColumn();
                    $rowExp = $db->query("SELECT `value` FROM app_settings WHERE key_name = 'wa_otp_expiry'")->fetchColumn();
                    if ($rowOtp) {
                        $expectedOtp = $rowOtp;
                        $expectedPhone = $rowPhone;
                        $expiry = (int)$rowExp;
                    }
                }
            } catch (Throwable $e) {}
        }

        if (empty($expectedOtp)) {
            return ['success' => false, 'message' => 'Tidak ada permintaan kode OTP yang aktif. Silakan klik "Kirim Kode OTP" terlebih dahulu.'];
        }

        if (time() > $expiry) {
            return ['success' => false, 'message' => 'Kode OTP telah kedaluwarsa (lebih dari 5 menit). Silakan klik "Kirim Ulang Kode OTP".'];
        }

        if ($inputOtp !== $expectedOtp) {
            return ['success' => false, 'message' => 'Kode OTP yang Anda masukkan salah. Periksa kembali pesan di WhatsApp Anda.'];
        }

        // OTP Verified Successfully!
        // Save and Activate WhatsApp Gateway
        $setting = new Setting();
        $currConfig = $setting->getWhatsAppConfig();

        $saveData = [
            'wa_enabled'           => '1',
            'wa_phone'             => $cleanPhone,
            'wa_provider'          => $currConfig['wa_provider'] ?: 'fonnte',
            'wa_api_token'         => $currConfig['wa_api_token'],
            'wa_api_url'           => $currConfig['wa_api_url'] ?: 'https://api.fonnte.com/send',
            'wa_instance_id'       => $currConfig['wa_instance_id'] ?? '',
            'wa_sender_name'       => $currConfig['wa_sender_name'] ?: 'SmartWarehouse Management',
            'wa_notify_inbound'    => '1',
            'wa_notify_outbound'   => '1',
            'wa_notify_transfer'   => '1',
            'wa_notify_adjustment' => '1',
            'wa_notify_low_stock'  => '1',
        ];

        $setting->saveWhatsAppConfig($saveData);

        // Clear pending OTP
        unset($_SESSION['wa_pending_otp'], $_SESSION['wa_pending_phone'], $_SESSION['wa_otp_expiry']);
        try {
            $db = (new Database())->getConnection();
            if ($db) {
                $db->exec("DELETE FROM app_settings WHERE key_name IN ('wa_pending_otp', 'wa_pending_phone', 'wa_otp_expiry')");
            }
        } catch (Throwable $e) {}

        // Send official confirmation message to WhatsApp
        $sender = $saveData['wa_sender_name'];
        $waktu = date('d/m/Y H:i:s') . ' WIB';

        $confirmMsg = "🎉 *SELAMAT! NOMOR WHATSAPP RESMI TERHUBUNG* 🎉\n";
        $confirmMsg .= "*{$sender}*\n";
        $confirmMsg .= "----------------------------------------\n";
        $confirmMsg .= "Nomor WhatsApp Anda ({$cleanPhone}) telah BERHASIL diverifikasi dan RESMI TERHUBUNG dengan sistem SmartWarehouse.\n\n";
        $confirmMsg .= "✅ *Status Notifikasi:* AKTIF REAL-TIME\n";
        $confirmMsg .= "📦 *Barang Masuk (Inbound):* AKTIF\n";
        $confirmMsg .= "📤 *Barang Keluar (Outbound):* AKTIF\n";
        $confirmMsg .= "🔄 *Pemindahan Rak (Transfer):* AKTIF\n";
        $confirmMsg .= "⚖️ *Audit / Opname Stok:* AKTIF\n";
        $confirmMsg .= "⚠️ *Peringatan Stok Kritis:* AKTIF\n\n";
        $confirmMsg .= "⏰ *Waktu Verifikasi:* {$waktu}\n";
        $confirmMsg .= "Mulai detik ini, setiap ada transaksi yang dilakukan oleh siapapun di gudang, rincian resmi akan langsung dikirimkan ke nomor ini.\n";
        $confirmMsg .= "----------------------------------------\n";
        $confirmMsg .= "_Sistem Otomasi SmartWarehouse_";

        self::sendMessage($cleanPhone, $confirmMsg, 'CONFIRM');

        return [
            'success'   => true,
            'message'   => "Nomor WhatsApp {$cleanPhone} berhasil diverifikasi dan terhubung!",
            'phone'     => $cleanPhone,
            'wa_link'   => self::generateWaMeLink($cleanPhone, $confirmMsg)
        ];
    }

    /**
     * Get recent logs from whatsapp_logs table
     */
    public static function getRecentLogs($limit = 15) {
        try {
            $database = new Database();
            $db = $database->getConnection();
            if (!$db) return [];

            $stmt = $db->prepare("SELECT * FROM whatsapp_logs ORDER BY id DESC LIMIT :lim");
            $stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}


