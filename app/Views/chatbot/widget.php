<!-- Chatbot Widget -->
<button class="chatbot-toggle" id="chatbotToggle" onclick="toggleChatbot()" aria-label="Open AI Assistant">
    <i class="bi bi-chat-dots-fill"></i>
    <span class="pulse-ring"></span>
</button>

<div class="chatbot-window" id="chatbotWindow">
    <div class="chatbot-header">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;">
                <i class="bi bi-robot"></i>
            </div>
            <div>
                <h4>AssetFlow Assistant</h4>
                <div style="font-size:11px;opacity:0.8;">Always here to help</div>
            </div>
        </div>
        <button class="chatbot-close" onclick="toggleChatbot()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="chatbot-messages" id="chatMessages">
        <div class="chat-message bot">
            <div class="chat-avatar"><i class="bi bi-robot"></i></div>
            <div class="chat-bubble">
                Hello! 👋 I'm your AssetFlow Assistant. I can help you with:
                <br>• Check asset status
                <br>• View your allocations & bookings
                <br>• Get help with system features
                <br><br>Type your question below!
            </div>
        </div>
    </div>
    <div class="chatbot-input">
        <input type="text" id="chatInput" placeholder="Ask me anything..." onkeydown="if(event.key==='Enter')sendChatMessage()">
        <button onclick="sendChatMessage()"><i class="bi bi-send-fill"></i></button>
    </div>
</div>

<script>
function toggleChatbot() {
    const window = document.getElementById('chatbotWindow');
    const toggle = document.getElementById('chatbotToggle');
    window.classList.toggle('open');
    
    if (window.classList.contains('open')) {
        toggle.innerHTML = '<i class="bi bi-x-lg"></i>';
        toggle.querySelector('.pulse-ring')?.remove();
        document.getElementById('chatInput').focus();
    } else {
        toggle.innerHTML = '<i class="bi bi-chat-dots-fill"></i><span class="pulse-ring"></span>';
    }
}

function sendChatMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    if (!message) return;

    // Add user message
    addChatMessage(message, 'user');
    input.value = '';

    // Show typing indicator
    const typing = addTypingIndicator();

    // Send to API
    fetch('/api/chatbot', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: message })
    })
    .then(r => r.json())
    .then(data => {
        typing.remove();
        addChatMessage(data.response || 'Sorry, I encountered an error.', 'bot');
        
        // If there are results, display them
        if (data.results && data.results.length > 0) {
            let resultHtml = '<div style="margin-top:8px;font-size:12px;">';
            data.results.forEach(r => {
                resultHtml += '<div style="padding:6px 0;border-bottom:1px solid rgba(0,0,0,0.05);">';
                Object.entries(r).forEach(([key, val]) => {
                    resultHtml += `<span style="color:var(--text-muted)">${key}:</span> <strong>${val}</strong> `;
                });
                resultHtml += '</div>';
            });
            resultHtml += '</div>';
            addChatMessage(resultHtml, 'bot', true);
        }
    })
    .catch(() => {
        typing.remove();
        addChatMessage('Sorry, something went wrong. Please try again.', 'bot');
    });
}

function addChatMessage(content, type, isHtml = false) {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = `chat-message ${type}`;
    
    const avatar = type === 'bot' 
        ? '<div class="chat-avatar"><i class="bi bi-robot"></i></div>' 
        : '';
    
    const bubbleContent = isHtml ? content : escapeHtml(content).replace(/\n/g, '<br>');
    
    div.innerHTML = `${avatar}<div class="chat-bubble">${bubbleContent}</div>`;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function addTypingIndicator() {
    const container = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'chat-message bot';
    div.innerHTML = `
        <div class="chat-avatar"><i class="bi bi-robot"></i></div>
        <div class="chat-bubble">
            <div class="typing-indicator"><span></span><span></span><span></span></div>
        </div>
    `;
    container.appendChild(div);
    container.scrollTop = container.scrollHeight;
    return div;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
</script>
