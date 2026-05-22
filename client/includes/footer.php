</div> 
    <footer style="background-color: #1a1a1a; color: #999; padding: 60px 50px 30px; text-align: center; margin-top: 50px;">
        <h2 style="color: #fff; margin-bottom: 15px; letter-spacing: 2px;">MOWGARDEN</h2>
        <p style="font-size: 15px; max-width: 500px; margin: 0 auto; line-height: 1.6;">
            Mang thiên nhiên xanh mát vào không gian sống của bạn. Chúng tôi cung cấp các loại cây cảnh chất lượng cao với dịch vụ tận tâm nhất.
        </p>
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #333; font-size: 13px;">
            &copy; 2026 Đồ án Phát triển Mã nguồn mở.
        </div>
    </footer>

<style>
    .chatbot-toggler { 
        position: fixed; 
        bottom: 30px; 
        right: 30px; 
        width: 60px; 
        height: 60px; 
        background: #2d5a27; 
        color: white; 
        border-radius: 50%; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        cursor: pointer; 
        box-shadow: 0 4px 15px rgba(0,0,0,0.2); 
        z-index: 9999; 
        font-size: 24px; 
        transition: 0.3s; 
        border: none; 
    }
    .chatbot-toggler:hover { 
        transform: scale(1.1); 
        background: #1f401b; 
    }
    
    .chatbot-window { 
        position: fixed; 
        bottom: 100px; 
        right: 30px; 
        width: 350px; 
        background: #fff; 
        border-radius: 12px; 
        box-shadow: 0 5px 20px rgba(0,0,0,0.15); 
        display: none; 
        flex-direction: column; 
        overflow: hidden; 
        z-index: 9999; 
        border: 1px solid #ddd; 
    }
    .chat-header { 
        background: #2d5a27; 
        color: white; 
        padding: 15px; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        font-weight: bold; 
    }
    .chat-header span.close-chat { 
        cursor: pointer; 
        font-size: 20px; 
    }
    
    .chat-body { 
        height: 350px; 
        padding: 15px; 
        overflow-y: auto; 
        background: #f9f9f9; 
        display: flex; 
        flex-direction: column; 
        gap: 10px; 
    }
    .message { 
        max-width: 80%; 
        padding: 10px 14px; 
        border-radius: 15px; 
        font-size: 14px; 
        line-height: 1.4; 
        word-wrap: break-word; 
    }
    .msg-bot { 
        background: #e8f5e9; 
        color: #1b5e20; 
        align-self: flex-start; 
        border-bottom-left-radius: 0; 
        border: 1px solid #c8e6c9; 
    }
    .msg-user { 
        background: #2d5a27; 
        color: white; 
        align-self: flex-end; 
        border-bottom-right-radius: 0; 
    }
    
    .chat-footer { 
        padding: 10px; 
        background: white; 
        border-top: 1px solid #ddd; 
        display: flex; 
        gap: 5px; 
    }
    .chat-input { 
        flex: 1; 
        padding: 10px; 
        border: 1px solid #ddd; 
        border-radius: 20px; 
        outline: none; 
        font-size: 14px; 
    }
    .chat-input:focus { 
        border-color: #2d5a27; 
    }
    .btn-send { 
        background: transparent; 
        border: none; 
        color: #2d5a27; 
        font-size: 20px; 
        cursor: pointer; 
        padding: 0 10px; 
        transition: 0.2s; 
    }
    .btn-send:hover { 
        color: #1b4332; 
        transform: scale(1.1); 
    }
    .typing-indicator { 
        font-size: 12px; 
        color: #888; 
        font-style: italic; 
        display: none; 
        margin-left: 10px; 
        }
</style>

<button class="chatbot-toggler" onclick="toggleChat()">
    <i class="fa-solid fa-robot"></i>
</button>

<div class="chatbot-window" id="chatWindow">
    <div class="chat-header">
        <div>
            <i class="fa-solid fa-leaf"></i> 
            MowGarden AI
        </div>
        <span class="close-chat" onclick="toggleChat()">&times;</span>
    </div>
    <div class="chat-body" id="chatBody">
        <div class="message msg-bot">
            Xin chào! Tôi là trợ lý ảo của MowGarden. 
            Tôi có thể giúp bạn tìm cây cảnh hay tư vấn cách chăm sóc cây nào? 🌱
        </div>
    </div>
    <div class="typing-indicator" id="typingIndicator">
        Trợ lý đang gõ...
    </div>
    <div class="chat-footer">
        <input type="text" id="chatInput" class="chat-input" placeholder="Nhập câu hỏi của bạn..." onkeypress="handleKeyPress(event)">
        <button class="btn-send" onclick="sendMessage()">
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
    function toggleChat() {
        let chatWin = document.getElementById('chatWindow');
        chatWin.style.display = chatWin.style.display === 'flex' ? 'none' : 'flex';
    }

    function handleKeyPress(e) {
        if (e.key === 'Enter') sendMessage();
    }

    async function sendMessage() {
        let input = document.getElementById('chatInput');
        let message = input.value.trim();
        if (!message) return;

        appendMessage(message, 'msg-user');
        input.value = '';
        
        document.getElementById('typingIndicator').style.display = 'block';
        scrollToBottom();

        try {
            let response = await fetch('../api/chatbot.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: message })
            });
            
            let data = await response.json();
            document.getElementById('typingIndicator').style.display = 'none';
            
            if(data.reply) {
                let formattedReply = data.reply.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                formattedReply = formattedReply.replace(/\n/g, '<br>');
                appendMessage(formattedReply, 'msg-bot');
            } 
            else if (data.error) {
                appendMessage("⚠️ " + data.error, 'msg-bot');
            } 
            else {
                appendMessage("Xin lỗi, hệ thống không nhận được dữ liệu hợp lệ.", 'msg-bot');
            }
        } catch (error) {
            document.getElementById('typingIndicator').style.display = 'none';
            appendMessage("Lỗi mạng, không thể kết nối tới file API.", 'msg-bot');
        }
    }

    function appendMessage(text, className) {
        let chatBody = document.getElementById('chatBody');
        let msgDiv = document.createElement('div');
        msgDiv.className = 'message ' + className;
        msgDiv.innerHTML = text;
        chatBody.appendChild(msgDiv);
        scrollToBottom();
    }

    function scrollToBottom() {
        let chatBody = document.getElementById('chatBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    }
</script>
</body>
</html>