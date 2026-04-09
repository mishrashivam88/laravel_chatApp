<?php

namespace App\Http\Controllers;

use App\Events\MessageDelivered;
use App\Events\MessageSeen;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Events\SendMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Events\MessageDeleted;

class MessageController extends Controller
{
    // SEND MESSAGE
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

        //  LOAD sender relation (IMAGE FIX)
        $message->load('sender');

        //  BROADCAST (NO toOthers)
        broadcast(new SendMessage($message));

        return response()->json([
            ...$message->toArray(),
            'sender_image' => $message->sender->image ?? null
        ]);
    }

    // GET CHAT MESSAGES
    public function getMessages(User $user)
    {
        $messages = Message::with('sender') //  IMPORTANT
            ->where(function ($q) use ($user) {
                $q->where('sender_id', Auth::id())
                    ->where('receiver_id', $user->id);
            })
            ->orWhere(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->where('receiver_id', Auth::id());
            })
            ->orderBy('created_at')
            ->get();

        //  attach sender image
        $messages->transform(function ($msg) {
            $msg->sender_image = $msg->sender->image ?? null;
            return $msg;
        });

        //  UNREAD FIX (delivered based)
        $unreadCount = Message::where('sender_id', $user->id)
            ->where('receiver_id', Auth::id())
            ->where('seen', 0)
            ->count();

        return response()->json([
            'messages' => $messages,
            'unread' => $unreadCount
        ]);
    }

    // MARK AS SEEN
    public function markAsSeen(Request $request, $userId)
    {
        $authId = $request->input('auth_id');

        $messages = Message::where('sender_id', $userId)
            ->where('receiver_id', $authId)
            ->where('seen', 0)
            ->get();

        Message::whereIn('id', $messages->pluck('id'))
            ->update(['seen' => 1]);

        if ($messages->isNotEmpty()) {
            broadcast(new MessageSeen(
                $messages->pluck('id')->toArray(),
                $userId
            ));
        }

        return response()->json([
            'updated' => $messages->count()
        ]);
    }
    
    public function markDelivered()
    {
        $messages = Message::where('receiver_id', Auth::id())
            ->where('delivered', 0)
            ->get();

        if ($messages->isEmpty()) return;

        Message::whereIn('id', $messages->pluck('id'))
            ->update(['delivered' => 1]);

        $grouped = $messages->groupBy('sender_id');

        foreach ($grouped as $senderId => $msgs) {
            broadcast(new MessageDelivered(
                $msgs->pluck('id')->toArray(),
                $senderId
            ));
        }
    }

    public function deleteMessage($id)
    {
        $message = Message::findOrFail($id);

        if ($message->sender_id !== Auth::id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message->delete();

        broadcast(new MessageDeleted($message));

        return response()->json(['success' => true]);
    }
}
