<?php
/**
 * WhatsApp Webhook Handler Class
 * 
 * Procesa eventos entrantes de WhatsApp (webhooks) enviados por Meta.
 * 
 * Funcionalidades:
 * - Verificación de webhook (handshake inicial con Meta)
 * - Procesamiento de mensajes entrantes
 * - Procesamiento de actualizaciones de estado (delivered, read)
 * - Persistencia automática en base de datos
 * 
 * @link https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks
 */

require_once __DIR__ . '/Database.php';

class WhatsAppWebhookHandler
{
    /**
     * Token de verificación del webhook
     * @var string
     */
    private $verifyToken;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        if (!defined('WHATSAPP_VERIFY_TOKEN') || WHATSAPP_VERIFY_TOKEN === 'YOUR_SECURE_RANDOM_TOKEN_HERE') {
            throw new Exception('WHATSAPP_VERIFY_TOKEN no está configurado en config.php');
        }
        
        $this->verifyToken = WHATSAPP_VERIFY_TOKEN;
    }
    
    /**
     * Verifica el webhook de WhatsApp (handshake inicial)
     * 
     * Meta realiza una petición GET con parámetros para verificar que
     * el endpoint del webhook es válido y está bajo tu control.
     * 
     * Proceso:
     * 1. Meta envía: GET webhook.php?hub.mode=subscribe&hub.verify_token=TU_TOKEN&hub.challenge=RANDOM
     * 2. Verificar que hub.mode = "subscribe"
     * 3. Verificar que hub.verify_token coincide con WHATSAPP_VERIFY_TOKEN
     * 4. Retornar hub.challenge en texto plano
     * 
     * @link https://developers.facebook.com/docs/graph-api/webhooks/getting-started#verification-requests
     * 
     * @param string $mode Modo del hub (debe ser "subscribe")
     * @param string $token Token enviado por Meta
     * @param string $challenge Challenge a retornar si la verificación es exitosa
     * @return string El challenge si la verificación es exitosa
     * @throws Exception Si la verificación falla
     */
    public function verifyWebhook($mode, $token, $challenge)
    {
        // Verificar que el modo es "subscribe"
        if ($mode !== 'subscribe') {
            $this->log("Webhook verification failed: invalid mode '{$mode}'", 'ERROR');
            throw new Exception('Modo de verificación inválido');
        }
        
        // Verificar que el token coincide
        if ($token !== $this->verifyToken) {
            $this->log("Webhook verification failed: token mismatch", 'ERROR');
            throw new Exception('Token de verificación no coincide');
        }
        
        $this->log("Webhook verified successfully", 'INFO');
        
        // Retornar el challenge
        return $challenge;
    }
    
    /**
     * Procesa eventos entrantes del webhook
     * 
     * Meta envía un payload JSON con la estructura:
     * {
     *   "object": "whatsapp_business_account",
     *   "entry": [{
     *     "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
     *     "changes": [{
     *       "value": {
     *         "messaging_product": "whatsapp",
     *         "metadata": {...},
     *         "messages": [...],  // Mensajes recibidos
     *         "statuses": [...]   // Actualizaciones de estado
     *       },
     *       "field": "messages"
     *     }]
     *   }]
     * }
     * 
     * @link https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/payload-examples
     * 
     * @param array|string $payload Payload JSON del webhook (array o string)
     * @return array Array con mensajes y estados procesados
     */
    public function processIncoming($payload)
    {
        // Si el payload llega como string, parsearlo
        if (is_string($payload)) {
            $payload = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log("Failed to parse webhook payload: " . json_last_error_msg(), 'ERROR');
                throw new Exception('Payload JSON inválido');
            }
        }
        
        $this->log("Processing webhook payload: " . json_encode($payload), 'DEBUG');
        
        $processed = [
            'messages' => [],
            'statuses' => []
        ];
        
        // Validar estructura básica
        if (!isset($payload['entry']) || !is_array($payload['entry'])) {
            $this->log("Invalid webhook payload structure: missing 'entry'", 'ERROR');
            return $processed;
        }
        
        // Procesar cada entrada
        foreach ($payload['entry'] as $entry) {
            if (!isset($entry['changes']) || !is_array($entry['changes'])) {
                continue;
            }
            
            foreach ($entry['changes'] as $change) {
                if (!isset($change['value'])) {
                    continue;
                }
                
                $value = $change['value'];
                
                // Procesar mensajes entrantes
                if (isset($value['messages']) && is_array($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $processedMessage = $this->processMessage($message, $value['metadata'] ?? []);
                        if ($processedMessage) {
                            $processed['messages'][] = $processedMessage;
                        }
                    }
                }
                
                // Procesar actualizaciones de estado
                if (isset($value['statuses']) && is_array($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $processedStatus = $this->processStatus($status);
                        if ($processedStatus) {
                            $processed['statuses'][] = $processedStatus;
                        }
                    }
                }
            }
        }
        
        $this->log(
            sprintf("Processed %d messages and %d status updates", 
                count($processed['messages']), 
                count($processed['statuses'])
            ), 
            'INFO'
        );
        
        return $processed;
    }
    
    /**
     * Procesa un mensaje individual
     * 
     * @param array $message Datos del mensaje
     * @param array $metadata Metadata del webhook
     * @return array|null Mensaje procesado o null si falla
     */
    private function processMessage($message, $metadata)
    {
        try {
            $messageId = $message['id'] ?? null;
            $from = $message['from'] ?? null;
            $timestamp = $message['timestamp'] ?? time();
            $type = $message['type'] ?? 'unknown';
            
            // Extraer contenido según el tipo de mensaje
            $content = $this->extractMessageContent($message, $type);
            
            if (!$messageId || !$from || !$content) {
                $this->log("Incomplete message data, skipping", 'WARNING');
                return null;
            }
            
            // Guardar en base de datos
            $dbId = $this->saveIncomingMessage(
                $messageId,
                $from,
                $content,
                $type,
                $timestamp,
                $metadata
            );
            
            return [
                'id' => $messageId,
                'db_id' => $dbId,
                'from' => $from,
                'content' => $content,
                'type' => $type,
                'timestamp' => date('Y-m-d H:i:s', $timestamp)
            ];
            
        } catch (Exception $e) {
            $this->log("Error processing message: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Extrae el contenido de un mensaje según su tipo
     * 
     * @param array $message Datos del mensaje
     * @param string $type Tipo de mensaje
     * @return string Contenido extraído
     */
    private function extractMessageContent($message, $type)
    {
        switch ($type) {
            case 'text':
                return $message['text']['body'] ?? '';
                
            case 'image':
                return $message['image']['caption'] ?? 'Imagen recibida';
                
            case 'document':
                return $message['document']['filename'] ?? 'Documento recibido';
                
            case 'audio':
                return 'Audio recibido';
                
            case 'video':
                return $message['video']['caption'] ?? 'Video recibido';
                
            case 'location':
                $lat = $message['location']['latitude'] ?? '';
                $lon = $message['location']['longitude'] ?? '';
                return "Ubicación: {$lat}, {$lon}";
                
            case 'contacts':
                return 'Contactos recibidos';
                
            case 'interactive':
                // Respuesta de botones o listas
                if (isset($message['interactive']['button_reply'])) {
                    return $message['interactive']['button_reply']['title'] ?? '';
                }
                if (isset($message['interactive']['list_reply'])) {
                    return $message['interactive']['list_reply']['title'] ?? '';
                }
                return 'Respuesta interactiva';
                
            default:
                return "Mensaje tipo {$type}";
        }
    }
    
    /**
     * Procesa una actualización de estado
     * 
     * Estados posibles:
     * - sent: mensaje enviado a Meta
     * - delivered: mensaje entregado al dispositivo del cliente
     * - read: mensaje leído por el cliente
     * - failed: falló el envío
     * 
     * @param array $status Datos del estado
     * @return array|null Estado procesado o null si falla
     */
    private function processStatus($status)
    {
        try {
            $messageId = $status['id'] ?? null;
            $newStatus = $status['status'] ?? null;
            $timestamp = $status['timestamp'] ?? time();
            
            if (!$messageId || !$newStatus) {
                return null;
            }
            
            // Actualizar estado en base de datos
            $updated = $this->updateMessageStatus($messageId, $newStatus);
            
            $this->log("Status update: {$messageId} -> {$newStatus}", 'DEBUG');
            
            return [
                'id' => $messageId,
                'status' => $newStatus,
                'timestamp' => date('Y-m-d H:i:s', $timestamp),
                'updated_in_db' => $updated
            ];
            
        } catch (Exception $e) {
            $this->log("Error processing status: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
    
    /**
     * Guarda un mensaje entrante en la base de datos
     * 
     * @param string $waId ID de WhatsApp del mensaje
     * @param string $from Número de teléfono del remitente
     * @param string $content Contenido del mensaje
     * @param string $type Tipo de mensaje
     * @param int $timestamp Timestamp Unix
     * @param array $metadata Metadata adicional
     * @return int ID del registro insertado
     */
    private function saveIncomingMessage($waId, $from, $content, $type, $timestamp, $metadata = [])
    {
        return Database::insert('wa_messages', [
            'id_wa_meta' => $waId,
            'phone_number' => $from,
            'content' => $content,
            'message_type' => $type,
            'direction' => 'incoming',
            'status' => null, // Los mensajes entrantes no tienen estado
            'timestamp' => date('Y-m-d H:i:s', $timestamp),
            'metadata' => !empty($metadata) ? json_encode($metadata) : null
        ]);
    }
    
    /**
     * Actualiza el estado de un mensaje en la base de datos
     * 
     * @param string $waId ID de WhatsApp del mensaje
     * @param string $newStatus Nuevo estado
     * @return int Número de filas actualizadas
     */
    private function updateMessageStatus($waId, $newStatus)
    {
        // Validar que el estado es válido
        $validStatuses = ['sent', 'delivered', 'read', 'failed'];
        if (!in_array($newStatus, $validStatuses)) {
            $this->log("Invalid status: {$newStatus}", 'WARNING');
            return 0;
        }
        
        return Database::update(
            'wa_messages',
            ['status' => $newStatus],
            ['id_wa_meta' => $waId]
        );
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
        $logMessage = "[{$timestamp}] [{$level}] [WhatsAppWebhookHandler] {$message}" . PHP_EOL;
        
        error_log($logMessage, 3, LOG_FILE);
    }
}
