# Servicio de WhatsApp Business API

Servicio PHP independiente para enviar mensajes de WhatsApp a través de la API oficial de Cloud de Meta (WhatsApp Business API).

## Características

- PHP puro (Vanilla PHP) sin dependencias externas
- Uso de cURL para peticiones HTTP
- Manejo completo de errores de la API
- Soporte para mensajes con plantillas (templates)
- Soporte para mensajes de texto simple
- Fácil integración con servidores AWS
- Configuración mediante variables de entorno

## Requisitos

- PHP 7.4 o superior
- Extensión cURL habilitada
- Cuenta de WhatsApp Business con API Cloud de Meta
- Token de acceso (Access Token) de Meta
- Phone Number ID de WhatsApp Business

## Estructura de Archivos

```
whatsapp/
├── WhatsappService.php    # Clase principal del servicio
├── config.php             # Cargador de variables de entorno
├── index.php              # Ejemplos de uso
├── .env.example           # Plantilla de configuración
├── .env                   # Tu configuración (crear desde .env.example)
└── README.md              # Esta documentación
```

## Instalación

### 1. Copiar el archivo de configuración

```bash
cd whatsapp
cp .env.example .env
```

### 2. Configurar las credenciales

Edita el archivo `.env` y agrega tus credenciales de Meta:

```env
WHATSAPP_ACCESS_TOKEN=tu_token_de_acceso_aqui
WHATSAPP_PHONE_NUMBER_ID=tu_phone_number_id_aqui
TEST_PHONE_NUMBER=51999999999
```

### 3. Obtener credenciales de Meta

1. Ve a [Meta for Developers](https://developers.facebook.com/)
2. Crea o selecciona tu aplicación
3. Agrega el producto "WhatsApp"
4. En la sección de WhatsApp, encontrarás:
   - **Phone Number ID**: En "Números de teléfono"
   - **Access Token**: En "Configuración > Tokens de acceso"

## Uso Básico

### Enviar mensaje con plantilla

```php
<?php
require_once 'config.php';
require_once 'WhatsappService.php';

// Instanciar el servicio
$whatsapp = new WhatsappService(
    env('WHATSAPP_ACCESS_TOKEN'),
    env('WHATSAPP_PHONE_NUMBER_ID')
);

// Enviar mensaje con plantilla
$resultado = $whatsapp->sendTemplateMessage(
    '51999999999',              // Número del destinatario (con código de país)
    'presupuesto_mineria',      // Nombre de la plantilla
    'es',                       // Código de idioma
    [                           // Parámetros de la plantilla
        'Juan Pérez',
        'MIN-2024-001',
        'Extracción de Oro',
        '$125,000 USD',
        '15/02/2024'
    ]
);

// Procesar resultado
if ($resultado['success']) {
    echo "Mensaje enviado correctamente\n";
    print_r($resultado['data']);
} else {
    echo "Error: " . $resultado['message'] . "\n";
}
```

### Enviar mensaje de texto simple

```php
$resultado = $whatsapp->sendTextMessage(
    '51999999999',
    '¡Hola! Este es un mensaje de prueba.'
);
```

**Nota:** Los mensajes de texto simple solo funcionan dentro de la ventana de 24 horas después de que el usuario haya contactado a tu negocio.

## API de la Clase WhatsappService

### Constructor

```php
__construct(string $accessToken, string $phoneNumberId)
```

**Parámetros:**
- `$accessToken`: Token de acceso de la API de Meta
- `$phoneNumberId`: ID del número de teléfono de WhatsApp Business

### Método: sendTemplateMessage

```php
sendTemplateMessage(
    string $to,
    string $templateName,
    string $languageCode = 'es',
    array $components = []
): array
```

**Parámetros:**
- `$to`: Número de teléfono del destinatario con código de país (ej: 51999999999)
- `$templateName`: Nombre de la plantilla aprobada en Meta
- `$languageCode`: Código de idioma (por defecto 'es')
- `$components`: Array con valores dinámicos para los parámetros {{1}}, {{2}}, etc.

**Retorna:**
```php
[
    'success' => true|false,
    'message' => 'Descripción del resultado',
    'data' => [...],           // Datos de respuesta de la API
    'http_code' => 200
]
```

### Método: sendTextMessage

```php
sendTextMessage(string $to, string $message): array
```

**Parámetros:**
- `$to`: Número de teléfono del destinatario
- `$message`: Texto del mensaje a enviar

**Retorna:** Mismo formato que `sendTemplateMessage`

## Plantillas de WhatsApp

### Crear una plantilla en Meta

1. Ve a **Meta Business Suite** > **Configuración de cuenta**
2. Selecciona **Plantillas de mensajes de WhatsApp**
3. Crea una nueva plantilla con parámetros dinámicos

### Ejemplo de plantilla de presupuesto

**Nombre:** `presupuesto_mineria`

**Contenido:**
```
Hola {{1}},

Tu presupuesto {{2}} para el proyecto de {{3}} ha sido generado.

💰 Monto: {{4}}
📅 Válido hasta: {{5}}

Para más información, contáctanos.
```

**Parámetros:**
- {{1}} - Nombre del cliente
- {{2}} - Número de presupuesto
- {{3}} - Tipo de proyecto
- {{4}} - Monto
- {{5}} - Fecha de validez

## Integración con APIs

### Ejemplo con endpoint REST

```php
<?php
// api.php
header('Content-Type: application/json');
require_once 'config.php';
require_once 'WhatsappService.php';

// Obtener datos del POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos requeridos
if (!isset($data['phone']) || !isset($data['template']) || !isset($data['params'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Instanciar servicio
$whatsapp = new WhatsappService(
    env('WHATSAPP_ACCESS_TOKEN'),
    env('WHATSAPP_PHONE_NUMBER_ID')
);

// Enviar mensaje
$resultado = $whatsapp->sendTemplateMessage(
    $data['phone'],
    $data['template'],
    $data['language'] ?? 'es',
    $data['params']
);

// Retornar resultado
http_response_code($resultado['success'] ? 200 : 500);
echo json_encode($resultado);
```

**Uso del endpoint:**

```bash
curl -X POST http://tu-servidor.com/api.php \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "51999999999",
    "template": "presupuesto_mineria",
    "language": "es",
    "params": ["Juan Pérez", "MIN-2024-001", "Extracción de Oro", "$125,000 USD", "15/02/2024"]
  }'
```

## Despliegue en AWS

### Opción 1: EC2 con Apache/Nginx

1. **Conectar a la instancia EC2:**
```bash
ssh -i tu-clave.pem ec2-user@tu-ip-publica
```

2. **Instalar PHP y Apache:**
```bash
sudo yum update -y
sudo yum install -y httpd php
sudo systemctl start httpd
sudo systemctl enable httpd
```

3. **Subir archivos:**
```bash
scp -i tu-clave.pem -r whatsapp/ ec2-user@tu-ip:/var/www/html/
```

4. **Configurar permisos:**
```bash
sudo chown -R apache:apache /var/www/html/whatsapp
sudo chmod 644 /var/www/html/whatsapp/.env
```

### Opción 2: AWS Lambda con API Gateway

1. Comprimir archivos en un ZIP
2. Crear función Lambda con runtime PHP
3. Configurar API Gateway como trigger
4. Agregar variables de entorno en Lambda

### Opción 3: Elastic Beanstalk

1. Crear aplicación Elastic Beanstalk
2. Seleccionar plataforma PHP
3. Subir código comprimido
4. Configurar variables de entorno

## Manejo de Errores

El servicio retorna respuestas estructuradas con información detallada:

```php
// Éxito
[
    'success' => true,
    'message' => 'Mensaje enviado correctamente',
    'data' => [
        'messaging_product' => 'whatsapp',
        'contacts' => [...],
        'messages' => [...]
    ],
    'http_code' => 200
]

// Error
[
    'success' => false,
    'message' => 'Descripción del error',
    'error_details' => [...],
    'http_code' => 400
]
```

## Errores Comunes

### Error 131030: Plantilla no existe
- Verifica que el nombre de la plantilla sea correcto
- Asegúrate de que la plantilla esté aprobada en Meta

### Error 131031: Parámetros incorrectos
- Verifica que el número de parámetros coincida con la plantilla
- Los parámetros deben ser strings

### Error 401: No autorizado
- Verifica que el Access Token sea válido y no haya expirado

### Error 400: Número de teléfono inválido
- El número debe incluir el código de país
- No uses '+', espacios ni guiones

## Pruebas

Ejecutar los ejemplos:

```bash
php index.php
```

Probar con cURL:

```bash
# Desde línea de comandos
php -r "require 'config.php'; require 'WhatsappService.php'; \
  \$w = new WhatsappService(env('WHATSAPP_ACCESS_TOKEN'), env('WHATSAPP_PHONE_NUMBER_ID')); \
  var_dump(\$w->sendTemplateMessage('51999999999', 'hello_world', 'es'));"
```

## Seguridad

1. **Protege tu archivo .env:**
```bash
chmod 600 .env
```

2. **Agrega .env al .gitignore:**
```bash
echo ".env" >> .gitignore
```

3. **Usa HTTPS** en producción

4. **Valida números de teléfono** antes de enviar

5. **Implementa rate limiting** para evitar abuso

## Logs y Debugging

Para debug, puedes modificar temporalmente el método `makeRequest`:

```php
// Agregar antes de return
error_log('WhatsApp Response: ' . $response);
```

Ver logs en Apache:
```bash
tail -f /var/log/httpd/error_log
```

## Limitaciones de la API

- Mensajes de texto simple: Solo en ventana de 24 horas
- Rate limits: 1000 mensajes por segundo (puede variar según tu plan)
- Plantillas: Requieren aprobación de Meta (24-48 horas)

## Soporte y Documentación

- [Documentación oficial de WhatsApp Business API](https://developers.facebook.com/docs/whatsapp)
- [Guía de plantillas](https://developers.facebook.com/docs/whatsapp/business-management-api/message-templates)
- [Códigos de error](https://developers.facebook.com/docs/whatsapp/cloud-api/support/error-codes)

## Licencia

Este código es de uso libre para proyectos comerciales y personales.

## Autor

Sistema de Notificaciones - 2024
