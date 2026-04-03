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

document.addEventListener('DOMContentLoaded', function () {

    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-text');
    const chatMessagesDiv = document.getElementById('chat-messages');
    const contactsList = document.getElementById('contacts-list');

    let selectedUserId = null;
    let selectedUserImage = null;

    const authUserId = window.authUser.id;
    const authUserImage = window.authUser.image;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // ----------- HELPER: FORMAT UTC TIME TO IST -----------
    function formatTimeToIST(utcTime) {
        const date = new Date(utcTime);
        return date.toLocaleTimeString('en-IN', {
            timeZone: 'Asia/Kolkata',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    // ----------- MAP USER IMAGES + INIT LAST MESSAGE -----------
    const contacts = Array.from(document.querySelectorAll('.contact-item'));
    const userImages = {};
    contacts.forEach(c => {
        userImages[c.dataset.id] = c.dataset.image;
        c.dataset.lastMessage = '1970-01-01T00:00:00Z'; // default old date
    });

    // ----------- REORDER CONTACTS BASED ON LAST MESSAGE -----------
    function reorderContacts() {
        const sorted = contacts.sort((a, b) => {
            const timeA = new Date(a.dataset.lastMessage).getTime();
            const timeB = new Date(b.dataset.lastMessage).getTime();
            return timeB - timeA;
        });
        sorted.forEach(c => contactsList.appendChild(c));
    }

    // ----------- INITIAL LOAD: FETCH LAST MESSAGE FOR ALL CONTACTS -----------
    const fetchPromises = contacts.map(contact => {
        const userId = contact.dataset.id;
        return fetch(`/messages/${userId}?latest=1`)
            .then(res => res.json())
            .then(messages => {
                if (messages.length) {
                    const lastMsg = messages[messages.length - 1];
                    contact.dataset.lastMessage = lastMsg.created_at;

                    const preview = contact.querySelector('.contact-preview');
                    const timeElem = contact.querySelector('.contact-time');

                    if (preview) preview.innerText = lastMsg.chat_messages;
                    if (timeElem) timeElem.innerText = formatTimeToIST(lastMsg.created_at);
                }
            });
    });

    Promise.all(fetchPromises).then(() => {
        reorderContacts(); // reorder after fetching all last messages
    });

    // ----------- DISPLAY MESSAGE IN CHAT WINDOW -----------
    function displayMessage(msg) {
        const senderImg = msg.sender_id == authUserId ? authUserImage : msg.sender_image || userImages[msg.sender_id];
        chatMessagesDiv.innerHTML += `
            <div class="message ${msg.sender_id == authUserId ? 'outgoing' : ''}">
                <img src="${senderImg}" />
                <div class="message-content">
                    ${msg.chat_messages}
                    <span class="message-time">${formatTimeToIST(msg.created_at)}</span>
                </div>
            </div>
        `;
        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }

    // ----------- CONTACT CLICK -----------
    document.addEventListener('click', function (e) {
        const contact = e.target.closest('.contact-item');
        if (!contact) return;

        selectedUserId = contact.dataset.id;
        selectedUserImage = contact.dataset.image;

        document.getElementById('chat-user-name').innerText = contact.dataset.name;
        document.getElementById('chat-user-img').src = selectedUserImage;

        // Reset badge + unread count
        const badge = contact.querySelector('.badge-dot');
        if (badge) {
            badge.classList.remove('bg-danger');
            badge.classList.add('bg-success');
        }
        const countElem = contact.querySelector('.unread-count');
        if (countElem) {
            countElem.innerText = 0;
            countElem.classList.add('d-none');
        }

        // Load messages for selected contact
        chatMessagesDiv.innerHTML = '';
        fetch(`/messages/${selectedUserId}`)
            .then(res => res.json())
            .then(messages => {
                messages.forEach(msg => displayMessage(msg));

                // Update last message timestamp and reorder
                if (messages.length) {
                    contact.dataset.lastMessage = messages[messages.length - 1].created_at;
                    const preview = contact.querySelector('.contact-preview');
                    const timeElem = contact.querySelector('.contact-time');
                    if (preview) preview.innerText = messages[messages.length - 1].chat_messages;
                    if (timeElem) timeElem.innerText = formatTimeToIST(messages[messages.length - 1].created_at);
                    reorderContacts();
                }
            });
    });

    // ----------- SEND MESSAGE -----------
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
            displayMessage(msg);

            const contactElem = document.querySelector(`.contact-item[data-id="${selectedUserId}"]`);
            if (contactElem) {
                contactElem.dataset.lastMessage = msg.created_at || new Date().toISOString();
                const preview = contactElem.querySelector('.contact-preview');
                const timeElem = contactElem.querySelector('.contact-time');
                if (preview) preview.innerText = msg.chat_messages;
                if (timeElem) timeElem.innerText = formatTimeToIST(msg.created_at);
            }

            reorderContacts();
            chatInput.value = '';
        });
    }

    chatForm.addEventListener('submit', e => {
        e.preventDefault();
        sendMessage();
    });

    // ----------- REALTIME LISTENER -----------
    window.Echo.private(`chat.${authUserId}`)
        .listen('.message.sent', (msg) => {
            const m = msg.message;
            if (!m.sender_id) return;

            const contactElem = document.querySelector(`.contact-item[data-id="${m.sender_id}"]`);
            if (contactElem) {
                contactElem.dataset.lastMessage = m.created_at;

                const preview = contactElem.querySelector('.contact-preview');
                const timeElem = contactElem.querySelector('.contact-time');
                if (preview) preview.innerText = m.chat_messages;
                if (timeElem) timeElem.innerText = formatTimeToIST(m.created_at);
            }

            reorderContacts();

            if (selectedUserId == m.sender_id || selectedUserId == m.receiver_id) {
                displayMessage(m);
            } else {
                // Show unread badge + count
                if (contactElem) {
                    const badge = contactElem.querySelector('.badge-dot');
                    if (badge) {
                        badge.classList.remove('bg-success');
                        badge.classList.add('bg-danger');
                    }

                    const countElem = contactElem.querySelector('.unread-count');
                    if (countElem) {
                        countElem.classList.remove('d-none');
                        let current = parseInt(countElem.innerText) || 0;
                        countElem.innerText = current + 1 > 99 ? '99+' : current + 1;
                    }
                }
            }
        });

});