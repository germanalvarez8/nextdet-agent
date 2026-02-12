<?php
/**
 * WhatsApp Webhook Endpoint
 * 
 * Este archivo es el endpoint público que debe configurarse en Meta Business Manager.
 * 
 * URL del webhook: https://tu-dominio.com/whatsapp_ag/public/webhook.php
 * 
 * Configuración en Meta:
 * 1. Ir a Meta for Developers > Tu App > WhatsApp > Configuration
 * 2. En "Webhook", hacer clic en "Edit"
 * 3. Callback URL: La URL de este archivo
 * 4. Verify Token: El mismo configurado en WHATSAPP_VERIFY_TOKEN
 * 5. Webhook fields: Subscribirse a "messages" y "message_status"
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../src/WhatsAppService.php';

// Headers para responder a Meta
header('Content-Type: application/json');

try {
    $service = new WhatsAppService();
    
    // Método GET: Verificación del webhook (handshake inicial)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Meta envía estos parámetros para verificar el webhook
        $mode = $_GET['hub_mode'] ?? '';
        $token = $_GET['hub_verify_token'] ?? '';
        $challenge = $_GET['hub_challenge'] ?? '';
        
        // Verificar el webhook
        $verifiedChallenge = $service->verifyWebhook($mode, $token, $challenge);
        
        // Retornar el challenge en texto plano (no JSON)
        header('Content-Type: text/plain');
        echo $verifiedChallenge;
        exit;
    }
    
    // Método POST: Procesamiento de eventos entrantes
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        
        // Obtener el payload JSON del body
        $input = file_get_contents('php://input');
        
        // Loggear el payload recibido (útil para debugging)
        error_log(
            "[" . date('Y-m-d H:i:s') . "] [INFO] [Webhook] Received payload: " . $input . PHP_EOL,
            3,
            LOG_FILE
        );
        
        // Procesar el webhook
        $result = $service->processWebhook($input);
        
        // Meta espera una respuesta 200 OK
        // Si no respondemos 200, Meta reintentará enviar el webhook
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'processed' => [
                'messages' => count($result['messages']),
                'statuses' => count($result['statuses'])
            ]
        ]);
        exit;
    }
    
    // Otros métodos HTTP no permitidos
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    
} catch (Exception $e) {
    // Loggear el error
    error_log(
        "[" . date('Y-m-d H:i:s') . "] [ERROR] [Webhook] " . $e->getMessage() . PHP_EOL,
        3,
        LOG_FILE
    );
    
    // Meta espera 200 incluso en caso de error para no reintentar
    // Solo retornar error si es un problema de verificación
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
    } else {
        http_response_code(200);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
