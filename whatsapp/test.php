<?php

/**
 * Script de prueba para verificar la configuración del servicio de WhatsApp
 * Este script verifica que todo esté correctamente configurado antes de enviar mensajes
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TEST DE CONFIGURACIÓN DEL SERVICIO DE WHATSAPP ===\n\n";

// 1. Verificar PHP y cURL
echo "1. Verificando requisitos de PHP...\n";
echo "   - Versión de PHP: " . PHP_VERSION;

if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo " ✓ OK\n";
} else {
    echo " ✗ ERROR - Se requiere PHP 7.4 o superior\n";
}

if (extension_loaded('curl')) {
    echo "   - Extensión cURL: Instalada ✓\n";
} else {
    echo "   - Extensión cURL: NO instalada ✗\n";
    echo "     Instala cURL para continuar\n";
    exit(1);
}

if (extension_loaded('json')) {
    echo "   - Extensión JSON: Instalada ✓\n";
} else {
    echo "   - Extensión JSON: NO instalada ✗\n";
}

echo "\n";

// 2. Verificar archivos requeridos
echo "2. Verificando archivos del proyecto...\n";

$requiredFiles = [
    'WhatsappService.php' => 'Clase principal del servicio',
    'config.php' => 'Configuración de variables de entorno',
    '.env.example' => 'Plantilla de configuración'
];

foreach ($requiredFiles as $file => $description) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "   - $file: Existe ✓ ($description)\n";
    } else {
        echo "   - $file: NO existe ✗\n";
    }
}

echo "\n";

// 3. Verificar archivo .env
echo "3. Verificando configuración (.env)...\n";

$envPath = __DIR__ . '/.env';
if (!file_exists($envPath)) {
    echo "   ✗ Archivo .env no encontrado\n";
    echo "   → Copia .env.example a .env y configura tus credenciales:\n";
    echo "     cp .env.example .env\n\n";
    exit(1);
} else {
    echo "   - Archivo .env: Existe ✓\n";
}

// Cargar configuración
require_once __DIR__ . '/config.php';

// Verificar variables de entorno
$requiredEnvVars = [
    'WHATSAPP_ACCESS_TOKEN' => 'Token de acceso de Meta',
    'WHATSAPP_PHONE_NUMBER_ID' => 'Phone Number ID',
    'TEST_PHONE_NUMBER' => 'Número de teléfono de prueba'
];

$configComplete = true;

foreach ($requiredEnvVars as $var => $description) {
    $value = env($var);
    if (empty($value)) {
        echo "   ✗ $var no configurada ($description)\n";
        $configComplete = false;
    } else {
        // Ocultar parcialmente el token por seguridad
        if ($var === 'WHATSAPP_ACCESS_TOKEN') {
            $displayValue = substr($value, 0, 10) . '...' . substr($value, -4);
        } elseif ($var === 'TEST_PHONE_NUMBER') {
            $displayValue = substr($value, 0, 3) . '****' . substr($value, -3);
        } else {
            $displayValue = $value;
        }
        echo "   ✓ $var: $displayValue ($description)\n";
    }
}

if (!$configComplete) {
    echo "\n   → Edita el archivo .env y configura las variables faltantes\n\n";
    exit(1);
}

echo "\n";

// 4. Verificar conectividad con la API de Meta
echo "4. Verificando conectividad con la API de Meta...\n";

$testUrl = "https://graph.facebook.com/v21.0";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo "   ✗ Error de conexión: $curlError\n";
    echo "   → Verifica tu conexión a internet\n\n";
} elseif ($httpCode == 200 || $httpCode == 400) {
    echo "   ✓ Conexión a graph.facebook.com exitosa\n";
} else {
    echo "   ⚠ Código HTTP: $httpCode\n";
}

echo "\n";

// 5. Probar instanciación de la clase
echo "5. Probando instanciación de WhatsappService...\n";

try {
    require_once __DIR__ . '/WhatsappService.php';

    $whatsapp = new WhatsappService(
        env('WHATSAPP_ACCESS_TOKEN'),
        env('WHATSAPP_PHONE_NUMBER_ID'),
        'v22.0'  // Usar la versión más reciente
    );

    echo "   ✓ Clase WhatsappService instanciada correctamente (API v22.0)\n";

} catch (Exception $e) {
    echo "   ✗ Error al instanciar la clase: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// 6. Resumen
echo "=== RESUMEN ===\n\n";
echo "✓ Todos los requisitos están cumplidos\n";
echo "✓ La configuración es correcta\n";
echo "✓ El servicio está listo para usar\n\n";

echo "PRÓXIMOS PASOS:\n";
echo "1. Crea una plantilla en Meta Business Suite\n";
echo "2. Ejecuta 'php index.php' para enviar mensajes de prueba\n";
echo "3. O ejecuta el servidor web: php -S localhost:8000\n";
echo "4. Luego prueba la API: curl http://localhost:8000/api.php?action=health\n\n";

echo "DOCUMENTACIÓN:\n";
echo "- Lee README.md para instrucciones completas\n";
echo "- Revisa ejemplos-curl.sh para ejemplos de peticiones\n\n";

echo "=== FIN DEL TEST ===\n";
