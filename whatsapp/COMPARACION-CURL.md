# Comparación: cURL vs WhatsappService PHP

## Tu cURL Funcional

```bash
curl -i -X POST \
  https://graph.facebook.com/v22.0/963557323506204/messages \
  -H 'Authorization: Bearer EAAtfYi5hoikBQ...' \
  -H 'Content-Type: application/json' \
  -d '{
    "messaging_product": "whatsapp",
    "to": "54358155484084",
    "type": "template",
    "template": {
      "name": "jaspers_market_order_confirmation_v1",
      "language": {
        "code": "en_US"
      },
      "components": [{
        "type": "body",
        "parameters": [
          { "type": "text", "text": "John Doe" },
          { "type": "text", "text": "123456" },
          { "type": "text", "text": "Jan 27, 2026" }
        ]
      }]
    }
  }'
```

## Equivalente en WhatsappService.php

```php
<?php
require_once 'WhatsappService.php';

// Instanciar con las mismas credenciales
$whatsapp = new WhatsappService(
    'EAAtfYi5hoikBQ...',  // Tu Access Token
    '963557323506204',     // Tu Phone Number ID
    'v22.0'                // Misma versión de API
);

// Enviar el mismo mensaje
$resultado = $whatsapp->sendTemplateMessage(
    '54358155484084',                        // to
    'jaspers_market_order_confirmation_v1',  // template name
    'en_US',                                 // language code
    [
        'John Doe',        // parámetro {{1}}
        '123456',          // parámetro {{2}}
        'Jan 27, 2026'     // parámetro {{3}}
    ]
);

// Ver resultado
print_r($resultado);
```

## Estructura JSON Generada

### cURL envía:
```json
{
  "messaging_product": "whatsapp",
  "to": "54358155484084",
  "type": "template",
  "template": {
    "name": "jaspers_market_order_confirmation_v1",
    "language": {
      "code": "en_US"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "John Doe" },
          { "type": "text", "text": "123456" },
          { "type": "text", "text": "Jan 27, 2026" }
        ]
      }
    ]
  }
}
```

### WhatsappService.php genera:
```json
{
  "messaging_product": "whatsapp",
  "to": "54358155484084",
  "type": "template",
  "template": {
    "name": "jaspers_market_order_confirmation_v1",
    "language": {
      "code": "en_US"
    },
    "components": [
      {
        "type": "body",
        "parameters": [
          { "type": "text", "text": "John Doe" },
          { "type": "text", "text": "123456" },
          { "type": "text", "text": "Jan 27, 2026" }
        ]
      }
    ]
  }
}
```

## ✅ Son IDÉNTICOS

El método `buildComponents()` en [WhatsappService.php:85-102](WhatsappService.php#L85-L102) construye exactamente la misma estructura:

```php
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
```

## Configuración HTTP

### cURL usa:
- Método: POST
- Headers:
  - `Authorization: Bearer {token}`
  - `Content-Type: application/json`
- Body: JSON codificado

### WhatsappService.php usa (líneas 115-121):
```php
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $this->accessToken,
    'Content-Type: application/json'
]);
```

## ✅ Son IDÉNTICOS

## Probar con tus datos

1. **Opción A: Ejecutar el script de prueba**
```bash
php test-real.php
```

2. **Opción B: Usar tu propio código**
```php
<?php
require_once 'WhatsappService.php';

$whatsapp = new WhatsappService(
    'TU_ACCESS_TOKEN',
    '963557323506204',
    'v22.0'
);

$resultado = $whatsapp->sendTemplateMessage(
    '54358155484084',
    'jaspers_market_order_confirmation_v1',
    'en_US',
    ['John Doe', '123456', 'Jan 27, 2026']
);

echo json_encode($resultado, JSON_PRETTY_PRINT);
```

3. **Opción C: Usar la API REST**
```bash
# Primero configurar el .env
cp .env.example .env
# Editar .env con tus credenciales

# Levantar servidor
php -S localhost:8000

# En otra terminal:
curl -X POST 'http://localhost:8000/api.php?action=send_template' \
  -H 'Content-Type: application/json' \
  -d '{
    "phone": "54358155484084",
    "template": "jaspers_market_order_confirmation_v1",
    "language": "en_US",
    "params": ["John Doe", "123456", "Jan 27, 2026"]
  }'
```

## Respuesta Esperada

Si todo funciona correctamente, recibirás:

```json
{
  "success": true,
  "message": "Mensaje enviado correctamente",
  "data": {
    "messaging_product": "whatsapp",
    "contacts": [
      {
        "input": "54358155484084",
        "wa_id": "54358155484084"
      }
    ],
    "messages": [
      {
        "id": "wamid.HBgNNTQzNTgxNTU0ODQwNDQVAgARGBI5NjM1NTczMjM1MDYyMDQA"
      }
    ]
  },
  "http_code": 200
}
```

## Conclusión

**Sí, el código funciona exactamente igual que tu cURL.**

La única ventaja del código PHP es que:
- Es reutilizable
- Maneja errores automáticamente
- Valida datos
- Puede integrarse fácilmente en aplicaciones
- Tiene una API REST lista para usar

Pero genera **exactamente** la misma petición HTTP que tu cURL funcional.
