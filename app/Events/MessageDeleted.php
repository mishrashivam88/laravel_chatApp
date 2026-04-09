<?php
namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Auth;

class MessageDeleted implements ShouldBroadcast
{
    public $messageId;
    public $senderId;
    public $receiverId;

    public function __construct($message)
    {
        $this->messageId = $message->id;
        $this->senderId = $message->sender_id;
        $this->receiverId = $message->receiver_id;
    }

    public function broadcastOn()
    {
        return [
            new PrivateChannel('chat.' . $this->senderId),
            new PrivateChannel('chat.' . $this->receiverId),
        ];
    }

    public function broadcastAs()
    {
        return 'message.deleted';
    }
}