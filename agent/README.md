# NextDet - Chat de Inversión Inmobiliaria

Sistema de chat con IA para asesorar sobre inversión inmobiliaria en Chile y Argentina.

## 🚀 Instalación

### Requisitos
- PHP 7.4 o superior
- Extensión cURL habilitada
- Servidor web (Apache, Nginx, o PHP built-in server)
- API Key de Anthropic

### Pasos de Instalación

1. **Descargar los archivos**
   - Coloca los archivos `index.php` y `api.php` en tu directorio web

2. **Configurar la API Key de Anthropic**
   - Abre el archivo `api.php`
   - En la línea 8, reemplaza `'TU_API_KEY_AQUI'` con tu API key real
   ```php
   define('ANTHROPIC_API_KEY', 'sk-ant-api03-...');
   ```

3. **Iniciar el servidor**
   
   **Opción A - Servidor PHP incorporado (desarrollo):**
   ```bash
   php -S localhost:8000
   ```
   
   **Opción B - Apache/Nginx:**
   - Coloca los archivos en tu directorio web (ej: `/var/www/html/`)
   - Accede desde el navegador

4. **Acceder a la aplicación**
   - Abre tu navegador
   - Visita: `http://localhost:8000` (o tu URL configurada)

---

## 🔑 Cómo Obtener tu API Key de Anthropic

### Método 1: Para uso con la API de Anthropic (RECOMENDADO)

1. **Crear una cuenta en Anthropic Console**
   - Ve a: https://console.anthropic.com/
   - Haz clic en "Sign Up" o "Sign In"
   - Completa el registro con tu email

2. **Acceder a API Keys**
   - Una vez dentro, ve al menú lateral
   - Haz clic en "API Keys"
   - O ve directamente a: https://console.anthropic.com/settings/keys

3. **Crear una nueva API Key**
   - Haz clic en el botón "Create Key"
   - Dale un nombre descriptivo (ej: "NextDet Chat")
   - Copia la key que aparece (empieza con `sk-ant-api03-...`)
   - ⚠️ **IMPORTANTE:** Guarda esta key en un lugar seguro, no se mostrará de nuevo

4. **Agregar créditos (si es necesario)**
   - Ve a "Billing" en el menú
   - Agrega un método de pago
   - Compra créditos o configura auto-recarga
   - Precios actuales: ~$3 USD por millón de tokens de entrada

### Método 2: Usar claude.ai (NO RECOMENDADO para este proyecto)

⚠️ **NOTA:** Las cuentas gratuitas de claude.ai NO tienen acceso directo a la API.

Si tienes cuenta de claude.ai:
- La cuenta gratuita no incluye API access
- Solo incluye acceso web al chat
- Para usar la API necesitas una cuenta de Anthropic Console separada

---

## 💰 Costos de la API

### Modelo: Claude Sonnet 4 (claude-sonnet-4-20250514)

**Precios aproximados:**
- **Input (entrada):** ~$3 USD por millón de tokens
- **Output (salida):** ~$15 USD por millón de tokens

**Ejemplo de uso:**
- Una conversación típica usa ~500-1000 tokens por pregunta/respuesta
- 100 conversaciones ≈ $0.50 - $2.00 USD
- 1000 conversaciones ≈ $5 - $20 USD

**Recomendaciones:**
- Configura límites de gasto en Anthropic Console
- Monitorea el uso regularmente
- Para producción, considera cachear respuestas frecuentes

---

## 📝 Configuración Adicional

### Cambiar el modelo de Claude

En `api.php`, línea 9:
```php
define('CLAUDE_MODEL', 'claude-sonnet-4-20250514');
```

**Modelos disponibles:**
- `claude-sonnet-4-20250514` - Recomendado (balance precio/calidad)
- `claude-opus-4-20250514` - Más potente pero más costoso
- `claude-haiku-4-20250514` - Más rápido y económico

### Ajustar el límite de tokens

En `api.php`, dentro de la función `callClaudeAPI`:
```php
'max_tokens' => 2048, // Cambia este valor (máximo ~8000)
```

### Personalizar el prompt

El prompt completo está en `api.php` dentro de la variable `$systemPrompt`.
Puedes modificarlo para:
- Agregar más información
- Cambiar el tono de respuesta
- Añadir otros países o temas

---

## 🔒 Seguridad

### Recomendaciones importantes:

1. **Proteger la API Key**
   - NUNCA la subas a repositorios públicos (GitHub, GitLab, etc.)
   - Usa variables de entorno en producción
   - Considera usar `.env` y `php-dotenv`

2. **Usar HTTPS en producción**
   - Configura SSL/TLS en tu servidor
   - Las API keys se transmiten en headers

3. **Limitar acceso**
   - Implementa rate limiting
   - Agrega autenticación de usuarios si es necesario
   - Valida y sanitiza todas las entradas

4. **Ejemplo con variables de entorno:**
   ```php
   // En api.php, reemplaza:
   define('ANTHROPIC_API_KEY', getenv('ANTHROPIC_API_KEY'));
   
   // Y en tu servidor, configura:
   export ANTHROPIC_API_KEY="sk-ant-api03-..."
   ```

---

## 🛠️ Troubleshooting

### Error: "Por favor configura tu API key"
- Verifica que reemplazaste `'TU_API_KEY_AQUI'` con tu key real
- Asegúrate de que la key empiece con `sk-ant-api03-`

### Error: "Error de conexión"
- Verifica que cURL esté habilitado en PHP
- Comprueba tu conexión a internet
- Verifica que puedes acceder a `api.anthropic.com`

### Error 401: "Invalid API Key"
- Verifica que copiaste la key completa
- Asegúrate de que la key no haya expirado
- Revisa que la cuenta tenga créditos disponibles

### Error 429: "Rate limit exceeded"
- Has excedido el límite de requests
- Espera unos minutos y reintenta
- Considera implementar rate limiting en tu app

### La página no carga
- Verifica que PHP esté instalado: `php -v`
- Confirma que el servidor esté corriendo
- Revisa los logs de error de PHP

### Las respuestas son muy lentas
- Es normal, Claude puede tomar 5-15 segundos
- Considera usar un modelo más rápido (Haiku)
- Verifica tu conexión a internet

---

## 📚 Recursos Adicionales

- **Documentación de Anthropic API:** https://docs.anthropic.com/
- **Console de Anthropic:** https://console.anthropic.com/
- **Pricing:** https://www.anthropic.com/pricing
- **Límites y cuotas:** https://docs.anthropic.com/en/api/rate-limits

---

## 🤝 Soporte

Para problemas o preguntas:
1. Revisa esta documentación
2. Consulta la documentación oficial de Anthropic
3. Verifica los logs de error de PHP
4. Contacta al equipo de desarrollo

---

## 📄 Licencia

Este proyecto es de uso interno para NextDet.

---

## 🔄 Actualizaciones

**Versión 1.0** (Enero 2026)
- Chat funcional con Claude Sonnet 4
- Interfaz responsive
- Prompt especializado en inversión inmobiliaria Chile/Argentina