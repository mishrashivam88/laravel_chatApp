<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDelivered implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public $messageIds;
    public $userId;

    public function __construct($messageIds, $userId)
    {
        $this->messageIds = $messageIds;
        $this->userId = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'message.delivered';
    }
    public function broadcastWith()
{
    return [
        'messageIds' => $this->messageIds,
        'userId' => $this->userId,
    ];
}
}