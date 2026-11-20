<?php
require_once __DIR__ . '/../config/database.php';

class Setting {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    /**
     * Get a setting by key name. Fallback to default or PHP constant.
     */
    public function get($key, $default = null) {
        try {
            $stmt = $this->db->prepare("SELECT value FROM app_settings WHERE key_name = :key LIMIT 1");
            $stmt->execute([':key' => $key]);
            $val = $stmt->fetchColumn();
            if ($val !== false && $val !== null && trim($val) !== '') {
                return $val;
            }
        } catch (Throwable $e) {
            // DB error fallback
        }

        // Constant fallback for gemini_api_key
        if ($key === 'gemini_api_key' && defined('GEMINI_API_KEY')) {
            $c = constant('GEMINI_API_KEY');
            if (!empty($c) && $c !== 'YOUR_GEMINI_API_KEY_HERE' && $c !== 'gen-lang-client-0527739822') {
                return $c;
            }
        }

        return $default;
    }

    /**
     * Save or update a setting.
     */
    public function set($key, $value) {
        try {
            $stmt = $this->db->prepare("INSERT INTO app_settings (key_name, value, updated_at) 
                VALUES (:key, :value, NOW()) 
                ON DUPLICATE KEY UPDATE value = :value_update, updated_at = NOW()");
            return $stmt->execute([
                ':key' => $key,
                ':value' => $value,
                ':value_update' => $value
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get Gemini API Key
     */
    public function getGeminiApiKey() {
        return $this->get('gemini_api_key', '');
    }

    /**
     * Set Gemini API Key
     */
    public function setGeminiApiKey($apiKey) {
        return $this->set('gemini_api_key', trim($apiKey));
    }

    /**
     * Get Gemini Model
     */
    public function getGeminiModel() {
        $model = $this->get('gemini_model', 'gemini-3.5-flash-lite');
        // If stored model is deprecated or prone to quota limits, automatically use gemini-3.5-flash-lite
        if (in_array($model, ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-2.0-flash', 'gemini-3.8-flash'])) {
            $model = 'gemini-3.5-flash-lite';
            $this->setGeminiModel($model);
        }
        return $model ?: 'gemini-3.5-flash-lite';
    }

    /**
     * Set Gemini Model
     */
    public function setGeminiModel($model) {
        return $this->set('gemini_model', trim($model));
    }

    /**
     * Check if Gemini API Key is configured and ready
     */
    public function isGeminiConfigured() {
        $key = $this->getGeminiApiKey();
        return !empty($key) && strlen($key) > 10 && $key !== 'gen-lang-client-0527739822';
    }

    /**
     * Get complete WhatsApp configuration array
     */
    public function getWhatsAppConfig() {
        return [
            'wa_enabled'          => (bool)$this->get('wa_enabled', '0'),
            'wa_phone'            => $this->get('wa_phone', ''),
            'wa_provider'         => $this->get('wa_provider', 'fonnte'),
            'wa_api_token'        => $this->get('wa_api_token', ''),
            'wa_api_url'          => $this->get('wa_api_url', 'https://api.fonnte.com/send'),
            'wa_instance_id'      => $this->get('wa_instance_id', ''),
            'wa_sender_name'      => $this->get('wa_sender_name', 'SmartWarehouse Bot'),
            'wa_notify_inbound'   => (bool)$this->get('wa_notify_inbound', '1'),
            'wa_notify_outbound'  => (bool)$this->get('wa_notify_outbound', '1'),
            'wa_notify_transfer'  => (bool)$this->get('wa_notify_transfer', '1'),
            'wa_notify_adjustment'=> (bool)$this->get('wa_notify_adjustment', '1'),
            'wa_notify_low_stock' => (bool)$this->get('wa_notify_low_stock', '1'),
        ];
    }

    /**
     * Save WhatsApp configuration array
     */
    public function saveWhatsAppConfig($data) {
        $allowed = [
            'wa_enabled', 'wa_phone', 'wa_provider', 'wa_api_token',
            'wa_api_url', 'wa_instance_id', 'wa_sender_name',
            'wa_notify_inbound', 'wa_notify_outbound', 'wa_notify_transfer',
            'wa_notify_adjustment', 'wa_notify_low_stock'
        ];
        foreach ($allowed as $key) {
            if (isset($data[$key])) {
                $val = $data[$key];
                $this->set($key, is_bool($val) ? ($val ? '1' : '0') : trim((string)$val));
            }
        }
        return true;
    }
}
