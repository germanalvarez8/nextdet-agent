<?php
/**
 * WhatsApp Client Class
 * 
 * Cliente para interactuar con la API de WhatsApp Cloud de Meta.
 * Gestiona el envío de mensajes y comunicación con la API.
 * 
 * Funcionalidades:
 * - Envío de mensajes basados en plantillas
 * - Envío de mensajes de texto simple
 * - Verificación de funciones existentes para evitar duplicidad
 * - Registro automático de mensajes en base de datos
 * - Manejo robusto de errores con logging
 * 
 * @link https://developers.facebook.com/docs/whatsapp/cloud-api
 */

require_once __DIR__ . '/Database.php';

class WhatsAppClient
{
    /**
     * Token de acceso a la API
     * @var string
     */
    private $accessToken;
    
    /**
     * ID del número de teléfono de WhatsApp Business
     * @var string
     */
    private $phoneNumberId;
    
    /**
     * URL base de la API
     * @var string
     */
    private $apiBaseUrl;
    
    /**
     * Bandera que indica si ya existe otra implementación de WhatsApp
     * @var bool
     */
    private $duplicateFunctionsDetected = false;
    
    /**
     * Constructor
     * 
     * @throws Exception Si faltan credenciales o se detectan funciones duplicadas
     */
    public function __construct()
    {
        // Validar configuración
        if (!defined('WHATSAPP_ACCESS_TOKEN') || WHATSAPP_ACCESS_TOKEN === 'YOUR_ACCESS_TOKEN_HERE') {
            throw new Exception('WHATSAPP_ACCESS_TOKEN no está configurado en config.php');
        }
        
        if (!defined('WHATSAPP_PHONE_NUMBER_ID') || WHATSAPP_PHONE_NUMBER_ID === 'YOUR_PHONE_NUMBER_ID_HERE') {
            throw new Exception('WHATSAPP_PHONE_NUMBER_ID no está configurado en config.php');
        }
        
        $this->accessToken = WHATSAPP_ACCESS_TOKEN;
        $this->phoneNumberId = WHATSAPP_PHONE_NUMBER_ID;
        $this->apiBaseUrl = WHATSAPP_API_BASE_URL;
        
        // Verificar funciones duplicadas
        $this->checkExistingFunctions();
        
        $this->log('WhatsAppClient initialized successfully', 'INFO');
    }
    
    /**
     * Verifica si ya existen funciones de WhatsApp en el entorno
     * para evitar duplicidad y conflictos
     */
    private function checkExistingFunctions()
    {
        $existingFunctions = [];
        $allFunctions = get_defined_functions();
        $userFunctions = $allFunctions['user'];
        
        // Buscar funciones con prefijos relacionados a WhatsApp
        foreach ($userFunctions as $function) {
            if (stripos($function, 'whatsapp') !== false || 
                stripos($function, 'wa_') === 0 ||
                stripos($function, '_wa_') !== false) {
                $existingFunctions[] = $function;
            }
        }
        
        // Buscar clases existentes
        $existingClasses = [];
        $allClasses = get_declared_classes();
        
        foreach ($allClasses as $class) {
            if (stripos($class, 'WhatsApp') !== false && $class !== 'WhatsAppClient') {
                $existingClasses[] = $class;
            }
        }
        
        if (!empty($existingFunctions) || !empty($existingClasses)) {
            $this->duplicateFunctionsDetected = true;
            
            $warning = "ADVERTENCIA: Se detectaron funciones/clases de WhatsApp existentes:\n";
            if (!empty($existingFunctions)) {
                $warning .= "Funciones: " . implode(', ', $existingFunctions) . "\n";
            }
            if (!empty($existingClasses)) {
                $warning .= "Clases: " . implode(', ', $existingClasses) . "\n";
            }
            $warning .= "Revisar compatibilidad antes de usar este cliente.";
            
            $this->log($warning, 'WARNING');
        }
    }
    
    /**
     * Envía un mensaje basado en una plantilla aprobada
     * 
     * Las plantillas deben estar pre-aprobadas en Meta Business Manager.
     * 
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-message-templates
     * 
     * @param string $to Número de teléfono destinatario (formato internacional sin +)
     *                   Ejemplo: "5491112345678" para Argentina
     * @param string $templateName Nombre exacto de la plantilla en Meta
     * @param string $language Código de idioma (ej: "es", "es_AR", "en_US")
     * @param array $variables Array de variables para la plantilla (ej: ["Juan", "12345", "$5000"])
     *                         El orden debe coincidir con {{1}}, {{2}}, {{3}} en la plantilla
     * @return array Respuesta de la API con el ID del mensaje
     * @throws Exception Si falla el envío
     */
    public function sendTemplate($to, $templateName, $language = 'es', $variables = [])
    {
        // Validar número de teléfono
        $to = $this->validatePhoneNumber($to);
        
        // Construir componentes de la plantilla
        $components = [];
        
        if (!empty($variables)) {
            $parameters = [];
            foreach ($variables as $variable) {
                $parameters[] = [
                    'type' => 'text',
                    'text' => (string) $variable
                ];
            }
            
            $components[] = [
                'type' => 'body',
                'parameters' => $parameters
            ];
        }
        
        // Construir payload según documentación de Meta
        // https://developers.facebook.com/docs/whatsapp/cloud-api/reference/messages
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language
                ],
                'components' => $components
            ]
        ];
        
        // Enviar a la API
        $endpoint = "/{$this->phoneNumberId}/messages";
        $response = $this->makeApiRequest($endpoint, 'POST', $payload);
        
        // Registrar en base de datos
        $this->saveOutgoingMessage(
            $to,
            $templateName,
            'template',
            $response['messages'][0]['id'] ?? null,
            ['template' => $templateName, 'variables' => $variables]
        );
        
        $this->log("Template message sent to {$to}: {$templateName}", 'INFO');
        
        return $response;
    }
    
    /**
     * Envía un mensaje de texto simple
     * 
     * IMPORTANTE: WhatsApp tiene restricciones para mensajes de texto.
     * Solo se pueden enviar durante las 24 horas siguientes a que el cliente
     * haya enviado un mensaje. Fuera de esa ventana, usar plantillas.
     * 
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/guides/send-messages
     * 
     * @param string $to Número de teléfono destinatario (formato internacional sin +)
     * @param string $message Contenido del mensaje (max 4096 caracteres)
     * @param bool $preview_url Si se debe generar preview de URLs (default: false)
     * @return array Respuesta de la API
     * @throws Exception Si falla el envío
     */
    public function sendTextMessage($to, $message, $preview_url = false)
    {
        $to = $this->validatePhoneNumber($to);
        
        // Validar longitud del mensaje
        if (strlen($message) > 4096) {
            throw new Exception('El mensaje excede el límite de 4096 caracteres');
        }
        
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => $preview_url,
                'body' => $message
            ]
        ];
        
        $endpoint = "/{$this->phoneNumberId}/messages";
        $response = $this->makeApiRequest($endpoint, 'POST', $payload);
        
        // Registrar en base de datos
        $this->saveOutgoingMessage(
            $to,
            $message,
            'text',
            $response['messages'][0]['id'] ?? null
        );
        
        $this->log("Text message sent to {$to}", 'INFO');
        
        return $response;
    }
    
    /**
     * Realiza una petición HTTP a la API de WhatsApp Cloud
     * 
     * @param string $endpoint Ruta del endpoint (ej: "/messages")
     * @param string $method Método HTTP (GET, POST, etc.)
     * @param array $data Datos a enviar (se convertirán a JSON)
     * @return array Respuesta parseada de la API
     * @throws Exception Si falla la petición
     */
    private function makeApiRequest($endpoint, $method = 'POST', $data = [])
    {
        $url = $this->apiBaseUrl . $endpoint;
        
        $ch = curl_init();
        
        // Configuración de cURL
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => API_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => API_CONNECT_TIMEOUT,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
        ]);
        
        // Headers requeridos por WhatsApp Cloud API
        $headers = [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Agregar body si es POST/PUT
        if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
            $jsonData = json_encode($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            
            $this->log("API Request: {$method} {$url} | Payload: {$jsonData}", 'DEBUG');
        }
        
        // Ejecutar petición
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        curl_close($ch);
        
        // Manejar errores de cURL
        if ($curlError) {
            $this->log("cURL Error: {$curlError}", 'ERROR');
            throw new Exception("Error de conexión con WhatsApp API: {$curlError}");
        }
        
        // Parsear respuesta JSON
        $responseData = json_decode($response, true);
        
        // Manejar errores HTTP
        if ($httpCode >= 400) {
            $errorMessage = $responseData['error']['message'] ?? 'Error desconocido';
            $errorCode = $responseData['error']['code'] ?? $httpCode;
            
            $this->log("API Error {$errorCode}: {$errorMessage} | Response: {$response}", 'ERROR');
            throw new Exception("Error de WhatsApp API ({$errorCode}): {$errorMessage}");
        }
        
        $this->log("API Response: {$httpCode} | " . substr($response, 0, 500), 'DEBUG');
        
        return $responseData;
    }
    
    /**
     * Valida y normaliza un número de teléfono
     * 
     * @param string $phoneNumber Número de teléfono
     * @return string Número normalizado
     * @throws Exception Si el formato es inválido
     */
    private function validatePhoneNumber($phoneNumber)
    {
        // Eliminar caracteres no numéricos excepto el +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Eliminar el + inicial si existe
        $cleaned = ltrim($cleaned, '+');
        
        // Validar longitud (entre 10 y 15 dígitos según E.164)
        if (strlen($cleaned) < 10 || strlen($cleaned) > 15) {
            throw new Exception("Número de teléfono inválido: {$phoneNumber}. Debe tener entre 10 y 15 dígitos.");
        }
        
        return $cleaned;
    }
    
    /**
     * Guarda un mensaje saliente en la base de datos
     * 
     * @param string $to Número de teléfono destinatario
     * @param string $content Contenido del mensaje
     * @param string $type Tipo de mensaje
     * @param string|null $waId ID de WhatsApp del mensaje
     * @param array $metadata Metadata adicional
     */
    private function saveOutgoingMessage($to, $content, $type, $waId = null, $metadata = [])
    {
        try {
            Database::insert('wa_messages', [
                'id_wa_meta' => $waId,
                'phone_number' => $to,
                'content' => $content,
                'message_type' => $type,
                'direction' => 'outgoing',
                'status' => 'sent',
                'timestamp' => date('Y-m-d H:i:s'),
                'metadata' => !empty($metadata) ? json_encode($metadata) : null
            ]);
        } catch (Exception $e) {
            $this->log("Failed to save outgoing message: " . $e->getMessage(), 'ERROR');
            // No lanzar excepción para no interrumpir el flujo principal
        }
    }
    
    /**
     * Registra eventos en el archivo de log
     * 
     * @param string $message Mensaje a loggear
     * @param string $level Nivel de log (DEBUG, INFO, ERROR, WARNING)
     */
    private function log($message, $level = 'INFO')
    {
        $levels = ['DEBUG' => 1, 'INFO' => 2, 'WARNING' => 2, 'ERROR' => 3];
        $currentLevel = $levels[LOG_LEVEL] ?? 2;
        $messageLevel = $levels[$level] ?? 2;
        
        if ($messageLevel < $currentLevel) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] [WhatsAppClient] {$message}" . PHP_EOL;
        
        error_log($logMessage, 3, LOG_FILE);
    }
}
