<?php
// includes/chatbot_widget.php - Universal Floating AI Civic Assistant Widget
?>
<!-- CivicBot Floating AI Widget -->
<style>
/* Floating Trigger Button */
.civicbot-launcher {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 99999;
    display: flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    color: #ffffff;
    border: none;
    border-radius: 50px;
    padding: 12px 20px;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 0.95rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(2, 132, 199, 0.35);
    transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.civicbot-launcher:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 12px 30px rgba(2, 132, 199, 0.45);
}

.civicbot-launcher .bot-icon-wrap {
    width: 32px;
    height: 32px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    position: relative;
}

.civicbot-launcher .live-pulse-dot {
    position: absolute;
    top: 0;
    right: 0;
    width: 9px;
    height: 9px;
    background: #4ade80;
    border: 1.5px solid #ffffff;
    border-radius: 50%;
    animation: civicPulse 2s infinite;
}

@keyframes civicPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(74, 222, 128, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
}

/* Chat Window Modal */
.civicbot-window {
    position: fixed;
    bottom: 90px;
    right: 24px;
    width: 380px;
    max-width: calc(100vw - 32px);
    height: 540px;
    max-height: calc(100vh - 120px);
    background: #ffffff;
    border-radius: 20px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.2);
    border: 1px solid #e2e8f0;
    display: none;
    flex-direction: column;
    z-index: 99999;
    overflow: hidden;
    font-family: 'Plus Jakarta Sans', sans-serif;
    animation: civicSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes civicSlideUp {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

/* Chat Header */
.civicbot-header {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: #ffffff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.bot-profile-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bot-avatar-badge {
    width: 38px;
    height: 38px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.bot-text-details h4 {
    font-size: 1rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0;
}

.bot-text-details small {
    color: #94a3b8;
    font-size: 0.76rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

.bot-online-indicator {
    width: 7px;
    height: 7px;
    background: #10b981;
    border-radius: 50%;
    display: inline-block;
}

.civicbot-close-btn {
    background: rgba(255, 255, 255, 0.1);
    color: #ffffff;
    border: none;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
    transition: background 0.2s;
}
.civicbot-close-btn:hover { background: rgba(255, 255, 255, 0.25); }

/* Messages Body */
.civicbot-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8fafc;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.civic-msg {
    display: flex;
    gap: 10px;
    max-width: 86%;
    font-size: 0.88rem;
    line-height: 1.45;
}

.civic-msg-bot {
    align-self: flex-start;
}

.civic-msg-user {
    align-self: flex-end;
    flex-direction: row-reverse;
}

.msg-bot-avatar {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    flex-shrink: 0;
}

.msg-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    word-break: break-word;
}

.civic-msg-bot .msg-bubble {
    background: #ffffff;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    border-top-left-radius: 4px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.civic-msg-user .msg-bubble {
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    color: #ffffff;
    border-top-right-radius: 4px;
    box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
}

/* Quick Suggestion Chips */
.civic-quick-chips {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    margin-top: 6px;
    margin-bottom: 4px;
}

.chip-btn {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #0284c7;
    font-size: 0.78rem;
    font-weight: 700;
    padding: 6px 12px;
    border-radius: 20px;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
}
.chip-btn:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
}

/* Typing Indicator Animation */
.civic-typing-indicator {
    display: none;
    align-self: flex-start;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    padding: 8px 14px;
    border-radius: 16px;
    border-top-left-radius: 4px;
    font-size: 0.8rem;
    color: #64748b;
}

.typing-dot {
    width: 6px;
    height: 6px;
    background: #0284c7;
    border-radius: 50%;
    animation: typingPulse 1.4s infinite ease-in-out;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingPulse {
    0%, 60%, 100% { transform: translateY(0); opacity: 0.4; }
    30% { transform: translateY(-4px); opacity: 1; }
}

/* Chat Footer Input */
.civicbot-footer {
    padding: 12px 14px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.civic-input-field {
    flex: 1;
    border: 1.5px solid #e2e8f0;
    border-radius: 24px;
    padding: 10px 16px;
    font-family: inherit;
    font-size: 0.88rem;
    outline: none;
    background: #f8fafc;
    transition: border-color 0.2s, background 0.2s;
}

.civic-input-field:focus {
    border-color: #0284c7;
    background: #ffffff;
}

.civic-send-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    color: #ffffff;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.95rem;
    box-shadow: 0 3px 10px rgba(16, 185, 129, 0.3);
    transition: transform 0.2s;
    flex-shrink: 0;
}
.civic-send-btn:hover { transform: scale(1.08); }
</style>

<!-- Launcher Button -->
<button type="button" id="civicBotLauncher" class="civicbot-launcher" onclick="toggleCivicBot()">
    <div class="bot-icon-wrap">
        <i class="fa-solid fa-robot"></i>
        <div class="live-pulse-dot"></div>
    </div>
    <span>Ask Civic AI</span>
</button>

<!-- Chatbot Window -->
<div id="civicBotWindow" class="civicbot-window">
    <!-- Header -->
    <div class="civicbot-header">
        <div class="bot-profile-info">
            <div class="bot-avatar-badge">
                <i class="fa-solid fa-robot"></i>
            </div>
            <div class="bot-text-details">
                <h4>CivicBot AI</h4>
                <small><span class="bot-online-indicator"></span> 24/7 Smart Municipal AI</small>
            </div>
        </div>
        <button type="button" class="civicbot-close-btn" onclick="toggleCivicBot()" title="Close Assistant">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Messages Body -->
    <div id="civicChatBody" class="civicbot-body">
        <!-- Initial Welcome Message -->
        <div class="civic-msg civic-msg-bot">
            <div class="msg-bot-avatar"><i class="fa-solid fa-robot"></i></div>
            <div class="msg-bubble">
                👋 Hello! I am <strong>CivicBot</strong>, your 24/7 Smart City AI Assistant.<br><br>
                Ask me anything in <strong>English, తెలుగు, हिंदी, or ಕನ್ನಡ</strong>:
                <div class="civic-quick-chips">
                    <button type="button" class="chip-btn" onclick="sendQuickPrompt('How do I report a pothole on my street?')">🛣️ Report a Pothole</button>
                    <button type="button" class="chip-btn" onclick="sendQuickPrompt('How do I report overflowing garbage?')">🚯 Garbage Issue</button>
                    <button type="button" class="chip-btn" onclick="sendQuickPrompt('What are the emergency municipal helpline numbers?')">⚡ Emergency Numbers</button>
                    <button type="button" class="chip-btn" onclick="sendQuickPrompt('నా ఫిర్యాదుల స్థితిని ఎలా తెలుసుకోవాలి?')">🌐 తెలుగులో సహాయం</button>
                </div>
            </div>
        </div>

        <!-- Typing Indicator -->
        <div id="civicTyping" class="civic-typing-indicator">
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <div class="typing-dot"></div>
            <span style="margin-left:6px;">CivicBot is thinking...</span>
        </div>
    </div>

    <!-- Footer Input Bar -->
    <div class="civicbot-footer">
        <input type="text" id="civicUserInput" class="civic-input-field" placeholder="Ask anything in English, తెలుగు, हिंदी..." onkeydown="handleChatKeyDown(event)">
        <button type="button" id="civicSendBtn" class="civic-send-btn" onclick="sendChatMessage()" title="Send Message">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
let chatHistory = [];
let isBotOpen = false;

function toggleCivicBot() {
    const chatWin = document.getElementById('civicBotWindow');
    const launcher = document.getElementById('civicBotLauncher');
    
    isBotOpen = !isBotOpen;
    if (isBotOpen) {
        chatWin.style.display = 'flex';
        launcher.style.display = 'none';
        document.getElementById('civicUserInput').focus();
    } else {
        chatWin.style.display = 'none';
        launcher.style.display = 'flex';
    }
}

function handleChatKeyDown(e) {
    if (e.key === 'Enter') {
        sendChatMessage();
    }
}

function sendQuickPrompt(promptText) {
    document.getElementById('civicUserInput').value = promptText;
    sendChatMessage();
}

function appendUserBubble(text) {
    const chatBody = document.getElementById('civicChatBody');
    const typing = document.getElementById('civicTyping');

    const msgDiv = document.createElement('div');
    msgDiv.className = 'civic-msg civic-msg-user';
    msgDiv.innerHTML = `<div class="msg-bubble">${escapeHtml(text)}</div>`;
    
    chatBody.insertBefore(msgDiv, typing);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function appendBotBubble(markdownText) {
    const chatBody = document.getElementById('civicChatBody');
    const typing = document.getElementById('civicTyping');

    const msgDiv = document.createElement('div');
    msgDiv.className = 'civic-msg civic-msg-bot';
    
    const formattedHtml = parseSimpleMarkdown(markdownText);
    msgDiv.innerHTML = `
        <div class="msg-bot-avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="msg-bubble">${formattedHtml}</div>
    `;
    
    chatBody.insertBefore(msgDiv, typing);
    chatBody.scrollTop = chatBody.scrollHeight;
}

function sendChatMessage() {
    const input = document.getElementById('civicUserInput');
    const userText = input.value.trim();
    if (!userText) return;

    appendUserBubble(userText);
    input.value = '';

    chatHistory.push({ sender: 'user', text: userText });

    // Show Typing Indicator
    const typing = document.getElementById('civicTyping');
    typing.style.display = 'flex';
    const chatBody = document.getElementById('civicChatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    // Call Backend API
    const apiPath = window.location.pathname.includes('/peoplelogin/') || window.location.pathname.includes('/workerlogin/') || window.location.pathname.includes('/adminlogin/') ? '../api/ai_chat.php' : 'api/ai_chat.php';

    fetch(apiPath, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            message: userText,
            history: chatHistory
        })
    })
    .then(res => res.json())
    .then(data => {
        typing.style.display = 'none';
        const botReply = data.reply || "I'm having trouble connecting to the civic server. Please try again shortly.";
        appendBotBubble(botReply);
        chatHistory.push({ sender: 'bot', text: botReply });
    })
    .catch(err => {
        typing.style.display = 'none';
        appendBotBubble("⚠️ Unable to reach CivicBot AI right now. Please check your internet connection.");
    });
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

function parseSimpleMarkdown(text) {
    let html = escapeHtml(text);
    // Bold **text**
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    // Bullet points * or -
    html = html.replace(/^\s*[\*\-]\s+(.*)$/gm, '• $1');
    // Backticks `code`
    html = html.replace(/`(.*?)`/g, '<code style="background:#f1f5f9; color:#0284c7; padding:2px 5px; border-radius:4px; font-weight:700;">$1</code>');
    // Linebreaks
    html = html.replace(/\n/g, '<br>');
    return html;
}
</script>
