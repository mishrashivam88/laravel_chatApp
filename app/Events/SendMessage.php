<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->receiver_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
    //     public function broadcastWith()
    // {
    //     return [
    //         'message' => $this->message, 
    //     ];
    // }

    public function broadcastWith()
{
    return [
        'id' => $this->message->id,
        'chat_messages' => $this->message->chat_messages,
        'sender_id' => $this->message->sender_id,
        'receiver_id' => $this->message->receiver_id,

        'file_url' => $this->message->file_path
            ? asset('storage/'.$this->message->file_path)
            : null,

        'file_type' => $this->message->file_type,

        'sender_image' => $this->message->sender && $this->message->sender->profile_img
            ? asset('storage/profile_images/'.$this->message->sender->profile_img)
            : null,

        'created_at' => $this->message->created_at,
    ];
}
}