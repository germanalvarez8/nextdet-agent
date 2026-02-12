-- ====================================
-- Esquema de Base de Datos
-- WhatsApp Business API Integration
-- ====================================
-- 
-- Este script crea las tablas necesarias para almacenar:
-- 1. Historial de mensajes (wa_messages)
-- 2. Plantillas aprobadas (wa_templates)
--
-- Requisitos: MySQL 5.7+ o MariaDB 10.2+
-- Charset: utf8mb4 para soportar emojis
-- ====================================

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS whatsapp_integration 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE whatsapp_integration;

-- ====================================
-- Tabla: wa_messages
-- ====================================
-- Almacena el historial completo de mensajes enviados y recibidos
-- Permite tracking del estado del mensaje y análisis de conversaciones
-- ====================================

CREATE TABLE IF NOT EXISTS wa_messages (
    -- ID autoincremental interno
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- ID único de WhatsApp asignado por Meta
    -- Este ID permite rastrear el mensaje en el sistema de WhatsApp
    -- Puede ser NULL para mensajes salientes hasta que Meta responda
    id_wa_meta VARCHAR(255) NULL,
    
    -- Número de teléfono del cliente (formato internacional sin +)
    -- Ejemplo: 5491112345678 para Argentina
    phone_number VARCHAR(20) NOT NULL,
    
    -- Contenido del mensaje
    -- Para mensajes de template, almacena el nombre de la plantilla
    -- Para mensajes de texto, el contenido completo
    content TEXT NOT NULL,
    
    -- Tipo de mensaje según WhatsApp Cloud API
    -- Valores posibles: text, template, image, document, audio, video, location, contacts, interactive
    message_type ENUM('text', 'template', 'image', 'document', 'audio', 'video', 'location', 'contacts', 'interactive') NOT NULL DEFAULT 'text',
    
    -- Dirección del mensaje
    -- incoming: mensaje recibido del cliente
    -- outgoing: mensaje enviado por la empresa
    direction ENUM('incoming', 'outgoing') NOT NULL,
    
    -- Estado del mensaje (para mensajes salientes)
    -- sent: enviado a Meta
    -- delivered: entregado al dispositivo del cliente
    -- read: leído por el cliente
    -- failed: falló el envío
    -- NULL: para mensajes entrantes (no aplica)
    status ENUM('sent', 'delivered', 'read', 'failed') NULL,
    
    -- Timestamp del mensaje
    -- Para mensajes salientes: cuando se envió
    -- Para mensajes entrantes: cuando se recibió del webhook
    timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos adicionales en formato JSON
    -- Útil para almacenar información extra como:
    -- - Variables de la plantilla
    -- - Metadata del archivo multimedia
    -- - Información del contexto (respuesta a otro mensaje)
    metadata JSON NULL,
    
    -- Timestamps de auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices para optimizar consultas comunes
    INDEX idx_phone_number (phone_number),
    INDEX idx_direction (direction),
    INDEX idx_timestamp (timestamp),
    INDEX idx_status (status),
    INDEX idx_wa_meta_id (id_wa_meta),
    
    -- Índice compuesto para obtener historial de conversación
    INDEX idx_phone_timestamp (phone_number, timestamp)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de mensajes de WhatsApp enviados y recibidos';

-- ====================================
-- Tabla: wa_templates
-- ====================================
-- Registro de plantillas de mensaje aprobadas en Meta Business Manager
-- WhatsApp requiere pre-aprobar las plantillas antes de poder enviarlas
-- Esta tabla facilita el mantenimiento y seguimiento de plantillas
-- ====================================

CREATE TABLE IF NOT EXISTS wa_templates (
    -- ID autoincremental interno
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    
    -- Nombre de la plantilla tal como está registrado en Meta
    -- Debe coincidir exactamente con el nombre en Meta Business Manager
    -- Ejemplo: 'presupuesto_insumos_mineros'
    template_name VARCHAR(255) NOT NULL,
    
    -- Código de idioma de la plantilla
    -- Formato: ISO 639-1 (2 letras) o con localización (es_AR, es_MX, etc.)
    -- Ejemplos: 'es', 'es_AR', 'en', 'en_US'
    language VARCHAR(10) NOT NULL DEFAULT 'es',
    
    -- Estado de la plantilla en Meta
    -- approved: aprobada y lista para usar
    -- pending: enviada para revisión
    -- rejected: rechazada por Meta (revisar políticas)
    status ENUM('approved', 'pending', 'rejected') NOT NULL DEFAULT 'pending',
    
    -- Número de variables que acepta la plantilla
    -- Las plantillas pueden tener parámetros dinámicos {{1}}, {{2}}, etc.
    -- Este campo ayuda a validar que se pasen todas las variables necesarias
    variables_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    
    -- Categoría de la plantilla según Meta
    -- UTILITY: notificaciones transaccionales (presupuestos, confirmaciones)
    -- MARKETING: ofertas y promociones (requiere opt-in del cliente)
    -- AUTHENTICATION: códigos de verificación (OTP)
    category ENUM('UTILITY', 'MARKETING', 'AUTHENTICATION') NOT NULL DEFAULT 'UTILITY',
    
    -- Descripción del uso de la plantilla
    -- Ayuda a los desarrolladores a entender cuándo usar cada plantilla
    description TEXT NULL,
    
    -- Contenido de ejemplo de la plantilla (para referencia)
    -- No se usa en la API, solo para documentación interna
    example_content TEXT NULL,
    
    -- Timestamps de auditoría
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraint único: una plantilla por idioma
    UNIQUE KEY unique_template_language (template_name, language),
    
    -- Índices
    INDEX idx_status (status),
    INDEX idx_template_name (template_name)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de plantillas de WhatsApp aprobadas en Meta Business Manager';

-- ====================================
-- Datos de ejemplo para testing
-- ====================================
-- Insertar una plantilla de ejemplo para presupuestos
-- NOTA: Debes crear esta plantilla real en Meta Business Manager primero

INSERT INTO wa_templates (
    template_name, 
    language, 
    status, 
    variables_count, 
    category,
    description,
    example_content
) VALUES (
    'presupuesto_insumos',
    'es',
    'pending',
    3,
    'UTILITY',
    'Plantilla para enviar notificaciones de presupuestos a clientes',
    'Estimado {{1}}, su presupuesto #{{2}} está listo. Total: ${{3}}. Responda si desea proceder.'
) ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    example_content = VALUES(example_content);

-- ====================================
-- Vistas útiles (opcional)
-- ====================================

-- Vista para obtener mensajes recientes con información legible
CREATE OR REPLACE VIEW vw_recent_messages AS
SELECT 
    id,
    id_wa_meta,
    phone_number,
    CASE 
        WHEN LENGTH(content) > 100 THEN CONCAT(SUBSTRING(content, 1, 100), '...')
        ELSE content
    END AS content_preview,
    message_type,
    direction,
    status,
    timestamp,
    TIMESTAMPDIFF(MINUTE, timestamp, NOW()) AS minutes_ago
FROM wa_messages
ORDER BY timestamp DESC
LIMIT 50;

-- Vista para estadísticas de conversación por cliente
CREATE OR REPLACE VIEW vw_conversation_stats AS
SELECT 
    phone_number,
    COUNT(*) AS total_messages,
    SUM(CASE WHEN direction = 'incoming' THEN 1 ELSE 0 END) AS incoming_count,
    SUM(CASE WHEN direction = 'outgoing' THEN 1 ELSE 0 END) AS outgoing_count,
    MAX(timestamp) AS last_message_at,
    MIN(timestamp) AS first_message_at
FROM wa_messages
GROUP BY phone_number;

-- ====================================
-- Fin del script
-- ====================================
