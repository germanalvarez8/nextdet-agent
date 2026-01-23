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
            background: linear-gradient(180deg, #1e3a8a 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .chat-container {
            width: 100%;
            max-width: 900px;
            height: 90vh;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 3px solid #e5e7eb;
        }

        .chat-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #dc2626;
        }

        .chat-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .logo-text .next {
            color: white;
        }

        .logo-text .det {
            color: #dc2626;
        }

        .header-subtitle {
            font-size: 13px;
            opacity: 0.9;
            margin-top: 2px;
            color: #60a5fa;
        }

        .country-flags {
            display: flex;
            gap: 8px;
            font-size: 24px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 25px;
            background: #f8fafc;
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
            max-width: 75%;
            padding: 14px 20px;
            border-radius: 16px;
            word-wrap: break-word;
            line-height: 1.6;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
            color: white;
            border-bottom-right-radius: 4px;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .message.assistant .message-content {
            background: white;
            color: #1e293b;
            border: 2px solid #e5e7eb;
            border-bottom-left-radius: 4px;
            border-left: 4px solid #dc2626;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .message.assistant .message-content strong {
            color: #1e3a8a;
            display: block;
            margin-top: 10px;
            margin-bottom: 5px;
            font-weight: 600;
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
            padding: 14px 20px;
            background: white;
            border-radius: 16px;
            border: 2px solid #e5e7eb;
            border-left: 4px solid #dc2626;
            max-width: 70%;
        }

        .loading.active {
            display: block;
        }

        .loading-dots {
            display: flex;
            gap: 6px;
        }

        .loading-dots span {
            width: 10px;
            height: 10px;
            background: linear-gradient(135deg, #1e3a8a 0%, #dc2626 100%);
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
            padding: 20px 25px;
            background: white;
            border-top: 3px solid #e5e7eb;
        }

        .chat-input-wrapper {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .chat-input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #cbd5e1;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .chat-input:focus {
            border-color: #1e3a8a;
            background: white;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .send-button {
            padding: 14px 32px;
            background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
            background: linear-gradient(135deg, #b91c1c 0%, #991b1b 100%);
        }

        .send-button:active {
            transform: translateY(0);
        }

        .send-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 20px;
            border-radius: 12px;
            border: 2px solid #fecaca;
            border-left: 4px solid #dc2626;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Scrollbar personalizado */
        .chat-messages::-webkit-scrollbar {
            width: 10px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #1e3a8a 0%, #dc2626 100%);
            border-radius: 10px;
        }

        .chat-messages::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, #1e40af 0%, #b91c1c 100%);
        }

        .welcome-message {
            text-align: center;
            padding: 40px 20px;
            color: #475569;
        }

        .welcome-message h2 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-size: 26px;
            font-weight: 700;
        }

        .welcome-message h2 .highlight {
            color: #dc2626;
        }

        .welcome-message p {
            margin-bottom: 12px;
            line-height: 1.7;
            font-size: 15px;
        }

        .welcome-countries {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 20px 0;
        }

        .country-box {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-weight: 600;
            color: #1e3a8a;
        }

        .country-flag {
            font-size: 24px;
        }

        .example-questions {
            margin-top: 25px;
            text-align: left;
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
            border-left: 4px solid #dc2626;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .example-questions h3 {
            color: #1e3a8a;
            margin-bottom: 15px;
            font-size: 17px;
            font-weight: 700;
        }

        .example-questions ul {
            list-style: none;
        }

        .example-questions li {
            padding: 12px 15px;
            color: #475569;
            cursor: pointer;
            transition: all 0.2s;
            border-radius: 8px;
            margin-bottom: 5px;
        }

        .example-questions li:hover {
            background: #f1f5f9;
            color: #1e3a8a;
            transform: translateX(5px);
        }

        .example-questions li:before {
            content: "💬";
            margin-right: 10px;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <div class="chat-header-left">
                <div>
                    <div class="logo-text">
                        <span class="next">next</span><span class="det">det</span>
                    </div>
                    <div class="header-subtitle">Tecnología Disruptiva Minería</div>
                </div>
            </div>
            <div class="country-flags">
                <span title="Chile">🇨🇱</span>
                <span title="Argentina">🇦🇷</span>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="welcome-message">
                <h2>¡Bienvenido a <span class="highlight">NextDet</span> Inmobiliaria!</h2>
                <p>Tu asistente especializado en inversión inmobiliaria para ciudadanos estadounidenses</p>
                
                <div class="welcome-countries">
                    <div class="country-box">
                        <span class="country-flag">🇨🇱</span>
                        <span>Chile</span>
                    </div>
                    <div class="country-box">
                        <span class="country-flag">🇦🇷</span>
                        <span>Argentina</span>
                    </div>
                </div>

                <p>Puedo ayudarte con información sobre procesos de compra, requisitos legales, costos, impuestos y mucho más.</p>
                
                <div class="example-questions">
                    <h3>💡 Preguntas frecuentes:</h3>
                    <ul>
                        <li onclick="askQuestion('¿Puedo comprar propiedad siendo estadounidense?')">¿Puedo comprar propiedad siendo estadounidense?</li>
                        <li onclick="askQuestion('¿Qué es el RUT y cómo lo obtengo en Chile?')">¿Qué es el RUT y cómo lo obtengo en Chile?</li>
                        <li onclick="askQuestion('¿Cuáles son los impuestos al comprar en cada país?')">¿Cuáles son los impuestos al comprar en cada país?</li>
                        <li onclick="askQuestion('¿Cuánto tiempo demora el proceso de compra?')">¿Cuánto tiempo demora el proceso de compra?</li>
                        <li onclick="askQuestion('¿La propiedad me da residencia automática?')">¿La propiedad me da residencia automática?</li>
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