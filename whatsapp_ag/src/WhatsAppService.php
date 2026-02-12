<?php
/**
 * WhatsApp Service Class
 * 
 * Capa de alto nivel para integración con el proyecto principal.
 * Proporciona una API simplificada para las operaciones más comunes.
 * 
 * Este es el punto de entrada recomendado para usar desde otras partes
 * del proyecto PHP existente.
 * 
 * Ejemplo de uso:
 * ```php
 * require_once 'src/WhatsAppService.php';
 * 
 * $service = new WhatsAppService();
 * $service->sendBudgetNotification('5491112345678', [
 *     'customer_name' => 'Juan Pérez',
 *     'budget_id' => '12345',
 *     'total' => '$150,000'
 * ]);
 * ```
 */

require_once __DIR__ . '/WhatsAppClient.php';
require_once __DIR__ . '/WhatsAppWebhookHandler.php';
require_once __DIR__ . '/Database.php';

class WhatsAppService
{
    /**
     * Cliente de WhatsApp
     * @var WhatsAppClient
     */
    private $client;
    
    /**
     * Handler de webhooks
     * @var WhatsAppWebhookHandler
     */
    private $webhookHandler;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->client = new WhatsAppClient();
        $this->webhookHandler = new WhatsAppWebhookHandler();
    }
    
    /**
     * Envía una notificación de presupuesto a un cliente
     * 
     * Esta es una función de alto nivel diseñada específicamente para
     * el caso de uso de la empresa de insumos mineros.
     * 
     * @param string $phoneNumber Número de teléfono del cliente
     * @param array $budgetData Datos del presupuesto con las claves:
     *                          - customer_name: Nombre del cliente
     *                          - budget_id: ID del presupuesto
     *                          - total: Monto total
     *                          - additional_info: (opcional) Info adicional
     * @return array Respuesta de la API
     * @throws Exception Si falla el envío
     */
    public function sendBudgetNotification($phoneNumber, $budgetData)
    {
        // Validar datos requeridos
        $requiredFields = ['customer_name', 'budget_id', 'total'];
        foreach ($requiredFields as $field) {
            if (!isset($budgetData[$field])) {
                throw new Exception("Campo requerido faltante: {$field}");
            }
        }
        
        // Nombre de la plantilla (debe existir en Meta Business Manager)
        // Ajustar según el nombre real de tu plantilla
        $templateName = 'jaspers_market_image_cta_v1';
        
        // Variables para la plantilla
        // El orden debe coincidir con {{1}}, {{2}}, {{3}} en la plantilla de Meta
        $variables = [
            $budgetData['customer_name'],  // {{1}}
            $budgetData['budget_id'],      // {{2}}
            $budgetData['total']           // {{3}}
        ];
        
        // Si la plantilla tiene más variables, agregarlas aquí
        if (isset($budgetData['additional_info'])) {
            $variables[] = $budgetData['additional_info']; // {{4}}
        }
        
        // Enviar mensaje usando la plantilla
        return $this->client->sendTemplate(
            $phoneNumber,
            $templateName,
            'es', // Idioma español
            $variables
        );
    }
    
    /**
     * Envía un mensaje de texto simple a un cliente
     * 
     * ADVERTENCIA: Solo funciona dentro de la ventana de 24 horas después
     * de que el cliente haya enviado un mensaje. Para notificaciones
     * proactivas, usar plantillas.
     * 
     * @param string $phoneNumber Número de teléfono del cliente
     * @param string $message Contenido del mensaje
     * @return array Respuesta de la API
     */
    public function sendTextMessage($phoneNumber, $message)
    {
        return $this->client->sendTextMessage($phoneNumber, $message);
    }
    
    /**
     * Obtiene el historial de conversación con un cliente
     * 
     * @param string $phoneNumber Número de teléfono del cliente
     * @param int $limit Número máximo de mensajes a retornar (default: 50)
     * @return array Array de mensajes ordenados por fecha (más reciente primero)
     */
    public function getConversationHistory($phoneNumber, $limit = 50)
    {
        return Database::select(
            'wa_messages',
            ['id', 'id_wa_meta', 'content', 'message_type', 'direction', 'status', 'timestamp', 'metadata'],
            ['phone_number' => $phoneNumber],
            'timestamp DESC',
            $limit
        );
    }
    
    /**
     * Obtiene los mensajes más recientes de todos los clientes
     * 
     * Útil para mostrar en dashboards o interfaces de administración.
     * 
     * @param int $limit Número de mensajes a retornar (default: 10)
     * @return array Array de mensajes
     */
    public function getRecentMessages($limit = 10)
    {
        return Database::select(
            'wa_messages',
            ['id', 'id_wa_meta', 'phone_number', 'content', 'message_type', 'direction', 'status', 'timestamp'],
            [],
            'timestamp DESC',
            $limit
        );
    }
    
    /**
     * Obtiene todas las plantillas aprobadas disponibles
     * 
     * @return array Array de plantillas
     */
    public function getApprovedTemplates()
    {
        return Database::select(
            'wa_templates',
            [],
            ['status' => 'approved'],
            'template_name ASC'
        );
    }
    
    /**
     * Registra una nueva plantilla en la base de datos
     * 
     * NOTA: Esto solo registra la plantilla en la base de datos local.
     * La plantilla debe crearse y aprobarse PRIMERO en Meta Business Manager.
     * 
     * @param string $name Nombre de la plantilla (debe coincidir con Meta)
     * @param string $language Código de idioma (ej: 'es', 'es_AR')
     * @param int $varCount Número de variables que acepta la plantilla
     * @param string $category Categoría (UTILITY, MARKETING, AUTHENTICATION)
     * @param string $description Descripción del uso
     * @param string $exampleContent Contenido de ejemplo
     * @return int ID del registro insertado
     */
    public function registerTemplate(
        $name, 
        $language = 'es', 
        $varCount = 0, 
        $category = 'UTILITY',
        $description = '',
        $exampleContent = ''
    ) {
        // Validar categoría
        $validCategories = ['UTILITY', 'MARKETING', 'AUTHENTICATION'];
        if (!in_array($category, $validCategories)) {
            throw new Exception("Categoría inválida. Debe ser: " . implode(', ', $validCategories));
        }
        
        return Database::insert('wa_templates', [
            'template_name' => $name,
            'language' => $language,
            'status' => 'pending', // Cambiar a 'approved' cuando Meta apruebe
            'variables_count' => $varCount,
            'category' => $category,
            'description' => $description,
            'example_content' => $exampleContent
        ]);
    }
    
    /**
     * Actualiza el estado de una plantilla
     * 
     * @param string $name Nombre de la plantilla
     * @param string $language Idioma de la plantilla
     * @param string $newStatus Nuevo estado (approved, pending, rejected)
     * @return int Número de filas actualizadas
     */
    public function updateTemplateStatus($name, $language, $newStatus)
    {
        $validStatuses = ['approved', 'pending', 'rejected'];
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Estado inválido. Debe ser: " . implode(', ', $validStatuses));
        }
        
        return Database::update(
            'wa_templates',
            ['status' => $newStatus],
            ['template_name' => $name, 'language' => $language]
        );
    }
    
    /**
     * Obtiene estadísticas de mensajería
     * 
     * @param int $days Número de días a analizar (default: 7)
     * @return array Estadísticas
     */
    public function getMessagingStats($days = 7)
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // Total de mensajes
        $totalQuery = Database::query(
            "SELECT COUNT(*) as total FROM wa_messages WHERE timestamp >= ?",
            [$since]
        );
        $total = $totalQuery->fetch()['total'] ?? 0;
        
        // Mensajes por dirección
        $directionQuery = Database::query(
            "SELECT direction, COUNT(*) as count FROM wa_messages WHERE timestamp >= ? GROUP BY direction",
            [$since]
        );
        $byDirection = [];
        while ($row = $directionQuery->fetch()) {
            $byDirection[$row['direction']] = $row['count'];
        }
        
        // Mensajes por estado
        $statusQuery = Database::query(
            "SELECT status, COUNT(*) as count FROM wa_messages WHERE timestamp >= ? AND status IS NOT NULL GROUP BY status",
            [$since]
        );
        $byStatus = [];
        while ($row = $statusQuery->fetch()) {
            $byStatus[$row['status']] = $row['count'];
        }
        
        // Conversaciones únicas (clientes únicos)
        $uniqueQuery = Database::query(
            "SELECT COUNT(DISTINCT phone_number) as unique_contacts FROM wa_messages WHERE timestamp >= ?",
            [$since]
        );
        $uniqueContacts = $uniqueQuery->fetch()['unique_contacts'] ?? 0;
        
        return [
            'period_days' => $days,
            'since' => $since,
            'total_messages' => $total,
            'by_direction' => $byDirection,
            'by_status' => $byStatus,
            'unique_contacts' => $uniqueContacts
        ];
    }
    
    /**
     * Procesa un webhook entrante
     * 
     * Delega al WebhookHandler pero proporciona una interfaz simplificada.
     * 
     * @param array|string $payload Payload del webhook
     * @return array Mensajes y estados procesados
     */
    public function processWebhook($payload)
    {
        return $this->webhookHandler->processIncoming($payload);
    }
    
    /**
     * Verifica un webhook
     * 
     * @param string $mode Modo del hub
     * @param string $token Token de verificación
     * @param string $challenge Challenge a retornar
     * @return string Challenge si es exitoso
     */
    public function verifyWebhook($mode, $token, $challenge)
    {
        return $this->webhookHandler->verifyWebhook($mode, $token, $challenge);
    }
}
