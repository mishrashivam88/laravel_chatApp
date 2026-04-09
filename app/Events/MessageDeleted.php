<?php
namespace App\Events;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageDeleted implements ShouldBroadcastNow
{
    public $messageId;
    public $senderId;
    public $receiverId;
    public $createdAt;

    public function __construct($message)
    {
        $this->messageId = $message->id;
        $this->senderId = $message->sender_id;
        $this->receiverId = $message->receiver_id;
        $this->createdAt = $message->created_at;
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