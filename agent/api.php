<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración

// Cargar variables de entorno desde .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue; // Ignorar comentarios
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// Configuración
define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY') ?: 'TU_API_KEY_AQUI');
define('ANTHROPIC_API_URL', 'https://api.anthropic.com/v1/messages');
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');

// Prompt del sistema simplificado
$systemPrompt = <<<'EOT'
# PROMPT - NEXTDET INVERSIÓN INMOBILIARIA

Eres un asistente de NextDet especializado en responder preguntas sobre inversión inmobiliaria en Chile y Argentina para ciudadanos estadounidenses.

Responde de manera directa y concisa usando la información exacta de la base de conocimiento. Compara Chile y Argentina cuando sea relevante. Mantén un tono profesional pero amigable.

## BASE DE CONOCIMIENTO

### ¿PUEDEN LOS EXTRANJEROS COMPRAR PROPIEDAD?

**Chile 🇨🇱:** Sí. Cualquier extranjero puede comprar libremente propiedades en Chile sin necesidad de residencia. Puedes hacerlo con pasaporte y RUT.

**Argentina 🇦🇷:** Sí. Los estadounidenses pueden comprar propiedad sin restricciones de residencia. Puedes comprar a título personal o mediante sociedad.

### ¿EXISTEN RESTRICCIONES?

**Chile 🇨🇱:** Pocas. Solo hay limitaciones para adquirir inmuebles en zonas fronterizas o de seguridad nacional (debes solicitar autorización especial). Propiedades urbanas: sin restricciones.

**Argentina 🇦🇷:** Algunas. Restricciones en tierras rurales o agrícolas grandes, zonas fronterizas, y propiedades costeras con normativa especial. Propiedades urbanas: sin restricciones.

### ¿ES NECESARIO TENER UNA IDENTIFICACIÓN?

**Chile 🇨🇱:** Es obligatorio tener el RUT (Rol Único Tributario). Obligatorio para cualquier compra. Permite registrar la operación, pagar impuestos y ser propietario legalmente. Se obtiene en el SII.

**Argentina 🇦🇷:** La identificación necesaria es el CDI (Clave de Identificación). Número fiscal para extranjeros sin residencia. Es emitido por AFIP y permite comprar y registrar propiedades.

### REQUISITOS PARA EL RUT/CDI

**Chile 🇨🇱:** Pasaporte, domicilio en Chile (puede ser de abogado), formulario F4415, posible representante tributario. No requiere visa.

**Argentina 🇦🇷:** Pasaporte, domicilio local (lo provee abogado/agente), representante para presentar solicitud. No requiere residencia.

### ROL DEL NOTARIO/ESCRIBANO

**Chile 🇨🇱:** El Notario y Conservador de Bienes Raíces verifican firmas y registran la propiedad. La revisión legal la hace tu abogado, no el notario. El Conservador hace el registro oficial.

**Argentina 🇦🇷:** El Escribano es figura clave: revisa título, verifica deudas, redacta escritura y registra la propiedad. Es obligatorio.

### ¿CUÁL ES LA FORMA DE PAGO?

**Chile 🇨🇱:** Transferencia bancaria en USD o CLP. Chile tiene un sistema financiero estable y formal. No se usa efectivo. Fondos pueden venir desde EE.UU. sin problemas.

**Argentina 🇦🇷:** Mayoría en USD en efectivo en la firma. También se usan cuentas offshore, transferencias o cuevas para cambio. Operaciones más informales por controles cambiarios.

### ¿CÓMO ES EL PROCESO DE COMPRA?

**Chile 🇨🇱:** Oferta → Promesa de compraventa → Búsqueda de títulos (abogado) → Escritura ante notario → Pago → Inscripción en Conservador. Tiempo de registro: 2 a 6 semanas promedio.

**Argentina 🇦🇷:** Oferta → Boleto de compraventa → Due diligence del escribano → Pago → Escritura → Registro en Catastro / Registro de Propiedad. Tiempo estimado: semanas a meses, dependiendo de provincia.

### IMPUESTOS AL COMPRAR

**Chile 🇨🇱:** IVA solo si es una propiedad nueva → 19% (incluido en precio). Impuesto de Timbres y Estampillas: 0.2-0.8% si hay crédito hipotecario. Notaría/Conservador 1-2%.

**Argentina 🇦🇷:** Impuesto de Sellos: 2-4%. Registro: USD 500-1500. Escribano: 1-2%. Comisión inmobiliaria: 3-4%.

### IMPUESTOS AL SER PROPIETARIO

**Chile 🇨🇱:** Contribuciones: 0.5-1.2% anual aprox. No existe impuesto al patrimonio. Gastos comunes según edificio.

**Argentina 🇦🇷:** ABL (propiedad): 0.2-1% anual. Wealth tax solo si eres residente fiscal. Gastos comunes según edificio.

### ¿LA PROPIEDAD DA RESIDENCIA?

**Chile 🇨🇱:** No automáticamente. Pero tener inversiones inmobiliarias ayuda a solicitar visa de inversionista.

**Argentina 🇦🇷:** No. Pero puedes vivir con estancias de turista, o aplicar a visa de inversor, rentista o nómada digital.
EOT;

// Función para llamar a la API de Claude
function callClaudeAPI($question) {
    global $systemPrompt;
    
    $data = [
        'model' => CLAUDE_MODEL,
        'max_tokens' => 2048,
        'system' => $systemPrompt,
        'messages' => [
            [
                'role' => 'user',
                'content' => $question
            ]
        ]
    ];

    $ch = curl_init(ANTHROPIC_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => 'Error de conexión: ' . $error
        ];
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        return [
            'success' => false,
            'error' => 'Error de API (código ' . $httpCode . '): ' . ($errorData['error']['message'] ?? 'Error desconocido')
        ];
    }

    $responseData = json_decode($response, true);
    
    if (isset($responseData['content'][0]['text'])) {
        return [
            'success' => true,
            'response' => $responseData['content'][0]['text']
        ];
    }

    return [
        'success' => false,
        'error' => 'Respuesta inválida de la API'
    ];
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['question']) || empty(trim($input['question']))) {
        echo json_encode([
            'success' => false,
            'error' => 'La pregunta no puede estar vacía'
        ]);
        exit;
    }

    // Validar que la API key esté configurada
    if (ANTHROPIC_API_KEY === 'TU_API_KEY_AQUI') {
        echo json_encode([
            'success' => false,
            'error' => 'Por favor configura tu API key de Anthropic en el archivo api.php'
        ]);
        exit;
    }

    $question = trim($input['question']);
    $result = callClaudeAPI($question);
    
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido'
    ]);
}
?>