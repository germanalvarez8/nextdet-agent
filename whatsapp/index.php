<?php

/**
 * Ejemplo de uso del servicio de WhatsApp
 * Notificación de presupuesto de minería
 */

// Cargar configuración y clase
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WhatsappService.php';

// Configurar encabezados para respuesta JSON
header('Content-Type: application/json; charset=utf-8');

// Obtener credenciales desde .env
$accessToken = env('WHATSAPP_ACCESS_TOKEN');
$phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');
$testPhoneNumber = env('TEST_PHONE_NUMBER');

// Validar que las credenciales estén configuradas
if (empty($accessToken) || empty($phoneNumberId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: Las credenciales de WhatsApp no están configuradas. Revisa tu archivo .env'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Instanciar el servicio de WhatsApp
$whatsapp = new WhatsappService($accessToken, $phoneNumberId);

// Ejemplo 1: Notificación de presupuesto de minería aprobado
echo "=== EJEMPLO 1: Notificación de Presupuesto de Minería ===\n\n";

$resultado1 = $whatsapp->sendTemplateMessage(
    $testPhoneNumber,
    'presupuesto_mineria', // Nombre de tu plantilla en Meta
    'es', // Código de idioma
    [
        'Juan Pérez',           // {{1}} - Nombre del cliente
        'MIN-2024-001',         // {{2}} - Número de presupuesto
        'Extracción de Oro',    // {{3}} - Tipo de proyecto
        '$125,000 USD',         // {{4}} - Monto del presupuesto
        '15/02/2024'            // {{5}} - Fecha de validez
    ]
);

echo json_encode($resultado1, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ejemplo 2: Usando la plantilla de prueba de Meta (igual que el cURL funcional)
echo "=== EJEMPLO 2: Plantilla de Prueba de Meta (Jasper's Market) ===\n\n";

$resultado2 = $whatsapp->sendTemplateMessage(
    $testPhoneNumber,                        // Número de prueba
    'jaspers_market_order_confirmation_v1',  // Template pre-aprobado de Meta
    'en_US',                                 // Idioma inglés
    [
        'John Doe',        // Nombre del cliente
        '123456',          // Número de orden
        'Jan 27, 2026'     // Fecha
    ]
);

echo json_encode($resultado2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ejemplo 3: Enviar mensaje de texto simple (requiere ventana de 24 horas)
echo "=== EJEMPLO 3: Mensaje de Texto Simple ===\n\n";

$resultado3 = $whatsapp->sendTextMessage(
    $testPhoneNumber,
    '¡Hola! Este es un mensaje de prueba del servicio de WhatsApp.'
);

echo json_encode($resultado3, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

// Ejemplo de uso con datos dinámicos (simulando datos de base de datos)
echo "=== EJEMPLO 4: Uso con Datos Dinámicos ===\n\n";

// Simular datos de una base de datos o API
$datosPresupuesto = [
    'cliente_nombre' => 'María González',
    'presupuesto_numero' => 'MIN-2024-002',
    'proyecto_tipo' => 'Exploración de Cobre',
    'monto' => '$95,500 USD',
    'fecha_validez' => '28/02/2024'
];

$resultado4 = $whatsapp->sendTemplateMessage(
    $testPhoneNumber,
    'presupuesto_mineria',
    'es',
    [
        $datosPresupuesto['cliente_nombre'],
        $datosPresupuesto['presupuesto_numero'],
        $datosPresupuesto['proyecto_tipo'],
        $datosPresupuesto['monto'],
        $datosPresupuesto['fecha_validez']
    ]
);

echo json_encode($resultado4, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n\n";

echo "=== FIN DE LOS EJEMPLOS ===\n";
