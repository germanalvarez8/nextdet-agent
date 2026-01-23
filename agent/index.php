<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NextDet - Asistente de Inversión Inmobiliaria</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 800px;
            height: 90vh;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .chat-header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }

        .chat-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }

        .message {
            margin-bottom: 20px;
            display: flex;
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message.user {
            justify-content: flex-end;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 18px;
            border-radius: 18px;
            word-wrap: break-word;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.assistant .message-content {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 4px;
        }

        .message.assistant .message-content strong {
            color: #667eea;
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
        }

        .message.assistant .message-content ul,
        .message.assistant .message-content ol {
            margin-left: 20px;
            margin-top: 10px;
        }

        .message.assistant .message-content li {
            margin-bottom: 5px;
        }

        .loading {
            display: none;
            padding: 12px 18px;
            background: white;
            border-radius: 18px;
            border: 1px solid #e0e0e0;
            max-width: 70%;
        }

        .loading.active {
            display: block;
        }

        .loading-dots {
            display: flex;
            gap: 4px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: #667eea;
            border-radius: 50%;
            animation: bounce 1.4s infinite ease-in-out both;
        }

        .loading-dots span:nth-child(1) {
            animation-delay: -0.32s;
        }

        .loading-dots span:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes bounce {
            0%, 80%, 100% {
                transform: scale(0);
            }
            40% {
                transform: scale(1);
            }
        }

        .chat-input-container {
            padding: 20px;
            background: white;
            border-top: 1px solid #e0e0e0;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 15px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input:focus {
            border-color: #667eea;
        }

        .send-button {
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .send-button:active {
            transform: translateY(0);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px 18px;
            border-radius: 18px;
            border: 1px solid #fcc;
            margin-bottom: 20px;
        }

        /* Scrollbar personalizado */
        .chat-messages::-webkit-scrollbar {
            width: 8px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 4px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: #764ba2;
        }

        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .welcome-message h2 {
            color: #667eea;
            margin-bottom: 15px;
        }

        .welcome-message p {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .example-questions {
            margin-top: 20px;
            text-align: left;
            background: white;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
        }

        .example-questions h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .example-questions ul {
            list-style: none;
        }

        .example-questions li {
            padding: 8px 0;
            color: #555;
            cursor: pointer;
            transition: color 0.2s;
        }

        .example-questions li:hover {
            color: #667eea;
        }

        .example-questions li:before {
            content: "💬 ";
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>🏢 NextDet Inmobiliaria</h1>
            <p>Asistente de Inversión en Chile y Argentina</p>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="welcome-message">
                <h2>¡Bienvenido!</h2>
                <p>Soy tu asistente especializado en inversión inmobiliaria en Chile y Argentina para ciudadanos estadounidenses.</p>
                <p>Puedo ayudarte con información sobre procesos de compra, requisitos, costos, impuestos y más.</p>
                
                <div class="example-questions">
                    <h3>Preguntas frecuentes:</h3>
                    <ul>
                        <li onclick="askQuestion('¿Puedo comprar propiedad siendo estadounidense?')">¿Puedo comprar propiedad siendo estadounidense?</li>
                        <li onclick="askQuestion('¿Qué documentos necesito para comprar en Chile?')">¿Qué documentos necesito para comprar en Chile?</li>
                        <li onclick="askQuestion('¿Cuáles son los impuestos al comprar?')">¿Cuáles son los impuestos al comprar?</li>
                        <li onclick="askQuestion('¿Cuánto tiempo demora el proceso?')">¿Cuánto tiempo demora el proceso?</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="loading" id="loadingIndicator">
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>

        <div class="chat-input-container">
            <form class="chat-input-wrapper" id="chatForm" onsubmit="sendMessage(event)">
                <input 
                    type="text" 
                    class="chat-input" 
                    id="userInput" 
                    placeholder="Escribe tu pregunta aquí..."
                    autocomplete="off"
                    required
                >
                <button type="submit" class="send-button" id="sendButton">Enviar</button>
            </form>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const userInput = document.getElementById('userInput');
        const sendButton = document.getElementById('sendButton');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const chatForm = document.getElementById('chatForm');

        // Función para agregar mensaje al chat
        function addMessage(content, isUser = false) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isUser ? 'user' : 'assistant'}`;
            
            const contentDiv = document.createElement('div');
            contentDiv.className = 'message-content';
            
            if (isUser) {
                contentDiv.textContent = content;
            } else {
                // Convertir markdown simple a HTML
                content = content
                    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\n\n/g, '<br><br>')
                    .replace(/\n/g, '<br>');
                contentDiv.innerHTML = content;
            }
            
            messageDiv.appendChild(contentDiv);
            chatMessages.appendChild(messageDiv);
            
            // Scroll al final
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Función para mostrar/ocultar indicador de carga
        function toggleLoading(show) {
            loadingIndicator.classList.toggle('active', show);
            sendButton.disabled = show;
            userInput.disabled = show;
            if (show) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Función para mostrar error
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.textContent = `❌ Error: ${message}`;
            chatMessages.appendChild(errorDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Función para enviar mensaje
        async function sendMessage(event) {
            event.preventDefault();
            
            const question = userInput.value.trim();
            if (!question) return;

            // Limpiar mensaje de bienvenida si existe
            const welcomeMsg = document.querySelector('.welcome-message');
            if (welcomeMsg) {
                welcomeMsg.remove();
            }

            // Agregar mensaje del usuario
            addMessage(question, true);
            userInput.value = '';

            // Mostrar indicador de carga
            toggleLoading(true);

            try {
                // Llamar a la API
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ question: question })
                });

                const data = await response.json();

                if (data.success) {
                    addMessage(data.response, false);
                } else {
                    showError(data.error || 'Error desconocido al procesar la solicitud');
                }
            } catch (error) {
                showError('Error de conexión. Por favor, intenta nuevamente.');
                console.error('Error:', error);
            } finally {
                toggleLoading(false);
                userInput.focus();
            }
        }

        // Función para hacer una pregunta desde los ejemplos
        function askQuestion(question) {
            userInput.value = question;
            chatForm.dispatchEvent(new Event('submit'));
        }

        // Enfocar input al cargar
        window.addEventListener('load', () => {
            userInput.focus();
        });
    </script>
</body>
</html>