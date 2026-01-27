<?php

/**
 * WhatsappService - Servicio para enviar mensajes de WhatsApp usando la API de Meta
 *
 * @author Sistema de Notificaciones
 * @version 1.0.0
 */
class WhatsappService
{
    private $accessToken;
    private $phoneNumberId;
    private $apiVersion;
    private $apiUrl;

    /**
     * Constructor
     *
     * @param string $accessToken Token de acceso de la API de Meta
     * @param string $phoneNumberId ID del número de teléfono de WhatsApp Business
     * @param string $apiVersion Versión de la API (por defecto 'v22.0')
     */
    public function __construct($accessToken, $phoneNumberId, $apiVersion = 'v22.0')
    {
        $this->accessToken = $accessToken;
        $this->phoneNumberId = $phoneNumberId;
        $this->apiVersion = $apiVersion;
        $this->apiUrl = "https://graph.facebook.com/{$this->apiVersion}/{$this->phoneNumberId}/messages";
    }

    /**
     * Envía un mensaje usando una plantilla de WhatsApp
     *
     * @param string $to Número de teléfono del destinatario (con código de país, ej: 51999999999)
     * @param string $templateName Nombre de la plantilla aprobada en Meta
     * @param string $languageCode Código de idioma (por defecto 'es')
     * @param array $components Array con los valores dinámicos para los parámetros de la plantilla
     * @return array Respuesta de la API en formato JSON
     */
    public function sendTemplateMessage($to, $templateName, $languageCode = 'es', $components = [])
    {
        try {
            // Validar número de teléfono
            if (empty($to)) {
                return $this->errorResponse('El número de teléfono es requerido');
            }

            // Validar nombre de plantilla
            if (empty($templateName)) {
                return $this->errorResponse('El nombre de la plantilla es requerido');
            }

            // Construir el payload
            $payload = [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $languageCode
                    ]
                ]
            ];

            // Agregar componentes si existen
            if (!empty($components)) {
                $payload['template']['components'] = $this->buildComponents($components);
            }

            // Realizar la petición
            $response = $this->makeRequest($payload);

            return $response;

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Construye los componentes de la plantilla
     *
     * @param array $components Array con los valores dinámicos
     * @return array Componentes formateados para la API
     */
    private function buildComponents($components)
    {
        $parameters = [];

        foreach ($components as $value) {
            $parameters[] = [
                'type' => 'text',
                'text' => (string)$value
            ];
        }

        return [
            [
                'type' => 'body',
                'parameters' => $parameters
            ]
        ];
    }

    /**
     * Realiza la petición HTTP a la API de Meta
     *
     * @param array $payload Datos a enviar
     * @return array Respuesta de la API
     */
    private function makeRequest($payload)
    {
        $ch = curl_init($this->apiUrl);

        // Configurar cURL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->accessToken,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Ejecutar la petición
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Manejar errores de cURL
        if ($curlError) {
            return $this->errorResponse('Error de conexión: ' . $curlError);
        }

        // Decodificar respuesta
        $decodedResponse = json_decode($response, true);

        // Verificar código HTTP
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'message' => 'Mensaje enviado correctamente',
                'data' => $decodedResponse,
                'http_code' => $httpCode
            ];
        } else {
            $errorMessage = isset($decodedResponse['error']['message'])
                ? $decodedResponse['error']['message']
                : 'Error desconocido al enviar el mensaje';

            return [
                'success' => false,
                'message' => $errorMessage,
                'error_details' => $decodedResponse,
                'http_code' => $httpCode
            ];
        }
    }

    /**
     * Genera una respuesta de error estandarizada
     *
     * @param string $message Mensaje de error
     * @return array Respuesta de error
     */
    private function errorResponse($message)
    {
        return [
            'success' => false,
            'message' => $message,
            'data' => null,
            'http_code' => 0
        ];
    }

    /**
     * Envía un mensaje de texto simple (sin plantilla)
     * Nota: Requiere una conversación activa o ventana de 24 horas
     *
     * @param string $to Número de teléfono del destinatario
     * @param string $message Mensaje de texto a enviar
     * @return array Respuesta de la API
     */
    public function sendTextMessage($to, $message)
    {
        try {
            if (empty($to) || empty($message)) {
                return $this->errorResponse('El número de teléfono y el mensaje son requeridos');
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ];

            return $this->makeRequest($payload);

        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
