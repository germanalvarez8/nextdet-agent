<?php
/**
 * EJEMPLO DE CONFIGURACIÓN - NO USAR EN PRODUCCIÓN
 * 
 * Copiar este archivo a config.php y configurar con valores reales
 */

// WhatsApp Cloud API
define('WHATSAPP_ACCESS_TOKEN', 'YOUR_ACCESS_TOKEN_HERE');
define('WHATSAPP_PHONE_NUMBER_ID', 'YOUR_PHONE_NUMBER_ID_HERE');
define('WHATSAPP_VERIFY_TOKEN', 'YOUR_SECURE_RANDOM_TOKEN_HERE');
define('WHATSAPP_API_VERSION', 'v18.0');

// Base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'whatsapp_integration');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Logging
define('LOG_FILE', __DIR__ . '/../logs/whatsapp.log');
define('LOG_LEVEL', 'DEBUG');

// Zona horaria
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Errores PHP
if (LOG_LEVEL === 'DEBUG') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// Constantes de aplicación
define('WHATSAPP_API_BASE_URL', 'https://graph.facebook.com/' . WHATSAPP_API_VERSION);
define('API_TIMEOUT', 30);
define('API_CONNECT_TIMEOUT', 10);
