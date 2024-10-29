<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuickCountUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $formattedData;

    public function __construct($formattedData)
    {
        $this->formattedData = $formattedData;
    }

    public function broadcastOn()
    {
        return new Channel('quick-count-channel');
    }

    public function broadcastAs()
    {
        return 'quick-count-updated';
    }
}
