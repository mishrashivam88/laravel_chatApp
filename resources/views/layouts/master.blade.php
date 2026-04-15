<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat App</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        html,
        body {
            height: 100%;
            margin: 0;
        }

        body {
            background: #f2f2f2;
            font-family: Arial, sans-serif;
        }

        .message {
            display: flex;
            margin-bottom: 10px;
        }

        .message-content {
            padding: 10px;
            border-radius: 10px;
            background: #0d6efd;
            color: #1a0202;
            max-width: 60%;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .message-content {
            max-width: none !important;
        }

        .media-message {
            max-width: none !important;
        }

        .deleted video,
        .deleted img,
        .deleted a {
            display: none !important;
        }

        #imageModal {
            display: none;
        }

        .media-message {
            max-width: 300px;
        }

        .chat-image {
            width: 200px !important;
            height: auto !important;
            border-radius: 12px;
            display: block;
        }

        /* WRAPPER */
        .chat-input-wrapper {
            display: flex;
            align-items: center;
            background: #fff;
            padding: 8px 12px;
            border-radius: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            gap: 10px;
        }

        /* TEXT INPUT */
        #chat-text {
            flex: 1;
            border: none;
            outline: none;
            padding: 8px;
            font-size: 14px;
            border-radius: 20px;
        }

        /* ICON BUTTON (attach) */
        .icon-btn {
            cursor: pointer;
            font-size: 18px;
            color: #555;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .icon-btn:hover {
            color: #0d6efd;
        }

        /* SEND BUTTON */
        .send-btn {
            border: none;
            background: #0d6efd;
            color: #fff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
        }

        .send-btn:hover {
            background: #0b5ed7;
            transform: scale(1.05);
        }

        .image-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            cursor: zoom-out;
        }

        .image-modal-overlay img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 10px;
        }

        /* IMAGE */
        .chat-image {
            width: 100%;
            /* full bubble width */
            max-width: 350px;
            /* bigger than before */
            max-height: 400px;
            /* bigger height */
            border-radius: 12px;
            margin-top: 5px;
            cursor: zoom-in;
            object-fit: cover;
            transition: 0.3s;
        }

        .chat-image:hover {
            transform: scale(1.05);
        }

        /* VIDEO */
        .chat-video {
            max-width: 300px;
            border-radius: 12px;
            margin-top: 5px;
        }

        /* FILE */
        .chat-file {
            display: inline-block;
            margin-top: 5px;
            padding: 6px 10px;
            background: #f1f1f1;
            border-radius: 8px;
            text-decoration: none;
        }

        /* AVATAR */
        .avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .contact-preview {
            display: inline-block;
            max-width: 150px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .dropdown-menu {
            position: absolute;
            right: 10px;
            top: 30px;
            background: white;
            border: 1px solid #ccc;
            border-radius: 6px;
            z-index: 10;
        }

        .dropdown-menu div {
            padding: 5px 10px;
            cursor: pointer;
        }

        .dropdown-menu div:hover {
            background: #f2f2f2;
        }

        .message-content {
            position: relative;
        }

        .tick {
            color: gray;
        }

        .tick.delivered {
            color: gray;
        }

        .tick.seen {
            color: blue;
        }

        .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            position: absolute;
            left: 45px;
            bottom: 20px;
            border: 2px solid #fff;
        }

        .bg-success {
            background-color: #28a745 !important;
            /* online */
        }

        .bg-secondary {
            background-color: #6c757d !important;
            /* offline */
        }

        .contact-info {
            display: flex;
            align-items: center;
            flex-grow: 1;
            position: relative;
        }

        .unread-count {
            position: absolute;
            right: 0px;
            top: 0px;
            font-size: 12px;
        }

        #chat-container {
            height: 100vh;
            display: flex;
            flex-direction: row;
            background: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }


        #chat-header {
            border-bottom: 1px solid #ddd;
            background: #f8f9fa;
        }

        /* Left contacts */
        #contacts {
            width: 30%;
            border-right: 1px solid #ddd;
            display: flex;
            flex-direction: column;
        }

        #contacts-header {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
        }

        #contacts-search input {
            width: 100%;
        }

        #contacts-list {
            flex-grow: 1;
            overflow-y: auto;
        }

        .contact-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            cursor: pointer;
            position: relative;
            transition: background 0.2s;
        }

        .contact-item:hover {
            background: #f5f5f5;
        }

        .contact-info {
            display: flex;
            align-items: center;
        }

        .contact-info img {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 0.75rem;
        }

        .contact-info .badge-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: -15px;
            margin-top: -5px;
            border: 2px solid #fff;
        }

        /* Right chat */
        #chat {
            width: 70%;
            display: flex;
            flex-direction: column;
        }

        #chat-messages {
            flex-grow: 1;
            padding: 1rem;
            overflow-y: auto;
            background: #e5ddd5;
        }

        .message {
            display: flex;
            margin-bottom: 0.75rem;
        }

        .message img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .message-content {
            max-width: 75%;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            margin-left: 0.5rem;
            background: #fff;
        }

        .message.outgoing {
            flex-direction: row-reverse;
        }

        .message.outgoing .message-content {
            background: #0b93f6;
            color: #fff;
            margin-left: 0;
            margin-right: 0.5rem;
        }

        /* Input bar */
        #chat-input {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-top: 1px solid #ddd;
            background: #f5f5f5;
        }

        #chat-input input {
            flex-grow: 1;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            border: 1px solid #ccc;
            margin: 0 0.5rem;
        }

        #chat-input i {
            cursor: pointer;
            font-size: 1.2rem;
            color: #555;
        }

        #chat-container {
            height: calc(100vh - 64px);
        }
    </style>

    @vite(['resources/js/app.js'])

</head>

<body>
    <!-- Navbar -->
    @include('layouts.navbar')

    <div id="chat-container">
        <!-- Left contacts -->
        @include('layouts.left')

        <!-- Right chat -->
        <div id="chat">
            <div id="chat-header">
                @include('layouts.right_upper')
            </div>
            @include('layouts.chats')
            @include('layouts.send')
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll chat to bottom on load
        const chatMessages = document.getElementById('chat-messages');
        chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>

    <script>
        window.authUser = {
            id: @json(Auth::id()),
            image: @json(Auth::check() ? asset('storage/profile_images/'.Auth::user() -> profile_img) : '')
        };

        window.authToken = "{{ session('auth_token') }}";

        function openImage(url) {
            let modal = document.getElementById('imageModal');

            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'imageModal';
                modal.innerHTML = `
            <div class="image-modal-overlay">
                <span class="close-btn" onclick="closeImage()">✖</span>
                <img id="modalImg" />
            </div>
        `;
                document.body.appendChild(modal);
            }

            document.getElementById('modalImg').src = url;
            modal.style.display = 'flex';
        }

        function closeImage() {
            document.getElementById('imageModal').style.display = 'none';
        }
    </script>
</body>

</html>