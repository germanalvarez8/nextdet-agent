# WhatsApp Business API Integration

Microservicio modular en PHP vanilla para integrar WhatsApp Business API de Meta en sistemas existentes. Diseñado específicamente para empresas de insumos mineros que necesitan enviar notificaciones de presupuestos y recibir respuestas de clientes.

## 🎯 Características

- ✅ Envío de mensajes basados en plantillas aprobadas
- ✅ Envío de mensajes de texto simple (dentro de ventana de 24h)
- ✅ Recepción de mensajes de clientes vía webhooks
- ✅ Tracking de estados de mensajes (sent, delivered, read)
- ✅ Almacenamiento en base de datos MySQL
- ✅ Verificación automática de funciones duplicadas
- ✅ Logging exhaustivo de operaciones
- ✅ Interfaz de testing con Bootstrap
- ✅ API de alto nivel para fácil integración

## 📋 Requisitos

### Software
- PHP 7.4 o superior
- MySQL 5.7+ o MariaDB 10.2+
- Extensiones PHP requeridas:
  - `curl` (para llamadas a la API)
  - `pdo_mysql` (para base de datos)
  - `json` (para parsing de respuestas)
  - `mbstring` (para manejo de caracteres especiales)

### Cuenta de WhatsApp Business
- Cuenta de Meta Business
- App de WhatsApp Business configurada
- Número de teléfono verificado
- Plantillas de mensaje aprobadas

## 🚀 Instalación

### 1. Clonar/Copiar el Proyecto

```bash
cd /tu/proyecto/existente
cp -r whatsapp_ag .
```

### 2. Configurar Base de Datos

Crear la base de datos y ejecutar el esquema:

```bash
mysql -u root -p < whatsapp_ag/database/schema.sql
```

O manualmente:

```sql
CREATE DATABASE whatsapp_integration CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Luego ejecutar el contenido de `database/schema.sql` en esa base de datos.

### 3. Configurar Credenciales

Editar `config/config.php` con tus credenciales:

```php
// Token de acceso de Meta Business Manager
define('WHATSAPP_ACCESS_TOKEN', 'EAAxxxxxxxxxxxx');

// ID del número de teléfono
define('WHATSAPP_PHONE_NUMBER_ID', '123456789012345');

// Token de verificación (elige uno aleatorio)
define('WHATSAPP_VERIFY_TOKEN', 'mi_token_secreto_12345');

// Credenciales de base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'whatsapp_integration');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. Configurar Permisos

```bash
chmod -R 755 whatsapp_ag
chmod 777 whatsapp_ag/logs
```

### 5. Verificar Instalación

Abrir en el navegador:
```
http://tu-dominio.com/whatsapp_ag/public/test_ui.php
```

Deberías ver la interfaz de testing con el estado de configuración.

## ⚙️ Configuración de Meta Business Manager

### Obtener Access Token

1. Ir a [Meta for Developers](https://developers.facebook.com/)
2. Seleccionar tu App de WhatsApp Business
3. En el menú izquierdo: **WhatsApp > API Setup**
4. Copiar el **Token de acceso permanente** (no el temporal)
5. Pegar en `WHATSAPP_ACCESS_TOKEN` en config.php

### Obtener Phone Number ID

1. En la misma página de API Setup
2. Buscar el número de teléfono configurado
3. Debajo aparece **Phone number ID**
4. Copiar y pegar en `WHATSAPP_PHONE_NUMBER_ID`

### Configurar Webhook

1. En el menú izquierdo: **WhatsApp > Configuration**
2. En la sección **Webhook**, hacer clic en **Edit**
3. Configurar:
   - **Callback URL**: `https://tu-dominio.com/whatsapp_ag/public/webhook.php`
   - **Verify token**: El mismo que configuraste en `WHATSAPP_VERIFY_TOKEN`
4. Hacer clic en **Verify and save**
5. Subscribirse a los campos:
   - ✅ `messages`
   - ✅ `message_status`

### Crear Plantillas de Mensaje

1. En Meta Business Manager: **WhatsApp Manager > Message templates**
2. Crear nueva plantilla con categoría **UTILITY**
3. Ejemplo de plantilla para presupuestos:

```
Nombre: presupuesto_insumos
Idioma: Spanish
Categoría: UTILITY

Contenido:
Estimado {{1}}, su presupuesto #{{2}} está listo. Total: {{3}}. Responda si desea proceder.
```

4. Enviar para aprobación (puede tardar 24-48 horas)
5. Una vez aprobada, registrarla en la base de datos:

```php
$service = new WhatsAppService();
$service->registerTemplate(
    'presupuesto_insumos',
    'es',
    3,  // variables_count
    'UTILITY',
    'Plantilla para enviar presupuestos a clientes',
    'Estimado {{1}}, su presupuesto #{{2}} está listo. Total: {{3}}.'
);

// Actualizar estado a aprobada
$service->updateTemplateStatus('presupuesto_insumos', 'es', 'approved');
```

## 💻 Uso Básico

### Enviar Presupuesto

```php
require_once 'whatsapp_ag/src/WhatsAppService.php';

$service = new WhatsAppService();

$service->sendBudgetNotification('5491112345678', [
    'customer_name' => 'Juan Pérez',
    'budget_id' => '12345',
    'total' => '$150,000'
]);
```

### Enviar Mensaje de Texto

```php
$service->sendTextMessage(
    '5491112345678',
    '¡Hola! Su pedido está listo para retirar.'
);
```

⚠️ **Importante**: Los mensajes de texto solo funcionan dentro de las 24 horas después de que el cliente haya enviado un mensaje.

### Obtener Historial de Conversación

```php
$messages = $service->getConversationHistory('5491112345678', 20);

foreach ($messages as $msg) {
    echo "{$msg['direction']}: {$msg['content']}\n";
}
```

### Obtener Estadísticas

```php
$stats = $service->getMessagingStats(7); // últimos 7 días

echo "Total mensajes: {$stats['total_messages']}\n";
echo "Conversaciones únicas: {$stats['unique_contacts']}\n";
```

## 🏗️ Arquitectura

### Estructura de Directorios

```
whatsapp_ag/
├── config/
│   └── config.php          # Configuración global
├── src/
│   ├── Database.php        # Utilidad de base de datos
│   ├── WhatsAppClient.php  # Cliente de la API de Meta
│   ├── WhatsAppWebhookHandler.php  # Procesador de webhooks
│   └── WhatsAppService.php # Capa de servicio (API de alto nivel)
├── database/
│   └── schema.sql          # Esquema de base de datos
├── logs/
│   └── whatsapp.log        # Logs de operaciones
├── public/
│   ├── webhook.php         # Endpoint público para webhooks
│   └── test_ui.php         # Interfaz de testing
└── README.md
```

### Clases Principales

#### WhatsAppService
**Propósito**: API de alto nivel para integración con el proyecto existente.

**Métodos principales**:
- `sendBudgetNotification($phone, $data)` - Enviar presupuesto
- `sendTextMessage($phone, $message)` - Enviar texto simple
- `getConversationHistory($phone, $limit)` - Obtener historial
- `getMessagingStats($days)` - Obtener estadísticas

#### WhatsAppClient
**Propósito**: Comunicación directa con la API de WhatsApp Cloud.

**Métodos principales**:
- `sendTemplate($to, $name, $lang, $vars)` - Enviar con plantilla
- `sendTextMessage($to, $message)` - Enviar texto
- `makeApiRequest($endpoint, $method, $data)` - Request genérico

#### WhatsAppWebhookHandler
**Propósito**: Procesar eventos entrantes de Meta.

**Métodos principales**:
- `verifyWebhook($mode, $token, $challenge)` - Verificar webhook
- `processIncoming($payload)` - Procesar mensajes/estados

#### Database
**Propósito**: Capa de abstracción para base de datos.

**Métodos principales**:
- `insert($table, $data)` - Insertar registro
- `update($table, $data, $where)` - Actualizar registro
- `select($table, $cols, $where, $order, $limit)` - Seleccionar registros

## 📊 Base de Datos

### Tabla: wa_messages

Almacena el historial completo de mensajes.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | BIGINT | PK autoincremental |
| id_wa_meta | VARCHAR(255) | ID único de WhatsApp |
| phone_number | VARCHAR(20) | Número de teléfono |
| content | TEXT | Contenido del mensaje |
| message_type | ENUM | text, template, image, etc. |
| direction | ENUM | incoming, outgoing |
| status | ENUM | sent, delivered, read, failed |
| timestamp | DATETIME | Fecha/hora del mensaje |
| metadata | JSON | Datos adicionales |

### Tabla: wa_templates

Registro de plantillas aprobadas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT | PK autoincremental |
| template_name | VARCHAR(255) | Nombre de la plantilla |
| language | VARCHAR(10) | Código de idioma |
| status | ENUM | approved, pending, rejected |
| variables_count | TINYINT | Cantidad de variables |
| category | ENUM | UTILITY, MARKETING, AUTHENTICATION |
| description | TEXT | Descripción del uso |

## 🔍 Testing

### Interfaz Web de Testing

Abrir `public/test_ui.php` en el navegador para:
- Enviar mensajes de prueba
- Ver últimos mensajes
- Verificar estado de configuración

### Testing de Webhooks

Meta proporciona una herramienta de testing:
1. Ir a **WhatsApp > API Setup** en Meta for Developers
2. Buscar la sección **Send and receive messages**
3. Usar el botón **Test** para enviar mensajes de prueba
4. Verificar que aparecen en la base de datos

### Logs

Todos los eventos se registran en `logs/whatsapp.log`:

```bash
tail -f whatsapp_ag/logs/whatsapp.log
```

Niveles de log:
- **DEBUG**: Detalles de requests/responses
- **INFO**: Operaciones normales
- **WARNING**: Advertencias (ej: funciones duplicadas)
- **ERROR**: Errores que requieren atención

## 🔧 Troubleshooting

### Error: "WHATSAPP_ACCESS_TOKEN no está configurado"

**Solución**: Editar `config/config.php` y reemplazar `YOUR_ACCESS_TOKEN_HERE` con tu token real de Meta.

### Error: "Database connection failed"

**Solución**: 
1. Verificar que MySQL está corriendo
2. Verificar credenciales en `config/config.php`
3. Verificar que la base de datos existe

### Webhook no recibe mensajes

**Solución**:
1. Verificar que la URL del webhook es accesible públicamente (HTTPS requerido en producción)
2. Verificar que el `WHATSAPP_VERIFY_TOKEN` coincide con el configurado en Meta
3. Revisar logs en `logs/whatsapp.log`
4. Usar herramienta de testing de Meta para debugear

### Error: "Error de WhatsApp API (100): Invalid parameter"

**Solución**:
1. Verificar que el nombre de la plantilla existe y está aprobado en Meta
2. Verificar que el número de variables coincide con la plantilla
3. Revisar que el formato del número de teléfono es correcto (sin + ni espacios)

### Los mensajes se envían pero no se leen en WhatsApp

**Solución**:
1. Verificar que el número de destino tiene WhatsApp instalado
2. Verificar que el número no ha bloqueado tu número de negocio
3. Esperar unos minutos, a veces hay delay

## 🔐 Seguridad

### Producción

1. **No exponer config.php**: Asegurarse de que `config.php` no sea accesible vía web
2. **HTTPS obligatorio**: Meta requiere HTTPS para webhooks en producción
3. **Validar firma del webhook**: Para máxima seguridad, validar la firma `X-Hub-Signature-256`
4. **Rotar tokens**: Cambiar Access Token periódicamente
5. **Permisos limitados**: Dar solo los permisos necesarios al usuario de BD

### Verificación de Firma (Opcional)

Para validar que los webhooks provienen de Meta, agregar en `WhatsAppWebhookHandler.php`:

```php
private function validateSignature($payload, $signature) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, APP_SECRET);
    return hash_equals($expectedSignature, $signature);
}
```

## 📚 Referencias

- [Documentación oficial de WhatsApp Cloud API](https://developers.facebook.com/docs/whatsapp/cloud-api)
- [Guía de plantillas de mensaje](https://developers.facebook.com/docs/whatsapp/message-templates)
- [Referencia de webhooks](https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks)
- [Políticas de WhatsApp Business](https://www.whatsapp.com/legal/business-policy)

## 📝 Licencia

Este microservicio es código propietario desarrollado para uso interno.

## 🆘 Soporte

Para problemas o consultas:
1. Revisar logs en `logs/whatsapp.log`
2. Consultar la documentación de Meta
3. Verificar el estado de la API de Meta en [status.fb.com](https://status.fb.com/)
