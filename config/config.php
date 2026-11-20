<?php
// Set Default Timezone to WIB (Asia/Jakarta, UTC+7)
date_default_timezone_set('Asia/Jakarta');

session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Strict'
]);
session_start();

define('BASE_URL', 'http://localhost/05092026/SmartWarehouse');
define('APP_NAME', 'SmartWarehouse');
define('GEMINI_API_KEY', 'AQ.Ab8RN6IbnrsYWXQ458T374q2Py9lL0ciPSIHy_OVp7ovVDO-Ew');