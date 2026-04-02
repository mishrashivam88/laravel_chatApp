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
            max-width: 70%;
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
            height: calc(100vh - 56px);
            /* 56px is default navbar height */
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
        image: @json(Auth::check() ? asset('storage/profile_images/'.Auth::user()->profile_img) : '')
    };
</script>
</body>

</html>