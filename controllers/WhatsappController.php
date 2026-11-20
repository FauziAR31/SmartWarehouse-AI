<?php
require_once '../helpers/AuthHelper.php';
require_once '../helpers/SecurityHelper.php';
require_once '../helpers/WhatsAppHelper.php';
require_once '../models/Setting.php';
require_once '../models/LogAktivitas.php';

class WhatsappController {

    private $settingModel;

    public function __construct() {
        // Only Admin and Supervisor can access WhatsApp Configuration
        AuthHelper::requireRole(['Admin', 'Supervisor']);
        $this->settingModel = new Setting();
    }

    /**
     * Display WhatsApp Gateway Settings & Real-time Logs
     */
    public function index() {
        $config = $this->settingModel->getWhatsAppConfig();
        $logs = WhatsAppHelper::getRecentLogs(25);
        $deviceStatus = WhatsAppHelper::getDeviceStatus();
        $title = "Pengaturan Notifikasi WhatsApp - " . APP_NAME;

        require_once '../views/layouts/header.php';
        require_once '../views/layouts/sidebar.php';
        require_once '../views/whatsapp/index.php';
        require_once '../views/layouts/footer.php';
    }

    /**
     * Check real-time WhatsApp Device status (AJAX)
     */
    public function deviceStatus() {
        header('Content-Type: application/json; charset=utf-8');
        $status = WhatsAppHelper::getDeviceStatus();
        echo json_encode($status);
        exit;
    }

    /**
     * Send OTP Verification code to registered WhatsApp number (AJAX)
     */
    public function sendOtp() {
        header('Content-Type: application/json; charset=utf-8');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'Nomor WhatsApp wajib diisi.']);
            exit;
        }

        $result = WhatsAppHelper::sendOtp($phone);

        $log = new LogAktivitas();
        $log->record(
            'WhatsApp OTP',
            "Permintaan kode OTP verifikasi WhatsApp untuk {$result['phone']} - Status: " . ($result['success'] ? 'Terkirim' : 'Gagal'),
            'WhatsApp',
            null
        );

        echo json_encode($result);
        exit;
    }

    /**
     * Verify OTP code and connect/activate WhatsApp gateway (AJAX)
     */
    public function verifyOtp() {
        header('Content-Type: application/json; charset=utf-8');
        $phone = trim($_POST['phone'] ?? '');
        $otp = trim($_POST['otp'] ?? '');

        $result = WhatsAppHelper::verifyOtp($phone, $otp);

        if (!empty($result['success'])) {
            $log = new LogAktivitas();
            $log->record(
                'WhatsApp Terverifikasi',
                "Nomor WhatsApp {$result['phone']} berhasil diverifikasi dan terhubung ke sistem.",
                'WhatsApp',
                null
            );
        }

        echo json_encode($result);
        exit;
    }

    /**
     * Request 8-character Pairing Code from Fonnte (AJAX)
     */
    public function requestPairingCode() {
        header('Content-Type: application/json; charset=utf-8');
        $phone = trim($_POST['phone'] ?? '');
        $result = WhatsAppHelper::requestPairingCode($phone);
        echo json_encode($result);
        exit;
    }

    /**
     * Request QR Code from Fonnte (AJAX)
     */
    public function requestQrCode() {
        header('Content-Type: application/json; charset=utf-8');
        $result = WhatsAppHelper::requestQrCode();
        echo json_encode($result);
        exit;
    }

    /**
     * Save WhatsApp Gateway Settings
     */
    public function save() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: " . BASE_URL . "/whatsapp/index");
            exit;
        }

        $data = [
            'wa_enabled'           => isset($_POST['wa_enabled']) ? '1' : '0',
            'wa_phone'             => trim($_POST['wa_phone'] ?? ''),
            'wa_provider'          => trim($_POST['wa_provider'] ?? 'fonnte'),
            'wa_api_token'         => trim($_POST['wa_api_token'] ?? ''),
            'wa_api_url'           => trim($_POST['wa_api_url'] ?? ''),
            'wa_instance_id'       => trim($_POST['wa_instance_id'] ?? ''),
            'wa_sender_name'       => trim($_POST['wa_sender_name'] ?? 'SmartWarehouse Management'),
            'wa_notify_inbound'    => isset($_POST['wa_notify_inbound']) ? '1' : '0',
            'wa_notify_outbound'   => isset($_POST['wa_notify_outbound']) ? '1' : '0',
            'wa_notify_transfer'   => isset($_POST['wa_notify_transfer']) ? '1' : '0',
            'wa_notify_adjustment' => isset($_POST['wa_notify_adjustment']) ? '1' : '0',
            'wa_notify_low_stock'  => isset($_POST['wa_notify_low_stock']) ? '1' : '0',
        ];

        $this->settingModel->saveWhatsAppConfig($data);

        $log = new LogAktivitas();
        $log->record(
            'Pengaturan WhatsApp',
            "Memperbarui konfigurasi WhatsApp Gateway (Status: " . ($data['wa_enabled'] ? 'Aktif' : 'Nonaktif') . ", Provider: {$data['wa_provider']})",
            'WhatsApp',
            null
        );

        $_SESSION['success_message'] = "Pengaturan notifikasi WhatsApp berhasil disimpan!";
        header("Location: " . BASE_URL . "/whatsapp/index");
        exit;
    }

    /**
     * Send test message via AJAX
     */
    public function test() {
        header('Content-Type: application/json; charset=utf-8');

        $phone = trim($_POST['phone'] ?? '');
        $result = WhatsAppHelper::sendTestMessage($phone);

        $log = new LogAktivitas();
        $statusStr = !empty($result['success']) ? 'Berhasil' : 'Gagal';
        $log->record(
            'Uji Coba WhatsApp',
            "Mengirim pesan uji coba ke $phone - Status: $statusStr",
            'WhatsApp',
            null
        );

        echo json_encode($result);
        exit;
    }

    /**
     * Get latest WhatsApp delivery logs (JSON)
     */
    public function getLogs() {
        header('Content-Type: application/json; charset=utf-8');
        $limit = isset($_GET['limit']) ? min(100, max(5, (int)$_GET['limit'])) : 25;
        $logs = WhatsAppHelper::getRecentLogs($limit);
        echo json_encode([
            'success' => true,
            'logs'    => $logs
        ]);
        exit;
    }

    /**
     * Generate or send WhatsApp receipt for specific transaction ID (AJAX)
     */
    public function sendReceipt() {
        header('Content-Type: application/json; charset=utf-8');

        $trxId = (int)($_POST['trx_id'] ?? $_GET['trx_id'] ?? 0);
        $targetPhone = trim($_POST['target_phone'] ?? $_GET['target_phone'] ?? '');

        if ($trxId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID Transaksi tidak valid.']);
            exit;
        }

        require_once '../models/Transaksi.php';
        $transaksiModel = new Transaksi();
        $trx = $transaksiModel->getDetailById($trxId);

        if (!$trx) {
            echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
            exit;
        }

        $config = $this->settingModel->getWhatsAppConfig();
        $sender = $config['wa_sender_name'] ?: 'SmartWarehouse Management';
        $waktu = date('d/m/Y H:i', strtotime($trx['transaction_date'])) . ' WIB';
        $qty = number_format((int)$trx['qty']);
        $unit = $trx['barang_unit'] ?: 'unit';

        $typeLabel = 'TRANSAKSI GUDANG';
        if ($trx['type'] === 'in') $typeLabel = 'BARANG MASUK (INBOUND)';
        elseif ($trx['type'] === 'out') $typeLabel = 'BARANG KELUAR (OUTBOUND)';
        elseif ($trx['type'] === 'transfer') $typeLabel = 'PEMINDAHAN RAK (TRANSFER)';

        $msg = "📋 *BUKTI RESMI {$typeLabel}* 📋\n";
        $msg .= "*{$sender}*\n";
        $msg .= "----------------------------------------\n";
        $msg .= "📄 *No. Transaksi:* `{$trx['transaction_number']}`\n";
        $msg .= "⏰ *Waktu:* {$waktu}\n";
        $msg .= "👤 *Operator:* {$trx['operator']}\n";
        if ($trx['type'] === 'in' && !empty($trx['supplier_name'])) {
            $msg .= "🏢 *Supplier:* {$trx['supplier_name']}\n";
        }
        if ($trx['type'] === 'out' && !empty($trx['recipient_name'])) {
            $msg .= "🏢 *Penerima:* {$trx['recipient_name']}\n";
        }
        $msg .= "🏷️ *Material:* {$trx['barang_name']} ({$trx['barang_code']})\n";
        $msg .= "📍 *Rak Asal:* {$trx['lokasi_asal_kode']}\n";
        if ($trx['type'] === 'transfer' && !empty($trx['lokasi_tujuan_kode'])) {
            $msg .= "➡️ *Rak Tujuan:* {$trx['lokasi_tujuan_kode']}\n";
        }
        $msg .= "📊 *Jumlah:* {$qty} {$unit}\n";
        if (!empty($trx['notes'])) {
            $msg .= "📝 *Catatan:* {$trx['notes']}\n";
        }
        $msg .= "----------------------------------------\n";
        $msg .= "_Dokumen digital resmi SmartWarehouse_";

        $recipient = !empty($targetPhone) ? $targetPhone : $config['wa_phone'];
        $waLink = WhatsAppHelper::generateWaMeLink($recipient, $msg);

        // If direct send requested
        $sendResult = null;
        if (!empty($_POST['direct_send'])) {
            $sendResult = WhatsAppHelper::sendMessage($recipient, $msg, 'RECEIPT', $trx['transaction_number']);
        }

        echo json_encode([
            'success'     => true,
            'message'     => $msg,
            'wa_link'     => $waLink,
            'recipient'   => $recipient,
            'send_result' => $sendResult
        ]);
        exit;
    }
}
