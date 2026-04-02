<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reverb Chat Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        #chat-messages { border: 1px solid #ccc; padding: 10px; height: 300px; overflow-y: auto; margin-bottom: 10px; }
        .message { margin: 5px 0; display: flex; align-items: center; }
        .message img { border-radius: 50%; margin-right: 5px; }
        .outgoing { justify-content: flex-end; color: blue; }
        .incoming { justify-content: flex-start; color: green; }
    </style>
</head>
<body>

<h2>Reverb Chat Test</h2>

<div id="chat-messages"></div>

<form id="chat-form">
    <input type="text" id="chat-text" placeholder="Type a message" />
    <button type="submit" id="send-btn">Send</button>
</form>

<!-- CDN for Pusher & Laravel Echo (browser friendly) -->
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.5.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.12.0/dist/echo.iife.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {

    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-text');
    const chatMessagesDiv = document.getElementById('chat-messages');

    // Auth user from Blade, fallback for testing
    const authUserId = {{ auth()->id() ?? 1 }};
    const authUserImage = '/images/auth-user.jpg';

    // Example selected user (receiver)
    const selectedUserId = 2;
    const selectedUserImage = '/images/receiver.jpg';

    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Initialize Laravel Echo with Reverb
    const Echo = new window.Echo.default({
        broadcaster: 'reverb',
        key: 'oxrhvghmaxbllswpraj5',
        wsHost: '127.0.0.1',
        wsPort: 8080,
        forceTLS: false,
        encrypted: false,
        disableStats: true,
    });

    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message) return;

        fetch('/send-message', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                chat_messages: message,
                receiver_id: selectedUserId
            })
        })
        .then(res => res.json())
        .then(msg => {
            chatMessagesDiv.innerHTML += `
                <div class="message outgoing">
                    <img src="${authUserImage}" width="30" />
                    <span>${msg.chat_messages}</span>
                </div>
            `;
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
            chatInput.value = '';
        });
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // Listen for Reverb events
    Echo.private(`chat.${authUserId}`)
        .listen('.message.sent', (e) => {
            const msg = e.message;
            const cls = msg.sender_id === authUserId ? 'outgoing' : 'incoming';
            const img = msg.sender_id === authUserId ? authUserImage : selectedUserImage;

            chatMessagesDiv.innerHTML += `
                <div class="message ${cls}">
                    <img src="${img}" width="30" />
                    <span>${msg.chat_messages}</span>
                </div>
            `;
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        });

});
</script>

</body>
</html>