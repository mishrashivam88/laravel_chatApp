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
    const lastMsg = [...messages].reverse().find(m => !m.is_deleted) 
                  || messages[messages.length - 1];

    contact.dataset.lastMessage = lastMsg.created_at;
    contact.dataset.lastMessageId = lastMsg.id;

    const preview = contact.querySelector('.contact-preview');
    const timeElem = contact.querySelector('.contact-time');

   
    if (preview) {
        if (lastMsg.is_deleted) {
            preview.innerText = lastMsg.chat_messages.replace(/<[^>]+>/g, '');
        } else if (lastMsg.file_type === 'image') {
            preview.innerText = '📷 Image';
        } else if (lastMsg.file_type === 'video') {
            preview.innerText = '🎥 Video';
        } else if (lastMsg.file_type === 'file') {
            preview.innerText = '📄 File';
        } else {
            preview.innerText = lastMsg.chat_messages;
        }
    }
    if (timeElem) {
        if (lastMsg.is_deleted) {
            timeElem.innerText = ''; 
        } else {
            timeElem.innerText = formatTimeToIST(lastMsg.created_at);
        }
    }
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
    chatMessagesDiv.addEventListener('scroll', () => {
        if (chatMessagesDiv.scrollTop === 0 && !loading && hasMore) {
            loadMoreMessages();
        }
    });
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

let currentPage = 1;
let loading = false;
let hasMore = true;
function loadMessages() {
    console.log("LOAD MESSAGES CALLED");
    chatMessagesDiv.innerHTML = '';
    currentPage = 1;
    hasMore = true;

    fetch(`/messages/${selectedUserId}?page=1`)
        .then(res =>{ 
            console.log("API RESPONSE STATUS:", res.status);
            return res.json()
        }
        )
        .then(data => {
            console.log("FULL API DATA:", data);
             const messages = data.messages?.data || [];
             console.log("MESSAGES ARRAY:", messages);
            renderMessages(messages, true);
            hasMore = data.messages.next_page_url !== null; 
        });
}
function renderMessages(messages, scrollBottom = false) {
     if (!messages || !Array.isArray(messages)) return;
    messages.reverse().forEach(msg => displayMessage(msg, false)); 

    if (scrollBottom) {
        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }
}

function loadMoreMessages() {

    loading = true;
    currentPage++;

    let oldHeight = chatMessagesDiv.scrollHeight;

    fetch(`/messages/${selectedUserId}?page=${currentPage}`)
        .then(res => res.json())
        .then(data => {
            const messages = data.messages?.data || []; 

            messages.reverse().forEach(msg => {
                displayMessage(msg, true); 
            });

            let newHeight = chatMessagesDiv.scrollHeight;

            chatMessagesDiv.scrollTop = newHeight - oldHeight;

            hasMore = data.messages.next_page_url !== null;
            loading = false;
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

function sendMessage() {
    const message = chatInput.value.trim();
    const fileInput = document.getElementById('fileInput');

    if (!message && !fileInput.files[0]) return;

    let formData = new FormData();
    formData.append('chat_messages', message);
    formData.append('receiver_id', selectedUserId);

    if (fileInput.files[0]) {
        formData.append('file', fileInput.files[0]);
    }

    fetch('/send-message', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': token
        },
        body: formData
    })
    .then(res => res.json())
    .then(msg => {
        console.log(msg);
        displayMessage(msg);
        updateSidebar(selectedUserId, msg);
        chatInput.value = '';
        fileInput.value = '';
    });
}

//  DISPLAY 
function displayMessage(msg , prepend = false) {

    if (typeof msg === 'string') {
        try { msg = JSON.parse(msg); } catch { return; }
    }

    const senderImg = msg.sender_id == authUserId
        ? authUserImage
        : msg.sender_image || userImages[msg.sender_id];

    let mediaHtml = '';

   
    if (msg.file_type === 'image' && msg.file_url) {
    mediaHtml = `
        <div class="media-message">
            <img src="${msg.file_url}" 
                 class="chat-image"
                  onload="scrollToBottom()"
                 onclick="openImage('${msg.file_url}')">
        </div>
    `;
}
    //  VIDEO
    else if (msg.file_type === 'video' && msg.file_url) {
        mediaHtml = `
            <video controls class="chat-video">
                <source src="${msg.file_url}">
            </video>
        `;
    } 
    
    //  FILE
    else if (msg.file_type === 'file' && msg.file_url) {
        mediaHtml = `
            <a href="${msg.file_url}" target="_blank" class="chat-file">
                 Download File
            </a>
        `;
    }

     let html = `
        <div class="message ${msg.sender_id == authUserId ? 'outgoing' : ''}" data-id="${msg.id}">
            <img src="${senderImg}" class="avatar" />
            <div class="message-content">
                ${msg.chat_messages ?? ''}
                ${mediaHtml}
            </div>
        </div>
    `;

    if (prepend) {
        chatMessagesDiv.insertAdjacentHTML('afterbegin', html);
    } else {
        chatMessagesDiv.insertAdjacentHTML('beforeend', html);
    }

   
}


//  SIDEBAR
function updateSidebar(userId, msg) {
    const contact = document.querySelector(`.contact-item[data-id="${userId}"]`);
    if (!contact) return;

    contact.dataset.lastMessage = msg.created_at;
    contact.dataset.lastMessageId = msg.id;

    const preview = contact.querySelector('.contact-preview');
    const timeElem = contact.querySelector('.contact-time');

    if (preview) {
        if (msg.chat_messages) {
            preview.innerText = msg.chat_messages;
        } else if (msg.file_type === 'image') {
            preview.innerText = '📷 Photo';
        } else if (msg.file_type === 'video') {
            preview.innerText = '🎥 Video';
        } else {
            preview.innerText = '📎 File';
        }
    }

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

function handleIncomingMessage(m) {

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

// function handleDeleted(e) {
//     const msgDiv = document.querySelector(`.message[data-id="${e.messageId}"]`);
//     if (!msgDiv) return;

//     const content = msgDiv.querySelector('.message-content');
//     const time = content.querySelector('.message-time')?.outerHTML || '';
//     const tick = content.querySelector('.tick')?.outerHTML || '';

//     const text = (e.senderId == authUserId)
//         ? "<i>Deleted by you</i>"
//         : "<i>Deleted by author</i>";

//     content.innerHTML = `${text} ${time} ${tick}`;

//     const otherUserId = (e.senderId == authUserId) ? e.receiverId : e.senderId;
//     const contact = document.querySelector(`.contact-item[data-id="${otherUserId}"]`);

//     if (contact && contact.dataset.lastMessage == e.createdAt) {
//         const preview = contact.querySelector('.contact-preview');
//         if (preview) preview.innerText = "Message deleted";
//     }
// }
function handleDeleted(e) {
    const msgDiv = document.querySelector(`.message[data-id="${e.messageId}"]`);
    if (msgDiv) {

        msgDiv.classList.add('deleted');

        const content = msgDiv.querySelector('.message-content');
        content.innerHTML = '';

        const text = (e.senderId == authUserId)
            ? "<i>Deleted by you</i>"
            : "<i>Deleted by author</i>";

        const deletedDiv = document.createElement('div');
        deletedDiv.className = 'deleted-msg';
        deletedDiv.innerHTML = text;

        content.appendChild(deletedDiv);
    }

    const otherUserId = (e.senderId == authUserId)
        ? e.receiverId
        : e.senderId;

    const contact = document.querySelector(`.contact-item[data-id="${otherUserId}"]`);

    if (!contact) return;

    const preview = contact.querySelector('.contact-preview');
    if (contact.dataset.lastMessageId == e.messageId) {

        if (preview) preview.innerText = "🗑️ Message deleted";

    }
}
function scrollToBottom() {
    setTimeout(() => {
        chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
    }, 50);
}

//  DELETE 
// function initDelete() {
//     document.addEventListener('click', function (e) {
//         const msgDiv = e.target.closest('.message');
//         if (!msgDiv || !msgDiv.classList.contains('outgoing')) return;

//         const msgId = msgDiv.dataset.id;

//         if (confirm("Delete this message?")) {
//             fetch(`/delete-message/${msgId}`, {
//                 method: 'DELETE',
//                 headers: { 'X-CSRF-TOKEN': token }
//             });
//         }
//     });
// }
function initDelete() {
    document.addEventListener('click', function (e) {

        const msgDiv = e.target.closest('.message');

        if (!msgDiv) return;
        if (!msgDiv.classList.contains('outgoing')) return;

        const msgId = msgDiv.dataset.id;

        if (!msgId) return;

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


