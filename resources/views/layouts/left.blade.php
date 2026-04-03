    <style>
        .contact-info img,
        #chat-user-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            /* image crop ho jayegi nicely */
            border-radius: 50%;
            /* perfect circle */
        }
    </style>

    @php
    use App\Models\User;
    use App\Models\Message;

    $users = User::whereNot('id', Auth::id())->get();

    // function to get last message with this user
    function getLastMessage($userId) {
    return Message::where(function($q) use ($userId){
    $q->where('sender_id', Auth::id())->where('receiver_id', $userId);
    })->orWhere(function($q) use ($userId){
    $q->where('sender_id', $userId)->where('receiver_id', Auth::id());
    })->orderBy('created_at', 'desc')->first();
    }
    @endphp

    <div id="contacts">
        <div id="contacts-header">
            <div id="contacts-search" class="mt-2">
                <input type="text" id="searchInput" class="form-control" placeholder="Search contacts">
            </div>
        </div>


        <div id="contacts-list">
            @foreach ($users as $user)
            @php
            $lastMsg = getLastMessage($user->id);
            $lastText = $lastMsg ? $lastMsg->chat_messages : 'Hello!';
            $lastTime = $lastMsg ? $lastMsg->created_at : '';
            @endphp

            <div class="contact-item" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-image="{{ asset('storage/profile_images/'.$user->profile_img) }}" data-last-message="{{ $lastTime }}">
                <div class="contact-info">
                    <img src="{{ asset('storage/profile_images/'.$user->profile_img) }}">
                    <div>
                        <p class="mb-0 fw-bold">{{ $user->name }}</p>
                        <small class="text-muted contact-preview">{{ $lastText }}</small>
                        <span class="contact-time">{{ $lastTime ? date('h:i A', strtotime($lastTime)) : '' }}</span>
                    </div>
                    <span class="unread-count badge bg-danger d-none">{{ $user->unreadMessagesCount ?? 0 }}</span>
                </div>
                <span class="badge-dot bg-success"></span>
            </div>
            @endforeach
        </div>

    </div>


    <script>  
        //search logic 
        const searchInput = document.getElementById('searchInput');
        const contactsList = document.getElementById('contacts-list');
        const contacts = contactsList.getElementsByClassName('contact-item');

        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();

            Array.from(contacts).forEach(contact => {
                const name = contact.getAttribute('data-name').toLowerCase();
                if (name.includes(filter)) {
                    contact.style.display = '';
                } else {
                    contact.style.display = 'none';
                }
            });
        });
    </script>