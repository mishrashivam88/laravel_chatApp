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
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    // SEND MESSAGE
    // public function sendMessage(Request $request)
    // {
    //     $request->validate([
    //         'chat_messages' => 'required|string',
    //         'receiver_id' => 'required|exists:users,id',
    //     ]);

    //     $message = Message::create([
    //         'chat_messages' => $request->chat_messages,
    //         'sender_id' => Auth::id(),
    //         'receiver_id' => $request->receiver_id,
    //     ]);

       
    //     $message->load('sender');

    //     //  BROADCAST (NO toOthers)
    //     broadcast(new SendMessage($message));

    //     return response()->json([
    //         ...$message->toArray(),
    //         'sender_image' => $message->sender->image ?? null
    //     ]);
    // }
   public function sendMessage(Request $request)
{
    $request->validate([
        'chat_messages' => 'nullable|string',
        'receiver_id' => 'required|exists:users,id',
'file' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/webp,application/pdf,video/mp4,video/webm,video/quicktime|max:51200'       
    ]);

    if (!$request->chat_messages && !$request->hasFile('file')) {
        return response()->json([
            'error' => 'Message or file required'
        ], 422);
    }

    $filePath = null;
    $fileType = null;

    //  FILE UPLOAD
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        Log::info('FILE DEBUG', [
        'name' => $file->getClientOriginalName(),
        'size_kb' => round($file->getSize() / 1024, 2),
        'mime' => $file->getMimeType(),
        'valid' => $file->isValid(),
        'extension' => $file->getClientOriginalExtension(),
    ]);
        $filePath = $file->store('chat_files', 'public');

        $mime = $file->getMimeType();

        if (str_starts_with($mime, 'image')) {
            $fileType = 'image';
        } elseif (str_starts_with($mime, 'video')) {
            $fileType = 'video';
        } else {
            $fileType = 'file';
        }
    }

    $message = Message::create([
        'chat_messages' => $request->chat_messages ?? '', 
        'sender_id' => Auth::id(),
        'receiver_id' => $request->receiver_id,
        'file_path' => $filePath,
        'file_type' => $fileType,
    ]);

    $message->load('sender');

    broadcast(new SendMessage($message))->toOthers();

    return response()->json([
        'id' => $message->id,
        'chat_messages' => $message->chat_messages,
        'sender_id' => $message->sender_id,
        'receiver_id' => $message->receiver_id,
        'file_type' => $message->file_type,
        'file_url' => $filePath ? asset('storage/'.$filePath) : null,

        'sender_image' => $message->sender && $message->sender->profile_img
            ? asset('storage/profile_images/'.$message->sender->profile_img)
            : null,

        'created_at' => $message->created_at,
    ]);
}

    // GET CHAT MESSAGES
//     public function getMessages(User $user)
//     {
//         $messages = Message::withTrashed()->with('sender')
//             ->where(function ($q) use ($user) {
//                 $q->where('sender_id', Auth::id())
//                     ->where('receiver_id', $user->id);
//             })
//             ->orWhere(function ($q) use ($user) {
//                 $q->where('sender_id', $user->id)
//                     ->where('receiver_id', Auth::id());
//             })
//             ->orderBy('created_at')
//             ->get();

//         //  attach sender image
//        $messages->transform(function ($msg) {

//     $msg->sender_image = $msg->sender->image ?? null;

//     //  SOFT DELETE FIX
//     if ($msg->deleted_at) {
//         if ($msg->sender_id == Auth::id()) {
//             $msg->chat_messages = '<i>Deleted by you</i>';
//         } else {
//             $msg->chat_messages = '<i>Deleted by author</i>';
//         }
//     }

//     return $msg;
// });

//         //  UNREAD FIX (delivered based)
//         $unreadCount = Message::where('sender_id', $user->id)
//             ->where('receiver_id', Auth::id())
//             ->where('seen', 0)
//             ->count();

//         return response()->json([
//             'messages' => $messages,
//             'unread' => $unreadCount
//         ]);
//     }
public function getMessages(User $user)
{
    $authId = Auth::id();

    $messages = Message::withTrashed()
        ->with('sender')
        ->where(function ($q) use ($user, $authId) {
            $q->where('sender_id', $authId)
              ->where('receiver_id', $user->id);
        })
        ->orWhere(function ($q) use ($user, $authId) {
            $q->where('sender_id', $user->id)
              ->where('receiver_id', $authId);
        })
        ->orderBy('created_at' , 'desc')
        ->paginate(20);

    //  TRANSFORM
    $messages->transform(function ($msg) use ($authId) {

        $msg->sender_image = $msg->sender && $msg->sender->profile_img
            ? asset('storage/profile_images/'.$msg->sender->profile_img)
            : null;

        $msg->file_url = $msg->file_path
            ? asset('storage/'.$msg->file_path)
            : null;

        //  DELETE 
        if ($msg->deleted_at) {
              return [
            'id' => $msg->id,
            'sender_id' => $msg->sender_id,
            'receiver_id' => $msg->receiver_id,

           
            'chat_messages' => $msg->sender_id == $authId
                ? '<i>Deleted by you</i>'
                : '<i>Deleted by author</i>',

            
            'file_type' => null,
            'file_url' => null,

            'sender_image' => $msg->sender_image,
            'created_at' => $msg->created_at,
            'is_deleted' => true,
        ];
    }
        return $msg;
    });

    //  MARK DELIVERED
    Message::where('sender_id', $user->id)
        ->where('receiver_id', $authId)
        ->where('delivered', 0)
        ->update(['delivered' => 1]);

    //  UNREAD COUNT
    $unreadCount = Message::where('sender_id', $user->id)
        ->where('receiver_id', $authId)
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
