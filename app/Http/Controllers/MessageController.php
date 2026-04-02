<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SendMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth ;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    // One-to-one message send
    public function sendMessage(Request $request)
{
    $request->validate([
        'chat_messages' => 'required|string',
        'receiver_id' => 'required|exists:users,id',
    ]);

    $message = Message::create([
        'chat_messages' => $request->chat_messages,
        'sender_id' => Auth::id(),
        'receiver_id' => $request->receiver_id,
    ]);

    Log::info('Broadcasting message', ['message' => $message]);

    broadcast(new SendMessage($message))->toOthers(); // do not send to self

    return response()->json($message);
}

    public function getMessages(User $user)
    {
        $messages = Message::where(function($q) use ($user) {
            $q->where('sender_id', Auth::id())
              ->where('receiver_id', $user->id);
        })->orWhere(function($q) use ($user) {
            $q->where('sender_id', $user->id)
              ->where('receiver_id', Auth::id());
        })->orderBy('created_at')->get();

        return response()->json($messages);
    }
}