<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
// use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldBroadcast
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
        public function broadcastWith()
    {
        return [
            'message' => $this->message, // ensures JS receives `e.message`
        ];
    }
}