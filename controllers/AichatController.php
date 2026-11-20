<?php
require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/SecurityHelper.php';
require_once __DIR__ . '/../helpers/WarehouseAiEngine.php';

class AichatController {

    private function getJsonInput() {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $data = json_decode($raw, true);
            if (is_array($data)) {
                return $data;
            }
        }
        return $_POST;
    }

    private function getUserContext() {
        return [
            'user_id'   => $_SESSION['user_id'] ?? null,
            'username'  => $_SESSION['username'] ?? 'User',
            'full_name' => $_SESSION['full_name'] ?? 'User',
            'role_name' => $_SESSION['role_name'] ?? 'Guest'
        ];
    }

    /**
     * Dedicated Full Page View for AI Assistant & Warehouse Control Center
     */
    public function index() {
        AuthHelper::requireLogin();

        require_once __DIR__ . '/../models/Dashboard.php';
        require_once __DIR__ . '/../models/Setting.php';

        $dashboard = new Dashboard();
        $setting   = new Setting();

        $stats = [
            'total_barang' => $dashboard->getTotalBarang(),
            'total_stok'   => $dashboard->getTotalStok(),
            'total_lokasi' => $dashboard->getTotalLokasi(),
            'stok_minimum' => count($dashboard->getBarangStokMinimum()),
            'barang_habis' => count($dashboard->getBarangHabis())
        ];

        $gemini = [
            'configured' => $setting->isGeminiConfigured(),
            'model'      => $setting->getGeminiModel(),
            'masked_key' => $setting->isGeminiConfigured() ? (substr($setting->getGeminiApiKey(), 0, 6) . '...' . substr($setting->getGeminiApiKey(), -4)) : ''
        ];

        $waConfig = $setting->getWhatsAppConfig();

        $title = "Asisten AI SmartWarehouse - " . APP_NAME;
        require_once __DIR__ . '/../views/layouts/header.php';
        require_once __DIR__ . '/../views/layouts/sidebar.php';
        require_once __DIR__ . '/../views/aichat/index.php';
        require_once __DIR__ . '/../views/layouts/footer.php';
    }

    /**
     * Process chat message from user
     */
    public function message() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Sesi login telah berakhir. Harap login kembali.'
            ]);
            exit;
        }

        $input = $this->getJsonInput();
        $message = trim($input['message'] ?? '');

        if (empty($message)) {
            echo json_encode([
                'success' => false,
                'message' => 'Pesan tidak boleh kosong.'
            ]);
            exit;
        }

        try {
            $engine = new WarehouseAiEngine();
            $response = $engine->process($message, $this->getUserContext());
            echo json_encode($response);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat memproses permintaan: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Execute any confirmed warehouse action (inbound, outbound, transfer, adjustment, create barang/lokasi/kategori)
     */
    public function executeAction() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Sesi login telah berakhir. Harap login kembali.'
            ]);
            exit;
        }

        $input = $this->getJsonInput();
        $actionData = $input['action'] ?? $input['transfer'] ?? $input;

        try {
            $engine = new WarehouseAiEngine();
            $result = $engine->executeAction($actionData, $this->getUserContext());
            echo json_encode($result);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Gagal mengeksekusi aksi: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Backward-compatible endpoint for transfer
     */
    public function executeTransfer() {
        $this->executeAction();
    }

    /**
     * Get quick suggestions & available warehouse data
     */
    public function suggestions() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false]);
            exit;
        }

        $db = (new Database())->getConnection();
        $barang = $db->query("SELECT code, name, unit FROM barang WHERE status = 'active' LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        $lokasi = $db->query("SELECT kode, nama, status FROM lokasi WHERE status != 'tidak_aktif' LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'barang'  => $barang,
            'lokasi'  => $lokasi
        ]);
        exit;
    }

    /**
     * Get Gemini API Key status and model
     */
    public function getApiKey() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        require_once '../models/Setting.php';
        $setting = new Setting();
        $apiKey = $setting->getGeminiApiKey();
        $model = $setting->getGeminiModel();
        $isConfigured = $setting->isGeminiConfigured();

        $maskedKey = '';
        if ($isConfigured) {
            $len = strlen($apiKey);
            $maskedKey = substr($apiKey, 0, 6) . '...' . substr($apiKey, -4);
        }

        echo json_encode([
            'success'          => true,
            'is_configured'    => $isConfigured,
            'masked_key'       => $maskedKey,
            'model'            => $model,
            'models_available' => [
                'gemini-3.8-flash' => 'Gemini 3.8 Flash (Direkomendasikan - Generasi Terbaru)',
                'gemini-2.0-flash' => 'Gemini 2.0 Flash (Cepat & Stabil)',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Generasi Sebelumnya)',
                'gemini-1.5-pro'   => 'Gemini 1.5 Pro (Penalaran Rumit)',
                'gemini-2.5-flash' => 'Gemini 2.5 Flash'
            ]
        ]);
        exit;
    }

    /**
     * Save & test Gemini API Key
     */
    public function saveApiKey() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input  = $this->getJsonInput();
        $apiKey = trim($input['api_key'] ?? '');
        $model  = trim($input['model'] ?? 'gemini-3.8-flash');
        if ($model === 'gemini-2.5-flash') {
            $model = 'gemini-3.8-flash';
        }

        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'API Key tidak boleh kosong.']);
            exit;
        }

        // Test connection to Gemini API
        $engine     = new WarehouseAiEngine();
        $testResult = $engine->testGeminiApiKey($apiKey, $model);

        if (!$testResult['success']) {
            echo json_encode([
                'success' => false,
                'message' => 'Gagal verifikasi API Key Gemini: ' . ($testResult['message'] ?? 'Periksa kembali API Key Anda.')
            ]);
            exit;
        }

        $actualModel = !empty($testResult['model']) ? $testResult['model'] : $model;

        require_once '../models/Setting.php';
        $setting = new Setting();
        $setting->setGeminiApiKey($apiKey);
        $setting->setGeminiModel($actualModel);

        // Update config.php if defined and writable
        $configFile = __DIR__ . '/../config/config.php';
        if (is_writable($configFile)) {
            $conf = file_get_contents($configFile);
            if (preg_match("/define\('GEMINI_API_KEY',\s*'.*?'\);/", $conf)) {
                $newConf = preg_replace("/define\('GEMINI_API_KEY',\s*'.*?'\);/", "define('GEMINI_API_KEY', '" . addslashes($apiKey) . "');", $conf);
                @file_put_contents($configFile, $newConf);
            }
        }

        require_once '../models/LogAktivitas.php';
        $log = new LogAktivitas();
        $log->record('Pengaturan AI', "Mengatur Gemini API Key aktif dengan model $model", 'AI Assistant', null);

        echo json_encode([
            'success' => true,
            'message' => 'Gemini API Key berhasil diverifikasi dan disimpan! Asisten AI kini aktif menggunakan ' . $model . '.',
            'model'   => $model
        ]);
        exit;
    }

    /**
     * Test Gemini API Key connectivity
     */
    public function testApiKey() {
        header('Content-Type: application/json; charset=utf-8');

        if (!AuthHelper::isLoggedIn()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = $this->getJsonInput();
        require_once '../models/Setting.php';
        $setting = new Setting();

        $apiKey = trim($input['api_key'] ?? '') ?: $setting->getGeminiApiKey();
        $model  = trim($input['model'] ?? '') ?: $setting->getGeminiModel();

        if (empty($apiKey)) {
            echo json_encode(['success' => false, 'message' => 'API Key belum diisi.']);
            exit;
        }

        $engine = new WarehouseAiEngine();
        $res    = $engine->testGeminiApiKey($apiKey, $model);
        echo json_encode($res);
        exit;
    }
}

