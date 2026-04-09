<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
Broadcast::routes(['middleware' => ['web', 'auth']]);

Broadcast::channel('chat-presence', function ($user) {

    // file_put_contents(storage_path('logs/user.txt'), json_encode($user));

    if (!$user) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
    ];
});


Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});