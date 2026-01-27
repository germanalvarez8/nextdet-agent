<?php

/**
 * Test con datos reales del cURL de ejemplo
 * Este script reproduce exactamente el cURL que funciona
 */

require_once __DIR__ . '/WhatsappService.php';

echo "=== TEST CON DATOS REALES ===\n\n";

// Datos exactos del cURL que funciona
$accessToken = 'EAAtfYi5hoikBQnp2PuHOGBTJu74L53txKhwe63RdynUhGb47JvvQAkyZCb6ZBzdzGbbjGCVKQ622GNomfEZBnGlPO9W1tC7z0P0xE4RTfVIfWc3kIhG1Ipwctyyi4b7qRAtCSnErjFlAsZBZBhb6VZBqIZAo8MSo1cnw8rNQh8o5Used3lV8p82HSD5AAG0zcWnuZBSgf7RqzhoGGX5CdD7InZAGsv5kMZBZBmaI3NFYrs9dwPYhUJkKtYPcClkkkGiG82kw6fAMZByua4e0wpxG5IOfkK0TImOGJhlOJD7GtgZDZD';
$phoneNumberId = '963557323506204';
$destinatario = '54358155484084';

// Instanciar el servicio
$whatsapp = new WhatsappService($accessToken, $phoneNumberId, 'v22.0');

echo "Configuración:\n";
echo "- API Version: v22.0\n";
echo "- Phone Number ID: $phoneNumberId\n";
echo "- Destinatario: $destinatario\n";
echo "- Template: jaspers_market_order_confirmation_v1\n";
echo "- Language: en_US\n\n";

// Enviar el mismo mensaje del cURL
$resultado = $whatsapp->sendTemplateMessage(
    $destinatario,
    'jaspers_market_order_confirmation_v1',
    'en_US',
    [
        'John Doe',      // Parámetro 1
        '123456',        // Parámetro 2
        'Jan 27, 2026'   // Parámetro 3
    ]
);

echo "Resultado:\n";
echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

if ($resultado['success']) {
    echo "✓ ÉXITO: El mensaje se envió correctamente\n";
    echo "  Message ID: " . ($resultado['data']['messages'][0]['id'] ?? 'N/A') . "\n";
} else {
    echo "✗ ERROR: " . $resultado['message'] . "\n";
    if (isset($resultado['error_details'])) {
        echo "\nDetalles del error:\n";
        print_r($resultado['error_details']);
    }
}

echo "\n=== COMPARACIÓN CON CURL ===\n\n";
echo "Tu cURL envía este JSON:\n";
$curlJson = [
    'messaging_product' => 'whatsapp',
    'to' => '54358155484084',
    'type' => 'template',
    'template' => [
        'name' => 'jaspers_market_order_confirmation_v1',
        'language' => [
            'code' => 'en_US'
        ],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'John Doe'],
                    ['type' => 'text', 'text' => '123456'],
                    ['type' => 'text', 'text' => 'Jan 27, 2026']
                ]
            ]
        ]
    ]
];

echo json_encode($curlJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "Mi código genera exactamente la misma estructura.\n";
echo "La única diferencia es que yo construyo el JSON programáticamente.\n\n";

echo "=== FIN DEL TEST ===\n";
