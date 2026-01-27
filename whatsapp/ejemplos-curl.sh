#!/bin/bash

# Ejemplos de peticiones cURL para probar la API de WhatsApp
# Asegúrate de cambiar localhost por tu servidor y el número de teléfono

BASE_URL="http://localhost:8000"

echo "=== EJEMPLOS DE PETICIONES CURL PARA API DE WHATSAPP ==="
echo ""

# 1. Health Check
echo "1. Health Check - Verificar estado del servicio"
echo "------------------------------------------------"
echo "curl -X GET \"$BASE_URL/api.php?action=health\""
echo ""
curl -X GET "$BASE_URL/api.php?action=health"
echo ""
echo ""

# 2. Enviar mensaje con plantilla
echo "2. Enviar mensaje con plantilla"
echo "--------------------------------"
TEMPLATE_CMD="curl -X POST \"$BASE_URL/api.php?action=send_template\" \\
  -H \"Content-Type: application/json\" \\
  -d '{
    \"phone\": \"51999999999\",
    \"template\": \"presupuesto_mineria\",
    \"language\": \"es\",
    \"params\": [
      \"Juan Pérez\",
      \"MIN-2024-001\",
      \"Extracción de Oro\",
      \"\$125,000 USD\",
      \"15/02/2024\"
    ]
  }'"
echo "$TEMPLATE_CMD"
echo ""
# eval "$TEMPLATE_CMD"
echo ""

# 3. Enviar mensaje de texto simple
echo "3. Enviar mensaje de texto simple"
echo "----------------------------------"
TEXT_CMD="curl -X POST \"$BASE_URL/api.php?action=send_text\" \\
  -H \"Content-Type: application/json\" \\
  -d '{
    \"phone\": \"51999999999\",
    \"message\": \"¡Hola! Este es un mensaje de prueba del servicio de WhatsApp.\"
  }'"
echo "$TEXT_CMD"
echo ""
# eval "$TEXT_CMD"
echo ""

# 4. Enviar notificación de presupuesto (endpoint especializado)
echo "4. Enviar notificación de presupuesto"
echo "--------------------------------------"
PRESUPUESTO_CMD="curl -X POST \"$BASE_URL/api.php?action=send_presupuesto\" \\
  -H \"Content-Type: application/json\" \\
  -d '{
    \"phone\": \"51999999999\",
    \"cliente_nombre\": \"María González\",
    \"presupuesto_numero\": \"MIN-2024-002\",
    \"proyecto_tipo\": \"Exploración de Cobre\",
    \"monto\": \"\$95,500 USD\",
    \"fecha_validez\": \"28/02/2024\"
  }'"
echo "$PRESUPUESTO_CMD"
echo ""
# eval "$PRESUPUESTO_CMD"
echo ""

# 5. Con autenticación (si tienes API_KEY configurada)
echo "5. Con autenticación (API Key)"
echo "-------------------------------"
AUTH_CMD="curl -X POST \"$BASE_URL/api.php?action=send_template\" \\
  -H \"Content-Type: application/json\" \\
  -H \"Authorization: Bearer tu_api_key_aqui\" \\
  -d '{
    \"phone\": \"51999999999\",
    \"template\": \"presupuesto_mineria\",
    \"params\": [\"Cliente\", \"NUM-001\", \"Proyecto\", \"\$1000\", \"01/01/2024\"]
  }'"
echo "$AUTH_CMD"
echo ""
# eval "$AUTH_CMD"
echo ""

echo "=== NOTAS IMPORTANTES ==="
echo "1. Cambia localhost:8000 por la URL de tu servidor"
echo "2. Reemplaza 51999999999 con un número de teléfono real"
echo "3. Asegúrate de que las plantillas existan en Meta y estén aprobadas"
echo "4. Para ejecutar los comandos, descomenta las líneas 'eval' en este script"
echo "5. Los comandos están listos para copiar y pegar en tu terminal"
echo ""

# Ejemplo para copiar y pegar directamente en la terminal
echo "=== COMANDO RÁPIDO DE PRUEBA ==="
echo "Copia y pega este comando en tu terminal (cambia la URL y el teléfono):"
echo ""
echo "curl -X POST 'http://localhost:8000/api.php?action=send_template' -H 'Content-Type: application/json' -d '{\"phone\":\"51999999999\",\"template\":\"hello_world\",\"language\":\"es\",\"params\":[]}'"
echo ""
