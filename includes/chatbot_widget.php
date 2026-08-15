<?php
// includes/chatbot_widget.php - Universal Floating AI Civic Assistant Widget with Voice Input & Emoji-Free Speaking Bot
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
    width: 390px;
    max-width: calc(100vw - 32px);
    height: 560px;
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
    padding: 14px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.bot-profile-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bot-avatar-badge {
    width: 36px;
    height: 36px;
    background: linear-gradient(135deg, #10b981 0%, #0284c7 100%);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
}

.bot-text-details h4 {
    font-size: 0.95rem;
    font-weight: 800;
    line-height: 1.2;
    margin: 0;
}

.bot-text-details small {
    color: #94a3b8;
    font-size: 0.74rem;
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

.header-action-controls {
    display: flex;
    align-items: center;
    gap: 6px;
}

/* Voice Mute / Speak Toggle in Header */
.civic-voice-toggle-btn {
    background: rgba(255, 255, 255, 0.12);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
    font-family: inherit;
    transition: all 0.2s;
}
.civic-voice-toggle-btn.voice-active {
    background: rgba(16, 185, 129, 0.25);
    border-color: #10b981;
    color: #4ade80;
}
.civic-voice-toggle-btn:hover { background: rgba(255, 255, 255, 0.25); }

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
    max-width: 88%;
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

.msg-bubble-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
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

/* Audio Read-Aloud Speaker Button */
.msg-speak-btn {
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.76rem;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 6px;
    align-self: flex-start;
    transition: all 0.2s;
}
.msg-speak-btn:hover {
    color: #0284c7;
    background: #e0f2fe;
}
.msg-speak-btn.is-speaking {
    color: #059669;
    animation: voicePulse 1.2s infinite;
}

@keyframes voicePulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.15); opacity: 0.7; }
}

/* Quick Suggestion & Language Chips */
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
    font-size: 0.8rem;
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

.chip-lang-btn {
    background: #f1f5f9;
    border: 1.5px solid #cbd5e1;
    color: #0f172a;
    font-size: 0.82rem;
    font-weight: 800;
    padding: 7px 14px;
    border-radius: 20px;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.2s;
}
.chip-lang-btn:hover {
    background: #0284c7;
    color: #ffffff;
    border-color: #0284c7;
    transform: translateY(-1px);
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

/* Chat Footer Input & Mic */
.civicbot-footer {
    padding: 10px 14px;
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

/* Voice Mic Button */
.civic-mic-btn {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #475569;
    border: 1.5px solid #cbd5e1;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
    transition: all 0.2s ease;
    flex-shrink: 0;
}
.civic-mic-btn:hover {
    background: #e2e8f0;
    color: #0f172a;
    transform: scale(1.05);
}
.civic-mic-btn.listening {
    background: #fee2e2;
    color: #dc2626;
    border-color: #ef4444;
    animation: micPulse 1.2s infinite;
}

@keyframes micPulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.6); }
    70% { transform: scale(1.1); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
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
    <span>Ask Civic AI 🎙️</span>
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
                <small><span class="bot-online-indicator"></span> 24/7 Multilingual Voice AI</small>
            </div>
        </div>
        <div class="header-action-controls">
            <button type="button" id="civicVoiceToggle" class="civic-voice-toggle-btn voice-active" onclick="toggleVoiceOutput()" title="Toggle Speaking Bot (Text-to-Speech)">
                <i class="fa-solid fa-volume-high" id="voiceToggleIcon"></i>
                <span id="voiceToggleText">Voice ON</span>
            </button>
            <button type="button" class="civicbot-close-btn" onclick="toggleCivicBot()" title="Close Assistant">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    </div>

    <!-- Messages Body -->
    <div id="civicChatBody" class="civicbot-body">
        <!-- 1st Welcome Message Asking for Language Preference -->
        <div class="civic-msg civic-msg-bot">
            <div class="msg-bot-avatar"><i class="fa-solid fa-robot"></i></div>
            <div class="msg-bubble-wrap">
                <div class="msg-bubble" id="welcomeBubble">
                    <strong>Namaste! Welcome to CivicBot AI.</strong><br>
                    Please choose your preferred language to speak or chat:<br>
                    <span style="font-size:0.84rem; color:#475569;">దయచేసి మీ భాషను ఎంచుకోండి / कृपया अपनी भाषा चुनें / ದಯವಿಟ್ಟು ನಿಮ್ಮ ಭಾಷೆಯನ್ನು ಆಯ್ಕೆಮಾಡಿ:</span>
                    <div class="civic-quick-chips" style="margin-top:10px;">
                        <button type="button" class="chip-lang-btn" onclick="selectBotLanguage('en')">🇬🇧 English</button>
                        <button type="button" class="chip-lang-btn" onclick="selectBotLanguage('te')">🇮🇳 తెలుగు</button>
                        <button type="button" class="chip-lang-btn" onclick="selectBotLanguage('hi')">🇮🇳 हिंदी</button>
                        <button type="button" class="chip-lang-btn" onclick="selectBotLanguage('kn')">🇮🇳 ಕನ್ನಡ</button>
                    </div>
                </div>
                <button type="button" class="msg-speak-btn" onclick="speakMessageContent('welcomeBubble', this)" title="Listen to message">
                    <i class="fa-solid fa-volume-high"></i> Listen
                </button>
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

    <!-- Footer Input Bar with Mic -->
    <div class="civicbot-footer">
        <button type="button" id="civicMicBtn" class="civic-mic-btn" onclick="toggleVoiceRecognition()" title="Speak in Telugu, Hindi, Kannada, or English">
            <i class="fa-solid fa-microphone" id="micIcon"></i>
        </button>
        <input type="text" id="civicUserInput" class="civic-input-field" placeholder="Type or click 🎙️ to speak..." onkeydown="handleChatKeyDown(event)">
        <button type="button" id="civicSendBtn" class="civic-send-btn" onclick="sendChatMessage()" title="Send Message">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
let chatHistory = [];
let isBotOpen = false;
let isVoiceEnabled = localStorage.getItem('civic_voice_enabled') !== 'false';
let recognition = null;
let isListening = false;
let currentUtterance = null;
let currentBotLang = document.documentElement.lang || 'en';

document.addEventListener('DOMContentLoaded', function() {
    initSpeechRecognition();
    updateVoiceToggleUI();
});

// 1. Language Selection Handler
function selectBotLanguage(lang) {
    currentBotLang = lang;
    if (recognition) {
        if (lang === 'te') recognition.lang = 'te-IN';
        else if (lang === 'hi' || lang === 'hn') recognition.lang = 'hi-IN';
        else if (lang === 'kn') recognition.lang = 'kn-IN';
        else recognition.lang = 'en-IN';
    }

    let responseMsg = '';
    let quickChipsHtml = '';

    if (lang === 'te') {
        responseMsg = "నమస్కారం! నేను మీ స్మార్ట్ సిటీ అసిస్టెంట్ సివిక్‌బాట్‌ని. మీ వీధిలో గుంతలు, చెత్త లేదా వీధి లైట్ల సమస్యల గురించి ఇక్కడ మాట్లాడండి లేదా టైప్ చేయండి:";
        quickChipsHtml = `
            <div class="civic-quick-chips">
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('రోడ్డుపై గుంతలు ఉన్నాయి ఫిర్యాదు ఎలా చేయాలి?')">🛣️ గుంతల సమస్య</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('చెత్త పేరుకుపోయింది తీయించగలరా?')">🚯 చెత్త సమస్య</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('మునిసిపల్ ఎమర్జెన్సీ నంబర్లు ఏమిటి?')">⚡ హెల్ప్‌లైన్ నంబర్లు</button>
            </div>
        `;
    } else if (lang === 'hi') {
        responseMsg = "नमस्ते! मैं सिविकबॉट हूँ। आप अपने इलाके की सड़क, कचरा, स्ट्रीटलाइट समस्याओं के बारे में पूछ सकते हैं या बोलकर बता सकते हैं:";
        quickChipsHtml = `
            <div class="civic-quick-chips">
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('सड़क पर गड्ढे की शिकायत कैसे दर्ज करें?')">🛣️ गड्ढे की शिकायत</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('कचरा नहीं उठ रहा है, क्या करें?')">🚯 कचरा समस्या</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('नगर निगम हेल्पलाइन नंबर क्या हैं?')">⚡ हेल्पलाइन नंबर</button>
            </div>
        `;
    } else if (lang === 'kn') {
        responseMsg = "ನಮಸ್ಕಾರ! ನಾನು ಸಿವಿಕ್‌ಬಾಟ್. ನಿಮ್ಮ ರಸ್ತೆ ಗುಂಡಿ, ಕಸದ ಸಮಸ್ಯೆ ಅಥವಾ ಬೀದಿ ದೀಪಗಳ ಬಗ್ಗೆ ಇಲ್ಲಿ ಮಾತನಾಡಿ ಅಥವಾ ಟೈಪ್ ಮಾಡಿ:";
        quickChipsHtml = `
            <div class="civic-quick-chips">
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('ರಸ್ತೆ ಗುಂಡಿ ದೂರು ಸಲ್ಲಿಸುವುದು ಹೇಗೆ?')">🛣️ ರಸ್ತೆ ಗುಂಡಿ</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('ಕಸ ವಿಲೇವಾರಿ ಸಮಸ್ಯೆ ಪರಿಹರಿಸಿ?')">🚯 ಕಸದ ಸಮಸ್ಯೆ</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('ತುರ್ತು ಸಹಾಯವಾಣಿ ಸಂಖ್ಯೆಗಳು ಯಾವುವು?')">⚡ ಸಹಾಯವಾಣಿ</button>
            </div>
        `;
    } else {
        responseMsg = "Hello! You have selected English. You can type or click the 🎙️ mic to speak regarding potholes, garbage, streetlights, or complaint status:";
        quickChipsHtml = `
            <div class="civic-quick-chips">
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('How do I report a pothole on my street?')">🛣️ Report a Pothole</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('How do I report overflowing garbage?')">🚯 Garbage Issue</button>
                <button type="button" class="chip-btn" onclick="sendQuickPrompt('What are the municipal emergency helpline numbers?')">⚡ Helpline Numbers</button>
            </div>
        `;
    }

    appendBotBubble(responseMsg, quickChipsHtml);
}

// 2. Toggle Chat Window
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
        stopSpeaking();
        if (isListening && recognition) {
            recognition.stop();
        }
    }
}

// 3. Toggle Voice Output (Text-to-Speech)
function toggleVoiceOutput() {
    isVoiceEnabled = !isVoiceEnabled;
    localStorage.setItem('civic_voice_enabled', isVoiceEnabled);
    updateVoiceToggleUI();
    if (!isVoiceEnabled) {
        stopSpeaking();
    }
}

function updateVoiceToggleUI() {
    const btn = document.getElementById('civicVoiceToggle');
    const icon = document.getElementById('voiceToggleIcon');
    const text = document.getElementById('voiceToggleText');
    if (isVoiceEnabled) {
        btn.classList.add('voice-active');
        icon.className = 'fa-solid fa-volume-high';
        text.innerText = 'Voice ON';
    } else {
        btn.classList.remove('voice-active');
        icon.className = 'fa-solid fa-volume-xmark';
        text.innerText = 'Muted';
    }
}

// 4. Speech-to-Text (Voice Recognition)
function initSpeechRecognition() {
    const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRec) {
        const micBtn = document.getElementById('civicMicBtn');
        if (micBtn) {
            micBtn.style.opacity = '0.4';
            micBtn.title = "Voice recognition not supported in this browser. Please use Chrome or Edge.";
        }
        return;
    }

    recognition = new SpeechRec();
    recognition.continuous = false;
    recognition.interimResults = false;

    // Detect language preference
    setRecognitionLang();

    recognition.onstart = function() {
        isListening = true;
        const micBtn = document.getElementById('civicMicBtn');
        const micIcon = document.getElementById('micIcon');
        const input = document.getElementById('civicUserInput');
        micBtn.classList.add('listening');
        micIcon.className = 'fa-solid fa-microphone-lines';
        input.placeholder = "🎙️ Listening to your voice... Speak now!";
    };

    recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        const input = document.getElementById('civicUserInput');
        input.value = transcript;
        sendChatMessage();
    };

    recognition.onerror = function(event) {
        console.warn("Speech Recognition Error:", event.error);
        stopListeningUI();
    };

    recognition.onend = function() {
        stopListeningUI();
    };
}

function setRecognitionLang() {
    if (!recognition) return;
    if (currentBotLang === 'te') recognition.lang = 'te-IN';
    else if (currentBotLang === 'hn' || currentBotLang === 'hi') recognition.lang = 'hi-IN';
    else if (currentBotLang === 'kn') recognition.lang = 'kn-IN';
    else recognition.lang = 'en-IN';
}

function toggleVoiceRecognition() {
    if (!recognition) {
        alert("Voice recognition is not supported on this browser. Please use Google Chrome or Microsoft Edge.");
        return;
    }

    if (isListening) {
        recognition.stop();
        stopListeningUI();
    } else {
        stopSpeaking();
        setRecognitionLang();

        try {
            recognition.start();
        } catch (e) {
            console.error(e);
        }
    }
}

function stopListeningUI() {
    isListening = false;
    const micBtn = document.getElementById('civicMicBtn');
    const micIcon = document.getElementById('micIcon');
    const input = document.getElementById('civicUserInput');
    if (micBtn) micBtn.classList.remove('listening');
    if (micIcon) micIcon.className = 'fa-solid fa-microphone';
    if (input) input.placeholder = "Type or click 🎙️ to speak...";
}

// 5. Clean Text for Natural Speech Synthesis (Removes all Emojis & Markdown)
function cleanTextForSpeech(rawText) {
    let clean = rawText.replace(/<[^>]*>?/gm, '')
                       .replace(/[\*#_`~•]/g, ' ')
                       .replace(/https?:\/\/[^\s]+/g, '');

    // Strip Unicode Emojis and Symbols
    clean = clean.replace(/([\u2700-\u27BF]|[\uE000-\uF8FF]|\uD83C[\uDC00-\uDFFF]|\uD83D[\uDC00-\uDFFF]|[\u2011-\u26FF]|\uD83E[\uDD10-\uDDFF])/g, '');
    try {
        clean = clean.replace(/[\p{Extended_Pictographic}\p{Emoji_Presentation}\p{Emoji}]/gu, '');
    } catch(e) {
        clean = clean.replace(/[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\u{1FA70}-\u{1FAFF}]/gu, '');
    }

    // Clean brackets and extra spaces
    clean = clean.replace(/\[.*?\]/g, '')
                 .replace(/\s+/g, ' ')
                 .trim();
    return clean;
}

let availableVoices = [];
function loadCivicVoices() {
    if ('speechSynthesis' in window) {
        availableVoices = window.speechSynthesis.getVoices();
    }
}
if ('speechSynthesis' in window) {
    window.speechSynthesis.onvoiceschanged = loadCivicVoices;
    loadCivicVoices();
}

function findBestVoiceForLang(langCode) {
    loadCivicVoices();
    if (!availableVoices || availableVoices.length === 0) return null;

    const prefix = langCode.split('-')[0].toLowerCase();
    
    // 1. Look for matching regional voice (Telugu, Hindi, Kannada, etc.)
    let match = availableVoices.find(v => {
        const vLang = v.lang.toLowerCase().replace('_', '-');
        const vName = v.name.toLowerCase();
        return (vLang.startsWith(prefix) || vLang.includes(langCode.toLowerCase())) &&
               (vName.includes('female') || vName.includes('google') || vName.includes('natural') || vName.includes('swara') || vName.includes('heera'));
    });

    if (!match) {
        match = availableVoices.find(v => v.lang.toLowerCase().replace('_', '-').startsWith(prefix));
    }

    // Return matched voice only if it actually belongs to the target language family!
    return match || null;
}

// Global speech queue to speak complete long responses seamlessly
window._activeCivicUtterance = null;
let speechQueue = [];
let isPlayingQueue = false;

function stopSpeaking() {
    speechQueue = [];
    isPlayingQueue = false;
    window._activeCivicUtterance = null;
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
    document.querySelectorAll('.msg-speak-btn').forEach(btn => {
        btn.classList.remove('is-speaking');
        btn.innerHTML = '<i class="fa-solid fa-volume-high"></i> Listen';
    });
}

// 6. Text-to-Speech (Speaking Bot) - Full Continuous Playback
function speakBotText(rawText, buttonElem) {
    if (!('speechSynthesis' in window)) {
        console.warn("SpeechSynthesis not supported on this browser.");
        return;
    }
    
    stopSpeaking();

    const cleanText = cleanTextForSpeech(rawText);
    if (!cleanText) return;

    // Split text into natural sentence chunks (using punctuation marks)
    const rawChunks = cleanText.split(/([.!?।\n]+)/g);
    const chunks = [];
    for (let i = 0; i < rawChunks.length; i += 2) {
        let sentence = (rawChunks[i] || '') + (rawChunks[i+1] || '');
        sentence = sentence.trim();
        if (sentence.length > 0) {
            chunks.push(sentence);
        }
    }

    if (chunks.length === 0) chunks.push(cleanText);

    speechQueue = chunks;
    isPlayingQueue = true;

    if (buttonElem) {
        buttonElem.classList.add('is-speaking');
        buttonElem.innerHTML = '<i class="fa-solid fa-volume-high"></i> Speaking...';
    }

    playNextInQueue(buttonElem);
}

function playNextInQueue(buttonElem) {
    if (!isPlayingQueue || speechQueue.length === 0) {
        stopSpeaking();
        return;
    }

    const currentSentence = speechQueue.shift();
    const utterance = new SpeechSynthesisUtterance(currentSentence);
    window._activeCivicUtterance = utterance;

    // Detect target language
    const isTelugu = /[\u0C00-\u0C7F]/.test(currentSentence) || currentBotLang === 'te';
    const isHindi = /[\u0900-\u097F]/.test(currentSentence) || currentBotLang === 'hi' || currentBotLang === 'hn';
    const isKannada = /[\u0C80-\u0CFF]/.test(currentSentence) || currentBotLang === 'kn';

    let targetLang = 'en-IN';
    if (isTelugu) targetLang = 'te-IN';
    else if (isHindi) targetLang = 'hi-IN';
    else if (isKannada) targetLang = 'kn-IN';

    utterance.lang = targetLang;

    // Assign matched native regional voice if available
    const matchedVoice = findBestVoiceForLang(targetLang);
    if (matchedVoice) {
        utterance.voice = matchedVoice;
    }

    utterance.pitch = 1.05;
    utterance.rate = 0.95;

    utterance.onend = function() {
        if (isPlayingQueue && speechQueue.length > 0) {
            setTimeout(function() { playNextInQueue(buttonElem); }, 40);
        } else {
            stopSpeaking();
        }
    };

    utterance.onerror = function(e) {
        console.warn("Speech Synthesis Error:", e);
        if (isPlayingQueue && speechQueue.length > 0) {
            setTimeout(function() { playNextInQueue(buttonElem); }, 40);
        } else {
            stopSpeaking();
        }
    };

    setTimeout(function() {
        if (window.speechSynthesis.paused) {
            window.speechSynthesis.resume();
        }
        window.speechSynthesis.speak(utterance);
    }, 40);
}

function speakMessageContent(bubbleId, btn) {
    const bubble = document.getElementById(bubbleId);
    if (!bubble) return;
    if (btn && btn.classList.contains('is-speaking')) {
        stopSpeaking();
    } else {
        speakBotText(bubble.innerText, btn);
    }
}

// 7. Send & Receive Chat Messages
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

let botMsgCounter = 0;
function appendBotBubble(markdownText, optionalCustomHtml = '') {
    const chatBody = document.getElementById('civicChatBody');
    const typing = document.getElementById('civicTyping');
    botMsgCounter++;
    const bubbleId = `botBubble_${botMsgCounter}`;

    const msgDiv = document.createElement('div');
    msgDiv.className = 'civic-msg civic-msg-bot';
    
    const formattedHtml = parseSimpleMarkdown(markdownText);
    const finalHtml = optionalCustomHtml ? (formattedHtml + optionalCustomHtml) : formattedHtml;

    msgDiv.innerHTML = `
        <div class="msg-bot-avatar"><i class="fa-solid fa-robot"></i></div>
        <div class="msg-bubble-wrap">
            <div class="msg-bubble" id="${bubbleId}">${finalHtml}</div>
            <button type="button" class="msg-speak-btn" onclick="speakMessageContent('${bubbleId}', this)" title="Listen to message">
                <i class="fa-solid fa-volume-high"></i> Listen
            </button>
        </div>
    `;
    
    chatBody.insertBefore(msgDiv, typing);
    chatBody.scrollTop = chatBody.scrollHeight;

    // Auto-Speak if enabled (speaks only clean text without reading HTML or buttons)
    if (isVoiceEnabled) {
        const speakBtn = msgDiv.querySelector('.msg-speak-btn');
        speakBotText(markdownText, speakBtn);
    }
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
    if (!text) return '';
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
