<?php

/**
 * Ejemplo de envío de mensaje WhatsApp usando plantilla de Meta
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WhatsappService.php';

header('Content-Type: application/json; charset=utf-8');

$accessToken = env('WHATSAPP_ACCESS_TOKEN');
$phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
$testPhoneNumber = env('TEST_PHONE_NUMBER');

if (empty($accessToken) || empty($phoneNumberId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: Las credenciales de WhatsApp no están configuradas. Revisa tu archivo .env'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

$whatsapp = new WhatsappService($accessToken, $phoneNumberId);

// Envío usando plantilla de prueba de Meta (Jasper's Market)
$resultado = $whatsapp->sendTemplateMessage(
    $testPhoneNumber,
    'jaspers_market_order_confirmation_v1',
    'en_US',
    [
        'John Doe',
        '123456',
        'Jan 27, 2026'
    ]
);

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
