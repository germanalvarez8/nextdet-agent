# Ejemplo de integración con proyecto existente

Este archivo muestra cómo integrar el microservicio de WhatsApp
en diferentes partes de tu proyecto PHP existente.

## Escenario 1: Enviar presupuesto desde el sistema de ventas

```php
<?php
// En tu archivo de gestión de presupuestos
require_once __DIR__ . '/whatsapp_ag/src/WhatsAppService.php';

// Después de crear un presupuesto
function onBudgetCreated($budgetId, $customerId) {
    // Obtener datos del cliente de tu BD
    $customer = getCustomerData($customerId);
    $budget = getBudgetData($budgetId);
    
    // Verificar que el cliente tiene WhatsApp
    if ($customer['whatsapp_number']) {
        try {
            $whatsapp = new WhatsAppService();
            
            $result = $whatsapp->sendBudgetNotification($customer['whatsapp_number'], [
                'customer_name' => $customer['name'],
                'budget_id' => $budgetId,
                'total' => '$' . number_format($budget['total'], 2)
            ]);
            
            // Registrar en tu sistema que se envió notificación
            logBudgetNotification($budgetId, 'whatsapp', 'sent', $result);
            
        } catch (Exception $e) {
            // Manejar error sin interrumpir el flujo principal
            logError('WhatsApp notification failed: ' . $e->getMessage());
        }
    }
}
```

## Escenario 2: Responder a consultas de clientes

```php
<?php
// Crear un cron job o script que procese mensajes entrantes

require_once __DIR__ . '/whatsapp_ag/src/WhatsAppService.php';

$whatsapp = new WhatsAppService();

// Obtener mensajes entrantes recientes no procesados
$recentMessages = $whatsapp->getRecentMessages(50);

foreach ($recentMessages as $msg) {
    if ($msg['direction'] === 'incoming') {
        $content = strtolower($msg['content']);
        $phone = $msg['phone_number'];
        
        // Detectar palabras clave
        if (strpos($content, 'presupuesto') !== false) {
            // Buscar presupuesto del cliente
            $budget = findCustomerBudget($phone);
            
            if ($budget) {
                $whatsapp->sendTextMessage(
                    $phone,
                    "Su presupuesto #{$budget['id']} tiene un total de \${$budget['total']}. ¿Desea proceder?"
                );
            }
        }
        
        if (strpos($content, 'sí') !== false || strpos($content, 'si') !== false) {
            // Cliente confirmó
            confirmBudget($phone);
            $whatsapp->sendTextMessage(
                $phone,
                "¡Perfecto! Procesaremos su pedido. Le notificaremos cuando esté listo."
            );
        }
        
        // Marcar como procesado en tu BD
        markMessageAsProcessed($msg['id']);
    }
}
```

## Escenario 3: Dashboard de conversaciones

```php
<?php
// En tu panel de administración

require_once __DIR__ . '/whatsapp_ag/src/WhatsAppService.php';

$whatsapp = new WhatsAppService();

// Mostrar estadísticas
$stats = $whatsapp->getMessagingStats(30); // últimos 30 días

echo "<h2>Estadísticas de WhatsApp (últimos 30 días)</h2>";
echo "<p>Total de mensajes: {$stats['total_messages']}</p>";
echo "<p>Clientes únicos contactados: {$stats['unique_contacts']}</p>";
echo "<p>Mensajes enviados: {$stats['by_direction']['outgoing']}</p>";
echo "<p>Mensajes recibidos: {$stats['by_direction']['incoming']}</p>";

// Listar conversaciones recientes
$recentMessages = $whatsapp->getRecentMessages(20);

echo "<h2>Conversaciones recientes</h2>";
echo "<table>";
foreach ($recentMessages as $msg) {
    echo "<tr>";
    echo "<td>{$msg['timestamp']}</td>";
    echo "<td>{$msg['phone_number']}</td>";
    echo "<td>{$msg['content']}</td>";
    echo "<td>{$msg['direction']}</td>";
    echo "</tr>";
}
echo "</table>";
```

## Escenario 4: Notificaciones automáticas de cambio de estado

```php
<?php
// En tu sistema de gestión de pedidos

require_once __DIR__ . '/whatsapp_ag/src/WhatsAppService.php';

$whatsapp = new WhatsAppService();

function notifyOrderStatus($orderId, $newStatus, $customerPhone) {
    global $whatsapp;
    
    $messages = [
        'processing' => 'Su pedido está siendo procesado.',
        'ready' => 'Su pedido está listo para retirar.',
        'dispatched' => 'Su pedido ha sido despachado.',
        'delivered' => '¡Su pedido ha sido entregado!'
    ];
    
    $message = $messages[$newStatus] ?? 'Estado actualizado.';
    
    try {
        $whatsapp->sendTextMessage($customerPhone, 
            "Pedido #{$orderId}: {$message}"
        );
    } catch (Exception $e) {
        error_log("Failed to send order status: " . $e->getMessage());
    }
}
```

## Escenario 5: Recordatorios automáticos

```php
<?php
// Cron job diario para enviar recordatorios

require_once __DIR__ . '/whatsapp_ag/src/WhatsAppService.php';

$whatsapp = new WhatsAppService();

// Buscar presupuestos pendientes de más de 3 días
$pendingBudgets = getPendingBudgets(3);

foreach ($pendingBudgets as $budget) {
    try {
        $whatsapp->sendTextMessage(
            $budget['customer_phone'],
            "Recordatorio: Tiene un presupuesto pendiente #{$budget['id']} por \${$budget['total']}. ¿Desea proceder?"
        );
        
        logReminder($budget['id'], 'sent');
        
    } catch (Exception $e) {
        logReminder($budget['id'], 'failed', $e->getMessage());
    }
    
    // Evitar rate limiting
    sleep(2);
}
```

## Notas importantes

1. **Ventana de 24 horas**: Solo puedes enviar mensajes de texto libre 
   dentro de las 24 horas después de que el cliente te escriba. 
   Fuera de esa ventana, usa plantillas.

2. **Rate limiting**: WhatsApp tiene límites de mensajes por segundo.
   Agregar `sleep()` entre envíos masivos.

3. **Manejo de errores**: Siempre usar try-catch para no interrumpir
   el flujo principal de tu aplicación.

4. **Testing**: Probar primero con números de prueba antes de enviar
   a clientes reales.
