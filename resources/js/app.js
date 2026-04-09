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

let selectedUserId = null;
document.addEventListener('DOMContentLoaded', function () {

    document.addEventListener('DOMContentLoaded', function () {
        const logoutForm = document.querySelector('#logoutModal form');
        if (logoutForm) {
            logoutForm.addEventListener('submit', function (e) {

                if (window.Echo) {
                    window.Echo.leave('chat.presence');
                    console.log('Left presence channel');
                }
            });
        }
    });

    const chatForm = document.getElementById('chat-form');
    const chatInput = document.getElementById('chat-text');
    const chatMessagesDiv = document.getElementById('chat-messages');
    const contactsList = document.getElementById('contacts-list');

    let selectedUserImage = null;

    const authUserId = window.authUser.id;
    const authUserImage = window.authUser.image;
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // HELPER: FORMAT UTC TIME TO IST 
    function formatTimeToIST(utcTime) {
        const date = new Date(utcTime);
        return date.toLocaleTimeString('en-IN', {
            timeZone: 'Asia/Kolkata',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }

    //  MAP USER IMAGES + INIT LAST MESSAGE
    const contacts = Array.from(document.querySelectorAll('.contact-item'));
    const userImages = {};
    contacts.forEach(c => {
        userImages[c.dataset.id] = c.dataset.image;
        c.dataset.lastMessage = '1970-01-01T00:00:00Z'; // default old date
    });

    // REORDER CONTACTS BASED ON LAST MESSAGE 
    function reorderContacts() {
        const sorted = contacts.sort((a, b) => {
            const timeA = new Date(a.dataset.lastMessage).getTime();
            const timeB = new Date(b.dataset.lastMessage).getTime();
            return timeB - timeA;
        });
        sorted.forEach(c => contactsList.appendChild(c));
    }

    //  INITIAL LOAD: FETCH LAST MESSAGE FOR ALL CONTACTS
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

    function displayMessage(msg) {
        const senderImg = msg.sender_id == authUserId
            ? authUserImage
            : msg.sender_image || userImages[msg.sender_id];

        let tickHtml = '';

        if (msg.sender_id == authUserId) {

            let ticks = '✔';
            let tickClass = '';

            if (msg.seen) {
                ticks = '✔✔';
                tickClass = 'seen';
            } else if (msg.delivered) {
                ticks = '✔✔';
                tickClass = 'delivered';
            }

            tickHtml = `<span class="tick ${tickClass}">${ticks}</span>`;
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

    //  CONTACT CLICK
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
            .then(data => {
                const messages = data.messages;
                const unread = data.unread;

                messages.forEach(msg => displayMessage(msg));

                const contactElem = document.querySelector(`.contact-item[data-id="${selectedUserId}"]`);
                if (contactElem) {
                    const countElem = contactElem.querySelector('.unread-count');

                    if (countElem) {
                        countElem.innerText = unread;
                        countElem.classList.toggle('d-none', unread == 0);
                    }
                }
            });

        fetch(`http://127.0.0.1:8000/messages/${selectedUserId}/seen`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                auth_id: window.authUser.id
            })
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

    //  Private Channnel
window.Echo.private(`chat.${authUserId}`)
.listen('.message.sent', (msg) => {

    const m = msg.message;
    if (!m.sender_id) return;

    const contactElem = document.querySelector(`.contact-item[data-id="${m.sender_id}"]`);

    //  mark delivered 
    fetch(`/messages/${m.sender_id}/delivered`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            auth_id: window.authUser.id
        })
    });

   const isActiveChat = (selectedUserId == m.sender_id);

// ALWAYS update sidebar preview
if (contactElem) {
    contactElem.dataset.lastMessage = m.created_at;

    const preview = contactElem.querySelector('.contact-preview');
    const timeElem = contactElem.querySelector('.contact-time');

    if (preview) preview.innerText = m.chat_messages;
    if (timeElem) timeElem.innerText = formatTimeToIST(m.created_at);
}

//  If chat open → show message + mark seen
if (isActiveChat) {

    displayMessage(m);

    fetch(`/messages/${m.sender_id}/seen`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        },
        body: JSON.stringify({
            auth_id: window.authUser.id
        })
    });

} else {
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
            current++;

            countElem.innerText = current > 99 ? '99+' : current;
        }
    }
}
//  reorder always
reorderContacts();
});
});

//Online/Offline
document.addEventListener('DOMContentLoaded', function () {
    const contacts = document.querySelectorAll('.contact-item');

    function updateUserStatus(userId, isOnline) {
        const contact = document.querySelector(`.contact-item[data-id="${userId}"]`);
        if (!contact) return;

        const badge = contact.querySelector('.badge-dot');
        if (badge) {
            badge.classList.toggle('bg-success', isOnline);
            badge.classList.toggle('bg-secondary', !isOnline);
        }
    }
    window.Echo.join('chat-presence')
        .here(users => {
            users.forEach(user => {
                updateUserStatus(user.id, true);
            });
        })
        .joining(user => {
            updateUserStatus(user.id, true);
        })
        .leaving(user => {
            updateUserStatus(user.id, false);
        });

    document.querySelectorAll('.contact-item').forEach(c => {
        updateUserStatus(c.dataset.id, false);
    });

});


document.addEventListener('DOMContentLoaded', function () {
    const authUserId = window.authUser.id;
    const channel = window.Echo.private(`chat.${authUserId}`);

    channel.listen('.message.delivered', (e) => {
        console.log('DELIVERED EVENT:', e);

        if (e.messageIds) {
            e.messageIds.forEach(id => {
                const tick = document.querySelector(`.message[data-id="${id}"] .tick`);
                if (tick) {
                    tick.innerText = '✔✔';
                    tick.classList.remove('seen');
                    tick.classList.add('delivered');
                }
            });
        }
    });
    channel.listen('.message.seen', (e) => {
        console.log('SEEN EVENT:', e);

        if (e.messageIds) {
            e.messageIds.forEach(id => {
                const tick = document.querySelector(`.message[data-id="${id}"] .tick`);
                if (tick) {
                    tick.innerText = '✔✔';
                    tick.classList.remove('delivered');
                    tick.classList.add('seen');
                }
            });
        }
    });
    channel.listen('.message.deleted', (e) => {
    console.log('DELETE EVENT:', e);
    const msgDiv = document.querySelector(`.message[data-id="${e.messageId}"]`);
    if (msgDiv) {
        const content = msgDiv.querySelector('.message-content');
        const time = content.querySelector('.message-time')?.outerHTML || '';
        const tick = content.querySelector('.tick')?.outerHTML || '';
        let text = '';
        if (e.senderId == authUserId) {
            text = "<i>Deleted by you</i>";
        } else {
            text = "<i>Deleted by author</i>";
        }
        content.innerHTML = `
            ${text}
            ${time}
            ${tick}
        `;
    }
     const otherUserId = (e.senderId == authUserId) ? e.receiverId : e.senderId;

    const contactElem = document.querySelector(`.contact-item[data-id="${otherUserId}"]`);

    if (!contactElem) return;

    const preview = contactElem.querySelector('.contact-preview');

    //  if deleted msg is last one
    if (preview) {
        preview.innerText = "Message deleted";
    }
});
});

document.addEventListener('click', function (e) {
    const msgDiv = e.target.closest('.message');
    if (!msgDiv) return;
     if (!msgDiv.classList.contains('outgoing')) return;
    const msgId = msgDiv.dataset.id;

    // confirm alert
    if (confirm("Delete this message?")) {

        fetch(`/delete-message/${msgId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    fetch(`/messages/delivered`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    }
});

});




