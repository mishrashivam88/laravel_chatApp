<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
Broadcast::routes(['middleware' => ['web', 'auth']]);



Broadcast::channel('online-users', function ($user) {
    return ['id' => $user->id];
});

Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});