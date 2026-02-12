<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Integration - Test UI</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
        }
        .card {
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075);
            margin-bottom: 20px;
        }
        .badge-incoming {
            background-color: #0d6efd;
        }
        .badge-outgoing {
            background-color: #198754;
        }
        .badge-sent {
            background-color: #6c757d;
        }
        .badge-delivered {
            background-color: #0dcaf0;
        }
        .badge-read {
            background-color: #198754;
        }
        .badge-failed {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor" class="bi bi-whatsapp" viewBox="0 0 16 16" style="color: #25D366;">
                <path d="M13.601 2.326A7.854 7.854 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.933 7.933 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.898 7.898 0 0 0 13.6 2.326zM7.994 14.521a6.573 6.573 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.557 6.557 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592zm3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.729.729 0 0 0-.529.247c-.182.198-.691.677-.691 1.654 0 .977.71 1.916.81 2.049.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232z"/>
            </svg>
            WhatsApp Business API - Test UI
        </h1>
        
        <?php
        require_once __DIR__ . '/../config/config.php';
        require_once __DIR__ . '/../src/WhatsAppService.php';
        
        $service = new WhatsAppService();

        // PRG: Procesar POST y redirigir para evitar reenvío al recargar
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                if ($_POST['action'] === 'send_template') {
                    $phoneNumber = $_POST['phone_number'] ?? '';
                    $customerName = $_POST['customer_name'] ?? '';
                    $budgetId = $_POST['budget_id'] ?? '';
                    $total = $_POST['total'] ?? '';

                    $result = $service->sendBudgetNotification($phoneNumber, [
                        'customer_name' => $customerName,
                        'budget_id' => $budgetId,
                        'total' => $total
                    ]);

                    $msg = '¡Mensaje de plantilla enviado exitosamente! ID: ' . ($result['messages'][0]['id'] ?? 'N/A');
                    $type = 'success';

                } elseif ($_POST['action'] === 'send_text') {
                    $phoneNumber = $_POST['phone_number'] ?? '';
                    $text = $_POST['text_message'] ?? '';

                    $result = $service->sendTextMessage($phoneNumber, $text);

                    $msg = '¡Mensaje de texto enviado exitosamente! ID: ' . ($result['messages'][0]['id'] ?? 'N/A');
                    $type = 'success';
                }

            } catch (Exception $e) {
                $msg = 'Error: ' . $e->getMessage();
                $type = 'danger';
            }

            // Redirigir con resultado en query string
            $params = http_build_query(['msg' => $msg ?? '', 'type' => $type ?? 'info']);
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?' . $params);
            exit;
        }

        // Mostrar mensaje de resultado desde redirect
        if (isset($_GET['msg']) && $_GET['msg'] !== '') {
            $messageType = in_array($_GET['type'] ?? '', ['success', 'danger', 'info']) ? $_GET['type'] : 'info';
            echo '<div class="alert alert-' . $messageType . ' alert-dismissible fade show" role="alert">';
            echo htmlspecialchars($_GET['msg']);
            echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
            echo '</div>';
        }
        ?>
        
        <div class="row">
            <!-- Formulario de envío de plantilla -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Enviar Presupuesto (Template)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="send_template">
                            
                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Número de Teléfono</label>
                                <input type="text" class="form-control" id="phone_number" name="phone_number" 
                                       placeholder="5491112345678" required>
                                <small class="text-muted">Formato internacional sin + (ej: 5491112345678)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="customer_name" class="form-label">Nombre del Cliente</label>
                                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                       placeholder="Juan Pérez" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="budget_id" class="form-label">ID del Presupuesto</label>
                                <input type="text" class="form-control" id="budget_id" name="budget_id" 
                                       placeholder="12345" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="total" class="form-label">Total</label>
                                <input type="text" class="form-control" id="total" name="total" 
                                       placeholder="$150,000" required>
                            </div>
                            
                            <button type="submit" class="btn btn-success w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-send-fill" viewBox="0 0 16 16">
                                    <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083l6-15Zm-1.833 1.89L6.637 10.07l-.215-.338a.5.5 0 0 0-.154-.154l-.338-.215 7.494-7.494 1.178-.471-.47 1.178Z"/>
                                </svg>
                                Enviar Presupuesto
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Formulario de envío de texto -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Enviar Mensaje de Texto</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning" role="alert">
                            <small>
                                <strong>Advertencia:</strong> Solo funciona dentro de las 24 horas después de que el cliente haya enviado un mensaje.
                            </small>
                        </div>
                        
                        <form method="POST">
                            <input type="hidden" name="action" value="send_text">
                            
                            <div class="mb-3">
                                <label for="phone_number_text" class="form-label">Número de Teléfono</label>
                                <input type="text" class="form-control" id="phone_number_text" name="phone_number" 
                                       placeholder="5491112345678" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="text_message" class="form-label">Mensaje</label>
                                <textarea class="form-control" id="text_message" name="text_message" 
                                          rows="4" placeholder="Escriba su mensaje aquí..." required></textarea>
                                <small class="text-muted">Máximo 4096 caracteres</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-chat-left-text-fill" viewBox="0 0 16 16">
                                    <path d="M0 2a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4.414a1 1 0 0 0-.707.293L.854 15.146A.5.5 0 0 1 0 14.793V2zm3.5 1a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 2.5a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/>
                                </svg>
                                Enviar Texto
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabla de mensajes recientes -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Últimos 5 Mensajes</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Fecha/Hora</th>
                                <th>Teléfono</th>
                                <th>Contenido</th>
                                <th>Tipo</th>
                                <th>Dirección</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $recentMessages = $service->getRecentMessages(5);
                                
                                if (empty($recentMessages)) {
                                    echo '<tr><td colspan="6" class="text-center text-muted py-4">No hay mensajes registrados</td></tr>';
                                } else {
                                    foreach ($recentMessages as $msg) {
                                        $directionBadge = $msg['direction'] === 'incoming' ? 'badge-incoming' : 'badge-outgoing';
                                        $directionText = $msg['direction'] === 'incoming' ? 'Entrante' : 'Saliente';
                                        
                                        $statusBadge = '';
                                        $statusText = '';
                                        if ($msg['status']) {
                                            $statusMap = [
                                                'sent' => ['badge-sent', 'Enviado'],
                                                'delivered' => ['badge-delivered', 'Entregado'],
                                                'read' => ['badge-read', 'Leído'],
                                                'failed' => ['badge-failed', 'Fallido']
                                            ];
                                            if (isset($statusMap[$msg['status']])) {
                                                $statusBadge = $statusMap[$msg['status']][0];
                                                $statusText = $statusMap[$msg['status']][1];
                                            }
                                        }
                                        
                                        // Truncar contenido largo
                                        $content = strlen($msg['content']) > 50 
                                            ? substr($msg['content'], 0, 50) . '...' 
                                            : $msg['content'];
                                        
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($msg['timestamp']) . '</td>';
                                        echo '<td>' . htmlspecialchars($msg['phone_number']) . '</td>';
                                        echo '<td>' . htmlspecialchars($content) . '</td>';
                                        echo '<td><span class="badge bg-secondary">' . htmlspecialchars($msg['message_type']) . '</span></td>';
                                        echo '<td><span class="badge ' . $directionBadge . '">' . $directionText . '</span></td>';
                                        echo '<td>' . ($statusText ? '<span class="badge ' . $statusBadge . '">' . $statusText . '</span>' : '-') . '</td>';
                                        echo '</tr>';
                                    }
                                }
                            } catch (Exception $e) {
                                echo '<tr><td colspan="6" class="text-center text-danger py-4">Error al cargar mensajes: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Información de configuración -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Estado de Configuración</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Access Token:</strong> 
                            <?php 
                            echo WHATSAPP_ACCESS_TOKEN !== 'YOUR_ACCESS_TOKEN_HERE' 
                                ? '<span class="badge bg-success">Configurado</span>' 
                                : '<span class="badge bg-danger">No configurado</span>';
                            ?>
                        </p>
                        <p><strong>Phone Number ID:</strong> 
                            <?php 
                            echo WHATSAPP_PHONE_NUMBER_ID !== 'YOUR_PHONE_NUMBER_ID_HERE' 
                                ? '<span class="badge bg-success">Configurado</span>' 
                                : '<span class="badge bg-danger">No configurado</span>';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Verify Token:</strong> 
                            <?php 
                            echo WHATSAPP_VERIFY_TOKEN !== 'YOUR_SECURE_RANDOM_TOKEN_HERE' 
                                ? '<span class="badge bg-success">Configurado</span>' 
                                : '<span class="badge bg-danger">No configurado</span>';
                            ?>
                        </p>
                        <p><strong>API Version:</strong> 
                            <span class="badge bg-secondary"><?php echo WHATSAPP_API_VERSION; ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
