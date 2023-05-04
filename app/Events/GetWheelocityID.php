<?php

namespace App\Events;

use App\Models\WheelocityID;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GetWheelocityID implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $wheelocityID;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(WheelocityID $wheelocityID)
    {
        $this->wheelocityID = $wheelocityID;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return ['wheelocity-id'];
    }

    public function broadcastAs()
    {
        return 'get-wheelocity-id';
    }
}
