import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'oxrhvghmaxbllswpraj5',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    encrypted: false,
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

document.addEventListener('DOMContentLoaded', function() {

    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-text');
    const chatMessagesDiv = document.getElementById('chat-messages');

    let selectedUserId = null;
    let selectedUserImage = null;

    const authUserId = window.authUser.id;
    const authUserImage = window.authUser.image;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // -----------  User lookup table -----------
    const userImages = {};
    document.querySelectorAll('.contact-item').forEach(c => {
        userImages[c.dataset.id] = c.dataset.image;
    });

    // -----------  Message queue -----------
    const messageQueue = []; // store messages until user selects

    // -----------  Display function -----------
    function displayMessage(msg) {
        const senderImg = msg.sender_id == authUserId ? authUserImage : msg.sender_image || userImages[msg.sender_id];
        chatMessagesDiv.innerHTML += `
            <div class="message ${msg.sender_id == authUserId ? 'outgoing' : ''}">
                <img src="${senderImg}" />
                <div class="message-content">${msg.chat_messages}</div>
            </div>
        `;
        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }

    // -----------  Contact click -----------
    document.addEventListener('click', function(e) {
        const contact = e.target.closest('.contact-item');
        if (!contact) return;

        selectedUserId = contact.dataset.id;
        selectedUserImage = contact.dataset.image;
        const userName = contact.dataset.name;

        document.getElementById('chat-user-name').innerText = userName;
        document.getElementById('chat-user-img').src = selectedUserImage;

        // Display queued messages for this user
        chatMessagesDiv.innerHTML = '';
        messageQueue.forEach(msg => {
            if (msg.sender_id == selectedUserId || msg.receiver_id == selectedUserId) {
                displayMessage(msg);
            }
        });

        // Fetch older messages from backend
        fetch(`/messages/${selectedUserId}`)
            .then(res => res.json())
            .then(messages => {
                messages.forEach(msg => displayMessage(msg));
            });
    });

    // -----------  Send message -----------
    function sendMessage() {
        const message = chatInput.value.trim();
        if (!message || !selectedUserId) return;

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
            messageQueue.push(msg); // store sent message
            displayMessage(msg);
            chatInput.value = '';
        });
    }

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        sendMessage();
    });

    // ----------- Echo listener (Realtime) -----------
    window.Echo.private(`chat.${authUserId}`)
        .listen('.message.sent', (msg) => {
            if (!msg.sender_id) return; // safety check

            messageQueue.push(msg); // store all incoming messages

            // Display immediately if current chat is open with sender/receiver
            if (selectedUserId == msg.sender_id || selectedUserId == msg.receiver_id) {
                displayMessage(msg);
            } else {
                // Optional: show notification badge for new message
                const contactElem = document.querySelector(`.contact-item[data-id="${msg.sender_id}"]`);
                if (contactElem && !contactElem.classList.contains('new-message')) {
                    contactElem.classList.add('new-message');
                    const badge = contactElem.querySelector('.badge-dot');
                    if (badge) badge.classList.add('bg-danger');
                }
            }
        });

});