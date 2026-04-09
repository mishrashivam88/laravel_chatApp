import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: 'oxrhvghmaxbllswpraj5',
    wsHost: '127.0.0.1',
    wsPort: 8080,
    forceTLS: false,
    withCredentials: true,
    authEndpoint: '/broadcasting/auth',
});

//  GLOBAL STATE 
let selectedUserId = null;
let selectedUserImage = null;

const authUserId = window.authUser.id;
const authUserImage = window.authUser.image;
const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

let channel = null;

//  INIT 
document.addEventListener('DOMContentLoaded', () => {
    initEcho();
    initContacts();
    initChat();
    initRealtime();
    initDelete();
    markDeliveredOnLoad();
});

//  HELPERS 
function formatTimeToIST(utcTime) {
    const date = new Date(utcTime);
    return date.toLocaleTimeString('en-IN', {
        timeZone: 'Asia/Kolkata',
        hour: '2-digit',
        minute: '2-digit',
        hour12: true
    });
}

//  ECHO 
function initEcho() {

    // PRIVATE (messages)
    channel = window.Echo.private(`chat.${authUserId}`);

    // PRESENCE (online users)
    window.presence = window.Echo.join('online-users')

        .here(users => {
            console.log('ONLINE USERS:', users);
            users.forEach(user => markOnline(user.id));
        })

        .joining(user => {
            console.log('USER JOINED:', user);
            markOnline(user.id);
        })

        .leaving(user => {
            console.log('USER LEFT:', user);
            markOffline(user.id);
        });
}


function markOnline(userId) {
    const contact = document.querySelector(`.contact-item[data-id="${userId}"]`);
    if (!contact) return;

    const badge = contact.querySelector('.badge-dot');
    if (badge) {
        badge.classList.remove('bg-secondary');
        badge.classList.add('bg-success');
    }
}

function markOffline(userId) {
    const contact = document.querySelector(`.contact-item[data-id="${userId}"]`);
    if (!contact) return;

    const badge = contact.querySelector('.badge-dot');
    if (badge) {
        badge.classList.remove('bg-success');
        badge.classList.add('bg-secondary');
    }
}

//  CONTACTS 
let contacts = [];
let userImages = {};
let contactsList = null;

function initContacts() {
    contactsList = document.getElementById('contacts-list');
    contacts = Array.from(document.querySelectorAll('.contact-item'));

    contacts.forEach(c => {
        userImages[c.dataset.id] = c.dataset.image;
    });

    loadLastMessages();
}

function loadLastMessages() {
    const promises = contacts.map(contact => {
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

    Promise.all(promises).then(reorderContacts);
}

function reorderContacts() {
    const sorted = contacts.sort((a, b) => {
        return new Date(b.dataset.lastMessage) - new Date(a.dataset.lastMessage);
    });

    sorted.forEach(c => contactsList.appendChild(c));
}

//  CHAT 
let chatMessagesDiv = null;
let chatInput = null;

function initChat() {
    chatMessagesDiv = document.getElementById('chat-messages');
    chatInput = document.getElementById('chat-text');

    document.getElementById('chat-form').addEventListener('submit', e => {
        e.preventDefault();
        sendMessage();
    });

    document.addEventListener('click', handleContactClick);
}

function handleContactClick(e) {
    const contact = e.target.closest('.contact-item');
    if (!contact) return;

    selectedUserId = contact.dataset.id;
    selectedUserImage = contact.dataset.image;

    document.getElementById('chat-user-name').innerText = contact.dataset.name;
    document.getElementById('chat-user-img').src = selectedUserImage;

    resetUnread(contact);
    loadMessages();
    markSeen();
}

function resetUnread(contact) {
    const badge = contact.querySelector('.badge-dot');
    const countElem = contact.querySelector('.unread-count');

    if (badge) {
        badge.classList.remove('bg-danger');
        badge.classList.add('bg-success');
    }

    if (countElem) {
        countElem.innerText = 0;
        countElem.classList.add('d-none');
    }
}

function loadMessages() {
    chatMessagesDiv.innerHTML = '';

    fetch(`/messages/${selectedUserId}`)
        .then(res => res.json())
        .then(data => {
            data.messages.forEach(displayMessage);
        });
}

function markSeen() {
    fetch(`/messages/${selectedUserId}/seen`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({ auth_id: authUserId })
    });
}

//  SEND 
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
        updateSidebar(selectedUserId, msg);
        chatInput.value = '';
    });
}

//  DISPLAY 
function displayMessage(msg) {
    const senderImg = msg.sender_id == authUserId
        ? authUserImage
        : msg.sender_image || userImages[msg.sender_id];

    let tickHtml = '';

    if (msg.sender_id == authUserId) {
        let ticks = '✔';
        let cls = '';

        if (msg.seen) { ticks = '✔✔'; cls = 'seen'; }
        else if (msg.delivered) { ticks = '✔✔'; cls = 'delivered'; }

        tickHtml = `<span class="tick ${cls}">${ticks}</span>`;
    }

    chatMessagesDiv.innerHTML += `
        <div class="message ${msg.sender_id == authUserId ? 'outgoing' : ''}" data-id="${msg.id}">
            <img src="${senderImg}" />
            <div class="message-content">
                ${msg.chat_messages}
                <span class="message-time">${formatTimeToIST(msg.created_at)}</span>
                ${tickHtml}
            </div>
        </div>
    `;

    chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
}

//  SIDEBAR
function updateSidebar(userId, msg) {
    const contact = document.querySelector(`.contact-item[data-id="${userId}"]`);
    if (!contact) return;

    contact.dataset.lastMessage = msg.created_at;

    const preview = contact.querySelector('.contact-preview');
    const timeElem = contact.querySelector('.contact-time');

    if (preview) preview.innerText = msg.chat_messages;
    if (timeElem) timeElem.innerText = formatTimeToIST(msg.created_at);

    reorderContacts();
}

//  REALTIME 
function initRealtime() {

    channel.listen('.message.sent', handleIncomingMessage);
    channel.listen('.message.delivered', handleDelivered);
    channel.listen('.message.seen', handleSeen);
    channel.listen('.message.deleted', handleDeleted);
}

function handleIncomingMessage(msg) {
    const m = msg.message;
    const contact = document.querySelector(`.contact-item[data-id="${m.sender_id}"]`);

    fetch(`/messages/delivered`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token }
    });

    if (contact) updateSidebar(m.sender_id, m);

    if (selectedUserId == m.sender_id) {
        displayMessage(m);
        markSeen();
    } else {
        increaseUnread(contact);
    }
}

function increaseUnread(contact) {
    const badge = contact.querySelector('.badge-dot');
    const countElem = contact.querySelector('.unread-count');

    if (badge) {
        badge.classList.remove('bg-success');
        badge.classList.add('bg-danger');
    }

    if (countElem) {
        countElem.classList.remove('d-none');
        let c = parseInt(countElem.innerText) || 0;
        countElem.innerText = c + 1;
    }
}

function handleDelivered(e) {
    e.messageIds.forEach(id => {
        const tick = document.querySelector(`.message[data-id="${id}"] .tick`);
        if (tick) {
            tick.innerText = '✔✔';
            tick.classList.add('delivered');
        }
    });
}

function handleSeen(e) {
    e.messageIds.forEach(id => {
        const tick = document.querySelector(`.message[data-id="${id}"] .tick`);
        if (tick) {
            tick.innerText = '✔✔';
            tick.classList.add('seen');
        }
    });
}

function handleDeleted(e) {
    const msgDiv = document.querySelector(`.message[data-id="${e.messageId}"]`);
    if (!msgDiv) return;

    const content = msgDiv.querySelector('.message-content');
    const time = content.querySelector('.message-time')?.outerHTML || '';
    const tick = content.querySelector('.tick')?.outerHTML || '';

    const text = (e.senderId == authUserId)
        ? "<i>Deleted by you</i>"
        : "<i>Deleted by author</i>";

    content.innerHTML = `${text} ${time} ${tick}`;

    const otherUserId = (e.senderId == authUserId) ? e.receiverId : e.senderId;
    const contact = document.querySelector(`.contact-item[data-id="${otherUserId}"]`);

    if (contact && contact.dataset.lastMessage == e.createdAt) {
        const preview = contact.querySelector('.contact-preview');
        if (preview) preview.innerText = "Message deleted";
    }
}

//  DELETE 
function initDelete() {
    document.addEventListener('click', function (e) {
        const msgDiv = e.target.closest('.message');
        if (!msgDiv || !msgDiv.classList.contains('outgoing')) return;

        const msgId = msgDiv.dataset.id;

        if (confirm("Delete this message?")) {
            fetch(`/delete-message/${msgId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': token }
            });
        }
    });
}

//  INIT LOAD
function markDeliveredOnLoad() {
    fetch(`/messages/delivered`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': token }
    });
}