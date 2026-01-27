<?php

/**
 * API REST para el servicio de WhatsApp
 *
 * Endpoints disponibles:
 * POST /api.php?action=send_template - Enviar mensaje con plantilla
 * POST /api.php?action=send_text - Enviar mensaje de texto simple
 * GET /api.php?action=health - Verificar estado del servicio
 */

// Cargar configuración y clase
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WhatsappService.php';

// Configurar encabezados CORS y JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Enviar respuesta JSON
 */
function sendResponse($data, $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Validar API Key (opcional - puedes implementar autenticación)
 */
function validateApiKey()
{
    $apiKey = env('API_KEY');

    if (empty($apiKey)) {
        return true; // Si no hay API_KEY configurada, permitir acceso
    }

    $headers = getallheaders();
    $providedKey = $headers['Authorization'] ?? $headers['authorization'] ?? '';

    // Remover "Bearer " si existe
    $providedKey = str_replace('Bearer ', '', $providedKey);

    return $providedKey === $apiKey;
}

// Validar API Key
if (!validateApiKey()) {
    sendResponse([
        'success' => false,
        'message' => 'API Key inválida o no proporcionada',
        'error' => 'unauthorized'
    ], 401);
}

// Obtener acción
$action = $_GET['action'] ?? '';

// Verificar que las credenciales estén configuradas
$accessToken = env('WHATSAPP_ACCESS_TOKEN');
$phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID');

if (empty($accessToken) || empty($phoneNumberId)) {
    sendResponse([
        'success' => false,
        'message' => 'Servicio no configurado. Contacta al administrador.',
        'error' => 'configuration_error'
    ], 500);
}

// Instanciar servicio de WhatsApp
$whatsapp = new WhatsappService($accessToken, $phoneNumberId);

// Router de endpoints
switch ($action) {
    case 'health':
        // Endpoint para verificar que el servicio está activo
        sendResponse([
            'success' => true,
            'message' => 'Servicio de WhatsApp operativo',
            'version' => '1.0.0',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        break;

    case 'send_template':
        // Enviar mensaje con plantilla
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse([
                'success' => false,
                'message' => 'Método no permitido. Usa POST.',
                'error' => 'method_not_allowed'
            ], 405);
        }

        // Obtener datos del body
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validar datos requeridos
        if (!isset($data['phone']) || !isset($data['template'])) {
            sendResponse([
                'success' => false,
                'message' => 'Datos incompletos. Se requiere: phone, template',
                'error' => 'invalid_request',
                'required_fields' => ['phone', 'template'],
                'optional_fields' => ['language', 'params']
            ], 400);
        }

        // Extraer parámetros
        $phone = $data['phone'];
        $template = $data['template'];
        $language = $data['language'] ?? 'es';
        $params = $data['params'] ?? [];

        // Validar formato de teléfono (solo números)
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            sendResponse([
                'success' => false,
                'message' => 'Formato de teléfono inválido. Usa solo números con código de país (ej: 51999999999)',
                'error' => 'invalid_phone_format'
            ], 400);
        }

        // Enviar mensaje
        $resultado = $whatsapp->sendTemplateMessage($phone, $template, $language, $params);

        // Retornar resultado
        sendResponse($resultado, $resultado['success'] ? 200 : 400);
        break;

    case 'send_text':
        // Enviar mensaje de texto simple
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse([
                'success' => false,
                'message' => 'Método no permitido. Usa POST.',
                'error' => 'method_not_allowed'
            ], 405);
        }

        // Obtener datos del body
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validar datos requeridos
        if (!isset($data['phone']) || !isset($data['message'])) {
            sendResponse([
                'success' => false,
                'message' => 'Datos incompletos. Se requiere: phone, message',
                'error' => 'invalid_request',
                'required_fields' => ['phone', 'message']
            ], 400);
        }

        // Extraer parámetros
        $phone = $data['phone'];
        $message = $data['message'];

        // Validar formato de teléfono
        if (!preg_match('/^[0-9]{10,15}$/', $phone)) {
            sendResponse([
                'success' => false,
                'message' => 'Formato de teléfono inválido. Usa solo números con código de país (ej: 51999999999)',
                'error' => 'invalid_phone_format'
            ], 400);
        }

        // Enviar mensaje
        $resultado = $whatsapp->sendTextMessage($phone, $message);

        // Retornar resultado
        sendResponse($resultado, $resultado['success'] ? 200 : 400);
        break;

    case 'send_presupuesto':
        // Endpoint especializado para enviar presupuestos de minería
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            sendResponse([
                'success' => false,
                'message' => 'Método no permitido. Usa POST.',
                'error' => 'method_not_allowed'
            ], 405);
        }

        // Obtener datos del body
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        // Validar datos requeridos
        $requiredFields = ['phone', 'cliente_nombre', 'presupuesto_numero', 'proyecto_tipo', 'monto', 'fecha_validez'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            sendResponse([
                'success' => false,
                'message' => 'Datos incompletos',
                'error' => 'invalid_request',
                'missing_fields' => $missingFields
            ], 400);
        }

        // Enviar mensaje con plantilla de presupuesto
        $resultado = $whatsapp->sendTemplateMessage(
            $data['phone'],
            'presupuesto_mineria',
            'es',
            [
                $data['cliente_nombre'],
                $data['presupuesto_numero'],
                $data['proyecto_tipo'],
                $data['monto'],
                $data['fecha_validez']
            ]
        );

        // Retornar resultado
        sendResponse($resultado, $resultado['success'] ? 200 : 400);
        break;

    default:
        // Endpoint no encontrado
        sendResponse([
            'success' => false,
            'message' => 'Endpoint no encontrado',
            'error' => 'not_found',
            'available_endpoints' => [
                'GET /api.php?action=health' => 'Verificar estado del servicio',
                'POST /api.php?action=send_template' => 'Enviar mensaje con plantilla',
                'POST /api.php?action=send_text' => 'Enviar mensaje de texto',
                'POST /api.php?action=send_presupuesto' => 'Enviar notificación de presupuesto'
            ]
        ], 404);
        break;
}
