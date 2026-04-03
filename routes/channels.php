<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

Broadcast::channel('chat.{id}', function ($user, $id) {
    // Only allow user to join their own channel
    return (int) $user->id === (int) $id ? ['id' => $user->id, 'name' => $user->name] : false;
});