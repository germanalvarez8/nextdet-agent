<?php

/**
 * Configuración del servicio de WhatsApp
 * Carga las variables de entorno desde el archivo .env
 */

function loadEnv($path = __DIR__ . '/.env')
{
    if (!file_exists($path)) {
        throw new Exception("Archivo .env no encontrado. Copia .env.example a .env y configura tus credenciales.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Ignorar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Separar clave y valor
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remover comillas si existen
            $value = trim($value, '"\'');

            // Establecer en $_ENV y $_SERVER
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Cargar variables de entorno
try {
    loadEnv();
} catch (Exception $e) {
    die("Error al cargar configuración: " . $e->getMessage());
}

// Función helper para obtener variables de entorno
function env($key, $default = null)
{
    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
}
